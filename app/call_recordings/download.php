<?php
/*
	FusionPBX
	Version: MPL 1.1

	The contents of this file are subject to the Mozilla Public License Version
	1.1 (the "License"); you may not use this file except in compliance with
	the License. You may obtain a copy of the License at
	http://www.mozilla.org/MPL/

	Software distributed under the License is distributed on an "AS IS" basis,
	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
	for the specific language governing rights and limitations under the
	License.

	The Original Code is FusionPBX

	The Initial Developer of the Original Code is
	Mark J Crane <markjcrane@fusionpbx.com>
	Portions created by the Initial Developer are Copyright (C) 2016-2020
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once  '../../resources/classes/database.php';


//check permisions
	if (permission_exists('call_recording_play') || permission_exists('call_recording_download')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//download
	if(isset($_GET['chk'])){
		$sql = "SELECT default_setting_value FROM v_default_settings WHERE default_setting_category ='server' AND default_setting_subcategory = 's3-manager-presigned-api' ";
	
		$database = new database;
		
		$result = $database->select($sql, $parameters, 'all');
		unset($sql, $parameters);
		
		$url = $result[0]['default_setting_value'];
		$uuid = $_GET['id'];
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
		$url = json_decode($response)->url;
		$call_recording_name = explode('?',basename($url))[0];
		header('Content-Disposition: attachment; filename="'.$call_recording_name.'"');
		header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		flush(); // Flush system output buffer
		readfile($url);

	}else{
		if (is_uuid($_GET['id'])) {
			$obj = new call_recordings;
			$obj->recording_uuid = $_GET['id'];
			$obj->binary = isset($_GET['binary']) ? true : false;
			$obj->download();
		}
	}
	


?>