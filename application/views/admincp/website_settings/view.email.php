<div id="content" class="span10">
    <div>
        <ul class="breadcrumb">
            <li><a href="<?php echo $this->config->base_url . ACPURL; ?>">Home</a> <span class="divider">/</span></li>
            <li><a href="<?php echo $this->config->base_url . ACPURL; ?>/manage-settings/email">Email Settings</a></li>
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
                <h2><i class="icon-edit"></i> Email Settings</h2>
            </div>
            <div class="box-content">
                <form class="form-horizontal" method="POST" action="" id="email_settings_form">
                    <fieldset>
                        <div class="control-group">
                            <label class="control-label" for="server_email">Server Email </label>

                            <div class="controls">
                                <input type="text" class="span6 typeahead" id="server_email" name="server_email"
                                       value="<?php echo (isset($email_config['server_email'])) ? $email_config['server_email'] : ''; ?>"/>

                                <p class="help-block">Your or server email.</p>
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label" for="mail_mode">Email Mode </label>

                            <div class="controls">
                                <select id="mail_mode" name="mail_mode">
                                    <option value="0" <?php if(isset($email_config['mail_mode']) && $email_config['mail_mode'] == 0){
                                        echo 'selected="selected"';
                                    } ?>>SMTP
                                    </option>
                                    <option value="1" <?php if(isset($email_config['mail_mode']) && $email_config['mail_mode'] == 1){
                                        echo 'selected="selected"';
                                    } ?>>PHP Mail
                                    </option>
                                    <option value="2" <?php if(isset($email_config['mail_mode']) && $email_config['mail_mode'] == 2){
                                        echo 'selected="selected"';
                                    } ?>>SendMail
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label" for="smtp_server">SMTP Server </label>

                            <div class="controls">
                                <input type="text" class="span6 typeahead" id="smtp_server" name="smtp_server"
                                       value="<?php echo (isset($email_config['smtp_server'])) ? $email_config['smtp_server'] : ''; ?>"/>
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label" for="smtp_port">SMTP Port </label>

                            <div class="controls">
                                <input type="text" class="span6 typeahead" id="smtp_port" name="smtp_port"
                                       value="<?php echo (isset($email_config['smtp_port'])) ? $email_config['smtp_port'] : ''; ?>"
                                       placeholder="25"/>
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label" for="smtp_username">SMTP User </label>

                            <div class="controls">
                                <input type="text" class="span6 typeahead" id="smtp_username" name="smtp_username"
                                       value="<?php echo (isset($email_config['smtp_username'])) ? $email_config['smtp_username'] : ''; ?>"/>
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label" for="smtp_password">SMTP Password </label>

                            <div class="controls">
                                <input type="password" class="span6 typeahead" id="smtp_password" name="smtp_password"
                                       value="<?php echo (isset($email_config['smtp_password'])) ? $email_config['smtp_password'] : ''; ?>"/>
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label">Welcome Email</label>
                            <div class="controls">
                                <label class="radio">
                                    <input type="checkbox" id="welcome_email" name="welcome_email"
                                           value="1" <?php if(isset($email_config['welcome_email']) && $email_config['welcome_email'] == 1){
                                        echo 'checked="checked"';
                                    } ?> data-no-uniform="true"> Send Welcome E-Mail when user creates new account
                                </label>
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label">Vip Notification</label>
                            <div class="controls">
                                <label class="radio">
                                    <input type="checkbox" id="vip_purchase_email" name="vip_purchase_email"
                                           value="1" <?php if(isset($email_config['vip_purchase_email']) && $email_config['vip_purchase_email'] == 1){
                                        echo 'checked="checked"';
                                    } ?> data-no-uniform="true"> Notify user by E-Mail when vip has been purchased
                                    successfully
                                </label>
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label">Donate Notification User</label>
                            <div class="controls">
                                <label class="radio">
                                    <input type="checkbox" id="donate_email_user" name="donate_email_user"
                                           value="1" <?php if(isset($email_config['donate_email_user']) && $email_config['donate_email_user'] == 1){
                                        echo 'checked="checked"';
                                    } ?> data-no-uniform="true"> Notify user by E-Mail when donation has been
                                    successfull
                                </label>
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label">Donate Notification Admin</label>
                            <div class="controls">
                                <label class="radio">
                                    <input type="checkbox" id="donate_email_admin" name="donate_email_admin"
                                           value="1" <?php if(isset($email_config['donate_email_admin']) && $email_config['donate_email_admin'] == 1){
                                        echo 'checked="checked"';
                                    } ?> data-no-uniform="true"> Notify admin by E-Mail when some user makes donation
                                </label>
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label">Ticket New Replies Notification For User</label>
                            <div class="controls">
                                <label class="radio">
                                    <input type="checkbox" id="support_email_user" name="support_email_user"
                                           value="1" <?php if(isset($email_config['support_email_user']) && $email_config['support_email_user'] == 1){
                                        echo 'checked="checked"';
                                    } ?> data-no-uniform="true"> Notify user by E-Mail when administrator have answered
                                    or replied to support ticket
                                </label>
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label">Ticket New Replies Notification For Admin</label>
                            <div class="controls">
                                <label class="radio">
                                    <input type="checkbox" id="support_email_admin" name="support_email_admin"
                                           value="1" <?php if(isset($email_config['support_email_admin']) && $email_config['support_email_admin'] == 1){
                                        echo 'checked="checked"';
                                    } ?> data-no-uniform="true"> Notify admin by E-Mail when user have created or
                                    replied to support ticket
                                </label>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary" name="edit_email_settings"
                                    id="edit_email_settings">Save changes
                            </button>
                            <button type="reset" class="btn">Cancel</button>
                        </div>
                    </fieldset>
                </form>
            </div>
        </div>
    </div>
</div>