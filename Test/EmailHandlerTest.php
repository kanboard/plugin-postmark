<?php

require_once 'tests/units/Base.php';

use Kanboard\Plugin\Postmark\EmailHandler;
use Kanboard\Model\TaskFinderModel;
use Kanboard\Model\ProjectModel;
use Kanboard\Model\ProjectUserRoleModel;
use Kanboard\Model\UserModel;
use Kanboard\Core\Security\Role;

class EmailHandlerTest extends Base
{
    public function testSendEmail()
    {
        $this->container['httpClient']
            ->expects($this->once())
            ->method('postJsonAsync')
            ->with(
                'https://api.postmarkapp.com/email',
                $this->anything(),
                $this->anything()
            );

        $pm = new EmailHandler($this->container);
        $pm->sendEmail('test@localhost', 'Me', 'Test', 'Content', 'Bob');
    }

    public function testSendEmailWithAuthorEmail()
    {
        $this->container['httpClient']
            ->expects($this->once())
            ->method('postJsonAsync')
            ->with(
                'https://api.postmarkapp.com/email',
                $this->contains('bob@localhost'),
                $this->anything()
            );

        $pm = new EmailHandler($this->container);
        $pm->sendEmail('test@localhost', 'Me', 'Test', 'Content', 'Bob', 'bob@localhost');
    }

    public function testHandlePayload()
    {
        $emailHandler = new EmailHandler($this->container);
        $projectModel = new ProjectModel($this->container);
        $projectUserRoleModel = new ProjectUserRoleModel($this->container);
        $userModel = new UserModel($this->container);
        $taskFinderModel = new TaskFinderModel($this->container);

        $this->assertEquals(2, $userModel->create(array('username' => 'me', 'email' => 'me@localhost')));

        $this->assertEquals(1, $projectModel->create(array('name' => 'test1')));
        $this->assertEquals(2, $projectModel->create(array('name' => 'test2', 'email' => 'project@localhost')));

        // Empty payload
        $this->assertFalse($emailHandler->receiveEmail(array()));

        // Unknown user
        $this->assertFalse($emailHandler->receiveEmail(array('From' => 'a@b.c', 'Subject' => 'Email task', 'To' => 'project@localhost', 'TextBody' => 'boo')));

        // Project not found
        $this->assertFalse($emailHandler->receiveEmail(array('From' => 'me@localhost', 'Subject' => 'Email task', 'To' => 'unknown@localhost', 'TextBody' => 'boo')));

        // User is not member
        $this->assertFalse($emailHandler->receiveEmail(array('From' => 'me@localhost', 'Subject' => 'Email task', 'To' => 'project@localhost', 'TextBody' => 'boo')));
        $this->assertTrue($projectUserRoleModel->addUser(2, 2, Role::PROJECT_MEMBER));

        // The task must be created
        $this->assertTrue($emailHandler->receiveEmail(array('From' => 'me@localhost', 'Subject' => 'Email task', 'To' => 'project@localhost', 'TextBody' => 'boo')));

        $task = $taskFinderModel->getById(1);
        $this->assertNotEmpty($task);
        $this->assertEquals(2, $task['project_id']);
        $this->assertEquals('Email task', $task['title']);
        $this->assertEquals('boo', $task['description']);
        $this->assertEquals(2, $task['creator_id']);
    }

    public function testHtml2Markdown()
    {
        $emailHandler = new EmailHandler($this->container);
        $projectModel = new ProjectModel($this->container);
        $projectUserRoleModel = new ProjectUserRoleModel($this->container);
        $userModel = new UserModel($this->container);
        $taskFinderModel = new TaskFinderModel($this->container);

        $this->assertEquals(2, $userModel->create(array('username' => 'me', 'email' => 'me@localhost')));
        $this->assertEquals(1, $projectModel->create(array('name' => 'test2', 'email' => 'project@localhost')));
        $this->assertTrue($projectUserRoleModel->addUser(1, 2, Role::PROJECT_MEMBER));

        $this->assertTrue($emailHandler->receiveEmail(array('From' => 'me@localhost', 'Subject' => 'Email task', 'To' => 'project@localhost', 'TextBody' => 'boo', 'HtmlBody' => '<p><strong>boo</strong></p>')));

        $task = $taskFinderModel->getById(1);
        $this->assertNotEmpty($task);
        $this->assertEquals(1, $task['project_id']);
        $this->assertEquals('Email task', $task['title']);
        $this->assertEquals('**boo**', $task['description']);
        $this->assertEquals(2, $task['creator_id']);

        $this->assertTrue($emailHandler->receiveEmail(array('From' => 'me@localhost', 'Subject' => 'Email task', 'To' => 'project@localhost', 'TextBody' => '**boo**', 'HtmlBody' => '')));

        $task = $taskFinderModel->getById(2);
        $this->assertNotEmpty($task);
        $this->assertEquals(1, $task['project_id']);
        $this->assertEquals('Email task', $task['title']);
        $this->assertEquals('**boo**', $task['description']);
        $this->assertEquals(2, $task['creator_id']);
    }
}
