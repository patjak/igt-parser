<?php

function cmd_purge($path, $os)
{
	$num_months = isset(Options::$options["months"]) ? Options::$options["months"] : 3;
	$non_interactive = isset(Options::$options["non-interactive"]) ? TRUE : FALSE;
	validate_input($path, $os, FALSE, FALSE, FALSE);

	msg("Scanning...");

	$machines = get_machines($path, $os);
	$size = 0;
	$dates_to_delete = array();
	$i = 1;
	foreach ($machines as $machine) {
		msg($i++."/".count($machines)."\r", FALSE);
		$dates = get_dates($path, $os, $machine);
		foreach ($dates as $date) {
			if (strtotime($date) < strtotime("-".$num_months." months")) {
				$file = $path."/".$os."/".$machine."/".$date;
				$dates_to_delete[] = $file;
			}
		}
	}

	error("This will delete all data older than ".$num_months." months from OS ".$os." (".count($dates_to_delete)." tests)");
	if (!$non_interactive)
		Util::pause();

	$i = 1;
	foreach ($dates_to_delete as $file) {
		msg($i++."/".count($dates_to_delete)."\r", FALSE);
		if (trim($file) == "")
			fatal("Invalid file, aborting");

		exec("rm -Rf ".$file, $res, $code);
		if ($code != 0)
			fatal("Failed to remove ".$file);
	}
	if ($i > 1)
		msg(--$i."/".count($dates_to_delete)." done");
}

?>
