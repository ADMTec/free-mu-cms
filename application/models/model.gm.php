<?php
    in_file();

    class Mgm extends model
    {
        public $error = false, $vars = [], $gm_info = [], $bans = [];

        //private $logs = array();
        public function __contruct(){
            parent::__construct();
        }

        public function __set($key, $val){
            $this->vars[$key] = $val;
        }

        public function __isset($name){
            return isset($this->vars[$name]);
        }

        public function load_announcement(){
            return $this->website->db('web')->query('SELECT TOP 1 announcement FROM DmN_GM_Announcement ORDER BY time DESC')->fetch();
        }

        public static function valid_username($name, $symbols = '\w\W', $len = [3, 30]){
            return preg_match('/^[' . $symbols . ']{' . $len[0] . ',' . $len[1] . '}+$/', $name);
        }

        public function check_gm_in_list(){
            $stmt = $this->website->db('web')->prepare('SELECT account, can_ban_acc, can_ban_char, can_search_acc, can_view_acc_details, limit_reward_credits FROM DmN_Gm_List WHERE account = :account AND server = :server');
            $stmt->execute([':account' => $this->vars['username'], ':server' => $this->vars['server']]);
            return ($this->gm_info = $stmt->fetch()) ? true : false;
        }

        public function login_gm($server){
            if(MD5 == 1){
                $stmt = $this->website->db('account', $server)->prepare('EXEC DmN_Check_Acc_MD5 :user, :pass');
                $stmt->execute([':user' => $this->vars['username'], ':pass' => $this->vars['password']]);
                $check = $stmt->fetch();
                $stmt->close_cursor();
                if($check['result'] == 'found'){
                    $stmt = $this->website->db('account', $server)->prepare('SELECT memb___id FROM MEMB_INFO WHERE (memb___id Collate Database_Default = :user Collate Database_Default)');
                    $stmt->execute([':user' => $this->vars['username']]);
                    $info = $stmt->fetch();
                } else{
                    $info = false;
                }
            } else{
                $stmt = $this->website->db('account', $server)->prepare('SELECT memb___id FROM MEMB_INFO WHERE (memb___id Collate Database_Default = :user Collate Database_Default) AND memb__pwd = :pass');
                $stmt->execute([':user' => $this->vars['username'], ':pass' => (MD5 == 2) ? md5($this->vars['password']) : $this->vars['password']]);
                $info = $stmt->fetch();
            }
            if($info){
                $this->session->register('user', ['username' => $info['memb___id'], 'server' => $this->vars['server'], 'is_gm' => true, 'can_ban_acc' => $this->gm_info['can_ban_acc'], 'can_ban_char' => $this->gm_info['can_ban_char'], 'can_search_acc' => $this->gm_info['can_search_acc'], 'credits_limit' => $this->gm_info['limit_reward_credits']]);
                return true;
            }
            return false;
        }

        public function search_acc($server){
            $stmt = $this->website->db('game', $server)->prepare('SELECT AccountId, Name FROM Character WHERE AccountId = :name');
            $stmt->execute([':name' => $this->vars['name']]);
            return $stmt->fetch();
        }

        public function search_char($server){
            $stmt = $this->website->db('game', $server)->prepare('SELECT AccountId, Name FROM Character WHERE Name = :name');
            $stmt->execute([':name' => $this->vars['name']]);
            return $stmt->fetch();
        }

        public function find_ip($acc, $server){
            $stmt = $this->website->db('account', $server)->prepare('SELECT last_login_ip FROM MEMB_INFO WHERE memb___id = :name');
            $stmt->execute([':name' => $acc]);
            $ip = $stmt->fetch();
            if($ip['last_login_ip'] != null)
                return $ip['last_login_ip']; else{
                $stmt = $this->website->db('account', $server)->prepare('SELECT IP FROM MEMB_STAT WHERE memb___id = :name');
                $stmt->execute([':name' => $acc]);
                $ip = $stmt->fetch();
                if($ip['IP'] != null)
                    return $ip['IP']; else{
                    return 'Unknown';
                }
            }
        }

        public function check_account($server){
            $stmt = $this->website->db('account', $server)->prepare('SELECT bloc_code FROM MEMB_INFO WHERE memb___id = :account');
            $stmt->execute([':account' => $this->vars['name']]);
            return ($info = $stmt->fetch()) ? $info : false;
        }

        public function check_char($server){
            $stmt = $this->website->db('game', $server)->prepare('SELECT AccountId, CtlCode FROM Character WHERE Name = :name');
            $stmt->execute([':name' => $this->vars['name']]);
            $info = $stmt->fetch();
            if($info != false){
                $guid = $this->get_guid($info['AccountId'], $server);
                $info['memb_guid'] = $guid['memb_guid'];
                return $info;
            }
            return false;
        }

        private function get_guid($acc, $server){
            $stmt = $this->website->db('account', $server)->prepare('SELECT memb_guid FROM MEMB_INFO WHERE memb___id = :acc');
            $stmt->execute([':acc' => $acc]);
            return $stmt->fetch();
        }

        public function ban_account($server){
            $stmt = $this->website->db('account', $server)->prepare('UPDATE MEMB_INFO SET bloc_code = 1 WHERE memb___id = :account');
            $stmt->execute([':account' => $this->vars['name']]);
        }

        public function ban_char($server){
            $stmt = $this->website->db('game', $server)->prepare('UPDATE Character SET CtlCode = 1 WHERE Name = :name');
            $stmt->execute([':name' => $this->vars['name']]);
        }

        public function add_to_banlist(){
            $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Ban_List (name, type, server, time, is_permanent, reason) VALUES (:name, :type, :server, :time, :is_permanent, :reason)');
            $stmt->execute([':name' => $this->vars['name'], ':type' => $this->vars['type'], ':server' => $this->session->userdata(['user' => 'server']), ':time' => (isset($this->vars['time']) && $this->vars['time'] != '') ? strtotime($this->vars['time']) : 0, ':is_permanent' => isset($this->vars['permanent_ban']) ? 1 : 0, ':reason' => $this->vars['reason']]);
        }

		public function load_ban_list(){
            $stmt = $this->website->db('web')->prepare('SELECT name, type, server, time, is_permanent, reason FROM DmN_Ban_List  WHERE server = :server ORDER BY time ASC, is_permanent ASC');
            $stmt->execute([':server' => $this->session->userdata(['user' => 'server'])]);
            while($row = $stmt->fetch()){
                $this->bans[] = ['name' => htmlspecialchars($row['name']), 'type' => ($row['type'] == 1) ? 'Account' : 'Character', 'time' => ($row['is_permanent'] == 0) ? (($row['time'] < time()) ? 'Ban Expired' : date(DATETIME_FORMAT, $row['time'])) : 'Permanent Ban', 'reason' => $row['reason']];
            }
            return $this->bans;
        }

        public function unban_account($name, $server){
            $stmt = $this->website->db('account', $server)->prepare('UPDATE MEMB_INFO SET bloc_code = 0 WHERE memb___id = :account');
            $stmt->execute([':account' => $name]);
        }

        public function unban_character($name, $server){
            $stmt = $this->website->db('game', $server)->prepare('UPDATE Character SET CtlCode = 0 WHERE Name = :name');
            $stmt->execute([':name' => $name]);
        }

        public function remove_ban_list_account($name){
            $stmt = $this->website->db('web')->prepare('DELETE FROM DmN_Ban_List WHERE name = :name AND type = 1');
            $stmt->execute([':name' => $name]);
        }

        public function remove_ban_list_character($name){
            $stmt = $this->website->db('web')->prepare('DELETE FROM DmN_Ban_List WHERE name = :name AND type = 2 AND server = :server');
            $stmt->execute([':name' => $name, ':server' => $this->session->userdata(['user' => 'server'])]);
        }

        public function add_gm_log($log, $acc, $server){
            $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_GM_Logs (text, date, account, server, ip) VALUES (:text, GETDATE(), :acc, :server, :ip)');
            $stmt->execute([':text' => $log, ':acc' => $acc, ':server' => $server, ':ip' => ip()]);
            $stmt->close_cursor();
        }

        public function get_gm_credits_limit($acc, $server, $default_limit = 0){
            $stmt = $this->website->db('web')->prepare('SELECT limit_left FROM DmN_Gm_Credits_Limit WHERE gm_acc = :acc AND gm_server = :server AND DATEDIFF(day, date, GETDATE()) = 0');
            $stmt->execute([':acc' => $acc, ':server' => $server]);
            if($info = $stmt->fetch()){
                return $info['limit_left'];
            } else{
                $this->delete_old_gm_data($acc, $server);
                $this->add_gm_into_credits_limit_table($acc, $server, $default_limit);
                return $default_limit;
            }
        }

        private function delete_old_gm_data($acc, $server){
            $stmt = $this->website->db('web')->prepare('DELETE FROM DmN_Gm_Credits_Limit WHERE gm_acc = :acc AND gm_server = :server');
            $stmt->execute([':acc' => $acc, ':server' => $server]);
        }

        private function add_gm_into_credits_limit_table($acc, $server, $default_limit){
            $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Gm_Credits_Limit (gm_acc, gm_server, date, limit_left) VALUES (:acc, :server, GETDATE(), :limit_left)');
            $stmt->execute([':acc' => $acc, ':server' => $server, ':limit_left' => $default_limit]);
        }

        public function update_credits_limit($acc, $server, $limit){
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_Gm_Credits_Limit SET limit_left = :limit_left WHERE gm_acc = :acc AND gm_server = :server AND DATEDIFF(day, date, GETDATE()) = 0');
            $stmt->execute([':limit_left' => $limit, ':acc' => $acc, ':server' => $server]);
        }
    }