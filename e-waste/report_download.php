<?php
session_start();
include 'db_connect.php';
require 'vendor/autoload.php';

use Dompdf\Dompdf;

$isCoordinator = (int) ($_SESSION['role_id'] ?? 0) === 22
    || strtolower((string) ($_SESSION['role_name'] ?? '')) === 'coordinator';
if (!$isCoordinator) {
    http_response_code(403);
    exit('Access denied.');
}

$period = $_GET['period'] ?? '';
$interval = $period === 'weekly' ? '7 days' : ($period === 'monthly' ? '1 month' : '');
if ($interval === '') {
    http_response_code(400);
    exit('Invalid report period.');
}

$sql = "SELECT w.track_no, w.actual_weight, r.amount, r.issued_date
        FROM waste w
        JOIN receipt r ON r.waste_id = w.waste_id
        WHERE w.waste_status = 'completed'
          AND r.issued_date >= CURRENT_TIMESTAMP - $1::interval
        ORDER BY r.issued_date DESC";
$result = pg_query_params($conn, $sql, [$interval]);
if ($result === false) {
    http_response_code(500);
    exit('Unable to load report data.');
}

$rows = [];
$totalWeight = 0;
$totalAmount = 0;
while ($row = pg_fetch_assoc($result)) {
    $rows[] = $row;
    $totalWeight += (float) $row['actual_weight'];
    $totalAmount += (float) $row['amount'];
}

$title = $period === 'weekly' ? 'Weekly Recovery Report' : 'Monthly Recovery Report';
$html = '<style>
    @page { margin: 35px; }
    body { font-family: DejaVu Sans, sans-serif; color: #1f3d2d; }
    h1 { text-align: center; color: #27ae60; }
    .summary { margin: 20px 0; padding: 12px; background: #eafaf1; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { border: 1px solid #b7d8c2; padding: 8px; text-align: left; }
    th { background: #27ae60; color: white; }
</style>
<h1>' . htmlspecialchars($title) . '</h1>
<div class="summary">
    <p><strong>Total recoveries:</strong> ' . count($rows) . '</p>
    <p><strong>Total actual weight:</strong> ' . number_format($totalWeight, 2) . ' kg</p>
    <p><strong>Total amount paid:</strong> ' . number_format($totalAmount, 2) . '</p>
</div>
<table>
    <tr><th>Track number</th><th>Actual weight (kg)</th><th>Amount paid</th><th>Issued date</th></tr>';

foreach ($rows as $row) {
    $html .= '<tr>
        <td>' . htmlspecialchars($row['track_no']) . '</td>
        <td>' . number_format((float) $row['actual_weight'], 2) . '</td>
        <td>' . number_format((float) $row['amount'], 2) . '</td>
        <td>' . htmlspecialchars($row['issued_date']) . '</td>
    </tr>';
}

$html .= '</table>';

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream($period . '-recovery-report.pdf', ['Attachment' => true]);
