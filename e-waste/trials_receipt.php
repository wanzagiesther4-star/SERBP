<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db_connect.php';
require 'vendor/autoload.php';


use Dompdf\Dompdf;

// Normalize the connection variable provided by db_connect.php.
$conn = $conn ?? $db_conn ?? null;
if (!$conn) {
    die('Database connection unavailable.');
}

$receipt_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($receipt_id <= 0) {
    die('Invalid receipt ID.');
}

$sql = "SELECT r.issued_date, r.amount, r.waste_id,w.track_no, w.waste_type, w.actual_weight, w.waste_condition, w.waste_status
    FROM receipt r
    JOIN waste w ON w.waste_id = r.waste_id
    WHERE r.receipt_id = $1
";

$result = pg_query_params($conn, $sql, [$receipt_id]);
$row = pg_fetch_assoc($result);

if (!$row) {
    die('Receipt not found.');
}

// Calculate the payout based on the waste condition
$multipliers = [
    'Critical/Worst' => 1000,
    'Worse' => 1250,
    'Normal' => 1500,
    'Very Good' => 1750,
    'Excellent' => 2000,
];

$multiplier = $multipliers[$row['waste_condition']] ?? 0;
$payout = (float) $row['actual_weight'] * $multiplier;


if ($row['waste_status'] === 'completed') {
    $update = pg_query_params(
        $conn,
        'UPDATE receipt SET amount = $1 WHERE receipt_id = $2',
        [$payout, $receipt_id]
    );
    $row['amount'] = $payout;
}



$dompdf = new Dompdf();
$logoPath = __DIR__ . '/image/logo.png';
$logo = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));

$html = '<style>
    @page { margin: 40px; }
    .watermark { position: fixed; top: 0; left: 0; width: 100%; height: 100%; opacity: 0.08; z-index: -1; }
    .content { position: relative; z-index: 1; }
</style>
<img class="watermark" src="' . $logo . '" alt="">
<div class="content">
<h1 style="text-align: center;color: #27ae60;">Receipt </h1>
<p>Track number: ' . htmlspecialchars($row['track_no']) . '</p>
<p>Waste type: ' . htmlspecialchars($row['waste_type']) . '</p>
<p>Actual weight: ' . htmlspecialchars($row['actual_weight']) . ' kg</p>
<p>Condition: ' . htmlspecialchars($row['waste_condition']) . '</p>
<p>Condition multiplier: ' . number_format($multiplier, 2) . '</p>
<p>Amount to be paid: ' . number_format($payout, 2) . '</p>
<p>Issued date: ' . htmlspecialchars($row['issued_date']) . '</p>
<p>Thank you for recycling responsibly.</p>
</div>';

$dompdf->loadHtml($html);
$dompdf->setPaper('A6', 'portrait');
$dompdf->render();
$dompdf->stream('receipt-' . $row['track_no'] . '.pdf', ['Attachment' => true]);
exit();
