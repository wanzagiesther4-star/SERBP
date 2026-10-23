<?php 
session_start();
include 'db_connect.php';
$isTechnician = (int) ($_SESSION['role_id'] ?? 0) === 23
    || strtolower((string) ($_SESSION['role_name'] ?? '')) === 'technician';
if (!$isTechnician) {
    header("Location:loginpage.php");
    exit();
}

$tech_name = $_SESSION['username'] ?? 'Technician';
$tech_id = $_SESSION['user_id'] ?? null;
$assignedRequests = [];

if($_SERVER["REQUEST_METHOD"]=="POST"){
    $actual_weight = trim($_POST['Aweight'] ?? '');
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $condition = trim($_POST['condition'] ?? '');

    if ($actual_weight === '' || $condition === '' || $phone === '') {
        $error = 'Please complete all fields.';
    } else {
        $sql = "SELECT users.phone_no, users.user_id
              FROM users
              JOIN waste ON users.user_id = waste.user_id
              WHERE users.phone_no = $1";
        $result = pg_query_params($conn, $sql, array($phone));

        if ($result && pg_num_rows($result) > 0) {
            $user = pg_fetch_assoc($result);
            $id = $user['user_id'];
            $sql2 = "UPDATE waste
                   SET actual_weight = $1,
                       waste_condition = $2,
                       waste_status = 'assessed'
                   WHERE user_id = $3";
            $result2 = pg_query_params($conn, $sql2, array($actual_weight, $condition, $id));

            if ($result2) {
                $message = "submission successful";
            }
        } else {
            $error = 'Invalid input';
        }
    }
} else {
    $error = 'invalid everything';
}

$totalSql="SELECT COUNT(*) AS total FROM waste WHERE tech_id=$tech_id";
$totalResult=pg_query($conn,$totalSql);
$totaRow=pg_fetch_assoc($totalResult);

if ($tech_id !== null) {
    $assignedSql = "SELECT w.track_no, w.waste_type, w.estimated_weight, w.waste_status,
                          u.username, u.user_location, u.phone_no
                    FROM waste w
                    JOIN users u ON u.user_id = w.user_id
                    WHERE w.tech_id = $1
                    ORDER BY w.track_no DESC";
                    
    $assignedResult = pg_query_params($conn, $assignedSql, array((int) $tech_id));
    if ($assignedResult) {
        while ($row = pg_fetch_assoc($assignedResult)) {
            $assignedRequests[] = $row;
        }
    }
}

$compSql="SELECT COUNT(*) AS complete FROM waste WHERE tech_id=$tech_id AND waste_status='completed'";
$compResult=pg_query($conn,$compSql);
$compRow=pg_fetch_assoc($compResult);

$assSql="SELECT COUNT(*) AS assessed FROM waste WHERE tech_id=$tech_id AND waste_status='assessed'";
$assResult=pg_query($conn,$assSql);
$assRow=pg_fetch_assoc($assResult);



?>
<!DOCTYPE html>

