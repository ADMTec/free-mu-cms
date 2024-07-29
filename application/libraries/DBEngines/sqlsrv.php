<?php
    in_file();

    class sqlsrv extends library
    {
        private $db_conn = null;
        private $host = '';
        private $user = '';
        private $pass = '';
        private $file;
        private $query = '';
		private $stmt = '';
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
			if(!extension_loaded('sqlsrv')){
                throw new Exception('Please enable sqlsrv extension in your php.ini');
            } 
			else{
                $this->db_conn = sqlsrv_connect($this->host, [
					"Database"=> $this->db, 
					"UID"=> $this->user, 
					"PWD"=> $this->pass, 
					"ReturnDatesAsStrings" => true, 
					"CharacterSet" => "UTF-8"
				]);
                if(!$this->db_conn){
                    $this->error = "Free MuCMS: Failed to connect to sql server instance. Please check your configuration details.\n";
					$errors = $this->error();
                    if(trim($errors) != ''){
                        $this->error .= 'Error: ' . $errors;
                    }
                    throw new Exception(htmlspecialchars($errors));
                }
            }
        }

        public function get_connection(){
            return $this->db_conn;
        }
		
		public function beginTransaction(){
			sqlsrv_begin_transaction($this->db_conn);
		}
		
		public function commit(){
			sqlsrv_commit($this->db_conn);
		}
		
		public function rollback(){
			sqlsrv_rollback($this->db_conn);
		}
		
		public function query($query){
            $this->stmt = sqlsrv_query($this->db_conn, $query);
            if($this->stmt != false){
                if(defined('LOG_SQL')){
                    if(LOG_SQL == true){
                        $this->log($query, 'database_log_' . date('Y-m-d', time()) . '.txt');
                    }
                }
                return $this;
            } else{
                $this->log($query, 'database_error_log_' . date('Y-m-d', time()) . '.txt');
                $this->log($this->error(), 'database_error_log_' . date('Y-m-d', time()) . '.txt');
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
            $this->query = $query;
            return $this;
        }

        public function execute($params = [], $dump = false){
			$this->stmt = sqlsrv_prepare($this->db_conn, $this->replace_named_params($this->query), $this->remove_keys_from_params($params));
			if($this->stmt != false){
				$query = $this->compile_binds($this->query, $params, $dump);
				if(sqlsrv_execute($this->stmt) != false){
					if(defined('LOG_SQL')){
						if(LOG_SQL == true){
							$this->log($query, 'database_log_' . date('Y-m-d', time()) . '.txt');
						}
					}
					return $this;
				}
				else{
					$this->log($query, 'database_error_log_' . date('Y-m-d', time()) . '.txt');
					$this->log($this->error(), 'database_error_log_' . date('Y-m-d', time()) . '.txt');
					throw new Exception('Sql sintax error. Please check application/logs/database_error_log_' . date('Y-m-d', time()) . '.txt for errors.');
				}
			}
			else{
				$this->log($query, 'database_error_log_' . date('Y-m-d', time()) . '.txt');
                $this->log($this->error(), 'database_error_log_' . date('Y-m-d', time()) . '.txt');
                throw new Exception('Sql sintax error. Please check application/logs/database_error_log_' . date('Y-m-d', time()) . '.txt for errors.');
			}
        }
		
		private function remove_keys_from_params($params){
			$data = [];
			foreach($params AS $key => $value){
				$data[] = $value;
			}
			return $data;
		}
		
		private function replace_named_params($sql){
			if(strpos($sql, ':') === false)
				return $sql;
			else{
				$patterns = [];
				$replacements = [];
				$i = 0;
				preg_match_all('/:(?P<name>[a-zA-Z_]+)/i', $sql, $match, PREG_SET_ORDER);
				foreach($match as $key => $value){
					$patterns[$i] = '/' . $value[0] . '\b/u';
					$replacements[$i] = '?';
					$i++;
				}
				$sql = preg_replace($patterns, $replacements, $sql);
				return $sql;
			}
		}
		
		private function compile_binds($sql, $binds, $dump = false){
            if(strpos($sql, ':') === false)
                return $sql;
            if(!is_array($binds))
                $binds = [$binds];
            preg_match_all('/:(?P<name>[a-zA-Z_]+)/i', $sql, $match, PREG_SET_ORDER);
            $patterns = [];
            $replacements = [];
            $i = 0;
            foreach($match as $key => $value){
                $patterns[$i] = '/' . $value[0] . '\b/u';
                $replacements[$i] = $this->escape($binds[$value[0]]);
                $i++;
            }
            $sql = preg_replace($patterns, $replacements, $sql);
            return $sql;
        }

        public function fetch(){
			$data = sqlsrv_fetch_array($this->stmt, SQLSRV_FETCH_ASSOC);
			if($data == null)
				return false;
            return $data;
        }

        public function fetch_all(){
            $list = [];
            while($row = $this->fetch()){
                $list[] = $row;
            }
            return $list;
        }

        public function numrows(){
            return sqlsrv_num_rows($this->stmt);
        }

        public function snumrows($query){
            $query = $this->query($query)->fetch();
            return $query['count'];
        }

        public function rows_affected(){
            return sqlsrv_rows_affected($this->stmt);
        }

        public function close_cursor(){
            return;
        }
		
		public function escape($string){
			if(is_int($string) || is_float($string))
                return $string;
            if(is_bool($string))
                return ($string === false) ? 0 : 1;
			if(is_null($string))
                return 'NULL';	
            return "'" . addcslashes(str_replace("'", "''", $this->sanitize_var($string)), "\000\n\r\\\032") . "'";
        }
		
		public function sanitize_var($var){
            return (!preg_match('/^\-?\d+(\.\d+)?$/D', $var) || preg_match('/^0\d+$/D', $var)) ? preg_replace('/[\000\010\011\012\015\032\047\134]/', '', $var) : $var;
        }

        public function bind_parameters($parameter = '', $variable = '', $data_type = '', $length = null){
            return;
        }
		
		public function last_insert_id(){
            $q = $this->query('SELECT @@IDENTITY AS id')->fetch();
            return $q['id'];
        }

        public function error(){
			$errors = sqlsrv_errors();
			$message = '';
            if($errors != NULL){
				foreach($errors as $error){
					$message .= 'SQLSTATE: '.$error[ 'SQLSTATE'].', code: '.$error[ 'code'].', message: '.$error[ 'message'].'<br />';
				}
			}
			return $message;
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
    }
