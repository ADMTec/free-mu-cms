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
            <li class="active">
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
                             aria-valuemin="0" aria-valuemax="100" style="width: 40%;">
                            40%
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-10">
                    <div class="page-header">
                        <h1>Connect Game Databases</h1>
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
                                    if(isset($error)):
                                        echo '<div class="alert alert-danger" role="alert">' . $error . '</div>';
                                    endif;
                                    ?>
                                    <form name="sql_data_form" method="POST" action="">
                                        <div class="form-group">
                                            <label for="acc_db">Account Database:</label>
                                            <select class="form-control" id="acc_db" name="acc_db">
                                                <?php foreach($dbs AS $key => $value): ?>
                                                    <option
                                                            value="<?php echo $value['name']; ?>" <?php if($value['name'] == 'Me_MuOnline'){
                                                        echo 'selected="selected"';
                                                    } ?>><?php echo $value['name']; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="char_db">Character Database:</label>
                                            <select class="form-control" id="char_db" name="char_db">
                                                <?php foreach($dbs AS $key => $value): ?>
                                                    <option
                                                            value="<?php echo $value['name']; ?>"><?php echo $value['name']; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <button type="submit" name="submit_game_dbs"
                                                class="btn btn-success btn-lg btn-block">Continue
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