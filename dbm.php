<?php
	$conn = null;

	function dbOpen() {
		try {
			$conn = new PDO('mysql:host=localhost;dbname=fuelprices', 'root', '', array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET CHARACTER SET UTF8"));
			return $conn;
		} catch (PDOException $e) {
			print "Error!: " . $e->getMessage() . "<br/>";
    		die();
		}
	}

	function dbClose() {
		$conn = null;
	}

?>