<?php

require_once 'tests/units/Base.php';

use Kanboard\Plugin\Postmark\EmailHandler;
use Kanboard\Model\TaskCreation;
use Kanboard\Model\TaskFinder;
use Kanboard\Model\Project;
use Kanboard\Model\ProjectPermission;
use Kanboard\Model\User;

class EmailHandlerTest extends Base
{
    public function testSendEmail()
    {
        $pm = new EmailHandler($this->container);
        $pm->sendEmail('test@localhost', 'Me', 'Test', 'Content', 'Bob');

        $this->assertEquals('https://api.postmarkapp.com/email', $this->container['httpClient']->getUrl());

        $data = $this->container['httpClient']->getData();

        $this->assertArrayHasKey('From', $data);
        $this->assertArrayHasKey('To', $data);
        $this->assertArrayHasKey('Subject', $data);
        $this->assertArrayHasKey('HtmlBody', $data);

        $this->assertEquals('Me <test@localhost>', $data['To']);
        $this->assertEquals('Bob <notifications@kanboard.local>', $data['From']);
        $this->assertEquals('Test', $data['Subject']);
        $this->assertEquals('Content', $data['HtmlBody']);

        $this->assertContains('Accept: application/json', $this->container['httpClient']->getHeaders());
        $this->assertContains('X-Postmark-Server-Token: ', $this->container['httpClient']->getHeaders());
    }

    public function testHandlePayload()
    {
        $w = new EmailHandler($this->container);
        $p = new Project($this->container);
        $pp = new ProjectPermission($this->container);
        $u = new User($this->container);
        $tc = new TaskCreation($this->container);
        $tf = new TaskFinder($this->container);

        $this->assertEquals(2, $u->create(array('username' => 'me', 'email' => 'me@localhost')));

        $this->assertEquals(1, $p->create(array('name' => 'test1')));
        $this->assertEquals(2, $p->create(array('name' => 'test2', 'identifier' => 'TEST1')));

        // Empty payload
        $this->assertFalse($w->receiveEmail(array()));

        // Unknown user
        $this->assertFalse($w->receiveEmail(array('From' => 'a@b.c', 'Subject' => 'Email task', 'MailboxHash' => 'foobar', 'TextBody' => 'boo')));

        // Project not found
        $this->assertFalse($w->receiveEmail(array('From' => 'me@localhost', 'Subject' => 'Email task', 'MailboxHash' => 'test', 'TextBody' => 'boo')));

        // User is not member
        $this->assertFalse($w->receiveEmail(array('From' => 'me@localhost', 'Subject' => 'Email task', 'MailboxHash' => 'test1', 'TextBody' => 'boo')));
        $this->assertTrue($pp->addMember(2, 2));

        // The task must be created
        $this->assertTrue($w->receiveEmail(array('From' => 'me@localhost', 'Subject' => 'Email task', 'MailboxHash' => 'test1', 'TextBody' => 'boo')));

        $task = $tf->getById(1);
        $this->assertNotEmpty($task);
        $this->assertEquals(2, $task['project_id']);
        $this->assertEquals('Email task', $task['title']);
        $this->assertEquals('boo', $task['description']);
        $this->assertEquals(2, $task['creator_id']);
    }

    public function testHtml2Markdown()
    {
        $w = new EmailHandler($this->container);
        $p = new Project($this->container);
        $pp = new ProjectPermission($this->container);
        $u = new User($this->container);
        $tc = new TaskCreation($this->container);
        $tf = new TaskFinder($this->container);

        $this->assertEquals(2, $u->create(array('username' => 'me', 'email' => 'me@localhost')));
        $this->assertEquals(1, $p->create(array('name' => 'test2', 'identifier' => 'TEST1')));
        $this->assertTrue($pp->addMember(1, 2));

        $this->assertTrue($w->receiveEmail(array('From' => 'me@localhost', 'Subject' => 'Email task', 'MailboxHash' => 'test1', 'TextBody' => 'boo', 'HtmlBody' => '<p><strong>boo</strong></p>')));

        $task = $tf->getById(1);
        $this->assertNotEmpty($task);
        $this->assertEquals(1, $task['project_id']);
        $this->assertEquals('Email task', $task['title']);
        $this->assertEquals('**boo**', $task['description']);
        $this->assertEquals(2, $task['creator_id']);

        $this->assertTrue($w->receiveEmail(array('From' => 'me@localhost', 'Subject' => 'Email task', 'MailboxHash' => 'test1', 'TextBody' => '**boo**', 'HtmlBody' => '')));

        $task = $tf->getById(2);
        $this->assertNotEmpty($task);
        $this->assertEquals(1, $task['project_id']);
        $this->assertEquals('Email task', $task['title']);
        $this->assertEquals('**boo**', $task['description']);
        $this->assertEquals(2, $task['creator_id']);
    }
}
