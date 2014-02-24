<?php
/*
	verze: 11.03.09 (od 10.06.01)
	t: 15h
*/

/** ODVOZENA TRIDA SE MUSI JMENOVAT PODLE PROMENNE $inheritedClassName !!!
 */
abstract class Authentication
{
	# # # # # # # # KONFIGURACE # # # # # # # #

	/** Nazev zdedene tridy, ktera pouziva tuto tridu a ktera je pouzivana
	 *  v aplikaci. Nelze bohuzel zjistit automaticky.
	 */	 	
	public static $inheritedClassName = "MyAuthentication"; 

	/** Umožnit ukládání loginu do cookies
	 *	V pripade zavreni a otevreni prohlizece nebude prihlaseni preruseno
	 */	 	 
	public $allowSaveLoginToCookies = true;

	/** automaticke ukladani uspesneho prihlaseni do cookies.
	 *	V pripade zavreni a otevreni prohlizece nebude prihlaseni preruseno
	 */	 	 
	public $autoSaveLoginToCookies = false;
	
	/** url adresa, na kterou se presmeruje funkce RedirectOnLoginFailed().
	 *  Nadefinuje se v MyAuthentication
	 */	
	public $redirectedPageOnLoginFailed;
	
	/** Je-li true, pri kazdem nacteni stranky se znovu zavola funkce, ktera
	 *	otestuje platnost uzivatelskych dat.
	 *	Je-li false (doporuceno), k overeni dojde pouze jednou. A po uspesnem prohlaseni
	 *	je tento stav trvaly po celou session. Do uzavreni prohlizece nebo odhlaseni
	 *	neni tedy znova platnost overovana.
	 */	 	 	 	 	 
	public $forceVerifyPermissionEverytime = false;
	
	/** funkce musí na zaklade jmena a hesla vratit informaci, zda je login korektni
	 *  ci ne. Zaroven muze uvest detaily o uzivateli, ktere se ulozi do session.
	 *		 	   
	 *  uziv jmeno je k dispozici v $this->user a heslo v $this->pass	 	 	 
	 *  
	 *  Na zaklade zjistenych informaci aktualizovat:
	 *  	$this->isLogged
	 *  	$this->userInfo
	 */	 	
	public abstract function decideAuthentication();

	/** Pokud byl zaznamenan pokus o otevreni stranky bez prihlaseni, sem se ulozi informace
	 *  o GETU, POSTu, apod. Jinak je null
	 */
	public $unfinishedPageLoad = null;
	
	# # # # # # # # KONEC KONFIGURACE # # # # # # # #

	protected $isLogged = false;
	protected $user = null;
	protected $passSHA1 = null;
	public  $userInfo = null;
	protected $authenticationID;

	/** Konstruktor
	 */	
	function __construct($authenticationID)
	{
		$this->authenticationID = $authenticationID;
		$this->initialize();
	}

	/** Funckce zaridi automaticky start autentifikace s kontrolou, jestli jiz
	 *  autentifikace neprobehla a neexistuje v session.
	 *  Nove vytvorenou autentifikaci pak do session automaticky uklada
	 */	 	 	
	public static function autoStart($authenticationID = 'default')
	{
		@session_start();			
		$authObject = null;
		if (isset($_SESSION['authentication'][$authenticationID]))
		{
			$authObject = $_SESSION['authentication'][$authenticationID];
			$authObject->initialize();
		}
		else
		{
			$authObject = new Authentication::$inheritedClassName($authenticationID);

			$_SESSION['authentication'][$authenticationID] = $authObject;

		}
		return $authObject;		
	}

	/** funkce provede otestovani stavu.
	 *  Funkce se vola po vytvoreni objektu nebo po jeho reinicializaci pomoci AutoStartu
	 */	 	 	
	public function initialize()
	{
		$pokusLoginu = false;
		
		// nacteni z COOKIES
		if ($this->allowSaveLoginToCookies)
		{
			$pokusLoginu = $pokusLoginu || $this->loginFromCookies();
		}

		// pokud je nutne pokazde overovat prihlaseni, overit nyni
		if ($this->forceVerifyPermissionEverytime && !$pokusLoginu && $this->isLogged)
		{
			$this->login();
		}
	}	

    /** Pokusi se nacist jmeno a heslo z cookies.
     *  Vraci bool, zda byly informace v cookies nalezeny.
     */	     
	public function loginFromCookies()
	{
		if (isset($_COOKIE['authentication_' . $this->authenticationID . '_user']) && isset($_COOKIE['authentication_' . $this->authenticationID . '_passSHA1']))
		{
			$this->loginFromStringSHA1(
				$_COOKIE['authentication_' . $this->authenticationID . '_user'],
				$_COOKIE['authentication_' . $this->authenticationID . '_passSHA1']
			);
			return true;
		}
		else
		{
			return false;
		}
	}

