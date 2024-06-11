<?php
    require_once(realpath(dirname(__FILE__) . '/..') . DIRECTORY_SEPARATOR . 'constants.php');
    require_once(BASEDIR . 'vendor/autoload.php');
    require_once(SYSTEM_PATH . DS . 'common.php');
	
	use Tracy\Debugger;

    Debugger::enable((ENVIRONMENT == 'development') ? Debugger::DEVELOPMENT : Debugger::PRODUCTION, APP_PATH . DS . 'logs' . DS . 'Tracy');
	
    $security = load_class('security');

    use Gettext\Translator;
    use Gettext\Translations;
    use Gettext\Generators;

    class controller extends \stdClass
    {
        private static $_instance;
        public $translator;
		
		
        public function __construct(){
            self::$_instance = $this;
            foreach(is_loaded() as $key => $class){
                $this->$key = load_class($class);
            }
            $this->config = load_class('config');
            $this->load = load_class('load');
            $this->translator = new Translator();
            $this->translator->register();
        }

        public static function get_instance(){
            if(!self::$_instance instanceof self){
                self::$_instance = new controller;
            }
            return self::$_instance;
        }
    }

    class scheduler_task
    {
        private $config;
        private $scheduler_config;
        private $scheduler;
        private $after_upgrade_key = 'a953feaec195bba04c142bc38ec2846c';
        private $after_install_key = 'a953feaec195bba04c142bc38ec283df';

        public function __construct(){
            $this->config = load_class('config');
            $this->scheduler_config = $this->config->values('scheduler_config');
            if(($this->scheduler_config['type'] == 2 || $this->scheduler_config['type'] == 3) || (isset($_GET['key']) && $_GET['key'] == $this->after_upgrade_key || isset($_GET['key']) && $_GET['key'] == $this->after_install_key || isset($_GET['custom']))){
                require_once(SYSTEM_PATH . DS . 'Scheduler' . DS . 'Scheduler.php');
                $this->scheduler = new Scheduler(['jobs' => ['path' => APP_PATH . DS . 'tasks'], 'session' => ['driver' => 'file', 'path' => APP_PATH . DS . 'logs'],]);
            } else{
                echo "Web tasks not enabled.";
                writelog("Web tasks not enabled.", "scheduler");
                exit;
            }
        }
		
		
        public function run($key = '', $custom = ''){
            if($key == $this->after_upgrade_key){
                $this->run_after_upgrade();
            } else if($key == $this->after_install_key){
                $this->run_after_upgrade();
            } else{
                if($this->scheduler_config['key'] != $key){
                    echo "Incorrect key\n";
                    writelog("Incorrect key", "scheduler");
                    exit;
                } else{
					ignore_user_abort(true);
					set_time_limit(120);
					if($custom != ''){
                        if(!array_key_exists($custom, $this->scheduler_config['tasks'])){
                            json(['error' => 'Scheduled task not found.']);
                        } else{
                            $this->scheduler->job($custom, function($task){
                                return $task->now();
                            })->start();
                            json(['success' => 'Scheduled task executed successfully.']);
                        }
                    } else{
						foreach($this->scheduler_config['tasks'] AS $key => $time){
							if(isset($time['status'])){
								if($time['status'] == 1){
									$this->scheduler->job($key, function($task) use ($time){
										return $task->custom($time['time']);
									})->start();
								}
							} else{
								$this->scheduler->job($key, function($task) use ($time){
									return $task->custom($time);
								})->start();
							}
						}
					}
                }
            }
        }
		
		
        private function run_after_upgrade(){
			
        }
    }

    $task = new scheduler_task;
    if(isset($_GET['key'], $_GET['custom'])){
        $task->run(htmlspecialchars($_GET['key']), htmlspecialchars($_GET['custom']));
    } else{
        if(isset($_GET['key'])){
            $task->run(htmlspecialchars($_GET['key']));
        } else{
            echo "Key not found.";
            writelog("Key not found.", "scheduler");
        }
    }
