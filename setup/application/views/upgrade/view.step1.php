<?php
    $this->load->view('setup' . DS . 'application' . DS . 'views' . DS . 'view.header');
?>
    <div id="wrapper">
    <div id="sidebar-wrapper">
        <div style="text-align:center;margin-top:10px;">
            <img src="<?php echo $this->config->base_url; ?>../assets/default_assets/images/cms_logo.png" style="width:100px;" />
        </div>
        <ul class="sidebar-nav">
            <li class="sidebar-brand">Uprade</li>
            <li>
                <a href="<?php echo $this->config->base_url; ?>index.php?action=upgrade">Check Credentials</a>
            </li>
            <li class="active">
                <a href="#">Requirements</a>
            </li>
            <li>
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
                        <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="10"
                             aria-valuemin="0" aria-valuemax="100" style="width: 10%;">
                            10%
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-10">
                    <div class="page-header">
                        <h1>Requirements</h1>
                    </div>
                    <div class="panel panel-info">
                        <div class="panel-heading">
                            <h3 class="panel-title">Upgrade DmN MuCMS
                                from <?php echo $current_version; ?> to
                                version <?php echo ($version != '') ? $version : $available_local_version; ?></h3>
                        </div>
                        <div class="panel-body">
                            <h3>System Requirements</h3>
                            <br/>
                            <h4>PHP: v<?php echo PHP_VER_MIN; ?> or better</h4>
                            <h4>Pre-Install Check: PHP Extensions</h4>
                            <em>PHP Extensions
                                Overview:</em> <?php echo (isset($this->Msetup->vars['extensionsOK']) && $this->Msetup->vars['extensionsOK'] === false) ? "<span style='color:red'>Failed</span>" : "<span style='color:green'>Passed</span>"; ?>
                            <?php
                                $html = '<ul>';
                                foreach($this->Msetup->vars['extension_data'] as $xt){
                                    if(isset($xt['ok']) && $xt['ok'] != true){
                                        if(isset($xt['remove'])){
                                            $html .= '<li>' . $xt['prettyname'] . ' (' . $xt['extensionname'] . '): <span style="color:red; font-weight: bold;">FAILED</span> (Please disable ' . $xt['extensionname'] . ' extension in your php)</li>';
                                        } else{
                                            $html .= '<li>' . $xt['prettyname'] . ' (' . $xt['extensionname'] . '): <span style="color:red; font-weight: bold;">FAILED</span> (<a href="' . $xt['helpurl'] . '" target="_blank">Click for more info</a>)</li>';
                                        }
                                    } else{
                                        $html .= '<li>' . $xt['prettyname'] . ' (' . $xt['extensionname'] . '): <span style="color:green">Passed</span></li>';
                                    }
                                }
                                echo $html . '</ul>';
                            ?>
                            <br/>
                            <h4>Pre-Install Check: Fils & Directories</h4>
                            <em>Files
                                Overview:</em> <?php echo (isset($this->Msetup->vars['filesOK']) && $this->Msetup->vars['filesOK'] === false) ? "<span style='color:red'>Failed</span>" : "<span style='color:green'>Passed</span>"; ?>
                            <?php
                                $html = '<ul>';
                                foreach($this->Msetup->vars['files_data'] as $files){
                                    if($files['dir_not_found'] == true){
                                        $html .= '<li>' . $files['file'] . ': <span style="color:red; font-weight: bold;">FAILED</span> (Directory does not exist. Please create it.)</li>';
                                    }
                                    if($files['file_not_found'] == true){
                                        $html .= '<li>' . $files['file'] . ': <span style="color:red; font-weight: bold;">FAILED</span> (File does not exist.)</li>';
                                    }
                                    if($files['dir_not_writable'] == true){
                                        $html .= '<li>' . $files['file'] . ': <span style="color:red; font-weight: bold;">FAILED</span> (Can not write to directory, please CHMOD to 0777)</li>';
                                    }
                                    if($files['file_not_writable'] == true){
                                        $html .= '<li>' . $files['file'] . ': <span style="color:red; font-weight: bold;">FAILED</span> (Can not write to file, please CHMOD to 0777)</li>';
                                    }
                                    if(!$files['dir_not_found'] && !$files['file_not_found'] && !$files['dir_not_writable'] && !$files['file_not_writable']){
                                        $html .= '<li>' . $files['file'] . ': <span style="color:green">Passed</span></li>';
                                    }
                                }
                                echo $html . '</ul>';
                            ?>
                            <?php if(isset($_SESSION['upgrade_allow_step_2']) && $_SESSION['upgrade_allow_step_2'] == true): ?>
                                <a href="<?php echo $this->config->base_url; ?>index.php?action=upgrade/step2/<?php echo $version; ?>"
                                   class="btn btn-success btn-lg btn-block">Continue</a>
                            <?php else: ?>
                                <div class="alert alert-danger" role="alert">Please Fix Failed Checks Before Continue.
                                </div>
                            <?php endif; ?>
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