<?php
    in_file();

    class Mcharacter extends model
    {
        private $characters = [], $guild_info = [], $ml_points = 0, $ml_level = 0, $skillTreeTable, $defaultStats = [], $charge_from_zen_wallet = 0;
        public $error = false, $vars = [], $char_info = [];

        public function __contruct(){
            parent::__construct();
        }

        public function __set($key, $val){
            $this->vars[$key] = $val;
        }

        public function __isset($name){
            return isset($this->vars[$name]);
        }
		
		public function load_char_list($user, $server){
			$ruud = ', 0 AS Ruud';
			if(MU_VERSION >= 5){
				$ruud = ', Ruud';
			}
			if(MU_VERSION >= 12){
				if($this->website->db('game', $server)->check_if_column_exists('RuudMoney', 'Character') != false){
					$ruud = ', RuudMoney AS Ruud';
				}
				else{
					$ruud = ', Ruud';
				}
			}
            $stmt = $this->website->db('game', $server)->prepare('SELECT Name, cLevel, Class, ' . $this->reset_column($server) . $this->greset_column($server) . ' Money, LevelUpPoint, CtlCode, PkCount, PkLevel '.$ruud.' FROM Character WHERE AccountId = :account');
            $stmt->execute([':account' => $user]);
			
            while($row = $stmt->fetch()){
                $this->characters[] = [
					'name' => $row['Name'], 
					'level' => $row['cLevel'], 
					'Class' => $row['Class'], 
					'resets' => $row['resets'], 
					'gresets' => $row['grand_resets'], 
					'money' => $row['Money'], 
					'points' => $row['LevelUpPoint'], 
					'ctlcode' => $row['CtlCode'], 
					'pkcount' => $row['PkCount'], 
					'pklevel' => $row['PkLevel'], 
					'CtlCode' => $row['CtlCode'], 
					'Ruud' => $ruud
				];
            }
			
            if(!empty($this->characters)){
                return $this->characters;
            } 
			else{
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

        public function check_char($user, $server, $char = '', $custom_field = '', $lock = false){
            $char = ($char != '') ? $char : $this->vars['character'];
			$with_lock = ($lock == true) ? 'WITH (UPDLOCK,HOLDLOCK)' : '';
			$leadership = (MU_VERSION < 1) ? '0 AS Leadership,' : 'Leadership,';
            $stmt = $this->website->db('game', $server)->prepare('SELECT Name, Money, Class, cLevel, ' . $this->reset_column($server) . $this->greset_column($server) . ' LevelUpPoint, Strength, Dexterity, Vitality, Energy, '.$leadership.' PkLevel, PkCount, CtlCode, MagicList, CONVERT(IMAGE, Quest) AS Quest, CONVERT(IMAGE, Inventory) AS Inventory, last_reset_time, '.$this->website->get_char_id_col($server).' AS id ' . $custom_field . ' FROM Character '.$with_lock.' WHERE AccountId = :user AND Name = :char');
            $stmt->execute([':user' => $user, ':char' => $char]);
            if($this->char_info = $stmt->fetch()){
                $this->char_info['mlevel'] = $this->load_master_level($this->char_info['Name'], $server);
				$this->char_info['Quest'] = $this->website->clean_hex($this->char_info['Quest']);
				$unpack = unpack('H*', $this->char_info['Inventory']);
				$this->char_info['Inventory'] = $this->website->clean_hex($unpack[1]);
                return true;
            }
            return false;
        }
		
		public function check_char_no_account($char, $server){
			$leadership = (MU_VERSION < 1) ? '0 AS Leadership,' : 'Leadership,';
            $stmt = $this->website->db('game', $server)->prepare('SELECT AccountId, Name, Money, Class, cLevel, ' . $this->reset_column($server) . $this->greset_column($server) . ' LevelUpPoint, Strength, Dexterity, Vitality, Energy, '.$leadership.' PkLevel, PkCount, CtlCode, MagicList, CONVERT(IMAGE, Inventory) AS Inventory, last_reset_time, '.$this->website->get_char_id_col($server).' AS id FROM Character WHERE Name = :char');
            $stmt->execute([':char' => $char]);
            if($this->char_info = $stmt->fetch()){
                $this->char_info['mlevel'] = $this->load_master_level($this->char_info['Name'], $server);
				$unpack = unpack('H*', $this->char_info['Inventory']);
				$this->char_info['Inventory'] = $this->website->clean_hex($unpack[1]);
                return true;
            }
            return false;
        }
		
		public function check_zen($required = 0, $multiply = 0, $col = 'resets'){
            if($this->session->userdata('vip')){
                $required -= $this->session->userdata(['vip' => 'reset_price_decrease']);
            }
            if($multiply == 1){
                $this->char_info['res_money'] = $required * ($this->char_info[$col] + 1);
            } 
			else{
                $this->char_info['res_money'] = $required;
            }
            return ($this->char_info['Money'] >= $this->char_info['res_money']) ? true : $this->char_info['res_money'];
        }

        public function check_zen_wallet($user, $server, $id, $required = 0, $multiply = 0, $col = 'resets'){
            $this->charge_from_zen_wallet = 1;
            $status = $this->website->get_user_credits_balance($user, $server, 3, $id);
            return ($status['credits'] >= $this->char_info['res_money']) ? true : $this->char_info['res_money'];
        }

        public function check_lvl($req_lvl = 400){
            if($this->session->userdata('vip')){
                $req_lvl -= $this->session->userdata(['vip' => 'reset_level_decrease']);
            }
            return ($this->char_info['cLevel'] >= $req_lvl) ? true : $req_lvl;
        }
		
		public function check_mlvl($req_lvl = 400){
            return ($this->char_info['mlevel'] >= $req_lvl) ? true : $req_lvl;
        }

        public function check_resets($req_resets = 100){
            return ($this->char_info['resets'] >= $req_resets);
        }

        public function check_gresets($req_gresets = 0){
            return ($this->char_info['grand_resets'] >= $req_gresets);
        }

        public function check_stats(){
            $this->vars['str_stat'] = isset($this->vars['str_stat']) ? (int)$this->vars['str_stat'] : 0;
            $this->vars['agi_stat'] = isset($this->vars['agi_stat']) ? (int)$this->vars['agi_stat'] : 0;
            $this->vars['ene_stat'] = isset($this->vars['ene_stat']) ? (int)$this->vars['ene_stat'] : 0;
            $this->vars['vit_stat'] = isset($this->vars['vit_stat']) ? (int)$this->vars['vit_stat'] : 0;
            $this->vars['com_stat'] = isset($this->vars['com_stat']) ? (int)$this->vars['com_stat'] : 0;
            $this->vars['allstats'] = in_array($this->char_info['Class'], [64, 65, 66, 67, 70, 78]) ? ($this->vars['str_stat'] + $this->vars['agi_stat'] + $this->vars['ene_stat'] + $this->vars['vit_stat'] + $this->vars['com_stat']) : ($this->vars['str_stat'] + $this->vars['agi_stat'] + $this->vars['ene_stat'] + $this->vars['vit_stat']);
        }

        public function set_new_stats(){
            $this->vars['new_str'] = $this->website->show65kStats($this->char_info['Strength']) + $this->vars['str_stat'];
            $this->vars['new_agi'] = $this->website->show65kStats($this->char_info['Dexterity']) + $this->vars['agi_stat'];
            $this->vars['new_ene'] = $this->website->show65kStats($this->char_info['Energy']) + $this->vars['ene_stat'];
            $this->vars['new_vit'] = $this->website->show65kStats($this->char_info['Vitality']) + $this->vars['vit_stat'];
            $this->vars['new_com'] = in_array($this->char_info['Class'], [64, 65, 66, 67, 70, 78]) ? $this->website->show65kStats($this->char_info['Leadership']) + $this->vars['com_stat'] : 0;
            $this->vars['new_lvlup'] = $this->char_info['LevelUpPoint'] - $this->vars['allstats'];
        }

        public function check_max_stat_limit($server){
            if($this->vars['new_str'] > $this->config->config_entry('character_' . $server . '|max_stats')){
                $this->vars['error'] = 'Max Strength: ' . $this->config->config_entry('character_' . $server . '|max_stats');
            }
            if($this->vars['new_agi'] > $this->config->config_entry('character_' . $server . '|max_stats')){
                $this->vars['error'] = 'Max Agility: ' . $this->config->config_entry('character_' . $server . '|max_stats');
            }
            if($this->vars['new_ene'] > $this->config->config_entry('character_' . $server . '|max_stats')){
                $this->vars['error'] = 'Max Energy: ' . $this->config->config_entry('character_' . $server . '|max_stats');
            }
            if($this->vars['new_vit'] > $this->config->config_entry('character_' . $server . '|max_stats')){
                $this->vars['error'] = 'Max Vitality: ' . $this->config->config_entry('character_' . $server . '|max_stats');
            }
            if(in_array($this->char_info['Class'], [64, 65, 66, 67, 70, 78])){
                if($this->vars['new_com'] > $this->config->config_entry('character_' . $server . '|max_stats')){
                    $this->vars['error'] = 'Max Command: ' . $this->config->config_entry('character_' . $server . '|max_stats');
                }
            }
            if(isset($this->vars['error']))
                return false;
            return true;
        }
		
		public function add_stats($user, $server, $char){
			$data = [
				':new_lvlup' => $this->vars['new_lvlup'], 
				':new_str' => $this->vars['new_str'], 
				':new_agi' => $this->vars['new_agi'], 
				':new_ene' => $this->vars['new_ene'], 
				':new_vit' => $this->vars['new_vit']
			];
			
			$leadership = '';
			
			if(MU_VERSION >= 1){
				$ld = [':new_com' => $this->vars['new_com']];
				$data = $data + $ld;
				$leadership = ', Leadership = :new_com';
			}
			$otherData = [
				':char' => $char, 
				':user' => $user
			];
			$data = $data + $otherData;
            $stmt = $this->website->db('game', $server)->prepare('UPDATE Character SET LevelUpPoint = :new_lvlup, Strength = :new_str, Dexterity = :new_agi, Energy = :new_ene, Vitality = :new_vit '.$leadership.' WHERE Name = :char AND AccountId = :user');
            $stmt->execute($data);
        }
		
		public function reset_character($user, $server){
			$location = 'MapNumber = 0, MapPosX = 123, MapPosY = 130,';
			
			if(!defined('CRYSTALMU')){
				if(in_array($this->char_info['Class'], [32,33,34,35,39])){
					$location = 'MapNumber = 3, MapPosX = 175, MapPosY = 114,';
				}
				if(in_array($this->char_info['Class'], [80,81,82,83,84,87])){
					$location = 'MapNumber = 51, MapPosX = 52, MapPosY = 226,';
				}
			}
			
			$level_after_reset = isset($this->char_info['res_info']['level_after_reset']) ? (int)$this->char_info['res_info']['level_after_reset'] : 1;
			
            if($this->charge_from_zen_wallet == 0){
                $query = 'UPDATE Character SET ' . $this->config->values('table_config', [$server, 'resets', 'column']) . ' = ' . $this->config->values('table_config', [$server, 'resets', 'column']) . ' + 1, clevel = '.$level_after_reset.', Money = Money - :reset_money, '.$location.' Experience = 0, last_reset_time = :time WHERE Name = :char AND AccountId = :user';
                $data = [':reset_money' => $this->char_info['res_money'], ':time' => time(), ':char' => $this->vars['character'], ':user' => $user];
            } 
			else{
                $this->website->charge_credits($user, $server, $this->char_info['res_money'], 3);
                $query = 'UPDATE Character SET ' . $this->config->values('table_config', [$server, 'resets', 'column']) . ' = ' . $this->config->values('table_config', [$server, 'resets', 'column']) . ' + 1, clevel = '.$level_after_reset.', '.$location.' Experience = 0, last_reset_time = :time WHERE Name = :char AND AccountId = :user';
                $data = [':time' => time(), ':char' => $this->vars['character'], ':user' => $user];
            }
            $stmt = $this->website->db('game', $server)->prepare($query);
            $stmt->execute($data);
			
            if($this->char_info['res_info']['clear_magic'] != 0){
                $this->clear_magic_list($user, $server);
            }
			
            $this->clear_inventory($user, $server, $this->char_info['res_info']);
			
            if($this->char_info['res_info']['clear_stats'] != 0){
                $this->clear_reset_stats($user, $server);
            }
			
            if($this->char_info['res_info']['clear_level_up'] != 0){
                $this->clear_reset_levelup($user, $server);
            }
			
            $this->add_bonus_reset_points($user, $server);
			
            if($this->char_info['res_info']['bonus_gr_points'] == 1 && $this->char_info['bonus_greset_stats_points'] > 0){
                $this->add_bonus_stats_for_gresets($user, $server);
            }
			
            if(defined('RES_CUSTOM_BACKUP_MASTER') && RES_CUSTOM_BACKUP_MASTER == true){
                if(in_array($this->char_info['Class'], [2, 3, 18, 19, 34, 35, 49, 50, 65, 66, 82, 83, 97, 98])){
                    $this->backup_master_level($user, $server);
                    $this->change_reset_class($user, $server);
                }
            }
			
            if($this->char_info['res_info']['bonus_credits'] != 0){
                $this->add_account_log('Reward ' . $this->website->translate_credits(1, $server) . ' for reset: ' . $this->vars['character'] . '', $this->char_info['res_info']['bonus_credits'], $user, $server);
				$this->website->add_credits($user, $server, $this->char_info['res_info']['bonus_credits'], 1);
            }
            if($this->char_info['res_info']['bonus_gcredits'] != 0){
                $this->add_account_log('Reward ' . $this->website->translate_credits(2, $server) . ' for reset: ' . $this->vars['character'] . '', $this->char_info['res_info']['bonus_gcredits'], $user, $server);
				$this->website->add_credits($user, $server, $this->char_info['res_info']['bonus_gcredits'], 2);
            }
			if($this->char_info['res_info']['bonus_credits'] == 0 && $this->char_info['res_info']['bonus_gcredits'] == 0){
                $this->add_account_log('Character ' . $this->vars['character'] . ' made reset', 0, $user, $server);
            }
            if(isset($this->char_info['res_info']['bonus_ruud']) && $this->char_info['res_info']['bonus_ruud'] > 0){
				$this->add_ruud($this->char_info['res_info']['bonus_ruud'], $this->char_info['id'], $user, $server);
			}
			if(isset($this->char_info['res_info']['clear_masterlevel']) && $this->char_info['res_info']['clear_masterlevel'] == 1){
				$skill_tree = $this->reset_skill_tree($user, $server, $this->config->config_entry('character_' . $server . '|skill_tree_type'), $this->config->config_entry('character_' . $server . '|skilltree_reset_level'), $this->config->config_entry('character_' . $server . '|skilltree_reset_points'), $this->config->config_entry('character_' . $server . '|skilltree_points_multiplier'));
			}
			
			$this->vars['current_date'] = date('Y-m-d', time());
			
			if($this->checkResetLog($this->char_info['id'], $user, $server, $this->vars['current_date']) == false){
				$this->insertResetLog($this->char_info['id'], $user, $server, $this->vars['current_date']);
			}
			else{
				$this->updateResetLog($this->char_info['id'], $user, $server, $this->vars['current_date']);
			}
		
            return true;
        }
		
		public function checkResetLog($char, $user, $server, $date){
			return $this->website->db('web')->query('SELECT id, resets FROM DmN_Character_Reset_Log WHERE char_id = '.$this->website->db('web')->escape($char).' AND account = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).' AND date = '.$this->website->db('web')->escape($date).'')->fetch();
		}
		
		public function getTotalResets($char, $user, $server){
			return $this->website->db('web')->query('SELECT SUM(resets) AS resets FROM DmN_Character_Reset_Log WHERE char_id = '.$this->website->db('web')->escape($char).' AND account = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).'')->fetch()['resets'];
		}
	
		public function clearResetLog($char, $user, $server, $date){
			$stmt = $this->website->db('web')->prepare('DELETE FROM DmN_Character_Reset_Log WHERE char_id = :char_id AND account = :account AND server = :server and date >= :date');
			$stmt->execute([
				':char_id' => $char, 
				':account' => $user, 
				':server' => $server,
				':date' => $date
			]);
		}
		
		public function insertResetLog($char, $user, $server, $date){
			$stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Character_Reset_Log (char_id, account, server, resets, date) VALUES (:char_id, :account, :server, 1, :date)');
			$stmt->execute([
				':char_id' => $char,
				':account' => $user,
				':server' => $server,
				':date' => $date
			]);
		}
		
		public function updateResetLog($char, $user, $server, $date){
			$stmt = $this->website->db('web')->prepare('UPDATE DmN_Character_Reset_Log SET resets = resets + 1 WHERE  char_id = :char_id AND account = :account AND server = :server AND date = :date');
			$stmt->execute([
				':char_id' => $char,
				':account' => $user,
				':server' => $server,
				':date' => $date
			]);
		}

		public function greset_character($user, $server){
            if(isset($this->char_info['gres_info']['clear_all_resets']) && $this->char_info['gres_info']['clear_all_resets'] == 0){
                $resets = $this->config->values('table_config', [$server, 'resets', 'column']) . '-' . $this->char_info['gres_info']['reset'];
            } else{
                $resets = 0;
            }
			
			$location = 'MapNumber = 0, MapPosX = 123, MapPosY = 130,';
			
			if(in_array($this->char_info['Class'], [32,33,34,35,39])){
				$location = 'MapNumber = 3, MapPosX = 175, MapPosY = 114,';
			}
			if(in_array($this->char_info['Class'], [80,81,82,83,84,87])){
				$location = 'MapNumber = 51, MapPosX = 52, MapPosY = 226,';
			}
			
            if($this->charge_from_zen_wallet == 0){
                $query = 'UPDATE Character SET ' . $this->config->values('table_config', [$server, 'resets', 'column']) . ' = ' . $resets . ', ' . $this->config->values('table_config', [$server, 'grand_resets', 'column']) . ' = ' . $this->config->values('table_config', [$server, 'grand_resets', 'column']) . ' + 1, clevel = 1, '.$location.' Experience = 0, Money = Money - :money, last_greset_time = :time WHERE Name = :char AND AccountId = :user';
                $data = [':money' => $this->char_info['res_money'], ':time' => time(), ':char' => $this->vars['character'], ':user' => $user];
            } 
			else{
                $this->website->charge_credits($user, $server, $this->char_info['res_money'], 3);
                $query = 'UPDATE Character SET ' . $this->config->values('table_config', [$server, 'resets', 'column']) . ' = ' . $resets . ', ' . $this->config->values('table_config', [$server, 'grand_resets', 'column']) . ' = ' . $this->config->values('table_config', [$server, 'grand_resets', 'column']) . ' + 1, clevel = 1, '.$location.' Experience = 0, last_greset_time = :time WHERE Name = :char AND AccountId = :user';
                $data = [':time' => time(), ':char' => $this->vars['character'], ':user' => $user];
            }
            $stmt = $this->website->db('game', $server)->prepare($query);
            $stmt->execute($data);
			
            if($this->char_info['gres_info']['clear_magic'] != 0){
                $this->clear_magic_list($user, $server);
            }
            if($this->char_info['gres_info']['clear_inventory'] != 0){
                $this->clear_inventory($user, $server, $this->char_info['gres_info']);
            }
            if($this->char_info['gres_info']['clear_stats'] != 0){
                $this->clear_greset_stats($user, $server);
            }
            if($this->char_info['gres_info']['clear_level_up'] != 0){
                $this->clear_greset_levelup($user, $server);
            }
			
            $this->add_bonus_greset_points($user, $server);
			
            if($this->char_info['gres_info']['bonus_reset_stats'] == 1 && $this->char_info['bonus_reset_stats_points'] > 0){
                $this->add_bonus_stats_for_resets($user, $server);
            }
            if($this->session->userdata('vip')){
                $this->char_info['gres_info']['bonus_credits'] += $this->session->userdata(['vip' => 'grand_reset_bonus_credits']);
                $this->char_info['gres_info']['bonus_gcredits'] += $this->session->userdata(['vip' => 'grand_reset_bonus_gcredits']);
            }
            if($this->char_info['gres_info']['bonus_credits'] != 0){
                $this->add_account_log('Reward ' . $this->website->translate_credits(1, $server) . ' for grand reset: ' . $this->vars['character'] . '', $this->char_info['gres_info']['bonus_credits'], $user, $server);
				$this->website->add_credits($user, $server, $this->char_info['gres_info']['bonus_credits'], 1);
            }
            if($this->char_info['gres_info']['bonus_gcredits'] != 0){
                $this->add_account_log('Reward ' . $this->website->translate_credits(2, $server) . ' for grand reset: ' . $this->vars['character'] . '', $this->char_info['gres_info']['bonus_gcredits'], $user, $server);
				$this->website->add_credits($user, $server, $this->char_info['gres_info']['bonus_gcredits'], 1);
            }
			if(isset($this->char_info['gres_info']['bonus_ruud']) && $this->char_info['gres_info']['bonus_ruud'] > 0){
				$this->add_ruud($this->char_info['gres_info']['bonus_ruud'], $this->char_info['id'], $user, $server);
			}
			if($this->char_info['gres_info']['bonus_credits'] == 0 && $this->char_info['gres_info']['bonus_gcredits'] == 0){
                $this->add_account_log('Character ' . $this->vars['character'] . ' made grand reset', 0, $user, $server);
            }

			if(isset($this->char_info['gres_info']['clear_masterlevel']) && $this->char_info['gres_info']['clear_masterlevel'] == 1){
				$skill_tree = $this->reset_skill_tree($user, $server, $this->config->config_entry('character_' . $server . '|skill_tree_type'), $this->config->config_entry('character_' . $server . '|skilltree_reset_level'), $this->config->config_entry('character_' . $server . '|skilltree_reset_points'), $this->config->config_entry('character_' . $server . '|skilltree_points_multiplier'));
			}
			
			$this->vars['current_date'] = date('Y-m-d', time());
			
			if($this->checkGResetLog($this->char_info['id'], $user, $server, $this->vars['current_date']) == false){
				$this->insertGResetLog($this->char_info['id'], $user, $server, $this->vars['current_date']);
			}
			else{
				$this->updateGResetLog($this->char_info['id'], $user, $server, $this->vars['current_date']);
			}
			
            return true;
        }
		
		public function checkGResetLog($char, $user, $server, $date){
			return $this->website->db('web')->query('SELECT id, resets FROM DmN_Character_GReset_Log WHERE char_id = '.$this->website->db('web')->escape($char).' AND account = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).' AND date = '.$this->website->db('web')->escape($date).'')->fetch();
		}
		
		public function clearGResetLog($char, $user, $server, $date){
			$stmt = $this->website->db('web')->prepare('DELETE FROM DmN_Character_GReset_Log WHERE char_id = :char_id AND account = :account AND server = :server and date >= :date');
			$stmt->execute([
				':char_id' => $char, 
				':account' => $user, 
				':server' => $server,
				':date' => $date
			]);
		}
		
		public function insertGResetLog($char, $user, $server, $date){
			$stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Character_GReset_Log (char_id, account, server, resets, date) VALUES (:char_id, :account, :server, 1, :date)');
			$stmt->execute([
				':char_id' => $char,
				':account' => $user,
				':server' => $server,
				':date' => $date
			]);
		}
		
		public function updateGResetLog($char, $user, $server, $date){
			$stmt = $this->website->db('web')->prepare('UPDATE DmN_Character_GReset_Log SET resets = resets + 1 WHERE  char_id = :char_id AND account = :account AND server = :server AND date = :date');
			$stmt->execute([
				':char_id' => $char,
				':account' => $user,
				':server' => $server,
				':date' => $date
			]);
		}
		
		private function clear_reset_stats($user, $server){
            if(!defined('RES_DECREASE_STATS_PERC') || RES_DECREASE_STATS_PERC == false){
                $data = [
					':str' => $this->Mcharacter->char_info['res_info']['new_stat_points'], 
					':agi' => $this->Mcharacter->char_info['res_info']['new_stat_points'], 
					':vit' => $this->Mcharacter->char_info['res_info']['new_stat_points'], 
					':ene' => $this->Mcharacter->char_info['res_info']['new_stat_points']
				];
                if(in_array($this->char_info['Class'], [64, 65, 66, 67, 70, 78])){
                    $dl = ', Leadership = :com';
                    $data[':com'] = $this->Mcharacter->char_info['res_info']['new_stat_points'];
                } 
				else{
                    $dl = '';
                }
            } 
			else{
                $data = [
					':str' => $this->website->show65kStats($this->char_info['Strength']) - round((RES_DECREASE_STATS_PERC / 100) * $this->website->show65kStats($this->char_info['Strength'])), 
					':agi' => $this->website->show65kStats($this->char_info['Dexterity']) - round((RES_DECREASE_STATS_PERC / 100) * $this->website->show65kStats($this->char_info['Dexterity'])), 
					':vit' => $this->website->show65kStats($this->char_info['Vitality']) - round((RES_DECREASE_STATS_PERC / 100) * $this->website->show65kStats($this->char_info['Vitality'])), 
					':ene' => $this->website->show65kStats($this->char_info['Energy']) - round((RES_DECREASE_STATS_PERC / 100) * $this->website->show65kStats($this->char_info['Energy']))
				];
                if(in_array($this->char_info['Class'], [64, 65, 66, 67, 70, 78])){
                    $dl = ', Leadership = :com';
                    $data[':com'] = $this->website->show65kStats($this->char_info['Leadership'])/* - round((RES_DECREASE_STATS_PERC / 100) * $this->website->show65kStats($this->char_info['Leadership']))*/;
                } 
				else{
                    $dl = '';
                }
            }
            $data[':char'] = $this->vars['character'];
            $data[':user'] = $user;
            $stmt = $this->website->db('game', $server)->prepare('UPDATE Character SET Strength = :str, Dexterity = :agi, Vitality = :vit, Energy = :ene' . $dl . ' WHERE Name = :char AND AccountId = :user');
            $stmt->execute($data);
        }
		
		private function clear_greset_stats($user, $server){
            $data = [':str' => $this->Mcharacter->char_info['gres_info']['new_stat_points'], ':agi' => $this->Mcharacter->char_info['gres_info']['new_stat_points'], ':vit' => $this->Mcharacter->char_info['gres_info']['new_stat_points'], ':ene' => $this->Mcharacter->char_info['gres_info']['new_stat_points']];
            if(in_array($this->char_info['Class'], [64, 65, 66, 67, 70, 78])){
                $dl = ', Leadership = :com';
                $data[':com'] = $this->Mcharacter->char_info['gres_info']['new_stat_points'];
            } else{
                $dl = '';
            }
            $data[':char'] = $this->vars['character'];
            $data[':user'] = $user;
            $stmt = $this->website->db('game', $server)->prepare('UPDATE Character SET Strength = :str, Dexterity = :agi, Vitality = :vit, Energy = :ene' . $dl . ' WHERE Name = :char AND AccountId = :user');
            $stmt->execute($data);
        }
		
		private function clear_reset_levelup($user, $server){
            $stmt = $this->website->db('game', $server)->prepare('UPDATE Character SET LevelUpPoint = :freepoints WHERE Name = :character AND AccountId = :user');
            $stmt->execute([':freepoints' => $this->Mcharacter->char_info['res_info']['new_free_points'], ':character' => $this->vars['character'], ':user' => $user]);
        }
		
		private function clear_greset_levelup($user, $server){
            $stmt = $this->website->db('game', $server)->prepare('UPDATE Character SET LevelUpPoint = :freepoints WHERE Name = :character AND AccountId = :user');
            $stmt->execute([':freepoints' => $this->Mcharacter->char_info['gres_info']['new_free_points'], ':character' => $this->vars['character'], ':user' => $user]);
        }

        public function clear_magic_list($user, $server){
            $stmt = $this->website->db('game', $server)->prepare('UPDATE Character SET MagicList = CAST(REPLICATE(char(0xff), 180) as varbinary(180)) WHERE Name = :char AND AccountId = :user');
            $stmt->execute([':char' => $this->vars['character'], ':user' => $user]);
        }
		
		private function clear_inventory($user, $server, $res_config = false){
			$item_size = $this->website->get_value_from_server($server, 'item_size');
			$multiplier = $this->website->get_value_from_server($server, 'inv_multiplier');
            $items_array = str_split($this->Mcharacter->char_info['Inventory'], $item_size);
            if($res_config != false){
                if(isset($res_config['clear_inventory']) && $res_config['clear_inventory'] == 1){
                    for($a = 12; $a < 76; $a++){
                        $items_array[$a] = str_repeat('F', $item_size);
                    }
                }
                if(isset($res_config['clear_equipment']) && $res_config['clear_equipment'] == 1){
                    for($a = 0; $a < 12; $a++){
                        $items_array[$a] = str_repeat('F', $item_size);
                    }
                    if(isset($items_array[236])){
                        $items_array[236] = str_repeat('F', $item_size);
                    }
                }
                if($multiplier == 236){
                    if(isset($res_config['clear_store']) && $res_config['clear_store'] == 1){
                        for($a = 204; $a < 236; $a++){
                            $items_array[$a] = str_repeat('F', $item_size);
                        }
                    }
                    if(isset($res_config['clear_exp_inventory']) && $res_config['clear_exp_inventory'] == 1){
                        for($a = 76; $a < 140; $a++){
                            $items_array[$a] = str_repeat('F', $item_size);
                        }
                    }
                } else{
                    if(isset($res_config['clear_store']) && $res_config['clear_store'] == 1){
                        for($a = 76; $a < 108; $a++){
                            $items_array[$a] = str_repeat('F', $item_size);
                        }
                    }
                }
            } else{
                for($a = 0; $a <= $multiplier; $a++){
                    $items_array[$a] = str_repeat('F', $item_size);
                }
            }
            $stmt = $this->website->db('game', $server)->prepare('UPDATE Character SET Inventory = 0x' . implode('', $items_array) . ' WHERE Name = :char AND AccountId = :user');
            $stmt->execute([':char' => $this->vars['character'], ':user' => $user]);
        }
		
		private function add_bonus_reset_points($user, $server){
            $bonus = ($this->char_info['resets'] + 1) * $this->bonus_points_by_class($this->char_info['Class']);
			if($this->session->userdata('vip')){
                $bonus += $this->session->userdata(['vip' => 'reset_bonus_points']);
            }
			if(defined('CRYSTALMU') && CRYSTALMU == 1){
				$bonusFromQuest = $this->website->db('game', $server)->query('SELECT QuestStat FROM Character WHERE Name = '.$this->website->db('game', $server)->escape($this->vars['character']).'')->fetch();
				if($bonusFromQuest != false){
					$bonus += $bonusFromQuest['QuestStat'];
				}
			}
			
            $stmt = $this->website->db('game', $server)->prepare('UPDATE Character SET LevelUpPoint = LevelUpPoint + :lvlup WHERE Name = :char AND AccountId = :user');
            $stmt->execute([':lvlup' => $bonus, ':char' => $this->vars['character'], ':user' => $user]);
        }

        private function add_bonus_greset_points($user, $server){
            if($this->char_info['gres_info']['bonus_points_save'] == 1){
                $bonus = ($this->char_info['grand_resets'] + 1) * $this->bonus_points_by_class($this->char_info['Class'], 'gres_info');
            } else{
                $bonus = $this->bonus_points_by_class($this->char_info['Class'], 'gres_info');
            }
			if(defined('CRYSTALMU') && CRYSTALMU == 1){
				$bonusFromQuest = $this->website->db('game', $server)->query('SELECT QuestStat FROM Character WHERE Name = '.$this->website->db('game', $server)->escape($this->vars['character']).'')->fetch();
				if($bonusFromQuest != false){
					$bonus += $bonusFromQuest['QuestStat'];
				}
			}
            $stmt = $this->website->db('game', $server)->prepare('UPDATE Character SET LevelUpPoint = LevelUpPoint + :lvlup WHERE Name = :char AND AccountId = :user');
            $stmt->execute([':lvlup' => $bonus, ':char' => $this->vars['character'], ':user' => $user]);
        }

        private function add_bonus_stats_for_resets($user, $server){
            $stmt = $this->website->db('game', $server)->prepare('UPDATE Character SET LevelUpPoint = LevelUpPoint + :lvlup WHERE Name = :char AND AccountId = :user');
            $stmt->execute([':lvlup' => $this->char_info['bonus_reset_stats_points'], ':char' => $this->vars['character'], ':user' => $user]);
        }
		
		private function add_bonus_stats_for_gresets($user, $server){
            $stmt = $this->website->db('game', $server)->prepare('UPDATE Character SET LevelUpPoint = LevelUpPoint + :lvlup WHERE Name = :char AND AccountId = :user');
            $stmt->execute([':lvlup' => $this->char_info['bonus_greset_stats_points'], ':char' => $this->vars['character'], ':user' => $user]);
        }

        public function bonus_points_by_class($class, $type = 'res_info', $data = false){
            $char_info = ($data != false) ? $data : $this->Mcharacter->char_info;
			if(isset($char_info[$type]['bonus_points'][$class])){
				return $char_info[$type]['bonus_points'][$class];
			}
			return 0;
        }
		
        private function backup_master_level($user, $server){
            $stmt = $this->website->db('game', $server)->prepare('UPDATE Character SET Master = mLevel WHERE Name = :char AND AccountId = :user');
            $stmt->execute([':char' => $this->vars['character'], ':user' => $user]);
        }

        private function change_reset_class($user, $server){
            $stmt = $this->website->db('game', $server)->prepare('UPDATE Character SET Class = CASE WHEN Class IN(50, 66, 98) THEN Class - 2 ELSE Class - 1 END WHERE Name = :char AND AccountId = :user');
            $stmt->execute([':char' => $this->vars['character'], ':user' => $user]);
        }
		
		private function questToReadable($quest){
            $quest = substr($quest, 0, 100);
            if($quest == 'FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF0000FF00FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF'){
                return 0; //No quest
            } else if($quest == 'FAFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF000000FF00FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF'){
                return 1; //2ndQuestFinished
            } else if($quest == 'EAFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF000000FF00FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF'){
                return 2; //MarlonQuestFinished
            } else if($quest == 'AAFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF000000FF00FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF'){
                return 3; //DarkStoneFinished
            } else if($quest == 'AAEAFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF000000000000FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF'){
                return 4; //3rdQuestFinishedA
            } else if($quest == 'FFEAFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF000000000000FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF'){
                return 5; //3rdQuestFinishedB
            } else{
                return -1;
            }
        }
		
		public function getBaseStats($class, $server){
			$leadership = (MU_VERSION >= 1) ? ', Leadership' : '';
            switch($class){
                case 0:
                case 1:
                case 2:
                case 3:
                case 7:
				case 15:
                    return $this->website->db('game', $server)->query('SELECT TOP 1 Strength, Dexterity, Vitality, Energy '.$leadership.' FROM DefaultClassType WHERE Class = 0')->fetch();
                    break;
                case 16:
                case 17:
                case 18:
                case 19:
                case 23:
				case 31:
                    return $this->website->db('game', $server)->query('SELECT TOP 1 Strength, Dexterity, Vitality, Energy '.$leadership.' FROM DefaultClassType WHERE Class = 16')->fetch();
                    break;
                case 32:
                case 33:
                case 34:
                case 35:
                case 39:
				case 47:
                    return $this->website->db('game', $server)->query('SELECT TOP 1 Strength, Dexterity, Vitality, Energy '.$leadership.' FROM DefaultClassType WHERE Class = 32')->fetch();
                    break;
                case 48:
                case 49:
                case 50:
				case 51:
                case 54:
				case 62:
                    return $this->website->db('game', $server)->query('SELECT TOP 1 Strength, Dexterity, Vitality, Energy '.$leadership.' FROM DefaultClassType WHERE Class = 48')->fetch();
                    break;
                case 64:
                case 65:
                case 66:
				case 67:
                case 70:
				case 78:
                    return $this->website->db('game', $server)->query('SELECT TOP 1 Strength, Dexterity, Vitality, Energy, Leadership FROM DefaultClassType WHERE Class = 64')->fetch();
                    break;
                case 80:
                case 81:
                case 82:
                case 83:
				case 84:
                case 87:
				case 95:
                    return $this->website->db('game', $server)->query('SELECT TOP 1 Strength, Dexterity, Vitality, Energy, Leadership FROM DefaultClassType WHERE Class = 80')->fetch();
                    break;
                case 96:
                case 97:
                case 98:
				case 99:
                case 102:
				case 110:
                    return $this->website->db('game', $server)->query('SELECT TOP 1 Strength, Dexterity, Vitality, Energy, Leadership FROM DefaultClassType WHERE Class = 96')->fetch();
                    break;
                case 112:
                case 114:
				case 115:
                case 118:
				case 126:
                    return $this->website->db('game', $server)->query('SELECT TOP 1 Strength, Dexterity, Vitality, Energy, Leadership FROM DefaultClassType WHERE Class = 112')->fetch();
                    break;
                case 128:
                case 129:
				case 130:
                case 131:
                case 135:
				case 143:
                    return $this->website->db('game', $server)->query('SELECT TOP 1 Strength, Dexterity, Vitality, Energy, Leadership FROM DefaultClassType WHERE Class = 128')->fetch();
                    break;
				case 144:	
                case 145:
                case 147:
				case 151:
				case 159:
                    return $this->website->db('game', $server)->query('SELECT TOP 1 Strength, Dexterity, Vitality, Energy, Leadership FROM DefaultClassType WHERE Class = 144')->fetch();
                    break;
				case 160:	
                case 161:
                case 163:
				case 167:
				case 175:
                    return $this->website->db('game', $server)->query('SELECT TOP 1 Strength, Dexterity, Vitality, Energy, Leadership FROM DefaultClassType WHERE Class = 160')->fetch();
                    break;	
				case 176:	
                case 177:
                case 178:
				case 179:
				case 183:
				case 191:
                    return $this->website->db('game', $server)->query('SELECT TOP 1 Strength, Dexterity, Vitality, Energy, Leadership FROM DefaultClassType WHERE Class = 176')->fetch();
                    break;	
				case 192:	
                case 193:
                case 194:
				case 195:
				case 199:
				case 207:
                    return $this->website->db('game', $server)->query('SELECT TOP 1 Strength, Dexterity, Vitality, Energy, Leadership FROM DefaultClassType WHERE Class = 192')->fetch();
                    break;	
				case 208:	
                case 209:
				case 210:
                case 211:
				case 215:
				case 223:
                    return $this->website->db('game', $server)->query('SELECT TOP 1 Strength, Dexterity, Vitality, Energy, Leadership FROM DefaultClassType WHERE Class = 208')->fetch();
                    break;		
            }
        }

        public function calculateNewStats($server){
            $new_stats = 0;
            $this->defaultStats = $this->getBaseStats($this->char_info['Class'], $server);
            $quest = $this->questToReadable($this->char_info['Quest']);
            if(defined('CUSTOM_RESET_STATS') && CUSTOM_RESET_STATS == true){
                if(in_array($this->char_info['Class'], [0, 1, 2, 3, 7, 16, 17, 18, 19, 23, 32, 33, 34, 35, 39, 80, 81, 82, 83, 87])){
                    if($this->char_info['resets'] < 1){
                        $new_stats = 5 * ($this->char_info['cLevel'] - 1);
                        if($quest == 1)
                            $new_stats += 20;
                        if($quest == 2 || $quest == 3)
                            $new_stats = 5 * 219 + 6 * ($this->char_info['cLevel'] - 220) + 20;
                        if(in_array($this->char_info['Class'], [2, 3, 7, 18, 19, 23, 34, 35, 39, 82, 83, 87]))
                            $new_stats = 5 * 219 + 6 * ($this->char_info['cLevel'] - 220) + 90;
                    } else if($this->char_info['resets'] >= 1){
                        $new_stats = $this->char_info['resets'] * 1995;
                        if($quest == 1)
                            $new_stats += 20;
                        if($quest == 2 || $quest == 3)
                            $new_stats = 2175 + ($this->char_info['resets'] - 1) * 2394 + 20 + $this->char_info['cLevel'] * 6;
                        if(in_array($this->char_info['Class'], [2, 3, 8, 18, 19, 23, 34, 35, 39, 82, 83, 87]))
                            $new_stats = 2175 + ($this->char_info['resets'] - 1) * 2394 + 90 + $this->char_info['cLevel'] * 6;
                    } else{
                        $new_stats = 404;
                    }
                } else{
                    if($this->char_info['resets'] < 1){
                        $new_stats = 7 * ($this->char_info['cLevel'] - 1);
                        if(in_array($this->char_info['Class'], [49, 50, 54, 65, 66, 70, 97, 98, 102, 114, 118]))
                            $new_stats = 7 * ($this->char_info['cLevel'] - 1) + 70;
                    } else if($this->char_info['resets'] >= 1){
                        $new_stats = $this->char_info['resets'] * 2793;
                        if(in_array($this->char_info['Class'], [49, 50, 54, 65, 66, 70, 97, 98, 102, 114, 118]))
                            $new_stats = $this->char_info['resets'] * 2793 + 70 + $this->char_info['cLevel'] * 7;
                    } else{
                        $new_stats = 404;
                    }
                }
            } else{
                if($this->char_info['Strength'] > $this->defaultStats['Strength']){
                    $new_stats += $this->char_info['Strength'] - $this->defaultStats['Strength'];
                }
                if($this->char_info['Dexterity'] > $this->defaultStats['Dexterity']){
                    $new_stats += $this->char_info['Dexterity'] - $this->defaultStats['Dexterity'];
                }
                if($this->char_info['Energy'] > $this->defaultStats['Energy']){
                    $new_stats += $this->char_info['Energy'] - $this->defaultStats['Energy'];
                }
                if($this->char_info['Vitality'] > $this->defaultStats['Vitality']){
                    $new_stats += $this->char_info['Vitality'] - $this->defaultStats['Vitality'];
                }
                if(in_array($this->char_info['Class'], [64, 65, 66, 67, 70, 78]) && $this->char_info['Leadership'] > $this->defaultStats['Leadership']){
                    $new_stats += $this->char_info['Leadership'] - $this->defaultStats['Leadership'];
                }
            }
            return $new_stats;
        }

        public function reset_stats($user, $server, $character = '', $freePoints = false, $baseStats = false){
			if($character != ''){
				$this->vars['character'] = $character;
			}
			if($freePoints !== false){
				$this->defaultStats = $baseStats;
				$stats = $freePoints;
			}
			else{
				$stats = $this->calculateNewStats($server);
			}
            if(defined('CUSTOM_RESET_STATS') && CUSTOM_RESET_STATS == true){
                $lvl_up = ':lvlUp';
            } else{
                $lvl_up = 'LevelUpPoint + :lvlUp';
            }
            if(in_array($this->char_info['Class'], [64, 65, 66, 67, 70, 78])){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE Character SET LevelUpPoint = ' . $lvl_up . ', Strength = ' . $this->defaultStats['Strength'] . ', Dexterity = ' . $this->defaultStats['Dexterity'] . ', Vitality = ' . $this->defaultStats['Vitality'] . ', Energy = ' . $this->defaultStats['Energy'] . ', Leadership = ' . $this->defaultStats['Leadership'] . ' WHERE Name = :char AND AccountId = :user');
            } else{
                $stmt = $this->website->db('game', $server)->prepare('UPDATE Character SET LevelUpPoint = ' . $lvl_up . ', Strength = ' . $this->defaultStats['Strength'] . ', Dexterity = ' . $this->defaultStats['Dexterity'] . ', Vitality = ' . $this->defaultStats['Vitality'] . ', Energy = ' . $this->defaultStats['Energy'] . ' WHERE Name = :char AND AccountId = :user');
            }
            $stmt->execute([':lvlUp' => $stats, ':char' => $this->vars['character'], ':user' => $user]);
        }
		
		public function setNewResGR($user, $server, $name, $gr, $res, $level){
			 $stmt = $this->website->db('game', $server)->prepare('UPDATE Character SET ' . $this->config->values('table_config', [$server, 'grand_resets', 'column']) . ' = :newgr, ' . $this->config->values('table_config', [$server, 'resets', 'column']) . ' = :newres, cLevel = :newlvl WHERE Name = :char AND AccountId = :user');
			 $stmt->execute([':newgr' => $gr, ':newres' => $res, ':newlvl' => $level, ':char' => $name, ':user' => $user]);
		}
		
		public function reset_skill_tree($user, $server, $type, $reset_level = 1, $reset_points = 1, $points_multiplier = 1){
            switch($type){
                default:
                    return false;
                    break;
                case 'igcn':
                    return $this->reset_skill_tree_igcn($user, $server, $reset_level, $reset_points, $points_multiplier);
                    break;
                case 'muengine':
                    return $this->reset_skill_tree_muengine($user, $server, $reset_level, $reset_points, $points_multiplier);
                    break;
                case 'xteam':
                    return $this->reset_skill_tree_xteam($user, $server, $reset_level, $reset_points, $points_multiplier);
                    break;
            }
        }
		
		public function reset_skill_tree_igcn($user, $server, $reset_level = 1, $reset_points = 1, $points_multiplier = 1){
            if($reset_points == 0){
                $this->ml_points = $this->get_master_level_igcn($user, $server);
            }
			
            if($reset_level == 0){
                $this->ml_level = $this->get_master_level_igcn($user, $server);
                $ml_exp = '';
            } 
			else{
                $ml_exp = ',  mlExperience = 0, mlNextExp = 35507050';
            }
			
			$query_enchancement = '';
			
			$skill_size = 6;
			$empty = 'ff0000';
			if(MU_VERSION >= 9){
				$enchancement_points = 0;
				if($this->ml_points > 400){
					$enchancement_points = $this->ml_points - 400;
					$enchancement_points = $enchancement_points * $points_multiplier;
					$this->ml_points = 400;
				}
				$query_enchancement = ', i4thSkillPoint = '.$enchancement_points.', AddStrength = 0, AddDexterity = 0, AddVitality = 0, AddEnergy = 0';
				$skill_size = 10;
				$empty = 'ff00000000';
			}
			
			if($reset_points == 0){
				$this->ml_points = $this->ml_points * $points_multiplier;
			}
			
            $this->get_skill_list($user, $server);
            $skills_array = str_split($this->char_info['MagicList'], $skill_size);
            foreach($skills_array AS $key => $skill){
                $index = $this->skill_index($skill);
                if($this->is_master_skill($index)){
                    $skills_array[$key] = $empty;
                }
            }
			
            $stmt = $this->website->db('game', $server)->prepare('UPDATE Character SET mLevel = ' . $this->ml_level . $ml_exp . ', mlPoint = ' . $this->ml_points . ', MagicList = 0x' . implode('', $skills_array) . ' '.$query_enchancement.' WHERE Name = :char AND AccountId = :user');
            return $stmt->execute([':char' => $this->vars['character'], ':user' => $user]);
        }
		
		public function reset_skill_tree_muengine($user, $server, $reset_level = 1, $reset_points = 1, $points_multiplier = 1){
            $stmt = $this->website->db('game', $server)->prepare('UPDATE Character SET MagicList = CAST(REPLICATE(char(0xff), 180) AS varbinary(180)) WHERE Name = :char');
            $stmt->execute([':char' => $this->vars['character']]);
            if($reset_points == 0){
                $this->ml_points = ($this->get_master_level_muengine($server) * $points_multiplier);
            }
            if($reset_level == 0){
                $this->ml_level = $this->get_master_level_muengine($server);
                $ml_exp = '';
            } else{
                $ml_exp = ', ML_EXP = 0,  ML_NEXTEXP = 35507050';
            }
            $stmt = $this->website->db('game', $server)->prepare('UPDATE T_MasterLevelSystem SET MASTER_LEVEL = ' . $this->ml_level . $ml_exp . ', ML_POINT = ' . $this->ml_points . ' WHERE CHAR_NAME = :char');
            return $stmt->execute([':char' => $this->vars['character']]);
        }
		
		public function reset_skill_tree_xteam($user, $server, $reset_level = 1, $reset_points = 1, $points_multiplier = 1){
            if($reset_points == 0){
                $this->ml_points = $this->get_master_level_xteam($server);
            }
   
            if($reset_level == 0){
                $this->ml_level = $this->get_master_level_xteam($server);
                $ml_exp = '';
            } else{
                $ml_exp = ', MasterExperience = 0';
            }
			
			if(MU_VERSION >= 9){
				$enchancement_points = 0;
				if($this->ml_points > 400){
					$enchancement_points = $this->ml_points - 400;
					$enchancement_points = $enchancement_points * $points_multiplier;
					$this->ml_points = 400;
				}
				
				$stmt1 = $this->website->db('game', $server)->prepare('UPDATE EnhanceSkillTree SET EnhancePoint = ' . $enchancement_points . ', EnhanceSkill = NULL, EnhanceSkillPassive = NULL WHERE Name = :char');
				$stmt1->execute([':char' => $this->vars['character']]);
			}
			
			 if($reset_points == 0){
                $this->ml_points = $this->ml_points * $points_multiplier;
            }
			
			$skill_size = 6;
			$empty = 'ff0000';
			$this->get_skill_list($user, $server);
			$skills_array = str_split($this->char_info['MagicList'], $skill_size);
            foreach($skills_array AS $key => $skill){
                $index = $this->skill_index($skill);
                if($this->is_master_skill($index)){
                    $skills_array[$key] = $empty;
                }
            }
			
			$rSkills = $this->website->db('game', $server)->prepare('UPDATE Character SET MagicList = 0x' . implode('', $skills_array) . ' WHERE Name = :char AND AccountId = :user');
            $rSkills->execute([':char' => $this->vars['character'], ':user' => $user]);

			$stmt = $this->website->db('game', $server)->prepare('UPDATE MasterSkillTree SET MasterLevel = ' . $this->ml_level . $ml_exp . ', MasterPoint = ' . $this->ml_points . ', MasterSkill = NULL WHERE Name = :char');
            return $stmt->execute([':char' => $this->vars['character']]);
        }


        private function get_master_level_muengine($server){
            $stmt = $this->website->db('game', $server)->prepare('SELECT MASTER_LEVEL FROM T_MasterLevelSystem WHERE CHAR_NAME = :char');
            $stmt->execute([':char' => $this->vars['character']]);
            $points = $stmt->fetch();
            return $points['MASTER_LEVEL'];
        }

        private function get_master_level_igcn($user, $server){
            $stmt = $this->website->db('game', $server)->prepare('SELECT mLevel FROM Character WHERE AccountId = :user AND Name = :char');
            $stmt->execute([':user' => $user, ':char' => $this->vars['character']]);
            $points = $stmt->fetch();
            if($points != false){
                return $points['mLevel'];
            }
            return 0;
        }

        private function get_master_level_xteam($server){
            $stmt = $this->website->db('game', $server)->prepare('SELECT MasterLevel FROM MasterSkillTree WHERE Name = :char');
            $stmt->execute([':char' => $this->vars['character']]);
            $points = $stmt->fetch();
            return $points['MasterLevel'];
        }
		
		private function is_master_skill($skill_id){
            static $SkillList = null;
            $is_master_skill = false;
            libxml_use_internal_errors(true);
            if($SkillList == null)
                $SkillList = simplexml_load_file(APP_PATH . DS . 'data' . DS . 'ServerData' . DS . 'SkillList.xml');
            if($SkillList === false){
                $err = 'Failed loading XML<br>';
                foreach(libxml_get_errors() as $error){
                    $err .= $error->message . '<br>';
                }
                writelog('[Server File Parser] Unable to parse xml file: ' . $err, 'system_error');
            }
            $skill_data = $SkillList->xpath("//SkillList/Skill[@Index='" . $skill_id . "']");
            if(!empty($skill_data)){
                if(in_array((string)$skill_data[0]->attributes()->UseType, [3,4,7,8,9,10,11])){
                    $is_master_skill = true;
                }
            }
            return $is_master_skill;
        }
		
		private function skill_index($hex){
			$len = strlen($hex);
            $id = hexdec(substr($hex, 0, 2));
			$id2 = hexdec(substr($hex, 2, 2));
			$id3 = hexdec(substr($hex, 4, 2));
			if($len == 10){
				$id4 = hexdec(substr($hex, 6, 2));
				$id5 = hexdec(substr($hex, 8, 2));
			}
			
			$idx = ($id2 & 255) | ($id & 255) << 8;
            return $idx;
        }

		private function get_skill_list($user, $server){
            $stmt = $this->website->db('game', $server)->prepare('SELECT CONVERT(IMAGE, MagicList) AS MagicList FROM Character WHERE Name = :char AND AccountId = :user');
            $stmt->execute([':char' => $this->vars['character'], ':user' => $user]);
			$skills = unpack('H*', implode('', $stmt->fetch()));
            $this->char_info['MagicList'] = $this->website->clean_hex($skills[1]);
        }

        public function check_pk(){
            if($this->char_info['PkLevel'] <= 3){
                return false;
            }
            return true;
        }

        public function clear_pk($user, $server, $money = 0){
            $stmt = $this->website->db('game', $server)->prepare('UPDATE Character SET PkLevel = 3, PkCount = 0, Money = Money - :money WHERE Name = :char AND AccountId = :user');
            $stmt->execute([':money' => $money, ':char' => $this->vars['character'], ':user' => $user]);
        }

        public function teleport_char($user, $server, $cords, $money){
            $stmt = $this->website->db('game', $server)->prepare('UPDATE Character SET MapNumber = :world, MapPosX = :x, MapPosY = :y WHERE Name = :char AND AccountId = :user');
            $stmt->execute([':world' => $this->vars['world'], ':x' => $cords[0], ':y' => $cords[1], ':char' => $this->vars['character'], ':user' => $user]);
			if($money > 0){
				$this->decrease_zen($user, $server, $money, $this->vars['character']);
			}
        }

        public function update_level($user, $server){
            $stmt = $this->website->db('game', $server)->prepare('UPDATE Character SET cLevel = :new_level WHERE Name = :char AND AccountId = :user');
            $stmt->execute([':new_level' => $this->char_info['cLevel'] + (int)$this->vars['level'], ':char' => $this->vars['character'], ':user' => $user]);
            return true;
        }

        public function update_points($user, $server){
            $stmt = $this->website->db('game', $server)->prepare('UPDATE Character SET LevelUpPoint = LevelUpPoint + :new_point WHERE Name = :char AND AccountId = :user');
            $stmt->execute([':new_point' => (int)$this->vars['points'], ':char' => $this->vars['character'], ':user' => $user]);
            return true;
        }

        public function update_gm($user, $server){
            $stmt = $this->website->db('game', $server)->prepare('UPDATE Character SET CtlCode = :ctlcode WHERE Name = :char AND AccountId = :user');
            $stmt->execute([':ctlcode' => $this->config->config_entry('buygm|gm_ctlcode'), ':char' => $this->vars['character'], ':user' => $user]);
            return true;
        }
		
		public function add_wcoins($user, $server, $uid, $amount = 0, $config = []){
            $acc = (in_array($config['identifier_column'], ['MemberGuid', 'memb_guid'])) ? $uid : $user;
            $stmt = $this->website->db($config['db'], $server)->prepare('UPDATE ' . $config['table'] . ' SET ' . $config['column'] . ' = ' . $config['column'] . ' + :wcoins WHERE ' . $config['identifier_column'] . ' = :account');
            $stmt->execute([':wcoins' => $amount, ':account' => $acc]);
            if($stmt->rows_affected() == 0){
                $stmt = $this->website->db($config['db'], $server)->prepare('INSERT INTO ' . $config['table'] . ' (' . $config['identifier_column'] . ', ' . $config['column'] . ') values (:user, :wcoins)');
                $stmt->execute([':user' => $acc, ':wcoins' => $amount]);
            }
        }
		
		public function remove_wcoins($user, $server, $uid, $config = [], $amount = false){
			if($amount != false){
				$this->vars['credits'] = $amount;
			}
            $acc = (in_array($config['identifier_column'], ['MemberGuid', 'memb_guid'])) ? $uid : $user;
            $stmt = $this->website->db($config['db'], $server)->prepare('UPDATE ' . $config['table'] . ' SET ' . $config['column'] . ' = ' . $config['column'] . ' - :wcoins WHERE ' . $config['identifier_column'] . ' = :account');
            $stmt->execute([':wcoins' => $this->vars['credits'], ':account' => $acc]);
        }
		
		public function get_wcoins($user, $uid, $config, $server){
            $acc = (in_array($config['identifier_column'], ['MemberGuid', 'memb_guid'])) ? $uid : $user;
            $stmt = $this->website->db($config['db'], $server)->prepare('SELECT ' . $config['column'] . ' FROM ' . $config['table'] . ' WHERE ' . $config['identifier_column'] . ' = :account');
            $stmt->execute([':account' => $acc]);
            if($wcoins = $stmt->fetch()){
                return $wcoins[$config['column']];
            }
            return false;
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

		public function load_character_info($char, $server, $by_id = false){
			$ruud = ', 0 AS Ruud';
			if(MU_VERSION >= 5){
				$ruud = ', Ruud';
			}
			if(MU_VERSION >= 12){
				if($this->website->db('game', $server)->check_if_column_exists('RuudMoney', 'Character') != false){
					$ruud = ', RuudMoney AS Ruud';
				}
				else{
					$ruud = ', Ruud';
				}
			}
			$zs = '';
			//if($this->website->db('game', $server)->check_if_column_exists('zs_count', 'Character') != false){
			//	$zs = ', zs_count, zs';
			//}
			$leadership = (MU_VERSION >= 1) ? 'Leadership,' : '0 AS Leadership,';
			$QuestIndex = (defined('CRYSTALMU')) ? ', QuestIndex' : '';
			
            $where = ($by_id == true) ? $this->website->get_char_id_col($server) .'  = :char' : 'Name = :char';
            $stmt = $this->website->db('game', $server)->prepare('SELECT TOP 1 AccountId, Name, Money, Class, cLevel, ' . $this->reset_column($server) . $this->greset_column($server) . ' LevelUpPoint, Strength, Dexterity, Vitality, Energy, '.$leadership.' MapNumber, MapPosX, MapPosY, PkLevel, PkCount, CtlCode, CONVERT(IMAGE, Inventory) AS Inventory '.$ruud.$zs.$QuestIndex.' FROM Character WHERE ' . $where . '');
            $stmt->execute([':char' => $char]);
            if($this->char_info = $stmt->fetch()){
                $this->char_info['mlevel'] = $this->load_master_level($this->char_info['Name'], $server);
				$unpack = unpack('H*', $this->char_info['Inventory']);
				$this->char_info['Inventory'] = $this->website->clean_hex($unpack[1]);
                return true;
            }
            return false;
        }

		public function get_inventory_content($char, $server){
			$stmt = $this->website->db('game', $server)->prepare('SELECT CONVERT(IMAGE, Inventory) AS Inventory FROM Character WHERE Name = :char');
			$stmt->execute([':char' => $char]);
			if($inv = $stmt->fetch()){
				$unpack = unpack('H*', $inv['Inventory']);
				$this->char_info['Inventory'] = $this->website->clean_hex($unpack[1]);
			} 
        }
		
		public function load_equipment($server){
			$item_size = $this->website->get_value_from_server($server, 'item_size');
            $items_array = str_split($this->Mcharacter->char_info['Inventory'], $item_size);
            $eq = array_chunk($items_array, 12);
            $equipment = [];
            foreach($eq[0] as $key => $item){
                if($item != str_pad("", $item_size, "F")){
                    $this->iteminfo->itemData($item, true, $server);
                    $equipment[$key]['item_id'] = $this->iteminfo->id;
                    $equipment[$key]['item_cat'] = $this->iteminfo->type;
                    $equipment[$key]['name'] = $this->iteminfo->realName();
                    $equipment[$key]['level'] = (int)substr($this->iteminfo->getLevel(), 1);
                    $equipment[$key]['hex'] = $item;
					$equipment[$key]['item_info'] = $this->iteminfo->allInfo();
					$equipment[$key]['socket'] = $this->iteminfo->socket;
                } 
				else{
                    $equipment[$key] = 0;
                }
            }
			if(isset($items_array[236]) && $items_array[236] != str_pad("", $item_size, "F")){
                $this->iteminfo->itemData($items_array[236], true, $server);
                $equipment[12]['item_id'] = $this->iteminfo->id;
                $equipment[12]['item_cat'] = $this->iteminfo->type;
                $equipment[12]['name'] = $this->iteminfo->realName();
                $equipment[12]['level'] = (int)substr($this->iteminfo->getLevel(), 1);
                $equipment[12]['hex'] = $items_array[236];
				$equipment[12]['item_info'] = $this->iteminfo->allInfo();
				$equipment[12]['socket'] = $this->iteminfo->socket;
            }
            if(isset($items_array[237]) && $items_array[237] != str_pad("", $item_size, "F")){
                $this->iteminfo->itemData($items_array[237], true, $server);
                $equipment[13]['item_id'] = $this->iteminfo->id;
                $equipment[13]['item_cat'] = $this->iteminfo->type;
                $equipment[13]['name'] = $this->iteminfo->realName();
                $equipment[13]['level'] = (int)substr($this->iteminfo->getLevel(), 1);
                $equipment[13]['hex'] = $items_array[237];
				$equipment[13]['item_info'] = $this->iteminfo->allInfo();
				$equipment[13]['socket'] = $this->iteminfo->socket;
            }
            if(isset($items_array[238]) && $items_array[238] != str_pad("", $item_size, "F")){
                $this->iteminfo->itemData($items_array[238], true, $server);
                $equipment[14]['item_id'] = $this->iteminfo->id;
                $equipment[14]['item_cat'] = $this->iteminfo->type;
                $equipment[14]['name'] = $this->iteminfo->realName();
                $equipment[14]['level'] = (int)substr($this->iteminfo->getLevel(), 1);
                $equipment[14]['hex'] = $items_array[238];
				$equipment[14]['item_info'] = $this->iteminfo->allInfo();
				$equipment[14]['socket'] = $this->iteminfo->socket;
            }													   
            return $equipment;
        }

		public function load_inventory($inv, $server){
			$item_size = $this->website->get_value_from_server($server, 'item_size');
            $items_array = str_split($this->Mcharacter->char_info['Inventory'], $item_size);
            $inventory = [];
            $items = [];
            $loop = [12, 76]; //default inv
            if($inv == 2)
                $loop = [76, 108]; //store
            if($inv == 3)
                $loop = [108, 140]; //exp inv 1
            if($inv == 4)
                $loop = [204, 236]; //exp inv 2
            for($a = $loop[0]; $a < $loop[1]; $a++){
                $inventory[$a] = !empty($items_array[$a]) ? $items_array[$a] : str_pad("", $item_size, "F");
            }
            $i = 0;
            $x = 0;
            $y = 0;
            foreach($inventory as $item){
                $i++;
                if($item != str_pad("", $item_size, "F")){
                    $this->iteminfo->itemData($item, true, $server);
                    $items[$i]['item_id'] = $this->iteminfo->id;
                    $items[$i]['item_cat'] = $this->iteminfo->type;
                    $items[$i]['name'] = $this->iteminfo->realName();
                    $items[$i]['level'] = (int)substr($this->iteminfo->getLevel(), 1);
                    $items[$i]['x'] = $this->iteminfo->getX();
                    $items[$i]['y'] = $this->iteminfo->getY();
                    $items[$i]['xx'] = $x;
                    $items[$i]['yy'] = $y;
                    $items[$i]['hex'] = $item;
					$items[$i]['item_info'] = $this->iteminfo->allInfo();
                } else{
                    $items[$i]['xx'] = $x;
                    $items[$i]['yy'] = $y;
                }
                $x++;
                if($x >= 8){
                    $x = 0;
                    $y++;
                }
            }
            return $items;
        }

		public function clear_inv($user, $server){
			$item_size = $this->website->get_value_from_server($server, 'item_size');
            $items_array = str_split($this->Mcharacter->char_info['Inventory'], $item_size);
            if(isset($this->vars['inventory'])){
                for($a = 12; $a < 76; $a++){
                    $items_array[$a] = str_repeat('F', $item_size);
                }
            }
            if(isset($this->vars['equipment'])){
                for($a = 0; $a < 12; $a++){
                    $items_array[$a] = str_repeat('F', $item_size);
                }
            }
            if(isset($this->vars['store'])){
                for($a = 204; $a < 236; $a++){
                    $items_array[$a] = str_repeat('F', $item_size);
                }
            }
            if(isset($this->vars['exp_inv_1'])){
                for($a = 76; $a < 108; $a++){
                    $items_array[$a] = str_repeat('F', $item_size);
                }
            }
            if(isset($this->vars['exp_inv_2'])){
                for($a = 108; $a < 140; $a++){
                    $items_array[$a] = str_repeat('F', $item_size);
                }
            }
            $stmt = $this->website->db('game', $server)->prepare('UPDATE Character SET Inventory = 0x' . implode('', $items_array) . ' WHERE Name = :char AND AccountId = :user');
            $stmt->execute([':char' => $this->vars['character'], ':user' => $user]);
        }

        public function check_equipment($server){
			$item_size = $this->website->get_value_from_server($server, 'item_size');
            return (strtoupper(substr($this->char_info['Inventory'], 0, $item_size * 12)) === str_repeat('F', $item_size * 12));
        }
		
		public function check_store($server){
			$item_size = $this->website->get_value_from_server($server, 'item_size');
            return (strtoupper(substr($this->char_info['Inventory'], $item_size * 204, $item_size * 32)) === str_repeat('F', $item_size * 32));
        }
		
		public function check_inventory($server){
			$item_size = $this->website->get_value_from_server($server, 'item_size');
            return (strtoupper(substr($this->char_info['Inventory'], $item_size * 12, $item_size * 64)) === str_repeat('F', $item_size * 64));
        }
		
		public function check_exp_inv1($server){
			$item_size = $this->website->get_value_from_server($server, 'item_size');
            return (strtoupper(substr($this->char_info['Inventory'], $item_size * 76, $item_size * 32)) === str_repeat('F', $item_size * 32));
        }
		
		public function check_exp_inv2($server){
			$item_size = $this->website->get_value_from_server($server, 'item_size');
            return (strtoupper(substr($this->char_info['Inventory'], $item_size * 108, $item_size * 32)) === str_repeat('F', $item_size * 32));
        }

        public function gen_class_select_field($config = false){
            if($config != false){
				if(isset($config[$this->char_info['Class']])){
					$select = '<option disabled="disabled" selected="selected" value="">- SELECT -</option>';
                    foreach($config[$this->char_info['Class']] AS $class){
                        $select .= '<option value="' . $class . '">' . $this->website->get_char_class($class) . '</option>';
                    }
					return $select;
				}
            }
            return false;
        }

        public function update_char_class($user, $server){
            $stmt = $this->website->db('game', $server)->prepare('UPDATE Character SET Class = :class WHERE Name = :char AND AccountId = :user');
            $stmt->execute([':class' => $this->vars['class_select'], ':char' => $this->vars['character'], ':user' => $user]);
            return true;
        }
		
		public function clear_quests_list($user, $server, $class){
            $stmt = $this->website->db('game', $server)->prepare('UPDATE Character SET Quest = (SELECT TOP 1 Quest FROM Character WHERE Class = :class) WHERE Name = :char AND AccountId = :user');
            $stmt->execute([':class' => $class, ':char' => $this->vars['character'], ':user' => $user]);
        }
		
		public function count_change_class_times($server, $char_id){
			$stmt = $this->website->db('web')->prepare('SELECT COUNT(id) AS count FROM DmN_Change_Class_Log WHERE char_id = :char AND server = :server');
			$stmt->execute([':char' => $char_id, ':server' => $server]);
			return $stmt->fetch();
		}
		
		public function add_change_class_log($server, $char_id, $old, $new){
			$stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Change_Class_Log (char_id, server, old, new) VALUES (:char, :server, :old, :new)');
			$stmt->execute([':char' => $char_id, ':server' => $server, ':old' => $old, ':new' => $new]);
		}

        public function get_status($name, $server){
            $accountDb = ($this->website->is_multiple_accounts() == true) ? $this->website->get_db_from_server($server, true) : $this->website->get_default_account_database();
            $stmt = $this->website->db('game', $server)->prepare('SELECT TOP 1 a.Id, a.GameIDC, m.ConnectStat, m.ConnectTM, m.DisConnectTM, m.IP, m.ServerName FROM AccountCharacter AS a RIGHT JOIN [' . $accountDb . '].dbo.MEMB_STAT AS m ON (a.Id Collate Database_Default = m.memb___id) WHERE m.memb___id = :user ' . $this->website->server_code($this->website->get_servercode($server)) . '');
            $stmt->execute([':user' => $name]);
            return $stmt->fetch();
        }

        public function load_chars($name, $server){
            $stmt = $this->website->db('game', $server)->prepare('SELECT Name FROM Character WHERE AccountId = :user');
            $stmt->execute([':user' => $name]);
            return $stmt->fetch_all();
        }

        public function check_guild($name, $server){
            $stmt = $this->website->db('game', $server)->prepare('SELECT G_Name FROM GuildMember WHERE Name = :char');
            $stmt->execute([':char' => $name]);
            return $stmt->fetch();
        }

        public function load_guild_info($g_name, $server){
            $stmt = $this->website->db('game', $server)->prepare('SELECT G_Mark, G_Master FROM Guild WHERE G_Name = :g_name');
            $stmt->execute([':g_name' => $g_name]);
            return $stmt->fetch();
        }

        public function guild_member_count($g_name, $server){
            $stmt = $this->website->db('game', $server)->prepare('SELECT COUNT(Name) AS count FROM GuildMember WHERE G_Name = :g_name');
            $stmt->execute([':g_name' => $g_name]);
            return $stmt->fetch();
        }

        public function add_account_log($log, $credits, $acc, $server){
            $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Account_Logs (text, amount, date, account, server, ip) VALUES (:text, :amount, GETDATE(), :acc, :server, :ip)');
            $stmt->execute([':text' => $log, ':amount' => round($credits), ':acc' => $acc, ':server' => $server, ':ip' => ip()]);
            $stmt->close_cursor();
        }
		
		public function get_guild_info($guild, $server){
			$g_union = (MU_VERSION < 1) ? '' : ', Number, G_Union';
            $stmt = $this->website->db('game', $server)->prepare('SELECT G_Name, G_Master, G_Mark, G_Score '.$g_union.' FROM Guild WHERE G_Name = :name');
            $stmt->execute([':name' => $guild]);
            if($row = $stmt->fetch()){
                $membercount = $this->website->db('game', $server)->snumrows('SELECT COUNT(Name) as count FROM GuildMember WHERE G_Name = '.$this->website->db('game', $server)->escape($guild).'');
                $union = '';
                $hostility = '';
				if($g_union != ''){
					if($row['G_Union'] != 0){
						$stmt = $this->website->db('game', $server)->prepare('SELECT G_Name FROM Guild WHERE G_Union = :number AND G_Name != :name');
						$stmt->execute([':number' => $row['G_Union'], ':name' => $guild]);
						while($row2 = $stmt->fetch()){
							$union .= '<a href="' . $this->config->base_url . 'info/guild/' . bin2hex($row2['G_Name']) . '/' . $server . '">' . $row2['G_Name'] . '</a>,';
						}
					}
				}
                return [
					'G_Name' => $row['G_Name'], 
					'G_Master' => $row['G_Master'], 
					'G_Mark' => urlencode(bin2hex($row['G_Mark'])), 
					'G_Score' => (int)$row['G_Score'], 
					'MemberCount' => $membercount, 
					'aliance_guilds' => ($union != '') ? substr($union, 0, -1) : 'N/A'
				];
            }
            return false;
        }
		
		private function checkStatus($acc, $server){
			$stmt = $this->website->db('account', $server)->prepare('SELECT ConnectStat, IP FROM MEMB_STAT WHERE memb___id = :user');
			$stmt->execute([':user' => $acc]);
			if($status = $stmt->fetch()){
				return $status;
			}
			return false;
        }

		public function get_guild_members($guild, $server){
			$g_status = (MU_VERSION < 1) ? '0 AS G_Status' : 'g.G_Status';
			$order = (MU_VERSION < 1) ? 'g.Name ASC' : 'g.G_Status DESC';
			$stmt = $this->website->db('game', $server)->prepare('SELECT g.Name, '.$g_status.', c.Class, c.cLevel, c.AccountId, ' . $this->reset_column($server) . ' ' . substr_replace($this->greset_column($server), '', -1) . ' FROM GuildMember AS g INNER JOIN Character AS c ON (g.Name Collate Database_Default = c.Name Collate Database_Default) WHERE g.G_Name = :name ORDER BY '.$order);
            $stmt->execute([':name' => $guild]);
            $data = $stmt->fetch_all();
			foreach($data AS $id => $row){
				$status = $this->checkStatus($row['AccountId'], $server);
				if(!$status){
					$connectStat = 0;
					$cntrCode = 'us';
				}
				else{
					$connectStat = ($status['ConnectStat'] == 1) ? 1 : 0;
					$cntrCode = $this->website->get_country_code($status['IP']);
				}
	
                $this->guild_info[] = [
					'name' => $row['Name'], 
					'position' => $this->website->get_guild_status($row['G_Status']), 
					'level' => $row['cLevel'], 
					'mlevel' => $this->load_master_level($row['Name'], $server),
					'resets' => $row['resets'], 
					'gresets' => $row['grand_resets'], 
					'class' => $this->website->get_char_class($row['Class'], true),
					'status' => $connectStat,
					'country' => $cntrCode,
					
				];
            }
            return $this->guild_info;
        }

        public function decrease_zen($account, $server, $money, $char){
            $stmt = $this->website->db('game', $server)->prepare('UPDATE Character SET Money = Money - :money WHERE AccountId = :account AND Name = :char');
            return $stmt->execute([':money' => (int)$money, ':account' => $account, ':char' => $char]);
        }

        public function add_zen($account, $server, $money, $char){
            $stmt = $this->website->db('game', $server)->prepare('UPDATE Character SET Money = Money + :money WHERE AccountId = :account AND Name = :char');
            return $stmt->execute([':money' => (int)$money, ':account' => $account, ':char' => $char]);
        }

        public function load_chars_from_ref($ref_acc, $server){
            $stmt = $this->website->db('game', $server)->prepare('SELECT Name, cLevel, ' . $this->reset_column($server) . substr_replace($this->greset_column($server), '', -1) . ' FROM Character WHERE AccountId = :ref_acc');
            $stmt->execute([':ref_acc' => $ref_acc]);
            $char_list = [];
            while($row = $stmt->fetch()){
                $char_list[] = ['Name' => $row['Name'], 'cLevel' => $row['cLevel'], 'resets' => $row['resets'], 'grand_resets' => $row['grand_resets'], 'mlevel' => $this->load_master_level($row['Name'], $server)];
            }
            return $char_list;
        }

		public function load_master_level($char, $server){
            if($this->config->values('table_config', [$server, 'master_level', 'column']) != false){
                $stmt = $this->website->db('game', $server)->prepare('SELECT ' . $this->config->values('table_config', [$server, 'master_level', 'column']) . ' AS mlevel FROM ' . $this->config->values('table_config', [$server, 'master_level', 'table']) . ' WHERE ' . $this->config->values('table_config', [$server, 'master_level', 'identifier_column']) . ' = :char');
                $stmt->execute([':char' => $char]);
                $mlevel = $stmt->fetch();
                $stmt->close_cursor();
                if($mlevel != false){
                    return $mlevel['mlevel'];
                }
            }
            return 0;
        }

        public function check_if_char_exists($char, $server){
            $stmt = $this->website->db('game', $server)->prepare('SELECT Name, AccountId, '.$this->website->get_char_id_col($server).' AS id FROM Character WHERE Name = :name');
            $stmt->execute([':name' => $char]);
            return $stmt->fetch();
        }

        public function has_guild($char, $server){
            $stmt = $this->website->db('game', $server)->prepare('SELECT Name FROM GuildMember WHERE Name = :name');
            $stmt->execute([':name' => $char]);
            return $stmt->fetch();
        }

		public function update_account_character($old, $new, $server){
			$old = $this->website->db('game', $server)->escape($old);
			$new = $this->website->db('game', $server)->escape($new);
			$data = 'UPDATE AccountCharacter SET 
					GameIDC = CASE WHEN (GameIDC = ' . $old . ') THEN ' . $new . ' ELSE GameIDC END,
					GameId1 = CASE WHEN (GameId1 = ' . $old . ') THEN ' . $new . ' ELSE GameId1 END,
					GameId2 = CASE WHEN (GameId2 = ' . $old . ') THEN ' . $new . ' ELSE GameId2 END,
					GameId3 = CASE WHEN (GameId3 = ' . $old . ') THEN ' . $new . ' ELSE GameId3 END,
					GameId4 = CASE WHEN (GameId4 = ' . $old . ') THEN ' . $new . ' ELSE GameId4 END,
					GameId5 = CASE WHEN (GameId5 = ' . $old . ') THEN ' . $new . ' ELSE GameId5 END';
			if(MU_VERSION >= 9){
				$data .= ', GameId6 = CASE WHEN (GameId6 = ' . $old . ') THEN ' . $new . ' ELSE GameId6 END, GameId7 = CASE WHEN (GameId7 = ' . $old . ') THEN ' . $new . ' ELSE GameId7 END, GameId8 = CASE WHEN (GameId8 = ' . $old . ') THEN ' . $new . ' ELSE GameId8 END';
			}	
			if(MU_VERSION >= 10){
				$data .= ', GameId9 = CASE WHEN (GameId9 = ' . $old . ') THEN ' . $new . ' ELSE GameId9 END, GameId10 = CASE WHEN (GameId10 = ' . $old . ') THEN ' . $new . ' ELSE GameId10 END';
			}				
            return $this->website->db('game', $server)->query($data);
        }

        public function update_guild($old, $new, $server){
            $stmt = $this->website->db('game', $server)->prepare('UPDATE Guild SET G_Master = :name WHERE G_Master = :old_name');
            return $stmt->execute([':name' => $new, ':old_name' => $old]);
        }

        public function update_guild_member($old, $new, $server){
            $stmt = $this->website->db('game', $server)->prepare('UPDATE GuildMember SET Name = :name WHERE Name = :old_name');
            return $stmt->execute([':name' => $new, ':old_name' => $old]);
        }

        public function update_character($old, $new, $server){
            $stmt = $this->website->db('game', $server)->prepare('UPDATE Character SET Name = :name WHERE Name = :old_name');
            return $stmt->execute([':name' => $new, ':old_name' => $old]);
        }

        public function update_option_data($old, $new, $server){
            $stmt = $this->website->db('game', $server)->prepare('UPDATE OptionData SET Name = :name WHERE Name = :old_name');
            return $stmt->execute([':name' => $new, ':old_name' => $old]);
        }

        public function update_t_friendlist($old, $new, $server){
            $stmt = $this->website->db('game', $server)->prepare('UPDATE T_FriendList SET FriendName = :name WHERE FriendName = :old_name');
            return $stmt->execute([':name' => $new, ':old_name' => $old]);
        }

        public function update_t_friendmail($old, $new, $server){
            $stmt = $this->website->db('game', $server)->prepare('UPDATE T_FriendMail SET FriendName = :name WHERE FriendName = :old_name');
            return $stmt->execute([':name' => $new, ':old_name' => $old]);
        }
		
		public function update_t_friendmain($old, $new, $server){
			if($this->website->db('game', $server)->check_if_table_exists('T_FriendMain')){
				$stmt = $this->website->db('game', $server)->prepare('UPDATE T_FriendMain SET Name = :name WHERE Name = :old_name');
				return $stmt->execute([':name' => $new, ':old_name' => $old]);  
			}
			else{
                return;
            }
        }     

        public function update_t_cguid($old, $new, $server){
            $stmt = $this->website->db('game', $server)->prepare('UPDATE T_CGuid SET Name = :name WHERE Name = :old_name');
            return $stmt->execute([':name' => $new, ':old_name' => $old]);
        }
		
		public function update_T_CurCharName($old, $new, $server){
			if($this->website->db('game', $server)->check_if_table_exists('T_CurCharName')){
				$stmt = $this->website->db('game', $server)->prepare('UPDATE T_CurCharName SET Name = :name WHERE Name = :old_name');
				return $stmt->execute([':name' => $new, ':old_name' => $old]);
			}
			else{
                return;
            }
        }
		
		public function update_T_Event_Inventory($old, $new, $server){
			if($this->website->db('game', $server)->check_if_table_exists('T_Event_Inventory')){
				$stmt = $this->website->db('game', $server)->prepare('UPDATE T_Event_Inventory SET Name = :name WHERE Name = :old_name');
				return $stmt->execute([':name' => $new, ':old_name' => $old]);
			}
			else{
                return;
            }
        }

        public function update_master_level_table($old, $new, $server){
            $stmt = $this->website->db('game', $server)->prepare('UPDATE ' . $this->config->values('table_config', [$server, 'master_level', 'table']) . ' SET ' . $this->config->values('table_config', [$server, 'master_level', 'identifier_column']) . ' = :name WHERE ' . $this->config->values('table_config', [$server, 'master_level', 'identifier_column']) . ' = :old_name');
            return $stmt->execute([':name' => $new, ':old_name' => $old]);
        }

        public function update_IGC_Gens($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('IGC_Gens')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE IGC_Gens SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }

        public function update_IGC_GensAbuse($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('IGC_GensAbuse')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE IGC_GensAbuse SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function update_IGC_GremoryCase($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('IGC_GremoryCase')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE IGC_GremoryCase SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function update_IGC_HuntingRecord($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('IGC_HuntingRecord')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE IGC_HuntingRecord SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function update_IGC_HuntingRecordOption($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('IGC_HuntingRecordOption')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE IGC_HuntingRecordOption SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function update_IGC_LabyrinthClearLog($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('IGC_LabyrinthClearLog')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE IGC_LabyrinthClearLog SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function update_IGC_LabyrinthInfo($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('IGC_LabyrinthInfo')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE IGC_LabyrinthInfo SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function update_IGC_LabyrinthLeagueLog($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('IGC_LabyrinthLeagueLog')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE IGC_LabyrinthLeagueLog SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function update_IGC_LabyrinthLeagueUser($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('IGC_LabyrinthLeagueUser')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE IGC_LabyrinthLeagueUser SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function update_IGC_LabyrinthMissionInfo($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('IGC_LabyrinthMissionInfo')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE IGC_LabyrinthMissionInfo SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function update_IGC_MixLostItemInfo($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('IGC_MixLostItemInfo')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE IGC_MixLostItemInfo SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function update_IGC_Muun_Inventory($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('IGC_Muun_Inventory')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE IGC_Muun_Inventory SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function update_IGC_Muun_Period($old, $new, $server){
			if($this->website->db('game', $server)->check_if_table_exists('IGC_Muun_Period')){
				$stmt = $this->website->db('game', $server)->prepare('UPDATE IGC_Muun_Period SET Name = :name WHERE Name = :old_name');
				return $stmt->execute([':name' => $new, ':old_name' => $old]);
			} else{
                return;
            }
        }
		
		public function update_IGC_RestoreItem_Inventory($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('IGC_RestoreItem_Inventory')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE IGC_RestoreItem_Inventory SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
        public function update_IGC_PeriodBuffInfo($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('IGC_PeriodBuffInfo')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE IGC_PeriodBuffInfo SET CharacterName = :name WHERE CharacterName = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }

        public function update_IGC_PeriodExpiredItemInfo($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('IGC_PeriodExpiredItemInfo')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE IGC_PeriodExpiredItemInfo SET CharacterName = :name WHERE CharacterName = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }

        public function update_IGC_PeriodItemInfo($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('IGC_PeriodItemInfo')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE IGC_PeriodItemInfo SET CharacterName = :name WHERE CharacterName = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function update_IGC_PentagramInfo($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('IGC_PentagramInfo')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE IGC_PentagramInfo SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }

		public function update_IGC_PStore_Items($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('IGC_PStore_Items')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE IGC_PStore_Items SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function update_IGC_PStore_Data($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('IGC_PStore_Data')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE IGC_PStore_Data SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		 
        public function update_IGC_PersonalStore_Info($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('IGC_PersonalStore_Info')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE IGC_PersonalStore_Info SET name = :name WHERE name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function update_IGC_ArtifactInfo($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('IGC_ArtifactInfo')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE IGC_ArtifactInfo SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function update_IGC_BlessingBox_Character($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('IGC_BlessingBox_Character')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE IGC_BlessingBox_Character SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function update_IGC_HuntPoint($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('IGC_HuntPoint')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE IGC_HuntPoint SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function update_IGC_StatsSystem($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('IGC_StatsSystem')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE IGC_StatsSystem SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function check_IGC_PersonalStore_ChangeName($id, $server){
			if($this->website->db('game', $server)->check_if_table_exists('IGC_PersonalStore_ChangeName')){
				$stmt = $this->website->db('game', $server)->prepare('SELECT id FROM IGC_PersonalStore_ChangeName WHERE character_id = :id');
				$stmt->execute([':id' => $id]); 
				return $stmt->fetch();
			}
			else{
				return false;
			}
		}
		
		public function insert_IGC_PersonalStore_ChangeName($old, $new, $id, $server){
            if($this->website->db('game', $server)->check_if_table_exists('IGC_PersonalStore_ChangeName')){
                $stmt = $this->website->db('game', $server)->prepare('INSERT INTO IGC_PersonalStore_ChangeName (old_name, new_name, character_id) VALUES (:old_name, :new_name, :character_id)');
                return $stmt->execute([':old_name' => $old, ':new_name' => $new, ':character_id' => $id]);
            } else{
                return;
            }
        }
		
		public function update_IGC_AbilityCardInfo($old, $new, $server)
        {
            if($this->website->db('game', $server)->check_if_table_exists('IGC_AbilityCardInfo')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE IGC_AbilityCardInfo SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function update_IGC_WingCoreInfo($old, $new, $server)
        {
            if($this->website->db('game', $server)->check_if_table_exists('IGC_WingCoreInfo')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE IGC_WingCoreInfo SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		
        public function update_T_3rd_Quest_Info($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('T_3rd_Quest_Info')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE T_3rd_Quest_Info SET CHAR_NAME = :name WHERE CHAR_NAME = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }

        public function update_T_GMSystem($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('T_GMSystem')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE T_GMSystem SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }

        public function update_T_LUCKY_ITEM_INFO($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('T_LUCKY_ITEM_INFO')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE T_LUCKY_ITEM_INFO SET CharName = :name WHERE CharName = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }

        public function update_T_PentagramInfo($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('T_PentagramInfo')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE T_PentagramInfo SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }

        public function update_T_QUEST_EXP_INFO($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('T_QUEST_EXP_INFO')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE T_QUEST_EXP_INFO SET CHAR_NAME = :name WHERE CHAR_NAME = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }

        public function update_PetWarehouse($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('PetWarehouse')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE PetWarehouse SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }

        public function update_T_WaitFriend($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('T_WaitFriend')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE T_WaitFriend SET FriendName = :name WHERE FriendName = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function update_T_PSHOP_ITEMVALUE_INFO($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('T_PSHOP_ITEMVALUE_INFO')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE T_PSHOP_ITEMVALUE_INFO SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function update_C_Monster_KillCount($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('C_Monster_KillCount')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE C_Monster_KillCount SET name = :name WHERE name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function update_C_PlayerKiller_Info($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('C_PlayerKiller_Info')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE C_PlayerKiller_Info SET Victim = :name WHERE Victim = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function update_C_PlayerKiller_Info2($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('C_PlayerKiller_Info')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE C_PlayerKiller_Info SET Killer = :name WHERE Killer = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function update_Gens_Left($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('Gens_Left')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE Gens_Left SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function update_Gens_Rank($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('Gens_Rank')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE Gens_Rank SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function update_Gens_Reward($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('Gens_Reward')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE Gens_Reward SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function update_EnhanceSkillTree($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('EnhanceSkillTree')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE EnhanceSkillTree SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function update_EventEntryCount($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('EventEntryCount')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE EventEntryCount SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function update_EventEntryLimit($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('EventEntryLimit')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE EventEntryLimit SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function update_EventInventory($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('EventInventory')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE EventInventory SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function update_FavoriteWarpList($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('FavoriteWarpList')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE FavoriteWarpList SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function update_GremoryCase($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('GremoryCase')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE GremoryCase SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function update_HelperData($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('HelperData')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE HelperData SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function update_MuHelperPlus($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('MuHelperPlus')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE MuHelperPlus SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function update_MuQuestInfo($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('MuQuestInfo')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE MuQuestInfo SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }

		public function update_MuunInventory($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('MuunInventory')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE MuunInventory SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function update_PentagramJewel($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('PentagramJewel')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE PentagramJewel SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function update_PersonalShopRenewalList($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('PersonalShopRenewalList')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE PersonalShopRenewalList SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function update_PShopItemValue($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('PShopItemValue')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE PShopItemValue SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function update_QuestGuide($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('QuestGuide')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE QuestGuide SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function update_QuestKillCount($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('QuestKillCount')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE QuestKillCount SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function update_QuestWorld($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('QuestWorld')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE QuestWorld SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function update_RankingBloodCastle($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('RankingBloodCastle')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE RankingBloodCastle SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function update_RankingCastleSiege($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('RankingCastleSiege')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE RankingCastleSiege SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function update_RankingChaosCastle($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('RankingChaosCastle')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE RankingChaosCastle SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function update_RankingDevilSquare($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('RankingDevilSquare')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE RankingDevilSquare SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function update_RankingDuel($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('RankingDuel')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE RankingDuel SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function update_RankingIllusionTemple($old, $new, $server){
            if($this->website->db('game', $server)->check_if_table_exists('RankingIllusionTemple')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE RankingIllusionTemple SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function update_EVENT_INFO($old, $new, $table_config, $server){
            if($this->website->db($table_config['db'], $server)->check_if_table_exists('EVENT_INFO')){
                $stmt = $this->website->db($table_config['db'], $server)->prepare('UPDATE EVENT_INFO SET CharacterName = :name WHERE CharacterName = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function update_EVENT_INFO_BC_5TH($old, $new, $table_config, $server){
            if($this->website->db($table_config['db'], $server)->check_if_table_exists('EVENT_INFO_BC_5TH')){
                $stmt = $this->website->db($table_config['db'], $server)->prepare('UPDATE EVENT_INFO_BC_5TH SET CharacterName = :name WHERE CharacterName = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function update_EVENT_INFO_CC($old, $new, $table_config, $server){
            if($this->website->db($table_config['db'], $server)->check_if_table_exists('EVENT_INFO_CC')){
                $stmt = $this->website->db($table_config['db'], $server)->prepare('UPDATE EVENT_INFO_CC SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function update_EVENT_INFO_IT($old, $new, $table_config, $server){
            if($this->website->db($table_config['db'], $server)->check_if_table_exists('EVENT_INFO_IT')){
                $stmt = $this->website->db($table_config['db'], $server)->prepare('UPDATE EVENT_INFO_IT SET Name = :name WHERE Name = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function update_T_ENTER_CHECK_BC($old, $new, $table_config, $server){
            if($this->website->db($table_config['db'], $server)->check_if_table_exists('T_ENTER_CHECK_BC')){
                $stmt = $this->website->db($table_config['db'], $server)->prepare('UPDATE T_ENTER_CHECK_BC SET CharName = :name WHERE CharName = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }
		
		public function update_T_ENTER_CHECK_ILLUSION_TEMPLE($old, $new, $table_config, $server){
            if($this->website->db($table_config['db'], $server)->check_if_table_exists('T_ENTER_CHECK_ILLUSION_TEMPLE')){
                $stmt = $this->website->db($table_config['db'], $server)->prepare('UPDATE T_ENTER_CHECK_ILLUSION_TEMPLE SET CharName = :name WHERE CharName = :old_name');
                return $stmt->execute([':name' => $new, ':old_name' => $old]);
            } else{
                return;
            }
        }

        public function update_DmN_Ban_List($old, $new, $server){
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_Ban_List SET name = :name WHERE name = :old_name AND type = 2 AND server = :server');
            return $stmt->execute([':name' => $new, ':old_name' => $old, ':server' => $server]);
        }

        public function update_DmN_Gm_List($old, $new, $server){
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_Gm_List SET character = :name WHERE character = :old_name AND server = :server');
            return $stmt->execute([':name' => $new, ':old_name' => $old, ':server' => $server]);
        }

        public function update_DmN_Market($old, $new, $server){
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_Market SET char = :name WHERE char = :old_name AND server = :server');
            return $stmt->execute([':name' => $new, ':old_name' => $old, ':server' => $server]);
        }

        public function update_DmN_Market_Logs($old, $new, $server){
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_Market_Logs SET char = :name WHERE char = :old_name AND server = :server');
            return $stmt->execute([':name' => $new, ':old_name' => $old, ':server' => $server]);
        }

        public function update_DmN_Votereward_Ranking($old, $new, $server){
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_Votereward_Ranking SET character = :name WHERE character = :old_name AND server = :server');
            return $stmt->execute([':name' => $new, ':old_name' => $old, ':server' => $server]);
        }

        public function add_to_change_name_history($old, $new, $user, $server){
            $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_ChangeName_History (account, old_name, new_name, change_date, server) VALUES (:acc, :old, :new, GETDATE(), :server)');
            return $stmt->execute([':acc' => $user, ':old' => $old, ':new' => $new, ':server' => $server]);
        }

		public function check_amount_of_coins($server){
            $items_array = str_split($this->Mcharacter->char_info['Inventory'], $this->website->get_value_from_server($server, 'item_size'));
            //$check_in = ['inv' => [12, 76], 'inv2' => [76, 107], 'inv3' => [108, 140],];
			$check_in = ['inv' => [12, 76]];
            $items = [];
            $coins = [];
            foreach($check_in AS $name => $loops){
                for($a = $loops[0]; $a < $loops[1]; $a++){
                    $items[$a] = strtoupper($items_array[$a]);
                }
            }
            $items = array_diff($items, [str_pad("", $this->website->get_value_from_server($server, 'item_size'), "F")]);
            foreach($items as $key => $item){
                $this->iteminfo->itemData($item);
                if($this->iteminfo->id == 100 && $this->iteminfo->type == 14){
                    $coins[$key] = $this->iteminfo->dur;
                }
            }
            return $coins;
        }

		public function check_amount_of_items($id, $cat, $server){
            $items_array = str_split($this->Mcharacter->char_info['Inventory'], $this->website->get_value_from_server($server, 'item_size'));
            //$check_in = ['inv' => [12, 76], 'inv2' => [76, 107], 'inv3' => [108, 140],];
			$check_in = ['inv' => [12, 76]];
            $items = [];
            $coins = [];
            foreach($check_in AS $name => $loops){
                for($a = $loops[0]; $a < $loops[1]; $a++){
                    $items[$a] = strtoupper($items_array[$a]);
                }
            }
            $items = array_diff($items, [str_pad("", $this->website->get_value_from_server($server, 'item_size'), "F")]);
            foreach($items as $key => $item){
                $this->iteminfo->itemData($item);
                if($this->iteminfo->id == $id && $this->iteminfo->type == $cat){
                    $coins[$key] = $this->iteminfo->dur;
                }
            }
            return $coins;
        }

		public function remove_old_coins($data, $user, $server){
            $items_array = str_split($this->Mcharacter->char_info['Inventory'], $this->website->get_value_from_server($server, 'item_size'));
            foreach($data AS $key => $val){
                if(array_key_exists($key, $items_array)){
                    $items_array[$key] = str_pad("", $this->website->get_value_from_server($server, 'item_size'), "F");
                }
            }
            $stmt = $this->website->db('game', $server)->prepare('UPDATE Character SET Inventory = 0x' . implode('', $items_array) . ' WHERE Name = :char AND AccountId = :user');
            $stmt->execute([':char' => $this->vars['character'], ':user' => $user]);
        }

		public function add_new_coins($where_to_add, $hex, $user, $server){
            $this->get_inventory_content($this->vars['character'], $server);
            $items_array = str_split($this->Mcharacter->char_info['Inventory'], $this->website->get_value_from_server($server, 'item_size'));
            if(array_key_exists($where_to_add, $items_array)){
                $items_array[$where_to_add] = $hex;
            }
            $stmt = $this->website->db('game', $server)->prepare('UPDATE Character SET Inventory = 0x' . implode('', $items_array) . ' WHERE Name = :char AND AccountId = :user');
            $stmt->execute([':char' => $this->vars['character'], ':user' => $user]);
        }

		public function draw_chance($probabilities, $max_propability = 1000){
            $rand = rand(0, $max_propability);
            $keys = array_keys($probabilities);
            arsort($keys);
            do{
                $sum = array_sum($keys);
                if($rand <= $sum && $rand >= $sum - end($keys)){
                    return $keys[key($keys)];
                }
            } while(array_pop($keys));
        }
		
        public function load_artifacts($name, $server){
			if($this->website->db('game', $server)->check_if_table_exists('IGC_ArtifactInfo')){
				$stmt = $this->website->db('game', $server)->prepare('SELECT Position, ArtifactLevel, ArtifactType FROM IGC_ArtifactInfo WHERE Name = :char ORDER BY Position ASC');
				$stmt->execute([':char' => $name]);
				return $stmt->fetch_all();
			}
			return false;
		}
		
		public function load_pentagram_data($user, $name, $server, $jewels){
			if($this->website->db('game', $server)->check_if_table_exists('IGC_PentagramInfo')){
				unset($jewels[0]);
				$stmt = $this->website->db('game', $server)->prepare('SELECT * FROM IGC_PentagramInfo WHERE AccountID = :user AND Name = :char AND JewelPos = 0');
				$stmt->execute([':user' => $user, ':char' => $name]);
				$this->vars['pentagram'] = $stmt->fetch_all();
				if(!empty($this->vars['pentagram'])){
					$this->vars['pentagram_data'] = [];
					
					foreach($this->vars['pentagram'] AS $data){
						if(in_array($data['JewelIndex'], $jewels)){
							$this->vars['pentagram_data'][$data['JewelIndex']] = [
								'jewelPos' => $data['JewelPos'],
								'itemType' => $data['ItemType'],
								'itemIndex' =>  $data['ItemIndex'],
								'mainAttr' => $data['MainAttribute'],
								'mainAttrLevel' =>  $data['JewelLevel'],
								'rank1' => ($data['Rank1'] == 15) ? 0 : $data['Rank1'],
								'rank1Level' => ($data['Rank1Level'] == 15) ? 0 : $data['Rank1Level'],
								'rank2' => ($data['Rank2'] == 15) ? 0 : $data['Rank2'],
								'rank2Level' => ($data['Rank2Level'] == 15) ? 0 : $data['Rank2Level'],
								'rank3' => ($data['Rank3'] == 15) ? 0 : $data['Rank3'],
								'rank3Level' => ($data['Rank3Level'] == 15) ? 0 : $data['Rank3Level'],
								'rank4' => ($data['Rank4'] == 15) ? 0 : $data['Rank4'],
								'rank4Level' => ($data['Rank4Level'] == 15) ? 0 : $data['Rank4Level'],
								'rank5' => ($data['Rank5'] == 15) ? 0 : $data['Rank5'],
								'rank5Level' => ($data['Rank5Level'] == 15) ? 0 : $data['Rank5Level'],
							];
						}
					}
					
					return $this->vars['pentagram_data'];
				} 
			}
			return false;
		}
    }