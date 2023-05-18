<?php

function cmd_regression($path, $os, $machine, $date1, $date2)
{
	if ($os === FALSE) {
		print_oses($path);
		print_usage(1);
	}

	if ($machine === FALSE) {
		print_machines($path, $os);
		print_usage(1);
	}

	if ($date1 === FALSE) {
		print_dates($path, $os, $machine);
		print_usage(1);
	}

	if ($date2 === FALSE) {
		$dates = get_dates($path, $os, $machine);

		$prev_date = $dates[0];
		for ($i = 0; $i < count($dates); $i++) {
			$date = $dates[$i];
			if ($date == $date1) {
				$date2 = $prev_date;
				break;
			}
			$prev_date = $date;
		}
	}

	$r1 = get_results($path, $os, $machine, $date1);
	$r2 = get_results($path, $os, $machine, $date2);

	if ($r1 === FALSE)
		fatal("No results found for ".$date1);
	if ($r2 === FALSE)
		fatal("No results found for ".$date2);

	$regressions = array();
	foreach ($r1["tests"] as $name1 => $test1) {
		$result1 = $test1["result"];
		foreach ($r2["tests"] as $name2 => $test2) {
			$result2 = $test2["result"];

			if ($name1 == $name2 && $result1 != "pass" && $result2 == "pass")
				$regressions[] = $name1;
		}
	}

	if (count($regressions) > 0) {
		msg("Regressions when comparing ".$date2." and ".$date1.":");
		foreach ($regressions as $regression)
			error(" * ".$regression);

		exit(1);
	}

	msg("No regressions found comparing ".$date2." and ".$date1);
}

?>
