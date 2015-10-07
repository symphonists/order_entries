<?php

	Class extension_order_entries extends Extension {

		private $pagination_maximum_rows = null;
		private $force_sort = false;
		private $field_id = 0;
		private $direction = 'asc';
		private $dsFilters;
		
		/**
		 * {@inheritDoc}
		 */
		public function getSubscribedDelegates(){
			return array(
				array(
					'page' => '/backend/',
					'delegate' => 'InitaliseAdminPageHead',
					'callback' => 'prepareIndex'
				),
				array(
					'page' => '/backend/',
					'delegate' => 'AdminPagePreGenerate',
					'callback' => 'adjustTable'
				),
				array(
					'page' => '/backend/',
					'delegate' => 'AdminPagePostGenerate',
					'callback' => 'resetPagination'
				),
				array(
					'page' => '/frontend/',
					'delegate' => 'DataSourcePreExecute',
					'callback' => 'saveFilterContext'
				)
			);
		}
		
		/**
		 * Save the Datasource Filter Context so it can be used for ordering
		 */
		public function saveFilterContext($context) {
			$this->dsFilters = $context['datasource']->dsParamFILTERS;
		}

		/**
		 * get filters using filterable field ids and the section denoting where the filters are
		 */
		public function getFilters($filterableFields,$section_id){
			//if no need to filter return empty filters
			if (empty($filterableFields)) return array();

			if (isset(Symphony::Engine()->Page)){
				$context = Symphony::Engine()->Page->getContext();
				$filters = $context['filters'];
				if (!isset($filters)) $filters = array();

				// check if the filters are used for entry ordering and switch from name to id
				foreach ($filters as $field_name => $value) {
					$filtered_field_id = FieldManager::fetchFieldIDFromElementName($field_name,$section_id);
					if (in_array($filtered_field_id, $filterableFields)){
						//ensuring that capitalization will never be an issue
						$filters[$filtered_field_id] = strtolower(General::sanitize($value));
					}
					unset($filters[$field_name]);
				}

			} else {
				$filters = $this->dsFilters;
				if (empty($filters)) return array();

				// check if the filters are used for entry ordering otherwise remove from list
				foreach ($filters as $filtered_field_id => $value) {
					if (!in_array($filtered_field_id, $filterableFields)){
						unset($filters[$filtered_field_id]);
					} else {
						$filters[$filtered_field_id] = strtolower(General::sanitize($value));
					}
				}

			}

			return $filters;
		}


		/**
		 * Prepare publish index for manual entry ordering
		 */
		public function prepareIndex($context) {
			$callback = Symphony::Engine()->getPageCallback();

			if($callback['driver'] == 'publish' && $callback['context']['page'] == 'index') {
				
				// Fetch sort settings for this section (sort field ID and direction)
				$section_id = SectionManager::fetchIDFromHandle($callback['context']['section_handle']);

				// Fetch sorting field
				if($section_id) {
					$section = SectionManager::fetch($section_id);
					$field = FieldManager::fetch($section->getSortingField());

					// Check sorting field
					if($field && $field->get('type') == 'order_entries') {
						$this->force_sort = $field->get('force_sort');
						$this->field_id = $field->get('id');
						$this->direction = $section->getSortingOrder();

						// Initialise manual ordering
						$this->addComponents();
						if ($field->get('disable_pagination') == 'yes'){
							$this->disablePagination();
						}
					}
				}
			}
		}
		
		/**
		 * Force manual sorting
		 */
		public function adjustTable($context) {
			if (!Symphony::Engine()->isLoggedIn()) {
				return;
			}
			$callback = Symphony::Engine()->getPageCallback();

			if($callback['driver'] == 'publish' && $callback['context']['page'] == 'index') {
				$contents = $context['oPage']->Contents->getChildren();
				
				// check every child, since the
				// form may not always be the first element
				foreach ($contents as $child) {
					$form = $child->getChildrenByName('table');
					// use current here since the keys can change somehow
					$table = current($form);

					if(!empty($table)) {
						$table->setAttribute('data-order-entries-id', $this->field_id);
						$table->setAttribute('data-order-entries-direction', $this->direction);

						if($this->force_sort == 'yes') {
							$table->setAttribute('data-order-entries-force', 'true');
						}

						$field = FieldManager::fetch($this->field_id);

						if ($field && $field->get('show_column') == 'no'){

							// sort order is not provided by field, so add manually
							$tbody = $table->getChildByName('tbody',0);

							//not looping as only the first row is required for sorting and is far more efficient
							$tr = $tbody->getChildByName('tr',0);

								$entry_id = str_replace('id-', '', $tr->getAttribute('id'));

								if ($entry_id){
									$entry = current(EntryManager::fetch($entry_id));
									$data = $entry->getData($this->field_id);
									$order = $field->getParameterPoolValue($data);
									$tr->setAttribute('data-order',$order);
								}
						}
						
						break;
					}
				}
			}
		}
		
		/**
		 * Add components for manual entry ordering
		 */
		public function addComponents() {

			// get pagination data
			$pagination = array(
				'max-rows' => Symphony::Configuration()->get('pagination_maximum_rows', 'symphony'),
				'current' => (isset($_REQUEST['pg']) && is_numeric($_REQUEST['pg']) ? max(1, intval($_REQUEST['pg'])) : 1)
			);


			// get filter data
			$filters = $_REQUEST['filter'];
			if (is_array($filters)){
				$generatedFilters = array();
				foreach ($filters as $field => $value) {
					$generatedFilters[$field] = $value;
				}
			}

			// add pagination and filter data into symphony context if Symphony does not provide it
			Administration::instance()->Page->addElementToHead(
				new XMLElement(
					'script', 
					'if (! Symphony.Context.get(\'env\').pagination) Symphony.Context.get(\'env\').pagination='.json_encode($pagination).';' .
					'if (! Symphony.Context.get(\'env\').filters) Symphony.Context.get(\'env\').filters='.json_encode($generatedFilters).';'
					, array(
						'type' => 'text/javascript'
					)
				)
			);

			Administration::instance()->Page->addScriptToHead(
				URL . '/extensions/order_entries/assets/order_entries.publish.js'
			);
		}

		/**
		 * Contextually adjust maximum pagination rows
		 */
		public function disablePagination() {
			$this->pagination_maximum_rows = Symphony::Configuration()->get('pagination_maximum_rows', 'symphony');
			Symphony::Configuration()->set('pagination_maximum_rows', 99999, 'symphony');
		}

		/**
		 * Reset maximum pagination rows
		 */
		public function resetPagination() {
			if($this->pagination_maximum_rows !== null) {
				Symphony::Configuration()->set('pagination_maximum_rows', $this->pagination_maximum_rows, 'symphony');
			}
		}
			
		/**
		 * {@inheritDoc}
		 */
		public function uninstall() {
			Symphony::Database()->query("DROP TABLE `tbl_fields_order_entries`");
		}
		
		/**
		 * {@inheritDoc}
		 */
		public function update($previousVersion) {
			$status = array();

			// Prior version 1.6
			if(version_compare($previousVersion, '1.6', '<')) {
				$status[] = Symphony::Database()->query("
					ALTER TABLE `tbl_fields_order_entries`
					ADD `force_sort` enum('yes','no')
					DEFAULT 'no'
				");
			}
			
			// Prior version 1.8
			if(version_compare($previousVersion, '1.8', '<')) {
				$status[] = Symphony::Database()->query("
					ALTER TABLE `tbl_fields_order_entries`
					ADD `hide` enum('yes','no')
					DEFAULT 'no'
				");
			}

			// Prior version 2.1.4
			if(version_compare($previousVersion, '2.1.4', '<')) {
				$status[] = Symphony::Database()->query("
					ALTER TABLE `tbl_fields_order_entries`
					ADD `disable_pagination` enum('yes','no')
					DEFAULT 'yes'
				");
			}

			// Prior version 2.2
			if(version_compare($previousVersion, '2.2', '<')) {
				$status[] = Symphony::Database()->query("
					ALTER TABLE `tbl_fields_order_entries`
					ADD `filtered_fields` varchar(255) DEFAULT NULL
				");

				$fields =  Symphony::Database()->fetchCol('field_id',"SELECT field_id FROM `tbl_fields_order_entries`");

				foreach ($fields as $key => $field) {					
					$status[] = Symphony::Database()->query("
						ALTER TABLE `tbl_entries_data_{$field}`
						DROP INDEX `entry_id`
					");

					$status[] = Symphony::Database()->query("
						ALTER TABLE `tbl_entries_data_{$field}`
						ADD UNIQUE `unique`(`entry_id`)
					");
				}
			}

			// Report status
			if(in_array(false, $status, true)) {
				return false;
			}
			else {
				return true;
			}
		}
		
		/**
		 * {@inheritDoc}
		 */
		public function install() {
			return Symphony::Database()->query("
				CREATE TABLE `tbl_fields_order_entries` (
					`id` int(11) unsigned NOT NULL auto_increment,
					`field_id` int(11) unsigned NOT NULL,
					`force_sort` enum('yes','no') default 'no',
					`hide` enum('yes','no') default 'no',
					`disable_pagination` enum('yes','no') default 'no',
					`filtered_fields` varchar(255) DEFAULT NULL,
					PRIMARY KEY  (`id`),
					UNIQUE KEY `field_id` (`field_id`)
				) TYPE=MyISAM
			");
		}
			
	}
