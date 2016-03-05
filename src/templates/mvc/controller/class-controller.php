<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class PluginPrefixController extends TestPluginPluggable {
	
	protected $views_dir = '';
	protected $data;
	
	function __construct( $args ) {
		$this->views_dir = rtrim ( $args['views_dir'], '/' );
	}
	
	protected function data( $key ) {
		return isset( $this->data[ $key ] ) ? $this->data[ $key ] : null;
	}
	
	protected function render( $view, $data = array() ) {
		$this->data = $data;
		include( $this->views_dir . '/' . $view . '.php');
	}

}
