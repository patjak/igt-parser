<?php

class Globals {
	public static	$debug = FALSE,
			$verbose = FALSE;
};

msg("");

$opts = array(	"path:",
		"sequence-cmp:",
		"debug",
		"verbose",
		"limit:",
		"months:",
		"non-interactive");

$args = Options::parse($argv, $opts);

if (isset(Options::$options["debug"]))
	Globals::$debug = TRUE;

if (isset(Options::$options["verbose"]))
	Globals::$verbose = TRUE;

$path = isset(Options::$options["path"]) ? Options::$options["path"] : FALSE;
$sequence_cmp = isset(Options::$options["sequence-cmp"]) ? Options::$options["sequence-cmp"] : FALSE;

if ($path === FALSE) {
	$path = getenv("IGT_RESULTS_PATH", TRUE);
	if ($path === FALSE) {
		error("Missing --path and IGT_RESULTS_PATH is not set");
		print_usage(1);
	}
}

$cmd = isset($args[1]) ? $args[1] : FALSE;

$os = isset($args[2]) ? $args[2] : FALSE;

switch (strtolower($cmd)) {
case "view":
	$machine = isset($args[3]) ? $args[3] : FALSE;
	$sequence = isset($args[4]) ? $args[4] : FALSE;
	$test = isset($args[5]) ? $args[5] : FALSE;
	cmd_view_testrun($path, $os, $machine, $sequence, $test);
	return 0;

case "regression":
	$machine = isset($args[3]) ? $args[3] : FALSE;
	$sequence = isset($args[4]) ? $args[4] : FALSE;

	if ($sequence === FALSE)
		$sequence = find_last_sequence($path, $os, $sequence);

	if ($os === FALSE) {
		error("No OS specified\n");

		print_oses($path);
		return 1;
	}

	validate_input($path, $os, $machine, $sequence, FALSE);

	if ($os !== FALSE && $machine !== FALSE && $sequence !== FALSE)
		print_machine_info_on_sequence($path, $os, $machine, $sequence);

	$ret = cmd_regression($path, $os, $machine, $sequence, $sequence_cmp);
	if ($ret)
		exit(1);

	break;

case "purge":
	if ($os === FALSE) {
		error("OS must be specified\n");
		print_oses($path);
		exit(1);
	}

	cmd_purge($path, $os);
	break;

default:
	error("No command specified\n");
	print_usage(1);
}

// Find last sequence for os
function find_last_sequence($path, $os, $sequence) {
	$i = 1;
	if ($sequence === FALSE && $os !== FALSE) {
		while (file_exists($path."/".$os."/".$i))
			$i++;

		$sequence = $i - 1;
	}

	return $sequence;
}
function get_oses($path) { return Util::get_directory_contents($path, 1); }
function get_sequences($path, $os) { return Util::get_directory_contents($path."/".$os, 1); }
function get_machines($path, $os, $sequence) { return Util::get_directory_contents($path."/".$os."/".$sequence, 1, array("date.txt")); }
function get_results($path, $os, $machine, $sequence)
{
	$filename = $path."/".$os."/".$sequence."/".$machine."/results.json";
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

// Valisequence input
function validate_input($path, $os, $machine, $sequence, $test)
{
	if ($os !== FALSE) {
		$oses = get_oses($path);
		if (!in_array($os, $oses))
			fatal("Invalid OS specified: ".$os);
	}

	if ($machine !== FALSE) {
		$sequence = find_last_sequence($path, $os, $sequence);
		$machines = get_machines($path, $os, $sequence);
		if (!in_array($machine, $machines))
			fatal("Invalid OS, sequence and machine combination specified:\nOS: ".$os."\nSequence: ".$sequence."\nMachine: ".$machine);
	}

	if ($sequence !== FALSE && $os !== FALSE) {
		$sequences = get_sequences($path, $os);
		if (!in_array($sequence, $sequences))
			fatal("Invalid OS and sequence combination specified:\nOS: ".$os."\nSequence: ".$sequence);
	}

	if ($test !== FALSE && !is_numeric($test) && $os !== FALSE && $machine !== FALSE && $sequence !== FALSE) {
		$results = get_results($path, $os, $machine, $sequence);
		if (!array_key_exists($test, $results["tests"]))
			fatal("Invalid OS, machine, sequence and test combination specified: ".$os." ".$machine." ".$sequence." ".$test);
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

function print_machine_info_on_sequence($path, $os, $machine, $sequence)
{
	$fullpath = $path."/".$os."/".$sequence."/".$machine;
	$r = get_results($path, $os, $machine, $sequence);
	if ($r === FALSE)
		fatal("Failed to find results for ".$os." ".$sequence." ".$machine);

	$vga_info = "";
	if (file_exists($fullpath."/vga-info.txt"))
		$vga_info = trim(file_get_contents($fullpath."/vga-info.txt"));

	$date = "";
	if (file_exists($fullpath."/date.txt"))
		$date = trim(file_get_contents($fullpath."/date.txt"));

	msg(Util::pad_str("Machine:", 16).$machine);
	msg(Util::pad_str("Date: ",16).$date);
	msg(Util::pad_str("uname: ", 16).$r["uname"]);
	$time = $r["time_elapsed"]["end"] - $r["time_elapsed"]["start"];
	$time = gmdate("H:i:s", $time);
	msg(Util::pad_str("Time elapsed: ", 16).$time." (h:m:s)");
	msg(Util::pad_str("VGA info: ", 16).$vga_info);
	msg("");
}

function print_sequences($path, $os, $machine)
{
	$sequences = Util::get_directory_contents($path."/".$os."/".$machine, 1);

	msg("Available sequences");
	foreach($sequences as $sequence) {
		if ($sequence == "")
			continue;
		msg("  ".$sequence);
	}

	msg("");
}

function print_usage($errno)
{
	global $argv;

	$execname = basename($argv[0]);

	msg("Usage: ".$execname." <command> [arguments] [options]\n");

	msg("Commands:");
	msg("  view <os> [machine] [sequence] [test]");
	msg("  regression <os> [machine] [sequence]");
	msg("    (if no sequence is specified, the last available sequence is used)");
	msg("    (if no sequence-cmp is specified, the closest previous sequence is used)");
	msg("  purge <os>");

	msg("\nOptions:");
	msg(Util::pad_str("  --path <path-to-igt-results>", 30)."Specifies where the IGT results files are stored.");
	msg(Util::pad_str("", 30)."Environment variable IGT_RESULTS_PATH can be used instead.");
	msg(Util::pad_str("  --sequence-cmp <no>", 30)."Which sequence to compare for regressions agains");

	exit($errno);
}

?>
