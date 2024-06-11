<?php
    in_file();

    class Mbattle_pass extends model
    {
        public $error = false, $vars = [], $characters = [], $char_info = [], $errors = [];
        private $achievements = []; 

        public function __contruct(){
            parent::__construct();
        }

        public function __set($key, $val){
            $this->vars[$key] = $val;
        }

        public function __isset($name){
            return isset($this->vars[$name]);
        }
		
		
		public function checkPassType($user, $server, $date){
			$stmt = $this->website->db('web')->prepare('SELECT pass_type, date FROM DmN_Unlocked_BattlePass WHERE memb___id = :memb___id AND server = :server AND period = :period');
			$stmt->execute([':memb___id' => $user, ':server' => $server, ':period' => md5($date)]);
			return $stmt->fetch();
		}
		
		
		public function upgradePass($user, $server, $date, $type){
			if($this->checkPassType($user, $server, $date) == false){
				$stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Unlocked_BattlePass (memb___id, server, pass_type, period, date) VALUES (:memb___id, :server, :pass_type, :period, :date)');
				$stmt->execute([':memb___id' => $user, ':server' => $server, ':pass_type' => $type, ':period' => md5($date), ':date' => date('Y-m-d', time())]);
			}
			else{
				$stmt = $this->website->db('web')->prepare('UPDATE DmN_Unlocked_BattlePass SET pass_type = :pass_type, date = :date WHERE memb___id = :memb___id AND server = :server AND period = :period');
				$stmt->execute([':pass_type' => $type, ':date' => date('Y-m-d', time()), ':memb___id' => $user, ':server' => $server, ':period' => md5($date)]);
			}
		}
		
		
		public function resetPass($user, $server, $date){
			$stmt = $this->website->db('web')->prepare('DELETE FROM DmN_Battle_Pass_Progress WHERE memb___id = :memb___id AND server = :server AND season = :period');
			$stmt->execute([':memb___id' => $user, ':server' => $server, ':period' => md5($date)]);
		}
		
		
		public function getBattlePassProgress($user, $server, $date){
			$stmt = $this->website->db('web')->prepare('SELECT TOP 1 pass_level, is_completed, is_free_reward_taken, is_silver_reward_taken, is_platinum_reward_taken, date_completed FROM DmN_Battle_Pass_Progress WHERE memb___id = :user AND server = :server AND season = :season ORDER BY id DESC');
			$stmt->execute([':user' => $user, ':server' => $server, ':season' => md5($date)]);
			return $stmt->fetch();
		}
		
		
		public function insertBattlePassProgress($user, $server, $date, $pass_level){
			$stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Battle_Pass_Progress (pass_level, memb___id, server, season) VALUES (:pass_level, :user, :server, :season)');
			$stmt->execute([':pass_level' => $pass_level, ':user' => $user, ':server' => $server, ':season' => md5($date)]);
		}
		
		public function setLevelCompleted($user, $server, $date, $pass_level){
			$stmt = $this->website->db('web')->prepare('UPDATE DmN_Battle_Pass_Progress SET is_completed = 1, date_completed = '.$this->website->db('web')->escape(date('Y-m-d', time())).'  WHERE memb___id = :user AND server = :server AND pass_level = :pass_level AND season = :season');
			$stmt->execute([':user' => $user, ':server' => $server, ':season' => md5($date), ':pass_level' => $pass_level]);
		}
		
		public function setRewardCompleted($user, $server, $date, $pass_level, $reward_type){
			switch($reward_type){
				case 0:
					$sql = 'is_free_reward_taken = 1';
				break;
				case 1:
					$sql = 'is_silver_reward_taken = 1';
				break;
				case 2:
					$sql = 'is_platinum_reward_taken = 1';
				break;
			}
			$stmt = $this->website->db('web')->prepare('UPDATE DmN_Battle_Pass_Progress SET '.$sql.'  WHERE memb___id = :user AND server = :server AND pass_level = :pass_level AND season = :season');
			$stmt->execute([':user' => $user, ':server' => $server, ':season' => md5($date), ':pass_level' => $pass_level]);
		}
		
		public function checkCompletedRequirement($user, $server, $date, $pass_level, $reqId){
			$stmt = $this->website->db('web')->prepare('SELECT id FROM DmN_Battle_Pass_Completed_Requirements WHERE memb___id = :user AND server = :server AND season = :season AND pass_level = :pass_level AND req_id = :req_id');
			$stmt->execute([':user' => $user, ':server' => $server, ':season' => md5($date), ':pass_level' => $pass_level, ':req_id' => $reqId]);
			return $stmt->fetch();
		}
		
		public function listCompletedItems($user, $server){
			$stmt = $this->website->db('web')->prepare('SELECT serial FROM DmN_Battle_Pass_Completed_Requirements WHERE memb___id = :user AND server = :server AND serial <> \'\'');
			$stmt->execute([':user' => $user, ':server' => $server]);
			return $stmt->fetch_all();
		}
		
		
		public function setCompletedRequirement($user, $server, $date, $pass_level, $reqId, $serial = []){
			if(empty($serial)){
				$serial = '';
			}
			else{
				$serial = implode(',', $serial);
			}
			$stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Battle_Pass_Completed_Requirements (pass_level, req_id, memb___id, server, season, serial) VALUES (:pass_level, :req_id, :memb___id, :server, :season, :serial)');
			$stmt->execute([':pass_level' => $pass_level, ':req_id' => $reqId, ':memb___id' => $user, ':server' => $server, ':season' => md5($date), ':serial' => $serial]);
		}
		
		public function checkLevelStatus($user, $server, $date, $pass_level){
			$stmt = $this->website->db('web')->prepare('SELECT is_completed, is_free_reward_taken, is_silver_reward_taken, is_platinum_reward_taken FROM DmN_Battle_Pass_Progress WHERE memb___id = :user AND server = :server AND season = :season AND pass_level = :pass_level');
			$stmt->execute([':user' => $user, ':server' => $server, ':season' => md5($date), ':pass_level' => $pass_level]);
			return $stmt->fetch();
		}
		
		
		public function getRewardTypeData($type, $server){
			switch($type){
				case 1:
					return $this->website->translate_credits(1, $server);									
				break;
				case 2:
					return $this->website->translate_credits(2, $server);		
				break;
				case 3:
					return __('WCoins');
				break;
				case 4:
					return __('GoblinPoint');
				break;
				case 5:
					return __('Zen');
				break;
				case 6:
					return __('WebZen');
				break;
				case 7:
					return __('Ruud');
				break;
				case 8:
					return __('Vip');
				break;
				case 9:
				case 10:
					return __('Items');
				break;
				case 11:
					return __('Buff');
				break;
				case 12:
					return __('Mystery Key');
				break;
			}
		}
		
		public function load_char_list($account, $server){
			$stmt = $this->website->db('game', $server)->prepare('SELECT '.$this->website->get_char_id_col($server).' AS id, Name FROM Character WHERE AccountId = :account');
			$stmt->execute([':account' => $account]);
			$i = 0;
			$this->characters = [];
			while($row = $stmt->fetch()){
				$this->characters[] = [
					'id' => $row['id'], 
					'name' => $row['Name']
				];
				$i++;
			}
			if($i > 0){
				return $this->characters;
			} 
			else{
				return false;
			}
		}
		
		
		public function check_char($char, $account, $server, $byId = true){
			$check = ($byId == true) ? $this->website->get_char_id_col($server) : 'Name';
            $stmt = $this->website->db('game', $server)->prepare('SELECT Name FROM Character WHERE AccountId = :user AND '.$check.' = :char');
            $stmt->execute([':user' => $account, ':char' => $char]);
            return $stmt->fetch();
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
		
		
		public function addFreeSpin($amount, $user, $server){
			if($this->website->db('web')->check_if_table_exists('DmN_Wheel_Of_Fortune_FreeSpins')){
				if($this->checkFreeSpins($user, $server) == false){
					$stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Wheel_Of_Fortune_FreeSpins (memb___id, server, spins) VALUES (:memb___id, :server, :spins)');
					$stmt->execute([':memb___id' => $user, ':server' => $server, ':spins' => $amount]);
				}
				else{
					$stmt = $this->website->db('web')->prepare('UPDATE DmN_Wheel_Of_Fortune_FreeSpins SET spins = spins+ :spins WHERE memb___id = :memb___id AND server = :server');
					$stmt->execute([':spins' => $amount, ':memb___id' => $user, ':server' => $server]);
				}
			}
		}
		
		public function checkFreeSpins($user, $server){
			$stmt = $this->website->db('web')->prepare('SELECT id FROM DmN_Wheel_Of_Fortune_FreeSpins WHERE memb___id = :memb___id AND server = :server');
			$stmt->execute([':memb___id' => $user, ':server' => $server]);
			return $stmt->fetch();
		}
		
		public function check_votes($user, $guid, $server){
			$votes = [];
			$beginOfDay = strtotime("today", time());
			$endOfDay   = strtotime("tomorrow", $beginOfDay) - 1;
			$search = ' AND time BETWEEN '.$beginOfDay.' AND  '.$endOfDay.' AND validated = 1'; 
			$search2 = ' AND time BETWEEN '.$beginOfDay.' AND  '.$endOfDay;
			if($this->website->db('web')->check_if_table_exists('DmN_Votereward_Dmncms_Log')){
				$votes[] = $this->website->db('web')->query('SELECT COUNT(id) AS tr FROM DmN_Votereward_Dmncms_Log WHERE memb_guid = '.$this->website->db('web')->escape($guid).''.$search)->fetch()['tr'];
			}	
			if($this->website->db('web')->check_if_table_exists('DmN_Votereward_Gtop_Log')){
				$votes[] = $this->website->db('web')->query('SELECT COUNT(id) AS tr FROM DmN_Votereward_Gtop_Log WHERE memb_guid = '.$this->website->db('web')->escape($guid).''.$search)->fetch()['tr'];
			}
			if($this->website->db('web')->check_if_table_exists('DmN_Votereward_Log')){
				$votes[] = $this->website->db('web')->query('SELECT COUNT(id) AS tr FROM DmN_Votereward_Log WHERE account = '.$this->website->db('web')->escape($user).''.$search2)->fetch()['tr'];
			}
			if($this->website->db('web')->check_if_table_exists('DmN_Votereward_Mmoserver_Log')){
				$votes[] = $this->website->db('web')->query('SELECT COUNT(id) AS tr FROM DmN_Votereward_Mmoserver_Log WHERE memb_guid = '.$this->website->db('web')->escape($guid).''.$search)->fetch()['tr'];
			}
			if($this->website->db('web')->check_if_table_exists('DmN_Votereward_Top100arena_Log')){
				$votes[] = $this->website->db('web')->query('SELECT COUNT(id) AS tr FROM DmN_Votereward_Top100arena_Log WHERE memb_guid = '.$this->website->db('web')->escape($guid).''.$search)->fetch()['tr'];
			}
			if($this->website->db('web')->check_if_table_exists('DmN_Votereward_Topg_Log')){
				$votes[] = $this->website->db('web')->query('SELECT COUNT(id) AS tr FROM DmN_Votereward_Topg_Log WHERE memb_guid = '.$this->website->db('web')->escape($guid).''.$search)->fetch()['tr'];
			}
			if($this->website->db('web')->check_if_table_exists('DmN_Votereward_Xtremetop_Log')){
				$votes[] = $this->website->db('web')->query('SELECT COUNT(id) AS tr FROM DmN_Votereward_Xtremetop_Log WHERE memb_guid = '.$this->website->db('web')->escape($guid).''.$search)->fetch()['tr'];
			}
			return array_sum($votes);
		}
				
		public function check_votes_specific($user, $guid, $server, $type){
			$votes = [];
			$beginOfDay = strtotime("today", time());
			$endOfDay   = strtotime("tomorrow", $beginOfDay) - 1;
			$search = ' AND time BETWEEN '.$beginOfDay.' AND  '.$endOfDay.' AND validated = 1'; 
			$search2 = ' AND time BETWEEN '.$beginOfDay.' AND  '.$endOfDay;
			switch($type){
				case 1:
					if($this->website->db('web')->check_if_table_exists('DmN_Votereward_Xtremetop_Log')){
						$votes[] = $this->website->db('web')->query('SELECT COUNT(id) AS tr FROM DmN_Votereward_Xtremetop_Log WHERE memb_guid = '.$this->website->db('web')->escape($guid).''.$search)->fetch()['tr'];
					}
				break;
				case 2:
					if($this->website->db('web')->check_if_table_exists('DmN_Votereward_Gtop_Log')){
						$votes[] = $this->website->db('web')->query('SELECT COUNT(id) AS tr FROM DmN_Votereward_Gtop_Log WHERE memb_guid = '.$this->website->db('web')->escape($guid).''.$search)->fetch()['tr'];
					}
				break;
				case 3:
					if($this->website->db('web')->check_if_table_exists('DmN_Votereward_Topg_Log')){
						$votes[] = $this->website->db('web')->query('SELECT COUNT(id) AS tr FROM DmN_Votereward_Topg_Log WHERE memb_guid = '.$this->website->db('web')->escape($guid).''.$search)->fetch()['tr'];
					}
				break;
				case 4:
					if($this->website->db('web')->check_if_table_exists('DmN_Votereward_Top100arena_Log')){
						$votes[] = $this->website->db('web')->query('SELECT COUNT(id) AS tr FROM DmN_Votereward_Top100arena_Log WHERE memb_guid = '.$this->website->db('web')->escape($guid).''.$search)->fetch()['tr'];
					}
				break;
				case 5:
					if($this->website->db('web')->check_if_table_exists('DmN_Votereward_Mmoserver_Log')){
						$votes[] = $this->website->db('web')->query('SELECT COUNT(id) AS tr FROM DmN_Votereward_Mmoserver_Log WHERE memb_guid = '.$this->website->db('web')->escape($guid).''.$search)->fetch()['tr'];
					}
				break;
				case 6:
					if($this->website->db('web')->check_if_table_exists('DmN_Votereward_Dmncms_Log')){
						$votes[] = $this->website->db('web')->query('SELECT COUNT(id) AS tr FROM DmN_Votereward_Dmncms_Log WHERE memb_guid = '.$this->website->db('web')->escape($guid).''.$search)->fetch()['tr'];
					}
				break;
				
			}
			
			return array_sum($votes);
		}
	
		public function check_donations($user, $guid, $server){
			$transactions = [];
			$beginOfDay = strtotime("today", time());
			$endOfDay   = strtotime("tomorrow", $beginOfDay) - 1;
			$search = ' AND order_date BETWEEN '.$beginOfDay.' AND  '.$endOfDay; 
			if($this->website->db('web')->check_if_table_exists('DmN_Donate_CoinBase_Transactions')){
				$transactions[] = $this->website->db('web')->query('SELECT COUNT(id) AS tr FROM DmN_Donate_CoinBase_Transactions WHERE acc = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).''.$search)->fetch()['tr'];
			}	
			if($this->website->db('web')->check_if_table_exists('DmN_Donate_CuentaDigital_Transactions')){
				$transactions[] = $this->website->db('web')->query('SELECT COUNT(id) AS tr FROM DmN_Donate_CuentaDigital_Transactions WHERE acc = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).''.$search)->fetch()['tr'];
			}
			//if($this->website->db('web')->check_if_table_exists('DmN_Donate_Fortumo')){
			//	$transactions[] = $this->website->db('web')->query('SELECT COUNT(id) AS tr FROM DmN_Donate_Fortumo WHERE account = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).'')->fetch()['tr'];
			//}
			if($this->website->db('web')->check_if_table_exists('DmN_Donate_Interkassa_Transactions')){
				$transactions[] = $this->website->db('web')->query('SELECT COUNT(id) AS tr FROM DmN_Donate_Interkassa_Transactions WHERE acc = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).''.$search)->fetch()['tr'];
			}
			if($this->website->db('web')->check_if_table_exists('DmN_Donate_MercadoPago_Transactions')){
				$transactions[] = $this->website->db('web')->query('SELECT COUNT(id) AS tr FROM DmN_Donate_MercadoPago_Transactions WHERE acc = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).''.$search)->fetch()['tr'];
			}
			if($this->website->db('web')->check_if_table_exists('DmN_Donate_NganLuong_Transactions')){
				$transactions[] = $this->website->db('web')->query('SELECT COUNT(id) AS tr FROM DmN_Donate_NganLuong_Transactions WHERE acc = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).''.$search)->fetch()['tr'];
			}
			if($this->website->db('web')->check_if_table_exists('DmN_Donate_Paddle_Transactions')){
				$transactions[] = $this->website->db('web')->query('SELECT COUNT(id) AS tr FROM DmN_Donate_Paddle_Transactions WHERE acc = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).''.$search)->fetch()['tr'];
			}
			if($this->website->db('web')->check_if_table_exists('DmN_Donate_PayCall_Transactions')){
				$transactions[] = $this->website->db('web')->query('SELECT COUNT(id) AS tr FROM DmN_Donate_PayCall_Transactions WHERE acc = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).''.$search)->fetch()['tr'];
			}
			if($this->website->db('web')->check_if_table_exists('DmN_Donate_Payeer_Transactions')){
				$transactions[] = $this->website->db('web')->query('SELECT COUNT(id) AS tr FROM DmN_Donate_Payeer_Transactions WHERE acc = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).''.$search)->fetch()['tr'];
			}
			if($this->website->db('web')->check_if_table_exists('DmN_Donate_Payssion_Transactions')){
				$transactions[] = $this->website->db('web')->query('SELECT COUNT(id) AS tr FROM DmN_Donate_Payssion_Transactions WHERE acc = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).''.$search)->fetch()['tr'];
			}
			if($this->website->db('web')->check_if_table_exists('DmN_Donate_PayU_Transactions')){
				$transactions[] = $this->website->db('web')->query('SELECT COUNT(id) AS tr FROM DmN_Donate_PayU_Transactions WHERE acc = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).''.$search)->fetch()['tr'];
			}
			if($this->website->db('web')->check_if_table_exists('DmN_Donate_Stripe_Transactions')){
				$transactions[] = $this->website->db('web')->query('SELECT COUNT(id) AS tr FROM DmN_Donate_Stripe_Transactions WHERE acc = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).''.$search)->fetch()['tr'];
			}
			if($this->website->db('web')->check_if_table_exists('DmN_Donate_Transactions')){
				$transactions[] = $this->website->db('web')->query('SELECT COUNT(id) AS tr FROM DmN_Donate_Transactions WHERE acc = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).' AND status = \'Completed\''.$search)->fetch()['tr'];
			}
			if($this->website->db('web')->check_if_table_exists('DmN_Donate_UnitPay_Transactions')){
				$transactions[] = $this->website->db('web')->query('SELECT COUNT(id) AS tr FROM DmN_Donate_UnitPay_Transactions WHERE acc = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).''.$search)->fetch()['tr'];
			}
			if($this->website->db('web')->check_if_table_exists('DmN_Donate_WalletOne_Transactions')){
				$transactions[] = $this->website->db('web')->query('SELECT COUNT(id) AS tr FROM DmN_Donate_WalletOne_Transactions WHERE acc = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).''.$search)->fetch()['tr'];
			}
			if($this->website->db('web')->check_if_table_exists('DmN_Donate_Gerencianet_Transactions')){
				$transactions[] = $this->website->db('web')->query('SELECT COUNT(id) AS tr FROM DmN_Donate_Gerencianet_Transactions WHERE acc = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).''.$search)->fetch()['tr'];
			}
			return array_sum($transactions);
		}
				
		public function check_event_entry_count($user, $guid, $server, $type){
			$beginOfDay = strtotime("today", time());
			$endOfDay   = strtotime("tomorrow", $beginOfDay) - 1;
			
			$characters = $this->load_char_list($user, $server);
			if($characters != false){
				$charNames = [];
				$event = '';
				switch($type){
					case 1:
						$event = 'BloodCastle';
					break;
					case 2:
						$event = 'ChaosCastle';
					break;
					case 3:
						$event = 'DevilSquare';
					break;
					case 4:
						$event = 'DoppelGanger';
					break;
					case 5:
						$event = 'ImperialGuardian';
					break;
					case 6:
						$event = 'IllusionTempleRenewal';
					break;
				}
				foreach($characters AS $id => $cdata){
					$charNames[] = $this->website->db('game', $server)->escape($cdata['name']);
				}
				if($this->website->db('game', $server)->check_if_table_exists('IGC_EventMapEnterLimit')){
					$search = ' AND LastDate BETWEEN '.$this->website->db('web')->escape(date('Y-m-d H:i:s', $beginOfDay)).' AND  '.$this->website->db('web')->escape(date('Y-m-d H:i:s', $endOfDay)); 
					return $this->website->db('game', $server)->query('SELECT SUM('.$event.') AS count FROM IGC_EventMapEnterLimit WHERE CharacterName IN ('.implode(',', $charNames).') '.$search)->fetch()['count'];
				}
			}
			return 0;
		}
		
		public function check_online_time($user, $guid, $server){ 
			return $this->website->db('web')->query('SELECT SUM(TotalTime) AS OnlineMinutes FROM DmN_OnlineCheck WHERE memb___id = '.$this->website->db('web')->escape($user).' ' . $this->website->server_code($this->website->get_servercode($server)) . ' AND date = \''.date('Y-m-d', time()).'\'')->fetch()['OnlineMinutes'];
		}
			
		public function check_monsters($user, $guid, $server, $monster = -1){
			$characters = $this->load_char_list($user, $server);
			if($characters != false){
				$charNames = [];
				foreach($characters AS $id => $cdata){
					$charNames[] = $this->website->db('game', $server)->escape($cdata['name']);
				}
				if($this->website->db('game', $server)->check_if_table_exists('MonsterKillCount')){
					return $this->website->db('game', $server)->query('SELECT SUM(KillCount) AS count FROM MonsterKillCount WHERE Name IN ('.implode(',', $charNames).') AND MonsterClass = '.$monster)->fetch()['count'];
				}
				else{
					if($this->website->db('game', $server)->check_if_table_exists('CustomMonsterRanking')){
						switch($monster){
                            case 43:
                                $column = 'GoldenBudgeDragon';
                            break;
                            case 53:
                                $column = 'GoldenTitan';
                            break;
                            case 54:
                                $column = 'GoldenSoldier';
                            break;
                            case 78:
                                $column = 'GoldenGoblin';
                            break;
                            case 79:
                                $column = 'GoldenDerkon';
                            break;
							case 80:
                                $column = 'GoldenLizard';
                            break;
							case 81:
                                $column = 'GoldenVepar';
                            break;
							case 82:
                                $column = 'GoldenTantalos';
                            break;
							case 83:
                                $column = 'GoldenWheel';
                            break;
							case 493:
                                $column = 'GoldenDarkKnight';
                            break;
							case 494:
                                $column = 'GoldenDevil';
                            break;
							case 495:
                                $column = 'GoldenStoneGolem';
                            break;
							case 496:
                                $column = 'GoldenCrust';
                            break;
							case 497:
                                $column = 'GoldenSatyros';
                            break;
							case 498:
                                $column = 'GoldenTwinTail';
                            break;
							case 499:
                                $column = 'GoldenIronKnight';
                            break;
							case 500:
                                $column = 'GoldenNapin';
                            break;
							case 501:
                                $column = 'GGD';
                            break;
							case 502:
                                $column = 'GoldenRabbit';
                            break;
							case 18:
                                $column = 'Gorgon';
                            break;
							case 25:
                                $column = 'IceQueen';
                            break;
							case 38:
                                $column = 'Balrog';
                            break;
							case 42:
                                $column = 'RedDragon';
                            break;
							case 701:
                                $column = 'EnragedRedDragon';
                            break;
							case 49:
                                $column = 'Hydra';
                            break;
							case 55:
                                $column = 'SkeletonKing';
                            break;
							case 67:
                                $column = 'MetalBalrog';
                            break;
							case 135:
                                $column = 'WhiteWizard';
                            break;
							case 413:
                                $column = 'LunarRabbit';
                            break;
							case 562:
                                $column = 'DarkMammoth';
                            break;
							case 563:
                                $column = 'DarkGiant';
                            break;
							case 564:
                                $column = 'DarkCoolutin';
                            break;
							case 565:
                                $column = 'DarkIronKnight';
                            break;
							case 727:
                                $column = 'Bismut';
                            break;
							case 728:
                                $column = 'Slardar';
                            break;
							case 729:
                                $column = 'Naga';
                            break;
							case 618:
                                $column = 'ArielTheSeaQueen';
                            break;
							case 598:
                                $column = 'Papito';
                            break;
							case 599:
                                $column = 'Potrisku';
                            break;
							case 519:
                                $column = 'BloodKnight';
                            break;
							case 593:
                                $column = 'Tiamat';
                            break;
							case 688:
                                $column = 'DevilFairy';
                            break;
							case 692:
                                $column = 'Succubus';
                            break;
							case 595:
                                $column = 'BroodMother';
                            break;
							case 601:
                                $column = 'Spinarak';
                            break;
							case 602:
                                $column = 'Bau';
                            break;
							case 622:
                                $column = 'SpiderEggs';
                            break;
							case 459:
                                $column = 'Selupan';
                            break;
							case 275:
                                $column = 'Kundun';
                            break;
							case 561:
                                $column = 'Medusa';
                            break;
                            default:
                                $column = '';
                            break;
                        }
						if($column != ''){
							return $this->website->db('game', $server)->query('SELECT SUM('.$column.') AS count FROM CustomMonsterRanking WHERE Name IN ('.implode(',', $charNames).')')->fetch()['count'];
						}
					}
					else{
						if($this->website->db('game', $server)->check_if_table_exists('C_Monster_KillCount')){
							return $this->website->db('game', $server)->query('SELECT SUM(count) AS count FROM C_Monster_KillCount WHERE name IN ('.implode(',', $charNames).') AND MonsterId = '.$monster)->fetch()['count'];
						}
					}
				}
			}
			return 0;
		}
				
		public function check_kills($user, $guid, $server, $unique = 0, $minRes = 0){
			$characters = $this->load_char_list($user, $server);
			if($characters != false){
				$charNames = [];
				foreach($characters AS $id => $cdata){
					$charNames[] = $this->website->db('game', $server)->escape($cdata['name']);
				}
				if($this->website->db('game', $server)->check_if_table_exists('C_PlayerKiller_Info')){
					if($unique == 1){
						$data = $this->website->db('game', $server)->query('SELECT DISTINCT kk.Killer, kk.Victim, kk.KillDate, c.Name, ' . $this->reset_column($server).' c.AccountId FROM C_PlayerKiller_Info AS kk LEFT JOIN Character AS c ON (kk.Victim Collate Database_Default = c.Name Collate Database_Default) WHERE kk.Killer IN ('.implode(',', $charNames).') AND isChecked = 0')->fetch_all();
						
						$newData = [];
						if($minRes > 0){
							foreach($data AS $info){
								if($info['resets'] >= $minRes){
									$newData[] = $info;
								}								
							}
						}
						$result = array_reverse(array_values(array_column(
							array_reverse($newData),
							null,
							'AccountId'
						)));
						return $result;
					}
					else{
						return $this->website->db('game', $server)->query('SELECT COUNT(Victim) AS count FROM C_PlayerKiller_Info WHERE Killer IN ('.implode(',', $charNames).')')->fetch()['count'];
					}
				}
				else{
					return $this->website->db('game', $server)->query('SELECT dmn_pk_count AS count FROM Character WHERE Name IN ('.implode(',', $charNames).')')->fetch()['count'];
				}
			}
			return 0;
		}
		
		public function setKillsChecked($victim, $killer, $date, $server){
			$this->website->db('game', $server)->query('UPDATE C_PlayerKiller_Info SET isChecked = 1 WHERE Victim = \''.$victim.'\' AND Killer = \''.$killer.'\' AND KillDate = \''.$date.'\'');
		}
		
		public function check_resets($user, $guid, $server){
			return $this->website->db('web')->query('SELECT SUM(resets) AS resets FROM DmN_Character_Reset_Log WHERE account = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).' AND date = \''.date('Y-m-d', time()).'\'')->fetch()['resets'];
		}
		
		public function check_gresets($user, $guid, $server){
			return $this->website->db('web')->query('SELECT SUM(resets) AS resets FROM DmN_Character_gReset_Log WHERE account = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).' AND date = \''.date('Y-m-d', time()).'\'')->fetch()['resets'];
		}
		
		public function check_shop($user, $guid, $server){
			$beginOfDay = strtotime("today", time());
			$endOfDay   = strtotime("tomorrow", $beginOfDay) - 1;
			$search = ' AND date BETWEEN '.$this->website->db('web')->escape(date('Y-m-d H:i:s', $beginOfDay)).' AND  '.$this->website->db('web')->escape(date('Y-m-d H:i:s', $endOfDay)); 
			return $this->website->db('web')->query('SELECT COUNT(memb___id) AS tr FROM DmN_Shop_Logs WHERE memb___id = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).''.$search)->fetch()['tr'];
		}
		
		public function check_market($user, $guid, $server){
			$beginOfDay = strtotime("today", time());
			$endOfDay   = strtotime("tomorrow", $beginOfDay) - 1;
			$search = ' AND sold_date BETWEEN '.$this->website->db('web')->escape(date('Y-m-d H:i:s', $beginOfDay)).' AND  '.$this->website->db('web')->escape(date('Y-m-d H:i:s', $endOfDay)); 
			return $this->website->db('web')->query('SELECT COUNT(DISTINCT buyer) AS tr FROM DmN_Market_Logs WHERE seller = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).''.$search)->fetch()['tr'];
		}
		
		public function inventory($user, $server){
			$characters = $this->load_char_list($user, $server);
			
			if($characters != false){
				$charData = [];
				foreach($characters AS $char){
					$stmt = $this->website->db('game', $server)->prepare('SELECT CONVERT(IMAGE, Inventory) AS Inventory FROM Character WHERE '.$this->website->get_char_id_col($server).' = :char');
					$stmt->execute([':char' => $char['id']]);
					if($inv = $stmt->fetch()){
						$unpack = unpack('H*', $inv['Inventory']);
						$charData[$char['id']] = $this->website->clean_hex($unpack[1]);
					}
				}
				return $charData;
			}
			return false;
        }
			
		public function inventory2($char, $server){
			$stmt = $this->website->db('game', $server)->prepare('SELECT CONVERT(IMAGE, Inventory) AS Inventory FROM Character WHERE '.$this->website->get_char_id_col($server).' = :char');
			$stmt->execute([':char' => $char]);
			if($inv = $stmt->fetch()){
				$unpack = unpack('H*', $inv['Inventory']);
				$this->char_info['Inventory'] = $this->website->clean_hex($unpack[1]);
			}  
        }
		
		public function getInventoryContents($server, $inv = false){
			$this->char_info['Inventory'] = ($inv == false) ? $this->char_info['Inventory'] : $inv;
			$items = [];
			$items_array = str_split($this->char_info['Inventory'], $this->website->get_value_from_server($server, 'item_size'));
			for($a = 12; $a < 76; $a++){
				$items[$a] = $items_array[$a];
			}
			return $items;	
		}	
				
		public function updateInventorySlots($slots, $server, $inv = false){
			$this->char_info['Inventory'] = ($inv == false) ? $this->char_info['Inventory'] : $inv;
			$items_array = str_split($this->char_info['Inventory'], $this->website->get_value_from_server($server, 'item_size'));
			for($a = 12; $a < 76; $a++){
				if(in_array($a, $slots)){
					$items_array[$a] = str_repeat('F', $this->website->get_value_from_server($server, 'item_size'));
				}
			}
			return $items_array;
		}
				
		public function addItemsToInventory($items, $server, $inv = false){
			$this->char_info['Inventory'] = ($inv == false) ? $this->char_info['Inventory'] : $inv;
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
		
		public function add_wcoins($user, $server, $amount = 0, $config = []){
            $stmt = $this->website->db($config['db'], $server)->prepare('UPDATE ' . $config['table'] . ' SET ' . $config['column'] . ' = ' . $config['column'] . ' + :wcoins WHERE ' . $config['identifier_column'] . ' = :account');
            $stmt->execute([':wcoins' => $amount, ':account' => $user]);
            if($stmt->rows_affected() == 0){
                $stmt = $this->website->db($config['db'], $server)->prepare('INSERT INTO ' . $config['table'] . ' (' . $config['identifier_column'] . ', ' . $config['column'] . ') values (:user, :wcoins)');
                $stmt->execute([':user' => $user, ':wcoins' => $amount]);
            }
        }

        public function get_wcoins($user, $server, $config = []){
            $stmt = $this->website->db($config['db'], $server)->prepare('SELECT ' . $config['column'] . ' FROM ' . $config['table'] . ' WHERE ' . $config['identifier_column'] . ' = :account');
            $stmt->execute([':account' => $user]);
            if($wcoins = $stmt->fetch()){
                return $wcoins[$config['column']];
            }
            return false;
        }
		
		public function get_vip_package_title($vip_type = 1){
			return $this->website->db('web')->query('SELECT package_title, vip_time FROM DmN_Vip_Packages WHERE id = '.$this->website->db('web')->escape($vip_type).'')->fetch();
        }
		
		public function log_reward($rid, $pass_type, $cid, $user, $server){
			$stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Battle_Pass_Rewards_Log (reward_key, pass_type, memb___id, server, char_id, claim_date) VALUES (:rid, :pass_type, :memb___id, :server, :cid, :claim_date)');
			$stmt->execute([
				':rid' => $rid,
				':pass_type' => $pass_type,
				':memb___id' => $user,
				':server' => $server,
				':cid' => $cid,
				':claim_date' => time()
			]);
		}

        /**
         * Check if account is connected to game
         *
         * @param string $account
         * @param string $server
         *
         * @return bool
         */
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
