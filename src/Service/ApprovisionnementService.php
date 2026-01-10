<?php

namespace App\Service;

use App\Entity\Account;
use App\Entity\ApproRequest;
use App\Entity\Operator;
use App\Entity\User;
use App\Repository\ApproRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ApprovisionnementService
{
    private EntityManagerInterface $em;
    private BalanceService $balanceService;
    private MailerService $mailerService;

    public function __construct(EntityManagerInterface $em, BalanceService $balanceService, MailerService $mailerService)
    {
        $this->em = $em;
        $this->balanceService = $balanceService;
        $this->mailerService = $mailerService;
    }

    public function createRequest(User $admin, array $data): ApproRequest
    {
        $operator = $this->em->getRepository(Operator::class)->find($data['operator_id'] ?? 0);
        if (!$operator) {
            throw new BadRequestHttpException('Opérateur invalide.');
        }

        $agent = $this->em->getRepository(User::class)->find($data['agent_id'] ?? 0);
        if (!$agent) {
             throw new BadRequestHttpException('Agent invalide.');
        }

        if (!isset($data['montant']) || !is_numeric($data['montant']) || $data['montant'] <= 0) {
            throw new BadRequestHttpException('Le montant doit être un nombre positif.');
        }

        if (!in_array($data['compte'], ['physique', 'virtuel'])) {
            throw new BadRequestHttpException('Type de compte invalide.');
        }

        $request = new ApproRequest();
        $request->setOperator($operator);
        $request->setAgent($agent);
        $request->setCreatedBy($admin);
        $request->setCompte($data['compte']);
        $request->setMontant($data['montant']);
        $request->setNotes($data['notes'] ?? null);
        $request->setStatus(ApproRequest::STATUS_PENDING);

        $this->em->persist($request);
        $this->em->flush();
        
        $this->mailerService->sendApproNotification($request);

        return $request;
    }

    public function validateRequest(User $validator, int $requestId): ApproRequest
    {
        $request = $this->em->getRepository(ApproRequest::class)->find($requestId);
        if (!$request) {
            throw new BadRequestHttpException('Demande non trouvée.');
        }

        if ($request->getStatus() !== ApproRequest::STATUS_PENDING) {
            throw new BadRequestHttpException('Cette demande n\'est plus en attente.');
        }

        if ($validator->getId() !== $request->getAgent()->getId()) {
             throw new AccessDeniedHttpException('Seul l\'agent concerné peut valider cette réception.');
        }

        $request->setStatus(ApproRequest::STATUS_APPROVED);
        $request->setValidatedBy($validator);
        $request->setValidatedAt(new \DateTime());

        // Impact Balance via BalanceService
        $amount = $request->getMontant();
        $accountRepo = $this->em->getRepository(Account::class);

        if ($request->getCompte() === 'virtuel') {
            // Appro UV: +Virtuel Operator, -Physique Global
            $virtAccount = $accountRepo->findOneBy(['operator' => $request->getOperator(), 'type' => Account::TYPE_VIRTUAL]);
            $physAccount = $accountRepo->findOneBy(['operator' => null, 'type' => Account::TYPE_PHYSICAL]);
            
            if ($virtAccount) $this->balanceService->adjust($virtAccount, $amount, $validator);
            if ($physAccount) $this->balanceService->adjust($physAccount, "-$amount", $validator);
        } else {
            // Appro Cash: +Physique Global
            $physAccount = $accountRepo->findOneBy(['operator' => null, 'type' => Account::TYPE_PHYSICAL]);
            if ($physAccount) $this->balanceService->adjust($physAccount, $amount, $validator);
        }

        $this->em->flush();
        $this->mailerService->sendValidationNotification($request);

        return $request;
    }

    // rejectRequest remains same...
    public function rejectRequest(User $validator, int $requestId): ApproRequest
    {
        $request = $this->em->getRepository(ApproRequest::class)->find($requestId);
        if (!$request) throw new BadRequestHttpException('Demande non trouvée.');
        if ($request->getStatus() !== ApproRequest::STATUS_PENDING) throw new BadRequestHttpException('Plus en attente.');
        if ($validator->getId() !== $request->getAgent()->getId()) throw new AccessDeniedHttpException('Non autorisé.');

        $request->setStatus(ApproRequest::STATUS_REJECTED);
        $request->setValidatedBy($validator);
        $request->setValidatedAt(new \DateTime());
        $this->em->flush();
        return $request;
    }
}
