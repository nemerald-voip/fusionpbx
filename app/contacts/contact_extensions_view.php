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
	Portions created by the Initial Developer are Copyright (C) 2008-2020
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('contact_extension_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//get the extension list
	$sql = "select e.extension_uuid, e.extension, e.enabled, e.description ";
	$sql .= "from v_extensions e, v_extension_users eu, v_users u ";
	$sql .= "where e.extension_uuid = eu.extension_uuid ";
	$sql .= "and u.user_uuid = eu.user_uuid ";
	$sql .= "and e.domain_uuid = :domain_uuid ";
	$sql .= "and u.contact_uuid = :contact_uuid ";
	$sql .= "order by e.extension asc ";
	$parameters['domain_uuid'] = $domain_uuid;
	$parameters['contact_uuid'] = $contact_uuid ?? '';
	$database = new database;
	$contact_extensions = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//show if exists
	if (!empty($contact_extensions)) {

		//show the content
			echo "<div class='grid' style='grid-template-columns: 70px 100px auto;'>\n";
			$x = 0;
			foreach ($contact_extensions as $row) {
				if ($row['enabled'] != 'true') { continue; } //skip disabled extensions
				echo "<div class='box contact-details-label'>".$text['label-extension']."</div>\n";
// 				($row['url_primary'] ? "style='font-weight: bold;'" : null).">\n";
				echo "<div class='box'>";
				echo escape($row['extension']);
				echo "</div>\n";
				echo "<div class='box'>".$row['description']."</div>\n";
				$x++;
			}
			echo "</div>\n";
			unset($contact_extensions);

	}

?>
