<?php

function cmd_regression($path, $os, $machine, $sequence, $sequence_cmp, $validate = TRUE)
{
	if ($validate)
		validate_input($path, $os, $machine, $sequence, FALSE);

	if ($os === FALSE)
		fatal("No OS specified");

	$regression_found = FALSE;

	if ($machine === FALSE) {
		$machines = get_machines($path, $os, $sequence);
		foreach ($machines as $machine) {
			if (cmd_regression($path, $os, $machine, $sequence, $sequence_cmp, FALSE)) {
				$regression_found = TRUE;
				msg("");
			}
		}

		return $regression_found;
	}

	if ($sequence === FALSE) {
		$sequences = get_sequences($path, $os, $machine);
		$sequence = array_pop($sequences);
	}

	if ($sequence_cmp === FALSE) {
		$sequences = get_sequences($path, $os, $machine);

		$prev_sequence = $sequences[0];
		for ($i = 0; $i < count($sequences); $i++) {
			$d = $sequences[$i];
			if ($d == $sequence) {
				$sequence_cmp = $prev_sequence;
				break;
			}
			$prev_sequence = $d;
		}
	}

	$r1 = get_results($path, $os, $machine, $sequence);
	$r2 = get_results($path, $os, $machine, $sequence_cmp);

	msg(Util::pad_str($machine.":", 14), FALSE);

	if ($r1 === FALSE) {
		info("No results found for ".$sequence);
		return FALSE;
	}
	if ($r2 === FALSE) {
		info("No results found for ".$sequence_cmp);
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
		msg("Regressions when comparing ".$sequence_cmp." and ".$sequence.":");
		if ($r1["uname"] != $r2["uname"]) {
			msg("  ".$sequence_cmp.": ".$r2["uname"]);
			msg("  ".$sequence.": ".$r1["uname"]);
		} else {
			msg("  ".$sequence.": ".$r1["uname"]);
		}

		foreach ($regressions as $regression)
			error("  ".$regression);

		return TRUE;
	}

	msg("No regressions found comparing ".$sequence_cmp." and ".$sequence);

	return $regression_found;
}

?>
