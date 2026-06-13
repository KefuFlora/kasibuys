<?php 

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../phpmailer/src/Exception.php';
require_once __DIR__ . '/../phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../phpmailer/src/SMTP.php';


function sendEmail(string $to_email, string $to_name, string $subject, string $body): bool {
    
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'rampaikefuoe@gmail.com';    // Replace with your Gmail address
        $mail->Password   = 'teynkjuxxkoqvzwp'; // Replace with your 16-character app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('rampaikefuoe@gmail.com', 'KasiBuys'); // Replace with your Gmail address
        $mail->addAddress($to_email, $to_name);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = emailTemplate($subject, $body);
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email error: " . $mail->ErrorInfo);
        return false;
    }
}

function emailTemplate(string $title, string $content): string {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: 'Segoe UI', sans-serif; background: #f5f5f5; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 40px auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 15px rgba(0,0,0,0.1); }
            .header { background: #111111; padding: 30px; text-align: center; }
            .header h1 { color: white; margin: 0; font-size: 1.8rem; }
            .header h1 span { color: #e85d04; }
            .body { padding: 35px; color: #333; line-height: 1.7; }
            .body h2 { color: #1a1a2e; margin-bottom: 15px; }
            .btn { display: inline-block; background: #00a86b; color: white; padding: 14px 30px; border-radius: 25px; text-decoration: none; font-weight: 600; margin: 20px 0; }
            .footer { background: #f5f5f5; padding: 20px; text-align: center; color: #999; font-size: 0.85rem; border-top: 1px solid #eee; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Kasi<span>Buys</span></h1>
            </div>
            <div class='body'>
                <h2>$title</h2>
                $content
            </div>
            <div class='footer'>
                &copy; " . date('Y') . " KasiBuys. All rights reserved.<br>
                South Africa's local buy &amp; sell marketplace.
            </div>
        </div>
    </body>
    </html>";
}
