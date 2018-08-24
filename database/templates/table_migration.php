<?php

/**
 * Migration Class
 *
 * @package      Primrix 4.0
 * @version      1.0
 */
	
	//activate this script by running:
	//primrix -table {tableName} build
	//or primrix -table {tableName} revert
	//or primrix -table {tableName} [~custom method]
	
	class {ClassName} extends Primrix
	{
		public function build()
		{
			$table = new stdClass();

			//lets set some general details about our table
			$table->name =		'{tableName}';
			$table->engine = 	'innodb';
			$table->charset = 	'utf8';
			$table->collate = 	'utf8_unicode_ci';
			$table->primary = 	'id';


			$columns = new stdClass();

			//lets set some general fields
			$columns->id['type'] = 'int';
			$columns->id['lenval'] = 6;
			$columns->id['not_null'] = true;

			//add your fields here

			$columns->created_at['type'] = 'datetime';
			$columns->created_at['not_null'] = true;

			$columns->updated_at['type'] = 'datetime';
			$columns->updated_at['not_null'] = true;

			$columns->updated_by['type'] = 'int';
			$columns->updated_by['lenval'] = 6;
			$columns->updated_by['not_null'] = true;
 	
			$columns->active['type'] = 'enum';
			$columns->active['lenval'] = "'y','n'";
			$columns->active['default'] = 'y';
			$columns->active['not_null'] = true;

			//lets pass the details on to our statics
			parent::$table = $table;
			parent::$columns = $columns;

			//lets create a table
			$this->_createTable();
		}

		//this should be the opposite of the above build action
		public function revert()
		{
			//lets drop the above table
			$this->_dropTable('{tableName}');
		}
	}
?>