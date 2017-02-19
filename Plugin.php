<?php

namespace Kanboard\Plugin\Postmark;

use Kanboard\Core\Security\Role;
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
        $this->route->addRoute('/postmark/handler/:token', 'WebhookController', 'receiver', 'postmark');
        $this->applicationAccessMap->add('WebhookController', 'receiver', Role::APP_PUBLIC);
    }

    public function onStartup()
    {
        Translator::load($this->languageModel->getCurrentLanguage(), __DIR__.'/Locale');
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
        return '1.0.8';
    }

    public function getPluginHomepage()
    {
        return 'https://github.com/kanboard/plugin-postmark';
    }

    public function getCompatibleVersion()
    {
        return '>=1.0.40';
    }
}
