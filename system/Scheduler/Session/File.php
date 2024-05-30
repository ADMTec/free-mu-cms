<?php
    require(SYSTEM_PATH . DS . 'Scheduler' . DS . 'Contracts' . DS . 'Session.php');

    class SFile extends ArrayObject implements Session
    {
        /**
         * Session filename.
         * @since 1.0
         * @var object
         */
        protected static $filename;
		private $jobs = [];

        /**
         * Casts to json.
         * @since 1.0.0
         *
         * @return string json
         */
        public function toJson(){
            return json_encode($this->jobs);
        }

        /**
         * Cast to string.
         * @since 1.0.0
         *
         * @return string
         */
        public function __toString(){
            return $this->toJson();
        }

        /**
         * Reads .json file and converts it to array.
         * @since 1.0.0
         *
         * @param string $filename Path to file.
         *
         * @return void
         */
        public function read($filename){
            $file = fopen($filename, 'r');
            $json = fread($file, filesize($filename));
            fclose($file);
            $array = (array)json_decode($json);
            foreach($array as $key => $value){
                $this->jobs[$key] = $value;
            }
            unset($file);
            unset($json);
            unset($array);
        }

        /**
         * Reads .json file and converts it to array.
         * @since 1.0.0
         *
         * @param string $filename Path to file.
         *
         * @return void
         */
        public function write($filename){
            if(count($this->jobs) == 0)
                return;
            $file = fopen($filename, 'w');
            fwrite($file, $this->toJson());
            fclose($file);
            unset($file);
        }

        /**
         * Loads session.
         * @since 1.0.0
         *
         * @param string $options Filename as options.
         *
         * @return object Session.
         */
        public static function load($options){
            if(!isset(static::$filename))
                static::$filename = $options;
            $sess = new self;
            if(file_exists($options))
                $sess->read($options);
            return $sess;
        }

        /**
         * Saves session.
         * @since 1.0.0
         */
        public function save(){
            $this->write(static::$filename);
        }

        /**
         * Returns flag indicating if key exists in session.
         * @since 1.0.0
         *
         * @return bool
         */
        public function has($key)
		{
			//var_dump($this);
			//var_dump(get_object_vars($this));
			//var_dump(array_key_exists($key, get_object_vars($this)));die();
			//return array_key_exists($key, get_object_vars($this)); //array_key_exists($key, $this);
			return array_key_exists($key, $this->jobs);
		}

        /**
         * Returns a session value based on a given key.
         * @since 1.0.0
         *
         * @return mixed
         */
        public function get($key){
            return $this->has($key) ? $this->jobs[$key] : null;
        }

        /**
         * Sets a key and a value into session.
         * Server session.
         * @since 1.0.0
         *
         * @param string $key Key.
         * @param mixed $value Value;
         */
        public function set($key, $value){
            $this->jobs[$key] = $value;
        }
    }