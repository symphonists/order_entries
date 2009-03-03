<?php

	require_once('../../../manifest/config.php');	
	require_once(CORE . '/class.administration.php');
	
    $Admin = Administration::instance();
    if(!$Admin->isLoggedIn()) return;	
	
	$field_id = $_REQUEST["field"];
	$items = $_REQUEST['items'];	
    if(!is_array($items) || empty($items)) return;
	
	foreach($items as $id => $position) {
        $Admin->Database->query("UPDATE tbl_entries_data_$field_id SET value = '$position' WHERE entry_id='$id'");
    }