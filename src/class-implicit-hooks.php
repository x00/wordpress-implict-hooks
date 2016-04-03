<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// exists check for simple inclusion
if ( ! class_exists( 'ImplicitHooks' ) ) {

	class ImplicitHooksClassLoader {

		protected $services;
		protected $hook_dir;
		protected $plugin_class_prefix;
		protected $hooks_prefix;
		protected $hooks_suffix;

		protected $instance = array();

		function  __construct( $base_dir, $config_dir, $hook_dir, $plugin_class_prefix, $hooks_files_prefix, $hooks_files_suffix ) {
			$base_dir   = rtrim( $base_dir, '/' );
			$config_dir = $base_dir . '/' . $config_dir;
			$hook_dir   = $base_dir . '/' . $hook_dir;

			$this->services = new ImplicitHooksServices( $base_dir, $config_dir, $plugin_class_prefix ); // conditional on init
			$this->hook_dir = $hook_dir;
			$this->plugin_class_prefix = $plugin_class_prefix;
			$this->hooks_prefix   = $hooks_files_prefix;
			$this->hooks_suffix   = $hooks_files_suffix;
		}

		public function instance( $instance ) {
			if( isset( $this->instance[ $instance ] ) ) {
				return $this->instance[ $instance ];
			} else {
				false;
			}
		}

		public function load() {
			$dir = rtrim( $this->hook_dir, '/' );
			$hooks_files = glob( "{$this->hook_dir}/{$this->hooks_prefix}*{$this->hooks_suffix}" );
			foreach ( $hooks_files as $hook_file ) {

				$key = strtolower(
					preg_replace( '`^' . preg_quote("{$this->hook_dir}/{$this->hooks_prefix}").'(.*)' . preg_quote( $this->hooks_suffix ) .'$`', '$1', $hook_file )
				);

				$class_name = $this->plugin_class_prefix . str_replace(' ', '',
					ucwords(
						str_replace( '-', ' ', $key )
					)
				);

				$class_name .= 'Hooks';

				require_once( $hook_file );

				if( class_exists( $class_name ) ) {
					$this->instance[ $key ] = new $class_name( $this->services );
				}
			}
		}
	}

	class ImplicitHooksServices {

		protected $services  = null;
		protected $base_dir = null;

		function  __construct( $base_dir, $config_dir, $plugin_class_prefix ) {

			$this->base_dir = rtrim( $base_dir, '/' );
			$config_dir = rtrim( $config_dir, '/' );

			// services file
			include_once $config_dir. '/services.php';

			if ( !isset($services) ) {
				$services = array();
			}

			$services = apply_filters( 'implicit_hooks_services', $services, $plugin_class_prefix );

			// will error if doesn't exist.
			$this->services =  $services;

			// remove variable
			unset( $services );
		}

		protected function &arg( $key ) {

			// need variable to pass by reference
			$ref = null;

			if( !preg_match( '`[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff\.]*`', $key ) ) {
				return $ref;
			}

			$parts = explode( '.' , $key );

			$key = array_shift( $parts );

			if ( isset( $GLOBALS [ $key ] ) ){

				if( !empty( $parts ) ) {
					$ref = &$GLOBALS [ $key ];
					$break = false;

					foreach ( $parts as $part ) {

						if ( is_array( $ref ) ) {

							if ( isset( $ref ) ) {
								$ref = &$ref[ $part ];
							} else {
								$break = true;
							}
						} else if ( is_object( $ref ) ) {

							if ( isset( $ref->$part ) ) {
								$ref = &$ref->$part;
							} else {
								$break = true;
							}
						} else {
							$break = true;
						}

						if ( $break ) {
							$break_ref = null;
							break;
						}
					}

					if ( $break ) {
						return $break_ref;
					}
				} else {
					return $GLOBALS [ $key ];
				}
			}

			return $ref;
		}

		public function get( $service ) {

			if( isset ( $this->services[ $service ] )) {

				$curr_service = $this->services[ $service ];

				if ( !isset( $curr_service['instance']) ) {

					if ( isset( $curr_service['requires'] ) ) {
						foreach ( $curr_service['requires'] as $require ) {
							require_once( $this->base_dir . '/' . $this->services[ $require ]['path'] );
						}
					}

					require_once( $this->base_dir . '/'. $curr_service['path'] );
					$cur_args = array();
					if ( is_array( $curr_service['args'] ) ) {
						foreach( $curr_service['args'] as $arg_name => $arg ) {
							if( is_string( $arg_name ) ) { // must have label
								if ( is_string( $arg ) && strpos( $arg, '@@' ) === 0 ){ // service prefixed with @@
									$arg_service = ltrim( $arg, '@' );
									$cur_args[ $arg_name ] = $this->get( $arg_service );
								} else if ( is_string( $arg ) && strpos( $arg, '%%' ) === 0 ){ // global prefixed with %%
									$arg_global = ltrim( $arg, '%' );
									$cur_args[ $arg_name ] = &$this->arg( $arg_global ); // pass by reference
								} else {
									$cur_args[ $arg_name ] = $arg; // literal
								}
							}
						}
					}

					$service_class = $curr_service['class'];
					$this->services[ $service ]['instance'] = new $service_class( $cur_args );
				}
				return $this->services[ $service ]['instance'];
			} else {
				return false;
			}
		}
	}

	abstract class ImplicitHooksPluggable {

		protected function event( $name, $type, $args ) {
			$event_func = "{$type}_ref_array";
			$event_func( $name, $args );
		}

		protected function action( $action ) {
			$args = func_get_args();
			array_shift( $args );
			$this->event( $action, 'do_action', $args  );
		}

		protected function filter( $filter ) {
			$args = func_get_args();
			array_shift( $args );
			return $this->event( $action, 'apply_filters', $args );
		}
	}

	abstract class ImplicitHooks extends ImplicitHooksPluggable {

		const VERSIONED = false;

		protected $services = null;

		protected static $cache = array(
			'file_loader' => null,
			'curr_plugin_file' => null,
			'activation_hooks' => array(),
			'deactivation_hooks' => array()
		);

		function __construct( $services ) {

			if ( !self::VERSIONED ) {
				die('Implicit Hooks was not correctly installed.');
			}

			$this->services = $services;
			$this->load_hooks();
			$this->action('implicit_hooks_init');
		}

		protected function load_hooks() {
			$class_methods = new ReflectionClass( $this );

			foreach( $class_methods->getMethods() as $method ) {
				$method_name = $method->name;
				$is_hook = preg_match(
					'`^([a-z][a-z0-9_]+)#([a-z][a-z0-9_]+)#(action|filter|activate|deactivate)(_([0-9]+))?$`',
					str_replace( '__', '#', $method_name ),
					$match
				);

				if ( $is_hook ) {

					$callback    = array( $this, $method_name );
					$hook_name   = $match[2] == 'init' ? 'implicit_hooks_init' : $match[2];
					$priority    = isset($match[4]) ? $match[4] : 20;
					$hook_type   = $match[3];
					if ( $hook_name == 'register' && $hook_type == 'activate') {
						$this->add_activation_hook( $callback );
					} else if ( $hook_name == 'register' && $hook_type == 'deactivate' ) {
						$this->add_deactivation_hook( $callback );
					} else {
						$hook_type      = "add_{$hook_type}"; // add_action or add_filter
						$num_args       = $method->getNumberOfParameters();

						$hook_type( $hook_name, $callback, $priority, $num_args );
					}
				}
			}
		}

		protected function plugin_state_hook( $callback, $hook_type ) {

			$hooks = &self::$cache[ "{$hook_type}_hooks" ];

			if ( !isset( $hooks[ self::cache('curr_plugin_file')  ] ) ) {
				$hooks[ self::cache('curr_plugin_file')  ] = array();
			}

			$hooks[ self::cache('curr_plugin_file')  ][] = $callback;
		}

		protected function add_activation_hook( $callback ) {
			$this->plugin_state_hook( $callback, 'activation' );
		}

		protected function add_deactivation_hook( $callback ) {
			$this->plugin_state_hook( $callback, 'deactivation' );
		}

		static protected function plugin_state_action( $hook_type ) {

			$hooks = self::cache( "{$hook_type}_hooks" );

			if ( !isset( $hooks[ self::cache('curr_plugin_file')  ] ) ) {
				return;
			}

			foreach ( $hooks[ self::cache('curr_plugin_file')  ] as $callback ) {
				call_user_func( $callback );
			}
		}

		static public function activation() {
			self::plugin_state_action('activation');
		}

		static public function deactivation() {
			self::plugin_state_action('deactivation');
		}

		static protected function cache( $key, $value = null ) {
			if ( $value !== null ) {
				self::$cache[ $key ] = $value;
			}

			return isset( self::$cache[ $key ] ) ? self::$cache[ $key ] : null;
		}

		static public function load( $plugin_file, $base_dir, $config_dir,  $hook_dir, $plugin_class_prefix, $hooks_files_prefix = 'class-', $hooks_files_suffix = '-hooks.php' ) {

			self::cache('curr_plugin_file',  $plugin_file );

			$plugin_loc = "{$base_dir}{$plugin_file}.php";

			register_activation_hook( $plugin_loc, array('ImplicitHooks', 'activation') );
			register_deactivation_hook( $plugin_loc, array('ImplicitHooks', 'deactivation') );

			self::cache('file_loader', new ImplicitHooksClassLoader( $base_dir, $config_dir, $hook_dir, $plugin_class_prefix, $hooks_files_prefix, $hooks_files_suffix ) );
			self::cache('file_loader')->load();
		}

		public function service( $instance ) {
			return $this->services->get( $instance );
		}

		public function get( $instance ) {
			if ( self::cache('file_loader') ) {
				self::cache('file_loader')->instance( $instance );
			} else {
				return false;
			}
		}
	}
}
