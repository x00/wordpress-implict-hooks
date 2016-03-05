<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class PluginPrefixUtility {
    
    protected $wp_query;
    
    public function __construct( $args ) {
        $this->wp_query   = $args['wp_query'];
    }
    
    /* add methods here */
}
