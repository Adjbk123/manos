<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

class MailerService
{
    public function __construct(
        private MailerInterface $mailer
    ) {}

    /**
     * Envoie un email de réinitialisation de mot de passe.
     */
    public function sendResetPasswordEmail(User $user, string $token): void
    {
        $resetUrl = "http://manos.local/reset-password?token=" . $token;

        $email = (new TemplatedEmail())
            ->from('hello@manosphone.com')
            ->to($user->getEmail())
            ->subject('Réinitialisation de votre mot de passe - MANOS')
            ->htmlTemplate('emails/reset_password.html.twig')
            ->context([
                'user_nom' => $user->getNom(),
                'reset_url' => $resetUrl,
            ]);

        $this->mailer->send($email);
    }

    /**
     * Exemple d'autre méthode d'envoi d'email (ex: bienvenue)
     */
    public function sendWelcomeEmail(User $user): void
    {
        $email = (new TemplatedEmail())
            ->from('hello@manosphone.com')
            ->to($user->getEmail())
            ->subject('Bienvenue chez MANOS')
            ->htmlTemplate('emails/welcome.html.twig')
            ->context([
                'user_nom' => $user->getNom(),
            ]);

        $this->mailer->send($email);
    }

    public function sendApproNotification(\App\Entity\ApproRequest $request): void
    {
        $agent = $request->getAgent();
        if ($agent->getEmail()) {
            $email = (new TemplatedEmail())
                ->from('hello@manosphone.com')
                ->to($agent->getEmail())
                ->subject('Nouvel Approvisionnement Mobile Money')
                ->htmlTemplate('emails/appro_new.html.twig')
                ->context([
                    'request' => $request,
                    'agent' => $agent,
                    'operator' => $request->getOperator()
                ]);

            try {
                $this->mailer->send($email);
            } catch (\Exception $e) {
                // Log error but don't block flow
            }
        }
    }

    public function sendValidationNotification(\App\Entity\ApproRequest $request): void
    {
        $admin = $request->getCreatedBy();
        if ($admin && $admin->getEmail()) {
            $email = (new TemplatedEmail())
                ->from('hello@manosphone.com')
                ->to($admin->getEmail())
                ->subject('Approvisionnement Validé')
                ->htmlTemplate('emails/appro_validated.html.twig')
                ->context([
                    'request' => $request,
                    'agent' => $request->getAgent()
                ]);

            try {
                $this->mailer->send($email);
            } catch (\Exception $e) {
                // Log error
            }
        }
    }
}
