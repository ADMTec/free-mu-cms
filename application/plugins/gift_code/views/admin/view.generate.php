<?php
$this->load->view('admincp' . DS . 'view.header');
$this->load->view('admincp' . DS . 'view.sidebar');
?>
<div id="content" class="span10">
    <div>
        <ul class="breadcrumb">
            <li><a href="<?php echo $this->config->base_url; ?>admincp">Home</a> <span class="divider">/</span></li>
            <li><a href="<?php echo $this->config->base_url; ?>admincp/manage-plugins">Manage Plugins</a></li>
        </ul>
    </div>
	<?php $server_list = ($is_multi_server == 0) ? ['all' => ['title' => 'All']] : $this->website->server_list(); ?>
	<div class="row-fluid">
        <div class="span12">
            <ul class="nav nav-pills">
				<li><a href="<?php echo $this->config->base_url; ?>gift-code/admin" role="tab">Settings</a></li>
				<li class="active"><a href="<?php echo $this->config->base_url; ?>gift-code/generate" role="tab">Generate Codes</a></li>
				<li><a href="<?php echo $this->config->base_url; ?>gift-code/logs" role="tab">Logs</a></li>
			</ul>
            <div class="clearfix"></div>
        </div>
    </div>
	<?php if(isset($js)): ?>
	<script src="<?php echo $js;?>"></script>
	<script type="text/javascript">	
	var giftCode = new giftCode();
	giftCode.setUrl('<?php echo $this->config->base_url . $this->request->get_controller();?>');
	$(document).ready(function(){
		$('form[id^="gift_code_settings_form_"]').on("submit", function (e){
			e.preventDefault();
			giftCode.saveSettings($(this));
		});
	});
	</script>
	<?php endif;?>
	<div class="row-fluid">
        <div class="box span12">
            <div class="tab-content">
                <div role="tabpanel" class="tab-pane fade in active">
                    <div class="box-header well">
                        <h2><i class="icon-edit"></i> Generate Coupon</h2>
                    </div>
                    <div class="box-content">
						<?php
						if(isset($error)){
							echo '<div class="alert alert-error">' . $error . '</div>';
						}
						if(isset($success)){
							echo '<div class="alert alert-success">' . $success . '</div>';
						}
						?>
                        <form class="form-horizontal" method="POST" action="<?php echo $this->config->base_url;?>gift-code/generate" id="gift_code_generate">
							<div class="control-group">
								<label class="control-label" for="coupon">Coupon </label>
								<div class="controls">
									<input type="text" class="span6 typeahead" id="coupon" name="coupon" value="<?php if(isset($_POST['coupon'])){ echo $_POST['coupon']; } ?>" style="text-transform:uppercase"/>
									<p class="help-block">Leave empty for auto generation.</p>
								</div>
							</div>
							<div class="control-group">
								<label class="control-label" for="server">Server </label>
								<div class="controls">
									<select id="server" name="server[]" required multiple data-rel="chosen">
										<?php 
											foreach($server_list AS $key => $server){
												if($server['visible'] == 1){
										?>
											 <option value="<?php echo $key;?>" <?php if(isset($_POST['server']) && in_array($_POST['server'], $server_list)){ echo 'selected="selected"'; } ?>><?php echo $server['title'];?></option>
										<?php }} ?>
									</select>
									<p class="help-block">Select server.</p>
								</div>
							</div>
							<div class="control-group">
								<label class="control-label" for="valid_until">Valid Until </label>

								<div class="controls">
									<input type="text" class="span6 datetimepicker" id="valid_until" name="valid_until" value="<?php if(isset($_POST['valid_until'])){ echo $_POST['valid_until']; } ?>" required />
									<p class="help-block">When coupon will expire.</p>
								</div>
							</div>
							<div class="control-group">
								<label class="control-label" for="max_uses">Max Uses </label>

								<div class="controls">
									<input type="text" class="span6" id="max_uses" name="max_uses" value="<?php if(isset($_POST['max_uses'])){ echo $_POST['max_uses']; } ?>" placeholder="999999" required />
									<p class="help-block">How many times coupon can be used in total.</p>
								</div>
							</div>
							<div class="control-group">
								<label class="control-label" for="max_uses_by_user">Max Uses Per Account</label>

								<div class="controls">
									<input type="text" class="span6" id="max_uses_by_user" name="max_uses_by_user" value="<?php if(isset($_POST['max_uses_by_user'])){ echo $_POST['max_uses_by_user']; } ?>" placeholder="1" />
									<p class="help-block">How many times coupon can be used per account.</p>
								</div>
							</div>
							<div class="control-group">
								<label class="control-label" for="max_uses_by_char">Max Uses Per Character</label>

								<div class="controls">
									<input type="text" class="span6" id="max_uses_by_char" name="max_uses_by_char" value="<?php if(isset($_POST['max_uses_by_char'])){ echo $_POST['max_uses_by_char']; } ?>" placeholder="1" />
									<p class="help-block">If giftcode type [<span style="color: red;">Zen in Character, Ruud in Character, Item, Item hex, Buff</span>] how many times coupon <br />can be used per character. Max uses per account will be ignored.</p>
								</div>
							</div>
							<div class="control-group">
								<label class="control-label" for="min_lvl">Min Level</label>

								<div class="controls">
									<input type="text" class="span6" id="min_lvl" name="min_lvl" value="<?php if(isset($_POST['min_lvl'])){ echo $_POST['min_lvl']; } ?>" placeholder="1" />
									<p class="help-block">If giftcode used for character min lvl required.</p>
								</div>
							</div>
							<div class="control-group">
								<label class="control-label" for="min_mlvl">Min MasterLevel</label>

								<div class="controls">
									<input type="text" class="span6" id="min_mlvl" name="min_mlvl" value="<?php if(isset($_POST['min_mlvl'])){ echo $_POST['min_mlvl']; } ?>" placeholder="1" />
									<p class="help-block">If giftcode used for character min master lvl required.</p>
								</div>
							</div>
							<div class="control-group">
								<label class="control-label" for="min_res">Min Reset</label>

								<div class="controls">
									<input type="text" class="span6" id="min_res" name="min_res" value="<?php if(isset($_POST['min_res'])){ echo $_POST['min_res']; } ?>" placeholder="0" />
									<p class="help-block">If giftcode used for character min reset required.</p>
								</div>
							</div>
							<div class="control-group">
								<label class="control-label" for="min_gres">Min GrandReset</label>

								<div class="controls">
									<input type="text" class="span6" id="min_gres" name="min_gres" value="<?php if(isset($_POST['min_gres'])){ echo $_POST['min_gres']; } ?>" placeholder="0" />
									<p class="help-block">If giftcode used for character min grand reset required.</p>
								</div>
							</div>
							<div class="control-group">
								<label class="control-label" for="class">Class</label>
								<div class="controls">
									<?php
										$class = [];
										if(isset($_POST['class']) && $_POST['class'] != ''){
											$class = $_POST['class'];
										}
									?>
									<select class="span3" id="class" name="class[]" multiple data-rel="chosen">
										<?php foreach($class_list AS $id => $classData){ ?>
											<option value="<?php echo $id;?>" <?php if(in_array($id, $class)){ echo 'selected="selected"'; } ?>><?php echo $classData['long'];?>(<?php echo $id; ?>)</option>
										<?php } ?>
									</select>	
									<p>Allowed for class. Leave empty if no class requirements.</p>	
								</div>
							</div>
							<div class="control-group">
								<label class="control-label" for="coupon_type">Coupon Type </label>
								<div class="controls">
									<select id="coupon_type" name="coupon_type" required>
										<option value="0" <?php if(isset($_POST['coupon_type']) && $_POST['coupon_type'] == 0){ echo 'selected="selected"'; } ?>>Select</option>
										<option value="1" <?php if(isset($_POST['coupon_type']) && $_POST['coupon_type'] == 1){ echo 'selected="selected"'; } ?>>Credits 1</option>
										<option value="2" <?php if(isset($_POST['coupon_type']) && $_POST['coupon_type'] == 2){ echo 'selected="selected"'; } ?>>Credits 2</option>
										<option value="3" <?php if(isset($_POST['coupon_type']) && $_POST['coupon_type'] == 3){ echo 'selected="selected"'; } ?>>Zen in Web Wallet</option>
										<option value="4" <?php if(isset($_POST['coupon_type']) && $_POST['coupon_type'] == 4){ echo 'selected="selected"'; } ?>>Zen in Character</option>
										<option value="5" <?php if(isset($_POST['coupon_type']) && $_POST['coupon_type'] == 5){ echo 'selected="selected"'; } ?>>Ruud in Character</option>
										<option value="6" <?php if(isset($_POST['coupon_type']) && $_POST['coupon_type'] == 6){ echo 'selected="selected"'; } ?>>WCoin</option>
										<option value="7" <?php if(isset($_POST['coupon_type']) && $_POST['coupon_type'] == 7){ echo 'selected="selected"'; } ?>>GoblinPoint</option>
										<option value="8" <?php if(isset($_POST['coupon_type']) && $_POST['coupon_type'] == 8){ echo 'selected="selected"'; } ?>>Vip Package</option>
										<option value="9" <?php if(isset($_POST['coupon_type']) && $_POST['coupon_type'] == 9){ echo 'selected="selected"'; } ?>>Item</option>
										<option value="10" <?php if(isset($_POST['coupon_type']) && $_POST['coupon_type'] == 10){ echo 'selected="selected"'; } ?>>Item hex</option>
										<option value="11" <?php if(isset($_POST['coupon_type']) && $_POST['coupon_type'] == 11){ echo 'selected="selected"'; } ?>>Buff</option>
									</select>
								</div>
							</div>
							<div class="control-group" id="coin_amount" style="display:none;">
								<label class="control-label" for="reward_amount">Reward Amount</label>
								<div class="controls">
									<input type="text" class="span6" id="reward_amount" name="reward_amount" value="<?php if(isset($_POST['reward_amount'])){ echo $_POST['reward_amount']; } ?>" />				
								</div>
							</div>
							<div class="control-group" id="vip_package" style="display:none;">
								<label class="control-label" for="vip_type">Vip Package</label>
								<div class="controls">
									<?php $vip_packages = $this->Madmin->load_vip_packages(); ?>
									<?php if(!empty($vip_packages)){ ?>
									<select id="vip_type" name="vip_type" required>
										<?php foreach($vip_packages AS $package){ ?>
										<option value="<?php echo $package['id'];?>" <?php if(isset($_POST['vip_type']) && $_POST['vip_type'] == $package['id']){ echo 'selected="selected"'; } ?>><?php echo $package['package_title'];?></option>
										<?php } ?>
									</select>
									<?php } else { ?>
									No vip packages found.
									<?php } ?>
								</div>
							</div>
							<div class="control-group" id="buff_item" style="display:none;">
								<label class="control-label" for="buff">Items</label>
								<div class="controls" id="bufflist">
									<?php 
									if(isset($_POST['item_category_buff'])){ 
										$i = 0;
										foreach($_POST['item_category_buff'] AS $data){
									?>
									<div id="buff_<?php echo $i + 1;?>" <?php if($i > 0){?>style="margin-top:2px;"<?php } ?>>
										Category: <input class="form-control" style="width:40px; display: inline;" type="text" name="item_category_buff[]" value="<?php echo $_POST['item_category_buff'][$i];?>" /> 
										Index: <input class="form-control" style="width:40px; display: inline;" type="text" name="item_index_buff[]" value="<?php echo $_POST['item_index_buff'][$i];?>" /> 
										Expire (minutes): <input class="form-control" style="width:40px; display: inline;" type="text" name="item_expires_buff[]" value="<?php echo $_POST['item_expires_buff'][$i];?>" placeholder="" /> 
										<button class="btn btn-danger removeBuff" name="removeItem" id="bremove_<?php echo $i + 1;?>"> <i class="icon-remove"></i></button>
										<?php if($i == 0){?><button class="btn btn-success" name="addBuff" id="addBuff"><i class="icon-plus"></i></button><?php } ?>
									</div>
									<?php
											$i++;
										}
									} else { 
									?>
									<div id="buff_1">
										Category: <input class="form-control" style="width:40px; display: inline;" type="text" name="item_category_buff[]" value="" /> 
										Index: <input class="form-control" style="width:40px; display: inline;" type="text" name="item_index_buff[]" value="" /> 
										Expire (minutes): <input class="form-control" style="width:40px; display: inline;" type="text" name="item_expires_buff[]" value="" placeholder="" /> 
										<button class="btn btn-danger removeBuff" name="removeBuff" id="bremove_1"> <i class="icon-remove"></i></button>
										<button class="btn btn-success" name="addBuff" id="addBuff"><i class="icon-plus"></i></button>
									</div>
									<?php } ?>	
								</div>
							</div>
							<div class="control-group" id="single_item" style="display:none;">
								<label class="control-label" for="items">Items</label>
								<div class="controls" id="itemlist">
									<?php 
									if(isset($_POST['item_category'])){ 
										$i = 0;
										foreach($_POST['item_category'] AS $data){
									?>
									<div id="item_<?php echo $i + 1;?>" <?php if($i > 0){?>style="margin-top:2px;"<?php } ?>>
										Category: <input class="form-control" style="width:40px; display: inline;" type="text" name="item_category[]" value="<?php echo $_POST['item_category'][$i];?>" /> 
										Index: <input class="form-control" style="width:40px; display: inline;" type="text" name="item_index[]" value="<?php echo $_POST['item_index'][$i];?>" /> 
										Dur: <input class="form-control" style="width:40px; display: inline;" type="text" name="item_dur[]" value="<?php echo $_POST['item_dur'][$i];?>" /> 
										Level: <input class="form-control" style="width:40px; display: inline;" type="text" name="item_level[]" value="<?php echo $_POST['item_level'][$i];?>" placeholder="0-15" /> 
										Skill: <input class="form-control" style="width:40px; display: inline;" type="text" name="item_skill[]" value="<?php echo $_POST['item_skill'][$i];?>" placeholder="0/1" /> 
										Luck: <input class="form-control" style="width:40px; display: inline;" type="text" name="item_luck[]" value="<?php echo $_POST['item_luck'][$i];?>" placeholder="0/1" /> 
										Option: <input class="form-control" style="width:40px; display: inline;" type="text" name="item_option[]" value="<?php echo $_POST['item_option'][$i];?>" placeholder="0-7" /> 
										Excellent: <input class="form-control" style="width:90px; display: inline;" type="text" name="item_excellent[]" value="<?php echo $_POST['item_excellent'][$i];?>" placeholder="1,1,1,1,1,1|0-6" /> 
										Ancient: <input class="form-control" style="width:70px; display: inline;" type="text" name="item_ancient[]" value="<?php echo $_POST['item_ancient'][$i];?>" placeholder="0/5/6/9/10" /> 
										Expire (minutes): <input class="form-control" style="width:40px; display: inline;" type="text" name="item_expires[]" value="<?php echo $_POST['item_expires'][$i];?>" placeholder="" /> 
										<button class="btn btn-danger removeItem" name="removeItem" id="remove_<?php echo $i + 1;?>"> <i class="icon-remove"></i></button>
										<?php if($i == 0){?><button class="btn btn-success" name="addItem" id="addItem"><i class="icon-plus"></i></button><?php } ?>
									</div>
									<?php
											$i++;
										}
									} else { 
									?>
									<div id="item_1">
										Category: <input class="form-control" style="width:40px; display: inline;" type="text" name="item_category[]" value="" /> 
										Index: <input class="form-control" style="width:40px; display: inline;" type="text" name="item_index[]" value="" /> 
										Dur: <input class="form-control" style="width:40px; display: inline;" type="text" name="item_dur[]" value="" /> 
										Level: <input class="form-control" style="width:40px; display: inline;" type="text" name="item_level[]" value="" placeholder="0-15" /> 
										Skill: <input class="form-control" style="width:40px; display: inline;" type="text" name="item_skill[]" value="" placeholder="0/1" /> 
										Luck: <input class="form-control" style="width:40px; display: inline;" type="text" name="item_luck[]" value="" placeholder="0/1" /> 
										Option: <input class="form-control" style="width:40px; display: inline;" type="text" name="item_option[]" value="" placeholder="0-7" /> 
										Excellent: <input class="form-control" style="width:90px; display: inline;" type="text" name="item_excellent[]" value="" placeholder="1,1,1,1,1,1|0-6" /> 
										Ancient: <input class="form-control" style="width:70px; display: inline;" type="text" name="item_ancient[]" value="" placeholder="0/5/6/9/10" /> 
										Expire (minutes): <input class="form-control" style="width:40px; display: inline;" type="text" name="item_expires[]" value="" placeholder="" /> 
										<button class="btn btn-danger removeItem" name="removeItem" id="remove_1"> <i class="icon-remove"></i></button>
										<button class="btn btn-success" name="addItem" id="addItem"><i class="icon-plus"></i></button>
									</div>
									<?php } ?>	
								</div>
							</div>
							<div class="control-group" id="single_item_hex" style="display:none;">
								<label class="control-label" for="items">Items</label>
								<div class="controls" id="itemlist_hex">
									<?php 
									if(isset($_POST['item_hex'])){ 
										$i = 0;
										foreach($_POST['item_hex'] AS $data){
									?>
									<div id="itemhex_<?php echo $i + 1;?>" <?php if($i > 0){?>style="margin-top:2px;"<?php } ?>>
										Hex: <input class="form-control" style="width:500px; display: inline;" type="text" name="item_hex[]" value="<?php echo $_POST['item_hex'][$i];?>" /> 
										<button class="btn btn-danger removeItemHex" name="removeItemHex" id="removehex_<?php echo $i + 1;?>"> <i class="icon-remove"></i></button>
										<?php if($i == 0){?><button class="btn btn-success" name="addItemHex" id="addItemHex"><i class="icon-plus"></i></button><?php } ?>
									</div>
									<?php
											$i++;
										}
									} else { 
									?>
									<div id="itemhex_1">
										Hex: <input class="form-control" style="width:500px; display: inline;" type="text" name="item_hex[]" value="" /> 
										<button class="btn btn-danger removeItemHex" name="removeItemHex" id="removehex_1"> <i class="icon-remove"></i></button>
										<button class="btn btn-success" name="addItemHex" id="addItemHex"><i class="icon-plus"></i></button>
									</div>
									<?php } ?>	
								</div>
							</div>
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary" name="add_code" id="add_code">Generate</button>
                            </div>
                        </form>
						<script>
						$(document).ready(function() {
							$('#addBuff').on("click", function(e) {
								e.preventDefault();
								var divId = parseInt($('#bufflist').children().last().attr('id').split('_')[1]) + 1;

								var html = '<div id="buff_'+divId+'" style="margin-top:2px;"><hr />';
								html += ' Category: <input class="form-control" style="width:40px; display: inline;" type="text" name="item_category_buff[]" value="" />';
								html += ' Index: <input class="form-control" style="width:40px; display: inline;" type="text" name="item_index_buff[]" value="" />';
								html += ' Expire (minutes): <input class="form-control" style="width:40px; display: inline;" type="text" name="item_expires_buff[]" value="" placeholder="" />'; 
								html += ' <button class="btn btn-danger removeBuff" name="removeBuff" id="bremove_'+divId+'"> <i class="icon-remove"></i></button>';
								html += '</div>';
								$('#bufflist').append(html);
							});
							$('#addItem').on("click", function(e) {
								e.preventDefault();
								var divId = parseInt($('#itemlist').children().last().attr('id').split('_')[1]) + 1;

								var html = '<div id="item_'+divId+'" style="margin-top:2px;">';
								html += ' Category: <input class="form-control" style="width:40px; display: inline;" type="text" name="item_category[]" value="" />';
								html += ' Index: <input class="form-control" style="width:40px; display: inline;" type="text" name="item_index[]" value="" />';
								html += ' Dur: <input class="form-control" style="width:40px; display: inline;" type="text" name="item_dur[]" value="" />';
								html += ' Level: <input class="form-control" style="width:40px; display: inline;" type="text" name="item_level[]" value="" placeholder="0-15" />';
								html += ' Skill: <input class="form-control" style="width:40px; display: inline;" type="text" name="item_skill[]" value="" placeholder="0/1" />';
								html += ' Luck: <input class="form-control" style="width:40px; display: inline;" type="text" name="item_luck[]" value="" placeholder="0/1" />';
								html += ' Option: <input class="form-control" style="width:40px; display: inline;" type="text" name="item_option[]" value="" placeholder="0-7" />';
								html += ' Excellent: <input class="form-control" style="width:90px; display: inline;" type="text" name="item_excellent[]" value="" placeholder="1,1,1,1,1,1|0-6" />';
								html += ' Ancient: <input class="form-control" style="width:70px; display: inline;" type="text" name="item_ancient[]" value="" placeholder="0/5/6/9/10" />';
								html += ' Expire (minutes): <input class="form-control" style="width:40px; display: inline;" type="text" name="item_expires[]" value="" placeholder="" />'; 
								html += ' <button class="btn btn-danger removeItem" name="removeItem" id="remove_'+divId+'"> <i class="icon-remove"></i></button>';
								html += '</div>';
								$('#itemlist').append(html);
							});
							$('#addItemHex').on("click", function(e) {
								e.preventDefault();
								var divId = parseInt($('#itemlist_hex').children().last().attr('id').split('_')[1]) + 1;

								var html = '<div id="itemhex_'+divId+'" style="margin-top:2px;">';
								html += ' Hex: <input class="form-control" style="width:500px; display: inline;" type="text" name="item_hex[]" value="" />';
								html += ' <button class="btn btn-danger removeItemHex" name="removeItemHex" id="removehex_'+divId+'"> <i class="icon-remove"></i></button>';
								html += '</div>';
								$('#itemlist_hex').append(html);
							});
							$(document).on("click", ".removeItem", function(e) {
								e.preventDefault();
								var id = $(this).attr('id').split('_')[1];
								if(id == 1)
									return false;
								$('#item_'+id).empty();
								$('#item_'+id).hide();
							});
							$(document).on("click", ".removeItemHex", function(e) {
								e.preventDefault();
								var id = $(this).attr('id').split('_')[1];
								if(id == 1)
									return false;
								$('#itemhex_'+id).empty();
								$('#itemhex_'+id).hide();
							});
							$(document).on("click", ".removeBuff", function(e) {
								e.preventDefault();
								var id = $(this).attr('id').split('_')[1];
								if(id == 1)
									return false;
								$('#buff_'+id).empty();
								$('#buff_'+id).hide();
							});
						});
						</script>
                    </div>
                </div>
            </div>
        </div>
    </div>
	<div class="row-fluid">
        <div class="box span12">
            <div class="box-header well" data-original-title>
                <h2>List of codes</h2>

                <div class="box-icon">
                    <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
                </div>
            </div>
            <div class="box-content">
                <?php
                    if(empty($codes)){
                        echo '<div class="alert alert-info">No gift codes</div>';
                    } else{
                        echo '<table class="table">
						  <thead>
							  <tr>
								  <th>Code</th>
								  <th>Server</th>
								  <th>For Class</th>
								  <th>Expires</th>
								  <th>Uses Max/Left</th>
								  <th>Action</th>                                        
							  </tr>
						  </thead>   
						  <tbody>';
                        foreach($codes as $key => $value){
							$servers = '';
							if(str_contains($value['server'], ',')){
								$server = explode(',', $value['server']);
								foreach($server AS $srv){
									$servers .= $this->website->get_value_from_server($srv, 'title').', ';
								}
								$servers = substr($servers, 0, -2);
							}
							else{
								$servers .= $this->website->get_value_from_server($value['server'], 'title');
							}
                            echo '<tr>
								<td>' . htmlspecialchars($value['code']) . '</td>
								<td>' . htmlspecialchars($servers) . '</td>';
							if(isset($value['char_class']) && ($value['char_class'] != '' and $value['char_class'] != null)){
								$classes = json_decode($value['char_class'], true);
								foreach($classes AS $key => $class){
									$classes[$key] = $this->website->get_char_class($class, true);
								}
								echo '<td class="center" style="inline-size: 300px;word-break: break-all;">' . implode(',', array_unique($classes)). '</td>';
							}
							else{
								echo '<td class="center">All</td>';
							}	
							echo '<td class="center">' . date(DATETIME_FORMAT, $value['expires']) . '</td>
								<td class="center">' . $value['max_uses_total'] . ' / ' . $value['uses_left'] . '</td>
								<td class="center">
									<a class="btn btn-info" href="' . $this->config->base_url . 'gift-code/edit/' . $value['id'] . '">
										<i class="icon-edit icon-white"></i>  
										Edit                                            
									</a>
									<a class="btn btn-danger" href="' . $this->config->base_url . 'gift-code/delete/' . $value['id'] . '">
										<i class="icon-trash icon-white"></i> 
										Delete
									</a>
								</td>  
							  </tr>';
                        }
                        echo '</tbody></table>';
                    }
                ?>
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {
	$('#coupon_type').on("change", function() {
        if ($(this).val() == 1 || $(this).val() == 2 || $(this).val() == 3 || $(this).val() == 4 || $(this).val() == 5 || $(this).val() == 6 || $(this).val() == 7) {
            $('#coin_amount').show();
        } else {
            $('#coin_amount').hide();
        }
        if ($(this).val() == 8) {
            $('#vip_package').show();
        } else {
            $('#vip_package').hide();
        }
		if ($(this).val() == 9) {
            $('#single_item').show();
        } else {
            $('#single_item').hide();
        }
		if ($(this).val() == 10) {
            $('#single_item_hex').show();
        } else {
            $('#single_item_hex').hide();
        }
		if ($(this).val() == 11) {
			$('#buff_item').show();
		} else {
			$('#buff_item').hide();
		}
    });
	
	var coupon_type = $("#coupon_type option:selected").val();
	if (coupon_type == 1 || coupon_type == 2 || coupon_type == 3 || coupon_type == 4 || coupon_type == 5 || coupon_type == 6 || coupon_type == 7) {
		$('#coin_amount').show();
	} else {
		$('#coin_amount').hide();
	}
	if (coupon_type == 8) {
		$('#vip_package').show();
	} else {
		$('#vip_package').hide();
	}
	if (coupon_type == 9) {
		$('#single_item').show();
	} else {
		$('#single_item').hide();
	}
	if (coupon_type == 10) {
		$('#single_item_hex').show();
	} else {
		$('#single_item_hex').hide();
	}
	if (coupon_type == 11) {
		$('#buff_item').show();
	} else {
		$('#buff_item').hide();
	}
});
</script>
<?php
$this->load->view('admincp' . DS . 'view.footer');
?>
