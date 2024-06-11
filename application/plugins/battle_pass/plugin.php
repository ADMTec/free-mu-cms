<?php
    in_file();

    class _plugin_battle_pass extends controller implements pluginInterface
    {
        private $pluginaizer;
        private $vars = [];
		public $errors = [];

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
        public function index(){
            if($this->pluginaizer->data()->value('installed') == false){
                throw new Exception('Plugin has not yet been installed.');
            } else{
                if($this->pluginaizer->data()->value('installed') == 1){
                    if($this->pluginaizer->data()->value('is_public') == 0){
                        $this->user_module();
                    } else{
                        $this->public_module();
                    }
                } else{
                    throw new Exception('Plugin has been disabled.');
                }
            }
        }
		
		
		public function upgrade(){
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
                        $this->vars['module_disabled'] = __('This module has been disabled.');
                    } 
					else{
                        $this->load->model('application/plugins/'.$this->pluginaizer->get_plugin_class().'/models/'.$this->pluginaizer->get_plugin_class());
						$this->load->model('shop');
						$this->vars['pass'] = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->checkPassType($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['plugin_config']['battle_pass_start_time']);
						
						if($this->vars['pass'] == false){
							$this->vars['pass']['pass_type'] = 0;
						}
						else{
							if($this->vars['pass']['pass_type'] == 2){
								$this->vars['config_not_found'] = __('You already have platinum pass.');
							}
						}
						
						try{
							if(isset($_POST['pass_type'])){
								if(!in_array($_POST['pass_type'], [1,2])){
									throw new Exception(__('Invalid pass type selected'));
								}
								if($_POST['pass_type'] == $this->vars['pass']['pass_type']){
									throw new Exception(__('You already have this pass type'));
								}
									
								if($_POST['pass_type'] == 1){
									$chargeAmount = $this->vars['plugin_config']['silver_pass_upgrade_price'];
									$chargeType = $this->vars['plugin_config']['silver_pass_payment_type']; 
									$type = 'Silver';
								}
								if($_POST['pass_type'] == 2){
									$chargeAmount = $this->vars['plugin_config']['platinum_pass_upgrade_price'];
									$chargeType = $this->vars['plugin_config']['platinum_pass_payment_type'];  
									$type = 'Platinum';
								}

								if($this->vars['pass']['pass_type'] == 1 && $_POST['pass_type'] == 2){
									$chargeAmount -= $this->vars['plugin_config']['silver_pass_upgrade_price'];
								}

								$status = $this->pluginaizer->website->get_user_credits_balance($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $chargeType, $this->pluginaizer->session->userdata(['user' => 'id']));
								if($status['credits'] < $chargeAmount){
									throw new Exception(sprintf(__('You have insufficient amount of %s'), $this->pluginaizer->website->translate_credits($chargeType, $this->pluginaizer->session->userdata(['user' => 'server']))));
								} 	
								$this->pluginaizer->website->charge_credits($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $chargeAmount, $chargeType, $this->pluginaizer->session->userdata(['user' => 'id']));
								$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->add_account_log('Purchased '.$type.' For ' . $this->pluginaizer->website->translate_credits($chargeType, $this->pluginaizer->session->userdata(['user' => 'server'])), -$chargeAmount, $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
								$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->upgradePass($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['plugin_config']['battle_pass_start_time'], $_POST['pass_type']);																		
								header('Location: '.$this->config->base_url.'battle-pass');
							}
						}
						catch(\Exception $e){
							$this->vars['error'] = $e->getMessage();
						}
						
					}
				}
				else{
					$this->vars['config_not_found'] = __('Plugin configuration not found.');
				}
				//set js
                $this->vars['js'] = $this->config->base_url . 'assets/plugins/js/'.$this->pluginaizer->get_plugin_class().'.js';
                //load template
                $this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . $this->config->config_entry('main|template') . DS . 'view.upgrade', $this->vars);
            } else{
                $this->pluginaizer->redirect($this->config->base_url . 'account-panel/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()));
            }
		}
		
		
		public function reset(){
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
                        $this->vars['module_disabled'] = __('This module has been disabled.');
                    } 
					else{
                        $this->load->model('application/plugins/'.$this->pluginaizer->get_plugin_class().'/models/'.$this->pluginaizer->get_plugin_class());
						$this->load->model('shop');
						
						try{
							if(isset($_POST['reset_progress'])){
								if(isset($this->vars['plugin_config']['allow_reset_pass_progress']) && $this->vars['plugin_config']['allow_reset_pass_progress'] == 1){
									$status = $this->pluginaizer->website->get_user_credits_balance($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['plugin_config']['reset_pass_progress_payment_type'], $this->pluginaizer->session->userdata(['user' => 'id']));
									if($status['credits'] < $this->vars['plugin_config']['reset_pass_progress_price']){
										throw new Exception(sprintf(__('You have insufficient amount of %s'), $this->pluginaizer->website->translate_credits($this->vars['plugin_config']['reset_pass_progress_payment_type'], $this->pluginaizer->session->userdata(['user' => 'server']))));
									}

									$this->vars['pass_levels'] = $this->config->values('battle_pass_levels', $this->pluginaizer->session->userdata(['user' => 'server']));
									$this->vars['progress'] = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->getBattlePassProgress($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['plugin_config']['battle_pass_start_time']);
									$lastKey = array_key_last($this->vars['pass_levels']);
									if($this->vars['progress']['is_completed'] == 1 && $this->vars['pass_levels'][$lastKey]['id'] == $this->vars['progress']['pass_level']){
										$this->pluginaizer->website->charge_credits($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['plugin_config']['reset_pass_progress_price'], $this->vars['plugin_config']['reset_pass_progress_payment_type'], $this->pluginaizer->session->userdata(['user' => 'id']));
										$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->add_account_log('Reset Battle Pass For ' . $this->pluginaizer->website->translate_credits($this->vars['plugin_config']['reset_pass_progress_payment_type'], $this->pluginaizer->session->userdata(['user' => 'server'])), -$this->vars['plugin_config']['reset_pass_progress_price'], $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
										$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->resetPass($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['plugin_config']['battle_pass_start_time']);																		
										header('Location: '.$this->config->base_url.'battle-pass');
										
									}
									else{
										throw new Exception(__('Please complete all battle pass levels first.'));
									}
								}
								else{
									throw new Exception(__('Reset battle pass progress disabled.'));
								}
							}
						}
						catch(\Exception $e){
							$this->vars['error'] = $e->getMessage();
						}
					}
				}
				else{
					$this->vars['config_not_found'] = __('Plugin configuration not found.');
				}
				//set js
                $this->vars['js'] = $this->config->base_url . 'assets/plugins/js/'.$this->pluginaizer->get_plugin_class().'.js';
                //load template
                $this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . $this->config->config_entry('main|template') . DS . 'view.reset', $this->vars);
            } else{
                $this->pluginaizer->redirect($this->config->base_url . 'account-panel/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()));
            }
		}
		
		
		private function arrayKeyFirst($array){
			foreach($array as $key => $unused){
				return $key;
			}
			return NULL;
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
            //if($this->pluginaizer->session->is_user()){
                //load website helper
                $this->load->helper('website');
				
				$this->vars['is_logged_in'] = $this->pluginaizer->session->is_user();
				
				if($this->vars['is_logged_in'] != true){
					$server = array_key_first($this->website->server_list());
					$this->session->register('user', [
						'server' => $server, 
					]);
				}
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
                    } 
					else{
						$this->load->model('application/plugins/'.$this->pluginaizer->get_plugin_class().'/models/'.$this->pluginaizer->get_plugin_class());
						$this->load->model('shop');
						
						if($this->vars['is_logged_in'] == true){
							
							$this->vars['pass'] = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->checkPassType($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['plugin_config']['battle_pass_start_time']);
							
							if($this->vars['pass'] == false){
								$this->vars['pass']['pass_type'] = 0;
								$this->vars['pass_title'] = __('Free');
							}
							else{
								switch($this->vars['pass']['pass_type']){
									default:
										$this->vars['pass_title'] = __('Free');
									break;
									case 1:
										$this->vars['pass_title'] = __('Silver');
									break;
									case 2:
										$this->vars['pass_title'] = __('Platinum');
									break;
								}
							}
						}
						else{
							$this->vars['pass']['pass_type'] = 0;
							$this->vars['pass_title'] = __('Free');
						}
						
						$this->vars['pass_levels'] = $this->config->values('battle_pass_levels', $this->pluginaizer->session->userdata(['user' => 'server']));
						$this->vars['pass_rewards'] = $this->config->values('battle_pass_rewards', $this->pluginaizer->session->userdata(['user' => 'server']));
						$this->vars['pass_requirements'] = $this->config->values('battle_pass_req', $this->pluginaizer->session->userdata(['user' => 'server']));
						
						if($this->vars['is_logged_in'] == true){
							$this->vars['progress'] = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->getBattlePassProgress($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['plugin_config']['battle_pass_start_time']);
							if($this->vars['progress'] == false){
								$this->vars['progress']['pass_level'] = $this->vars['pass_levels'][$this->arrayKeyFirst($this->vars['pass_levels'])]['id'];
								$this->vars['progress']['is_completed'] = 0;
								$this->vars['progress']['is_free_reward_taken'] = 0;
								$this->vars['progress']['is_silver_reward_taken'] = 0;
								$this->vars['progress']['is_platinum_reward_taken'] = 0;
								$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->insertBattlePassProgress($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['plugin_config']['battle_pass_start_time'], $this->vars['progress']['pass_level']);
							}
							else{	
								if($this->vars['progress']['date_completed'] != null){
									try{
										$date = new DateTime($this->vars['progress']['date_completed']);
										$timestamp = $date->getTimestamp();
									} catch (Exception $e) {
										$date = DateTime::createFromFormat('M d Y H:i:s:A', $this->vars['progress']['date_completed']);
										$timestamp = $date->getTimestamp();
									}
								}
								else{
									$timestamp = null;
								}
								
								if($this->vars['progress']['is_completed'] == 1 && date('Y-m-d', $timestamp) != date('Y-m-d', time())){
									$this->vars['last_key'] = null;
									$this->vars['last_completed_level'] = null;
									$this->vars['next_key'] = null;
									foreach($this->vars['pass_levels'] AS $pkey => $pdata){
										if($this->vars['progress']['pass_level'] == $pdata['id']){
											$this->vars['last_key'] = $pkey;
											$this->vars['last_completed_level'] = $this->vars['progress']['pass_level'];
											break;
										}
									}
									$keys = array_keys($this->vars['pass_levels']);
									$position = array_search($this->vars['last_key'], $keys, true);
									if(isset($keys[$position + 1])){
										$this->vars['next_key'] = $keys[$position + 1];
									}
									if($this->vars['next_key'] != false){
										$this->vars['progress']['pass_level'] = $this->vars['pass_levels'][$this->vars['next_key']]['id'];
										$this->vars['progress']['is_completed'] = 0;
										$this->vars['progress']['is_free_reward_taken'] = 0;
										$this->vars['progress']['is_silver_reward_taken'] = 0;
										$this->vars['progress']['is_platinum_reward_taken'] = 0;
										$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->insertBattlePassProgress($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['plugin_config']['battle_pass_start_time'], $this->vars['progress']['pass_level']);
									}
									else{
										$this->vars['progress']['pass_level'] = $this->vars['last_key'];
									}
								}
							}
							
						}
						else{
							$this->vars['progress']['pass_level'] = $this->vars['pass_levels'][$this->arrayKeyFirst($this->vars['pass_levels'])]['id'];
							$this->vars['progress']['is_completed'] = 0;
							$this->vars['progress']['is_free_reward_taken'] = 0;
							$this->vars['progress']['is_silver_reward_taken'] = 0;
							$this->vars['progress']['is_platinum_reward_taken'] = 0;
							
						}
						
						
						$this->load->lib('iteminfo');
						$this->load->lib('itemimage');
						$this->load->lib('createitem', [MU_VERSION, SOCKET_LIBRARY, $this->pluginaizer->website->get_value_from_server($this->pluginaizer->session->userdata(['user' => 'server']), 'item_size')]);
						$this->load->lib('npc');
						
						
						if(!empty($this->vars['pass_requirements'])){
							foreach($this->vars['pass_requirements'] AS $prKey => $prData){
								if(!empty($prData)){
									foreach($prData AS $prKey2 => $prData2){
										if($prData2['req_type'] == 10){
											if(!empty($prData2['items'])){
												$item = $prData2['items'][0];
												if($this->pluginaizer->iteminfo->setItemData($item['id'], $item['cat'], $this->pluginaizer->website->get_value_from_server($this->pluginaizer->session->userdata(['user' => 'server']), 'item_size'))){	
													$this->pluginaizer->createitem->setItemData($this->pluginaizer->iteminfo->item_data);
													$this->pluginaizer->createitem->id($item['id']);
													$this->pluginaizer->createitem->cat($item['cat']);
													$this->pluginaizer->createitem->refinery(false);
													$this->pluginaizer->createitem->serial(0);
													if($this->pluginaizer->website->get_value_from_server($this->pluginaizer->session->userdata(['user' => 'server']), 'item_size') == 64){
														$this->createitem->serial2(true);
													}
													if($item['lvl'] != ''){
														$this->pluginaizer->createitem->lvl($item['lvl']);
													}
													else{
														$this->pluginaizer->createitem->lvl(0);
													}
													if($item['skill'] != '' && $item['skill'] == 1){
														$this->pluginaizer->createitem->skill(true);
													}
													else{
														$this->pluginaizer->createitem->skill(false);
													}
													if($item['luck'] != '' && $item['luck'] == 1){
														$this->pluginaizer->createitem->luck(true);
													}
													else{
														$this->pluginaizer->createitem->luck(false);
													}
													if($item['opt'] != ''){
														$this->pluginaizer->createitem->opt($item['opt']);
													}
													else{
														$this->pluginaizer->createitem->opt(0);
													}
													if($item['exe'] != ''){
														$exe_opts = [0 => 1, 1 => 2, 2 => 4, 3 => 8, 4 => 16, 5 => 32];
														$iexe = explode(',', $item['exe']);
														$exe = 0;

														foreach($iexe AS $ek => $eval){
															if($eval == 0){
																unset($iexe[$ek]);
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
													if($item['anc'] != '' && $item['anc'] != 0){
														$this->pluginaizer->createitem->ancient($item['anc']);
													}
													
													$itemHex = $this->pluginaizer->createitem->to_hex();
													$this->pluginaizer->iteminfo->itemData($itemHex, true, $this->pluginaizer->session->userdata(['user' => 'server']));
													$this->vars['pass_requirements'][$prKey][$prKey2]['item'] = [
														'hex' => $itemHex,
														'name' => $this->pluginaizer->iteminfo->getNameStyle(true),
														'amount' => $item['amount'],
														'data' => $item,
														'item_info' => $this->pluginaizer->itemimage->load($this->pluginaizer->iteminfo->id, $this->pluginaizer->iteminfo->type, (int)substr($this->pluginaizer->iteminfo->getLevel(), 1)) . '<br />' . $this->pluginaizer->iteminfo->allInfo()
													];
												}
											}
										}
									}
								}
							}
						}
						
						$this->vars['user_requirement_data'] = [];
						
						if(!empty($this->vars['pass_requirements'][$this->vars['progress']['pass_level']]) && $this->vars['progress']['pass_level'] != -1 && $this->vars['is_logged_in'] == true){
							$requirementCount = count($this->vars['pass_requirements'][$this->vars['progress']['pass_level']]);
							$completedCount = 0;
							foreach($this->vars['pass_requirements'][$this->vars['progress']['pass_level']] AS $reqId => $reqData){
								$checkReq = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->checkCompletedRequirement($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['plugin_config']['battle_pass_start_time'], $this->vars['progress']['pass_level'], $reqId);
								if($checkReq != false){
									$this->vars['user_requirement_data'][$this->vars['progress']['pass_level']][$reqId]['completed'] = 1;
									$completedCount += 1;
								}
								else{
									if($reqData['req_type'] == 1){
										$amount = $reqData['total_votes'];
										$votes = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->check_votes($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'id']), $this->pluginaizer->session->userdata(['user' => 'server']));
										if($votes >= $amount){
											$this->vars['user_requirement_data'][$this->vars['progress']['pass_level']][$reqId]['completed'] = 1;
											$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->setCompletedRequirement($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['plugin_config']['battle_pass_start_time'], $this->vars['progress']['pass_level'], $reqId);
											$completedCount += 1;
										}
										else{
											$this->vars['user_requirement_data'][$this->vars['progress']['pass_level']][$reqId]['completeProgress'] = $votes;
											$percents = ($votes * 100) / $amount;
											$this->vars['user_requirement_data'][$this->vars['progress']['pass_level']][$reqId]['progressPerc'] = $percents;
										}
									}
									if($reqData['req_type'] == 11){
										$amount = $reqData['total_votes'];
										$votes = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->check_votes_specific($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'id']), $this->pluginaizer->session->userdata(['user' => 'server']), $reqData['vote_type']);
										if($votes >= $amount){
											$this->vars['user_requirement_data'][$this->vars['progress']['pass_level']][$reqId]['completed'] = 1;
											$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->setCompletedRequirement($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['plugin_config']['battle_pass_start_time'], $this->vars['progress']['pass_level'], $reqId);
											$completedCount += 1;
										}
										else{
											$this->vars['user_requirement_data'][$this->vars['progress']['pass_level']][$reqId]['completeProgress'] = $votes;
											$percents = ($votes * 100) / $amount;
											$this->vars['user_requirement_data'][$this->vars['progress']['pass_level']][$reqId]['progressPerc'] = $percents;
										}
									}
									if($reqData['req_type'] == 12){
										$amount = $reqData['enter_count'];
										$enterCount = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->check_event_entry_count($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'id']), $this->pluginaizer->session->userdata(['user' => 'server']), $reqData['event_type']);
										if($enterCount >= $amount){
											$this->vars['user_requirement_data'][$this->vars['progress']['pass_level']][$reqId]['completed'] = 1;
											$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->setCompletedRequirement($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['plugin_config']['battle_pass_start_time'], $this->vars['progress']['pass_level'], $reqId);
											$completedCount += 1;
										}
										else{
											$this->vars['user_requirement_data'][$this->vars['progress']['pass_level']][$reqId]['completeProgress'] = $enterCount;
											$percents = ($votes * 100) / $amount;
											$this->vars['user_requirement_data'][$this->vars['progress']['pass_level']][$reqId]['progressPerc'] = $percents;
										}
									}
									if($reqData['req_type'] == 2){
										$amount = $reqData['total_donate'];
										$donation = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->check_donations($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'id']), $this->pluginaizer->session->userdata(['user' => 'server']));
										if($donation >= $amount){
											$this->vars['user_requirement_data'][$this->vars['progress']['pass_level']][$reqId]['completed'] = 1;
											$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->setCompletedRequirement($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['plugin_config']['battle_pass_start_time'], $this->vars['progress']['pass_level'], $reqId);
											$completedCount += 1;
										}
										else{
											$this->vars['user_requirement_data'][$this->vars['progress']['pass_level']][$reqId]['completeProgress'] = $donation;
											$percents = ($donation * 100) / $amount;
											$this->vars['user_requirement_data'][$this->vars['progress']['pass_level']][$reqId]['progressPerc'] = $percents;
										}
									}
									if($reqData['req_type'] == 3){
										$amount = $reqData['mamount'];
										$monsters = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->check_monsters($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'id']), $this->pluginaizer->session->userdata(['user' => 'server']), $reqData['monsters']);
										if($monsters >= $amount){
											$this->vars['user_requirement_data'][$this->vars['progress']['pass_level']][$reqId]['completed'] = 1;
											$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->setCompletedRequirement($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['plugin_config']['battle_pass_start_time'], $this->vars['progress']['pass_level'], $reqId);
											$completedCount += 1;
										}
										else{
											$this->vars['user_requirement_data'][$this->vars['progress']['pass_level']][$reqId]['completeProgress'] = $monsters;
											$percents = ($monsters * 100) / $amount;
											$this->vars['user_requirement_data'][$this->vars['progress']['pass_level']][$reqId]['progressPerc'] = $percents;
										}
									}
									if($reqData['req_type'] == 4){
										$amount = $reqData['total_kills'];
										$unique = 0;
										$minRes = 0;
										if(isset($reqData['unique']) && $reqData['unique'] != ''){
											$uniqueData = explode('|', $reqData['unique']);
											$unique = $uniqueData[0];
											$minRes = $uniqueData[1];
										}
										$kills = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->check_kills($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'id']), $this->pluginaizer->session->userdata(['user' => 'server']), $unique, $minRes);
										if(is_array($kills)){
											foreach($kills AS $info){
												$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->setKillsChecked($info['Victim'], $info['Killer'], $info['KillDate'], $this->pluginaizer->session->userdata(['user' => 'server']));
											}
											$kills = count($kills);
										}
										
										if($kills == null)
											$kills = 0;
										
										if($kills >= $amount){
											$this->vars['user_requirement_data'][$this->vars['progress']['pass_level']][$reqId]['completed'] = 1;
											$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->setCompletedRequirement($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['plugin_config']['battle_pass_start_time'], $this->vars['progress']['pass_level'], $reqId);
											$completedCount += 1;
										}
										else{
											$this->vars['user_requirement_data'][$this->vars['progress']['pass_level']][$reqId]['completeProgress'] = $kills;
											$percents = ($kills * 100) / $amount;
											$this->vars['user_requirement_data'][$this->vars['progress']['pass_level']][$reqId]['progressPerc'] = $percents;
										}
									}
									if($reqData['req_type'] == 5){
										$amount = $reqData['total_stats'];
										$resets = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->check_resets($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'id']), $this->pluginaizer->session->userdata(['user' => 'server']));
										if($resets == null)
											$resets = 0;
										if($resets >= $amount){
											$this->vars['user_requirement_data'][$this->vars['progress']['pass_level']][$reqId]['completed'] = 1;
											$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->setCompletedRequirement($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['plugin_config']['battle_pass_start_time'], $this->vars['progress']['pass_level'], $reqId);
											$completedCount += 1;
										}
										else{
											$this->vars['user_requirement_data'][$this->vars['progress']['pass_level']][$reqId]['completeProgress'] = $resets;
											$percents = ($resets * 100) / $amount;
											$this->vars['user_requirement_data'][$this->vars['progress']['pass_level']][$reqId]['progressPerc'] = $percents;
										}
									}
									if($reqData['req_type'] == 6){
										$amount = $reqData['total_stats'];
										$resets = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->check_gresets($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'id']), $this->pluginaizer->session->userdata(['user' => 'server']));
										if($resets == null)
											$resets = 0;
										if($resets >= $amount){
											$this->vars['user_requirement_data'][$this->vars['progress']['pass_level']][$reqId]['completed'] = 1;
											$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->setCompletedRequirement($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['plugin_config']['battle_pass_start_time'], $this->vars['progress']['pass_level'], $reqId);
											$completedCount += 1;
										}
										else{
											$this->vars['user_requirement_data'][$this->vars['progress']['pass_level']][$reqId]['completeProgress'] = $resets;
											$percents = ($resets * 100) / $amount;
											$this->vars['user_requirement_data'][$this->vars['progress']['pass_level']][$reqId]['progressPerc'] = $percents;
										}
										
									}
									if($reqData['req_type'] == 9){
										$amount = $reqData['total_stats'];
										$online = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->check_online_time($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'id']), $this->pluginaizer->session->userdata(['user' => 'server']));
										if($online == null)
											$online = 0;
										if($online >= $amount){
											$this->vars['user_requirement_data'][$this->vars['progress']['pass_level']][$reqId]['completed'] = 1;
											$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->setCompletedRequirement($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['plugin_config']['battle_pass_start_time'], $this->vars['progress']['pass_level'], $reqId);
											$completedCount += 1;
										}
										else{
											$this->vars['user_requirement_data'][$this->vars['progress']['pass_level']][$reqId]['completeProgress'] = $online;
											$percents = ($online * 100) / $amount;
											$this->vars['user_requirement_data'][$this->vars['progress']['pass_level']][$reqId]['progressPerc'] = $percents;
										}	
									}
									if($reqData['req_type'] == 7){
										$amount = $reqData['total_items_buy'];
										$shop = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->check_shop($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'id']), $this->pluginaizer->session->userdata(['user' => 'server']));
										if($shop == null)
											$shop = 0;
										if($shop >= $amount){
											$this->vars['user_requirement_data'][$this->vars['progress']['pass_level']][$reqId]['completed'] = 1;
											$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->setCompletedRequirement($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['plugin_config']['battle_pass_start_time'], $this->vars['progress']['pass_level'], $reqId);
											$completedCount += 1;
										}
										else{
											$this->vars['user_requirement_data'][$this->vars['progress']['pass_level']][$reqId]['completeProgress'] = $shop;
											$percents = ($shop * 100) / $amount;
											$this->vars['user_requirement_data'][$this->vars['progress']['pass_level']][$reqId]['progressPerc'] = $percents;
										}	
									}
									if($reqData['req_type'] == 8){
										$amount = $reqData['total_items_sell'];
										$market = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->check_market($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'id']), $this->pluginaizer->session->userdata(['user' => 'server']));
										if($market == null)
											$market = 0;
										if($market >= $amount){
											$this->vars['user_requirement_data'][$this->vars['progress']['pass_level']][$reqId]['completed'] = 1;
											$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->setCompletedRequirement($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['plugin_config']['battle_pass_start_time'], $this->vars['progress']['pass_level'], $reqId);
											$completedCount += 1;
										}
										else{
											$this->vars['user_requirement_data'][$this->vars['progress']['pass_level']][$reqId]['completeProgress'] = $market;
											$percents = ($market * 100) / $amount;
											$this->vars['user_requirement_data'][$this->vars['progress']['pass_level']][$reqId]['progressPerc'] = $percents;
										}
									}
									
									if($reqData['req_type'] == 10 && $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->check_connect_stat($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']))){
										$amount = $reqData['item']['amount'];
										$foundItems = 0;
										$logSerials = [];
										$this->pluginaizer->load->model('warehouse');
										$charData = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->inventory($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
										$completedItems = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->listCompletedItems($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
										if($charData != false){
											foreach($charData AS $cid => $inventory){
												$items = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->getInventoryContents($this->pluginaizer->session->userdata(['user' => 'server']), $inventory);
												
												$item = $reqData['item'];	
												$this->pluginaizer->iteminfo->itemData($item['hex'], true, $this->pluginaizer->session->userdata(['user' => 'server']));
												$idd = $this->pluginaizer->iteminfo->id;
												$type = $this->pluginaizer->iteminfo->type;
												$skill = $this->pluginaizer->iteminfo->hasSkill();
												$lvl = (int)substr($this->pluginaizer->iteminfo->getLevel(), 1);
												$opt = ($this->pluginaizer->iteminfo->getOption()*4);
												$exe = $this->pluginaizer->iteminfo->exeForCompare();
												$luck = $this->pluginaizer->iteminfo->getLuck();
												$found = false;
												$lvlOk = false;
												$optOk = false;
												$skillOk = false;
												$luckOk = false;
												$exe0Ok = false;
												$exe1Ok = false;
												$exe2Ok = false;
												$exe3Ok = false;
												$exe4Ok = false;
												$exe5Ok = false; 
												$itemCount = $item['data']['amount'];
												foreach($items AS $slot => $invItem){
												 if($invItem == str_repeat('F', $this->pluginaizer->website->get_value_from_server($this->pluginaizer->session->userdata(['user' => 'server']), 'item_size'))){
													 continue;
												 }
												 $itemInfo = $this->pluginaizer->Mwarehouse->load_item_info($invItem);

												 if($itemInfo['info']['id'] == $idd && $itemInfo['info']['cat'] == $type){
													if($item['data']['lvl'] != ''){
														if($itemInfo['info']['lvl'] >= $lvl){
															$lvlOk = true;
														}
													}
													else{
														$lvlOk = true;
													}
													if($item['data']['opt'] != ''){
														if($itemInfo['info']['opt'] >= $opt){
															$optOk = true;
														}
													}
													else{
														$optOk = true;
													}
													if($item['data']['luck'] != ''){
														if($itemInfo['info']['luck'] >= $luck){
															$luckOk = true;
														}
													}
													else{
														$luckOk = true;
													}
													if($item['data']['skill'] != ''){
														if($itemInfo['info']['skill'] >= $skill){
															$skillOk = true;
														}
													}
													else{
														$skillOk = true;
													}
													if($item['data']['exe'] != ''){
														if($itemInfo['info']['id'] == 37 && $itemInfo['info']['cat'] == 13){
															if($exe[0] == 0 && $exe[1] == 0 && $exe[2] == 0 && $exe[3] == 0 && $exe[4] == 0 && $exe[5] == 0){
																$exe0Ok = true;
																$exe1Ok = true;
																$exe2Ok = true;
																$exe3Ok = true;
																$exe4Ok = true;
																$exe5Ok = true;
															}
															else{	
																if($exe[0] == 1 && $exe[1] == 0 && $exe[2] == 0 && $exe[3] == 0 && $exe[4] == 0 && $exe[5] == 0){
																	if($itemInfo['exe_opts'][0] == 1){
																		$exe0Ok = true;
																		$exe1Ok = true;
																		$exe2Ok = true;
																		$exe3Ok = true;
																		$exe4Ok = true;
																		$exe5Ok = true;
																	}
																}
																if($exe[0] == 0 && $exe[1] == 1 && $exe[2] == 0 && $exe[3] == 0 && $exe[4] == 0 && $exe[5] == 0){
																	if($itemInfo['exe_opts'][1] == 1){
																		$exe0Ok = true;
																		$exe1Ok = true;
																		$exe2Ok = true;
																		$exe3Ok = true;
																		$exe4Ok = true;
																		$exe5Ok = true;
																	}
																}
																if($exe[0] == 0 && $exe[1] == 0 && $exe[2] == 1 && $exe[3] == 0 && $exe[4] == 0 && $exe[5] == 0){
																	if($itemInfo['exe_opts'][2] == 1){
																		$exe0Ok = true;
																		$exe1Ok = true;
																		$exe2Ok = true;
																		$exe3Ok = true;
																		$exe4Ok = true;
																		$exe5Ok = true;
																	}
																}
															}
														}
														else{
															if($itemInfo['exe_opts'][0] == 1){
																if($exe[0] <= $itemInfo['exe_opts'][0]){
																	$exe0Ok = true;
																}
															}
															else{
																$exe0Ok = true;
															}
															if($exe[1] == 1){
																if($exe[1] <= $itemInfo['exe_opts'][1]){
																	$exe1Ok = true;
																}
															}
															else{
																$exe1Ok = true;
															}
															if($exe[2] == 1){
																if($exe[2] <= $itemInfo['exe_opts'][2]){
																	$exe2Ok = true;
																}
															}
															else{
																$exe2Ok = true;
															}
															if($exe[3] == 1){
																if($exe[3] <= $itemInfo['exe_opts'][3]){
																	$exe3Ok = true;
																}
															}
															else{
																$exe3Ok = true;
															}
															
															if($exe[4] == 1){
																if($exe[4] <= $itemInfo['exe_opts'][4]){
																	$exe4Ok = true;
																}
															}
															else{
																$exe4Ok = true;
															}

															if($exe[5] == 1){
																if($exe[5] <= $itemInfo['exe_opts'][5]){
																	$exe5Ok = true;
																}
															}
															else{
																$exe5Ok = true;
															}
														}
													}
													else{
														$exe0Ok = true;
														$exe1Ok = true;
														$exe2Ok = true;
														$exe3Ok = true;
														$exe4Ok = true;
														$exe5Ok = true; 
													}
													
													if($lvlOk == true && $optOk == true && $skillOk == true && $luckOk == true && $exe0Ok == true && $exe1Ok == true && $exe2Ok == true && $exe3Ok == true && $exe4Ok == true && $exe5Ok == true && $itemCount > 0 && !in_array($itemInfo['info']['serial'].$itemInfo['info']['serial2'], array_column($completedItems, 'serial'))){
														$itemCount -= 1;
														$foundItems += 1;
														$logSerials[] = $itemInfo['info']['serial'].$itemInfo['info']['serial2'];
													}
												 }
												}
												if($foundItems >= $amount){
													break;
												}
											}
										}
										if($foundItems >= $amount){
											$this->vars['user_requirement_data'][$this->vars['progress']['pass_level']][$reqId]['completed'] = 1;
											$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->setCompletedRequirement($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['plugin_config']['battle_pass_start_time'], $this->vars['progress']['pass_level'], $reqId, $logSerials);
											$completedCount += 1;
										}
										else{
											$this->vars['user_requirement_data'][$this->vars['progress']['pass_level']][$reqId]['completeProgress'] = $foundItems;
											$percents = ($foundItems * 100) / $amount;
											$this->vars['user_requirement_data'][$this->vars['progress']['pass_level']][$reqId]['progressPerc'] = $percents;
										}
									}
								}
							}
							if($completedCount == $requirementCount){
								$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->setLevelCompleted($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['plugin_config']['battle_pass_start_time'], $this->vars['progress']['pass_level']);
								$this->vars['progress']['is_completed'] == 1;
							}
						}

						if(!empty($this->vars['pass_rewards'])){
							foreach($this->vars['pass_rewards'] AS $rid => $rewards){
								foreach($rewards AS $rKey => $rewardData){
									if(isset($rewardData['display_item_title']) && $rewardData['display_item_title'] == 1){
										$this->vars['pass_rewards'][$rid][$rKey]['item_data'][] = 1;
									}
									else{
										if(!empty($rewardData['items'])){
											foreach($rewardData['items'] AS $ritem){
												if(isset($ritem['hex'])){
													$this->iteminfo->itemData($ritem['hex']);
													if($this->pluginaizer->website->get_value_from_server($this->pluginaizer->session->userdata(['user' => 'server']), 'item_size') == 64){
														$ritem['hex'] = substr_replace($ritem['hex'], sprintf("%08X", 0, 00000000), 6, 8);
														$ritem['hex'] = substr_replace($ritem['hex'], sprintf("%08X", 0, 00000000), 32, 8);
													}
													else{
														$ritem['hex'] = substr_replace($ritem['hex'], sprintf("%08X", 0, 00000000), 6, 8);
													}
													
													$this->vars['pass_rewards'][$rid][$rKey]['item_data'][] = [
														'hex' => $ritem['hex'],
														'name' => $this->pluginaizer->iteminfo->getNameStyle(true),
														'serial' => 00000000,
														'expires' => 0,
														'itemtype' => $this->pluginaizer->iteminfo->itemIndex($this->iteminfo->type, $this->iteminfo->id),
														'item_info' => $this->pluginaizer->itemimage->load($this->pluginaizer->iteminfo->id, $this->pluginaizer->iteminfo->type, (int)substr($this->pluginaizer->iteminfo->getLevel(), 1)) . '<br />' . $this->pluginaizer->iteminfo->allInfo()
													];		
												}
												else{
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
														$serial = 0;
														
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
														$this->vars['pass_rewards'][$rid][$rKey]['item_data'][] = [
															'hex' => $itemHex,
															'name' => $this->pluginaizer->iteminfo->getNameStyle(true),
															'serial' => 00000000,
															'expires' => $ritem['expires'],
															'itemtype' => $this->pluginaizer->iteminfo->itemIndex($ritem['cat'], $ritem['id']),
															'item_info' => $this->pluginaizer->itemimage->load($this->pluginaizer->iteminfo->id, $this->pluginaizer->iteminfo->type, (int)substr($this->pluginaizer->iteminfo->getLevel(), 1)) . '<br />' . $this->pluginaizer->iteminfo->allInfo()
														];
													}
												}
											}
										}
									}
								}
							}
						}
						
						$this->vars['free_wcoins'] = 0;
						$this->vars['silver_wcoins'] = 0;
						$this->vars['platinum_wcoins'] = 0;
						if(!empty($this->vars['pass_levels'])){
							foreach($this->vars['pass_levels'] AS $id => $day){
								if(isset($day['free_pass_wcoins'])){
									$this->vars['free_wcoins'] += (int)$day['free_pass_wcoins'];
								}
								if(isset($day['silver_pass_wcoins'])){
									$this->vars['silver_wcoins'] += (int)$day['silver_pass_wcoins'];
								}
								if(isset($day['platinum_pass_wcoins'])){
									$this->vars['platinum_wcoins'] += (int)$day['platinum_pass_wcoins'];
								}
							}
						}
						
						$this->vars['characters'] = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->load_char_list($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
					}
                } else{
                    $this->vars['config_not_found'] = __('Plugin configuration not found.');
                }
                //set js
                $this->vars['js'] = $this->config->base_url . 'assets/plugins/js/'.$this->pluginaizer->get_plugin_class().'.js';
                //load template
                $this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . $this->config->config_entry('main|template') . DS . 'view.index', $this->vars);
            //} else{
            //    $this->pluginaizer->redirect($this->config->base_url . 'account-panel/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()));
            //}
        }
		
		
		public function claim($id = '', $pass_type = 0){
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
                    } 
					else{
						try{
							$this->load->helper('website');
							$this->load->model('application/plugins/'.$this->pluginaizer->get_plugin_class().'/models/'.$this->pluginaizer->get_plugin_class().'');
							$this->load->model('shop');
							
							$this->vars['pass'] = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->checkPassType($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['plugin_config']['battle_pass_start_time']);
						
							if($this->vars['pass'] == false){
								$this->vars['pass']['pass_type'] = 0;
							}
							
							if($this->vars['pass']['pass_type'] < $pass_type){
								throw new Exception(__('This reward is available after upgrading pass.'));
							}
							
							$this->vars['pass_levels'] = $this->config->values('battle_pass_levels', $this->pluginaizer->session->userdata(['user' => 'server']));
							
							$this->vars['achKey'] = -1;
							if(!empty($this->vars['pass_levels'])){
								foreach($this->vars['pass_levels'] AS $key => $data){
									if($data['id'] == $id){
										$this->vars['achKey'] = $key;
										break;
									}
								}
							}
							else{
								throw new Exception(__('Battle pass level not found.'));
							}
							
							if($this->vars['achKey'] == -1){
								throw new Exception(__('Battle pass level not found.'));
							}
							
							$this->vars['reward_list'] = $this->config->values('battle_pass_rewards', $this->pluginaizer->session->userdata(['user' => 'server']));

							$rewardData = $this->vars['reward_list'][$id];
							
							if(!isset($rewardData) || empty($rewardData)){
								throw new Exception(__('Rewards not found.'));
							}
							
							$this->vars['level_status'] = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->checkLevelStatus($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $this->vars['plugin_config']['battle_pass_start_time'], $id);
							if($this->vars['level_status'] == false){
								$this->vars['level_status']['is_completed'] = 0;
								$this->vars['level_status']['is_free_reward_taken'] = 0;
								$this->vars['level_status']['is_silver_reward_taken'] = 0;
								$this->vars['level_status']['is_platinum_reward_taken'] = 0;
							}
							
							if($this->vars['level_status']['is_completed'] == 0){
								throw new Exception(__('Please complete this day free pass.'));
							}
							
							if($pass_type == 0 && $this->vars['level_status']['is_free_reward_taken'] == 1){
								throw new Exception(__('Reward already claimed'));
							}
							
							if($pass_type == 1 && $this->vars['level_status']['is_silver_reward_taken'] == 1){
								throw new Exception(__('Reward already claimed'));
							}
							
							if($pass_type == 2 && $this->vars['level_status']['is_platinum_reward_taken'] == 1){
								throw new Exception(__('Reward already claimed'));
							}
							
							$this->vars['character'] = isset($_POST['character']) ? $_POST['character'] : '';
							
							$this->vars['character_data'] = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->check_char($this->vars['character'], $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), true);
							
							if($this->vars['character_data'] == false){
								throw new Exception(__('Invalid character.'));
							}
							
							$this->load->lib('iteminfo');
							$this->load->lib('createitem', [MU_VERSION, SOCKET_LIBRARY, $this->pluginaizer->website->get_value_from_server($this->pluginaizer->session->userdata(['user' => 'server']), 'item_size')]);
							
							
							foreach($rewardData AS $rid => $rData){
								$this->vars['reward_items'] = [];
								$this->vars['reward_buffs'] = [];
								if($rData['pass_type'] == $pass_type){
									if(in_array($rData['reward_type'], [3,4,5,7,9,10,11])){
										if(!$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->check_connect_stat($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']))){
											throw new Exception(__('Please logout from game.'));
										}
									}
									if($rData['reward_type'] == 3){
										$this->vars['table_config'] = $this->pluginaizer->config->values('table_config', $this->pluginaizer->session->userdata(['user' => 'server']));
										if(!isset($this->vars['table_config']['wcoins']))
											throw new Exception(__('WCoins configuration not found'));
										if($this->vars['table_config']['wcoins']['table'] == '')
											throw new Exception(__('WCoins configuration not found'));
									}
									if($rData['reward_type'] == 4){
										if(!isset($this->vars['table_config'])){
											$this->vars['table_config'] = $this->pluginaizer->config->values('table_config', $this->pluginaizer->session->userdata(['user' => 'server']));
										}
										if(!isset($this->vars['table_config']['goblinpoint']))
											throw new Exception(__('GoblinPoint configuration not found'));
										if($this->vars['table_config']['goblinpoint']['table'] == '')
											throw new Exception(__('GoblinPoint configuration not found'));
									}
									if($rData['reward_type'] == 5){
										$zen = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->checkZen($this->vars['character'], $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
										if(($zen['Money'] + $rData['amount']) > 2000000000){
											throw new Exception('Zen limit reached on character.');
										}
									}
									if($rData['reward_type'] == 7){
										$ruud = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->checkRuud($this->vars['character'], $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
										if(($ruud['Ruud'] + $rData['amount']) > 2000000000){
											throw new Exception('Ruud limit reached on character.');
										}
									}
									if($rData['reward_type'] == 9 || $rData['reward_type'] == 10){
										if(!empty($rData['items'])){
											foreach($rData['items'] AS $ritem){
												if(!isset($ritem['hex'])){
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
																	
																	foreach($iexe AS $ek => $eval){
																		if($eval == 0){
																			unset($iexe[$ek]);
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
														$this->vars['reward_items'][] = [
															'hex' => $itemHex,
															'name' => $this->pluginaizer->iteminfo->getNameStyle(true),
															'serial' => $serial,
															'expires' => $ritem['expires'],
															'itemtype' => $this->pluginaizer->iteminfo->itemIndex($ritem['cat'], $ritem['id'])
														];
													}
												}
												if(isset($ritem['hex'])){
													$this->iteminfo->itemData($ritem['hex']);
													$serial = $this->pluginaizer->Mshop->generate_serial($this->pluginaizer->session->userdata(['user' => 'server']));
													if($this->pluginaizer->website->get_value_from_server($this->pluginaizer->session->userdata(['user' => 'server']), 'item_size') == 64){
														$ritem['hex'] = substr_replace($ritem['hex'], sprintf("%08X", 0, 00000000), 6, 8);
														$ritem['hex'] = substr_replace($ritem['hex'], sprintf("%08X", $serial, 00000000), 32, 8);
													}
													else{
														$ritem['hex'] = substr_replace($ritem['hex'], sprintf("%08X", $serial, 00000000), 6, 8);
													}
													
													$this->vars['reward_items'][] = [
														'hex' => $ritem['hex'],
														'name' => $this->pluginaizer->iteminfo->getNameStyle(true),
														'serial' => $serial,
														'expires' => '',
														'itemtype' => ''
													];
												}
											}

											if(!empty($this->vars['reward_items'])){
												$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->inventory2($this->vars['character'], $this->pluginaizer->session->userdata(['user' => 'server']));
												$items = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->getInventoryContents($this->pluginaizer->session->userdata(['user' => 'server']));
												$itemsList = implode('', $items);
												$itemInfo = $this->pluginaizer->iteminfo;
												$itemArr = str_split($itemsList, $this->pluginaizer->website->get_value_from_server($this->pluginaizer->session->userdata(['user' => 'server']), 'item_size'));
												$takenSlots = [];
												$expirableItems = [];
												
												foreach($this->vars['reward_items'] AS $ritems){
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
												}
											}
										}
									}
									if($rData['reward_type'] == 11){
										if(!empty($rData['buffs'])){
											static $ItemOptionManager = null;
											
											if($ItemOptionManager == null){
												$ItemOptionManager = new \DOMDocument;
												$ItemOptionManager->load(APP_PATH . DS . 'data' . DS . 'ServerData' . DS . 'ItemOptionManager.xml');
											}
											
											$xpath = new DOMXPath($ItemOptionManager);
											$node = $xpath->query("//ItemOptionManager/Section/Item");
											
											foreach($rData['buffs'] AS $buffs){
												if($node->length > 0){
													$effectType = 0;
													$effect1 = 0;
													$effect2 = 0;
													foreach($node AS $s => $v){
														if($v->getAttribute('Index') == $buffs['id'] && $v->getAttribute('Cat') == $buffs['cat']){
															$effectType = $v->parentNode->getAttribute('ID');
															$effect1 = $v->getAttribute('Option1');
															$effect2 = $v->getAttribute('Option2');
															break;
														}
													}
													$serial = $this->pluginaizer->Mshop->generate_serial($this->pluginaizer->session->userdata(['user' => 'server']));
														
													$this->vars['reward_buffs'][] = [
														'serial' => $serial,
														'expires' => $buffs['expires'],
														'itemtype' => $this->pluginaizer->iteminfo->itemIndex($buffs['cat'], $buffs['id']),
														'effect_type' => $effectType,
														'effect1' => $effect1,
														'effect2' => $effect2
													];
												}
												else{
													throw new Exception('Unable to find option data in ItemOptionManager.xml for item '.$buffs['cat'].'-'.$buffs['id']);
												}
											}
										}
									}

									if($rData['reward_type'] == 1){
										$this->pluginaizer->website->add_credits($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $rData['amount'], 1, false, $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->get_guid($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server'])));	
									}
									if($rData['reward_type'] == 2){
										$this->pluginaizer->website->add_credits($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $rData['amount'], 2, false, $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->get_guid($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server'])));	
									}
									if($rData['reward_type'] == 3){
										$acc = (in_array($this->vars['table_config']['wcoins']['identifier_column'], ['MemberGuid', 'memb_guid'])) ? $this->pluginaizer->session->userdata(['user' => 'id']) : $this->pluginaizer->session->userdata(['user' => 'username']);
										if($pass_type == 0 && isset($this->vars['pass_levels'][$this->vars['achKey']][$id]['free_pass_wcoins'])){
											$rData['amount'] += $this->vars['pass_levels'][$this->vars['achKey']][$id]['free_pass_wcoins'];
										}
										if($pass_type == 1 && isset($this->vars['pass_levels'][$this->vars['achKey']][$id]['silver_pass_wcoins'])){
											$rData['amount'] += $this->vars['pass_levels'][$this->vars['achKey']][$id]['silver_pass_wcoins'];
										}
										if($pass_type == 3 && isset($this->vars['pass_levels'][$this->vars['achKey']][$id]['platinum_pass_wcoins'])){
											$rData['amount'] += $this->vars['pass_levels'][$this->vars['achKey']][$id]['platinum_pass_wcoins'];
										}
										$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->add_wcoins($acc, $this->pluginaizer->session->userdata(['user' => 'server']), $rData['amount'], $this->vars['table_config']['wcoins']);
							
									}
									if($rData['reward_type'] == 4){
										$acc = (in_array($this->vars['table_config']['goblinpoint']['identifier_column'], ['MemberGuid', 'memb_guid'])) ? $this->pluginaizer->session->userdata(['user' => 'id']) : $this->pluginaizer->session->userdata(['user' => 'username']);
										$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->add_wcoins($acc, $this->pluginaizer->session->userdata(['user' => 'server']), $rData['amount'], $this->vars['table_config']['goblinpoint']);					
									}
									if($rData['reward_type'] == 5){
										$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->add_zen($rData['amount'], $this->vars['character'], $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));							
									}
									if($rData['reward_type'] == 6){
										$this->pluginaizer->website->add_credits($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $rData['amount'], 3, false, $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->get_guid($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server'])));						
									}
									if($rData['reward_type'] == 7){
										$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->add_ruud($rData['amount'], $this->vars['character'], $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));						
									}
									if($rData['reward_type'] == 8){
										$vip_config = $this->pluginaizer->config->values('vip_config');
										$vip_query_config = $this->pluginaizer->config->values('vip_query_config');
										$table_config = $this->pluginaizer->config->values('table_config', $this->pluginaizer->session->userdata(['user' => 'server']));
										$this->load->model('account');
										
										$this->vars['vip_data'] = $this->pluginaizer->Mshop->check_vip($rData['vip_type'], $this->pluginaizer->session->userdata(['user' => 'server']));
										$viptime = time() + $this->vars['vip_data']['vip_time'];
										if($this->vars['existing'] = $this->pluginaizer->Mshop->check_existing_vip_package($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']))){
											if($this->vars['existing']['viptime'] > time()){
												$viptime = $this->vars['existing']['viptime'] + $this->vars['vip_data']['vip_time'];
											}
											$this->pluginaizer->Mshop->update_vip_package($rData['vip_type'], $viptime, $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
											$this->pluginaizer->Mshop->add_server_vip($viptime, $this->vars['vip_data']['server_vip_package'], $this->vars['vip_data']['connect_member_load'], $vip_query_config, $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
											$this->pluginaizer->Maccount->set_vip_session($viptime, $this->vars['vip_data']);	
										}
										else{
											$this->pluginaizer->Mshop->insert_vip_package($rData['vip_type'], $viptime, $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
											$this->pluginaizer->Mshop->add_server_vip($viptime, $this->vars['vip_data']['server_vip_package'], $this->vars['vip_data']['connect_member_load'], $vip_query_config, $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
											$this->pluginaizer->Maccount->set_vip_session($viptime, $this->vars['vip_data']);	
										}	
									}
									if($rData['reward_type'] == 9 || $rData['reward_type'] == 10){
										$newInv = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->addItemsToInventory($itemArr, $this->pluginaizer->session->userdata(['user' => 'server']));
										if(!empty($expirableItems)){
											$currTime = time();
											foreach($expirableItems AS $expideData){
												$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->addExpirableItem($this->pluginaizer->session->userdata(['user' => 'id']), $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['character_data']['Name'], $expideData['index'], $expideData['time'], $expideData['serial'], $currTime);
											}
										}
										$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->updateInventory($this->vars['character'], $this->pluginaizer->session->userdata(['user' => 'server']), $newInv);
									}
									if($rData['reward_type'] == 11){
										if(!empty($this->vars['reward_buffs'])){
											$currTime = time();
											foreach($this->vars['reward_buffs'] AS $expireData){
												$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->addExpirableItem($this->pluginaizer->session->userdata(['user' => 'id']), $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['character_data']['Name'], $expireData['itemtype'], $expireData['expires'], $expireData['serial'], $currTime, $expireData['effect_type'], $expireData['effect1'], $expireData['effect2'], 1);
											}
										}
									}
									if($rData['reward_type'] == 12){
										$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->addFreeSpin($rData['amount'], $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));						
									}
								}
							}
							$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->setRewardCompleted($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['plugin_config']['battle_pass_start_time'], $id, $pass_type);
							$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->log_reward($id, $pass_type, $this->vars['character'], $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
							$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->add_account_log('Claimed battle pass reward.', 0, $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
							echo $this->pluginaizer->jsone(['success' => __('Reward successfully claimed')]);															
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
		
		 
		public function pass_levels(){
			if($this->pluginaizer->session->is_admin()){
				$this->vars['server'] = isset($_GET['server']) ? $_GET['server'] : '';
				if($this->vars['server'] != ''){
					$this->load->helper('website');
					$this->load->model('application/plugins/'.$this->pluginaizer->get_plugin_class().'/models/'.$this->pluginaizer->get_plugin_class());
					$this->vars['js'] = $this->config->base_url . 'assets/plugins/js/'.$this->pluginaizer->get_plugin_class().'.js';
					$this->vars['is_multi_server'] = $this->pluginaizer->data()->value('is_multi_server');
					
					if(isset($_POST['add_level'])){
						$title = isset($_POST['title']) ? $_POST['title'] : '';
						$free_image = isset($_POST['free_pass_image']) ? $_POST['free_pass_image'] : '';
						$free_wcoins = isset($_POST['free_pass_wcoins']) ? $_POST['free_pass_wcoins'] : '';
						$silver_image = isset($_POST['silver_pass_image']) ? $_POST['silver_pass_image'] : '';
						$silver_wcoins = isset($_POST['silver_pass_wcoins']) ? $_POST['silver_pass_wcoins'] : '';
						$platinum_image = isset($_POST['platinum_pass_image']) ? $_POST['platinum_pass_image'] : '';
						$platinum_wcoins = isset($_POST['platinum_pass_wcoins']) ? $_POST['platinum_pass_wcoins'] : '';

						if($title == ''){
							$this->vars['error'] = 'Please enter title.';
						}
						
						$this->vars['battle_pass_levels'] = $this->config->values('battle_pass_levels');
						if(array_key_exists($this->vars['server'], $this->vars['battle_pass_levels'])){
							if(empty($this->vars['battle_pass_levels'][$this->vars['server']])){
								$this->vars['battle_pass_levels'][$this->vars['server']] = [
									1 => [
										'id' => uniqid(),
										'title' => $title,
										'free_pass_image' => $free_image,
										'free_pass_wcoins' => $free_wcoins,
										'silver_pass_image' => $silver_image,
										'silver_pass_wcoins' => $silver_wcoins,
										'platinum_pass_image' => $platinum_image,
										'platinum_pass_wcoins' => $platinum_wcoins
									]
								];
							}
							else{
								$this->vars['battle_pass_levels'][$this->vars['server']][] = [
									'id' => uniqid(),
									'title' => $title,
									'free_pass_image' => $free_image,
									'free_pass_wcoins' => $free_wcoins,
									'silver_pass_image' => $silver_image,
									'silver_pass_wcoins' => $silver_wcoins,
									'platinum_pass_image' => $platinum_image,
									'platinum_pass_wcoins' => $platinum_wcoins
								];
							}
							$this->config->save_config_data($this->vars['battle_pass_levels'], 'battle_pass_levels');
						}
						else{
							$this->vars['new_config'] = [
								$this->vars['server'] => [
									'1' => [
										'id' => uniqid(),
										'title' => $title,
										'free_pass_image' => $free_image,
										'free_pass_wcoins' => $free_wcoins,
										'silver_pass_image' => $silver_image,
										'silver_pass_wcoins' => $silver_wcoins,
										'platinum_pass_image' => $platinum_image,
										'platinum_pass_wcoins' => $platinum_wcoins
									]
								]
							];
							$this->vars['battle_pass_levels'] = array_merge($this->vars['battle_pass_levels'], $this->vars['new_config']);
                            $this->config->save_config_data($this->vars['battle_pass_levels'], 'battle_pass_levels');
						}
						 $this->vars['success'] = 'Level successfully added.';
					}
					
					$this->vars['battle_pass_levels'] = $this->config->values('battle_pass_levels', $this->vars['server']);
					$this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . 'admin' . DS . 'view.pass_levels', $this->vars);
				}
				else{
					$this->vars['error'] = __('Invalid server.');
				}
			} else{
                $this->pluginaizer->redirect($this->config->base_url . 'admincp/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()) . '/admin');
            }
		}
		
		 
		public function edit_level($id, $server){
			if($this->pluginaizer->session->is_admin()){
				$this->vars['server'] = $server;
				if($this->vars['server'] != ''){
					$this->load->helper('website');
					$this->load->model('application/plugins/'.$this->pluginaizer->get_plugin_class().'/models/'.$this->pluginaizer->get_plugin_class());
					$this->vars['js'] = $this->config->base_url . 'assets/plugins/js/'.$this->pluginaizer->get_plugin_class().'.js';
					$this->vars['is_multi_server'] = $this->pluginaizer->data()->value('is_multi_server');
					$this->vars['battle_pass'] = $this->config->values('battle_pass_levels', $this->vars['server']);
					$this->vars['achKey'] = -1;
					if(!empty($this->vars['battle_pass'])){
						foreach($this->vars['battle_pass'] AS $key => $data){
							if($data['id'] == $id){
								$this->vars['achKey'] = $key;
								break;
							}
						}
					}
					else{
						$this->vars['not_found'] = __('Level not found.');
					}
					
					if($this->vars['achKey'] == -1){
						$this->vars['not_found'] = __('Level not found.');
					}
					else{
						$this->vars['achData'] = $this->vars['battle_pass'][$this->vars['achKey']];
					}
					
					if(isset($_POST['edit_level'])){
						$title = isset($_POST['title']) ? $_POST['title'] : '';
						$free_image = isset($_POST['free_pass_image']) ? $_POST['free_pass_image'] : '';
						$free_wcoins = isset($_POST['free_pass_wcoins']) ? $_POST['free_pass_wcoins'] : '';
						$silver_image = isset($_POST['silver_pass_image']) ? $_POST['silver_pass_image'] : '';
						$silver_wcoins = isset($_POST['silver_pass_wcoins']) ? $_POST['silver_pass_wcoins'] : '';
						$platinum_image = isset($_POST['platinum_pass_image']) ? $_POST['platinum_pass_image'] : '';
						$platinum_wcoins = isset($_POST['platinum_pass_wcoins']) ? $_POST['platinum_pass_wcoins'] : '';
						
						if($title == ''){
							$this->vars['error'] = 'Please enter title.';
						}
						
						$this->vars['battle_pass_levels'] = $this->config->values('battle_pass_levels');
						$this->vars['battle_pass_levels'][$this->vars['server']][$this->vars['achKey']] = [
							'id' => $id,
							'title' => $title,
							'free_pass_image' => $free_image,
							'free_pass_wcoins' => $free_wcoins,
							'silver_pass_image' => $silver_image,
							'silver_pass_wcoins' => $silver_wcoins,
							'platinum_pass_image' => $platinum_image,
							'platinum_pass_wcoins' => $platinum_wcoins
						];
						$this->config->save_config_data($this->vars['battle_pass_levels'], 'battle_pass_levels');
						$this->vars['success'] = 'Level successfully updated.';
						$this->vars['achData'] = $this->vars['battle_pass_levels'][$this->vars['server']][$this->vars['achKey']];
					}
					
					$this->vars['battle_pass_levels'] = $this->config->values('battle_pass_levels', $this->vars['server']);
					$this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . 'admin' . DS . 'view.edit_level', $this->vars);
				}
				else{
					$this->vars['error'] = __('Invalid server.');
				}
			} else{
                $this->pluginaizer->redirect($this->config->base_url . 'admincp/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()) . '/admin');
            }
		}
		
		 
		public function save_order($server){
            //check if visitor has administrator privilleges
            if($this->pluginaizer->session->is_admin()){
                $this->vars['battle_pass_levels'] = $this->config->values('battle_pass_levels');
				$this->vars['new'] = [];	
				foreach($_POST['order'] AS $value){
                    if(array_key_exists($value, $this->vars['battle_pass_levels'][$server])){
                        $this->vars['new'][$server][$value] = $this->vars['battle_pass_levels'][$server][$value];
                    }
                }
				
				if(!empty($this->vars['new'])){
					$this->vars['battle_pass_levels'][$server] = $this->vars['new'][$server];
					$this->config->save_config_data($this->vars['battle_pass_levels'], 'battle_pass_levels');
				}
            } 
			else{
                echo $this->pluginaizer->jsone(['error' => 'Please login first!']);
            }
        }
		
		public function delete_level($id, $server){
			if($this->pluginaizer->session->is_admin()){
				$this->vars['server'] = $server;
				if($this->vars['server'] != ''){
					$this->load->helper('website');
					$this->load->model('application/plugins/'.$this->pluginaizer->get_plugin_class().'/models/'.$this->pluginaizer->get_plugin_class());
					$this->vars['js'] = $this->config->base_url . 'assets/plugins/js/'.$this->pluginaizer->get_plugin_class().'.js';
					$this->vars['is_multi_server'] = $this->pluginaizer->data()->value('is_multi_server');
					$this->vars['battle_pass'] = $this->config->values('battle_pass_levels');
					$this->vars['req_list'] = $this->config->values('battle_pass_req');
					$this->vars['reward_list'] = $this->config->values('battle_pass_rewards');
					$this->vars['achKey'] = -1;
					if(!empty($this->vars['battle_pass'][$this->vars['server']])){
						foreach($this->vars['battle_pass'][$this->vars['server']] AS $key => $data){
							if($data['id'] == $id){
								$this->vars['achKey'] = $key;
								break;
							}
						}
					}
					else{
						$this->vars['not_found'] = __('Level not found.');
					}
					
					if($this->vars['achKey'] == -1){
						$this->vars['not_found'] = __('Level not found.');
					}
					else{
						unset($this->vars['battle_pass'][$this->vars['server']][$this->vars['achKey']]);
						if(isset($this->vars['req_list'][$this->vars['server']][$id])){
							unset($this->vars['req_list'][$this->vars['server']][$id]);
						}
						if(isset($this->vars['req_list'][$this->vars['server']][$id])){
							unset($this->vars['req_list'][$this->vars['server']][$id]);
						}
						if(isset($this->vars['reward_list'][$this->vars['server']][$id])){
							unset($this->vars['reward_list'][$this->vars['server']][$id]);
						}
					}
					
					$this->config->save_config_data($this->vars['battle_pass'], 'battle_pass_levels');
					$this->config->save_config_data($this->vars['req_list'], 'battle_pass_req');
					$this->config->save_config_data($this->vars['reward_list'], 'battle_pass_rewards');
					$this->vars['success'] = 'Level successfully removed.';

					$this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . 'admin' . DS . 'view.delete_level', $this->vars);
				}
				else{
					$this->vars['error'] = __('Invalid server.');
				}
			} else{
                $this->pluginaizer->redirect($this->config->base_url . 'admincp/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()) . '/admin');
            }
		}
		
		 
		public function requirements($id, $server){
			if($this->pluginaizer->session->is_admin()){
				$this->vars['server'] = $server;
				if($this->vars['server'] != ''){
					$this->load->helper('website');
					$this->load->helper('webshop');
					$this->load->lib('npc');
					$this->load->model('admin');
					$this->load->model('application/plugins/'.$this->pluginaizer->get_plugin_class().'/models/'.$this->pluginaizer->get_plugin_class());
					$this->vars['js'] = $this->config->base_url . 'assets/plugins/js/'.$this->pluginaizer->get_plugin_class().'.js';
					$this->vars['is_multi_server'] = $this->pluginaizer->data()->value('is_multi_server');
					$this->vars['monster_list'] = $this->pluginaizer->npc->get_list();
					$this->vars['class_list'] = $this->pluginaizer->website->get_char_class(0, false, true);
					$this->vars['battle_pass'] = $this->config->values('battle_pass_levels');
					$this->vars['aid'] = $id;
					$this->vars['achKey'] = -1;
					if(!empty($this->vars['battle_pass'][$this->vars['server']])){
						foreach($this->vars['battle_pass'][$this->vars['server']] AS $key => $data){
							if($data['id'] == $id){
								$this->vars['achKey'] = $key;
								break;
							}
						}
					}
					else{
						$this->vars['not_found'] = __('Battle Pass level not found.');
					}
					

					if($this->vars['achKey'] == -1){
						$this->vars['not_found'] = __('Battle Pass level not found.');
					}
					
					$this->vars['req_list'] = $this->config->values('battle_pass_req');
					
					$this->vars['achData'] = [];
					
					if(isset($this->vars['req_list'][$this->vars['server']][$id])){
						$this->vars['achData'] = $this->vars['req_list'][$this->vars['server']][$id];
					}
					
					if(isset($_POST['add_req'])){
						$title = isset($_POST['title']) ? $_POST['title'] : '';
						$achievement_type = isset($_POST['req_type']) ? $_POST['req_type'] : '';
						$total_stats = isset($_POST['total_stats']) ? $_POST['total_stats'] : 0;
						$total_items_buy = isset($_POST['total_items_buy']) ? $_POST['total_items_buy'] : 0;
						$total_items_sell = isset($_POST['total_items_sell']) ? $_POST['total_items_sell'] : 0;
						$total_kills = isset($_POST['total_kills']) ? $_POST['total_kills'] : 0;
						$unique = isset($_POST['unique']) ? $_POST['unique'] : 0;
						$total_votes = isset($_POST['total_votes']) ? $_POST['total_votes'] : 0;
						$total_votes2 = isset($_POST['total_votes2']) ? $_POST['total_votes2'] : 0;
						$vote_type = isset($_POST['vote_type']) ? $_POST['vote_type'] : 0;
						$enter_count = isset($_POST['enter_count']) ? $_POST['enter_count'] : 0;
						$event_type = isset($_POST['event_type']) ? $_POST['event_type'] : 0;
						
						$total_donate = isset($_POST['total_donate']) ? $_POST['total_donate'] : 0;
						$monsters = isset($_POST['monsters']) ? $_POST['monsters'] : '';
						$mamount = isset($_POST['mamount']) ? $_POST['mamount'] : 0;
						$items = [];
						if($_POST['item_count'] && $_POST['item_count'] > 0){
							$items[] = [
								'amount' => $_POST['item_count'],
								'cat' => $_POST['item_category'],
								'id' => $_POST['item_index'],
								'lvl' => $_POST['item_level'],
								'skill' => $_POST['item_skill'],
								'luck' => $_POST['item_luck'],
								'opt' => $_POST['item_option'],
								'exe' => $_POST['item_excellent'],
								'anc' => $_POST['item_ancient'],
							];
						}
						if($title == ''){
							$this->vars['error'] = 'Please enter title.';
						}
						else{
							if($achievement_type == ''){
								$this->vars['error'] = 'Please select requirement type.';
							}
						}
						
						if($achievement_type == 11){
							$total_votes = $total_votes2;
						}

						if(!isset($this->vars['error'])){
							$this->vars['req_list'][$this->vars['server']][$id][uniqid()] = [
								'title' => $title,
								'req_type' => $achievement_type,
								'total_stats' => $total_stats,
								'total_items_buy' => $total_items_buy,
								'total_items_sell' => $total_items_sell,
								'total_kills' => $total_kills,
								'unique' => $unique,
								'total_votes' => $total_votes,
								'vote_type' => $vote_type,
								'enter_count' => $enter_count,
								'event_type' => $event_type,
								'total_donate' => $total_donate,
								'monsters' => $monsters,
								'mamount' => $mamount,
								'items' => $items
							];
							
							$this->config->save_config_data($this->vars['req_list'], 'battle_pass_req');
							$this->vars['success'] = 'Requirement successfully added.';
							$this->vars['achData'] = $this->vars['req_list'][$this->vars['server']][$id];	
						}
					}

					$this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . 'admin' . DS . 'view.req', $this->vars);
				}
				else{
					$this->vars['error'] = __('Invalid server.');
				}
			} else{
                $this->pluginaizer->redirect($this->config->base_url . 'admincp/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()) . '/admin');
            }
		}
		
		 
		public function edit_requirement($id, $rid, $server){
			if($this->pluginaizer->session->is_admin()){
				$this->vars['server'] = $server;
				if($this->vars['server'] != ''){
					$this->load->helper('website');
					$this->load->helper('webshop');
					$this->load->lib('npc');
					$this->load->model('admin');
					$this->load->model('application/plugins/'.$this->pluginaizer->get_plugin_class().'/models/'.$this->pluginaizer->get_plugin_class());
					$this->vars['js'] = $this->config->base_url . 'assets/plugins/js/'.$this->pluginaizer->get_plugin_class().'.js';
					$this->vars['is_multi_server'] = $this->pluginaizer->data()->value('is_multi_server');
					$this->vars['monster_list'] = $this->pluginaizer->npc->get_list();
					$this->vars['class_list'] = $this->pluginaizer->website->get_char_class(0, false, true);
					$this->vars['battle_pass'] = $this->config->values('battle_pass_levels');
					$this->vars['aid'] = $id;
					$this->vars['achKey'] = -1;
					if(!empty($this->vars['battle_pass'][$this->vars['server']])){
						foreach($this->vars['battle_pass'][$this->vars['server']] AS $key => $data){
							if($data['id'] == $id){
								$this->vars['achKey'] = $key;
								break;
							}
						}
					}
					else{
						$this->vars['not_found'] = __('Battle Pass level not found.');
					}
					

					if($this->vars['achKey'] == -1){
						$this->vars['not_found'] = __('Battle Pass level not found.');
					}
					
					$this->vars['req_list'] = $this->config->values('battle_pass_req');
					
					$this->vars['achData'] = [];
					
					if(isset($this->vars['req_list'][$this->vars['server']][$id][$rid])){
						$this->vars['achData'] = $this->vars['req_list'][$this->vars['server']][$id][$rid];
					}
					
					if(isset($_POST['edit_req'])){
						$title = isset($_POST['title']) ? $_POST['title'] : '';
						$achievement_type = isset($_POST['req_type']) ? $_POST['req_type'] : '';
						$total_stats = isset($_POST['total_stats']) ? $_POST['total_stats'] : 0;
						$total_items_buy = isset($_POST['total_items_buy']) ? $_POST['total_items_buy'] : 0;
						$total_items_sell = isset($_POST['total_items_sell']) ? $_POST['total_items_sell'] : 0;
						$total_kills = isset($_POST['total_kills']) ? $_POST['total_kills'] : 0;
						$unique = isset($_POST['unique']) ? $_POST['unique'] : 0;
						$total_votes = isset($_POST['total_votes']) ? $_POST['total_votes'] : 0;
						$total_votes2 = isset($_POST['total_votes2']) ? $_POST['total_votes2'] : 0;
						$vote_type = isset($_POST['vote_type']) ? $_POST['vote_type'] : 0;
						$enter_count = isset($_POST['enter_count']) ? $_POST['enter_count'] : 0;
						$event_type = isset($_POST['event_type']) ? $_POST['event_type'] : 0;
						$total_donate = isset($_POST['total_donate']) ? $_POST['total_donate'] : 0;
						$monsters = isset($_POST['monsters']) ? $_POST['monsters'] : '';
						$mamount = isset($_POST['mamount']) ? $_POST['mamount'] : 0;
						$items = [];
						if(isset($_POST['item_count']) && $_POST['item_count'] > 0){
							$items[] = [
								'amount' => $_POST['item_count'],
								'cat' => $_POST['item_category'],
								'id' => $_POST['item_index'],
								'lvl' => $_POST['item_level'],
								'skill' => $_POST['item_skill'],
								'luck' => $_POST['item_luck'],
								'opt' => $_POST['item_option'],
								'exe' => $_POST['item_excellent'],
								'anc' => $_POST['item_ancient'],
							];
						}
						if($title == ''){
							$this->vars['error'] = 'Please enter title.';
						}
						else{
							if($achievement_type == ''){
								$this->vars['error'] = 'Please select requirement type.';
							}
						}
						
						if($achievement_type == 11){
							$total_votes = $total_votes2;
						}
						
						if(!isset($this->vars['error'])){
							$this->vars['req_list'][$this->vars['server']][$id][$rid] = [
								'title' => $title,
								'req_type' => $achievement_type,
								'total_stats' => $total_stats,
								'total_items_buy' => $total_items_buy,
								'total_items_sell' => $total_items_sell,
								'total_kills' => $total_kills,
								'unique' => $unique,
								'total_votes' => $total_votes,
								'vote_type' => $vote_type,
								'enter_count' => $enter_count,
								'event_type' => $event_type,
								'total_donate' => $total_donate,
								'monsters' => $monsters,
								'mamount' => $mamount,
								'items' => $items
							];
							
							$this->config->save_config_data($this->vars['req_list'], 'battle_pass_req');
							$this->vars['success'] = 'Requirement successfully added.';
							$this->vars['achData'] = $this->vars['req_list'][$this->vars['server']][$id][$rid];	
						}
					}

					$this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . 'admin' . DS . 'view.edit_req', $this->vars);
				}
				else{
					$this->vars['error'] = __('Invalid server.');
				}
			} else{
                $this->pluginaizer->redirect($this->config->base_url . 'admincp/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()) . '/admin');
            }
		}
		
		 
		public function delete_requirement($id, $rid, $server){
			if($this->pluginaizer->session->is_admin()){
				$this->vars['server'] = $server;
				if($this->vars['server'] != ''){
					$this->load->helper('website');
					$this->load->helper('webshop');
					$this->load->lib('npc');
					$this->load->model('admin');
					$this->load->model('application/plugins/'.$this->pluginaizer->get_plugin_class().'/models/'.$this->pluginaizer->get_plugin_class());
					$this->vars['js'] = $this->config->base_url . 'assets/plugins/js/'.$this->pluginaizer->get_plugin_class().'.js';
					$this->vars['is_multi_server'] = $this->pluginaizer->data()->value('is_multi_server');
					$this->vars['monster_list'] = $this->pluginaizer->npc->get_list();
					$this->vars['class_list'] = $this->pluginaizer->website->get_char_class(0, false, true);
					$this->vars['battle_pass'] = $this->config->values('battle_pass_levels');
					$this->vars['aid'] = $id;
					$this->vars['achKey'] = -1;
					if(!empty($this->vars['battle_pass'][$this->vars['server']])){
						foreach($this->vars['battle_pass'][$this->vars['server']] AS $key => $data){
							if($data['id'] == $id){
								$this->vars['achKey'] = $key;
								break;
							}
						}
					}
					else{
						$this->vars['not_found'] = __('Battle Pass level not found.');
					}
					

					if($this->vars['achKey'] == -1){
						$this->vars['not_found'] = __('Battle Pass level not found.');
					}
					
					$this->vars['req_list'] = $this->config->values('battle_pass_req');
					
					$this->vars['achData'] = [];
					
					if(isset($this->vars['req_list'][$this->vars['server']][$id][$rid])){
						unset($this->vars['req_list'][$this->vars['server']][$id][$rid]);
						$this->config->save_config_data($this->vars['req_list'], 'battle_pass_req');
						$this->vars['success'] = 'Requirement successfully removed.';
					}
					else{
						$this->vars['error'] = 'Requirement not found';
					}
					
					$this->vars['achData'] = $this->vars['req_list'][$this->vars['server']][$id];

					$this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . 'admin' . DS . 'view.req', $this->vars);
				}
				else{
					$this->vars['error'] = __('Invalid server.');
				}
			} else{
                $this->pluginaizer->redirect($this->config->base_url . 'admincp/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()) . '/admin');
            }
		}
		
		 
		public function rewards($id, $server){
			if($this->pluginaizer->session->is_admin()){
				$this->vars['server'] = $server;
				if($this->vars['server'] != ''){
					$this->load->helper('website');
					$this->load->helper('webshop');
					$this->load->lib('npc');
					$this->load->model('admin');
					$this->load->model('application/plugins/'.$this->pluginaizer->get_plugin_class().'/models/'.$this->pluginaizer->get_plugin_class());
					$this->vars['js'] = $this->config->base_url . 'assets/plugins/js/'.$this->pluginaizer->get_plugin_class().'.js';
					$this->vars['is_multi_server'] = $this->pluginaizer->data()->value('is_multi_server');
					$this->vars['monster_list'] = $this->pluginaizer->npc->get_list();
					$this->vars['class_list'] = $this->pluginaizer->website->get_char_class(0, false, true);
					$this->vars['battle_pass'] = $this->config->values('battle_pass_levels');
					$this->vars['aid'] = $id;
					$this->vars['achKey'] = -1;
					if(!empty($this->vars['battle_pass'][$this->vars['server']])){
						foreach($this->vars['battle_pass'][$this->vars['server']] AS $key => $data){
							if($data['id'] == $id){
								$this->vars['achKey'] = $key;
								break;
							}
						}
					}
					else{
						$this->vars['not_found'] = __('Battle Pass level not found.');
					}
					

					if($this->vars['achKey'] == -1){
						$this->vars['not_found'] = __('Battle Pass level not found.');
					}
					
					$this->vars['reward_list'] = $this->config->values('battle_pass_rewards');
					
					$this->vars['achData'] = [];
					
					if(isset($this->vars['reward_list'][$this->vars['server']][$id])){
						$this->vars['achData'] = $this->vars['reward_list'][$this->vars['server']][$id];
					}
					
					if(isset($_POST['add_reward'])){
						$title = isset($_POST['title']) ? $_POST['title'] : '';
						$pass_type = isset($_POST['pass_type']) ? $_POST['pass_type'] : '';
						$reward_type = isset($_POST['reward_type']) ? $_POST['reward_type'] : '';
						$amount = isset($_POST['amount']) ? $_POST['amount'] : 0;
						$vip_type = isset($_POST['vip_type']) ? $_POST['vip_type'] : '';
						$display_item_title = isset($_POST['display_item_code']) ? $_POST['display_item_code'] : 0;
						$display_item_title2 = isset($_POST['display_item_code_hex']) ? $_POST['display_item_code_hex'] : 0;
						$items = [];
						$buffs = [];
						if(isset($_POST['item_category']) && !empty($_POST['item_category']) && $reward_type == 9){
							$amount = 0;
							$vip_type = '';
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
						
						if(isset($_POST['item_hex'])  && !empty($_POST['item_hex']) && $reward_type == 10){
							$amount = 0;
							$vip_type = '';
							$display_item_title = $display_item_title2;
							foreach($_POST['item_hex'] AS $key => $val){
								if($val != ''){
									$items[] = [
										'hex' => $val
									];
								}
							}
						}
						
						if(isset($_POST['item_category_buff']) && !empty($_POST['item_category_buff']) && $reward_type == 11){
							$amount = 0;
							$vip_type = '';
							foreach($_POST['item_category_buff'] AS $key => $val){
								if($val != ''){
									$buffs[] = [
										'cat' => $_POST['item_category_buff'][$key],
										'id' => $_POST['item_index_buff'][$key],
										'expires' => $_POST['item_expires_buff'][$key],
									];
								}
							}
						}
						
						if($title == ''){
							$this->vars['error'] = 'Please enter title.';
						}
						else{
							if($reward_type == ''){
								$this->vars['error'] = 'Please select reward type.';
							}
						}
						
						if(!isset($this->vars['error'])){
							$this->vars['reward_list'][$this->vars['server']][$id][uniqid()] = [
								'title' => $title,
								'pass_type' => $pass_type,
								'reward_type' => $reward_type,
								'amount' => $amount,
								'vip_type' => $vip_type,
								'items' => $items,
								'display_item_title' => $display_item_title,
								'buffs' => $buffs
							];
							
							$this->config->save_config_data($this->vars['reward_list'], 'battle_pass_rewards');
							$this->vars['success'] = 'Reward successfully added.';
							$this->vars['achData'] = $this->vars['reward_list'][$this->vars['server']][$id];	
						}
					}

					$this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . 'admin' . DS . 'view.rewards', $this->vars);
				}
				else{
					$this->vars['error'] = __('Invalid server.');
				}
			} else{
                $this->pluginaizer->redirect($this->config->base_url . 'admincp/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()) . '/admin');
            }
		}
		
		 
		public function edit_reward($id, $rid, $server){
			if($this->pluginaizer->session->is_admin()){
				$this->vars['server'] = $server;
				if($this->vars['server'] != ''){
					$this->load->helper('website');
					$this->load->helper('webshop');
					$this->load->lib('npc');
					$this->load->model('admin');
					$this->load->model('application/plugins/'.$this->pluginaizer->get_plugin_class().'/models/'.$this->pluginaizer->get_plugin_class());
					$this->vars['js'] = $this->config->base_url . 'assets/plugins/js/'.$this->pluginaizer->get_plugin_class().'.js';
					$this->vars['is_multi_server'] = $this->pluginaizer->data()->value('is_multi_server');
					$this->vars['monster_list'] = $this->pluginaizer->npc->get_list();
					$this->vars['class_list'] = $this->pluginaizer->website->get_char_class(0, false, true);
					$this->vars['battle_pass'] = $this->config->values('battle_pass_levels');
					$this->vars['aid'] = $id;
					$this->vars['achKey'] = -1;
					if(!empty($this->vars['battle_pass'][$this->vars['server']])){
						foreach($this->vars['battle_pass'][$this->vars['server']] AS $key => $data){
							if($data['id'] == $id){
								$this->vars['achKey'] = $key;
								break;
							}
						}
					}
					else{
						$this->vars['not_found'] = __('Battle Pass level not found.');
					}
					

					if($this->vars['achKey'] == -1){
						$this->vars['not_found'] = __('Battle Pass level not found.');
					}
					
					$this->vars['reward_list'] = $this->config->values('battle_pass_rewards');
					
					$this->vars['achData'] = [];
					
					if(isset($this->vars['reward_list'][$this->vars['server']][$id][$rid])){
						$this->vars['achData'] = $this->vars['reward_list'][$this->vars['server']][$id][$rid];
					}
					
					if(isset($_POST['edit_reward'])){
						$title = isset($_POST['title']) ? $_POST['title'] : '';
						$pass_type = isset($_POST['pass_type']) ? $_POST['pass_type'] : '';
						$reward_type = isset($_POST['reward_type']) ? $_POST['reward_type'] : '';
						$amount = isset($_POST['amount']) ? $_POST['amount'] : 0;
						$vip_type = isset($_POST['vip_type']) ? $_POST['vip_type'] : '';
						$display_item_title = isset($_POST['display_item_code']) ? $_POST['display_item_code'] : 0;
						$display_item_title2 = isset($_POST['display_item_code_hex']) ? $_POST['display_item_code_hex'] : 0;
						$items = [];
						$buffs = [];
						if(isset($_POST['item_category']) && !empty($_POST['item_category']) && $reward_type == 9){
							$amount = 0;
							$vip_type = '';
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
						
						if(isset($_POST['item_hex'])  && !empty($_POST['item_hex']) && $reward_type == 10){
							$amount = 0;
							$vip_type = '';
							$display_item_title = $display_item_title2;
							foreach($_POST['item_hex'] AS $key => $val){
								if($val != ''){
									$items[] = [
										'hex' => $val
									];
								}
							}
						}
						
						if(isset($_POST['item_category_buff']) && !empty($_POST['item_category_buff']) && $reward_type == 11){
							$amount = 0;
							$vip_type = '';
							foreach($_POST['item_category_buff'] AS $key => $val){
								if($val != ''){
									$buffs[] = [
										'cat' => $_POST['item_category_buff'][$key],
										'id' => $_POST['item_index_buff'][$key],
										'expires' => $_POST['item_expires_buff'][$key],
									];
								}
							}
						}
						
						if($title == ''){
							$this->vars['error'] = 'Please enter title.';
						}
						else{
							if($reward_type == ''){
								$this->vars['error'] = 'Please select reward type.';
							}
						}
						
						if(!isset($this->vars['error'])){
							$this->vars['reward_list'][$this->vars['server']][$id][$rid] = [
								'title' => $title,
								'pass_type' => $pass_type,
								'reward_type' => $reward_type,
								'amount' => $amount,
								'vip_type' => $vip_type,
								'items' => $items,
								'display_item_title' => $display_item_title,
								'buffs' => $buffs
							];
							
							$this->config->save_config_data($this->vars['reward_list'], 'battle_pass_rewards');
							$this->vars['success'] = 'Reward successfully edited.';
							$this->vars['achData'] = $this->vars['reward_list'][$this->vars['server']][$id][$rid];	
						}
					}

					$this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . 'admin' . DS . 'view.edit_reward', $this->vars);
				}
				else{
					$this->vars['error'] = __('Invalid server.');
				}
			} else{
                $this->pluginaizer->redirect($this->config->base_url . 'admincp/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()) . '/admin');
            }
		}
		
		 
		public function delete_reward($id, $rid, $server){
			if($this->pluginaizer->session->is_admin()){
				$this->vars['server'] = $server;
				if($this->vars['server'] != ''){
					$this->load->helper('website');
					$this->load->helper('webshop');
					$this->load->lib('npc');
					$this->load->model('admin');
					$this->load->model('application/plugins/'.$this->pluginaizer->get_plugin_class().'/models/'.$this->pluginaizer->get_plugin_class());
					$this->vars['js'] = $this->config->base_url . 'assets/plugins/js/'.$this->pluginaizer->get_plugin_class().'.js';
					$this->vars['is_multi_server'] = $this->pluginaizer->data()->value('is_multi_server');
					$this->vars['monster_list'] = $this->pluginaizer->npc->get_list();
					$this->vars['class_list'] = $this->pluginaizer->website->get_char_class(0, false, true);
					$this->vars['battle_pass'] = $this->config->values('battle_pass_levels');
					$this->vars['aid'] = $id;
					$this->vars['achKey'] = -1;
					if(!empty($this->vars['battle_pass'][$this->vars['server']])){
						foreach($this->vars['battle_pass'][$this->vars['server']] AS $key => $data){
							if($data['id'] == $id){
								$this->vars['achKey'] = $key;
								break;
							}
						}
					}
					else{
						$this->vars['not_found'] = __('Battle Pass level not found.');
					}
					

					if($this->vars['achKey'] == -1){
						$this->vars['not_found'] = __('Battle Pass level not found.');
					}
					
					$this->vars['reward_list'] = $this->config->values('battle_pass_rewards');
					
					$this->vars['achData'] = [];
					
					if(isset($this->vars['reward_list'][$this->vars['server']][$id][$rid])){
						unset($this->vars['reward_list'][$this->vars['server']][$id][$rid]);
						$this->config->save_config_data($this->vars['reward_list'], 'battle_pass_rewards');
						$this->vars['success'] = 'Reward successfully removed.';
					}
					else{
						$this->vars['error'] = 'Reward not found';
					}
					
					$this->vars['achData'] = $this->vars['reward_list'][$this->vars['server']][$id];

					$this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . 'admin' . DS . 'view.rewards', $this->vars);
				}
				else{
					$this->vars['error'] = __('Invalid server.');
				}
			} else{
                $this->pluginaizer->redirect($this->config->base_url . 'admincp/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()) . '/admin');
            }
		}
		
		 
		public function save_reward_order($bid, $server){
            //check if visitor has administrator privilleges
            if($this->pluginaizer->session->is_admin()){
                $this->vars['battle_pass_rewards'] = $this->config->values('battle_pass_rewards');
				$this->vars['new'] = [];	
				foreach($_POST['order'] AS $value){
                    if(array_key_exists($value, $this->vars['battle_pass_rewards'][$server][$bid])){
                        $this->vars['new'][$server][$bid][$value] = $this->vars['battle_pass_rewards'][$server][$bid][$value];
                    }
                }
				
				if(!empty($this->vars['new'])){
					$this->vars['battle_pass_rewards'][$server][$bid] = $this->vars['new'][$server][$bid];
					$this->config->save_config_data($this->vars['battle_pass_rewards'], 'battle_pass_rewards');
				}
            } 
			else{
                echo $this->pluginaizer->jsone(['error' => 'Please login first!']);
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
                //load website helper
                $this->load->helper('website');
                $this->load->model('application/plugins/'.$this->pluginaizer->get_plugin_class().'/models/'.$this->pluginaizer->get_plugin_class());
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
					'description' => 'Each day receive rewards for completing missions.' //description which will see user
				]);
				//create plugin config template
				$this->pluginaizer->create_config([
					'active' => 0, 
					'battle_pass_start_time' => '', 
					'battle_pass_end_time' => '',
					'sale_start_time' => '', 
					'sale_end_time' => '', 
					'silver_pass_upgrade_price' => 1000,
					'silver_pass_payment_type' => 1,
					'platinum_pass_upgrade_price' => 5000,
					'platinum_pass_payment_type' => 1,
					'allow_reset_pass_progress' => 1,
					'reset_pass_progress_price' => 5000,
					'reset_pass_progress_payment_type' => 1
				]);
				//add sql scheme if there is any into website database
				//all schemes should be located in plugin_folder/sql_schemes
				$this->pluginaizer->add_sql_scheme('completed_requirements');
				$this->pluginaizer->add_sql_scheme('progress');
				$this->pluginaizer->add_sql_scheme('reward_log');
				$this->pluginaizer->add_sql_scheme('unlocked_battle_pass');

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
                $this->pluginaizer->delete_config()->remove_sql_scheme('completed_requirements')->remove_sql_scheme('progress')->remove_sql_scheme('reward_log')->remove_sql_scheme('unlocked_battle_pass')->remove_plugin();
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