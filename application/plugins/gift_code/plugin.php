<?php

    class _plugin_gift_code extends controller implements pluginInterface
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
						$this->load->helper('website');
                        $this->load->model('application/plugins/gift_code/models/gift_code');
						$this->vars['char_list'] = $this->pluginaizer->Mgift_code->load_char_list($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
						
						if(isset($_POST['redeem_coupon'])){
							$coupon = isset($_POST['coupon']) ? $_POST['coupon'] : '';
							$character = isset($_POST['character']) ? $_POST['character'] : '';
							if($coupon == ''){
								$this->vars['error'] = __('Invalid gift code.'); 
							}
							else{
								$this->vars['coupon_data'] = $this->pluginaizer->Mgift_code->checkCode($coupon);
								if($this->vars['coupon_data'] != false){
									if($this->vars['coupon_data']['uses_left'] <= 0){
										$this->vars['error'] = __('Coupon has reached max uses.'); 
									}
									else{
										if(str_contains($this->vars['coupon_data']['server'], ',')){
											$servers = explode(',', $this->vars['coupon_data']['server']);
											$check = in_array($this->pluginaizer->session->userdata(['user' => 'server']), $servers);
										}
										else{
											$check = $this->vars['coupon_data']['server'] == $this->pluginaizer->session->userdata(['user' => 'server']);
										}
										if($check){
											if($this->vars['coupon_data']['expires'] < time()){
												$this->vars['error'] = __('Gift code has expired.'); 
											}
											else{
												if(in_array($this->vars['coupon_data']['code_type'], [4,5,6,7,8,9,10]) && !$this->pluginaizer->Mgift_code->check_connect_stat($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']))){
													$this->vars['error'] = __('Please logout from game.'); 
												}
												else{
													$charData = $this->pluginaizer->Mgift_code->check_char($character, $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
													if(in_array($this->vars['coupon_data']['code_type'], [4,5,9,10]) && !$charData){
														$this->vars['error'] = __('Character not found.'); 
													}
													else{
														$skipClass = true;
														if($this->vars['coupon_data']['char_class'] != '' and $this->vars['coupon_data']['char_class'] != null){
														//if(in_array($this->vars['coupon_data']['code_type'], [4,5,9,10]) && ($this->vars['coupon_data']['char_class'] != '' and $this->vars['coupon_data']['char_class'] != null)){
															$classes = json_decode($this->vars['coupon_data']['char_class'], true);
															if(!in_array($charData['Class'], $classes)){
																$classesReadable = [];
																foreach($classes AS $ckey => $cval){
																	$classesReadable[$ckey] = $this->pluginaizer->website->get_char_class($cval, true);
																}
																$this->vars['error'] = sprintf(__('This code only allowed for %s class'), implode(',', array_unique($classesReadable)));
																$skipClass = false;	
															}
														}
														
														if($skipClass == true){
															$this->vars['use_count_acc'] = $this->pluginaizer->Mgift_code->checkUsesByAccount($coupon, $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
															$this->vars['use_count_char'] = $this->pluginaizer->Mgift_code->checkUsesByCharacter($coupon, $character, $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
															if($this->vars['use_count_acc'] >= $this->vars['coupon_data']['max_uses_by_user'] || $this->vars['use_count_char'] >= $this->vars['coupon_data']['max_uses_by_char']){
																if($this->vars['use_count_acc'] >= $this->vars['coupon_data']['max_uses_by_user']){
																	$this->vars['error'] = sprintf(__('Gift code already used maximum times for this %s.'), __('account')); 
																}
																if($this->vars['use_count_char'] >= $this->vars['coupon_data']['max_uses_by_char']){
																	$this->vars['error'] = sprintf(__('Gift code already used maximum times for this %s.'),  __('character')); 
																}
															}
															else{
																if($this->vars['coupon_data']['min_lvl'] > $charData['cLevel'])
																	$this->vars['error'] = sprintf(__('Min level required %d'), $this->vars['coupon_data']['min_lvl']); 
																else{
																	if($this->vars['coupon_data']['min_mlvl'] > $this->pluginaizer->Mgift_code->load_master_level($charData['Name'], $this->pluginaizer->session->userdata(['user' => 'server'])))
																		$this->vars['error'] = sprintf(__('Min master level required %d'), $this->vars['coupon_data']['min_mlvl']); 
																	else{
																		if($this->vars['coupon_data']['min_res'] > $charData['resets'])
																			$this->vars['error'] = sprintf(__('Min reset required %d'), $this->vars['coupon_data']['min_res']); 
																		else{
																			if($this->vars['coupon_data']['min_gres'] > $charData['grand_resets'])
																				$this->vars['error'] = sprintf(__('Min grand reset required %d'), $this->vars['coupon_data']['min_gres']); 
																			else{
																				switch($this->vars['coupon_data']['code_type']){
																					case 1:
																					case 2:
																					case 3:
																						$this->pluginaizer->website->add_credits($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['coupon_data']['code_reward_currency'], $this->vars['coupon_data']['code_type'], false, $this->pluginaizer->Mgift_code->get_guid($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server'])));
																						$this->pluginaizer->Mgift_code->logGiftCode($coupon, $character, $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
																						$this->pluginaizer->Mgift_code->setUsesLeft($coupon);
																						$this->pluginaizer->Mgift_code->add_account_log('Received ' . $this->pluginaizer->website->translate_credits($this->vars['coupon_data']['code_type'], $this->pluginaizer->session->userdata(['user' => 'server'])).' from gift code: '.$coupon.'', $this->vars['coupon_data']['code_reward_currency'], $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
																						$this->vars['success'] = __('Gift code activated successfully.');
																					break;
																					case 4:
																						$this->pluginaizer->Mgift_code->add_zen($this->vars['coupon_data']['code_reward_currency'], $character, $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
																						$this->pluginaizer->Mgift_code->logGiftCode($coupon, $character, $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
																						$this->pluginaizer->Mgift_code->setUsesLeft($coupon);
																						$this->pluginaizer->Mgift_code->add_account_log('Received Zen from gift code: '.$coupon.'', $this->vars['coupon_data']['code_reward_currency'], $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
																						$this->vars['success'] = __('Gift code activated successfully.');
																					break;
																					case 5:
																						$this->pluginaizer->Mgift_code->add_ruud($this->vars['coupon_data']['code_reward_currency'], $character, $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
																						$this->pluginaizer->Mgift_code->logGiftCode($coupon, $character, $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
																						$this->pluginaizer->Mgift_code->setUsesLeft($coupon);
																						$this->pluginaizer->Mgift_code->add_account_log('Received Ruud from gift code: '.$coupon.'', $this->vars['coupon_data']['code_reward_currency'], $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
																						$this->vars['success'] = __('Gift code activated successfully.');
																					break;
																					case 6:
																						$this->vars['table_config'] = $this->pluginaizer->config->values('table_config', $this->pluginaizer->session->userdata(['user' => 'server']));
																						$this->pluginaizer->Mgift_code->add_wcoins($this->vars['coupon_data']['code_reward_currency'], $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $this->pluginaizer->Mgift_code->get_guid($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server'])), $this->vars['table_config']['wcoins']);
																						$this->pluginaizer->Mgift_code->logGiftCode($coupon, $character, $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
																						$this->pluginaizer->Mgift_code->setUsesLeft($coupon);
																						$this->pluginaizer->Mgift_code->add_account_log('Received Wcoins from gift code: '.$coupon.'', $this->vars['coupon_data']['code_reward_currency'], $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
																						$this->vars['success'] = __('Gift code activated successfully.');
																					break;
																					case 7:
																						$this->vars['table_config'] = $this->pluginaizer->config->values('table_config', $this->pluginaizer->session->userdata(['user' => 'server']));
																						$this->pluginaizer->Mgift_code->add_goblinpoints($this->vars['coupon_data']['code_reward_currency'], $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $this->pluginaizer->Mgift_code->get_guid($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server'])), $this->vars['table_config']['goblinpoint']);
																						$this->pluginaizer->Mgift_code->logGiftCode($coupon, $character, $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
																						$this->pluginaizer->Mgift_code->setUsesLeft($coupon);
																						$this->pluginaizer->Mgift_code->add_account_log('Received GoblinPoints from gift code: '.$coupon.'', $this->vars['coupon_data']['code_reward_currency'], $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
																						$this->vars['success'] = __('Gift code activated successfully.');
																					break;
																					case 8:
																						$vip_config = $this->pluginaizer->config->values('vip_config');
																						$vip_query_config = $this->pluginaizer->config->values('vip_query_config');
																						$table_config = $this->pluginaizer->config->values('table_config', $this->pluginaizer->session->userdata(['user' => 'server']));
																						$this->load->model('shop');
																						$this->load->model('account');
																						$this->vars['vip_data'] = $this->pluginaizer->Mshop->check_vip($this->vars['coupon_data']['code_reward_vip'], $this->pluginaizer->session->userdata(['user' => 'server']));
																						$viptime = time() + $this->vars['vip_data']['vip_time'];
																						if($this->vars['existing'] = $this->pluginaizer->Mshop->check_existing_vip_package($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']))){
																							if($this->vars['existing']['viptime'] > time()){
																								$viptime = $this->vars['existing']['viptime'] + $this->vars['vip_data']['vip_time'];
																							}
																							$this->pluginaizer->Mshop->update_vip_package($this->vars['coupon_data']['code_reward_vip'], $viptime, $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
																							$this->pluginaizer->Mshop->add_server_vip($viptime, $this->vars['vip_data']['server_vip_package'], $this->vars['vip_data']['connect_member_load'], $vip_query_config, $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
																							$this->pluginaizer->Maccount->set_vip_session($viptime, $this->vars['vip_data']);
																						}
																						else{
																							$this->pluginaizer->Mshop->insert_vip_package($this->vars['coupon_data']['code_reward_vip'], $viptime, $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
																							$this->pluginaizer->Mshop->add_server_vip($viptime, $this->vars['vip_data']['server_vip_package'], $this->vars['vip_data']['connect_member_load'], $vip_query_config, $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
																							$this->pluginaizer->Maccount->set_vip_session($viptime, $this->vars['vip_data']);
																						}	
																						
																						$this->pluginaizer->Mgift_code->logGiftCode($coupon, $character, $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
																						$this->pluginaizer->Mgift_code->setUsesLeft($coupon);
																						$this->pluginaizer->Mgift_code->add_account_log('Received vip package from gift code: '.$coupon.'', 0, $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
																						$this->vars['success'] = __('Gift code activated successfully.');
																					break;
																					case 9:
																						try{
																							$itemData = json_decode($this->vars['coupon_data']['code_reward_items'], true);
																							if(!empty($itemData)){
																								$this->load->model('shop');
																								$this->load->lib('iteminfo');
																								$this->load->lib('createitem', [MU_VERSION, SOCKET_LIBRARY, $this->pluginaizer->website->get_value_from_server($this->pluginaizer->session->userdata(['user' => 'server']), 'item_size')]);
																								$this->vars['reward_muuns'] = [];
																								$this->vars['reward_items'] = [];
													
																								foreach($itemData AS $ritem){
																									if($this->pluginaizer->iteminfo->setItemData($ritem['id'], $ritem['cat'], $this->pluginaizer->website->get_value_from_server($this->pluginaizer->session->userdata(['user' => 'server']), 'item_size'))){	
																										$this->pluginaizer->createitem->setItemData($this->pluginaizer->iteminfo->item_data);
																										$this->pluginaizer->createitem->id($ritem['id']);
																										$this->pluginaizer->createitem->refinery(false);
																										$this->pluginaizer->createitem->cat($ritem['cat']);
																										
																										if($ritem['expires'] != ''){
																											$this->pluginaizer->createitem->expirable();
																										}
																										if(isset($ritem['dur']) && $ritem['dur'] != ''){
																											$this->pluginaizer->createitem->dur($ritem['dur']);
																										}
																										$serial = array_values($this->pluginaizer->Mshop->generate_serial($this->pluginaizer->session->userdata(['user' => 'server'])))[0];
																										
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
																										if($ritem['cat'] == 16){
																											$this->vars['reward_muuns'] = [
																												'hex' => $itemHex,
																												'serial' => $serial,
																												'expires' => $ritem['expires'],
																												'itemtype' => $this->pluginaizer->iteminfo->itemIndex($ritem['cat'], $ritem['id'])
																											];
																										}
																										else{
																											$this->vars['reward_items'][] = [
																												'hex' => $itemHex,
																												'serial' => $serial,
																												'expires' => $ritem['expires'],
																												'itemtype' => $this->pluginaizer->iteminfo->itemIndex($ritem['cat'], $ritem['id'])
																											];
																										}
																									}
																									else{
																										throw new Exception(__('Unable to set item data'));
																									}
																								}
																								
																								if(!empty($this->vars['reward_muuns'])){
																									$slot = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->find_free_muun_slot($charData['Name'], $this->pluginaizer->session->userdata(['user' => 'server']));
																									if($slot === false){
																										throw new Exception(__('No slots in muun inventory.')); 
																									}
																									$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->generate_new_muun_by_slot($slot, $this->vars['reward_muuns']['hex'], $this->pluginaizer->session->userdata(['user' => 'server']));
																									$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->update_muun_inventory($charData['Name'], $this->pluginaizer->session->userdata(['user' => 'server']));
																									if($this->vars['reward_muuns']['expires'] != ''){
																										$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->add_muun_period($charData['Name'], $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['reward_muuns']['serial'], $this->vars['reward_muuns']['expires'], $this->vars['reward_muuns']['itemtype']);
																									}
																								}
																								
																								if(!empty($this->vars['reward_items'])){
																									$this->pluginaizer->Mgift_code->inventory($character, $this->pluginaizer->session->userdata(['user' => 'server']));
																									$items = $this->pluginaizer->Mgift_code->getInventoryContents($this->pluginaizer->session->userdata(['user' => 'server']));
																									$itemsList = implode('', $items);
																									$itemInfo = $this->pluginaizer->iteminfo;
																									$itemArr = str_split($itemsList, $this->pluginaizer->website->get_value_from_server($this->pluginaizer->session->userdata(['user' => 'server']), 'item_size'));
																									$takenSlots = [];
																									$expirableItems = [];
																									foreach($this->vars['reward_items'] AS $ritems){
																										$this->pluginaizer->iteminfo->itemData($ritems['hex'], true, $this->pluginaizer->session->userdata(['user' => 'server']));
																										$space = $this->pluginaizer->Mgift_code->check_space_inventory($itemArr, $this->pluginaizer->iteminfo->getX(), $this->pluginaizer->iteminfo->getY(), 64, $this->pluginaizer->website->get_value_from_server($this->pluginaizer->session->userdata(['user' => 'server']), 'item_size'), 8, 8, false, $itemInfo, $takenSlots);
																										
																										if($space === null){
																											throw new Exception($this->Mgift_code->errors[0]);
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

																									$newInv = $this->pluginaizer->Mgift_code->addItemsToInventory($itemArr, $this->pluginaizer->session->userdata(['user' => 'server']));
																									
																									if(!empty($expirableItems)){
																										$currTime = time();
																										foreach($expirableItems AS $expideData){
																											$this->pluginaizer->Mgift_code->addExpirableItem($this->pluginaizer->session->userdata(['user' => 'id']), $this->pluginaizer->session->userdata(['user' => 'server']), $this->pluginaizer->Mgift_code->char_info['Name'], $expideData['index'], $expideData['time'], $expideData['serial'], $currTime, 0, 0, 0, 2);
																										}
																									}
																									
																									$this->pluginaizer->Mgift_code->updateInventory($character, $this->pluginaizer->session->userdata(['user' => 'server']), $newInv);
																								}
																								$this->pluginaizer->Mgift_code->logGiftCode($coupon, $character, $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
																								$this->pluginaizer->Mgift_code->setUsesLeft($coupon);
																								$this->pluginaizer->Mgift_code->add_account_log('Received item from gift code: '.$coupon.'', 0, $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
																								$this->vars['success'] = __('Gift code activated successfully.');
																							}
																							else{
																								throw new Exception('Item data is empty');
																							}
																							
																						}
																						catch(Exception $e){
																							$this->vars['error'] = $e->getMessage();
																						}
																					break;
																					case 10:
																						try{
																							$itemData = json_decode($this->vars['coupon_data']['code_reward_items'], true);
																							if(!empty($itemData)){
																								$this->load->model('shop');
																								$this->load->lib('iteminfo');
																								$this->load->lib('createitem', [MU_VERSION, SOCKET_LIBRARY, $this->pluginaizer->website->get_value_from_server($this->pluginaizer->session->userdata(['user' => 'server']), 'item_size')]);
																								$this->vars['reward_items'] = [];
													
																								foreach($itemData AS $ritem){
																									$this->iteminfo->itemData($ritem['hex']);
																									$serial = array_values($this->pluginaizer->Mshop->generate_serial($this->pluginaizer->session->userdata(['user' => 'server'])))[0];
																									if($this->pluginaizer->website->get_value_from_server($this->pluginaizer->session->userdata(['user' => 'server']), 'item_size') == 64){
																										$ritem['hex'] = substr_replace($ritem['hex'], sprintf("%08X", 0, 00000000), 6, 8);
																										$ritem['hex'] = substr_replace($ritem['hex'], sprintf("%08X", $serial, 00000000), 32, 8);
																									}
																									else{
																										$ritem['hex'] = substr_replace($ritem['hex'], sprintf("%08X", $serial, 00000000), 6, 8);
																									}
																									
																									$this->vars['reward_items'][] = [
																										'hex' => $ritem['hex'],
																										'serial' => $serial,
																									];
																								}
																								
																								$this->pluginaizer->Mgift_code->inventory($character, $this->pluginaizer->session->userdata(['user' => 'server']));
																								$items = $this->pluginaizer->Mgift_code->getInventoryContents($this->pluginaizer->session->userdata(['user' => 'server']));
																								$itemsList = implode('', $items);
																								$itemInfo = $this->pluginaizer->iteminfo;
																								$itemArr = str_split($itemsList, $this->pluginaizer->website->get_value_from_server($this->pluginaizer->session->userdata(['user' => 'server']), 'item_size'));
																								$takenSlots = [];
																								$expirableItems = [];
																								foreach($this->vars['reward_items'] AS $ritems){
																									$this->pluginaizer->iteminfo->itemData($ritems['hex'], true, $this->pluginaizer->session->userdata(['user' => 'server']));
																									$space = $this->pluginaizer->Mgift_code->check_space_inventory($itemArr, $this->pluginaizer->iteminfo->getX(), $this->pluginaizer->iteminfo->getY(), 64, $this->pluginaizer->website->get_value_from_server($this->pluginaizer->session->userdata(['user' => 'server']), 'item_size'), 8, 8, false, $itemInfo, $takenSlots);
																									
																									if($space === null){
																										throw new Exception($this->Mgift_code->errors[0]);
																									}
																									$takenSlots[$space] = $space;
																									$itemArr[$space] = $ritems['hex'];											
																								}
																								$newInv = $this->pluginaizer->Mgift_code->addItemsToInventory($itemArr, $this->pluginaizer->session->userdata(['user' => 'server']));
																								$this->pluginaizer->Mgift_code->updateInventory($character, $this->pluginaizer->session->userdata(['user' => 'server']), $newInv);
																								$this->pluginaizer->Mgift_code->logGiftCode($coupon, $character, $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
																								$this->pluginaizer->Mgift_code->setUsesLeft($coupon);
																								$this->pluginaizer->Mgift_code->add_account_log('Received item from gift code: '.$coupon.'', 0, $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
																								$this->vars['success'] = __('Gift code activated successfully.');
																							}
																							else{
																								throw new Exception('Item data is empty');
																							}
																							
																						}
																						catch(Exception $e){
																							$this->vars['error'] = $e->getMessage();
																						}
																					break;
																					case 11:
																						try{
																							$this->vars['reward_buffs'] = [];
																							$itemData = json_decode($this->vars['coupon_data']['code_reward_items'], true);
																							if(!empty($itemData)){
																								static $ItemOptionManager = null;
											
																								if($ItemOptionManager == null){
																									$ItemOptionManager = new \DOMDocument;
																									$ItemOptionManager->load(APP_PATH . DS . 'data' . DS . 'ServerData' . DS . 'ItemOptionManager.xml');
																								}
																								
																								$xpath = new DOMXPath($ItemOptionManager);
																								$node = $xpath->query("//ItemOptionManager/Section/Item");
																								
																								$this->load->model('shop');
																								$this->load->lib('iteminfo');
																								
																								foreach($itemData AS $buffs){
																									$isBuff = false;
																									if($node->length > 0){
																										$effectType = 0;
																										$effect1 = 0;
																										$effect2 = 0;
																										foreach($node AS $s => $v){
																											if($v->getAttribute('Index') == $buffs['id'] && $v->getAttribute('Cat') == $buffs['cat']){
																												$effectType = $v->parentNode->getAttribute('ID');
																												$effect1 = $v->getAttribute('Option1');
																												$effect2 = $v->getAttribute('Option2');
																												$isBuff = true;
																												break;
																											}
																										}
																										
																										if($isBuff == true){
																											$serial = array_values($this->pluginaizer->Mshop->generate_serial($this->pluginaizer->session->userdata(['user' => 'server'])))[0];
																												
																											$this->vars['reward_buffs'][] = [
																												'serial' => $serial,
																												'expires' => $buffs['expires'],
																												'itemtype' => $this->pluginaizer->iteminfo->itemIndex($buffs['cat'], $buffs['id']),
																												'effect_type' => $effectType,
																												'effect1' => $effect1,
																												'effect2' => $effect2
																											];
																										}
																									}
																								}
																								
																								if(!empty($this->vars['reward_buffs'])){
																									$currTime = time();
																									foreach($this->vars['reward_buffs'] AS $expireData){
																										$this->pluginaizer->Mgift_code->addExpirableItem($this->pluginaizer->session->userdata(['user' => 'id']), $this->pluginaizer->session->userdata(['user' => 'server']), $charData['Name'], $expireData['itemtype'], $expireData['expires'], $expireData['serial'], $currTime, $expireData['effect_type'], $expireData['effect1'], $expireData['effect2'], 1);
																									}
																								}
																								$this->pluginaizer->Mgift_code->logGiftCode($coupon, $character, $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
																								$this->pluginaizer->Mgift_code->setUsesLeft($coupon);
																								$this->pluginaizer->Mgift_code->add_account_log('Received item from gift code: '.$coupon.'', 0, $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
																								$this->vars['success'] = __('Gift code activated successfully.');
																							}
																							else{
																								throw new Exception('Item data is empty');
																							}
																							
																						}
																						catch(Exception $e){
																							$this->vars['error'] = $e->getMessage();
																						}
																					break;
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
										else{
											$this->vars['error'] = __('Invalid gift code for this server.'); 
										}
									}
								}
								else{
									$this->vars['reward_data'] = $this->pluginaizer->Mgift_code->checkCodeWheel($coupon, $this->pluginaizer->session->userdata(['user' => 'server']));
									if($this->vars['reward_data'] != false){
										$this->load->helper('website');
										$this->load->model('application/plugins/wheel_of_fortune/models/wheel_of_fortune');
	
										$this->vars['wheel_rewards'] = $this->config->values('wheel_of_fortune_rewards', $this->pluginaizer->session->userdata(['user' => 'server']));
										if(!empty($this->vars['wheel_rewards']['rewards'])){
											if($this->vars['reward_data']['is_claimed'] == 1){
												$this->vars['error'] = __('Reward already claimed.');
											}
											else{
												if(!isset($this->vars['wheel_rewards']['rewards'][$this->vars['reward_data']['reward_id']])){
													$this->vars['error'] = __('Reward data not found.');
												}
												else{
													$rewardData = $this->vars['wheel_rewards']['rewards'][$this->vars['reward_data']['reward_id']];
													if(!$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->check_connect_stat($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']))){
														$this->vars['error'] = __('Please logout from game.');
													}
													else{
														$this->vars['character'] = $character;
														$this->vars['character_data'] = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->check_char($this->vars['character'], $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), true);
									
														if($this->vars['character_data'] == false){
															$this->vars['error'] = __('Invalid character.');
														}
														else{														
															try{
																if(in_array($rewardData['reward_type'], [1,2,3])){
																	$this->pluginaizer->website->add_credits($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $rewardData['amount'], $rewardData['reward_type'], false, $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->get_guid($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server'])));
																}
																
																$this->vars['table_config'] = $this->pluginaizer->config->values('table_config', $this->pluginaizer->session->userdata(['user' => 'server']));
																
																if($rewardData['reward_type'] == 4){
																	if(!isset($this->vars['table_config']['wcoins']))
																		throw new Exception(__('WCoins configuration not found'));
																	if($this->vars['table_config']['wcoins']['table'] == '')
																		throw new Exception(__('WCoins configuration not found'));
																	$acc = (in_array($this->vars['table_config']['wcoins']['identifier_column'], ['MemberGuid', 'memb_guid'])) ? $this->pluginaizer->session->userdata(['user' => 'id']) : $this->pluginaizer->session->userdata(['user' => 'username']);
																	$this->pluginaizer->Mwheel_of_fortune->add_wcoins($acc, $this->pluginaizer->session->userdata(['user' => 'server']), $rewardData['amount'], $this->vars['table_config']['wcoins']);
																		
																}
																if($rewardData['reward_type'] == 5){
																	if(!isset($this->vars['table_config']['goblinpoint']))
																		throw new Exception(__('GoblinPoint configuration not found'));
																	if($this->vars['table_config']['goblinpoint']['table'] == '')
																		throw new Exception(__('GoblinPoint configuration not found'));
																	$acc = (in_array($this->vars['table_config']['goblinpoint']['identifier_column'], ['MemberGuid', 'memb_guid'])) ? $this->pluginaizer->session->userdata(['user' => 'id']) : $this->pluginaizer->session->userdata(['user' => 'username']);
																	$this->pluginaizer->Mwheel_of_fortune->add_wcoins($acc, $this->pluginaizer->session->userdata(['user' => 'server']), $rewardData['amount'], $this->vars['table_config']['goblinpoint']);
																		
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
																			$serial = array_values($this->pluginaizer->Mshop->generate_serial($this->pluginaizer->session->userdata(['user' => 'server'])))[0];
																			
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
																				foreach($expirableItems AS $expideData){
																					$currTime = time();
																					$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->addExpirableItem($this->pluginaizer->session->userdata(['user' => 'id']), $this->pluginaizer->session->userdata(['user' => 'server']), $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->char_info['Name'], $expideData['index'], $expideData['time'], $expideData['serial'], $currTime, 0, 0, 0, 2);
																				}
																			}
																			
																			$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->updateInventory($this->vars['character'], $this->pluginaizer->session->userdata(['user' => 'server']), $newInv);	
																		}
																	}
																	else{
																		throw new Exception(__('Invalid reward item.'));
																	}
																}
																$this->pluginaizer->Mwheel_of_fortune->set_reward_claimed($this->vars['reward_data']['id'], $this->vars['character_data']['Name'], $this->vars['character'], $this->vars['reward_data']['account'], $this->pluginaizer->session->userdata(['user' => 'server']));
																$this->pluginaizer->Mgift_code->logGiftCode($coupon, $character, $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
																$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->add_account_log('Claimed wheel of fortune reward.', 0, $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
																$this->vars['success'] = __('Gift code activated successfully.');
															}
															catch(Exception $e){
																$this->vars['error'] = $e->getMessage();
															}
														}
													}
												}
											}
										}
										else{
											$this->vars['error'] = __('Invalid gift code.'); 
										}	
									}
									else{
										$this->vars['error'] = __('Invalid gift code.'); 
									}
								}
							}
						}

                    }
                } else{
                    $this->vars['config_not_found'] = __('Plugin configuration not found.');
                }
                //set js
                $this->vars['js'] = $this->config->base_url . 'assets/plugins/js/gift_code.js';
                //load template
                $this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . $this->config->config_entry('main|template') . DS . 'view.gift_code', $this->vars);
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
                $this->load->model('application/plugins/gift_code/models/gift_code');
                $this->vars['is_multi_server'] = $this->pluginaizer->data()->value('is_multi_server');
                $this->vars['plugin_config'] = $this->pluginaizer->plugin_config();
                //load any js, css files if required
                $this->vars['js'] = $this->config->base_url . 'assets/plugins/js/gift_code.js';
                //load template
                $this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . 'admin' . DS . 'view.index', $this->vars);
            } else{
                $this->pluginaizer->redirect($this->config->base_url . 'admincp/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()) . '/admin');
            }
        }

				
		public function generate(){
			if($this->pluginaizer->session->is_admin()){
                //load website helper
                $this->load->helper('website');
				$this->load->helper('webshop');
				$this->load->lib('serverfile');
                $this->load->model('application/plugins/gift_code/models/gift_code');
				$this->load->model('admin');
                $this->vars['is_multi_server'] = $this->pluginaizer->data()->value('is_multi_server');
                $this->vars['plugin_config'] = $this->pluginaizer->plugin_config();
                //load any js, css files if required
                $this->vars['js'] = $this->config->base_url . 'assets/plugins/js/gift_code.js';
				$this->vars['class_list'] = $this->pluginaizer->website->get_char_class(0, false, true);
				
				if(isset($_POST['add_code'])){
					$coupon = isset($_POST['coupon']) ? $_POST['coupon'] : '';
					$server = isset($_POST['server']) ? $_POST['server'] : '';
					$valid = isset($_POST['valid_until']) ? $_POST['valid_until'] : '';
					$max_uses = isset($_POST['max_uses']) ? $_POST['max_uses'] : '';
					$max_uses_by_user = isset($_POST['max_uses_by_user']) ? $_POST['max_uses_by_user'] : 1;
					$max_uses_by_char = isset($_POST['max_uses_by_char']) ? $_POST['max_uses_by_char'] : 0;
					$min_lvl = isset($_POST['min_lvl']) ? $_POST['min_lvl'] : 1;
					$min_mlvl = isset($_POST['min_lvl']) ? $_POST['min_mlvl'] : 0;
					$min_res = isset($_POST['min_res']) ? $_POST['min_res'] : 0;
					$min_gres = isset($_POST['min_gres']) ? $_POST['min_gres'] : 0;
					$char_class = isset($_POST['class']) ? json_encode($_POST['class']) : '';
					$coupon_type = isset($_POST['coupon_type']) ? $_POST['coupon_type'] : 0;
					$reward_amount = isset($_POST['reward_amount']) ? $_POST['reward_amount'] : 0;
					$vip_type = isset($_POST['vip_type']) ? $_POST['vip_type'] : 0;
					$items = [];
					if(isset($_POST['item_category']) && !empty($_POST['item_category']) && $coupon_type == 9){
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
					
					if(isset($_POST['item_hex'])  && !empty($_POST['item_hex']) && $coupon_type == 10){
						//$items = [];
						foreach($_POST['item_hex'] AS $key => $val){
							if($val != ''){
								$items[] = [
									'hex' => $val
								];
							}
						}
					}
					
					if(isset($_POST['item_category_buff']) && !empty($_POST['item_category_buff']) && $coupon_type == 11){
						foreach($_POST['item_category_buff'] AS $key => $val){
							if($val != ''){
								$items[] = [
									'cat' => $_POST['item_category_buff'][$key],
									'id' => $_POST['item_index_buff'][$key],
									'expires' => $_POST['item_expires_buff'][$key],
								];
							}
						}
					}
					
					if($coupon == ''){
						$coupon = strtoupper(substr(base_convert(sha1(uniqid(mt_rand())), 16, 36), 0, 8));
					}
					if($this->pluginaizer->Mgift_code->checkCode($coupon) != false){
						$this->vars['error'] = 'Try different name. This one already exist.';
					}
					else{
						if($server == ''){
							$this->vars['error'] = 'Please select server.';
						}
						else{
							if($valid == '' || strtotime($valid) < time()){
								$this->vars['error'] = 'Please select valid future date.';
							}
							else{
								if($max_uses == '' || $max_uses < 1){
									$this->vars['error'] = 'Please enter max uses.';
								}
								else{
									if($coupon_type == 0){
										$this->vars['error'] = 'Please select coupon type.';
									}
									else{
										if(!in_array($coupon_type, [4,5,9,10]) == 0 && $max_uses_by_user < 1){
											$this->vars['error'] = 'Please enter max uses for account.';
										}
										else{
											if(in_array($coupon_type, [4,5,9,10]) && $max_uses_by_char < 1){
												$this->vars['error'] = 'Please enter max uses for character.';
											}
											else{
												$this->pluginaizer->Mgift_code->createCode($coupon, $server, $valid, $max_uses, $max_uses_by_user, $max_uses_by_char, $min_lvl, $min_mlvl, $min_res, $min_gres, $coupon_type, $reward_amount, $vip_type, $items, $char_class);
												$this->vars['success'] = 'Gift code generated successfully.';
											}
										}
									}
								}
							}
						}
					}
				}
				$this->vars['codes'] = $this->pluginaizer->Mgift_code->listCodes();
                //load template
                $this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . 'admin' . DS . 'view.generate', $this->vars);
            } else{
                $this->pluginaizer->redirect($this->config->base_url . 'admincp/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()) . '/admin');
            }
		}

				
		public function edit($id = ''){
			if($this->pluginaizer->session->is_admin()){
				$this->load->helper('website');
				$this->load->helper('webshop');
				$this->load->lib('serverfile');
                $this->load->model('application/plugins/gift_code/models/gift_code');
				$this->load->model('admin');
                $this->vars['is_multi_server'] = $this->pluginaizer->data()->value('is_multi_server');
                $this->vars['plugin_config'] = $this->pluginaizer->plugin_config();
                //load any js, css files if required
                $this->vars['js'] = $this->config->base_url . 'assets/plugins/js/gift_code.js';
				$this->vars['class_list'] = $this->pluginaizer->website->get_char_class(0, false, true);
				
				if($id == ''){
					$this->vars['error'] = 'Gift code not found.';
				}
				else{
					$this->vars['code_info'] = $this->pluginaizer->Mgift_code->checkCodeById($id);
					if($this->vars['code_info'] != false){
						if(isset($_POST['edit_code'])){
							//$coupon = isset($_POST['coupon']) ? $_POST['coupon'] : '';
							$server = isset($_POST['server']) ? $_POST['server'] : '';
							$valid = isset($_POST['valid_until']) ? $_POST['valid_until'] : '';
							$max_uses = isset($_POST['max_uses']) ? $_POST['max_uses'] : '';
							$uses_left = isset($_POST['uses_left']) ? $_POST['uses_left'] : '';
							$max_uses_by_user = isset($_POST['max_uses_by_user']) ? $_POST['max_uses_by_user'] : 1;
							$max_uses_by_char = isset($_POST['max_uses_by_char']) ? $_POST['max_uses_by_char'] : 0;
							$min_lvl = isset($_POST['min_lvl']) ? $_POST['min_lvl'] : 1;
							$min_mlvl = isset($_POST['min_lvl']) ? $_POST['min_mlvl'] : 0;
							$min_res = isset($_POST['min_res']) ? $_POST['min_res'] : 0;
							$min_gres = isset($_POST['min_gres']) ? $_POST['min_gres'] : 0;
							$char_class = isset($_POST['class']) ? json_encode($_POST['class']) : '';
							$coupon_type = isset($_POST['coupon_type']) ? $_POST['coupon_type'] : 0;
							$reward_amount = isset($_POST['reward_amount']) ? $_POST['reward_amount'] : 0;
							$vip_type = isset($_POST['vip_type']) ? $_POST['vip_type'] : 0;
							$items = [];
							if(isset($_POST['item_category']) && !empty($_POST['item_category']) && $coupon_type == 9){
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
							
							
							if(isset($_POST['item_hex'])  && !empty($_POST['item_hex']) && $coupon_type == 10){
								//$items = [];
								foreach($_POST['item_hex'] AS $key => $val){
									if($val != ''){
										$items[] = [
											'hex' => $val
										];
									}
								}
							}
							
							if(isset($_POST['item_category_buff']) && !empty($_POST['item_category_buff']) && $coupon_type == 11){
								foreach($_POST['item_category_buff'] AS $key => $val){
									if($val != ''){
										$items[] = [
											'cat' => $_POST['item_category_buff'][$key],
											'id' => $_POST['item_index_buff'][$key],
											'expires' => $_POST['item_expires_buff'][$key],
										];
									}
								}
							}
							
							if($server == ''){
								$this->vars['error'] = 'Please select server.';
							}
							else{
								if($valid == '' || strtotime($valid) < time()){
									$this->vars['error'] = 'Please select valid future date.';
								}
								else{
									if($max_uses == '' || $max_uses < 1){
										$this->vars['error'] = 'Please enter max uses.';
									}
									else{
										if($coupon_type == 0){
											$this->vars['error'] = 'Please select coupon type.';
										}
										else{
											if(!in_array($coupon_type, [4,5,9,10]) == 0 && $max_uses_by_user < 1){
												$this->vars['error'] = 'Please enter max uses for account.';
											}
											else{
												if(in_array($coupon_type, [4,5,9,10]) && $max_uses_by_char < 1){
													$this->vars['error'] = 'Please enter max uses for character.';
												}
												else{
													$this->pluginaizer->Mgift_code->updateCode($server, $valid, $max_uses, $uses_left, $max_uses_by_user, $max_uses_by_char, $min_lvl, $min_mlvl, $min_res, $min_gres, $coupon_type, $reward_amount, $vip_type, $items, $char_class, $id);
													$this->vars['code_info'] = $this->pluginaizer->Mgift_code->checkCodeById($id);
													$this->vars['success'] = 'Gift code updated successfully.';
												}
											}
										}
									}
								}
							}
						}
					}
					else{
						$this->vars['error'] = 'Gift code not found.';
					}	 
				}
				$this->vars['codes'] = $this->pluginaizer->Mgift_code->listCodes();
				//load template
                $this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . 'admin' . DS . 'view.edit', $this->vars);
			}
			else{
				 $this->pluginaizer->redirect($this->config->base_url . 'admincp/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()) . '/admin');
			}
		}

				
		public function delete($id = ''){
			if($this->pluginaizer->session->is_admin()){
				$this->load->helper('website');
				$this->load->helper('webshop');
				$this->load->lib('serverfile');
                $this->load->model('application/plugins/gift_code/models/gift_code');
				$this->load->model('admin');
                $this->vars['is_multi_server'] = $this->pluginaizer->data()->value('is_multi_server');
                $this->vars['plugin_config'] = $this->pluginaizer->plugin_config();
                //load any js, css files if required
                $this->vars['js'] = $this->config->base_url . 'assets/plugins/js/gift_code.js';
				
				if($id == ''){
					$this->vars['error'] = 'Gift code not found.';
				}
				else{
					 if($this->pluginaizer->Mgift_code->checkCodeById($id) != false){
						 $this->pluginaizer->Mgift_code->deleteCode($id);
						 $this->vars['success'] = 'Gift code removed.';
					 }
					 else{
						 $this->vars['error'] = 'Gift code not found.';
					 }	 
				}
				$this->vars['codes'] = $this->pluginaizer->Mgift_code->listCodes();
				//load template
                $this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . 'admin' . DS . 'view.generate', $this->vars);
			}
			else{
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
                $this->load->model('application/plugins/gift_code/models/gift_code');
                if(isset($_POST['search_logs'])){
                    $server = (isset($_POST['server']) && $_POST['server'] != 'All') ? $_POST['server'] : 'All';
                    $acc = isset($_POST['account']) ? $_POST['account'] : '';
                    if($acc == ''){
                        $this->vars['error'] = 'Invalid account';
                    } else{
                        $this->vars['logs'] = $this->pluginaizer->Mgift_code->load_logs(1, 25, $acc, $server);
                        $this->pluginaizer->pagination->initialize(1, 25, $this->pluginaizer->Mgift_code->count_total_logs($acc, $server), $this->config->base_url . 'gift-code/logs/%s/' . $acc . '/' . $server . '');
                        $this->vars['pagination'] = $this->pluginaizer->pagination->create_links();
                    }
                } else{
                    $this->vars['logs'] = $this->pluginaizer->Mgift_code->load_logs($page, 25, $acc, $server);
                    $lk = '';
                    if($acc != '')
                        $lk .= '/' . $acc;
                    $lk .= '/' . $server;
                    $this->pluginaizer->pagination->initialize($page, 25, $this->pluginaizer->Mgift_code->count_total_logs($acc, $server), $this->config->base_url . 'gift_code/logs/%s' . $lk);
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
                    'description' => 'Redeem Gift Code' //description which will see user
                ]);
                //create plugin config template
                $this->pluginaizer->create_config(['active' => 0]);
                //add sql scheme if there is any into website database
                //all schemes should be located in plugin_folder/sql_schemes
                $this->pluginaizer->add_sql_scheme('gift_codes');
				$this->pluginaizer->add_sql_scheme('gift_code_logs');
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
                $this->pluginaizer->delete_config()->remove_sql_scheme('gift_codes')->remove_sql_scheme('gift_code_logs')->remove_plugin();
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