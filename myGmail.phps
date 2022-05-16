<?php

date_default_timezone_set('Etc/UTC');

require 'PHPMailerAutoload.php';

$mail = new PHPMailer;
$mail->CharSet = "utf8";

$mail->isSMTP();
$mail->SMTPDebug = 2;
$mail->Debugoutput = 'html';

$mail->Host = 'smtp.gmail.com';
$mail->Port = 587;
$mail->SMTPSecure = 'tls';
$mail->SMTPAuth = true;


$mail->Username = "dm.teryoshin@gmail.com";
$mail->Password = "simplepass";
$mail->setFrom("dm.teryoshin@gmail.com");
$mail->addAddress('dm.teryoshin@gmail.com');

#$mail->Subject = 'RZD';
#$mail->Body = 'Hello man! Good morning!';
$mail->Subject = 'РЖД';
$mail->Body = 'Привет! Доброе утро!';


if (!$mail->send()) {
    echo "Mailer Error: " . $mail->ErrorInfo;
} else {
    echo "Message sent!";
}
