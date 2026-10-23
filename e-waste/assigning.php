<?php
session_start();
include 'db_connect.php';
if(!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 22) {
    header("Location:loginpage.php");
    exit();
}

$message = '';

$sql = "SELECT users.username, users.phone_no, users.account_no, users.user_location, users.email,
              waste.submision_date, waste.track_no, waste.waste_type, waste.estimated_weight,
              waste.waste_status, waste.tech_id
        FROM users
        JOIN waste ON users.user_id = waste.user_id
        ORDER BY users.user_id";
$result = pg_query($conn, $sql);

$sql2 = "SELECT user_id, username
         FROM users
         WHERE role_id = 23
         ORDER BY username";
$result2 = pg_query($conn, $sql2);
$technicians = array();
while ($tech = pg_fetch_assoc($result2)) {
    $technicians[] = $tech;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $tech_id = trim($_POST['tech_id'] ?? '');
    $track_no = trim($_POST['track_no'] ?? '');
    $decision = $_POST['decision'] ?? 'assign';

    if ($track_no !== '' && ($decision === 'approve' || $tech_id !== '')) {
    $status=pg_query_params($conn, "SELECT waste_status FROM waste WHERE track_no = $1", array($track_no));
    $status_row = pg_fetch_assoc($status);

    if ($decision === 'approve' && $status_row && $status_row['waste_status'] === 'assessed') {
        $multipliers = [
            'Critical/Worst' => 1000,
            'Worse' => 1250, 
            'Normal' => 1500,
            'Very Good' => 1750, 
            'Excellent' => 2000
        ];
        $dataResult = pg_query_params($conn, 'SELECT waste_id, actual_weight, waste_condition FROM waste WHERE track_no = $1', array($track_no));
        $data = pg_fetch_assoc($dataResult);
        $amount = (float) $data['actual_weight'] * ($multipliers[$data['waste_condition']] ?? 0);
        $result3 = pg_query_params($conn, "UPDATE waste SET waste_status = 'completed' WHERE track_no = $1", array($track_no));
        if ($result3) {
            $receiptResult = pg_query_params($conn, 'SELECT receipt_id FROM receipt WHERE waste_id = $1', array($data['waste_id']));
            if ($receiptResult && pg_num_rows($receiptResult) > 0) {
                pg_query_params($conn, 'UPDATE receipt SET amount = $1 WHERE waste_id = $2', array($amount, $data['waste_id']));
            } else {
                pg_query_params($conn, 'INSERT INTO receipt (waste_id, issued_date, amount) VALUES ($1, NOW(), $2)', array($data['waste_id'], $amount));
            }
            $message = 'Request ' . $track_no . ' approved and completed.';
            $result = pg_query($conn, $sql);
        }
    } elseif ($decision === 'assign' && $status_row && in_array($status_row['waste_status'], ['pending', 'assessed'], true)) {
        $sql3 = "UPDATE waste SET tech_id = $1, waste_status = 'assigned' WHERE track_no = $2";
        $result3 = pg_query_params($conn, $sql3, array((int) $tech_id, $track_no));
        if ($result3) {
            $message = 'Request ' . $track_no . ' assigned successfully.';
            $result = pg_query($conn, $sql);
        }
    } elseif ($status_row && $status_row['waste_status'] === 'assigned') {
        $message = 'This request has already been assigned.';
    } elseif ($status_row && $status_row['waste_status'] === 'completed') {
        $message = 'This request has already been completed.';
    }
    }
}

?>

<!DOCTYPE html>
<html>
    <head>
        <title>request_assigning</title>
        <link rel="stylesheet" href="index.css">
        <style>
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
                max-width: 100%;
            }
            h1, h3 {
                margin-top: 0;
                color: #1f3d2d;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                box-shadow: 2px 2px 12px #27ae60;
                background-color: #27ae60;
                color: white;
                border-radius: 10px;
                overflow: hidden;
                justify-content:center;
            }
            th, td {
                padding: 10px;
                border: 1px solid rgba(255,255,255,0.3);
                text-align: left;
                vertical-align: top;
            }
            tr{
                font-size: clamp(0.5rem, 1vw, 1.5rem);
                font-family: 'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif;
            }
            h3 {
                text-align: center;
                margin-top: 0;
                color: #1f3d2d;
            }
            .assign-form {
                display: none;
                margin-top: 10px;
            }
            .assign-form select {
                width: 100%;
                padding: 8px 10px;
                border-radius: 8px;
                border: none;
            }
            .success-message {
                color: #14532d;
                background: #dcfce7;
                padding: 10px 12px;
                border-radius: 8px;
                margin-bottom: 16px;
                font-weight: 600;
            }
           button{
                font-size: clamp(0.2rem, 0.8vw, 1.2rem);
                font-family: 'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif;
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
                <a href="inCharge_dashboard.php">back to coordinator dashboard</a>
            </div>
        </div>

        <div class="container">
            <div class="panel">
                <h1>Recovery Coordinator Request Assignment</h1>
                <p>Welcome to the staff dashboard. You can now manage pickup requests and monitor activity from here.</p>
            </div>

            <?php if ($message !== ''): ?>
                <div class="success-message"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="panel">
                <h3>CITIZEN'S REQUESTS DETAILS</h3>
                <table border="1">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Account Number</th>
                        <th>Location</th>
                        <th>Waste type</th>
                        <th>Estimated weight</th>
                        <th>Submission date</th>
                        <th>Track no.</th>
                        <th>Status</th>
                    </tr>
                    <?php while ($row = pg_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo $row['username'] ?></td>
                            <td><?php echo $row['email']; ?></td>
                            <td><?php echo $row['phone_no']; ?></td>
                            <td><?php echo $row['account_no']; ?></td>
                            <td><?php echo $row['user_location']; ?></td>
                            <td><?php echo $row['waste_type']; ?></td>
                            <td><?php echo $row['estimated_weight']; ?></td>
                            <td><?php echo $row['submision_date']; ?></td>
                            <td><?php echo $row['track_no']; ?></td>
                            <td>
                                <button type="button" onclick="assigning(this)"><?php echo $row['waste_status']; ?></button>
                                <form class="assign-form" method="POST" action="">
                                    <input type="hidden" name="track_no" value="<?php echo $row['track_no']; ?>">
                                    <select name="tech_id">
                                        <option value="">Select technician</option>
                                        <?php foreach ($technicians as $tech): ?>
                                            <option value="<?php echo $tech['user_id']; ?>"><?php echo $tech['username']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" name="decision" value="assign">Assign / assign</button>
                                    <?php if ($row['waste_status'] === 'assessed'): ?>
                                        <button type="submit" name="decision" value="approve">Approve and Complete</button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            </div>
        </div>
    </body>
</html>
<script src="viewRequest.js"></script>
