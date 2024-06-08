<?php
    in_file();

    class parse_server_file extends library
    {
        private $lang;
        private $file;
        private $info = [];
		private $anctype = [];
		private $ancinfo = [];
		private $sockettype = [];
		private $earringtype = [];
		private $earringoption = [];
		private $earringoptionname = [];
		private $gradeinfo = [];
		private $penta_option_value = [];
		private $penta_option = [];
		private $skill_data = [];
		private $muuninfo = [];
		private $muunoptioninfo = [];
		private $staticitems = [];
		private $staticoptioninfo = [];
		private $monsters = [];
        private $cache_time;
		private $dom;
		private $isMuEngine = false;

        public function __construct(){
            if($this->config->config_entry('main|cache_type') == 'file'){
                $this->load->lib('Cache/File as cache', [APP_PATH . DS . 'data' . DS . 'shop']);
            } 
			else{
                $this->load->lib('Cache/MemCached as cache',[$this->config->config_entry('main|mem_cached_ip'), $this->config->config_entry('main|mem_cached_port')]);
            }

			$this->cache_time = (3600 * 24) * 100;
        }
		
		public function parse_txt($type = ''){
			$this->info = [];
			$file_list = [
				'exe_common' => 'ExcellentCommonOption.txt', 
				'exe_wing' => 'ExcellentWingOption.txt', 
				'item_add_option' => 'ItemAddOption.txt', 
				'item_level_tooltip' => 'ItemLevelTooltip.txt', 
				'item_set_option_text' => 'ItemSetOptionText.csv', 
				'item_tooltip' => 'ItemTooltip.csv', 
				'item_tooltip_text' => 'ItemTooltipText.csv', 
				'jewel_of_harmony_option' => 'JewelOfHarmonyOption.txt',
				'socket_item' => 'SocketItem.txt', 
				'socket_item[6]' => 'SocketItem[6].txt'
			];
			$patternItemToolTip = '([0-9]+)[\s]+([0-9]+)[\s]+([\p{L}\p{M}\-\(\)\[\]\\\'\&\%\:\~\#\.\,\?\!\$\*\+\=\/（） ?0-9]+)[\s]+' . substr(str_repeat("(-?[0-9]+)[\s]+", 28), 0, -5);
			$key_list = [
				'item_add_option' => ['group', 'index', 'type1', 'rise1', 'type2', 'rise2', 'time'], 
				'item_tooltip' => ['', 'Group', 'Index', 'Name', 'Color', 'Unk1', 'Unk2', 'Unk3', 'iInfoLine_1', 'Unk4', 'iInfoLine_2', 'Unk5', 'iInfoLine_3', 'Unk6', 'iInfoLine_4', 'Unk7', 'iInfoLine_5', 'Unk8', 'iInfoLine_6', 'Unk9', 'iInfoLine_7', 'Unk10', 'iInfoLine_8', 'Unk11', 'iInfoLine_9', 'Unk12', 'iInfoLine_10', 'Unk13', 'iInfoLine_11', 'Unk14', 'iInfoLine_12', 'Unk15'], 
			];	
			if(MU_VERSION >= 11){
				$file_list['item_tooltip'] = 'ItemTooltip[11].csv';
				$patternItemToolTip = '([0-9]+)[\s]+([0-9]+)[\s]+([\p{L}\p{M}\-\(\)\[\]\\\'\&\%\:\~\#\.\,\?\!\$\*\+\=\/（） ?0-9]+)[\s]+' . substr(str_repeat("(-?[0-9]+)[\s]+", 34), 0, -5);
				$key_list['item_tooltip'] = ['', 'Group', 'Index', 'Name', 'Color', 'Unk1', 'Unk2', 'Unk3', 'iInfoLine_1', 'Unk4', 'iInfoLine_2', 'Unk5', 'iInfoLine_3', 'Unk6', 'iInfoLine_4', 'Unk7', 'iInfoLine_5', 'Unk8', 'iInfoLine_6', 'Unk9', 'iInfoLine_7', 'Unk10', 'iInfoLine_8', 'Unk11', 'iInfoLine_9', 'Unk12', 'iInfoLine_10', 'Unk13', 'iInfoLine_11', 'Unk14', 'iInfoLine_12', 'Unk15', 'iInfoLine_13', 'Unk16', 'iInfoLine_14', 'Unk17', 'iInfoLine_15', 'Unk18'];
			}
			$patter_list = [
				'exe_common' => '[\s]?([0-9]+)[\s]+([0-9]+)[\s]+([0-9]+)[\s]+([\p{L}\p{M}\-\(\)\[\]\\\'\&\%\:\~\#\.\,\?\!\$\*\+\=\/（） ?0-9]+)[\s]' . substr(str_repeat("{0,}([0-9]{0,})[\s]", 12), 0, -4), 
				'exe_wing' => '[\s]?([0-9]+)[\s]+([0-9]+)[\s]+([0-9]+)[\s]+([\p{L}\p{M}\-\(\)\[\]\\\'\&\%\:\~\#\.\,\?\!\$\*\+\=\/（） ?0-9]+)[\s]' . substr(str_repeat("{0,}([0-9]{0,})[\s]", 12), 0, -4), 
				'item_add_option' => '[\s]?([0-9]+)[\s]+([0-9]+)[\s]+([0-9]+)[\s]+([0-9]+)[\s]+([0-9]+)[\s]+([0-9]+)[\s]+(-?[0-9]+)', 
				'item_level_tooltip' => '[\s]?([0-9]+)[\s]+([0-9]+)[\s]+([\p{L}\p{M}\-\(\)\[\]\\\'\&\%\:\~\#\.\,\?\!\$\*\+\=\/（） ?0-9]+)[\s]' . substr(str_repeat("{0,}(-?[0-9]{0,})[\s]", 26), 0, -4), 
				'item_set_option_text' => '([0-9]+)[\s]+([\p{L}\p{M}\-\(\)\[\]\\\'\&\%\:\~\#\.\,\?\!\$\*\+\=\/ ?0-9]+)[\s]' . substr(str_repeat("{0,}(-?[0-9]{0,})[\s]", 1), 0, -4), 
				'item_tooltip' => $patternItemToolTip, 
				'item_tooltip_text' => '([0-9]+)[\s]+([0-9]+)[\s]+([\p{L}\p{M}\-\(\)\[\]\\\'\&\%\:\~\#\.\,\?\!\$\*\+\=\/（） ?0-9]+)[\s]' . substr(str_repeat("{0,}(-?[0-9]{0,})[\s]", 1), 0, -4), 
				'jewel_of_harmony_option' => '[\s]?([0-9]+)[\s]+([0-9]+)[\s]+([\p{L}\p{M}\-\(\)\[\]\\\'\&\%\:\~\#\.\,\?\!\$\*\+\=\/（） ?0-9]+)[\s]+' . substr(str_repeat("([0-9]+)[\s]+", 28), 0, -5), 
				'socket_item' => '[\s]?' . str_repeat("(-?[0-9]+)[\s]+", 4) . '([\p{L}\p{M}\-\(\)\[\]\\\'\&\%\:\~\#\.\,\?\!\$\*\+\=\/（） ?0-9]+)[\s]+' . substr(str_repeat("(-?[0-9]+)[\s]+", 14), 0, -5), 
				'socket_item[6]' => '[\s]?' . str_repeat("(-?[0-9]+)[\s]+", 4) . '([\p{L}\p{M}\-\(\)\[\]\\\'\&\%\:\~\#\.\,\?\!\$\*\+\=\/（） ?0-9]+)[\s]+' . substr(str_repeat("(-?[0-9]+)[\s]+", 29), 0, -5)
			];
			
			if($type != ''){
				if(array_key_exists($type, $file_list)){
					if($this->check_file($file_list[$type])){
						$data = file($this->file, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES);						
						$i = 0;
						
						foreach($data AS $line){
							if($type == 'item_tooltip_text' && !preg_match('/' . $patter_list[$type] . '$/u', $line)){
								$patter_list['item_tooltip_text'] = '([0-9]+)[\s]+([\p{L}\p{M}\-\(\)\[\]\\\'\&\%\:\~\#\.\,\?\!\$\*\+\=\/（） ?0-9]+)[\s]' . substr(str_repeat("{0,}(-?[0-9]{0,})[\s]", 1), 0, -4);
							}
							if(preg_match('/' . $patter_list[$type] . '$/u', $line, $match)){
								$i++;
								unset($match[0]);
								if($type == 'item_tooltip'){
									unset($match[3]);
									$item_cat = $match[1];
									$id = $match[2];
									unset($match[1]);
									unset($match[2]);
										
								}

								if($type == 'exe_common' || $type == 'exe_wing'){
									unset($match[1]);
								}
								if(array_key_exists($type, $key_list)){
									foreach($match AS $k => $v){
										if(isset($cat)){
											if(isset($key_list[$type][$k])){
												$this->info[$cat][$match[1]][$key_list[$type][$k]] = $v;
											}
										} else{
											if(isset($key_list[$type][$k])){
												if($type == 'item_tooltip'){
													$this->info[$item_cat][$id][$key_list[$type][$k]] = $v;
												} else{
													$this->info[$i][$key_list[$type][$k]] = $v;
												}
											}
										}
									}
								} else{
									if($type == 'item_tooltip_text'){
										$this->info[$match[1]] = $match;
									} else if($type == 'item_level_tooltip'){
										if(empty($match[3])){
											continue;
										}
										$this->info[$match[2]] = $match;
									} else{
										$this->info[] = $match;
									}
								}
							}
						}
					} else{
						writelog('[Server File Parser] File not found: ' . $this->file, 'system_error');
						return false;
					}
				} else{
					writelog('[Server File Parser] type not found: ' . $type, 'system_error');
					return false;
				}
			} else{
				writelog('[Server File Parser] type is empty', 'system_error');
				return false;
			}
			return true;
		}
		
		public function parse_xml_pentagram_jewel_option_value(){
			if($this->check_file('PentagramJewelOptionValue.xml')){
				$this->dom = new \DomDocument();
				$this->dom->load($this->file);
				$xp = new \DomXPath($this->dom);
				$res = $xp->query('Errtel');
				foreach($res AS $s => $v){
					$this->penta_option_value[] = [
						'cat' => $v->getAttribute('ItemCat'),
						'id' => $v->getAttribute('ItemIndex'),
						'rank' => $v->getAttribute('Rank'),
						'num' => $v->getAttribute('OptionNum'),
						'OptionValue0' => $v->getAttribute('OptionValue0'),
						'OptionValue1' => $v->getAttribute('OptionValue1'),
						'OptionValue2' => $v->getAttribute('OptionValue2'),
						'OptionValue3' => $v->getAttribute('OptionValue3'),
						'OptionValue4' => $v->getAttribute('OptionValue4'),
						'OptionValue5' => $v->getAttribute('OptionValue5'),
						'OptionValue6' => $v->getAttribute('OptionValue6'),
						'OptionValue7' => $v->getAttribute('OptionValue7'),
						'OptionValue8' => $v->getAttribute('OptionValue8'),
						'OptionValue9' => $v->getAttribute('OptionValue9'),
						'OptionValue10' => $v->getAttribute('OptionValue10'),
						'name' => $v->getAttribute('Name')
					];
				}
				return true;
			}
			return false;
		}
		
		
		public function parse_xml_muun_info(){
			if($this->check_file('MuunInfo.xml')){
				$this->dom = new \DomDocument();
				$this->dom->load($this->file);
				$xp = new \DomXPath($this->dom);
				$res = $xp->query('Muun');
				foreach($res AS $s => $v){
					$this->muuninfo[$v->getAttribute('ItemIndex')] = [
						'type' => $v->getAttribute('Type'),
						'rank' => $v->getAttribute('Rank'),
						'option_index' => $v->getAttribute('OptionIndex'),
						'option_type' => $v->getAttribute('AddOptionType'),
						'option_value' => $v->getAttribute('AddOptionValue'),
						'period' => $v->getAttribute('SetPeriod'),
						'evo_item' => $v->getAttribute('EvolutionItemIndex')
					];
				}
				return true;
			}
			return false;
		}
		
		public function parse_xml_static_option_system(){
			if($this->check_file('ItemOptionSystem_Exc.xml')){
				$this->dom = new \DomDocument();
				$this->dom->load($this->file);
				$xp = new \DomXPath($this->dom);
				$res = $xp->query('Items/Item');
				$res2 = $xp->query('OptionText/Text');
				foreach($res AS $s => $v){
					$this->staticitems[] = [
						'Cat' => $v->getAttribute('Cat'),
						'Index' => $v->getAttribute('Index'),
						'MinLevel' => $v->getAttribute('MinLevel'),
						'SocketCount' => $v->getAttribute('SocketCount'),
						'OptionIndex' => $v->getAttribute('OptionIndex'),
						'OptionValue' => $v->getAttribute('OptionValue'),
						'Ancient' => $v->getAttribute('Ancient'),
						'Excellent' => $v->getAttribute('Excellent')
					];
				}
				foreach($res2 AS $ss => $vv){
					$this->staticoptioninfo[$vv->getAttribute('OptionId')] = [
						'ColorID' => $vv->getAttribute('ColorID'),
						'Title' => $vv->getAttribute('Title')
					];		
				}
				return true;
			}
			return false;
		}
		
		
		public function parse_xml_skill(){
			if($this->check_file('SkillList.xml')){
				$this->dom = new \DomDocument();
				$this->dom->load($this->file);
				$xp = new \DomXPath($this->dom);
				$res = $xp->query('Skill');
				foreach($res AS $s => $v){
					$this->skill_data[$v->getAttribute('Index')] = [
						'name' => $v->getAttribute('Name'),
						'mana' => $v->getAttribute('ManaUsage')
					];
				}
				return true;
			}
			return false;
		}
		
		
		public function parse_xml_muun_option(){
			if($this->check_file('MuunOption.xml')){
				$this->dom = new \DomDocument();
				$this->dom->load($this->file);
				$xp = new \DomXPath($this->dom);
				$res = $xp->query('Option');
				foreach($res AS $s => $v){
					$this->muunoptioninfo[$v->getAttribute('Index')] = [
						'name' => $v->getAttribute('Name'),
						'desc' => $v->getAttribute('Description'),
						'type' => $v->getAttribute('Type'),
						'rank' => $v->getAttribute('Rank'),
						'value0' => $v->getAttribute('Value0'),
						'value1' => $v->getAttribute('Value1'),
						'value2' => $v->getAttribute('Value2'),
						'value3' => $v->getAttribute('Value3'),
						'value4' => $v->getAttribute('Value4'),
						'value5' => $v->getAttribute('Value5'),
						'cond_type' => $v->getAttribute('ConditionType'),
						'cond_val1' => $v->getAttribute('ConditionValue1'),
						'cond_val2' => $v->getAttribute('ConditionValue2')
					];
				}
				return true;
			}
			return false;
		}
		
		
		public function parse_xml_item_grade(){
			if($this->check_file('ItemGradeOption.xml')){
				$this->dom = new \DomDocument();
				$this->dom->load($this->file);
				$xp = new \DomXPath($this->dom);
				$res = $xp->query('List/Option');
				foreach($res AS $s => $v){
					$this->gradeinfo[$v->getAttribute('Index')] = [
						'name' => $v->getAttribute('Name'),
						'Grade0Val' => $v->getAttribute('Grade0Val'),
						'Grade1Val' => $v->getAttribute('Grade1Val'),
						'Grade2Val' => $v->getAttribute('Grade2Val'),
						'Grade3Val' => $v->getAttribute('Grade3Val'),
						'Grade4Val' => $v->getAttribute('Grade4Val'),
						'Grade5Val' => $v->getAttribute('Grade5Val'),
						'Grade6Val' => $v->getAttribute('Grade6Val'),
						'Grade7Val' => $v->getAttribute('Grade7Val'),
						'Grade8Val' => $v->getAttribute('Grade8Val'),
						'Grade9Val' => $v->getAttribute('Grade9Val')
					];
				}
				return true;
			}
			return false;
		}
		
		
		public function parse_xml_set_type(){
			if($this->check_file('ItemSetType.xml')){
				$this->dom = new \DomDocument();
				$this->dom->load($this->file);
				$xp = new \DomXPath($this->dom);
				$res = $xp->query('Section/Item');

				foreach($res AS $s => $v){
					$this->anctype[$v->parentNode->getAttribute('Index')][$v->getAttribute('Index')] = [
						'set' => $v->getAttribute('TierI'),
						'typeA' => $v->getAttribute('TierII'),
						'set2' => $v->getAttribute('TierIII'),
						'typeB' => $v->getAttribute('TierIV'),
						'set3' => 0,
						'typeC' => 0
					];
				}
				return true;
			}
			return false;
		}
		
		
		public function parse_xml_set_options(){
			if($this->check_file('ItemSetOption.xml')){
				$this->dom = new \DomDocument();
				$this->dom->load($this->file);
				$xp = new \DomXPath($this->dom);
				$res = $xp->query('SetItem');
				foreach($res AS $s => $v){
					$this->ancinfo[$v->getAttribute('Index')] = [
						'name' => $v->getAttribute('Name'),
						'opt_1_1' => $v->getAttribute('OptIdx1_1'),
						'opt_1_1_val' => $v->getAttribute('OptVal1_1'),
						'opt_2_1' => $v->getAttribute('OptIdx2_1'),
						'opt_2_1_val' => $v->getAttribute('OptVal2_1'),
						'opt_1_2' => $v->getAttribute('OptIdx1_2'),
						'opt_1_2_val' => $v->getAttribute('OptVal1_2'),
						'opt_2_2' => $v->getAttribute('OptIdx2_2'),
						'opt_2_2_val' => $v->getAttribute('OptVal2_2'),
						'opt_1_3' => $v->getAttribute('OptIdx1_3'),
						'opt_1_3_val' => $v->getAttribute('OptVal1_3'),
						'opt_2_3' => $v->getAttribute('OptIdx2_3'),
						'opt_2_3_val' => $v->getAttribute('OptVal2_3'),
						'opt_1_4' => $v->getAttribute('OptIdx1_4'),
						'opt_1_4_val' => $v->getAttribute('OptVal1_4'),
						'opt_2_4' => $v->getAttribute('OptIdx2_4'),
						'opt_2_4_val' => $v->getAttribute('OptVal2_4'),
						'opt_1_5' => $v->getAttribute('OptIdx1_5'),
						'opt_1_5_val' => $v->getAttribute('OptVal1_5'),
						'opt_2_5' => $v->getAttribute('OptIdx2_5'),
						'opt_2_5_val' => $v->getAttribute('OptVal2_5'),
						'opt_1_6' => $v->getAttribute('OptIdx1_6'),
						'opt_1_6_val' => $v->getAttribute('OptVal1_6'),
						'opt_2_6' => $v->getAttribute('OptIdx2_6'),
						'opt_2_6_val' => $v->getAttribute('OptVal2_6'),
						'fopt_1' => $v->getAttribute('FullOptIdx1'),
						'fopt_val1' => $v->getAttribute('FullOptVal1'),
						'fopt_2' => $v->getAttribute('FullOptIdx2'),
						'fopt_val2' => $v->getAttribute('FullOptVal2'),
						'fopt_3' => $v->getAttribute('FullOptIdx3'),
						'fopt_val3' => $v->getAttribute('FullOptVal3'),
						'fopt_4' => $v->getAttribute('FullOptIdx4'),
						'fopt_val4' => $v->getAttribute('FullOptVal4'),
						'fopt_5' => $v->getAttribute('FullOptIdx5'),
						'fopt_val5' => $v->getAttribute('FullOptVal5'),
						'fopt_6' => $v->getAttribute('FullOptIdx6'),
						'fopt_val6' => $v->getAttribute('FullOptVal6'),
						'fopt_7' => $v->getAttribute('FullOptIdx7'),
						'fopt_val7' => $v->getAttribute('FullOptVal7'),
					];
				}
				return true;
			}
			return false;
		}
		
		
		public function parse_xml_monster_list(){
			if($this->check_file('Monsters' . DS . 'MonsterList.xml')){
				$this->dom = new \DomDocument();
				$this->dom->load($this->file);
				$xp = new \DomXPath($this->dom);
				$res = $xp->query('Monster');

				foreach($res AS $s => $v){
					$this->monsters[$v->getAttribute('Index')] = [
						'name' => $v->getAttribute('Name'),
						'lvl' => $v->getAttribute('Level'),
						'hp' => $v->getAttribute('HP'),
						'DamageMin' => $v->getAttribute('DamageMin'),
						'DamageMax' => $v->getAttribute('DamageMax'),
						'Defense' => $v->getAttribute('Defense'),
						'MagicDefense' => $v->getAttribute('MagicDefense'),
						'AttackRate' => $v->getAttribute('AttackRate'),
						'BlockRate' => $v->getAttribute('BlockRate'),
						'AttackRange' => $v->getAttribute('AttackRange'),
						'AttackSpeed' => $v->getAttribute('AttackSpeed'),
						'PentagramDamageMin' => $v->getAttribute('PentagramDamageMin'),
						'PentagramDamageMax' => $v->getAttribute('PentagramDamageMax'),
						'PentagramDefense' => $v->getAttribute('PentagramDefense'),
						'PentagramDefenseRate' => $v->getAttribute('PentagramDefenseRate'),
						'IceRes' => $v->getAttribute('IceRes'),
						'PoisonRes' => $v->getAttribute('PoisonRes'),						
						'LightRes' => $v->getAttribute('LightRes'),
						'FireRes' => $v->getAttribute('FireRes'),		
					];
				}
				return true;
			}
			return false;
		}
		
		
		public function parse_xml_socket_item_type(){
			if($this->check_file('SocketItemType.xml')){
				$this->dom = new \DomDocument();
				$this->dom->load($this->file);
				$xp = new \DomXPath($this->dom);
				$res = $xp->query('Item');

				foreach($res AS $s => $v){
					$this->sockettype[$v->getAttribute('Section')][] = $v->getAttribute('Type');
				}
				return true;
			}
			return false;
		}
		
		
		public function parse_xml_earring_attribute(){
			if($this->check_file('EarringAttribute.xml')){
				$this->dom = new \DomDocument();
				$this->dom->load($this->file);
				$xp = new \DomXPath($this->dom);
				$res = $xp->query('OptionLinkSettings/Option');
				$res2 = $xp->query('OptionSettings/OptionSet');
				$res3 = $xp->query('OptionsList/Option');
				foreach($res AS $s => $v){
					$this->earringtype[$v->getAttribute('ItemCat')][$v->getAttribute('ItemIndex')] = [
						'Index1' => $v->getAttribute('Index1'),
						'ValueIdx1' => $v->getAttribute('ValueIdx1'),
						'Index2' => $v->getAttribute('Index2'),
						'ValueIdx2' => $v->getAttribute('ValueIdx2'),
						'Index3' => $v->getAttribute('Index3'),
						'ValueIdx3' => $v->getAttribute('ValueIdx3'),
						'Index4' => $v->getAttribute('Index4'),
						'ValueIdx4' => $v->getAttribute('ValueIdx4'),
						'Index5' => $v->getAttribute('Index5'),
						'ValueIdx5' => $v->getAttribute('ValueIdx5'),
						'Edition' => $v->getAttribute('Edition')
					];
				}
				foreach($res2 AS $ss => $vv){
					$this->earringoption[$vv->getAttribute('Edition')][$vv->getAttribute('Index')] = $vv->getAttribute('Value');		
				}
				foreach($res3 AS $sss => $vvv){
					$this->earringoptionname[$vvv->getAttribute('Index')] = [
						'name' => $vvv->getAttribute('Name'),
						'operator' => $vvv->getAttribute('Operator')
					];
				}
				return true;
			}
			return false;
		}
		
		
		public function parse_xml($cat = [], $type = 1){
			$this->info = [];
			$file = ($type == 1) ? 'ItemList.xml' : 'Item.xml';
            if($this->check_file($file)){
				$this->dom = new \DomDocument();
				@$this->dom->load($this->file);
				$xp = new \DomXPath($this->dom);

                if(!empty($cat)){
                    foreach($cat AS $category){
						$list = $xp->query("//ItemList/Section[@Index='" . $category . "']/Item");
                        if(!empty($list)){
                            foreach($list AS $item){
								if($type == 1){
									$this->info[$category][$item->getAttribute('Index')] = $this->load_item_attributes($item);
								}
								else{
									$this->info[$category][$item->getAttribute('Index')] = $this->load_item_attributes_mudevs($item);
								}
                            }
                        }
                    }
                }
                return true;
            }
            return false;
        }
		
		public function parse_item_txt(){
            $file_data = null;
            $items = [];
            if($this->check_file('Item.txt')){
                $keys = [];
                $keys[0] = ['id', 'slot', 'skill', 'x', 'y', 'serial', 'option', 'drop', 'name', 'lvldrop', 'mindmg', 'maxdmg', 'attspeed', 'dur', 'magdur', 'magpower', 'lvlreq', 'strreq', 'agireq', 'enereq', 'vitreq', 'cmdreq', 'setattribute', 'dw/sm', 'dk/bk', 'elf/me', 'mg', 'dl', 'sum', 'rf', 'gl', 'rw', 'sl', 'gc', 'km', 'lm', 'ik'];
                $keys[1] = $keys[0];
                $keys[2] = $keys[0];
                $keys[3] = $keys[0];
                $keys[4] = $keys[0];
                $keys[5] = $keys[0];
                $keys[6] = ['id', 'slot', 'skill', 'x', 'y', 'serial', 'option', 'drop', 'name', 'lvldrop', 'def', 'successblock', 'dur', 'lvlreq', 'strreq', 'agireq', 'enereq', 'vitreq', 'cmdreq', 'setattribute', 'dw/sm', 'dk/bk', 'elf/me', 'mg', 'dl', 'sum', 'rf', 'gl', 'rw', 'sl', 'gc', 'km', 'lm', 'ik'];
                $keys[7] = ['id', 'slot', 'skill', 'x', 'y', 'serial', 'option', 'drop', 'name', 'lvldrop', 'def', 'magdef', 'dur', 'lvlreq', 'strreq', 'agireq', 'enereq', 'vitreq', 'cmdreq', 'setattribute', 'dw/sm', 'dk/bk', 'elf/me', 'mg', 'dl', 'sum', 'rf', 'gl', 'rw', 'sl', 'gc', 'km', 'lm', 'ik'];
                $keys[8] = $keys[7];
                $keys[9] = $keys[7];
                $keys[10] = $keys[7];
                $keys[11] = $keys[7];
                $keys[12] = ['id', 'slot', 'skill', 'x', 'y', 'serial', 'option', 'drop', 'name', 'lvldrop', 'def', 'dur', 'lvlreq', 'enereq', 'strreq', 'dexreq', 'comreq', 'buymoney', 'dw/sm', 'dk/bk', 'elf/me', 'mg', 'dl', 'sum', 'rf', 'gl', 'rw', 'sl', 'gc', 'km', 'lm', 'ik'];
                $keys[13] = ['id', 'slot', 'skill', 'x', 'y', 'serial', 'option', 'drop', 'name', 'lvlreq', 'dur', 'res1', 'res2', 'res3', 'res4', 'res5', 'res6', 'res7', 'setattribute', 'dw/sm', 'dk/bk', 'elf/me', 'mg', 'dl', 'sum', 'rf', 'gl', 'rw', 'sl', 'gc', 'km', 'lm', 'ik'];
                $keys[14] = ['id', 'slot', 'skill', 'x', 'y', 'serial', 'option', 'drop', 'name', 'value', 'lvldrop'];
                $keys[15] = ['id', 'slot', 'skill', 'x', 'y', 'serial', 'option', 'drop', 'name', 'lvldrop', 'lvlreq', 'enereq', 'buymoney', 'dw/sm', 'dk/bk', 'elf/me', 'mg', 'dl', 'sum', 'rf', 'gl', 'rw', 'sl', 'gc', 'km', 'lm', 'ik'];
                if(MU_VERSION >= 11){
					$keys[16] = ['id', 'slot', 'skill', 'x', 'y', 'serial', 'option', 'drop', 'name', 'lvlreq', 'dur', 'res1', 'res2', 'res3', 'res4', 'res5', 'res6', 'res7', 'setattribute', 'dw/sm', 'dk/bk', 'elf/me', 'mg', 'dl', 'sum', 'rf', 'gl', 'rw', 'sl', 'gc', 'km', 'lm', 'ik'];
					$keys[19] = ['id', 'slot', 'skill', 'x', 'y', 'serial', 'option', 'drop', 'name', 'value', 'lvlreq'];
					$keys[20] = $keys[16];
				}
				if($file_data == null){
                    $file_data = file($this->file, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES);
                }
				
				$cats = (MU_VERSION >= 11) ? 20 : 15;
				
                if(empty($items)){
                    foreach($file_data AS $line){
                        if(is_numeric(trim(substr($line, 0, 1))) && strlen(trim($line)) <= $cats){
                            $type = (int)trim($line);
                            continue;
                        }

                        if(preg_match('/([0-9\*]+)[\s]+([0-9\-\*]+)[\s]+([0-9\*]+)[\s]+([0-9\*]+)[\s]+([0-9\*]+)[\s]+([0-9\*]+)[\s]+([0-9\*]+)[\s]+([0-9\*]+)[\s]+"([\p{L}\-\(\)\[\]\'\&\/\. ?0-9]+)"[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})$/u', $line, $match)){
                            unset($match[0]);
                            foreach($match AS $k => $v){
                                if(isset($keys[$type][$k - 1])){
                                    $items[$type][$match[1]][$keys[$type][$k - 1]] = $v;
                                }
                            }
                        }
						else{
							//test muengine
							if(preg_match('/([0-9\*]+)[\s]+([0-9\-\*]+)[\s]+([0-9\*]+)[\s]+([0-9\*]+)[\s]+([0-9\*]+)[\s]+([0-9\*]+)[\s]+"([\p{L}\p{M}\-\(\)\[\]\'\&\/\. ?0-9]+)"[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})[\s]{0,}([0-9\*]{0,})$/u', $line, $match)){
								unset($match[0]);
								$keys = ['rid', 'sid', 'subgroup', 'unk1', 'group', 'id', 'name', 'kindA', 'kindB', 'type', 'thand', 'lvldrop', 'slot', 'skill', 'x', 'y', 'mindmg', 'maxdmg', 'successblock', 'def', 'attspeed', 'walkspeed', 'dur', 'magdur', 'magpower', 'strreq', 'agireq', 'enereq', 'vitreq', 'cmdreq', 'lvlreq', 'ivalue', 'buymoney', 'setattribute', 'dw/sm', 'dk/bk', 'elf/me', 'mg', 'dl', 'sum', 'rf', 'gl', 'rw', 'res1', 'res2', 'res3', 'res4', 'res5', 'res6', 'res7', 'dump', 'transaction', 'pstore', 'swarehouse', 'snpc', 'unk2', 'repair', 'overlap', 'unk3'];
								
								foreach($match AS $k => $v){
									$items[$match[5]][$match[6]][$keys[$k - 1]] = $v;
								}
								$this->isMuEngine = true;
							}
						}
                    }
                }
                $this->info = $items;
                return true;
            }
            return false;
        }
		
		private function load_item_attributes($item)
		{
			$items = [
				'id' => $item->getAttribute('Index'), 
				'slot' => $item->getAttribute('Slot'), 
				'skill' => $item->getAttribute('SkillIndex'), 
				'x' => $item->getAttribute('Width'), 
				'y' => $item->getAttribute('Height'), 
				'option' => $item->getAttribute('Option'), 
				'name' => $item->getAttribute('Name'), 
				'lvldrop' => $item->getAttribute('DropLevel'), 
				'kindA' => $item->getAttribute('KindA'), 
				'kindB' => $item->getAttribute('KindB'),
				'type' => $item->getAttribute('Type'),
				'dump' => $item->getAttribute('Dump')
			];
			if($item->getAttribute('Durability') != NULL)
				$items['dur'] = $item->getAttribute('Durability');
			if($item->getAttribute('DamageMin') != NULL)
				$items['mindmg'] = $item->getAttribute('DamageMin');
			if($item->getAttribute('DamageMax') != NULL)
				$items['maxdmg'] = $item->getAttribute('DamageMax');
			if($item->getAttribute('AttackSpeed') != NULL)
				$items['attspeed'] = $item->getAttribute('AttackSpeed');
			if($item->getAttribute('MagicDurability') != NULL)
				$items['magdur'] = $item->getAttribute('MagicDurability');
			if($item->getAttribute('WalkSpeed') != NULL)
				$items['attspeed'] = $item->getAttribute('WalkSpeed');
			if($item->getAttribute('Defense') != NULL)
				$items['def'] = $item->getAttribute('Defense');
			if($item->getAttribute('SuccessfulBlocking') != NULL)
				$items['successblock'] = $item->getAttribute('SuccessfulBlocking');
			if($item->getAttribute('MagicPower') != NULL)
				$items['magpow'] = $item->getAttribute('MagicPower');
			if($item->getAttribute('MagicDefense') != NULL)
				$items['magdef'] = $item->getAttribute('MagicDefense');
			if($item->getAttribute('ReqLevel') != NULL)
				$items['lvlreq'] = $item->getAttribute('ReqLevel');
			if($item->getAttribute('ReqStrength') != NULL)
				$items['strreq'] = $item->getAttribute('ReqStrength');
			if($item->getAttribute('ReqDexterity') != NULL)
				$items['agireq'] = $item->getAttribute('ReqDexterity');
			if($item->getAttribute('ReqVitality') != NULL)
				$items['vitreq'] = $item->getAttribute('ReqVitality');
			if($item->getAttribute('ReqCommand') != NULL)
				$items['cmdreq'] = $item->getAttribute('ReqCommand');
			if($item->getAttribute('ReqEnergy') != NULL)
				$items['enereq'] = $item->getAttribute('ReqEnergy');
			if($item->getAttribute('IceRes') != NULL)
				$items['iceres'] = $item->getAttribute('IceRes');
			if($item->getAttribute('PoisonRes') != NULL)
				$items['poisonres'] = $item->getAttribute('PoisonRes');
			if($item->getAttribute('LightRes') != NULL)
				$items['lightres'] = $item->getAttribute('LightRes');
			if($item->getAttribute('FireRes') != NULL)
				$items['fireres'] = $item->getAttribute('FireRes');
			if($item->getAttribute('EarthRes') != NULL)
				$items['earthres'] = $item->getAttribute('EarthRes');
			if($item->getAttribute('WindRes') != NULL)
				$items['windres'] = $item->getAttribute('WindRes');
			if($item->getAttribute('WaterRes') != NULL)
				$items['waterres'] = $item->getAttribute('WaterRes');
			if($item->getAttribute('SetAttrib') != NULL)
				$items['setattrib'] = $item->getAttribute('SetAttrib');
			if($item->getAttribute('DarkWizard') != NULL)
				$items['dw/sm'] = $item->getAttribute('DarkWizard');
			if($item->getAttribute('DarkKnight') != NULL)
				$items['dk/bk'] = $item->getAttribute('DarkKnight');
			if($item->getAttribute('FairyElf') != NULL)
				$items['elf/me'] = $item->getAttribute('FairyElf');
			if($item->getAttribute('MagicGladiator') != NULL)
				$items['mg'] = $item->getAttribute('MagicGladiator');
			if($item->getAttribute('DarkLord') != NULL)
				$items['dl'] = $item->getAttribute('DarkLord');
			if($item->getAttribute('Summoner') != NULL)
				$items['sum'] = $item->getAttribute('Summoner');
			if($item->getAttribute('RageFighter') != NULL)
				$items['rf'] = $item->getAttribute('RageFighter');
			if($item->getAttribute('GrowLancer') != NULL)
				$items['gl'] = $item->getAttribute('GrowLancer');
			if($item->getAttribute('RuneWizard') != NULL)
				$items['rw'] = $item->getAttribute('RuneWizard');
			if($item->getAttribute('Slayer') != NULL)
				$items['sl'] = $item->getAttribute('Slayer');	
			if($item->getAttribute('GunCrusher') != NULL)
				$items['gc'] = $item->getAttribute('GunCrusher');	
			if($item->getAttribute('LightWizard') != NULL)		
				$items['km'] = $item->getAttribute('LightWizard');	
			if($item->getAttribute('LemuriaMage') != NULL)	
				$items['lm'] = $item->getAttribute('LemuriaMage');
			if($item->getAttribute('IllusionKnight') != NULL)	
				$items['ik'] = $item->getAttribute('IllusionKnight');
			if($item->getAttribute('SetAttrib') != NULL)
				$items['setattribute'] = $item->getAttribute('SetAttrib');
			if($item->getAttribute('Overlap') != NULL)
				$items['overlap'] = $item->getAttribute('Overlap');	
			
			return $items;
		}
		
		
		private function load_item_attributes_mudevs($item)
		{
			$items = [
				'id' => $item->getAttribute('Index'), 
				'slot' => $item->getAttribute('Slot'), 
				'skill' => $item->getAttribute('Skill'), 
				'x' => $item->getAttribute('Width'), 
				'y' => $item->getAttribute('Height'), 
				'option' => $item->getAttribute('Option'), 
				'name' => $item->getAttribute('Name'), 
				'lvldrop' => $item->getAttribute('Level'), 
				'kindA' => $item->getAttribute('ItemGroup'), 
				'kindB' => $item->getAttribute('KindA'),
				'type' => $item->getAttribute('KindB'),
				'dump' => $item->getAttribute('Dump')
			];
			if($item->getAttribute('Durability') != NULL)
				$items['dur'] = $item->getAttribute('Durability');
			if($item->getAttribute('DamageMin') != NULL)	
				$items['mindmg'] = $item->getAttribute('DamageMin');
			if($item->getAttribute('DamageMax') != NULL)	
				$items['maxdmg'] = $item->getAttribute('DamageMax');
			if($item->getAttribute('AttackSpeed') != NULL)	
				$items['attspeed'] = $item->getAttribute('AttackSpeed');
			if($item->getAttribute('MagicDurability') != NULL)	
				$items['magdur'] = $item->getAttribute('MagicDurability');
			if($item->getAttribute('WalkSpeed') != NULL)	
				$items['attspeed'] = $item->getAttribute('WalkSpeed');
			if($item->getAttribute('Defense') != NULL)	
				$items['def'] = $item->getAttribute('Defense');
			if($item->getAttribute('DefenseSuccessRate') != NULL)	
				$items['successblock'] = $item->getAttribute('DefenseSuccessRate');
			if($item->getAttribute('MagicDamageRate') != NULL)	
				$items['magpow'] = $item->getAttribute('MagicDamageRate');
			if($item->getAttribute('MagicDefense') != NULL)	
				$items['magdef'] = $item->getAttribute('MagicDefense');
			if($item->getAttribute('RequireLevel') != NULL)	
				$items['lvlreq'] = $item->getAttribute('RequireLevel');
			if($item->getAttribute('RequireStrength') != NULL)	
				$items['strreq'] = $item->getAttribute('RequireStrength');
			if($item->getAttribute('RequireDexterity') != NULL)	
				$items['agireq'] = $item->getAttribute('RequireDexterity');
			if($item->getAttribute('RequireVitality') != NULL)	
				$items['vitreq'] = $item->getAttribute('RequireVitality');
			if($item->getAttribute('RequireLeadership') != NULL)	
				$items['cmdreq'] = $item->getAttribute('RequireLeadership');
			if($item->getAttribute('RequireEnergy') != NULL)	
				$items['enereq'] = $item->getAttribute('RequireEnergy');
			if($item->getAttribute('Resistance0') != NULL)	
				$items['iceres'] = $item->getAttribute('Resistance0');
			if($item->getAttribute('Resistance1') != NULL)	
				$items['poisonres'] = $item->getAttribute('Resistance1');
			if($item->getAttribute('Resistance2') != NULL)	
				$items['lightres'] = $item->getAttribute('Resistance2');
			if($item->getAttribute('Resistance3') != NULL)	
				$items['fireres'] = $item->getAttribute('Resistance3');
			if($item->getAttribute('Resistance4') != NULL)	
				$items['earthres'] = $item->getAttribute('Resistance4');
			if($item->getAttribute('Resistance5') != NULL)	
				$items['windres'] = $item->getAttribute('Resistance5');
			if($item->getAttribute('Resistance6') != NULL)	
				$items['waterres'] = $item->getAttribute('Resistance6');
			if($item->getAttribute('DW') != NULL)	
				$items['dw/sm'] = $item->getAttribute('DW');
			if($item->getAttribute('DK') != NULL)	
				$items['dk/bk'] = $item->getAttribute('DK');
			if($item->getAttribute('FE') != NULL)	
				$items['elf/me'] = $item->getAttribute('FE');
			if($item->getAttribute('MG') != NULL)	
				$items['mg'] = $item->getAttribute('MG');
			if($item->getAttribute('DL') != NULL)	
				$items['dl'] = $item->getAttribute('DL');
			if($item->getAttribute('SU') != NULL)	
				$items['sum'] = $item->getAttribute('SU');
			if($item->getAttribute('RF') != NULL)	
				$items['rf'] = $item->getAttribute('RF');
			if($item->getAttribute('GL') != NULL)	
				$items['gl'] = $item->getAttribute('GL');
			if($item->getAttribute('RW') != NULL)	
				$items['rw'] = $item->getAttribute('RW');
			if($item->getAttribute('SL') != NULL)	
				$items['sl'] = $item->getAttribute('SL');
			if($item->getAttribute('GC') != NULL)		
				$items['gc'] = $item->getAttribute('GC');
			if($item->getAttribute('KM') != NULL)		
				$items['km'] = $item->getAttribute('KM');	
			if($item->getAttribute('LM') != NULL)	
				$items['lm'] = $item->getAttribute('LM');
			if($item->getAttribute('IK') != NULL)	
				$items['ik'] = $item->getAttribute('IK');
			if($item->getAttribute('ItemOverlap') != NULL)	
				$items['overlap'] = $item->getAttribute('ItemOverlap');	
			return $items;
		}

        private function set_language($lang){
            $this->lang = $lang;
        }

        private function check_file($file){
			if($file == 'SkillList.xml'){
				$this->file = APP_PATH . DS . 'data' . DS . 'ServerData/' . $file;
			}
			else{
				$this->file = APP_PATH . DS . 'data' . DS . 'ServerData/' . $this->lang . '/' . $file;
			}
            if(is_file($this->file)){
				$this->convertEncoding($this->file);
                return true; 
			}	
			else{
                $this->file = APP_PATH . DS . 'data' . DS . 'ServerData/en/' . $file;
                if(is_file($this->file)){
					$this->convertEncoding($this->file);
                    return true;
				}
            }
            return false;
        }
		
		
		private function convertEncoding($file){
			$ext = pathinfo($file, PATHINFO_EXTENSION);
			if($ext == 'csv'){
				$str = file_get_contents($file);
				$enc = mb_detect_encoding($str, 'auto', true);
				if($enc == false){
					$str = mb_convert_encoding($str, 'UTF-8', 'UCS-2LE'); 
					file_put_contents($file, $str);
				}
			}
		}
		
		public function parse_all(){
			$langs = [];
			$dir = new \DirectoryIterator(APP_PATH . DS . 'data' . DS . 'ServerData');
			foreach($dir as $fileinfo){
				if($fileinfo->isDir() && !$fileinfo->isDot()){
					$langs[] = $fileinfo->getFilename();
				}
			}
			
			if(!empty($langs)){
				foreach($langs AS $lang){
					$this->set_language($lang);
					$file_list_txt = [
						'exe_common', 
						'exe_wing', 
						'item_add_option', 
						'item_level_tooltip', 
						'item_set_option_text', 
						'item_tooltip', 
						'item_tooltip_text', 
						'jewel_of_harmony_option', 
						'socket_item', 
						'socket_item[6]'
					];
				   
					foreach($file_list_txt AS $type){
						if($this->parse_txt($type) != false){
							$this->cache->set($type . '#' . $this->lang, $this->info, $this->cache_time);
							$this->info = [];
						}
					}
					$item_xml = $this->parse_xml([0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 19, 20], 1);
					for($i = 0; $i <= 20; $i++){
						if($item_xml != false){
							if(isset($this->info[$i])){
								$this->cache->set('item_list[64][' . $i . ']' . '#' . $this->lang, $this->info[$i], $this->cache_time);
							}	
						}
					}
					$item_xml_mudevs = $this->parse_xml([0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 19, 20], 2);
					for($i = 0; $i <= 20; $i++){
						if($item_xml_mudevs != false){
							if(isset($this->info[$i])){
								$this->cache->set('item_list[50][' . $i . ']' . '#' . $this->lang, $this->info[$i], $this->cache_time);
							}
						}
					}
					$item_txt = $this->parse_item_txt();
					$cats = (MU_VERSION >= 11) ? 20 : 15;
					$size = ($this->isMuEngine == true) ? 58: 32;
					
					for($i = 0; $i <= $cats; $i++){
						if($item_txt != false){
							if(isset($this->info[$i])){
								$this->cache->set('item_list['.$size.'][' . $i . ']' . '#' . $this->lang, $this->info[$i], $this->cache_time);
							}
						}
					}
					
					$item_anc_type = $this->parse_xml_set_type();
					if($item_anc_type != false){
						$this->cache->set('item_set_type#' . $this->lang, $this->anctype, $this->cache_time);
					}
					
					$item_anc_opt = $this->parse_xml_set_options();
					if($item_anc_opt != false){
						$this->cache->set('item_set_option#' . $this->lang, $this->ancinfo, $this->cache_time);
					}
					
					$item_grade_opt = $this->parse_xml_item_grade();
					if($item_grade_opt != false){
						$this->cache->set('item_grade_option#' . $this->lang, $this->gradeinfo, $this->cache_time);
					}
					
					$item_muun_info = $this->parse_xml_muun_info();
					if($item_muun_info != false){
						$this->cache->set('muun_info#' . $this->lang, $this->muuninfo, $this->cache_time);
					}
					
					$item_muun_option_info = $this->parse_xml_muun_option();
					if($item_muun_option_info != false){
						$this->cache->set('muun_option_info#' . $this->lang, $this->muunoptioninfo, $this->cache_time);
					}
					
					$sockets = $this->parse_xml_socket_item_type();
					if($sockets != false){
						$this->cache->set('sockettype#' . $this->lang, $this->sockettype, $this->cache_time);
					}
					
					$earring = $this->parse_xml_earring_attribute();
					if($earring != false){
						$this->cache->set('earringtype#' . $this->lang, $this->earringtype, $this->cache_time);
						$this->cache->set('earringoption#' . $this->lang, $this->earringoption, $this->cache_time);
						$this->cache->set('earringoptionname#' . $this->lang, $this->earringoptionname, $this->cache_time);
					}

					$staticOptions = $this->parse_xml_static_option_system();
					if($staticOptions != false){
						$this->cache->set('staticitems#' . $this->lang, $this->staticitems, $this->cache_time);
						$this->cache->set('staticoptioninfo#' . $this->lang, $this->staticoptioninfo, $this->cache_time);
					}
					
					$poption = $this->parse_xml_pentagram_jewel_option_value();
					if($poption != false){
						$this->cache->set('pentagram_jewel_option_value#' . $this->lang, $this->penta_option_value, $this->cache_time);
					}
					
					$skill = $this->parse_xml_skill();
					if($skill != false){
						$this->cache->set('skill#' . $this->lang, $this->skill_data, $this->cache_time);
					}
					
					$monster_list = $this->parse_xml_monster_list();
					if($monster_list != false){
						$this->cache->set('mlist#' . $this->lang, $this->monsters, $this->cache_time);
					}
				}
			}
        }
    }