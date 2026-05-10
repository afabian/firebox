<? // These variables are set:
   // $fbx
   // $content
   
  fbx_execute_plugins('prehtml');
?>
<? if (isset($GLOBALS['argv'])) { 
	if (isset($content['debugpanes'])) foreach ($content['debugpanes'] as $title => $contents) { 
		echo "\n" . $title . ':' . "\n" . $contents . "\n";
	}
	echo "\n" . $content['body']; 
} else { ?>

<html>
	<head>
		<title><?=$fbx['settings']['name']?></title>
		<? if (isset($content['includes'])) { ?>
			<?=is_array($content['includes'])?join("\n", $content['includes']):$content['includes']?>
		<? } ?>
	</head>
	<body>
		<? if (!$fbx['production'] && count($content['debugpanes'])) { ?>
		<div id="fbx_debug_bar" style="z-index: 10000; opacity: 0.9; position: absolute; top: 3px; right: 3px; height: 20px; background-color: #ccc; border: 1px solid #888; font-family: tahoma, sans-serif;">
			Firebox:
			<? if (count($content['debugpanes'])) foreach ($content['debugpanes'] as $title => $contents) { ?>
			<a onclick="fbx_show_debug('<?=$title?>');" href="javascript:void(null);"><?=ucwords($title)?></a>
			<? } ?>
			<a onclick="document.getElementById('fbx_debug_bar').style.display = 'none';" href="javascript:void(null);">X</a>
		</div>
		<? if (count($content['debugpanes'])) foreach ($content['debugpanes'] as $title => $contents) { ?>
		<div id="fbx_debug_<?=$title?>" onclick="fbx_hide_debug();" style="z-index: 10001; opacity: 0.9; font-size: 0.9em; display: none; position: absolute; top: 26px; right:3px; background-color: #ccc; border: 1px solid #888; font-family: monospace;">
			<?=$contents?>
		</div>
		<? } ?>
		<script language="Javascript">
			function fbx_show_debug(pane)
			{
				fbx_hide_debug();
				window.fbx_debug_pane = pane;
				document.getElementById('fbx_debug_' + pane).style.display = '';
			}
			function fbx_hide_debug()
			{
				if (window.fbx_debug_pane)
				{
					document.getElementById('fbx_debug_' + window.fbx_debug_pane).style.display = 'none';
				}
			}
			function fbx_append_debug(pane, message)
			{
				var elem = document.getElementById('fbx_debug_' + pane);
				if (elem)
				{
					elem.innerHTML = elem.innerHTML + "<br>----<br>" + message;
				}
			}
		</script>
		<? } ?>
		<?=$content['body']?>
	</body>
</html>
<? } ?>
