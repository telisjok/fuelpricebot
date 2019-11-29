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

	// Load response to Objecet
	$xml = simplexml_load_string($response);

	$currentDate = date('Y-m-d H:i:s', time() + 60*60);
	$loggerFile = 'updates_log.txt';

	// Open DB Connection
	$pdo = dbOpen();
		
	foreach ($xml as $element) {
		// ------------- Check for changes ------------- //

		// Check if there are data for current station and the current product in the database
		$chkIfEdited = $pdo->prepare('SELECT * FROM station_product_map WHERE station_id = ? AND product_id = ?'); // Select current station and current product
		$chkIfEdited->execute([$element->station, $element->product->code]);
		$editResult = $chkIfEdited->fetch(PDO::FETCH_ASSOC);

		if(!$editResult) { // If no results found at all then insert the new entry in mapping table
			if($element->price != 0) { // Insert the new entry only if the price is not 0
				$currentFileContents = file_get_contents($loggerFile); // Get current logged content

				$ddOK = checkDD($element->address->dd->code); // Check if dd already exists
				if(!$ddOK) { // If dd not found then insert the new station
					$chckResult = insertDD($element->address->dd->code, $element->address->dd->dd_descr, $element->address->dd->dimos_descr, $element->address->dd->nomos_descr);
					echo $chckResult.'<br>';
					$currentFileContents .= 'Event logged on: '.$currentDate.'. | '.$chckResult."\n";
				}

				$stationOK = checkStation($element->station); // Check if station already exists
				if(!$stationOK) { // If station not found then insert the new station
					$chckResult = insertStation($element->station, $element->name, $element->address->fulladdress, $element->address->zipcode, $element->address->dd->code);
					echo $chckResult.'<br>';
					$currentFileContents .= 'Event logged on: '.$currentDate.'. | '.$chckResult."\n";
				}

				$companyOK = checkCompany($element->company->code); // Check if company already exists
				if(!$companyOK) { // If company not found then insert the new company
					$chckResult = insertCompany($element->company->code, $element->company->name);
					echo $chckResult.'<br>';
					$currentFileContents .= 'Event logged on: '.$currentDate.'. | '.$chckResult."\n";
				}

				$productOK = checkProduct($element->product->code); // Check if product already exists
				if(!$productOK) { // If product not found then insert the new product
					$chckResult = insertProduct($element->product->code, $element->product->description, $element->company->code);
					echo $chckResult.'<br>';
					$currentFileContents .= 'Event logged on: '.$currentDate.'. | '.$chckResult."\n";
				}

				$stat_prod_map = "INSERT INTO station_product_map (station_id, product_id, price, last_edit) VALUES (?, ?, ?, ?)";
				$stMap = $pdo->prepare($stat_prod_map);
				$epoch = substr($element->timestamp, 0, 10);
				$dt = new DateTime("@$epoch"); // convert UNIX timestamp to PHP DateTime
				$last_edited_on = $dt->format('Y-m-d H:i:s');
				
				try {
					$stMap->execute([$element->station, $element->product->code, $element->price, $last_edited_on]);
				} catch(PDOException $e) {
					print "Error!: " . $e->getMessage() . "<br/>";
				}

				// Display the message about the update
				$msg = 'New entry found for station '.$element->station.' on product '.$element->product->code.'. Price is '.$element->price.' on '.$last_edited_on.'.'; 
				echo $msg;
				echo '<br>';

				// Write events to logger
				$currentFileContents .= 'Event logged on: '.$currentDate.'. | '.$msg."\n";
				file_put_contents($loggerFile, $currentFileContents);
			}
		} elseif($editResult['price'] != $element->price) { // If results found then check if stored price is different from current price
			$epoch = substr($element->timestamp, 0, 10);
			$dt = new DateTime("@$epoch"); // convert UNIX timestamp to PHP DateTime
			$last_edited_on = $dt->format('Y-m-d H:i:s');

			if($element->price != 0) { // If new price is not 0 then update the old price with the new price
				$stat_prod_map = "UPDATE station_product_map SET price = ?, last_edit = ? WHERE station_id = ? AND product_id = ?"; // Perform the update for new price
				$stMap = $pdo->prepare($stat_prod_map);
				try {
					$stMap->execute([$element->price, $last_edited_on, $element->station, $element->product->code]);
				} catch(PDOException $e) {
					print "Error!: " . $e->getMessage() . "<br/>";
				}
				
				// Display the message about the update
				$msg = 'Update found for station '.$element->station.' on product '.$element->product->code.'. Previous price was '.$editResult['price'].' on '.$editResult['last_edit'].' and now is '.$element->price.' on '.$last_edited_on.'.'; 
				echo $msg;
				echo '<br>';

				// Write events to logger
				$currentFileContents = file_get_contents($loggerFile);
				$currentFileContents .= 'Event logged on: '.$currentDate.'. | '.$msg."\n";
				file_put_contents($loggerFile, $currentFileContents);
			} else { // If new price is 0 then delete the old entry from the database
				$stat_prod_map = "DELETE FROM station_product_map WHERE station_id = ? AND product_id = ?"; // Perform the deletion for new price being 0
				$stMap = $pdo->prepare($stat_prod_map);
				try {
					$stMap->execute([$element->station, $element->product->code]);
				} catch(PDOException $e) {
					print "Error!: " . $e->getMessage() . "<br/>";
				}
				
				// Display the message about the update
				$msg = 'Delete condition found for station '.$element->station.' on product '.$element->product->code.'. Previous price was '.$editResult['price'].' on '.$editResult['last_edit'].' and now is '.$element->price.' on '.$last_edited_on.'.';
				echo $msg;
				echo '<br>';

				// Write events to logger
				$currentFileContents = file_get_contents($loggerFile);
				$currentFileContents .= 'Event logged on: '.$currentDate.'. | '.$msg."\n";
				file_put_contents($loggerFile, $currentFileContents);
			}
		}
	}

	// Open DB Connection
	$pdo = dbClose();

	// Write events to logger
	$currentFileContents = file_get_contents($loggerFile);
	$currentFileContents .= "\n";
	file_put_contents($loggerFile, $currentFileContents);

	function checkDD($ddID) {
		$pdo = dbOpen();
		$chkStat = $pdo->prepare('SELECT * FROM dd WHERE id = ?');
		$chkStat->execute([$ddID]);
		$result = $chkStat->fetch(PDO::FETCH_ASSOC);
		$pdo = dbClose();

		return $result;
	}

	function insertDD($ddID, $dd_descr, $dimos_descr, $nomos_descr) {
		$pdo = dbOpen();
		$DD = "INSERT INTO dd (id, dd_descr, dimos_descr, nomos_descr) VALUES (?, ?, ?, ?)";
		$stDD = $pdo->prepare($DD);
		try {
			$stDD->execute([$ddID, $dd_descr, $dimos_descr, $nomos_descr]);
		} catch(PDOException $e) {
			print "Error!: " . $e->getMessage() . "<br/>";
		}
		$pdo = dbClose();

		return 'New DD added with id: '.$ddID;
	}

	function checkStation($stationID) {
		$pdo = dbOpen();
		$chkStat = $pdo->prepare('SELECT * FROM stations WHERE id = ?');
		$chkStat->execute([$stationID]);
		$result = $chkStat->fetch(PDO::FETCH_ASSOC);
		$pdo = dbClose();

		return $result;
	}

	function insertStation($stationID, $name, $address, $zipcode, $dd_code) {
		$pdo = dbOpen();
		$Station = "INSERT INTO stations (id, name, address, zipcode, dd_code) VALUES (?, ?, ?, ?, ?)";
		$stSt = $pdo->prepare($Station);
		try {
			$stSt->execute([$stationID, $name, $address, $zipcode, $dd_code]);
		} catch(PDOException $e) {
			print "Error!: " . $e->getMessage() . "<br/>";
		}
		$pdo = dbClose();

		return 'New Station added with id: '.$stationID;
	}

	function checkCompany($companyID) {
		$pdo = dbOpen();
		$chkComp = $pdo->prepare('SELECT * FROM companies WHERE id = ?');
		$chkComp->execute([$companyID]);
		$result = $chkComp->fetch(PDO::FETCH_ASSOC);
		$pdo = dbClose();

		return $result;
	}

	function insertCompany($companyID, $name) {
		$pdo = dbOpen();
		$Company = "INSERT INTO companies (id, name) VALUES (?, ?)";
		$stComp = $pdo->prepare($Company);
		try {
			$stComp->execute([$companyID, $name]);
		} catch(PDOException $e) {
			print "Error!: " . $e->getMessage() . "<br/>";
		}
		$pdo = dbClose();

		return 'New Company added with id: '.$companyID;
	}

	function checkProduct($productID) {
		$pdo = dbOpen();
		$chkProd = $pdo->prepare('SELECT * FROM products WHERE id = ?');
		$chkProd->execute([$productID]);
		$result = $chkProd->fetch(PDO::FETCH_ASSOC);
		$pdo = dbClose();

		return $result;
	}

	function insertProduct($productID, $description, $company_id) {
		$pdo = dbOpen();
		$Product = "INSERT INTO products (id, description, company_id) VALUES (?, ?, ?)";
		$stProd = $pdo->prepare($Product);
		try {
			$stProd->execute([$productID, $description, $company_id]);
		} catch(PDOException $e) {
			print "Error!: " . $e->getMessage() . "<br/>";
		}
		$pdo = dbClose();

		return 'New Product added with id: '.$productID;
	}
?>