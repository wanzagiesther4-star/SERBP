PHP
<?php

require 'vendor/autoload.php';

use Dompdf\Dompdf;

$dompdf = new Dompdf();

$dompdf->loadHtml("
<h1>PDF Test</h1>
<p>Dompdf is working correctly.</p>
");

$dompdf->render();

$dompdf->stream("test.pdf");
?>
