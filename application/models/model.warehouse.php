<?php
    in_file();

    class Mwarehouse extends model
    {
        public $vault_items, $vault_money, $item = '', $items = [], $total_items, $exe_opt_count = 0;
        private $new_hex;

        public function __contruct(){
            parent::__construct();
        }

		public function get_vault_content($user, $server){
			$stmt = $this->website->db('game', $server)->prepare('SELECT CONVERT(IMAGE, Items) AS Items, Money FROM Warehouse WHERE AccountId = :user');
			$stmt->execute([':user' => $user]);
			if($this->vault_items = $stmt->fetch()){ 
				$unpack = unpack('H*', $this->vault_items['Items']);
				$this->vault_items['Items'] = $this->website->clean_hex($unpack[1]);
				$this->vault_money = $this->vault_items['Money'];
				return true;
			} else{
				return false;
			}  
        }

		public function load_items($server){
            $hex = str_split($this->vault_items['Items'], $this->website->get_value_from_server($server, 'item_size'));
            $items = [];
            $i = 0;
            $x = 0;
            $y = 0;
             foreach($hex as $item){
                $i++;
                if($item != str_pad("", $this->website->get_value_from_server($server, 'item_size'), "F")){
                    $this->iteminfo->itemData($item);
												  
                    $items[$i]['item_id'] = $this->iteminfo->id;
                    $items[$i]['item_cat'] = $this->iteminfo->type;
                    $items[$i]['level'] = (int)substr($this->iteminfo->getLevel(), 1);
                    $items[$i]['name'] = $this->iteminfo->realName();
                    $items[$i]['x'] = $this->iteminfo->getX();
                    $items[$i]['y'] = $this->iteminfo->getY();
                    $items[$i]['xx'] = $x;
                    $items[$i]['yy'] = $y;
                    $items[$i]['hex'] = $this->iteminfo->hex;
					$items[$i]['item_info'] = $this->iteminfo->allInfo();
                }
                $x++;
                if($x >= 8){
                    $x = 0;
                    $y++;
                    if($y >= 15){
                        $y = 0;
                    }
                }
            }
            $this->set_total_items(count($hex));
            return $items;
        }

        private function set_total_items($count = 120){
            $this->total_items = $count;
        }

		public function find_item_by_slot($slot, $server){
            $hex = str_split($this->vault_items['Items'], $this->website->get_value_from_server($server, 'item_size'));
            $found = false;
            if(isset($hex[$slot - 1]) && $hex[$slot - 1] != str_pad("", $this->website->get_value_from_server($server, 'item_size'), "F")){
                $found = true;
                $this->item = $hex[$slot - 1];
            }
            return $found;
        }

		public function generate_new_item_by_slot($slot, $server){
            $hex = str_split($this->vault_items['Items'], $this->website->get_value_from_server($server, 'item_size'));
            if(isset($hex[$slot - 1])){
                $hex[$slot - 1] = str_pad("", $this->website->get_value_from_server($server, 'item_size'), "F");
            }
            $this->new_hex = implode('', $hex);
        }

        public function insert_web_item($user, $server, $item = null){
			$item = ($item != null) ? $item : $this->item;
            $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Web_Storage (item, account, server, expires_on) VALUES (:item, :account, :server, :expires_on)');
            return $stmt->execute([':item' => $item, ':account' => $user, ':server' => $server, ':expires_on' => strtotime('+' . $this->config->config_entry('warehouse|web_wh_item_expires_after'))]);
        }

        public function check_web_wh_item($id, $user, $server){
            return $this->website->db('web')->query('SELECT item FROM DmN_Web_Storage WHERE account = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).' AND is_removed = 0 AND expires_on > ' . time() . ' AND id = ' . $this->website->db('web')->escape($id) . '')->fetch();
        }

        public function set_removed_web_item($id){
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_Web_Storage SET is_removed = 1 WHERE id = :id');
            return $stmt->execute([':id' => $id]);
        }
		
		public function remove_web_item($id){
            $stmt = $this->website->db('web')->prepare('DELETE FROM DmN_Web_Storage WHERE id = :id');
            return $stmt->execute([':id' => $id]);
        }

		public function load_web_items($user, $server, $page = 1, $item = ''){
            $per_page = ($page <= 1) ? 0 : (int)$this->config->config_entry('warehouse|web_items_per_page') * ((int)$page - 1);
			$pp = ($item != '') ? 500 : $this->website->db('web')->escape((int)$this->config->config_entry('warehouse|web_items_per_page'));
            $items = $this->website->db('web')->query('SELECT TOP ' . $pp . ' id, item, expires_on FROM DmN_Web_Storage WHERE account = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).' AND is_removed = 0 AND expires_on > ' . time() . ' AND id Not IN (SELECT Top ' . $this->website->db('web')->escape($per_page) . ' id FROM DmN_Web_Storage WHERE account = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).' AND is_removed = 0 AND expires_on > ' . time() . ' ORDER BY id DESC) ORDER BY id DESC');
            $pos = ($page == 1) ? 1 : (int)(($page - 1) * $this->config->config_entry('warehouse|web_items_per_page')) + 1;
            foreach($items->fetch_all() as $value){
                $this->iteminfo->itemData($value['item']);
				if($item != ''){
					if (stripos($this->iteminfo->realName(), $item) !== false) {
						$this->items[] = [
							'item' => $value['item'],
							'name' => $this->iteminfo->getNameStyle(true), 
							'namenostyle' => $this->iteminfo->realName(), 
							'id' => $value['id'], 
							'expires_on' => $value['expires_on'], 
							'pos' => $pos,
							'item_info' => $this->itemimage->load($this->iteminfo->id, $this->iteminfo->type, (int)substr($this->iteminfo->getLevel(), 1)) . '<br />' . $this->iteminfo->allInfo()
						];
						$pos++;
					}
				}
				else{
					$this->items[] = [
						'item' => $value['item'],
						'name' => $this->iteminfo->getNameStyle(true), 
						'namenostyle' => $this->iteminfo->realName(), 
						'id' => $value['id'], 
						'expires_on' => $value['expires_on'], 
						'pos' => $pos,
						'item_info' => $this->itemimage->load($this->iteminfo->id, $this->iteminfo->type, (int)substr($this->iteminfo->getLevel(), 1)) . '<br />' . $this->iteminfo->allInfo()
					];
					$pos++;
				}
            }
            return $this->items;
        }
		
		public function list_web_items($user, $server){
			return $this->website->db('web')->query('SELECT id, item FROM DmN_Web_Storage WHERE account = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).' AND is_removed = 0 AND expires_on > ' . time() . '')->fetch_all();
		}

        public function count_total_web_items($user, $server){
            $this->total_items = $this->website->db('web')->snumrows('SELECT COUNT(item) AS count FROM DmN_Web_Storage WHERE account = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).' AND is_removed = 0 AND expires_on > ' . time() . '');
        }

        public function update_warehouse($user, $server){
            $stmt = $this->website->db('game', $server)->prepare('UPDATE Warehouse SET Items = 0x' . $this->new_hex . ' WHERE AccountId = :user');
            return $stmt->execute([':user' => $user]);
        }

		public function load_item_info($item = ''){
			if($item != ''){
				$this->iteminfo->itemData($item);
			}
			else{
				$this->iteminfo->itemData($this->item);
			}
            $option = $this->iteminfo->option;
            $info = [
				'id' => $this->iteminfo->id, 
				'name' => $this->iteminfo->realName(), 
				'cat' => $this->iteminfo->type, 
				'serial' => $this->iteminfo->serial, 
				'serial2' => ($this->iteminfo->serial2 != null) ? $this->iteminfo->serial2 : 'FFFFFFFF', 
				'skill' => ($option >= 128) ? 1 : 0, 
				'luck' => $this->iteminfo->getLuck(),
				'lvl' => ($option >= 128) ? floor(($option - 128) / 8) : floor($option / 8), 
				'opt' => $this->iteminfo->getOption()*4,
				'exe' => $this->iteminfo->exe, 
				'socket' => $this->iteminfo->socket, 
				'class' => $this->iteminfo->getClass(), 
				'anc' => ($this->iteminfo->ancient > 0) ? 1 : 0,
				'ancData' => $this->iteminfo->ancient,
				'dur' => $this->iteminfo->dur
			];
            $luck = (($this->iteminfo->option - ($info['lvl'] * 8)) >= 4) ? 1 : 0;
            $exe_opts = [0, 0, 0, 0, 0, 0, 0, 0, 0];
            if($info['exe'] >= 64){
                $info['exe'] -= 64;
            }
            if($info['exe'] >= 32){
                $info['exe'] -= 32;
                $exe_opts[5] = 1;
                $this->exe_opt_count += 1;
            }
            if($info['exe'] >= 16){
                $info['exe'] -= 16;
                $exe_opts[4] = 1;
                $this->exe_opt_count += 1;
            }
            if($info['exe'] >= 8){
                $info['exe'] -= 8;
                $exe_opts[3] = 1;
                $this->exe_opt_count += 1;
            }
            if($info['exe'] >= 4){
                $info['exe'] -= 4;
                $exe_opts[2] = 1;
                $this->exe_opt_count += 1;
            }
            if($info['exe'] >= 2){
                $info['exe'] -= 2;
                $exe_opts[1] = 1;
                $this->exe_opt_count += 1;
            }
            if($info['exe'] >= 1){
                $info['exe'] -= 1;
                $exe_opts[0] = 1;
                $this->exe_opt_count += 1;
            }
            if(defined('MU_VERSION') && MU_VERSION >= 5){
                if(in_array($info['socket'][1], [6, 7, 8])){
                    $this->exe_opt_count += 1;
                    $exe_opts[6] = 1;
                }
                if(in_array($info['socket'][2], [6, 7, 8])){
                    $this->exe_opt_count += 1;
                    $exe_opts[7] = 1;
                }
                if(in_array($info['socket'][3], [6, 7, 8])){
                    $this->exe_opt_count += 1;
                    $exe_opts[8] = 1;
                }
            }
            return ['info' => $info, 'luck' => $luck, 'exe_opts' => $exe_opts];
        }

        public function check_shop_item($item = null){
			$check = ($item != null) ? $item : $this->item;
            return $this->website->db('web')->query('SELECT id FROM DmN_Shop_Logs WHERE SUBSTRING(item_hex ,7 ,8) = '.$this->website->db('web')->escape(substr($check, 6, 8)).'')->fetch();
        }

		public function add_market_item($user, $server, $info, $price, $ptype, $time, $char, $highlight, $password = '', $item = null){
			$item = ($item != null) ? $item : $this->item;
			
            if(in_array($ptype, [4, 5, 6, 7, 8, 9])){
                $price_jewels = $price;
                $price_type = $ptype;
            } else{
                $price_jewels = 0;
                $price_type = 0;
            }
            $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Market (cat, item, price_type, price, seller, add_date, active_till, serial, serial2, has_luck, has_skill, lvl, highlighted, char, server, has_ancient, has_exe_1, has_exe_2, has_exe_3, has_exe_4, has_exe_5, has_exe_6, has_exe_7, has_exe_8, has_exe_9, is_sm, is_bk, is_me, is_mg, is_dl, is_sum, is_rf, is_gl, is_rw, is_sl, is_gc, is_km, is_lm, is_ik, price_jewel, jewel_type, item_name, item_id, item_password)
										VALUES 
										(:cat, :item, :ptype, :price, :user, GETDATE(), '.$this->website->db('web')->escape(date('Ymd H:i:s', strtotime('+' . $time . ' days', time()))).', :serial, :seriall, :luck, :skill, :lvl, :higlight, :char, :server, :has_ancient, :has_exe_a, :has_exe_b, :has_exe_c, :has_exe_d, :has_exe_e, :has_exe_f, :has_exe_h, :has_exe_i, :has_exe_j, :is_sm, :is_bk, :is_me, :is_mg, :is_dl, :is_sum, :is_rf, :is_gl, :is_rw, :is_sl, :is_gc, :is_km, :is_lm, :is_ik, :price_jewel, :jewel_type, :item_name, :item_id, :password)');
            $stmt->execute([
				':cat' => $info['info']['cat'], 
				':item' => $item, 
				':ptype' => $ptype, 
				':price' => $price, 
				':user' => $user, 
				':serial' => $info['info']['serial'], 
				':seriall' => $info['info']['serial2'], 
				':luck' => $info['luck'], 
				':skill' => $info['info']['skill'], 
				':lvl' => $info['info']['lvl'], 
				':higlight' => $highlight, 
				':char' => $char,
				':server' => $server, 
				':has_ancient' => $info['info']['anc'], 
				':has_exe_a' => $info['exe_opts'][0], 
				':has_exe_b' => $info['exe_opts'][1], 
				':has_exe_c' => $info['exe_opts'][2], 
				':has_exe_d' => $info['exe_opts'][3], 
				':has_exe_e' => $info['exe_opts'][4], 
				':has_exe_f' => $info['exe_opts'][5],
				':has_exe_h' => $info['exe_opts'][6], 
				':has_exe_i' => $info['exe_opts'][7], 
				':has_exe_j' => $info['exe_opts'][8], 
				':is_sm' => $info['info']['class']['sm'], 
				':is_bk' => $info['info']['class']['bk'], 
				':is_me' => $info['info']['class']['me'], 
				':is_mg' => $info['info']['class']['mg'], 
				':is_dl' => $info['info']['class']['dl'], 
				':is_sum' => $info['info']['class']['bs'], 
				':is_rf' => $info['info']['class']['rf'], 
				':is_gl' => $info['info']['class']['gl'], 
				':is_rw' => $info['info']['class']['rw'], 
				':is_sl' => $info['info']['class']['sl'], 
				':is_gc' => $info['info']['class']['gc'], 
				':is_km' => $info['info']['class']['km'], 
				':is_lm' => $info['info']['class']['lm'], 
				':is_ik' => $info['info']['class']['ik'], 
				':price_jewel' => $price_jewels, 
				':jewel_type' => $price_type, 
				':item_name' => $info['info']['name'],
				':item_id' => $info['info']['id'],
				':password' => $password
			]);
        }

        public function check_existing_item(){
            $stmt = $this->website->db('web')->prepare('SELECT id FROM DmN_Market WHERE item = :item AND active = 1 AND sold != 1 AND removed != 1');
            $stmt->execute([':item' => $this->item]);
            return $stmt->fetch();
        }

        public function log_deleted_item($user, $server, $by_admin = 0){
            $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Warehouse_Delete_Log (account, server, item, date, deleted_by_admin) VALUES (:account, :server, :item, GETDATE(), :by_admin)');
            $stmt->execute([':account' => $user, ':server' => $server, ':item' => $this->item, ':by_admin' => $by_admin]);
        }

        public function get_market_item_count($user, $server){
            return $this->website->db('web')->query('SELECT COUNT(*) AS count FROM DmN_Market WHERE seller = '.$this->website->db('web')->escape($user).' AND DATEDIFF(day, add_date, GETDATE()) = 0')->fetch();
        }

        public function decrease_zen($account, $server, $money){
            $stmt = $this->website->db('game', $server)->prepare('UPDATE Warehouse SET Money = Money - :money WHERE AccountId = :account');
            return $stmt->execute([':money' => $money, ':account' => $account]);
        }

        public function add_zen($account, $server, $money){
            $stmt = $this->website->db('game', $server)->prepare('UPDATE Warehouse SET Money = Money + :money WHERE AccountId = :account');
            return $stmt->execute([':money' => $money, ':account' => $account]);
        }

        public function create_vault($user, $server){
            $stmt = $this->website->db('game', $server)->prepare('INSERT INTO warehouse (AccountID, Items, Money, EndUseDate) VALUES (:user, cast(REPLICATE(char(0xff),' . $this->website->get_value_from_server($server, 'wh_size') . ') as varbinary(' . $this->website->get_value_from_server($server, 'wh_size') . ')), 0, getdate())');
            $stmt->execute([':user' => $user]);
        }
		
		public function checkAdditionalSlots($user, $server){
			$check = $this->website->db('web')->query('SELECT slots FROM DmN_Market_Slots WHERE memb___id = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).'')->fetch();
			if($check == false){
				return 0;
			}
			return $check['slots'];
		}
    }