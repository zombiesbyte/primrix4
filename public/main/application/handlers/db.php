<?php namespace main\application\handlers;

/**
 * DB
 *
 * The DB handler is used to connect and address the database.
 *
 * @author       James Dalgarno <james@imagewebdesign.co.uk>
 * @copyright    James Dalgarno 2014
 * @license      license.txt The MIT License (MIT)
 * @package      Primrix 4.0
 * @version      1.0
 */

	use \site\config\Def;
	use \PDO;
	use \PDOException;

	class DB
	{
		protected $_dbName;
		protected $_db;
		protected $_prefix;
		public $prefix;
		protected $_select = '*';
		protected $_where;
		protected $_orderBy;
		protected $_limit;
		protected $_sql;
		protected $_query;
		protected $_sqlCount;		
		protected $_queryCount;
		protected $_errorCode;
		protected $_errorInfo;

		protected $_flagPagination = false;
		protected $_paginationLimit;
		protected $_paginationNumRows;

		/**
		 * Lets define our connection either by the default or by passing
		 * in a named connection from our /site/config/settings.php JSON 
		 * @param string $connName Connection name as defined within
		 * /site/config/settings.php JSON file or use the default 'connection1'
		 * 
		 */
		public function __construct($connName = 'connection1') //'connection1'
		{
			
			$environment = Def::$primrix->environment;
			$conn = [
				"dbtype" => Def::$primrix->database->$environment->$connName->dbtype,
				"dbname" => Def::$primrix->database->$environment->$connName->dbname,
				"dbhost" => Def::$primrix->database->$environment->$connName->dbhost,
				"dbuser" => Def::$primrix->database->$environment->$connName->dbuser,
				"dbpass" => Def::$primrix->database->$environment->$connName->dbpass,
				"prefix" => Def::$primrix->database->$environment->$connName->prefix
			];

			$this->_db = $this->_testconn($conn);
			$this->_prefix = $conn['prefix'];
			$this->prefix = $this->_prefix; //this is used as a getter only
			$this->_dbName = $conn['dbname'];
		}


		/**
		 * Lets test the connection string
		 * 
		 * @param  array $conn as gathered from our constructor
		 * @return object $_db PDO connection object
		 */
		protected function _testconn($conn)
		{
			
			$supportedDrivers = PDO::getAvailableDrivers();

			if(!in_array($conn['dbtype'], $supportedDrivers)){
				echo "PDO connection error: The driver for: '" . $conn['dbtype'] . "' has not been installed on this server."; //shove this into a JSON doc
				die();
			}
			else{
				try{
					$_db = new PDO("{$conn['dbtype']}:host={$conn['dbhost']};dbname={$conn['dbname']}", $conn['dbuser'], $conn['dbpass']);
				}
				catch(PDOException $error){
					echo "Error!: " . $error->getMessage() . "<br/>";
					die();
				}
			}

			return $_db;
		}


		/**
		 * Used for debug purposes, this will explain the SQL
		 * @return null (ATM)
		 */
		public function echoQuery($showAll = true)
		{
			if($showAll){
				echo "select property: " . $this->_select . "<br>\n";
				echo "where property: " . $this->_where . "<br>\n";
				echo "limit property: " . $this->_limit . "<br>\n";
				echo "order property: " . $this->_orderBy . "<br>\n";
			}
			echo "complete SQL:<br>\n" . $this->_sql . "<br>\n";
			if($this->_errorCode != "" and $this->_errorCode != "00000"){
				echo "Errors:<br>\n";
				foreach($this->_errorInfo as $error){
					echo "::" . $error . "<br>\n";
				}
			}
		}


		public function select($select){
			if(is_array($select)){
				$selectTemp = '';
				foreach($select as $s){
					$selectTemp .= '`' . $s . '`,';
				}
				$this->_select = Text::groom('`,', $selectTemp);
			}
			else $this->_select = $select;
		}

		/**
		 * Build a where clause. This is recursive 
		 * @param  string $whereClause pass a string
		 * @return object
		 */
		public function where($whereClause = null)
		{
			if($whereClause != null and $whereClause != 'false'){
				if($this->_where != '') $this->_where .= ' ' . $whereClause;
				else $this->_where = ' WHERE ' . $whereClause;
			}
			else if($whereClause == 'false'){
				//this is used as a bypass on some methods
				$this->_where = 'false';
			}
			return $this;
		}


		/**
		 * Orderby string.
		 * @param  string $orderBy
		 * @return object
		 */
		public function orderBy($orderBy)
		{
			if($orderBy != ''){
				$this->_orderBy = ' ORDER BY ' . $orderBy;
			}
			return $this;
		}

		/**
		 * 
		 */
		public function limit($limit)
		{
			if($limit != ''){
				$this->_limit = ' LIMIT ' . $limit;
			}
			return $this;
		}

		/**
		 * Shortcut for declaring a page limit. Basically performs a
		 * quick URI check and calculation based on the supplied limit
		 * @param integer $limit how many results to show per page
		 * @param boolean $overRule if the uri can be used to over rule the limit
		 * @return object
		 */
		public function pageLimit($limit = 10, $overRule = false)
		{
			$this->_paginationLimit = $limit;
			if($overRule and URI::$limit > 0) $this->_paginationLimit = URI::$limit;
			$this->_flagPagination = true;
			self::limit(((URI::$page -1) * $this->_paginationLimit) . ", $limit");
			return $this;
		}

		/**
		 * Lets build a query string based on the SQL gathered from our other
		 * functions above. 
		 * @param  string $queryTable select a table name
		 * @return object
		 */
		public function query($queryTable)
		{
			//lets prepare our query string
			$this->_sql = "SELECT {$this->_select} FROM `{$this->_prefix}{$queryTable}`" . $this->_where . $this->_orderBy . $this->_limit;

			//build an additional query string for our count ready for numRows() method
			$this->_sqlCount = "SELECT COUNT(*) FROM `{$this->_prefix}{$queryTable}`" . $this->_where;

			//let the server prepare, execute and check for errors
			$this->_query = $this->_prepare($this->_sql);
			$this->_execute($this->_query);
			$this->_errors($this->_query);

			return $this;
		}		

		/**
		 * http://www.w3schools.com/sql/sql_join.asp
		 * INNER JOIN: Returns all rows when there is at least one match in BOTH tables
		 * LEFT JOIN: Return all rows from the left table, and the matched rows from the right table
		 * RIGHT JOIN: Return all rows from the right table, and the matched rows from the left table
		 * FULL JOIN: Return all rows when there is a match in ONE of the tables
		 * PS! INNER JOIN is the same as JOIN.
		 *
		 * @return [type] [description]
		 */
		public function join($tables, $columnName, $joinType = 'JOIN')
		{
			
			$totalTables = count($tables);
			
			if(is_array($columnName)){
				$colsIsArray = true;
				if(count($columnName) != $totalTables){
					echo "DB Join error. columnName passed as an array but does not match tables count. ";
					echo "columnName is an array of: " . count($colsIsArray) . " while tables is an array of: " . $totalTables;
					die();
				}
			}
			else $colsIsArray = false;
			$c = 0;

//SELECT column_list
//FROM t1
//INNER JOIN t2 ON join_condition1
//INNER JOIN t3 ON join_condition2

			$joinSQL = "`{$this->_prefix}{$tables[0]}` ";

			for($n = 0; $n < $totalTables; $n += 1){

				if($n+1 < $totalTables){
					$joinSQL .= "{$joinType} `{$this->_prefix}{$tables[$n+1]}` ";
					if($colsIsArray) $joinSQL .= "ON `{$this->_prefix}{$tables[$n]}`.`{$columnName[$c]}` = `{$this->_prefix}{$tables[$n+1]}`.`{$columnName[$c+1]}` ";
					else $joinSQL .= "ON `{$this->_prefix}{$tables[$n]}`.`{$columnName}` = `{$this->_prefix}{$tables[$n+1]}`.`{$columnName}` ";
					$c += 1;
				}
			}

			//lets prepare our query string
			$this->_sql = "SELECT {$this->_select} FROM " . $joinSQL . $this->_where . $this->_orderBy . $this->_limit;

			//build an additional query string for our count ready for numRows() method
			$this->_sqlCount = "SELECT COUNT(*) FROM " . $joinSQL . $this->_where;

			//let the server prepare, execute and check for errors
			$this->_query = $this->_prepare($this->_sql);
			$this->_execute($this->_query);
			$this->_errors($this->_query);

			return $this;
		}

		/**
		 * Insert a record within a table. The dataArray should match the keys of our table
		 * column names for good practice.
		 * @param string $table table name
		 * @param array $dataArray array of associated data with matching table column names
		 * @return bool status of insert (success/fail)
		 */
		public function insert($table, $dataArray)
		{
			$sqlValues = "";
			$tableCols = $this->getColumnNames($table);

			//loop through our table column names array and match them against our data array
			//the additional handling of data which is an array is imploded to a csv which is
			//quite useful in some scenarios and it provides a good fall back option
			foreach($tableCols as $colName){
				if(isset($dataArray[$colName])){
					if(is_array($dataArray[$colName])) $dataArray[$colName] = implode("\n", $dataArray[$colName]);
					$sqlValues .= "'" . $dataArray[$colName] . "',";
				}
				else $sqlValues .= "'',";
			}

			//lets build our statement
			$sqlValues = Text::groom(',', $sqlValues); //remove last comma
			$sqlInsert = "INSERT INTO `{$this->_prefix}{$table}` VALUES({$sqlValues})";
			
			//let the server prepare, execute and check for errors
			$this->_query = $this->_prepare($sqlInsert);
			$eStatus = $this->_execute($this->_query);
			$this->_errors($this->_query);			

			return $eStatus;
		}


		/**
		 * Update a record within a database
		 * @param string $table
		 * @param array $dataArray associative array relating to the db column names
		 * @param string/array $protectedFields a csv or a single dimensional array of
		 * fields that should not be effected
		 * @param string/array $conCat a csv or a single dimensional array of fields
		 * that require their existing values appended to instead of being replaced
		 * @return bool status of update (success/fail)
		 */
		public function update($table, $dataArray, $protectFields = null, $conCat = null)
		{
			$sqlValues = "";			
			$tableCols = $this->getColumnNames($table);

			$dataArray = Bunch::asArray($dataArray, "\n", true);

			if($protectFields != null) $protectFields = Bunch::asArray($protectFields);
			if($conCat != null) $conCat = Bunch::asArray($conCat);

			foreach($dataArray as $field => $value)
			{
				if($protectFields == null or !in_array($field, $protectFields)){
					if(in_array($field, $tableCols)){
						if(is_array($value)) $value = implode(',', $value);
						if($conCat != null and in_array($field, $conCat)){
							$sqlValues .= "`{$field}` = concat({$field}, '{$value}'),";
						}
						else $sqlValues .= "`{$field}` = '{$value}',";
					}
				}
			}

			$sqlValues = Text::groom(',', $sqlValues); //remove last comma

			if($this->_where != "" or $this->_where == 'false'){
				$sqlUpdate = "UPDATE `{$this->_prefix}{$table}` SET {$sqlValues}";
				if($this->_where != 'false') $sqlUpdate .= $this->_where;
			}
			else echo "Warning, DB Update has no where clause, this will overwrite all rows, please make sure that the where clause has been addressed, to avoid this error, please us where('false')";

			$this->_query = $this->_prepare($sqlUpdate);
			$eStatus = $this->_execute($this->_query);
			$this->_errors($this->_query);
			$this->close();

			return $eStatus;
		}


		/**
		 * Delete a row based on a where clause
		 * @param string $table
		 * @return bool status of delete (success/fail)
		 */
		public function deleteRow($table)
		{

			if($this->_where != "" and $this->_where != 'false'){
				$sqlDelete = "DELETE FROM `{$this->_prefix}{$table}`" . $this->_where;				
			}
			else echo "Error: Please provide a where clause when trying to delete a row";

			$this->_query = $this->_prepare($sqlDelete);
			$eStatus = $this->_execute($this->_query);
			$this->_errors($this->_query);
			$this->close();

			return $eStatus;
		}

		/**
		 * Delete multiple rows by a supplied array of ids.
		 * @param string $table
		 * @param string $field
		 * @param array $deleteIDs
		 */
		public function deleteRowByArray($table, $field, $deleteIDs)
		{
			foreach($deleteIDs as $id){
				$this->where("`{$field}` = '{$id}'");
				$this->deleteRow($table);
			}
			return true;
		}


		/**
		 * DBO prepare statement
		 * @param string $sql sql statement to be prepared
		 * @return object $prep prepared object
		 */
		private function _prepare($sql)
		{
			$prep = $this->_db->prepare($sql);
			$this->_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
			return $prep;
		}


		/**
		 * DBO execute statement
		 * @param object $obj object passed to be executed
		 * @return bool status of execution (success/fail)
		 */
		private function _execute($obj)
		{
			return $obj->execute();
		}


		/**
		 * Error collection
		 * @param object $obj the query object which is tested for errors
		 * @return null
		 */
		private function _errors($obj)
		{
			//lets retrieve any SQL errors
			$this->_errorCode = $obj->errorCode();
			$this->_errorInfo = $obj->errorInfo();
		}


		/**
		 * By using our secondary query _sqlCount we use the below
		 * method of a means to count the possible results
		 *
		 * Sometimes this is far as we need to take a query so the option
		 * to pass a close argument can be added. This will close the DB
		 * connection and return the count results.
		 * 
		 * @param  boolean $closeDB 
		 * @return int number of rows found
		 */
		public function numRows($closeDB = false)
		{   
			$this->_queryCount = $this->_prepare($this->_sqlCount);
			$this->_execute($this->_queryCount);
			$this->_errors($this->_queryCount);
			$count = $this->_queryCount->fetchColumn();
			//lets close our connection for our count query
			$this->_queryCount->closeCursor();
			$this->_reset();

			if($this->_flagPagination){
				$this->_paginationNumRows = $count;
				$this->_flagPagination = false;
			}

			if($closeDB) $this->close();

			return $count;
		}

		
		/**
		 * Lets fetch our results. As an optional parameter we can fetch
		 * as an associative array, numbered array, object etc...
		 * (please note: we are unable to fetch as a class using this method)
		 * @param  string $fetchAs FETCH_ASSOC | FETCH_NUM | FETCH_OBJ | etc
		 * @return array | obj depending upon the above setting
		 */
		public function fetch($fetchAs = "FETCH_ASSOC")
		{
			return $this->_query->fetch(constant("PDO::{$fetchAs}"));
		}


		/**
		 * Returns the next index on a field. This can be used instead of
		 * relying on auto_increment within the database or can sometimes
		 * be useful for other tasks relating to incremental entries
		 * @param  string $table the table name
		 * @param  string $indexField the field name we want to address
		 * @return integer integer value of next index
		 */
		public function nextIndex($table, $indexField)
		{
			$this->orderby("`{$indexField}` DESC");
			$this->query($table);
			$row = $this->fetch();
			$this->close();
			return($row[$indexField] + 1);
		}

		/**
		 * Returns the next index on a field within a group. See nextIndex
		 * for further notes regarding this method
		 * @param  string $table the table name
		 * @param  string $indexField the index field name we want to address
		 * @param  string $groupField the group field name we want to address
		 * @param  string $groupID the group set to look within
		 * @param  string $grp2Field (optional 2nd level group)
		 * @param  string $grp2Value (optional 2nd level value)
		 * @return integer integer value of next index
		 */
		public function nextIndexGrp($table, $indexField, $groupField, $groupID, $grp2Field = null, $grp2Value = null)
		{
			if($grp2Field == null) $this->where("`{$groupField}` = '{$groupID}'");
			else $this->where("`{$groupField}` = '{$groupID}' AND `{$grp2Field}` = '{$grp2Value}'");
			$this->orderby("`{$indexField}` DESC");
			$this->query($table);
			$row = $this->fetch();
			$this->close();
			return($row[$indexField] + 1);
		}

		/**
		 * Returns the next index based on a pre-established where clause. This can
		 * be used instead of relying on auto_increment within the database or can
		 * sometimes be useful for other tasks relating to incremental entries.
		 * @param  string $table the database table name
		 * @param  string $indexField the field name of the index to use
		 * @return int returns the integer of the next index
		 */
		public function nextIndexLookup($table, $indexField)
		{
			if($this->_where != ""){
				$this->orderby("`{$indexField}` DESC");
				$this->query($table);
				$row = $this->fetch();
				$this->close();
				return($row[$indexField] + 1);
			}
		}		

		/**
		 * Toggles through an array of values. Commonly used for y and n toggles but can be used
		 * with more than two array values.
		 * @param  string $table the table to target
		 * @param  string $indexField the index field of the row to target
		 * @param  string $index the index of the row
		 * @param  string $toggleField the field name which is toggled
		 * @param  array $valuesArray an array of values i.e. ['y','n'] or ['one','two','three','etc']
		 * @return boolean true or false completion
		 */
		public function toggleValue($table, $indexField, $index, $toggleField, $valuesArray)
		{
			$this->where("`{$indexField}` = '{$index}'");
			$this->query($table);
			if($this->numRows() == 1){
				$row = $this->fetch();

				for($n = 0; $n < count($valuesArray); $n++){
					if($row[$toggleField] == $valuesArray[$n]){
						if(($n + 1) >= count($valuesArray)) $t = 0;
						else $t = $n + 1;
					}
				}
				$this->close();

				$this->where("`{$indexField}` = '{$index}'");
				$this->update($table, [$toggleField => $valuesArray[$t]]);
				$this->close();
				return true;				
			}
			else{
				$this->close();
				return false;
			}

		}

		/**
		 * WARNING: This is using a command which is still not supported by all db types
		 * such as sqlite, oracle and postgres. known supported db types are mysql, mssql
		 * and db2. We would need to tackle these as they come up. This feature was submitted
		 * as a request and acknowledged in 2011 for sqlite... ...someone didn't pick up their
		 * messages I guess as it's still not available in 2014 from what I can gather. I write
		 * this note here in case I don't come back to this section to test/update.
		 *
		 * This method simply returns an array of column names for a particular table
		 * @param $table the table name
		 * @return array an array of column names in db order
		 */
		public function getColumnNames($table)
		{
			$colNames = array();
			$schemaArray = array();

			//let the server prepare, execute and check for errors
			$this->_query = $this->_prepare("SELECT * FROM `{$this->_prefix}{$table}`");
			$this->_execute($this->_query);
			$this->_errors($this->_query);

			$totalCols = $this->_query->columnCount();
			for($n = 0; $n < $totalCols; $n++){
				$columnSchema = $this->_query->getColumnMeta($n);
				$colNames[] = $columnSchema['name'];
			}
			$this->close(false);

			return $colNames;
		}


		/**
		 * Reset our properties ready for the next query
		 */
		protected function _reset()
		{
			$this->_select = '*';
			$this->_where = "";
			$this->_orderBy = "";
			return null;
		}


		/**
		 * Close our database connection which is great housekeeping. Please note
		 * that this method is fixed at closing _query only. i.e. we close things
		 * _queryCount within their own methods. close() was designed to just close
		 * the default _query object.
		 * @return object
		 */
		public function close($reset = true)
		{
			if($this->_query != null) $this->_query->closeCursor();
			if($reset) $this->_reset();
			return $this;
		}


		/**
		 * Returns an array of pagination links. If the params are not specified
		 * then we use the values stored within our properties from the last query.
		 * pageLimit should be used instead of the limit method to force this object
		 * to record the limit results. Please note the page numbers are returned as
		 * keys and the current page is marked by a type array. Iterating through
		 * the $key(page number) => $value (link) pairs the current page can be checked
		 * testing whether $value (link) is_array of which 0 key will contain the link.
		 * 
		 * @example
		 * 
		 * $pageLinks = $this->db->pages();
		 * foreach($pageLinks as $pageNum => $link) {
		 * 		if(!is_array($link)) echo " <a href='{$link}'>{$pageNum}</a> |";
		 *   	else echo " <a href='{$link[0]}'>[{$pageNum}]</a> |";
		 * }
		 * 
		 * @param boolean $numRows number of rows in query
		 * @param boolean $resultsPerPage the limit total per page
		 * @return array
		 */
		public function pages($numRows = false, $resultsPerPage = false)
		{
			if(!$numRows) $numRows = $this->_paginationNumRows;
			if(!$resultsPerPage) $resultsPerPage = $this->_paginationLimit;

			$pageLinks = array();

			$totalPages = ceil($numRows / $resultsPerPage);

			for($n = 1; $n <= $totalPages; $n++){
				if(URI::$page == $n){
					$pageLinks[$n][] = '/' . URI::rebuild(['page' => $n]);
				}
				else $pageLinks[$n] = '/' . URI::rebuild(['page' => $n]);
			}

			return $pageLinks;
		}


		/**
		 * Returns the calculation of total number of pages. If the params are
		 * not specified then we use the values stored within our properties from
		 * the last query. pageLimit should be used instead of the limit method to
		 * force this object to record the limit results.
		 * @param boolean $numRows number of rows in query
		 * @param boolean $resultsPerPage the limit total per page
		 * @return int total pages
		 */
		public function totalPages($numRows = false, $resultsPerPage = false)
		{
			if(!$numRows) $numRows = $this->_paginationNumRows;
			if(!$resultsPerPage) $resultsPerPage = $this->_paginationLimit;

			$totalPages = ceil($numRows / $resultsPerPage);

			return $totalPages;
		}

		public function truncate($table)
		{
			//let the server prepare, execute and check for errors
			$this->_query = $this->_prepare("TRUNCATE TABLE `{$this->_prefix}{$table}`");
			$this->_execute($this->_query);
			$this->_errors($this->_query);
			$this->close();
		}


		/**
		 * Uses the oIndex to gather information about the destination index and loops through both source
		 * and destination ids to update their ordering. "id" column is required in the database as a method
		 * of identifying the rows uniquely. Warning: ordering relies on the integrity of the existing data.
		 * @param string $table database table name
		 * @param string $field the field name in the table where our indexes are stored
		 * @param int $oIndex order index of the record(s) you want to move up or down
		 * @param mixed $direction 'up' or 1, 'down' or -1
		 * @return boolean returns true on success
		 */

		public function reorderField($table, $field, $oIndex, $direction)
		{
			if($this->_where != ""){
				$direction = strtolower($direction);
/*
				
				$where = $this->_where; 
				$where = str_replace('WHERE ', '', $where);

				//We need to find the current index value of our field so we can perform actions on it
				$this->close(); //we need to reset before we can challenge the db hence why we store our where clause
				$this->where("`id` = '{$id}'");
				$this->select("`{$field}`");
				$this->query($table);
				$row = $this->fetch();
				$this->close();

				$currentIndex = $row[$field]; //this is the current index value of the id in the field

				if($direction == -1 or strtolower($direction) == 'up'){
					
					if($currentIndex > 1){

						$newIndex = $currentIndex -1;
						$this->_reorderWriter($where, $table, $field, $newIndex, $currentIndex);
						return true;

					}
					else return false;
				}
				else if($direction == 1 or strtolower($direction) == 'down'){
					$lastIndex = ($this->nextIndex($table, $field) - 1); //gets the last index number within a field
					if($currentIndex < $lastIndex){
						
						$newIndex = $currentIndex + 1;
						$this->_reorderWriter($where, $table, $field, $newIndex, $currentIndex);
						return true;

					}
					else return false;
				}
				*/
				
				//We need to store the where clause temporarily and we also need to remove 'WHERE' otherwise
				//we end up with 'WHERE WHERE' when we run it back through the where clause method
				$where = $this->_where; 
				$where = str_replace('WHERE ', '', $where);

				if($direction == -1 or $direction == 'up'){
					
					//we need to make sure that we can actually move this record up
					if($oIndex > 1){
						$sourceIDs = array();
						$destinationIDs = array();
						$destID = $oIndex - 1;

						$this->select("`id`,`{$field}`");
						$this->where(" AND `{$field}` = '{$oIndex}'");
						$this->query($table);
						while($row = $this->fetch()){
							$sourceIDs[] = $row['id'];
						}
						$this->close();

						$this->select("`id`,`{$field}`");
						$this->where($where . " AND `{$field}` = '{$destID}'");
						$this->query($table);
						while($row = $this->fetch()){
							$destinationIDs[] = $row['id'];
						}
						$this->close();

						if(!empty($sourceIDs) and !empty($destinationIDs)){
							foreach($sourceIDs as $srcID){
								$updateQuery = "UPDATE `{$this->_prefix}{$table}` SET `{$field}` = `{$field}` - 1";
								$this->where("`id` = '{$srcID}'");
								$this->_query = $this->_prepare($updateQuery . $this->_where);
								$eStatus = $this->_execute($this->_query);
								$this->_errors($this->_query);
								$this->close();
							}

							foreach($destinationIDs as $dstID){
								$updateQuery = "UPDATE `{$this->_prefix}{$table}` SET `{$field}` = `{$field}` + 1";
								$this->where("`id` = '{$dstID}'");
								$this->_query = $this->_prepare($updateQuery . $this->_where);
								$eStatus = $this->_execute($this->_query);
								$this->_errors($this->_query);
								$this->close();
							}
						}
						return true;
					}
				}
				else if($direction == 1 or $direction == 'down'){
					$lastIndex = ($this->nextIndex($table, $field) - 1); //gets the last index number within a field
					
					if($oIndex < $lastIndex){
	
						$sourceIDs = array();
						$destinationIDs = array();
						$destID = $oIndex + 1;

						$this->select("`id`,`{$field}`");
						$this->where($where . " AND `{$field}` = '{$oIndex}'");
						$this->query($table);
						while($row = $this->fetch()){
							$sourceIDs[] = $row['id'];
						}
						$this->close();

						$this->select("`id`,`{$field}`");
						$this->where($where . " AND `{$field}` = '{$destID}'");
						$this->query($table);
						while($row = $this->fetch()){
							$destinationIDs[] = $row['id'];
						}
						$this->close();

						if(!empty($sourceIDs) and !empty($destinationIDs)){
							foreach($sourceIDs as $srcID){
								$updateQuery = "UPDATE `{$this->_prefix}{$table}` SET `{$field}` = `{$field}` + 1";
								$this->where("`id` = '{$srcID}'");
								$this->_query = $this->_prepare($updateQuery . $this->_where);
								$eStatus = $this->_execute($this->_query);
								$this->_errors($this->_query);
								$this->close();
							}

							foreach($destinationIDs as $dstID){
								$updateQuery = "UPDATE `{$this->_prefix}{$table}` SET `{$field}` = `{$field}` - 1";
								$this->where("`id` = '{$dstID}'");
								$this->_query = $this->_prepare($updateQuery . $this->_where);
								$eStatus = $this->_execute($this->_query);
								$this->_errors($this->_query);
								$this->close();
							}
						}
						return true;
					}
				}

				return false;
			}
			else{
echo "WHERE clause not specified in DB->reorderField";
				return false;
			}
		}

		/**
		 * Rewrites an incremental column of data. This can be used to rebuild an order list
		 * which may have inconsistent integer ordering such as from a record being removed.
		 * For instance: if a list of 1-10 has an ordering of 1-10 and a record has been removed,
		 * this list will become a total of 9 however the ordering column will then be inconstant
		 * with the number of rows. This method rewrites the list based on the existing ordering
		 * system but overwrites the inconsistent integer ordering. Thanks to Christian Hansel's
		 * comment https://dev.mysql.com/doc/refman/5.0/en/update.html. This was helpful in
		 * creating this method.
		 * @param  string $table database table name
		 * @param  string $field table field name
		 * @param  int $asGrp if orders are handled as multiple groups then a group integer is used
		 * @return boolean returns true on complete
		 */
		public function rebuildOrders($table, $field, $asGrp = false)
		{
			if($this->_where != ""){

				if($asGrp){
					$updateQuery = "UPDATE `{$this->_prefix}{$table}` SET `{$field}` = `{$field}` - 1";
					$this->where("AND `{$field}` > $asGrp");

					$this->_query = $this->_prepare($updateQuery . $this->_where . $this->_orderBy);
					$eStatus = $this->_execute($this->_query);
					$this->_errors($this->_query);
					$this->close();

					return true;
				}
				else{
					$updateQuery = "SET @{$field} = 0;";
					$updateQuery .= "UPDATE `{$this->_prefix}{$table}` SET `{$field}` = (SELECT @{$field} := @{$field} + 1)";
					$this->orderBy("`{$field}` ASC");

					$this->_query = $this->_prepare($updateQuery . $this->_where . $this->_orderBy);
					$eStatus = $this->_execute($this->_query);
					$this->_errors($this->_query);
					$this->close();

					return true;
				}
			}
			else return false;
		}

		/**
		 * Runs a backup of the entire database. Please note that this will not record
		 * structure of the tables. This was a pitfall to using PDO as table column
		 * information is pulled in a generic PDO native_type which fails to accommodate
		 * unique database column types such as ENUM (i.e. ENUM returns as STRING and does
		 * not record possible values list) This solution is a compromise until PDO can fill
		 * the task of being a bit less abstract or even help a translation layer.
		 * @param string $filepath If supplied then the output will be sent to a file
		 * @return string sql statement
		 */
		public function backup($filepath = null)
		{
			//check out the last post on this site for inspiration
			//http://stackoverflow.com/questions/18279066/pdo-mysql-backups-function
			
			//I should simply record data and not table structure information!

			$tablesArray = array();

			//let the server prepare, execute and check for errors
			$this->_query = $this->_prepare("SELECT `TABLE_NAME` FROM `information_schema`.`TABLES` WHERE `TABLE_TYPE` = 'BASE TABLE' AND `TABLE_SCHEMA` = '{$this->_dbName}'");
			$this->_execute($this->_query);
			$this->_errors($this->_query);

			while($table = $this->fetch()){
				if($table['TABLE_NAME'] != "{$this->_prefix}primrix_backup"){
					$tablesArray[] = str_replace($this->_prefix, '', $table['TABLE_NAME']);
				}
			}
			
			$this->close();
			//\Help::pre($tablesArray);

			$sql = "";

			foreach($tablesArray as $table){
				
				$columns = array();
				$columns = $this->getColumnNames($table);

				$insertInto = "INSERT INTO `{$this->_prefix}{$table}`";
				$insertColumns = "";
				$insertValues = "";

				foreach($columns as $col){
					$insertColumns .= "`{$col}`, ";
				}

				$insertColumns = Text::groom(', ', $insertColumns);

				$this->query($table);
				$totalRows = $this->numRows();
				while($row = $this->fetch()){
					$insertValues .= "(";
					foreach($columns as $col){
						$insertValues .= "'" . addcslashes($row[$col], "\n\r") . "', ";
					}
					$insertValues = Text::groom(', ', $insertValues);
					$insertValues .= "),\n";
				}
				
				if($totalRows > 0){
					$insertValues = Text::groom(', ', $insertValues);
					$insertValues .= ";";

					$sql .= $insertInto . "(" . $insertColumns . ")\n";
					$sql .= "VALUES \n";
					$sql .= $insertValues . "\n\n";
				}

			}

			if($filepath == null) return $sql;
			else {
				//check that the file doesn't already exist
				//for some reason and unlink it if it does
				File::delete($filepath);

				//create file
				$fh = fopen($filepath, 'x');

				//add sql to file
				file_put_contents($filepath, $sql);

				//close file
				fclose($fh);
			}
	
		}

		/**
		 * Runs a restore of a database
		 * @param $sql sql statement
		 */
		public function restore($filepath, $sql = null)
		{
			if($sql == null){
				if(is_file($filepath)){
					$sql = file_get_contents($filepath);
				}
				else return false;
			}

			if($sql != ""){

				$tablesArray = array();

				//let the server prepare, execute and check for errors
				$this->_query = $this->_prepare("SELECT `TABLE_NAME` FROM `information_schema`.`TABLES` WHERE `TABLE_TYPE` = 'BASE TABLE' AND `TABLE_SCHEMA` = '{$this->_dbName}'");
				$this->_execute($this->_query);
				$this->_errors($this->_query);

				while($table = $this->fetch()){
					if($table['TABLE_NAME'] != "{$this->_prefix}primrix_backup"){
						$tablesArray[] = str_replace($this->_prefix, '', $table['TABLE_NAME']);
					}
				}
			
				$this->close();

				foreach($tablesArray as $table){
					$this->truncate($table);
				}

				//let the server prepare, execute and check for errors
				$this->_query = $this->_prepare($sql);
				$this->_execute($this->_query);
				$this->_errors($this->_query);
				$this->close();

				return true;
			}
			else return false;
		}
	}

//!EOF : /main/application/handlers/db.php