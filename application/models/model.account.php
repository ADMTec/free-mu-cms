<?php
    in_file();

    class Maccount extends model
    {
        private $activation = 0;
        private $activation_code;
        private $password;
        public $error = false, $vars = [];
        private $logs = [];

        public function __contruct(){
            parent::__construct();
        }

        public function __set($key, $val){
            $this->vars[$key] = $val;
        }

        public function __isset($name){
            return isset($this->vars[$name]);
        }

        public function valid_username($name, $symbols = 'a-zA-Z0-9_-', $len = [3, 10]){	
            return preg_match('/^[' . $symbols . ']{' . $len[0] . ',' . $len[1] . '}+$/', $name);
        }

		public function valid_id($name, $symbols = '0-9', $len = [1, 5]){
            return preg_match('/^[' . $symbols . ']{' . $len[0] . ',' . $len[1] . '}+$/', $name);
        }																
        public function valid_password($password, $symbols = '\w\W', $len = [3, 32]){
            return preg_match('/^[' . $symbols . ']{' . $len[0] . ',' . $len[1] . '}+$/', $password);
        }

		public function test_password_strength($password, $len = [3, 10], $requirements = false){
            if(strlen($password) < $len[0]){
                $this->vars['errors'][] = sprintf(__('The password you entered is too short. Minimum length %d'), $len[0]);
            }
            if(strlen($password) > $len[1]){
                $this->vars['errors'][] = sprintf(__('The password you entered is too long. Maximum length %d'), $len[1]);
            }
            if($requirements['atleast_one_lowercase'] == 1){
                if(!preg_match("/[a-z]+/", $password)){
                    $this->vars['errors'][] = __('Password should contain atleast one lowercase letter.');
                }
            }
            if($requirements['atleast_one_uppercase'] == 1){
                if(!preg_match("/[A-Z]+/", $password)){
                    $this->vars['errors'][] = __('Password should contain atleast one uppercase letter.');
                }
            }
            if($requirements['atleast_one_number'] == 1){
                if(!preg_match("/[0-9]+/", $password)){
                    $this->vars['errors'][] = __('Password should contain atleast one number.');
                }
            }
            if($requirements['atleast_one_symbol'] == 1){
                if(!preg_match("/\W+/", $password)){
                    $this->vars['errors'][] = __('Password should contain atleast one symbol.');
                }
            }
        }

		public function generate_password($min_length = 4, $max_length = 10, $requirements = false){
            $chars = '';
            if($requirements['atleast_one_lowercase'] == 1){
                $chars .= 'abcdefghijklmnopqrstuvwxyz';
            }
            if($requirements['atleast_one_uppercase'] == 1){
                $chars .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            }
            if($requirements['atleast_one_number'] == 1){
                $chars .= '0123456789';
            }
            if($requirements['atleast_one_symbol'] == 1){
                $chars .= '-=~@#$%^&*()_+,./<>?;:[]{}\|';
            }
            if($requirements['atleast_one_lowercase'] == 0 && $requirements['atleast_one_uppercase'] == 0 && $requirements['atleast_one_number'] == 0 && $requirements['atleast_one_symbol'] == 0){
                $chars .= 'abcdefghijklmnopqrstuvwxyz';
            }
            $password = '';
            $alphabet_len = strlen($chars);
            $password_len = mt_rand($min_length + 1, $max_length);
            $random = openssl_random_pseudo_bytes($password_len);
            for($i = 0; $i < $password_len; $i++){
                $password .= $chars[ord($random[$i]) % $alphabet_len];
            }
            return $password;
        }

		public static function valid_email($email){
            if(filter_var($email, FILTER_VALIDATE_EMAIL) != false){
				$emailParts = explode('@', $email);
				if(strpos($emailParts[1], 'gmail') !== false || strpos($emailParts[1], 'yahoo') !== false) {
					if(!preg_match("/^[a-zA-Z0-9_\-\.]+$/", $emailParts[0])){
						return false;
					}
					return $email;
				}
				return $email;
			}
			return false;
        }

        public function check_if_validated($email, $server){
            $stmt = $this->website->db('account', $server)->prepare('SELECT memb___id, memb__pwd, activated, activation_id FROM MEMB_INFO WHERE mail_addr = :email');
            $stmt->execute([':email' => $email]);
            return $stmt->fetch();
        }

        public function check_duplicate_account($name, $server){
            $stmt = $this->website->db('account', $server)->prepare('SELECT memb___id FROM MEMB_INFO WHERE (memb___id Collate Database_Default = :username Collate Database_Default)');
            $stmt->execute([':username' => $name]);
            return $stmt->fetch();
        }

        public function check_duplicate_email($email, $server){
            $stmt = $this->website->db('account', $server)->prepare('SELECT memb___id FROM MEMB_INFO WHERE mail_addr = :email');
            $stmt->execute([':email' => $email]);
            return ($stmt->fetch()) ? true : false;
        }
		
		public function count_accounts_by_email($email, $server){
			return $this->website->db('account', $server)->snumrows('SELECT COUNT(memb___id) AS count FROM MEMB_INFO WHERE mail_addr = '.$this->website->db('account', $server)->escape($email).'');
		}

        public function check_acc_by_guid($id, $server){
            $stmt = $this->website->db('account', $server)->prepare('SELECT memb___id, mail_addr FROM MEMB_INFO WHERE memb_guid = :id');
            $stmt->execute([':id' => $id]);
            return $stmt->fetch();
        }


        public function set_activation($status){
            $this->activation = $status;
        }

		public function prepare_account($server, $req_email = 1, $req_secret = 0, $serverCode = false){
            $this->activation_code = strtoupper(sha1(microtime()));
            if($this->activation == 1){
                if($this->send_activation_email($server)){
                    return $this->create_account($server, $req_email, $req_secret, $serverCode);
                }
            } else{
                if($this->create_account($server, $req_email, $req_secret, $serverCode)){
                    if($this->config->values('email_config', 'welcome_email') == 1 && $req_email == 1){
                        $this->send_welcome_email($this->vars['user'], $this->vars['email']);
                    }
                    return true;
                }
            }
            return false;
        }

		public function create_account($server, $req_email = 1, $req_secret = 0, $serverCode = false){
            if(MD5 == 1){
                $prepare = $this->website->db('account', $server)->prepare('SET NOCOUNT ON;EXEC DmN_Check_Acc_MD5 :user, :pass');
                $prepare->execute([':user' => $this->vars['user'], ':pass' => $this->vars['pass']]);
                $pw = $prepare->fetch();
				if($pw == false){
					$prepare = $$this->website->db('account', $server)->prepare('EXEC DmN_Check_Acc_MD5 :user, :pass');
					$prepare->execute([':user' => $this->vars['user'], ':pass' => $this->vars['pass']]);
					$pw = $prepare->fetch();
				}
                $pw = (!$this->website->is_hex($pw['result'])) ? '0x' . strtoupper(bin2hex($pw['result'])) : '0x' . $pw['result'];
            } else if(MD5 == 2){
                $pw = md5($this->vars['pass']);
            } else{
                $pw = $this->vars['pass'];
            }
            $data = [];
            $data[] = ['field' => 'memb___id', 'value' => $this->vars['user'], 'type' => 's'];
            $data[] = ['field' => 'memb__pwd', 'value' => $pw, 'type' => (MD5 == 1) ? 'v' : 's'];
            $data[] = ['field' => 'memb_name', 'value' => $this->vars['user'], 'type' => 's'];
            $data[] = ['field' => 'sno__numb', 'value' => isset($this->vars['sno_number']) ? '111111'.$this->vars['sno_number'] : str_repeat(1, 13), 'type' => 's'];
            $data[] = ['field' => 'post_code', 'value' => '1234', 'type' => 's'];
            $data[] = ['field' => 'addr_info', 'value' => '11111', 'type' => 's'];
            $data[] = ['field' => 'addr_deta', 'value' => '12343', 'type' => 's'];
            if($req_email == 1){
                $data[] = ['field' => 'mail_addr', 'value' => $this->vars['email'], 'type' => 's'];
            }
            if($req_secret == 1){
                $data[] = ['field' => 'fpas_ques', 'value' => $this->vars['fpas_ques'], 'type' => 's'];
                $data[] = ['field' => 'fpas_answ', 'value' => $this->vars['fpas_answ'], 'type' => 's'];
            }
            $data[] = ['field' => 'phon_numb', 'value' => '12345', 'type' => 's'];
            $data[] = ['field' => 'job__code', 'value' => '1', 'type' => 's'];
            $data[] = ['field' => 'appl_days', 'value' => time(), 'type' => 'd'];
            $data[] = ['field' => 'modi_days', 'value' => time(), 'type' => 'ds'];
            $data[] = ['field' => 'out__days', 'value' => time(), 'type' => 'ds'];
            $data[] = ['field' => 'true_days', 'value' => time(), 'type' => 'ds'];
            $data[] = ['field' => 'mail_chek', 'value' => '1', 'type' => 's'];
            $data[] = ['field' => 'bloc_code', 'value' => '0', 'type' => 's'];
            $data[] = ['field' => 'ctl1_code', 'value' => '0', 'type' => 's'];
            $data[] = ['field' => 'activated', 'value' => ($this->activation == 1) ? 0 : 1, 'type' => 'i'];
            $data[] = ['field' => 'activation_id', 'value' => $this->activation_code, 'type' => 's'];
            $data[] = ['field' => 'dmn_country', 'value' => get_country_code(ip()), 'type' => 's'];
			if($serverCode !== false){
				$data[] = ['field' => 'servercode', 'value' => $serverCode, 'type' => 's'];
			}
            $prepare = $this->website->db('account', $server)->prepare($this->website->db('account', $server)->get_insert('MEMB_INFO', $data));
            if($prepare->execute()){
				if($this->website->db('account', $server)->check_if_table_exists('VI_CURR_INFO')){
					$this->website->db('account', $server)->query("INSERT INTO VI_CURR_INFO (ends_days,chek_code,used_time,memb___id,memb_name,memb_guid,sno__numb,Bill_Section,Bill_value,Bill_Hour,Surplus_Point,Surplus_Minute,Increase_Days ) VALUES ('2005','1',1234,'" . $this->website->db('account', $server)->escape($this->vars['user']) . "','" . $this->website->db('account', $server)->escape($this->vars['user']) . "',1,'7','6','3','6','6','".date("Ymd")."','0')");
					return true;
				}
				return true;
			}
			return false;
        }

        public function insert_referrer($referrer){
            $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Refferals (refferer, refferal, date_reffered, refferal_ip) VALUES (:referrer, :referral, GETDATE(), :ip)');
            $stmt->execute([':referrer' => $referrer, ':referral' => $this->vars['user'], ':ip' => ip()]);
        }

        public function check_referral_ip($server){
            $stmt = $this->website->db('account', $server)->prepare('SELECT memb_guid FROM MEMB_INFO WHERE last_login_ip = :ip');
            $stmt->execute([':ip' => ip()]);
            if($stmt->fetch()){
                return true;
            }
            return false;
        }

        public function add_ref_reward_after_reg($referrer){
            $this->website->add_credits($referrer, $this->vars['ref_server'], $this->config->values('referral_config', 'reward_on_registration'), $this->config->values('referral_config', 'reward_type'));
            $this->add_account_log('Reward for referring player ' . $this->website->translate_credits($this->config->values('referral_config', 'reward_type')), $this->config->values('referral_config', 'reward_on_registration'), $referrer, $this->vars['ref_server']);
        }

		protected function send_activation_email($server){
            $body = @file_get_contents(APP_PATH . DS . 'data' . DS . 'email_patterns' . DS . 'reg_email_pattern.html');
            $body = str_replace('###USERNAME###', $this->vars['user'], $body);
            $body = str_replace('###SERVERNAME###', $this->config->config_entry('main|servername'), $body);
            $body = str_replace('###PASSWORD###', $this->vars['pass'], $body);
            $body = str_replace('###ACTIVATIONURL###', $this->config->base_url . 'registration/activation/' . $this->activation_code . '/' . $server, $body);
            $this->sendmail($this->vars['email'], 'Confirm Your Registration', $body);
            if($this->error == false){
                return true;
            } else{
                return false;
            }
        }

		public function resend_activation_email($email, $user, $pwd, $server, $code){
            $body = @file_get_contents(APP_PATH . DS . 'data' . DS . 'email_patterns' . DS . 'reg_email_pattern_resend_activation.html');
            $body = str_replace('###USERNAME###', $user, $body);
            $body = str_replace('###SERVERNAME###', $this->config->config_entry('main|servername'), $body);
            $body = (MD5 == 0) ? str_replace('###PASSWORD###', 'Password: ' . $pwd, $body) : str_replace('###PASSWORD###', '<br />', $body);
            $body = str_replace('###ACTIVATIONURL###', $this->config->base_url . 'registration/activation/' . $code . '/' . $server, $body);
            $this->sendmail($email, 'Confirm Your Registration', $body);
            if($this->error == false){
                return true;
            } else{
                return false;
            }
        }

		public function send_welcome_email($user, $email){
            $body = @file_get_contents(APP_PATH . DS . 'data' . DS . 'email_patterns' . DS . 'welcome_email_pattern.html');
            $body = str_replace('###USERNAME###', $user, $body);
            $body = str_replace('###SERVERNAME###', $this->config->config_entry('main|servername'), $body);
            $body = str_replace('###LINK###', $this->config->base_url, $body);
            $this->sendmail($email, 'Welcome to ' . $this->config->config_entry('main|servername'), $body);
            if($this->error == false){
                return true;
            } else{
                return false;
            }
        }

		public function sent_vip_purchase_email($user, $server, $email, $package_title, $time){
            $body = @file_get_contents(APP_PATH . DS . 'data' . DS . 'email_patterns' . DS . 'vip_purchase_email_pattern.html');
            $body = str_replace('###USERNAME###', $user, $body);
            $body = str_replace('###SERVERNAME###', $this->config->config_entry('main|servername'), $body);
            $body = str_replace('###LINK###', $this->config->base_url, $body);
            $body = str_replace('###TIME###', date(DATETIME_FORMAT, $time), $body);
            $body = str_replace('###PACKAGE_TITLE###', $package_title, $body);
            $this->sendmail($email, 'You have successfully purchased vip!', $body);
            if($this->error == false){
                return true;
            } else{
                return false;
            }
        }

        public function sent_vip_extend_email($user, $server, $email, $package_title, $time){
            $body = @file_get_contents(APP_PATH . DS . 'data' . DS . 'email_patterns' . DS . 'vip_extend_email_pattern.html');
            $body = str_replace('###USERNAME###', $user, $body);
            $body = str_replace('###SERVERNAME###', $this->config->config_entry('main|servername'), $body);
            $body = str_replace('###LINK###', $this->config->base_url, $body);
            $body = str_replace('###TIME###', date(DATETIME_FORMAT, $time), $body);
            $body = str_replace('###PACKAGE_TITLE###', $package_title, $body);
            $this->sendmail($email, 'You have successfully extended your vip!', $body);
            if($this->error == false){
                return true;
            } else{
                return false;
            }
        }

		public function send_email_confirmation($user){
            $body = @file_get_contents(APP_PATH . DS . 'data' . DS . 'email_patterns' . DS . 'email_confirmation_pattern.html');
            $body = str_replace('###USERNAME###', $user, $body);
            $body = str_replace('###SERVERNAME###', $this->config->config_entry('main|servername'), $body);
            $body = str_replace('###IP###', ip(), $body);
            $body = str_replace('###URL###', $this->config->base_url . 'account-panel/email-confirm/' . $this->activation_code, $body);
            $this->sendmail($this->vars['email'], 'Email Confirmation', $body);
            if($this->error == false){
                return true;
            } else{
                return false;
            }
        }

		protected function send_master_key_recovery_email($user){
            $body = @file_get_contents(APP_PATH . DS . 'data' . DS . 'email_patterns' . DS . 'master_key_recovery_pattern.html');
            $body = str_replace('###USERNAME###', $user, $body);
            $body = str_replace('###SERVERNAME###', $this->config->config_entry('main|servername'), $body);
            $body = str_replace('###MASTERKEY###', $this->vars['master_key']['MasterKey'], $body);
            $this->sendmail($this->vars['master_key']['mail_addr'], 'Master Key Recovery', $body);
            if($this->error == false){
                return true;
            } else{
                return false;
            }
        }

		public function recover_master_key_process($user, $server){
            $stmt =  $this->website->db('account', $server)->prepare('SELECT mail_addr, auth__code AS MasterKey FROM MEMB_INFO WHERE memb___id = :account');
            $stmt->execute([':account' => $user]);
            if($this->vars['master_key'] = $stmt->fetch()){
                if($this->send_master_key_recovery_email($user)){
                    return true;
                }
                return false;
            }
            return false;
        }

        public function check_activation_code($code, $server){
            $stmt = $this->website->db('account', $server)->prepare('SELECT memb___id, mail_addr, activated FROM MEMB_INFO WHERE activation_id = :code');
            $stmt->execute([':code' => $code]);
            return $stmt->fetch();
        }

        public function activate_account($acc, $server, $code){
            $stmt = $this->website->db('account', $server)->prepare('UPDATE MEMB_INFO SET activated = 1 WHERE memb___id = :account AND activation_id = :code AND activated != 1');
            return $stmt->execute([':account' => $acc, ':code' => $code]);
        }

        public function load_account_by_name($name, $server){
            $stmt = $this->website->db('account', $server)->prepare('SELECT memb___id, mail_addr, sno__numb FROM MEMB_INFO WHERE memb___id = :name or mail_addr = :email');
            $stmt->execute([':name' => $name, ':email' => $name]);
            return $stmt->fetch();
        }

        public function load_reminder_by_name($name){
            $stmt = $this->website->db('web')->prepare('SELECT TOP 1 used FROM DmN_Account_Invt WHERE assignto = :name ORDER BY used DESC');
            $stmt->execute([':name' => $name]);
            return $stmt->fetch();
        }

        public function load_reminder_by_code($code){
            $stmt = $this->website->db('web')->prepare('SELECT inv_id, invt_code, assignto, used FROM DmN_Account_Invt WHERE UPPER(invt_code) = UPPER(:code)');
            $stmt->execute([':code' => $code]);
            return $stmt->fetch();
        }

        public function delete_reminder_entries_for_name($name){
            $stmt = $this->website->db('web')->prepare('DELETE FROM DmN_Account_Invt WHERE assignto = :name');
            return $stmt->execute([':name' => $name]);
        }

        public function create_reminder_entry_for_name($name){
            $code = strtoupper(sha1(microtime()));
            $data = [];
            $data[] = ['field' => 'invt_code', 'value' => $code, 'type' => 's'];
            $data[] = ['field' => 'assignto', 'value' => $name, 'type' => 's'];
            $data[] = ['field' => 'used', 'value' => time(), 'type' => 'i'];
            $prepare = $this->website->db('web')->prepare($this->website->db('web')->get_insert('DmN_Account_Invt', $data));
            $prepare->execute();
            return $code;
        }

		public function send_lostpassword_email_for_name($user, $email, $code, $server, $sid = ''){
            $body = @file_get_contents(APP_PATH . DS . 'data' . DS . 'email_patterns' . DS . 'lostpassword_email_pattern.html');
            $body = str_replace('###USERNAME###', $user, $body);
            $body = str_replace('###SERVERNAME###', $this->config->config_entry('main|servername'), $body);
            $body = str_replace('###IP###', ip(), $body);
            $body = str_replace('###SID###', $sid, $body);
            $body = str_replace('###URL###', $this->config->base_url . 'lost-password/activation/' . $code . '/' . $server, $body);
            $this->sendmail($email, 'Password Reminder', $body);
            if($this->error == false){
                return true;
            } else{
                return false;
            }
        }

		public function update_password($user, $server){		  
            if(MD5 == 1){
                $query = $this->website->db('account', $server)->query('SET NOCOUNT ON;EXEC DmN_Check_Acc_MD5 '.$this->website->db('account', $server)->escape($user).', '.$this->website->db('account', $server)->escape($this->vars['new_password']).'');
                $fetch = $query->fetch();
				$query->close_cursor();
				if($fetch  == false){
					$query = $this->website->db('account', $server)->query('EXEC DmN_Check_Acc_MD5 '.$this->website->db('account', $server)->escape($user).', '.$this->website->db('account', $server)->escape($this->vars['new_password']).'');
					$fetch = $query->fetch();
					$query->close_cursor();
				}
                
                if($fetch['result'] == 'found'){
                    return true;
                } else{
                    $pw = (!$this->website->is_hex($fetch['result'])) ? '0x' . strtoupper(bin2hex($fetch['result'])) : '0x' . $fetch['result'];
                }
            } else if(MD5 == 2){
                $pw = '\'' . md5($this->vars['new_password']) . '\'';
            } else{
                $pw = '\'' . $this->vars['new_password'] . '\'';
            }
						
            return $this->website->db('account', $server)->query('UPDATE MEMB_INFO SET memb__pwd = ' . $pw . ' WHERE (memb___id COLLATE Database_Default = '.$this->website->db('account', $server)->escape($user).' COLLATE Database_Default)');				
        }

		public function check_login_attemts(){
            $file = APP_PATH . DS . 'logs' . DS . 'login_attempts.txt';
            if(file_exists($file)){
                $data = file_get_contents($file);
                if($data != false && $data != ''){
                    $ips = unserialize($data);
                    if(isset($ips[ip()]) && $ips[ip()]['time'] >= time() - 900){
                        return $ips[ip()]['attempts'] >= 5;
                    }
                }
            }
            return false;
        }

		public function add_login_attemt(){
            $file = APP_PATH . DS . 'logs' . DS . 'login_attempts.txt';
            if(!file_exists($file)){
                file_put_contents($file, '');
            }
            $data = file_get_contents($file);
            if($data != false && $data != ''){
                $ips = unserialize($data);
                if(isset($ips[ip()])){
                    $ips[ip()]['attempts'] = $ips[ip()]['attempts'] + 1;
                    $ips[ip()]['time'] = time();
                } else{
                    $ips[ip()]['attempts'] = 1;
                    $ips[ip()]['time'] = time();
                }
            } else{
                $ips = [ip() => ['attempts' => 1, 'time' => time()]];
            }
            file_put_contents($file, serialize($ips));
            return true;
        }

		public function clear_login_attemts(){
            $file = APP_PATH . DS . 'logs' . DS . 'login_attempts.txt';
            if(file_exists($file)){
                $data = file_get_contents($file);
                if($data != false && $data != ''){
                    $ips = unserialize($data);
                    if(isset($ips[ip()])){
                        unset($ips[ip()]);
                        file_put_contents($file, serialize($ips));
                    }
                }
            }
            return true;
        }

		public function log_user_ip($user = ''){
            if($user != '')
                $this->vars['username'] = $user;
            if(!$this->ip_log_exists()){
                $this->insert_ip_log();
            } //else {
            //   $this->update_ip_log();
            //}
        }

        private function ip_log_exists(){
            $stmt = $this->website->db('web')->prepare('SELECT id FROM DmN_IP_Log WHERE account = :account AND ip = :ip AND login_type = 1');
            $stmt->execute([':account' => $this->vars['username'], ':ip' => ip()]);
            return $stmt->fetch();
        }

        private function insert_ip_log(){
            $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_IP_Log (account, ip, last_connected, login_type) VALUES (:account, :ip, GETDATE(), 1)');
            return $stmt->execute([':account' => $this->vars['username'], ':ip' => ip()]);
        }

		public function login_user($server){
			$linked = '';
			
			if(defined('PARTNER_SYSTEM') && PARTNER_SYSTEM == true){
				$linked = ', dmn_partner, dmn_linked_to';
			}
			
            $serverQuery = '';
			if(defined('CUSTOM_SERVER_CODES') && array_key_exists($server, CUSTOM_SERVER_CODES)){
				$serverQuery = ' AND servercode = '.(int)CUSTOM_SERVER_CODES[$server];
			}
			
            if(MD5 == 1){
                $stmt = $this->website->db('account', $server)->prepare('SET NOCOUNT ON;EXEC DmN_Check_Acc_MD5 :user, :pass');
                $stmt->execute([':user' => $this->vars['username'], ':pass' => $this->vars['password']]);
                $check = $stmt->fetch();
                $stmt->close_cursor();
				if($check == false){
					$stmt = $$this->website->db('account', $server)->prepare('EXEC DmN_Check_Acc_MD5 :user, :pass');
					$stmt->execute([':user' => $this->vars['username'], ':pass' => $this->vars['password']]);
					$check = $stmt->fetch();
					$stmt->close_cursor();
				}
                if($check['result'] == 'found'){
                    $stmt = $this->website->db('account', $server)->prepare('SELECT memb_guid, memb___id, memb__pwd, mail_addr, appl_days, modi_days, bloc_code, last_login, last_login_ip, activated, Admin, dmn_country '.$linked.' FROM MEMB_INFO WITH (NOLOCK) WHERE (memb___id Collate Database_Default = :user Collate Database_Default) '.$serverQuery.'');
                    $stmt->execute([':user' => $this->vars['username']]);
                    $info = $stmt->fetch();
                } else{
                    $info = false;
                }
            } else{
                $stmt = $this->website->db('account', $server)->prepare('SELECT memb_guid, memb___id, memb__pwd, mail_addr, appl_days, modi_days, bloc_code, last_login, last_login_ip, activated, Admin, dmn_country '.$linked.' FROM MEMB_INFO WITH (NOLOCK) WHERE (memb___id Collate Database_Default = :user Collate Database_Default) AND memb__pwd = :pass '.$serverQuery.'');
                $stmt->execute([':user' => $this->vars['username'], ':pass' => (MD5 == 2) ? md5($this->vars['password']) : $this->vars['password']]);
                $info = $stmt->fetch();
            }
            
            if($info != false){
                if($this->vars['username'] !== $info['memb___id']){
                    return false;
                } else{
                    $this->update_last_login($info['memb___id'], $server);
					if($info['appl_days'] instanceof \DateTime){
						$joined = $info['appl_days']->format(DATE_FORMAT);
						$last_login = $info['last_login']->format(DATETIME_FORMAT);
					}
					else{
						$joined = date(DATE_FORMAT, strtotime($info['appl_days']));
						$last_login = date(DATETIME_FORMAT, strtotime($info['last_login']));
					}
					
					$salt = $this->session->genRandSalt(25);
                    $this->session->register('user', [
						'id' => $info['memb_guid'], 
						'username' => $info['memb___id'], 
						'pass' => sha1($this->vars['password']), 
						'email' => $info['mail_addr'], 
						'last_login' => $last_login, 
						'last_ip' => $info['last_login_ip'], 
						'admin' => $info['Admin'], 
						'joined' => $joined, 
						'country' => $info['dmn_country'], 
						'server' => $server, 
                        'server_t' => $this->website->get_value_from_server($server, 'title'), 
						'logged_in' => true,
						'salt' => $salt,
						'partner' => (defined('PARTNER_SYSTEM') && PARTNER_SYSTEM == true) ? $info['dmn_partner'] : 0,
						'is_merchant' => $this->checkMerchant($info['memb___id'], $server)
                    ]);
					
					if($this->check_user_salt($info['memb___id'])){
						$this->update_user_salt($info['memb___id'], $salt);
					}
					else{
						$this->insert_user_salt($info['memb___id'], $salt);
					}

					if(defined('PARTNER_SYSTEM') && PARTNER_SYSTEM == true && $info['dmn_linked_to'] == NULL && $info['dmn_partner'] != 1){
						$data = $this->checkLinkedPartnerIP(ip());
						if($data != false){
							$this->linkToPartner($data['username'], $info['memb___id'], $server);
						}
					}
                    return $info;
                }
            }
            return false;
        }

		private function checkMerchant($user, $server){
			if($this->website->db('web')->check_if_table_exists('DmN_Merchant_List')){
				$stmt = $this->website->db('web')->prepare('SELECT id FROM DmN_Merchant_List WHERE memb___id = :user AND server = :server');
				$stmt->execute([':user' => $user, ':server' => $server]);
				$data = $stmt->fetch();
				return ($data != false) ? 1 : 0;
			}
			return 0;
		}

		private function checkLinkedPartnerIP($ip){
			$stmt = $this->website->db('web')->prepare('SELECT username FROM DmN_Partner_Access_Ips WHERE ip = :ip');
			$stmt->execute([':ip' => $ip]);
			return $stmt->fetch();
		}

		private function linkToPartner($partner, $user, $server){
			$stmt = $this->website->db('account', $server)->prepare('UPDATE MEMB_INFO SET dmn_linked_to = :partner, dmn_linked_on = GETDATE() WHERE memb___id = :user');
			$stmt->execute([':partner' => $partner, ':user' => $user]);
		}

		private function check_user_salt($user)
		{
			$stmt = $this->website->db('web')->prepare('SELECT session_salt FROM DmN_User_Salts WHERE memb___id = :user');
			$stmt->execute(array(':user' => $user));
			return $stmt->fetch();
		}

		private function update_user_salt($user, $salt)
		{
			$stmt = $this->website->db('web')->prepare('UPDATE DmN_User_Salts SET session_salt = :salt WHERE memb___id = :user');
			$stmt->execute(array(':salt' => $salt, ':user' => $user));
		}

		private function insert_user_salt($user, $salt)
		{
			$stmt = $this->website->db('web')->prepare('INSERT INTO DmN_User_Salts (memb___id, session_salt) VALUES (:user, :salt)');
			$stmt->execute(array(':user' => $user, ':salt' => $salt));
		}

		private function update_last_login($user, $server){
			$ip = ip();
            $country_code = get_country_code($ip);
            $stmt = $this->website->db('account', $server)->prepare('UPDATE MEMB_INFO SET last_login = GETDATE(), last_login_ip = :ip, dmn_country = :country WHERE memb___id = :user');
            $stmt->execute([':ip' => $ip, ':country' => $country_code, ':user' => $user]);
        }

		public function check_user_on_server($user, $server){
			$serverQuery = '';
			if(defined('CUSTOM_SERVER_CODES') && array_key_exists($server, CUSTOM_SERVER_CODES)){
				$serverQuery = ' AND servercode = '.(int)CUSTOM_SERVER_CODES[$server];
			}
			
            $stmt = $this->website->db('account', $server)->prepare('SELECT memb___id, memb__pwd FROM MEMB_INFO WHERE memb___id = :user '.$serverQuery.'');
            $stmt->execute([':user' => $user]);
            return $stmt->fetch();
        }

		public function check_fb_user($email, $server){
            $stmt = $this->website->db('account', $server)->prepare('SELECT memb_guid, memb___id, mail_addr, appl_days, modi_days, bloc_code, last_login, last_login_ip, activated, Admin, dmn_country FROM MEMB_INFO WHERE mail_addr = :email');
            $stmt->execute([':email' => $email]);
            $info = $stmt->fetch();
            if($info){
				$this->update_last_login($info['memb___id'], $server);
				if($info['appl_days'] instanceof \DateTime) {
					$joined = $info['appl_days']->format(DATE_FORMAT);
					$last_login = $info['last_login']->format(DATETIME_FORMAT);
				}
				else{
					$joined = date(DATE_FORMAT, strtotime($info['appl_days']));
					$last_login = date(DATETIME_FORMAT, strtotime($info['last_login']));
				}
				
				$salt = $this->session->genRandSalt(25);
				$this->session->register('user', [
					'id' => $info['memb_guid'], 
					'username' => $info['memb___id'], 
					'email' => $info['mail_addr'], 
					'last_login' => $last_login, 
					'last_ip' => $info['last_login_ip'], 
					'admin' => $info['Admin'], 
					'joined' => $joined, 
					'country' => $info['dmn_country'], 
					'server' => $server, 
					'server_t' => $this->website->get_value_from_server($server, 'title'),
					'logged_in' => true,
					'salt' => $salt,
					'partner' => (defined('PARTNER_SYSTEM') && PARTNER_SYSTEM == true) ? $info['dmn_partner'] : 0,
					'is_merchant' => $this->checkMerchant($info['memb___id'], $server)
					]
				);
				
				if($this->check_user_salt($info['memb___id'])){
					$this->update_user_salt($info['memb___id'], $salt);
				}
				else{
					$this->insert_user_salt($info['memb___id'], $salt);
				}

				if(defined('PARTNER_SYSTEM') && PARTNER_SYSTEM == true && $info['dmn_linked_to'] == NULL && $info['dmn_partner'] != 1){
					$data = $this->checkLinkedPartnerIP(ip());
					if($data != false){
						$this->linkToPartner($data['username'], $info['memb___id'], $server);
					}
				}
				return $info; 
            }
        }

		public function sendmail($recipients, $subject, $message){  
            try{
                $this->vars['config'] = $this->config->values('email_config');
                
                if($this->vars['config'] == false){
                    throw new Exception('Email settings not configured.');
                }
                if(!isset($this->vars['config']['server_email']) || $this->vars['config']['server_email'] == ''){
                    throw new Exception('Website email is not specified');
                }
                
                switch($this->vars['config']['mail_mode']){
                    case 0:
                        if(!isset($this->vars['config']['smtp_server']) || $this->vars['config']['smtp_server'] == ''){
                            throw new Exception('SMTP Server is not specified.');
                        }
                        if(!isset($this->vars['config']['smtp_port']) || $this->vars['config']['smtp_port'] == '' || !is_numeric($this->vars['config']['smtp_port'])){
                            throw new Exception('SMTP Port is not specified');
                        }
                        if($this->vars['config']['smtp_username'] == ''){
                            throw new Exception('SMTP Username is not specified');
                        }
                        
                        $password = '';
                        
                        if($this->vars['config']['smtp_password'] != ''){
                            if(str_contains($this->vars['config']['smtp_server'], 'gmail')){
                                $this->vars['config']['smtp_password'] = preg_replace('/\s+/', '', $this->vars['config']['smtp_password']);
                            }
                            $password = ':'.urlencode($this->vars['config']['smtp_password']);
                        }
                        
                        $transport = Symfony\Component\Mailer\Transport::fromDsn('smtp://'.urlencode($this->vars['config']['smtp_username']).$password.'@'.$this->vars['config']['smtp_server'].':'.$this->vars['config']['smtp_port'].''); 
                    break;
                    default:
                    case 1:
                        $transport = Symfony\Component\Mailer\Transport::fromDsn('native://default'); 
                    break;
                    case 2:
                        $transport = Symfony\Component\Mailer\Transport::fromDsn('sendmail://default'); 
                    break;
                }
                
                $mailer = new Symfony\Component\Mailer\Mailer($transport);
                $from = new  Symfony\Component\Mime\Address($this->vars['config']['server_email'], $this->config->config_entry('main|servername'));                
                $email = (new Symfony\Component\Mime\Email())->from($from)->to(...[$recipients])->subject($subject)->html($message);
                $mailer->send($email); 
                return true;
            } catch(TransportExceptionInterface $e){
                $this->error = $e->getMessage();	
                return false;
            } catch(\Exception $e){
                $this->error = $e->getMessage();
                return false;
            }
        }

        public function compare_passwords(){
            return ($this->session->userdata(['user' => 'pass']) == sha1($this->vars['old_password']));
        }

        public function get_amount_of_credits($name, $payment_method, $server, $id = false){
            $status = $this->website->get_user_credits_balance($name, $server, $payment_method, $id);
            return $status['credits'];
        }

        public function load_vote_links($server){
            return $this->website->db('web')->query('SELECT id, votelink, name, img_url, hours, reward, reward_type, mmotop_stats_url, mmotop_reward_sms, api, server FROM DmN_Votereward WHERE server = '.$this->website->db('web')->escape($server).' ORDER BY id')->fetch_all();
        }

        public function check_vote_link($link, $server){
            return $this->website->db('web')->query('SELECT name, hours, reward, reward_type, api  FROM DmN_Votereward WHERE id = ' . $this->website->db('web')->escape($link) . ' AND server = '.$this->website->db('web')->escape($server).'')->fetch();
        }

		public function get_last_vote($user, $server, $link, $interval = 12, $api = 0, $xtremetop_same_acc_vote = 0, $links = ''){
            if($api != 2){
                $vote_time = time() - (3600 * $interval);
                $log1 = $this->website->db('web')->query('SELECT TOP 1 account, ip, time FROM DmN_Votereward_Log WHERE number = ' . $this->website->db('web')->escape($link) . '  AND account = '.$this->website->db('web')->escape($user).' AND time > ' . $this->website->db('web')->escape($vote_time) . ' AND server = '.$this->website->db('web')->escape($server).' ORDER BY time DESC');
                if($xtremetop_same_acc_vote == 1){
                    $ids = (strpos(trim($links), ',') !== false) ? explode(',', trim($links)) : [0 => $links];
                    if(in_array($link, $ids)){
                        $log1 = $log1->fetch_all();
                    } else{
                        $log1 = $log1->fetch();
                    }
                } else{
                    $log1 = $log1->fetch();
                }
                $log2 = $this->website->db('web')->query('SELECT TOP 1 account, ip, time FROM DmN_Votereward_Log WHERE number = ' . $this->website->db('web')->escape($link) . '  AND ip = '.$this->website->db('web')->escape(ip()).' AND time > ' . $this->website->db('web')->escape($vote_time) . ' AND server = '.$this->website->db('web')->escape($server).' ORDER BY time DESC')->fetch();
                if((isset($log1['account']) || isset($log2['ip'])) || (isset($log1['account']) && isset($log2['ip']))){
                    return isset($log1['time']) ? $log1['time'] : $log2['time'];
                }
            }
            return false;
        }

        public function log_vote($user, $server, $link){
            $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Votereward_Log (number, ip, account, time, server) VALUES (:number, :ip, :account, :time, :server)');
            return $stmt->execute([':number' => $link, ':ip' => ip(), ':account' => $user, ':time' => time(), ':server' => $server]);
        }

        public function check_xtremetop_vote(){
            $stmt = $this->website->db('web')->prepare('SELECT id FROM DmN_Votereward_Xtremetop_Log WHERE memb_guid = :memb_guid AND validated = 0');
            $stmt->execute([':memb_guid' => $this->session->userdata(['user' => 'id'])]);
            return $stmt->fetch_all();
        }

        public function check_dmncms_vote(){
            $stmt = $this->website->db('web')->prepare('SELECT id FROM DmN_Votereward_Dmncms_Log WHERE memb_guid = :memb_guid AND validated = 0');
            $stmt->execute([':memb_guid' => $this->session->userdata(['user' => 'id'])]);
            return $stmt->fetch_all();
        }

        public function check_gametop100_vote(){
            $stmt = $this->website->db('web')->prepare('SELECT id FROM DmN_Votereward_Gametop100_Log WHERE memb_guid = :memb_guid AND validated = 0');
            $stmt->execute([':memb_guid' => $this->session->userdata(['user' => 'id'])]);
            return $stmt->fetch_all();
        }

        public function check_mmoserver_vote(){
            $stmt = $this->website->db('web')->prepare('SELECT id FROM DmN_Votereward_Mmoserver_Log WHERE memb_guid = :memb_guid AND validated = 0');
            $stmt->execute([':memb_guid' => $this->session->userdata(['user' => 'id'])]);
            return $stmt->fetch();
        }

        public function check_top100arena_vote(){
            $stmt = $this->website->db('web')->prepare('SELECT id FROM DmN_Votereward_Top100arena_Log WHERE memb_guid = :memb_guid AND validated = 0');
            $stmt->execute([':memb_guid' => $this->session->userdata(['user' => 'id'])]);
            return $stmt->fetch();
        }

        public function check_gtop100_vote(){
            $stmt = $this->website->db('web')->prepare('SELECT id FROM DmN_Votereward_Gtop_Log WHERE memb_guid = :memb_guid AND validated = 0');
            $stmt->execute([':memb_guid' => $this->session->userdata(['user' => 'id'])]);
            return $stmt->fetch_all();
        }

        public function check_topg_vote(){
            $stmt = $this->website->db('web')->prepare('SELECT id FROM DmN_Votereward_Topg_Log WHERE memb_guid = :memb_guid AND validated = 0');
            $stmt->execute([':memb_guid' => $this->session->userdata(['user' => 'id'])]);
            return $stmt->fetch_all();
        }

        public function add_xtremetop_vote($memb_guid, $ip){
            if(is_numeric($memb_guid)){
                $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Votereward_Xtremetop_Log (memb_guid, ip, time) VALUES (:memb_guid, :ip, :time)');
                return $stmt->execute([':memb_guid' => $memb_guid, ':ip' => $ip, ':time' => time()]);
            } else{
                writelog('Invalid user id ' . htmlspecialchars($memb_guid), 'vote_api');
            }
        }

        public function add_dmncms_vote($memb_guid, $ip){
            if(is_numeric($memb_guid)){
                $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Votereward_Dmncms_Log (memb_guid, ip, time) VALUES (:memb_guid, :ip, :time)');
                return $stmt->execute([':memb_guid' => $memb_guid, ':ip' => $ip, ':time' => time()]);
            } else{
                writelog('Invalid user id ' . htmlspecialchars($memb_guid), 'vote_api');
            }
        }

        public function add_gametop100_vote($memb_guid, $ip){
            if(is_numeric($memb_guid)){
                $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Votereward_Gametop100_Log (memb_guid, ip, time) VALUES (:memb_guid, :ip, :time)');
                return $stmt->execute([':memb_guid' => $memb_guid, ':ip' => $ip, ':time' => time()]);
            } else{
                writelog('Invalid user id ' . htmlspecialchars($memb_guid), 'vote_api');
            }
        }

        public function add_mmoserver_vote($memb_guid){
            if(is_numeric($memb_guid)){
                $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Votereward_Mmoserver_Log (memb_guid, time) VALUES (:memb_guid, :time)');
                return $stmt->execute([':memb_guid' => $memb_guid, ':time' => time()]);
            } else{
                writelog('Invalid user id ' . htmlspecialchars($memb_guid), 'vote_api');
            }
        }

        public function add_top100arena_vote($memb_guid){
            if(is_numeric($memb_guid)){
                $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Votereward_Top100arena_Log (memb_guid, time) VALUES (:memb_guid, :time)');
                return $stmt->execute([':memb_guid' => $memb_guid, ':time' => time()]);
            } else{
                writelog('Invalid user id ' . htmlspecialchars($memb_guid), 'vote_api');
            }
        }

        public function add_gtop100_vote($memb_guid, $ip){
            if(is_numeric($memb_guid)){
                $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Votereward_Gtop_Log (memb_guid, ip, time) VALUES (:memb_guid, :ip, :time)');
                return $stmt->execute([':memb_guid' => $memb_guid, ':ip' => $ip, ':time' => time()]);
            } else{
                writelog('Invalid user id ' . htmlspecialchars($memb_guid), 'vote_api');
            }
        }

        public function add_topg_vote($memb_guid, $ip){
            if(is_numeric($memb_guid)){
                $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Votereward_Topg_Log (memb_guid, ip, time) VALUES (:memb_guid, :ip, :time)');
                return $stmt->execute([':memb_guid' => $memb_guid, ':ip' => $ip, ':time' => time()]);
            } else{
                writelog('Invalid user id ' . htmlspecialchars($memb_guid), 'vote_api');
            }
        }

        public function set_valid_vote_xtremetop($id){
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_Votereward_Xtremetop_Log SET validated = 1 WHERE id = :id');
            return $stmt->execute([':id' => $id]);
        }

        public function set_valid_vote_dmncms($id){
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_Votereward_Dmncms_Log SET validated = 1 WHERE id = :id');
            return $stmt->execute([':id' => $id]);
        }

        public function set_valid_vote_gametop100($id){
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_Votereward_Gametop100_Log SET validated = 1 WHERE id = :id');
            return $stmt->execute([':id' => $id]);
        }

        public function set_valid_vote_mmoserver($id){
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_Votereward_Mmoserver_Log SET validated = 1 WHERE id = :id');
            return $stmt->execute([':id' => $id]);
        }

        public function set_valid_vote_top100arena($id){
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_Votereward_Top100arena_Log SET validated = 1 WHERE id = :id');
            return $stmt->execute([':id' => $id]);
        }

        public function set_valid_vote_gtop100($id){
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_Votereward_Gtop_Log SET validated = 1 WHERE id = :id');
            return $stmt->execute([':id' => $id]);
        }

        public function set_valid_vote_topg($id){
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_Votereward_Topg_Log SET validated = 1 WHERE id = :id');
            return $stmt->execute([':id' => $id]);
        }

        public function check_vote_rankings($account, $server){
			$gameid = $this->check_game_idc($account, $server);
            if($gameid != false){
                if($gameid['GameIDC'] != null){
					$id = $this->check_vote_rankings_entry($account, $server);
                    if($id != false){
                        $this->update_vote_rankings($account, $server, $gameid['GameIDC'], $id);
                    } else{
                        $this->insert_vote_rankings($account, $server, $gameid['GameIDC']);
                    }
                }
            }

            return false;
        }

        private function check_game_idc($account, $server){
            $stmt = $this->website->db('game', $server)->prepare('SELECT GameIDC FROM AccountCharacter WHERE Id = :user');
            $stmt->execute([':user' => $account]);
            return $stmt->fetch();
        }

        private function check_vote_rankings_entry($account, $server){
            $stmt = $this->website->db('web')->prepare('SELECT TOP 1 id FROM DmN_Votereward_Ranking WHERE account = :user AND server = :server AND year = :year AND month = :month');
            $stmt->execute([':user' => $account, ':server' => $server, ':year' => date('Y', time()), ':month' => date('F', time())]);
			$data = $stmt->fetch();
            if($data != false){
                return $data['id'];
            }
            return false;
        }

        private function update_vote_rankings($account, $server, $char, $id){
           $stmt = $this->website->db('web')->prepare('UPDATE DmN_Votereward_Ranking SET lastvote = :lastvote, totalvotes = totalvotes + 1, character = :char WHERE id = :id AND account = :user AND server = :server AND year = :year AND month = :month');
            $stmt->execute([':lastvote' => time(), ':char' => $char, ':id' => $id, ':user' => $account, ':server' => $server, ':year' => date('Y', time()), ':month' => date('F', time())]);
            $stmt->close_cursor();
        }

        private function insert_vote_rankings($account, $server, $char){
            $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Votereward_Ranking (account, character, server, lastvote, totalvotes, year, month) VALUES (:user, :char, :server, :lastvote, 1, :year, :month)');
            $stmt->execute([':user' => $account, ':char' => $char, ':server' => $server, ':lastvote' => time(), ':year' => date('Y', time()), ':month' => date('F', time())]);
            $stmt->close_cursor();
        }

		public function calculate_next_vote($time, $interval = 12){
            $hours = floor((((3600 * $interval) - (time() - $time)) / 3600));
            $minutes = floor(((((3600 * $interval) - (time() - $time)) % 3600) / 60));
            $h = isset($hours) ? $hours . ' h' : '';
            $m = isset($minutes) ? $minutes . ' min' : '';
            return $h . ' ' . $m;
        }

        public function check_mmotop_stats($link, $server){
            if(trim($link) != ''){
                $stats = file($link, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $logs = [];
                if($stats){
                    foreach($stats as $log){
                        $logs[] = explode("	", $log);
                    }
                    return $logs;
                }
            }
            return false;
        }

        public function insert_mmotop_stats($stats, $server){
            $reward = false;
            foreach($stats as $key => $log){
                $stmt = $this->website->db('web')->prepare('SELECT unid FROM DmN_Mmotop_Stats WHERE unid = :unid AND server = :server');
                $stmt->execute([':unid' => $log[0], ':server' => $server]);
                if(!$stmt->fetch()){
                    $reward = true;
                    $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Mmotop_Stats (unid, character, vote_type, server) VALUES (:unid, :char, :vote_type, :server)');
                    $stmt->execute([':unid' => $log[0], ':char' => $log[3], ':vote_type' => $log[4], ':server' => $server]);
                }
            }
            return $reward;
        }

		public function check_mmotop_voters($rewards, $type, $server){
            $query = $this->website->db('web')->query('SELECT unid, character, vote_type FROM DmN_Mmotop_Stats WHERE status = 0 AND server = '.$this->website->db('web')->escape($server).'')->fetch_all();
            foreach($query as $value){
                $stmt = $this->website->db('game', $server)->prepare('SELECT TOP 1 AccountId FROM Character WHERE Name = :char OR AccountId = :acc');
                $stmt->execute([':char' => $value['character'], ':acc' => $value['character']]);
                if($info = $stmt->fetch()){
                    $this->check_vote_rankings($info['AccountId'], $server);
                    $this->log_rewarded_mmotop_vote($value['unid']);
                    if($value['vote_type'] == 1){
                        $this->reward_voter($rewards[0], $type, $server, $info['AccountId'], 'mmotop');																												 
                    } else{
                        $this->reward_voter($rewards[1], $type, $server, $info['AccountId'], 'mmotop');																													 
                    }
                }
            }
        }

        private function log_rewarded_mmotop_vote($mmotop_unid){
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_Mmotop_Stats SET status = 1 WHERE unid = :unid');
            $stmt->execute([':unid' => $mmotop_unid]);
            $stmt->close_cursor();
        }

		public function add_account_log($log, $credits, $acc, $server){
            $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Account_Logs (text, amount, date, account, server, ip) VALUES (:text, :amount, GETDATE(), :acc, :server, :ip)');
            $stmt->execute([':text' => $log, ':amount' => round($credits), ':acc' => $acc, ':server' => $server, ':ip' => ip()]);
            $stmt->close_cursor();
        }

        public function reward_voter($reward, $type, $server, $user, $site = ''){
            $this->website->add_credits($user, $server, $reward, $type);
			if($site != ''){
				$this->add_account_log('Reward ' . $this->website->translate_credits($type, $server) . ' votereward, site: '.$site.'', $reward, $user, $server);
			}
			else{
				$this->add_account_log('Reward ' . $this->website->translate_credits($type, $server) . ' votereward', $reward, $user, $server);
			}
        }

		public function check_connect_stat($user, $server){
			$stmt = $this->website->db('account', $server)->prepare('SELECT ConnectStat FROM MEMB_STAT WHERE memb___id = :user');
			$stmt->execute([':user' => $user]);
			if($status = $stmt->fetch()){
				return ($status['ConnectStat'] == 0);
			}
        }

        public function load_logs($user, $server, $page = 1, $per_page = 30){
            $next_page = ($page <= 1) ? 0 : (int)$per_page * ((int)$page - 1);
            $logs = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape((int)$per_page) . ' id, text, amount, date, ip FROM DmN_Account_Logs WHERE account = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).' AND id Not IN (SELECT Top ' . $this->website->db('web')->escape($next_page) . ' id FROM DmN_Account_Logs WHERE account = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).' ORDER BY id DESC) ORDER BY id DESC');
            $pos = ($page == 1) ? 1 : (int)(($page - 1) * $per_page) + 1;
            foreach($logs->fetch_all() as $key => $value){
                $this->logs[] = [
                    'id' => $value['id'], 
                    'text' => $value['text'], 
                    'amount' => $value['amount'], 
                    'date' => strtotime($value['date']), 
                    'ip' => $value['ip'], 
                    'pos' => $pos
                ];
                $pos++;
            }
            return $this->logs;
        }

        public function count_total_logs($user, $server){
            return $this->website->db('web')->snumrows('SELECT COUNT(id) AS count FROM DmN_Account_Logs WHERE account = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).'');
        }

        public function load_wallet_zen($user, $server){
            return $this->website->db('web')->query('SELECT credits4 AS credits3 FROM DmN_Shop_Credits WHERE memb___id = '.$this->website->db('web')->escape($user).' AND server = '.$this->website->db('web')->escape($server).'')->fetch();
        }

        public function check_acc_ban($user){
            $stmt = $this->website->db('web')->prepare('SELECT time, is_permanent, reason FROM DmN_Ban_List WHERE name = :name AND type = 1');
            $stmt->execute([':name' => $user]);
            return $stmt->fetch();
        }

        public function check_secret_q_a($account, $server, $question, $answer){
            $stmt = $this->website->db('account', $server)->prepare('SELECT TOP 1 memb_guid FROM MEMB_INFO WHERE memb___id = :account AND fpas_ques = :question AND fpas_answ = :answer');
            $stmt->execute([':account' => $account, ':question' => $question, ':answer' => $answer]);
            return $stmt->fetch();
        }

        public function check_vip($account, $server){
            $stmt = $this->website->db('web')->prepare('SELECT viptype, viptime FROM DmN_Vip_Users WHERE memb___id = :account AND server = :server');
            $stmt->execute([':account' => $account, ':server' => $server]);
            return $stmt->fetch();
        }

        public function remove_vip($id, $account, $server){
            $stmt = $this->website->db('web')->prepare('DELETE FROM DmN_Vip_Users WHERE viptype = :id AND memb___id = :account AND server = :server');
            return $stmt->execute([':id' => $id, ':account' => $account, ':server' => $server]);
        }

		public function check_connect_member_file($connect_member_load, $account){
            if($connect_member_load != null){
                $info = pathinfo($connect_member_load);
                if(isset($info['extension']) && $info['extension'] == 'txt'){
                    $this->remove_from_txt_file($connect_member_load, $account);
                }
                if(isset($info['extension']) && $info['extension'] == 'xml'){
                    $this->remove_from_xml_file($connect_member_load, $account);
                }
            }
        }

		private function remove_from_txt_file($connect_member_load, $account){
            if(is_writable($connect_member_load)){
                $acc = '"' . $account . '"';
                $file = file($connect_member_load);
                $file = array_filter($file, function($item) use ($acc){
                    return trim($item) != $acc;
                });
                file_put_contents($connect_member_load, preg_replace('/^\h*\v+/m', '', implode(PHP_EOL, $file)));
            }
        }

		private function remove_from_xml_file($connect_member_load, $account){
            if(is_writable($connect_member_load)){
                $data = file_get_contents($connect_member_load);
                $xml = new SimpleXMLElement($data);
                unset($xml->xpath('Account[@Name="' . $account . '"]')[0]->{0});
                $dom = new DOMDocument("1.0");
                $dom->preserveWhiteSpace = false;
                $dom->formatOutput = true;
                $dom->loadXML($xml->asXml());
                $dom->save($connect_member_load);
            }
        }

        public function load_vip_package_info($id, $server){
            $stmt = $this->website->db('web')->prepare('SELECT TOP 1
                                              [package_title]
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
                                              ,[server_bonus_info] FROM DmN_Vip_Packages WHERE id = :id AND server = :server ORDER BY id ASC');
            $stmt->execute([':id' => $id, ':server' => $server]);
            return $stmt->fetch();
        }

        public function set_vip_session($viptime, $data){
            $this->session->register('vip', ['time' => $viptime, 'title' => $data['package_title'], 'reset_price_decrease' => $data['reset_price_decrease'], 'reset_level_decrease' => $data['reset_level_decrease'], 'reset_bonus_points' => $data['reset_bonus_points'], 'grand_reset_bonus_credits' => $data['grand_reset_bonus_credits'], 'grand_reset_bonus_gcredits' => $data['grand_reset_bonus_gcredits'], 'hide_info_discount' => $data['hide_info_discount'], 'pk_clear_discount' => $data['pk_clear_discount'], 'clear_skilltree_discount' => $data['clear_skilltree_discount'], 'online_hour_exchange_bonus' => $data['online_hour_exchange_bonus'], 'change_name_discount' => $data['change_name_discount'], 'change_class_discount' => $data['change_class_discount'], 'bonus_credits_for_donate' => $data['bonus_credits_for_donate'], 'shop_discount' => $data['shop_discount']]);
        }

        public function load_my_referrals($user){
            $stmt = $this->website->db('web')->prepare('SELECT refferal, date_reffered FROM DmN_Refferals WHERE refferer = :account ORDER BY date_reffered DESC');
            $stmt->execute([':account' => $user]);
            return $stmt->fetch_all();
        }
        
        public function check_if_referral_exists($referrer, $referral){
            $stmt = $this->website->db('web')->prepare('SELECT id FROM DmN_Refferals WHERE refferer = :account AND refferal = :refaccount');
            $stmt->execute([':account' => $referrer, ':refaccount' => $referral]);
            $data = $stmt->fetch();
            return ($data != false) ? true : false;
        }

        public function load_referral_rewards($server){
            $stmt = $this->website->db('web')->prepare('SELECT id, required_lvl, required_res, required_gres, reward, reward_type, server FROM DmN_Refferal_Reward_List WHERE server = :server AND status = 1');
            $stmt->execute([':server' => $server]);
            return $stmt->fetch_all();
        }

        public function check_referral_reward($id, $server){
            $stmt = $this->website->db('web')->prepare('SELECT TOP 1 id, required_lvl, required_res, required_gres, reward, reward_type, server FROM DmN_Refferal_Reward_List WHERE id = :id AND server = :server AND status = 1');
            $stmt->execute([':id' => $id, ':server' => $server]);
            return $stmt->fetch();
        }

		public function check_claimed_referral_rewards($curUser, $id, $chars, $server){
            if(is_array($chars)){
                $chars = array_map(function($a){
                    return sprintf("'%s'", $a);
                }, $chars);
                $search = implode(',', $chars);
            } else{
                $search = '\'' . $chars . '\'';
            }
            $stmt = $this->website->db('web')->prepare('SELECT TOP 1 id FROM DmN_Refferal_Claimed_Rewards WHERE reward_id = :id AND account = :account AND character IN(' . $search . ') AND server = :server');
            $stmt->execute([':id' => $id, ':account' => $curUser, ':server' => $server]);
            return $stmt->fetch();
        }

        public function check_name_in_history($name, $server){
            $stmt = $this->website->db('web')->prepare('SELECT old_name, new_name FROM DmN_ChangeName_History WHERE new_name = :name OR old_name = :namee AND server = :server ORDER BY change_date DESC');
            $stmt->execute([':name' => $name, ':namee' => $name, ':server' => $server]);
            return $stmt->fetch_all();
        }

        public function check_if_reward_was_claimed($curUser, $id, $server, $account){
            $stmt = $this->website->db('web')->prepare('SELECT TOP 1 id FROM DmN_Refferal_Claimed_Rewards WHERE reward_id = :id AND account = :account AND server = :server AND ref_account = :ref_account');
            $stmt->execute([':id' => $id, ':account' => $curUser, ':server' => $server, ':ref_account' => $account]);
            return $stmt->fetch();
        }

        public function add_referral_reward($curUser, $curServer, $reward, $reward_type, $char){
            $this->website->add_credits($curUser, $curServer, $reward, $reward_type);
            $this->add_account_log('Claimed referral reward from character ' . $char . ' for ' . $this->website->translate_credits($reward_type, $curServer), $reward, $curUser, $curServer);
        }

        public function log_reward($curUser, $id, $char, $server, $account){
            $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Refferal_Claimed_Rewards (reward_id, account, character, server, ref_account) VALUES (:id, :account, :char, :server, :ref)');
            return $stmt->execute([':id' => $id, ':account' => $curUser, ':char' => $char, ':server' => $server, ':ref' => $account,]);
        }

		public function check_referral_ips($curUser, $acc){
            $stmt = $this->website->db('web')->prepare('SELECT ip FROM DmN_IP_Log WHERE account = :account');
            $stmt->execute([':account' => $acc]);
            $data = $stmt->fetch_all();
            $ip_data = [];
            if(!empty($data)){
                foreach($data AS $value){
                    $stmt2 = $this->website->db('web')->prepare('SELECT account FROM DmN_IP_Log WHERE ip = :ip');
                    $stmt2->execute([':ip' => $value['ip']]);
                    $ip_data = $stmt2->fetch_all();
                }
                if(!empty($ip_data)){
                    foreach($ip_data AS $key => $accounts){
                        foreach($accounts AS $acc){
                            if($curUser == $acc){
                                return true;
                            }
                        }
                    }
                }
            }
            return false;
        }

        public function get_guid($user, $server){
            $stmt = $this->website->db('account', $server)->prepare('SELECT memb_guid FROM MEMB_INFO WHERE memb___id = :user');
            $stmt->execute([':user' => $user]);
            $info = $stmt->fetch();
            return ($info != false) ? $info['memb_guid'] : false;
        }

        public function check_existing_email($user, $server){
            $stm = $this->website->db('account', $server)->prepare('SELECT mail_addr FROM MEMB_INFO WHERE (memb___id Collate Database_Default = :username) AND mail_addr = :email');
            $stm->execute([':username' => $user, ':email' => $this->vars['email']]);
            return ($stm->fetch()) ? true : false;
        }

        public function create_email_confirmation_entry($user, $old = 1){
            $old = ($old == 1) ? 1 : 0;
            $this->activation_code = strtoupper(sha1(microtime()));
            $prepare = $this->website->db('web')->prepare('INSERT INTO DmN_Email_Confirmation (account, email, code, old_email) VALUES (:account, :email, :code, :old_email)');
            return $prepare->execute([':account' => $user, ':email' => $this->vars['email'], ':code' => $this->activation_code, ':old_email' => $old]);
        }

        public function delete_old_confirmation_entries($user, $old = 0){
            $old = ($old == 1) ? 1 : 0;
            $stmt = $this->website->db('web')->prepare('DELETE FROM DmN_Email_Confirmation WHERE account = :acc AND old_email = ' . $old . '');
            return $stmt->execute([':acc' => $user]);
        }

        public function load_email_confirmation_by_code($code){
            $stmt = $this->website->db('web')->prepare('SELECT account, email, old_email FROM DmN_Email_Confirmation WHERE UPPER(code) = UPPER(:code)');
            $stmt->execute([':code' => $code]);
            return $stmt->fetch();
        }
        
        public function get_email($acc, $server){
            $stmt = $this->website->db('account', $server)->prepare('SELECT mail_addr FROM MEMB_INFO WHERE memb___id = :acc');
            $stmt->execute([':acc' => $acc]);
            if($email = $stmt->fetch()){
                return $email['mail_addr'];
            }
            return false;
        }

        public function update_email($acc, $email, $server){
            $stmt = $this->website->db('account', $server)->prepare('UPDATE MEMB_INFO SET mail_addr = :email WHERE memb___id = :account');
            return $stmt->execute([':email' => $email, ':account' => $acc]);
        }

		public function check2FA($user){
			$stmt = $this->website->db('web')->prepare('SELECT memb___id, secret, backup_code FROM DmN_2FA WHERE memb___id = :user');
            $stmt->execute([':user' => $user]);
            return $stmt->fetch();
		}

		public function add2FA($user, $secret, $backup){
			$stmt = $this->website->db('web')->prepare('INSERT INTO DmN_2FA (memb___id, secret, backup_code) VALUES (:user, :secret, :backup)');
            $stmt->execute([':user' => $user, ':secret' => $secret, ':backup' => $backup]);
		}

		public function remove2FA($user){
			$stmt = $this->website->db('web')->prepare('DELETE FROM DmN_2FA WHERE memb___id = :user');
            $stmt->execute([':user' => $user]);
		}
    }
	