<?php

    class _plugin_guild_recruit extends controller implements pluginInterface
    {
        private $pluginaizer;
        private $vars = [];

        /**
         *
         * Plugin constructor
         * Initialize plugin class
         *
         */
        public function __construct()
        {
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
        public function index()
        {
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
        private function user_module()
        {
            //check if visitor has user privilleges
            if($this->pluginaizer->session->is_user()){
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
                        $this->vars['chars'] = $this->pluginaizer->{'M' . $this->pluginaizer->get_plugin_class()}->loadCharList($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
                        if(!empty($this->vars['chars'])){
                            $this->vars['guilds'] = [];
                            foreach($this->vars['chars'] AS $char){
                                $guild = $this->pluginaizer->{'M' . $this->pluginaizer->get_plugin_class()}->getGuildData($char['Name'], $this->pluginaizer->session->userdata(['user' => 'server']));
                                if($guild[0] != false){
                                    $this->vars['guilds'][] = $guild;
                                }
                            }
                            //print_r($this->vars['guilds']);
                        }
                        else{
                          $this->vars['config_not_found'] = __('You don\'t have any guilds.');  
                        }
                    }
                } else{
                    $this->vars['config_not_found'] = __('Plugin configuration not found.');
                }
                //set js
                $this->vars['js'] = $this->config->base_url . 'assets/plugins/js/' . $this->pluginaizer->get_plugin_class() . '.js';
                //load template
                $this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . $this->config->config_entry('main|template') . DS . 'view.index', $this->vars);
            } else{
                $this->pluginaizer->redirect($this->config->base_url . 'account-panel/login?return=' . str_replace('_', '-', $this->pluginaizer->get_plugin_class()));
            }
        }
        
        public function update($gname){
            //check if visitor has user privilleges
            if($this->pluginaizer->session->is_user()){
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
                        $this->vars['chars'] = $this->pluginaizer->{'M' . $this->pluginaizer->get_plugin_class()}->loadCharList($this->pluginaizer->session->userdata(['user' => 'username']), $this->pluginaizer->session->userdata(['user' => 'server']));
                        $this->vars['gname'] = $gname;
                        if(!empty($this->vars['chars'])){
                            $this->vars['guilds'] = [];
                            foreach($this->vars['chars'] AS $char){
                                $guild = $this->pluginaizer->{'M' . $this->pluginaizer->get_plugin_class()}->getGuildData($char['Name'], $this->pluginaizer->session->userdata(['user' => 'server']));
                                if($guild[0] != false){
                                    $this->vars['guilds'][] = $guild;
                                }
                            }
                            
                            $this->vars['found'] = false;
                            $this->vars['guildOwner'] = false;
                            if(!empty($this->vars['guilds'])){
                                foreach($this->vars['guilds'] AS $guild){
                                    if($guild[0] != false && $guild[0] == $this->vars['gname']){
                                        $this->vars['found'] = $guild[1];
                                        $this->vars['guildOwner'] = true;
                                        break;
                                    }
                                }
                                
                                if(!$this->vars['guildOwner']){
                                    $this->vars['config_not_found'] = __('Unable to update this guild.');   
                                }
                                else{
                                    if(isset($_POST['discord_link'])){
                                        if(!preg_match('/(https:\/\/)?(www\.)?(((discord(app)?)?\.com\/invite)|((discord(app)?)?\.gg))\/(?<invite>.+)/', $_POST['discord_link'])){
                                            $this->vars['config_not_found'] = __('Enter valid discord link.');   
                                        }
                                        else{
                                            $this->pluginaizer->{'M' . $this->pluginaizer->get_plugin_class()}->updateDiscord($this->vars['gname'], $_POST['discord_link'], $this->pluginaizer->session->userdata(['user' => 'server']));  
                                            $this->vars['found'] = ['discord_link' => $_POST['discord_link'], 'gname' => $this->vars['gname']];
                                            $this->vars['success'] = __('Discord has been updated');
                                        }
                                    }
                                }
                            }
                            else{
                                $this->vars['config_not_found'] = __('You don\'t have any guilds.');  
                            }
                        }
                        else{
                          $this->vars['config_not_found'] = __('You don\'t have any guilds.');  
                        }
                    }
                } else{
                    $this->vars['config_not_found'] = __('Plugin configuration not found.');
                }
                //set js
                $this->vars['js'] = $this->config->base_url . 'assets/plugins/js/' . $this->pluginaizer->get_plugin_class() . '.js';
                //load template
                $this->load->view('plugins' . DS . $this->pluginaizer->get_plugin_class() . DS . 'views' . DS . $this->config->config_entry('main|template') . DS . 'view.update', $this->vars);
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
        private function public_module()
        {
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
        public function admin()
        {
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
         * Save plugin settings
         *
         *
         * Return mixed
         */
        public function save_settings()
        {
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
        public function install()
        {
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
                    'description' => 'Recruit guild members by discord' //description which will see user
                ]);
                //create plugin config template
                $this->pluginaizer->create_config(['active' => 0]);
                
                $this->pluginaizer->add_sql_scheme('guild_recruit');
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
        public function uninstall()
        {
            //check if visitor has administrator privilleges
            if($this->pluginaizer->session->is_admin()){
                //delete plugin config and remove plugin data
                 $this->pluginaizer->delete_config()->remove_sql_scheme('guild_recruit')->remove_plugin();
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

        public function enable()
        {
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

        public function disable()
        {
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

        public function about()
        {
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