<?php

fbx_plugin_register('prehtml', 'fbx_menu');

function fbx_menu($item)
{
	global $fbx, $content, $myself;
	$menu_parts = array();
	if (isset($fbx['menu']) && count($fbx['menu'])) foreach ($fbx['menu'] as $menu_item)
	{
		$menu_parts[] = "<a href='" . $myself . $menu_item['action'] . "'>" . $menu_item['title'] . "</a>";
	}
	$content['debugpanes']['menu']
		= "Firebox Admin Menu<br><br>"
		. "<a href='" . $myself . "fbx.start_production'>Start Production</a><br>"
		. "<a href='" . $myself . "fbx.stop_production'>Start Development</a><br>"
		. "<br>"
		. (count($menu_parts) ? join("<br>", $menu_parts) . "<br><br>" : "")
		. "<a href='" . $myself . "fbx.about'>About Firebox</a><br>";
}

function fbx_menu_register($title, $action)
{
	global $fbx;
	fbx_debug("Adding menu item $title => $action", __FILE__, __LINE__);
	$fbx['menu'][] = array('title' => $title, 'action' => $action);
}