	/** Přijme přihlašovací údaje a zkusí provést autentizaci.
	 *  Autentizaci provede funkce login().	
	 */		
	public function loginFromString($user, $pass)
	{
		$this->loginFromStringSHA1($user, sha1($pass));
	}

   	/** Přijme přihlašovací údaje a zkusí provést autentizaci.
	 *  Autentizaci provede funkce login().
	 *  heslo je ve forme otisku SHA1	 	
	 */		
	public function loginFromStringSHA1($user, $passSHA1)
	{
		if ($this->forceVerifyPermissionEverytime || (!$this->isLogged || $user != $this->user || $passSHA1 != $this->passSHA1))
		{
			$this->user = $user;
			$this->passSHA1 = $passSHA1;

			$this->login();
		}
	}

	/** Provede zjisteni, zda uziv. jmeno a heslo ma opravneni te autentifikovat
	 *  Vysledek je pak ulozen do $this->isLogged. Pristup k ni je pres getIsLogged	
	 */	
	protected function login($saveToCookie = false)
	{
		if ($this->user !== null)
		{
			$isLoggedBefore = $this->isLogged;
			$this->decideAuthentication();

			if ($this->isLogged)
			{
				if ($this->autoSaveLoginToCookies || $saveToCookie)
					$this->saveLoginToCookies;
				if (!$isLoggedBefore)
					$this->onAfterLogin();
				return;
			}
		}
		// v pripade, ze se neprihlasilo...
		$this->logout();
		
	}
	
	private function saveLoginToCookies()
	{
		if ($this->isLogged && $this->allowSaveLoginToCookies && !headers_sent())
		{
			setcookie('authentication_' . $this->authenticationID . '_user', $this->user, time()+60*60*24*30); 	
			$_COOKIE['authentication_' . $this->authenticationID . '_user'] = $this->user; 	
			setcookie('authentication_' . $this->authenticationID . '_passSHA1', $this->passSHA1, time()+60*60*24*30);
			$_COOKIE['authentication_' . $this->authenticationID . '_passSHA1'] = $this->passSHA1; 	
		}
	}
	
	/** Udalost se vola pri selhani prihlaseni
	 */	
	public function redirectOnLoginFailed($saveUnfinishedPage = false)
	{
		if (!$this->isLogged)
		{
			//uzivatel neni prihlasen
			if(!empty($this->redirectedPageOnLoginFailed))
			{
				if (!$this->isLogged)
				{
					// ulozeni parametru nenactene stranky (pro pristi mozne donacteni)
					if($saveUnfinishedPage)
					{
						$this->unfinishedPageLoad = array(
							'url'=> $_SERVER['REQUEST_URI'],
							'_POST' => $_POST,
							'_GET' => $_GET,
							'_SERVER' => $_SERVER,
							'_FILES' => $_FILES
						);
					}
					
					if (!headers_sent())
					{
						//při špatném příhlášení původně header, ted připojuji index.php
						header('Location: ' . $this->redirectedPageOnLoginFailed);
//						header ('login.php');
						exit;
					}
					else
					{
						echo '<h1>Nejste prihlasen(a) / You are not logged.</h1>';
						echo '<p><strong>Pro prihlaseni pokracujte na <a href="' . htmlspecialchars($this->redirectedPageOnLoginFailed) . '">stranku s loginem</a>.</strong></p>';
						echo '<p><strong>To login please continue to <a href="' . htmlspecialchars($this->redirectedPageOnLoginFailed) . '">login page</a>.</strong></p>';
					}
					exit;
				}
			}
		}			
	}

	public function getIsLogged()
	{
		return $this->isLogged;
	}
	
	### ODHLASENI ###
	public function logout()
	{
		$this->user = NULL;
		$this->passSHA1 = NULL;
		if (!headers_sent())
		{
			setcookie('authentication_' . $this->authenticationID . '_user', '', -1);
			setcookie('authentication_' . $this->authenticationID . '_passSHA1', '', -1); 	
		}
		if (isset($_COOKIE['authentication_' . $this->authenticationID . '_user']))
			unset($_COOKIE['authentication_' . $this->authenticationID . '_user']); 	
		if (isset($_COOKIE['authentication_' . $this->authenticationID . '_passSHA1']))
			unset($_COOKIE['authentication_' . $this->authenticationID . '_passSHA1']);
		$this->userInfo = null; 	
		$this->isLogged = false;
		$this->unfinishedPageLoad = null;
	}
	
	public function getUserName()
	{
		return $this->user;
	} 

	public function isPassOK($pass)
	{
		return (sha1((string)$pass) == $this->passSHA1);
	} 

	public function isPassSHA1OK($passSHA1)
	{
		return ((string)$passSHA1 == $this->passSHA1);
	} 
}

?>
