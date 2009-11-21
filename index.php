<?php
require 'tinygojira/TinyGojira.php';
$gojira = new TinyGojira();

echo $gojira->put('Pogo5', 'CatzManDu') ? 'success' : 'error';
echo "<br>";

$data = $gojira->get('Pogo5');
echo $data === false ? 'error' : $data;
echo "<br>";

echo $gojira->out('Pogo5') ? 'deleted' : 'error';
echo "<br>";

$data = $gojira->get('Pogo5');
echo $data === false ? 'error' : $data;
echo "<br>";
?>
