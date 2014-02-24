<?php
	require_once dirname(__FILE__).'/class_authentication.php';
	
	class MyAuthentication extends Authentication
	{
		public $redirectedPageOnLoginFailed = 'login.php';

		public function decideAuthentication()
		{
			global $DBH;
			$STH = $DBH->prepare("SELECT
					ID_USER,
					TRIM(PASS) as PASS,
					TRIM(EMAIL) as EMAIL
				FROM users WHERE EMAIL= ?");
			
			$STH->execute(array(strtolower($this->user)));
			$STH->setFetchMode(PDO::FETCH_ASSOC);
			$z = $STH->fetch();
			if (!$z || sha1(rtrim($z['PASS'])) != $this->passSHA1) {
				$this->isLogged = false;
				$this->userInfo = null;
			} else {
				$this->isLogged = true;
				$this->userInfo = $z;
				$STH = $DBH->prepare("UPDATE users SET LAST_IP=? WHERE ID_USER=?");
				$STH->execute(array($_SERVER['REMOTE_ADDR'],(int)$z['ID_USER']));
			}
		}

		protected function finishPageLoad()
		{
			if (!empty($this->unfinishedPageLoad))
			{
				header('Location: __finishpageload.php');
				exit;
			}
		}
		
		protected function onAfterLogin() {
			global $DBH;
			$STH = $DBH->prepare("UPDATE users SET LAST_IP=? WHERE ID_USER=?");
			$STH->execute(array($_SERVER['REMOTE_ADDR'],(int)$GLOBALS[CONFIG]['auth']->userInfo['ID_USER']));
		}
	}
?>
