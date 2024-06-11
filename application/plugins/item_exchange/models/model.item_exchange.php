<?php

    class Mitem_exchange extends model
    {
        public $error = false, $vars = [], $characters = [], $char_info = [], $errors = [];

        public function __contruct(){
            parent::__construct();
        }

		 		
		public function load_char_list($account, $server){
			$stmt = $this->website->db('game', $server)->prepare('SELECT '.$this->website->get_char_id_col($server).' AS id, Name FROM Character WHERE AccountId = :account');
			$stmt->execute([':account' => $account]);
			$i = 0;
			while($row = $stmt->fetch()){
				$this->characters[] = [
					'id' => $row['id'], 
					'name' => $row['Name'], 
				];
				$i++;
			}
			if($i > 0){
				return $this->characters;
			} else{
				return false;
			}
		}
		
		public function check_char($char, $account, $server, $byId = true){
			$check = ($byId == true) ? $this->website->get_char_id_col($server) : 'Name';
            $stmt = $this->website->db('game', $server)->prepare('SELECT Name FROM Character WHERE AccountId = :user AND '.$check.' = :char');
            $stmt->execute([':user' => $account, ':char' => $char]);
            return $stmt->fetch();
        }
		 		
		public function checkCurrencyPoints($user, $server){
			$stmt = $this->website->db('web')->prepare('SELECT points FROM DmN_Item_Exchange_Points WHERE memb___id = :account AND server = :server');
			$stmt->execute([
				':account' => $user,
				':server' => $server
			]);
			$data = $stmt->fetch();
			if($data != false){
				return $data['points'];
			}
			return false;
		}
	 		
		public function decreaseCurrencyPoints($user, $server, $points){
			$stmt = $this->website->db('web')->prepare('UPDATE DmN_Item_Exchange_Points SET points = points - :points WHERE memb___id = :account AND server = :server');
			$stmt->execute([
				':points' => $points,
				':account' => $user,
				':server' => $server
			]);
		}
		 		
		public function increaseCurrencyPoints($user, $server, $points){
			if($this->checkCurrencyPoints($user, $server) !== false){
				$this->updateCurrencyPoints($user, $server, $points);
			}
			else{
				$this->insertCurrencyPoints($user, $server, $points);
			}
		}
	 		
		public function updateCurrencyPoints($user, $server, $points){
			$stmt = $this->website->db('web')->prepare('UPDATE DmN_Item_Exchange_Points SET points = points + :points WHERE memb___id = :account AND server = :server');
			$stmt->execute([
				':points' => $points,
				':account' => $user,
				':server' => $server
			]);
		}
		 		
		public function insertCurrencyPoints($user, $server, $points){
			$stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Item_Exchange_Points (memb___id, server, points) VALUES (:account, :server, :points)');
			$stmt->execute([
				':account' => $user,
				':server' => $server,
				':points' => $points
			]);
		}
		
		public function get_vip_package_title($vip_type = 1){
			return $this->website->db('web')->query('SELECT package_title, vip_time FROM DmN_Vip_Packages WHERE id = '.$this->website->db('web')->escape($vip_type).'')->fetch();
        }
	 		
		public function log_reward($rid, $cid, $user, $server){
			$stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Item_Exchange_Log (exchange_key, memb___id, server, char_id, claim_date) VALUES (:rid, :memb___id, :server, :cid, :claim_date)');
			$stmt->execute([
				':rid' => $rid,
				':memb___id' => $user,
				':server' => $server,
				':cid' => $cid,
				':claim_date' => time()
			]);
		}
		
		public function load_logs($page = 1, $per_page = 25, $acc = '', $server = 'All'){
			if($page == ''){
				$page = 1;
			}
			$sql1 = 'WHERE ';
			$sql2 = '';
			if($acc != '' && $acc != '-'){
				$sql1 .= 'memb___id like \'%' . $this->website->db('web')->escape($acc) . '%\' AND ';
				$sql2 .= 'WHERE memb___id like \'%' . $this->website->db('web')->escape($acc) . '%\' ';
			}
			if($server != '' && $server != 'All'){
				if($sql1 != ''){
					$sql1 .= 'server = '.$this->website->db('web')->escape($server).' AND ';
				} 
				else{
					$sql1 .= 'server = '.$this->website->db('web')->escape($server).' AND ';
				}
				if($sql2 != ''){
					$sql2 .= 'AND server = '.$this->website->db('web')->escape($server).' ';
				} 
				else{
					$sql2 .= 'WHERE server = '.$this->website->db('web')->escape($server).' ';
				}
			}
                
			$items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape($per_page) . '  id, exchange_key, char_id, memb___id, server, claim_date FROM DmN_Item_Exchange_Log '.$sql1.' id Not IN (SELECT Top ' . $this->website->db('web')->escape($per_page * ($page - 1)) . ' id FROM DmN_Item_Exchange_Log '.$sql2.' ORDER BY id DESC) ORDER BY id DESC'); 

			$logs = [];
            foreach($items->fetch_all() as $value){
				if($value['char_id'] != ''){
					$charData = $this->check_char($value['char_id'], $value['memb___id'], $value['server'], true);
				}
				else{
					$charData = false;
				}
				$logs[] = [
					'exchange_id' => $value['exchange_key'], 
					'char_id' => $value['char_id'], 
					'name' => ($charData != false) ? $charData['Name'] : '',
					'memb___id' => htmlspecialchars($value['memb___id']), 
					'server' => htmlspecialchars($value['server']), 
					'claim_date' => date('d/m/Y H:i:s', $value['claim_date'])
				];
            }
            return $logs;
        }
		
		public function load_points_logs($page = 1, $per_page = 25, $acc = '', $server = 'All'){
			if($page == ''){
				$page = 1;
			}
			$sql1 = 'WHERE ';
			$sql2 = '';
			if($acc != '' && $acc != '-'){
				$sql1 .= 'memb___id like \'%' . $this->website->db('web')->escape($acc) . '%\' AND ';
				$sql2 .= 'WHERE memb___id like \'%' . $this->website->db('web')->escape($acc) . '%\' ';
			}
			if($server != '' && $server != 'All'){
				if($sql1 != ''){
					$sql1 .= 'server = '.$this->website->db('web')->escape($server).' AND ';
				} 
				else{
					$sql1 .= 'server = '.$this->website->db('web')->escape($server).' AND ';
				}
				if($sql2 != ''){
					$sql2 .= 'AND server = '.$this->website->db('web')->escape($server).' ';
				} 
				else{
					$sql2 .= 'WHERE server = '.$this->website->db('web')->escape($server).' ';
				}
			}
                
			$items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape($per_page) . '  id, memb___id, server, points FROM DmN_Item_Exchange_Points '.$sql1.' id Not IN (SELECT Top ' . $this->website->db('web')->escape($per_page * ($page - 1)) . ' id FROM DmN_Item_Exchange_Points '.$sql2.' ORDER BY id DESC) ORDER BY id DESC'); 

			$logs = [];
            foreach($items->fetch_all() as $value){

				$logs[] = [
					'memb___id' => htmlspecialchars($value['memb___id']), 
					'server' => htmlspecialchars($value['server']), 
					'points' => $value['points']
				];
            }
            return $logs;
        }

        public function count_total_logs($acc = '', $server = 'All'){
            $sql = '';
            if($acc != '' && $acc != '-'){
                $sql .= 'WHERE memb___id like \'%' . $this->website->db('web')->escape($acc) . '%\'';
                if($server != 'All'){
                    $sql .= ' AND server = '.$this->website->db('web')->escape($server).'';
                }
            }
            $count = $this->website->db('web')->snumrows('SELECT COUNT(id) AS count FROM DmN_Item_Exchange_Log ' . $sql . '');
            return $count;
        }
		
		public function count_total_logs_points($acc = '', $server = 'All'){
            $sql = '';
            if($acc != '' && $acc != '-'){
                $sql .= 'WHERE memb___id like \'%' . $this->website->db('web')->escape($acc) . '%\'';
                if($server != 'All'){
                    $sql .= ' AND server = '.$this->website->db('web')->escape($server).'';
                }
            }
            $count = $this->website->db('web')->snumrows('SELECT COUNT(id) AS count FROM DmN_Item_Exchange_Points ' . $sql . '');
            return $count;
        }

		 		
		public function vault($user, $server){
			$stmt = $this->website->db('game', $server)->prepare('SELECT CONVERT(IMAGE, Items) AS Items FROM Warehouse WHERE AccountId = :user');
			$stmt->execute([':user' => $user]);
			if($vault = $stmt->fetch()){
				$unpack = unpack('H*', $vault['Items']);
				$this->vars['vault_items'] = $this->website->clean_hex($unpack[1]);
				return true;
			} 
			return false;
        }
	 		
		public function inventory($char, $server){
			$stmt = $this->website->db('game', $server)->prepare('SELECT CONVERT(IMAGE, Inventory) AS Inventory FROM Character WHERE '.$this->website->get_char_id_col($server).' = :char');
			$stmt->execute([':char' => $char]);
			if($inv = $stmt->fetch()){
				$unpack = unpack('H*', $inv['Inventory']);
				$this->char_info['Inventory'] = $this->website->clean_hex($unpack[1]);
			}  
        }
		 		
		public function getVaultContents($server){	
			$items = [];
			$items_array = str_split($this->vars['vault_items'], $this->website->get_value_from_server($server, 'item_size'));
			
			for($a = 0; $a < $this->website->get_value_from_server($server, 'wh_multiplier'); $a++){
				$items[$a] = $items_array[$a];
			}
			return $items;	
		}	
		 		
		public function getInventoryContents($server){	
			$items = [];
			$items_array = str_split($this->char_info['Inventory'], $this->website->get_value_from_server($server, 'item_size'));
			
			for($a = 12; $a < 76; $a++){
				$items[$a] = $items_array[$a];
			}
			return $items;	
		}	
		 		
		public function updateVaultSlots($slots, $server){
			$items_array = str_split($this->vars['vault_items'], $this->website->get_value_from_server($server, 'item_size'));
			for($a = 0; $a < $this->website->get_value_from_server($server, 'wh_multiplier'); $a++){
				if(in_array($a, $slots)){
					$items_array[$a] = str_repeat('F', $this->website->get_value_from_server($server, 'item_size'));
				}
			}
			return $items_array;
		}
		 		
		public function updateInventorySlots($slots, $server){
			$items_array = str_split($this->char_info['Inventory'], $this->website->get_value_from_server($server, 'item_size'));
			for($a = 12; $a < 76; $a++){
				if(in_array($a, $slots)){
					$items_array[$a] = str_repeat('F', $this->website->get_value_from_server($server, 'item_size'));
				}
			}
			return $items_array;
		}
		 		
		public function addItemsToInventory($items, $server){
			$items_array = str_split($this->char_info['Inventory'], $this->website->get_value_from_server($server, 'item_size'));
			for($a = 12; $a < 76; $a++){
				if(isset($items[$a-12]) && $items[$a-12] != str_repeat('F', $this->website->get_value_from_server($server, 'item_size'))){
					$items_array[$a] = $items[$a-12];
				}
			}
			return $items_array;
		}
		 		
		public function updateInventory($char, $server, $items){
			$stmt = $this->website->db('game', $server)->prepare('UPDATE Character SET Inventory = 0x' . implode('', $items) . ' WHERE '.$this->website->get_char_id_col($server).' = :char');
            $stmt->execute([':char' => $char]);
		}
		 		
		public function updateVault($user, $server, $items){
			$stmt = $this->website->db('game', $server)->prepare('UPDATE Warehouse SET Items = 0x' . implode('', $items) . ' WHERE AccountId = :user');
            $stmt->execute([':user' => $user]);
		}	
		
		public function addExpirableItem($guid, $server, $name, $index, $time, $serial, $currTime, $effectType = 0, $effect1 = 0, $effect2 = 0, $itemType = 2){
			if($this->website->db('game', $server)->check_if_table_exists('T_PeriodItemInfo')){
				$this->website->db('game', $server)->query('EXEC WZ_PeriodItemInsert '.$guid.', \''.$name.'\', '.$index.', '.$effectType.', '.$effect1.', '.$effect2.', '.$time.', \''.date('Y-m-d H:i:s', ($currTime + $time * 60)).'\'');
				return;
			}
			if($this->website->db('game', $server)->check_if_table_exists('CashShopPeriodicItem')){
				$stmt = $this->website->db('game', $server)->prepare('INSERT INTO CashShopPeriodicItem (ItemSerial, Time) VALUES (:ItemSerial, :Time)');
				$stmt->execute([':ItemSerial' => $serial, ':Time' => ($currTime + $time * 60)]);
				return;
			}
			if($this->website->db('game', $server)->check_if_table_exists('CashShopPeriodItem')){
				$stmt = $this->website->db('game', $server)->prepare('INSERT INTO CashShopPeriodItem (ItemSerial, Time) VALUES (:ItemSerial, :Time)');
				$stmt->execute([':ItemSerial' => $serial, ':Time' => ($currTime + $time * 60)]);
				return;
			}
			if($this->website->db('game', $server)->check_if_table_exists('IGC_PeriodItemInfo')){
				$this->website->db('game', $server)->query('EXEC IGC_PeriodItemInsertEx '.$guid.', \''.$name.'\', '.$itemType.', '.$index.', '.$effectType.', '.$effect1.', '.$effect2.', '.$serial.', '.($time * 60).', '.$currTime.', '.($currTime + $time * 60).'');
				return;
			}
		}

		 		
		public function check_space_inventory($items, $item_x, $item_y, $multiplier = 64, $size = 32, $hor = 8, $ver = 8, $add_to_slot = false, $iteminfo = null, $takenSlots = []){
            $spots = str_repeat('0', $multiplier);
			
			if(!empty($takenSlots)){
				$spotsArr = str_split($spots, 1);
				foreach($takenSlots AS $key => $val){
					$spotsArr[$key] = 1;
				}
				$spots = implode('', $spotsArr);
			}
			
            $items_array = $items;
            for($i = 0; $i < $multiplier; ++$i){
                if($items_array[$i] != str_repeat('F', $size) && !empty($items_array[$i])){
                    $iteminfo->itemData($items_array[$i]);
                    if($iteminfo->getX() == false || $iteminfo->getY() == false){
                        $this->errors[] = sprintf(__('Found unknown item in inventory please remove it first. Slot: %d') . $i);
                        return null;
                    }
                    $y = 0;
                    while($y < $iteminfo->getY()){
                        $y++;
                        $x = 0;
                        while($x < $iteminfo->getX()){
                            $spots = substr_replace($spots, '1', ($i + $x) + (($y - 1) * $hor), 1);
                            $x++;
                        }
                    }
                }
            }
            for($y2 = 0; $y2 <= $ver - $item_y; $y2++){
                for($x2 = 0; $x2 <= $hor - $item_x; $x2++){
                    if($this->search($x2, $y2, $item_x, $item_y, $spots, $hor)){
                        if(!$add_to_slot){
                            return $x2 + ($y2 * $hor);
                        } else{
                            if($add_to_slot == ($x2 + ($y2 * $hor)))
                                return ($x2 + ($y2 * $hor));
                        }
                    }
                }
            }
            $this->errors[] = __('Please free up space in your inventory.');
            return null;
        }

		 		
		public function search($x, $y, $item_w, $item_h, &$spots, $vault_w){
            for($yy = 0; $yy < $item_h; $yy++){
                for($xx = 0; $xx < $item_w; $xx++){
                    if($spots[$x + $xx + (($y + $yy) * $vault_w)] != '0')
                        return false;
                }
            }
            return true;
        }

		 		
		public function add_wcoins($user, $server, $amount = 0, $config = []){
            $stmt = $this->website->db($config['db'], $server)->prepare('UPDATE ' . $config['table'] . ' SET ' . $config['column'] . ' = ' . $config['column'] . ' + :wcoins WHERE ' . $config['identifier_column'] . ' = :account');
            $stmt->execute([':wcoins' => $amount, ':account' => $user]);
            if($stmt->rows_affected() == 0){
                $stmt = $this->website->db($config['db'], $server)->prepare('INSERT INTO ' . $config['table'] . ' (' . $config['identifier_column'] . ', ' . $config['column'] . ') values (:user, :wcoins)');
                $stmt->execute([':user' => $user, ':wcoins' => $amount]);
            }
        }
		
		public function get_guid($user, $server){
            $stmt = $this->website->db('account', $server)->prepare('SELECT memb_guid FROM MEMB_INFO WHERE memb___id = :user');
            $stmt->execute([':user' => $user]);
            $info = $stmt->fetch();
            return $info['memb_guid'];
        }

        public function add_account_log($log, $credits, $acc, $server){
            $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Account_Logs (text, amount, date, account, server, ip) VALUES (:text, :amount, GETDATE(), :acc, :server, :ip)');
            $stmt->execute([':text' => $log, ':amount' => $credits, ':acc' => $acc, ':server' => $server, ':ip' => ip()]);
            $stmt->close_cursor();
        }
		
		public function check_connect_stat($account, $server){
            $stmt = $this->website->db('account', $server)->prepare('SELECT ConnectStat FROM MEMB_STAT WHERE memb___id = :user');
            $stmt->execute([':user' => $account]);
            if($status = $stmt->fetch()){
                return ($status['ConnectStat'] == 0);
            }
            return true;
        }
    }
