<?php

	Class extension_order_entries extends Extension{
	
		public function about(){
			return array('name' => 'Order Entries',
						 'version' => '1.9.8',
						 'release-date' => '2011-12-19',
						 'author' => array('name' => 'Nick Dunn',
										   'website' => 'http://nick-dunn.co.uk')
				 		);
		}
		
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
				$section_handle = $page_callback['section_handle'];
				$section = Symphony::Database()->fetchRow(0, "SELECT entry_order, entry_order_direction FROM tbl_sections WHERE handle='$section_handle'");
				
				// we only want a valid entry order field and ascending order only
				if ($section['entry_order_direction'] != 'asc' || !is_numeric($section['entry_order'])) return;
				
				$order_entries_field = Symphony::Database()->fetchRow(0, "SELECT field_id as `id`, force_sort FROM tbl_fields_order_entries WHERE field_id=" . $section['entry_order']);
				
				if($order_entries_field) {
					
					Administration::instance()->Page->addElementToHead(
						new XMLElement(
							'script',
							"Symphony.Context.add('order-entries', " . json_encode(array(
								'id' => $order_entries_field['id'],
								'force-sort' => $order_entries_field['force_sort'],
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
			
		}
		
		public function uninstall(){
			$this->_Parent->Database->query("DROP TABLE `tbl_fields_order_entries`");
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

?>