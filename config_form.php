<div class="field">
    <div class="two columns alpha">
        <label><?php echo __('Server Name'); ?></label>
    </div>
    <div class="inputs five columns omega" >
        <p class='explanation'><?php echo __("A name for the server."); ?>
        </p>
        <div class="input-block">
            <textarea name="libco_server_name" ><?php echo get_option('libco_server_name'); ?></textarea>
        </div>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label><?php echo __('Server Url'); ?></label>
    </div>
    <div class="inputs five columns omega" >
        <p class='explanation'><?php echo __("Server Url."); ?>
        </p>
        <div class="input-block">
            <textarea name="libco_server_url" ><?php echo get_option('libco_server_url'); ?></textarea>
        </div>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label><?php echo __('URL Path'); ?></label>
    </div>
    <div class="inputs five columns omega" >
        <p class='explanation'><?php echo __("URL Path."); ?>
        </p>
        <div class="input-block">
            <textarea name="libco_url_path" ><?php echo get_option('libco_url_path'); ?></textarea>
        </div>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label><?php echo __('Proxy'); ?></label>
    </div>
    <div class="inputs five columns omega" >
        <p class='explanation'><?php echo __("Proxy server with port number (server_address:port)."); ?>
        </p>
        <div class="input-block">
            <textarea name="libco_server_proxy" ><?php echo get_option('libco_server_proxy'); ?></textarea>
        </div>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label><?php echo __('Token'); ?></label>
    </div>
    <div class="inputs five columns omega" >
        <p class='explanation'><?php echo __("Server login token."); ?>
        </p>
        <div class="input-block">
            <textarea name="libco_server_login_token" ><?php echo get_option('libco_server_login_token'); ?></textarea>
        </div>
    </div>
</div>