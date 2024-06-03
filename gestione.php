<?php
  include_once('database.php');

  $sensoreId = $stanza= $temperatura = $umidita = "";

  if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
	$sensoreID = test_input($_POST["sensoreId"]);
	$stanza = test_input($_POST["stanza"]);
    $temperatura = test_input($_POST["temperatura"]);
    $umidita = test_input($_POST["umidita"]);
	
    $result = insertReading($sensoreID, $stanza, $temperatura, $umidita);
    echo $result;
  }
  else {
    echo "No elementi caricati con HTTP POST.";
  }

  function test_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
  }
?>