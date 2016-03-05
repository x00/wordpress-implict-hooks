<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class PluginPrefixGeneralHooks extends PluginPrefixHooks {
    
    public function load_language() {
        load_plugin_textdomain( 'plugin-folder', false, dirname( plugin_basename( PLUGIN_PATH . 'plugin-folder.php' ) ) . '/languages' );
    }
    
    public function i18n__plugins_loaded__action() {
        $this->load_language();
    }
    
}
