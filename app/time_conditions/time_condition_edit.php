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
	Portions created by the Initial Developer are Copyright (C) 2008-2015
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('time_condition_add') || permission_exists('time_condition_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}
require_once "resources/header.php";
require_once "resources/paging.php";

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set the action as an add or an update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$dialplan_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//get the app uuid
	$app_uuid = check_str($_REQUEST["app_uuid"]);

//get the http post values and set them as php variables
	if (count($_POST) > 0) {
		$dialplan_name = check_str($_POST["dialplan_name"]);
		$dialplan_number = check_str($_POST["dialplan_number"]);
		$dialplan_order = check_str($_POST["dialplan_order"]);
		$dialplan_continue = check_str($_POST["dialplan_continue"]);
		$dialplan_details = $_POST["dialplan_details"];
		if (strlen($dialplan_continue) == 0) { $dialplan_continue = "false"; }
		$dialplan_context = check_str($_POST["dialplan_context"]);
		$dialplan_enabled = check_str($_POST["dialplan_enabled"]);
		$dialplan_description = check_str($_POST["dialplan_description"]);

		$action_1 = check_str($_POST["action_1"]);
		$action_1_array = explode(":", $action_1);
		$action_application_1 = array_shift($action_1_array);
		$action_data_1 = join(':', $action_1_array);

		$anti_action_1 = check_str($_POST["anti_action_1"]);
		$anti_action_1_array = explode(":", $anti_action_1);
		$anti_action_application_1 = array_shift($anti_action_1_array);
		$anti_action_data_1 = join(':', $anti_action_1_array);
	}

//prcoess the HTTP POST
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {
		
		if ($action == "update") {
			$dialplan_uuid = check_str($_POST["dialplan_uuid"]);
		}

		//check for all required data
			$msg = '';
			if (strlen($dialplan_name) == 0) { $msg .= $text['message-required'].$text['label-name']."<br>\n"; }
			if (strlen($dialplan_order) == 0) { $msg .= $text['message-required'].$text['label-order']."<br>\n"; }
			if (strlen($dialplan_continue) == 0) { $msg .= $text['message-required'].$text['label-continue']."<br>\n"; }
			if (strlen($dialplan_context) == 0) { $msg .= $text['message-required'].$text['label-context']."<br>\n"; }
			if (strlen($dialplan_enabled) == 0) { $msg .= $text['message-required'].$text['label-enabled']."<br>\n"; }
			//if (strlen($dialplan_description) == 0) { $msg .= $text['message-required'].$text['label-description']."<br>\n"; }
			if (strlen($msg) > 0 && strlen($_POST["persistformvar"]) == 0) {
				require_once "resources/header.php";
				require_once "resources/persist_form_var.php";
				echo "<div align='center'>\n";
				echo "<table><tr><td>\n";
				echo $msg."<br />";
				echo "</td></tr></table>\n";
				persistformvar($_POST);
				echo "</div>\n";
				require_once "resources/footer.php";
				return;
			}

		//remove the invalid characters from the dialplan name
			$dialplan_name = $_POST["dialplan_name"];
			$dialplan_name = str_replace(" ", "_", $dialplan_name);
			$dialplan_name = str_replace("/", "", $dialplan_name);

		//build the array
			if (strlen($row["dialplan_uuid"]) > 0) {
				$array['dialplan_uuid'] = $_POST["dialplan_uuid"];
			}
			if (isset($_POST["domain_uuid"])) {
				$array['domain_uuid'] = $_POST['domain_uuid'];
			}
			else {
				$array['domain_uuid'] = $_SESSION['domain_uuid'];
			}
			$array['dialplan_name'] = $dialplan_name;
			$array['dialplan_number'] = $_POST["dialplan_number"];
			$array['dialplan_context'] = $_POST["dialplan_context"];
			$array['dialplan_continue'] = $_POST["dialplan_continue"];
			$array['dialplan_order'] = $_POST["dialplan_order"];
			$array['dialplan_enabled'] = $_POST["dialplan_enabled"];
			$array['dialplan_description'] = $_POST["dialplan_description"];
			$x = 0;
			foreach ($_POST["dialplan_details"] as $row) {
				if (strlen($row["dialplan_detail_tag"]) > 0) {
					if (strlen($row["dialplan_detail_uuid"]) > 0) {
						$array['dialplan_details'][$x]['dialplan_detail_uuid'] = $row["dialplan_detail_uuid"];
					}
					$array['dialplan_details'][$x]['domain_uuid'] = $array['domain_uuid'];
					$array['dialplan_details'][$x]['dialplan_detail_tag'] = $row["dialplan_detail_tag"];
					$array['dialplan_details'][$x]['dialplan_detail_type'] = $row["dialplan_detail_type"];
					$array['dialplan_details'][$x]['dialplan_detail_data'] = $row["dialplan_detail_data"];
					$array['dialplan_details'][$x]['dialplan_detail_break'] =  $row["dialplan_detail_break"];
					$array['dialplan_details'][$x]['dialplan_detail_inline'] = $row["dialplan_detail_inline"];
					$array['dialplan_details'][$x]['dialplan_detail_group'] = $row["dialplan_detail_group"];
					$array['dialplan_details'][$x]['dialplan_detail_order'] = $row["dialplan_detail_order"];
				}
				$x++;
			}

		//add or update the database
			if ($_POST["persistformvar"] != "true") {
				$orm = new orm;
				$orm->name('dialplans');
				$orm->uuid($dialplan_uuid);
				$orm->save($array);
				//$message = $orm->message;
			}

		//clear the cache
			$cache = new cache;
			//$cache->delete("dialplan:".$dialplan_context);
			$cache->delete("dialplan:".$_SESSION["context"]);

		//synchronize the xml config
			save_dialplan_xml();

		//set the message
			if ($action == "add") {
				$_SESSION['message'] = $text['message-add'];
			}
			else if ($action == "update") {
				$_SESSION['message'] = $text['message-update'];
			}
			header("Location: time_condition_edit.php?id=".$dialplan_uuid.(($app_uuid != '') ? "&app_uuid=".$app_uuid : null));
			return;

	} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//get the information to pre-populate the form
	if (strlen($_GET['id']) > 0) {
		//get the dialplan
			if (count($_GET) > 0 && $_POST["persistformvar"] != "true") {
				$dialplan_uuid = $_GET["id"];
				$orm = new orm;
				$orm->name('dialplans');
				$orm->uuid($dialplan_uuid);
				$result = $orm->find()->get();
				//$message = $orm->message;
				foreach ($result as &$row) {
					$domain_uuid = $row["domain_uuid"];
					//$app_uuid = $row["app_uuid"];
					$dialplan_name = $row["dialplan_name"];
					$dialplan_number = $row["dialplan_number"];
					$dialplan_order = $row["dialplan_order"];
					$dialplan_continue = $row["dialplan_continue"];
					$dialplan_context = $row["dialplan_context"];
					$dialplan_enabled = $row["dialplan_enabled"];
					$dialplan_description = $row["dialplan_description"];
				}
				unset ($prep_statement);
			}

		//get the dialplan details in an array
			$sql = "select * from v_dialplan_details ";
			$sql .= "where dialplan_uuid = '$dialplan_uuid' ";
			$sql .= "order by dialplan_detail_group asc, dialplan_detail_order asc";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			$result_count = count($result);
			unset ($prep_statement, $sql);

		//create a new array that is sorted into groups and put the tags in order conditions, actions, anti-actions
			$x = 0;
			$details = '';
		//conditions
			foreach($result as $row) {
				if ($row['dialplan_detail_tag'] == "condition") {
					$group = $row['dialplan_detail_group'];
					foreach ($row as $key => $val) {
						$details[$group][$x][$key] = $val;
					}
				}
				$x++;
			}
		//regex
			foreach($result as $row) {
				if ($row['dialplan_detail_tag'] == "regex") {
					$group = $row['dialplan_detail_group'];
					foreach ($row as $key => $val) {
						$details[$group][$x][$key] = $val;
					}
				}
				$x++;
			}
		//actions
			foreach($result as $row) {
				if ($row['dialplan_detail_tag'] == "action") {
					$group = $row['dialplan_detail_group'];
					foreach ($row as $key => $val) {
						$details[$group][$x][$key] = $val;
					}
				}
				$x++;
			}
		//anti-actions
			foreach($result as $row) {
				if ($row['dialplan_detail_tag'] == "anti-action") {
					$group = $row['dialplan_detail_group'];
					foreach ($row as $key => $val) {
						$details[$group][$x][$key] = $val;
					}
				}
				$x++;
			}
			unset($result);

		//get the last action and anti-action
			foreach($details as $group) {
				foreach ($group as $row) {
					if ($row['dialplan_detail_tag'] == 'action') {
						//echo $row['dialplan_detail_tag']." ".$row['dialplan_detail_type'].":".$row['dialplan_detail_data']."\n";
						$detail_action = $row['dialplan_detail_type'].':'.$row['dialplan_detail_data'];
					}
					if ($row['dialplan_detail_tag'] == 'anti-action') {
						//echo $row['dialplan_detail_tag']." ".$row['dialplan_detail_type'].":".$row['dialplan_detail_data']."\n";
						$detail_anti_action = $row['dialplan_detail_type'].':'.$row['dialplan_detail_data'];
					}
				}
			}

		//blank row
			foreach($details as $group => $row) {
				//set the array key for the empty row
					$x = "999";
				//get the highest dialplan_detail_order
					foreach ($row as $key => $field) {
						$dialplan_detail_order = 0;
						if ($dialplan_detail_order < $field['dialplan_detail_order']) {
							$dialplan_detail_order = $field['dialplan_detail_order'];
						}
					}
				//increment the highest order by 5
					$dialplan_detail_order = $dialplan_detail_order + 10;
				//set the rest of the empty array
					//$details[$group][$x]['domain_uuid'] = '';
					//$details[$group][$x]['dialplan_uuid'] = '';
					$details[$group][$x]['dialplan_detail_tag'] = '';
					$details[$group][$x]['dialplan_detail_type'] = '';
					$details[$group][$x]['dialplan_detail_data'] = '';
					$details[$group][$x]['dialplan_detail_break'] = '';
					$details[$group][$x]['dialplan_detail_inline'] = '';
					$details[$group][$x]['dialplan_detail_group'] = $group;
					$details[$group][$x]['dialplan_detail_order'] = $dialplan_detail_order;
					$details[$group][$x]['preset'] = 'false';
			}
	}

//get the presets
	foreach ($_SESSION['time_conditions']['preset'] as $json) {
		$presets[] = json_decode($json, true);
	}

//get the time array from the dialplan set it as array dialplan_times
	$x = 0;
	foreach($details as $detail_group) {
		foreach ($detail_group as $row) {
			if ($row['dialplan_detail_tag'] == 'condition') {
				$type = $row['dialplan_detail_type'];
				$data = $row['dialplan_detail_data'];
				$group = $row['dialplan_detail_group'];
				//echo "type: ".$type. " data: ".$data."<br />\n";
				$array = explode(',', 'year,mon,mday,wday,yday,week,mweek,hour,minute,minute-of-day,time-of-day,date-time');
				if (in_array($type, $array)) {
					$dialplan_times[$group][$type] = $data;
					$dialplan_times[$group]['group'] = $group;
				}
			}
		}
		$x++;
	}

//get the preset_times
	$p = 0;
	foreach ($presets as $preset_number => $preset) {
		foreach ($preset as $preset_name => $preset_variables) {
			$preset_times[] = $preset_variables['variables'];
		}
	}

//add a function to check the time to see if its is a preset time
	function is_preset($presets, $times) {
		if ($_GET['debug'] == 'true') {
			echo "<p style='background-color: #E8D8C1;'>\n";
			echo "<br />\n";
			echo "<br />\n";
		}
		$preset_keys = array();
		foreach ($presets as $row) {
			if ($_GET['debug'] == 'true') {
				echo "<table>\n";
			}
			$match = true;
			foreach ($row as $k => $v) {
				if ($_GET['debug'] == 'true') {
					echo "<tr>\n";
					echo "<td>".$k."</td><td>".$row[$k]."</td><td>".$k."</td><td>".$times[$k]."</td>";
				}
				if ($row[$k] == $times[$k]) {
					if ($_GET['debug'] == 'true') { echo "<td>match</td>"; }
				}
				else {
					if ($_GET['debug'] == 'true') { echo "<td>no match</td>"; }
					$match = false;
				}
				if ($_GET['debug'] == 'true') {
					echo "</tr>\n";
				}
			}
			if ($_GET['debug'] == 'true') {
				echo "</table>\n";
				echo "<br />\n";
			}
			if ($match) { return true; }
		}
		return false;
	}

//set preset to true or false on dialplan_times array
	$x = 0;
	foreach ($dialplan_times as $times) {
		if ($_GET['debug'] == 'true') {
			echo "<pre>\n";
			print_r($times);
			echo "</pre>\n";
			echo "<hr>\n";
		}
		$g = $times['group'];
		if (is_preset($preset_times, $times)) {
			$dialplan_times[$g]['preset'] = 'true';
		}
		else {
			$dialplan_times[$g]['preset'] = 'false';
		}
		$x++;
	}

//show the results
	if ($_GET['debug'] == 'true') {
		echo "<pre>\n";
		print_r($dialplan_times);
		echo "</pre>\n";
	}

?>

<script type="text/javascript">
	<?php
	$time_condition_vars["year"] = $text['label-year'];
	$time_condition_vars["mon"] = $text['label-month'];
	$time_condition_vars["mday"] = $text['label-day-of-month'];
	$time_condition_vars["wday"] = $text['label-day-of-week'];
	//$time_condition_vars["yday"] = $text['label-day-of-year'];
	$time_condition_vars["week"] = $text['label-week-of-year'];
	$time_condition_vars["mweek"] = $text['label-week-of-month'];
	$time_condition_vars["hour"] = $text['label-hour-of-day'];
	$time_condition_vars["minute"] = $text['label-minute-of-hour'];
	//$time_condition_vars["minute-of-day"] = $text['label-minute-of-day'];
	$time_condition_vars["time-of-day"] = $text['label-time-of-day'];
	$time_condition_vars["date-time"] = $text['label-date-and-time'];
	?>
	function hide_var_options(row_num) {
		<?php
		foreach ($time_condition_vars as $var_name => $var_label) {
			echo "document.getElementById('var_".$var_name."_options_' + row_num).style.display = 'none';\n";
		}
		?>
	}

	function show_var_option(row_num, var_name) {
		if (var_name != '') { document.getElementById('var_' + var_name + '_options_' + row_num).style.display = ''; }
	}

	function toggle_var_stops(row_num, scope) {
		display = (scope == 'range') ? '' : 'none';
		<?php
		foreach ($time_condition_vars as $var_name => $var_label) {
			echo "document.getElementById('".$var_name."_' + row_num + '_stop').style.display = display;\n";
		}
		?>
	}
</script>

<?php

//show the content
	echo "<form method='post' name='frm' action=''>\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td align='left' valign='top'>\n";
	echo "			<span class='title'>".$text['title-time-condition-add']."</span><br />\n";
	echo "		</td>\n";
	echo "		<td align='right' valign='top'>\n";
	echo "			<input type='button' class='btn' name='' alt='back' onclick=\"window.location='".PROJECT_PATH."/app/dialplan/dialplans.php?app_uuid=4b821450-926b-175a-af93-a03c441818b1'\" value='".$text['button-back']."'>\n";
	echo "			<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<td align='left' colspan='2'>\n";
	echo "			<span class='vexpl'>\n";
	echo "			".$text['description-time-condition-add']."\n";
	echo "			</span>\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>";

	echo "<br />\n";
	echo "<br />\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='20%' class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-name']."\n";
	echo "</td>\n";
	echo "<td width='80%' class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='dialplan_name' maxlength='255' value=\"$dialplan_name\">\n";
	echo "	<br />\n";
	echo "	".$text['description-name']."\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-extension']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='dialplan_number' id='dialplan_number' maxlength='255' value=\"$dialplan_number\">\n";
	echo "	<br />\n";
	echo "	".$text['description-extension']."<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-conditions']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	//define select box options for each time condition variable (where appropriate)
	for ($y = date('Y'); $y <= (date('Y') + 10); $y++) { $var_option_select['year'][$y] = $y; } //years
	for ($m = 1; $m <= 12; $m++) { $var_option_select['mon'][$m] = date('F', strtotime('2015-'.number_pad($m,2).'-01')); } //month names
	for ($d = 1; $d <= 366; $d++) { $var_option_select['yday'][$d] = $d; } //days of year
	for ($d = 1; $d <= 31; $d++) { $var_option_select['mday'][$d] = $d; } //days of month
	for ($d = 1; $d <= 7; $d++) { $var_option_select['wday'][$d] = date('l', strtotime('Sunday +'.($d-1).' days')); } //week days
	for ($w = 1; $w <= 53; $w++) { $var_option_select['week'][$w] = $w; } //weeks of year
	for ($w = 1; $w <= 5; $w++) { $var_option_select['mweek'][$w] = $w; } //weeks of month
	for ($h = 0; $h <= 23; $h++) { $var_option_select['hour'][$h] = (($h) ? (($h >= 12) ? (($h == 12) ? $h : ($h-12)).' PM' : $h.' AM') : '12 AM'); } //hours of day
	for ($m = 0; $m <= 59; $m++) { $var_option_select['minute'][$m] = number_pad($m,2); } //minutes of hour
	//output condition fields
	echo "	<table border='0' cellpadding='2' cellspacing='0' style='margin: -2px;'>\n";
	echo "		<tr>\n";
	echo "			<td class='vtable'>".$text['label-condition_parameter']."</td>\n";
	echo "			<td class='vtable'>".$text['label-condition_scope']."</td>\n";
	echo "			<td class='vtable'>".$text['label-condition_values']."</td>\n";
	echo "			<td></td>\n";
	echo "		</tr>\n";

	$x = 0;
	foreach($details as $detail_group) {
		foreach ($detail_group as $row) {
			if ($row['dialplan_detail_tag'] == 'condition') {
				$type = $row['dialplan_detail_type'];
				$data = $row['dialplan_detail_data'];
				$group = $row['dialplan_detail_group'];

				if ($dialplan_times[$group]['preset'] == 'false' && $type != "destination_number") {
					$domain_uuid = $row['domain_uuid'];
					$dialplan_uuid = $row['dialplan_uuid'];
					$dialplan_detail_uuid = $row['dialplan_detail_uuid'];
					$dialplan_detail_tag = $row['dialplan_detail_tag'];
					$dialplan_detail_type = $row['dialplan_detail_type'];
					$dialplan_detail_data = $row['dialplan_detail_data'];
					$dialplan_detail_break = $row['dialplan_detail_break'];
					$dialplan_detail_inline = $row['dialplan_detail_inline'];
					$dialplan_detail_group = $row['dialplan_detail_group'];
					$dialplan_detail_order = $row['dialplan_detail_order'];

					//add the primary key uuid
					if (strlen($dialplan_detail_uuid) > 0) {
						echo "	<input name='dialplan_details[".$x."][dialplan_detail_uuid]' type='hidden' value=\"".$dialplan_detail_uuid."\">\n";
					}

					//start a new row
					echo "	<tr>\n";

					//time condition
					echo "		<td>\n";
					echo "			<select class='formfld' name='dialplan_details[".$x."][dialplan_detail_type' id='variable_".$x."' onchange=\"hide_var_options('".$x."'); show_var_option('".$x."', this.options[this.selectedIndex].value);\">\n";
					echo "				<option value=''></option>\n";
					foreach ($time_condition_vars as $var_name => $var_label) {
						if ($var_name == $dialplan_detail_type) {
							echo "				<option value='".$var_name."' selected='selected'>".$var_label."</option>\n";
						}
						else {
							echo "				<option value='".$var_name."'>".$var_label."</option>\n";
						}
					}
					echo "			</select>\n";
					echo "		</td>\n";

					//single or range
					echo "		<td>\n";
					echo "			<select class='formfld' name='dialplan_details[".$x."][scope]' id='scope_".$x."' onchange=\"toggle_var_stops('".$x."', this.options[this.selectedIndex].value);\">\n";
					$detail_data = explode("-", $dialplan_detail_data);
					if (count($detail_data) == 1) {
						echo "				<option value='single' selected='selected'>Single</option>\n";
						echo "				<option value='range'>Range</option>\n";
					}
					else {
						//$dialplan_detail_data
						echo "				<option value='single'>Single</option>\n";
						echo "				<option value='range' selected='selected'>Range</option>\n";
					}
					echo "			</select>\n";
					echo "		</td>\n";

					//$dialplan_detail_data
					echo "		<td>\n";
					//echo $dialplan_detail_type." ".$detail_data[0]." :: ".$detail_data[1]."<br />";
					//foreach ($time_condition_vars as $var_name => $var_label) {
						//switch ($var_name) {
						switch ($dialplan_detail_type) {
							case "minute-of-day" :
								echo "<span id='var_minute-of-day_options_".$x."' style='display: none;'>\n";
								echo "	<input type='number' class='formfld' style='width: 50px; min-width: 50px; max-width: 50px;' name='dialplan_details[".$x."][minute-of-day][start]' id='minute-of-day_".$x."_start' value='".$detail_data[0]."'>\n";
								if (strlen($detail_data[1]) > 0) {
									echo "	<span id='minute-of-day_".$x."_stop' style='display: inline;'>\n";
									echo "		&nbsp;<strong>~</strong>&nbsp;\n";
									echo "		<input type='number' class='formfld' style='width: 50px; min-width: 50px; max-width: 50px;' name='dialplan_details[".$x."][minute-of-day][stop]' value='".$detail_data[1]."'>\n";
									echo "	</span>\n";
								}
								echo "</span>\n";
								break;
							case "time-of-day" :
								echo "<span id='var_time-of-day_options_".$x."' style='display: inline;'>\n";
								echo "	<select class='formfld' name='dialplan_details[".$x."][time-of-day][start][hour]' id='time-of-day_".$x."_start_hour' onchange=\"if (document.getElementById('time-of-day_".$x."_start_minute').selectedIndex == 0) { document.getElementById('time-of-day_".$x."_start_minute').selectedIndex = 1; } if (document.getElementById('time-of-day_".$x."_stop_hour').selectedIndex == 0) { document.getElementById('time-of-day_".$x."_stop_hour').selectedIndex = this.selectedIndex; document.getElementById('time-of-day_".$x."_stop_minute').selectedIndex = 1; }\">\n";
								echo "		<option value=''>Hour</option>\n";
								for ($h = 1; $h <= 12; $h++) {
									if ($detail_data[0] == $h) {
										echo "	<option value='".$h."' selected='selected'>".$h."</option>\n";
									}
									else {
										echo "	<option value='".$h."'>".$h."</option>\n";
									}
								}
								echo "	</select>\n";
								echo "	<select class='formfld' name='dialplan_details[".$x."][time-of-day][start][minute]' id='time-of-day_".$x."_start_minute' onchange=\"if (document.getElementById('time-of-day_".$x."_stop_minute').selectedIndex == 0) { document.getElementById('time-of-day_".$x."_stop_minute').selectedIndex = this.selectedIndex; }\">\n";
								echo "		<option value='00'>Minute</option>\n";
								for ($m = 0; $m < 60; $m++) {
									if ($detail_data[1] == $m) {
										echo "	<option value='".number_pad($m,2)."' selected='selected'>".number_pad($m,2)."</option>\n";
									}
									else {
										echo "	<option value='".number_pad($m,2)."'>".number_pad($m,2)."</option>\n";
									}
								}
								echo "	</select>\n";
								echo "	<select class='formfld' name='dialplan_details[".$x."][time-of-day][start][notation]' id='time-of-day_".$x."_start_notation'>\n";
								echo "		<option value='AM'>AM</option>\n";
								echo "		<option value='PM'>PM</option>\n";
								echo "	</select>\n";
								if (strlen($detail_data[1]) > 0) {
									echo "	<span id='time-of-day_".$x."_stop' style='display: inline;'>\n";
									echo "		&nbsp;~&nbsp;";
									echo "		<select class='formfld' name='dialplan_details[".$x."][time-of-day][stop][hour]' id='time-of-day_".$x."_stop_hour' onchange=\"if (document.getElementById('time-of-day_".$x."_stop_minute').selectedIndex == 0) { document.getElementById('time-of-day_".$x."_stop_minute').selectedIndex = 1; }\">\n";
									echo "			<option value=''>Hour</option>\n";
									for ($h = 1; $h <= 12; $h++) {
										echo "		<option value='".$h."'>".$h."</option>\n";
									}
									echo "		</select>\n";
									echo "		<select class='formfld' name='dialplan_details[".$x."][time-of-day][stop][minute]' id='time-of-day_".$x."_stop_minute'>\n";
									echo "			<option value='00'>Minute</option>\n";
									for ($m = 0; $m < 60; $m++) {
										if ($detail_data[1] == $m) {
											echo "	<option value='".number_pad($m,2)."' selected='selected'>".number_pad($m,2)."</option>\n";
										}
										else {
											echo "	<option value='".number_pad($m,2)."'>".number_pad($m,2)."</option>\n";
										}
									}
									echo "		</select>\n";
									echo "		<select class='formfld' name='dialplan_details[".$x."][time-of-day][stop][notation]' id='time-of-day_".$x."_stop_notation'>\n";
									echo "			<option value='AM'>AM</option>\n";
									echo "			<option value='PM'>PM</option>\n";
									echo "		</select>\n";
									echo "	</span>\n";
								}
								echo "</span>\n";
								break;
							case "date-time" :
								echo "<span id='var_date-time_options_".$x."' style='display: none;'>\n";
								echo "	<input type='text' class='formfld' style='min-width: 115px; max-width: 115px;' data-calendar=\"{format: '%Y-%m-%d %H:%M', listYears: true, hideOnPick: true, fxName: null, showButtons: true}\" name='dialplan_details[".$x."][date-time][start]' value='".$detail_data[1]."' id='date-time_".$x."_start'>\n";
								if (strlen($detail_data[1]) > 0) {
									echo "	<span id='date-time_".$x."_stop' style='display: inline;'>\n";
									echo "		&nbsp;<strong>~</strong>&nbsp;\n";
									echo "		<input type='text' class='formfld' style='min-width: 115px; max-width: 115px;' data-calendar=\"{format: '%Y-%m-%d %H:%M', listYears: true, hideOnPick: true, fxName: null, showButtons: true}\" name='dialplan_details[".$x."][date-time][stop]'>\n";
									echo "	</span>\n";
								}
								echo "</span>\n";
								break;
							default:
								$var_name = $dialplan_detail_type;
								echo "<span id='var_".$var_name."_options_".$x."' style='display: inline;'>\n";
								echo "	<select class='formfld' name='dialplan_details[".$x."][".$var_name."][start]' id='".$var_name."_".$x."_start' onchange=\"if (document.getElementById('".$var_name."_".$x."_stop').selectedIndex == 0) { document.getElementById('".$var_name."_".$x."_stop').selectedIndex = this.selectedIndex; }\">\n";
								foreach ($var_option_select[$var_name] as $var_option_select_value => $var_option_select_label) {
									if ($var_option_select_value == $detail_data[0]) {
										echo "	<option value='".$var_option_select_value."' selected='selected'>".$var_option_select_label."</option>\n";
									}
									else {
										echo "	<option value='".$var_option_select_value."'>".$var_option_select_label."</option>\n";
									}
								}
								echo "	</select>\n";
								if (strlen($detail_data[1]) > 0) {
									echo "	<span id='".$var_name."_".$x."_stop' style='display: inline;'>\n";
									echo "		&nbsp;<strong>~</strong>&nbsp;\n";
									echo "		<select class='formfld' name='dialplan_details[".$x."][".$var_name."][stop]' id='".$var_name."_".$x."_stop-real'>\n";
									echo "			<option value=''></option>\n";
									foreach ($var_option_select[$var_name] as $var_option_select_value => $var_option_select_label) {
										if ($var_option_select_value == $detail_data[1]) {
											echo "		<option value='".$dialplan_detail_data."' selected='selected'>".$var_option_select_label."</option>\n";
										}
										else {
											echo "		<option value='".$dialplan_detail_data."'>".$var_option_select_label."</option>\n";
										}
									}
									echo "		</select>\n";
								}
								echo "	</span>\n";
								echo "</span>\n";
						}
					//}

					echo "		</td>\n";
					echo "	</tr>\n";
				} // if (in_array($type, $array))
			} // if ($row['dialplan_detail_tag'] == 'condition')
		} // foreach ($detail_group as $row)
		//echo "<tr><td>&nbsp;</td></tr>\n";
		$x++;
	} // foreach($details as $detail_group)

	echo "	</table>\n";
	if ($action == 'add') {
		echo "<script>\n";
		//set field values
		echo "	document.getElementById('variable_1').selectedIndex = 4;\n"; //day of week
		echo "	document.getElementById('scope_1').selectedIndex = 1;\n"; //range
		echo "	document.getElementById('wday_1_start').selectedIndex = 1;\n"; //monday
		echo "	document.getElementById('wday_1_stop-real').selectedIndex = 6;\n"; //friday
		echo "	document.getElementById('variable_2').selectedIndex = 7;\n"; //hour of day
		echo "	document.getElementById('scope_2').selectedIndex = 1;\n"; //range
		echo "	document.getElementById('hour_2_start').selectedIndex = 8;\n"; //8am
		echo "	document.getElementById('hour_2_stop-real').selectedIndex = 18;\n"; //5pm
		//display fields
		echo "	document.getElementById('var_wday_options_1').style.display = '';\n";
		echo "	document.getElementById('wday_1_stop').style.display = '';\n";
		echo "	document.getElementById('var_hour_options_2').style.display = '';\n";
		echo "	document.getElementById('hour_2_stop').style.display = '';\n";
		echo "</script>\n";
	}
	echo "	".$text['description-conditions']."<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	//show the presets
	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-presets']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";

	//echo "<pre>"; print_r($presets); echo "<pre><br><br>";
	echo "	<table cellpadding='0' cellspacing='15' border='0' style='margin: -15px;'>\n";
	echo "		<tr>\n";
	echo "			<td class='vtable' style='border: none; padding: 0px; vertical-align: top; white-space: nowrap;'>\n";
	$preset_count = sizeof($presets);
	$presets_per_column = ceil($preset_count / 3);
	$p = 0;
	foreach ($presets as $preset_number => $preset) {
		foreach ($preset as $preset_name => $preset_variables) {
			$preset_times = $preset_variables['variables'];
			foreach ($dialplan_times as $row) {
				$array = explode(',', 'year,mon,mday,wday,yday,week,mweek,hour,minute,minute-of-day,time-of-day,date-time');
				if ($_GET['debug'] == 'true') {
					echo "<table border='0' cellpadding='3' cellspacing='0'>\n";
					echo "<tr>\n";
					echo "	<th>database</th>\n";
					echo "	<th>value</th>\n";
					echo "	<th>presets</th>\n";
					echo "	<th>value</th>\n";
					echo "	<th>match</th>\n";
					echo "</tr>\n";
				}
				$y = 0;
				$match = true;
				foreach ($array as $k) {
					if (strlen($preset_times[$k]) > 0) {
						if ($_GET['debug'] == 'true') {
							echo "<tr>\n";
							echo "<td>$k</td><td>".$row[$k]."&nbsp;</td><td>$k</td><td>".$preset_times[$k]."&nbsp;</td>";
						}
						if ($row[$k] == $preset_times[$k]) {
							if ($_GET['debug'] == 'true') {
								echo "<td>true</td>\n";
							}
						}
						else {
							if ($_GET['debug'] == 'true') {
								echo "<td>false</td>\n";
							}
							$match = false;
						}
						if ($_GET['debug'] == 'true') {
							echo "</tr>\n";
						}
						$y++;
					}
				}
				if ($_GET['debug'] == 'true') {
					echo "</table><br />";
				}
				if ($match) {
					break;
				}
			}

			if ($match) { $checked = "checked='checked'"; } else { $checked = ''; }
			echo "<label for='preset_".$preset_number."'><input type='checkbox' name='preset[]' $checked id='preset_".$preset_number."' value='".$preset_number."'> ".$text['label-preset_'.$preset_name]."</label><br>\n";
			$p++;
			if ($p == $presets_per_column) {
				echo "	</td>";
				echo "	<td class='vtable' style='border: none; padding: 0px; vertical-align: top; white-space: nowrap;'>\n";
				$p = 0;
			}
		}
	}
	echo "			</td>\n";
	echo "		</tr>\n";
	echo "	</table>\n";
	echo "	<br />\n";
	echo "	".$text['description-presets']."<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	//add destinations
	if (strlen($_GET['id']) == 0) {
				echo "<tr>\n";
				echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
				echo "    ".$text['label-action']."\n";
				echo "</td>\n";
				echo "<td class='vtable' align='left'>\n";
				//switch_select_destination(select_type, select_label, select_name, select_value, select_style, $action);
				switch_select_destination("dialplan", $action_1, "action_1", $action_1, "", "");
				echo "</td>\n";
				echo "</tr>\n";

				echo "<tr>\n";
				echo "<td class='vncell' valign='top' align='left' nowrap>\n";
				echo "    ".$text['label-action-alternate']."\n";
				echo "</td>\n";
				echo "<td class='vtable' align='left'>\n";
				//switch_select_destination(select_type, select_label, select_name, select_value, select_style, $action);
				switch_select_destination("dialplan", $anti_action_1, "anti_action_1", $anti_action_1, "", "");
				echo "</td>\n";
				echo "</tr>\n";
	}

	//edit destinations
	$x = 0;
	foreach($details as $group) {
		foreach ($group as $row) {
			if ($row['dialplan_detail_tag'] == 'action' && $row['dialplan_detail_type'] != 'set') {
				echo "<tr>\n";
				echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
				echo "    ".$text['label-action']."\n";
				echo "</td>\n";
				echo "<td class='vtable' align='left'>\n";
				//switch_select_destination(select_type, select_label, select_name, select_value, select_style, $action);
				$data = $row['dialplan_detail_data'];
				$label = explode("XML", $data);
				$divider = ($row['dialplan_detail_type'] != '') ? ":" : null;
				$detail_action = $row['dialplan_detail_type'].$divider.$row['dialplan_detail_data'];
				switch_select_destination("dialplan", $label[0], "dialplan_details[".$x."][action]", $detail_action, "width: 60%;", 'action');
				echo "</td>\n";
				echo "</tr>\n";
			}
			if ($row['dialplan_detail_tag'] == 'anti-action' && $row['dialplan_detail_type'] != 'set') {
				echo "<tr>\n";
				echo "<td class='vncell' valign='top' align='left' nowrap>\n";
				echo "    ".$text['label-action-alternate']."\n";
				echo "</td>\n";
				echo "<td class='vtable' align='left'>\n";
				//switch_select_destination(select_type, select_label, select_name, select_value, select_style, $action);
				$label = explode("XML", $row['dialplan_detail_data']);
				$divider = ($row['dialplan_detail_type'] != '') ? ":" : null;
				$detail_action = $row['dialplan_detail_type'].$divider.$row['dialplan_detail_data'];
				switch_select_destination("dialplan", $label[0], "dialplan_details[".$x."][anti_action]", $detail_action, "width: 60%;", 'action');
				echo "</td>\n";
				echo "</tr>\n";
			}
		}
		$x++;
	}

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-order']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select name='dialplan_order' class='formfld'>\n";
	$i = 300;
	while($i <= 999) {
		$selected = ($dialplan_order == $i) ? "selected" : null;
		if (strlen($i) == 1) { echo "<option value='00$i' ".$selected.">00$i</option>\n"; }
		if (strlen($i) == 2) { echo "<option value='0$i' ".$selected.">0$i</option>\n"; }
		if (strlen($i) == 3) { echo "<option value='$i' ".$selected.">$i</option>\n"; }
		$i = $i + 10;
	}
	echo "	</select>\n";
	echo "	<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='dialplan_enabled'>\n";
	if ($dialplan_enabled == "true") {
		echo "    <option value='true' selected='selected'>".$text['label-true']."</option>\n";
	}
	else {
		echo "    <option value='true'>".$text['label-true']."</option>\n";
	}
	if ($dialplan_enabled == "false") {
		echo "    <option value='false' selected='selected'>".$text['label-false']."</option>\n";
	}
	else {
		echo "    <option value='false'>".$text['label-false']."</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-description']."\n";
	echo "</td>\n";
	echo "<td colspan='4' class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='dialplan_description' maxlength='255' value=\"$dialplan_description\">\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>\n";
	echo "<br><br>";

	echo "<div align='right'>\n";
	if ($action == "update") {
		echo "	<input type='hidden' name='dialplan_uuid' value='$dialplan_uuid'>\n";
	}
	echo "	<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "</div>";

	echo "</form>";
	echo "<br><br>";

//include the footer
	require_once "resources/footer.php";

?>