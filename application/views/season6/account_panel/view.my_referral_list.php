<?php
    $this->load->view($this->config->config_entry('main|template') . DS . 'view.header');
?>
<div id="content">
    <div id="box1">
        <div class="title1">
            <h1><?php echo __('Referral System'); ?></h1>
        </div>
        <?php if($this->config->values('referral_config', 'active') == 1): ?>
            <div class="box-style1" style="margin-bottom: 20px;">
                <h2 class="title"><?php echo __('Refferal Link'); ?></h2>
                <div class="entry">
                    <div id="ucp_info">
                        <div class="full">
                            <table width="99%" style="padding:3px;">
                                <tr>
                                    <td><input type="text"
                                               value="<?php echo $this->config->base_url; ?>registration/index/<?php echo $this->session->userdata(['user' => 'id']); ?>/<?php echo $this->session->userdata(['user' => 'server']); ?>"
                                               class="text" style="width: 100%;" onclick="select(this);"/></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="box-style1" style="margin-bottom: 20px;">
                <h2 class="title"><?php echo __('View your referred players'); ?></h2>
                <div class="entry">
                    <script>
                        $(document).ready(function () {
                            $('a[id^="ref-reward-"]').on('click', function (e) {
                                e.preventDefault();
                                var char = $(this).attr("id").split("-")[2];
                                var reward = $(this).attr("id").split("-")[3];
                                App.addRefferalReward(char, reward);
                            });
                        });
                    </script>
                    <?php
                        if(!empty($my_referral_list)){
                            foreach($my_referral_list as $refs){
                                ?>
                                <table class="add_to_card" cellspacing="0">
                                    <thead>
                                    <th><?php echo __('Referral'); ?></th>
                                    <th><?php echo __('Date Invited'); ?></th>
                                    </thead>
                                    <tbody>
                                    <tr>
                                        <td><?php echo $refs['refferal']; ?></td>
                                        <td><?php echo date(DATETIME_FORMAT, strtotime($refs['date_reffered'])); ?></td>
                                    </tr>
                                    <?php
                                        if(!empty($refs['ref_chars'])){
                                            echo '<tr><td colspan="2" style="text-align:left;">' . __('Characters.') . '</td></tr>';
                                            foreach($refs['ref_chars'] AS $key => $char){
                                                ?>
                                                <tr>
                                                    <td style="text-align:left;">
                                                        <?php echo $char['Name']; ?>
                                                    </td>
                                                    <td>
                                                        <table style="border-collapse: collapse;border:0;">
                                                            <tr>
                                                                <td><?php echo __('Level'); ?>
                                                                    : <?php echo $char['cLevel']; ?></td>
                                                                <td><?php echo __('Resets'); ?>
                                                                    : <?php echo $char['resets']; ?></td>
                                                                <td><?php echo __('GResets'); ?>
                                                                    : <?php echo $char['grand_resets']; ?></td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="text-align:left;"><?php echo __('Achievable rewards'); ?></td>
                                                    <td>
                                                        <?php
                                                            if(!empty($ref_rewards)){
                                                                $reward_count = count($ref_rewards);
                                                                $claimed_reward_count = 0;
                                                                $html = '<div class="ref-reward-dropdown"><button class="custom_button">' . __('View Reward List') . '</button><div class="ref-reward-dropdown-content">';
                                                                foreach($ref_rewards AS $k => $value){
                                                                    $reward = '<p>' . __('Req LvL') . ': ' . $value['required_lvl'] . '</p>';
                                                                    if($value['required_res'] > 0){
                                                                        $reward .= '<p>' . __('Req Resets') . ': ' . $value['required_res'] . '</p>';
                                                                    }
                                                                    if($value['required_gres'] > 0){
                                                                        $reward .= '<p>' . __('Req GResets') . ': ' . $value['required_gres'] . '</p>';
                                                                    }
                                                                    if($this->Maccount->check_claimed_referral_rewards($this->session->userdata(['user' => 'username']), $value['id'], $char['Name'], $value['server']) != false){
                                                                        $reward .= '<p>' . __('Already Claimed') . '</p>';
                                                                    } else{
                                                                        $reward .= '<p>' . __('Reward') . ': ' . $value['reward'] . ' ' . $this->website->translate_credits($value['reward_type'], $value['server']) . '</p>';
                                                                    }
                                                                    $html .= '<a id="ref-reward-' . $char['Name'] . '-' . $value['id'] . '" href="#" data-info="' . $reward . '">' . __('Reward') . ': ' . ($k + 1) . '</a>';
                                                                }
                                                                $html .= '</div></div>';
                                                            } else{
                                                                $html = __('No rewards achievable');
                                                            }
                                                            echo $html;
                                                        ?>
                                                    </td>
                                                </tr>
                                                <?php
                                            }
                                        }
                                    ?>
                                    </tbody>
                                </table>
                                <br/>
                                <?php
                            }
                        } else{
                            ?>
                            <div class="i_note"><?php echo __('No referred players.'); ?></div>
                            <?php
                        }
                    ?>
                </div>
            </div>
        <?php else: ?>
            <div class="box-style1" style="margin-bottom: 20px;">
                <div class="entry"
                >
                    <div class="i_note"><?php echo __('This module has been disabled.'); ?></div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php
    $this->load->view($this->config->config_entry('main|template') . DS . 'view.right_sidebar');
    $this->load->view($this->config->config_entry('main|template') . DS . 'view.footer');
?>
	
