<?php
   // in_file();

    class Machievements extends model
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
		
		
		public function achivementTypeToReadable($type){
			switch($type){
				case 0:
					return __('Do Nothing');
				break;
				case 1:
					return __('Collect Zen');
				break;
				case 2:
					return __('Collect Ruud');
				break;
				case 3:
					return __('Collect WCoins');
				break;
				case 4:
					return __('Collect GoblinPoints');
				break;
				case 5:
					return __('Vote');
				break;
				case 6:
					return __('Donate');
				break;
				case 7:
					return __('Kill Monsters');
				break;
				case 8:
					return __('Kill Players');
				break;
				case 9:
					return __('Collect Items');
				break;
				case 10:
					return __('Level');
				break;
				case 11:
					return __('Master Level');
				break;
				case 12:
					return __('Reset');
				break;
				case 13:
					return __('Grand Reset');
				break;
				case 14:
					return __('Refer a friend');
				break;
				case 15:
					return __('BloodCastle');
				break;
				case 16:
					return __('DevilSquare');
				break;
				case 17:
					return __('ChaosCastle');
				break;
				case 18:
					return __('IllusionTemple');
				break;
				case 19:
					return __('Duels');
				break;
				case 20:
					return __('Gens');
				break;
				case 21:
					return __('Buy Shop Item');
				break;
				case 22:
					return __('Sell Market Item');
				break;
				case 22:
					return __('Sell Market Item');
				break;
				case 23:
					return __('Complete Achievements');
				break;
				case 24:
					return __('Maxed Out');
				break;
				case 25:
					return __('Online Time');
				break;
			}
		}
		
		
		public function achivementTypeToReadableLong($type){
			switch($type){
				case 0:
					return __('Do Nothing');
				break;
				case 1:
					return __('Collect Zen');
				break;
				case 2:
					return __('Collect Ruud');
				break;
				case 3:
					return __('Collect WCoins');
				break;
				case 4:
					return __('Collect GoblinPoints');
				break;
				case 5:
					return __('Vote for Server');
				break;
				case 6:
					return __('Complete Donations');
				break;
				case 7:
					return __('Kill Monsters');
				break;
				case 8:
					return __('Kill Players');
				break;
				case 9:
					return __('Collect Items');
				break;
				case 10:
					return __('Upgrade Level');
				break;
				case 11:
					return __('Upgrade Master Level');
				break;
				case 12:
					return __('Reset Character');
				break;
				case 13:
					return __('Grand Reset Character');
				break;
				case 14:
					return __('Refer friend to server');
				break;
				case 15:
					return __('Get Score in BloodCastle');
				break;
				case 16:
					return __('Get Score in DevilSquare');
				break;
				case 17:
					return __('Win in ChaosCastle');
				break;
				case 18:
					return __('Kill players in IllusionTemple');
				break;
				case 19:
					return __('Win Duels');
				break;
				case 20:
					return __('Get Gens Contribution');
				break;
				case 21:
					return __('Buy Shop Item');
				break;
				case 22:
					return __('Sell Market Item');
				break;
				case 23:
					return __('Complete Achievements');
				break;
				case 24:
					return __('Maxed Out');
				break;
				case 25:
					return __('Spend Time In Game');
				break;
			}
		}
		
		public function checkUnlocked($id, $server){
			$stmt = $this->website->db('web')->prepare('SELECT id FROM DmN_Unlocked_Achievements WHERE char_id = :id AND server = :server');
			$stmt->execute([':id' => $id, ':server' => $server]);
			return $stmt->fetch();
		}
		
		public function load_char_list($account, $server){
			$stmt = $this->website->db('game', $server)->prepare('SELECT '.$this->website->get_char_id_col($server).' AS id, Name, ' . $this->reset_column($server) . $this->greset_column($server) . ' cLevel, Class FROM Character WHERE AccountId = :account');
			$stmt->execute([':account' => $account]);
			$i = 0;
			while($row = $stmt->fetch()){
				$this->characters[] = [
					'id' => $row['id'], 
					'name' => $row['Name'], 
					'level' => $row['cLevel'], 
					'resets' => $row['resets'], 
					'gresets' => $row['grand_resets'],
					'class' => $row['Class']
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
            $stmt = $this->website->db('game', $server)->prepare('SELECT Name, Money, Class, cLevel, ' . $this->reset_column($server) . $this->greset_column($server) . ' '.$this->website->get_char_id_col($server).' AS id '.$ruud.' FROM Character WHERE ' . $identifier . ' = :char');
            $stmt->execute([':char' => $char]);
            if($this->char_info = $stmt->fetch()){
                $this->char_info['mlevel'] = $this->load_master_level($this->char_info['Name'], $server); 
            }
        }
		
		public function unlockAchievements($id, $server){
			$stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Unlocked_Achievements (char_id, server)  VALUES (:char_id, :server)');
			$stmt->execute([':char_id' => $id, ':server' => $server]);
		}
		
		public function setCompleted($charId, $achId, $user, $server){
			$stmt = $this->website->db('web')->prepare('UPDATE DmN_User_Achievements set is_completed = 1 WHERE ach_id = :ach_id AND memb___id = :memb___id AND server = :server AND char_id = :char_id');
			$stmt->execute([
				':ach_id' => $achId,
				':memb___id' => $user,
				':server' => $server,
				':char_id' => $charId
			]);
		}

				
		public function updateCompletedAmount($charId, $achId, $user, $server, $amount){
			$stmt = $this->website->db('web')->prepare('UPDATE DmN_User_Achievements set amount_completed = :amount_completed, last_updated = :last_updated WHERE ach_id = :ach_id AND memb___id = :memb___id AND server = :server AND char_id = :char_id');
			$stmt->execute([
				':amount_completed' => $amount,
				':last_updated' => time(),
				':ach_id' => $achId,
				':memb___id' => $user,
				':server' => $server,
				':char_id' => $charId
			]);
		}
		
		public function updateUserAchievementItems($achId, $charId, $user, $server, $items){
			$stmt = $this->website->db('web')->prepare('UPDATE DmN_User_Achievements SET items = :items, last_updated = :last_updated WHERE ach_id = :ach_id AND memb___id = :memb___id AND server = :server AND char_id = :char_id');
			$stmt->execute([
				':items' => json_encode($items),
				':last_updated' => time(),
				':ach_id' => $achId,
				':memb___id' => $user,
				':server' => $server,
				':char_id' => $charId
			]);
		}
		
		
		public function get_rankings($server, $amount, $cache){
			$cache_name = 'achievements#' . $server . '#' . $amount;
            $this->website->check_cache($cache_name, 'achievements', $cache);
			 if(!$this->website->cached){
				 $query = $this->website->db('web')->query('SELECT TOP '.$amount.' char_id, ranking_points, achievements_completed FROM DmN_Unlocked_Achievements WHERE achievements_completed > 0  AND server = '.$this->website->db('web')->escape($server).' ORDER BY ranking_points DESC, last_updated DESC');
				 if($query){
                    $i = 0;
                    while($row = $query->fetch()){
						$this->achievements[] = [
							'char_id' => $row['char_id'],
							'ranking_points' => $row['ranking_points'],
							'achievements_completed' => $row['achievements_completed'],
						];
						$i++;
					}
					if(!empty($this->achievements)){
						foreach($this->achievements AS $key => $ach){
							$this->achievements[$key]['achievements_total'] = $this->countTotalAchievements($ach['char_id'], $server);
							$this->char_info($ach['char_id'], $server, true);
							$this->achievements[$key]['char_data'] = $this->char_info;
						}
					}
					if($i > 0){
                        $this->website->set_cache($cache_name, $this->achievements, $cache);
                        return $this->achievements;
                    }
				 }
				 return false;
			 }
			 return $this->website->achievements;
		}

				
		private function countTotalAchievements($id, $server){
			return $this->website->db('web')->snumrows('SELECT COUNT(id) AS count FROM DmN_User_Achievements WHERE char_id = '.$this->website->db('web')->escape($id).' AND server = '.$this->website->db('web')->escape($server).'');
		}

				
		public function getRankingScore($charId, $server){
			$stmt = $this->website->db('web')->prepare('SELECT ranking_points FROM DmN_Unlocked_Achievements WHERE char_id = :id AND server = :server');
			$stmt->execute([
				':id' => $charId, 
				':server' => $server
			]);
			return $stmt->fetch();
		}
		
		public function addRankingScore($charId, $server, $score){
			$stmt = $this->website->db('web')->prepare('UPDATE DmN_Unlocked_Achievements SET ranking_points = ranking_points + :ranking_points, achievements_completed = achievements_completed + 1, last_updated = :time WHERE char_id = :id AND server = :server');
			$stmt->execute([
				':ranking_points' => $score,
				':time' => time(),
				':id' => $charId, 
				':server' => $server
			]);
		}
		
		
		public function checkAchievementStatus($achievementData, $id, $user, $server){
			$status = $this->checkUserAchievement($achievementData['id'], $id, $user, $server);
			if($status == false){
				$this->insertUserAchievement($achievementData, $id, $user, $server);
				$status = $this->checkUserAchievement($achievementData['id'], $id, $user, $server);
			}
			return $status;
		}
		
		
		public function checkUserAchievement($achId, $charId, $user, $server){
			$stmt = $this->website->db('web')->prepare('SELECT ach_type, amount, amount_completed, items, is_completed FROM DmN_User_Achievements WHERE ach_id = :ach_id AND memb___id = :memb___id AND server = :server AND char_id = :char_id');
			$stmt->execute([
				':ach_id' => $achId,
				':memb___id' => $user,
				':server' => $server,
				':char_id' => $charId
			]);
			return $stmt->fetch();
		}
		
		
		public function checkClaimedReward($achId, $charId, $user, $server, $type){
			$stmt = $this->website->db('web')->prepare('SELECT '.$type.' AS reward FROM DmN_Claimed_Achievement_Reward WHERE ach_id = :ach_id AND memb___id = :memb___id AND server = :server AND char_id = :char_id');
			$stmt->execute([
				':ach_id' => $achId,
				':memb___id' => $user,
				':server' => $server,
				':char_id' => $charId
			]);
			return $stmt->fetch();
		}
		
		public function insertRewardData($achId, $charId, $user, $server){
			$stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Claimed_Achievement_Reward (ach_id, memb___id, server, char_id) VALUES (:ach_id, :memb___id, :server, :char_id)');
			$stmt->execute([
				':ach_id' => $achId,
				':memb___id' => $user,
				':server' => $server,
				':char_id' => $charId
			]);
		}
		
		public function setClaimedReward($achId, $charId, $user, $server, $type){
			$stmt = $this->website->db('web')->prepare('UPDATE DmN_Claimed_Achievement_Reward SET '.$type.' = 1 WHERE ach_id = :ach_id AND memb___id = :memb___id AND server = :server AND char_id = :char_id');
			$stmt->execute([
				':ach_id' => $achId,
				':memb___id' => $user,
				':server' => $server,
				':char_id' => $charId
			]);
		}
		
		
		public function insertUserAchievement($achData, $charId, $user, $server){
			$amount = $achData['amount'];
			if(in_array($achData['achievement_type'], [5])){
				$amount = $achData['total_votes'];
			}
			if(in_array($achData['achievement_type'], [6])){
				$amount = $achData['total_donate'];
			}
			if(in_array($achData['achievement_type'], [7])){
				$amount = $achData['mamount'];
			}
			if(in_array($achData['achievement_type'], [8,18])){
				$amount = $achData['total_kills'];
			}
			if(in_array($achData['achievement_type'], [10,11,12,13,23,25])){
				$amount = $achData['total_stats'];
			}
			if(in_array($achData['achievement_type'], [14])){
				$amount = $achData['total_ref'];
			}
			if(in_array($achData['achievement_type'], [15,16])){
				$amount = $achData['total_score'];
			}
			if(in_array($achData['achievement_type'], [17, 19])){
				$amount = $achData['total_wins'];
			}
			if(in_array($achData['achievement_type'], [20])){
				$amount = $achData['total_contr'];
			}
			if(in_array($achData['achievement_type'], [21])){
				$amount = $achData['total_items_buy'];
			}
			if(in_array($achData['achievement_type'], [22])){
				$amount = $achData['total_items_sell'];
			}
			if(in_array($achData['achievement_type'], [24])){
				$amount = 1;
				$achData['items'] = implode('|', [$achData['total_level'], $achData['total_mlevel'], $achData['total_res'], $achData['total_gres']]);
			}
			$stmt = $this->website->db('web')->prepare('INSERT INTO DmN_User_Achievements (ach_id, memb___id, server, char_id, ach_type, amount, items, last_updated) VALUES (:ach_id, :memb___id, :server, :char_id, :ach_type, :amount, :items, :last_updated)');
			$stmt->execute([
				':ach_id' => $achData['id'],
				':memb___id' => $user,
				':server' => $server,
				':char_id' => $charId,
				':ach_type' => $achData['achievement_type'],
				':amount' => $amount,
				':items' => json_encode($achData['items']),
				':last_updated' => time()
			]);
		}
		
		
		public function removeUserAchievement($id, $server){
			$this->decreaseRanks($id, $server);
			
			$stmt = $this->website->db('web')->prepare('DELETE FROM DmN_User_Achievements WHERE ach_id = :id AND server = :server');
			$stmt->execute([
				':id' => $id,
				':server' => $server
			]);
		}
		
		
		private function decreaseRanks($id, $server){
			$stmt = $this->website->db('web')->prepare('SELECT char_id, is_completed FROM DmN_User_Achievements WHERE ach_id = :id AND server = :server');
			$stmt->execute([
				':id' => $id,
				':server' => $server
			]);
			$userData = $stmt->fetch_all();
			if(!empty($userData)){
				$dataForDecrease = [];
				foreach($userData AS $userInfo){
					if($userInfo['is_completed'] == 1){
						if(isset($dataForDecrease[$userInfo['char_id']])){
							$dataForDecrease[$userInfo['char_id']] += 1;
						}
						else{
							$dataForDecrease[$userInfo['char_id']] = 1;
						}
					}
				}
				if(!empty($dataForDecrease)){
					foreach($dataForDecrease AS $char => $amount){
						$this->website->db('web')->query('UPDATE DmN_Unlocked_Achievements SET achievements_completed = achievements_completed - '.$this->website->db('web')->escape($amount).' WHERE char_id = '.$this->website->db('web')->escape($char).' AND server = '.$this->website->db('web')->escape($server).'');
					}
				}
			}
		}
		
		public function checkZen($char, $user, $server){
			$stmt = $this->website->db('game', $server)->prepare('SELECT Money FROM Character WHERE AccountId = :account AND '.$this->website->get_char_id_col($server).' = :char');
			$stmt->execute([':account' => $user, ':char' => $char]);
			return $stmt->fetch();
		}
		
		public function remove_zen($money, $char, $user, $server){
            $stmt = $this->website->db('game', $server)->prepare('UPDATE Character SET Money = Money - :money WHERE AccountId = :account AND '.$this->website->get_char_id_col($server).' = :char');
			$stmt->execute([':money' => (int)$money, ':account' => $user, ':char' => $char]);
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

				
		public function count_completed_achievements($char, $user, $server){
			$stmt = $this->website->db('web')->prepare('SELECT COUNT(id) AS count FROM DmN_User_Achievements WHERE memb___id = :account AND char_id = :char AND server = :server AND is_completed = 1');
			$stmt->execute([':account' => $user, ':char' => $char, 'server' => $server]);
			return $stmt->fetch();
		}

				
		public function remove_ruud($money, $char, $user, $server){
			if($this->website->db('game', $server)->check_if_column_exists('RuudMoney', 'Character') != false){
				$ruud = 'RuudMoney';
			}
			else{
				$ruud = 'Ruud';
			}
            $stmt = $this->website->db('game', $server)->prepare('UPDATE Character SET '.$ruud.' = '.$ruud.' - :money WHERE AccountId = :account AND '.$this->website->get_char_id_col($server).' = :char');
			$stmt->execute([':money' => (int)$money, ':account' => $user, ':char' => $char]);
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
		
		public function add_hunt($money, $char, $server){
			if($this->website->db('game', $server)->check_if_table_exists('IGC_HuntPoint')){
				$stmt = $this->website->db('game', $server)->prepare('UPDATE IGC_HuntPoint SET HuntPoint = HuntPoint + :money WHERE Name = :char');
				$stmt->execute([':money' => (int)$money, ':char' => $char]);
			}
		}

		public function check_votes($user, $server){
			$stmt = $this->website->db('web')->prepare('SELECT SUM(totalvotes) AS votes FROM DmN_Votereward_Ranking WHERE account = :account AND server = :server AND year = :year');
			$stmt->execute([':account' => $user, ':server' => $server, ':year' => date('Y', time())]);
			return $stmt->fetch();
		}
		
		public function check_votes_other($id, $user, $server){
			$stmt = $this->website->db('web')->prepare('SELECT SUM(amount_completed) AS votes FROM DmN_User_Achievements WHERE ach_type = 5 AND memb___id = :account AND server = :server AND char_id != :id');
			$stmt->execute([':account' => $user, ':server' => $server, ':id' => $id]);
			return $stmt->fetch();
		}
		
				
		public function check_donations_other($id, $ach_id, $user, $server){
			$stmt = $this->website->db('web')->prepare('SELECT SUM(amount_completed) AS donations FROM DmN_User_Achievements WHERE ach_type = 6 AND memb___id = :account AND server = :server AND char_id != :id');
			$stmt->execute([':account' => $user, ':server' => $server, ':id' => $id]);
			return $stmt->fetch();
		}
		/*
		public function check_donations_other($id, $ach_id, $user, $server){
			$stmt = $this->website->db('web')->prepare('SELECT SUM(amount_completed) AS donations FROM DmN_User_Achievements WHERE ach_type = 6 AND memb___id = :account AND server = :server');
			$stmt->execute([':account' => $user, ':server' => $server]);
			return $stmt->fetch();
		}
		*/
		
		public function check_donations($user, $server){
			$transactions = [];
			if($this->website->db('web')->check_if_table_exists('DmN_Donate_CoinBase_Transactions')){
				$transactions[] = $this->website->db('web')->query('SELECT COUNT(id) AS tr FROM DmN_Donate_CoinBase_Transactions WHERE acc = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).'')->fetch()['tr'];
			}	
			if($this->website->db('web')->check_if_table_exists('DmN_Donate_CuentaDigital_Transactions')){
				$transactions[] = $this->website->db('web')->query('SELECT COUNT(id) AS tr FROM DmN_Donate_CuentaDigital_Transactions WHERE acc = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).'')->fetch()['tr'];
			}
			//if($this->website->db('web')->check_if_table_exists('DmN_Donate_Fortumo')){
			//	$transactions[] = $this->website->db('web')->query('SELECT COUNT(id) AS tr FROM DmN_Donate_Fortumo WHERE account = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).'')->fetch()['tr'];
			//}
			if($this->website->db('web')->check_if_table_exists('DmN_Donate_Interkassa_Transactions')){
				$transactions[] = $this->website->db('web')->query('SELECT COUNT(id) AS tr FROM DmN_Donate_Interkassa_Transactions WHERE acc = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).'')->fetch()['tr'];
			}
			if($this->website->db('web')->check_if_table_exists('DmN_Donate_MercadoPago_Transactions')){
				$transactions[] = $this->website->db('web')->query('SELECT COUNT(id) AS tr FROM DmN_Donate_MercadoPago_Transactions WHERE acc = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).'')->fetch()['tr'];
			}
			if($this->website->db('web')->check_if_table_exists('DmN_Donate_NganLuong_Transactions')){
				$transactions[] = $this->website->db('web')->query('SELECT COUNT(id) AS tr FROM DmN_Donate_NganLuong_Transactions WHERE acc = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).'')->fetch()['tr'];
			}
			if($this->website->db('web')->check_if_table_exists('DmN_Donate_Paddle_Transactions')){
				$transactions[] = $this->website->db('web')->query('SELECT COUNT(id) AS tr FROM DmN_Donate_Paddle_Transactions WHERE acc = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).'')->fetch()['tr'];
			}
			if($this->website->db('web')->check_if_table_exists('DmN_Donate_PayCall_Transactions')){
				$transactions[] = $this->website->db('web')->query('SELECT COUNT(id) AS tr FROM DmN_Donate_PayCall_Transactions WHERE acc = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).'')->fetch()['tr'];
			}
			if($this->website->db('web')->check_if_table_exists('DmN_Donate_Payeer_Transactions')){
				$transactions[] = $this->website->db('web')->query('SELECT COUNT(id) AS tr FROM DmN_Donate_Payeer_Transactions WHERE acc = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).'')->fetch()['tr'];
			}
			if($this->website->db('web')->check_if_table_exists('DmN_Donate_Payssion_Transactions')){
				$transactions[] = $this->website->db('web')->query('SELECT COUNT(id) AS tr FROM DmN_Donate_Payssion_Transactions WHERE acc = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).'')->fetch()['tr'];
			}
			if($this->website->db('web')->check_if_table_exists('DmN_Donate_PayU_Transactions')){
				$transactions[] = $this->website->db('web')->query('SELECT COUNT(id) AS tr FROM DmN_Donate_PayU_Transactions WHERE acc = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).'')->fetch()['tr'];
			}
			if($this->website->db('web')->check_if_table_exists('DmN_Donate_Stripe_Transactions')){
				$transactions[] = $this->website->db('web')->query('SELECT COUNT(id) AS tr FROM DmN_Donate_Stripe_Transactions WHERE acc = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).'')->fetch()['tr'];
			}
			if($this->website->db('web')->check_if_table_exists('DmN_Donate_Transactions')){
				$transactions[] = $this->website->db('web')->query('SELECT COUNT(id) AS tr FROM DmN_Donate_Transactions WHERE acc = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).' AND status = \'Completed\'')->fetch()['tr'];
			}
			if($this->website->db('web')->check_if_table_exists('DmN_Donate_UnitPay_Transactions')){
				$transactions[] = $this->website->db('web')->query('SELECT COUNT(id) AS tr FROM DmN_Donate_UnitPay_Transactions WHERE acc = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).'')->fetch()['tr'];
			}
			if($this->website->db('web')->check_if_table_exists('DmN_Donate_WalletOne_Transactions')){
				$transactions[] = $this->website->db('web')->query('SELECT COUNT(id) AS tr FROM DmN_Donate_WalletOne_Transactions WHERE acc = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).'')->fetch()['tr'];
			}
			if($this->website->db('web')->check_if_table_exists('DmN_Donate_Binance_Transactions')){
				$transactions[] = $this->website->db('web')->query('SELECT COUNT(id) AS tr FROM DmN_Donate_Binance_Transactions WHERE acc = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).'')->fetch()['tr'];
			}
			if($this->website->db('web')->check_if_table_exists('DmN_Donate_Gerencianet_Transactions')){
				$transactions[] = $this->website->db('web')->query('SELECT COUNT(id) AS tr FROM DmN_Donate_Gerencianet_Transactions WHERE acc = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).'')->fetch()['tr'];
			}
			if($this->website->db('web')->check_if_table_exists('DmN_Donate_MoMo_Transactions')){
				$transactions[] = $this->website->db('web')->query('SELECT COUNT(id) AS tr FROM DmN_Donate_MoMo_Transactions WHERE acc = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).'')->fetch()['tr'];
			}
			if($this->website->db('web')->check_if_table_exists('DmN_Donate_Paghiper_Transactions')){
				$transactions[] = $this->website->db('web')->query('SELECT COUNT(id) AS tr FROM DmN_Donate_Paghiper_Transactions WHERE acc = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).'')->fetch()['tr'];
			}
			if($this->website->db('web')->check_if_table_exists('DmN_Donate_PayMongo_Transactions')){
				$transactions[] = $this->website->db('web')->query('SELECT COUNT(id) AS tr FROM DmN_Donate_PayMongo_Transactions WHERE acc = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).'')->fetch()['tr'];
			}
			if($this->website->db('web')->check_if_table_exists('DmN_Donate_PrimePayments_Transactions')){
				$transactions[] = $this->website->db('web')->query('SELECT COUNT(id) AS tr FROM DmN_Donate_PrimePayments_Transactions WHERE acc = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).'')->fetch()['tr'];
			}
			if($this->website->db('web')->check_if_table_exists('DmN_Donate_Xendit_Transactions')){
				$transactions[] = $this->website->db('web')->query('SELECT COUNT(id) AS tr FROM DmN_Donate_Xendit_Transactions WHERE acc = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).'')->fetch()['tr'];
			}
			return array_sum($transactions);
		}

				
		public function check_online_time($user, $server){
			return $this->website->db('web')->query('SELECT SUM(TotalTime) AS OnlineMinutes FROM DmN_OnlineCheck WHERE memb___id = '.$this->website->db('web')->escape($user).' ' . $this->website->server_code($this->website->get_servercode($server)) . '')->fetch();
		}

				
		public function check_online_time_other($id, $ach_id, $user, $server){
			$stmt = $this->website->db('web')->prepare('SELECT SUM(amount_completed) AS onlineHours FROM DmN_User_Achievements WHERE ach_type = 25 AND memb___id = :account AND server = :server AND char_id != :id');
			$stmt->execute([':account' => $user, ':server' => $server, ':id' => $id]);
			return $stmt->fetch();
		}

				
		public function check_online_achievement_count($id, $user, $server){
			$stmt = $this->website->db('web')->prepare('SELECT COUNT(id) AS achCount FROM DmN_User_Achievements WHERE ach_type = 25 AND memb___id = :account AND server = :server AND char_id != :chr_id AND amount_completed > 0');
			$stmt->execute([':account' => $user, ':server' => $server, ':chr_id' => $id]);
			return $stmt->fetch();
		}
		
		
		public function check_monsters($char, $server, $monsters = []){
			$search = '';
			if($this->website->db('game', $server)->check_if_table_exists('MonsterKillCount')){
				if(!empty($monsters)){
					$data = implode(',', $monsters);
					$search = 'AND MonsterClass IN('.$data.')';
				}
				$column = 'KillCount';
				if($this->website->db('game', $server)->check_if_column_exists('MonsterKillCount', 'MonsterKillCount') != false){
					$column = 'MonsterKillCount';
				}
				return $this->website->db('game', $server)->query('SELECT SUM('.$column.') AS count FROM MonsterKillCount WHERE Name = '.$this->website->db('game', $server)->escape($char).' '.$search.'')->fetch()['count'];
			}
			else{
				if(!empty($monsters)){
					$data = implode(',', $monsters);
					$search = 'AND MonsterId IN('.$data.')';
				}
				return $this->website->db('game', $server)->query('SELECT SUM(count) AS count FROM C_Monster_KillCount WHERE name = '.$this->website->db('game', $server)->escape($char).' '.$search.'')->fetch()['count'];
			}
		}

				
		public function check_referrals($user, $server){
			return $this->website->db('web')->query('SELECT COUNT(id) AS tr FROM DmN_Refferals WHERE refferer = '.$this->website->db('web')->escape($user).'')->fetch()['tr'];
		}
		
		
		public function check_kills($char, $server, $unique = 0, $minRes = 0){
			if($this->website->db('game', $server)->check_if_table_exists('C_PlayerKiller_Info')){
				if($unique == 1){
					$data = $this->website->db('game', $server)->query('SELECT DISTINCT kk.Victim, kk.KillDate, c.Name, ' . $this->reset_column($server).' c.AccountId FROM C_PlayerKiller_Info AS kk LEFT JOIN Character AS c ON (kk.Victim Collate Database_Default = c.Name Collate Database_Default) WHERE kk.Killer = '.$this->website->db('game', $server)->escape($char).' AND isChecked = 0')->fetch_all();
					
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
					return $this->website->db('game', $server)->query('SELECT COUNT(Victim) AS count FROM C_PlayerKiller_Info WHERE Killer = '.$this->website->db('game', $server)->escape($char).'')->fetch()['count'];
				}
			}
			else{
				return $this->website->db('game', $server)->query('SELECT dmn_pk_count AS count FROM Character WHERE Name = '.$this->website->db('game', $server)->escape($char).'')->fetch()['count'];
			}
		}

				
		public function setKillsChecked($victim, $killer, $date, $server){
			$this->website->db('game', $server)->query('UPDATE C_PlayerKiller_Info SET isChecked = 1 WHERE Victim = \''.$victim.'\' AND Killer = \''.$killer.'\' AND KillDate = \''.$date.'\'');
		}
		
		
		public function get_score($char, $server, $table_config){
			return $this->website->db($table_config['db'], $server)->query('SELECT ' . $table_config['column'] . ' AS score FROM ' . $table_config['table'] . ' WHERE ' . $table_config['identifier_column'] . ' = '.$this->website->db($table_config['db'], $server)->escape($char).'')->fetch()['score'];
		}
		
		
		public function get_score_sum($char, $server, $table_config){
			return $this->website->db($table_config['db'], $server)->query('SELECT SUM(' . $table_config['column'] . ') AS score FROM ' . $table_config['table'] . ' WHERE ' . $table_config['identifier_column'] . ' = '.$this->website->db($table_config['db'], $server)->escape($char).'')->fetch()['score'];
		}
		
		
		public function get_bc_playcount($char, $server, $table_config){
			return $this->website->db($table_config['db'], $server)->query('SELECT COUNT(' . $table_config['column'] . ') AS score FROM ' . $table_config['table'] . ' WHERE ' . $table_config['identifier_column'] . ' = '.$this->website->db($table_config['db'], $server)->escape($char).'')->fetch()['score'];
		}
		
		
		public function get_contribution($char, $server){
			if($this->website->db('game', $server)->check_if_table_exists('GensUserInfo')){
				return $this->website->db('game', $server)->query('SELECT memb_contribution AS contribution FROM GensUserInfo WHERE memb_char = '.$this->website->db('game', $server)->escape($char).'')->fetch()['contribution'];
			}	
			if($this->website->db('game', $server)->check_if_table_exists('GensMember')){
				return $this->website->db('game', $server)->query('SELECT Contribute AS contribution FROM GensMember WHERE Name = '.$this->website->db('game', $server)->escape($char).'')->fetch()['contribution'];
			}
			if($this->website->db('game', $server)->check_if_table_exists('IGC_Gens')){
				return $this->website->db('game', $server)->query('SELECT Points AS contribution FROM IGC_Gens WHERE Name = '.$this->website->db('game', $server)->escape($char).'')->fetch()['contribution'];
			}
			if($this->website->db('game', $server)->check_if_table_exists('Gens_Rank')){
				return $this->website->db('game', $server)->query('SELECT Contribution AS contribution FROM Gens_Rank WHERE Name = '.$this->website->db('game', $server)->escape($char).'')->fetch()['contribution'];
			}
		}
		
		
		public function check_shop($user, $server){
			$transactions = [];
			$transactions[] = $this->website->db('web')->query('SELECT COUNT(memb___id) AS tr FROM DmN_Shop_Logs WHERE memb___id = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).'')->fetch()['tr'];
			
			if($this->website->db('game', $server)->check_if_table_exists('T_InGameShop_Log')){
				$transactions[] = $this->website->db('game', $server)->query('SELECT COUNT(AccountID) AS tr FROM T_InGameShop_Log WHERE AccountID = '.$this->website->db('web')->escape($user).'')->fetch()['tr'];
			}
			
			return array_sum($transactions);
		}
		
		public function check_market($char, $user, $server){
			$transactions = [];
			$transactions[] = $this->website->db('web')->query('SELECT COUNT(DISTINCT buyer) AS tr FROM DmN_Market_Logs WHERE seller = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).' AND char = '.$this->website->db('web')->escape($char).'')->fetch()['tr'];
			
			if($this->website->db('game', $server)->check_if_table_exists('IGC_PersonalStore_Log')){
				$transactions[] = $this->website->db('game', $server)->query('SELECT COUNT(id) AS tr FROM IGC_PersonalStore_Log WHERE seller_account = '.$this->website->db('web')->escape($user).' AND seller_name = '.$this->website->db('web')->escape($char).' AND buyer_account != \'-\'')->fetch()['tr'];
			}
			return array_sum($transactions);
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

				
		public function check_inventory($server){
            return (strtoupper(substr($this->char_info['Inventory'], $this->website->get_value_from_server($server, 'item_size') * 12, $this->website->get_value_from_server($server, 'item_size') * 64)) === str_repeat('F', $this->website->get_value_from_server($this->session->userdata(['user' => 'server']), 'item_size') * 64));
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
		
		public function get_vip_package_title($vip_type = 1){
			return $this->website->db('web')->query('SELECT package_title, vip_time FROM DmN_Vip_Packages WHERE id = '.$this->website->db('web')->escape($vip_type).'')->fetch();
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
