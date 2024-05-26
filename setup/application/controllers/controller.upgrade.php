<?php
	in_file();
	
    class upgrade extends controller
    {
        protected $vars = [], $errors = [];
        private $server_list = [], $is_multi_server = false, $account_database;
        private $after_upgrade_key = 'a953feaec195bba04c142bc38ec2846c';
        private $current_version;
		
		
        public function __construct(){
            parent::__construct();
            $this->load->model('setup/application/models/setup');
            $this->load->helper('website');
            $this->server_list = $this->server_list();
            $this->is_multi_server = $this->server_list('', true);
            $first = reset($this->server_list);
            $this->account_database = $first['db_acc'];
            $this->load->lib('DBEngines/' . DRIVER, [HOST, USER, PASS, WEB_DB], 'web_db');
            $this->vars['current_version'] = $this->Msetup->get_current_version();
            $this->website->check_cache('available_upgrades', 'available_upgrades', 3600 * 24);
            $this->vars['available_upgrades'] = $this->website->cached ? $this->website->available_upgrades : false;
            $this->vars['available_local_version'] = $this->Msetup->get_cms_version();
        }
		
		
        public function index($version = ''){
            $this->vars['version'] = ($version != '') ? $version : $this->vars['available_local_version'];
            if(count($_POST) > 0){
                $user = isset($_POST['username']) ? $_POST['username'] : '';
                $pasword = isset($_POST['password']) ? $_POST['password'] : '';
                $pincode = isset($_POST['pincode']) ? $_POST['pincode'] : '';
                if($user == '')
                    $this->vars['errors'][] = 'Please enter admin username.'; else{
                    if($pasword == '')
                        $this->vars['errors'][] = 'Please enter admin password.'; else{
                        if($user === USERNAME && md5($pasword . SECURITY_SALT) === md5(PASSWORD . SECURITY_SALT)){
                            if(defined('PINCODE') && PINCODE != ''){
                                if($pincode == '')
                                    $this->vars['errors'][] = 'Please enter admin pincode.'; else{
                                    if(PINCODE != $pincode)
                                        $this->vars['errors'][] = 'Entered pincode is wrong.';
                                }
                            } else{
                                $_SESSION['pincode'] = $pincode;
                            }
                            if(!isset($this->vars['errors'])){
                                $_SESSION['is_admin'] = true;
                                header('Location: ' . $this->config->base_url . 'index.php?action=upgrade/step1/' . $this->vars['version']);
                            }
                        } else{
                            $this->vars['errors'][] = 'Wrong username and/or password.';
                        }
                    }
                }
            }
            $this->load->view('setup' . DS . 'application' . DS . 'views' . DS . 'upgrade' . DS . 'view.step0', $this->vars);
        }

        public function step1($version = ''){
            if($this->Msetup->is_admin()){
                $this->vars['version'] = ($version != '') ? $version : $this->vars['available_local_version'];
                $this->Msetup->get_extension_data();
                $this->Msetup->check_writable_files_folders();
                if((isset($this->Msetup->vars['extensionsOK']) && $this->Msetup->vars['extensionsOK'] == false) || (isset($this->Msetup->vars['filesOK']) && $this->Msetup->vars['filesOK'] == false)){
                    $_SESSION['upgrade_allow_step_2'] = false;
                } else{
                    $_SESSION['upgrade_allow_step_2'] = true;
                }
                $this->load->view('setup' . DS . 'application' . DS . 'views' . DS . 'upgrade' . DS . 'view.step1', $this->vars);
            } else{
                throw new Exception('Unauthorized user.');
            }
        }

        public function step2($version = ''){
            $this->vars['version'] = ($version != '') ? $version : $this->vars['available_local_version'];
            if($this->Msetup->is_admin()){
                if(isset($_SESSION['upgrade_allow_step_2']) && $_SESSION['upgrade_allow_step_2'] == false){
                    $this->vars['errors'][] = 'Please complete step 1 before continue.';
                } else{
                    if($this->vars['current_version'] == $this->vars['version']){
                        $this->vars['errors'][] = 'You are running lattest version, upgrade not needed.';
                    } else{
                        if($this->vars['current_version'] > $this->vars['version']){
                            $this->vars['errors'][] = 'Downgrade to lower version is not possible.';
                        } else{
                            if($this->vars['available_upgrades'] == false){
                                if($this->vars['available_local_version'] > $this->vars['current_version']){
                                    $_SESSION['upgrade_allow_step_3'] = true;
                                    if(isset($_SESSION['return_to'])){
                                        unset($_SESSION['return_to']);
                                    }
                                } else{
                                    $this->vars['errors'][] = 'No upgrade versions available.';
                                }
                            } else{
                                if(!empty($this->vars['available_upgrades']['sub_versions'])){
                                    arsort($this->vars['available_upgrades']['sub_versions']);
                                    if(count($this->vars['available_upgrades']['sub_versions']) > 1){
                                        $oldest_version = key(array_slice($this->vars['available_upgrades']['sub_versions'], -1, 1, true));
                                        $before_oldest_version = key(array_slice($this->vars['available_upgrades']['sub_versions'], -2, 1, true));
                                        if($this->vars['available_upgrades']['sub_versions'][$oldest_version]['is_auto_update'] == 0){
                                            if($this->vars['available_local_version'] == $oldest_version){
                                                $_SESSION['upgrade_allow_step_3'] = true;
                                                $_SESSION['return_to'] = 'upgrade/step2/' . $before_oldest_version;
                                            } else{
                                                $this->vars['errors'][] = 'Please upgrade manually first to version ' . $oldest_version . '</a>.<p>Download patch <a href="' . $this->config->base_url . 'index.php?action=upgrade/download-update/' . $oldest_version . '" target="_blank">here</a>. Extract into ' . BASEDIR . ' and refresh this page.</p>';
                                            }
                                        } else{
                                            //run auto update version
                                        }
                                    } else{
                                        $oldest_version = key($this->vars['available_upgrades']['sub_versions']);
                                        if($this->vars['available_upgrades']['sub_versions'][$oldest_version]['is_auto_update'] == 0){
                                            if($this->vars['available_local_version'] == $oldest_version){
                                                $_SESSION['upgrade_allow_step_3'] = true;
                                                $_SESSION['return_to'] = 'upgrade/step2/' . key($this->vars['available_upgrades']['lattest_version']);
                                            } else{
                                                $this->vars['errors'][] = 'Please upgrade manually first to version ' . $oldest_version . '</a>.<p>Download patch <a href="' . $this->config->base_url . 'index.php?action=upgrade/download-update/' . $oldest_version . '" target="_blank">here</a>. Extract into ' . BASEDIR . ' and refresh this page.</p>';
                                            }
                                        } else{
                                            //run auto update version
                                        }
                                    }
                                } else{
                                    if(!empty($this->vars['available_upgrades']['lattest_version'])){
                                        if($this->vars['available_upgrades']['lattest_version'][key($this->vars['available_upgrades']['lattest_version'])]['is_auto_update'] == 0){
                                            if($this->vars['available_local_version'] >= key($this->vars['available_upgrades']['lattest_version'])){
                                                $_SESSION['upgrade_allow_step_3'] = true;
                                                if(isset($_SESSION['return_to'])){
                                                    unset($_SESSION['return_to']);
                                                }
                                            } else{
                                                $this->vars['errors'][] = 'Version ' . key($this->vars['available_upgrades']['lattest_version']) . ' requires manual upgrade. <a href="' . $this->config->base_url . 'index.php?action=upgrade/download-update/' . key($this->vars['available_upgrades']['lattest_version']) . '" target="_blank">Download NOW!</a><p>Extract into ' . BASEDIR . ' and refresh this page.</p>';
                                            }
                                        } else{
                                            //run auto update version
                                        }
                                    } else{
                                        if($this->vars['available_local_version'] > $this->vars['current_version']){
                                            $_SESSION['upgrade_allow_step_3'] = true;
                                            if(isset($_SESSION['return_to'])){
                                                unset($_SESSION['return_to']);
                                            }
                                        } else{
                                            $this->vars['errors'][] = 'No upgrade versions available.';
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                $this->load->view('setup' . DS . 'application' . DS . 'views' . DS . 'upgrade' . DS . 'view.step2', $this->vars);
            } else{
                throw new Exception('Unauthorized user.');
            }
        }

        public function step3(){
            if($this->Msetup->is_admin()){
                if(isset($_SESSION['upgrade_allow_step_2']) && $_SESSION['upgrade_allow_step_2'] == false){
                    $this->vars['errors'][] = 'Please complete step 1 before continue.';
                }
                if(!isset($_SESSION['upgrade_allow_step_3']) || $_SESSION['upgrade_allow_step_3'] == false){
                    $this->vars['errors'][] = 'Please complete step 2 before continue.';
                }
                $_SESSION['upgrade_allow_step_4'] = true;
                $this->load->view('setup' . DS . 'application' . DS . 'views' . DS . 'upgrade' . DS . 'view.step3', $this->vars);
            } else{
                throw new Exception('Unauthorized user.');
            }
        }

        public function step4(){
            if($this->Msetup->is_admin()){
                set_time_limit(0);
                if(isset($_SESSION['upgrade_allow_step_2']) && $_SESSION['upgrade_allow_step_2'] == false){
                    echo json_encode(['error' => 'Please complete step 1 before continue.']);
                }
                if(!isset($_SESSION['upgrade_allow_step_3']) || $_SESSION['upgrade_allow_step_3'] == false){
                    echo json_encode(['error' => 'Please complete step 2 before continue.']);
                }
                if(!isset($_SESSION['upgrade_allow_step_4']) || $_SESSION['upgrade_allow_step_4'] == false){
                    echo json_encode(['error' => 'Please complete step 3 before continue.']);
                }
                if(count($_POST) > 0){
                    $this->vars['cms_versions'] = $this->Msetup->get_all_cms_versions();
                    $this->vars['current_version'] = $this->Msetup->get_current_version();
                    $_SESSION['setup_mu_version'] = $_POST['mu_version'];
                    foreach($this->vars['cms_versions'] AS $key => $value){
                        if($key <= $this->vars['current_version']){
                            unset($this->vars['cms_versions'][$key]);
                        }
                    }
                    $_SESSION['setup_versions'] = $this->vars['cms_versions'];
                    $this->add_tables(key($_SESSION['setup_versions']));
                }
            } else{
                echo json_encode(['error' => 'Unauthorized user.']);
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
                        $this->add_sql_tables($tables_info);
                        unset($_SESSION['setup_versions'][$version][key($version_data)]);
                        if(count($_SESSION['setup_versions'][$version]) > 0){
                            echo json_encode(['step3_5' => 1, 'version' => $version, 'date' => $date, 'type' => 'minor']);
                            return;
                        } else{
                            unset($_SESSION['setup_versions'][$version]);
                            $version = key($_SESSION['setup_versions']);
                            echo json_encode(['step3_5' => 1, 'version' => $version, 'date' => $date, 'type' => 'minor']);
                            return;
                        }
                    } else{
                        unset($_SESSION['setup_versions'][$version][key($version_data)]);
                        if(count($_SESSION['setup_versions'][$version]) > 0){
                            echo json_encode(['step3_5' => 1, 'version' => $version, 'date' => $date, 'type' => 'minor']);
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
                        $this->add_sql_tables($tables_info);
                        unset($_SESSION['setup_versions'][$version]);
                    } else{
                        unset($_SESSION['setup_versions'][$version]);
                    }
                }
                if(count($_SESSION['setup_versions']) > 0){
                    echo json_encode(['step3_5' => 1, 'version' => key($_SESSION['setup_versions']), 'date' => $date, 'type' => 'major']);
                    return;
                }
            }
            echo json_encode(['step4' => 1, 'progress' => '60%', 'message' => 'SQL Table Upgrade Completed']);
        }

        private function add_sql_tables($tables_info){
            if(is_array($tables_info) && count($tables_info) > 0){
                foreach($tables_info AS $key => $table){
                    if($this->Msetup->check_if_table_exists($key, $table['db']) == false){
                        $this->Msetup->run_query($table['query'], $table['db']);
                    }
                }
            }
        }

        public function step5(){
            if($this->Msetup->is_admin()){
                $this->vars['cms_versions'] = $this->Msetup->get_all_cms_versions();
                $this->vars['current_version'] = $this->Msetup->get_current_version();
                foreach($this->vars['cms_versions'] AS $key => $value){
                    if($key <= $this->vars['current_version']){
                        unset($this->vars['cms_versions'][$key]);
                    }
                }
                $_SESSION['setup_versions'] = $this->vars['cms_versions'];
                $this->add_columns(key($_SESSION['setup_versions']));
            } else{
                echo json_encode(['error' => 'Unauthorized user.']);
            }
        }
		
		
        public function add_columns($version){
            if($this->Msetup->is_admin()){
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
                                echo json_encode(['step4_5' => 1, 'version' => $version, 'date' => $date, 'type' => 'minor']);
                                return;
                            } else{
                                unset($_SESSION['setup_versions'][$version]);
                                $version = key($_SESSION['setup_versions']);
                                echo json_encode(['step4_5' => 1, 'version' => $version, 'date' => $date, 'type' => 'minor']);
                                return;
                            }
                        } else{
                            unset($_SESSION['setup_versions'][$version][key($version_data)]);
                            if(count($_SESSION['setup_versions'][$version]) > 0){
                                echo json_encode(['step4_5' => 1, 'version' => $version, 'date' => $date, 'type' => 'minor']);
                                return;
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
                            unset($_SESSION['setup_versions'][$version]);
                        } else{
                            unset($_SESSION['setup_versions'][$version]);
                        }
                    }
                    if(count($_SESSION['setup_versions']) > 0){
                        echo json_encode(['step4_5' => 1, 'version' => key($_SESSION['setup_versions']), 'date' => $date, 'type' => 'major']);
                        return;
                    }
                }
                echo json_encode(['step5' => 1, 'progress' => '70%', 'message' => 'SQL Column Upgrade Completed']);
            } else{
                echo json_encode(['error' => 'Unauthorized user.']);
            }
        }
		
		
        private function add_sql_columns($columns_info){
            if($this->Msetup->is_admin()){
                if(is_array($columns_info) && count($columns_info) > 0){
                    set_time_limit(0);
                    foreach($columns_info AS $db => $table_data){
                        if(array_key_exists('web', $columns_info)){
                            foreach($columns_info['web'] AS $table => $columns){
                                foreach($columns AS $col => $info){
                                    if($this->Msetup->check_column_count($col, $table, 'web') != 1){
                                        if($column_data = $this->Msetup->check_if_column_exists($col, $table, 'web') == false){
                                            $this->Msetup->add_column($col, $table, $info, 'web');
                                            $this->vars['inserted_columns'][] = $col;
                                        } else{
                                            if(strtolower($column_data['DATA_TYPE']) != $info['type']){
                                                $this->Msetup->drop_column($col, $table, 'web');
                                                $this->Msetup->add_column($col, $table, $info, 'web');
                                                $this->vars['inserted_columns'][] = $col;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        if(array_key_exists('account', $columns_info)){
                            if($this->is_multi_server == false){
								$this->load->lib('DBEngines/' . DRIVER, [HOST, USER, PASS, $this->account_database], 'account_db');
                                if(array_key_exists('account', $columns_info)){
                                    foreach($columns_info['account'] AS $table => $columns){
                                        foreach($columns AS $col => $info){
                                            if($this->Msetup->check_column_count($col, $table, 'account') != 1){
                                                if($column_data = $this->Msetup->check_if_column_exists($col, $table, 'account') == false){
                                                    $this->Msetup->add_column($col, $table, $info, 'account');
                                                    $this->vars['inserted_columns'][] = $col;
                                                } else{
                                                    if(strtolower($column_data['DATA_TYPE']) != $info['type']){
                                                        $this->Msetup->drop_column($col, $table, 'account');
                                                        $this->Msetup->add_column($col, $table, $info, 'account');
                                                        $this->vars['inserted_columns'][] = $col;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            } else{
                                foreach($this->server_list AS $server){
                                    if(array_key_exists('account', $columns_info)){
										$this->load->lib('DBEngines/' . DRIVER, [HOST, USER, PASS, $server['db_acc']], 'account_db');
                                        foreach($columns_info['account'] AS $table => $columns){
                                            foreach($columns AS $col => $info){
                                                if($this->Msetup->check_column_count($col, $table, 'account') != 1){
                                                    if($column_data = $this->Msetup->check_if_column_exists($col, $table, 'account') == false){
                                                        $this->Msetup->add_column($col, $table, $info, 'account');
                                                        $this->vars['inserted_columns'][] = $col;
                                                    } else{
                                                        if(strtolower($column_data['DATA_TYPE']) != $info['type']){
                                                            $this->Msetup->drop_column($col, $table, 'account');
                                                            $this->Msetup->add_column($col, $table, $info, 'account');
                                                            $this->vars['inserted_columns'][] = $col;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        if(array_key_exists('game', $columns_info)){
                            foreach($this->server_list AS $server){
								$this->load->lib('DBEngines/' . DRIVER, [HOST, USER, PASS, $server['db']], 'game_db');
                                foreach($columns_info['game'] AS $table => $columns){
                                    foreach($columns AS $col => $info){
                                        if($this->Msetup->check_column_count($col, $table, 'game') != 1){
                                            if($column_data = $this->Msetup->check_if_column_exists($col, $table, 'game') == false){
                                                $this->Msetup->add_column($col, $table, $info, 'game');
                                                $this->vars['inserted_columns'][] = $col;
                                            } else{
                                                if(strtolower($column_data['DATA_TYPE']) != $info['type']){
                                                    $this->Msetup->drop_column($col, $table, 'game');
                                                    $this->Msetup->add_column($col, $table, $info, 'game');
                                                    $this->vars['inserted_columns'][] = $col;
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
                echo json_encode(['error' => 'Unauthorized user.']);
            }
        }
		
		
        public function step6(){
            if($this->Msetup->is_admin()){
                if(isset($_POST['submit_upgrade_data'])){
                    $procedures_info = json_decode(file_get_contents(INSTALL_DIR . 'data' . DS . 'procedures' . DS . 'required_stored_procedures[20.05.2015].json'), true);
                    if(is_array($procedures_info) && !empty($procedures_info)){
                        if($this->Msetup->check_procedure('Add_Credits', 'web') != false){
                            $this->Msetup->drop_procedure('Add_Credits', 'web');
                        }
                        $this->Msetup->insert_sql_data($procedures_info['web']['Add_Credits'], 'web');
                        if($this->is_multi_server == false){
							$this->load->lib('DBEngines/' . DRIVER, [HOST, USER, PASS, $this->account_database], 'account_db');
                            if($this->Msetup->check_procedure('WZ_CONNECT_MEMB', 'account') != false){
                                $this->Msetup->drop_procedure('WZ_CONNECT_MEMB', 'account');
                            }
                            if($this->Msetup->check_procedure('WZ_DISCONNECT_MEMB', 'account') != false){
                                $this->Msetup->drop_procedure('WZ_DISCONNECT_MEMB', 'account');
                            }
							if(defined('MD5') && MD5 == 1){
								if($this->Msetup->check_procedure('DmN_Check_Acc_MD5', 'account') != false){
									$this->Msetup->drop_procedure('DmN_Check_Acc_MD5', 'account');
								}
							}
							if($this->Msetup->check_if_column_exists('HWID', 'MEMB_STAT', 'account') != false){
								$this->Msetup->insert_sql_data(str_replace('dmncms', '[' . WEB_DB . ']', $procedures_info['account']['WZ_CONNECT_MEMB_MUDEVS']), 'account');
								$this->Msetup->insert_sql_data(str_replace('dmncms', '[' . WEB_DB . ']', $procedures_info['account']['WZ_DISCONNECT_MEMB_MUDEVS']), 'account');
							}
							else{
								$this->Msetup->insert_sql_data(str_replace('dmncms', '[' . WEB_DB . ']', $procedures_info['account']['WZ_CONNECT_MEMB']), 'account');
								$this->Msetup->insert_sql_data(str_replace('dmncms', '[' . WEB_DB . ']', $procedures_info['account']['WZ_DISCONNECT_MEMB']), 'account');
							}

                            if(defined('MD5') && MD5 == 1){
								$this->Msetup->insert_sql_data($procedures_info['account']['DmN_Check_Acc_MD5'], 'account');
							}
                        } 
						else{
                            foreach($this->server_list AS $server){
								$this->load->lib('DBEngines/' . DRIVER, [HOST, USER, PASS, $server['db_acc']], 'account_db');
                                if($this->Msetup->check_procedure('WZ_CONNECT_MEMB', 'account') != false){
                                    $this->Msetup->drop_procedure('WZ_CONNECT_MEMB', 'account');
                                }
                                if($this->Msetup->check_procedure('WZ_DISCONNECT_MEMB', 'account') != false){
                                    $this->Msetup->drop_procedure('WZ_DISCONNECT_MEMB', 'account');
                                }
								if(defined('MD5') && MD5 == 1){
									if($this->Msetup->check_procedure('DmN_Check_Acc_MD5', 'account') != false){
										$this->Msetup->drop_procedure('DmN_Check_Acc_MD5', 'account');
									}
								}
								if($this->Msetup->check_if_column_exists('HWID', 'MEMB_STAT', 'account') != false){
									$this->Msetup->insert_sql_data(str_replace('dmncms', '[' . WEB_DB . ']', $procedures_info['account']['WZ_CONNECT_MEMB_MUDEVS']), 'account');
									$this->Msetup->insert_sql_data(str_replace('dmncms', '[' . WEB_DB . ']', $procedures_info['account']['WZ_DISCONNECT_MEMB_MUDEVS']), 'account');
								}
								else{
									$this->Msetup->insert_sql_data(str_replace('dmncms', '[' . WEB_DB . ']', $procedures_info['account']['WZ_CONNECT_MEMB']), 'account');
									$this->Msetup->insert_sql_data(str_replace('dmncms', '[' . WEB_DB . ']', $procedures_info['account']['WZ_DISCONNECT_MEMB']), 'account');
								}
                                
								if(defined('MD5') && MD5 == 1){
									$this->Msetup->insert_sql_data($procedures_info['account']['DmN_Check_Acc_MD5'], 'account');
								}
                            }
                        }
                    }
                    echo json_encode(['step6' => 1, 'progress' => '80%', 'message' => 'SQL Stored Procedures Upgrade Completed.']);
                }
            } else{
                echo json_encode(['error' => 'Unauthorized user.']);
            }
        }
		
		
        public function step7(){
            if($this->Msetup->is_admin()){
                if(isset($_POST['submit_upgrade_data'])){
                    if($this->write_config()){
                        if($this->write_server_data()){
                            if($this->upgrade_version()){
                                $this->clear_cache();
								$this->create_localization_list();
                                $this->add_cron_task('CheckBans', '*/30 * * * *', 'Check bans in server, and add to website ban list');
								$this->add_cron_task('BCMonthlyReward', '6 0 1 * *', 'Add monthly rewards to top BC ranked players');
                                $this->add_cron_task('DSMonthlyReward', '7 0 1 * *', 'Add monthly rewards to top DS ranked players');
                                $this->add_cron_task('CCMonthlyReward', '8 0 1 * *', 'Add monthly rewards to top CC ranked players');
                                $this->add_cron_task('DuelerMonthlyReward', '9 0 1 * *', 'Add monthly rewards to top duelers');
								$this->add_cron_task('ParseMMOTOPVotes', '59 * * * *', 'Parse http:\/\/mu.mmotop.ru votes and reward users');
								$this->add_cron_task('LastMarketItems', '*/5 * * * *', 'Load latest market items', 0);
								$this->add_cron_task('LastForumTopics', '*/20 * * * *', 'Load latest forum topics', 0);
								$this->add_cron_task('SynchronizeVip', '*/2 * * * *', 'Check server vip and add to website vip.', 0, 0);
								$this->upgrade_meta_list();
								$this->upgrade_news();
								$this->upgrade_server_data();
								$this->parse_server_files();
                                $redirect = isset($_SESSION['return_to']) ? $this->config->base_url . 'index.php?action=' . $_SESSION['return_to'] : $this->config->base_url . 'index.php?action=upgrade/completed';
                                echo json_encode(['step7' => 1, 'progress' => '90%', 'message' => 'Configuration Upgrade Completed. Redirecting...', 'redirect' => $redirect]);
                            } else{
                                echo json_encode(['error' => 'Unable to upgrade version']);
                            }
                        } else{
                            echo json_encode(['error' => 'Unable to write server data']);
                        }
                    } else{
                        echo json_encode(['error' => 'Unable to write server config']);
                    }
                }
            } else{
                throw new Exceptin('Unauthorized user.');
            }
        }

        public function completed(){
            session_destroy();
			setcookie("dmn_language", "", 1);
            $this->load->view('setup' . DS . 'application' . DS . 'views' . DS . 'upgrade' . DS . 'view.completed', $this->vars);
        }
		
		
        private function write_config(){
            $ip_check = (ACP_IP_CHECK) ? 'true' : 'false';
            $pincode = (defined('PINCODE') && PINCODE != '') ? PINCODE : $_SESSION['pincode'];
            $data = "<?PHP\r\n";
            $data .= "\r\n";
            $data .= "\tdefine('DMNCMS',		true);\r\n";
            $data .= "\tdefine('DS',			DIRECTORY_SEPARATOR);\r\n";
            $data .= "\tdefine('BASEDIR',		realpath(dirname(__FILE__)).DS);\r\n";
            $data .= "\tdefine('SYSTEM_PATH',	BASEDIR.'system');\r\n";
            $data .= "\tdefine('APP_PATH',		BASEDIR.'application');\r\n";
			if(defined('DATE_FORMAT')){
				$data .= "\tdefine('DATE_FORMAT',	'" . DATE_FORMAT . "');\r\n";
			}
			else{
				$data .= "\tdefine('DATE_FORMAT',	'd-m-Y');\r\n";
			}
			if(defined('DATETIME_FORMAT')){
				$data .= "\tdefine('DATETIME_FORMAT',	'" . DATETIME_FORMAT . "');\r\n";
			}
			else{
				$data .= "\tdefine('DATETIME_FORMAT',	'd-m-Y H:i:s');\r\n";
			}
            $data .= "\tdefine('INSTALLED',		true);\r\n";
			if(defined('IPS_CONNECT')){
				$data .= "\tdefine('IPS_CONNECT',	" . var_export(IPS_CONNECT, true) . ");\r\n";
			}
			if(defined('IPS_CONNECT_MASTER_KEY')){
				$data .= "\tdefine('IPS_CONNECT_MASTER_KEY',	'" . IPS_CONNECT_MASTER_KEY . "');\r\n";
			}
			if(defined('IPS_CONNECT_BASE_URL')){
				$data .= "\tdefine('IPS_CONNECT_BASE_URL',	'" . IPS_CONNECT_BASE_URL . "');\r\n";
			}
			if(defined('CUSTOM_RESET_REQ_ITEMS')){
				$data .= "\tdefine('CUSTOM_RESET_REQ_ITEMS',	" . var_export(CUSTOM_RESET_REQ_ITEMS, true) . ");\r\n";
			}
			if(defined('CUSTOM_GRESET_REQ_ITEMS')){
				$data .= "\tdefine('CUSTOM_GRESET_REQ_ITEMS',	" . var_export(CUSTOM_GRESET_REQ_ITEMS, true) . ");\r\n";
			}
			if(defined('MAX_GR')){
				$data .= "\tdefine('MAX_GR',	" . MAX_GR . ");\r\n";
			}
			if(defined('DBLIBUTF')){
				$data .= "\tdefine('DBLIBUTF',	" . var_export(DBLIBUTF, true) . ");\r\n";
			}
			if(defined('SWITCH_LANG_ON_LOCATION')){
				$data .= "\tdefine('SWITCH_LANG_ON_LOCATION',	" . var_export(SWITCH_LANG_ON_LOCATION, true) . ");\r\n";
			}
			if(defined('LANGS')){
				$data .= "\tdefine('LANGS',	" . var_export(LANGS, true) . ");\r\n";
			}
			if(defined('RES_DECREASE_STATS_PERC')){
				$data .= "\tdefine('RES_DECREASE_STATS_PERC',	" . RES_DECREASE_STATS_PERC . ");\r\n";
			}
			if(defined('BPASS_PURCHASE')){
				$data .= "\tdefine('BPASS_PURCHASE',	" . var_export(BPASS_PURCHASE, true) . ");\r\n";
			}
			if(defined('BPASS_SILVER_PRICE')){
				$data .= "\tdefine('BPASS_SILVER_PRICE',	" . BPASS_SILVER_PRICE . ");\r\n";
			}
			if(defined('BPASS_PLATINUM_PRICE')){
				$data .= "\tdefine('BPASS_PLATINUM_PRICE',	" . BPASS_PLATINUM_PRICE . ");\r\n";
			}
			if(defined('BPASS_CURRENCY')){
				$data .= "\tdefine('BPASS_CURRENCY',	'" . BPASS_CURRENCY . "');\r\n";
			}
			if(defined('PARTNER_SYSTEM')){
				$data .= "\tdefine('PARTNER_SYSTEM',	" . var_export(PARTNER_SYSTEM, true) . ");\r\n";
			}
			if(defined('TWITCH_CLIENT_ID')){
				$data .= "\tdefine('TWITCH_CLIENT_ID',	'" . TWITCH_CLIENT_ID . "');\r\n";
			}
			if(defined('TWITCH_SECRET')){
				$data .= "\tdefine('TWITCH_SECRET',	'" . TWITCH_SECRET . "');\r\n";
			}
			if(defined('MARKET_IMAGE_URL')){
				$data .= "\tdefine('MARKET_IMAGE_URL',	'" . MARKET_IMAGE_URL . "');\r\n";
			}
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
            $data .= "\tdefine('HOST',		'" . HOST . "');\r\n";
            $data .= "\tdefine('USER',		'" . USER . "');\r\n";
            $data .= "\tdefine('PASS',		'" . PASS . "');\r\n";
            $data .= "\tdefine('WEB_DB',	'" . WEB_DB . "');\r\n";
            $data .= "\tdefine('PAGE_START', microtime(true));\r\n";
            $data .= "\tdefine('LOG_SQL',	false);\r\n";
            $data .= "\tdefine('DRIVER', 	'" . strtolower(DRIVER) . "');\r\n";
            $data .= "\tdefine('MD5',		" . MD5 . ");\r\n";
            $data .= "\tdefine('SOCKET_LIBRARY'," . SOCKET_LIBRARY . ");\r\n";
            $data .= "\tdefine('ENVIRONMENT', '" . ENVIRONMENT . "');\r\n";
            $data .= "\r\n";
            $data .= "\r\n";
            $data .= "\t/*\r\n";
            $data .= "\t *---------------------------------------------------------------\r\n";
            $data .= "\t * Mu Server Version\r\n";
            $data .= "\t *---------------------------------------------------------------\r\n";
            $data .= "\t *\r\n";
            $data .= "\t *     Define MuOnline Server Version\r\n";
            $data .= "\t *     - version 0 - below season 1\r\n";
            $data .= "\t *     - version 1 - season 1\r\n";
            $data .= "\t *     - version 2 - season 2 and higher\r\n";
            $data .= "\t *     - version 3 - ex700 and higher\r\n";
            $data .= "\t *     - version 4 - season 8 and higher\r\n";
            $data .= "\t *     - version 5 - season 10 and higher\r\n";
            $data .= "\t *     - version 6 - season 11 and higher\r\n";
            $data .= "\t *     - version 7 - season 12 and higher\r\n";
            $data .= "\t *     - version 8 - season 13 and higher\r\n";
            $data .= "\t *     - version 9 - season 14 and higher\r\n";
			$data .= "\t *     - version 10 - season 15 and higher\r\n";
			$data .= "\t *     - version 11 - season 16 and higher\r\n";
			$data .= "\t *     - version 12 - season 17 and higher\r\n";
			$data .= "\t *     - version 13 - season 18 and higher\r\n";
			$data .= "\t *     - version 14 - season 19 and higher\r\n";
            $data .= "\t *\r\n";
            $data .= "\t */\r\n";
            $data .= "\r\n";
            $data .= "\tdefine('MU_VERSION',		" . $_SESSION['setup_mu_version'] . ");\r\n";
            $data .= "\r\n";
            $data .= "\r\n";
            $data .= "\t/*\r\n";
            $data .= "\t *---------------------------------------------------------------\r\n";
            $data .= "\t * Admin CP\r\n";
            $data .= "\t *---------------------------------------------------------------\r\n";
            $data .= "\t *\r\n";
            $data .= "\t */\r\n";
            $data .= "\r\n";
            $data .= "\tdefine('USERNAME',	'" . USERNAME . "');\r\n";
            $data .= "\tdefine('PASSWORD', 	'" . PASSWORD . "');\r\n";
            $data .= "\tdefine('PINCODE', 	'" . $pincode . "');\r\n";
            $data .= "\tdefine('SECURITY_SALT','" . SECURITY_SALT . "');\r\n";
            $data .= "\tdefine('ACP_IP_CHECK', " . $ip_check . ");\r\n";
            $data .= "\tdefine('ACP_IP_WHITE_LIST','" . ACP_IP_WHITE_LIST . "');\r\n";
			$data .= "\tdefine('ACPURL','" . ACPURL . "');\r\n";
            $data .= "\r\n";
            $data .= "\r\n";
            if(is_writable(BASEDIR . 'constants.php')){
                $fp = @fopen(BASEDIR . 'constants.php', 'w');
                if($fp){
                    @fwrite($fp, $data);
                    @fclose($fp);
                    return true;
                } else{
                    throw new Exception('Couldn\'t open file <i>' . BASEDIR . 'constants.php</i>');
                }
            } else{
                throw new Exception('Directory ' . BASEDIR . ' is not writable.');
            }
        }
		
		
        private function write_server_data(){
            $array = ['USE_MULTI_ACCOUNT_DB' => $this->is_multi_server];
            foreach($this->server_list AS $key => $server){
                $array[$key] = [
					'db' => $server['db'], 
					'db_acc' => $server['db_acc'], 
					'title' => $server['title'], 
					'visible' => $server['visible'], 
					'identity_column_character' => isset($server['identity_column_character']) ? $server['identity_column_character'] : 'id', 
					'inv_size' => $server['inv_size'], 
					'wh_size' => $server['wh_size'], 
					'inv_multiplier' => $server['inv_multiplier'], 
					'wh_multiplier' => $server['wh_multiplier'], 
					'wh_hor_size' => 8, 
					'wh_ver_size' => 15, 
					'item_size' => $server['item_size'],  
					'version' => $server['version'], 
					'exp' => $server['exp'], 
					'drop' => $server['drop']
				];
            }
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
		
		
        private function upgrade_version(){
			$new_data = ['package' => 'Free MuCMS', "version" => $this->Msetup->get_cms_version()];
			$data = json_encode($new_data, JSON_PRETTY_PRINT);
			if(is_writable(BASEDIR . 'application' . DS . 'config')){
                $fp = @fopen(BASEDIR . 'application' . DS . 'config' . DS . 'cms_config.json', 'w');
                @fwrite($fp, $data);
                @fclose($fp);
            }
			return true;
        }
		
		
        private function clear_cache(){
            $files = glob(BASEDIR . 'application' . DS . 'data' . DS . 'cache' . DS . '*.dmn');
            array_map('unlink', $files);																									 
        }
		
		
        private function server_list($serv = '', $check_multi_acc = false){
            $file = file_get_contents(BASEDIR . 'application' . DS . 'data' . DS . 'serverlist.json');
            $servers = json_decode($file, true);
            if(is_array($servers)){
                if($check_multi_acc == true){
                    return $servers['USE_MULTI_ACCOUNT_DB'];
                } else{
                    unset($servers['USE_MULTI_ACCOUNT_DB']);
                    if($serv != ''){
                        if(array_key_exists($serv, $servers)){
                            return $servers[$serv];
                        }
                    }
                    return $servers;
                }
            } else{
                throw new Exception('Unable to load server list. Please check configuration file.');
            }
        }
		
		
		private function create_localization_list(){
			$file = BASEDIR . 'application' . DS . 'config' . DS . 'locale_config.json';
			$new_data = [
				'default_localization' => 'en',
				'localizations' => [
					'en' => 1
				]
			];
			if(!file_exists($file)){
				$data = json_encode($new_data, JSON_PRETTY_PRINT);
				if(is_writable(BASEDIR . 'application' . DS . 'config')){
					$fp = @fopen($file, 'w');
					@fwrite($fp, $data);
					@fclose($fp);
				}
				return true;
			}
			else{
				$data = json_decode(file_get_contents($file), true);
				if(empty($data) || !isset($data['default_localization'])){
					$data = json_encode($new_data, JSON_PRETTY_PRINT);
					if(is_writable(BASEDIR . 'application' . DS . 'config')){
						$fp = @fopen($file, 'w');
						@fwrite($fp, $data);
						@fclose($fp);
					}
					return true;
				}
			}
        }
		
		private function upgrade_meta_list(){
			$file = BASEDIR . 'application' . DS . 'config' . DS . 'meta_config.json';
			if(file_exists($file)){
				$data = json_decode(file_get_contents($file), true);
				if(isset($data['en_GB'])){
					$data['en'] = $data['en_GB'];
					unset($data['en_GB']);
					 if(is_writable(BASEDIR . 'application' . DS . 'config')){
						$fp = @fopen($file, 'w');
						@fwrite($fp,json_encode($data, JSON_PRETTY_PRINT));
						@fclose($fp);
					}
				}
				return true;
			}
			else{
				$this->create_meta_list();
			}
        }
		
		private function upgrade_server_data(){
			$path = BASEDIR . 'application' . DS . 'data' . DS . 'ServerData' . DS . 'en_GB';
			if(is_dir($path)){
				rename($path, BASEDIR . 'application' . DS . 'data' . DS . 'ServerData' . DS . 'en');
			}
		}
		
		private function upgrade_news(){
			$file = BASEDIR . 'application' . DS . 'data' . DS . 'dmn_news.json';
			if(file_exists($file)){
				$data = json_decode(file_get_contents($file), true);
				foreach($data AS $k => $v){
					if($v['lang'] == 'en_GB'){
						$data[$k]['lang'] = 'en';
					}
				}
				if(is_writable(BASEDIR . 'application' . DS . 'data')){
					$fp = @fopen($file, 'w');
					@fwrite($fp,json_encode($data, JSON_PRETTY_PRINT));
					@fclose($fp);
				}
			}
		}
		
		
		private function parse_server_files(){
			$file = BASEDIR . 'application' . DS . 'config' . DS . 'scheduler_config.json';
			if(file_exists($file)){
				$conf = json_decode(file_get_contents($file), true);
				$options = [
					"ssl" => [
						"verify_peer" => false,
						"verify_peer_name" => false
					],
					"http" => [
						"header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36"
					]
				]; 
				file_get_contents($this->config->base_url . '../interface/web.php?key=' . $conf['key'] . '&custom=ParseServerFiles', false, stream_context_create($options));
			}
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
    }
	