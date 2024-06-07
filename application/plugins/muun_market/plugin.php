<?php
    in_file();

    class _plugin_muun_market extends controller implements pluginInterface
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
						$this->load->lib("iteminfo");
                        $this->load->model('application/plugins/muun_market/models/muun_market');
                        $this->pluginaizer->Mmuun_market->count_total_muuns($this->pluginaizer->session->userdata(['user' => 'server']));
                        $this->pluginaizer->pagination->initialize($page, $this->vars['plugin_config']['muuns_per_page'], $this->pluginaizer->Mmuun_market->total, $this->config->base_url . str_replace('_', '-', $this->pluginaizer->get_plugin_class()) . '/index/%s');
                        $this->vars['items'] = $this->pluginaizer->Mmuun_market->load_market($page, $this->vars['plugin_config']['muuns_per_page'], $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['plugin_config']['sale_tax']);
                        $this->vars['pagination'] = $this->pluginaizer->pagination->create_links();
					}
                } else{
                    $this->vars['config_not_found'] = __('Plugin configuration not found.');
                }
                //set js
                $this->vars['js'] = $this->config->base_url . 'assets/plugins/js/muun_market.js';
                //load template
                $this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . $this->config->config_entry('main|template') . DS . 'view.muun_market', $this->vars);
            } else{
                $this->pluginaizer->redirect($this->config->base_url . 'account-panel/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()));
            }
        }

		public function sell_muun(){
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
						$this->load->lib("itemimage");
						$this->load->lib("iteminfo");
						$this->iteminfo->isMuun(true);
                        $this->load->model('application/plugins/muun_market/models/muun_market');
                        $this->vars['char_list'] = $this->pluginaizer->Mmuun_market->load_char_list($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
					}	
                } else{
                    $this->vars['config_not_found'] = __('Plugin configuration not found.');
                }
                //set js
                $this->vars['js'] = $this->config->base_url . 'assets/plugins/js/muun_market.js';
                //load template
                $this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . $this->config->config_entry('main|template') . DS . 'view.sell_muun', $this->vars);
            } else{
                $this->pluginaizer->redirect($this->config->base_url . 'account-panel/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()) . '/muun-market');
            }
        }
		
		
		public function do_sell_muun(){
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
						exit;
                    } else{
						$this->load->model('application/plugins/muun_market/models/muun_market');
						if(count($_POST) > 0){
							$slot = (isset($_POST['slot']) ? ctype_digit($_POST['slot']) ? $_POST['slot'] : '' : '');
							$mcharacter = trim($_POST['char']);
							$time = (isset($_POST['time']) ? ctype_digit($_POST['time']) ? $_POST['time'] : '' : '');
							$payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : '';
							$price = (isset($_POST['price']) ? ctype_digit($_POST['price']) ? $_POST['price'] : '' : '');
							$hex = trim($_POST['hex']);
							if(!$this->pluginaizer->Mmuun_market->check_connect_stat($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']))){
								echo $this->pluginaizer->jsone(['error' =>__('Please logout from game.')]);
								exit;
							}
							else{
								if($price == ''){
									echo $this->pluginaizer->jsone(['error' => __('Please enter price.')]);
									exit;
								}	
								else{
									$price_min = ($payment_method == 3) ? $this->vars['plugin_config']['sale_price_minimum_zen'] : $this->vars['plugin_config']['sale_price_minimum_credits'];
									if($price < $price_min){
										echo $this->pluginaizer->jsone(['error' => vsprintf(__('Minimum price can be %d'), [$price_min])]);
									}
									else{
										$price_max = ($payment_method == 3) ? $this->vars['plugin_config']['sale_price_maximum_zen'] : $this->vars['plugin_config']['sale_price_maximum_credits'];
										if($price > $price_max){
											echo $this->pluginaizer->jsone(['error' => vsprintf(__('Maximum price can be %d'), [$price_max])]);
										}
										else{
											if($payment_method == ''){
												echo $this->pluginaizer->jsone(['error' => __('Please select payment method.')]);
												exit;
											}
											else{
												if($time == '' || !in_array($time, [1, 2, 3, 4, 5, 7, 14])){
													echo $this->pluginaizer->jsone(['error' => __('Please select time.')]);
													exit;
												}
												else{
													$char = $this->pluginaizer->Mmuun_market->check_char($mcharacter, $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
													 if(!$char){
														echo $this->pluginaizer->jsone(['error' => __('Character not found.')]); 
														exit;
													 }
													 else{
														$checkMuun = $this->pluginaizer->Mmuun_market->check_muun($char['Name'], $this->pluginaizer->session->userdata(['user' => 'server']), $hex);
														if($checkMuun === false){
															echo $this->pluginaizer->jsone(['error' => __('Muun not found')]); 
															exit;
														}
														else{
															$this->load->lib("iteminfo");
															$this->iteminfo->isMuun(true);
															$this->iteminfo->itemData($hex, true, $this->pluginaizer->session->userdata(['user' => 'server']));
															
															if(isset($this->vars['plugin_config']['blacklist_items'])){
																if($this->black_list($this->vars['plugin_config']['blacklist_items'], $this->iteminfo->type, $this->iteminfo->id)){
																	echo $this->pluginaizer->jsone(['error' => __('Muun not allowed for sell.')]); 
																	exit;
																}
															}
															
															$this->pluginaizer->Mmuun_market->generate_new_item_by_slot($checkMuun);
															$this->pluginaizer->Mmuun_market->update_muun_inventory($char['Name'], $this->pluginaizer->session->userdata(['user' => 'server']));
															$this->pluginaizer->Mmuun_market->update_muun_period(hexdec(substr($hex, 32, 8)), $this->pluginaizer->session->userdata(['user' => 'server']), hexdec(substr($hex, 32, 8)));
															$this->pluginaizer->Mmuun_market->update_muun_condition(hexdec(substr($hex, 32, 8)), $this->pluginaizer->session->userdata(['user' => 'server']), $checkMuun);
															$this->pluginaizer->Mmuun_market->add_muun_into_market($hex, $char['Name'], $time, $payment_method, $price, $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
															$this->pluginaizer->Mmuun_market->add_account_log('Added muun into market', 0, $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
															echo $this->pluginaizer->jsone(['success' => __('Muun added into market.')]); 
															exit;
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
                    echo $this->pluginaizer->jsone(['error' => __('Plugin configuration not found.')]);
					exit;
                }
            } else{
               echo $this->pluginaizer->jsone(['error' => 'Please login.']);
			   exit;
            }
		}

				
		private function black_list($blacklist = '', $cat, $id){
			if($blacklist != ''){
				if(str_contains($blacklist, ',')){
					$blist = explode(',', $blacklist);
					foreach($blist AS $key => $val){
						$itemList = explode('#', $val);
						if($cat == $itemList[0] && $id == $itemList[1]){
							return true;
						}
					}
				}
				else{
					$itemList = explode('#', $blacklist);
					if($cat == $itemList[0] && $id == $itemList[1]){
						return true;
					}
				}
			}
			return false;
		}
		
		public function buy($id = ''){
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
						$this->load->lib("itemimage");
						$this->load->lib("iteminfo");
						$this->iteminfo->isMuun(true);
						$this->load->model('application/plugins/muun_market/models/muun_market');
						if(!$this->pluginaizer->Mmuun_market->check_connect_stat($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']))){
							$this->vars['error'] = __('Please logout from game.');
						}
						else{
							
							$this->pluginaizer->website->db('web')->beginTransaction();
							if($this->vars['sale_info'] = $this->pluginaizer->Mmuun_market->check_sale_in_market($id, $this->pluginaizer->session->userdata(['user' => 'server']))){
								$this->vars['char_list'] = $this->pluginaizer->Mmuun_market->load_char_list_for_select($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
								$this->iteminfo->itemData($this->vars['sale_info']['item'], true, $this->pluginaizer->session->userdata(['user' => 'server']));
								if($this->vars['sale_info']['sold'] == 1)
									$this->vars['error'] = __('This sale has been sold already.'); 
								else{
									if($this->vars['sale_info']['removed'] == 1)
										$this->vars['error'] = __('This sale has been removed.'); 
									else{
										if(strtotime($this->vars['sale_info']['active_till']) < time())
											$this->vars['error'] = __('This sale has expire.');
										else{
											if(isset($_POST['buy'])){
												$char = trim($_POST['character']);
												if($this->vars['sale_info']['seller_acc'] == $this->pluginaizer->session->userdata(['user' => 'username']))
													$this->vars['purchase_error'] = __('You can not purchase own sale.'); 
												else{
													$char = $this->pluginaizer->Mmuun_market->check_char($char, $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
													if(!$char){
														$this->vars['purchase_error'] = __('Character not found.'); 
													}
													else{
														$this->status = $this->pluginaizer->website->get_user_credits_balance($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['sale_info']['price_type'], $this->pluginaizer->session->userdata(['user' => 'id']));
														$this->price_with_tax = round($this->vars['sale_info']['price'] + ($this->vars['sale_info']['price'] / 100) * $this->vars['plugin_config']['sale_tax'], 0);
														if($this->status['credits'] < $this->price_with_tax){
															$this->vars['purchase_error'] = sprintf(__('You have insufficient amount of %s'), $this->pluginaizer->website->translate_credits($this->vars['sale_info']['price_type'], $this->pluginaizer->session->userdata(['user' => 'server'])));
														}
														$slot = $this->pluginaizer->Mmuun_market->find_free_slot($char['Name'], $this->pluginaizer->session->userdata(['user' => 'server']));
														if($slot === false){
															$this->vars['purchase_error'] = __('No slots in muun inventory.'); 
														}
														if(!isset($this->vars['purchase_error'])){
															$this->pluginaizer->Mmuun_market->generate_new_item_by_slot($slot, $this->vars['sale_info']['item']);
															$this->pluginaizer->Mmuun_market->update_muun_inventory($char['Name'], $this->pluginaizer->session->userdata(['user' => 'server']));
															$this->pluginaizer->Mmuun_market->update_muun_period($char['Name'], $this->pluginaizer->session->userdata(['user' => 'server']), hexdec(substr($this->vars['sale_info']['item'], 32, 8)));
															$this->pluginaizer->Mmuun_market->update_muun_condition($char['Name'], $this->pluginaizer->session->userdata(['user' => 'server']), $slot, hexdec(substr($this->vars['sale_info']['item'], 32, 8)));
															$this->pluginaizer->website->charge_credits($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), $this->price_with_tax, $this->vars['sale_info']['price_type'], $this->pluginaizer->session->userdata(['user' => 'id']));
															$this->pluginaizer->website->add_credits($this->vars['sale_info']['seller_acc'], $this->vars['sale_info']['server'], round($this->vars['sale_info']['price'], 0), $this->vars['sale_info']['price_type'], false, $this->pluginaizer->Mmuun_market->get_guid($this->vars['sale_info']['seller_acc'], $this->vars['sale_info']['server']));
															$this->pluginaizer->Mmuun_market->add_account_log('Bought Muun For ' . $this->pluginaizer->website->translate_credits($this->vars['sale_info']['price_type'], $this->pluginaizer->session->userdata(['user' => 'server'])), -$this->price_with_tax, $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
															$this->pluginaizer->Mmuun_market->add_account_log('Sold Muun For ' . $this->pluginaizer->website->translate_credits($this->vars['sale_info']['price_type'], $this->pluginaizer->session->userdata(['user' => 'server'])), $this->vars['sale_info']['price'], $this->vars['sale_info']['seller_acc'], $this->vars['sale_info']['server']);
															$this->pluginaizer->Mmuun_market->update_sale_set_purchased($id, $char['Name'], $this->pluginaizer->session->userdata(['user' => 'username']));
															$this->pluginaizer->website->db('web')->commit();
															header('Location: ' . $this->config->base_url . 'muun-market/success');
													   }
													}
												}	
											}
										}
									}
								}
							} else{
								$this->vars['error'] = __('Sale not found.');
							}
						}
                    }
                } else{
                    $this->vars['config_not_found'] = __('Plugin configuration not found.');
                }
                //load template
                $this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . $this->config->config_entry('main|template') . DS . 'view.buy', $this->vars);
            } else{
                $this->pluginaizer->redirect($this->config->base_url . 'account-panel/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()) . '/buy/' . $id);
            }
        }

		public function sale_history($page = 1){
            //check if visitor has user privilleges
            if($this->pluginaizer->session->is_user()){
                //load website helper
                $this->load->helper('website');
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
						$this->load->lib("iteminfo");
                        $this->load->model('application/plugins/muun_market/models/muun_market');
						$this->pluginaizer->Mmuun_market->count_total_history_items();
                        $this->pluginaizer->pagination->initialize($page, $this->vars['plugin_config']['muuns_per_page'], $this->pluginaizer->Mmuun_market->total, $this->config->base_url . str_replace('_', '-', $this->pluginaizer->get_plugin_class()) . '/sale-history/%s');
                        $this->vars['items'] = $this->pluginaizer->Mmuun_market->load_market_history($page, $this->vars['plugin_config']['muuns_per_page'], $this->vars['plugin_config']['sale_tax']);
                        $this->vars['pagination'] = $this->pluginaizer->pagination->create_links();
                    }
                } else{
                    $this->vars['config_not_found'] = __('Plugin configuration not found.');
                }
                //load template
                $this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . $this->config->config_entry('main|template') . DS . 'view.history', $this->vars);
            } else{
                $this->pluginaizer->redirect($this->config->base_url . 'account-panel/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()) . '/sale-history');
            }
        }

		public function remove($id){
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
                        $this->load->model('application/plugins/muun_market/models/muun_market');
						
						if(!$this->pluginaizer->Mmuun_market->check_connect_stat($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']))){
							$this->vars['error'] = __('Please logout from game.');
						}
						else{
							$this->pluginaizer->website->db('web')->beginTransaction();
							if($this->vars['sale_info'] = $this->pluginaizer->Mmuun_market->check_sale_in_market($id, $this->pluginaizer->session->userdata(['user' => 'server']))){
								if($this->vars['sale_info']['sold'] == 1)
									$this->vars['error'] = __('This item has been sold already.'); 
								else{
									if($this->vars['sale_info']['removed'] == 1)
										$this->vars['error'] = __('This item has been removed.'); 
									else{
										if($this->vars['sale_info']['seller_acc'] != $this->pluginaizer->session->userdata(['user' => 'username']))
											$this->vars['error'] = __('This sale is not yours.'); 
										else{
											if($this->vars['plugin_config']['allow_remove_before_expires'] == 0 && strtotime($this->vars['sale_info']['active_till']) > time())
												$this->vars['error'] = __('Your not allowed to remove this item while it is not expired.'); 
											else{
												$char = $this->pluginaizer->Mmuun_market->check_char($this->vars['sale_info']['seller'], $this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']), false);
												 if(!$char){
													$this->vars['error'] = __('Character not found.'); 
												 }
												 else{
													$slot = $this->pluginaizer->Mmuun_market->find_free_slot($this->vars['sale_info']['seller'], $this->pluginaizer->session->userdata(['user' => 'server']));
													if($slot === false){
														$this->vars['error'] = __('No slots in muun inventory.'); 
													}
													else{
														$this->pluginaizer->Mmuun_market->generate_new_item_by_slot($slot, $this->vars['sale_info']['item']);
														$this->pluginaizer->Mmuun_market->update_muun_inventory($this->vars['sale_info']['seller'], $this->pluginaizer->session->userdata(['user' => 'server']));
														$this->pluginaizer->Mmuun_market->update_sale_set_removed($id, $this->pluginaizer->session->userdata(['user' => 'username']));
														$this->pluginaizer->website->db('web')->commit();		
														$this->vars['success'] = __('Sale successfully restored.');
													}
												 }
											}
										}
									}
								}
							} else{
								$this->vars['error'] = __('Sale not found.');
							}
						}
                    }
                } else{
                    $this->vars['config_not_found'] = __('Plugin configuration not found.');
                }
                //load template
                $this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . $this->config->config_entry('main|template') . DS . 'view.remove', $this->vars);
            } else{
                $this->pluginaizer->redirect($this->config->base_url . 'account-panel/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()) . '/sale-history');
            }
        }

		public function success(){
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
                        $this->vars['success'] = __('Purchase has been successfull.');
                    }
                } else{
                    $this->vars['config_not_found'] = __('Plugin configuration not found.');
                }
                //load template
                $this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . $this->config->config_entry('main|template') . DS . 'view.success', $this->vars);
            } else{
                $this->pluginaizer->redirect($this->config->base_url . 'account-panel/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()) . '/success');
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
                $this->load->model('application/plugins/muun_market/models/muun_market');
                $this->vars['is_multi_server'] = $this->pluginaizer->data()->value('is_multi_server');
                $this->vars['plugin_config'] = $this->pluginaizer->plugin_config();
                //load any js, css files if required
                $this->vars['js'] = $this->config->base_url . 'assets/plugins/js/muun_market.js';
                //load template
                $this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . 'admin' . DS . 'view.index', $this->vars);
            } else{
                $this->pluginaizer->redirect($this->config->base_url . 'admincp/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()) . '/admin');
            }
        }
		
		/**
         *
         * Generate coinbase logs
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
				$this->load->lib("iteminfo");
                $this->load->model('application/plugins/muun_market/models/muun_market');
                if(isset($_POST['search_muun_market'])){
                    $server = (isset($_POST['server']) && $_POST['server'] != 'All') ? $_POST['server'] : 'All';
                    $acc = isset($_POST['account']) ? $_POST['account'] : '';
                    if($acc == ''){
                        $this->vars['error'] = 'Invalid account';
                    } else{
                        $this->vars['logs'] = $this->pluginaizer->Mmuun_market->load_logs(1, 25, $acc, $server);
                        $this->pluginaizer->pagination->initialize(1, 25, $this->pluginaizer->Mmuun_market->count_total_logs($acc, $server), $this->config->base_url . 'muun-market/logs/%s/' . $acc . '/' . $server . '');
                        $this->vars['pagination'] = $this->pluginaizer->pagination->create_links();
                    }
                } else{
                    $this->vars['logs'] = $this->pluginaizer->Mmuun_market->load_logs($page, 25, $acc, $server);
                    $lk = '';
                    if($acc != '')
                        $lk .= '/' . $acc;
                    $lk .= '/' . $server;
                    $this->pluginaizer->pagination->initialize($page, 25, $this->pluginaizer->Mmuun_market->count_total_logs($acc, $server), $this->config->base_url . 'muun-market/logs/%s' . $lk);
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
                            if($key == 'credits_sell_currencies' || $key == 'zen_sell_currencies'){
								$this->vars['plugin_config'][$_POST['server']][$key] = implode(',', $val);
							}
							else{
								$this->vars['plugin_config'][$_POST['server']][$key] = $val;
							}
                        }
                    }
                } else{
                    foreach($_POST AS $key => $val){
                        if($key != 'server'){
                           if($key == 'credits_sell_currencies' || $key == 'zen_sell_currencies'){
								$this->vars['plugin_config'][$key] = implode(',', $val);
							}
							else{
								$this->vars['plugin_config'][$key] = $val;
							}
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
                    'description' => 'Sell & Buy Muuns' //description which will see user
                ]);
                //create plugin config template
                $this->pluginaizer->create_config(['active' => 0, 'muuns_per_page' => 25, 'sale_tax' => 0, 'sale_price_minimum_zen' => 50000, 'sale_price_minimum_credits' => 500, 'sale_price_maximum_zen' => 50000000, 'sale_price_maximum_credits' => 50000, 'allow_remove_before_expires' => 0, 'blacklist_items' => '']);
                //add sql scheme if there is any into website database
                //all schemes should be located in plugin_folder/sql_schemes
                $this->pluginaizer->add_sql_scheme('muun_market');
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
                $this->pluginaizer->delete_config()->remove_sql_scheme('muun_market')->remove_plugin();
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