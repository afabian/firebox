<?

global $fbx, $content;

// compile everything

fbx_debug("Compiling everything", __FILE__, __LINE__);

$files = array_merge( dirTree($fbx['site_root'] . 'controller/', '/.*\.php/'),
					  dirTree($fbx['site_root'] . 'model/', '/.*\.php/'),
                      dirTree($fbx['site_root'] . 'view/', '/.*\.php/'),
                      dirTree($fbx['site_root'] . 'layout/', '/.*\.php/')
                    );

require_once($fbx['fbx_root'] . 'firebox_compiler.php');

$fbx['production'] = true;

foreach ($files as $file)
{
	$file = substr($file, strlen($fbx['site_root']));
	fbx_compile($file);	
}

// update the settings file

fbx_debug("Switching to production mode", __FILE__, __LINE__);

touch($fbx['site_root'] . 'parsed/production') or fbx_error("Couldn't create parsed/production");

// and we're done!

fbx_debug("Site is in production mode", __FILE__, __LINE__);

echo "Site is in production mode.  To bring up the Firebox toolbar, append fbx_develop=1 and fbx_pass=XXX to a URL.";

return;

// directory searcher function

function dirTree($dir, $pattern) {
    $d = dir($dir);
    $myFiles = array();
    while (false !== ($entry = $d->read())) {
        if($entry[0] != '.' && is_dir($dir.$entry))
            $myFiles = array_merge($myFiles, dirTree($dir.$entry.'/', $pattern));
        if ($entry[0] != '.' && is_file($dir.$entry) && preg_match($pattern, $entry))
        	$myFiles[] = $dir.$entry;
    }
    $d->close();
    return $myFiles;
}
