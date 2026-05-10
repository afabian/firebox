<?php

// $fbx, and $content are available inside of controller functions
// controllers must call each other via control(), not directly

function pre()
{
}

function show_home()
{
	echo layout('lay_html');
}

function post()
{
}
