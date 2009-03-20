<?php

	Class extension_order_entries extends Extension{
	
		public function about(){
			return array('name' => 'Order Entries',
						 'version' => '1.3',
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
			$field = self::activeOrderField($context);
			
			if ($field) {
				$context["parent"]->Page->addScriptToHead(URL . '/extensions/order_entries/assets/order.js', 80);
				$this->_Parent->Configuration->set("pagination_maximum_rows", 99999, "symphony");
				
			}
		}
		
		public function appendOrderFieldId($context){
			$field = self::activeOrderField($context);
			if ($field) {
				$span = new XMLElement("span", $field["id"]);
				$span->setAttribute("id", "order_number_field");
				$span->setAttribute("class", "asc");
				$span->setAttribute("style", "display:none;");
				$context["parent"]->Page->Form->appendChild($span);
			}
		}
		
		private function activeOrderField() {
			if(isset(Administration::instance()->Page->_context['section_handle']) && Administration::instance()->Page->_context['page'] == 'index'){
				
				// find sort settings for this section (sort field ID and direction)
				$section_handle = Administration::instance()->Page->_context['section_handle'];
				$section = $this->_Parent->Database->fetchRow(0, "SELECT entry_order, entry_order_direction FROM sym_sections WHERE handle='$section_handle'");
				
				// only apply sorting if ascending and entry_order is an Order Entries field
				if (!$section['entry_order_direction'] == 'asc' && !is_numeric($section['entry_order'])) return;

				$field = $this->_Parent->Database->fetchRow(0, "SELECT id FROM sym_fields WHERE id=" . $section['entry_order'] . " AND type='order_entries'");
				
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