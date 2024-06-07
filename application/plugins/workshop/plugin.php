<?php
    in_file();

    class _plugin_workshop extends controller implements pluginInterface
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
		  
        private function user_module($page = 1){
            //check if visitor has user privilleges
            if($this->pluginaizer->session->is_user()){
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
                        $this->load->model('application/plugins/workshop/models/workshop');
                        $this->load->lib("itemimage");
                        $this->load->lib("iteminfo");
                        $this->vars['char_list'] = $this->pluginaizer->Mworkshop->load_char_list($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
                        foreach($this->vars['char_list'] AS $key => $val){
                            $this->pluginaizer->Mworkshop->char_info($val['id'], $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
                            if(!empty($this->pluginaizer->Mworkshop->char_info) && $this->pluginaizer->Mworkshop->char_info != false){
                                $this->vars['character_info'][$val['id']] = $this->pluginaizer->Mworkshop->char_info;
                                $this->vars['equipment'][$val['id']] = $this->pluginaizer->Mworkshop->load_equipment($this->pluginaizer->session->userdata(['user' => 'server']));
                                $this->vars['inventory'][$val['id']] = $this->pluginaizer->Mworkshop->load_inventory(1, $this->pluginaizer->session->userdata(['user' => 'server']));
                            } else{
                                unset($this->vars['char_list'][$key]);
                            }
                        }
                    }
                } else{
                    $this->vars['config_not_found'] = __('Plugin configuration not found.');
                }
                //load template
                $this->vars['js'] = $this->config->base_url . 'assets/plugins/js/workshop.js';
                $this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . $this->config->config_entry('main|template') . DS . 'view.workshop', $this->vars);
            } else{
                $this->pluginaizer->redirect($this->config->base_url . 'account-panel/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()));
            }
        }
		
		 
        public function get_item_data(){
            if($this->pluginaizer->session->is_user()){
                $this->vars['plugin_config'] = $this->pluginaizer->plugin_config();
                if($this->vars['plugin_config'] != false && !empty($this->vars['plugin_config'])){
                    if($this->pluginaizer->data()->value('is_multi_server') == 1){
                        if(array_key_exists($this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['plugin_config'])){
                            $this->vars['plugin_config'] = $this->vars['plugin_config'][$this->pluginaizer->session->userdata(['user' => 'server'])];
                            $this->vars['about'] = $this->pluginaizer->get_about();
                            $this->vars['about']['user_description'] = $this->pluginaizer->data()->value('description');
                        } else{
                            echo $this->pluginaizer->jsone(['error' => __('Plugin configuration not found.')]);
                        }
                    }
                    if($this->vars['plugin_config']['active'] == 0){
                        echo $this->pluginaizer->jsone(['error' => __('This module has been disabled.')]);
                    } else{
                        if(count($_POST) > 0){
                            //$this->pluginaizer->csrf->verifyToken('post', 'json', 3600, true);
                            $serial = isset($_POST['serial']) ? trim($_POST['serial']) : '';
                            $char = isset($_POST['character']) ? trim($_POST['character']) : '';
                            $this->load->model('application/plugins/workshop/models/workshop');
                            $mu_id = $this->validate_char_id($char);
                            $this->pluginaizer->Mworkshop->char_info($mu_id, $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
                            if(!empty($this->pluginaizer->Mworkshop->char_info) && $this->pluginaizer->Mworkshop->char_info != false){
                                if(($serials = $this->check_serial($serial)) != false){
                                    $this->load->lib("itemimage");
                                    $this->load->lib("iteminfo");
                                    $item_data = $this->pluginaizer->Mworkshop->find_item($serials, $this->pluginaizer->session->userdata(['user' => 'server']));
                                    if(!empty($item_data)){
                                        $slot = key($item_data);
                                        $hex = $item_data[$slot];
                                        $this->pluginaizer->iteminfo->itemData($hex);
										if($this->check_black_list_cat($this->vars['plugin_config']['black_list_items'], $this->pluginaizer->iteminfo->type) != false){
											$shop_data = $this->pluginaizer->Mworkshop->get_item_shop_info($this->pluginaizer->iteminfo->id, $this->pluginaizer->iteminfo->type, $this->vars['plugin_config']['check_socket_part_type']);
											if($shop_data != false){
												if($shop_data['allow_upgrade'] == 1){
													echo $this->pluginaizer->jsone([
														'char' => $char, 
														'base_url' => $this->config->base_url, 
														'config' => $this->vars['plugin_config'], 
														'mu_version' => MU_VERSION, 
														'sr' => $serial, 
														'item' => $shop_data, 
														'level' => (int)substr($this->pluginaizer->iteminfo->getLevel(), 1), 
														'option' => $this->pluginaizer->iteminfo->GetOption(), 
														'luck' => $this->pluginaizer->iteminfo->getLuck(), 
														'skill' => (int)$this->pluginaizer->iteminfo->item_data['skill'], 
														'has_skill' => $this->pluginaizer->iteminfo->hasSkill(), 
														'exe_opts' => $this->pluginaizer->iteminfo->exeOpts(), 
														'sockets' => $this->pluginaizer->iteminfo->socket, 
														'seeds' => $this->pluginaizer->iteminfo->seedsIndex(), 
														'image' => $this->pluginaizer->itemimage->load($shop_data['item_id'], $shop_data['item_cat'], $shop_data['stick_level']), 
														'payment_method' => $this->pluginaizer->website->translate_credits($this->vars['plugin_config']['payment_method'], $this->session->userdata(['user' => 'server'])), 
														'payment_code' => $this->vars['plugin_config']['payment_method'],
														'price' => $shop_data['upgrade_price']
													]);
												}
												else{
													echo $this->pluginaizer->jsone(['error' => __('This item cannot be upgraded.')]);
												}
											} else{
												echo $this->pluginaizer->jsone(['error' => __('Invalid item or item can not be upgraded.')]);
											}
										} else{
											echo $this->pluginaizer->jsone(['error' => __('This item cannot be upgraded. Category blacklisted.')]);
										}
                                    } else{
                                        echo $this->pluginaizer->jsone(['error' => __('Item not found.')]);
                                    }
                                } else{
                                    echo $this->pluginaizer->jsone(['error' => __('Invalid item or item can not be upgraded.')]);
                                }
                            } else{
                                echo $this->pluginaizer->jsone(['error' => __('Unable to load character data')]);
                            }
                        }
                    }
                } else{
                    echo $this->pluginaizer->jsone(['error' => __('Plugin configuration not found.')]);
                }
            } else{
                echo $this->pluginaizer->jsone(['error' => __('Please login into website.')]);
            }
        }
		
		 
        public function upgrade_item(){
            if($this->pluginaizer->session->is_user()){
                $this->vars['plugin_config'] = $this->pluginaizer->plugin_config();
                if($this->vars['plugin_config'] != false && !empty($this->vars['plugin_config'])){
                    if($this->pluginaizer->data()->value('is_multi_server') == 1){
                        if(array_key_exists($this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['plugin_config'])){
                            $this->vars['plugin_config'] = $this->vars['plugin_config'][$this->pluginaizer->session->userdata(['user' => 'server'])];
                            $this->vars['about'] = $this->pluginaizer->get_about();
                            $this->vars['about']['user_description'] = $this->pluginaizer->data()->value('description');
                        } else{
                            echo $this->pluginaizer->jsone(['error' => __('Plugin configuration not found.')]);
                        }
                    }
                    if($this->vars['plugin_config']['active'] == 0){
                        echo $this->pluginaizer->jsone(['error' => __('This module has been disabled.')]);
                    } else{
                        if(count($_POST) > 0){
                            //$this->pluginaizer->csrf->verifyToken('post', 'json', 3600, true);
                            $this->load->model('application/plugins/workshop/models/workshop');
                            $this->vars['serial'] = isset($_POST['sr']) ? trim($_POST['sr']) : '';
                            $this->vars['char'] = isset($_POST['character']) ? trim($_POST['character']) : '';
                            $this->vars['level'] = isset($_POST['item_level']) ? ctype_digit($_POST['item_level']) ? (int)$_POST['item_level'] : 0 : 0;
                            $this->vars['option'] = isset($_POST['item_opt']) ? ctype_digit($_POST['item_opt']) ? (int)$_POST['item_opt'] : 0 : 0;
                            $this->vars['luck'] = (isset($_POST['item_luck']) && $_POST['item_luck'] == 1) ? true : false;
                            $this->vars['skill'] = (isset($_POST['item_skill']) && $_POST['item_skill'] == 1) ? true : false;
                            $this->vars['ancient'] = (isset($_POST['item_anc']) && $_POST['item_anc'] > 0) ? ctype_digit($_POST['item_anc']) ? (int)$_POST['item_anc'] : 0 : 0;
                            $this->vars['exe'] = isset($_POST['exe']) ? $_POST['exe'] : [];
                            for($s_i = 0; $s_i < 5; $s_i++){
                                $this->vars['sockets'][$s_i] = (isset($_POST['socket' . ($s_i + 1)]) && $_POST['socket' . ($s_i + 1)] != 'no') ? preg_match('/\d{1,3}-\d{1,3}/', $_POST['socket' . ($s_i + 1)]) ? explode('-', $_POST['socket' . ($s_i + 1)])[1] : '' : '';
                                $this->vars['seeds'][$s_i] = (isset($_POST['socket' . ($s_i + 1)]) && $_POST['socket' . ($s_i + 1)] != 'no') ? preg_match('/\d{1,3}-\d{1,3}/', $_POST['socket' . ($s_i + 1)]) ? explode('-', $_POST['socket' . ($s_i + 1)])[0] : '' : '';
                            }
                            $this->vars['mu_id'] = $this->validate_char_id($this->vars['char']);
                            $this->pluginaizer->Mworkshop->char_info($this->vars['mu_id'], $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
                            if(!empty($this->pluginaizer->Mworkshop->char_info) && $this->pluginaizer->Mworkshop->char_info != false){
                                if(($serials = $this->check_serial($this->vars['serial'])) != false){
                                    if(!$this->pluginaizer->Mworkshop->check_connect_stat($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server'])))
                                        echo $this->pluginaizer->jsone(['error' => __('Please logout from game.')]); 
									else{
                                        $this->load->model("account");
                                        $this->load->lib("itemimage");
                                        $this->load->lib("iteminfo");
                                        $this->load->lib('createitem', [MU_VERSION, SOCKET_LIBRARY, $this->pluginaizer->website->get_value_from_server($this->pluginaizer->session->userdata(['user' => 'server']), 'item_size')]);
										$item_data = $this->pluginaizer->Mworkshop->find_item($serials, $this->pluginaizer->session->userdata(['user' => 'server']));
                                        if(!empty($item_data)){
                                            $slot = key($item_data);
                                            $this->pluginaizer->iteminfo->itemData($item_data[$slot]);
                                            $shop_data = $this->pluginaizer->Mworkshop->get_item_shop_info($this->pluginaizer->iteminfo->id, $this->pluginaizer->iteminfo->type, $this->vars['plugin_config']['check_socket_part_type']);
                                            if($shop_data != false){
												if($shop_data['allow_upgrade'] == 1){
													$this->pluginaizer->createitem->setItemData($this->pluginaizer->iteminfo->item_data);
													$this->pluginaizer->createitem->id($this->pluginaizer->iteminfo->id);
													$this->pluginaizer->createitem->cat($this->pluginaizer->iteminfo->type);
													$this->pluginaizer->createitem->dur($this->pluginaizer->iteminfo->dur);
													$this->pluginaizer->createitem->refinery((($this->pluginaizer->iteminfo->ref > 0) ? true : false));
													$this->pluginaizer->createitem->harmony($this->pluginaizer->iteminfo->harmony);
													$this->pluginaizer->createitem->serialsFromHex(true);
													$this->pluginaizer->createitem->hex_serial[0] = $serials[0];
													if($this->pluginaizer->website->get_value_from_server($this->session->userdata(['user' => 'server']), 'item_size') == 64){
														$this->pluginaizer->createitem->hex_serial[1] = $serials[1];
													}
													$this->vars['current_level'] = (int)$this->pluginaizer->iteminfo->GetLevel();
													$this->vars['current_option'] = $this->pluginaizer->iteminfo->GetOption();
													$this->vars['current_luck'] = $this->pluginaizer->iteminfo->getLuck();
													$this->vars['current_skill'] = $this->pluginaizer->iteminfo->hasSkill();
													$this->vars['current_exe'] = array_filter($this->pluginaizer->iteminfo->exeOpts(), function($data){
														return ($data !== 0);
													});
													
													$this->vars['current_sockets'] = $this->pluginaizer->iteminfo->socket;
													if($this->vars['plugin_config']['allow_upgrade_level']){
														if($this->vars['current_level'] < $this->vars['plugin_config']['min_level_required']){
															echo $this->pluginaizer->jsone(['error' => __('Your item level is too low for upgrade, minimum: %d level', $this->vars['plugin_config']['min_level_required'])]);
															return;
														}
														if($this->vars['current_level'] > $this->vars['plugin_config']['max_level_allowed']){
															echo $this->pluginaizer->jsone(['error' => __('Your item level is too high for upgrade, maximum: %d level', $this->vars['plugin_config']['max_level_allowed'])]);
															return;
														}
														if($this->vars['level'] > $this->vars['plugin_config']['max_level_allowed']){
															echo $this->pluginaizer->jsone(['error' => __('Your selected level is too high for upgrade, maximum: %d level', $this->vars['plugin_config']['max_level_allowed'])]);
															return;
														}
														if($this->vars['level'] > $this->vars['current_level']){
															$this->pluginaizer->Mworkshop->setPriceForLevel($this->vars['current_level'], $this->vars['level'], $this->vars['plugin_config']['level_price']);
															$this->pluginaizer->createitem->lvl($this->vars['level']);
														} else{
															$this->pluginaizer->Mworkshop->setPriceForLevel($this->vars['current_level'], $this->vars['current_level'], 0);
															$this->pluginaizer->createitem->lvl($this->vars['current_level']);
														}
													} else{
														$this->pluginaizer->Mworkshop->setPriceForLevel($this->vars['current_level'], $this->vars['current_level'], 0);
														$this->pluginaizer->createitem->lvl($this->vars['current_level']);
													}
													if($shop_data['stick_level'] > 0){
														$this->pluginaizer->createitem->stickLvl($shop_data['stick_level']);
													}

													if($this->vars['plugin_config']['allow_upgrade_option']){
														$multiplier = ($this->pluginaizer->iteminfo->type == 6) ? 5 : 4;
														if($this->vars['current_option'] < $this->vars['plugin_config']['min_option_required']){
															echo $this->pluginaizer->jsone(['error' => __('Your item option is too low for upgrade, minimum: %s option', '+'.$this->vars['plugin_config']['min_option_required']*$multiplier)]);
															return;
														}
														if($this->vars['current_option'] > $this->vars['plugin_config']['max_option_allowed']){
															echo $this->pluginaizer->jsone(['error' => __('Your item option is too high for upgrade, maximum: %s option', '+'.$this->vars['plugin_config']['max_option_allowed']*$multiplier)]);
															return;
														}
														if($this->vars['option'] > $this->vars['plugin_config']['max_option_allowed']){
															echo $this->pluginaizer->jsone(['error' => __('Your selected option is too high for upgrade, maximum: %s option', '+'.$this->vars['plugin_config']['max_option_allowed']*$multiplier)]);
															return;
														}
														if($this->vars['option'] > $this->vars['current_option']){
															$this->pluginaizer->Mworkshop->setPriceForOption($this->vars['current_option'], $this->vars['option'], $this->vars['plugin_config']['option_price']);
															$this->pluginaizer->createitem->opt($this->vars['option']);
														} else{
															$this->pluginaizer->Mworkshop->setPriceForOption($this->vars['current_option'], $this->vars['current_option'], 0);
															$this->pluginaizer->createitem->opt($this->vars['current_option']);
														}
													} else{
														$this->pluginaizer->Mworkshop->setPriceForOption($this->vars['current_option'], $this->vars['current_option'], 0);
														$this->pluginaizer->createitem->opt($this->vars['current_option']);
													}
													if($this->vars['plugin_config']['allow_add_luck']){
														if($this->vars['current_luck'] == 1){
															$this->pluginaizer->Mworkshop->setPriceForLuck(0);
															$this->pluginaizer->createitem->luck((bool)$this->vars['current_luck']);
														} else{
															if($this->vars['luck'] != false){
																$this->pluginaizer->Mworkshop->setPriceForLuck($this->vars['plugin_config']['luck_price']);
																$this->pluginaizer->createitem->luck($this->vars['luck']);
															} else{
																$this->pluginaizer->Mworkshop->setPriceForLuck(0);
																$this->pluginaizer->createitem->luck((bool)$this->vars['current_luck']);
															}
														}
													} else{
														$this->pluginaizer->Mworkshop->setPriceForLuck(0);
														$this->pluginaizer->createitem->luck((bool)$this->vars['current_luck']);
													}
													if($this->vars['plugin_config']['allow_add_skill']){
														if($this->vars['current_skill'] == 1){
															$this->pluginaizer->Mworkshop->setPriceForSkill(0);
															$this->pluginaizer->createitem->skill((bool)$this->vars['current_skill']);
														} else{
															if($this->vars['skill'] != false){
																$this->pluginaizer->Mworkshop->setPriceForSkill($this->vars['plugin_config']['skill_price']);
																$this->pluginaizer->createitem->skill((bool)$this->vars['skill']);
															} else{
																$this->pluginaizer->Mworkshop->setPriceForSkill(0);
																$this->pluginaizer->createitem->skill((bool)$this->vars['current_skill']);
															}
														}
													} else{
														$this->pluginaizer->Mworkshop->setPriceForSkill(0);
														$this->pluginaizer->createitem->skill((bool)$this->vars['current_skill']);
													}
													
													$this->vars['exe2'] = $this->vars['exe'];
													$this->vars['do_upgrade_exe'] = false;

													if($this->vars['plugin_config']['allow_add_exe']){
														if($shop_data['exetype'] != -1){
															if(count($this->vars['exe']) > 0){
																foreach($this->vars['exe'] AS $key => $exe){
																	if(in_array($exe, $this->vars['current_exe'])){
																		unset($this->vars['exe'][$key]);
																	}
																}
																
																$exe_options = array_merge($this->vars['current_exe'], $this->vars['exe']);
																
																if(count($exe_options) > $this->vars['plugin_config']['max_exe_opt']){
																	echo $this->pluginaizer->jsone(['error' => sprintf(__('Max exellent options allowed %s'), $this->vars['plugin_config']['max_exe_opt'])]);
																	return;
																} else{
																	$this->vars['do_upgrade_exe'] = true;
																	$this->pluginaizer->Mworkshop->setPriceForExe($this->vars['plugin_config']['exe_opt_price'], count($this->vars['exe']));
																	$this->pluginaizer->createitem->exe($exe_options);
																}
															} else{	
																$this->pluginaizer->Mworkshop->setPriceForExe(0, 0);
																$this->pluginaizer->createitem->exe($this->vars['current_exe']);
															}
														} else{
															$this->pluginaizer->Mworkshop->setPriceForExe(0, 0);
															$this->pluginaizer->createitem->exe($this->vars['current_exe']);
														}
													} else{
														$this->pluginaizer->Mworkshop->setPriceForExe(0, 0);
														$this->pluginaizer->createitem->exe($this->vars['current_exe']);
													}

													if($this->vars['plugin_config']['allow_remove_exe']){
														if($shop_data['exetype'] != -1){
															foreach($this->vars['current_exe'] AS $key => $exe){
																if(!in_array($exe, $this->vars['exe2'])){
																	unset($this->vars['current_exe'][$exe]);
																	$this->pluginaizer->Mworkshop->setPriceForRemoveExe($this->vars['plugin_config']['remove_exe_opt_price']);
																}
															}
															if(empty($this->vars['current_exe']) && !$this->vars['do_upgrade_exe']){
																$this->pluginaizer->createitem->exe([]);
															}
															else{
																$exe_options = array_merge($this->vars['current_exe'], $this->vars['exe']);
																$this->pluginaizer->createitem->exe($exe_options);
															}
														}
													}

													$this->pluginaizer->createitem->ancient($this->pluginaizer->iteminfo->ancient);
													$this->pluginaizer->createitem->fenrir(0);
													if($this->vars['plugin_config']['allow_add_socket']){
														if($shop_data['use_sockets'] == 1){
															$seeds_index = $this->pluginaizer->iteminfo->seedsIndex();
															if(!empty($seeds_index)){
																$this->pluginaizer->createitem->is_socket_ancient = true;
																$this->pluginaizer->createitem->is_socket_exe = true;
																if(!isset($seeds_index[1]) && !isset($seeds_index[2]) && !isset($seeds_index[3]) && !isset($seeds_index[4])){
																	$this->pluginaizer->createitem->ancient(0);
																}
																if(!isset($seeds_index[5])){
																	$this->pluginaizer->createitem->removeExe(16);
																}
															}
															foreach($this->vars['sockets'] as $key => $socket){
																if($this->vars['plugin_config']['check_socket_part_type'] == 1){
																	$socket_info = $this->pluginaizer->Mworkshop->check_sockets_part_type($socket, $this->pluginaizer->iteminfo->type, $this->vars['seeds'][$key]);
																} else{
																	$socket_info = $this->pluginaizer->Mworkshop->check_sockets($socket, $this->vars['seeds'][$key]);
																}
																if($socket_info != false){
																	if($socket_info['value'] >= 7){
																		if($socket_info['socket_id'] != $this->pluginaizer->iteminfo->socket[$key + 1]){
																			if($key + 1 == 1){
																				$this->pluginaizer->createitem->is_socket_ancient = true;
																				$this->pluginaizer->createitem->addAncient(64);
																			}
																			if($key + 1 == 2){
																				$this->pluginaizer->createitem->is_socket_ancient = true;
																				$this->pluginaizer->createitem->addAncient(16);
																			}
																			if($key + 1 == 3){
																				$this->pluginaizer->createitem->is_socket_ancient = true;
																				$this->pluginaizer->createitem->addAncient(4);
																			}
																			if($key + 1 == 4){
																				$this->pluginaizer->createitem->is_socket_ancient = true;
																				$this->pluginaizer->createitem->addAncient(1);
																			}
																			if($key + 1 == 5){
																				$this->pluginaizer->createitem->is_socket_exe = true;
																				$this->pluginaizer->createitem->addExe(16);
																			}
																			$this->pluginaizer->Mworkshop->setPriceForSocket($socket_info['socket_price']);
																		}
																	} else{
																		if($socket_info['socket_id'] != 254){
																			if($socket_info['socket_id'] != $this->pluginaizer->iteminfo->socket[$key + 1]){
																				$this->pluginaizer->Mworkshop->setPriceForSocket($socket_info['socket_price']);
																			}
																		}
																	}
																} else{
																	echo $this->pluginaizer->jsone(['error' => __('Wrong socket option. Socket: ' . $socket)]);
																	return;
																}
															}
															$this->pluginaizer->createitem->socket($this->vars['sockets']);
														} else{
															$this->pluginaizer->Mworkshop->setPriceForSocket(0);
															$this->pluginaizer->createitem->socket($this->pluginaizer->iteminfo->socket);
														}
													} else{
														$this->pluginaizer->Mworkshop->setPriceForSocket(0);
														$this->pluginaizer->createitem->socket($this->pluginaizer->iteminfo->socket);
													}
													
													$this->pluginaizer->Mworkshop->items_array[$slot] = $this->pluginaizer->createitem->to_hex();
													
													if($this->pluginaizer->Mworkshop->items_array[$slot] != $this->pluginaizer->iteminfo->hex){
														$this->Mworkshop->setPriceForUpgrade($shop_data['upgrade_price']);
													}

													$status = $this->Maccount->get_amount_of_credits($this->session->userdata(['user' => 'username']), $this->vars['plugin_config']['payment_method'], $this->session->userdata(['user' => 'server']), $this->session->userdata(['user' => 'id']));
													if($status < $this->pluginaizer->Mworkshop->price){
														echo $this->pluginaizer->jsone(['error' => sprintf(__('You have insufficient amount of %s'), $this->pluginaizer->website->translate_credits($this->vars['plugin_config']['payment_method'], $this->session->userdata(['user' => 'server'])))]);
													} else{
														$this->pluginaizer->website->charge_credits($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $this->pluginaizer->Mworkshop->price, $this->vars['plugin_config']['payment_method']);
														$this->Maccount->add_account_log('Upgraded Item For ' . $this->pluginaizer->website->translate_credits($this->vars['plugin_config']['payment_method'], $this->session->userdata(['user' => 'server'])) . '', -$this->pluginaizer->Mworkshop->price, $this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
														$this->pluginaizer->Mworkshop->logUpgrade($item_data[$slot], $this->pluginaizer->Mworkshop->items_array[$slot], $this->pluginaizer->Mworkshop->price, $this->vars['plugin_config']['payment_method'], $this->vars['mu_id'], $this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
														$this->pluginaizer->Mworkshop->upgradeItem($this->vars['mu_id'], $this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
														echo $this->pluginaizer->jsone(['success' => __('Thank You, item was upgraded.'), 'price' => $this->pluginaizer->Mworkshop->price, 'payment_method' => $this->vars['plugin_config']['payment_method'], 'old_hex' => $item_data[$slot], 'new_hex' => $this->pluginaizer->Mworkshop->items_array[$slot]]);
													}
												}
												else{
													echo $this->pluginaizer->jsone(['error' => __('This item cannot be upgraded.')]);
												}
                                            } else{
                                                echo $this->pluginaizer->jsone(['error' => __('Invalid item or item can not be upgraded.')]);
                                            }
                                        }
                                    }
                                } else{
                                    echo $this->pluginaizer->jsone(['error' => __('Invalid item or item can not be upgraded.')]);
                                }
                            } else{
                                echo $this->pluginaizer->jsone(['error' => __('Unable to load character data')]);
                            }
                        }
                    }
                } else{
                    echo $this->pluginaizer->jsone(['error' => __('Plugin configuration not found.')]);
                }
            } else{
                echo $this->pluginaizer->jsone(['error' => __('Please login into website.')]);
            }
        }

        public function logs($page = 1, $acc = '-', $server = 'All'){
            //check if visitor has administrator privilleges
            if($this->pluginaizer->session->is_admin()){
                //load website helper
                $this->load->helper('website');
                //load paginator
                $this->load->lib('pagination');
                $this->load->model('application/plugins/workshop/models/workshop');
                if(isset($_POST['server'])){
                    $server = (isset($_POST['server']) && $_POST['server'] != 'All') ? $_POST['server'] : 'All';
                    $acc = isset($_POST['account']) ? $_POST['account'] : '';
                    if($acc == ''){
                        $this->vars['error'] = 'Invalid account';
                    } else{
                        $this->vars['logs'] = $this->pluginaizer->Mworkshop->load_logs(1, 25, $acc, $server);
                        $this->pluginaizer->pagination->initialize(1, 25, $this->pluginaizer->Mworkshop->count_total_logs($acc, $server), $this->config->base_url . 'workshop/logs/%s/' . $acc . '/' . $server . '');
                        $this->vars['pagination'] = $this->pluginaizer->pagination->create_links();
                    }
                } else{
                    $this->vars['logs'] = $this->pluginaizer->Mworkshop->load_logs($page, 25, $acc, $server);
                    $lk = '';
                    if($acc != '')
                        $lk .= '/' . $acc;
                    $lk .= '/' . $server;
                    $this->pluginaizer->pagination->initialize($page, 25, $this->pluginaizer->Mworkshop->count_total_logs($acc, $server), $this->config->base_url . 'workshop/logs/%s' . $lk);
                    $this->vars['pagination'] = $this->pluginaizer->pagination->create_links();
                }
                $this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . 'admin' . DS . 'view.logs', $this->vars);
            } else{
                $this->pluginaizer->redirect($this->config->base_url . 'admincp/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()) . '/logs');
            }
        }

		  
        private function check_serial($serial){
            if(strpos($serial, '-') !== false){
                $serials = explode('-', $serial);
                $blocked1 = '00000000';
                $blocked2 = 'FFFFFFFF';
                if($serials[1] != '0'){
                    if(($serials[0] === $blocked1 || $serials[0] === $blocked2) && ($serials[1] === $blocked1 || $serials[1] === $blocked2)){
                        return false;
                    }
                } else{
                    if($serials[0] === $blocked1 || $serials[0] === $blocked2){
                        return false;
                    }
                }
                return $serials;
            }
            return false;
        }
		
		  
        private function check_black_list_cat($blacklist = '', $cat){
			if($blacklist != ''){
				if(str_contains($blacklist, ',')){
					$blist = explode(',', $blacklist);
					if(in_array($cat, $blist))
						return false;
				}
				else{
					if($blacklist == $cat)
						return false;
				}
			}
			return true;
			
		}
		
		  
        private function validate_char_id($char){
            if(strpos($char, '-id') !== false){
                $mu_id = substr(strrchr($char, '-id'), 3);
                if(is_numeric($mu_id)){
                    return $mu_id;
                } else{
                    throw new Exception(__('Invalid character'));
                }
            } else{
                throw new Exception(__('Invalid character'));
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
                //$this->load->helper('website');
                $this->load->model('application/plugins/workshop/models/workshop');
                $this->vars['is_multi_server'] = $this->pluginaizer->data()->value('is_multi_server');
                $this->vars['plugin_config'] = $this->pluginaizer->plugin_config();
                //load any js, css files if required
                $this->vars['js'] = $this->config->base_url . 'assets/plugins/js/workshop.js';
                //load template
                $this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . 'admin' . DS . 'view.index', $this->vars);
            } else{
                $this->pluginaizer->redirect($this->config->base_url . 'admincp/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()) . '/admin');
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
                    echo $this->pluginaizer->jsone(['success' => __('Plugin configuration successfully saved')]);
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
                    'sidebar_public_item' => 0, //add link to module in public sidebar menu, if template supports
                    'account_panel_item' => 1, //add link in user account panel
                    'donation_panel_item' => 0, //add link in donation page
                    'description' => 'Item WorkShop' //description which will see user
                ]);
                //create plugin config template
                $this->pluginaizer->create_config(['active' => 0, 'allow_upgrade_level' => 1, 'min_level_required' => 0, 'max_level_allowed' => 15, 'level_price' => 50, 'allow_upgrade_option' => 1, 'min_option_required' => 0, 'max_option_allowed' => 7, 'option_price' => 50, 'allow_add_luck' => 1, 'luck_price' => 50, 'allow_add_skill' => 1, 'skill_price' => 50, 'allow_add_exe' => 1, 'allow_remove_exe' => 0, 'max_exe_opt' => 6, 'exe_opt_price' => 50, 'remove_exe_opt_price' => 50, 'allow_add_ancient' => 1, 'ancient_opt_price' => 50, 'allow_add_refinery' => 1, 'refinery_opt_price' => 50, 'allow_add_harmony' => 1, 'harmony_opt_price' => 50, 'allow_add_socket' => 1, 'check_socket_part_type' => 1, 'allow_equal_seed' => 0, 'allow_equal_sockets' => 0, 'socket_opt_price' => 50, 'black_list_items' => '', 'payment_method' => 1]);
                //add sql scheme if there is any into website database
                //all schemes should be located in plugin_folder/sql_schemes
                $this->pluginaizer->add_sql_scheme('item_upgrade_log');
                //check for errors
                if(count($this->pluginaizer->error) > 0){
                    $data['error'] = $this->pluginaizer->error;
                }
                $data['success'] = __('Plugin installed successfully');
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
                $this->pluginaizer->delete_config()->remove_sql_scheme('item_upgrade_log')->remove_plugin();
                //check for errors
                if(count($this->pluginaizer->error) > 0){
                    $data['error'] = $this->pluginaizer->error;
                }
                $data['success'] = 'Plugin uninstalled successfully';
                echo $this->pluginaizer->jsone($data);
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