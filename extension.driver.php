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
					'delegate' => 'InitialiseAdminPageHead',
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
				$context = method_exists(Symphony::Engine()->Page, 'getContext') ? Symphony::Engine()->Page->getContext() : array();
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
					$section = (new SectionManager)->select()->section($section_id)->execute()->next();
					$field = (new FieldManager)->select()->field($section->getSortingField())->execute()->next();

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

						$field = (new FieldManager)->select()->field($this->field_id)->execute()->next();

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
			// get filter data
			$filters = $_REQUEST['filter'];
			if (is_array($filters)){
				$generatedFilters = array();
				foreach ($filters as $field => $value) {
					$generatedFilters[$field] = $value;
				}
			}

			// add pagination and filter data on the form element
			Administration::instance()->Page->Form->setAttribute(
				'data-order-entries-filter',
				empty($generatedFilters) ? '' : json_encode($generatedFilters)
			);
			Administration::instance()->Page->Form->setAttribute(
				'data-order-entries-pagination-max-rows',
				Symphony::Configuration()->get('pagination_maximum_rows', 'symphony')
			);
			Administration::instance()->Page->Form->setAttribute(
				'data-order-entries-pagination-current',
				(isset($_REQUEST['pg']) && is_numeric($_REQUEST['pg']) ? max(1, intval($_REQUEST['pg'])) : 1)
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
			return Symphony::Database()
				->drop('tbl_fields_order_entries')
				->ifExists()
				->execute()
				->success();
		}

		/**
		 * {@inheritDoc}
		 */
		public function update($previousVersion = false) {
			$status = array();

			// Prior version 1.6
			if(version_compare($previousVersion, '1.6', '<')) {
				$status[] = Symphony::Database()
					->alter('tbl_fields_order_entries')
					->add([
						'force_sort' => [
							'type' => 'enum',
							'values' => ['yes','no'],
							'default' => 'no',
						],
					])
					->execute()
					->success();
			}

			// Prior version 1.8
			if(version_compare($previousVersion, '1.8', '<')) {
				$status[] = Symphony::Database()
					->alter('tbl_fields_order_entries')
					->add([
						'hide' => [
							'type' => 'enum',
							'values' => ['yes','no'],
							'default' => 'no',
						],
					])
					->execute()
					->success();
			}

			// Prior version 2.1.4
			if(version_compare($previousVersion, '2.1.4', '<')) {
				$status[] = Symphony::Database()
					->alter('tbl_fields_order_entries')
					->add([
						'disable_pagination' => [
							'type' => 'enum',
							'values' => ['yes','no'],
							'default' => 'no',
						],
					])
					->execute()
					->success();
			}

			// Prior version 2.2
			if(version_compare($previousVersion, '2.2', '<')) {
				$status[] = Symphony::Database()
					->alter('tbl_fields_order_entries')
					->add([
						'filtered_fields' => [
							'type' => 'varchar(255)',
							'null' => true,
						],
					])
					->execute()
					->success();

				$fields =  Symphony::Database()
					->select(['field_id'])
					->from('tbl_fields_order_entries')
					->execute()
					->column('field_id');

				foreach ($fields as $key => $field) {
					$status[] = Symphony::Database()
						->alter('tbl_entries_data_' . $field)
						->dropIndex('entry_id')
						->addKey([
							'entry_id' => 'unique',
						])
						->execute()
						->success();
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
			return Symphony::Database()
				->create('tbl_fields_order_entries')
				->ifNotExists()
				->fields([
					'id' => [
						'type' => 'int(11)',
						'auto' => true,
					],
					'field_id' => 'int(11)',
					'force_sort' => [
						'type' => 'enum',
						'values' => ['yes','no'],
						'default' => 'no',
					],
					'hide' => [
						'type' => 'enum',
						'values' => ['yes','no'],
						'default' => 'no',
					],
					'disable_pagination' => [
						'type' => 'enum',
						'values' => ['yes','no'],
						'default' => 'no',
					],
					'filtered_fields' => [
						'type' => 'varchar(255)',
						'null' => true,
					],
				])
				->keys([
					'id' => 'primary',
					'field_id' => 'unique',
				])
				->execute()
				->success();
		}

	}
