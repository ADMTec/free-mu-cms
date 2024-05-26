<?php
    in_file();

    class gmcp extends controller
    {
        protected $vars = [], $errors = [];

        public function __construct(){
            parent::__construct();
            $this->load->helper('website');
            $this->load->lib('session', ['DmNCMS']);
			$this->session->checkSession();
			$this->load->lib('csrf');						 
            $this->load->model('gm');
        }

        public function index(){
            if($this->session->userdata(['user' => 'is_gm'])){
                $this->load->view('gmcp' . DS . 'view.header');
                $this->load->view('gmcp' . DS . 'view.sidebar');
                $this->vars['announcement'] = $this->Mgm->load_announcement();
                $this->load->view('gmcp' . DS . 'view.index', $this->vars);
                $this->load->view('gmcp' . DS . 'view.footer');
            } else{
                $this->login();
            }
        }

        public function login(){
            if(count($_POST) > 0){
                foreach($_POST as $key => $value){
                    $this->Mgm->$key = trim($value);
                }
                if(!isset($this->Mgm->vars['username']))
                    $this->vars['error'] = 'Please enter username1.'; 
                else{
                    if($this->Mgm->vars['username'] == '')
                        $this->vars['error'] = 'Please enter username.'; 
                    else{
                        if(!isset($this->Mgm->vars['password']))
                            $this->vars['error'] = 'Please enter password.'; 
                        else{
                            if($this->Mgm->vars['password'] == '')
                                $this->vars['error'] = 'Please enter password.'; 
                            else{
                                if(!$this->Mgm->valid_username($this->Mgm->vars['username']))
                                    $this->vars['error'] = 'Invalid Username.'; 
                                else{
                                    if(!$this->Mgm->valid_username($this->Mgm->vars['password']))
                                        $this->vars['error'] = 'Invalid Password.'; 
                                    else{
                                        if(!isset($this->Mgm->vars['server']))
                                            $this->vars['error'] = 'Please select server.'; 
                                        else{
                                            if($this->Mgm->check_gm_in_list()){
                                                if($this->Mgm->login_gm($this->Mgm->vars['server'])){
                                                    header('Location: ' . $this->config->base_url . 'gmcp');
                                                } else{
                                                    $this->vars['error'] = 'Wrong username and/or password.';
                                                }
                                            } else{
                                                $this->vars['error'] = 'Gm account nof found.';
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $this->load->view('gmcp' . DS . 'view.login', $this->vars);
        }

        public function logout(){
            $this->session->destroy();
            header('Location: ' . $this->config->base_url . 'gmcp');
        }

        public function search(){
            if($this->session->userdata(['user' => 'is_gm'])){
                $this->load->view('gmcp' . DS . 'view.header');
                $this->load->view('gmcp' . DS . 'view.sidebar');
                if($this->session->userdata(['user' => 'can_search_acc']) == 1){
                    if(isset($_POST['search_acc'])){
                        foreach($_POST as $key => $value){
                            $this->Mgm->$key = trim($value);
                        }
                        switch($this->Mgm->vars['type']){
                            case 1:
                                if(!$this->Mgm->valid_username($this->Mgm->vars['name']))
                                    $this->vars['error'] = 'Invalid account name.'; 
                                else{
                                    if(!$this->vars['account'] = $this->Mgm->search_acc($this->session->userdata(['user' => 'server']))){
                                        $this->vars['acc_not_found'] = 'Account not found';
                                    } else{
                                        $this->vars['ip'] = $this->Mgm->find_ip($this->vars['account']['AccountId'], $this->session->userdata(['user' => 'server']));
                                    }
                                }
                                break;
                            case 2:
                                if(!$this->Mgm->valid_username($this->Mgm->vars['name']))
                                    $this->vars['error'] = 'Invalid char name.'; 
                                else{
                                    if(!$this->vars['account'] = $this->Mgm->search_char($this->session->userdata(['user' => 'server']))){
                                        $this->vars['acc_not_found'] = 'Character not found';
                                    } else{
                                        $this->vars['ip'] = $this->Mgm->find_ip($this->vars['account']['AccountId'], $this->session->userdata(['user' => 'server']));
                                    }
                                }
                                break;
                        }
                    }
                } else{
                    $this->vars['not_allowed'] = 'Your access level is too low to use this action';
                }
                $this->load->view('gmcp' . DS . 'view.search', $this->vars);
                $this->load->view('gmcp' . DS . 'view.footer');
            } else{
                $this->login();
            }
        }

        public function ban(){
            if($this->session->userdata(['user' => 'is_gm'])){
                $this->load->view('gmcp' . DS . 'view.header');
                $this->load->view('gmcp' . DS . 'view.sidebar');
                if($this->session->userdata(['user' => 'can_ban_acc']) == 1){
                    if(isset($_POST['ban'])){
                        foreach($_POST as $key => $value){
                            $this->Mgm->$key = trim($value);
                        }
                        switch($this->Mgm->vars['type']){
                            case 1:
                                if(!$this->Mgm->valid_username($this->Mgm->vars['name']))
                                    $this->vars['error'] = 'Invalid account name.'; 
                                else{
                                    if(strtotime($this->Mgm->vars['time']) < time() && !isset($this->Mgm->vars['permanent_ban'])){
                                        $this->vars['error'] = 'Wrong ban time.';
                                    } else{
                                        if($check = $this->Mgm->check_account($this->session->userdata(['user' => 'server']))){
                                            if($check['bloc_code'] != 1){
                                                $this->Mgm->ban_account($this->session->userdata(['user' => 'server']));
                                                $this->Mgm->add_to_banlist();
                                                $this->Mgm->add_gm_log('Blocked account: ' . $this->Mgm->vars['name'], $this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
                                                $this->vars['success'] = 'Account successfully banned.';
                                            } else{
                                                $this->vars['error'] = 'Account already banned.';
                                            }
                                        } else{
                                            $this->vars['error'] = 'Account not found.';
                                        }
                                    }
                                }
                                break;
                            case 2:
                                if(!$this->Mgm->valid_username($this->Mgm->vars['name']))
                                    $this->vars['error'] = 'Invalid char name.'; 
                                else{
                                    if(strtotime($this->Mgm->vars['time']) < time() && !isset($this->Mgm->vars['permanent_ban'])){
                                        $this->vars['error'] = 'Wrong ban time.';
                                    } else{
                                        if($check = $this->Mgm->check_char($this->session->userdata(['user' => 'server']))){
                                            if($check['CtlCode'] != 1){
                                                $this->Mgm->ban_char($this->session->userdata(['user' => 'server']));
                                                $this->Mgm->add_to_banlist();
                                                $this->Mgm->add_gm_log('Blocked character: ' . $this->Mgm->vars['name'], $this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
                                                $this->vars['success'] = 'Character banned.';
                                            } else{
                                                $this->vars['error'] = 'Character aleady banned.';
                                            }
                                        } else{
                                            $this->vars['error'] = 'Character not found.';
                                        }
                                    }
                                }
                                break;
                        }
                    }
                } else{
                    $this->vars['not_allowed'] = 'Your access level is too low to use this action';
                }
                $this->vars['ban_list'] = $this->Mgm->load_ban_list();
                $this->load->view('gmcp' . DS . 'view.ban', $this->vars);
                $this->load->view('gmcp' . DS . 'view.footer');
            } else{
                $this->login();
            }
        }

        public function unban($type = '', $name = ''){
            if($this->session->userdata(['user' => 'is_gm'])){
                $this->load->view('gmcp' . DS . 'view.header');
                $this->load->view('gmcp' . DS . 'view.sidebar');
                if($this->session->userdata(['user' => 'can_ban_acc']) == 1){
                    if($type == '')
                        $this->vars['errors'] = 'Invalid ban type.'; 
                    else{
                        if($name == '')
                            $this->vars['errors'] = 'Invalid name.'; 
                        else{
                            switch($type){
                                case 'account':
                                    $this->Mgm->unban_account($name, $this->session->userdata(['user' => 'server']));
                                    $this->Mgm->remove_ban_list_account($name);
                                    $this->Mgm->add_gm_log('Unblocked account: ' . $name, $this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
                                    $this->vars['success'] = 'Account unbanned.';
                                    break;
                                case 'character':
                                    $this->Mgm->unban_character($name, $this->session->userdata(['user' => 'server']));
                                    $this->Mgm->remove_ban_list_character($name);
                                    $this->Mgm->add_gm_log('Unblocked character: ' . $name, $this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
                                    $this->vars['success'] = 'Character unbanned.';
                                    break;
                            }
                        }
                    }
                } else{
                    $this->vars['not_allowed'] = 'Your access level is too low to use this action';
                }
                $this->load->view('gmcp' . DS . 'view.info', $this->vars);
                $this->load->view('gmcp' . DS . 'view.footer');
            } else{
                $this->login();
            }
        }

        public function credits_adder(){
            if($this->session->userdata(['user' => 'is_gm'])){
                $this->load->view('gmcp' . DS . 'view.header');
                $this->load->view('gmcp' . DS . 'view.sidebar');
                $this->vars['credits_limit'] = $this->Mgm->get_gm_credits_limit($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), $this->session->userdata(['user' => 'credits_limit']));
                if($this->session->userdata(['user' => 'credits_limit']) > 0){
                    if(isset($_POST['add_credits'])){
                        foreach($_POST as $key => $value){
                            $this->Mgm->$key = trim($value);
                        }
                        if(!isset($this->Mgm->name)){
                            $this->vars['error'] = 'Please enter character name.';
                        } else{
                            if($this->vars['account_info'] = $this->Mgm->check_char($this->session->userdata(['user' => 'server']))){
                                if(!isset($_POST['c_type']) || $_POST['c_type'] == ''){
                                    $this->vars['error'] = 'Please select credits type.';
                                } else{
                                    if(!isset($_POST['amount']) || !ctype_digit($_POST['amount'])){
                                        $this->vars['error'] = 'Please enter credits amount.';
                                    } else{
                                        if($_POST['amount'] > $this->vars['credits_limit']){
                                            $this->vars['error'] = 'Amount entered is bigger than your credits limit.';
                                        } else{
                                            $this->Mgm->update_credits_limit($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), ($this->vars['credits_limit'] - $_POST['amount']));
                                            $this->website->add_credits($this->vars['account_info']['AccountId'], $this->session->userdata(['user' => 'server']), $_POST['amount'], $_POST['c_type'], false, $this->vars['account_info']['memb_guid']);
                                            $this->Mgm->add_gm_log('Added ' . $_POST['amount'] . ' ' . $this->website->translate_credits($_POST['c_type'], $this->session->userdata(['user' => 'server'])) . ' to account: ' . $this->vars['account_info']['AccountId'], $this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']));
                                            $this->vars['success'] = 'Credits Added';
                                        }
                                    }
                                }
                            } else{
                                $this->vars['error'] = 'Character not found';
                            }
                        }
                    }
                } else{
                    $this->vars['not_allowed'] = 'Your access level is too low to use this action';
                }
                $this->load->view('gmcp' . DS . 'view.credits_adder', $this->vars);
                $this->load->view('gmcp' . DS . 'view.footer');
            } else{
                $this->login();
            }
        }
		
		public function support_requests($page = 1){
            if($this->session->userdata(['user' => 'is_gm'])){
                $this->load->view('gmcp' . DS . 'view.header');
                $this->load->view('gmcp' . DS . 'view.sidebar');
				$this->load->model('admin');
				$this->load->lib("pagination");
                if(isset($_POST['filter_tickets'])){
                    $department = isset($_POST['department']) ? $_POST['department'] : '';
                    $priorities = isset($_POST['priority']) ? $_POST['priority'] : '';
                    $status = isset($_POST['status']) ? $_POST['status'] : '';
                    $order1 = isset($_POST['order']) ? $_POST['order'] : '';
                    $order2 = isset($_POST['order2']) ? $_POST['order2'] : '';
                    $order_merge = [$order1, $order2];
                    if($department != ''){
                        $this->Madmin->serialize_departments($department);
                    } else{
                        $this->Madmin->unset_departments();
                    }
                    if($priorities != ''){
                        $this->Madmin->serialize_priorities($priorities);
                    } else{
                        $this->Madmin->unset_priorities();
                    }
                    if($status != ''){
                        $this->Madmin->serialize_status($status);
                    } else{
                        $this->Madmin->unset_status();
                    }
                    if($order1 != '' && $order2 != ''){
                        $this->Madmin->serialize_order($order_merge);
                    } else{
                        $this->Madmin->unset_order();
                    }
                }
                if(isset($_POST['set_status'])){
                    if(isset($_POST['id'])){
                        if(count($_POST['id']) > 0){
                            if($_POST['set_status'] == 3){
                                foreach($_POST['id'] as $key => $val){
                                    $this->Madmin->set_replied_by_admin_and_user($key);
                                }
                            }
                            $this->Madmin->change_ticket_status($_POST['id'], $_POST['set_status']);
                        }
                    }
                }
                $this->vars['department_list'] = $this->Madmin->load_department_list();
                $this->vars['filter'] = $this->Madmin->load_support_filter();
                $this->vars['tickets'] = $this->Madmin->load_support_requests($page, 25, $this->vars['filter']['filter_department'], $this->vars['filter']['filter_priority'], $this->vars['filter']['filter_status'], $this->vars['filter']['sort_by']);
                $this->vars['ticket_count'] = $this->Madmin->count_total_tickets($this->vars['filter']['filter_department'], $this->vars['filter']['filter_priority'], $this->vars['filter']['filter_status']);
                $this->pagination->initialize($page, 25, $this->vars['ticket_count'], $this->config->base_url . 'gmcp/support-requests/%s');
                $this->vars['pagination'] = $this->pagination->create_links();
                $this->vars['status'] = [0 => 'Open', 1 => 'Closed', 2 => 'Hold', 3 => 'Resolved', 4 => 'Spam', 5 => 'Working'];
                $this->load->view('gmcp' . DS . 'support' . DS . 'view.support_requests', $this->vars);
                $this->load->view('gmcp' . DS . 'view.footer');
            } else{
                $this->login();
            }
        }

        public function view_request($id){
           if($this->session->userdata(['user' => 'is_gm'])){
               $this->load->view('gmcp' . DS . 'view.header');
                $this->load->view('gmcp' . DS . 'view.sidebar');
				$this->load->model('admin');
                if($this->vars['ticket_data'] = $this->Madmin->check_ticket($id)){
                    if(isset($_POST['submit_reply'])){
                        $text = isset($_POST['reply']) ? $_POST['reply'] : '';
                        if($text == ''){
                            $this->vars['reply_error'] = 'Please enter reply text.';
                        } else{
                            if($this->Madmin->add_reply($id, $text, $this->session->userdata(['user' => 'username']))){
                                if($this->Madmin->set_replied_by_admin($id)){
                                    if($this->vars['ticket_data']['status'] == 3){
                                        $this->Madmin->change_ticket_status([$id => 'on'], 0);
                                    }
                                    $this->Madmin->log_reply_time($id);
									if($this->config->values('email_config', 'support_email_user') == 1){
										$user = $this->Madmin->get_account_data_by_username($this->vars['ticket_data']['creator_account'], $this->vars['ticket_data']['server']);
										$this->Madmin->sent_ticket_reply_email_user($this->vars['ticket_data']['creator_account'], $this->vars['ticket_data']['server'], $user['mail_addr'], $this->vars['ticket_data']['subject'], $id);
									}
                                    $this->vars['reply_success'] = 'You have successfully replied to ticket.';
                                }
                            }
                        }
                    }
                    $this->vars['ticket_replies'] = $this->Madmin->load_ticket_replies($id);
                    $this->vars['last_reply'] = $this->Madmin->get_last_reply_time($this->vars['ticket_data']['id']);
                    if($this->vars['last_reply'] != false){
                        $this->vars['time_elapsed'] = $this->website->date_diff($this->vars['ticket_data']['create_time'], $this->vars['last_reply']['reply_time']);
                    } else{
                        $this->vars['time_elapsed'] = 'None';
                    }
                    $this->vars['department_list'] = $this->Madmin->load_department_list();
                    $this->vars['status'] = [0 => 'Open', 1 => 'Closed', 2 => 'Hold', 3 => 'Resolved', 4 => 'Spam', 5 => 'Working'];
                } else{
                    $this->vars['not_found'] = 'Ticket not found.';
                }
                $this->load->view('gmcp' . DS . 'support' . DS . 'view.read_request', $this->vars);
                $this->load->view('gmcp' . DS . 'view.footer');
            } else{
                $this->login();
            }
        }

        public function change_department(){
            if($this->session->userdata(['user' => 'is_gm'])){
				$this->load->model('admin');
                if($this->Madmin->check_ticket($_POST['id'])){
                    $this->Madmin->change_department($_POST['id'], $_POST['department']);
                    json(['success' => 'Department changed.']);
                } else{
                    json(['error' => 'Ticket not found.']);
                }
            } else{
                json(['error' => 'Please login first.']);
            }
        }

        public function change_status(){
            if($this->session->userdata(['user' => 'is_gm'])){
				$this->load->model('admin');
                if($this->Madmin->check_ticket($_POST['id'])){
                    $this->Madmin->change_status($_POST['id'], $_POST['status']);
                    if($_POST['status'] == 3){
                        $this->Madmin->set_replied_by_admin_and_user($_POST['id']);
                    }
                    json(['success' => 'Status changed.']);
                } else{
                    json(['error' => 'Ticket not found.']);
                }
            } else{
                json(['error' => 'Please login first.']);
            }
        }
    }