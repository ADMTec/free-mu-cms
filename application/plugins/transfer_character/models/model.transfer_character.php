<?php
    in_file();

    class Mtransfer_character extends model
    {
        public $character_data = [], $accountCharInfo, $serials = [], $pets = [];

        public function __contruct(){
            parent::__construct();
        }
		
		public function check_if_acc_has_chars($user, $server){
            $stmt = $this->website->db('game', $server)->prepare('SELECT Name, '.$this->website->get_char_id_col($server).' FROM Character WHERE AccountId = :account');
            $stmt->execute([':account' => $user]);
            $this->character_data = $stmt->fetch_all();
            return (count($this->character_data) > 0) ? true : false;
        }

			
        public function check_if_chars_exists($id, $user, $server){
            $stmt = $this->website->db('game', $server)->prepare('SELECT Name FROM Character WHERE '.$this->website->get_char_id_col($server).' = :id AND AccountId = :account');
            $stmt->execute([':id' => $id, ':account' => $user]);
            $data = $stmt->fetch();
            return ($data != false) ? $data : false;
        }

		public function check_if_chars_exists_on_second_server($name, $server){
            $stmt = $this->website->db($this->website->get_db_from_server($server))->prepare('SELECT '.$this->website->get_char_id_col($server).' AS id FROM Character WHERE Name = :name');
            $stmt->execute([':name' => $name]);
            return $stmt->fetch();
        }

		public function check_free_slot($account, $server){
            $this->accountCharInfo = $this->account_char_info($account, $server);
			if($this->accountCharInfo == false){
				$this->insert_account_character($account, $server);
				return 1;
			}
            if($this->accountCharInfo['GameID1'] == null)
                return 1;
            if($this->accountCharInfo['GameID2'] == null)
                return 2;
            if($this->accountCharInfo['GameID3'] == null)
                return 3;
            if($this->accountCharInfo['GameID4'] == null)
                return 4;
            if($this->accountCharInfo['GameID5'] == null)
                return 5;
			if(MU_VERSION >= 9){
				if($this->accountCharInfo['GameID6'] == null)
					return 6;
				if($this->accountCharInfo['GameID7'] == null)
					return 7;
				if($this->accountCharInfo['GameID8'] == null)
					return 8;
			}
			if(MU_VERSION >= 10){
				if($this->accountCharInfo['GameID9'] == null)
					return 9;
				if($this->accountCharInfo['GameID10'] == null)
					return 10;
			}
            return false;
        }

				
		private function account_char_info($account, $server){
			$additional_slots = '';
			if(MU_VERSION >= 9){
				$additional_slots .= ', GameID6, GameID7, GameID8';
			}
			if(MU_VERSION >= 10){
				$additional_slots .= ', GameID9, GameID10';
			}
            $stmt = $this->website->db($this->website->get_db_from_server($server))->prepare('SELECT GameID1, GameID2, GameID3, GameID4, GameID5 '.$additional_slots.', GameIDC FROM AccountCharacter WHERE Id = :account');
            $stmt->execute([':account' => $account]);
            return $stmt->fetch();
        }
		
		public function insert_account_character($account, $server){
			$stmt = $this->website->db($this->website->get_db_from_server($server))->prepare('INSERT INTO AccountCharacter (Id) VALUES (:account)');
			$stmt->execute([':account' => $account]);
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
					':af' => $accountCharInfo['GameID5'], 
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
				$stmt = $this->website->db($this->website->get_db_from_server($server))->prepare('UPDATE AccountCharacter SET GameID1 = :ab, GameID2 = :ac, GameID3 = :ad, GameID4 = :ae, GameID5 = :af '.$additional_slots.' WHERE Id = :account');
				$stmt->execute($array_data);
				return true;
			}
			return false;
        }

		public function remove_account_character($account, $server, $name){
            $accountCharInfo = $this->account_char_info($account, $server);
			if($accountCharInfo != false){
				if($accountCharInfo['GameID1'] === $name){
					$accountCharInfo['GameID1'] = null;
				}
				if($accountCharInfo['GameID2'] === $name){
					$accountCharInfo['GameID2'] = null;
				}
				if($accountCharInfo['GameID3'] === $name){
					$accountCharInfo['GameID3'] = null;
				}
				if($accountCharInfo['GameID4'] === $name){
					$accountCharInfo['GameID4'] = null;
				}
				if($accountCharInfo['GameID5'] === $name){
					$accountCharInfo['GameID5'] = null;
				}
				if($accountCharInfo['GameIDC'] === $name){
					$accountCharInfo['GameIDC'] = null;
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
					if($accountCharInfo['GameID6'] === $name){
						$accountCharInfo['GameID6'] = null;
					}
					if($accountCharInfo['GameID7'] === $name){
						$accountCharInfo['GameID7'] = null;
					}
					if($accountCharInfo['GameID8'] === $name){
						$accountCharInfo['GameID8'] = null;
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
					if($accountCharInfo['GameID6'] === $name){
						$accountCharInfo['GameID6'] = null;
					}
					if($accountCharInfo['GameID7'] === $name){
						$accountCharInfo['GameID7'] = null;
					}
					if($accountCharInfo['GameID8'] === $name){
						$accountCharInfo['GameID8'] = null;
					}
					if($accountCharInfo['GameID9'] === $name){
						$accountCharInfo['GameID9'] = null;
					}
					if($accountCharInfo['GameID10'] === $name){
						$accountCharInfo['GameID10'] = null;
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
					':idc' => $accountCharInfo['GameIDC'],
					':account' => $account
				];
				$array_data = $array_data + $array_data3;
				$stmt = $this->website->db('game', $server)->prepare('UPDATE AccountCharacter SET GameID1 = :ab, GameID2 = :ac, GameID3 = :ad, GameID4 = :ae, GameID5 = :af '.$additional_slots.', GameIDC = :idc WHERE Id = :account');
				$stmt->execute($array_data);
			}
			return true;
        }

		public function character($from, $to, $server, $name, $new_name, $titems = 1){
            $columns = $this->get_columns('Character', $this->website->db('game', $this->session->userdata(['user' => 'server'])));
            $columns = array_diff($columns, [$this->website->get_char_id_col($server), 'weey_sky', 'week_sky_base', 'MaxReached', 'Avatar', 'PatentId']);

            foreach($columns AS $key => $data){
                if($data == 'MagicList' || $data == 'Quest' || $data == 'Inventory' || $data == 'MuBotData' || $data == 'MuHelperData' || $data == 'EffectList' || $data == 'MuHelperPlusData'){
                    $columns[$key] = 'CONVERT(IMAGE, ' . $data . ') AS ' . $data . '';
                }
            }
            $char = $this->website->db('game', $this->session->userdata(['user' => 'server']))->query('SELECT ' . implode(',', $columns) . ' FROM Character WHERE AccountId = \'' . $from . '\' AND Name = \'' . $name . '\'')->fetch();
            foreach($char AS $k => $val){
                if(strtolower($k) == 'inventory' || strtolower($k) == 'magiclist' || strtolower($k) == 'quest' || strtolower($k) == 'mubotdata' || strtolower($k) == 'muhelperdata' || strtolower($k) == 'effectlist' || strtolower($k) == 'muhelperplusdata' || strtolower($k) == 'newslots'){
					$unpack = unpack('H*', $val);
					if(strtolower($k) == 'inventory' && $this->website->get_value_from_server($this->session->userdata(['user' => 'server']), 'item_size') == 64 && $titems == 1){
						$items_array = str_split($this->website->clean_hex($unpack[1]), $this->website->get_value_from_server($this->session->userdata(['user' => 'server']), 'item_size'));
						foreach($items_array AS $item){
							if(strtoupper($item) != str_repeat('F', $this->website->get_value_from_server($this->session->userdata(['user' => 'server']), 'item_size'))){
								$serial = hexdec(substr($item, 32, 8));
								if($serial > 0){
									$id = hexdec(substr($item, 0, 2));
									$cat = hexdec(substr($item, 18, 1));
									if($cat == 13 && in_array($id, [4,5])){
										$this->pets[] = $serial;
									}
									$this->serials[] = $serial;
								}
							}
						}
					}
					if(strtolower($k) == 'inventory'){
						if($titems == 1){
							$val = '0x' . $this->website->clean_hex($unpack[1]);
						}
						else{
							$val = 'cast(REPLICATE(char(0xff),' . $this->website->get_value_from_server($this->session->userdata(['user' => 'server']), 'inv_size') . ') as varbinary(' . $this->website->get_value_from_server($this->session->userdata(['user' => 'server']), 'inv_size') . '))';
						}
					}
					else{
						$val = '0x' . $this->website->clean_hex($unpack[1]);
					}
                }
                if(strtolower($k) == 'accountid'){
                    $val = $to;
                }
                if(strtolower($k) == 'name'){
                    $val = $new_name;
                }
                if(strtolower($k) != 'inventory' && strtolower($k) != 'magiclist' && strtolower($k) != 'quest' && strtolower($k) != 'mubotdata' && strtolower($k) != 'muhelperdata' && strtolower($k) != 'effectlist' && strtolower($k) != 'muhelperplusdata' &&  strtolower($k) != 'newslots'){
                    $val = $this->website->db('game', $this->session->userdata(['user' => 'server']))->escape($val);
                }
				
				
                $char[$k] = $val;
            }
            foreach($columns AS $key => $data){
                if(($tmp = strstr($data, 'AS ')) !== false){
                    $columns[$key] = substr($tmp, 3);
                }
            }
            $this->website->db($this->website->get_db_from_server($server))->query('INSERT INTO Character (' . implode(',', $columns) . ') VALUES (' . implode(',', $char) . ')');
            return true;
        }

		public function gremory_case($from, $to, $server, $name, $new_name){
            $columns = $this->get_columns('IGC_GremoryCase', $this->website->db('game', $this->session->userdata(['user' => 'server'])));
			$columns = array_diff($columns, ['GremoryCaseIndex']);
			if(!empty($columns)){
				$chars = $this->website->db('game', $this->session->userdata(['user' => 'server']))->query('SELECT ' . implode(',', $columns) . ' FROM IGC_GremoryCase WHERE AccountId = \'' . $from . '\' AND Name = \'' . $name . '\' AND UsedInfo = 0')->fetch_all();
				$s = [];
				
				foreach($chars AS $key => $char_data){
					foreach($char_data AS $k => $val){
						if(strtolower($k) == 'accountid'){
							$val = $to;
						}
						if(strtolower($k) == 'name'){
							$val = $new_name;
						}
						$chars[$key][$k] = $this->website->db('game', $this->session->userdata(['user' => 'server']))->escape($val);
					}
				}
				foreach($chars AS $c){
					$s[] = implode(',', $c);
				}
				foreach($s AS $vals){
					$this->website->db($this->website->get_db_from_server($server))->query('INSERT INTO IGC_GremoryCase (' . implode(',', $columns) . ') VALUES (' . $vals . ')');
				}
			}
            return true;
        }

		public function IGC_PeriodBuffInfo($from, $to, $server, $name, $new_name){
            $columns = $this->get_columns('IGC_PeriodBuffInfo', $this->website->db('game', $this->session->userdata(['user' => 'server'])));
            $columns = array_diff($columns, ['id']);
			if(!empty($columns)){
				$chars = $this->website->db('game', $this->session->userdata(['user' => 'server']))->query('SELECT ' . implode(',', $columns) . ' FROM IGC_PeriodBuffInfo WHERE CharacterName = \'' . $name . '\' AND ExpireDate > GETDATE()')->fetch_all();
				$s = [];
				foreach($chars AS $key => $char_data){
					foreach($char_data AS $k => $val){
						if(strtolower($k) == 'charactername'){
							$val = $new_name;
						}
						$chars[$key][$k] = $this->website->db('game', $this->session->userdata(['user' => 'server']))->escape($val);
					}
				}
				foreach($chars AS $c){
					$s[] = implode(',', $c);
				}
				foreach($s AS $vals){
					$this->website->db($this->website->get_db_from_server($server))->query('INSERT INTO IGC_PeriodBuffInfo (' . implode(',', $columns) . ') VALUES (' . $vals . ')');
				}
			}
            return true;
        }

		public function IGC_PeriodItemInfo($from, $to, $server, $name, $new_name){
            $columns = $this->get_columns('IGC_PeriodItemInfo', $this->website->db('game', $this->session->userdata(['user' => 'server'])));
            $columns = array_diff($columns, ['id', 'PeriodIndex']);
            if(!empty($columns)){
				$chars = $this->website->db('game', $this->session->userdata(['user' => 'server']))->query('SELECT ' . implode(',', $columns) . ' FROM IGC_PeriodItemInfo WHERE CharacterName = \'' . $name . '\' AND ExpireDate > GETDATE()')->fetch_all();
				$s = [];
				foreach($chars AS $key => $char_data){
					foreach($char_data AS $k => $val){
						if(strtolower($k) == 'charactername'){
							$val = $new_name;
						}
						$chars[$key][$k] = $this->website->db('game', $this->session->userdata(['user' => 'server']))->escape($val);
					}
				}
				foreach($chars AS $c){
					$s[] = implode(',', $c);
				}
				foreach($s AS $vals){
					$this->website->db($this->website->get_db_from_server($server))->query('INSERT INTO IGC_PeriodItemInfo (' . implode(',', $columns) . ') VALUES (' . $vals . ')');
				}
			}
            return true;
        }
		
			
        public function OptionData($from, $to, $server, $name, $new_name){
            $columns = $this->get_columns('OptionData', $this->website->db('game', $this->session->userdata(['user' => 'server'])));
            $chars = $this->website->db('game', $this->session->userdata(['user' => 'server']))->query('SELECT ' . implode(',', $columns) . ' FROM OptionData WHERE Name = \'' . $name . '\'')->fetch_all();
            $s = [];
            foreach($chars AS $key => $char_data){
                foreach($char_data AS $k => $val){
                    if(strtolower($k) == 'name'){
                        $val = $new_name;
                    }
                    if(strtolower($k) == 'skillkey'){
						$unpack = unpack('H*', $val);
						$val = '0x' . $this->website->clean_hex($unpack[1]);
                    } else{
                        $val = $this->website->db('game', $this->session->userdata(['user' => 'server']))->escape($val);
                    }
                    $chars[$key][$k] = $val;
                }
            }
            foreach($chars AS $c){
                $s[] = implode(',', $c);
            }
            foreach($s AS $vals){
                $this->website->db($this->website->get_db_from_server($server))->query('INSERT INTO OptionData (' . implode(',', $columns) . ') VALUES (' . $vals . ')');
            }
            return true;
        }

        public function T_CGuid($from, $to, $server, $name){
            $this->website->db($this->website->get_db_from_server($server))->query('INSERT INTO T_CGuid (Name) VALUES (\'' . $name . '\')');
            return true;
        }
		
			
        public function T_PentagramInfo($from, $to, $server, $name, $new_name){
			if($this->website->db('game', $this->session->userdata(['user' => 'server']))->check_if_table_exists('IGC_PentagramInfo')){
				$columns = $this->get_columns('IGC_PentagramInfo', $this->website->db('game', $this->session->userdata(['user' => 'server'])));
				$columns = array_diff($columns, ['id']);
				foreach($columns AS $key => $data){
					if($data == 'PentagramInfo'){
						$columns[$key] = 'CONVERT(IMAGE, ' . $data . ') AS ' . $data . '';
					}
				}

				$chars = $this->website->db('game', $this->session->userdata(['user' => 'server']))->query('SELECT ' . implode(',', $columns) . ' FROM IGC_PentagramInfo WHERE Name = \'' . $name . '\'')->fetch_all();
				$s = [];
				foreach($chars AS $key => $char_data){
					foreach($char_data AS $k => $val){
						if(strtolower($k) == 'accountid'){
							$val = $to;
						}
						if(strtolower($k) == 'name'){
							$val = $new_name;
						}
						if(strtolower($k) == 'pentagraminfo'){
							$unpack = unpack('H*', $val);
							$val = '0x' . $this->website->clean_hex($unpack[1]);
						}
						else{
							$val = $this->website->db('game', $this->session->userdata(['user' => 'server']))->escape($val);
						}
						$chars[$key][$k] = $val;
					}
				}
				foreach($chars AS $c){
					$s[] = implode(',', $c);
				}
				foreach($s AS $vals){
					foreach($columns AS $key => $data){
						if(($tmp = strstr($data, 'AS ')) !== false){
							$columns[$key] = substr($tmp, 3);
						}
					}
					$this->website->db($this->website->get_db_from_server($server))->query('INSERT INTO IGC_PentagramInfo (' . implode(',', $columns) . ') VALUES (' . $vals . ')');
				}
			}
            return true;
        }
		
			
		public function IGC_Muun_ConditionInfo($from, $to, $server, $name, $new_name){
			if($this->website->db('game', $this->session->userdata(['user' => 'server']))->check_if_table_exists('IGC_Muun_ConditionInfo')){
				$columns = $this->get_columns('IGC_Muun_ConditionInfo', $this->website->db('game', $this->session->userdata(['user' => 'server'])));
				
				$chars = $this->website->db('game', $this->session->userdata(['user' => 'server']))->query('SELECT ' . implode(',', $columns) . ' FROM IGC_Muun_ConditionInfo WHERE Name = \'' . $name . '\'')->fetch_all();
				$s = [];
				foreach($chars AS $key => $char_data){
					foreach($char_data AS $k => $val){
						if(strtolower($k) == 'name'){
							$val = $new_name;
						}
						$chars[$key][$k] = $this->website->db('game', $this->session->userdata(['user' => 'server']))->escape($val);
					}
				}
				foreach($chars AS $c){
					$s[] = implode(',', $c);
				}
				foreach($s AS $vals){
					$this->website->db($this->website->get_db_from_server($server))->query('INSERT INTO IGC_Muun_ConditionInfo (' . implode(',', $columns) . ') VALUES (' . $vals . ')');
				}
			}
            return true;
        }
		
			
		public function IGC_Muun_Period($from, $to, $server, $name, $new_name){
			if($this->website->db('game', $this->session->userdata(['user' => 'server']))->check_if_table_exists('IGC_Muun_Period')){
				$columns = $this->get_columns('IGC_Muun_Period', $this->website->db('game', $this->session->userdata(['user' => 'server'])));

				$chars = $this->website->db('game', $this->session->userdata(['user' => 'server']))->query('SELECT ' . implode(',', $columns) . ' FROM IGC_Muun_Period WHERE Name = \'' . $name . '\' AND ExpireDate > GETDATE()')->fetch_all();
				$s = [];
				foreach($chars AS $key => $char_data){
					foreach($char_data AS $k => $val){
						if(strtolower($k) == 'name'){
							$val = $new_name;
						}
						$chars[$key][$k] = $this->website->db('game', $this->session->userdata(['user' => 'server']))->escape($val);
					}
				}
				foreach($chars AS $c){
					$s[] = implode(',', $c);
				}
				foreach($s AS $vals){
					$this->website->db($this->website->get_db_from_server($server))->query('INSERT INTO IGC_Muun_Period (' . implode(',', $columns) . ') VALUES (' . $vals . ')');
				}
			}
            return true;
        }
		
			
		public function IGC_Muun_Inventory($from, $to, $server, $name, $new_name){
			if($this->website->db('game', $this->session->userdata(['user' => 'server']))->check_if_table_exists('IGC_Muun_Inventory')){
				$columns = $this->get_columns('IGC_Muun_Inventory', $this->website->db('game', $this->session->userdata(['user' => 'server'])));

				foreach($columns AS $key => $data){
					if($data == 'Items'){
						$columns[$key] = 'CONVERT(IMAGE, ' . $data . ') AS ' . $data . '';
					}
				}

				$chars = $this->website->db('game', $this->session->userdata(['user' => 'server']))->query('SELECT ' . implode(',', $columns) . ' FROM IGC_Muun_Inventory WHERE Name = \'' . $name . '\'')->fetch_all();
				$s = [];
				foreach($chars AS $key => $char_data){
					foreach($char_data AS $k => $val){
						if(strtolower($k) == 'name'){
							$val = $new_name;
						}
						if(strtolower($k) == 'items'){
							$unpack = unpack('H*', $val);
							$val = '0x' . $this->website->clean_hex($unpack[1]);
						}
						else{
							$val = $this->website->db('game', $this->session->userdata(['user' => 'server']))->escape($val);
						}
						$chars[$key][$k] = $val;
					}
				}
				foreach($chars AS $c){
					$s[] = implode(',', $c);
				}
				foreach($s AS $vals){
					foreach($columns AS $key => $data){
						if(($tmp = strstr($data, 'AS ')) !== false){
							$columns[$key] = substr($tmp, 3);
						}
					}
					$this->website->db($this->website->get_db_from_server($server))->query('INSERT INTO IGC_Muun_Inventory (' . implode(',', $columns) . ') VALUES (' . $vals . ')');
				}
			}
            return true;
        }
		
			
		public function IGC_HarmonyItemData($from, $to, $server, $name, $new_name){
			if(!empty($this->serials)){
				if($this->website->db('game', $this->session->userdata(['user' => 'server']))->check_if_table_exists('IGC_HarmonyItemData')){
					$columns = $this->get_columns('IGC_HarmonyItemData', $this->website->db('game', $this->session->userdata(['user' => 'server'])));
					$columns = array_diff($columns, ['id', 'AccountId']);
					$chars = $this->website->db('game', $this->session->userdata(['user' => 'server']))->query('SELECT ' . implode(',', $columns) . ' FROM IGC_HarmonyItemData WHERE Serial IN('.implode(',', $this->serials).')')->fetch_all();
					$s = [];
					foreach($chars AS $key => $char_data){
						foreach($char_data AS $k => $val){
							$chars[$key][$k] = $this->website->db('game', $this->session->userdata(['user' => 'server']))->escape($val);
						}
					}
					foreach($chars AS $c){
						$s[] = implode(',', $c);
					}
					foreach($s AS $vals){
						$data = explode(',', $vals);
						$check = $this->website->db($this->website->get_db_from_server($server))->query('SELECT id FROM IGC_HarmonyItemData WHERE Serial = '.$data[0].'')->fetch();
						if($check == false){
							$this->website->db($this->website->get_db_from_server($server))->query('INSERT INTO IGC_HarmonyItemData (' . implode(',', $columns) . ') VALUES (' . $vals . ')');
						}
					}
				}
			}
            return true;
        }
		
			
		public function IGC_ArtifactInfo($from, $to, $server, $name, $new_name){
			if($this->website->db('game', $this->session->userdata(['user' => 'server']))->check_if_table_exists('IGC_ArtifactInfo')){
				$columns = $this->get_columns('IGC_ArtifactInfo', $this->website->db('game', $this->session->userdata(['user' => 'server'])));
				$columns = array_diff($columns, ['id']);
				$chars = $this->website->db('game', $this->session->userdata(['user' => 'server']))->query('SELECT ' . implode(',', $columns) . ' FROM IGC_ArtifactInfo WHERE Name = \'' . $name . '\'')->fetch_all();
				$s = [];
				foreach($chars AS $key => $char_data){
					foreach($char_data AS $k => $val){
						if(strtolower($k) == 'name'){
							$val = $new_name;
						}
						$chars[$key][$k] = $this->website->db('game', $this->session->userdata(['user' => 'server']))->escape($val);
					}
				}
				foreach($chars AS $c){
					$s[] = implode(',', $c);
				}
				foreach($s AS $vals){
					$data = explode(',', $vals);
					$check = $this->website->db($this->website->get_db_from_server($server))->query('SELECT id FROM IGC_ArtifactInfo WHERE Name = '.$data[0].' AND Serial = '.$data[2].'')->fetch();
					if($check == false){
						$this->website->db($this->website->get_db_from_server($server))->query('INSERT INTO IGC_ArtifactInfo (' . implode(',', $columns) . ') VALUES (' . $vals . ')');
					}
				}
			}
			
            return true;
        }
		
			
		public function T_PetItem_Info($from, $to, $server, $name, $new_name){
			if(!empty($this->pets)){
				$columns = $this->get_columns('T_PetItem_Info', $this->website->db('game', $this->session->userdata(['user' => 'server'])));
				//$columns = array_diff($columns, ['id']);
				$chars = $this->website->db('game', $this->session->userdata(['user' => 'server']))->query('SELECT ' . implode(',', $columns) . ' FROM T_PetItem_Info WHERE ItemSerial IN('.implode(',', $this->pets).')')->fetch_all();
				$s = [];
				foreach($chars AS $key => $char_data){
					foreach($char_data AS $k => $val){
						$chars[$key][$k] = $this->website->db('game', $this->session->userdata(['user' => 'server']))->escape($val);
					}
				}
				foreach($chars AS $c){
					$s[] = implode(',', $c);
				}
				foreach($s AS $vals){
					$this->website->db($this->website->get_db_from_server($server))->query('INSERT INTO T_PetItem_Info (' . implode(',', $columns) . ') VALUES (' . $vals . ')');
				}
			}
            return true;
        }

				
		public function IGC_Gens($from, $to, $server, $name, $new_name){
            $columns = $this->get_columns('IGC_Gens', $this->website->db('game', $this->session->userdata(['user' => 'server'])));
            if(!empty($columns)){
				$chars = $this->website->db('game', $this->session->userdata(['user' => 'server']))->query('SELECT ' . implode(',', $columns) . ' FROM IGC_Gens WHERE Name = \'' . $name . '\'')->fetch_all();
				$s = [];
				foreach($chars AS $key => $char_data){
					foreach($char_data AS $k => $val){
						if(strtolower($k) == 'name'){
							$val = $new_name;
						}
		   
						$val = $this->website->db('game', $this->session->userdata(['user' => 'server']))->escape($val);
						$chars[$key][$k] = $val;
					}
				}
				foreach($chars AS $c){
					$s[] = implode(',', $c);
				}
				foreach($s AS $vals){
					$this->website->db($this->website->get_db_from_server($server))->query('INSERT INTO IGC_Gens (' . implode(',', $columns) . ') VALUES (' . $vals . ')');
				}
			}
            return true;
        }
		
		public function IGC_SeasonPass($from, $to, $server, $id, $newId){
            $columns = $this->get_columns('IGC_SeasonPass', $this->website->db('game', $this->session->userdata(['user' => 'server'])));
            if(!empty($columns)){
				$columns = array_diff($columns, ['id']);
				$chars = $this->website->db('game', $this->session->userdata(['user' => 'server']))->query('SELECT ' . implode(',', $columns) . ' FROM IGC_SeasonPass WHERE character_id = \'' . $id . '\'')->fetch_all();
				$s = [];
				foreach($chars AS $key => $char_data){
					foreach($char_data AS $k => $val){
						if(strtolower($k) == 'character_id'){
							$val = $newId;
						}
		   
						$val = $this->website->db('game', $this->session->userdata(['user' => 'server']))->escape($val);
						$chars[$key][$k] = $val;
					}
				}
				foreach($chars AS $c){
					$s[] = implode(',', $c);
				}
				foreach($s AS $vals){
					$this->website->db($this->website->get_db_from_server($server))->query('INSERT INTO IGC_SeasonPass (' . implode(',', $columns) . ') VALUES (' . $vals . ')');
				}
			}
            return true;
        }
		
		public function IGC_SeasonPassMission($from, $to, $server, $id, $newId){
            $columns = $this->get_columns('IGC_SeasonPassMission', $this->website->db('game', $this->session->userdata(['user' => 'server'])));
            if(!empty($columns)){
				$columns = array_diff($columns, ['id']);
				$chars = $this->website->db('game', $this->session->userdata(['user' => 'server']))->query('SELECT ' . implode(',', $columns) . ' FROM IGC_SeasonPassMission WHERE character_id = \'' . $id . '\'')->fetch_all();
				$s = [];
				foreach($chars AS $key => $char_data){
					foreach($char_data AS $k => $val){
						if(strtolower($k) == 'character_id'){
							$val = $newId;
						}
		   
						$val = $this->website->db('game', $this->session->userdata(['user' => 'server']))->escape($val);
						$chars[$key][$k] = $val;
					}
				}
				foreach($chars AS $c){
					$s[] = implode(',', $c);
				}
				foreach($s AS $vals){
					$this->website->db($this->website->get_db_from_server($server))->query('INSERT INTO IGC_SeasonPassMission (' . implode(',', $columns) . ') VALUES (' . $vals . ')');
				}
			}
            return true;
        }
		
		public function IGC_SeasonPassTicket($from, $to, $server, $id, $newId){
            $columns = $this->get_columns('IGC_SeasonPassTicket', $this->website->db('game', $this->session->userdata(['user' => 'server'])));
            if(!empty($columns)){
				$columns = array_diff($columns, ['id']);
				$chars = $this->website->db('game', $this->session->userdata(['user' => 'server']))->query('SELECT ' . implode(',', $columns) . ' FROM IGC_SeasonPassTicket WHERE character_id = \'' . $id . '\'')->fetch_all();
				$s = [];
				foreach($chars AS $key => $char_data){
					foreach($char_data AS $k => $val){
						if(strtolower($k) == 'character_id'){
							$val = $newId;
						}
		   
						$val = $this->website->db('game', $this->session->userdata(['user' => 'server']))->escape($val);
						$chars[$key][$k] = $val;
					}
				}
				foreach($chars AS $c){
					$s[] = implode(',', $c);
				}
				foreach($s AS $vals){
					$this->website->db($this->website->get_db_from_server($server))->query('INSERT INTO IGC_SeasonPassTicket (' . implode(',', $columns) . ') VALUES (' . $vals . ')');
				}
			}
            return true;
        }
		
		public function DmN_User_Achievements($from, $to, $server, $id, $newId){
            $columns = $this->get_columns('DmN_User_Achievements', $this->website->db('web'));
            if(!empty($columns)){
				$columns = array_diff($columns, ['id']);
				$chars = $this->website->db('web')->query('SELECT ' . implode(',', $columns) . ' FROM DmN_User_Achievements WHERE char_id = \'' . $id . '\' AND server = \''.$this->session->userdata(['user' => 'server']).'\'')->fetch_all();
				$s = [];
				foreach($chars AS $key => $char_data){
					foreach($char_data AS $k => $val){
						if(strtolower($k) == 'char_id'){
							$val = $newId;
						}
						if(strtolower($k) == 'server'){
							$val = $server;
						}
		   
						$val = $this->website->db('web')->escape($val);
						$chars[$key][$k] = $val;
					}
				}
				foreach($chars AS $c){
					$s[] = implode(',', $c);
				}
				foreach($s AS $vals){
					$this->website->db('web')->query('INSERT INTO DmN_User_Achievements (' . implode(',', $columns) . ') VALUES (' . $vals . ')');
				}
			}
            return true;
        }
		
		public function DmN_Unlocked_Achievements($from, $to, $server, $id, $newId){
            $columns = $this->get_columns('DmN_Unlocked_Achievements', $this->website->db('web'));
            if(!empty($columns)){
				$columns = array_diff($columns, ['id']);
				$chars = $this->website->db('web')->query('SELECT ' . implode(',', $columns) . ' FROM DmN_Unlocked_Achievements WHERE char_id = \'' . $id . '\' AND server = \''.$this->session->userdata(['user' => 'server']).'\'')->fetch_all();
				$s = [];
				foreach($chars AS $key => $char_data){
					foreach($char_data AS $k => $val){
						if(strtolower($k) == 'char_id'){
							$val = $newId;
						}
						if(strtolower($k) == 'server'){
							$val = $server;
						}
		   
						$val = $this->website->db('web')->escape($val);
						$chars[$key][$k] = $val;
					}
				}
				foreach($chars AS $c){
					$s[] = implode(',', $c);
				}
				foreach($s AS $vals){
					$this->website->db('web')->query('INSERT INTO DmN_Unlocked_Achievements (' . implode(',', $columns) . ') VALUES (' . $vals . ')');
				}
			}
            return true;
        }
		
				
		public function CustomQuest($from, $to, $server, $name, $new_name){
            $columns = $this->get_columns('CustomQuest', $this->website->db('game', $this->session->userdata(['user' => 'server'])));
            if(!empty($columns)){
				$chars = $this->website->db('game', $this->session->userdata(['user' => 'server']))->query('SELECT ' . implode(',', $columns) . ' FROM CustomQuest WHERE Name = \'' . $name . '\'')->fetch_all();
				$s = [];
				foreach($chars AS $key => $char_data){
					foreach($char_data AS $k => $val){
						if(strtolower($k) == 'name'){
							$val = $new_name;
						}
		   
						$val = $this->website->db('game', $this->session->userdata(['user' => 'server']))->escape($val);
						$chars[$key][$k] = $val;
					}
				}
				foreach($chars AS $c){
					$s[] = implode(',', $c);
				}
				foreach($s AS $vals){
					$this->website->db($this->website->get_db_from_server($server))->query('INSERT INTO CustomQuest (' . implode(',', $columns) . ') VALUES (' . $vals . ')');
				}
			}
            return true;
        }
		
		public function Achievements($from, $to, $server, $name, $new_name){
            $columns = $this->get_columns('Achievements', $this->website->db('game', $this->session->userdata(['user' => 'server'])));
            if(!empty($columns)){
				$chars = $this->website->db('game', $this->session->userdata(['user' => 'server']))->query('SELECT ' . implode(',', $columns) . ' FROM Achievements WHERE Name = \'' . $name . '\'')->fetch_all();
				$s = [];
				foreach($chars AS $key => $char_data){
					foreach($char_data AS $k => $val){
						if(strtolower($k) == 'name'){
							$val = $new_name;
						}
		   
						$val = $this->website->db('game', $this->session->userdata(['user' => 'server']))->escape($val);
						$chars[$key][$k] = $val;
					}
				}
				foreach($chars AS $c){
					$s[] = implode(',', $c);
				}
				foreach($s AS $vals){
					$this->website->db($this->website->get_db_from_server($server))->query('INSERT INTO Achievements (' . implode(',', $columns) . ') VALUES (' . $vals . ')');
				}
			}
            return true;
        }
				
		public function check_if_transfered($id, $server){
			$stmt = $this->website->db('web')->prepare('SELECT id FROM DmN_CharacterTransferServerLogs WHERE mu_id = :id AND server = :server');
			$stmt->execute([
				':id' => $id,
				':server' => $server
			]);
			return $stmt->fetch();
		}

				
		public function log_transfer($from, $to, $to_server, $id, $name, $new_name){
            $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_CharacterTransferServerLogs (mu_id, name, newname, fromAccount, toAccount, transferDate, server, toserver) VALUES (:mu_id, :name, :newname, :fromAccount, :toAccount, :transferDate, :server, :toserver)');
            $stmt->execute([
				':mu_id' => $id,
				':name' => $name,
				':newname' => $new_name,
				':fromAccount' => $from,
				':toAccount' => $to,
				':transferDate' => time(),
				':server' => $this->session->userdata(['user' => 'server']),
				':toserver' => $to_server
			]);
            $stmt->close_cursor();
        }
		
		public function load_logs($page = 1, $per_page = 25, $acc = '', $server = 'All'){
            if(($acc == '' || $acc == '-') && $server == 'All')
                $items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape($per_page) . '  id, mu_id, name, newname, fromAccount, toAccount, transferDate, server, toserver FROM DmN_CharacterTransferServerLogs WHERE id Not IN (SELECT Top ' . $this->website->db('web')->escape($per_page * ($page - 1)) . ' id FROM DmN_CharacterTransferServerLogs ORDER BY id DESC) ORDER BY id DESC'); 
			else{
                if(($acc != '' && $acc != '-') && $server == 'All')
                    $items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape($per_page) . ' id, mu_id, name, newname, fromAccount, toAccount, transferDate, server, toserver FROM DmN_CharacterTransferServerLogs WHERE fromAccount like \'%' . $this->website->db('web')->escape($acc) . '%\' AND id Not IN (SELECT Top ' . $this->website->db('web')->escape($per_page * ($page - 1)) . ' id FROM DmN_CharacterTransferServerLogs WHERE fromAccount like \'%' . $this->website->db('web')->escape($acc) . '%\' ORDER BY id DESC) ORDER BY id DESC'); else
				$items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape($per_page) . ' id, mu_id, name, newname, fromAccount, toAccount, transferDate, server, toserver FROM DmN_CharacterTransferServerLogs WHERE fromAccount like \'%' . $this->website->db('web')->escape($acc) . '%\' AND server = '.$this->website->db('web')->escape($server).' AND id Not IN (SELECT Top ' . $this->website->db('web')->escape($per_page * ($page - 1)) . ' id DmN_CharacterTransferServerLogs WHERE fromAccount like \'%' . $this->website->db('web')->escape($acc) . '%\' AND server = '.$this->website->db('web')->escape($server).' ORDER BY id DESC) ORDER BY id DESC');
            }
			$logs = [];
            foreach($items->fetch_all() as $value){
				$logs[] = [
					'char_id' => $value['mu_id'], 
					'name' => $value['name'],
					'newname' => $value['newname'],
					'fromAccount' => htmlspecialchars($value['fromAccount']), 
					'server' => htmlspecialchars($value['server']), 
					'toserver' => htmlspecialchars($value['toserver']), 
					'date' => date('d/m/Y H:i:s', $value['transferDate'])
				];
            }
            return $logs;
        }

        public function count_total_logs($acc = '', $server = 'All'){
            $sql = '';
            if($acc != '' && $acc != '-'){
                $sql .= 'WHERE fromAccount like \'%' . $this->website->db('web')->escape($acc) . '%\'';
                if($server != 'All'){
                    $sql .= ' AND server = '.$this->website->db('web')->escape($server).'';
                }
            }
            $count = $this->website->db('web')->snumrows('SELECT COUNT(id) AS count FROM DmN_CharacterTransferServerLogs ' . $sql . '');
            return $count;
        }

				
        public function deleteChar($name, $table, $identifier){
			if($this->website->db('game', $this->session->userdata(['user' => 'server']))->check_if_table_exists($table)){
				$this->website->db('game', $this->session->userdata(['user' => 'server']))->query('DELETE FROM ' . $table . ' WHERE ' . $identifier . ' = \'' . $name . '\'');
			}
        }

        public function check_connect_stat($account, $server){
            $stmt = $this->website->db('account', $server)->prepare('SELECT ConnectStat FROM MEMB_STAT WHERE memb___id = :user');
            $stmt->execute([':user' => $account]);
            if($status = $stmt->fetch()){
                return ($status['ConnectStat'] == 0);
            }
            return true;
        }
		
		public function disconnectMember($account, $server){
			$stmt = $this->website->db('account', $server)->prepare('UPDATE MEMB_STAT SET ConnectStat = 0 WHERE memb___id = :user');
			$stmt->execute([':user' => $account]);
		}

		private function get_columns($table, $db){
            $data = $db->query('SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = \'' . $table . '\'');
            $arr = [];
            while($row = $data->fetch()){
                $arr[] = $row['COLUMN_NAME'];
            }
            return $arr;
        }

    }
