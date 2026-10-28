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
	Portions created by the Initial Developer are Copyright (C) 2010-2023
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	James Rose <james.o.rose@gmail.com>
	Matthew Vale <github@mafoo.org>
*/

//Shared read-only selector for legacy pages using FS PBX Music on Hold streams.
if (!class_exists('switch_music_on_hold')) {
	class switch_music_on_hold {

		public function select($name, $selected, $options) {
			//add multi-lingual support
				$language = new text;
				$text = $language->get();

			//start the select
				$select = "<select class='formfld' name='".$name."' id='".$name."' style='width: auto;'>\n";

			//music on hold
				$music_list = $this->get();
				if (count($music_list) > 0) {
					$select .= "	<option value=''>\n";
					$select .= "	<optgroup label='".$text['label-music_on_hold']."'>\n";
					$previous_name = '';
					foreach($music_list as $row) {
						if ($previous_name != $row['music_on_hold_name']) {
							$name = '';
							if (!empty($row['domain_uuid'])) {
								$name = $row['domain_name'].'/';
							}
							$name .= $row['music_on_hold_name'];
							$select .= "		<option value='local_stream://".$name."' ".(($selected == "local_stream://".$name) ? 'selected="selected"' : null).">".$row['music_on_hold_name']."</option>\n";
						}
						$previous_name = $row['music_on_hold_name'];
					}
					$select .= "	</optgroup>\n";
				}
			//recordings
				if (is_dir($_SERVER["PROJECT_ROOT"].'/app/recordings')) {
					require_once "app/recordings/resources/classes/switch_recordings.php";
					$recordings_c = new switch_recordings;
					$recordings = $recordings_c->list_recordings();
					if (is_array($recordings) && sizeof($recordings) > 0) {
						$select .= "	<optgroup label='".$text['label-recordings']."'>";
						foreach($recordings as $recording_value => $recording_name){
							$select .= "		<option value='".$recording_value."' ".(($selected == $recording_value) ? 'selected="selected"' : null).">".$recording_name."</option>\n";
						}
						$select .= "	</optgroup>\n";
					}
				}
			//streams
				if (is_dir($_SERVER["PROJECT_ROOT"].'/app/streams')) {
					$sql = "select * from v_streams ";
					$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
					$sql .= "and stream_enabled = 'true' ";
					$sql .= "order by stream_name asc ";
					$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
					$database = new database;
					$streams = $database->select($sql, $parameters, 'all');
					if (is_array($streams) && @sizeof($streams) != 0) {
						$select .= "	<optgroup label='".$text['label-streams']."'>";
						foreach($streams as $row){
							$select .= "		<option value='".$row['stream_location']."' ".(($selected == $row['stream_location']) ? 'selected="selected"' : null).">".$row['stream_name']."</option>\n";
						}
						$select .= "	</optgroup>\n";
					}
					unset($sql, $parameters, $streams, $row);
				}
			//add additional options
				$select .= "	<optgroup label='".$text['label-others']."'>";
				$select .= "		<option value='silence' ".($selected == "silence" ? 'selected="selected"' : null).">".$text['label-none']."</option>\n";
				if (is_array($options) && sizeof($options) > 0) {
					$select .= $options;
				}
				$select .= "	</optgroup>\n";
			//end the select and return it
				$select .= "</select>\n";
				return $select;
		}

		public function get() {
			//get moh records, build array
				$sql = "select ";
				$sql .= "d.domain_name, ";
				$sql .= "m.* ";
				$sql .= "from v_music_on_hold as m ";
				$sql .= "left join v_domains as d on d.domain_uuid = m.domain_uuid ";
				$sql .= "where (m.domain_uuid = :domain_uuid or m.domain_uuid is null) ";
				$sql .= "order by m.domain_uuid desc, music_on_hold_name asc, music_on_hold_rate asc ";
				$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
				$database = new database;
				return $database->select($sql, $parameters, 'all');
		}

	}
}
