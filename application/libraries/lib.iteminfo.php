<?php
	in_file();

    class iteminfo extends library
    {
        public $hex = null;
        public $item_data;
        public $info = '';
        public $id = null;
        public $option = null;
        public $dur = null;
        public $serial = null;
        public $serial2 = null;
        public $exe = null;
        public $ancient = null;
        public $cat = null;
        public $ref = 0;
        public $type = null;
        public $harmony = [0, 0];
        public $socket = [];
        public $level = 0;
        public $skill = '';
        public $skill_exists = null;
        public $luck = '';
        public $name = '';
        public $item_for = '';
        public $class = '';
        private $exe_option_list = [];
        private $exe_options;
		private $winggradeopt;
        private $exe_count = 0;
        private $elementtype = '';
        private $elementopt = '';
        private $errtel_rank;
        private $pentagram_option_info;
        private $skill_list;
        private $harmony_list;
        private $socket_list = '';
        private $socket_data;
        private $item_tooltip;
        private $item_tooltip_text;
        private $item_level_tooltip;
        public $addopt = '';
        public $refopt = '';
        public $haropt = '';
        public $sockopt = '';
		public $bonussocketopt = '';
        public $stamina = '';
        public $ancopt = '';
		public $anc_prefix = '';
        private $set_options;
        private $set_options_text;
        private $no_socket;
        private $empty_socket;
        private $seedopt = '';
        private $mountable_slots = 0;
        private $index;
		private $name_from_tooltip = false;
		private $isSocketItem = false;
		private $is_socket_exe = false;
		private $isExpirable = false;
		private $hasTooltipLvl = false;
		private $server = false;
		private $isMuun = false;
		private $muunExpirationTime = false;
		private $expiretime = null;
		public $tooltip_options;

        public function __construct(){
            $this->load->lib('serverfile');
            $this->no_socket = (SOCKET_LIBRARY == 1) ? 255 : 0;
            $this->empty_socket = (SOCKET_LIBRARY == 1) ? 254 : 255;
            $this->socket = (SOCKET_LIBRARY == 1) ? [255, 255, 255, 255, 255, 255] : [0, 0, 0, 0, 0, 0];
        }
		
		public function itemData($item = '', $load_item_settings = true, $server = false){
            if(preg_match('/[a-fA-F0-9]{20,64}/', $item)){
                $this->hex = $item;
                $this->calculateItemVariables();
                $this->option = hexdec(substr($this->hex, 2, 2));
                $this->dur = hexdec(substr($this->hex, 4, 2));
                $this->serial = substr($this->hex, 6, 8);
                $this->ancient = hexdec(substr($this->hex, 16, 2));
                $this->index = $this->itemIndex($this->type, $this->id);
                if(strlen($this->hex) >= 32){											   
                    $this->harmony[0] = hexdec(substr($this->hex, 20, 1));
                    $this->harmony[1] = hexdec(substr($this->hex, 21, 1));
                    $this->socket[1] = hexdec(substr($this->hex, 22, 2));
                    $this->socket[2] = hexdec(substr($this->hex, 24, 2));
                    $this->socket[3] = hexdec(substr($this->hex, 26, 2));
                    $this->socket[4] = hexdec(substr($this->hex, 28, 2));
                    $this->socket[5] = hexdec(substr($this->hex, 30, 2));
                    if(strlen($this->hex) == 64){
                        $this->serial2 = substr($this->hex, 32, 8);
                    }
					if($server != false){
						$this->server = $server;
					}
                }

                if($load_item_settings){
                    $this->setItemData($this->id, $this->type, strlen($this->hex), $this->hex);
                }
				
				$this->addopt = '';
				$this->luck = '';
				$this->refopt = '';
				$this->haropt = '';
				$this->skill = '';
				$this->item_for = '';
				$this->class = '';
				$this->exe_options = '';
				$this->ancopt = '';
				$this->stamina = '';
				$this->sockopt = '';
				$this->bonussocketopt = '';
				$this->elementtype = '';
				$this->expiretime = '';
            } 
			else{
                writelog('Invalid item hex value. Value: ' . $item, 'system_error');
                return 'Invalid item hex value. Value: ' . $item;
            }
        }
		
		public function isMuun($muun = false){
			$this->isMuun = $muun;
		}
		
		private function calculateItemVariables(){
            $this->exe = hexdec(substr($this->hex, 14, 2));
            if(strlen($this->hex) == 20){
                $tempId = hexdec(substr($this->hex, 0, 2));
				$this->id = ($tempId & 31);
				$this->type = (($tempId & 224) >> 5);
				$this->type += (($this->exe & 128) == 128 ? 8 : 0);
				$this->exe -= 128;
				$this->exe = ($this->exe < 0) ? abs($this->exe) : $this->exe;
            } else{
                $this->id = hexdec(substr($this->hex, 0, 2));
                $this->type = hexdec(substr($this->hex, 18, 1));
				$this->ref = hexdec(substr($this->hex, 19, 1));
                if($this->exe >= 128){
                    $this->id += 256;
                    $this->exe -= 128;
                }
				if($this->ref == 1 || $this->ref == 3 || $this->isMuun){
					$this->type += 16;
					if($this->ref == 3){
						$this->isExpirable = true;
					}
				}
				if($this->ref == 2 || $this->ref > 8){
					$this->isExpirable = true;
				}	
            }
        }
		
		public function itemIndex($type, $id){
            return ($type * 512 + $id);
        }
		
		
		public function calculateIdCat($index){
			$cat = floor($index / 512);
			$catIndex = $cat * 512;
			$id = $index - $catIndex;
			return [$cat, $id];
		}

        public function setItemData($id = false, $type = false, $size = 32, $hex = null){
            static $data = [];
			if($size == 20)
				$size = 32;
            if(!isset($data[$type]))
                $data[$type] = $this->serverfile->item_list($type, $size)->get('items');
				
            if($data[$type] !== false){		
                if($id !== false){
                    if(array_key_exists($id, $data[$type])){					
                        $this->item_data = $data[$type][$id];
                        return true;
                    } else{
						if($hex != null){
							writelog('Item file load error - item with id: ' . $id . ' not found in category: ' . $type.'. HEX: '.$hex, 'system_error');
						}
						else{
							writelog('Item file load error - item with id: ' . $id . ' not found in category: ' . $type, 'system_error');
						}
                        throw new Exception('Item file load error - item with id: ' . $id . ' not found in category: ' . $type);
                    }
                } else{
                    $this->item_data = $data[$type];
                    return true;
                }
            } else{
				if($hex != null){
					writelog('Item file load - error category with id: ' . $type . ' not found. HEX: '.$hex, 'system_error');
				}
				else{
					writelog('Item file load - error category with id: ' . $type . ' not found', 'system_error');
				}
                throw new Exception('Item file load error - category with id: ' . $type . ' not found');
            }
            return false;
        }
		
		private function setItemTooltip($id = false, $type = false){
            static $data = [];
            if(empty($data))
                $data = $this->serverfile->item_tooltip()->get('item_tooltip');
			
            $this->item_tooltip = $data;
			
			if($id != false)
				$this->id = $id;
			if($id != false)
				$this->type = $type;
			
			
            if(is_array($this->item_tooltip)){
                if(array_key_exists($this->type, $this->item_tooltip)){
                    if(array_key_exists($this->id, $this->item_tooltip[$this->type])){
                        $this->item_tooltip = $this->item_tooltip[$this->type][$this->id];
                        return true;
                    }
                }
            }
            return false;
        }

        private function getItemTooltip(){
            $this->setItemTooltip();
            return $this->item_tooltip[$this->type][$this->id];
        }
		
		private function setItemTooltipText(){
            static $data = [];
            if(empty($data))
                $data = $this->serverfile->item_tooltip_text()->get('item_tooltip_text');
            $this->item_tooltip_text = $data;
        }

        private function getItemTooltipText(){
            $this->setItemTooltipText();
            return $this->item_tooltip_text;
        }

        private function setItemLevelTooltip(){
            static $data = [];
            if(empty($data))
                $data = $this->serverfile->item_level_tooltip()->get('item_level_tooltip');
            $this->item_level_tooltip = $data;
        }

        private function getItemLevelTooltip(){
            $this->setItemLevelTooltip();
            return $this->item_level_tooltip;
        }
		
		private function setTooltipOptions(){
            if($this->setItemTooltip()){
                $this->getItemTooltipText();
                $this->tooltip_options = '';
                if($this->item_tooltip['iInfoLine_1'] != -1)
                    $this->tooltip_options .= $this->findTooltipOption($this->item_tooltip['iInfoLine_1']);
                if($this->item_tooltip['iInfoLine_2'] != -1)
                    $this->tooltip_options .= $this->findTooltipOption($this->item_tooltip['iInfoLine_2']);
                if($this->item_tooltip['iInfoLine_3'] != -1)
                    $this->tooltip_options .= $this->findTooltipOption($this->item_tooltip['iInfoLine_3']);
                if($this->item_tooltip['iInfoLine_4'] != -1)
                    $this->tooltip_options .= $this->findTooltipOption($this->item_tooltip['iInfoLine_4']);
                if($this->item_tooltip['iInfoLine_5'] != -1)
                    $this->tooltip_options .= $this->findTooltipOption($this->item_tooltip['iInfoLine_5']);
                if($this->item_tooltip['iInfoLine_6'] != -1)
                    $this->tooltip_options .= $this->findTooltipOption($this->item_tooltip['iInfoLine_6']);
                if($this->item_tooltip['iInfoLine_7'] != -1)
                    $this->tooltip_options .= $this->findTooltipOption($this->item_tooltip['iInfoLine_7']);
                if($this->item_tooltip['iInfoLine_8'] != -1)
                    $this->tooltip_options .= $this->findTooltipOption($this->item_tooltip['iInfoLine_8']);
                if($this->item_tooltip['iInfoLine_9'] != -1)
                    $this->tooltip_options .= $this->findTooltipOption($this->item_tooltip['iInfoLine_9']);
                if($this->item_tooltip['iInfoLine_10'] != -1)
                    $this->tooltip_options .= $this->findTooltipOption($this->item_tooltip['iInfoLine_10']);
                if($this->item_tooltip['iInfoLine_11'] != -1)
                    $this->tooltip_options .= $this->findTooltipOption($this->item_tooltip['iInfoLine_11']);
                if($this->item_tooltip['iInfoLine_12'] != -1)
                    $this->tooltip_options .= $this->findTooltipOption($this->item_tooltip['iInfoLine_12']);
				if(isset($this->item_tooltip['iInfoLine_13']) && $this->item_tooltip['iInfoLine_13'] != -1)
                    $this->tooltip_options .= $this->findTooltipOption($this->item_tooltip['iInfoLine_13']);
				if(isset($this->item_tooltip['iInfoLine_14']) && $this->item_tooltip['iInfoLine_14'] != -1)
                    $this->tooltip_options .= $this->findTooltipOption($this->item_tooltip['iInfoLine_14']);
				if(isset($this->item_tooltip['iInfoLine_15']) && $this->item_tooltip['iInfoLine_15'] != -1)
                    $this->tooltip_options .= $this->findTooltipOption($this->item_tooltip['iInfoLine_15']);
                if($this->item_tooltip['Unk3'] != -1){
                    $this->checkItemLevelTooltip();
                }
				
                $this->tooltip_options = '<div class="item_light_blue item_size_12 item_font_family">' . preg_replace('/([0-9]{1,})+(%%)/i', '$1%', $this->tooltip_options) . '</div>';
            }
        }
		
		private function checkItemLevelTooltip(){
            $this->getItemLevelTooltip();
            if(array_key_exists($this->item_tooltip['Unk3'] + (int)substr($this->getLevel(), 1), $this->item_level_tooltip)){
                $value = $this->item_level_tooltip[$this->item_tooltip['Unk3'] + (int)substr($this->getLevel(), 1)];
                if($value[8] != -1){
                    $this->tooltip_options .= $this->findTooltipOption($value[8]);
                }
                if($value[10] != -1){
                    $this->tooltip_options .= $this->findTooltipOption($value[10]);
                }
                if($value[12] != -1){
                    $this->tooltip_options .= $this->findTooltipOption($value[12]);
                }
                if($value[14] != -1){
                    $this->tooltip_options .= $this->findTooltipOption($value[14]);
                }
                if($value[16] != -1){
                    $this->tooltip_options .= $this->findTooltipOption($value[16]);
                }
                if($value[18] != -1){
                    $this->tooltip_options .= $this->findTooltipOption($value[18]);
                }
                if($value[20] != -1){
                    $this->tooltip_options .= $this->findTooltipOption($value[20]);
                }
                if($value[22] != -1){
                    $this->tooltip_options .= $this->findTooltipOption($value[22]);
                }
                if($value[24] != -1){
                    $this->tooltip_options .= $this->findTooltipOption($value[24]);
                }
                if($value[26] != -1){
                    $this->tooltip_options .= $this->findTooltipOption($value[26]);
                }
                if($value[28] != -1){
                    $this->tooltip_options .= $this->findTooltipOption($value[28]);
                }
            }
        }
		
		private function findTooltipOption($id){
            if(!in_array($id, [10, 12, 14, 16, 18, 20, 174, 357])){
                if(array_key_exists($id, $this->item_tooltip_text)){
                    if(array_key_exists(3, $this->item_tooltip_text[$id])){
                        return $this->setTooltipOptionValues($id, $this->item_tooltip_text[$id][2]) . '<br />';
                    }
                }
            }
        }
		
		private function setTooltipOptionValues($id, $value){
            switch($id){
                case 0:
				case 1:
					return preg_replace('/(%[d](\s?)\~(\s?)%[d])/', $this->damage(), $value);
					break;
				case 2:
				case 71:
				case 72:
				case 56:
				case 86:
				case 198:
				case 359:
				case 395:
					return preg_replace('/(%[d])/', $this->dur, $value);
					break;
				case 260:
					return preg_replace('/(%[d])/', 5 - $this->dur, $value);
					break;		
				case 3:
				case 358:
					return preg_replace('/(%[d])/', $this->defense(), $value);
					break;
				case 5:
					return preg_replace('/(%[d])/', $this->successBlock(), $value);
					break;
				case 6:
				case 7:
					return preg_replace('/(%[d])/', $this->speed(), $value);
					break;
				case 8:
					return preg_replace('/(%[d]\/%[d])/', $this->dur . '/' . $this->durability(), $value);
					break;
				case 9:
					return preg_replace('/(%[d])/', $this->levelRequired(), $value);
					break;
				case 11:
					return preg_replace('/(%[d])/', $this->reqStr(), $value);
					break;
				case 13:
					return preg_replace('/(%[d])/', $this->reqAgi(), $value);
					break;
				case 15:
					return preg_replace('/(%[d])/', $this->reqVit(), $value);
					break;	
				case 17:
					return preg_replace('/(%[d])/', $this->reqEne(), $value);
					break;
				case 19:
					return preg_replace('/(%[d])/', $this->reqCom(), $value);
					break;
				case 21:
				case 22:
				case 23:
					return preg_replace('/(%[d]%)/', $this->magicPower(), $value);
					break;
				case 26:
				case 27:
				case 28:
				case 29:
				case 409:
				case 410:
				case 411:
				case 425:
				case 445:
				case 496:
				case 498:
				case 1022:
					return preg_replace('/(%[d]%)/', $this->increaseDamageWings(), $value);
					break;
				case 30:
				case 31:
				case 32:
				case 33:
				case 34:
				case 35:
				case 36:
				case 412:
				case 413:
				case 414:
				case 426:
				case 446:
				case 497:
				case 499:
				case 1023:
				case 1041:
					return preg_replace('/(%[d]%)/', $this->absorbDamageWings(), $value);
					break;
				case 1042:
					return preg_replace('/(%[d])/', $this->wings4ThDamage(), $value);
					break;
				case 1046:
					return preg_replace('/(%[d])/', $this->wings4ThDefense(), $value);
					break;	
				case 37:
					return preg_replace('/(%[d]\s%[s])/', (((int)substr($this->getLevel(), 1) + 1) * 10) . ' ' . str_replace(' Bundle', '', $this->item_data['name']), $value);
					break;
				case 47:
					return preg_replace('/(%[s])/', $this->socketOptionName(), $value);
					break;
				case 53:
					return preg_replace('/(%[s])/', $this->socketOptionValue(), $value);
					break;
				case 337:
					return preg_replace('/(%[d])/', $this->iceRes(), $value);
					break;
				case 338:
					return preg_replace('/(%[d])/', $this->poisonRes(), $value);
					break;
				case 339:
					return preg_replace('/(%[d])/', $this->lightRes(), $value);
					break;
				case 340:
					return preg_replace('/(%[d])/', $this->fireRes(), $value);
					break;
				case 341:
					return preg_replace('/(%[d])/', $this->earthRes(), $value);
					break;
				case 342:
					return preg_replace('/(%[d])/', $this->windRes(), $value);
					break;
				case 343:
					return preg_replace('/(%[d])/', $this->waterRes(), $value);
					break;
				case 360:
					return preg_replace('/(%[d])/', $this->countMountableSlots(), $value);
					break;	
				case 214:
					return preg_replace('/(%[d]%)/', $this->dur, $value);
					break;	
				case 465:
					if($this->isMuun){
						$muun_info = $this->serverfile->muun_info()->get('muun_info');
						if(isset($muun_info[$this->id])){
							switch($muun_info[$this->id]['rank']){
								case 1;
									return preg_replace('/(%[s])/', 'O', $value);
								break;
								case 2;
									return preg_replace('/(%[s])/', 'OO', $value);
								break;
								case 3;
									return preg_replace('/(%[s])/', 'OOO', $value);
								break;
								case 4;
									return preg_replace('/(%[s])/', 'OOOO', $value);
								break;
							}	
						}
					}
					return '';
					break;	
				case 466:
					if($this->isMuun){
						$max_level = 5;
						$muun_info = $this->serverfile->muun_info()->get('muun_info');
						if(isset($muun_info[$this->id])){
							$max_level = $muun_info[$this->id]['rank']+1;
							return '<span style="color: green;">'.__('Level').(int)$this->getLevel().' / '.__('Level').$max_level.'</span>';						
						}
					}
					return '';
					break;
				case 471:
				case 532:
					if($this->isMuun){
						$muun_info = $this->serverfile->muun_info()->get('muun_info');
						if(isset($muun_info[$this->id])){
							$muun_option_info = $this->serverfile->muun_option_info()->get('muun_option_info');
							if(isset($muun_option_info[$muun_info[$this->id]['option_index']])){
								$optData = $muun_option_info[$muun_info[$this->id]['option_index']];
								$optName = $optData['name'];
								if($muun_info[$this->id]['evo_item'] == -1){
									$optName = preg_replace('/(%[d])/', $optData['value0'], $optName);
								}
								else{
									$optName = preg_replace('/(%[d])/', $optData['value'.(int)$this->getLevel().''], $optName);
								}
								return __('If').' '.$optData['desc'].', '.str_replace('#', '<br />', $optName);
							}
						}
					}
					return '';
				case 472:
					if($this->isMuun){
						$this->getMuunExpireInfo();
						if($this->muunExpirationTime != false){
							$muun_info = $this->serverfile->muun_info()->get('muun_info');
							return '<span class="item_yellow">'.__('Until').' '.date('Y-m-d', $this->muunExpirationTime).', '.__('Ability will increase by').' '.$muun_info[$this->id]['option_value']. __('time(s)').'</span>';
						}
					}
					return '';
				case 473:
					if($this->isMuun){
						$this->getMuunExpireInfo();
						if($this->muunExpirationTime != false){
							$muun_info = $this->serverfile->muun_info()->get('muun_info');
							return '<span class="item_yellow">'.__('Until').' '.date('Y-m-d', $this->muunExpirationTime).', '.__('Ability will increase').' +'.$muun_info[$this->id]['option_value'].'</span>';
						}
					}
					return '';
				case 474:
					if($this->isMuun){
						$this->getMuunExpireInfo();
						if($this->muunExpirationTime != false){
							$muun_info = $this->serverfile->muun_info()->get('muun_info');
							return '<span class="item_yellow">'.__('Until').' '.date('Y-m-d', $this->muunExpirationTime).', '.__('Skill delay will be reduced by').' '.$muun_info[$this->id]['option_value']. __('time(s)').'</span>';
						}
					}
					return '';
				break;
				case 517:
					if($this->isMuun){
						$this->getMuunExpireInfo();
						if($this->muunExpirationTime != false){
							$muun_info = $this->serverfile->muun_info()->get('muun_info');
							return '<span class="item_yellow">'.__('Until').' '.date('Y-m-d H:i', $this->muunExpirationTime).', '.__('stats are increased by').' '.$muun_info[$this->id]['option_value'].__('(x)').'</span>';
						}
					}
					return '';
				case 518:
					if($this->isMuun){
						$this->getMuunExpireInfo();
						if($this->muunExpirationTime != false){
							$muun_info = $this->serverfile->muun_info()->get('muun_info');
							return '<span class="item_yellow">'.__('Until').' '.date('Y-m-d H:i', $this->muunExpirationTime).', '.__('stats are increased by').' +'.$muun_info[$this->id]['option_value'].'</span>';
						}
					}
					return '';
				case 519:
					if($this->isMuun){
						$this->getMuunExpireInfo();
						if($this->muunExpirationTime != false){
							$muun_info = $this->serverfile->muun_info()->get('muun_info');
							return '<span class="item_yellow">'.__('Until').' '.date('Y-m-d H:i', $this->muunExpirationTime).', '.__('the speed of using MUUN skills is increased by').' '.$muun_info[$this->id]['option_value'].__('(x)').'</span>';
						}
					}
					return '';
				case 520:
					if($this->isMuun){
						$this->getMuunExpireInfo();
						if($this->muunExpirationTime != false){
							$muun_info = $this->serverfile->muun_info()->get('muun_info');
							return '<span class="item_yellow">'.__('Until').' '.date('Y-m-d H:i', $this->muunExpirationTime).', '.__('the damage of attack skills is increased by').' '.$muun_info[$this->id]['option_value'].__('(x)').'</span>';
						}
					}
					return '';
				break;
				case 475:
					return '<span style="color: green;">'.__('Level Max').'</span>';
					break;
				case 477:
					return __('Life: ').$this->dur;
					break;	
					
				case 1070:
					return preg_replace('/(%[d]~%[d])/', $this->damage(), $value);
					break;	
				
				default:
					return str_replace('\'', '&#39;', $value);
					break;
            }
        }
		
		public function getItemSkill(){
            static $data = [];
            if(empty($data))
                $data = $this->serverfile->skill()->get('skill');
            if($this->item_data['skill'] > 0){
                $option = $this->option;
                if($option >= 128){
                    $this->skill_list = $data;
					if(is_array($this->skill_list)){
						if(array_key_exists($this->item_data['skill'], $this->skill_list)){
							$this->skill = '<div class="item_light_blue item_size_12 item_font_family">' . $this->skill_list[$this->item_data['skill']]['name'] . ' ' . __('skill') . ' (' . __('Mana') . ':' . $this->skill_list[$this->item_data['skill']]['mana'] . ')</div>';
						}
					}
                }
            }
        }

        public function hasSkill(){
            $skill = 0;
            if($this->item_data['skill'] > 0){
                $option = $this->option;
                if($option >= 128){
                    $skill = 1;
                }
            }
            return $skill;
        }
		
		public function getLevel(){
            $level = 0;
            $option = $this->option;
            if($option >= 128){
                $option -= 128;
            }
            while($option - 8 >= 0){
                $level++;
                $option -= 8;
            }
            if($option - 4 >= 0){
                $option -= $option;
            }
            return '+' . $level;
        }
		
		public function setLevel($level){
			if($level > 0){
				$this->option += $level * 8;
			}
		}
		
		public function getOption(){
            $option = $this->option;
            if($option >= 128)
                $option -= 128;
            $option = $option - floor($option / 8) * 8;
            if($option >= 4)
                $option -= 4;
			
            if($this->exe >= 64 && MU_VERSION >= 1)
                $option += 4;
            return $option;
        }

        public function getLuck(){
            $luck = 0;
			$option = $this->option;
			if($option >= 128)
				$option -= 128;
			$option = $option - floor($option / 8) * 8;
			if($option >= 4){
				$this->luck = '<div class="item_size_12 item_font_family item_luck">'.str_replace('(L', 'L', __('(Luck(success rate of Jewel of Soul +25%)')).'<br />'.__('Luck(critical damage rate +5%)').'</div>';
				$luck = 1;
			}
			return $luck;
        }
		
		public function additionalOption(){
            $this->addopt = '';
            $option = $this->getOption();
			
            if($option > 0){
                if($this->type == 6){
                    $this->addopt = __('Additional Defense Rate') . ' +' . ($option * 5);
                } else if($this->item_data['slot'] == 9 || $this->item_data['slot'] == 10 || $this->item_data['slot'] == 11){
                    if($this->type == 13 && $this->id == 28){
						$this->addopt = __('Max AG increase by') . ' ' . $option . '%';
					}
					else{
						$this->addopt = __('Automatic Hp Recovery') . ' ' . $option . '%';
					}
                } else{
					if($this->item_data['slot'] == 7){
						if(array_key_exists('dw/sm', $this->item_data) && in_array($this->item_data['dw/sm'], [1, 2, 3])){
							$this->addopt = __('Additional Wizardy Dmg') . ' +' . $option * 4;
						}
						elseif(array_key_exists('elf/me', $this->item_data) && in_array($this->item_data['elf/me'], [1, 2, 3])){
							$this->addopt = __('Automatic HP recovery') . ' ' . $option . '%';
						}
						else{
							$this->addopt = __('Additional Damage') . ' +' . $option * 4;
						}
					}
					else{
						$exe_type = $this->getExeType($this->item_data['slot'], $this->id, $this->type);
						if($exe_type == 1){
							$this->addopt = __('Additional Damage') . ' +' . $option * 4;
						}
						if($exe_type == 2){
							$this->addopt = __('Additional Defense') . ' +' . $option * 4;
						}
					}
                }
                $this->addopt = '<div class="item_light_blue item_size_12 item_font_family">' . $this->addopt . '</div>';
            }
        }
		
		
		public function exeForCompare(){
			$exe_opts = [0, 0, 0, 0, 0, 0, 0, 0, 0];
			$exe = $this->exe;
			if(MU_VERSION >= 1){
				if($exe >= 64){
					$exe -= 64;
				}
			}
			if($exe >= 32){
                $exe -= 32;
                $exe_opts[5] = 1;
            }
            if($exe >= 16){
                $exe -= 16;
                $exe_opts[4] = 1;
            }
            if($exe >= 8){
                $exe -= 8;
                $exe_opts[3] = 1;
            }
            if($exe >= 4){
                $exe -= 4;
                $exe_opts[2] = 1;
            }
            if($exe >= 2){
                $exe -= 2;
                $exe_opts[1] = 1;
            }
            if($exe >= 1){
                $exe -= 1;
                $exe_opts[0] = 1;
            }
			return $exe_opts;
		}
		
		public function exeOpts(){
            $exe = $this->exe;
			if(MU_VERSION >= 1){
				if($exe >= 64){
					$exe -= 64;
				}
			}
            $exe_opts = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
            if($exe >= 32){
                $exe_opts[6] = 6;
                $exe -= 32;
            }
            if($exe >= 16){
                $exe_opts[5] = 5;
                $exe -= 16;
            }
            if($exe >= 8){
                $exe_opts[4] = 4;
                $exe -= 8;
            }
            if($exe >= 4){
                $exe_opts[3] = 3;
                $exe -= 4;
            }
            if($exe >= 2){
                $exe_opts[2] = 2;
                $exe -= 2;
            }
            if($exe >= 1){
                $exe_opts[1] = 1;
                $exe -= 1;
            }
            if(MU_VERSION <= 5){
                for($i = 1; $i <= 3; $i++){
                    if(in_array($this->socket[$i], [6, 7, 8, 9])){
                        if($this->socket[$i] == 6){
                            $exe_opts[7] = 7;
                        }
                        if($this->socket[$i] == 7){
                            $exe_opts[8] = 8;
                        }
                        if($this->socket[$i] == 8){
                            $exe_opts[9] = 9;
                        }
                        if($this->socket[$i] == 9){
                            $exe_opts[10] = 10;
                        }
                    }
                }
            }
            return $exe_opts;
        }
	
		public function getExe(){
            $this->exe_options = '';
            $exe = $this->exe;
			if(MU_VERSION >= 1){
				if($exe >= 64){
					$exe -= 64;
				}
			}
            if($exe >= 32){
				$opt = $this->findExeOption(0);
				if($opt != NULL){
					$this->exe_options .= $this->findExeOption(0) . '<br />';
				}
                $exe -= 32;
                $this->exe_count += 1;
            }
            if($exe >= 16){
				$opt = $this->findExeOption(1);
				if($opt != NULL){
					$this->exe_options .= $this->findExeOption(1) . '<br />';
				}
                $exe -= 16;
                $this->exe_count += 1;
            }
            if($exe >= 8){
				$opt = $this->findExeOption(2);
				if($opt != NULL){
					$this->exe_options .= $this->findExeOption(2) . '<br />';
				}
                $exe -= 8;
                $this->exe_count += 1;
            }
            if($exe >= 4){
				$isFenrir = $this->isFenrir($exe);
				if(!$isFenrir){
					$opt = $this->findExeOption(3);
					if($opt != NULL){
						$this->exe_options .= $this->findExeOption(3) . '<br />';
					}
				}
				
				if($isFenrir){
					if($exe == 4)
						$exe -= 4;
					if($exe == 5)
						$exe -= 5;
					if($exe == 6)
						$exe -= 6;
				}
				else{
					$exe -= 4;
				}
                $this->exe_count += 1;
            }
            if($exe >= 2){
                if(!$this->isFenrir($exe)){
					$opt = $this->findExeOption(4);
					if($opt != NULL){
						$this->exe_options .= $this->findExeOption(4) . '<br />';
					}
                }
                $exe -= 2;
                $this->exe_count += 1;
            }
            if($exe >= 1){
                if(!$this->isFenrir($exe)){
					$opt = $this->findExeOption(5);
					if($opt != NULL){
						$this->exe_options .= $this->findExeOption(5) . '<br />';
					}
                }
                $exe -= 1;
                $this->exe_count += 1;
            }
            $this->exe_options = '<div class="item_light_blue item_size_12 item_font_family">' . $this->exe_options . '</div>';
        }
		
		
		private function findExeOption($opt_number){
            static $data = [];
            $kind = $this->getExeType($this->item_data['slot'], $this->id, $this->type);
            if(in_array($kind, [1, 2])){
                if(!isset($data['common']))
                    $data['common'] = $this->serverfile->exe_common()->get('exe_common');
                $this->exe_option_list = $data['common'];
            }
            if(in_array($kind, [24, 25, 26, 27, 28, 60, 62])){
                if(!isset($data['wings']))
                    $data['wings'] = $this->serverfile->exe_wing()->get('exe_wing');
                $this->exe_option_list = $data['wings'];
            }
			
			if(!empty($this->exe_option_list)){
				foreach($this->exe_option_list AS $key => $option){
					if($option[2] == $kind){
						if($option[3] == $opt_number){
							if($opt_number == 2 && $this->type == 13 && in_array($this->id, [12, 25, 27])){
								$option = __('Increase Wizardy Dmg +2%');
							}
							else{
								if($option[5] == 100 && isset($this->item_data['lvldrop'])){
									$option = preg_replace('/(%[d])/', '+' . $this->exeFormula((int)$this->item_data['lvldrop'], $option[7]), preg_replace('/(%[d]%)/', $this->exeFormula((int)$this->item_data['lvldrop'], $option[7]), $option[4]));
								}
								else{
									$option = preg_replace('/(%[d])/', '+' . $option[7], preg_replace('/(%[d]%)/', $option[7], $option[4]));
								}
							}
							
							$type = ($kind == 1) ? __('Attack') : __('Defense');
							$option = preg_replace('/(%[s])/', $type, $option);
							return $option;
						}
					}
				}
			}
			return '';
        }
		
		
		public function exeFormula($value, $type){
			switch($type){
				case 0:
					return $value; //<!-- ItemDropLevel, PlayerLevel -->
				break;
				case 1:
					return round($value/3.5); //<!-- ItemDropLevel -->
				break;
				case 2:
					return round($value*1.1);  //<!-- ItemDropLevel -->
				break;
				case 3:
					return round($value*1.1);  //<!-- ItemDropLevel -->
				break;
				case 4:
					return round($value*0.3);  //<!-- ItemDropLevel -->
				break;
				case 5:
					return round($value/3.5);  //<!-- ItemDropLevel -->
				break;
				case 6:
					return $value; //<!-- ItemDropLevel, PlayerLevel -->
				break;
			}
		}

        private function isFenrir($exe){
            if($this->type == 13 && $this->id == 37){
                if($exe == 1)
                    $this->exe_options = __('Increases final damage by 10%') . '<br />';
                if($exe == 2)
                    $this->exe_options = __('Absorbs 10% of final damage') . '<br />';
                if($exe == 4)
                    $this->exe_options = __('Fenrir +Illusion') . '<br />';
				if($exe == 5)
                    $this->exe_options = __('Fenrir +Silver') . '<br />';
				if($exe == 6)
                    $this->exe_options = __('Fenrir +Purple') . '<br />';
                return true;
            }
            return false;
        }
		
		public function getRefinery(){
            if(!in_array($this->type, [12, 13, 14, 15])){
                if($this->ref == 8){
                    $load_ref_file = file(APP_PATH . DS . 'data' . DS . 'shop' . DS . 'shop_ref_type.dmn');
                    foreach($load_ref_file as $loaded_ref_file){
                        $ref_opt = explode("|", $loaded_ref_file);
                        if($this->type == $ref_opt[0]){
                            $this->refopt = '<div class="item_pink item_size_12 item_font_family">' . $ref_opt[1] . '</div>';
                        }
                    }
                }
            }
        }
		
		public function getHarmony(){
            static $data = [];
			if($this->getExeType($this->item_data['slot'], $this->id, $this->type) == 76){
				$this->getWingsGradeOptions();
			}
			else{
				if($this->type <= 11){
					$itemType = $this->isSocketItem();
					
					if($itemType != 2){
						if($this->harmony[0] > 0){
							if(empty($data))
								$data = $this->serverfile->jewel_of_harmony_option()->get('jewel_of_harmony_option');
							$this->harmony_list = $data;
							$offset = 0;
							if($this->type < 5)
								$offset = -1;
							if($this->type == 5)
								$offset = 249;
							if($this->type > 5)
								$offset = 499;
							foreach($this->harmony_list AS $key => $value){
								if($value[1] == ($offset + $this->harmony[0])){
									$this->haropt = '<div class="item_yellow item_size_12 item_font_family">' . $value[3] . ' +' . $value[$this->harmony[1] + 4] . '</div>';
									break;
								}
							}
						}
					}
					else{
						$bonus_socket = $this->bonusSocket();
						if($bonus_socket != 0){
							$bonussockets = $this->config->values('bonussockets_config');	
							if(!empty($bonussockets)){
								if($this->type < 5)
									$bonussockets = $bonussockets[0];
								else
									$bonussockets = $bonussockets[1];
								$this->bonussocketopt = '<div class="item_socket item_size_12 item_font_family">'.__('Bonus Socket Option').'</div>';
								if(isset($bonussockets[$bonus_socket])){
									$this->bonussocketopt .= '<div class="item_socket item_size_12 item_font_family">'.__($bonussockets[$bonus_socket]).'</div>';
								}
								else{
									$this->bonussocketopt .= '<div class="item_socket item_size_12 item_font_family">None</div>';
								}
							}
						}
					}
				}
			}
        }
		
		
		public function bonusSocket(){
			return hexdec(substr($this->hex, 20, 2));
		}
		
		
		public function getWingsGradeOptions(){
			if(MU_VERSION >= 8){
				static $data = [];
				if(empty($data))
					$data = $this->serverfile->item_grade_option()->get('item_grade_option');
				$opt = hexdec(substr($this->hex, 20, 2));
				if($opt != 255){
					$mvalues = [25, 35, 45, 55, 65, 75, 85, 96, 107, 118, 130, 142, 154, 167, 180, 193];
					$this->sockopt = '<div class="item_grey item_size_12 item_font_family">'.__('Elemental DEF').' ('.__('Lv').'. '.$opt.'): '.__('Increase by').' '.$mvalues[$opt].'</div>';
				}
				if($this->socket[5] != 255){
					$id = 0;
					if($this->socket[5] >= 96){
						$id = 6;
						$this->socket[5] -= 96;
					}
					if($this->socket[5] >= 80){
						$id = 5;
						$this->socket[5] -= 80;
					}
					if($this->socket[5] >= 64){
						$id = 4;
						$this->socket[5] -= 64;
					}
					if($this->socket[5] >= 48){
						$id = 3;
						$this->socket[5] -= 48;
					}
					if($this->socket[5] >= 32){
						$id = 2;
						$this->socket[5] -= 32;
					}
					if($this->socket[5] >= 16){
						$id = 1;
						$this->socket[5] -= 16;
					}
					$options = [
						0 => [20, 23, 27, 32, 38, 45, 53, 62, 72, 83, 95, 108, 122, 137, 153, 170, __('Elemental Damage Increase')],
						1 => [5, 10, 15, 20, 26, 32, 38, 45, 52, 59, 67, 75, 84, 93, 104, 125, __('Elemental Attack Success Rate Increase')],
						2 => [5, 10, 15, 20, 26, 32, 38, 45, 52, 59, 67, 75, 84, 93, 104, 125, __('Elemental Defense Success Rate Increase')],
						3 => [30, 34, 39, 45, 52, 60, 69, 79, 90, 102, 115, 129, 144, 160, 177, 195, __('Elemental Damage (II) Increase')],
						4 => [4, 6, 8, 10, 13, 16, 19, 23, 27, 31, 36, 41, 46, 52, 58, 64, __('Elemental Defense (II) Increase')],
						5 => [10, 15, 25, 35, 46, 57, 68, 80, 92, 104, 117, 130, 144, 158, 174, 200, __('Elemental Attack Success Rate (II) Increase')],
						6 => [10, 15, 25, 35, 46, 57, 68, 80, 92, 104, 117, 130, 144, 158, 174, 200, __('Elemental Defense Success Rate (II) Increase')],
					];
					if(isset($options[$id])){
						$this->sockopt .= '<div class="item_grey item_size_12 item_font_family">'.$options[$id][16].' ('.__('Lv').'. '.$this->socket[5].'): '.__('Increase by').' '.$options[$id][$this->socket[5]].'</div>';
					}
				}
				$this->winggradeopt = '';
				for($i = 1; $i <= 4; $i++){
					if($this->socket[$i] != 255){
						$id = 0;
						if($this->socket[$i] >= 160){
							$id = 10;
							$this->socket[$i] -= 160;
						}
						if($this->socket[$i] >= 144){
							$id = 9;
							$this->socket[$i] -= 144;
						}
						if($this->socket[$i] >= 128){
							$id = 8;
							$this->socket[$i] -= 128;
						}
						if($this->socket[$i] >= 112){
							$id = 7;
							$this->socket[$i] -= 112;
						}
						if($this->socket[$i] >= 96){
							$id = 6;
							$this->socket[$i] -= 96;
						}
						if($this->socket[$i] >= 80){
							$id = 5;
							$this->socket[$i] -= 80;
						}
						if($this->socket[$i] >= 64){
							$id = 4;
							$this->socket[$i] -= 64;
						}
						if($this->socket[$i] >= 48){
							$id = 3;
							$this->socket[$i] -= 48;
						}
						if($this->socket[$i] >= 32){
							$id = 2;
							$this->socket[$i] -= 32;
						}
						if($this->socket[$i] >= 16){
							$id = 1;
							$this->socket[$i] -= 16;
						}

						if(isset($data[$id])){
							$replace = preg_replace('/(%[d]%)/', $data[$id]['Grade'.$this->socket[$i].'Val'], $data[$id]['name']);
							$this->winggradeopt .= '<div class="item_light_blue item_size_12 item_font_family">' . preg_replace('/(%[d])/', $data[$id]['Grade'.$this->socket[$i].'Val'], $replace) . '</div>';
						}
					}
				}
			}
		}
		
		public function elementType(){
            if($this->isPentagramItem() || $this->isErrtelItem()){
                if($this->harmony[1] == 1){
                    $this->elementtype = '<div class="item_red">(' . __('Fire Element') . ')</div>';
                } else if($this->harmony[1] == 2){
                    $this->elementtype = '<div class="item_blue">(' . __('Water Element') . ')</div>';
                } else if($this->harmony[1] == 3){
                    $this->elementtype = '<div class="item_yellow_2">(' . __('Earth Element') . ')</div>';
                } else if($this->harmony[1] == 4){
                    $this->elementtype = '<div class="item_light_green">(' . __('Wind Element') . ')</div>';
                } else if($this->harmony[1] == 5){
                    $this->elementtype = '<div class="item_purple">(' . __('Darkness Element') . ')</div>';
                } else{
                    $this->elementtype = '<div class="item_dark_red">' . __('Invalid Elements') . '</div>';
                }
            }
            return $this->elementopt;
        }
		
		public function elementInfo(){
            $this->elementopt = '';
            if($this->isPentagramItem() || $this->isErrtelItem()){
                for($i = 1; $i <= 5; $i++){
                    if($this->socket[$i] != $this->no_socket){
                        if($i == 1){
                            $this->elementopt .= '<div class="item_white">' . __('Slot of Anger') . ' (' . $i . ')</div>';
                            if($this->socket[$i] == $this->empty_socket){
                                $this->elementopt .= '<div class="item_light_blue_2">' . __('None') . '</div>';
                            } else{
                                $this->elementopt .= '<div class="item_light_blue_2">' . __('Errtel of Anger') . '</div>';
                            }
                        }
                        if($i == 2){
                            $this->elementopt .= '<div class="item_white">' . __('Slot of Blessing') . ' (' . $i . ')</div>';
                            if($this->socket[$i] == $this->empty_socket){
                                $this->elementopt .= '<div class="item_light_blue_2">' . __('None') . '</div>';
                            } else{
                                $this->elementopt .= '<div class="item_light_blue_2">' . __('Errtel of Blessing') . '</div>';
                            }
                        }
                        if($i == 3){
                            $this->elementopt .= '<div class="item_white">' . __('Slot of Integrity') . ' (' . $i . ')</div>';
                            if($this->socket[$i] == $this->empty_socket){
                                $this->elementopt .= '<divclass="item_light_blue_2">' . __('None') . '</div>';
                            } else{
                                $this->elementopt .= '<div class="item_light_blue_2">' . __('Errtel of Integrity') . '</div>';
                            }
                        }
                        if($i == 4){
                            $this->elementopt .= '<div class="item_white">' . __('Slot of Divinity') . ' (' . $i . ')</div>';
                            if($this->socket[$i] == $this->empty_socket){
                                $this->elementopt .= '<div class="item_light_blue_2">' . __('None') . '</div>';
                            } else{
                                $this->elementopt .= '<div class="item_light_blue_2">' . __('Errtel of Divinity') . '</div>';
                            }
                        }
                        if($i == 5){
                            $this->elementopt .= '<div class="item_white">' . __('Slot of Gale') . ' (' . $i . ')</div>';
                            if($this->socket[$i] == $this->empty_socket){
                                $this->elementopt .= '<div class="item_light_blue_2">' . __('None') . '</div>';
                            } else{
                                $this->elementopt .= '<div class="item_light_blue_2">' . __('Errtel of Gale') . '</div>';
                            }
                        }
                    }
                }
            }
            if($this->isErrtelItem()){
                $this->pentagram_option_info = $this->serverfile->pentagram_jewel_option_value(MU_VERSION)->get('pentagram_jewel_option_value');
                $s_data = [];
                for($i = 1; $i <= 5; $i++){
                    if($this->socket[$i] != 255){
						$this->errtel_rank += 1;
                        if($this->socket[$i] <= 5){
                            $s_data[$i]['lvl'] = 0;
                            $s_data[$i]['rank'] = $this->socket[$i];
                        } else{
                            $s_data[$i]['lvl'] = round($this->socket[$i] / 16, 0, PHP_ROUND_HALF_UP);
                            $s_data[$i]['rank'] = $this->socket[$i] - ($s_data[$i]['lvl'] * 16);
                        }
                        $s_data[$i]['contents'] = '<div>' . ($i) . ' ' . __('Rank Option') . ' ' . '+' . $s_data[$i]['lvl'] . '</div>';
                        $s_data[$i]['contents'] .= '<div class="item_light_blue_2">' . $this->loadElementName(($i), $s_data[$i]['rank'], $s_data[$i]['lvl']) . '</div>';
                    } else{
                        $s_data[$i]['contents'] = '';
                    }
                }
                $this->elementopt = $s_data[1]['contents'] . $s_data[2]['contents'] . $s_data[3]['contents'] . $s_data[4]['contents'] . $s_data[5]['contents'];
            }
        }
		
		public function loadElementName($element, $rank, $lvl){	
			if($this->pentagram_option_info == null){
				$this->pentagram_option_info = $this->serverfile->pentagram_jewel_option_value(MU_VERSION)->get('pentagram_jewel_option_value');
            }
			foreach($this->pentagram_option_info AS $key => $value){
               //if($value['cat'] == $this->type){
                    if($value['id'] == $this->item_data['id']){
                        if($value['rank'] == $element){
                            if($value['num'] == $rank){
								$optVal = $value['OptionValue'.$lvl.''];
								return str_replace('\'', '', preg_replace('/(%[d])/', $optVal, preg_replace('/(%[d]%)/', $optVal, $value['name'])));
                            }
                        }
                    }
                //}
            }
            return __('Unknown');
        }
		
		public function countMountableSlots(){
            $slots = array_count_values($this->socket);
            unset($slots[0]);
            $mountable_slots = 5;
            if(isset($slots[$this->no_socket])){
                $mountable_slots -= $slots[$this->no_socket];
            }
            return $mountable_slots;
        }
		
		private function socketOptionName(){
            $level = (int)substr($this->getLevel(), 1);
            $element_type = $this->socketElementType();
            foreach($this->getSocketData() AS $key => $value){
                if($value[3] == $element_type){
                    if($value[4] == $level){
                        return $value[5];
                        break;
                    }
                }
            }
            return __('Unknown');
        }
		
		private function socketOptionValue(){
            $level = (int)substr($this->getLevel(), 1);
            $element_type = $this->socketElementType();
            foreach($this->getSocketData() AS $key => $value){
                if($value[3] == $element_type){
                    if($value[4] == $level){
                        $add = ($level == 0) ? $level + 1 : $level;
                        return $this->addSocketBonusType($value[6 + $add], $value[6]);
                        break;
                    }
                }
            }
        }
		
		
		public function selectSocketOption($seedsIndex, $number, $socket, $socket_id, $seed, $i, $value){
			$selected = 0;
			if(isset($seedsIndex[$number]) && $seedsIndex[$number] == $value && $socket_id == $socket[$number] && $i >= 6){
				$selected = 1;
			}
			else{
				$ancient = $this->ancient;
				$exe = $this->exe;
				if($ancient >= 192){
					$checkTierS1 = 1;
					$ancient -= 192;
				}
				else{
					if($ancient >= 128){
						$checkTierS1 = 1;
						$ancient -= 128;
					}
					else{
						if($ancient >= 64){
							$checkTierS1 = 0;
							$ancient -= 64;
						}
						else{
							$checkTierS1 = 0;
						}
					}
				}
				if($ancient >= 48){
					$checkTierS2 = 1;
					$ancient -= 32;
				}
				else{
					if($ancient >= 32){
						$checkTierS2 = 1;
						$ancient -= 32;
					}
					else{
						if($ancient >= 16){
							$checkTierS2 = 0;
							$ancient -= 16;
						}
						else{
							$checkTierS2 = 0;
						}
					}
				}
				if($ancient >= 12){
					$checkTierS3 = 1;
					$ancient -= 12;
				}
				else{
					if($ancient >= 8){
						$checkTierS3 = 1;
						$ancient -= 8;
					}
					else{
						if($ancient >= 4){
							$checkTierS3 = 0;
							$ancient -= 4;
						}
						else{
							$checkTierS3 = 0;
						}
					}
				}
				if($ancient >= 3){
					$checkTierS4 = 1;
					$ancient -= 3;
				}
				else{
					if($ancient >= 2){
						$checkTierS4 = 1;
						$ancient -= 2;
					}
					else{
						if($ancient >= 1){
							$checkTierS4 = 0;
							$ancient -= 1;
						}
						else{
							$checkTierS4 = 0;
						}
					}
				}
				$option = $this->getOption();
				$addon = ($option > 3) ? 64 : 0;

				if($exe >= (48+$addon)){
					$checkTierS5 = 1;
					$exe -= 48;
				}
				else{
					if($exe >= (32+$addon)){
						$checkTierS5 = 1;
						$exe -= 32;
					}
					else{
						$checkTierS5 = 0;
					}
				}
				
				if($i > 10){
					if($checkTierS1 == 1 && $number == 1){
						if($socket_id == $socket[$number] && $seed == $value){
							$selected = 1;
						}
					}
					if($checkTierS2 == 1 && $number == 2){
						if($socket_id == $socket[$number] && $seed == $value){
							$selected = 1;
						}
					}
					if($checkTierS3 == 1 && $number == 3){
						if($socket_id == $socket[$number] && $seed == $value){
							$selected = 1;
						}
					}
					if($checkTierS4 == 1 && $number == 4){
						if($socket_id == $socket[$number] && $seed == $value){
							$selected = 1;
						}
					}
					if($checkTierS5 == 1 && $number == 5){
						if($socket_id == $socket[$number] && $seed == $value){
							$selected = 1;
						}
					}
				}
				else{
					if($socket_id == $socket[$number] && $seed == $value){
						if(isset($seedsIndex[$number]) && $i >= 6){
							$selected = 1;
						}
						elseif(!isset($seedsIndex[$number]) && $i < 6){
							$selected = 1;
						}
					}
				}
			}
			return $selected;
		}
		
		
		public function isSocketItem(){
			if(!isset($this->item_data['type']) || $this->item_data['type'] == ''){
				$soketItems = $this->serverfile->socket_item_type()->get('sockettype');
				if(isset($soketItems[$this->type])){
					if(in_array($this->id, $soketItems[$this->type])){
						$itemType = 2;
					}
					else{
						$itemType = 1;
					}
				}
				else{
					$itemType = 1;
				}
			}
			else{
				$itemType = $this->item_data['type'];
			}
			
			if($this->type == 12 && in_array($this->id, [449, 450, 451, 452, 457, 458, 459, 460])){	
				$itemType = 3;
			}
			return $itemType;
		}
		
		
		public function getSockets(){ 	
			$itemType = $this->isSocketItem();
			
			if($itemType == 2){
				if($this->socket[1] != $this->no_socket || $this->socket[2] != $this->no_socket || $this->socket[3] != $this->no_socket || $this->socket[4] != $this->no_socket || $this->socket[5] != $this->no_socket){
					$this->sockopt = '<div class="item_socket item_size_12 item_font_family">';
					for($i = 1; $i <= 5; $i++){
						if(SOCKET_LIBRARY != 1){
							if($this->socket[$i] != 255 && $this->socket[$i] != 0)
								$this->socket[$i] -= 1;
						}
					}	
					
					$seedsIndex = $this->seedsIndex((MU_VERSION >= 11) ? 1 : 0);
					
					$socket_config = $this->config->values('sockets_config');	
					
					if($this->getExeType($this->item_data['slot'], $this->id, $this->type) == 1){
						if($this->type == 20){
							unset($socket_config[1]);
							unset($socket_config[3]);
							unset($socket_config[5]);
						}
						else{
							unset($socket_config[2]);
							unset($socket_config[4]);
							unset($socket_config[6]);
						}
					}
					else{
						unset($socket_config[1]);
						unset($socket_config[3]);
						unset($socket_config[5]);
					}

					$sOptions = [];
					foreach($this->getSocketData() as $key => $value){
						if(isset($socket_config[$value[3]])){
							foreach($socket_config[$value[3]] AS $seed => $v){
								if($value[1] == $seed){	
									$i = 1; 
									foreach($v AS $socket_id => $data){
										if($this->socket[1] == $this->empty_socket){
												$sOptions[0] = __('Socket') . ' 1: ' . __('No item application') . '<br>';
										}
										else{
											if($this->selectSocketOption($seedsIndex, 1, $this->socket, $socket_id, $seed, $i, $value[1]) == 1){
												$sOptions[0] = __('Socket') . ' 1: '.__('Lv').'.'.$i.' ' . $this->socketElementTypeName($value[3], $value[5] . ' ' . $this->addSocketBonusType($value[6 + $i], $value[6])) . '<br>';
											}
										}
 
										if($this->socket[2] == $this->empty_socket){
												$sOptions[1] = __('Socket') . ' 2: ' . __('No item application') . '<br>';
										}
										else{
											if($this->selectSocketOption($seedsIndex, 2, $this->socket, $socket_id, $seed, $i, $value[1]) == 1){
												$sOptions[1] = __('Socket') . ' 2: '.__('Lv').'.'.$i.' ' . $this->socketElementTypeName($value[3], $value[5] . ' ' . $this->addSocketBonusType($value[6 + $i], $value[6])) . '<br>';
											}
										}
										
										if($this->socket[3] == $this->empty_socket){
												$sOptions[2] = __('Socket') . ' 3: ' . __('No item application') . '<br>';
										}
										else{
											if($this->selectSocketOption($seedsIndex, 3, $this->socket, $socket_id, $seed, $i, $value[1]) == 1){
												$sOptions[2] = __('Socket') . ' 3: '.__('Lv').'.'.$i.' ' . $this->socketElementTypeName($value[3], $value[5] . ' ' . $this->addSocketBonusType($value[6 + $i], $value[6])) . '<br>';
											}
										}

										if($this->socket[4] == $this->empty_socket){
												$sOptions[3] = __('Socket') . ' 4: ' . __('No item application') . '<br>';
										}
										else{
											if($this->selectSocketOption($seedsIndex, 4, $this->socket, $socket_id, $seed, $i, $value[1]) == 1){
												$sOptions[3] = __('Socket') . ' 4: '.__('Lv').'.'.$i.' ' . $this->socketElementTypeName($value[3], $value[5] . ' ' . $this->addSocketBonusType($value[6 + $i], $value[6])) . '<br>';
											}
										}
 
										if($this->socket[5] == $this->empty_socket){
												$sOptions[4] = __('Socket') . ' 5: ' . __('No item application') . '<br>';
										}
										else{
											if($this->selectSocketOption($seedsIndex, 5, $this->socket, $socket_id, $seed, $i, $value[1]) == 1){
												$sOptions[4] = __('Socket') . ' 5: '.__('Lv').'.'.$i.' ' . $this->socketElementTypeName($value[3], $value[5] . ' ' . $this->addSocketBonusType($value[6 + $i], $value[6])) . '<br>';
											}
										}
										$i++;
									}
								}
							}
						}
					}
					
					ksort($sOptions);
					$this->sockopt .= implode('', $sOptions);
					$this->sockopt .= '</div>';
				}
			}
			if($itemType == 1){	
				if($this->socket[1] != $this->no_socket || $this->socket[2] != $this->no_socket || $this->socket[3] != $this->no_socket || $this->socket[4] != $this->no_socket || $this->socket[5] != $this->no_socket){
					for($i = 1; $i <= 5; $i++){
						if(in_array($this->socket[$i], [6, 7, 8, 9, 10]) && in_array($i, [1, 2, 3]) && MU_VERSION >= 5){
							$this->exe_options .= '<div class="item_light_blue item_size_12 item_font_family">' . $this->findExeOption($this->socket[$i]) . '</div>';
						}
					}
				}
				$kindA = $this->isMasteryItem();
				if($kindA != false && ($this->socket[5] != $this->no_socket && $this->socket[5] != $this->empty_socket)){
					if($kindA == 15 || $kindA == 18){
						$this->is_socket_exe = true;
						$this->sockopt = '<div class="item_socket item_size_12 item_font_family">'. __('Mastery Bonus Options') .'</div><div class="item_light_blue item_size_12 item_font_family">'.__('Damage Decrease').' '.(25*$this->socket[5]).'</div>';
					}
					if($kindA == 14){
						$values = [1 => 10, 2 => 25, 3 => 40];
						$this->sockopt = '<div class="item_socket item_size_12 item_font_family">'. __('Mastery Bonus Options') .'</div><div class="item_light_blue item_size_12 item_font_family">'.__('Increase all stats').' +'.$values[$this->socket[5]].'</div>';
					}
				}
			}
		}
		
		
		public function isMasteryItem(){
			return $this->item_data['kindA'] ?? false;
		}
		
		public function realSocketId($id, $isNewSocket = false){
			if(($id == 254 || $id == 255) && !$isNewSocket)
				return $id;
			if($id > 37){
				return ($id % 50);
			}
			return $id;
		}
		
		
		public function seedsIndex($id_socket_level_20 = 1){
			$seed_index = [];
			$ancient = $this->ancient;
			$exe = $this->exe;
			
			if($id_socket_level_20 == 1){
				if($ancient >= 192){
					$index = $this->realSocketId($this->socket[1]);
					$seed_index[1] = $index + 4;
					$ancient -= 192;
				}
				else{
					if($ancient >= 128){
						$index = $this->realSocketId($this->socket[1]);
						$seed_index[1] = $index + 4;
						$ancient -= 128;
					}
					else{
						if($ancient >= 64){
							$index = $this->realSocketId($this->socket[1]);
							$seed_index[1] = $index + 4;
							$ancient -= 64;
						}
					}
				}
				if($ancient >= 48){
					$index = $this->realSocketId($this->socket[2]);
					$seed_index[2] = $index + 4;
					$ancient -= 48;
				}
				else{
					if($ancient >= 32){
						$index = $this->realSocketId($this->socket[2]);
						$seed_index[2] = $index + 4;
						$ancient -= 32;
					}
					else{
						if($ancient >= 16){
							$index = $this->realSocketId($this->socket[2]);
							$seed_index[2] = $index + 4;
							$ancient -= 16;
						}
					}
				}
				if($ancient >= 12){
					$index = $this->realSocketId($this->socket[3]);
					$seed_index[3] = $index + 4;
					$ancient -= 12;
				}
				else{
					if($ancient >= 8){
						$index = $this->realSocketId($this->socket[3]);
						$seed_index[3] = $index + 4;
						$ancient -= 8;
					}
					else{
						if($ancient >= 4){
							$index = $this->realSocketId($this->socket[3]);
							$seed_index[3] = $index + 4;
							$ancient -= 4;
						}
					}
				}
				if($ancient >= 3){
					$index = $this->realSocketId($this->socket[4]);
					$seed_index[4] = $index + 4;
					$ancient -= 3;
				}
				else{
					if($ancient >= 2){
						$index = $this->realSocketId($this->socket[4]);
						$seed_index[4] = $index + 4;
						$ancient -= 2;
					}
					else{
						if($ancient >= 1){
							$index = $this->realSocketId($this->socket[4]);
							$seed_index[4] = $index + 4;
							$ancient -= 1;
						}
					}
				}
				
				$option = $this->getOption();
				$addon = ($option > 3) ? 64 : 0;

				if($exe >= (48+$addon)){
					$index = $this->realSocketId($this->socket[5]);
					$seed_index[5] = $index + 4;
					$exe -= 48;
				}
				else{
					if($exe >= (32+$addon)){
						$index = $this->realSocketId($this->socket[5]);
						$seed_index[5] = $index + 4;
						$exe -= 32;
					}
					else{
						if($exe >= (16+$addon)){
							$index = $this->realSocketId($this->socket[5]);
							$seed_index[5] = $index + 4;
							$exe -= 16;
						}
					}
				}
			}
			else{
				if($ancient >= 64){
					$index = $this->realSocketId($this->socket[1]);
					$seed_index[1] = $index + 4;
					$ancient -= 64;
				}
				if($ancient >= 16){
					$index = $this->realSocketId($this->socket[2]);
					$seed_index[2] = $index + 4;
					$ancient -= 16;
				}
				if($ancient >= 4){
					$index = $this->realSocketId($this->socket[3]);
					$seed_index[3] = $index + 4;
					$ancient -= 4;
				}
				if($ancient >= 1){
					$index = $this->realSocketId($this->socket[4]);
					$seed_index[4] = $index + 4;
					$ancient -= 1;
				}
				$option = $this->getOption();
				$addon = ($option > 3) ? 64 : 0;
					
				if($exe >= (16+$addon)){
					$index = $this->realSocketId($this->socket[5]);
					$seed_index[5] = $index + 4;
					$exe -= 16;
				}
			}
			return $seed_index;
		}
		
		
		public function findSocketValue($id, $isNewSocket = false){
			if(($id == 254 || $id == 255) && !$isNewSocket)
				return 0;
			if($id > 37){
				$real_id = ($id % 50);
				return (($id - $real_id) / 50) + 1;
			} 
			else{
				return 1;
			}
		}
		
        public function addSocketBonusType($val, $type){
            switch($type){
                default:
                    return '+' . $val;
                    break;
                case 2:
                    return $val . '%';
                    break;
            }
        }
		
		private function socketElementType(){
            $element_type = 1;
            if(in_array($this->id, [60, 100, 106, 112, 118, 124])){
                $element_type = 1;
            }
            if(in_array($this->id, [61, 101, 107, 113, 119, 125])){
                $element_type = 2;
            }
            if(in_array($this->id, [62, 102, 108, 114, 120, 126])){
                $element_type = 3;
            }
            if(in_array($this->id, [63, 103, 109, 115, 121, 127])){
                $element_type = 4;
            }
            if(in_array($this->id, [64, 104, 110, 116, 122, 128])){
                $element_type = 5;
            }
            if(in_array($this->id, [65, 105, 111, 117, 123, 129])){
                $element_type = 6;
            }
            return $element_type;
        }
		
		public function socketElementTypeName($type, $value){
            $name = '';
            if($type == 1){
                $name .= __('Fire') . ' (' . $value . ')';
            }
            if($type == 2){
                $name .= __('Water') . ' (' . $value . ')';
            }
            if($type == 3){
                $name .= __('Ice') . ' (' . $value . ')';
            }
            if($type == 4){
                $name .= __('Wind') . ' (' . $value . ')';
            }
            if($type == 5){
                $name .= __('Lightning') . ' (' . $value . ')';
            }
            if($type == 6){
                $name .= __('Earth') . ' (' . $value . ')';
            }
            return $name;
        }

        private function setSocketData(){
            static $data = [];
            if(empty($data))
                $data = $this->serverfile->socket_item(MU_VERSION)->get('socket_item');
            $this->socket_data = $data;
        }

        public function getSocketData(){
            if(!is_array($this->socket_data))
                $this->setSocketData();
            return $this->socket_data;
        }
		
		public function getAncient(){
			if($this->isSocketItem() != 2){
				if($this->ancient > 0){
					if(!in_array($this->ancient, [5,6,20])){
						$stamina = 10;
					} else{
						$stamina = 5;
					}
					if($this->type < 5){
						$this->stamina = '<div class="item_light_blue item_size_12 item_font_family">' . __('Increase Strength') . ' +' . $stamina . '</div>';
					} else{
						$this->stamina = '<div class="item_light_blue item_size_12 item_font_family">' . __('Increase Stamina') . ' +' . $stamina . '</div>';
					}
					$options = $this->ancientOptions();
					if($options != false){
						$this->ancopt = '<div class="item_yellow">'.__('Set Item Option Info').'</div><br /><div class="item_grey">';
						$this->ancopt .= $options . '<br />';
						$this->ancopt .= '</div>';
					}
				}
			}
        }
		
		
		public function isAncientItem($id, $cat){
			$set_type = $this->serverfile->item_set_type()->get('item_set_type');
			if(is_array($set_type)){
				if(array_key_exists($cat, $set_type)){
					if(array_key_exists($id, $set_type[$cat])){
						return $set_type[$cat][$id];
					}
				}
			}
			return false;
		}
		
		private function ancientOptions(){
            $set_type = $this->serverfile->item_set_type()->get('item_set_type');
            if(is_array($set_type)){
                $this->set_options = $this->serverfile->item_set_option()->get('item_set_option');
                $this->set_options_text = $this->serverfile->item_set_option_text()->get('item_set_option_text');
                if(array_key_exists($this->type, $set_type)){
					
                    if(array_key_exists($this->id, $set_type[$this->type])){
						if($this->ancient == 5 || $this->ancient == 9){
								$set = 'set';
								if(MU_VERSION < 2){
									$set = 'typeA';
								}
						}
						if($this->ancient == 6 || $this->ancient == 10){
								$set = 'typeA';
								if(MU_VERSION < 2){
									$set = 'set2';
								}
								if($set_type[$this->type][$this->id][$set] == 0){
										$set = 'set';
										if(MU_VERSION < 2){
											$set = 'typeA';
										}
								}
						}
						if($this->ancient == 20|| $this->ancient == 24){
								$set = 'set2';
								if(MU_VERSION < 2){
									$set = 'typeB';
								}
								if($set_type[$this->type][$this->id][$set] == 0){
										$set = 'typeA';
										if(MU_VERSION < 2){
											$set = 'set2';
										}
								}
						}
                        
   
                        return $this->findAncientOption($set_type[$this->type][$this->id][$set]) ?? '';
                    }
                }
            }
            return false;
        }
		
		private function findAncientOption($set){
			$options = '';
            if(isset($this->set_options[$set])){
				$this->anc_prefix = $this->set_options[$set]['name'];
				$options .= '<div class="item_light_green item_size_12 item_font_family">'.__('2Set Effect').'</div>';
				if($this->set_options[$set]['opt_1_1'] != -1){
					$options .= $this->findAncientOptionText($this->set_options[$set]['opt_1_1'], $this->set_options[$set]['opt_1_1_val']);
				}
				if($this->set_options[$set]['opt_2_1'] != -1){
					$options .= $this->findAncientOptionText($this->set_options[$set]['opt_2_1'], $this->set_options[$set]['opt_2_1_val']);
				}
				$options .= '<div class="item_light_green item_size_12 item_font_family">'.__('3Set Effect').'</div>';
				if($this->set_options[$set]['opt_1_2'] != -1){
					$options .= $this->findAncientOptionText($this->set_options[$set]['opt_1_2'], $this->set_options[$set]['opt_1_2_val']);
				}
				if($this->set_options[$set]['opt_2_2'] != -1){
					$options .= $this->findAncientOptionText($this->set_options[$set]['opt_2_2'], $this->set_options[$set]['opt_2_2_val']);
				}
				$options .= '<div class="item_light_green item_size_12 item_font_family">'.__('4Set Effect').'</div>';
				if($this->set_options[$set]['opt_1_3'] != -1){
					$options .= $this->findAncientOptionText($this->set_options[$set]['opt_1_3'], $this->set_options[$set]['opt_1_3_val']);
				}
				if($this->set_options[$set]['opt_2_3'] != -1){
					$options .= $this->findAncientOptionText($this->set_options[$set]['opt_2_3'], $this->set_options[$set]['opt_2_3_val']);
				}
				if($this->set_options[$set]['opt_1_4'] != -1){
					$options .= $this->findAncientOptionText($this->set_options[$set]['opt_1_4'], $this->set_options[$set]['opt_1_4_val']);
				}
				if($this->set_options[$set]['opt_2_4'] != -1){
					$options .= $this->findAncientOptionText($this->set_options[$set]['opt_2_4'], $this->set_options[$set]['opt_2_4_val']);
				}
				if($this->set_options[$set]['opt_1_5'] != -1){
					$options .= $this->findAncientOptionText($this->set_options[$set]['opt_1_5'], $this->set_options[$set]['opt_1_5_val']);
				}
				if($this->set_options[$set]['opt_2_5'] != -1){
					$options .= $this->findAncientOptionText($this->set_options[$set]['opt_2_5'], $this->set_options[$set]['opt_2_5_val']);
				}
				if($this->set_options[$set]['opt_1_6'] != -1){
					$options .= $this->findAncientOptionText($this->set_options[$set]['opt_1_6'], $this->set_options[$set]['opt_1_6_val']);
				}
				if($this->set_options[$set]['opt_2_6'] != -1){
					$options .= $this->findAncientOptionText($this->set_options[$set]['opt_2_6'], $this->set_options[$set]['opt_2_6_val']);
				}
				if($this->set_options[$set]['fopt_1'] != -1){
					$options .= $this->findAncientOptionText($this->set_options[$set]['fopt_1'], $this->set_options[$set]['fopt_val1']);
				}
				if($this->set_options[$set]['fopt_2'] != -1){
					$options .= $this->findAncientOptionText($this->set_options[$set]['fopt_2'], $this->set_options[$set]['fopt_val2']);
				}
				if($this->set_options[$set]['fopt_3'] != -1){
					$options .= $this->findAncientOptionText($this->set_options[$set]['fopt_3'], $this->set_options[$set]['fopt_val3']);
				}
				if($this->set_options[$set]['fopt_4'] != -1){
					$options .= $this->findAncientOptionText($this->set_options[$set]['fopt_4'], $this->set_options[$set]['fopt_val4']);
				}
				if($this->set_options[$set]['fopt_5'] != -1){
					$options .= $this->findAncientOptionText($this->set_options[$set]['fopt_5'], $this->set_options[$set]['fopt_val5']);
				}
				if($this->set_options[$set]['fopt_6'] != -1){
					$options .= $this->findAncientOptionText($this->set_options[$set]['fopt_6'], $this->set_options[$set]['fopt_val6']);
				}
				if($this->set_options[$set]['fopt_7'] != -1){
					$options .= $this->findAncientOptionText($this->set_options[$set]['fopt_7'], $this->set_options[$set]['fopt_val7']);
				}
			}
			return $options;
        }
		
		private function findAncientOptionText($index, $val){
            foreach($this->set_options_text AS $key => $value){
                if($value[1] == $index){
                    $sign = '';
                    if($value[3] == 2){
                        $sign = '%';
                    }
                    return str_replace('\'', '&#39;', $value[2]) . ' +' . $val . $sign . '<br />';
                    break;
                }
            }
        }

        public function getX(){
			
            if(is_array($this->item_data) && array_key_exists('x', $this->item_data)){
                return $this->item_data['x'];
            }
            return 1;
        }

        public function getY(){
            if(is_array($this->item_data) && array_key_exists('y', $this->item_data)){
                return $this->item_data['y'];
            }
            return 1;
        }

        public function speed(){
            if(is_array($this->item_data) && array_key_exists('attspeed', $this->item_data)){
                return $this->item_data['attspeed'];
            }
            return 0;
        }
		
		public function levelRequired(){
            if(isset($this->item_data['lvlreq']) && $this->item_data['lvlreq'] != 0){
                $level = (int)substr($this->getLevel(), 1);
                if($this->index >= $this->itemIndex(0, 0) && $this->index < $this->itemIndex(12, 0)){
                    $this->item_data['lvlreq'] = $this->item_data['lvlreq'];
                } else if(($this->index >= $this->itemIndex(12, 3) && $this->index <= $this->itemIndex(12, 6)) || $this->index == $this->itemIndex(12, 42)){
                    $this->item_data['lvlreq'] = $this->item_data['lvlreq'] + ($level * 5);
                } else if(($this->index >= $this->itemIndex(12, 7) && $this->index <= $this->itemIndex(12, 24) && $this->index != $this->itemIndex(12, 15)) || ($this->index >= $this->itemIndex(12, 44) && $this->index <= $this->itemIndex(12, 48))){
                    $this->item_data['lvlreq'] = $this->item_data['lvlreq'];
                } else if(($this->index >= $this->itemIndex(12, 36) && $this->index <= $this->itemIndex(12, 40)) || $this->index == $this->itemIndex(12, 43) || $this->index == $this->itemIndex(12, 50)){
                    $this->item_data['lvlreq'] = $this->item_data['lvlreq'];
                } else if($this->index >= $this->itemIndex(12, 130) && $this->index <= $this->itemIndex(12, 135)){
                    $this->item_data['lvlreq'] = $this->item_data['lvlreq'];
                } else if($this->index >= $this->itemIndex(12, 262) && $this->index <= $this->itemIndex(12, 265)){
                    $this->item_data['lvlreq'] = $this->item_data['lvlreq'] + ($level * 4);
                } else if($this->index >= $this->itemIndex(12, 266) && $this->index <= $this->itemIndex(12, 267)){
                    $this->item_data['lvlreq'] = $this->item_data['lvlreq'];
                } else if($this->index == $this->itemIndex(13, 4)){
                    $this->item_data['lvlreq'] = 218 + (1 * 2);
                } else{
                    $this->item_data['lvlreq'] = $this->item_data['lvlreq'] + ($level * 4);
                }
            }
            if($this->index == $this->itemIndex(13, 10)){
                if($level <= 2){
                    $this->item_data['lvlreq'] = 20;
                } else{
                    $this->item_data['lvlreq'] = 50;
                }
            }
            if($this->exe != 0 && (isset($this->item_data['lvlreq']) && $this->item_data['lvlreq'] > 0)){
                if($this->index <= $this->itemIndex(12, 0)){
                    $this->item_data['lvlreq'] += 20;
                }
            }
            return $this->item_data['lvlreq'] ?? 0;
        }
		
		public function getClass(){
            $class = ['sm' => 0, 'bk' => 0, 'me' => 0, 'mg' => 0, 'dl' => 0, 'bs' => 0, 'rf' => 0, 'gl' => 0, 'rw' => 0, 'sl' => 0, 'gc' => 0, 'km' => 0, 'lm' => 0, 'ik' => 0];
            if(array_key_exists('dw/sm', $this->item_data)){
                if(in_array($this->item_data['dw/sm'], [1,2,3,4,5])){
                    $class['sm'] = 1;
                }
            }
            if(array_key_exists('dk/bk', $this->item_data)){
                if(in_array($this->item_data['dk/bk'], [1,2,3,4,5])){
                    $class['bk'] = 1;
                }
            }
            if(array_key_exists('elf/me', $this->item_data)){
                if(in_array($this->item_data['elf/me'], [1,2,3,4,5])){
                    $class['me'] = 1;
                }
            }
            if(array_key_exists('mg', $this->item_data)){
                if(in_array($this->item_data['mg'], [1,2,3,4,5])){
                    $class['mg'] = 1;
                }
            }
            if(array_key_exists('dl', $this->item_data)){
                if(in_array($this->item_data['dl'], [1,2,3,4,5])){
                    $class['dl'] = 1;
                }
            }
            if(array_key_exists('sum', $this->item_data)){
                if(in_array($this->item_data['sum'], [1,2,3,4,5])){
                    $class['bs'] = 1;
                }
            }
            if(array_key_exists('rf', $this->item_data)){
                if(in_array($this->item_data['rf'], [1,2,3,4,5])){
                    $class['rf'] = 1;
                }
            }
            if(array_key_exists('gl', $this->item_data)){
                if(in_array($this->item_data['gl'], [1,2,3,4,5])){
                    $class['gl'] = 1;
                }
            }
            if(array_key_exists('rw', $this->item_data)){
                if(in_array($this->item_data['rw'], [1,2,3,4,5])){
                    $class['rw'] = 1;
                }
            }
			if(array_key_exists('sl', $this->item_data)){
				if(in_array($this->item_data['sl'], [1,2,3,4,5])){
					$class['sl'] = 1;
				}
			}
			if(array_key_exists('gc', $this->item_data)){
				if(in_array($this->item_data['gc'], [1,2,3,4,5])){
					$class['gc'] = 1;
				}
			}
			if(array_key_exists('km', $this->item_data)){
				if(in_array($this->item_data['km'], [1,2,3,4,5])){
					$class['km'] = 1;
				}
			}
			if(array_key_exists('lm', $this->item_data)){
				if(in_array($this->item_data['lm'], [1,2,3,4,5])){
					$class['lm'] = 1;
				}
			}
			if(array_key_exists('ik', $this->item_data)){
				if(in_array($this->item_data['ik'], [1,2,3,4,5])){
					$class['ik'] = 1;
				}
			}
            return $class;
        }
		
		public function canEquip($short = false){
            if(is_array($this->item_data)){
				if(array_key_exists('dw/sm', $this->item_data)){
					if($this->item_data['dw/sm'] == 1){
						$this->item_for .= '0,1,2,7,15,';
					}
					if($this->item_data['dw/sm'] == 2){
						$this->item_for .= '1,2,7,15,';
					}
					if($this->item_data['dw/sm'] == 3){
						$this->item_for .= '2,7,15,';
					}
					if($this->item_data['dw/sm'] == 4){
						$this->item_for .= '7,15,';
					}
					if($this->item_data['dw/sm'] == 5){
						$this->item_for .= '15,';
					}
				}
				if(array_key_exists('dk/bk', $this->item_data)){
					if($this->item_data['dk/bk'] == 1){
						$this->item_for .= '16,17,18,23,31,';
					}
					if($this->item_data['dk/bk'] == 2){
						$this->item_for .= '17,18,23,31,';
					}
					if($this->item_data['dk/bk'] == 3){
						$this->item_for .= '18,23,31,';
					}
					if($this->item_data['dk/bk'] == 4){
						$this->item_for .= '23,31,';
					}
					if($this->item_data['dk/bk'] == 5){
						$this->item_for .= '31,';
					}
				}
				
				if(array_key_exists('elf/me', $this->item_data)){
					if($this->item_data['elf/me'] == 1){
						$this->item_for .= '32,33,34,39,47,';
					}
					if($this->item_data['elf/me'] == 2){
						$this->item_for .= '33,34,39,47,';
					}
					if($this->item_data['elf/me'] == 3){
						$this->item_for .= '34,39,47,';
					}
					if($this->item_data['elf/me'] == 4){
						$this->item_for .= '39,47,';
					}
					if($this->item_data['elf/me'] == 5){
						$this->item_for .= '47,';
					}
				}
				if(array_key_exists('mg', $this->item_data)){
					if($this->item_data['mg'] == 1){
						$this->item_for .= '48,49,51,62,';
					}
					if($this->item_data['mg'] == 2){
						$this->item_for .= '49,51,62,';
					}
					if($this->item_data['mg'] == 3){
						$this->item_for .= '51,62,';
					}
					if($this->item_data['mg'] == 4){
						$this->item_for .= '62,';
					}
				}
				if(array_key_exists('dl', $this->item_data)){
					if($this->item_data['dl'] == 1){
						$this->item_for .= '64,65,67,78,';
					}
					if($this->item_data['dl'] == 2){
						$this->item_for .= '65,67,78,';
					}
					if($this->item_data['dl'] == 3){
						$this->item_for .= '67,78,';
					}
					if($this->item_data['dl'] == 4){
						$this->item_for .= '78,';
					}
				}
				if(array_key_exists('sum', $this->item_data)){
					if($this->item_data['sum'] == 1){
						$this->item_for .= '80,81,82,84,95,';
					}
					if($this->item_data['sum'] == 2){
						$this->item_for .= '81,82,84,95,';
					}
					if($this->item_data['sum'] == 3){
						$this->item_for .= '82,84,95,';
					}
					if($this->item_data['sum'] == 4){
						$this->item_for .= '84,95,';
					}
					if($this->item_data['sum'] == 5){
						$this->item_for .= '95,';
					}
				}
				if(array_key_exists('rf', $this->item_data)){
					if($this->item_data['rf'] == 1){
						$this->item_for .= '96,98,99,110,';
					}
					if($this->item_data['rf'] == 2){
						$this->item_for .= '98,99,110,';
					}
					if($this->item_data['rf'] == 3){
						$this->item_for .= '99,110,';
					}
					if($this->item_data['rf'] == 4){
						$this->item_for .= '110,';
					}
				}
				if(defined('MU_VERSION') && MU_VERSION >= 5){
					if(array_key_exists('gl', $this->item_data)){
						if($this->item_data['gl'] == 1){
							$this->item_for .= '112,114,118,126,';
						}
						if($this->item_data['gl'] == 2){
							$this->item_for .= '114,118,126,';
						}
						if($this->item_data['gl'] == 3){
							$this->item_for .= '118,126,';
						}
						if($this->item_data['gl'] == 4){
							$this->item_for .= '126,';
						}
					}
				}
				if(defined('MU_VERSION') && MU_VERSION >= 9){
					if(array_key_exists('rw', $this->item_data)){
						if($this->item_data['rw'] == 1){
							$this->item_for .= '128,129,131,135,143,';
						}
						if($this->item_data['rw'] == 2){
							$this->item_for .= '129,131,135,143,';
						}
						if($this->item_data['rw'] == 3){
							$this->item_for .= '131,135,143,';
						}
						if($this->item_data['rw'] == 4){
							$this->item_for .= '135,143,';
						}
						if($this->item_data['rw'] == 5){
							$this->item_for .= '143,';
						}
					}
				}
				if(defined('MU_VERSION') && MU_VERSION >= 10){
					if(array_key_exists('sl', $this->item_data)){
						if($this->item_data['sl'] == 1){
							$this->item_for .= '144,145,147,151,159,';
						}
						if($this->item_data['sl'] == 2){
							$this->item_for .= '145,147,151,159,';
						}
						if($this->item_data['sl'] == 3){
							$this->item_for .= '147,151,159,';
						}
						if($this->item_data['sl'] == 4){
							$this->item_for .= '151,159,';
						}
						if($this->item_data['sl'] == 5){
							$this->item_for .= '159,';
						}
					}
				}
				if(defined('MU_VERSION') && MU_VERSION >= 11){
					if(array_key_exists('gc', $this->item_data)){
						if($this->item_data['gc'] == 1){
							$this->item_for .= '160,161,163,167,175,';
						}
						if($this->item_data['gc'] == 2){
							$this->item_for .= '161,163,167,175,';
						}
						if($this->item_data['gc'] == 3){
							$this->item_for .= '163,167,175,';
						}
						if($this->item_data['gc'] == 4){
							$this->item_for .= '167,175,';
						}
						if($this->item_data['gc'] == 5){
							$this->item_for .= '175,';
						}
					}
				}
				if(defined('MU_VERSION') && MU_VERSION >= 12){
					if(array_key_exists('km', $this->item_data)){
						if($this->item_data['km'] == 1){
							$this->item_for .= '176,177,178,179,191,';
						}
						if($this->item_data['km'] == 2){
							$this->item_for .= '177,178,179,191,';
						}
						if($this->item_data['km'] == 3){
							$this->item_for .= '178,179,191,';
						}
						if($this->item_data['km'] == 4){
							$this->item_for .= '179,191,';
						}
						if($this->item_data['km'] == 5){
							$this->item_for .= '191,';
						}
					}
					if(array_key_exists('lm', $this->item_data)){
						if($this->item_data['lm'] == 1){
							$this->item_for .= '192,193,194,195,207,';
						}
						if($this->item_data['lm'] == 2){
							$this->item_for .= '193,194,195,207,';
						}
						if($this->item_data['lm'] == 3){
							$this->item_for .= '194,195,207,';
						}
						if($this->item_data['lm'] == 4){
							$this->item_for .= '195,207,';
						}
						if($this->item_data['lm'] == 5){
							$this->item_for .= '207,';
						}
					}
				}
				if(defined('MU_VERSION') && MU_VERSION >= 13){
					if(array_key_exists('ik', $this->item_data)){
						if($this->item_data['ik'] == 1){
							$this->item_for .= '208,209,211,215,223,';
						}
						if($this->item_data['ik'] == 2){
							$this->item_for .= '209,211,215,223,';
						}
						if($this->item_data['ik'] == 3){
							$this->item_for .= '211,215,223,';
						}
						if($this->item_data['ik'] == 4){
							$this->item_for .= '215,223,';
						}
						if($this->item_data['ik'] == 5){
							$this->item_for .= '223,';
						}
					}
				}
			}
            if($this->item_for != ''){
                $gl = '';
                $rw = '';
				$sl = '';
				$gc = '';
				$km = '';
				$lm = '';
				$ik = '';
                if(defined('MU_VERSION') && MU_VERSION >= 5){
                    $gl = '112,114,118,126,';
                }
                if(defined('MU_VERSION') && MU_VERSION >= 9){
                    $rw = '128,129,131,135,143,';
                }
				if(defined('MU_VERSION') && MU_VERSION >= 10){
					$sl = '144,145,147,151,159,';
				}
				if(defined('MU_VERSION') && MU_VERSION >= 11){
					$gc = '160,161,163,167,175,';
				}
				if(defined('MU_VERSION') && MU_VERSION >= 12){
					$km = '176,177,178,179,191,';
					$lm = '192,193,194,195,207,';
				}
				if(defined('MU_VERSION') && MU_VERSION >= 13){
					$ik = '208,209,211,215,223,';
				}
				
				$compare = preg_replace('/,$/', '', preg_replace('/[,,]/', ',', '0,1,2,7,15,16,17,18,23,31,32,33,34,39,47,48,49,51,62,64,65,67,78,80,81,82,84,95,96,98,99,110,' . $gl . $rw . $sl . $gc . $km . $lm . $ik));
                $this->item_for = preg_replace('/,$/', '', preg_replace('/[,,]/', ',', $this->item_for));
				if($this->item_for == $compare){
					if($short){
						return __('Can be used by all classes');
					}
					else{
						$this->class = '<div class="item_white item_dark_red_background">'.__('Can be used by all classes').'</div>';
					}
                } 
				else{
                    $item_for = (strstr($this->item_for, ',')) ? explode(',', $this->item_for) : [$this->item_for];
					$this->item_for = '';

                    if(in_array(0, $item_for))
						$item_for = array_diff($item_for, [1, 2, 7, 15]);
					if(in_array(1, $item_for))
						$item_for = array_diff($item_for, [0, 2, 7, 15]);
					if(in_array(2, $item_for))
						$item_for = array_diff($item_for, [0, 1, 7, 15]);
					if(in_array(7, $item_for))
						$item_for = array_diff($item_for, [0, 1, 2, 15]);
					if(in_array(15, $item_for))
						$item_for = array_diff($item_for, [0, 1, 2, 7]);		
					if(in_array(16, $item_for))
						$item_for = array_diff($item_for, [17, 18, 23, 31]);
					if(in_array(17, $item_for))
						$item_for = array_diff($item_for, [16, 18, 23, 31]);
					if(in_array(18, $item_for))
						$item_for = array_diff($item_for, [16, 17, 23, 31]);
					if(in_array(23, $item_for))
						$item_for = array_diff($item_for, [16, 17, 18, 31]);
					if(in_array(31, $item_for))
						$item_for = array_diff($item_for, [16, 17, 18, 23]);	
					if(in_array(32, $item_for))
						$item_for = array_diff($item_for, [33, 34, 39, 47]);
					if(in_array(33, $item_for))
						$item_for = array_diff($item_for, [32, 34, 39, 47]);
					if(in_array(34, $item_for))
						$item_for = array_diff($item_for, [32, 33, 39, 47]);
					if(in_array(39, $item_for))
						$item_for = array_diff($item_for, [32, 33, 34, 47]);
					if(in_array(47, $item_for))
						$item_for = array_diff($item_for, [32, 33, 34, 39]);		
					if(in_array(48, $item_for))
						$item_for = array_diff($item_for, [49, 51, 62]);
					if(in_array(49, $item_for))
						$item_for = array_diff($item_for, [48, 51, 62]);
					if(in_array(51, $item_for))
						$item_for = array_diff($item_for, [48, 49, 62]);
					if(in_array(62, $item_for))
						$item_for = array_diff($item_for, [48, 49, 51]);		
					if(in_array(64, $item_for))
						$item_for = array_diff($item_for, [65, 67, 78]);
					if(in_array(65, $item_for))
						$item_for = array_diff($item_for, [64, 67, 78]);
					if(in_array(67, $item_for))
						$item_for = array_diff($item_for, [64, 65, 78]);
					if(in_array(78, $item_for))
						$item_for = array_diff($item_for, [64, 65, 67]);
					if(in_array(80, $item_for))
						$item_for = array_diff($item_for, [81, 82, 84, 95]);
					if(in_array(81, $item_for))
						$item_for = array_diff($item_for, [80, 82, 84, 95]);
					if(in_array(82, $item_for))
						$item_for = array_diff($item_for, [80, 81, 84, 95]);
					if(in_array(84, $item_for))
						$item_for = array_diff($item_for, [80, 81, 82, 95]);
					if(in_array(95, $item_for))
						$item_for = array_diff($item_for, [80, 81, 82, 84]);	
					if(in_array(96, $item_for))
						$item_for = array_diff($item_for, [98, 99, 110]);
					if(in_array(98, $item_for))
						$item_for = array_diff($item_for, [96, 99, 110]);
					if(in_array(99, $item_for))
						$item_for = array_diff($item_for, [96, 98, 110]);
					if(in_array(110, $item_for))
						$item_for = array_diff($item_for, [96, 98, 99]);
					if(defined('MU_VERSION') && MU_VERSION >= 5){
						if(in_array(112, $item_for))
							$item_for = array_diff($item_for, [114, 118, 126]);
						if(in_array(114, $item_for))
							$item_for = array_diff($item_for, [112, 118, 126]);
						if(in_array(118, $item_for))
							$item_for = array_diff($item_for, [112, 114, 126]);
						if(in_array(126, $item_for))
							$item_for = array_diff($item_for, [112, 114, 118]);
					}	
					if(defined('MU_VERSION') && MU_VERSION >= 9){
						if(in_array(128, $item_for))
							$item_for = array_diff($item_for, [129, 131, 135, 143]);
						if(in_array(129, $item_for))
							$item_for = array_diff($item_for, [128, 131, 135, 143]);
						if(in_array(131, $item_for))
							$item_for = array_diff($item_for, [128, 129, 135, 143]);
						if(in_array(135, $item_for))
							$item_for = array_diff($item_for, [128, 129, 131, 143]);
						if(in_array(143, $item_for))
							$item_for = array_diff($item_for, [128, 129, 131, 135]);	
					}
					if(defined('MU_VERSION') && MU_VERSION >= 10){
						if(in_array(144, $item_for))
							$item_for = array_diff($item_for, [145, 147, 151, 159]);
						if(in_array(145, $item_for))
							$item_for = array_diff($item_for, [144, 147, 151, 159]);
						if(in_array(147, $item_for))
							$item_for = array_diff($item_for, [144, 145, 151, 159]);
						if(in_array(151, $item_for))
							$item_for = array_diff($item_for, [144, 145, 147, 159]);
						if(in_array(159, $item_for))
							$item_for = array_diff($item_for, [144, 145, 147, 151]);	
					}
					if(defined('MU_VERSION') && MU_VERSION >= 11){
						if(in_array(160, $item_for))
							$item_for = array_diff($item_for, [161, 163, 167, 175]);
						if(in_array(161, $item_for))
							$item_for = array_diff($item_for, [160, 163, 167, 175]);
						if(in_array(163, $item_for))
							$item_for = array_diff($item_for, [160, 161, 167, 175]);		
						if(in_array(167, $item_for))
							$item_for = array_diff($item_for, [160, 161, 163, 175]);
						if(in_array(175, $item_for))
							$item_for = array_diff($item_for, [160, 161, 163, 167]);		
					}
					if(defined('MU_VERSION') && MU_VERSION >= 12){
						if(in_array(176, $item_for))
							$item_for = array_diff($item_for, [177, 178, 179, 191]);
						if(in_array(177, $item_for))
							$item_for = array_diff($item_for, [176, 178, 179, 191]);
						if(in_array(178, $item_for))
							$item_for = array_diff($item_for, [176, 177, 179, 191]);		
						if(in_array(179, $item_for))
							$item_for = array_diff($item_for, [176, 177, 178, 191]);	
						if(in_array(191, $item_for))
							$item_for = array_diff($item_for, [176, 177, 178, 179]);

						if(in_array(192, $item_for))
							$item_for = array_diff($item_for, [193, 194, 195, 207]);
						if(in_array(193, $item_for))
							$item_for = array_diff($item_for, [192, 194, 195, 207]);
						if(in_array(194, $item_for))
							$item_for = array_diff($item_for, [192, 193, 195, 207]);		
						if(in_array(195, $item_for))
							$item_for = array_diff($item_for, [192, 193, 194, 207]);
						if(in_array(207, $item_for))
							$item_for = array_diff($item_for, [192, 193, 194, 195]);	
					}
					if(defined('MU_VERSION') && MU_VERSION >= 13){
						if(in_array(208, $item_for))
							$item_for = array_diff($item_for, [209, 211, 215, 223]);
						if(in_array(209, $item_for))
							$item_for = array_diff($item_for, [208, 211, 215, 223]);
						if(in_array(211, $item_for))
							$item_for = array_diff($item_for, [208, 209, 215, 223]);		
						if(in_array(215, $item_for))
							$item_for = array_diff($item_for, [208, 209, 211, 223]);
						if(in_array(223, $item_for))
							$item_for = array_diff($item_for, [208, 209, 211, 215]);
					}	
					if($short){
						$classes = '';
						foreach($item_for as $class_key => $class_code){
							$classes .= $this->website->get_char_class($class_code, true).', ';
						}
						return __('Can be equipped by ') . ' '. $classes;
					}
					else{
						foreach($item_for as $class_code){
							$this->class .= '<div class="item_white item_dark_red_background">'.__('Can be equipped by ') . __($this->website->get_char_class($class_code, false)) . '</div>';
						}
					}
                }
            }
        }

        private function isPentagramItem()
		{
			if(($this->index >= $this->itemIndex(12, 200) && $this->index <= $this->itemIndex(12, 220)) || ($this->index >= $this->itemIndex(12, 306) && $this->index <= $this->itemIndex(12, 308))){
				return true;
			}
			return false;
		}
		
		private function isErrtelItem()
		{
			if(($this->index >= $this->itemIndex(12, 221) && $this->index <= $this->itemIndex(12, 261))){
				return true;
			}
			return false;
		}

        private function chaosItem(){
            if($this->index == $this->itemIndex(2, 6)){
                return 15;
            } else if($this->index == $this->itemIndex(5, 7)){
                return 25;
            }
            if($this->index == $this->itemIndex(4, 6)){
                return 30;
            }
            return 0;
        }
		
		private function additionalValue()
		{
			if($this->exe > 0 || $this->ancient > 0){
				return 25;
			}
			return 0;
		}
		
		public function damage(){
            $level = (int)substr($this->getLevel(), 1);
            $chaos_item = $this->chaosItem();
            $min_damage = 0;
            $max_damage = 0;
            
            if(array_key_exists('mindmg', $this->item_data)){
                if($this->item_data['mindmg'] > 0){
                    $min_damage = $this->item_data['mindmg'];
					if(($this->exe != 0 || $this->ancient != 0)){
						if($chaos_item != 0){
                            $min_damage += $chaos_item;
                        } 
						else{
                            $min_damage += ($this->item_data['mindmg'] * 25 / ($level+$this->item_data['mindmg']) + 8);
                        }
					}
					
					if($this->ancient != 0){
						$min_damage += ($level+30) / 40 + 5;
					}
					
					if($this->isPentagramItem()){
                        $min_damage += ($level * 4);
                    }
					else{
						 $min_damage += ($level * 3);
					}
					
					if($level >= 10){
                        $min_damage += ($level - 8) * ($level - 9) / 2;
                    }
                }
            }
			if(array_key_exists('maxdmg', $this->item_data)){
                if($this->item_data['maxdmg'] > 0){
					$max_damage = $this->item_data['maxdmg'];
					if(($this->exe != 0 || $this->ancient != 0)){
						if($chaos_item != 0){
                            $max_damage += $chaos_item;
                        } 
						else{
                            $max_damage += ($this->item_data['mindmg'] * 25 / ($level+$this->item_data['mindmg']) + 8);
                        }
					}
					
					if($this->ancient != 0){
						$max_damage += ($level+30) / 40 + 5;
					}
					
					if($this->isPentagramItem()){
                        $max_damage += ($level * 4);
                    }
					else{
						 $max_damage += ($level * 3);
					}
					
					if($level >= 10){
                        $max_damage += ($level - 8) * ($level - 9) / 2;
                    }
                }
            }
            return round($min_damage) . '~' . round($max_damage);
        }
		
		private function increaseDamageWings(){
            $dmg = 0;
            $level = (int)substr($this->getLevel(), 1);
			
			if($this->index == $this->itemIndex(12, 130) || $this->index == $this->itemIndex(12, 135) || $this->index == $this->itemIndex(12, 278)){ // small wings
                $dmg = (20 + ($level * 2));
            } 
			if(
				$this->index == $this->itemIndex(12, 131) || 
				$this->index == $this->itemIndex(12, 132) || 
				$this->index == $this->itemIndex(12, 133) || 
				$this->index == $this->itemIndex(12, 134) || 
				$this->index == $this->itemIndex(12, 154) || 
				$this->index == $this->itemIndex(12, 172)){ // small wings
					$dmg = (12 + ($level * 2));
            } 
            if(
				($this->index >= $this->itemIndex(12, 0) && $this->index <= $this->itemIndex(12, 2)) || 
				$this->index == $this->itemIndex(12, 41) || 
				$this->index == $this->itemIndex(12, 155) || 
				$this->index == $this->itemIndex(12, 173)){ // 1st wings
					$dmg = (12 + ($level * 2));
            } 
			if(
				($this->index >= $this->itemIndex(12, 3) && $this->index <= $this->itemIndex(12, 6)) || 
				$this->index == $this->itemIndex(12, 42) || 
				$this->index == $this->itemIndex(12, 156) || 
				$this->index == $this->itemIndex(12, 157) || 
				$this->index == $this->itemIndex(12, 174) || 
				$this->index == $this->itemIndex(12, 175)){ // 2nd wings
					$dmg = (32 + ($level));
            } 
			if($this->index == $this->itemIndex(12, 30) || $this->index == $this->itemIndex(12, 49) || $this->index == $this->itemIndex(12, 269)){ //2rd capes
                $dmg = (20 + ($level * 2)); 
            } 
			if(
				($this->index >= $this->itemIndex(12, 36) && $this->index <= $this->itemIndex(12, 39)) || 
				$this->index == $this->itemIndex(12, 27) ||
				$this->index == $this->itemIndex(12, 40) || 	
				$this->index == $this->itemIndex(12, 43) || 
				$this->index == $this->itemIndex(12, 50) || 
				$this->index == $this->itemIndex(12, 158) || 
				$this->index == $this->itemIndex(12, 176) || 
				$this->index == $this->itemIndex(12, 268) || 
				$this->index == $this->itemIndex(12, 270) || 
				$this->index == $this->itemIndex(12, 434) ||
				$this->index == $this->itemIndex(12, 467) || 
				$this->index == $this->itemIndex(12, 472) || 
				$this->index == $this->itemIndex(12, 489)){ // 3rd wings
					$dmg = (39 + ($level * 2));
            } 
			if($this->index == $this->itemIndex(12, 262) || $this->index == $this->itemIndex(12, 279) || $this->index == $this->itemIndex(12, 284)){ //monster wings
                $dmg = 21 + $level;
            } 
			if($this->index == $this->itemIndex(12, 263) || $this->index == $this->itemIndex(12, 280) || $this->index == $this->itemIndex(12, 285)){ //monster wings
                $dmg = 33 + $level;
            } 
			if(
				$this->index == $this->itemIndex(12, 264) || 
				$this->index == $this->itemIndex(12, 265) || 
				$this->index == $this->itemIndex(12, 281) || 
				$this->index == $this->itemIndex(12, 282) || 
				$this->index == $this->itemIndex(12, 286) || 
				$this->index == $this->itemIndex(12, 287)){ //monster wings
					$dmg = 35 + $level;
            }
			if($this->index == $this->itemIndex(12, 266)){ // Wings of Conqueror
                $dmg = 71;
            } 
			if($this->index == $this->itemIndex(12, 267)){ // Wings of Angel And Devil
                $dmg = 60 + $level;
            } 
			if(in_array($this->index, [
				$this->itemIndex(12, 152), 
				$this->itemIndex(12, 160), 
				$this->itemIndex(12, 178),
				$this->itemIndex(12, 180), 
				$this->itemIndex(12, 181), 
				$this->itemIndex(12, 182), 
				$this->itemIndex(12, 183), 
				$this->itemIndex(12, 184), 
				$this->itemIndex(12, 185), 
				$this->itemIndex(12, 186), 
				$this->itemIndex(12, 187), 
				$this->itemIndex(12, 188), 
				$this->itemIndex(12, 189), 
				$this->itemIndex(12, 190), 
				$this->itemIndex(12, 191),
				$this->itemIndex(12, 192), 
				$this->itemIndex(12, 193),		
				$this->itemIndex(12, 414), 
				$this->itemIndex(12, 415), 
				$this->itemIndex(12, 416), 
				$this->itemIndex(12, 417), 
				$this->itemIndex(12, 418), 
				$this->itemIndex(12, 419), 
				$this->itemIndex(12, 420), 
				$this->itemIndex(12, 421), 
				$this->itemIndex(12, 469), 
				$this->itemIndex(12, 474), 
				$this->itemIndex(12, 490)
			])){ // 4th,5th wings
				$dmg = 55 + $level;
			}
			if($this->index == $this->itemIndex(12, 480)){ // Wings of power
                $dmg = 65;
            }
			
            return $dmg;
        }
		
		private function absorbDamageWings(){
            $dmg = 0;
            $level = (int)substr($this->getLevel(), 1);
			
			if($this->index == $this->itemIndex(12, 130) || $this->index == $this->itemIndex(12, 135) || $this->index == $this->itemIndex(12, 278)){ // small wings
                $dmg = (20 + ($level * 2));
            } 
			if(
				$this->index == $this->itemIndex(12, 131) || 
				$this->index == $this->itemIndex(12, 132) || 
				$this->index == $this->itemIndex(12, 133) || 
				$this->index == $this->itemIndex(12, 134) || 
				$this->index == $this->itemIndex(12, 154) || 
				$this->index == $this->itemIndex(12, 172)){ // small wings
					$dmg = (12 + ($level * 2));
            } 
            if(
				($this->index >= $this->itemIndex(12, 0) && $this->index <= $this->itemIndex(12, 2)) || 
				$this->index == $this->itemIndex(12, 41) || 
				$this->index == $this->itemIndex(12, 155) || 
				$this->index == $this->itemIndex(12, 173)){ // 1st wings
					$dmg = (12 + ($level * 2));
            } 
			if(
				($this->index >= $this->itemIndex(12, 3) && $this->index <= $this->itemIndex(12, 6)) || 
				$this->index == $this->itemIndex(12, 42) || 
				$this->index == $this->itemIndex(12, 156) || 
				$this->index == $this->itemIndex(12, 157) || 
				$this->index == $this->itemIndex(12, 174) || 
				$this->index == $this->itemIndex(12, 175)){ // 2nd wings
					$dmg = (25 + ($level));
            } 
			if($this->index == $this->itemIndex(12, 30) || $this->index == $this->itemIndex(12, 49) || $this->index == $this->itemIndex(12, 269)){ //2rd capes
                $dmg = (10 + ($level * 2)); 
            } 
			if($this->index == $this->itemIndex(12, 40) || $this->index == $this->itemIndex(12, 434)){ //3rd capes
                $dmg = (24 + ($level * 2)); 
            } 
			if(
				($this->index >= $this->itemIndex(12, 36) && $this->index <= $this->itemIndex(12, 39)) || 
				$this->index == $this->itemIndex(12, 27) || 
				$this->index == $this->itemIndex(12, 43) || 
				$this->index == $this->itemIndex(12, 50) || 
				$this->index == $this->itemIndex(12, 158) || 
				$this->index == $this->itemIndex(12, 176) || 
				$this->index == $this->itemIndex(12, 268) || 
				$this->index == $this->itemIndex(12, 270) || 
				$this->index == $this->itemIndex(12, 467) || 
				$this->index == $this->itemIndex(12, 472) || 
				$this->index == $this->itemIndex(12, 489)){ // 3rd wings
					$dmg = (39 + ($level * 2));
            } 
			if($this->index == $this->itemIndex(12, 262) || $this->index == $this->itemIndex(12, 279) || $this->index == $this->itemIndex(12, 284)){ //monster wings
                $dmg = (13 + ($level * 2));
            } 
			if($this->index == $this->itemIndex(12, 263) || $this->index == $this->itemIndex(12, 280) || $this->index == $this->itemIndex(12, 285)){ //monster wings
                $dmg = (30 + ($level * 2));
            } 
			if(
				$this->index == $this->itemIndex(12, 264) || 
				$this->index == $this->itemIndex(12, 265) || 
				$this->index == $this->itemIndex(12, 281) || 
				$this->index == $this->itemIndex(12, 282) || 
				$this->index == $this->itemIndex(12, 286) || 
				$this->index == $this->itemIndex(12, 287)){ //monster wings
					$dmg = (29 + ($level * 2));
            }
			if($this->index == $this->itemIndex(12, 266)){ // Wings of Conqueror
                $dmg = 71;
            } 
			if($this->index == $this->itemIndex(12, 267)){ // Wings of Angel And Devil
                $dmg = 60 + $level;
            } 
			if(in_array($this->index, [
				$this->itemIndex(12, 152), 
				$this->itemIndex(12, 160), 
				$this->itemIndex(12, 178), 
				$this->itemIndex(12, 180), 
				$this->itemIndex(12, 181), 
				$this->itemIndex(12, 182), 
				$this->itemIndex(12, 183), 
				$this->itemIndex(12, 184), 
				$this->itemIndex(12, 185), 
				$this->itemIndex(12, 186), 
				$this->itemIndex(12, 187), 
				$this->itemIndex(12, 188), 
				$this->itemIndex(12, 189), 
				$this->itemIndex(12, 190), 
				$this->itemIndex(12, 191),
				$this->itemIndex(12, 192), 
				$this->itemIndex(12, 193), 				
				$this->itemIndex(12, 414), 
				$this->itemIndex(12, 415), 
				$this->itemIndex(12, 416), 
				$this->itemIndex(12, 417), 
				$this->itemIndex(12, 418), 
				$this->itemIndex(12, 419), 
				$this->itemIndex(12, 420), 
				$this->itemIndex(12, 421), 
				$this->itemIndex(12, 469), 
				$this->itemIndex(12, 474), 
				$this->itemIndex(12, 490)
			])){ // 4th,5th wings
				if($this->index == $this->itemIndex(12, 418) || $this->index == $this->itemIndex(12, 182)){
					$dmg = (37 + ($level * 2));
				}
				else{
					$dmg = (43 + ($level * 2));
				}
			}
			if($this->index == $this->itemIndex(12, 480)){ // Wings of power
                $dmg = 65;
            }
			
            return $dmg;
        }
		
		private function wings4ThDamage(){
			$level = (int)substr($this->getLevel(), 1);
            return round(100+($level**1.2*11.8));
        }
		
		private function wings4ThDefense(){
			$level = (int)substr($this->getLevel(), 1);
            return round((140+($level**1.5*15)))+round((((($level-10)**1.3)*5)**1.6)); 
        }
		
		public function magicPower(){
			if(array_key_exists('magpower', $this->item_data)){
				 $this->item_data['magpow'] = $this->item_data['magpower'];
			}
            if(array_key_exists('magpow', $this->item_data)){
                $level = (int)substr($this->getLevel(), 1);
                $magic_power = $this->item_data['magpow'];
                if($this->exe > 0 || $this->ancient > 0){
                    $magic_power += 25;
                }
                $magic_power += $level * 3;
                if($level >= 10)
                    $magic_power += (($level - 9) * ($level - 8)) / 2;
                if($this->type == 2 && $this->id != 16 && $this->id >= 8)
                    $magic_power = ($magic_power / 2); 
				else
                    $magic_power = round((($magic_power / 2) + ($level * 2)));
                return $magic_power;
            }
            return 0;
        }
		
		public function defense(){
            if(array_key_exists('def', $this->item_data)){
                $def = $this->item_data['def'];
                $level = (int)substr($this->getLevel(), 1);
                $exe = $this->exe;
                $drop_level = (int)$this->item_data['lvldrop'];
                if($exe >= 64){
                    $exe -= 64;
                }
                $drop_level += $this->additionalValue();
                if($this->index == $this->itemIndex(13, 30) || $this->index == $this->itemIndex(12, 49) || $this->index == $this->itemIndex(12, 269)){ // Cloak of Lord, Cloak of Fighter, Cloak Of Limit
                    $def = 15;
                }
                if($def > 0){
                    if($this->index >= $this->itemIndex(6, 0) && $this->index < $this->itemIndex(7, 0)){ // Shields
                        $def += $level;
                        if($this->ancient != 0 && $drop_level != 0){
                            $def += (($def * 20) / $drop_level) + 2;
                        }
                    } else{
                        if($this->ancient != 0 && $this->item_data['lvldrop'] != 0 && $drop_level != 0){
                            $def += ((($def * 12) / (int)$this->item_data['lvldrop']) + ((int)$this->item_data['lvldrop'] / 5)) + 4;
                            $def += ((($def * 3) / $drop_level) + ($drop_level / 30)) + 2;
                        } else if($exe != 0 && $this->item_data['lvldrop'] != 0){
                            $def += ((($def * 12) / (int)$this->item_data['lvldrop']) + ((int)$this->item_data['lvldrop'] / 5)) + 4;
                        }
                        if(($this->index >= $this->itemIndex(12, 3) && $this->index <= $this->itemIndex(12, 6)) || $this->index == $this->itemIndex(12, 42) || $this->index == $this->itemIndex(13, 4)){ // 2nd Wings,Dark Horse
                            $def += $level * 2;
                        } else if(($this->index >= $this->itemIndex(12, 36) && $this->index <= $this->itemIndex(12, 40)) || $this->index == $this->itemIndex(12, 43) || $this->index == $this->itemIndex(12, 50) || $this->index == $this->itemIndex(12, 270) || $this->index == $this->itemIndex(12, 467)){ // 3rd Wings
                            $def += $level * 4;
                        } else if($this->index >= $this->itemIndex(12, 130) && $this->index <= $this->itemIndex(12, 135)){ // Mini Wings
                            $def += $level * 2;
                        } else if($this->index >= $this->itemIndex(12, 262) && $this->index <= $this->itemIndex(12, 265)){ // Monster Wings
                            $def += $level * 3;
                        } else if($this->index >= $this->itemIndex(12, 266) && $this->index <= $this->itemIndex(12, 267)){ // Special Wings
                            $def += $level * 3;
                        } else{
                            $def += $level * 3;
                        }
                        if($level >= 10){
                            $def += (($level - 9) * ($level - 8)) / 2;
                        }
                    }
                }
                return floor($def);
            }
            return 0;
        }
		
		public function successBlock(){
            if(array_key_exists('successblock', $this->item_data)){
                $level = (int)substr($this->getLevel(), 1);
                $success_block = $this->item_data['successblock'] + ($level * 3);
                if($level == 10)
                    $success_block += $level - 9;
                if($level == 11)
                    $success_block += $level - 8;
                if($level == 12)
                    $success_block += $level - 6;
                if($level == 13)
                    $success_block += $level - 3;
                if($level == 14)
                    $success_block += $level + 1;
                if($level == 15)
                    $success_block += $level + 6;
                if($this->exe > 0 && $this->ancient != 0){
                    $success_block += 30;
                } else if($this->exe <= 0 && $this->ancient != 0){
                    $success_block += 30;
                } else if($this->exe > 0 && $this->ancient == 0){
                    $success_block += 30;
                }
                return $success_block;
            }
            return 0;
        }

        public function iceRes(){
            if(array_key_exists('iceres', $this->item_data)){
                return $this->item_data['iceres'] + (int)substr($this->getLevel(), 1);
            }
			if(array_key_exists('res1', $this->item_data)){
                return $this->item_data['res1'] + (int)substr($this->getLevel(), 1);
            }
            return 0;
        }

        public function poisonRes(){
            if(array_key_exists('poisonres', $this->item_data)){
                return $this->item_data['poisonres'] + (int)substr($this->getLevel(), 1);
            }
			if(array_key_exists('res2', $this->item_data)){
                return $this->item_data['res2'] + (int)substr($this->getLevel(), 1);
            }
            return 0;
        }

        public function lightRes(){
            if(array_key_exists('lightres', $this->item_data)){
                return $this->item_data['lightres'] + (int)substr($this->getLevel(), 1);
            }
			if(array_key_exists('res3', $this->item_data)){
                return $this->item_data['res3'] + (int)substr($this->getLevel(), 1);
            }
            return 0;
        }

        public function fireRes(){
            if(array_key_exists('fireres', $this->item_data)){
                return $this->item_data['fireres'] + (int)substr($this->getLevel(), 1);
            }
			if(array_key_exists('res4', $this->item_data)){
                return $this->item_data['res4'] + (int)substr($this->getLevel(), 1);
            }
            return 0;
        }

        public function earthRes(){
            if(array_key_exists('earthres', $this->item_data)){
                return $this->item_data['earthres'] + (int)substr($this->getLevel(), 1);
            }
			if(array_key_exists('res5', $this->item_data)){
                return $this->item_data['res5'] + (int)substr($this->getLevel(), 1);
            }
            return 0;
        }

        public function windRes(){
            if(array_key_exists('windres', $this->item_data)){
                return $this->item_data['windres'] + (int)substr($this->getLevel(), 1);
            }
			if(array_key_exists('res6', $this->item_data)){
                return $this->item_data['res6'] + (int)substr($this->getLevel(), 1);
            }
            return 0;
        }

        public function waterRes(){
            if(array_key_exists('waterres', $this->item_data)){
                return $this->item_data['waterres'] + (int)substr($this->getLevel(), 1);
            }
			if(array_key_exists('res7', $this->item_data)){
                return $this->item_data['res7'] + (int)substr($this->getLevel(), 1);
            }
            return 0;
        }

        public function reqStr(){
            if(array_key_exists('strreq', $this->item_data)){
                if($this->item_data['strreq'] > 0){
                    return floor(((($this->item_data['strreq'] * (((int)substr($this->getLevel(), 1) * 3) + ((int)$this->item_data['lvldrop'] + $this->additionalValue()))) * 3) / 100) + 20);
                }
            }
            return 0;
        }

        public function reqAgi(){
            if(array_key_exists('agireq', $this->item_data)){
                if($this->item_data['agireq'] > 0){
                    return floor(((($this->item_data['agireq'] * (((int)substr($this->getLevel(), 1) * 3) + ((int)$this->item_data['lvldrop'] + $this->additionalValue()))) * 3) / 100) + 20);
                }
            }
            return 0;
        }
		
		public function reqVit(){
            if(array_key_exists('vitreq', $this->item_data)){
                if($this->item_data['vitreq'] > 0){
                    return floor(((($this->item_data['vitreq'] * (((int)substr($this->getLevel(), 1) * 3) + ((int)$this->item_data['lvldrop'] + $this->additionalValue()))) * 3) / 100) + 20);
                }
            }
            return 0;
        }

        public function reqEne(){
            if(array_key_exists('enereq', $this->item_data)){
                if($this->item_data['enereq'] > 0){
                    $multiplier = ($this->type != 5 && $this->item_data['slot'] != 1) ? 4 : 3;
                    return floor(((($this->item_data['enereq'] * (((int)substr($this->getLevel(), 1) * 3) + ((int)$this->item_data['lvldrop'] + $this->additionalValue()))) * $multiplier) / 100) + 20);
                }
            }
            return 0;
        }

        public function reqCom(){
            if(array_key_exists('cmdreq', $this->item_data)){
                if($this->item_data['cmdreq'] > 0){
                    if($this->index == $this->itemIndex(13, 5)){
                        return 185 + (1 * 15);
                    }
                    return floor(((($this->item_data['cmdreq'] * (((int)substr($this->getLevel(), 1) * 3) + ((int)$this->item_data['lvldrop'] + $this->additionalValue()))) * 3) / 100) + 20);
                }
            }
            return 0;
        }
		
		public function durability(){
            if(array_key_exists('dur', $this->item_data)){
                if($this->type == 5)
                    $dur = $this->item_data['magdur']; else
                    $dur = $this->item_data['dur'];
                $level = (int)substr($this->getLevel(), 1);
                if($level <= 4)
                    $dur = $dur + ($level * 1);
                if($level < 10 && $level > 4)
                    $dur = $dur + (($level - 2) * 2);
                if($level == 10)
                    $dur = $dur + (($level * 2) - 3);
                if($level == 11)
                    $dur = $dur + (($level * 2) - 1);
                if($level == 12)
                    $dur = $dur + (($level + 1) * 2);
                if($level == 13)
                    $dur = $dur + (($level + 3) * 2);
                if($level == 14)
                    $dur = $dur + (($level * 2) + 11);
                if($level == 15)
                    $dur = $dur + (($level * 2) + 17);
                if($this->exe > 0 && $this->ancient != 0){
                    $dur += 20;
                } else if($this->exe <= 0 && $this->ancient != 0){
                    $dur += 20;
                } else if($this->exe > 0 && $this->ancient == 0){
                    $dur += 15;
                }
                return $dur;
            }
            return 0;
        }
		
		public function realName($id = false, $type = false){
			
            if($this->setItemTooltip($id, $type)){
                if($this->item_tooltip['Unk3'] != -1){
                    $level = (int)substr($this->getLevel(), 1);
                    if($level > 0){
                        $this->getItemLevelTooltip();
                        foreach($this->item_level_tooltip AS $key => $value){
                            if($value[2] == $this->item_tooltip['Unk3'] + $level){
								$this->hasTooltipLvl = true;
                                return $value[3];
                            }
                        }
                    }
                }
            }
            return $this->item_data['name'];
        }

        public function getNameStyle($return = false, $limit_text = 50){
            $this->getName($return, $limit_text);
            return $this->name;
        }
		
		public function getName($return = false, $limit_text = 50){
            $this->name = '';
            $class = ($return) ? '' : 'item_white';
            $exe = $this->exe;
			$realName = str_replace('\'', '&#39;', $this->realName());
			$itemType = $this->isSocketItem();
			
            if($this->type == 12 && in_array($this->id, [60, 100, 106, 112, 118, 124, 61, 101, 107, 113, 118, 125, 62, 102, 108, 114, 119, 126, 63, 103, 109, 115, 120, 127, 64, 104, 110, 116, 121, 128, 65, 105, 111, 117, 122, 123, 129])){
                $level = '';
                if(in_array($this->id, [100, 101, 102, 103, 104, 105])){
                    $level = '[Level: 1]';
                }
                if(in_array($this->id, [106, 107, 108, 109, 110, 111])){
                    $level = '[Level: 2]';
                }
                if(in_array($this->id, [112, 113, 114, 115, 116, 117])){
                    $level = '[Level: 3]';
                }
                if(in_array($this->id, [118, 119, 120, 121, 122, 123])){
                    $level = '[Level: 4]';
                }
                if(in_array($this->id, [124, 125, 126, 127, 128, 129])){
                    $level = '[Level: 5]';
                }
                $this->name = '<div class="item_size_12 item_font_family item_socket_title">' . $this->website->set_limit($realName, $limit_text, '.') . $level . '</div>';
            } 
			else{
                if($exe >= 64){
                    $exe -= 64;
                }
				$span = '';
				
				$prefix = (($exe > 0) && $itemType != 2) ? 'Exc. ' : '';
                $prefix = ($exe > 0) ? __('Exc. ') : '';
                $level = ((int)substr($this->getlevel(), 1) > 0) ? $this->getlevel() : '';
                if(in_array($this->ancient, [5, 6, 9, 10, 20, 24]) && $itemType != 2){
					$this->getAncient();
					$span = '</span>';
					if($this->anc_prefix != ''){
						$this->name = '<div class="item_size_12 item_font_family"><span class="item_ancient_background item_ancient_title">' . $prefix . str_replace('\'', '&#39;', $this->anc_prefix).' ';
					}
					else{
						$this->name = '<div class="item_size_12 item_font_family"><span class="item_ancient_background item_ancient_title">' . $prefix . __('Ancient').' ';
					}  
                } 
				else{					
                    if($itemType == 2){
						if($this->type == 12){
                            $class = ($level > 6) ? 'item_yellow_title' : $class;
                        } else{
                            $class = ($exe > 0) ? 'item_socket_exe_title' : 'item_socket_title';
                        }
                        $this->name = '<div class="item_size_12 item_font_family ' . $class . '">' . $prefix;
                    } 
					else{
                        $class = ($level > 6) ? 'item_yellow_title' : $class;
                        $class = ($exe > 0) ? 'item_exe_title' : $class;
                        $this->name = '<div class="item_size_12 item_font_family ' . $class . '">' . $prefix . '';
                    }
				}
				if($this->hasTooltipLvl || $this->isMuun)
					$level = '';
                $this->name .= $this->website->set_limit($realName, $limit_text, '.') . ' ' . $level . $span . '</div>';
            }
        }
		
		
		public function setExpireTime($time){
			$this->expiretime = $time;
		}
		
		
		private function getExpireInfo(){
			if($this->expiretime != NULL && $this->expiretime != ''){
				return '<div class="item_size_12 item_font_family" style="margin-bottom: 3px;"><span style="color:#ed5109 !important;font-weight:bold;">'.__('Expirable Item').'</span><br>' . $this->website->date_diff(time(), strtotime('now +'.$this->expiretime.' minutes')) . '<br /></div>';	
			}
			else{
				if(!$this->server)
					$this->server = array_key_first($this->website->server_list());
				
				if(strlen($this->hex) == 64 && $this->isExpirable){
					$serial = hexdec(substr($this->hex, 32, 8));
					if($serial > 0){
						$column = (MU_VERSION >= 11) ? 'SerialCode' : 'Serial';
						$data = $this->website->db('game', $this->server)->cached_query('item_'.$serial.'', 'SELECT ExpireDate FROM IGC_PeriodItemInfo WHERE '.$column.' = '.$this->website->db('game', $this->server)->escape($serial).'', 360);
						if(!empty($data)){
							if(ctype_digit($data[0]['ExpireDate'])){
								$data[0]['ExpireDate'] = date(DATETIME_FORMAT, $data[0]['ExpireDate']);
							}
							return '<div class="item_size_12 item_font_family" style="margin-bottom: 3px;"><span style="color:#ed5109 !important;font-weight:bold;">'.__('Expiration Day').'</span><br>' . $data[0]['ExpireDate'] . '<br /></div>';
						}
						else{
							return '<div class="item_size_12 item_font_family" style="margin-bottom: 3px;"><span style="color:#ed5109 !important;font-weight:bold;">'.__('Expirable Item').'</span></div>';
						}
					}
				}
				elseif(strlen($this->hex) > 64){
					if($this->website->db('game', $this->server)->check_if_table_exists('CashShopPeriodicItem')){
						$serial = hexdec(substr($this->hex, 6, 8));
						if($serial > 0){
							$data = $this->website->db('game', $this->server)->cached_query('item_'.$serial.'', 'SELECT Time AS ExpireDate FROM CashShopPeriodicItem WHERE ItemSerial = '.$this->website->db('game', $this->server)->escape($serial).'', 360);
							if(!empty($data)){
								if(ctype_digit($data[0]['ExpireDate'])){
									$data[0]['ExpireDate'] = date(DATETIME_FORMAT, $data[0]['ExpireDate']);
								}
							}
						}
					}
					else{
						if($this->website->db('game', $this->server)->check_if_table_exists('CashShopPeriodItem')){
							if($serial > 0){
								$serial = hexdec(substr($this->hex, 6, 8));
								$data = $this->website->db('game', $this->server)->cached_query('item_'.$serial.'', 'SELECT Time AS ExpireDate FROM CashShopPeriodItem WHERE ItemSerial = '.$this->website->db('game', $this->server)->escape($serial).'', 360);
								if(!empty($data)){
									if(ctype_digit($data[0]['ExpireDate'])){
										$data[0]['ExpireDate'] = date(DATETIME_FORMAT, $data[0]['ExpireDate']);
									}
								}
							}
						}
					}
					if(isset($data) && !empty($data)){
						return '<div class="item_size_12 item_font_family" style="margin-bottom: 3px;"><span style="color:#ed5109 !important;font-weight:bold;">'.__('Expiration Day').'</span><br>' . $data[0]['ExpireDate'] . '<br /></div>';
					}	
				}	
			}
			return '';
		}
		
		
		public function earringOptions(){
			$options = '';
			
			if($this->type == 12 && in_array($this->id, [449, 450, 451, 452, 453, 454, 457, 458, 459, 460, 461, 462])){
				$opt_type = $this->serverfile->earring_type()->get('earringtype');	
				if($opt_type != false){
					if(isset($opt_type[$this->type][$this->id])){
						$opt_names = $this->serverfile->earring_option_name()->get('earringoptionname');	
						if($this->socket[1] != 255){
							if($opt_names != false && isset($opt_names[$opt_type[$this->type][$this->id]['Index1']]['name'])){
								$options .= '<div class="item_light_blue item_size_12 item_font_family">'.$opt_names[$opt_type[$this->type][$this->id]['Index1']]['name'].' '.$this->getEarringOptionValue($opt_type[$this->type][$this->id]['ValueIdx1'], $opt_type[$this->type][$this->id]['Edition'], $opt_names[$opt_type[$this->type][$this->id]['Index1']]['operator']).'</div>';
							}
						}
						if($this->socket[2] != 255){
							if($opt_names != false && isset($opt_names[$opt_type[$this->type][$this->id]['Index2']]['name'])){
								$options .= '<div class="item_light_blue item_size_12 item_font_family">'.$opt_names[$opt_type[$this->type][$this->id]['Index2']]['name'].' '.$this->getEarringOptionValue($opt_type[$this->type][$this->id]['ValueIdx2'], $opt_type[$this->type][$this->id]['Edition'], $opt_names[$opt_type[$this->type][$this->id]['Index2']]['operator']).'</div>';
							}
						}
						if($this->socket[3] != 255){
							if($opt_names != false && isset($opt_names[$opt_type[$this->type][$this->id]['Index3']]['name'])){
								$options .= '<div class="item_light_blue item_size_12 item_font_family">'.$opt_names[$opt_type[$this->type][$this->id]['Index3']]['name'].' '.$this->getEarringOptionValue($opt_type[$this->type][$this->id]['ValueIdx3'], $opt_type[$this->type][$this->id]['Edition'], $opt_names[$opt_type[$this->type][$this->id]['Index3']]['operator']).'</div>';
							}
						}
						if($this->socket[4] != 255){
							if($opt_names != false && isset($opt_names[$opt_type[$this->type][$this->id]['Index4']]['name'])){
								$options .= '<div class="item_light_blue item_size_12 item_font_family">'.$opt_names[$opt_type[$this->type][$this->id]['Index4']]['name'].' '.$this->getEarringOptionValue($opt_type[$this->type][$this->id]['ValueIdx4'], $opt_type[$this->type][$this->id]['Edition'], $opt_names[$opt_type[$this->type][$this->id]['Index4']]['operator']).'</div>';
							}
						}
						if($this->socket[5] != 255){
							if($opt_names != false && isset($opt_names[$opt_type[$this->type][$this->id]['Index5']]['name'])){
								$options .= '<div class="item_light_blue item_size_12 item_font_family">'.$opt_names[$opt_type[$this->type][$this->id]['Index5']]['name'].' '.$this->getEarringOptionValue($opt_type[$this->type][$this->id]['ValueIdx5'], $opt_type[$this->type][$this->id]['Edition'], $opt_names[$opt_type[$this->type][$this->id]['Index5']]['operator']).'</div>';
							}
						}
					}
				}
			} 
			return $options;
		}
		
		
		private function getEarringOptionValue($value, $edition, $operator){
			$data = $this->serverfile->earring_option()->get('earringoption');	
			if($data != false){
				if(isset($data[$edition][$value])){
					$symbol = ($operator == 1) ? '+' : '';
					$symbol2 = ($operator == 1) ? '' : '%';
					return $symbol.$data[$edition][$value].$symbol2;
				}
			}
			return '';
		}
		
		
		private function getMuunExpireInfo(){
			$data = [];
			if(!$this->server)
                $this->server = array_key_first($this->website->server_list());
			if(strlen($this->hex) == 64){
				$serial = hexdec(substr($this->hex, 32, 8));
	
				$data = $this->website->db('game', $this->server)->cached_query('muun_'.$serial.'', 'SELECT ExpireDate FROM IGC_Muun_Period WHERE Serial = '.$this->website->db('game', $this->server)->escape($serial).'', 360);
				if(!empty($data)){
					$this->muunExpirationTime = strtotime($data[0]['ExpireDate']);
													   
					if(strtotime($data[0]['ExpireDate']) < time()){
						$data[0]['ExpireDate'] = 'Expired Item';
					}
				}
			}
			if(strlen($this->hex) == 50){
				$data[0]['ExpireDate'] = strtotime('1970-01-01');
				if(!empty($data)){
					$this->muunExpirationTime = $data[0]['ExpireDate'];
					if($data[0]['ExpireDate'] < time()){
						$data[0]['ExpireDate'] = 'Expired Item';
					}
					else{
						$data[0]['ExpireDate'] = date(DATETIME_FORMAT, $data[0]['ExpireDate']);
					}
				}
			}
			if(!empty($data) && strlen($this->hex) != 50){
				return '<div class="item_size_12 item_font_family" style="margin-bottom: 3px;"><span style="color:#ed5109 !important;font-weight:bold;">'.__('Expiration Day').'</span><br>' . $data[0]['ExpireDate'] . '<br /></div>';
			}				
			return '';
		}
		
		public function staticOptionSystem(){	
			$options = '';
			$foundItems = [];
			if(strlen($this->hex) == 64){
				static $items = [];
			
				if(empty($items))
					$items = $this->serverfile->staticitems()->get('staticitems');
				
				if(!empty($items)){
					$lvl = ($this->option >= 128) ? floor(($this->option - 128) / 8) : floor($this->option / 8);
					foreach($items AS $item){
						if($this->type == $item['Cat'] && $this->id == $item['Index']){
							$foundItems[$item['OptionIndex']] = [
								'optId' => $item['OptionIndex'],
								'optVal' => $item['OptionValue'],
								'active' => ($lvl >= $item['MinLevel']) ? 1 : 0,
								'MinLevel' => $item['MinLevel'],
							];

						}
					}
				}

				if(!empty($foundItems)){
					$options .= '<div class="item_light_blue item_size_12 item_font_family">-- '.__('Over Enchantment  Bonus Information').' --</div>';
					$optionData = $this->serverfile->staticoptioninfo()->get('staticoptioninfo');
					foreach($foundItems AS $fkey => $fdata){
						if(isset($optionData[$fdata['optId']])){
							$color = 'purple';
							$desc = '';
							if($optionData[$fdata['optId']]['ColorID'] == 6){
								$color = 'purple';
							}
							if($optionData[$fdata['optId']]['ColorID'] == 7){
								$color = 'purple';
							}
							if($optionData[$fdata['optId']]['ColorID'] == 8){
								$color = 'purple';
							}
							if($optionData[$fdata['optId']]['ColorID'] == 9){
								$color = 'purple';
							}
							if($optionData[$fdata['optId']]['ColorID'] == 10){
								$color = 'purple';
							}
							if($optionData[$fdata['optId']]['ColorID'] == 11){
								$color = 'purple';
							}
							if($fdata['active'] == 0){
								$color = 'grey';
								$desc = sprintf(__(' (Rq Lv >= +%d)'), $fdata['MinLevel']);
							}
							$opt = preg_replace('/(%[d]%)/', $fdata['optVal'], $optionData[$fdata['optId']]['Title']);
							$opt = preg_replace('/(%[d])/', $fdata['optVal'], $opt);
							$options .= '<div style="color: '.$color.'" class="item_size_12 item_font_family">'.$opt.$desc.'</div>';
						}
					}
				}
			}
			
			return $options;
		}
		
		public function allInfo(){
            $this->canEquip();
			$this->getItemSkill();
			$this->additionalOption();
			$this->getLuck();
			//$this->getAncient();
			$this->getName();
			$this->getRefinery();
			$this->getHarmony();
			$this->getExe();
			$this->getSockets();
			$this->elementType();
			$this->elementInfo();
			$this->setTooltipOptions();
			$this->info = $this->name . '<br>';
			$this->info .= !empty($this->elementtype) ? $this->elementtype : '';
			$this->info .= !empty($this->tooltip_options) ? $this->tooltip_options : '';
			$this->info .= !empty($this->class) ? '<br>' . $this->class . '<br>' : '';
			$this->info .= !empty($this->stamina) ? $this->stamina . '<br>' : '';
			$this->info .= !empty($this->refopt) ? $this->refopt . '<br>' : '';
			$this->info .= !empty($this->haropt) ? $this->haropt . '<br>' : '';
			$this->info .= !empty($this->skill) ? $this->skill : '';
			$this->info .= !empty($this->luck) ? $this->luck : '';
			$this->info .= !empty($this->addopt) ? $this->addopt : '';
			$earringOptions = $this->earringOptions();
			if($earringOptions != ''){
				$this->info .= $earringOptions;
			}
			$this->info .= !empty($this->exe_options) ? str_replace('\'', '&#39;',$this->exe_options) . '<br>' : '';
			$this->info .= !empty($this->winggradeopt) ? $this->winggradeopt . '<br>' : '';
			$this->info .= !empty($this->ancopt) ? $this->ancopt . '<br>' : '';
			if($this->isErrtelItem()){
				$this->info .= '<div>' . $this->errtel_rank . ' '.__('Rank Errtel.').'</div>';
			}
			$this->info .= !empty($this->sockopt) ? $this->sockopt . '<br>' : '';
			$this->info .= !empty($this->bonussocketopt) ? $this->bonussocketopt . '<br>' : '';
			$this->info .= !empty($this->elementopt) ? $this->elementopt . '<br>' : '';
			$staticOptions = $this->staticOptionSystem();
			if(mb_strlen($staticOptions) > 0){
				$this->info .= $staticOptions.'<br>';
			}
			$this->info .= $this->getExpireInfo();
			if($this->isMuun){
				$this->info .= $this->getMuunExpireInfo();
			}
			if(strlen($this->hex) == 64){
				$this->info .= !empty($this->serial2) ? __('Serial').': ' . $this->serial2 . '<br>' : '';
			}
			else{
				$this->info .= !empty($this->serial) ? __('Serial').': ' . $this->serial . '<br>' : '';
			}
			return $this->info;
        }
		
		private function getExeType($slot, $id, $cat){
			$exetype = -2;
            switch($slot){
				default:
				case -1:
				case 8:
					$exetype = -1;
				break;
				case 0:
				case 1:
				case 9:
					if($cat == 6){
						$exetype = 2;
					} 
					else{
						$exetype = 1;
					}
				break;
				case 2:
				case 3:
				case 4:
				case 5:
				case 6:
				case 10:
				case 11:
					$exetype = 2;
				break;
				case 7:
					//279,280,281,282-287,422-445
					if($cat == 12){
						if(in_array($id, [0, 1, 2, 3, 4, 5, 6, 41, 42, 130, 131, 132, 133, 134, 135, 154, 155, 156, 157, 172, 173, 174, 278, 422, 423, 424, 425, 427, 435]))
							$exetype = 24; 
						if(in_array($id, [27, 28, 36, 37, 38, 39, 40, 43, 50, 158, 159, 176, 268, 467, 468, 472, 473, 430, 431, 432, 433, 434, 489, 496]))
							$exetype = 25; 
						if(in_array($id, [426]))
							$exetype = 26; 
						if(in_array($id, [49, 428]))
							$exetype = 27; 
						if(in_array($id, [262, 263, 264, 265]))
							$exetype = 28; 
						if(in_array($id, [266, 269, 270, 429]))
							$exetype = 60; 
						if(in_array($id, [267]))
							$exetype = 62; 
						if(in_array($id, [480]))
							$exetype = 63; 
						if(in_array($id, [152, 160, 178, 414, 415, 416, 417, 418, 419, 420, 421, 469, 474, 490]))
							$exetype = 76; 
						if(in_array($id, [180, 181, 182, 183, 184, 185, 186, 187, 188, 189, 190, 191, 192, 193]))
							$exetype = 63; 
					}
					if($cat == 13 && in_array($id, [30])){
						$exetype = 26;
					}
				break;
			}
            if($exetype == -2){
				writelog('Invalid item exe type. Slot: ' . $slot . ', id: ' . $id . ', cat: ' . $cat . '', 'system_error');
			}
            return $exetype;
        }
    }