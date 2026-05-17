<?php
namespace App\Services\Adapters;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class SmtpAdapter
{
    protected array $cfg;

    public function __construct(array $cfg)
    {
        $this->cfg = $cfg;
    }

    public function send(string $to, string $subject, string $body): void
    {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $this->cfg['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->cfg['username'];
            $mail->Password = $this->cfg['password'];
            $mail->SMTPSecure = $this->cfg['secure'] === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = $this->cfg['port'];

            $mail->setFrom($this->cfg['from']);
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->isHTML(false);

            $mail->send();
        } catch (PHPMailerException $e) {
            throw new \RuntimeException('SMTP send failed: ' . $e->getMessage());
        }
    }
}
