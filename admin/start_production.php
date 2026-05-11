<?php

global $fbx, $content;

// compile everything

fbx_debug("Compiling everything", __FILE__, __LINE__);

require_once($fbx['fbx_root'] . 'firebox_compiler.php');

$files = array_merge(
	fbx_dir_tree_files($fbx['site_root'] . 'controller/', '/.*\.php$/'),
	fbx_dir_tree_files($fbx['site_root'] . 'model/',      '/.*\.php$/'),
	fbx_dir_tree_files($fbx['site_root'] . 'view/',       '/.*\.php$/'),
	fbx_dir_tree_files($fbx['site_root'] . 'layout/',     '/.*\.php$/')
);

$fbx['production'] = true;

foreach ($files as $file)
{
	fbx_compile(substr($file, strlen($fbx['site_root'])));
}

// update the settings file

fbx_debug("Switching to production mode", __FILE__, __LINE__);

touch($fbx['site_root'] . 'parsed/production') or fbx_error("Couldn't create parsed/production");

fbx_debug("Site is in production mode", __FILE__, __LINE__);

echo "Site is in production mode. To bring up the Firebox toolbar, append fbx_develop=1 and fbx_pass=XXX to a URL.";
