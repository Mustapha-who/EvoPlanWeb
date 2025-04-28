<?php

namespace App\Service\UserModule;

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Messenger\Transport\TransportFactory;
use Symfony\Component\Mime\Email;

class UserEmailService
{
    private string $apiMailerDsn;
    private Mailer $mailer;

    public function __construct( string $apiMailerDsn)
    {
        $transport = Transport::fromDsn($apiMailerDsn);
        $this->mailer = new Mailer($transport);
    }

    public function sendEmail(string $to, string $subject, string $textContent, string $htmlContent = null): void
    {
        $email = (new Email())
            ->sender('MS_Ezbemm@test-yxj6lj992514do2r.mlsender.net')
            ->to($to)
            ->subject($subject)
            ->text($textContent);

        if ($htmlContent) {
            $email->html($htmlContent);
        }

        $this->mailer->send($email);
    }
}