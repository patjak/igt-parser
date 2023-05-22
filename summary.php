<?php

function get_summary($results)
{
	$summary = array();
	foreach ($results["tests"] as $name => $test) {
		$result = $test["result"];
		if (!isset($summary[$result]))
			$summary[$result] = 0;

		$summary[$result]++;
	}

	return $summary;
}

function cmd_os_date_summary($path, $os, $date)
{
	validate_input($path, $os, FALSE, $date, FALSE);

	if ($os === FALSE)
		fatal("No OS specified");

	if ($date === FALSE) {
		$available_dates = array();
		$machines = get_machines($path, $os);
		foreach ($machines as $machine) {
			$dates = get_dates($path, $os, $machine);
			foreach ($dates as $date) {
				$available_dates[] = $date;
			}
		}
		$available_dates = array_unique($available_dates);
		sort($available_dates);

		$last_date = array_pop($available_dates);
		cmd_os_date_summary($path, $os, $last_date);
		return;
	}

	msg("Results summary for OS ".$os." on date ".$date);

	$i = 1;
	$machines = get_machines($path, $os);
	foreach ($machines as $m) {
		$r = get_results($path, $os, $m, $date);
		if ($r === FALSE) {
			msg(Util::pad_str($m, 12), FALSE);
			error("No results");
			continue;
		}

		msg(Util::pad_str($m, 12), FALSE);
		$s = get_summary($r);
		// Manually reorder the array to pass/fail/dmesg-warn/dmesg-fail/skip
		$summary = array();
		$summary["pass"] = isset($s["pass"]) ? $s["pass"] : 0;
		$summary["fail"] = isset($s["fail"]) ? $s["fail"] : 0;
		$summary["dmesg-warn"] = isset($s["dmesg-warn"]) ? $s["dmesg-warn"] : 0;
		$summary["dmesg-fail"] = isset($s["dmesg-fail"]) ? $s["dmesg-fail"] : 0;
		$summary["skip"] = isset($s["skip"]) ? $s["skip"] : 0;

		foreach ($summary as $name => $num) {
			$str = Util::pad_str($name.": ".$num, 20);

			if ($num == 0) {
				msg($str, FALSE);
				continue;
			}

			switch ($name) {
			case "pass":
				green($str, FALSE);
				break;
			case "fail":
			case "dmesg-fail":
			case "dmesg-warn":
				error($str, FALSE);
				break;
			default:
				msg($str, FALSE);
			}
		}
		msg("");
	}
}

?>
