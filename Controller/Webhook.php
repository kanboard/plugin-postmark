<?php

namespace Kanboard\Plugin\Postmark\Controller;

use Kanboard\Controller\Base;
use Kanboard\Plugin\Postmark\EmailHandler;

/**
 * Webhook Controller
 *
 * @package  postmark
 * @author   Frederic Guillot
 */
class Webhook extends Base
{
    /**
     * Handle Postmark webhooks
     *
     * @access public
     */
    public function receiver()
    {
        $this->checkWebhookToken();

        $handler = new EmailHandler($this->container);
        echo $handler->receiveEmail($this->request->getJson()) ? 'PARSED' : 'IGNORED';
    }
}
