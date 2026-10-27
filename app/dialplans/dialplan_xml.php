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
	Portions created by the Initial Developer are Copyright (C) 2008-2021
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('dialplan_edit')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//get the uuids
	if (!empty($_REQUEST['id']) && is_uuid($_REQUEST['id'])) {
		$dialplan_uuid = $_REQUEST['id'];
	}
	if (!empty($_REQUEST['app_uuid']) && is_uuid($_REQUEST['app_uuid'])) {
		$app_uuid = $_REQUEST['app_uuid'];
	}
	$dialplan_xml = $_REQUEST['dialplan_xml'] ?? '';

//process the HTTP POST
	if (count($_POST) > 0 && empty($_POST["persistformvar"])) {

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: dialplans.php');
				exit;
			}

		//get the dialplan xml
			if (is_uuid($dialplan_uuid)) {
				$sql = "select * from v_dialplans ";
				$sql .= "where dialplan_uuid = :dialplan_uuid ";
				$parameters['dialplan_uuid'] = $dialplan_uuid;
				$database = new database;
				$row = $database->select($sql, $parameters, 'row');
				if (is_array($row) && @sizeof($row) != 0) {
					$app_uuid = $row["app_uuid"];
					$dialplan_context = $row["dialplan_context"];
				}
				unset($sql, $parameters, $row);
			}

		//validate the xml
			$dialplan_valid = true;
			if (preg_match("/.*([\"\'])system([\"\']).*>/i", $dialplan_xml)) {
				$dialplan_valid = false;
			}
			if (preg_match("/.*([\"\'])bgsystem([\"\']).*>/i", $dialplan_xml)) {
				$dialplan_valid = false;
			}
			if (preg_match("/.*([\"\'])bg_spawn([\"\']).*>/i", $dialplan_xml)) {
				$dialplan_valid = false;
			}
			if (preg_match("/.*([\"\'])spawn([\"\']).*>/i", $dialplan_xml)) {
				$dialplan_valid = false;
			}
			if (preg_match("/.*([\"\'])spawn_stream([\"\']).*>/i", $dialplan_xml)) {
				$dialplan_valid = false;
			}
			if (preg_match("/.*{system.*/i", $dialplan_xml)) {
				$dialplan_valid = false;
			}
			if (preg_match("/.*{bgsystem.*/i", $dialplan_xml)) {
				$dialplan_valid = false;
			}
			if (preg_match("/.*{bg_spawn.*/i", $dialplan_xml)) {
				$dialplan_valid = false;
			}
			if (preg_match("/.*{spawn.*/i", $dialplan_xml)) {
				$dialplan_valid = false;
			}
			if (preg_match("/.*{spawn_stream.*/i", $dialplan_xml)) {
				$dialplan_valid = false;
			}

		//disable xml entities and load the xml object to test if the xml is valid
			if (PHP_VERSION_ID < 80000) { libxml_disable_entity_loader(true); }
			preg_match_all('/^\s*<extension.+>(?:[\S\s])+<\/extension>\s*$/mU', $dialplan_xml, $matches);
			foreach($matches as $match) {
				$xml = simplexml_load_string($match[0], 'SimpleXMLElement', LIBXML_NOCDATA);
				if (!$xml) {
					//$errors = libxml_get_errors();
					$dialplan_valid = false;
					break;
				}
			}

		//save the xml to the database
			if ($dialplan_valid) {
				//build the dialplan array
					$x = 0;
					//$array['dialplans'][$x]["domain_uuid"] = $_SESSION['domain_uuid'];
					$array['dialplans'][$x]["dialplan_uuid"] = $dialplan_uuid;
					$array['dialplans'][$x]["dialplan_xml"] =  $dialplan_xml;

				//save to the data
					$database = new database;
					$database->app_name = 'dialplans';
					$database->app_uuid = is_uuid($app_uuid) ? $app_uuid : '742714e5-8cdf-32fd-462c-cbe7e3d655db';
					$database->save($array);
					unset($array);

				//clear the cache
					$cache = new cache;
					if ($dialplan_context == "\${domain_name}" or $dialplan_context == "global") {
						$dialplan_context = "*";
					}
					$cache->delete("dialplan:".$dialplan_context);

				//save the message to a session variable
					message::add($text['message-update']);
			}
			else {
				//save the message to a session variable
					message::add($text['message-failed'], 'negative');
			}

		//redirect the user
			header("Location: dialplan_edit.php?id=".$dialplan_uuid.(is_uuid($app_uuid) ? "&app_uuid=".$app_uuid : null));
			exit;

	}

//get the dialplan xml
	if (is_uuid($dialplan_uuid)) {
		$sql = "select * from v_dialplans ";
		$sql .= "where dialplan_uuid = :dialplan_uuid ";
		$parameters['dialplan_uuid'] = $dialplan_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$domain_uuid = $row["domain_uuid"];
			$dialplan_name = $row["dialplan_name"];
			$dialplan_number = $row["dialplan_number"];
			$dialplan_order = $row["dialplan_order"];
			$dialplan_continue = $row["dialplan_continue"];
			$dialplan_context = $row["dialplan_context"];
			$dialplan_xml = $row["dialplan_xml"];
			$dialplan_enabled = $row["dialplan_enabled"];
			$dialplan_description = $row["dialplan_description"];
		}
		unset($sql, $parameters, $row);
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	$document['title'] = $text['title-dialplan_edit'].' XML';
	require_once "resources/header.php";

//show the content
	echo "<form method='post' name='frm' id='frm'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-dialplan_edit']." XML</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','link'=>'dialplan_edit.php?id='.urlencode($dialplan_uuid).(!empty($app_uuid) && is_uuid($app_uuid) ? "&app_uuid=".urlencode($app_uuid) : null)]);
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','style'=>'margin-left: 15px;']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['description-dialplan-edit']."\n";
	echo "<br />\n";

	echo "\t<textarea class='formfld' name='dialplan_xml' id='dialplan_xml' rows='30' wrap='off' spellcheck='false' style='width: 100%; font-family: monospace; resize: vertical;'>".htmlspecialchars($dialplan_xml, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')."</textarea>\n";
	echo "	<br />\n";

	echo "	<input type='hidden' name='app_uuid' value='".escape($app_uuid ?? null)."'>\n";
	echo "	<input type='hidden' name='dialplan_uuid' value='".escape($dialplan_uuid ?? null)."'>\n";
	echo "	<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>\n";

//show the footer
	require_once "resources/footer.php";

?>
