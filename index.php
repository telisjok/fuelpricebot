<?php
	ini_set('max_execution_time', 300);
	require('dbm.php');

	$ch = curl_init();

	$url = 'http://www.fuelprices.gr/test/xml/get_prices.view';
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_ENCODING, "");
	$response = curl_exec($ch);
	curl_close($ch);

	// Load response to Object
	$xml = simplexml_load_string($response);

	// Open DB Connection
	$pdo = dbOpen();
	$totalDD = 0;
	$totalStations = 0;
	$totalCompanies = 0;
	$totalProducts = 0;
	$totalPrices = 0;
		
	foreach ($xml as $element) {
		// First populate the DDs before station since the dd_code in station table is a reference key to the dd table	
		$DD = "INSERT INTO dd (id, dd_descr, dimos_descr, nomos_descr) VALUES (?, ?, ?, ?)";
		$stDD = $pdo->prepare($DD);
		try {
			if($stDD->execute([$element->address->dd->code, $element->address->dd->dd_descr, $element->address->dd->dimos_descr, $element->address->dd->nomos_descr])) {
				$totalDD += 1;
			}
		} catch(PDOException $e) {
			print "Error!: " . $e->getMessage() . "<br/>";
		}
	

		// Populate the stations
		$Stations = "INSERT INTO stations (id, name, address, zipcode, dd_code) VALUES (?, ?, ?, ?, ?)";
		$stSt = $pdo->prepare($Stations);
		try {
			if($stSt->execute([$element->station, $element->name, $element->address->fulladdress, $element->address->zipcode, $element->address->dd->code])) {
				$totalStations += 1;
			}
		} catch(PDOException $e) {
			print "Error!: " . $e->getMessage() . "<br/>";
		}


		// Populate the companies
		$Companies = "INSERT INTO companies (id, name) VALUES (?, ?)";
		$stComp = $pdo->prepare($Companies);
		try {
			if($stComp->execute([$element->company->code, $element->company->name])) {
				$totalCompanies += 1;
			}
		} catch(PDOException $e) {
			print "Error!: " . $e->getMessage() . "<br/>";
		}


		// Populate the products
		$Products = "INSERT INTO products (id, description, company_id) VALUES (?, ?, ?)";
		$stProd = $pdo->prepare($Products);
		try {
			if($stProd->execute([$element->product->code, $element->product->description, $element->company->code])) {
				$totalProducts += 1;
			}
		} catch(PDOException $e) {
			print "Error!: " . $e->getMessage() . "<br/>";
		}


		// Populate the mapping table
		if($element->price != 0) { // If price has 0 value then don't insert
			$stat_prod_map = "INSERT INTO station_product_map (station_id, product_id, price, last_edit) VALUES (?, ?, ?, ?)";
			$stMap = $pdo->prepare($stat_prod_map);
			$epoch = substr($element->timestamp, 0, 10);
			$dt = new DateTime("@$epoch"); // convert UNIX timestamp to PHP DateTime
			$last_edited_on = $dt->format('Y-m-d H:i:s');
			try {
				if($stMap->execute([$element->station, $element->product->code, $element->price, $last_edited_on])) {
					$totalPrices += 1;
				}
			} catch(PDOException $e) {
				print "Error!: " . $e->getMessage() . "<br/>";
			}
		}
	}

	echo '<h3>The initialization of the database has finished.</h3><br>
			<strong>Total Regions</strong>: '.$totalDD.'<br>
			<strong>Total Stations</strong>: '.$totalStations.'<br>
			<strong>Total Companies</strong>: '.$totalCompanies.'<br>
			<strong>Total Products</strong>: '.$totalProducts.'<br>
			<strong>Total Prices Mapped</strong>: '.$totalPrices;

	// Open DB Connection
	$pdo = dbClose();
?>