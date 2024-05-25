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
            <li class="active">
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
                        <div class="progress-bar progress-bar-success" role="progressbar" style="width: 100%;">
                            100%
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-10">
                    <div class="page-header">
                        <h1>Setup Completed</h1>
                    </div>
                    <div class="panel panel-info">
                        <div class="panel-heading">
                            <h3 class="panel-title">DmN MuCMS <?php echo $this->Msetup->get_cms_version() ?></h3>
                        </div>
                        <div class="panel-body">
                            Congratulations, your <a href="https://github.com/pulseP1986/free-mu-cms" target="_blank">DmN
                                MuCMS <?php echo $this->Msetup->get_cms_version() ?></a> is now installed and ready to
                            use. Below are some links you may find useful.<br/><br/>
                            <ul class="list-group">
                                <li class="list-group-item"><a href="<?php echo $this->config->base_url . '../'; ?>">Website
                                        Home</a></li>
                                <li class="list-group-item"><a
                                            href="<?php echo $this->config->base_url . '../'; ?>admincp">Website
                                        AdminCP</a></li>
                            </ul>
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