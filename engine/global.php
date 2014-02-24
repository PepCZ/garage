<?php
	// uzitecne pri vice webech na jedne domene - aby se nemlatili nazvy v session
	define ('CONFIG','CONFIG');


	// pristup do mysql db

	$GLOBALS[CONFIG]['mysql']['host'] = 'wm35.wedos.net';  
	$GLOBALS[CONFIG]['mysql']['db'] = 'd44532_garage';
	$GLOBALS[CONFIG]['mysql']['user'] = 'w44532_garage';
	$GLOBALS[CONFIG]['mysql']['pass'] = 'FW4nr4tm';
	$GLOBALS[CONFIG]['mysql']['charset'] = "utf8";
	$GLOBALS[CONFIG]['foto_file'] = "foto/";

?>
