<?php
    in_file();

    class Mmarket extends model
    {
        public $error = false, $vars = [];
        public $query = 'WHERE ';
        private $filter = false;
        public $total_items;
        public $items = [];
        public $item_info;

        public function __contruct(){
            parent::__construct();
        }

        public function __set($key, $val){
            $this->vars[$key] = $val;
        }

        public function __isset($name){
            return isset($this->vars[$name]);
        }

		public function count_total_items($server){
            if($this->filter == true){
                $this->query .= 'AND active_till > GETDATE() AND add_date <= dateadd(minute,-1,getdate()) AND active = 1 AND sold != 1 AND removed != 1 AND server = '.$this->website->db('web')->escape($server).'';
            } else{
                $this->query .= 'active_till > GETDATE() AND add_date <= dateadd(minute,-1,getdate()) AND active = 1  AND sold != 1 AND removed != 1 AND server = '.$this->website->db('web')->escape($server).'';
            }
            $this->total_items = $this->website->db('web')->snumrows('SELECT COUNT(item) AS count FROM DmN_Market ' . $this->query);
        }

		public function count_total_history_items($user, $server){
            $this->total_items = $this->website->db('web')->snumrows('SELECT COUNT(item) AS count FROM DmN_Market WHERE seller = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).'');
        }
		
		public function get_item_list_from_cat($cat, $server){
            return $this->website->db('web')->query('SELECT DISTINCT item_name, item_id FROM DmN_Market WHERE cat = ' . $this->website->db('web')->escape($cat) . ' AND active_till > GETDATE() AND active = 1  AND sold != 1 AND removed != 1 AND server = '.$this->website->db('web')->escape($server).' ORDER BY item_id ASC')->fetch_all();
        }
		
		public function count_items_by_id($id, $cat, $server){
			$times = [60, 70, 80, 90, 100];
			$count = $this->website->db('web')->cached_query('market_item_count_' . $id.'_'.$cat.'_'.$server, 'SELECT COUNT(id) AS count FROM DmN_Market WHERE item_id = ' . $this->website->db('web')->escape($id) . ' AND cat = ' . $this->website->db('web')->escape($cat) . ' AND active_till > GETDATE() AND active = 1  AND sold != 1 AND removed != 1 AND server = '.$this->website->db('web')->escape($server).'', $times[array_rand($times)]);
			return $count[0]['count'];
		}
		
		public function count_items_by_cat($cat, $server){
			$times = [60, 70, 80, 90, 100];
			$count = $this->website->db('web')->cached_query('market_item_count_'.$cat.'_'.$server, 'SELECT COUNT(id) AS count FROM DmN_Market WHERE cat = ' . $this->website->db('web')->escape($cat) . ' AND active_till > GETDATE() AND active = 1  AND sold != 1 AND removed != 1 AND server = '.$this->website->db('web')->escape($server).'', $times[array_rand($times)]);
			return $count[0]['count'];
		}

        private function catNameToId($cat = 'swords'){
            switch($cat){
                default:
                case 'swords':
                    return 0;
                    break;
                case 'axes':
                    return 1;
                    break;
                case 'maces':
                    return 2;
                    break;
                case 'spears':
                    return 3;
                    break;
                case 'bows':
                    return 4;
                    break;
                case 'staffs':
                    return 5;
                    break;
                case 'shields':
                    return 6;
                    break;
                case 'helms':
                    return 7;
                    break;
                case 'armors':
                    return 8;
                    break;
                case 'pants':
                    return 9;
                    break;
                case 'gloves':
                    return 10;
                    break;
                case 'boots':
                    return 11;
                    break;
                case 'wings':
                    return 12;
                    break;
                case 'items':
                    return 13;
                    break;
                case 'other':
                    return 14;
                    break;
                case 'scrolls':
                    return 15;
                    break;
            }
        }

		public function generate_query_post(){
            if(isset($this->vars['filter_items'])){
                if(isset($this->vars['lvl'])){
                    $this->vars['lvl'] = array_filter($this->vars['lvl'], 'is_numeric');
                    if(!empty($this->vars['lvl'])){
                        $_SESSION['filter']['lvl'] = $this->vars['lvl'];
                        $this->query .= 'lvl IN (' . $this->website->db('web')->escape(implode(',', $this->vars['lvl'])) . ') ';
                    } else{
                        unset($_SESSION['filter']['lvl']);
                    }
                } else{
                    unset($_SESSION['filter']['lvl']);
                }
                if(isset($this->vars['luck'])){
                    $_SESSION['filter']['luck'] = is_numeric($this->vars['luck']) ? $this->vars['luck'] : 0;
                    $this->query .= isset($this->vars['lvl']) ? 'AND has_luck = ' . $this->website->db('web')->escape($_SESSION['filter']['luck']) . ' ' : 'has_luck = ' . $this->website->db('web')->escape($_SESSION['filter']['luck']) . ' ';
                } else{
                    unset($_SESSION['filter']['luck']);
                }
                if(isset($this->vars['skill'])){
                    $_SESSION['filter']['skill'] = is_numeric($this->vars['skill']) ? $this->vars['skill'] : 0;
                    $this->query .= (isset($this->vars['lvl']) || isset($this->vars['luck'])) ? 'AND has_skill = ' . $this->website->db('web')->escape($_SESSION['filter']['skill']) . ' ' : 'has_skill = ' . $this->website->db('web')->escape($_SESSION['filter']['skill']) . ' ';
                } else{
                    unset($_SESSION['filter']['skill']);
                }
                if(isset($this->vars['ancient'])){
                    $_SESSION['filter']['ancient'] = is_numeric($this->vars['ancient']) ? $this->vars['ancient'] : 0;
                    $this->query .= (isset($this->vars['lvl']) || isset($this->vars['luck']) || isset($this->vars['skill'])) ? 'AND has_ancient = ' . $this->website->db('web')->escape($_SESSION['filter']['ancient']) . ' ' : 'has_ancient = ' . $this->website->db('web')->escape($_SESSION['filter']['ancient']) . ' ';
                } else{
                    unset($_SESSION['filter']['ancient']);
                }
                if(isset($this->vars['excellent'])){
                    $_SESSION['filter']['excellent'] = $this->vars['excellent'];
                    $exe_query = '';
                    $exe_count = count($this->vars['excellent']);
                    foreach($this->vars['excellent'] as $key => $exe){
                        if($exe_count > 1 && ($key != $exe_count - 1)){
                            $exe_query .= 'has_exe_' . $this->website->db('web')->escape((int)$exe) . ' = 1 AND ';
                        } else{
                            $exe_query .= 'has_exe_' . $this->website->db('web')->escape((int)$exe) . ' = 1 ';
                        }
                    }
                    $this->query .= (isset($this->vars['lvl']) || isset($this->vars['luck']) || isset($this->vars['skill']) || isset($this->vars['ancient'])) ? 'AND ' . $exe_query : $exe_query;
                } else{
                    unset($_SESSION['filter']['excellent']);
                }
                if(isset($this->vars['cat'])){
                    $this->vars['cat'] = array_filter($this->vars['cat'], 'is_numeric');
                    if(!empty($this->vars['cat'])){
                        $_SESSION['filter']['cat'] = $this->vars['cat'];
                        $this->query .= ((isset($this->vars['lvl']) && !empty($this->vars['lvl'])) || isset($this->vars['luck']) || isset($this->vars['skill']) || isset($this->vars['ancient']) || isset($this->vars['excellent'])) ? 'AND cat IN (' . $this->website->db('web')->escape(implode(',', $this->vars['cat'])) . ') ' : 'cat IN (' . $this->website->db('web')->escape(implode(',', $this->vars['cat'])) . ') ';
                    } else{
                        unset($_SESSION['filter']['cat']);
                    }
                } else{
                    unset($_SESSION['filter']['cat']);
                }
                if(isset($this->vars['class'])){
                    $_SESSION['filter']['class'] = $this->vars['class'];
                    if($this->vars['class'] == 'sm'){
                        $this->query .= (isset($this->vars['lvl']) || isset($this->vars['luck']) || isset($this->vars['skill']) || isset($this->vars['ancient']) || isset($this->vars['excellent']) || isset($this->vars['cat'])) ? 'AND is_sm = 1 ' : 'is_sm = 1 ';
                    }
                    if($this->vars['class'] == 'bk'){
                        $this->query .= (isset($this->vars['lvl']) || isset($this->vars['luck']) || isset($this->vars['skill']) || isset($this->vars['ancient']) || isset($this->vars['excellent']) || isset($this->vars['cat'])) ? 'AND is_bk = 1 ' : 'is_bk = 1 ';
                    }
                    if($this->vars['class'] == 'me'){
                        $this->query .= (isset($this->vars['lvl']) || isset($this->vars['luck']) || isset($this->vars['skill']) || isset($this->vars['ancient']) || isset($this->vars['excellent']) || isset($this->vars['cat'])) ? 'AND is_me = 1 ' : 'is_me = 1 ';
                    }
                    if($this->vars['class'] == 'mg'){
                        $this->query .= (isset($this->vars['lvl']) || isset($this->vars['luck']) || isset($this->vars['skill']) || isset($this->vars['ancient']) || isset($this->vars['excellent']) || isset($this->vars['cat'])) ? 'AND is_mg = 1 ' : 'is_mg = 1 ';
                    }
                    if($this->vars['class'] == 'dl'){
                        $this->query .= (isset($this->vars['lvl']) || isset($this->vars['luck']) || isset($this->vars['skill']) || isset($this->vars['ancient']) || isset($this->vars['excellent']) || isset($this->vars['cat'])) ? 'AND is_dl = 1 ' : 'is_dl = 1 ';
                    }
                    if($this->vars['class'] == 'bs'){
                        $this->query .= (isset($this->vars['lvl']) || isset($this->vars['luck']) || isset($this->vars['skill']) || isset($this->vars['ancient']) || isset($this->vars['excellent']) || isset($this->vars['cat'])) ? 'AND is_sum = 1 ' : 'is_sum = 1 ';
                    }
                    if($this->vars['class'] == 'rf'){
                        $this->query .= (isset($this->vars['lvl']) || isset($this->vars['luck']) || isset($this->vars['skill']) || isset($this->vars['ancient']) || isset($this->vars['excellent']) || isset($this->vars['cat'])) ? 'AND is_rf = 1 ' : 'is_rf = 1 ';
                    }
                    if($this->vars['class'] == 'gl'){
                        $this->query .= (isset($this->vars['lvl']) || isset($this->vars['luck']) || isset($this->vars['skill']) || isset($this->vars['ancient']) || isset($this->vars['excellent']) || isset($this->vars['cat'])) ? 'AND is_gl = 1 ' : 'is_gl = 1 ';
                    }
					if($this->vars['class'] == 'rw'){
                        $this->query .= (isset($this->vars['lvl']) || isset($this->vars['luck']) || isset($this->vars['skill']) || isset($this->vars['ancient']) || isset($this->vars['excellent']) || isset($this->vars['cat'])) ? 'AND is_rw = 1 ' : 'is_rw = 1 ';
                    }
					if($this->vars['class'] == 'sl'){
                        $this->query .= (isset($this->vars['lvl']) || isset($this->vars['luck']) || isset($this->vars['skill']) || isset($this->vars['ancient']) || isset($this->vars['excellent']) || isset($this->vars['cat'])) ? 'AND is_sl = 1 ' : 'is_sl = 1 ';
                    }
					if($this->vars['class'] == 'gc'){
                        $this->query .= (isset($this->vars['lvl']) || isset($this->vars['luck']) || isset($this->vars['skill']) || isset($this->vars['ancient']) || isset($this->vars['excellent']) || isset($this->vars['cat'])) ? 'AND is_gc = 1 ' : 'is_gc = 1 ';
                    }
					if($this->vars['class'] == 'km'){
                        $this->query .= (isset($this->vars['lvl']) || isset($this->vars['luck']) || isset($this->vars['skill']) || isset($this->vars['ancient']) || isset($this->vars['excellent']) || isset($this->vars['cat'])) ? 'AND is_km = 1 ' : 'is_km = 1 ';
                    }
					if($this->vars['class'] == 'lm'){
                        $this->query .= (isset($this->vars['lvl']) || isset($this->vars['luck']) || isset($this->vars['skill']) || isset($this->vars['ancient']) || isset($this->vars['excellent']) || isset($this->vars['cat'])) ? 'AND is_lm = 1 ' : 'is_lm = 1 ';
                    }
                    if($this->vars['class'] == 'ik'){
                        $this->query .= (isset($this->vars['lvl']) || isset($this->vars['luck']) || isset($this->vars['skill']) || isset($this->vars['ancient']) || isset($this->vars['excellent']) || isset($this->vars['cat'])) ? 'AND is_ik = 1 ' : 'is_ik = 1 ';
                    }
                } else{
                    unset($_SESSION['filter']['class']);
                }
                $_SESSION['filter']['query'] = $this->query;
            }
            if(isset($this->vars['reset_filter'])){
                unset($_SESSION['filter']);
            }
            if(isset($_SESSION['filter']['query']) && $_SESSION['filter']['query'] != 'WHERE '){
                $this->query = $_SESSION['filter']['query'];
                $this->filter = true;
            }
        }

		public function load_items($page, $server){
            if(!isset($_SESSION['filter']['query'])){
                $this->query = 'WHERE active_till > GETDATE() AND add_date <= dateadd(minute,-1,getdate()) AND active = 1 AND sold != 1 AND removed != 1 AND server = '.$this->website->db('web')->escape($server).'';
            } else{
                $this->query .= ' AND active_till > GETDATE() AND add_date <= dateadd(minute,-1,getdate()) AND active = 1 AND sold != 1 AND removed != 1 AND server = '.$this->website->db('web')->escape($server).'';
            }
            $per_page = ($page <= 1) ? 0 : (int)$this->config->config_entry('market|items_per_page') * ((int)$page - 1);
            $items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape((int)$this->config->config_entry('market|items_per_page')) . ' id, cat, item, price_type, price, seller, add_date, active_till, highlighted, char, price_jewel, jewel_type FROM DmN_Market ' . $this->query . ' AND id Not IN (SELECT Top ' . $this->website->db('web')->escape($per_page) . ' id FROM DmN_Market ' . $this->query . ' ORDER BY id DESC) ORDER BY id DESC');
            $pos = ($page == 1) ? 1 : (int)(($page - 1) * $this->config->config_entry('market|items_per_page')) + 1;
            foreach($items->fetch_all() as $value){
                $this->iteminfo->itemData($value['item']);
                if($value['price_jewel'] != 0 && $value['jewel_type'] != 0){
                    $price = $this->get_jewel_image($value['jewel_type']) . 'x ' . $value['price_jewel'];
                } else{
                    switch($value['price_type']){
                        case 1:
                            $price = round(($value['price'] / 100) * $this->config->config_entry('market|sell_tax') + $value['price']) . ' ' . $this->config->config_entry('credits_' . $server . '|title_1');
                            break;
                        case 2:
                            $price = round(($value['price'] / 100) * $this->config->config_entry('market|sell_tax') + $value['price']) . ' ' . $this->config->config_entry('credits_' . $server . '|title_2');
                            break;
                        case 3:
                            $price = $this->website->zen_format(round(($value['price'] / 100) * $this->config->config_entry('market|sell_tax') + $value['price'])) . ' ' . $this->config->config_entry('credits_' . $server . '|title_3');
                            break;
                    }
                }
                $this->items[] = [
					'icon' => (date("F j, Y", strtotime($value['add_date'])) == date("F j, Y", time())) ? '<img src="' . $this->config->base_url . 'assets/' . $this->config->config_entry('main|template') . '/images/new.png" />' : $pos, 
					'highlighted' => $value['highlighted'], 
					'price' => $price, 
					'item' => $value['item'], 
					'name' => $this->iteminfo->getNameStyle(true), 
					'namenostyle' => $this->iteminfo->realName(), 
					'id' => $value['id'], 
					'pos' => $pos, 
					'seller' => $value['char'], 
					'date' => $value['add_date'],
					'item_info' => $this->itemimage->load($this->iteminfo->id, $this->iteminfo->type, (int)substr($this->iteminfo->getLevel(), 1)) . '<br />' . $this->iteminfo->allInfo()
				];
                $pos++;
            }
            return $this->items;
        }

        public function load_all_items_names($server){
            return $this->website->db('web')->query('SELECT DISTINCT(item_name) FROM DmN_Market WHERE item_name != \'NULL\' AND active_till > GETDATE() AND add_date <= dateadd(minute,-1,getdate()) AND active = 1 AND sold != 1 AND removed != 1 AND server = '.$this->website->db('web')->escape($server).'')->fetch_all();
        }

		public function load_search_items($name, $server){
            $items = $this->website->db('web')->query('SELECT id, cat, item, price_type, price, seller, add_date, active_till, highlighted, char, price_jewel, jewel_type FROM DmN_Market WHERE item_name LIKE \'%' . $this->website->db('web')->sanitize_var($name) . '%\' AND active_till > GETDATE() AND add_date <= dateadd(minute,-1,getdate()) AND active = 1 AND sold != 1 AND removed != 1 AND server = '.$this->website->db('web')->escape($server).' ORDER BY id DESC');
            $pos = 1;
            foreach($items->fetch_all() as $value){
                $this->iteminfo->itemData($value['item']);
                if($value['price_jewel'] != 0 && $value['jewel_type'] != 0){
                    $price = $this->get_jewel_image($value['jewel_type']) . 'x ' . $value['price_jewel'];
                } else{
                    switch($value['price_type']){
                        case 1:
                            $price = round(($value['price'] / 100) * $this->config->config_entry('market|sell_tax') + $value['price']) . ' ' . $this->config->config_entry('credits_' . $server . '|title_1');
                            break;
                        case 2:
                            $price = round(($value['price'] / 100) * $this->config->config_entry('market|sell_tax') + $value['price']) . ' ' . $this->config->config_entry('credits_' . $server . '|title_2');
                            break;
                        case 3:
                            $price = $this->website->zen_format(round(($value['price'] / 100) * $this->config->config_entry('market|sell_tax') + $value['price'])) . ' ' . $this->config->config_entry('credits_' . $server . '|title_3');
                            break;
                    }
                }
                $this->items[] = [
					'icon' => (date("F j, Y", strtotime($value['add_date'])) == date("F j, Y", time())) ? '<img src="' . $this->config->base_url . 'assets/' . $this->config->config_entry('main|template') . '/images/new.png" />' : $pos, 
					'highlighted' => $value['highlighted'], 
					'price' => $price, 
					'item' => $value['item'], 
					'name' => $this->iteminfo->getNameStyle(true), 
					'namenostyle' => $this->iteminfo->realName(), 
					'id' => $value['id'], 
					'pos' => $pos, 
					'seller' => $value['char'], 
					'date' => $value['add_date'],
					'item_info' => $this->itemimage->load($this->iteminfo->id, $this->iteminfo->type, (int)substr($this->iteminfo->getLevel(), 1)) . '<br />' . $this->iteminfo->allInfo()
				];
                $pos++;
            }
            return $this->items;
        }	

		public function load_filtered_items($cat, $id, $class, $server){
            $where = '';
            if($cat != 'all'){
                $catId = $this->catNameToId($cat);
                $where .= 'WHERE cat = ' . $this->website->db('web')->escape($catId) . '';
            }
            if($id != 'all' && is_numeric($id)){
                $item_id = $id;
                if(isset($catId)){
                    $where .= ' AND item_id = ' . $this->website->db('web')->escape($item_id) . '';
                } else{
                    $where .= 'WHERE item_id = ' . $this->website->db('web')->escape($item_id) . '';
                }
            }
            if($class != 'all'){
                if($class == 'dw'){
                    $where .= (isset($catId) || isset($item_id)) ? ' AND is_sm = 1 ' : 'WHERE is_sm = 1 ';
                }
                if($class == 'dk'){
                    $where .= (isset($catId) || isset($item_id)) ? ' AND is_bk = 1 ' : 'WHERE is_bk = 1 ';
                }
                if($class == 'fe'){
                    $where .= (isset($catId) || isset($item_id)) ? ' AND is_me = 1 ' : 'WHERE is_me = 1 ';
                }
                if($class == 'mg'){
                    $where .= (isset($catId) || isset($item_id)) ? ' AND is_mg = 1 ' : 'WHERE is_mg = 1 ';
                }
                if($class == 'dl'){
                    $where .= (isset($catId) || isset($item_id)) ? ' AND is_dl = 1 ' : 'WHERE is_dl = 1 ';
                }
                if($class == 'su'){
                    $where .= (isset($catId) || isset($item_id)) ? ' AND is_sum = 1 ' : 'WHERE is_sum = 1 ';
                }
                if($class == 'rf'){
                    $where .= (isset($catId) || isset($item_id)) ? ' AND is_rf = 1 ' : 'WHERE is_rf = 1 ';
                }
                if($class == 'gl'){
                    $where .= (isset($catId) || isset($item_id)) ? ' AND is_gl = 1 ' : 'WHERE is_gl = 1 ';
                }
                if($class == 'rw'){
                    $where .= (isset($catId) || isset($item_id)) ? ' AND is_rw = 1 ' : 'WHERE is_rw = 1 ';
                }
				if($class == 'sl'){
                    $where .= (isset($catId) || isset($item_id)) ? ' AND is_sl = 1 ' : 'WHERE is_sl = 1 ';
                }
				if($class == 'gc'){
                    $where .= (isset($catId) || isset($item_id)) ? ' AND is_gc = 1 ' : 'WHERE is_gc = 1 ';
                }
				if($class == 'km'){
                    $where .= (isset($catId) || isset($item_id)) ? ' AND is_km = 1 ' : 'WHERE is_km = 1 ';
                }
				if($class == 'lm'){
                    $where .= (isset($catId) || isset($item_id)) ? ' AND is_lm = 1 ' : 'WHERE is_lm = 1 ';
                }
                if($class == 'ik'){
                    $where .= (isset($catId) || isset($item_id)) ? ' AND is_ik = 1 ' : 'WHERE is_ik = 1 ';
                }
            }
			
			if($where == ''){
				$where = 'WHERE 1 = 1';
			}
            $items = $this->website->db('web')->query('SELECT id, cat, item, price_type, price, seller, add_date, active_till, highlighted, char, price_jewel, jewel_type FROM DmN_Market ' . $where . ' AND active_till > GETDATE() AND ACTIVE = 1 AND sold != 1 AND removed != 1 AND SERVER = '.$this->website->db('web')->escape($server).' ORDER BY id DESC');
            $pos = 1;
            foreach($items->fetch_all() as $value){
                $this->iteminfo->itemData($value['item']);
                if($value['price_jewel'] != 0 && $value['jewel_type'] != 0){
                    $price = $this->get_jewel_image($value['jewel_type']) . 'x ' . $value['price_jewel'];
                } else{
                    switch($value['price_type']){
                        case 1:
                            $price = round(($value['price'] / 100) * $this->config->config_entry('market|sell_tax') + $value['price']) . ' ' . $this->config->config_entry('credits_' . $server . '|title_1');
                            break;
                        case 2:
                            $price = round(($value['price'] / 100) * $this->config->config_entry('market|sell_tax') + $value['price']) . ' ' . $this->config->config_entry('credits_' . $server . '|title_2');
                            break;
                        case 3:
                            $price = $this->website->zen_format(round(($value['price'] / 100) * $this->config->config_entry('market|sell_tax') + $value['price'])) . ' ' . $this->config->config_entry('credits_' . $server . '|title_3');
                            break;
                    }
                }
                $this->items[] = [
					'icon' => (date("F j, Y", strtotime($value['add_date'])) == date("F j, Y", time())) ? '<img src="' . $this->config->base_url . 'assets/' . $this->config->config_entry('main|template') . '/images/new.png" />' : $pos, 
					'highlighted' => $value['highlighted'], 
					'price' => $price, 
					'item' => $value['item'], 
					'name' => $this->iteminfo->getNameStyle(true), 
					'namenostyle' => $this->iteminfo->realName(), 
					'id' => $value['id'], 
					'pos' => $pos, 
					'seller' => $value['char'],
					'item_info' => $this->itemimage->load($this->iteminfo->id, $this->iteminfo->type, (int)substr($this->iteminfo->getLevel(), 1)) . '<br />' . $this->iteminfo->allInfo()
				];
                $pos++;
            }
            return $this->items;
        }
	
		public function get_jewel_image($code, $style = ''){
            switch($code){
                case 4:
                    $img = '12/15.webp';
                    $alt = 'Jewel of Chaos';
                    break;
                case 5:
                    $img = '14/13.webp';
                    $alt = 'Jewel of Bless';
                    break;
                case 6:
                    $img = '14/14.webp';
                    $alt = 'Jewel of Soul';
                    break;
                case 7:
                    $img = '14/16.webp';
                    $alt = 'Jewel of Life';
                    break;
                case 8:
                    $img = '14/22.webp';
                    $alt = 'Jewel of Creation';
                    break;
                case 9:
                    $img = '14/42.webp';
                    $alt = 'Jewel of Harmony';
                    break;
            }
            if(defined('MARKET_IMAGE_URL')){
				return '<img src="' . MARKET_IMAGE_URL . 'assets/item_images/' . $img . '" title="' . $alt . '" style="vertical-align: middle;border:0;' . $style . '"/>';
			}
			else{
				return '<img src="' . str_replace('/interface', '', $this->config->base_url) . 'assets/item_images/' . $img . '" title="' . $alt . '" style="vertical-align: middle;border:0;' . $style . '"/>';
			}
        }
        
        public function check_amount_of_jewels($amount, $type, $vault, $server){
            $hex = str_split($vault, $this->website->get_value_from_server($server, 'item_size'));
            if($type == 4){ //chaos
                $search = [1 => [12, 15], 2 => [12, 141]];
            }
            if($type == 5){ //bless
                $search = [1 => [14, 13], 2 => [12, 30]];
            }
            if($type == 6){ //soul
                $search = [1 => [14, 14], 2 => [12, 31]];
            }
            if($type == 7){ //life
                $search = [1 => [14, 16], 2 => [12, 136]];
            }
            if($type == 8){ //creation
                $search = [1 => [14, 22], 2 => [12, 137]];
            }
            if($type == 9){ //harmony
                $search = [1 => [14, 42], 2 => [12, 140]];
            }
            $found_items = [];
            foreach($hex as $key => $it){
                if($it != str_pad("", $this->website->get_value_from_server($server, 'item_size'), "F")){
                    $this->iteminfo->itemData($it);
                    if($this->iteminfo->id == $search[1][1] && $this->iteminfo->type == $search[1][0]){
                        $found_items[$key] = [
                            'hex' => $it,
                            'dur' => ($this->iteminfo->dur == 0) ? 1 : $this->iteminfo->dur
                        ];
                    }
                }
            }
            
            if(!empty($found_items)){
                $amountLeft = $amount;
                $slots_to_clean = [];
                $slots_to_update = [];
                foreach($found_items AS $slot => $itemData){
                    if($amountLeft > 0){
                        if($itemData['dur'] <= $amountLeft){
                            $slots_to_clean[] = $slot;
                            $amountLeft -= $itemData['dur'];
                        }
                        else{
                            if($itemData['dur'] > $amountLeft){
                                $slots_to_update[] = [
                                    'key' => $slot,
                                    'new_hex' => substr_replace($itemData['hex'], sprintf("%02X", $itemData['dur'] - $amountLeft, 00), 4, 2)
                                ];
                                $amountLeft = 0;
                            }
                        }
                    }
                }
               
                if($amountLeft > 0){
                    return false;
                }
                
                return [$slots_to_clean, $slots_to_update];
            }
            return false;
        }
        
        public function charge_jewels($slots, $vault, $server){
            $hex = str_split($vault, $this->website->get_value_from_server($server, 'item_size'));
            if(!empty($slots[0])){
                foreach($slots[0] as $k1 => $s1){
                    $hex[$s1] = str_pad("", $this->website->get_value_from_server($server, 'item_size'), "F");
                }
            }
            if(!empty($slots[1])){
                foreach($slots[1] as $k2 => $s2){
                    $hex[$s2['key']] = $s2['new_hex'];
                }
            }
            return implode('', $hex);
        }

		public function add_jewels_to_web_wh($jewels, $account, $server){
            $query = 'INSERT INTO DmN_Web_Storage (item, account, server, expires_on) VALUES';
            foreach($jewels AS $key => $jewel){
                $query .= '(\'' . $jewel . '\', \'' . $account . '\', \'' . $server . '\', ' . strtotime('+' . $this->config->config_entry('warehouse|web_wh_item_expires_after')) . '),';
            }
            return $this->website->db('web')->query(substr($query, 0, -1));
        }

		public function get_jewel_by_type($type){
            switch($type){
                default:
                    $jewel = [0, 0];
                    break;
                case 4:
                    $jewel = [12, 15];
                    break;
                case 5:
                    $jewel = [14, 13];
                    break;
                case 6:
                    $jewel = [14, 14];
                    break;
                case 7:
                    $jewel = [14, 16];
                    break;
                case 8:
                    $jewel = [14, 22];
                    break;
                case 9:
                    $jewel = [14, 42];
                    break;
            }
            return $jewel;
        }

		public function price_to_jewels($type){
            switch($type){
                default:
                    $jewel = 'Undefined';
                    break;
                case 4:
                    $jewel = 'Jewel of Chaos';
                    break;
                case 5:
                    $jewel = 'Jewel of Bless';
                    break;
                case 6:
                    $jewel = 'Jewel of Soul';
                    break;
                case 7:
                    $jewel = 'Jewel of Life';
                    break;
                case 8:
                    $jewel = 'Jewel of Creation';
                    break;
                case 9:
                    $jewel = 'Jewel of Harmony';
                    break;
            }
            return $jewel;
        }

		public function load_history_items($user, $server, $page){
            $per_page = ($page <= 1) ? 0 : (int)$this->config->config_entry('market|items_per_page') * ((int)$page - 1);
            $items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape((int)$this->config->config_entry('market|items_per_page')) . ' id, item, price, price_type, active, sold, removed, price_jewel, jewel_type FROM DmN_Market WHERE seller = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).' AND id Not IN (SELECT Top ' . $this->website->db('web')->escape($per_page) . ' id FROM DmN_Market WHERE seller = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).' ORDER BY id DESC) ORDER BY id DESC');
            $pos = ($page == 1) ? 1 : (int)(($page - 1) * $this->config->config_entry('market|items_per_page')) + 1;
            foreach($items->fetch_all() as $value){
                $this->iteminfo->itemData($value['item']);
                if($value['price_jewel'] != 0 && $value['jewel_type'] != 0){
                    $price = $this->get_jewel_image($value['jewel_type']) . 'x ' . $value['price_jewel'];
                } else{
                    switch($value['price_type']){
                        case 1:
                            $price = round(($value['price'] / 100) * $this->config->config_entry('market|sell_tax') + $value['price']) . ' ' . $this->website->translate_credits(1, $server);
                            break;
                        case 2:
                            $price = round(($value['price'] / 100) * $this->config->config_entry('market|sell_tax') + $value['price']) . ' ' . $this->website->translate_credits(2, $server);
                            break;
                        case 3:
                            $price = $this->website->zen_format(round(($value['price'] / 100) * $this->config->config_entry('market|sell_tax') + $value['price'])) . ' ' . $this->website->translate_credits(3, $server);
                            break;
                    }
                }
                $this->items[] = [
					'price' => $price, 
					'item' => $value['item'], 
					'name' => $this->iteminfo->getNameStyle(true), 
					'id' => $value['id'], 
					'pos' => $pos, 
					'active' => $value['active'], 
					'sold' => $value['sold'], 
					'removed' => $value['removed'],
					'item_info' => $this->itemimage->load($this->iteminfo->id, $this->iteminfo->type, (int)substr($this->iteminfo->getLevel(), 1)) . '<br />' . $this->iteminfo->allInfo()
				];
                $pos++;
            }
            return $this->items;
        }

        public function load_item_from_market($id, $server){
            $stmt = $this->website->db('web')->prepare('SELECT TOP 1 item, price, price_type, seller, add_date, active_till, cat, char, server, price_jewel, jewel_type, item_password AS password FROM DmN_Market WITH (UPDLOCK) WHERE id = :id AND add_date <= dateadd(minute,-1,getdate()) AND active = 1 AND removed != 1 AND server = :server');
            $stmt->execute([':id' => (int)$id, ':server' => $server]);
            if($this->item_info = $stmt->fetch()){
                return true;
            }
            return false;
        }

		public function check_item_in_maket($id, $server){
			$stmt = $this->website->db('web')->prepare('SELECT TOP 1 item FROM DmN_Market WHERE id = :id AND add_date <= dateadd(minute,-1,getdate()) AND active = 1 AND removed != 1 AND server = :server');
			$stmt->execute([':id' => (int)$id, ':server' => $server]);
			if($stmt->fetch()){
				return true;
			}
			return false;
		}
		
        public function load_item_from_market_for_history($id, $server){
            $stmt = $this->website->db('web')->prepare('SELECT TOP 1 item, price, price_type, seller, add_date, active_till, active, sold, removed, cat, char, server FROM DmN_Market WITH (UPDLOCK) WHERE id = :id AND server = :server');
            $stmt->execute([':id' => $id, ':server' => $server]);
            if($this->item_info = $stmt->fetch()){
                return true;
            }
            return false;
        }

        public function log_purchase($user, $price, $id){
            $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Market_Logs (seller, buyer, price, price_type, start_date, end_date, sold_date, item, cat, char, server)
										VALUES 
										(:seller, :buyer, :price, :type, :add_date, :active_till, GETDATE(), :item, :cat, :char, :server)');
            $stmt->execute([':seller' => $this->item_info['seller'], ':buyer' => $user, ':price' => $price, ':type' => $this->item_info['price_type'], ':add_date' => date('Y-m-d H:i:s', strtotime($this->item_info['add_date'])), ':active_till' => date('Y-m-d H:i:s', strtotime($this->item_info['active_till'])), ':item' => $this->item_info['item'], ':cat' => $this->item_info['cat'], ':char' => $this->item_info['char'], ':server' => $this->item_info['server']]);
           
        }
		
		public function setSold($id){
			$stmt = $this->website->db('web')->prepare('UPDATE DmN_Market SET active = 0, sold = 1 WHERE id = :id');
            $stmt->execute([':id' => $id]);
		}

        public function change_item_status($id){
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_Market SET active = 0, removed = 1 WHERE id = :id');
            $stmt->execute([':id' => $id]);
        }

        public function remove_from_market($id){
            $stmt = $this->website->db('web')->prepare('DELETE FROM DmN_Market WHERE id = :id');
            $stmt->execute([':id' => $id]);
        }
        
        public function get_lattest_items($server){
            $this->website->check_cache('last_market_' . $server, 'items', 3600, false);
            if(!$this->website->cached){
                return false;
            }
            return $this->website->items;
        }
        
        public function get_lattest_sold_items($server){
            $this->website->check_cache('last_sold_market_' . $server, 'items', 3600, false);
            if(!$this->website->cached){
                return false;
            }
            return $this->website->items;
        }
		
        public function addSlots($user, $server){
			$check = $this->website->db('web')->query('SELECT slots FROM DmN_Market_Slots WHERE memb___id = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).'')->fetch();
			if($check == false){
				$this->website->db('web')->query('INSERT INTO DmN_Market_Slots (memb___id, server, slots) VALUES ('.$this->website->db('web')->escape($user).', '.$this->website->db('web')->escape($server).', 10)');
			}
			else{
				$this->website->db('web')->query('UPDATE DmN_Market_Slots SET slots = slots +10 WHERE memb___id = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).'');
			}
		}
    }