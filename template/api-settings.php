<?php
defined('ABSPATH')||die('No Script Kiddies Please');
if(isset($_POST['sub_api_secret'])){
    $secret_key=$_POST['sub_api_secret'];
    update_option('sub_api_secret',$secret_key);
}
$secret_key=get_option('sub_api_secret');
?>
<style>
.sub-api-form .form-group div{
    margin:5px;
}
</style>
<div class="sub-api-form">
    <form action="" method="post">
        <div class="form-group" style="display:flex;align-items:center;">

            <div class="label">
            <label for="sub_api_secret">Secret Key</label>
            </div>

            <div class="input">
            <input type="text" name="sub_api_secret" value="<?=$secret_key;?>" id="sub_api_secret" class="regular-text">
            </div>

            <div class="submit">
            <button type="submit_button">Save</button>
            </div>

        </div>
    </form>
</div>
<div class="note">
<p>POST URL TO REQUEST IS : <b><?=site_url()."/wp-json/wc-qlm-webhooks/v1/renew-subscription"?></b>
<br>
If it doesn't work, go to Settings>Permalinks and refresh the page</p>
</div>