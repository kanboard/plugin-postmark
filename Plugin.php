<?php

namespace Kanboard\Plugin\Postmark;

use Kanboard\Core\Translator;
use Kanboard\Core\Plugin\Base;

/**
 * Postmark Plugin
 *
 * @package  postmark
 * @author   Frederic Guillot
 */
class Plugin extends Base
{
    public function initialize()
    {
        $this->emailClient->setTransport('postmark', '\Kanboard\Plugin\Postmark\EmailHandler');
        $this->template->hook->attach('template:config:integrations', 'postmark:integration');

        $this->on('session.bootstrap', function($container) {
            Translator::load($container['config']->getCurrentLanguage(), __DIR__.'/Locale');
        });
    }

    public function getPluginDescription()
    {
        return 'Postmark Email Integration';
    }

    public function getPluginAuthor()
    {
        return 'Frédéric Guillot';
    }

    public function getPluginVersion()
    {
        return '1.0.0';
    }

    public function getPluginHomepage()
    {
        return 'https://github.com/kanboard/plugin-postmark';
    }
}
