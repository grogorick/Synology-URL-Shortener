<?php

define("CONFIG_FILE", "urls.txt");

define("CONFIG_DSM_SERVER", "#####.synology.me:5001");
define("CONFIG_SESSION_TIMEOUT", 1800);



$urls = array();
$lines = array_filter(explode("\n", file_get_contents(CONFIG_FILE)));
foreach ($lines as $line) {	
	$url = explode(" ", $line);
	$urls[$url[0]] = $url[1];
}

if (count($_GET)) {
	$name = trim(array_keys($_GET)[0]);
	foreach ($urls as $key => $url) {
		if ($key === $name) {
			header("Location: " . $url);
			exit;
		}
	}
	echo "Not found: " . $name . "<hr />";
}



session_start();

?>
<!DOCTYPE html>
<html lang="de" xml:lang="de">
  <head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex,nofollow" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Links</title>
    <meta name="description" content="private website" />
  </head>
  <body>
<?php

if (!isset($_SESSION["auth"]) && isset($_POST["user"]) && isset($_POST["pass"])) {
	$url = "https://" . CONFIG_DSM_SERVER . "/webapi/auth.cgi?api=SYNO.API.Auth&version=3&session=FileStation&method=login&account=" . $_POST["user"] . "&passwd=" . rawurlencode($_POST["pass"]) . "&format=cookie";
	$response = file_get_contents($url);
	$json = json_decode($response);
	if ($json->success) {
		file_get_contents("https://" . CONFIG_DSM_SERVER . "/webapi/auth.cgi?api=SYNO.API.Auth&version=1&session=FileStation&method=logout");
		session_regenerate_id(true);
		$_SESSION["user"] = $_POST["user"];
		$_SESSION["auth"] = time();
	}
	else {
		echo "Login incorrect<hr />";
	}
}

if (isset($_SESSION["auth"])) {
	if (time() - $_SESSION["auth"] > CONFIG_SESSION_TIMEOUT || isset($_POST["logout"])) {
		session_unset();
	}
	else {
		$_SESSION["auth"] = time();
	}
}

if (!isset($_SESSION["auth"])) {
	?>
	<form action="" method="post">
		<input type="text" name="user" placeholder="Nutzer" autofocus />
		<input type="password" name="pass" placeholder="Passwort" />
		<input type="submit" value="&rarr;" class="button" />
	</form>
	<?php
}



if (isset($_SESSION["auth"])) {
	if (isset($_POST["add"]) && isset($_POST["name"]) && !empty(trim($_POST["name"])) && isset($_POST["url"]) && !empty(trim($_POST["url"]))) {
		$urls[trim($_POST["name"])] = trim($_POST["url"]);
		file_put_contents(CONFIG_FILE, trim($_POST["name"]) . " " . trim($_POST["url"]) . "\n", FILE_APPEND);
		echo "Added: " . $_POST["name"] . "<hr />";
	}

	if (isset($_POST["delete"]) && isset($_POST["name"])) {
		$filecontent = "";
		foreach ($urls as $key => $url) {
			if ($key !== $_POST["name"]) {
				$filecontent .= $key . " " . $url . "\n";
			}
		}
		unset($urls[$_POST["name"]]);
		file_put_contents(CONFIG_FILE, $filecontent);
		echo "Removed: " . $_POST["name"] . "<hr />";
	}
}

if (isset($_SESSION["auth"])) {
	?>
	<form action="" method="post">
		<input type="submit" name="logout" value="logout" class="button" />
	</form>
	<form action="" method="post">
		<div style="display: inline-block;">Name: <input type="text" name="name" required pattern="[a-zA-Z0-9\-]+" placeholder="Nur Buchstaben und Zahlen" style="width: 200px;" /> &nbsp;</div>
		<div style="display: inline-block;">URL: <input type="text" name="url" required placeholder="https://#####.synology.me/photo/share/" style="width: 400px;" /> &nbsp;</div>
		<input type="submit" name="add" value="Speichern" />
	</form>
	<?php
}
foreach ($urls as $key => $url) {
	if (isset($_SESSION["auth"])) {
		?>
		<form action="" method="post" style="display: inline;">
			<input type="hidden" name="name" value="<?=$key?>" />
			<input type="submit" name="delete" value="X" onclick="return confirm('<?=$key?>\nwirklich löschen?');" />
		</form>
		<?php
	}
  ?>
	<a href="https://link.yournicedyndnsdomain.com/?<?=$key?>"><?=$key?></a> &nbsp; &#x21E2; &nbsp; <?=$url?><br />
  <?php
}
?>
  </body>
</html>
