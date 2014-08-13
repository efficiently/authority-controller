<?php

use Mockery as m;

class AcAuthorityTest extends AcTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->user = new stdClass;
        $this->user->id = 1;
        $this->user->email = "testuser@localhost";
        $this->user->name = "TestUser";

        $this->authority = App::make('authority');
        $this->authority->setCurrentUser($this->user);
    }

    public function tearDown()
    {
        m::close();
    }

    public function testCanStoreCurrentUser()
    {
        $this->assertSame($this->user, $this->authority->getCurrentUser());

        $user = new stdClass;
        $this->authority->setCurrentUser($user);
        $this->assertSame($user, $this->authority->getCurrentUser());
    }

    public function testCanEvaluateRulesOnObject()
    {
        $rulesCount = $this->authority->getRules()->count();
        $this->authority->allow('destroy', 'Project', function ($self, $project) {
            return $self->user()->id === $project->user_id;
        });
        $this->assertGreaterThan($rulesCount, $this->authority->getRules()->count());

        $project = m::mock('Project');
        $project->user_id = 1;
        $this->assertCan('destroy', $project);

        $this->assertCannot('destroy', new stdClass);
    }

    // A user cannot do anything without rules
    public function testCannotDoAnythingWithoutRules()
    {
        $project = $this->mock('Project');

        $this->assertCount(0, $this->authority->getRules());

        $this->assertCannot('read', 'all');
        $this->assertCannot('read', $project);
        $this->assertCannot('read', 'Project');

        $this->assertCannot('create', 'all');
        $this->assertCannot('create', $project);
        $this->assertCannot('create', 'Project');

        $this->assertCannot('update', 'all');
        $this->assertCannot('update', $project);
        $this->assertCannot('update', 'Project');

        $this->assertCannot('delete', 'all');
        $this->assertCannot('delete', $project);
        $this->assertCannot('delete', 'Project');

        $this->assertCannot('manage', 'all');
        $this->assertCannot('manage', $project);
        $this->assertCannot('manage', 'Project');
    }

    // Adding deny rule overrides prior rules
    public function testDenyRuleOverridesPriorRules()
    {
        $project = $this->mock('Project');

        $this->authority->allow('manage', 'Project');
        $this->authority->deny('destroy', 'Project');

        $this->assertCannot('destroy', $project);
        $this->assertCan('read', $project);
        $this->assertCan('update', $project);
    }

    // Adding allow rules do not override prior rules, but instead are logically or'ed
    public function testAllowRulesDoNotOverridePriorRules()
    {
        $user = $this->mock("User");
        $userAttributes = [
            "id" => 1, "email" => "admin@localhost", "name" => "Administrator",
            "created_at" => "2013-12-17 10:17:21", "updated_at" => "2013-12-17 10:17:21"
        ];
        $this->fillMock($user, $userAttributes);

        $this->authority = App::make('authority');
        $this->authority->setCurrentUser($this->user);

        $this->authority->allow('read', 'User', function ($self, $user) {
            return $user->id != 1;// Should return false
        });

        $this->authority->allow('read', 'User', function ($self, $user) {
            return $user->email == "admin@localhost";// Should return true
        });

        $this->authority->allow('read', 'User', function ($self, $user) {
            return $user->name != "Administrator";// Should return false
        });

        // $user can view 'index' action even if there is only one 'allow' rules which is true
        $this->assertCan('index', $user);
    }

    public function testRulesPrecedence()
    {
        $user = $this->mock("User");
        $userAttributes = [
            "id" => 1, "email" => "admin@localhost", "name" => "Administrator",
            "created_at" => "2013-12-17 10:17:21", "updated_at" => "2013-12-17 10:17:21"
        ];
        $this->fillMock($user, $userAttributes);

        $this->authority = App::make('authority');
        $this->authority->setCurrentUser($this->user);

        $this->authority->allow('read', 'User', function ($self, $user) {
            return $user->id != 1;// Should return false
        });

        $this->authority->allow('read', 'User', function ($self, $user) {
            return $user->email != "admin@localhost";// Should return false
        });

        $this->authority->allow('read', 'User', function ($self, $user) {
            return $user->name != "Administrator";// Should return false
        });

        $this->authority->allow('update', 'User');

        $this->assertCan('update', 'User');
        $this->assertCan('update', $user);

        $this->assertCan('index', 'User');

        // $user cannot view 'index' action if there is only 'allow' rules with conditions
        $this->assertCannot('index', $user);

        // $user can view 'index' action if there is above one 'allow' rule without conditions
        $this->authority->allow('index', 'User');
        $this->assertCan('index', $user);

        // $user cannot view the 'index' action if there above one 'deny' rules with  conditions
        $this->authority->deny('read', 'User', function ($self, $user) {
            return $user->name == "Administrator";// Should return true
        });
        $this->assertCannot('index', $user);

        // Deny rule is overrided by allow rule
        $this->authority->allow('index', 'User');
        $this->assertCan('index', $user);

        // $user cannot view the 'index' action if there above one 'deny' rules without conditions
        $this->authority->deny('index', 'User');
        $this->assertCannot('index', $user);
    }

    public function testDefaultAliasActions()
    {
        $this->assertEquals(['read'], $this->authority->getAliasesForAction('index'));
        $this->assertEquals(['read'], $this->authority->getAliasesForAction('show'));

        $this->assertEquals(['create'], $this->authority->getAliasesForAction('new'));
        $this->assertEquals(['create'], $this->authority->getAliasesForAction('store'));

        $this->assertEquals(['update'], $this->authority->getAliasesForAction('edit'));

        $this->assertEquals(['delete'], $this->authority->getAliasesForAction('destroy'));

        $this->assertEquals(['read', 'index', 'show'], $this->authority->getExpandActions('read'));
        $this->assertEquals(['create', 'new', 'store'], $this->authority->getExpandActions('create'));
        $this->assertEquals(['update', 'edit'], $this->authority->getExpandActions('update'));
        $this->assertEquals(['delete', 'destroy'], $this->authority->getExpandActions('delete'));
    }

    // Helpers

    protected function assertCan($action, $resource, $resourceValue = null, $authority = null)
    {
        $authority = $authority ?: $this->authority;
        $this->assertTrue($authority->can($action, $resource, $resourceValue));
    }

    protected function assertCannot($action, $resource, $resourceValue = null, $authority = null)
    {
        $authority = $authority ?: $this->authority;
        $this->assertTrue($this->authority->cannot($action, $resource, $resourceValue));
    }
}
