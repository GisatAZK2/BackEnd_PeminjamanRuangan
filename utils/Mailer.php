<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

class Mailer
{
    public static function sendAdminNotification($subject, $body)
    {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'gisatazk2@gmail.com';
            $mail->Password = 'kpld krrk ratp hbyl';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('gisatazk2@gmail.com', 'Sistem Ruangan');
            $mail->addAddress('gisatazk2@gmail.com', 'Administrator');

            $mail->isHTML(true);
            $mail->Subject = $subject;

            // Design email yang lebih cakep
            $styledBody = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; color: #333; background-color: #f4f4f4; padding: 20px; }
                    .container { max-width: 600px; margin: 0 auto; background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
                    .header { background-color: #007bff; color: #fff; padding: 10px; text-align: center; border-radius: 8px 8px 0 0; }
                    .content { padding: 20px; }
                    .footer { text-align: center; font-size: 12px; color: #777; margin-top: 20px; }
                    a { color: #007bff; text-decoration: none; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>{$subject}</h2>
                    </div>
                    <div class='content'>
                        {$body}
                    </div>
                    <div class='footer'>
                        <p>&copy; " . date('Y') . " Sistem Manajemen User. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
            ";

            $mail->Body = $styledBody;

            $mail->send();
        } catch (Exception $e) {
            error_log("Mailer Error: {$mail->ErrorInfo}");
        }
    }
}