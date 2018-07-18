<?php

$file = "urls.txt";

if (isset($_POST["name"]) && isset($_POST["url"]) && !empty(trim($_POST["name"])) && !empty(trim($_POST["url"]))) {
	$line = trim($_POST["name"]) . " " . trim($_POST["url"]) . "\n";
	$t = file_put_contents($file, $line, FILE_APPEND);
#	header("Location: /");
#	exit;
}

$urls = array();
$lines = array_filter(explode("\n", file_get_contents($file)));
foreach ($lines as $line) {	
	$url = explode(' ', $line);
	$urls[$url[0]] = $url[1];
}

if (count($_GET) && !isset($_GET["add"])) {
	$name = trim(array_keys($_GET)[0]);
	
	foreach ($urls as $key => $url) {
		if ($key === $name) {
			header("Location: " . $url);
			exit;
		}
	}
}

else {
	if (isset($_GET["add"])) {
?>
		
		<form action="/link/" method="post">
			<div style="display: inline-block;">Name: <input type="text" name="name" required pattern="[a-zA-Z0-9\-]+" placeholder="Nur Buchstaben und Zahlen" style="width: 200px;" /> &nbsp;</div>
			<div style="display: inline-block;">URL: <input type="text" name="url" required placeholder="https://#####.synology.me/photo/share/" style="width: 400px;" /> &nbsp;</div>
			<input type="submit" value="Speichern" />
		</form>
		
<?php
	}
	foreach ($urls as $key => $url) {
?>
		<a href="https://#####.synology.me/link/?<?=$key?>"><?=$key?></a> &nbsp; &#x21E2; &nbsp; <?=$url?><br />
<?php
	}
}
?>

