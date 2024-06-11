<?php
	
    class _plugin_vip_rewards extends controller implements pluginInterface
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
        public function index($server = ''){
            if($this->pluginaizer->data()->value('installed') == false){
                throw new Exception('Plugin has not yet been installed.');
            } else{
                if($this->pluginaizer->data()->value('installed') == 1){
                    if($this->pluginaizer->data()->value('is_public') == 0){
                        $this->user_module();
                    } else{
                        $this->public_module($server);
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
						$this->load->model('application/plugins/' . $this->pluginaizer->get_plugin_class() . '/models/' . $this->pluginaizer->get_plugin_class());     
						
						$this->vars['vip_package'] = $this->pluginaizer->Mvip_rewards->check_existing_vip_package($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['plugin_config']['vip_type']);
						if($this->vars['vip_package'] == false){
							$this->vars['module_disabled'] = __('Your not a vip user.');
						}
						else{
							$curVipTime = ($this->vars['plugin_config']['vip_type'] == 2) ? strtotime($this->vars['vip_package']['viptime']) : $this->vars['vip_package']['viptime'];
							if($curVipTime <= time()){
								$this->vars['module_disabled'] = __('Your vip has expired, please renew.');
							}
							else{
								
								$this->vars['reward_list'] = $this->config->values('vip_rewards_list', $this->pluginaizer->session->userdata(['user' => 'server']));
								if(empty($this->vars['reward_list'][$this->vars['vip_package']['viptype']])){
									$this->vars['module_disabled'] = __('No rewards for your vip plan.');
								}
								else{
									$this->load->lib('iteminfo');
									$this->load->lib('createitem', [MU_VERSION, SOCKET_LIBRARY, $this->pluginaizer->website->get_value_from_server($this->pluginaizer->session->userdata(['user' => 'server']), 'item_size')]);
									
									foreach($this->vars['reward_list'][$this->vars['vip_package']['viptype']] AS $type => $rewards){
										if($rewards['status'] == 1){
											if(!empty($rewards['items'])){
												$this->vars['reward_items'][$type] = [];
												foreach($rewards['items'] AS $ritem){
													if($this->pluginaizer->iteminfo->setItemData($ritem['id'], $ritem['cat'], $this->pluginaizer->website->get_value_from_server($this->pluginaizer->session->userdata(['user' => 'server']), 'item_size'))){
														$this->pluginaizer->createitem->setItemData($this->pluginaizer->iteminfo->item_data);
														$this->pluginaizer->createitem->id($ritem['id']);
														$this->pluginaizer->createitem->cat($ritem['cat']);
														$this->pluginaizer->createitem->refinery(false);
														
														if($ritem['expires'] != ''){
															$this->pluginaizer->createitem->expirable();
														}
														if(isset($ritem['dur']) && $ritem['dur'] != ''){
															$this->pluginaizer->createitem->dur($ritem['dur']);
														}
														$this->pluginaizer->createitem->serial(0);
														if($this->pluginaizer->website->get_value_from_server($this->pluginaizer->session->userdata(['user' => 'server']), 'item_size') == 64){
															$this->createitem->serial2(true);
														}
														if($ritem['lvl'] != ''){
															$this->pluginaizer->createitem->lvl($ritem['lvl']);
														}
														else{
															$this->pluginaizer->createitem->lvl(0);
														}
														if($ritem['skill'] != '' && $ritem['skill'] == 1){
															$this->pluginaizer->createitem->skill(true);
														}
														else{
															$this->pluginaizer->createitem->skill(false);
														}
														if($ritem['luck'] != '' && $ritem['luck'] == 1){
															$this->pluginaizer->createitem->luck(true);
														}
														else{
															$this->pluginaizer->createitem->luck(false);
														}
														if($ritem['opt'] != ''){
															$this->pluginaizer->createitem->opt($ritem['opt']);
														}
														else{
															$this->pluginaizer->createitem->opt(0);
														}
														if($ritem['exe'] != ''){
															$exe_opts = [0 => 1, 1 => 2, 2 => 4, 3 => 8, 4 => 16, 5 => 32];
															$iexe = explode(',', $ritem['exe']);
															$exe = 0;
															
															foreach($iexe AS $k => $val){
																if($val == 0){
																	unset($iexe[$k]);
																}
															}
															
															if(!empty($iexe)){		
																foreach($iexe as $key => $exe_opt){
																	if($exe_opt == 1){
																		$exe += $exe_opts[$key];
																	}
																}
															}
															$this->pluginaizer->createitem->addStaticExe($exe);
														}
														else{
															$this->pluginaizer->createitem->addStaticExe(0);
														}
														if($ritem['anc'] != '' && $ritem['anc'] != 0){
															$this->pluginaizer->createitem->ancient($ritem['anc']);
														}

														$itemHex = $this->pluginaizer->createitem->to_hex();
														$this->pluginaizer->iteminfo->itemData($itemHex, true, $this->pluginaizer->session->userdata(['user' => 'server']));
														$this->vars['reward_items'][$type][] = [
															'hex' => $itemHex,
															'name' => $this->pluginaizer->iteminfo->getNameStyle(true),
															'serial' => 00000000,
															'expires' => $ritem['expires']
														];
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
                    $this->vars['config_not_found'] = __('Plugin configuration not found.');
                }
                
                //load template
                $this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . $this->config->config_entry('main|template') . DS . 'view.vip_rewards', $this->vars);
            } else{
                $this->pluginaizer->redirect($this->config->base_url . 'account-panel/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()));
            }
        }
		
		
		public function claim($type, $viptype){
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
						$this->load->model('application/plugins/' . $this->pluginaizer->get_plugin_class() . '/models/' . $this->pluginaizer->get_plugin_class());     
						$this->load->model('shop');
						$this->vars['vip_package'] = $this->pluginaizer->Mvip_rewards->check_existing_vip_package($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['plugin_config']['vip_type']);
						if($this->vars['vip_package'] == false){
							$this->vars['module_disabled'] = __('Your not a vip user.');
						}
						else{
							$curVipTime = ($this->vars['plugin_config']['vip_type'] == 2) ? strtotime($this->vars['vip_package']['viptime']) : $this->vars['vip_package']['viptime'];
							if($curVipTime <= time()){
								$this->vars['module_disabled'] = __('Your vip has expired, please renew.');
							}
							else{
								$this->vars['reward_list'] = $this->config->values('vip_rewards_list', $this->pluginaizer->session->userdata(['user' => 'server']));
								if(empty($this->vars['reward_list'][$viptype][$type])){
									$this->vars['module_disabled'] = __('Reward not found.');
								}
								else{
									if($this->vars['vip_package']['viptype'] != $viptype){
										$this->vars['module_disabled'] = __('This reward is for other vip plan.');
									}
									else{
										$this->pluginaizer->Mvip_rewards->load_char_list($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
										$this->vars['characters'] = $this->pluginaizer->Mvip_rewards->characters;
										
										if(isset($_POST['claim'])){
											$id = isset($_POST['character']) ? $_POST['character'] : '';
											
											try{										
												if(isset($this->vars['reward_list'][$viptype][$type]['min_vip_days'])){
													$this->vars['daysLeft'] = floor(abs(time() - $curVipTime) / 86400);
													if($this->vars['daysLeft'] < $this->vars['reward_list'][$viptype][$type]['min_vip_days'])
														throw new Exception(sprintf(__('Your vip left less than %d days, please renew.'), $this->vars['reward_list'][$viptype][$type]['min_vip_days']));
												}
												
												if($this->pluginaizer->Mvip_rewards->checkClaimedReward($type, $viptype, $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server'])) != false){
													throw new Exception(__('You have already claimed this reward.'));
												}
												
												$this->pluginaizer->Mvip_rewards->char_info($id, $this->pluginaizer->session->userdata(['user' => 'server']), true);
												
												if($this->pluginaizer->Mvip_rewards->char_info == false){
													throw new Exception(__('Invalid character'));
												}
												
												$this->load->lib('iteminfo');
												$this->load->lib('createitem', [MU_VERSION, SOCKET_LIBRARY, $this->pluginaizer->website->get_value_from_server($this->pluginaizer->session->userdata(['user' => 'server']), 'item_size')]);
												
												if(!empty($this->vars['reward_list'][$viptype][$type]['items'])){
													$this->vars['reward_items'] = [];
													
													foreach($this->vars['reward_list'][$viptype][$type]['items'] AS $ritem){													
														if($this->pluginaizer->iteminfo->setItemData($ritem['id'], $ritem['cat'], $this->pluginaizer->website->get_value_from_server($this->pluginaizer->session->userdata(['user' => 'server']), 'item_size'))){	
															$this->pluginaizer->createitem->setItemData($this->pluginaizer->iteminfo->item_data);
															$this->pluginaizer->createitem->id($ritem['id']);
															$this->pluginaizer->createitem->cat($ritem['cat']);
															$this->pluginaizer->createitem->refinery(false);
															
															if($ritem['expires'] != ''){
																$this->pluginaizer->createitem->expirable();
															}
															if(isset($ritem['dur']) && $ritem['dur'] != ''){
																$this->pluginaizer->createitem->dur($ritem['dur']);
															}
															$serial = $this->pluginaizer->Mshop->generate_serial($this->pluginaizer->session->userdata(['user' => 'server']));
															
															$this->pluginaizer->createitem->serial($serial);
															if($this->pluginaizer->website->get_value_from_server($this->pluginaizer->session->userdata(['user' => 'server']), 'item_size') == 64){
																$this->createitem->serial2(true);
															}
															if($ritem['lvl'] != ''){
																$this->pluginaizer->createitem->lvl($ritem['lvl']);
															}
															else{
																$this->pluginaizer->createitem->lvl(0);
															}
															if($ritem['skill'] != '' && $ritem['skill'] == 1){
																$this->pluginaizer->createitem->skill(true);
															}
															else{
																$this->pluginaizer->createitem->skill(false);
															}
															if($ritem['luck'] != '' && $ritem['luck'] == 1){
																$this->pluginaizer->createitem->luck(true);
															}
															else{
																$this->pluginaizer->createitem->luck(false);
															}
															if($ritem['opt'] != ''){
																$this->pluginaizer->createitem->opt($ritem['opt']);
															}
															else{
																$this->pluginaizer->createitem->opt(0);
															}
															if($ritem['exe'] != ''){
																$exe_opts = [0 => 1, 1 => 2, 2 => 4, 3 => 8, 4 => 16, 5 => 32];
																$iexe = explode(',', $ritem['exe']);
																$exe = 0;
																
																foreach($iexe AS $k => $val){
																	if($val == 0){
																		unset($iexe[$k]);
																	}
																}
																
																if(!empty($iexe)){		
																	foreach($iexe as $key => $exe_opt){
																		if($exe_opt == 1){
																			$exe += $exe_opts[$key];
																		}
																	}
																}
																$this->pluginaizer->createitem->addStaticExe($exe);
															}
															else{
																$this->pluginaizer->createitem->addStaticExe(0);
															}
															if($ritem['anc'] != '' && $ritem['anc'] != 0){
																$this->pluginaizer->createitem->ancient($ritem['anc']);
															}

															$itemHex = $this->pluginaizer->createitem->to_hex();
															$this->pluginaizer->iteminfo->itemData($itemHex, true, $this->pluginaizer->session->userdata(['user' => 'server']));
															$this->vars['reward_items'][] = [
																'hex' => $itemHex,
																'name' => $this->pluginaizer->iteminfo->getNameStyle(true),
																'serial' => $serial,
																'expires' => $ritem['expires'],
																'itemtype' => $this->pluginaizer->iteminfo->itemIndex($ritem['cat'], $ritem['id'])
															];
														}
													}
												}
												
												if((isset($this->vars['reward_list'][$viptype][$type]['wcoin']) && $this->vars['reward_list'][$viptype][$type]['wcoin'] > 0) || 
												(isset($this->vars['reward_list'][$viptype][$type]['goblin']) && $this->vars['reward_list'][$viptype][$type]['goblin'] > 0) ||
												(isset($this->vars['reward_list'][$viptype][$type]['zen']) && $this->vars['reward_list'][$viptype][$type]['zen'] > 0) ||
												(isset($this->vars['reward_list'][$viptype][$type]['ruud']) && $this->vars['reward_list'][$viptype][$type]['ruud'] > 0) ||
												(!empty($this->vars['reward_list'][$viptype][$type]['items']))){
													if(!$this->pluginaizer->Mvip_rewards->check_connect_stat($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']))){
														throw new Exception(__('Please logout from game.'));
													}
												}
												
												if(isset($this->vars['reward_list'][$viptype][$type]['zen']) && $this->vars['reward_list'][$viptype][$type]['zen'] > 0){
													$zen = $this->pluginaizer->Mvip_rewards->checkZen($id, $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
													if(($zen['Money'] + $this->vars['reward_list'][$viptype][$type]['zen']) > 2000000000){
														throw new Exception('Zen limit reached on character.');
													}
												}
												
												if(isset($this->vars['reward_list'][$viptype][$type]['ruud']) && $this->vars['reward_list'][$viptype][$type]['ruud'] > 0){
													$ruud = $this->pluginaizer->Mvip_rewards->checkRuud($id, $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
													if(($ruud['Ruud'] + $this->vars['reward_list'][$viptype][$type]['ruud']) > 2000000000){
														throw new Exception('Ruud limit reached on character.');
													}
												}
												
												if(isset($this->vars['reward_list'][$viptype][$type]['wcoin']) && $this->vars['reward_list'][$viptype][$type]['wcoin'] > 0){
													$this->vars['table_config'] = $this->pluginaizer->config->values('table_config', $this->pluginaizer->session->userdata(['user' => 'server']));
													if(!isset($this->vars['table_config']['wcoins']))
														throw new Exception(__('WCoins configuration not found'));
													if($this->vars['table_config']['wcoins']['table'] == '')
														throw new Exception(__('WCoins configuration not found'));
												}
												
												if(isset($this->vars['reward_list'][$viptype][$type]['goblin']) && $this->vars['reward_list'][$viptype][$type]['goblin'] > 0){
													$this->vars['table_config'] = $this->pluginaizer->config->values('table_config', $this->pluginaizer->session->userdata(['user' => 'server']));
													if(!isset($this->vars['table_config']['goblinpoint']))
														throw new Exception(__('GoblinPoint configuration not found'));
													if($this->vars['table_config']['goblinpoint']['table'] == '')
														throw new Exception(__('GoblinPoint configuration not found'));
												}
												
												if(!empty($this->vars['reward_list'][$viptype][$type]['items'])){
													$this->pluginaizer->Mvip_rewards->inventory($id, $this->pluginaizer->session->userdata(['user' => 'server']));
													$items = $this->pluginaizer->Mvip_rewards->getInventoryContents($this->pluginaizer->session->userdata(['user' => 'server']));
													$itemsList = implode('', $items);
													$itemInfo = $this->pluginaizer->iteminfo;
													$itemArr = str_split($itemsList, $this->pluginaizer->website->get_value_from_server($this->pluginaizer->session->userdata(['user' => 'server']), 'item_size'));
													$takenSlots = [];
													$expirableItems = [];
													foreach($this->vars['reward_items'] AS $ritems){
														$this->pluginaizer->iteminfo->itemData($ritems['hex'], true, $this->pluginaizer->session->userdata(['user' => 'server']));
														$space = $this->pluginaizer->Mvip_rewards->check_space_inventory($itemArr, $this->pluginaizer->iteminfo->getX(), $this->pluginaizer->iteminfo->getY(), 64, $this->pluginaizer->website->get_value_from_server($this->pluginaizer->session->userdata(['user' => 'server']), 'item_size'), 8, 8, false, $itemInfo, $takenSlots);
														
														if($space === null){
															throw new Exception($this->Mvip_rewards->errors[0]);
														}
														$takenSlots[$space] = $space;
														$itemArr[$space] = $ritems['hex'];
														if($ritems['expires'] != ''){
															$expirableItems[] = [
																'index' => $ritems['itemtype'],
																'time' => $ritems['expires'],
																'serial' => $ritems['serial']
															];
														}													
													}
												}
												
												if(isset($this->vars['reward_list'][$viptype][$type]['credits1']) && $this->vars['reward_list'][$viptype][$type]['credits1'] > 0){
													$this->pluginaizer->website->add_credits($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['reward_list'][$viptype][$type]['credits1'], 1, false, $this->pluginaizer->Mvip_rewards->get_guid($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server'])));	
												}
												
												if(isset($this->vars['reward_list'][$viptype][$type]['credits2']) && $this->vars['reward_list'][$viptype][$type]['credits2'] > 0){
													$this->pluginaizer->website->add_credits($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['reward_list'][$viptype][$type]['credits2'], 2, false, $this->pluginaizer->Mvip_rewards->get_guid($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server'])));							
												}
												if(isset($this->vars['reward_list'][$viptype][$type]['wcoin']) && $this->vars['reward_list'][$viptype][$type]['wcoin'] > 0){
													$acc = (in_array($this->vars['table_config']['wcoins']['identifier_column'], ['MemberGuid', 'memb_guid'])) ? $this->pluginaizer->session->userdata(['user' => 'id']) : $this->pluginaizer->session->userdata(['user' => 'username']);
													$this->pluginaizer->Mvip_rewards->add_wcoins($acc, $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['reward_list'][$viptype][$type]['wcoin'], $this->vars['table_config']['wcoins']);
												}
												if(isset($this->vars['reward_list'][$viptype][$type]['goblin']) && $this->vars['reward_list'][$viptype][$type]['goblin'] > 0){
													$acc = (in_array($this->vars['table_config']['goblinpoint']['identifier_column'], ['MemberGuid', 'memb_guid'])) ? $this->pluginaizer->session->userdata(['user' => 'id']) : $this->pluginaizer->session->userdata(['user' => 'username']);
													$this->pluginaizer->Mvip_rewards->add_wcoins($acc, $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['reward_list'][$viptype][$type]['goblin'], $this->vars['table_config']['goblinpoint']);					
												}
												if(isset($this->vars['reward_list'][$viptype][$type]['zen']) && $this->vars['reward_list'][$viptype][$type]['zen'] > 0){
													$this->pluginaizer->Mvip_rewards->add_zen($this->vars['reward_list'][$viptype][$type]['zen'], $id, $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));							
												}
												if(isset($this->vars['reward_list'][$viptype][$type]['credits3']) && $this->vars['reward_list'][$viptype][$type]['credits3'] > 0){
													$this->pluginaizer->website->add_credits($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['reward_list'][$viptype][$type]['credits3'], 3, false, $this->pluginaizer->Mvip_rewards->get_guid($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server'])));							
												}
												if(isset($this->vars['reward_list'][$viptype][$type]['ruud']) && $this->vars['reward_list'][$viptype][$type]['ruud'] > 0){
													$this->pluginaizer->Mvip_rewards->add_ruud($this->vars['reward_list'][$viptype][$type]['ruud'], $id, $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));						
												}
												if(!empty($this->vars['reward_list'][$viptype][$type]['items'])){
													$newInv = $this->pluginaizer->Mvip_rewards->addItemsToInventory($itemArr, $this->pluginaizer->session->userdata(['user' => 'server']));
													if(!empty($expirableItems)){
														$currTime = time();
														foreach($expirableItems AS $expideData){
															$this->pluginaizer->Mvip_rewards->addExpirableItem($this->pluginaizer->session->userdata(['user' => 'id']), $this->pluginaizer->session->userdata(['user' => 'server']), $this->pluginaizer->Mvip_rewards->char_info['Name'], $expideData['index'], $expideData['time'], $expideData['serial'], $currTime);
														}
													}
													$this->pluginaizer->Mvip_rewards->updateInventory($id, $this->pluginaizer->session->userdata(['user' => 'server']), $newInv);
												}
												
												$this->pluginaizer->Mvip_rewards->add_account_log(sprintf(__('Claimed %s %s reward'), $this->pluginaizer->Mvip_rewards->typeToReadable($type), $this->pluginaizer->session->userdata(['vip' => 'title'])), 0, $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
												$this->pluginaizer->Mvip_rewards->logReward($type, $viptype, $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
												$this->vars['success'] = __('Rewards successfully claimed.');
								
											}
											catch(Exception $e){
												$this->vars['error'] = $e->getMessage();
											}
										}
									}
								}
							}
						}
                    }
                } else{
                    $this->vars['config_not_found'] = __('Plugin configuration not found.');
                }
                
                //load template
                $this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . $this->config->config_entry('main|template') . DS . 'view.claim_reward', $this->vars);
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
        private function public_module($server = ''){
            
        }
		
		
		public function delete($id, $server){
			if($this->pluginaizer->session->is_admin()){
				$this->vars['server'] = $server;
				if($this->vars['server'] != ''){
					$this->load->helper('website');
					$this->vars['is_multi_server'] = $this->pluginaizer->data()->value('is_multi_server');

					$this->vars['reward_list'] = $this->config->values('vip_rewards_list');
					$this->vars['id'] = explode('-', $id);
					if(!empty($this->vars['reward_list'][$this->vars['server']][$this->vars['id'][0]][$this->vars['id'][1]])){
						unset($this->vars['reward_list'][$this->vars['server']][$this->vars['id'][0]][$this->vars['id'][1]]);
					}
					else{
						$this->vars['not_found'] = 'Reward not found.';
					}
					
					$this->config->save_config_data($this->vars['reward_list'], 'vip_rewards_list');
					$this->vars['success'] = 'Reward successfully removed.';

					$this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . 'admin' . DS . 'view.delete', $this->vars);
				}
				else{
					$this->vars['error'] = __('Invalid server.');
				}
			} else{
                $this->pluginaizer->redirect($this->config->base_url . 'admincp/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()) . '/admin');
            }
		}
		
		
		public function change_status($id, $server){
			if($this->pluginaizer->session->is_admin()){
				$this->vars['server'] = $server;
				if($this->vars['server'] != ''){
					$this->load->helper('website');
					$this->vars['is_multi_server'] = $this->pluginaizer->data()->value('is_multi_server');

					$this->vars['reward_list'] = $this->config->values('vip_rewards_list');
					$this->vars['id'] = explode('-', $id);
					if(!empty($this->vars['reward_list'][$this->vars['server']][$this->vars['id'][0]][$this->vars['id'][1]])){
						$this->vars['reward_list'][$this->vars['server']][$this->vars['id'][0]][$this->vars['id'][1]]['status'] = $this->vars['id'][2];
					}
					else{
						$this->vars['not_found'] = 'Reward not found.';
					}
					
					$this->config->save_config_data($this->vars['reward_list'], 'vip_rewards_list');
					$this->vars['success'] = 'Reward status chnaged.';

					$this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . 'admin' . DS . 'view.delete', $this->vars);
				}
				else{
					$this->vars['error'] = __('Invalid server.');
				}
			} else{
                $this->pluginaizer->redirect($this->config->base_url . 'admincp/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()) . '/admin');
            }
		}
		
		public function edit($id, $server){
			if($this->pluginaizer->session->is_admin()){
				$this->vars['server'] = $server;
				$this->vars['is_multi_server'] = $this->pluginaizer->data()->value('is_multi_server');
				if($this->vars['server'] != ''){
					$this->load->helper('website');
					$this->load->model('admin');
					$this->load->model('application/plugins/' . $this->pluginaizer->get_plugin_class() . '/models/' . $this->pluginaizer->get_plugin_class());     
					
					$this->vars['config'] = $this->config->values('vip_rewards', $this->vars['server']);
					$this->vars['reward_list'] = $this->config->values('vip_rewards_list');
					$this->vars['id'] = explode('-', $id);
					if(!empty($this->vars['reward_list'][$this->vars['server']][$this->vars['id'][0]][$this->vars['id'][1]])){
						$this->vars['achData'] = $this->vars['reward_list'][$this->vars['server']][$this->vars['id'][0]][$this->vars['id'][1]];
						$this->vars['achData']['vip_type'] = $this->vars['id'][0];
						$this->vars['achData']['reward_type'] = $this->vars['id'][1];
						
						if(isset($_POST['add_reward'])){
							$items = [];
							if($_POST['item_category']){
								foreach($_POST['item_category'] AS $key => $val){
									if($val != ''){
										$items[] = [
											'cat' => $_POST['item_category'][$key],
											'id' => $_POST['item_index'][$key],
											'dur' => $_POST['item_dur'][$key],
											'lvl' => $_POST['item_level'][$key],
											'skill' => $_POST['item_skill'][$key],
											'luck' => $_POST['item_luck'][$key],
											'opt' => $_POST['item_option'][$key],
											'exe' => $_POST['item_excellent'][$key],
											'anc' => $_POST['item_ancient'][$key],
											'expires' => $_POST['item_expires'][$key],
										];
									}
								}
							}
							
							$this->vars['reward_list'][$this->vars['server']][$this->vars['id'][0]][$this->vars['id'][1]] = [
								'status' => $this->vars['reward_list'][$this->vars['server']][$this->vars['id'][0]][$this->vars['id'][1]]['status'],
								'min_vip_days' => $_POST['min_vip_days'], 
								'credits1' => $_POST['credits1'],
								'credits2' => $_POST['credits2'],
								'wcoin' => $_POST['wcoin'],
								'goblin' => $_POST['goblin'],
								'zen' => $_POST['zen'],
								'credits3' => $_POST['credits3'],
								'ruud' => $_POST['ruud'],
								'items' => $items
							];
							
							$this->config->save_config_data($this->vars['reward_list'], 'vip_rewards_list');
							$this->vars['achData'] = $this->vars['reward_list'][$this->vars['server']][$this->vars['id'][0]][$this->vars['id'][1]];
							$this->vars['achData']['vip_type'] = $this->vars['id'][0];
							$this->vars['achData']['reward_type'] = $this->vars['id'][1];
							$this->vars['success'] = 'Vip reward successfully updated.';
						}
					}
					else{
						$this->vars['not_found'] = 'Reward not found.';
					}

					$this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . 'admin' . DS . 'view.edit', $this->vars);
				}
				else{
					$this->vars['error'] = __('Invalid server.');
				}
			} else{
                $this->pluginaizer->redirect($this->config->base_url . 'admincp/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()) . '/admin');
            }
		}
		
		public function rewards_list(){
			if($this->pluginaizer->session->is_admin()){
				$this->vars['server'] = isset($_GET['server']) ? $_GET['server'] : '';
				$this->vars['is_multi_server'] = $this->pluginaizer->data()->value('is_multi_server');
				if($this->vars['server'] != ''){
					$this->load->helper('website');
					$this->load->model('admin');
					$this->load->model('application/plugins/' . $this->pluginaizer->get_plugin_class() . '/models/' . $this->pluginaizer->get_plugin_class());     
					$this->vars['config'] = $this->config->values('vip_rewards', $this->vars['server']);
					$this->vars['reward_list'] = $this->config->values('vip_rewards_list');
					
					if(isset($_POST['add_reward'])){
						$items = [];
						if($_POST['item_category']){
							foreach($_POST['item_category'] AS $key => $val){
								if($val != ''){
									$items[] = [
										'cat' => $_POST['item_category'][$key],
										'id' => $_POST['item_index'][$key],
										'dur' => $_POST['item_dur'][$key],
										'lvl' => $_POST['item_level'][$key],
										'skill' => $_POST['item_skill'][$key],
										'luck' => $_POST['item_luck'][$key],
										'opt' => $_POST['item_option'][$key],
										'exe' => $_POST['item_excellent'][$key],
										'anc' => $_POST['item_ancient'][$key],
										'expires' => $_POST['item_expires'][$key],
									];
								}
							}
						}
						
						$_POST['items'] = $items;
						
						if(array_key_exists($this->vars['server'], $this->vars['reward_list'])){
							if(empty($this->vars['reward_list'][$this->vars['server']])){
								$this->vars['reward_list'][$this->vars['server']] = [
									$_POST['vip_type'] => [
										$_POST['reward_type'] => [
											'status' => 1,
											'min_vip_days' => $_POST['min_vip_days'], 
											'credits1' => $_POST['credits1'],
											'credits2' => $_POST['credits2'],
											'wcoin' => $_POST['wcoin'],
											'goblin' => $_POST['goblin'],
											'zen' => $_POST['zen'],
											'credits3' => $_POST['credits3'],
											'ruud' => $_POST['ruud'],
											'items' => $items
										]
									]
								];
							}
							else{
								if(empty($this->vars['reward_list'][$this->vars['server']][$_POST['vip_type']])){
									$this->vars['reward_list'][$this->vars['server']][$_POST['vip_type']] = [
										$_POST['reward_type'] => [
											'status' => 1,
											'min_vip_days' => $_POST['min_vip_days'], 
											'credits1' => $_POST['credits1'],
											'credits2' => $_POST['credits2'],
											'wcoin' => $_POST['wcoin'],
											'goblin' => $_POST['goblin'],
											'zen' => $_POST['zen'],
											'credits3' => $_POST['credits3'],
											'ruud' => $_POST['ruud'],
											'items' => $items
										]
									];
								}
								else{
									$this->vars['reward_list'][$this->vars['server']][$_POST['vip_type']][$_POST['reward_type']] = [
										'status' => 1,
										'min_vip_days' => $_POST['min_vip_days'], 
										'credits1' => $_POST['credits1'],
										'credits2' => $_POST['credits2'],
										'wcoin' => $_POST['wcoin'],
										'goblin' => $_POST['goblin'],
										'zen' => $_POST['zen'],
										'credits3' => $_POST['credits3'],
										'ruud' => $_POST['ruud'],
										'items' => $items
									];
								}
								
							}
							$this->config->save_config_data($this->vars['reward_list'], 'vip_rewards_list');
						}
						else{
							$this->vars['new_config'] = [
								$this->vars['server'] => [
									$_POST['vip_type'] => [
										$_POST['reward_type'] => [
											'status' => 1,
											'min_vip_days' => $_POST['min_vip_days'], 
											'credits1' => $_POST['credits1'],
											'credits2' => $_POST['credits2'],
											'wcoin' => $_POST['wcoin'],
											'goblin' => $_POST['goblin'],
											'zen' => $_POST['zen'],
											'credits3' => $_POST['credits3'],
											'ruud' => $_POST['ruud'],
											'items' => $items
										]
									]
								]
							];
							$this->vars['reward_list'] = array_merge($this->vars['reward_list'], $this->vars['new_config']);
                            $this->config->save_config_data($this->vars['reward_list'], 'vip_rewards_list');
						}
						$this->vars['success'] = 'Vip reward successfully updated.';
					}
					
				}
				else{
					$this->vars['error'] = __('Invalid server.');
				}
				$this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . 'admin' . DS . 'view.reward', $this->vars);		
			}
			else{
                $this->pluginaizer->redirect($this->config->base_url . 'admincp/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()) . '/admin');
            }		
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
                $this->vars['is_multi_server'] = $this->pluginaizer->data()->value('is_multi_server');
                $this->vars['plugin_config'] = $this->pluginaizer->plugin_config();
                //load any js, css files if required
                $this->vars['js'] = $this->config->base_url . 'assets/plugins/js/' . $this->pluginaizer->get_plugin_class() . '.js';
                //load template
                $this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . 'admin' . DS . 'view.index', $this->vars);
            } else{
                $this->pluginaizer->redirect($this->config->base_url . 'admincp/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()) . '/admin');
            }
        }
		
		/**
         *
         * Generate vip rewards logs
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
                if(isset($_POST['search_vip_rewards'])){
                    $server = (isset($_POST['server']) && $_POST['server'] != 'All') ? $_POST['server'] : 'All';
                    $acc = isset($_POST['account']) ? $_POST['account'] : '';
                    if($acc == ''){
                        $this->vars['error'] = 'Invalid account';
                    } else{
                        $this->vars['logs'] = $this->pluginaizer->Mvip_rewards->load_logs(1, 25, $acc, $server);
                        $this->pluginaizer->pagination->initialize(1, 25, $this->pluginaizer->Mvip_rewards->count_total_logs($acc, $server), $this->config->base_url . 'vip-rewards/logs/%s/' . $acc . '/' . $server . '');
                        $this->vars['pagination'] = $this->pluginaizer->pagination->create_links();
                    }
                } else{
                    $this->vars['logs'] = $this->pluginaizer->Mvip_rewards->load_logs($page, 25, $acc, $server);
                    $lk = '';
                    if($acc != '')
                        $lk .= '/' . $acc;
                    $lk .= '/' . $server;
                    $this->pluginaizer->pagination->initialize($page, 25, $this->pluginaizer->Mvip_rewards->count_total_logs($acc, $server), $this->config->base_url . 'vip-rewards/logs/%s' . $lk);
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
                    'sidebar_public_item' => 0, //add link to module in public sidebar menu, if template supports
                    'account_panel_item' => 1, //add link in user account panel
                    'donation_panel_item' => 0, //add link in donation page
					'rankings_panel_item' => 0, //add link in rankings page
                    'description' => 'Receive vip rewards.' //description which will see user
                ]);
                //create plugin config template
                $this->pluginaizer->create_config([
					'active' => 0,
					'vip_type' => 1
				]);
				
				$this->pluginaizer->add_sql_scheme('vip_claimed_rewards');
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
                $this->pluginaizer->delete_config()->remove_sql_scheme('vip_claimed_rewards')->remove_plugin();
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