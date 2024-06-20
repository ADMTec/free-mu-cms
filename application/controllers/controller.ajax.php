<?php
    in_file();

    class ajax extends controller
    {
        protected $vars = [], $errors = [];
		protected $resetSkillTreeClass = [2, 3, 7, 18, 19, 23, 34, 35, 39, 49, 50, 51, 54, 65, 66, 67, 70, 82, 83, 84, 87, 97, 98, 99, 102, 114, 115, 118, 130, 131, 135, 147, 151, 163, 167, 178, 179, 183, 194, 195, 199, 210, 211, 215, 15, 31, 47, 62, 78, 95, 110, 126, 143, 159, 175, 191, 207, 223];

        public function __construct(){
            parent::__construct();
            $this->load->helper('website');
            $this->load->lib('session', ['DmNCMS']);
			$this->session->checkSession();
			$this->load->lib('csrf');						 
            if($this->session->userdata(['user' => 'logged_in'])){
                if(!in_array($this->request->get_method(), ['event_timers', 'get_time'])){
                    if($this->config->values('scheduler_config', 'type') == 3){
                        file_get_contents($this->config->base_url . 'interface/web.php?key=' . $this->config->values('scheduler_config', 'key'));
                    }
                }
            }
        }

        public function index(){
            throw new exception('Nothing to see in here');
        }

		public function checkcaptcha(){
            if(isset($_POST['act'], $_POST['qaptcha_key'])){
                $_SESSION['qaptcha_key'] = false;
                if(htmlentities($_POST['act'], ENT_QUOTES, 'UTF-8') == 'qaptcha'){
                    $_SESSION['qaptcha_key'] = $_POST['qaptcha_key'];
                    json(['error' => false]);
                } else{
                    json(['error' => true]);
                }
            } else{
                json(['error' => true]);
            }
        }

		public function login(){
            if($this->session->userdata(['user' => 'logged_in'])){
                json(['error' => __('You are already logged in. Please logout first.')]);
            } else{
                $servers = $this->website->server_list();
                $default = array_keys($servers)[0];
                if(!isset($_POST['server'])){
                    $server = $default;
                } 
				else{
                    if(!array_key_exists($_POST['server'], $servers)){
                        $server = $default;
                    }
					else{
						$server = $_POST['server'];
					}
                }
				
                $this->load->model('account');
                $this->vars['config'] = $this->config->values('registration_config');
                if($this->vars['config'] != false && !empty($this->vars['config'])){
                    foreach($_POST as $key => $value){
                        $this->Maccount->$key = trim($value);
                    }

					try{
						$this->vars['security_config'] = $this->config->values('security_config');
						if($this->vars['security_config'] != false){
							if(isset($this->vars['security_config']['captcha_on_login']) && $this->vars['security_config']['captcha_on_login'] == 1){
								if(isset($this->Maccount->vars['captcha'])){
									$check = $this->check_captcha($this->Maccount->vars['captcha']);
									if($check == false){
										throw new Exception('error_captcha');
									}	
								}
							}
						}
						if($this->Maccount->check_login_attemts() == true){
							throw new Exception(__('You have reached max failed login attemts, please come back after 15 minutes.'));
						}
						if(!isset($this->Maccount->vars['username']) || $this->Maccount->vars['username'] == ''){
                           throw new Exception(__('You haven\'t entered a username.')); 
						}	
						if(!isset($this->Maccount->vars['password']) || $this->Maccount->vars['password'] == ''){
							throw new Exception(__('You haven\'t entered a password.')); 
						}							
						if(!$this->Maccount->valid_username($this->Maccount->vars['username'], '\w\W', [$this->vars['config']['min_username'], $this->vars['config']['max_username']])){
							throw new Exception(__('The username you entered is invalid.')); 
						}
						 if(!$this->Maccount->valid_password($this->Maccount->vars['password'], '\w\W', [$this->vars['config']['min_password'], $this->vars['config']['max_password']])){
							 throw new Exception(__('The password you entered is invalid.')); 
						 }
						 
						 $ban_info = $this->Maccount->check_acc_ban($this->Maccount->vars['username']);
						 
						 if($ban_info != false){
							if($ban_info['time'] > time() && $ban_info['is_permanent'] == 0){
								throw new Exception(sprintf(__('Your account is blocked until %s, reason: %s'), date(DATETIME_FORMAT, $ban_info['time']), $ban_info['reason']));
							} 
							if($ban_info['is_permanent'] == 1){
								throw new Exception(sprintf(__('Your account is blocked permanently. Reason: %s'), $ban_info['reason']));
							} 
						 }
						 
						 if($login = $this->Maccount->login_user($server)){			
							if(($this->vars['config']['email_validation'] == 1) && ($login['activated'] == 0)){
								$this->session->unset_session_key('user');
								throw new Exception(sprintf(__('Please activate your account first. <a id="repeat_activation" href="%s">Did not receive activation email?</a>'), $this->config->base_url . 'registration/resend-activation'));
							} else{
								if(isset($this->vars['security_config']['2fa']) && $this->vars['security_config']['2fa'] == 1){
									$this->vars['is_auth_enabled'] = $this->Maccount->check2FA($_POST['username']);
									if($this->vars['is_auth_enabled'] != false && !isset($_SESSION['tfa_complete'])){
										$this->session->unset_session_key('user');
										$_SESSION['tfa_temp_user'] = $_POST['username'];
										$_SESSION['tfa_temp_password'] = $_POST['password'];
										$_SESSION['tfa_temp_server'] = $server;
										$_SESSION['tfa_temp_servers'] = $servers;
										json(['tfa' => 'check']);
										exit;
									}
								}
								
								$this->Maccount->log_user_ip();
								$this->Maccount->clear_login_attemts();
								$this->change_user_vip_session($this->Maccount->vars['username'], $server);
								setcookie("DmN_Current_User_Server_" . $this->Maccount->vars['username'], $server, strtotime('+1 days', time()), "/");
								if(defined('IPS_CONNECT') && IPS_CONNECT == true){
									$this->load->lib('ipb');
									if($this->ipb->checkEmail($this->session->userdata(['user' => 'email'])) == true){
										$salt = $this->ipb->fetchSalt(2, $this->session->userdata(['user' => 'email']));
										$ipb_login_data = $this->ipb->login(2, $this->session->userdata(['user' => 'email']), $this->ipb->encrypt_password($this->Maccount->vars['password'], $salt));
										$this->session->session_key_overwrite('user', [0 => 'ipb_id', 1 => $ipb_login_data['connect_id']]);
										json(['success' => __('You have logged in successfully.'), 'ipb_login' => $this->ipb->crossLogin($ipb_login_data['connect_id'], $this->config->base_url . 'account-panel')]);																									  
									}
								}																														   
								json(['success' => __('You have logged in successfully.')]);
							}
						} 
						else{
							$this->Maccount->add_login_attemt();
							throw new Exception(__('Wrong username and/or password.'));
						}	 
					}
					catch(Exception $e){
						json(['error' => $e->getMessage()]);
					}
                   
                } else{
                    json(['error' => __('Registration settings has not yet been configured.')]);
                }
            }
        }

		public function switch_server(){
            if($this->session->userdata(['user' => 'logged_in'])){
                if(isset($_POST['server'])){
                    $server_list = $this->website->server_list();
                    if(array_key_exists($_POST['server'], $server_list)){
                        if($this->website->is_multiple_accounts() == true || defined('CUSTOM_SERVER_CODES')){
                            $this->load->model('account');
							$check = $this->Maccount->check_user_on_server($this->session->userdata(['user' => 'username']), $_POST['server']);
                            if($check != false){
								if(sha1($check['memb__pwd']) != $this->session->userdata(['user' => 'pass'])){
									json(['error' => __('Account password not match. Please logout and login again.')]);
								}
								else{
									$this->change_user_session_server($this->session->userdata(['user' => 'username']), $_POST['server']);
									$this->change_user_vip_session($this->session->userdata(['user' => 'username']), $_POST['server']);
								}
                                json(['success' => __('Server Changed.')]);
                            } 
							else{
                                json(['error' => __('You have not created account on this server. Please logout and create.')]);
                            }
                        } 
						else{
                            $this->change_user_session_server($this->session->userdata(['user' => 'username']), $_POST['server']);
                            $this->change_user_vip_session($this->session->userdata(['user' => 'username']), $_POST['server']);
                            json(['success' => __('Server Changed.')]);
                        }
                    } 
					else{
                        json(['error' => __('Invalid server selected.')]);
                    }
                } 
				else{
                    json(['error' => __('Invalid server selected.')]);
                }
            } 
			else{
                json(['error' => __('Please login into website.')]);
            }
        }

		private function change_user_session_server($user, $server){
            $this->session->session_key_overwrite('user', [0 => 'server', 1 => $server]);
            $this->session->session_key_overwrite('user', [0 => 'server_t', 1 => $this->website->get_value_from_server($server, 'title')]);
            setcookie("DmN_Current_User_Server_" . $user, $server, strtotime('+1 days', time()), "/");
        }

		private function change_user_vip_session($user, $server){
			$this->vars['config'] = $this->config->values('vip_config');
			
			if(!empty($this->vars['config']) && $this->vars['config']['active'] == 1){
				$this->load->model('account');
				if($this->vars['vip_data'] = $this->Maccount->check_vip($user, $server)){
					$this->vars['vip_package_info'] = $this->Maccount->load_vip_package_info($this->vars['vip_data']['viptype'], $server);
					if($this->vars['vip_data']['viptime'] <= time()){
						$this->Maccount->remove_vip($this->vars['vip_data']['viptype'], $user, $server);
						if($this->vars['vip_package_info'] != false){
							$this->Maccount->check_connect_member_file($this->vars['vip_package_info']['connect_member_load'], $user);
						}
					} else{
						$this->Maccount->set_vip_session($this->vars['vip_data']['viptime'], $this->vars['vip_package_info']);
					}
				} else{
					$this->session->unset_session_key('vip');
				}
			}
        }

		public function change_password(){
            if($this->session->userdata(['user' => 'logged_in'])){
                $this->vars['config'] = $this->config->values('registration_config');
                $this->load->model('account');
                foreach($_POST as $key => $value){
                    $this->Maccount->$key = trim($value);
                }
                if(!isset($this->Maccount->vars['old_password']))
                    json(['error' => __('You haven\'t entered your current password.')]); 
				else{
                    if(!$this->Maccount->compare_passwords())
                        json(['error' => __('The current password you entered is wrong.')]); 
					else{
                        if(!isset($this->Maccount->vars['new_password']))
                            json(['error' => __('You haven\'t entered your new password.')]); 
						else{
                            if(!$this->Maccount->valid_password($this->Maccount->vars['new_password']))
                                json(['error' => __('The new password you entered is invalid.')]); 
							else{
                                $this->Maccount->test_password_strength($this->Maccount->vars['new_password'], [$this->vars['config']['min_password'], $this->vars['config']['max_password']], $this->vars['config']['password_strength']);
                                if(isset($this->Maccount->errors))
                                    json(['error' => $this->Maccount->vars['errors']]); 
								else{
                                    if(!isset($this->Maccount->vars['new_password2']))
                                        json(['error' => __('You haven\'t entered new password-repetition.')]); 
									else{
                                        if($this->Maccount->vars['new_password'] != $this->Maccount->vars['new_password2'])
                                            json(['error' => __('The two passwords you entered do not match.')]); 
										else{
                                            if($this->Maccount->vars['old_password'] == $this->Maccount->vars['new_password'])
                                                json(['error' => __('New password cannot be same as old!')]); 
											else{
                                                if($this->Maccount->update_password($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']))){
													$this->Maccount->add_account_log('Changed password.', 0, $this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));          
                                                    $this->session->destroy();
                                                    json(['success' => [__('Your password was successfully changed.'), __('You\'ve been logged out for security reasons!')]]);
                                                } else{
                                                    json(['error' => __('Password could not be updated.')]);
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
                json(['error' => __('Please login into website.')]);
            }
        }

        public function change_email(){
            if($this->session->userdata(['user' => 'logged_in'])){
				$this->vars['security_config'] = $this->config->values('security_config');
				if(isset($this->vars['security_config']['allow_mail_change']) && $this->vars['security_config']['allow_mail_change'] == 1){
                    $this->load->model('account');
                    foreach($_POST as $key => $value){
                        $this->Maccount->$key = trim($value);
                    }
                    if(!isset($this->Maccount->vars['email']))
                        json(['error' => __('You haven\'t entered your current email.')]); 
					else{
                        if(!$this->Maccount->valid_email($this->Maccount->vars['email']))
                            json(['error' => __('You have entered an invalid email-address.')]); 
						else{
                            if(!$this->Maccount->check_existing_email($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server'])))
                                json(['error' => __('Email-address is wrong for this account.')]); 
							else{
                                if($this->Maccount->create_email_confirmation_entry($this->session->userdata(['user' => 'username']), 1)){
                                    if($this->Maccount->send_email_confirmation($this->session->userdata(['user' => 'username']))){
                                        json(['success' => __('Please check your current mail-box for confirmation link.')]);
                                    } else{
                                        $this->Maccount->delete_old_confirmation_entries($this->session->userdata(['user' => 'username']), 1);
                                        json(['error' => $this->Maccount->error]);
                                    }
                                } else{
                                    json(['error' => __('Unable to write confirmation code into database.')]);
                                }
                            }
                        }
                    }
                }
            } else{
                json(['error' => __('Please login into website.')]);
            }
        }

        public function set_new_email(){
            if($this->session->userdata(['user' => 'logged_in'])){
                $this->vars['security_config'] = $this->config->values('security_config');
				if(isset($this->vars['security_config']['allow_mail_change']) && $this->vars['security_config']['allow_mail_change'] == 1){
                    $this->load->model('account');
                    foreach($_POST as $key => $value){
                        $this->Maccount->$key = trim($value);
                    }
                    if(!isset($this->Maccount->vars['email']))
                        json(['error' => __('You haven\'t entered your new email-address.')]); 
					else{
                        if(!$this->Maccount->valid_email($this->Maccount->vars['email']))
                            json(['error' => __('You have entered an invalid email-address.')]); 
						else{
                            if($this->Maccount->check_duplicate_email($this->Maccount->vars['email'], $this->session->userdata(['user' => 'server'])))
                                json(['error' => __('This email-address is already used.')]); 
							else{
                                if($this->Maccount->create_email_confirmation_entry($this->session->userdata(['user' => 'username']), 0)){
                                    if($this->Maccount->send_email_confirmation($this->session->userdata(['user' => 'username']))){
                                        $this->Maccount->delete_old_confirmation_entries($this->session->userdata(['user' => 'username']), 1);
                                        json(['success' => __('Please check your new mail-box for confirmation link.')]);
                                    } else{
                                        $this->Maccount->delete_old_confirmation_entries($this->session->userdata(['user' => 'username']));
                                        json(['error' => $this->Maccount->error]);
                                    }
                                } else{
                                    json(['error' => __('Unable to write confirmation code into database.')]);
                                }
                            }
                        }
                    }
                }
            } else{
                json(['error' => __('Please login into website.')]);
            }
        }

        public function status(){
            if($this->session->userdata(['user' => 'logged_in'])){
                json(['success' => true]);
            } else{
                json(['error' => __('Please login into website.')]);
            }
        }

		public function checkcredits(){
            if($this->session->userdata(['user' => 'logged_in'])){
                $this->load->model('account');
                $payment_method = (isset($_POST['payment_method']) && ctype_digit($_POST['payment_method'])) ? (int)$_POST['payment_method'] : '';
                $credits = (isset($_POST['credits']) && ctype_digit($_POST['credits'])) ? (int)$_POST['credits'] : '';
                $gcredits = (isset($_POST['gcredits']) && ctype_digit($_POST['gcredits'])) ? (int)$_POST['gcredits'] : '';
                if(!in_array($payment_method, [1, 2]))
                    json(['error' => __('Invalid payment method.')]); 
				else if($credits === '')
                    json(['error' => sprintf(__('Invalid amount of %s'), $this->website->translate_credits(1, $this->session->userdata(['user' => 'server'])))]);
                else if($gcredits === '')
                    json(['error' => sprintf(__('Invalid amount of %s'), $this->website->translate_credits(2, $this->session->userdata(['user' => 'server'])))]);
                else{
                    $status = $this->website->get_user_credits_balance($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $payment_method, $this->session->userdata(['user' => 'id']));
                    if($payment_method == 1){
                        if($status['credits'] < $credits){
                            json(['error' => sprintf(__('You have insufficient amount of %s'), $this->website->translate_credits(1, $this->session->userdata(['user' => 'server'])))]);
                        } else{
                            json(['success' => true]);
                        }
                    }
                    if($payment_method == 2){
                        if($status['credits'] < $gcredits){
                            json(['error' => sprintf(__('You have insufficient amount of %s'), $this->website->translate_credits(1, $this->session->userdata(['user' => 'server'])))]);
                        } else{
                            json(['success' => true]);
                        }
                    }
                }
            } else{
                json(['error' => __('Please login into website.')]);
            }
        }

		public function vote(){
            if($this->session->userdata(['user' => 'logged_in'])){
                $this->vars['votereward_config'] = $this->config->values('votereward_config', $this->session->userdata(['user' => 'server']));
                if($this->vars['votereward_config']['active'] == 1){
                    $this->load->model('account');
					$this->load->model('character');
                    if(isset($_POST['vote']) && ctype_digit($_POST['vote'])){
						if($this->vars['votereward_config']['req_char'] == 1){
							$this->vars['has_char'] = $this->Mcharacter->load_char_list($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
						}
						if(isset($this->vars['has_char']) && $this->vars['has_char'] == false){
							 json(['error' => __('Voting require character.')]);
						}
						if(isset($this->vars['has_char'])) {
							$lvl_total = 0;
							$res_total = 0;
							foreach ($this->vars['has_char'] as $key => $value) {
								$lvl_total += $value['level'];
								$res_total += $value['resets'];
							}

							if ($this->vars['votereward_config']['req_lvl'] > $lvl_total) {
								json(['error' => __('Your character total level sum need to be atleast') . ' ' . $this->vars['votereward_config']['req_lvl']]);
							}
							if ($this->vars['votereward_config']['req_res'] > $res_total) {
								json(['error' => __('Your character total res sum need to be atleast') . ' ' . $this->vars['votereward_config']['req_res']]);
							}
						}
                        if(!$check_link = $this->Maccount->check_vote_link($_POST['vote'], $this->session->userdata(['user' => 'server']))){
                            json(['error' => __('Voting link not found.')]);
                        } else{
                            if($check_link['api'] == 2){
                                json(['success_mmotop' => __('Thank You, we will review your vote and reward you.')]);
                            } else{
                                if($check_last_vote = $this->Maccount->get_last_vote($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $_POST['vote'], $check_link['hours'], 0, $this->vars['votereward_config']['xtremetop_same_acc_vote'], $this->vars['votereward_config']['xtremetop_link_numbers'])){
                                    json(['error' => sprintf(__('Already voted. Next vote after %s'), $this->Maccount->calculate_next_vote($check_last_vote, $check_link['hours']))]);
                                } else{
                                    if($check_link['api'] == 1){
                                        if($valid_votes = $this->Maccount->check_xtremetop_vote()){
                                            if(!empty($valid_votes)){
                                                $count = count($valid_votes);
                                                $i = 0;
                                                foreach($valid_votes AS $valid){
                                                    $i++;
                                                    $this->Maccount->set_valid_vote_xtremetop($valid['id']);
                                                    $this->Maccount->reward_voter($check_link['reward'], $check_link['reward_type'], $this->session->userdata(['user' => 'server']), $this->session->userdata(['user' => 'username']), $check_link['name']);
                                                    $this->Maccount->check_vote_rankings($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
                                                    if($i == $count){
                                                        if($this->Maccount->log_vote($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $_POST['vote'])){
                                                            json(['success' => vsprintf(__('Vote was successful. You have received %d %s'), [$check_link['reward'], $this->website->translate_credits($check_link['reward_type'], $this->session->userdata(['user' => 'server']))]), 'next_vote' => $this->Maccount->calculate_next_vote((time() - 60), $check_link['hours']), 'reward' => $check_link['reward']]);
                                                        } else{
                                                            json(['error' => __('Unable to log vote. Please try again latter')]);
                                                        }
                                                    }
                                                }
                                            } else{
                                                json(['error' => __('Unable to log vote. Please try again latter')]);
                                            }
                                        } else{
                                            json(['error' => __('Unable to validate vote. Please try again after few minutes.')]);
                                        }
                                    } else if($check_link['api'] == 3){
                                        if($valid_votes = $this->Maccount->check_gtop100_vote()){
                                            if(!empty($valid_votes)){
                                                $count = count($valid_votes);
                                                $i = 0;
                                                foreach($valid_votes AS $valid){
                                                    $i++;
                                                    $this->Maccount->set_valid_vote_gtop100($valid['id']);
                                                    $this->Maccount->reward_voter($check_link['reward'], $check_link['reward_type'], $this->session->userdata(['user' => 'server']), $this->session->userdata(['user' => 'username']), $check_link['name']);
                                                    $this->Maccount->check_vote_rankings($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
                                                    if($i == $count){
                                                        if($this->Maccount->log_vote($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $_POST['vote'])){
                                                            json(['success' => vsprintf(__('Vote was successful. You have received %d %s'), [$check_link['reward'], $this->website->translate_credits($check_link['reward_type'], $this->session->userdata(['user' => 'server']))]), 'next_vote' => $this->Maccount->calculate_next_vote((time() - 60), $check_link['hours']), 'reward' => $check_link['reward']]);
                                                        } else{
                                                            json(['error' => __('Unable to log vote. Please try again latter')]);
                                                        }
                                                    }
                                                }
                                            } else{
                                                json(['error' => __('Unable to validate vote. Please try again after few minutes.')]);
                                            }
                                        } else{
                                            json(['error' => __('Unable to validate vote. Please try again after few minutes.')]);
                                        }
                                    } else if($check_link['api'] == 4){
                                        if($valid_votes = $this->Maccount->check_topg_vote()){
                                            if(!empty($valid_votes)){
                                                $count = count($valid_votes);
                                                $i = 0;
                                                foreach($valid_votes AS $valid){
                                                    $i++;
                                                    $this->Maccount->set_valid_vote_topg($valid['id']);
                                                    $this->Maccount->reward_voter($check_link['reward'], $check_link['reward_type'], $this->session->userdata(['user' => 'server']), $this->session->userdata(['user' => 'username']), $check_link['name']);
                                                    $this->Maccount->check_vote_rankings($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
                                                    if($i == $count){
                                                        if($this->Maccount->log_vote($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $_POST['vote'])){
                                                            json(['success' => vsprintf(__('Vote was successful. You have received %d %s'), [$check_link['reward'], $this->website->translate_credits($check_link['reward_type'], $this->session->userdata(['user' => 'server']))]), 'next_vote' => $this->Maccount->calculate_next_vote((time() - 60), $check_link['hours']), 'reward' => $check_link['reward']]);
                                                        } else{
                                                            json(['error' => __('Unable to log vote. Please try again latter')]);
                                                        }
                                                    }
                                                }
                                            } else{
                                                json(['error' => __('Unable to validate vote. Please try again after few minutes.')]);
                                            }
                                        } else{
                                            json(['error' => __('Unable to validate vote. Please try again after few minutes.')]);
                                        }
                                    } else if($check_link['api'] == 5){
                                        if($valid = $this->Maccount->check_top100arena_vote()){
                                            if($this->Maccount->log_vote($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $_POST['vote']) && $this->Maccount->set_valid_vote_top100arena($valid['id'])){
                                                $this->Maccount->reward_voter($check_link['reward'], $check_link['reward_type'], $this->session->userdata(['user' => 'server']), $this->session->userdata(['user' => 'username']), $check_link['name']);
                                                $this->Maccount->check_vote_rankings($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
                                                json(['success' => vsprintf(__('Vote was successful. You have received %d %s'), [$check_link['reward'], $this->website->translate_credits($check_link['reward_type'], $this->session->userdata(['user' => 'server']))]), 'next_vote' => $this->Maccount->calculate_next_vote((time() - 60), $check_link['hours']), 'reward' => $check_link['reward']]);
                                            } else{
                                                json(['error' => __('Unable to log vote. Please try again latter')]);
                                            }
                                        } else{
                                            json(['error' => __('Unable to validate vote. Please try again after few minutes.')]);
                                        }
                                    } else if($check_link['api'] == 6){
                                        if($valid = $this->Maccount->check_mmoserver_vote()){
                                            if($this->Maccount->log_vote($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $_POST['vote']) && $this->Maccount->set_valid_vote_mmoserver($valid['id'])){
                                                $this->Maccount->reward_voter($check_link['reward'], $check_link['reward_type'], $this->session->userdata(['user' => 'server']), $this->session->userdata(['user' => 'username']), $check_link['name']);
                                                $this->Maccount->check_vote_rankings($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
                                                json(['success' => vsprintf(__('Vote was successful. You have received %d %s'), [$check_link['reward'], $this->website->translate_credits($check_link['reward_type'], $this->session->userdata(['user' => 'server']))]), 'next_vote' => $this->Maccount->calculate_next_vote((time() - 60), $check_link['hours']), 'reward' => $check_link['reward']]);
                                            } else{
                                                json(['error' => __('Unable to log vote. Please try again latter')]);
                                            }
                                        } else{
                                            json(['error' => __('Unable to validate vote. Please try again after few minutes.')]);
                                        }
                                    } else if($check_link['api'] == 8){
                                        if($valid_votes = $this->Maccount->check_dmncms_vote()){
                                            if(!empty($valid_votes)){
                                                $count = count($valid_votes);
                                                $i = 0;
                                                foreach($valid_votes AS $valid){
                                                    $i++;
                                                    $this->Maccount->set_valid_vote_dmncms($valid['id']);
                                                    $this->Maccount->reward_voter($check_link['reward'], $check_link['reward_type'], $this->session->userdata(['user' => 'server']), $this->session->userdata(['user' => 'username']), $check_link['name']);
                                                    $this->Maccount->check_vote_rankings($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
                                                    if($i == $count){
                                                        if($this->Maccount->log_vote($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $_POST['vote'])){
                                                            json(['success' => vsprintf(__('Vote was successful. You have received %d %s'), [$check_link['reward'], $this->website->translate_credits($check_link['reward_type'], $this->session->userdata(['user' => 'server']))]), 'next_vote' => $this->Maccount->calculate_next_vote((time() - 60), $check_link['hours']), 'reward' => $check_link['reward']]);
                                                        } else{
                                                            json(['error' => __('Unable to log vote. Please try again latter')]);
                                                        }
                                                    }
                                                }
                                            } else{
                                                json(['error' => __('Unable to log vote. Please try again latter')]);
                                            }
                                        } else{
                                            json(['error' => __('Unable to validate vote. Please try again after few minutes.')]);
                                        }
                                    } else if($check_link['api'] == 9){
                                        if($valid_votes = $this->Maccount->check_gametop100_vote()){
                                            if(!empty($valid_votes)){
                                                $count = count($valid_votes);
                                                $i = 0;
                                                foreach($valid_votes AS $valid){
                                                    $i++;
                                                    $this->Maccount->set_valid_vote_gametop100($valid['id']);
                                                    $this->Maccount->reward_voter($check_link['reward'], $check_link['reward_type'], $this->session->userdata(['user' => 'server']), $this->session->userdata(['user' => 'username']), $check_link['name']);
                                                    $this->Maccount->check_vote_rankings($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
                                                    if($i == $count){
                                                        if($this->Maccount->log_vote($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $_POST['vote'])){
                                                            json(['success' => vsprintf(__('Vote was successful. You have received %d %s'), [$check_link['reward'], $this->website->translate_credits($check_link['reward_type'], $this->session->userdata(['user' => 'server']))]), 'next_vote' => $this->Maccount->calculate_next_vote((time() - 60), $check_link['hours']), 'reward' => $check_link['reward']]);
                                                        } else{
                                                            json(['error' => __('Unable to log vote. Please try again latter')]);
                                                        }
                                                    }
                                                }
                                            } else{
                                                json(['error' => __('Unable to log vote. Please try again latter')]);
                                            }
                                        } else{
                                            json(['error' => __('Unable to validate vote. Please try again after few minutes.')]);
                                        }
                                    } else{
                                        if($this->Maccount->log_vote($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $_POST['vote'])){
                                            $this->Maccount->reward_voter($check_link['reward'], $check_link['reward_type'], $this->session->userdata(['user' => 'server']), $this->session->userdata(['user' => 'username']), $check_link['name']);
                                            $this->Maccount->check_vote_rankings($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
                                            json(['success' => vsprintf(__('Vote was successful. You have received %d %s'), [$check_link['reward'], $this->website->translate_credits($check_link['reward_type'], $this->session->userdata(['user' => 'server']))]), 'next_vote' => $this->Maccount->calculate_next_vote((time() - 60), $check_link['hours']), 'reward' => $check_link['reward']]);
                                        } else{
                                           json(['error' => __('Unable to log vote. Please try again latter')]);
                                        }
                                    }
                                }
                            }
                        }
                    } else{
                        json(['error' => __('Invalid voting link.')]);
                    }
                } else{
                    json(['error' => __('Module disabled.')]);
                }
            } else{
                json(['error' => __('Please login into website.')]);
            }
        }

		public function reset_character(){
            if($this->session->userdata(['user' => 'logged_in'])){				
				try{	
					$this->load->model('account');
					$this->load->model('character');
					
					foreach($_POST as $key => $value){
						if($key == 'character'){
							$this->Mcharacter->$key = trim($this->website->hex2bin($value));
						} else{
							$this->Mcharacter->$key = trim($value);
						}
					}

					if(!$this->Maccount->check_connect_stat($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']))){
						throw new \Exception(__('Please logout from game.')); 
					}
					
					if(!$this->Mcharacter->check_char($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), '', '', false)){
						throw new \Exception(__('Character not found.')); 
					}
					
					if(!isset($_POST['character']) || $_POST['character'] == ''){
						throw new \Exception(__('Invalid Character'));
					}
					
					$reset_config = $this->config->values('reset_config', $this->session->userdata(['user' => 'server']));
					$greset_config = $this->config->values('greset_config', $this->session->userdata(['user' => 'server']));
					
					if(defined('CUSTOM_RESET_REQ_ITEMS') && CUSTOM_RESET_REQ_ITEMS == true){
						$reset_items_config = $this->config->values('reset_items_config', $this->session->userdata(['user' => 'server']));
					}
					
					if(!$reset_config){
						throw new \Exception(__('Reset configuration for this server not found.')); 
					}
					
					if($reset_config['allow_reset'] == 0){
						throw new \Exception(__('Reset function is disabled for this server')); 
					}
					
					unset($reset_config['allow_reset']);
					if($greset_config != false && !empty($greset_config)){
						unset($greset_config['allow_greset']);
					}
												 
					foreach($reset_config AS $key => $values){
						list($start_res, $end_res) = explode('-', $key);
						if($this->Mcharacter->char_info['resets'] >= $start_res && $this->Mcharacter->char_info['resets'] < $end_res){
							if(defined('RESET_NORIA_IS_VIP_RESET') && RESET_NORIA_IS_VIP_RESET == true){
								if(isset($_POST['vip']) && $_POST['vip'] == 1){
									$values['level_after_reset'] = RESET_NORIA_VIP_LVL_AFTER_RESET;
								}
							}
							if(defined('CUSTOM_RESET_NORIA') && CUSTOM_RESET_NORIA == true){
								$addonLVL = ($this->Mcharacter->char_info['resets'] > 0) ? $this->Mcharacter->char_info['resets'] * RESET_NORIA_LVL_INCREASE : 0;
								$values['level'] = $values['level'] + $addonLVL;
								if($values['level'] > 400){
									$values['level'] = 400;
								}
							}
							$this->Mcharacter->char_info['res_info'] = $values;
							if(defined('CUSTOM_RESET_REQ_ITEMS') && CUSTOM_RESET_REQ_ITEMS == true){
								$this->load->lib('iteminfo');
								$this->load->lib('createitem', [MU_VERSION, SOCKET_LIBRARY, $this->website->get_value_from_server($this->session->userdata(['user' => 'server']), 'item_size')]);
								if(!empty($reset_items_config[$start_res.'-'.$end_res])){
									foreach($reset_items_config[$start_res.'-'.$end_res] AS $cat => $items){
										if(count($items) > 0){
											$check = $this->website->checkResetItem($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $_POST['character'], $start_res.'-'.$end_res, $cat);													
											if($check == false){
												$randItem = array_rand($items, 1);
												if($this->iteminfo->setItemData($items[$randItem]['id'], $items[$randItem]['cat'], $this->website->get_value_from_server($this->session->userdata(['user' => 'server']), 'item_size'))){
													$this->createitem->setItemData($this->iteminfo->item_data);
													$this->createitem->id($items[$randItem]['id']);
													$this->createitem->cat($items[$randItem]['cat']);
													$this->createitem->refinery(false);
													$this->createitem->serial(0);
													if($this->website->get_value_from_server($this->session->userdata(['user' => 'server']), 'item_size') == 64){
														$this->createitem->serial2(true);
													}
													if($items[$randItem]['minLvl'] == $items[$randItem]['maxLvl']){
														$this->createitem->lvl($items[$randItem]['minLvl']);
													}
													else{
														$this->createitem->lvl(rand($items[$randItem]['minLvl'], $items[$randItem]['maxLvl']));
														
													}
													$this->createitem->skill(false);
													$this->createitem->luck(false);
													if($items[$randItem]['minOpt'] == $items[$randItem]['maxOpt']){
														$this->createitem->opt($items[$randItem]['minOpt']);
													}
													else{
														$this->createitem->opt(rand($items[$randItem]['minOpt'], $items[$randItem]['maxOpt']));
														
													}
													$exeData = explode('|', $items[$randItem]['exe']);
													$iexe = explode(',', $exeData[0]);
													foreach($iexe AS $k => $val){
														if($val == 0){
															unset($iexe[$k]);
														}
													}
													$totaExe = count($iexe);
													$randomizer = explode('-', $exeData[1]);
													$exe_opts = [0 => 1, 1 => 2, 2 => 4, 3 => 8, 4 => 16, 5 => 32];
													
													if($randomizer[1] > 0){
														if($randomizer[1] > $totaExe)
															$randomizer[1] = $totaExe;
														$rand = rand($randomizer[0], $randomizer[1]);
														if($rand == 0){
															$iexe = [];
														}
														else{
															$iexe = array_rand($iexe, $rand);
															if(!is_array($iexe))
																$iexe = [$iexe => 1];
															else{
																$newArr = [];
																foreach($iexe AS $k => $val){
																	$newArr[$val] = 1;
																}
																$iexe = $newArr;
															}
														}
													}
													
													$exe = 0;
													if(!empty($iexe)){		
														foreach($iexe as $key => $exe_opt){
															if($exe_opt == 1){
																$exe += $exe_opts[$key];
															}
														}
													}
													$this->createitem->addStaticExe($exe);
													$this->createitem->socket([254,254,254,254,254]);	
													$items[$randItem]['hex'] = $this->createitem->to_hex();
													$this->website->addResetReqItems($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $_POST['character'], $start_res.'-'.$end_res, $cat, $randItem, $items[$randItem]);
												}
											}
										}
									}
									$this->Mcharacter->char_info['res_info']['range'] = $start_res.'-'.$end_res;
								}
							}
						}
					}
					
					$this->Mcharacter->char_info['bonus_greset_stats_points'] = 0;
					
					if(!isset($this->Mcharacter->char_info['res_info'])){
						throw new \Exception(__('Reset Disabled'));
					}
					
					if($this->Mcharacter->char_info['res_info']['bonus_gr_points'] == 1){
						$greset_bonus_data = [];
						$greset_bonus_info = [];
						foreach($greset_config AS $key => $values){
							$greset_range = explode('-', $key);
							for($i = $greset_range[0]; $i < $greset_range[1]; $i++){
								$greset_bonus_data[$i] = $values['bonus_points'];
								$greset_bonus_info[$i] = $values['bonus_points_save'];
							}
						}
						foreach($greset_bonus_data AS $gres => $data){
							if($this->Mcharacter->char_info['grand_resets'] <= $gres)
								break;
							if($greset_bonus_info[$gres] == 1){
								$this->Mcharacter->char_info['bonus_greset_stats_points'] += $data[$this->Mcharacter->char_info['Class']];
							} else{
								$this->Mcharacter->char_info['bonus_greset_stats_points'] = $data[$this->Mcharacter->char_info['Class']];
							}
						}
					}
																  
					if($this->Mcharacter->char_info['res_info']['clear_equipment'] == 1){							   
						if(!$this->Mcharacter->check_equipment($this->session->userdata(['user' => 'server']))){
							throw new \Exception(__('Before reset please remove your equipped items.'));
						}
					}
					
					if(defined('CUSTOM_RESET_NORIA') && CUSTOM_RESET_NORIA == true){
						$resetsAllowed = (RESET_NORIA_START_DATE == date('Y-m-d', time())) ? RESET_NORIA_DAY_LIMIT : floor(abs(time() - strtotime(RESET_NORIA_START_DATE)) / 86400) + RESET_NORIA_DAY_LIMIT;
						$resLog = $this->Mcharacter->getTotalResets($this->Mcharacter->char_info['id'], $this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
						if($resLog != false){
							if($resLog['resets'] >= $resetsAllowed){
								throw new \Exception('Daily Reset Limit Reached.');
							}
						}
					}
					
					if(defined('RESET_NORIA_IS_VIP_RESET') && RESET_NORIA_IS_VIP_RESET == true){
						 if(isset($_POST['vip']) && $_POST['vip'] == 1){
							 $status = $this->website->get_user_credits_balance($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), RESET_NORIA_VIP_PRICE_TYPE, $this->session->userdata(['user' => 'id']));
							 if($status['credits'] < RESET_NORIA_VIP_PRICE){
								throw new \Exception(sprintf(__('You have insufficient amount of %s'), $this->website->translate_credits(RESET_NORIA_VIP_PRICE_TYPE, $this->session->userdata(['user' => 'server']))));
							 }
						 }
					}
																
					$next_reset = (int)$this->Mcharacter->char_info['last_reset_time'] + $this->Mcharacter->char_info['res_info']['reset_cooldown'];
					if($next_reset > time()){
						throw new \Exception(sprintf(__('You will be able to reset at %s'), date(DATETIME_FORMAT, $next_reset)));
					}
					
					$req_zen = $this->Mcharacter->check_zen($this->Mcharacter->char_info['res_info']['money'], $this->Mcharacter->char_info['res_info']['money_x_reset'], 'resets');
					if($req_zen !== true){
						$req_zen_wallet = $this->Mcharacter->check_zen_wallet($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $this->session->userdata(['user' => 'id']), $this->Mcharacter->char_info['res_info']['money'], $this->Mcharacter->char_info['res_info']['money_x_reset'], 'resets');
						if($req_zen_wallet !== true){
							throw new \Exception(sprintf(__('Your have insufficient amount of zen. Need: %s'), $this->website->zen_format($req_zen)));
						}
					}
					
					$req_lvl = $this->Mcharacter->check_lvl($this->Mcharacter->char_info['res_info']['level']);
					if($req_lvl !== true){
						throw new \Exception(sprintf(__('Your lvl is too low. You need %d lvl.'), $req_lvl)); 
					}	
					
					if(defined('CUSTOM_RESET_REQ_ITEMS') && CUSTOM_RESET_REQ_ITEMS == true){
						if(isset($this->Mcharacter->char_info['res_info']['range'])){
							$countItems = $this->website->checkNotCompletedItemCount($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $_POST['character'], $this->Mcharacter->char_info['res_info']['range']);
							if($countItems > 0){
								 throw new \Exception('Please complete all required items.');
							}
							else{
								$this->website->removeResetItems($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $_POST['character'], $this->Mcharacter->char_info['res_info']['range']);
							}
						}
					}
					
					if(isset($this->Mcharacter->char_info['res_info']['mlevel']) && $this->Mcharacter->char_info['res_info']['mlevel'] > 0){
						$req_mlvl = $this->Mcharacter->check_mlvl($this->Mcharacter->char_info['res_info']['mlevel']);
						if($req_mlvl !== true){
							throw new \Exception(sprintf(__('Your master lvl is too low. You need %d lvl.'), $req_mlvl)); 
						}
					}
					
					if($this->Mcharacter->reset_character($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server'])) == true){
						if(defined('RESET_NORIA_IS_VIP_RESET') && RESET_NORIA_IS_VIP_RESET == true){
							if(isset($_POST['vip']) && $_POST['vip'] == 1){
								$this->website->charge_credits($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), RESET_NORIA_VIP_PRICE, RESET_NORIA_VIP_PRICE_TYPE);
							}
						}
						json(['success' => __('Your character has been successfully reseted.'), 'newlvl' => $this->Mcharacter->char_info['res_info']['level_after_reset']]);
					} 
					else{
						throw new \Exception(__('Unable to reset character.'));
					}	
					
				}
				catch(\Exception $e){
					json(['error' => $e->getMessage()]);
				}   
			}
			else{
				json(['error' => __('Please login into website.')]);
			}  
        }
		
		public function greset_character(){
            if($this->session->userdata(['user' => 'logged_in'])){
				try{
					$this->load->model('account');
					$this->load->model('character');
					
					foreach($_POST as $key => $value){
						if($key == 'character'){
							$this->Mcharacter->$key = trim($this->website->hex2bin($value));
						} else{
							$this->Mcharacter->$key = trim($value);
						}
					}
					
					if(!$this->Maccount->check_connect_stat($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']))){
						throw new \Exception(__('Please logout from game.')); 
					}
					
					if(!$this->Mcharacter->check_char($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), '', '', false)){
						throw new \Exception(__('Character not found.')); 
					}
					
					if(!isset($_POST['character']) || $_POST['character'] == ''){
						throw new \Exception(__('Invalid Character'));
					}
					
					$reset_config = $this->config->values('reset_config', $this->session->userdata(['user' => 'server']));
					$greset_config = $this->config->values('greset_config', $this->session->userdata(['user' => 'server']));
					if(defined('CUSTOM_GRESET_REQ_ITEMS') && CUSTOM_GRESET_REQ_ITEMS == true){
						$reset_items_config = $this->config->values('greset_items_config', $this->session->userdata(['user' => 'server']));
					}
					
					if(!$greset_config){
						throw new \Exception(__('Grand Reset configuration for this server not found.')); 
					}
					
					if($greset_config['allow_greset'] == 0){
						throw new \Exception(__('Grand Reset function is disabled for this server')); 
					}
					
					unset($greset_config['allow_greset']);
					if(isset($reset_config)){
						unset($reset_config['allow_reset']);
					}
					
					foreach($greset_config AS $key => $values){
						list($start_gres, $end_gres) = explode('-', $key);
						if($this->Mcharacter->char_info['grand_resets'] >= $start_gres && $this->Mcharacter->char_info['grand_resets'] < $end_gres){
							$this->Mcharacter->char_info['gres_info'] = $values;
							if(defined('CUSTOM_GRESET_REQ_ITEMS') && CUSTOM_GRESET_REQ_ITEMS == true){
								$this->load->lib('iteminfo');
								$this->load->lib('createitem', [MU_VERSION, SOCKET_LIBRARY, $this->website->get_value_from_server($this->session->userdata(['user' => 'server']), 'item_size')]);
								if(!empty($reset_items_config[$start_gres.'-'.$end_gres])){
									foreach($reset_items_config[$start_gres.'-'.$end_gres] AS $cat => $items){
										if(count($items) > 0){
											$check = $this->website->checkGResetItem($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $_POST['character'], $start_gres.'-'.$end_gres, $cat);													
											if($check == false){
												$randItem = array_rand($items, 1);
												if($this->iteminfo->setItemData($items[$randItem]['id'], $items[$randItem]['cat'], $this->website->get_value_from_server($this->session->userdata(['user' => 'server']), 'item_size'))){
													$this->createitem->setItemData($this->iteminfo->item_data);
													$this->createitem->id($items[$randItem]['id']);
													$this->createitem->cat($items[$randItem]['cat']);
													$this->createitem->refinery(false);
													$this->createitem->serial(0);
													if($this->website->get_value_from_server($this->session->userdata(['user' => 'server']), 'item_size') == 64){
														$this->createitem->serial2(true);
													}
													if($items[$randItem]['minLvl'] == $items[$randItem]['maxLvl']){
														$this->createitem->lvl($items[$randItem]['minLvl']);
													}
													else{
														$this->createitem->lvl(rand($items[$randItem]['minLvl'], $items[$randItem]['maxLvl']));
														
													}
													$this->createitem->skill(false);
													$this->createitem->luck(false);
													if($items[$randItem]['minOpt'] == $items[$randItem]['maxOpt']){
														$this->createitem->opt($items[$randItem]['minOpt']);
													}
													else{
														$this->createitem->opt(rand($items[$randItem]['minOpt'], $items[$randItem]['maxOpt']));
														
													}
													$exeData = explode('|', $items[$randItem]['exe']);
													$iexe = explode(',', $exeData[0]);
													foreach($iexe AS $k => $val){
														if($val == 0){
															unset($iexe[$k]);
														}
													}
													$totaExe = count($iexe);
													$randomizer = explode('-', $exeData[1]);
													$exe_opts = [0 => 1, 1 => 2, 2 => 4, 3 => 8, 4 => 16, 5 => 32];
													
													if($randomizer[1] > 0){
														if($randomizer[1] > $totaExe)
															$randomizer[1] = $totaExe;
														$rand = rand($randomizer[0], $randomizer[1]);
														if($rand == 0){
															$iexe = [];
														}
														else{
															$iexe = array_rand($iexe, $rand);
															if(!is_array($iexe))
																$iexe = [$iexe => 1];
															else{
																$newArr = [];
																foreach($iexe AS $k => $val){
																	$newArr[$val] = 1;
																}
																$iexe = $newArr;
															}
														}
													}
													
													$exe = 0;
													if(!empty($iexe)){		
														foreach($iexe as $key => $exe_opt){
															if($exe_opt == 1){
																$exe += $exe_opts[$key];
															}
														}
													}
													$this->createitem->addStaticExe($exe);
													$this->createitem->socket([254,254,254,254,254]);	
													$items[$randItem]['hex'] = $this->createitem->to_hex();
													$this->website->addGResetReqItems($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $_POST['character'], $start_gres.'-'.$end_gres, $cat, $randItem, $items[$randItem]);
												}
											}
										}
									}
									$this->Mcharacter->char_info['gres_info']['range'] = $start_gres.'-'.$end_gres;
								}
							}
						}
					}
					
					$this->Mcharacter->char_info['bonus_reset_stats_points'] = 0;
					
					if(!isset($this->Mcharacter->char_info['gres_info'])){
						throw new \Exception(__('GrandReset Disabled'));
					}
					
					if($this->Mcharacter->char_info['gres_info']['bonus_reset_stats'] == 1){
						$reset_data = [];
						foreach($reset_config AS $key => $values){
							$reset_range = explode('-', $key);
							for($i = $reset_range[0]; $i < $reset_range[1]; $i++){
								$reset_data[$i] = $values['bonus_points'];
							}
						}
					 
						foreach($reset_data AS $res => $data){
							if($this->Mcharacter->char_info['resets'] <= $res)
								break;
							$this->Mcharacter->char_info['bonus_reset_stats_points'] += $data[$this->Mcharacter->char_info['Class']];
						}
					}
					
					$req_zen = $this->Mcharacter->check_zen($this->Mcharacter->char_info['gres_info']['money'], $this->Mcharacter->char_info['gres_info']['money_x_reset'], 'grand_resets');
					if($req_zen !== true){
						$req_zen_wallet = $this->Mcharacter->check_zen_wallet($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $this->session->userdata(['user' => 'id']), $this->Mcharacter->char_info['gres_info']['money'], $this->Mcharacter->char_info['gres_info']['money_x_reset'], 'grand_resets');
						if($req_zen_wallet !== true){
							throw new \Exception(sprintf(__('Your have insufficient amount of zen. Need: %s'), $this->website->zen_format($req_zen)));
						}
					}
					
					$req_lvl = $this->Mcharacter->check_lvl($this->Mcharacter->char_info['gres_info']['level']);
					if($req_lvl !== true){
						throw new \Exception(sprintf(__('Your lvl is too low. You need %d lvl.'), $req_lvl)); 
					}
					
					if(!$this->Mcharacter->check_resets($this->Mcharacter->char_info['gres_info']['reset'])){
						throw new \Exception(sprintf(__('Your resets is too low. You need %d resets.'), $this->Mcharacter->char_info['gres_info']['reset']));
					}
					
					if(defined('CUSTOM_GRESET_REQ_ITEMS') && CUSTOM_GRESET_REQ_ITEMS == true){
						if(isset($this->Mcharacter->char_info['gres_info']['range'])){
							$countItems = $this->website->checkNotCompletedItemCountGRes($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $_POST['character'], $this->Mcharacter->char_info['gres_info']['range']);
							if($countItems > 0){
								 throw new \Exception(__('Please complete all required items.'));
							}
							else{
								$this->website->removeGResetItems($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $_POST['character'], $this->Mcharacter->char_info['gres_info']['range']);
							}
						}
					}
					
					if(isset($this->Mcharacter->char_info['gres_info']['mlevel']) && $this->Mcharacter->char_info['gres_info']['mlevel'] > 0){
						$req_mlvl = $this->Mcharacter->check_mlvl($this->Mcharacter->char_info['gres_info']['mlevel']);
						if($req_mlvl !== true){
							throw new \Exception(sprintf(__('Your master lvl is too low. You need %d lvl.'), $req_mlvl)); 
						}
					}
					
					if($this->Mcharacter->greset_character($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']))){
						json(['success' => __('Your character has been successfully reseted.')]);
					} 
					else{
						throw new \Exception(__('Unable to reset character.'));
					}
											
				}
				catch(\Exception $e){
					json(['error' => $e->getMessage()]);
				} 
            } 
			else{
                json(['error' => __('Please login into website.')]);
            }
        }

		public function add_ref_reward(){
            if($this->session->userdata(['user' => 'logged_in'])){
                $this->load->model('account');
                $this->load->model('character');
                $id = (isset ($_POST['id']) && preg_match('/^\d*$/', $_POST['id'])) ? $_POST['id'] : '';
                $char = isset($_POST['char']) ? $_POST['char'] : '';
														  															
                if($id == '')
                    json(['error' => __('Invalid referral reward id.')]); 
				else{
                    if(!$reward_data = $this->Maccount->check_referral_reward($id, $this->session->userdata(['user' => 'server'])))
                        json(['error' => __('Referral reward not found.')]); 
					else{
                        if($char == '')
                            json(['error' => __('Invalid Character')]); 
						else{
                            if(!$this->Mcharacter->check_char_no_account($char, $this->session->userdata(['user' => 'server'])))
                                json(['error' => __('Character not found.')]); 
							else{
								if(!$this->Maccount->check_if_referral_exists($this->session->userdata(['user' => 'username']), $this->Mcharacter->char_info['AccountId']))
									json(['error' => __('Referral not found.')]); 
								else{
									if($reward_data['required_lvl'] > $this->Mcharacter->char_info['cLevel'] + $this->Mcharacter->char_info['mlevel'])
										json(['error' => sprintf(__('Character lvl is too low required %d lvl'), $reward_data['required_lvl'])]); 
									else{
										if(!$this->check_ref_req_resets($reward_data['required_res'], $this->Mcharacter->char_info))
											json(['error' => sprintf(__('Character reset is too low required %d reset'), $reward_data['required_res'])]); 
										else{
											if(!$this->check_ref_req_gresets($reward_data['required_gres'], $this->Mcharacter->char_info))
												json(['error' => sprintf(__('Character grand reset is too low required %d grand reset'), $reward_data['required_gres'])]); 
											else{
												$history = $this->Maccount->check_name_in_history($char, $reward_data['server']);
												if(!empty($history)){
													$check_chars = [$char];
													foreach($history AS $names){
														$check_chars[] = $names['old_name'];
														$check_chars[] = $names['new_name'];					   
													}
													$check_chars = array_unique($check_chars);
												} else{
													$check_chars = [$char];
												}
												if($this->Maccount->check_claimed_referral_rewards($this->session->userdata(['user' => 'username']), $reward_data['id'], $check_chars, $reward_data['server'])){
													json(['error' => __('Reward was already claimed with this character.')]);
												} else{
													if($this->config->values('referral_config', 'claim_type') == 0){
														if($this->Maccount->check_if_reward_was_claimed($this->session->userdata(['user' => 'username']), $reward_data['id'], $reward_data['server'], $this->Mcharacter->char_info['AccountId'])){
															json(['error' => __('Reward can be claimed only once. It was already claimed by different character.')]);
															return; 
														}
													}
													if($this->config->values('referral_config', 'compare_ips') == 1){
														if($this->Maccount->check_referral_ips($this->session->userdata(['user' => 'username']), $this->Mcharacter->char_info['AccountId'])){
															json(['error' => __('You can not claim rewards for own accounts.')]);
															return; 
														}																		 
													}
													$this->Maccount->add_referral_reward($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $reward_data['reward'], $reward_data['reward_type'], $char);
													$this->Maccount->log_reward($this->session->userdata(['user' => 'username']), $reward_data['id'], $char, $reward_data['server'], $this->Mcharacter->char_info['AccountId']);
													json(['success' => __('Referral reward was claimed successfully.')]);
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
                json(['error' => __('Please login into website.')]);
            }
        }

        private function check_ref_req_resets($req, $char_info){
            if($req > 0){
                if($req > $char_info['resets']){
                    return false;
                }
            }
            return true;
        }

        private function check_ref_req_gresets($req, $char_info){
            if($req > 0){
                if($req > $char_info['grand_resets']){
                    return false;
                }
            }
            return true;
        }

        public function pk_clear(){
            if($this->session->userdata(['user' => 'logged_in'])){
                $this->load->model('account');
                $this->load->model('character');
                foreach($_POST as $key => $value){
					if($key == 'character'){
                        $this->Mcharacter->$key = trim($this->website->hex2bin($value));
                    }																	
                }
                if(!$this->Maccount->check_connect_stat($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server'])))
                    json(['error' => __('Please logout from game.')]); 
				else{
                    if(!isset($_POST['character']))
                        json(['error' => __('Invalid Character')]); 
					else{
                        if(!$this->Mcharacter->check_char($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server'])))
                            json(['error' => __('Character not found.')]); 
						else{
                            if(!$this->Mcharacter->check_pk())
                                json(['error' => __('You are not a murder.')]); 
							else{
                                $price = $this->config->config_entry('character_' . $this->session->userdata(['user' => 'server']) . '|pk_clear_price');
								$method = $this->config->config_entry('character_' . $this->session->userdata(['user' => 'server']) . '|pk_clear_payment_method');
								
                                if($this->session->userdata('vip')){
                                    $price -= $this->session->userdata(['vip' => 'pk_clear_discount']);
                                }
								if($method == 0){
									if($this->Mcharacter->char_info['Money'] < $price)
										json(['error' => sprintf(__('Your have insufficient amount of zen. Need: %s'), $this->website->zen_format($price))]); 
									else{
										$this->Mcharacter->clear_pk($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $price);
										json(['success' => __('Your murders have been successfully reseted.')]);
									}
								}
								else{
									if(in_array($method, [1,2])){
										 $status = $this->website->get_user_credits_balance($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $method, $this->session->userdata(['user' => 'id']));
										 if($status['credits'] < $price){
											json(['error' => sprintf(__('You have insufficient amount of %s'), $this->website->translate_credits($method, $this->session->userdata(['user' => 'server'])))]);
										 }
										 else{
											$this->Mcharacter->clear_pk($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), 0);
											$this->website->charge_credits($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $price, $method);
											json(['success' => __('Your murders have been successfully reseted.')]);
										 }
									}
									else{
										 $this->vars['table_config'] = $this->config->values('table_config', $this->session->userdata(['user' => 'server']));
										 
										 if($status = $this->Mcharacter->get_wcoins($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'id']), $this->vars['table_config']['wcoins'], $this->session->userdata(['user' => 'server']))){
											if($status < $price)
												json(['error' => sprintf(__('You have insufficient amount of %s'), __('WCoins'))]);
											else{
												$this->Mcharacter->clear_pk($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), 0);
												$this->Mcharacter->remove_wcoins($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $this->session->userdata(['user' => 'id']), $this->vars['table_config']['wcoins'], $price);
												json(['success' => __('Your murders have been successfully reseted.')]);
											}
										} else{
											json(['error' => __('Unable to load wcoins')]);
										}
									}
								}
                            }
                        }
                    }
                }
            } else{
                json(['error' => __('Please login into website.')]);
            }
        }

        public function reset_stats(){
            if($this->session->userdata(['user' => 'logged_in'])){
                if($this->config->config_entry('character_' . $this->session->userdata(['user' => 'server']) . '|allow_reset_stats') == 1){
                    $this->load->model('account');
                    $this->load->model('character');
                    foreach($_POST as $key => $value){
                        if($key == 'character'){
                            $this->Mcharacter->$key = trim($this->website->hex2bin($value));
                        } else{
                            $this->Mcharacter->$key = trim($value);
                        }
                    }
                    if(!$this->Maccount->check_connect_stat($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server'])))
                        json(['error' => __('Please logout from game.')]); 
					else{
                        if(!isset($_POST['character']))
                            json(['error' => __('Invalid Character')]); 
						else{
                            if(!$this->Mcharacter->check_char($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server'])))
                                json(['error' => __('Character not found.')]); 
							else{
                                if($this->config->config_entry('character_' . $this->session->userdata(['user' => 'server']) . '|reset_stats_price') > 0){
                                    $status = $this->website->get_user_credits_balance($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $this->config->config_entry('character_' . $this->session->userdata(['user' => 'server']) . '|reset_stats_payment_type'), $this->session->userdata(['user' => 'id']));
                                    if($status['credits'] < $this->config->config_entry('character_' . $this->session->userdata(['user' => 'server']) . '|reset_stats_price')){
                                        json(['error' => sprintf(__('You have insufficient amount of %s'), $this->website->translate_credits($this->config->config_entry('character_' . $this->session->userdata(['user' => 'server']) . '|reset_stats_payment_type'), $this->session->userdata(['user' => 'server'])))]);
                                    } else{
                                        $this->Mcharacter->reset_stats($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
                                        $this->website->charge_credits($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $this->config->config_entry('character_' . $this->session->userdata(['user' => 'server']) . '|reset_stats_price'), $this->config->config_entry('character_' . $this->session->userdata(['user' => 'server']) . '|reset_stats_payment_type'));
                                        $this->Mcharacter->add_account_log('Cleared character ' . $this->website->hex2bin($_POST['character']) . ' stats for ' . $this->website->translate_credits($this->config->config_entry('character_' . $this->session->userdata(['user' => 'server']) . '|reset_stats_payment_type'), $this->session->userdata(['user' => 'server'])) . '', -$this->config->config_entry('character_' . $this->session->userdata(['user' => 'server']) . '|reset_stats_price'), $this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
                                        json(['success' => __('Stats successfully reseted.')]);
                                    }
                                } else{
                                    $this->Mcharacter->reset_stats($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
                                    json(['success' => __('Stats successfully reseted.')]);
                                }
                            }
                        }
                    }
                } else{
                    json(['error' => __('Reset Stats Disabled')]);
                }
            } else{
                json(['error' => __('Please login into website.')]);
            }
        }

		public function reset_skilltree(){
            if($this->session->userdata(['user' => 'logged_in'])){
				if($this->config->config_entry('character_' . $this->session->userdata(['user' => 'server']) . '|allow_reset_skilltree') == 1){
					$this->load->model('account');
					$this->load->model('character');
					foreach($_POST as $key => $value){
						if($key == 'character'){
							$this->Mcharacter->$key = trim(hex2bin($value));
						} else{
							$this->Mcharacter->$key = trim($value);
						}
					}
					if(!$this->Maccount->check_connect_stat($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server'])))
						json(['error' => __('Please logout from game.')]); 
					else{
						if(!isset($_POST['character']))
							json(['error' => __('Invalid Character')]); 
						else{
							if(!$this->Mcharacter->check_char($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server'])))
								json(['error' => __('Character not found.')]); 
							else{
								if(!in_array($this->Mcharacter->char_info['Class'], $this->resetSkillTreeClass))
									json(['error' => __('Your class is not allowed to reset skilltree.')]); else{
									$status = $this->Maccount->get_amount_of_credits($this->session->userdata(['user' => 'username']), $this->config->config_entry('character_' . $this->session->userdata(['user' => 'server']) . '|skilltree_reset_price_type'), $this->session->userdata(['user' => 'server']), $this->session->userdata(['user' => 'id']));
									$price = $this->config->config_entry('character_' . $this->session->userdata(['user' => 'server']) . '|skilltree_reset_price');
									if($this->session->userdata('vip')){
										$price -= ($price / 100) * $this->session->userdata(['vip' => 'clear_skilltree_discount']);
									}
									if($status < $price){
										json(['error' => sprintf(__('You have insufficient amount of %s'), $this->website->translate_credits($this->config->config_entry('character_' . $this->session->userdata(['user' => 'server']) . '|skilltree_reset_price_type'), $this->session->userdata(['user' => 'server'])))]);
									} else{
										$skill_tree = $this->Mcharacter->reset_skill_tree($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $this->config->config_entry('character_' . $this->session->userdata(['user' => 'server']) . '|skill_tree_type'), $this->config->config_entry('character_' . $this->session->userdata(['user' => 'server']) . '|skilltree_reset_level'), $this->config->config_entry('character_' . $this->session->userdata(['user' => 'server']) . '|skilltree_reset_points'), $this->config->config_entry('character_' . $this->session->userdata(['user' => 'server']) . '|skilltree_points_multiplier'));
										if($skill_tree){
											$this->website->charge_credits($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $price, $this->config->config_entry('character_' . $this->session->userdata(['user' => 'server']) . '|skilltree_reset_price_type'));
											$this->Mcharacter->add_account_log('Cleared character ' . $this->website->hex2bin($_POST['character']) . ' skill tree for ' . $this->website->translate_credits($this->config->config_entry('character_' . $this->session->userdata(['user' => 'server']) . '|skilltree_reset_price_type'), $this->session->userdata(['user' => 'server'])), -$price, $this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
											json(['success' => __('SkillTree successfully reseted.')]);
										} else{
											json(['error' => __('Unable to reset skilltree.')]);
										}
									}
								}
							}
						}
					}
				}
				else{
					json(['error' => __('Reset SkillTree Disabled')]);
				}
            } else{
                json(['error' => __('Please login into website.')]);
            }
        }

		public function clear_inventory(){
            if($this->session->userdata(['user' => 'logged_in'])){
				$this->load->model('account');
                $this->load->model('character');
                foreach($_POST as $key => $value){
                    $this->Mcharacter->$key = trim($value);
                }
                if(!$this->Maccount->check_connect_stat($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server'])))
                    json(['error' => __('Please logout from game.')]); 
								else{
                    if(!isset($this->Mcharacter->vars['character']))
                        json(['error' => __('Invalid Character')]); 
					else{
                        if(!isset($this->Mcharacter->vars['inventory']) && !isset($this->Mcharacter->vars['equipment']) && !isset($this->Mcharacter->vars['store']) && !isset($this->Mcharacter->vars['exp_inv_1']) && !isset($this->Mcharacter->vars['exp_inv_2']))
                            json(['error' => __('Please select one of options.')]); 
													else{
                            if(!$this->Mcharacter->check_char($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server'])))
                                json(['error' => __('Character not found.')]); 
							else{
                                $this->Mcharacter->clear_inv($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
                                json(['success' => __('Character inventory successfully cleared.')]);
                            }
                        }
                    }
                }
            } else{
                json(['error' => __('Please login into website.')]);
            }
        }

        public function buy_level(){
            if($this->session->userdata(['user' => 'logged_in'])){
				$this->load->model('account');
                $this->load->model('character');
                $level_conf = $this->config->values('buylevel_config', $this->session->userdata(['user' => 'server']));
                foreach($_POST as $key => $value){
                    $this->Mcharacter->$key = trim($value);
                }
                if(!$this->Maccount->check_connect_stat($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server'])))
                    json(['error' => __('Please logout from game.')]); 
				else{
                    if(!isset($this->Mcharacter->vars['character']))
                        json(['error' => __('Invalid Character')]); 
					else{
                        if(!isset($this->Mcharacter->vars['level']))
                            json(['error' => __('Please select level.')]); 
						else{
                            if(!$this->Mcharacter->check_char($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server'])))
                                json(['error' => __('Character not found.')]); 
							else{
                                if(!array_key_exists($this->Mcharacter->vars['level'], $level_conf['levels']))
                                    json(['error' => __('Invalid level selected.')]); 
								else{
                                    if(!$this->check_max_level_allowed($level_conf, $this->Mcharacter->vars['level'], $this->Mcharacter->char_info['cLevel']))
                                        json(['error' => sprintf(__('You will exceed max level allowed: %d, please try to buy lower level.'), isset($level_conf['max_level']) ? $level_conf['max_level'] : 0)]); else{
                                        $status = $this->Maccount->get_amount_of_credits($this->session->userdata(['user' => 'username']), $level_conf['levels'][$this->Mcharacter->vars['level']]['payment_type'], $this->session->userdata(['user' => 'server']), $this->session->userdata(['user' => 'id']));
                                        if($status < $level_conf['levels'][$this->Mcharacter->vars['level']]['price']){
                                            json(['error' => sprintf(__('You have insufficient amount of %s'), $this->website->translate_credits($level_conf['levels'][$this->Mcharacter->vars['level']]['payment_type'], $this->session->userdata(['user' => 'server'])))]);
                                        } else{
                                            if($this->Mcharacter->update_level($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']))){
                                                $this->website->charge_credits($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $level_conf['levels'][$this->Mcharacter->vars['level']]['price'], $level_conf['levels'][$this->Mcharacter->vars['level']]['payment_type']);
                                                json(['success' => __('Character level updated.')]);
                                            } else{
                                                json(['error' => __('Unable to update character level.')]);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            } else{
                json(['error' => __('Please login into website.')]);
            }
        }

        private function check_max_level_allowed($level_config, $levels_to_add, $char_level){
            if($level_config != false){
                if(isset($level_config['max_level'])){
                    $new_char_level = $levels_to_add + $char_level;
                    if($new_char_level <= $level_config['max_level'])
                        return true;
                }
            }
            return false;
        }

        public function buy_points(){
            if($this->session->userdata(['user' => 'logged_in'])){
				$this->load->model('account');
                $this->load->model('character');
                foreach($_POST as $key => $value){
                    $this->Mcharacter->$key = trim($value);
                }
                if(!$this->Maccount->check_connect_stat($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server'])))
                    json(['error' => __('Please logout from game.')]); 
				else{
                    if(!isset($this->Mcharacter->vars['character']))
                        json(['error' => __('Invalid Character')]); 
					else{
                        if(!isset($this->Mcharacter->vars['points']))
                            json(['error' => __('Please enter amount of points.')]); 
						else{
                            if(!$this->Mcharacter->check_char($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server'])))
                                json(['error' => __('Character not found.')]); else{
                                if($this->Mcharacter->vars['points'] < $this->config->config_entry('buypoints|points'))
                                    json(['error' => __('Minimal points value: %d points.', $this->config->config_entry('buypoints|points'))]); else{
                                    $status = $this->Maccount->get_amount_of_credits($this->session->userdata(['user' => 'username']), $this->config->config_entry('buypoints|price_type'), $this->session->userdata(['user' => 'server']), $this->session->userdata(['user' => 'id']));
                                    $price = ceil(($this->Mcharacter->vars['points'] * $this->config->config_entry('buypoints|price')) / $this->config->config_entry('buypoints|points'));
                                    if($status < $price){
                                        json(['error' => sprintf(__('You have insufficient amount of %s'), $this->website->translate_credits($this->config->config_entry('buypoints|price_type'), $this->session->userdata(['user' => 'server'])))]);
                                    } else{
                                        if($this->Mcharacter->update_points($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']))){
                                            $this->website->charge_credits($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $price, $this->config->config_entry('buypoints|price_type'));
                                            json(['success' => __('Character statpoints updated.')]);
                                        } else{
                                            json(['error' => __('Unable to update character statpoints.')]);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            } else{
                json(['error' => __('Please login into website.')]);
            }
        }

        public function buy_gm(){
            if($this->session->userdata(['user' => 'logged_in'])){
				$this->load->model('account');
                $this->load->model('character');
                foreach($_POST as $key => $value){
                    $this->Mcharacter->$key = trim($value);
                }
                if(!$this->Maccount->check_connect_stat($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server'])))
                    json(['error' => __('Please logout from game.')]); 
				else{
                    if(!isset($this->Mcharacter->vars['character']))
                        json(['error' => __('Invalid Character')]); 
					else{
                        if(!$this->Mcharacter->check_char($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server'])))
                            json(['error' => __('Character not found.')]); 
						else{
                            if($this->Mcharacter->char_info['CtlCode'] == $this->config->config_entry('buygm|gm_ctlcode'))
                                json(['error' => __('Your character already is GameMaster.')]); 
							else{
                                $status = $this->Maccount->get_amount_of_credits($this->session->userdata(['user' => 'username']), $this->config->config_entry('buygm|price_t'), $this->session->userdata(['user' => 'server']), $this->session->userdata(['user' => 'id']));
                                if($status < $this->config->config_entry('buygm|price')){
                                    json(['error' => sprintf(__('You have insufficient amount of %s'), $this->website->translate_credits($this->config->config_entry('buygm|price_t'), $this->session->userdata(['user' => 'server'])))]);
                                } else{
                                    if($this->Mcharacter->update_gm($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']))){
                                        $this->Maccount->add_account_log('Bought GM Status For ' . $this->website->translate_credits($this->config->config_entry('buygm|price_t'), $this->session->userdata(['user' => 'server'])), -$this->config->config_entry('buygm|price'), $this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
                                        $this->website->charge_credits($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $this->config->config_entry('buygm|price'), $this->config->config_entry('buygm|price_t'));
                                        json(['success' => __('Character successfully promoted to GameMaster.')]);
                                    } else{
                                        json(['error' => __('Unable to update character gm status.')]);
                                    }
                                }
                            }
                        }
                    }
                }
            } else{
                json(['error' => __('Please login into website.')]);
            }
        }

		public function skip_reset_item(){
			if($this->session->userdata(['user' => 'logged_in'])){
				$id = $_POST['id'];
				$char = $_POST['Char'];
				$cat = $_POST['cat'];
				$status = $this->website->checkCompletedChangeClassItem($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $char, $id, $cat);

				if($status != false){
					if($status['is_skipped'] == 1)
						json(['error' => __('Item requirement already skipped.')]);
					else{
						if($status['is_completed'] == 1)
							json(['error' => __('Item requirement already completed.')]);
						else{
							if($status['skip_price_type'] == 0)
								json(['error' => __('This item cannot be skipped.')]);
							else{
								 $statusCr = $this->website->get_user_credits_balance($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $status['skip_price_type'], $this->session->userdata(['user' => 'id']));
								 if($statusCr['credits'] < $status['skip_price']){
									json(['error' => sprintf(__('You have insufficient amount of %s'), $this->website->translate_credits($status['skip_price_type'], $this->session->userdata(['user' => 'server'])))]);
								 }
								 else{
									 $this->website->charge_credits($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $status['skip_price'], $status['skip_price_type']);
									 $this->website->setSkippedChangeClassItem($id, $char, $this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $cat);
									 $this->Mcharacter->add_account_log('Skipped change class req item ' . $this->website->hex2bin($char) . ' for ' . $this->website->translate_credits($status['skip_price_type'], $this->session->userdata(['user' => 'server'])) . '', -$status['skip_price'], $this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
                                     json(['success' => __('Item skipped.')]);   
								 }
							}								
						}							
					}
					
				}
				else{
					json(['error' => __('Item not found.')]);
				}
			}
			else{
				json(['error' => __('Please login into website.')]);
			}  
		}

		public function check_change_class_item(){
			if($this->session->userdata(['user' => 'logged_in'])){
				$id = $_POST['id'];
				$char = $_POST['Char'];
				$cat = $_POST['cat'];
				$status = $this->website->checkCompletedChangeClassItem($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $char, $id, $cat);

				if($status != false){
					if($status['is_skipped'] == 1)
						json(['error' => __('Item requirement already skipped.')]);
					else{
						if($status['is_completed'] == 1)
							json(['error' => __('Item requirement already completed.')]);
						else{
							$itemC = $status['hex'];

							if(isset($itemC)){
								 $this->load->lib('iteminfo');
								 $this->iteminfo->itemData($itemC);
								 $idd = $this->iteminfo->id;
								 $type = $this->iteminfo->type;
								 $lvl = (int)substr($this->iteminfo->getLevel(), 1);
								 $opt = ($this->iteminfo->getOption()*4);
								 $exe = $this->iteminfo->exeForCompare();
								 $this->load->model('warehouse');
								 $items = $this->Mwarehouse->list_web_items($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
								 if(empty($items))
									json(['error' => __('Item not found in web warehouse.')]);
								 else{
									 $this->load->lib('iteminfo');
									 $found = false;
									 $lvlOk = false;
									 $optOk = false;
									 $exe0Ok = false;
									 $exe1Ok = false;
									 $exe2Ok = false;
									 $exe3Ok = false;
									 $exe4Ok = false;
									 $exe5Ok = false; 
									 
									 foreach($items AS $item){
										 $itemInfo = $this->Mwarehouse->load_item_info($item['item']);

										 if($itemInfo['info']['id'] == $idd && $itemInfo['info']['cat'] == $type){
											
											if($itemInfo['info']['lvl'] >= $lvl){
												$lvlOk = true;
											}
											if($itemInfo['info']['opt'] >= $opt){
												$optOk = true;
											}
											
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

											if($lvlOk == true && $optOk == true && $exe0Ok == true && $exe1Ok == true && $exe2Ok == true && $exe3Ok == true && $exe4Ok == true && $exe5Ok == true){
												$found = $item['id'];
												break;
											}
										 }
									 }
									 if($found == false){
										 json(['error' => __('Item not found in web warehouse.')]);
									 }
									 else{
										 $this->Mwarehouse->remove_web_item($found);
										 
										 $this->website->setCompletedChangeClassItem($id, $char, $this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $cat);
										 json(['success' => __('Item found and removed.')]);
									 }
								 }
							}
							else{
								json(['error' => __('Item not found in config.')]);
							}						
						}							
					}
					
				}
				else{
					json(['error' => __('Item not found.')]);
				}
			}
			else{
				json(['error' => __('Please login into website.')]);
			}  
		}

		public function load_class_list(){
            if($this->session->userdata(['user' => 'logged_in'])){
				$this->load->model('account');
                $this->load->model('character');
                foreach($_POST as $key => $value){
                    $this->Mcharacter->$key = trim($value);
                }
                if(!$this->Maccount->check_connect_stat($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server'])))
                    json(['error' => __('Please logout from game.')]); 
				else{
                    if(!isset($this->Mcharacter->vars['character']))
                        json(['error' => __('Invalid Character')]); 
					else{
                        if(!$this->Mcharacter->check_char($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server'])))
                            json(['error' => __('Character not found.')]); 
						else{
                            if($select = $this->Mcharacter->gen_class_select_field($this->config->values('change_class_config', 'class_list'))){
								$this->vars['changeclass_config'] = $this->config->values('change_class_config');
								$price = $this->vars['changeclass_config']['price'];
								if($this->session->userdata('vip')){
									$price -= ($price / 100) * $this->session->userdata(['vip' => 'change_class_discount']);
								}
								$price_type = $this->website->translate_credits($this->vars['changeclass_config']['payment_type'], $this->session->userdata(['user' => 'server']));
								
								if(defined('CHANGE_CLASS_REQ_ITEMS') && CHANGE_CLASS_REQ_ITEMS == true){
									$this->load->lib('iteminfo');
									$this->load->lib('createitem', [MU_VERSION, SOCKET_LIBRARY, $this->website->get_value_from_server($this->session->userdata(['user' => 'server']), 'item_size')]);
									
									$reset_items_config = $this->config->values('class_change_items_config', $this->session->userdata(['user' => 'server']));
									
									$html = '';
									
									if(!empty($reset_items_config)){
										$this->vars['reqItems'] = [];
										foreach($reset_items_config AS $cat => $items){												
											if(count($items) > 0){
												$check = $this->website->checkClassChangeItem($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), bin2hex($this->Mcharacter->vars['character']), $cat);													
												if($check == false){
													$randItem = array_rand($items, 1);
													if($this->iteminfo->setItemData($items[$randItem]['id'], $items[$randItem]['cat'], $this->website->get_value_from_server($this->session->userdata(['user' => 'server']), 'item_size'))){
														$this->createitem->setItemData($this->iteminfo->item_data);
														$this->createitem->id($items[$randItem]['id']);
														$this->createitem->cat($items[$randItem]['cat']);
														$this->createitem->refinery(false);
														$this->createitem->serial(0);
														if($this->website->get_value_from_server($this->session->userdata(['user' => 'server']), 'item_size') == 64){
															$this->createitem->serial2(true);
														}
														if($items[$randItem]['minLvl'] == $items[$randItem]['maxLvl']){
															$this->createitem->lvl($items[$randItem]['minLvl']);
														}
														else{
															$this->createitem->lvl(rand($items[$randItem]['minLvl'], $items[$randItem]['maxLvl']));
															
														}
														$this->createitem->skill(false);
														$this->createitem->luck(false);
														if($items[$randItem]['minOpt'] == $items[$randItem]['maxOpt']){
															$this->createitem->opt($items[$randItem]['minOpt']);
														}
														else{
															$this->createitem->opt(rand($items[$randItem]['minOpt'], $items[$randItem]['maxOpt']));
															
														}
														if($items[$randItem]['exe'] != ''){
															$exeData = explode('|', $items[$randItem]['exe']);
															$iexe = explode(',', $exeData[0]);
															foreach($iexe AS $k => $val){
																if($val == 0){
																	unset($iexe[$k]);
																}
															}
															$totaExe = count($iexe);
															$randomizer = explode('-', $exeData[1]);
															$exe_opts = [0 => 1, 1 => 2, 2 => 4, 3 => 8, 4 => 16, 5 => 32];
															
															if($randomizer[1] > 0){
																if($randomizer[1] > $totaExe)
																	$randomizer[1] = $totaExe;
																$rand = rand($randomizer[0], $randomizer[1]);
																if($rand == 0){
																	$iexe = [];
																}
																else{
																	$iexe = array_rand($iexe, $rand);
																	if(!is_array($iexe))
																		$iexe = [$iexe => 1];
																	else{
																		$newArr = [];
																		foreach($iexe AS $k => $val){
																			$newArr[$val] = 1;
																		}
																		$iexe = $newArr;
																	}
																}
															}
															
															$exe = 0;
															if(!empty($iexe)){		
																foreach($iexe as $key => $exe_opt){
																	if($exe_opt == 1){
																		$exe += $exe_opts[$key];
																	}
																}
															}
															$this->createitem->addStaticExe($exe);
														}

														$items[$randItem]['hex'] = $this->createitem->to_hex();
														$this->website->addChangeClassReqItems($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), bin2hex($this->Mcharacter->vars['character']), $cat, $randItem, $items[$randItem]);
														$this->vars['reqItems'][$cat][$randItem] = $items[$randItem];
													}
												}
												else{
													$items[$check['config_id']]['hex'] = $check['hex'];
													$this->vars['reqItems'][$cat][$check['config_id']] = $items[$check['config_id']];
												}
											}
										}
										if(!empty($this->vars['reqItems'])){
											$i = 0;
											$html = '<tr><td colspan="5"><table style="width: 100%;" class="ranking-table">';
											foreach($this->vars['reqItems'] AS $cat => $reqItems){
												foreach($reqItems AS $id => $reqItem){
													$status = $this->website->checkCompletedChangeClassItem($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), bin2hex($this->Mcharacter->vars['character']), $id, $cat); 
													if($i == 0){
														$html .= '<tr class="main-tr"><td style="text-align:left;">Req Item</td><td>Skip</td><td>Status</td></tr>';
													}
													$this->iteminfo->itemData($reqItem['hex']);
													
													$html .= '<tr> <td style="text-align:left;"><span id="reset_item_'.$id.'" data-info="'.$reqItem['hex'].'">'.$this->iteminfo->getNameStyle(true).'</span></td><td>';
													if(isset($reqItem['priceType']) && $reqItem['priceType'] != 0){ 
														if($status['is_skipped'] == 0 && $status['is_completed'] == 0){
														$html .= '<a id="skip_reset_item_'.$id.'_'.bin2hex($name).'_'.$cat.'" data-action="'.$this->config->base_url.'ajax/skip-change-class-item" data-info="Price: '.$reqItem['skipPrice'].' '.$this->website->translate_credits($reqItem['priceType'], $this->session->userdata(['user' => 'server'])).'" href="">Skip</a>'; 
														}
														else{
															if($status['is_skipped'] == 1){
																$html .= 'Already skipped';
															}
															else{
																$html .= 'Already completed.';
															}															
														}
													} 
													else{
														$html .= 'Cannot Skip';
													}
													
													$html .= '</td><td>';

													if($status['is_skipped'] == 0 && $status['is_completed'] == 0){
														$html .= '<a id="check_completed_reset_item_'.$id.'_'.bin2hex($this->Mcharacter->vars['character']).'_'.$cat.'" data-action="'.$this->config->base_url.'ajax/check-change-class-item" data-info="Item should be located in Web Warehouse" href="" style="color: red;">Check</a>';
													}
													else{
														$html .= '<span style="color: green;">Completed</span>';
													}
													
													$html .= '</td></tr>';
													
													$i++;
												}
											}
											$html .= '</table></td></tr>';
										}
									}
									json(['data' => $select, 'price' => $price, 'price_type' => $price_type, 'items' => $html]);
								}
								else{
									if(defined('ELITE_CUSTOM_CHANGE_CLASS') && ELITE_CUSTOM_CHANGE_CLASS == true){
										if($this->Mcharacter->char_info['grand_resets'] < 4){
											$price = 2500;
										}
										if($this->Mcharacter->char_info['grand_resets'] < 3){
											$price = 2000;
										}
										if($this->Mcharacter->char_info['grand_resets'] < 2){
											$price = 1500;
										}
										if($this->Mcharacter->char_info['grand_resets'] < 1){
											$price = 1000;
										}
									}
									json(['data' => $select, 'price' => $price, 'price_type' => $price_type]);
								}
                            } else{
                                json(['error' => __('This character is not allowed to change class.')]);
                            }
                        }
                    }
                }
            } else{
                json(['error' => __('Please login into website.')]);
            }
        }

		public function buy_class(){
            if($this->session->userdata(['user' => 'logged_in'])){
				$this->load->model('account');
                $this->load->model('character');
				
                foreach($_POST as $key => $value){
                    $this->Mcharacter->$key = trim($value);
                }
                if(!$this->Maccount->check_connect_stat($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server'])))
                    json(['error' => __('Please logout from game.')]); 
				else{
                    if(!isset($this->Mcharacter->vars['character']))
                        json(['error' => __('Invalid Character')]); 
					else{
                        if(!$this->Mcharacter->check_char($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server'])))
                            json(['error' => __('Character not found.')]); 
						else{
                            if(!isset($this->Mcharacter->vars['class_select']))
                                json(['error' => __('Invalid class selected')]); 
							else{
                                if($this->Mcharacter->vars['class_select'] == $this->Mcharacter->char_info['Class'])
                                    json(['error' => __('You already have this class.')]); 
								else{
                                    $this->vars['changeclass_config'] = $this->config->values('change_class_config');
                                    $status = $this->Maccount->get_amount_of_credits($this->session->userdata(['user' => 'username']), $this->vars['changeclass_config']['payment_type'], $this->session->userdata(['user' => 'server']), $this->session->userdata(['user' => 'id']));
                                    $price = $this->vars['changeclass_config']['price'];
                                    if($this->session->userdata('vip')){
                                        $price -= ($price / 100) * $this->session->userdata(['vip' => 'change_class_discount']);
                                    }
									if(defined('ELITE_CUSTOM_CHANGE_CLASS') && ELITE_CUSTOM_CHANGE_CLASS == true){
										if($this->Mcharacter->char_info['grand_resets'] < 4){
											$price = 2500;
										}
										if($this->Mcharacter->char_info['grand_resets'] < 3){
											$price = 2000;
										}
										if($this->Mcharacter->char_info['grand_resets'] < 2){
											$price = 1500;
										}
										if($this->Mcharacter->char_info['grand_resets'] < 1){
											$price = 1000;
										}
									}
                                    if($status < $price){
                                        json(['error' => sprintf(__('You have insufficient amount of %s'), $this->website->translate_credits($this->vars['changeclass_config']['payment_type'], $this->session->userdata(['user' => 'server'])))]);
                                    } else{
										if(isset($this->vars['changeclass_config']['min_level']) && (int)$this->vars['changeclass_config']['min_level'] > $this->Mcharacter->char_info['cLevel']){
											 json(['error' => sprintf(__('Level too low required %d level'), (int)$this->vars['changeclass_config']['min_level'])]);
											 exit;
										}
										if(isset($this->vars['changeclass_config']['min_mlevel']) && (int)$this->vars['changeclass_config']['min_mlevel'] > $this->Mcharacter->char_info['mlevel']){
											 json(['error' => sprintf(__('MasterLevel too low required %d level'), (int)$this->vars['changeclass_config']['min_mlevel'])]);
											 exit;
										}
										if(isset($this->vars['changeclass_config']['min_resets']) && (int)$this->vars['changeclass_config']['min_resets'] > $this->Mcharacter->char_info['resets']){
											 json(['error' => sprintf(__('Resets too low required %d resets'), (int)$this->vars['changeclass_config']['min_resets'])]);
											 exit;
										}
										if(isset($this->vars['changeclass_config']['max_resets']) && (int)$this->vars['changeclass_config']['max_resets'] < $this->Mcharacter->char_info['resets']){
											 json(['error' => sprintf(__('Resets too high max %d resets'), (int)$this->vars['changeclass_config']['max_resets'])]);
											 exit;
										}
										if(isset($this->vars['changeclass_config']['min_gresets']) && (int)$this->vars['changeclass_config']['min_gresets'] > $this->Mcharacter->char_info['grand_resets']){
											 json(['error' => sprintf(__('GrandResets too low required %d resets'), (int)$this->vars['changeclass_config']['min_gresets'])]);
											 exit;
										}
										if(isset($this->vars['changeclass_config']['max_gresets']) && (int)$this->vars['changeclass_config']['max_gresets'] < $this->Mcharacter->char_info['grand_resets']){
											 json(['error' => sprintf(__('GrandResets too high max %d resets'), (int)$this->vars['changeclass_config']['max_gresets'])]);
											 exit;
										}
										if(in_array($this->Mcharacter->char_info['Class'], [64,65,66,67,70,78])){
											$this->load->lib('iteminfo');
											$foundPet = false;
											$foundRaven = false;
											$horses = [];
											$ravens = [];
											$inv1 = $this->Mcharacter->load_inventory(1, $this->session->userdata(['user' => 'server']));
											$inv2 = $this->Mcharacter->load_inventory(3, $this->session->userdata(['user' => 'server']));
											$inv3 = $this->Mcharacter->load_inventory(4, $this->session->userdata(['user' => 'server']));
											if(!empty($inv1)){
												foreach($inv1 AS $key => $val){
													if(($val['item_id'] == 4 && $val['item_cat'] == 13) || ($val['item_id'] == 471 && $val['item_cat'] == 12)){
														$horses[] = $inv1[$key]['hex'];
													}
													if($val['item_id'] == 5 && $val['item_cat'] == 13){
														$ravens[] = $inv1[$key]['hex'];
													}
												}
											}
											if(!empty($inv2)){
												foreach($inv2 AS $key => $val){
													if(($val['item_id'] == 4 && $val['item_cat'] == 13) || ($val['item_id'] == 471 && $val['item_cat'] == 12)){
														$horses[] = $inv2[$key]['hex'];
													}
													if($val['item_id'] == 5 && $val['item_cat'] == 13){
														$ravens[] = $inv2[$key]['hex'];
													}
												}
											}
											if(!empty($inv3)){
												foreach($inv3 AS $key => $val){
													if(($val['item_id'] == 4 && $val['item_cat'] == 13) || ($val['item_id'] == 471 && $val['item_cat'] == 12)){
														$horses[] = $inv3[$key]['hex'];
													}
													if($val['item_id'] == 5 && $val['item_cat'] == 13){
														$ravens[] = $inv3[$key]['hex'];
													}
												}
											}
											if(!empty($horses)){
												foreach($horses AS $key => $hex){
													if(hexdec(substr($hex, 20, 2)) == 1){
														$foundPet = true;
														break;
													}
												}
											}
											if(!empty($ravens)){
												foreach($ravens AS $key => $hex){
													if(hexdec(substr($hex, 20, 2)) == 1){
														$foundRaven = true;
														break;
													}
												}
											}
											if($foundPet){
												json(['error' => __('Please unmount horse.')]);
												exit;
											}
											if($foundRaven){
												json(['error' => __('Please unmount raven.')]);
												exit;
											}
										}
										
										if(defined('ELITE_CUSTOM_CHANGE_CLASS') && ELITE_CUSTOM_CHANGE_CLASS == true){									
											$statLoweringPercentage = 20;
											$maxResets = 50;
											$currentGR = $this->Mcharacter->char_info['grand_resets'];
											$currentRes = $this->Mcharacter->char_info['resets'];
											$totalResets = ($currentGR * $maxResets) + $currentRes;										
											$resToDecrease = floor(($statLoweringPercentage / 100) * $totalResets);
											$newRes = $totalResets - $resToDecrease;
											$leftRes = $newRes % $maxResets;
											$newGR = ($newRes - $leftRes) / $maxResets;

											$newLevel = $this->Mcharacter->char_info['cLevel'];
											
											if($newGR == 0 && $currentRes == 0) { 
												$newLevel = $newLevel - (($newLevel * $statLoweringPercentage) / 100);
											}
										}
										
										if(defined('CHANGE_CLASS_REQ_ITEMS') && CHANGE_CLASS_REQ_ITEMS == true){
											$this->load->lib('iteminfo');
											$this->load->lib('createitem', [MU_VERSION, SOCKET_LIBRARY, $this->website->get_value_from_server($this->session->userdata(['user' => 'server']), 'item_size')]);
											
											$reset_items_config = $this->config->values('class_change_items_config', $this->session->userdata(['user' => 'server']));
											
											$html = '';
											
											if(!empty($reset_items_config)){
												$this->vars['reqItems'] = [];
												foreach($reset_items_config AS $cat => $items){												
													if(count($items) > 0){
														$check = $this->website->checkClassChangeItem($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), bin2hex($this->Mcharacter->vars['character']), $cat);													
														if($check == false){
															$randItem = array_rand($items, 1);
															if($this->iteminfo->setItemData($items[$randItem]['id'], $items[$randItem]['cat'], $this->website->get_value_from_server($this->session->userdata(['user' => 'server']), 'item_size'))){
																$this->createitem->setItemData($this->iteminfo->item_data);
																$this->createitem->id($items[$randItem]['id']);
																$this->createitem->cat($items[$randItem]['cat']);
																$this->createitem->refinery(false);
																$this->createitem->serial(0);
																if($this->website->get_value_from_server($this->session->userdata(['user' => 'server']), 'item_size') == 64){
																	$this->createitem->serial2(true);
																}
																if($items[$randItem]['minLvl'] == $items[$randItem]['maxLvl']){
																	$this->createitem->lvl($items[$randItem]['minLvl']);
																}
																else{
																	$this->createitem->lvl(rand($items[$randItem]['minLvl'], $items[$randItem]['maxLvl']));
																	
																}
																$this->createitem->skill(false);
																$this->createitem->luck(false);
																if($items[$randItem]['minOpt'] == $items[$randItem]['maxOpt']){
																	$this->createitem->opt($items[$randItem]['minOpt']);
																}
																else{
																	$this->createitem->opt(rand($items[$randItem]['minOpt'], $items[$randItem]['maxOpt']));
																	
																}
																if($items[$randItem]['exe'] != ''){
																	$exeData = explode('|', $items[$randItem]['exe']);
																	$iexe = explode(',', $exeData[0]);
																	foreach($iexe AS $k => $val){
																		if($val == 0){
																			unset($iexe[$k]);
																		}
																	}
																	$totaExe = count($iexe);
																	$randomizer = explode('-', $exeData[1]);
																	$exe_opts = [0 => 1, 1 => 2, 2 => 4, 3 => 8, 4 => 16, 5 => 32];
																	
																	if($randomizer[1] > 0){
																		if($randomizer[1] > $totaExe)
																			$randomizer[1] = $totaExe;
																		$rand = rand($randomizer[0], $randomizer[1]);
																		if($rand == 0){
																			$iexe = [];
																		}
																		else{
																			$iexe = array_rand($iexe, $rand);
																			if(!is_array($iexe))
																				$iexe = [$iexe => 1];
																			else{
																				$newArr = [];
																				foreach($iexe AS $k => $val){
																					$newArr[$val] = 1;
																				}
																				$iexe = $newArr;
																			}
																		}
																	}
																	
																	$exe = 0;
																	if(!empty($iexe)){		
																		foreach($iexe as $key => $exe_opt){
																			if($exe_opt == 1){
																				$exe += $exe_opts[$key];
																			}
																		}
																	}
																	$this->createitem->addStaticExe($exe);
																}

																$items[$randItem]['hex'] = $this->createitem->to_hex();
																$this->website->addChangeClassReqItems($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), bin2hex($this->Mcharacter->vars['character']), $cat, $randItem, $items[$randItem]);
															}
														}
													}
												}
											}
										}

                                        if($this->Mcharacter->check_equipment($this->session->userdata(['user' => 'server']))){
                                            if(isset($this->vars['changeclass_config']['class_list'][$this->Mcharacter->char_info['Class']]) && in_array($this->Mcharacter->vars['class_select'], $this->vars['changeclass_config']['class_list'][$this->Mcharacter->char_info['Class']])){
                                               if(defined('CHANGE_CLASS_REQ_ITEMS') && CHANGE_CLASS_REQ_ITEMS == true){
												    $changeClassTimes = $this->Mcharacter->count_change_class_times($this->session->userdata(['user' => 'server']), $this->Mcharacter->char_info['id']);

													if(MAX_CLASS_CHANGE <= $changeClassTimes['count']){
														json(['error' => __('Change class limit reached.')]);
														exit;
													}
												    $countItems = $this->website->checkNotCompletedItemCountChangeClass($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), bin2hex($this->Mcharacter->vars['character']));
													if($countItems > 0){
														 json(['error' => __('Please complete all required items.')]);
														 exit;
													}
													else{
														$this->website->removeChangeClassItems($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), bin2hex($this->Mcharacter->vars['character']));
													}
											    }
												
												if(isset($this->vars['changeclass_config']['skill_tree']['active']) && $this->vars['changeclass_config']['skill_tree']['active'] == 1){
                                                    if(in_array($this->Mcharacter->char_info['Class'], $this->resetSkillTreeClass)){
                                                        $this->Mcharacter->reset_skill_tree($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $this->config->config_entry('character_' . $this->session->userdata(['user' => 'server']) . '|skill_tree_type'), isset($this->vars['changeclass_config']['skill_tree']['reset_level']) ? $this->vars['changeclass_config']['skill_tree']['reset_level'] : 0, isset($this->vars['changeclass_config']['skill_tree']['reset_points']) ? $this->vars['changeclass_config']['skill_tree']['reset_points'] : 0, isset($this->vars['changeclass_config']['skill_tree']['points_multiplier']) ? $this->vars['changeclass_config']['skill_tree']['points_multiplier'] : 0);
                                                    }
                                                }
												
												$baseStats = $this->Mcharacter->getBaseStats($this->Mcharacter->char_info['Class'], $this->session->userdata(['user' => 'server']));
												$new_stats = 0;
												if($this->Mcharacter->char_info['Strength'] > $baseStats['Strength']){
													$new_stats += $this->Mcharacter->char_info['Strength'] - $baseStats['Strength'];
												}
												if($this->Mcharacter->char_info['Dexterity'] > $baseStats['Dexterity']){
													$new_stats += $this->Mcharacter->char_info['Dexterity'] - $baseStats['Dexterity'];
												}
												if($this->Mcharacter->char_info['Energy'] > $baseStats['Energy']){
													$new_stats += $this->Mcharacter->char_info['Energy'] - $baseStats['Energy'];
												}
												if($this->Mcharacter->char_info['Vitality'] > $baseStats['Vitality']){
													$new_stats += $this->Mcharacter->char_info['Vitality'] - $baseStats['Vitality'];
												}
												if(in_array($this->Mcharacter->char_info['Class'], [64, 65, 66, 67, 70, 78]) && $this->Mcharacter->char_info['Leadership'] > $baseStats['Leadership']){
													$new_stats += $this->Mcharacter->char_info['Leadership'] - $baseStats['Leadership'];
												}
								
                                                $this->Mcharacter->update_char_class($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
												$this->Mcharacter->clear_quests_list($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $this->Mcharacter->vars['class_select']);
												if(defined('CHANGE_CLASS_REQ_ITEMS') && CHANGE_CLASS_REQ_ITEMS == true){
													 $this->Mcharacter->add_change_class_log($this->session->userdata(['user' => 'server']), $this->Mcharacter->char_info['id'], $this->Mcharacter->char_info['Class'], $this->Mcharacter->vars['class_select']);
												}
												$this->Mcharacter->check_char($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
												$baseStats = $this->Mcharacter->getBaseStats($this->Mcharacter->char_info['Class'], $this->session->userdata(['user' => 'server']));
												$this->Mcharacter->reset_stats($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $this->Mcharacter->char_info['Name'], $new_stats, $baseStats);
												if(defined('ELITE_CUSTOM_CHANGE_CLASS') && ELITE_CUSTOM_CHANGE_CLASS == true){		
													$this->Mcharacter->setNewResGR($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $this->Mcharacter->char_info['Name'], $newGR, $leftRes, $newLevel);
												}
												
                                                $this->Maccount->add_account_log('Changed Character ' . $this->Mcharacter->char_info['Name'] . ' class for ' . $this->website->translate_credits($this->vars['changeclass_config']['payment_type'], $this->session->userdata(['user' => 'server'])), -$price, $this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
                                                $this->website->charge_credits($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $price, $this->vars['changeclass_config']['payment_type']);
                                                json(['success' => __('Character class successfully changed.')]);
                                            } else{
                                                json(['error' => __('You are not allowed to use this class.')]);
                                            }
                                       } 
									   else{
											json(['error' => __('Before changing class please remove your equipped items.')]);
                                       }
                                    }
                                }
                            }
                        }
                    }
                }
            } else{
                json(['error' => __('Please login into website.')]);
            }
        }

        public function change_name(){
            if($this->session->userdata(['user' => 'logged_in'])){
				$this->load->model('account');
                $this->load->model('character');
                foreach($_POST as $key => $value){
                    $this->Mcharacter->$key = trim($value);
                }
				
				$old_name = $this->website->hex2bin($this->Mcharacter->vars['old_name']);
				
                if(!$this->Maccount->check_connect_stat($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server'])))
                    json(['error' => __('Please logout from game.')]); 
				else{
                    if(!isset($this->Mcharacter->vars['old_name']) || $this->Mcharacter->vars['old_name'] == ''){
                        json(['error' => __('Old name can not be empty.')]);
                    } else{
                        if(!isset($this->Mcharacter->vars['new_name']) || $this->Mcharacter->vars['new_name'] == ''){
                            json(['error' => __('New name can not be empty.')]);
                        } else{
                            if(!preg_match('/^[' . str_replace('/', '\/', quotemeta($this->config->config_entry('changename|allowed_pattern'))) . ']+$/u', $this->Mcharacter->vars['new_name'])){
                                json(['error' => __('You are using forbidden chars in your new name.')]);
                            } else{
                                if(mb_strlen($this->Mcharacter->vars['new_name']) < 4 || mb_strlen($this->Mcharacter->vars['new_name']) > $this->config->config_entry('changename|max_length')){
                                    json(['error' => sprintf(__('Character Name can be 4-%d chars long!'), $this->config->config_entry('changename|max_length'))]);
                                } else{
                                    if($this->Mcharacter->vars['new_name'] === $old_name){
                                        json(['error' => __('New name can not be same as old.')]);
                                    } else{
                                        $old_char_data = $this->Mcharacter->check_if_char_exists($old_name, $this->session->userdata(['user' => 'server']));
                                        $new_char_data = $this->Mcharacter->check_if_char_exists($this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
                                        if(!$old_char_data){
                                            json(['error' => __('Old character not found on your account.')]);
                                        } else{
                                            if(strtolower($old_char_data['AccountId']) != strtolower($this->session->userdata(['user' => 'username']))){
                                                json(['error' => __('You are not owner of this character.')]);
                                            } else{
                                                if($new_char_data){
                                                    json(['error' => __('Character with this name already exists.')]);
                                                } else{
                                                    if($this->config->config_entry('changename|check_guild') == 1 && $this->Mcharacter->has_guild($old_name, $this->session->userdata(['user' => 'server']))){
                                                        json(['error' => __('You are not allowed to change name while you are in guild.')]);
                                                    } else{
                                                        $restricted_words = explode(',', $this->config->config_entry('changename|forbidden'));
                                                        $restrict = false;
                                                        foreach($restricted_words as $key => $words){
                                                            if(stripos($this->Mcharacter->vars['new_name'], $words) !== false){
                                                                $restrict = true;
                                                                break;
                                                            }
                                                        }
                                                        if($restrict != false){
                                                            json(['error' => __('Found forbidden word in new character name please fix it.')]);
                                                        } else{
                                                            $status = $this->Maccount->get_amount_of_credits($this->session->userdata(['user' => 'username']), $this->config->config_entry('changename|price_type'), $this->session->userdata(['user' => 'server']), $this->session->userdata(['user' => 'id']));
                                                            $price = $this->config->config_entry('changename|price');
                                                            if($this->session->userdata('vip')){
                                                                $price -= ($price / 100) * $this->session->userdata(['vip' => 'change_name_discount']);
                                                            }
                                                            if($status < $price){
                                                                json(['error' => sprintf(__('You have insufficient amount of %s'), $this->website->translate_credits($this->config->config_entry('changename|price_type'), $this->session->userdata(['user' => 'server'])))]);
                                                            } else{
																if($this->Mcharacter->check_IGC_PersonalStore_ChangeName($old_char_data['id'], $this->session->userdata(['user' => 'server'])) != false){
																	echo json_encode(['error' => __('Please login into game with this character and logout before next change')]);
																	exit;
																}
                                                                $this->website->charge_credits($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $price, $this->config->config_entry('changename|price_type'));
																if($this->Mcharacter->update_account_character($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']))){
                                                                    if($this->config->config_entry('changename|check_guild') == 0){
                                                                        $this->Mcharacter->update_guild($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
                                                                        $this->Mcharacter->update_guild_member($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
                                                                    }
                                                                    $this->Mcharacter->update_character($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
                                                                    $this->Mcharacter->update_option_data($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
                                                                    $this->Mcharacter->update_t_friendlist($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
                                                                    $this->Mcharacter->update_t_friendmail($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
                                                                    $this->Mcharacter->update_t_friendmain($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
																	$this->Mcharacter->update_t_cguid($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
																	$this->Mcharacter->update_T_CurCharName($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
																	$this->Mcharacter->update_T_Event_Inventory($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
																	if($this->config->config_entry('changename|user_master_level') == 1 && (strtolower($this->config->values('table_config', [$this->session->userdata(['user' => 'server']), 'master_level', 'table'])) != 'character' && trim($this->config->values('table_config', [$this->session->userdata(['user' => 'server']), 'master_level', 'table'])) != '')){
                                                                        $this->Mcharacter->update_master_level_table($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
                                                                    }

                                                                    $this->Mcharacter->update_IGC_Gens($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
                                                                    $this->Mcharacter->update_IGC_GensAbuse($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
																	$this->Mcharacter->update_IGC_HuntingRecord($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
																	$this->Mcharacter->update_IGC_HuntingRecordOption($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
																	$this->Mcharacter->update_IGC_LabyrinthClearLog($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
																	$this->Mcharacter->update_IGC_LabyrinthInfo($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
																	$this->Mcharacter->update_IGC_LabyrinthLeagueLog($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
																	$this->Mcharacter->update_IGC_LabyrinthLeagueUser($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
																	$this->Mcharacter->update_IGC_LabyrinthMissionInfo($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
																	$this->Mcharacter->update_IGC_MixLostItemInfo($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
																	$this->Mcharacter->update_IGC_Muun_Inventory($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
																	$this->Mcharacter->update_IGC_Muun_Period($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
																	$this->Mcharacter->update_IGC_RestoreItem_Inventory($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
                                                                    $this->Mcharacter->update_IGC_PeriodBuffInfo($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
                                                                    $this->Mcharacter->update_IGC_PeriodExpiredItemInfo($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
                                                                    $this->Mcharacter->update_IGC_PeriodItemInfo($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
																	$this->Mcharacter->update_IGC_PentagramInfo($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
																	$this->Mcharacter->update_IGC_PStore_Items($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
																	$this->Mcharacter->update_IGC_PStore_Data($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));																					   
                                                                    $this->Mcharacter->update_IGC_PersonalStore_Info($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
																	$this->Mcharacter->insert_IGC_PersonalStore_ChangeName($old_name, $this->Mcharacter->vars['new_name'], $old_char_data['id'], $this->session->userdata(['user' => 'server']));
																	
																	$this->Mcharacter->update_IGC_ArtifactInfo($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));																					   
																	$this->Mcharacter->update_IGC_BlessingBox_Character($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));																					   
																	$this->Mcharacter->update_IGC_HuntPoint($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));																					   
																	$this->Mcharacter->update_IGC_StatsSystem($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));																					   
																	$this->Mcharacter->update_IGC_AbilityCardInfo($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));																					   
																	$this->Mcharacter->update_IGC_WingCoreInfo($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));																					   
																	
																	
																	$this->Mcharacter->update_T_3rd_Quest_Info($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
                                                                    $this->Mcharacter->update_T_GMSystem($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
                                                                    $this->Mcharacter->update_T_LUCKY_ITEM_INFO($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
                                                                    $this->Mcharacter->update_T_PentagramInfo($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
                                                                    $this->Mcharacter->update_T_QUEST_EXP_INFO($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
                                                                    $this->Mcharacter->update_T_WaitFriend($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
                                                                    $this->Mcharacter->update_T_WaitFriend($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
                                                                    $this->Mcharacter->update_T_PSHOP_ITEMVALUE_INFO($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
                                                                    
																	$this->Mcharacter->update_PetWarehouse($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
                                                                    
																	$this->Mcharacter->update_C_PlayerKiller_Info($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
																	$this->Mcharacter->update_C_PlayerKiller_Info2($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
																	$this->Mcharacter->update_C_Monster_KillCount($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
																	$this->Mcharacter->update_Gens_Left($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
																	$this->Mcharacter->update_Gens_Rank($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
																	$this->Mcharacter->update_Gens_Reward($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
																	$this->Mcharacter->update_EnhanceSkillTree($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
																	$this->Mcharacter->update_EventEntryCount($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
																	$this->Mcharacter->update_EventEntryLimit($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
																	$this->Mcharacter->update_EventInventory($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
																	$this->Mcharacter->update_FavoriteWarpList($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
																	$this->Mcharacter->update_GremoryCase($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
																	$this->Mcharacter->update_HelperData($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
																	$this->Mcharacter->update_MuHelperPlus($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
																	$this->Mcharacter->update_MuQuestInfo($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
																	$this->Mcharacter->update_MuunInventory($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
																	$this->Mcharacter->update_PentagramJewel($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
																	$this->Mcharacter->update_PersonalShopRenewalList($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
																	$this->Mcharacter->update_PShopItemValue($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
																	$this->Mcharacter->update_QuestGuide($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
																	$this->Mcharacter->update_QuestKillCount($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
																	$this->Mcharacter->update_QuestWorld($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
																	$this->Mcharacter->update_RankingBloodCastle($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
																	$this->Mcharacter->update_RankingCastleSiege($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
																	$this->Mcharacter->update_RankingChaosCastle($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
																	$this->Mcharacter->update_RankingDevilSquare($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
																	$this->Mcharacter->update_RankingDuel($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
																	$this->Mcharacter->update_RankingIllusionTemple($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
																	
																	$this->vars['table_config'] = $this->config->values('table_config', $this->session->userdata(['user' => 'server']));
																	
																	if(isset($this->vars['table_config']['bc']) && $this->vars['table_config']['bc']['db'] != ''){
																		$this->Mcharacter->update_EVENT_INFO($old_name, $this->Mcharacter->vars['new_name'], $this->vars['table_config']['bc'], $this->session->userdata(['user' => 'server']));
																		$this->Mcharacter->update_EVENT_INFO_BC_5TH($old_name, $this->Mcharacter->vars['new_name'], $this->vars['table_config']['bc'], $this->session->userdata(['user' => 'server']));
																		$this->Mcharacter->update_EVENT_INFO_CC($old_name, $this->Mcharacter->vars['new_name'], $this->vars['table_config']['bc'], $this->session->userdata(['user' => 'server']));
																		$this->Mcharacter->update_EVENT_INFO_IT($old_name, $this->Mcharacter->vars['new_name'], $this->vars['table_config']['bc'], $this->session->userdata(['user' => 'server']));
																		$this->Mcharacter->update_T_ENTER_CHECK_BC($old_name, $this->Mcharacter->vars['new_name'], $this->vars['table_config']['bc'], $this->session->userdata(['user' => 'server']));
																		$this->Mcharacter->update_T_ENTER_CHECK_ILLUSION_TEMPLE($old_name, $this->Mcharacter->vars['new_name'], $this->vars['table_config']['bc'], $this->session->userdata(['user' => 'server']));
																	}
																	
																	$this->Mcharacter->update_DmN_Ban_List($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
                                                                    $this->Mcharacter->update_DmN_Gm_List($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
                                                                    $this->Mcharacter->update_DmN_Market($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
                                                                    $this->Mcharacter->update_DmN_Market_Logs($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
                                                                    $this->Mcharacter->update_DmN_Votereward_Ranking($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'server']));
                                                                    $this->Maccount->add_account_log('Changed Name To ' . $this->Mcharacter->vars['new_name'] . ' for ' . $this->website->translate_credits($this->config->config_entry('changename|price_type'), $this->session->userdata(['user' => 'server'])), -$price, $this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
                                                                    $this->Mcharacter->add_to_change_name_history($old_name, $this->Mcharacter->vars['new_name'], $this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
                                                                    json(['success' => __('Character Name Successfully Changed.'), 'new_name' => bin2hex($this->Mcharacter->vars['new_name'])]);
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
                    }
                }
            } else{
                json(['error' => __('Please login into website.')]);
            }
        }

		public function exchange_wcoins(){
            if($this->session->userdata(['user' => 'logged_in'])){
                $this->load->model('account');
                $this->load->model('character');
                foreach($_POST as $key => $value){
                    $this->Mcharacter->$key = trim($value);
                }
                if(!$this->Maccount->check_connect_stat($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server'])))
                    json(['error' => __('Please logout from game.')]); 
				else{
                    if(!preg_match('/^[0-9]+$/', $this->Mcharacter->vars['credits']))
                        json(['error' => sprintf(__('Invalid amount of %s'), $this->website->translate_credits($this->vars['wcoin_config']['credits_type'], $this->session->userdata(['user' => 'server'])))]); 
					else{
                        $this->vars['wcoin_config'] = $this->config->values('wcoin_exchange_config', $this->session->userdata(['user' => 'server']));
                        $this->vars['table_config'] = $this->config->values('table_config', $this->session->userdata(['user' => 'server']));
                        if(isset($this->vars['table_config']['wcoins']) && $this->vars['wcoin_config'] != false && $this->vars['wcoin_config']['active'] == 1){
                            if($this->Mcharacter->vars['credits'] < $this->vars['wcoin_config']['min_rate'])
                                json(['error' => vsprintf(__('Minimal exchange rate is %d %s'), [$this->vars['wcoin_config']['min_rate'], $this->website->translate_credits($this->vars['wcoin_config']['credits_type'], $this->session->userdata(['user' => 'server']))])]); 
							else{
                                if($this->vars['wcoin_config']['reward_coin'] < 0)
                                    $total = floor($this->Mcharacter->vars['credits'] * abs($this->vars['wcoin_config']['reward_coin'])); 
								else
                                    $total = floor($this->Mcharacter->vars['credits'] / $this->vars['wcoin_config']['reward_coin']);
                                if($this->vars['wcoin_config']['change_back'] == 1){
                                    if($this->Mcharacter->vars['exchange_type'] == 1){
                                        goto exchange_wcoins;
                                    } else{
                                        goto exchange_credits;
                                    }
                                }
                                exchange_wcoins:
                                $status = $this->Maccount->get_amount_of_credits($this->session->userdata(['user' => 'username']), $this->vars['wcoin_config']['credits_type'], $this->session->userdata(['user' => 'server']), $this->session->userdata(['user' => 'id']));
                                if($status < $this->Mcharacter->vars['credits'])
                                    json(['error' => sprintf(__('You have insufficient amount of %s'), $this->website->translate_credits($this->vars['wcoin_config']['credits_type'], $this->session->userdata(['user' => 'server'])))]); 
								else{
                                    $this->website->charge_credits($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $this->Mcharacter->vars['credits'], $this->vars['wcoin_config']['credits_type']);
                                    $this->Maccount->add_account_log('Exchange ' . $this->website->translate_credits($this->vars['wcoin_config']['credits_type'], $this->session->userdata(['user' => 'server'])) . ' to' . __('WCoins'), -$this->Mcharacter->vars['credits'], $this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
                                    $this->Mcharacter->add_wcoins($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $this->session->userdata(['user' => 'id']), $total, $this->vars['table_config']['wcoins']);
                                    json(['success' => __('WCoins successfully exchanged.')]);
                                }
                                exchange_credits:
                                if($status = $this->Mcharacter->get_wcoins($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'id']), $this->vars['table_config']['wcoins'], $this->session->userdata(['user' => 'server']))){
                                    if($status < $this->Mcharacter->vars['credits'])
                                        json(['error' => sprintf(__('You have insufficient amount of %s'), __('WCoins'))]); 
									else{
                                        $this->website->add_credits($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $total, $this->vars['wcoin_config']['credits_type']);
                                        $this->Maccount->add_account_log('Exchange ' . __('WCoins') . ' to ' . $this->website->translate_credits($this->vars['wcoin_config']['credits_type'], $this->session->userdata(['user' => 'server'])), -$this->Mcharacter->vars['credits'], $this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
                                        $this->Mcharacter->remove_wcoins($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $this->session->userdata(['user' => 'id']), $this->vars['table_config']['wcoins']);
                                        json(['success' => __('WCoins successfully exchanged.')]);
                                    }
                                } else{
                                    json(['error' => __('Unable to exchange Wcoins')]);
                                }
                            }
                        } else{
                            json(['error' => __('This module has been disabled.')]);
                        }
                    }
                }
            } else{
                json(['error' => __('Please login into website.')]);
            }
        }

        public function switch_language(){
            if(isset($_POST['lang'])){
                setcookie("dmn_language", htmlspecialchars($_POST['lang']), strtotime('+5 days', time()), "/");
				json(['success' => true]);																								
            }
			else{
				json(['error' => true]); 
			}
        }
		
		public function switch_theme(){
            if(isset($_POST['theme'])){
                setcookie("dmn_template", htmlspecialchars($_POST['theme']), strtotime('+5 days', time()), "/");
				json(['success' => true]);																								
            }
			json(['error' => true]); 
        }

		public function paypal(){
            if($this->session->userdata(['user' => 'logged_in'])){
                $this->load->model('donate');
                if(isset($_POST['proccess_paypal'])){
					$isBattlePass = 0;
					$isKeys = 0;
					if(in_array($_POST['proccess_paypal'], ['b1', 'b2'])){
						$package_data = [
							'reward' => ($_POST['proccess_paypal'] == 'b1') ? 1 : 2,
							'price' => ($_POST['proccess_paypal'] == 'b1') ? BPASS_SILVER_PRICE : BPASS_PLATINUM_PRICE,
							'currency' => BPASS_CURRENCY
						];
						$isBattlePass = 1;
					}
					else{
						if(in_array($_POST['proccess_paypal'], ['k1'])){
							$package_data = [
								'reward' => AMOUNT_OF_KEYS,
								'price' => WHEEL_KEYS_PRICE,
								'currency' => WHEEL_KEYS_CURRENCY
							];
							$isKeys = 1;
						}
						else{
							$package_data = $this->Mdonate->get_paypal_package_data_by_id($_POST['proccess_paypal'], $this->session->userdata(['user' => 'server']));
						}
					}
                    if($package_data != false){
                        if($this->Mdonate->insert_paypal_order($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $package_data['reward'], $package_data['price'], $package_data['currency'], $isBattlePass, $isKeys))
                            json($this->Mdonate->get_paypal_data($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']))); 
						else
                            json(['error' => __('Unable to checkout please try again.')]);
                    } 
					else{
                        json(['error' => __('Paypal package not found.')]);
                    }
                } 
				else{
                    json(['error' => __('Unable to checkout please try again.')]);
                }
            } 
			else{
                json(['error' => __('Please login into website.')]);
            }
        }

        public function paycall(){
            if($this->session->userdata(['user' => 'logged_in'])){
                $this->load->model('donate');
                if(isset($_POST['proccess_paycall'])){
                    if($package_data = $this->Mdonate->get_paycall_package_data_by_id($_POST['proccess_paycall'], $this->session->userdata(['user' => 'server']))){
                        if($this->Mdonate->insert_paycall_order($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $package_data['reward'], $package_data['price']))
                            json($this->Mdonate->get_paycall_data($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $package_data['reward'], $package_data['price'])); 
						else
                            json(['error' => __('Unable to checkout please try again.')]);
                    } else{
                        json(['error' => __('Paycall package not found.')]);
                    }
                } else{
                    json(['error' => __('Unable to checkout please try again.')]);
                }
            } else{
                json(['error' => __('Please login into website.')]);
            }
        }

        public function hide_chars(){
            if($this->session->userdata(['user' => 'logged_in'])){
                $this->load->model('account');
                if($this->config->config_entry('account|hide_char_enabled') == 1){														
                    $status = $this->Maccount->get_amount_of_credits($this->session->userdata(['user' => 'username']), $this->config->config_entry('account|hide_char_price_type'), $this->session->userdata(['user' => 'server']), $this->session->userdata(['user' => 'id']));
                    $price = $this->config->config_entry('account|hide_char_price');
                    if($this->session->userdata('vip')){
                        $price -= ($price / 100) * $this->session->userdata(['vip' => 'hide_info_discount']);
                    }
                    if($status < $price){
                        json(['error' => sprintf(__('You have insufficient amount of %s'), $this->website->translate_credits($this->config->config_entry('account|hide_char_price_type'), $this->session->userdata(['user' => 'server'])))]);
                    } else{
                        $check_hide = $this->Maccount->check_hide_time($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
                        if($check_hide == 'None'){
                            $this->Maccount->add_hide($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $price);
                            $this->website->charge_credits($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $price, $this->config->config_entry('account|hide_char_price_type'));
                            json(['success' => __('You have successfully hidden your chars')]);
                        } else{   
                            $this->Maccount->extend_hide($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $check_hide, $price);
                            $this->website->charge_credits($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $price, $this->config->config_entry('account|hide_char_price_type'));
                            json(['success' => __('You char hide time has been extended')]); 
                        }
                    }
                } else{
                    json(['error' => __('This module has been disabled.')]);
                }
            } else{
                json(['error' => __('Please login into website.')]);
            }
        }
		
		public function hide_chars_pk(){
            if($this->session->userdata(['user' => 'logged_in'])){
                $this->load->model('account');
                if(defined('ELITE_KILLER_HIDE') && ELITE_KILLER_HIDE == true){												
                    $status = $this->Maccount->get_amount_of_credits($this->session->userdata(['user' => 'username']), ELITE_KILLER_HIDE_PRICE_TYPE, $this->session->userdata(['user' => 'server']), $this->session->userdata(['user' => 'id']));
                    $price = ELITE_KILLER_HIDE_PRICE;
                    if($status < $price){
                        json(['error' => sprintf(__('You have insufficient amount of %s'), $this->website->translate_credits(ELITE_KILLER_HIDE_PRICE_TYPE, $this->session->userdata(['user' => 'server'])))]);
                    } else{
                        $check_hide = $this->Maccount->check_hide_time_pk($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
                        if($check_hide == 'None'){
                            $this->Maccount->add_hide_pk($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $price);
                            $this->website->charge_credits($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $price, ELITE_KILLER_HIDE_PRICE_TYPE);
                            json(['success' => __('You have successfully hidden your chars PK stats')]);
                        } else{   
                            $this->Maccount->extend_hide_pk($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $check_hide, $price);
                            $this->website->charge_credits($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $price, ELITE_KILLER_HIDE_PRICE_TYPE);
                            json(['success' => __('You char hide time has been extended')]); 
                        }
                    }
                } else{
                    json(['error' => __('This module has been disabled.')]);
                }
            } else{
                json(['error' => __('Please login into website.')]);
            }
        }

        public function download(){
            $this->load->model('admin');
            if(!isset($_POST['image'])){
                exit;
            } else{
                if(!ctype_digit($_POST['image'])){
                    exit;
                }
                if(!$image = $this->Madmin->check_gallery_image($_POST['image'])){
                    exit;
                } else{
                    $file = BASEDIR . 'assets' . DS . 'uploads' . DS . 'normal' . DS . $image['name'];
                    if(file_exists($file)){
                        header('Pragma: public');
                        header('Cache-Control: public, no-cache');
                        header('Content-Type: application/octet-stream');
                        header('Content-Length: ' . filesize($file));
                        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
                        header('Content-Transfer-Encoding: binary');
                        readfile($file);
                    } else{
                        exit;
                    }
                }
            }
        }

		private function generate_string($input, $strength = 10) {
			$input_length = strlen($input);
			$random_string = '';
			for($i = 0; $i < $strength; $i++) {
				$random_character = $input[mt_rand(0, $input_length - 1)];
				$random_string .= $random_character;
			}
		  
			return $random_string;
		}

		public function captcha($width = 200, $heigth = 50, $letter_spaccing = 170){
			//$_SESSION['captcha_text'] = [];
			$permitted_chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ';

			$image = imagecreatetruecolor(abs((int)$width), abs((int)$heigth));
			 
			imageantialias($image, true);
			 
			$colors = [];
			 
			$red = rand(125, 175);
			$green = rand(125, 175);
			$blue = rand(125, 175);
			 
			for($i = 0; $i < 5; $i++) {
			  $colors[] = imagecolorallocate($image, $red - 20*$i, $green - 20*$i, $blue - 20*$i);
			}
			 
			imagefill($image, 0, 0, $colors[0]);
			 
			for($i = 0; $i < 10; $i++) {
			  imagesetthickness($image, rand(2, 10));
			  $line_color = $colors[rand(1, 4)];
			  imagerectangle($image, rand(-10, abs((int)$width)), rand(-10, 10), rand(-10, abs((int)$width)), rand(40, 60), $line_color);
			}
			 
			$black = imagecolorallocate($image, 0, 0, 0);
			$white = imagecolorallocate($image, 255, 255, 255);
			$textcolors = [$black, $white];
			 
			$fonts = [BASEDIR . 'assets' . DS . 'default_assets' . DS . 'fonts' . DS . 'arial.ttf'];
			 
			$string_length = 6;
			$captcha_string = $this->generate_string($permitted_chars, $string_length);
			
			$_SESSION['captcha_text'] = [];	
			$_SESSION['captcha_text'][$captcha_string] = $captcha_string;
			 
			for($i = 0; $i < $string_length; $i++) {
			  $letter_space = abs((int)$letter_spaccing)/$string_length;
			  $initial = 15;
			   
			  imagettftext($image, 24, rand(-15, 15), $initial + $i*$letter_space, rand(25, 45), $textcolors[rand(0, 1)], $fonts[array_rand($fonts)], $captcha_string[$i]);
			}
			 
			header('Content-type: image/png');
			imagepng($image);
			imagedestroy($image);
		}
		
		public function check_captcha($captcha){
			if(empty($_SESSION['captcha_text']))
				return false;
			else{
				if(!in_array($captcha, $_SESSION['captcha_text'])){
					unset($_SESSION['captcha_text'][$captcha]);
					return false;
				}
				else{
					unset($_SESSION['captcha_text'][$captcha]);
					return true;
				}
			}
		}
    }