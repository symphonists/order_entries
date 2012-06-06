<?php

	Class extension_order_entries extends Extension{
		
		public function getSubscribedDelegates(){
			return array(
					array(
						'page' => '/backend/',
						'delegate' => 'InitaliseAdminPageHead',
						'callback' => 'appendScriptToHead'
					)
			);
		}
		
		public function appendScriptToHead($context){
			
			$page_callback = Administration::instance()->getPageCallback();
			$page_callback = $page_callback['context'];
			
			if(isset($page_callback['section_handle']) && $page_callback['page'] == 'index'){
				
				// find sort settings for this section (sort field ID and direction)
				$section_id = SectionManager::fetchIDFromHandle($page_callback['section_handle']);
				if(!$section_id) return;
				
				$section = SectionManager::fetch(SectionManager::fetchIDFromHandle($page_callback['section_handle']));
				
				// we only want a valid entry order field and ascending order only
				if ($section->getSortingOrder() !== 'asc' || !is_numeric($section->getSortingField())) return;
				
				$field = FieldManager::fetch($section->getSortingField());
				if(!$field || $field->get('type') !== 'order_entries') return;
				
				Administration::instance()->Page->addElementToHead(
					new XMLElement(
						'script',
						"Symphony.Context.add('order-entries', " . json_encode(array(
							'id' => $field->get('id'),
							'force-sort' => $field->get('force_sort'),
						)) . ");",
						array('type' => 'text/javascript')
					), time()
				);
				
				Administration::instance()->Page->addScriptToHead(
					URL . '/extensions/order_entries/assets/order_entries.publish.js',
					time()
				);
				
				Symphony::Configuration()->set("pagination_maximum_rows", 99999, "symphony");
				
			}
			
		}
		
		public function uninstall(){
			Symphony::Database()->query("DROP TABLE `tbl_fields_order_entries`");
		}

		public function update($previousVersion){
			if(version_compare($previousVersion, '1.6', '<')){
				Symphony::Database()->query("ALTER TABLE `tbl_fields_order_entries` ADD `force_sort` enum('yes','no') DEFAULT 'no'");
			}
			if(version_compare($previousVersion, '1.8', '<')){
				Symphony::Database()->query("ALTER TABLE `tbl_fields_order_entries` ADD `hide` enum('yes','no') DEFAULT 'no'");
			}
			return true;
		}
		
		public function install(){
			return Symphony::Database()->query("CREATE TABLE `tbl_fields_order_entries` (
			  `id` int(11) unsigned NOT NULL auto_increment,
			  `field_id` int(11) unsigned NOT NULL,
			  `force_sort` enum('yes','no') default 'no',
			  `hide` enum('yes','no') default 'no',
			  PRIMARY KEY  (`id`),
			  UNIQUE KEY `field_id` (`field_id`)
			) TYPE=MyISAM");
		}
			
	}