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
            <li class="active">
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
                        <div class="progress-bar progress-bar-success" role="progressbar" style="width: 90%;">
                            90%
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-10">
                    <div class="page-header">
                        <h1>Setup Admin Account</h1>
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
                                    ?>
                                    <form name="admin_account" method="POST" action="">
                                        <div class="form-group">
                                            <label for="username">Admin Username</label>
                                            <input type="username" class="form-control" id="username" name="username"
                                                   placeholder="Username" required/>
                                        </div>
                                        <div class="form-group">
                                            <label for="password">Admin Password</label>
                                            <input type="password" class="form-control" id="password" name="password"
                                                   placeholder="Password" required/>
                                        </div>
                                        <div class="form-group">
                                            <label for="pincode">Admin PinCode</label>
                                            <input type="username" class="form-control" id="pincode" name="pincode"
                                                   placeholder="Pincode" maxlength="6" title="6 random digits"
                                                   pattern="[0-9]{6}" required/>
                                        </div>
                                        <button type="submit" name="submit_admin_account"
                                                class="btn btn-success btn-lg btn-block">Proceed
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