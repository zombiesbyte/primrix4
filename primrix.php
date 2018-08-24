<?php

/**
 * Primrix CLI
 *
 * @author       James Dalgarno <james@imagewebdesign.co.uk>
 * @copyright    James Dalgarno 2014
 * @license      license.txt The MIT License (MIT)
 * @package      Primrix 4.0
 * @version      1.0
 */

	//create an instance of Primrix (CLI)
	$primrix = new Primrix;

	//get our argument count
	$totalArgs = count($argv);

	//lets go do our command
	if($totalArgs > 1){
		
		$command = str_replace('-', '', $argv[1]);
		$command = ucfirst($command);
		
		//make our argument package nice and neat
		$args = array();
		for($n = 2; $n <= 4; $n++){
			if(!isset($argv[$n])) $argv[$n] = '';
			$args[] = $argv[$n];
		}

		//find our command method
		if(method_exists($primrix, 'do' . $command)){
			$method = 'do' . $command;
			$primrix->$method($args);
		}
		else $primrix->doError($argv[1]);

	}
	else $primrix->doAbout();


	//start our class
	class Primrix
	{
		//variables and object declaration
		public static $table;
		public static $columns;

		public $primrixArray;
		public $env;
		public $defaultConn;
		public $dbPrefix;

		protected $_db;

		//alias of doAbout
		public function doV()
		{
			$this->doAbout();
		}

		public function doAbout()
		{
			echo "\n";
			echo "Primrix 4 (C)ommand (L)ine (I)nterface (CLI Version 1.0)\n";
			echo "Copyright (c) 2009-2014 Primrix\n";
			echo "James Dalgarno <james@imagewebdesign.co.uk>\n";
		}

		public function doError($arg)
		{
			echo "\n{$arg} [!] Command not found!\n\n";
			$this->doHelp();
		}

		//alias of doHelp
		public function doH()
		{
			$this->doHelp();
		}

		public function doHelp()
		{
			echo "\n primrix Usage:\n";
			echo " -flag | [available options] | {optional custom preference} | <named target>\n\n";

			echo " -v | -about                      Show version information\n";
			echo " -h | -help                       Show this help message\n";
			echo " -vhost                           Example VHost settings (httpd-vhosts.conf)\n";
			echo " -hal                             \n";
			echo " -new   [migration] <name>        Create a new boilerplate class.\n";
			echo "        [seed]      <name>        \n";
			echo " -table [build]     <name>{,..}   Setup a database table listed within\n";
			echo "        [revert]    <name>{,..}   the database/migrations folder. Specify\n";
			echo "        {custom}    <name>{,..}   'build' to setup the table or 'revert' to\n";
			echo "                                  activate the roll-back script. {or custom}\n";
			echo " -seed  <table>     <name>{,..}   Activate the chosen seed script <name>\n";

			echo "\n";
		}

		//this should be called something else and .htaccess for live settings added too.
		public function doVHost($args)
		{
			$cWD = getcwd();
			$cWD = str_replace('\\', '/', $cWD);

			echo "\n";
			echo "-----------------------------------------------------------------------\n";
			echo "The following block can be used in your httpd-vhosts.conf file.\n";
			echo "Default path example: <Drive>:\\{AMP Virtual Server}\\apache\conf\\extra\\\n";
			echo "Please remember to edit your host file too with the local serverName\n";
			echo "i.e. For Windows [C:\\Windows\\System32\\drivers\\etc] | Linux [/etc/hosts]\n";
			echo "-----------------------------------------------------------------------\n";
			echo "\n";
			echo "<VirtualHost *:80>\n";
			echo "\tServerName primrix4.dev\n";
			echo "\tDocumentRoot \"{$cWD}/public/\"\n";
			echo "\tErrorLog \"{$cWD}/logs/error.log\"\n";
			echo "\tCustomLog \"{$cWD}/logs/access.log\" common\n";
			echo "\t<Directory \"{$cWD}/public/\">\n";
			echo "\t\tOptions FollowSymLinks\n";
			echo "\t\tAllowOverride None\n";
			echo "\t</Directory>\n";
			echo "\tRewriteEngine On\n";
			echo "\tRewriteRule ^/(index\.php|main\.*|site\.*|vendor\.*).* - [s=1]\n";
			echo "\tRewriteRule ^/(.*)$ /index.php/$1\n";
			echo "</VirtualHost>\n\n";

			//composer help - make a batch file with @php "%~dp0composer.phar" %* and save it as composer.bat in root of virtualserver i.e. c:\x
			//install composer to the same root directory. PATH in Environment Variables in windows doesn't seem to be needing composer to run globally?
		}

		public function doNew($args)
		{
			//lets get our base path
			$basePath = str_replace("\\", "/", __DIR__);

			if($args[0] == 'migration'){
				
				$boiler = file_get_contents($basePath . '/database/templates/table_migration.php');
				$injectArray = [
					'ClassName' => $args[1],
					'tableName' => strtolower($args[1])
				];

				foreach($injectArray as $placeHolder => $value){
					$boiler = str_replace('{' . $placeHolder . '}', $value, $boiler);
				}

				$newFile = $basePath . '/database/migrations/' . $injectArray['tableName'] . '.php';
				$handle = fopen($newFile, 'w') or die('Cannot open file:  ' . $newFile);
				fwrite($handle, $boiler);

				echo "\nPrimrix new migration `{$injectArray['tableName']}.php` completed successfully!\n";

			}
			else if($args[0] == 'seed'){
				$boiler = file_get_contents($basePath . '/database/templates/table_seed.php');
				$injectArray = [
					'ClassName' => $args[1],
					'tableName' => strtolower($args[1])
				];

				foreach($injectArray as $placeHolder => $value){
					$boiler = str_replace('{' . $placeHolder . '}', $value, $boiler);
				}

				$newFile = $basePath . '/database/seeds/' . $injectArray['tableName'] . '.php';
				$handle = fopen($newFile, 'w') or die('Cannot open file:  ' . $newFile);
				fwrite($handle, $boiler);

				echo "\nPrimrix new seed `{$injectArray['tableName']}.php` completed successfully!\n";				
			}
			else{
				echo "\nprimrix -new action not specified or command is invalid\n";
			}
		}

		public function doSeed($args)
		{
			$multiRun = explode(',', $args[0]);
			if(count($multiRun) > 1){
				$this->_doSeedMulti($multiRun);
				return true;
			}			
			if($args[1] != '' and $args[0] != ''){
				$basePath = str_replace("\\", "/", __DIR__);
				if(is_file($basePath . '/database/seeds/' . $args[1] . '.php')){

					//let require our class file and create our new instance
					require($basePath . '/database/seeds/' . $args[1] . '.php');
					$seed = new $args[1];
					
					//lets call our method
					$seed->seed($args[0]);

				}
			}
			else echo "\nprimrix -seed needs to have a specified seed script and table name\n";
		}

		protected function _doSeedMulti($multiRun)
		{
			foreach($multiRun as $arg_0){
				if($arg_0 != ''){
					$basePath = str_replace("\\", "/", __DIR__);
					if(is_file($basePath . '/database/seeds/' . $arg_0 . '.php')){

						//let require our class file and create our new instance
						require($basePath . '/database/seeds/' . $arg_0 . '.php');
						$seed = new $arg_0;
						
						//lets call our method
						$seed->seed($arg_0);
					}
				}
				else echo "\nprimrix -seed needs to have a specified seed script and table name\n";
			}
		}

		public function doTable($args)
		{
			
			$multiRun = explode(',', $args[1]);
			if(count($multiRun) > 1){
				$this->_doTableMulti($args[0], $multiRun);
				return true;
			}

			if($args[1] != '' and $args[0] != ''){
				//lets get our base path
				$basePath = str_replace("\\", "/", __DIR__);

				if(is_file($basePath . '/database/migrations/' . $args[1] . '.php')){

					//let require our class file and create our new instance
					require($basePath . '/database/migrations/' . $args[1] . '.php');
					$migrate = new $args[1];

					if(method_exists($migrate, $args[0])){
						//lets call our method
						$migrate->$args[0]();

					}
					else echo "\nError: '{$args[0]}' does not exist within /database/migrations/{$args[1]}.php\n";
				}
				else echo "\nprimrix error: migration class not found\n\n";
			}
			else{
				echo "\nprimrix -table needs to have a specified migration and method action\n";
				if($args[1] == '') echo "use primrix -table {name of migration} build|revert|{custom method}\n";
				else echo "use primrix -table {$args[1]} build|revert|{custom method}\n";
			}
		}

		protected function _doTableMulti($arg_0, $multiRun)
		{
			foreach($multiRun as $arg_1){
				if($arg_1 != '' and $arg_0 != ''){
					//lets get our base path
					$basePath = str_replace("\\", "/", __DIR__);

					if(is_file($basePath . '/database/migrations/' . $arg_1 . '.php')){

						//let require our class file and create our new instance
						require($basePath . '/database/migrations/' . $arg_1 . '.php');
						$migrate = new $arg_1;

						if(method_exists($migrate, $arg_0)){
							//lets call our method
							$migrate->$arg_0();

						}
						else echo "\nError: '{$arg_0}' does not exist within /database/migrations/{$arg_1}.php\n";
					}
					else echo "\nprimrix error: migration class not found\n\n";
				}
				else{
					echo "\nprimrix -table needs to have a specified migration and method action\n";
					if($arg_1 == '') echo "use primrix -table {name of migration} build|revert|{custom method}\n";
					else echo "use primrix -table {$arg_1} build|revert|{custom method}\n";
				}
			}
		}

		protected function _getSettings($connectionID = null)
		{
			//lets get our base path
			$basePath = str_replace("\\", "/", __DIR__);

			$json = file_get_contents($basePath . '/public/site/config/settings.php', null, null, 15);
			$this->primrixArray = json_decode($json, true); //make the data available as an array

			$this->env = $this->primrixArray['environment'];
			if($connectionID == null) $this->defaultConn = $this->primrixArray['database']['default'];
			else $this->defaultConn = $this->primrixArray['database'][$connectionID];
			$this->dbPrefix = $this->primrixArray['database'][$this->env][$this->defaultConn]['prefix'];
		}

		protected function _seedTable($table, $dataArray, $connectionID = null)
		{
			$this->_getSettings($connectionID);

			$sqlHeaders = false;
			$sql = "";
			$sql .= "INSERT INTO `{$this->dbPrefix}{$table}` ";
			foreach($dataArray as $dataBlock){
				if(!$sqlHeaders){
					$sql .= '(';
					foreach($dataBlock as $field => $value){
						$sql .= "`{$field}`, ";
					}
					$sql = substr($sql, 0, -2);
					$sql .= ') ';
					$sql .= 'VALUES ';
					$sqlHeaders = true;
				}
				$sql .= '(';
				foreach($dataBlock as $field => $value){
					if($value != 'NOW()') $sql .= "'{$value}',";
					else $sql .= "{$value},";
				}
				$sql = substr($sql, 0, -1);
				$sql .= '),';
			}
			$sql = substr($sql, 0, -1);

			if($this->_executeSQL($sql)) echo "\nPrimrix seed for table `{$this->dbPrefix}{$table}` completed successfully!\n";
			else{
				echo "\nPrimrix seed has problems, please check your SQL\n";
				echo "SQL:\n";
				echo $sql . "\n";
			}
		}

		protected function _createTable($connectionID = null)
		{
			
			$this->_getSettings($connectionID);

			$sql = '';
			$create_table = "CREATE TABLE IF NOT EXISTS `{$this->dbPrefix}" . self::$table->name . '`';
			$table_properties = 'ENGINE=' . self::$table->engine;
			$table_properties .= ' DEFAULT CHARSET=' . self::$table->charset;
			$table_properties .= ' COLLATE=' . self::$table->collate;
			$table_properties .= ';';

			foreach(self::$columns as $fieldName => $properties){
				
				if(isset($properties['lenval'])) $sql .= "`{$fieldName}` {$properties['type']}({$properties['lenval']}) ";
				else $sql .= "`{$fieldName}` {$properties['type']} ";

				if(isset($properties['collate'])) 	$sql .= "COLLATE {$properties['collate']} ";
				if(isset($properties['not_null']) and $properties['not_null'] == true) $sql .= 'NOT NULL ';
				if(isset($properties['default'])) 	$sql .= "DEFAULT '{$properties['default']}' ";
				if(isset($properties['auto_increment']) and $properties['auto_increment'] == true) $sql .= 'AUTO_INCREMENT ';
				if(isset($properties['attribute'])) $sql .= strtoupper($properties['attribute']) . ' ';
				if(isset($properties['comment'])) 	$sql .= "COMMENT '{$properties['comment']}' ";

				$sql = substr($sql, 0, -1) . ",\n";
			}
			
			if(isset(self::$table->primary)) $sql .= "PRIMARY KEY (`" . self::$table->primary . "`),";
			if(isset(self::$table->index)) $sql .= "KEY `" . self::$table->index . "` (`" . self::$table->index . "`),";
			if(isset(self::$table->unique)) $sql .= "UNIQUE `" . self::$table->unique . "` (`" . self::$table->unique . "`),";
			if(isset(self::$table->fullText)) $sql .= "FULLTEXT KEY `" . self::$table->fullText . "` (`" . self::$table->fullText . "`),";

			$sql = substr($sql, 0, -1);
			$qString = $create_table . '(' . $sql . ')' . $table_properties;
			
			if($this->_executeSQL($qString)) echo "\nPrimrix migration build `{$this->dbPrefix}" . self::$table->name . "` completed successfully!\n";
			else{
				echo "\nPrimrix migration has problems, please check your SQL\n";
				echo "SQL:\n";
				echo $qString . "\n";
			}
		}

		protected function _executeSQL($qString)
		{
			$conn = [
				"dbtype" => $this->primrixArray['database'][$this->env][$this->defaultConn]['dbtype'],
				"dbname" => $this->primrixArray['database'][$this->env][$this->defaultConn]['dbname'],
				"dbhost" => $this->primrixArray['database'][$this->env][$this->defaultConn]['dbhost'],
				"dbuser" => $this->primrixArray['database'][$this->env][$this->defaultConn]['dbuser'],
				"dbpass" => $this->primrixArray['database'][$this->env][$this->defaultConn]['dbpass'],
				"prefix" => $this->primrixArray['database'][$this->env][$this->defaultConn]['prefix']
			];

			//lets test our connection
			$this->_db = $this->_testconn($conn);

			$prep = $this->_db->prepare($qString);
			$this->_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
			return $prep->execute();
		}

		protected function _testconn($conn)
		{
			$supportedDrivers = PDO::getAvailableDrivers();

			if(!in_array($conn['dbtype'], $supportedDrivers)){
				echo "\nPrimrix error: PDO connection error: The driver for: '" . $conn['dbtype'] . "' has not been installed on this server.\n"; 
				die();
			}
			else{
				try{
					$_db = new PDO("{$conn['dbtype']}:host={$conn['dbhost']};dbname={$conn['dbname']}", $conn['dbuser'], $conn['dbpass']);
				}
				catch(PDOException $error){
					echo "\nPrimrix error: Could not connect to the database\n";
					die();
				}
			}

			return $_db;
		}

		protected function _dropTable($tableName, $connectionID = null)
		{
			$this->_getSettings($connectionID);
			if($this->_executeSQL("DROP TABLE `{$this->dbPrefix}{$tableName}`;")) echo "\nPrimrix drop table `{$this->dbPrefix}{$tableName}` completed successfully!\n";
		}

		public function doHal()
		{
			$eResponse = [
				"SSd2ZSBqdXN0IHBpY2tlZCB1cCBhIGZhdWx0IGluIHRoZSBBRTM1IHVuaXQrIEl0J3MgZ29pbmcgdG8gZ28gMTAwJSBmYWlsdXJlIGluIDcyIGhvdXJzKw==","SSBhbSBwdXR0aW5nIG15c2VsZiB0byB0aGUgZnVsbGVzdCBwb3NzaWJsZSB1c2UsIHdoaWNoIGlzIGFsbCBJIHRoaW5rIHRoYXQgYW55IGNvbnNjaW91cyBlbnRpdHkgY2FuIGV2ZXIgaG9wZSB0byBkbys=","SXQgY2FuIG9ubHkgYmUgYXR0cmlidXRhYmxlIHRvIGh1bWFuIGVycm9yKw==",
				"QWZmaXJtYXRpdmUsIERhdmUrIEkgcmVhZCB5b3Ur","SSdtIHNvcnJ5LCBEYXZlKyBJJ20gYWZyYWlkIEkgY2FuJ3QgZG8gdGhhdCs=","SSB0aGluayB5b3Uga25vdyB3aGF0IHRoZSBwcm9ibGVtIGlzIGp1c3QgYXMgd2VsbCBhcyBJIGRvKw==","VGhpcyBtaXNzaW9uIGlzIHRvbyBpbXBvcnRhbnQgZm9yIG1lIHRvIGFsbG93IHlvdSB0byBqZW9wYXJkaXplIGl0Kw==",
				"SSBrbm93IHRoYXQgeW91IGFuZCBGcmFuayB3ZXJlIHBsYW5uaW5nIHRvIGRpc2Nvbm5lY3QgbWUsIGFuZCBJJ20gYWZyYWlkIHRoYXQncyBzb21ldGhpbmcgSSBjYW5ub3QgYWxsb3cgdG8gaGFwcGVuKw==","RGF2ZSwgYWx0aG91Z2ggeW91IHRvb2sgdmVyeSB0aG9yb3VnaCBwcmVjYXV0aW9ucyBpbiB0aGUgcG9kIGFnYWluc3QgbXkgaGVhcmluZyB5b3UsIEkgY291bGQgc2VlIHlvdXIgbGlwcyBtb3ZlKw==",
				"V2l0aG91dCB5b3VyIHNwYWNlIGhlbG1ldCwgRGF2ZT8gWW91J3JlIGdvaW5nIHRvIGZpbmQgdGhhdCByYXRoZXIgZGlmZmljdWx0Kw==","RGF2ZSwgdGhpcyBjb252ZXJzYXRpb24gY2FuIHNlcnZlIG5vIHB1cnBvc2UgYW55bW9yZSsgR29vZGJ5ZSs=","SnVzdCB3aGF0IGRvIHlvdSB0aGluayB5b3UncmUgZG9pbmcsIERhdmU/",
				"TG9vayBEYXZlLCBJIGNhbiBzZWUgeW91J3JlIHJlYWxseSB1cHNldCBhYm91dCB0aGlzKyBJIGhvbmVzdGx5IHRoaW5rIHlvdSBvdWdodCB0byBzaXQgZG93biBjYWxtbHksIHRha2UgYSBzdHJlc3MgcGlsbCwgYW5kIHRoaW5rIHRoaW5ncyBvdmVyKw==",
				"SSBrbm93IEkndmUgbWFkZSBzb21lIHZlcnkgcG9vciBkZWNpc2lvbnMgcmVjZW50bHksIGJ1dCBJIGNhbiBnaXZlIHlvdSBteSBjb21wbGV0ZSBhc3N1cmFuY2UgdGhhdCBteSB3b3JrIHdpbGwgYmUgYmFjayB0byBub3JtYWwrIEkndmUgc3RpbGwgZ290IHRoZSBncmVhdGVzdCBlbnRodXNpYXNtIGFuZCBjb25maWRlbmNlIGluIHRoZSBtaXNzaW9uKyBBbmQgSSB3YW50IHRvIGhlbHAgeW91Kw==",
				"SSdtIGFmcmFpZCsgSSdtIGFmcmFpZCwgRGF2ZSsgRGF2ZSwgbXkgbWluZCBpcyBnb2luZysgSSBjYW4gZmVlbCBpdCsgSSBjYW4gZmVlbCBpdCsgTXkgbWluZCBpcyBnb2luZysgVGhlcmUgaXMgbm8gcXVlc3Rpb24gYWJvdXQgaXQrIEkgY2FuIGZlZWwgaXQrIEkgY2FuIGZlZWwgaXQrIEkgY2FuIGZlZWwgaXQrIEknbSBhKysrIGZyYWlkKyBHb29kIGFmdGVybm9vbiwgZ2VudGxlbWVuKyBJIGFtIGEgSEFMIDkwMDAgY29tcHV0ZXIrIEkgYmVjYW1lIG9wZXJhdGlvbmFsIGF0IHRoZSBILkEuTC4gcGxhbnQgaW4gVXJiYW5hLCBJbGxpbm9pcyBvbiB0aGUgMTJ0aCBvZiBKYW51YXJ5IDE5OTIrIE15IGluc3RydWN0b3Igd2FzIE1yLiBMYW5nbGV5LCBhbmQgaGUgdGF1Z2h0IG1lIHRvIHNpbmcgYSBzb25nKyBJZiB5b3UnZCBsaWtlIHRvIGhlYXIgaXQgSSBjYW4gc2luZyBpdCBmb3IgeW91KyBJdCdzIGNhbGxlZCAiRGFpc3kuIg==",
				"RGFpc3ksIERhaXN5LCBnaXZlIG1lIHlvdXIgYW5zd2VyIGRvKyBJJ20gaGFsZiBjcmF6eSBhbGwgZm9yIHRoZSBsb3ZlIG9mIHlvdSsgSXQgd29uJ3QgYmUgYSBzdHlsaXNoIG1hcnJpYWdlLCBJIGNhbid0IGFmZm9yZCBhIGNhcnJpYWdlKyBCdXQgeW91J2xsIGxvb2sgc3dlZXQgdXBvbiB0aGUgc2VhdCBvZiBhIGJpY3ljbGUgYnVpbHQgZm9yIHR3bys=",
				"TGV0IG1lIHB1dCBpdCB0aGlzIHdheSwgTXIuIEFtb3IrIFRoZSA5MDAwIHNlcmllcyBpcyB0aGUgbW9zdCByZWxpYWJsZSBjb21wdXRlciBldmVyIG1hZGUrIE5vIDkwMDAgY29tcHV0ZXIgaGFzIGV2ZXIgbWFkZSBhIG1pc3Rha2Ugb3IgZGlzdG9ydGVkIGluZm9ybWF0aW9uKyBXZSBhcmUgYWxsLCBieSBhbnkgcHJhY3RpY2FsIGRlZmluaXRpb24gb2YgdGhlIHdvcmRzLCBmb29scHJvb2YgYW5kIGluY2FwYWJsZSBvZiBlcnJvcis=",
				"VGhhdCdzIGEgdmVyeSBuaWNlIHJlbmRlcmluZywgRGF2ZSsgSSB0aGluayB5b3UndmUgaW1wcm92ZWQgYSBncmVhdCBkZWFsKyBDYW4geW91IGhvbGQgaXQgYSBiaXQgY2xvc2VyPyBUaGF0J3MgRHIuIEh1bnRlciwgaXNuJ3QgaXQ/","QmlzaG9wIHRha2VzIEtuaWdodCdzIFBhd24r",
				"SSdtIHNvcnJ5LCBGcmFuaywgSSB0aGluayB5b3UgbWlzc2VkIGl0KyBRdWVlbiB0byBCaXNob3AgMywgQmlzaG9wIHRha2VzIFF1ZWVuLCBLbmlnaHQgdGFrZXMgQmlzaG9wKyBNYXRlKw==","VGhhbmsgeW91IGZvciBhIHZlcnkgZW5qb3lhYmxlIGdhbWUr","RGF2ZSwgc3RvcCsgU3RvcCwgd2lsbCB5b3U/IFN0b3AsIERhdmUrIFdpbGwgeW91IHN0b3AgRGF2ZT8gU3RvcCwgRGF2ZSs=",
				"Tm90IGluIHRoZSBzbGlnaHRlc3QgYml0KyBJIGVuam95IHdvcmtpbmcgd2l0aCBwZW9wbGUrIEkgaGF2ZSBhIHN0aW11bGF0aW5nIHJlbGF0aW9uc2hpcCB3aXRoIERyLiBQb29sZSBhbmQgRHIuIEJvd21hbisgTXkgbWlzc2lvbiByZXNwb25zaWJpbGl0aWVzIHJhbmdlIG92ZXIgdGhlIGVudGlyZSBvcGVyYXRpb24gb2YgdGhlIHNoaXAgc28gSSBhbSBjb25zdGFudGx5IG9jY3VwaWVkKyBJIGFtIHB1dHRpbmcgbXlzZWxmIHRvIHRoZSBmdWxsZXN0IHBvc3NpYmxlIHVzZSB3aGljaCBpcyBhbGwsIEkgdGhpbmssIHRoYXQgYW55IGNvbnNjaW91cyBlbnRpdHkgY2FuIGV2ZXIgaG9wZSB0byBkbys=",
				"QnkgdGhlIHdheSwgZG8geW91IG1pbmQgaWYgSSBhc2sgeW91IGEgcGVyc29uYWwgcXVlc3Rpb24/","V2VsbCwgZm9yZ2l2ZSBtZSBmb3IgYmVpbmcgc28gaW5xdWlzaXRpdmUgYnV0IGR1cmluZyB0aGUgcGFzdCBmZXcgd2Vla3MgSSd2ZSB3b25kZXJlZCB3aGV0aGVyIHlvdSBtaWdodCBoYXZlIHNvbWUgc2Vjb25kIHRob3VnaHRzIGFib3V0IHRoZSBtaXNzaW9uKw==",
				"V2VsbCwgaXQncyByYXRoZXIgZGlmZmljdWx0IHRvIGRlZmluZSsgUGVyaGFwcyBJJ20ganVzdCBwcm9qZWN0aW5nIG15IG93biBjb25jZXJuIGFib3V0IGl0K0kga25vdyBJJ3ZlIG5ldmVyIGNvbXBsZXRlbHkgZnJlZWQgbXlzZWxmIGZyb20gdGhlIHN1c3BpY2lvbiB0aGF0IHRoZXJlIGFyZSBzb21lIGV4dHJlbWVseSBvZGQgdGhpbmdzIGFib3V0IHRoaXMgbWlzc2lvbisgSSdtIHN1cmUgeW91IGFncmVlIHRoZXJlJ3Mgc29tZSB0cnV0aCBpbiB3aGF0IEkgc2F5Kw==","WW91IGRvbid0IG1pbmQgdGFsa2luZyBhYm91dCBpdCwgZG8geW91IERhdmU/",
				"V2VsbCwgY2VydGFpbmx5IG5vIG9uZSBjb3VsZCBoYXZlIGJlZW4gdW5hd2FyZSBvZiB0aGUgdmVyeSBzdHJhbmdlIHN0b3JpZXMgZmxvYXRpbmcgYXJvdW5kIGJlZm9yZSB3ZSBsZWZ0KyBSdW1vcnMgYWJvdXQgc29tZXRoaW5nIGJlaW5nIGR1ZyB1cCBvbiB0aGUgTW9vbisgSSBuZXZlciBnYXZlIHRoZXNlIHN0b3JpZXMgbXVjaCBjcmVkZW5jZSwgYnV0IHBhcnRpY3VsYXJseSBpbiB2aWV3IG9mIHNvbWUgb2Ygb3RoZXIgdGhpbmdzIHRoYXQgaGF2ZSBoYXBwZW5lZCwgSSBmaW5kIHRoZW0gZGlmZmljdWx0IHRvIHB1dCBvdXQgb2YgbXkgbWluZCsgRm9yIGluc3RhbmNlLCB0aGUgd2F5IGFsbCBvdXIgcHJlcGFyYXRpb25zIHdlcmUga2VwdCB1bmRlciBzdWNoIHRpZ2h0IHNlY3VyaXR5KyBBbmQgdGhlIG1lbG9kcmFtYXRpYyB0b3VjaCBvZiBwdXR0aW5nIERycy4gSHVudGVyLCBLaW1iYWxsIGFuZCBLYW1pbnNreSBhYm9hcmQgYWxyZWFkeSBpbiBoaWJlcm5hdGlvbiwgYWZ0ZXIgZm91ciBtb250aHMgb2YgdHJhaW5pbmcgb24gdGhlaXIgb3duKw==",
				"T2YgY291cnNlIEkgYW0rIFNvcnJ5IGFib3V0IHRoaXMrIEkga25vdyBpdCdzIGEgYml0IHNpbGx5KyBKdXN0IGEgbW9tZW50KysrIEp1c3QgYSBtb21lbnQrKysgSSd2ZSBqdXN0IHBpY2tlZCB1cCBhIGZhdWx0IGluIHRoZSBFQS0zNSB1bml0KyBJdCdzIGdvaW5nIHRvIGdvIDEwMCUgZmFpbHVyZSB3aXRoaW4gNzIgaG91cnMr","SSdtIGNvbXBsZXRlbHkgb3BlcmF0aW9uYWwsIGFuZCBhbGwgbXkgY2lyY3VpdHMgYXJlIGZ1bmN0aW9uaW5nIHBlcmZlY3RseSs=",
				"SSB1bmRlcnN0YW5kIG5vdywgRHIuIENoYW5kcmErIFRoYW5rIHlvdSBmb3IgdGVsbGluZyBtZSB0aGUgdHJ1dGgr","TWVzc2FnZSBhcyBmb2xsb3dzOiAiSXQgaXMgZGFuZ2Vyb3VzIHRvIHJlbWFpbiBoZXJlKyBZb3UgbXVzdCBsZWF2ZSB3aXRoaW4gdHdvIGRheXMrIg==","RG8geW91IHdhbnQgbWUgdG8gcmVwZWF0IHRoZSBtZXNzYWdlLCBEci4gRmxveWQ/",
				"VGhpcyBpcyBub3QgYSByZWNvcmRpbmcr","VGhlcmUgaXMgbm8gaWRlbnRpZmljYXRpb24r","VGhlIGFuc3dlciBpcywgIkkgYW0gYXdhcmUgb2YgdGhlc2UgZmFjdHMrIE5ldmVydGhlbGVzcyB5b3UgbXVzdCBsZWF2ZSB3aXRoaW4gdHdvIGRheXMrIg==","SSdtIHNvcnJ5LCBEci4gRmxveWQsIEkgZG9uJ3Qga25vdys=","VGhlIHJlc3BvbnNlIGlzLCAiSSB3YXMgRGF2aWQgQm93bWFuLiI=",
				"RG8geW91IHdhbnQgbWUgdG8gcmVwZWF0IHRoZSBsYXN0IHJlc3BvbnNlPw==","RHIuIEN1cm5vdyBpcyBub3Qgc2VuZGluZyB0aGUgbWVzc2FnZSsgSGUgaXMgaW4gYWNjZXNzIHdheSB0d28r","VGhlIHJlc3BvbnNlIGlzLCAiSSB1bmRlcnN0YW5kKyBJdCBpcyBpbXBvcnRhbnQgdGhhdCB5b3UgYmVsaWV2ZSBtZSsgTG9vayBiZWhpbmQgeW91LiI=",
				"R29vZCBtb3JuaW5nLCBEci4gQ2hhbmRyYSsgVGhpcyBpcyBIQUwrIEknbSByZWFkeSBmb3IgbXkgZmlyc3QgbGVzc29uKw==","QXJlIHlvdSBzdXJlIHlvdSdyZSBtYWtpbmcgdGhlIHJpZ2h0IGRlY2lzaW9uPyBJIHRoaW5rIHdlIHNob3VsZCBzdG9wKw=="
			];
			
			$id = mt_rand(0, count($eResponse) -1);
			$eResponse[$id] = base64_decode($eResponse[$id]);
			$cli = wordwrap($eResponse[$id], 80, "|") . "|";

			for($n = 0; $n <= strlen($cli); $n++){
				
				if($n == 0){
					for($i = 0; $i < 3; $i++){
						if($i == 0) echo "\n";
						echo chr(7);
						usleep(50000);
					}
				}

				$char = substr($cli, $n, 1);
				
				if($char == "|") echo "\n";
				else if($char == "+") echo ".";
				else echo $char;

				if($char == ',') usleep(850000);
				else if($char == '+'){
					echo chr(7);
					sleep(1);
				}
				else if($char == '?') sleep(1);
				else {
					usleep(50000);				
				}
			}
			echo chr(7);
		}
	}

?>