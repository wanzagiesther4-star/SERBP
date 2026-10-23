<?php
session_start();
include 'db_connect.php';
$isCoordinator = (int) ($_SESSION['role_id'] ?? 0) === 22
    || strtolower((string) ($_SESSION['role_name'] ?? '')) === 'coordinator';
if (!$isCoordinator) {
    header("Location:loginpage.php");
    exit();
}


$sql="SELECT COUNT(*) AS total_request FROM waste ";
$result1=pg_query($conn,$sql);

$total=pg_fetch_assoc($result1);

$sql2="SELECT COUNT(*) AS waste_status FROM waste
       WHERE waste_status = 'pending'
       ";
$result2=pg_query($conn,$sql2);
$status=pg_fetch_assoc($result2);

$sql3="SELECT COUNT(*) AS assign FROM waste WHERE waste_status='assigned'";
$result3=pg_query($conn,$sql3);
$status3=pg_fetch_assoc($result3);

$sql4="SELECT COUNT(*) AS completed FROM waste WHERE waste_status='completed'";
$result4=pg_query($conn,$sql4);
$status4=pg_fetch_assoc($result4);

$weeklySql = "SELECT COUNT(*) AS recoveries, COALESCE(SUM(r.amount), 0) AS amount
              FROM receipt r
              JOIN waste w ON w.waste_id = r.waste_id
              WHERE w.waste_status = 'completed'
                AND r.issued_date >= CURRENT_TIMESTAMP - INTERVAL '7 days'";
$weeklyResult = pg_query($conn, $weeklySql);
$weekly = pg_fetch_assoc($weeklyResult);

$monthlySql = "SELECT COUNT(*) AS recoveries, COALESCE(SUM(r.amount), 0) AS amount
               FROM receipt r
               JOIN waste w ON w.waste_id = r.waste_id
               WHERE w.waste_status = 'completed'
                 AND r.issued_date >= CURRENT_TIMESTAMP - INTERVAL '1 month'";
$monthlyResult = pg_query($conn, $monthlySql);
$monthly = pg_fetch_assoc($monthlyResult);

$weeklyDetailsResult = pg_query($conn, "SELECT w.track_no, w.actual_weight
    FROM waste w JOIN receipt r ON r.waste_id = w.waste_id
    WHERE w.waste_status = 'completed'
      AND r.issued_date >= CURRENT_TIMESTAMP - INTERVAL '7 days'
    ORDER BY r.issued_date DESC");
$monthlyDetailsResult = pg_query($conn, "SELECT w.track_no, w.actual_weight
    FROM waste w JOIN receipt r ON r.waste_id = w.waste_id
    WHERE w.waste_status = 'completed'
      AND r.issued_date >= CURRENT_TIMESTAMP - INTERVAL '1 month'
    ORDER BY r.issued_date DESC");

?>

<!DOCTYPE html>
<html>
    <head>
        <title>Recovery Coordinator Dashboard</title>
        <link rel="stylesheet" href="index.css">
        <link rel="stylesheet" href="inCharge_dashboard.css">
        <style>

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

        <div class="container">
            <div class="panel">
                <h1>Recovery Coordinator Dashboard</h1>
                <p>Welcome to the staff dashboard. You can now manage pickup requests and monitor activity from here.</p>
            </div>

            <div class="stats">
                <div class="stat-box">
                    <span>Total pickups</span>
                    <strong><?php echo $total['total_request'];?></strong>
                </div>
                <div class="stat-box">
                    <span>Pending pickups</span>
                    <strong><?php echo $status['waste_status']?></strong>
                </div>
                <div class="stat-box">
                    <span>Assigned pickups</span>
                    <strong><?php echo $status3['assign'] ?></strong>                    
                </div>
                <div class="stat-box">
                    <span>Completed recoveries</span>
                    <strong><?php echo $status4['completed'] ?></strong>
                </div>
            </div>
            <div class="panel">
                <div class="report">
                    <div class="report-stats">
                        <h2>Assign request to technician</h2>
                        <p>you can assign available request to technician</p>
                    </div>
                    <div class="view-report">
                        <a href="assigning.php"><button>To assign</button></a>
                    </div>
                </div>


                
            </div>
            <div class="panel">               
                <div class="report">                    
                    <div class="report-stats">
                        <h2>Reports</h2>
                        <p>View completed recovery activity for the last 7 days or month.</p>
                    </div>
                    <div class="view-report">
                        <button onclick="report()">view report</button>
                    </div>
                </div>
                
                
            </div>
            <div class="panel">
                <h2>Recent Activity</h2>
                <p>There are <?php echo $total['total_request'] ?> activities done </p>
            </div>
            <div id="to_report" class="to_reports">
                <h3>Recovery Reports</h3>
                <div class="Rcontent">
                    <div class="stat-box">
                        <span style="font-weight: bold;color:white">Weekly recoveries (last 7 days)</span>
                        <strong><?php echo (int) $weekly['recoveries']; ?></strong>
                        <?php $weeklyWeight = 0; $weeklyRows = []; while ($detail = pg_fetch_assoc($weeklyDetailsResult)) { $weeklyRows[] = $detail; $weeklyWeight += (float) $detail['actual_weight']; } ?>
                        <p>Total actual weight: <?php echo number_format($weeklyWeight, 2); ?> kg</p>
                        <p>Total paid: <?php echo number_format((float) $weekly['amount'], 2); ?></p>
                        <?php foreach ($weeklyRows as $detail): ?>
                            <p>Track no: <?php echo htmlspecialchars($detail['track_no']); ?> |
                               <?php echo number_format((float) $detail['actual_weight'], 2); ?> kg</p>
                        <?php endforeach; ?>
                    </div>
                    <div class="stat-box">
                        <span style="font-weight: bold; color: white;">Monthly recoveries (last month)</span>
                        <strong><?php echo (int) $monthly['recoveries']; ?></strong>
                        <?php $monthlyWeight = 0; $monthlyRows = []; while ($detail = pg_fetch_assoc($monthlyDetailsResult)) { $monthlyRows[] = $detail; $monthlyWeight += (float) $detail['actual_weight']; } ?>
                        <p>Total actual weight: <?php echo number_format($monthlyWeight, 2); ?> kg</p>
                        <p>Total paid: <?php echo number_format((float) $monthly['amount'], 2); ?></p>
                        <?php foreach ($monthlyRows as $detail): ?>
                            <p>Track no: <?php echo htmlspecialchars($detail['track_no']); ?> |
                               <?php echo number_format((float) $detail['actual_weight'], 2); ?> kg</p>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="Rbutton">
                    <button onclick="report()">back</button>
                    <a href="report_download.php?period=weekly"><button type="button">Download weekly PDF</button></a>
                    <a href="report_download.php?period=monthly"><button type="button">Download monthly PDF</button></a>
                </div>
            </div>
        </div>

    </body>
</html>
<script src="viewRequest.js"> </script>
