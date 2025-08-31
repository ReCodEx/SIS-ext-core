<?php

$container = require_once __DIR__ . "/../bootstrap.php";

use App\Presenters\CoursesPresenter;
use App\Helpers\SisHelper;
use App\Helpers\SisCourseRecord;
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

    public function testStudentRefetchCourses()
    {
        PresenterTestHelper::login($this->container, PresenterTestHelper::STUDENT1_LOGIN);
        $this->presenter->sis->shouldReceive("getCourses")->withArgs(["stud1", ['2025-1']])
            ->andReturn([
                SisCourseRecord::fromArray('stud1', [
                    'id' => 'gl1x',
                    'course' => 'prg1',
                    'type' => 'X',
                    'year' => 2025,
                    'semester' => 1,
                    'day_of_week' => 1,
                    'time' => 900,
                    'room' => 'X1',
                    'firstweek' => 2,
                    'fortnight' => 1,
                    'affiliation' => 'student',
                    'caption_cs' => 'Prog fix',
                    'caption_en' => 'Prg fix',
                    'annotation_cs' => 'an-cs',
                    'annotation_en' => 'an-en',
                ]),
                SisCourseRecord::fromArray('stud1', [
                    'id' => 'gl9p',
                    'course' => 'swe1',
                    'type' => 'P',
                    'year' => 2025,
                    'semester' => 1,
                    'day_of_week' => 5,
                    'time' => 1200,
                    'room' => 'A1',
                    'firstweek' => 1,
                    'fortnight' => 0,
                    'affiliation' => 'student',
                    'caption_cs' => 'SWI',
                    'caption_en' => 'SWE',
                    'annotation_cs' => 'an-cs',
                    'annotation_en' => 'an-en',
                ]),
            ])->once();

        $eventsCount = $this->presenter->sisScheduleEvents->count();
        $coursesCount = $this->presenter->sisCourses->count();

        $payload = PresenterTestHelper::performPresenterRequest(
            $this->presenter,
            'Courses',
            'POST',
            ['action' => 'default'],
            ['year' => 2025, 'term' => 1, 'expiration' => 0]
        );

        Assert::count(2, $payload);
        Assert::true(array_key_exists('student', $payload));
        Assert::true($payload['refetched'] ?? null);
        Assert::count(2, $payload['student']);

        $data = [];
        foreach ($payload['student'] as $event) {
            $data[$event->getSisId()] = $event;
        }
        $ids = array_keys($data);
        sort($ids);
        Assert::equal(['gl1x', 'gl9p'], $ids);

        // updates were made
        Assert::equal(900, $data['gl1x']->getTime());
        Assert::equal('X1', $data['gl1x']->getRoom());
        Assert::true($data['gl1x']->getFortnight());
        Assert::equal(2, $data['gl1x']->getFirstWeek());
        Assert::equal('Prog fix', $data['gl1x']->getCourse()->getCaption('cs'));
        Assert::equal('Prg fix', $data['gl1x']->getCourse()->getCaption('en'));
        Assert::equal('an-cs', $data['gl1x']->getCourse()->getAnnotation('cs'));
        Assert::equal('an-en', $data['gl1x']->getCourse()->getAnnotation('en'));

        // new course and event were created
        Assert::equal($eventsCount + 1, $this->presenter->sisScheduleEvents->count());
        Assert::equal($coursesCount + 1, $this->presenter->sisCourses->count());

        Assert::equal(1200, $data['gl9p']->getTime());
        Assert::equal('A1', $data['gl9p']->getRoom());
        Assert::false($data['gl9p']->getFortnight());
        Assert::equal(1, $data['gl9p']->getFirstWeek());
        Assert::equal('SWI', $data['gl9p']->getCourse()->getCaption('cs'));
        Assert::equal('SWE', $data['gl9p']->getCourse()->getCaption('en'));
        Assert::equal('an-cs', $data['gl9p']->getCourse()->getAnnotation('cs'));
        Assert::equal('an-en', $data['gl9p']->getCourse()->getAnnotation('en'));
    }

    public function testTeacherRefetchCourses()
    {
        PresenterTestHelper::login($this->container, PresenterTestHelper::TEACHER1_LOGIN);
        $this->presenter->sis->shouldReceive("getCourses")->withArgs(["teach1", ['2025-1']])
            ->andReturn([
                SisCourseRecord::fromArray('teach1', [
                    'id' => 'gl1x',
                    'course' => 'prg1',
                    'type' => 'X',
                    'year' => 2025,
                    'semester' => 1,
                    'day_of_week' => 1,
                    'time' => 900,
                    'room' => 'X1',
                    'firstweek' => 2,
                    'fortnight' => 1,
                    'affiliation' => 'teacher',
                    'caption_cs' => 'Prog fix',
                    'caption_en' => 'Prg fix',
                    'annotation_cs' => 'an-cs',
                    'annotation_en' => 'an-en',
                ]),
                SisCourseRecord::fromArray('teach1', [
                    'id' => 'gl9p',
                    'course' => 'swe1',
                    'type' => 'P',
                    'year' => 2025,
                    'semester' => 1,
                    'day_of_week' => 5,
                    'time' => 1200,
                    'room' => 'A1',
                    'firstweek' => 1,
                    'fortnight' => 0,
                    'affiliation' => 'guarantor',
                    'caption_cs' => 'SWI',
                    'caption_en' => 'SWE',
                    'annotation_cs' => 'an-cs',
                    'annotation_en' => 'an-en',
                ]),
            ])->once();

        $eventsCount = $this->presenter->sisScheduleEvents->count();
        $coursesCount = $this->presenter->sisCourses->count();

        $payload = PresenterTestHelper::performPresenterRequest(
            $this->presenter,
            'Courses',
            'POST',
            ['action' => 'default'],
            ['year' => 2025, 'term' => 1, 'expiration' => 0, 'affiliation' => 'teacher']
        );

        Assert::count(3, $payload);
        Assert::true(array_key_exists('teacher', $payload));
        Assert::true(array_key_exists('guarantor', $payload));
        Assert::true($payload['refetched'] ?? null);
        Assert::count(1, $payload['teacher']);
        Assert::count(1, $payload['guarantor']);

        $data = [
            $payload['teacher'][0]->getSisId() => $payload['teacher'][0],
            $payload['guarantor'][0]->getSisId() => $payload['guarantor'][0],
        ];
        $ids = array_keys($data);
        sort($ids);
        Assert::equal(['gl1x', 'gl9p'], $ids);

        // updates were made
        Assert::equal(900, $data['gl1x']->getTime());
        Assert::equal('X1', $data['gl1x']->getRoom());
        Assert::true($data['gl1x']->getFortnight());
        Assert::equal(2, $data['gl1x']->getFirstWeek());
        Assert::equal('Prog fix', $data['gl1x']->getCourse()->getCaption('cs'));
        Assert::equal('Prg fix', $data['gl1x']->getCourse()->getCaption('en'));
        Assert::equal('an-cs', $data['gl1x']->getCourse()->getAnnotation('cs'));
        Assert::equal('an-en', $data['gl1x']->getCourse()->getAnnotation('en'));

        // new course and event were created
        Assert::equal($eventsCount + 1, $this->presenter->sisScheduleEvents->count());
        Assert::equal($coursesCount + 1, $this->presenter->sisCourses->count());

        Assert::equal(1200, $data['gl9p']->getTime());
        Assert::equal('A1', $data['gl9p']->getRoom());
        Assert::false($data['gl9p']->getFortnight());
        Assert::equal(1, $data['gl9p']->getFirstWeek());
        Assert::equal('SWI', $data['gl9p']->getCourse()->getCaption('cs'));
        Assert::equal('SWE', $data['gl9p']->getCourse()->getCaption('en'));
        Assert::equal('an-cs', $data['gl9p']->getCourse()->getAnnotation('cs'));
        Assert::equal('an-en', $data['gl9p']->getCourse()->getAnnotation('en'));
    }
}

(new TestCoursesPresenter())->run();
