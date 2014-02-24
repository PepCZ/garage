<?php
	class Mysql {
		static function connect() {
			$DBH = null;
			try {
				# MySQL with PDO_MYSQL
				$DBH = new PDO("mysql:host=".$GLOBALS[CONFIG]['mysql']['host'].";dbname=".$GLOBALS[CONFIG]['mysql']['db'].";charset=".$GLOBALS[CONFIG]['mysql']['charset'], $GLOBALS[CONFIG]['mysql']['user'], $GLOBALS[CONFIG]['mysql']['pass']);
//				$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
//				$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT );
				$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

			}
			catch(PDOException $e) {
				echo $e->getMessage();
				//header('Location: _error_db_connection.html');
				echo 'NOK';
				exit;
			}
			return ($DBH);
		}

		static function close()
		{
			$DBH = null;
		}

	}

	/** Konverze CZ formatu data do SQL
	 */	
	function mysql_convert_date($d)
	{
		if (!preg_match("/^[0-3]?[0-9]\.[0-1]?[0-9].[0-2][0-9]{3}$/u", $d))
			return null;
		else
			return preg_replace("/^([0-3]?[0-9])\.([0-1]?[0-9]).([0-2][0-9]{3})$/u","\\3-\\2-\\1", $d);
	}
