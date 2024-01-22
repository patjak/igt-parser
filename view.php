<?php

// Print details about a single test from a single testrun
function print_test($path, $os, $machine, $date, $test_name)
{
	$results = get_results($path, $os, $machine, $date);

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

function cmd_view_testrun($path, $os, $machine, $date, $test)
{
	validate_input($path, $os, $machine, $date, $test);

	if ($os === FALSE) {
		error("No OS specified\n");

		print_oses($path);
		return 1;
	}

	if ($machine === FALSE) {
		cmd_os_date_summary($path, $os, $date);
		return;
	}

	if ($date === FALSE) {
		print_results_all_dates($path, $os, $machine);
		return;
	}

	print_machine_info_on_date($path, $os, $machine, $date);

	if ($test === FALSE)
		print_results_on_date($path, $os, $machine, $date);
	else
		print_test($path, $os, $machine, $date, $test);
}

// Print results from all dates from a specified machine
function print_results_all_dates($path, $os, $machine)
{
	$dates = get_dates($path, $os, $machine);
	$limit = isset(Options::$options["limit"]) ? Options::$options["limit"] : FALSE;

	if ($limit !== FALSE && $limit < count($dates))
		$i = count($dates) - $limit; // Limit results to last 10 days
	else
		$i = 0;


	for (; $i < count($dates); $i++) {
		$date = $dates[$i];

		if ($i == 0) {
			print_summary_header("");
			delimiter(12 * 9);
		}

		msg(Util::pad_str($date, 12), FALSE);
		
		$results = get_results($path, $os, $machine, $date);
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

function print_results_on_date($path, $os, $machine, $date)
{
	$results = get_results($path, $os, $machine, $date);
	if ($results === FALSE)
		fatal("Failed to open results.json file");

	msg("Results for ".$os." / ".$machine." / ".$date);

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
