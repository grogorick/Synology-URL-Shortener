<?php

define("CONFIG_FILE", "urls.txt");

define("CONFIG_DSM_SERVER", "#####.synology.me:5001");
define("CONFIG_SESSION_TIMEOUT", 1800);



function print_header() {
?>
<!DOCTYPE html>
<html lang="de" xml:lang="de">
<head>
	<meta charset="utf-8">
	<meta name="robots" content="noindex,nofollow" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>Links</title>
	<meta name="description" content="private website" />
	<link rel="stylesheet" href="style/general.css">
	<style type="text/css">
	</style>
</head>
<body>
<?php
}

function print_footer() {
?>
</body>
</html>
<?php
}

function show_message($msg) {
?>
	<section>
	<span class="message"><?=$msg?></span>
	</section>
<?php
}



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
	print_header();
	show_message("Not found: " . $name);
	print_footer();
	exit;
}



session_start();

print_header();

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
		show_message("Login incorrect");
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
	<section>
		<form action="" method="post">
			<input type="text" name="user" placeholder="Nutzer" autofocus />
			<input type="password" name="pass" placeholder="Passwort" />
			<input type="submit" value="&rarr;" class="button" />
		</form>
	</section>
<?php
}



if (isset($_SESSION["auth"])) {
	if (isset($_POST["add"]) && isset($_POST["name"]) && !empty(trim($_POST["name"])) && isset($_POST["url"]) && !empty(trim($_POST["url"]))) {
		$new_url = trim($_POST["url"]);
		if (preg_match("#http://gofile[.]me/(.+)/(.+)#", $new_url, $matches)) {
			$new_url = "https://" . CONFIG_DSM_SERVER . "/sharing/" . $matches[2];
		}
		$urls[trim($_POST["name"])] = $new_url;
		file_put_contents(CONFIG_FILE, trim($_POST["name"]) . " " . trim($_POST["url"]) . "\n", FILE_APPEND);
		show_message("Added: " . $_POST["name"]);
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
		show_message("Removed: " . $_POST["name"]);
	}
}

if (isset($_SESSION["auth"])) {
	?>
	<section>
		<?=$_SESSION["user"]?> &nbsp;
		<form action="" method="post">
			<input type="submit" name="logout" value="ausloggen" class="button" />
		</form>
		<hr />
	</section>

	<section>
		Neuen Link hinzufügen:
		<form action="" method="post">
			<div style="display: inline-block;"><input type="text" name="name" required pattern="[a-zA-Z0-9\-]+" placeholder="Name (Buchstaben, Zahlen, -)" style="width: 200px;" /> &nbsp;</div>
			<div style="display: inline-block;"> &nbsp; <input type="text" name="url" required placeholder="URL (https://#####.synology.me/photo/share/... oder http://gofile.me/... oder ...)" style="width: 500px;" /> &nbsp;</div>
			<input type="submit" name="add" value="speichern" class="button" />
		</form>
	</section>
	<?php
}

if (isset($_SESSION["auth"])) {
?>
	<section>
		<table>
<?php
  $second_row = TRUE;
  foreach ($urls as $key => $url) {
	?>
			<tr class="<?=($second_row = !$second_row) ? "second_row" : ""?>">
	<?php
    if (isset($_SESSION["auth"])) {
?>
				<td>
					<form action="" method="post" style="display: inline;">
						<input type="hidden" name="name" value="<?=$key?>" />
						<input type="submit" name="delete" value="X" class="button" onclick="return confirm('<?=$key?>\nwirklich löschen?');" />
					</form>
				</td>
		<?php
    }
	?>
				<td><a href="https://link.yournicedyndnsdomain.com/?<?=$key?>"><?=$key?></a></td>
	<?php
    if (isset($_SESSION["auth"])) {
		?>
				<td>&nbsp; &#x21E2; &nbsp; <?=$url?></td>
		<?php
    }
	?>
			</tr>
	<?php
  }
?>
		</table>
	</section>
<?php
}

print_footer();
?>
