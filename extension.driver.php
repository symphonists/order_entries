<?php

	Class extension_order_entries extends Extension {

		private $pagination_maximum_rows = null;
		private $force_sort = false;
		private $field_id = 0;
		private $direction = 'asc';
		
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
				)
			);
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
						$this->disablePagination();
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
						
						break;
					}
				}
			}
		}
		
		/**
		 * Add components for manual entry ordering
		 */
		public function addComponents() {
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
					PRIMARY KEY  (`id`),
					UNIQUE KEY `field_id` (`field_id`)
				) TYPE=MyISAM
			");
		}
			
	}
