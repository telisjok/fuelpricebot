<?php 
	class Database {
		// DB Params
		private $host = 'localhost';
		private $db_name = 'benzinig_fuels';
		private $username = 'benzinig_root';
		private $password = '167458967teliss';
		private $conn;
		
		// DB Connect
		public function connect() {
			$this->conn = null;
			try { 
				$this->conn = new PDO('mysql:host=' . $this->host . ';dbname=' . $this->db_name, $this->username, $this->password, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET CHARACTER SET UTF8"));
				$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			} catch(PDOException $e) {
				echo 'Connection Error: ' . $e->getMessage();
			}
			return $this->conn;
		}
	}
?>