<?php

	require_once(TOOLKIT . '/class.jsonpage.php');

	Class contentExtensionOrder_entriesSave extends JSONPage{
		
		public function view(){
			$field_id = General::sanitize($_GET['field']);
			$items = $_GET['items'];

			if(!is_array($items) || empty($items)) {
				$this->_Result['error'] = __('No items provided');
				$this->generate();
			};

			/**
			 * Just prior to reordering entries
			 *
			 * @delegate EntryPreOrder
			 * @param string $context
			 * '/publish/'
			 * @param number $field_id
			 * @param array $items
			 */
			Symphony::ExtensionManager()->notifyMembers('EntriesPreOrder', '/publish/', array('field_id' => $field_id, 'items' => &$items));

			foreach($items as $entry_id => $position) {
				$id = Symphony::Database()->fetchVar('id', 0, "
					SELECT id
					FROM tbl_entries_data_$field_id
					WHERE `entry_id` = '$entry_id'
					ORDER BY id ASC LIMIT 1
				");

				if(is_null($id)) {
					Symphony::Database()->query("
						INSERT INTO tbl_entries_data_$field_id (entry_id, value)
						VALUES ('$entry_id', '$position')
					");
				}
				else {
					Symphony::Database()->query("
						UPDATE tbl_entries_data_$field_id
						SET `value`='$position'
						WHERE `entry_id` = '$entry_id'
					");
					Symphony::Database()->query("
						DELETE FROM tbl_entries_data_$field_id
						WHERE `entry_id` = '$entry_id'
						AND `id` > '$id'
					");
				}
		    }

		    /**
			 * After reordering entries
			 *
			 * @delegate EntryPostOrder
			 * @param string $context
			 * '/publish/'
			 * @param array $entry_id
			 */
			Symphony::ExtensionManager()->notifyMembers('EntriesPostOrder', '/publish/', array('entry_id' => array_keys($items)));
	
			$this->_Result['success'] = __('Sorting complete');
		}
		
	}
