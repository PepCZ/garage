<?php
	define ("NN","\n");

	######################### POCATECNI INICIALIZACE ################################

	require_once 'engine/global.php';
	require_once 'engine/mysql.inc.php';

	// HTML dalsi hlavicky
	$GLOBALS[CONFIG]['header']['others'] = "";
	// funkce volane javacriptem pri nacteni stranky
	$GLOBALS[CONFIG]['header']['onload'] = "";

	// chybove a informacni hlasky - doplnuji se pri ruznych udalostich
	$GLOBALS[CONFIG]['info'] = "";
	$GLOBALS[CONFIG]['info_html'] = "";
	$GLOBALS[CONFIG]['chyby'] = "";

	//pripojeni k DB
	$DBH = Mysql::connect();

	// spusteni kontroly autentizace
	require_once dirname(__FILE__).'/class_myauthentication.php';
	$GLOBALS[CONFIG]['auth'] = MyAuthentication::autoStart('garage');

	######################### FUNKCE ####################################
	function fileName($names)
	{
		global $DBH;
		$STH = $DBH->prepare("SELECT * FROM options WHERE NAME IN ($names) ORDER BY SELECTS_ID, ID_OPTIONS");
		$STH->execute();
		$STH->setFetchMode(PDO::FETCH_ASSOC);
		$nam = '';
		while ($z = $STH->fetch()) {
			$STH2 = $DBH->prepare("SELECT * FROM options WHERE SELECTS_ID=? ORDER BY ID_OPTIONS");
			$STH2->execute(array($z['SELECTS_ID']));
			$STH2->setFetchMode(PDO::FETCH_ASSOC);
			$opt = 0;
			while ($z2 = $STH2->fetch()) {
				if ($z['ID_OPTIONS'] === $z2['ID_OPTIONS']) {
					$nam .= ($opt < 10 ? '0'.$opt : $opt);
				}
				$opt = $opt +1;
			}
		}
		$nam .= '.JPG';
		return $nam;
	}

	function fileNameID($names)
	{
		global $DBH;
		$STH = $DBH->prepare("SELECT * FROM options WHERE ID_OPTIONS IN ($names) ORDER BY SELECTS_ID, ID_OPTIONS");
		$STH->execute();
		$STH->setFetchMode(PDO::FETCH_ASSOC);
		$nam = '';
		while ($z = $STH->fetch()) {
			$STH2 = $DBH->prepare("SELECT * FROM options WHERE SELECTS_ID=? ORDER BY ID_OPTIONS");
			$STH2->execute(array($z['SELECTS_ID']));
			$STH2->setFetchMode(PDO::FETCH_ASSOC);
			$opt = 0;
			while ($z2 = $STH2->fetch()) {
				if ($z['ID_OPTIONS'] === $z2['ID_OPTIONS']) {
					$nam .= ($opt < 10 ? '0'.$opt : $opt);
				}
				$opt = $opt +1;
			}
		}
		$nam .= '.JPG';
		return $nam;
	}
	
?>
