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
            <li class="active">
                <a href="#">Check Version</a>
            </li>
            <li>
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
                        <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="20"
                             aria-valuemin="0" aria-valuemax="100" style="width: 20%;">
                            20%
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-10">
                    <div class="page-header">
                        <h1>Check Version</h1>
                    </div>
                    <div class="panel panel-info">
                        <div class="panel-heading">
                            <h3 class="panel-title">Upgrade DmN MuCMS
                                from <?php echo $current_version; ?> to
                                version <?php echo ($version != '') ? $version : $available_local_version; ?></h3>
                        </div>
                        <div class="panel-body">
                            <?php
                                if(isset($errors)):
                                    foreach($errors as $error):
                                        echo '<div class="alert alert-danger" role="alert">' . $error . '</div>';
                                    endforeach;
                                else:
                                    if(isset($success)):
                                        foreach($success as $message):
                                            echo '<div class="alert alert-success" role="alert">' . $message . '</div>';
                                        endforeach;
                                    endif;
                                    ?>
                                    <a href="<?php echo $this->config->base_url; ?>index.php?action=upgrade/step3"
                                       class="btn btn-success btn-lg btn-block">Continue</a>
                                <?php
                                endif;
                            ?>
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