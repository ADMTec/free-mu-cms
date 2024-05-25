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
            <li class="active">
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
                             aria-valuemin="0" aria-valuemax="100" style="width: 30%;">
                            30%
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-10">
                    <div class="page-header">
                        <h1>SQL Configuration</h1>
                    </div>
                    <div class="panel panel-info">
                        <div class="panel-heading">
                            <h3 class="panel-title">DmN MuCMS <?php echo $this->Msetup->get_cms_version(); ?></h3>
                        </div>
                        <div class="panel-body">
                            <?php
                                if(isset($errors)):
                                    foreach($errors as $error):
                                        echo '<div class="alert alert-danger" role="alert">' . $error . '</div>';
                                    endforeach;
                                else:
                                    if(isset($info)):
                                        foreach($info as $notice):
                                            echo '<div class="alert alert-info" role="alert">' . $notice . '</div>';
                                        endforeach;
                                    endif;
                                    if(isset($sql_errors)):
                                        foreach($sql_errors as $error):
                                            echo '<div class="alert alert-danger" role="alert">' . $error . '</div>';
                                        endforeach;
                                    endif;
                                    ?>
                                    <form name="sql_config_form" method="POST" action="">
                                        <div class="form-group">
                                            <label for="key">SQL Host</label>
                                            <input type="text" class="form-control" id="sql_host" name="sql_host"
                                                   placeholder="127.0.0.1" value="<?php if(isset($ip)){
                                                echo $ip;
                                            } ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="key">SQL Port</label>
                                            <input type="text" class="form-control" id="sql_host" name="sql_port"
                                                   placeholder="1433" value="<?php if(isset($port)){
                                                echo $port;
                                            } ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="key">SQL Username</label>
                                            <input type="text" class="form-control" id="sql_user" name="sql_user"
                                                   placeholder="sa" value="<?php if(defined('USER')){
                                                echo USER;
                                            } ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="key">SQL Password</label>
                                            <input type="password" class="form-control" id="sql_pass" name="sql_pass"
                                                   placeholder="12345" value="<?php if(defined('PASS')){
                                                echo PASS;
                                            } ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="key">Website Database Name</label>
                                            <input type="text" class="form-control" id="sql_web_db" name="sql_web_db"
                                                   placeholder="dmncms" value="<?php if(defined('WEB_DB')){
                                                echo WEB_DB;
                                            } ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="sql_driver">Sql Connection Extension:</label>
                                            <select class="form-control" id="sql_driver" name="sql_driver">
                                                <option value="sqlsrv" <?php if(defined('DRIVER') && DRIVER == 'sqlsrv'){
                                                    echo 'selected="selected"';
                                                } ?>>SQLServ
                                                </option>
                                                <option value="pdo_sqlsrv" <?php if(defined('DRIVER') && DRIVER == 'pdo_sqlsrv'){
                                                    echo 'selected="selected"';
                                                } ?>>PDO SQLServ
                                                </option>
                                                <!--<option value="pdo_odbc" <?php if(defined('DRIVER') && strtolower(DRIVER) == 'pdo_odbc'){
                                                    echo 'selected="selected"';
                                                } ?>>PDO ODBC
                                                </option>-->
                                                <option value="pdo_dblib" <?php if(defined('DRIVER') && DRIVER == 'pdo_dblib'){
                                                    echo 'selected="selected"';
                                                } ?>>PDO DBLIB
                                                </option>
                                            </select>
                                        </div>
                                        <button type="submit" name="submit_sql" class="btn btn-success btn-lg btn-block">
                                            Check Sql Connection
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