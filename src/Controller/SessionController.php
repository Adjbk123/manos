<?php

namespace App\Controller;

use App\Entity\Billetage;
use App\Entity\RapportSession;
use App\Entity\ResumeDetailSession;
use App\Entity\ResumeGlobalSession;
use App\Entity\SessionService;
use App\Repository\AccountRepository;
use App\Repository\OperatorRepository;
use App\Repository\SessionServiceRepository;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/sessions')]
class SessionController extends AbstractController
{
    #[Route('/status', name: 'api_session_status', methods: ['GET'])]
    public function status(SessionServiceRepository $sessionRepository): JsonResponse
    {
        $user = $this->getUser();
        $session = $sessionRepository->findActiveSessionByUser($user);

        // Get last closed session for summary
        $lastSession = $sessionRepository->createQueryBuilder('s')
            ->andWhere('s.agent = :user')
            ->andWhere('s.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', SessionService::STATUS_CLOSED)
            ->orderBy('s.endedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $this->json([
            'isActive' => $session !== null,
            'session' => $session,
            'lastSession' => $lastSession
        ], 200, [], ['groups' => ['session:read']]);
    }

    #[Route('/start', name: 'api_session_start', methods: ['POST'])]
    public function start(
        SessionServiceRepository $sessionRepository,
        AccountRepository $accountRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $this->getUser();
        $activeSession = $sessionRepository->findAnyActiveSession();

        if ($activeSession) {
            if ($activeSession->getAgent() === $user) {
                return $this->json(['message' => 'Vous avez déjà une session ouverte', 'session' => $activeSession], 200, [], ['groups' => ['session:read']]);
            }
            return $this->json(['message' => 'Une session est déjà tenue par ' . $activeSession->getAgent()->getNom()], 403);
        }

        $session = new SessionService();
        $session->setAgent($user);
        $session->setStatus(SessionService::STATUS_OPEN);

        // Initialiser les rapports de session avec les soldes actuels
        $accounts = $accountRepository->findAll();
        foreach ($accounts as $account) {
            $rapport = new RapportSession();
            $rapport->setSession($session);
            $rapport->setCompte($account);
            $rapport->setSoldeOuverture($account->getBalance());
            $em->persist($rapport);
        }

        $em->persist($session);
        $em->flush();

        return $this->json([
            'message' => 'Session ouverte avec succès',
            'session' => $session
        ], 201, [], ['groups' => ['session:read']]);
    }

    #[Route('/end', name: 'api_session_end', methods: ['POST'])]
    public function end(
        Request $request,
        SessionServiceRepository $sessionRepository,
        TransactionRepository $transactionRepository,
        OperatorRepository $operatorRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $this->getUser();
        $session = $sessionRepository->findActiveSessionByUser($user);

        if (!$session) {
            return $this->json(['message' => 'Aucune session active trouvée'], 404);
        }

        $data = json_decode($request->getContent(), true);

        $typeFermeture = $data['typeFermeture'] ?? SessionService::TYPE_SIMPLE;
        $confirmedBalances = $data['confirmedBalances'] ?? []; // [accountId => balance]
        $billetageData = $data['billetage'] ?? [];

        $session->setEndedAt(new \DateTimeImmutable());
        $session->setStatus(SessionService::STATUS_CLOSED);
        $session->setTypeFermeture($typeFermeture);

        $totalTheoretical = 0;
        $totalConfirmed = 0;

        // Mettre à jour les rapports de session
        foreach ($session->getRapports() as $rapport) {
            $account = $rapport->getCompte();
            $accId = (string) $account->getId();
            
            $theoretical = $account->getBalance();
            $confirmed = $confirmedBalances[$accId] ?? $theoretical;

            $rapport->setSoldeTheoriqueFermeture($theoretical);
            $rapport->setSoldeConfirmeFermeture((string) $confirmed);
            $rapport->setEcart((string) ($confirmed - $theoretical));

            // Calculer les stats de transactions pour ce compte/réseau pendant la session
            $stats = $transactionRepository->getSessionStatsByAccount($session, $account);
            $rapport->setTotalDepots($stats['deposits'] ?? '0');
            $rapport->setTotalRetraits($stats['withdrawals'] ?? '0');
            $rapport->setTotalVentes($stats['sales'] ?? '0');

            $totalTheoretical += $theoretical;
            $totalConfirmed += $confirmed;
        }

        // Billetage
        $billetage = new Billetage();
        $billetage->setSession($session);
        $billetage->setDetails($billetageData['details'] ?? []);
        $billetage->setTotalTheorique($billetageData['theoretical'] ?? '0');
        $billetage->setTotalPhysique($billetageData['physical'] ?? '0');
        $billetage->setEcart((string) (($billetageData['physical'] ?? 0) - ($billetageData['theoretical'] ?? 0)));
        $em->persist($billetage);

        // Resume Global
        $global = new ResumeGlobalSession();
        $global->setSession($session);
        $global->setValeurTheoriqueTotale((string) $totalTheoretical);
        $global->setValeurConfirmeeTotale((string) $totalConfirmed);
        $global->setEcartTotal((string) ($totalConfirmed - $totalTheoretical));
        $em->persist($global);

        // Resume Detail (Aggregated by Operator and Category)
        $operators = $operatorRepository->findAll();
        foreach ($operators as $op) {
            $detailStats = $transactionRepository->getSessionStatsByOperator($session, $op);
            foreach ($detailStats as $type => $values) {
                if ($values['volume'] > 0) {
                    $detail = new ResumeDetailSession();
                    $detail->setSession($session);
                    $detail->setOperateur($op);
                    $detail->setTypeBesognee($type);
                    $detail->setVolume((string) $values['volume']);
                    $detail->setNombre($values['count']);
                    $em->persist($detail);
                }
            }
        }

        $em->flush();

        return $this->json([
            'message' => 'Session clôturée avec succès',
            'session' => $session
        ], 200, [], ['groups' => ['session:read']]);
    }
}
