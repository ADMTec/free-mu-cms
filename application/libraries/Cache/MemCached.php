<?php
    in_file();

    class MemCached
    {
        /**
         * The root cache directory.
         * @var string
         */
        private $memcached = null;
        private $lifetime;
        private $cache_time = [];
        private $isMemcache = false;
        private $IP;
        private $PORT

        /**
         * Creates a FileCache object
         *
         * @param array $options
         */
           
        public function __construct($ip, $port){
            $connected = false;
            $this->IP = $ip;
            $this->PORT = $port;

            if(class_exists('Memcache')){
                $this->memcached = new Memcache;  
                if($this->memcached->connect($this->IP, (int)$this->PORT)){
                    $connected = true;
                    $this->isMemcache = true;
                }
            }
            if($connected == false && class_exists('Memcached')){
                $this->memcached = new Memcached;  
                if($this->memcached->addServer($this->IP, (int)$this->PORT)){
                    $connected = true;
                    $this->isMemcache = false;
                }
                else{
                    throw new Exception('Unable to connect to memcached');
                }
            }

			if($connected == false){
				throw new Exception('No memcache[d] class found.');
			}
        }

        /**
         * Fetches an entry from the cache.
         *
         * @param string $id
         * @param bool $delete_old_cache
         */
           
        public function get($id, $delete_old_cache = true){
            $data = $this->memcached->get($id);
            if($data == false){
                $this->cache_time[$id] = '';
                return false;
            }
            $this->cache_time[$id] = $data[0];
            if($this->cache_time[$id] !== 0 && $this->cache_time[$id] < time() && $delete_old_cache == true){
                $this->cache_time[$id] = '';
                $this->delete($id);
                return false;
            }
            
            return $data[1];
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
            return $this->memcached->delete($id);
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
            $this->lifetime = time() + $lifetime;
            $storeData = [$this->lifetime, $data];
            if($this->isMemcache){
                $result = $this->memcached->set($id, $storeData, false, $this->lifetime);
            }
            else{
                $result = $this->memcached->set($id, $storeData, $this->lifetime);
            }
            return $result;
        }
    }
