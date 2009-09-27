<?php

	Class extension_order_entries extends Extension{
	
		protected $active_field;
	
		public function about(){
			return array('name' => 'Order Entries',
						 'version' => '1.8',
						 'release-date' => '2009-09-28',
						 'author' => array('name' => 'Nick Dunn',
										   'website' => 'http://airlock.com',
										   'email' => 'nick.dunn@airlock.com')
				 		);
		}
		
		public function getSubscribedDelegates(){
			return array(
						array(
							'page' => '/backend/',
							'delegate' => 'InitaliseAdminPageHead',
							'callback' => 'appendScriptToHead'
						),
						array(
							'page' => '/backend/',
							'delegate' => 'AppendElementBelowView',
							'callback' => 'appendOrderFieldId'
						),						
			);
		}
		
		public function appendScriptToHead($context){
			$this->activeOrderField();
			if ($this->active_field) {
				Administration::instance()->Page->addScriptToHead(URL . '/extensions/order_entries/assets/order.js', 80);
				$this->_Parent->Configuration->set("pagination_maximum_rows", 99999, "symphony");
			}
		}
		
		public function appendOrderFieldId($context){			
			if ($this->active_field) {
				$span = new XMLElement("span", $this->active_field["id"]);
				$span->setAttribute("id", "order_number_field");
				$span->setAttribute("class", $this->active_field["force_sort"]);
				$span->setAttribute("style", "display:none;");
				$context["parent"]->Page->Form->appendChild($span);
			}
		}
		
		private function activeOrderField() {
			if(isset(Administration::instance()->Page->_context['section_handle']) && Administration::instance()->Page->_context['page'] == 'index'){
				
				// find sort settings for this section (sort field ID and direction)
				$section_handle = Administration::instance()->Page->_context['section_handle'];
				$section = $this->_Parent->Database->fetchRow(0, "SELECT entry_order, entry_order_direction FROM tbl_sections WHERE handle='$section_handle'");
				
				// only apply sorting if ascending and entry_order is an Order Entries field
				if ($section['entry_order_direction'] != 'asc' || !is_numeric($section['entry_order'])) return;
				
				$field = $this->_Parent->Database->fetchRow(0, "SELECT field_id as `id`, force_sort FROM tbl_fields_order_entries WHERE field_id=" . $section['entry_order']);
				
				$this->active_field = $field;
			}
		}
		
		public function uninstall(){
			$this->_Parent->Database->query("DROP TABLE `tbl_fields_order_entries`");
		}

		public function update($previousVersion){
			if(version_compare($previousVersion, '1.6', '<')){
				$this->_Parent->Database->query("ALTER TABLE `tbl_fields_order_entries` ADD `force_sort` enum('yes','no') DEFAULT 'no'");
			}
			if(version_compare($previousVersion, '1.8', '<')){
				$this->_Parent->Database->query("ALTER TABLE `tbl_fields_order_entries` ADD `hide` enum('yes','no') DEFAULT 'no'");
			}
			return true;
		}
		
		public function install(){
			return $this->_Parent->Database->query("CREATE TABLE `tbl_fields_order_entries` (
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