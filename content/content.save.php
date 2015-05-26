<?php

	require_once(TOOLKIT . '/class.jsonpage.php');

	Class contentExtensionOrder_entriesSave extends JSONPage{
		
		public function view(){
			$field_id = General::sanitize($_GET['field']);
			$items = $_GET['items'];
			$filters = $_GET['filters'];

			if(!is_array($items) || empty($items)) {
				$this->_Result['error'] = __('No items provided');
				$this->generate();
			};

			$where = '';

			$field = FieldManager::fetch($field_id);
			$filterableFields = explode(',', $field->get('filtered_fields'));
			$section_id = $field->get('parent_section');

			if(!is_array($filters) && empty($filters)) {
				$filters = array();
			}

			//set change the field name with the field id for each filter
			if(!empty($filterableFields)) {

				// check if the filters are related to the entry order being saved
				foreach ($filters as $field_name => $value) {
					$filtered_field_id = FieldManager::fetchFieldIDFromElementName($field_name,$section_id);
					if (in_array($filtered_field_id, $filterableFields)){
						$filters[$filtered_field_id] = strtolower(General::sanitize($value));
					}
					unset($filters[$field_name]);
				}

				$where = $field->buildFilteringSQL($filters);
			}

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
					WHERE `entry_id` = '$entry_id' {$where}
					ORDER BY id ASC LIMIT 1
				");

				if(is_null($id)) {
					$fields = '';
					$values = '';

					//add the filtered params if available (default set to null)
					foreach ($filterableFields as $key => $filterable_field) {
						if (isset($filters[$filterable_field])){
							$fields .= " ,field_{$filterable_field}";
							$values .= " ,'{$filters[$filterable_field]}'";
						}
					}

					Symphony::Database()->query("
						INSERT INTO tbl_entries_data_$field_id (entry_id, value{$fields})
						VALUES ('$entry_id', '$position'{$values})
					");
				}
				else {
					Symphony::Database()->query("
						UPDATE tbl_entries_data_$field_id
						SET `value`='$position'
						WHERE `entry_id` = '$entry_id' {$where}
					");
					Symphony::Database()->query("
						DELETE FROM tbl_entries_data_$field_id
						WHERE `entry_id` = '$entry_id' {$where}
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
