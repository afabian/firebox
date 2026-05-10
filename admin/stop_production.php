<?php

global $fbx;

unlink($fbx['site_root'] . 'parsed/production') or fbx_error("Couldn't unlink parsed/production");

?>
Site is now in development mode.