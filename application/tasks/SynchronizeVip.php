<?php

    class SynchronizeVip extends Job
    {
        private $registry, $config, $load, $vars = [];

        public function __construct(){
            $this->registry = controller::get_instance();
            $this->config = $this->registry->config;
            $this->load = $this->registry->load;
        }

        public function execute(){
            
            if($this->config->values('vip_config', 'active') == 1){
                
                $this->load->helper('website');
                $this->load->model('account');
                $this->load->model('shop');
                $this->vars['query_config'] = $this->config->values('vip_query_config');
                foreach($this->registry->website->server_list() AS $key => $server){
                    $list = $this->registry->website->db('web')->query('SELECT id, package_title, server, server_vip_package FROM DmN_Vip_Packages WHERE server = \''.$key.'\' AND is_registration_package = 0')->fetch_all();
                    
                    if(!empty($list)){
                        foreach($list AS $vipdata){
                            if($vipdata['server_vip_package'] != null && str_contains($vipdata['server_vip_package'], '|')){
                                $vip = explode('|', $vipdata['server_vip_package']);
                                $select = $this->vars['query_config']['quearies'][$vip[0]]['check'];
                                $vip_code = $this->vars['query_config']['quearies'][$vip[0]]['vip_codes'][$vip[1]]['code'];
                                $selectAll = explode(' WHERE', $select);
                                if($vip[0] == 'xteam'){
                                    $selectAll[0] .= ' WHERE AccountLevel = '.$vip_code.' AND AccountExpireDate >= \''.date('Y-m-d H:i:s', time()).'\'';
                                }
                                if($vip[0] == 'igcn'){
                                    $selectAll[0] .= ' WHERE Type = '.$vip_code.' AND Date >= \''.date('Y-m-d H:i:s', time()).'\'';
                                }
                                $listVips = $this->registry->website->db('account', $key)->query($selectAll[0])->fetch_all();
                                if(!empty($listVips)){
                                    foreach($listVips AS $userdata){
                                        $checkWebVip = $this->registry->Mshop->check_existing_vip_package($userdata['memb___id'], $key);
                                        if($checkWebVip != false){
                                            $this->registry->Mshop->update_vip_package($vipdata['id'], strtotime($userdata['Date']), $userdata['memb___id'], $key);
                                        }
                                        else{
                                            $this->registry->Mshop->insert_vip_package($vipdata['id'], strtotime($userdata['Date']), $userdata['memb___id'], $key);
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