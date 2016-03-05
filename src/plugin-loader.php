<?php
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

require PLUGIN_PATH . 'library/implicit-hooks/class-implicit-hooks.php';

class PluginPrefixPluggable extends ImplicitHooksPluggable {}
class PluginPrefixHooks extends ImplicitHooks {}

PluginPrefixHooks::load(
    'plugin-folder',
    PLUGIN_PATH,
    'config',
    'hooks',
    'PluginPrefix'
);
