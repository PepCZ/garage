<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<?php
	echo '<title lang="cs">'.htmlspecialchars($GLOBALS[CONFIG]['header']['title']).'</title>'.NN;
?>
	<meta lang="cs" name="copyright" content="" />
	<meta name="Robots" content="noindex,nofollow" />

    <link rel="stylesheet" href="css/layout3.css" type="text/css" />
    <?php
	echo $GLOBALS[CONFIG]['header']['others'];
	echo '</head>'.NN;
	echo '	<body'.(!empty($GLOBALS[CONFIG]['header']['onload']) ? ' onload="'.htmlspecialchars($GLOBALS[CONFIG]['header']['onload']).'"' : '').'>'.NN;

	// prihlaseny uzivatel
	echo '		<div>'.NN;
	if ($GLOBALS[CONFIG]['auth']->getIsLogged()){
		echo '		<a>přihlášen:'.' <strong>'.$GLOBALS[CONFIG]['auth']->getUserName().' </strong></a>';
		echo '		<a href="login.php?action=logout">odhlásit se</a>';
		} else {
		echo '		<a href="login.php">nepřihlášen</a>';
	}
	echo '		</div>'.NN;
?>
		</div>
<?php
	
	// VYPIS CHYB A INFORMACI
	if (!empty($GLOBALS[CONFIG]['chyby']))
		echo '<div>'.nl2br(htmlspecialchars($GLOBALS[CONFIG]['chyby'])).'</div>';
	if (!empty($GLOBALS[CONFIG]['info']) || !empty($GLOBALS[CONFIG]['info_html']))
		echo '<div>'.nl2br(htmlspecialchars($GLOBALS[CONFIG]['info'])).$GLOBALS[CONFIG]['info_html'].'</div>';
?>
