<?php
    in_file();

    class pdo_sqlsrv extends library
    {
        private $db_conn = null;
        private $host = '';
        private $user = '';
        private $pass = '';
        private $file;
        private $query = '';
        private $db = '';
        private $queries = [];
        private $querycount = 0;
        private $fields = [];
        private $values = [];
        private $error = [];

        public function __construct($host, $user, $pass, $db){
            $this->host = $host;
            $this->user = $user;
            $this->pass = $pass;
            $this->db = $db;
            if($this->host == '' || $this->user == '' || $this->db == ''){
                throw new Exception('Free MuCMS: Missing One Of Connection Parameters');
            } 
			else{
                $this->make_connection();
            }
        }
		
		public function getDB(){
			return $this->db;
		}
		
		private function make_connection(){
			if(!extension_loaded('pdo_sqlsrv') && !extension_loaded('pdo_sqlserv')){
				throw new Exception('Please enable PDO SQL_SERV extensions in your php.ini');
			} 
			else{
				$this->db_conn = new PDO("sqlsrv:Server=" . $this->host . ";Database=" . $this->db . ";TrustServerCertificate=true;", "" . $this->user . "", "" . $this->pass . "");
				//$this->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_SYSTEM);
				$this->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_UTF8);
			}
                
            $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            if(!$this->db_conn){
                die($this->db_conn->errorCode());
            }
        }

        public function get_connection(){
            return $this->db_conn;
        }

        public function setAttribute($attr, $attr2){
            $this->db_conn->setAttribute($attr, $attr2);
        }
		
		public function beginTransaction(){
			$this->db_conn->beginTransaction();
		}
		
		public function commit(){
			$this->db_conn->commit();
		}
		
		public function rollback(){
			$this->db_conn->rollback();
		}
		
		public function query($query){
            try{
                $this->query = $this->db_conn->query($query);
                if(defined('LOG_SQL')){
                    if(LOG_SQL == true){
                        $this->log($this->query->queryString, 'database_log_' . date('Y-m-d', time()) . '.txt');
                    }
                }
                return $this;
            } catch(Exception $e){
                $this->log($query, 'database_error_log_' . date('Y-m-d', time()) . '.txt');
                $this->log($e, 'database_error_log_' . date('Y-m-d', time()) . '.txt');
                throw new Exception('Sql sintax error. Please check application/logs/database_error_log_' . date('Y-m-d', time()) . '.txt for errors.');
            }
        }
		
		public function cached_query($name, $query, $data = [], $cache_time = 60){
            if($this->config->config_entry('main|cache_type') == 'file'){
                $this->load->lib('Cache/File as cache', [APP_PATH . DS . 'data' . DS . 'cache']);
            } 
			else{
                $this->load->lib('Cache/MemCached as cache',[$this->config->config_entry('main|mem_cached_ip'), $this->config->config_entry('main|mem_cached_port')]);
            }
            $cached_data = $this->cache->get($name);
            if(!$cached_data){
                $stmt = $this->prepare($query);
                $stmt->execute($data);
                $result = $stmt->fetch_all();
                if(count($result) > 0){
                    $this->cache->set($name, $result, $cache_time);
                    return $result;
                } 
				else{
                    return false;
                }
            }
            return $cached_data;
        }

        public function prepare($query){
            $this->query = $this->db_conn->prepare($query);
            return $this;
        }

        public function execute($params = []){
            if(defined('LOG_SQL')){
                if(LOG_SQL == true){
                    $this->log($this->debug_pdo_query($this->query->queryString, $params), 'database_log_' .date('Y-m-d', time()) . '.txt');
                }
            }
            try{
                return (is_array($params) && count($params) > 0) ? $this->query->execute($params) : $this->query->execute();
            } catch(PDOException $e){
                $this->log($e, 'database_error_log_' . date('Y-m-d', time()) . '.txt');
                throw new Exception('Sql sintax error. Please check application/logs/database_error_log_' . date('Y-m-d', time()) . '.txt for errors.');
            }
        }

        public function fetch(){
			$data = $this->query->fetch();
			if($data == null)
				return false;
            return $data;
        }

        public function fetch_all(){
            return $this->query->fetchAll();
        }

        public function numrows(){
            return $this->query->rowCount();
        }

        public function snumrows($query){
            $query = $this->query($query)->fetch();
            return $query['count'];
        }

        public function rows_affected(){
            return $this->query->rowCount();
        }
		
		public function escape($string, $param_type = PDO::PARAM_STR){
            if(is_int($string) || is_float($string))
                return $string;
			if(is_bool($string))
                return ($string === false) ? 0 : 1;
			if(is_null($string))
                return 'NULL';	
            if(($value = $this->db_conn->quote($string, $param_type)) !== false)
                return $value; 
			else
                return "'" . addcslashes(str_replace("'", "''", $this->sanitize_var($string)), "\000\n\r\\\032") . "'";
        }
		
		public function sanitize_var($var){
            return (!preg_match('/^\-?\d+(\.\d+)?$/D', $var) || preg_match('/^0\d+$/D', $var)) ? preg_replace('/[\000\010\011\012\015\032\047\134]/', '', $var) : $var;
        }

        public function next_row_set(){
            return $this->query->nextRowset();
        }

        public function close_cursor(){
            return $this->query->closeCursor();
        }

        public function bind_parameters($parameter, $variable, $data_type, $length = null){
            return $this->query->bindParam($parameter, $variable, $data_type, $length);
        }
		
		public function last_insert_id(){
            return $this->db_conn->lastInsertId();
        }

        public function error(){
            $this->error = $this->query->errorInfo();
            return $this->error[2];
        }
		
		private function log($query, $file = 'database_log.txt'){
            $this->file = @fopen(APP_PATH . DS . 'logs' . DS . $file, 'a');
			$mtime = microtime(true);
			$now = DateTime::createFromFormat('U.u', $mtime);
			if(is_bool($now)){
				$now = DateTime::createFromFormat('U.u', $mtime += 0.001);
			}
            @fputs($this->file, $now->format("m-d-Y H:i:s.u").' #### '.$query . "\n");
            @fputs($this->file, str_repeat('=', 80) . "\n");
            @fclose($this->file);
        }

		public function get_insert($table, $data){
            foreach($data as $curdata){
                $this->fields[] = $curdata['field'];
                switch(strtolower($curdata['type'])){
                    case 'i':
                        $this->values[] = (int)$curdata['value'];
                        break;
                    case 's':
                        $this->values[] = $this->escape($curdata['value']);
                        break;
                    case 'v':
                        $this->values[] = $this->sanitize_var($curdata['value']);
                        break;
                    case 'd':
                        $this->values[] = '\'' . date('Ymd H:i:s', $curdata['value']) . '\'';
                        break;
                    case 'ds':
                        $this->values[] = '\'' . date('Ymd', $curdata['value']) . '\'';
                        break;
                    case 'e':
                        $this->values[] = 'NULL';
                        break;
                }
            }
            return 'INSERT INTO ' . $table . ' (' . implode(', ', $this->fields) . ') VALUES (' . implode(', ', $this->values) . ')';
        }

        public function get_query_count(){
            return $this->querycount;
        }

        public function get_quearies(){
            return $this->queries;
        }
		
		public function check_if_table_exists($table){
            $data = $this->query('SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = N' . $this->escape($table) . '')->fetch();
			return ($data != false) ? true : false;
        }
		
		public function check_if_column_exists($column, $table){
            $data = $this->query('SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ' . $this->escape($table) . '  AND COLUMN_NAME = ' . $this->escape($column) . '')->fetch();
			return ($data != false) ? true : false;
		}
		
		public function add_column($column, $table, $info){
            $query = 'ALTER TABLE ' . $this->sanitize_var($table) . ' ADD ' . $this->sanitize_var($column) . ' ' . $info['type'];
            if($info['identity'] == 1){
                $query .= ' IDENTITY(1,1)';
            }
            if($info['is_primary_key'] == 1){
                $query .= ' PRIMARY KEY';
            }
            $query .= ($info['null'] == 1) ? ' NULL' : ' NOT NULL';
            if($info['default'] != ''){
                $query .= ' DEFAULT ' . $info['default'] . '';
            }
            return $this->query($query);
        }
		
		public function remove_table($table){
            return $this->query('DROP TABLE ' . $this->sanitize_var($table) . '');
        }
		
		private function debug_pdo_query($raw_sql, $params = []){
            $keys = [];
            $values = $params;
			if(is_array($params)){
				foreach($params as $key => $value){
					if(is_string($key)){
						$keys[] = '/' . $key . '/';
					} else{
						$keys[] = '/[?]/';
					}
					if(is_string($value)){
						$values[$key] = "'" . $value . "'";
					} else if(is_array($value)){
						$values[$key] = implode(',', $value);
					} else if(is_null($value)){
						$values[$key] = 'NULL';
					} else if(is_bool($value)){
						$values[$key] = ($value === false) ? 0 : 1;
					}
				}
			}
            return preg_replace($keys, $values, $raw_sql, 1, $count);
        }
    }
