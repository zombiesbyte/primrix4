<?php

/**
 * Seed Class
 *
 * @package      Primrix 4.0
 * @version      1.0
 */

	//activate this script by running:
	//primrix -seed {ClassName} <tableName>
	
	class {ClassName} extends Primrix
	{
		public function seed($table)
		{
			$dataArray = array();

			//here's an example of two row entries
			$dataArray[] = [
				'id' => '1',
				'created_at' => 'NOW()',
				'updated_at' => 'NOW()',
				'updated_by' => '1',
				'active' => 'y'
			];

			$dataArray[] = [
				'id' => '2',
				'created_at' => 'NOW()',
				'updated_at' => 'NOW()',
				'updated_by' => '1',
				'active' => 'y'
			];

			//...

			//we then call the seed table method
			$this->_seedTable($table, $dataArray);			
		}
	}
?>