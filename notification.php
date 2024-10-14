<?php
    $host = getenv('DB_HOST') ?: '34.138.176.84';
    $user = getenv('DB_USER') ?: 'leopica';
    $password = getenv('DB_PASSWORD') ?: 'Leo12345!';
    $db = getenv('DB_NAME') ?: 'leozada';
    $port = getenv('DB_PORT') ?: '19038';
    $accessToken = getenv('ACCESS_TOKEN') ?: 'APP_USR-891104909929153-100422-44c8d5ad01e0b6c29a9c331bfe0c99da-558785318';

   if ($_SERVER["REQUEST_METHOD"] != "POST") {
        http_response_code(500);
        return;
   }
   if (!(isset($_GET['id'])) && !(isset($_GET['topic']))) {
        http_response_code(500);
        return;
   }
   if ($_GET['topic'] != "payment") {
        http_response_code(500);
        return;
   }

   $id = $_GET['id'];

   $curl = curl_init();

   curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.mercadopago.com/v1/payments/' . $id,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer ' . $accessToken
        )
   ));

   $payment = json_decode(curl_exec($curl), true);

   if ($payment && $payment["status"] === "approved") {
    
    $conn = new mysqli($host, $user, $password, $db, $port);

    if ($conn->connect_error) {
        http_response_code(500);
        $conn->close();
        return;
    }
    $player = $payment["external_reference"];

    $insertSql = "INSERT INTO autopix_pendings (id, player) " 
            . "VALUES ('" . $id . "', '" . $player . "');";

    if ($conn->query($insertSql)) {
        $conn->close();
        http_response_code(201);
    } else {
        http_response_code(500);
        $conn->close();
    }
   }
  
?>
