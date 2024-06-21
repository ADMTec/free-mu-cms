<?php
    $this->load->view('admincp' . DS . 'view.header');
    $this->load->view('admincp' . DS . 'view.sidebar');
?>
<div id="content" class="span10">
    <div>
        <ul class="breadcrumb">
            <li><a href="<?php echo $this->config->base_url; ?>admincp">Home</a> <span class="divider">/</span></li>
            <li><a href="<?php echo $this->config->base_url; ?>admincp/manage-plugins">Manage Plugins</a></li>
        </ul>
    </div>
    <?php $server_list = ($is_multi_server == 0) ? ['all' => ['title' => 'All']] : $this->website->server_list(); ?>
    <div class="row-fluid">
        <div class="span12">
            <ul class="nav nav-pills">
                <?php
                $i = 0;
                foreach($server_list AS $key => $val):
                $i++;
                ?>
                <li role="presentation" <?php if($i == 1){ ?> class="active"<?php } ?>><a href="#<?php echo $key; ?>" aria-controls="<?php echo $key; ?>" role="tab" data-toggle="tab"><?php echo $val['title']; ?> Server Settings</a></li>
                <?php endforeach; ?>
            </ul>
            <div class="clearfix"></div>
        </div>
    </div>
    <?php if(isset($js)): ?>
        <script src="<?php echo $js; ?>"></script>
        <script type="text/javascript">
            var pluginJs = new pluginJs();
            pluginJs.setUrl('<?php echo $this->config->base_url . $this->request->get_controller();?>');
            $(document).ready(function () {
                $('form[id^="settings_form_"]').on("submit", function (e) {
                    e.preventDefault();
                    pluginJs.saveSettings($(this));
                });
            });
        </script>
    <?php endif; ?>
    <div class="row-fluid">
        <div class="box span12">
            <div class="tab-content">
                <?php
                    $i = 0;
                    foreach($server_list AS $key => $data):
                        $val = ($is_multi_server == 0) ? $plugin_config : (isset($plugin_config[$key]) ? $plugin_config[$key] : false);
                        $i++;
                        ?>
                        <div role="tabpanel" class="tab-pane fade in <?php if($i == 1){ ?>active<?php } ?>"
                             id="<?php echo $key; ?>">
                            <div class="box-header well">
                                <h2><i class="icon-edit"></i> <?php echo $data['title']; ?> Server Settings</h2>
                            </div>
                            <div class="box-content">
                                <form class="form-horizontal" method="POST" action="" id="settings_form_<?php echo $key; ?>">
                                    <input type="hidden" id="server" name="server" value="<?php echo $key; ?>"/>

                                    <div class="control-group">
                                        <label class="control-label" for="active">Status </label>

                                        <div class="controls">
                                            <select id="active" name="active" required>
                                                <option value="0" <?php if($val['active'] == 0){
                                                    echo 'selected="selected"';
                                                } ?>>Inactive
                                                </option>
                                                <option value="1" <?php if($val['active'] == 1){
                                                    echo 'selected="selected"';
                                                } ?>>Active
                                                </option>
                                            </select>

                                            <p class="help-block">Use module.</p>
                                        </div>
                                    </div>
                                    <div class="form-actions">
                                        <button type="submit" class="btn btn-primary" name="edit_settings" id="edit_settings">Save changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php
    $this->load->view('admincp' . DS . 'view.footer');
?>
