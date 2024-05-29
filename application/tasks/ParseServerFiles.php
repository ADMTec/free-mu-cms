<?php

    class ParseServerFiles extends Job
    {
        private $registry, $config, $load;

        public function __construct(){
            $this->registry = controller::get_instance();
            $this->config = $this->registry->config;
            $this->load = $this->registry->load;
        }

        public function execute(){
            $this->load->helper('website');
            $this->load->lib('parse_server_file');
            $this->registry->parse_server_file->parse_all();
            return true;
        }
    }