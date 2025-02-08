<?php

use App\Helpers\UserUpdater;
use App\Model\Entity\User;
use App\Model\Entity\SisUser;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . "/../bootstrap.php";

class TestUserUpdater extends TestCase
{
    private $userUpdater;
    private $user;
    private $sisUser;

    protected function setUp()
    {
        $this->userUpdater = new UserUpdater();
        $this->user = new User(
            'recodex1',
            'instance1',
            'krulis@d3s.mff.cuni.cz',
            'Martin',
            'Kruliš',
            'RNDr.',
            'Ph.D.',
            null
        );
        $this->user->setSisId('12345678');
        $this->user->setSisLogin('ulogin1');

        $this->sisUser = new SisUser(
            '12345678',
            'ulogin1',
            'krulis@d3s.mff.cuni.cz',
            'Martin',
            'Kruliš',
            'RNDr.',
            'Ph.D.'
        );
    }

    private function changeSisUser()
    {
        $this->sisUser->setFirstName('Castor');
        $this->sisUser->setLastName('Fiber');
        $this->sisUser->setTitlesBeforeName('BOBr.');
        $this->sisUser->setTitlesAfterName('Mh.D.');
        $this->sisUser->setEmail('fiber@d3s.mff.cuni.cz');
        $this->sisUser->setLogin('ulogin2');
    }

    public function testDiff()
    {
        Assert::equal([], $this->userUpdater->diff($this->user, $this->sisUser));

        $this->changeSisUser();
        $diff = $this->userUpdater->diff($this->user, $this->sisUser);

        Assert::equal('Castor', $this->sisUser->getFirstName());
        Assert::equal('Fiber', $this->sisUser->getLastName());
        Assert::equal('BOBr.', $this->sisUser->getTitlesBeforeName());
        Assert::equal('Mh.D.', $this->sisUser->getTitlesAfterName());
        Assert::equal('fiber@d3s.mff.cuni.cz', $this->sisUser->getEmail());
        Assert::equal('ulogin2', $this->sisUser->getLogin());

        Assert::equal($this->sisUser->getFirstName(), $diff['firstName']['new'] ?? '');
        Assert::equal($this->sisUser->getLastName(), $diff['lastName']['new'] ?? '');
        Assert::equal($this->sisUser->getTitlesBeforeName(), $diff['titlesBeforeName']['new'] ?? '');
        Assert::equal($this->sisUser->getTitlesAfterName(), $diff['titlesAfterName']['new'] ?? '');
        Assert::equal($this->sisUser->getEmail(), $diff['email']['new'] ?? '');
        Assert::equal($this->sisUser->getLogin(), $diff['login']['new'] ?? '');

        Assert::equal('Martin', $this->user->getFirstName());
        Assert::equal('Kruliš', $this->user->getLastName());
        Assert::equal('RNDr.', $this->user->getTitlesBeforeName());
        Assert::equal('Ph.D.', $this->user->getTitlesAfterName());
        Assert::equal('krulis@d3s.mff.cuni.cz', $this->user->getEmail());
        Assert::equal('ulogin1', $this->user->getSisLogin());

        Assert::equal($this->user->getFirstName(), $diff['firstName']['old'] ?? '');
        Assert::equal($this->user->getLastName(), $diff['lastName']['old'] ?? '');
        Assert::equal($this->user->getTitlesBeforeName(), $diff['titlesBeforeName']['old'] ?? '');
        Assert::equal($this->user->getTitlesAfterName(), $diff['titlesAfterName']['old'] ?? '');
        Assert::equal($this->user->getEmail(), $diff['email']['old'] ?? '');
        Assert::equal($this->user->getSisLogin(), $diff['login']['old'] ?? '');
    }

    public function testUpdate()
    {
        Assert::false($this->userUpdater->update($this->user, $this->sisUser));
        $this->changeSisUser();
        Assert::true($this->userUpdater->update($this->user, $this->sisUser));

        Assert::equal('Castor', $this->sisUser->getFirstName());
        Assert::equal('Fiber', $this->sisUser->getLastName());
        Assert::equal('BOBr.', $this->sisUser->getTitlesBeforeName());
        Assert::equal('Mh.D.', $this->sisUser->getTitlesAfterName());
        Assert::equal('fiber@d3s.mff.cuni.cz', $this->sisUser->getEmail());
        Assert::equal('ulogin2', $this->sisUser->getLogin());

        Assert::equal($this->sisUser->getFirstName(), $this->user->getFirstName());
        Assert::equal($this->sisUser->getLastName(), $this->user->getLastName());
        Assert::equal($this->sisUser->getTitlesBeforeName(), $this->user->getTitlesBeforeName());
        Assert::equal($this->sisUser->getTitlesAfterName(), $this->user->getTitlesAfterName());
        Assert::equal($this->sisUser->getEmail(), $this->user->getEmail());
        Assert::equal($this->sisUser->getLogin(), $this->user->getSisLogin());
    }
}

(new TestUserUpdater())->run();
