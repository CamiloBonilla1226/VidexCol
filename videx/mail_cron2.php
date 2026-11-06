<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;


require '/home/playhhwa/videx.sms-app.co/phpmailer/src/Exception.php';
require '/home/playhhwa/videx.sms-app.co/phpmailer/src/PHPMailer.php';
require '/home/playhhwa/videx.sms-app.co/phpmailer/src/SMTP.php';
$mail = new PHPMailer(true);

try {
    //Server settings
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
    $mail->isSMTP();                                            //Send using SMTP
    $mail->Host       = 'mail.develtec.com.co';                     //Set the SMTP server to send through
    $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
    $mail->Username   = 'info@develtec.com.co';                     //SMTP username
    $mail->Password   = 'Develtec@2023*.';                               //SMTP password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
    $mail->Port       = 465;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

    //Recipients
    $mail->setFrom('info@develtec.com.co', 'DEVELTEC');
    $mail->addAddress('andrestorres.develtec@gmail.com', 'Andres Torres');     //Add a recipient
    //$mail->addAddress('ellen@example.com');               //Name is optional
    $mail->addReplyTo('andrestorres.developer@gmail.com', 'Andres Torres');
    //$mail->addCC('cc@example.com');
    //$mail->addBCC('bcc@example.com');

    //Attachments
    //$mail->addAttachment('/var/tmp/file.tar.gz');         //Add attachments
    //$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    //Optional name

    //Content
    $mail->isHTML(true);                                  //Set email format to HTML
    $mail->Subject = 'ESTA ES UNA PRUEBA DE VIDEX CON PHPMAILER';
    $mail->Body    = '<H1>HOLA ANDRES</H1> <b>Como va todo!</b>';
    $mail->AltBody = 'Este es un pie de pagina sin html';

    $mail->send();
    echo 'Message has been sent';
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}