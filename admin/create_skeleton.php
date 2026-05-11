<?php

global $fbx;

$target = (isset($_REQUEST['target']) && $_REQUEST['target']) ? $_REQUEST['target'] . '/' : $fbx['site_root'];

if (!is_dir($target)) mkdir($target, 0755, true);

// create the skeleton project

$new_directories = array( 'controller', 'model', 'model/default', 'view', 'view/default', 'layout', 'layout/default', 'lib', 'parsed', 'parsed/dev', 'parsed/prod', 'plugins' );

foreach ($new_directories as $new_directory)
{
	@mkdir($target . $new_directory);
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

// say something — fbx_relative_path() is defined in firebox_controller_functions.php

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
