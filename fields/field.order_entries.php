<?php

	Class fieldOrder_Entries extends Field{

		const SIMPLE = 0;
		const REGEXP = 1;
		const RANGE = 3;
		const ERROR = 4;

		function __construct() {
			parent::__construct();

			$this->_name = __('Entry Order');
			$this->_required = false;

			$this->set('hide', 'no');
			$this->set('location', 'sidebar');
		}

		function isSortable() {
			return true;
		}

		function canFilter() {
			return true;
		}

		function allowDatasourceOutputGrouping() {
			return true;
		}

		function allowDatasourceParamOutput() {
			return true;
		}

		function canPrePopulate() {
			return true;
		}

		function processRawFieldData($data, &$status, &$message = null, $simulate = false, $entry_id = null) {
			$status = self::__OK__;
			$increment_subsequent_order = false;
			
			if ($entry_id != null) {
				$entry_id = General::intval($entry_id);
			}

			if (is_array($data)){
				//TODO Auto Increment for filtered ordering for now just return the data as it is already properly formatted
				return $data;
			} 

			if($entry_id) {
				$new_value = $data;
				$current_value = Symphony::Database()->fetchVar("value", 0, "
					SELECT value
					FROM tbl_entries_data_{$this->get('id')}
					WHERE entry_id=".$entry_id."
					LIMIT 1
				");

				if(isset($current_value) && $current_value !== $new_value) {
					$increment_subsequent_order = true;
				}
			}
			else {
				$increment_subsequent_order = true;
			}

			if($increment_subsequent_order && !empty($data)) {
				Symphony::Database()->query("UPDATE tbl_entries_data_{$this->get('id')} SET value = (value + 1) WHERE value >= ".$data);
			}

			return array(
				'value' => $data
			);
		}

		function groupRecords($records) {
			if(!is_array($records) || empty($records)) return;

			$groups = array($this->get('element_name') => array());

			foreach($records as $r) {
				$data = $r->getData($this->get('id'));

				$value = $data['value'];

				if(!isset($groups[$this->get('element_name')][$value])) {
					$groups[$this->get('element_name')][$value] = array(
						'attr' => array('value' => $value),
						'records' => array(),
						'groups' => array()
					);
				}

				$groups[$this->get('element_name')][$value]['records'][] = $r;
			}

			return $groups;
		}

		function displaySettingsPanel(&$wrapper, $errors = null) {
			parent::displaySettingsPanel($wrapper, $errors);

			$order = $this->get('sortorder');

			$div = new XMLElement('div', null, array('class' => 'two columns'));

			$label = Widget::Label();
			$label->setAttribute('class', 'column');
			$input = Widget::Input("fields[{$order}][force_sort]", 'yes', 'checkbox');

			if($this->get('force_sort') == 'yes') {
				$input->setAttribute('checked', 'checked');
			}

			$label->setValue(__('%s Force manual sorting', array($input->generate())));
			$div->appendChild($label);

			$label = Widget::Label();
			$label->setAttribute('class', 'column');
			$input = Widget::Input("fields[{$order}][disable_pagination]", 'yes', 'checkbox');

			if($this->get('disable_pagination') == 'yes') {
				$input->setAttribute('checked', 'checked');
			}

			$label->setValue(__('%s Disable Pagination', array($input->generate())));
			$div->appendChild($label);
			$wrapper->appendChild($div);

			// Display options
			$fieldset = new XMLElement('fieldset');
			$div = new XMLElement('div', null, array('class' => 'two columns'));

			$label = Widget::Label();
			$label->setAttribute('class', 'column');
			$input = Widget::Input("fields[{$order}][hide]", 'yes', 'checkbox');

			if($this->get('hide') == 'yes') {
				$input->setAttribute('checked', 'checked');
			}

			$label->setValue(__('%s Hide on publish page', array($input->generate())));
			$div->appendChild($label);

			$this->appendShowColumnCheckbox($div);
			$fieldset->appendChild($div);
			$wrapper->appendChild($fieldset);

			//filtered orders

			$fieldset = new XMLElement('fieldset');

			$div = new XMLElement('h3', __('Filtered Ordering'));
			$fieldset->appendChild($div);

			$section = SectionManager::fetch($this->get('parent_section'));
			if (!is_object($section)){
				// you need to save first
				$div = new XMLElement('p', __('You have to save this field before you can add filtered ordering'));
				$fieldset->appendChild($div);
			} else {
				$fields = $section->fetchFields();

				$options = array();

				$filteredFields = $this->get('filtered_fields');
				if (!is_array($filteredFields)){
					$filteredFields = explode(',', $filteredFields);
				}

				if (is_array($fields)) {
					foreach ($fields as $field) {
						$selected = in_array($field->get('id'), $filteredFields);
						$options[] = array($field->get('id'),$selected,$field->get('label'));
					}
				}

				$label = Widget::Label(__('Fields'));
				$label->appendChild(
					Widget::Select("fields[{$order}][filtered_fields][]", $options, array(
						'multiple' => 'multiple',
						'data-required' => 'false'
					))
				);

				$text = new XMLElement('p', __('Filtered Ordering is an advanced use case for Order Entries. Refer to the readme for further details. Do not select any field unless you understand what it entails as it might lead to unexpected results.'), array('class' => 'help'));

				$fieldset->appendChild($label);
				$fieldset->appendChild($text);

			}
			
			$wrapper->appendChild($fieldset);


		}

		private function updateFilterTable(){
			$filteredFields = $this->get('filtered_fields');
			if (!is_array($filteredFields)){
				$filteredFields = explode(',', $filteredFields);
			}

			$orderFieldId = $this->get('id');

			// fetch existing table schema
			$currentFilters = Symphony::Database()->fetchCol('Field',"SHOW COLUMNS FROM tbl_entries_data_{$orderFieldId} WHERE Field like 'field_%';");
						
			//change the value format to match the filtered fields stored
			foreach ($currentFilters as $key => $value) {
				$currentFilter = substr($value, 6);
				if (!empty($currentFilter)) {
					$currentFilters[$key] = $currentFilter;
				} else {
					unset($currentFilters[$key]);
				}
			}

			$newFilters = array_filter(array_diff($filteredFields, $currentFilters));
			$removedFilters = array_filter(array_diff($currentFilters, $filteredFields));

			foreach ($removedFilters as $key => $field_id) {
				Symphony::Database()->query("ALTER TABLE `tbl_entries_data_{$orderFieldId}` DROP COLUMN `field_{$field_id}`");
			}

			foreach ($newFilters as $key => $field_id) {
				//maybe in the future fields can give supported filters until then using a varchar for flexibility
				$fieldtype = "varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL";
				Symphony::Database()->query("ALTER TABLE `tbl_entries_data_{$orderFieldId}` ADD COLUMN `field_{$field_id}`{$fieldtype}");
			}

			if (!empty($newFilters) || !empty($removedFilters)){
				$fields = '';
				foreach ($filteredFields as $field_id) {
					$fields .= ",`field_{$field_id}` ";
				}
				try {
					Symphony::Database()->query("ALTER TABLE `tbl_entries_data_{$orderFieldId}` DROP INDEX `unique`;");
				} catch (Exception $ex) {
					// ignore. This can fail is not index exists.
					// See #73
				}
				if (!empty($fields)) {
					Symphony::Database()->query("ALTER TABLE `tbl_entries_data_{$orderFieldId}` ADD UNIQUE `unique`(`entry_id` {$fields});");
				}
			}
		}

		function commit() {
			if(!parent::commit()) {
				return false;
			}

			$id = $this->get('id');
			if($id === false) {
				return false;
			}

			$filteredFields = $this->get('filtered_fields');
			if (!isset($filteredFields))
				$filteredFields = array();

			$fields = array();

			$fields['field_id'] = $id;
			$fields['force_sort'] = $this->get('force_sort');
			$fields['disable_pagination'] = $this->get('disable_pagination');
			$fields['filtered_fields'] = implode(',', $filteredFields);
			$fields['hide'] = $this->get('hide');

			// Update section's sorting field
			if($this->get('force_sort') == 'yes') {
				$section = SectionManager::fetch($this->get('parent_section'));
				$section->setSortingField($id);
			}

			$this->updateFilterTable();

			Symphony::Database()->query("DELETE FROM `tbl_fields_".$this->handle()."` WHERE `field_id` = '$id' LIMIT 1");
			return Symphony::Database()->insert($fields, 'tbl_fields_' . $this->handle());
		}

		function displayPublishPanel(&$wrapper, $data = null, $flagWithError = null, $fieldnamePrefix = null, $fieldnamePostfix = null) {
			$value = $this->getOrderValue($data);

			$max_position = Symphony::Database()->fetchRow(0, "SELECT max(value) AS max FROM tbl_entries_data_{$this->get('id')}");

			$inputs = new XMLElement('div');

			// If data is an array there must be filtered values
			if (is_array($data) && !empty($data)){
				foreach ($data as $col => $row) {
					if (!is_array($row)){
						$row = array($row);
					}
					foreach ($row as $key => $value) {
						$input = Widget::Input(
							'fields' . $fieldnamePrefix . '[' . $this->get('element_name') . '][' . $col . '][' . $key . ']' . $fieldnamePostfix,
							(strlen($value) !== 0 ? (string)$value : (string)++$max_position["max"]),
							($this->get('hide') == 'yes' || $col != 'value') ? 'hidden' : 'text'
						);
						$inputs->appendChild($input);
					}
				}
				// for now hide all 
				$wrapper->addClass('irrelevant');
				$wrapper->appendChild($inputs);
			} else {
				$input = Widget::Input(
					'fields' . $fieldnamePrefix . '[' . $this->get('element_name') . ']' . $fieldnamePostfix,
					(strlen($value) !== 0 ? (string)$value : (string)++$max_position["max"]),
					($this->get('hide') == 'yes') ? 'hidden' : 'text'
				);

				if($this->get('hide') != 'yes') {
					$label = Widget::Label($this->get('label'));
					if($this->get('required') != 'yes') $label->appendChild(new XMLElement('i', __('Optional')));
					$label->appendChild($input);
					if($flagWithError != null) {
						$wrapper->appendChild(Widget::wrapFormElementWithError($label, $flagWithError));
					}
					else {
						$wrapper->appendChild($label);
					}
				}
				else {
					$wrapper->addClass('irrelevant');
					$wrapper->appendChild($input);
				}
			}
		}

		public function displayDatasourceFilterPanel(&$wrapper, $data = null, $errors = null, $fieldnamePrefix = null, $fieldnamePostfix = null) {
			parent::displayDatasourceFilterPanel($wrapper, $data, $errors, $fieldnamePrefix, $fieldnamePostfix);

			$text = new XMLElement('p', __('To filter by ranges, add <code>%s</code> to the beginning of the filter input. Use <code>%s</code> for field name. E.G. <code>%s</code>', array('mysql:', 'value', 'mysql: value &gt;= 1.01 AND value &lt;= {$price}')), array('class' => 'help'));
			$wrapper->appendChild($text);
		}

		public function checkPostFieldData($data, &$message, $entry_id = null) {
			$message = null;

			// Check requirement
			if($this->get('required') == 'yes' && strlen($data) == 0) {
				$message = __('This is a required field.');
				return self::__MISSING_FIELDS__;
			}

			// Check type
			if(is_array($data) && is_array($data['value'])){
				$numeric = array_filter($data['value'],'is_numeric');
				if (sizeof($numeric) != sizeof($data['value'])){
					$message = __('Must be a number.');
					return self::__INVALID_FIELDS__;					
				}
			} else if(strlen($data) > 0 && !is_numeric($data)) {
				$message = __('Must be a number.');
				return self::__INVALID_FIELDS__;
			}

			return self::__OK__;
		}

		public function createTable() {
			return Symphony::Database()->query("
				CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') . "` (
					`id` int(11) unsigned NOT null auto_increment,
					`entry_id` int(11) unsigned NOT null,
					`value` double default null,
					PRIMARY KEY  (`id`),
					UNIQUE KEY `unique` (`entry_id`),
					KEY `value` (`value`)
				) TYPE=MyISAM;
			");
		}

		function buildDSRetrievalSQL($data, &$joins, &$where, $andOperation = false) {

			// Check its not a regexp
			if(preg_match('/^mysql:/i', $data[0])) {
				$field_id = $this->get('id');

				$expression = str_replace(array('mysql:', 'value'), array('', " `t$field_id`.`value` " ), $data[0]);

				$joins .= " LEFT JOIN `tbl_entries_data_$field_id` AS `t$field_id` ON (`e`.`id` = `t$field_id`.entry_id) ";
				$where .= " AND $expression ";
			}
			else {
				parent::buildDSRetrievalSQL($data, $joins, $where, $andOperation);
			}

			return true;
		}

		public function buildSortingSQL(&$joins, &$where, &$sort, $order = 'ASC') {
			
			$filterableFields = explode(',', $this->get('filtered_fields'));
			$section_id = $this->get('parent_section');

			$orderEntriesExtension = ExtensionManager::create('order_entries');
			$filters = $orderEntriesExtension->getFilters($filterableFields,$section_id);

			$filteringParams = $this->buildFilteringSQL($filters,'`ed`.');

			if (in_array(strtolower($order), array('random', 'rand'))) {
				$sort = 'ORDER BY RAND()';
			} else {
				$joins .= "LEFT OUTER JOIN `tbl_entries_data_".$this->get('id')."` AS `ed` ON (`e`.`id` = `ed`.`entry_id`{$filteringParams}) ";
				$sort = 'ORDER BY `ed`.`value` ' . $order;
			}
		}

		public function buildFilteringSQL($filters,$prefix =''){

			$filterableFields = $this->get('filtered_fields');

			//no filters no sql to add
			if (empty($filterableFields)) return "";

			$filterableFields = explode(',', $filterableFields);

			$where = '' ;

			foreach ($filterableFields as $key => $filterable_field) {
				if (isset($filters[$filterable_field])){
					$where .= " AND {$prefix}field_{$filterable_field} = '{$filters[$filterable_field]}'";
				} else {
					$where .= " AND {$prefix}field_{$filterable_field} is NULL";
				}
			}

			return $where;
		}

		private function getOrderValue($data){

			$filterableFields = $this->get('filtered_fields');

			//there are no filters to apply so should just be a single value
			if (empty($filterableFields)) {
				return $data['value'];
			}

			$filterableFields = explode(',', $filterableFields);
			$section_id = $this->get('parent_section');

			$orderEntriesExtension = ExtensionManager::create('order_entries');
			$filters = $orderEntriesExtension->getFilters($filterableFields,$section_id);

			// if there are no filter, bail out
			if (empty($filterableFields)) {
				return $data['value'];
			}

			if (!is_array($data['value'])){
				foreach ($data as $key => $value) {
					$data[$key] = array(strtolower(General::sanitize($value)));
				}
			}

			if (is_array($data['value'])){
				$keys = array_keys($data['value']);

				foreach ($filterableFields as $filtered_field_id) {
					$filter = $filters[$filtered_field_id];

					if (isset($data['field_' . $filtered_field_id])){
						$matchingKeys = array_search($filter, $data['field_' . $filtered_field_id]);
					} else {
						$matchingKeys = array();
					}

					if (empty($matchingKeys) && !is_int($matchingKeys)){
						$matchingKeys = array();
					} else if (!is_array($matchingKeys)) {
						$matchingKeys = array($matchingKeys);
					}

					//intersect the original keys with the filtered ones which match the search - should leave one or no items
					$keys = array_intersect($keys, $matchingKeys);
				}

				if ( empty($keys) ){
					//this view is not sorted
					return current($data['value']);
				} else {
					return $data['value'][current($keys)];
				}
			} else {
				return 0;
			}
		}

		public function prepareTableValue($data, XMLElement $link = null) {

			$orderValue = $this->getOrderValue($data);

			if(!$link) {
				return sprintf('<span class="order-entries-item">%d</span>', $orderValue);
			}
			else {
				$link->setValue($orderValue);
				return $link->generate();
			}
		}

		public function appendFormattedElement(XMLElement &$wrapper, $data, $encode = false, $mode = null, $entry_id = null) {
			$wrapper->appendChild(new XMLElement($this->get('element_name'), $this->getOrderValue($data) ));
		}

		public function getParameterPoolValue(Array $data) {
			return $this->getOrderValue($data);
		}

	}
