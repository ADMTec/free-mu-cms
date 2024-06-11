<?php
    in_file();

    class shop extends controller
    {
        protected $vars = [], $errors = [];
        private $payment_method = 0;
        private $item_info = false;
        private $level = 0;
        private $option = 0;
        private $luck = 0;
        private $skill = 0;
        private $ancient = 0;
        private $exe = [];
        private $harmony = [];
        private $ref = 0;
        private $fenrir = 0;
        private $sockets = [];
        private $socket_count = 0;
        private $seeds = [];
        private $socket_seed = [];
        private $socket_info = false;
        private $price = 0;
        private $serial, $serial2;
        private $item_hex;
		private $pentagram_ids = [200, 201, 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 217, 306, 307, 308];
        private $errtel_ids = [221, 231, 241, 251, 261];
        private $element_type;
        private $element_rank_1;
        private $element_rank_2;
        private $element_rank_3;
        private $element_rank_4;
        private $element_rank_5;
        private $rank_1_lvl;
        private $rank_2_lvl;
        private $rank_3_lvl;
        private $rank_4_lvl;
        private $rank_5_lvl;
        private $wing_main_element;
        private $wing_main_element_lvl;
        private $wing_additional_element;
        private $wing_additional_element_lvl;
        private $wing_additional2_element;
        private $wing_additional2_element_lvl;
        private $wing_additional3_element;
        private $wing_additional3_element_lvl;
        private $wing_additional4_element;
        private $wing_additional4_element_lvl;
        private $wing_additional5_element;
        private $wing_additional5_element_lvl;
		private $mastery_bonus_opt;
		
		public function __construct(){
            parent::__construct();
            $this->load->helper('website');
            $this->load->lib('session', ['DmNCMS']);
			$this->session->checkSession();
			 $this->load->lib('csrf');						 
            $this->load->helper('breadcrumbs', [$this->request]);
            $this->load->helper('meta');
            $this->load->helper('webshop');
            if($this->session->userdata(['user' => 'server'])){
                $this->load->lib("createitem", [MU_VERSION, SOCKET_LIBRARY, $this->website->get_value_from_server($this->session->userdata(['user' => 'server']), 'item_size')]);
            }
            $this->load->lib("pagination");
            $this->load->lib("itemimage");
            $this->load->lib("iteminfo");															 
            $this->load->model('shop');
        }

		public function index($page = 1){
            if($this->session->userdata(['user' => 'logged_in'])){
                if(!$this->website->module_disabled('shop_' . $this->session->userdata(['user' => 'server']))){
                    $this->vars['items'] = $this->Mshop->load_items($this->session->userdata(['user' => 'server']), $page, $this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|item_per_page'), $this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|columns'));
                    $this->vars['total_columns'] = $this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|columns');
                    $this->pagination->initialize($page, $this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|item_per_page'), $this->Mshop->count_items, $this->config->base_url . 'shop/index/%s');
                    $this->vars['pagination'] = $this->pagination->create_links();
                    $this->load->view($this->config->config_entry('main|template') . DS . 'shop' . DS . 'view.items', $this->vars);
                }
            } else{
                $this->login();
            }
        }
		
				
		public function show_sub_cats(){
			if(!$this->website->module_disabled('shop_'.$this->session->userdata(array('user' => 'server')))){
				if($this->session->userdata(array('user'=>'logged_in'))){
					if(isset($_POST['id'])){
						$config = $this->config->values('shopplaydeon_config', $this->session->userdata(['user' => 'server']));
						if(array_key_exists($_POST['id'], $config['super_cats'])){
							$subCats = $config['super_cats'][$_POST['id']]['Subcats'];
							$html = '';
							$i = 0;
							$first = '';
							foreach($subCats AS $subCat){
								if(isset($config['sub_cats'][$subCat])){
									if($i == 0){
										$first = implode(',', $config['sub_cats'][$subCat]['sub_sub_cats']);
									}
									$html .= '<a class="mirror non" onclick="App.showSubSubCats(\''.implode(',', $config['sub_cats'][$subCat]['sub_sub_cats']).'\')">'.$config['sub_cats'][$subCat]['Name'].'</a>';
									$i++;
								}
								
							}
							if($first != ''){
								$html .= '<script type="text/javascript">
											 $(document).ready(function () {
												 App.showSubSubCats(\''.$first.'\');
											 });
										</script>';
							}
							if($html != ''){
								header('Content-Type: application/json');
								die(json_encode($html, JSON_HEX_QUOT | JSON_HEX_TAG));
							}
							else{
								json(array('error' => __('No Sets Found')));
							}
						}
						else{
							json(array('error' => __('No Sets Found')));
						}
					}
					else{
						json(array('error' => __('Invalid category')));
					}
				}
				else{
					json(array('error' => __('Please Login')));
				}
			}
		}

				
		public function show_sub_sub_cats(){
			if(!$this->website->module_disabled('shop_'.$this->session->userdata(array('user' => 'server')))){
				if($this->session->userdata(array('user'=>'logged_in'))){
					if(isset($_POST['id'])){
						$config = $this->config->values('shopplaydeon_config', $this->session->userdata(['user' => 'server']));
						if(strpos($_POST['id'], ',') !== false){
							$ids = explode(',', $_POST['id']);
						}
						else{
							$ids = [$_POST['id']];
						}
						$html = '';
						$first = '';
						$i = 0;
						foreach($ids AS $subId){
							if(isset($config['sub_sub_cats'][$subId])){
								if(isset($config['sub_sub_cats'][$subId]['SetIds']) && !empty($config['sub_sub_cats'][$subId]['SetIds'])){
									if($i == 0){
										$first .= '<script type="text/javascript">
											 $(document).ready(function () {
												App.showSetItems(\''.implode(',', $config['sub_sub_cats'][$subId]['SetIds']).'\');
											 });
										</script>';
									}
									$i++;
									$html .= '<div class="filter-left"><div class="smithy-select"><p class="sel-value"><a onclick="App.showSetItems(\''.implode(',', $config['sub_sub_cats'][$subId]['SetIds']).'\');">'.$config['sub_sub_cats'][$subId]['Name'].'</a></p></div></div>';
								}
								else{
									if($i == 0){
										$first .= '<script type="text/javascript">
											 $(document).ready(function () {
												App.showItems(\''.$config['sub_sub_cats'][$subId]['Items'].'\', 0);
											 });
										</script>';
									}
									$i++;
									$html .= '<div class="filter-left"><div class="smithy-select"><p class="sel-value"><a onclick="App.showItems(\''.$config['sub_sub_cats'][$subId]['Items'].'\', 0);">'.$config['sub_sub_cats'][$subId]['Name'].'</a></p></div></div>';
								}
								
							}
						}
						if($first != ''){
							$html .= $first;
						}
						header('Content-Type: application/json');
						die(json_encode(['html' => $html], JSON_HEX_QUOT | JSON_HEX_TAG));
					}
					else{
						json(array('error' => __('Invalid category')));
					}
				}
				else{
					json(array('error' => __('Please Login')));
				}
			}
		}

		
		public function show_items(){
			if(!$this->website->module_disabled('shop_'.$this->session->userdata(array('user' => 'server')))){
				if($this->session->userdata(array('user'=>'logged_in'))){
					if(isset($_POST['ids'])){
						$items = $this->Mshop->get_items($_POST['ids']);
						if($items){
							if($_POST['type'] == 1){
								$html = '<div class="smithy-list" style="display: block;">';
							}
							else{
								$html = '<div class="smithy-select-list" style="display: block;">';
							}
							foreach($items as $item){
								$html .= '<a id="shop_item_title_'.$item['id'].'" data-name="'.$item['name'].'" class="non">'.$item['name'].'&nbsp;&nbsp;&nbsp;</a>';
							}
							$html .= '</div>';
							json($html);
						}
						else{
							json(array('error' => _('No Items Found')));
						}
					}
					else{
						json(array('error' => _('No Items Found')));
					}
				}
				else{
					json(array('error' => _('Please Login')));
				}
			}
		}
		
		public function show_set_items(){
			if(!$this->website->module_disabled('shop_'.$this->session->userdata(array('user' => 'server')))){
				if($this->session->userdata(array('user'=>'logged_in'))){
					if(isset($_POST['ids'])){
						$config = $this->config->values('shopplaydeon_config', $this->session->userdata(['user' => 'server']));
						if(strpos($_POST['ids'], ',') !== false){
							$ids = explode(',', $_POST['ids']);
						}
						else{
							$ids = [$_POST['ids']];
						}
						//print_r($ids);
						$html = '<div class="smithy-select-list" id="swords-list" style="display: block;">';
						foreach($ids AS $setId){
							if(isset($config['sets'][$setId])){
								$html .= '<a onclick="App.showItems(\''.$config['sets'][$setId]['Items'].'\', 1);" class="non">'.$config['sets'][$setId]['Name'].'&nbsp;&nbsp;&nbsp;</a>';
							}
						}
						$html .= '</div>';
						json($html);
						//$items = $this->Mshop->get_items($_POST['ids']);
						/*if($items){
							$html = '<div class="smithy-select-list" id="swords-list" style="display: block;">';
							foreach($items as $item){
								$html .= '<a id="shop_item_title_'.$item['id'].'" href="" data-name="'.$item['name'].'" class="non">'.$item['name'].'&nbsp;&nbsp;&nbsp;</a>';
							}
							$html .= '</div>';
							json($html);
						}
						else{
							json(array('error' => _('No Items Found')));
						}*/
					}
					else{
						json(array('error' => _('No Items Found')));
					}
				}
				else{
					json(array('error' => _('Please Login')));
				}
			}
		}

	
		
		public function show_cat_items(){
			if(!$this->website->module_disabled('shop_'.$this->session->userdata(array('user' => 'server')))){
				if($this->session->userdata(array('user'=>'logged_in'))){
					$items = $this->Mshop->get_items_from_cat($_POST['cat']);
					if($items){
						$html = '';
						foreach($items as $item){
							$html .= '<a id="shop_item_title_'.$item['id'].'" href="" data-name="'.$item['name'].'" data-info="<img src=\''.$item['image'].'\'>"><span>'.$item['name'].'</span></a>';
						}
						json($html);
					}
					else{
						json(array('error' => _('No Items Found')));
					}
				}
				else{
					json(array('error' => _('Please Login')));
				}
			}
		}

		public function category($cat = '', $page = 1){
            if($this->session->userdata(['user' => 'logged_in'])){
                if(!$this->website->module_disabled('shop_' . $this->session->userdata(['user' => 'server']))){
                    $category = $this->webshop->category_to_id($cat);
                    if($category === false){
                        $this->vars['error'] = __('Invalid Category');
                    } else{
                        $this->vars['items'] = $this->Mshop->load_items($this->session->userdata(['user' => 'server']), $page, $this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|item_per_page'), $this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|columns'), $category);
                        $this->vars['total_columns'] = $this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|columns');
                        $this->pagination->initialize($page, $this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|item_per_page'), $this->Mshop->count_items, $this->config->base_url . 'shop/category/' . $cat . '/%s');
                        $this->vars['pagination'] = $this->pagination->create_links();
                    }
                    $this->load->view($this->config->config_entry('main|template') . DS . 'shop' . DS . 'view.items', $this->vars);
                }
            } else{
                $this->login();
            }
        }

		public function get_item_data(){
            if($this->session->userdata(['user' => 'logged_in'])){
                if(!$this->website->module_disabled('shop_' . $this->session->userdata(['user' => 'server']))){
                    if(isset($_POST['id'])){
                        if($item = $this->Mshop->get_item_info($this->session->userdata(['user' => 'server']), $_POST['id'])){
                            if($item == 'disabled'){
                                json(['error' => __('This item is disabled on this server.')]);
                            } else{
                                json([
									'item' => $item, 
									'base_url' => $this->config->base_url, 
									'template' => $this->config->config_entry('main|template'), 
									'config' => $this->config->load_all_xml_config('shop_' . $this->session->userdata(['user' => 'server'])), 
									'credits' => $this->config->config_entry('credits_' . $this->session->userdata(['user' => 'server']) . '|title_1'), 
									'g_credits' => $this->config->config_entry('credits_' . $this->session->userdata(['user' => 'server']) . '|title_2'), 
									'is_vip' => (int)$this->session->userdata('vip'), 
									'vip_discount' => $this->session->userdata('vip') ? $this->session->userdata(['vip' => 'shop_discount']) : 0,
									'mu_version' => (defined('MU_VERSION')) ? MU_VERSION : 5
								]);
                            }
                        } else{
                            json(['error' => __('This item doen\'t exist in our database.')]);
                        }
                    } else{
                        json(['error' => __('Invalid Items.')]);
                    }
                }
            }
        }

		public function senditem($id = -1, $type = 'direct'){
            if(is_ajax()){
                if($this->session->userdata(['user' => 'logged_in'])){
                    if($this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|module_status') == 1){
                        $this->load->model('account');
                        $this->item_info = $this->Mshop->get_item_info($this->session->userdata(['user' => 'server']), $id);
                        if(!$this->item_info)
                            $this->errors[] = __('Invalid item.');
                        if(!$this->item_info == 'disabled')
                            $this->errors[] = __('This item is disabled on this server.');
                        if(count($this->errors) > 0){
                            if(count($this->errors) == 1)
                                json(['error' => $this->errors[0]]); 
							else
                                json(['error' => $this->errors]);
                        } else{
                            $this->payment_method = isset($_POST['payment_method']) ? ctype_digit($_POST['payment_method']) ? (int)$_POST['payment_method'] : 0 : 0;
                            $this->level = isset($_POST['item_level']) ? ctype_digit($_POST['item_level']) ? (int)$_POST['item_level'] : 0 : 0;
                            $this->option = isset($_POST['item_opt']) ? ctype_digit($_POST['item_opt']) ? (int)$_POST['item_opt'] : 0 : 0;
                            $this->luck = (isset($_POST['item_luck']) && $_POST['item_luck'] == 1) ? true : false;
                            $this->skill = (isset($_POST['item_skill']) && $_POST['item_skill'] == 1) ? true : false;$this->ancient = (isset($_POST['item_anc']) && $_POST['item_anc'] > 0) ? ctype_digit($_POST['item_anc']) ? (int)$_POST['item_anc'] : 0 : 0;
                            $this->exe = (!empty($_POST['exe']) && count($_POST['exe']) > 0) ? $_POST['exe'] : [];
                            if($this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|use_harmony') == 1){
                                $this->harmony = (isset($_POST['item_harm']) && isset($_POST['harmonyvalue'])) ? [$_POST['item_harm'], $_POST['harmonyvalue']] : [];
                            } else{
                                $this->harmony = [];
                            }
                            $this->ref = (isset($_POST['item_ref']) && $_POST['item_ref'] == 1) ? true : false;
                            $this->fenrir = (isset($_POST['fenrir']) && $_POST['fenrir'] != 0) ? ctype_digit($_POST['fenrir']) ? (int)$_POST['fenrir'] : 0 : 0;
							
							$this->mastery_bonus_opt = isset($_POST['item_mastery_bonus'])  ? ctype_digit($_POST['item_mastery_bonus']) ? (int)$_POST['item_mastery_bonus'] : 0 : 0;
							
                            if($this->item_info['original_item_cat'] == 12 && in_array($this->item_info['item_id'], [221, 231, 241, 251, 261])){
                                $this->element_type = isset($_POST['element_type']) ? ctype_digit($_POST['element_type']) ? (int)$_POST['element_type'] : 0 : 0;
                                $this->element_rank_1 = isset($_POST['element_rank_1']) ? ctype_digit($_POST['element_rank_1']) ? (int)$_POST['element_rank_1'] : 0 : 0;
                                $this->element_rank_2 = isset($_POST['element_rank_2']) ? ctype_digit($_POST['element_rank_2']) ? (int)$_POST['element_rank_2'] : 0 : 0;
                                $this->element_rank_3 = isset($_POST['element_rank_3']) ? ctype_digit($_POST['element_rank_3']) ? (int)$_POST['element_rank_3'] : 0 : 0;
                                $this->element_rank_4 = isset($_POST['element_rank_4']) ? ctype_digit($_POST['element_rank_4']) ? (int)$_POST['element_rank_4'] : 0 : 0;
                                $this->element_rank_5 = isset($_POST['element_rank_5']) ? ctype_digit($_POST['element_rank_5']) ? (int)$_POST['element_rank_5'] : 0 : 0;
                                $this->rank_1_lvl = isset($_POST['rank_1_lvl']) ? ctype_digit($_POST['rank_1_lvl']) ? (int)$_POST['rank_1_lvl'] : 0 : 0;
                                $this->rank_2_lvl = isset($_POST['rank_2_lvl']) ? ctype_digit($_POST['rank_2_lvl']) ? (int)$_POST['rank_2_lvl'] : 0 : 0;
                                $this->rank_3_lvl = isset($_POST['rank_3_lvl']) ? ctype_digit($_POST['rank_3_lvl']) ? (int)$_POST['rank_3_lvl'] : 0 : 0;
                                $this->rank_4_lvl = isset($_POST['rank_4_lvl']) ? ctype_digit($_POST['rank_4_lvl']) ? (int)$_POST['rank_4_lvl'] : 0 : 0;
                                $this->rank_5_lvl = isset($_POST['rank_5_lvl']) ? ctype_digit($_POST['rank_5_lvl']) ? (int)$_POST['rank_5_lvl'] : 0 : 0;
                                if($this->element_type == false){
                                    $this->errors[] = __('Please select element type');
                                }
                                if(!in_array($this->rank_1_lvl, range(0, 10))){
                                    $this->errors[] = __('Wrong Element Level');
                                }
                                if(!in_array($this->rank_2_lvl, range(0, 10))){
                                    $this->errors[] = __('Wrong Element Level');
                                }
                                if(!in_array($this->rank_3_lvl, range(0, 10))){
                                    $this->errors[] = __('Wrong Element Level');
                                }
                                if(!in_array($this->rank_4_lvl, range(0, 10))){
                                    $this->errors[] = __('Wrong Element Level');
                                }
                                if(!in_array($this->rank_5_lvl, range(0, 10))){
                                    $this->errors[] = __('Wrong Element Level');
                                }
                            }
                            if($this->item_info['exetype'] == 11){
                                $this->wing_main_element = isset($_POST['wing_main_element']) ? ctype_digit($_POST['wing_main_element']) ? (int)$_POST['wing_main_element'] : 0 : 0;
                                $this->wing_main_element_lvl = isset($_POST['wing_main_element_lvl']) ? ctype_digit($_POST['wing_main_element_lvl']) ? (int)$_POST['wing_main_element_lvl'] : 0 : 0;
                                $this->wing_additional_element = isset($_POST['wing_additional_element']) ? ctype_digit($_POST['wing_additional_element']) ? (int)$_POST['wing_additional_element'] : 0 : 0;
                                $this->wing_additional_element_lvl = isset($_POST['wing_additional_element_lvl']) ? ctype_digit($_POST['wing_additional_element_lvl']) ? (int)$_POST['wing_additional_element_lvl'] : 0 : 0;
                                $this->wing_additional2_element = isset($_POST['wing_additional2_element']) ? ctype_digit($_POST['wing_additional2_element']) ? (int)$_POST['wing_additional2_element'] : 0 : 0;
                                $this->wing_additional2_element_lvl = isset($_POST['wing_additional2_element_lvl']) ? ctype_digit($_POST['wing_additional2_element_lvl']) ? (int)$_POST['wing_additional2_element_lvl'] : 0 : 0;
                                $this->wing_additional3_element = isset($_POST['wing_additional3_element']) ? ctype_digit($_POST['wing_additional3_element']) ? (int)$_POST['wing_additional3_element'] : 0 : 0;
                                $this->wing_additional3_element_lvl = isset($_POST['wing_additional3_element_lvl']) ? ctype_digit($_POST['wing_additional3_element_lvl']) ? (int)$_POST['wing_additional3_element_lvl'] : 0 : 0;
                                $this->wing_additional4_element = isset($_POST['wing_additional4_element']) ? ctype_digit($_POST['wing_additional4_element']) ? (int)$_POST['wing_additional4_element'] : 0 : 0;
                                $this->wing_additional4_element_lvl = isset($_POST['wing_additional4_element_lvl']) ? ctype_digit($_POST['wing_additional4_element_lvl']) ? (int)$_POST['wing_additional4_element_lvl'] : 0 : 0;
                                $this->wing_additional5_element = isset($_POST['wing_additional5_element']) ? ctype_digit($_POST['wing_additional5_element']) ? (int)$_POST['wing_additional5_element'] : 0 : 0;
                                $this->wing_additional5_element_lvl = isset($_POST['wing_additional5_element_lvl']) ? ctype_digit($_POST['wing_additional5_element_lvl']) ? (int)$_POST['wing_additional5_element_lvl'] : 0 : 0;
                                $check_dupe_wing_options = array_count_values([$this->wing_additional2_element, $this->wing_additional3_element, $this->wing_additional4_element, $this->wing_additional5_element]);
                                foreach($check_dupe_wing_options as $key => $dupe){
                                    if($dupe > 1 && $key != 0){
                                        $this->errors[] = __('Please choose different wing options');
                                        break;
                                    }
                                }
								
								if($this->wing_main_element_lvl > 15){
									$this->errors[] = __('Wing main element max lvl is 15');
								}
								if($this->wing_additional_element_lvl > 15){
									$this->errors[] = __('Wing additional #0 element max lvl is 15');
								}
								if($this->wing_additional2_element_lvl > 3){
									$this->errors[] = __('Wing additional #1 element max lvl is 3');
								}
								if($this->wing_additional3_element_lvl > 3){
									$this->errors[] = __('Wing additional #2 element max lvl is 3');
								}
								if($this->wing_additional4_element_lvl > 3){
									$this->errors[] = __('Wing additional #3 element max lvl is 3');
								}
								if($this->wing_additional5_element_lvl > 3){
									$this->errors[] = __('Wing additional #4 element max lvl is 3');
								}
                            }
                            if($this->item_info['original_item_cat'] == 12 && in_array($this->item_info['item_id'], [200, 201, 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 217, 306, 307, 308])){
                                $this->element_type = isset($_POST['element_type']) ? ctype_digit($_POST['element_type']) ? (int)$_POST['element_type'] : 0 : 0;
                                $this->element_rank_1 = isset($_POST['slot_anger']) ? ctype_digit($_POST['slot_anger']) ? (int)$_POST['slot_anger'] : 0 : 0;
                                $this->element_rank_2 = isset($_POST['slot_blessing']) ? ctype_digit($_POST['slot_blessing']) ? (int)$_POST['slot_blessing'] : 0 : 0;
                                $this->element_rank_3 = isset($_POST['slot_integrity']) ? ctype_digit($_POST['slot_integrity']) ? (int)$_POST['slot_integrity'] : 0 : 0;
                                $this->element_rank_4 = isset($_POST['slot_divinity']) ? ctype_digit($_POST['slot_divinity']) ? (int)$_POST['slot_divinity'] : 0 : 0;
                                $this->element_rank_5 = isset($_POST['slot_gale']) ? ctype_digit($_POST['slot_gale']) ? (int)$_POST['slot_gale'] : 0 : 0;
                                if($this->element_type == 0){
                                    $this->errors[] = __('Please select element type');
                                }
                                if($this->element_rank_1 == 0 &&$this->element_rank_2 == 0 && $this->element_rank_3 == 0 && $this->element_rank_4 == 0 && $this->element_rank_5 == 0){
                                    $this->errors[] = __('Please select atleast one pentagram slot');
                                }
                            }
                            if($this->item_info['use_sockets'] == 1){
                                if($this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|use_socket') == 1 && $this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|allow_select_socket') == 1){
                                    for($s_i = 0; $s_i < (int)$this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|max_sockets_to_show'); $s_i++){
                                        $this->sockets[$s_i] = (isset($_POST['socket' . ($s_i + 1)]) && $_POST['socket' . ($s_i + 1)] != 'no') ? preg_match('/\d{1,3}-\d{1,3}/', $_POST['socket' . ($s_i + 1)]) ? explode('-', $_POST['socket' . ($s_i + 1)]) : '' : '';
                                    }
                                    foreach($this->sockets as $key => $value){
                                        if($value === ''){
                                            $this->errors[] = __('Please select socket') . ' ' . ($key + 1);
                                        } else{
                                            $this->socket_seed[$key] = $this->sockets[$key][0];
                                            $this->sockets[$key] = $this->sockets[$key][1];
                                        }
                                    }
                                    $max_sockets = ($this->payment_method == 1) ? $this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|socket_limit_credits') : $this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|socket_limit_gcredits');
                                } else{
                                    if($this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|empty_socket') == 1)
                                        $this->sockets = [254, 254, 254, 254, 254];
                                }
                            }
                            if($this->item_info['payment_type'] != 0){
                                if($this->payment_method != $this->item_info['payment_type'])
                                    $this->errors[] = __('You are not allowed to use this payment method.');
                            }
                            if(!in_array($this->payment_method, [1, 2]))
                                $this->errors[] = __('Invalid payment method.');
                            if(count($this->errors) > 0){
                                if(count($this->errors) == 1)
                                    json(['error' => $this->errors[0]]); else
                                    json(['error' => $this->errors]);
                            } else{
                                if(!empty($this->harmony)){
                                    if($this->Mshop->check_harmony($this->item_info['use_harmony'], $this->harmony) == false)
                                        $this->errors[] = __('Invalid harmony value selected.');
                                }
                                if($this->level > $this->item_info['max_item_lvl'])
                                    $this->errors[] = sprintf(__('Max item level allowed %s'), $this->item_info['max_item_lvl']);
                                if($this->option > $this->item_info['max_item_opt']){
                                    if($this->item_info['original_item_cat'] == 13)
                                        $max_opt = $this->item_info['max_item_opt'] . ' %'; else
                                        $max_opt = '+ ' . ($this->item_info['max_item_opt'] * (($this->item_info['original_item_cat'] == 6) ? 5 : 4));
                                    $this->errors[] = sprintf(__('Max item option allowed %s'), $max_opt);
                                }
                                if(count($this->exe) > $this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|exe_limit'))
                                    $this->errors[] = sprintf(__('Max exellent options allowed %s'), $this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|exe_limit'));
                                if($this->item_info['use_sockets'] == 1){
                                    if($this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|use_socket') == 1 && $this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|allow_select_socket') == 1){
                                        if($this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|equal_socket') == 0){
                                            $check_dupe = array_count_values($this->sockets);
                                            foreach($check_dupe as $key => $dupe){
                                                if($dupe > 1 && $key != 254){
                                                    $this->errors[] = __('Please select different socket options.');
                                                }
                                            }
                                        }
                                        foreach($this->sockets as $key => $socket){
                                            if($this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|check_socket_part_type') == 1){
                                                $this->socket_info[$key] = $this->Mshop->check_sockets_part_type($this->item_info['exetype'], $socket, $this->socket_seed[$key], $this->item_info['original_item_cat']);
                                            } else{
                                                $this->socket_info[$key] = $this->Mshop->check_sockets($socket, $this->socket_seed[$key]);
                                            }
                                            if($this->socket_info[$key] != false){
                                                if($this->socket_info[$key]['socket_id'] != 254 && ($this->socket_info[$key]['value'] != null || $this->socket_info[$key]['value'] != 0))
                                                    $this->socket_count++;
                                            } else{
                                                $this->errors[] = __('Wrong socket option.');
                                            }
                                            if($this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|equal_seed') == 0){
                                                $this->seeds[$socket] = $this->socket_info[$key]['seed'];
                                            }
                                        }
                                        if($this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|equal_seed') == 0){
                                            $duped_seeds = array_count_values($this->seeds);
                                            foreach($duped_seeds as $key => $dupe){
                                                if($dupe > 1 && $key != 254){
                                                    $this->errors[] = __('Please select different socket seeds.');
                                                }
                                            }
                                        }
                                        if($max_sockets < $this->socket_count)
                                            $this->errors[] = sprintf(__('Sorry you can\'t select more than %d sockets'), $max_sockets);
                                    }
                                }
                                if($this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|allow_exe_anc') == 0){
                                    if(count($this->exe) > 0 && $this->ancient != 0){
                                        $this->errors[] = __('Please choose only ancient or only excelent options.');
                                    }
                                }
                                if($this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|allow_exe_socket') == 0){
                                    if(count($this->exe) > 0 && $this->socket_count > 0){
                                        $this->errors[] = __('Please choose only socket or only excelent options.');
                                    }
                                }
                                if($this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|allow_anc_harmony') == 0){
                                    if(!empty($this->harmony) > 0 && $this->ancient != 0){
                                        $this->errors[] = __('Please choose only ancient or only harmony options.');
                                    }
                                }
								
								if($this->mastery_bonus_opt != 0){
									if(!in_array($this->mastery_bonus_opt, [1, 2, 3, 255])){
										 $this->errors[] = __('Invalid mastery bonus option.');
									}
								}
								
                                if(count($this->errors) > 0){
                                    if(count($this->errors) == 1)
                                        json(['error' => $this->errors[0]]); 
									else
                                        json(['error' => $this->errors]);
                                } else{
                                    if($type == 'direct'){
                                        if(!$this->Maccount->check_connect_stat($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']))){
                                            json(['error' => __('Please logout from game.')]);
                                        } else{
                                            $this->calculate_price();
                                            $this->generate_item();
                                            if($vault = $this->Mshop->get_vault_content($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']))){
                                                $space = $this->Mshop->check_space($vault['Items'], $this->item_info['data']['x'], $this->item_info['data']['y'], $this->website->get_value_from_server($this->session->userdata(['user' => 'server']), 'wh_multiplier'), $this->website->get_value_from_server($this->session->userdata(['user' => 'server']), 'item_size'), $this->website->get_value_from_server($this->session->userdata(['user' => 'server']), 'wh_hor_size'), $this->website->get_value_from_server($this->session->userdata(['user' => 'server']), 'wh_ver_size'));
                                                if($space === null){
                                                    json(['error' => $this->Mshop->errors[0]]);
                                                } else{
                                                    $this->Mshop->generate_new_items($this->item_hex, $space, $this->website->get_value_from_server($this->session->userdata(['user' => 'server']), 'wh_multiplier'), $this->website->get_value_from_server($this->session->userdata(['user' => 'server']), 'item_size'));
                                                    $status = $this->Maccount->get_amount_of_credits($this->session->userdata(['user' => 'username']), $this->payment_method, $this->session->userdata(['user' => 'server']), $this->session->userdata(['user' => 'id']));
                                                    switch($this->payment_method){
                                                        case 1:
                                                            if($status < $this->price){
                                                                json(['error' => sprintf(__('You have insufficient amount of %s'), $this->config->config_entry('credits_' . $this->session->userdata(['user' => 'server']) . '|title_1'))]);
                                                            } else{
                                                                goto charge;
                                                            }
                                                            break;
                                                        case 2:
                                                            if($status < $this->price){
                                                                json(['error' => sprintf(__('You have insufficient amount of %s'), $this->config->config_entry('credits_' . $this->session->userdata(['user' => 'server']) . '|title_2'))]);
                                                            } else{
                                                                goto charge;
                                                            }
                                                            break;
                                                    }
                                                    charge:
                                                    $this->website->charge_credits($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $this->price, $this->payment_method);
                                                    $this->Maccount->add_account_log('Bought Shop Item For ' . $this->website->translate_credits($this->payment_method, $this->session->userdata(['user' => 'server'])) . '', -$this->price, $this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
                                                    $this->Mshop->update_warehouse($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
                                                    $this->Mshop->set_total_bought(hexdec(substr($this->item_hex, 0, 2)), hexdec(substr($this->item_hex, 18, 1)));
                                                    $this->Mshop->log_purchase($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $this->item_hex, $this->price, $this->payment_method);
                                                    json(['success' => __('Thank You, for your purchase.') . ' ' . __('Your item was moved into warehouse.'), 'price' => $this->price, 'payment_method' => $this->payment_method]);
                                                }
                                            } else{
                                                json(['error' => __('Please open your warehouse in game first.')]);
                                            }
                                        }
                                    } else if($type == 'card'){
                                        $this->calculate_price();
                                        $this->generate_item();
                                        $this->Mshop->add_item_to_card($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $this->item_hex, $this->price, $this->payment_method);
                                        json(['success' => __('Thank You, for your purchase.') . ' ' . __('Your item was added to cart.')]);
                                    } else{
                                        json(['error' => __('Error wrong  sending type.')]);
                                    }
                                }
                            }
                        }
                    } else{
                        json(['error' => __('Module Disabled.')]);
                    }
                } else{
                    json(['error' => __('Please login into website.')]);
                }
            }
        }

        public function cart(){
            if($this->session->userdata(['user' => 'logged_in'])){
                if(!$this->website->module_disabled('shop_' . $this->session->userdata(['user' => 'server']))){
                    $this->vars['credits_items'] = $this->Mshop->load_card_items($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), 1);
                    $this->vars['gcredits_items'] = $this->Mshop->load_card_items($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), 2);
                    $this->load->view($this->config->config_entry('main|template') . DS . 'shop' . DS . 'view.card', $this->vars);
                }
            } else{
                $this->login();
            }
        }

        public function remove_item_from_cart(){
            if($this->session->userdata(['user' => 'logged_in'])){
                if(!$this->website->module_disabled('shop_' . $this->session->userdata(['user' => 'server']))){
                    $id = (isset($_POST['id']) && ctype_digit($_POST['id'])) ? (int)$_POST['id'] : '';
                    if($this->Mshop->item_exist_in_cart($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $id)){
                        $this->Mshop->remove_item_from_cart($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $id);
                        json(['success' => __('Item successfully removed from cart.')]);
                    } else{
                        json(['error' => __('Item not found.')]);
                    }
                }
            } else{
                json(['error' => __('Please login into website.')]);
            }
        }

		public function senditems(){
            if(is_ajax()){
                if($this->session->userdata(['user' => 'logged_in'])){
                    $this->load->model('account');
                    if(!$this->Maccount->check_connect_stat($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']))){
                        json(['error' => __('Please logout from game.')]);
                    } else{
                        if(isset($_POST['add_to_warehouse'])){
                            if(count($_POST['add_to_warehouse']) > 0){
                                $total_items = count($_POST['add_to_warehouse']);
                                $total_price = 0;
                                $price_type = 1;
                                $items_hex_values = [];
                                foreach($_POST['add_to_warehouse'] as $key => $ids){
                                    if($item = $this->Mshop->item_exist_in_cart($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $key)){
                                        $items_hex_values[] = [$item['item_hex'], $item['price'], $key];
                                        $total_price += $item['price'];
                                        $price_type = $item['price_type'];
                                    } else{
                                        json(['error' => __('Item not found.')]);
                                    }
                                }
                                $status = $this->Maccount->get_amount_of_credits($this->session->userdata(['user' => 'username']), $price_type, $this->session->userdata(['user' => 'server']), $this->session->userdata(['user' => 'id']));
                                switch($price_type){
                                    case 1:
                                        if($status < $total_price){
                                            json(['error' => sprintf(__('You have insufficient amount of %s'), $this->config->config_entry('credits_' . $this->session->userdata(['user' => 'server']) . '|title_1'))]);
                                        } else{
                                            goto check_space;
                                        }
                                        break;
                                    case 2:
                                        if($status < $total_price){
                                            json(['error' => sprintf(__('You have insufficient amount of %s'), $this->config->config_entry('credits_' . $this->session->userdata(['user' => 'server']) . '|title_2'))]);
                                        } else{
                                            goto check_space;
                                        }
                                        break;
                                }
                                check_space:
                                if($vault = $this->Mshop->get_vault_content($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']))){
                                    $space = [];
                                    $new_items = false;
                                    $not_added_items = [];
                                    foreach($items_hex_values as $items){
                                        $this->iteminfo->itemData($items[0]);
                                        $space[$items[0]] = $this->Mshop->check_space(($new_items != false) ? $new_items : $vault['Items'], $this->iteminfo->getX(), $this->iteminfo->getY(), $this->website->get_value_from_server($this->session->userdata(['user' => 'server']), 'wh_multiplier'), $this->website->get_value_from_server($this->session->userdata(['user' => 'server']), 'item_size'), $this->website->get_value_from_server($this->session->userdata(['user' => 'server']), 'wh_hor_size'), $this->website->get_value_from_server($this->session->userdata(['user' => 'server']), 'wh_ver_size'));
                                        if($space[$items[0]] !== null){
                                            $this->Mshop->generate_new_items($items[0], $space[$items[0]], $this->website->get_value_from_server($this->session->userdata(['user' => 'server']), 'wh_multiplier'), $this->website->get_value_from_server($this->session->userdata(['user' => 'server']), 'item_size'), $new_items);
                                            $new_items = $this->Mshop->new_vault;
                                            $this->Mshop->change_cart_item_status($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $items[0]);
                                            $this->Mshop->log_purchase($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $items[0], $items[1], $price_type);
                                            $this->Mshop->set_total_bought(hexdec(substr($items[0], 0, 2)), hexdec(substr($items[0], 18, 1)));
                                        } else{
                                            $not_added_items[] = $items;
                                        }
                                    }
                                    if(count($not_added_items) == $total_items){
                                        json(['error' => __('Please free up space in your warehouse.')]);
                                    } else{
                                        if(count($not_added_items) > 0){
                                            $left_items = [];
                                            $total_not_added_items = count($not_added_items);
                                            foreach($not_added_items as $items){
                                                if($info = $this->Mshop->get_not_added_item_price($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $items[0])){
                                                    $total_price -= $info['price'];
                                                    $left_items[] = $items[2];
                                                }
                                            }
                                            $this->Mshop->update_warehouse($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
                                            $this->website->charge_credits($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $total_price, $price_type);
                                            $this->Maccount->add_account_log('Bought Shop Items For ' . $this->website->translate_credits($price_type, $this->session->userdata(['user' => 'server'])) . '', -$total_price, $this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
                                            json(['success' => __('Thank You, for your purchase.') . ' ' . sprintf(__('%d was not added to warehouse.'), $total_not_added_items), 'price' => $total_price, 'payment_method' => $price_type, 'left_items' => $left_items]);
                                        } else{
                                            $this->Mshop->update_warehouse($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
                                            $this->website->charge_credits($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $total_price, $price_type);
                                            $this->Maccount->add_account_log('Bought Shop Items For ' . $this->website->translate_credits($price_type, $this->session->userdata(['user' => 'server'])) . '', -$total_price, $this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
                                            json(['success' => __('Thank You, for your purchase.') . ' ' . __('Your items was moved into warehouse.'), 'price' => $total_price, 'payment_method' => $price_type, 'left_items' => []]);
                                        }
                                    }
                                } else{
                                    json(['error' => __('Please open your warehouse in game first.')]);
                                }
                            } else{
                                json(['error' => __('Please select atleast one item.')]);
                            }
                        } else{
                            json(['error' => __('Please select atleast one item.')]);
                        }
                    }
                } else{
                    json(['error' => __('Please login into website.')]);
                }
            }
        }

        public function loadharmonylist(){
            $cat = (isset($_POST['cat']) && ctype_digit($_POST['cat'])) ? (int)$_POST['cat'] : '';
            $hopt = (isset($_POST['hopt']) && ctype_digit($_POST['hopt'])) ? (int)$_POST['hopt'] : '';
            if(is_ajax()){
                if($cat === '')
                    json(['error' => __('Invalid Category')]); else if($hopt === '')
                    json(['error' => __('Invalid Harmony Option')]);
                else
                    json(['harmonylist' => $this->Mshop->load_harmony_values($cat, $hopt)]);
            }
        }

        public function getharmonyprice(){
            $cat = (isset($_POST['cat']) && ctype_digit($_POST['cat'])) ? (int)$_POST['cat'] : '';
            $hopt = (isset($_POST['hopt']) && ctype_digit($_POST['hopt'])) ? (int)$_POST['hopt'] : '';
            $hval = (isset($_POST['hval']) && ctype_digit($_POST['hval'])) ? (int)$_POST['hval'] : '';
            if(is_ajax()){
                if($cat === '')
                    json(['error' => __('Invalid Category')]); else if($hopt === '')
                    json(['error' => __('Invalid Harmony Option')]);
                else if($hval === '')
                    json(['error' => __('Invalid Harmony Value')]);
                else
                    json(['hprice' => $this->Mshop->get_harmony_price($cat, $hopt, $hval, $this->session->userdata(['user' => 'server']))]);
            }
        }

        public function getsocketprice(){
            $option = (isset($_POST['option']) && ctype_digit($_POST['option'])) ? (int)$_POST['option'] : '';
            if(is_ajax()){
                if($option === '')
                    json(['error' => __('Invalid Socket Option')]); else
                    json(['socket_price' => $this->Mshop->get_socket_price($option, $this->session->userdata(['user' => 'server']))]);
            }
        }

		private function calculate_price(){
            $this->price = $this->Mshop->discount($this->item_info['price'], $this->session->userdata(['user' => 'server']));
            if($this->item_info['original_item_cat'] == 12 && in_array($this->item_info['item_id'], $this->errtel_ids)){
                if($this->element_type != 0){
                    $this->price += $this->Mshop->discount($this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|element_type_price'), $this->session->userdata(['user' => 'server']));
                }
                if($this->element_rank_1 != 0){
                    $this->price += ($this->rank_1_lvl + 1) * $this->Mshop->discount($this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|element_rank_1_price'), $this->session->userdata(['user' => 'server']));
                }
                if($this->element_rank_2 != 0){
                    $this->price += ($this->rank_2_lvl + 1) * $this->Mshop->discount($this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|element_rank_2_price'), $this->session->userdata(['user' => 'server']));
                }
                if($this->element_rank_3 != 0){
                    $this->price += ($this->rank_3_lvl + 1) * $this->Mshop->discount($this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|element_rank_3_price'), $this->session->userdata(['user' => 'server']));
                }
                if($this->element_rank_4 != 0){
                    $this->price += ($this->rank_4_lvl + 1) * $this->Mshop->discount($this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|element_rank_4_price'), $this->session->userdata(['user' => 'server']));
                }
                if($this->element_rank_5 != 0){
                    $this->price += ($this->rank_5_lvl + 1) * $this->Mshop->discount($this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|element_rank_5_price'), $this->session->userdata(['user' => 'server']));
                }
            }
            if($this->item_info['original_item_cat'] == 12 && in_array($this->item_info['item_id'], $this->pentagram_ids)){
                if($this->element_type != 0){
                    $this->price += $this->Mshop->discount($this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|element_type_price'), $this->session->userdata(['user' => 'server']));
                }
                if($this->element_rank_1 != 0){
                    $this->price += $this->Mshop->discount($this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|pentagram_slot_anger_price'), $this->session->userdata(['user' => 'server']));
                }
                if($this->element_rank_2 != 0){
                    $this->price += $this->Mshop->discount($this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|pentagram_slot_blessing_price'), $this->session->userdata(['user' => 'server']));
                }
                if($this->element_rank_3 != 0){
                    $this->price += $this->Mshop->discount($this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|pentagram_slot_integrity_price'), $this->session->userdata(['user' => 'server']));
                }
                if($this->element_rank_4 != 0){
                    $this->price += $this->Mshop->discount($this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|pentagram_slot_divinity_price'), $this->session->userdata(['user' => 'server']));
                }
                if($this->element_rank_5 != 0){
                    $this->price += $this->Mshop->discount($this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|pentagram_slot_gale_price'), $this->session->userdata(['user' => 'server']));
                }
            }
            if($this->item_info['exetype'] == 11){
                if($this->wing_main_element != 0){
                    $this->price += $this->Mshop->discount($this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|wing_element_main_price'), $this->session->userdata(['user' => 'server']));
                }
                if($this->wing_main_element_lvl != 0){
                    $this->price += $this->wing_main_element_lvl * $this->Mshop->discount($this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|wing_element_main_price'), $this->session->userdata(['user' => 'server']));
                }
                if($this->wing_additional_element != 0){
                    $this->price += $this->Mshop->discount($this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|wing_element_additional_price'), $this->session->userdata(['user' => 'server']));
                }
                if($this->wing_additional_element_lvl != 0){
                    $this->price += $this->wing_additional_element_lvl * $this->Mshop->discount($this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|wing_element_additional_price'), $this->session->userdata(['user' => 'server']));
                }
                if($this->wing_additional2_element != 0){
                    $this->price += $this->Mshop->discount($this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|wing_element_additional2_price'), $this->session->userdata(['user' => 'server']));
                }
                if($this->wing_additional2_element_lvl != 0){
                    $this->price += $this->wing_additional2_element_lvl * $this->Mshop->discount($this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|wing_element_additional2_price'), $this->session->userdata(['user' => 'server']));
                }
                if($this->wing_additional3_element != 0){
                    $this->price += $this->Mshop->discount($this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|wing_element_additional3_price'), $this->session->userdata(['user' => 'server']));
                }
                if($this->wing_additional3_element_lvl != 0){
                    $this->price += $this->wing_additional3_element_lvl * $this->Mshop->discount($this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|wing_element_additional3_price'), $this->session->userdata(['user' => 'server']));
                }
                if($this->wing_additional4_element != 0){
                    $this->price += $this->Mshop->discount($this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|wing_element_additional4_price'), $this->session->userdata(['user' => 'server']));
                }
                if($this->wing_additional4_element_lvl != 0){
                    $this->price += $this->wing_additional4_element_lvl * $this->Mshop->discount($this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|wing_element_additional4_price'), $this->session->userdata(['user' => 'server']));
                }
                if($this->wing_additional5_element != 0){
                    $this->price += $this->Mshop->discount($this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|wing_element_additional5_price'), $this->session->userdata(['user' => 'server']));
                }
                if($this->wing_additional5_element_lvl != 0){
                    $this->price += $this->wing_additional5_element_lvl * $this->Mshop->discount($this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|wing_element_additional5_price'), $this->session->userdata(['user' => 'server']));
                }
            }
            if($this->level > 0)
                $this->price += $this->Mshop->discount($this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|lvl_price'), $this->session->userdata(['user' => 'server'])) * $this->level;
            if($this->option > 0)
                $this->price += $this->Mshop->discount($this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|opt_price'), $this->session->userdata(['user' => 'server'])) * $this->option;
            if($this->luck == 1)
                $this->price += $this->Mshop->discount($this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|luck_price'), $this->session->userdata(['user' => 'server']));
            if($this->skill == 1)
                $this->price += $this->Mshop->discount($this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|skill_price'), $this->session->userdata(['user' => 'server']));
            if($this->ancient == 1 || $this->ancient == 2)
                $this->price += ($this->ancient == 1) ? $this->Mshop->discount($this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|anc1_price'), $this->session->userdata(['user' => 'server'])) : $this->Mshop->discount($this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|anc2_price'), $this->session->userdata(['user' => 'server']));
            if(count($this->exe) > 0)
                $this->price += $this->Mshop->discount($this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|exe_price') * count($this->exe), $this->session->userdata(['user' => 'server']));
            if(count($this->harmony) > 0){
                $this->price += $this->Mshop->get_harmony_price($this->item_info['original_item_cat'], $this->harmony[0], $this->harmony[1], $this->session->userdata(['user' => 'server']));
            }
			
			if($this->mastery_bonus_opt != 0){
				if(in_array($this->mastery_bonus_opt, [1, 2, 3])){
					 $this->price += $this->Mshop->discount($this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|bonus_mastery_price'), $this->session->userdata(['user' => 'server'])) * $this->mastery_bonus_opt;
				}
			}
            if($this->ref == 1)
                $this->price += $this->Mshop->discount($this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|ref_price'), $this->session->userdata(['user' => 'server']));
            if($this->fenrir != 0){
                switch($this->fenrir){
                    case 1:
                        $this->price += $this->Mshop->discount($this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|dfenrir_price'), $this->session->userdata(['user' => 'server']));
                        break;
                    case 2:
                        $this->price += $this->Mshop->discount($this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|pfenrir_price'), $this->session->userdata(['user' => 'server']));
                        break;
                    case 4:
                        $this->price += $this->Mshop->discount($this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|gfenrir_price'), $this->session->userdata(['user' => 'server']));
                        break;
                }
            }
            if($this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|use_socket') == 1 && $this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|allow_select_socket') == 1){
                foreach($this->sockets as $socket){
                    $this->price += $this->Mshop->get_socket_price($socket, $this->session->userdata(['user' => 'server']));
                }
            }
            if($this->payment_method == 2){
                $this->price = floor($this->price + (($this->config->config_entry('shop_' . $this->session->userdata(['user' => 'server']) . '|gold_credits_price') * $this->price) / 100));
            }
            if($this->session->userdata('vip')){
                $this->price -= floor(($this->price / 100) * $this->session->userdata(['vip' => 'shop_discount']));
            }
        }
	
		private function generate_item(){
            if($this->iteminfo->setItemData($this->item_info['item_id'], $this->item_info['original_item_cat'], $this->website->get_value_from_server($this->session->userdata(['user' => 'server']), 'item_size'))){
                $this->createitem->setItemData($this->iteminfo->item_data);
                $this->createitem->id($this->item_info['item_id']);
                $this->createitem->cat($this->item_info['original_item_cat']);
                $this->createitem->refinery($this->ref);
                $this->createitem->harmony($this->harmony);
                $this->createitem->serial($this->Mshop->generate_serial($this->session->userdata(['user' => 'server'])));
                if($this->website->get_value_from_server($this->session->userdata(['user' => 'server']), 'item_size') == 64){
                    $this->createitem->serial2(true);
                }
                $this->createitem->lvl($this->level);
                if($this->item_info['stick_level'] > 0){
                    $this->createitem->stickLvl($this->item_info['stick_level']);
                }
                $this->createitem->skill($this->skill);
                $this->createitem->luck($this->luck);
                $this->createitem->opt($this->option);
                $this->createitem->exe($this->exe);
                $this->createitem->fenrir($this->fenrir);
                $this->createitem->ancient($this->ancient);
				if($this->mastery_bonus_opt != 0){
					if(in_array($this->mastery_bonus_opt, [1, 2, 3, 255])){
						$this->createitem->is_mastery_opt = true;
						$this->createitem->socket([255, 255, 255, 255, $this->mastery_bonus_opt]);
					}
				}
				else{
					$this->createitem->socket($this->sockets);
				}
                $this->createitem->elementType($this->element_type);
                $this->createitem->elementRanks(['rank1' => $this->element_rank_1, 'rank2' => $this->element_rank_2, 'rank3' => $this->element_rank_3, 'rank4' => $this->element_rank_4, 'rank5' => $this->element_rank_5]);
				if($this->item_info['original_item_cat'] == 12 && in_array($this->item_info['item_id'], [221, 231, 241, 251, 261])){
					$this->createitem->elementLevels(['rank1' => $this->rank_1_lvl, 'rank2' => $this->rank_2_lvl, 'rank3' => $this->rank_3_lvl, 'rank4' => $this->rank_4_lvl, 'rank5' => $this->rank_5_lvl]);
				}
				
				if($this->item_info['exetype'] == 11){
					if($this->wing_main_element != 0){
						$this->createitem->harmony([0, $this->wing_main_element_lvl]);
					}
					if($this->wing_additional_element != 0){
						if($this->wing_additional_element == 1){
							$this->wing_additional_element = 0;
						}
						$this->sockets[4] = $this->wing_additional_element + $this->wing_additional_element_lvl;
					}
					else{
						$this->sockets[4] = 255;
					}
					if($this->wing_additional2_element != 0){
						if($this->wing_additional2_element == 1){
							$this->wing_additional2_element = 0;
						}
						$this->sockets[0] = $this->wing_additional2_element + $this->wing_additional2_element_lvl;
					} else{
						$this->sockets[0] = 255;
					}
					if($this->wing_additional3_element != 0){
						if($this->wing_additional3_element == 1){
							$this->wing_additional3_element = 0;
						}
						$this->sockets[1] = $this->wing_additional3_element + $this->wing_additional3_element_lvl;
					} else{
						$this->sockets[1] = 255;
					}
					if($this->wing_additional4_element != 0){
						if($this->wing_additional4_element == 1){
							$this->wing_additional4_element = 0;
						}
						$this->sockets[2] = $this->wing_additional4_element + $this->wing_additional4_element_lvl;
					} else{
						$this->sockets[2] = 255;
					}
					if($this->wing_additional5_element != 0){
						if($this->wing_additional5_element == 1){
							$this->wing_additional5_element = 0;
						}
						$this->sockets[3] = $this->wing_additional5_element + $this->wing_additional5_element_lvl;
					} else{
						$this->sockets[3] = 255;
					}
					$this->createitem->socket($this->sockets);
				}
                $this->item_hex = $this->createitem->to_hex();
                return true;
            }
            return false;
        }

        public function buy_level(){
            if($this->session->userdata(['user' => 'logged_in'])){
                $this->load->model('character');
                $this->vars['level_config'] = $this->config->values('buylevel_config', $this->session->userdata(['user' => 'server']));
                if($this->vars['level_config']['active'] == 1){
                    $this->vars['char_list'] = $this->Mcharacter->load_char_list($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
                    $this->load->view($this->config->config_entry('main|template') . DS . 'shop' . DS . 'view.buy_level', $this->vars);
                } else{
                    $this->disabled();
                }
            } else{
                $this->login();
            }
        }

        public function buy_gm(){
            if($this->session->userdata(['user' => 'logged_in'])){
                if(!$this->website->module_disabled('buygm')){
                    $this->load->model('character');
                    $this->vars['char_list'] = $this->Mcharacter->load_char_list($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
                    $this->load->view($this->config->config_entry('main|template') . DS . 'shop' . DS . 'view.buy_gm', $this->vars);
                }
            } else{
                $this->login();
            }
        }

        public function buy_stats(){
            if($this->session->userdata(['user' => 'logged_in'])){
                if(!$this->website->module_disabled('buypoints')){
                    $this->load->model('character');
                    $this->vars['char_list'] = $this->Mcharacter->load_char_list($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
                    $this->load->view($this->config->config_entry('main|template') . DS . 'shop' . DS . 'view.buy_statpoints', $this->vars);
                }
            } else{
                $this->login();
            }
        }

        public function change_class(){
            if($this->session->userdata(['user' => 'logged_in'])){
                $this->vars['changeclass_config'] = $this->config->values('change_class_config');
                if(!$this->vars['changeclass_config']){
                    $this->disabled();
                } else{
                    if($this->vars['changeclass_config']['active'] == 0){
                        $this->disabled();
                    } else{
                        $this->load->model('character');
                        $this->vars['char_list'] = $this->Mcharacter->load_char_list($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
                        $this->load->view($this->config->config_entry('main|template') . DS . 'shop' . DS . 'view.change_class', $this->vars);
                    }
                }
            } else{
                $this->login();
            }
        }

        public function vip(){
            if($this->session->userdata(['user' => 'logged_in'])){
                $this->load->model('account');
                $vip_config = $this->config->values('vip_config');
                if(!$vip_config){
                    $this->disabled();
                } else{
                    if($vip_config['active'] == 0){
                        $this->disabled();
                    } else{
                        $this->vars['vip_packages'] = $this->Mshop->load_vip_packages($this->session->userdata(['user' => 'server']));
                        $this->load->view($this->config->config_entry('main|template') . DS . 'shop' . DS . 'view.buy_vip', $this->vars);
                    }
                }
            } else{
                $this->login();
            }
        }

		public function buy_vip($id = ''){
            if($this->session->userdata(['user' => 'logged_in'])){
                $this->load->model('account');
				$this->load->model('character');
                $vip_config = $this->config->values('vip_config');
                $vip_query_config = $this->config->values('vip_query_config');
				$table_config = $this->config->values('table_config', $this->session->userdata(['user' => 'server']));
                if(!$vip_config){
                    $this->disabled();
                } else{
                    if($vip_config['active'] == 0){
                        $this->disabled();
                    } else{
                        if($this->vars['vip_data'] = $this->Mshop->check_vip($id, $this->session->userdata(['user' => 'server']))){
                            if($this->vars['vip_data']['server_vip_package'] != null){
                                if($this->vars['vip_data']['server_vip_package'] != null && str_contains($this->vars['vip_data']['server_vip_package'], '|')){
									$vip = explode('|', $this->vars['vip_data']['server_vip_package']);
                                    $this->vars['vip_title'] = $vip_query_config['quearies'][$vip[0]]['vip_codes'][$vip[1]]['title'];
                                }
                            }
                            if(isset($_POST['buy_vip'])){
                                $this->csrf->verifyToken('post', 'exception', 3600, false);
																								
                                $status = $this->Maccount->get_amount_of_credits($this->session->userdata(['user' => 'username']), $this->vars['vip_data']['payment_type'], $this->session->userdata(['user' => 'server']), $this->session->userdata(['user' => 'id']));
								if($status < $this->vars['vip_data']['price']){
                                    $this->vars['error'] = sprintf(__('You have insufficient amount of %s'), $this->website->translate_credits($this->vars['vip_data']['payment_type'], $this->session->userdata(['user' => 'server'])));
                                } else{
                                    if($this->vars['existing'] = $this->Mshop->check_existing_vip_package($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']))){
                                        if($this->vars['existing']['viptype'] != $id){
                                            if($this->vars['existing']['viptime'] <= time()){									 
                                                $viptime = time() + $this->vars['vip_data']['vip_time'];
                                                $this->Mshop->update_vip_package($id, $viptime, $this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
                                                $this->Mshop->add_server_vip($viptime, $this->vars['vip_data']['server_vip_package'], $this->vars['vip_data']['connect_member_load'], $vip_query_config, $this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
                                                $this->Maccount->set_vip_session($viptime, $this->vars['vip_data']);
                                                $this->website->charge_credits($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $this->vars['vip_data']['price'], $this->vars['vip_data']['payment_type']);
                                                if($this->vars['vip_data']['wcoins'] > 0 && $table_config['wcoins']['table'] != '' && $table_config['wcoins']['column'] != ''){
													$this->Mcharacter->add_wcoins($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $this->session->userdata(['user' => 'id']), $this->vars['vip_data']['wcoins'], $table_config['wcoins']);
												}
												$this->Maccount->add_account_log('Purchased vip ' . $this->vars['vip_data']['package_title'] . ' package for ' . $this->website->translate_credits($this->vars['vip_data']['payment_type'], $this->session->userdata(['user' => 'server'])) . '', -$this->vars['vip_data']['price'], $this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
                                                if($this->config->values('email_config', 'vip_purchase_email') == 1){
                                                    $this->Maccount->sent_vip_extend_email($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $this->session->userdata(['user' => 'email']), $this->vars['vip_data']['package_title'], $viptime);
                                                }
                                                $this->vars['success'] = __('You have successfully purchased vip package.');
                                            } else{
                                                $this->vars['error'] = __('Other vip plan is still active, please wait until it will expire.');
                                            }
                                        } else{
                                            if($this->vars['existing']['viptime'] <= time()){
                                                $viptime = time() + $this->vars['vip_data']['vip_time'];
                                            } else{
												if($this->vars['vip_data']['allow_extend'] == 1){
													$viptime = $this->vars['existing']['viptime'] + $this->vars['vip_data']['vip_time'];
												}
												else{
													$viptime = false;
												}
                                            }
											if($viptime == false){
												$this->vars['error'] = __('Your not allowed to purchase vip package before expiring.');
											}
											else{
												$this->Mshop->update_vip_package($id, $viptime, $this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
												$this->Mshop->add_server_vip($viptime, $this->vars['vip_data']['server_vip_package'], $this->vars['vip_data']['connect_member_load'], $vip_query_config, $this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
												$this->Maccount->set_vip_session($viptime, $this->vars['vip_data']);
												$this->website->charge_credits($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $this->vars['vip_data']['price'], $this->vars['vip_data']['payment_type']);
												
												if($this->vars['vip_data']['wcoins'] > 0 && $table_config['wcoins']['table'] != '' && $table_config['wcoins']['column'] != ''){
													$this->Mcharacter->add_wcoins($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $this->session->userdata(['user' => 'id']), $this->vars['vip_data']['wcoins'], $table_config['wcoins']);
												}
												$this->Maccount->add_account_log('Purchased vip ' . $this->vars['vip_data']['package_title'] . ' package for ' . $this->website->translate_credits($this->vars['vip_data']['payment_type'], $this->session->userdata(['user' => 'server'])) . '', -$this->vars['vip_data']['price'], $this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
												if($this->config->values('email_config', 'vip_purchase_email') == 1){
													$this->Maccount->sent_vip_extend_email($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $this->session->userdata(['user' => 'email']), $this->vars['vip_data']['package_title'], $viptime);
												}
												$this->vars['success'] = __('You have successfully purchased vip package.');
											}
                                        }
                                    } else{
                                        $viptime = time() + $this->vars['vip_data']['vip_time'];
										$this->Mshop->insert_vip_package($id, $viptime, $this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
                                        $this->Mshop->add_server_vip($viptime, $this->vars['vip_data']['server_vip_package'], $this->vars['vip_data']['connect_member_load'], $vip_query_config, $this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
                                        $this->Maccount->set_vip_session($viptime, $this->vars['vip_data']);                                 
										$this->website->charge_credits($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $this->vars['vip_data']['price'], $this->vars['vip_data']['payment_type']);
										if($this->vars['vip_data']['wcoins'] > 0 && $table_config['wcoins']['table'] != '' && $table_config['wcoins']['column'] != ''){
											$this->Mcharacter->add_wcoins($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $this->session->userdata(['user' => 'id']), $this->vars['vip_data']['wcoins'], $table_config['wcoins']);
										}
										$this->Maccount->add_account_log('Purchased vip ' . $this->vars['vip_data']['package_title'] . ' package for ' . $this->website->translate_credits($this->vars['vip_data']['payment_type'], $this->session->userdata(['user' => 'server'])) . '', -$this->vars['vip_data']['price'], $this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
                                        if($this->config->values('email_config', 'vip_purchase_email') == 1){
                                            $this->Maccount->sent_vip_purchase_email($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $this->session->userdata(['user' => 'email']), $this->vars['vip_data']['package_title'], $viptime);
                                        }
                                        $this->vars['success'] = __('You have successfully purchased vip package.');
                                    }
                                }
                            }
                        } else{
                            $this->vars['package_error'] = __('Invalid vip package selected.');
                        }
                        $this->load->view($this->config->config_entry('main|template') . DS . 'shop' . DS . 'view.buy_vip_package', $this->vars);
                    }
                }
            } else{
                $this->login();
            }
        }

        public function change_name(){
            if($this->session->userdata(['user' => 'logged_in'])){
                $this->load->model('character');
                $this->vars['char_list'] = $this->Mcharacter->load_char_list($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
                $this->load->view($this->config->config_entry('main|template') . DS . 'shop' . DS . 'view.change_name', $this->vars);
            } else{
                $this->login();
            }
        }

        public function change_name_history(){
            if($this->session->userdata(['user' => 'logged_in'])){
                $this->vars['change_history'] = $this->Mshop->change_name_history($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
                $this->load->view($this->config->config_entry('main|template') . DS . 'shop' . DS . 'view.change_name_history', $this->vars);
            } else{
                $this->login();
            }
        }

        public function login(){
            $this->load->view($this->config->config_entry('main|template') . DS . 'shop' . DS . 'view.login');
        }

        public function disabled(){
            $this->load->view($this->config->config_entry('main|template') . DS . 'view.module_disabled');
        }
    }