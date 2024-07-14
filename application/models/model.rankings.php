<?php
   in_file();

    class Mrankings extends model
    {
        private $players = [];
        private $guilds = [];
        private $killers = [];
        private $online_players = [];
        private $gm_list = [];
        private $ban_list = [];
        private $bc = [];
        private $ds = [];
        private $cc = [];
        private $cs = [];
        private $duels = [];
        private $voters = [];
        private $online = [];
        private $gens = [];
        private $class_filter = false;
        private $c_class = '';
        private $cache_name = '';
        private $top = false;
        private $order = '';
        private $gens_data = ['vanert' => 0, 'duprian' => 0, 'perc_d' => 0];
        public $error = false, $vars = [];

        public function __contruct(){
            parent::__construct();
        }
		
		public function get_ranking_data($type, $server, $config, $table_config, $top = false, $pageNumber = 1){
            switch($type){
                case 'players':
                    return $this->load_player_rankings($server, $config, $table_config, $top, $pageNumber);
                    break;
                case 'guilds':
                    return $this->load_guild_rankings($server, $config, $top, $table_config);
                    break;
                case 'votereward':
                    return $this->load_vote_rankings($server, $config, $top);
                    break;
                case 'killer':
                    return $this->load_killers_rankings($server, $config, $table_config, $top);
                    break;
                case 'online':
                    return $this->load_online_rankings($server, $config, $top);
                    break;
                case 'gens':
                    return $this->load_gens_rankings($server, $config, $top);
                    break;
                case 'bc':
                    return $this->load_bc_rankings($server, $config, $table_config, $top);
                    break;
                case 'ds':
                    return $this->load_ds_rankings($server, $config, $table_config, $top);
                    break;
                case 'cc':
                    return $this->load_cc_rankings($server, $config, $table_config, $top);
                    break;
                case 'cs':
                    return $this->load_cs_rankings($server, $config, $table_config, $top);
                    break;
                case 'duels':
                    return $this->load_duel_rankings($server, $config, $table_config, $top);
                    break;	
                default:
                    return false;
                    break;
            }
        }
		
		
		public function kill_stats($page = 1, $per_page = 25, $server = '', $order_column = 2, $order_dir = 'desc'){
			if($this->website->db('game', $server)->check_if_table_exists('C_PlayerKiller_Info')){
				$dir = ($order_dir == 'desc') ? 'DESC' : 'ASC';
				switch($order_column){
					case 0:
						$column = 'Killer';
						break;
					case 1:
						$column = 'Victim';
						break;
					default:
					case 2:
						$column = 'KillDate';
						break;
				}
				return $this->website->db('game', $server)->query('SELECT Victim, Killer, KillDate FROM C_PlayerKiller_Info ORDER BY ' . $column . ' ' . $dir . ' OFFSET (('.$page.' - 1) * '.$per_page.') ROWS FETCH NEXT '.$per_page.' ROWS ONLY;')->fetch_all();				
			}
			return false;
		}
		
		
		public function count_total_kill_stats($server){
            if($this->website->db('game', $server)->check_if_table_exists('C_PlayerKiller_Info')){
				$count = $this->website->db('game', $server)->snumrows('SELECT COUNT(Killer) AS count FROM C_PlayerKiller_Info');
				return $count;
			}
			else{
				return 0;
			}
        }
		
		private function check_cache($name, $identifier, $server, $time = 360){
            $this->cache_name = ($this->class_filter == true) ? $name . '#' . $server . '#' . $this->c_class . '#' . $this->top : $name . '#' . $server . '#' . $this->top;
            $this->website->check_cache($this->cache_name, $identifier, $time);
        }
		
		
		private function load_player_rankings($server, $config, $table_config, $top, $pageNumber = 1){
            if(!isset($config['player']) || $table_config == false)
                return false;
            $this->top = ($top != false) ? $top : $config['player']['count'];

            if($this->top == 0)
                return false;
            $this->check_cache('players#'.$pageNumber, 'players', $server, $config['player']['cache_time']);
            if(!$this->website->cached){
                $class = ($this->class_filter == true) ? $this->gen_rank_by_class($this->c_class) : '';
				$inactivePlayers = isset($config['player']['inactive_players']) ? (int)$config['player']['inactive_players'] : 0;
                $status = $this->join_memb_stat((int)($config['player']['display_status'] == 1 || $config['player']['display_country'] == 1 || $inactivePlayers == 1), 'c.AccountId', ', m.IP,  m.ConnectStat, m.DisConnectTM, m.ServerName', $server);
				$mlevel = $this->join_master_level($config['player']['display_master_level'], $table_config, 'c.Name');
                $res = $this->join_resets($config['player']['display_resets'], $table_config, 'c.Name');
                $gres = $this->join_gresets($config['player']['display_gresets'], $table_config, 'c.Name');
                $account_char = $this->join_account_character('c.Name');
                $gms = $this->include_gms($config['player']['display_gms']);
				//$gens = $this->joinGens('c.Name');
				$exclude_list = '';
                if(isset($config['player']['excluded_list'])){
                    $exclude_list = $this->exclude_list($config['player']['excluded_list']);
                }
                if($class != ''){
                    $class = ' WHERE ' . $class;
                }
                if($class == '' && $gms != ''){
                    $gms = ' WHERE ' . str_replace(' AND', '', $gms);
                }
                if($class == '' && $gms == '' && $exclude_list != ''){
                    $exclude_list = ' WHERE ' . str_replace(' AND', '', $exclude_list);
                }
				
                $this->create_order([$gres[0], $res[0],  $mlevel[0], 'c.cLevel', 'c.Experience']);

				$serverCode = '';
				$joinServer = '';
				if(defined('CUSTOM_SERVER_CODES') && array_key_exists($server, CUSTOM_SERVER_CODES)){
					$dbMemb = $this->website->get_db_from_server($server, true);
					if($class == '' && $gms == '' && $exclude_list != ''){
						$serverCode = ' WHERE mi.servercode = '.CUSTOM_SERVER_CODES[$server].'';
					}
					else{
						$serverCode = ' AND mi.servercode = '.CUSTOM_SERVER_CODES[$server].'';
					}
					$joinServer = ' FULL JOIN '.$dbMemb.'.dbo.MEMB_INFO AS mi ON (c.AccountId Collate Database_Default = mi.memb___id Collate Database_Default)';
				}
				
				$inactive = '';
				if($inactivePlayers == 1){
					if($class == '' && $gms == '' && $exclude_list == '' && $serverCode == ''){
						$inactive = ' WHERE m.DisConnectTM >= \'' . date('Ymd H:i:s', strtotime('-'.(int)$config['player']['inactivity_time'].' days', mktime(0, 0, 0))) . '\'';
					}
					else{
						$inactive = ' AND m.DisConnectTM >= \'' . date('Ymd H:i:s', strtotime('-'.(int)$config['player']['inactivity_time'].' days', mktime(0, 0, 0))) . '\'';
					}
				}
				
                $select = 'c.AccountId, c.Name, c.cLevel, c.Experience, c.Class, c.MapNumber, c.Ctlcode ' . $account_char[0] . $status[0] . $mlevel[0] .$res[0] . $gres[0];
                //$query = $this->website->db('game', $server)->query('SELECT ' . $select . ' FROM Character AS c ' . $account_char[1] . $status[1] . $mlevel[1] . $res[1] . $gres[1] . $class . $gms . $exclude_list . ' GROUP BY ' . $select . ' ' . $this->order.' OFFSET (('.$pageNumber.' - 1) * '.$this->top.') ROWS FETCH NEXT '.$this->top.' ROWS ONLY;');
                $query = $this->website->db('game', $server)->query('SELECT TOP '.$this->top.' ' . $select . ' FROM Character AS c ' . $account_char[1] . $status[1] . $joinServer . $mlevel[1] . $res[1] . $gres[1] . $class . $gms . $exclude_list . $serverCode . $inactive . ' GROUP BY ' . $select . ' ' . $this->order.'');
				
			    if($query){
                    $i = 0;
                    $is_hide_module = false;
					if($this->config->is_plugin_installed('hide_info')){
						$this->load->model('application/plugins/hide_info/models/hide_info'); 
                        $is_hide_module = true;
					}
                    while($row = $query->fetch()){
						$cntrCode = 'us';
						$cntrLong = 'United States';
						if($config['player']['display_country'] == 1 && isset($row['IP'])){
							$cntrCode = $this->website->get_country_code($row['IP']);
							$cntrLong = $this->website->codeToCountryName($cntrCode);
						}
						/*$mlExpNeed = number_format($row['mlExperience']/$row['mlNextExp'] * 100, 2); 
						if($mlExpNeed == 'nan'){
							$mlExpNeed = ' - ';
						}
						else{
							$mlExpNeed .=  '%';
						}*/
						$customTitle = '';
						if(defined('CUSTOM_TITLES') && is_array(CUSTOM_TITLES)){
							if(isset(CUSTOM_TITLES[$server][$row['Name']])){
								if(CUSTOM_TITLES[$server][$row['Name']]['img'] != ''){
									$customTitle = CUSTOM_TITLES[$server][$row['Name']]['img'];
								}
								else{
									$customTitle = CUSTOM_TITLES[$server][$row['Name']]['title'];
								}
							}
						}
						$guild = $this->check_guild($row['Name'], $server);
                        $this->players[] = [
							'name' => (!mb_detect_encoding($row['Name'], 'utf-8', true)) ? mb_convert_encoding($row['Name'], "UTF-8") : $row['Name'],
							'name_hex' => bin2hex($row['Name']), 
							'level' => $row['cLevel'], 
							'exp' => $row['Experience'],
							'resets' => ($config['player']['display_resets'] == 1 && $table_config['resets']['column'] != '') ? $row[$table_config['resets']['column']] : 0, 
							'gresets' => ($config['player']['display_gresets'] == 1 && $table_config['grand_resets']['column'] != '') ? $row[$table_config['grand_resets']['column']] : 0, 
							//'gens' => isset($row['Influence']) ? $row['Influence'] : 0, 
							'class' => $this->website->get_char_class($row['Class']), 'class_small' => $this->website->get_char_class($row['Class'], true), 
							'loc' => $this->website->get_map_name($row['MapNumber']), 
							'status' => ($config['player']['display_status'] == 1) ? ($row['ConnectStat'] == 1 && ($row['GameIDC'] == $row['Name'])) ? 1 : 0 : 0, 
							'hidden' => ($is_hide_module == true) ? $this->Mhide_info->check_hide_time($row['AccountId'], $server) : false,
							'mlevel' => ($config['player']['display_master_level'] == 1) ? $row[$table_config['master_level']['column']] : 0,
							//'mnexexp' => $mlExpNeed,	
							'country' => $cntrCode,
							'country_long' => $cntrLong,
							'vip' => $this->check_vip($row['AccountId'], $server),
							'custom' => $customTitle,
							'g_mark' => ($guild != false) ? $guild['logo'] : '',
							'g_name' => ($guild != false) ? $guild['name'] : ''
						];
                        $i++;
                    }
                    if($i > 0){
                        $data = $this->players;
						$this->players = [];
                        $this->website->set_cache($this->cache_name, $data, (int)$config['player']['cache_time']);
                        return $data;
                    }
                }
                return false;
            }
            return $this->website->players;
        }
		
		
		public function check_guild($name, $server){
            $stmt = $this->website->db('game/newConnection', $server)->query('SELECT gm.G_Name, g.G_Mark FROM GuildMember AS gm INNER JOIN Guild AS g ON (gm.G_Name Collate Database_Default = g.G_Name Collate Database_Default) WHERE gm.Name = '.$this->website->db('game', $server)->escape($name).'');
			$guild = $stmt->fetch();
            if($guild != false){
				return [
					'name' => $guild['G_Name'],
					'name_hex' => bin2hex($guild['G_Name']),
					'logo' => urlencode(bin2hex($guild['G_Mark']))
				];
			}
			return false;
        }
		
		public function countTotalPlayers($server, $config){
			$class = ($this->class_filter == true) ? $this->gen_rank_by_class($this->c_class) : '';
			$gms = $this->include_gms($config['player']['display_gms']);
			$exclude_list = '';
			if(isset($config['player']['excluded_list'])){
				$exclude_list = $this->exclude_list($config['player']['excluded_list']);
			}
			if($class != ''){
				$class = ' WHERE ' . $class;
			}
			if($class == '' && $gms != ''){
				$gms = ' WHERE ' . str_replace(' AND', '', $gms);
			}
			if($class == '' && $gms == '' && $exclude_list != ''){
				$exclude_list = ' WHERE ' . str_replace(' AND', '', $exclude_list);
			}
			
			return $this->website->db('game', $server)->snumrows('SELECT COUNT(c.Name) AS count FROM Character  AS c ' . $class . $gms . $exclude_list);
		}
		
		//private function joinGens($bound = 'a.GameIDC'){
		//	$sql = [', ge.Influence', ' LEFT JOIN IGC_Gens AS ge ON (' . $bound . ' Collate Database_Default = ge.Name Collate Database_Default)'];
		//	return $sql;
		//}
		
		
		private function check_vip($account, $server){
			/*$stmt = $this->website->db('account', $server)->prepare('SELECT AccountLevel FROM MEMB_INFO WHERE memb___id = :account AND AccountExpireDate >= \''.date('Y-m-d H:i:s', time()).'\'');
            $stmt->execute([':account' => $account]);
			if($row = $stmt->fetch()){
                return $row['AccountLevel'];
            }
            return 0;
		    $stmt = $this->website->db('web')->prepare('SELECT viptype FROM DmN_Vip_Users WHERE memb___id = :account AND server = :server');
            $stmt->execute([':account' => $account, ':server' => $server]);
            if($row = $stmt->fetch()){
                return $row['viptype'];
            }
            return 0;*/
			return 0;
        }
		
		public function load_found_chars($name, $server){
            $status = $this->join_memb_stat(1, 'c.AccountId', ', m.IP,  m.ConnectStat', $server);
            $query = $this->website->db('game', $server)->query('SELECT c.Name, c.AccountId ' . $status[0] . ' FROM Character AS c ' . $status[1] . ' WHERE c.Name LIKE \'%' . $this->website->db('game', $server)->sanitize_var($name) . '%\'');
            if($query){
                $i = 0;
                while($row = $query->fetch()){
                    $this->players[] = ['name' => $row['Name'], 'url' => $this->config->base_url . 'character/' . bin2hex($row['Name']) . '/' . $server, 'status' => ($row['ConnectStat'] == 1) ? 1 : 0, 'country' => (isset($row['IP'])) ? $this->website->get_country_code($row['IP']) : 'us', 'country_long' => (isset($row['IP'])) ? $this->website->codeToCountryName($this->website->get_country_code($row['IP'])) : 'United States'];
                    $i++;
                }
                if($i > 0){
                    return $this->players;
                }
                return false;
            }
            return false;
        }
		
		public function load_found_guilds($name, $server){
            $query = $this->website->db('game', $server)->query('SELECT G_Name FROM Guild WHERE G_Name LIKE \'%' . $this->website->db('game', $server)->sanitize_var($name) . '%\'');
            if($query){
                $i = 0;
                while($row = $query->fetch()){
                    $this->guilds[] = ['name' => $row['G_Name'], 'url' => $this->config->base_url . 'guild/' . bin2hex($row['G_Name']) . '/' . $server];
                    $i++;
                }
                if($i > 0){
                    return $this->guilds;
                }
                return false;
            }
            return false;
        }
		
		private function load_guild_rankings($server, $config, $top, $table_config){
            if(!isset($config['guild']))
                return false;
            $this->top = ($top != false) ? $top : $config['guild']['count'];
            if($this->top == 0)
                return false;
            $this->check_cache('guilds', 'guilds', $server, $config['guild']['cache_time']);
            if(!$this->website->cached){
				 $exclude_list = '';
                if(isset($config['guild']['excluded_list'])){
                    $exclude_list = $this->exclude_list($config['guild']['excluded_list'], 'g.G_Name');
                }
                if($exclude_list != ''){
                    $exclude_list = 'WHERE ' . str_replace(' AND', '', $exclude_list);
                }
				
				$sum_level = ', SUM(c.cLevel) AS clevel';
                $join = 'INNER JOIN Character AS c ON (gm.Name Collate Database_Default = c.Name Collate Database_Default)';
				$order = '';
				$joinedML = 0;
				
				 if($table_config && isset($table_config['master_level']) && (isset($table_config['master_level']['column']) && $table_config['master_level']['column'] != '')){
					if(strtolower($table_config['master_level']['table']) == 'character'){
						$sum_mlevel = ', SUM(c.' . $table_config['master_level']['column'] . ') AS mlevel';
						if(isset($config['guild']['order_by']) && $config['guild']['order_by'] == 1){
							$order = 'SUM(c.'.$table_config['master_level']['column'].') DESC, ';
						}
					}
					else{
						$joinedML = 1;
						$join .= ' INNER JOIN '.$table_config['master_level']['table'].' AS ml ON (gm.Name Collate Database_Default = ml.'.$table_config['master_level']['identifier_column'].' Collate Database_Default)';
						$sum_mlevel = ', SUM(ml.' . $table_config['master_level']['column'] . ') AS mlevel';
						if(isset($config['guild']['order_by']) && $config['guild']['order_by'] == 1){
							$order = 'SUM(ml.'.$table_config['master_level']['column'].') DESC, ';
						}
					}
                } else{
                    $sum_mlevel = ', SUM(0) AS mlevel';
                }  
				
                if($table_config && isset($table_config['resets']) && (isset($table_config['resets']['column']) && $table_config['resets']['column'] != '')){
                    $sum_resets = ', SUM(c.' . $table_config['resets']['column'] . ') AS resets';
                    if(isset($config['guild']['order_by']) && $config['guild']['order_by'] == 2){
						$order = 'SUM(c.'.$table_config['resets']['column'].') DESC, ';
					}
                } else{
                    $sum_resets = ', SUM(0) AS resets';
                }  
				if($table_config && isset($table_config['grand_resets']) && (isset($table_config['grand_resets']['column']) && $table_config['grand_resets']['column'] != '')){
                    $sum_gresets = ', SUM(c.' . $table_config['grand_resets']['column'] . ') AS grand_resets';
                    if(isset($config['guild']['order_by']) && $config['guild']['order_by'] == 3){
						$order = 'SUM(c.' . $table_config['grand_resets']['column'] . ') DESC, ';
					}
                } else{
                    $sum_gresets = ', SUM(0) AS grand_resets';
                }
				
				if(isset($config['guild']['order_by']) && $config['guild']['order_by'] == 4){
					$order = 'SUM(c.cLevel) DESC, ';
                }
						
				if(isset($config['guild']['order_by']) && $config['guild']['order_by'] == 5){
					if(strtolower($table_config['master_level']['table']) == 'character'){
						$sum_total = ', SUM(c.cLevel + c.' . $table_config['master_level']['column'] . ') AS totallvl';
						$order = 'SUM(c.cLevel + c.' . $table_config['master_level']['column'] . ') DESC, ';
					}
					else{
						$sum_total = ', SUM(c.cLevel + ml.' . $table_config['master_level']['column'] . ') AS totallvl';
						if($joinedML == 0){
							$join .= ' INNER JOIN '.$table_config['master_level']['table'].' AS ml ON (gm.Name Collate Database_Default = ml.'.$table_config['master_level']['identifier_column'].' Collate Database_Default)';
						}
						$order = 'SUM(c.cLevel + ml.'.$table_config['master_level']['column'].') DESC, ';
					}
                }
				else{
                    $sum_total = ', SUM(0) AS totallvl';
                }
				
				$serverCode = '';
				$joinServer = '';
				if(defined('CUSTOM_SERVER_CODES') && array_key_exists($server, CUSTOM_SERVER_CODES)){
					$dbMemb = $this->website->get_db_from_server($server, true);
					if($exclude_list != ''){
						$serverCode = ' WHERE mi.servercode = '.CUSTOM_SERVER_CODES[$server].'';
					}
					else{
						$serverCode = ' AND mi.servercode = '.CUSTOM_SERVER_CODES[$server].'';
					}
					$joinServer = ' INNER JOIN '.$dbMemb.'.dbo.MEMB_INFO AS mi ON (c.AccountId Collate Database_Default = mi.memb___id Collate Database_Default)';
				}
				
                $query = $this->website->db('game', $server)->query('SELECT TOP ' . (int)$this->top . ' g.G_Name, g.G_Mark, g.G_Score, g.G_Master, COUNT(gm.Name) AS membercount ' . $sum_gresets . $sum_resets . $sum_mlevel . $sum_level .  $sum_total .' FROM Guild AS g FULL JOIN GuildMember AS gm ON (g.G_Name Collate Database_Default = gm.G_Name Collate Database_Default) ' . $join . $joinServer . ' ' . $exclude_list . $serverCode. ' GROUP BY g.G_Name, g.G_Mark, g.G_Score, g.G_Master, g.G_Notice ORDER BY ' . $order . ' g.G_Score DESC, g.G_Name ASC');
                
				$is_guild_recruit_module = false;
				if($this->config->is_plugin_installed('guild_recruit')){
					$is_guild_recruit_module = true;
				}
				
				if($query){
                    $i = 0;
					$max_gr = defined('MAX_GR') ? MAX_GR : 70;
                    while($row = $query->fetch()){
                       $this->guilds[] = [
							'name' => htmlspecialchars($row['G_Name']), 
							'name_hex' => bin2hex($row['G_Name']), 
							'master' => htmlspecialchars($row['G_Master']), 
							'master_hex' => bin2hex($row['G_Master']), 
							'score' => number_format((int)$row['G_Score']), 
							'members' => $row['membercount'], 
							'mark' => urlencode(bin2hex($row['G_Mark'])), 
							'gresets' => $row['grand_resets'], 
							'resets' => $row['resets'], 
							'mlevel' => number_format($row['mlevel']), 
							'clevel' => number_format($row['clevel']), 	
							'totallvl' => number_format($row['totallvl']), 							
							'points' => defined('MAX_GR') ? ($row['grand_resets']*$max_gr)+$row['resets'] : 0,	
							'server' => $server,
							'recruit_data' => ($is_guild_recruit_module == true) ? $this->guild_recruit_data($row['G_Name'], $server) : false
						];
                       $i++;
                    }
					
                    if($i > 0){
						if(defined('MAX_GR')){
							usort( $this->guilds, function($a, $b) {
								return $b['points'] <=> $a['points'];
							});
						}
                        $this->website->set_cache($this->cache_name, $this->guilds, (int)$config['guild']['cache_time']);
                        return $this->guilds;
                    }
                }
                return false;
            }
            return $this->website->guilds;
        }
		
		private function guild_recruit_data($gname, $server){
			return $this->website->db('web')->query('SELECT TOP 1 id, discord_link, gname FROM DmN_GuildRecruit WHERE gname = '.$this->website->db('web')->escape($gname).' AND server = '.$this->website->db('web')->escape($server).'')->fetch();
		}
		
		private function load_killers_rankings($server, $config, $table_config, $top){
            if(!isset($config['killer']) || $table_config == false)
                return false;
            $this->top = ($top != false) ? $top : $config['killer']['count'];
            if($this->top == 0)
                return false;
            $this->check_cache('killers', 'killers', $server, $config['killer']['cache_time']);
            if(!$this->website->cached){
                $ip = $this->join_memb_stat($config['killer']['display_country'], 'k.AccountId', ', m.IP', $server);
                $mlevel = $this->join_master_level($config['killer']['display_master_level'], $table_config, 'k.Name');
                $res = $this->join_resets($config['killer']['display_resets'], $table_config, 'k.Name');
                $gres = $this->join_gresets($config['killer']['display_gresets'], $table_config, 'k.Name');
                $gms = $this->include_gms($config['killer']['display_gms'], 'k.CtlCode');
				if(isset($config['killer']['excluded_list'])){
                    $exclude_list = $this->exclude_list($config['killer']['excluded_list'], 'k.Name');
                }
                $select = 'k.Name, k.dmn_pk_count, k.PkLevel, k.PkTime, k.Class' . $ip[0] . $mlevel[0] . $res[0] . $gres[0];
				
				$serverCode = '';
				$joinServer = '';
				if(defined('CUSTOM_SERVER_CODES') && array_key_exists($server, CUSTOM_SERVER_CODES)){
					$dbMemb = $this->website->get_db_from_server($server, true);
					if($gms != '' && $exclude_list != ''){
						$serverCode = ' WHERE mi.servercode = '.CUSTOM_SERVER_CODES[$server].'';
					}
					else{
						$serverCode = ' AND mi.servercode = '.CUSTOM_SERVER_CODES[$server].'';
					}
					$joinServer = ' INNER JOIN '.$dbMemb.'.dbo.MEMB_INFO AS mi ON (k.AccountId Collate Database_Default = mi.memb___id Collate Database_Default)';
				}
				
                $query = $this->website->db('game', $server)->query('SELECT TOP ' . (int)$this->top . ' ' . $select . ' FROM Character  AS k ' . $ip[1] . $mlevel[1] . $res[1] . $gres[1] . $joinServer . ' WHERE k.dmn_pk_count > 0 ' . $gms . $exclude_list . $serverCode . ' GROUP BY ' . $select . ' ORDER BY k.dmn_pk_count DESC');
                if($query){
                    $i = 0;
                    while($row = $query->fetch()){
                        $this->killers[] = [
							'name' => $row['Name'], 
							'name_hex' => bin2hex($row['Name']), 
							'PkCount' => $row['dmn_pk_count'], 
							'PkLevel' => $this->website->pk_level($row['PkLevel']), 
							'PkTime' => $row['PkTime'], 
							'resets' => ($config['killer']['display_gresets'] == 1) ? $row[$table_config['resets']['column']] : 0, 
							'gresets' => ($config['killer']['display_gresets'] == 1) ? $row[$table_config['grand_resets']['column']] : 0, 
							'mlevel' => ($config['killer']['display_master_level'] == 1) ? $row[$table_config['master_level']['column']] : 0, 
							'class' => $this->website->get_char_class($row['Class']), 'class_small' => $this->website->get_char_class($row['Class'], true), 
							'country' => ($config['killer']['display_country'] == 1 && isset($row['IP'])) ? $this->website->get_country_code($row['IP']) : 'us'];
                        $i++;
                    }
                    if($i > 0){
                        $this->website->set_cache($this->cache_name, $this->killers, (int)$config['killer']['cache_time']);
                        return $this->killers;
                    }
                }
                return false;
            }
            return $this->website->killers;
        }
		
		private function load_vote_rankings($server, $config, $top){
            if(!isset($config['voter']))
                return false;
            $this->top = ($top != false) ? $top : $config['voter']['count'];
            if($this->top == 0)
                return false;
            $this->check_cache('voters', 'voters', $server, $config['voter']['cache_time']);
            if(!$this->website->cached){
                $ip = $this->join_memb_stat($config['voter']['display_country'], 'v.account', ', m.IP', $server);
				$exclude_list = '';
                if(isset($config['voter']['excluded_list'])){
                    $exclude_list = $this->exclude_list($config['voter']['excluded_list'], 'character');
                }
                $select = 'v.account, v.character, v.lastvote, v.totalvotes ' . $ip[0];
                $query = $this->website->db('web')->query('SELECT TOP ' . (int)$this->top . ' ' . $select . ' FROM DmN_Votereward_Ranking AS v ' . $ip[1] . ' WHERE server = '.$this->website->db('web')->escape($server).' AND year = ' . date('Y', time()) . ' AND month = \'' . date('F', time()) . '\' ' . $exclude_list . ' GROUP BY ' . $select . ' ORDER BY totalvotes DESC');
                if($query){
                    $i = 0;
                    while($row = $query->fetch()){
                        $char = $this->website->db('game', $server)->query('SELECT GameIDC FROM AccountCharacter WHERE Id = '.$this->website->db('game', $server)->escape($row['account']).'')->fetch();
                        $this->voters[] = ['name' => $char['GameIDC'], 'name_hex' => bin2hex($char['GameIDC']), 'lastvote' => date(DATE_FORMAT, $row['lastvote']), 'totalvotes' => $row['totalvotes'], 'country' => ($config['voter']['display_country'] == 1 && isset($row['IP'])) ? $this->website->get_country_code($row['IP']) : 'us'];
                        $i++;
                    }
                    if($i > 0){
                        $this->website->set_cache($this->cache_name, $this->voters, (int)$config['voter']['cache_time']);
                        return $this->voters;
                    }
                }
                return false;
            }
            return $this->website->voters;
        }
		
		private function load_online_rankings($server, $config, $top){
            if(!isset($config['online']))
                return false;
            $this->top = ($top != false) ? $top : $config['online']['count'];
            if($this->top == 0)
                return false;
            $this->check_cache('online', 'online', $server, $config['online']['cache_time']);
            if(!$this->website->cached){
                $ip = $this->join_memb_stat($config['online']['display_country'], 'd.memb___id', ', m.IP', $server, 'INNER JOIN');
                $select = 'd.memb___id, SUM(d.TotalTime) AS TotalTime ' . $ip[0];
                $query = $this->website->db('web')->query('SELECT TOP ' . (int)$this->top . ' ' . $select . ' FROM DmN_OnlineCheck AS d ' . $ip[1] . ' WHERE ' . str_replace('ServerName', 'd.ServerName', $this->website->server_code($this->website->get_servercode($server), false)) . ' ' . $this->exclude_list($config['online']['excluded_list'], 'd.memb___id') . ' GROUP BY d.memb___id ' . $ip[0] . '  ORDER BY SUM(TotalTime) DESC');
                if($query){
                    $i = 0;
                    $rows = array_unique($query->fetch_all(), SORT_REGULAR);
                    foreach($rows AS $row){
                        $char = $this->website->db('game', $server)->query('SELECT GameIDC FROM AccountCharacter WHERE Id = '.$this->website->db('game', $server)->escape($row['memb___id']).'')->fetch();
										 
                        $this->online[] = ['name' => $char['GameIDC'], 'name_hex' => bin2hex($char['GameIDC']), 'server' => $server, 'h' => floor($row['TotalTime'] / 60), 'm' => ($row['TotalTime'] - (floor($row['TotalTime'] / 60) * 60)), 'country' => ($config['online']['display_country'] == 1 && isset($row['IP'])) ? $this->website->get_country_code($row['IP']) : 'us'];
                        $i++;
                    }
                    if($i > 0){
                        $this->website->set_cache($this->cache_name, $this->online, (int)$config['online']['cache_time']);
                        return $this->online;
                    }
                }
                return false;
            }
            return $this->website->online;
        }
		
		private function load_gens_rankings($server, $config, $top){
            if(!isset($config['gens']))
                return false;
            $this->top = ($top != false) ? $top : $config['gens']['count'];
            if($this->top == 0)
                return false;
            $this->check_cache('gens', 'gens', $server, $config['gens']['cache_time']);
            if(!$this->website->cached){
                $i = 0;
                $data = $this->gens_query($config['gens']['type'], $server);
                if(!empty($data)){
                    foreach($data AS $row){
                        if($i == 0){
                            $this->gens_data($row['scorev'], $row['scored']);
                        }
                        $this->gens[] = [
							'name' => $row['Name'], 
							'name_hex' => bin2hex($row['Name']), 
							'contr' => $row['contribution'], 
							'type' => $this->gens_family_from_id($row['family']), 
							'rank' => $this->gens_rank($row['contribution'], $row['rank'])
						];
                        $i++;
                    }
                    $this->gens['info'] = $this->gens_data;
                }
                if($i > 0){
                    $this->website->set_cache($this->cache_name, $this->gens, (int)$config['gens']['cache_time']);
                    return $this->gens;
                }
            }
            return $this->website->gens;
        }
		
		private function gens_query($type, $server){
            switch($type){
                case 'scf':
                    return $this->website->db('game', $server)->query('SELECT TOP ' . (int)$this->top . ' Name, SCFGensContribution AS contribution, SCFGensFamily AS family, SCFGensRank AS rank, (SELECT COALESCE(SUM(SCFGensContribution),1) FROM Character WHERE SCFGensFamily = 1) AS scored, (SELECT COALESCE(SUM(SCFGensContribution),1) FROM Character WHERE SCFGensFamily = 2) AS scorev FROM Character WHERE SCFGensFamily = 1 OR SCFGensFamily = 2 ORDER BY SCFGensContribution DESC')->fetch_all();
                    break;
                case 'muengine':
                    return $this->website->db('game', $server)->query('SELECT TOP ' . (int)$this->top . ' Name, GensType AS family, GensRank AS rank, GensContribution AS contribution, (SELECT COALESCE(SUM(GensContribution),1) FROM Character WHERE GensType = 1) AS scored, (SELECT COALESCE(SUM(GensContribution),1) FROM Character WHERE GensType = 2) AS scorev FROM Character WHERE GensType = 1 OR GensType = 2 ORDER BY GensContribution DESC')->fetch_all();
                    break;
                case 'zteam':
                    return $this->website->db('game', $server)->query('SELECT TOP ' . (int)$this->top . ' memb_char AS Name, memb_clan AS family, memb_rank AS rank, memb_contribution AS contribution, (SELECT COALESCE(SUM(memb_contribution),1) FROM GensUserInfo WHERE memb_clan = 1) AS scored, (SELECT COALESCE(SUM(memb_contribution),1) FROM GensUserInfo WHERE memb_clan = 2) AS scorev FROM GensUserInfo WHERE memb_clan = 1 OR memb_clan = 2 ORDER BY memb_contribution DESC')->fetch_all();
                    break;
                case 'exteam':
                    return $this->website->db('game', $server)->query('SELECT TOP ' . (int)$this->top . ' Name, Influence AS family, Rank AS rank, Contribute AS contribution, (SELECT COALESCE(SUM(Contribute),1) FROM GensMember WHERE Influence = 1) AS scored, (SELECT COALESCE(SUM(Contribute),1) FROM GensMember WHERE Influence = 2) AS scorev FROM GensMember WHERE Influence = 1 OR Influence = 2 ORDER BY Contribute DESC')->fetch_all();
                    break;
                case 'igcn':
                    return $this->website->db('game', $server)->query('SELECT TOP ' . (int)$this->top . ' Name, Influence AS family, Rank AS rank, points AS contribution, (SELECT COALESCE(SUM(points),1) FROM IGC_Gens WHERE Influence = 1) AS scored, (SELECT COALESCE(SUM(points),1) FROM IGC_Gens WHERE Influence = 2) AS scorev FROM IGC_Gens WHERE Influence = 1 OR Influence = 2 ORDER BY points DESC')->fetch_all();
                    break;
                case 'xteam':
                    return $this->website->db('game', $server)->query('SELECT TOP ' . (int)$this->top . ' Name, Contribution AS contribution, Family AS family, 0 AS rank, (SELECT COALESCE(SUM(Contribution),1) FROM Gens_Rank WHERE Family = 1) AS scored, (SELECT COALESCE(SUM(Contribution),1) FROM Gens_Rank WHERE Family = 2) AS scorev FROM Gens_Rank WHERE Family = 1 OR Family = 2 ORDER BY Contribution DESC')->fetch_all();
                    break;
				case 'eggame':
					$serverCode = '';
					$joinServer = '';
					if(defined('CUSTOM_SERVER_CODES') && array_key_exists($server, CUSTOM_SERVER_CODES)){
						$dbMemb = $this->website->get_db_from_server($server, true);
						$serverCode = ' AND mi.servercode = '.CUSTOM_SERVER_CODES[$server].'';
						$joinServer = ' INNER JOIN '.$dbMemb.'.dbo.MEMB_INFO AS mi ON (c.AccountId Collate Database_Default = mi.memb___id Collate Database_Default)';
						$joinServerd = ' INNER JOIN '.$dbMemb.'.dbo.MEMB_INFO AS mi ON (d.AccountId Collate Database_Default = mi.memb___id Collate Database_Default)';
						$joinServerv = ' INNER JOIN '.$dbMemb.'.dbo.MEMB_INFO AS mi ON (v.AccountId Collate Database_Default = mi.memb___id Collate Database_Default)';
					}
                    return $this->website->db('game', $server)->query('SELECT TOP ' . (int)$this->top . ' c.Name, c.GenFamily AS family, c.GenRanking AS rank, c.GenContribution AS contribution, (SELECT COALESCE(SUM(d.GenContribution),1) FROM Character AS d '.$joinServerd.' WHERE d.GenFamily = 1 '.$serverCode.') AS scored, (SELECT COALESCE(SUM(v.GenContribution),1) FROM Character AS v '.$joinServerv.' WHERE v.GenFamily = 2 '.$serverCode.') AS scorev FROM Character AS c '.$joinServer.' WHERE (c.GenFamily = 1 OR c.GenFamily = 2) '.$serverCode.' ORDER BY c.GenContribution DESC')->fetch_all();
                    break;	
                default:
                    return [];
                    break;
            }
        }
		
		private function gens_family_from_id($type){
            return ($type == 1) ? 'duprian' : 'vanert';
        }
		
		private function gens_data($varent, $duprian){
			if($varent == 0 && $duprian == 0){
				$varent = 1;
				$duprian = 1;
			}
            $this->gens_data = ['vanert' => $varent, 'duprian' => $duprian, 'perc_d' => ($duprian / ($varent + $duprian)) * 100];
        }
		
		private function gens_rank($points, $rank){
            if($points < 1000)
                $gens_rank = __('Private'); 
			else if($points >= 1000 && $points < 5000)
                $gens_rank = __('Sergant');
            else if($points >= 5000 && $points < 15000)
                $gens_rank = __('Lieutenant');
            else if($points >= 15000 && $points < 50000)
                $gens_rank = __('Officer');
            else if($points >= 50000 && $points < 100000)
                $gens_rank = __('Guard Prefect');
            else if($points >= 100000 && $rank > 8)
                $gens_rank = __('Knight');
            else if($points >= 100000 && $rank == 8)
                $gens_rank = __('Superior Knight');
            else if($points >= 100000 && $rank == 7)
                $gens_rank = __('Knight Commander');
            else if($points >= 100000 && $rank == 6)
                $gens_rank = __('Baron');
            else if($points >= 100000 && $rank == 5)
                $gens_rank = __('Viscount');
            else if($points >= 100000 && $rank == 4)
                $gens_rank = __('Count');
            else if($points >= 100000 && $rank == 3)
                $gens_rank = __('Marquis');
            else if($points >= 100000 && $rank == 2)
                $gens_rank = __('Duke');
            else if($points >= 100000 && $rank == 1)
                $gens_rank = __('Grand Duke');
            else
                $gens_rank = __('Unknown');
            return $gens_rank;
        }
		
		private function load_bc_rankings($server, $config, $table_config, $top){
            if(!isset($config['bc']) || ($table_config == false || $table_config['bc']['table'] == ''))
                return false;
            $this->top = ($top != false) ? $top : $config['bc']['count'];
            if($this->top == 0)
                return false;
            $this->check_cache('bc', 'bc', $server, $config['bc']['cache_time']);
            if(!$this->website->cached){
				$exclude_list = '';
                if(isset($config['bc']['excluded_list'])){
                    $exclude_list = $this->exclude_list($config['bc']['excluded_list'], 'b.'.$table_config['bc']['identifier_column']);
                }
                if($exclude_list != ''){
                    $exclude_list = 'WHERE ' . str_replace(' AND', '', $exclude_list);
                }
				
				$serverCode = '';
				$joinServer = '';
				$addonColumn = '';
				if(defined('CUSTOM_SERVER_CODES') && array_key_exists($server, CUSTOM_SERVER_CODES)){
					$addonColumn = ', AccountId';
					$dbMemb = $this->website->get_db_from_server($server, true);
					if($exclude_list != ''){
						$serverCode = ' WHERE mi.servercode = '.CUSTOM_SERVER_CODES[$server].'';
					}
					else{
						$serverCode = ' AND mi.servercode = '.CUSTOM_SERVER_CODES[$server].'';
					}
					$joinServer = ' INNER JOIN '.$dbMemb.'.dbo.MEMB_INFO AS mi ON (AccountId Collate Database_Default = mi.memb___id Collate Database_Default)';
				}
				$mdb = $this->website->get_db_from_server($server);
                $query = $this->website->db($table_config['bc']['db'], $server)->query('SELECT TOP ' . (int)$this->top . ' b.' . $table_config['bc']['identifier_column'] . $addonColumn . ', SUM(' . $table_config['bc']['column'] . ') AS ' . $table_config['bc']['column'] . ', c.Class FROM ' . $table_config['bc']['table'] . ' AS b '.$joinServer.' INNER JOIN '.$mdb.'.dbo.Character AS c ON (b.' . $table_config['bc']['identifier_column'] . ' Collate Database_Default = c.Name Collate Database_Default) ' . $exclude_list . $serverCode . ' GROUP BY b.' . $table_config['bc']['identifier_column'].', c.Class ' . $addonColumn . ' HAVING SUM(' . $table_config['bc']['column'] . ') > 0 ORDER BY SUM(' . $table_config['bc']['column'] . ') DESC');
                if($query){
                    $i = 0;
                    while($row = $query->fetch()){
                        $this->bc[] = [
							'name' => $row[$table_config['bc']['identifier_column']], 
							'name_hex' => bin2hex($row[$table_config['bc']['identifier_column']]), 
							'score' => $row[$table_config['bc']['column']],
							'class' => $this->website->get_char_class($row['Class']), 
							'class_small' => $this->website->get_char_class($row['Class'], true), 
						];
                        $i++;
                    }
                    if($i > 0){
                        $this->website->set_cache($this->cache_name, $this->bc, (int)$config['bc']['cache_time']);
                        return $this->bc;
                    }
                }
                return false;
            }
            return $this->website->bc;
        }
		
		private function load_ds_rankings($server, $config, $table_config, $top){
            if(!isset($config['ds']) || ($table_config == false || $table_config['ds']['table'] == ''))
                return false;
            $this->top = ($top != false) ? $top : $config['ds']['count'];
            if($this->top == 0)
                return false;
            $this->check_cache('ds', 'ds', $server, $config['ds']['cache_time']);
            if(!$this->website->cached){
				$exclude_list = '';
                if(isset($config['ds']['excluded_list'])){
                    $exclude_list = $this->exclude_list($config['ds']['excluded_list'], 'b.'.$table_config['ds']['identifier_column']);
                }
                if($exclude_list != ''){
                    $exclude_list = 'WHERE ' . str_replace(' AND', '', $exclude_list);
                }
				$serverCode = '';
				$joinServer = '';
				$addonColumn = '';
				if(defined('CUSTOM_SERVER_CODES') && array_key_exists($server, CUSTOM_SERVER_CODES)){
					$addonColumn = ', AccountId';
					$dbMemb = $this->website->get_db_from_server($server, true);
					if($exclude_list != ''){
						$serverCode = ' WHERE mi.servercode = '.CUSTOM_SERVER_CODES[$server].'';
					}
					else{
						$serverCode = ' AND mi.servercode = '.CUSTOM_SERVER_CODES[$server].'';
					}
					$joinServer = ' INNER JOIN '.$dbMemb.'.dbo.MEMB_INFO AS mi ON (AccountId Collate Database_Default = mi.memb___id Collate Database_Default)';
				}
				$mdb = $this->website->get_db_from_server($server);
                $query = $this->website->db($table_config['ds']['db'], $server)->query('SELECT TOP ' . (int)$this->top . ' b.' . $table_config['ds']['identifier_column'] . $addonColumn . ', SUM(' . $table_config['ds']['column'] . ') AS ' . $table_config['ds']['column'] . ', c.Class  FROM ' . $table_config['ds']['table'] . ' AS b '.$joinServer . ' INNER JOIN '.$mdb.'.dbo.Character AS c ON (b.' . $table_config['ds']['identifier_column'] . ' Collate Database_Default = c.Name Collate Database_Default) ' . $exclude_list . $serverCode. ' GROUP BY b.' . $table_config['ds']['identifier_column'] .', c.Class ' . $addonColumn . ' HAVING SUM(' . $table_config['ds']['column'] . ') > 0 ORDER BY SUM(' . $table_config['ds']['column'] . ') DESC');
                if($query){
                    $i = 0;
                    while($row = $query->fetch()){
                        $this->ds[] = [
							'name' => $row[$table_config['ds']['identifier_column']], 
							'name_hex' => bin2hex($row[$table_config['ds']['identifier_column']]), 
							'score' => $row[$table_config['ds']['column']],
							'class' => $this->website->get_char_class($row['Class']), 
							'class_small' => $this->website->get_char_class($row['Class'], true), 
						];
                        $i++;
                    }
                    if($i > 0){
                        $this->website->set_cache($this->cache_name, $this->ds, (int)$config['ds']['cache_time']);
                        return $this->ds;
                    }
                }
                return false;
            }
            return $this->website->ds;
        }
		
		private function load_cc_rankings($server, $config, $table_config, $top){
			
            if(!isset($config['cc']) || ($table_config == false || $table_config['cc']['table'] == ''))
                return false;
            $this->top = ($top != false) ? $top : $config['cc']['count'];
            if($this->top == 0)
                return false;
            $this->check_cache('cc', 'cc', $server, $config['cc']['cache_time']);
            if(!$this->website->cached){
				$exclude_list = '';
                if(isset($config['cc']['excluded_list'])){
                    $exclude_list = $this->exclude_list($config['cc']['excluded_list'], 'b.'.$table_config['cc']['identifier_column']);
                }
                if($exclude_list != ''){
                    $exclude_list = 'WHERE ' . str_replace(' AND', '', $exclude_list);
                }
                $select = 'b.'.$table_config['cc']['identifier_column'] . ', SUM(' . $table_config['cc']['column'] . ') AS ' . $table_config['cc']['column'];
                $order = 'ORDER BY SUM(' . $table_config['cc']['column'] . ') DESC';
                if(isset($table_config['cc']['column2']) && $table_config['cc']['column2'] != ''){
                    $select .= ', SUM(' . $table_config['cc']['column2'] . ') AS ' . $table_config['cc']['column2'];
                    $order .= ', SUM(' . $table_config['cc']['column2'] . ') DESC';
                }
                if(isset($table_config['cc']['column3']) && $table_config['cc']['column3'] != ''){
                    $select .= ', SUM(' . $table_config['cc']['column3'] . ') AS ' . $table_config['cc']['column3'];
                    $order .= ', SUM(' . $table_config['cc']['column3'] . ') DESC';
                }
				$mdb = $this->website->get_db_from_server($server);
                $query = $this->website->db($table_config['cc']['db'], $server)->query('SELECT TOP ' . (int)$this->top . ' ' . $select . ', c.Class FROM ' . $table_config['cc']['table'] . ' AS b INNER JOIN '.$mdb.'.dbo.Character AS c ON (b.' . $table_config['cc']['identifier_column'] . ' Collate Database_Default = c.Name Collate Database_Default) ' . $exclude_list . ' GROUP BY b.' . $table_config['cc']['identifier_column'] . ', c.Class ' . $order);
                if($query){
                    $i = 0;
                    while($row = $query->fetch()){
                        $this->cc[$i] = [
							'name' => $row[$table_config['cc']['identifier_column']], 
							'name_hex' => bin2hex($row[$table_config['cc']['identifier_column']]), 
							'score' => $row[$table_config['cc']['column']],
							'class' => $this->website->get_char_class($row['Class']), 
							'class_small' => $this->website->get_char_class($row['Class'], true),
						];
                        if(isset($table_config['cc']['column2']) && $table_config['cc']['column2'] != ''){
                            $this->cc[$i]['pkillcount'] = $row[$table_config['cc']['column2']];
                        }
                        if(isset($table_config['cc']['column3']) && $table_config['cc']['column3'] != ''){
                            $this->cc[$i]['mkillcount'] = $row[$table_config['cc']['column3']];
                        }
                        $i++;
                    }
                    if($i > 0){
                        $this->website->set_cache($this->cache_name, $this->cc, (int)$config['cc']['cache_time']);
                        return $this->cc;
                    }
                }
                return false;
            }
            return $this->website->cc;
        }
		
		private function load_cs_rankings($server, $config, $table_config, $top){
            if(!isset($config['cs']) || ($table_config == false || $table_config['cs']['table'] == ''))
                return false;
            $this->top = ($top != false) ? $top : $config['cs']['count'];
            if($this->top == 0)
                return false;
            $this->check_cache('cs', 'cs', $server, $config['cs']['cache_time']);
            if(!$this->website->cached){
				$exclude_list = '';
                if(isset($config['cs']['excluded_list'])){
                    $exclude_list = $this->exclude_list($config['cs']['excluded_list'], $table_config['cs']['identifier_column']);
                }
                if($exclude_list != ''){
                    $exclude_list = 'WHERE ' . str_replace(' AND', '', $exclude_list);
                }
                $query = $this->website->db($table_config['cs']['db'], $server)->query('SELECT TOP ' . (int)$this->top . ' ' . $table_config['cs']['identifier_column'] . ', ' . $table_config['cs']['column'] . ', ' . $table_config['cs']['column2'] . ' FROM ' . $table_config['cs']['table'] . ' ' . $exclude_list . ' ORDER BY ' . $table_config['cs']['column'] . ' DESC');
                if($query){
                    $i = 0;
                    while($row = $query->fetch()){
                        $this->cs[] = ['name' => htmlspecialchars($row[$table_config['cs']['identifier_column']]), 'name_hex' => bin2hex($row[$table_config['cs']['identifier_column']]), 'kill_score' => $row[$table_config['cs']['column']], 'death_score' => $row[$table_config['cs']['column2']]];
                        $i++;
                    }
                    if($i > 0){
                        $this->website->set_cache($this->cache_name, $this->cs, (int)$config['cs']['cache_time']);
                        return $this->cs;
                    }
                }
                return false;
            }
            return $this->website->cs;
        }
		
		private function load_duel_rankings($server, $config, $table_config, $top){
            if(!isset($config['duels']) || ($table_config == false || $table_config['duels']['table'] == ''))
                return false;
            $this->top = ($top != false) ? $top : $config['duels']['count'];
            if($this->top == 0)
                return false;
            $this->check_cache('duels', 'duels', $server, $config['duels']['cache_time']);
            if(!$this->website->cached){
				$exclude_list = '';
                if(isset($config['duels']['excluded_list']) && $config['duels']['excluded_list'] != ''){
                    $exclude_list = 'AND'.$this->exclude_list($config['duels']['excluded_list'], 'b.'.$table_config['duels']['identifier_column']);
                }
                if($exclude_list != ''){
                    $exclude_list = ' ' . str_replace(' AND', '', $exclude_list);
                }
                $query = $this->website->db($table_config['duels']['db'], $server)->query('SELECT TOP ' . (int)$this->top . ' b.' . $table_config['duels']['identifier_column'] . ', b.' . $table_config['duels']['column'] . ', b.' . $table_config['duels']['column2'] . ', c.Class, c.cLevel FROM ' . $table_config['duels']['table'] . ' AS b INNER JOIN Character AS c ON (b.' . $table_config['duels']['identifier_column'] . ' Collate Database_Default = c.Name Collate Database_Default) WHERE b.' . $table_config['duels']['column'] . ' > 0 OR b.' . $table_config['duels']['column2'] . ' > 0 ' . $exclude_list . ' ORDER BY b.' . $table_config['duels']['column'] . ' DESC, b.' . $table_config['duels']['column2'] . ' ASC');
                if($query){
                    $i = 0;
                    while($row = $query->fetch()){
						$guild = $this->check_guild($row[$table_config['duels']['identifier_column']], $server);
                        $this->duels[] = [
							'name' => $row[$table_config['duels']['identifier_column']], 
							'name_hex' => bin2hex($row[$table_config['duels']['identifier_column']]), 
							'win' => $row[$table_config['duels']['column']], 
							'lose' => $row[$table_config['duels']['column2']], 
							'ratio' => $this->duel_ratio($row[$table_config['duels']['column']], $row[$table_config['duels']['column2']]),
							'class' => $this->website->get_char_class($row['Class']), 
							'class_small' => $this->website->get_char_class($row['Class'], true),
							'level' => $row['cLevel'],
							//'loc' => $this->website->get_map_name($row['MapNumber']),
							'g_name' => ($guild != false) ? $guild['name'] : '',
							'g_mark' => ($guild != false) ? $guild['logo'] : '',
							
						];
                        $i++;
                    }
                    if($i > 0){
                        $this->website->set_cache($this->cache_name, $this->duels, (int)$config['duels']['cache_time']);
                        return $this->duels;
                    }
                }
                return false;
            }
            return $this->website->duels;
        }
		
		private function duel_ratio($win, $lose){
            for($x = $lose; $x > 1; $x--){
                if(($win % $x) == 0 && ($lose % $x) == 0){
                    $win = $win / $x;
                    $lose = $lose / $x;
                }
            }
            return $win . ':' . $lose;
        }
		
		public function load_online_players($config, $table_config, $server){
            if($table_config != false){
                $accountDb = ($this->website->is_multiple_accounts() == true) ? $this->website->get_db_from_server($server, true) : $this->website->get_default_account_database();
                $res = $this->join_resets($config['display_resets'], $table_config);
                $gres = $this->join_gresets($config['display_gresets'], $table_config);
				if(isset($config['display_master_level'])){
					$mlevel = $this->join_master_level($config['display_master_level'], $table_config, 'a.GameIDC');
				}
				else{
					$mlevel = $this->join_master_level(0, $table_config, 'a.GameIDC');
				}
                $query = $this->website->db('game', $server)->cached_query('online_list_' . $server, 'SELECT m.memb___id , m.ConnectTM, m.ServerName,  m.IP, a.GameIDC, a.Id ' . $mlevel[0] . $res[0] . $gres[0] . ', c.Class, c.cLevel, c.Name FROM [' . $accountDb . '].dbo.[MEMB_STAT] as m INNER JOIN AccountCharacter AS a ON (m.memb___id Collate Database_Default = a.Id Collate Database_Default) INNER JOIN Character AS c ON (a.GameIDC Collate Database_Default = c.Name Collate Database_Default) ' . $mlevel[1] . $res[1] . $gres[1] . ' WHERE ConnectStat = 1 ' . $this->website->server_code($this->website->get_servercode($server)) . $this->include_gms($config['display_gms']) . $this->exclude_list($config['excluded_list']) . '  ORDER by m.ConnectTM DESC', [], $config['cache_time']);
                if($query != false){
                    $i = 0;
                    foreach($query AS $row){
                        $online_time = $this->online_time($row['memb___id'], $server);
                        if(!$online_time){
                            $online_time['OnlineMinutes'] = 0;
                        }
                        $this->online_players[] = [
							'connecttime' => htmlspecialchars($row['ConnectTM']), 
							'h' => floor($online_time['OnlineMinutes'] / 60), 
							'm' => ($online_time['OnlineMinutes'] - (floor($online_time['OnlineMinutes'] / 60) * 60)), 
							'server' => $row['ServerName'], 
							'name' => $row['GameIDC'], 
							'name_hex' => bin2hex($row['GameIDC']), 
							'resets' => ($config['display_resets'] == 1) ? $row[$table_config['resets']['column']] : 0, 
							'gresets' => ($config['display_gresets'] == 1) ? $row[$table_config['grand_resets']['column']] : 0, 
							'class' => $this->website->get_char_class($row['Class'], true), 
							'level' => $row['cLevel'], 
							'mlevel' => (isset($config['display_master_level']) && $config['display_master_level'] == 1) ? $row[$table_config['master_level']['column']] : 0,
							'country' => ($config['display_country'] == 1 && isset($row['IP'])) ? $this->website->get_country_code($row['IP']) : 'us'
						];
                        $i++;
                    }
                    if($i > 0){
                        return $this->online_players;
                    }
                }
            }
            return false;
        }
		
		private function online_time($memb___id, $server){
            return $this->website->db('web')->query('SELECT SUM(TotalTime) AS OnlineMinutes FROM DmN_OnlineCheck WHERE memb___id = '.$this->website->db('web')->escape($memb___id).' ' . $this->website->server_code($this->website->get_servercode($server)) . '')->fetch();
        }
		
		private function join_resets($status, $table_config, $bound = 'a.GameIDC'){
            $sql = ['', ''];
            if($status == 1){
                if($table_config && isset($table_config['resets']) && (isset($table_config['resets']['column']) && $table_config['resets']['column'] != '')){
                    $sql = [', r.' . $table_config['resets']['column'] . '', ' INNER JOIN ' . $table_config['resets']['table'] . ' AS r ON (' . $bound . ' Collate Database_Default = r.' . $table_config['resets']['identifier_column'] . ' Collate Database_Default)'];
                }
            }
            return $sql;
        }

		private function join_gresets($status, $table_config, $bound = 'a.GameIDC'){
            $sql = ['', ''];
            if($status == 1){
                if($table_config && isset($table_config['grand_resets']) && (isset($table_config['grand_resets']['column']) && $table_config['grand_resets']['column'] != '')){
                    $sql = [', g.' . $table_config['grand_resets']['column'] . '', ' INNER JOIN ' . $table_config['grand_resets']['table'] . ' AS g ON (' . $bound . ' Collate Database_Default = g.' . $table_config['grand_resets']['identifier_column'] . ' Collate Database_Default)'];
                }
            }
            return $sql;
        }
		
		private function join_account_character($bound = 'a.GameIDC'){
            return [', a.GameIDC', ' FULL JOIN AccountCharacter AS a ON (' . $bound . ' Collate Database_Default = a.GameIDC Collate Database_Default)'];
        }
		
		private function join_master_level($status, $table_config, $bound = 'a.GameIDC'){
            $sql = ['', ''];
            if($status == 1){
                if($table_config && isset($table_config['master_level']) && (isset($table_config['master_level']['column']) && $table_config['master_level']['column'] != '')){
                    $sql = [', ml.' . $table_config['master_level']['column'] . '', ' INNER JOIN ' . $table_config['master_level']['table'] . ' AS ml ON (' . $bound . ' Collate Database_Default = ml.' . $table_config['master_level']['identifier_column'] . ' Collate Database_Default)'];
                }
            }
            return $sql;
        }
		
		private function join_memb_stat($status, $bound = 'c.AccountId', $columns = ', m.IP,  m.ConnectStat', $server = '', $joinType = 'FULL JOIN'){
            $sql = ['', ''];
            if($status == 1){
                $accountDb = ($this->website->is_multiple_accounts() == true) ? $this->website->get_db_from_server($server, true) : $this->website->get_default_account_database();
                $sql = [$columns, ' ' . $joinType . ' [' . $accountDb . '].dbo.[MEMB_STAT] AS m ON(' . $bound . ' Collate Database_Default = m.memb___id Collate Database_Default)'];
            }
            return $sql;
        }
		
		private function create_order($data = []){
            $this->order = 'ORDER BY ';
            if(!empty($data)){
                foreach($data AS $order){
                    if($order != ''){
                        $this->order .= str_replace(',', '', $order) . ' DESC,';
                    }
                }
            }
            $this->order = substr_replace($this->order, '', -1);
        }
		
		private function include_gms($status, $bound = 'c.Ctlcode'){
            return ($status == 0) ? $this->exclude_list('8,16,32', $bound, false) : '';
        }
		
		private function exclude_list($list, $bound = 'c.Name', $quote = true, $stmt = 'NOT IN'){
            $data = implode(',', array_map(function($value) use ($quote){
                return ($quote) ? "'" . $this->website->db('web')->escape($value) . "'" : $this->website->db('web')->escape($value);
            }, explode(',', $list)));
            return ($list != '') ? ' AND ' . $bound . ' ' . $stmt . ' (' . $data . ')' : '';
        }

        public function load_gm_list($server){
            $query = $this->website->db('web')->query('SELECT character, contact FROM DmN_Gm_List WHERE server = '.$this->website->db('web')->escape($server).'');
            if($query){
                while($row = $query->fetch()){
                    $this->gm_list[] = ['name' => htmlspecialchars($row['character']), 'contact' => htmlspecialchars($row['contact'])];
                }
                return $this->gm_list;
            }
            return false;
        }

        public function load_ban_list($type, $server){
            $query = $this->website->db('web')->query('SELECT name, time, is_permanent, reason FROM DmN_Ban_List WHERE type = ' . $this->get_ban_type($type) . ' AND server = '.$this->website->db('web')->escape($server).' ORDER BY time ASC, is_permanent ASC');
            if($query){
                while($row = $query->fetch()){
                    $this->ban_list[] = ['name' => htmlspecialchars($row['name']), 'time' => ($row['is_permanent'] == 0) ? (($row['time'] < time()) ? 'Ban Expired' : date(DATETIME_FORMAT, $row['time'])) : 'Permanent Ban', 'reason' => htmlspecialchars($row['reason'])];
                }
                return $this->ban_list;
            }
            return false;
        }
		
		private function get_ban_type($type){
            switch($type){
                default:
                case 'chars':
                    return 2;
                    break;
                case 'accounts':
                    return 1;
                    break;
            }
        }

        public function class_filter($class){
            $this->class_filter = true;
            $this->c_class = $class;
        }

        private function gen_rank_by_class($class, $bound = 'c.Class'){
            if($class != ''){
                switch($class){
                    case 'dk':
                        return ' ' . $bound . ' IN(16, 17, 18, 19, 23, 31)';
                        break;
                    case 'dw':
                        return ' ' . $bound . ' IN(0, 1, 2, 3, 7, 15)';
                        break;
                    case 'fe':
                        return ' ' . $bound . ' IN(32, 33, 34, 35, 39, 47)';
                        break;
                    case 'mg':
                        return ' ' . $bound . ' IN(48, 49, 50, 51, 54, 62)';
                        break;
                    case 'dl':
                        return ' ' . $bound . ' IN(64, 65, 66, 67, 70, 78)';
                        break;
                    case 'su':
                        return ' ' . $bound . ' IN(80, 81, 82, 83, 84, 87, 95)';
                        break;
                    case 'rf':
                        return ' ' . $bound . ' IN(96, 97, 98, 99, 102, 110)';
                        break;
                    case 'gl':
                        return ' ' . $bound . ' IN(112, 114, 115, 118, 126)';
                        break;
                    case 'rw':
                        return ' ' . $bound . ' IN(128, 129, 130, 131, 135, 143)';
                        break;
					case 'sl':
                        return ' ' . $bound . ' IN(144, 145, 146, 147, 151, 159)';
                        break;		
					case 'gc':
                        return ' ' . $bound . ' IN(160, 161, 163, 167, 175)';
                        break;	
					case 'wm':
                        return ' ' . $bound . ' IN(176, 177, 178, 179, 183, 191)';
                        break;	
					case 'lr':
                        return ' ' . $bound . ' IN(192, 193, 194, 195, 199, 207)';
                        break;
					case 'ik':
						if(MU_VERSION >= 13){
							return ' ' . $bound . ' IN(208, 209, 210, 211, 215, 223)';
						}
						else{
							return ' ' . $bound . ' IN(16, 17, 18, 19, 23)';
						}
						break;
                    default:
                        $this->class_filter = false;
                        $this->c_class = '';
                        return '';
                        break;
                }
            }
            return false;
        }
		
		public function load_mark($hex, $size = 16){
			$pixelSize = abs((int)$size) / 8;
			$img = imagecreatetruecolor(8 * $pixelSize, 8 * $pixelSize);
			$colors = ['ffffff','000000','8c8a8d','ffffff','fe0000','ff8a00','ffff00','8cff01','00ff00','01ff8d','00ffff','008aff','0000fe','8c00ff','ff00fe','ff008c'];
			$transparent = imagecolorallocate($img, 204, 204, 204);
			
			imagecolortransparent($img, $transparent);
			
			for($y = 0; $y < 8; $y++){
				for($x = 0; $x < 8; $x++){
					$color_id = hexdec(substr($hex, ($y * 8) + $x, 1));
					$r = '0x' . substr($colors[$color_id], 0, 2);
					$g = '0x' . substr($colors[$color_id], 2, 2);
					$b = '0x' . substr($colors[$color_id], 4, 2);
					$row[$x] = $x * $pixelSize;
					$row[$y] = $y * $pixelSize;
					$row2[$x] = $row[$x] + $pixelSize;
					$row2[$y] = $row[$y] + $pixelSize;
					$color[$y][$x] = imagecolorallocate($img, hexdec($r), hexdec($g), hexdec($b));
					imagefilledrectangle($img, (int)$row[$x], (int)$row[$y], (int)$row2[$x], (int)$row2[$y], $color[$y][$x]);
				}
			}

			header('Content-type: image/webp');
			imagewebp($img);
			imagedestroy($img);
		}
    }