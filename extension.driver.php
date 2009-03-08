<?php

	Class extension_order_entries extends Extension{
	
		public function about(){
			return array('name' => 'Order Entries',
						 'version' => '1.2',
						 'release-date' => '2009-03-08',
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
			$field = self::sectionHasOrderField($context);
			
			if ($field && $field["id"] == $field["entry_order"] && $field["entry_order_direction"] == "asc") {
				$context["parent"]->Page->addScriptToHead(URL . '/extensions/order_entries/assets/order.js', 80);
				$this->_Parent->Configuration->set("pagination_maximum_rows", 99999, "symphony");
				
			}
		}
		
		public function appendOrderFieldId($context){
			$field = self::sectionHasOrderField($context);
			if ($field && $field["id"] == $field["entry_order"] && $field["entry_order_direction"] == "asc") {
				$span = new XMLElement("span", $field["id"]);
				$span->setAttribute("id", "order_number_field");
				$span->setAttribute("class", $field["entry_order_direction"]);
				$span->setAttribute("style", "display:none;");
				$context["parent"]->Page->Form->appendChild($span);
			}
		}
		
		private function sectionHasOrderField() {
			if(isset(Administration::instance()->Page->_context['section_handle']) && Administration::instance()->Page->_context['page'] == 'index'){
				$section = Administration::instance()->Page->_context['section_handle'];
				$field = $this->_Parent->Database->fetchRow(0, "SELECT tbl_fields.id, tbl_sections.entry_order, tbl_sections.entry_order_direction FROM tbl_fields INNER JOIN tbl_sections ON tbl_fields.parent_section = tbl_sections.id WHERE tbl_fields.type='order_entries' AND tbl_sections.handle='$section'");
				return $field;
			}
			
			return false;
		}
		
		public function uninstall(){
			$this->_Parent->Database->query("DROP TABLE `tbl_fields_order_entries`");
		}


		public function install(){
			return $this->_Parent->Database->query("CREATE TABLE `tbl_fields_order_entries` (
			  `id` int(11) unsigned NOT NULL auto_increment,
			  `field_id` int(11) unsigned NOT NULL,
			  PRIMARY KEY  (`id`),
			  UNIQUE KEY `field_id` (`field_id`)
			) TYPE=MyISAM");
		}
			
	}

?>