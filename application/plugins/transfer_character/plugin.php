<?php
    in_file();

    class _plugin_transfer_character extends controller implements pluginInterface
    {
        private $pluginaizer;
        private $vars = [];

        /**
         *
         * Plugin constructor
         * Initialize plugin class
         *
         */
        public function __construct(){
            //initialize parent constructor
            parent::__construct();
            //initialize pluginaizer
            $this->pluginaizer = $this->load_class('plugin');
            //set plugin class name
            $this->pluginaizer->set_plugin_class(substr(get_class($this), 8));
        }

        /**
         *
         * Main module body
         * All main things related to user side
         *
         *
         * Return mixed
         */
        public function index($page = 1){
            if($this->pluginaizer->data()->value('installed') == false){
                throw new Exception('Plugin has not yet been installed.');
            } else{
                if($this->pluginaizer->data()->value('installed') == 1){
                    if($this->pluginaizer->data()->value('is_public') == 0){
                        $this->user_module($page);
                    } else{
                        $this->public_module();
                    }
                } else{
                    throw new Exception('Plugin has been disabled.');
                }
            }
        }

        /**
         *
         * Load user module data
         *
         * return mixed
         *
         */
		 
        private function user_module(){
            //check if visitor has user privilleges
            if($this->pluginaizer->session->is_user()){
                //load website helper
                $this->load->helper('website');
                //load paginator
                $this->load->lib('pagination');
                //load plugin config
                $this->vars['plugin_config'] = $this->pluginaizer->plugin_config();
                if($this->vars['plugin_config'] != false && !empty($this->vars['plugin_config'])){
                    if($this->pluginaizer->data()->value('is_multi_server') == 1){
                        if(array_key_exists($this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['plugin_config'])){
                            $this->vars['plugin_config'] = $this->vars['plugin_config'][$this->pluginaizer->session->userdata(['user' => 'server'])];
                            $this->vars['about'] = $this->pluginaizer->get_about();
                            $this->vars['about']['user_description'] = $this->pluginaizer->data()->value('description');
                        } else{
                            $this->vars['config_not_found'] = __('Plugin configuration not found.');
                        }
                    }
                    if($this->vars['plugin_config']['active'] == 0){
                        $this->vars['module_disabled'] = __('This module has been disabled.');
                    } else{
						$this->load->model('application/plugins/' . $this->pluginaizer->get_plugin_class() . '/models/' . $this->pluginaizer->get_plugin_class() . '');
						if(!$this->{'M'.$this->pluginaizer->get_plugin_class()}->check_connect_stat($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server'])))
                            $this->vars['module_disabled'] = __('Please logout from game.'); 
						else{
							if(!$this->{'M'.$this->pluginaizer->get_plugin_class()}->check_if_acc_has_chars($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']))){
                                $this->vars['module_disabled'] = __('Your account don\'t have any character');
                            }
							
							if(!isset($this->vars['cant_proceed'])){
                                $this->vars['char_list'] = $this->{'M'.$this->pluginaizer->get_plugin_class()}->character_data;
								if(isset($_POST['character'])){
									try{
										$id = isset($_POST['character']) ? $_POST['character'] : '';
										$new_name = isset($_POST['new_name']) ? $_POST['new_name'] : '';
										
										if($id == ''){
											throw new Exception(__('Please select character.'));
										}
										
										$char_data = $this->{'M'.$this->pluginaizer->get_plugin_class()}->check_if_chars_exists($id, $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
										if($char_data == false){
											throw new Exception(__('Character not found.'));
										}
										
										if($new_name == ''){
											$new_name = $char_data['Name'];
										}
										
										if(mb_strlen($new_name) < 4 || mb_strlen($new_name) > 10){
											throw new Exception(sprintf(__('Character Name can be 4-%d chars long!'), 10));
										}
										
										if(!$this->{'M'.$this->pluginaizer->get_plugin_class()}->check_connect_stat($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']))){
											throw new Exception(__('Please logout from game.')); 
										}
										
										if(!preg_match('/^[\p{L}0-9]+$/u', $new_name)){
											throw new Exception(__('You are using forbidden chars in your new name.'));
										}
										
										if($this->vars['plugin_config']['delete_char'] == 0){
											if($this->{'M'.$this->pluginaizer->get_plugin_class()}->check_if_transfered($id, $this->pluginaizer->session->userdata(['user' => 'server'])) != false){
												throw new Exception(__('Character has been already transfered.'));
											}
										}
										
										if($this->{'M'.$this->pluginaizer->get_plugin_class()}->check_if_chars_exists_on_second_server($new_name, $this->vars['plugin_config']['to']) != false){
											throw new Exception(__('Character with this name already exists on second server.'));
										}
										
										if($space = $this->{'M'.$this->pluginaizer->get_plugin_class()}->check_free_slot($this->pluginaizer->session->userdata(['user' => 'username']), $this->vars['plugin_config']['to'])){
											$this->{'M'.$this->pluginaizer->get_plugin_class()}->add_to_account_character($space, $new_name, $this->pluginaizer->session->userdata(['user' => 'username']), $this->vars['plugin_config']['to']);
											$this->{'M'.$this->pluginaizer->get_plugin_class()}->character($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'username']), $this->vars['plugin_config']['to'], $char_data['Name'], $new_name, $this->vars['plugin_config']['transfer_items']);
											$this->{'M'.$this->pluginaizer->get_plugin_class()}->OptionData($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'username']), $this->vars['plugin_config']['to'], $char_data['Name'], $new_name);
											$this->{'M'.$this->pluginaizer->get_plugin_class()}->T_CGuid($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'username']), $this->vars['plugin_config']['to'], $new_name);
											$this->{'M'.$this->pluginaizer->get_plugin_class()}->CustomQuest($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'username']), $this->vars['plugin_config']['to'], $char_data['Name'], $new_name);
											$this->{'M'.$this->pluginaizer->get_plugin_class()}->Achievements($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'username']), $this->vars['plugin_config']['to'], $char_data['Name'], $new_name);
											
											$newId = $this->{'M'.$this->pluginaizer->get_plugin_class()}->check_if_chars_exists_on_second_server($new_name, $this->vars['plugin_config']['to']);
											
											if($this->vars['plugin_config']['transfer_items'] == 1){
												$this->{'M'.$this->pluginaizer->get_plugin_class()}->gremory_case($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'username']), $this->vars['plugin_config']['to'], $char_data['Name'], $new_name);
												$this->{'M'.$this->pluginaizer->get_plugin_class()}->IGC_PeriodBuffInfo($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'username']), $this->vars['plugin_config']['to'], $char_data['Name'], $new_name);
												$this->{'M'.$this->pluginaizer->get_plugin_class()}->IGC_PeriodItemInfo($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'username']), $this->vars['plugin_config']['to'], $char_data['Name'], $new_name);
												$this->{'M'.$this->pluginaizer->get_plugin_class()}->T_PentagramInfo($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'username']), $this->vars['plugin_config']['to'], $char_data['Name'], $new_name);
												$this->{'M'.$this->pluginaizer->get_plugin_class()}->IGC_HarmonyItemData($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'username']), $this->vars['plugin_config']['to'], $char_data['Name'], $new_name);
												$this->{'M'.$this->pluginaizer->get_plugin_class()}->IGC_ArtifactInfo($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'username']), $this->vars['plugin_config']['to'], $char_data['Name'], $new_name);
												$this->{'M'.$this->pluginaizer->get_plugin_class()}->T_PetItem_Info($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'username']), $this->vars['plugin_config']['to'], $char_data['Name'], $new_name);
											}
											if($this->vars['plugin_config']['transfer_muun'] == 1){
												$this->{'M'.$this->pluginaizer->get_plugin_class()}->IGC_Muun_ConditionInfo($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'username']), $this->vars['plugin_config']['to'], $char_data['Name'], $new_name);
												$this->{'M'.$this->pluginaizer->get_plugin_class()}->IGC_Muun_Inventory($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'username']), $this->vars['plugin_config']['to'], $char_data['Name'], $new_name);
												$this->{'M'.$this->pluginaizer->get_plugin_class()}->IGC_Muun_Period($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'username']), $this->vars['plugin_config']['to'], $char_data['Name'], $new_name);
											}
											if($this->vars['plugin_config']['transfer_gens'] == 1){
												$this->{'M'.$this->pluginaizer->get_plugin_class()}->IGC_Gens($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'username']), $this->vars['plugin_config']['to'], $char_data['Name'], $new_name);
                                            }
											
											if($newId != false){
												$this->{'M'.$this->pluginaizer->get_plugin_class()}->IGC_SeasonPass($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'username']), $this->vars['plugin_config']['to'], $id, $newId['id']);
												$this->{'M'.$this->pluginaizer->get_plugin_class()}->IGC_SeasonPassMission($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'username']), $this->vars['plugin_config']['to'], $id, $newId['id']);
												$this->{'M'.$this->pluginaizer->get_plugin_class()}->IGC_SeasonPassTicket($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'username']), $this->vars['plugin_config']['to'], $id, $newId['id']);
												$this->{'M'.$this->pluginaizer->get_plugin_class()}->DmN_User_Achievements($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'username']), $this->vars['plugin_config']['to'], $id, $newId['id']);
												$this->{'M'.$this->pluginaizer->get_plugin_class()}->DmN_Unlocked_Achievements($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'username']), $this->vars['plugin_config']['to'], $id, $newId['id']);
											
											}
											
											if($this->vars['plugin_config']['delete_char'] == 1){
												$this->{'M'.$this->pluginaizer->get_plugin_class()}->deleteChar($id, 'T_MU_QUEST_INFO', 'character_id');
												$this->{'M'.$this->pluginaizer->get_plugin_class()}->deleteChar($id, 'IGC_MuQuestInfo', 'character_id');
												$this->{'M'.$this->pluginaizer->get_plugin_class()}->deleteChar($id, 'IGC_GuideQuestInfo', 'character_id');
												$this->{'M'.$this->pluginaizer->get_plugin_class()}->deleteChar($char_data['Name'], 'OptionData', 'Name');
												$this->{'M'.$this->pluginaizer->get_plugin_class()}->deleteChar($char_data['Name'], 'Achievements', 'Name');
												$this->{'M'.$this->pluginaizer->get_plugin_class()}->deleteChar($char_data['Name'], 'T_QUEST_EXP_INFO', 'CHAR_NAME');
												$this->{'M'.$this->pluginaizer->get_plugin_class()}->deleteChar($char_data['Name'], 'IGC_GremoryCase', 'Name');
												$this->{'M'.$this->pluginaizer->get_plugin_class()}->deleteChar($char_data['Name'], 'IGC_PeriodBuffInfo', 'CharacterName');
												$this->{'M'.$this->pluginaizer->get_plugin_class()}->deleteChar($char_data['Name'], 'IGC_PeriodItemInfo', 'CharacterName');
												$this->{'M'.$this->pluginaizer->get_plugin_class()}->deleteChar($char_data['Name'], 'T_LUCKY_ITEM_INFO', 'CharName');
												$this->{'M'.$this->pluginaizer->get_plugin_class()}->deleteChar($char_data['Name'], 'T_CGuid', 'Name');
												$this->{'M'.$this->pluginaizer->get_plugin_class()}->deleteChar($char_data['Name'], 'IGC_PentagramInfo', 'Name');
												$this->{'M'.$this->pluginaizer->get_plugin_class()}->deleteChar($char_data['Name'], 'IGC_Muun_Inventory', 'Name');
												$this->{'M'.$this->pluginaizer->get_plugin_class()}->deleteChar($char_data['Name'], 'IGC_Muun_Period', 'Name');
												$this->{'M'.$this->pluginaizer->get_plugin_class()}->deleteChar($char_data['Name'], 'IGC_Muun_ConditionInfo', 'Name');
												$this->{'M'.$this->pluginaizer->get_plugin_class()}->deleteChar($char_data['Name'], 'IGC_Gens', 'Name');
												$this->{'M'.$this->pluginaizer->get_plugin_class()}->deleteChar($char_data['Name'], 'IGC_GensAbuse', 'Name');
												$this->{'M'.$this->pluginaizer->get_plugin_class()}->deleteChar($char_data['Name'], 'IGC_ArtifactInfo', 'Name');
												$this->{'M'.$this->pluginaizer->get_plugin_class()}->deleteChar($char_data['Name'], 'Character', 'Name');
												$this->{'M'.$this->pluginaizer->get_plugin_class()}->remove_account_character($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $char_data['Name']);
											}
											$this->{'M'.$this->pluginaizer->get_plugin_class()}->log_transfer($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'username']), $this->vars['plugin_config']['to'], $id, $char_data['Name'], $new_name);
											
											$this->vars['success'] = __('Character successfully transfered.');  
											$this->{'M'.$this->pluginaizer->get_plugin_class()}->check_if_acc_has_chars($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
											$this->vars['char_list'] = $this->{'M'.$this->pluginaizer->get_plugin_class()}->character_data;
										}	
										else{
											throw new Exception(__('All character slots are used. Please remove some character.'));
										}
									}
									catch(Exception $e){
										$this->vars['error'] = $e->getMessage();
									}
								}
							}
						}
                    }
                } else{
                    $this->vars['config_not_found'] = __('Plugin configuration not found.');
                }
                //set js
                $this->vars['js'] = $this->config->base_url . 'assets/plugins/js/transfer_character.js';
                //load template
                $this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . $this->config->config_entry('main|template') . DS . 'view.transfer_char', $this->vars);
            } else{
                $this->pluginaizer->redirect($this->config->base_url . 'account-panel/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()));
            }
        }

        /**
         *
         * Load public module data
         *
         * return mixed
         *
         */
        private function public_module(){
            // public module not used in this plugin
        }

        /**
         *
         * Main admin module body
         * All main things related to admincp
         *
         *
         * Return mixed
         */
        public function admin(){
            //check if visitor has administrator privilleges
            if($this->pluginaizer->session->is_admin()){
                //load website helper
                $this->load->helper('website');
                $this->load->model('application/plugins/transfer_character/models/transfer_character');
                $this->vars['is_multi_server'] = $this->pluginaizer->data()->value('is_multi_server');
                $this->vars['plugin_config'] = $this->pluginaizer->plugin_config();
                //load any js, css files if required
                $this->vars['js'] = $this->config->base_url . 'assets/plugins/js/transfer_character.js';
                //load template
                $this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . 'admin' . DS . 'view.index', $this->vars);
            } else{
                $this->pluginaizer->redirect($this->config->base_url . 'admincp/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()) . '/admin');
            }
        }
		
		/**
         *
         * Generate logs
         *
         * @param int $page
         * @param string $acc
         * @param string $server
         *
         * Return mixed
         */
		 
		public function logs($page = 1, $acc = '-', $server = 'All'){
            //check if visitor has administrator privilleges
            if($this->pluginaizer->session->is_admin()){
                //load website helper
                $this->load->helper('website');
                //load paginator
                $this->load->lib('pagination');
				$this->load->model('admin');
                $this->load->model('application/plugins/' . $this->pluginaizer->get_plugin_class() . '/models/' . $this->pluginaizer->get_plugin_class());  
				
				$this->vars['is_multi_server'] = $this->pluginaizer->data()->value('is_multi_server');
				
                if(isset($_POST['search'])){
                    $server = isset($_GET['server']) ? $_GET['server'] : 'All';
                    $acc = isset($_POST['account']) ? $_POST['account'] : '';
                    if($acc == ''){
                        $this->vars['error'] = 'Invalid account';
                    } else{
                        $this->vars['logs'] = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->load_logs(1, 25, $acc, $server);
                        $this->pluginaizer->pagination->initialize(1, 25, $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->count_total_logs($acc, $server), $this->config->base_url . 'transfer-character/logs/%s/' . $acc . '/' . $server . '');
                        $this->vars['pagination'] = $this->pluginaizer->pagination->create_links();
                    }
                } 
				else{
					if(isset($_GET['server'])){
						$server = $_GET['server'];
						$acc = '';
					}
                    $this->vars['logs'] = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->load_logs($page, 25, $acc, $server);
                    $lk = '';
                    if($acc != '')
                        $lk .= '/' . $acc;
                    $lk .= '/' . $server;
                    $this->pluginaizer->pagination->initialize($page, 25, $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->count_total_logs($acc, $server), $this->config->base_url . 'transfer-character/logs/%s' . $lk);
                    $this->vars['pagination'] = $this->pluginaizer->pagination->create_links();
                }
                $this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . 'admin' . DS . 'view.logs', $this->vars);
            } else{
                $this->pluginaizer->redirect($this->config->base_url . 'admincp/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()) . '/logs');
            }
        }

        /**
         *
         * Save plugin settings
         *
         *
         * Return mixed
         */
        public function save_settings(){
            //check if visitor has administrator privilleges
            if($this->pluginaizer->session->is_admin()){
                $this->vars['plugin_config'] = $this->pluginaizer->plugin_config();
                if(isset($_POST['server']) && $_POST['server'] != 'all'){
                    foreach($_POST AS $key => $val){
                        if($key != 'server'){
                            $this->vars['plugin_config'][$_POST['server']][$key] = $val;
                        }
                    }
                } else{
                    foreach($_POST AS $key => $val){
                        if($key != 'server'){
                            $this->vars['plugin_config'][$key] = $val;
                        }
                    }
                }
                if($this->pluginaizer->save_config($this->vars['plugin_config'])){
                    echo $this->pluginaizer->jsone(['success' => 'Plugin configuration successfully saved']);
                } else{
                    echo $this->pluginaizer->jsone(['error' => $this->pluginaizer->error]);
                }
            }
        }

        /**
         *
         * Plugin installer
         * Admin module for plugin installation
         * Set plugin data, create plugin config template, create sql schemes
         *
         */
		 
        public function install(){
            //check if visitor has administrator privilleges
            if($this->pluginaizer->session->is_admin()){
                //create plugin info
                $this->pluginaizer->set_about()->add_plugin([
					'installed' => 1, 
					'module_url' => str_replace('_', '-', $this->pluginaizer->get_plugin_class()), //link to module
                    'admin_module_url' => str_replace('_', '-', $this->pluginaizer->get_plugin_class()) . '/admin', //link to admincp module
                    'is_public' => 0, //if is public module or requires to login
                    'is_multi_server' => 1, //will this plugin have different config for each server, multi server is supported only by not user modules
                    'main_menu_item' => 0, //add link to module in main website menu,
                    'sidebar_user_item' => 0, //add link to module in user sidebar
                    'sidebar_public_item' => 1, //add link to module in public sidebar menu, if template supports
                    'account_panel_item' => 1, //add link in user account panel
                    'donation_panel_item' => 0, //add link in donation page
                    'description' => 'Transfer Characters Between Servers' //description which will see user
                ]);
                //create plugin config template
                $this->pluginaizer->create_config(['active' => 0,  'to' => '', 'transfer_items' => 1, 'transfer_muun' => 1, 'transfer_gens' => 1, 'delete_char' => 0, 'price' => 0, 'price_type' => 1]);
                //add sql scheme if there is any into website database
                //all schemes should be located in plugin_folder/sql_schemes
                $this->pluginaizer->add_sql_scheme('character_transfer_server_logs');
                //check for errors
                if(count($this->pluginaizer->error) > 0){
                    $data['error'] = $this->pluginaizer->error;
                }
                $data['success'] = 'Plugin installed successfully';
                echo $this->pluginaizer->jsone($data);
            } else{
                echo $this->pluginaizer->jsone(['error' => 'Please login first!']);
            }
        }

        /**
         *
         * Plugin uninstaller
         * Admin module for plugin uninstall
         * Remove plugin data, delete plugin config, delete sql schemes
         *
         */
		 
        public function uninstall(){
            //check if visitor has administrator privilleges
            if($this->pluginaizer->session->is_admin()){
                //delete plugin config and remove plugin data
                $this->pluginaizer->delete_config()->remove_sql_scheme('character_transfer_server_logs')->remove_plugin();
                //check for errors
                if(count($this->pluginaizer->error) > 0){
                    echo $this->pluginaizer->jsone(['error' => $this->pluginaizer->error]);
                }
                echo $this->pluginaizer->jsone(['success' => 'Plugin uninstalled successfully']);
            } else{
                echo $this->pluginaizer->jsone(['error' => 'Please login first!']);
            }
        }

        public function enable(){
            //check if visitor has administrator privilleges
            if($this->pluginaizer->session->is_admin()){
                //enable plugin
                $this->pluginaizer->enable_plugin();
                //check for errors
                if(count($this->pluginaizer->error) > 0){
                    echo $this->pluginaizer->jsone(['error' => $this->pluginaizer->error]);
                } else{
                    echo $this->pluginaizer->jsone(['success' => 'Plugin successfully enabled.']);
                }
            } else{
                echo $this->pluginaizer->jsone(['error' => 'Please login first!']);
            }
        }

        public function disable(){
            //check if visitor has administrator privilleges
            if($this->pluginaizer->session->is_admin()){
                //disable plugin
                $this->pluginaizer->disable_plugin();
                //check for errors
                if(count($this->pluginaizer->error) > 0){
                    echo $this->pluginaizer->jsone(['error' => $this->pluginaizer->error]);
                } else{
                    echo $this->pluginaizer->jsone(['success' => 'Plugin successfully disabled.']);
                }
            } else{
                echo $this->pluginaizer->jsone(['error' => 'Please login first!']);
            }
        }

        public function about(){
            //check if visitor has administrator privilleges
            if($this->pluginaizer->session->is_admin()){
                //create plugin info
                $about = $this->pluginaizer->get_about();
                if($about != false){
                    $description = '<div class="box-content">
								<dl>
								  <dt>Plugin Name</dt>
								  <dd>' . $about['name'] . '</dd>
								  <dt>Version</dt>
								  <dd>' . $about['version'] . '</dd>
								  <dt>Description</dt>
								  <dd>' . $about['description'] . '</dd>
								  <dt>Developed By</dt>
								  <dd>' . $about['developed_by'] . ' <a href="' . $about['website'] . '" target="_blank">' . $about['website'] . '</a></dd>
								</dl>            
							</div>';
                } else{
                    $description = '<div class="alert alert-info">Unable to find plugin description.</div>';
                }
                echo $this->pluginaizer->jsone(['about' => $description]);
            } else{
                echo $this->pluginaizer->jsone(['error' => 'Please login first!']);
            }
        }

    }