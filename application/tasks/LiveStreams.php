<?php

    class LiveStreams extends Job
    {
        private $registry, $load, $config, $vars = [];

        public function __construct(){
            $this->registry = controller::get_instance();
            $this->config = $this->registry->config;
            $this->load = $this->registry->load;
            $this->vars['api_url'] = 'https://api.twitch.tv/helix'; 
			
			
        }
        
        public function execute(){
			
            $this->load->helper('website');
            if($this->config->config_entry('main|cache_type') == 'file'){
                $this->load->lib('Cache/File as cache', [APP_PATH . DS . 'data' . DS . 'cache']);
            } 
			else{
                $this->load->lib('Cache/MemCached as cache',[$this->config->config_entry('main|mem_cached_ip'), $this->config->config_entry('main|mem_cached_port')]);
            }

			$streamingPartners = [];
			$streamingBox = [];
			
			if(defined('STREAM_BOX') && STREAM_BOX == true){
				if(defined('STREAMERS') && !empty(STREAMERS)){
					foreach(STREAMERS AS $user => $tags){
						array_push($streamingBox, [
							'dmn_twitch_link' => $user,
							'tags' => $tags
						]);
					}
				}
			}
			else{
				$this->load->model('partner');
				$serverlist = $this->registry->website->server_list();
				foreach($serverlist AS $key => $server){
					$streamers = $this->registry->Mpartner->listTwitchPartners($server);
					if(!empty($streamers)){
						foreach($streamers AS $user){
							array_push($streamingPartners, [
								'memb___id' => $user['memb___id'],
								'dmn_twitch_link' => $user['dmn_twitch_link'],
								'tags' => $user['dmn_twitch_tags']
							]);
						}
					}
				}
			}

			$data = [];
			
			if(!empty($streamingPartners)){
				$token = $this->registry->website->authTwitch();
				foreach($streamingPartners AS $channel){
					$url = $this->vars['api_url'].'/streams/?user_login='. $channel['dmn_twitch_link'];
					$checkStream = json_decode($this->registry->website->curlTwitch($url, $token['access_token']), true);
					$data[$channel['dmn_twitch_link']] = [];
					
					if(!empty($checkStream['data'])){
						$tags = [];
						if($channel['tags'] != NULL){
							if(strpos($channel['tags'], ',') !== false) {
								$tags = explode(',', $channel['tags']);
							}
							else{
								$tags[0] = $channel['tags'];
							}
						}
						$skip = true;
						if(!empty($tags) && !empty($checkStream['data'][0]['tags'])){
							$tagsCompare = array_intersect($checkStream['data'][0]['tags'], $tags);
							if(empty($tagsCompare)){
								$skip = false;
							}
						}
						if($skip){
							$streamStarted = strtotime($checkStream['data'][0]['started_at']);
							$date_utc = new \DateTime("now", new \DateTimeZone("UTC"));
							$current_stream_time = $date_utc->getTimestamp() - $streamStarted;
							$streamLog =  $this->registry->Mpartner->findStreamingLog($channel['memb___id'], $streamStarted);
							if($streamLog == false){
								$this->registry->Mpartner->addStreamLog($channel['memb___id'], $streamStarted, $current_stream_time);
							}
							else{
								$this->registry->Mpartner->updateStreamLog($current_stream_time, $streamLog['id']);
							}

							$urlProfile = $this->vars['api_url'].'/users/?id='. $checkStream['data'][0]['user_id'];
							$checkProfile = json_decode($this->registry->website->curlTwitch($urlProfile, $token['access_token']), true);

							$data[$channel['dmn_twitch_link']] = [
								'url' => 'https://www.twitch.tv/'.$channel['dmn_twitch_link'],
								'image' => str_replace('{width}x{height}',''.TWITCH_THUMBNAIL_SIZE.'x'.TWITCH_THUMBNAIL_SIZE.'', $checkStream['data'][0]['thumbnail_url']),
								'profile_image' => isset($checkProfile['data'][0]) ? $checkProfile['data'][0]['profile_image_url'] : ''
							];
						}
					}
				}
			}
			
			if(!empty($streamingBox)){
				$token = $this->registry->website->authTwitch();
				foreach($streamingBox AS $channel){
					$url = $this->vars['api_url'].'/streams/?user_login='. $channel['dmn_twitch_link'];
					$checkStream = json_decode($this->registry->website->curlTwitch($url, $token['access_token']), true);
					$data[$channel['dmn_twitch_link']] = [];
					
					if(!empty($checkStream['data'])){
						$tags = [];
						if($channel['tags'] != NULL){
							if(strpos($channel['tags'], ',') !== false) {
								$tags = explode(',', $channel['tags']);
							}
							else{
								$tags[0] = $channel['tags'];
							}
						}
						$skip = true;
						if(!empty($tags) && !empty($checkStream['data'][0]['tags'])){
							$tagsCompare = array_intersect($checkStream['data'][0]['tags'], $tags);
							if(empty($tagsCompare)){
								$skip = false;
							}
						}
						if($skip){
							$urlProfile = $this->vars['api_url'].'/users/?id='. $checkStream['data'][0]['user_id'];
							$checkProfile = json_decode($this->registry->website->curlTwitch($urlProfile, $token['access_token']), true);
							
							$urlVideo = $this->vars['api_url'].'/videos/?user_id='. $checkStream['data'][0]['user_id'];
							$checkVideos = json_decode($this->registry->website->curlTwitch($urlVideo, $token['access_token']), true);

							$data[$channel['dmn_twitch_link']] = [
								'url' => 'https://www.twitch.tv/'.$channel['dmn_twitch_link'],
								'image' => str_replace('{width}x{height}',''.STREAM_BOX_THUMBNAIL_SIZE_W.'x'.STREAM_BOX_THUMBNAIL_SIZE_H.'', $checkStream['data'][0]['thumbnail_url']),
								'profile_image' => isset($checkProfile['data'][0]) ? $checkProfile['data'][0]['profile_image_url'] : '',
								'user' => $checkStream['data'][0]['user_name'],
								'video' => isset($checkVideos['data'][0]) ? $checkVideos['data'][0]['id'] : ''
							];
						}
					}
				}
			}
			
			$this->registry->cache->set('twitch_streamer_data', $data);
        } 
    }