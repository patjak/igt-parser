<?php

function cmd_list($path, $os, $machine)
{
	validate_input($path, $os, $machine, FALSE, FALSE);

	if ($os === FALSE) {
		print_oses($path);
		return;
	}

	if ($machine === FALSE) {
		print_machines($path, $os);
		return;
	}

	print_dates($path, $os, $machine);
}

?>
