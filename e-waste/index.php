<?php
session_start();
include 'db_connect.php';



$row = null;
$searchedCode = '';
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $searchedCode = trim($_POST['code'] ?? '');

    if ($searchedCode !== '') {
        $sql = "SELECT w.track_no, w.waste_type, w.waste_status, w.actual_weight, w.waste_condition , r.receipt_id
                FROM waste w
                LEFT JOIN receipt r ON w.waste_id = r.waste_id
                WHERE w.track_no = $1";
        $result = pg_query_params($conn, $sql,[$searchedCode]);
        $row = pg_fetch_assoc($result);
        if ($row && $row['waste_status'] === 'completed') {
            $multipliers = [
                'Critical/Worst' => 1000,
                'Worse' => 1250,
                'Normal' => 1500,
                'Very Good' => 1750,
                'Excellent' => 2000
            ];
            $multiplier = $multipliers[$row['waste_condition']] ?? 0;
            $row['multiplier'] = $multiplier;
            $row['payout'] = (float) $row['actual_weight'] * $multiplier;



        }
    }
}

if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'searchedCode' => $searchedCode,
        'row' => $row
    ]);
    exit;
}
?>


<!DOCTYPE html>
<html>
    <head>
        <title>homepage</title>
        <link rel="stylesheet" href="index.css">
        <style>
            .track{
                box-shadow:2px 2px 12px green;
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                width: min(90vw, 520px);
                max-width: calc(100% - 24px);
                background-color:white;
                border:none;
                border-radius:20px;
                display:none;
                z-index: 20;
                padding: 12px;
                box-sizing: border-box;
            }
            fieldset{
                border:none;
            }
            label {
                display: block;
                margin-top: 18px;
                margin-bottom: 8px;
                color: #2c3e50;
                font-weight: 600;
                font-size: 14px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            button {
                width: 100%;
                padding: 14px;
                margin-top: 30px;
                background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
                color: white;
                border: none;
                border-radius: 8px;
                font-size: 16px;
                font-weight: bold;
                cursor: pointer;
                transition: all 0.3s ease;
                text-transform: uppercase;
                letter-spacing: 1px;
                box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
            }

            button:hover {
                transform: translateY(-3px);
                box-shadow: 0 8px 20px rgba(39, 174, 96, 0.4);
                background: linear-gradient(135deg, #229954 0%, #1e8449 100%);
            }

            button:active {
                transform: translateY(-1px);
                box-shadow: 0 3px 10px rgba(39, 174, 96, 0.3);
            }

            @media (max-width: 600px) {
                fieldset {
                    padding: 25px;
                }

                legend {
                    font-size: 22px;
                }

                label {
                    font-size: 12px;
                }
            }
            .login{
                margin-top: 100px;
            }

            .input {
                width: 90%;
                padding: 12px 15px;
                border-radius: 8px;
                border: 2px solid #e0e0e0;
                font-size: 15px;
                font-family: inherit;
                transition: all 0.3s ease;
                background-color: #fafafa;
            }
            .content{
                position:relative;
            }
            .input:focus {
                outline: none;
                border-color: #27ae60;
                background-color: #fff;
                box-shadow: 0 0 8px rgba(39, 174, 96, 0.2);
                transform: translateY(-2px);
            }

            .input:hover {
                border-color: #27ae60;
            }
            .track p{
                color:#27ae60;
                text-align:center;
                font-size:20px;
                text-shadow:1px 1px 2px #27ae60;
            }
            .status{
                display: none;
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                width: min(90vw, 500px);
                max-width: calc(100% - 24px);
                min-height: 200px;
                background-color:#b7b4b4;
                border-radius: 20px;
                padding: 20px 16px;
                box-sizing: border-box;
                z-index: 30;
            }
            .status h3{
                color: #1e8449;
                text-align: center;
                margin-top: 20px;
                margin-bottom: 20px;
                font-size: clamp(1.1rem, 3vw, 2rem);
            }
            .status p {
                font-size: clamp(0.9rem, 2.3vw, 1.2rem);
                word-break: break-word;
            }
            .status button {
                margin-top: 16px;
                width: 100%;
            }
            .account{
                position:absolute;
                margin-top:68px;
                background-color: #1a954d ;
                display:none;
                flex-direction:column;
                padding:2px;
                z-index: 10;
            }

            .account.show {
                display: flex;
            }
 
        </style>
    </head>
    <body>
        <div class="header">
            <div class="left-header">
                    <img class="icon" src="image/logo.png" alt="">
                
                <p class="name">Smart E-waste Recovery & Bounty Platform</p>
            </div>
            <div class="middle-header">
            </div>
            <div class="right-header">
                <img class="icon2" src="image/profile.png" alt="" onclick="accounts()">
                
                <div class="account" id="account">
                <a href="loginpage.php">Staff login</a>               
                <a href="register.php">register</a>
                </div>
                

                <div class="pickup">
                    <a href="req.php">pick-up?</a>
                </div>
                <a style="cursor:pointer;" onclick="track()"> track</a>

            </div>

        </div>
        <div class="body">

            <div class="content">
                <div class="slideshow">
                    <img src="image/p1.jpg" alt="">
                    <img src="image/p3.webp" alt="">
                    <img src="image/p5.jpg" alt="">
                    <img src="image/p6.webp" alt="">
                    <img src="image/p7.jpg" alt="">
                    <img src="image/p8.jpg" alt="">
                    <img src="image/p9.jpg" alt="">
                    <img src="image/p10.jpg" alt="">
                    <img src="image/p11.jpg" alt="">

                </div>
                <div class="explain">
                    <div class="explain-heading">
                        <p>Smart E-waste Recovery & Bounty Platform</p><br>
                    </div>
                    <div class="explain-content">
                        <span>Welcome to our Smart E-waste Recovery & Bounty Platform!</span>
                        <span>We're responsible for disposal and recycling of e-waste while offering incentives to individuals and businesses</span>
                        <span>We aims to make it easier for everyone to contribute to a cleaner and more sustainable environment.</span>
                        <span>Is e-waste disposal an issue? We provide tools and resources you need to make a positive impact on the environment.</span>
                        <span>Submit your request for pick-up at any time!</span>


                    </div>
                </div>
            </div>
            <div class="faq">
                <p class="faq-heading">SUPPORT & FAQ</p>
                <div class="faq-content">
                    <div class="support-item">
                        <details>
                            <summary>How do I contact customer support?</summary>
                            <p>You can contact our customer support team at support@ewaste.com or call us at +255-7544-6418-9.</p>
                        </details>              

                        
                    </div>
                    <div class="faq-item">
                        <details>
                            <summary>What is your return policy?</summary>
                            <p>We offer a 30-day return policy for all items.</p>
                        </details>
                        

                    </div>
                </div>
            </div>

            <div>
                <footer class="footer">
                    <div class="about">
                        <p><a href="dasdh@gkaug">about us</a></p>
                    </div>
                    <div class="copy">
                        <p>&copy; 2026 Smart E-waste Recovery & Bountry Platform.<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;All Rights Reserved</p>
                    </div>
                    <div class="instagram" >
                        <p><a href="https://www.instagram.com/" target="_blank">instagram</a></p>
                    </div>
                    <div class="contact">
                        <p ><a href="http://wa.me/255754464189">WhatAapp</a><br><a href="antonynjama2004@gmail.com">support@ewaste.com</a></p>
                    </div>

                </footer>
            </div>·
            </div>
            <div id="tracks" class="track">
                <form action="" id="statusForm" method="POST">
                    <p>Check the status of your e-waste pickup request at any time.</p>
                    <fieldset>
                        <label for="code">TRACK NUMBER</label>
                        <input class="input" id="code" name="code" type="text" value="<?php echo htmlspecialchars($searchedCode, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Enter your code here" required>
                    </fieldset>
                    <button type="submit">Track</button>

                    <div id="status" class="status" style="display: <?php echo ($_SERVER["REQUEST_METHOD"] === "POST") ? 'block' : 'none'; ?>;">
                        <?php if ($row): ?>
                            <h3>Your request status</h3>
                            <p>TRACK NUMBER : <?php echo $row['track_no']; ?> </p>
                            <p>WASTE TYPE : <?php echo $row['waste_type']; ?> </p>
                            <p>WASTE STATUS : <?php echo $row['waste_status']; ?> </p>
                            <?php if ($row['waste_status'] === 'completed'): ?>
                                <p>ACTUAL WEIGHT : <?php echo htmlspecialchars($row['actual_weight']); ?> kg</p>
                                <p>CONDITION : <?php echo htmlspecialchars($row['waste_condition']); ?></p>
                                <p>CONDITION MULTIPLIER : <?php echo number_format($row['multiplier'], 2); ?></p>
                                <p>AMOUNT TO BE PAID : <?php echo number_format($row['payout'], 2); ?></p>
                                <a href="trials_receipt.php? id=<?= $row['receipt_id'] ?>"><button type="button" >Download receipt</button></a>
                            <?php endif; ?>
                        <?php elseif ($_SERVER["REQUEST_METHOD"] === "POST"): ?>
                            <h3>Tracking result</h3>
                            <p>No request found for this track number.</p>
                        <?php endif; ?>
                        <button type="button" onclick="document.getElementById('status').style.display='none';">back</button>
                    </div>
                </form>
            </div>


    </body>
</html>
<script src="viewRequest.js">
    
</script>
