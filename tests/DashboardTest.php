<?php

namespace RAD\Streams\Tests;

use Illuminate\Support\Facades\Auth;

class DashboardTest extends TestCase
{
    protected $withDummy = true;

    public function setUp()
    {
        parent::setUp();

        $this->install();
    }

    public function testWeHaveAccessToTheMainSections()
    {
        // We must first login and visit the dashboard page.
        Auth::loginUsingId(1);
        $this->visit(route('streams.dashboard'));

        $this->see('Dashboard');

        // We can see number of Users.
        $this->see('1 Users');

        // list them.
        $this->click('View All Users');
        $this->seePageIs(route('streams.users.index'));

        // and return to dashboard from there.
        $this->click('Dashboard');
        $this->seePageIs(route('streams.dashboard'));

        // We can see number of posts.
        $this->see('4 Post(s)');

        // list them.
        $this->click('View All Posts');
        $this->seePageIs(route('streams.posts.index'));

        // and return to dashboard from there.
        $this->click('Dashboard');
        $this->seePageIs(route('streams.dashboard'));

        // We can see number of Pages.
        $this->see('1 Page(s)');

        // list them.
        $this->click('View All Pages');
        $this->seePageIs(route('streams.pages.index'));

        // and return to Dashboard from there.
        $this->click('Dashboard');
        $this->seePageIs(route('streams.dashboard'));
        $this->see('Dashboard');
    }
}
