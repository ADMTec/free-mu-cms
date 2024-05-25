<?php
    $this->load->view('setup' . DS . 'application' . DS . 'views' . DS . 'view.header');
?>
    <div id="wrapper">
    <div id="sidebar-wrapper">
        <div style="text-align:center;margin-top:10px;">
            <img src="<?php echo $this->config->base_url; ?>../assets/default_assets/images/cms_logo.png" style="width:100px;" />
        </div>
        <ul class="sidebar-nav">
            <li class="sidebar-brand">Setup</li>
            <li>
                <a href="<?php echo $this->config->base_url; ?>index.php">Requirements</a>
            </li>
            <li>
                <a href="#">SQL Configuration</a>
            </li>
            <li>
                <a href="#">Connect Game Databases</a>
            </li>
            <li>
                <a href="#">Insert SQL Data</a>
            </li>
            <li>
                <a href="#">Setup Admin Account</a>
            </li>
            <li>
                <a href="#">Setup Completed</a>
            </li>
        </ul>
    </div>
    <div id="page-content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-10">
                    <div class="page-header">
                        <h1>Setup Progress</h1>
                    </div>
                    <div class="progress">
                        <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="0"
                             aria-valuemin="0" aria-valuemax="100" style="width: 20%;">
                            20%
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-10">
                    <div class="page-header">
                        <h1>License Key</h1>
                    </div>
                    <div class="panel panel-info">
                        <div class="panel-heading">
                            <h3 class="panel-title">DmN MuCMS <?php echo $this->Msetup->get_cms_version() ?></h3>
                        </div>
                        <div class="panel-body">
                            <?php
                                if(isset($errors)):
                                    foreach($errors as $error):
                                        echo '<div class="alert alert-danger" role="alert">' . $error . '</div>';
                                    endforeach;
                                else:
                                    if(isset($license_errors)):
                                        foreach($license_errors as $error):
                                            echo '<div class="alert alert-danger" role="alert">' . $error . '</div>';
                                        endforeach;
                                    endif;
                                    ?>
                                    <form name="license_form" method="POST" action="">
                                        <div class="form-group">
                                            <label for="email">Customer Email (<a
                                                        href="http://dmncms.net/settings/"
                                                        target="_blnak">Find My Email</a>)</label>
                                            <input type="email" class="form-control" id="email" name="email"
                                                   placeholder="Email" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="key">License Key (<a
                                                        href="http://dmncms.net/clients/purchases/"
                                                        target="_blnak">Find My License Key</a>)</label>
                                            <input type="text" class="form-control" id="license_key" name="license_key"
                                                   placeholder="License Key" required>
                                        </div>
                                        <button type="submit" name="submit_license"
                                                class="btn btn-success btn-lg btn-block">Check License
                                        </button>

                                    </form>
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