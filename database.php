<?php
  $servername = "localhost";

  
  $dbname = "progetto";
 
  $username = "root";
  
  $password = "";

  function insertReading($sensoreId, $stanza, $temperatura, $umidita) {
    global $servername, $username, $password, $dbname;

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);
  
    if ($conn->connect_error) {
      die("Connection fallita: " . $conn->connect_error);
    }

    $sql = "INSERT INTO sensor (sensoreId, stanza, temperatura, umidita)
    VALUES ('" . $sensoreId . "', '" . $stanza . "','" . $temperatura . "', '" . $umidita . "')";

    if ($conn->query($sql) === TRUE) {
      return "New record creato con successo";
    }
    else {
      return "Errore: " . $sql . "<br>" . $conn->error;
    }
    $conn->close();
  }
  
  function getAllReadings($limit) {
    global $servername, $username, $password, $dbname;

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
      die("Connection fallita: " . $conn->connect_error);
    }

    $sql = "SELECT id, sensoreId, stanza,  temperatura, umidita,datetime FROM sensor order by datetime desc limit " . $limit;
    if ($result = $conn->query($sql)) {
      return $result;
    }
    else {
      return false;
    }
    $conn->close();
  }
  function getLastReadings() {
    global $servername, $username, $password, $dbname;

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);
	
    if ($conn->connect_error) {
      die("Connessione fallita: " . $conn->connect_error);
    }

    $sql = "SELECT id, sensoreId, stanza, temperatura, umidita, datetime FROM sensor order by datetime desc limit 1" ;
    if ($result = $conn->query($sql)) {
      return $result->fetch_assoc();
    }
    else {
      return false;
    }
    $conn->close();
  }

  function minReading($limit, $value) {
     global $servername, $username, $password, $dbname;

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);
   
    if ($conn->connect_error) {
      die("Connessione fallita: " . $conn->connect_error);
    }

    $sql = "SELECT MIN(" . $value . ") AS min_amount FROM (SELECT " . $value . " FROM sensor order by datetime desc limit " . $limit . ") AS min";
    if ($result = $conn->query($sql)) {
      return $result->fetch_assoc();
    }
    else {
      return false;
    }
    $conn->close();
  }

  function maxReading($limit, $value) {
     global $servername, $username, $password, $dbname;

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
      die("Connessione fallita: " . $conn->connect_error);
    }

    $sql = "SELECT MAX(" . $value . ") AS max_amount FROM (SELECT " . $value . " FROM sensor order by datetime desc limit " . $limit . ") AS max";
    if ($result = $conn->query($sql)) {
      return $result->fetch_assoc();
    }
    else {
      return false;
    }
    $conn->close();
  }

  function avgReading($limit, $value) {
     global $servername, $username, $password, $dbname;

    // Crea connesione
    $conn = new mysqli($servername, $username, $password, $dbname);
   
    if ($conn->connect_error) {
      die("Connessione fallita: " . $conn->connect_error);
    }

    $sql = "SELECT AVG(" . $value . ") AS avg_amount FROM (SELECT " . $value . " FROM sensor order by datetime desc limit " . $limit . ") AS avg";
    if ($result = $conn->query($sql)) {
      return $result->fetch_assoc();
    }
    else {
      return false;
    }
    $conn->close();
  }
?>