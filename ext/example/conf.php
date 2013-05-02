<?php
$EXT_CONF['example'] = array(
	'title' => 'Example Extension',
	'description' => 'Example Extension',
	'version' => '1.0.0',
	'author' => array('name'=>'Uwe Steinmann', 'email'=>'uwe@steinmann.cx', 'company'=>'MMK GmbH'),
	'constraints' => array(
		'depends' => array('php' => '5.4.4-', 'seeddms' => '4.2.0-'),
	),
	'icon' => '',
	'class' => array(
		'file' => 'class.example.php',
		'name' => 'SeedDMS_ExtExample'
	),
);
?>
