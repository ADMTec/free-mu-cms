<div id="content" class="span10">
    <div>
        <ul class="breadcrumb">
            <li><a href="<?php echo $this->config->base_url . ACPURL; ?>">Home</a> <span class="divider">/</span></li>
            <li><a href="<?php echo $this->config->base_url . ACPURL; ?>/manage-settings/security">Security Settings</a>
            </li>
        </ul>
    </div>
    <?php
        if(isset($error)){
            echo '<div class="alert alert-error span12">' . $error . '</div>';
        }
        if(isset($success)){
            echo '<div class="alert alert-success span12">' . $success . '</div>';
        }
    ?>
    <div class="row-fluid">
        <div class="box span12">
            <div class="box-header well">
                <h2><i class="icon-edit"></i> Security Settings</h2>
            </div>
            <div class="box-content">
                <form class="form-horizontal" method="POST" action="" id="security_settings_form">
                    <fieldset>
                        <div class="control-group">
                            <label class="control-label" for="captcha_type">Captcha Type </label>

                            <div class="controls">
                                <select id="mail_mode" name="captcha_type">
                                    <option value="0" <?php if(isset($security_config['captcha_type']) && $security_config['captcha_type'] == 0){
                                        echo 'selected="selected"';
                                    } ?>>None
                                    </option>
                                    <option value="1" <?php if(isset($security_config['captcha_type']) && $security_config['captcha_type'] == 1){
                                        echo 'selected="selected"';
                                    } ?>>JS Captcha
                                    </option>
                                    <option value="3" <?php if(isset($security_config['captcha_type']) && $security_config['captcha_type'] == 3){
                                        echo 'selected="selected"';
                                    } ?>>ReCaptcha v2
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label" for="recaptcha_pub_key">ReCaptcha Public Key </label>
                            <div class="controls">
                                <input type="text" class="span6 typeahead" id="recaptcha_pub_key" name="recaptcha_pub_key" value="<?php echo $security_config['recaptcha_pub_key'] ?? ''; ?>"/>
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label" for="recaptcha_priv_key">ReCaptcha Private Key </label>
                            <div class="controls">
                                <input type="text" class="span6 typeahead" id="recaptcha_priv_key" name="recaptcha_priv_key" value="<?php echo $security_config['recaptcha_priv_key'] ?? ''; ?>"/>
                                <p class="help-block">You can generate your recaptcha keys <a href="https://www.google.com/recaptcha/intro/index.html" target="_blank">here</a>.
                                </p>
                            </div>
                        </div>
						<div class="control-group">
                            <label class="control-label" for="captcha_on_login">Captcha On Login</label>
                            <div class="controls">
                                <select id="captcha_on_login" name="captcha_on_login">
                                    <option value="0" <?php if(isset($security_config['captcha_on_login']) && $security_config['captcha_on_login'] == 0){ echo 'selected="selected"'; } ?>>No</option>
                                    <option value="1" <?php if(isset($security_config['captcha_on_login']) && $security_config['captcha_on_login'] == 1){ echo 'selected="selected"'; } ?>>Yes</option>
                                </select>
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label" for="allow_mail_change">Email Change</label>
                            <div class="controls">
                                <select id="allow_mail_change" name="allow_mail_change">
                                    <option value="0" <?php if(isset($security_config['allow_mail_change']) && $security_config['allow_mail_change'] == 0){ echo 'selected="selected"'; } ?>>No</option>
                                    <option value="1" <?php if(isset($security_config['allow_mail_change']) && $security_config['allow_mail_change'] == 1){ echo 'selected="selected"'; } ?>>Yes</option>
                                </select>
                                <p class="help-block">Allow change email of account.</p>
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label" for="allow_recover_masterkey">Master Key Recovery</label>
                            <div class="controls">
                                <select id="allow_recover_masterkey" name="allow_recover_masterkey">
                                    <option value="0" <?php if(isset($security_config['allow_recover_masterkey']) && $security_config['allow_recover_masterkey'] == 0){ echo 'selected="selected"'; } ?>>No</option>
                                    <option value="1" <?php if(isset($security_config['allow_recover_masterkey']) && $security_config['allow_recover_masterkey'] == 1){ echo 'selected="selected"'; } ?>>Yes</option>
                                </select>
                                <p class="help-block">Allow master key recovery. Supported by MuEngine server files.</p>
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label" for="2fa">Two Factor Auth</label>
                            <div class="controls">
                                <select id="2fa" name="2fa">
                                    <option value="0" <?php if(isset($security_config['2fa']) && $security_config['2fa'] == 0){ echo 'selected="selected"'; } ?>>No</option>
                                    <option value="1" <?php if(isset($security_config['2fa']) && $security_config['2fa'] == 1){ echo 'selected="selected"'; } ?>>Yes</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary" name="edit_security_settings" id="edit_security_settings">Save changes</button>
                            <button type="reset" class="btn">Cancel</button>
                        </div>
                    </fieldset>
                </form>
            </div>
        </div>
    </div>
</div>