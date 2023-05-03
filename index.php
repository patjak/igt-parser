<!DOCTYPE html>
<html><head><title>SUSE IGT results</title>
<style>
	body { background-color: #eee; font-size: 14pt; padding: 4em; font-family: Arial; }
	a { text-decoration: none; }
	table,tr,th,td { border: 1px solid #000; border-collapse: collapse; background-color: #fff; }
	table { font-size: 1em; margin-bottom: 3em; }
	th,td { padding: 0.4em;  }
</style>
</head>
<body>
<?php

if (!isset($_GET['os']))
	display_oses();

if (isset($_GET['os']) && $_GET['mach'] && isset($_GET['result']))
	display_result($_GET['os'], $_GET['mach'], $_GET['result'], $_GET['prev']);
else if (isset($_GET['os']))
	display_os($_GET['os']);

function result_to_color($result)
{
	switch ($result) {
	case "pass":
		$color = "#080";
		break;
	case "skip":
		$color = "#888";
		break;
	case "fail":
	case "crash":
	case "regression":
		$color = "#800";
		break;
	default:
		$color = "#000";
	}

	return $color;
}

function load_results($path) {
	$filename = $path."/results.json";
	$file = file_get_contents($filename);

	if ($file === FALSE) {
		if (file_exists(dirname($filename))) {

			$tests_done = get_test_progress($path);
			return "Incomplete: ".$tests_done;
		} else {
			return "";
		}
	}

	$json = json_decode($file, TRUE);
	return decode_results($json);
}

function decode_results($json) {
	echo "\n";
	$count = 0;
	$results = array();

	foreach ($json["tests"] as $name => $test) {
		$result = $test["result"];
		if (isset($results[$result]))
			$results[$result]++;
		else
			$results[$result] = 1;
	}

	return $results;
}

function display_oses() {
	$oses = array();
	exec("ls -d */", $out, $res);

	if (!isset($_GET['os'])) {
		foreach ($out as $os)
			$oses[] = explode("/", $os)[0];

	?>
	<h1>Select OS:</h1>
	<?php
		foreach ($oses as $os)
			echo "<div><a href=\"?os=".$os."\">".$os."</a></div>";
	}
}

function display_os($os) {
	echo "<h1>IGT tests for ".$os."</h1>";
	$dates = array();
	$machs = array();

	unset($out);
	exec("cd ".$os." && ls -d */", $out, $res);
	foreach ($out as $mach)
		$machs[] = explode("/", $mach)[0];

	foreach ($machs as $mach) {
		unset($out);
		exec("cd ".$os."/".$mach." && ls -d */", $out, $res);
		foreach ($out as $date) {
			$dates[] = explode("/", $date)[0];
		}
	}

	$dates = array_unique($dates);
	$dates = array_values($dates); // Reindex array
	sort($dates);


	echo "<table><tr><th></th>";

	foreach ($machs as $mach) {
		echo "<th>".$mach."</th>";
	}

	echo "</tr>";

	for ($i = count($dates) - 1; $i >= 0; $i--) {
		if (!isset($dates[$i]))
			continue;

		$date = $dates[$i];

		echo "<tr><th>".$date."</th>";

		foreach ($machs as $mach) {
			echo "<td>";

			$results = load_results($os."/".$mach."/".$date);
			if (is_string($results)) {

				echo "<a href=\"?os=".$os."&mach=".$mach."&result=".$date."&prev=".$dates[$i-1]."\">".$results."</a></td>";
				continue;
			}

			// Look ahead so we can detect regressions
			$results_next = load_results($os."/".$mach."/".$dates[$i-1]);
			if (!is_string($results_next))
				$fails_next = $results_next["fail"];
			else
				$fails_next = $results["fail"];

			$str = "";
			$regression_str = "";

			// Resort the results to: pass / fail / skip / the rest
			$sorted_results = array();

			foreach ($results as $result => $num) {
				if ($result == "pass") {
					$sorted_results[$result] = $num;
					unset($results[$result]);
				}
			}

			foreach ($results as $result => $num) {
				if ($result == "fail") {
					$sorted_results[$result] = $num;
					unset($results[$result]);
				}
			}

			foreach ($results as $result => $num) {
				if ($result == "skip") {
					$sorted_results[$result] = $num;
					unset($results[$result]);
				}
			}

			ksort($results);
			$results = array_merge($sorted_results, $results);

			foreach ($results as $result => $num) {
				if ($result == "fail") {
					if ($num > $fails_next)
						$regression_str = "<span style=\"color: #f00;\" title=\"Regression\" alt=\"Regression\">* </span>";
				}

				$color = result_to_color($result);
				if ($str != "")
					$str .= " / ";
				$str .= "<span style=\"color: ".$color.";\" title=\"".$result."\" alt=\"".$result."\">".$num."</span>";
			}
			echo "<a href=\"?os=".$os."&mach=".$mach."&result=".$date."&prev=".$dates[$i-1]."\">".$regression_str;
			echo $str."</a></td>";
		}
		echo "</tr>";

		$date_next = $date;
	}

	echo "<table>";
}

function get_test_progress($path)
{
	$scandir = scandir($path."/", SCANDIR_SORT_DESCENDING);

	for ($i = 0; $i < count($scandir); $i++) {
		if (!is_dir($path."/".$scandir[$i] && !is_int($path."/".$scandir)))
			unset($scandir[$i]);
	}

	return count($scandir) - 1;
}

function display_result($os, $mach, $result, $prev)
{
	$path = $os."/".$mach."/".$result;
	$file = file_get_contents($path."/results.json");
	$res = json_decode($file, TRUE);

	$file_prev = file_get_contents($os."/".$mach."/".$prev."/results.json");
	$res_prev = json_decode($file_prev, TRUE);

	$vga_info = file_get_contents($path."/vga-info.txt");

	$tests_done = get_test_progress($path);

	echo "<table>".
	"<tr><td>name</td><td>".$res["name"]."</td></tr>".
	"<tr><td>progress</td><td>".$tests_done." tests finished</td></tr>".
	"<tr><td>uname</td><td>".$res["uname"]."</td></tr>".
	"<tr><td>time elapsed</td><td>".round($res["time_elapsed"]["end"] - $res["time_elapsed"]["start"], 2)." s</td></tr>".
	"<tr><td>VGA info</td><td>".$vga_info."</td></tr>".
	"</table>\n";

	$summary = array();
	foreach ($res['tests'] as $name => $test) {
		if (!isset($summary[$test['result']]))
			$summary[$test['result']] = 1;
		else
			$summary[$test['result']]++;
	}

	echo "<table style=\"text-align: center; \">";
	$row1 = "";
	$row2 = "";
	foreach ($summary as $name => $val) {
		$color = result_to_color($name);
		$row1 .= "<td>".$name."</td>";
		$row2 .= "<td style=\"background-color: ".$color."; color: #ffff; \">".$val."</td>";
	}
	echo "<tr>".$row1."</tr>";
	echo "<tr>".$row2."</tr>";
	echo "</table>";

	echo "<table>\n";

	$i = 1;
	foreach ($res['tests'] as $name => $test) {
		$regression = FALSE;

		foreach ($res_prev['tests'] as $name_prev => $test_prev) {
			if ($name == $name_prev && $test['result'] == "fail" && $test_prev['result'] != "fail")
				$regression = TRUE;
		}

		$result = $test['result'];
		if ($regression)
			$result = "regression";

		$err = htmlentities($test['err']);
		$time = round($test['time']['end'] - $test['time']['start'], 2);
		$color = result_to_color($result);
		echo "<tr><td>".$i++."</td><td style=\"".$name_style."\">".$name."</td><td style=\"background-color: ".$color."; color: #fff; \" title=\"".$err."\">".$result."</td>".
		"<td>".$time." s</td></tr>\n";
	}

	echo "</table>";
}

?>
</table>

</body>
</html>
