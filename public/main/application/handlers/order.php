<?php namespace main\application\handlers;

/**
 * Order
 *
 * @author       James Dalgarno <james@imagewebdesign.co.uk>
 * @copyright    James Dalgarno 2014
 * @license      license.txt The MIT License (MIT)
 * @package      Primrix 4.0
 * @version      1.0
 */
	
	use \site\config\Def;

	class Order
	{
		public static function order($table, $id, $orderDirection, $complexGrp = false)
		{
			$db = new DB;

			$db->where("`id` = '{$id}'");
			$db->query($table);
			if($db->numRows() == 1){
				$oRow = $db->fetch();
				$db->close();

				if(isset($oRow['group'])){ //we test to see if this is in a group
					
					if(!$complexGrp) $db->where("`group` = '{$oRow['group']}'");
					else $db->where("`group` = '{$oRow['group']}' AND {$complexGrp}");

					$db->query($table);
					$total = $db->numRows();
					$db->close();

					if($orderDirection == 'up' or $orderDirection == 'down'){
						if($orderDirection == 'up' and $oRow['order'] > 1) $newOrder = $oRow['order'] - 1;
						else if($orderDirection == 'down' and $oRow['order'] < $total) $newOrder = $oRow['order'] + 1;
						else return false;
						
						if(!$complexGrp) $db->where("`group` = '{$oRow['group']}' AND `order` = '{$newOrder}'");
						else $db->where("`group` = '{$oRow['group']}' AND {$complexGrp} AND `order` = '{$newOrder}'");
						$db->query($table);
						if($db->numRows(true) == 1){

							if(!$complexGrp) $db->where("`group` = '{$oRow['group']}' AND `order` = '{$newOrder}'");
							else $db->where("`group` = '{$oRow['group']}' AND {$complexGrp} AND `order` = '{$newOrder}'");
							$db->update($table, ['order' => $oRow['order']]);
							$db->close();
						}
						$db->where("`group` = '{$oRow['group']}' AND `id` = '{$id}'");
						$db->update($table, ['order' => $newOrder]);
						$db->close();
						return true;
					}
					else if($orderDirection == 'top' or $orderDirection == 'bottom'){
						if($orderDirection == 'top' and $oRow['order'] > 1) return self::_reorderTableAsGrp($table, $id, $oRow['group'], $oRow['order'], $orderDirection, $total, $complexGrp);
						else if($orderDirection == 'bottom' and $oRow['order'] < $total) return self::_reorderTableAsGrp($table, $id, $oRow['group'], $oRow['order'], $orderDirection, $total, $complexGrp);
					}
					else return false; //we can't do anything
					
				}
				else{

					$db->where();
					$db->query($table);
					$total = $db->numRows();
					$db->close();

					if($orderDirection == 'up' or $orderDirection == 'down'){
						
						if($orderDirection == 'up' and $oRow['order'] > 1) $newOrder = $oRow['order'] - 1;
						else if($orderDirection == 'down' and $oRow['order'] < $total) $newOrder = $oRow['order'] + 1;
						else return false;

						$db->where("`order` = '{$newOrder}'");
						$db->query($table);
						if($db->numRows(true) == 1){

							$db->where("`order` = '{$newOrder}'");
							$db->update($table, ['order' => $oRow['order']]);
							$db->close();
						}
						$db->where("`id` = '{$id}'");
						$db->update($table, ['order' => $newOrder]);
						$db->close();
						return true;

					}
					else if($orderDirection == 'top' or $orderDirection == 'bottom'){
						if($orderDirection == 'top' and $oRow['order'] > 1) return self::_reorderTable($table, $id, $oRow['order'], $orderDirection, $total);
						else if($orderDirection == 'bottom' and $oRow['order'] < $total) return self::_reorderTable($table, $id, $oRow['order'], $orderDirection, $total);
					}
					else return false; //we can't do anything
				}
			}
		}

		protected static function _reorderTable($table, $id, $oOrder, $orderDirection, $total)
		{
			$db = new DB;

			$ids = array();
			$i = 0;
			if($orderDirection == 'top'){
				
				$db->select("`id`,`order`");
				$db->where("`order` < '{$oOrder}'");
				$db->query($table);
				while($rows = $db->fetch()){
					$ids[$i]['id'] = $rows['id'];
					$ids[$i]['order'] = $rows['order'];
					$i++;
				}
				$db->close();

				for($n = 0; $n < count($ids); $n++){
					$rowID = $ids[$n]['id'];
					$newOrder = $ids[$n]['order'] + 1;

					$db->select("`id`,`order`");
					$db->where("`id` = '{$rowID}'");
					$db->update($table, ['order' => $newOrder]);
				}

				$db->select("`id`,`order`");
				$db->where("`id` = '{$id}'");
				$db->update($table, ['order' => '1']);
			}
			else if($orderDirection == 'bottom'){
				
				$db->select("`id`,`order`");
				$db->where("`order` > '{$oOrder}'");
				$db->query($table);
				while($rows = $db->fetch()){
					$ids[$i]['id'] = $rows['id'];
					$ids[$i]['order'] = $rows['order'];
					$i++;
				}
				$db->close();

				for($n = 0; $n < count($ids); $n++){
					$rowID = $ids[$n]['id'];
					$newOrder = $ids[$n]['order'] - 1;

					$db->select("`id`,`order`");
					$db->where("`id` = '{$rowID}'");
					$db->update($table, ['order' => $newOrder]);
				}

				$db->select("`id`,`order`");
				$db->where("`id` = '{$id}'");
				$db->update($table, ['order' => $total]);
			}
			return true;
		}

		protected static function _reorderTableAsGrp($table, $id, $oGroup, $oOrder, $orderDirection, $total, $complexGrp = false)
		{
			$db = new DB;

			$ids = array();
			$i = 0;
			if($orderDirection == 'top'){
				
				$db->select("`id`,`order`");
				if(!$complexGrp) $db->where("`group` = '{$oGroup}' AND `order` < '{$oOrder}'");
				else $db->where("`group` = '{$oGroup}' AND {$complexGrp} AND `order` < '{$oOrder}'");
				$db->query($table);
				while($rows = $db->fetch()){
					$ids[$i]['id'] = $rows['id'];
					$ids[$i]['order'] = $rows['order'];
					$i++;
				}
				$db->close();

				for($n = 0; $n < count($ids); $n++){
					$rowID = $ids[$n]['id'];
					$newOrder = $ids[$n]['order'] + 1;

					$db->select("`id`,`order`");
					$db->where("`id` = '{$rowID}'");
					$db->update($table, ['order' => $newOrder]);
				}

				$db->select("`id`,`order`");
				$db->where("`id` = '{$id}'");
				$db->update($table, ['order' => '1']);
			}
			else if($orderDirection == 'bottom'){
				
				$db->select("`id`,`order`");
				if(!$complexGrp) $db->where("`group` = '{$oGroup}' AND `order` > '{$oOrder}'");
				else $db->where("`group` = '{$oGroup}' AND {$complexGrp} AND `order` > '{$oOrder}'");
				$db->query($table);
				while($rows = $db->fetch()){
					$ids[$i]['id'] = $rows['id'];
					$ids[$i]['order'] = $rows['order'];
					$i++;
				}
				$db->close();

				for($n = 0; $n < count($ids); $n++){
					$rowID = $ids[$n]['id'];
					$newOrder = $ids[$n]['order'] - 1;

					$db->select("`id`,`order`");
					$db->where("`id` = '{$rowID}'");
					$db->update($table, ['order' => $newOrder]);
				}

				$db->select("`id`,`order`");
				$db->where("`id` = '{$id}'");
				$db->update($table, ['order' => $total]);
			}
			return true;
		}

		/**
		 * This method cycles through the orders within a table and corrects the integrity
		 * of the order should it be sequential. This occurs when a record is removed as the
		 * order will need to be rewrote. This is definitely the downfall of having a system
		 * which uses ordering! This method also accepts groups and sub-groups ordering via 
		 * the groupField# arguments.
		 * @param  string $table the table which we need to address
		 * @param  string $orderField the column name that holds the order i.e. 'order'
		 * @param  string $groupField1 if required this is the column name for the group
		 * @param  string $groupField2 if required this is the column name for the group within the group
		 * @return null
		 */
		public static function rebuildOrders($table, $orderField, $groupField1 = null, $groupField2 = null)
		{
			$groupArray = array();

			$db1 = new DB;
			$db2 = new DB;
			$db3 = new DB;

			if($groupField2 != null){
				$db1->select("DISTINCT `{$groupField1}`");
				$db1->orderBy("`{$groupField1}` ASC");
				$db1->query($table);
				while($grpRow1 = $db1->fetch()){
					
					$db2->where("`{$groupField1}` = '{$grpRow1[$groupField1]}'");
					$db2->select("DISTINCT `{$groupField2}`");
					$db2->orderBy("`{$groupField1}` ASC");
					$db2->query($table);
					while($grpRow2 = $db2->fetch()){

						self::_rewriteOrders($table, $orderField, $groupField1, $groupField2, $grpRow1, $grpRow2);

					}
					$db2->close();
				}
				$db1->close();
			}
			else if($groupField1 != null){

				$db1->select("DISTINCT `{$groupField1}`");
				$db1->orderBy("`{$groupField1}` ASC");
				$db1->query($table);
				while($grpRow1 = $db1->fetch()){
					
					self::_rewriteOrders($table, $orderField, $groupField1, $groupField2, $grpRow1);

				}
				$db1->close();
			}
			else{
				
				self::_rewriteOrders($table, $orderField, $groupField1, $groupField2);
			}
		}

		/**
		 * Required for the rebuildOrders method. This is basically the core operation of the rebuildOrders method
		 * @param  string $table the table
		 * @param  string $orderField the order column name
		 * @param  string $groupField1 optional group
		 * @param  string $groupField2 optional sub-group
		 * @param  string $grpRow1 supplied if $groupField1 is addressed
		 * @param  string $grpRow2 supplied if $groupField2 is addressed
		 * @return null
		 */
		protected static function _rewriteOrders($table, $orderField, $groupField1, $groupField2, $grpRow1 = null, $grpRow2 = null)
		{
			$db = new DB;
			$rowsArray = array();
			$i = 1;
			$n = 1;
			
			if($groupField1 != null and $groupField2 != null){
				$db->select("`id`, `{$orderField}`, `{$groupField1}`");
				$db->where("`{$groupField1}` = '{$grpRow1[$groupField1]}' AND `{$groupField2}` = '{$grpRow2[$groupField2]}'");
			}
			else if($groupField1 != null){
				$db->select("`id`, `{$orderField}`, `{$groupField1}`");
				$db->where("`{$groupField1}` = '{$grpRow1[$groupField1]}'");
			}
			else{
				$db->select("`id`, `{$orderField}`");
			}
			
			$db->orderBy("`{$orderField}` ASC");
			$db->query($table);

			while($rows = $db->fetch()){
				if($rows[ $orderField ] != $i){
					$rowsArray[$n]['id'] = $rows['id'];
					$rowsArray[$n]['ordering'] = $rows[ $orderField ];
					$rowsArray[$n]['ordering'] = $i;
					$n++;
				}
				$i++;
			}
			$db->close();

			for($n = 1; $n <= count($rowsArray); $n++){
				$db->where("`id` = '{$rowsArray[$n]['id']}'");
				$db->update($table, [ $orderField => $rowsArray[$n]['ordering'] ]);
			}
		}
	}

//!EOF : /main/application/handlers/order.php