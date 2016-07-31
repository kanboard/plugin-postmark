<h3><img src="<?= $this->url->dir() ?>plugins/Postmark/postmark-icon.png"/>&nbsp;Postmark</h3>
<div class="listing">
    <input type="text" class="auto-select" readonly="readonly" value="<?= $this->url->href('WebhookController', 'receiver', array('plugin' => 'postmark', 'token' => $values['webhook_token']), false, '', true) ?>"/>

    <?= $this->form->label(t('Postmark API token'), 'postmark_api_token') ?>
    <?= $this->form->text('postmark_api_token', $values) ?>

    <p class="form-help"><a href="https://kanboard.net/plugin/postmark" target="_blank"><?= t('Help on Postmark integration') ?></a></p>

    <div class="form-actions">
        <input type="submit" value="<?= t('Save') ?>" class="btn btn-blue">
    </div>
</div>
