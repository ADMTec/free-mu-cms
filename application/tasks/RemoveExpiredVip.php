<?php

    class RemoveExpiredVip extends Job
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
                $list = $this->registry->website->db('web')->query('SELECT memb___id, viptype, server FROM DmN_Vip_Users WHERE viptime <= ' . time() . '')->fetch_all();
                if(!empty($list)){
                    foreach($list AS $vipdata){
                        $this->registry->Maccount->remove_vip($vipdata['viptype'], $vipdata['memb___id'], $vipdata['server']);
                        $this->vars['vip_package_info'] = $this->registry->Maccount->load_vip_package_info($vipdata['viptype'], $vipdata['server']);
                        if($this->vars['vip_package_info'] != false){
                            if($this->vars['vip_package_info']['server_vip_package'] != null && str_contains($this->vars['vip_package_info']['server_vip_package'], '|')){
                                $vip = explode('|', $this->vars['vip_package_info']['server_vip_package']);
                                if($vip[0] == 'xteam'){
                                    $stmt = $this->registry->website->db('account', $vipdata['server'])->prepare('UPDATE MEMB_INFO SET AccountLevel = 0 WHERE memb___id = :account');
                                    $stmt->execute([':account' => $vipdata['memb___id']]);
                                }
                            }
                            $this->registry->Maccount->check_connect_member_file($this->vars['vip_package_info']['connect_member_load'], $vipdata['memb___id']);
                        }
                    }
                }
            }
        }
    }