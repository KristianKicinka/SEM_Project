<?php 

$output = exec("python3 ./hash_generator.py ./JA3_040544_alza.pcap JA3");

// Decode the JSON output into a PHP array
print($output);
$dataArray = json_decode($output, true);

// Now $dataArray contains the array of objects from Python stdout
// You can process it further as needed
var_dump($dataArray);