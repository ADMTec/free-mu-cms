<?php

    class Mwheel_of_fortune extends model
    {
        public $error = false, $vars = [], $characters = [], $char_info = [], $errors = [];

        public function __contruct(){
            parent::__construct();
        }

        public function __set($key, $val){
            $this->vars[$key] = $val;
        }

        public function __isset($name){
            return isset($this->vars[$name]);
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
		
		
		public function get_muun_content($char, $server = '') {
			if($this->website->db('game', $server)->check_if_table_exists('MuunInventory'))
				$tbl = 'MuunInventory';
			else
				$tbl = 'IGC_Muun_Inventory';
			$stmt = $this->website->db('game', $server)->prepare('SELECT CONVERT(IMAGE, Items) AS Items FROM '.$tbl.' WHERE Name = :char');
			$stmt->execute([':char' => $char]);
			$inv = $stmt->fetch();
			$stmt->close_cursor();
			if($inv != false){
				$unpack = unpack('H*', $inv['Items']);
				$this->vars['Items'] = $this->website->clean_hex($unpack[1]);
			} 
			else{
				$this->vars['Items'] = false;
			}
        }
		
		 
		public function find_free_muun_slot($char, $server) {
			$this->get_muun_content($char, $server);
            $items = str_split($this->vars['Items'], $this->website->get_value_from_server($server, 'item_size'));
			foreach($items AS $key => $val){
				if($key <= 3)
					continue;
				if(strtoupper($val) == str_pad("", $this->website->get_value_from_server($server, 'item_size'), "F")){
					return $key;
				}
			}
            return false;
        }
		
		 
        public function generate_new_muun_by_slot($slot, $item = '', $server = ''){
            $hex = str_split($this->vars['Items'], $this->website->get_value_from_server($server, 'item_size'));
            if(isset($hex[$slot])){
                $hex[$slot] = ($item == '') ? str_pad("", $this->website->get_value_from_server($server, 'item_size'), "F") : $item;
            }
            $this->vars['new_hex'] = implode('', $hex);
        }
		
		
		public function update_muun_inventory($char , $server){
			if($this->website->db('game', $server)->check_if_table_exists('MuunInventory'))
				$tbl = 'MuunInventory';
			else
				$tbl = 'IGC_Muun_Inventory';
            $stmt = $this->website->db('game', $server)->prepare('UPDATE '.$tbl.' SET Items = 0x' .  $this->vars['new_hex'] . ' WHERE Name = :char');
            $stmt->execute([':char' => $char]);
        }
		
		
		public function add_muun_period($char, $server, $serial, $time, $itemtype){
			if($this->website->db('game', $server)->check_if_table_exists('MuunPeriodItem')){
				$stmt = $this->website->db('game', $server)->prepare('INSERT INTO MuunPeriodItem (ItemSerial, Time) VALUES (:ItemSerial, :Time)');
				$stmt->execute([':ItemSerial' => $serial, ':Time' => ($currTime + $time * 60)]);
			}
			else{
				if($this->website->db('game', $server)->check_if_table_exists('IGC_Muun_Period')){
					$stmt = $this->website->db('game', $server)->prepare('INSERT INTO IGC_Muun_Period (Name, ItemType, UsedInfo, Serial, GetItemDate, ExpireDate, ExpireDateConvert) VALUES (:char, :itemtype, 0, :serial, GETDATE(), DATEADD(minute, +'.$time.', GETDATE()), '.strtotime("+$time minutes").')');
					$stmt->execute([':char' => $char, ':itemtype' => $itemtype, ':serial' => $serial]);
				}
			}
        }
		
		 
		public function inventory($char, $server){
			$stmt = $this->website->db('game', $server)->prepare('SELECT Name, CONVERT(IMAGE, Inventory) AS Inventory FROM Character WHERE '.$this->website->get_char_id_col($server).' = :char');
			$stmt->execute([':char' => $char]);
			if($inv = $stmt->fetch()){
				$unpack = unpack('H*', $inv['Inventory']);
				$this->char_info['Inventory'] = $this->website->clean_hex($unpack[1]);
				$this->char_info['Name'] = $inv['Name'];
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
		
			
		public function count_spins($account, $server){
			$stmt = $this->website->db('web')->prepare('SELECT COUNT(id) AS count FROM DmN_Wheel_Of_Fortune_Log WHERE account = :account AND server = :server AND spin_date >= :spin_date');
			$stmt->execute([
				':account' => $account,
				':server' => $server,
				':spin_date' => date('Ymd H:i:s', strtotime('-1 days', mktime(0, 0, 0)))
			]);
			return $stmt->fetch();
		}
		
			
		public function count_total_spins($account, $server){
			$stmt = $this->website->db('web')->prepare('SELECT amount FROM DmN_Wheel_Of_Fortune_Spins WHERE account = :account AND server = :server');
			$stmt->execute([
				':account' => $account,
				':server' => $server
			]);
			return $stmt->fetch();
		}
		
			
		public function insert_total_spins($account, $server){
			$stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Wheel_Of_Fortune_Spins (account, server) VALUES (:account, :server)');
			$stmt->execute([
				':account' => $account,
				':server' => $server
			]);
		}
		
		public function update_total_spins($account, $server){
			$stmt = $this->website->db('web')->prepare('UPDATE DmN_Wheel_Of_Fortune_Spins SET amount = amount + 1 WHERE account = :account AND server = :server');
			$stmt->execute([
				':account' => $account,
				':server' => $server
			]);
		}
		
			
		public function reset_total_spins($account, $server){
			$stmt = $this->website->db('web')->prepare('UPDATE DmN_Wheel_Of_Fortune_Spins SET amount = 0 WHERE account = :account AND server = :server');
			$stmt->execute([
				':account' => $account,
				':server' => $server
			]);
		}
		
		public function count_total_free_spins($account, $server){
			$stmt = $this->website->db('web')->prepare('SELECT spins FROM DmN_Wheel_Of_Fortune_FreeSpins WHERE memb___id = :account AND server = :server');
			$stmt->execute([
				':account' => $account,
				':server' => $server
			]);
			return $stmt->fetch();
		}
		
			
		public function insert_total_free_spins($account, $server, $spins){
			$stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Wheel_Of_Fortune_FreeSpins (memb___id, server, spins) VALUES (:account, :server, :spins)');
			$stmt->execute([
				':account' => $account,
				':server' => $server,
				':spins' => $spins
			]);
		}
		
			
		public function update_total_free_spins($account, $server, $spins){
			$stmt = $this->website->db('web')->prepare('UPDATE DmN_Wheel_Of_Fortune_FreeSpins SET spins = spins + :spins WHERE memb___id = :account AND server = :server');
			$stmt->execute([
				':spins' => $spins,
				':account' => $account,
				':server' => $server
			]);
		}
		
			
		public function decrease_free_spins($account, $server, $spins){
			$stmt = $this->website->db('web')->prepare('UPDATE DmN_Wheel_Of_Fortune_FreeSpins SET spins = spins - :spins WHERE memb___id = :account AND server = :server');
			$stmt->execute([
				':spins' => $spins,
				':account' => $account,
				':server' => $server
			]);
		}
		
			
		public function log_spin($account, $server, $reward_id){
			$stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Wheel_Of_Fortune_Log (account, server, spin_date, reward_id) VALUES (:account, :server, GETDATE(), :reward_id)');
			$stmt->execute([
				':account' => $account,
				':server' => $server,
				':reward_id' => $reward_id
			]);
		}
		
		 
		public function log_reward($account, $server, $reward_id, $coupon = false, $reward_data = []){
			$data = [
				':reward_id' => $reward_id,
				':account' => $account,
				':server' => $server,
				':date' => time(),
				':reward_data' => json_encode($reward_data)
			];
			$sql1 = 'reward_id, account, server, date_generated, reward_data';
			$sql2 = ':reward_id, :account, :server, :date, :reward_data';
			if($coupon != false){
				$data[':coupon'] = $coupon;
				$sql1 .= ', code';
				$sql2 .= ', :coupon';
			}
			$stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Wheel_Of_Fotune_Rewards ('.$sql1.') VALUES ('.$sql2.')');
			$stmt->execute($data);
		}
		
			
		public function get_reward_list($account, $server){
			$stmt = $this->website->db('web')->prepare('SELECT id, reward_id, character, is_claimed, date_generated, code, reward_data FROM DmN_Wheel_Of_Fotune_Rewards WHERE account = :account AND server = :server ORDER BY date_generated DESC');
			$stmt->execute([
				':account' => $account,
				':server' => $server
			]);
			return $stmt->fetch_all();
		}
		
		public function check_reward($account, $server, $id){
			$stmt = $this->website->db('web')->prepare('SELECT id, reward_id, character, is_claimed, date_generated, code, reward_data FROM DmN_Wheel_Of_Fotune_Rewards WHERE account = :account AND server = :server AND id = :id');
			$stmt->execute([
				':account' => $account,
				':server' => $server,
				':id' => $id
			]);
			return $stmt->fetch();
		}
		
		 
		public function set_reward_claimed($id, $char, $char_id, $account, $server){
			$stmt = $this->website->db('web')->prepare('UPDATE DmN_Wheel_Of_Fotune_Rewards SET is_claimed = 1, character = :character, character_id = :char_id, date_claimed = :time WHERE account = :account AND server = :server AND id = :id');
			$stmt->execute([
				':character' => $char, 
				':char_id' => $char_id,
				':time' => time(),
				':account' => $account,
				':server' => $server,
				':id' => $id
			]);
		}
		
		public function load_logs($page = 1, $per_page = 25, $acc = '', $server = 'All') {
            if(($acc == '' || $acc == '-') && $server == 'All')
                $items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape($per_page) . '  id, account, server, spin_date, reward_id FROM DmN_Wheel_Of_Fortune_Log WHERE id Not IN (SELECT Top ' . $this->website->db('web')->escape($per_page * ($page - 1)) . ' id FROM DmN_Wheel_Of_Fortune_Log ORDER BY id DESC) ORDER BY id DESC'); 
			else{
                if(($acc != '' && $acc != '-') && $server == 'All')
                    $items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape($per_page) . ' id, account, server, spin_date, reward_id FROM DmN_Wheel_Of_Fortune_Log WHERE account like \'%' . $this->website->db('web')->escape($acc) . '%\' AND id Not IN (SELECT Top ' . $this->website->db('web')->escape($per_page * ($page - 1)) . ' id FROM DmN_Wheel_Of_Fortune_Log WHERE account like \'%' . $this->website->db('web')->escape($acc) . '%\' ORDER BY id DESC) ORDER BY id DESC'); else
				$items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape($per_page) . ' id, account, server, spin_date, reward_id FROM DmN_Wheel_Of_Fortune_Log WHERE account like \'%' . $this->website->db('web')->escape($acc) . '%\' AND server = '.$this->website->db('web')->escape($server).' AND id Not IN (SELECT Top ' . $this->website->db('web')->escape($per_page * ($page - 1)) . ' id DmN_Wheel_Of_Fortune_Log WHERE account like \'%' . $this->website->db('web')->escape($acc) . '%\' AND server = '.$this->website->db('web')->escape($server).' ORDER BY id DESC) ORDER BY id DESC');
            }
			$logs = [];
            foreach($items->fetch_all() as $value){
				$logs[] = [
					'reward_id' => $value['reward_id'],  
					'account' => htmlspecialchars($value['account']), 
					'server' => htmlspecialchars($value['server']), 
					'used' => date('d/m/Y H:i:s', strtotime($value['spin_date']))
				];
            }
            return $logs;
        }

        public function count_total_logs($acc = '', $server = 'All'){
            $sql = '';
            if($acc != '' && $acc != '-'){
                $sql .= 'WHERE account like \'%' . $this->website->db('web')->escape($acc) . '%\'';
                if($server != 'All'){
                    $sql .= ' AND server = '.$this->website->db('web')->escape($server).'';
                }
            }
            $count = $this->website->db('web')->snumrows('SELECT COUNT(id) AS count FROM DmN_Wheel_Of_Fortune_Log ' . $sql . '');
            return $count;
        }
		
			
		public function add_wcoins($user, $server, $amount = 0, $config = []){
            $stmt = $this->website->db($config['db'], $server)->prepare('UPDATE ' . $config['table'] . ' SET ' . $config['column'] . ' = ' . $config['column'] . ' + :wcoins WHERE ' . $config['identifier_column'] . ' = :account');
            $stmt->execute([':wcoins' => $amount, ':account' => $user]);
            if($stmt->rows_affected() == 0){
                $stmt = $this->website->db($config['db'], $server)->prepare('INSERT INTO ' . $config['table'] . ' (' . $config['identifier_column'] . ', ' . $config['column'] . ') values (:user, :wcoins)');
                $stmt->execute([':user' => $user, ':wcoins' => $amount]);
            }
        }

        public function remove_wcoins($user, $server, $amount = 0, $config = []){
            $stmt = $this->website->db($config['db'], $server)->prepare('UPDATE ' . $config['table'] . ' SET ' . $config['column'] . ' = ' . $config['column'] . ' - :wcoins WHERE ' . $config['identifier_column'] . ' = :account');
            $stmt->execute([':wcoins' => $amount, ':account' => $user]);
        }
		
			
        public function get_wcoins($user, $server, $config = []){
            $stmt = $this->website->db($config['db'], $server)->prepare('SELECT ' . $config['column'] . ' FROM ' . $config['table'] . ' WHERE ' . $config['identifier_column'] . ' = :account');
            $stmt->execute([':account' => $user]);
            if($wcoins = $stmt->fetch()){
                return $wcoins[$config['column']];
            }
            return false;
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
		
		 
		public function add_ruud($money, $char, $user, $server) {
			if($this->website->db('game', $server)->check_if_column_exists('RuudMoney', 'Character') != false){
				$ruud = 'RuudMoney';
			}
			else{
				$ruud = 'Ruud';
			}
            $stmt = $this->website->db('game', $server)->prepare('UPDATE Character SET '.$ruud.' = '.$ruud.' + :money WHERE AccountId = :account AND '.$this->website->get_char_id_col($server).' = :char');
			$stmt->execute([':money' => (int)$money, ':account' => $user, ':char' => $char]);
        }
		
		public function check_connect_stat($account, $server){
            $stmt = $this->website->db('account', $server)->prepare('SELECT ConnectStat FROM MEMB_STAT WHERE memb___id = :user');
            $stmt->execute([':user' => $account]);
            if($status = $stmt->fetch()){
                return ($status['ConnectStat'] == 0);
            }
            return true;
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
    }
