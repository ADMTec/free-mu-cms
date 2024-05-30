<?php
    require(SYSTEM_PATH . DS . 'Scheduler' . DS . 'Base' . DS . 'Tasker.php');
    require(SYSTEM_PATH . DS . 'Scheduler' . DS . 'Session' . DS . 'File.php');

    class Scheduler extends Tasker
    {
        /**
         * Path to where jobs are located.
         * @since 1.0.0
         * @var array
         */
        protected $jobsPath;

        /**
         * Default constructor.
         * Setup settings and inits server session.
         * @since 1.0.0
         *
         * @param string $settings Settings.
         */
        public function __construct($settings){
            parent::__construct();
            $this->jobsPath = $settings['jobs']['path'];
            switch($settings['session']['driver']){
                case 'file':
                    $this->session = SFile::load($settings['session']['path'] . '/scheduler.json');
                    break;
            }
        }
    }