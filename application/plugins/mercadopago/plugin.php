<?php

    class _plugin_mercadopago extends controller implements pluginInterface
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
                        $this->load->model('application/plugins/'.$this->pluginaizer->get_plugin_class().'/models/'.$this->pluginaizer->get_plugin_class());
                        $this->vars['country'] = get_country_code(ip());
                        $this->vars['packages_mercadopago'] = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->load_packages(true);
                    }
                } else{
                    $this->vars['config_not_found'] = __('Plugin configuration not found.');
                }
                //set js
                $this->vars['js'] = $this->config->base_url . 'assets/plugins/js/mercadopago.js';
                //load template
                $this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . $this->config->config_entry('main|template') . DS . 'view.mercadopago', $this->vars);
            } else{
                $this->pluginaizer->redirect($this->config->base_url . 'account-panel/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()));
            }
        }

        /**
         *
         * Generate checkout data and checkout
         *
         * return mixed
         *
         */
        public function checkout(){
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
                            $this->pluginaizer->jsone(['error' => __('Plugin configuration not found.')]);
                        }
                    }
                    if($this->vars['plugin_config']['active'] == 0){
                        echo $this->pluginaizer->jsone(['error' => __('This module has been disabled.')]);
                    } else{
                        //$this->pluginaizer->csrf->verifyToken('post', 'json', 3600, true);
                        $this->load->model('application/plugins/'.$this->pluginaizer->get_plugin_class().'/models/'.$this->pluginaizer->get_plugin_class());
                        $id = isset($_POST['id']) ? (int)$_POST['id'] : '';
                        if($id == '')
                            echo $this->pluginaizer->jsone(['error' => __('Invalid MercadoPago package.')]); 
						else{
                            if($this->vars['package'] = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->check_package($id)){
                                require_once(APP_PATH . DS . 'plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'libraries' . DS . 'mercadopago.php');
                                
                                $country = get_country_code(ip());
                                
                                if(defined('MERCADO_KEYS') && isset(MERCADO_KEYS[$country])){
                                    $this->vars['mp'] = new MP('2.2.5', MERCADO_KEYS[$country]['client_id'], MERCADO_KEYS[$country]['client_secret']);
                                }
                                else{
                                    $this->vars['mp'] = new MP('2.2.5', $this->vars['plugin_config']['client_id'], $this->vars['plugin_config']['client_secret']);
                                }
                     
                                $fields = ['payment_amount' => number_format($this->vars['package']['price'], 0, '.', ','), 'currency' => $this->vars['package']['currency'], 'username' => $this->pluginaizer->session->userdata(['user' => 'username']), 'server' => $this->pluginaizer->session->userdata(['user' => 'server']), 'item_number' => md5($this->pluginaizer->session->userdata(['user' => 'username']) . $this->vars['package']['price'] . $this->vars['package']['currency'] . uniqid(microtime(), 1))];
                                if($this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->insert_order(filter_var($fields['payment_amount'], FILTER_SANITIZE_NUMBER_INT), $fields['currency'], $this->vars['package']['reward'], $fields['item_number'], $fields['username'], $fields['server'])){
										$order_data = [
										"items" => [[
											"id" => mt_rand(100000,999999),
											"title" => $this->vars['package']['reward'].' '.$this->pluginaizer->website->translate_credits($this->vars['plugin_config']['reward_type'], $this->pluginaizer->session->userdata(['user' => 'server'])), 
											"quantity" => 1, 
											"currency_id" => $fields['currency'], 
											"unit_price" => (int)$this->vars['package']['price']
										]],
										"payer" => [
											"email" => $this->pluginaizer->session->userdata(['user' => 'email'])
										],
										"back_urls" => [
											"success" => $this->config->base_url . str_replace('_', '-', $this->pluginaizer->get_plugin_class()) . '/thanks',
											"failure" => $this->config->base_url . str_replace('_', '-', $this->pluginaizer->get_plugin_class()) . '/failure',
											"pending" => $this->config->base_url . str_replace('_', '-', $this->pluginaizer->get_plugin_class()) . '/pending'
										],
										"notification_url" => $this->config->base_url . str_replace('_', '-', $this->pluginaizer->get_plugin_class()) . '/callback/'.$this->pluginaizer->session->userdata(['user' => 'server']).'/'.$country,
										"external_reference" => $fields['item_number']
									];

                                    $checkout = $this->vars['mp']->create_preference($order_data);

                                    if($checkout != false){
										if(isset($checkout['response']['error'])){
											 echo $this->pluginaizer->jsone(['error' => $checkout['response']['message']]);
										}
										else{
											echo $this->pluginaizer->jsone(['success' => $checkout['response']['init_point']]);
										}
                                    } else{
                                        echo $this->pluginaizer->jsone(['error' => $this->vars['error_message']]);
                                    }
                                } else{
                                    echo $this->pluginaizer->jsone(['error' => __('Unable to checkout please try again.')]);
                                }
                            } else{
                                echo $this->pluginaizer->jsone(['error' => 'Invalid package']);
                            }
                        }
                    }
                } else{
                    $this->pluginaizer->jsone(['error' => __('Plugin configuration not found.')]);
                }
            } else{
                echo $this->pluginaizer->jsone(['error' => __('Please login into website.')]);
            }
        }

        /**
         *
         * Redirect user after successfull checkout
         *
         * return string
         *
         */
        public function thanks(){
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
                    }
                } else{
                    $this->vars['config_not_found'] = __('Plugin configuration not found.');
                }
                $this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . $this->config->config_entry('main|template') . DS . 'view.mercadopago_thanks', $this->vars);
            } else{
                $this->pluginaizer->redirect($this->config->base_url . 'account-panel/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()));
            }
        }

        public function failure(){
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
                    }
                } else{
                    $this->vars['config_not_found'] = __('Plugin configuration not found.');
                }
                $this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . $this->config->config_entry('main|template') . DS . 'view.mercadopago_failure', $this->vars);
            } else{
                $this->pluginaizer->redirect($this->config->base_url . 'account-panel/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()));
            }
        }

        public function pending(){
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
                    }
                } else{
                    $this->vars['config_not_found'] = __('Plugin configuration not found.');
                }
                $this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . $this->config->config_entry('main|template') . DS . 'view.mercadopago_pending', $this->vars);
            } else{
                $this->pluginaizer->redirect($this->config->base_url . 'account-panel/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()));
            }
        }

        /**
         *
         * Proccess mercadopago request
         *
         *
         * return mixed
         *
         */
        public function callback($server = '', $country = ''){
            //load website helper
            $this->load->helper('website');
            $this->vars['plugin_config'] = $this->pluginaizer->plugin_config();
            if($this->vars['plugin_config'] != false && !empty($this->vars['plugin_config'])){
                if($this->pluginaizer->data()->value('is_multi_server') == 1){
                    if(array_key_exists($server, $this->vars['plugin_config'])){
                        $this->vars['plugin_config'] = $this->vars['plugin_config'][$server];
                    } else{
                        $this->writelog('Plugin configuration not found.', 'mercadopago');
                        http_response_code(400);
                        return;
                    }
                }
                if(!isset($_GET["id"], $_GET["topic"]) || !ctype_digit($_GET["id"])){
                    http_response_code(400);
                    return;
                }
                require_once(APP_PATH . DS . 'plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'libraries' . DS . 'mercadopago.php');
                
                if(defined('MERCADO_KEYS') && isset(MERCADO_KEYS[$country])){
                    $this->vars['mp'] = new MP('2.2.5', MERCADO_KEYS[$country]['client_id'], MERCADO_KEYS[$country]['client_secret']);
                }
                else{
                    $this->vars['mp'] = new MP('2.2.5', $this->vars['plugin_config']['client_id'], $this->vars['plugin_config']['client_secret']);
                }

                //$params = ["access_token" => $this->vars['mp']->get_access_token()];
                if($_GET["topic"] == 'payment'){
                    $payment_info = $this->vars['mp']->get(['uri' => '/collections/notifications/' . $_GET["id"]]);
                    //$payment_info = $this->vars['mp']->get("/collections/notifications/" . $_GET["id"], $params, false);
                    if($payment_info['status'] == 404){
                        $this->writelog($payment_info['response']['message'], 'mercadopago');
                        http_response_code(400);
                        return;
                    } else{
                        $merchant_order_info = $this->vars['mp']->get(['uri' => '/merchant_orders/' . $payment_info["response"]["collection"]["merchant_order_id"]]);
                        //$merchant_order_info = $this->vars['mp']->get("/merchant_orders/" . $payment_info["response"]["collection"]["merchant_order_id"], $params, false);
                    }
                } else if($_GET["topic"] == 'merchant_order'){
                    $merchant_order_info = $this->vars['mp']->get(['uri' => '/merchant_orders/' . $_GET["id"]]);
                    //$merchant_order_info = $this->vars['mp']->get("/merchant_orders/" . $_GET["id"], $params, false);
                }
                if($merchant_order_info["status"] == 200){
                    $this->load->model('application/plugins/'.$this->pluginaizer->get_plugin_class().'/models/'.$this->pluginaizer->get_plugin_class());
                    $this->load->model('account');
                    $transaction_amount_payments = 0;
                    if($merchant_order_info["response"]['status'] == 'closed'){
                        foreach($merchant_order_info["response"]["payments"] as $payment){
                            if($payment['status'] == 'approved'){
                                $transaction_amount_payments += $payment['transaction_amount'];
                            }
                        }
                        if($transaction_amount_payments >= $merchant_order_info["response"]["total_amount"]){
                            if(!$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->check_order_number($merchant_order_info["response"]['external_reference'])){
                                $this->writelog('Order not found: ' . $merchant_order_info["response"]['external_reference'], 'mercadopago');
                                $this->writelog('order info: ' . print_r($merchant_order_info, true), 'mercadopago');
                                header("HTTP/1.1 200 OK");
                            } 
							else{
                                if($this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->check_completed_transaction($merchant_order_info["response"]['external_reference'])){
                                    $this->writelog('Order already processed: ' . $merchant_order_info["response"]['external_reference'], 'mercadopago');
                                    $this->writelog('order info: ' . print_r($merchant_order_info, true), 'mercadopago');
                                    header("HTTP/1.1 200 OK");
                                } 
								else{
                                    $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->insert_transaction_status($_GET["id"], $merchant_order_info["response"]['external_reference']);
									$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->add_total_recharge($this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->order_details['account'], $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->order_details['server'], $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->order_details['credits']);
                                    $this->pluginaizer->Maccount->add_account_log(__('Reward').' ' . $this->pluginaizer->website->translate_credits($this->vars['plugin_config']['reward_type'], $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->order_details['server']) . ' MercadoPago', $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->order_details['credits'], $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->order_details['account'], $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->order_details['server']);
                                    $this->pluginaizer->website->add_credits($this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->order_details['account'], $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->order_details['server'], $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->order_details['credits'], $this->vars['plugin_config']['reward_type'], false, $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->get_guid($this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->order_details['account'], $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->order_details['server']));
                                    
									if($this->config->values('referral_config', 'reward_on_donation') > 0){
										$ref = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->findReferral($this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->order_details['account']);
										if($ref != false){
											$ref_reward = floor(($this->config->values('referral_config', 'reward_on_donation') / 100) * $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->order_details['credits']);
											$this->pluginaizer->website->add_credits($ref, $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->order_details['server'], $ref_reward, $this->vars['plugin_config']['reward_type'], false, $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->get_guid($this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->order_details['account'], $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->order_details['server']));
											$this->pluginaizer->Maccount->add_account_log('Friend donation bonus ' . $this->pluginaizer->website->translate_credits($this->vars['plugin_config']['reward_type'], $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->order_details['server']) . '', $ref_reward, $ref, $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->order_details['server']);
										}
									}
									
									if(defined('PARTNER_SYSTEM') && PARTNER_SYSTEM == true){
										$this->load->model('partner');
										$partner = $this->Mpartner->findLinkedPartner($this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->order_details['account'], $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->order_details['server']);
										if($partner != false){
											if($partner['dmn_linked_to'] != NULL){
												$partnerShare = $this->Mpartner->getPartnerShare($partner['dmn_linked_to'], $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->order_details['server']);
												$share = floor(($partnerShare / 100) * $merchant_order_info["response"]["total_amount"]);
												$this->Mpartner->updateShare($partner['dmn_linked_to'], $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->order_details['server'], $share);
												$this->Mpartner->logShare($partner['dmn_linked_to'], $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->order_details['server'], $share, $merchant_order_info["response"]["total_amount"], $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->order_details['account']);
											}
										}
									}
									header("HTTP/1.1 200 OK");
                                }
                            }
                        } else{
                            $this->writelog('Wrong transaction amount. Payment: ' . $transaction_amount_payments . ', order: ' . $merchant_order_info["response"]["total_amount"] . '', 'mercadopago');
                            $this->writelog('order info: ' . print_r($merchant_order_info, true), 'mercadopago');
                            header("HTTP/1.1 200 OK");
                        }
                    }
                }
            } else{
                $this->writelog('Plugin configuration not found.', 'mercadopago');
                http_response_code(400);
            }
        }

        /**
         *
         * Write payment log
         *
         * @param string $logentry
         * @param string $logname
         *
         *
         */
        private function writelog($logentry, $logname){
            $log = '[' . $this->pluginaizer->website->ip() . '] ' . $logentry;
            $logfile = @fopen(APP_PATH . DS . 'logs' . DS . $logname . '_' . date("m-d-y") . '.txt', "a+");
            if($logfile){
                fwrite($logfile, "[" . date("h:iA") . "] $log\r\n");
                fclose($logfile);
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
                $this->load->model('application/plugins/'.$this->pluginaizer->get_plugin_class().'/models/'.$this->pluginaizer->get_plugin_class());
                $this->vars['is_multi_server'] = $this->pluginaizer->data()->value('is_multi_server');
                $this->vars['plugin_config'] = $this->pluginaizer->plugin_config();
                $this->vars['packages_mercadopago'] = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->load_packages();
                //load any js, css files if required
                $this->vars['js'] = $this->config->base_url . 'assets/plugins/js/mercadopago.js';
                //load template
                $this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . 'admin' . DS . 'view.index', $this->vars);
            } else{
                $this->pluginaizer->redirect($this->config->base_url . 'admincp/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()) . '/admin');
            }
        }

        /**
         *
         * Add mercadopago package
         *
         *
         * Return mixed
         */
        public function add_package(){
            //check if visitor has administrator privilleges
            if($this->pluginaizer->session->is_admin()){
                //load website helper
                $this->load->helper('website');
                $this->load->model('application/plugins/'.$this->pluginaizer->get_plugin_class().'/models/'.$this->pluginaizer->get_plugin_class());
                $title = !empty($_POST['title']) ? htmlspecialchars($_POST['title']) : '';
                $price = (isset($_POST['price']) && is_numeric($_POST['price'])) ? $_POST['price'] : '';
                $currency = !empty($_POST['currency']) ? htmlspecialchars($_POST['currency']) : '';
                $reward = (isset($_POST['reward']) && preg_match('/^\d*$/', $_POST['reward'])) ? $_POST['reward'] : '';
                $server = !empty($_POST['server']) ? htmlspecialchars($_POST['server']) : '';
                $country = !empty($_POST['country']) ? htmlspecialchars($_POST['country']) : null;
                if($title == '')
                    echo $this->pluginaizer->jsone(['error' => 'Invalid package title']); 
                else{
                    if($price == '')
                        echo $this->pluginaizer->jsone(['error' => 'Invalid package price']); 
                    else{
                        if($currency == '')
                            echo $this->pluginaizer->jsone(['error' => 'Invalid package currency']); 
                        else{
                            if($server == '')
                                echo $this->pluginaizer->jsone(['error' => 'Invalid server selected']); 
                            else{
                                if($reward == '')
                                    echo $this->pluginaizer->jsone(['error' => 'Invalid package reward']); 
                                else{
                                    if($id = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->add_package($title, $price, $currency, $reward, $server, $country)){
                                        echo $this->pluginaizer->jsone(['success' => 'Package successfully added', 'id' => $id, 'server' => $server, 'servers' => $this->pluginaizer->website->server_list()]);
                                    } 
                                    else{
                                        echo $this->pluginaizer->jsone(['error' => 'Unable to add new package']);
                                    }
                                }
                            }
                        }
                    }
                }
            } else{
                $this->pluginaizer->jsone(['error' => 'Please login first!']);
            }
        }

        /**
         *
         * Edit mercadopago package
         *
         *
         * Return mixed
         */
        public function edit_package(){
            //check if visitor has administrator privilleges
            if($this->pluginaizer->session->is_admin()){
                //load website helper
                $this->load->helper('website');
                $this->load->model('application/plugins/'.$this->pluginaizer->get_plugin_class().'/models/'.$this->pluginaizer->get_plugin_class());
                $id = (isset($_POST['id']) && preg_match('/^\d*$/', $_POST['id'])) ? $_POST['id'] : '';
                $title = !empty($_POST['title']) ? htmlspecialchars($_POST['title']) : '';
                $price = (isset($_POST['price']) && is_numeric($_POST['price'])) ? $_POST['price'] : '';
                $currency = !empty($_POST['currency']) ? htmlspecialchars($_POST['currency']) : '';
                $reward = (isset($_POST['reward']) && preg_match('/^\d*$/', $_POST['reward'])) ? $_POST['reward'] : '';
                $server = !empty($_POST['server']) ? htmlspecialchars($_POST['server']) : '';
                $country = !empty($_POST['country']) ? htmlspecialchars($_POST['country']) : null;
                if($id == '')
                    echo $this->pluginaizer->jsone(['error' => 'Invalid package id']); 
                else{
                    if($title == '')
                        echo $this->pluginaizer->jsone(['error' => 'Invalid package title']); 
                    else{
                        if($price == '')
                            echo $this->pluginaizer->jsone(['error' => 'Invalid package price']); 
                        else{
                            if($currency == '')
                                echo $this->pluginaizer->jsone(['error' => 'Invalid package currency']); 
                            else{
                                if($server == '')
                                    echo $this->pluginaizer->jsone(['error' => 'Invalid server selected']); 
                                else{
                                    if($reward == '')
                                        echo $this->pluginaizer->jsone(['error' => 'Invalid package reward']); 
                                    else{
                                        if($this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->check_package($id)){
                                            $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->edit_package($id, $title, $price, $currency, $reward, $server, $country);
                                            echo $this->pluginaizer->jsone(['success' => 'Package successfully edited']);
                                        } 
                                        else{
                                            echo $this->pluginaizer->jsone(['error' => 'Invalid package']);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            } else{
                echo $this->pluginaizer->jsone(['error' => 'Please login first!']);
            }
        }

        /**
         *
         * Delete mercadopago package
         *
         *
         * Return mixed
         */
        public function delete_package(){
            //check if visitor has administrator privilleges
            if($this->pluginaizer->session->is_admin()){
                //load website helper
                $this->load->helper('website');
                $this->load->model('application/plugins/'.$this->pluginaizer->get_plugin_class().'/models/'.$this->pluginaizer->get_plugin_class());
                $id = (isset($_POST['id']) && preg_match('/^\d*$/', $_POST['id'])) ? $_POST['id'] : '';
                if($id == '')
                    echo $this->pluginaizer->jsone(['error' => 'Invalid package id']); else{
                    if($this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->check_package($id)){
                        $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->delete_package($id);
                        echo $this->pluginaizer->jsone(['success' => 'Package successfully removed']);
                    } else{
                        echo $this->pluginaizer->jsone(['error' => 'Invalid package']);
                    }
                }
            } else{
                echo $this->pluginaizer->jsone(['error' => 'Please login first!']);
            }
        }

        /**
         *
         * Enable / Disable mercadopago package
         *
         *
         * Return mixed
         */
        public function change_status(){
            //check if visitor has administrator privilleges
            if($this->pluginaizer->session->is_admin()){
                //load website helper
                $this->load->helper('website');
                $this->load->model('application/plugins/'.$this->pluginaizer->get_plugin_class().'/models/'.$this->pluginaizer->get_plugin_class());
                $id = (isset($_POST['id']) && preg_match('/^\d*$/', $_POST['id'])) ? $_POST['id'] : '';
                $status = (isset($_POST['status']) && preg_match('/^\d*$/', $_POST['status'])) ? $_POST['status'] : '';
                if($id == '')
                    echo $this->pluginaizer->jsone(['error' => 'Invalid package id']); else{
                    if($status == '')
                        echo $this->pluginaizer->jsone(['error' => 'Invalid package status']); else{
                        if($this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->check_package($id)){
                            $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->change_status($id, $status);
                            echo $this->pluginaizer->jsone(['success' => 'Package status changed']);
                        } else{
                            echo $this->pluginaizer->jsone(['error' => 'Invalid package']);
                        }
                    }
                }
            } else{
                echo $this->pluginaizer->jsone(['error' => 'Please login first!']);
            }
        }

        /**
         *
         * Save mercadopago package order
         *
         *
         * Return mixed
         */
        public function save_order(){
            //check if visitor has administrator privilleges
            if($this->pluginaizer->session->is_admin()){
                //load website helper
                $this->load->helper('website');
                $this->load->model('application/plugins/'.$this->pluginaizer->get_plugin_class().'/models/'.$this->pluginaizer->get_plugin_class());
                $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->save_order($_POST['order']);
            } else{
                echo $this->pluginaizer->jsone(['error' => 'Please login first!']);
            }
        }

        /**
         *
         * Generate mercadopago one logs
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
                $this->load->model('application/plugins/'.$this->pluginaizer->get_plugin_class().'/models/'.$this->pluginaizer->get_plugin_class());
                if(isset($_POST['search_mercadopago_transactions'])){
                    $server = (isset($_POST['server']) && $_POST['server'] != 'All') ? $_POST['server'] : 'All';
                    $acc = isset($_POST['account']) ? $_POST['account'] : '';
                    if($acc == ''){
                        $this->vars['error'] = 'Invalid account';
                    } else{
                        $this->vars['logs'] = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->load_transactions(1, 25, $acc, $server);
                        $this->pluginaizer->pagination->initialize(1, 25, $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->count_total_transactions($acc, $server), $this->config->base_url . 'mercadopagologs/%s/' . $acc . '/' . $server . '');
                        $this->vars['pagination'] = $this->pluginaizer->pagination->create_links();
                    }
                } else{
                    $this->vars['logs'] = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->load_transactions($page, 25, $acc, $server);
                    $lk = '';
                    if($acc != '')
                        $lk .= '/' . $acc;
                    $lk .= '/' . $server;
                    $this->pluginaizer->pagination->initialize($page, 25, $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->count_total_transactions($acc, $server), $this->config->base_url . 'mercadopago/logs/%s' . $lk);
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
                    'account_panel_item' => 0, //add link in user account panel
                    'donation_panel_item' => 1, //add link in donation page
                    'description' => 'Donate with MercadoPago' //description which will see user
                ]);
                //create plugin config template
                $this->pluginaizer->create_config(['active' => 0, 'client_id' => '', 'client_secret' => '', 'reward_type' => 0]);
                //add sql scheme if there is any into website database
                //all schemes should be located in plugin_folder/sql_schemes
                $this->pluginaizer->add_sql_scheme('mercadopago_packages');
                $this->pluginaizer->add_sql_scheme('mercadopago_orders');
                $this->pluginaizer->add_sql_scheme('mercadopago_transactions');
				
				$this->pluginaizer->website->db('web')->query('ALTER TABLE DmN_Donate_MercadoPago_Transactions ADD  CONSTRAINT [u_order_hash_'.time().'] UNIQUE NONCLUSTERED  (
					[order_hash] ASC
				)
				WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, IGNORE_DUP_KEY = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]');

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
                $this->pluginaizer->delete_config()->remove_sql_scheme('mercadopago_packages')->remove_sql_scheme('mercadopago_orders')->remove_sql_scheme('mercadopago_transactions')->remove_plugin();
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