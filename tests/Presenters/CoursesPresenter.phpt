<?php

$container = require_once __DIR__ . "/../bootstrap.php";

use App\Model\Entity\SisTerm;
use App\Presenters\CoursesPresenter;
use App\Helpers\SisHelper;
use App\Exceptions\BadRequestException;
use App\Exceptions\ForbiddenRequestException;
use Doctrine\ORM\EntityManagerInterface;
use Tester\Assert;

/**
 * @testCase
 */
class TestCoursesPresenter extends Tester\TestCase
{
    /** @var CoursesPresenter */
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

        $sisHelperName = current($this->container->findByType(SisHelper::class));
        $this->container->removeService($sisHelperName);
        $this->container->addService($sisHelperName, Mockery::mock(SisHelper::class));
    }

    protected function setUp()
    {
        PresenterTestHelper::fillDatabase($this->container);
        $this->presenter = PresenterTestHelper::createPresenter(
            $this->container,
            CoursesPresenter::class
        );
    }

    protected function tearDown()
    {
        Mockery::close();

        if ($this->user->isLoggedIn()) {
            $this->user->logout(true);
        }
    }

    public function testStudentCourses()
    {
        PresenterTestHelper::login($this->container, PresenterTestHelper::STUDENT1_LOGIN);

        $payload = PresenterTestHelper::performPresenterRequest(
            $this->presenter,
            'Courses',
            'POST',
            ['action' => 'default'],
            ['year' => 2025, 'term' => 1]
        );

        Assert::count(1, $payload);
        Assert::true(array_key_exists('student', $payload));
        Assert::count(2, $payload['student']);

        $ids = array_map(function ($event) {
            return $event->getSisId();
        }, $payload['student']);
        sort($ids);
        Assert::equal(['gl1p', 'gl1x'], $ids);
    }

    public function testTeacherCourses()
    {
        PresenterTestHelper::login($this->container, PresenterTestHelper::TEACHER1_LOGIN);

        $payload = PresenterTestHelper::performPresenterRequest(
            $this->presenter,
            'Courses',
            'POST',
            ['action' => 'default'],
            ['year' => 2025, 'term' => 1]
        );

        Assert::count(3, $payload);
        Assert::true(array_key_exists('student', $payload));
        Assert::true(array_key_exists('teacher', $payload));
        Assert::true(array_key_exists('guarantor', $payload));
        Assert::count(0, $payload['guarantor']);
        Assert::count(0, $payload['student']);
        Assert::count(2, $payload['teacher']);

        $ids = array_map(function ($event) {
            return $event->getSisId();
        }, $payload['teacher']);
        sort($ids);
        Assert::equal(['gl1p', 'gl1x'], $ids);
    }

    public function testTeacherCourses2()
    {
        PresenterTestHelper::login($this->container, PresenterTestHelper::TEACHER1_LOGIN);

        $payload = PresenterTestHelper::performPresenterRequest(
            $this->presenter,
            'Courses',
            'POST',
            ['action' => 'default'],
            ['year' => 2025, 'term' => 1, 'affiliation' => 'teacher']
        );

        Assert::count(2, $payload);
        Assert::true(array_key_exists('teacher', $payload));
        Assert::true(array_key_exists('guarantor', $payload));
        Assert::count(0, $payload['guarantor']);
        Assert::count(2, $payload['teacher']);

        $ids = array_map(function ($event) {
            return $event->getSisId();
        }, $payload['teacher']);
        sort($ids);
        Assert::equal(['gl1p', 'gl1x'], $ids);
    }

    public function testGuarantorCourses()
    {
        PresenterTestHelper::login($this->container, PresenterTestHelper::TEACHER2_LOGIN);

        $payload = PresenterTestHelper::performPresenterRequest(
            $this->presenter,
            'Courses',
            'POST',
            ['action' => 'default'],
            ['year' => 2025, 'term' => 1, 'affiliation' => 'teacher']
        );

        Assert::count(2, $payload);
        Assert::true(array_key_exists('teacher', $payload));
        Assert::true(array_key_exists('guarantor', $payload));
        Assert::count(1, $payload['guarantor']);
        Assert::count(0, $payload['teacher']);
        Assert::equal('gl3p', current($payload['guarantor'])->getSisId());
    }

    public function testFutureTermStudent()
    {
        PresenterTestHelper::login($this->container, PresenterTestHelper::STUDENT1_LOGIN);

        $payload = PresenterTestHelper::performPresenterRequest(
            $this->presenter,
            'Courses',
            'POST',
            ['action' => 'default'],
            ['year' => 2025, 'term' => 2]
        );
        Assert::count(0, $payload);
    }

    public function testFutureTermStudentException()
    {
        PresenterTestHelper::login($this->container, PresenterTestHelper::STUDENT1_LOGIN);

        Assert::exception(function () {
            PresenterTestHelper::performPresenterRequest(
                $this->presenter,
                'Courses',
                'POST',
                ['action' => 'default'],
                ['year' => 2025, 'term' => 2, 'affiliation' => 'student']
            );
        }, ForbiddenRequestException::class);
    }

    public function testFutureTermTeacher()
    {
        PresenterTestHelper::login($this->container, PresenterTestHelper::TEACHER1_LOGIN);

        $payload = PresenterTestHelper::performPresenterRequest(
            $this->presenter,
            'Courses',
            'POST',
            ['action' => 'default'],
            ['year' => 2025, 'term' => 2]
        );
        Assert::count(0, $payload);
    }

    public function testFutureTermTeacherException()
    {
        PresenterTestHelper::login($this->container, PresenterTestHelper::TEACHER1_LOGIN);

        Assert::exception(function () {
            PresenterTestHelper::performPresenterRequest(
                $this->presenter,
                'Courses',
                'POST',
                ['action' => 'default'],
                ['year' => 2025, 'term' => 2, 'affiliation' => 'teacher']
            );
        }, ForbiddenRequestException::class);
    }

    public function testStudentCannotAccessTeacher()
    {
        PresenterTestHelper::login($this->container, PresenterTestHelper::STUDENT1_LOGIN);

        Assert::exception(function () {
            PresenterTestHelper::performPresenterRequest(
                $this->presenter,
                'Courses',
                'POST',
                ['action' => 'default'],
                ['year' => 2025, 'term' => 2, 'affiliation' => 'teacher']
            );
        }, ForbiddenRequestException::class);
    }

    public function testStudentTeacherCourses()
    {
        PresenterTestHelper::login($this->container, PresenterTestHelper::STUDENT_TEACHER_LOGIN);

        $payload = PresenterTestHelper::performPresenterRequest(
            $this->presenter,
            'Courses',
            'POST',
            ['action' => 'default'],
            ['year' => 2025, 'term' => 1]
        );

        Assert::count(3, $payload);
        Assert::true(array_key_exists('student', $payload));
        Assert::true(array_key_exists('teacher', $payload));
        Assert::true(array_key_exists('guarantor', $payload));
        Assert::count(0, $payload['guarantor']);
        Assert::count(2, $payload['student']);
        Assert::count(1, $payload['teacher']);
        Assert::equal('gl3x', current($payload['teacher'])->getSisId());

        $ids = array_map(function ($event) {
            return $event->getSisId();
        }, $payload['student']);
        sort($ids);
        Assert::equal(['gl1p', 'gl1x'], $ids);
    }
}

(new TestCoursesPresenter())->run();
