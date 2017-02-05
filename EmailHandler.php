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
        $taskId = $this->taskCreationModel->create(array(
            'project_id'  => $project['id'],
            'title'       => $this->helper->mail->filterSubject($payload['Subject']),
            'description' => $this->getTaskDescription($payload),
            'creator_id'  => $user['id'],
            'swimlane_id' => $this->getSwimlaneId($project),
        ));

        if ($taskId > 0) {
            $this->addEmailBodyAsAttachment($taskId, $payload);
            $this->uploadAttachments($taskId, $payload);
            return true;
        }

        return false;
    }

    /**
     * Get swimlane id
     *
     * @access protected
     * @param  array $project
     * @return string
     */
    protected function getSwimlaneId(array $project)
    {
        $swimlane = $this->swimlaneModel->getFirstActiveSwimlane($project['id']);
        return empty($swimlane) ? 0 : $swimlane['id'];
    }

    protected function getTaskDescription(array $payload)
    {
        if (! empty($payload['HtmlBody'])) {
            $htmlConverter = new HtmlConverter(array(
                'strip_tags'   => true,
                'remove_nodes' => 'meta script style link img span',
            ));

            $markdown = $htmlConverter->convert($payload['HtmlBody']);

            // Document parsed incorrectly
            if (strpos($markdown, 'html') !== false && ! empty($payload['TextBody'])) {
                return $payload['TextBody'];
            }

            return $markdown;
        } elseif (! empty($payload['TextBody'])) {
            return $payload['TextBody'];
        }

        return '';
    }

    protected function addEmailBodyAsAttachment($taskId, array $payload)
    {
        $filename = t('Email') . '.txt';
        $data = '';

        if (! empty($payload['HtmlBody'])) {
            $data = $payload['HtmlBody'];
            $filename = t('Email') . '.html';
        } elseif (! empty($payload['TextBody'])) {
            $data = $payload['TextBody'];
        }

        if (! empty($data)) {
            $this->taskFileModel->uploadContent($taskId, $filename, $data, false);
        }
    }

    protected function uploadAttachments($taskId, array $payload)
    {
        foreach ($payload['Attachments'] as $attachment) {
            $this->taskFileModel->uploadContent($taskId, $attachment['Name'], $attachment['Content']);
        }
    }
}
