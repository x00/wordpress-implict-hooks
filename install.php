<?php
// check running command line
if (php_sapi_name() != "cli") {
    die("You can only run this script on the command line.\n");
}

// check git installed. 
exec('git --version', $return, $error);

if ($error) {
    die("You need to isntall git.\n");
}

// get class
$implicit_hooks_txt = file_get_contents('src/class-implicit-hooks.php');

// add version number to classes
$implicit_hooks_txt = preg_replace('`\b(ImplicitHooks[A-Za-z]*)\b`', '\1_v0_1b', $implicit_hooks_txt );

exec('git checkout master', $return, $error);

if ($error) {
    die("git repo missing\n");
}
