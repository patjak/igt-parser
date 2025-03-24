<?php

// Print details about a single test from a single testrun
function print_test($path, $os, $machine, $sequence, $test_name)
{
	$results = get_results($path, $os, $machine, $sequence);

	$i = 1;
	foreach ($results["tests"] as $name => $test) {
		if ($name == $test_name || (is_numeric($test_name) && $test_name == $i && $test["result"] != "skip")) {
			green("Test: ".$name);
			msg($test["igt-version"]);
			msg("Result: ".$test["result"]);
			if (isset($test["time"]))
				msg("Time: start=".$test["time"]["start"]." end=".$test["time"]["end"]);

			info("\nstdout:");
			msg($test["out"]);

			error("stderr:");
			msg($test["err"]);

			info("dmesg: ");
			msg($test["dmesg"]);
			return;
		}
		if ($test["result"] != "skip")
			$i++;
	}

	fatal("Test not found");
}

function cmd_view_testrun($path, $os, $machine, $sequence, $test)
{
	validate_input($path, $os, $machine, $sequence, $test);

	if ($os === FALSE) {
		error("No OS specified\n");

		print_oses($path);
		return 1;
	}

	if ($machine === FALSE) {
		cmd_os_sequence_summary($path, $os, $sequence);
		return;
	}

	if ($sequence === FALSE) {
		print_results_all_sequences($path, $os, $machine);
		return;
	}

	print_machine_info_on_sequence($path, $os, $machine, $sequence);

	if ($test === FALSE)
		print_results_on_sequence($path, $os, $machine, $sequence);
	else
		print_test($path, $os, $machine, $sequence, $test);
}

// Print results from all sequences from a specified machine
function print_results_all_sequences($path, $os, $machine)
{
	$sequences = get_sequences($path, $os, $machine);
	$limit = isset(Options::$options["limit"]) ? Options::$options["limit"] : FALSE;

	if ($limit !== FALSE && $limit < count($sequences))
		$i = count($sequences) - $limit; // Limit results to last 10 days
	else
		$i = 0;


	for (; $i < count($sequences); $i++) {
		$sequence = $sequences[$i];

		if ($i == 0) {
			print_summary_header("");
			delimiter(12 * 9);
		}

		msg(Util::pad_str($sequence, 12), FALSE);
		
		$results = get_results($path, $os, $machine, $sequence);
		if ($results === FALSE) {
			msg("---");
			continue;
		}

		$time = $results["time_elapsed"]["end"] - $results["time_elapsed"]["start"];
		$summary = get_summary($results);
		print_summary($summary, $time);
	}

	delimiter(12 * 9);
	print_summary_header("");
}

function print_results_on_sequence($path, $os, $machine, $sequence)
{
	$results = get_results($path, $os, $machine, $sequence);
	if ($results === FALSE)
		fatal("Failed to open results.json file");

	msg("Results for ".$os." / ".$machine." / ".$sequence);

	$i = 1;
	foreach ($results["tests"] as $name => $test) {
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

	msg("");
	$summary = get_summary($results);
	print_summary_header();
	delimiter(12 * 9);

	$time = $results["time_elapsed"]["end"] - $results["time_elapsed"]["start"];
	print_summary($summary, $time);
}


?>
