<?php
require 'tinygojira/TinyGojira.php';
$gojira = new TinyGojira();

/*$gojira->putkeep('Pogo1', 'cat1') ? 'success' : 'error';
$gojira->putkeep('Pogo2', 'cat2') ? 'success' : 'error';
$gojira->putkeep('Pogo3', 'cat3') ? 'success' : 'error';
$gojira->putkeep('Pogo4', 'cat4') ? 'success' : 'error';
$gojira->putkeep('Pogo5', 'cat5') ? 'success' : 'error';
$gojira->putkeep('Pogo6', 'cat6') ? 'success' : 'error';
$gojira->putkeep('Pogo7', 'cat7') ? 'success' : 'error';
*/

//$gojira->mget(array('Pogo3', 'Pogo1', 'Pogo6'));
$gojira->fwmkeys('Pogo', 7);
//echo $gojira->nr_put('Pogo6', 'CatzManDu!!') ? 'success' : 'error';
//echo "<br>";
/*
$data = $gojira->get('Pogo5');
echo $data === false ? 'error' : $data;
echo "<br>";

echo $gojira->out('Pogo5') ? 'deleted' : 'error';
echo "<br>";
*/
//$data = $gojira->get('Pogo7');
echo $data === false ? 'error' : $data;
echo "<br>";

?>
