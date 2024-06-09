<?php

    class Mlevel_rewards extends model
    {
        public $error = false, $vars = [], $characters = [], $char_info = [], $errors = [];

        public function __contruct(){
            parent::__construct();
        }

				
		public function checkClaimedReward($rid, $cid, $user, $server){
			$stmt = $this->website->db('web')->prepare('SELECT id FROM DmN_Level_Claimed_Rewards WHERE reward_id = :rid AND memb___id = :user AND server = :server AND char_id = :cid');
			$stmt->execute([
				':rid' => $rid,
				':user' => $user,
				':server' => $server,
				':cid' => $cid
			]);
			
			return $stmt->fetch();
		}
		
		public function get_vip_package_title($vip_type = 1){
			return $this->website->db('web')->query('SELECT package_title, vip_time FROM DmN_Vip_Packages WHERE id = '.$this->website->db('web')->escape($vip_type).'')->fetch();
        }

				
		public function generate_serial($server){
			$query = $this->website->db('game', $server)->query('EXEC WZ_GetItemSerial');
            $data = $query->fetch();
            $query->close_cursor();
            return $data;
        }

				
		public function logReward($rid, $cid, $user, $server){
			$stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Level_Claimed_Rewards (reward_id, memb___id, server, char_id, claim_date) VALUES (:rid, :memb___id, :server, :cid, :claim_date)');
			$stmt->execute([
				':rid' => $rid,
				':memb___id' => $user,
				':server' => $server,
				':cid' => $cid,
				':claim_date' => time()
			]);
		}
		
		public function load_logs($page = 1, $per_page = 25, $acc = '', $server = 'All'){
            if(($acc == '' || $acc == '-') && $server == 'All')
                $items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape($per_page) . '  id, reward_id, char_id, memb___id, server, claim_date FROM DmN_Level_Claimed_Rewards WHERE id Not IN (SELECT Top ' . $this->website->db('web')->escape($per_page * ($page - 1)) . ' id FROM DmN_Level_Claimed_Rewards ORDER BY id DESC) ORDER BY id DESC'); 
			else{
                if(($acc != '' && $acc != '-') && $server == 'All')
                    $items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape($per_page) . ' id, reward_id, char_id, memb___id, server, claim_date FROM DmN_Level_Claimed_Rewards WHERE memb___id like \'%' . $this->website->db('web')->escape($acc) . '%\' AND id Not IN (SELECT Top ' . $this->website->db('web')->escape($per_page * ($page - 1)) . ' id FROM DmN_Level_Claimed_Rewards WHERE memb___id like \'%' . $this->website->db('web')->escape($acc) . '%\' ORDER BY id DESC) ORDER BY id DESC'); else
				$items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape($per_page) . ' id, reward_id, char_id, memb___id, server, claim_date FROM DmN_Level_Claimed_Rewards WHERE memb___id like \'%' . $this->website->db('web')->escape($acc) . '%\' AND server = '.$this->website->db('web')->escape($server).' AND id Not IN (SELECT Top ' . $this->website->db('web')->escape($per_page * ($page - 1)) . ' id DmN_Level_Claimed_Rewards WHERE memb___id like \'%' . $this->website->db('web')->escape($acc) . '%\' AND server = '.$this->website->db('web')->escape($server).' ORDER BY id DESC) ORDER BY id DESC');
            }
			$logs = [];
            foreach($items->fetch_all() as $value){
				$charData = $this->char_info($value['char_id'], $value['server'], true);
				$logs[] = [
					'reward_id' => $value['reward_id'], 
					'char_id' => $value['char_id'], 
					'name' => $this->char_info['Name'],
					'memb___id' => htmlspecialchars($value['memb___id']), 
					'server' => htmlspecialchars($value['server']), 
					'claim_date' => date('d/m/Y H:i:s', $value['claim_date'])
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
            $count = $this->website->db('web')->snumrows('SELECT COUNT(id) AS count FROM DmN_Level_Claimed_Rewards ' . $sql . '');
            return $count;
        }

				
		public function load_char_list($account, $server){
			$stmt = $this->website->db('game', $server)->prepare('SELECT '.$this->website->get_char_id_col($server).' AS id, Name FROM Character WHERE AccountId = :account');
			$stmt->execute([':account' => $account]);
			$i = 0;
			while($row = $stmt->fetch()){
				$this->characters[] = [
					'id' => $row['id'], 
					'name' => $row['Name']
				];
				$i++;
			}
			if($i > 0){
				return $this->characters;
			} else{
				return false;
			}
		}
		
		private function reset_column($server){
            $resets = $this->config->values('table_config', [$server, 'resets', 'column']);
            if($resets && $resets != ''){
                return $resets . ' AS resets,';
            }
            return '0 AS resets,';
        }

        private function greset_column($server){
            $grand_resets = $this->config->values('table_config', [$server, 'grand_resets', 'column']);
            if($grand_resets && $grand_resets != ''){
                return $grand_resets . ' AS grand_resets,';
            }
            return '0 AS grand_resets,';
        }
		
		private function load_master_level($char, $server){
            if($this->config->values('table_config', [$server, 'master_level', 'column']) != false){
                $stmt = $this->website->db('game', $server)->prepare('SELECT ' . $this->config->values('table_config', [$server, 'master_level', 'column']) . ' AS mlevel FROM ' . $this->config->values('table_config', [$server, 'master_level', 'table']) . ' WHERE ' . $this->config->values('table_config', [$server, 'master_level', 'identifier_column']) . ' = :char');
                $stmt->execute([':char' => $char]);
                $mlevel = $stmt->fetch();
                if($mlevel){
                    return $mlevel['mlevel'];
                }
            }
            return 0;
        }

				
		public function char_info($char, $server, $by_id = false){
            $identifier = ($by_id) ? $this->website->get_char_id_col($server) : 'Name';
			$ruud = ', 0 AS Ruud';
			if(MU_VERSION >= 5){
				if($this->website->db('game', $server)->check_if_column_exists('RuudMoney', 'Character') != false){
					$ruud = ', RuudMoney AS Ruud';
				}
				else{
					$ruud = ', Ruud';
				}
			}
			
            $stmt = $this->website->db('game', $server)->prepare('SELECT AccountId, Name, Money, Class, cLevel, ' . $this->reset_column($server) . $this->greset_column($server) . ' '.$this->website->get_char_id_col($server).' AS id '.$ruud.' FROM Character WHERE ' . $identifier . ' = :char');
            $stmt->execute([':char' => $char]);
            if($this->char_info = $stmt->fetch()){
                $this->char_info['mlevel'] = $this->load_master_level($this->char_info['Name'], $server); 
            }
        }
		
		public function guid($user, $server){
            $stmt = $this->website->db('account', $server)->prepare('SELECT memb_guid FROM MEMB_INFO WHERE memb___id = :user');
            $stmt->execute([':user' => $user]);
            $info = $stmt->fetch();
            return $info['memb_guid'];
        }
		
		public function checkZen($char, $user, $server){
			$stmt = $this->website->db('game', $server)->prepare('SELECT Money FROM Character WHERE AccountId = :account AND '.$this->website->get_char_id_col($server).' = :char');
			$stmt->execute([':account' => $user, ':char' => $char]);
			return $stmt->fetch();
		}

				
		public function add_zen($money, $char, $user, $server){
            $stmt = $this->website->db('game', $server)->prepare('UPDATE Character SET Money = Money + :money WHERE AccountId = :account AND '.$this->website->get_char_id_col($server).' = :char');
			$stmt->execute([':money' => (int)$money, ':account' => $user, ':char' => $char]);
        }

				
		public function checkRuud($char, $user, $server){
			if($this->website->db('game', $server)->check_if_column_exists('RuudMoney', 'Character') != false){
				$ruud = 'RuudMoney AS Ruud';
			}
			else{
				$ruud = 'Ruud';
			}
			$stmt = $this->website->db('game', $server)->prepare('SELECT '.$ruud.' FROM Character WHERE AccountId = :account AND '.$this->website->get_char_id_col($server).' = :char');
			$stmt->execute([':account' => $user, ':char' => $char]);
			return $stmt->fetch();
		}

				
		public function add_ruud($money, $char, $user, $server){
			if($this->website->db('game', $server)->check_if_column_exists('RuudMoney', 'Character') != false){
				$ruud = 'RuudMoney';
			}
			else{
				$ruud = 'Ruud';
			}
            $stmt = $this->website->db('game', $server)->prepare('UPDATE Character SET '.$ruud.' = '.$ruud.' + :money WHERE AccountId = :account AND '.$this->website->get_char_id_col($server).' = :char');
			$stmt->execute([':money' => (int)$money, ':account' => $user, ':char' => $char]);
        }

				
		public function inventory($char, $server){
			$stmt = $this->website->db('game', $server)->prepare('SELECT CONVERT(IMAGE, Inventory) AS Inventory FROM Character WHERE '.$this->website->get_char_id_col($server).' = :char');
			$stmt->execute([':char' => $char]);
			if($inv = $stmt->fetch()){
				$unpack = unpack('H*', $inv['Inventory']);
				$this->char_info['Inventory'] = $this->website->clean_hex($unpack[1]);
			}  
        }

				
		public function getInventoryContents($server){	
			$items = [];
			$items_array = str_split($this->char_info['Inventory'], $this->website->get_value_from_server($server, 'item_size'));
			
			for($a = 12; $a < 76; $a++){
				$items[$a] = $items_array[$a];
			}
			return $items;	
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
		
		public function check_space_gremory_case($user, $server, $name){
			return $this->website->db('game', $server)->snumrows('SELECT COUNT(GremoryCaseIndex) AS count FROM IGC_GremoryCase WHERE AccountID = '.$this->website->db('web')->escape($user).' AND Name = '.$this->website->db('web')->escape($name).' AND GCType = 2 AND UsedInfo = 0');
		}
		
		public function add_item_gremory_case($user, $server, $item, $name){
			if($this->website->db('game', $server)->check_if_table_exists('IGC_GremoryCase')){
				$serial = $this->generate_serial_gremorycase($server);
				$time = ((24*7)*60*60);
				$itemTime = 0;
				$itemTimeStamp = 0;
				if($item['expires'] != 0){
					$itemTime = $item['expires'] * 60;
					$itemTimeStamp = time() + $item['expires'] * 60;
				}
				$this->website->db('game', $server)->query('EXEC IGC_GremoryCaseInsert 2, 100, '.$this->website->db('web')->escape($user).', '.$this->website->db('web')->escape($name).', '.$item['cat'].', '.$item['id'].', '.$item['lvl'].', '.$item['dur'].', '.$item['skill'].', '.$item['luck'].', '.$item['opt'].', 0, 0, 0, '.$item['exe'].', 0, 0, 0, 0, 0, 0, 255, 255, 255, '.$serial.', '.$time.', '.$itemTime.', '.time().', '.(time() + $time).', '.$itemTimeStamp.', 0');
				return;
			}
		}
		
		public function generate_serial_gremorycase($server){
			$query = $this->website->db('game', $server)->query('EXEC IGC_GetGremoryCaseSerial 1');
            $data = $query->fetch();
            $query->close_cursor();
            return $data['ItemSerial'];
        }
		
		public function add_item_cash_shop($user, $server, $item){
			$this->website->db('game', $server)->query('EXEC WZ_IBS_AddItem '.$this->website->db('web')->escape($user).', 673, '.$item['cguid'].', '.$item['cid'].', 1, \'LevelRewards\'');
			return;
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
