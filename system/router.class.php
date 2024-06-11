<?php
    in_file();

    class router
    {
        private $path;
        private $controller_file;
        private $plugin_file;
        private $ctrl;
        private $controller;
        private $plugin;
        private $method;
        private $args;
		
		public function route($request){
            $this->ctrl = $request->get_controller();
            $this->method = $request->get_method();
            $this->args = $request->get_args();
			if($this->ctrl == 'assets')
				return;
			
			if(defined('ACPURL')){
				if($this->ctrl == 'admincp' && ACPURL != 'admincp'){
					throw new Exception('Controller ' . $this->ctrl . ' not found.');
				}
				if($this->ctrl == ACPURL)
					$this->ctrl = 'admincp';
			}
			else{
				define('ACPURL', 'admincp');
			}
			
            $this->path = (in_array($this->ctrl, ['setup', 'upgrade'])) ? BASEDIR . 'setup' . DS . 'application' . DS : APP_PATH . DS;
            $this->controller_file = $this->path . 'controllers' . DS . 'controller.' . $this->ctrl . '.php';
            if($this->ctrl == 'market' AND $this->method != 'index'){
                if(in_array($this->method, ['all', 'swords', 'axes', 'maces', 'spears', 'bows', 'staffs', 'shields', 'helms', 'armors', 'pants', 'gloves', 'boots', 'wings', 'items', 'other', 'scrolls'])){
                    array_unshift($this->args, $this->method);
                    $this->method = 'load_market_items';
                }
            }
			
            if(is_readable($this->controller_file)){
                require_once $this->controller_file;
                if(!class_exists($this->ctrl)){
                    throw new Exception('Class ' . $this->ctrl . ' not found.');
                } else{
                    $this->controller = new $this->ctrl;
                    if(!in_array($this->ctrl, ['setup', 'upgrade'])){
                        if($this->controller->config->config_entry('main|maintenance') == 1){
                            if(!in_array($this->ctrl, ['admincp', 'maintenance'])){
                                header('Location: ' . $this->controller->config->base_url . 'maintenance/index/503');
                            }
                        }
                    }
					if($this->ctrl == 'partner' && !in_array($this->method, ['link', 'panel'])){
						$this->args[0] = $this->method;
						$this->method = 'link';
					}
                    if(!is_callable([$this->controller, $this->method])){
						if($this->ctrl == 'rankings'){
							$this->plugin_file = APP_PATH . DS . 'plugins' . DS . $this->method . DS . 'plugin.php';
							if(is_readable($this->plugin_file)){
								require_once $this->plugin_file;
								$class_name = '_plugin_' . $this->method;
								if(!class_exists($class_name)){
									throw new Exception('Plugin class ' . $class_name . ' not found.');
								} else{
									$this->plugin = new $class_name;
									if(isset($this->args[0]) && !empty($this->args[0])){
										$method = $this->args[0];
										unset($this->args[0]);
									}
									else{
										$method = 'index';
									}
									if(!is_callable([$this->plugin, $method])){
										throw new Exception('Plugin method ' . $method . ' not found.');
									}
									if(!empty($this->args)){
										call_user_func_array([$this->plugin, $method], $this->args);
									} else{
										call_user_func([$this->plugin, $method]);
									}
									return;
									
								}
							}
							else{
								header('Location: '.$this->controller->config->base_url.'');
								return;
							}
						}
						else{
							//throw new Exception('Controller method ' . $this->method . ' not found.');
							header('Location: '.$this->controller->config->base_url.'');
							return;
						}
                    }
                    if(!empty($this->args)){
                        call_user_func_array([$this->controller, $this->method], $this->args);
                    } else{
                        call_user_func([$this->controller, $this->method]);
                    }
                    return;
                }
            } else{
                $this->plugin_file = APP_PATH . DS . 'plugins' . DS . $this->ctrl . DS . 'plugin.php';
                if(is_readable($this->plugin_file)){
                    require_once $this->plugin_file;
                    $class_name = '_plugin_' . $this->ctrl;
                    if(!class_exists($class_name)){
                        throw new Exception('Plugin class ' . $class_name . ' not found.');
                    } else{
                        $this->plugin = new $class_name;
                        if($this->plugin->config->config_entry('main|maintenance') == 1){
                            if(!in_array($this->ctrl, ['admincp', 'maintenance'])){
                                header('Location: ' . $this->plugin->config->base_url . 'maintenance/index/503');
                            }
                        }
                        if(!is_callable([$this->plugin, $this->method])){
                            throw new Exception('Plugin method ' . $this->method . ' not found.');
                        }
                        if(!empty($this->args)){
                            call_user_func_array([$this->plugin, $this->method], $this->args);
                        } else{
                            call_user_func([$this->plugin, $this->method]);
                        }
                        return;
                    }
                }
            }
            throw new Exception('Controller ' . $this->ctrl . ' not found.');
        }
		
		private function check_plugin($plugin, $method, $args){
            $this->plugin_file = APP_PATH . DS . 'plugins' . DS . $plugin . DS . 'plugin.php';
            if(file_exists($this->plugin_file)){
                require_once $this->plugin_file;
                $class_name = '_plugin_' . $plugin;
                if(!class_exists($class_name)){
                    throw new Exception('Plugin class ' . $class_name . ' not found.');
                } else{
                    $this->plugin = new $class_name;
                    if(!is_callable([$this->plugin, $method])){
                        throw new Exception('Plugin method ' . $method . ' not found.');
                    }
                    if(!empty($args)){
                        call_user_func_array([$this->plugin, $method], $args);
                    } else{
                        call_user_func([$this->plugin, $method]);
                    }
                    return;
                }
            }
            throw new Exception('Plugin method ' . $method . ' not found.');
        }
    }
