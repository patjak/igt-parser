<?php

class Globals {
	public static	$debug = FALSE,
			$verbose = FALSE;
};

msg("");

$opts = array(	"path:",
		"date:",
		"date-cmp:",
		"debug",
		"verbose");

$args = Options::parse($argv, $opts);

if (isset(Options::$options["debug"]))
	Globals::$debug = TRUE;

if (isset(Options::$options["verbose"]))
	Globals::$verbose = TRUE;

$path = isset(Options::$options["path"]) ? Options::$options["path"] : FALSE;
$date = isset(Options::$options["date"]) ? Options::$options["date"] : FALSE;
$date_cmp = isset(Options::$options["date-cmp"]) ? Options::$options["date-cmp"] : FALSE;

if ($path === FALSE) {
	$path = getenv("IGT_RESULTS_PATH", TRUE);
	if ($path === FALSE) {
		error("Missing --path and IGT_RESULTS_PATH is not set");
		print_usage(1);
	}
}

$cmd = isset($args[1]) ? $args[1] : FALSE;

switch (strtolower($cmd)) {
case "view":
	$os = isset($args[2]) ? $args[2] : FALSE;
	$machine = isset($args[3]) ? $args[3] : FALSE;
	$test = isset($args[4]) ? $args[4] : FALSE;
	cmd_view_testrun($path, $os, $machine, $date, $test);
	return 0;

case "regression":
	$os = isset($args[2]) ? $args[2] : FALSE;
	$machine = isset($args[3]) ? $args[3] : FALSE;
	if ($os !== FALSE && $machine !== FALSE && $date !== FALSE)
		print_machine_info_on_date($path, $os, $machine, $date);

	$ret = cmd_regression($path, $os, $machine, $date, $date_cmp);
	if ($ret)
		exit(1);

	break;

default:
	error("No command specified\n");
	print_usage(1);
}

function get_oses($path) { return Util::get_directory_contents($path, 1); }
function get_machines($path, $os) { return Util::get_directory_contents($path."/".$os, 1); }
function get_dates($path, $os, $machine) { return Util::get_directory_contents($path."/".$os."/".$machine, 1); }
function get_results($path, $os, $machine, $date)
{
	$filename = $path."/".$os."/".$machine."/".$date."/results.json";
	if (!file_exists($filename))
		return FALSE;

	$file = file_get_contents($filename);
	if ($file === FALSE)
		return FALSE;

	$json = json_decode($file, TRUE);
	if ($json == NULL)
		fatal("Failed to decode results.json file");

	return $json;
}

// Validate input
function validate_input($path, $os, $machine, $date, $test)
{
	if ($os !== FALSE) {
		$oses = get_oses($path);
		if (!in_array($os, $oses))
			fatal("Invalid OS specified: ".$os);
	}

	if ($machine !== FALSE) {
		$machines = get_machines($path, $os);
		if (!in_array($machine, $machines))
			fatal("Invalid OS and machine combination specified: ".$os." ".$machine);
	}

	if ($date !== FALSE && $os !== FALSE && $machine !== FALSE) {
		$dates = get_dates($path, $os, $machine);
		if (!in_array($date, $dates))
			fatal("Invalid OS, machine and date combination specified: ".$os." ".$machine." ".$date);
	}

	if ($test !== FALSE && !is_numeric($test) && $os !== FALSE && $machine !== FALSE && $date !== FALSE) {
		$results = get_results($path, $os, $machine, $date);
		if (!array_key_exists($test, $results["tests"]))
			fatal("Invalid OS, machine, date and test combination specified: ".$os." ".$machine." ".$date." ".$test);
	}
}

function print_oses($path)
{
	$oses = get_oses($path);

	msg("Available OSes");
	foreach($oses as $os) {
		if ($os == "")
			continue;
		if (!is_dir($path."/".$os))
			continue;
		msg("  ".$os);
	}

	msg("");
}

function print_machines($path, $os)
{
	$machines = Util::get_directory_contents($path."/".$os, 1);

	msg("Available machines");
	foreach($machines as $machine) {
		if ($machine == "")
			continue;
		msg("  ".$machine);
	}

	msg("");
}

function print_machine_info_on_date($path, $os, $machine, $date)
{
	$r = get_results($path, $os, $machine, $date);
	$filename = $path."/".$os."/".$machine."/".$date."/vga-info.txt";
	$vga_info = "";
	if (file_exists($filename))
		$vga_info = file_get_contents($filename);

	msg(Util::pad_str("Machine:", 16).$machine);
	msg(Util::pad_str("uname: ", 16).$r["uname"]);
	$time = $r["time_elapsed"]["end"] - $r["time_elapsed"]["start"];
	$time = gmdate("H:i:s", $time);
	msg(Util::pad_str("Time elapsed: ", 16).$time." (h:m:s)");
	msg(Util::pad_str("VGA info: ", 16).$vga_info);
}

function print_dates($path, $os, $machine)
{
	$dates = Util::get_directory_contents($path."/".$os."/".$machine, 1);

	msg("Available dates");
	foreach($dates as $date) {
		if ($date == "")
			continue;
		msg("  ".$date);
	}

	msg("");
}

function print_usage($errno)
{
	global $argv;

	$execname = basename($argv[0]);

	msg("Usage: ".$execname." <command> [arguments] [options]\n");

	msg("Commands:");
	msg("  view <os> [machine] [test]");
	msg("  regression <os> [machine]");
	msg("    (if no date is specified, the last available date is used)");
	msg("    (if no date-cmp is specified, the closest previous date is used)");

	msg("\nOptions:");
	msg(Util::pad_str("  --path <path-to-igt-results>", 30)."Specifies where the IGT results files are stored.");
	msg(Util::pad_str("", 30)."Environment variable IGT_RESULTS_PATH can be used instead.");
	msg(Util::pad_str("  --date <YYYY-MM-DD>", 30)."For commands that can provide date specific results.");
	msg(Util::pad_str("  --date-cmp <YYYY-MM-DD>", 30)."Which date to compare for regressions agains");

	exit($errno);
}

?>
