<?php 
  class Prices {
    // DB stuff
    private $conn;
    
    // Price Properties
    public $price;
    public $station;
    public $address;
    public $company;
    public $product;
    
    // Constructor with DB
    public function __construct($db) {
      $this->conn = $db;
    }
    // Get Posts
    public function read($dimos, $product, $company) {
      // Create query
      $query  = 'SELECT map.price AS price, p.description AS product, s.name AS station, s.id AS stationID, s.address AS address, c.name AS company FROM station_product_map AS map ';
      $query .= 'JOIN stations AS s ON s.id = map.station_id ';
      $query .= 'JOIN products AS p ON p.id = map.product_id ';
      $query .= 'JOIN dd AS d ON d.id = s.dd_code ';
      $query .= 'JOIN companies AS c ON c.id = p.company_id ';
      $query .= 'WHERE map.price != 0 AND d.dimos_descr LIKE \'%'.$dimos.'%\' ';
      
      if($product == 'Υγραέριο') {
        $query .= 'AND p.description LIKE \'%'.$product.'%\' ';
      } elseif($product == 'Diesel') {
        $query .= 'AND (p.description LIKE \'%Diesel Κίνησης%\' OR p.description LIKE \'%Diesel Κινησης%\' OR p.description LIKE \'%Πετρέλαιο Κίνησης%\' OR p.description LIKE \'%Πετρελαιο Κινησης%\' ) ';
      } elseif($product == 'Αμόλυβδη 95') {
        $query .= 'AND p.description LIKE \'%95%\' ';
      } elseif($product == 'Αμόλυβδη 100') {
        $query .= 'AND (p.description LIKE \'%BP Ultimate 100%\' OR p.description LIKE \'%Αμόλυβδη 100%\' OR p.description LIKE \'%Unleaded 100%\' OR p.description LIKE \'%Unleaded plus 100%\' ) ';
      }  elseif($product == 'Super') {
        $query .= 'AND (p.description NOT LIKE \'%Super Heat%\' AND p.description LIKE \'%Super%\' ) ';
      } elseif($product == 'Heat') {
        $query .= 'AND (p.description LIKE \'%Heat%\' OR p.description LIKE \'%Θέρμα%\' ) ';
      }

      if(!empty($company)) {
        $query .= 'AND c.name LIKE \'%'.$company.'%\' ';
      }

      $query .= 'ORDER BY map.price ';
      $query .= 'LIMIT 2';

      // Prepare statement
      $stmt = $this->conn->prepare($query);
      // Execute query
      $stmt->execute();
      return $stmt;
    }
  }
?>