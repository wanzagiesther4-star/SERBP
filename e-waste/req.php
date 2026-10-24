<?php 

include 'db_connect.php';
use PHPMailer\PHPMailer\PHPMailer;  
use PHPMailer\PHPMailer\Exception;


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullName = trim($_POST['fullName']??'');
    $phone = (int)trim($_POST['phone']??'');
    $email = trim($_POST['email']??'');
    $location = $_POST['location'];
    $accN = (int)$_POST['accN'];
    $Eweight = $_POST['Eweight'];
    $category = $_POST['category']??[];

    if(str_starts_with($phone,"255")){
        if(strlen($phone)==12){
            $sql = "INSERT INTO users (username, phone_no, email, user_location, account_no) VALUES ($1,$2,$3,$4,$5)
            RETURNING user_id
            ";

            $result1 = pg_query_params($conn, $sql,array($fullName, $phone, $email, $location, $accN));
            $result2 = false;

            if ($result1) {
                $users = pg_fetch_assoc($result1);
                $user_id = $users['user_id'];
                $track_no="SEW-".date("Ymd")."-".rand(1000,9999);

                $sql2 = "INSERT INTO waste (estimated_weight, user_id, waste_type,track_no) VALUES ($1, $2, $3, $4)";
                $result2 = pg_query_params($conn, $sql2, array($Eweight, $user_id, '{' . implode(',', $category) . '}', $track_no));
            }

            if ($result1 && $result2) {

                $message = " request send succesfully, your track number is"." ". "<span id='trackNo'>" . $track_no . "</span>";


            require 'vendor/autoload.php';  

            $mail = new PHPMailer(true);   
            
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';  
                $mail->SMTPAuth = true;  
                $mail->Username = 'www.anthonynjama@gmail.com';  
                $mail->Password = 'ykboljzsnikjjdio';  
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;  
                $mail->Port = 465; 
                $mail->Timeout = 60;
                $mail->SMTPDebug = 3;
                $mail->Debugoutput = 'html';

                $mail->setFrom('www.anthonynjama@gmail.com', 'SERBP');
                $mail->addAddress($email);  
                $mail->isHTML(true);  
                $mail->Subject = 'Tracking Number';  
                $mail->Body = "Hello $fullName,
                Your request was successfully received. Your tracking number is: $track_no.
                You can use this number to track the status of your request.
                ";  


                try {
                    $mail->send();
                    } catch (Exception $e) {
                        die("Mailer Error: " . $mail->ErrorInfo);
                        }
                
                 
             


            } else {
                $error="Error submitting pickup request";
            }
        }else{
            $error = "Number must be 12 digits. Invalid number";
        }
    }else{
    $error = "Number must start with 255. Invalid number";
     }

}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Pickup</title>
    <style>
        .option{
            font-weight:normal;
            text-transform:none;
            letter-spacing:normal;
            margin-top:10px;
        }
        .option input[type="radio"]{
            margin-right:8px;
            cursor:pointer;
            accent-color:#27ae60;

        }
        .option label{
            display:inline;
            margin:0;
            font-weight:normal;
            text-transform:none;
            letter-spacing:normal;
            margin-bottom:12px;

        }
        .category{
            width: 100%;
            border:2px solid #e0e0e0;
            border-radius:8px;
            background-color: #fafafa;
            padding: 12px 15px;
        }
        .category summary{
            color: #555;
            cursor: pointer;
            font-size:15px;
        }
        .category[open] summary{
            margin-bottom:10px;
        }
        .category .option{
            display:block;
            margin: 10px 0 0;

        }
        .category input[type="checkbox"]{
            margin-right:8px;
            cursor: pointer;
            accent-color: #27ae60;
        }
        .success-message {
            color: #14532d;
            background: #dcfce7;
            padding: 10px 12px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-weight: 600;
        }

    </style>
    <link rel="stylesheet" href="req.css">
    <link rel="stylesheet" href="index.css">
</head>
<body>
    <div class="header">
        <div class="left-header">
           <a href="index.php"><img class="icon" src="image/logo.png" alt=""></a>
            <p class="name">Smart E-waste Recovery & Bounty Platform</p>
        </div>
        <div class="middle-header">
        </div>
        <div class="right-header">
            <a href="loginpage.php">Staff login</a>
            <a href="register.php">register</a>
            <div class="pickup">
                <a href="req.php">pick-up?</a>
            </div>

        </div>

    </div>
            <?php if (!empty($message)): ?>
                <div  class="success-message"><?php echo $message; ?></div>
            <?php endif; ?>
    <div class="container">

        <div class="left-section">
            <h1>Current E-Waste Pickup status</h1>
            

            <div class="receipt">
                <h3>View your receipts here </h3>
                <div class="receipts">
                    <h3>No receipts to show</h3>
                    <div class="receipt_content">
                        <p style="font-size:90px;">&#x27F3;</p>
                    </div>

                    <div class="receipt_button">
                        <button class="pdf_button">reload</button>
                        <button class="pdf_button">to pdf</button>
                    </div>

                </div>
            </div>

        </div>
        <div class="right-section">
            <form action="" method="POST">
                <fieldset>
                    <legend>E-WASTE PICKUP REQUEST</legend>

                    <label for="fullName">Full Name</label>
                    <input class="input" id="fullName" name="fullName" type="text" placeholder="Enter your full name" required>



                    <label for="phone">Phone Number</label>
                    <?php if(!empty($error)): ?>
                        <p  style="color:#c0392b;"><?php echo htmlspecialchars($error); ?></p>
                    <?php endif; ?>
                    <input class="input" id="phone" name="phone" type="tel" placeholder="Enter your phone number" inputmode="numeric" required>

                    <label for="email">Email</label>
                    <input class="input" id="email" name="email" type="email" placeholder="Enter your email address" required>

                    <label for="location">Street/Location</label>
                    <input class="input" id="location" name="location" type="text" placeholder="Enter your street address" pattern="[A-Za-z0-9\s]+" required>

                    <label for="accN">Account Number</label>
                    <input class="input" id="accN" name="accN" type="text" placeholder="Enter your account number(number only)" pattern="[0-9]+" required>

                    <label for="Eweight">Estimated Weight (kg)</label>
                    <input class="input" id="Eweight" name="Eweight" type="number" placeholder="Enter estimated weight" required>

                    <label for="category">E-Waste categories</label>
                    <details class="category" id="category">
                        <summary>Select Option</summary>
                            <label class="option"><input type="checkbox" value="computer" name="category[]">computers</label>
                            <label class="option"><input type="checkbox" value="phone" name="category[]">Mobile Phones</label>
                            <label class="option"><input type="checkbox" value="peripheral" name="category[]">Peripherals</label>
                            <label class="option"><input type="checkbox" value="appliances" name="category[]">home appliances</label>
                            <label class="option"><input type="checkbox" value="others" name="category[]">Others</label>

                    </details>

                    <button type="submit">Submit Request</button>
                </fieldset>
            </form>
        </div>
    </div>
  
</body>
</html>
<script src="viewRequest.js"></script>
