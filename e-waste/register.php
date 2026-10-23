<?php
error_reporting(0);
ini_set('display_errors', 0);

session_start();

require_once 'db_connect.php';
//include 'db_connect.php';
$conn = $conn ?? ($connect ?? null);
$errors = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullName = trim($_POST['fullName'] ?? '');
    $role_id = (int) trim($_POST['role_id'] ?? '22'||'23');
    $phone = (int)trim($_POST['phone'] ?? '');
    $staff_email = trim($_POST['email'] ?? '');
    $user_location = trim($_POST['location'] ?? '');
    $user_password = $_POST['password'] ?? '';
    $confirm_password = $_POST['cPassword'] ?? '';

    if ($user_password !== $confirm_password) {
        $error="Password do not match";
    } elseif ($fullName === '' || $phone === 0 || $staff_email === '' || $user_location === '') {
        echo "<script>alert('Please complete all required fields.');</script>";
    } else {
        $sql = "SELECT staff_email FROM users WHERE staff_email = $1";
        $result = pg_query_params($conn, $sql, array($staff_email));

          if (pg_num_rows($result) > 0) {
            $errors = "Email already exists.";
        }else{
            $hashed_password = password_hash($user_password, PASSWORD_DEFAULT);
                $sql2 = "INSERT INTO users (username,staff_email,phone_no,passwords,user_location,role_id) VALUES ($1,$2,$3,$4,$5,$6)";
                
                $result2 = pg_query_params($conn, $sql2, array($fullName,$staff_email,$phone,$hashed_password,$user_location,$role_id));

                if ($result2 === false) {
                    $errors = "Registration failed. Please try again.";
                }else{
                   // echo "<script>alert('Registration successful. Please login.');</script>";
                      
                    header("Location:loginpage.php");
                    echo "<script>window.location.href='loginpage.php';</script>";
                    echo "<script>alert('Registration successfully');</script>";
                    exit();
                
                }
            

                
        }

        }



}

?>

<!DOCTYPE html>
<html>
    <head>
        <title>registerpage</title>
        <link rel="stylesheet" href="req.css">
        <link rel="stylesheet" href="index.css">
        <style>
            a p{
                color:#27ae60;
                font-size:14px;
                margin-top:13px;
            }
            a p:hover{
                font-size:15px;
                transition:font-size 0.2s;
            }
        </style>
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
                <div class="pickup">
                    <a href="req.php">pick-up?</a>
                </div>

            </div>
        </div>
        <div class="register">
            <form action="register.php" method="POST">
                <fieldset>
                    <legend>E-WASTE REGISTRATION</legend>

                    <label for="fullName">Full Name</label>
                    <input class="input" id="fullName" name="fullName" type="text" placeholder="Enter your full name" required>
                    <label>ROLE</label>
                    <label class="option">
                        <input type="radio" name="role_id" value="22" required> RECOVERY COORDINATORY
                    </label>
                    <label class="option">
                        <input type="radio" name="role_id" value="23"> ASSESSMENT TECHNICIAN 
                    </label>

                    <label for="phone">Phone Number</label>
                    <input class="input" id="phone" name="phone" type="tel" placeholder="Enter your phone number" inputmode="numeric" pattern="[0-9]+" required>

                    <label for="email">Email</label>
                    <?php if(!empty($errors)): ?>
                        <p  style="color:#c0392b;"><?php echo htmlspecialchars($errors); ?></p>
                    <?php endif; ?>
                    <input class="input" id="email" name="email" type="email" placeholder="Enter your email address" required>

                    <label for="location">Street/Location</label>
                    <input class="input" id="location" name="location" type="text" placeholder="Enter your street address" pattern="[A-Za-z0-9\s]+" required>
                
                    <label for="password">Enter password</label>
                    <input class="input" id="password" name="password" type="password" placeholder="Enter your password" required>
                    <label for="password">Confirm password</label>
                    <?php if(!empty($error)): ?>
                        <p  style="color:#c0392b;"><?php echo htmlspecialchars($error); ?></p>
                    <?php endif; ?>
                    <input class="input" id="cPassword" name="cPassword" type="password" placeholder="Comfirm your password" required>


                    <button type="submit">Register</button>

                    <a href="loginpage.php"><p>have account?login</p></a>

                </fieldset>
            </form>
        </div>

    </body>
</html>
