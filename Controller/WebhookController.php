<?php

namespace Kanboard\Plugin\Postmark\Controller;

use Kanboard\Controller\BaseController;
use Kanboard\Plugin\Postmark\EmailHandler;

/**
 * Webhook Controller
 *
 * @package  postmark
 * @author   Frederic Guillot
 */
class WebhookController extends BaseController
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
        $this->response->text($handler->receiveEmail($this->request->getJson()) ? 'PARSED' : 'IGNORED');
    }
}
