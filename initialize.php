<?php

	// could be needed because of existing config.php
	function define_default($param, $value) {
		//
	}

	function make_password($length = 8) {

		$password = "";
		$possible = "0123456789abcdfghjkmnpqrstvwxyzABCDFGHJKMNPQRSTVWXYZ*%+^";

   	$i = 0;

		while ($i < $length) {
			$char = substr($possible, mt_rand(0, strlen($possible)-1), 1);

			if (!strstr($password, $char)) {
				$password .= $char;
				$i++;
			}
		}
		return $password;
	}

	function db_connect($host, $user, $pass, $db, $type, $port = false) {
		if ($type == "pgsql") {

			$string = "dbname=$db user=$user";

			if ($pass) {
				$string .= " password=$pass";
			}

			if ($host) {
				$string .= " host=$host";
			}

			if ($port) {
				$string = "$string port=" . $port;
			}

			$link = pg_connect($string);

			return $link;

		} else if ($type == "mysql") {
			if ($port)
				return mysqli_connect($host, $user, $pass, $db, $port);
			else
				return mysqli_connect($host, $user, $pass, $db);
		}
	}

	function make_config($DB_TYPE, $DB_HOST, $DB_USER, $DB_NAME, $DB_PASS,
			$DB_PORT, $SELF_URL_PATH) {

		$data = explode("\n", file_get_contents("../config.php-dist"));

		$rv = "";

		$finished = false;

		$crypt_key = make_password(24);

		foreach ($data as $line) {
			if (preg_match("/define\('DB_TYPE'/", $line)) {
				$rv .= "\tdefine('DB_TYPE', '$DB_TYPE');\n";
			} else if (preg_match("/define\('DB_HOST'/", $line)) {
				$rv .= "\tdefine('DB_HOST', '$DB_HOST');\n";
			} else if (preg_match("/define\('DB_USER'/", $line)) {
				$rv .= "\tdefine('DB_USER', '$DB_USER');\n";
			} else if (preg_match("/define\('DB_NAME'/", $line)) {
				$rv .= "\tdefine('DB_NAME', '$DB_NAME');\n";
			} else if (preg_match("/define\('DB_PASS'/", $line)) {
				$rv .= "\tdefine('DB_PASS', '$DB_PASS');\n";
			} else if (preg_match("/define\('DB_PORT'/", $line)) {
				$rv .= "\tdefine('DB_PORT', '$DB_PORT');\n";
			} else if (preg_match("/define\('SELF_URL_PATH'/", $line)) {
				$rv .= "\tdefine('SELF_URL_PATH', '$SELF_URL_PATH');\n";
			} else if (preg_match("/define\('FEED_CRYPT_KEY'/", $line)) {
				$rv .= "\tdefine('FEED_CRYPT_KEY', '$crypt_key');\n";
			} else if (!$finished) {
				$rv .= "$line\n";
			}

			if (preg_match("/\?\>/", $line)) {
				$finished = true;
			}
		}

		return $rv;
	}

	function db_query($link, $query, $type, $die_on_error = true) {
		if ($type == "pgsql") {
			$result = pg_query($link, $query);
			if (!$result) {
				$query = htmlspecialchars($query); // just in case
				if ($die_on_error) {
					die("Query <i>$query</i> failed [$result]: " . ($link ? pg_last_error($link) : "No connection"));
				}
			}
			return $result;
		} else if ($type == "mysql") {

			$result = mysqli_query($link, $query);

			if (!$result) {
				$query = htmlspecialchars($query);
				if ($die_on_error) {
					die("Query <i>$query</i> failed: " . ($link ? mysqli_error($link) : "No connection"));
				}
			}
			return $result;
		}
	}

	function make_self_url_path() {
		$url_path = ((!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != "on") ? 'http://' :  'https://') . $_SERVER["HTTP_HOST"] . parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

		return $url_path;
	}



	if (!$SELF_URL_PATH) {
		$SELF_URL_PATH = preg_replace("/\/install\/$/", "/", make_self_url_path());
	}

	function check_database() {

		$link = db_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_TYPE, $DB_PORT);

		if (!$link) {
			return false;
		}

		$result = @db_query($link, "SELECT true FROM ttrss_feeds", $DB_TYPE, false);
		if ($result) {
			return true;
		}
		return true;
	}

	function install_schema() {

		$link = db_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_TYPE, $DB_PORT);

		if (!$link) {
			exit;
		}

		$lines = explode(";", preg_replace("/[\r\n]/", "", file_get_contents("../schema/ttrss_schema_".basename($DB_TYPE).".sql")));
		foreach ($lines as $line) {
			if (strpos($line, "--") !== 0 && $line) {
				db_query($link, $line, $DB_TYPE);
			}
		}
	}

	function install_config() {
		if (!file_exists("../config.php")) {
			$fp = fopen("../config.php", "w");
			if ($fp) {
				$written = fwrite($fp, make_config($DB_TYPE, $DB_HOST,
					$DB_USER, $DB_NAME, $DB_PASS,
					$DB_PORT, $SELF_URL_PATH));
				fclose($fp);
			} else {
				echo "Unable to open config.php in tt-rss directory for writing.";
			}
		} else {
			echo  "config.php already present in tt-rss directory, refusing to overwrite.";
		}
	}

	$DB_HOST = getenv('DB_HOST');
	$DB_TYPE = getenv('DB_TYPE');
	$DB_USER = getenv('DB_USER');
	$DB_NAME = getenv('DB_NAME');
	$DB_PASS = getenv('DB_PASS');
	$DB_PORT = getenv('DB_PORT');
	$SELF_URL_PATH = strip_tags($_POST['SELF_URL_PATH']);
?>

