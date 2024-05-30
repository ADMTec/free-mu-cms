<?php $this->load->view('plugins' . DS . $plugin_class . DS . 'views' . DS . 'default' . DS . 'view.header'); ?>
<body>
<div id="navbar">
	<div style="float: left;"><a href="<?php echo $this->config->base_url; ?>account-panel" style="color: #fff"><?php echo __('Account Panel');?></a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href="<?php echo $this->config->base_url; ?>wheel-of-fortune/my-rewards" style="color: #fff"><?php echo __('View My Reward List');?></a></div><?php echo __('Welcome');?> <?php echo $this->session->userdata(['user' => 'username']);?>, <?php echo __('your');?> <?php echo $currency_name;?> <?php echo __('balance');?>: <span id="balance" style="color: #fff;"><?php echo $currency_amount;?></span>
</div>
<div id="content">
	<?php if(isset($config_not_found)){ ?>
		<div style="margin-top: 10px;height: 600px;background-color: #fff;padding: 20px;"><div class="alert alert-danger"><?php echo $config_not_found;?></div></div>
	<?php } else{ ?>	
	<div id="left_side">
		<div id="rewards">
		<br />
		<script>
			$(document).ready(function () {
				$('.img, .hex').each(function () {
					App.initializeTooltip($(this), true, 'warehouse/item_info');
				});
				$('#spin_button').each(function () {
					App.initializeTooltip($(this), false);
				});
				
			});
			var names = [];
		</script>
		<?php 
		if(!empty($rewards)){
			foreach($rewards AS $id => $data){	
				$s = 'class="selected"';
		?>
			
			<div id="item" class="items">
				<div id="count"><?php echo $id;?></div>
				<?php if(!in_array($data['type'], [0, 7])){ ?>
				<div id="image"><img style="width:30px;" src="<?php echo $this->config->base_url; ?>assets/plugins/images/wheel_of_fortune/currency.png"></div>
				<?php } ?>
				<?php if($data['amount'] != false){ ?>
				<div id="title">
				<?php echo $data['name'];?> x<?php echo $data['amount'];?>
				<script>
					names.push({'text' : '<?php echo $data['name'];?> x<?php echo $data['amount'];?>'});
				</script>
				</div>
				<?php } else{ ?>
					<?php if($data['type'] == 0){ ?>
					<div id="title"><?php echo $data['name'];?></div>
					<script>
						names.push({'text' : '<?php echo $data['name'];?>'});
					</script>
					<?php } else { ?>
					<div id="image" class="img" data-info="<?php echo $data['item']['hex']; ?>" style="background: url(<?php echo $data['item']['image'];?>) no-repeat center center;background-size:contain;border: 1px solid #fff;background-color: #146C74;"></div>
					<div id="title" class="hex" data-info="<?php echo $data['item']['hex']; ?>"><?php echo $data['item']['name'];?></div>
					<script>
						names.push({'text' : '<?php echo $data['item']['name'];?>'});
					</script>
					<?php } ?>
				<?php } ?>
			</div>
		<?php
			}
		}
		?>
		</div>
	</div>
	<div id="right_side">
		<table cellpadding="0" cellspacing="0" border="0">
			<tr>
				<td>
					<div class="power_controls"></div>
				</td>
				<td width="438" height="582" class="the_wheel" align="center" valign="center">
				<img id="spin_button" src="<?php echo $this->config->base_url; ?>assets/plugins/images/wheel_of_fortune/spin_on.png" alt="Spin" onClick="startSpin();" class="spinme" data-info="<?php echo __('Spin cost'); ?> <?php echo $plugin_config['spin_price'];?> <?php echo $currency_name;?>" />
					<canvas id="canvas" width="434" height="434">
						<p style="color: #fff;" align="center">Sorry, your browser doesn't support canvas. Please try another.</p>
					</canvas>
				</td>
			</tr>
		</table>	
	</div>
	<script>
		var theWheel = new Winwheel({
			'drawMode': 'image', 'numSegments': 10, 'outerRadius': 212, 'innerRadius': 120, 'textFontSize': 13, 'textMargin': 0,  'segments': names, 'animation': { 'type': 'spinToStop', 'duration': 5, 'spins': 8, 'callbackFinished': 'alertPrize()' }
		});
		
		var wheelImg = new Image();

		wheelImg.onload = function(){
			theWheel.wheelImage = wheelImg;
			theWheel.draw();
		}
		 
		wheelImg.src = "<?php echo $this->config->base_url; ?>assets/plugins/images/wheel_of_fortune/wheel.png";

		var wheelSpinning = false;				  
		var rid = false;		  

		function startSpin(){
			if(wheelSpinning == false){
				$("#spin_button").attr("src","<?php echo $this->config->base_url; ?>assets/plugins/images/wheel_of_fortune/spin_off.png");
				$("#spin_button").removeClass("spinme");

				$.ajax({
					url: '<?php echo $this->config->base_url;?>wheel-of-fortune/spin',
					dataType: 'json',	
					success: function(data){
						if(data.error){
						  $.alertable.alert(data.error, {
							html: true
						  });
						  $('.items').removeClass('selected');
						  return;
						}
						if(data.rid){
							rid = data.id;
							theWheel.animation.stopAngle = data.rid;
							theWheel.startAnimation();
							wheelSpinning = true;
							$('.items').removeClass('selected');
							$('#balance').html(data.left_amount);
						}
					},
					error: function (xhr, ajaxOptions, thrownError){
						alert(thrownError);
						return;
					}
				});
				
			}
		}

		function resetWheel(){
			theWheel.stopAnimation(false);
			theWheel.rotationAngle = 0;
			theWheel.draw(); 
			wheelSpinning = false;
			
			$("#spin_button").attr("src","<?php echo $this->config->base_url; ?>assets/plugins/images/wheel_of_fortune/spin_on.png");
			$("#spin_button").addClass("spinme");
		}
		
		function alertPrize(){
			var winningSegment = theWheel.getIndicatedSegment();
			$.alertable.alert('<div style="text-align:center;">You have won</div><div style="text-align:center;">' + winningSegment.text + '</div>', {
				html: true
			  }).then(function(){ 
				resetWheel(); 
				$('.items:eq('+(rid-1)+')').addClass('selected');
			});		
		}
	</script>
	<?php } ?>
</div>
<?php $this->load->view('plugins' . DS . $plugin_class . DS . 'views' . DS . 'default' . DS . 'view.footer'); ?>	