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
            <li class="active">
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
                        <h1>Insert SQL Data</h1>
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
                                    <form name="sql_data_form" id="sql_data_form" method="POST" action="">
                                        <input type="hidden" id="version" name="version"
                                               value="<?php echo $first_version; ?>"/>
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
                                        <div class="form-group">
                                            <label class="checkbox-inline">
                                                <input type="checkbox" id="overwrite_old_tables" name="overwrite_old_tables"
                                                       value="1"> Overwrite Existing Tables
                                            </label>
                                        </div>
                                        <div class="form-group">
                                            <label class="checkbox-inline">
                                                <input type="checkbox" id="insert_sql_data" name="insert_sql_data" value="1"
                                                       checked> Insert SQL Data
                                            </label>
                                        </div>
                                        <button type="submit" name="submit_sql_data"
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