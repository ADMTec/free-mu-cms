<?php
    in_file();

    class File
    {
        /**
         * The root cache directory.
         * @var string
         */
        private $cache_dir = '/tmp/cache';
        private $extension = '';
        private $file_name;
        private $lifetime;
        private $cache_time = [];
        private $lines;

        /**
         * Creates a FileCache object
         *
         * @param array $options
         */
        public function __construct($cache_dir = null, $extension = '.dmn'){
            if($cache_dir != null){
                $this->cache_dir = $cache_dir;
            }
            $this->extension = $extension;
        }

        /**
         * Fetches an entry from the cache.
         *
         * @param string $id
         */
        public function get($id, $delete_old_cache = true){
            $this->getFileName($id);
            if(!is_readable($this->file_name)){
                $this->cache_time[$id] = '';
                return false;
            }
            $this->lines = file($this->file_name);
            $this->lifetime = array_shift($this->lines);
            $this->lifetime = (int)trim($this->lifetime);
            $this->cache_time[$id] = $this->lifetime;
            if($this->lifetime !== 0 && $this->lifetime < time() && $delete_old_cache == true){
                $this->cache_time[$id] = '';
                @unlink($this->file_name);
                return false;
            }
            $serialized = join('', $this->lines);
            $data = json_decode($serialized, true);
            return $data;
        }

        public function last_cached($id){
            return $this->cache_time[$id];
        }

        /**
         * Deletes a cache entry.
         *
         * @param string $id
         *
         * @return bool
         */
        public function remove($id){
            $this->getFileName($id);
            return unlink($this->file_name);
        }

        /**
         * Puts data into the cache.
         *
         * @param string $id
         * @param mixed $data
         * @param int $lifetime
         *
         * @return bool
         */
        public function set($id, $data, $lifetime = 3600){
            $dir = $this->getCacheDirectory();
            if(!is_dir($dir)){
                if(!mkdir($dir, 0755, true)){
                    return false;
                }
            }
            $this->getFileName($id);
            $this->lifetime = time() + $lifetime;
            $json = json_encode($data);
            $result = file_put_contents($this->file_name, $this->lifetime . PHP_EOL . $json);
            if($result === false){
                return false;
            }
            return true;
        }

        /**
         * Fetches a base directory to store the cache data
         *
         * @return string
         */
        protected function getCacheDirectory(){
            return $this->cache_dir;
        }

        /**
         * Fetches a file path of the cache data
         *
         * @param string $id
         *
         * @return string
         */
        protected function getFileName($id){
            $directory = $this->getCacheDirectory();
            $this->file_name = $directory . DS . $id . $this->extension;
        }
    }
