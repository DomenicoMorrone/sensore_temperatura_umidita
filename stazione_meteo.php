<?php
    include_once('database.php');
    if (isset($_GET["readingsCount"])){
      $data = $_GET["readingsCount"];
      $data = trim($data);
      $data = stripslashes($data);
      $data = htmlspecialchars($data);
      $readings_count = $_GET["readingsCount"];
    }
    // ultime letture visualizzabili
    else {
      $readings_count = 20;
    }

    $last_reading = getLastReadings();
    $last_reading_temp = $last_reading["temperatura"];
    $last_reading_humi = $last_reading["umidita"];
    $last_reading_time = $last_reading["datetime"];

    $min_temp = minReading($readings_count, 'temperatura');
    $max_temp = maxReading($readings_count, 'temperatura');
    $avg_temp = avgReading($readings_count, 'temperatura');

    $min_humi = minReading($readings_count, 'umidita');
    $max_humi = maxReading($readings_count, 'umidita');
    $avg_humi = avgReading($readings_count, 'umidita');
?>

<!DOCTYPE html>
<html>
    <head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<meta http-equiv="refresh" content="10">
        <link rel="stylesheet" type="text/css" href="stile.css">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    </head>
    <header class="header">
        <h1>📊STAZIONE METEO</h1>
        <form method="get">
            <input type="number" name="readingsCount" min="1" placeholder="Number of readings (<?php echo $readings_count; ?>)">
            <input type="submit" value="UPDATE">
        </form>
    </header>
<body>
    <p>Last reading: <?php echo $last_reading_time; ?></p>
    <section class="content">
	    <div class="box gauge--1">
	    <h3>TEMPERATURA</h3>
              <div class="mask">
			  <div class="semi-circle"></div>
			  <div class="semi-circle--mask"></div>
			</div>
		    <p style="font-size: 30px;" id="temp">--</p>
		    <table cellspacing="5" cellpadding="5">
		        <tr>
		            <th colspan="3">Temperatura <?php echo $readings_count; ?> letture</th>
	            </tr>
		        <tr>
		            <td>Min</td>
                    <td>Max</td>
                    <td>Media</td>
                </tr>
                <tr>
                    <td><?php echo $min_temp['min_amount']; ?> &deg;C</td>
                    <td><?php echo $max_temp['max_amount']; ?> &deg;C</td>
                    <td><?php echo round($avg_temp['avg_amount'], 2); ?> &deg;C</td>
                </tr>
            </table>
        </div>
        <div class="box gauge--2">
            <h3>UMIDITA</h3>
            <div class="mask">
                <div class="semi-circle"></div>
                <div class="semi-circle--mask"></div>
            </div>
            <p style="font-size: 30px;" id="humi">--</p>
            <table cellspacing="5" cellpadding="5">
                <tr>
                    <th colspan="3">Umididà <?php echo $readings_count; ?> letture</th>
                </tr>
                <tr>
                    <td>Min</td>
                    <td>Max</td>
                    <td>Media</td>
                </tr>
                <tr>
                    <td><?php echo $min_humi['min_amount']; ?> %</td>
                    <td><?php echo $max_humi['max_amount']; ?> %</td>
                    <td><?php echo round($avg_humi['avg_amount'], 2); ?> %</td>
                </tr>
            </table>
        </div>
    </section>
<?php
    echo   '<h2> View Latest ' . $readings_count . ' Readings</h2>
            <table cellspacing="5" cellpadding="5" id="tableReadings">
                <tr>
                    <th>ID</th>
					<th>Sensore-Id</th>
                    <th>Stanza</th>
                    <th>Temperatura</th>
                    <th>Umidità</th>
                    <th>Data-ora</th>
                </tr>';

    $result = getAllReadings($readings_count);
        if ($result) {
        while ($row = $result->fetch_assoc()) {
            $row_id = $row["id"];
			$row_sensoreId = $row["sensoreId"];
            $row_stanza = $row["stanza"];
            $row_temperatura = $row["temperatura"];
            $row_umidita = $row["umidita"];
            $row_reading_time = $row["datetime"];

            echo '<tr>
                    <td>' . $row_id . '</td>
					<td>' . $row_sensoreId . '</td>
                    <td>' . $row_stanza . '</td>
                    <td>' . $row_temperatura . '</td>
                    <td>' . $row_umidita . '</td>
                    <td>' . $row_reading_time . '</td>
                  </tr>';
        }
        echo '</table>';
        $result->free();
    }
?>

<script>
    var temperatura = <?php echo $last_reading_temp; ?>;
    var umidita = <?php echo $last_reading_humi; ?>;
    setTemperature(temperatura);
    setHumidity(umidita);

    function setTemperature(curVal){
    	//set range valore temperatura 
    	var minTemp = -5.0;
    	var maxTemp = 50.0;

    	var newVal = scaleValue(curVal, [minTemp, maxTemp], [0, 180]);
    	$('.gauge--1 .semi-circle--mask').attr({
    		style: '-webkit-transform: rotate(' + newVal + 'deg);' +
    		'-moz-transform: rotate(' + newVal + 'deg);' +
    		'transform: rotate(' + newVal + 'deg);'
    	});
    	$("#temp").text(curVal + ' ºC');
    }

    function setHumidity(curVal){
    	//set range for Humidity percentage 0 % to 100 %
    	var minHumi = 0;
    	var maxHumi = 100;

    	var newVal = scaleValue(curVal, [minHumi, maxHumi], [0, 180]);
    	$('.gauge--2 .semi-circle--mask').attr({
    		style: '-webkit-transform: rotate(' + newVal + 'deg);' +
    		'-moz-transform: rotate(' + newVal + 'deg);' +
    		'transform: rotate(' + newVal + 'deg);'
    	});
    	$("#humi").text(curVal + ' %');
    }

    function scaleValue(value, from, to) {
        var scale = (to[1] - to[0]) / (from[1] - from[0]);
        var capped = Math.min(from[1], Math.max(from[0], value)) - from[0];
        return ~~(capped * scale + to[0]);
    }
	

</script>

</body>
</html>