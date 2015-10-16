<h3><img src="<?= $this->url->dir() ?>plugins/postmark/postmark-icon.png"/>&nbsp;Postmark</h3>
<div class="listing">
    <input type="text" class="auto-select" readonly="readonly" value="<?= $this->url->href('webhook', 'receiver', array('plugin' => 'postmark', 'token' => $values['webhook_token']), false, '', true) ?>"/><br/>
    <p class="form-help"><a href="https://github.com/kanboard/plugin-postmark" target="_blank"><?= t('Help on Postmark integration') ?></a></p>
</div>