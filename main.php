<?php

class Globals {
	public static	$debug = FALSE,
			$verbose = FALSE;
};

msg("");

$opts = array(	"path:",
		"debug",
		"verbose");

$commands = Options::parse($argv, $opts);

if (isset(Options::$options["debug"]))
	Globals::$debug = TRUE;

if (isset(Options::$options["verbose"]))
	Globals::$verbose = TRUE;

$path = isset(Options::$options["path"]) ? Options::$options["path"] : FALSE;

if ($path === FALSE)
	print_usage(1);

$os = isset($commands[1]) ? $commands[1] : FALSE;
$machine = isset($commands[2]) ? $commands[2] : FALSE;
$date = isset($commands[3]) ? $commands[3] : FALSE;
$test = isset($commands[4]) ? $commands[4] : FALSE;

if ($os === FALSE) {
	print_oses($path);
	print_usage(1);
}

function get_oses($path) { return Util::get_directory_contents($path, 1); }
function get_machines($path, $os) { return Util::get_directory_contents($path."/".$os, 1); }
function get_dates($path, $os, $machine) { return Util::get_directory_contents($path."/".$os."/".$machine, 1); }
function get_results($path, $os, $machine, $date)
{
	$file = file_get_contents($path."/".$os."/".$machine."/".$date."/results.json");
	if ($file === FALSE)
		fatal("Failed to open results.json file");

	$json = json_decode($file, TRUE);
	if ($json == NULL)
		fatal("Failed to decode results.json file");

	return $json;
}

// Validate input
$oses = get_oses($path);
if (!in_array($os, $oses))
	fatal("Invalid OS specified");

$machines = get_machines($path, $os);
if (!in_array($machine, $machines))
	fatal("Invalid OS and machine combination specified");

$dates = get_dates($path, $os, $machine);
if (!in_array($date, $dates))
	fatal("Invalid OS, machine and date combination specified");

if ($machine === FALSE) {
	print_machines($path, $os);
	print_usage(1);
}

if ($date === FALSE) {
	print_dates($path, $os, $machine);
	print_usage(1);
}

if ($test === FALSE)
	print_results($path, $os, $machine, $date);
else
	print_test($path, $os, $machine, $date, $test);

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

function print_test($path, $os, $machine, $date, $test_name)
{
	$results = get_results($path, $os, $machine, $date);

	foreach ($results["tests"] as $name => $test) {
		if ($name == $test_name) {
			green("Test: ".$name);
			msg($test["igt-version"]);
			msg("Result: ".$test["result"]);
			msg("Time: start=".$test["time"]["start"]." end=".$test["time"]["end"]);

			info("\nout:");
			msg($test["out"]);

			error("err:");
			msg($test["err"]);

			info("dmesg: ");
			msg($test["dmesg"]);
			// var_dump($test);
		}
	}
}

function print_results($path, $os, $machine, $date)
{
	$results = get_results($path, $os, $machine, $date);

	msg("Results for ".$os." / ".$machine." / ".$date);

	$summary = array();

	$i = 1;
	foreach ($results["tests"] as $name => $test) {
		$result = $test["result"];
		if (isset($summary[$result]))
			$summary[$result]++;
		else
			$summary[$result] = 0;

		$result = $test["result"];
		$result = Util::pad_str($result, 12);
		$str = $i.") ".$result.$name;

		switch (trim($result)) {
		case "pass":
			green($str);
			break;
		case "warn":
			info($str);
			break;
		case "fail":
		case "crash":
			error($str);
			break;
		case "skip":
			if (!Globals::$verbose) {
				$i--;
				break;
			}
		default:
			msg($str);
		}
		$i++;
	}

	msg("\nSummary:");
	foreach ($summary as $name => $num) {
		msg(" * ".$name.":\t".$num);
	}
}

function print_usage($errno)
{
	global $argv;

	msg("Usage: ".$argv[0]." --path=<path-to-igt-results><os> <machine> <date>\n");

	exit($errno);
}

?>
