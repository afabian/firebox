<?php 

$text_error = false;
if (isset($GLOBALS['argv'])) $text_error = true;
if (substr($_REQUEST['go'], 0, 4) == 'api.') $text_error = true;

if ($text_error) { 
	echo "Error: " . $fbx['error_message'] . "\n"; 
} 

else { ?>
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
  padding-left: 25px;
  padding-top: 2px;
  padding-bottom: 5px;
  margin-top: 120px;
  height: 20px;
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

<div class="menu">
<img src="images/andy-fabian-web-programming.png" style="border: 0; position: relative; top: -110px;">
</div>

<br>

<?php global $fbx; ?>
<div style="margin-left: 15px; margin-top: 20px;">
Error: <?=$fbx['error_message']?>
</div>

<br><br>

</div></div></div></div></div>
<?php } ?>
