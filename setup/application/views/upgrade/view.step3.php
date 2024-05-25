<?php
    $this->load->view('setup' . DS . 'application' . DS . 'views' . DS . 'view.header');
?>
    <div id="wrapper">
    <div id="sidebar-wrapper">
        <div style="text-align:center;margin-top:10px;">
            <img src="<?php echo $this->config->base_url; ?>../assets/default_assets/images/cms_logo.png" style="width:100px;" />
        </div>
        <ul class="sidebar-nav">
            <li class="sidebar-brand">Upgrade</li>
            <li>
                <a href="<?php echo $this->config->base_url; ?>index.php?action=upgrade">Check Credentials</a>
            </li>
            <li>
                <a href="#">Requirements</a>
            </li>
            <li>
                <a href="#">Check Version</a>
            </li>
            <li class="active">
                <a href="#">Run Upgrade</a>
            </li>
            <li>
                <a href="#">Upgrade Completed</a>
            </li>
        </ul>
    </div>
    <div id="page-content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-10">
                    <div class="page-header">
                        <h1>Upgrade Progress</h1>
                    </div>
                    <div class="progress">
                        <div id="progress" class="progress-bar progress-bar-success" role="progressbar"
                             aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 50%;">
                            50%
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-10">
                    <div class="page-header">
                        <h1>Run Upgrade</h1>
                    </div>
                    <div class="panel panel-info">
                        <div class="panel-heading">
                            <h3 class="panel-title">Upgrade DmN MuCMS
                                from <?php echo $current_version; ?> to
                                version <?php echo $available_local_version; ?></h3>
                        </div>
                        <div class="panel-body">
                            <form name="upgrade_data_form" id="upgrade_data_form" method="POST" action="">
                                <div class="form-group">
                                    <label for="mu_version">Server Version:</label>
                                    <select class="form-control" id="mu_version" name="mu_version">
                                        <option value="-1">Select</option>
                                        <?php foreach($this->Msetup->mu_versions() AS $key => $version): ?>
                                            <option value="<?php echo $key; ?>" <?php if(defined('MU_VERSION') && $key == MU_VERSION){
                                                echo 'selected="selected"';
                                            } ?>><?php echo $version; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" name="submit_upgrade_data"
                                        class="btn btn-success btn-lg btn-block">Run Upgrade
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="push"></div>
<?php
    $this->load->view('setup' . DS . 'application' . DS . 'views' . DS . 'view.footer');
?>