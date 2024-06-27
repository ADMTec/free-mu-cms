<?php
    in_file();

    use Gettext\Translations;
	use Gettext\Translation;

    class admincp extends controller
    {
        protected $vars = [], $errors = [];
        private $item_info = false;
        private $level = 0;
        private $option = 0;
        private $luck = 0;
        private $skill = 0;
        private $ancient = 0;
        private $exe = [];
        private $harmony = [];
        private $ref = 0;
        private $fenrir = 0;
        private $sockets = [];
        private $item_hex;

        public function __construct(){
            parent::__construct();
            if(ACP_IP_CHECK == true){
                check_ip_white_list(ACP_IP_WHITE_LIST);
            }
            $this->load->helper('website');
            $this->load->lib('session', ['DmNCMS']);
            $this->load->lib("pagination");
            $this->load->helper('webshop');
            $this->load->model('admin');
        }

        public function index(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                $this->vars['total_acccounts'] = $this->Madmin->total_accounts();
                $this->vars['total_characters'] = $this->Madmin->total_characters();
                $this->vars['total_guilds'] = $this->Madmin->total_guilds();
                $this->vars['total_online'] = $this->Madmin->total_online();
                $this->vars['stats'] = $this->Madmin->load_statistics();
                $this->vars['login_attemts'] = $this->Madmin->load_last_admin_login_attemts();
                $this->vars['tickets'] = $this->Madmin->check_unreplied_tickets();
                $this->vars['cron'] = $this->Madmin->get_last_cron_run();
                $this->load->view('admincp' . DS . 'view.index', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

		public function login(){
            if(count($_POST) > 0){
                foreach($_POST as $key => $value){
                    $this->Madmin->$key = $value;
                }
                if(!isset($this->Madmin->vars['username']))
                    $this->vars['error'] = 'Please enter username.'; 
                else{
                    if($this->Madmin->vars['username'] == '')
                        $this->vars['error'] = 'Please enter username.'; 
                    else{
                        if(!isset($this->Madmin->vars['password']))
                            $this->vars['error'] = 'Please enter password.'; 
                        else{
                            if($this->Madmin->vars['password'] == '')
                                $this->vars['error'] = 'Please enter password.'; 
                            else{
                                if(!$this->Madmin->valid_username($this->Madmin->vars['username']))
                                    $this->vars['error'] = 'Invalid Username.'; 
                                else{
                                    if(!$this->Madmin->valid_username($this->Madmin->vars['password']))
                                        $this->vars['error'] = 'Invalid Password.'; 
                                    else{
                                        if(defined('PINCODE') && PINCODE != ''){
                                            $pincode = str_split(PINCODE);
                                            if(!isset($this->Madmin->vars['first']) || !is_numeric($this->Madmin->vars['first']) || strlen($this->Madmin->vars['first']) > 1)
                                                $this->vars['error'] = 'Wrong pincode entered.';
                                            if(!isset($this->Madmin->vars['second']) || !is_numeric($this->Madmin->vars['second']) || strlen($this->Madmin->vars['second']) > 1)
                                                $this->vars['error'] = 'Wrong pincode entered.';
                                            if(!isset($this->Madmin->vars['third']) || !is_numeric($this->Madmin->vars['third']) || strlen($this->Madmin->vars['third']) > 1)
                                                $this->vars['error'] = 'Wrong pincode entered.';
                                            if($pincode[$this->session->userdata(['pincode' => 'first'])] != $this->Madmin->vars['first'])
                                                $this->vars['error'] = 'Wrong pincode entered.';
                                            if($pincode[$this->session->userdata(['pincode' => 'second'])] != $this->Madmin->vars['second'])
                                                $this->vars['error'] = 'Wrong pincode entered.';
                                            if($pincode[$this->session->userdata(['pincode' => 'third'])] != $this->Madmin->vars['third'])
                                                $this->vars['error'] = 'Wrong pincode entered.';
                                        }
                                        if(!isset($this->vars['error'])){
                                            if($this->Madmin->login_admin()){
                                                $this->session->unset_session_key('pincode');
                                                if(isset($_GET['return']) && $_GET['return'] != ''){
                                                    header('Location: ' . $this->config->base_url . htmlspecialchars($_GET['return']));
                                                } else{
                                                    header('Location: ' . $this->config->base_url . ACPURL . '');
                                                }
                                            } else{
                                                $this->vars['error'] = 'Wrong username and/or password.';
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            if(defined('PINCODE') && PINCODE != ''){
                $numbers = str_split(PINCODE);
                $first_pos = array_rand($numbers, 1);
                $this->vars['first'] = ['placeholder' => 'Enter ' . $this->Madmin->pin_number_to_text($first_pos) . ' Nr Of Pin'];
                unset($numbers[$first_pos]);
                $second_pos = array_rand($numbers, 1);
                $this->vars['second'] = ['placeholder' => 'Enter ' . $this->Madmin->pin_number_to_text($second_pos) . ' Nr Of Pin'];
                unset($numbers[$second_pos]);
                $third_pos = array_rand($numbers, 1);
                $this->vars['third'] = ['placeholder' => 'Enter ' . $this->Madmin->pin_number_to_text($third_pos) . ' Nr Of Pin'];
                unset($numbers[$third_pos]);
                $this->session->register('pincode', ['first' => $first_pos, 'second' => $second_pos, 'third' => $third_pos,]);
            }
            $this->load->view('admincp' . DS . 'view.login', $this->vars);
        }

        public function logout(){
            $this->session->unset_session_key('admin');
            header('Location: ' . $this->config->base_url . ACPURL . '');
        }

		public function manage_settings($type = 'main'){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                if($type != 'reset' && $type != 'greset' && $type != 'donate' && $type != 'wcoin-exchange' && $type != 'referral' && $type != 'vip' && $type != 'event-timers' && $type != 'rankings' && $type != 'tables' && $type != 'buylevel' && $type != 'email' && $type != 'registration' && $type != 'security' && $type != 'lostpassword' && $type != 'changeclass' && $type != 'votereward' && $type != 'scheduler'){
                    if(preg_match('/\bcharacter\w*\b/', $type) || preg_match('/\bshop\w*\b/', $type) || preg_match('/\bcredits\w*\b/', $type)){
                        $this->vars['servers'] = $this->website->server_list();
                        if(isset($_POST['switch_server_file'])){
                            $_SESSION['default_key'] = $_POST['switch_server_file'];
                        }
                        $default_key = isset($_SESSION['default_key']) ? $_SESSION['default_key'] : array_key_first($this->vars['servers']);
                        $this->vars['default'] = $default_key;
                    }
                    $file = isset($this->vars['default']) ? '_' . $this->vars['default'] : '';
                    if(isset($_POST['edit_config'])){
                        $this->config->write_xml($type . $file, $_POST);
                        $this->vars['success'] = 'Configuration successfully edited';
                    }
                    $this->config->config_file($type . $file);
                } else{
                    $this->vars['server_list'] = $this->website->server_list();
                    $this->vars['default_server'] = array_key_first($this->vars['server_list']);
                    switch($type){
                        default:
                        case 'reset':
                            $this->vars['reset_config'] = $this->config->values('reset_config');
                            break;
                        case 'greset':
                            $this->vars['greset_config'] = $this->config->values('greset_config');
                            break;
                        case 'donate':
                            $this->vars['packages_paypal'] = $this->Madmin->load_paypal_packages();
                            $this->vars['packages_twocheckout'] = $this->Madmin->load_twocheckout_packages();
                            $this->vars['packages_pagseguro'] = $this->Madmin->load_pagseguro_packages();
                            $this->vars['packages_paycall'] = $this->Madmin->load_paycall_packages();
                            $this->vars['packages_interkassa'] = $this->Madmin->load_interkassa_packages();
                            $this->vars['packages_cuenta_digital'] = $this->Madmin->load_cuenta_digital_packages();
                            break;
                        case 'referral':
                            $this->vars['reward_list'] = $this->Madmin->load_refferal_reward_list();
                            break;
                        case 'vip':
                            $this->vars['vip_config'] = $this->config->values('vip_config');
                            $this->vars['vip_packages'] = $this->Madmin->load_vip_packages();
                            break;
                        case 'event-timers':
                            $this->vars['event_config'] = $this->config->values('event_config', 'events');
                            break;
                        case 'rankings':
                            $this->vars['rankings_config'] = $this->config->values('rankings_config');
                            break;
                        case 'tables':
                            $this->vars['pre_defined_table_config'] = $this->config->values('pre_defined_table_config');
                            $this->vars['table_config'] = $this->config->values('table_config');
                            break;
                        case 'buylevel':
                            $this->vars['buylevel_config'] = $this->config->values('buylevel_config');
                            break;
                        case 'email':
                            $this->vars['email_config'] = $this->config->values('email_config');
                            break;
                        case 'registration':
                            $this->vars['registration_config'] = $this->config->values('registration_config');
                            break;
                        case 'security':
                            $this->vars['security_config'] = $this->config->values('security_config');
                            break;
                        case 'lostpassword':
                            $this->vars['lostpassword_config'] = $this->config->values('lostpassword_config');
                            break;
                        case 'votereward':
                            $this->vars['votereward_config'] = $this->config->values('votereward_config');
                            if(!isset($this->vars['votereward_config']['api_key']) || $this->vars['votereward_config']['api_key'] == ''){
                                $this->vars['votereward_config']['api_key'] = md5(microtime());
                                $this->write_votereward_api_key($this->vars['votereward_config']);
                            }
                            break;
                        case 'changeclass':
                            $this->vars['changeclass_config'] = $this->config->values('change_class_config');
                            if(isset($_POST['active'])){
                                $this->vars['changeclass_config']['active'] = (int)$_POST['active'];
                                $this->vars['changeclass_config']['payment_type'] = (int)$_POST['payment_type'];
								$this->vars['changeclass_config']['min_level'] = (int)$_POST['min_level'];
								$this->vars['changeclass_config']['min_mlevel'] = (int)$_POST['min_mlevel'];
								$this->vars['changeclass_config']['min_resets'] = (int)$_POST['min_resets'];
								$this->vars['changeclass_config']['max_resets'] = (int)$_POST['max_resets'];
								$this->vars['changeclass_config']['min_gresets'] = (int)$_POST['min_gresets'];
								$this->vars['changeclass_config']['max_gresets'] = (int)$_POST['max_gresets'];
                                $this->vars['changeclass_config']['price'] = (int)$_POST['price'];
                                if(!$this->Madmin->save_config_data($this->vars['changeclass_config'], 'change_class_config', false)){
                                    $this->vars['error'] = 'Unable to save configuration.';
                                } else{
                                    $this->vars['success'] = 'Configuration successfully saved.';
                                }
                            }
                            break;
                        case 'scheduler':
                            $this->vars['php_exe'] = getPHPExecutablePath();
                            $this->vars['scheduler_config'] = $this->config->values('scheduler_config');
                            if(!isset($this->vars['scheduler_config']['key']) || $this->vars['scheduler_config']['key'] == ''){
                                $this->vars['scheduler_config']['key'] = md5(microtime());
                                $this->write_scheduler_key($this->vars['scheduler_config']);
                            }
                            break;
                    }
                }
                $this->load->view('admincp' . DS . 'website_settings' . DS . 'view.' . $type, $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

		public function run_cron_task($task = ''){
            if($this->session->userdata(['admin' => 'is_admin'])){
                if($task != ''){
                    $this->vars['scheduler_config'] = $this->config->values('scheduler_config');
                    if(!isset($this->vars['scheduler_config']['key']) || $this->vars['scheduler_config']['key'] == ''){
                        $this->vars['scheduler_config']['key'] = md5(microtime());
                        $this->write_scheduler_key($this->vars['scheduler_config']);
                    }
					
                    $options = [
						"ssl" => [
							"verify_peer" => false,
							"verify_peer_name" => false
						],
						"http" => [
							"header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36"
						]
					];  

                    $info = file_get_contents($this->config->base_url . 'interface/web.php?key=' . $this->vars['scheduler_config']['key'] . '&custom=' . $task, false, stream_context_create($options));
                    echo $info;
                } else{
                    json(['error' => 'Cron task can not be empty!']);
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

		public function change_task_status(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $task = isset($_POST['task']) ? $_POST['task'] : '';
                $val = isset($_POST['val']) ? (int)$_POST['val'] : 0;
                if($task != ''){
                    $this->vars['scheduler_config'] = $this->config->values('scheduler_config');
                    if(array_key_exists($task, $this->vars['scheduler_config']['tasks'])){
                        $this->vars['scheduler_config']['tasks'][$task]['status'] = $val;
                        if(!$this->Madmin->save_config_data($this->vars['scheduler_config'], 'scheduler_config', false)){
                            json(['error' => 'Unable to change task status']);
                        } else{
                            json(['success' => 'Task status updated.']);
                        }
                    } else{
                        json(['error' => 'Cron task not found.']);
                    }
                } else{
                    json(['error' => 'Cron task can not be empty!']);
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

		public function edit_task_schedule(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $task = isset($_POST['task']) ? $_POST['task'] : '';
                $min = isset($_POST['minute']) ? trim($_POST['minute']) : '*';
                $hour = isset($_POST['hour']) ? trim($_POST['hour']) : '*';
                $dom = isset($_POST['dom']) ? trim($_POST['dom']) : '*';
                $month = isset($_POST['month']) ? trim($_POST['month']) : '*';
                $dweek = isset($_POST['dweek']) ? trim($_POST['dweek']) : '*';
                $full = $min . ' ' . $hour . ' ' . $dom . ' ' . $month . ' ' . $dweek;
                if($task != ''){
                    $this->vars['scheduler_config'] = $this->config->values('scheduler_config');
                    if(array_key_exists($task, $this->vars['scheduler_config']['tasks'])){
                        $this->vars['scheduler_config']['tasks'][$task]['time'] = $full;
                        if(!$this->Madmin->save_config_data($this->vars['scheduler_config'], 'scheduler_config', false)){
                            json(['error' => 'Unable to change task status']);
                        } else{
                            json(['success' => 'Task successfully edited.']);
                        }
                    } else{
                        json(['error' => 'Cron task not found.']);
                    }
                } else{
                    json(['error' => 'Cron task can not be empty!']);
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        private function write_scheduler_key($config){
            if(!$this->Madmin->save_config_data($config, 'scheduler_config', false)){
                return false;
            }
            return true;
        }

        private function write_votereward_api_key($config){
            if(!$this->Madmin->save_config_data($config, 'votereward_config', false)){
                return false;
            }
            return true;
        }

		public function change_class_allowed_class_list(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                $this->vars['changeclass_config'] = $this->config->values('change_class_config');
                if(isset($_POST['allowed_class_list'])){
                    foreach($_POST['allowed_class_list'] AS $key => $val){
                        $this->vars['changeclass_config']['class_list'][$key] = $val;
                    }
                    if(!$this->Madmin->save_config_data($this->vars['changeclass_config'], 'change_class_config', false)){
                        $this->vars['error'] = 'Unable to save configuration.';
                    } else{
                        $this->vars['success'] = 'Configuration successfully saved.';
                    }
                }
                $this->vars['class_list'] = $this->website->get_char_class(0, false, true);
                $this->load->view('admincp' . DS . 'website_settings' . DS . 'view.allowed_class_list', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function change_class_skill_tree(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                $this->vars['changeclass_config'] = $this->config->values('change_class_config');
                if(isset($_POST['skilltree_reset_level'])){
                    $this->vars['changeclass_config']['skill_tree']['active'] = (int)$_POST['active'];
                    $this->vars['changeclass_config']['skill_tree']['reset_level'] = (int)$_POST['skilltree_reset_level'];
                    $this->vars['changeclass_config']['skill_tree']['reset_points'] = (int)$_POST['skilltree_reset_points'];
                    $this->vars['changeclass_config']['skill_tree']['points_multiplier'] = (int)$_POST['skilltree_points_multiplier'];
                    if(!$this->Madmin->save_config_data($this->vars['changeclass_config'], 'change_class_config', false)){
                        $this->vars['error'] = 'Unable to save configuration.';
                    } else{
                        $this->vars['success'] = 'Configuration successfully saved.';
                    }
                }
                $this->load->view('admincp' . DS . 'website_settings' . DS . 'view.change_class_skill_tree', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function load_donation_settings(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                if(isset($_POST['server'])){
                    $this->vars['donation_config'] = $this->config->values('donation_config');
                    if(array_key_exists($_POST['server'], $this->vars['donation_config'])){
                        if(isset($_POST['type'])){
                            if(in_array($_POST['type'], ['paypal', 'paymentwall', 'fortumo', 'paygol', '2checkout', 'pagseguro', 'esteria', 'paycall', 'interkassa', 'cuenta_digital'])){
                                if(array_key_exists($_POST['type'], $this->vars['donation_config'][$_POST['server']])){
                                    json(['info' => $this->vars['donation_config'][$_POST['server']][$_POST['type']]]);
                                }
                            } else{
                                json(['error' => 'Donation method not found.']);
                            }
                        } else{
                            json(['error' => 'Please select valid donation method!']);
                        }
                    }
                } else{
                    json(['error' => 'Please select valid server!']);
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

		public function load_ranking_settings(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                if(isset($_POST['server'])){
                    $this->vars['rankings_config'] = $this->config->values('rankings_config');
                    if(array_key_exists($_POST['server'], $this->vars['rankings_config'])){
                        if(isset($_POST['type'])){
                            if(in_array($_POST['type'], ['player', 'guild', 'gens', 'voter', 'killer', 'online', 'online_list', 'bc', 'ds', 'cc', 'cs', 'duels'])){
                                if(array_key_exists($_POST['type'], $this->vars['rankings_config'][$_POST['server']])){
                                    json(['info' => $this->vars['rankings_config'][$_POST['server']][$_POST['type']]]);
                                }
                            } else{
                                json(['error' => 'Rankings config method not found.']);
                            }
                        } else{
                            json(['error' => 'Please select valid rankings config method!']);
                        }
                    }
                } else{
                    json(['error' => 'Please select valid server!']);
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

		public function load_table_settings(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                if(isset($_POST['server'])){
                    $this->vars['table_config'] = $this->config->values('table_config');
                    if(array_key_exists($_POST['server'], $this->vars['table_config'])){
                        json(['info' => $this->vars['table_config'][$_POST['server']]]);
                    }
                } else{
                    json(['error' => 'Please select valid server!']);
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

		public function load_pre_defined_table_settings(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                if(isset($_POST['team'])){
                    $this->vars['pre_defined_table_config'] = $this->config->values('pre_defined_table_config');
                    if(array_key_exists($_POST['team'], $this->vars['pre_defined_table_config'])){
                        json(['info' => $this->vars['pre_defined_table_config'][$_POST['team']]]);
                    } else{
                        json(['error' => 'Settings not found!']);
                    }
                } else{
                    json(['error' => 'Please select valid server!']);
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function load_wcoin_settings(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                if(isset($_POST['server'])){
                    $this->vars['wcoin_config'] = $this->config->values('wcoin_exchange_config');
                    if(array_key_exists($_POST['server'], $this->vars['wcoin_config'])){
                        json(['info' => $this->vars['wcoin_config'][$_POST['server']]]);
                    }
                } else{
                    json(['error' => 'Please select valid server!']);
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function load_referral_settings(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                if(isset($_POST['load_settings'])){
                    $this->vars['referral_config'] = $this->config->values('referral_config');
                    if(!empty($this->vars['referral_config'])){
                        json(['info' => $this->vars['referral_config']]);
                    }
                } else{
                    json(['error' => 'No data submited.']);
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function save_wcoin_settings(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                if(count($_POST) > 0){
                    $this->vars['wcoin_config'] = $this->config->values('wcoin_exchange_config');
                    $reward_coin = isset($_POST['reward_coin']) ? $_POST['reward_coin'] : '';
                    $min_rate = isset($_POST['min_rate']) ? $_POST['min_rate'] : '';
                    if($reward_coin == '' || !is_numeric($reward_coin))
                        json(['error' => 'Enter valid price coins.']); else{
                        if($min_rate == '' || !preg_match('/^\d*$/', $min_rate))
                            json(['error' => 'Enter valid min exchange rate.']); else{
                            if(array_key_exists($_POST['server'], $this->vars['wcoin_config'])){
                                $this->vars['wcoin_config'][$_POST['server']] = ['active' => $_POST['active'], 'reward_coin' => $reward_coin, 'credits_type' => $_POST['credits_type'], 'change_back' => $_POST['change_back'], 'min_rate' => $min_rate, 'display_wcoins' => (int)$_POST['display_wcoins']];
                            } else{
                                $new_config = [$_POST['server'] => ['active' => $_POST['active'], 'reward_coin' => $reward_coin, 'credits_type' => $_POST['credits_type'], 'change_back' => $_POST['change_back'], 'min_rate' => $min_rate, 'display_wcoins' => (int)$_POST['display_wcoins']]];
                                $this->vars['wcoin_config'] = array_merge($this->vars['wcoin_config'], $new_config);
                            }
                            if(!$this->Madmin->save_config_data($this->vars['wcoin_config'], 'wcoin_exchange_config')){
                                json(['error' => 'Unable to save configuration.']);
                            } else{
                                json(['success' => 'Configuration successfully saved.']);
                            }
                        }
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function save_rankings_status(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                if(isset($_POST['server'], $_POST['status'])){
                    $this->vars['rankings_config'] = $this->config->values('rankings_config');
                    if(array_key_exists($_POST['server'], $this->vars['rankings_config'])){
                        if(array_key_exists('active', $this->vars['rankings_config'][$_POST['server']])){
                            $this->vars['rankings_config'][$_POST['server']]['active'] = (int)$_POST['status'];
                        } else{
                            $new_config = ['active' => (int)$_POST['status']];
                            $this->vars['rankings_config'][$_POST['server']] = array_merge($this->vars['rankings_config'][$_POST['server']], $new_config);
                            ksort($this->vars['rankings_config'][$_POST['server']]);
                        }
                    } else{
                        $new_config = [$_POST['server'] => ['active' => (int)$_POST['status']]];
                        $this->vars['rankings_config'] = array_merge($this->vars['rankings_config'], $new_config);
                        ksort($this->vars['rankings_config']);
                    }
                    if(!$this->Madmin->save_config_data($this->vars['rankings_config'], 'rankings_config', false)){
                        json(['error' => 'Unable to save configuration.']);
                    } else{
                        json(['success' => 'Configuration successfully saved.']);
                    }
                } else{
                    json(['error' => 'Please select valid server & status']);
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function reload_rankings_status(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                if(isset($_POST['server'])){
                    $this->vars['rankings_config'] = $this->config->values('rankings_config');
                    if(array_key_exists($_POST['server'], $this->vars['rankings_config'])){
                        if(array_key_exists('active', $this->vars['rankings_config'][$_POST['server']])){
                            json(['status' => $this->vars['rankings_config'][$_POST['server']]['active']]);
                        } else{
                            json(['status' => 0]);
                        }
                    } else{
                        json(['status' => 0]);
                    }
                } else{
                    json(['error' => 'No data submited.']);
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function save_ranking_settings(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                if(count($_POST) > 0){
                    if(isset($_POST['rankings_type']) && in_array($_POST['rankings_type'], ['player', 'guild', 'gens', 'voter', 'killer', 'online', 'online_list', 'bc', 'ds', 'cc', 'cs', 'duels'])){
                        $this->vars['rankings_config'] = $this->config->values('rankings_config');
                        $server = $_POST['server'];
                        $type = $_POST['rankings_type'];
                        unset($_POST['server']);
                        unset($_POST['rankings_type']);
                        foreach($_POST AS $key => $value){
                            if(in_array($key, ['is_sidebar_module', 'count', 'count_in_sidebar', 'cache_time', 'display_resets', 'display_gresets', 'display_master_level', 'display_status', 'display_gms', 'display_country', 'order_by']))
                                $value = (int)$value;
                            $this->vars['rankings_config'][$server][$type][$key] = $value;
                        }
                    } else{
                        json(['error' => 'Invalid rankings.']);
                    }
                    $this->vars['rankings_config'][$server] = array_replace(array_flip(['active', 'player', 'guild', 'gens', 'voter', 'killer', 'online', 'online_list', 'bc', 'ds', 'cc', 'cs', 'duels']), $this->vars['rankings_config'][$server]);
                    foreach($this->vars['rankings_config'][$server] AS $key => $value){
                        if(!is_array($value) && $key != 'active'){
                            unset($this->vars['rankings_config'][$server][$key]);
                        }
                    }
                    if(!$this->Madmin->save_config_data($this->vars['rankings_config'], 'rankings_config', false)){
                        json(['error' => 'Unable to save configuration.']);
                    } else{
                        json(['success' => 'Configuration successfully saved.']);
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function save_table_settings(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                if(count($_POST) > 0){
                    $this->vars['table_config'] = $this->config->values('table_config');
                    $server = $_POST['server'];
                    unset($_POST['server']);
                    $db = '';
                    foreach($_POST AS $key => $value){
                        list($first, $second) = explode('-', $key);
                        if(preg_match('/(.*)_custom-db/', $key, $matches)){
                            $db = $_POST[$matches[0]];
                        }
                        if(in_array($first, ['resets', 'grand_resets'])){
                            if($second == 'db' && $value == ''){
                                $this->vars['errors'][] = 'Please select ' . $first . ' database.';
                            }
                            if($second == 'table' && $value == ''){
                                $this->vars['errors'][] = 'Please enter ' . $first . ' table name.';
                            }
                            //if($second == 'column' && $value == ''){
                            //    $this->vars['errors'][] = 'Please enter '.$first.' column name.';
                            //}
                            if($second == 'identifier_column' && $value == ''){
                                $this->vars['errors'][] = 'Please enter ' . $first . ' identifier column name.';
                            }
                        }
                        if(!preg_match('/(.*)_custom/', $first)){
                            $this->vars['table_config'][$server][$first] = ['db' => ($db != '') ? $db : $_POST[$first . '-db'], 'table' => $_POST[$first . '-table'], 'column' => $_POST[$first . '-column'], 'identifier_column' => $_POST[$first . '-identifier_column'],];
                            if($first == 'duels' || $first == 'cc'){
                                $this->vars['table_config'][$server][$first]['column2'] = $_POST[$first . '-column2'];
                            }
                            if($first == 'cc'){
                                $this->vars['table_config'][$server][$first]['column3'] = $_POST[$first . '-column3'];
                            }
                        }
                    }
                    if(isset($this->vars['errors']) && count($this->vars['errors']) > 0){
                        foreach($this->vars['errors'] AS $error){
                            json(['error' => $error]);
                        }
                    } else{
                        if(!$this->Madmin->save_config_data($this->vars['table_config'], 'table_config', false)){
                            json(['error' => 'Unable to save configuration.']);
                        } else{
                            json(['success' => 'Configuration successfully saved.']);
                        }
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function save_timer_settings(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                if(count($_POST) > 0){
                    $this->vars['event_config'] = $this->config->values('event_config');
                    if(array_key_exists('events', $this->vars['event_config'])){
                        if(array_key_exists('active', $this->vars['event_config']['events'])){
                            $this->vars['event_config']['events']['active'] = $_POST['active'];
                        } else{
                            $new_config = ['active' => $_POST['active']];
                            $this->vars['event_config']['events'] = array_merge($this->vars['event_config']['events'], $new_config);
                            ksort($this->vars['event_config']['events']);
                        }
                    } else{
                        $this->vars['event_config'] = ['events' => ['active' => $_POST['active']]];
                    }
                    if(!$this->Madmin->save_config_data($this->vars['event_config'], 'event_config')){
                        json(['error' => 'Unable to save configuration.']);
                    } else{
                        json(['success' => 'Configuration successfully saved.']);
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function delete_event_timer(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                if(count($_POST) > 0){
                    $this->vars['event_config'] = $this->config->values('event_config');
                    if(array_key_exists('events', $this->vars['event_config'])){
                        if(array_key_exists('event_timers', $this->vars['event_config']['events'])){
                            if(array_key_exists($_POST['id'], $this->vars['event_config']['events']['event_timers'])){
                                unset($this->vars['event_config']['events']['event_timers'][$_POST['id']]);
                                if(!$this->Madmin->save_config_data($this->vars['event_config'], 'event_config')){
                                    json(['error' => 'Unable to delete event.']);
                                } else{
                                    json(['success' => 'Event successfully removed.']);
                                }
                            } else{
                                json(['error' => 'Event not found.']);
                            }
                        } else{
                            json(['error' => 'Unable to delete event.']);
                        }
                    } else{
                        json(['error' => 'Unable to delete event.']);
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function add_event_timers(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                if(isset($_POST['add_event_timer'])){
                    $name = isset($_POST['name']) ? $_POST['name'] : '';
                    $days = isset($_POST['days']) ? $_POST['days'] : '';
                    if($name == '')
                        $this->vars['error'] = 'Please enter event name'; else{
                        if($days == '')
                            $this->vars['error'] = 'Please select event days'; else{
                            if(in_array(0, $days)){
                                $days = 0;
                            }
                            if($days == 0){
                                $time = isset($_POST['time']) ? $_POST['time'] : '';
                            } else{
                                if(in_array(1, $days)){
                                    $monday = isset($_POST['time_monday']) ? $_POST['time_monday'] : '';
                                }
                                if(in_array(2, $days)){
                                    $tuesday = isset($_POST['time_tuesday']) ? $_POST['time_tuesday'] : '';
                                }
                                if(in_array(3, $days)){
                                    $wednesday = isset($_POST['time_wednesday']) ? $_POST['time_wednesday'] : '';
                                }
                                if(in_array(4, $days)){
                                    $thursday = isset($_POST['time_thursday']) ? $_POST['time_thursday'] : '';
                                }
                                if(in_array(5, $days)){
                                    $friday = isset($_POST['time_friday']) ? $_POST['time_friday'] : '';
                                }
                                if(in_array(6, $days)){
                                    $saturday = isset($_POST['time_saturday']) ? $_POST['time_saturday'] : '';
                                }
                                if(in_array(7, $days)){
                                    $sunday = isset($_POST['time_sunday']) ? $_POST['time_sunday'] : '';
                                }
                            }
                            if(isset($time)){
                                if($time == '')
                                    $this->vars['error'] = 'Please enter event time'; else{
                                    if(!$this->validate_timers($time)){
                                        $this->vars['error'] = 'Wrongly formated time. Time range: 00:00:00 - 23:59:59';
                                    }
                                }
                            }
                            if(isset($monday)){
                                if($monday == '')
                                    $this->vars['error'] = 'Please enter event time for monday'; else{
                                    if(!$this->validate_timers($monday)){
                                        $this->vars['error'] = 'Wrongly formated time for monday. Time range: 00:00:00 - 23:59:59';
                                    }
                                }
                            }
                            if(isset($tuesday)){
                                if($tuesday == '')
                                    $this->vars['error'] = 'Please enter event time for tuesday'; else{
                                    if(!$this->validate_timers($tuesday)){
                                        $this->vars['error'] = 'Wrongly formated time for tuesday. Time range: 00:00:00 - 23:59:59';
                                    }
                                }
                            }
                            if(isset($wednesday)){
                                if($wednesday == '')
                                    $this->vars['error'] = 'Please enter event time for wednesday'; else{
                                    if(!$this->validate_timers($wednesday)){
                                        $this->vars['error'] = 'Wrongly formated time for wednesday. Time range: 00:00:00 - 23:59:59';
                                    }
                                }
                            }
                            if(isset($thursday)){
                                if($thursday == '')
                                    $this->vars['error'] = 'Please enter event time for thursday'; else{
                                    if(!$this->validate_timers($thursday)){
                                        $this->vars['error'] = 'Wrongly formated time for thursday. Time range: 00:00:00 - 23:59:59';
                                    }
                                }
                            }
                            if(isset($friday)){
                                if($friday == '')
                                    $this->vars['error'] = 'Please enter event time for friday'; else{
                                    if(!$this->validate_timers($friday)){
                                        $this->vars['error'] = 'Wrongly formated time for friday. Time range: 00:00:00 - 23:59:59';
                                    }
                                }
                            }
                            if(isset($saturday)){
                                if($saturday == '')
                                    $this->vars['error'] = 'Please enter event time for saturday'; else{
                                    if(!$this->validate_timers($saturday)){
                                        $this->vars['error'] = 'Wrongly formated time for saturday. Time range: 00:00:00 - 23:59:59';
                                    }
                                }
                            }
                            if(isset($sunday)){
                                if($sunday == '')
                                    $this->vars['error'] = 'Please enter event time for sunday'; else{
                                    if(!$this->validate_timers($sunday)){
                                        $this->vars['error'] = 'Wrongly formated time for sunday. Time range: 00:00:00 - 23:59:59';
                                    }
                                }
                            }
                            if(!isset($this->vars['error'])){
                                $this->vars['event_config'] = $this->config->values('event_config');
                                if(!is_array($days)){
                                    $new_config = ['name' => $name, 'days' => $time];
                                } else{
                                    $d = [];
                                    foreach($days AS $key => $value){
                                        if($value == 1)
                                            $d[$value] = $monday;
                                        if($value == 2)
                                            $d[$value] = $tuesday;
                                        if($value == 3)
                                            $d[$value] = $wednesday;
                                        if($value == 4)
                                            $d[$value] = $thursday;
                                        if($value == 5)
                                            $d[$value] = $friday;
                                        if($value == 6)
                                            $d[$value] = $saturday;
                                        if($value == 7)
                                            $d[$value] = $sunday;
                                    }
                                    $new_config = ['name' => $name, 'days' => $d];
                                }
                                if(array_key_exists('events', $this->vars['event_config'])){
                                    if(!array_key_exists('event_timers', $this->vars['event_config']['events'])){
                                        $this->vars['event_config']['events'] = array_merge($this->vars['event_config']['events'], ['event_timers' => [1 => $new_config]]);
                                    } else{
                                        array_push($this->vars['event_config']['events']['event_timers'], $new_config);
                                    }
                                    ksort($this->vars['event_config']['events']);
                                } else{
                                    $this->vars['event_config'] = ['events' => ['active' => 1, 'event_timers' => [1 => $new_config]]];
                                }
                                if(!$this->Madmin->save_config_data($this->vars['event_config'], 'event_config')){
                                    $this->vars['error'] = 'Unable to add event.';
                                } else{
                                    $this->vars['success'] = 'Event successfully added.';
                                }
                            }
                        }
                    }
                }
                $this->load->view('admincp' . DS . 'website_settings' . DS . 'view.add_event_timer', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function edit_event_timer($id = ''){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                if($id == ''){
                    $this->vars['event_not_found'] = 'Event not found';
                } else{
                    $this->vars['event_config'] = $this->config->values('event_config');
                    if(array_key_exists($id, $this->vars['event_config']['events']['event_timers'])){
                        $this->vars['event_data'] = $this->vars['event_config']['events']['event_timers'][$id];
                        if(isset($_POST['edit_event_timer'])){
                            $name = isset($_POST['name']) ? $_POST['name'] : '';
                            $days = isset($_POST['days']) ? $_POST['days'] : '';
                            if($name == '')
                                $this->vars['error'] = 'Please enter event name'; else{
                                if($days == '')
                                    $this->vars['error'] = 'Please select event days'; else{
                                    if(in_array(0, $days)){
                                        $days = 0;
                                    }
                                    if($days == 0){
                                        $time = isset($_POST['time']) ? $_POST['time'] : '';
                                    } else{
                                        if(in_array(1, $days)){
                                            $monday = isset($_POST['time_monday']) ? $_POST['time_monday'] : '';
                                        }
                                        if(in_array(2, $days)){
                                            $tuesday = isset($_POST['time_tuesday']) ? $_POST['time_tuesday'] : '';
                                        }
                                        if(in_array(3, $days)){
                                            $wednesday = isset($_POST['time_wednesday']) ? $_POST['time_wednesday'] : '';
                                        }
                                        if(in_array(4, $days)){
                                            $thursday = isset($_POST['time_thursday']) ? $_POST['time_thursday'] : '';
                                        }
                                        if(in_array(5, $days)){
                                            $friday = isset($_POST['time_friday']) ? $_POST['time_friday'] : '';
                                        }
                                        if(in_array(6, $days)){
                                            $saturday = isset($_POST['time_saturday']) ? $_POST['time_saturday'] : '';
                                        }
                                        if(in_array(7, $days)){
                                            $sunday = isset($_POST['time_sunday']) ? $_POST['time_sunday'] : '';
                                        }
                                    }
                                    if(isset($time)){
                                        if($time == '')
                                            $this->vars['error'] = 'Please enter event time'; else{
                                            if(!$this->validate_timers($time)){
                                                $this->vars['error'] = 'Wrongly formated time. Time range: 00:00:00 - 23:59:59';
                                            }
                                        }
                                    }
                                    if(isset($monday)){
                                        if($monday == '')
                                            $this->vars['error'] = 'Please enter event time for monday'; else{
                                            if(!$this->validate_timers($monday)){
                                                $this->vars['error'] = 'Wrongly formated time for monday. Time range: 00:00:00 - 23:59:59';
                                            }
                                        }
                                    }
                                    if(isset($tuesday)){
                                        if($tuesday == '')
                                            $this->vars['error'] = 'Please enter event time for tuesday'; else{
                                            if(!$this->validate_timers($tuesday)){
                                                $this->vars['error'] = 'Wrongly formated time for tuesday. Time range: 00:00:00 - 23:59:59';
                                            }
                                        }
                                    }
                                    if(isset($wednesday)){
                                        if($wednesday == '')
                                            $this->vars['error'] = 'Please enter event time for wednesday'; else{
                                            if(!$this->validate_timers($wednesday)){
                                                $this->vars['error'] = 'Wrongly formated time for wednesday. Time range: 00:00:00 - 23:59:59';
                                            }
                                        }
                                    }
                                    if(isset($thursday)){
                                        if($thursday == '')
                                            $this->vars['error'] = 'Please enter event time for thursday'; else{
                                            if(!$this->validate_timers($thursday)){
                                                $this->vars['error'] = 'Wrongly formated time for thursday. Time range: 00:00:00 - 23:59:59';
                                            }
                                        }
                                    }
                                    if(isset($friday)){
                                        if($friday == '')
                                            $this->vars['error'] = 'Please enter event time for friday'; else{
                                            if(!$this->validate_timers($friday)){
                                                $this->vars['error'] = 'Wrongly formated time for friday. Time range: 00:00:00 - 23:59:59';
                                            }
                                        }
                                    }
                                    if(isset($saturday)){
                                        if($saturday == '')
                                            $this->vars['error'] = 'Please enter event time for saturday'; else{
                                            if(!$this->validate_timers($saturday)){
                                                $this->vars['error'] = 'Wrongly formated time for saturday. Time range: 00:00:00 - 23:59:59';
                                            }
                                        }
                                    }
                                    if(isset($sunday)){
                                        if($sunday == '')
                                            $this->vars['error'] = 'Please enter event time for sunday'; else{
                                            if(!$this->validate_timers($sunday)){
                                                $this->vars['error'] = 'Wrongly formated time for sunday. Time range: 00:00:00 - 23:59:59';
                                            }
                                        }
                                    }
                                    if(!isset($this->vars['error'])){
                                        if(!is_array($days)){
                                            $new_config = ['name' => $name, 'days' => $time];
                                        } else{
                                            $d = [];
                                            foreach($days AS $key => $value){
                                                if($value == 1)
                                                    $d[$value] = $monday;
                                                if($value == 2)
                                                    $d[$value] = $tuesday;
                                                if($value == 3)
                                                    $d[$value] = $wednesday;
                                                if($value == 4)
                                                    $d[$value] = $thursday;
                                                if($value == 5)
                                                    $d[$value] = $friday;
                                                if($value == 6)
                                                    $d[$value] = $saturday;
                                                if($value == 7)
                                                    $d[$value] = $sunday;
                                            }
                                            $new_config = ['name' => $name, 'days' => $d];
                                        }
                                        $this->vars['event_config']['events']['event_timers'][$id] = $new_config;
                                        $this->vars['event_data'] = $this->vars['event_config']['events']['event_timers'][$id];
                                        ksort($this->vars['event_config']['events']);
                                        if(!$this->Madmin->save_config_data($this->vars['event_config'], 'event_config')){
                                            $this->vars['error'] = 'Unable to edit event.';
                                        } else{
                                            $this->vars['success'] = 'Event successfully edited.';
                                        }
                                    }
                                }
                            }
                        }
                    } else{
                        $this->vars['event_not_found'] = 'Event not found';
                    }
                }
                $this->load->view('admincp' . DS . 'website_settings' . DS . 'view.edit_event_timer', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }
		
		public function save_event_order(){
			if($this->session->userdata(['admin' => 'is_admin'])){
                $this->vars['event_config'] = $this->config->values('event_config');
                $new_array = [];
                foreach($_POST['order'] AS $value){
                    if(array_key_exists($value, $this->vars['event_config']['events']['event_timers'])){
                        $new_array[$value] = $this->vars['event_config']['events']['event_timers'][$value];
                    }
                }
				$this->vars['event_config']['events']['event_timers'] = $new_array;
                if($this->config->save_config_data($this->vars['event_config'], 'event_config', false)){
                    json(['success' => 'Event order changed.']);
                } else{
                    json(['error' => 'Unable to save event order.']);
                }
            } else{
                json(['error' => 'Please login first.']);
            }
        }

        private function validate_timers($time){
            if(strpos($time, ',') !== false){
                $timers = explode(',', $time);
            } else{
                $timers = [$time];
            }
            $is_valid = true;
            foreach($timers AS $timer){
                if(!preg_match("/^((2[0-3]|[01]?[0-9]):[0-5][0-9])|((2[0-3]|[01]?[0-9]):[0-5][0-9]:[0-5][0-9])$/", $timer)){
                    $is_valid = false;
                    break;
                }
            }
            return $is_valid;
        }

        public function save_vip_settings(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                if(count($_POST) > 0){
                    $this->vars['vip_config'] = ['active' => (int)$_POST['active']];
                    if(!$this->Madmin->save_config_data($this->vars['vip_config'], 'vip_config')){
                        json(['error' => 'Unable to save configuration.']);
                    } else{
                        json(['success' => 'Configuration successfully saved.']);
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function save_email_settings(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                if(count($_POST) > 0){
                    $this->vars['email_config'] = [
						'server_email' => $_POST['server_email'], 
						'mail_mode' => (int)$_POST['mail_mode'], 
						'smtp_server' => $_POST['smtp_server'], 
						'smtp_port' => (int)$_POST['smtp_port'], 
						'smtp_username' => $_POST['smtp_username'], 
						'smtp_password' => $_POST['smtp_password'], 
						'welcome_email' => isset($_POST['welcome_email']) ? 1 : 0, 
						'vip_purchase_email' => isset($_POST['vip_purchase_email']) ? 1 : 0, 
						'donate_email_user' => isset($_POST['donate_email_user']) ? 1 : 0, 
						'donate_email_admin' => isset($_POST['donate_email_admin']) ? 1 : 0,
						'support_email_user' => isset($_POST['support_email_user']) ? 1 : 0,
						'support_email_admin' => isset($_POST['support_email_admin']) ? 1 : 0
					];
                    if(!$this->Madmin->save_config_data($this->vars['email_config'], 'email_config', false)){
                        json(['error' => 'Unable to save configuration.']);
                    } else{
                        json(['success' => 'Configuration successfully saved.']);
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function save_security_settings(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                if(count($_POST) > 0){
                    $this->vars['security_config'] = [
                        'captcha_type' => (int)$_POST['captcha_type'], 
                        'recaptcha_pub_key' => $_POST['recaptcha_pub_key'], 
                        'recaptcha_priv_key' => $_POST['recaptcha_priv_key'], 
                        'captcha_on_login' => $_POST['captcha_on_login'],
                        'allow_mail_change' => $_POST['allow_mail_change'],
                        'allow_recover_masterkey' => $_POST['allow_recover_masterkey'],
                        '2fa' => $_POST['2fa']
                    ];
                    if(!$this->Madmin->save_config_data($this->vars['security_config'], 'security_config', false)){
                        json(['error' => 'Unable to save configuration.']);
                    } else{
                        json(['success' => 'Configuration successfully saved.']);
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function save_registration_settings(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                if(count($_POST) > 0){
                    $this->vars['registration_config'] = ['active' => (int)$_POST['active'], 'req_email' => (int)$_POST['req_email'], 'req_secret' => (int)$_POST['req_secret'], 'min_username' => (int)$_POST['min_username'], 'max_username' => (int)$_POST['max_username'], 'min_password' => (int)$_POST['min_password'], 'max_password' => (int)$_POST['max_password'], 'password_strength' => ['atleast_one_lowercase' => (int)$_POST['atleast_one_lowercase'], 'atleast_one_uppercase' => (int)$_POST['atleast_one_uppercase'], 'atleast_one_number' => (int)$_POST['atleast_one_number'], 'atleast_one_symbol' => (int)$_POST['atleast_one_symbol']], 'email_validation' => (int)$_POST['email_validation'], 'generate_password' => (int)$_POST['generate_password'], 'email_domain_check' => (int)$_POST['email_domain_check'], 'domain_whitelist' => trim($_POST['domain_whitelist']), 'accounts_per_email' => (int)$_POST['accounts_per_email']];
                    if(!$this->Madmin->save_config_data($this->vars['registration_config'], 'registration_config', false)){
                        json(['error' => 'Unable to save configuration.']);
                    } else{
                        json(['success' => 'Configuration successfully saved.']);
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function save_lostpassword_settings(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                if(count($_POST) > 0){
                    $this->vars['lostpassword_config'] = ['active' => (int)$_POST['active'], 'method' => (int)$_POST['method']];
                    if(!$this->Madmin->save_config_data($this->vars['lostpassword_config'], 'lostpassword_config', false)){
                        json(['error' => 'Unable to save configuration.']);
                    } else{
                        json(['success' => 'Configuration successfully saved.']);
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function add_vip(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                if(count($_POST) > 0){
                    $title = !empty($_POST['package_title']) ? $_POST['package_title'] : '';
                    $price = (!empty($_POST['price']) && preg_match('/^\d*$/', $_POST['price'])) ? $_POST['price'] : '';
                    $payment_type = (!empty($_POST['payment_type']) && in_array($_POST['payment_type'], [1, 2])) ? $_POST['payment_type'] : '';
                    $server = !empty($_POST['server']) ? $_POST['server'] : '';
                    $time = !empty($_POST['vip_time']) ? $_POST['vip_time'] : '';
                    $time_type = !empty($_POST['vip_time_type']) ? $_POST['vip_time_type'] : '';
					$extend = !empty($_POST['allow_extend']) ? $_POST['allow_extend'] : 1;
                    $reset_price_decrease = (!empty($_POST['reset_price_decrease']) && preg_match('/^\d*$/', $_POST['reset_price_decrease'])) ? $_POST['reset_price_decrease'] : 0;
                    $reset_level_decrease = (!empty($_POST['reset_level_decrease']) && preg_match('/^\d*$/', $_POST['reset_level_decrease'])) ? $_POST['reset_level_decrease'] : 0;
					$reset_bonus_points = (!empty($_POST['reset_bonus_levelup']) && preg_match('/^\d*$/', $_POST['reset_bonus_levelup'])) ? $_POST['reset_bonus_levelup'] : 0;
                    $grand_reset_bonus_credits = (!empty($_POST['grand_reset_bonus_credits']) && preg_match('/^\d*$/', $_POST['grand_reset_bonus_credits'])) ? $_POST['grand_reset_bonus_credits'] : 0;
					$grand_reset_bonus_gcredits = (!empty($_POST['grand_reset_bonus_gcredits']) && preg_match('/^\d*$/', $_POST['grand_reset_bonus_gcredits'])) ? $_POST['grand_reset_bonus_gcredits'] : 0;
                    $hide_info_discount = (!empty($_POST['hide_info_discount']) && preg_match('/^\d*$/', $_POST['hide_info_discount'])) ? $_POST['hide_info_discount'] : 0;
                    $pk_clear_discount = (!empty($_POST['pk_clear_discount']) && preg_match('/^\d*$/', $_POST['pk_clear_discount'])) ? $_POST['pk_clear_discount'] : 0;
                    $clear_skilltree_discount = (!empty($_POST['clear_skilltree_discount']) && preg_match('/^\d*$/', $_POST['clear_skilltree_discount'])) ? $_POST['clear_skilltree_discount'] : 0;
                    $online_hour_exchange_bonus = (!empty($_POST['online_hour_exchange_bonus']) && preg_match('/^\d*$/', $_POST['online_hour_exchange_bonus'])) ? $_POST['online_hour_exchange_bonus'] : 0;
                    $change_name_discount = (!empty($_POST['change_name_discount']) && preg_match('/^\d*$/', $_POST['change_name_discount'])) ? $_POST['change_name_discount'] : 0;
                    $change_class_discount = (!empty($_POST['change_class_discount']) && preg_match('/^\d*$/', $_POST['change_class_discount'])) ? $_POST['change_class_discount'] : 0;
                    $bonus_credits_for_donate = (!empty($_POST['bonus_credits_for_donate']) && preg_match('/^\d*$/', $_POST['bonus_credits_for_donate'])) ? $_POST['bonus_credits_for_donate'] : 0;
                    $shop_discount = (!empty($_POST['shop_discount']) && preg_match('/^\d*$/', $_POST['shop_discount'])) ? $_POST['shop_discount'] : 0;
					$wcoins = (!empty($_POST['add_wcoins']) && preg_match('/^\d*$/', $_POST['add_wcoins'])) ? $_POST['add_wcoins'] : 0;
					$server_vip_package = (!empty($_POST['server_vip_package'])) ? $_POST['server_vip_package'] : '';
                    $server_bonus_info = (!empty($_POST['server_bonus_info'])) ? $_POST['server_bonus_info'] : '';
                    $connect_member_load = (!empty($_POST['connect_member_load'])) ? str_replace('/', DS, $_POST['connect_member_load']) : '';
                    if($title == '')
                        $this->vars['error'] = 'Please enter package title.'; else{
                        if($price == '')
                            $this->vars['error'] = 'Please enter package price.'; else{
                            if($payment_type == '')
                                $this->vars['error'] = 'Please select valid payment method.'; else{
                                if($server == '')
                                    $this->vars['error'] = 'Please select valid server.'; else{
                                    if($time == '')
                                        $this->vars['error'] = 'Please select valid time.'; else{
                                        if($time_type == '')
                                            $this->vars['error'] = 'Please select valid time.'; else{
                                            if($this->Madmin->check_vip_package_title($title))
                                                $this->vars['error'] = 'Package with this title already exists.'; else{
                                                if($this->Madmin->add_vip_package($title, $price, $payment_type, $server, $time, $time_type, $extend, $reset_price_decrease, $reset_level_decrease, $reset_bonus_points, $grand_reset_bonus_credits, $grand_reset_bonus_gcredits, $hide_info_discount, $pk_clear_discount, $clear_skilltree_discount, $online_hour_exchange_bonus, $change_name_discount, $change_class_discount, $bonus_credits_for_donate, $shop_discount, $wcoins, $server_vip_package, $server_bonus_info, $connect_member_load)){
                                                    $this->vars['success'] = 'Vip package successfully added.';
                                                } else{
                                                    $this->vars['error'] = 'Unable to add vip package.';
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                $this->vars['query_data'] = $this->config->values('vip_query_config');
                $this->vars['servers'] = $this->website->server_list();
                $this->load->view('admincp' . DS . 'website_settings' . DS . 'view.add_vip', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function edit_vip($id = ''){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                if($this->vars['package_data'] = $this->Madmin->check_vip_status($id)){
                    if(count($_POST) > 0){
                        $title = !empty($_POST['package_title']) ? $_POST['package_title'] : '';
                        $price = (!empty($_POST['price']) && preg_match('/^\d*$/', $_POST['price'])) ? $_POST['price'] : '';
                        $payment_type = (!empty($_POST['payment_type']) && in_array($_POST['payment_type'], [1, 2])) ? $_POST['payment_type'] : '';
                        $server = !empty($_POST['server']) ? $_POST['server'] : '';
                        $time = !empty($_POST['vip_time']) ? $_POST['vip_time'] : '';
                        $time_type = !empty($_POST['vip_time_type']) ? $_POST['vip_time_type'] : '';
                        $extend = !empty($_POST['allow_extend']) ? $_POST['allow_extend'] : 1;
						$reset_price_decrease = (!empty($_POST['reset_price_decrease']) && preg_match('/^\d*$/', $_POST['reset_price_decrease'])) ? $_POST['reset_price_decrease'] : 0;
                        $reset_level_decrease = (!empty($_POST['reset_level_decrease']) && preg_match('/^\d*$/', $_POST['reset_level_decrease'])) ? $_POST['reset_level_decrease'] : 0;
                        $reset_bonus_points = (!empty($_POST['reset_bonus_levelup']) && preg_match('/^\d*$/', $_POST['reset_bonus_levelup'])) ? $_POST['reset_bonus_levelup'] : 0;
						$grand_reset_bonus_credits = (!empty($_POST['grand_reset_bonus_credits']) && preg_match('/^\d*$/', $_POST['grand_reset_bonus_credits'])) ? $_POST['grand_reset_bonus_credits'] : 0;
                        $grand_reset_bonus_gcredits = (!empty($_POST['grand_reset_bonus_gcredits']) && preg_match('/^\d*$/', $_POST['grand_reset_bonus_gcredits'])) ? $_POST['grand_reset_bonus_gcredits'] : 0;
						$hide_info_discount = (!empty($_POST['hide_info_discount']) && preg_match('/^\d*$/', $_POST['hide_info_discount'])) ? $_POST['hide_info_discount'] : 0;
                        $pk_clear_discount = (!empty($_POST['pk_clear_discount']) && preg_match('/^\d*$/', $_POST['pk_clear_discount'])) ? $_POST['pk_clear_discount'] : 0;
                        $clear_skilltree_discount = (!empty($_POST['clear_skilltree_discount']) && preg_match('/^\d*$/', $_POST['clear_skilltree_discount'])) ? $_POST['clear_skilltree_discount'] : 0;
                        $online_hour_exchange_bonus = (!empty($_POST['online_hour_exchange_bonus']) && preg_match('/^\d*$/', $_POST['online_hour_exchange_bonus'])) ? $_POST['online_hour_exchange_bonus'] : 0;
                        $change_name_discount = (!empty($_POST['change_name_discount']) && preg_match('/^\d*$/', $_POST['change_name_discount'])) ? $_POST['change_name_discount'] : 0;
                        $change_class_discount = (!empty($_POST['change_class_discount']) && preg_match('/^\d*$/', $_POST['change_class_discount'])) ? $_POST['change_class_discount'] : 0;
                        $bonus_credits_for_donate = (!empty($_POST['bonus_credits_for_donate']) && preg_match('/^\d*$/', $_POST['bonus_credits_for_donate'])) ? $_POST['bonus_credits_for_donate'] : 0;
                        $shop_discount = (!empty($_POST['shop_discount']) && preg_match('/^\d*$/', $_POST['shop_discount'])) ? $_POST['shop_discount'] : 0;
						$wcoins = (!empty($_POST['add_wcoins']) && preg_match('/^\d*$/', $_POST['add_wcoins'])) ? $_POST['add_wcoins'] : 0;
                        $server_vip_package = (!empty($_POST['server_vip_package'])) ? $_POST['server_vip_package'] : '';
                        $server_bonus_info = (!empty($_POST['server_bonus_info'])) ? $_POST['server_bonus_info'] : '';
                        $connect_member_load = (!empty($_POST['connect_member_load'])) ? str_replace('/', DS, $_POST['connect_member_load']) : '';
                        if($title == '')
                            $this->vars['error'] = 'Please enter package title.'; else{
                            if($price == '')
                                $this->vars['error'] = 'Please enter package price.'; else{
                                if($payment_type == '')
                                    $this->vars['error'] = 'Please select valid payment method.'; else{
                                    if($server == '')
                                        $this->vars['error'] = 'Please select valid server.'; else{
                                        if($time == '')
                                            $this->vars['error'] = 'Please select valid time.'; else{
                                            if($time_type == '')
                                                $this->vars['error'] = 'Please select valid time.'; else{
                                                if($this->Madmin->check_vip_package_title_for_edit($title, $id))
                                                    $this->vars['error'] = 'Package with this title already exists.'; else{
                                                    if($this->Madmin->edit_vip_package($id, $title, $price, $payment_type, $server, $time, $time_type, $extend, $reset_price_decrease, $reset_level_decrease, $reset_bonus_points, $grand_reset_bonus_credits, $grand_reset_bonus_gcredits, $hide_info_discount, $pk_clear_discount, $clear_skilltree_discount, $online_hour_exchange_bonus, $change_name_discount, $change_class_discount, $bonus_credits_for_donate, $shop_discount, $wcoins, $server_vip_package, $server_bonus_info, $connect_member_load)){
                                                        $this->vars['success'] = 'Vip package successfully edited.';
                                                    } else{
                                                        $this->vars['error'] = 'Unable to edit vip package.';
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    $this->vars['query_data'] = $this->config->values('vip_query_config');
                    $this->vars['servers'] = $this->website->server_list();
                } else{
                    $this->vars['package_error'] = 'Invalid vip package';
                }
                $this->load->view('admincp' . DS . 'website_settings' . DS . 'view.edit_vip', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function add_vip_on_registration($id = ''){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                $id = (preg_match('/^\d*$/', $id)) ? $id : '';
                if($id == '')
                    $this->vars['error'] = 'Invalid vip package'; else{
                    if($this->vars['package_data'] = $this->Madmin->check_vip_status($id)){
                        if($this->vars['package_data']['is_registration_package'] == 1)
                            $this->vars['error'] = 'Package already set as registration package.'; else{
                            $this->Madmin->remove_old_vip_registration_package($this->vars['package_data']['server']);
                            $this->Madmin->add_new_vip_registration_package($id, $this->vars['package_data']['server']);
                            $this->vars['success'] = 'Vip package successfully added to registration.';
                        }
                    } else{
                        $this->vars['error'] = 'Invalid vip package';
                    }
                }
                $this->load->view('admincp' . DS . 'website_settings' . DS . 'view.vipinfo', $this->vars);
            } else{
                $this->login();
            }
        }

        public function remove_vip_from_registration($id = ''){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                $id = (preg_match('/^\d*$/', $id)) ? $id : '';
                if($id == '')
                    $this->vars['error'] = 'Invalid vip package'; else{
                    if($this->vars['package_data'] = $this->Madmin->check_vip_status($id)){
                        if($this->vars['package_data']['is_registration_package'] == 0)
                            $this->vars['error'] = 'Package is not assigned to registration'; else{
                            $this->Madmin->remove_vip_registration_package($id, $this->vars['package_data']['server']);
                            $this->vars['success'] = 'Vip package successfully removed to registration.';
                        }
                    } else{
                        $this->vars['error'] = 'Invalid vip package';
                    }
                }
                $this->load->view('admincp' . DS . 'website_settings' . DS . 'view.vipinfo', $this->vars);
            } else{
                $this->login();
            }
        }

        public function change_vip_status(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $id = (isset($_POST['id']) && preg_match('/^\d*$/', $_POST['id'])) ? $_POST['id'] : '';
                $status = (isset($_POST['status']) && preg_match('/^\d*$/', $_POST['status'])) ? $_POST['status'] : '';
                if($id == '')
                    json(['error' => 'Invalid vip id']); else{
                    if($status == '')
                        json(['error' => 'Invalid vip status']); else{
                        if($this->Madmin->check_vip_status($id)){
                            $this->Madmin->change_vip_status($id, $status);
                            json(['success' => 'Vip package status changed']);
                        } else{
                            json(['error' => 'Invalid vip package']);
                        }
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function delete_vip_package(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $id = (isset($_POST['id']) && preg_match('/^\d*$/', $_POST['id'])) ? $_POST['id'] : '';
                if($id == '')
                    json(['error' => 'Invalid vip id']); else{
                    if($this->Madmin->check_vip_status($id)){
                        $this->Madmin->delete_vip_package($id);
                        json(['success' => 'Vip package successfully removed']);
                    } else{
                        json(['error' => 'Invalid vip package']);
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function save_referral_settings(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                if(count($_POST) > 0){
                    $this->vars['referral_config'] = [
						'active' => (int)$_POST['active'], 
						'reward_on_registration' => (int)$_POST['reward_on_registration'], 
						'reward_type' => (int)$_POST['reward_type'], 
						'claim_type' => (int)$_POST['claim_type'], 
						'compare_ips' => (int)$_POST['compare_ips'],
						'allow_email_invitations' => (int)$_POST['allow_email_invitations'],
						'reward_on_donation' => (int)$_POST['reward_on_donation']
					];
                    if(!$this->Madmin->save_config_data($this->vars['referral_config'], 'referral_config')){
                        json(['error' => 'Unable to save configuration.']);
                    } else{
                        json(['success' => 'Configuration successfully saved.']);
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function save_scheduler_settings(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                if(count($_POST) > 0){
                    $this->vars['scheduler_config'] = $this->config->values('scheduler_config');
                    $this->vars['scheduler_config']['type'] = (int)$_POST['type'];
                    if(!$this->Madmin->save_config_data($this->vars['scheduler_config'], 'scheduler_config', false)){
                        json(['error' => 'Unable to save configuration.']);
                    } else{
                        json(['success' => 'Configuration successfully saved.']);
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function save_votereward_settings(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                if(count($_POST) > 0){
                    $this->vars['votereward_config'] = $this->config->values('votereward_config');
                    if(array_key_exists($_POST['server'], $this->vars['votereward_config'])){
                        $this->vars['votereward_config'][$_POST['server']] = ['active' => $_POST['active'], 'req_char' => $_POST['req_char'], 'req_lvl' => $_POST['req_lvl'], 'req_res' => $_POST['req_res'], 'xtremetop_same_acc_vote' => $_POST['xtremetop_same_acc_vote'], 'xtremetop_link_numbers' => isset($_POST['xtremetop_link_numbers']) ? $_POST['xtremetop_link_numbers'] : '', 'count_down' => isset($_POST['count_down']) ? $_POST['count_down'] : 60, 'is_monthly_reward' => $_POST['is_monthly_reward'], 'amount_of_players_to_reward' => $_POST['amount_of_players_to_reward'], 'reward_formula' => isset($_POST['reward_formula']) ? $_POST['reward_formula'] : '', 'reward_type' => $_POST['reward_type']];
                    } else{
                        $new_config = [$_POST['server'] => ['active' => $_POST['active'], 'req_char' => $_POST['req_char'], 'req_lvl' => $_POST['req_lvl'], 'req_res' => $_POST['req_res'], 'xtremetop_same_acc_vote' => $_POST['xtremetop_same_acc_vote'], 'xtremetop_link_numbers' => isset($_POST['xtremetop_link_numbers']) ? $_POST['xtremetop_link_numbers'] : '', 'count_down' => isset($_POST['count_down']) ? $_POST['count_down'] : 60, 'is_monthly_reward' => $_POST['is_monthly_reward'], 'amount_of_players_to_reward' => $_POST['amount_of_players_to_reward'], 'reward_formula' => isset($_POST['reward_formula']) ? $_POST['reward_formula'] : '', 'reward_type' => $_POST['reward_type']]];
                        $this->vars['votereward_config'] = array_merge($this->vars['votereward_config'], $new_config);
                    }
                    if(!$this->Madmin->save_config_data($this->vars['votereward_config'], 'votereward_config')){
                        json(['error' => 'Unable to save configuration.']);
                    } else{
                        json(['success' => 'Configuration successfully saved.']);
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function save_paypal_settings(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                if(count($_POST) > 0){
                    $this->vars['donation_config'] = $this->config->values('donation_config');
                    if(array_key_exists($_POST['server'], $this->vars['donation_config'])){
                        $this->vars['donation_config'][$_POST['server']]['paypal'] = ['active' => $_POST['active'], 'type' => $_POST['type'], 'sandbox' => $_POST['sandbox'], 'email' => $_POST['email'], 'punish_player' => $_POST['punish_player'], 'reward_type' => $_POST['reward_type'], 'paypal_fee' => $_POST['paypal_fee'], 'paypal_fixed_fee' => $_POST['paypal_fixed_fee'], 'api_username' => $_POST['api_username'], 'api_password' => $_POST['api_password'], 'api_signature' => $_POST['api_signature']];
                    } else{
                        $new_config = [$_POST['server'] => ['paypal' => ['active' => $_POST['active'], 'type' => $_POST['type'], 'sandbox' => $_POST['sandbox'], 'email' => $_POST['email'], 'punish_player' => $_POST['punish_player'], 'reward_type' => $_POST['reward_type'], 'paypal_fee' => $_POST['paypal_fee'], 'paypal_fixed_fee' => $_POST['paypal_fixed_fee'], 'api_username' => $_POST['api_username'], 'api_password' => $_POST['api_password'], 'api_signature' => $_POST['api_signature']]]];
                        $this->vars['donation_config'] = array_merge($this->vars['donation_config'], $new_config);
                    }
                    if(!$this->Madmin->save_config_data($this->vars['donation_config'], 'donation_config')){
                        json(['error' => 'Unable to save configuration.']);
                    } else{
                        json(['success' => 'Configuration successfully saved.']);
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function save_paymentwall_settings(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                if(count($_POST) > 0){
                    $this->vars['donation_config'] = $this->config->values('donation_config');
                    if(array_key_exists($_POST['server'], $this->vars['donation_config'])){
                        $this->vars['donation_config'][$_POST['server']]['paymentwall'] = ['active' => $_POST['active'], 'api_key' => $_POST['api_key'], 'secret_key' => $_POST['secret_key'], 'sign_version' => $_POST['sign_version'], 'widget' => $_POST['widget'], 'width' => $_POST['width'], 'reward_type' => $_POST['reward_type']];
                    } else{
                        $new_config = [$_POST['server'] => ['paymentwall' => ['active' => $_POST['active'], 'api_key' => $_POST['api_key'], 'secret_key' => $_POST['secret_key'], 'sign_version' => $_POST['sign_version'], 'widget' => $_POST['widget'], 'width' => $_POST['width'], 'reward_type' => $_POST['reward_type']]]];
                        $this->vars['donation_config'] = array_merge($this->vars['donation_config'], $new_config);
                    }
                    if(!$this->Madmin->save_config_data($this->vars['donation_config'], 'donation_config')){
                        json(['error' => 'Unable to save configuration.']);
                    } else{
                        json(['success' => 'Configuration successfully saved.']);
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function save_fortumo_settings(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                if(count($_POST) > 0){
                    $this->vars['donation_config'] = $this->config->values('donation_config');
                    if(array_key_exists($_POST['server'], $this->vars['donation_config'])){
                        $this->vars['donation_config'][$_POST['server']]['fortumo'] = ['active' => $_POST['active'], 'sandbox' => $_POST['sandbox'], 'service_id' => $_POST['service_id'], 'secret' => $_POST['secret'], 'allowed_ip_list' => $_POST['allowed_ip_list'], 'reward_type' => $_POST['reward_type']];
                    } else{
                        $new_config = [$_POST['server'] => ['fortumo' => ['active' => $_POST['active'], 'sandbox' => $_POST['sandbox'], 'service_id' => $_POST['service_id'], 'secret' => $_POST['secret'], 'allowed_ip_list' => $_POST['allowed_ip_list'], 'reward_type' => $_POST['reward_type']]]];
                        $this->vars['donation_config'] = array_merge($this->vars['donation_config'], $new_config);
                    }
                    if(!$this->Madmin->save_config_data($this->vars['donation_config'], 'donation_config')){
                        json(['error' => 'Unable to save configuration.']);
                    } else{
                        json(['success' => 'Configuration successfully saved.']);
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function save_paygol_settings(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                if(count($_POST) > 0){
                    $this->vars['donation_config'] = $this->config->values('donation_config');
                    if(array_key_exists($_POST['server'], $this->vars['donation_config'])){
                        $this->vars['donation_config'][$_POST['server']]['paygol'] = ['active' => $_POST['active'], 'service_id' => $_POST['service_id'], 'reward' => $_POST['reward'], 'reward_type' => $_POST['reward_type'], 'currency_code' => $_POST['currency_code'], 'service_price' => $_POST['service_price']];
                    } else{
                        $new_config = [$_POST['server'] => ['paygol' => ['active' => $_POST['active'], 'service_id' => $_POST['service_id'], 'reward' => $_POST['reward'], 'reward_type' => $_POST['reward_type'], 'currency_code' => $_POST['currency_code'], 'service_price' => $_POST['service_price']]]];
                        $this->vars['donation_config'] = array_merge($this->vars['donation_config'], $new_config);
                    }
                    if(!$this->Madmin->save_config_data($this->vars['donation_config'], 'donation_config')){
                        json(['error' => 'Unable to save configuration.']);
                    } else{
                        json(['success' => 'Configuration successfully saved.']);
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function save_twocheckout_settings(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                if(count($_POST) > 0){
                    $this->vars['donation_config'] = $this->config->values('donation_config');
                    if(array_key_exists($_POST['server'], $this->vars['donation_config'])){
                        $this->vars['donation_config'][$_POST['server']]['2checkout'] = ['active' => $_POST['active'], 'seller_id' => $_POST['seller_id'], 'private_key' => $_POST['private_key'], 'private_secret_word' => $_POST['private_secret_word'], 'reward_type' => $_POST['reward_type']];
                    } else{
                        $new_config = [$_POST['server'] => ['2checkout' => ['active' => $_POST['active'], 'seller_id' => $_POST['seller_id'], 'private_key' => $_POST['private_key'], 'private_secret_word' => $_POST['private_secret_word'], 'reward_type' => $_POST['reward_type']]]];
                        $this->vars['donation_config'] = array_merge($this->vars['donation_config'], $new_config);
                    }
                    if(!$this->Madmin->save_config_data($this->vars['donation_config'], 'donation_config')){
                        json(['error' => 'Unable to save configuration.']);
                    } else{
                        json(['success' => 'Configuration successfully saved.']);
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function save_pagseguro_settings(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                if(count($_POST) > 0){
                    $this->vars['donation_config'] = $this->config->values('donation_config');
                    if(array_key_exists($_POST['server'], $this->vars['donation_config'])){
                        $this->vars['donation_config'][$_POST['server']]['pagseguro'] = ['active' => $_POST['active'], 'email' => $_POST['email'], 'token' => $_POST['token'], 'reward_type' => $_POST['reward_type']];
                    } else{
                        $new_config = [$_POST['server'] => ['pagseguro' => ['active' => $_POST['active'], 'email' => $_POST['email'], 'token' => $_POST['token'], 'reward_type' => $_POST['reward_type']]]];
                        $this->vars['donation_config'] = array_merge($this->vars['donation_config'], $new_config);
                    }
                    if(!$this->Madmin->save_config_data($this->vars['donation_config'], 'donation_config')){
                        json(['error' => 'Unable to save configuration.']);
                    } else{
                        json(['success' => 'Configuration successfully saved.']);
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function save_paycall_settings(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                if(count($_POST) > 0){
                    $this->vars['donation_config'] = $this->config->values('donation_config');
                    if(array_key_exists($_POST['server'], $this->vars['donation_config'])){
                        $this->vars['donation_config'][$_POST['server']]['paycall'] = ['active' => $_POST['active'], 'sandbox' => $_POST['sandbox'], 'business_code' => $_POST['business_code'], 'reward_type' => $_POST['reward_type']];
                    } else{
                        $new_config = [$_POST['server'] => ['paycall' => ['active' => $_POST['active'], 'sandbox' => $_POST['sandbox'], 'business_code' => $_POST['business_code'], 'reward_type' => $_POST['reward_type']]]];
                        $this->vars['donation_config'] = array_merge($this->vars['donation_config'], $new_config);
                    }
                    if(!$this->Madmin->save_config_data($this->vars['donation_config'], 'donation_config')){
                        json(['error' => 'Unable to save configuration.']);
                    } else{
                        json(['success' => 'Configuration successfully saved.']);
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function save_interkassa_settings(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                if(count($_POST) > 0){
                    $this->vars['donation_config'] = $this->config->values('donation_config');
                    if(array_key_exists($_POST['server'], $this->vars['donation_config'])){
                        $this->vars['donation_config'][$_POST['server']]['interkassa'] = ['active' => $_POST['active'], 'shop_id' => $_POST['shop_id'], 'secret_key' => $_POST['secret_key'], 'reward_type' => $_POST['reward_type']];
                    } else{
                        $new_config = [$_POST['server'] => ['interkassa' => ['active' => $_POST['active'], 'shop_id' => $_POST['shop_id'], 'secret_key' => $_POST['secret_key'], 'reward_type' => $_POST['reward_type']]]];
                        $this->vars['donation_config'] = array_merge($this->vars['donation_config'], $new_config);
                    }
                    if(!$this->Madmin->save_config_data($this->vars['donation_config'], 'donation_config')){
                        json(['error' => 'Unable to save configuration.']);
                    } else{
                        json(['success' => 'Configuration successfully saved.']);
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function save_cuenta_digital_settings(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                if(count($_POST) > 0){
                    $this->vars['donation_config'] = $this->config->values('donation_config');
                    if(array_key_exists($_POST['server'], $this->vars['donation_config'])){
                        $this->vars['donation_config'][$_POST['server']]['cuenta_digital'] = ['active' => $_POST['active'], 'api_type' => $_POST['api_type'], 'account_id' => $_POST['account_id'], 'voucher_api_password' => $_POST['voucher_api_password'], 'reward_type' => $_POST['reward_type']];
                    } else{
                        $new_config = [$_POST['server'] => ['cuenta_digital' => ['active' => $_POST['active'], 'api_type' => $_POST['api_type'], 'account_id' => $_POST['account_id'], 'voucher_api_password' => $_POST['voucher_api_password'], 'reward_type' => $_POST['reward_type']]]];
                        $this->vars['donation_config'] = array_merge($this->vars['donation_config'], $new_config);
                    }
                    if(!$this->Madmin->save_config_data($this->vars['donation_config'], 'donation_config')){
                        json(['error' => 'Unable to save configuration.']);
                    } else{
                        json(['success' => 'Configuration successfully saved.']);
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function list_guides(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                $this->vars['guides'] = $this->Madmin->list_guides();
                $this->load->view('admincp' . DS . 'guides_manager' . DS . 'view.list_guides', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }
		
		public function list_drops(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                $this->vars['guides'] = $this->Madmin->list_drops();
                $this->load->view('admincp' . DS . 'drop_manager' . DS . 'view.list_drops', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function delete_guide($id = ''){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                if($id == ''){
                    $this->vars['error'] = 'Guide not found';
                } else{
                    if($this->Madmin->check_guide($id)){
                        $this->Madmin->delete_guide($id);
                        $this->vars['success'] = 'Guide successfully removed';
                    } else{
                        $this->vars['error'] = 'Guide not found';
                    }
                }
                $this->vars['guides'] = $this->Madmin->list_guides();
                $this->load->view('admincp' . DS . 'guides_manager' . DS . 'view.list_guides', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }
		
		public function delete_drop($id = ''){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                if($id == ''){
                    $this->vars['error'] = 'Drop not found';
                } else{
                    if($this->Madmin->check_drop($id)){
                        $this->Madmin->delete_drop($id);
                        $this->vars['success'] = 'Drop successfully removed';
                    } else{
                        $this->vars['error'] = 'Drop not found';
                    }
                }
                $this->vars['guides'] = $this->Madmin->list_drops();
                $this->load->view('admincp' . DS . 'drop_manager' . DS . 'view.list_drops', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function edit_guide($id = ''){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                if($id == ''){
                    $this->vars['error'] = 'Guide not found';
                } else{
                    $this->vars['guide'] = $this->Madmin->check_guide($id);
                    if(!$this->vars['guide']){
                        $this->vars['error'] = 'Guide not found';
                    } else{
                        if(count($_POST) > 0){
                            foreach($_POST as $key => $value){
                                $this->Madmin->$key = trim($value);
                            }
                            if(!isset($_POST['title']))
                                $this->vars['error'] = 'Please enter guide title.'; else{
                                if($_POST['title'] == '')
                                    $this->vars['error'] = 'Please enter guide title.'; else{
                                    if(!isset($_POST['guide']))
                                        $this->vars['error'] = 'Please enter guide.'; else{
                                        if($_POST['guide'] == '')
                                            $this->vars['error'] = 'Please enter guide.'; else{
                                            $this->Madmin->edit_guide($id);
                                            $this->vars['success'] = 'Guide successfully edited.';
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                //$this->vars['guides'] = $this->Madmin->list_guides();
                $this->load->view('admincp' . DS . 'guides_manager' . DS . 'view.edit_guide', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }
		
		public function edit_drop($id = ''){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
				$this->vars['cats'] = $this->config->values('drop_config');
                if($id == ''){
                    $this->vars['error'] = 'Drop not found';
                } else{
                    $this->vars['guide'] = $this->Madmin->check_drop($id);
                    if(!$this->vars['guide']){
                        $this->vars['error'] = 'Drop not found';
                    } else{
                        if(count($_POST) > 0){
                            foreach($_POST as $key => $value){
                                $this->Madmin->$key = trim($value);
                            }
                            if(!isset($_POST['title']))
                                $this->vars['error'] = 'Please enter title.'; else{
                                if($_POST['title'] == '')
                                    $this->vars['error'] = 'Please enter title.'; else{
                                    if(!isset($_POST['guide']))
                                        $this->vars['error'] = 'Please enter text.'; else{
                                        if($_POST['guide'] == '')
                                            $this->vars['error'] = 'Please enter text.'; else{
                                            $this->Madmin->edit_drop($id);
                                            $this->vars['success'] = 'Drop successfully edited.';
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
               // $this->vars['guides'] = $this->Madmin->list_guides();
                $this->load->view('admincp' . DS . 'drop_manager' . DS . 'view.edit_drop', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function add_guide(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                if(count($_POST) > 0){
                    foreach($_POST as $key => $value){
                        $this->Madmin->$key = trim($value);
                    }
                    if(!isset($_POST['title']))
                        $this->vars['error'] = 'Please enter guide title.'; else{
                        if($_POST['title'] == '')
                            $this->vars['error'] = 'Please enter guide title.'; else{
                            if(!isset($_POST['guide']))
                                $this->vars['error'] = 'Please enter guide.'; else{
                                if($_POST['guide'] == '')
                                    $this->vars['error'] = 'Please enter guide.'; else{
                                    $this->Madmin->add_guide();
                                    $this->vars['success'] = 'Guide successfully added.';
                                }
                            }
                        }
                    }
                }
                $this->load->view('admincp' . DS . 'guides_manager' . DS . 'view.add_guide', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }
		
		public function add_drop(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
				$this->vars['cats'] = $this->config->values('drop_config');
                if(count($_POST) > 0){
                    foreach($_POST as $key => $value){
                        $this->Madmin->$key = trim($value);
                    }
                    if(!isset($_POST['title']))
                        $this->vars['error'] = 'Please enter title.'; else{
                        if($_POST['title'] == '')
                            $this->vars['error'] = 'Please enter title.'; else{
                            if(!isset($_POST['guide']))
                                $this->vars['error'] = 'Please enter text.'; else{
                                if($_POST['guide'] == '')
                                    $this->vars['error'] = 'Please enter text.'; else{
                                    $this->Madmin->add_drop();
                                    $this->vars['success'] = 'Drop successfully added.';
                                }
                            }
                        }
                    }
                }
                $this->load->view('admincp' . DS . 'drop_manager' . DS . 'view.add_drop', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function bulk_mail(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                $this->vars['bulk_emails'] = $this->Madmin->load_bulk_emails();
                $this->load->view('admincp' . DS . 'bulk_mail' . DS . 'view.list', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function create_bulk_email(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                if(count($_POST) > 0){
                    foreach($_POST as $key => $value){
                        $this->Madmin->$key = $value;
                    }
                    if(!isset($_POST['subject']) || $_POST['subject'] == '')
                        $this->vars['error'] = 'Please enter subject.'; else{
                        if(!isset($_POST['body']) || $_POST['body'] == '')
                            $this->vars['error'] = 'Please enter message body.'; else{
                            if(!isset($_POST['server']) || $_POST['server'] == '')
                                $this->vars['error'] = 'Please select server.'; else{
                                if($this->Madmin->check_if_bulk_email_exists($this->website->seo_string($_POST['subject'])) != false){
                                    $this->vars['error'] = 'Bulk email already exists.';
                                } else{
                                    $save_mail = $this->Madmin->add_bulk_mail();
                                    if($save_mail != false && $save_mail != null){
                                        $this->vars['success'] = 'Bulk mail successfully saved.';
                                    } else{
                                        if($save_mail == null){
                                            $this->vars['error'] = 'No recipients.';
                                        }
                                        if($save_mail == false){
                                            $this->vars['error'] = 'Unable to save bulk mail.';
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                $this->load->view('admincp' . DS . 'bulk_mail' . DS . 'view.create_bulk_email', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function edit_bulk_email($subject = ''){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                if($subject == ''){
                    $this->vars['error'] = 'Invalid bulk email.';
                } else{
                    if(($this->vars['email_data'] = $this->Madmin->check_if_bulk_email_exists($this->website->seo_string($subject))) != false){
                        if(count($_POST) > 0){
                            foreach($_POST as $key => $value){
                                $this->Madmin->$key = $value;
                            }
                            if(!isset($_POST['new_subject']) || $_POST['new_subject'] == '')
                                $this->vars['error'] = 'Please enter subject.'; else{
                                if(!isset($_POST['body']) || $_POST['body'] == '')
                                    $this->vars['error'] = 'Please enter message body.'; else{
                                    if(!isset($_POST['server']) || $_POST['server'] == '')
                                        $this->vars['error'] = 'Please select server.'; else{
                                        $new_subject = $this->website->seo_string($_POST['new_subject']);
                                        if($new_subject != $subject){
                                            if($this->Madmin->check_if_bulk_email_exists($new_subject) != false){
                                                $this->vars['error'] = 'Bulk email already exist.';
                                            }
                                        }
                                        if(!isset($this->vars['error'])){
                                            $save_mail = $this->Madmin->edit_bulk_mail($this->vars['email_data']['id']);
                                            if($save_mail != false && $save_mail != null){
                                                $this->vars['success'] = 'Bulk mail successfully edited.';
                                                header('Location: ' . $this->config->base_url . ACPURL . '/edit-bulk-email/' . $new_subject);
                                            } else{
                                                if($save_mail == null){
                                                    $this->vars['error'] = 'No recipients.';
                                                }
                                                if($save_mail == false){
                                                    $this->vars['error'] = 'Unable to save bulk mail.';
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    } else{
                        $this->vars['error'] = 'Bulk email not found.';
                    }
                }
                $this->load->view('admincp' . DS . 'bulk_mail' . DS . 'view.edit_bulk_email', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function delete_bulk_email($subject){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                if($subject == ''){
                    $this->vars['error'] = 'Invalid bulk email.';
                } else{
                    if(($this->Madmin->check_if_bulk_email_exists($this->website->seo_string($subject))) != false){
                        if($this->Madmin->remove_bulk_email($this->website->seo_string($subject))){
                            $this->vars['success'] = 'Bulk mail successfully deleted.';
                        } else{
                            $this->vars['error'] = 'Unable to delete bulk mail.';
                        }
                    } else{
                        $this->vars['error'] = 'Bulk email not found.';
                    }
                }
                $this->load->view('admincp' . DS . 'bulk_mail' . DS . 'view.info', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function resend_bulk_email($subject){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                if($subject == ''){
                    $this->vars['error'] = 'Invalid bulk email.';
                } else{
                    if(($this->vars['email_data'] = $this->Madmin->check_if_bulk_email_exists($this->website->seo_string($subject))) != false){
                        if($this->vars['email_data']['is_finished'] == 0){
                            $this->vars['error'] = 'This email is still processing.';
                        } else{
                            if($this->Madmin->resend_bulk_email($this->website->seo_string($subject), $this->vars['email_data']['server'])){
                                $this->vars['success'] = 'Bulk mail successfully restarted.';
                            } else{
                                $this->vars['error'] = 'Unable to resend bulk mail.';
                            }
                        }
                    } else{
                        $this->vars['error'] = 'Bulk email not found.';
                    }
                }
                $this->load->view('admincp' . DS . 'bulk_mail' . DS . 'view.info', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function news_composer(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
				
				$this->load->helper('locales');
				
                if(count($_POST) > 0){
					foreach($_POST as $key => $value){
						if($key == 'news_lang'){
							$this->Madmin->news_lang = $_POST['news_lang'];
						}
						else{
							$this->Madmin->$key = trim($value);
						}
					}
					
                    if(!isset($_POST['title']))
                        $this->vars['error'] = 'Please enter news title.'; 
					else{
                        if($_POST['title'] == '')
                            $this->vars['error'] = 'Please enter news title.'; 
						else{
                            if(!isset($_POST['img_url']))
                                $this->vars['error'] = 'Please enter image url.'; 
							else{
                                if($_POST['img_url'] == '')
                                    $this->vars['error'] = 'Please enter image url.'; 
								else{
                                    if(!isset($_POST['news_small']))
                                        $this->vars['error'] = 'Please enter small news.'; 
									else{
                                        if($_POST['news_small'] == '')
                                            $this->vars['error'] = 'Please enter small news.'; 
										else{
                                            if(!isset($_POST['news_big']))
                                                $this->vars['error'] = 'Please enter full news.'; 
											else{
                                                if($_POST['news_big'] == '')
                                                    $this->vars['error'] = 'Please enter full news.'; 
												else{
													if(!isset($_POST['news_lang']) OR $_POST['news_lang'] == NULL){
														$this->vars['error'] = 'Please select atleast one language.'; 
													}
													else{
													//var_dump($this->Madmin->news_lang);die();
														$this->Madmin->add_news();
														$this->vars['success'] = 'News successfully added.';
													}
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                if(!$this->vars['news'] = $this->Madmin->load_news()){
                    $this->vars['news']['error'] = 'No News Found';
                }
                $this->load->view('admincp' . DS . 'news_composer' . DS . 'view.add_news', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function edit_news($id = ''){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                if(!isset($id))
                    $this->vars['news']['error'] = 'Invalid news.'; 
				else{
                    if($id == '')
                        $this->vars['news']['error'] = 'Invalid news.'; 
					else{
                        if(!$info = $this->Madmin->check_news($id))
                            $this->vars['news']['error'] = 'News not found.'; 
						else{
                            $this->vars['news'] = $info;
                        }
                    }
                }
				//var_dump($this->vars['news']);
                if(count($_POST) > 0){
                    foreach($_POST as $key => $value){
						if($key == 'news_lang'){
							$this->Madmin->news_lang = $_POST['news_lang'];
						}
						else{
							$this->Madmin->$key = trim($value);
						}
					}
                    if(!isset($_POST['title']))
                        $this->vars['error'] = 'Please enter news title.'; 
					else{
                        if($_POST['title'] == '')
                            $this->vars['error'] = 'Please enter news title.'; 
						else{
                            if(!isset($_POST['img_url']))
                                $this->vars['error'] = 'Please enter image url.'; 
							else{
                                if($_POST['img_url'] == '')
                                    $this->vars['error'] = 'Please enter news title.'; 
								else{
                                    if(!isset($_POST['news_small']))
                                        $this->vars['error'] = 'Please enter small news.'; 
									else{
                                        if($_POST['news_small'] == '')
                                            $this->vars['error'] = 'Please enter small news.'; 
										else{
                                            if(!isset($_POST['news_big']))
                                                $this->vars['error'] = 'Please enter full news.'; 
											else{
                                                if($_POST['news_big'] == '')
                                                    $this->vars['error'] = 'Please enter full news.'; 
												else{
                                                    $this->Madmin->edit_news($id);
                                                    $this->vars['success'] = 'News successfully edited.';
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                $this->load->view('admincp' . DS . 'news_composer' . DS . 'view.edit_news', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function delete_news($id = ''){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                if(!isset($id))
                    $this->vars['error'] = 'Invalid news.'; else{
                    if($id == '')
                        $this->vars['error'] = 'Invalid news.'; else{
                        if(!$this->Madmin->check_news($id))
                            $this->vars['error'] = 'News not found.'; else{
                            $this->Madmin->delete_news($id);
                            $this->vars['success'] = 'News successfully deleted.';
                        }
                    }
                }
                $this->load->view('admincp' . DS . 'news_composer' . DS . 'view.info', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function clear_news_cache(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                $this->Madmin->clear_news_cache();
                $this->vars['success'] = 'News cache cleared.';
                $this->load->view('admincp' . DS . 'news_composer' . DS . 'view.info', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function manage_gallery(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                if($gallery = $this->Madmin->load_gallery())
                    $this->vars['gallery'] = $gallery; else $this->vars['gallery']['error'] = 'No images found.';
                if(count($_POST) > 0){
                    foreach($_POST as $key => $value){
                        $this->Madmin->$key = trim($value);
                    }
                    if(!isset($_POST['section']))
                        $this->vars['error'] = 'Please select image section'; else{
                        if(isset($_FILES['image'])){
                            if($_FILES['image']['name'] == '')
                                $this->vars['error'] = 'Please select image to upload'; else{
                                $file_name = $_FILES['image']['name'];
                                $ext = strtolower(substr(strrchr($file_name, "."), 1));
                                $mime = @getimagesize($_FILES['image']['tmp_name']);
                                if(!$mime)
                                    $this->vars['error'] = 'Invalid image file'; else{
                                    if(!in_array($ext, ['jpg', 'jpeg', 'png', 'gif']))
                                        $this->vars['error'] = 'You must upload a file with one of the following extensions: ' . implode(', ', ['jpg', 'jpeg', 'png', 'gif']); else{
                                        if(!in_array($mime['mime'], ['image/jpeg', 'image/png', 'image/gif']))
                                            $this->vars['error'] = 'You must upload a file with one of the following extensions: ' . implode(', ', ['jpg', 'jpeg', 'png', 'gif']); else{
                                            if($this->Madmin->upload_image($_FILES['image']['tmp_name'], $file_name)){
                                                $this->Madmin->add_gallery_image($file_name, $_POST['section']);
                                                $this->vars['success'] = 'Image successfully added to gallery';
                                            } else{
                                                $this->vars['error'] = $this->Madmin->error;
                                            }
                                        }
                                    }
                                }
                            }
                        } else{
                            $this->vars['error'] = 'Invalid image selected.';
                        }
                    }
                }
                $this->load->view('admincp' . DS . 'gallery' . DS . 'view.manage_gallery', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function delete_image(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->Madmin->delete_gallery_image($_POST['id']);
                json(['success' => 1]);
            } else{
                json(['error' => 'Your not authorised to access this area!']);
            }
        }

        public function manage_downloads(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                if(count($_POST) > 0){
                    foreach($_POST as $key => $value){
                        $this->Madmin->$key = trim($value);
                    }
                    if(!isset($_POST['link_name']))
                        $this->vars['error'] = 'Please enter file title.'; else{
                        if($_POST['link_name'] == '')
                            $this->vars['error'] = 'Please enter file title.'; else{
                            if(!isset($_POST['link_desc']))
                                $this->vars['error'] = 'Please enter file description.'; else{
                                if($_POST['link_desc'] == '')
                                    $this->vars['error'] = 'Please enter file description.'; else{
                                    if(!isset($_POST['link_size']))
                                        $this->vars['error'] = 'Please enter file size.'; else{
                                        if($_POST['link_size'] == '')
                                            $this->vars['error'] = 'Please enter file size.'; else{
                                            if(!isset($_POST['link_type']))
                                                $this->vars['error'] = 'Please select file type.'; else{
                                                if($_POST['link_type'] == '')
                                                    $this->vars['error'] = 'Please select file type.'; else{
                                                    if(!isset($_POST['link_url']))
                                                        $this->vars['error'] = 'Please enter file url.'; else{
                                                        if($_POST['link_url'] == '')
                                                            $this->vars['error'] = 'Please enter file url.'; else{
                                                            $this->Madmin->add_file();
                                                            $this->vars['success'] = 'File successfully added.';
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                if(!$this->vars['files'] = $this->Madmin->load_files()){
                    $this->vars['files'] = [];
                    $this->vars['files']['error'] = 'No Files Found';
                }
                $this->load->view('admincp' . DS . 'downloads' . DS . 'view.manage_downloads', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function edit_download($id = ''){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                if(!isset($id))
                    $this->vars['error'] = 'Invalid file.'; else{
                    if($id == '')
                        $this->vars['error'] = 'Invalid file.'; else{
                        if($this->vars['file_info'] = $this->Madmin->check_file((int)$id)){
                            if(count($_POST) > 0){
                                foreach($_POST as $key => $value){
                                    $this->Madmin->$key = trim($value);
                                }
                                if(!isset($_POST['link_name']))
                                    $this->vars['error'] = 'Please enter file title.'; else{
                                    if($_POST['link_name'] == '')
                                        $this->vars['error'] = 'Please enter file title.'; else{
                                        if(!isset($_POST['link_desc']))
                                            $this->vars['error'] = 'Please enter file description.'; else{
                                            if($_POST['link_desc'] == '')
                                                $this->vars['error'] = 'Please enter file description.'; else{
                                                if(!isset($_POST['link_size']))
                                                    $this->vars['error'] = 'Please enter file size.'; else{
                                                    if($_POST['link_size'] == '')
                                                        $this->vars['error'] = 'Please enter file size.'; else{
                                                        if(!isset($_POST['link_type']))
                                                            $this->vars['error'] = 'Please select file type.'; else{
                                                            if($_POST['link_type'] == '')
                                                                $this->vars['error'] = 'Please select file type.'; else{
                                                                if(!isset($_POST['link_url']))
                                                                    $this->vars['error'] = 'Please enter file url.'; else{
                                                                    if($_POST['link_url'] == '')
                                                                        $this->vars['error'] = 'Please enter file url.'; else{
                                                                        $this->Madmin->edit_file($id);
                                                                        $this->vars['success'] = 'File successfully edited.';
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        } else{
                            $this->vars['not_found'] = 'File not found';
                        }
                    }
                }
                $this->load->view('admincp' . DS . 'downloads' . DS . 'view.edit_download', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function save_downloads_order(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->Madmin->save_downloads_order($_POST['order']);
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function delete_file($id = ''){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                if(!isset($id))
                    $this->vars['error'] = 'Invalid file.'; else{
                    if($id == '')
                        $this->vars['error'] = 'Invalid file.'; else{
                        if(!$this->Madmin->check_file($id))
                            $this->vars['error'] = 'File not found.'; else{
                            $this->Madmin->delete_file($id);
                            $this->vars['success'] = 'File successfully deleted.';
                        }
                    }
                }
                $this->load->view('admincp' . DS . 'downloads' . DS . 'view.info', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function gm_manager(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                if(count($_POST) > 0){
                    foreach($_POST as $key => $value){
                        $this->Madmin->$key = trim($value);
                    }
                    if(!isset($_POST['name']))
                        $this->vars['error'] = 'Please enter gamemaster name.'; 
                    else{
                        if(!$this->Madmin->valid_username($_POST['name']))
                            $this->vars['error'] = 'Invalid character name.'; 
                        else{
                            if(!isset($_POST['server']))
                                $this->vars['error'] = 'Please select server.'; 
                            else{
                                if(!$this->Madmin->check_gm_char($_POST['name'], $_POST['server']))
                                    $this->vars['error'] = 'Character not found on selected server.'; 
                                else{
                                    $this->Madmin->set_ctlcode(32, $_POST['name'], $_POST['server']);
                                    $this->Madmin->add_to_gmlist();
                                    if($_POST['system_type'] == 2){
                                        $autorityMask = 0;
                                        if(isset($_POST['event_management']))
                                            $autorityMask += $_POST['event_management'];
                                        if(isset($_POST['fireworks']))
                                            $autorityMask += $_POST['fireworks'];
                                        if(isset($_POST['guild_commands']))
                                            $autorityMask += $_POST['guild_commands'];
                                        if(isset($_POST['battle_soccer_commands']))
                                            $autorityMask += $_POST['battle_soccer_commands'];
                                        if(isset($_POST['chat_dc_move']))
                                            $autorityMask += $_POST['chat_dc_move'];
                                        if(isset($_POST['item']))
                                            $autorityMask += $_POST['item'];
                                        if(isset($_POST['hide']))
                                            $autorityMask += $_POST['hide'];
                                        if(isset($_POST['ban']))
                                            $autorityMask += $_POST['ban'];
                                        if(isset($_POST['pk_set']))
                                            $autorityMask += $_POST['pk_set'];
                                        if(isset($_POST['skin']))
                                            $autorityMask += $_POST['skin'];
                                        if(isset($_POST['gm_shop']))
                                            $autorityMask += $_POST['gm_shop'];
                                        if(isset($_POST['invisible_to_monsters']))
                                            $autorityMask += $_POST['invisible_to_monsters'];
                                        if(!$this->Madmin->add_igcn_autority($autorityMask, $_POST['valid_until'], $_POST['name'], $_POST['server'])){
                                            $this->vars['error'] = 'Unable to insert GM into Gm System Table.';
                                        }
                                    }
                                    if(!$this->Madmin->error)
                                        $this->vars['success'] = 'Character sucessfully added to gm list.'; 
                                    else $this->vars['error'] = $this->Madmin->error;
                                }
                            }
                        }
                    }
                }
                if(!$gm_list = $this->Madmin->load_gm_list()){
                    $this->vars['no_gms'] = 'No GameMaster Found.';
                } else{
                    $this->vars['gm_list'] = $gm_list;
                }
                $this->load->view('admincp' . DS . 'gm_manager' . DS . 'view.gm_manager', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function edit_gm($name, $server){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                if($name == '')
                    $this->vars['error'] = 'Invalid character.'; 
                else{
                    if($server == '')
                        $this->vars['error'] = 'Invalid server.'; 
                    else{
                        if(!$this->Madmin->check_gm_char($name, $server))
                            $this->vars['error'] = 'Character not found on selected server.'; 
                        else{
                            if($this->Madmin->gm_info['CtlCode'] != 32)
                                $this->vars['error'] = 'Character is not gamemaster.'; 
                            else{
                                if(count($_POST) > 0){
                                    foreach($_POST as $key => $value){
                                        $this->Madmin->$key = trim($value);
                                    }
                                    $this->Madmin->edit_gm($name, $server);
                                    if($_POST['system_type'] == 2){
                                        $autorityMask = 0;
                                        if(isset($_POST['event_management']))
                                            $autorityMask += $_POST['event_management'];
                                        if(isset($_POST['fireworks']))
                                            $autorityMask += $_POST['fireworks'];
                                        if(isset($_POST['guild_commands']))
                                            $autorityMask += $_POST['guild_commands'];
                                        if(isset($_POST['battle_soccer_commands']))
                                            $autorityMask += $_POST['battle_soccer_commands'];
                                        if(isset($_POST['chat_dc_move']))
                                            $autorityMask += $_POST['chat_dc_move'];
                                        if(isset($_POST['item']))
                                            $autorityMask += $_POST['item'];
                                        if(isset($_POST['hide']))
                                            $autorityMask += $_POST['hide'];
                                        if(isset($_POST['ban']))
                                            $autorityMask += $_POST['ban'];
                                        if(isset($_POST['pk_set']))
                                            $autorityMask += $_POST['pk_set'];
                                        if(isset($_POST['skin']))
                                            $autorityMask += $_POST['skin'];
                                        if(isset($_POST['gm_shop']))
                                            $autorityMask += $_POST['gm_shop'];
                                        if(isset($_POST['invisible_to_monsters']))
                                            $autorityMask += $_POST['invisible_to_monsters'];
                                        if(!$this->Madmin->add_igcn_autority($autorityMask, $_POST['valid_until'], $name, $server)){
                                            $this->vars['error'] = 'Unable to insert GM into Gm System Table.';
                                        }
                                    }
                                    if(!$this->Madmin->error)
                                        $this->vars['success'] = 'Character sucessfully edited.'; 
                                    else $this->vars['error'] = $this->Madmin->error;
                                }
                                $this->vars['gm_info'] = $this->Madmin->load_gm_info($name, $server);
                                if($this->vars['gm_info']['system_type'] == 2){
                                    $this->vars['igcn_diplay'] = 'block';
                                    $authorityMask = $this->Madmin->get_gm_authority_mask($name, $server);
                                    if($authorityMask != false){
                                        if($authorityMask['AuthorityMask'] >= 2048){
                                            $this->vars['invisible_to_monsters'] = true;
                                            $authorityMask['AuthorityMask'] -= 2048;
                                        }
                                        if($authorityMask['AuthorityMask'] >= 1024){
                                            $this->vars['gm_shop'] = true;
                                            $authorityMask['AuthorityMask'] -= 1024;
                                        }
                                        if($authorityMask['AuthorityMask'] >= 512){
                                            $this->vars['ban'] = true;
                                            $authorityMask['AuthorityMask'] -= 512;
                                        }
                                        if($authorityMask['AuthorityMask'] >= 256){
                                            $this->vars['skin'] = true;
                                            $authorityMask['AuthorityMask'] -= 256;
                                        }
                                        if($authorityMask['AuthorityMask'] >= 128){
                                            $this->vars['hide'] = true;
                                            $authorityMask['AuthorityMask'] -= 128;
                                        }
                                        if($authorityMask['AuthorityMask'] >= 64){
                                            $this->vars['pk_set'] = true;
                                            $authorityMask['AuthorityMask'] -= 64;
                                        }
                                        if($authorityMask['AuthorityMask'] >= 32){
                                            $this->vars['item'] = true;
                                            $authorityMask['AuthorityMask'] -= 32;
                                        }
                                        if($authorityMask['AuthorityMask'] >= 16){
                                            $this->vars['battle_soccer'] = true;
                                            $authorityMask['AuthorityMask'] -= 16;
                                        }
                                        if($authorityMask['AuthorityMask'] >= 8){
                                            $this->vars['guild'] = true;
                                            $authorityMask['AuthorityMask'] -= 8;
                                        }
                                        if($authorityMask['AuthorityMask'] >= 4){
                                            $this->vars['dc'] = true;
                                            $authorityMask['AuthorityMask'] -= 4;
                                        }
                                        if($authorityMask['AuthorityMask'] >= 2){
                                            $this->vars['fireworks'] = true;
                                            $authorityMask['AuthorityMask'] -= 2;
                                        }
                                        if($authorityMask['AuthorityMask'] >= 1){
                                            $this->vars['event'] = true;
                                            $authorityMask['AuthorityMask'] -= 1;
                                        }
                                        $this->vars['valid_until'] = $authorityMask['Expiry'];
                                    }
                                } else{
                                    $this->vars['igcn_diplay'] = 'none';
                                }
                            }
                        }
                    }
                }
                if(!$gm_list = $this->Madmin->load_gm_list()){
                    $this->vars['no_gms'] = 'No GameMaster Found.';
                } else{
                    $this->vars['gm_list'] = $gm_list;
                }
                $this->load->view('admincp' . DS . 'gm_manager' . DS . 'view.gm_edit', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function delete_gm($name = '', $server = ''){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                if($name == '')
                    $this->vars['error'] = 'Invalid character.'; 
                else{
                    if($server == '')
                        $this->vars['error'] = 'Invalid server.'; 
                    else{
                        if(!$this->Madmin->check_gm_char($name, $server))
                            $this->vars['error'] = 'Character not found on selected server.'; 
                        else{
                            $this->Madmin->check_gm_type($name, $server);
                            $this->Madmin->set_ctlcode(0, $name, $server);
                            $this->Madmin->remove_gm_from_list($name, $server);
                            if($this->Madmin->gm_system_type == 2){
                                $this->Madmin->remove_from_igcn_gm_system($name, $server);
                            }
                            $this->vars['success'] = 'Character removed from gm list.';
                        }
                    }
                }
                $this->load->view('admincp' . DS . 'gm_manager' . DS . 'view.info', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function gm_announcement(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                if(isset($_POST['add_anouncement'])){
                    if(isset($_POST['announcement']) && $_POST['announcement'] != ''){
                        $this->Madmin->add_anouncement($_POST['announcement']);
                        $this->vars['success'] = 'Announcement added.';
                    } else{
                        $this->vars['error'] = 'Please enter text.';
                    }
                }
                $this->vars['announcement'] = $this->Madmin->load_announcement();
                $this->load->view('admincp' . DS . 'gm_manager' . DS . 'view.announcement', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }
		
		public function logs_partner($page = 1, $coupon = '-'){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                $this->load->lib('iteminfo');
                if(isset($_POST['search_partner_logs'])){ 
                    $coupon = isset($_POST['coupon']) ? $_POST['coupon'] : '';
                    if($coupon == ''){
                        $this->vars['error'] = 'Invalid coupon';
                    } else{
                        $this->vars['logs'] = $this->Madmin->load_partner_logs(1, 25, $coupon);
                        $this->pagination->initialize(1, 25, $this->Madmin->count_total_partner_logs($coupon), $this->config->base_url . ACPURL . '/logs-partner/%s/' . $coupon . '');
                        $this->vars['pagination'] = $this->pagination->create_links();
                    }
                } else{
                    $this->vars['logs'] = $this->Madmin->load_partner_logs($page, 25, $coupon);
                    $lk = '';
                    if($coupon != '')
                        $lk .= '/' . $coupon;
                    $this->pagination->initialize($page, 25, $this->Madmin->count_total_partner_logs($coupon), $this->config->base_url . ACPURL . '/logs-partner/%s' . $lk);
                    $this->vars['pagination'] = $this->pagination->create_links();
                }
                $this->load->view('admincp' . DS . 'logs' . DS . 'view.partner_logs', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function logs_shop($page = 1, $acc = '-', $server = 'All', $from = '', $to = ''){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                $this->load->lib('iteminfo');
                if(isset($_POST['search_shop_logs'])){
                    $from = (isset($_POST['date01']) && preg_match('/\d{1,2}\/\d{1,2}\/\d{4}/', $_POST['date01'])) ? date('Y-m-d', strtotime($_POST['date01'])) : '';
                    $to = (isset($_POST['date02']) && preg_match('/\d{1,2}\/\d{1,2}\/\d{4}/', $_POST['date02'])) ? date('Y-m-d', strtotime($_POST['date02'])) : '';
                    $server = (isset($_POST['server']) && $_POST['server'] != 'All') ? $_POST['server'] : 'All';
                    $acc = isset($_POST['account']) ? $_POST['account'] : '';
                    if($from == ''){
                        $this->vars['error'] = 'Invalid from date';
                    } else if($to == ''){
                        $this->vars['error'] = 'Invalid to date';
                    } else if($acc == ''){
                        $this->vars['error'] = 'Invalid account';
                    } else{
                        $this->vars['logs'] = $this->Madmin->load_shop_logs(1, 25, $acc, $server, $from, $to);
                        $this->pagination->initialize(1, 25, $this->Madmin->count_total_shop_logs($acc, $server, $from, $to), $this->config->base_url . ACPURL . '/logs-shop/%s/' . $acc . '/' . $server . '/' . str_replace('/', '-', $from) . '/' . str_replace('/', '-', $to) . '');
                        $this->vars['pagination'] = $this->pagination->create_links();
                    }
                } else{
                    $this->vars['logs'] = $this->Madmin->load_shop_logs($page, 25, $acc, $server, $from, $to);
                    $lk = '';
                    if($acc != '')
                        $lk .= '/' . $acc;
                    $lk .= '/' . $server;
                    if($from != '')
                        $lk .= '/' . str_replace('/', '-', $from);
                    if($to != '')
                        $lk .= '/' . str_replace('/', '-', $to);
                    $this->pagination->initialize($page, 25, $this->Madmin->count_total_shop_logs($acc, $server, $from, $to), $this->config->base_url . ACPURL . '/logs-shop/%s' . $lk);
                    $this->vars['pagination'] = $this->pagination->create_links();
                }
                $this->load->view('admincp' . DS . 'logs' . DS . 'view.shop_logs', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function logs_market($page = 1, $acc = '-', $server = 'All', $from = '', $to = ''){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                $this->load->lib('iteminfo');
                if(isset($_POST['search_market_logs'])){
                    $from = (isset($_POST['date01']) && preg_match('/\d{1,2}\/\d{1,2}\/\d{4}/', $_POST['date01'])) ? date('Y-m-d', strtotime($_POST['date01'])) : '';
                    $to = (isset($_POST['date02']) && preg_match('/\d{1,2}\/\d{1,2}\/\d{4}/', $_POST['date02'])) ? date('Y-m-d', strtotime($_POST['date02'])) : '';
                    $server = (isset($_POST['server']) && $_POST['server'] != 'All') ? $_POST['server'] : 'All';
                    $acc = isset($_POST['account']) ? $_POST['account'] : '';
                    if($from == ''){
                        $this->vars['error'] = 'Invalid from date';
                    } else if($to == ''){
                        $this->vars['error'] = 'Invalid to date';
                    } else if($acc == ''){
                        $this->vars['error'] = 'Invalid account';
                    } else{
                        $this->vars['logs'] = $this->Madmin->load_market_logs(1, 25, $acc, $server, $from, $to);
                        $this->pagination->initialize(1, 25, $this->Madmin->count_total_market_logs($acc, $server, $from, $to), $this->config->base_url . ACPURL . '/logs-market/%s/' . $acc . '/' . $server . '/' . str_replace('/', '-', $from) . '/' . str_replace('/', '-', $to) . '');
                        $this->vars['pagination'] = $this->pagination->create_links();
                    }
                } else{
                    $this->vars['logs'] = $this->Madmin->load_market_logs($page, 25, $acc, $server, $from, $to);
                    $lk = '';
                    if($acc != '')
                        $lk .= '/' . $acc;
                    $lk .= '/' . $server;
                    if($from != '')
                        $lk .= '/' . str_replace('/', '-', $from);
                    if($to != '')
                        $lk .= '/' . str_replace('/', '-', $to);
                    $this->pagination->initialize($page, 25, $this->Madmin->count_total_market_logs($acc, $server, $from, $to), $this->config->base_url . ACPURL . '/logs-market/%s' . $lk);
                    $this->vars['pagination'] = $this->pagination->create_links();
                }
                $this->load->view('admincp' . DS . 'logs' . DS . 'view.market_logs', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function gm_logs($page = 1, $acc = '-', $server = 'All', $from = '', $to = ''){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                if(isset($_POST['search_account_logs'])){
                    $from = (isset($_POST['date01']) && preg_match('/\d{1,2}\/\d{1,2}\/\d{4}/', $_POST['date01'])) ? date('Y-m-d', strtotime($_POST['date01'])) : '';
                    $to = (isset($_POST['date02']) && preg_match('/\d{1,2}\/\d{1,2}\/\d{4}/', $_POST['date02'])) ? date('Y-m-d', strtotime($_POST['date02'])) : '';
                    $server = (isset($_POST['server']) && $_POST['server'] != 'All') ? $_POST['server'] : 'All';
                    $acc = isset($_POST['account']) ? $_POST['account'] : '';
                    if($from == ''){
                        $this->vars['error'] = 'Invalid from date';
                    } else if($to == ''){
                        $this->vars['error'] = 'Invalid to date';
                    } else if($acc == ''){
                        $this->vars['error'] = 'Invalid account';
                    } else{
                        $this->vars['logs'] = $this->Madmin->load_gm_logs(1, 25, $acc, $server, $from, $to);
                        $this->pagination->initialize(1, 25, $this->Madmin->count_total_gm_logs($acc, $server, $from, $to), $this->config->base_url . ACPURL . '/gm-logs/%s/' . $acc . '/' . $server . '/' . str_replace('/', '-', $from) . '/' . str_replace('/', '-', $to) . '');
                        $this->vars['pagination'] = $this->pagination->create_links();
                    }
                } else{
                    $this->vars['logs'] = $this->Madmin->load_gm_logs($page, 25, $acc, $server, $from, $to);
                    $lk = '';
                    if($acc != '')
                        $lk .= '/' . $acc;
                    $lk .= '/' . $server;
                    if($from != '')
                        $lk .= '/' . str_replace('/', '-', $from);
                    if($to != '')
                        $lk .= '/' . str_replace('/', '-', $to);
                    $this->pagination->initialize($page, 25, $this->Madmin->count_total_gm_logs($acc, $server, $from, $to), $this->config->base_url . ACPURL . '/gm-logs/%s' . $lk);
                    $this->vars['pagination'] = $this->pagination->create_links();
                }
                $this->load->view('admincp' . DS . 'gm_manager' . DS . 'view.gm_logs', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }
		
		public function load_account_logs(){
			if($this->session->userdata(['admin' => 'is_admin'])){
				$this->vars['start'] = (isset($_POST['start']) && is_numeric($_POST['start'])) ? $_POST['start'] : 0;
				$this->vars['per_page'] = (isset($_POST['length']) && is_numeric($_POST['length'])) ? $_POST['length'] : 10;
				$this->vars['order_column'] = (isset($_POST['order'][0]['column']) && is_numeric($_POST['order'][0]['column'])) ? $_POST['order'][0]['column'] : 2;
				$this->vars['order_dir'] = (isset($_POST['order'][0]['dir'])) ? $_POST['order'][0]['dir'] : 'desc';
				if(isset($_POST['search']['value']) && $_POST['search']['value'] != ''){
					$this->Madmin->searchConditionAccount(htmlspecialchars($_POST['search']['value']), 'account');
				}
				if(isset($_COOKIE['account_filter_date_1']) && $_COOKIE['account_filter_date_1'] != ''){
					$this->Madmin->searchConditionDates($_COOKIE['account_filter_date_1'], $_COOKIE['account_filter_date_2'], 'date');
				}
				if(isset($_COOKIE['account_filter_text_string']) && $_COOKIE['account_filter_text_string'] != ''){
					$this->Madmin->searchConditionText($_COOKIE['account_filter_text_string']);
				}
				$this->vars['logs'] = $this->Madmin->load_account_logs($this->vars['start'], $this->vars['per_page'], $this->vars['order_column'], $this->vars['order_dir']);
				$this->vars['total_records'] = $this->Madmin->count_total_account_logs();
                $this->vars['total_filtered_records'] = $this->Madmin->count_total_account_logs(true);
                if($this->vars['logs'] != false){
                    foreach($this->vars['logs'] AS $info){
						if($info['amount'] >= 0){
							$amount = '<span style="color: green;">' . $info['amount'] . '</span>';
						} else{
							$amount = '<span style="color: red;">' . $info['amount'] . '</span>';
						}
                        $this->vars['data'][] = [
							$info['account'], 
							$info['text'],
							$amount,
							$info['date'], 
							$info['ip'], 
							$this->website->get_value_from_server($info['server'], 'title')];
                    }
                } else{
                    $this->vars['data'] = [];
                }
                json(["draw" => (int)$_POST['draw'], "recordsTotal" => $this->vars['total_records'], "recordsFiltered" => $this->vars['total_filtered_records'], "data" => $this->vars['data']]);
			}
			else{
				json(['error' => 'Please login first!']);
			}
		}
		
		public function filter_account_logs(){
			$time = time() + (86400 * 3);
			if($_POST['date01'] != ''){
					setcookie("account_filter_date_1", $_POST['date01'], $time, '/');
			} else{
					setcookie("account_filter_date_1", '', time() - 3600, '/');
			}
			if($_POST['date02'] != ''){
					setcookie("account_filter_date_2", $_POST['date02'], $time, '/');
			} else{
					setcookie("account_filter_date_2", '', time() - 3600, '/');
			}
			if($_POST['log_string'] != ''){
					setcookie("account_filter_text_string", $_POST['log_string'], $time, '/');
			} else{
					setcookie("account_filter_text_string", '', time() - 3600, '/');
			}
			echo json(['success' => 'filters added']);
		}
		
		public function filter_account_logs_reset(){
			setcookie("account_filter_date_1", '', time() - 3600, '/');
			setcookie("account_filter_date_2", '', time() - 3600, '/');
			setcookie("account_filter_text_string", '', time() - 3600, '/');
			echo json(['success' => 'filters reset']);	
		}

        public function logs_account($page = 1, $acc = '-', $server = 'All', $from = '', $to = ''){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
				if(isset($_COOKIE['account_filter_date_1']) && $_COOKIE['account_filter_date_1'] != ''){
					$this->vars['account_filter_date_1'] = $_COOKIE['account_filter_date_1'];
					$this->vars['account_filter_date_2'] = $_COOKIE['account_filter_date_2'];
				}
				if(isset($_COOKIE['account_filter_text_string']) && $_COOKIE['account_filter_text_string'] != ''){
					$this->vars['account_filter_text_string'] = $_COOKIE['account_filter_text_string'];
				}
                $this->load->view('admincp' . DS . 'logs' . DS . 'view.account_logs', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function logs_paypal_transactions($page = 1, $acc = '-', $server = 'All'){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                if(isset($_POST['search_paypal_transactions'])){
                    $server = (isset($_POST['server']) && $_POST['server'] != 'All') ? $_POST['server'] : 'All';
                    $acc = isset($_POST['account']) ? $_POST['account'] : '';
                    if($acc == ''){
                        $this->vars['error'] = 'Invalid account';
                    } else{
                        $this->vars['logs'] = $this->Madmin->load_paypal_transactions(1, 25, $acc, $server);
                        $this->pagination->initialize(1, 25, $this->Madmin->count_total_paypal_transactions($acc, $server), $this->config->base_url . ACPURL . '/logs-paypal-transactions/%s/' . $acc . '/' . $server . '');
                        $this->vars['pagination'] = $this->pagination->create_links();
                    }
                } else{
                    $this->vars['logs'] = $this->Madmin->load_paypal_transactions($page, 25, $acc, $server);
                    $lk = '';
                    if($acc != '')
                        $lk .= '/' . $acc;
                    $lk .= '/' . $server;
                    $this->pagination->initialize($page, 25, $this->Madmin->count_total_paypal_transactions($acc, $server), $this->config->base_url . ACPURL . '/logs-paypal-transactions/%s' . $lk);
                    $this->vars['pagination'] = $this->pagination->create_links();
                }
                $this->load->view('admincp' . DS . 'logs' . DS . 'view.paypal_transactions', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function logs_twocheckout_transactions($page = 1, $acc = '-', $server = 'All'){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                if(isset($_POST['search_2checkout_transactions'])){
                    $server = (isset($_POST['server']) && $_POST['server'] != 'All') ? $_POST['server'] : 'All';
                    $acc = isset($_POST['account']) ? $_POST['account'] : '';
                    if($acc == ''){
                        $this->vars['error'] = 'Invalid account';
                    } else{
                        $this->vars['logs'] = $this->Madmin->load_twocheckout_transactions(1, 25, $acc, $server);
                        $this->pagination->initialize(1, 25, $this->Madmin->count_total_twocheckout_transactions($acc, $server), $this->config->base_url . ACPURL . '/logs-twocheckout-transactions/%s/' . $acc . '/' . $server . '');
                        $this->vars['pagination'] = $this->pagination->create_links();
                    }
                } else{
                    $this->vars['logs'] = $this->Madmin->load_twocheckout_transactions($page, 25, $acc, $server);
                    $lk = '';
                    if($acc != '')
                        $lk .= '/' . $acc;
                    $lk .= '/' . $server;
                    $this->pagination->initialize($page, 25, $this->Madmin->count_total_twocheckout_transactions($acc, $server), $this->config->base_url . ACPURL . '/logs-twocheckout-transactions/%s' . $lk);
                    $this->vars['pagination'] = $this->pagination->create_links();
                }
                $this->load->view('admincp' . DS . 'logs' . DS . 'view.2checkout_transactions', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function logs_paymentwall_transactions($page = 1, $acc = '-', $server = 'All'){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                if(isset($_POST['search_pw_transactions'])){
                    $server = (isset($_POST['server']) && $_POST['server'] != 'All') ? $_POST['server'] : 'All';
                    $acc = isset($_POST['account']) ? $_POST['account'] : '';
                    if($acc == ''){
                        $this->vars['error'] = 'Invalid account';
                    } else{
                        $this->vars['logs'] = $this->Madmin->load_pw_transactions(1, 25, $acc, $server);
                        $this->pagination->initialize(1, 25, $this->Madmin->count_total_pw_transactions($acc, $server), $this->config->base_url . ACPURL . '/logs-paymentwall-transactions/%s/' . $acc . '/' . $server . '');
                        $this->vars['pagination'] = $this->pagination->create_links();
                    }
                } else{
                    $this->vars['logs'] = $this->Madmin->load_pw_transactions($page, 25, $acc, $server);
                    $lk = '';
                    if($acc != '')
                        $lk .= '/' . $acc;
                    $lk .= '/' . $server;
                    $this->pagination->initialize($page, 25, $this->Madmin->count_total_pw_transactions($acc, $server), $this->config->base_url . ACPURL . '/logs-paymentwall-transactions/%s' . $lk);
                    $this->vars['pagination'] = $this->pagination->create_links();
                }
                $this->load->view('admincp' . DS . 'logs' . DS . 'view.pw_transactions', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function logs_pagseguro_transactions($page = 1, $acc = '-', $server = 'All'){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                if(isset($_POST['search_pagseguro_transactions'])){
                    $server = (isset($_POST['server']) && $_POST['server'] != 'All') ? $_POST['server'] : 'All';
                    $acc = isset($_POST['account']) ? $_POST['account'] : '';
                    if($acc == ''){
                        $this->vars['error'] = 'Invalid account';
                    } else{
                        $this->vars['logs'] = $this->Madmin->load_pagseguro_transactions(1, 25, $acc, $server);
                        $this->pagination->initialize(1, 25, $this->Madmin->count_total_pagseguro_transactions($acc, $server), $this->config->base_url . ACPURL . '/logs-pagseguro-transactions/%s/' . $acc . '/' . $server . '');
                        $this->vars['pagination'] = $this->pagination->create_links();
                    }
                } else{
                    $this->vars['logs'] = $this->Madmin->load_pagseguro_transactions($page, 25, $acc, $server);
                    $lk = '';
                    if($acc != '')
                        $lk .= '/' . $acc;
                    $lk .= '/' . $server;
                    $this->pagination->initialize($page, 25, $this->Madmin->count_total_pagseguro_transactions($acc, $server), $this->config->base_url . ACPURL . '/logs-pagseguro-transactions/%s' . $lk);
                    $this->vars['pagination'] = $this->pagination->create_links();
                }
                $this->load->view('admincp' . DS . 'logs' . DS . 'view.pagseguro_transactions', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function logs_fortumo_transactions($page = 1, $acc = '-', $server = 'All'){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                if(isset($_POST['search_fortumo_transactions'])){
                    $server = (isset($_POST['server']) && $_POST['server'] != 'All') ? $_POST['server'] : 'All';
                    $acc = isset($_POST['account']) ? $_POST['account'] : '';
                    if($acc == ''){
                        $this->vars['error'] = 'Invalid account';
                    } else{
                        $this->vars['logs'] = $this->Madmin->load_fortumo_transactions(1, 25, $acc, $server);
                        $this->pagination->initialize(1, 25, $this->Madmin->count_total_fortumo_transactions($acc, $server), $this->config->base_url . ACPURL . '/logs-fortumo-transactions/%s/' . $acc . '/' . $server . '');
                        $this->vars['pagination'] = $this->pagination->create_links();
                    }
                } else{
                    $this->vars['logs'] = $this->Madmin->load_fortumo_transactions($page, 25, $acc, $server);
                    $lk = '';
                    if($acc != '')
                        $lk .= '/' . $acc;
                    $lk .= '/' . $server;
                    $this->pagination->initialize($page, 25, $this->Madmin->count_total_fortumo_transactions($acc, $server), $this->config->base_url . ACPURL . '/logs-fortumo-transactions/%s' . $lk);
                    $this->vars['pagination'] = $this->pagination->create_links();
                }
                $this->load->view('admincp' . DS . 'logs' . DS . 'view.fortumo_transactions', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function logs_paygol_transactions($page = 1, $acc = '-', $server = 'All'){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                if(isset($_POST['search_paygol_transactions'])){
                    $server = (isset($_POST['server']) && $_POST['server'] != 'All') ? $_POST['server'] : 'All';
                    $acc = isset($_POST['account']) ? $_POST['account'] : '';
                    if($acc == ''){
                        $this->vars['error'] = 'Invalid account';
                    } else{
                        $this->vars['logs'] = $this->Madmin->load_paygol_transactions(1, 25, $acc, $server);
                        $this->pagination->initialize(1, 25, $this->Madmin->count_total_paygol_transactions($acc, $server), $this->config->base_url . ACPURL . '/logs-paygol-transactions/%s/' . $acc . '/' . $server . '');
                        $this->vars['pagination'] = $this->pagination->create_links();
                    }
                } else{
                    $this->vars['logs'] = $this->Madmin->load_paygol_transactions($page, 25, $acc, $server);
                    $lk = '';
                    if($acc != '')
                        $lk .= '/' . $acc;
                    $lk .= '/' . $server;
                    $this->pagination->initialize($page, 25, $this->Madmin->count_total_paygol_transactions($acc, $server), $this->config->base_url . ACPURL . '/logs-paygol-transactions/%s' . $lk);
                    $this->vars['pagination'] = $this->pagination->create_links();
                }
                $this->load->view('admincp' . DS . 'logs' . DS . 'view.paygol_transactions', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function logs_paycall_transactions($page = 1, $acc = '-', $server = 'All'){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                if(isset($_POST['search_paycall_transactions'])){
                    $server = (isset($_POST['server']) && $_POST['server'] != 'All') ? $_POST['server'] : 'All';
                    $acc = isset($_POST['account']) ? $_POST['account'] : '';
                    if($acc == ''){
                        $this->vars['error'] = 'Invalid account';
                    } else{
                        $this->vars['logs'] = $this->Madmin->load_paycall_transactions(1, 25, $acc, $server);
                        $this->pagination->initialize(1, 25, $this->Madmin->count_total_paycall_transactions($acc, $server), $this->config->base_url . ACPURL . '/logs-paycall-transactions/%s/' . $acc . '/' . $server . '');
                        $this->vars['pagination'] = $this->pagination->create_links();
                    }
                } else{
                    $this->vars['logs'] = $this->Madmin->load_paycall_transactions($page, 25, $acc, $server);
                    $lk = '';
                    if($acc != '')
                        $lk .= '/' . $acc;
                    $lk .= '/' . $server;
                    $this->pagination->initialize($page, 25, $this->Madmin->count_total_paycall_transactions($acc, $server), $this->config->base_url . ACPURL . '/logs-paycall-transactions/%s' . $lk);
                    $this->vars['pagination'] = $this->pagination->create_links();
                }
                $this->load->view('admincp' . DS . 'logs' . DS . 'view.paycall_transactions', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function logs_interkassa_transactions($page = 1, $acc = '-', $server = 'All'){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                if(isset($_POST['search_interkassa_transactions'])){
                    $server = (isset($_POST['server']) && $_POST['server'] != 'All') ? $_POST['server'] : 'All';
                    $acc = isset($_POST['account']) ? $_POST['account'] : '';
                    if($acc == ''){
                        $this->vars['error'] = 'Invalid account';
                    } else{
                        $this->vars['logs'] = $this->Madmin->load_interkassa_transactions(1, 25, $acc, $server);
                        $this->pagination->initialize(1, 25, $this->Madmin->count_total_interkassa_transactions($acc, $server), $this->config->base_url . ACPURL . '/logs-interkassa-transactions/%s/' . $acc . '/' . $server . '');
                        $this->vars['pagination'] = $this->pagination->create_links();
                    }
                } else{
                    $this->vars['logs'] = $this->Madmin->load_interkassa_transactions($page, 25, $acc, $server);
                    $lk = '';
                    if($acc != '')
                        $lk .= '/' . $acc;
                    $lk .= '/' . $server;
                    $this->pagination->initialize($page, 25, $this->Madmin->count_total_interkassa_transactions($acc, $server), $this->config->base_url . ACPURL . '/logs-interkassa-transactions/%s' . $lk);
                    $this->vars['pagination'] = $this->pagination->create_links();
                }
                $this->load->view('admincp' . DS . 'logs' . DS . 'view.interkassa_transactions', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function logs_cuenta_digital_transactions($page = 1, $acc = '-', $server = 'All'){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                if(isset($_POST['search_cuenta_digital_transactions'])){
                    $server = (isset($_POST['server']) && $_POST['server'] != 'All') ? $_POST['server'] : 'All';
                    $acc = isset($_POST['account']) ? $_POST['account'] : '';
                    if($acc == ''){
                        $this->vars['error'] = 'Invalid account';
                    } else{
                        $this->vars['logs'] = $this->Madmin->load_cuenta_digital_transactions(1, 25, $acc, $server);
                        $this->pagination->initialize(1, 25, $this->Madmin->count_total_cuenta_digital_transactions($acc, $server), $this->config->base_url . ACPURL . '/logs-cuenta-digital-transactions/%s/' . $acc . '/' . $server . '');
                        $this->vars['pagination'] = $this->pagination->create_links();
                    }
                } else{
                    $this->vars['logs'] = $this->Madmin->load_cuenta_digital_transactions($page, 25, $acc, $server);
                    $lk = '';
                    if($acc != '')
                        $lk .= '/' . $acc;
                    $lk .= '/' . $server;
                    $this->pagination->initialize($page, 25, $this->Madmin->count_total_cuenta_digital_transactions($acc, $server), $this->config->base_url . ACPURL . '/logs-cuenta-digital-transactions/%s' . $lk);
                    $this->vars['pagination'] = $this->pagination->create_links();
                }
                $this->load->view('admincp' . DS . 'logs' . DS . 'view.cuenta_digital_transactions', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function find_item($server = '', $serial = ''){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                if($server != '' && $serial != ''){
                    if(preg_match('/^[0-9A-F]{8}$/i', $serial)){
                        if(array_key_exists($server, $this->website->server_list())){
                            if($this->vars['search'] = $this->Madmin->search_char_inventory($serial, $server)){
                                $this->vars['success'] = 'Item found in character ' . $this->vars['search']['Name'] . ' inventory. Want To <a href="javascript:void(0);" onclick="return App.confirmLink(\'Are you sure you want to remove item?\', \'' . $this->config->base_url . ACPURL . '/remove-item-inventory/' . $this->vars['search']['Name'] . '/' . $serial . '/' . $server . '\', \'self\')">Delete</a> Item?';
                            } else{
                                if($this->vars['search'] = $this->Madmin->search_warehouse($serial, $server)){
                                    $this->vars['success'] = 'Item found in account ' . $this->vars['search']['AccountId'] . ' warehouse. Want To <a href="javascript:void(0);" onclick="return App.confirmLink(\'Are you sure you want to remove item?\', \'' . $this->config->base_url . ACPURL . '/remove-item-warehouse/' . $this->vars['search']['AccountId'] . '/' . $serial . '/' . $server . '\', \'self\')">Delete</a> Item?';
                                } else{
                                    $this->vars['error'] = 'Item not found.';
                                }
                            }
                        } else{
                            $this->vars['error'] = 'Invalid server selected.';
                        }
                    } else{
                        $this->vars['error'] = 'Invalid serial';
                    }
                } else{
                    if(isset($_POST['search_item'])){
                        $server = isset($_POST['server']) ? $_POST['server'] : '';
                        $serial = isset($_POST['serial']) ? $_POST['serial'] : '';
                        if($serial == '' || !preg_match('/^[0-9A-F]{8}$/i', $serial))
                            $this->vars['error'] = 'Invalid serial'; else{
                            if($server == '')
                                $this->vars['error'] = 'Invalid server'; else{
                                if(array_key_exists($server, $this->website->server_list())){
                                    if($this->vars['search'] = $this->Madmin->search_char_inventory($serial, $server)){
                                        $this->vars['success'] = 'Item found in character ' . $this->vars['search']['Name'] . ' inventory. Want To <a href="javascript:void(0);" onclick="return App.confirmLink(\'Are you sure you want to remove item?\', \'' . $this->config->base_url . ACPURL . '/remove-item-inventory/' . $this->vars['search']['Name'] . '/' . $serial . '/' . $server . '\', \'self\')">Delete</a> Item?';
                                    } else{
                                        if($this->vars['search'] = $this->Madmin->search_warehouse($serial, $server)){
                                            $this->vars['success'] = 'Item found in account ' . $this->vars['search']['AccountId'] . ' warehouse. Want To <a href="javascript:void(0);" onclick="return App.confirmLink(\'Are you sure you want to remove item?\', \'' . $this->config->base_url . ACPURL . '/remove-item-warehouse/' . $this->vars['search']['AccountId'] . '/' . $serial . '/' . $server . '\', \'self\')">Delete</a> Item?';
                                        } else{
                                            $this->vars['error'] = 'Item not found.';
                                        }
                                    }
                                } else{
                                    $this->vars['error'] = 'Invalid server selected.';
                                }
                            }
                        }
                    }
                }
                $this->load->view('admincp' . DS . 'logs' . DS . 'view.search_item', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function remove_item_inventory($name, $serial, $server){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                if(array_key_exists($server, $this->website->server_list())){
                    if(preg_match('/^[0-9A-F]{8}$/i', $serial)){
                        if($this->Madmin->check_status($name, true, $server)){
                            $this->Madmin->get_inventory_content($name, $server);
                            if($this->Madmin->remove_inventory_item_by_serial($name, $serial, $server)){
                                $this->vars['success'] = 'Inventory item successfully removed.';
                            } else{
                                $this->vars['error'] = 'Item not found or already removed.';
                            }
                        } else{
                            $this->vars['error'] = 'User is online';
                        }
                    } else{
                        $this->vars['error'] = 'Invalid serial';
                    }
                } else{
                    $this->vars['error'] = 'Invalid server selected.';
                }
                $this->load->view('admincp' . DS . 'logs' . DS . 'view.search_item', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function remove_item_warehouse($name, $serial, $server){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                if(array_key_exists($server, $this->website->server_list())){
                    if(preg_match('/^[0-9A-F]{8}$/i', $serial)){
                        if($this->Madmin->check_status($name, false, $server)){
                            $this->Madmin->get_vault_content($name, $server);
                            if($this->Madmin->remove_vault_item_by_serial($name, $serial, $server)){
                                $this->vars['success'] = 'Warehouse item successfully removed.';
                            } else{
                                $this->vars['error'] = 'Item not found or already removed.';
                            }
                        } else{
                            $this->vars['error'] = 'User is online';
                        }
                    } else{
                        $this->vars['error'] = 'Invalid serial';
                    }
                } else{
                    $this->vars['error'] = 'Invalid server selected.';
                }
                $this->load->view('admincp' . DS . 'logs' . DS . 'view.search_item', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function add_item(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                if(isset($_POST['add_item'])){
                    foreach($_POST as $key => $value){
                        $this->Madmin->$key = $value;
                    }
                    if($this->Madmin->vars['item_id'] == '')
                        $this->errors[] = 'Please enter item id.';
                    if($this->Madmin->vars['name'] == '')
                        $this->errors[] = 'Please enter item name.';
                    if($this->Madmin->vars['item_cat'] == '')
                        $this->vars['error'] = 'Please select item category.';
                    if($this->Madmin->vars['original_item_cat'] == '')
                        $this->errors[] = 'Please select item original category.';
                    if($this->Madmin->vars['price'] == '')
                        $this->errors[] = 'Please enter item price.';
                    $image = isset($_FILES['itemimage']) ? $_FILES['itemimage'] : '';
                    if(!empty($image['tmp_name'])){
                        $file_name = $image['name'];
                        $ext = strtolower(substr(strrchr($file_name, "."), 1));
                        $mime = @getimagesize($image['tmp_name']);
                        if($mime){
                            if(!in_array($ext, ['jpg', 'jpeg', 'png', 'gif']))
                                $this->errors[] = ['You must upload a file with one of the following extensions: ' . implode(', ', ['jpg', 'jpeg', 'png', 'gif'])]; 
                            else{
                                if(!in_array($mime['mime'], ['image/jpeg', 'image/png', 'image/gif']))
                                    $this->errors[] = ['You must upload a file with one of the following extensions: ' . implode(', ', ['jpg', 'jpeg', 'png', 'gif'])];
                            }
                        } else{
                            $this->errors[] = 'Invalid image file.';
                        }
                    }
                    if($this->Madmin->check_item_exists())
                        $this->errors[] = 'Item already exists.';
                    if(count($this->errors) > 0){
                        $this->vars['error'] = $this->errors;
                    } else{
                        if(!empty($image['tmp_name'])){
                            $name = $this->Madmin->vars['item_id'];
                            if($this->Madmin->vars['stick_level'] > 0){
                                $name .= '-' . $this->Madmin->vars['stick_level'];
                            }
                            $name .= '.' . $ext;
                            if(!$this->Madmin->upload_image($image['tmp_name'], $name, false, BASEDIR . 'assets' . DS . 'item_images' . DS . $this->Madmin->vars['item_cat'] . DS)){
                                $this->vars['error'] = $this->Madmin->error;
                            }
                        }
                        $this->Madmin->add_item();
                        $this->vars['success'] = 'Item added to database.';
                    }
                }
                $this->load->view('admincp' . DS . 'shop_editor' . DS . 'view.add_item', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function item_list($page = 1, $category = ''){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                $this->vars['items'] = $this->Madmin->load_item_list($page, 25, $category);
                $this->pagination->initialize($page, 25, $this->Madmin->count_items, $this->config->base_url . ACPURL . '/item-list/%s/' . $category);
                $this->vars['pagination'] = $this->pagination->create_links();
                $this->load->view('admincp' . DS . 'shop_editor' . DS . 'view.item_list', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function set_item_price($id){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                if($id != ''){
                    if(!$this->vars['info'] = $this->Madmin->check_item($id)){
                        $this->vars['load_error'] = 'Unable to load item info.';
                    } else{
                        $this->vars['price_info'] = $this->Madmin->load_custom_item_price($this->vars['info']['item_id'], $this->vars['info']['item_cat']);
                        if(isset($_POST['set_item_price'])){
                            if($this->Madmin->set_cutom_item_price($this->vars['info']['item_id'], $this->vars['info']['item_cat'], serialize($_POST['prices']), $this->vars['price_info'])){
                                $this->vars['success'] = 'Successfully updated item price.';
                            } else{
                                $this->vars['error'] = 'Unable to set custom price.';
                            }
                        }
                    }
                } else{
                    $this->vars['load_error'] = 'Invalid item.';
                }
                $this->load->view('admincp' . DS . 'shop_editor' . DS . 'view.set_item_price', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function custom_price_list(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                if(!$this->vars['items'] = $this->Madmin->load_custom_price_list()){
                    $this->vars['load_error'] = 'No items in list.';
                }
                $this->load->view('admincp' . DS . 'shop_editor' . DS . 'view.custom_price_list', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function delete_from_custom_price_list($id){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                if(!$this->Madmin->delete_from_price_list($id)){
                    $this->vars['error'] = 'Unable to remove item.';
                } else{
                    $this->vars['success'] = 'Item removed.';
                }
                $this->load->view('admincp' . DS . 'shop_editor' . DS . 'view.delete_from_list', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function edit_item($id = ''){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                if($id != ''){
                    if(!$this->vars['info'] = $this->Madmin->check_item($id)){
                        $this->vars['load_error'] = 'Unable to load item info.';
                    }
                    if(isset($_POST['edit_item'])){
                        foreach($_POST as $key => $value){
                            $this->Madmin->$key = $value;
                        }
                        if($this->Madmin->vars['item_id'] == '')
                            $this->errors[] = 'Please enter item id.';
                        if($this->Madmin->vars['name'] == '')
                            $this->errors[] = 'Please enter item name.';
                        if($this->Madmin->vars['item_cat'] === '')
                            $this->vars['error'] = 'Please select item category.';
                        if($this->Madmin->vars['original_item_cat'] === '')
                            $this->errors[] = 'Please select item original category.';
                        if($this->Madmin->vars['price'] == '')
                            $this->errors[] = 'Please enter item price.';
                        $image = isset($_FILES['itemimage']) ? $_FILES['itemimage'] : '';
                        if(!empty($image['tmp_name'])){
                            $file_name = $image['name'];
                            $ext = strtolower(substr(strrchr($file_name, "."), 1));
                            //list($imageWidth, $imageHeight, $imageType, $imageAttr) = @getimagesize($image['tmp_name']);
                            $mime = @getimagesize($image['tmp_name']);
                            if($mime){
                                if(!in_array($ext, ['jpg', 'jpeg', 'png', 'gif']))
                                    $this->errors[] = ['You must upload a file with one of the following extensions: ' . implode(', ', ['jpg', 'jpeg', 'png', 'gif'])]; else{
                                    if(!in_array($mime['mime'], ['image/jpeg', 'image/png', 'image/gif']))
                                        $this->errors[] = ['You must upload a file with one of the following extensions: ' . implode(', ', ['jpg', 'jpeg', 'png', 'gif'])];
                                }
                            } else{
                                $this->errors[] = 'Invalid image file.';
                            }
                        }
                        if(count($this->errors) > 0){
                            $this->vars['error'] = $this->errors;
                        } else{
                            if(!empty($image['tmp_name'])){
                                $name = $this->Madmin->vars['item_id'];
                                if($this->Madmin->vars['stick_level'] > 0){
                                    $name .= '-' . $this->Madmin->vars['stick_level'];
                                }
                                $name .= '.' . $ext;
                                if(!$this->Madmin->upload_image($image['tmp_name'], $name, false, BASEDIR . 'assets' . DS . 'item_images' . DS . $this->Madmin->vars['item_cat'] . DS)){
                                    $this->vars['error'] = $this->Madmin->error;
                                }
                            }
                            $this->Madmin->edit_item($id);
                            $this->vars['success'] = 'Item successfully edited.';
                        }
                    }
                } else{
                    $this->vars['load_error'] = 'Invalid item.';
                }
                $this->load->view('admincp' . DS . 'shop_editor' . DS . 'view.edit_item', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function save_item_price($id = ''){
            if($id != ''){
                if(!$this->vars['info'] = $this->Madmin->check_item($id)){
                    json(['error' => 'Item not found.']);
                } else{
                    $price = isset($_POST['price']) ? $_POST['price'] : '';
                    if($price == '' || !is_numeric($price))
                        json(['error' => 'Please enter valid item price.']); else{
                        if($this->Madmin->set_item_price($id, $price)){
                            json(['success' => 'Price saved.']);
                        } else{
                            json(['error' => 'unable to save price.']);
                        }
                    }
                }
            }
        }

        public function delete_item($id = ''){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                if($id == '' || !$this->Madmin->check_item($id)){
                    $this->vars['error'] = 'Unable to delete this item.';
                } else{
                    $this->Madmin->delete_item($id);
                    $this->vars['success'] = 'Item has been removed.';
                }
                $this->load->view('admincp' . DS . 'shop_editor' . DS . 'view.info', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function import_items($cat = 0, $server = ''){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                $this->load->lib('iteminfo');
                if($server == ''){
                    $server = array_key_first($this->website->server_list());
                }
                $this->iteminfo->setItemData(false, (int)$cat, $this->website->get_value_from_server($server, 'item_size'));
				if(isset($_POST['import_items'])){
					if(isset($_POST['import'])){
						if(count($_POST['import']) > 0){
							$this->Madmin->import_shop_items($_POST['import'], $_POST['name'], $_POST['price'], $_POST['slot'], (int)$cat);
							$this->vars['success'] = 'Selected items imported.';
						} else{
							$this->vars['error'] = 'Please select any item to import.';
						}
					} else{
						$this->vars['error'] = 'Please select any item to import.';
					}
				}
				$this->vars['cat'] = (int)$cat;
				$this->vars['items'] = $this->iteminfo->item_data;
				$this->vars['category'] = $this->webshop->category_from_id((int)$cat);											  																 
                $this->load->view('admincp' . DS . 'shop_editor' . DS . 'view.import_items', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function edit_category_list(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                if(isset($_POST['edit_cat'])){
                    foreach($_POST as $key => $value){
                        $this->Madmin->$key = trim($value);
                    }
                    if($this->Madmin->vars['cat_id'] == '')
                        $this->vars['error'] = 'Please enter category id.'; else{
                        if($this->Madmin->vars['cat_name'] == '')
                            $this->vars['error'] = 'Please enter category name.'; else{
                            $this->Madmin->edit_category_list();
                            $this->vars['success'] = 'Category edited.';
                        }
                    }
                }
                if(isset($_POST['add_cat'])){
                    foreach($_POST as $key => $value){
                        $this->Madmin->$key = trim($value);
                    }
                    if($this->Madmin->vars['cat_id'] == '')
                        $this->vars['error'] = 'Please enter category id.'; else{
                        if($this->Madmin->vars['cat_name'] == '')
                            $this->vars['error'] = 'Please enter category name.'; else{
                            if($this->Madmin->cat_not_exists()){
                                if(!$this->Madmin->create_category_image_folder()){
                                    $this->vars['error'] = 'Failed to create category image directory.';
                                }
                                $this->Madmin->add_category();
                                $this->vars['success'] = 'Category added.';
                            } else{
                                $this->vars['error'] = 'Category with this id already exists.';
                            }
                        }
                    }
                }
                if(isset($_POST['delete_cat'])){
                    foreach($_POST as $key => $value){
                        $this->Madmin->$key = trim($value);
                    }
                    if($this->Madmin->vars['cat_id'] == '')
                        $this->vars['error'] = 'Please enter category id.'; else{
                        if($this->Madmin->vars['cat_id'] <= 15)
                            $this->vars['error'] = 'Your not allowed to delete default categories'; else{
                            $this->Madmin->delete_category_image_folder();
                            $this->Madmin->delete_category();
                            $this->vars['success'] = 'Category deleted.';
                        }
                    }
                }
                $this->vars['categories'] = $this->Madmin->load_category_list();
                $this->load->view('admincp' . DS . 'shop_editor' . DS . 'view.category_editor', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function edit_ancient_sets(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                if(isset($_POST['edit_set'])){
                    foreach($_POST as $key => $value){
                        $this->Madmin->$key = trim($value);
                    }
                    if($this->Madmin->vars['set_id'] == '')
                        $this->vars['error'] = 'Invalid set id'; else{
                        if($this->Madmin->vars['set_cat'] == '')
                            $this->vars['error'] = 'Please select valid category'; else{
                            if($this->Madmin->vars['item_id'] == '')
                                $this->vars['error'] = 'Please enter valid item id'; else{
                                $this->Madmin->update_ancient_sets();
                                $this->vars['success'] = 'Ancient set successfully edited.';
                            }
                        }
                    }
                }
                if(isset($_POST['add_set'])){
                    foreach($_POST as $key => $value){
                        $this->Madmin->$key = trim($value);
                    }
                    if($this->Madmin->vars['set_cat'] == '')
                        $this->vars['error'] = 'Please select valid category'; else{
                        if($this->Madmin->vars['item_id'] == '')
                            $this->vars['error'] = 'Please enter valid item id'; else{
                            if($this->Madmin->vars['typeA'] == '' && $this->Madmin->vars['typeB'] == '')
                                $this->vars['error'] = 'Please enter atleast one set type name'; else{
                                $this->Madmin->add_ancient_set();
                                $this->vars['success'] = 'Ancient set successfully added.';
                            }
                        }
                    }
                }
                $this->vars['ancient'] = $this->Madmin->load_ancient_list();
                $this->load->view('admincp' . DS . 'shop_editor' . DS . 'view.ancient_editor', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function edit_socket_options(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                $this->vars['sockets'] = $this->Madmin->load_socket_list();
                $this->load->view('admincp' . DS . 'shop_editor' . DS . 'view.socket_editor', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function save_socket_order(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->Madmin->save_socket_order($_POST['order']);
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function edit_socket_package(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $id = (isset($_POST['id']) && preg_match('/^\d*$/', $_POST['id'])) ? $_POST['id'] : '';
                $name = !empty($_POST['name']) ? htmlspecialchars($_POST['name']) : '';
                $price = (isset($_POST['price']) && preg_match('/^\d*$/', $_POST['price'])) ? $_POST['price'] : '';
                $part_type = (isset($_POST['part_type']) && is_numeric($_POST['part_type'])) ? $_POST['part_type'] : '';
                if($id == '')
                    json(['error' => 'Invalid socket option id']); else{
                    if($name == '')
                        json(['error' => 'Invalid socket option name']); else{
                        if($price == '')
                            json(['error' => 'Invalid socket option price']); else{
                            if($part_type == '')
                                json(['error' => 'Invalid socket option part type']); else{
                                if($this->Madmin->check_socket($id)){
                                    $this->Madmin->edit_socket($id, $name, $price, $part_type);
                                    json(['success' => 'Socket option successfully edited']);
                                } else{
                                    json(['error' => 'Invalid socket option']);
                                }
                            }
                        }
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function change_socket_status(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $id = (isset($_POST['id']) && preg_match('/^\d*$/', $_POST['id'])) ? $_POST['id'] : '';
                $status = (isset($_POST['status']) && preg_match('/^\d*$/', $_POST['status'])) ? $_POST['status'] : '';
                if($id == '')
                    json(['error' => 'Invalid socket option id']); else{
                    if($status == '')
                        json(['error' => 'Invalid socket option status']); else{
                        if($this->Madmin->check_socket($id)){
                            $this->Madmin->change_socket_status($id, $status);
                            json(['success' => 'Socket option status changed']);
                        } else{
                            json(['error' => 'Invalid socket option']);
                        }
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function edit_harmony_options(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                $this->vars['harmony'] = $this->Madmin->load_harmony_list();
                $this->load->view('admincp' . DS . 'shop_editor' . DS . 'view.harmony_editor', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function change_harmony_status(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $id = (isset($_POST['id']) && preg_match('/^\d*$/', $_POST['id'])) ? $_POST['id'] : '';
                $status = (isset($_POST['status']) && preg_match('/^\d*$/', $_POST['status'])) ? $_POST['status'] : '';
                if($id == '')
                    json(['error' => 'Invalid harmony option id']); else{
                    if($status == '')
                        json(['error' => 'Invalid harmony option status']); else{
                        if($this->Madmin->check_harmony($id)){
                            $this->Madmin->change_harmony_status($id, $status);
                            json(['success' => 'Harmony option status changed']);
                        } else{
                            json(['error' => 'Invalid socket']);
                        }
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function edit_harmony_package(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $id = (isset($_POST['id']) && preg_match('/^\d*$/', $_POST['id'])) ? $_POST['id'] : '';
                $name = !empty($_POST['name']) ? htmlspecialchars($_POST['name']) : '';
                $price = (isset($_POST['price']) && preg_match('/^\d*$/', $_POST['price'])) ? $_POST['price'] : '';
                if($id == '')
                    json(['error' => 'Invalid harmony option id']); else{
                    if($name == '')
                        json(['error' => 'Invalid harmony option name']); else{
                        if($price == '')
                            json(['error' => 'Invalid harmony option price']); else{
                            if($this->Madmin->check_harmony($id)){
                                $this->Madmin->edit_harmony($id, $name, $price);
                                json(['success' => 'Harmony option successfully edited']);
                            } else{
                                json(['error' => 'Invalid socket option']);
                            }
                        }
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function save_paypal_order(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->Madmin->save_paypal_order($_POST['order']);
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function save_twocheckout_order(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->Madmin->save_twocheckout_order($_POST['order']);
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function save_pagseguro_order(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->Madmin->save_pagseguro_order($_POST['order']);
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function save_paycall_order(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->Madmin->save_paycall_order($_POST['order']);
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function save_interkassa_order(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->Madmin->save_interkassa_order($_POST['order']);
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function save_cuenta_digital_order(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->Madmin->save_cuenta_digital_order($_POST['order']);
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function edit_paypal_package(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $id = (isset($_POST['id']) && preg_match('/^\d*$/', $_POST['id'])) ? $_POST['id'] : '';
                $title = !empty($_POST['title']) ? htmlspecialchars($_POST['title']) : '';
                $price = (isset($_POST['price']) && is_numeric($_POST['price'])) ? $_POST['price'] : '';
                $currency = !empty($_POST['currency']) ? htmlspecialchars($_POST['currency']) : '';
                $reward = (isset($_POST['reward']) && preg_match('/^\d*$/', $_POST['reward'])) ? $_POST['reward'] : '';
                $server = !empty($_POST['server']) ? htmlspecialchars($_POST['server']) : '';
                if($id == '')
                    json(['error' => 'Invalid package id']); else{
                    if($title == '')
                        json(['error' => 'Invalid package title']); else{
                        if($price == '')
                            json(['error' => 'Invalid package price']); else{
                            if($currency == '')
                                json(['error' => 'Invalid package currency']); else{
                                if($server == '')
                                    json(['error' => 'Invalid server selected']); else{
                                    if($reward == '')
                                        json(['error' => 'Invalid package reward']); else{
                                        if($this->Madmin->check_paypal_package($id)){
                                            $this->Madmin->edit_paypal_package($id, $title, $price, $currency, $reward, $server);
                                            json(['success' => 'Package successfully edited']);
                                        } else{
                                            json(['error' => 'Invalid package']);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function edit_twocheckout_package(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $id = (isset($_POST['id']) && preg_match('/^\d*$/', $_POST['id'])) ? $_POST['id'] : '';
                $title = !empty($_POST['title']) ? htmlspecialchars($_POST['title']) : '';
                $price = (isset($_POST['price']) && is_numeric($_POST['price'])) ? $_POST['price'] : '';
                $currency = !empty($_POST['currency']) ? htmlspecialchars($_POST['currency']) : '';
                $reward = (isset($_POST['reward']) && preg_match('/^\d*$/', $_POST['reward'])) ? $_POST['reward'] : '';
                $server = !empty($_POST['server']) ? htmlspecialchars($_POST['server']) : '';
                if($id == '')
                    json(['error' => 'Invalid package id']); else{
                    if($title == '')
                        json(['error' => 'Invalid package title']); else{
                        if($price == '')
                            json(['error' => 'Invalid package price']); else{
                            if($currency == '')
                                json(['error' => 'Invalid package currency']); else{
                                if($server == '')
                                    json(['error' => 'Invalid server selected']); else{
                                    if($reward == '')
                                        json(['error' => 'Invalid package reward']); else{
                                        if($this->Madmin->check_twocheckout_package($id)){
                                            $this->Madmin->edit_twocheckout_package($id, $title, $price, $currency, $reward, $server);
                                            json(['success' => 'Package successfully edited']);
                                        } else{
                                            json(['error' => 'Invalid package']);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function edit_pagseguro_package(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $id = (isset($_POST['id']) && preg_match('/^\d*$/', $_POST['id'])) ? $_POST['id'] : '';
                $title = !empty($_POST['title']) ? htmlspecialchars($_POST['title']) : '';
                $price = (isset($_POST['price']) && is_numeric($_POST['price'])) ? $_POST['price'] : '';
                $currency = !empty($_POST['currency']) ? htmlspecialchars($_POST['currency']) : '';
                $reward = (isset($_POST['reward']) && preg_match('/^\d*$/', $_POST['reward'])) ? $_POST['reward'] : '';
                $server = !empty($_POST['server']) ? htmlspecialchars($_POST['server']) : '';
                if($id == '')
                    json(['error' => 'Invalid package id']); else{
                    if($title == '')
                        json(['error' => 'Invalid package title']); else{
                        if($price == '')
                            json(['error' => 'Invalid package price']); else{
                            if($currency == '')
                                json(['error' => 'Invalid package currency']); else{
                                if($server == '')
                                    json(['error' => 'Invalid server selected']); else{
                                    if($reward == '')
                                        json(['error' => 'Invalid package reward']); else{
                                        if($this->Madmin->check_pagseguro_package($id)){
                                            $this->Madmin->edit_pagseguro_package($id, $title, $price, $currency, $reward, $server);
                                            json(['success' => 'Package successfully edited']);
                                        } else{
                                            json(['error' => 'Invalid package']);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function edit_paycall_package(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $id = (isset($_POST['id']) && preg_match('/^\d*$/', $_POST['id'])) ? $_POST['id'] : '';
                $title = !empty($_POST['title']) ? htmlspecialchars($_POST['title']) : '';
                $price = (isset($_POST['price']) && is_numeric($_POST['price'])) ? $_POST['price'] : '';
                $reward = (isset($_POST['reward']) && preg_match('/^\d*$/', $_POST['reward'])) ? $_POST['reward'] : '';
                $server = !empty($_POST['server']) ? htmlspecialchars($_POST['server']) : '';
                if($id == '')
                    json(['error' => 'Invalid package id']); else{
                    if($title == '')
                        json(['error' => 'Invalid package title']); else{
                        if($price == '')
                            json(['error' => 'Invalid package price']); else{
                            if($server == '')
                                json(['error' => 'Invalid server selected']); else{
                                if($reward == '')
                                    json(['error' => 'Invalid package reward']); else{
                                    if($this->Madmin->check_paycall_package($id)){
                                        $this->Madmin->edit_paycall_package($id, $title, $price, $reward, $server);
                                        json(['success' => 'Package successfully edited']);
                                    } else{
                                        json(['error' => 'Invalid package']);
                                    }
                                }
                            }
                        }
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function edit_interkassa_package(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $id = (isset($_POST['id']) && preg_match('/^\d*$/', $_POST['id'])) ? $_POST['id'] : '';
                $title = !empty($_POST['title']) ? htmlspecialchars($_POST['title']) : '';
                $price = (isset($_POST['price']) && is_numeric($_POST['price'])) ? $_POST['price'] : '';
                $currency = !empty($_POST['currency']) ? htmlspecialchars($_POST['currency']) : '';
                $reward = (isset($_POST['reward']) && preg_match('/^\d*$/', $_POST['reward'])) ? $_POST['reward'] : '';
                $server = !empty($_POST['server']) ? htmlspecialchars($_POST['server']) : '';
                if($id == '')
                    json(['error' => 'Invalid package id']); else{
                    if($title == '')
                        json(['error' => 'Invalid package title']); else{
                        if($price == '')
                            json(['error' => 'Invalid package price']); else{
                            if($currency == '')
                                json(['error' => 'Invalid package currency']); else{
                                if($server == '')
                                    json(['error' => 'Invalid server selected']); else{
                                    if($reward == '')
                                        json(['error' => 'Invalid package reward']); else{
                                        if($this->Madmin->check_interkassa_package($id)){
                                            $this->Madmin->edit_interkassa_package($id, $title, $price, $currency, $reward, $server);
                                            json(['success' => 'Package successfully edited']);
                                        } else{
                                            json(['error' => 'Invalid package']);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function edit_cuenta_digital_package(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $id = (isset($_POST['id']) && preg_match('/^\d*$/', $_POST['id'])) ? $_POST['id'] : '';
                $title = !empty($_POST['title']) ? htmlspecialchars($_POST['title']) : '';
                $price = (isset($_POST['price']) && is_numeric($_POST['price'])) ? $_POST['price'] : '';
                $currency = !empty($_POST['currency']) ? htmlspecialchars($_POST['currency']) : '';
                $reward = (isset($_POST['reward']) && preg_match('/^\d*$/', $_POST['reward'])) ? $_POST['reward'] : '';
                $server = !empty($_POST['server']) ? htmlspecialchars($_POST['server']) : '';
                if($id == '')
                    json(['error' => 'Invalid package id']); else{
                    if($title == '')
                        json(['error' => 'Invalid package title']); else{
                        if($price == '')
                            json(['error' => 'Invalid package price']); else{
                            if($currency == '')
                                json(['error' => 'Invalid package currency']); else{
                                if($server == '')
                                    json(['error' => 'Invalid server selected']); else{
                                    if($reward == '')
                                        json(['error' => 'Invalid package reward']); else{
                                        if($this->Madmin->check_cuenta_digital_package($id)){
                                            $this->Madmin->edit_cuenta_digital_package($id, $title, $price, $currency, $reward, $server);
                                            json(['success' => 'Package successfully edited']);
                                        } else{
                                            json(['error' => 'Invalid package']);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function add_paypal_package(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $title = !empty($_POST['title']) ? htmlspecialchars($_POST['title']) : '';
                $price = (isset($_POST['price']) && is_numeric($_POST['price'])) ? $_POST['price'] : '';
                $currency = !empty($_POST['currency']) ? htmlspecialchars($_POST['currency']) : '';
                $reward = (isset($_POST['reward']) && preg_match('/^\d*$/', $_POST['reward'])) ? $_POST['reward'] : '';
                $server = !empty($_POST['server']) ? htmlspecialchars($_POST['server']) : '';
                if($title == '')
                    json(['error' => 'Invalid package title']); else{
                    if($price == '')
                        json(['error' => 'Invalid package price']); else{
                        if($currency == '')
                            json(['error' => 'Invalid package currency']); else{
                            if($server == '')
                                json(['error' => 'Invalid server selected']); else{
                                if($reward == '')
                                    json(['error' => 'Invalid package reward']); else{
                                    if($id = $this->Madmin->add_paypal_package($title, $price, $currency, $reward, $server)){
                                        json(['success' => 'Package successfully added', 'id' => $id, 'server' => $server, 'servers' => $this->website->server_list()]);
                                    } else{
                                        json(['error' => 'Unable to add new package']);
                                    }
                                }
                            }
                        }
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function add_twocheckout_package(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $title = !empty($_POST['title']) ? htmlspecialchars($_POST['title']) : '';
                $price = (isset($_POST['price']) && is_numeric($_POST['price'])) ? $_POST['price'] : '';
                $currency = !empty($_POST['currency']) ? htmlspecialchars($_POST['currency']) : '';
                $reward = (isset($_POST['reward']) && preg_match('/^\d*$/', $_POST['reward'])) ? $_POST['reward'] : '';
                $server = !empty($_POST['server']) ? htmlspecialchars($_POST['server']) : '';
                if($title == '')
                    json(['error' => 'Invalid package title']); else{
                    if($price == '')
                        json(['error' => 'Invalid package price']); else{
                        if($currency == '')
                            json(['error' => 'Invalid package currency']); else{
                            if($server == '')
                                json(['error' => 'Invalid server selected']); else{
                                if($reward == '')
                                    json(['error' => 'Invalid package reward']); else{
                                    if($id = $this->Madmin->add_twocheckout_package($title, $price, $currency, $reward, $server)){
                                        json(['success' => 'Package successfully added', 'id' => $id, 'server' => $server, 'servers' => $this->website->server_list()]);
                                    } else{
                                        json(['error' => 'Unable to add new package']);
                                    }
                                }
                            }
                        }
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function add_pagseguro_package(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $title = !empty($_POST['title']) ? htmlspecialchars($_POST['title']) : '';
                $price = (isset($_POST['price']) && is_numeric($_POST['price'])) ? $_POST['price'] : '';
                $currency = !empty($_POST['currency']) ? htmlspecialchars($_POST['currency']) : '';
                $reward = (isset($_POST['reward']) && preg_match('/^\d*$/', $_POST['reward'])) ? $_POST['reward'] : '';
                $server = !empty($_POST['server']) ? htmlspecialchars($_POST['server']) : '';
                if($title == '')
                    json(['error' => 'Invalid package title']); else{
                    if($price == '')
                        json(['error' => 'Invalid package price']); else{
                        if($currency == '')
                            json(['error' => 'Invalid package currency']); else{
                            if($server == '')
                                json(['error' => 'Invalid server selected']); else{
                                if($reward == '')
                                    json(['error' => 'Invalid package reward']); else{
                                    if($id = $this->Madmin->add_pagseguro_package($title, $price, $currency, $reward, $server)){
                                        json(['success' => 'Package successfully added', 'id' => $id, 'server' => $server, 'servers' => $this->website->server_list()]);
                                    } else{
                                        json(['error' => 'Unable to add new package']);
                                    }
                                }
                            }
                        }
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function add_paycall_package(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $title = !empty($_POST['title']) ? htmlspecialchars($_POST['title']) : '';
                $price = (isset($_POST['price']) && is_numeric($_POST['price'])) ? $_POST['price'] : '';
                $reward = (isset($_POST['reward']) && preg_match('/^\d*$/', $_POST['reward'])) ? $_POST['reward'] : '';
                $server = !empty($_POST['server']) ? htmlspecialchars($_POST['server']) : '';
                if($title == '')
                    json(['error' => 'Invalid package title']); else{
                    if($price == '')
                        json(['error' => 'Invalid package price']); else{
                        if($server == '')
                            json(['error' => 'Invalid server selected']); else{
                            if($reward == '')
                                json(['error' => 'Invalid package reward']); else{
                                if($id = $this->Madmin->add_paycall_package($title, $price, $reward, $server)){
                                    json(['success' => 'Package successfully added', 'id' => $id, 'server' => $server, 'servers' => $this->website->server_list()]);
                                } else{
                                    json(['error' => 'Unable to add new package']);
                                }
                            }
                        }
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function add_interkassa_package(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $title = !empty($_POST['title']) ? htmlspecialchars($_POST['title']) : '';
                $price = (isset($_POST['price']) && is_numeric($_POST['price'])) ? $_POST['price'] : '';
                $currency = !empty($_POST['currency']) ? htmlspecialchars($_POST['currency']) : '';
                $reward = (isset($_POST['reward']) && preg_match('/^\d*$/', $_POST['reward'])) ? $_POST['reward'] : '';
                $server = !empty($_POST['server']) ? htmlspecialchars($_POST['server']) : '';
                if($title == '')
                    json(['error' => 'Invalid package title']); else{
                    if($price == '')
                        json(['error' => 'Invalid package price']); else{
                        if($currency == '')
                            json(['error' => 'Invalid package currency']); else{
                            if($server == '')
                                json(['error' => 'Invalid server selected']); else{
                                if($reward == '')
                                    json(['error' => 'Invalid package reward']); else{
                                    if($id = $this->Madmin->add_interkassa_package($title, $price, $currency, $reward, $server)){
                                        json(['success' => 'Package successfully added', 'id' => $id, 'server' => $server, 'servers' => $this->website->server_list()]);
                                    } else{
                                        json(['error' => 'Unable to add new package']);
                                    }
                                }
                            }
                        }
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function add_cuenta_digital_package(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $title = !empty($_POST['title']) ? htmlspecialchars($_POST['title']) : '';
                $price = (isset($_POST['price']) && is_numeric($_POST['price'])) ? $_POST['price'] : '';
                $currency = !empty($_POST['currency']) ? htmlspecialchars($_POST['currency']) : '';
                $reward = (isset($_POST['reward']) && preg_match('/^\d*$/', $_POST['reward'])) ? $_POST['reward'] : '';
                $server = !empty($_POST['server']) ? htmlspecialchars($_POST['server']) : '';
                if($title == '')
                    json(['error' => 'Invalid package title']); else{
                    if($price == '')
                        json(['error' => 'Invalid package price']); else{
                        if($currency == '')
                            json(['error' => 'Invalid package currency']); else{
                            if($server == '')
                                json(['error' => 'Invalid server selected']); else{
                                if($reward == '')
                                    json(['error' => 'Invalid package reward']); else{
                                    if($id = $this->Madmin->add_cuenta_digital_package($title, $price, $currency, $reward, $server)){
                                        json(['success' => 'Package successfully added', 'id' => $id, 'server' => $server, 'servers' => $this->website->server_list()]);
                                    } else{
                                        json(['error' => 'Unable to add new package']);
                                    }
                                }
                            }
                        }
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function delete_paypal_package(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $id = (isset($_POST['id']) && preg_match('/^\d*$/', $_POST['id'])) ? $_POST['id'] : '';
                if($id == '')
                    json(['error' => 'Invalid package id']); else{
                    if($this->Madmin->check_paypal_package($id)){
                        $this->Madmin->delete_paypal_package($id);
                        json(['success' => 'Package successfully removed']);
                    } else{
                        json(['error' => 'Invalid package']);
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function delete_twocheckout_package(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $id = (isset($_POST['id']) && preg_match('/^\d*$/', $_POST['id'])) ? $_POST['id'] : '';
                if($id == '')
                    json(['error' => 'Invalid package id']); else{
                    if($this->Madmin->check_twocheckout_package($id)){
                        $this->Madmin->delete_twocheckout_package($id);
                        json(['success' => 'Package successfully removed']);
                    } else{
                        json(['error' => 'Invalid package']);
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function delete_pagseguro_package(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $id = (isset($_POST['id']) && preg_match('/^\d*$/', $_POST['id'])) ? $_POST['id'] : '';
                if($id == '')
                    json(['error' => 'Invalid package id']); else{
                    if($this->Madmin->check_pagseguro_package($id)){
                        $this->Madmin->delete_pagseguro_package($id);
                        json(['success' => 'Package successfully removed']);
                    } else{
                        json(['error' => 'Invalid package']);
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function delete_paycall_package(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $id = (isset($_POST['id']) && preg_match('/^\d*$/', $_POST['id'])) ? $_POST['id'] : '';
                if($id == '')
                    json(['error' => 'Invalid package id']); else{
                    if($this->Madmin->check_paycall_package($id)){
                        $this->Madmin->delete_paycall_package($id);
                        json(['success' => 'Package successfully removed']);
                    } else{
                        json(['error' => 'Invalid package']);
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function delete_interkassa_package(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $id = (isset($_POST['id']) && preg_match('/^\d*$/', $_POST['id'])) ? $_POST['id'] : '';
                if($id == '')
                    json(['error' => 'Invalid package id']); else{
                    if($this->Madmin->check_interkassa_package($id)){
                        $this->Madmin->delete_interkassa_package($id);
                        json(['success' => 'Package successfully removed']);
                    } else{
                        json(['error' => 'Invalid package']);
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function delete_cuenta_digital_package(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $id = (isset($_POST['id']) && preg_match('/^\d*$/', $_POST['id'])) ? $_POST['id'] : '';
                if($id == '')
                    json(['error' => 'Invalid package id']); else{
                    if($this->Madmin->check_cuenta_digital_package($id)){
                        $this->Madmin->delete_cuenta_digital_package($id);
                        json(['success' => 'Package successfully removed']);
                    } else{
                        json(['error' => 'Invalid package']);
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function change_paypal_status(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $id = (isset($_POST['id']) && preg_match('/^\d*$/', $_POST['id'])) ? $_POST['id'] : '';
                $status = (isset($_POST['status']) && preg_match('/^\d*$/', $_POST['status'])) ? $_POST['status'] : '';
                if($id == '')
                    json(['error' => 'Invalid package id']); else{
                    if($status == '')
                        json(['error' => 'Invalid package status']); else{
                        if($this->Madmin->check_paypal_package($id)){
                            $this->Madmin->change_paypal_status($id, $status);
                            json(['success' => 'Package status changed']);
                        } else{
                            json(['error' => 'Invalid package']);
                        }
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function change_twocheckout_status(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $id = (isset($_POST['id']) && preg_match('/^\d*$/', $_POST['id'])) ? $_POST['id'] : '';
                $status = (isset($_POST['status']) && preg_match('/^\d*$/', $_POST['status'])) ? $_POST['status'] : '';
                if($id == '')
                    json(['error' => 'Invalid package id']); else{
                    if($status == '')
                        json(['error' => 'Invalid package status']); else{
                        if($this->Madmin->check_twocheckout_package($id)){
                            $this->Madmin->change_twocheckout_status($id, $status);
                            json(['success' => 'Package status changed']);
                        } else{
                            json(['error' => 'Invalid package']);
                        }
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function change_pagseguro_status(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $id = (isset($_POST['id']) && preg_match('/^\d*$/', $_POST['id'])) ? $_POST['id'] : '';
                $status = (isset($_POST['status']) && preg_match('/^\d*$/', $_POST['status'])) ? $_POST['status'] : '';
                if($id == '')
                    json(['error' => 'Invalid package id']); else{
                    if($status == '')
                        json(['error' => 'Invalid package status']); else{
                        if($this->Madmin->check_pagseguro_package($id)){
                            $this->Madmin->change_pagseguro_status($id, $status);
                            json(['success' => 'Package status changed']);
                        } else{
                            json(['error' => 'Invalid package']);
                        }
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function change_paycall_status(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $id = (isset($_POST['id']) && preg_match('/^\d*$/', $_POST['id'])) ? $_POST['id'] : '';
                $status = (isset($_POST['status']) && preg_match('/^\d*$/', $_POST['status'])) ? $_POST['status'] : '';
                if($id == '')
                    json(['error' => 'Invalid package id']); else{
                    if($status == '')
                        json(['error' => 'Invalid package status']); else{
                        if($this->Madmin->check_paycall_package($id)){
                            $this->Madmin->change_paycall_status($id, $status);
                            json(['success' => 'Package status changed']);
                        } else{
                            json(['error' => 'Invalid package']);
                        }
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function change_interkassa_status(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $id = (isset($_POST['id']) && preg_match('/^\d*$/', $_POST['id'])) ? $_POST['id'] : '';
                $status = (isset($_POST['status']) && preg_match('/^\d*$/', $_POST['status'])) ? $_POST['status'] : '';
                if($id == '')
                    json(['error' => 'Invalid package id']); else{
                    if($status == '')
                        json(['error' => 'Invalid package status']); else{
                        if($this->Madmin->check_interkassa_package($id)){
                            $this->Madmin->change_interkassa_status($id, $status);
                            json(['success' => 'Package status changed']);
                        } else{
                            json(['error' => 'Invalid package']);
                        }
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function change_cuenta_digital_status(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $id = (isset($_POST['id']) && preg_match('/^\d*$/', $_POST['id'])) ? $_POST['id'] : '';
                $status = (isset($_POST['status']) && preg_match('/^\d*$/', $_POST['status'])) ? $_POST['status'] : '';
                if($id == '')
                    json(['error' => 'Invalid package id']); else{
                    if($status == '')
                        json(['error' => 'Invalid package status']); else{
                        if($this->Madmin->check_cuenta_digital_package($id)){
                            $this->Madmin->change_cuenta_digital_status($id, $status);
                            json(['success' => 'Package status changed']);
                        } else{
                            json(['error' => 'Invalid package']);
                        }
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function add_referral_package(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $req_lvl = (isset($_POST['req_lvl']) && preg_match('/^\d*$/', $_POST['req_lvl'])) ? $_POST['req_lvl'] : '';
                $req_res = (isset($_POST['req_res']) && preg_match('/^\d*$/', $_POST['req_res'])) ? $_POST['req_res'] : '';
                $req_gres = (isset($_POST['req_gres']) && preg_match('/^\d*$/', $_POST['req_gres'])) ? $_POST['req_gres'] : '';
                $reward = (isset($_POST['reward']) && preg_match('/^\d*$/', $_POST['reward'])) ? $_POST['reward'] : '';
                $reward_type = (isset($_POST['reward_type']) && in_array($_POST['reward_type'], [1, 2, 3])) ? $_POST['reward_type'] : '';
                $server = !empty($_POST['server']) ? htmlspecialchars($_POST['server']) : '';
                if($req_lvl == '')
                    json(['error' => 'Please select valid level']); else{
                    if($req_res == '')
                        json(['error' => 'Please select valid reset']); else{
                        if($req_gres == '')
                            json(['error' => 'Please select valid grand reset']); else{
                            if($server == '')
                                json(['error' => 'Invalid server selected']); else{
                                if($reward == '')
                                    json(['error' => 'Invalid referral reward']); else{
                                    if($reward_type == '')
                                        json(['error' => 'Please select valid reward type']); else{
                                        if(!$this->Madmin->check_referral_reward($req_lvl, $req_res, $req_gres, $reward_type, $server)){
                                            if($id = $this->Madmin->add_referral_reward($req_lvl, $req_res, $req_gres, $reward, $reward_type, $server)){
                                                json(['success' => 'Reward successfully added', 'id' => $id, 'server' => $this->website->get_value_from_server($server, 'title'), 'reward_type' => $this->website->translate_credits($reward_type, $server)]);
                                            } else{
                                                json(['error' => 'Unable to add new reward']);
                                            }
                                        } else{
                                            json(['error' => 'Referral reward already exists.']);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function change_referral_reward_status(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $id = (isset($_POST['id']) && preg_match('/^\d*$/', $_POST['id'])) ? $_POST['id'] : '';
                $status = (isset($_POST['status']) && preg_match('/^\d*$/', $_POST['status'])) ? $_POST['status'] : '';
                if($id == '')
                    json(['error' => 'Invalid reward id']); else{
                    if($status == '')
                        json(['error' => 'Invalid reward status']); else{
                        if($this->Madmin->check_referral_reward_status($id)){
                            $this->Madmin->change_referral_reward_status($id, $status);
                            json(['success' => 'Reward status changed']);
                        } else{
                            json(['error' => 'Invalid reward']);
                        }
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function delete_referral_reward(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $id = (isset($_POST['id']) && preg_match('/^\d*$/', $_POST['id'])) ? $_POST['id'] : '';
                if($id == '')
                    json(['error' => 'Invalid reward id']); else{
                    if($this->Madmin->check_referral_reward_status($id)){
                        $this->Madmin->delete_referral_reward($id);
                        json(['success' => 'Reward successfully removed']);
                    } else{
                        json(['error' => 'Invalid reward']);
                    }
                }
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function credits_editor($acc = ''){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                $this->vars['acc'] = $acc;
                $this->vars['found'] = false;
                if(isset($_POST['edit_credits'])){
                    $this->vars['acc'] = trim(isset($_POST['username']) ? $_POST['username'] : '');
                    $server = trim(isset($_POST['server']) ? $_POST['server'] : '');
                    $amount = trim(isset($_POST['amount']) ? ctype_digit($_POST['amount']) ? (int)$_POST['amount'] : '' : '');
                    $type = trim(isset($_POST['c_type']) ? ctype_digit($_POST['c_type']) ? (int)$_POST['c_type'] : '' : '');
                    $act = trim(isset($_POST['act']) ? ctype_digit($_POST['act']) ? (int)$_POST['act'] : '' : '');
                    $this->load->model('account');
                    if($this->vars['acc'] == '')
                        $this->vars['error'] = 'Please enter usename.'; 
					else{
                        if($server == '')
                            $this->vars['error'] = 'Please select server.'; 
						else{
                            if($amount == '')
                                $this->vars['error'] = 'Please enter amount of credits.'; 
							else{
                                if($type == '')
                                    $this->vars['error'] = 'Please select credit type you want to add.'; 
								else{
                                    if($act == '')
                                        $this->vars['error'] = 'Please select action.'; 
									else{
                                        if($acc_info = $this->Madmin->acc_exists($this->vars['acc'], $server)){
                                            if($_POST['act'] == 1){
                                                $this->website->add_credits($this->vars['acc'], $server, $amount, $type, false, $acc_info['memb_guid']);
                                                $this->Madmin->add_account_log('Added ' . $this->website->translate_credits($type, $server) . ' by system', $amount, $this->vars['acc'], $server);
												if($this->config->is_plugin_installed('accumulated_donation_rewards')){
													if($this->config->values('accumulated_donation_rewards', [$server, 'active']) == 1){
														$this->Madmin->add_total_recharge($this->vars['acc'], $server, $amount);
													}
												}
											} 
											else{
                                                $this->website->charge_credits($this->vars['acc'], $server, $amount, $type, $acc_info['memb_guid']);
                                                $this->Madmin->add_account_log('Removed ' . $this->website->translate_credits($type, $server) . ' by system', -$amount, $this->vars['acc'], $server);
                                            }
                                            $this->vars['success'] = 'Credits successfully edited.';
                                        } 
										else{
                                            $this->vars['similar_accounts'] = $this->Madmin->search_similar_accounts($this->vars['acc'], $server);
                                            if(count($this->vars['similar_accounts']) > 0){
                                                $this->vars['found'] = true;
                                            } 
											else{
                                                $this->vars['error'] = 'Account not found.';
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                $this->vars['found_acc_credits'] = false;
                if(isset($_POST['view_credits'])){
                    $this->vars['user'] = trim(isset($_POST['username']) ? $_POST['username'] : '');
                    $this->vars['server'] = trim(isset($_POST['server']) ? $_POST['server'] : '');
                    if($this->vars['user'] == '')
                        $this->vars['error2'] = 'Please enter usename.'; else{
                        if($this->vars['server'] == '')
                            $this->vars['error2'] = 'Please select server.'; else{
                            if($acc_info = $this->Madmin->acc_exists($this->vars['user'], $this->vars['server'])){
                                $this->vars['credits_info']['server'] = $this->vars['server'];
                                $this->vars['credits_info']['credits'] = $this->website->get_user_credits_balance($this->vars['user'], $this->vars['server'], 1, $acc_info['memb_guid']);
                                $this->vars['credits_info']['credits2'] = $this->website->get_user_credits_balance($this->vars['user'], $this->vars['server'], 2, $acc_info['memb_guid']);
                                $this->vars['credits_info']['credits3'] = $this->website->get_user_credits_balance($this->vars['user'], $this->vars['server'], 3, $acc_info['memb_guid']);
                                if(count($this->vars['credits_info']) > 0){
                                    $this->vars['found_acc_credits'] = true;
                                }
                            } else{
                                $this->vars['error2'] = 'User not found.';
                            }
                        }
                    }
                }
                $this->load->view('admincp' . DS . 'shop_editor' . DS . 'view.credits_editor', $this->vars);
                $this->load_footer();
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function vote_links(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                $this->vars['votereward_config'] = $this->config->values('votereward_config');
                if(count($_POST) > 0){
                    foreach($_POST as $key => $value){
                        $this->Madmin->$key = trim($value);
                    }
                    if($_POST['votelink'] == '')
                        $this->vars['error'] = 'Please enter valid voting url'; 
                    else{
                        if($_POST['name'] == '')
                            $this->vars['error'] = 'Please enter voting site name'; 
                        else{
                            if($_POST['img_url'] == '')
                                $this->vars['error'] = 'Please enter voting button image url'; 
                            else{
                                if($_POST['reward'] == '' || !ctype_digit($_POST['reward']))
                                    $this->vars['error'] = 'Please enter valid reward amount.'; 
                                else{
                                    if($_POST['voting_api'] == 2 && $_POST['mmotop_stats_url'] == '')
                                        $this->vars['error'] = 'Please enter valid mmotop stats api url.'; 
                                    else{
                                        if($_POST['voting_api'] == 2 && ($_POST['mmotop_reward_sms'] == '' || !ctype_digit($_POST['mmotop_reward_sms'])))
                                            $this->vars['error'] = 'Please enter valid mmotop sms reward amount.'; else{
                                            if($_POST['server'] == '')
                                                $this->vars['error'] = 'Please select server.'; else{
                                                $this->Madmin->add_vote_link();
                                                $this->vars['success'] = 'Voting link successfully added.';
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                $this->vars['vote_links'] = $this->Madmin->load_vote_links();
                $this->load->view('admincp' . DS . 'vote_manager' . DS . 'view.links_editor', $this->vars);
                $this->load_footer();
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function edit_vote($id){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                $this->vars['votereward_config'] = $this->config->values('votereward_config');
                if($id == '')
                    $this->vars['vote']['error'] = 'Voting link not found'; else{
                    if($this->Madmin->voting_link_exists($id)){
                        if(count($_POST) > 0){
                            foreach($_POST as $key => $value){
                                $this->Madmin->$key = trim($value);
                            }
                            if($_POST['votelink'] == '')
                                $this->vars['error'] = 'Please enter valid voting url'; else{
                                if($_POST['name'] == '')
                                    $this->vars['error'] = 'Please enter voting site name'; else{
                                    if($_POST['img_url'] == '')
                                        $this->vars['error'] = 'Please enter voting button image url'; else{
                                        if($_POST['reward'] == '' || !ctype_digit($_POST['reward']))
                                            $this->vars['error'] = 'Please enter valid reward amount.'; else{
                                            if($_POST['voting_api'] == 2 && $_POST['mmotop_stats_url'] == '')
                                                $this->vars['error'] = 'Please enter valid mmotop stats api url.'; else{
                                                if($_POST['voting_api'] == 2 && ($_POST['mmotop_reward_sms'] == '' || !ctype_digit($_POST['mmotop_reward_sms'])))
                                                    $this->vars['error'] = 'Please enter valid mmotop sms reward amount.'; else{
                                                    if($_POST['server'] == '')
                                                        $this->vars['error'] = 'Please select server.'; else{
                                                        $this->Madmin->edit_vote_link($id);
                                                        $this->vars['success'] = 'Voting link successfully edited.';
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        $this->vars['link_data'] = $this->Madmin->vote_link_info;
                    } else{
                        $this->vars['vote']['error'] = 'Voting link not found';
                    }
                }
                $this->vars['vote_links'] = $this->Madmin->load_vote_links();
                $this->load->view('admincp' . DS . 'vote_manager' . DS . 'view.edit_existing_link', $this->vars);
                $this->load_footer();
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function delete_vote($id = ''){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                if($id == '')
                    $this->vars['error'] = 'Voting link not found'; else{
                    if($this->Madmin->voting_link_exists($id)){
                        $this->Madmin->delete_voting_link($id);
                        $this->vars['success'] = 'Voting link successfully deleted';
                    } else{
                        $this->vars['error'] = 'Voting link not found';
                    }
                }
                $this->load->view('admincp' . DS . 'vote_manager' . DS . 'view.info', $this->vars);
                $this->load_footer();
            } else{
                json(['error' => 'Please login first!']);
            }
        }

        public function top_voters(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                $this->load->view('admincp' . DS . 'vote_manager' . DS . 'view.top_voters', $this->vars);
                $this->load_footer();
            } else{
                json(['error' => 'Please login first!']);
            }
        }

		public function languages(){
			if ($this->session->userdata(array('admin' => 'is_admin'))) {
				$this->load_header();
				$this->load->helper('locales');
				$this->vars['languages'] = $this->config->values('locale_config');
				$this->load->view('admincp' . DS . 'language_manager' . DS . 'view.list_languages', $this->vars);
				$this->load_footer();
			} else {
				json(array('error' => 'Please login first!'));
			}
        }

		public function translate($lang = 'en'){
            if($this->session->userdata(array('admin'=>'is_admin'))){
				$this->load_header();
				$this->load->helper('locales');

				$this->locales->loadTranslation($lang);
				$this->vars['strings'] = $this->locales->getTranslationStrings();
				$this->vars['newStrings'] = [];
				
				foreach($this->vars['strings'] AS $key => $val){
					$this->vars['newStrings'][] = [
						$key => $val
					];
				}

				$this->vars['lang'] = $lang;

				$this->load->view('admincp'.DS.'language_manager'.DS.'view.edit_language', $this->vars);
				$this->load_footer();
			}
			else{
				json(array('error' => 'Please login first!'));
			}
        }

				
		public function edit_string(){
            if($this->session->userdata(array('admin'=>'is_admin'))){				
				$lang = $_POST['lang'];
				$lkey = $_POST['key'];
				$ltext = $_POST['text'];
				
				$this->load->helper('locales');
				$this->locales->loadTranslation($lang);
				
				$this->vars['strings'] = $this->locales->getTranslationStrings();
				$this->vars['newStrings'] = [];
				
				foreach($this->vars['strings'] AS $key => $val){
					$this->vars['newStrings'][] = [
						$key => $val
					];
				}
				
				$oldKey = false;
				
				foreach($this->vars['newStrings'] AS $k => $langData){
					if($k == $lkey){
						$oldKey = array_keys($langData);
						break;
					}
				}

				if($oldKey != false){
					$translations = Translations::fromJsonFile(APP_PATH . DS . 'localization' . DS . $lang . '.json');
					
					$translation = $translations->find(null, $oldKey[0]);
					if($translation) {
						$translation->setTranslation($ltext);
					}
					$translations->toJsonFile(APP_PATH . DS . 'localization' . DS . $lang . '.json', ['includeHeaders' => false, 'json' => JSON_PRETTY_PRINT]);
				}					
			}
			else{
				json(array('error' => 'Please login first!'));
			}
        }

				
		public function add_string(){
            if($this->session->userdata(array('admin'=>'is_admin'))){
				$lang = $_POST['lang'];
				$lkey = $_POST['key'];
				$ltext = $_POST['text'];
				
				$this->load->helper('locales');
				
				$this->locales->loadTranslation($lang);
				
				$this->vars['strings'] = $this->locales->getTranslationStrings();
				
				$translations = Translations::fromJsonFile(APP_PATH . DS . 'localization' . DS . $lang . '.json');
				
				$findString = $translations->find(null, $lkey);				
				
				if($findString){
					$findString->setTranslation($ltext);
				}
				else{
					$translations->insert('', $lkey);
					$findString = $translations->find(null, $lkey);
					if($findString){
						$findString->setTranslation($ltext);
					}
				}
				
				$translations->toJsonFile(APP_PATH . DS . 'localization' . DS . $lang . '.json', ['json' => JSON_PRETTY_PRINT]);		
				
				$this->vars['strings'] = $this->locales->getTranslationStrings();
				$this->vars['newStrings'] = [];
				$newKey = false;
				
				foreach($this->vars['strings'] AS $key => $val){
					$this->vars['newStrings'][] = [
						$key => $val
					];
				}
				
				foreach($this->vars['newStrings'] AS $k => $v){
					$vkeys = array_keys($v);
					if($vkeys[0] == $lkey){
						$newKey = $k;
						break;
					}
				}
				
				json(array('success' => 'Language string added', 'key' => $newKey));	
			}
			else{
				json(array('error' => 'Please login first!'));
			}
        }

				
		public function add_language(){  
			if($this->session->userdata(array('admin' => 'is_admin'))){
				$this->load_header();
				$this->load->helper('locales');
				$this->vars['all_languages'] = $this->locales->allLanguages();
				
				if(isset($_POST['add_language'])){
					if($_POST['short_code'] == '') {
						$this->vars['short_code'] = 'Please select language.';
					} 
					else{
						$lang = $_POST['short_code'];
						$languages = $this->config->values('locale_config');
						if(isset($languages['localizations'][$lang])){
							$this->vars['error'] = 'Language already exists.';
						}
						else{							
							$translations = json_decode(file_get_contents(APP_PATH . DS . 'localization' . DS . $languages['default_localization'] . '.json'), true);	
							foreach($translations['messages'][''] AS $key => $data){
								$translations['messages'][''][$key][0] = '';
							}

							$translations = Translations::fromJsonString(json_encode($translations));
							$translations->toJsonFile(APP_PATH . DS . 'localization' . DS . $lang . '.json', ['json' => JSON_PRETTY_PRINT]);
							$languages['localizations'][$lang] = 1;
							$this->config->save_config_data($languages, 'locale_config');
							$this->vars['success'] = 'Language successfully added.';
						}
					}
				}
				$this->load->view('admincp' . DS . 'language_manager' . DS . 'view.add_language', $this->vars);
				$this->load_footer();
			} else {
				json(array('error' => 'Please login first!'));
			}
        }

		public function import_language(){
			if ($this->session->userdata(array('admin' => 'is_admin'))){
				$this->load_header();
				$this->load->helper('locales');
				
				$this->vars['all_languages'] = $this->locales->allLanguages();
				if(count($_POST) > 0){
					$lang = $_POST['short_code'];
					if ($_FILES['language']['name'] == '')
						$this->vars['error'] = 'Please select file to upload';
					else {
						$file_name = $_FILES['language']['name'];
						$ext = strtolower(substr(strrchr($file_name, "."), 1));
						if(!in_array($ext, ['json', 'mo', 'po']))
							$this->vars['error'] = 'You must upload a file with one of the following extensions: ' . implode(', ', ['json', 'mo', 'po']);
						else{
							if($_FILES['language']['error'] == UPLOAD_ERR_OK && is_uploaded_file($_FILES['language']['tmp_name'])){
								$languages = $this->config->values('locale_config');

								if($ext == 'json'){
									$translations = Translations::fromJsonFile($_FILES['language']['tmp_name']);		
								}
								if($ext == 'po'){
									$translations = Translations::fromPoFile($_FILES['language']['tmp_name']);
								}
								if($ext == 'mo'){
									$translations = Translations::fromMoFile($_FILES['language']['tmp_name']);
								}
								
								$translations->toJsonFile(APP_PATH . DS . 'localization' . DS . $lang . '.json', ['json' => JSON_PRETTY_PRINT]);
								$languages['localizations'][$lang] = 1;
								$this->config->save_config_data($languages, 'locale_config');
								$this->vars['success'] = 'Language successfully imported.';
							}
						}
					}
				}
				$this->load->view('admincp' . DS . 'language_manager' . DS . 'view.import_language', $this->vars);
				$this->load_footer();
			} else {
				$this->login();
			}
        }

		public function export_language($lang){
			if($this->session->userdata(array('admin' => 'is_admin'))){
				$languages = $this->config->values('locale_config');
				if(array_key_exists($lang, $languages['localizations'])){
					$file = APP_PATH . DS . 'localization' . DS . htmlspecialchars($lang) . '.json';
					if(is_file($file)){
						$file_data = json_decode(file_get_contents($file));
						$file_data = json_encode($file_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
						header("Content-type: application/javascript");
						header("Content-Disposition: attachment; filename=dmnmucms_language_export_" . htmlspecialchars($lang) . ".json");
						echo $file_data;
						exit;
					}
					else{
						json(array('error' => 'Unable to find language file.'));
					}
				} 
				else{
					json(array('error' => 'Unable to find language.'));
				}
			} 
			else{
				json(array('error' => 'Please login first!'));
			}
        }

				
		public function import_missing_translations($lang){
			if ($this->session->userdata(array('admin' => 'is_admin'))){
				$this->load_header();
				$this->load->helper('locales');
				
				$languages = $this->config->values('locale_config');
				
				if(array_key_exists($lang, $languages['localizations'])){
					$file = APP_PATH . DS . 'localization' . DS . htmlspecialchars($lang) . '.json';
					$fileDeault = APP_PATH . DS . 'localization' . DS . 'en.json';
					if(is_file($file)){
						if(is_file($fileDeault)){
							$translations = json_decode(file_get_contents($file), true);	
							$translationsDedault = json_decode(file_get_contents($fileDeault), true);	
							foreach($translationsDedault['messages'][''] AS $key => $data){
								if(isset($translations['messages'][''][$key])){
									continue;
								}
								$translations['messages'][''][$key][0] = '';
							}
							
							$data = json_encode($translations, JSON_PRETTY_PRINT);
							if(is_writable(APP_PATH . DS . 'localization')){
								$fp = @fopen(APP_PATH . DS . 'localization' . DS . htmlspecialchars($lang) . '.json', 'w');
								@fwrite($fp, $data);
								@fclose($fp);
								$this->vars['success'] = 'Missing strings imported.';
							} 
							else{
								$this->vars['error'] = 'Folder application' . DS . 'localization is not writable';
							}
						}
						else{
							$this->vars['error'] = 'Unable to find en.json';
						}
					}
					else{
						$this->vars['error'] = 'Unable to find localization file';
					}
				} 
				else{
					$this->vars['error'] = 'Unable to find language';
				}
				$this->load->view('admincp' . DS . 'language_manager' . DS . 'view.import_missing_translations', $this->vars);
				$this->load_footer();
			} else {
				$this->login();
			}
        }

		public function delete_language(){
			if($this->session->userdata(array('admin' => 'is_admin'))){
				$this->vars['languages'] = $this->config->values('locale_config');
				$this->vars['lang'] = htmlspecialchars($_POST['id']);
				
				$file = APP_PATH. DS . 'localization' . DS . $this->vars['lang'] . '.json';
				if(is_file($file)){
					if($this->vars['lang'] == $this->vars['languages']['default_localization']){
						json(array('error' => 'Your unable to delete default website language.'));
					} 
					else{
						unlink($file);
						if(isset($this->vars['languages']['localizations'][$this->vars['lang']])){
							unset($this->vars['languages']['localizations'][$this->vars['lang']]);
							$this->config->save_config_data($this->vars['languages'], 'locale_config');
						}
						json(array('success' => 'Language successfully removed.'));
					}
				}
				else{
					if(isset($this->vars['languages']['localizations'][$this->vars['lang']])){
						unset($this->vars['languages']['localizations'][$this->vars['lang']]);
						$this->config->save_config_data($this->vars['languages'], 'locale_config');
						json(array('success' => 'Language successfully removed.'));
					}
					else{
						json(array('error' => 'Unable to find language.'));
					}
				}
			} 
			else{
				json(array('error' => 'Please login first!'));
			}
        }

				
		public function disable_language($lang){
			if($this->session->userdata(array('admin' => 'is_admin'))){
				$this->vars['languages'] = $this->config->values('locale_config');
				$this->vars['lang'] = htmlspecialchars($lang);
				
				if(isset($this->vars['languages']['localizations'][$this->vars['lang']])){
					$this->vars['languages']['localizations'][$this->vars['lang']] = 0;
					$this->config->save_config_data($this->vars['languages'], 'locale_config');	
				}
				header('Location: '.$this->config->base_url . ACPURL . '/languages');
			} 
			else{
				$this->login();
			}
		}

				
		public function enable_language($lang){
			if($this->session->userdata(array('admin' => 'is_admin'))){
				$this->vars['languages'] = $this->config->values('locale_config');
				$this->vars['lang'] = htmlspecialchars($lang);
				
				if(isset($this->vars['languages']['localizations'][$this->vars['lang']])){
					$this->vars['languages']['localizations'][$this->vars['lang']] = 1;
					$this->config->save_config_data($this->vars['languages'], 'locale_config');	
				}
				header('Location: '.$this->config->base_url . ACPURL . '/languages');
			} 
			else{
				$this->login();
			}
		}

				
		public function set_default_language($lang){
			if($this->session->userdata(array('admin' => 'is_admin'))){
				$this->vars['languages'] = $this->config->values('locale_config');
				$this->vars['lang'] = htmlspecialchars($lang);
				
				if(isset($this->vars['languages']['localizations'][$this->vars['lang']])){
					$this->vars['languages']['default_localization'] = $this->vars['lang'];
					$this->config->save_config_data($this->vars['languages'], 'locale_config');	
				}
				header('Location: '.$this->config->base_url . ACPURL . '/languages');
			} 
			else{
				$this->login();
			}
		}     

		public function item_category_generator(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                json(['category' => $this->webshop->load_cat_list(true)]);
            } 
			else{
                json(['error' => 'Please login first.']);
            }
        }

		        
        public function warehouse_editor(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                $this->vars['show_vault'] = false;
                if(count($_POST) > 0){
                    $acc = isset($_POST['account']) ? htmlspecialchars($_POST['account']) : '';
                    $server = isset($_POST['server']) ? htmlspecialchars($_POST['server']) : '';
                    $this->load->model('account');
                    if($acc == '')
                        $this->vars['error'] = 'Please enter username'; 
                    else{
                        if(!$this->Madmin->acc_exists($acc, $server))
                            $this->vars['error'] = 'Account not found'; 
                        else{
                            if(!$this->Madmin->check_status($acc, false, $server))
                                $this->vars['error'] = 'Account is online'; 
                            else{
                                if($server == '')
                                    $this->vars['error'] = 'Please select server'; 
                                else{
                                    $this->load->model('warehouse');
                                    $this->load->lib('iteminfo');
                                    $this->load->lib("itemimage");
                                    $this->load->helper('webshop');
                                    if(!$this->Madmin->get_vault_content($acc, $server)){
                                        $this->Madmin->create_vault($acc, $server);
                                    }
                                    $_SESSION['vault_user'] = $acc;
                                    $_SESSION['vault_server'] = $server;
                                    $this->vars['show_vault'] = true;
                                    $this->vars['items'] = $this->Madmin->load_items($server);
                                    $this->vars['total_items'] = $this->Madmin->total_items;
                                }
                            }
                        }
                    }
                }
                $this->load->view('admincp' . DS . 'warehouse_editor' . DS . 'view.index', $this->vars);
                $this->load_footer();
            } else{
                json(['error' => 'Please login first!']);
            }
        }

		public function del_item(){
            if(is_ajax()){
                if($this->session->userdata(['admin' => 'is_admin'])){
                    $slot = (isset($_POST['slot']) ? ctype_digit($_POST['slot']) ? $_POST['slot'] : '' : '');
                    $acc = isset($_SESSION['vault_user']) ? htmlspecialchars($_SESSION['vault_user']) : '';
                    $server = isset($_SESSION['vault_server']) ? htmlspecialchars($_SESSION['vault_server']) : '';
                    $this->load->model('account');
                    if($slot == '')
                        json(['error' => 'Invalid item_slot']); 
                    else{
                        if($acc == '')
                            json(['error' => 'Invalid account.']); 
                        else{
                            if($server == '')
                                json(['error' => 'Invalid server.']); 
                            else{
                                if(!$this->Madmin->check_status($acc, false, $server))
                                    json(['error' => 'Account is online']); 
                                else{
                                    if($this->Madmin->get_vault_content($acc, $server)){
                                        if($this->Madmin->find_item_by_slot($slot, $server)){
                                            $this->Madmin->log_deleted_item($acc, $server, 1);
                                            $this->Madmin->generate_new_item_by_slot($slot, $server);
                                            $this->Madmin->update_warehouse($acc, $server);
                                            json(['success' => 'Item successfully removed from warehouse.']);
                                        } else{
                                            json(['error' => 'Item not found.']);
                                        }
                                    } else{
                                        json(['error' => 'Please create warehouse first.']);
                                    }
                                }
                            }
                        }
                    }
                } else{
                    json(['error' => 'Please login first.']);
                }
            }
        }

		public function load_items(){
            if(is_ajax()){
                if($this->session->userdata(['admin' => 'is_admin'])){
                    $cat = isset($_POST['cat']) ? $_POST['cat'] : '';
                    if($cat == '')
                        json(['error' => 'Category not found.']); else{
                        json(['items' => $this->Madmin->load_items_for_select($cat)]);
                    }
                } else{
                    json(['error' => 'Please login first.']);
                }
            }
        }

        public function check_item(){
            if(is_ajax()){
                if($this->session->userdata(['admin' => 'is_admin'])){
                    $id = isset($_POST['id']) ? $_POST['id'] : '';
                    if($id == '')
                        json(['error' => 'Item not found.']); 
					else{
                        $this->vars['item_info'] = $this->Madmin->load_items_data($id);
                        $this->vars['socket_list'] = $this->Madmin->socket_list($this->vars['item_info']['use_sockets'], $this->config->config_entry('shop_' . $_SESSION['vault_server'] . '|check_socket_part_type'), $this->vars['item_info']['exetype']);
                        json(['sockets' => $this->vars['socket_list']]);
                    }
                } else{
                    json(['error' => 'Please login first.']);
                }
            }
        }

        public function loadharmonylist(){
            if(is_ajax()){
                if($this->session->userdata(['admin' => 'is_admin'])){
                    $cat = isset($_POST['cat']) ? (int)$_POST['cat'] : '';
                    $hopt = isset($_POST['hopt']) ? (int)$_POST['hopt'] : '';
                    if($cat === '')
                        json(['error' => 'Invalid category']); else if($hopt === '')
                        json(['error' => 'Invalid harmony option']);
                    else json(['harmonylist' => $this->Madmin->load_harmony_values($cat, $hopt)]);
                } else{
                    json(['error' => 'Please login first.']);
                }
            }
        }

		public function add_wh_item(){
            if(is_ajax()){
                if($this->session->userdata(['admin' => 'is_admin'])){
                    $this->load->lib('iteminfo');
                    $this->load->lib('createitem', [MU_VERSION, SOCKET_LIBRARY, $this->website->get_value_from_server($_SESSION['vault_server'], 'item_size')]);	
                    $this->load->lib('itemimage');
                    $this->load->model('shop');
                    $this->load->model('account');
                    $errors = [];
                    $this->item_info = $this->Madmin->get_item_info($_POST['items_wh'], $_SESSION['vault_server']);
                    $this->level = isset($_POST['items_lvl']) ? ctype_digit($_POST['items_lvl']) ? (int)$_POST['items_lvl'] : 0 : 0;
                    $this->option = isset($_POST['items_opt']) ? ctype_digit($_POST['items_opt']) ? (int)$_POST['items_opt'] : 0 : 0;
                    $this->luck = (isset($_POST['items_luck']) && $_POST['items_luck'] == 1) ? true : false;
                    $this->skill = (isset($_POST['items_skill']) && $_POST['items_skill'] == 1) ? true : false;
                    $this->ancient = (isset($_POST['items_anc']) && $_POST['items_anc'] > 0) ? ctype_digit($_POST['items_anc']) ? (int)$_POST['items_anc'] : 0 : 0;
                    $this->exe = (!empty($_POST['exe']) && count($_POST['exe']) > 0) ? $_POST['exe'] : [];
                    $this->harmony = ((isset($_POST['items_harm']) && $_POST['items_harm'] != '') && (isset($_POST['harmonyvalue']) && $_POST['harmonyvalue'] != '')) ? [$_POST['items_harm'], $_POST['harmonyvalue']] : [];
                    $this->ref = (isset($_POST['items_ref']) && $_POST['items_ref'] == 1) ? true : false;
                    $this->fenrir = (isset($_POST['fenrir']) && $_POST['fenrir'] != 0) ? ctype_digit($_POST['fenrir']) ? (int)$_POST['fenrir'] : 0 : 0;
                    $this->sockets[0] = (isset($_POST['socket1']) && $_POST['socket1'] != 'no') ? preg_match('/\d{1,3}-\d{1,3}/', $_POST['socket1']) ? explode('-', $_POST['socket1']) : '' : '';
                    $this->sockets[1] = (isset($_POST['socket2']) && $_POST['socket2'] != 'no') ? preg_match('/\d{1,3}-\d{1,3}/', $_POST['socket2']) ? explode('-', $_POST['socket2']) : '' : '';
                    $this->sockets[2] = (isset($_POST['socket3']) && $_POST['socket3'] != 'no') ? preg_match('/\d{1,3}-\d{1,3}/', $_POST['socket3']) ? explode('-', $_POST['socket3']) : '' : '';
                    $this->sockets[3] = (isset($_POST['socket4']) && $_POST['socket4'] != 'no') ? preg_match('/\d{1,3}-\d{1,3}/', $_POST['socket4']) ? explode('-', $_POST['socket4']) : '' : '';
                    $this->sockets[4] = (isset($_POST['socket5']) && $_POST['socket5'] != 'no') ? preg_match('/\d{1,3}-\d{1,3}/', $_POST['socket5']) ? explode('-', $_POST['socket5']) : '' : '';
                    foreach($this->sockets as $key => $value){
                        if($value !== ''){
                            $this->sockets[$key] = $this->sockets[$key][1];
                        }
                    }
                    if(!$this->item_info)
                        json(['error' => 'Invalid item.']); 
                    else{
                        if(!empty($this->harmony)){
                            if($this->Mshop->check_harmony($this->item_info['use_harmony'], $this->harmony) == false)
                                $errors[] = 'Invalid harmony value selected.';
                        }
                        if($this->level > 15)
                            $errors[] = 'Max item level allowed 15';
                        if($this->option > 7){
                            if($this->item_info['original_item_cat'] == 13)
                                $max_opt = '7 %'; 
							else 
								$max_opt = '+ ' . (7 * (($this->item_info['original_item_cat'] == 6) ? 5 : 4));
                            $errors[] = 'Max item option allowed ' . $max_opt;
                        }
                        if(count($errors) > 0){
                            if(count($errors) == 1)
                                json(['error' => $errors[0]]); 
							else 
								json(['error' => $errors]);
                        } else{
                            if(!$this->Madmin->check_status($_SESSION['vault_user'], false, $_SESSION['vault_server'])){
                                json(['error' => 'Account is online']);
                            } else{
                                $this->generate_item();
                                if($vault = $this->Madmin->get_vault_content($_SESSION['vault_user'], $_SESSION['vault_server'])){
                                    $space = $this->Mshop->check_space($vault['Items'], $this->item_info['data']['x'], $this->item_info['data']['y'], $this->website->get_value_from_server($_SESSION['vault_server'], 'wh_multiplier'), $this->website->get_value_from_server($_SESSION['vault_server'], 'item_size'), $this->website->get_value_from_server($_SESSION['vault_server'], 'wh_hor_size'), $this->website->get_value_from_server($_SESSION['vault_server'], 'wh_ver_size'));
                                    if($space === null){
                                        json(['error' => $this->Mshop->errors[0]]);
                                    } else{
                                        $this->vars['new_items'] = $this->Mshop->generate_new_items($this->item_hex, $space, $this->website->get_value_from_server($_SESSION['vault_server'], 'wh_multiplier'), $this->website->get_value_from_server($_SESSION['vault_server'], 'item_size'), $vault['Items'], true);
                                        $this->Mshop->update_warehouse($_SESSION['vault_user'], $_SESSION['vault_server']);
                                        $hex = str_split($this->vars['new_items'], $this->website->get_value_from_server($_SESSION['vault_server'], 'item_size'));
                                        $items = [];
                                        $i = 0;
                                        $x = 0;
                                        $y = 0;
                                        foreach($hex as $it){
                                            $i++;
                                            if($it != str_pad("", $this->website->get_value_from_server($_SESSION['vault_server'], 'item_size'), "F")){
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
                                                $items[$i]['hex'] = $this->iteminfo->hex;
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
                                        $div = '';
                                        for($i = 1; $i <= 120; $i++){
                                            if(($space + 1) == $i){
                                                $div = '<div id="item-slot-' . $i . '" class="square" style="margin-top:' . ($items[$i]['yy'] * 32) . 'px; margin-left:' . ($items[$i]['xx'] * 32) . 'px; position:absolute; width:' . ($items[$i]['x'] * 32) . 'px; cursor:pointer; background-image: url(' . $this->config->base_url . 'assets/' . $this->config->config_entry('main|template') . '/images/wh_root_on.png); height:' . ($items[$i]['y'] * 32) . 'px;" data-info="' . $items[$i]['hex'] . '"><img width="100%" height="100%" alt="' . $items[$i]['name'] . '" src="' . $this->itemimage->load($items[$i]['item_id'], $items[$i]['item_cat'], $items[$i]['level'], 0) . '" /></div>';
                                            }
                                        }
                                        json(['success' => 'Item added to users warehouse.', 'slot' => $space + 1, 'div' => $div]);
                                    }
                                } else{
                                    json(['error' => 'Unable to open warehouse.']);
                                }
                            }
                        }
                    }
                } else{
                    json(['error' => 'Please login first.']);
                }
            }
        }

		private function generate_item(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->iteminfo->setItemData($this->item_info['item_id'], $this->item_info['original_item_cat'], $this->website->get_value_from_server($_SESSION['vault_server'], 'item_size'));
                $this->createitem->setItemData($this->iteminfo->item_data);
                $this->createitem->id($this->item_info['item_id']);
                $this->createitem->cat($this->item_info['original_item_cat']);
                $this->createitem->refinery($this->ref);
                $this->createitem->harmony($this->harmony);
                $this->createitem->serial($this->Mshop->generate_serial($_SESSION['vault_server']));
                if($this->website->get_value_from_server($_SESSION['vault_server'], 'item_size') == 64){
                    $this->createitem->serial2(true);
                }
                $this->createitem->lvl($this->level);
                if($this->item_info['stick_level'] > 0){
                    $this->createitem->stickLvl($this->item_info['stick_level']);
                }
                $this->createitem->skill($this->skill);
                $this->createitem->luck($this->luck);
                $this->createitem->opt($this->option);
                $this->createitem->exe($this->exe);
                $this->createitem->fenrir($this->fenrir);
                $this->createitem->ancient($this->ancient);
                $this->createitem->socket($this->sockets);
                
                $this->item_hex = $this->createitem->to_hex();
            } else{
                json(['error' => 'Please login first.']);
            }
        }

		public function load_accounts(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->vars['servers'] = $this->website->server_list();
                $this->vars['start'] = (isset($_POST['start']) && is_numeric($_POST['start'])) ? $_POST['start'] : 0;
                $this->vars['per_page'] = (isset($_POST['length']) && is_numeric($_POST['length'])) ? $_POST['length'] : 10;
                $this->vars['order_column'] = (isset($_POST['order'][0]['column']) && is_numeric($_POST['order'][0]['column'])) ? $_POST['order'][0]['column'] : 2;
                $this->vars['order_dir'] = (isset($_POST['order'][0]['dir'])) ? $_POST['order'][0]['dir'] : 'desc';
                if(isset($_POST['search']['value']) && $_POST['search']['value'] != ''){
                    $this->Madmin->search_condition_account(htmlspecialchars($_POST['search']['value']));
                }
                if(isset($_COOKIE['filter_joined']) && $_COOKIE['filter_joined'] != ''){
                    $this->Madmin->search_condition_date_start($_COOKIE['filter_joined']);
                }
                if(isset($_COOKIE['filter_joined_end']) && $_COOKIE['filter_joined_end'] != ''){
                    $this->Madmin->search_condition_date_end($_COOKIE['filter_joined_end']);
                }
                if(isset($_COOKIE['filter_status']) && $_COOKIE['filter_status'] != ''){
                    $this->Madmin->search_condition_status(unserialize($_COOKIE['filter_status']));
                }
                if(isset($_COOKIE['filter_country']) && $_COOKIE['filter_country'] != ''){
                    $this->Madmin->search_condition_country(unserialize($_COOKIE['filter_country']));
                }
                if(isset($_COOKIE['filter_server']) && $_COOKIE['filter_server'] != ''){
                    $_SESSION['account_server'] = $_COOKIE['filter_server'];
                } else{
                    unset($_SESSION['account_server']);
                    if(!isset($_SESSION['account_server'])){
                        $_SESSION['account_server'] = array_key_first($this->vars['servers']);
                    }
                }
                $this->vars['account_list'] = $this->Madmin->load_account_list($this->vars['start'], $this->vars['per_page'], $_SESSION['account_server'], $this->vars['order_column'], $this->vars['order_dir']);
                $this->vars['total_records'] = $this->Madmin->count_total_accounts(false, $_SESSION['account_server']);
                $this->vars['total_filtered_records'] = $this->Madmin->count_total_accounts(true, $_SESSION['account_server']);
                if($this->vars['account_list'] != false){
                    foreach($this->vars['account_list'] AS $info){
						
					    $this->vars['activate_link'] = '';
                        if($info['activated'] == 0){
                            $this->vars['activate_link'] = '<a class="btn btn-success" href="' . $this->config->base_url . ACPURL . '/activate-account/' . $info['id'] . '/' . $info['server'] . '"><i class="icon-ok icon-white"></i> Activate</a>';
                        }
						$partner = '';
						if(defined('PARTNER_SYSTEM') && PARTNER_SYSTEM == true ){ 
							$partner = ' <a class="btn btn-inverse" href="' . $this->config->base_url . ACPURL . '/partner-editor/' . $info['memb___id'] . '/' . $info['server'] . '">
										<i class="icon-wrench icon-white"></i> 
										Edit Partner
									</a>';
						}
                        $this->vars['data'][] = [$info['memb___id'], date(DATETIME_FORMAT, strtotime($info['reg_date'])), $info['country'], $this->website->get_value_from_server($info['server'], 'title'), '<a class="btn btn-success" href="' . $this->config->base_url . ACPURL . '/edit-account/' . $info['id'] . '/' . $info['server'] . '">
							<i class="icon-edit icon-white"></i>  
							Edit                                            
						</a> ' . $this->vars['activate_link'] . '
						<a class="btn btn-primary" href="' . $this->config->base_url . ACPURL . '/ban-account/' . $info['id'] . '/' . $info['server'] . '">
							<i class="icon-remove icon-white"></i> 
							Ban
						</a>
						<a class="btn btn-danger" onclick="return App.confirmMessage(\'Are you sure to delete this account?\');" href="' . $this->config->base_url . ACPURL . '/delete-account/' . $info['id'] . '/' . $info['server'] . '">
							<i class="icon-trash icon-white"></i> 
							Delete
						</a>
						<a class="btn btn-info" href="' . $this->config->base_url . ACPURL . '/credits-editor/' . $info['memb___id'] . '">
							<i class="icon-wrench icon-white"></i> 
							Edit Credits
						</a>
						<a class="btn btn-inverse" href="' . $this->config->base_url . ACPURL . '/vip-editor/' . $info['memb___id'] . '/' . $info['server'] . '">
							<i class="icon-wrench icon-white"></i> 
							Edit Vip
						</a>' .$partner];
                    }
                } else{
                    $this->vars['data'] = [];
                }
                json(["draw" => (int)$_POST['draw'], "recordsTotal" => $this->vars['total_records'], "recordsFiltered" => $this->vars['total_filtered_records'], "data" => $this->vars['data']]);
            } else{
                json(['error' => 'Please login first.']);
            }
        }

		public function filter_account_list(){
            $time = time() + (86400 * 3);
            if($this->session->userdata(['admin' => 'is_admin'])){
                if($_POST['joined1'] != ''){
                    setcookie("filter_joined", $_POST['joined1'], $time, '/');
                } else{
                    setcookie("filter_joined", '', time() - 3600, '/');
                }
                if($_POST['joined2'] != ''){
                    setcookie("filter_joined_end", $_POST['joined2'], $time, '/');
                } else{
                    setcookie("filter_joined_end", '', time() - 3600, '/');
                }
                if(isset($_POST['status'])){
                    setcookie("filter_status", serialize($_POST['status']), $time, '/');
                } else{
                    setcookie("filter_status", '', time() - 3600, '/');
                }
                if(isset($_POST['country'])){
                    setcookie("filter_country", serialize($_POST['country']), $time, '/');
                } else{
                    setcookie("filter_country", '', time() - 3600, '/');
                }
                if($_POST['server'] != ''){
                    setcookie("filter_server", $_POST['server'], $time, '/');
                } else{
                    setcookie("filter_server", '', time() - 3600, '/');
                }
                json(['success' => 'filters added']);
            } else{
                json(['error' => 'Please login first.']);
            }
        }

		public function filter_account_reset(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                setcookie("filter_joined", '', time() - 3600, '/');
                setcookie("filter_joined_end", '', time() - 3600, '/');
                setcookie("filter_status", '', time() - 3600, '/');
                setcookie("filter_country", '', time() - 3600, '/');
                setcookie("filter_server", '', time() - 3600, '/');
                json(['success' => 'filters reset']);
            } else{
                json(['error' => 'Please login first.']);
            }
        }

        public function account_manager($page = 1, $server = ''){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                $this->vars['servers'] = $this->website->server_list();
                $this->load->view('admincp' . DS . 'server_manager' . DS . 'view.account_manager', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

				
		public function partner_editor($account = '', $server = ''){
			if($this->session->userdata(['admin' => 'is_admin'])){
				 $this->load_header();
				if($account == ''){
					 $this->vars['error'] = 'Invalid account.';
				}
				else{
					$this->vars['servers'] = $this->website->server_list();
					$this->vars['account'] = $account;
					if($server == ''){
						$this->vars['firstKey'] = array_key_first($this->vars['servers']);
					}
					else{
						$this->vars['firstKey'] = array_key_exists($server, $this->vars['servers']) ? $server : array_key_first($this->vars['servers']);
					}
					
					$this->load->model('account');
					
					if(isset($_POST['is_partner'])){
						 $partner = $_POST['is_partner'];
						 $twitch = isset($_POST['twitch']) ? $_POST['twitch'] : '';
                         $ttags = isset($_POST['twitch_tags']) ? $_POST['twitch_tags'] : '';
                         $youtube = isset($_POST['youtube']) ? $_POST['youtube'] : '';
						 $daily_coins = isset($_POST['daily_coins']) ? $_POST['daily_coins'] : '';
						 $daily_coins_type = isset($_POST['daily_coins_type']) ? $_POST['daily_coins_type'] : '';
						 $purchase_share = isset($_POST['purchase_share']) ? $_POST['purchase_share'] : '';
						 $share_url = isset($_POST['share_url']) ? $_POST['share_url'] : '';
						 if($twitch == '')
							 $this->vars['error'] = 'Please enter twitch username';
						 if($daily_coins == '' || $daily_coins <= 0)
							 $this->vars['error'] = 'Please enter daily coins limit';
						 if($purchase_share == '' || $purchase_share <= 0 || $purchase_share > 100)
							 $this->vars['error'] = 'Please enter purchase share';
						 if($share_url == '')
							 $this->vars['error'] = 'Please enter share slug';
						 
						 if(!isset($this->vars['error'])){
							 $this->Madmin->update_partner_data($account, $partner, $twitch, $ttags, $youtube, $daily_coins, $daily_coins_type, $purchase_share, $share_url, $server);
							 $this->vars['success'] = 'Partner updated successfully';
						 }
					}
					
					$this->vars['account_data'] = $this->Madmin->get_account_data_for_partner($account, $server);
					$this->vars['purchasesReffered'] = $this->Madmin->countPurchasesReffered($account, $server);
					$this->vars['totalAmount'] = $this->Madmin->totalAmountShares($account, $server);
					if($this->vars['totalAmount'] == NULL)
						$this->vars['totalAmount'] = 0;
					$this->vars['sharesAmount'] = $this->Madmin->earnedAmountShares($account, $server);
					if($this->vars['sharesAmount'] == NULL)
						$this->vars['sharesAmount'] = 0;
					$this->vars['accountsReffered'] = $this->Madmin->accountsReferred($account, $server);
					$this->vars['streamLogs'] = $this->Madmin->findStreamLog($account);
					$this->vars['server'] = $server;
				}
				$this->load->view('admincp' . DS . 'server_manager' . DS . 'view.partner_manager', $this->vars);
				$this->load_footer();
			} else{
				$this->login();
			}
		}
    
		    
		public function vip_editor($account = '', $server = ''){
			if($this->session->userdata(['admin' => 'is_admin'])){
				 $this->load_header();
				if($account == ''){
					 $this->vars['error'] = 'Invalid account.';
				}
				else{
					$this->vars['servers'] = $this->website->server_list();
					$this->vars['account'] = $account;
					if($server == ''){
						$this->vars['firstKey'] = array_key_first($this->vars['servers']);
					}
					else{
						$this->vars['firstKey'] = array_key_exists($server, $this->vars['servers']) ? $server : array_key_first($this->vars['servers']);
					}
					$this->vars['vip_config'] = $this->config->values('vip_config');
					$this->vars['vip_query_config'] = $this->config->values('vip_query_config');
					if(!$this->vars['vip_config']){
						$this->vars['error'] = 'Vip configuration not found.';
					}
					else{
						 $this->load->model('account');
						 $this->load->model('shop');
						 if(isset($_POST['vip_package'])){
							 $package = $_POST['vip_package'];
							 $time = $_POST['vip_time'];
							 if($package != 0){
								 $this->vars['vip_data'] = $this->Mshop->check_vip($package, $this->vars['firstKey']);
								  $this->vars['existing_vip'] =  $this->Mshop->check_existing_vip_package($account, $this->vars['firstKey']); 
								  if($this->vars['existing_vip'] != NULL){
									  $this->Mshop->update_vip_package($package, strtotime($time), $account, $this->vars['firstKey']);
									  $this->Mshop->add_server_vip(strtotime($time), $this->vars['vip_data']['server_vip_package'], $this->vars['vip_data']['connect_member_load'], $this->vars['vip_query_config'], $account, $this->vars['firstKey']);
									  $this->Maccount->add_account_log('Admin updated vip ' . $this->vars['vip_data']['package_title'] . ' package until '.$time.'', 0, $account, $this->vars['firstKey']);                
									  $this->vars['success'] = 'Vip updated.';
								  }
								  else{
									 $this->Mshop->insert_vip_package($package, strtotime($time), $account, $this->vars['firstKey']);
									 $this->Mshop->add_server_vip(strtotime($time), $this->vars['vip_data']['server_vip_package'], $this->vars['vip_data']['connect_member_load'], $this->vars['vip_query_config'], $account, $this->vars['firstKey']);
									 $this->Maccount->add_account_log('Admin added vip ' . $this->vars['vip_data']['package_title'] . ' package until '.$time.'', 0, $account, $this->vars['firstKey']);        
									 $this->vars['success'] = 'Vip added.';
								  }
							 }
							 else{
								 $this->Mshop->remove_vip_package($account, $this->vars['firstKey']);
								 $this->Mshop->add_server_vip((time - (86400*7)), $this->vars['vip_data']['server_vip_package'], $this->vars['vip_data']['connect_member_load'], $this->vars['vip_query_config'], $account, $this->vars['firstKey']);
								 $this->Maccount->add_account_log('Admin removed vip', 0, $account, $this->vars['firstKey']);                
							    $this->vars['success'] = 'Vip updated.';
							 }
						 }
						 
						 $this->vars['vip_packages'] = $this->Mshop->load_vip_packages($this->vars['firstKey']);
						 $this->vars['existing_vip'] =  $this->Mshop->check_existing_vip_package($account, $this->vars['firstKey']); 
					}
				}
				$this->load->view('admincp' . DS . 'server_manager' . DS . 'view.vip_manager', $this->vars);
                $this->load_footer();
			} else{
                $this->login();
            }
		}

		public function character_manager($page = 1, $server = ''){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                $this->vars['current_page'] = $this->config->base_url . ACPURL . '/character-manager/1';
                if(isset($_POST['character'])){
                    $char = isset($_POST['character']) ? $_POST['character'] : '';
                    $sserver = isset($_POST['server']) ? $_POST['server'] : '';
                    if($char == ''){
                        $this->vars['error'] = 'Please enter character name';
                    }
                    if(!isset($this->vars['error'])){
                        $this->vars['char_list'] = $this->Madmin->search_char_list($char, $sserver);
                    } else{
                        $this->vars['char_list'] = [];
                    }
                    $this->vars['serv'] = ($sserver == '') ? '' : $sserver;
                } else{
                    $this->vars['servers'] = $this->website->server_list();
                    if($server == ''){
                        $server_for_db = array_key_first($this->vars['servers']);
                    } else{
                        $server_for_db = $server;
                    }
                    $this->vars['serv'] = $server;
                    $this->vars['char_list'] = $this->Madmin->load_char_list($page, 25, $server_for_db);
                    $this->pagination->initialize($page, 25, $this->Madmin->count_total_chars($server_for_db), $this->config->base_url . ACPURL . '/character-manager/%s/' . $server . '');
                    $this->vars['pagination'] = $this->pagination->create_links();
                }
                $this->load->view('admincp' . DS . 'server_manager' . DS . 'view.character_manager', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

		public function activate_account($id, $server){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                $this->vars['servers'] = $this->website->server_list();
                if($server == ''){
                    $server_for_db = array_key_first($this->vars['servers']);
                } else{
                    $server_for_db = $server;
                }
                $this->vars['account_data'] = $this->Madmin->get_account_data($id, $server_for_db);
                if($this->vars['account_data'] != false){
                    if($this->vars['account_data']['activated'] == 1){
                        $this->vars['error'] = 'Account is already activated';
                    } else{
                        $this->Madmin->activate_account($id, $server_for_db);
                        $this->vars['success'] = 'Account activated successfully';
                    }
                }
                $this->load->view('admincp' . DS . 'server_manager' . DS . 'view.activate_account', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function edit_account($id = -1, $server = ''){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                $this->vars['servers'] = $this->website->server_list();
                if($server == ''){
                    $server_for_db = array_key_first($this->vars['servers']);
                } else{
                    $server_for_db = $server;
                }
                if(isset($_POST['password'])){
                    $pass = isset($_POST['password']) ? $_POST['password'] : '';
                    $email = isset($_POST['email']) ? $_POST['email'] : '';
                    $sno_numb = isset($_POST['sno_numb']) ? trim($_POST['sno_numb']) : '';
                    if($pass == '' && MD5 == 0){
                        $this->vars['error'] = 'Password can not be empty';
                    } else{
                        if($email == ''){
                            $this->vars['error'] = 'Email can not be empty';
                        } else{
                            if($sno_numb == '' || !is_numeric($sno_numb)){
                                $this->vars['error'] = 'Personal ID can not be empty';
                            } else{
                                if($this->Madmin->update_account_info($id, $pass, $email, $sno_numb, $server_for_db)){
                                    $this->vars['success'] = 'Account info updated.';
                                } else{
                                    $this->vars['error'] = 'Unable to update account information.';
                                }
                            }
                        }
                    }
                }
                $this->vars['account_data'] = $this->Madmin->get_account_data($id, $server_for_db);
                if($this->vars['account_data'] != false){
                    $this->vars['ip_logs'] = $this->Madmin->get_ip_logs($this->vars['account_data']['memb___id']);
                    $this->vars['char_list'] = $this->Madmin->get_char_list($this->vars['account_data']['memb___id'], -1, $server_for_db);
                }
                $this->load->view('admincp' . DS . 'server_manager' . DS . 'view.edit_account', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

		public function edit_character($id = -1, $server = ''){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                $this->vars['servers'] = $this->website->server_list();
                if($server == ''){
                    $this->vars['server'] = array_key_first($this->vars['servers']);
                } else{
                    $this->vars['server'] = $server;
                }
                if(isset($_POST['edit_character'])){
                    foreach($_POST as $key => $value){
                        $this->Madmin->$key = $value;
                    }
                    if(!isset($this->Madmin->vars['cLevel']) || !preg_match('/^\d+$/', $this->Madmin->vars['cLevel']))
                        $this->vars['error'] = 'Character level can only be numbers';
                    if(!isset($this->Madmin->vars['LevelUpPoint']) || !preg_match('/^\d+$/', $this->Madmin->vars['LevelUpPoint']))
                        $this->vars['error'] = 'Character levelup point can only be numbers';
                    if(!isset($this->Madmin->vars['Experience']) || !preg_match('/^-?\d+$/', $this->Madmin->vars['Experience']))
                        $this->vars['error'] = 'Character experience can only be numbers';
                    if(!isset($this->Madmin->vars['Strength']) || !preg_match('/^\d+$/', $this->Madmin->vars['Strength']))
                        $this->vars['error'] = 'Character strength can only be numbers';
                    if(!isset($this->Madmin->vars['Dexterity']) || !preg_match('/^\d+$/', $this->Madmin->vars['Dexterity']))
                        $this->vars['error'] = 'Character agility can only be numbers';
                    if(!isset($this->Madmin->vars['Energy']) || !preg_match('/^\d+$/', $this->Madmin->vars['Energy']))
                        $this->vars['error'] = 'Character energy can only be numbers';
                    if(!isset($this->Madmin->vars['Vitality']) || !preg_match('/^\d+$/', $this->Madmin->vars['Vitality']))
                        $this->vars['error'] = 'Character vitality can only be numbers';
                    if(!isset($this->Madmin->vars['Money']) || !preg_match('/^\d+$/', $this->Madmin->vars['Money']))
                        $this->vars['error'] = 'Character money can only be numbers';
                    if(!isset($this->Madmin->vars['MapPosX']) || !preg_match('/^\d+$/', $this->Madmin->vars['MapPosX']))
                        $this->vars['error'] = 'Character map pos x can only be numbers';
                    if(!isset($this->Madmin->vars['MapPosY']) || !preg_match('/^\d+$/', $this->Madmin->vars['MapPosY']))
                        $this->vars['error'] = 'Character map pos y can only be numbers';
                    if(!isset($this->Madmin->vars['PkCount']) || !preg_match('/^\d+$/', $this->Madmin->vars['PkCount']))
                        $this->vars['error'] = 'Character pk count can only be numbers';
                    if(!isset($this->Madmin->vars['PkTime']) || !preg_match('/^\d+$/', $this->Madmin->vars['PkTime']))
                        $this->vars['error'] = 'Character pk time can only be numbers';
                    if(!isset($this->Madmin->vars['resets']) || !preg_match('/^\d+$/', trim($this->Madmin->vars['resets'])))
                        $this->vars['error'] = 'Character resets can only be numbers';
                    if(!isset($this->Madmin->vars['grand_resets']) || !preg_match('/^\d+$/', trim($this->Madmin->vars['grand_resets'])))
                        $this->vars['error'] = 'Character grand resets can only be numbers';
                    if(!isset($this->vars['error'])){
                        if($this->Madmin->update_character($id, $this->vars['server'])){
                            $this->vars['success'] = 'Character successfully updated.';
                        }
                    }
                }
                $this->vars['character_data'] = $this->Madmin->get_character_data($id, $this->vars['server']);
                if($this->vars['character_data'] != false){
                    $this->vars['ip_logs'] = $this->Madmin->get_ip_logs($this->vars['character_data']['AccountId']);
                    $this->vars['char_list'] = $this->Madmin->get_char_list($this->vars['character_data']['AccountId'], $id, $this->vars['server']);
                }
                $this->load->view('admincp' . DS . 'server_manager' . DS . 'view.edit_character', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function ban_account($id, $server){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                $this->vars['servers'] = $this->website->server_list();
                if($server == ''){
                    $server_for_db = array_key_first($this->vars['servers']);
                } else{
                    $server_for_db = $server;
                }
                if($acc = $this->Madmin->check_account($id, $server_for_db)){
                    $this->vars['name'] = $acc['memb___id'];
                    if(isset($_POST['ban'])){
                        foreach($_POST as $key => $value){
                            $this->Madmin->$key = trim($value);
                        }
                        if(strtotime($this->Madmin->vars['time']) < time() && !isset($this->Madmin->vars['permanent_ban'])){
                            $this->vars['error'] = 'Wrong ban time.';
                        } else{
                            if($acc['bloc_code'] != 1){
                                $this->Madmin->ban_account($server_for_db);
                                $this->Madmin->add_to_banlist(1, $server_for_db);
                                $this->vars['success'] = 'Account successfully banned.';
                            } else{
                                $this->vars['error'] = 'Account already banned.';
                            }
                        }
                    }
                    $this->vars['server'] = $server_for_db;
                    $this->vars['ban_list'] = $this->Madmin->load_ban_list(1);
                } else{
                    $this->vars['not_allowed'] = 'Account not found.';
                }
                $this->load->view('admincp' . DS . 'server_manager' . DS . 'view.ban_account', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function ban_character($id, $server){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                $this->vars['servers'] = $this->website->server_list();
                if($server == ''){
                    $server_for_db = array_key_first($this->vars['servers']);
                } else{
                    $server_for_db = $server;
                }
                if($char = $this->Madmin->check_char($id, $server_for_db)){
                    $this->vars['name'] = $char['Name'];
                    if(isset($_POST['ban'])){
                        foreach($_POST as $key => $value){
                            $this->Madmin->$key = trim($value);
                        }
                        if(strtotime($this->Madmin->vars['time']) < time() && !isset($this->Madmin->vars['permanent_ban'])){
                            $this->vars['error'] = 'Wrong ban time.';
                        } else{
                            if($char['CtlCode'] != 1){
                                $this->Madmin->ban_char($server_for_db);
                                $this->Madmin->add_to_banlist(2, $server_for_db);
                                $this->vars['success'] = 'Character successfully banned.';
                            } else{
                                $this->vars['error'] = 'Character already banned.';
                            }
                        }
                    }
                    $this->vars['server'] = $server_for_db;
                    $this->vars['ban_list'] = $this->Madmin->load_ban_list(2);
                } else{
                    $this->vars['not_allowed'] = 'Character not found.';
                }
                $this->load->view('admincp' . DS . 'server_manager' . DS . 'view.ban_character', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function unban($type = 'account', $name = '', $server = ''){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                $this->vars['servers'] = $this->website->server_list();
                if($server == ''){
                    $server_for_db = array_key_first($this->vars['servers']);;
                } else{
                    $server_for_db = $server;
                }
                switch($type){
                    default:
                    case 'account':
                        if($this->Madmin->check_banned_account($server_for_db)){
                            $this->Madmin->unban($name, 1, $server_for_db);
                            $this->vars['success'] = 'Account successfully unbanned.';
                        } else{
                            $this->vars['error'] = 'Account is not banned or does not exist.';
                        }
                        break;
                    case 'character':
                        if($this->Madmin->check_banned_char($name, $server_for_db)){
                            $this->Madmin->unban($name, 2, $server_for_db);
                            $this->vars['success'] = 'Character successfully unbanned.';
                        } else{
                            $this->vars['error'] = 'Character is not banned or does not exist.';
                        }
                        break;
                }
                $this->load->view('admincp' . DS . 'server_manager' . DS . 'view.unban', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function delete_account($id, $server = ''){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                $this->vars['servers'] = $this->website->server_list();
                if($server == ''){
                    $server_for_db = array_key_first($this->vars['servers']);
                } else{
                    $server_for_db = $server;
                }
                if($acc = $this->Madmin->check_account($id, $server_for_db)){
                    $this->Madmin->delete_account($acc['memb___id'], $server_for_db);
                    $char_list = $this->Madmin->get_character_list($acc['memb___id'], $server_for_db);
                    $this->Madmin->delete_account_character($acc['memb___id'], $server_for_db);
                    if(!empty($char_list)){
                        $this->Madmin->delete_characters($acc['memb___id'], $char_list, $server_for_db);
                    }
                    $this->Madmin->delete_account_log($acc['memb___id'], $server_for_db);
                    $this->Madmin->delete_account_credits($acc['memb___id'], $server_for_db);
                    $this->Madmin->delete_ban_list($acc['memb___id'], $server_for_db);
                    $this->vars['success'] = 'Account removed successfully.';
                } else{
                    $this->vars['error'] = 'Account not found.';
                }
                $this->load->view('admincp' . DS . 'server_manager' . DS . 'view.delete_account', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function search_ip($ip = ''){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                if(isset($_POST['ip'])){
                    if(!filter_var($_POST['ip'], FILTER_VALIDATE_IP)){
                        $this->vars['error'] = 'Invalid ip address specified.';
                    } else{
                        $this->vars['ip_log'] = $this->Madmin->get_account_by_ip($_POST['ip']);
                    }
                } else{
                    if(filter_var($ip, FILTER_VALIDATE_IP)){
                        $this->vars['ip_log'] = $this->Madmin->get_account_by_ip($ip);
                    }
                }
                $this->load->view('admincp' . DS . 'server_manager' . DS . 'view.search_ip', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function support_departments(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                $this->vars['department_list'] = $this->Madmin->load_department_list();
                $this->load->view('admincp' . DS . 'support' . DS . 'view.departments', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function add_support_department(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                if(isset($_POST['add_department'])){
                    $title = isset($_POST['title']) ? $_POST['title'] : '';
                    $server = isset($_POST['server']) ? $_POST['server'] : '';
                    $pay = isset($_POST['pay_per_incident']) ? $_POST['pay_per_incident'] : '';
                    $ptype = isset($_POST['payment_type']) ? $_POST['payment_type'] : '';
                    $status = isset($_POST['status']) ? $_POST['status'] : 1;
                    if($title == ''){
                        $this->vars['error'] = 'Department title can not be empty';
                    } else{
                        if($server == ''){
                            $this->vars['error'] = 'Please select server';
                        } else{
                            if($this->Madmin->check_existing_department($title, $server) != false)
                                $this->vars['error'] = 'Department with this name already exists'; else{
                                if($this->Madmin->add_department($title, $server, $pay, $ptype, $status)){
                                    $this->vars['success'] = 'Support department added successfully';
                                }
                            }
                        }
                    }
                }
                $this->load->view('admincp' . DS . 'support' . DS . 'view.add_departments', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function edit_support_department($id){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                if(isset($_POST['edit_department'])){
                    $title = isset($_POST['title']) ? $_POST['title'] : '';
                    $server = isset($_POST['server']) ? $_POST['server'] : '';
                    $pay = isset($_POST['pay_per_incident']) ? $_POST['pay_per_incident'] : '';
                    $ptype = isset($_POST['payment_type']) ? $_POST['payment_type'] : '';
                    $status = isset($_POST['status']) ? $_POST['status'] : 1;
                    if($title == ''){
                        $this->vars['error'] = 'Department title can not be empty';
                    } else{
                        if($server == ''){
                            $this->vars['error'] = 'Please select server';
                        } else{
                            if($this->Madmin->check_existing_department($title, $server, $id) != false)
                                $this->vars['error'] = 'Department with this name already exists'; else{
                                if($this->Madmin->edit_department($title, $server, $pay, $ptype, $status, $id)){
                                    $this->vars['success'] = 'Support department edited successfully';
                                }
                            }
                        }
                    }
                }
                if(!$this->vars['data'] = $this->Madmin->check_department($id)){
                    $this->vars['not_found'] = 'Department not found';
                }
                $this->load->view('admincp' . DS . 'support' . DS . 'view.edit_departments', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function delete_support_department($id){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                if($this->Madmin->check_department($id) != false){
                    $this->Madmin->delete_department($id);
                    $this->vars['success'] = 'Support department deleted successfully';
                } else{
                    $this->vars['error'] = 'Department not found';
                }
                $this->load->view('admincp' . DS . 'support' . DS . 'view.info', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

		public function support_requests($page = 1){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                if(isset($_POST['filter_tickets'])){
                    $department = isset($_POST['department']) ? $_POST['department'] : '';
                    $priorities = isset($_POST['priority']) ? $_POST['priority'] : '';
                    $status = isset($_POST['status']) ? $_POST['status'] : '';
                    $order1 = isset($_POST['order']) ? $_POST['order'] : '';
                    $order2 = isset($_POST['order2']) ? $_POST['order2'] : '';
                    $order_merge = [$order1, $order2];
                    if($department != ''){
                        $this->Madmin->serialize_departments($department);
                    } else{
                        $this->Madmin->unset_departments();
                    }
                    if($priorities != ''){
                        $this->Madmin->serialize_priorities($priorities);
                    } else{
                        $this->Madmin->unset_priorities();
                    }
                    if($status != ''){
                        $this->Madmin->serialize_status($status);
                    } else{
                        $this->Madmin->unset_status();
                    }
                    if($order1 != '' && $order2 != ''){
                        $this->Madmin->serialize_order($order_merge);
                    } else{
                        $this->Madmin->unset_order();
                    }
                }
                if(isset($_POST['set_status'])){
                    if(isset($_POST['id'])){
                        if(count($_POST['id']) > 0){
                            if($_POST['set_status'] == 3){
                                foreach($_POST['id'] as $key => $val){
                                    $this->Madmin->set_replied_by_admin_and_user($key);
                                }
                            }
                            $this->Madmin->change_ticket_status($_POST['id'], $_POST['set_status']);
                        }
                    }
                }
                $this->vars['department_list'] = $this->Madmin->load_department_list();
                $this->vars['filter'] = $this->Madmin->load_support_filter();
                $this->vars['tickets'] = $this->Madmin->load_support_requests($page, 25, $this->vars['filter']['filter_department'], $this->vars['filter']['filter_priority'], $this->vars['filter']['filter_status'], $this->vars['filter']['sort_by']);
                $this->vars['ticket_count'] = $this->Madmin->count_total_tickets($this->vars['filter']['filter_department'], $this->vars['filter']['filter_priority'], $this->vars['filter']['filter_status']);
                $this->pagination->initialize($page, 25, $this->vars['ticket_count'], $this->config->base_url . ACPURL . '/support-requests/%s');
                $this->vars['pagination'] = $this->pagination->create_links();
                $this->vars['status'] = [0 => 'Open', 1 => 'Closed', 2 => 'Hold', 3 => 'Resolved', 4 => 'Spam', 5 => 'Working'];
                $this->load->view('admincp' . DS . 'support' . DS . 'view.support_requests', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function view_request($id){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                if($this->vars['ticket_data'] = $this->Madmin->check_ticket($id)){
                    if(isset($_POST['submit_reply'])){
                        $text = isset($_POST['reply']) ? $_POST['reply'] : '';
                        if($text == ''){
                            $this->vars['reply_error'] = 'Please enter reply text.';
                        } else{
                            if($this->Madmin->add_reply($id, $text)){
                                if($this->Madmin->set_replied_by_admin($id)){
                                    if($this->vars['ticket_data']['status'] == 3){
                                        $this->Madmin->change_ticket_status([$id => 'on'], 0);
                                    }
                                    $this->Madmin->log_reply_time($id);
									if($this->config->values('email_config', 'support_email_user') == 1){
										$user = $this->Madmin->get_account_data_by_username($this->vars['ticket_data']['creator_account'], $this->vars['ticket_data']['server']);
										$this->Madmin->sent_ticket_reply_email_user($this->vars['ticket_data']['creator_account'], $this->vars['ticket_data']['server'], $user['mail_addr'], $this->vars['ticket_data']['subject'], $id);
									}
                                    $this->vars['reply_success'] = 'You have successfully replied to ticket.';
                                }
                            }
                        }
                    }
                    $this->vars['ticket_replies'] = $this->Madmin->load_ticket_replies($id);
                    $this->vars['last_reply'] = $this->Madmin->get_last_reply_time($this->vars['ticket_data']['id']);
                    if($this->vars['last_reply'] != false){
                        $this->vars['time_elapsed'] = $this->website->date_diff($this->vars['ticket_data']['create_time'], $this->vars['last_reply']['reply_time']);
                    } else{
                        $this->vars['time_elapsed'] = 'None';
                    }
                    $this->vars['department_list'] = $this->Madmin->load_department_list();
                    $this->vars['status'] = [0 => 'Open', 1 => 'Closed', 2 => 'Hold', 3 => 'Resolved', 4 => 'Spam', 5 => 'Working'];
                } else{
                    $this->vars['not_found'] = 'Ticket not found.';
                }
                $this->load->view('admincp' . DS . 'support' . DS . 'view.read_request', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function change_department(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                if($this->Madmin->check_ticket($_POST['id'])){
                    $this->Madmin->change_department($_POST['id'], $_POST['department']);
                    json(['success' => 'Department changed.']);
                } else{
                    json(['error' => 'Ticket not found.']);
                }
            } else{
                json(['error' => 'Please login first.']);
            }
        }

        public function change_status(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                if($this->Madmin->check_ticket($_POST['id'])){
                    $this->Madmin->change_status($_POST['id'], $_POST['status']);
                    if($_POST['status'] == 3){
                        $this->Madmin->set_replied_by_admin_and_user($_POST['id']);
                    }
                    json(['success' => 'Status changed.']);
                } else{
                    json(['error' => 'Ticket not found.']);
                }
            } else{
                json(['error' => 'Please login first.']);
            }
        }

        public function edit_buylevel_settings($key, $server){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                $this->vars['servers'] = $this->website->server_list();
                $this->vars['buylevel_config'] = $this->config->values('buylevel_config', [$server, 'levels']);
                if(!$this->vars['buylevel_config']){
                    $this->vars['not_found'] = 'Level configuration for this server not found.';
                } else{
                    if(!array_key_exists($key, $this->vars['buylevel_config'])){
                        $this->vars['not_found'] = 'Level configuration for this server not found.';
                    } else{
                        if(isset($_POST['edit_settings'])){
                            foreach($_POST as $keys => $value){
                                $this->Madmin->$keys = $value;
                            }
                            if(!isset($this->Madmin->vars['price']) || !preg_match('/^\d+$/', $this->Madmin->vars['price']))
                                $this->vars['error'][] = 'Please enter valid price';
                            if(!isset($this->Madmin->vars['payment_type']))
                                $this->vars['error'][] = 'Please select payment type';
                            if(!isset($this->vars['error']) || count($this->vars['error']) <= 0){
                                $this->vars['all_config'] = $this->config->values('buylevel_config');
                                $this->vars['all_config'][$server]['levels'][$key] = ["price" => $this->Madmin->vars['price'], "payment_type" => $this->Madmin->vars['payment_type']];
                                $this->Madmin->save_config_data($this->vars['all_config'], 'buylevel_config', false);
                                header('Location: ' . $this->config->base_url . ACPURL . '/manage-settings/buylevel');
                            }
                        }
                        $this->vars['b_config'] = $this->vars['buylevel_config'][$key];
                    }
                }
                $this->load->view('admincp' . DS . 'website_settings' . DS . 'view.edit_buylevel_settings', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function add_buylevel_settings(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                $this->vars['servers'] = $this->website->server_list();
                if(isset($_POST['add_buylevel_settings'])){
                    foreach($_POST as $key => $value){
                        $this->Madmin->$key = $value;
                    }
                    if(!isset($this->Madmin->vars['server']) || $this->Madmin->vars['server'] == '')
                        $this->vars['error'][] = 'Please select server.';
                    if(!isset($this->Madmin->vars['level']) || !preg_match('/^\d+$/', $this->Madmin->vars['level']))
                        $this->vars['error'][] = 'Please enter valid level';
                    if(!isset($this->Madmin->vars['price']) || !preg_match('/^\d+$/', $this->Madmin->vars['price']))
                        $this->vars['error'][] = 'Please enter valid price';
                    if(!isset($this->Madmin->vars['payment_type']))
                        $this->vars['error'][] = 'Please select payment type';
                    if(!isset($this->vars['error']) || count($this->vars['error']) <= 0){
                        $this->vars['buylevel_config'] = $this->config->values('buylevel_config');
                        if(array_key_exists($this->Madmin->vars['server'], $this->vars['buylevel_config'])){
                            $temp_array = $this->vars['buylevel_config'];
                            unset($temp_array[$this->Madmin->vars['server']]['active']);
                            foreach($temp_array[$this->Madmin->vars['server']] AS $key => $value){
                                if(array_key_exists($this->Madmin->vars['level'], $value)){
                                    $this->vars['error'][] = 'Level configuration already exists';
                                }
                            }
                            if(!isset($this->vars['error']) || count($this->vars['error']) <= 0){
                                $this->vars['buylevel_config'][$this->Madmin->vars['server']]['levels'][$this->Madmin->vars['level']] = ["price" => $this->Madmin->vars['price'], "payment_type" => $this->Madmin->vars['payment_type']];
                                $this->Madmin->save_config_data($this->vars['buylevel_config'], 'buylevel_config', false);
                                $this->vars['success'] = 'Level configuration added.';
                            }
                        } else{
                            $new_config = [$this->Madmin->vars['server'] => ["active" => 1, "levels" => [$this->Madmin->vars['level'] => ["price" => $this->Madmin->vars['price'], "payment_type" => $this->Madmin->vars['payment_type']]]]];
                            $this->vars['buylevel_config'] = array_merge($this->vars['buylevel_config'], $new_config);
                            $this->Madmin->save_config_data($this->vars['buylevel_config'], 'buylevel_config', false);
                            $this->vars['success'] = 'Level configuration added.';
                        }
                    }
                }
                $this->load->view('admincp' . DS . 'website_settings' . DS . 'view.add_buylevel_settings', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function change_buylevel_status(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $server = isset($_POST['server']) ? $_POST['server'] : '';
                if($server == '')
                    json(['error' => 'Invalid server.']); else{
                    $this->vars['buylevel_config'] = $this->config->values('buylevel_config');
                    if(array_key_exists($server, $this->vars['buylevel_config'])){
                        $this->vars['buylevel_config'][$server]['active'] = (int)$_POST['status'];
                        $this->Madmin->save_config_data($this->vars['buylevel_config'], 'buylevel_config', false);
                        json(['success' => 'Configuration saved.']);
                    } else{
                        json(['error' => 'Server not found.']);
                    }
                }
            } else{
                json(['error' => 'Please login first.']);
            }
        }

        public function delete_buylevel_settings(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $key = isset($_POST['key']) ? $_POST['key'] : '';
                $server = isset($_POST['server']) ? $_POST['server'] : '';
                if($key == '')
                    json(['error' => 'Invalid level.']); else{
                    if($server == '')
                        json(['error' => 'Invalid server.']); else{
                        $this->vars['buylevel_config'] = $this->config->values('buylevel_config');
                        if(array_key_exists($server, $this->vars['buylevel_config'])){
                            if(isset($this->vars['buylevel_config'][$server]['levels'])){
                                if(array_key_exists($key, $this->vars['buylevel_config'][$server]['levels'])){
                                    unset($this->vars['buylevel_config'][$server]['levels'][$key]);
                                    $this->Madmin->save_config_data($this->vars['buylevel_config'], 'buylevel_config', false);
                                    json(['success' => 'Level configuration deleted.']);
                                } else{
                                    json(['error' => 'Level not found.']);
                                }
                            } else{
                                json(['error' => 'Level configuration is empty.']);
                            }
                        } else{
                            json(['error' => 'Server not found.']);
                        }
                    }
                }
            } else{
                json(['error' => 'Please login first.']);
            }
        }

        public function buylevel_save_max_level(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $data = (isset($_POST['max_level']) && is_numeric($_POST['max_level'])) ? $_POST['max_level'] : '';
                $server = isset($_POST['server']) ? $_POST['server'] : '';
                if($data == '')
                    json(['error' => 'Invalid level.']); else{
                    if($server == '')
                        json(['error' => 'Invalid server.']); else{
                        $this->vars['buylevel_config'] = $this->config->values('buylevel_config');
                        if(array_key_exists($server, $this->vars['buylevel_config'])){
                            $this->vars['buylevel_config'][$server]['max_level'] = $data;
                            $this->Madmin->save_config_data($this->vars['buylevel_config'], 'buylevel_config', false);
                            json(['success' => 'Max Level configuration saved.']);
                        } else{
                            json(['error' => 'Server not found.']);
                        }
                    }
                }
            } else{
                json(['error' => 'Please login first.']);
            }
        }
		
		public function add_reset_settings(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                $this->vars['servers'] = $this->website->server_list();
                if(isset($_POST['add_reset_settings'])){
                    foreach($_POST as $key => $value){
                        $this->Madmin->$key = $value;
                    }
                    if(!isset($this->Madmin->vars['server']) || $this->Madmin->vars['server'] == '')
                        $this->vars['error'][] = 'Please select server.';
                    if(!isset($this->Madmin->vars['sreset']) || !preg_match('/^\d+$/', $this->Madmin->vars['sreset']))
                        $this->vars['error'][] = 'Starting reset can only be numeric value';
                    if(!isset($this->Madmin->vars['ereset']) || !preg_match('/^\d+$/', $this->Madmin->vars['ereset']))
                        $this->vars['error'][] = 'Ending reset can only be numeric value';
                    if(!isset($this->Madmin->vars['money']) || !preg_match('/^\d+$/', $this->Madmin->vars['money']))
                        $this->vars['error'][] = 'Required zen can only be numeric value';
                    if(!isset($this->Madmin->vars['level']) || !preg_match('/^\d+$/', $this->Madmin->vars['level']))
                        $this->vars['error'][] = 'Required level can only be numeric value';
					if(!isset($this->Madmin->vars['mlevel']) || !preg_match('/^\d+$/', $this->Madmin->vars['mlevel']))
                        $this->vars['error'][] = 'Required master level can only be numeric value';
					if(!isset($this->Madmin->vars['level_after_reset']) || !preg_match('/^\d+$/', $this->Madmin->vars['level_after_reset']))
                        $this->vars['error'][] = 'Level after reset can only be numeric value';
                    if(!isset($this->Madmin->vars['reset_cooldown']) || !preg_match('/^\d+$/', $this->Madmin->vars['reset_cooldown']))
                        $this->vars['error'][] = 'Reset cooldown can only be numeric value';
                    if(!isset($this->Madmin->vars['new_stat_points']) || !preg_match('/^\d+$/', $this->Madmin->vars['new_stat_points']))
                        $this->vars['error'][] = 'New stat points can only be numeric value';
                    if(!isset($this->Madmin->vars['new_free_points']) || !preg_match('/^\d+$/', $this->Madmin->vars['new_free_points']))
                        $this->vars['error'][] = 'New levelup points can only be numeric value';
					foreach($this->website->get_char_class(0, false, true) AS $k => $class){ 
						if(!isset($this->Madmin->vars['bonus_lvl_up_'.$k.'']) || !preg_match('/^\d+$/', $this->Madmin->vars['bonus_lvl_up_'.$k.'']))
							$this->vars['error'][] = 'Bonus level up points for '.$class['short'].'('.$k.') can only be numeric value';
					}
                    if(!isset($this->Madmin->vars['bonus_credits']) || !preg_match('/^\d+$/', $this->Madmin->vars['bonus_credits']))
                        $this->vars['error'][] = 'Bonus credits value can only be numeric value';
                    if(!isset($this->Madmin->vars['bonus_gcredits']) || !preg_match('/^\d+$/', $this->Madmin->vars['bonus_gcredits']))
                        $this->vars['error'][] = 'Bonus gold credits value can only be numeric value';
					if(!isset($this->Madmin->vars['bonus_ruud']) || !preg_match('/^\d+$/', $this->Madmin->vars['bonus_ruud']))
                        $this->vars['error'][] = 'Bonus ruud value can only be numeric value';
                    if(!isset($this->vars['error']) || count($this->vars['error']) <= 0){
                        $this->vars['reset_config'] = $this->config->values('reset_config');
						$class_bonus = [];
						foreach($this->website->get_char_class(0, false, true) AS $kk => $class){ 
							$class_bonus[$kk] = $this->Madmin->vars['bonus_lvl_up_'.$kk.''];
						}
                        if(array_key_exists($this->Madmin->vars['server'], $this->vars['reset_config'])){
                            $temp_array = $this->vars['reset_config'];
                            unset($temp_array[$this->Madmin->vars['server']]['allow_reset']);
                            foreach($temp_array[$this->Madmin->vars['server']] AS $key => $value){
                                list($start, $end) = explode('-', $key);
                                if(in_array($this->Madmin->vars['sreset'], range($start, $end - 1))){
                                    $this->vars['error'][] = 'Starting reset is in another reset configuration range';
                                }
                                if(in_array($this->Madmin->vars['ereset'], range($start, $end))){
                                    $this->vars['error'][] = 'Ending reset is in another reset configuration range';
                                }
                            }
                            if(!isset($this->vars['error']) || count($this->vars['error']) <= 0){	
                                $this->vars['reset_config'][$this->Madmin->vars['server']][$this->Madmin->vars['sreset'] . '-' . $this->Madmin->vars['ereset']] = [
									"money" => $this->Madmin->vars['money'], 
									"money_x_reset" => $this->Madmin->vars['money_x_reset'], 
									"level" => $this->Madmin->vars['level'], 
									"mlevel" => $this->Madmin->vars['mlevel'], 
									"level_after_reset" => $this->Madmin->vars['level_after_reset'],
									"clear_magic" => $this->Madmin->vars['clear_magic'], 
									"clear_inventory" => $this->Madmin->vars['clear_inventory'], 
									"clear_exp_inventory" => $this->Madmin->vars['clear_exp_inventory'], 
									"clear_equipment" => $this->Madmin->vars['clear_equipment'], 
									"clear_store" => $this->Madmin->vars['clear_store'], 
									"clear_stats" => $this->Madmin->vars['clear_stats'], 
									"clear_level_up" => $this->Madmin->vars['clear_level_up'], 
									"new_stat_points" => $this->Madmin->vars['new_stat_points'], 
									"new_free_points" => $this->Madmin->vars['new_free_points'], 
									"bonus_points" => $class_bonus, 
									"bonus_credits" => $this->Madmin->vars['bonus_credits'], 
									"bonus_gcredits" => $this->Madmin->vars['bonus_gcredits'],
									"bonus_ruud" => $this->Madmin->vars['bonus_ruud'], 	
									"reset_cooldown" => $this->Madmin->vars['reset_cooldown'], 
									"bonus_gr_points" => $this->Madmin->vars['bonus_gr_points'], 
									"clear_masterlevel" => $this->Madmin->vars['clear_masterlevel']
								];
                                $this->config->save_config_data($this->vars['reset_config'], 'reset_config');
                                $this->vars['success'] = 'Reset configuration added.';
                            }
                        } else{
                            $new_config = [$this->Madmin->vars['server'] => [
								"allow_reset" => 1, 
								$this->Madmin->vars['sreset'] . '-' . $this->Madmin->vars['ereset'] => [
									"money" => $this->Madmin->vars['money'], 
									"money_x_reset" => $this->Madmin->vars['money_x_reset'], 
									"level" => $this->Madmin->vars['level'], 
									"mlevel" => $this->Madmin->vars['mlevel'], 
									"level_after_reset" => $this->Madmin->vars['level_after_reset'],
									"clear_magic" => $this->Madmin->vars['clear_magic'], 
									"clear_inventory" => $this->Madmin->vars['clear_inventory'], 
									"clear_exp_inventory" => $this->Madmin->vars['clear_exp_inventory'], 
									"clear_equipment" => $this->Madmin->vars['clear_equipment'], 
									"clear_store" => $this->Madmin->vars['clear_store'], 
									"clear_stats" => $this->Madmin->vars['clear_stats'], 
									"clear_level_up" => $this->Madmin->vars['clear_level_up'], 
									"new_stat_points" => $this->Madmin->vars['new_stat_points'], 
									"new_free_points" => $this->Madmin->vars['new_free_points'], 
									"bonus_points" => $class_bonus, 
									"bonus_credits" => $this->Madmin->vars['bonus_credits'], 
									"bonus_gcredits" => $this->Madmin->vars['bonus_gcredits'], 
									"bonus_ruud" => $this->Madmin->vars['bonus_ruud'], 
									"reset_cooldown" => $this->Madmin->vars['reset_cooldown'], 
									"bonus_gr_points" => $this->Madmin->vars['bonus_gr_points'], 
									"clear_masterlevel" => $this->Madmin->vars['clear_masterlevel']
								]
							]];
                            $this->vars['reset_config'] = array_merge($this->vars['reset_config'], $new_config);
                            $this->config->save_config_data($this->vars['reset_config'], 'reset_config');
                            $this->vars['success'] = 'Reset configuration added.';
                        }
                    }
                }
                $this->load->view('admincp' . DS . 'website_settings' . DS . 'view.add_reset_settings', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

		public function add_greset_settings(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                $this->vars['servers'] = $this->website->server_list();
                if(isset($_POST['add_greset_settings'])){
                    foreach($_POST as $key => $value){
                        $this->Madmin->$key = $value;
                    }
                    if(!isset($this->Madmin->vars['server']) || $this->Madmin->vars['server'] == '')
                        $this->vars['error'][] = 'Please select server.';
                    if(!isset($this->Madmin->vars['sreset']) || !preg_match('/^\d+$/', $this->Madmin->vars['sreset']))
                        $this->vars['error'][] = 'Starting grand reset can only be numeric value';
                    if(!isset($this->Madmin->vars['ereset']) || !preg_match('/^\d+$/', $this->Madmin->vars['ereset']))
                        $this->vars['error'][] = 'Ending grand reset can only be numeric value';
                    if(!isset($this->Madmin->vars['money']) || !preg_match('/^\d+$/', $this->Madmin->vars['money']))
                        $this->vars['error'][] = 'Required zen can only be numeric value';
                    if(!isset($this->Madmin->vars['level']) || !preg_match('/^\d+$/', $this->Madmin->vars['level']))
                        $this->vars['error'][] = 'Required level can only be numeric value';
					if(!isset($this->Madmin->vars['mlevel']) || !preg_match('/^\d+$/', $this->Madmin->vars['mlevel']))
                        $this->vars['error'][] = 'Required master level can only be numeric value';
                    if(!isset($this->Madmin->vars['reset']) || !preg_match('/^\d+$/', $this->Madmin->vars['reset']))
                        $this->vars['error'][] = 'Required reset can only be numeric value';
                    if(!isset($this->Madmin->vars['new_stat_points']) || !preg_match('/^\d+$/', $this->Madmin->vars['new_stat_points']))
                        $this->vars['error'][] = 'New stat points can only be numeric value';
                    if(!isset($this->Madmin->vars['new_free_points']) || !preg_match('/^\d+$/', $this->Madmin->vars['new_free_points']))
                        $this->vars['error'][] = 'New levelup points can only be numeric value';
                    foreach($this->website->get_char_class(0, false, true) AS $k => $class){ 
						if(!isset($this->Madmin->vars['bonus_lvl_up_'.$k.'']) || !preg_match('/^\d+$/', $this->Madmin->vars['bonus_lvl_up_'.$k.'']))
							$this->vars['error'][] = 'Bonus level up points for '.$class['short'].'('.$k.') can only be numeric value';
					}
                    if(!isset($this->Madmin->vars['bonus_credits']) || !preg_match('/^\d+$/', $this->Madmin->vars['bonus_credits']))
                        $this->vars['error'][] = 'Bonus credits value can only be numeric value';
                    if(!isset($this->Madmin->vars['bonus_gcredits']) || !preg_match('/^\d+$/', $this->Madmin->vars['bonus_gcredits']))
                        $this->vars['error'][] = 'Bonus gold credits value can only be numeric value';
					if(!isset($this->Madmin->vars['bonus_ruud']) || !preg_match('/^\d+$/', $this->Madmin->vars['bonus_ruud']))
                        $this->vars['error'][] = 'Bonus ruud value can only be numeric value';
                    if(!isset($this->vars['error']) || count($this->vars['error']) <= 0){
                        $this->vars['greset_config'] = $this->config->values('greset_config');
						$class_bonus = [];
						foreach($this->website->get_char_class(0, false, true) AS $kk => $class){ 
							$class_bonus[] = $this->Madmin->vars['bonus_lvl_up_'.$kk.''];
						}
                        if(array_key_exists($this->Madmin->vars['server'], $this->vars['greset_config'])){
                            $temp_array = $this->vars['greset_config'];
                            unset($temp_array[$this->Madmin->vars['server']]['allow_greset']);
                            foreach($temp_array[$this->Madmin->vars['server']] AS $key => $value){
                                list($start, $end) = explode('-', $key);
                                if(in_array($this->Madmin->vars['sreset'], range($start, $end - 1))){
                                    $this->vars['error'][] = 'Starting grand reset is in another grand reset configuration range';
                                }
                                if(in_array($this->Madmin->vars['ereset'], range($start, $end))){
                                    $this->vars['error'][] = 'Ending grand reset is in another grand reset configuration range';
                                }
                            }
                            if(!isset($this->vars['error']) || count($this->vars['error']) <= 0){
                                $this->vars['greset_config'][$this->Madmin->vars['server']][$this->Madmin->vars['sreset'] . '-' . $this->Madmin->vars['ereset']] = [
									"money" => $this->Madmin->vars['money'], 
									"money_x_reset" => $this->Madmin->vars['money_x_reset'], 
									"level" => $this->Madmin->vars['level'],
									"mlevel" => $this->Madmin->vars['mlevel'],	
									"reset" => $this->Madmin->vars['reset'], 
									"clear_all_resets" => $this->Madmin->vars['clear_all_resets'], 
									"clear_magic" => $this->Madmin->vars['clear_magic'], 
									"clear_inventory" => $this->Madmin->vars['clear_inventory'], 
									"clear_stats" => $this->Madmin->vars['clear_stats'], 
									"clear_level_up" => $this->Madmin->vars['clear_level_up'], 
									"new_stat_points" => $this->Madmin->vars['new_stat_points'], 
									"new_free_points" => $this->Madmin->vars['new_free_points'],
									"bonus_points" => $class_bonus, 
									"bonus_points_save" => $this->Madmin->vars['bonus_points_save'], 
									"bonus_reset_stats" => $this->Madmin->vars['bonus_reset_stats'], 
									"bonus_credits" => $this->Madmin->vars['bonus_credits'], 
									"bonus_gcredits" => $this->Madmin->vars['bonus_gcredits'], 
									"bonus_ruud" => $this->Madmin->vars['bonus_ruud'], 
									"clear_masterlevel" => $this->Madmin->vars['clear_masterlevel']
								];
                                $this->config->save_config_data($this->vars['greset_config'], 'greset_config');
                                $this->vars['success'] = 'Grand Reset configuration added.';
                            }
                        } else{
                            $new_config = [$this->Madmin->vars['server'] => [
								"allow_greset" => 1, 
								$this->Madmin->vars['sreset'] . '-' . $this->Madmin->vars['ereset'] => [
									"money" => $this->Madmin->vars['money'], 
									"money_x_reset" => $this->Madmin->vars['money_x_reset'], 
									"level" => $this->Madmin->vars['level'], 
									"mlevel" => $this->Madmin->vars['mlevel'], 
									"reset" => $this->Madmin->vars['reset'], 
									"clear_all_resets" => $this->Madmin->vars['clear_all_resets'], 
									"clear_magic" => $this->Madmin->vars['clear_magic'], 
									"clear_inventory" => $this->Madmin->vars['clear_inventory'], 
									"clear_stats" => $this->Madmin->vars['clear_stats'], 
									"clear_level_up" => $this->Madmin->vars['clear_level_up'], 
									"new_stat_points" => $this->Madmin->vars['new_stat_points'], 
									"new_free_points" => $this->Madmin->vars['new_free_points'], 
									"bonus_points" => $class_bonus, 
									"bonus_points_save" => $this->Madmin->vars['bonus_points_save'], 
									"bonus_reset_stats" => $this->Madmin->vars['bonus_reset_stats'], 
									"bonus_credits" => $this->Madmin->vars['bonus_credits'], 
									"bonus_gcredits" => $this->Madmin->vars['bonus_gcredits'], 
									"bonus_ruud" => $this->Madmin->vars['bonus_ruud'], 
									"clear_masterlevel" => $this->Madmin->vars['clear_masterlevel']
								]
							]];
                            $this->vars['greset_config'] = array_merge($this->vars['greset_config'], $new_config);
                            $this->config->save_config_data($this->vars['greset_config'], 'greset_config');
                            $this->vars['success'] = 'Grand Reset configuration added.';
                        }
                    }
                }
                $this->load->view('admincp' . DS . 'website_settings' . DS . 'view.add_greset_settings', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

				
		public function change_class_req_items(){
			 if($this->session->userdata(['admin' => 'is_admin'])){
				$this->load_header();
                $this->vars['server'] = $_GET['server'];
                $this->vars['reset_items_config'] = $this->config->values('class_change_items_config');
				//$this->vars['key'] = $key;
                $this->load->lib('serverfile');
				$this->load->helper('webshop');
				if(isset($_GET['step']) &&  $_GET['step'] == 2){
					if(isset($_POST['items'])){
						$this->vars['items'] = $_POST['items'];
						$this->vars['itemData'] = [];
						foreach($this->vars['items'] AS $k => $item){
							$data = explode('-', $item);
							$info = $this->serverfile->item_list($data[1], $this->website->get_value_from_server($this->vars['server'], 'item_size'))->get('items');
							$this->vars['itemData'][$k] = $info[$data[0]]['name'];
						}

						$this->load->view('admincp' . DS . 'website_settings' . DS . 'view.change_class_items_step_2', $this->vars);
					}
					else{
						if(isset($_POST['name'])){
							$ckey = $_POST['ckey'];
							if($ckey == ''){
								$this->vars['error'] = 'Please enter config key';
							}
							$names = $_POST['name'];
							$minLv = $_POST['item_min_lvl'];
							$maxLv = $_POST['item_max_lvl'];
							$minOpt = $_POST['item_min_opt'];
							$maxOpt = $_POST['item_max_opt'];
							$exe = $_POST['item_exe'];
							$skipPrice = $_POST['skip_price'];
							$priceType = $_POST['skip_price_type'];
							
							$genItems = [];
							
							foreach($names AS $it => $data){
								$idCat = explode('-', $it);
								$genItems[] = [
									'id' => $idCat[0],
									'cat' => $idCat[1],
									'name' => $names[$it],
									'minLvl' => $minLv[$it],
									'maxLvl' => $maxLv[$it],
									'minOpt' => $minOpt[$it],
									'maxOpt' => $maxOpt[$it],
									'exe' => $exe[$it],
									'skipPrice' => $skipPrice[$it],
									'priceType' => $priceType[$it]
								];
							}
							if(!empty($this->vars['reset_items_config'])){
								if(!empty($this->vars['reset_items_config'][$this->vars['server']])){
									if(!empty($this->vars['reset_items_config'][$this->vars['server']][$ckey])){
										$this->vars['reset_items_config'][$this->vars['server']][$ckey] = array_merge($this->vars['reset_items_config'][$this->vars['server']][$ckey], $genItems);
									}
									else{
										$this->vars['reset_items_config'][$this->vars['server']][$ckey] = $genItems;
									}
								}
								else{
									$this->vars['reset_items_config'][$this->vars['server']] = [$ckey => $genItems];
								}
							}
							else{
								$this->vars['reset_items_config'] = [$this->vars['server'] => [$ckey => $genItems]];
							}
							if(!isset($this->vars['error'])){
								$this->config->save_config_data($this->vars['reset_items_config'], 'class_change_items_config');
								$this->vars['success'] = 'Items added.';
							}
						}
						else{
							 $this->vars['error'] = 'No items to add.';
						}
						$this->load->view('admincp' . DS . 'website_settings' . DS . 'view.change_class_items_step_3', $this->vars);
					}
				}
				else{
					if(isset($_GET['remove'])){
						$cat = $_GET['key'];
						if(isset($this->vars['reset_items_config'][$this->vars['server']][$cat])){
							if(array_key_exists($_GET['remove'], $this->vars['reset_items_config'][$this->vars['server']][$cat])){
								unset($this->vars['reset_items_config'][$this->vars['server']][$cat][$_GET['remove']]);
								$this->config->save_config_data($this->vars['reset_items_config'], 'class_change_items_config');
							}
						}
					}
					$cats = $this->webshop->load_cat_list_array();
					$this->vars['items'] = [];
					foreach($cats AS $catId => $name){
						$this->vars['items'][$catId] = $this->serverfile->item_list($catId, $this->website->get_value_from_server($this->vars['server'], 'item_size'))->get('items');
					}
					
					$this->load->view('admincp' . DS . 'website_settings' . DS . 'view.change_class_items', $this->vars);
				}
				$this->load_footer();
			 } else{
                $this->login();
            }
		}

				
		public function edit_reset_items($key, $server){
			 if($this->session->userdata(['admin' => 'is_admin'])){
				$this->load_header();
                $this->vars['server'] = $server;
                $this->vars['reset_items_config'] = $this->config->values('reset_items_config');
				$this->vars['key'] = $key;
                $this->load->lib('serverfile');
				$this->load->helper('webshop');
				if(isset($_GET['step']) &&  $_GET['step'] == 2){
					if(isset($_POST['items'])){
						$this->vars['items'] = $_POST['items'];
						$this->vars['itemData'] = [];
						foreach($this->vars['items'] AS $k => $item){
							$data = explode('-', $item);
							$info = $this->serverfile->item_list($data[1], $this->website->get_value_from_server($server, 'item_size'))->get('items');
							$this->vars['itemData'][$k] = $info[$data[0]]['name'];
						}

						$this->load->view('admincp' . DS . 'website_settings' . DS . 'view.reset_items_step_2', $this->vars);
					}
					else{
						if(isset($_POST['name'])){
							$ckey = $_POST['ckey'];
							if($ckey == ''){
								$this->vars['error'] = 'Please enter config key';
							}
							$names = $_POST['name'];
							$minLv = $_POST['item_min_lvl'];
							$maxLv = $_POST['item_max_lvl'];
							$minOpt = $_POST['item_min_opt'];
							$maxOpt = $_POST['item_max_opt'];
							$exe = $_POST['item_exe'];
							$skipPrice = $_POST['skip_price'];
							$priceType = $_POST['skip_price_type'];
							
							$genItems = [];
							
							foreach($names AS $it => $data){
								$idCat = explode('-', $it);
								$genItems[] = [
									'id' => $idCat[0],
									'cat' => $idCat[1],
									'name' => $names[$it],
									'minLvl' => $minLv[$it],
									'maxLvl' => $maxLv[$it],
									'minOpt' => $minOpt[$it],
									'maxOpt' => $maxOpt[$it],
									'exe' => $exe[$it],
									'skipPrice' => $skipPrice[$it],
									'priceType' => $priceType[$it]
								];
							}
							if(!empty($this->vars['reset_items_config'])){
								if(!empty($this->vars['reset_items_config'][$server])){
									if(!empty($this->vars['reset_items_config'][$server][$key][$ckey])){
										$this->vars['reset_items_config'][$server][$key][$ckey] = array_merge($this->vars['reset_items_config'][$server][$key][$ckey], $genItems);
									}
									else{
										$this->vars['reset_items_config'][$server][$key][$ckey] = $genItems;
									}
								}
								else{
									$this->vars['reset_items_config'][$server] = [$key => [$ckey => $genItems]];
								}
							}
							else{
								$this->vars['reset_items_config'] = [$server => [$key => [$ckey => $genItems]]];
							}
							if(!isset($this->vars['error'])){
								$this->config->save_config_data($this->vars['reset_items_config'], 'reset_items_config');
								$this->vars['success'] = 'Reset items added.';
							}
						}
						else{
							 $this->vars['error'] = 'No items to add.';
						}
						$this->load->view('admincp' . DS . 'website_settings' . DS . 'view.reset_items_step_3', $this->vars);
					}
				}
				else{
					if(isset($_GET['remove'])){
						$cat = $_GET['key'];
						if(isset($this->vars['reset_items_config'][$server][$key][$cat])){
							if(array_key_exists($_GET['remove'], $this->vars['reset_items_config'][$server][$key][$cat])){
								unset($this->vars['reset_items_config'][$server][$key][$cat][$_GET['remove']]);
								$this->config->save_config_data($this->vars['reset_items_config'], 'reset_items_config');
							}
						}
					}
					$cats = $this->webshop->load_cat_list_array();
					$this->vars['items'] = [];
					foreach($cats AS $catId => $name){
						$this->vars['items'][$catId] = $this->serverfile->item_list($catId, $this->website->get_value_from_server($server, 'item_size'))->get('items');
					}
					
					$this->load->view('admincp' . DS . 'website_settings' . DS . 'view.reset_items', $this->vars);
				}
				$this->load_footer();
			 } else{
                $this->login();
            }
		}

				
		public function edit_greset_items($key, $server){
			 if($this->session->userdata(['admin' => 'is_admin'])){
				$this->load_header();
                $this->vars['server'] = $server;
                $this->vars['reset_items_config'] = $this->config->values('greset_items_config');
				$this->vars['key'] = $key;
                $this->load->lib('serverfile');
				$this->load->helper('webshop');
				if(isset($_GET['step']) &&  $_GET['step'] == 2){
					if(isset($_POST['items'])){
						$this->vars['items'] = $_POST['items'];
						$this->vars['itemData'] = [];
						foreach($this->vars['items'] AS $k => $item){
							$data = explode('-', $item);
							$info = $this->serverfile->item_list($data[1], $this->website->get_value_from_server($server, 'item_size'))->get('items');
							$this->vars['itemData'][$k] = $info[$data[0]]['name'];
						}

						$this->load->view('admincp' . DS . 'website_settings' . DS . 'view.greset_items_step_2', $this->vars);
					}
					else{
						if(isset($_POST['name'])){
							$ckey = $_POST['ckey'];
							if($ckey == ''){
								$this->vars['error'] = 'Please enter config key';
							}
							$names = $_POST['name'];
							$minLv = $_POST['item_min_lvl'];
							$maxLv = $_POST['item_max_lvl'];
							$minOpt = $_POST['item_min_opt'];
							$maxOpt = $_POST['item_max_opt'];
							$exe = $_POST['item_exe'];
							$skipPrice = $_POST['skip_price'];
							$priceType = $_POST['skip_price_type'];
							
							$genItems = [];
							
							foreach($names AS $it => $data){
								$idCat = explode('-', $it);
								$genItems[] = [
									'id' => $idCat[0],
									'cat' => $idCat[1],
									'name' => $names[$it],
									'minLvl' => $minLv[$it],
									'maxLvl' => $maxLv[$it],
									'minOpt' => $minOpt[$it],
									'maxOpt' => $maxOpt[$it],
									'exe' => $exe[$it],
									'skipPrice' => $skipPrice[$it],
									'priceType' => $priceType[$it]
								];
							}
							if(!empty($this->vars['reset_items_config'])){
								if(!empty($this->vars['reset_items_config'][$server])){
									if(!empty($this->vars['reset_items_config'][$server][$key][$ckey])){
										$this->vars['reset_items_config'][$server][$key][$ckey] = array_merge($this->vars['reset_items_config'][$server][$key][$ckey], $genItems);
									}
									else{
										$this->vars['reset_items_config'][$server][$key][$ckey] = $genItems;
									}
								}
								else{
									$this->vars['reset_items_config'][$server] = [$key => [$ckey => $genItems]];
								}
							}
							else{
								$this->vars['reset_items_config'] = [$server => [$key => [$ckey => $genItems]]];
							}
							if(!isset($this->vars['error'])){
								$this->config->save_config_data($this->vars['reset_items_config'], 'greset_items_config');
								$this->vars['success'] = 'Grand Reset items added.';
							}
						}
						else{
							 $this->vars['error'] = 'No items to add.';
						}
						$this->load->view('admincp' . DS . 'website_settings' . DS . 'view.greset_items_step_3', $this->vars);
					}
				}
				else{
					if(isset($_GET['remove'])){
						$cat = $_GET['key'];
						if(isset($this->vars['reset_items_config'][$server][$key][$cat])){
							if(array_key_exists($_GET['remove'], $this->vars['reset_items_config'][$server][$key][$cat])){
								unset($this->vars['reset_items_config'][$server][$key][$cat][$_GET['remove']]);
								$this->config->save_config_data($this->vars['reset_items_config'], 'greset_items_config');
							}
						}
					}
					$cats = $this->webshop->load_cat_list_array();
					$this->vars['items'] = [];
					foreach($cats AS $catId => $name){
						$this->vars['items'][$catId] = $this->serverfile->item_list($catId, $this->website->get_value_from_server($server, 'item_size'))->get('items');
					}
					
					$this->load->view('admincp' . DS . 'website_settings' . DS . 'view.greset_items', $this->vars);
				}
				$this->load_footer();
			 } else{
                $this->login();
            }
		}

		public function edit_reset_settings($key, $server){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                $this->vars['servers'] = $this->website->server_list();
                $this->vars['reset_config'] = $this->config->values('reset_config', $server);
                if(!$this->vars['reset_config']){
                    $this->vars['not_found'] = 'Reset configuration for this server not found.';
                } else{
                    if(!array_key_exists($key, $this->vars['reset_config'])){
                        $this->vars['not_found'] = 'Reset configuration for this reset range not found.';
                    } else{
                        if(isset($_POST['edit_settings'])){
                            foreach($_POST as $keys => $value){
                                $this->Madmin->$keys = $value;
                            }
                            if(!isset($this->Madmin->vars['server']) || $this->Madmin->vars['server'] == '')
                                $this->vars['error'][] = 'Please select server.';
                            if(!isset($this->Madmin->vars['sreset']) || !preg_match('/^\d+$/', $this->Madmin->vars['sreset']))
                                $this->vars['error'][] = 'Starting reset can only be numeric value';
                            if(!isset($this->Madmin->vars['ereset']) || !preg_match('/^\d+$/', $this->Madmin->vars['ereset']))
                                $this->vars['error'][] = 'Ending reset can only be numeric value';
                            if(!isset($this->Madmin->vars['money']) || !preg_match('/^\d+$/', $this->Madmin->vars['money']))
                                $this->vars['error'][] = 'Required zen can only be numeric value';
                            if(!isset($this->Madmin->vars['level']) || !preg_match('/^\d+$/', $this->Madmin->vars['level']))
                                $this->vars['error'][] = 'Required level can only be numeric value';
							if(!isset($this->Madmin->vars['mlevel']) || !preg_match('/^\d+$/', $this->Madmin->vars['mlevel']))
								$this->vars['error'][] = 'Required master level can only be numeric value';
							if(!isset($this->Madmin->vars['level_after_reset']) || !preg_match('/^\d+$/', $this->Madmin->vars['level_after_reset']))
                                $this->vars['error'][] = 'Level after reset can only be numeric value';
                            if(!isset($this->Madmin->vars['reset_cooldown']) || !preg_match('/^\d+$/', $this->Madmin->vars['reset_cooldown']))
                                $this->vars['error'][] = 'Reset cooldown can only be numeric value';
                            if(!isset($this->Madmin->vars['new_stat_points']) || !preg_match('/^\d+$/', $this->Madmin->vars['new_stat_points']))
                                $this->vars['error'][] = 'New stat points can only be numeric value';
                            if(!isset($this->Madmin->vars['new_free_points']) || !preg_match('/^\d+$/', $this->Madmin->vars['new_free_points']))
                                $this->vars['error'][] = 'New levelup points can only be numeric value';
                            foreach($this->website->get_char_class(0, false, true) AS $k => $class){ 
								if(!isset($this->Madmin->vars['bonus_lvl_up_'.$k.'']) || !preg_match('/^\d+$/', $this->Madmin->vars['bonus_lvl_up_'.$k.'']))
									$this->vars['error'][] = 'Bonus level up points for '.$class['short'].'('.$k.') can only be numeric value';
							}
                            if(!isset($this->Madmin->vars['bonus_credits']) || !preg_match('/^\d+$/', $this->Madmin->vars['bonus_credits']))
                                $this->vars['error'][] = 'Bonus credits value can only be numeric value';
                            if(!isset($this->Madmin->vars['bonus_gcredits']) || !preg_match('/^\d+$/', $this->Madmin->vars['bonus_gcredits']))
                                $this->vars['error'][] = 'Bonus gold credits value can only be numeric value';
							if(!isset($this->Madmin->vars['bonus_ruud']) || !preg_match('/^\d+$/', $this->Madmin->vars['bonus_ruud']))
								$this->vars['error'][] = 'Bonus ruud value can only be numeric value';
                            if(!isset($this->vars['error']) || count($this->vars['error']) <= 0){
                                $this->vars['all_config'] = $this->config->values('reset_config');
								$class_bonus = [];
								foreach($this->website->get_char_class(0, false, true) AS $kk => $class){ 
									$class_bonus[$kk] = $this->Madmin->vars['bonus_lvl_up_'.$kk.''];
								}
                                if(array_key_exists($this->Madmin->vars['server'], $this->vars['all_config'])){
                                    $temp_array = $this->vars['all_config'];
                                    unset($temp_array[$this->Madmin->vars['server']]['allow_reset'], $temp_array[$server][$key]);
                                    foreach($temp_array[$this->Madmin->vars['server']] AS $keyy => $value){
                                        list($start, $end) = explode('-', $keyy);
                                        if(in_array($this->Madmin->vars['sreset'], range($start, $end - 1))){
                                            $this->vars['error'][] = 'Starting reset is in another reset configuration range';
                                        }
                                        if(in_array($this->Madmin->vars['ereset'], range($start, $end))){
                                            $this->vars['error'][] = 'Ending reset is in another reset configuration range';
                                        }
                                    }
                                    if(!isset($this->vars['error']) || count($this->vars['error']) <= 0){
                                        unset($this->vars['all_config'][$server][$key]);
                                        $this->vars['all_config'][$this->Madmin->vars['server']][$this->Madmin->vars['sreset'] . '-' . $this->Madmin->vars['ereset']] = [
											"money" => $this->Madmin->vars['money'], 
											"money_x_reset" => $this->Madmin->vars['money_x_reset'], 
											"level" => $this->Madmin->vars['level'], 
											"mlevel" => $this->Madmin->vars['mlevel'], 
											"level_after_reset" => $this->Madmin->vars['level_after_reset'], 
											"clear_magic" => $this->Madmin->vars['clear_magic'], 
											"clear_inventory" => $this->Madmin->vars['clear_inventory'], 
											"clear_exp_inventory" => $this->Madmin->vars['clear_exp_inventory'], 
											"clear_equipment" => $this->Madmin->vars['clear_equipment'], 
											"clear_store" => $this->Madmin->vars['clear_store'], 
											"clear_stats" => $this->Madmin->vars['clear_stats'], 
											"clear_level_up" => $this->Madmin->vars['clear_level_up'], 
											"new_stat_points" => $this->Madmin->vars['new_stat_points'], 
											"new_free_points" => $this->Madmin->vars['new_free_points'], 
											"bonus_points" => $class_bonus, 
											"bonus_credits" => $this->Madmin->vars['bonus_credits'], 
											"bonus_gcredits" => $this->Madmin->vars['bonus_gcredits'], 
											"bonus_ruud" => $this->Madmin->vars['bonus_ruud'], 
											"reset_cooldown" => $this->Madmin->vars['reset_cooldown'], 
											"bonus_gr_points" => $this->Madmin->vars['bonus_gr_points'], 
											"clear_masterlevel" => $this->Madmin->vars['clear_masterlevel']
										];
                                        $this->config->save_config_data($this->vars['all_config'], 'reset_config');
                                        header('Location: ' . $this->config->base_url . ACPURL . '/manage-settings/reset');
                                    }
                                }
                            }
                        }
                        $this->vars['r_config'] = $this->vars['reset_config'][$key];
                        list($this->vars['r_config']['sreset'], $this->vars['r_config']['ereset']) = explode('-', $key);
                        $this->vars['selected_server'] = $server;
                    }
                }
                $this->load->view('admincp' . DS . 'website_settings' . DS . 'view.edit_reset_settings', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

		public function edit_greset_settings($key, $server){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                $this->vars['servers'] = $this->website->server_list();
                $this->vars['greset_config'] = $this->config->values('greset_config', $server);
                if(!$this->vars['greset_config']){
                    $this->vars['not_found'] = 'Grand Reset configuration for this server not found.';
                } else{
                    if(!array_key_exists($key, $this->vars['greset_config'])){
                        $this->vars['not_found'] = 'Grand Reset configuration for this grand reset range not found.';
                    } else{
                        if(isset($_POST['edit_settings'])){
                            foreach($_POST as $keys => $value){
                                $this->Madmin->$keys = $value;
                            }
                            if(!isset($this->Madmin->vars['server']) || $this->Madmin->vars['server'] == '')
                                $this->vars['error'][] = 'Please select server.';
                            if(!isset($this->Madmin->vars['sreset']) || !preg_match('/^\d+$/', $this->Madmin->vars['sreset']))
                                $this->vars['error'][] = 'Starting grand reset can only be numeric value';
                            if(!isset($this->Madmin->vars['ereset']) || !preg_match('/^\d+$/', $this->Madmin->vars['ereset']))
                                $this->vars['error'][] = 'Ending grand reset can only be numeric value';
                            if(!isset($this->Madmin->vars['money']) || !preg_match('/^\d+$/', $this->Madmin->vars['money']))
                                $this->vars['error'][] = 'Required zen can only be numeric value';
                            if(!isset($this->Madmin->vars['level']) || !preg_match('/^\d+$/', $this->Madmin->vars['level']))
                                $this->vars['error'][] = 'Required level can only be numeric value';
							 if(!isset($this->Madmin->vars['mlevel']) || !preg_match('/^\d+$/', $this->Madmin->vars['mlevel']))
                                $this->vars['error'][] = 'Required master level can only be numeric value';
                            if(!isset($this->Madmin->vars['reset']) || !preg_match('/^\d+$/', $this->Madmin->vars['reset']))
                                $this->vars['error'][] = 'Required reset can only be numeric value';
                            if(!isset($this->Madmin->vars['new_stat_points']) || !preg_match('/^\d+$/', $this->Madmin->vars['new_stat_points']))
                                $this->vars['error'][] = 'New stat points can only be numeric value';
                            if(!isset($this->Madmin->vars['new_free_points']) || !preg_match('/^\d+$/', $this->Madmin->vars['new_free_points']))
                                $this->vars['error'][] = 'New levelup points can only be numeric value';
                            foreach($this->website->get_char_class(0, false, true) AS $k => $class){ 
								if(!isset($this->Madmin->vars['bonus_lvl_up_'.$k.'']) || !preg_match('/^\d+$/', $this->Madmin->vars['bonus_lvl_up_'.$k.'']))
									$this->vars['error'][] = 'Bonus level up points for '.$class['short'].'('.$k.') can only be numeric value';
							}
                            if(!isset($this->Madmin->vars['bonus_credits']) || !preg_match('/^\d+$/', $this->Madmin->vars['bonus_credits']))
                                $this->vars['error'][] = 'Bonus credits value can only be numeric value';
                            if(!isset($this->Madmin->vars['bonus_gcredits']) || !preg_match('/^\d+$/', $this->Madmin->vars['bonus_gcredits']))
                                $this->vars['error'][] = 'Bonus gold credits value can only be numeric value';
							if(!isset($this->Madmin->vars['bonus_ruud']) || !preg_match('/^\d+$/', $this->Madmin->vars['bonus_ruud']))
								$this->vars['error'][] = 'Bonus ruud value can only be numeric value';
                            if(!isset($this->vars['error']) || count($this->vars['error']) <= 0){
                                $this->vars['all_config'] = $this->config->values('greset_config');
								$class_bonus = [];
								foreach($this->website->get_char_class(0, false, true) AS $kk => $class){ 
									$class_bonus[$kk] = $this->Madmin->vars['bonus_lvl_up_'.$kk.''];
								}
                                if(array_key_exists($this->Madmin->vars['server'], $this->vars['all_config'])){
                                    $temp_array = $this->vars['all_config'];
                                    unset($temp_array[$this->Madmin->vars['server']]['allow_greset'], $temp_array[$server][$key]);
                                    foreach($temp_array[$this->Madmin->vars['server']] AS $keyy => $value){
                                        list($start, $end) = explode('-', $keyy);
                                        if(in_array($this->Madmin->vars['sreset'], range($start, $end - 1))){
                                            $this->vars['error'][] = 'Starting grand reset is in another grand reset configuration range';
                                        }
                                        if(in_array($this->Madmin->vars['ereset'], range($start, $end))){
                                            $this->vars['error'][] = 'Ending grand reset is in another grand reset configuration range';
                                        }
                                    }
                                    if(!isset($this->vars['error']) || count($this->vars['error']) <= 0){
                                        unset($this->vars['all_config'][$server][$key]);
                                        $this->vars['all_config'][$this->Madmin->vars['server']][$this->Madmin->vars['sreset'] . '-' . $this->Madmin->vars['ereset']] = [
											"money" => $this->Madmin->vars['money'], 
											"money_x_reset" => $this->Madmin->vars['money_x_reset'], 
											"level" => $this->Madmin->vars['level'], 
											"mlevel" => $this->Madmin->vars['mlevel'], 
											"reset" => $this->Madmin->vars['reset'], 
											"clear_all_resets" => $this->Madmin->vars['clear_all_resets'], 
											"clear_magic" => $this->Madmin->vars['clear_magic'], 
											"clear_inventory" => $this->Madmin->vars['clear_inventory'], 
											"clear_stats" => $this->Madmin->vars['clear_stats'], 
											"clear_level_up" => $this->Madmin->vars['clear_level_up'], 
											"new_stat_points" => $this->Madmin->vars['new_stat_points'], 
											"new_free_points" => $this->Madmin->vars['new_free_points'], 
											"bonus_points" => $class_bonus, 
											"bonus_points_save" => $this->Madmin->vars['bonus_points_save'], 
											"bonus_reset_stats" => $this->Madmin->vars['bonus_reset_stats'], 
											"bonus_credits" => $this->Madmin->vars['bonus_credits'], 
											"bonus_gcredits" => $this->Madmin->vars['bonus_gcredits'], 
											"bonus_ruud" => $this->Madmin->vars['bonus_ruud'], 
											"clear_masterlevel" => $this->Madmin->vars['clear_masterlevel']
										];
                                        $this->config->save_config_data($this->vars['all_config'], 'greset_config');
                                        header('Location: ' . $this->config->base_url . ACPURL . '/manage-settings/greset');
                                    }
                                }
                            }
                        }
                        $this->vars['r_config'] = $this->vars['greset_config'][$key];
                        list($this->vars['r_config']['sreset'], $this->vars['r_config']['ereset']) = explode('-', $key);
                        $this->vars['selected_server'] = $server;
                    }
                }
                $this->load->view('admincp' . DS . 'website_settings' . DS . 'view.edit_greset_settings', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function change_reset_status(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $server = isset($_POST['server']) ? $_POST['server'] : '';
                if($server == '')
                    json(['error' => 'Invalid server.']); else{
                    $this->vars['reset_config'] = $this->config->values('reset_config');
                    if(array_key_exists($server, $this->vars['reset_config'])){
                        $this->vars['reset_config'][$server]['allow_reset'] = (int)$_POST['status'];
                        $this->config->save_config_data($this->vars['reset_config'], 'reset_config');
                        json(['success' => 'Reset configuration saved.']);
                    } else{
                        json(['error' => 'Server not found.']);
                    }
                }
            } else{
                json(['error' => 'Please login first.']);
            }
        }

        public function change_greset_status(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $server = isset($_POST['server']) ? $_POST['server'] : '';
                if($server == '')
                    json(['error' => 'Invalid server.']); else{
                    $this->vars['greset_config'] = $this->config->values('greset_config');
                    if(array_key_exists($server, $this->vars['greset_config'])){
                        $this->vars['greset_config'][$server]['allow_greset'] = (int)$_POST['status'];
                        $this->config->save_config_data($this->vars['greset_config'], 'greset_config');
                        json(['success' => 'Grand Reset configuration saved.']);
                    } else{
                        json(['error' => 'Server not found.']);
                    }
                }
            } else{
                json(['error' => 'Please login first.']);
            }
        }

        public function delete_reset_settings(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $key = isset($_POST['key']) ? $_POST['key'] : '';
                $server = isset($_POST['server']) ? $_POST['server'] : '';
                if($key == '')
                    json(['error' => 'Invalid reset key.']); else{
                    if($server == '')
                        json(['error' => 'Invalid server.']); else{
                        $this->vars['reset_config'] = $this->config->values('reset_config');
                        if(array_key_exists($server, $this->vars['reset_config'])){
                            if(array_key_exists($key, $this->vars['reset_config'][$server])){
                                unset($this->vars['reset_config'][$server][$key]);
                                $this->config->save_config_data($this->vars['reset_config'], 'reset_config');
                                json(['success' => 'Reset configuration deleted.']);
                            } else{
                                json(['error' => 'Reset key not found.']);
                            }
                        } else{
                            json(['error' => 'Server not found.']);
                        }
                    }
                }
            } else{
                json(['error' => 'Please login first.']);
            }
        }

        public function delete_greset_settings(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $key = isset($_POST['key']) ? $_POST['key'] : '';
                $server = isset($_POST['server']) ? $_POST['server'] : '';
                if($key == '')
                    json(['error' => 'Invalid reset key.']); else{
                    if($server == '')
                        json(['error' => 'Invalid server.']); else{
                        $this->vars['greset_config'] = $this->config->values('greset_config');
                        if(array_key_exists($server, $this->vars['greset_config'])){
                            if(array_key_exists($key, $this->vars['greset_config'][$server])){
                                unset($this->vars['greset_config'][$server][$key]);
                                $this->config->save_config_data($this->vars['greset_config'], 'greset_config');
                                json(['success' => 'Grand Reset configuration deleted.']);
                            } else{
                                json(['error' => 'Grand Reset key not found.']);
                            }
                        } else{
                            json(['error' => 'Server not found.']);
                        }
                    }
                }
            } else{
                json(['error' => 'Please login first.']);
            }
        }

        public function server_list_manager(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                $this->vars['server_list'] = $this->website->server_list();
                $this->load->view('admincp' . DS . 'server_manager' . DS . 'view.server_list_manager', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }
        
        public function game_server_list_manager(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                $this->vars['server_list'] = $this->website->server_list();
				$this->vars['game_server_list'] = $this->config->values('gameserver_config');
                $this->load->view('admincp' . DS . 'server_manager' . DS . 'view.game_server_list_manager', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

        public function save_plugin_order(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->vars['plugin_list'] = $this->config->values('plugin_config');
                $new_array = [];
                foreach($_POST['order'] AS $value){
                    if($value != ''){
                        if(array_key_exists($value, $this->vars['plugin_list'])){
                            $new_array[$value] = $this->vars['plugin_list'][$value];
                        }
                    }
                }
                if($this->config->save_config_data($new_array, 'plugin_config', false)){
                    json(['success' => 'Plugin order changed.']);
                } else{
                    json(['error' => 'Unable to save plugin order.']);
                }
            } else{
                json(['error' => 'Please login first.']);
            }
        }

        public function save_server_order(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->vars['server_list'] = $this->website->server_list();
                $new_array = [];
                if($this->website->is_multiple_accounts()){
                    $new_array['USE_MULTI_ACCOUNT_DB'] = true;
                } else{
                    $new_array['USE_MULTI_ACCOUNT_DB'] = false;
                }
                foreach($_POST['order'] AS $value){
                    if(array_key_exists($value, $this->vars['server_list'])){
                        $new_array[$value] = $this->vars['server_list'][$value];
                    }
                }
                if(!$this->Madmin->save_server_data($new_array, false)){
                    json(['error' => 'Unable to save server order.']);
                } else{
                    $this->Madmin->reorder_server_in_config('buylevel_config', $_POST['order']);
                    $this->Madmin->reorder_server_in_config('donation_config', $_POST['order']);
                    $this->Madmin->reorder_server_in_config('rankings_config', $_POST['order']);
                    $this->Madmin->reorder_server_in_config('reset_config', $_POST['order']);
                    $this->Madmin->reorder_server_in_config('greset_config', $_POST['order']);
                    $this->Madmin->reorder_server_in_config('table_config', $_POST['order']);
                    $this->Madmin->reorder_server_in_config('wcoin_exchange_config', $_POST['order']);
                    json(['success' => 'Server order changed.']);
                }
            } else{
                json(['error' => 'Please login first.']);
            }
        }
        
        public function save_game_server_order(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->Madmin->reorder_server_in_config('gameserver_config', $_POST['order']);
				json(['success' => 'GameServer order changed.']);

            } else{
                json(['error' => 'Please login first.']);
            }
        }

        public function change_server_status(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->vars['server_list'] = $this->website->server_list();
                if($this->website->is_multiple_accounts()){
                    $this->vars['server_list'] = array_merge(['USE_MULTI_ACCOUNT_DB' => true], $this->vars['server_list']);
                } else{
                    $this->vars['server_list'] = array_merge(['USE_MULTI_ACCOUNT_DB' => false], $this->vars['server_list']);
                }
                if(array_key_exists($_POST['id'], $this->vars['server_list'])){
                    $this->vars['server_list'][$_POST['id']]['visible'] = $_POST['status'];
                    if(!$this->Madmin->save_server_data($this->vars['server_list'])){
                        json(['error' => 'Unable to save server status.']);
                    } else{
                        json(['success' => 'Server status changed.']);
                    }
                } else{
                    json(['error' => 'Server not found.']);
                }
            } else{
                json(['error' => 'Please login first.']);
            }
        }
        
        public function change_game_server_status(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->vars['game_server_list'] = $this->config->values('gameserver_config');
                if(array_key_exists($_POST['id'], $this->vars['game_server_list'])){
                    $this->vars['game_server_list'][$_POST['id']]['visible'] = $_POST['status'];
                    if(!$this->Madmin->save_config_data($this->vars['game_server_list'], 'gameserver_config', false)){
                        json(['error' => 'Unable to save gameserver status.']);
                    } else{
                        json(['success' => 'Gameserver status changed.']);
                    }
                } else{
                    json(['error' => 'Gameserver not found.']);
                }
            } else{
                json(['error' => 'Please login first.']);
            }
        }

        public function delete_server(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->vars['server_list'] = $this->website->server_list();
                if($this->website->is_multiple_accounts()){
                    $this->vars['server_list'] = array_merge(['USE_MULTI_ACCOUNT_DB' => true], $this->vars['server_list']);
                } else{
                    $this->vars['server_list'] = array_merge(['USE_MULTI_ACCOUNT_DB' => false], $this->vars['server_list']);
                }
                if(array_key_exists($_POST['id'], $this->vars['server_list'])){
                    if(count($this->vars['server_list']) > 2){
                        unset($this->vars['server_list'][$_POST['id']]);
                        if(!$this->Madmin->save_server_data($this->vars['server_list'])){
                            json(['error' => 'Unable to save server status.']);
                        } else{
                            $this->Madmin->remove_server_from_config('buylevel_config', $_POST['id']);
                            $this->Madmin->remove_server_from_config('character_market', $_POST['id']);
                            $this->Madmin->remove_server_from_config('mercadopago', $_POST['id']);
                            $this->Madmin->remove_server_from_config('paddle', $_POST['id']);
                            $this->Madmin->remove_server_from_config('ruud_exchange', $_POST['id']);
                            $this->Madmin->remove_server_from_config('stats_specialization', $_POST['id']);
                            $this->Madmin->remove_server_from_config('donation_config', $_POST['id']);
                            $this->Madmin->remove_server_from_config('rankings_config', $_POST['id']);
                            $this->Madmin->remove_server_from_config('reset_config', $_POST['id']);
                            $this->Madmin->remove_server_from_config('greset_config', $_POST['id']);
                            $this->Madmin->remove_server_from_config('table_config', $_POST['id']);
                            $this->Madmin->remove_server_from_config('wcoin_exchange_config', $_POST['id']);
                            $this->Madmin->remove_server_from_config('votereward_config', $_POST['id']);
                            json(['success' => 'Server deleted.']);
                        }
                    } else{
                        json(['error' => 'You need to have atleast one server.']);
                    }
                } else{
                    json(['error' => 'Server not found.']);
                }
            } else{
                json(['error' => 'Please login first.']);
            }
        }
        
        public function delete_game_server(){
            if($this->session->userdata(['admin' => 'is_admin'])){
				$this->vars['game_server_list'] = $this->config->values('gameserver_config');
                if(array_key_exists($_POST['id'], $this->vars['game_server_list'])){
                    $this->Madmin->remove_server_from_config('gameserver_config', $_POST['id']);
                    json(['success' => 'Gameserver removed.']);
                } else{
                    json(['error' => 'Gameserver not found.']);
                }
            } else{
                json(['error' => 'Please login first.']);
            }
        }

        public function change_multi_account_db(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->vars['server_list'] = $this->website->server_list();
                if(isset($_POST['status']) && $_POST['status'] == 1){
                    $this->vars['server_list'] = array_merge(['USE_MULTI_ACCOUNT_DB' => true], $this->vars['server_list']);
                } else{
                    $this->vars['server_list'] = array_merge(['USE_MULTI_ACCOUNT_DB' => false], $this->vars['server_list']);
                }
                if(!$this->Madmin->save_server_data($this->vars['server_list'])){
                    json(['error' => 'Unable to save data.']);
                } else{
                    if(isset($_POST['status']) && $_POST['status'] == 1){
                        json(['success' => 'Multiple account databases enabled.']);
                    } else{
                        json(['success' => 'Multiple account databases disabled.']);
                    }
                }
            } else{
                json(['error' => 'Please login first.']);
            }
        }
		
		public function add_server(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                if(count($_POST) > 0){
                    $skey = isset($_POST['key']) ? $_POST['key'] : '';
                    $title = isset($_POST['title']) ? $_POST['title'] : '';
                    $char_db = isset($_POST['char_db']) ? $_POST['char_db'] : '';
                    $account_db = isset($_POST['account_db']) ? $_POST['account_db'] : '';
                    $version = isset($_POST['version']) ? $_POST['version'] : '0';
                    $exp = isset($_POST['exp']) ? $_POST['exp'] : '0';
                    $drop = isset($_POST['drop']) ? $_POST['drop'] : '0';
                    $this->vars['server_list'] = $this->website->server_list();
                    if($skey == '')
                        $this->vars['error'] = 'Please enter server key'; 
                    else{
                        if(!preg_match("/^[a-zA-Z0-9\_\@$&amp;\%\[\]\(\)\-\,\<]+$/i", $skey))
                            $this->vars['error'] = 'Please enter valid server key'; 
                        else{
                            if(array_key_exists(strtoupper($this->website->seo_string($skey)), $this->vars['server_list']))
                                $this->vars['error'] = 'Server with this key already exists.'; 
                            else{
                                if($title == '')
                                    $this->vars['error'] = 'Please enter server title'; 
                                else{
                                    if(!preg_match("/[\w\W]/", $title))
                                        $this->vars['error'] = 'Please enter valid server title'; 
                                    else{
                                        if($char_db == '')
                                            $this->vars['error'] = 'Please select character database'; 
                                        else{
                                            if(!$this->Madmin->check_character($char_db))
                                                $this->vars['error'] = 'Char database does not contain any character table.'; 
                                            else{
                                                if($account_db == '')
                                                    $this->vars['error'] = 'Please select account database'; 
                                                else{
                                                    if(!$this->Madmin->check_memb_info($account_db))
                                                        $this->vars['error'] = 'Account database does not contain any account table.'; 
                                                    else{
                                                        if($this->website->is_multiple_accounts() == true){
                                                            $this->vars['server_list'] = array_merge(['USE_MULTI_ACCOUNT_DB' => true], $this->vars['server_list']);
                                                        } else{
                                                            $this->vars['server_list'] = array_merge(['USE_MULTI_ACCOUNT_DB' => false], $this->vars['server_list']);
                                                        }
                                                        $this->vars['columns'] = $this->Madmin->required_columns();
                                                        foreach($this->vars['columns']['account_db'] AS $key => $columns){
                                                            foreach($columns AS $col => $info){
                                                                if($this->Madmin->check_if_column_exists($col, $key, $account_db) == false){
                                                                    $this->Madmin->add_column($col, $key, $info, $account_db);
                                                                }
                                                            }
                                                        }
                                                        foreach($this->vars['columns']['char_db'] AS $key => $columns){
                                                            foreach($columns AS $col => $info){
                                                                if($this->Madmin->check_if_column_exists($col, $key, $char_db) == false){
                                                                    $this->Madmin->add_column($col, $key, $info, $char_db);
                                                                }
                                                            }
                                                        }
                                                        
                                                        $procedureFile = BASEDIR . 'setup' . DS . 'data' . DS . 'procedures' . DS . 'required_stored_procedures[20.05.2015].json';
                                                        if(file_exists($procedureFile)){
                                                            $procedures_info = json_decode(file_get_contents($procedureFile), true);
                                                            if($this->Madmin->check_procedure('WZ_CONNECT_MEMB', $account_db) != false){
                                                                $this->Madmin->drop_procedure('WZ_CONNECT_MEMB', $account_db);
                                                            }
                                                            if($this->Madmin->check_procedure('WZ_DISCONNECT_MEMB', $account_db) != false){
                                                                $this->Madmin->drop_procedure('WZ_DISCONNECT_MEMB', $account_db);
                                                            }
                                                            if(MD5 == 1){
                                                                if($this->Madmin->check_procedure('DmN_Check_Acc_MD5', $account_db) != false){
                                                                    $this->Madmin->drop_procedure('DmN_Check_Acc_MD5', $account_db);
                                                                }
                                                            }
                                                            if($this->Madmin->check_if_column_exists('HWID', 'MEMB_STAT', $account_db) != false){
                                                                $this->Madmin->insert_sql_data(str_replace('dmncms', '[' . WEB_DB . ']', $procedures_info['account']['WZ_CONNECT_MEMB_MUDEVS']), $account_db);
                                                                $this->Madmin->insert_sql_data(str_replace('dmncms', '[' . WEB_DB . ']', $procedures_info['account']['WZ_DISCONNECT_MEMB_MUDEVS']), $account_db);
                                                            }
                                                            else{
                                                                $this->Madmin->insert_sql_data(str_replace('dmncms', '[' . WEB_DB . ']', $procedures_info['account']['WZ_CONNECT_MEMB']), $account_db);
                                                                $this->Madmin->insert_sql_data(str_replace('dmncms', '[' . WEB_DB . ']', $procedures_info['account']['WZ_DISCONNECT_MEMB']), $account_db);
                                                            }
                                                            
                                                            if(MD5 == 1){
                                                                $this->Madmin->insert_sql_data($procedures_info['account']['DmN_Check_Acc_MD5'], $account_db);
                                                            }
                                                        }
                                                        
                                                        $this->vars['identity_column_character'] = $this->Madmin->get_identity_column('Character', $char_db);
                                                        if($this->vars['identity_column_character'] == false){
                                                            if($this->Madmin->check_if_column_exists('id', 'Character', $char_db) == false){
                                                                $this->Madmin->add_column('id', 'Character', ['type' => 'int', 'identity' => 1, 'is_primary_key' => 0, 'null' => 0, 'default' => ''], $char_db);
                                                                $this->vars['identity_column_character']['name'] = 'id';
                                                            } else{
                                                                $this->Madmin->drop_column('id', 'Character', $char_db);
                                                                $this->Madmin->add_column('id', 'Character', ['type' => 'int', 'identity' => 1, 'is_primary_key' => 0, 'null' => 0, 'default' => ''], $char_db);
                                                                $this->vars['identity_column_character']['name'] = 'id';
                                                            }
                                                        }
                                                        $this->Madmin->dropTriggerPKCount($char_db);
                                                        $this->Madmin->createTriggerPKCount($char_db);
                                                        
                                                        $wh_size = $this->Madmin->get_wh_size($char_db);
                                                        $inv_size = $this->Madmin->get_inv_size($char_db);
                                                        $item_size = 20;
                                                        if($wh_size['length'] > 1200){
                                                            $item_size = 32;
                                                        }
                                                        if($wh_size['length'] > 3840){
                                                            $item_size = 50;
                                                        }
                                                        if($wh_size['length'] == 4800){
                                                            $item_size = 40;
                                                        }
                                                        if($wh_size['length'] > 6000){
                                                            $item_size = 64;
                                                        }
                                                        if($wh_size['length'] == 6960){
                                                            $item_size = 58;
                                                        }
                                                        $new_server = [
                                                            strtoupper($this->website->seo_string($skey)) => [
                                                                'db' => $char_db, 
                                                                'db_acc' => $account_db, 
                                                                'title' => $title, 
                                                                'visible' => 1, 
                                                                'identity_column_character' => $this->vars['identity_column_character']['name'], 
                                                                'inv_size' => $inv_size['length'], 
                                                                'wh_size' => $wh_size['length'], 
                                                                'inv_multiplier' => ($inv_size['length'] > 1728) ? 236 : 108, 
                                                                'wh_multiplier' => ($wh_size['length'] > 1920) ? 240 : 120, 
                                                                'wh_hor_size' => 8, 
                                                                'wh_ver_size' => 15, 
                                                                'item_size' => $item_size,  
                                                                'version' => $version,
                                                                'exp' => $exp, 
                                                                'drop' => $drop
                                                            ]
                                                        ];
                                                        $this->vars['server_list'] = array_merge($this->vars['server_list'], $new_server);
                                                        if(!$this->Madmin->save_server_data($this->vars['server_list'], false)){
                                                            $this->vars['error'] = 'Unable to add new server.';
                                                        } else{
                                                            $this->Madmin->copy_settings('buylevel_config', strtoupper($this->website->seo_string($skey)));
                                                            $this->Madmin->copy_settings('donation_config', strtoupper($this->website->seo_string($skey)));
                                                            $this->Madmin->copy_settings('rankings_config', strtoupper($this->website->seo_string($skey)));
                                                            $this->Madmin->copy_settings('reset_config', strtoupper($this->website->seo_string($skey)));
                                                            $this->Madmin->copy_settings('greset_config', strtoupper($this->website->seo_string($skey)));
                                                            $this->Madmin->copy_settings('table_config', strtoupper($this->website->seo_string($skey)));
                                                            $this->Madmin->copy_settings('wcoin_exchange_config', strtoupper($this->website->seo_string($skey)));
                                                            $this->vars['success'] = 'Server added successfully';
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                $this->vars['databases'] = $this->Madmin->list_databases();
                $this->load->view('admincp' . DS . 'server_manager' . DS . 'view.add_server', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }
        
        public function add_game_server(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
				
				$this->vars['server_list'] = $this->website->server_list();
				$this->vars['game_server_list'] = $this->config->values('gameserver_config');
				
                if(count($_POST) > 0){
                    $this->vars['name'] = isset($_POST['name']) ? $_POST['name'] : '';
					$this->vars['ip'] = isset($_POST['ip']) ? $_POST['ip'] : '';
					$this->vars['port'] = isset($_POST['port']) ? $_POST['port'] : '';
					$this->vars['gs_list'] = isset($_POST['gs_list']) ? $_POST['gs_list'] : '';
					$this->vars['bound_to'] = isset($_POST['bound_to']) ? $_POST['bound_to'] : '';
					$this->vars['max_online'] = (isset($_POST['max_online']) && $_POST['max_online'] != '') ? (int)$_POST['max_online'] : 1000;
					
					if($this->vars['name'] == '')
						$this->vars['error'] = 'Please enter server title'; 
					if($this->vars['ip'] == '')
						$this->vars['error'] = 'Please enter gameserver ip'; 
					if(!filter_var($this->vars['ip'], FILTER_VALIDATE_IP))
						$this->vars['error'] = 'Please enter valid gameserver ip'; 
					if($this->vars['port'] == '')
						$this->vars['error'] = 'Please enter gameserver port'; 
					if(!is_numeric($this->vars['port']) || ($this->vars['port'] < 0 || $this->vars['port'] > 65535))
						$this->vars['error'] = 'Please enter valid gameserver port';    
					if($this->vars['bound_to'] == '')
						$this->vars['error'] = 'Please select server bound to'; 
					if(!isset($this->vars['error'])){
						$newData = [
							'name' => $this->vars['name'],
							'ip' => $this->vars['ip'],
							'port' => $this->vars['port'],
							'gs_list' => $this->vars['gs_list'],
							'bound_to' => $this->vars['bound_to'],
							'max_online' => $this->vars['max_online'],
							'visible' => 1
						];
						
						array_push($this->vars['game_server_list'], $newData);

						if(!$this->Madmin->save_config_data($this->vars['game_server_list'], 'gameserver_config', false)){
							$this->vars['error'] = 'Unable to add gameserver.';
						} 
						else{
							$this->vars['success'] = 'Gameserver added successfully.';
							$_POST['name'] = $_POST['ip'] = $_POST['port'] = $_POST['gs_list'] = $_POST['max_online'] = '';
						}
					}
                }
                $this->load->view('admincp' . DS . 'server_manager' . DS . 'view.add_game_server', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }
		
		public function edit_server($server = ''){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                $this->vars['server_list'] = $this->website->server_list();
                if(!array_key_exists($server, $this->vars['server_list'])){
                    $this->vars['not_found'] = 'Server not found';
                } else{
                    $this->vars['data'] = $this->vars['server_list'][$server];
                    $this->vars['key'] = $server;
                    $this->vars['databases'] = $this->Madmin->list_databases();
                    if(count($_POST) > 0){
                        $title = isset($_POST['title']) ? $_POST['title'] : '';
                        $char_db = isset($_POST['char_db']) ? $_POST['char_db'] : '';
                        $account_db = isset($_POST['account_db']) ? $_POST['account_db'] : '';
                        $version = isset($_POST['version']) ? $_POST['version'] : '0';
                        $exp = isset($_POST['exp']) ? $_POST['exp'] : '0';
                        $drop = isset($_POST['drop']) ? $_POST['drop'] : '0';
                        if($title == '')
                            $this->vars['error'] = 'Please enter server title'; 
						else{
                            if(!preg_match("/[\w\W]/", $title))
                                $this->vars['error'] = 'Please enter valid server title'; 
							else{
                                if($char_db == '')
                                    $this->vars['error'] = 'Please select character database'; 
								else{
                                    if(!$this->Madmin->check_character($char_db))
                                        $this->vars['error'] = 'Char database does not contain any character table.'; 
									else{
                                        if($account_db == '')
                                            $this->vars['error'] = 'Please select account database'; 
										else{
                                            if(!$this->Madmin->check_memb_info($account_db))
                                                $this->vars['error'] = 'Account database does not contain any account table.'; 
											else{
                                                if($this->website->is_multiple_accounts() == true){
                                                    $this->vars['server_list'] = array_merge(['USE_MULTI_ACCOUNT_DB' => true], $this->vars['server_list']);
                                                } 
                                                else{
                                                    $this->vars['server_list'] = array_merge(['USE_MULTI_ACCOUNT_DB' => false], $this->vars['server_list']);
                                                }
                                                $this->vars['columns'] = $this->Madmin->required_columns();
                                                foreach($this->vars['columns']['account_db'] AS $key => $columns){
                                                    foreach($columns AS $col => $info){
                                                        if($this->Madmin->check_if_column_exists($col, $key, $account_db) == false){
                                                            $this->Madmin->add_column($col, $key, $info, $account_db);
                                                        }
                                                    }
                                                }
                                                foreach($this->vars['columns']['char_db'] AS $key => $columns){
                                                    foreach($columns AS $col => $info){
                                                        if($this->Madmin->check_if_column_exists($col, $key, $char_db) == false){
                                                            $this->Madmin->add_column($col, $key, $info, $char_db);
                                                        }
                                                    }
                                                }
                                                
                                                $procedureFile = BASEDIR . 'setup' . DS . 'data' . DS . 'procedures' . DS . 'required_stored_procedures[20.05.2015].json';
                                                if(file_exists($procedureFile)){
                                                    $procedures_info = json_decode(file_get_contents($procedureFile), true);
                                                    if($this->Madmin->check_procedure('WZ_CONNECT_MEMB', $account_db) != false){
                                                        $this->Madmin->drop_procedure('WZ_CONNECT_MEMB', $account_db);
                                                    }
                                                    if($this->Madmin->check_procedure('WZ_DISCONNECT_MEMB', $account_db) != false){
                                                        $this->Madmin->drop_procedure('WZ_DISCONNECT_MEMB', $account_db);
                                                    }
                                                    if(MD5 == 1){
                                                        if($this->Madmin->check_procedure('DmN_Check_Acc_MD5', $account_db) != false){
                                                            $this->Madmin->drop_procedure('DmN_Check_Acc_MD5', $account_db);
                                                        }
                                                    }
                                                    if($this->Madmin->check_if_column_exists('HWID', 'MEMB_STAT', $account_db) != false){
                                                        $this->Madmin->insert_sql_data(str_replace('dmncms', '[' . WEB_DB . ']', $procedures_info['account']['WZ_CONNECT_MEMB_MUDEVS']), $account_db);
                                                        $this->Madmin->insert_sql_data(str_replace('dmncms', '[' . WEB_DB . ']', $procedures_info['account']['WZ_DISCONNECT_MEMB_MUDEVS']), $account_db);
                                                    }
                                                    else{
                                                        $this->Madmin->insert_sql_data(str_replace('dmncms', '[' . WEB_DB . ']', $procedures_info['account']['WZ_CONNECT_MEMB']), $account_db);
                                                        $this->Madmin->insert_sql_data(str_replace('dmncms', '[' . WEB_DB . ']', $procedures_info['account']['WZ_DISCONNECT_MEMB']), $account_db);
                                                    }
                                                    
                                                    if(MD5 == 1){
                                                        $this->Madmin->insert_sql_data($procedures_info['account']['DmN_Check_Acc_MD5'], $account_db);
                                                    }
                                                }
                                                
                                                $this->vars['identity_column_character'] = $this->Madmin->get_identity_column('Character', $char_db);
                                                if($this->vars['identity_column_character'] == false){
                                                    if($this->Madmin->check_if_column_exists('id', 'Character', $char_db) == false){
                                                        $this->Madmin->add_column('id', 'Character', ['type' => 'int', 'identity' => 1, 'is_primary_key' => 0, 'null' => 0, 'default' => ''], $char_db);
                                                        $this->vars['identity_column_character']['name'] = 'id';
                                                    } else{
                                                        $this->Madmin->drop_column('id', 'Character', $char_db);
                                                        $this->Madmin->add_column('id', 'Character', ['type' => 'int', 'identity' => 1, 'is_primary_key' => 0, 'null' => 0, 'default' => ''], $char_db);
                                                        $this->vars['identity_column_character']['name'] = 'id';
                                                    }
                                                }
                                                $this->Madmin->dropTriggerPKCount($char_db);
                                                $this->Madmin->createTriggerPKCount($char_db);
                                                
                                                $wh_size = $this->Madmin->get_wh_size($char_db);
                                                $inv_size = $this->Madmin->get_inv_size($char_db);
                                                $item_size = 20;
                                                if($wh_size['length'] > 1200){
                                                    $item_size = 32;
                                                }
                                                if($wh_size['length'] > 3840){
                                                    $item_size = 50;
                                                }
                                                if($wh_size['length'] == 4800){
                                                    $item_size = 40;
                                                }
                                                if($wh_size['length'] > 6000){
                                                    $item_size = 64;
                                                }
                                                if($wh_size['length'] == 6960){
                                                    $item_size = 58;
                                                }
                                                $new_server = [
                                                    strtoupper(seo_string($server)) => [
                                                        'db' => $char_db, 
                                                        'db_acc' => $account_db, 
                                                        'title' => $title, 
                                                        'visible' => 1, 
                                                        'identity_column_character' => $this->vars['identity_column_character']['name'], 
                                                        'inv_size' => $inv_size['length'], 
                                                        'wh_size' => $wh_size['length'], 
                                                        'inv_multiplier' => ($inv_size['length'] > 1728) ? 236 : 108, 
                                                        'wh_multiplier' => ($wh_size['length'] > 1920) ? 240 : 120, 
                                                        'wh_hor_size' => 8, 
                                                        'wh_ver_size' => 15, 
                                                        'item_size' => $item_size, 
                                                        'version' => $version, 
                                                        'exp' => $exp, 
                                                        'drop' => $drop
                                                    ]
                                                ];
                                                $this->vars['server_list'] = array_merge($this->vars['server_list'], $new_server);
                                                if(!$this->Madmin->save_server_data($this->vars['server_list'])){
                                                    $this->vars['error'] = 'Unable to edit server.';
                                                } else{
                                                    $this->vars['data'] = $this->vars['server_list'][$server];
                                                    $this->vars['success'] = 'Server edited successfully';
                                                } 
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                $this->load->view('admincp' . DS . 'server_manager' . DS . 'view.edit_server', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }
        
        public function edit_game_server($id = ''){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
				
				$this->vars['server_list'] = $this->website->server_list();
				$this->vars['game_server_list'] = $this->config->values('gameserver_config');
				
                if(array_key_exists($id, $this->vars['game_server_list'])){
					foreach($this->vars['game_server_list'][$id] AS $k => $v){
						$this->vars[$k] = $v;
					}
					if(count($_POST) > 0){
						$this->vars['name'] = isset($_POST['name']) ? $_POST['name'] : '';
						$this->vars['ip'] = isset($_POST['ip']) ? $_POST['ip'] : '';
						$this->vars['port'] = isset($_POST['port']) ? $_POST['port'] : '';
						$this->vars['gs_list'] = isset($_POST['gs_list']) ? $_POST['gs_list'] : '';
						$this->vars['bound_to'] = isset($_POST['bound_to']) ? $_POST['bound_to'] : '';
						$this->vars['max_online'] = (isset($_POST['max_online']) && $_POST['max_online'] != '') ? (int)$_POST['max_online'] : 1000;
						$this->vars['visible'] = (isset($_POST['visible']) && $_POST['visible'] != '') ? (int)$_POST['visible'] : 1;
						
						if($this->vars['name'] == '')
							$this->vars['error'] = 'Please enter server title'; 
						if($this->vars['ip'] == '')
							$this->vars['error'] = 'Please enter gameserver ip'; 
						if(!filter_var($this->vars['ip'], FILTER_VALIDATE_IP))
							$this->vars['error'] = 'Please enter valid gameserver ip'; 
						if($this->vars['port'] == '')
							$this->vars['error'] = 'Please enter gameserver port'; 
						if(!is_numeric($this->vars['port']) || ($this->vars['port'] < 0 || $this->vars['port'] > 65535))
							$this->vars['error'] = 'Please enter valid gameserver port';    
						if($this->vars['bound_to'] == '')
							$this->vars['error'] = 'Please select server bound to'; 
						if(!isset($this->vars['error'])){
							$this->vars['game_server_list'][$id] = [
								'name' => $this->vars['name'],
								'ip' => $this->vars['ip'],
								'port' => $this->vars['port'],
								'gs_list' => $this->vars['gs_list'],
								'bound_to' => $this->vars['bound_to'],
								'max_online' => $this->vars['max_online'],
								'visible' => $this->vars['visible']
							];

							if(!$this->Madmin->save_config_data($this->vars['game_server_list'], 'gameserver_config', false)){
								$this->vars['error'] = 'Unable to edit gameserver.';
							} 
							else{
								$this->vars['success'] = 'Gameserver edited successfully.';
							}
						}
					}
				}
				else{
					$this->vars['error'] = 'GameServer not found'; 
				}
                $this->load->view('admincp' . DS . 'server_manager' . DS . 'view.edit_game_server', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }

		public function manage_plugins(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                $this->load_header();
                $this->vars['plugin_list'] = $this->Madmin->get_plugin_list();
                $this->vars['plugin_config'] = $this->config->values('plugin_config');
                foreach($this->vars['plugin_list'] AS $key => $plugin){
                    if(!array_key_exists($plugin, $this->vars['plugin_config'])){    
                        $about_file = APP_PATH . DS . 'plugins' . DS . $plugin . DS . 'about.json';
                        if(file_exists($about_file)){
                            $this->vars['available_data'][$plugin] = ['installed' => 0, 'about' => json_decode(file_get_contents($about_file), true)];
                        } else{
                            $this->vars['available_data'][$plugin] = ['installed' => 0, 'about' => ['name' => $plugin, 'version' => false, 'description' => false, 'developed_by' => false, 'website' => false, 'update_url' => false]];
                        }
                    }
                }
                $this->load->view('admincp' . DS . 'plugin_manager' . DS . 'view.index', $this->vars);
                $this->load_footer();
            } else{
                $this->login();
            }
        }
		
		public function release_license(){
            if($this->session->userdata(['admin' => 'is_admin'])){
                if($this->session->userdata(['admin' => 'username']) == 'demo_admin'){
                    throw new Exception('Demo account does not have permission for this action.');
                } else{
                    if($this->license->release_license()){
                        header('Location: ' . $this->config->base_url);
                    }
                }
            } else{
                $this->login();
            }
        }

        private function load_header(){
            $this->load->view('admincp' . DS . 'view.header', $this->vars);
            $this->load->view('admincp' . DS . 'view.sidebar', $this->vars);
        }

        private function load_footer(){
            $this->load->view('admincp' . DS . 'view.footer', $this->vars);
        }
    }
