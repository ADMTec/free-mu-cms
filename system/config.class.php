<?php
    in_file();

    class config
    {
        public $xmlfile = [], $xml, $base_url, $scriptname, $script_url, $time_offset, $val = [];
        private $xml_conf_values = ['main' => ['servername' => 'Free MuCMS', 'site_url' => 'http://', 'forum_url' => 'http://', 'con_check' => '1', 'template' => 'season6', 'timezone' => 'UTC', 'use_ajax_page_load' => '0', 'cache_type' => 'file', 'mem_cached_ip' => '', 'mem_cached_port' => '', 'grand_open_timer' => '', 'grand_open_timer_text' => 'Until server opening', 'maintenance' => '0', 'show_debug_info' => '0'], 'news' => ['module_status' => '1', 'storage' => 'dmn', 'news_per_page' => '5', 'ipb_host' => '', 'ipb_user' => '', 'ipb_pass' => '', 'ipb_db' => '', 'ipb_tb_prefix' => '', 'ipb_forum_ids' => '', 'rss_feed_url' => '', 'rss_feed_count' => '5', 'fb_script' => '', 'cache_time' => '360'], 'buygm' => ['module_status' => '0', 'gm_ctlcode' => '32', 'price' => '5000', 'price_t' => '1'], 'buypoints' => ['module_status' => '0', 'price' => '1000', 'price_type' => '1', 'allow_external_chars' => '0', 'points' => '10'], 'character' => ['max_stats' => '32767', 'skill_tree_type' => 'scf', 'skilltree_reset_price' => '500', 'skilltree_reset_price_type' => '1', 'skilltree_reset_level' => '0', 'skilltree_reset_points' => '0', 'skilltree_points_multiplier' => '1', 'pk_clear_price' => '9999', 'allow_reset_stats' => '1', 'reset_stats_price' => '0', 'reset_stats_payment_type' => '1', 'show_equipment' => '1',], 'credits' => ['db_1' => 'web', 'table_1' => 'DmN_Shop_Credits', 'account_column_1' => 'memb___id', 'credits_column_1' => 'credits', 'title_1' => 'Credits', 'db_2' => 'web', 'table_2' => 'DmN_Shop_Credits', 'account_column_2' => 'memb___id', 'credits_column_2' => 'credits2', 'title_2' => 'Gold Credits', 'db_3' => 'web', 'table_3' => 'DmN_Shop_Credits', 'account_column_3' => 'memb___id', 'credits_column_3' => 'credits3', 'title_3' => 'Web Zen'], 'market' => ['module_status' => '1', 'items_per_page' => '20', 'price_limit_credits' => '9999', 'price_limit_gcredits' => '9999', 'price_limit_zen' => '9999999', 'price_limit_jewels' => '240', 'price_highlight' => '1000', 'sell_tax' => '15', 'sell_item_limit' => '5', 'allow_sell_shop_items' => '1', 'allow_remove_only_when_expired' => '0', 'max_exe' => '6',], 'media' => ['module_status' => '1', 'images_per_page' => '30',], 'modules' => ['recent_forum_module' => '0', 'recent_forum_rss_url' => '', 'recent_forum_rss_count' => '5', 'recent_forum_rss_cache_time' => '3600', 'last_market_items_module' => '0', 'last_market_items_count' => '5'], 'shop' => ['module_status' => '1', 'item_per_page' => '16', 'columns' => '4', 'exe_price' => '1', 'luck_price' => '1', 'skill_price' => '1', 'lvl_price' => '1', 'opt_price' => '1', 'anc1_price' => '1', 'anc2_price' => '1', 'dfenrir_price' => '1', 'pfenrir_price' => '1', 'gfenrir_price' => '1', 'ref_price' => '1', 'exe_limit' => '6', 'use_harmony' => '1', 'use_socket' => '1', 'allow_select_socket' => '1', 'equal_seed' => '0', 'equal_socket' => '0', 'empty_socket' => '1', 'socket_limit_credits' => '5', 'socket_limit_gcredits' => '5', 'check_socket_part_type' => '1', 'max_sockets_to_show' => '5', 'allow_exe_anc' => '1', 'allow_exe_socket' => '1', 'allow_anc_harmony' => '0', 'element_type_price' => '20', 'element_rank_1_price' => '20', 'element_rank_2_price' => '20', 'element_rank_3_price' => '20', 'element_rank_4_price' => '20', 'element_rank_5_price' => '20', 'pentagram_slot_anger_price' => '200', 'pentagram_slot_blessing_price' => '200', 'pentagram_slot_integrity_price' => '200', 'pentagram_slot_divinity_price' => '200', 'pentagram_slot_gale_price' => '200', 'discount' => '0', 'discount_time' => '0', 'discount_perc' => '0', 'discount_notice' => '', 'gold_credits_price' => '-20', 'card_item_expires' => 1800], 'warehouse' => ['module_status' => '1', 'allow_sell_for_credits' => '1', 'allow_sell_for_gcredits' => '1', 'allow_sell_for_zen' => '1', 'allow_sell_for_chaos' => '1', 'allow_sell_for_bless' => '1', 'allow_sell_for_soul' => '1', 'allow_sell_for_life' => '1', 'allow_sell_for_creation' => '1', 'allow_sell_for_harmony' => '1', 'allow_delete_item' => '0', 'allow_item_upgrade' => '0', 'allow_move_to_web_warehouse' => '1', 'web_wh_item_expires_after' => '1 month', 'web_items_per_page' => '25'], 'changename' => ['module_status' => '0', 'price' => '100', 'price_type' => '1', 'forbidden' => 'webzen,admin,test,[GM]', 'check_guild' => '1', 'user_master_level' => '0', 'max_length' => '10', 'allowed_pattern' => 'a-zA-Z0-9{}!$%&amp;\/()=?^@\[\]#',],];

        public function __construct(){
            $_SERVER['SERVER_NAME'] = $this->server_name();
            $_SERVER['SERVER_PORT'] = $this->server_port();
            $_SERVER['HTTP_HOST'] = $this->http_host();
            if(!isCommandLineInterface()){
                if((isset($_SERVER['HTTPS']) && (($_SERVER['HTTPS'] == 'on') || ($_SERVER['HTTPS'] == '1'))) || $_SERVER['SERVER_PORT'] == 443){
                    $url = 'https://';
                } else if(!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' || !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on'){
                    $url = 'https://';
								} else if(!empty($_SERVER['HTTP_CF_VISITOR']) && json_decode($_SERVER['HTTP_CF_VISITOR'], true)['scheme'] == 'https'){
                    $url = 'https://';																																																																		 
                } else{
                    $url = 'http://';
                }
                $url .= $_SERVER['SERVER_NAME'] . htmlspecialchars($_SERVER['SCRIPT_NAME']);
                $parts = parse_url($url);
                if(substr($parts['path'], -1, 1) == '/')
                    $parts['dirpath'] = $parts['path']; else
                    $parts['dirpath'] = substr($parts['path'], 0, strrpos($parts['path'], '/') + 1);
                if((int)$_SERVER['SERVER_PORT'] <> 80 && (int)$_SERVER['SERVER_PORT'] <> 443)
                    $this->base_url = $parts['scheme'] . '://' . $parts['host'] . ':' . $_SERVER['SERVER_PORT'] . $parts['dirpath']; else
                    $this->base_url = $parts['scheme'] . '://' . $parts['host'] . $parts['dirpath'];
                unset($url);
                unset($parts);
                $this->scriptname = basename(ltrim(htmlspecialchars($_SERVER['SCRIPT_NAME']), '/'));
                $this->script_url = $this->base_url . $this->scriptname;
            }
            $this->time_offset = ((substr(date('O', time()), 0, 1) != '+') ? '-' : '') . (int)substr(date('O', time()), 1, 2) . '.' . substr(date('O', time()), 3, 2);
        }

        private function server_name(){
            if(isCommandLineInterface()){
                return isset($_SERVER['argv'][2]) ? htmlspecialchars($_SERVER['argv'][2]) : '';
            } else{
                return isset($_SERVER['SERVER_NAME']) ? htmlspecialchars($_SERVER['SERVER_NAME']) : '';
            }
        }

        private function server_port(){
            if(isCommandLineInterface()){
                return isset($_SERVER['argv'][3]) ? htmlspecialchars($_SERVER['argv'][3]) : '';
            } else{
                return isset($_SERVER['SERVER_PORT']) ? htmlspecialchars($_SERVER['SERVER_PORT']) : '';
            }
        }

        private function http_host(){
            if(isCommandLineInterface()){
                return isset($_SERVER['argv'][4]) ? htmlspecialchars($_SERVER['argv'][4]) : '';
            } else{
                return isset($_SERVER['HTTP_HOST']) ? htmlspecialchars($_SERVER['HTTP_HOST']) : '';
            }
        }

        public function language(){
            if(isset($_COOKIE['dmn_language']))
                return htmlspecialchars($_COOKIE['dmn_language']); 
			else{
                $lang = $this->values('locale_config', 'default_localization');
                if($lang != false){
                    return $lang;
                }
            }
            return 'en';
        }

        public function config_entry($conf){
            $this->xmlfile = explode('|', $conf);
            self::xml_load(APP_PATH . DS . 'config' . DS . 'xml' . DS . $this->xmlfile[0] . '_conf.xml');
            $element = $this->xml->getElementsByTagName($this->xmlfile[0]);
            foreach($element as $value){
                $task = $value->getElementsByTagName($this->xmlfile[1]);
                if($task->length == 0){
                    if(strpos($this->xmlfile[0], '_') !== false){
                        $conf_name = explode('_', $this->xmlfile[0]);
                        $conf_name = $conf_name[0];
                    } else{
                        $conf_name = $this->xmlfile[0];
                    }
					
                    if(array_key_exists($this->xmlfile[1], $this->xml_conf_values[$conf_name])){
                        $new_element = $this->xml->createElement($this->xmlfile[1], $this->xml_conf_values[$conf_name][$this->xmlfile[1]]);
                        $value->appendChild($new_element);
                        $this->xml->save(APP_PATH . DS . 'config' . DS . 'xml' . DS . $this->xmlfile[0] . '_conf.xml');
                    } 
					else{
                        throw new Exception('Configuration element \'' . $this->xmlfile[1] . '\' not found in file ' . $this->xmlfile[0] . '_conf.xml');
                    }
                }
                if(count($this->xmlfile) == 3){
                    foreach($task as $val){
                        $t = $val->getElementsByTagName($this->xmlfile[2]);
                        if($t->length == 0)
                            throw new Exception('Configuration element \'' . $this->xmlfile[2] . '\' not found in element <' . $this->xmlfile[1] . '></' . $this->xmlfile[1] . '> file ' . $this->xmlfile[0] . '_conf.xml');
                        return json_decode(json_encode($t->item(0)->nodeValue));
                    }
                } else{
					/*if($this->xmlfile[1] == 'template'){
						if(isset($_COOKIE['dmn_template'])){
							return htmlspecialchars($_COOKIE['dmn_template']);
						}
						else{
							$templ = json_decode(json_encode($task->item(0)->nodeValue));
							setcookie("dmn_template", $templ, strtotime('+5 days', time()), "/");
							return $templ;
						}
					}*/
                    return json_decode(json_encode($task->item(0)->nodeValue));
                }
            }
            return false;
        }

        public function load_all_xml_config($file){
            $conf = json_decode(json_encode((array)simplexml_load_file(APP_PATH . DS . 'config' . DS . 'xml' . DS . htmlspecialchars($file) . '_conf.xml')), 1);
            return $conf[$file];
        }

        public function config_file($file){
            $xml_file = APP_PATH . DS . 'config' . DS . 'xml' . DS . htmlspecialchars($file) . '_conf.xml';
            if(file_exists($xml_file)){
                $conf_file = (array)simplexml_load_file($xml_file);
                $this->val[$file] = $conf_file[$file];
                return;
            } else{
                self::xml_load($xml_file);
                $conf_file = (array)simplexml_load_file($xml_file);
                $this->val[$file] = $conf_file[$file];
                return;
            }
        }

        public function write_xml($file, $conf = []){
            $doc = new DOMDocument('1.0');
            $doc->formatOutput = true;
            $doc->preserveWhiteSpace = false;
            $first_element = $doc->createElement($file . '_conf');
            $second_element = $doc->createElement($file);
            foreach($conf as $key => $value){
                if($key != 'edit_config'){
                    if(is_array($value)){
                        $third_element = $doc->createElement($key);
                        $second_element->appendChild($third_element);
                        foreach($value as $k => $v){
                            $forth_element = $doc->createElement($k);
                            $third_element->appendChild($forth_element);
                            $val = $doc->createTextNode($v);
                            $forth_element->appendChild($val);
                        }
                    } else{
                        if($key == 'fb_script'){
                            $third_element = $doc->createElement($key);
                            $second_element->appendChild($third_element);
                            $val = $doc->createCDATASection($value);
                            $third_element->appendChild($val);
                        } else{
                            $third_element = $doc->createElement($key);
                            $second_element->appendChild($third_element);
                            $val = $doc->createTextNode($value);
                            $third_element->appendChild($val);
                        }
                    }
                }
            }
            $first_element->appendChild($second_element);
            $doc->appendChild($first_element);
            if(is_writable(APP_PATH . DS . 'config' . DS . 'xml')){
                if($doc->save(APP_PATH . DS . 'config' . DS . 'xml' . DS . $file . '_conf.xml')){
                    return true;
                } else{
                    throw new Exception('Failed to save external entity file:' . APP_PATH . DS . 'config' . DS . 'xml' . DS . $file . '_conf.xml');
                }
            } else{
                throw new Exception('Folder ' . APP_PATH . DS . 'config' . DS . 'xml is not writable please chmod to 0777');
            }
        }

        private function xml_load($xml_file){
            $this->xml = new DOMDocument('1.0', 'utf-8');
            $this->xml->formatOutput = true;
            $this->xml->preserveWhiteSpace = false;
            if(file_exists($xml_file)){
                $this->xml->load($xml_file);
            } else{
                $arr = explode(DS, $xml_file);
                $module_name = strstr(array_pop($arr), '.', true);
                $module_name = substr($module_name, 0, strrpos($module_name, "_"));
                $conf_first = $this->xml->createElement($module_name . '_conf');
                $conf_second = $this->xml->createElement($module_name);
                $this->apped_defaults($conf_second, $module_name, $this->xml);
                $conf_first->appendChild($conf_second);
                $this->xml->appendChild($conf_first);
                if(is_writable(APP_PATH . DS . 'config' . DS . 'xml')){
                    if($this->xml->save($xml_file)){
                        $this->xml->load($xml_file);
                    } else{
                        throw new Exception('Failed to save external entity file:' . $xml_file);
                    }
                } else{
                    throw new Exception('Folder ' . APP_PATH . DS . 'config' . DS . 'xml is not writable please chmod to 0777');
                }
            }
        }

        private function apped_defaults($conf, $file, $xml){
            if(preg_match('/\bmain\b/i', $file)){
                foreach($this->xml_conf_values['main'] AS $key => $value){
                    $conf->appendChild($xml->createElement($key, $value));
                }
            }
            if(preg_match('/\bnews\b/i', $file)){
                foreach($this->xml_conf_values['news'] AS $key => $value){
                    $conf->appendChild($xml->createElement($key, $value));
                }
            }
            if(preg_match('/\bbuygm\b/i', $file)){
                foreach($this->xml_conf_values['buygm'] AS $key => $value){
                    $conf->appendChild($xml->createElement($key, $value));
                }
            }
            if(preg_match('/\bbuypoints\b/i', $file)){
                foreach($this->xml_conf_values['buypoints'] AS $key => $value){
                    $conf->appendChild($xml->createElement($key, $value));
                }
            }
            if(preg_match('/\bchangeclass\b/i', $file)){
                foreach($this->xml_conf_values['changeclass'] AS $key => $value){
                    $conf->appendChild($xml->createElement($key, $value));
                }
            }
            if(preg_match('/\bcharacter\w*\b/', $file)){
                foreach($this->xml_conf_values['character'] AS $key => $value){
                    $conf->appendChild($xml->createElement($key, $value));
                }
            }
            if(preg_match('/\bcredits\w*\b/', $file)){
                foreach($this->xml_conf_values['credits'] AS $key => $value){
                    $conf->appendChild($xml->createElement($key, $value));
                }
            }
            if(preg_match('/\bmarket\b/', $file)){
                foreach($this->xml_conf_values['market'] AS $key => $value){
                    $conf->appendChild($xml->createElement($key, $value));
                }
            }
            if(preg_match('/\bmedia\b/', $file)){
                foreach($this->xml_conf_values['media'] AS $key => $value){
                    $conf->appendChild($xml->createElement($key, $value));
                }
            }
            if(preg_match('/\bmodules\b/', $file)){
                foreach($this->xml_conf_values['modules'] AS $key => $value){
                    $conf->appendChild($xml->createElement($key, $value));
                }
            }
            if(preg_match('/\bshop\w*\b/', $file)){
                foreach($this->xml_conf_values['shop'] AS $key => $value){
                    $conf->appendChild($xml->createElement($key, $value));
                }
            }
            if(preg_match('/\bwarehouse\b/', $file)){
                foreach($this->xml_conf_values['warehouse'] AS $key => $value){
                    $conf->appendChild($xml->createElement($key, $value));
                }
            }
            if(preg_match('/\bchangename\w*\b/', $file)){
                foreach($this->xml_conf_values['changename'] AS $key => $value){
                    $conf->appendChild($xml->createElement($key, $value));
                }
            }
        }
        
        public function handle_json_error($errno, $file){
            match(json_last_error()){
                JSON_ERROR_DEPTH => throw new Exception('JSON - Maximum stack depth exceeded. FILE - '.$file),
                JSON_ERROR_STATE_MISMATCH => throw new Exception('JSON - Underflow or the modes mismatch. FILE - '.$file),
                JSON_ERROR_CTRL_CHAR => throw new Exception('JSON - Unexpected control character found. FILE - '.$file),
                JSON_ERROR_SYNTAX => throw new Exception('JSON - Syntax error, malformed JSON. FILE - '.$file),
                JSON_ERROR_UTF8 => throw new Exception('JSON - Malformed UTF-8 characters, possibly incorrectly encoded. FILE - '.$file),
                default => throw new Exception('JSON - Unknown error. FILE - '.$file)
            };
        }
        
        public function from_json($data, $file = null, $is_array = true){
            $json_data = json_decode($data, $is_array);
            if($json_data == NULL && json_last_error() !== JSON_ERROR_NONE){
                $this->handle_json_error(json_last_error(), $file);
            }
            return $json_data;
        }
        
        public function to_json($data, $file = null, $pretty_print = JSON_PRETTY_PRINT){
            header('Content-Type: application/json');
            $json_data = json_encode($data, $pretty_print);
            if($json_data == NULL && json_last_error() !== JSON_ERROR_NONE){
                $this->handle_json_error(json_last_error(), $file);
            }
            return $json_data;
        }

        public function values($file_name = null, $key = false, $ext = '.json', $reset = false){
            try{
                static $configData = [];
                
                if($reset && isset($configData[$file_name])){
                    unset($configData[$file_name]);
                    return;
                }
                
                if($file_name == null){
                    throw new \Exception('Config file name cannot be empty');
                }
                 
                $file = APP_PATH . DS . 'config' . DS . $file_name . $ext;
                 
                if(!file_exists($file)){
                    if(is_writable(APP_PATH . DS . 'config')){
                        file_put_contents($file, '{}');
                    } 
                    else{
                        throw new Exception('Folder application' . DS . 'config is not writable');
                    }
                }
                
                if(!isset($configData[$file_name]))
                    $configData[$file_name] = $this->from_json(file_get_contents($file), $file_name);
                
                if($key != false){
                    if(is_array($key)){
                        if(count($key) == 3){
                            return $configData[$file_name][$key[0]][$key[1]][$key[2]] ?? false;
                        } 
                        else{
                            return $configData[$file_name][$key[0]][$key[1]] ?? false;
                        }
                    } 
                    else{
                        return $configData[$file_name][$key] ?? false;
                    }
                }
                
                return $configData[$file_name] ?? false;
            } catch(\Exception $e){
                throw new \Exception($e->getMessage());
            }
        }

        public function save_config_data($array = [], $file = '', $sort = false, $ext = '.json'){
            try{
                if($file == ''){
                   throw new Exception('Config file name can not be empty.'); 
                }
                if(!is_array($array)){
                   throw new Exception('Config data for saving should be formated as array.'); 
                }
                
                if($sort){
                    ksort($array);
                }
                
                $data = $this->to_json($array, $file);
                
                if(!is_writable(APP_PATH . DS . 'config')){
                    throw new Exception('Folder application' . DS . 'config is not writable');
                }
                $fp = fopen(APP_PATH . DS . 'config' . DS . $file . $ext, 'w');
                fwrite($fp, $data);
                fclose($fp);
                $this->values($file, false, '.json', true);
                header('Content-Type: text/html');
                return true;
            } catch(\Exception $e){
                throw new \Exception($e->getMessage());
            }
        }

        public function plugins(){
            static $plugin_config = [];
            if(!empty($plugin_config)){
                return $plugin_config;
            } 
            else{
                $plugin_config = $this->values('plugin_config');
                if(!empty($plugin_config)){
                    return $plugin_config;
                }
            }
            return false;
        }
        
        public function is_plugin_installed($plugin){
            $plugins = $this->plugins();
            if($plugins != false && array_key_exists($plugin, $plugins) && $plugins[$plugin]['installed'] == 1){
                return true;
            }
            return false;
        }
    }
