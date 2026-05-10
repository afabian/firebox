<?php

global $fbx;

$target = (isset($_REQUEST['target']) && $_REQUEST['target']) ? $_REQUEST['target'] . '/' : $fbx['site_root'];

if (!is_dir($target)) mkdir($target, 0755, true);

// create the skeleton project

//error_reporting(E_ALL);

$new_directories = array( 'controller', 'model', 'model/default', 'view', 'view/default', 'layout', 'layout/default', 'lib', 'parsed', 'parsed/dev', 'parsed/prod', 'plugins' );

foreach ($new_directories as $new_directory)
{
	$status = @mkdir($target . $new_directory);
	//echo "mkdir $target$new_directory: status=" . var_export($status, true) . "<br>";
}

// copy files

if (!is_file($target . 'index.php'))
{
	$index = join('', file($fbx['fbx_root'] . 'skeleton/index.php'));
	
	$path_to_corefiles = fbx_relative_path($target, $fbx['fbx_root']);
	$index = str_replace('XXXX', $path_to_corefiles, $index);	
	$fp = fopen($target . 'index.php', 'w');
	fputs($fp, $index);
	fclose($fp);
}


copy($fbx['fbx_root'] . 'skeleton/settings_skel.php', $target . 'settings.php');

copy($fbx['fbx_root'] . 'skeleton/default.php', $target . 'controller/default.php');

copy($fbx['fbx_root'] . 'skeleton/debug.php', $target . 'plugins/debug.php');
copy($fbx['fbx_root'] . 'skeleton/profiler.php', $target . 'plugins/profiler.php');
copy($fbx['fbx_root'] . 'skeleton/queries.php', $target . 'plugins/queries.php');
copy($fbx['fbx_root'] . 'skeleton/menu.php', $target . 'plugins/menu.php');

copy($fbx['fbx_root'] . 'skeleton/lay_html.php', $target . 'layout/default/lay_html.php');

// helper function.  this could be moved up to the core files if it were used anywhere else

function fbx_relative_path($from_dir, $to_dir)
{
	// both paths must be absolute for this to work
	
	if (!($_SERVER['WINDIR'] && $from_dir[1] == ':' && $to_dir[1] == ':') && !(!$_SERVER['WINDIR'] && $from_dir[0] == '/' && $to_dir[0] == '/'))
	    fbx_error("Non-absolute path given to fbx_relative_path: $from_dir, $to_dir");
	    
	$to_array = explode('/', str_replace('\\', '/', $to_dir));
	$from_array = explode('/', str_replace('\\', '/', $from_dir));

	if (empty($to_array[count($to_array)-1])) array_pop($to_array);
	if (empty($from_array[count($from_array)-1])) array_pop($from_array);	
	
	$path = '';
	$stack = array();
	
	while (count($from_array) > count($to_array))
	{
		$path .= '../';
		array_pop($from_array);
	}
	
	while (count($to_array) > count($from_array))
		$stack[] = array_pop($to_array);
	
	if ($_SERVER['WINDIR'])
	{
		for ($i = 0; $i<count($to_array); $i++) $to_array[$i] = strtolower($to_array[$i]);
		for ($i = 0; $i<count($from_array); $i++) $from_array[$i] = strtolower($from_array[$i]);
	}

	while ($to_array != $from_array)
	{
		$path .= '../';
		$stack[] = array_pop($to_array);
		array_pop($from_array);
	}
	
	return($path . join('/', $stack));
}

// say something

?>

<style type="text/css">

body {
	font-family: 'Lucida Grande',Verdana,Arial,Helvetica,sans-serif;
	font-size: small;
	background-color: #688B9A;
	margin-top: 0px;
}

.maincontainer {
	width: 750px;
	background-color: white;
	padding: 5px;
	padding-left: 15px;
	padding-right: 15px;
	border-right: 1px solid #34454d;
	border-left: 1px solid #34454d;
	border-bottom: 1px solid #34454d;
}

.maincontainer2 {
	width: 782px;
	border-right: 1px solid #455c66;
	border-left: 1px solid #455c66;
	border-bottom: 1px solid #455c66;
}

.maincontainer3 {
	width: 784px;
	border-right: 1px solid #577380;
	border-left: 1px solid #577380;
	border-bottom: 1px solid #577380;
}

.maincontainer4 {
	width: 786px;
	border-right: 1px solid #5f7f8c;
	border-left: 1px solid #5f7f8c;
	border-bottom: 1px solid #5f7f8c;
}

h1 {
  margin-top: 10px;
  padding-left: 10px;
}

code, pre {
  font-size: 125%;
  color: #666;
}
  
a {
  color: #000;
}

.menu {
  margin-left: -15px;
  padding: 0px;
  background-color: #bcd58c;
  width: 750px;
  color: #fff;
  text-align: right;
  padding-right: 30px;
  padding-top: 2px;
  padding-bottom: 5px;
}
  
.menu a {
  color: #fff;
  text-decoration: none;
}
  
.menu a:hover {
  text-decoration: underline;
}
  
</style>

<div align="center" width="100%">
<div class="maincontainer4">
<div class="maincontainer3">
<div class="maincontainer2">
<div class="maincontainer" align="left"/>

<h1><img src="http://firebox.anjero.com/firebox logo 200px.png" alt="Firebox" style="border: 0; color: #688B9A;"></h1>

<div class="menu">
&nbsp;
</div>

<br>

Done!<br><br>

The Firebox skeleton project has been copied into<br>
<code><?=$fbx['site_root']?></code>  <br><br>

Click <a href='index.php'>here</a> to go back to the index page.
<br>
<br>

</div></div></div></div></div>
