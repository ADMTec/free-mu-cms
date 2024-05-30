<?php $this->load->view('plugins' . DS . $plugin_class . DS . 'views' . DS . 'default' . DS . 'view.header'); ?>
<body>
<div id="navbar">
	<div style="float: left;"><a href="<?php echo $this->config->base_url; ?>account-panel" style="color: #fff"><?php echo __('Account Panel');?></a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href="<?php echo $this->config->base_url; ?>wheel-of-fortune" style="color: #fff"><?php echo __('Spin Wheel');?></a></div><?php echo __('Welcome');?> <?php echo $this->session->userdata(['user' => 'username']);?>, <?php echo __('your');?> <?php echo $currency_name;?> <?php echo __('balance');?>: <span id="balance" style="color: #fff;"><?php echo $currency_amount;?></span>
</div>
<div id="content">
	<?php if(isset($config_not_found)){ ?>
		<div style="margin-top: 10px;height: 550px;background-color: #fff;padding: 20px;"><div class="alert alert-danger"><?php echo $config_not_found;?></div></div>
	<?php } else{ ?>
	<div style="margin-top: 10px;height: 550px;background-color: #fff;padding: 30px;">
		<?php if(empty($user_rewards_parsed)) { ?>
		<div class="alert alert-info"><?php echo __('No rewards found.');?></div>
		<?php } else { ?>
		<div class="row">
		  <h2><?php echo __('View My Reward List');?></h2>  
		  <div style="height: 500px; overflow: auto; ">
		  <table class="table table-bordered" style="width: 950px;">
			<thead>
			  <tr>
				<th><?php echo __('Reward'); ?></th>
				<th><?php echo __('Character'); ?></th>
				<th><?php echo __('Date Won'); ?></th>
				<th><?php echo __('Status'); ?></th>
			  </tr>
			</thead>
			<tbody>
			  <?php foreach($user_rewards_parsed AS $id => $data){	?>			
			  <tr>
				<td>
					<?php if($data['amount'] != false){ ?>
					<?php echo $data['name'];?> x<?php echo $data['amount'];?>
					<?php } else{ ?>
						<?php if($data['type'] == 0){ ?>
						<?php echo $data['name'];?>
						<?php } else { ?>
						<?php echo $data['item']['name'];?>
						<?php } ?>
					<?php } ?>
				</td>
				<td id="character_<?php echo $data['id'];?>"><?php if($data['character'] != null){ echo $data['character']; } else { echo __('Not Claimed'); } ?></td>
				<td><?php echo $data['date_won'];?></td>
				<?php if($data['code'] != NULL && $data['is_claimed'] != 1){ ?>
				<td><?php echo $data['code'];?> <a href="<?php echo $this->config->base_url;?>gift-code" target="_blank"><?php echo __('Claim Reward');?></a></td>
				<?php } else { ?>
				<td><?php if($data['is_claimed'] == 1){ echo __('Claimed'); } else { echo '<a class="get_reward" href="#" id="reward_'.$data['id'].'" data-type="'.$data['type'].'" data-action="'.$this->config->base_url.'wheel-of-fortune/claim-reward/'.$data['id'].'">'.__('Claim Reward').'</a>'; } ?></td>
				<?php } ?>
			 </tr>
			  <?php } ?>
			</tbody>
		  </table>
		  </div>
		</div>
		<script type="text/javascript">
			$(document).ready(function (){
				$('.get_reward').on('click', function() {
					if($(this).data('action') != ''){
						var action = $(this).data('action');
						var that = $(this).attr('id');
						$(this).data('action', '');	
						$.alertable.prompt('Select Character', {
							prompt:
							 '<div class="form-group" style="width: 280px;">' +
							  '<select class="alertable-input" id="sel1" name="character">' +
							  <?php 
							  if($characters != false){ 
								foreach($characters AS $char){
							  ?>
								'<option value="<?php echo $char["id"];?>"><?php echo $char["name"];?></option>' +
							  <?php
								}
							  }
							  ?>
							  '</select>' +
							'</div> ' 
						}).then(function(cdata) {
							if(typeof cdata.character != 'undefined'){
							$.ajax({
								dataType: 'json',
								method: 'post',
								url: action,
								data: {'claim': 1, 'character': cdata.character},
								success: function (data) {
									if (data.error) {
										$('#'+that).data('action', action);	
										$.alertable.alert(data.error, {
											html: true
										});	
									}
									else {
										$('#'+that).parent().html('Claimed');
										$('#character_'+data.id).html(data.char);	
										$.alertable.alert(data.success, {
											html: true
										});
									}
								},
								error: function (xhr, ajaxOptions, thrownError){
									$('#'+that).data('action', action);		
									alert(thrownError);
								}
							});
							}
							else{
								$('#'+that).data('action', action);	
								alert('<?php echo __("Invalid character.");?>');
							}
						}, function() {
							$('#'+that).data('action', action);										
						});
					}
				});
				
			});
		</script>	
		<?php } ?>
	</div>
	<?php } ?>
</div>
<?php $this->load->view('plugins' . DS . $plugin_class . DS . 'views' . DS . 'default' . DS . 'view.footer'); ?>	