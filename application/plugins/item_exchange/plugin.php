<?php
	
    class _plugin_item_exchange extends controller implements pluginInterface
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
						$this->load->model('shop');
						$this->vars['exchange_list'] = $this->config->values('item_exchange_list', $this->pluginaizer->session->userdata(['user' => 'server']));
						$this->vars['reward_list'] = $this->config->values('item_exchange_reward_list', $this->pluginaizer->session->userdata(['user' => 'server']));

						$this->load->lib('iteminfo');
						$this->load->lib('itemimage');
						$this->load->lib('createitem', [MU_VERSION, SOCKET_LIBRARY, $this->pluginaizer->website->get_value_from_server($this->pluginaizer->session->userdata(['user' => 'server']), 'item_size')]);
						
						$this->vars['currency_points'] = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->checkCurrencyPoints($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
						if($this->vars['currency_points'] == false)
							$this->vars['currency_points'] = 0;
						$this->vars['characters'] = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->load_char_list($this->pluginaizer->session->userdata(['user' => 'username']),$this->pluginaizer->session->userdata(['user' => 'server']));
						
						foreach($this->vars['exchange_list'] AS $type => $exchange){
							if($exchange['status'] == 1){
								if(!empty($exchange['items'])){
									$this->vars['exchange_items'][$type] = [];
									foreach($exchange['items'] AS $eitem){
										if($this->pluginaizer->iteminfo->setItemData($eitem['id'], $eitem['cat'], $this->pluginaizer->website->get_value_from_server($this->pluginaizer->session->userdata(['user' => 'server']), 'item_size'))){
											$this->pluginaizer->createitem->setItemData($this->pluginaizer->iteminfo->item_data);
											$this->pluginaizer->createitem->id($eitem['id']);
											$this->pluginaizer->createitem->cat($eitem['cat']);
											$this->pluginaizer->createitem->refinery(false);
											$this->pluginaizer->createitem->serial(0);
											if($this->pluginaizer->website->get_value_from_server($this->pluginaizer->session->userdata(['user' => 'server']), 'item_size') == 64){
												$this->createitem->serial2(true);
											}
											if($eitem['lvl'] != ''){
												$this->pluginaizer->createitem->lvl($eitem['lvl']);
											}
											else{
												$this->pluginaizer->createitem->lvl(0);
											}
											if($eitem['skill'] != '' && $eitem['skill'] == 1){
												$this->pluginaizer->createitem->skill(true);
											}
											else{
												$this->pluginaizer->createitem->skill(false);
											}
											if($eitem['luck'] != '' && $eitem['luck'] == 1){
												$this->pluginaizer->createitem->luck(true);
											}
											else{
												$this->pluginaizer->createitem->luck(false);
											}
											if($eitem['opt'] != ''){
												$this->pluginaizer->createitem->opt($eitem['opt']);
											}
											else{
												$this->pluginaizer->createitem->opt(0);
											}
											if($eitem['exe'] != ''){
												$exe_opts = [0 => 1, 1 => 2, 2 => 4, 3 => 8, 4 => 16, 5 => 32];
												$iexe = explode(',', $eitem['exe']);
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
											if($eitem['anc'] != '' && $eitem['anc'] != 0){
												$this->pluginaizer->createitem->ancient($eitem['anc']);
											}
											
											$itemHex = $this->pluginaizer->createitem->to_hex();
											
											$this->pluginaizer->iteminfo->itemData($itemHex, true, $this->pluginaizer->session->userdata(['user' => 'server']));
											$this->vars['exchange_items'][$type][] = [
												'hex' => $itemHex,
												'name' => $this->pluginaizer->iteminfo->getNameStyle(true),
												'serial' => 00000000,
												'amount' => $eitem['amount'],
												'item_info' => $this->itemimage->load($this->iteminfo->id, $this->iteminfo->type, (int)substr($this->iteminfo->getLevel(), 1)) . '<br />' . $this->iteminfo->allInfo()
											];
										}
									}
								}
							}
						}
						
						foreach($this->vars['reward_list'] AS $type => $rewards){
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
											if($ritem['expires'] != ''){
												$this->pluginaizer->iteminfo->setExpireTime($ritem['expires']);
											}
											$this->vars['reward_items'][$type][] = [
												'hex' => $itemHex,
												'name' => $this->pluginaizer->iteminfo->getNameStyle(true),
												'serial' => 00000000,
												'expires' => $ritem['expires'],
												'item_info' => $this->itemimage->load($this->iteminfo->id, $this->iteminfo->type, (int)substr($this->iteminfo->getLevel(), 1)) . '<br />' . $this->iteminfo->allInfo()
											];
										}
									}
								}
								
								if(!empty($rewards['items2'])){
									if(!isset($this->vars['reward_items'][$type])){
										$this->vars['reward_items'][$type] = [];
									}
									foreach($rewards['items2'] AS $ritemHex){
										$this->iteminfo->itemData($ritemHex['hex']);
										if($this->pluginaizer->website->get_value_from_server($this->pluginaizer->session->userdata(['user' => 'server']), 'item_size') == 64){
											$ritemHex['hex'] = substr_replace($ritemHex['hex'], sprintf("%08X", 0, 00000000), 6, 8);
											$ritemHex['hex'] = substr_replace($ritemHex['hex'], sprintf("%08X", 0, 00000000), 32, 8);
										}
										else{
											$ritemHex['hex'] = substr_replace($ritemHex['hex'], sprintf("%08X", 0, 00000000), 6, 8);
										}
										
										$this->vars['reward_items'][$type][] = [
											'hex' => $ritemHex['hex'],
											'name' => $this->pluginaizer->iteminfo->getNameStyle(true),
											'serial' => 00000000,
											'expires' => '',
											'item_info' => $this->itemimage->load($this->iteminfo->id, $this->iteminfo->type, (int)substr($this->iteminfo->getLevel(), 1)) . '<br />' . $this->iteminfo->allInfo()
										];
									}
								}
							}
						}
                    }
                } else{
                    $this->vars['config_not_found'] = __('Plugin configuration not found.');
                }
                
                //load template
                $this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . $this->config->config_entry('main|template') . DS . 'view.item_exchange', $this->vars);
            } else{
                $this->pluginaizer->redirect($this->config->base_url . 'account-panel/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()));
            }
        }
		
		
		public function claim($id = ''){
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
							
							$this->vars['reward_list'] = $this->config->values('item_exchange_reward_list', $this->pluginaizer->session->userdata(['user' => 'server']));

							$rewardData = $this->vars['reward_list'][$id];
							
							if(!isset($rewardData) || empty($rewardData)){
								throw new Exception(__('Reward not found.'));
							}
							
							$this->vars['currency_points'] = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->checkCurrencyPoints($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
							if($this->vars['currency_points'] == false)
								$this->vars['currency_points'] = 0;
							
							if($rewardData['currency_amount'] > $this->vars['currency_points']){
								throw new Exception(sprintf(__('Insufficient %s.'), $this->vars['plugin_config']['currency_name']));
							}
							
							if((isset($rewardData['wcoin']) && $rewardData['wcoin'] > 0) || (isset($rewardData['goblin']) && $rewardData['goblin'] > 0) || !empty($rewardData['items'])){
								if(!$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->check_connect_stat($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']))){
									throw new Exception(__('Please logout from game.'));
								}
							}
							
							$this->vars['character'] = isset($_POST['character']) ? $_POST['character'] : '';
							
							$this->vars['character_data'] = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->check_char($this->vars['character'], $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), true);
							
							if($this->vars['character_data'] == false){
								throw new Exception(__('Invalid character.'));
							}

							if(isset($rewardData['wcoin']) && $rewardData['wcoin'] > 0){
								$this->vars['table_config'] = $this->pluginaizer->config->values('table_config', $this->pluginaizer->session->userdata(['user' => 'server']));
								if(!isset($this->vars['table_config']['wcoins']))
									throw new Exception(__('WCoins configuration not found'));
								if($this->vars['table_config']['wcoins']['table'] == '')
									throw new Exception(__('WCoins configuration not found'));
							}
							
							if(isset($rewardData['goblin']) && $rewardData['goblin'] > 0){
								if(!isset($this->vars['table_config'])){
									$this->vars['table_config'] = $this->pluginaizer->config->values('table_config', $this->pluginaizer->session->userdata(['user' => 'server']));
								}
								if(!isset($this->vars['table_config']['goblinpoint']))
									throw new Exception(__('GoblinPoint configuration not found'));
								if($this->vars['table_config']['goblinpoint']['table'] == '')
									throw new Exception(__('GoblinPoint configuration not found'));
							}
							
							$this->load->lib('iteminfo');
							$this->load->lib('createitem', [MU_VERSION, SOCKET_LIBRARY, $this->pluginaizer->website->get_value_from_server($this->pluginaizer->session->userdata(['user' => 'server']), 'item_size')]);
							
							$this->vars['reward_items'] = [];
							$this->vars['reward_buffs'] = [];
								
							if(!empty($rewardData['items']) || !empty($rewardData['items2'])){
								if(!empty($rewardData['items'])){
									foreach($rewardData['items'] AS $ritem){
										static $ItemOptionManager = null;
											
										if($ItemOptionManager == null){
											$ItemOptionManager = new \DOMDocument;
											$ItemOptionManager->load(APP_PATH . DS . 'data' . DS . 'ServerData' . DS . 'ItemOptionManager.xml');
										}
										
										$xpath = new DOMXPath($ItemOptionManager);
										$node = $xpath->query("//ItemOptionManager/Section/Item");
										
										$isBuff = false;

										if($node->length > 0){
											$effectType = 0;
											$effect1 = 0;
											$effect2 = 0;
											foreach($node AS $s => $v){
												if($v->getAttribute('Index') == $ritem['id'] && $v->getAttribute('Cat') == $ritem['cat']){
													$effectType = $v->parentNode->getAttribute('ID');
													$effect1 = $v->getAttribute('Option1');
													$effect2 = $v->getAttribute('Option2');
													$isBuff = true;
													break;
												}
											}
											
											if($isBuff == true){
												$serial = $this->pluginaizer->Mshop->generate_serial($this->pluginaizer->session->userdata(['user' => 'server']));
													
												$this->vars['reward_buffs'][] = [
													'serial' => $serial,
													'expires' => $ritem['expires'],
													'itemtype' => $this->pluginaizer->iteminfo->itemIndex($ritem['cat'], $ritem['id']),
													'effect_type' => $effectType,
													'effect1' => $effect1,
													'effect2' => $effect2
												];
											}
										}
										
										if($this->pluginaizer->iteminfo->setItemData($ritem['id'], $ritem['cat'], $this->pluginaizer->website->get_value_from_server($this->pluginaizer->session->userdata(['user' => 'server']), 'item_size')) && $isBuff == false){	
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
								
								if(!empty($rewardData['items2'])){
									foreach($rewardData['items2'] AS $ritemHex){
										$this->iteminfo->itemData($ritemHex['hex']);
										$serial = $this->pluginaizer->Mshop->generate_serial($this->pluginaizer->session->userdata(['user' => 'server']));
										if($this->pluginaizer->website->get_value_from_server($this->pluginaizer->session->userdata(['user' => 'server']), 'item_size') == 64){
											$ritemHex['hex'] = substr_replace($ritemHex['hex'], sprintf("%08X", 0, 00000000), 6, 8);
											$ritemHex['hex'] = substr_replace($ritemHex['hex'], sprintf("%08X", $serial, 00000000), 32, 8);
										}
										else{
											$ritemHex['hex'] = substr_replace($ritemHex['hex'], sprintf("%08X", $serial, 00000000), 6, 8);
										}
										
										$this->vars['reward_items'][] = [
											'hex' => $ritemHex['hex'],
											'name' => $this->pluginaizer->iteminfo->getNameStyle(true),
											'serial' => $serial,
											'expires' => '',
											'itemtype' => ''
										];
									}
								}
								
								if(!empty($this->vars['reward_items'])){
									$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->inventory($this->vars['character'], $this->pluginaizer->session->userdata(['user' => 'server']));
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
							if(isset($rewardData['credits1']) && $rewardData['credits1'] > 0){
								$this->pluginaizer->website->add_credits($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $rewardData['credits1'], 1, false, $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->get_guid($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server'])));	
							}
							
							if(isset($rewardData['credits2']) && $rewardData['credits2'] > 0){
								$this->pluginaizer->website->add_credits($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $rewardData['credits2'], 2, false, $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->get_guid($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server'])));							
							}
							if(isset($rewardData['wcoin']) && $rewardData['wcoin'] > 0){
								$acc = (in_array($this->vars['table_config']['wcoins']['identifier_column'], ['MemberGuid', 'memb_guid'])) ? $this->pluginaizer->session->userdata(['user' => 'id']) : $this->pluginaizer->session->userdata(['user' => 'username']);
								$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->add_wcoins($acc, $this->pluginaizer->session->userdata(['user' => 'server']), $rewardData['wcoin'], $this->vars['table_config']['wcoins']);
							}
							if(isset($rewardData['goblin']) && $rewardData['goblin'] > 0){
								$acc = (in_array($this->vars['table_config']['goblinpoint']['identifier_column'], ['MemberGuid', 'memb_guid'])) ? $this->pluginaizer->session->userdata(['user' => 'id']) : $this->pluginaizer->session->userdata(['user' => 'username']);
								$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->add_wcoins($acc, $this->pluginaizer->session->userdata(['user' => 'server']), $rewardData['goblin'], $this->vars['table_config']['goblinpoint']);					
							}
							if(isset($rewardData['credits3']) && $rewardData['credits3'] > 0){
								$this->pluginaizer->website->add_credits($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $rewardData['credits3'], 3, false, $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->get_guid($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server'])));							
							}
							if(isset($rewardData['vip_type']) && $rewardData['vip_type'] != ''){
								$vip_config = $this->pluginaizer->config->values('vip_config');
								$vip_query_config = $this->pluginaizer->config->values('vip_query_config');
								$table_config = $this->pluginaizer->config->values('table_config', $this->pluginaizer->session->userdata(['user' => 'server']));
								$this->load->model('account');
								
								$this->vars['vip_data'] = $this->pluginaizer->Mshop->check_vip($rewardData['vip_type'], $this->pluginaizer->session->userdata(['user' => 'server']));
								$viptime = time() + $this->vars['vip_data']['vip_time'];
								if($this->vars['existing'] = $this->pluginaizer->Mshop->check_existing_vip_package($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']))){
									if($this->vars['existing']['viptime'] > time()){
										$viptime = $this->vars['existing']['viptime'] + $this->vars['vip_data']['vip_time'];
									}
									$this->pluginaizer->Mshop->update_vip_package($rewardData['vip_type'], $viptime, $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
									$this->pluginaizer->Mshop->add_server_vip($viptime, $this->vars['vip_data']['server_vip_package'], $this->vars['vip_data']['connect_member_load'], $vip_query_config, $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
									$this->pluginaizer->Maccount->set_vip_session($viptime, $this->vars['vip_data']);	
								}
								else{
									$this->pluginaizer->Mshop->insert_vip_package($rewardData['vip_type'], $viptime, $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
									$this->pluginaizer->Mshop->add_server_vip($viptime, $this->vars['vip_data']['server_vip_package'], $this->vars['vip_data']['connect_member_load'], $vip_query_config, $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
									$this->pluginaizer->Maccount->set_vip_session($viptime, $this->vars['vip_data']);	
								}	
							}
							
							if(!empty($this->vars['reward_items'])){
								$newInv = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->addItemsToInventory($itemArr, $this->pluginaizer->session->userdata(['user' => 'server']));
								if(!empty($expirableItems)){
									$currTime = time();
									foreach($expirableItems AS $expideData){
										$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->addExpirableItem($this->pluginaizer->session->userdata(['user' => 'id']), $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['character_data']['Name'], $expideData['index'], $expideData['time'], $expideData['serial'], $currTime, 0, 0, 0, 2);
									}
								}
								$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->updateInventory($this->vars['character'], $this->pluginaizer->session->userdata(['user' => 'server']), $newInv);
							}
							if(!empty($this->vars['reward_buffs'])){
								$currTime = time();
								foreach($this->vars['reward_buffs'] AS $expireData){
									$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->addExpirableItem($this->pluginaizer->session->userdata(['user' => 'id']), $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['character_data']['Name'], $expireData['itemtype'], $expireData['expires'], $expireData['serial'], $currTime, $expireData['effect_type'], $expireData['effect1'], $expireData['effect2'], 1);
								}
							}
							$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->decreaseCurrencyPoints($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $rewardData['currency_amount']);
							$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->log_reward($id, $this->vars['character'], $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
							$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->add_account_log('Claimed item exchange reward.', 0, $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
							echo $this->pluginaizer->jsone(['success' => __('Reward successfully claimed'), 'new_points' => $this->vars['currency_points'] - $rewardData['currency_amount']]);															
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
		
		
		
		public function exchange($id = ''){
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
							
							$this->vars['exchange_list'] = $this->config->values('item_exchange_list', $this->pluginaizer->session->userdata(['user' => 'server']));
							$this->vars['achKey'] = $id;
							
							$exchangeData = $this->vars['exchange_list'][$id];
							
							if(!isset($exchangeData) || empty($exchangeData)){
								throw new Exception(__('Exchange not found.'));
							}
							
							$this->vars['currency_points'] = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->checkCurrencyPoints($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
							if($this->vars['currency_points'] == false)
								$this->vars['currency_points'] = 0;
							
							
							if(!$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->check_connect_stat($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']))){
								throw new Exception(__('Please logout from game.'));
							}
							
							$this->load->lib('iteminfo');
							$this->load->lib('createitem', [MU_VERSION, SOCKET_LIBRARY, $this->pluginaizer->website->get_value_from_server($this->pluginaizer->session->userdata(['user' => 'server']), 'item_size')]);
							$this->load->model('warehouse');
							
							$this->vars['item_list'] = [];
							$slotsToRemove = false;

							if(!empty($this->vars['exchange_list'][$this->vars['achKey']]['items'])){
								foreach($this->vars['exchange_list'][$this->vars['achKey']]['items'] AS $itemKey => $item){
									if($this->pluginaizer->iteminfo->setItemData($item['id'], $item['cat'], $this->pluginaizer->website->get_value_from_server($this->pluginaizer->session->userdata(['user' => 'server']), 'item_size'))){	
										$this->pluginaizer->createitem->setItemData($this->pluginaizer->iteminfo->item_data);
										$this->pluginaizer->createitem->id($item['id']);
										$this->pluginaizer->createitem->cat($item['cat']);
										$this->pluginaizer->createitem->refinery(false);
										$this->pluginaizer->createitem->serial(0);
										if($this->pluginaizer->website->get_value_from_server($this->session->userdata(['user' => 'server']), 'item_size') == 64){
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

											foreach($iexe AS $k => $val){
												if($val == 0){
													unset($iexe[$k]);
												}
											}
											
											if(!empty($iexe)){		
												foreach($iexe as $kk => $exe_opt){
													if($exe_opt == 1){
														$exe += $exe_opts[$kk];
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
										$this->vars['item_list'][] = [
											'hex' => $itemHex,
											'name' => $this->pluginaizer->iteminfo->getNameStyle(true),
											'data' => $item,
											'itemKey' => $itemKey
										];
									}
								}
							}

							$vault = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->vault($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
							if($vault != true){
								throw new Exception(__('Please open your warehouse in game first.'));
							}
							
							$items = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->getVaultContents($this->pluginaizer->session->userdata(['user' => 'server']));
							
							foreach($this->vars['item_list'] AS $key => $item){
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

										if($lvlOk == true && $optOk == true && $skillOk == true && $luckOk == true && $exe0Ok == true && $exe1Ok == true && $exe2Ok == true && $exe3Ok == true && $exe4Ok == true && $exe5Ok == true && $itemCount > 0){
											$slotsToRemove[$slot] = $slot;
											
											if($itemCount > 1){
												foreach($this->vars['exchange_list'][$this->vars['achKey']]['items'] AS $k => $dbItem){
													if($k == $item['itemKey']){									
														$this->vars['exchange_list'][$this->vars['achKey']]['items'][$k]['amount'] = $itemCount - 1;
													}
												}
											}
											else{														
												foreach($this->vars['exchange_list'][$this->vars['achKey']]['items'] AS $k => $dbItem){																
													if($itemCount == 1){																	
														if($k == $item['itemKey']){																		
															unset($this->vars['exchange_list'][$this->vars['achKey']]['items'][$k]);
														}
													}
												}
											}
											$itemCount -= 1;
										}
									 }
								 }
							}
							if($itemCount != 0){
								throw new Exception(__('Missing some of items in warehouse'));
							}
							
							//print_r($slotsToRemove);die();
							if($slotsToRemove != false){
								$newItems = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->updateVaultSlots($slotsToRemove, $this->pluginaizer->session->userdata(['user' => 'server']));
								$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->updateVault($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $newItems);
								$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->increaseCurrencyPoints($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $exchangeData['currency_amount']);
								$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->log_reward($id, '', $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
								$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->add_account_log('Exchanged items.', 0, $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
								echo $this->pluginaizer->jsone(['success' => __('Items successfully exchanged'), 'new_points' => $this->vars['currency_points'] + $exchangeData['currency_amount']]);		
							}
							else{
								throw new Exception(__('Missing some of items in warehouse'));
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

        /**
         *
         * Load public module data
         *
         * return mixed
         *
         */
        private function public_module($server = ''){
            
        }
		
		public function delete_exchange($id, $server){
			if($this->pluginaizer->session->is_admin()){
				$this->vars['server'] = $server;
				if($this->vars['server'] != ''){
					$this->load->helper('website');
					$this->vars['is_multi_server'] = $this->pluginaizer->data()->value('is_multi_server');

					$this->vars['exchange_list'] = $this->config->values('item_exchange_list');
					//$this->vars['id'] = explode('-', $id);
					if(!empty($this->vars['exchange_list'][$this->vars['server']][$id])){
						unset($this->vars['exchange_list'][$this->vars['server']][$id]);
					}
					else{
						$this->vars['not_found'] = 'Exchange not found.';
					}
					
					$this->config->save_config_data($this->vars['exchange_list'], 'item_exchange_list');
					$this->vars['success'] = 'Exchange successfully removed.';

					$this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . 'admin' . DS . 'view.delete', $this->vars);
				}
				else{
					$this->vars['error'] = __('Invalid server.');
				}
			} else{
                $this->pluginaizer->redirect($this->config->base_url . 'admincp/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()) . '/admin');
            }
		}
		
		public function change_exchange_status($id, $server){
			if($this->pluginaizer->session->is_admin()){
				$this->vars['server'] = $server;
				if($this->vars['server'] != ''){
					$this->load->helper('website');
					$this->vars['is_multi_server'] = $this->pluginaizer->data()->value('is_multi_server');

					$this->vars['exchange_list'] = $this->config->values('item_exchange_list');
					$this->vars['id'] = explode('-', $id);
					if(!empty($this->vars['exchange_list'][$this->vars['server']][$this->vars['id'][0]])){
						$this->vars['exchange_list'][$this->vars['server']][$this->vars['id'][0]]['status'] = $this->vars['id'][1];
					}
					else{
						$this->vars['not_found'] = 'Exchange not found.';
					}
					
					$this->config->save_config_data($this->vars['exchange_list'], 'item_exchange_list');
					$this->vars['success'] = 'Exchange status changed.';

					$this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . 'admin' . DS . 'view.delete', $this->vars);
				}
				else{
					$this->vars['error'] = __('Invalid server.');
				}
			} else{
                $this->pluginaizer->redirect($this->config->base_url . 'admincp/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()) . '/admin');
            }
		}

		 		
		public function edit_exchange($id, $server){
			if($this->pluginaizer->session->is_admin()){
				$this->vars['server'] = $server;
				$this->vars['is_multi_server'] = $this->pluginaizer->data()->value('is_multi_server');
				if($this->vars['server'] != ''){
					$this->load->helper('website');
					$this->load->model('admin');
					$this->load->model('application/plugins/' . $this->pluginaizer->get_plugin_class() . '/models/' . $this->pluginaizer->get_plugin_class());     
					
					$this->vars['exchange_list'] = $this->config->values('item_exchange_list');
					
					if(!empty($this->vars['exchange_list'][$this->vars['server']][$id])){
						$this->vars['achData'] = $this->vars['exchange_list'][$this->vars['server']][$id];
						if(isset($_POST['edit_exchange'])){
							$items = [];
							if($_POST['item_count']){
								foreach($_POST['item_count'] AS $key => $val){
									if($val > 0){
										$items[] = [
											'amount' => $val,
											'cat' => $_POST['item_category'][$key],
											'id' => $_POST['item_index'][$key],
											'lvl' => $_POST['item_level'][$key],
											'skill' => $_POST['item_skill'][$key],
											'luck' => $_POST['item_luck'][$key],
											'opt' => $_POST['item_option'][$key],
											'exe' => $_POST['item_excellent'][$key],
											'anc' => $_POST['item_ancient'][$key],
										];
									}
								}
							}
														
							$this->vars['exchange_list'][$this->vars['server']][$id] = [
								'status' => $this->vars['exchange_list'][$this->vars['server']][$id]['status'],
								'currency_amount' => $_POST['currency_amount'], 
								'items' => $items
							];
							
							$this->config->save_config_data($this->vars['exchange_list'], 'item_exchange_list');
							$this->vars['achData'] = $this->vars['exchange_list'][$this->vars['server']][$id];
							$this->vars['success'] = 'Exchange successfully updated.';
						}
					}
					else{
						$this->vars['not_found'] = 'Exchange not found.';
					}

					$this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . 'admin' . DS . 'view.edit_exchange', $this->vars);
				}
				else{
					$this->vars['error'] = __('Invalid server.');
				}
			} else{
                $this->pluginaizer->redirect($this->config->base_url . 'admincp/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()) . '/admin');
            }
		}
		
		 
		public function exchange_list(){
			if($this->pluginaizer->session->is_admin()){
				$this->vars['server'] = isset($_GET['server']) ? $_GET['server'] : '';
				$this->vars['is_multi_server'] = $this->pluginaizer->data()->value('is_multi_server');
				if($this->vars['server'] != ''){
					$this->load->helper('website');
					$this->load->model('admin');
					$this->load->model('application/plugins/' . $this->pluginaizer->get_plugin_class() . '/models/' . $this->pluginaizer->get_plugin_class());     
					$this->vars['exchange_list'] = $this->config->values('item_exchange_list');
					
					if(isset($_POST['add_exchange'])){
						$items = [];
						if($_POST['item_count']){
							foreach($_POST['item_count'] AS $key => $val){
								if($val > 0){
									$items[] = [
										'amount' => $val,
										'cat' => $_POST['item_category'][$key],
										'id' => $_POST['item_index'][$key],
										'lvl' => $_POST['item_level'][$key],
										'skill' => $_POST['item_skill'][$key],
										'luck' => $_POST['item_luck'][$key],
										'opt' => $_POST['item_option'][$key],
										'exe' => $_POST['item_excellent'][$key],
										'anc' => $_POST['item_ancient'][$key],
									];
								}
							}
						}
						
						$_POST['items'] = $items;
						
						if(array_key_exists($this->vars['server'], $this->vars['exchange_list'])){
							$this->vars['exchange_list'][$this->vars['server']][uniqid()] = [
									'status' => 1,
									'currency_amount' => $_POST['currency_amount'], 
									'items' => $items
							];
							
							$this->config->save_config_data($this->vars['exchange_list'], 'item_exchange_list');
						}
						else{
							$this->vars['new_config'] = [
								$this->vars['server'] => [uniqid() => [
									'status' => 1,
									'currency_amount' => $_POST['currency_amount'], 
									'items' => $items						
								]
							]];
							$this->vars['exchange_list'] = array_merge($this->vars['exchange_list'], $this->vars['new_config']);
                            $this->config->save_config_data($this->vars['exchange_list'], 'item_exchange_list');
						}
						$this->vars['success'] = 'Exchange successfully updated.';
					}
					
				}
				else{
					$this->vars['error'] = __('Invalid server.');
				}
				$this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . 'admin' . DS . 'view.exchange', $this->vars);		
			}
			else{
                $this->pluginaizer->redirect($this->config->base_url . 'admincp/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()) . '/admin');
            }		
		}
		
		public function delete($id, $server){
			if($this->pluginaizer->session->is_admin()){
				$this->vars['server'] = $server;
				if($this->vars['server'] != ''){
					$this->load->helper('website');
					$this->vars['is_multi_server'] = $this->pluginaizer->data()->value('is_multi_server');

					$this->vars['reward_list'] = $this->config->values('item_exchange_reward_list');
					//$this->vars['id'] = explode('-', $id);
					if(!empty($this->vars['reward_list'][$this->vars['server']][$id])){
						unset($this->vars['reward_list'][$this->vars['server']][$id]);
					}
					else{
						$this->vars['not_found'] = 'Reward not found.';
					}
					
					$this->config->save_config_data($this->vars['reward_list'], 'item_exchange_reward_list');
					$this->vars['success'] = 'Reward successfully removed.';

					$this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . 'admin' . DS . 'view.delete_reward', $this->vars);
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

					$this->vars['reward_list'] = $this->config->values('item_exchange_reward_list');
					$this->vars['id'] = explode('-', $id);
					if(!empty($this->vars['reward_list'][$this->vars['server']][$this->vars['id'][0]])){
						$this->vars['reward_list'][$this->vars['server']][$this->vars['id'][0]]['status'] = $this->vars['id'][1];
					}
					else{
						$this->vars['not_found'] = 'Reward not found.';
					}
					
					$this->config->save_config_data($this->vars['reward_list'], 'item_exchange_reward_list');
					$this->vars['success'] = 'Reward status changed.';

					$this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . 'admin' . DS . 'view.delete_reward', $this->vars);
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
					
					$this->vars['reward_list'] = $this->config->values('item_exchange_reward_list');
					
					if(!empty($this->vars['reward_list'][$this->vars['server']][$id])){
						$this->vars['achData'] = $this->vars['reward_list'][$this->vars['server']][$id];
						if(isset($_POST['add_reward'])){
							$items = [];
							$items2 = [];
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
							
							if(isset($_POST['item_hex'])  && !empty($_POST['item_hex'])){
								foreach($_POST['item_hex'] AS $key => $val){
									if($val != ''){
										$items2[] = [
											'hex' => $val
										];
									}
								}
							}
							
							$this->vars['reward_list'][$this->vars['server']][$id] = [
								'status' => $this->vars['reward_list'][$this->vars['server']][$id]['status'],
								'currency_amount' => $_POST['currency_amount'], 
								'credits1' => $_POST['credits1'],
								'credits2' => $_POST['credits2'],
								'wcoin' => $_POST['wcoin'],
								'goblin' => $_POST['goblin'],
								'credits3' => $_POST['credits3'],
								'vip_type' => $_POST['vip_type'],
								'items' => $items,
								'items2' => $items2
							];
							
							$this->config->save_config_data($this->vars['reward_list'], 'item_exchange_reward_list');
							$this->vars['achData'] = $this->vars['reward_list'][$this->vars['server']][$id];
							$this->vars['success'] = 'Reward successfully updated.';
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
		
		public function reward_list(){
			if($this->pluginaizer->session->is_admin()){
				$this->vars['server'] = isset($_GET['server']) ? $_GET['server'] : '';
				$this->vars['is_multi_server'] = $this->pluginaizer->data()->value('is_multi_server');
				if($this->vars['server'] != ''){
					$this->load->helper('website');
					$this->load->model('admin');
					$this->load->model('application/plugins/' . $this->pluginaizer->get_plugin_class() . '/models/' . $this->pluginaizer->get_plugin_class());     
					$this->vars['reward_list'] = $this->config->values('item_exchange_reward_list');
					
					if(isset($_POST['add_reward'])){
						$items = [];
						$items2 = [];
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
						
						if(isset($_POST['item_hex'])  && !empty($_POST['item_hex'])){
							foreach($_POST['item_hex'] AS $key => $val){
								if($val != ''){
									$items2[] = [
										'hex' => $val
									];
								}
							}
						}
						
						if(array_key_exists($this->vars['server'], $this->vars['reward_list'])){
							$this->vars['reward_list'][$this->vars['server']][uniqid()] = [
									'status' => 1,
									'currency_amount' => $_POST['currency_amount'], 
									'credits1' => $_POST['credits1'],
									'credits2' => $_POST['credits2'],
									'wcoin' => $_POST['wcoin'],
									'goblin' => $_POST['goblin'],
									'credits3' => $_POST['credits3'],
									'vip_type' => $_POST['vip_type'],
									'items' => $items,
									'items2' => $items2
							];
							
							$this->config->save_config_data($this->vars['reward_list'], 'item_exchange_reward_list');
						}
						else{
							$this->vars['new_config'] = [
								$this->vars['server'] => [uniqid() => [
									'status' => 1,
									'currency_amount' => $_POST['currency_amount'], 
									'credits1' => $_POST['credits1'],
									'credits2' => $_POST['credits2'],
									'wcoin' => $_POST['wcoin'],
									'goblin' => $_POST['goblin'],
									'credits3' => $_POST['credits3'],
									'vip_type' => $_POST['vip_type'],
									'items' => $items,
									'items2' => $items2								
								]
							]];
							$this->vars['reward_list'] = array_merge($this->vars['reward_list'], $this->vars['new_config']);
                            $this->config->save_config_data($this->vars['reward_list'], 'item_exchange_reward_list');
						}
						$this->vars['success'] = 'Reward successfully updated.';
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
                    } 
					else{
                        $this->vars['logs'] = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->load_logs(1, 25, $acc, $server);
                        $this->pluginaizer->pagination->initialize(1, 25, $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->count_total_logs($acc, $server), $this->config->base_url . 'item-exchange/logs/%s/' . $acc . '/' . $server . '');
                        $this->vars['pagination'] = $this->pluginaizer->pagination->create_links();
                    }
                } 
				else{
					if(isset($_GET['server'])){
						$server = $_GET['server'];
						$acc = '';
					}
					if($acc == '-'){
						$acc = '';
					}
                    $this->vars['logs'] = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->load_logs($page, 25, $acc, $server);
                    $lk = '';
                    if($acc != ''){
                        $lk .= '/' . $acc;
					}
					else{
						$lk .= '/-';
					}
                    $lk .= '/' . $server;
                    $this->pluginaizer->pagination->initialize($page, 25, $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->count_total_logs($acc, $server), $this->config->base_url . 'item-exchange/logs/%s' . $lk);
                    $this->vars['pagination'] = $this->pluginaizer->pagination->create_links();
                }
                $this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . 'admin' . DS . 'view.logs', $this->vars);
            } else{
                $this->pluginaizer->redirect($this->config->base_url . 'admincp/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()) . '/logs');
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
		 
		public function logs_points($page = 1, $acc = '-', $server = 'All'){
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
                    } 
					else{
                        $this->vars['logs'] = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->load_points_logs(1, 25, $acc, $server);
                        $this->pluginaizer->pagination->initialize(1, 25, $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->count_total_logs_points($acc, $server), $this->config->base_url . 'item-exchange/logs-points/%s/' . $acc . '/' . $server . '');
                        $this->vars['pagination'] = $this->pluginaizer->pagination->create_links();
                    }
                } 
				else{
					if(isset($_GET['server'])){
						$server = $_GET['server'];
						$acc = '';
					}
					if($acc == '-'){
						$acc = '';
					}
                    $this->vars['logs'] = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->load_points_logs($page, 25, $acc, $server);
                    $lk = '';
                    if($acc != ''){
                        $lk .= '/' . $acc;
					}
					else{
						$lk .= '/-';
					}
                    $lk .= '/' . $server;
                    $this->pluginaizer->pagination->initialize($page, 25, $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->count_total_logs_points($acc, $server), $this->config->base_url . 'item-exchange/logs-points/%s' . $lk);
                    $this->vars['pagination'] = $this->pluginaizer->pagination->create_links();
                }
                $this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . 'admin' . DS . 'view.logs_points', $this->vars);
            } else{
                $this->pluginaizer->redirect($this->config->base_url . 'admincp/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()) . '/logs-points');
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
                    'description' => 'Exchange your items.' //description which will see user
                ]);
                //create plugin config template
                $this->pluginaizer->create_config([
					'active' => 0,
					'currency_name' => 'Bless Points'
				]);
				
				$this->pluginaizer->add_sql_scheme('item_exchange_log');
				$this->pluginaizer->add_sql_scheme('item_exchange_points');
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
                $this->pluginaizer->delete_config()->remove_sql_scheme('item_exchange_log')->remove_sql_scheme('item_exchange_points')->remove_plugin();
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