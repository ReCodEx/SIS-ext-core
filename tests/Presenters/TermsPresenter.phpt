<?php

$container = require_once __DIR__ . "/../bootstrap.php";

use App\Model\Entity\SisTerm;
use App\Presenters\TermsPresenter;
use App\Exceptions\BadRequestException;
use App\Exceptions\ForbiddenRequestException;
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
        foreach ($payload as $term) {
            Assert::type(SisTerm::class, $term);
            Assert::equal(2024, $term->getYear());
        }
    }

    public function testCreateTerm()
    {
        PresenterTestHelper::loginDefaultAdmin($this->container);

        $beginning = (new DateTime('2025-10-01'))->getTimestamp();
        $end = (new DateTime('2026-01-31'))->getTimestamp();

        $payload = PresenterTestHelper::performPresenterRequest(
            $this->presenter,
            'Terms',
            'POST',
            ['action' => 'create'],
            [
                'year' => 2025,
                'term' => 1,
                'beginning' => $beginning,
                'end' => $end,
                'studentsFrom' => $beginning,
                'studentsUntil' => $end,
                'teachersFrom' => $beginning - 30,
                'teachersUntil' => $end + 30,
                'archiveAfter' => $end + 60,
            ]
        );

        Assert::count(3, $this->presenter->sisTerms->findAll());
        Assert::type(SisTerm::class, $payload);
        Assert::equal(2025, $payload->getYear());
        Assert::equal(1, $payload->getTerm());
        Assert::equal($beginning, $payload->getBeginning()->getTimestamp());
        Assert::equal($end, $payload->getEnd()->getTimestamp());
        Assert::equal($beginning, $payload->getStudentsFrom()->getTimestamp());
        Assert::equal($end, $payload->getStudentsUntil()->getTimestamp());
        Assert::equal($beginning - 30, $payload->getTeachersFrom()->getTimestamp());
        Assert::equal($end + 30, $payload->getTeachersUntil()->getTimestamp());
        Assert::equal($end + 60, $payload->getArchiveAfter()->getTimestamp());
    }

    public function testCreateTermMinimal()
    {
        PresenterTestHelper::loginDefaultAdmin($this->container);

        $beginning = (new DateTime('2025-10-01'))->getTimestamp();
        $end = (new DateTime('2026-01-31'))->getTimestamp();

        $payload = PresenterTestHelper::performPresenterRequest(
            $this->presenter,
            'Terms',
            'POST',
            ['action' => 'create'],
            [
                'year' => 2025,
                'term' => 1,
                'studentsFrom' => $beginning,
                'studentsUntil' => $end,
                'teachersFrom' => $beginning - 30,
                'teachersUntil' => $end + 30,
            ]
        );

        Assert::count(3, $this->presenter->sisTerms->findAll());
        Assert::type(SisTerm::class, $payload);
        Assert::equal(2025, $payload->getYear());
        Assert::equal(1, $payload->getTerm());
        Assert::null($payload->getBeginning());
        Assert::null($payload->getEnd());
        Assert::equal($beginning, $payload->getStudentsFrom()->getTimestamp());
        Assert::equal($end, $payload->getStudentsUntil()->getTimestamp());
        Assert::equal($beginning - 30, $payload->getTeachersFrom()->getTimestamp());
        Assert::equal($end + 30, $payload->getTeachersUntil()->getTimestamp());
        Assert::null($payload->getArchiveAfter());
    }

    public function testCreateTermDuplicateFail()
    {
        PresenterTestHelper::loginDefaultAdmin($this->container);

        $beginning = (new DateTime('2025-10-01'))->getTimestamp();
        $end = (new DateTime('2026-01-31'))->getTimestamp();

        Assert::exception(
            function () use ($beginning, $end) {
                PresenterTestHelper::performPresenterRequest(
                    $this->presenter,
                    'Terms',
                    'POST',
                    ['action' => 'create'],
                    [
                        'year' => 2024,
                        'term' => 1,
                        'studentsFrom' => $beginning,
                        'studentsUntil' => $end,
                        'teachersFrom' => $beginning - 30,
                        'teachersUntil' => $end + 30,
                    ]
                );
            },
            BadRequestException::class
        );
    }

    public function testCreateTermWrongDatesFail()
    {
        PresenterTestHelper::loginDefaultAdmin($this->container);

        $beginning = (new DateTime('2025-10-01'))->getTimestamp();
        $end = (new DateTime('2030-01-31'))->getTimestamp();

        Assert::exception(
            function () use ($beginning, $end) {
                PresenterTestHelper::performPresenterRequest(
                    $this->presenter,
                    'Terms',
                    'POST',
                    ['action' => 'create'],
                    [
                        'year' => 2025,
                        'term' => 1,
                        'studentsFrom' => $beginning,
                        'studentsUntil' => $end,
                        'teachersFrom' => $beginning - 30,
                        'teachersUntil' => $end + 30,
                    ]
                );
            },
            BadRequestException::class
        );
    }

    public function testCreateTermTeacherFails()
    {
        PresenterTestHelper::login($this->container, PresenterTestHelper::TEACHER_LOGIN);

        $beginning = (new DateTime('2025-10-01'))->getTimestamp();
        $end = (new DateTime('2026-01-31'))->getTimestamp();

        Assert::exception(
            function () use ($beginning, $end) {
                PresenterTestHelper::performPresenterRequest(
                    $this->presenter,
                    'Terms',
                    'POST',
                    ['action' => 'create'],
                    [
                        'year' => 2025,
                        'term' => 1,
                        'studentsFrom' => $beginning,
                        'studentsUntil' => $end,
                        'teachersFrom' => $beginning - 30,
                        'teachersUntil' => $end + 30,
                    ]
                );
            },
            ForbiddenRequestException::class
        );
    }

    public function testDetailTerm()
    {
        PresenterTestHelper::login($this->container, PresenterTestHelper::STUDENT_LOGIN);

        $term = $this->presenter->sisTerms->findTerm(2024, 1);
        Assert::type(SisTerm::class, $term);

        $payload = PresenterTestHelper::performPresenterRequest(
            $this->presenter,
            'Terms',
            'GET',
            ['action' => 'detail', 'id' => $term->getId()]
        );

        Assert::type(SisTerm::class, $payload);
        Assert::equal($term->getId(), $payload->getId());
        Assert::equal(2024, $payload->getYear());
        Assert::equal(1, $payload->getTerm());
        Assert::equal($term->getBeginning()->getTimestamp(), $payload->getBeginning()->getTimestamp());
        Assert::equal($term->getEnd()->getTimestamp(), $payload->getEnd()->getTimestamp());
        Assert::equal($term->getStudentsFrom()->getTimestamp(), $payload->getStudentsFrom()->getTimestamp());
        Assert::equal($term->getStudentsUntil()->getTimestamp(), $payload->getStudentsUntil()->getTimestamp());
        Assert::equal($term->getTeachersFrom()->getTimestamp(), $payload->getTeachersFrom()->getTimestamp());
        Assert::equal($term->getTeachersUntil()->getTimestamp(), $payload->getTeachersUntil()->getTimestamp());
        Assert::equal($term->getArchiveAfter()->getTimestamp(), $payload->getArchiveAfter()->getTimestamp());
    }

    public function testUpdateTerm()
    {
        PresenterTestHelper::loginDefaultAdmin($this->container);

        $term = $this->presenter->sisTerms->findTerm(2024, 1);
        $beginning = $term->getBeginning()->getTimestamp() + 5;
        $end = $term->getEnd()->getTimestamp() + 5;

        $payload = PresenterTestHelper::performPresenterRequest(
            $this->presenter,
            'Terms',
            'POST',
            ['action' => 'update', 'id' => $term->getId()],
            [
                'beginning' => $beginning,
                'end' => $end,
                'studentsFrom' => $beginning,
                'studentsUntil' => $end,
                'teachersFrom' => $beginning - 30,
                'teachersUntil' => $end + 30,
            ]
        );

        Assert::type(SisTerm::class, $payload);
        Assert::equal($term->getId(), $payload->getId());
        Assert::equal(2024, $payload->getYear());
        Assert::equal(1, $payload->getTerm());
        Assert::equal($beginning, $payload->getBeginning()->getTimestamp());
        Assert::equal($end, $payload->getEnd()->getTimestamp());
        Assert::equal($beginning, $payload->getStudentsFrom()->getTimestamp());
        Assert::equal($end, $payload->getStudentsUntil()->getTimestamp());
        Assert::equal($beginning - 30, $payload->getTeachersFrom()->getTimestamp());
        Assert::equal($end + 30, $payload->getTeachersUntil()->getTimestamp());
        Assert::null($payload->getArchiveAfter());
    }

    public function testUpdateTermTeacherFails()
    {
        PresenterTestHelper::login($this->container, PresenterTestHelper::TEACHER_LOGIN);

        $term = $this->presenter->sisTerms->findTerm(2024, 1);
        $beginning = $term->getBeginning()->getTimestamp() + 5;
        $end = $term->getEnd()->getTimestamp() + 5;

        Assert::exception(
            function () use ($term, $beginning, $end) {
                PresenterTestHelper::performPresenterRequest(
                    $this->presenter,
                    'Terms',
                    'POST',
                    ['action' => 'update', 'id' => $term->getId()],
                    [
                        'beginning' => $beginning,
                        'end' => $end,
                        'studentsFrom' => $beginning,
                        'studentsUntil' => $end,
                        'teachersFrom' => $beginning - 30,
                        'teachersUntil' => $end + 30,
                    ]
                );
            },
            ForbiddenRequestException::class
        );
    }

    public function testRemoveTerm()
    {
        PresenterTestHelper::loginDefaultAdmin($this->container);

        $term = $this->presenter->sisTerms->findTerm(2024, 1);
        Assert::type(SisTerm::class, $term);

        $payload = PresenterTestHelper::performPresenterRequest(
            $this->presenter,
            'Terms',
            'DELETE',
            ['action' => 'remove', 'id' => $term->getId()]
        );

        Assert::equal('OK', $payload);
        Assert::count(1, $this->presenter->sisTerms->findAll());
        $remain = $this->presenter->sisTerms->findAll()[0];
        Assert::equal(2024, $remain->getYear());
        Assert::equal(2, $remain->getTerm());
    }

    public function testRemoveTermTeacherFails()
    {
        PresenterTestHelper::login($this->container, PresenterTestHelper::TEACHER_LOGIN);

        $term = $this->presenter->sisTerms->findTerm(2024, 1);
        Assert::type(SisTerm::class, $term);

        Assert::exception(
            function () use ($term) {
                PresenterTestHelper::performPresenterRequest(
                    $this->presenter,
                    'Terms',
                    'DELETE',
                    ['action' => 'remove', 'id' => $term->getId()]
                );
            },
            ForbiddenRequestException::class
        );
    }
}

(new TestTermsPresenter())->run();
