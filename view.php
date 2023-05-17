<?php

function print_test($path, $os, $machine, $date, $test_name)
{
	$results = get_results($path, $os, $machine, $date);

	$i = 1;
	foreach ($results["tests"] as $name => $test) {
		if ($name == $test_name || (is_numeric($test_name) && $test_name == $i)) {
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
		$i++;
	}

	error("Test not found");
}

function cmd_view_testrun($path, $os, $machine, $date, $test)
{
	validate_input($path, $os, $machine, $date, $test);

	if ($os === FALSE) {
		print_oses($path);
		print_usage(1);
	}

	if ($machine === FALSE) {
		print_machines($path, $os);
		print_usage(1);
	}

	if ($date === FALSE) {
		print_dates($path, $os, $machine);
		print_usage(1);
	}

	print_machine_info_on_date($path, $os, $machine, $date);

	if ($test === FALSE)
		print_results($path, $os, $machine, $date);
	else
		print_test($path, $os, $machine, $date, $test);
}

function print_results($path, $os, $machine, $date)
{
	$results = get_results($path, $os, $machine, $date);
	if ($results === FALSE)
		fatal("Failed to open results.json file");

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


?>
