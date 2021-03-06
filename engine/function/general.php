<?php
// Fetch and sanitize POST and GET values
function getValue($value) {
	return (!empty($value)) ? sanitize($value) : false;
}

function SendGet($getArray, $location = 'error.php') {
	$string = "";
	$count = 0;
	foreach ($getArray as $getKey => $getValue) {
		if ($count > 0) $string .= '&';
		$string .= "{$getKey}={$getValue}";
	}
	header("Location: {$location}?{$string}");
	exit();
}

// Sweet error reporting
function data_dump($print = false, $var = false, $title = false) {
	if ($title !== false) echo "<pre><font color='red' size='5'>$title</font><br>";
	else echo '<pre>';
	if ($print !== false) {
		echo 'Print: - ';
		print_r($print);
		echo "<br>";
	}
	if ($var !== false) {
		echo 'Var_dump: - ';
		var_dump($var);
	}
	echo '</pre><br>';
}

function accountAccess($accountId, $TFS) {
	$accountId = (int)$accountId;
	$access = 0;

	// TFS 0.3/4
	$yourChars = mysql_select_multi("SELECT `name`, `group_id`, `account_id` FROM `players` WHERE `account_id`='$accountId';");
	if ($yourChars !== false) {
		foreach ($yourChars as $char) {
			if ($TFS === 'OTH') {
				if ($char['group_id'] > $access) $access = $char['group_id'];
			} else {
				if ($char['group_id'] > 1) {
					if ($access == 0) {
						$acc = mysql_select_single("SELECT `type` FROM `accounts` WHERE `id`='". $char['account_id'] ."' LIMIT 1;");
						$access = $acc['type'];
					}
				}
			}
		}
		if ($access == 0) $access++;
		return $access;
	} else return false;
	//
}
// Generate recovery key
function generate_recovery_key($lenght) {
	$lenght = (int)$lenght;
	$tmp = rand(1000, 9000);
	$tmp += time();
	$tmp = sha1($tmp);
	
	$results = '';
	for ($i = 0; $i < $lenght; $i++) $results = $results.''.$tmp[$i];
	
	return $results;
}

// Calculate discount
function calculate_discount($orig, $new) {
	$orig = (int)$orig;
	$new = (int)$new;
	
	$tmp = '';
	if ($new >= $orig) {
		if ($new != $orig) {
			$calc = ($new/$orig) - 1;
			$calc *= 100;
			$tmp = '+'. $calc .'%';
		} else $tmp = '0%';
	} else {
		$calc = 1 - ($new/$orig);
		$calc *= 100;
		$tmp = '-'. $calc .'%';
	}
	return $tmp;
}

