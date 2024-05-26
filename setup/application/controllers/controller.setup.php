<?php
    in_file();

    class setup extends controller
    {
        protected $vars = [], $errors = [];
        private $after_install_key = 'a953feaec195bba04c142bc38ec283df';
        
        
        public function __construct(){
            parent::__construct();
            $this->load->model('setup/application/models/setup');
            $this->check_lock();
        }
        
        public function index(){
            $this->Msetup->get_extension_data();
            $this->Msetup->check_writable_files_folders();
            if((isset($this->Msetup->vars['extensionsOK']) && $this->Msetup->vars['extensionsOK'] == false) || (isset($this->Msetup->vars['filesOK']) && $this->Msetup->vars['filesOK'] == false)){
                $_SESSION['allow_step_2'] = false;
            } else{
                $_SESSION['allow_step_2'] = true;
            }
            $this->load->view('setup' . DS . 'application' . DS . 'views' . DS . 'setup' . DS . 'view.step1', $this->vars);
        }
        
        
        public function step2(){
            $_SESSION['allow_step_3'] = true;
            header('Location: ' . $this->config->base_url . 'index.php?action=setup/step3');
        }
        
        
        public function step3(){
            if(defined('HOST')){
                if(preg_match('/,|:/', HOST, $matches)){
                    list($this->vars['ip'], $this->vars['port']) = explode($matches[0], HOST);
                } else{
                    $this->vars['ip'] = HOST;
                }
            }
            if(isset($_SESSION['allow_step_2']) && $_SESSION['allow_step_2'] == false){
                $this->vars['errors'][] = 'Please complete step 1 before continue.';
            }
            if(!isset($_SESSION['allow_step_3']) || $_SESSION['allow_step_3'] == false){
                $this->vars['errors'][] = 'Please complete step 2 before continue.';
            }
            if(count($_POST) > 0){
                $this->vars['sql_host'] = isset($_POST['sql_host']) ? $_POST['sql_host'] : '';
                $this->vars['sql_port'] = isset($_POST['sql_port']) ? trim($_POST['sql_port']) : '';
                $this->vars['sql_user'] = isset($_POST['sql_user']) ? $_POST['sql_user'] : '';
                $this->vars['sql_pass'] = isset($_POST['sql_pass']) ? $_POST['sql_pass'] : '';
                $this->vars['sql_web_db'] = isset($_POST['sql_web_db']) ? $_POST['sql_web_db'] : '';
                $this->vars['sql_driver'] = isset($_POST['sql_driver']) ? $_POST['sql_driver'] : '';
                if($this->vars['sql_host'] == ''){
                    $this->vars['sql_errors'][] = 'Please enter sql server host address';
                }
                if($this->vars['sql_user'] == ''){
                    $this->vars['sql_errors'][] = 'Please enter sql server user';
                }
                if($this->vars['sql_pass'] == ''){
                    $this->vars['sql_errors'][] = 'Please enter sql server user password';
                }
                if($this->vars['sql_web_db'] == ''){
                    $this->vars['sql_errors'][] = 'Please enter website database name';
                }
                if($this->vars['sql_driver'] == ''){
                    $this->vars['sql_errors'][] = 'Please select sql server connection extension';
                }
                if($this->vars['sql_port'] != ''){
                    if(preg_match('/[0-9]{1,5}/', $this->vars['sql_port'])){
                        $this->vars['sql_host'] .= (strtolower(substr(php_uname(), 0, 7)) == 'windows') ? ',' . $this->vars['sql_port'] : ':' . $this->vars['sql_port'];
                    } else{
                        $this->vars['sql_errors'][] = 'Please enter valid port number';
                    }
                }
                if(!extension_loaded($this->vars['sql_driver'])){
                    $this->vars['sql_errors'][] = 'You are missing ' . $this->vars['sql_driver'] . ' extension. Please select different or enable this in your php settings.';
                }
                if(!isset($this->vars['sql_errors']) || count($this->vars['sql_errors']) <= 0){
                    $this->load->lib('DBEngines/' . $this->vars['sql_driver'], [$this->vars['sql_host'], $this->vars['sql_user'], $this->vars['sql_pass'], 'master'], 'master_db');
                    if(!empty($this->master_db->error)){
                        $this->vars['sql_errors'][] = $this->master_db->error;
                    } 
                    else{
                        $_SESSION['db'] = ['host' => $this->vars['sql_host'], 'user' => $this->vars['sql_user'], 'pass' => $this->vars['sql_pass'], 'web_db' => $this->vars['sql_web_db'], 'driver' => $this->vars['sql_driver']];
                        $_SESSION['allow_step_4'] = true;
                        if(!$this->Msetup->db_exists($this->vars['sql_web_db'])){
                            if($this->Msetup->create_database($this->vars['sql_web_db'], $this->vars['sql_user'])){
                                header('Location: ' . $this->config->base_url . 'index.php?action=setup/step4');
                            } 
                            else{
                                $this->vars['sql_errors'][] = 'Unable to create database ' . htmlspecialchars($this->vars['sql_web_db']) . ', please create it manually & refresh page.';
                            }
                        } 
                        else{
                            header('Location: ' . $this->config->base_url . 'index.php?action=setup/step4');
                        }
                    }
                }
            }
            $this->load->view('setup' . DS . 'application' . DS . 'views' . DS . 'setup' . DS . 'view.step3', $this->vars);
        }

        public function step4(){
            if(isset($_SESSION['allow_step_2']) && $_SESSION['allow_step_2'] == false){
                $this->vars['errors'][] = 'Please complete step 1 before continue.';
            }
            if(!isset($_SESSION['allow_step_3']) || $_SESSION['allow_step_3'] == false){
                $this->vars['errors'][] = 'Please complete step 2 before continue.';
            }
            if(!isset($_SESSION['allow_step_4']) || $_SESSION['allow_step_4'] == false){
                $this->vars['errors'][] = 'Please complete step 3 before continue.';
            }
            $this->load->lib('DBEngines/' .  $_SESSION['db']['driver'], [$_SESSION['db']['host'], $_SESSION['db']['user'], $_SESSION['db']['pass'], $_SESSION['db']['web_db']], 'web_db');
            $this->vars['dbs'] = $this->Msetup->list_databases();
            if(count($_POST) > 0){
                $this->vars['acc_db'] = isset($_POST['acc_db']) ? $_POST['acc_db'] : '';
                $this->vars['char_db'] = isset($_POST['char_db']) ? $_POST['char_db'] : '';
                $this->load->lib('DBEngines/' .  $_SESSION['db']['driver'], [$_SESSION['db']['host'], $_SESSION['db']['user'], $_SESSION['db']['pass'], $this->vars['acc_db']], 'account_db');
                $this->load->lib('DBEngines/' .  $_SESSION['db']['driver'], [$_SESSION['db']['host'], $_SESSION['db']['user'], $_SESSION['db']['pass'], $this->vars['char_db']], 'game_db');
                if(!$this->Msetup->check_memb_info()){
                    $this->vars['error'] = 'Please select different database for accounts.';
                } else{
                    if(!$this->Msetup->check_character()){
                        $this->vars['error'] = 'Please select different database for characters.';
                    } else{
                        $_SESSION['db']['acc_db'] = $this->vars['acc_db'];
                        $_SESSION['db']['char_db'] = $this->vars['char_db'];
                        $_SESSION['allow_step_5'] = true;
                        header('Location: ' . $this->config->base_url . 'index.php?action=setup/step5');
                    }
                }
            }
            $this->load->view('setup' . DS . 'application' . DS . 'views' . DS . 'setup' . DS . 'view.step4', $this->vars);
        }

        public function step5(){
            if(isset($_SESSION['allow_step_2']) && $_SESSION['allow_step_2'] == false){
                $this->vars['errors'][] = 'Please complete step 1 before continue.';
            }
            if(!isset($_SESSION['allow_step_3']) || $_SESSION['allow_step_3'] == false){
                $this->vars['errors'][] = 'Please complete step 2 before continue.';
            }
            if(!isset($_SESSION['allow_step_4']) || $_SESSION['allow_step_4'] == false){
                $this->vars['errors'][] = 'Please complete step 3 before continue.';
            }
            if(!isset($_SESSION['allow_step_5']) || $_SESSION['allow_step_5'] == false){
                $this->vars['errors'][] = 'Please complete step 4 before continue.';
            }
            $_SESSION['allow_step_9'] = true;
            $this->vars['first_version'] = $this->Msetup->first_cms_version();
            $this->load->view('setup' . DS . 'application' . DS . 'views' . DS . 'setup' . DS . 'view.step5', $this->vars);
        }
		
		private function check_steps_ajax(){
								
            if(isset($_SESSION['allow_step_2']) && $_SESSION['allow_step_2'] == false){
                echo json_encode(['error' => 'Please complete step 1 before continue.']);
                return;
            }
            if(!isset($_SESSION['allow_step_3']) || $_SESSION['allow_step_3'] == false){
                echo json_encode(['error' => 'Please complete step 2 before continue.']);
                return;
            }
            if(!isset($_SESSION['allow_step_4']) || $_SESSION['allow_step_4'] == false){
                echo json_encode(['error' => 'Please complete step 3 before continue.']);
                return;
            }
            if(!isset($_SESSION['allow_step_5']) || $_SESSION['allow_step_5'] == false){
                echo json_encode(['error' => 'Please complete step 4 before continue.']);
                return;
            }
        }

        public function step6(){
            $this->check_steps_ajax();							  
            if(!isset($_POST['mu_version']) || $_POST['mu_version'] == -1){
                echo json_encode(['error' => 'Please select your mu server version.']);
                return;
            }
            if(isset($_POST['submit_sql_data']) && isset($_POST['version'])){
                $_SESSION['overwrite_old_tables'] = isset($_POST['overwrite_old_tables']) ? 1 : 0;
                $_SESSION['insert_sql_data'] = isset($_POST['insert_sql_data']) ? 1 : 0;
                $_SESSION['setup_versions'] = $this->Msetup->get_all_cms_versions();
                $_SESSION['setup_mu_versions'] = $_POST['mu_version'];
                $this->add_tables($_POST['version']);
            }
        }
        
        
        public function add_tables($version){
            if(array_key_exists($version, $_SESSION['setup_versions'])){
                $version_data = $_SESSION['setup_versions'][$version];
                if(is_array($version_data)){
                    $table_file = INSTALL_DIR . 'data' . DS . 'tables' . DS . 'required_tables[' . $version_data[key($version_data)] . '].json';
                    $date = $_SESSION['setup_versions'][$version][key($version_data)];
                    if(file_exists($table_file)){
                        $tables_info = json_decode(file_get_contents($table_file), true);
                        $this->add_sql_tables($tables_info, $_SESSION['overwrite_old_tables']);
                        unset($_SESSION['setup_versions'][$version][key($version_data)]);
                        if(count($_SESSION['setup_versions'][$version]) > 0){
                            echo json_encode(['step5_5' => 1, 'version' => $version, 'date' => $date, 'type' => 'minor']);
                            return;
                        } else{
                            unset($_SESSION['setup_versions'][$version]);
                            $version = key($_SESSION['setup_versions']);
                            echo json_encode(['step5_5' => 1, 'version' => $version, 'date' => $date, 'type' => 'minor']);
                            return;
                        }
                    } else{
                        unset($_SESSION['setup_versions'][$version][key($version_data)]);
                        if(count($_SESSION['setup_versions'][$version]) > 0){
                            echo json_encode(['step5_5' => 1, 'version' => $version, 'date' => $date, 'type' => 'minor']);
                            return;
                        } else{
                            unset($_SESSION['setup_versions'][$version]);
                        }
                    }
                } else{
                    $table_file = INSTALL_DIR . 'data' . DS . 'tables' . DS . 'required_tables[' . $version_data . '].json';
                    $date = $_SESSION['setup_versions'][$version];
                    if(file_exists($table_file)){
                        $tables_info = json_decode(file_get_contents($table_file), true);
                        $this->add_sql_tables($tables_info, $_SESSION['overwrite_old_tables']);
                        unset($_SESSION['setup_versions'][$version]);
                    } else{
                        unset($_SESSION['setup_versions'][$version]);
                    }
                }
                if(count($_SESSION['setup_versions']) > 0){
                    $version = key($_SESSION['setup_versions']);
                    if(is_array($_SESSION['setup_versions'][$version])){
                        $date = $_SESSION['setup_versions'][$version][key($_SESSION['setup_versions'][$version])];
                    } else{
                        $date = $_SESSION['setup_versions'][$version];
                    }
                    echo json_encode(['step5_5' => 1, 'version' => $version, 'date' => $date, 'type' => 'major']);
                    return;
                }
            } else{
                if(count($_SESSION['setup_versions']) > 0){
                    $version = key($_SESSION['setup_versions']);
                    if(is_array($_SESSION['setup_versions'][$version])){
                        $date = $_SESSION['setup_versions'][$version][key($_SESSION['setup_versions'][$version])];
                    } else{
                        $date = $_SESSION['setup_versions'][$version];
                    }
                    echo json_encode(['step5_5' => 1, 'version' => $version, 'date' => $date, 'type' => 'major']);
                    return;
                }
            }
            echo json_encode(['step6' => 1, 'progress' => '60%', 'message' => 'SQL Table Adding Completed']);
        }
        
        
        private function add_sql_tables($tables_info, $overwrite_old_tables){
            if(is_array($tables_info) && count($tables_info) > 0){
                $this->load->lib('DBEngines/' .  $_SESSION['db']['driver'], [$_SESSION['db']['host'], $_SESSION['db']['user'], $_SESSION['db']['pass'], $_SESSION['db']['web_db']], 'web_db');
                $this->load->lib('DBEngines/' .  $_SESSION['db']['driver'], [$_SESSION['db']['host'], $_SESSION['db']['user'], $_SESSION['db']['pass'], $_SESSION['db']['acc_db']], 'account_db');
                $this->load->lib('DBEngines/' .  $_SESSION['db']['driver'], [$_SESSION['db']['host'], $_SESSION['db']['user'], $_SESSION['db']['pass'], $_SESSION['db']['char_db']], 'game_db');
                
                foreach($tables_info AS $key => $table){
                    if($overwrite_old_tables == 0){
                        if($this->Msetup->check_if_table_exists($key, $table['db']) == false){
                            $this->Msetup->run_query($table['query'], $table['db']);
                        }
                    } else{
                        if($this->Msetup->check_if_table_exists($key, $table['db']) != false){
                            $this->Msetup->drop_table($key, $table['db']);
                        }
                        $this->Msetup->run_query($table['query'], $table['db']);
                    }
                }
            }
        }

        public function step7(){
            $this->check_steps_ajax();
            $_SESSION['setup_versions'] = $this->Msetup->get_all_cms_versions();
            $this->add_columns(key($_SESSION['setup_versions']));
        }
        
        
        public function add_columns($version){
            $this->check_steps_ajax();
            if(array_key_exists($version, $_SESSION['setup_versions'])){
                $version_data = $_SESSION['setup_versions'][$version];
                if(is_array($version_data)){
                    $column_file = INSTALL_DIR . 'data' . DS . 'columns' . DS . 'required_columns[' . $version_data[key($version_data)] . '].json';
                    $date = $_SESSION['setup_versions'][$version][key($version_data)];
                    if(file_exists($column_file)){
                        $column_info = json_decode(file_get_contents($column_file), true);
                        $this->add_sql_columns($column_info);
                        unset($_SESSION['setup_versions'][$version][key($version_data)]);
                        if(count($_SESSION['setup_versions'][$version]) > 0){
                            echo json_encode(['step6_5' => 1, 'version' => $version, 'date' => $date, 'type' => 'minor']);
                            exit;
                        } else{
                            unset($_SESSION['setup_versions'][$version]);
                            $version = key($_SESSION['setup_versions']);
                            echo json_encode(['step6_5' => 1, 'version' => $version, 'date' => $date, 'type' => 'minor']);
                            exit;
                        }
                    } else{
                        unset($_SESSION['setup_versions'][$version][key($version_data)]);
                        if(count($_SESSION['setup_versions'][$version]) > 0){
                            echo json_encode(['step6_5' => 1, 'version' => $version, 'date' => $date, 'type' => 'minor']);
                            exit;
                        } else{
                            unset($_SESSION['setup_versions'][$version]);
                        }
                    }
                } else{
                    $column_file = INSTALL_DIR . 'data' . DS . 'columns' . DS . 'required_columns[' . $version_data . '].json';
                    $date = $_SESSION['setup_versions'][$version];
                    if(file_exists($column_file)){
                        $column_info = json_decode(file_get_contents($column_file), true);
                        $this->add_sql_columns($column_info);
                    }
                    unset($_SESSION['setup_versions'][$version]);
                }
                if(count($_SESSION['setup_versions']) > 0){
                    $version = key($_SESSION['setup_versions']);
                    if(is_array($_SESSION['setup_versions'][$version])){
                        $date = $_SESSION['setup_versions'][$version][key($_SESSION['setup_versions'][$version])];
                    } else{
                        $date = $_SESSION['setup_versions'][$version];
                    }
                    echo json_encode(['step6_5' => 1, 'version' => $version, 'date' => $date, 'type' => 'major']);
                    exit;
                }
            } else{
                if(count($_SESSION['setup_versions']) > 0){
                    $version = key($_SESSION['setup_versions']);
                    if(is_array($_SESSION['setup_versions'][$version])){
                        $date = $_SESSION['setup_versions'][$version][key($_SESSION['setup_versions'][$version])];
                    } else{
                        $date = $_SESSION['setup_versions'][$version];
                    }
                    echo json_encode(['step6_5' => 1, 'version' => $version, 'date' => $date, 'type' => 'major']);
                    exit;
                }
            }
            echo json_encode(['step7' => 1, 'progress' => '70%', 'message' => 'SQL Column Adding Completed']);
        }
        
        
        private function add_sql_columns($columns_info){
            if(is_array($columns_info) && count($columns_info) > 0){
				set_time_limit(0);				  
                $this->load->lib('DBEngines/' .  $_SESSION['db']['driver'], [$_SESSION['db']['host'], $_SESSION['db']['user'], $_SESSION['db']['pass'], $_SESSION['db']['web_db']], 'web_db');
                $this->load->lib('DBEngines/' .  $_SESSION['db']['driver'], [$_SESSION['db']['host'], $_SESSION['db']['user'], $_SESSION['db']['pass'], $_SESSION['db']['acc_db']], 'account_db');
                $this->load->lib('DBEngines/' .  $_SESSION['db']['driver'], [$_SESSION['db']['host'], $_SESSION['db']['user'], $_SESSION['db']['pass'], $_SESSION['db']['char_db']], 'game_db');
               
                foreach($columns_info AS $db => $table_data){
                    if(array_key_exists('web', $columns_info)){
                        foreach($columns_info['web'] AS $table => $columns){
                            foreach($columns AS $col => $info){
                                $web_column_info = $this->Msetup->check_if_column_exists($col, $table, 'web');
                                if($web_column_info == null || $web_column_info == false){
                                    $this->Msetup->add_column($col, $table, $info, 'web');
                                    $this->vars['inserted_columns'][] = $col;
                                }
                            }
                        }
                    }
                    if(array_key_exists('account', $columns_info)){
                        foreach($columns_info['account'] AS $table => $columns){
                            foreach($columns AS $col => $info){
                                $account_column_info = $this->Msetup->check_if_column_exists($col, $table, 'account');
                                if($account_column_info == null || $account_column_info == false){
                                    $this->Msetup->add_column($col, $table, $info, 'account');
                                    $this->vars['inserted_columns'][] = $col;
                                }
                            }
                        }
                    }
                    if(array_key_exists('game', $columns_info)){
                        foreach($columns_info['game'] AS $table => $columns){
                            foreach($columns AS $col => $info){
                                $game_column_info = $this->Msetup->check_if_column_exists($col, $table, 'game');
                                if($game_column_info == null || $game_column_info == false){
                                    $this->Msetup->add_column($col, $table, $info, 'game');
                                    $this->vars['inserted_columns'][] = $col;
                                }
                            }
                        }
                    }
                }
            }
        }

        public function step8(){
            $this->check_steps_ajax();
            set_time_limit(0);
            $this->load->lib('DBEngines/' .  $_SESSION['db']['driver'], [$_SESSION['db']['host'], $_SESSION['db']['user'], $_SESSION['db']['pass'], $_SESSION['db']['web_db']], 'web_db');   
            
            $sql_data = json_decode(file_get_contents(INSTALL_DIR . 'data' . DS . 'inserts' . DS . 'required_sql_data[20.05.2015].json'), true);
            if($_SESSION['insert_sql_data'] == 1){
                if(is_array($sql_data['DmN_Shopp'])){
                    $this->Msetup->delete_data('DmN_Shopp');
                    foreach($sql_data['DmN_Shopp'] AS $key => $data){
                        $this->Msetup->insert_sql_data($data, 'web');
                    }
                }
                $this->Msetup->delete_data('DmN_Shop_Harmony');
                $this->Msetup->insert_sql_data($sql_data['DmN_Shop_Harmony'], 'web');
                $this->Msetup->delete_data('DmN_Shop_Sockets');
                $this->Msetup->insert_sql_data($sql_data['DmN_Shop_Sockets'], 'web');
            }
            echo json_encode(['step8' => 1, 'progress' => '80%', 'message' => 'SQL Table Data Insert Completed']);
        }
        
        
        public function step9(){
			$this->check_steps_ajax();						  
            set_time_limit(0);
            $this->load->lib('DBEngines/' .  $_SESSION['db']['driver'], [$_SESSION['db']['host'], $_SESSION['db']['user'], $_SESSION['db']['pass'], $_SESSION['db']['web_db']], 'web_db');
            $this->load->lib('DBEngines/' .  $_SESSION['db']['driver'], [$_SESSION['db']['host'], $_SESSION['db']['user'], $_SESSION['db']['pass'], $_SESSION['db']['acc_db']], 'account_db');    
            
            $procedures_info = json_decode(file_get_contents(INSTALL_DIR . 'data' . DS . 'procedures' . DS . 'required_stored_procedures[20.05.2015].json'), true);
            if(is_array($procedures_info) && !empty($procedures_info)){
                if($this->Msetup->check_procedure('Add_Credits', 'web') != false){
                    $this->Msetup->drop_procedure('Add_Credits', 'web');
                }
                if($this->Msetup->check_procedure('WZ_CONNECT_MEMB', 'account') != false){
                    $this->Msetup->drop_procedure('WZ_CONNECT_MEMB', 'account');
                }
                if($this->Msetup->check_procedure('WZ_DISCONNECT_MEMB', 'account') != false){
                    $this->Msetup->drop_procedure('WZ_DISCONNECT_MEMB', 'account');
                }
				$this->vars['md5'] = $this->Msetup->get_md5();
				if($this->vars['md5']){
					if($this->Msetup->check_procedure('DmN_Check_Acc_MD5', 'account') != false){
						$this->Msetup->drop_procedure('DmN_Check_Acc_MD5', 'account');
					}
				}
                $this->Msetup->insert_sql_data($procedures_info['web']['Add_Credits'], 'web');
				
				
				if($this->Msetup->check_if_column_exists('HWID', 'MEMB_STAT', 'account') != false){
					$this->Msetup->insert_sql_data(str_replace('dmncms', '[' . $_SESSION['db']['web_db'] . ']', $procedures_info['account']['WZ_CONNECT_MEMB_MUDEVS']), 'account');
                    $this->Msetup->insert_sql_data(str_replace('dmncms', '[' . $_SESSION['db']['web_db'] . ']', $procedures_info['account']['WZ_DISCONNECT_MEMB_MUDEVS']), 'account');
				}
				else{
					$this->Msetup->insert_sql_data(str_replace('dmncms', '[' . $_SESSION['db']['web_db'] . ']', $procedures_info['account']['WZ_CONNECT_MEMB']), 'account');
                    $this->Msetup->insert_sql_data(str_replace('dmncms', '[' . $_SESSION['db']['web_db'] . ']', $procedures_info['account']['WZ_DISCONNECT_MEMB']), 'account');
                }
                
                if($this->vars['md5']){
					$this->Msetup->insert_sql_data($procedures_info['account']['DmN_Check_Acc_MD5'], 'account');
				}
            }
            echo json_encode(['step9' => 1, 'progress' => '85%', 'message' => 'SQL Stored Procedures Adding Completed. Redirecting...', 'redirect' => $this->config->base_url . 'index.php?action=setup/step10']);
        }

        public function step10(){					
            if(isset($_SESSION['allow_step_2']) && $_SESSION['allow_step_2'] == false){
                $this->vars['errors'][] = 'Please complete step 1 before continue.';
            }
            if(!isset($_SESSION['allow_step_3']) || $_SESSION['allow_step_3'] == false){
                $this->vars['errors'][] = 'Please complete step 2 before continue.';
            }
            if(!isset($_SESSION['allow_step_4']) || $_SESSION['allow_step_4'] == false){
                $this->vars['errors'][] = 'Please complete step 3 before continue.';
            }
            if(!isset($_SESSION['allow_step_5']) || $_SESSION['allow_step_5'] == false){
                $this->vars['errors'][] = 'Please complete step 4 before continue.';
            }
            if(!isset($_SESSION['allow_step_9']) || $_SESSION['allow_step_9'] == false){
                $this->vars['errors'][] = 'Please complete step 5 before continue.';
            }
            $this->load->lib('DBEngines/' .  $_SESSION['db']['driver'], [$_SESSION['db']['host'], $_SESSION['db']['user'], $_SESSION['db']['pass'], $_SESSION['db']['web_db']], 'web_db');
            $this->load->lib('DBEngines/' .  $_SESSION['db']['driver'], [$_SESSION['db']['host'], $_SESSION['db']['user'], $_SESSION['db']['pass'], $_SESSION['db']['acc_db']], 'account_db');
            $this->load->lib('DBEngines/' .  $_SESSION['db']['driver'], [$_SESSION['db']['host'], $_SESSION['db']['user'], $_SESSION['db']['pass'], $_SESSION['db']['char_db']], 'game_db');    
            
            if(count($_POST) > 0){
                $this->vars['admin_user'] = isset($_POST['username']) ? $_POST['username'] : '';
                $this->vars['admin_pass'] = isset($_POST['password']) ? $_POST['password'] : '';
                $this->vars['admin_pincode'] = isset($_POST['pincode']) ? $_POST['pincode'] : '';
                if($this->vars['admin_user'] == ''){
                    $this->vars['error'] = 'Please enter admin account username';
                } else{
                    if($this->vars['admin_pass'] == ''){
                        $this->vars['error'] = 'Please enter admin account password';
                    } else{
                        if($this->vars['admin_pincode'] == ''){
                            $this->vars['error'] = 'Please enter admin account pincode';
                        } else{
                            if(!preg_match('/[0-9]{6}/', $this->vars['admin_pincode'])){
                                $this->vars['error'] = 'Pincode is wrong, please enter 6 random digits';
                            } else{
                                $this->vars['wh_size'] = $this->Msetup->get_wh_size()['length'];
                                $this->vars['inv_size'] = $this->Msetup->get_inv_size()['length'];
                                $this->vars['md5'] = $this->Msetup->get_md5();
                                $this->vars['identity_column_character'] = $this->Msetup->get_identity_column('Character', 'game');
                                if($this->vars['identity_column_character'] == false){
                                    if($this->Msetup->check_if_column_exists('id', 'Character', 'game') == false){
                                        $this->Msetup->add_column('id', 'Character', ['type' => 'int', 'identity' => 1, 'is_primary_key' => 0, 'null' => 0, 'default' => ''], 'game');
                                        $this->vars['identity_column_character']['name'] = 'id';
                                    } else{
                                        $this->Msetup->drop_column('id', 'Character', 'game');
                                        $this->Msetup->add_column('id', 'Character', ['type' => 'int', 'identity' => 1, 'is_primary_key' => 0, 'null' => 0, 'default' => ''], 'game');
                                        $this->vars['identity_column_character']['name'] = 'id';
                                    }
                                }
								$this->Msetup->dropTriggerPKCount('game');
								$this->Msetup->createTriggerPKCount('game');
                                if($this->write_config()){
									$this->create_localization_list();
                                    $this->create_social_list();
                                    $this->create_meta_list();
									$this->create_cms_config();
                                    header('Location: ' . $this->config->base_url . 'index.php?action=setup/completed');
                                } else{
                                    $this->vars['error'] = 'Unable to write configuration file.';
                                }
                            }
                        }
                    }
                }
            }
            $this->load->view('setup' . DS . 'application' . DS . 'views' . DS . 'setup' . DS . 'view.step10', $this->vars);
        }

        public function completed(){
            session_destroy();
            setcookie("dmn_language", "", 1);
            $this->create_lock();
            $this->load->view('setup' . DS . 'application' . DS . 'views' . DS . 'setup' . DS . 'view.completed', $this->vars);
        }
    
        
        private function write_config(){
            $data = "<?PHP\r\n";
            $data .= "\r\n";
            $data .= "\tdefine('DMNCMS',		true);\r\n";
            $data .= "\tdefine('DS',			DIRECTORY_SEPARATOR);\r\n";
            $data .= "\tdefine('BASEDIR',		realpath(dirname(__FILE__)).DS);\r\n";
            $data .= "\tdefine('SYSTEM_PATH',	BASEDIR.'system');\r\n";
            $data .= "\tdefine('APP_PATH',		BASEDIR.'application');\r\n";
			$data .= "\tdefine('DATE_FORMAT',	'd-m-Y');\r\n";
			$data .= "\tdefine('DATETIME_FORMAT',		'd-m-Y H:i:s');\r\n";
            $data .= "\tdefine('INSTALLED',		true);\r\n";
            $data .= "\r\n";
            $data .= "\r\n";
            $data .= "\t/*\r\n";					
            $data .= "\t *---------------------------------------------------------------\r\n";
            $data .= "\t * Sql Server-Configuration\r\n";
            $data .= "\t *---------------------------------------------------------------\r\n";
            $data .= "\t *\r\n";
            $data .= "\t *     The following constants define the logins which should be used to access the database.\r\n";
            $data .= "\t *\r\n";
            $data .= "\t */\r\n";
            $data .= "\r\n";
            $data .= "\tdefine('HOST',		'" . $_SESSION['db']['host'] . "');\r\n";
            $data .= "\tdefine('USER',		'" . $_SESSION['db']['user'] . "');\r\n";
            $data .= "\tdefine('PASS',		'" . $_SESSION['db']['pass'] . "');\r\n";
            $data .= "\tdefine('WEB_DB',	'" . $_SESSION['db']['web_db'] . "');\r\n";
            $data .= "\tdefine('PAGE_START', microtime(true));\r\n";
            $data .= "\tdefine('LOG_SQL',	false);\r\n";
            $data .= "\tdefine('DRIVER', 	'" . strtolower($_SESSION['db']['driver']) . "');\r\n";
            $data .= "\tdefine('MD5',		" . $this->vars['md5'] . ");\r\n";
            $data .= "\tdefine('SOCKET_LIBRARY',1);\r\n";
            $data .= "\tdefine('ENVIRONMENT', 'production');\r\n";
            $data .= "\r\n";
            $data .= "\r\n";
            $data .= "\t/*\r\n";
            $data .= "\t *---------------------------------------------------------------\r\n";
            $data .= "\t * Mu Server Version\r\n";
            $data .= "\t *---------------------------------------------------------------\r\n";
            $data .= "\t *\r\n";
            $data .= "\t *     Define MuOnline Server Version\r\n";
            $data .= "\t * 		- version 0 - below season 1\r\n";
            $data .= "\t * 		- version 1 - season 1\r\n";
            $data .= "\t * 		- version 2 - season 2 and higher\r\n";
            $data .= "\t * 		- version 3 - ex700 and higher\r\n";
            $data .= "\t * 		- version 4 - season 8 and higher\r\n";
            $data .= "\t * 		- version 5 - season 10 and higher\r\n";
            $data .= "\t * 		- version 6 - season 11 and higher\r\n";
            $data .= "\t * 		- version 7 - season 12 and higher\r\n";
            $data .= "\t * 		- version 8 - season 13 and higher\r\n";
            $data .= "\t * 		- version 9 - season 14 and higher\r\n";
			$data .= "\t * 		- version 10 - season 15 and higher\r\n";
			$data .= "\t * 		- version 11 - season 16 and higher\r\n";
			$data .= "\t * 		- version 12 - season 17 and higher\r\n";
            $data .= "\t * 		- version 13 - season 18 and higher\r\n";
            $data .= "\t * 		- version 14 - season 19 and higher\r\n";
            $data .= "\t *\r\n";
            $data .= "\t */\r\n";
            $data .= "\r\n";
            $data .= "\tdefine('MU_VERSION',		" . $_SESSION['setup_mu_versions'] . ");\r\n";
            $data .= "\r\n";
            $data .= "\r\n";
            $data .= "\t/*\r\n";
            $data .= "\t *---------------------------------------------------------------\r\n";
            $data .= "\t * Admin CP\r\n";
            $data .= "\t *---------------------------------------------------------------\r\n";
            $data .= "\t *\r\n";
            $data .= "\t */\r\n";
            $data .= "\r\n";
            $data .= "\tdefine('USERNAME',	'" . $this->vars['admin_user'] . "');\r\n";
            $data .= "\tdefine('PASSWORD', 	'" . $this->vars['admin_pass'] . "');\r\n";
            $data .= "\tdefine('PINCODE', 	'" . $this->vars['admin_pincode'] . "');\r\n";
            $data .= "\tdefine('SECURITY_SALT','" . $this->Msetup->generate_salt() . "');\r\n";
            $data .= "\tdefine('ACP_IP_CHECK',false);\r\n";
            $data .= "\tdefine('ACP_IP_WHITE_LIST','127.0.0.1');\r\n";
            $data .= "\tdefine('ACPURL','admincp');\r\n";
            $data .= "\r\n";
            $data .= "\r\n";
            if(is_writable(BASEDIR . 'constants.php')){
                $fp = @fopen(BASEDIR . 'constants.php', 'w');
                if($fp){
                    @fwrite($fp, $data);
                    @fclose($fp);
                    return $this->write_server_data();
                } else{
                    throw new Exception('Couldn\'t open file <i>' . BASEDIR . 'constants.php</i>');
                }
            } else{
                throw new Exception('Directory ' . BASEDIR . ' is not writable.');
            }			 
        }
        
        
        private function write_server_data(){
			$item_size = 20;
            if($this->vars['wh_size'] > 1200){
                $item_size = 32;
            }
            if($this->vars['wh_size'] > 3840){
                $item_size = 50;
            }
			if($this->vars['wh_size'] == 4800){
                $item_size = 40;
            }
			if($this->vars['wh_size'] > 6000){
                $item_size = 64;
            }
            if($this->vars['wh_size'] == 6960){
                $item_size = 58;
            }
            $array = [
				'USE_MULTI_ACCOUNT_DB' => false, 
				'DEFAULT' => [
					'db' => $_SESSION['db']['char_db'], 
					'db_acc' => $_SESSION['db']['acc_db'], 
					'title' => 'Default', 
					'visible' => 1, 
					'identity_column_character' => $this->vars['identity_column_character']['name'], 
					'inv_size' => $this->vars['inv_size'], 
					'wh_size' => $this->vars['wh_size'], 
					'inv_multiplier' => ($this->vars['inv_size'] > 1728) ? 236 : 108, 
					'wh_multiplier' => ($this->vars['wh_size'] > 1920) ? 240 : 120, 
					'wh_hor_size' => 8, 
					'wh_ver_size' => 15, 
					'item_size' => $item_size, 
					'version' => 'Season X', 
					'exp' => '1x', 
					'drop' => '1%'
				]
			];
            $data = json_encode($array, JSON_PRETTY_PRINT);
            if(is_writable(BASEDIR . 'application' . DS . 'data')){
                $fp = @fopen(BASEDIR . 'application' . DS . 'data' . DS . 'serverlist.json', 'w');
                @fwrite($fp, $data);
                @fclose($fp);
                return true;
            } else{
                throw new Exception('Directory ' . BASEDIR . 'application' . DS . 'data is not writable.');
            }					 
        }
		
		private function create_localization_list(){
            $new_data = [
				'default_localization' => 'en',
				'localizations' => [
					'en' => 1
				]
			];
            $data = json_encode($new_data, JSON_PRETTY_PRINT);
            if(is_writable(BASEDIR . 'application' . DS . 'config')){
                $fp = @fopen(BASEDIR . 'application' . DS . 'config' . DS . 'locale_config.json', 'w');
                @fwrite($fp, $data);
                @fclose($fp);
            }
            return true;
        }

        private function create_social_list(){
            $new_data = ['providers' => ['Facebook' => ['enabled' => false, 'keys' => ['id' => '', 'secret' => '']]]];
            $data = json_encode($new_data, JSON_PRETTY_PRINT);
            if(is_writable(BASEDIR . 'application' . DS . 'config')){
                $fp = @fopen(BASEDIR . 'application' . DS . 'config' . DS . 'social_config.json', 'w');
                @fwrite($fp, $data);
                @fclose($fp);
            }
            return true;
        }

        private function create_meta_list(){
            $new_data = ['en' => ['default' => ['title' => '%server_title%', 'keywords' => '%server_title%, Free MuCMS ' . $this->Msetup->get_cms_version() . ', MuOnline, Website', 'description' => 'Content Management System For MuOnline'], 'home' => ['title' => '%server_title% Home', 'keywords' => '%server_title%, Free MuCMS ' . $this->Msetup->get_cms_version() . ', MuOnline, Website', 'description' => 'Content Management System For MuOnline'], 'registration' => ['title' => '%server_title% Registration', 'keywords' => '%server_title%, Free MuCMS ' . $this->Msetup->get_cms_version() . ', MuOnline, Website', 'description' => 'Content Management System For MuOnline']]];
            $data = json_encode($new_data, JSON_PRETTY_PRINT);
            if(is_writable(BASEDIR . 'application' . DS . 'config')){
                $fp = @fopen(BASEDIR . 'application' . DS . 'config' . DS . 'meta_config.json', 'w');
                @fwrite($fp, $data);
                @fclose($fp);
            }
            return true;
        }
		
		private function create_cms_config(){
			$new_data = ['package' => 'Free MuCMS', "version" => $this->Msetup->get_cms_version()];
			$data = json_encode($new_data, JSON_PRETTY_PRINT);
            if(is_writable(BASEDIR . 'application' . DS . 'config')){
                $fp = @fopen(BASEDIR . 'application' . DS . 'config' . DS . 'cms_config.json', 'w');
                @fwrite($fp, $data);
                @fclose($fp);
            }
            return true;
        }
        
        
        private function add_cron_task($task, $time, $desc, $owerwrite = 0, $status = 1){
            $file = BASEDIR . 'application' . DS . 'config' . DS . 'scheduler_config.json';
            $data = file_get_contents($file);
            $tasks = json_decode($data, true);
            if(!array_key_exists($task, $tasks['tasks'])){
                $tasks['tasks'][$task] = [
					'time' => $time,
					'status' => $status,
					'desc' => $desc
				];
            }
			else{
				if($owerwrite == 1){
					unset($tasks['tasks'][$task]);
					$tasks['tasks'][$task] = [
						'time' => $time,
						'status' => $status,
						'desc' => $desc
					];
				}
			}
            file_put_contents($file, json_encode($tasks, JSON_PRETTY_PRINT));
        }
        
        
        private function check_lock(){
            if(file_exists(INSTALL_DIR . 'data' . DS . 'install.lock')){
                throw new Exception('Installation blocked! Please remove setup' . DS . 'data' . DS . 'install.lock before starting.');
            }
        }

        private function create_lock(){
            if(is_writable(INSTALL_DIR . 'data')){
                $fp = @fopen(INSTALL_DIR . 'data' . DS . 'install.lock', 'w');
                @fwrite($fp, '1');
                @fclose($fp);
            }
            return true;
        }
    }
	