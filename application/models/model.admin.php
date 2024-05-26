<?php
    in_file();

    class Madmin extends model
    {
        public $error = false, $vars = [], $gm_info = [], $vote_link_info = [], $total_items, $items_sql = [], $count_items = 0, $gm_system_type = 1;
        private $items = [], $logs = [], $vault_items, $inventory_items, $new_hex, $item, $translations, $accounts = [], $chars = [], $bans = [], $times = [], $replies = [], $pos = 1, $recipients = [], $gm_list = [], $sql_condition = '';

        public static function valid_username($name, $symbols = '\w\W+', $len = [3, 30]){
            return preg_match('/^[' . $symbols . ']{' . $len[0] . ',' . $len[1] . '}+$/', $name);
        }

        public function __contruct(){
            parent::__construct();
        }

        public function __set($key, $val){
            $this->vars[$key] = $val;
        }

        public function __isset($name){
            return isset($this->vars[$name]);
        }
		
		public function get_current_version(){			
			if(is_readable($path = BASEDIR . 'application' . DS . 'config' . DS . 'cms_config.json')){
				return json_decode(file_get_contents($path), true)['version'];
			}
			return '';
        }
		
		public function get_cms_upgradable_version(){
            return json_decode(file_get_contents(BASEDIR . 'setup' . DS . 'data' . DS . 'version_control.json'), true)['current_version']['version'];
        }

        public function load_statistics(){
            $queries = [];
			$queries2 = [];
			
            if($this->website->is_multiple_accounts() == true){
                foreach($this->website->server_list() AS $key => $server){
                    $db = $this->website->db('account', $key);
                    $queries[$key] = [
						'reg_day' => ['query' => 'SELECT COUNT(*) AS count FROM MEMB_INFO WHERE appl_days >= DATEADD(day, DATEDIFF(day,0,GETDATE()), 0)', 'db' => $db], 
						'reg_week' => ['query' => 'SELECT COUNT(*) AS count FROM MEMB_INFO WHERE appl_days >= DATEADD(week, DATEDIFF(week,0,GETDATE()), 0)', 'db' => $db], 
						'reg_month' => ['query' => 'SELECT COUNT(*) AS count FROM MEMB_INFO WHERE appl_days >= DATEADD(month, DATEDIFF(month,0,GETDATE()), 0)', 'db' => $db], 
					];
					if($db->check_if_column_exists('activated', 'MEMB_INFO')){
						$queries2[$key] = [	
							'activ_day' => ['query' => 'SELECT COUNT(*) AS count FROM MEMB_INFO WHERE appl_days >= DATEADD(day, DATEDIFF(day,0,GETDATE()), 0) AND activated = 1', 'db' => $db], 
							'activ_week' => ['query' => 'SELECT COUNT(*) AS count FROM MEMB_INFO WHERE appl_days >= DATEADD(week, DATEDIFF(week,0,GETDATE()), 0) AND activated = 1', 'db' => $db], 
							'activ_month' => ['query' => 'SELECT COUNT(*) AS count FROM MEMB_INFO WHERE appl_days >= DATEADD(month, DATEDIFF(month,0,GETDATE()), 0) AND activated = 1', 'db' => $db]
						];
					}
					else{
						$queries2[$key] = [	
							'activ_day' => ['query' => 'SELECT COUNT(*) AS count FROM MEMB_INFO WHERE 1 != 1', 'db' => $db], 
							'activ_week' => ['query' => 'SELECT COUNT(*) AS count FROM MEMB_INFO WHERE 1 != 1', 'db' => $db], 
							'activ_month' => ['query' => 'SELECT COUNT(*) AS count FROM MEMB_INFO WHERE 1 != 1', 'db' => $db]
						];
					}
					$queries[$key] = $queries[$key] + $queries2[$key];
                }
                $result = [];
                foreach($queries as $key => $query){
                    foreach($query as $key2 => $value){
                        $qresult = $value['db']->query($value['query']);
                        $result[$key][$key2] = $qresult->fetch();
                        $result[$key][$key2] = (int)$result[$key][$key2]['count'];
                    }
                }
            } 
			else{
				
				$db = $this->website->db('account');
				
                $queries = [
					'reg_day' => ['query' => 'SELECT COUNT(*) AS count FROM MEMB_INFO WHERE appl_days >= DATEADD(day, DATEDIFF(day,0,GETDATE()), 0)'], 
					'reg_week' => ['query' => 'SELECT COUNT(*) AS count FROM MEMB_INFO WHERE appl_days >= DATEADD(week, DATEDIFF(week,0,GETDATE()), 0)'], 
					'reg_month' => ['query' => 'SELECT COUNT(*) AS count FROM MEMB_INFO WHERE appl_days >= DATEADD(month, DATEDIFF(month,0,GETDATE()), 0)']
				];
				
				if($db->check_if_column_exists('activated', 'MEMB_INFO')){
					$queries2 = [	
						'activ_day' => ['query' => 'SELECT COUNT(*) AS count FROM MEMB_INFO WHERE appl_days >= DATEADD(day, DATEDIFF(day,0,GETDATE()), 0) AND activated = 1'], 
						'activ_week' => ['query' => 'SELECT COUNT(*) AS count FROM MEMB_INFO WHERE appl_days >= DATEADD(week, DATEDIFF(week,0,GETDATE()), 0) AND activated = 1'], 
						'activ_month' => ['query' => 'SELECT COUNT(*) AS count FROM MEMB_INFO WHERE appl_days >= DATEADD(month, DATEDIFF(month,0,GETDATE()), 0) AND activated = 1']
					];
				}
				else{
					$queries2 = [	
						'activ_day' => ['query' => 'SELECT COUNT(*) AS count FROM MEMB_INFO WHERE 1 != 1'], 
						'activ_week' => ['query' => 'SELECT COUNT(*) AS count FROM MEMB_INFO WHERE 1 != 1'], 
						'activ_month' => ['query' => 'SELECT COUNT(*) AS count FROM MEMB_INFO WHERE 1 != 1']
					];
				}
				$queries = $queries + $queries2;
                $result = [];
                foreach($queries as $key => $query){
                    $qresult = $db->query($query['query']);
                    $result[$key] = $qresult->fetch();
                    $result[$key] = (int)$result[$key]['count'];
                }
            }
            return $result;
        }

        public function load_last_admin_login_attemts(){
            return $this->website->db('web')->query('SELECT TOP 5 memb___id, time, ip FROM DmN_Admin_Logins ORDER BY time DESC')->fetch_all();
        }

        public function total_accounts(){
            $total = 0;
            if($this->website->is_multiple_accounts() == true){
                foreach($this->website->server_list() AS $key => $server){
                    $query = $this->website->db('account', $key)->snumrows('SELECT COUNT(memb___id) AS count FROM MEMB_INFO');
                    $total += $query;
                }
            } else{
                $query = $this->website->db($this->website->get_default_account_database())->snumrows('SELECT COUNT(memb___id) AS count FROM MEMB_INFO');
                $total += $query;
            }
            return $total;
        }

        public function total_online(){
            $total = 0;
            if($this->website->is_multiple_accounts() == true){
                foreach($this->website->server_list() AS $key => $server){
                    $query = $this->website->db('account', $key)->snumrows('SELECT COUNT(memb___id) AS count FROM MEMB_STAT WHERE ConnectStat = 1');
                    $total += $query;
                }
            } else{
                $query = $this->website->db($this->website->get_default_account_database())->snumrows('SELECT COUNT(memb___id) AS count FROM MEMB_STAT WHERE ConnectStat = 1');
                $total += $query;
            }
            return $total;
        }

        public function total_characters(){
            $total = 0;
            foreach($this->website->server_list() AS $key => $server){
                $query = $this->website->db('game', $key)->snumrows('SELECT COUNT(Name) AS count FROM Character');
                $total += $query;
            }
            return $total;
        }

        public function total_guilds(){
            $total = 0;
            foreach($this->website->server_list() AS $key => $server){
                $query = $this->website->db('game', $key)->snumrows('SELECT COUNT(G_Name) AS count FROM Guild');
                $total += $query;
            }
            return $total;
        }

        public function login_admin(){
            if($this->vars['username'] === USERNAME && md5($this->vars['password'] . SECURITY_SALT) === md5(PASSWORD . SECURITY_SALT)){
                $this->session->register('admin', ['username' => $this->vars['username'], 'is_admin' => true]);
                if(defined('PINCODE') && PINCODE != ''){
                    $_SESSION['admin']['pincode'] = PINCODE;
                }
                $this->admin_login_attemt($this->vars['username']);
                return true;
            }
            return false;
        }

        private function admin_login_attemt($user = ''){
            $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Admin_Logins (memb___id, time, ip) VALUES (:user, GETDATE(), :ip)');
            $stmt->execute([':user' => $user, ':ip' => ip()]);
        }

        public function load_news(){
            $file = file_get_contents(APP_PATH . DS . 'data' . DS . 'dmn_news.json');
            $json = json_decode($file, true);
            if(is_array($json)){
                krsort($json);
                $news = [];
                foreach($json AS $key => $data){
                    $news[] = [
						'id' => $key, 
						'title' => $data['title'], 
						'time' => $data['time'], 
						'author' => $data['author'], 
						'lang' => $data['lang'], 
						'type' => isset($data['type']) ? $data['type'] : 1
					];
                }
                return $news;
            }
        }

        public function add_news(){
            $file = file_get_contents(APP_PATH . DS . 'data' . DS . 'dmn_news.json');
            $json = json_decode($file, true);
            if(is_array($json)){
                $new_data = [
					'title' => htmlspecialchars($this->vars['title']), 
					'news_content' => $this->vars['news_small'], 
					'news_content_full' => $this->vars['news_big'], 
					'time' => time(), 
					'icon' => $this->vars['img_url'], 
					'author' => $this->session->userdata(['admin' => 'username']), 
					'lang' => implode(',', $this->vars['news_lang']),
					'type' => $this->vars['news_type']
				];
                $json[] = $new_data;
                $this->write_news($json);
                return true;
            } else{
                $new_data = [1 => [
					'title' => htmlspecialchars($this->vars['title']), 
					'news_content' => $this->vars['news_small'], 
					'news_content_full' => $this->vars['news_big'], 
					'time' => time(), 
					'icon' => $this->vars['img_url'], 
					'author' => $this->session->userdata(['admin' => 'username']), 
					'lang' => implode(',', $this->vars['news_lang']),
					'type' => $this->vars['news_type']
				]];
                $this->write_news($new_data);
                return true;
            }
        }

        private function write_news($data){
            if(empty($data)){
                $data = '';
            } else{
                $data = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            }
            file_put_contents(APP_PATH . DS . 'data' . DS . 'dmn_news.json', $data);
        }

        public function edit_news($id){
            $file = file_get_contents(APP_PATH . DS . 'data' . DS . 'dmn_news.json');
            $json = json_decode($file, true);
            if(is_array($json)){
                if(array_key_exists($id, $json)){
                    $json[$id] = [
						'title' => htmlspecialchars($this->vars['title']), 
						'news_content' => $this->vars['news_small'], 
						'news_content_full' => $this->vars['news_big'], 
						'time' => time(), 'icon' => $this->vars['img_url'],
						'author' => $this->session->userdata(['admin' => 'username']), 
						'lang' => implode(',', $this->vars['news_lang']),
						'type' => $this->vars['news_type']
					];
                    $this->write_news($json);
                    return true;
                }
            }
            return false;
        }

        public function check_news($id){
            $file = file_get_contents(APP_PATH . DS . 'data' . DS . 'dmn_news.json');
            $json = json_decode($file, true);
            if(is_array($json)){
                if(array_key_exists($id, $json)){
                    return $json[$id];
                }
            }
            return false;
        }

        public function delete_news($id){
            $file = file_get_contents(APP_PATH . DS . 'data' . DS . 'dmn_news.json');
            $json = json_decode($file, true);
            if(is_array($json)){
                if(array_key_exists($id, $json)){
                    unset($json[$id]);
                    $this->write_news($json);
                    return true;
                }
            }
            return false;
        }

        public function add_guide(){
            $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Guides (title, text, lang, date, category) VALUES (:title, :text, :lang, GETDATE(), :category)');
            $stmt->execute([':title' => bin2hex($this->vars['title']), ':text' => bin2hex($this->vars['guide']), ':lang' => $this->vars['lang'], ':category' => $this->vars['category']]);
        }
		
		public function add_drop(){
            $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Drops (title, text, lang, date, cat) VALUES (:title, :text, :lang, GETDATE(), :cat)');
            $stmt->execute([':title' => $this->vars['title'], ':text' => $this->vars['guide'], ':lang' => $this->vars['lang'], ':cat' => $this->vars['cat']]);
        }

        public function list_guides(){
            return $this->website->db('web')->query('SELECT id, title, lang, date, category FROM DmN_Guides ORDER BY date DESC')->fetch_all();
        }
		
		public function list_drops(){
            return $this->website->db('web')->query('SELECT id, title, lang, date, cat FROM DmN_Drops ORDER BY date DESC')->fetch_all();
        }

        public function check_guide($id){
            $stmt = $this->website->db('web')->prepare('SELECT id, title, text, date, lang, category FROM DmN_Guides WHERE id = :id');
            $stmt->execute([':id' => $id]);
            return $stmt->fetch();
        }
		
		public function check_drop($id){
            $stmt = $this->website->db('web')->prepare('SELECT id, title, text, date, lang, cat FROM DmN_Drops WHERE id = :id');
            $stmt->execute([':id' => $id]);
            return $stmt->fetch();
        }

        public function delete_guide($id){
            $stmt = $this->website->db('web')->prepare('DELETE FROM DmN_Guides WHERE id = :id');
            return $stmt->execute([':id' => $id]);
        }
		
		public function delete_drop($id){
            $stmt = $this->website->db('web')->prepare('DELETE FROM DmN_Drops WHERE id = :id');
            return $stmt->execute([':id' => $id]);
        }

        public function edit_guide($id){
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_Guides SET title = :title, lang = :lang, text = :text, category = :category WHERE id = :id');
            return $stmt->execute([':title' => bin2hex($this->vars['title']), ':lang' => $this->vars['lang'], ':text' => bin2hex($this->vars['guide']), ':category' => $this->vars['category'], ':id' => $id]);
        }
		
		public function edit_drop($id){
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_Drops SET title = :title, lang = :lang, text = :text, cat = :cat WHERE id = :id');
            return $stmt->execute([':title' => $this->vars['title'], ':lang' => $this->vars['lang'], ':text' => $this->vars['guide'], ':cat' => $this->vars['cat'], ':id' => $id]);
        }

        public function load_gallery(){
            $gallery = $this->website->db('web')->query('SELECT id, name, section FROM DmN_Gallery ORDER BY add_date DESC')->fetch_all();
            return ($gallery) ? $gallery : false;
        }

        public function add_gallery_image($name, $section){
            $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Gallery (name, section, add_date) VALUES (:name, :section, :time)');
            $stmt->execute([':name' => $name, ':section' => $section, ':time' => time()]);
        }

        public function upload_image($image, $name, $thumbnail = true, $directory = ''){
            $image_size = @getimagesize($image);
			
			switch($image_size['mime']){
				case 'image/jpg':
				case 'image/jpeg':
					$old_image = @imagecreatefromjpeg($image);
				break;
				case 'image/gif':
					$old_image = @imagecreatefromgif($image);
				break;
				case 'image/png':
					$old_image = @imagecreatefrompng($image);
				break;
				case 'image/bmp':
					$old_image = imagecreatefromwbmp($image);
				break;
				default:
					$this->error = 'Image upload error.';
					return false;
				break;
			}

            if($directory == '')
                $directory = BASEDIR . 'assets' . DS . 'uploads' . DS . 'normal' . DS;
            $result_image_name = $directory . $name;
            $image_croped = imagecreatetruecolor($image_size[0], $image_size[1]);
            if(@!imagecopyresampled($image_croped, $old_image, 0, 0, 0, 0, $image_size[0], $image_size[1], $image_size[0], $image_size[1])){
                $this->error = 'Function error.';
                return false;
            }
            if(@imagejpeg($image_croped, $result_image_name)){
                imagedestroy($image_croped);
                if($thumbnail){
                    $this->create_thumbnail(150, $result_image_name);
                }
                return true;
            } else{
                $this->error = 'Image upload error.';
                return false;
            }
        }

        private function create_thumbnail($size, $image){
            if(file_exists($image)){
                $image_size = @getimagesize($image);
                $image_width = $image_size[0];
                $image_height = $image_size[1];
                switch($image_size['mime']){
					case 'image/jpg':
					case 'image/jpeg':
						$old_image = @imagecreatefromjpeg($image);
					break;
					case 'image/gif':
						$old_image = @imagecreatefromgif($image);
					break;
					case 'image/png':
						$old_image = @imagecreatefrompng($image);
					break;
					case 'image/bmp':
						$old_image = imagecreatefromwbmp($image);
					break;
					default:
						$this->error = 'Image upload error.';
						return false;
					break;
				}
                $arr = explode(DS, $image);
                $name = array_pop($arr);
                $result_image_name = BASEDIR . 'assets' . DS . 'uploads' . DS . 'thumb' . DS . $this->website->strstr_alt($name, '.', true) . '_thumb' . $this->website->strstr_alt($name, '.', false);
                if($image_height > $image_width){
                    $smaller_axe = $image_width;
                } else{
                    $smaller_axe = $image_height;
                }
                $x = ($image_width - $smaller_axe) / 2;
                $y = ($image_height - $smaller_axe) / 2;
                $image_croped = imagecreatetruecolor($size, $size);
                if(@!imagecopyresampled($image_croped, $old_image, 0, 0, $x, $y, $size, $size, $smaller_axe, $smaller_axe)){
                    $this->error = 'Function error.';
                    return false;
                }
                if(@imagejpeg($image_croped, $result_image_name)){
                    imagedestroy($image_croped);
                    return true;
                } else{
                    $this->error = 'Image upload error.';
                    return false;
                }
            } else{
                $this->error = 'Picture does not exist.';
                return false;
            }
        }

        public function delete_gallery_image($id){
            $stmt = $this->website->db('web')->prepare('DELETE FROM DmN_Gallery WHERE id = :id');
            $stmt->execute([':id' => $id]);
        }

        public function check_gallery_image($id){
            $stmt = $this->website->db('web')->prepare('SELECT name FROM DmN_Gallery WHERE id = :id');
            $stmt->execute([':id' => $id]);
            return $stmt->fetch();
        }

        public function add_file(){
            $max_orders = $this->website->db('web')->query('SELECT ISNULL(MAX(orders), 0) AS max_orders FROM DmN_Downloads')->fetch();
            $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Downloads (link_name, link_desc, link_size, link_type, link_url, orders) VALUES (:link_name, :link_desc, :link_size, :link_type, :link_url, :orders)');
            return $stmt->execute([':link_name' => htmlspecialchars($this->vars['link_name']), ':link_desc' => htmlspecialchars($this->vars['link_desc']), ':link_size' => htmlspecialchars($this->vars['link_size']), ':link_type' => htmlspecialchars($this->vars['link_type']), ':link_url' => htmlspecialchars($this->vars['link_url']), ':orders' => $max_orders['max_orders']]);
        }

        public function edit_file($id){
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_Downloads SET link_name = :link_name, link_desc = :link_desc, link_size = :link_size, link_type = :link_type, link_url = :link_url WHERE id = :id');
            return $stmt->execute([':link_name' => htmlspecialchars($this->vars['link_name']), ':link_desc' => htmlspecialchars($this->vars['link_desc']), ':link_size' => htmlspecialchars($this->vars['link_size']), ':link_type' => htmlspecialchars($this->vars['link_type']), ':link_url' => htmlspecialchars($this->vars['link_url']), ':id' => (int)$id]);
        }

        public function load_files(){
            $files = $this->website->db('web')->query('SELECT id, link_name, link_url, link_type FROM DmN_Downloads ORDER BY orders ASC')->fetch_all();
            return ($files) ? $files : false;
        }

        public function check_file($id){
            $stmt = $this->website->db('web')->prepare('SELECT id, link_name, link_desc, link_size, link_type, link_url, type FROM DmN_Downloads WHERE id = :id');
            $stmt->execute([':id' => $id]);
            return ($info = $stmt->fetch()) ? $info : false;
        }

        public function delete_file($id){
            $stmt = $this->website->db('web')->prepare('DELETE FROM DmN_Downloads WHERE id = :id');
            $stmt->execute([':id' => $id]);
        }

        public function save_downloads_order($orders){
            foreach($orders as $key => $value){
                pre($key);
                $stmt = $this->website->db('web')->prepare('UPDATE DmN_Downloads SET orders = :order WHERE id = :id');
                $stmt->execute([':order' => $key, ':id' => $value]);
            }
        }

        public function check_gm_char($name, $server){
            $stmt = $this->website->db('game', $server)->prepare('SELECT AccountId, CtlCode FROM Character WHERE Name = :name');
            $stmt->execute([':name' => $name]);
            return ($this->gm_info = $stmt->fetch()) ? true : false;
        }

        public function set_ctlcode($code, $name, $server){
            $stmt = $this->website->db('game', $server)->prepare('UPDATE Character SET CtlCode = :code WHERE Name = :name');
            $stmt->execute([':code' => $code, ':name' => $name]);
        }

        public function add_igcn_autority($authorityMask, $valid_until, $name, $server){
            $stmt = $this->website->db('game', $server)->prepare('SELECT Name FROM T_GMSystem WHERE Name = :name');
            $stmt->execute([':name' => $name]);
            if($stmt->fetch()){
                return $this->update_icgn_authority($name, $authorityMask, $valid_until, $server);
            } else{
                return $this->insert_icgn_authority($name, $authorityMask, $valid_until, $server);
            }
        }

        private function update_icgn_authority($name, $authorityMask, $valid_until, $server){
            $stmt = $this->website->db('game', $server)->prepare('UPDATE T_GMSystem SET AuthorityMask = :authmask, Expiry = :expiry WHERE Name = :name');
            return $stmt->execute([':authmask' => $authorityMask, ':expiry' => date('Y-m-d', strtotime($valid_until)), ':name' => $name]);
        }

        private function insert_icgn_authority($name, $authorityMask, $valid_until, $server){
            $stmt = $this->website->db('game', $server)->prepare('INSERT INTO T_GMSystem (Name, AuthorityMask, Expiry) VALUES(:name, :authmask, :expiry)');
            return $stmt->execute([':name' => $name, ':authmask' => $authorityMask, ':expiry' => date('Y-m-d', strtotime($valid_until))]);
        }

        public function add_to_gmlist(){
            $this->vars['ban_acc'] = isset($this->vars['ban_acc']) ? 1 : 0;
            $this->vars['ban_char'] = isset($this->vars['ban_char']) ? 1 : 0;
            $this->vars['search_acc'] = isset($this->vars['search_acc']) ? 1 : 0;
            $this->vars['acc_details'] = isset($this->vars['acc_details']) ? 1 : 0;
            $this->vars['contact'] = isset($this->vars['contact']) ? $this->vars['contact'] : '';
            if(!$this->check_gm_list()){
                $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Gm_List (account, character, server, can_ban_acc, can_ban_char, can_search_acc, can_view_acc_details, limit_reward_credits, system_type, contact) VALUES (:account, :character, :server, :can_ban_acc, :can_ban_char, :can_search_acc, :can_view_acc_details, :limit_reward_credits, :system_type, :contact)');
                $stmt->execute([':account' => $this->gm_info['AccountId'], ':character' => $this->vars['name'], ':server' => $this->vars['server'], ':can_ban_acc' => $this->vars['ban_acc'], ':can_ban_char' => $this->vars['ban_char'], ':can_search_acc' => $this->vars['search_acc'], ':can_view_acc_details' => $this->vars['acc_details'], ':limit_reward_credits' => (int)$this->vars['credits_limit'], ':system_type' => (int)$this->vars['system_type'], ':contact' => $this->vars['contact']]);
            } 
            else{
                $this->error = 'This character already is in gamemaster list';
            }
        }

        private function check_gm_list(){
            $stmt = $this->website->db('web')->prepare('SELECT account, system_type FROM DmN_Gm_List WHERE account = :account AND server = :server');
            $stmt->execute([':account' => $this->gm_info['AccountId'], ':server' => $this->vars['server']]);
            $info = $stmt->fetch();
            if($info){
                $this->gm_system_type = $info['system_type'];
                return true;
            }
            return false;
        }

        public function edit_gm($name, $server){
            $this->vars['ban_acc'] = isset($this->vars['ban_acc']) ? 1 : 0;
            $this->vars['ban_char'] = isset($this->vars['ban_char']) ? 1 : 0;
            $this->vars['search_acc'] = isset($this->vars['search_acc']) ? 1 : 0;
            $this->vars['acc_details'] = isset($this->vars['acc_details']) ? 1 : 0;
            $this->vars['contact'] = isset($this->vars['contact']) ? $this->vars['contact'] : '';
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_Gm_List SET can_ban_acc = :can_ban_acc, can_ban_char = :can_ban_char, can_search_acc = :can_search_acc, can_view_acc_details = :can_view_acc_details, limit_reward_credits = :credits_limit, system_type = :system_type, contact = :contact WHERE character = :character AND server = :server');
            $stmt->execute([':can_ban_acc' => $this->vars['ban_acc'], ':can_ban_char' => $this->vars['ban_char'], ':can_search_acc' => $this->vars['search_acc'], ':can_view_acc_details' => $this->vars['acc_details'], ':credits_limit' => $this->vars['credits_limit'], ':system_type' => (int)$this->vars['system_type'], ':contact' => $this->vars['contact'], ':character' => $name, ':server' => $server]);
        }

        public function remove_gm_from_list($name, $server){
            $stmt = $this->website->db('web')->prepare('DELETE FROM DmN_Gm_List WHERE character = :character AND server = :server');
            $stmt->execute([':character' => $name, ':server' => $server]);
        }

        public function remove_from_igcn_gm_system($name, $server){
            $stmt = $this->website->db('game', $server)->prepare('DELETE FROM T_GMSystem WHERE Name = :character');
            $stmt->execute([':character' => $name]);
        }

        public function check_gm_type($name, $server){
            $stmt = $this->website->db('web')->prepare('SELECT system_type FROM DmN_Gm_List WHERE character = :name AND server = :server');
            $stmt->execute([':name' => $name, ':server' => $server]);
            $info = $stmt->fetch();
            if($info){
                $this->gm_system_type = $info['system_type'];
                return true;
            }
            return false;
        }

        public function load_gm_list(){
            return $this->website->db('web')->query('SELECT account, character, server, can_ban_acc, can_ban_char, can_search_acc, can_view_acc_details FROM DmN_Gm_List ORDER BY server DESC')->fetch_all();
        }

        public function load_gm_info($name, $server){
            $stmt = $this->website->db('web')->prepare('SELECT account, character, server, can_ban_acc, can_ban_char, can_search_acc, can_view_acc_details, limit_reward_credits, system_type, contact FROM DmN_Gm_List WHERE character = :character AND server = :server');
            $stmt->execute([':character' => $name, ':server' => $server]);
            return $stmt->fetch();
        }

        public function get_gm_authority_mask($name, $server){
            $stmt = $this->website->db('game', $server)->prepare('SELECT AuthorityMask, Expiry FROM T_GMSystem WHERE Name = :character');
            $stmt->execute([':character' => $name]);
            return $stmt->fetch();
        }

        public function load_announcement(){
            return $this->website->db('web')->query('SELECT TOP 1 announcement FROM DmN_GM_Announcement ORDER BY time DESC')->fetch();
        }

        public function add_anouncement($text){
            $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_GM_Announcement (announcement, time) VALUES (:announcement, :time)');
            $stmt->execute([':announcement' => $text, ':time' => time()]);
        }
		
		public function load_partner_logs($page = 1, $per_page = 25, $coupon = ''){
            if($coupon == '' || $coupon == '-')
                $items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape((int)$per_page) . ' coupon, username, date_used, generated_by FROM DmN_Partner_Used_Coupons WHERE id Not IN (SELECT Top ' . $this->website->db('web')->escape((int)($per_page * ($page - 1))) . ' id FROM DmN_Partner_Used_Coupons ORDER BY id DESC) ORDER BY id DESC'); 
			else{
                if($coupon != '' && $coupon != '-')
                    $items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape((int)$per_page) . ' coupon, username, date_used, generated_by FROM DmN_Partner_Used_Coupons WHERE coupon like \'%' . $this->website->db('web')->escape($coupon) . '%\' AND id Not IN (SELECT Top ' . $this->website->db('web')->escape((int)($per_page * ($page - 1))) . ' id FROM DmN_Partner_Used_Coupons WHERE coupon like \'%' . $this->website->db('web')->escape($coupon) . '%\' ORDER BY id DESC) ORDER BY id DESC'); 
            }
            $pos = ($page == 1) ? 1 : (int)(($page - 1) * $per_page) + 1;
            foreach($items->fetch_all() as $value){
                $this->items[] = [
					'acc' => htmlspecialchars($value['username']), 
					'coupon' => $value['coupon'], 
					'generated_by' => $value['generated_by'], 
					'date_used' => $value['date_used'], 
					'pos' => $pos
				];
                $pos++;
            }
            return $this->items;
        }

        public function load_shop_logs($page = 1, $per_page = 25, $acc = '', $server = 'All', $date_from = '', $date_to = ''){
            if(($acc == '' || $acc == '-') && $server == 'All')
                $items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape((int)$per_page) . ' memb___id, server, item_hex, date, price, price_type, ip FROM DmN_Shop_Logs WHERE id Not IN (SELECT Top ' . $this->website->db('web')->escape((int)($per_page * ($page - 1))) . ' id FROM DmN_Shop_Logs ORDER BY id DESC) ORDER BY id DESC'); 
			else{
                if(($acc != '' && $acc != '-') && $server == 'All')
                    $items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape((int)$per_page) . ' memb___id, server, item_hex, date, price, price_type, ip FROM DmN_Shop_Logs WHERE memb___id like \'%' . $this->website->db('web')->escape($acc) . '%\' AND date BETWEEN '.$this->website->db('web')->escape($date_from).' AND '.$this->website->db('web')->escape($date_to).' AND id Not IN (SELECT Top ' . $this->website->db('web')->escape((int)($per_page * ($page - 1))) . ' id FROM DmN_Shop_Logs WHERE memb___id like \'%' . $this->website->db('web')->escape($acc) . '%\' AND date BETWEEN '.$this->website->db('web')->escape($date_from).' AND '.$this->website->db('web')->escape($date_to).' ORDER BY id DESC) ORDER BY id DESC'); 
				else
                    $items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape((int)$per_page) . ' memb___id, server, item_hex, date, price, price_type, ip FROM DmN_Shop_Logs WHERE memb___id like \'%' . $this->website->db('web')->escape($acc) . '%\' AND server = '.$this->website->db('web')->escape($server).' AND date BETWEEN '.$this->website->db('web')->escape($date_from).' AND '.$this->website->db('web')->escape($date_to).' AND id Not IN (SELECT Top ' . $this->website->db('web')->escape((int)($per_page * ($page - 1))) . ' id FROM DmN_Shop_Logs WHERE memb___id like \'%' . $this->website->db('web')->escape($acc) . '%\' AND server = '.$this->website->db('web')->escape($server).' AND date BETWEEN '.$this->website->db('web')->escape($date_from).' AND '.$this->website->db('web')->escape($date_to).' ORDER BY id DESC) ORDER BY id DESC');
            }
            $pos = ($page == 1) ? 1 : (int)(($page - 1) * $per_page) + 1;
            foreach($items->fetch_all() as $value){
                $this->iteminfo->itemData($value['item_hex']);
                $this->items[] = [
					'acc' => htmlspecialchars($value['memb___id']), 
					'server' => htmlspecialchars($value['server']), 
					'name' => $this->iteminfo->getNameStyle(), 
					'namenostyle' => $this->iteminfo->realName(), 
					'hex' => $value['item_hex'], 
					'serial' => $this->iteminfo->serial, 
					'date' => $value['date'], 
					'price' => $value['price'], 
					'payment_type' => $this->website->translate_credits($value['price_type'], ($server == 'All') ? 'DEFAULT' : $server), 
					'ip' => $value['ip'], 
					'pos' => $pos
				];
                $pos++;
            }
            return $this->items;
        }

        public function load_paypal_transactions($page = 1, $per_page = 25, $acc = '', $server = 'All'){
            if(($acc == '' || $acc == '-') && $server == 'All')
                $items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape((int)$per_page) . ' transaction_id, amount, currency, acc, server, credits, order_date, status, payer_email, country FROM DmN_Donate_Transactions WHERE id Not IN (SELECT Top ' . $this->website->db('web')->escape((int)($per_page * ($page - 1))) . ' id FROM DmN_Donate_Transactions ORDER BY id DESC) ORDER BY id DESC'); else{
                if(($acc != '' && $acc != '-') && $server == 'All')
                    $items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape((int)$per_page) . ' transaction_id, amount, currency, acc, server, credits, order_date, status, payer_email, country FROM DmN_Donate_Transactions WHERE acc like \'%' . $this->website->db('web')->escape($acc) . '%\' AND id Not IN (SELECT Top ' . $this->website->db('web')->escape((int)($per_page * ($page - 1))) . ' id FROM DmN_Donate_Transactions WHERE acc like \'%' . $this->website->db('web')->escape($acc) . '%\' ORDER BY id DESC) ORDER BY id DESC'); else
                    $items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape((int)$per_page) . ' transaction_id, amount, currency, acc, server, credits, order_date, status, payer_email, country FROM DmN_Donate_Transactions WHERE acc like \'%' . $this->website->db('web')->escape($acc) . '%\' AND server = '.$this->website->db('web')->escape($server).' AND id Not IN (SELECT Top ' . $this->website->db('web')->escape((int)($per_page * ($page - 1))) . ' id FROM DmN_Donate_Transactions WHERE acc like \'%' . $this->website->db('web')->escape($acc) . '%\' AND server = '.$this->website->db('web')->escape($server).' ORDER BY id DESC) ORDER BY id DESC');
            }
            foreach($items->fetch_all() as $value){
                $this->logs[] = ['transaction' => $value['transaction_id'], 'amount' => $value['amount'], 'currency' => $value['currency'], 'acc' => htmlspecialchars($value['acc']), 'server' => htmlspecialchars($value['server']), 'credits' => $value['credits'], 'order_date' => date(DATETIME_FORMAT, $value['order_date']), 'status' => $value['status'], 'payer_email' => $value['payer_email'], 'country' => $this->website->codeToCountryName($value['country'])];
            }
            return $this->logs;
        }

        public function load_pagseguro_transactions($page = 1, $per_page = 25, $acc = '', $server = 'All'){
            if(($acc == '' || $acc == '-') && $server == 'All')
                $items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape((int)$per_page) . ' transaction_id, amount, currency, acc, server, credits, order_date FROM DmN_PagSeguro_Transactions WHERE id Not IN (SELECT Top ' . $this->website->db('web')->escape((int)($per_page * ($page - 1))) . ' id FROM DmN_PagSeguro_Transactions ORDER BY id DESC) ORDER BY id DESC'); else{
                if(($acc != '' && $acc != '-') && $server == 'All')
                    $items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape((int)$per_page) . ' transaction_id, amount, currency, acc, server, credits, order_date FROM DmN_PagSeguro_Transactions WHERE acc like \'%' . $this->website->db('web')->escape($acc) . '%\' AND id Not IN (SELECT Top ' . $this->website->db('web')->escape((int)($per_page * ($page - 1))) . ' id FROM DmN_PagSeguro_Transactions WHERE acc like \'%' . $this->website->db('web')->escape($acc) . '%\' ORDER BY id DESC) ORDER BY id DESC'); else
                    $items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape((int)$per_page) . ' transaction_id, amount, currency, acc, server, credits, order_date FROM DmN_PagSeguro_Transactions WHERE acc like \'%' . $this->website->db('web')->escape($acc) . '%\' AND server = '.$this->website->db('web')->escape($server).' AND id Not IN (SELECT Top ' . $this->website->db('web')->escape((int)($per_page * ($page - 1))) . ' id FROM DmN_PagSeguro_Transactions WHERE acc like \'%' . $this->website->db('web')->escape($acc) . '%\' AND server = '.$this->website->db('web')->escape($server).' ORDER BY id DESC) ORDER BY id DESC');
            }
            foreach($items->fetch_all() as $value){
                $this->logs[] = ['transaction' => $value['transaction_id'], 'amount' => $value['amount'], 'currency' => $value['currency'], 'acc' => htmlspecialchars($value['acc']), 'server' => htmlspecialchars($value['server']), 'credits' => $value['credits'], 'order_date' => date(DATETIME_FORMAT, $value['order_date'])];
            }
            return $this->logs;
        }

        public function load_interkassa_transactions($page = 1, $per_page = 25, $acc = '', $server = 'All'){
            if(($acc == '' || $acc == '-') && $server == 'All')
                $items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape((int)$per_page) . ' transaction_id, amount, currency, acc, server, credits, order_date FROM DmN_Donate_Interkassa_Transactions WHERE id Not IN (SELECT Top ' . $this->website->db('web')->escape((int)($per_page * ($page - 1))) . ' id FROM DmN_Donate_Interkassa_Transactions ORDER BY id DESC) ORDER BY id DESC'); else{
                if(($acc != '' && $acc != '-') && $server == 'All')
                    $items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape((int)$per_page) . ' transaction_id, amount, currency, acc, server, credits, order_date FROM DmN_Donate_Interkassa_Transactions WHERE acc like \'%' . $this->website->db('web')->escape($acc) . '%\' AND id Not IN (SELECT Top ' . $this->website->db('web')->escape((int)($per_page * ($page - 1))) . ' id FROM DmN_Donate_Interkassa_Transactions WHERE acc like \'%' . $this->website->db('web')->escape($acc) . '%\' ORDER BY id DESC) ORDER BY id DESC'); else
                    $items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape((int)$per_page) . ' transaction_id, amount, currency, acc, server, credits, order_date FROM DmN_Donate_Interkassa_Transactions WHERE acc like \'%' . $this->website->db('web')->escape($acc) . '%\' AND server = '.$this->website->db('web')->escape($server).' AND id Not IN (SELECT Top ' . $this->website->db('web')->escape((int)($per_page * ($page - 1))) . ' id FROM DmN_Donate_Interkassa_Transactions WHERE acc like \'%' . $this->website->db('web')->escape($acc) . '%\' AND server = '.$this->website->db('web')->escape($server).' ORDER BY id DESC) ORDER BY id DESC');
            }
            foreach($items->fetch_all() as $value){
                $this->logs[] = ['transaction' => $value['transaction_id'], 'amount' => $value['amount'], 'currency' => $value['currency'], 'acc' => htmlspecialchars($value['acc']), 'server' => htmlspecialchars($value['server']), 'credits' => $value['credits'], 'order_date' => date(DATETIME_FORMAT, $value['order_date'])];
            }
            return $this->logs;
        }

        public function load_cuenta_digital_transactions($page = 1, $per_page = 25, $acc = '', $server = 'All'){
            if(($acc == '' || $acc == '-') && $server == 'All')
                $items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape((int)$per_page) . ' amount, currency, acc, server, credits, order_date FROM DmN_Donate_CuentaDigital_Transactions WHERE id Not IN (SELECT Top ' . $this->website->db('web')->escape((int)($per_page * ($page - 1))) . ' id FROM DmN_Donate_CuentaDigital_Transactions ORDER BY id DESC) ORDER BY id DESC'); else{
                if(($acc != '' && $acc != '-') && $server == 'All')
                    $items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape((int)$per_page) . ' amount, currency, acc, server, credits, order_date FROM DmN_Donate_CuentaDigital_Transactions WHERE acc like \'%' . $this->website->db('web')->escape($acc) . '%\' AND id Not IN (SELECT Top ' . $this->website->db('web')->escape((int)($per_page * ($page - 1))) . ' id FROM DmN_Donate_CuentaDigital_Transactions WHERE acc like \'%' . $this->website->db('web')->escape($acc) . '%\' ORDER BY id DESC) ORDER BY id DESC'); else
                    $items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape((int)$per_page) . ' amount, currency, acc, server, credits, order_date FROM DmN_Donate_CuentaDigital_Transactions WHERE acc like \'%' . $this->website->db('web')->escape($acc) . '%\' AND server = '.$this->website->db('web')->escape($server).' AND id Not IN (SELECT Top ' . $this->website->db('web')->escape((int)($per_page * ($page - 1))) . ' id FROM DmN_Donate_CuentaDigital_Transactions WHERE acc like \'%' . $this->website->db('web')->escape($acc) . '%\' AND server = '.$this->website->db('web')->escape($server).' ORDER BY id DESC) ORDER BY id DESC');
            }
            foreach($items->fetch_all() as $value){
                $this->logs[] = ['amount' => $value['amount'], 'currency' => $value['currency'], 'acc' => htmlspecialchars($value['acc']), 'server' => htmlspecialchars($value['server']), 'credits' => $value['credits'], 'order_date' => date(DATETIME_FORMAT, $value['order_date'])];
            }
            return $this->logs;
        }

        public function load_twocheckout_transactions($page = 1, $per_page = 25, $acc = '', $server = 'All'){
            if(($acc == '' || $acc == '-') && $server == 'All')
                $items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape((int)$per_page) . ' transaction_id, amount, currency, acc, server, credits, order_date, payer_email FROM DmN_2CheckOut_Transactions WHERE id Not IN (SELECT Top ' . $this->website->db('web')->escape((int)($per_page * ($page - 1))) . ' id FROM DmN_2CheckOut_Transactions ORDER BY id DESC) ORDER BY id DESC'); else{
                if(($acc != '' && $acc != '-') && $server == 'All')
                    $items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape((int)$per_page) . ' transaction_id, amount, currency, acc, server, credits, order_date, payer_email FROM DmN_2CheckOut_Transactions WHERE acc like \'%' . $this->website->db('web')->escape($acc) . '%\' AND id Not IN (SELECT Top ' . $this->website->db('web')->escape((int)($per_page * ($page - 1))) . ' id FROM DmN_2CheckOut_Transactions WHERE acc like \'%' . $this->website->db('web')->escape($acc) . '%\' ORDER BY id DESC) ORDER BY id DESC'); else
                    $items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape((int)$per_page) . ' transaction_id, amount, currency, acc, server, credits, order_date, payer_email FROM DmN_2CheckOut_Transactions WHERE acc like \'%' . $this->website->db('web')->escape($acc) . '%\' AND server = '.$this->website->db('web')->escape($server).' AND id Not IN (SELECT Top ' . $this->website->db('web')->escape((int)($per_page * ($page - 1))) . ' id FROM DmN_2CheckOut_Transactions WHERE acc like \'%' . $this->website->db('web')->escape($acc) . '%\' AND server = '.$this->website->db('web')->escape($server).' ORDER BY id DESC) ORDER BY id DESC');
            }
            foreach($items->fetch_all() as $value){
                $this->logs[] = ['transaction' => $value['transaction_id'], 'amount' => $value['amount'], 'currency' => $value['currency'], 'acc' => htmlspecialchars($value['acc']), 'server' => htmlspecialchars($value['server']), 'credits' => $value['credits'], 'order_date' => date(DATETIME_FORMAT, $value['order_date']), 'payer_email' => $value['payer_email']];
            }
            return $this->logs;
        }

        public function load_pw_transactions($page = 1, $per_page = 25, $acc = '', $server = 'All'){
            if(($acc == '' || $acc == '-') && $server == 'All')
                $items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape((int)$per_page) . ' uid, server, currency, type, ref, reason, order_date FROM DmN_Donate WHERE id Not IN (SELECT Top ' . $this->website->db('web')->escape((int)($per_page * ($page - 1))) . ' id FROM DmN_Donate ORDER BY id DESC) ORDER BY id DESC'); else{
                if(($acc != '' && $acc != '-') && $server == 'All')
                    $items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape((int)$per_page) . ' uid, server, currency, type, ref, reason, order_date FROM DmN_Donate WHERE uid like \'%' . $this->website->db('web')->escape($acc) . '%\' AND id Not IN (SELECT Top ' . $this->website->db('web')->escape((int)($per_page * ($page - 1))) . ' id FROM DmN_Donate WHERE uid like \'%' . $this->website->db('web')->escape($acc) . '%\' ORDER BY id DESC) ORDER BY id DESC'); else
                    $items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape((int)$per_page) . ' uid, server, currency, type, ref, reason, order_date FROM DmN_Donate WHERE uid like \'%' . $this->website->db('web')->escape($acc) . '%\' AND server = '.$this->website->db('web')->escape($server).' AND id Not IN (SELECT Top ' . $this->website->db('web')->escape((int)($per_page * ($page - 1))) . ' id FROM DmN_Donate WHERE uid like \'%' . $this->website->db('web')->escape($acc) . '%\' AND server = '.$this->website->db('web')->escape($server).' ORDER BY id DESC) ORDER BY id DESC');
            }
            foreach($items->fetch_all() as $value){
                switch($value['type']){
                    case 0:
                        $type = 'Credits is earned';
                        break;
                    case 1:
                        $type = 'Credits is given by customer service';
                        break;
                    case 2:
                        $type = 'Chargeback by customer service';
                        break;
                }
                $this->logs[] = ['acc' => $value['uid'], 'currency' => $value['currency'], 'type' => $type, 'server' => htmlspecialchars($value['server']), 'transaction' => $value['ref'], 'status' => $value['reason'], 'order_date' => date(DATETIME_FORMAT, $value['order_date'])];
            }
            return $this->logs;
        }

        public function load_fortumo_transactions($page = 1, $per_page = 25, $acc = '', $server = 'All'){
            if(($acc == '' || $acc == '-') && $server == 'All')
                $items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape((int)$per_page) . ' payment_id, sender, account, server, credits FROM DmN_Donate_Fortumo WHERE id Not IN (SELECT Top ' . $this->website->db('web')->escape((int)($per_page * ($page - 1))) . ' id FROM DmN_Donate_Fortumo ORDER BY id DESC) ORDER BY id DESC'); else{
                if(($acc != '' && $acc != '-') && $server == 'All')
                    $items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape((int)$per_page) . ' payment_id, sender, account, server, credits FROM DmN_Donate_Fortumo WHERE account like \'%' . $this->website->db('web')->escape($acc) . '%\' AND id Not IN (SELECT Top ' . $this->website->db('web')->escape((int)($per_page * ($page - 1))) . ' id FROM DmN_Donate_Fortumo WHERE account like \'%' . $this->website->db('web')->escape($acc) . '%\' ORDER BY id DESC) ORDER BY id DESC'); else
                    $items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape((int)$per_page) . ' payment_id, sender, account, server, credits FROM DmN_Donate_Fortumo WHERE account like \'%' . $this->website->db('web')->escape($acc) . '%\' AND server = '.$this->website->db('web')->escape($server).' AND id Not IN (SELECT Top ' . $this->website->db('web')->escape((int)($per_page * ($page - 1))) . ' id FROM DmN_Donate_Fortumo WHERE account like \'%' . $this->website->db('web')->escape($acc) . '%\' AND server = '.$this->website->db('web')->escape($server).' ORDER BY id DESC) ORDER BY id DESC');
            }
            foreach($items->fetch_all() as $value){
                $this->logs[] = ['transaction' => $value['payment_id'], 'sender' => $value['sender'], 'acc' => htmlspecialchars($value['account']), 'server' => htmlspecialchars($value['server']), 'credits' => $value['credits']];
            }
            return $this->logs;
        }

        public function load_paygol_transactions($page = 1, $per_page = 25, $acc = '', $server = 'All'){
            if(($acc == '' || $acc == '-') && $server == 'All')
                $items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape((int)$per_page) . ' message_id, sender, country, currency, price, acc, server FROM DmN_PayGoal_Log WHERE id Not IN (SELECT Top ' . $this->website->db('web')->escape((int)($per_page * ($page - 1))) . ' id FROM DmN_PayGoal_Log ORDER BY id DESC) ORDER BY id DESC'); else{
                if(($acc != '' && $acc != '-') && $server == 'All')
                    $items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape((int)$per_page) . ' message_id, sender, country, currency, price, acc, server FROM DmN_PayGoal_Log WHERE acc like \'%' . $this->website->db('web')->escape($acc) . '%\' AND id Not IN (SELECT Top ' . $this->website->db('web')->escape((int)($per_page * ($page - 1))) . ' id FROM DmN_PayGoal_Log WHERE acc like \'%' . $this->website->db('web')->escape($acc) . '%\' ORDER BY id DESC) ORDER BY id DESC'); else
                    $items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape((int)$per_page) . ' message_id, sender, country, currency, price, acc, server FROM DmN_PayGoal_Log WHERE acc like \'%' . $this->website->db('web')->escape($acc) . '%\' AND server = '.$this->website->db('web')->escape($server).' AND id Not IN (SELECT Top ' . $this->website->db('web')->escape((int)($per_page * ($page - 1))) . ' id FROM DmN_PayGoal_Log WHERE acc like \'%' . $this->website->db('web')->escape($acc) . '%\' AND server = '.$this->website->db('web')->escape($server).' ORDER BY id DESC) ORDER BY id DESC');
            }
            foreach($items->fetch_all() as $value){
                $this->logs[] = ['transaction' => $value['message_id'], 'sender' => $value['sender'], 'country' => $value['country'], 'currency' => $value['currency'], 'price' => $value['price'], 'acc' => htmlspecialchars($value['acc']), 'server' => htmlspecialchars($value['server'])];
            }
            return $this->logs;
        }

        public function load_paycall_transactions($page = 1, $per_page = 25, $acc = '', $server = 'All'){
            if(($acc == '' || $acc == '-') && $server == 'All')
                $items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape((int)$per_page) . ' transaction_id, amount, acc, server, credits, order_date FROM DmN_Donate_PayCall_Transactions WHERE id Not IN (SELECT Top ' . $this->website->db('web')->escape((int)($per_page * ($page - 1))) . ' id FROM DmN_Donate_PayCall_Transactions ORDER BY id DESC) ORDER BY id DESC'); else{
                if(($acc != '' && $acc != '-') && $server == 'All')
                    $items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape((int)$per_page) . ' transaction_id, amount, acc, server, credits, order_date FROM DmN_Donate_PayCall_Transactions WHERE acc like \'%' . $this->website->db('web')->escape($acc) . '%\' AND id Not IN (SELECT Top ' . $this->website->db('web')->escape((int)($per_page * ($page - 1))) . ' id FROM DmN_Donate_PayCall_Transactions WHERE acc like \'%' . $this->website->db('web')->escape($acc) . '%\' ORDER BY id DESC) ORDER BY id DESC'); else
                    $items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape((int)$per_page) . ' transaction_id, amount, acc, server, credits, order_date FROM DmN_Donate_PayCall_Transactions WHERE acc like \'%' . $this->website->db('web')->escape($acc) . '%\' AND server = '.$this->website->db('web')->escape($server).' AND id Not IN (SELECT Top ' . $this->website->db('web')->escape((int)($per_page * ($page - 1))) . ' id DmN_Donate_PayCall_Transactions WHERE acc like \'%' . $this->website->db('web')->escape($acc) . '%\' AND server = '.$this->website->db('web')->escape($server).' ORDER BY id DESC) ORDER BY id DESC');
            }
            foreach($items->fetch_all() as $value){
                $this->logs[] = ['transaction' => $value['transaction_id'], 'amount' => $value['amount'], 'acc' => htmlspecialchars($value['acc']), 'server' => htmlspecialchars($value['server']), 'credits' => $value['credits'], 'order_date' => date(DATETIME_FORMAT, $value['order_date'])];
            }
            return $this->logs;
        }

        public function load_market_logs($page = 1, $per_page = 25, $acc = '', $server = 'All', $date_from = '', $date_to = ''){
            if(($acc == '' || $acc == '-') && $server == 'All')
                $items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape((int)$per_page) . ' seller, buyer, price, price_type, sold_date, item, server FROM DmN_Market_Logs WHERE id Not IN (SELECT Top ' . $this->website->db('web')->escape((int)($per_page * ($page - 1))) . ' id FROM DmN_Market_Logs ORDER BY id DESC) ORDER BY id DESC'); else{
                if(($acc != '' && $acc != '-') && $server == 'All')
                    $items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape((int)$per_page) . ' seller, buyer, price, price_type, sold_date, item, server FROM DmN_Market_Logs WHERE seller like \'%' . $this->website->db('web')->escape($acc) . '%\' AND sold_date BETWEEN '.$this->website->db('web')->escape($date_from).' AND '.$this->website->db('web')->escape($date_to).' AND id Not IN (SELECT Top ' . $this->website->db('web')->escape((int)($per_page * ($page - 1))) . ' id FROM DmN_Market_Logs WHERE seller like \'%' . $this->website->db('web')->escape($acc) . '%\' AND sold_date BETWEEN '.$this->website->db('web')->escape($date_from).' AND '.$this->website->db('web')->escape($date_to).' ORDER BY id DESC) ORDER BY id DESC'); else
                    $items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape((int)$per_page) . ' seller, buyer, price, price_type, sold_date, item, server FROM DmN_Market_Logs WHERE seller like \'%' . $this->website->db('web')->escape($acc) . '%\' AND server = '.$this->website->db('web')->escape($server).' AND sold_date BETWEEN '.$this->website->db('web')->escape($date_from).' AND '.$this->website->db('web')->escape($date_to).' AND id Not IN (SELECT Top ' . $this->website->db('web')->escape((int)($per_page * ($page - 1))) . ' id FROM DmN_Market_Logs WHERE seller like \'%' . $this->website->db('web')->escape($acc) . '%\' AND server = '.$this->website->db('web')->escape($server).' AND sold_date BETWEEN '.$this->website->db('web')->escape($date_from).' AND '.$this->website->db('web')->escape($date_to).' ORDER BY id DESC) ORDER BY id DESC');
            }
            $pos = ($page == 1) ? 1 : (int)(($page - 1) * $per_page) + 1;
            foreach($items->fetch_all() as $value){
                $this->iteminfo->itemData($value['item']);
                $this->items[] = ['seller' => htmlspecialchars($value['seller']), 'buyer' => htmlspecialchars($value['buyer']), 'server' => htmlspecialchars($value['server']), 'name' => $this->iteminfo->getNameStyle(), 'namenostyle' => $this->iteminfo->realName(), 'hex' => $value['item'], 'serial' => $this->iteminfo->serial, 'date' => $value['sold_date'], 'price' => $value['price'], 'payment_type' => $this->website->translate_credits($value['price_type'], ($server == 'All') ? 'DEFAULT' : $server), 'pos' => $pos];
                $pos++;
            }
            return $this->items;
        }
		
		public function searchConditionAccount($string = '', $column = 'account'){
			if($string != ''){
				$this->sql_condition .= ' AND '.$column.' LIKE \'' . $string . '%\'';
			}
		}
		
		public function searchConditionDates($date1, $date2, $column){
			$this->sql_condition .= ' AND '.$column.' BETWEEN \'' . $date1 . '\' AND \'' . $date2 . '\'';
		}
		
		public function searchConditionText($string = ''){
			if($string != '')
				$this->sql_condition .= ' AND text LIKE \'%' . $string . '%\'';
		}

        public function load_account_logs($page = 1, $per_page = 25, $order_column = 3, $order_dir = 'desc'){
			$dir = ($order_dir == 'desc') ? 'DESC' : 'ASC';
			switch($order_column){
				case 0:
					$column = 'account';
					break;
				case 5:
					$column = 'server';
					break;
				default:
				case 3:
					$column = 'date';
					break;
			}
			$condition2 = '';
			if($this->sql_condition != ''){
				$condition2 = 'WHERE ' . substr($this->sql_condition, 5);
			}
			
			$items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape((int)$per_page) . ' text, amount, date, account, server, ip FROM DmN_Account_Logs WHERE id Not IN (SELECT Top ' . $page . ' id FROM DmN_Account_Logs '.$condition2.' ORDER BY ' . $column . ' ' . $dir . ') ' . $this->sql_condition . ' ORDER BY ' . $column . ' ' . $dir . ''); 
            foreach($items->fetch_all() as $value){
				if($value['date'] instanceof \DateTime) {
					$date = $value['date']->format(DATETIME_FORMAT);
				}
				else{
					$date = date(DATETIME_FORMAT, strtotime($value['date']));
				}
                $this->items[] = [
					'account' => htmlspecialchars($value['account']), 
					'text' => $value['text'], 
					'server' => htmlspecialchars($value['server']), 
					'amount' => $value['amount'], 
					'date' => $date, 
					'ip' => $value['ip']
				];
            }
            return $this->items;
        }

        public function load_gm_logs($page = 1, $per_page = 25, $acc = '', $server = 'All', $date_from = '', $date_to = ''){
            if(($acc == '' || $acc == '-') && $server == 'All')
                $items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape((int)$per_page) . ' text, date, account, server, ip FROM DmN_GM_Logs WHERE id Not IN (SELECT Top ' . $this->website->db('web')->escape((int)($per_page * ($page - 1))) . ' id FROM DmN_GM_Logs ORDER BY id DESC) ORDER BY id DESC'); else{
                if(($acc != '' && $acc != '-') && $server == 'All')
                    $items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape((int)$per_page) . ' text, date, account,server, ip FROM DmN_GM_Logs WHERE account like \'%' . $this->website->db('web')->escape($acc) . '%\' AND date BETWEEN '.$this->website->db('web')->escape($date_from).' AND '.$this->website->db('web')->escape($date_to).' AND id Not IN (SELECT Top ' . $this->website->db('web')->escape((int)($per_page * ($page - 1))) . ' id FROM DmN_GM_Logs WHERE account like \'%' . $this->website->db('web')->escape($acc) . '%\' AND date BETWEEN '.$this->website->db('web')->escape($date_from).' AND '.$this->website->db('web')->escape($date_to).' ORDER BY id DESC) ORDER BY id DESC'); else
                    $items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape((int)$per_page) . ' text, date, account,server, ip FROM DmN_GM_Logs WHERE account like \'%' . $this->website->db('web')->escape($acc) . '%\' AND server = '.$this->website->db('web')->escape($server).' AND date BETWEEN '.$this->website->db('web')->escape($date_from).' AND '.$this->website->db('web')->escape($date_to).' AND id Not IN (SELECT Top ' . $this->website->db('web')->escape((int)($per_page * ($page - 1))) . ' id FROM DmN_GM_Logs WHERE account like \'%' . $this->website->db('web')->escape($acc) . '%\' AND server = '.$this->website->db('web')->escape($server).' AND date BETWEEN '.$this->website->db('web')->escape($date_from).' AND '.$this->website->db('web')->escape($date_to).' ORDER BY id DESC) ORDER BY id DESC');
            }
            $pos = ($page == 1) ? 1 : (int)(($page - 1) * $per_page) + 1;
            foreach($items->fetch_all() as $value){
                $this->items[] = ['account' => htmlspecialchars($value['account']), 'text' => $value['text'], 'server' => htmlspecialchars($value['server']), 'date' => $value['date'], 'ip' => $value['ip'], 'pos' => $pos];
                $pos++;
            }
            return $this->items;
        }
		
		public function count_total_partner_logs($coupon = ''){
            $sql = '';
            if($coupon != '' && $coupon != '-'){
                $sql .= 'WHERE coupon like \'%' . $this->website->db('web')->escape($coupon) . '%\'';
            }
            $count = $this->website->db('web')->snumrows('SELECT COUNT(id) AS count FROM DmN_Partner_Used_Coupons ' . $sql . '');
            return $count;
        }

        public function count_total_shop_logs($acc = '', $server = 'All', $from = '', $to = ''){
            $sql = '';
            if($acc != '' && $acc != '-'){
                $sql .= 'WHERE memb___id like \'%' . $this->website->db('web')->escape($acc) . '%\'';
                if($server != 'All'){
                    $sql .= ' AND server = '.$this->website->db('web')->escape($server).'';
                }
                if($from != '' && $to != ''){
                    $sql .= ' AND date BETWEEN '.$this->website->db('web')->escape($from).' AND '.$this->website->db('web')->escape($to).'';
                }
            }
            $count = $this->website->db('web')->snumrows('SELECT COUNT(memb___id) AS count FROM DmN_Shop_Logs ' . $sql . '');
            return $count;
        }

        public function count_total_paypal_transactions($acc = '', $server = 'All'){
            $sql = '';
            if($acc != '' && $acc != '-'){
                $sql .= 'WHERE acc like \'%' . $this->website->db('web')->escape($acc) . '%\'';
                if($server != 'All'){
                    $sql .= ' AND server = '.$this->website->db('web')->escape($server).'';
                }
            }
            $count = $this->website->db('web')->snumrows('SELECT COUNT(acc) AS count FROM DmN_Donate_Transactions ' . $sql . '');
            return $count;
        }

        public function count_total_pagseguro_transactions($acc = '', $server = 'All'){
            $sql = '';
            if($acc != '' && $acc != '-'){
                $sql .= 'WHERE acc like \'%' . $this->website->db('web')->escape($acc) . '%\'';
                if($server != 'All'){
                    $sql .= ' AND server = '.$this->website->db('web')->escape($server).'';
                }
            }
            $count = $this->website->db('web')->snumrows('SELECT COUNT(acc) AS count FROM DmN_PagSeguro_Transactions ' . $sql . '');
            return $count;
        }

        public function count_total_interkassa_transactions($acc = '', $server = 'All'){
            $sql = '';
            if($acc != '' && $acc != '-'){
                $sql .= 'WHERE acc like \'%' . $this->website->db('web')->escape($acc) . '%\'';
                if($server != 'All'){
                    $sql .= ' AND server = '.$this->website->db('web')->escape($server).'';
                }
            }
            $count = $this->website->db('web')->snumrows('SELECT COUNT(acc) AS count FROM DmN_Donate_Interkassa_Transactions ' . $sql . '');
            return $count;
        }

        public function count_total_cuenta_digital_transactions($acc = '', $server = 'All'){
            $sql = '';
            if($acc != '' && $acc != '-'){
                $sql .= 'WHERE acc like \'%' . $this->website->db('web')->escape($acc) . '%\'';
                if($server != 'All'){
                    $sql .= ' AND server = '.$this->website->db('web')->escape($server).'';
                }
            }
            $count = $this->website->db('web')->snumrows('SELECT COUNT(acc) AS count FROM DmN_Donate_CuentaDigital_Transactions ' . $sql . '');
            return $count;
        }

        public function count_total_twocheckout_transactions($acc = '', $server = 'All'){
            $sql = '';
            if($acc != '' && $acc != '-'){
                $sql .= 'WHERE acc like \'%' . $this->website->db('web')->escape($acc) . '%\'';
                if($server != 'All'){
                    $sql .= ' AND server = '.$this->website->db('web')->escape($server).'';
                }
            }
            $count = $this->website->db('web')->snumrows('SELECT COUNT(acc) AS count FROM DmN_2CheckOut_Transactions ' . $sql . '');
            return $count;
        }

        public function count_total_pw_transactions($acc = '', $server = 'All'){
            $sql = '';
            if($acc != '' && $acc != '-'){
                $sql .= 'WHERE uid like \'%' . $this->website->db('web')->escape($acc) . '%\'';
                if($server != 'All'){
                    $sql .= ' AND server = '.$this->website->db('web')->escape($server).'';
                }
            }
            $count = $this->website->db('web')->snumrows('SELECT COUNT(uid) AS count FROM DmN_Donate ' . $sql . '');
            return $count;
        }

        public function count_total_fortumo_transactions($acc = '', $server = 'All'){
            $sql = '';
            if($acc != '' && $acc != '-'){
                $sql .= 'WHERE account like \'%' . $this->website->db('web')->escape($acc) . '%\'';
                if($server != 'All'){
                    $sql .= ' AND server = '.$this->website->db('web')->escape($server).'';
                }
            }
            $count = $this->website->db('web')->snumrows('SELECT COUNT(account) AS count FROM DmN_Donate_Fortumo ' . $sql . '');
            return $count;
        }

        public function count_total_paygol_transactions($acc = '', $server = 'All'){
            $sql = '';
            if($acc != '' && $acc != '-'){
                $sql .= 'WHERE acc like \'%' . $this->website->db('web')->escape($acc) . '%\'';
                if($server != 'All'){
                    $sql .= ' AND server = '.$this->website->db('web')->escape($server).'';
                }
            }
            $count = $this->website->db('web')->snumrows('SELECT COUNT(acc) AS count FROM DmN_PayGoal_Log ' . $sql . '');
            return $count;
        }

        public function count_total_paycall_transactions($acc = '', $server = 'All'){
            $sql = '';
            if($acc != '' && $acc != '-'){
                $sql .= 'WHERE acc like \'%' . $this->website->db('web')->escape($acc) . '%\'';
                if($server != 'All'){
                    $sql .= ' AND server = '.$this->website->db('web')->escape($server).'';
                }
            }
            $count = $this->website->db('web')->snumrows('SELECT COUNT(acc) AS count FROM DmN_Donate_PayCall_Transactions ' . $sql . '');
            return $count;
        }

        public function count_total_market_logs($acc = '', $server = 'All', $from = '', $to = ''){
            $sql = '';
            if($acc != '' && $acc != '-'){
                $sql .= 'WHERE seller like \'%' . $this->website->db('web')->escape($acc) . '%\'';
                if($server != 'All'){
                    $sql .= ' AND server = '.$this->website->db('web')->escape($server).'';
                }
                if($from != '' && $to != ''){
                    $sql .= ' AND sold_date BETWEEN '.$this->website->db('web')->escape($from).' AND '.$this->website->db('web')->escape($to).'';
                }
            }
            $count = $this->website->db('web')->snumrows('SELECT COUNT(seller) AS count FROM DmN_Market_Logs ' . $sql . '');
            return $count;
        }

        public function count_total_account_logs($filtered = false){
			$condition2 = '';
			if($this->sql_condition != '' && $filtered == true){
				$condition2 = 'WHERE ' . substr($this->sql_condition, 5);
			}
            $count = $this->website->db('web')->snumrows('SELECT COUNT(account) AS count FROM DmN_Account_Logs ' . $condition2 . '');
            return $count;
        }

        public function count_total_gm_logs($acc = '', $server = 'All', $from = '', $to = ''){
            $sql = '';
            if($acc != '' && $acc != '-'){
                $sql .= 'WHERE  account like \'%' . $this->website->db('web')->escape($acc) . '%\'';
                if($server != 'All'){
                    $sql .= ' AND server = '.$this->website->db('web')->escape($server).'';
                }
                if($from != '' && $to != ''){
                    $sql .= ' AND date BETWEEN '.$this->website->db('web')->escape($from).' AND '.$this->website->db('web')->escape($to).'';
                }
            }
            $count = $this->website->db('web')->snumrows('SELECT COUNT(account) AS count FROM DmN_GM_Logs ' . $sql . '');
            return $count;
        }

        public function search_char_inventory($serial, $server){
            return $this->website->db('game', $server)->query('SELECT Name FROM Character WHERE (charindex (0x' . $this->website->db('game', $server)->escape($serial) . ', Inventory) %16=4)')->fetch();
        }

        public function search_warehouse($serial){
            return $this->website->db('game', $server)->query('SELECT AccountId FROM Warehouse WHERE (charindex (0x' . $this->website->db('game', $server)->escape($serial) . ', Items) %16=4)')->fetch();
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

        public function create_vault($acc, $server){
            $stmt = $this->website->db('game', $server)->prepare('INSERT INTO warehouse (AccountID, Items, Money, EndUseDate) VALUES (:user, cast(REPLICATE(char(0xff), ' . $this->website->get_value_from_server($server, 'wh_size') . ') AS VARBINARY(' . $this->website->get_value_from_server($server, 'wh_size') . ')), 0, getdate())');
            $this->vault_items['Items'] = str_pad("F", $this->website->get_value_from_server($server, 'wh_size'), "F");
            return $stmt->execute([':user' => $acc]);
        }

        public function load_items($server){
            $hex = str_split($this->vault_items['Items'], $this->website->get_value_from_server($server, 'item_size'));
            $items = [];
            $i = 0;
            $x = 0;
            $y = 0;
            foreach($hex as $it){
                $i++;
                if($it != str_pad("", $this->website->get_value_from_server($server, 'item_size'), "F")){
                    $this->iteminfo->itemData($it);
                    //$this->iteminfo->GetOptions();
                    $items[$i]['item_id'] = $this->iteminfo->id;
                    $items[$i]['item_cat'] = $this->iteminfo->type;
                    $items[$i]['name'] = $this->iteminfo->realName();
                    $items[$i]['level'] = (int)substr($this->iteminfo->getLevel(), 1);
					$items[$i]['x'] = $this->iteminfo->getX();
                    $items[$i]['y'] = $this->iteminfo->getY();
                    $items[$i]['xx'] = $x;
                    $items[$i]['yy'] = $y;
                    $items[$i]['hex'] = $it;
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
            $i = 0;
            $found = false;
            foreach($hex as $it){
                $i++;
                if($it != str_pad("", $this->website->get_value_from_server($server, 'item_size'), "F")){
                    if($i == $slot){
                        $this->item = $it;
                        $found = true;
                        break;
                    }
                }
            }
            return $found;
        }

        public function generate_new_item_by_slot($slot, $server){
            $hex = str_split($this->vault_items['Items'], $this->website->get_value_from_server($server, 'item_size'));
            $new_items = [];
            $i = 0;
            foreach($hex as $it){
                $i++;
                if($i == $slot){
                    $new_items[$i] = str_pad("", $this->website->get_value_from_server($server, 'item_size'), "F");
                } else{
                    $new_items[$i] = $it;
                }
            }
            $this->new_hex = implode('', $new_items);
        }

        public function update_warehouse($user, $server){
            $stmt = $this->website->db('game', $server)->prepare('UPDATE Warehouse SET Items = 0x' . $this->new_hex . ' WHERE AccountId = :user');
            $stmt->execute([':user' => $user]);
        }

        public function log_deleted_item($user = '', $server = '', $by_admin = 0){
            $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Warehouse_Delete_Log (account, server, item, date, deleted_by_admin) VALUES (:account, :server, :item, GETDATE(), :by_admin)');
            $stmt->execute([':account' => $user, ':server' => $server, ':item' => $this->item, ':by_admin' => $by_admin]);
        }

        public function get_inventory_content($char, $server){
			$stmt = $this->website->db('game', $server)->prepare('SELECT CONVERT(IMAGE, Inventory) AS Inventory FROM Character WHERE Name = :char');
			$stmt->execute([':char' => $char]);
			if($this->inventory_items = $stmt->fetch()){
                $unpack = unpack('H*', $this->inventory_items['Inventory']);
				$this->inventory_items['Inventory'] = $this->website->clean_hex($unpack[1]);
			}       
        }

        public function remove_vault_item_by_serial($acc, $serial, $server){
            $found = false;
            $items_array = str_split($this->vault_items['Items'], $this->website->get_value_from_server($server, 'item_size'));
            foreach($items_array as $key => $value){
                if(strtoupper($serial) === substr($items_array[$key], 6, 8)){
                    $found = true;
                    $items_array[$key] = str_repeat('F', $this->website->get_value_from_server($server, 'item_size'));
                    break;
                }
            }
            if($found){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE Warehouse SET Items = 0x' . implode('', $items_array) . ' WHERE AccountId = :user');
                return $stmt->execute([':user' => $acc]);
            }
            return false;
        }

        public function remove_inventory_item_by_serial($char, $serial, $server){
            $found = false;
            $items_array = str_split($this->inventory_items['Inventory'], $this->website->get_value_from_server($server, 'item_size'));
            foreach($items_array as $key => $value){
                if(strtoupper($serial) === substr($items_array[$key], 6, 8)){
                    $found = true;
                    $items_array[$key] = str_repeat('F', $this->website->get_value_from_server($server, 'item_size'));
                    break;
                }
            }
            if($found){
                $stmt = $this->website->db('game', $server)->prepare('UPDATE Character SET Inventory = 0x' . implode('', $items_array) . ' WHERE Name = :char');
                return $stmt->execute([':char' => $char]);
            }
            return false;
        }

        public function load_paypal_packages(){
            return $this->website->db('web')->query('SELECT id, package, reward, price, currency, orders, status, server FROM DmN_Donate_Packages ORDER BY orders ASC')->fetch_all();
        }

        public function save_paypal_order($orders){
            foreach($orders as $key => $value){
                $stmt = $this->website->db('web')->prepare('UPDATE DmN_Donate_Packages SET orders = :order WHERE id = :id');
                $stmt->execute([':order' => $key, ':id' => $value]);
            }
        }

        public function check_paypal_package($id){
            $stmt = $this->website->db('web')->prepare('SELECT id FROM DmN_Donate_Packages WHERE id = :id');
            $stmt->execute([':id' => $id]);
            return $stmt->fetch();
        }

        public function edit_paypal_package($id, $title, $price, $currency, $reward, $server){
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_Donate_Packages SET package = :title, reward = :reward, price = :price, currency = :currency, server = :server WHERE id = :id');
            $stmt->execute([':title' => $title, ':reward' => $reward, ':price' => $price, ':currency' => $currency, ':server' => $server, ':id' => $id]);
        }

        public function delete_paypal_package($id){
            $stmt = $this->website->db('web')->prepare('DELETE FROM DmN_Donate_Packages WHERE id = :id');
            $stmt->execute([':id' => $id]);
        }

        public function change_paypal_status($id, $status){
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_Donate_Packages SET status = :status WHERE id = :id');
            $stmt->execute([':status' => $status, ':id' => $id]);
        }

        public function add_paypal_package($title, $price, $currency, $reward, $server){
            $max_orders = $this->website->db('web')->query('SELECT ISNULL(MAX(orders), 0) AS max_orders FROM DmN_Donate_Packages')->fetch();
            $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Donate_Packages (package, reward, price, currency, orders, status, server) VALUES (:title, :reward, :price, :currency, :count, 1, :server)');
            $stmt->execute([':title' => $title, ':reward' => $reward, ':price' => $price, ':currency' => $currency, ':count' => $max_orders['max_orders'], ':server' => $server]);
            return $this->website->db('web')->last_insert_id();
        }

        public function load_twocheckout_packages(){
            return $this->website->db('web')->query('SELECT id, package, reward, price, currency, orders, status, server FROM DmN_2CheckOut_Packages ORDER BY orders ASC')->fetch_all();
        }

        public function load_pagseguro_packages(){
            return $this->website->db('web')->query('SELECT id, package, reward, price, currency, orders, status, server FROM DmN_PagSeguro_Packages ORDER BY orders ASC')->fetch_all();
        }

        public function save_twocheckout_order($orders){
            foreach($orders as $key => $value){
                $id = explode('_', $value);
                $stmt = $this->website->db('web')->prepare('UPDATE DmN_2CheckOut_Packages SET orders = :order WHERE id = :id');
                $stmt->execute([':order' => $key, ':id' => end($id)]);
            }
        }

        public function save_pagseguro_order($orders){
            foreach($orders as $key => $value){
                $id = explode('_', $value);
                $stmt = $this->website->db('web')->prepare('UPDATE DmN_PagSeguro_Packages SET orders = :order WHERE id = :id');
                $stmt->execute([':order' => $key, ':id' => end($id)]);
            }
        }

        public function save_paycall_order($orders){
            foreach($orders as $key => $value){
                $id = explode('_', $value);
                $stmt = $this->website->db('web')->prepare('UPDATE DmN_Donate_PayCall_Packages SET orders = :order WHERE id = :id');
                $stmt->execute([':order' => $key, ':id' => end($id)]);
            }
        }

        public function save_interkassa_order($orders){
            foreach($orders as $key => $value){
                $id = explode('_', $value);
                $stmt = $this->website->db('web')->prepare('UPDATE DmN_Donate_Interkassa_Packages SET orders = :order WHERE id = :id');
                $stmt->execute([':order' => $key, ':id' => end($id)]);
            }
        }

        public function save_cuenta_digital_order($orders){
            foreach($orders as $key => $value){
                $id = explode('_', $value);
                $stmt = $this->website->db('web')->prepare('UPDATE DmN_Donate_CuentaDigital_Packages SET orders = :order WHERE id = :id');
                $stmt->execute([':order' => $key, ':id' => end($id)]);
            }
        }

        public function check_twocheckout_package($id){
            $stmt = $this->website->db('web')->prepare('SELECT id FROM DmN_2CheckOut_Packages WHERE id = :id');
            $stmt->execute([':id' => $id]);
            return $stmt->fetch();
        }

        public function check_pagseguro_package($id){
            $stmt = $this->website->db('web')->prepare('SELECT id FROM DmN_PagSeguro_Packages WHERE id = :id');
            $stmt->execute([':id' => $id]);
            return $stmt->fetch();
        }

        public function check_paycall_package($id){
            $stmt = $this->website->db('web')->prepare('SELECT id FROM DmN_Donate_PayCall_Packages WHERE id = :id');
            $stmt->execute([':id' => $id]);
            return $stmt->fetch();
        }

        public function check_interkassa_package($id){
            $stmt = $this->website->db('web')->prepare('SELECT id FROM DmN_Donate_Interkassa_Packages WHERE id = :id');
            $stmt->execute([':id' => $id]);
            return $stmt->fetch();
        }

        public function check_cuenta_digital_package($id){
            $stmt = $this->website->db('web')->prepare('SELECT id FROM DmN_Donate_CuentaDigital_Packages WHERE id = :id');
            $stmt->execute([':id' => $id]);
            return $stmt->fetch();
        }

        public function edit_twocheckout_package($id, $title, $price, $currency, $reward, $server){
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_2CheckOut_Packages SET package = :title, reward = :reward, price = :price, currency = :currency, server = :server WHERE id = :id');
            $stmt->execute([':title' => $title, ':reward' => $reward, ':price' => $price, ':currency' => $currency, ':server' => $server, ':id' => $id]);
        }

        public function edit_pagseguro_package($id, $title, $price, $currency, $reward, $server){
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_PagSeguro_Packages SET package = :title, reward = :reward, price = :price, currency = :currency, server = :server WHERE id = :id');
            $stmt->execute([':title' => $title, ':reward' => $reward, ':price' => $price, ':currency' => $currency, ':server' => $server, ':id' => $id]);
        }

        public function edit_paycall_package($id, $title, $price, $reward, $server){
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_Donate_PayCall_Packages SET package = :title, reward = :reward, price = :price, server = :server WHERE id = :id');
            $stmt->execute([':title' => $title, ':reward' => $reward, ':price' => $price, ':server' => $server, ':id' => $id]);
        }

        public function edit_interkassa_package($id, $title, $price, $currency, $reward, $server){
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_Donate_Interkassa_Packages SET package = :title, reward = :reward, price = :price, currency = :currency, server = :server WHERE id = :id');
            $stmt->execute([':title' => $title, ':reward' => $reward, ':price' => $price, ':currency' => $currency, ':server' => $server, ':id' => $id]);
        }

        public function edit_cuenta_digital_package($id, $title, $price, $currency, $reward, $server){
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_Donate_CuentaDigital_Packages SET package = :title, reward = :reward, price = :price, currency = :currency, server = :server WHERE id = :id');
            $stmt->execute([':title' => $title, ':reward' => $reward, ':price' => $price, ':currency' => $currency, ':server' => $server, ':id' => $id]);
        }

        public function delete_twocheckout_package($id){
            $stmt = $this->website->db('web')->prepare('DELETE FROM DmN_2CheckOut_Packages WHERE id = :id');
            $stmt->execute([':id' => $id]);
        }

        public function delete_pagseguro_package($id){
            $stmt = $this->website->db('web')->prepare('DELETE FROM DmN_PagSeguro_Packages WHERE id = :id');
            $stmt->execute([':id' => $id]);
        }

        public function delete_paycall_package($id){
            $stmt = $this->website->db('web')->prepare('DELETE FROM DmN_Donate_PayCall_Packages WHERE id = :id');
            $stmt->execute([':id' => $id]);
        }

        public function delete_interkassa_package($id){
            $stmt = $this->website->db('web')->prepare('DELETE FROM DmN_Donate_Interkassa_Packages WHERE id = :id');
            $stmt->execute([':id' => $id]);
        }

        public function delete_cuenta_digital_package($id){
            $stmt = $this->website->db('web')->prepare('DELETE FROM DmN_Donate_CuentaDigital_Packages WHERE id = :id');
            $stmt->execute([':id' => $id]);
        }

        public function change_twocheckout_status($id, $status){
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_2CheckOut_Packages SET status = :status WHERE id = :id');
            $stmt->execute([':status' => $status, ':id' => $id]);
        }

        public function change_pagseguro_status($id, $status){
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_PagSeguro_Packages SET status = :status WHERE id = :id');
            $stmt->execute([':status' => $status, ':id' => $id]);
        }

        public function change_paycall_status($id, $status){
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_Donate_PayCall_Packages SET status = :status WHERE id = :id');
            $stmt->execute([':status' => $status, ':id' => $id]);
        }

        public function change_interkassa_status($id, $status){
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_Donate_Interkassa_Packages SET status = :status WHERE id = :id');
            $stmt->execute([':status' => $status, ':id' => $id]);
        }

        public function change_cuenta_digital_status($id, $status){
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_Donate_CuentaDigital_Packages SET status = :status WHERE id = :id');
            $stmt->execute([':status' => $status, ':id' => $id]);
        }

        public function add_twocheckout_package($title, $price, $currency, $reward, $server){
            $max_orders = $this->website->db('web')->query('SELECT ISNULL(MAX(orders), 0) AS max_orders FROM DmN_2CheckOut_Packages')->fetch();
            $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_2CheckOut_Packages (package, reward, price, currency, orders, status, server) VALUES (:title, :reward, :price, :currency, :count, 1, :server)');
            $stmt->execute([':title' => $title, ':reward' => $reward, ':price' => $price, ':currency' => $currency, ':count' => $max_orders['max_orders'], ':server' => $server]);
            return $this->website->db('web')->last_insert_id();
        }

        public function add_pagseguro_package($title, $price, $currency, $reward, $server){
            $max_orders = $this->website->db('web')->query('SELECT ISNULL(MAX(orders), 0) AS max_orders FROM DmN_PagSeguro_Packages')->fetch();
            $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_PagSeguro_Packages (package, reward, price, currency, orders, status, server) VALUES (:title, :reward, :price, :currency, :count, 1, :server)');
            $stmt->execute([':title' => $title, ':reward' => $reward, ':price' => $price, ':currency' => $currency, ':count' => $max_orders['max_orders'], ':server' => $server]);
            return $this->website->db('web')->last_insert_id();
        }

        public function add_paycall_package($title, $price, $reward, $server){
            $max_orders = $this->website->db('web')->query('SELECT ISNULL(MAX(orders), 0) AS max_orders FROM DmN_Donate_PayCall_Packages')->fetch();
            $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Donate_PayCall_Packages (package, reward, price, orders, status, server) VALUES (:title, :reward, :price, :count, 1, :server)');
            $stmt->execute([':title' => $title, ':reward' => $reward, ':price' => $price, ':count' => $max_orders['max_orders'], ':server' => $server]);
            return $this->website->db('web')->last_insert_id();
        }

        public function add_interkassa_package($title, $price, $currency, $reward, $server){
            $max_orders = $this->website->db('web')->query('SELECT ISNULL(MAX(orders), 0) AS max_orders FROM DmN_Donate_Interkassa_Packages')->fetch();
            $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Donate_Interkassa_Packages (package, reward, price, currency, orders, status, server) VALUES (:title, :reward, :price, :currency, :count, 1, :server)');
            $stmt->execute([':title' => $title, ':reward' => $reward, ':price' => $price, ':currency' => $currency, ':count' => $max_orders['max_orders'], ':server' => $server]);
            return $this->website->db('web')->last_insert_id();
        }

        public function add_cuenta_digital_package($title, $price, $currency, $reward, $server){
            $max_orders = $this->website->db('web')->query('SELECT ISNULL(MAX(orders), 0) AS max_orders FROM DmN_Donate_CuentaDigital_Packages')->fetch();
            $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Donate_CuentaDigital_Packages (package, reward, price, currency, orders, status, server) VALUES (:title, :reward, :price, :currency, :count, 1, :server)');
            $stmt->execute([':title' => $title, ':reward' => $reward, ':price' => $price, ':currency' => $currency, ':count' => $max_orders['max_orders'], ':server' => $server]);
            return $this->website->db('web')->last_insert_id();
        }

        public function load_paycall_packages(){
            return $this->website->db('web')->query('SELECT id, package, reward, price, orders, status, server FROM DmN_Donate_PayCall_Packages ORDER BY orders ASC')->fetch_all();
        }

        public function load_interkassa_packages(){
            return $this->website->db('web')->query('SELECT id, package, reward, price, currency, orders, status, server FROM DmN_Donate_Interkassa_Packages ORDER BY orders ASC')->fetch_all();
        }

        public function load_cuenta_digital_packages(){
            return $this->website->db('web')->query('SELECT id, package, reward, price, currency, orders, status, server FROM DmN_Donate_CuentaDigital_Packages ORDER BY orders ASC')->fetch_all();
        }

        public function check_referral_reward($req_lvl, $req_res, $req_gres, $reward_type, $server){
            $stmt = $this->website->db('web')->prepare('SELECT id FROM DmN_Refferal_Reward_List WHERE required_lvl = :required_lvl AND required_res = :required_res AND required_gres = :required_gres AND reward_type = :reward_type AND server = :server');
            $stmt->execute([':required_lvl' => $req_lvl, ':required_res' => $req_res, ':required_gres' => $req_gres, ':reward_type' => $reward_type, ':server' => $server]);
            return $stmt->fetch();
        }

        public function add_referral_reward($req_lvl, $req_res, $req_gres, $reward, $reward_type, $server){
            $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Refferal_Reward_List (required_lvl, required_res, required_gres, reward, reward_type, server, status) VALUES (:required_lvl, :required_res, :required_gres, :reward, :reward_type, :server, 1)');
            $stmt->execute([':required_lvl' => $req_lvl, ':required_res' => $req_res, ':required_gres' => $req_gres, ':reward' => $reward, ':reward_type' => $reward_type, ':server' => $server]);
            return $this->website->db('web')->last_insert_id();
        }

        public function check_referral_reward_status($id){
            $stmt = $this->website->db('web')->prepare('SELECT id FROM DmN_Refferal_Reward_List WHERE id = :id');
            $stmt->execute([':id' => $id]);
            return $stmt->fetch();
        }

        public function delete_referral_reward($id){
            $stmt = $this->website->db('web')->prepare('DELETE FROM DmN_Refferal_Reward_List WHERE id = :id');
            $stmt->execute([':id' => $id]);
        }

        public function change_referral_reward_status($id, $status){
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_Refferal_Reward_List SET status = :status WHERE id = :id');
            $stmt->execute([':status' => $status, ':id' => $id]);
        }

        public function load_vip_packages(){
            return $this->website->db('web')->query('SELECT id, package_title, price, payment_type, server, status, vip_time, is_registration_package FROM DmN_Vip_Packages ORDER BY id ASC')->fetch_all();
        }

        public function check_vip_status($id){
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
											  ,[is_registration_package]
											  ,[allow_extend] FROM DmN_Vip_Packages WHERE id = :id ORDER By vip_time ASC, server DESC');
            $stmt->execute([':id' => $id]);
            return $stmt->fetch();
        }

        public function remove_old_vip_registration_package($server){
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_Vip_Packages SET is_registration_package = 0 WHERE server = :server');
            $stmt->execute([':server' => $server]);
        }

        public function remove_vip_registration_package($id, $server){
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_Vip_Packages SET is_registration_package = 0 WHERE id = :id AND server = :server');
            $stmt->execute([':id' => $id, ':server' => $server]);
        }

        public function add_new_vip_registration_package($id, $server){
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_Vip_Packages SET is_registration_package = 1 WHERE id = :id AND server = :server');
            $stmt->execute([':id' => $id, ':server' => $server]);
        }

        public function check_vip_package_title($title){
            $stmt = $this->website->db('web')->prepare('SELECT id FROM DmN_Vip_Packages WHERE package_title = :title');
            $stmt->execute([':title' => $title]);
            return $stmt->fetch();
        }

        public function check_vip_package_title_for_edit($title, $id){
            $stmt = $this->website->db('web')->prepare('SELECT id FROM DmN_Vip_Packages WHERE id != :id AND package_title = :title');
            $stmt->execute([':id' => $id, ':title' => $title]);
            return $stmt->fetch();
        }

		public function add_vip_package($title, $price, $payment_type, $server, $time, $time_type, $allow_extend, $reset_price_decrease, $reset_level_decrease, $reset_bonus_points, $grand_reset_bonus_credits, $grand_reset_bonus_gcredits, $hide_info_discount, $pk_clear_discount, $clear_skilltree_discount, $online_hour_exchange_bonus, $change_name_discount, $change_class_discount, $bonus_credits_for_donate, $shop_discount, $wcoins, $server_vip_package, $server_bonus_info, $connect_member_load){
            if($time_type == 1){
                $time_calculated = ($time * (3600 * 24));
            }
            if($time_type == 2){
                $time_calculated = ($time * ((3600 * 24) * 7));
            }
            if($time_type == 3){
                $time_calculated = ($time * ((3600 * 24) * 30));
            }
            if($time_type == 4){
                $time_calculated = ($time * ((3600 * 24) * 365));
            }
            $variables = [':package_title', ':price', ':payment_type', ':server', ':vip_time', ':reset_price_decrease', ':reset_level_decrease', ':reset_bonus_points', ':grand_reset_bonus_credits', ':grand_reset_bonus_gcredits', ':hide_info_discount', ':pk_clear_discount', ':clear_skilltree_discount', ':online_hour_exchange_bonus', ':change_name_discount', ':change_class_discount', ':bonus_credits_for_donate', ':shop_discount', ':wcoins', ':connect_member_load', ':server_vip_package', ':server_bonus_info', ':allow_extend'];

			if($connect_member_load == ''){
                unset($variables[19]);
            }
            if($server_vip_package == ''){
                unset($variables[20]);
            }
            if($server_bonus_info == ''){
                unset($variables[21]);
            }
            $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Vip_Packages (' . str_replace(':', '', implode(', ', $variables)) . ') VALUES (' . implode(', ', $variables) . ')');
            $data = [':package_title' => $title, ':price' => $price, ':payment_type' => $payment_type, ':server' => $server, ':vip_time' => $time_calculated, ':reset_price_decrease' => $reset_price_decrease, ':reset_level_decrease' => $reset_level_decrease, ':reset_bonus_points' => $reset_bonus_points, ':grand_reset_bonus_credits' => $grand_reset_bonus_credits, ':grand_reset_bonus_gcredits' => $grand_reset_bonus_gcredits, ':hide_info_discount' => $hide_info_discount, ':pk_clear_discount' => $pk_clear_discount, ':clear_skilltree_discount' => $clear_skilltree_discount, ':online_hour_exchange_bonus' => $online_hour_exchange_bonus, ':change_name_discount' => $change_name_discount, ':change_class_discount' => $change_class_discount, ':bonus_credits_for_donate' => $bonus_credits_for_donate, ':shop_discount' => $shop_discount, ':wcoins' => $wcoins];
            if($connect_member_load != ''){
                $data[':connect_member_load'] = $connect_member_load;
            }
            if($server_vip_package != ''){
                $data[':server_vip_package'] = $server_vip_package;
            }
            if($server_bonus_info != ''){
                $data[':server_bonus_info'] = $server_bonus_info;
            }
			$data[':allow_extend'] = $allow_extend;
            return $stmt->execute($data);
        }

		public function edit_vip_package($id, $title, $price, $payment_type, $server, $time, $time_type, $allow_extend, $reset_price_decrease, $reset_level_decrease, $reset_bonus_points, $grand_reset_bonus_credits, $grand_reset_bonus_gcredits, $hide_info_discount, $pk_clear_discount, $clear_skilltree_discount, $online_hour_exchange_bonus, $change_name_discount, $change_class_discount, $bonus_credits_for_donate, $shop_discount, $wcoins, $server_vip_package, $server_bonus_info, $connect_member_load){
            if($time_type == 1){
                $time_calculated = ($time * (3600 * 24));
            }
            if($time_type == 2){
                $time_calculated = ($time * ((3600 * 24) * 7));
            }
            if($time_type == 3){
                $time_calculated = ($time * ((3600 * 24) * 30));
            }
            if($time_type == 4){
                $time_calculated = ($time * ((3600 * 24) * 365));
            }
            $variables = [':package_title', ':price', ':payment_type', ':server', ':vip_time', ':reset_price_decrease', ':reset_level_decrease', ':reset_bonus_points', ':grand_reset_bonus_credits', ':grand_reset_bonus_gcredits', ':hide_info_discount', ':pk_clear_discount', ':clear_skilltree_discount', ':online_hour_exchange_bonus', ':change_name_discount', ':change_class_discount', ':bonus_credits_for_donate', ':shop_discount', ':wcoins', ':connect_member_load', ':server_vip_package', ':server_bonus_info', ':allow_extend'];
            $query = 'UPDATE DmN_Vip_Packages SET ';
            foreach($variables AS $value){
                $query .= str_replace(':', '', $value) . ' = ' . $value . ', ';
            }
            $query = rtrim($query, ', ');
            $query .= ' WHERE id = :id';
            $stmt = $this->website->db('web')->prepare($query);
            $data = [':package_title' => $title, ':price' => $price, ':payment_type' => $payment_type, ':server' => $server, ':vip_time' => $time_calculated, ':reset_price_decrease' => $reset_price_decrease, ':reset_level_decrease' => $reset_level_decrease, ':reset_bonus_points' => $reset_bonus_points, ':grand_reset_bonus_credits' => $grand_reset_bonus_credits, ':grand_reset_bonus_gcredits' => $grand_reset_bonus_gcredits, ':hide_info_discount' => $hide_info_discount, ':pk_clear_discount' => $pk_clear_discount, ':clear_skilltree_discount' => $clear_skilltree_discount, ':online_hour_exchange_bonus' => $online_hour_exchange_bonus, ':change_name_discount' => $change_name_discount, ':change_class_discount' => $change_class_discount, ':bonus_credits_for_donate' => $bonus_credits_for_donate, ':shop_discount' => $shop_discount, ':wcoins' => $wcoins];
            if($connect_member_load != ''){
                $data[':connect_member_load'] = $connect_member_load;
            } else{
                $data[':connect_member_load'] = '';
            }
            if($server_vip_package != ''){
                $data[':server_vip_package'] = $server_vip_package;
            } else{
                $data[':server_vip_package'] = '';
            }
            if($server_bonus_info != ''){
                $data[':server_bonus_info'] = $server_bonus_info;
            } else{
                $data[':server_bonus_info'] = '';
            }
			$data[':allow_extend'] = $allow_extend;
            $data[':id'] = $id;
            return $stmt->execute($data);
        }

        public function delete_vip_package($id){
            $stmt = $this->website->db('web')->prepare('DELETE FROM DmN_Vip_Packages WHERE id = :id');
            $stmt->execute([':id' => $id]);
        }

        public function change_vip_status($id, $status){
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_Vip_Packages SET status = :status WHERE id = :id');
            $stmt->execute([':status' => $status, ':id' => $id]);
        }

        public function xtremetop100_autoload_links($server){
            $query = $this->website->db('web')->query('SELECT id, votelink FROM DmN_Votereward WHERE server = '.$this->website->db('web')->escape($server).' ORDER BY id ASC');
            $links = '';
            while($row = $query->fetch()){
                if(preg_match('/\b(xtremetop\w+)\b/', $row['votelink'])){
                    $links .= ',' . $row['id'];
                }
            }
            return substr($links, 1);
        }

        public function check_status($acc, $search_acc, $server){
            if($search_acc){
                $stmt = $this->website->db('game', $server)->prepare('SELECT AccountId FROM Character WHERE Name = :name');
                $stmt->execute([':name' => $acc]);
                if($char_acc = $stmt->fetch()){
                    $acc = $char_acc['AccountId'];
                } else{
                    $acc = false;
                }
            }
            if($acc != false){
                $stmt = $this->website->db('account', $server)->prepare('SELECT ConnectStat FROM MEMB_STAT WHERE memb___id = :user');
                $stmt->execute([':user' => $acc]);
                if($status = $stmt->fetch()){
                    return ($status['ConnectStat'] == 0);
                }
            }
            return true;
        }

        public function check_item_exists(){
            $stmt = $this->website->db('web')->prepare('SELECT id FROM DmN_Shopp WHERE item_id = :id AND item_cat = :cat AND stick_level = :level');
            $stmt->execute([':id' => $this->vars['item_id'], ':cat' => $this->vars['item_cat'], ':level' => $this->vars['stick_level']]);
            return $stmt->fetch();
        }

		public function add_item(){
            $keys = [];
            $bind_params = [];
            $values = [];
            if(is_array($this->vars)){
                foreach($this->vars as $key => $value){
                    if(isset($this->vars[$key]) && (is_array($this->vars[$key]) ? count($this->vars[$key]) : strlen($this->vars[$key])) > 0){
                        if($key != 'add_item'){
                            array_push($keys, $key);
                            array_push($bind_params, ':' . $key);
                            if(is_array($value)){
                                $value = implode(',', $value);
                            }
                            $values[':' . $key] = $value;
                        }
                    }
                }
                $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Shopp (' . implode(', ', $keys) . ') VALUES (' . implode(', ', $bind_params) . ')');
                $stmt->execute($values);
            }
        }

		public function edit_item($id){
            if(is_array($this->vars)){
                $query = [];
                $values = [];
                foreach($this->vars as $key => $value){
                    if(isset($this->vars[$key]) && (is_array($this->vars[$key]) ? count($this->vars[$key]) : strlen($this->vars[$key])) > 0){
                        if($key != 'edit_item'){
                            array_push($query, $key . ' = :' . $key);
                            if(is_array($value)){
                                $value = implode(',', $value);
                            }
                            $values[':' . $key] = $value;
                        }
                    }
                }
                $values[':id'] = $id;
                $stmt = $this->website->db('web')->prepare('UPDATE DmN_Shopp SET ' . implode(', ', $query) . ' WHERE id = :id');
                $stmt->execute($values);
            }
        }

        public function load_item_list($page = 1, $per_page = 25, $category = ''){
            $category = ($category != '') ? 'WHERE item_cat = ' . $this->website->db('web')->escape((int)$category) : '';
            $items = $this->website->db('web')->query('SELECT id, item_id, original_item_cat, item_cat, name, price, stick_level FROM DmN_Shopp ' . $category . ' ORDER BY  item_cat ASC, item_id ASC')->fetch_all();
            $this->count_items = count($items);
            $this->items_sql = array_slice($items, (int)(($page - 1) * $per_page), $per_page);
            foreach($this->items_sql as $row){
                $this->items[] = ['id' => $row['id'], 'item_id' => $row['item_id'], 'item_cat' => $this->webshop->category_from_id($row['item_cat']), 'original_item_cat' => $this->webshop->category_from_id($row['original_item_cat']), 'name' => $row['name'], 'price' => $row['price'], 'stick_level' => $row['stick_level']];
            }
            return $this->items;
        }

        public function check_item($id){
            $stmt = $this->website->db('web')->prepare('SELECT id, item_id, item_cat, max_item_lvl, max_item_opt, exetype, name, price, luck, use_sockets, use_harmony, use_refinary, payment_type, original_item_cat, stick_level, allow_upgrade, upgrade_price FROM DmN_Shopp WHERE id = :id');
            $stmt->execute([':id' => $id]);
            return $stmt->fetch();
        }

        public function set_item_price($id, $price){
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_Shopp SET price = :price WHERE id = :id');
            return $stmt->execute([':price' => $price, ':id' => $id]);
        }

        public function delete_item($id){
            $stmt = $this->website->db('web')->prepare('DELETE FROM DmN_Shopp WHERE id = :id');
            return $stmt->execute([':id' => $id]);
        }

        public function load_custom_price_list(){
            $items = $this->website->db('web')->query('SELECT id, item_id, item_cat FROM DmN_Shop_Custom_Price_List')->fetch_all();
            foreach($items AS $item){
                $this->items[] = ['iid' => $item['id'], 'name' => $this->get_item_name($item['item_id'], $item['item_cat']), 'id' => $this->get_item_id($item['item_id'], $item['item_cat'])];
            }
            return $this->items;
        }

        private function get_item_name($id, $cat){
            $stmt = $this->website->db('web')->prepare('SELECT name FROM DmN_Shopp WHERE item_id = :id AND original_item_cat = :cat');
            $stmt->execute([':id' => $id, ':cat' => $cat]);
            $info = $stmt->fetch();
            return $info['name'];
        }

        private function get_item_id($id, $cat){
            $stmt = $this->website->db('web')->prepare('SELECT id FROM DmN_Shopp WHERE item_id = :id AND original_item_cat = :cat');
            $stmt->execute([':id' => $id, ':cat' => $cat]);
            $info = $stmt->fetch();
            return $info['id'];
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

        public function set_cutom_item_price($id, $cat, $prices, $price_info){
            if(!$price_info){
                return $this->add_to_price_list($id, $cat, $prices);
            } else{
                return $this->update_price_list($id, $cat, $prices);
            }
        }

        private function add_to_price_list($id, $cat, $prices){
            return $this->website->db('web')->query('INSERT INTO DmN_Shop_Custom_Price_List (item_id, item_cat, price) VALUES (' . $this->website->db('web')->escape($id) . ', ' . $this->website->db('web')->escape($cat) . ', '.$this->website->db('web')->escape($prices).')');
        }

        private function update_price_list($id, $cat, $prices){
            return $this->website->db('web')->query('UPDATE DmN_Shop_Custom_Price_List SET price = '.$this->website->db('web')->escape($prices).' WHERE item_id = ' . $this->website->db('web')->escape($id) . ' AND item_cat = ' . $this->website->db('web')->escape($cat) . '');
        }

        public function delete_from_price_list($id){
            $stmt = $this->website->db('web')->prepare('DELETE FROM DmN_Shop_Custom_Price_List WHERE id = :id');
            return $stmt->execute([':id' => $id]);
        }

        public function load_category_list(){
            $file_arr = file(APP_PATH . DS . 'data' . DS . 'shop' . DS . 'shop_cat_list.dmn');
            $categories = [];
            foreach($file_arr as $line){
                $cats = explode('|', $line);
                $categories[] = ['id' => $cats[0], 'name' => $cats[1], 'status' => $cats[3]];
            }
            return $categories;
        }

		public function edit_category_list(){
            $file_arr = file(APP_PATH . DS . 'data' . DS . 'shop' . DS . 'shop_cat_list.dmn');
            $file = fopen(APP_PATH . DS . 'data' . DS . 'shop' . DS . 'shop_cat_list.dmn', 'w');
            foreach($file_arr as $line){
                $cats = explode('|', $line);
                if($cats[0] == $this->vars['old_cat_id']){
                    fwrite($file, "" . $this->vars['cat_id'] . "|" . htmlspecialchars($this->vars['cat_name']) . "|" . $this->website->seo_string($this->vars['cat_name']) . "|" . $this->vars['cat_status'] . "|\n");
                } else{
                    fwrite($file, "" . $line . "");
                }
            }
            fclose($file);
        }

        public function cat_not_exists(){
            $file_arr = file(APP_PATH . DS . 'data' . DS . 'shop' . DS . 'shop_cat_list.dmn');
            foreach($file_arr as $line){
                $cats = explode('|', $line);
                if($cats[0] == $this->vars['cat_id']){
                    return false;
                    break;
                }
            }
            return true;
        }

		public function create_category_image_folder(){
            if(!is_dir(BASEDIR . 'assets' . DS . 'item_images' . DS . $this->vars['cat_id'])){
                if(!mkdir(BASEDIR . 'assets' . DS . 'item_images' . DS . $this->vars['cat_id'], 0777)){
                    return false;
                }
            }
            return true;
        }

		public function add_category(){
            $file = fopen(APP_PATH . DS . 'data' . DS . 'shop' . DS . 'shop_cat_list.dmn', 'a');
            fwrite($file, "" . $this->vars['cat_id'] . "|" . htmlspecialchars($this->vars['cat_name']) . "|" . $this->website->seo_string($this->vars['cat_name']) . "|1|\n");
            fclose($file);
        }

		public function delete_category_image_folder(){
            if(is_dir(BASEDIR . 'assets' . DS . 'item_images' . DS . $this->vars['cat_id'])){
                rmdir(BASEDIR . 'assets' . DS . 'item_images' . DS . $this->vars['cat_id']);
            }
        }

		public function delete_category(){
            $file_arr = file(APP_PATH . DS . 'data' . DS . 'shop' . DS . 'shop_cat_list.dmn');
            $file = fopen(APP_PATH . DS . 'data' . DS . 'shop' . DS . 'shop_cat_list.dmn', 'w');
            foreach($file_arr as $line){
                $cats = explode('|', $line);
                if($cats[0] == $this->vars['cat_id']){
                    fwrite($file, "");
                } else{
                    fwrite($file, "" . $line . "");
                }
            }
            fclose($file);
        }

		public function load_ancient_list(){
            $file_arr = file(APP_PATH . DS . 'data' . DS . 'shop' . DS . 'shop_anc_opt.dmn');
            $sets = [];
            foreach($file_arr as $line){
                $ancient = explode('|', $line);
                $set_types = (preg_match('/[-]/', $ancient[2])) ? explode('-', $ancient[2]) : $ancient[2];
                if(is_array($set_types)){
                    $typeA = $set_types[0];
                    $typeB = $set_types[1];
                } else{
                    $typeA = $set_types;
                    $typeB = '';
                }
                $sets[] = ['id' => $ancient[5], 'cat' => $this->webshop->load_cat_list(true, $ancient[0]), 'item_id' => $ancient[1], 'typeA' => $typeA, 'typeB' => $typeB, 'statusA' => $ancient[3], 'statusB' => $ancient[4]];
            }
            return $sets;
        }

		public function update_ancient_sets(){
            $typeAB = '';
            if(isset($this->vars['typeA']) && $this->vars['typeA'] != ''){
                $typeAB .= $this->vars['typeA'];
            }
            if(isset($this->vars['typeB']) && $this->vars['typeB'] != ''){
                $typeAB .= '-' . $this->vars['typeB'];
            }
            $this->vars['statusA'] = isset($this->vars['statusA']) ? $this->vars['statusA'] : 0;
            $this->vars['statusB'] = isset($this->vars['statusB']) ? $this->vars['statusB'] : 0;
            $file_arr = file(APP_PATH . DS . 'data' . DS . 'shop' . DS . 'shop_anc_opt.dmn');
            $file = fopen(APP_PATH . DS . 'data' . DS . 'shop' . DS . 'shop_anc_opt.dmn', 'w');
            foreach($file_arr as $line){
                $sets = explode('|', $line);
                if($sets[5] == $this->vars['set_id']){
                    fwrite($file, "" . $this->vars['set_cat'] . "|" . $this->vars['item_id'] . "|" . $typeAB . "|" . $this->vars['statusA'] . "|" . $this->vars['statusB'] . "|" . $this->vars['set_id'] . "|\n");
                } else{
                    fwrite($file, "" . $line . "");
                }
            }
            fclose($file);
        }

		public function add_ancient_set(){
            $file_arr = file(APP_PATH . DS . 'data' . DS . 'shop' . DS . 'shop_anc_opt.dmn');
            $next_line = (count($file_arr) + 1);
            $typeAB = '';
            if(isset($this->vars['typeA']) && $this->vars['typeA'] != ''){
                $typeAB .= $this->vars['typeA'];
            }
            if(isset($this->vars['typeB']) && $this->vars['typeB'] != ''){
                $typeAB .= '-' . $this->vars['typeB'];
            }
            $this->vars['statusA'] = isset($this->vars['statusA']) ? $this->vars['statusA'] : 0;
            $this->vars['statusB'] = isset($this->vars['statusB']) ? $this->vars['statusB'] : 0;
            $file = fopen(APP_PATH . DS . 'data' . DS . 'shop' . DS . 'shop_anc_opt.dmn', 'a');
            fwrite($file, "" . $this->vars['set_cat'] . "|" . $this->vars['item_id'] . "|" . $typeAB . "|" . $this->vars['statusA'] . "|" . $this->vars['statusB'] . "|" . $next_line . "|\n");
            fclose($file);
        }

        public function load_socket_list(){
            return $this->website->db('web')->query('SELECT id, socket_id, socket_name, socket_price, status, orders, socket_part_type FROM DmN_Shop_Sockets ORDER BY orders ASC')->fetch_all();
        }

        public function save_socket_order($orders){
            foreach($orders as $key => $value){
                $stmt = $this->website->db('web')->prepare('UPDATE DmN_Shop_Sockets SET orders = :order WHERE id = :id');
                $stmt->execute([':order' => $key, ':id' => $value]);
            }
        }

        public function check_socket($id){
            $stmt = $this->website->db('web')->prepare('SELECT id FROM DmN_Shop_Sockets WHERE id = :id');
            $stmt->execute([':id' => $id]);
            return $stmt->fetch();
        }

        public function change_socket_status($id, $status){
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_Shop_Sockets SET status = :status WHERE id = :id');
            $stmt->execute([':status' => $status, ':id' => $id]);
        }

        public function edit_socket($id, $name, $price, $part_type){
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_Shop_Sockets SET socket_name = :name, socket_price = :price, socket_part_type = :type WHERE id = :id');
            $stmt->execute([':name' => $name, ':price' => $price, ':type' => $part_type, ':id' => $id]);
        }

        public function load_harmony_list(){
            return $this->website->db('web')->query('SELECT id, hname, price, status FROM DmN_Shop_Harmony ORDER BY hoption ASC')->fetch_all();
        }

        public function check_harmony($id){
            $stmt = $this->website->db('web')->prepare('SELECT id FROM DmN_Shop_Harmony WHERE id = :id');
            $stmt->execute([':id' => $id]);
            return $stmt->fetch();
        }

        public function change_harmony_status($id, $status){
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_Shop_Harmony SET status = :status WHERE id = :id');
            $stmt->execute([':status' => $status, ':id' => $id]);
        }

        public function edit_harmony($id, $name, $price){
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_Shop_Harmony SET hname = :name, price = :price WHERE id = :id');
            $stmt->execute([':name' => $name, ':price' => $price, ':id' => $id]);
        }

		public function import_shop_items($items, $names, $prices, $slots, $category){
            foreach($items as $key => $value){
                switch($slots[$key]){
                    case -1:
                        $exetype = -1;
                        break;
                    case 0:
                    case 1:
                        $exetype = 1;
                        break;
                    case 2:
                    case 3:
                    case 4:
                    case 5:
                    case 6:
                        $exetype = 2;
                        break;
                    case 7:
                        $exetype = 3;
                        break;
                    case 8:
                        $exetype = -1;
                        break;
                    case 9:
                        $exetype = 1;
                        break;
                    case 10:
                    case 11:
                        $exetype = 5;
                        break;
                    default:
                        $exetype = -1;
                        break;
                }
                if($category == 6){
                    $exetype = 2;
                }
                if(($category == 13 && $key == 37)){
                    $exetype = 4;
                }
                if($category == 12 && (in_array($key, [36, 37, 38, 39, 43, 50]))){
                    $exetype = 6;
                }
                $luck = ($category < 12) ? 1 : 0;
                if($exetype == -1){
                    $max_it_lvl = 0;
                    $max_it_opt = 0;
                } else{
                    $max_it_lvl = 15;
                    $max_it_opt = 7;
                }
                if(!$this->check_item_in_db($key, $category)){
                    $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Shopp (item_id, item_cat, max_item_lvl, max_item_opt, exetype, name, price, luck, original_item_cat) VALUES (:id, :cat, :itlvl, :itopt, :exetype, :name, :price, :luck, :orig_cat)');
                    $stmt->execute([':id' => $key, ':cat' => $category, ':itlvl' => $max_it_lvl, ':itopt' => $max_it_opt, ':exetype' => $exetype, ':name' => $names[$key], ':price' => $prices[$key], ':luck' => $luck, ':orig_cat' => $category]);
                }
            }
        }

        private function check_item_in_db($id, $cat){
            $stmt = $this->website->db('web')->prepare('SELECT id FROM DmN_Shopp WHERE item_id = :id AND original_item_cat = :cat');
            $stmt->execute([':id' => $id, ':cat' => $cat]);
            return $stmt->fetch();
        }

        public function acc_exists($user, $server){
            $stmt = $this->website->db('account', $server)->prepare('SELECT memb_guid, memb___id FROM MEMB_INFO WHERE memb___id = :user');
            $stmt->execute([':user' => $user]);
            return $stmt->fetch();
        }

		public function add_account_log($log, $credits, $acc, $server){
            $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Account_Logs (text, amount, date, account, server, ip) VALUES (:text, :amount, GETDATE(), :acc, :server, :ip)');
            $stmt->execute([':text' => $log, ':amount' => round($credits), ':acc' => $acc, ':server' => $server, ':ip' => ip()]);
            $stmt->close_cursor();
        }
		
		
		public function add_total_recharge($account, $server, $credits)
		{
			if($this->website->db('web')->check_if_table_exists('DmN_Total_Recharge')){
				$this->insert_recharge($account, $server, $credits);
			}
		}
		
		
		private function insert_recharge($account, $server, $credits){
            $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Total_Recharge (account, server, points, date) VALUES (:account, :server, :points, GETDATE())');
            $stmt->execute([':account' => $account, ':server' => $server, ':points' => $credits]);
        }
		
        public function search_similar_accounts($user, $server){
            $stmt = $this->website->db('account', $server)->prepare('SELECT memb___id FROM MEMB_INFO WHERE memb___id LIKE :user');
            $stmt->execute([':user' => '%' . $user . '%']);
            return $stmt->fetch_all();
        }

        public function add_vote_link(){
            $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Votereward (votelink, name, img_url, hours, reward, reward_type, mmotop_stats_url, mmotop_reward_sms, api, server) VALUES (:votelink, :name, :img_url, :hours, :reward, :reward_type, :mmotop_stats_url, :mmotop_reward_sms, :api, :server)');
            $stmt->execute([':votelink' => $this->vars['votelink'], ':name' => $this->vars['name'], ':img_url' => $this->vars['img_url'], ':hours' => $this->vars['hours'], ':reward' => $this->vars['reward'], ':reward_type' => $this->vars['reward_type'], ':mmotop_stats_url' => (isset($this->vars['mmotop_stats_url']) && $this->vars['mmotop_stats_url'] != '') ? $this->vars['mmotop_stats_url'] : '', ':mmotop_reward_sms' => (isset($this->vars['mmotop_reward_sms']) && $this->vars['mmotop_reward_sms'] != '') ? $this->vars['mmotop_reward_sms'] : 0, ':api' => $this->vars['voting_api'], ':server' => $this->vars['server']]);
        }

        public function edit_vote_link($id){
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_Votereward SET votelink = :votelink, name = :name, img_url = :img_url, hours = :hours, reward = :reward, reward_type = :reward_type, mmotop_stats_url = :mmotop_stats_url, mmotop_reward_sms = :mmotop_reward_sms, api = :api, server = :server WHERE id = :id');
            $stmt->execute([':votelink' => $this->vars['votelink'], ':name' => $this->vars['name'], ':img_url' => $this->vars['img_url'], ':hours' => $this->vars['hours'], ':reward' => $this->vars['reward'], ':reward_type' => $this->vars['reward_type'], ':mmotop_stats_url' => (isset($this->vars['mmotop_stats_url']) && $this->vars['mmotop_stats_url'] != '') ? $this->vars['mmotop_stats_url'] : '', ':mmotop_reward_sms' => (isset($this->vars['mmotop_reward_sms']) && $this->vars['mmotop_reward_sms'] != '') ? $this->vars['mmotop_reward_sms'] : 0, ':api' => $this->vars['voting_api'], ':server' => $this->vars['server'], ':id' => $id]);
        }

        public function load_vote_links(){
            $stmt = $this->website->db('web')->prepare('SELECT id, votelink, name, img_url, hours, reward, reward_type, mmotop_stats_url, mmotop_reward_sms, api, server FROM DmN_Votereward');
            $stmt->execute();
            return $stmt->fetch_all();
        }

        public function voting_link_exists($id){
            $stmt = $this->website->db('web')->prepare('SELECT id, votelink, name, img_url, hours, reward, reward_type, mmotop_stats_url, mmotop_reward_sms, api, server FROM DmN_Votereward WHERE id = :id');
            $stmt->execute([':id' => $id]);
            return ($this->vote_link_info = $stmt->fetch()) ? true : false;
        }

        public function delete_voting_link($id){
            $stmt = $this->website->db('web')->prepare('DELETE FROM DmN_Votereward WHERE id = :id');
            $stmt->execute([':id' => $id]);
        }

        public function load_items_for_select($cat = ''){
            $stmt = $this->website->db('web')->prepare('SELECT id, name FROM DmN_Shopp WHERE item_cat = :cat ORDER BY item_id ASC');
            $stmt->execute([':cat' => $cat]);
            return $stmt->fetch_all();
        }

        public function load_items_data($id = ''){
            $stmt = $this->website->db('web')->prepare('SELECT TOP 1 exetype, use_sockets FROM DmN_Shopp WHERE id = :id');
            $stmt->execute([':id' => $id]);
            return $stmt->fetch();
        }

        public function load_harmony_values($cat = 0, $hopt = 0){
            return $this->website->db('web')->query('SELECT hvalue, hname FROM DmN_Shop_Harmony WHERE itemtype = ' . $this->website->db('web')->escape($this->get_type($cat)) . ' AND hoption = ' . $this->website->db('web')->escape($hopt) . ' AND status = 1')->fetch_all();
        }

        private function get_type($cat){
            if($cat < 5)
                return 1; else if($cat == 5)
                return 2;
            else if($cat > 5)
                return 3;
            else
                return 1;
        }

		public function socket_list($use_sockets = 1, $check_part = 1, $exe_type = 1){
            $exe_type = ($exe_type == 1) ? 1 : 0;
            if($use_sockets == 1){
                if($check_part == 1){
                    $sockets = $this->website->db('web')->query('SELECT seed, socket_id, socket_name FROM DmN_Shop_Sockets WHERE status != 0  AND socket_part_type IN (-1, ' . $exe_type . ') ORDER BY orders ASC')->fetch_all();
                } else{
                    $sockets = $this->website->db('web')->query('SELECT seed, socket_id, socket_name FROM DmN_Shop_Sockets WHERE status != 0 ORDER BY orders ASC')->fetch_all();
                }
                return $sockets;
            }
            return false;
        }

		public function get_item_info($id = '', $server = ''){
            if($id == '')
                return false;
            $item = $this->website->db('web')->query('SELECT item_id, item_cat, exetype, name, luck, max_item_lvl, max_item_opt, use_sockets, use_harmony, use_refinary, original_item_cat, total_bought, stick_level FROM DmN_Shopp WHERE id = ' . $this->website->db('web')->escape($id))->fetch();
            if($item){
                $this->iteminfo->setItemData($item['item_id'], $item['original_item_cat'], (int)$this->website->get_value_from_server($server, 'item_size'));
                $item['data'] = $this->iteminfo->item_data;
                return $item;
            }
            return false;
        }

        public function check_harmony_data($use = 0, $harmony = []){
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

        public function load_refferal_reward_list(){
            return $this->website->db('web')->query('SELECT id, required_lvl, required_res, required_gres, reward, reward_type, server, status FROM DmN_Refferal_Reward_List ORDER BY id ASC')->fetch_all();
        }

        public function search_condition_account($string = ''){
            if($string != '')
                $this->sql_condition .= ' AND m.memb___id LIKE \'' . $string . '%\'';
        }

        public function search_condition_date_start($string = ''){
            if($string != ''){
                $this->sql_condition .= ' AND m.appl_days >= \'' . $string . '\'';
            }
        }

        public function search_condition_date_end($string = ''){
            if($string != '')
                $this->sql_condition .= ' AND m.appl_days <= \'' . $string . '\'';
        }

		public function search_condition_status($data = []){
            if(!empty($data)){
                if(in_array('activated', $data)){
                    $this->sql_condition .= ' AND m.activated = 1';
                }
                if(in_array('not_activated', $data)){
                    $this->sql_condition .= ' AND m.activated = 0';
                }
                if(in_array('blocked', $data)){
                    $this->sql_condition .= ' AND m.bloc_code = 1';
                }
                if(in_array('gm', $data)){
                    $this->sql_condition .= ' AND m.ctl1_code = 1';
                }
				if(in_array('vip', $data)){
                    $this->sql_condition .= ' AND d.viptime >= '.time().'';
                }
				if(in_array('partner', $data)){
                    $this->sql_condition .= ' AND m.dmn_partner = 1';
                }
            }
        }

		public function search_condition_country($data = []){
            if(!empty($data)){
                $list_with_quotes = implode(',', array_map('self::add_quotes', $data));
                $this->sql_condition .= ' AND m.dmn_country IN (' . $list_with_quotes . ')';
            }
        }

		private function add_quotes($str){
            return sprintf("'%s'", $str);
        }

		public function load_account_list($page = 1, $per_page = 25, $server = '', $order_column = 2, $order_dir = 'desc'){
            $dir = ($order_dir == 'desc') ? 'DESC' : 'ASC';
            switch($order_column){
                case 0:
                    $column = 'm.memb___id';
                    $column2 = 'memb___id';
                    break;
                case 1:
                    $column = 'm.appl_days';
                    $column2 = 'appl_days';
                    break;
                default:
                case 2:
                    $column = 'm.dmn_country';
                    $column2 = 'dmn_country';
                    break;
            }
            $condition2 = '';
            if($this->sql_condition != ''){
                $condition2 = 'WHERE ' . substr($this->sql_condition, 5);
            }
			$partner = '';
			if(defined('PARTNER_SYSTEM') && PARTNER_SYSTEM == true){
				$partner = 'm.dmn_partner,';
			}
			
			if($server != '' && defined('CUSTOM_SERVER_CODES')){
				if(array_key_exists($server, CUSTOM_SERVER_CODES)){
					if($this->sql_condition != ''){
						$condition2 .= ' AND m.servercode = '. CUSTOM_SERVER_CODES[$server];
						$this->sql_condition .= ' AND m.servercode = '. CUSTOM_SERVER_CODES[$server];
					}
					else{
						$condition2 = 'WHERE m.servercode = '. CUSTOM_SERVER_CODES[$server];
						$this->sql_condition .= ' AND m.servercode = '. CUSTOM_SERVER_CODES[$server];
					}
				}
			}		
            $accounts = $this->website->db('account', $server)->query('SELECT TOP ' . (int)$per_page . ' m.memb_guid, m.memb___id, m.appl_days, m.dmn_country, m.activated, '.$partner.' d.viptime FROM MEMB_INFO AS m LEFT JOIN ['.WEB_DB.'].dbo.DmN_Vip_Users AS d ON(m.memb___id Collate Database_Default = d.memb___id Collate Database_Default) WHERE m.memb_guid NOT IN (SELECT Top ' . $this->website->db('web')->escape((int)$page) . ' m.memb_guid FROM MEMB_INFO AS m ' . $condition2 . ' ORDER BY ' . $column . ' ' . $dir . ') ' . $this->sql_condition . ' ORDER BY ' . $column . ' ' . $dir . '');
            foreach($accounts->fetch_all() as $row){
                $this->accounts[] = ['id' => $row['memb_guid'], 'memb___id' => htmlspecialchars($row['memb___id']), 'reg_date' => $row['appl_days'], 'country' => $this->website->codeToCountryName($row['dmn_country']), 'server' => $server, 'activated' => $row['activated']];
            }
            return $this->accounts;
        }

        public function load_char_list($page, $per_page, $server){
																			 
            $pos = ($page == 1) ? 1 : (int)(($page - 1) * $per_page) + 1;
            $accounts = $this->website->db('game', $server)->query('SELECT TOP ' . $per_page . ' AccountId, Name, '.$this->website->get_char_id_col($server).' FROM Character WHERE '.$this->website->get_char_id_col($server).' NOT IN (SELECT Top ' . $this->website->db('web')->escape($pos) . ' '.$this->website->get_char_id_col($server).' FROM Character ORDER BY Name ASC)  ORDER BY Name ASC');
            foreach($accounts->fetch_all() as $row){
                $this->chars[] = ['id' => $row[$this->website->get_char_id_col($server)], 'name' => htmlspecialchars($row['Name']), 'account' => htmlspecialchars($row['AccountId'])];
                $pos++;
            }
            return $this->chars;
        }

        public function search_account_list($account, $server){
            $stmt = $this->website->db('account', $server)->query('SELECT memb_guid, memb___id, appl_days FROM MEMB_INFO WHERE memb___id LIKE \'' . $this->website->db('account', $server)->escape($account) . '%\' ORDER BY appl_days DESC');
            foreach($stmt->fetch_all() as $row){
                $this->accounts[] = ['id' => (int)$row['memb_guid'], 'memb___id' => htmlspecialchars($row['memb___id']), 'reg_date' => $row['appl_days']];
            }
            return $this->accounts;
        }

		public function search_char_list($name, $server){
            $stmt = $this->website->db('game', $server)->prepare('SELECT AccountId, Name, '.$this->website->get_char_id_col($server).' AS id FROM Character WHERE Name LIKE :name ORDER BY Name ASC');
            $stmt->execute([':name' => $name . '%']);
            foreach($stmt->fetch_all() as $row){
				$status = $this->checkStatus($row['AccountId'], $server);
				if(!$status){
					$connectStat = 0;
					$cntrCode = 'us';
					$gs = '';
				}
				else{
					$connectStat = ($status['ConnectStat'] == 1) ? 1 : 0;
					$cntrCode = $this->website->get_country_code($status['IP']);
					$gs = $status['ServerName'];
				}
                $this->chars[] = [
					'id' => (int)$row['id'], 
					'name' => htmlspecialchars($row['Name']), 
					'account' => htmlspecialchars($row['AccountId']),
					'status' => $connectStat,
					'country' => $cntrCode,
					'gs' => $gs
				];
            }
            return $this->chars;
        }
		
		private function checkStatus($acc, $server){
			$stmt = $this->website->db('account', $server)->prepare('SELECT ConnectStat, IP, ServerName FROM MEMB_STAT WHERE memb___id = :user');
			$stmt->execute([':user' => $acc]);
			if($status = $stmt->fetch()){
				return $status;
			}
			return false;
        }

        public function count_total_accounts($filtered, $server){
            $condition = '';
            if($this->sql_condition != '' && $filtered == true){
                $condition = 'WHERE ' . substr($this->sql_condition, 4);
            }
            $count = $this->website->db('account', $server)->snumrows('SELECT COUNT(m.memb___id) AS count FROM MEMB_INFO AS m LEFT JOIN ['.WEB_DB.'].dbo.DmN_Vip_Users AS d ON(m.memb___id Collate Database_Default = d.memb___id Collate Database_Default) ' . $condition . '');
            return $count;
        }

        public function count_total_chars($server){
            $count = $this->website->db('game', $server)->snumrows('SELECT COUNT(Name) AS count FROM Character');
            return $count;
        }

        public function get_account_data($id, $server){
            $stmt = $this->website->db('account', $server)->prepare('SELECT memb___id, memb__pwd, sno__numb, mail_addr, bloc_code, activated FROM MEMB_INFO WHERE memb_guid = :id');
            $stmt->execute([':id' => $id]);
            return $stmt->fetch();
        }
		
		public function get_account_data_by_username($acc, $server){
            $stmt = $this->website->db('account', $server)->prepare('SELECT memb___id, memb__pwd, sno__numb, mail_addr, bloc_code, activated FROM MEMB_INFO WHERE memb___id = :acc');
            $stmt->execute([':acc' => $acc]);
            return $stmt->fetch();
        }
		
		public function countPurchasesReffered($account, $server){
			return $this->website->db('web')->snumrows('SELECT COUNT(id) AS count FROM DmN_Partner_Share_Log WHERE partner = '.$this->website->db('web')->escape($account).' AND server = '.$this->website->db('web')->escape($server).'');
		}
		
		public function totalAmountShares($account, $server){
			return $this->website->db('web')->snumrows('SELECT SUM(fullAmount) AS count FROM DmN_Partner_Share_Log WHERE partner = '.$this->website->db('web')->escape($account).' AND server = '.$this->website->db('web')->escape($server).'');
		}
		
		public function earnedAmountShares($account, $server){
			return $this->website->db('web')->snumrows('SELECT SUM(amount) AS count FROM DmN_Partner_Share_Log WHERE partner = '.$this->website->db('web')->escape($account).' AND server = '.$this->website->db('web')->escape($server).'');
		}
		
		public function accountsReferred($account, $server){
			return $this->website->db('account', $server)->snumrows('SELECT count(memb___id) AS count FROM MEMB_INFO WHERE dmn_linked_to = '.$this->website->db('web')->escape($account).'');
		}
		
		public function findStreamLog($account){
			return $this->website->db('web')->query('SELECT TOP 10 SUM(stream_time) AS time, day FROM DmN_Partner_Stream_Log  WHERE username = '.$this->website->db('web')->escape($account).' GROUP BY [day] ORDER By day DESC')->fetch_all();
		}
		
		public function get_account_data_for_partner($acc, $server){
            $stmt = $this->website->db('account', $server)->prepare('SELECT dmn_partner, dmn_twitch_link, dmn_twitch_tags, dmn_youtube_link, dmn_daily_coins, dmn_daily_coins_type, dmn_purchases_share, dmn_share_url, dmn_current_share FROM MEMB_INFO WHERE memb___id = :acc');
            $stmt->execute([':acc' => $acc]);
            return $stmt->fetch();
        }

				
		public function update_partner_data($account, $partner, $twitch, $ttags, $youtube, $daily_coins, $daily_coins_type, $purchase_share, $share_url, $server){
			$stmt = $this->website->db('account', $server)->prepare('UPDATE MEMB_INFO SET dmn_partner = :partner, dmn_twitch_link = :twitch, dmn_twitch_tags = :ttags, dmn_youtube_link = :youtube, dmn_daily_coins = :daily_coins, dmn_daily_coins_type = :coins_type, dmn_purchases_share = :purchase_share, dmn_share_url = :share_url WHERE memb___id = :acc');
			$stmt->execute([
				':partner' => $partner,
				':twitch' => $twitch,
                ':ttags' => $ttags,
                ':youtube' => $youtube,
				':daily_coins' => $daily_coins,
				':coins_type' => $daily_coins_type,
				':purchase_share' => $purchase_share,
				':share_url' => $share_url,
				':acc' => $account
			]);
		}

        public function activate_account($id, $server){
            $stmt = $this->website->db('account', $server)->prepare('UPDATE MEMB_INFO SET activated = 1  WHERE memb_guid = :id');
            return $stmt->execute([':id' => $id]);
        }

        public function get_character_data($id, $server){
            $res = ', ' . $this->config->values('table_config', [$server, 'resets', 'column']);
            $gr = ', ' . $this->config->values('table_config', [$server, 'grand_resets', 'column']);
            $leadership = (MU_VERSION < 1) ? '0 AS Leadership' : 'Leadership';
            $stmt = $this->website->db('game', $server)->prepare('SELECT AccountId, Name, cLevel, LevelUpPoint, Class, Experience, Strength, Dexterity, Vitality, Energy, Money, MapNumber, MapPosX, MapPosY, PkCount, PkLevel, PkTime, CtlCode, ' . $this->reset_column($server) . $this->greset_column($server) . ' '.$leadership.' FROM Character WHERE '.$this->website->get_char_id_col($server).' = :id');
            $stmt->execute([':id' => $id]);
            return $stmt->fetch();
        }


        private function reset_column($server = '', $for_update = false){
            $resets = $this->config->values('table_config', [$server, 'resets', 'column']);
            if($for_update){
                if($resets && $resets != ''){
                    return ', ' . $resets . ' = :resets';
                }
                return '';
            } else{
                if($resets && $resets != ''){
                    return $resets . ' AS resets,';
                }
                return '0 AS resets,';
            }
        }

        private function greset_column($server = '', $for_update = false){
            $grand_resets = $this->config->values('table_config', [$server, 'grand_resets', 'column']);
            if($for_update){
                if($grand_resets && $grand_resets != ''){
                    return ', ' . $grand_resets . ' = :gresets';
                }
                return '';
            } else{
                if($grand_resets && $grand_resets != ''){
                    return $grand_resets . ' AS grand_resets,';
                }
                return '0 AS grand_resets,';
            }
        }

        public function update_character($id, $server){
            $res = $this->reset_column($server, true);
            $gres = $this->greset_column($server, true);
            $stmt = $this->website->db('game', $server)->prepare('UPDATE Character SET cLevel = :clevel, LevelUpPoint = :leveluppoint, Class = :class, Experience = :experience, Strength = :strength, Dexterity = :dexterity, Vitality = :vitality, Energy = :energy, Money = :money,  MapNumber = :mapnumber, MapPosX = :mapposx, MapPosY = :mapposy, PkCount = :pkcount, PkLevel = :pklevel, PkTime = :pktime, CtlCode = :ctlcode, Leadership = :leadership' . $res . $gres . ' WHERE '.$this->website->get_char_id_col($server).' = :id');
            $data = [':clevel' => $this->vars['cLevel'], ':leveluppoint' => $this->vars['LevelUpPoint'], ':class' => $this->vars['Class'], ':experience' => $this->vars['Experience'], ':strength' => $this->vars['Strength'], ':dexterity' => $this->vars['Dexterity'], ':vitality' => $this->vars['Vitality'], ':energy' => $this->vars['Energy'], ':money' => $this->vars['Money'], ':mapnumber' => $this->vars['MapNumber'], ':mapposx' => $this->vars['MapPosX'], ':mapposy' => $this->vars['MapPosY'], ':pkcount' => $this->vars['PkCount'], ':pklevel' => $this->vars['PkLevel'], ':pktime' => $this->vars['PkTime'], ':ctlcode' => $this->vars['CtlCode'], ':leadership' => isset($this->vars['Leadership']) ? $this->vars['Leadership'] : 0];
            if($res != '')
                $data[':resets'] = $this->vars['resets'];
            if($gres != '')
                $data[':gresets'] = $this->vars['grand_resets'];
            $data[':id'] = $id;
            return $stmt->execute($data);
        }

        public function get_ip_logs($account){
            $stmt = $this->website->db('web')->prepare('SELECT ip, last_connected, login_type FROM DmN_IP_Log WHERE account = :account ORDER BY last_connected DESC');
            $stmt->execute([':account' => $account]);
            return $stmt->fetch_all();
        }

        public function get_char_list($account, $id = -1, $server = null){
            $sql = ($id != -1) ? [' AND '.$this->website->get_char_id_col($server).' != :id', [':account' => $account, ':id' => $id]] : ['', [':account' => $account]];
            $stmt = $this->website->db('game', $server)->prepare('SELECT '.$this->website->get_char_id_col($server).' AS id, Name FROM Character WHERE AccountId = :account' . $sql[0] . '');
            $stmt->execute($sql[1]);
            return $stmt->fetch_all();
        }

		public function update_account_info($id, $pass, $email, $sno_numb, $server){
            $update_pw = true;
            if(MD5 == 1){
                if($pass != ''){
                    $user = $this->get_account_username($id, $server);
                    $prepare = $this->website->db('account', $server)->prepare('SET NOCOUNT ON;EXEC DmN_Check_Acc_MD5 :user, :pass');
                    $prepare->execute([':user' => $user, ':pass' => $pass]);
                    $pw = $prepare->fetch();
                    if($pw['result'] == 'found'){
                        $update_pw = false;
                    } else{
                        $pw = !$this->website->is_hex($pw['result']) ? '0x' . strtoupper(bin2hex($pw['result'])) : '0x' . $pw['result'];
                    }
                }
            }
            if(MD5 == 2){
                $pass = md5($pass);
            }
            if($pass != ''){
                if(MD5 == 1){
                    $pwd = 'memb__pwd = ' . $pw . ',';
                } else{
                    $pwd = 'memb__pwd = \'' . $pass . '\',';
                }
            } else{
                $pwd = '';
            }
            if($update_pw){
                $stmt = $this->website->db('account', $server)->prepare('UPDATE MEMB_INFO SET ' . $pwd . 'mail_addr = :mail, sno__numb = :numb WHERE memb_guid = :id');
            } else{
                $stmt = $this->website->db('account', $server)->prepare('UPDATE MEMB_INFO SET mail_addr = :mail, sno__numb = :numb WHERE memb_guid = :id');
            }
            return $stmt->execute([':mail' => $email, ':numb' => $sno_numb, ':id' => $id]);
        }

        private function get_account_username($id, $server){
            $stmt = $this->website->db('account', $server)->prepare('SELECT memb___id FROM MEMB_INFO WHERE memb_guid = :id');
            $stmt->execute([':id' => $id]);
            $user = $stmt->fetch();
            return $user['memb___id'];
        }

        public function get_account_by_ip($ip){
            $stmt = $this->website->db('web')->prepare('SELECT DISTINCT account, last_connected, login_type FROM DmN_IP_Log WHERE ip = :ip');
            $stmt->execute([':ip' => $ip]);
            return $stmt->fetch_all();
        }

        public function check_account($id, $server){
            $stmt = $this->website->db('account', $server)->prepare('SELECT memb___id, bloc_code FROM MEMB_INFO WHERE memb_guid = :id');
            $stmt->execute([':id' => $id]);
            return $stmt->fetch();
        }

        public function check_char($id, $server){
            $stmt = $this->website->db('game', $server)->prepare('SELECT Name, CtlCode FROM Character WHERE '.$this->website->get_char_id_col($server).' = :id');
            $stmt->execute([':id' => $id]);
            return $stmt->fetch();
        }

        public function check_banned_account($account, $server){
            $stmt = $this->website->db('account', $server)->prepare('SELECT memb___id FROM MEMB_INFO WHERE memb___id = :account AND bloc_code = 1');
            $stmt->execute([':account' => $account]);
            return $stmt->fetch();
        }

        public function check_banned_char($char, $server){
            $stmt = $this->website->db('game', $server)->prepare('SELECT Name FROM Character WHERE name = :char AND CtlCode = 1');
            $stmt->execute([':char' => $char]);
            return $stmt->fetch();
        }

        public function unban($name, $type, $server){
            if($type == 1){
                $this->set_bloc_code($name, 0, $server);
                $this->remove_from_ban_list($name, 1, $server);
            } else{
                $this->set_ctl_code($name, 0, $server);
                $this->remove_from_ban_list($name, 2, $server);
            }
        }

        private function set_bloc_code($name, $code, $server){
            $stmt = $this->website->db('account', $server)->prepare('UPDATE MEMB_INFO SET bloc_code = :code WHERE memb___id = :account');
            return $stmt->execute([':code' => $code, ':account' => $name]);
        }

        private function remove_from_ban_list($name, $type, $server){
            $stmt = $this->website->db('web')->prepare('DELETE FROM DmN_Ban_List WHERE name = :name AND type = :type AND server = :server');
            return $stmt->execute([':name' => $name, ':type' => $type, ':server' => $server]);
        }

        private function set_ctl_code($name, $code, $server){
            $stmt = $this->website->db('game', $server)->prepare('UPDATE Character SET CtlCode = :code WHERE Name = :name');
            return $stmt->execute([':code' => $code, ':name' => $name]);
        }

        public function delete_account($account, $server){
            $stmt = $this->website->db('account', $server)->prepare('DELETE FROM MEMB_INFO WHERE memb___id = :account');
            return $stmt->execute([':account' => $account]);
        }

        public function get_character_list($account, $server){
            $stmt = $this->website->db('game', $server)->prepare('SELECT Name FROM Character WHERE AccountId = :account');
            $stmt->execute([':account' => $account]);
            return $stmt->fetch_all();
        }

        public function delete_account_character($account, $server){
            $stmt = $this->website->db('game', $server)->prepare('DELETE FROM AccountCharacter WHERE Id = :account');
            return $stmt->execute([':account' => $account]);
        }

        public function delete_characters($account, $chars, $server){
            $c = '';
            foreach($chars as $char){
                $c .= ',\'' . $char['Name'] . '\'';
            }
            return $stmt = $this->website->db('game', $server)->query('DELETE FROM Character WHERE AccountId = ' . $this->website->db('game', $server)->escape($account) . ' AND Name IN (' . substr($c, 1, strlen($c)) . ')');
        }

        public function delete_account_log($account, $server){
            $stmt = $this->website->db('web')->prepare('DELETE FROM DmN_Account_Logs WHERE account = :account AND server = :server');
            return $stmt->execute([':account' => $account, ':server' => $server]);
        }

        public function delete_account_credits($account, $server){
            $stmt = $this->website->db('web')->prepare('DELETE FROM DmN_Shop_Credits WHERE memb___id = :account AND server = :server');
            return $stmt->execute([':account' => $account, ':server' => $server]);
        }

        public function delete_ban_list($account, $server){
            $stmt = $this->website->db('web')->prepare('DELETE FROM DmN_Ban_List WHERE name = :account AND server = :server AND type = 1');
            return $stmt->execute([':account' => $account, ':server' => $server]);
        }

        public function ban_account($server){
            $stmt = $this->website->db('account', $server)->prepare('UPDATE MEMB_INFO SET bloc_code = 1 WHERE memb___id = :account');
            $stmt->execute([':account' => $this->vars['name']]);
        }

        public function ban_char($server){
            $stmt = $this->website->db('game', $server)->prepare('UPDATE Character SET CtlCode = 1 WHERE Name = :name');
            $stmt->execute([':name' => $this->vars['name']]);
        }

        public function add_to_banlist($type = 1, $server = ''){
            $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Ban_List (name, type, server, time, is_permanent, reason) VALUES (:name, :type, :server, :time, :is_permanent, :reason)');
            $stmt->execute([':name' => $this->vars['name'], ':type' => $type, ':server' => $server, ':time' => (isset($this->vars['time']) && $this->vars['time'] != '') ? strtotime($this->vars['time']) : 0, ':is_permanent' => isset($this->vars['permanent_ban']) ? 1 : 0, ':reason' => $this->vars['reason']]);
        }

        public function load_ban_list($type){
            $query = $this->website->db('web')->query('SELECT name, server, time, type, is_permanent, reason FROM DmN_Ban_List WHERE type = ' . $this->website->db('web')->escape($type) . ' ORDER BY time ASC, is_permanent ASC');
            while($row = $query->fetch()){
                $this->bans[] = ['name' => htmlspecialchars($row['name']), 'type' => ($row['type'] == 1) ? 'Account' : 'Character', 'time' => ($row['is_permanent'] == 0) ? (($row['time'] < time()) ? 'Ban Expired' : date(DATETIME_FORMAT, $row['time'])) : 'Permanent Ban', 'reason' => $row['reason']];
            }
            return $this->bans;
        }

        public function load_department_list(){
            $data = $this->website->db('web')->query('SELECT id, department_name, server, is_active FROM DmN_Support_Departments ORDER BY id DESC');
			$departments = $data->fetch_all();
			if(!empty($departments)){
				foreach($departments AS $key => $val){
					if(ctype_xdigit($val['department_name'])){
						$departments[$key]['department_name'] = hex2bin($val['department_name']);
					}
				}
				return $departments;
			}
			else{
				return [];
			}
        }

		public function generate_priority($pr = 1, $list = false, $style = false){
            $priority = [1 => ['<div class="PriorityZero">' . __('Low') . '</div>', __('Low')], 2 => ['<div class="PriorityOne">' . __('Medium') . '</div>', __('Medium')], 3 => ['<div class="PriorityTwo">' . __('High') . '</div>', __('High')], 4 => ['<div class="PriorityThree">' . __('Urgent') . '</div>', __('Urgent')],];
            if($list){
                return $priority;
            } else{
                if(array_key_exists($pr, $priority)){
                    return ($style == true) ? $priority[$pr][0] : $priority[$pr][1];
                } else{
                    return 'unknown';
                }
            }
        }

        public function readable_status($status){
            switch($status){
                default:
                case 0:
                    $s = __('Open');
                    break;
                case 1:
                    $s = __('Closed');
                    break;
                case 2:
                    $s = __('Hold');
                    break;
                case 3:
                    $s = __('Resolved');
                    break;
                case 4:
                    $s = __('Spam');
                    break;
                case 5:
                    $s = __('Working');
                    break;
            }
            return $s;
        }

		public function load_support_requests($page, $per_page, $d_filter, $p_filter, $s_filter, $o_filter){
            $sql_data = [];
            if($d_filter != false){
                $sql_data[] = 'department IN(' . implode(',', $d_filter) . ')';
            }
            if($p_filter != false){
                $sql_data[] = 'priority IN(' . implode(',', $p_filter) . ')';
            }
            if($s_filter != false){
                $sql_data[] = 'status IN(' . implode(',', $s_filter) . ')';
            }
            $and = (!empty($sql_data)) ? 'AND ' . implode(' AND ', $sql_data) : '';
            if($o_filter[0] == 1){
                $order_by = 'create_time';
            } else if($o_filter[0] == 2){
                $order_by = 'last_reply_time';
            } else{
                $order_by = 'create_time';
            }
            if($o_filter[1] == 1){
                $order_by2 = ' ASC';
            } else{
                $order_by2 = ' DESC';
            }
            $query = $this->website->db('web')->query('SELECT TOP ' . $this->website->db('web')->escape((int)$per_page) . ' id, subject, create_time, creator_account, status, department FROM DmN_Support_Tickets WHERE id NOT IN (SELECT TOP ' . $this->website->db('web')->escape((int)($per_page * ($page - 1))) . ' id FROM DmN_Support_Tickets ORDER BY id DESC) ' . $and . ' ORDER BY ' . $order_by . $order_by2 . '');
            foreach($query->fetch_all() as $value){
                $this->items[] = [
					'id' => $value['id'], 
					'subject' => ctype_xdigit($value['subject']) ? hex2bin($value['subject']) : $value['subject'], 
					'create_time' => $value['create_time'], 
					'user' => htmlspecialchars($value['creator_account']), 
					'status' => $value['status'], 
					'reply_count' => $this->reply_count($value['id']),
					'department' => $this->get_department_name($value['department'])
				];
            }
            return $this->items;
        }
		
		public function get_department_name($id){
            $stmt = $this->website->db('web')->prepare('SELECT department_name FROM DmN_Support_Departments WHERE id = :id');
            $stmt->execute([':id' => $id]);
            if($name = $stmt->fetch()){
                return ctype_xdigit($name['department_name']) ? hex2bin($name['department_name']) : $name['department_name'];
            }
            return 'Unknown';
        }

        private function reply_count($id){
            return $this->website->db('web')->snumrows('SELECT COUNT(id) AS count FROM DmN_Support_Replies WHERE ticket_id = ' . $this->website->db('web')->escape($id) . '');
        }

        public function count_total_tickets($d_filter, $p_filter, $s_filter){
            $sql_data = [];
            if($d_filter != false){
                $sql_data[] = 'department IN(' . implode(',', $d_filter) . ')';
            }
            if($p_filter != false){
                $sql_data[] = 'priority IN(' . implode(',', $p_filter) . ')';
            }
            if($s_filter != false){
                $sql_data[] = 'status IN(' . implode(',', $s_filter) . ')';
            }
            $where = (!empty($sql_data)) ? 'WHERE ' . implode(' AND ', $sql_data) : '';
            $count = $this->website->db('web')->snumrows('SELECT COUNT(id) AS count FROM DmN_Support_Tickets ' . $where . '');
            return $count;
        }

        public function change_ticket_status($ids, $status){
            $in = implode(", ", array_keys($ids));
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_Support_Tickets SET status = :status WHERE id IN(' . $this->website->db('web')->escape($in) . ')');
            return $stmt->execute([':status' => $status]);
        }

		public function check_ticket($id){
            $stmt = $this->website->db('web')->prepare('SELECT TOP 1 id, subject, message, department, priority, create_time, status, creator_account, creator_character, server, attachment FROM DmN_Support_Tickets WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $ticket = $stmt->fetch();
			if($ticket != false){
				if(ctype_xdigit($ticket['subject'])){
					$ticket['subject'] = hex2bin($ticket['subject']);
				}
				if(ctype_xdigit($ticket['message'])){
					$ticket['message'] = hex2bin($ticket['message']);
				}
				return $ticket;
			}
			return false;
        }

        public function change_department($id, $department){
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_Support_Tickets SET department = :dept WHERE id = :id');
            return $stmt->execute([':dept' => $department, ':id' => $id]);
        }

        public function change_status($id, $status){
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_Support_Tickets SET status = :status WHERE id = :id');
            return $stmt->execute([':status' => $status, ':id' => $id]);
        }

        public function add_reply($id, $text, $user = 'Administrator'){
            $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Support_Replies(ticket_id, reply, reply_time, reply_by) VALUES (:id, :reply, :time, :account)');
            return $stmt->execute([':id' => $id, ':reply' => bin2hex($text), ':time' => time(), ':account' => $user]);
        }

        public function log_reply_time($id){
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_Support_Tickets SET last_reply_time = :time WHERE id = :id');
            return $stmt->execute([':time' => time(), ':id' => $id]);
        }

        public function set_replied_by_admin($id){
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_Support_Tickets SET replied_by_admin = 1, replied_by_user = 0 WHERE id = :id');
            return $stmt->execute([':id' => $id]);
        }

        public function set_replied_by_admin_and_user($id){
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_Support_Tickets SET replied_by_admin = 1, replied_by_user = 1 WHERE id = :id');
            return $stmt->execute([':id' => $id]);
        }

        public function load_ticket_replies($id){
            $ticket_create_date = $this->get_ticket_create_time($id);
            $stmt = $this->website->db('web')->prepare('SELECT id, ticket_id, reply, reply_time, reply_by FROM DmN_Support_Replies WHERE ticket_id = :id ORDER BY reply_time ASC');
            $stmt->execute([':id' => $id]);
            while($row = $stmt->fetch()){
                $this->times[$this->pos] = $row['reply_time'];
                $this->replies[] = ['reply' => ctype_xdigit($row['reply']) ? hex2bin($row['reply']) : $row['reply'], 'sender' => htmlspecialchars($row['reply_by']), 'create_time' => date(DATETIME_FORMAT, $row['reply_time']), 'time_between' => ($this->pos == 1) ? $this->website->date_diff($ticket_create_date, $this->times[$this->pos]) : $this->website->date_diff($this->times[$this->pos - 1], $this->times[$this->pos])];
                $this->pos++;
            }
            return $this->replies;
        }

        public function get_ticket_create_time($id){
            $stmt = $this->website->db('web')->prepare('SELECT create_time FROM DmN_Support_Tickets WHERE id = :id');
            $stmt->execute([':id' => $id]);
            if($time = $stmt->fetch()){
                return $time['create_time'];
            }
            return 0;
        } 

        public function get_last_reply_time($id){
            $stmt = $this->website->db('web')->prepare('SELECT TOP 1 reply_time FROM DmN_Support_Replies WHERE ticket_id = :id ORDER BY reply_time DESC');
            $stmt->execute([':id' => $id]);
            return $stmt->fetch();
        }

        public function check_unreplied_tickets(){
            $stmt = $this->website->db('web')->prepare('SELECT id FROM DmN_Support_Tickets WHERE replied_by_admin = 0 AND status NOT IN(1,3,4)');
            $stmt->execute([]);
            return $stmt->fetch_all();
        }

        public function load_support_filter(){
            return ['filter_department' => isset($_COOKIE['dmn_support_department']) ? unserialize($_COOKIE['dmn_support_department']) : false, 'filter_priority' => isset($_COOKIE['dmn_support_priorities']) ? unserialize($_COOKIE['dmn_support_priorities']) : false, 'filter_status' => isset($_COOKIE['dmn_support_status']) ? unserialize($_COOKIE['dmn_support_status']) : false, 'sort_by' => isset($_COOKIE['dmn_support_order']) ? unserialize($_COOKIE['dmn_support_order']) : [0 => 1, 1 => 2]];
        }

        public function serialize_departments($department){
            setcookie("dmn_support_department", serialize($department), strtotime('+30 days', time()), "/");
        }

        public function serialize_priorities($priority){
            setcookie("dmn_support_priorities", serialize($priority), strtotime('+30 days', time()), "/");
        }

        public function serialize_status($status){
            setcookie("dmn_support_status", serialize($status), strtotime('+30 days', time()), "/");
        }

        public function serialize_order($order){
            setcookie("dmn_support_order", serialize($order), strtotime('+30 days', time()), "/");
        }

        public function unset_departments(){
            if(isset($_COOKIE['dmn_support_department'])){
                unset($_COOKIE['dmn_support_department']);
                setcookie("dmn_support_department", "", time() - 3600, '/');
            }
        }

        public function unset_priorities(){
            if(isset($_COOKIE['dmn_support_priorities'])){
                unset($_COOKIE['dmn_support_priorities']);
                setcookie("dmn_support_priorities", "", time() - 3600, '/');
            }
        }

        public function unset_status(){
            if(isset($_COOKIE['dmn_support_status'])){
                unset($_COOKIE['dmn_support_status']);
                setcookie("dmn_support_status", "", time() - 3600, '/');
            }
        }

        public function unset_order(){
            if(isset($_COOKIE['dmn_support_order'])){
                unset($_COOKIE['dmn_support_order']);
                setcookie("dmn_support_order", "", time() - 3600, '/');
            }
        }

        public function check_existing_department($title, $server, $id = false){
            $sql = '';
            $data = [':name' => bin2hex($title), ':server' => $server];
            if($id != false){
                $sql = ' AND id != :id';
                $data[':id'] = $id;
            }
            $stmt = $this->website->db('web')->prepare('SELECT id FROM DmN_Support_Departments WHERE department_name = :name AND server = :server' . $sql);
            $stmt->execute($data);
            return $stmt->fetch();
        }

        public function add_department($title, $server, $pay, $ptype, $status){
            $sql = ['', ''];
            $sql2 = ['', ''];
            $data = [':name' => bin2hex($title), ':server' => $server];
            if($pay != 0 && $pay != ''){
                $sql = [', pay_per_incident', ', :pay'];
                $data[':pay'] = $pay;
            }
            if($ptype != 0 && $ptype != ''){
                $sql2 = [', payment_type', ', :ptype'];
                $data[':ptype'] = $ptype;
            }
            $data[':status'] = $status;
            $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Support_Departments(department_name, server' . $sql[0] . $sql2[0] . ', is_active) VALUES(:name, :server' . $sql[1] . $sql2[1] . ', :status)');
            return $stmt->execute($data);
        }

        public function edit_department($title, $server, $pay, $ptype, $status, $id){
            $sql = '';
            $sql2 = '';
            $data = [':name' => bin2hex($title), ':server' => $server];
            if($pay != 0 && $pay != ''){
                $sql = ', pay_per_incident = :pay';
                $data[':pay'] = $pay;
            }
            if($ptype != 0 && $ptype != ''){
                $sql2 = ', payment_type = :ptype';
                $data[':ptype'] = $ptype;
            }
            $data[':status'] = $status;
            $data[':id'] = $id;
            $stmt = $this->website->db('web')->prepare('UPDATE  DmN_Support_Departments SET department_name = :name, server =:server' . $sql . $sql2 . ', is_active = :status WHERE id = :id');
            return $stmt->execute($data);
        }

        public function check_department($id){
            $stmt = $this->website->db('web')->prepare('SELECT id, department_name, server, pay_per_incident, payment_type, is_active FROM DmN_Support_Departments WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $data = $stmt->fetch();
			if($data != false){
				$data['department_name'] = ctype_xdigit($data['department_name']) ? hex2bin($data['department_name']) : $data['department_name'];
				return $data;
			}
			return false;
        }

        public function delete_department($id){
            $stmt = $this->website->db('web')->prepare('DELETE FROM DmN_Support_Departments WHERE id = :id');
            return $stmt->execute([':id' => $id]);
        }

        public function save_server_data($array, $ksort = true){
            if($ksort){
                ksort($array);
            }
            $data = json_encode($array, JSON_PRETTY_PRINT);
            if(is_writable(BASEDIR . 'application' . DS . 'data')){
                $fp = @fopen(BASEDIR . 'application' . DS . 'data' . DS . 'serverlist.json', 'w');
                @fwrite($fp, $data);
                @fclose($fp);
                return true;
            }
            return false;
        }

		public function save_config_data($array, $file, $ksort = true){
            if($ksort){
                ksort($array);
            }
            $data = json_encode($array, JSON_PRETTY_PRINT);
            if(is_writable(BASEDIR . 'application' . DS . 'config')){
                $fp = @fopen(BASEDIR . 'application' . DS . 'config' . DS . $file . '.json', 'w');
                @fwrite($fp, $data);
                @fclose($fp);
                return true;
            }
            return false;
        }

		public function remove_server_from_config($file, $server){
            $config = $this->config->values($file);
            if(array_key_exists($server, $config)){
                unset($config[$server]);
                $this->save_config_data($config, $file, false);
            }
        }

		public function reorder_server_in_config($file, $order){
            $config = $this->config->values($file);
            $new_array = [];
            foreach($order AS $value){
                if(array_key_exists($value, $config)){
                    $new_array[$value] = $config[$value];
                }
            }
            if(!empty($new_array)){
                $this->save_config_data($new_array, $file, false);
            }
        }

		public function copy_settings($file, $new_server){
            $config = $this->config->values($file);
            if($config != false){
                if(!array_key_exists($new_server, $config)){
                    $new_config = reset($config);
                    $config[$new_server] = $new_config;
                    $this->save_config_data($config, $file, false);
                }
            }
        }

        public function list_databases(){
            return $this->website->db('web')->query('SELECT name FROM master.dbo.sysdatabases WHERE dbid > 4')->fetch_all();
        }

        public function check_memb_info($db){
            return $this->$db->query('SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = N\'MEMB_INFO\'')->fetch();
        }

        public function check_character($db){
            return $this->$db->query('SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = N\'Character\'')->fetch();
        }

        public function get_wh_size($db){
            return $this->$db->query('SELECT character_maximum_length as length FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = \'Warehouse\' AND column_name = \'Items\'')->fetch();
        }

        public function get_inv_size($db){
            return $this->$db->query('SELECT character_maximum_length as length FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = \'Character\' AND column_name = \'Inventory\'')->fetch();
        }

        public function check_if_column_exists($column, $table, $db){
            return $this->$db->query('SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = \'' . $table . '\'  AND COLUMN_NAME = \'' . $column . '\'')->fetch();
        }
		
		public function get_identity_column($table, $db){
            return $this->$db->query('SELECT name FROM syscolumns WHERE id = Object_ID(\'' . $table . '\') AND colstat & 1 = 1')->fetch();
        }

		public function add_column($column, $table, $info, $db){
            $query = 'ALTER TABLE ' . $table . ' ADD ' . $column . ' ' . $info['type'];
            if($info['identity'] == 1){
                $query .= ' IDENTITY(1,1)';
            }
            if($info['is_primary_key'] == 1){
                $query .= ' PRIMARY KEY';
            }
            $query .= ($info['null'] == 1) ? ' NULL' : ' NOT NULL';
            if($info['default'] != ''){
                $query .= ' DEFAULT ' . $info['default'] . '';
            }
            return $this->$db->query($query);
        }

				
		public function drop_column($col, $table, $db){
            $this->check_constraints_column($col, $table, $db);
            $this->check_default_constraints($col, $table, $db);
            return $this->$db->query('ALTER TABLE ' . $table . ' DROP COLUMN ' . $col . '');
        }

				
		private function check_constraints_column($col, $table, $db){
            $constraints = $this->$db->query('SELECT cu.CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE cu WHERE EXISTS (SELECT tc.* FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS tc WHERE tc.TABLE_NAME = \'' . $table . '\' AND cu.COLUMN_NAME = \'' . $col . '\' AND tc.CONSTRAINT_NAME = cu.CONSTRAINT_NAME)')->fetch_all();
            if(!empty($constraints)){
                foreach($constraints AS $const){
                    $this->drop_constraint($const['CONSTRAINT_NAME'], $table, $db);
                }
            }
        }

		private function check_default_constraints($col, $table, $db){
            $constraints = $this->$db->query('SELECT NAME FROM SYS.DEFAULT_CONSTRAINTS WHERE OBJECT_NAME(PARENT_OBJECT_ID) = \'' . $table . '\' AND COL_NAME (PARENT_OBJECT_ID, PARENT_COLUMN_ID) = \'' . $col . '\'')->fetch_all();
            if(!empty($constraints)){
                foreach($constraints AS $const){
                    $this->drop_constraint($const['NAME'], $table, $db);
                }
            }
        }
		
		private function drop_constraint($name, $table, $db){
            $this->$db->query('ALTER TABLE ' . $table . ' DROP CONSTRAINT ' . $name . '');
        }

        public function check_procedure($proc, $db){
            return $this->$db->query('SELECT * FROM sysobjects WHERE type = \'P\' AND name = \'' . $proc . '\'')->fetch();
        }

        public function drop_procedure($proc, $db){
            return $this->$db->query('DROP PROCEDURE ' . $proc . '');
        }

        public function insert_sql_data($sql, $db){
            $query = $this->$db->query($sql);
            $query->close_cursor();
            return $query;
        }

				
		public function dropTriggerPKCount($db){
			$this->$db->query('IF EXISTS (SELECT * FROM sys.triggers WHERE object_id = OBJECT_ID(N\'[dbo].[DmN_Update_Killer_Ranking]\'))
				DROP TRIGGER [dbo].[DmN_Update_Killer_Ranking]');
		}

				
		public function createTriggerPKCount($db){
			$this->$db->query('CREATE TRIGGER [dbo].[DmN_Update_Killer_Ranking] ON [dbo].[Character]
						   AFTER UPDATE
						AS 
						BEGIN
						DECLARE @last_pk_count int
						DECLARE @Name varchar(50)
						DECLARE @PKCount int
						DECLARE @new_pk int
						SET NOCOUNT ON;
							IF (UPDATE(PKCount))
							BEGIN
								SELECT @Name = Name, @PKCount = PKCount FROM inserted
								SELECT @last_pk_count = dmn_last_server_pk_count FROM Character WHERE Name = @Name
								
								IF(@last_pk_count < @PKCount)
								 BEGIN
									SET @new_pk = @PKCount - @last_pk_count
									UPDATE Character SET dmn_last_server_pk_count = @PKCount, dmn_pk_count = dmn_pk_count + @new_pk WHERE Name = @Name
								  END	
							END
						END');
		}

		public function required_columns(){
            return [
				'account_db' => [
					'MEMB_INFO' => [
						'Admin' => [
							'type' => 'int', 
							'is_primary_key' => 0, 
							'null' => 0, 
							'identity' => 0, 
							'default' => '((0))'
						], 
						'last_login' => [
							'type' => 'datetime', 
							'is_primary_key' => 0, 
							'null' => 1, 
							'identity' => 0, 
							'default' => ''
						], 
						'activated' => [
							'type' => 'int', 
							'is_primary_key' => 0, 
							'null' => 0, 
							'identity' => 0, 
							'default' => '((0))'
						], 
						'activation_id' => [
							'type' => 'varchar(50)', 
							'is_primary_key' => 0, 
							'null' => 1, 
							'identity' => 0, 
							'default' => ''
						], 
						'last_login_ip' => [
							'type' => 'varchar(50)', 
							'is_primary_key' => 0, 
							'null' => 1, 
							'identity' => 0, 
							'default' => ''
						], 
						'dmn_country' => [
							'type' => 'varchar(50)', 
							'is_primary_key' => 0, 
							'null' => 1, 
							'identity' => 0, 
							'default' => ''
						],
					],
				], 
				'char_db' => [
					'Character' => [
						'resets' => [
							'type' => 'int', 
							'is_primary_key' => 0, 
							'null' => 0, 
							'identity' => 0, 
							'default' => '((0))'
						], 
						'grand_resets' => [
							'type' => 'int', 
							'is_primary_key' => 0, 
							'null' => 0, 
							'identity' => 0, 
							'default' => '((0))'
						], 
						'last_reset_time' => [
							'type' => 'int', 
							'is_primary_key' => 0, 
							'null' => 1, 
							'identity' => 0, 
							'default' => ''
						], 
						'last_greset_time' => [
							'type' => 'int', 
							'is_primary_key' => 0, 
							'null' => 1, 
							'identity' => 0, 
							'default' => ''
						], 
						'dmn_pk_count' => [
							'type' => 'int', 
							'is_primary_key' => 0, 
							'null' => 0, 
							'identity' => 0, 
							'default' => '((0))'
						], 
						'dmn_last_server_pk_count' => [
							'type' => 'int', 
							'is_primary_key' => 0, 
							'null' => 0, 
							'identity' => 0, 
							'default' => '((0))'
						],
					],
				]
			];
        }

		public function pin_number_to_text($pos){
            switch($pos){
                case 0:
                    return '1st';
                    break;
                case 1:
                    return '2nd';
                    break;
                case 2:
                    return '3rd';
                    break;
                case 3:
                case 4:
                case 5:
                    return ($pos + 1) . 'th';
                    break;
            }
        }

        public function check_if_bulk_email_exists($subject){
            $stmt = $this->website->db('web')->prepare('SELECT TOP 1 id, subject, body, recipient_list, server, sending_started, sending_finished, sent_to, failed, is_finished, exclude FROM DmN_Bulk_Emails WHERE seo_subject = :seo_subject');
            $stmt->execute([':seo_subject' => $subject]);
            $data = $stmt->fetch();
            return ($data != false) ? $data : false;
        }

        public function load_bulk_emails(){
            return $this->website->db('web')->query('SELECT id, subject, body, sending_started, sending_finished, sent_to, failed, seo_subject, is_finished, exclude FROM DmN_Bulk_Emails ORDER BY sending_started DESC, id DESC')->fetch_all();
        }

        private function remove_not_activated($list){
            $new_list = [];
            foreach($list AS $key => $emails){
                if($emails['activated'] != 0){
                    $new_list[] = $emails;
                }
            }
            return $new_list;
        }

        private function remove_blocked($list){
            $new_list = [];
            foreach($list AS $key => $emails){
                if($emails['bloc_code'] != 1){
                    $new_list[] = $emails;
                }
            }
            return $new_list;
        }

        private function remove_vip_users($list){
            $new_list = [];
            $vip_users = $this->website->db('web')->query('SELECT memb___id FROM DmN_Vip_Users WHERE viptime > ' . time() . '')->fetch_all();
            if(!empty($vip_users)){
                $vip_users = call_user_func_array('array_merge', $vip_users);
                foreach($list AS $key => $emails){
                    if(!in_array($emails['memb___id'], $vip_users)){
                        $new_list[] = $emails;
                    }
                }
            } else{
                $new_list = $list;
            }
            return $new_list;
        }

        private function remove_gm_users($list, $servers = false){
            $new_list = [];
            $this->vars['server'] = ($servers != false) ? unserialize($servers) : $this->vars['server'];
            if(count($this->vars['server']) > 1){
                if($this->website->is_multiple_accounts() == true){
                    foreach($this->vars['server'] AS $server){
                        $this->list_gms($server);
                    }
                } else{
                    $this->list_gms($this->vars['server'][0]);
                }
            } else{
                $this->list_gms($this->vars['server'][0]);
            }

			if(!empty($this->gm_list)){
				$this->gm_list = call_user_func_array('array_merge', $this->gm_list);
				foreach($this->gm_list AS $key => $value){
					$this->gm_list[$key] = $value['AccountId'];
				}
            
                foreach($list AS $key => $emails){
                    if(!in_array($emails['memb___id'], $this->gm_list)){
                        $new_list[] = $emails;
                    }
                }
            } else{
                $new_list = $list;
            }
            return $new_list;
        }

        private function list_gms($server){
            $list = $this->website->db('game', $server)->query('SELECT DISTINCT AccountId FROM Character WHERE CtlCode IN(8, 32)')->fetch_all();
            if(!empty($list)){
                $this->gm_list[] = $list;
            }
        }

		public function add_bulk_mail(){
            $this->get_recipient_list();
            $this->recipients[0] = call_user_func_array('array_merge', $this->recipients);
            $recipient_list = $this->arrayUniqueMulti($this->recipients[0], true);
            if(isset($this->vars['exclude_list'])){
                $exclude_list = serialize($this->vars['exclude_list']);
                if(in_array('inactive', $this->vars['exclude_list'])){
                    $recipient_list = $this->remove_not_activated($recipient_list);
                }
                if(in_array('banned', $this->vars['exclude_list'])){
                    $recipient_list = $this->remove_blocked($recipient_list);
                }
                if(in_array('vip', $this->vars['exclude_list'])){
                    $recipient_list = $this->remove_vip_users($recipient_list);
                }
                if(in_array('gms', $this->vars['exclude_list'])){
                    $recipient_list = $this->remove_gm_users($recipient_list);
                }
            } else{
                $exclude_list = serialize([]);
            }
            if(!empty($recipient_list)){
                if($this->create_recipient_list($recipient_list, $this->vars['subject'])){
                    $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Bulk_Emails (subject, body, server, seo_subject, exclude) VALUES (:subject, :body, :server, :seo_subject, :exclude)');
                    return $stmt->execute([':subject' => $this->vars['subject'], ':body' => $this->vars['body'], ':server' => serialize($this->vars['server']), ':seo_subject' => $this->website->seo_string($this->vars['subject']), ':exclude' => $exclude_list]);
                }
            }
            return null;
        }

        private function create_recipient_list($recipient_list, $subject){
            $file = APP_PATH . DS . 'data' . DS . 'bulk_email_recipient_list' . DS . $this->website->seo_string($subject) . '.txt';
            $add_recipient_list = @file_put_contents($file, serialize($recipient_list));
            if($add_recipient_list != false){
                return true;
            }
            return false;
        }

        public function get_recipient_list_from_file($subject){
            $file = APP_PATH . DS . 'data' . DS . 'bulk_email_recipient_list' . DS . $subject . '.txt';
            if(file_exists($file)){
                return file_get_contents($file);
            }
            return serialize([]);
        }

        public function edit_bulk_mail($id){
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_Bulk_Emails SET subject = :subject, body = :body, server = :server, seo_subject = :seo_subject WHERE id = :id');
            return $stmt->execute([':subject' => $this->vars['new_subject'], ':body' => $this->vars['body'], ':server' => serialize($this->vars['server']), ':seo_subject' => $this->website->seo_string($this->vars['new_subject']), ':id' => $id]);
        }

        public function remove_bulk_email($subject){
            $file = APP_PATH . DS . 'data' . DS . 'bulk_email_recipient_list' . DS . $subject . '.txt';
            if(file_exists($file)){
                unlink($file);
            }
            $stmt = $this->website->db('web')->prepare('DELETE FROM DmN_Bulk_Emails WHERE seo_subject = :subject');
            return $stmt->execute([':subject' => $subject]);
        }

        public function resend_bulk_email($subject, $servers){
            $this->get_recipient_list($servers);
            $this->recipients[0] = call_user_func_array('array_merge', $this->recipients);
            if($this->create_recipient_list($this->arrayUniqueMulti($this->recipients[0], true), $subject)){
                return $this->website->db('web')->query('UPDATE DmN_Bulk_Emails SET sending_started = NULL, sending_finished = NULL, sent_to = 0, failed = 0, is_finished = 0 WHERE seo_subject = '.$this->website->db('web')->escape($subject).'');
            }
            return false;
        }

		private function get_recipient_list($servers = false){
            $this->vars['server'] = ($servers != false) ? unserialize($servers) : $this->vars['server'];
            if(count($this->vars['server']) > 1){
                if($this->website->is_multiple_accounts() == true){
                    foreach($this->vars['server'] AS $server){
                        $this->list_recipients($server);
                    }
                } else{
                    $this->list_recipients($this->vars['server'][0]);
                }
            } else{
                $this->list_recipients($this->vars['server'][0]);
            }
        }

        private function list_recipients($server){
            $list = $this->website->db('account', $server)->query('SELECT memb___id, mail_addr, activated, bloc_code FROM MEMB_INFO')->fetch_all();
            if(!empty($list)){
                $this->recipients[] = $list;
            }
        }

		private function arrayUniqueMulti($array, $preserveKeys = false){
            $arrayRewrite = [];
            $arrayHashes = [];
            foreach($array as $key => $item){
                $hash = md5(serialize($item));
                if(!isset($arrayHashes[$hash])){
                    $arrayHashes[$hash] = $hash;
                    if($preserveKeys){
                        $arrayRewrite[$key] = $item;
                    } else{
                        $arrayRewrite[] = $item;
                    }
                }
            }
            return $arrayRewrite;
        }

        public function get_plugin_list(){
            $plugins = [];
            foreach(new \DirectoryIterator(APP_PATH . DS . 'plugins') as $path){
                if($path->isDir() && !$path->isDot()){
                    $plugins[] = $path->__toString();
                }
            }
            return $plugins;
        }

		public function get_task_last_run($task){
            $file = $dir = APP_PATH . DS . 'logs' . DS . 'scheduler.json';
            if(file_exists($file)){
                $data = file_get_contents($file);
                if($data != ''){
                    $details = json_decode($data, true);
                    if(isset($details['jobs']) && array_key_exists($task, $details['jobs'])){
                        return $details['jobs'][$task]['time'];
                    }
                }
            }
            return 'undefined';
        }

		public function get_last_cron_run(){
            $file = $dir = APP_PATH . DS . 'logs' . DS . 'scheduler.json';
            if(file_exists($file)){
                $data = file_get_contents($file);
                if($data != ''){
                    $details = json_decode($data, true);
                    if(isset($details['last_exec_time'])){
                        return $details['last_exec_time'];
                    }
                }
            }
            return false;
        }
		
		public function sent_ticket_reply_email_user($user, $server, $uemail, $subject, $id){
            $body = @file_get_contents(APP_PATH . DS . 'data' . DS . 'email_patterns' . DS . 'support_email_reply_user_pattern.html');
            $body = str_replace('###USERNAME###', $user, $body);
            $body = str_replace('###SERVERNAME###', $this->config->config_entry('main|servername'), $body);
            $body = str_replace('###LINK###', $this->config->base_url, $body);
			$body = str_replace('###TICKET_LINK###', $this->config->base_url.'support/read-ticket/'.$id, $body);
            $body = str_replace('###SUBJECT###', $subject, $body);

            $this->sendmail($uemail, 'New reply on '.$subject.'.', $body);
            if($this->error == false){
                return true;
            } else{
                return false;
            }
        }

		public function sendmail($recipients, $subject, $message, $from = []){
            $this->vars['email_config'] = $this->config->values('email_config');
            if(!$this->vars['email_config'])
                throw new Exception('Email settings not configured.');
            if(!isset($this->vars['email_config']['server_email']) || $this->vars['email_config']['server_email'] == '')
                throw new Exception('Server email is not set.');
			
			if(!empty($from)){
				$ff = $from;
			}
			else{
				$ff = [$this->vars['email_config']['server_email'] => $this->config->config_entry('main|servername')];
			}
			
            switch($this->vars['email_config']['mail_mode']){
                case 0:
                    try{
                        if(!isset($this->vars['email_config']['smtp_server']) || $this->vars['email_config']['smtp_server'] == '')
                            throw new Exception('SMTP Server is not set.');
                        if(!isset($this->vars['email_config']['smtp_port']) || $this->vars['email_config']['smtp_port'] == '' || !is_numeric($this->vars['email_config']['smtp_port']))
                            throw new Exception('SMTP Port is not set.');
                        $transport = Swift_SmtpTransport::newInstance($this->vars['email_config']['smtp_server'], (int)$this->vars['email_config']['smtp_port']);
                        if($this->vars['email_config']['smtp_use_ssl'] == 1){
                            $transport->setEncryption('ssl');
                        }
                        if($this->vars['email_config']['smtp_use_ssl'] == 2){
                            $transport->setEncryption('tls');
                        }
                        if($this->vars['email_config']['smtp_username'] != ''){
                            $transport->setUsername($this->vars['email_config']['smtp_username']);
                        }
                        if($this->vars['email_config']['smtp_password'] != ''){
                            $transport->setPassword($this->vars['email_config']['smtp_password']);
                        }
                        $mailer = Swift_Mailer::newInstance($transport);
                        $message = Swift_Message::newInstance()->setSubject($subject)->setFrom($ff)->setTo([$recipients])->setBody($message)->setContentType('text/html');
                        if(!$mailer->send($message, $failures)){
                            $this->error = 'Failed sending email to ' . print_r($failures, 1);
                            return false;
                        }
                        return true;
                    } catch(Exception $e){
                        $this->error = $e->getMessage();
                    } catch(Swift_ConnectionException $e){
                        $this->error = 'There was a problem communicating with the SMTP-Server. Error-Text: ' . $e->getMessage();
                    } catch(Swift_Message_MimeException $e){
                        $this->error = 'There was an unexpected problem building the email. Error-Text: ' . $e->getMessage();
                    } catch(Swift_TransportException $e){
                        $this->error = $e->getMessage();
                    }
                    break;
                case 1:
                    try{
                        $transport = Swift_MailTransport::newInstance();
                        $mailer = Swift_Mailer::newInstance($transport);
                        $message = Swift_Message::newInstance()->setSubject($subject)->setFrom($ff)->setTo([$recipients])->setBody($message)->setContentType('text/html');
                        if(!$mailer->send($message, $failures)){
                            $this->error = 'Failed sending email to ' . print_r($failures, 1);
                            return false;
                        }
                        return true;
                    } catch(Swift_ConnectionException $e){
                        $this->error = 'There was a problem communicating with the SMTP-Server. Error-Text: ' . $e->getMessage();
                    } catch(Swift_Message_MimeException $e){
                        $this->error = 'There was an unexpected problem building the email. Error-Text: ' . $e->getMessage();
                    } catch(Swift_TransportException $e){
                        $this->error = $e->getMessage();
                    }
                    break;
                case 2:
                    try{
                        $transport = Swift_SendmailTransport::newInstance('/usr/sbin/sendmail -bs');
                        $mailer = Swift_Mailer::newInstance($transport);
                        $message = Swift_Message::newInstance()->setSubject($subject)->setFrom($ff)->setTo([$recipients])->setBody($message)->setContentType('text/html');
                        if(!$mailer->send($message, $failures)){
                            $this->error = 'Failed sending email to ' . print_r($failures, 1);
                            return false;
                        }
                        return true;
                    } catch(Swift_ConnectionException $e){
                        $this->error = 'There was a problem communicating with the SMTP-Server. Error-Text: ' . $e->getMessage();
                    } catch(Swift_Message_MimeException $e){
                        $this->error = 'There was an unexpected problem building the email. Error-Text: ' . $e->getMessage();
                    } catch(Swift_TransportException $e){
                        $this->error = $e->getMessage();
                    }
                    break;
                case 3:
                    try{
                        $transport = SwiftSparkPost\Transport::newInstance($this->vars['email_config']['smtp_password']);
                        $mailer = Swift_Mailer::newInstance($transport);
						
                        $message = Swift_Message::newInstance()->setSubject($subject)->setFrom($ff)->setTo([$recipients])->setBody($message)->setContentType('text/html');
                        if(!$mailer->send($message, $failures)){
                            $this->error = 'Failed sending email to ' . print_r($failures, 1);
                            return false;
                        }
                        return true;
                    } catch(Swift_ConnectionException $e){
                        $this->error = 'There was a problem communicating with the SMTP-Server. Error-Text: ' . $e->getMessage();
                    } catch(Swift_Message_MimeException $e){
                        $this->error = 'There was an unexpected problem building the email. Error-Text: ' . $e->getMessage();
                    } catch(Swift_TransportException $e){
                        $this->error = $e->getMessage();
                    }
                    break;
            }
        }
    }