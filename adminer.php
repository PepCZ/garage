<?php
	require_once ('engine/all.php');
	//opravneny pristup pouze autorizovanym uzivatelum
	$GLOBALS[CONFIG]['auth']->redirectOnLoginFailed();
	$GLOBALS[CONFIG]['header']['onload'] .= "";
	$GLOBALS[CONFIG]['header']['others'] = ''.NN;
	$GLOBALS[CONFIG]['header']['title'] = 'Nastavení konfigurátoru';

	switch (@$_GET['action']) {

		//nahrát kategorii
		case 'uploadSelects':
		if (isset($_POST['set'])) {
				$p = trim($_POST['NAME']);
				//prilis dlouhy text -> chyba
				if (strlen($p) < 45 and !empty($p)) {
					try {
						$STH = $DBH->prepare("INSERT INTO selects (NAME) values (?)");
						$STH->execute(array($p));
					} catch(PDOException $e) {
						if ($e->errorInfo[1] == 1062) {
							$GLOBALS[CONFIG]['chyby'] .= "Tato kategorie již existuje!";
						}
					}
				} else {
					$GLOBALS[CONFIG]['chyby'] .= "Nová kategorie má příliš dlohý název, nebo neobsahuje žádné znaky!";
				}
				if (empty($GLOBALS[CONFIG]['chyby'])){
					if ($handle = opendir($GLOBALS[CONFIG]['foto_file'])) {
						//prejmenovani souboru
						while (false !== ($entry = readdir($handle))) {
							//pridani 00 na konec souboru
							@rename($GLOBALS[CONFIG]['foto_file'].$entry,$GLOBALS[CONFIG]['foto_file'].substr_replace($entry, '', -4, 4).'00.JPG');
							@chmod($GLOBALS[CONFIG]['foto_file'].substr_replace($entry, '', -4, 4).'00.JPG', 0777); 
						}
						closedir($handle);
					}
				}
			}
			header("Location: adminer.php");
			exit();	
			break;

		//přejmenovat kategorii
		case 'renameSelects':
			if (isset($_POST['rename']) && !empty($_POST['rename'])) {
				try {
					//zjisteni pouice ID_SELECTS
					$STH = $DBH->prepare("UPDATE selects SET NAME = ? WHERE ID_SELECTS=?");
					$STH->execute(array($_POST['NAME'],(int)$_POST['rename']));
				} catch(PDOException $e) {
					$GLOBALS[CONFIG]['chyby'] .= "Nepodařilo se přejmenovat název. Buď je moc dlouhý, nebo název již existuje.";
				}						
			}
			header("Location: adminer.php");
			exit();	
			break;

		//upravit fšechny možnosti kategorie
		case 'updateOptions':
			if (isset($_POST['updateOptions']) and isset($_POST['VAL'])) {
				foreach($_POST['VAL'] as $id => $arr) {
					foreach ($arr as $col=>$name) {
						try {
							$STH = $DBH->prepare("UPDATE options SET ".$col." = ? WHERE ID_OPTIONS=?");
							$STH->execute(array($name,$id));
						} catch(PDOException $e) {
							$GLOBALS[CONFIG]['chyby'] = "Nepodařilo se překonfigurovat možnosti. Název musí být unikátní nebo cena nebí číslo.";
						}
					}
				}

				//mazani vsech souboru s danou moznosti
				if (isset($_POST['DEL'])) {
					$STH = $DBH->prepare("SELECT * FROM options WHERE SELECTS_ID IN (SELECT SELECTS_ID FROM options WHERE ID_OPTIONS=?)");
					$STH->execute(array($_POST['DEL']));
					$STH->setFetchMode(PDO::FETCH_ASSOC);
					$i=0;
					while($z = $STH->fetch()) {
						 if ($_POST['DEL'] === $z['ID_OPTIONS']) {
							$val = $i;
						}
						$i++;
					}
					$id = $_POST['DEL'];

					$STH = $DBH->prepare("SELECT SELECTS_ID FROM options WHERE ID_OPTIONS =?");
					$STH->execute(array($id));
					$STH->setFetchMode(PDO::FETCH_ASSOC);
					$IDsel = $STH->fetch();

					$STH2 = $DBH->prepare("SELECT * FROM selects ORDER BY ID_SELECTS");
					$STH2->execute();
					$STH2->setFetchMode(PDO::FETCH_ASSOC);
					$i = 0;
					while($z = $STH2->fetch()) {
						if (($z['ID_SELECTS'] == $IDsel['SELECTS_ID']) && !empty($z['ID_SELECTS'])){
							$STH3 = $DBH->prepare("DELETE FROM options WHERE ID_OPTIONS =?");
							$STH3->execute(array($id));
//							echo "val:$val,i:$i<br>";
							//mazani souboru
							$posit = ($i*2);
							if ($handle = opendir('foto/')) {
								while (false !== ($entry = readdir($handle))) {
//							echo substr($entry,$posit,2)." == ".($val < 10 ?'0'.$val : $val).'</br>';

								//smazani souboru obsahujici danou volbu
									if (substr($entry,$posit,2) == ($val < 10 ?'0'.$val : $val)) {
//										echo "     unlink: foto/$entry </br>";
										unlink ("foto/$entry");
									}
								}
								closedir($handle);
							}
							//prejmenovani souboru
							if ($handle = opendir('foto/')) {
								while (false !== ($entry = readdir($handle))) {
									if ((int)substr($entry,$posit,2) > $val){
										$tmp = (string)(((int)substr($entry,$posit,2) - 1) < 10 ? '0'.((int)substr($entry,$posit,2) - 1) : ((int)substr($entry,$posit,2) - 1));
										$tmp = substr_replace($entry,$tmp,$posit,2);
//										echo "rename: foto/$entry   foto/$tmp <br> ";
										rename("foto/$entry","foto/$tmp");
									}
								}
								closedir($handle);
							}

						}
						$i++;
					}
				}
//				exit();
			}
			header("Location: adminer.php");
			exit();	

			break;

		//vložit novou možnost dané kategorie
		case 'insertOptions':
			if (isset($_POST['insertOptions'])) {
				try {
					$STH = $DBH->prepare("INSERT INTO options 
											(NAME,MAT_PRICE,WORK_PRICE_DPH,PRICE_DPH,PRICE,SELECTS_ID) 
											VALUES (?,?,?,?,?,?)");
					$STH->execute(array($_POST['NAME'],$_POST['MAT_PRICE'],$_POST['WORK_PRICE_DPH'],$_POST['PRICE_DPH'],$_POST['PRICE'],$_POST['SELECTS_ID']));
				} catch(PDOException $e) {
					$GLOBALS[CONFIG]['chyby'] = "Nepodařilo se přidat novou možnost.";
				}						
			}
			header("Location: adminer.php");
			exit();	
			break;

		//nahrát soubor
		case 'uploadFile':
			if (isset($_POST['uploadFile']) && (!empty($_POST['fileName']) && isset($_FILES['file']))) {
				if (isset($_FILES['file']) && ($_FILES['file']['type'] == 'image/jpeg' or $_FILES['file']['type'] == 'image/jpg') && ($_FILES['file']['size'] < 2000000))  {
					if ($_FILES['file']['error'] > 0) {
						$GLOBALS[CONFIG]['chyby'] .= 'Chyba souboru.' ;
					} else {
						//mazání souboru
						if (file_exists('foto/'.trim($_POST['fileName']))) {
							unlink('foto/'.trim($_POST['fileName']));
						}
			
						if (empty($GLOBALS[CONFIG]['chyby'])) {
							move_uploaded_file($_FILES['file']['tmp_name'],'foto/'.trim($_POST['fileName']));
						}
					}
				} else {
					$GLOBALS[CONFIG]['chyby'] .= 'Soubor musí být menší než 20MB a ve formátu JPG.' ;
				}
			} else if (isset($_POST['deleteFile']) && !empty($_POST['fileName'])) {
				if (file_exists('foto/'.trim($_POST['fileName']))) {
					unlink('foto/'.trim($_POST['fileName']));
				} else {
					$GLOBALS[CONFIG]['chyby'] .= 'Soubor neexistuje.' ;
				}
			}
			header("Location: adminer.php");
			exit();	
			break;		
	

		}
	// vlozeni horniho menu a ostatniho uvodniho HTML
	require_once 'engine/html_top.php';

?>
<style type="text/css">
	table,tr,td,th {border:1px solid black; border-collapse:collapse;}
	table {margin: 10px 0 10px 0;}
</style>
<?php
	
	//seznam kategorii s moznosti přejmenovani
	$STH = $DBH->prepare("SELECT * FROM selects ORDER BY ID_SELECTS");
	$STH->execute();
	$STH->setFetchMode(PDO::FETCH_ASSOC);
	echo '<div style="border: 2px solid black; margin:10px; padding:5px">'.NN;
	echo '<h3>Upravit kategorie:</h3>'.NN;
	while ($z = $STH->fetch()) {
	echo '<form action="?action=renameSelects" method="post">';
	echo '<table>'.NN;
		echo '<tr>'.NN;
		echo '	<th><input type="text" name="NAME"  value="'.htmlspecialchars($z['NAME']).'"/></th>'.NN;
		echo '	<td><button type="submit" name="rename" value="'.$z['ID_SELECTS'].'">Přejmenovat kategorii</button></td>'.NN;
		echo '</tr>'.NN;
	echo '</table>'.NN;
	echo '</form>';
	}
	
	echo '<form action="?action=uploadSelects" method="post">';
	echo '<table>'.NN;
	echo '<tr>'.NN;	
	echo '	<th colspan="2">přidat nový výběr:</th>'.NN;
	echo '</tr>'.NN;	
	echo '	<tr>'.NN;
	echo '		<td>název: <input type="text" name="NAME" value="" /></td>'.NN;
	echo '		<td><input type="submit" name="set" value="Přidat novou kategorii" /></td>'.NN;
	echo '	</tr>'.NN;
	echo '</table>'.NN;
	echo '</form>'.NN;
	echo '</div>'.NN;
	
	$STH = $DBH->prepare("SELECT * FROM selects ORDER BY ID_SELECTS");
	$STH->execute();
	$STH->setFetchMode(PDO::FETCH_ASSOC);
	while ($z = $STH->fetch()) {
		$STH2 = $DBH->prepare("SELECT * FROM options WHERE SELECTS_ID=? ORDER BY ID_OPTIONS");
		$STH2->execute(array((int)$z['ID_SELECTS']));
		$STH2->setFetchMode(PDO::FETCH_ASSOC);
		
		//nova kategorie
		echo '<div style="border: 2px solid black; margin:10px; padding:5px">'.NN;
		echo '<h3>'.htmlspecialchars($z['NAME']).'</h3>'.NN;

		//upravit jednotlive moznosti v kategorii
		echo '<form action="?action=updateOptions" method="post">'.NN;
		echo '<table>'.NN;
		echo '	<tr>'.NN;
		echo '		<th colspan="5">Upravit možnosti:</th>'.NN;
		echo '	</tr>'.NN;
		echo '	<tr>'.NN;
		echo '		<th>Název:</th>'.NN;			
		echo '		<th>Cena materiálu:</th>'.NN;
		echo '		<th>Cena práce (bez DPH):</th>'.NN;
		echo '		<th>Celková cena (bez DPH)</th>'.NN;
		echo '		<th>Celková cena:</th>'.NN;		
//		echo '		<th>Odstranit:</th>'.NN;	
		echo '	</tr>'.NN;
		$i = 0;
		while ($z2 = $STH2->fetch()) {
			echo '	<tr>'.NN;
			echo '		<th><input type="text" name="VAL['.$z2['ID_OPTIONS'].'][NAME]" value="'.htmlspecialchars($z2['NAME']).'"/></th>'.NN;
			echo '		<td><input type="text" name="VAL['.$z2['ID_OPTIONS'].'][MAT_PRICE]" value="'.htmlspecialchars($z2['MAT_PRICE']).'" /></td>'.NN;
			echo '		<td><input type="text" name="VAL['.$z2['ID_OPTIONS'].'][WORK_PRICE_DPH]" value="'.htmlspecialchars($z2['WORK_PRICE_DPH']).'"/></td>'.NN;
			echo '		<td><input type="text" name="VAL['.$z2['ID_OPTIONS'].'][PRICE_DPH]" value="'.htmlspecialchars($z2['PRICE_DPH']).'"/></td>'.NN;
			echo '		<td><input type="text" name="VAL['.$z2['ID_OPTIONS'].'][PRICE]" value="'.htmlspecialchars($z2['PRICE']).'"/></td>'.NN;
//moznost odstranit zakazana
//			echo '		<td><input type="radio" name="DEL" value="'.$z2['ID_OPTIONS'].'"/></td>'.NN;
			echo '	</tr>'.NN;
		}
		echo '	<tr>'.NN;
		echo '		<td colspan="5"><input type="submit" name="updateOptions" value="Upravit jednotlivé možnosti"/></td>'.NN;
		echo '	</tr>'.NN;
		echo '</table>'.NN;
		echo '</form>'.NN;
		
		//vlozit novou moznost
		echo '<form action="?action=insertOptions" method="post">';
		echo '<table>'.NN;
		echo '	<tr>'.NN;
		echo '		<th colspan="6">Přidat novou možnost:</th>'.NN;
		echo '	</tr>'.NN;
		echo '	<tr>'.NN;
		echo '		<td>Název: <input type="text" name="NAME" value="" /></td>'.NN;
		echo '		<td>Cena materiálu: <input type="text" name="MAT_PRICE" value="" /></td>'.NN;
		echo '		<td>Cena práce (bez DPH): <input type="text" name="WORK_PRICE_DPH" value="" /></td>'.NN;
		echo '		<td>Celková cena (bez DPH): <input type="text" name="PRICE_DPH" value="" /></td>'.NN;
		echo '		<td>Celková cena : <input type="text" name="PRICE" value="" /></td>'.NN;
		echo '		<td><input type="hidden" name="SELECTS_ID" value="'.$z['ID_SELECTS'].'" />'.NN;
		echo '		<input type="submit" name="insertOptions" value="Přidat novou možnost" /></td>'.NN;
		echo '	</tr>'.NN;
		echo '</table>'.NN;
		echo '</form>'.NN;
		echo '</div>'.NN;
	}
	$STH = $DBH->prepare("SELECT * FROM selects ORDER BY ID_SELECTS");
	$STH->execute();
	$STH->setFetchMode(PDO::FETCH_ASSOC);
	$pole = array();
	$i = 0;
	while ($z = $STH->fetch()) {
		$STH2 = $DBH->prepare("SELECT * FROM options WHERE SELECTS_ID=? ORDER BY ID_OPTIONS");
		$STH2->execute(array((int)$z['ID_SELECTS']));
		$STH2->setFetchMode(PDO::FETCH_ASSOC);
		$tmp =false;
		while ($z2 = $STH2->fetch()) {
			$pole[$i][] = $z2['NAME'];
			$tmp =true;
		}
		if ($tmp) {
			$i = $i +1;
		}
	}
//nastaveni poli
//	$pole = array(
//              array('1', '2', '3'),
//               array('1', '2', '3'),
//               array('1', '2', '3'),
//              array('1', '2', '3'),
//	);
	// vypis kombinaci
	function kombinace($arrays, $index = 0)
	{
		// priprava statickych promennych
        static $a, $l, $p, $o;
        if($index === 0) {
			$a = $arrays;
			$l = sizeof($arrays) - 1;
			$p = array();
		}
        // rekurzivni kombinovani
		for($i = 0; isset($a[$index][$i]); ++$i) {
			$p[] = $a[$index][$i];
			if($index !== $l) kombinace(null, $index + 1);
			else $o[] = $p;
			array_pop($p);
		}
		// navraceni vysledku (a vynulovani pouzitych stat. prom.)
        if($index === 0) {
			$a = $l = $p = null;
            $otmp = $o;
            $o = null;
			return $otmp;
		}
	}

	//ziskani pole s kombinacemi
	$arr = kombinace($pole);
	echo '<table>'.NN;
	if (!empty($arr)) {
		foreach ($arr as $ar) {
			$odd = "";
			$tmp = "";
			foreach ($ar as $name) {
				$tmp .= "$odd'".$name."'";
				$odd = ",";
			}
			$atxt = fileName($tmp);
			if (file_exists("foto/".fileName($tmp))) {
				$style = 'style="background-color:#00C322"';
				$href = 'href="foto/'.fileName($tmp).'"';
			} else {
				$style = 'style="background-color:#BF3030"';
				$href = '';
			}
			echo '<tr '.$style.'>'.NN;
			foreach ($ar as $name) {
				echo '	<td '.$style.'>'.htmlspecialchars($name).'</td>'.NN;
			}
			echo '	<td style="width:600px">'.NN;
			echo '		<form action="?action=uploadFile" method="post" enctype="multipart/form-data">'.NN;
			echo '<a '.$href.'>'.$atxt.'</a>'.NN;
			echo '			<input type="hidden" name="fileName" value="'.$atxt.'" />'.NN;
			echo '			<input type="file" name="file" />'.NN;
			echo '			<input type="submit" name="uploadFile" value="Nahrát soubor" />'.NN;
			echo '			<input type="submit" name="deleteFile" value="Smazat soubor" />'.NN;
			echo '		</form>'.NN;
			echo '	</td>'.NN;
			echo '</tr>'.NN;
		}
		echo '</table>'.NN;
	}
?>
