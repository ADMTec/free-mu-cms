<?php
    in_file();

    class Mshop extends model
    {
        public $items = [], $items_sql = [], $count_items = 0;
        public $pos = 1;
        public $errors = [];
        public $new_vault;
        private $vault_items, $category = '';
        private $exe_opts = [0 => 0, 1 => 1, 2 => 2, 3 => 4, 4 => 8, 5 => 16, 6 => 32];
        private $ancient_opts = [0 => 0, 1 => 9, 2 => 10];
        private $fenrir_opts = [0 => 0, 1 => 1, 2 => 2, 4 => 4, 5 => 5, 6 => 6];

        public function __contruct(){
            parent::__construct();
        }

		public function load_items($server, $page = 1, $per_page = 20, $columns = 4, $category = ''){
            if($category != '')
                $this->category = 'AND item_cat = ' . $this->website->db('web')->escape($category) . ''; 
            else{
                $load_cat_list = file(APP_PATH . DS . 'data' . DS . 'shop' . DS . 'shop_cat_list.dmn');
                $cat_list = '';
                foreach($load_cat_list as $key => $category){
                    $category_data = explode('|', $category);
                    if($category_data[3] != 0){
                        $cat_list .= $category_data[0] . ',';
                    }
                }
                $this->category = 'AND item_cat IN (' . $this->website->db('web')->sanitize_var(substr($cat_list, 0, -1)) . ')';
            }
            $items = $this->website->db('web')->query('SELECT id, item_id, item_cat, name, original_item_cat, stick_level, price FROM DmN_Shopp WHERE price >= 1 ' . $this->category . ' ORDER BY  item_cat ASC, item_id ASC')->fetch_all();
            $this->count_items = count($items);
            $this->items_sql = array_slice($items, (int)(($page - 1) * $per_page), $per_page);
            foreach($this->items_sql as $value){
				if($this->iteminfo->setItemData($value['item_id'], $value['original_item_cat'], $this->website->get_value_from_server($server, 'item_size'))){
					$class = $this->iteminfo->canEquip(true);
				} 
				else{
					$class = __('Undefined');
				}
                
                $this->items[] = [
					'id' => $value['id'], 
					'item_id' => $value['item_id'], 
					'name' => htmlspecialchars($value['name']), 
					'class' => preg_replace('~(.*)' . preg_quote(',', '~') . '~', '$1' . '', $class, 1), 
					'image' => ($value['stick_level'] > 0) ? $this->itemimage->load($value['item_id'], $value['original_item_cat'], $value['stick_level']) : $this->itemimage->load($value['item_id'], $value['original_item_cat'], 0), 
					'pos' => $this->pos,
					'price' => $value['price']
				];
                if($this->pos == $columns){
                    $this->pos = 0;
                }
                $this->pos++;
            }
            return $this->items;
        }

		public function get_items($ids)
		{

			$stmt = $this->website->db('web')->prepare('SELECT id, item_id, item_cat, name, stick_level FROM DmN_Shopp WHERE id IN(' . $this->website->db('web')->escape($ids) . ') AND price >= 1');
			$stmt->execute();

			foreach ($stmt->fetch_all() as $key => $value) {
				$this->items[] = array('id' => $value['id'],
					'item_id' => $value['item_id'],
					'name' => htmlspecialchars($value['name']),
					'image' => ($value['stick_level'] > 0) ? $this->itemimage->load($value['item_id'], $value['item_cat'], $value['stick_level'], 0) : $this->itemimage->load($value['item_id'], $value['item_cat'], 0, 0),
					'pos' => $this->pos);
				$this->pos++;
			}

			return $this->items;

		}

		public function get_item_info($server, $id){
            $stmt = $this->website->db('web')->prepare('SELECT TOP 1 id, item_id, item_cat, exetype, name, luck, price, max_item_lvl, max_item_opt, use_sockets, use_harmony, use_refinary, payment_type, original_item_cat, total_bought, stick_level FROM DmN_Shopp WHERE id = :id AND price >= 1');
            $stmt->execute([':id' => (int)$id]);
            if($item = $stmt->fetch()){
				if($this->iteminfo->setItemData($item['item_id'], $item['original_item_cat'], $this->website->get_value_from_server($server, 'item_size'))){
					$item['data'] = $this->iteminfo->item_data;
					$item['image'] = $this->itemimage->load($item['item_id'], $item['original_item_cat'], $item['stick_level']);
					$item['ancient_info'] = $this->webshop->load_ancient_settings();
					$item['socket_info'] = $this->socket_list($item['use_sockets'], $this->config->config_entry('shop_' . $server . '|check_socket_part_type'), $item['exetype'], $item['original_item_cat']);
					$item['price_p'] = $this->discount($item['price'], $server);
					$price_info = $this->load_custom_item_price($item['item_id'], $item['original_item_cat']);
					if($price_info != false){
						if(array_key_exists($server, $price_info)){
							if($price_info[$server] <= 0){
								return 'disabled';
							} else{
								$item['price'] = $price_info[$server];
								$item['price_p'] = $this->discount($price_info[$server], $server);
							}
						}
					}
					return $item;
				}
            }
            return false;
        }

        public function load_custom_item_price($id, $cat){
            $stmt = $this->website->db('web')->prepare('SELECT price FROM DmN_Shop_Custom_Price_List WHERE item_id = :id AND item_cat = :cat');
            $stmt->execute([':id' => $id, ':cat' => $cat]);
            $info = $stmt->fetch();
            if($info){
                if(substr_count($info['price'], "\0")){
                    $info['price'] = strtoupper(str_replace("\0", '', $info['price']));
                }
                return unserialize($info['price']);
            }
            return false;
        }

        public function load_harmony_values($cat = 0, $hopt = 0){
            return $this->website->db('web')->query('SELECT hvalue, hname FROM DmN_Shop_Harmony WHERE itemtype = ' . $this->website->db('web')->escape($this->get_type($cat)) . ' AND hoption = ' . $this->website->db('web')->escape($hopt) . ' AND status = 1')->fetch_all();
        }

		public function check_harmony($use = 0, $harmony = []){
            if($use == 1){
                if(count($harmony) == 2){
                    $check_harmony = $this->website->db('web')->snumrows('SELECT COUNT(id) AS count FROM DmN_Shop_Harmony WHERE hoption = ' . $this->website->db('web')->escape($harmony[0]) . ' AND hvalue = ' . $this->website->db('web')->escape($harmony[1]) . ' AND status = 1');
                    return $check_harmony > 0;
                } else{
                    return false;
                }
            }
            return [];
        }

        public function get_harmony_price($cat, $hopt, $hval, $server){
            $info = $this->website->db('web')->query('SELECT TOP 1 price FROM DmN_Shop_Harmony WHERE itemtype = ' . $this->website->db('web')->escape($this->get_type($cat)) . ' AND hoption = ' . $this->website->db('web')->escape($hopt) . ' AND hvalue = ' . $this->website->db('web')->escape($hval) . ' AND status = 1')->fetch();
            return $this->discount($info['price'], $server);
        }

        public function get_socket_price($socket, $server){
            $info = $this->website->db('web')->query('SELECT TOP 1 socket_price FROM DmN_Shop_Sockets WHERE socket_id = ' . $this->website->db('web')->escape($socket) . ' AND status != 0')->fetch();
            return $this->discount($info['socket_price'], $server);
        }

		public function socket_list($use_sockets, $check_part, $exe_type, $cat){
            $exe_type = ($cat <= 5) ? 1 : 0;
            if($use_sockets == 1){
                if($check_part == 1){
                    $sockets = $this->website->db('web')->query('SELECT seed, socket_id, socket_name FROM DmN_Shop_Sockets WHERE status != 0  AND socket_part_type IN (-1, ' . $exe_type . ') ORDER BY orders ASC')->fetch_all();
                } else{
                    $sockets = $this->website->db('web')->query('SELECT seed, socket_id, socket_name FROM DmN_Shop_Sockets WHERE status != 0 ORDER BY orders ASC')->fetch_all();
                }
                return $sockets;
            }
        }

		public function check_sockets_part_type($exe_type, $socket, $seed, $cat){
            $exe_type = ($cat <= 5) ? 1 : 0;
            return $this->website->db('web')->query('SELECT seed, socket_id, value FROM DmN_Shop_Sockets WHERE socket_id = ' . $this->website->db('web')->escape($socket) . ' AND seed = ' . $this->website->db('web')->escape($seed) . ' AND status != 0 AND socket_part_type IN (-1, ' . $exe_type . ')')->fetch();
        }

        public function check_sockets($socket, $seed){
            return $this->website->db('web')->query('SELECT seed, socket_id, value FROM DmN_Shop_Sockets WHERE socket_id = ' . $this->website->db('web')->escape($socket) . ' AND seed = ' . $this->website->db('web')->escape($seed) . ' AND status != 0')->fetch();
        }

        public function is_socket_item($id, $cat){
            return $this->website->db('web')->snumrows('SELECT COUNT(id) AS count FROM DmN_Shopp WHERE item_id = ' . $this->website->db('web')->escape($id) . ' AND original_item_cat = ' . $this->website->db('web')->escape($cat) . ' AND use_sockets = 1');
        }

		public function generate_serial($server){
			$query = $this->website->db('game', $server)->query('EXEC WZ_GetItemSerial');
            $data = $query->fetch();
            $query->close_cursor();
            return $data;
        }

		public function generate_serial2($count, $server){
			$query = $this->website->db('game', $server)->query('EXEC WZ_GetItemSerial2 ' . $this->website->db('game', $server)->escape($count) . '');
            $data = $query->fetch();
            $query->close_cursor();
            return $data;
        }

        public function discount($price, $server){
            $disc = (strtotime($this->config->config_entry('shop_' . $server . '|discount_time')) >= time()) ? $this->config->config_entry('shop_' . $server . '|discount') : 0;
            return ($disc == 1) ? floor($price - (($price / 100) * $this->config->config_entry('shop_' . $server . '|discount_perc'))) : $price;
        }

		public function get_vault_content($user, $server){
			$stmt = $this->website->db('game', $server)->prepare('SELECT CONVERT(IMAGE, Items) AS Items FROM Warehouse WHERE AccountId = :user');
			$stmt->execute([':user' => $user]);
			if($this->vault_items = $stmt->fetch()){ 
				$unpack = unpack('H*', $this->vault_items['Items']);
				$this->vault_items['Items'] = $this->website->clean_hex($unpack[1]);
				return $this->vault_items;
			} else{
				return false;
			}            
        }
		
		public function check_space($items, $item_x, $item_y, $multiplier = 120, $size = 32, $hor = 8, $ver = 15, $add_to_slot = false){
            $spots = str_repeat('0', $multiplier);
            $items_array = str_split($items, $size);
            for($i = 0; $i < $multiplier; ++$i){
                if($items_array[$i] != str_repeat('F', $size) && !empty($items_array[$i])){
                    $this->iteminfo->itemData($items_array[$i]);
                    if($this->iteminfo->getX() == false || $this->iteminfo->getY() == false){
                        $this->errors[] = sprintf(__('Found unknown item in warehouse please remove it first. Slot: %d') . $i);
                        return null;
                    }
                    $y = 0;
                    while($y < $this->iteminfo->getY()){
                        $y++;
                        $x = 0;
                        while($x < $this->iteminfo->getX()){
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
            $this->errors[] = __('Please free up space in your warehouse.');
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

		public function generate_new_items($new_item, $slot, $multiplier = 120, $size = 32, $items = false, $return = false){
            $items = ($items != false) ? $items : $this->vault_items['Items'];
            for($x = 0; $x < $multiplier; ++$x){
                $ware_array[$x] = substr($items, $x * $size, $size);
            }
            $ware_array[$slot] = $new_item;
            $this->new_vault = implode('', $ware_array);
            if($return)
                return $this->new_vault;
        }

		public function update_warehouse($user, $server){
            $stmt = $this->website->db('game', $server)->prepare('UPDATE Warehouse SET Items = 0x' . $this->new_vault . ' WHERE AccountId = :user');
            $stmt->execute([':user' => $user]);
        }

        public function set_total_bought($id, $cat){
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_Shopp SET total_bought = total_bought + 1 WHERE item_id = :id AND original_item_cat = :cat');
            $stmt->execute([':id' => $id, ':cat' => $cat]);
        }

        public function log_purchase($user, $server, $hex, $price, $method){
            $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Shop_Logs (memb___id, server, item_hex, date, price, price_type, ip) VALUES (:user, :server, :hex, GETDATE(), :price, :price_text, :ip)');
            $stmt->execute([':user' => $user, ':server' => $server, ':hex' => $hex, ':price' => $price, ':price_text' => $method, ':ip' => ip()]);
        }

        private function get_type($cat){
            if($cat < 5)
                return 1; 
			else if($cat == 5)
                return 2;
            else if($cat > 5)
                return 3;
            else
                return 1;
        }

        public function add_item_to_card($user, $server, $hex, $price, $payment_type){
            $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Shop_Card (account, item_hex, price, price_type, server, time_added) VALUES (:account, :item_hex, :price, :price_type, :server, :time_added)');
            $stmt->execute([':account' => $user, ':item_hex' => $hex, ':price' => $price, ':price_type' => $payment_type, ':server' => $server, ':time_added' => time()]);
        }

        public function load_card_items($user, $server, $type = 1){
            $stmt = $this->website->db('web')->prepare('SELECT id, item_hex, price FROM DmN_Shop_Card WHERE account = :account AND price_type = :type AND server = :server AND bought = 0 AND time_added >= :time_until_expires');
            $stmt->execute([':account' => $user, ':type' => $type, ':server' => $server, ':time_until_expires' => time() - $this->config->config_entry('shop_' . $server . '|card_item_expires')]);
            return $stmt->fetch_all();
        }

        public function item_exist_in_cart($user, $server, $id){
            $stmt = $this->website->db('web')->prepare('SELECT TOP 1 id, item_hex, price, price_type FROM DmN_Shop_Card WHERE account = :account AND id = :id AND server = :server AND bought = 0 AND time_added >= :time_until_expires');
            $stmt->execute([':account' => $user, ':id' => $id, ':server' => $server, ':time_until_expires' => time() - $this->config->config_entry('shop_' . $server . '|card_item_expires')]);
            return $stmt->fetch();
        }

        public function remove_item_from_cart($user, $server, $id){
            $stmt = $this->website->db('web')->prepare('DELETE FROM DmN_Shop_Card WHERE account = :account AND id = :id AND server = :server AND bought = 0');
            $stmt->execute([':account' => $user, ':id' => $id, ':server' => $server]);
        }

        public function change_cart_item_status($user, $server, $hex){
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_Shop_Card SET bought = 1 WHERE account = :account AND item_hex = :hex AND server = :server AND bought = 0');
            $stmt->execute([':account' => $user, ':hex' => $hex, ':server' => $server]);
        }

        public function get_not_added_item_price($user, $server, $hex){
            $stmt = $this->website->db('web')->prepare('SELECT TOP 1 price FROM DmN_Shop_Card WHERE account = :account AND item_hex = :hex AND server = :server AND bought = 0');
            $stmt->execute([':account' => $user, ':hex' => $hex, ':server' => $server]);
            return $stmt->fetch();
        }

        public function change_name_history($user, $server){
            $stmt = $this->website->db('web')->prepare('SELECT old_name, new_name, change_date FROM DmN_ChangeName_History WHERE account = :account AND server = :server ORDER BY change_date DESC');
            $stmt->execute([':account' => $user, ':server' => $server]);
            return $stmt->fetch_all();
        }

		public function check_vip($id, $server){
            $stmt = $this->website->db('web')->prepare('SELECT [id]
                                              ,[package_title]
                                              ,[price]
                                              ,[payment_type]
                                              ,[server]
                                              ,[status]
                                              ,[vip_time]
                                              ,[reset_price_decrease]
                                              ,[reset_level_decrease]
											  ,[reset_bonus_points]
                                              ,[grand_reset_bonus_credits]
											  ,[grand_reset_bonus_gcredits]
                                              ,[hide_info_discount]
                                              ,[pk_clear_discount]
                                              ,[clear_skilltree_discount]
                                              ,[online_hour_exchange_bonus]
                                              ,[change_name_discount]
											  ,[change_class_discount]
                                              ,[bonus_credits_for_donate]
                                              ,[shop_discount]
											  ,[wcoins]
                                              ,[connect_member_load]
                                              ,[server_vip_package]
                                              ,[server_bonus_info]
											  ,[allow_extend] FROM DmN_Vip_Packages WHERE id = :id AND server = :server');
            $stmt->execute([':id' => $id, ':server' => $server]);
            return $stmt->fetch();
        }

        public function load_vip_packages($server){
            $stmt = $this->website->db('web')->prepare('SELECT [id]
                                              ,[package_title]
                                              ,[price]
                                              ,[payment_type]
                                              ,[server]
                                              ,[status]
                                              ,[vip_time]
                                              ,[reset_price_decrease]
                                              ,[reset_level_decrease]
											  ,[reset_bonus_points]
                                              ,[grand_reset_bonus_credits]
											  ,[grand_reset_bonus_gcredits]
                                              ,[hide_info_discount]
                                              ,[pk_clear_discount]
                                              ,[clear_skilltree_discount]
                                              ,[online_hour_exchange_bonus]
                                              ,[change_name_discount]
											  ,[change_class_discount]
                                              ,[bonus_credits_for_donate]
                                              ,[shop_discount]
											  ,[wcoins]
                                              ,[connect_member_load]
                                              ,[server_vip_package]
                                              ,[server_bonus_info] FROM DmN_Vip_Packages WHERE server = :server  AND status = 1 AND is_registration_package != 1 ORDER BY id ASC');
            $stmt->execute([':server' => $server]);
            return $stmt->fetch_all();
        }

        public function load_registration_vip_packages($server = ''){
            $srv = ($server != '') ? ' AND server = '.$this->website->db('web')->escape($server).'' : '';
            return $this->website->db('web')->query('SELECT [id]
                                              ,[package_title]
                                              ,[price]
                                              ,[payment_type]
                                              ,[server]
                                              ,[status]
                                              ,[vip_time]
                                              ,[reset_price_decrease]
                                              ,[reset_level_decrease]
                                              ,[grand_reset_bonus_credits]
											  ,[grand_reset_bonus_gcredits]
                                              ,[hide_info_discount]
                                              ,[pk_clear_discount]
                                              ,[clear_skilltree_discount]
                                              ,[online_hour_exchange_bonus]
                                              ,[change_name_discount]
											  ,[change_class_discount]
                                              ,[bonus_credits_for_donate]
                                              ,[shop_discount]
                                              ,[connect_member_load]
                                              ,[server_vip_package]
                                              ,[server_bonus_info] FROM DmN_Vip_Packages WHERE is_registration_package = 1 ' . $srv . '')->fetch_all();
        }

        public function check_existing_vip_package($user, $server){
            $stmt = $this->website->db('web')->prepare('SELECT viptype, viptime FROM DmN_Vip_Users WHERE memb___id = :account AND server = :server');
            $stmt->execute([':account' => $user, ':server' => $server]);
            return $stmt->fetch();
        }

		public function update_vip_package($id, $viptime, $user, $server){
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_Vip_Users SET viptype = :id, viptime = :viptime WHERE memb___id = :account AND server = :server');
            return $stmt->execute([':id' => $id, ':viptime' => $viptime, ':account' => $user, ':server' => $server]);
        }

        public function insert_vip_package($id, $viptime, $user, $server){
            $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Vip_Users (viptype, viptime, memb___id, server) VALUES (:id, :viptime, :account, :server)');
            return $stmt->execute([':id' => $id, ':viptime' => $viptime, ':account' => $user, ':server' => $server]);
        }
		
		public function remove_vip_package($user, $server){
			$stmt = $this->website->db('web')->prepare('DELETE FROM DmN_Vip_Users WHERE memb___id = :account AND server = :server');
            $stmt->execute([':account' => $user, ':server' => $server]);
		}

		public function add_server_vip($viptime, $viptype, $connect_member_load, $query_config, $user, $server){
            if($viptype != null){
                if(str_contains($viptype, '|')){
                    $vip = explode('|', $viptype);
                    $package = $vip[0];
                    $paycode = $query_config['quearies'][$package]['vip_codes'][$vip[1]]['code'];
                } else{
                    $package = $viptype;
                    $paycode = -1;
                }
                if($this->check_server_vip($query_config['quearies'][$package]['check'], $user, $server)){
                    $this->update_server_vip($viptime, $paycode, $query_config['quearies'][$package]['update'], $user, $server);
                } else{
                    if(!empty($query_config['quearies'][$package]['insert'])){
                        $this->insert_server_vip($viptime, $paycode, $query_config['quearies'][$package]['insert'], $user, $server);
                    }
                }
                if($connect_member_load != null){
                    $this->add_to_connect_member($connect_member_load);
                }
            }
        }

        private function check_server_vip($query, $user, $server){
            $stmt = $this->website->db('account', $server)->prepare($query);
            $stmt->execute([':account' => $user]);
            return $stmt->fetch();
        }

		private function update_server_vip($viptime, $paycode, $query, $user, $server){
            $stmt = $this->website->db('account', $server)->prepare($query);
            $data = [':until_date' => ($paycode != -1) ? date('Y-m-d H:i:s', $viptime) : $viptime];
            if(preg_match('/:type/', $query)){
                $data[':type'] = $paycode;
            }
            $data[':account'] = $user;
            return $stmt->execute($data);
        }

		private function insert_server_vip($viptime, $paycode, $query, $user, $server){
            $stmt = $this->website->db('account', $server)->prepare($query);
            $data = [':account' => $user, ':until_date' => ($paycode != -1) ? date('Y-m-d H:i:s', $viptime) : $viptime];
            if(preg_match('/:type/', $query)){
                $data[':type'] = $paycode;
            }
            return $stmt->execute($data);
        }

		private function add_to_connect_member($connect_member_load, $user = false){
            $info = pathinfo($connect_member_load);
            if(isset($info['extension']) && $info['extension'] == 'txt'){
                $this->write_to_txt_member($connect_member_load, $user);
            }
            if(isset($info['extension']) && $info['extension'] == 'xml'){
                $this->write_to_xml_member($connect_member_load, $user);
            }
        }

		private function write_to_txt_member($connect_member_load, $user = false){
            if(is_writable($connect_member_load)){
                $acc_exists = false;
                $file = file($connect_member_load);
                foreach($file AS $line){
                    if((substr($line, 0, 2) !== '//')){
                        if(trim($line) == '"' . $user . '"'){
                            $acc_exists = true;
                            break;
                        }
                    }
                }
                if(!$acc_exists){
                    file_put_contents($connect_member_load, preg_replace('/^\h*\v+/m', '', implode(PHP_EOL, $file)));
                    file_put_contents($connect_member_load, '"' . $user . '"' . PHP_EOL, FILE_APPEND);
                }
            }
        }

		private function write_to_xml_member($connect_member_load, $user = false){
            if(is_writable($connect_member_load)){
                $data = simplexml_load_file($connect_member_load);
                $acc_exists = false;
                foreach($data->Account AS $accounts){
                    if($accounts->attributes()->Name == $user){
                        $acc_exists = true;
                        break;
                    }
                }
                if(!$acc_exists){
                    $doc = new DomDocument();
                    $doc->formatOutput = true;
                    if($xml = file_get_contents($connect_member_load)){
                        $doc->loadXML($xml, LIBXML_NOBLANKS);
                        $ConnectMember = $doc->getElementsByTagName('ConnectMember')->item(0);
                        $Account = $doc->createElement('Account');
                        $NameAttribute = $doc->createAttribute("Name");
                        $NameAttribute->value = $user;
                        $Account->appendChild($NameAttribute);
                        $ConnectMember->appendChild($Account);
                        $doc->save($connect_member_load);
                    }
                }
            }
        }
    }
