<?php
// check running command line
if ( php_sapi_name() != "cli" ) {
	die("You can only run this script on the command line\n");
}

$complete = false;

// useful functions
function run_cmd( $cmd ) {
	ob_start();
	exec($cmd, $return, $error );
	ob_end_clean();
	return array($return, $error );
}

function confirm( $message ) {
	echo "{$message} [y/n] \n";
	$res = fopen('php://stdin', 'r');
	$resp = strtolower( fgets( $res ) );
	return ! (trim( $resp ) != 'yes' && trim( $resp ) != 'y' );
}

function copy_replace_save( $from, $to, $replace = array() ) {
	if ( empty( $replace ) ) {
		copy( $from, $to );
	} else {
		$from_file_txt = file_get_contents( $from );
		foreach ( $replace as $rule => $sub ) {
			if ( strpos( $rule, '`') !== false ) {
				$from_file_txt = preg_replace( $rule, $sub, $from_file_txt );
			} else {
				$from_file_txt = str_replace( $rule, $sub, $from_file_txt );
			}
		}

		file_put_contents( $to, $from_file_txt );
	}
}

function copy_replace( $from, $to, $replace = array() ) {
	global $overwrite;
	if ( is_dir( $from ) ) {
		$res = opendir( $from );
		while( $file = readdir ( $res ) ) {
			if ($file != '.' && $file != '..' ){
				if( is_dir( $from . '/' . $file ) ){
					if( !is_dir( $to . '/' . $file ) ){
						mkdir( $to . '/' . $file );
					}
					copy_replace( $from . '/' . $file, $to . '/' . $file, $replace );
				} else {
					if ( file_exists ($to . '/' . $file ) ) {
						if ( !$overwrite ) {
							if ( !confirm("The file {$to}/{$file} already exists, are you sure you want to overwrite?")) {
								continue;
							}
						}
					}
					copy_replace_save(  $from . '/' . $file, $to . '/' . $file, $replace );
				}
			}
		}
		closedir( $res );
	} else {
		copy_replace_save( $from, $to, $replace );
	}
}

function remove_options( $arg ) {
	return strpos( $arg, '--') !== 0;
}

// register shutdown comands
function shut_down() {
	global $curr_dir, $complete;
	// checkout master
	chdir( $curr_dir );
	run_cmd("git checkout master --quiet");

	// finish
	if ( $complete ) {
		echo "\n\n***** COMPLETED *****\n";
	} else {
		echo "\n\n***** ABORTED *****\n";
	}
}

register_shutdown_function('shut_down');

// get options
$opts = getopt('',  array('template::', 'force-overwrite', 'help', 'update', 'version') );

// store current directory
$curr_dir = rtrim( getcwd(), '/');

// help info
$help_txt = <<<EOT

***** HELP *****

Install Usage: php install.php [OPTION...] FILE CLASSPREFIX
Example: php install.php --template=mvc /path/to/wordpress/wp-content/plugins/special-widget SpecialWidget

	--help                          Gets this help list ofImplicit Hooks

General modes:
	--version=VERSION               Specify a tagged version of Implicit Hooks

Install modes:
	--template=TEMPLATE, --force-overwrite
									Specify which tempalte to install (default basic if none).
									Using force-overwrite will overwrite existing template without warning

Update modes:
	--update                        To specify you only want to update Implicit Hooks

Report bugs to: https://github.com/x00/wordpress-implict-hooks/issues
EOT;

$error_txt = "\n***** ERROR *****\n\n";

if ( isset( $opts['help'] ) ) {
	// get delimited list of templates
	$templates = join('|', preg_grep('/^[^.]/', scandir( $curr_dir . '/src/templates/') ) );
	die( $help_txt );
}

$update = isset( $opts['update'] );

$overwrite = $update || isset( $opts['force-overwrite'] );

$version = isset( $opts['version'] );

// remove options from arguments
$argv = array_values( array_filter( $argv, 'remove_options') );

if ( !isset( $argv[1] ) ) {
	die("{$error_txt}Plugin path required\n{$help_txt}\n");
}

if ( !isset( $argv[2] ) || !preg_match('`^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$`', $argv[2] ) ) {
	die("{$error_txt}Plugin Class Prefix is required and needs to be correctly formatted for classes e.g. SpecialWidget\n{$help_txt}\n");
}

// determine current template
$template = '';
if ( isset( $opts['template'] ) ) {
	$template = $opts['template'];

	if ( !is_dir( $curr_dir . '/src/templates/' . $template ) ) {
		die("{$error_txt}Template type not found\n{$help_txt}\n");
	}
}
// get directory, folder and prefix
$template_dir = $curr_dir . '/src/templates/' . $template ;
$plugin_dir = rtrim( $argv[1], '/');
$plugin_folder = basename( $plugin_dir );
$plugins_dir = dirname( $plugin_dir );
$class_prefix = ucfirst($argv[2]);

