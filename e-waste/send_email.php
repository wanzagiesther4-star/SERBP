<?php 
require 'vendor/autoload.php';  
use PHPMailer\PHPMailer\PHPMailer;  
use PHPMailer\PHPMailer\Exception;
$email = new PHPMailer(true);   
  
    $email->isSMTP();  
    $email->Host = 'smtp.gmail.com';  
    $email->SMTPAuth = true;  
    $email->Username = 'www.anthonynjama@gmail.com';  
    $email->Password = 'ykboljzsnikjjdio';  
    $email->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;  
    $email->Port = 587;  

    $email->setFrom('www.anthonynjama@gmail.com', 'SERBP');  
    $email->addAddress($email);  
    $email->isHTML(true);  
    $email->Subject = 'Tracking Number';  
    $fullName = $fullName ?? 'Customer';
    $track_no = $track_no ?? 'N/A';
    $email->Body = "Hello $fullName,
    Your request was successfully received. Your tracking number is: $track_no.
    You can use this number to track the status of your request.
    ";  

    $email->send();


?>
