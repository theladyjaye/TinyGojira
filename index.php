<?php
require 'tinygojira/TinyGojira.php';
$gojira = new TinyGojira();

$gojira->putcat('Pogo7', 'vishnue') ? 'success' : 'error';
//echo $gojira->nr_put('Pogo6', 'CatzManDu!!') ? 'success' : 'error';
//echo "<br>";
/*
$data = $gojira->get('Pogo5');
echo $data === false ? 'error' : $data;
echo "<br>";

echo $gojira->out('Pogo5') ? 'deleted' : 'error';
echo "<br>";
*/
$data = $gojira->get('Pogo7');
echo $data === false ? 'error' : $data;
echo "<br>";

?>
