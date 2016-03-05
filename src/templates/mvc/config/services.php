<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$services = array(
	'utility' => array(
		'requires'  => array(
			'some_library'
		),
		'class'     => 'PluginPrefixUtility',
		'path'      => 'helper/class-utility.php',
		'args'      => array(
			'wp_query'   => '%%wp_query.query'
		)
	),
	'model' => array(
		'class'     => 'PluginPrefixModel',
		'path'      => 'model/class-model.php',
		'args'      => array(
			'wpdb'      => '%%wpdb' // pass database class instance
		)
	),
	'controller' => array(
		'class'     => 'PluginPrefixController',
		'path'      => 'controller/class-controller.php',
		'args'      => array(
			'views_dir' => PLUGIN_PATH . 'views'
		)
	),
	/*'some_library' => array(
		'class'     => 'SomeLibrary',
		'path'      => 'library/some_library/class.some_library.php'
	),
	'custom_controller' => array(
		'requires'  => array(
			'controller',
			'some_library'
		),
		'class'     => 'PluginPrefixCustomController',
		'path'      => 'controller/class-custom.php',
		'args'      => array(
			'views_dir' => PLUGIN_PATH . 'views',
			'utility'   => '@@utility', // include utility service
			'post'      => '%%_POST'
		)
	),*/
);
