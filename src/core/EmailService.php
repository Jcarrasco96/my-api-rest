<?php

namespace SimpleApiRest\core;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class EmailService
{

    private PHPMailer $mailer;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $mailConfig = BaseApplication::$config['mail'];

        $this->mailer = new PHPMailer(true);
        $this->mailer->isSMTP();
        $this->mailer->Host = $mailConfig['host'];
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = $mailConfig['username'];
        $this->mailer->Password = $mailConfig['password'];
        $this->mailer->SMTPSecure = $mailConfig['encryption'];
        $this->mailer->Port = $mailConfig['port'];
        $this->mailer->setFrom($mailConfig['username'], 'SimpleApiRest');
    }

    /**
     * @throws Exception
     */
    public function sendEmail(string $to, string $subject, string $body): bool
    {
        $this->mailer->addAddress($to);
        $this->mailer->isHTML();
        $this->mailer->Subject = $subject;
        $this->mailer->Body = $body;
        return $this->mailer->send();
    }

}