<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class PluginPrefixModel {
    
    protected $wpdb;
    
    function __construct( $args ) {
        $this->wpdb = $args['wpdb'];
    }
    
    //public function get( $text ) {
        //if ( array_key_exists( $text, self::$id ) ) {
            //return self::$id[ $text ];
        //}

        //$sql = $this->wpdb->prepare(
            //"SELECT id FROM {$this->wpdb->pidix}some_table WHERE text = %s",
            //$text
        //);
        
        //$row = $this->wpdb->get_row( $sql );

        //if ( !$row ) {
            //return '';
        //}
        //return $row->id;
    //}
    
    //public function save( $text, $id ) {
        //if ( !$text || !$id ) {
            //return;
        //}

        //if ($this->get_id( $text )) {
            //$sql = $this->wpdb->prepare(
                //"UPDATE {$this->wpdb->pidix}some_table SET id = %s WHERE text = %s",
                //$id,
                //$text
            //);

            //$result = $this->wpdb->query( $sql );

            //self::$id[ $text ] = $id;
        //} else {

            //$sql = $this->wpdb->prepare(
                //"INSERT INTO {$this->wpdb->pidix}some_table (id, text) VALUES (%s, %s)",
                //$id,
                //$text
            //);
            //$result = $this->wpdb->query( $sql );
        //}
    //}
    
    public function structure() {
        
        //require_once ABSPATH . '/wp-admin/includes/upgrade.php';
        
        //$query = "CREATE TABLE {$this->wpdb->pidix}some_table (
            //id mediumint(9) NOT NULL AUTO_INCREMENT,
            //text text NOT NULL,
            //PRIMARY KEY id (id) 
            //);";
       
        //dbDelta( $query );
        
    }
}
