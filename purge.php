<?php

function cmd_purge($path, $os)
{
	$num_months = isset(Options::$options["months"]) ? Options::$options["months"] : 3;
	$non_interactive = isset(Options::$options["non-interactive"]) ? TRUE : FALSE;
	validate_input($path, $os, FALSE, FALSE, FALSE);

	msg("Scanning...");

	$sequences = get_sequences($path, $os);
	$size = 0;
	$sequences_to_delete = array();
	$i = 1;
	foreach ($sequences as $sequence) {
		msg($i++."/".count($sequences)."\r", FALSE);
		if (filemtime($path."/".$os."/".$sequence) < strtotime("-".$num_months." months")) {
			$file = $path."/".$os."/".$sequence;
			$sequences_to_delete[] = $file;
		}
	}

	error("This will delete all data older than ".$num_months." months from OS ".$os." (".count($sequences_to_delete)." tests)");
	if (!$non_interactive)
		Util::pause();

	$i = 1;
	foreach ($sequences_to_delete as $file) {
		msg($i++."/".count($sequences_to_delete)."\r", FALSE);
		if (trim($file) == "")
			fatal("Invalid file, aborting");

		exec("rm -Rf ".$file, $res, $code);
		if ($code != 0)
			fatal("Failed to remove ".$file);
	}
	if ($i > 1)
		msg(--$i."/".count($sequences_to_delete)." done");
}

?>
