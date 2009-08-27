<?php

	require_once(TOOLKIT . '/class.administrationpage.php');
	
	class ContentExtensionOrder_EntriesSave extends AdministrationPage {
		protected $_driver = null;
		
		public function __construct(&$parent){
			parent::__construct($parent);
			$this->_driver = $this->_Parent->ExtensionManager->create('order_entries');
		}
		
		public function __viewIndex() {
			$field_id = $_REQUEST["field"];
			$items = $_REQUEST['items'];
			
		    if(!is_array($items) || empty($items)) exit;

			foreach($items as $entry_id => $position) {
		        $this->_Parent->Database->query("INSERT INTO tbl_entries_data_$field_id (entry_id, value) VALUES ('$entry_id', '$position') ON DUPLICATE KEY UPDATE `entry_id` = '$entry_id'");
		    }
			
			exit;
		}
	}
	
?>