<?php

	require_once(TOOLKIT . '/class.administrationpage.php');
	
	class ContentExtensionOrder_EntriesSave extends AdministrationPage {
		
		public function __viewIndex() {
			$field_id = $_REQUEST["field"];
			$items = $_REQUEST['items'];
			
		    if(!is_array($items) || empty($items)) exit;

			foreach($items as $entry_id => $position) {
				$id = Symphony::Database()->fetchVar('id', 0, "SELECT id FROM tbl_entries_data_$field_id WHERE `entry_id` = '$entry_id' ORDER BY id ASC LIMIT 1");
				if (is_null($id)) {
					Symphony::Database()->query("INSERT INTO tbl_entries_data_$field_id (entry_id, value) VALUES ('$entry_id', '$position')");
				} else {
					Symphony::Database()->query("UPDATE tbl_entries_data_$field_id SET `value`='$position' WHERE `entry_id` = '$entry_id'");
					Symphony::Database()->query("DELETE FROM tbl_entries_data_$field_id WHERE `entry_id` = '$entry_id' AND `id` > '$id'");
				}
		    }
			
			exit;
		}
	}
	
?>