<html>
    <meta name="viewpoint" content="width=device-width, initial-scale=1.0">
    <head>
        <title>Technician Dashboard</title>
        <link rel="stylesheet" href="index.css">
        <link rel="stylesheet" href="form.css">
        <style>
            h1 {
                text-align: center;
                color: #27ae60;
                margin-top: 20px;
            }
            .welcome {
                text-align: center;
                color: #555;
                font-size: 16px;
            }
            .user-greeting {
                background: #eafaf1;
                border-bottom: 2px solid #27ae60;
                padding: 10px 20px;
                text-align: right;
                color: #1e8449;
                font-weight: 600;
                font-size: clamp(1rem, 1vw, 2rem)
            }
            .container {
                display: flex;
                flex-direction: column;
                max-width: 1100px;
                margin: 32px auto;
                padding: 0 20px;
            }
            .panel {
                background: white;
                border-radius: 14px;
                padding: 24px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.08);
                margin-bottom: 22px;
                margin-top: 30px;
            }
            h2 {
                margin-top: 0;
                color: #27ae60;
            }
            .nav {
                display: flex;
                margin-right: 50px;
                gap: 20px;
                flex-wrap: wrap;
            }
            .nav a {
                text-decoration: none;
                color: white;
                background:none;
                padding: 10px 16px;
                border-radius: 8px;
            }
            .requests {
                margin-top: 20px;
                display: flex;
                flex-direction:row;
            }
            .requests-stats {
                flex: 1;
                display: flex;
                flex-direction: column;
            }
            .view-requests {
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .view-requests button {
                background-color: #27ae60;
                color: white;
                border: none;
                padding: 20px 20px;
                border-radius: 5px;
                cursor: pointer;
                font-size: 16px;
            }
            .view-requests button:hover {
                background-color: #1e8449;
            }
            .stats {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
                gap: 18px;
            }
            .stat-box {
                background: #eafaf1;
                border: 1px solid #c9ebd6;
                border-radius: 12px;
                padding: 18px;
            }
            .stat-box strong {
                display: block;
                font-size: 28px;
                color: #27ae60;
                margin-top: 8px;
            }
            .assigned-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 12px;
                background: #fff;
            }
            .assigned-table th,
            .assigned-table td {
                border: 1px solid #dfe7e1;
                padding: 10px 12px;
                text-align: left;
                vertical-align: top;
            }
            .assigned-table th {
                background: #eafaf1;
                color: #14532d;
            }
            .Form{
                display:none;
                position: fixed;
                top: 50%;
                right: 20px;
                transform: translateY(-50%);
                width: min(90vw, 340px);
                z-index: 30;
                box-sizing: border-box;
            }
            .Form.show {
                display: block;
            }
            .Form form {
                width: 100%;
                box-sizing: border-box;
                margin: 0;
            }
            .Form fieldset {
                width: 100%;
                padding: 14px 12px;
                box-sizing: border-box;
                margin: 0;
            }
            .Form input,
            .Form textarea,
            .Form select,
            .Form button,
            .Form label,
            .Form legend,
            .Form .option {
                max-width: 100%;
                box-sizing: border-box;
            }
            .Form .input,
            .Form .form-button,
            .Form button {
                width: 100%;
            }
            .Form .option {
                display: block;
                margin-bottom: 6px;
            }
            .Form .input,
            .Form .form-button,
            .Form label,
            .Form legend {
                font-size: 12px;
            }
            .Form .form-button {
                padding: 10px 12px;
                margin-top: 12px;
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
    </head>
    <body>
        <div class="header">
            <div class="left-header">
                <a href="index.php"><img class="icon" src="image/logo.png" alt="Logo"></a>
                <p class="name">Smart E-waste Recovery & Bounty Platform</p>
            </div>
            <div class="middle-header">
            </div>
            <div class="right-header">
                <div class="nav">
                    <a href="req.php">New Pickup</a>
                    <a href="index.php">Home</a>
                </div>
            </div>
        </div>
        <div class="user-greeting">Hello, <?php echo htmlspecialchars($tech_name); ?></div>
        <h1>Technician Dashboard</h1>
        <p class="welcome">Welcome to the technician dashboard. You can now manage your tasks and monitor your activity from here.</p>



        <div class="container">
            <div class="stats">
                <div class="stat-box">
                    <span>Total Assigned</span>
                    <strong><?php echo $totaRow['total']?></strong>
                </div>
                <div class="stat-box">
                    <span>Assessed pickups</span>
                    <strong><?php echo $assRow['assessed']; ?></strong>
                </div>
                <div class="stat-box">
                    <span>complete pickups</span>
                    <strong><?php echo $compRow['complete']; ?></strong>                    
                </div>


            </div>
            <div class="panel">
                <?php if (!empty($message)): ?>
                    <div class="success-message"><?php echo $message; ?></div>
                <?php endif; ?>
                <div class="requests">
                    <div class="requests-stats">
                        <h2>Assigned Requests</h2>
                        <?php if (!empty($assignedRequests)): ?>
                            <table class="assigned-table">
                                <tr>
                                    <th>Track No.</th>
                                    <th>Citizen</th>
                                    <th>Location</th>
                                    <th>Phone</th>
                                    <th>Waste Type</th>
                                    <th>Weight</th>
                                    <th>Status</th>
                                </tr>
                                <?php foreach ($assignedRequests as $request): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($request['track_no']); ?></td>
                                        <td><?php echo htmlspecialchars($request['username']); ?></td>
                                        <td><?php echo htmlspecialchars($request['user_location']); ?></td>
                                        <td><?php echo htmlspecialchars($request['phone_no']); ?></td>
                                        <td><?php echo htmlspecialchars($request['waste_type']); ?></td>
                                        <td><?php echo htmlspecialchars($request['estimated_weight']); ?></td>
                                        <td><?php echo htmlspecialchars($request['waste_status']); ?></td>
                                        <td><button onclick="Form()">fill the form</button></td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        <?php else: ?>
                            <p>No requests assigned at the moment.</p>
                        <?php endif; ?>
                    </div>
                    <div id="is_form" class="Form">
                            <form action="" method="POST">
                                <fieldset>
                                    <legend>E-WASTE DETAILS FORM</legend>
                            
                                    <?php if(!empty($error)): ?>
                                        <p  style="color:#c0392b;"><?php echo htmlspecialchars($error); ?></p>
                                    <?php endif; ?>

                                    <label for="Aweight">Actual Weight (kg)</label>
                                    <input class="input" id="Aweight" name="Aweight" type="number" placeholder="Enter Actual weight" required>

                                    <label for="phone">Phone Number</label>
                                    <input class="input" id="phone" name="phone" type="tel" placeholder="Enter your phone number" inputmode="numeric" pattern="[0-9]+" required>

                                    <label>E-Waste Condition</label>
                                    <label class="option">
                                        <input type="radio" name="condition" value="Critical/Worst" required> Critical/Worst
                                    </label>
                                    <label class="option">
                                        <input type="radio" name="condition" value="Worse"> Worse
                                    </label>
                                    <label class="option">
                                        <input type="radio" name="condition" value="Normal"> Normal
                                    </label>
                                    <label class="option">
                                        <input type="radio" name="condition" value="Very Good"> Very Good
                                    </label>
                                    <label class="option">
                                        <input type="radio" name="condition" value="Excellent"> Excellent
                                    </label>
                                    <button class="form-button" type="submit">Submit Request</button>
                                    <button class="form-button" type="button" onclick="Form()">go back</button>


                                </fieldset>
                            </form>
                    </div>
                </div>
            </div>
            <div class="panel">
                <h2>Completed Requests</h2>
                <p>No completed requests to display.</p>
            </div>

            <div class="panel">
                <h2>Recent Activity</h2>
                <p>No recent activity to display.</p>
            </div>  

        </div>


    </body>
</html>
<script src="viewRequest.js"></script>
                    
