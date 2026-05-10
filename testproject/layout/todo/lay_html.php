<?php fbx_execute_plugins('prehtml'); ?>
<?php if (isset($GLOBALS['argv'])) {
	if (isset($content['debugpanes'])) foreach ($content['debugpanes'] as $title => $contents) {
		echo "\n" . $title . ':' . "\n" . $contents . "\n";
	}
	echo "\n" . $content['body'];
} else { ?>
<html>
	<head>
		<title><?=$fbx['settings']['name']?></title>
		<?php if (isset($content['includes'])) { ?>
			<?=is_array($content['includes']) ? join("\n", $content['includes']) : $content['includes']?>
		<?php } ?>
	</head>
	<body>
		<?php if (!$fbx['production'] && isset($content['debugpanes']) && count($content['debugpanes'])) { ?>
		<div id="fbx_debug_bar" style="z-index: 10000; opacity: 0.9; position: absolute; top: 3px; right: 3px; height: 20px; background-color: #ccc; border: 1px solid #888; font-family: tahoma, sans-serif;">
			Firebox:
			<?php foreach ($content['debugpanes'] as $title => $contents) { ?>
			<a onclick="fbx_show_debug('<?=$title?>');" href="javascript:void(null);"><?=ucwords($title)?></a>
			<?php } ?>
			<a onclick="document.getElementById('fbx_debug_bar').style.display = 'none';" href="javascript:void(null);">X</a>
		</div>
		<?php foreach ($content['debugpanes'] as $title => $contents) { ?>
		<div id="fbx_debug_<?=$title?>" onclick="fbx_hide_debug();" style="z-index: 10001; opacity: 0.9; font-size: 0.9em; display: none; position: absolute; top: 26px; right:3px; background-color: #ccc; border: 1px solid #888; font-family: monospace;">
			<?=$contents?>
		</div>
		<?php } ?>
		<script>
			function fbx_show_debug(pane) {
				fbx_hide_debug();
				window.fbx_debug_pane = pane;
				document.getElementById('fbx_debug_' + pane).style.display = '';
			}
			function fbx_hide_debug() {
				if (window.fbx_debug_pane) {
					document.getElementById('fbx_debug_' + window.fbx_debug_pane).style.display = 'none';
				}
			}
		</script>
		<?php } ?>
		<?=$content['body']?>
	</body>
</html>
<?php } ?>
