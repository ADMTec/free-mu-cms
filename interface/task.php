<?php
    require_once(realpath(dirname(__FILE__) . '/..') . DIRECTORY_SEPARATOR . 'constants.php');
    require_once(BASEDIR . 'vendor/autoload.php');
    require_once(SYSTEM_PATH . DS . 'common.php');
    if(!isset($_SERVER['argv'])){
        echo "Not at command line\n";
        writelog("Not at command line", "scheduler");
        exit;
    }
	
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

        public function __construct(){
            $this->config = load_class('config');
            $this->scheduler_config = $this->config->values('scheduler_config');
            if($this->scheduler_config['type'] == 1){
                require_once(SYSTEM_PATH . DS . 'Scheduler' . DS . 'Scheduler.php');
                $this->scheduler = new Scheduler(['jobs' => ['path' => APP_PATH . DS . 'tasks'], 'session' => ['driver' => 'file', 'path' => APP_PATH . DS . 'logs'],]);
            } else{
                echo "Cron tasks not enabled.";
                writelog("Cron tasks not enabled.", "scheduler");
                exit;
            }
        }
		
		
        public function run($key = ''){
            if($this->scheduler_config['key'] != $key){
                echo "Incorrect key\n";
                writelog("Incorrect key", "scheduler");
                exit;
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

    $task = new scheduler_task;
    $task->run($_SERVER['argv'][1]);