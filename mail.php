<?php


require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mail {
    public static function sendConfirmation($to,$subject, $message) {
        $mail = new PHPMailer(true);
        try {
            // SMTP Settings
            $mail->isSMTP();
            $mail->Host       = getenv('MAIL_HOST') ?: 'smtp.example.com'; // SMTP server
            $mail->SMTPAuth   = true;
            $mail->Username   = getenv('MAIL_USERNAME') ?: 'your_email@example.com'; // SMTP username
            $mail->Password   = getenv('MAIL_PASSWORD') ?: ''; // SMTP password - Use environment variable
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Recipients
            $mail->setFrom(getenv('MAIL_FROM') ?: 'noreply@example.com', getenv('APP_NAME') ?: 'Example Application');
            $mail->addAddress($to);

            // Email Content
            $mail->isHTML(true);
            $mail->Subject = $subject;//"Booking Confirmation #$confirmationNumber";
            $mail->Body    = $message; //"Thank you for your booking. Your confirmation number is: <b>$confirmationNumber</b>";

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Mail Error: {$mail->ErrorInfo}");
            //$this->logMessage("Mail Error: {$mail->ErrorInfo}");
            file_put_contents("log.txt","Sending Email failed to:". $to." subject:".$subject." message:". $message, FILE_APPEND );
            return false;
        }
    }
    
    
    // Simple logging function
    function logMessage($message) {
        $logfile = 'log.txt'; // Adjust the path to where you want to store the log file
        $currentDate = date("Y-m-d H:i:s");
        file_put_contents($logfile, "[$currentDate] $message\n", FILE_APPEND);
    }
    
    
}
