<?php
	require_once 'engine/all.php';

	$GLOBALS[CONFIG]['header']['onload'] .= "";
	$GLOBALS[CONFIG]['header']['others'] = ''.NN;
	$GLOBALS[CONFIG]['header']['title'] = 'Přihlášení';


	switch (@$_GET['action'])
	{
		case 'logout':
			$GLOBALS[CONFIG]['auth']->logout();
			$GLOBALS[CONFIG]['info'] = 'odhlášen'.NN;
			break;
		case 'tryLogin':
			unset($_SESSION[CONFIG]['file']); //pokud má název souboru vymaze se a generuje nový (více uzivatelů na jednom pc)
			$GLOBALS[CONFIG]['auth']->loginFromString(
				(string)@$_POST['authentication_user'],
				(string)@$_POST['authentication_pass']
			);
			if (!$GLOBALS[CONFIG]['auth']->getIsLogged())
				$GLOBALS[CONFIG]['chyby'] = 'Špatné jméno nebo heslo.'.NN;
			break;
	}

	if ($GLOBALS[CONFIG]['auth']->getIsLogged())
	{
		header('Location: adminer.php');
	}
	require_once('engine/html_top.php');
?>
	<center>
		<h1>Přihlášení</h1>
		<form action="?action=tryLogin" method="post">
			<table class="center">
				<tbody>
				<tr>
					<th>E-mail:</th>
					<td>
						<input type="text" name="authentication_user" id="authentication_user" size="30" autofocus="1" />
					</td>
				</tr>

				<tr>
					<th>Password:</th>
					<td>
						<input type="password" name="authentication_pass" id="authentication_pass" size="30" />
					</td>
				</tr>

				<tr>
					<td align="center" colspan="2">
						<button type="submit">Přihlásit se</button>
					</td>
				</tr>
				</tbody>
			</table>
		</form>
	</center>
