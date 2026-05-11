<?php

global $fbx;

// create the skeleton project

$new_directories = array( 'controller', 'model', 'model/todo', 'view', 'view/todo', 'layout', 'layout/todo', 'lib', 'parsed', 'parsed/dev', 'parsed/prod', 'plugins' );

foreach ($new_directories as $new_directory)
{
	@mkdir($fbx['site_root'] . $new_directory);
}

// copy files

if (!is_file($fbx['site_root'] . 'index.php'))
{
	$index = file_get_contents($fbx['fbx_root'] . 'skeleton/index.php');
	$index = str_replace('XXXX', fbx_relative_path($fbx['site_root'], $fbx['fbx_root']), $index);
	file_put_contents($fbx['site_root'] . 'index.php', $index);
}

copy($fbx['fbx_root'] . 'skeleton/settings_todo.php', $fbx['site_root'] . 'settings.php');

copy($fbx['fbx_root'] . 'skeleton/todo.php', $fbx['site_root'] . 'controller/todo.php');

copy($fbx['fbx_root'] . 'skeleton/debug.php', $fbx['site_root'] . 'plugins/debug.php');
copy($fbx['fbx_root'] . 'skeleton/profiler.php', $fbx['site_root'] . 'plugins/profiler.php');
copy($fbx['fbx_root'] . 'skeleton/queries.php', $fbx['site_root'] . 'plugins/queries.php');
copy($fbx['fbx_root'] . 'skeleton/menu.php', $fbx['site_root'] . 'plugins/menu.php');

copy($fbx['fbx_root'] . 'skeleton/qry_s_todo_list.php', $fbx['site_root'] . 'model/todo/qry_s_todo_list.php');
copy($fbx['fbx_root'] . 'skeleton/qry_s_todo_item.php', $fbx['site_root'] . 'model/todo/qry_s_todo_item.php');
copy($fbx['fbx_root'] . 'skeleton/qry_i_todo_item.php', $fbx['site_root'] . 'model/todo/qry_i_todo_item.php');
copy($fbx['fbx_root'] . 'skeleton/qry_u_todo_item.php', $fbx['site_root'] . 'model/todo/qry_u_todo_item.php');
copy($fbx['fbx_root'] . 'skeleton/qry_u_todo_item_done.php', $fbx['site_root'] . 'model/todo/qry_u_todo_item_done.php');
copy($fbx['fbx_root'] . 'skeleton/define_datafile.php', $fbx['site_root'] . 'model/todo/define_datafile.php');
copy($fbx['fbx_root'] . 'skeleton/sort_array_by_key.php', $fbx['site_root'] . 'model/todo/sort_array_by_key.php');

copy($fbx['fbx_root'] . 'skeleton/item_detail.php', $fbx['site_root'] . 'view/todo/item_detail.php');
copy($fbx['fbx_root'] . 'skeleton/todo_list.php', $fbx['site_root'] . 'view/todo/todo_list.php');
copy($fbx['fbx_root'] . 'skeleton/todo_list_item.php', $fbx['site_root'] . 'view/todo/todo_list_item.php');

copy($fbx['fbx_root'] . 'skeleton/lay_html.php', $fbx['site_root'] . 'layout/todo/lay_html.php');

copy($fbx['fbx_root'] . 'skeleton/todo.data', $fbx['site_root'] . 'todo.data');

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

The Firebox demo project has been copied into<br>
<code><?=$fbx['site_root']?></code>  <br><br>

Click <a href='index.php'>here</a> to go back to the index page.
<br>
<br>

</div></div></div></div></div>
