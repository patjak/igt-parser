<?php

class Globals {
	public static	$debug = FALSE,
			$verbose = FALSE;
};

msg("");

$opts = array(	"path:",
		"debug",
		"verbose",
		"summary");

$args = Options::parse($argv, $opts);

if (isset(Options::$options["debug"]))
	Globals::$debug = TRUE;

if (isset(Options::$options["verbose"]))
	Globals::$verbose = TRUE;

$path = isset(Options::$options["path"]) ? Options::$options["path"] : FALSE;
$summary = isset(Options::$options["summary"]) ? Options::$options["summary"] : FALSE;

if ($path === FALSE)
	print_usage(1);

$cmd = isset($args[1]) ? $args[1] : FALSE;

switch (strtolower($cmd)) {
case "view":
	$os = isset($args[2]) ? $args[2] : FALSE;
	$machine = isset($args[3]) ? $args[3] : FALSE;
	$date = isset($args[4]) ? $args[4] : FALSE;
	$test = isset($args[5]) ? $args[5] : FALSE;
	cmd_view_testrun($path, $os, $machine, $date, $test);
	return 0;

case "summary":
	$os = isset($args[2]) ? $args[2] : FALSE;
	$date = isset($args[3]) ? $args[3] : FALSE;
	cmd_os_date_summary($path, $os, $date);
	return 0;

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
			fatal("Invalid OS specified");
	}

	if ($machine !== FALSE) {
		$machines = get_machines($path, $os);
		if (!in_array($machine, $machines))
			fatal("Invalid OS and machine combination specified");
	}

	if ($date !== FALSE && $os !== FALSE && $machine !== FALSE) {
		$dates = get_dates($path, $os, $machine);
		if (!in_array($date, $dates))
			fatal("Invalid OS, machine and date combination specified");
	}

	if ($test !== FALSE && $os !== FALSE && $machine !== FALSE && $date !== FALSE) {
		$results = get_results($path, $os, $machine, $date);
		if (!array_key_exists($test, $results["tests"]))
			fatal("Invalid OS, machine, date and test combination specified");
	}
}

function print_oses($path)
{
	$oses = get_oses($path);

	error("No OS specified\n");

	msg("Available OSes");
	foreach($oses as $os) {
		if ($os == "")
			continue;
		msg(" * ".$os);
	}

	msg("");
}

function print_machines($path, $os)
{
	$machines = Util::get_directory_contents($path."/".$os, 1);

	error("No machine specified\n");

	msg("Available machines");
	foreach($machines as $machine) {
		if ($machine == "")
			continue;
		msg(" * ".$machine);
	}

	msg("");
}

function print_machine_info_on_date($path, $os, $machine, $date)
{
	$r = get_results($path, $os, $machine, $date);
	$vga_info = file_get_contents($path."/".$os."/".$machine."/".$date."/vga-info.txt");

	msg(Util::pad_str("Machine:", 16).$machine);
	msg(Util::pad_str("uname: ", 16).$r["uname"]);
	$time = round($r["time_elapsed"]["end"] - $r["time_elapsed"]["start"], 2);
	msg(Util::pad_str("Time elapsed: ", 16).$time." seconds");
	msg(Util::pad_str("VGA info: ", 16).$vga_info);
}

function print_dates($path, $os, $machine)
{
	$dates = Util::get_directory_contents($path."/".$os."/".$machine, 1);

	error("No date specified\n");

	msg("Available dates");
	foreach($dates as $date) {
		if ($date == "")
			continue;
		msg(" * ".$date);
	}

	msg("");
}

function print_usage($errno)
{
	global $argv;

	$execname = basename($argv[0]);

	msg("Usage: ".$execname." --path=<path-to-igt-results> <command> [arguments] ");
	msg("\nCommands:");
	msg("\tview <os> <machine> <date>");
	msg("\tsummary <os> <date>");
	msg("\tregression <os> <machine> <date-1> <date-2>");

	exit($errno);
}

?>
