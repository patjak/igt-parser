<?php

function msg($msg, $eol = TRUE) { echo $msg; if ($eol) { echo "\n"; } }
function fatal($msg) { echo "\e[31m".$msg."\e[0m\n"; if (Globals::$debug) { debug_print_backtrace(); } exit(1); }
function error($msg, $eol = TRUE) { echo "\e[31m".$msg."\e[0m"; if ($eol) { echo "\n"; }}
function info($msg, $eol = TRUE) { echo "\e[36m".$msg."\e[0m"; if ($eol) { echo "\n"; }}
function green($msg, $eol = TRUE) { echo "\e[32m".$msg."\e[0m"; if ($eol) { echo "\n"; }}
function debug($msg, $eol = TRUE) { if (Globals::$debug) { echo "\e[33m".$msg."\e[0m"; if ($eol) { echo "\n"; } } }
function debug_git($msg, $eol = TRUE) { if (Globals::$debug_git) { echo "\e[33m".$msg."\e[0m"; if ($eol) { echo "\n"; }} }
function delimiter() { echo "\e[33m----------\e[0m\n"; }

class Util {
	public static function get_line($prompt) {
		echo $prompt;
		return stream_get_line(STDIN, 1024, PHP_EOL);
	}

	public static function ask_from_array($array, $str, $print = FALSE) {
		if ($print === TRUE) {
			$i = 1;
			foreach ($array as $item) {
				msg($i++.")\t".$item);
			}
		}

		$entry = "";
		while ($entry == "") {
			$no = (int)Util::get_line($str." (1-".count($array)."): ");
			if (isset($array[$no - 1]))
				$entry = $array[$no - 1];
		}

		return $entry;
	}

	public static function ask($str, $options, $default) {
		$entry = "";
		while ($entry == "") {
			$val = Util::get_line($str);
			$val = strtolower($val);
			if (in_array($val, $options))
				$entry = $val;
			if ($val == "")
				return $default;
		}

		return $entry;
	}

	public static function pause() {
		self::get_line("--- press enter to continue ---");
	}

	// Returns the remains of the string after first occurance of token
	public static function parse_after($token, $str) {
		if (strpos($str, $token) === FALSE)
			return FALSE;

		$remains = explode($token, $str);
		if (count($remains) > 1) {
			array_shift($remains);
			return implode($token, $remains);
		}

		return "";
	}

	// Returns string leading up to first occurance of token
	public static function parse_before($token, $str) {
		if (strpos($str, $token) === FALSE)
			return FALSE;

		$before = explode($token, $str);

		if (count($before) > 1)
			return $before[0];
		else
			return "";
	}

	public static function get_user(&$username, &$fullname, &$path) {
		exec("whoami", $res);
		$username = $res[0];
		exec("getent passwd ".$username, $res);
		$res = explode(":", $res[1]);
		$fullname = $res[4];
		$path = $res[5];
	}

        public static function get_directory_contents($path, $depth) {
		$path .= "/";
		$res = shell_exec("find ".$path." -maxdepth ".$depth." 2> /dev/null");
		$rows = explode(PHP_EOL, $res);
		array_shift($rows);
		array_pop($rows);

		$entries = array();

		foreach ($rows as $row) {
			$row = substr($row, strlen($path));
			$entry = $row;

			$row = explode("/", $row);

			if (count($row) != $depth)
				continue;

			$entries[] = $entry;
		}

		return $entries;
	}

	public static function pad_str($str, $len)
	{
		$align = "";

		for ($i = 0; $i < $len; $i++)
			$align .= " ";

		return (substr($str.$align, 0, $len - 1)." ");
	}
};

?>
