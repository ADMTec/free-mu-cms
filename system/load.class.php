<?php
    in_file();

    class load extends \stdClass
    {
        private $registry;
        private $elements;
        private $full_path;
        private $lib_name;
        private $original_lib_name;
        private $lib_path;

        public function model($name){
            $this->registry = controller::get_instance();
            if(preg_match('/[\/]/', $name)){
                $this->elements = explode("/", $name);
                $model_name = array_pop($this->elements);
                $model_class = 'M' . $model_name;
                $this->full_path = BASEDIR . implode(DS, $this->elements) . DS . 'model.' . $model_name . '.php';
            } 
            else{
                $model_class = 'M' . $name;
                $this->full_path = APP_PATH . DS . 'models' . DS . 'model.' . $name . '.php';
            }
            if(isset($this->registry->$model_class))
                return;
            if(is_readable($this->full_path)){
                if(!class_exists('model'))
                    load_class('model');
                require_once($this->full_path);
                if(class_exists($model_class)){
                    $this->registry->$model_class = new $model_class;
                } else{
                    throw new Exception('Class ' . $model_class . ' not found.');
                }
            } else{
                throw new Exception('Model file ' . $name . ' not found.');
            }
        }

        public function view($name, $vars = null){
            $this->full_path = APP_PATH . DS . 'views' . DS . $name . '.php';
            if(preg_match('/setup[\/|\\\]application/', $name)){
                $this->full_path = BASEDIR . $name . '.php';
            }
            if(preg_match('/plugins/', $name)){
                $this->full_path = APP_PATH . DS . 'plugins' . str_replace('plugins', '', $name) . '.php';
            }
            if(!is_readable($this->full_path)){
                throw new Exception('view file ' . $this->full_path . ' not found.');
            } else{
                $this->registry = controller::get_instance();
                foreach(get_object_vars($this->registry) as $key => $val){
                    if(!isset($this->$key)){
                        $this->$key = &$this->registry->$key;
                    }
                }
                if(isset($this->registry->vars['css_classes']) || isset($this->registry->vars['css']) || isset($this->registry->vars['scripts'])){
                    extract($this->registry->vars);
                }
                if(isset($vars)){
                    extract($vars);
                }
                ob_start();
                require_once($this->full_path);
                ob_end_flush();
            }
        }
        
        public function lib($name, $params = [], $registry_name = null){
            $this->registry = controller::get_instance();
            if(preg_match('/[\/]/', $name)){
                $this->elements = explode("/", $name);
                $lib_name = array_pop($this->elements);
                $this->full_path = APP_PATH . DS . 'libraries' . DS . implode(DS, $this->elements) . DS . $lib_name . '.php';
            } 
            else{
                $lib_name = $name;
                $this->full_path = APP_PATH . DS . 'libraries' . DS . 'lib.' . $name . '.php';
            }

            if(is_readable($this->full_path)){
                if(!class_exists('library')){
                    load_class('library');
                }
                
                require_once($this->full_path);
                
                if(class_exists($lib_name)){
                    if(!empty($params)){
                        if($registry_name != null){
                            $this->registry->{$registry_name} = (new ReflectionClass($lib_name))->newInstanceArgs($params);
                        }
                        else{
                            $this->registry->{$lib_name} = (new ReflectionClass($lib_name))->newInstanceArgs($params);
                        }
                    } 
                    else{
                        if($registry_name != null){
                            $this->registry->{$registry_name} = new $lib_name;
                        }
                        else{
                            $this->registry->{$lib_name} = new $lib_name;
                        }
                    }
                } 
                else{
                    throw new Exception('Class ' . $lib_name . ' not found.');
                }
            } 
            else{
                throw new Exception('Library file ' . $this->full_path . ' not found.');
            }
        }

        public function helper($name, $params = []){
            if(is_readable($helperpath = APP_PATH . DS . 'helpers' . DS . 'helper.' . $name . '.php')){
                require_once($helperpath);
                if(class_exists($name)){
                    $this->registry = controller::get_instance();
                    if(!empty($params)){
                        $this->registry->$name = (new ReflectionClass($name))->newInstanceArgs($params);
                    } 
                    else{
                        $this->registry->$name = new $name;
                    }
                    return true;
                }
            }
            throw new Exception('Helper file helper.' . $name . '.php not found.');
        }
    }