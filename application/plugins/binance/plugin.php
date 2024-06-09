<?php

class _plugin_binance extends controller implements pluginInterface{
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
		}
		else{
			if($this->pluginaizer->data()->value('installed') == 1){
				if($this->pluginaizer->data()->value('is_public') == 0){
					$this->user_module();
				}
				else{
					$this->public_module();
				}
			}
			else{
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
					}
					else{
						$this->vars['config_not_found'] = __('Plugin configuration not found.');
					}
				}
				
				if($this->vars['plugin_config']['active'] == 0){
					$this->vars['module_disabled'] =  __('This module has been disabled.');
				}
				else{
					$this->load->model('application/plugins/'.$this->pluginaizer->get_plugin_class().'/models/'.$this->pluginaizer->get_plugin_class());;
					$this->vars['packages'] = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->load_packages(true);
				}
			}
			else{
				$this->vars['config_not_found'] = __('Plugin configuration not found.');
			}
			//set js
			$this->vars['js'] = $this->config->base_url . 'assets/plugins/js/'.$this->pluginaizer->get_plugin_class().'.js?v1';
			//load template
			$this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . $this->config->config_entry('main|template') . DS . 'view.index', $this->vars);
		}
		else{
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
	
	
	public function checkout($id = -1){
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
					}
					else{
						$this->vars['config_not_found'] = __('Plugin configuration not found.');
					}
				}
				if($this->vars['plugin_config']['active'] == 0){
					$this->vars['config_not_found'] = __('This module has been disabled.');
				}
				else{
					$this->load->model('application/plugins/'.$this->pluginaizer->get_plugin_class().'/models/'.$this->pluginaizer->get_plugin_class());
					$id = ($id != -1) ? (int)$id : '';
					if($id == '')
						$this->vars['config_not_found'] = __('Invalid package.');
					else{
						if($this->vars['package'] = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->check_package($id)){
							try{
								$params = [
								   'env' => [
									'terminalType' => 'WEB'
									], 
								   'merchantTradeNo' => md5($this->pluginaizer->session->userdata(['user' => 'username']) . $this->vars['package']['price'] . $this->vars['package']['currency'] . uniqid(microtime(), 1)), 
								   'orderAmount' => number_format($this->vars['package']['price'], 2, '.', ''), 
								   'currency' => $this->vars['package']['currency'], 
								   'goods' => [
										'goodsType' => '02', 
										'goodsCategory' => '6000', 
										'referenceGoodsId' => uniqid(microtime(), 1), 
										'goodsName' => $this->vars['package']['reward']. ' '.$this->pluginaizer->website->translate_credits($this->vars['plugin_config']['reward_type'], $this->pluginaizer->session->userdata(array('user' => 'server')))
									],
									'returnUrl' => $this->config->base_url . $this->pluginaizer->get_plugin_class().'/success',
									'cancelUrl' => $this->config->base_url . $this->pluginaizer->get_plugin_class().'/failed',
									'webhookUrl' => $this->config->base_url . $this->pluginaizer->get_plugin_class().'/notify',
								]; 
								$nonce = $this->generateNonce(32);
								$timestamp = round(microtime(true) * 1000);
								$jsonParams = json_encode($params);
								$payload = $timestamp."\n".$nonce."\n".$jsonParams."\n";
								$signature = strtoupper(hash_hmac('SHA512', $payload, $this->vars['plugin_config']['secret_key']));
								$headers = [];
								$headers[] = 'Content-Type: application/json';
								$headers[] = 'BinancePay-Timestamp: '.$timestamp;
								$headers[] = 'BinancePay-Nonce: '.$nonce;
								$headers[] = 'BinancePay-Certificate-SN: '.$this->vars['plugin_config']['api_key'];
								$headers[] = 'BinancePay-Signature: '.$signature;
								
								$paymentMethodCurl = curl_init();
								curl_setopt($paymentMethodCurl, CURLOPT_HTTPHEADER, $headers);
								curl_setopt($paymentMethodCurl, CURLOPT_URL, "https://bpay.binanceapi.com/binancepay/openapi/v2/order");
								curl_setopt($paymentMethodCurl, CURLOPT_RETURNTRANSFER, 1);
								curl_setopt($paymentMethodCurl, CURLOPT_POST, 1);
								curl_setopt($paymentMethodCurl, CURLOPT_POSTFIELDS, $jsonParams);
								curl_setopt($paymentMethodCurl, CURLOPT_SSL_VERIFYPEER, false);
								
								$raw_response = curl_exec($paymentMethodCurl);
								$paymentMethodCurlResponse = json_decode($raw_response, true);
								$paymentMethodCurlErr = curl_error($paymentMethodCurl);
								
								if($paymentMethodCurlErr != '') {
									throw new Exception('[Binance] Error creating Payment Method #'.$paymentMethodCurlErr);
								}
								if(isset($paymentMethodCurlResponse['status'])){
									if($paymentMethodCurlResponse['status'] == 'FAIL'){
										throw new Exception('[Binance] '.$paymentMethodCurlResponse['errorMessage']);
									}
									else{
										if($this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->insert_order(
											number_format($this->vars['package']['price'], 2, '.', ''),
											$this->vars['package']['currency'], 
											$this->vars['package']['reward'],
											$paymentMethodCurlResponse['data']['prepayId'],
											$this->pluginaizer->session->userdata(['user' => 'username']),
											$this->pluginaizer->session->userdata(['user' => 'server'])
										)){
											header('Location: '. $paymentMethodCurlResponse['data']['checkoutUrl']);
										}
										else{
											throw new Exception('[Binance] Error creating order');	
										}
									}
								}
								else{
									throw new Exception('[Binance] '.$paymentMethodCurlResponse['msg']);
								}
								curl_close($paymentMethodCurl);
							}
							catch(\Exception $e){
								$this->vars['error'] = $e->getMessage();
								$this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . $this->config->config_entry('main|template') . DS . 'view.message', $this->vars);
							}
						} 
						else{
							$this->vars['config_not_found'] = __('Invalid package');
						}
					}
				}
			}
			else{
				$this->vars['config_not_found'] = __('Plugin configuration not found.');
			}

			//load template
			if(isset($this->vars['config_not_found'])){
				$this->vars['error'] = $this->vars['config_not_found'];
				$this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . $this->config->config_entry('main|template') . DS . 'view.message', $this->vars);
			}
		}
		else{
			$this->pluginaizer->redirect($this->config->base_url . 'account-panel/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()));
		}
	}

		
	private function generateNonce($length = 10){
        $chars ='1234567890qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM';
        $char_len = strlen($chars)-1;
        $output = '';
        while(strlen($output) < $length){
            $output .= $chars[mt_rand(0, $char_len)];
        }
        return $output;
    }

	public function success(){
		if($this->pluginaizer->session->is_user()){
			$this->vars['plugin_config'] = $this->pluginaizer->plugin_config();
			if($this->vars['plugin_config'] != false && !empty($this->vars['plugin_config'])){
				if($this->pluginaizer->data()->value('is_multi_server') == 1){
					if(array_key_exists($this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['plugin_config'])){
						$this->vars['plugin_config'] = $this->vars['plugin_config'][$this->pluginaizer->session->userdata(['user' => 'server'])];
						$this->vars['about'] = $this->pluginaizer->get_about();
						$this->vars['about']['user_description'] = $this->pluginaizer->data()->value('description');
					}
					else{
						$this->vars['error'] = __('Plugin configuration not found.');
					}
				}
				if($this->vars['plugin_config']['active'] == 0){
					$this->vars['error'] = __('This module has been disabled.');
				}
				else{
					$this->vars['success'] = __('Transaction set to processing, pending confirmation by Binance');
				}
			}	
			$this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . $this->config->config_entry('main|template') . DS . 'view.message', $this->vars);
		}
		else{
			$this->pluginaizer->redirect($this->config->base_url . 'account-panel/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()));
		}
	}
	
	public function failed(){
		if($this->pluginaizer->session->is_user()){
			$this->vars['plugin_config'] = $this->pluginaizer->plugin_config();
			if($this->vars['plugin_config'] != false && !empty($this->vars['plugin_config'])){
				if($this->pluginaizer->data()->value('is_multi_server') == 1){
					if(array_key_exists($this->pluginaizer->session->userdata(['user' => 'server']), $this->vars['plugin_config'])){
						$this->vars['plugin_config'] = $this->vars['plugin_config'][$this->pluginaizer->session->userdata(['user' => 'server'])];
						$this->vars['about'] = $this->pluginaizer->get_about();
						$this->vars['about']['user_description'] = $this->pluginaizer->data()->value('description');
					}
					else{
						$this->vars['error'] = __('Plugin configuration not found.');
					}
				}
				if($this->vars['plugin_config']['active'] == 0){
					$this->vars['error'] = __('This module has been disabled.');
				}
				else{
					$this->vars['error'] = __('Transaction has been canceled.');
				}
			}	
			$this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . $this->config->config_entry('main|template') . DS . 'view.message', $this->vars);
		}
		else{
			$this->pluginaizer->redirect($this->config->base_url . 'account-panel/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()));
		}
	}
	
	
	public function notify(){
		$entityBody = file_get_contents('php://input');
        $webhookCall = json_decode($entityBody, true);
		$headers = $this->getAllheaders();
		$payload = $headers["binancepay-timestamp"] . "\n" . $headers["binancepay-nonce"] . "\n" . $entityBody . "\n";
        $decoded_signature = base64_decode($headers["binancepay-signature"]);


		$this->vars['plugin_config'] = $this->pluginaizer->plugin_config();
		
		if($this->vars['plugin_config'] != false && !empty($this->vars['plugin_config'])){
			if(isset($webhookCall['bizStatus']) && $webhookCall['bizStatus'] == 'PAY_SUCCESS'){
				$order_id = isset($webhookCall['bizId']) ? $webhookCall['bizId'] : null;
				$this->load->model('account');	
				$this->load->model('application/plugins/'.$this->pluginaizer->get_plugin_class().'/models/'.$this->pluginaizer->get_plugin_class());	
				if (!$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->check_order_number($order_id)){
					$this->writelog('Order not found: '. $order_id, 'binance');
					echo $this->pluginaizer->jsone(['returnCode' => 'SUCCESS', 'returnMessage' => null]);
					exit;
				}
				else{
					if($this->pluginaizer->data()->value('is_multi_server') == 1){
						if(array_key_exists($this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->order_details['server'], $this->vars['plugin_config'])){
							$this->vars['plugin_config'] = $this->vars['plugin_config'][$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->order_details['server']];
						}
						else{
							$this->writelog('Plugin configuration not found.', 'binance');
							echo $this->pluginaizer->jsone(['returnCode' => 'FAIL', 'returnMessage' => 'Plugin configuration not found']);
							exit;
						}
					}
					
					$timestamp = round(microtime(true) * 1000);
					$jsonParams = json_encode([]);
					$payload2 = $timestamp."\n". $headers["binancepay-nonce"] ."\n".$jsonParams."\n";
					$signature = strtoupper(hash_hmac('SHA512', $payload2, $this->vars['plugin_config']['secret_key']));
					$headers = [];
					$headers[] = 'Content-Type: application/json';
					$headers[] = 'BinancePay-Timestamp: '.$timestamp;
					$headers[] = 'BinancePay-Nonce: '.$headers["binancepay-nonce"];
					$headers[] = 'BinancePay-Certificate-SN: '.$this->vars['plugin_config']['api_key'];
					$headers[] = 'BinancePay-Signature: '.$signature;
					
					$apiCurl = curl_init();
					curl_setopt($apiCurl, CURLOPT_HTTPHEADER, $headers);
					curl_setopt($apiCurl, CURLOPT_URL, "https://bpay.binanceapi.com/binancepay/openapi/certificates");
					curl_setopt($apiCurl, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($apiCurl, CURLOPT_POST, 1);
					curl_setopt($apiCurl, CURLOPT_POSTFIELDS, $jsonParams);
					curl_setopt($apiCurl, CURLOPT_SSL_VERIFYPEER, false);
					
					$raw_response = curl_exec($apiCurl);
					$apiCurlResponse = json_decode($raw_response, true);
					$apiCurlErr = curl_error($apiCurl);
					
					if($apiCurlErr != '') {
						$this->writelog('[Binance] Error Api #'.$apiCurlErr, 'binance');
						echo $this->pluginaizer->jsone(['returnCode' => 'FAIL', 'returnMessage' => 'Binance api error']);
						exit;
					}
					
					if(openssl_verify($payload, $decoded_signature, $apiCurlResponse['data']['0']['certPublic'], OPENSSL_ALGO_SHA256) < 1){
						$this->writelog('Invalid signature.', 'binance');
						echo $this->pluginaizer->jsone(['returnCode' => 'FAIL', 'returnMessage' => 'Invalid signature']);
						exit;
					}
					
					$complete_transaction = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->check_completed_transaction($order_id);
					if($complete_transaction == false){
						$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->insert_transaction_status($this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->order_details['hash'], $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->order_details['hash'], 'completed');
						$this->pluginaizer->Maccount->add_account_log(
							'Reward ' . $this->pluginaizer->website->translate_credits($this->vars['plugin_config']['reward_type'], 
							$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->order_details['server']) . ' Binance', 
							$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->order_details['credits'], 
							$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->order_details['account'], 
							$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->order_details['server']
						);
						$this->pluginaizer->website->add_credits(
							$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->order_details['account'], 
							$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->order_details['server'], 
							$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->order_details['credits'],
							$this->vars['plugin_config']['reward_type'], 
							false,
							$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->get_guid($this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->order_details['account'], $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->order_details['server'])
						);
						
						$this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->add_total_recharge($this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->order_details['account'], $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->order_details['server'], $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->order_details['credits']);
				
						if($this->config->values('referral_config', 'reward_on_donation') > 0){
							$ref = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->findReferral($this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->order_details['account']);
							if($ref != false){
								$ref_reward = floor(($this->config->values('referral_config', 'reward_on_donation') / 100) * $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->order_details['credits']);
								$this->pluginaizer->website->add_credits($ref, $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->order_details['server'], $ref_reward, $this->vars['plugin_config']['reward_type'], false, $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->get_guid($this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->order_details['account'], $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->order_details['server']));
								$this->pluginaizer->Maccount->add_account_log('Friend donation bonus ' . $this->pluginaizer->website->translate_credits($this->vars['plugin_config']['reward_type'], $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->order_details['server']) . '', $ref_reward, $ref, $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->order_details['server']);
							}
						}
						echo $this->pluginaizer->jsone(['returnCode' => 'SUCCESS', 'returnMessage' => null]);
						exit;
					}	
				}
			}
		}
		else{
			$this->writelog('Plugin configuration not found.', 'binance');
			echo $this->pluginaizer->jsone(['returnCode' => 'FAIL', 'returnMessage' => 'Plugin configuration not found']);
			exit;
		}
	}
	
	private function getAllHeaders(){
		return array_change_key_case(getallheaders(), CASE_LOWER);
	}
	
	private function writelog($logentry, $logname){
        $log = '[' . $this->pluginaizer->website->ip() . '] ' . $logentry;
        $logfile = @fopen(APP_PATH . DS . 'logs' . DS . $logname . '_' . date("m-d-y") . '.txt', "a+");
        if ($logfile){
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
			
			$this->load->model('application/plugins/'.$this->pluginaizer->get_plugin_class().'/models/'.$this->pluginaizer->get_plugin_class());;
			
			$this->vars['is_multi_server'] = $this->pluginaizer->data()->value('is_multi_server');
			$this->vars['plugin_config'] = $this->pluginaizer->plugin_config();
			$this->vars['packages'] = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->load_packages();
			//load any js, css files if required
			$this->vars['js'] = $this->config->base_url . 'assets/plugins/js/'. $this->pluginaizer->get_plugin_class() .'.js';
			//load template
			$this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . 'admin' . DS . 'view.index', $this->vars);
		
		}
		else{
			$this->pluginaizer->redirect($this->config->base_url . 'admincp/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()) . '/admin');
		}
	}
	
	/**
	 *
	 * Add package
	 * 
	 *
	 * Return mixed
	 */
	
	public function add_package(){
        //check if visitor has administrator privilleges
		if($this->pluginaizer->session->is_admin()){
			//load website helper
			$this->load->helper('website');
			
			$this->load->model('application/plugins/'.$this->pluginaizer->get_plugin_class().'/models/'.$this->pluginaizer->get_plugin_class());;
			
            $title = !empty($_POST['title']) ? htmlspecialchars($_POST['title']) : '';
            $price = (isset($_POST['price']) && is_numeric($_POST['price'])) ? $_POST['price'] : '';
            $currency = !empty($_POST['currency']) ? htmlspecialchars($_POST['currency']) : '';
            $reward = (isset($_POST['reward']) && preg_match('/^\d*$/', $_POST['reward'])) ? $_POST['reward'] : '';
            $server = !empty($_POST['server']) ? htmlspecialchars($_POST['server']) : '';
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
                               echo  $this->pluginaizer->jsone(['error' => 'Invalid package reward']);
                            else{
								if($id = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->add_package($title, $price, $currency, $reward, $server)){
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
        } 
		else{
            $this->pluginaizer->jsone(['error' => 'Please login first!']);
        }
    }
	
	/**
	 *
	 * Edit package
	 * 
	 *
	 * Return mixed
	 */
	
	public function edit_package(){
        //check if visitor has administrator privilleges
		if($this->pluginaizer->session->is_admin()){
			//load website helper
			$this->load->helper('website');
			
			$this->load->model('application/plugins/'.$this->pluginaizer->get_plugin_class().'/models/'.$this->pluginaizer->get_plugin_class());;
			
            $id = (isset($_POST['id']) && preg_match('/^\d*$/', $_POST['id'])) ? $_POST['id'] : '';
            $title = !empty($_POST['title']) ? htmlspecialchars($_POST['title']) : '';
            $price = (isset($_POST['price']) && is_numeric($_POST['price'])) ? $_POST['price'] : '';
            $currency = !empty($_POST['currency']) ? htmlspecialchars($_POST['currency']) : '';
            $reward = (isset($_POST['reward']) && preg_match('/^\d*$/', $_POST['reward'])) ? $_POST['reward'] : '';
            $server = !empty($_POST['server']) ? htmlspecialchars($_POST['server']) : '';
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
                                    if($this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->check_package($id)) {
                                         $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->edit_package($id, $title, $price, $currency, $reward, $server);
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
        } 
		else{
            echo $this->pluginaizer->jsone(['error' => 'Please login first!']);
        }
    }
	
	/**
	 *
	 * Delete package
	 * 
	 *
	 * Return mixed
	 */
	
	public function delete_package(){
        //check if visitor has administrator privilleges
		if($this->pluginaizer->session->is_admin()){
			//load website helper
			$this->load->helper('website');
			
			$this->load->model('application/plugins/'.$this->pluginaizer->get_plugin_class().'/models/'.$this->pluginaizer->get_plugin_class());;
			
            $id = (isset($_POST['id']) && preg_match('/^\d*$/', $_POST['id'])) ? $_POST['id'] : '';
            if($id == '')
                echo $this->pluginaizer->jsone(['error' => 'Invalid package id']);
            else{
                if($this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->check_package($id)) {
                    $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->delete_package($id);
                    echo $this->pluginaizer->jsone(['success' => 'Package successfully removed']);
                } 
				else{
                    echo $this->pluginaizer->jsone(['error' => 'Invalid package']);
                }
            }
        } 
		else{
            echo $this->pluginaizer->jsone(['error' => 'Please login first!']);
        }
    }
	
	/**
	 *
	 * Enable / Disable package
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
                echo $this->pluginaizer->jsone(['error' => 'Invalid package id']);
            else{
                if($status == '')
                    echo $this->pluginaizer->jsone(['error' => 'Invalid package status']);
                else{
                    if($this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->check_package($id)) {
                        $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->change_status($id, $status);
                        echo $this->pluginaizer->jsone(['success' => 'Package status changed']);
                    } 
					else{
                        echo $this->pluginaizer->jsone(['error' => 'Invalid package']);
                    }
                }
            }
        } 
		else{
            echo $this->pluginaizer->jsone(['error' => 'Please login first!']);
        }
    }
	
	/**
	 *
	 * Save package order
	 * 
	 *
	 * Return mixed
	 */
	 
	public function save_order(){
         //check if visitor has administrator privilleges
		if($this->pluginaizer->session->is_admin()){
			//load website helper
			$this->load->helper('website');			
			$this->load->model('application/plugins/'.$this->pluginaizer->get_plugin_class().'/models/'.$this->pluginaizer->get_plugin_class());;	
            $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->save_order($_POST['order']);
        } 
		else{
             echo $this->pluginaizer->jsone(['error' => 'Please login first!']);
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
			
			$this->load->model('application/plugins/'.$this->pluginaizer->get_plugin_class().'/models/'.$this->pluginaizer->get_plugin_class());;	
			
			if(isset($_POST['search_transactions'])){
                $server = (isset($_POST['server']) && $_POST['server'] != 'All') ? $_POST['server'] : 'All';
                $acc = isset($_POST['account']) ? $_POST['account'] : '';
                if($acc == '') {
                    $this->vars['error'] = 'Invalid account';
                } 
				else{
                    $this->vars['logs'] = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->load_transactions(1, 25, $acc, $server);
                    $this->pluginaizer->pagination->initialize(1, 25, $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->count_total_transactions($acc, $server), $this->config->base_url . 'binance/logs/%s/' . $acc . '/' . $server . '');
                    $this->vars['pagination'] =  $this->pluginaizer->pagination->create_links();
                }
            } 
			else{
                $this->vars['logs'] = $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->load_transactions($page, 25, $acc, $server);
                $lk = '';
                if($acc != '')
                    $lk .= '/' . $acc;
                $lk .= '/' . $server;
                $this->pluginaizer->pagination->initialize($page, 25,  $this->pluginaizer->{'M'.$this->pluginaizer->get_plugin_class()}->count_total_transactions($acc, $server), $this->config->base_url . 'binance/logs/%s' . $lk);
                $this->vars['pagination'] =  $this->pluginaizer->pagination->create_links();
            }
			$this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . 'admin' . DS . 'view.logs', $this->vars);
		} 
		else{
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
			}
			else{
				foreach($_POST AS $key => $val){
					if($key != 'server'){
						$this->vars['plugin_config'][$key] = $val;
					}
				}
			}
			if($this->pluginaizer->save_config($this->vars['plugin_config'])){
				echo $this->pluginaizer->jsone(['success' => 'Plugin configuration successfully saved']);
			}
			else{
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
				'description' => 'Donate with Binance' //description which will see user
			]);
			
			//create plugin config template
			$this->pluginaizer->create_config([
				'active' => 0,
				'api_key' =>  '',
				'secret_key' => '',
				'reward_type' => 0
			]);
			//add sql scheme if there is any into website database
			//all schemes should be located in plugin_folder/sql_schemes
			$this->pluginaizer->add_sql_scheme('binance_packages');
			$this->pluginaizer->add_sql_scheme('binance_orders');
			$this->pluginaizer->add_sql_scheme('binance_transactions');
			//check for errors
			if(count($this->pluginaizer->error) > 0){
				$data['error'] = $this->pluginaizer->error;
			}
			$data['success'] = 'Plugin installed successfully';
			echo $this->pluginaizer->jsone($data);
		}
		else{
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
			$this->pluginaizer->delete_config()
			->remove_sql_scheme('binance_packages')
			->remove_sql_scheme('binance_orders')
			->remove_sql_scheme('binance_transactions')
			->remove_plugin();
			//check for errors
			if(count($this->pluginaizer->error) > 0){
				echo $this->pluginaizer->jsone(['error' => $this->pluginaizer->error]);
			}
			echo $this->pluginaizer->jsone(['success' => 'Plugin uninstalled successfully']);
		}
		else{
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
			}
			else{		
				echo $this->pluginaizer->jsone(['success' => 'Plugin successfully enabled.']);
			}
		}
		else{
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
			}
			else{		
				echo $this->pluginaizer->jsone(['success' => 'Plugin successfully disabled.']);
			}
		}
		else{
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
								  <dd>'.$about['name'].'</dd>
								  <dt>Version</dt>
								  <dd>'.$about['version'].'</dd>
								  <dt>Description</dt>
								  <dd>'.$about['description'].'</dd>
								  <dt>Developed By</dt>
								  <dd>'.$about['developed_by'].' <a href="'.$about['website'].'" target="_blank">'.$about['website'].'</a></dd>
								</dl>            
							</div>';
			}
			else{
				$description = '<div class="alert alert-info">Unable to find plugin description.</div>';
			}
			echo $this->pluginaizer->jsone(['about' => $description]);
		}
		else{
			echo $this->pluginaizer->jsone(['error' => 'Please login first!']);
		}
	}
}