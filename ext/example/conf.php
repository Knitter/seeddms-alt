<?php
$EXT_CONF['example'] = array(
	'title' => 'Example Extension',
	'description' => 'This sample extension demonstrate the use of various hooks',
	'version' => '1.0.0',
	'author' => array('name'=>'Uwe Steinmann', 'email'=>'uwe@steinmann.cx', 'company'=>'MMK GmbH'),
	'config' => array(
		'input_field' => array(
			'title'=>'Example input field',
			'type'=>'input',
			'size'=>20,
		),
		'checkbox' => array(
			'title'=>'Example check box',
			'type'=>'checkbox',
		),
	),
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
