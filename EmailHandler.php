<?php

namespace Kanboard\Plugin\Postmark;

require_once __DIR__.'/vendor/autoload.php';

use Kanboard\Core\Base;
use Kanboard\Core\Mail\ClientInterface;
use League\HTMLToMarkdown\HtmlConverter;

/**
 * Postmark Mail Handler
 *
 * @package  postmark
 * @author   Frederic Guillot
 */
class EmailHandler extends Base implements ClientInterface
{
    /**
     * Send a HTML email
     *
     * @access public
     * @param  string  $email
     * @param  string  $name
     * @param  string  $subject
     * @param  string  $html
     * @param  string  $author
     */
    public function sendEmail($email, $name, $subject, $html, $author)
    {
        $headers = array(
            'Accept: application/json',
            'X-Postmark-Server-Token: '.$this->getApiToken(),
        );

        $payload = array(
            'From' => sprintf('%s <%s>', $author, $this->helper->mail->getMailSenderAddress()),
            'To' => sprintf('%s <%s>', $name, $email),
            'Subject' => $subject,
            'HtmlBody' => $html,
        );

        $this->httpClient->postJsonAsync('https://api.postmarkapp.com/email', $payload, $headers);
    }

    /**
     * Parse incoming email
     *
     * @access public
     * @param  array   $payload   Incoming email
     * @return boolean
     */
    public function receiveEmail(array $payload)
    {
        if (empty($payload['From']) || empty($payload['Subject']) || empty($payload['To'])) {
            return false;
        }

        // The user must exists in Kanboard
        $user = $this->userModel->getByEmail($payload['From']);

        if (empty($user)) {
            $this->logger->debug('Postmark: ignored => user not found');
            return false;
        }

        // The project must have a short name
        $project = $this->projectModel->getByEmail($payload['To']);

        if (empty($project)) {
            $this->logger->debug('Postmark: ignored => project not found');
            return false;
        }

        // The user must be member of the project
        if (! $this->projectPermissionModel->isAssignable($project['id'], $user['id'])) {
            $this->logger->debug('Postmark: ignored => user is not member of the project');
            return false;
        }

        // Finally, we create the task
        return (bool) $this->taskCreationModel->create(array(
            'project_id' => $project['id'],
            'title' => $this->helper->mail->filterSubject($payload['Subject']),
            'description' => $this->getTaskDescription($payload),
            'creator_id' => $user['id'],
        ));
    }

    /**
     * @param array $payload
     * @return array
     */
    protected function getTaskDescription(array $payload)
    {
        $description = '';

        if (!empty($payload['HtmlBody'])) {
            $htmlConverter = new HtmlConverter(array('strip_tags' => true));
            $description = $htmlConverter->convert($payload['HtmlBody']);
        } elseif (!empty($payload['TextBody'])) {
            $description = $payload['TextBody'];
        }

        return $description;
    }

    /**
     * Get API token
     *
     * @access public
     * @return string
     */
    public function getApiToken()
    {
        if (defined('POSTMARK_API_TOKEN')) {
            return POSTMARK_API_TOKEN;
        }

        return $this->configModel->get('postmark_api_token');
    }
}
