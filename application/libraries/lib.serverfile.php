<?php
    in_file();

    class serverfile extends library
    {
        private $lang;
        private $info = [];
		private $cachedir = '';

        public function __construct(){
			$this->cachedir = APP_PATH . DS . 'data' . DS . 'shop';

			if($this->config->config_entry('main|cache_type') == 'file'){
                $this->load->lib('Cache/File as scache', [APP_PATH . DS . 'data' . DS . 'shop']);
            } 
			else{
                $this->load->lib('Cache/MemCached as scache',[$this->config->config_entry('main|mem_cached_ip'), $this->config->config_entry('main|mem_cached_port')]);
            }
			
            $this->set_language();
        }

        public function get($key){
            return $this->info[$key] ?? false;
        }

        public function set($key, $val){
            $this->info[$key] = $val;
        }
		
		public function item_list($cat, $size = 32){
			if($size == 40){
				$size = 32;
			}
			$file = 'item_list[' . $size . '][' . $cat . ']#' . $this->lang . '.dmn';
			if(!file_exists($this->cachedir . DS . $file)){
				$file = 'item_list[' . $size . '][' . $cat . ']#en.dmn';
			}
            $cached_data = $this->scache->get(str_replace('.dmn', '', $file), false);
            if($cached_data != false){
                $this->set('items', $cached_data);
			}
			else{
				$this->load->lib('parse_server_file');
				$this->parse_server_file->parse_all();
				$cached_data = $this->scache->get(str_replace('.dmn', '', $file), false);	
				if($cached_data != false){
					$this->set('items', $cached_data);
				}
			}
            return $this;
        }

        public function item_tooltip(){
			$file = 'item_tooltip#' . $this->lang . '.dmn';
			if(!file_exists($this->cachedir . DS . $file)){
				$file = 'item_tooltip#en.dmn';
			}
            $cached_data = $this->scache->get(str_replace('.dmn', '', $file), false);
            if($cached_data != false)
				$this->set('item_tooltip', $cached_data);
            return $this;
        }

        public function item_tooltip_text(){
			$file = 'item_tooltip_text#' . $this->lang . '.dmn';
			if(!file_exists($this->cachedir . DS . $file)){
				$file = 'item_tooltip_text#en.dmn';
			}
            $cached_data = $this->scache->get(str_replace('.dmn', '', $file), false);
            if($cached_data != false)
				$this->set('item_tooltip_text', $cached_data);
            return $this;
        }

        public function jewel_of_harmony_option(){
			$file = 'jewel_of_harmony_option#' . $this->lang . '.dmn';
			if(!file_exists($this->cachedir . DS . $file)){
				$file = 'jewel_of_harmony_option#en.dmn';
			}
            $cached_data = $this->scache->get(str_replace('.dmn', '', $file), false);
            if($cached_data != false)
				$this->set('jewel_of_harmony_option', $cached_data);
            return $this;
        }

        public function npc_names(){
			$file = 'npc_name#' . $this->lang . '.dmn';
			if(!file_exists($this->cachedir . DS . $file)){
				$file = 'npc_name#en.dmn';
			}
            $cached_data = $this->scache->get(str_replace('.dmn', '', $file), false);
            if($cached_data != false)
				$this->set('npc_names', $cached_data);
            return $this;
        }
		
		public function monster_list(){
			$file = 'mlist#' . $this->lang . '.dmn';
			if(!file_exists($this->cachedir . DS . $file)){
				$file = 'mlist#en.dmn';
			}
			$cached_data = $this->scache->get(str_replace('.dmn', '', $file), false);
			if($cached_data != false)
				$this->set('mlist', $cached_data);
			return $this;
		}
		
		
		public function muun_info(){
			$file = 'muun_info#' . $this->lang . '.dmn';
			if(!file_exists($this->cachedir . DS . $file)){
				$file = 'muun_info#en.dmn';
			}
			$cached_data = $this->scache->get(str_replace('.dmn', '', $file), false);
			if($cached_data != false)
				$this->set('muun_info', $cached_data);
			return $this;
		}
		
		public function muun_option_info(){
			$file = 'muun_option_info#' . $this->lang . '.dmn';
			if(!file_exists($this->cachedir . DS . $file)){
				$file = 'muun_option_info#en.dmn';
			}
			$cached_data = $this->scache->get(str_replace('.dmn', '', $file), false);
			if($cached_data != false)
				$this->set('muun_option_info', $cached_data);
			return $this;
		}
		
		public function socket_item_type(){
			$file = 'sockettype#' . $this->lang . '.dmn';
			if(!file_exists($this->cachedir . DS . $file)){
				$file = 'sockettype#en.dmn';
			}
			$cached_data = $this->scache->get(str_replace('.dmn', '', $file), false);
			if($cached_data != false)
				$this->set('sockettype', $cached_data);
			return $this;
		}

        public function pentagram_jewel_option_value($version = 4){
			$file = 'pentagram_jewel_option_value#' . $this->lang . '.dmn';
			if(!file_exists($this->cachedir . DS . $file)){
				$file = 'pentagram_jewel_option_value#en.dmn';
			}

            $cached_data = $this->scache->get(str_replace('.dmn', '', $file), false);
            if($cached_data != false)
				$this->set('pentagram_jewel_option_value', $cached_data);
            return $this;
        }

        public function skill(){
			$file = 'skill#' . $this->lang . '.dmn';
			if(!file_exists($this->cachedir . DS . $file)){
				$file = 'skill#en.dmn';
			}
            $cached_data = $this->scache->get(str_replace('.dmn', '', $file), false);
            if($cached_data != false)
				$this->set('skill', $cached_data);
            return $this;
        }

        public function socket_item($version = 5){
			$v = ($version > 5) ? '[6]' : '';
			$file = 'socket_item'.$v.'#' . $this->lang . '.dmn';
			if(!file_exists($this->cachedir . DS . $file)){
				$file = 'socket_item'.$v.'#en.dmn';
			}

            $cached_data = $this->scache->get(str_replace('.dmn', '', $file), false);
            if($cached_data != false)
				$this->set('socket_item', $cached_data);
            return $this;
        }

        public function exe_common(){
			$file = 'exe_common#' . $this->lang . '.dmn';
			if(!file_exists($this->cachedir . DS . $file)){
				$file = 'exe_common#en.dmn';
			}
            $cached_data = $this->scache->get(str_replace('.dmn', '', $file), false);
            if($cached_data != false)
				$this->set('exe_common', $cached_data);
            return $this;
        }

        public function exe_wing(){
			$file = 'exe_wing#' . $this->lang . '.dmn';
			if(!file_exists($this->cachedir . DS . $file)){
				$file = 'exe_wing#en.dmn';
			}
            $cached_data = $this->scache->get(str_replace('.dmn', '', $file), false);
            if($cached_data != false)
				$this->set('exe_wing', $cached_data);
            return $this;
        }

        public function item_add_option(){
			$file = 'item_add_option#' . $this->lang . '.dmn';
			if(!file_exists($this->cachedir . DS . $file)){
				$file = 'item_add_option#en.dmn';
			}
            $cached_data = $this->scache->get(str_replace('.dmn', '', $file), false);
            if($cached_data != false)
				$this->set('item_add_option', $cached_data);
        }

        public function item_level_tooltip(){
			$file = 'item_level_tooltip#' . $this->lang . '.dmn';
			if(!file_exists($this->cachedir . DS . $file)){
				$file = 'item_level_tooltip#en.dmn';
			}
            $cached_data = $this->scache->get(str_replace('.dmn', '', $file), false);
            if($cached_data != false)
				$this->set('item_level_tooltip', $cached_data);
            return $this;
        }

        public function item_set_option(){
			$file = 'item_set_option#' . $this->lang . '.dmn';
			if(!file_exists($this->cachedir . DS . $file)){
				$file = 'item_set_option#en.dmn';
			}
            $cached_data = $this->scache->get(str_replace('.dmn', '', $file), false);
            if($cached_data != false)
				$this->set('item_set_option', $cached_data);
            return $this;
        }

        public function item_set_option_text(){
			$file = 'item_set_option_text#' . $this->lang . '.dmn';
			if(!file_exists($this->cachedir . DS . $file)){
				$file = 'item_set_option_text#en.dmn';
			}
            $cached_data = $this->scache->get(str_replace('.dmn', '', $file), false);
            if($cached_data != false)
				$this->set('item_set_option_text', $cached_data);
            return $this;
        }

        public function item_set_type(){
			$file = 'item_set_type#' . $this->lang . '.dmn';
			if(!file_exists($this->cachedir . DS . $file)){
				$file = 'item_set_type#en.dmn';
			}
            $cached_data = $this->scache->get(str_replace('.dmn', '', $file), false);
            if($cached_data != false)
				$this->set('item_set_type', $cached_data);
            return $this;
        }
		
		public function item_grade_option(){
			$file = 'item_grade_option#' . $this->lang . '.dmn';
			if(!file_exists($this->cachedir . DS . $file)){
				$file = 'item_grade_option#en.dmn';
			}
            $cached_data = $this->scache->get(str_replace('.dmn', '', $file), false);
            if($cached_data != false)
				$this->set('item_grade_option', $cached_data);
            return $this;
        }
		
		
		public function earring_type(){
			$file = 'earringtype#' . $this->lang . '.dmn';
			if(!file_exists($this->cachedir . DS . $file)){
				$file = 'earringtype#en.dmn';
			}
            $cached_data = $this->scache->get(str_replace('.dmn', '', $file), false);
            if($cached_data != false)
				$this->set('earringtype', $cached_data);
            return $this;
        }
		
		public function earring_option(){
			$file = 'earringoption#' . $this->lang . '.dmn';
			if(!file_exists($this->cachedir . DS . $file)){
				$file = 'earringoption#en.dmn';
			}
            $cached_data = $this->scache->get(str_replace('.dmn', '', $file), false);
            if($cached_data != false)
				$this->set('earringoption', $cached_data);
            return $this;
        }
		
		public function staticitems(){
			$file = 'staticitems#' . $this->lang . '.dmn';
			if(!file_exists($this->cachedir . DS . $file)){
				$file = 'staticitems#en.dmn';
			}
            $cached_data = $this->scache->get(str_replace('.dmn', '', $file), false);
            if($cached_data != false)
				$this->set('staticitems', $cached_data);
            return $this;
        }
		
		public function staticoptioninfo(){
			$file = 'staticoptioninfo#' . $this->lang . '.dmn';
			if(!file_exists($this->cachedir . DS . $file)){
				$file = 'staticoptioninfo#en.dmn';
			}
            $cached_data = $this->scache->get(str_replace('.dmn', '', $file), false);
            if($cached_data != false)
				$this->set('staticoptioninfo', $cached_data);
            return $this;
        }
		
		public function earring_option_name(){
			$file = 'earringoptionname#' . $this->lang . '.dmn';
			if(!file_exists($this->cachedir . DS . $file)){
				$file = 'earringoptionname#en.dmn';
			}
            $cached_data = $this->scache->get(str_replace('.dmn', '', $file), false);
            if($cached_data != false)
				$this->set('earringoptionname', $cached_data);
            return $this;
        }

        private function set_language(){
            $this->lang = $this->config->language();
        }
    }