// Proper URLs
function url($path = false) {
	$protocol = (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://';
	$domain   = $_SERVER['SERVER_NAME'] . ($_SERVER['SERVER_PORT'] != 80 ? ':' . $_SERVER['SERVER_PORT'] : null);
	$folder   = dirname($_SERVER['SCRIPT_NAME']);

	return $protocol . $domain . ($folder == '/' ? '' : $folder) . '/' . $path;
}

function getCache() {
	$results = mysql_select_single("SELECT `cached` FROM `znote`;");
	return ($results !== false) ? $results['cached'] : false;
}

function setCache($time) {
	$time = (int)$time;
	mysql_update("UPDATE `znote` set `cached`='$time'");
}

// Get visitor basic data
function znote_visitors_get_data() {
	return mysql_select_multi("SELECT `ip`, `value` FROM `znote_visitors`");
}

// Set visitor basic data
function znote_visitor_set_data($visitor_data) {
	$exist = false;
	$ip = ip2long(getIP());
	
	foreach ((array)$visitor_data as $row) {
		if ($ip == $row['ip']) {
			$exist = true;
			$value = $row['value'];
		}
	}
	
	if ($exist && isset($value)) {
		// Update the value
		$value++;
		mysql_update("UPDATE `znote_visitors` SET `value` = '$value' WHERE `ip` = '$ip'");
	} else {
		// Insert new row
		mysql_insert("INSERT INTO `znote_visitors` (`ip`, `value`) VALUES ('$ip', '1')");
	}
}

// Get visitor basic data
function znote_visitors_get_detailed_data($cache_time) {
	$period = (int)time() - (int)$cache_time;
	return mysql_select_multi("SELECT `ip`, `time`, `type`, `account_id` FROM `znote_visitors_details` WHERE `time` >= '$period' LIMIT 0, 50");
}

function znote_visitor_insert_detailed_data($type) {
	$type = (int)$type;
	/*
	type 0 = normal visits
	type 1 = register form
	type 2 = character creation
	type 3 = fetch highscores
	type 4 = search character
	*/
	$time = time();
	$ip = ip2long(getIP());
	if (user_logged_in()) {
		$acc = $_SESSION['user_id'];
		mysql_insert("INSERT INTO `znote_visitors_details` (`ip`, `time`, `type`, `account_id`) VALUES ('$ip', '$time', '$type', '$acc')");
	} else mysql_insert("INSERT INTO `znote_visitors_details` (`ip`, `time`, `type`, `account_id`) VALUES ('$ip', '$time', '$type', '0')");
}

function something () {
	// Make acc data compatible:
	$ip = ip2long(getIP());
}

// Secret token
function create_token() {
	echo 'Checking whether to create token or not<br />';
	#if (empty($_SESSION['token'])) {
		echo 'Creating token<br />';
		$token = sha1(uniqid(time(), true));
		$token2 = $token;
		var_dump($token, $token2);
		$_SESSION['token'] = $token2;
	#}
	
	echo "<input type=\"hidden\" name=\"token\" value=\"". $_SESSION['token'] ."\" />";
}
function reset_token() {
	echo 'Reseting token<br />';
	unset($_SESSION['token']);
}

// Time based functions
// 60 seconds to 1 minute
function second_to_minute($seconds) {
	return ($seconds / 60);
}

// 1 minute to 60 seconds
function minute_to_seconds($minutes) {
	return ($minutes * 60);
}

// 60 minutes to 1 hour
function minute_to_hour($minutes) {
	return ($minutes / 60);
}

// 1 hour to 60 minutes
function hour_to_minute($hours) {
	return ($hour * 60);
}

// seconds / 60 / 60 = hours.
function seconds_to_hours($seconds) {
	$minutes = second_to_minute($seconds);
	$hours = minute_to_hour($minutes);
	return $hours;
}

function remaining_seconds_to_clock($seconds) {
	return date("(H:i)",time() + $seconds);
}

// Returns false if name contains more than configured max words, returns name otherwise.
function validate_name($string) {
	//edit: make sure only one space separates words: 
	//(found this regex through a search and havent tested it)
	$string  = preg_replace("/\\s+/", " ", $string);

	//trim off beginning and end spaces;
	$string = trim($string);

	//get an array of the words
	$wordArray = explode(" ", $string);

	//get the word count
	$wordCount = sizeof($wordArray);

	//see if its too big
	if($wordCount > config('maxW')) {
		return false;
	} else {
		return $string;
	}
}

// Checks if an IPv4(or localhost IPv6) address is valid
function validate_ip($ip) {
	$ipL = ip2long($ip);
	$ipR = long2ip($ipL);
	
	if ($ip === $ipR) {
		return true;
	} elseif ($ip=='::1')  {
		return true;
	} else {
		return false;
	}
}

// Fetch a config value. Etc config('vocations') will return vocation array from config.php.
function config($value) {
	global $config;
	return $config[$value];
}

// Some functions uses several configurations from config.php, so it sounds
// smarter to give them the whole array instead of calling the function all the time.
function fullConfig() {
	global $config;
	return $config;
}

// Capitalize Every Word In String.
function format_character_name($name) {
	return ucwords(strtolower($name));
}

// Returns a list of players online
function online_list() {
	if (config('TFSVersion') == 'TFS_10') return mysql_select_multi("SELECT `o`.`player_id` AS `id`, `p`.`name` as `name`, `p`.`level` as `level`, `p`.`vocation` as `vocation`, `g`.`name` as `gname` FROM `players_online` as `o` INNER JOIN `players` as `p` ON `o`.`player_id` = `p`.`id` LEFT JOIN `guild_membership` gm ON `o`.`player_id` = `gm`.`player_id` LEFT JOIN `guilds` g ON `gm`.`guild_id` = `g`.`id`");
	else return mysql_select_multi("SELECT `p`.`name` as `name`, `p`.`level` as `level`, `p`.`vocation` as `vocation`, `g`.`name` as `gname` FROM `players` p LEFT JOIN `guild_ranks` gr ON `gr`.`id` = `p`.`rank_id` LEFT JOIN `guilds` g ON `gr`.`guild_id` = `g`.`id` WHERE `p`.`online` = '1' ORDER BY `p`.`name` DESC;");
}

// Gets you the actual IP address even from users behind ISP proxies and so on.
function getIP() {
	/*
  $IP = '';
  if (getenv('HTTP_CLIENT_IP')) {
    $IP =getenv('HTTP_CLIENT_IP');
  } elseif (getenv('HTTP_X_FORWARDED_FOR')) {
    $IP =getenv('HTTP_X_FORWARDED_FOR');
  } elseif (getenv('HTTP_X_FORWARDED')) {
    $IP =getenv('HTTP_X_FORWARDED');
  } elseif (getenv('HTTP_FORWARDED_FOR')) {
    $IP =getenv('HTTP_FORWARDED_FOR');
  } elseif (getenv('HTTP_FORWARDED')) {
    $IP = getenv('HTTP_FORWARDED');
  } else {
    $IP = $_SERVER['REMOTE_ADDR'];
  } */
return $_SERVER['REMOTE_ADDR'];
}

// Deprecated, just use count($array) instead.
function array_length($ar) {
	$r = 1;
	foreach($ar as $a) {
		$r++;
	}
	return $r;
}
// Parameter: level, returns experience for that level from an experience table.
function level_to_experience($level) {
	return 50/3*(pow($level, 3) - 6*pow($level, 2) + 17*$level - 12);
}

// Parameter: players.hide_char returns: Status word inside a font with class identifier so it can be designed later on by CSS.
function hide_char_to_name($id) {
	$id = (int)$id;
	if ($id == 1) {
		return 'hidden';
	} else {
		return 'visible';
	}
}

// Parameter: players.online returns: Status word inside a font with class identifier so it can be designed later on by CSS.
function online_id_to_name($id) {
	$id = (int)$id;
	if ($id == 1) {
		return '<font class="status_online">ONLINE</font>';
	} else {
		return '<font class="status_offline">offline</font>';
	}
}

// Parameter: players.vocation_id. Returns: Configured vocation name.
function vocation_id_to_name($id) {
	$vocations = config('vocations');
	return ($vocations[$id] >= 0) ? $vocations[$id] : false;
}

function gender_exist($gender) {
	// Range of allowed gender ids, fromid toid
	if ($gender >= 0 && $gender <= 1) {
		return true;
	} else {
		return false;
	}
}

function skillid_to_name($skillid) {
	$skillname = array(
		0 => 'fist fighting',
		1 => 'club fighting',
		2 => 'sword fighting',
		3 => 'axe fighting',
		4 => 'distance fighting',
		5 => 'shielding',
		6 => 'fishing',
		7 => 'experience', // Hardcoded, does not actually exist in database as a skillid.
		8 => 'magic level' // Hardcoded, does not actually exist in database as a skillid.
	);

	return ($skillname[$skillid] >= 0) ? $skillname[$skillid] : false;
}

// Parameter: players.town_id. Returns: Configured town name.
function town_id_to_name($id) {
	$towns = config('towns');
	return (array_key_exists($id, $towns)) ? $towns[$id] : 'Missing Town';
}

// Parameter: players.world_id. Returns: Configured world name.
function world_id_to_name($id) {
	$worlds = config('worlds');
	return (array_key_exists($id, $worlds)) ? $worlds[$id] : 'Missing World';
}


// Unless you have an internal mail server then mail sending will not be supported in this version.
function email($to, $subject, $body) {
	mail($to, $subject, $body, 'From: TEST');
}

function logged_in_redirect() {
	if (user_logged_in() === true) {
		header('Location: myaccount.php');
	}
}

function protect_page() {
	if (user_logged_in() === false) {
		header('Location: protected.php');
		exit();
	}
}

// When function is called, you will be redirected to protect_page and deny access to rest of page, as long as you are not admin.
function admin_only($user_data) {	
	// Chris way
	$gotAccess = is_admin($user_data);
	
	if ($gotAccess == false) {
		logged_in_redirect();
		exit();
	}
}

function is_admin($user_data) {
	return in_array($user_data['id'], config('page_admin_access')) ? true : false;
}

function array_sanitize(&$item) {
	$item = htmlentities(strip_tags(mysql_znote_escape_string($item)));
}

function sanitize($data) {
	return htmlentities(strip_tags(mysql_znote_escape_string($data)));
}

function output_errors($errors) {
	return '<ul><li>'. implode('</li><li>', $errors) .'</li></ul>';
}

// Resize images

function resize_imagex($file, $width, $height) {

	list($w, $h) = getimagesize($file['tmp']);

	$ratio = max($width/$w, $height/$h);
	$h = ceil($height / $ratio);
	$x = ($w - $width / $ratio) / 2;
	$w = ceil($width / $ratio);

	$path = 'engine/guildimg/'.$file['new_name'];

	$imgString = file_get_contents($file['tmp']);

	$image = imagecreatefromstring($imgString);
	$tmp = imagecreatetruecolor($width, $height);
	imagecopyresampled($tmp, $image,
	    0, 0,
	    $x, 0,
	    $width, $height,
	    $w, $h);

	imagegif($tmp, $path);
	imagedestroy($image);
	imagedestroy($tmp);

	return true;
}

// Guild logo upload security
function check_image($image) {

	$image_data = array('new_name' => $_GET['name'].'.gif', 'name' => $image['name'], 'tmp' => $image['tmp_name'], 'error' => $image['error'], 'size' => $image['size'], 'type' => $image['type']);

	// First security check, quite useless but still do its job
	if ($image_data['type'] === 'image/gif') {

		// Second security check, lets go
		$check = getimagesize($image_data['tmp']);

		if ($check) {

			// Third
			if ($check['mime'] === 'image/gif') {

				$path_info = pathinfo($image_data['name']);

				// Last one
				if ($path_info['extension'] === 'gif') {
					
					// Resize image
					$img = resize_imagex($image_data, 100, 100);

					if ($img) {

						header('Location: guilds.php?name='. $_GET['name']);
						exit();
					}

				} else {

					header('Location: guilds.php?error=Only gif images accepted, you uploaded:['.$path_info['extension'].'].&name='. $_GET['name']);
					exit();
				}

			} else {

				header('Location: guilds.php?error=Only gif images accepted, you uploaded:['.$check['mime'].'].&name='. $_GET['name']);
				exit();
			}

		} else {

			header('Location: guilds.php?error=Uploaded image is invalid.&name='. $_GET['name']);
			exit();
		}

	} else {

		header('Location: guilds.php?error=Only gif images are accepted, you uploaded:['.$image_data['type'].'].&name='. $_GET['name']);
		exit();
	}
}

// Check guild logo
function logo_exists($guild) {

	if (file_exists('engine/guildimg/'.$guild.'.gif')) {

		echo'engine/guildimg/'.$guild.'.gif';

	} else {

		echo'engine/guildimg/default@logo.gif';
	}
}

?>