<?php
    in_file();

    class Mtransfer_char extends model
    {
        public $error = false, $vars = [], $characters = [], $total_characters, $char_info = [], $gens_family;
        private $price, $per_page, $chars, $char_list = [], $pos;

        public function __contruct(){
            parent::__construct();
        }

        public function __set($key, $val){
            $this->vars[$key] = $val;
        }

        public function __isset($name){
            return isset($this->vars[$name]);
        }

				
		public function checkAccount($name, $server){
            $stmt = $this->website->db('account', $server)->prepare('SELECT memb_guid FROM MEMB_INFO WHERE (memb___id Collate Database_Default = :username Collate Database_Default)');
            $stmt->execute([':username' => $name]);
            return $stmt->fetch();
        }

		public function load_char_list($account, $server){
            $stmt = $this->website->db('game', $server)->prepare('SELECT Name, cLevel, Class, ' . $this->reset_column($server) . $this->greset_column($server) . ' Money, LevelUpPoint, CtlCode, PkCount, PkLevel FROM Character WHERE AccountId = :account');
            $stmt->execute([':account' => $account]);
            $i = 0;
            while($row = $stmt->fetch()){
                $this->characters[] = ['name' => $row['Name'], 'level' => $row['cLevel'], 'Class' => $row['Class'], 'resets' => $row['resets'], 'gresets' => $row['grand_resets'], 'money' => $row['Money'], 'points' => $row['LevelUpPoint'], 'ctlcode' => $row['CtlCode'], 'pkcount' => $row['PkCount'], 'pklevel' => $row['PkLevel'], 'CtlCode' => $row['CtlCode']];
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

		public function check_char($char, $account, $server){
            $stmt = $this->website->db('game', $server)->prepare('SELECT Name FROM Character WHERE AccountId = :user AND Name = :char');
            $stmt->execute([':user' => $account, ':char' => $char]);
            return $stmt->fetch();
        }

		public function check_char_without_account($char, $server){
            $stmt = $this->website->db('game', $server)->prepare('SELECT Name FROM Character WHERE Name = :char');
            $stmt->execute([':char' => $char]);
            return $stmt->fetch();
        }

		public function char_info($char, $account, $server, $by_id = false){
            $identifier = ($by_id) ? $this->website->get_char_id_col($server) : 'Name';
            $stmt = $this->website->db('game', $server)->prepare('SELECT Name, Money, Class, cLevel, ' . $this->reset_column($server) . $this->greset_column($server) . ' LevelUpPoint, Strength, Dexterity, Vitality, Energy, Leadership, PkLevel, PkCount, CtlCode, MagicList, MapNumber, MapPosX, MapPosY, '.$this->website->get_char_id_col($server).' AS id FROM Character WHERE AccountId = :user AND ' . $identifier . ' = :char');
            $stmt->execute([':user' => $account, ':char' => $char]);
            if($this->char_info = $stmt->fetch()){
                $this->char_info['mlevel'] = $this->load_master_level($this->char_info['Name'], $server);
				$this->inventory($this->char_info['Name'], $server);
            }
        }

		private function load_master_level($char, $server){
            if($this->config->values('table_config', [$server, 'master_level', 'column']) != false){
                $stmt = $this->website->db('game', $server)->prepare('SELECT ' . $this->config->values('table_config', [$server, 'master_level', 'column']) . ' AS mlevel FROM ' . $this->config->values('table_config', [$server, 'master_level', 'table']) . ' WHERE ' . $this->config->values('table_config', [$server, 'master_level', 'identifier_column']) . ' = :char');
                $stmt->execute([':char' => $char]);
                $mlevel = $stmt->fetch();
                //$stmt->close_cursor();
                if($mlevel){
                    return $mlevel['mlevel'];
                }
            }
            return 0;
        }

				
		private function inventory($char, $server){
			$stmt = $this->website->db('game', $server)->prepare('SELECT CONVERT(IMAGE, Inventory) AS Inventory FROM Character WHERE Name = :char');
			$stmt->execute([':char' => $char]);
			if($inv = $stmt->fetch()){
				$unpack = unpack('H*', $inv['Inventory']);
				$this->char_info['Inventory'] = $this->website->clean_hex($unpack[1]);
			}
            
        }

				
		public function check_equipment($server){
            return (strtoupper(substr($this->char_info['Inventory'], 0, $this->website->get_value_from_server($server, 'item_size') * 12)) === str_repeat('F', $this->website->get_value_from_server($server, 'item_size') * 12));
        }

				
		public function check_store($server){
            $items_array = str_split($this->char_info['Inventory'], $this->website->get_value_from_server($server, 'item_size'));
			for($a = 204; $a < 236; $a++){
				if(strtoupper($items_array[$a]) != str_repeat('F', $this->website->get_value_from_server($server, 'item_size'))){
					return false;
					break;
				}
			}
            return true;
		}

				
		public function check_inventory($server){
			$items_array = str_split($this->char_info['Inventory'], $this->website->get_value_from_server($server, 'item_size'));
			for($a = 12; $a < 76; $a++){
				if(strtoupper($items_array[$a]) != str_repeat('F', $this->website->get_value_from_server($server, 'item_size'))){
					return false;
					break;
				}

			}
            return true;
		}

				
		public function check_exp_inv1($server){
			$items_array = str_split($this->char_info['Inventory'], $this->website->get_value_from_server($server, 'item_size'));
			for($a = 76; $a < 108; $a++){
				if(strtoupper($items_array[$a]) != str_repeat('F', $this->website->get_value_from_server($server, 'item_size'))){
					return false;
					break;
				}

			}
            return true;
        }

				
		public function check_exp_inv2($server){
			$items_array = str_split($this->char_info['Inventory'], $this->website->get_value_from_server($server, 'item_size'));
			for($a = 108; $a < 140; $a++){
				if(strtoupper($items_array[$a]) != str_repeat('F', $this->website->get_value_from_server($server, 'item_size'))){
					return false;
					break;
				}

			}
            return true;
        }

				
        public function update_account_character($account, $server){
            $accountCharInfo = $this->account_char_info($account, $server);
            if($accountCharInfo['GameID1'] === $this->vars['character'] || $accountCharInfo['GameID1'] === null){
                $accountCharInfo['GameID1'] = '';
            }
            if($accountCharInfo['GameID2'] == $this->vars['character'] || $accountCharInfo['GameID2'] === null){
                $accountCharInfo['GameID2'] = '';
            }
            if($accountCharInfo['GameID3'] == $this->vars['character'] || $accountCharInfo['GameID3'] === null){
                $accountCharInfo['GameID3'] = '';
            }
            if($accountCharInfo['GameID4'] === $this->vars['character'] || $accountCharInfo['GameID4'] === null){
                $accountCharInfo['GameID4'] = '';
            }
            if($accountCharInfo['GameID5'] === $this->vars['character'] || $accountCharInfo['GameID5'] === null){
                $accountCharInfo['GameID5'] = '';
            }
			$array_data = [
				':ab' => $accountCharInfo['GameID1'], 
				':ac' => $accountCharInfo['GameID2'], 
				':ad' => $accountCharInfo['GameID3'], 
				':ae' => $accountCharInfo['GameID4'], 
				':af' => $accountCharInfo['GameID5']
			];
			$additional_slots = '';
			if(MU_VERSION >= 9){
				$additional_slots = ', GameID6 = :ag, GameID7 = :ah, GameID8 = :ai';
				if($accountCharInfo['GameID6'] === $this->vars['character'] || $accountCharInfo['GameID6'] === null){
					$accountCharInfo['GameID6'] = '';
				}
				if($accountCharInfo['GameID7'] === $this->vars['character'] || $accountCharInfo['GameID7'] === null){
					$accountCharInfo['GameID7'] = '';
				}
				if($accountCharInfo['GameID8'] === $this->vars['character'] || $accountCharInfo['GameID8'] === null){
					$accountCharInfo['GameID8'] = '';
				}
				$array_data2 = [
					':ag' => $accountCharInfo['GameID6'], 
					':ah' => $accountCharInfo['GameID7'], 
					':ai' => $accountCharInfo['GameID8']
				];
				$array_data = $array_data + $array_data2;
			}
			if(MU_VERSION >= 10){
				$additional_slots = ', GameID6 = :ag, GameID7 = :ah, GameID8 = :ai, GameID9 = :aj, GameID10 = :ak';
				if($accountCharInfo['GameID6'] === $this->vars['character'] || $accountCharInfo['GameID6'] === null){
					$accountCharInfo['GameID6'] = '';
				}
				if($accountCharInfo['GameID7'] === $this->vars['character'] || $accountCharInfo['GameID7'] === null){
					$accountCharInfo['GameID7'] = '';
				}
				if($accountCharInfo['GameID8'] === $this->vars['character'] || $accountCharInfo['GameID8'] === null){
					$accountCharInfo['GameID8'] = '';
				}
				if($accountCharInfo['GameID9'] === $this->vars['character'] || $accountCharInfo['GameID9'] === null){
					$accountCharInfo['GameID9'] = '';
				}
				if($accountCharInfo['GameID10'] === $this->vars['character'] || $accountCharInfo['GameID10'] === null){
					$accountCharInfo['GameID10'] = '';
				}
				$array_data2 = [
					':ag' => $accountCharInfo['GameID6'], 
					':ah' => $accountCharInfo['GameID7'], 
					':ai' => $accountCharInfo['GameID8'],
					':aj' => $accountCharInfo['GameID9'],
					':ak' => $accountCharInfo['GameID10']
				];
				$array_data = $array_data + $array_data2;
			}
            if($accountCharInfo['GameIDC'] === $this->vars['character'] || $accountCharInfo['GameIDC'] === null){
                $accountCharInfo['GameIDC'] = $this->vars['character'];
            }
			$array_data3 = [
				':idc' => $accountCharInfo['GameIDC'], 
				':account' => $account
			];
			$array_data = $array_data + $array_data3;
            $stmt = $this->website->db('game', $server)->prepare('UPDATE AccountCharacter SET GameID1 = :ab, GameID2 = :ac, GameID3 = :ad, GameID4 = :ae, GameID5 = :af '.$additional_slots.', GameIDC = :idc WHERE Id = :account');
            return $stmt->execute($array_data);
        }

		public function add_to_account_character($space, $name, $account, $server){
            $accountCharInfo = $this->account_char_info($account, $server);
			if($accountCharInfo != false){
				if($space == 1){
					$accountCharInfo['GameID1'] = $name;
				}
				if($space == 2){
					$accountCharInfo['GameID2'] = $name;
				}
				if($space == 3){
					$accountCharInfo['GameID3'] = $name;
				}
				if($space == 4){
					$accountCharInfo['GameID4'] = $name;
				}
				if($space == 5){
					$accountCharInfo['GameID5'] = $name;
				}
				
				$additional_slots = '';
				$array_data = [
					':ab' => $accountCharInfo['GameID1'], 
					':ac' => $accountCharInfo['GameID2'], 
					':ad' => $accountCharInfo['GameID3'], 
					':ae' => $accountCharInfo['GameID4'], 
					':af' => $accountCharInfo['GameID5']
				];
				if(MU_VERSION >= 9){
					if($space == 6){
						$accountCharInfo['GameID6'] = $name;
					}
					if($space == 7){
						$accountCharInfo['GameID7'] = $name;
					}
					if($space == 8){
						$accountCharInfo['GameID8'] = $name;
					}
					$additional_slots = ', GameID6 = :ag, GameID7 = :ah, GameID8 = :ai';
					$array_data2 = [
						':ag' => $accountCharInfo['GameID6'], 
						':ah' => $accountCharInfo['GameID7'], 
						':ai' => $accountCharInfo['GameID8']
					];
					$array_data = $array_data + $array_data2;
				}
				if(MU_VERSION >= 10){
					if($space == 6){
						$accountCharInfo['GameID6'] = $name;
					}
					if($space == 7){
						$accountCharInfo['GameID7'] = $name;
					}
					if($space == 8){
						$accountCharInfo['GameID8'] = $name;
					}
					if($space == 9){
						$accountCharInfo['GameID9'] = $name;
					}
					if($space == 10){
						$accountCharInfo['GameID10'] = $name;
					}
					$additional_slots = ', GameID6 = :ag, GameID7 = :ah, GameID8 = :ai, GameID9 = :aj, GameID10 = :ak';
					$array_data2 = [
						':ag' => $accountCharInfo['GameID6'], 
						':ah' => $accountCharInfo['GameID7'], 
						':ai' => $accountCharInfo['GameID8'],
						':aj' => $accountCharInfo['GameID9'],
						':ak' => $accountCharInfo['GameID10']
					];
					$array_data = $array_data + $array_data2;
				}
				$array_data3 = [
					':account' => $account
				];
				$array_data = $array_data + $array_data3;
				$stmt = $this->website->db('game', $server)->prepare('UPDATE AccountCharacter SET GameID1 = :ab, GameID2 = :ac, GameID3 = :ad, GameID4 = :ae, GameID5 = :af '.$additional_slots.' WHERE Id = :account');
				$stmt->execute($array_data);
				return true;
			}
			return false;
        }

		private function account_char_info($account, $server){
			$additional_slots = '';
			if(MU_VERSION >= 9){
				$additional_slots = ', GameID6, GameID7, GameID8';
			}
			if(MU_VERSION >= 10){
				$additional_slots = ', GameID6, GameID7, GameID8, GameID9, GameID10';
			}
            $stmt = $this->website->db('game', $server)->prepare('SELECT GameID1, GameID2, GameID3, GameID4, GameID5 '.$additional_slots.', GameIDC FROM AccountCharacter WHERE Id = :account');
            $stmt->execute([':account' => $account]);
            return $stmt->fetch();
        }

		public function update_character($id, $account, $server){
            $stmt = $this->website->db('game', $server)->prepare('UPDATE Character SET AccountId = :account WHERE '.$this->website->get_char_id_col($server).' = :id');
            return $stmt->execute([':account' => $account, ':id' => $id]);
        }

				
		public function remove_ruud($id, $server){
			if($this->website->db('game', $server)->check_if_column_exists('RuudMoney', 'Character') != false){
				$ruud = 'RuudMoney';
			}
			else{
				$ruud = 'Ruud';
			}
			$stmt = $this->website->db('game', $server)->prepare('UPDATE Character SET '.$ruud.' = 0 WHERE '.$this->website->get_char_id_col($server).' = :char');
			$stmt->execute([':char' => $id]);
		}

				
		public function remove_zen($id, $server){
			$stmt = $this->website->db('game', $server)->prepare('UPDATE Character SET Money = 0 WHERE '.$this->website->get_char_id_col($server).' = :char');
			$stmt->execute([':char' => $id]);
		}

				
		public function remove_items_from_pstore($server){
			$stmt = $this->website->db('game', $server)->prepare('DELETE FROM IGC_PStore_Data WHERE Name = :char');
			$stmt->execute([':char' =>  $this->vars['character']]);
			$stmt = $this->website->db('game', $server)->prepare('DELETE FROM IGC_PStore_Items WHERE Name = :char');
			$stmt->execute([':char' =>  $this->vars['character']]);
		}

				
        public function update_IGC_PeriodExpiredItemInfo($guid, $server){
            if($this->website->db('game', $server)->check_if_table_exists('IGC_PeriodExpiredItemInfo')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE IGC_PeriodExpiredItemInfo SET UserGUID = ' . $guid . ' WHERE CharacterName = :name');
                return $stmt->execute([':name' => $this->vars['character']]);
            }
            return true;
        }

        public function update_IGC_PeriodItemInfo($guid, $server){
            if($this->website->db('game', $server)->check_if_table_exists('IGC_PeriodItemInfo')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE IGC_PeriodItemInfo SET UserGUID = ' . $guid . ' WHERE CharacterName = :name');
                return $stmt->execute([':name' => $this->vars['character']]);
            }
            return true;
        }

				
		public function update_IGC_PentagramInfo($guid, $account, $server){
            if($this->website->db('game', $server)->check_if_table_exists('IGC_PentagramInfo')){
                $stmt = this->website->db('game', $server)->prepare('UPDATE IGC_PentagramInfo SET UserGuid = ' . $guid . ', AccountID = \''.$account.'\' WHERE Name = :name');
                return $stmt->execute([':name' => $this->vars['character']]);
            } else{
                return;
            }
        }

        public function update_T_LUCKY_ITEM_INFO($guid, $server){
            if($this->website->db('game', $server)->check_if_table_exists('T_LUCKY_ITEM_INFO')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE T_LUCKY_ITEM_INFO SET UserGUID = ' . $guid . ' WHERE CharName = :name');
                return $stmt->execute([':name' => $this->vars['character']]);
            }
            return true;
        }

        public function update_T_MuRummy($user = false, $account = '', $server = null){
            if($this->website->db('game', $server)->check_if_table_exists('T_MuRummy')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE T_MuRummy SET AccountID = :market_account WHERE Name = :name');
                $user = ($user != false) ? $account : 'dmnmark987';
                return $stmt->execute([':market_account' => $user, ':name' => $this->vars['character']]);
            }
            return true;
        }

        public function update_T_MuRummyInfo($user = false, $account = '', $server = null){
            if($this->website->db('game', $server)->check_if_table_exists('T_MuRummyInfo')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE T_MuRummyInfo SET AccountID = :market_account WHERE Name = :name');
                $user = ($user != false) ? $account : 'dmnmark987';
                return $stmt->execute([':market_account' => $user, ':name' => $this->vars['character']]);
            }
            return true;
        }

        public function update_T_MuRummyLog($user = false, $account = '', $server = null){
            if($this->website->db('game', $server)->check_if_table_exists('T_MuRummyLog')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE T_MuRummyLog SET AccountID = :market_account WHERE Name = :name');
                $user = ($user != false) ? $account : 'dmnmark987';
                return $stmt->execute([':market_account' => $user, ':name' => $this->vars['character']]);
            }
            return true;
        }

		public function update_T_PentagramInfo($user = false, $account = '', $server = null){
            if($this->website->db('game', $server)->check_if_table_exists('T_PentagramInfo')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE T_PentagramInfo SET AccountID = :market_account WHERE Name = :name');
                $user = ($user != false) ? $account : 'dmnmark987';
                return $stmt->execute([':market_account' => $user, ':name' => $this->vars['character']]);
            }
            return true;
        }

        public function update_T_PSHOP_ITEMVALUE_INFO($user = false, $account = '', $server = null){
            if($this->website->db('game', $server)->check_if_table_exists('T_PSHOP_ITEMVALUE_INFO')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE T_PSHOP_ITEMVALUE_INFO SET AccountID = :market_account WHERE Name = :name');
                $user = ($user != false) ? $account : 'dmnmark987';
                return $stmt->execute([':market_account' => $user, ':name' => $this->vars['character']]);
            }
            return true;
        }

		public function update_PetWarehouse($user = false, $account = '', $server = null){
            if($this->website->db('game', $server)->check_if_table_exists('PetWarehouse')){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE PetWarehouse SET AccountID = :market_account WHERE Name = :name');
                $user = ($user != false) ? $account : 'dmnmark987';
                return $stmt->execute([':market_account' => $user, ':name' => $this->vars['character']]);
            }
            return true;
        }

				
		public function update_DmN_User_Achievements($user = false, $account = '', $server = null, $id = null){
            if($this->website->db('web')->check_if_table_exists('DmN_User_Achievements')){
                $stmt = $this->website->db('web')->prepare('UPDATE DmN_User_Achievements SET memb___id = :market_account WHERE char_id = :id AND server = :server');
                $user = ($user != false) ? $account : 'dmnmark987';
                return $stmt->execute([':market_account' => $user, ':id' => $id, ':server' => $server]);
            }
            return true;
        }

        public function get_guild_info($name, $server){
            $stmt = $this->website->db('game', $server)->prepare('SELECT G_Name, G_Status FROM GuildMember WHERE Name = :name');
            $stmt->execute([':name' => $name]);
            return $stmt->fetch();
        }

		public function get_gens_info($name, $server){
            $gens_config = $this->config->values('rankings_config', [$server, 'gens', 'type']);
            if($gens_config != false){
                switch($gens_config){
                    case 'scf':
                        $query = $this->website->db('game', $server)->prepare('SELECT SCFGensFamily AS family FROM Character WHERE Name = :name');
                        break;
                    case 'muengine':
                        $query = $this->website->db('game', $server)->prepare('SELECT GensType AS family FROM Character WHERE Name = :name');
                        break;
                    case 'zteam':
                        $query = $this->website->db('game', $server)->prepare('SELECT memb_clan AS family FROM GensUserInfo WHERE memb_char = :name');
                        break;
                    case 'exteam':
                        $query = $this->website->db('game', $server)->prepare('SELECT Influence AS family FROM GensMember WHERE Name = :name');
                        break;
                    case 'igcn':
                        $query = $this->website->db('game', $server)->prepare('SELECT Influence AS family FROM IGC_Gens WHERE Name = :name');
                        break;
                    case 'xteam':
                        $query = $this->website->db('game', $server)->prepare('SELECT Family AS family FROM Gens_Rank WHERE Name = :name');
                        break;
                    default:
                        $query = false;
                        break;
                }
                if($query != false){
                    $query->execute([':name' => $name]);
                    $gens = $query->fetch();
                    if(in_array($gens['family'], [1, 2])){
                        $this->gens_family = $gens['family'];
                        return true;
                    }
                }
            }
            return false;
        }

        public function has_guild($char, $server){
            $stmt = $this->website->db('game', $server)->prepare('SELECT Name FROM GuildMember WHERE Name = :name');
            $stmt->execute([':name' => $char]);
            return $stmt->fetch();
        }

		public function check_free_slot($account, $server){
            $accountCharInfo = $this->account_char_info($account, $server);
            if($accountCharInfo['GameID1'] == null)
                return 1;
            if($accountCharInfo['GameID2'] == null)
                return 2;
            if($accountCharInfo['GameID3'] == null)
                return 3;
            if($accountCharInfo['GameID4'] == null)
                return 4;
            if($accountCharInfo['GameID5'] == null)
                return 5;
			if(MU_VERSION >= 9){
				if($accountCharInfo['GameID6'] == null)
					return 6;
				if($accountCharInfo['GameID7'] == null)
					return 7;
				if($accountCharInfo['GameID8'] == null)
					return 8;
			}
			if(MU_VERSION >= 10){
				if($accountCharInfo['GameID6'] == null)
					return 6;
				if($accountCharInfo['GameID7'] == null)
					return 7;
				if($accountCharInfo['GameID8'] == null)
					return 8;
				if($accountCharInfo['GameID9'] == null)
					return 9;
				if($accountCharInfo['GameID10'] == null)
					return 10;
			}
            return false;
        }

        public function get_char_name_by_id($id, $server){
            $stmt = $this->website->db('game', $server)->prepare('SELECT Name FROM Character WHERE '.$this->website->get_char_id_col($server).' = :id');
            $stmt->execute([':id' => $id]);
            $info = $stmt->fetch();
            return $info['Name'];
        }

		private function get_char_id_by_name($name, $server){
            $stmt = $this->website->db('game', $server)->prepare('SELECT '.$this->website->get_char_id_col($server).' AS id FROM Character WHERE Name = :name');
            $stmt->execute([':name' => $name]);
            $info = $stmt->fetch();
            return $info['id'];
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
