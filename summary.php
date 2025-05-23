<?php

function cmd_os_sequence_summary($path, $os, $sequence)
{
	if ($sequence === FALSE)
		$sequence = find_last_sequence($path, $os, $sequence);

	validate_input($path, $os, FALSE, $sequence, FALSE);

	if ($os === FALSE)
		fatal("No OS specified");

	$date_file = $path."/".$os."/".$sequence."/date.txt";
	if (file_exists($date_file))
		$date = trim(file_get_contents($date_file));
	else
		$date = "";

	msg("Results summary for OS ".$os." on sequence ".$sequence." (".$date.")\n");
	print_summary_header("");
	delimiter(12 * 9);

	$i = 1;
	$machines = get_machines($path, $os, $sequence);
	foreach ($machines as $m) {
		$r = get_results($path, $os, $m, $sequence);
		if (isset($r["time_elapsed"]))
			$time = $r["time_elapsed"]["end"] - $r["time_elapsed"]["start"];
		else
			$time = 0;

		if ($r === FALSE) {
			msg(Util::pad_str($m, 12), FALSE);
			if (is_dir($path."/".$os."/".$m."/".$sequence))
				info("Incomplete");
			else
				error("No results");

			continue;
		}

		msg(Util::pad_str($m, 12), FALSE);
		$summary = get_summary($r);
		print_summary($summary, $time);

	}
}

function get_summary($results)
{
	$summary = array();
	foreach ($results["tests"] as $name => $test) {
		$result = $test["result"];
		if (!isset($summary[$result]))
			$summary[$result] = 0;

		$summary[$result]++;
	}

	return sort_summary($summary);
}

function sort_summary($s)
{
	// Manually reorder the array to pass/fail/dmesg-warn/dmesg-fail/skip
	$summary = array();
	$summary["pass"] = isset($s["pass"]) ? $s["pass"] : 0;
	$summary["fail"] = isset($s["fail"]) ? $s["fail"] : 0;
	$summary["crash"] = isset($s["crash"]) ? $s["crash"] : 0;
	$summary["dmesg-warn"] = isset($s["dmesg-warn"]) ? $s["dmesg-warn"] : 0;
	$summary["dmesg-fail"] = isset($s["dmesg-fail"]) ? $s["dmesg-fail"] : 0;
	$summary["incomplete"] = isset($s["incomplete"]) ? $s["incomplete"] : 0;
	$summary["skip"] = isset($s["skip"]) ? $s["skip"] : 0;

	if (count($summary) < count($s)) {
		var_dump($summary);
		var_dump($s);
		fatal("count(summary) != count(s)");
	}

	return $summary;
}

function print_summary_header($subject = FALSE)
{
	$len = 12;

	if ($subject !== FALSE)
	msg(Util::pad_str($subject, $len), FALSE);
	msg(Util::pad_str("pass", $len), FALSE);
	msg(Util::pad_str("fail", $len), FALSE);
	msg(Util::pad_str("crash", $len), FALSE);
	msg(Util::pad_str("dmesg-warn", $len), FALSE);
	msg(Util::pad_str("dmesg-fail", $len), FALSE);
	msg(Util::pad_str("incomplete", $len), FALSE);
	msg(Util::pad_str("skip", $len), FALSE);
	msg(Util::pad_str("time elapsed", $len));
}

function print_summary($summary, $time)
{
	$len = 12;

	$i = 0;
	foreach ($summary as $name => $num) {
		$num = Util::pad_str($num, $len);
		switch ($name) {
		case "pass":
			green($num, FALSE);
			break;
		case "fail":
		case "crash":
		case "dmesg-fail":
		case "dmesg-warn":
		case "incomplete":
			if ($num > 0)
				error($num, FALSE);
			else
				msg(Util::pad_str("-", $len), FALSE);
			break;
		default:
			msg($num, FALSE);
		}
	}
	msg(gmdate("H:i:s", $time));
}

?>
