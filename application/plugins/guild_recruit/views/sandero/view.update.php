<?php
    $this->load->view($this->config->config_entry('main|template') . DS . 'view.header');
?>
<div class="dmn-content">
	<div class="dmn-page-box">
		<div class="dmn-page-title">
			<h1><?php echo __($about['name']); ?></h1>
		</div>
		<div class="dmn-page-content">
			<div class="row">
				<div class="col-12">     
					<h2 class="title"><?php echo __($about['user_description']); ?></h2>
					<div class="mb-5"> 
						<?php
						 if(isset($module_disabled)){
							echo '<div class="alert alert-danger" role="alert">' . $module_disabled . '</div>';
						} 
						else{
							if(isset($error)){
								echo '<div class="alert alert-danger" role="alert">' . $error . '</div>';
							}
							if(isset($success)){
								echo '<div class="alert alert-success" role="alert">' . $success . '</div>';
							}
                            if(isset($js)){
                            ?>
                            <script src="<?php echo $js; ?>"></script>
                            <?php } ?>
                            <?php if(!empty($guilds)){ ?>
                            <div class="form">
                            <form method="post" action="" id="discord_form">
                                <div class="form-group">
                                    <label class="control-label"><?php echo __('Guild'); ?></label>
                                    <?php echo $gname;?>
                                </div>
                                <div class="form-group">
                                    <label class="control-label"><?php echo __('Discord Link'); ?></label>
                                    <input type="text" id="discord_link" name="discord_link" value="<?php if($found['discord_link'] != null){ echo $found['discord_link']; } ?>" class="form-control" />
                                </div>
                                <div class="mb-5">
									<div class="d-flex justify-content-center align-items-center">
                                        <button type="submit" class="btn btn-primary"><?php echo __('Submit'); ?></button>
                                    </div>
								</div>
                            </form>
                        </div>
                            <?php } else { ?>
                            <?php echo __('You don\'t have any guilds.'); ?>
                            <?php } ?>
						<?php } ?>
					</div>
				</div>	
			</div>	
		</div>
	</div>	
</div>		
<?php
    $this->load->view($this->config->config_entry('main|template') . DS . 'view.footer');
?>