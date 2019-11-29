<?php 
  // Headers
  header('Access-Control-Allow-Origin: *');
  header('Content-Type: application/x-www-form-urlencoded');

  include_once '../../config/Database.php';
  include_once '../../models/Prices.php';
  
  // Instantiate DB & connect
  $database = new Database();
  $db = $database->connect();

  // Instantiate blog post object
  $prices = new Prices($db);
  
  $axiosResponse = file_get_contents("php://input");
  
  $resp = json_decode($axiosResponse);

  if(isset($resp->dimos)) { $dimos = $resp->dimos; } else { $dimos = ''; }
  if(isset($resp->product)) { $product = $resp->product; } else { $product = ''; }
  if(isset($resp->company)) { $company = $resp->company; } else { $company = ''; }

  if(empty($dimos)) {
  	echo json_encode(
      array('error' => 'Κανένα αποτέλσμα. Όρισε δήμο.')
    );
    die();
  }
  
  if($dimos == 'Serres') {
      $dimos = 'Δήμος Σερρών';
  } 
  if($dimos == 'Thessaloniki') {
      $dimos = 'Δήμος Θεσσαλονίκης';
  } 
  if($dimos == 'Athina') {
      $dimos = 'Δήμος Αθηναίων';
  }
  
  if($product == 'unleaded 95') {
      $product = 'Αμόλυβδη 95';
  } elseif($product == 'unleaded 100') {
      $product = 'Αμόλυβδη 100';
  } elseif($product == 'diesel') {
      $product = 'Diesel';
  } else {
      $product = 'Gas';
  }

  // Blog post query
  $result = $prices->read($dimos, $product, $company);

  // Get row count
  $num = $result->rowCount();

  $counter = 1;
  // Check if any posts
  if($num > 0) {
    // Post array
    $response = array();
    // $posts_arr['data'] = array();
    while($row = $result->fetch(PDO::FETCH_ASSOC)) {
      extract($row);
      $price_item = array(
      	'#' => $counter,
        'price' => $price,
        'station' => $station,
        'address' => $address,
        'company' => $company
      );
      
      // Push to "data"
      array_push($response, $price_item);
      
      $counter = $counter + 1;
    }
    // Turn to JSON & output
    echo json_encode($response);
  } else {
    // No Posts
    echo json_encode(
      array('message' => 'Δεν βρέθηκαν πρατήρια')
    );
  }
?>