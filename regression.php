<?php

function cmd_regression($path, $os, $machine, $date, $date_cmp, $validate = TRUE)
{
	if ($validate)
		validate_input($path, $os, $machine, $date, FALSE);

	if ($os === FALSE)
		fatal("No OS specified");

	$regression_found = FALSE;

	if ($machine === FALSE) {
		$machines = get_machines($path, $os);
		foreach ($machines as $machine) {
			if (cmd_regression($path, $os, $machine, $date, $date_cmp, FALSE)) {
				$regression_found = TRUE;
				msg("");
			}
		}

		return $regression_found;
	}

	if ($date === FALSE) {
		$dates = get_dates($path, $os, $machine);
		$date = array_pop($dates);
	}

	if ($date_cmp === FALSE) {
		$dates = get_dates($path, $os, $machine);

		$prev_date = $dates[0];
		for ($i = 0; $i < count($dates); $i++) {
			$d = $dates[$i];
			if ($d == $date) {
				$date_cmp = $prev_date;
				break;
			}
			$prev_date = $d;
		}
	}

	$r1 = get_results($path, $os, $machine, $date);
	$r2 = get_results($path, $os, $machine, $date_cmp);

	msg(Util::pad_str($machine.":", 14), FALSE);

	if ($r1 === FALSE) {
		info("No results found for ".$date);
		return FALSE;
	}
	if ($r2 === FALSE) {
		info("No results found for ".$date_cmp);
		return FALSE;
	}

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
		msg("Regressions when comparing ".$date_cmp." and ".$date.":");
		foreach ($regressions as $regression)
			error("  ".$regression);

		return TRUE;
	}

	msg("No regressions found comparing ".$date_cmp." and ".$date);

	return $regression_found;
}

?>
