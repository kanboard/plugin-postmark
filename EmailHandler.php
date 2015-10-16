<?php

namespace Kanboard\Plugin\Postmark;

use Kanboard\Core\Base;
use Kanboard\Core\Mail\ClientInterface;

defined('POSTMARK_API_TOKEN') or define('POSTMARK_API_TOKEN', '');

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
            'X-Postmark-Server-Token: '.POSTMARK_API_TOKEN,
        );

        $payload = array(
            'From' => sprintf('%s <%s>', $author, MAIL_FROM),
            'To' => sprintf('%s <%s>', $name, $email),
            'Subject' => $subject,
            'HtmlBody' => $html,
        );

        $this->httpClient->postJson('https://api.postmarkapp.com/email', $payload, $headers);
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
        if (empty($payload['From']) || empty($payload['Subject']) || empty($payload['MailboxHash'])) {
            return false;
        }

        // The user must exists in Kanboard
        $user = $this->user->getByEmail($payload['From']);

        if (empty($user)) {
            $this->logger->debug('Postmark: ignored => user not found');
            return false;
        }

        // The project must have a short name
        $project = $this->project->getByIdentifier($payload['MailboxHash']);

        if (empty($project)) {
            $this->logger->debug('Postmark: ignored => project not found');
            return false;
        }

        // The user must be member of the project
        if (! $this->projectPermission->isMember($project['id'], $user['id'])) {
            $this->logger->debug('Postmark: ignored => user is not member of the project');
            return false;
        }

        // Get the Markdown contents
        if (! empty($payload['HtmlBody'])) {
            $description = $this->htmlConverter->convert($payload['HtmlBody']);
        }
        else if (! empty($payload['TextBody'])) {
            $description = $payload['TextBody'];
        }
        else {
            $description = '';
        }

        // Finally, we create the task
        return (bool) $this->taskCreation->create(array(
            'project_id' => $project['id'],
            'title' => $payload['Subject'],
            'description' => $description,
            'creator_id' => $user['id'],
        ));
    }
}
