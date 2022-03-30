<?php
require_once  '../../resources/classes/database.php';

$sql = "SELECT default_setting_value FROM v_default_settings WHERE default_setting_category ='server' AND default_setting_subcategory = 's3-manager-presigned-api' ";

$database = new database;

$result = $database->select($sql, $parameters, 'all');
unset($sql, $parameters);

$url = $result[0]['default_setting_value'];
$uuid = $_POST['uuid'];
// echo $uuid;
// exit();
$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => $url,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS =>'{
    "call_recording_uuid":"'.$uuid.'"
}',
  CURLOPT_HTTPHEADER => array(
    'Content-Type: application/json'
  ),
));
$response = curl_exec($curl);
curl_close($curl);
echo $response;