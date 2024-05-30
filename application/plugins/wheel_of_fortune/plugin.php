<?php

    class _plugin_wheel_of_fortune extends controller implements pluginInterface
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
			$this->vars['plugin_class'] = substr(get_class($this), 8);
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
			//load website helper
			$this->load->helper('website');
			//load plugin config
			
			$this->vars['is_logged_in'] = $this->pluginaizer->session->is_user();
			
			if($this->vars['is_logged_in'] != true){
				$server = array_keys($this->pluginaizer->website->server_list())[0];
				$this->session->register('user', [
					'server' => $server, 
				]);
			}
			
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
					$this->vars['config_not_found'] = __('This module has been disabled.');
				} else{
					$this->load->helper('website');
					$this->load->model('application/plugins/'.$this->pluginaizer->get_plugin_class().'/models/'.$this->pluginaizer->get_plugin_class().'');
					
					if(in_array($this->vars['plugin_config']['spin_currency'], [1,2])){
						if($this->vars['is_logged_in'] == true){
							$credits = $this->pluginaizer->website->get_user_credits_balance($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['plugin_config']['spin_currency'], $this->pluginaizer->session->userdata(['user' => 'id']));
							$this->vars['currency_amount'] = $credits['credits'];
						}
						else{
							$this->vars['currency_amount'] = 0;
						}
						$this->vars['currency_name'] = $this->pluginaizer->website->translate_credits($this->vars['plugin_config']['spin_currency'], $this->pluginaizer->session->userdata(['user' => 'server']));
						
					}
					if(in_array($this->vars['plugin_config']['spin_currency'], [5])){
						if($this->vars['is_logged_in'] == true){
							$credits = $this->pluginaizer->website->get_user_credits_balance($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), 3, $this->pluginaizer->session->userdata(['user' => 'id']));
							$this->vars['currency_amount'] = $credits['credits'];
						}
						else{
							$this->vars['currency_amount'] = 0;
						}
						$this->vars['currency_name'] = $this->pluginaizer->website->translate_credits(3, $this->pluginaizer->session->userdata(['user' => 'server']));
						
					}
					if(in_array($this->vars['plugin_config']['spin_currency'], [3,4])){
						$this->vars['table_config'] = $this->pluginaizer->config->values('table_config', $this->pluginaizer->session->userdata(['user' => 'server']));
						if($this->vars['plugin_config']['spin_currency'] == 3){	
							if($this->vars['is_logged_in'] == true){
								$acc = (in_array($this->vars['table_config']['wcoins']['identifier_column'], ['MemberGuid', 'memb_guid'])) ? $this->pluginaizer->session->userdata(['user' => 'id']) : $this->pluginaizer->session->userdata(['user' => 'username']);
								$this->vars['currency_amount'] = floor($this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->get_wcoins($acc, $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['table_config']['wcoins']));
							}
							else{
								$this->vars['currency_amount'] = 0;
							}
							$this->vars['currency_name'] = __('WCoins');
						}
						if($this->vars['plugin_config']['spin_currency'] == 4){
							if($this->vars['is_logged_in'] == true){
								$acc = (in_array($this->vars['table_config']['goblinpoint']['identifier_column'], ['MemberGuid', 'memb_guid'])) ? $this->pluginaizer->session->userdata(['user' => 'id']) : $this->pluginaizer->session->userdata(['user' => 'username']);
								$this->vars['currency_amount'] = floor($this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->get_wcoins($acc, $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['table_config']['goblinpoint']));
							}
							else{
								$this->vars['currency_amount'] = 0;
							}
							$this->vars['currency_name'] = __('GoblinPoint');
						}
					}
					
					if($this->vars['is_logged_in'] == true){
						$this->vars['totalSpins'] = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->count_total_spins($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
						if($this->vars['totalSpins'] == false){
							$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->insert_total_spins($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
							$this->vars['totalSpins'] = 0;
						}
						else{
							$this->vars['totalSpins'] = $this->vars['totalSpins']['amount'];
						}
						
						$this->vars['freeSpins'] = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->count_total_free_spins($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
						if($this->vars['freeSpins'] == false){
							$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->insert_total_free_spins($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), 0);
							$this->vars['freeSpins'] = 0;
						}
						else{
							$this->vars['freeSpins'] = $this->vars['freeSpins']['spins'];
						}
					}
					else{
						$this->vars['totalSpins'] = 0;
						$this->vars['freeSpins'] = 0;
					}
					
					$this->load->lib('iteminfo');
					$this->load->lib('itemimage');
					$this->load->lib('createitem', [MU_VERSION, SOCKET_LIBRARY, $this->pluginaizer->website->get_value_from_server($this->pluginaizer->session->userdata(['user' => 'server']), 'item_size')]);	
					$this->load->model('shop');
					
					
					
					$this->vars['wheel_rewards'] = $this->config->values('wheel_of_fortune_rewards', $this->pluginaizer->session->userdata(['user' => 'server']));
					if(!empty($this->vars['wheel_rewards']['rewards'])){
						$this->vars['rewards'] = [];
						$ui = 0;
						foreach($this->vars['wheel_rewards']['rewards'] AS $key => $rewardData){
							if($ui >= $this->vars['plugin_config']['total_rewards'])
								continue;
							$name = __('No Reward');
							if(in_array($rewardData['reward_type'], [1,2,3])){
								$name = $this->pluginaizer->website->translate_credits($rewardData['reward_type'], $this->pluginaizer->session->userdata(['user' => 'server']));
							}
							if($rewardData['reward_type'] == 4){
								$name = __('WCoins');
							}
							if($rewardData['reward_type'] == 5){
								$name = __('GoblinPoint');
							}
							if($rewardData['reward_type'] == 6){
								$name = __('Ruud');
							}
							if($rewardData['reward_type'] == 8){
								$name = __('Free Spins');
							}
							if($rewardData['reward_type'] == 7){
								if(!empty($rewardData['item'])){
									$ritem = $rewardData['item'];
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
											if($ritem['cat'] == 13 && $ritem['id'] == 37){
												if(in_array($ritem['exe'], [0,1,2,4,5,6])){
													$this->pluginaizer->createitem->fenrir($ritem['exe']);
												}
											}
											else{
												if(mb_strpos($ritem['exe'], ',') !== false){
													$exe_opts = [0 => 1, 1 => 2, 2 => 4, 3 => 8, 4 => 16, 5 => 32];
													$iexe = explode(',', $ritem['exe']);
													$exe = 0;
													
													foreach($iexe AS $k => $val){
														if($val == 0){
															unset($iexe[$k]);
														}
													}
													
													if(!empty($iexe)){		
														foreach($iexe as $ekey => $exe_opt){
															if($exe_opt == 1){
																$exe += $exe_opts[$ekey];
															}
														}
													}
													$this->pluginaizer->createitem->addStaticExe($exe);
												}
												else{
													$this->pluginaizer->createitem->addStaticExe(0);
												}
											}
										}
										else{
											$this->pluginaizer->createitem->addStaticExe(0);
										}
										
										if($ritem['anc'] != '' && $ritem['anc'] != 0){
											$this->pluginaizer->createitem->ancient($ritem['anc']);
										}
										
										$itemHex = null;
										$itemHex = $this->pluginaizer->createitem->to_hex();
										$this->pluginaizer->iteminfo->itemData($itemHex, true, $this->pluginaizer->session->userdata(['user' => 'server']));
										if($ritem['expires'] != ''){
											$this->pluginaizer->iteminfo->setExpireTime($ritem['expires']);
										}
										$itemData = [
											'hex' => $itemHex,
											'name' => $this->pluginaizer->iteminfo->getNameStyle(true, 31),
											'name_no_style' => $this->pluginaizer->iteminfo->realName(),
											'image' => $this->pluginaizer->itemimage->load($this->pluginaizer->iteminfo->id, $this->pluginaizer->iteminfo->type, (int)substr($this->pluginaizer->iteminfo->getLevel(), 1), 0),
											'item_info' => $this->pluginaizer->iteminfo->allInfo(),
											'serial' => 00000000,
											'expires' => $ritem['expires'],
											'amount' => $this->pluginaizer->iteminfo->dur
										];
									}
								}
							}
							
							$propability = trim($rewardData['probability']) / 100;
							$color = 'grey';
							$title = __('Common');
							if($propability < 5){
								$color = 'blue';
								$title = __('Rare');
							}
							if($propability < 2){
								$color = 'red';
								$title = __('Super rare');
							}
							if($propability < 1){
								$color = 'gold';
								$title = __('Legendary');
							}
							
							$this->vars['rewards'][$key] = [
								'type' => $rewardData['reward_type'],
								'name' => $name,
								'propability' => $propability,
								'color' => $color,
								'title' => $title,
								'amount' => !in_array($rewardData['reward_type'], [0, 7]) ? $rewardData['amount']: false,
								'item' => ($rewardData['reward_type'] == 7 && isset($itemData)) ? $itemData : ['name' => $name]
							];
							
							$realName = $name;
							if(!in_array($rewardData['reward_type'], [0, 7])){
								$realName .= ' x'.$rewardData['amount'];
							}
							if($rewardData['reward_type'] == 7) {
								$realName = $itemData['name_no_style'];
							}
							$_SESSION['wheel_rewards'][$key] = [
								'name' => $realName
							];
							$ui++;
						}
					}
					else{
						$this->vars['config_not_found'] = __('No rewards configured for this wheel.');
					}
				}
			} else{
				$this->vars['config_not_found'] = __('Plugin configuration not found.');
			}
			//set js
			$this->vars['js'] = $this->config->base_url . 'assets/plugins/js/'.$this->pluginaizer->get_plugin_class().'.js';
			//load template
			$templateFile = (defined('CUSTOM_WHEEL') && CUSTOM_WHEEL == 1) ? 'view.wheel2' : 'view.wheel';
			$this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . 'default' . DS . $templateFile, $this->vars);
        }
		
		public function my_rewards(){
			//check if visitor has user privilleges
            if($this->pluginaizer->session->is_user()){
                //load website helper
                $this->load->helper('website');
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
                        $this->vars['config_not_found'] = __('This module has been disabled.');
                    } else{
						$this->load->helper('website');
                        $this->load->model('application/plugins/'.$this->pluginaizer->get_plugin_class().'/models/'.$this->pluginaizer->get_plugin_class().'');
						
						if(in_array($this->vars['plugin_config']['spin_currency'], [1,2])){
							$credits = $this->pluginaizer->website->get_user_credits_balance($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['plugin_config']['spin_currency'], $this->pluginaizer->session->userdata(['user' => 'id']));
							$this->vars['currency_amount'] = $credits['credits'];
							$this->vars['currency_name'] = $this->pluginaizer->website->translate_credits($this->vars['plugin_config']['spin_currency'], $this->pluginaizer->session->userdata(['user' => 'server']));
							
						}
						if(in_array($this->vars['plugin_config']['spin_currency'], [3,4])){
							$this->vars['table_config'] = $this->pluginaizer->config->values('table_config', $this->pluginaizer->session->userdata(['user' => 'server']));
							if($this->vars['plugin_config']['spin_currency'] == 3){	
								$acc = (in_array($this->vars['table_config']['wcoins']['identifier_column'], ['MemberGuid', 'memb_guid'])) ? $this->pluginaizer->session->userdata(['user' => 'id']) : $this->pluginaizer->session->userdata(['user' => 'username']);
								$this->vars['currency_amount'] = floor($this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->get_wcoins($acc, $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['table_config']['wcoins']));
								$this->vars['currency_name'] = __('WCoins');
							}
							if($this->vars['plugin_config']['spin_currency'] == 4){
								$acc = (in_array($this->vars['table_config']['goblinpoint']['identifier_column'], ['MemberGuid', 'memb_guid'])) ? $this->pluginaizer->session->userdata(['user' => 'id']) : $this->pluginaizer->session->userdata(['user' => 'username']);
								$this->vars['currency_amount'] = floor($this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->get_wcoins($acc, $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['table_config']['goblinpoint']));
								$this->vars['currency_name'] = __('GoblinPoint');
							}
						}
						
						$this->load->lib('iteminfo');
						$this->load->lib('itemimage');
						$this->load->lib('createitem', [MU_VERSION, SOCKET_LIBRARY, $this->pluginaizer->website->get_value_from_server($this->pluginaizer->session->userdata(['user' => 'server']), 'item_size')]);	
						$this->load->model('shop');
								
						$this->vars['wheel_rewards'] = $this->config->values('wheel_of_fortune_rewards', $this->pluginaizer->session->userdata(['user' => 'server']));
						if(!empty($this->vars['wheel_rewards']['rewards'])){
							$this->vars['user_rewards'] = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->get_reward_list($this->pluginaizer->session->userdata(['user' => 'username']),$this->pluginaizer->session->userdata(['user' => 'server']));
							
							$this->vars['user_rewards_parsed'] = [];
							$this->vars['characters'] = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->load_char_list($this->pluginaizer->session->userdata(['user' => 'username']),$this->pluginaizer->session->userdata(['user' => 'server']));
							
							if(!empty($this->vars['user_rewards'])){
								foreach($this->vars['user_rewards'] AS $rid => $reward){
									if($reward['reward_data'] != null && $reward['reward_data'] != ''){
										$rewardData = json_decode($reward['reward_data'], true);
									}
									else{
										$rewardData = isset($this->vars['wheel_rewards']['rewards'][$reward['reward_id']]) ? $this->vars['wheel_rewards']['rewards'][$reward['reward_id']] : false;
									}
									
									if($rewardData != false){
										if($rewardData['reward_type'] == 0){
											continue;	
										}
										
										$name = __('No Reward');
										
										if(in_array($rewardData['reward_type'], [1,2,3])){
											$name = $this->pluginaizer->website->translate_credits($rewardData['reward_type'], $this->pluginaizer->session->userdata(['user' => 'server']));
										}
										if($rewardData['reward_type'] == 4){
											$name = __('WCoins');
										}
										if($rewardData['reward_type'] == 5){
											$name = __('GoblinPoint');
										}
										if($rewardData['reward_type'] == 6){
											$name = __('Ruud');
										}
										if($rewardData['reward_type'] == 8){
											$name = __('Free Spins');
										}
										if($rewardData['reward_type'] == 7){
											if(!empty($rewardData['item'])){
												$ritem = $rewardData['item'];
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
														if($ritem['cat'] == 13 && $ritem['id'] == 37){
															if(in_array($ritem['exe'], [0,1,2,4])){
																$this->pluginaizer->createitem->fenrir($ritem['exe']);
															}
														}
														else{
															if(mb_strpos($ritem['exe'], ',') !== false){
																$exe_opts = [0 => 1, 1 => 2, 2 => 4, 3 => 8, 4 => 16, 5 => 32];
																$iexe = explode(',', $ritem['exe']);
																$exe = 0;
																
																foreach($iexe AS $k => $val){
																	if($val == 0){
																		unset($iexe[$k]);
																	}
																}
																
																if(!empty($iexe)){		
																	foreach($iexe as $ekey => $exe_opt){
																		if($exe_opt == 1){
																			$exe += $exe_opts[$ekey];
																		}
																	}
																}
																$this->pluginaizer->createitem->addStaticExe($exe);
															}
															else{
																$this->pluginaizer->createitem->addStaticExe(0);
															}
														}
													}
													else{
														$this->pluginaizer->createitem->addStaticExe(0);
													}
													if($ritem['anc'] != '' && $ritem['anc'] != 0){
														$this->pluginaizer->createitem->ancient($ritem['anc']);
													}

													$itemHex = $this->pluginaizer->createitem->to_hex();
													$this->pluginaizer->iteminfo->itemData($itemHex, true, $this->pluginaizer->session->userdata(['user' => 'server']));
													if($ritem['expires'] != ''){
														$this->pluginaizer->iteminfo->setExpireTime($ritem['expires']);
													}
													$itemData = [
														'hex' => $itemHex,
														'name' => $this->pluginaizer->iteminfo->getNameStyle(true),
														'image' => $this->pluginaizer->itemimage->load($this->pluginaizer->iteminfo->id, $this->pluginaizer->iteminfo->type, (int)substr($this->pluginaizer->iteminfo->getLevel(), 1), 0),
														'item_info' => $this->pluginaizer->itemimage->load($this->pluginaizer->iteminfo->id, $this->pluginaizer->iteminfo->type, (int)substr($this->pluginaizer->iteminfo->getLevel(), 1)) . '<br />' . $this->pluginaizer->iteminfo->allInfo(),
														'serial' => 00000000,
														'expires' => $ritem['expires'],
														'amount' => $this->pluginaizer->iteminfo->durability()
													];
												}
											}
										}
										$this->vars['user_rewards_parsed'][] = [
											'id' => $reward['id'],
											'type' => $rewardData['reward_type'],
											'name' => $name,
											'amount' => !in_array($rewardData['reward_type'], [0, 7]) ? $rewardData['amount']: false,
											'item' => ($rewardData['reward_type'] == 7 && isset($itemData)) ? $itemData : ['name' => $name],
											'is_claimed' => $reward['is_claimed'],
											'character' => $reward['character'],
											'date_won' => date('d/m/Y H:i:s' , $reward['date_generated']),
											'code' => $reward['code']
										];
										
									}
									else{
										continue;
									}
								}
							}
						}
						else{
							$this->vars['config_not_found'] = __('No rewards configured for this wheel.');
						}
                    }
                } else{
                    $this->vars['config_not_found'] = __('Plugin configuration not found.');
                }
                //set js
                $this->vars['js'] = $this->config->base_url . 'assets/plugins/js/'.$this->pluginaizer->get_plugin_class().'.js';
                //load template
                $this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . 'default' . DS . 'view.rewards', $this->vars);
            } else{
                $this->pluginaizer->redirect($this->config->base_url . 'account-panel/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()));
            }
		}
		
		public function claim_reward($id = ''){
			//check if visitor has user privilleges
            if($this->pluginaizer->session->is_user()){
                //load website helper
                $this->load->helper('website');
                //load plugin config
                $this->vars['plugin_config'] = $this->pluginaizer->plugin_config();
                if($this->vars['plugin_config'] != false && !empty($this->vars['plugin_config'])){
                    if($this->pluginaizer->data()->value('is_multi_server') == 1){
                        if(array_key_exists($this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['plugin_config'])){
                            $this->vars['plugin_config'] = $this->vars['plugin_config'][$this->pluginaizer->session->userdata(['user' => 'server'])];
                            $this->vars['about'] = $this->pluginaizer->get_about();
                            $this->vars['about']['user_description'] = $this->pluginaizer->data()->value('description');
                        } else{
                            echo $this->pluginaizer->jsone(['error' => __('Plugin configuration not found.')]);
							exit;
                        }
                    }
                    if($this->vars['plugin_config']['active'] == 0){
                         echo $this->pluginaizer->jsone(['error' => __('This module has been disabled.')]);
                    } else{
						try{
							$this->load->helper('website');
							$this->load->model('application/plugins/'.$this->pluginaizer->get_plugin_class().'/models/'.$this->pluginaizer->get_plugin_class().'');
	
							$this->vars['wheel_rewards'] = $this->config->values('wheel_of_fortune_rewards', $this->pluginaizer->session->userdata(['user' => 'server']));
							
							if(!empty($this->vars['wheel_rewards']['rewards'])){
								$this->vars['reward_data'] = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->check_reward($this->pluginaizer->session->userdata(['user' => 'username']),$this->pluginaizer->session->userdata(['user' => 'server']), $id);
								
								if($this->vars['reward_data'] == false){
									throw new Exception(__('Reward not found.'));
								}
								
								if($this->vars['reward_data']['is_claimed'] == 1){
									throw new Exception(__('Reward already claimed.'));
								}
								
								if($this->vars['reward_data']['code'] != NULL){
									throw new Exception(__('Reward can be claimed in gift codes.'));
								}
								
								if($this->vars['reward_data']['reward_data'] != null && $this->vars['reward_data']['reward_data'] != ''){
									$rewardData = json_decode($this->vars['reward_data']['reward_data'], true);
								}
								else{
									$rewardData = isset($this->vars['wheel_rewards']['rewards'][$this->vars['reward_data']['reward_id']]) ? $this->vars['wheel_rewards']['rewards'][$this->vars['reward_data']['reward_id']] : false;
								}
								
								if($rewardData == false){
									throw new Exception(__('Reward data not found.'));
								}
								else{
									if($rewardData['reward_type'] == 0){
										throw new Exception(__('No Reward.'));
									}
									
									if(in_array($rewardData['reward_type'], [4,5,6,7,9,10,11])){
										if(!$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->check_connect_stat($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']))){
											throw new Exception(__('Please logout from game.'));
										}
									}
									
									$this->vars['character'] = isset($_POST['character']) ? $_POST['character'] : '';
									
									$this->vars['character_data'] = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->check_char($this->vars['character'], $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), true);
									
									if($this->vars['character_data'] == false){
										throw new Exception(__('Invalid character.'));
									}
									
									if(in_array($rewardData['reward_type'], [1,2,3])){
										$this->pluginaizer->website->add_credits($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $rewardData['amount'], $rewardData['reward_type'], false, $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->get_guid($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server'])));
									}
									
									if($rewardData['reward_type'] == 8){
										if($this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->count_total_free_spins($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server'])) != false){
											$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->update_total_free_spins($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $rewardData['amount']);
										}
										else{
											$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->insert_total_free_spins($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $rewardData['amount']);
										}
									}
									
									$this->vars['table_config'] = $this->pluginaizer->config->values('table_config', $this->pluginaizer->session->userdata(['user' => 'server']));
									
									if($rewardData['reward_type'] == 4){
										if(!isset($this->vars['table_config']['wcoins']))
											throw new Exception(__('WCoins configuration not found'));
										if($this->vars['table_config']['wcoins']['table'] == '')
											throw new Exception(__('WCoins configuration not found'));
										$acc = (in_array($this->vars['table_config']['wcoins']['identifier_column'], ['MemberGuid', 'memb_guid'])) ? $this->pluginaizer->session->userdata(['user' => 'id']) : $this->pluginaizer->session->userdata(['user' => 'username']);
										$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->add_wcoins($acc, $this->pluginaizer->session->userdata(['user' => 'server']), $rewardData['amount'], $this->vars['table_config']['wcoins']);
											
									}
									if($rewardData['reward_type'] == 5){
										if(!isset($this->vars['table_config']['goblinpoint']))
											throw new Exception(__('GoblinPoint configuration not found'));
										if($this->vars['table_config']['goblinpoint']['table'] == '')
											throw new Exception(__('GoblinPoint configuration not found'));
										$acc = (in_array($this->vars['table_config']['goblinpoint']['identifier_column'], ['MemberGuid', 'memb_guid'])) ? $this->pluginaizer->session->userdata(['user' => 'id']) : $this->pluginaizer->session->userdata(['user' => 'username']);
										$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->add_wcoins($acc, $this->pluginaizer->session->userdata(['user' => 'server']), $rewardData['amount'], $this->vars['table_config']['goblinpoint']);
											
									}
									if($rewardData['reward_type'] == 6){
										$ruud = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->checkRuud($this->vars['character'], $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
										if(($ruud['Ruud'] + $rewardData['amount']) > 2000000000){
											throw new Exception('Ruud limit reached on character.');
										}
										$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->add_ruud($rewardData['amount'], $this->vars['character'], $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
									}
									if($rewardData['reward_type'] == 7){
										$this->load->lib('iteminfo');
										$this->load->lib('itemimage');
										$this->load->lib('createitem', [MU_VERSION, SOCKET_LIBRARY, $this->pluginaizer->website->get_value_from_server($this->pluginaizer->session->userdata(['user' => 'server']), 'item_size')]);
										$this->load->model('shop');
										
										if(!empty($rewardData['item'])){
											$ritem = $rewardData['item'];
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
												//$this->vars['reward_data']['reward_id']
												if(defined('WHEEL_NEGATIVE_SERIALS')){
													if(isset(WHEEL_NEGATIVE_SERIALS[$this->vars['reward_data']['reward_id']]) && WHEEL_NEGATIVE_SERIALS[$this->vars['reward_data']['reward_id']] == 1){
														$serial = -22050;
													}
												}
												else{
													$serial = array_values($this->pluginaizer->Mshop->generate_serial($this->pluginaizer->session->userdata(['user' => 'server'])))[0];
												}
												
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
													if($ritem['cat'] == 13 && $ritem['id'] == 37){
														if(in_array($ritem['exe'], [0,1,2,4])){
															$this->pluginaizer->createitem->fenrir($ritem['exe']);
														}
													}
													else{
														if(mb_strpos($ritem['exe'], ',') !== false){
															$exe_opts = [0 => 1, 1 => 2, 2 => 4, 3 => 8, 4 => 16, 5 => 32];
															$iexe = explode(',', $ritem['exe']);
															$exe = 0;
															
															foreach($iexe AS $k => $val){
																if($val == 0){
																	unset($iexe[$k]);
																}
															}
															
															if(!empty($iexe)){		
																foreach($iexe as $ekey => $exe_opt){
																	if($exe_opt == 1){
																		$exe += $exe_opts[$ekey];
																	}
																}
															}
															$this->pluginaizer->createitem->addStaticExe($exe);
														}
														else{
															$this->pluginaizer->createitem->addStaticExe(0);
														}
													}
												}
												else{
													$this->pluginaizer->createitem->addStaticExe(0);
												}
												
												if($ritem['anc'] != '' && $ritem['anc'] != 0){
													$this->pluginaizer->createitem->ancient($ritem['anc']);
												}

												$itemHex = $this->pluginaizer->createitem->to_hex();
												$this->pluginaizer->iteminfo->itemData($itemHex, true, $this->pluginaizer->session->userdata(['user' => 'server']));
												$this->vars['reward_items'] = [
													'hex' => $itemHex,
													'serial' => $serial,
													'expires' => $ritem['expires'],
													'itemtype' => $this->pluginaizer->iteminfo->itemIndex($ritem['cat'], $ritem['id'])
												];
											}
											else{
												throw new Exception(__('Unable to set item data'));
											}
											
											if($ritem['cat'] == 16){
												$slot = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->find_free_muun_slot($this->vars['character_data']['Name'], $this->pluginaizer->session->userdata(['user' => 'server']));
												if($slot === false){
													throw new Exception(__('No slots in muun inventory.')); 
												}
												$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->generate_new_muun_by_slot($slot, $this->vars['reward_items']['hex'], $this->pluginaizer->session->userdata(['user' => 'server']));
												$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->update_muun_inventory($this->vars['character_data']['Name'], $this->pluginaizer->session->userdata(['user' => 'server']));
												if($this->vars['reward_items']['expires'] != ''){
													$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->add_muun_period($this->vars['character_data']['Name'], $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['reward_items']['serial'], $this->vars['reward_items']['expires'], $this->vars['reward_items']['itemtype']);
												}
											}
											else{
												$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->inventory($this->vars['character'], $this->pluginaizer->session->userdata(['user' => 'server']));
												$items = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->getInventoryContents($this->pluginaizer->session->userdata(['user' => 'server']));
												$itemsList = implode('', $items);
												$itemInfo = $this->pluginaizer->iteminfo;
												$itemArr = str_split($itemsList, $this->pluginaizer->website->get_value_from_server($this->pluginaizer->session->userdata(['user' => 'server']), 'item_size'));
												$takenSlots = [];
												$expirableItems = [];
												$ritems = $this->vars['reward_items'];
												
												$this->pluginaizer->iteminfo->itemData($ritems['hex'], true, $this->pluginaizer->session->userdata(['user' => 'server']));
												$space = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->check_space_inventory($itemArr, $this->pluginaizer->iteminfo->getX(), $this->pluginaizer->iteminfo->getY(), 64, $this->pluginaizer->website->get_value_from_server($this->pluginaizer->session->userdata(['user' => 'server']), 'item_size'), 8, 8, false, $itemInfo, $takenSlots);
												
												if($space === null){
													throw new Exception($this->{'M'.$this->pluginaizer->get_plugin_class()}->errors[0]);
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
												
												$newInv = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->addItemsToInventory($itemArr, $this->pluginaizer->session->userdata(['user' => 'server']));
												if(!empty($expirableItems)){
													$currTime = time();
													foreach($expirableItems AS $expideData){
														$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->addExpirableItem($this->pluginaizer->session->userdata(['user' => 'id']), $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['character_data']['Name'], $expideData['index'], $expideData['time'], $expideData['serial'], $currTime);
													}
												}
												$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->updateInventory($this->vars['character'], $this->pluginaizer->session->userdata(['user' => 'server']), $newInv);
											}
										}
										else{
											throw new Exception(__('Invalid reward item.'));
										}
									}
									$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->set_reward_claimed($id, $this->vars['character_data']['Name'], $this->vars['character'], $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
									$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->add_account_log('Claimed wheel of fortune reward.', 0, $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
									echo $this->pluginaizer->jsone(['success' => __('Reward successfully claimed'), 'char' => $this->vars['character_data']['Name'], 'id' => $id]);															
								}
							}
							else{
								throw new Exception(__('No rewards configured for this wheel.'));
							}
						}
						catch(\Exception $e){
							echo $this->pluginaizer->jsone(['error' => $e->getMessage()]);
							exit;
						}
                    }
                } else{
                    echo $this->pluginaizer->jsone(['error' => __('Plugin configuration not found.')]);
                }
            } else{
                 echo $this->pluginaizer->jsone(['error' => __('Please login first!')]);
            }
		}
		
		public function spin($free = 0){
			//check if visitor has user privilleges
            if($this->pluginaizer->session->is_user()){
                //load website helper
                $this->load->helper('website');
                //load plugin config
                $this->vars['plugin_config'] = $this->pluginaizer->plugin_config();
                if($this->vars['plugin_config'] != false && !empty($this->vars['plugin_config'])){
                    if($this->pluginaizer->data()->value('is_multi_server') == 1){
                        if(array_key_exists($this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['plugin_config'])){
                            $this->vars['plugin_config'] = $this->vars['plugin_config'][$this->pluginaizer->session->userdata(['user' => 'server'])];
                            $this->vars['about'] = $this->pluginaizer->get_about();
                            $this->vars['about']['user_description'] = $this->pluginaizer->data()->value('description');
                        } else{
                            echo $this->pluginaizer->jsone(['error' => __('This module has been disabled.')]);
							exit;
                        }
                    }
                    if($this->vars['plugin_config']['active'] == 0){
						echo $this->pluginaizer->jsone(['error' => __('This module has been disabled.')]);
                    } else{
						$this->load->helper('website');
                        $this->load->model('application/plugins/'.$this->pluginaizer->get_plugin_class().'/models/'.$this->pluginaizer->get_plugin_class().'');
										
						if($free == 1){
							$this->vars['freeSpins'] = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->count_total_free_spins($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
							if($this->vars['freeSpins'] == false){
								$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->insert_total_free_spins($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), 0);
								$this->vars['freeSpins'] = 0;
							}
							else{
								$this->vars['freeSpins'] = $this->vars['freeSpins']['spins'];
							}
							if($this->vars['freeSpins'] < 1){
								echo $this->pluginaizer->jsone(['error' => sprintf(__('You have insufficient amount of %s'), __('Free Spins'))]);
								exit;
							}
						}
						else{
							if(in_array($this->vars['plugin_config']['spin_currency'], [1,2])){
								$credits = $this->pluginaizer->website->get_user_credits_balance($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['plugin_config']['spin_currency'], $this->pluginaizer->session->userdata(['user' => 'id']));
								$this->vars['currency_amount'] = $credits['credits'];
								$this->vars['currency_name'] = $this->pluginaizer->website->translate_credits($this->vars['plugin_config']['spin_currency'], $this->pluginaizer->session->userdata(['user' => 'server']));
							}
							if(in_array($this->vars['plugin_config']['spin_currency'], [5])){
								$credits = $this->pluginaizer->website->get_user_credits_balance($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), 3, $this->pluginaizer->session->userdata(['user' => 'id']));
								$this->vars['currency_amount'] = $credits['credits'];
								$this->vars['currency_name'] = $this->pluginaizer->website->translate_credits(3, $this->pluginaizer->session->userdata(['user' => 'server']));
								
							}
							if(in_array($this->vars['plugin_config']['spin_currency'], [3,4])){
								$this->vars['table_config'] = $this->pluginaizer->config->values('table_config', $this->pluginaizer->session->userdata(['user' => 'server']));
								if($this->vars['plugin_config']['spin_currency'] == 3){	
									$acc = (in_array($this->vars['table_config']['wcoins']['identifier_column'], ['MemberGuid', 'memb_guid'])) ? $this->pluginaizer->session->userdata(['user' => 'id']) : $this->pluginaizer->session->userdata(['user' => 'username']);
									$this->vars['currency_amount'] = floor($this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->get_wcoins($acc, $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['table_config']['wcoins']));
									$this->vars['currency_name'] = __('WCoins');
								}
								if($this->vars['plugin_config']['spin_currency'] == 4){
									$acc = (in_array($this->vars['table_config']['goblinpoint']['identifier_column'], ['MemberGuid', 'memb_guid'])) ? $this->pluginaizer->session->userdata(['user' => 'id']) : $this->pluginaizer->session->userdata(['user' => 'username']);
									$this->vars['currency_amount'] = floor($this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->get_wcoins($acc, $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['table_config']['goblinpoint']));
									$this->vars['currency_name'] = __('GoblinPoint');
								}
							}
							
							if($this->vars['currency_amount'] < $this->vars['plugin_config']['spin_price']){
								echo $this->pluginaizer->jsone(['error' => sprintf(__('You have insufficient amount of %s'), $this->vars['currency_name'])]);
								exit;
							}
						}
						
						$todaySpins = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->count_spins($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
						if($todaySpins != false){
							if($todaySpins['count'] >= $this->vars['plugin_config']['max_spins_per_day']){
								echo $this->pluginaizer->jsone(['error' => sprintf(__('Max allowed spins per day %s, come back tomorrow'), $this->vars['plugin_config']['max_spins_per_day'])]);
								exit;
							}
						}
						
						$this->load->lib('iteminfo');
						$this->load->lib('itemimage');
						$this->load->lib('createitem', [MU_VERSION, SOCKET_LIBRARY, $this->pluginaizer->website->get_value_from_server($this->pluginaizer->session->userdata(['user' => 'server']), 'item_size')]);
						$this->load->model('shop');
								
						$this->vars['wheel_rewards'] = $this->config->values('wheel_of_fortune_rewards', $this->pluginaizer->session->userdata(['user' => 'server']));
						if(!empty($this->vars['wheel_rewards']['rewards'])){
							$probabilities = [];
							$ui = 0;
							foreach($this->vars['wheel_rewards']['rewards'] AS $k => $v){
								if($ui >= $this->vars['plugin_config']['total_rewards'])
									continue;

								if($v['probability'] > 0){
									$probabilities[$k] = $v['probability'];
								}
								$ui++;
							}
							
							$chance = $this->draw_chance($probabilities);
							
							$totalSpins = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->count_total_spins($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
							if($totalSpins == false){
								$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->insert_total_spins($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
								$totalSpins = 0;
							}
							else{
								$totalSpins = $totalSpins['amount'] + 1;
								$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->update_total_spins($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
							}
								
							if(defined('CUSTOM_WHEEL') && CUSTOM_WHEEL == 1){
								if(isset($this->vars['plugin_config']['special_award_id']) && $this->vars['plugin_config']['special_award_id'] != ''){
									if(isset($this->vars['plugin_config']['spins_required_for_special_award']) && $this->vars['plugin_config']['spins_required_for_special_award'] != 0){
										if($chance == $this->vars['plugin_config']['spins_required_for_special_award']){
											$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->reset_total_spins($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
										}
										else{
											if($this->vars['plugin_config']['spins_required_for_special_award'] <= $totalSpins){
												$chance = $this->vars['plugin_config']['special_award_id'];
												$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->reset_total_spins($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
											}
										}
									}
								}
							}
							
							$deg = ($chance * 36) - 18;
							
							$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->log_spin($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $chance);
							//if($this->vars['wheel_rewards']['rewards'][$chance]['reward_type'] != 0){
								if(isset($this->vars['wheel_rewards']['rewards'][$chance]['generate_code']) && $this->vars['wheel_rewards']['rewards'][$chance]['generate_code'] == 1){
									$coupon = strtoupper(substr(base_convert(sha1(uniqid(mt_rand())), 16, 36), 0, 8));
								}
								else{
									$coupon = false;
								}
								$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->log_reward($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $chance, $coupon, $this->vars['wheel_rewards']['rewards'][$chance]);
							//}
							
							if($free == 1){
								$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->decrease_free_spins($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), 1);
								$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->add_account_log('Spin Wheel For Free', 0, $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));						
								if(isset($this->vars['plugin_config']['webhook_url']) && $this->vars['plugin_config']['webhook_url'] != ''){
									$this->load->lib('discord', [$this->vars['plugin_config']['webhook_url']]);
									$msg = $this->pluginaizer->discord->newMessage()->setContent('['.substr($this->pluginaizer->session->userdata(['user' => 'username']), 0, -3) . '*******] won '.$_SESSION['wheel_rewards'][$chance]['name'].' from Mystery Box'); 
									$msg->send();
								}
								echo $this->pluginaizer->jsone(['rid' => $deg, 'id' => $chance, 'cid' => $chance-1, 'rewardCount' => count($this->vars['wheel_rewards']['rewards']), 'left_free_spins' => $this->vars['freeSpins'] - 1]);
							
							}
							else{
								if(in_array($this->vars['plugin_config']['spin_currency'], [1,2])){
									$this->pluginaizer->website->charge_credits($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['plugin_config']['spin_price'], $this->vars['plugin_config']['spin_currency'], $this->pluginaizer->session->userdata(['user' => 'id']));
								}
								if(in_array($this->vars['plugin_config']['spin_currency'], [5])){
									$this->pluginaizer->website->charge_credits($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['plugin_config']['spin_price'], 3, $this->pluginaizer->session->userdata(['user' => 'id']));
								}
								if($this->vars['plugin_config']['spin_currency'] == 3){	
									$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->remove_wcoins($acc, $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['plugin_config']['spin_price'], $this->vars['table_config']['wcoins']);
								}
								if($this->vars['plugin_config']['spin_currency'] == 4){
									$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->remove_wcoins($acc, $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['plugin_config']['spin_price'], $this->vars['table_config']['goblinpoint']);
								}
							
								$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->add_account_log('Spin Wheel For '.$this->vars['currency_name'].'', -$this->vars['plugin_config']['spin_price'], $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));						
								if(isset($this->vars['plugin_config']['webhook_url']) && $this->vars['plugin_config']['webhook_url'] != ''){
									$this->load->lib('discord', [$this->vars['plugin_config']['webhook_url']]);
									$msg = $this->pluginaizer->discord->newMessage()->setContent('['.substr($this->pluginaizer->session->userdata(['user' => 'username']), 0, -3) . '*******] won '.$_SESSION['wheel_rewards'][$chance]['name'].' from Mystery Box'); 
									$msg->send();
								}
								echo $this->pluginaizer->jsone(['rid' => $deg, 'id' => $chance, 'cid' => $chance-1, 'rewardCount' => count($this->vars['wheel_rewards']['rewards']), 'left_amount' => ($this->vars['currency_amount'] - $this->vars['plugin_config']['spin_price'])]);
							}
						}
						else{
							echo $this->pluginaizer->jsone(['error' => __('No rewards configured for this wheel.')]);
						}
                    }
                } else{
                    echo $this->pluginaizer->jsone(['error' => __('Plugin configuration not found.')]);
                }
            } else{
                echo $this->pluginaizer->jsone(['error' => __('Please login first!')]);
            }
		}
		
		private function draw_chance(array $weightedValues){
			$rand = mt_rand(1, (int)array_sum($weightedValues));

			foreach($weightedValues as $key => $value){
			  $rand -= $value;
			  if($rand <= 0){
				return $key;
			  }
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
                $this->load->model('application/plugins/'.$this->pluginaizer->get_plugin_class().'/models/'.$this->pluginaizer->get_plugin_class().'');
                $this->vars['is_multi_server'] = $this->pluginaizer->data()->value('is_multi_server');
                $this->vars['plugin_config'] = $this->pluginaizer->plugin_config();
                //load any js, css files if required
                $this->vars['js'] = $this->config->base_url . 'assets/plugins/js/'.$this->pluginaizer->get_plugin_class().'.js';
                //load template
                $this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . 'admin' . DS . 'view.index', $this->vars);
            } else{
                $this->pluginaizer->redirect($this->config->base_url . 'admincp/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()) . '/admin');
            }
        }
		
		public function rewards(){
			if($this->pluginaizer->session->is_admin()){
                //load website helper
                $this->load->helper('website');
				$this->load->helper('webshop');
				$this->load->lib('serverfile');
                $this->load->model('application/plugins/'.$this->pluginaizer->get_plugin_class().'/models/'.$this->pluginaizer->get_plugin_class().'');
				$this->load->model('admin');
                $this->vars['is_multi_server'] = $this->pluginaizer->data()->value('is_multi_server');
                $this->vars['plugin_config'] = $this->pluginaizer->plugin_config();
				
				$this->vars['server'] = isset($_GET['server']) ? $_GET['server'] : false;

				if($this->vars['server'] != false){
					$this->vars['reward_list'] = $this->config->values('wheel_of_fortune_rewards');
					
					if(isset($_POST['add_reward'])){
						$totalProbabilty = array_sum($_POST['probability']);
						if($totalProbabilty != 10000){
							$this->vars['error'] = 'Total probability should be 10000, your probability = '.$totalProbabilty;
						}
						
						$count = isset($this->vars['plugin_config'][$this->vars['server']]['total_rewards']) ? $this->vars['plugin_config'][$this->vars['server']]['total_rewards'] : 10;
						
						for($i = 1; $i <= $count; $i++){
							if($_POST['probability'][$i] == ''){
								$_POST['probability'][$i] = 0;
							}
							if($_POST['amount'][$i] == ''){
								$_POST['amount'][$i] = 0;
							}
							$this->vars['reward_list'][$this->vars['server']]['rewards'][$i] = [
								'reward_type' => $_POST['reward_type'][$i],
								'probability' => $_POST['probability'][$i],
							];
							if($_POST['reward_type'][$i] != 7){
								$this->vars['reward_list'][$this->vars['server']]['rewards'][$i]['amount'] = $_POST['amount'][$i];
								$this->vars['reward_list'][$this->vars['server']]['rewards'][$i]['generate_code'] = $_POST['generate_code'][$i];
								$this->vars['reward_list'][$this->vars['server']]['rewards'][$i]['item'] = [];
							}
							else{
								$this->vars['reward_list'][$this->vars['server']]['rewards'][$i]['amount'] = 0;
								$this->vars['reward_list'][$this->vars['server']]['rewards'][$i]['generate_code'] = $_POST['generate_code'][$i];
								$this->vars['reward_list'][$this->vars['server']]['rewards'][$i]['item'] = [
									'cat' => $_POST['item_category'][$i],
									'id' => $_POST['item_index'][$i],
									'dur' => $_POST['item_dur'][$i],
									'lvl' => $_POST['item_level'][$i],
									'skill' => $_POST['item_skill'][$i],
									'luck' => $_POST['item_luck'][$i],
									'opt' => $_POST['item_option'][$i],
									'exe' => $_POST['item_excellent'][$i],
									'anc' => $_POST['item_ancient'][$i],
									'expires' => $_POST['item_expires'][$i]
								];
							}
						}
						if(!isset($this->vars['error'])){
							$this->config->save_config_data($this->vars['reward_list'], 'wheel_of_fortune_rewards');
							$this->vars['success'] = 'Rewards successfully updated.';
						}
					}
				}
				else{
					$this->vars['invalid'] = 'Server not found.';
				}
                //load template
                $this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . 'admin' . DS . 'view.rewards', $this->vars);
            } else{
                $this->pluginaizer->redirect($this->config->base_url . 'admincp/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()) . '/admin');
            }
		}
		
		public function logs($page = 1, $acc = '-', $server = 'All'){
            //check if visitor has administrator privilleges
            if($this->pluginaizer->session->is_admin()){
                //load website helper
                $this->load->helper('website');
                //load paginator
                $this->load->lib('pagination');
				$this->load->lib("iteminfo");
                $this->load->model('application/plugins/'.$this->pluginaizer->get_plugin_class().'/models/'.$this->pluginaizer->get_plugin_class().'');
				$this->vars['is_multi_server'] = $this->pluginaizer->data()->value('is_multi_server');
                $this->vars['plugin_config'] = $this->pluginaizer->plugin_config();
				
                if(isset($_POST['search_logs'])){
                    $server = (isset($_POST['server']) && $_POST['server'] != 'All') ? $_POST['server'] : 'All';
                    $acc = isset($_POST['account']) ? $_POST['account'] : '';
                    if($acc == ''){
                        $this->vars['error'] = 'Invalid account';
                    } else{
                        $this->vars['logs'] = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->load_logs(1, 25, $acc, $server);
                        $this->pluginaizer->pagination->initialize(1, 25, $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->count_total_logs($acc, $server), $this->config->base_url . $this->pluginaizer->get_plugin_class().'/logs/%s/' . $acc . '/' . $server . '');
                        $this->vars['pagination'] = $this->pluginaizer->pagination->create_links();
                    }
                } else{
                    $this->vars['logs'] = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->load_logs($page, 25, $acc, $server);
                    $lk = '';
                    if($acc != '')
                        $lk .= '/' . $acc;
                    $lk .= '/' . $server;
                    $this->pluginaizer->pagination->initialize($page, 25, $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->count_total_logs($acc, $server), $this->config->base_url . $this->pluginaizer->get_plugin_class().'/logs/%s' . $lk);
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
                    'description' => 'Play wheel of fortune and get random rewards.' //description which will see user
                ]);
                //create plugin config template
                $this->pluginaizer->create_config(['active' => 0, 'spin_price' => 500, 'spin_currency' => 1, 'max_spins_per_day' => 5, 'total_rewards' => 10, 'webhook_url' => '', 'style' => 1]);
                //add sql scheme if there is any into website database
                //all schemes should be located in plugin_folder/sql_schemes
                $this->pluginaizer->add_sql_scheme('wheel_of_fortune_rewards');
				$this->pluginaizer->add_sql_scheme('wheel_of_fortune_log');
				$this->pluginaizer->add_sql_scheme('wheel_of_fortune_spins');
				$this->pluginaizer->add_sql_scheme('wheel_of_fortune_free_spins');
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
                $this->pluginaizer->delete_config()->remove_sql_scheme('wheel_of_fortune_log')->remove_sql_scheme('wheel_of_fortune_rewards')->remove_sql_scheme('wheel_of_fortune_spins')->remove_sql_scheme('wheel_of_fortune_free_spins')->remove_plugin();
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