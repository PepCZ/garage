<?php
	require_once ('engine/all.php');
	$GLOBALS[CONFIG]['header']['onload'] .= "";
	$GLOBALS[CONFIG]['header']['others'] = '<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>'.NN;
	$GLOBALS[CONFIG]['header']['title'] = 'Konfigurátor';

	switch (@$_GET['action']) {
		case 'select':
			if (isset($_POST['sel'])) {
				$_SESSION['sel'] = $_POST['sel'];
				$_SESSION['name'] = fileNameID(implode(',',$_POST['sel']));
			}
			break;
	}
	
	require_once('engine/html_top.php');

?>
	<style type="text/css">
		table,tr,td,th {border:1px solid black; border-collapse:collapse;}
		td {text-align:right;}
		table {margin: 10px 0 10px 0;}
	</style>

	<form action="?action=select" method="post">
<?php
// nastaveni prvni moznosti
if (!isset($_SESSION['sel'])) {
	$STH = $DBH->prepare("SELECT * FROM selects ORDER BY ID_SELECTS");
	$STH->execute();
	$STH->setFetchMode(PDO::FETCH_ASSOC);
	while ($z = $STH->fetch()) {
		$STH2 = $DBH->prepare("SELECT * FROM options WHERE SELECTS_ID=? ORDER BY SELECTS_ID");
		$STH2->execute(array((int)$z['ID_SELECTS']));
		$STH2->setFetchMode(PDO::FETCH_ASSOC);
		$z2 = $STH2->fetch();
		$_SESSION['sel'][$z['ID_SELECTS']] = $z2['ID_OPTIONS'];
		$_SESSION['name'] = fileNameID(implode(',',$_SESSION['sel']));
	}
	//natvrdo navolena prvni platna garaz
	$_SESSION['sel']['1'] = 2;	
	//vytvoreni nazvu souboru
	$_SESSION['name'] = fileNameID(implode(',',$_SESSION['sel']));
}

//vypis selectu
	$STH = $DBH->prepare("SELECT * FROM selects ORDER BY ID_SELECTS");
	$STH->execute();
	$STH->setFetchMode(PDO::FETCH_ASSOC);
	while ($z = $STH->fetch()) {
		echo '<div>'.NN;
		echo '<b>'.htmlspecialchars($z['NAME']).': </b>'.NN;
		$STH2 = $DBH->prepare("SELECT * FROM options WHERE SELECTS_ID=? ORDER BY ID_OPTIONS");
		$STH2->execute(array((int)$z['ID_SELECTS']));
		$STH2->setFetchMode(PDO::FETCH_ASSOC);
		echo '	<select name="sel['.$z['ID_SELECTS'].']" onchange="this.form.submit();">'.NN;
		while ($z2 = $STH2->fetch()) {
			echo '		<option value="'.$z2['ID_OPTIONS'].'" '.($z2['ID_OPTIONS'] == $_SESSION['sel'][$z['ID_SELECTS']] ? 'selected="selected"' : '').'>'.htmlspecialchars($z2['NAME']).'</option>'.NN;
		}
		echo '</select>'.NN;
		echo '</div>'.NN;
	}
//	echo '<input type="submit" name="select" value="Vybrat" />';
?>
	</form>

<?php
	echo '<img src="foto/'.$_SESSION['name'].'" alt="obrázek není k dispozici" style="width:600px"/>'.NN;
?>
	</div>
	<div>
<?php
	if (isset($_SESSION['sel'])) {
	
		$STH = $DBH->prepare("SELECT * FROM options WHERE ID_OPTIONS IN (".implode(',',$_SESSION['sel']).") ORDER BY SELECTS_ID, ID_OPTIONS");
		$STH->execute();
		$STH->setFetchMode(PDO::FETCH_ASSOC);
		echo '	<table>'.NN;
		echo '		<tr>'.NN;	
		echo '			<th>Kategorie:</th>'.NN;
		echo '			<th>Volba:</th>'.NN;
		echo '			<th>Cena prefa dílců:</th>'.NN;
		echo '			<th>Cena práce (bez DPH):</th>'.NN;
		echo '			<th>Celková cena (bez DPH)</th>'.NN;
		echo '			<th>Celková cena:</th>'.NN;
		echo '		</tr>'.NN;

		$MAT_PRICE = $WORK_PRICE_DPH = $PRICE_DPH = $PRICE = 0;
		while ($z = $STH->fetch()) {
			$STH2 = $DBH->prepare("SELECT NAME FROM selects WHERE ID_SELECTS =?");
			$STH2->execute(array($z['SELECTS_ID']));
			$STH2->setFetchMode(PDO::FETCH_ASSOC);
			$z2 = $STH2->fetch();
			echo '		<tr>'.NN;	
			echo '			<th>'.htmlspecialchars($z2['NAME']).':</th>'.NN;
			echo '			<td>'.htmlspecialchars($z['NAME']).'</td>'.NN;
			echo '			<td>'.number_format(round($z['MAT_PRICE'],2),2,',',' ').' Kč</td>'.NN;
			$MAT_PRICE = $MAT_PRICE + $z['MAT_PRICE'];
			echo '			<td>'.number_format(round($z['WORK_PRICE_DPH'],2),2,',',' ').' Kč</td>'.NN;
			$WORK_PRICE_DPH = $WORK_PRICE_DPH + $z['WORK_PRICE_DPH'];
			echo '			<td>'.number_format(round($z['PRICE_DPH'],2),2,',',' ').' Kč</td>'.NN;
			$PRICE_DPH = $PRICE_DPH + $z['PRICE_DPH'];
			echo '			<td>'.number_format(round($z['PRICE'],2),2,',',' ').' Kč</td>'.NN;
			$PRICE = $PRICE + $z['PRICE'];
			echo '		</tr>'.NN;
		}
		echo '		<tr>'.NN;
		echo '			<th colspan="2">Součet:</th>'.NN;
		echo '			<td>'.number_format(round($MAT_PRICE,2),2,',',' ').' Kč</td>'.NN;
		echo '			<td>'.number_format(round($WORK_PRICE_DPH,2),2,',',' ').' Kč</td>'.NN;
		echo '			<td>'.number_format(round($PRICE_DPH,2),2,',',' ').' Kč</td>'.NN;
		echo '			<td>'.number_format(round($PRICE,2),2,',',' ').' Kč</td>'.NN;
		echo '		</tr>'.NN;	
		echo '	</table>'.NN;
	} else {
		echo '<div>'.NN;
		echo 'Není vybrána žádná možnost.'.NN;
		echo '</div>'.NN;
	}
?>
	</div>