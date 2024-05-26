<?php
    in_file();

    class Msetup extends model
    {
        public $vars = [];

        public function __contruct(){
            parent::__construct();
        }

        public function __set($key, $val){
            $this->vars[$key] = $val;
        }

        public function __isset($name){
            return isset($this->vars[$name]);
        }

        public function get_cms_version(){
            return json_decode(file_get_contents(INSTALL_DIR . 'data' . DS . 'version_control.json'), true)['current_version']['version'];
        }

        public function get_current_version(){		
			if(is_readable($path = BASEDIR . 'application' . DS . 'config' . DS . 'cms_config.json')){
				return json_decode(file_get_contents($path), true)['version'];
			}
        }

        public function get_all_cms_versions(){
            return json_decode(file_get_contents(INSTALL_DIR . 'data' . DS . 'version_control.json'), true)['upgrade_versions'];
        }

        public function first_cms_version(){
            return key($this->get_all_cms_versions());
        }

        public function is_admin(){
            if(isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == true){
                return true;
            }
            return false;
        }

        public function get_extension_data(){
            $this->vars['extensions'] = get_loaded_extensions();
            $this->vars['extensions_info'] = json_decode(file_get_contents(INSTALL_DIR . 'data' . DS . 'required_extensions.json'), true);
            if(is_array($this->vars['extensions_info']) && count($this->vars['extensions_info']) > 0){
                foreach($this->vars['extensions_info'] AS $data){
                    if(!in_array($data['testfor'], $this->vars['extensions'])){
                        if(isset($data['turnoff']) && strtolower(PHP_SHLIB_SUFFIX) === 'dll'){
                            $this->vars['extensionsOK'] = true;
                            $data['ok'] = true;
                        }
                    } else{
                        if(isset($data['turnoff']) && strtolower(PHP_SHLIB_SUFFIX) === 'dll'){
                            $this->vars['extensionsOK'] = false;
                            $data['ok'] = false;
                            $data['remove'] = true;
                        } else{
                            $this->vars['extensionsOK'] = true;
                            $data['ok'] = true;
                        }
                    }
                    $this->vars['extension_data'][] = $data;
                }
            }
        }

        public function check_writable_files_folders(){
            $file_info = json_decode(file_get_contents(INSTALL_DIR . 'data' . DS . 'required_files.json'), true);
            if(is_array($file_info) && count($file_info)){
                foreach($file_info as $files){
                    $files['file'] = str_replace('\\', DS, $files['file']);
                    if($files['file'] != ''){
                        $path = BASEDIR . $files['file'];
                    } else{
                        $path = BASEDIR;
                        $files['file'] = BASEDIR;
                    }
                    $files['dir_not_found'] = false;
                    $files['file_not_found'] = false;
                    $files['dir_not_writable'] = false;
                    $files['file_not_writable'] = false;
                    if($files['dir'] == 0){
                        if(!file_exists($path)){
                            $this->create_file($files['file']);
                        }
                    }
                    if(!file_exists($path)){
                        if($files['dir'] == 1){
                            if(!@mkdir($path, 0777, true)){
                                $files['dir_not_found'] = true;
                                $this->vars['filesOK'] = false;
                            }
                        } else{
                            $files['file_not_found'] = true;
                            $this->vars['filesOK'] = false;
                        }
                    }
                    if(!is_writeable($path)){
                        if(!@chmod($path, 0777)){
                            if(is_dir($path)){
                                $files['dir_not_writable'] = true;
                                $this->vars['filesOK'] = false;
                            } else{
                                $files['file_not_writable'] = true;
                                $this->vars['filesOK'] = false;
                            }
                        }
                    }
                    $this->vars['files_data'][] = $files;
                }
            }
        }

        private function create_file($file){
            if($file == 'application' . DS . 'data' . DS . 'dmn_news.json'){
                $data = [1 => ['title' => 'Free MuCMS ' . $this->get_cms_version() . '', 'news_content' => 'Free MuCMS ' . $this->get_cms_version() . ' has been successfully installed.', 'news_content_full' => 'Free MuCMS ' . $this->get_cms_version() . ' has been successfully installed.', 'time' => time(), 'icon' => 'http://', 'author' => 'System', 'lang' => 'en']];
                if(@file_put_contents(BASEDIR . $file, json_encode($data)) != false){
                    return true;
                } else{
                    return false;
                }
            } 
            else{
                if(@file_put_contents(BASEDIR . $file, '') != false){
                    return true;
                } else{
                    return false;
                }
            }
        }

        public function generate_salt($length = 10){
            return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
        }

        public function valid_md5_hash($md5 = ''){
            return strlen($md5) == 32 && ctype_xdigit($md5);
        }

        public function valid_email($email = ''){
            return filter_var($email, FILTER_VALIDATE_EMAIL);
        }

        public function list_databases(){
            return $this->web_db->query('SELECT name FROM master.dbo.sysdatabases WHERE dbid > 4')->fetch_all();
        }

        public function db_exists($db){
            return $this->master_db->query('SELECT name FROM master.dbo.sysdatabases WHERE name = \'' . $this->master_db->sanitize_var($db) . '\'')->fetch();
        }

        public function create_database($db, $user){
            if($user != 'sa'){
                $this->grant_permissions($user);
            }
            $this->create_db($db);
            $this->alter_db_first($db);
            $this->alter_db_second($db);
            return true;
        }

        private function grant_permissions($user){
            return $this->master_db->query('GRANT CREATE ANY DATABASE to ' . $this->master_db->sanitize_var($user) . '');
        }

        private function create_db($db){
            $this->master_db->query('CREATE DATABASE [' . $this->master_db->sanitize_var($db) . ']');
        }

        private function alter_db_first($db){
            $this->master_db->query('ALTER DATABASE ' . $this->master_db->sanitize_var($db) . ' MODIFY FILE ( NAME = N\'' . $this->master_db->sanitize_var($db) . '\' , SIZE = 6048KB , MAXSIZE = UNLIMITED, FILEGROWTH = 1024KB )');
        }

        private function alter_db_second($db){
            $this->master_db->query('ALTER DATABASE ' . $this->master_db->sanitize_var($db) . ' MODIFY FILE ( NAME = N\'' . $this->master_db->sanitize_var($db) . '_log\' , SIZE = 4024KB , MAXSIZE = 2048GB , FILEGROWTH = 10%)');
        }

        public function check_memb_info(){
            return $this->account_db->query('SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = N\'MEMB_INFO\'')->fetch();
        }

        public function check_character(){
            return $this->game_db->query('SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = N\'Character\'')->fetch();
        }

        public function check_if_table_exists($table, $db){
            return $this->get_db($db)->query('SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = N\'' . $table . '\'')->fetch();
        }

        public function run_query($query, $db){
            return $this->get_db($db)->query($query);
        }

        public function add_column($column, $table, $info, $db){
            $query = 'ALTER TABLE ' . $table . ' ADD ' . $column . ' ' . $info['type'];
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
            return $this->get_db($db)->query($query);
        }

        public function drop_column($col, $table, $db){
            $this->check_constraints_column($col, $table, $db);
            $this->check_default_constraints($col, $table, $db);
            return $this->get_db($db)->query('ALTER TABLE ' . $table . ' DROP COLUMN ' . $col . '');
        }

        private function check_constraints_column($col, $table, $db){
            $constraints = $this->get_db($db)->query('SELECT cu.CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE cu WHERE EXISTS (SELECT tc.* FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS tc WHERE tc.TABLE_NAME = \'' . $table . '\' AND cu.COLUMN_NAME = \'' . $col . '\' AND tc.CONSTRAINT_NAME = cu.CONSTRAINT_NAME)')->fetch_all();
            if(!empty($constraints)){
                foreach($constraints AS $const){
                    $this->drop_constraint($const['CONSTRAINT_NAME'], $table, $db);
                }
            }
        }

        private function check_default_constraints($col, $table, $db){
            $constraints = $this->get_db($db)->query('SELECT NAME FROM SYS.DEFAULT_CONSTRAINTS WHERE OBJECT_NAME(PARENT_OBJECT_ID) = \'' . $table . '\' AND COL_NAME (PARENT_OBJECT_ID, PARENT_COLUMN_ID) = \'' . $col . '\'')->fetch_all();
            if(!empty($constraints)){
                foreach($constraints AS $const){
                    $this->drop_constraint($const['NAME'], $table, $db);
                }
            }
        }

        public function drop_table($table, $db){
            $this->check_constraints($table, $db);
            return $this->get_db($db)->query('DROP TABLE ' . $table . '');
        }

        private function drop_constraint($name, $table, $db){
            $this->get_db($db)->query('ALTER TABLE ' . $table . ' DROP CONSTRAINT ' . $name . '');
        }

        private function check_constraints($table, $db){
            $constraints = $this->get_db($db)->query('SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_NAME = \'' . $table . '\'')->fetch_all();
            if(!empty($constraints)){
                foreach($constraints AS $key => $name){
                    $this->drop_constraint($name['CONSTRAINT_NAME'], $table, $db);
                }
            }
        }

        public function check_if_column_exists($column, $table, $db){
            return $this->get_db($db)->query('SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = \'' . $table . '\'  AND COLUMN_NAME = \'' . $column . '\'')->fetch();
        }

        public function check_column_count($column, $table, $db){
            return $this->get_db($db)->snumrows('SELECT COUNT(COLUMN_NAME) AS count FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = \'' . $table . '\'  AND COLUMN_NAME = \'' . $column . '\'');
        }

        public function get_table_info($table){
            return $this->web_db->query('SELECT COLUMN_NAME, COLUMN_DEFAULT, IS_NULLABLE, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = \'' . $table . '\'')->fetch_all();
        }

        public function get_identity_column($table, $db){
            return $this->get_db($db)->query('SELECT name FROM syscolumns WHERE id = Object_ID(\'' . $table . '\') AND colstat & 1 = 1')->fetch();
        }

        public function get_primary_key_column($table){
            return $this->web_db->query('SELECT cu.COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE cu WHERE EXISTS (SELECT tc.* FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS tc WHERE tc.TABLE_NAME = \'' . $table . '\' AND tc.CONSTRAINT_TYPE = \'PRIMARY KEY\' AND tc.CONSTRAINT_NAME = cu.CONSTRAINT_NAME)')->fetch();
        }
		
		public function dropTriggerPKCount($db){
			$this->get_db($db)->query('IF EXISTS (SELECT * FROM sys.triggers WHERE object_id = OBJECT_ID(N\'[dbo].[DmN_Update_Killer_Ranking]\'))
				DROP TRIGGER [dbo].[DmN_Update_Killer_Ranking]');
		}
		
		public function createTriggerPKCount($db){
			$this->get_db($db)->query('CREATE TRIGGER [dbo].[DmN_Update_Killer_Ranking] ON [dbo].[Character]
						   AFTER UPDATE
						AS 
						BEGIN
						DECLARE @last_pk_count int
						DECLARE @Name varchar(50)
						DECLARE @PKCount int
						DECLARE @new_pk int
						SET NOCOUNT ON;
							IF (UPDATE(PKCount))
							BEGIN
								SELECT @Name = Name, @PKCount = PKCount FROM inserted
								SELECT @last_pk_count = dmn_last_server_pk_count FROM Character WHERE Name = @Name
								
								IF(@last_pk_count < @PKCount)
								 BEGIN
									SET @new_pk = @PKCount - @last_pk_count
									UPDATE Character SET dmn_last_server_pk_count = @PKCount, dmn_pk_count = dmn_pk_count + @new_pk WHERE Name = @Name
								  END	
							END
						END');
		}
		
        private function get_db($db){
            switch($db){
                default:
                case 'web':
                    $db_sql = $this->web_db;
                    break;
                case 'account':
                    $db_sql = $this->account_db;
                    break;
                case 'game':
                    $db_sql = $this->game_db;
                    break;
            }
            return $db_sql;
        }

        public function insert_sql_data($sql, $db){
            $query = $this->get_db($db)->query($sql);
            $query->close_cursor();
            return $query;
        }

        public function delete_data($table){
            $query = $this->web_db->query('DELETE FROM ' . $table . '');
            $query->close_cursor();
            return $query;
        }

        public function check_procedure($proc, $db){
            return $this->get_db($db)->query('SELECT * FROM sysobjects WHERE type = \'P\' AND name = \'' . $proc . '\'')->fetch();
        }

        public function drop_procedure($proc, $db){
            return $this->get_db($db)->query('DROP PROCEDURE ' . $proc . '');
        }

        public function get_md5(){
            $md5 = $this->account_db->query('SELECT data_type, character_maximum_length AS length FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = \'MEMB_INFO\' AND column_name = \'memb__pwd\'')->fetch();
            if($md5['data_type'] != 'varchar')
                return 1; else{
                if($md5['length'] == '32')
                    return 2;
            }
            return 0;
        }

        public function get_wh_size(){
            return $this->game_db->query('SELECT character_maximum_length AS length FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = \'Warehouse\' AND column_name = \'Items\'')->fetch();
        }

        public function get_inv_size(){
            return $this->game_db->query('SELECT character_maximum_length AS length FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = \'Character\' AND column_name = \'Inventory\'')->fetch();
        }

        public function mu_versions(){
            return [0 => 'Below Season 1', 1 => 'Season 1', 2 => 'Season 2 and higher', 3 => 'Ex700 and higher', 4 => 'Season 8 and higher', 5 => 'Season 10 and higher', 6 => 'Season 11 and higher', 7 => 'Season 12 and higher', 8 => 'Season 13 and higher', 9 => 'Season 14 and higher', 10 => 'Season 15', 11 => 'Season 16 and higher', 12 => 'Season 17 and higher', 13 => 'Season 18 and higher', 14 => 'Season 19 and higher'];
        }
    }