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
	Portions created by the Initial Developer are Copyright (C) 2008-2023
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

if ($domains_processed == 1) {

	//if the default groups do not exist add them
		$group = new groups;
		$group->defaults();

	//create the user view combines username, organization, contact first and last name
		$database = new database;
		$database->execute("DROP VIEW view_users;", null);

		$sql = "CREATE VIEW view_users AS ( \n";
		$sql .= "	select u.domain_uuid, u.user_uuid, d.domain_name, u.username, u.user_status, u.user_enabled, u.add_date, \n";
		if (file_exists($_SERVER["PROJECT_ROOT"]."/app/contacts/app_config.php")) {
			$sql .= "	c.contact_uuid, c.contact_organization, c.contact_name_given ||' '|| c.contact_name_family as contact_name, c.contact_name_given, c.contact_name_family, \n";
		}
		$sql .= "	( \n";
		$sql .= "		select \n";
		$sql .= "		string_agg(g.group_name, ', ') \n";
		$sql .= "		from \n";
		$sql .= "		v_user_groups as ug, \n";
		$sql .= "		v_groups as g \n";
		$sql .= "		where \n";
		$sql .= "		ug.group_uuid = g.group_uuid \n";
		$sql .= "		and u.user_uuid = ug.user_uuid \n";
		$sql .= "	) AS group_names, \n";
		$sql .= "	( \n";
		$sql .= "		select \n";
		$sql .= "		string_agg(g.group_uuid::text, ', ') \n";
		//$sql .= "		array_agg(g.group_uuid::text) \n";
		$sql .= "		from \n";
		$sql .= "		v_user_groups as ug, \n";
		$sql .= "		v_groups as g \n";
		$sql .= "		where \n";
		$sql .= "		ug.group_uuid = g.group_uuid \n";
		$sql .= "		and u.user_uuid = ug.user_uuid \n";
		$sql .= "	) AS group_uuids, \n";
		$sql .= "	( \n";
		$sql .= "		SELECT group_level \n";
		$sql .= "		FROM v_user_groups ug, v_groups g \n";
		$sql .= "		WHERE (ug.group_uuid = g.group_uuid) \n";
		$sql .= "		AND (u.user_uuid = ug.user_uuid) \n"; 
		$sql .= "		ORDER BY group_level DESC \n";
		$sql .= "		LIMIT 1 \n";
		$sql .= "	) AS group_level \n";
		if (file_exists($_SERVER["PROJECT_ROOT"]."/app/contacts/app_config.php")) {
			$sql .= "	from v_contacts as c \n";
			$sql .= "	right join v_users u on u.contact_uuid = c.contact_uuid \n";
			$sql .= "	inner join v_domains as d on d.domain_uuid = u.domain_uuid \n";
		}
		else {
			$sql .= "	from v_users as u \n";
			$sql .= "	inner join v_domains as d on d.domain_uuid = u.domain_uuid \n";
		}
		$sql .= "	where 1 = 1 \n";
		$sql .= "	order by u.username asc \n";
		$sql .= "); \n";
		$database = new database;
		$database->execute($sql, null);
		unset($sql);

	//find rows that have a null group_uuid and set the correct group_uuid
		$sql = "select * from v_user_groups ";
		$sql .= "where group_uuid is null; ";
		$database = new database;
		$result = $database->select($sql, null, 'all');
		if (is_array($result)) {
			foreach($result as $row) {
				if (!empty($row['group_name'])) {
					//get the group_uuid
						$sql = "select group_uuid from v_groups ";
						$sql .= "where group_name = :group_name ";
						$parameters['group_name'] = $row['group_name'];
						$database = new database;
						$group_uuid = $database->execute($sql, $parameters, 'column');
						unset($sql, $parameters);
					//set the user_group_uuid
						$sql = "update v_user_groups set ";
						$sql .= "group_uuid = :group_uuid ";
						$sql .= "where user_group_uuid = :user_group_uuid; ";
						$parameters['group_uuid'] = $group_uuid;
						$parameters['user_group_uuid'] = $row['user_group_uuid'];
						$database = new database;
						$database->execute($sql, $parameters);
						unset($sql, $parameters);
				}
			}
		}
		unset($result);

	//update users email if they are all null
		$sql = "select count(*) from v_users ";
		$sql .= "where user_email is not null; ";
		$database = new database;
		$num_rows = $database->select($sql, null, 'column');
		if ($num_rows == 0) {
			$sql = "with users AS ( ";
			$sql .= "	select u.domain_uuid, u.user_uuid, u.username, u.contact_uuid, e.email_address ";
			$sql .= "	from v_users as u, v_contact_emails as e ";
			$sql .= "	where u.contact_uuid is not null ";
			$sql .= "	and u.contact_uuid = e.contact_uuid ";
			$sql .= "	and e.email_primary = 1 ";
			$sql .= ") ";
			$sql .= "update v_users ";
			$sql .= "set user_email = users.email_address ";
			$sql .= "from users ";
			$sql .= "where v_users.user_uuid = users.user_uuid;";
			$database = new database;
			$database->execute($sql, null);
		}

	//find rows that have a null group_uuid and set the correct group_uuid
		$sql = "select count(*) from v_default_settings ";
		$sql .= "where default_setting_category = 'user'; ";
		$database = new database;
		$num_rows = $database->select($sql, null, 'column');
		if ($num_rows > 0) {
			//build the array
			$x=0;
			$array['default_settings'][$x]['default_setting_uuid'] = "38cf53d2-5fae-43ed-be93-33b0a5cc1c38";
			$array['default_settings'][$x]['default_setting_category'] = "users";
			$array['default_settings'][$x]['default_setting_subcategory'] = "unique";
			$x++;
			$array['default_settings'][$x]['default_setting_uuid'] = "e3f5f4cd-0f17-428a-b788-2f2db91b6dc7";
			$array['default_settings'][$x]['default_setting_category'] = "users";
			$array['default_settings'][$x]['default_setting_subcategory'] = "password_length";
			$x++;
			$array['default_settings'][$x]['default_setting_uuid'] = "51c106d9-9aba-436b-b9b1-ff4937cef706";
			$array['default_settings'][$x]['default_setting_category'] = "users";
			$array['default_settings'][$x]['default_setting_subcategory'] = "password_number";
			$x++;
			$array['default_settings'][$x]['default_setting_uuid'] = "f0e601b9-b619-4247-9624-c33605e96fd8";
			$array['default_settings'][$x]['default_setting_category'] = "users";
			$array['default_settings'][$x]['default_setting_subcategory'] = "password_lowercase";
			$x++;
			$array['default_settings'][$x]['default_setting_uuid'] = "973b6773-dac0-4041-844e-71c48fc9542c";
			$array['default_settings'][$x]['default_setting_category'] = "users";
			$array['default_settings'][$x]['default_setting_subcategory'] = "password_uppercase";
			$x++;
			$array['default_settings'][$x]['default_setting_uuid'] = "a6b6d9cc-fb25-4bc3-ad85-fa530d9b334d";
			$array['default_settings'][$x]['default_setting_category'] = "users";
			$array['default_settings'][$x]['default_setting_subcategory'] = "password_special";

			//add the temporary permission
			$p = new permissions;
			$p->add("default_setting_edit", 'temp');

			//save to the data
			$database = new database;
			$database->app_name = 'default_setting';
			$database->app_uuid = '2c2453c0-1bea-4475-9f44-4d969650de09';
			$database->save($array, false);
			unset($array);

			//remove the temporary permission
			$p->delete("default_setting_edit", 'temp');
		}

	//update the user_type when the value is null
		$sql = "select count(*) from v_users ";
		$sql .= "where user_type is null; ";
		$database = new database;
		$num_rows = $database->select($sql, null, 'column');
		if ($num_rows > 0) {
			$sql = "update v_users ";
			$sql .= "set user_type = 'default' ";
			$sql .= "where user_type is null;";
			$database = new database;
			$database->execute($sql, null);
		}

}

?>
