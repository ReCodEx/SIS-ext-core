<?php

$container = require_once __DIR__ . "/../bootstrap.php";

use App\Model\Entity\SisTerm;
use App\Presenters\TermsPresenter;
use Doctrine\ORM\EntityManagerInterface;
use Tester\Assert;

/**
 * @testCase
 */
class TestTermsPresenter extends Tester\TestCase
{
    /** @var TermsPresenter */
    protected $presenter;

    /** @var EntityManagerInterface */
    protected $em;

    /** @var  Nette\DI\Container */
    protected $container;

    /** @var Nette\Security\User */
    private $user;

    public function __construct()
    {
        global $container;
        $this->container = $container;
        $this->em = PresenterTestHelper::getEntityManager($container);
        $this->user = $container->getByType(\Nette\Security\User::class);
    }

    protected function setUp()
    {
        PresenterTestHelper::fillDatabase($this->container);
        $this->presenter = PresenterTestHelper::createPresenter(
            $this->container,
            TermsPresenter::class
        );
    }

    protected function tearDown()
    {
        Mockery::close();

        if ($this->user->isLoggedIn()) {
            $this->user->logout(true);
        }
    }

    public function testListTerms()
    {
        PresenterTestHelper::login($this->container, PresenterTestHelper::STUDENT_LOGIN);

        $payload = PresenterTestHelper::performPresenterRequest(
            $this->presenter,
            'Terms',
            'GET',
            ['action' => 'default']
        );

        Assert::count(2, $payload);
    }
}

(new TestTermsPresenter())->run();
