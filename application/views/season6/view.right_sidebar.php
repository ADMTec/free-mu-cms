<div id="sidebar">
    <div class="box-style2">
        <div class="title2">
            <h2><?php echo __('Account Panel'); ?></h2>
        </div>
        <div class="entry">
            <?php
                if($this->session->userdata(['user' => 'logged_in'])):
                    $credits = $this->website->get_user_credits_balance($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), 1, $this->session->userdata(['user' => 'id']));
                    $credits2 = $this->website->get_user_credits_balance($this->session->userdata(['user' => 'username']), $this->session->userdata(['user' => 'server']), 2, $this->session->userdata(['user' => 'id']));
                    ?>
                    <ul class="style4">
                        <li class="first" style="text-align: center;"><?php echo __('Welcome'); ?>
                            , <?php echo $this->session->userdata(['user' => 'username']); ?></li>
                        <li class="first" style="text-align: center;">
                            <a href="<?php echo $this->config->base_url; ?>account-panel"><img class="avatar_frame"
                                                                                               src="<?php echo $this->config->base_url; ?>assets/<?php echo $this->config->config_entry('main|template'); ?>/images/avatar.jpg"/></a>
                        </li>
                        <li><?php echo __('Current Server'); ?>:
                            <span><?php echo $this->session->userdata(['user' => 'server_t']); ?> <img
                                        data-modal-div="select_server"
                                        src="<?php echo $this->config->base_url; ?>assets/<?php echo $this->config->config_entry('main|template'); ?>/images/switch.png"
                                        style="width: 13px;cursor: pointer;"/></span></li>
                        <li class="w-coins"><?php echo __('My'); ?> <?php echo $this->config->config_entry('credits_' . $this->session->userdata(['user' => 'server']) . '|title_1'); ?>
                            : <span id="my_credits"><?php echo number_format($credits['credits']); ?></span></li>
                        <li class="zz-coins"><?php echo __('My'); ?> <?php echo $this->config->config_entry('credits_' . $this->session->userdata(['user' => 'server']) . '|title_2'); ?>
                            : <span id="my_gold_credits"><?php echo number_format($credits2['credits']); ?></span></li>
                        <?php
                            if($this->config->values('wcoin_exchange_config', [$this->session->userdata(['user' => 'server']), 'display_wcoins']) == 1):
                                $wcoin = $this->website->get_account_wcoins_balance($this->session->userdata(['user' => 'server']));
                                ?>
                                <li class="w-coins"><?php echo __('My'); ?> <?php echo __('WCoins'); ?>: <span
                                            id="my_credits"><?php echo number_format($wcoin); ?></span></li>
                            <?php endif; ?>
                        <li><a href="<?php echo $this->config->base_url; ?>donate"><?php echo __('Buy Credits'); ?></a></li>
                        <li><a href="<?php echo $this->config->base_url; ?>shop"><?php echo __('Shop'); ?></a></li>
                        <li><a href="<?php echo $this->config->base_url; ?>shop/cart"><?php echo __('My Cart'); ?></a></li>
                        <li><a href="<?php echo $this->config->base_url; ?>market"><?php echo __('Market'); ?></a></li>
                        <li><a href="<?php echo $this->config->base_url; ?>warehouse"><?php echo __('Warehouse'); ?></a>
                        </li>
                        <li>
                            <a href="<?php echo $this->config->base_url; ?>account-panel"><?php echo __('Account Panel'); ?></a>
                        </li>
                        <li>
                            <a href="<?php echo $this->config->base_url; ?>account-logs"><?php echo __('Account Logs'); ?></a>
                        </li>
                        <li><a href="<?php echo $this->config->base_url; ?>settings"><?php echo __('Settings'); ?></a></li>
                        <?php
                            $plugins = $this->config->plugins();
                            if(!empty($plugins)):
                                if(array_key_exists('merchant', $plugins)){
                                    if($this->session->userdata(['user' => 'is_merchant']) != 1){
                                        unset($plugins['merchant']);
                                    }
                                }
                                foreach($plugins AS $key => $plugin):
                                    if($plugin['installed'] == 1 && $plugin['sidebar_user_item'] == 1):
										if(mb_substr($plugin['module_url'], 0, 4) !== "http"){
											$plugin['module_url'] = $this->config->base_url . $plugin['module_url'];
										}
                                        ?>
                                        <li>
                                            <a href="<?php echo $plugin['module_url']; ?>"><?php echo __($plugin['about']['name']); ?></a>
                                        </li>
                                    <?php
                                    endif;
                                endforeach;
                            endif;
                        ?>
                    </ul>
                    <span style="float:right;"><a style="text-decoration:underline"
                                                  href="<?php echo $this->config->base_url; ?>logout"><?php echo __('Logout'); ?></a></span>
                <?php
                else:
                    ?>
                    <div style="text-align:center;margin:0 auto;">
                        <div><?php echo $this->website->fb_login(); ?></div>
                        <form id="login_form" method="post" action="<?php echo $this->config->base_url; ?>">
                            <input type="text" name="username" id="login_input" maxlength="10" class="input-main"
                                   style="width:182px;" placeholder="<?php echo __('Username'); ?>" value=""/>
                            <input type="password" name="password" id="password_input" maxlength="20" class="input-main"
                                   style="width:182px;" placeholder="<?php echo __('Password'); ?>" value=""/>
							<?php if($this->config->values('security_config', 'captcha_on_login') == 1){ ?>	   
							 <input type="text" name="captcha" id="captcha_input" maxlength="20" class="input-main"
                                   style="width:182px;" placeholder="<?php echo __('Captcha'); ?>" value=""/>	   
							 <img src="<?php echo $this->config->base_url; ?>ajax/captcha" alt="CAPTCHA" id="captcha_image" />
							<?php } ?>	
                            <div style="margin-left:15px">
                                <input type="submit" id="submit" value="<?php echo __('Login'); ?>" class="button-style"
                                       style="border:none;cursor:pointer "/>
                            </div>
                        </form>
                        <a href="<?php echo $this->config->base_url; ?>lost-password"><?php echo __('Lost Password'); ?>
                            ?</a>&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;<a
                                href="<?php echo $this->config->base_url; ?>registration"><?php echo __('Registration'); ?></a><br/>
                    </div>
                <?php
                endif;
            ?>
            <br/><br/>
        </div>
    </div>
    <div class="box-style2">
        <div class="title2">
            <h2><?php echo __('Information'); ?></h2>
        </div>
        <div class="entry">
            <ul class="style4">
                <li class="first"><a
                            href="<?php echo $this->config->base_url; ?>about"><?php echo __('About Server'); ?></a>
                </li>
                <li><a href="<?php echo $this->config->base_url; ?>rules"><?php echo __('Rules'); ?></a></li>
                <li><a href="<?php echo $this->config->config_entry('main|forum_url'); ?>"
                       target="_blank"><?php echo __('Forum'); ?></a></li>
                <li><a href="<?php echo $this->config->base_url; ?>donate"><?php echo __('Donate'); ?></a></li>
                <li><a href="<?php echo $this->config->base_url; ?>rankings/gm-list"><?php echo __('GM List'); ?></a>
                </li>
                <li><a href="<?php echo $this->config->base_url; ?>rankings/ban-list"><?php echo __('Ban List'); ?></a>
                </li>
                <li><a href="<?php echo $this->config->base_url; ?>guides"><?php echo __('Guides'); ?></a></li>
                <li><a href="<?php echo $this->config->base_url; ?>support"><?php echo __('Contact Us'); ?></a></li>
                <?php
                    $plugins = $this->config->plugins();
                    if(!empty($plugins)):
                        foreach($plugins AS $plugin):
                            if($plugin['installed'] == 1 && $plugin['sidebar_public_item'] == 1):
								if(mb_substr($plugin['module_url'], 0, 4) !== "http"){
									$plugin['module_url'] = $this->config->base_url . $plugin['module_url'];
								}
                                ?>
                                <li>
                                    <a href="<?php echo $plugin['module_url']; ?>"><?php echo __($plugin['about']['name']); ?></a>
                                </li>
                            <?php
                            endif;
                        endforeach;
                    endif;
                ?>
            </ul>
        </div>
    </div>
    <?php if($this->config->config_entry('modules|last_market_items_module') == 1): ?>
        <div class="box-style2">
            <div class="title2">
                <h2><?php echo __('Market'); ?></h2>
            </div>
            <div class="entry">
                <?php
                    if($this->session->userdata(['user' => 'logged_in'])):
                        $server = $this->session->userdata(['user' => 'server']);
                    else:
                        $server = array_key_first($this->website->server_list());
                    endif;
                ?>
                <script>
                    $(document).ready(function () {
                        App.loadLatestItems('<?php echo $server;?>', <?php echo $this->config->config_entry('modules|last_market_items_count');?>, 20);
                    });
                </script>
                <div id="lattest_items"></div>
                <br/>
                <span style="float:right;margin-right:28px">
			<?php
                foreach($this->website->server_list() as $key => $server):
                    if($server['visible'] == 1):
                        ?>
                        <a href="#" id="switch_items_<?php echo $key; ?>"
                           data-count="<?php echo $this->config->config_entry('modules|last_market_items_count'); ?>"
                           data-limit="20"><?php echo $server['title']; ?></a>
                    <?php
                    endif;
                endforeach;
            ?>
			</span>
                <br/><br/>
            </div>
        </div>
    <?php
    endif;
        $ranking_config = $this->config->values('rankings_config');
        $i = 0;
        foreach($ranking_config AS $srv => $data){
			
            if($data['active'] == 1){
                if(isset($data['player']) && $data['player']['is_sidebar_module'] == 1){
                    echo '<div class="box-style2">
							<div class="title2">
								<h2>' . __('Top Players') . '</h2>
							</div>
							<div class="entry">
								<script>
								$(document).ready(function () {
									App.populateSidebarRanking(\'players\', \'' . $srv . '\', ' . $data['player']['count_in_sidebar'] . ');
								});
								</script>
								<div id="top_players"></div>
								<br/>
							<span style="float:right;margin-right:28px">';
                    foreach($this->website->server_list() as $key => $server){
                        if($server['visible'] == 1 && isset($ranking_config[$key]['player'])){
                            echo '<a href="#" id="switch_top_players_' . $key . '" data-count="' . $ranking_config[$key]['player']['count_in_sidebar'] . '">' . $server['title'] . '</a> ';
                        }
                    }
                    echo '</span><br/><br/></div></div>';
                }
                if(isset($data['guild']) && $data['guild']['is_sidebar_module'] == 1){
                    echo '<div class="box-style2">
							<div class="title2">
								<h2>' . __('Top Guilds') . '</h2>
							</div>
							<div class="entry">
								<script>
								$(document).ready(function () {
									App.populateSidebarRanking(\'guilds\', \'' . $srv . '\', ' . $data['guild']['count_in_sidebar'] . ');
								});
								</script>
								<div id="top_guilds"></div>
								<br/>
							<span style="float:right;margin-right:28px">';
                    foreach($this->website->server_list() as $key => $server){
                        if($server['visible'] == 1 && isset($ranking_config[$key]['guild'])){
                            echo '<a href="#" id="switch_top_guilds_' . $key . '" data-count="' . $ranking_config[$key]['guild']['count_in_sidebar'] . '">' . $server['title'] . '</a> ';
                        }
                    }
                    echo '</span><br/><br/></div></div>';
                }
                if(isset($data['voter']) && $data['voter']['is_sidebar_module'] == 1){
                    echo '<div class="box-style2">
							<div class="title2">
								<h2>' . __('Top Voters') . '</h2>
							</div>
							<div class="entry">
								<script>
								$(document).ready(function () {
									App.populateSidebarRanking(\'votereward\', \'' . $srv . '\', ' . $data['voter']['count_in_sidebar'] . ');
								});
								</script>
								<ul class="style4" id="top_votereward"></ul>
								<br/>
							<span style="float:right;margin-right:28px">';
                    foreach($this->website->server_list() as $key => $server){
                        if($server['visible'] == 1 && isset($ranking_config[$key]['voter'])){
                            echo '<a href="#" id="switch_top_votereward_' . $key . '" data-count="' . $ranking_config[$key]['voter']['count_in_sidebar'] . '">' . $server['title'] . '</a> ';
                        }
                    }
                    echo '</span><br/><br/></div></div>';
                }
                if(isset($data['killer']) && $data['killer']['is_sidebar_module'] == 1){
                    echo '<div class="box-style2">
							<div class="title2">
								<h2>' . __('Top Killers') . '</h2>
							</div>
							<div class="entry">
								<script>
								$(document).ready(function () {
									App.populateSidebarRanking(\'killer\', \'' . $srv . '\', ' . $data['killer']['count_in_sidebar'] . ');
								});
								</script>
								<div id="top_killer"></div>
								<br/>
							<span style="float:right;margin-right:28px">';
                    foreach($this->website->server_list() as $key => $server){
                        if($server['visible'] == 1 && isset($ranking_config[$key]['killer'])){
                            echo '<a href="#" id="switch_top_killer_' . $key . '"  data-count="' . $ranking_config[$key]['killer']['count_in_sidebar'] . '">' . $server['title'] . '</a> ';
                        }
                    }
                    echo '</span><br/><br/></div></div>';
                }
                if(isset($data['online']) && $data['online']['is_sidebar_module'] == 1){
                    echo '<div class="box-style2">
							<div class="title2">
								<h2>' . __('Top Online') . '</h2>
							</div>
							<div class="entry">
								<script>
								$(document).ready(function () {
									App.populateSidebarRanking(\'online\', \'' . $srv . '\', ' . $data['online']['count_in_sidebar'] . ');
								});
								</script>
								<div id="top_online"></div>
								<br/>
							<span style="float:right;margin-right:28px">';
                    foreach($this->website->server_list() as $key => $server){
                        if($server['visible'] == 1 && isset($ranking_config[$key]['online'])){
                            echo '<a href="#" id="switch_top_online_' . $key . '" data-count="' . $ranking_config[$key]['online']['count_in_sidebar'] . '">' . $server['title'] . '</a> ';
                        }
                    }
                    echo '</span><br/><br/></div></div>';
                }
                if(isset($data['bc']) && $data['bc']['is_sidebar_module'] == 1){
                    echo '<div class="box-style2">
							<div class="title2">
								<h2>' . __('Top BC') . '</h2>
							</div>
							<div class="entry">
								<script>
								$(document).ready(function () {
									App.populateSidebarRanking(\'bc\', \'' . $srv . '\', ' . $data['bc']['count_in_sidebar'] . ');
								});
								</script>
								<div id="top_bc"></div>
								<br/>
							<span style="float:right;margin-right:28px">';
                    foreach($this->website->server_list() as $key => $server){
                        if($server['visible'] == 1 && isset($ranking_config[$key]['bc'])){
                            echo '<a href="#" id="switch_top_bc_' . $key . '" data-count="' . $ranking_config[$key]['bc']['count_in_sidebar'] . '">' . $server['title'] . '</a> ';
                        }
                    }
                    echo '</span><br/><br/></div></div>';
                }
                if(isset($data['ds']) && $data['ds']['is_sidebar_module'] == 1){
                    echo '<div class="box-style2">
							<div class="title2">
								<h2>' . __('Top DS') . '</h2>
							</div>
							<div class="entry">
								<script>
								$(document).ready(function () {
									App.populateSidebarRanking(\'ds\', \'' . $srv . '\', ' . $data['ds']['count_in_sidebar'] . ');
								});
								</script>
								<div id="top_ds"></div>
								<br/>
							<span style="float:right;margin-right:28px">';
                    foreach($this->website->server_list() as $key => $server){
                        if($server['visible'] == 1 && isset($ranking_config[$key]['ds'])){
                            echo '<a href="#" id="switch_top_ds_' . $key . '" data-count="' . $ranking_config[$key]['ds']['count_in_sidebar'] . '">' . $server['title'] . '</a> ';
                        }
                    }
                    echo '</span><br/><br/></div></div>';
                }
                if(isset($data['cc']) && $data['cc']['is_sidebar_module'] == 1){
                    echo '<div class="box-style2">
							<div class="title2">
								<h2>' . __('Top CC') . '</h2>
							</div>
							<div class="entry">
								<script>
								$(document).ready(function () {
									App.populateSidebarRanking(\'cc\', \'' . $srv . '\', ' . $data['cc']['count_in_sidebar'] . ');
								});
								</script>
								<div id="top_cc"></div>
								<br/>
							<span style="float:right;margin-right:28px">';
                    foreach($this->website->server_list() as $key => $server){
                        if($server['visible'] == 1 && isset($ranking_config[$key]['cc'])){
                            echo '<a href="#" id="switch_top_cc_' . $key . '" data-count="' . $ranking_config[$key]['cc']['count_in_sidebar'] . '">' . $server['title'] . '</a> ';
                        }
                    }
                    echo '</span><br/><br/></div></div>';
                }
                if(isset($data['duels']) && $data['duels']['is_sidebar_module'] == 1){
                    echo '<div class="box-style2">
							<div class="title2">
								<h2>' . __('Top Duelers') . '</h2>
							</div>
							<div class="entry">
								<script>
								$(document).ready(function () {
									App.populateSidebarRanking(\'duels\', \'' . $srv . '\', ' . $data['duels']['count_in_sidebar'] . ');
								});
								</script>
								<div id="top_duels"></div>
								<br/>
							<span style="float:right;margin-right:28px">';
                    foreach($this->website->server_list() as $key => $server){
                        if($server['visible'] == 1 && isset($ranking_config[$key]['duels'])){
                            echo '<a href="#" id="switch_top_duels_' . $key . '" data-count="' . $ranking_config[$key]['duels']['count_in_sidebar'] . '">' . $server['title'] . '</a> ';
                        }
                    }
                    echo '</span><br/><br/></div></div>';
                }
                $i++;
                if($i == 1){
                    break;
                }
            }
        }
        if($this->config->config_entry('modules|recent_forum_module') == 1): ?>
            <div class="box-style2">
                <div class="title2">
                    <h2><?php echo __('Recent Forum Topics'); ?></h2>
                </div>
                <div class="entry">
                    <div style="padding-left: 10px;">
                        <?php
                            if($load_rss = $this->website->load_rss($this->config->config_entry('modules|recent_forum_rss_url'), $this->config->config_entry('modules|recent_forum_rss_count'), $this->config->config_entry('modules|recent_forum_rss_cache_time'))){
                                ?>
                                <ul class="style4">
                                    <?php
                                        foreach($load_rss as $key => $rss):
                                            $first = ($key == 0) ? 'class="first"' : '';
                                            ?>
                                            <li <?php echo $first; ?>><a
                                                        href="<?php echo $rss['link']; ?>"><?php echo $rss['title']; ?></a></li>
                                        <?php
                                        endforeach;
                                    ?>
                                </ul>
                                <?php
                            } else{
                                echo '<div class="i_note">' . __('No Data') . '</div>';
                            }
                        ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php if($this->config->values('event_config', ['events', 'active']) == 1): ?>
        <div class="box-style2">
            <div class="title2">
                <h2><?php echo __('Events'); ?></h2>
            </div>
            <div class="entry">
                <div id="events"></div>
                <script type="text/javascript">
                    $(document).ready(function () {
                        App.getEventTimes();
                    });
                </script>
            </div>
        </div>
    <?php endif; ?>
</div>