// check git installed.
list( $return, $error ) = run_cmd('git --version');

if ( $error ) {
	die("{$error_txt}You need to install git\n");
}

// find latest version
list( $versions, $error ) = run_cmd('git tag -l v*');

if ( $error || empty($versions ) ) {
	die("{$error_txt}Can't find current version of Implicit Hooks\n");
}

if ( $version && ( !preg_match('`^v[0-9]+[0-9\.]+?[a-z]*`', $version ) ||  !in_array( $version, $versions ) ) ) {
	die("{$error_txt}Version not found\n");
}

if ( !$version ) {
	usort( $versions, 'version_compare');

	$version = array_pop( $versions );
}

// checkout version
list( $return, $error ) = run_cmd("git checkout {$version} --quiet");

if ( $error ) {
	die("{$error_txt}Cant checkout version.\n");
}

// test an set up directories
if ( !is_dir( $plugins_dir ) ) {
	die("{$error_txt}Not a valid plugins directory.\n");
}

if ( !preg_match('`wp-content/plugins$`',  $plugins_dir ) ) {
	if ( !confirm("{$error_txt}You don't appear to be installing a wordpress plugins directory. Continue anyway?") ) {
		exit;
	}
}

if ( !chdir( $plugins_dir ) ) {
	die("{$error_txt}Unable to move to plugins directory\n");
}

if ( !is_dir('./' . $plugin_folder ) ) {
	if ( !mkdir('./' . $plugin_folder ) ) {
		die("{$error_txt}Unable to make plugin folder\n");
	}
}

if ( !chdir( './' . $plugin_folder ) ) {
	die("{$error_txt}Unable to move to plugin folder\n");
}

if ( !is_dir('./library') ) {
	if ( !mkdir('./library' ) ) {
		die("{$error_txt}Unable to make library directory\n");
	}
}

if ( !chdir( './library' ) ) {
	die("{$error_txt}Unable to move to library directory\n");
}

if ( !is_dir('./implicit-hooks') ) {
	if ( !mkdir('./implicit-hooks' ) ) {
		die("{$error_txt}Unable to make implicit hooks directory\n");
	}
}

if ( !chdir( './implicit-hooks' ) ) {
	die("{$error_txt}Unable to move to implicit hooks directory\n");
}

if ( file_exists('./class-implicit-hooks.php') ) {
	if ( !$update ) {
		die("{$error_txt}class-implicit-hooks.php already exists\n");
	}
}

$subs = array(
	'`\b(ImplicitHooks[A-Za-z]*)\b`' => '\1_' . str_replace('.', '_', $version ),
	'const VERSIONED = false;' => 'const VERSIONED = true;',
	'PLUGIN_PATH' => strtoupper( str_replace('-', '_', $plugin_folder ) ) . '_PATH',
	'plugin-folder' => $plugin_folder,
	'PluginPrefix' => $class_prefix,
	'plugin_label' => preg_replace('`[^a-zA-Z0-9_\x7f-\xff]`', '_', preg_match('`^[0-9]`', $plugin_folder ) ? '_' . $plugin_folder : $plugin_folder )
);

// save versioned file
copy_replace(
	$curr_dir . '/src/class-implicit-hooks.php',
	$plugin_dir . '/library/implicit-hooks/class-implicit-hooks.php',
	$subs
);

// copy over loader
copy_replace(
	$curr_dir . '/src/plugin-loader.php',
	"{$plugin_dir}/{$plugin_folder}-loader.php",
	$subs
);

// copy over plugin file if necessary
if ( $update || file_exists("{$plugin_dir}/{$plugin_folder}.php") ) {
	if ( strpos( file_get_contents("{$plugin_dir}/{$plugin_folder}.php"), "{$plugin_folder}-loader.php" ) === false ) {
		echo "As your plugin file already exists, you may need to add the following to you plugin file:\n\n",
			"\trequire plugin_dir_path( __FILE__ ) . '{$plugin_folder}-loader.php';\n\n";
	}
} else {
	copy_replace(
		$curr_dir . '/src/plugin-file.php',
		"{$plugin_dir}/{$plugin_folder}.php",
		$subs
	);
	echo "Don't forget to edit the file header of {$plugin_dir}/{$plugin_folder}.php\n";
}

if ( !$template && !file_exists("{$plugin_dir}/{$plugin_folder}.php") ) {
	$template = 'basic';
}

if ( $template ) {
	// copy over template
	copy_replace(
		$template_dir,
		$plugin_dir,
		$subs
	);
}

$complete = true;
