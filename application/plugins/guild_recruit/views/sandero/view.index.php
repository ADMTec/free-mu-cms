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
                            <table class="table dmn-rankings-table table-striped">
                                <thead>
                                    <tr>
                                        <th><?php echo __('Guild'); ?></th>
                                        <th><?php echo __('Discord'); ?></th>
                                        <th><?php echo __('Action'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($guilds AS $guild){ ?>
                                    <tr>
                                        <td><?php echo $guild[0];?></td>
                                        <td><?php if($guild[1] != false){ echo $guild[1]['discord_link']; } else { echo '-'; } ?></td>
                                        <td><a href="<?php echo $this->config->base_url;?>guild-recruit/update/<?php echo $guild[0];?>"><?php echo __('Update');?></a></td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
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