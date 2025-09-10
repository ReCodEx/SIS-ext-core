<?php

$container = require_once __DIR__ . "/../bootstrap.php";

use App\Helpers\RecodexApiHelper;
use App\Presenters\GroupsPresenter;
use App\Exceptions\BadRequestException;
use App\Exceptions\ForbiddenRequestException;
use App\Helpers\NamingHelper;
use App\Helpers\RecodexGroup;
use App\Model\Repository\Users;
use Doctrine\ORM\EntityManagerInterface;
use Tester\Assert;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Tracy\Debugger;

/**
 * @testCase
 */
class TestGroupsPresenter extends Tester\TestCase
{
    /** @var GroupsPresenter */
    protected $presenter;

    /** @var EntityManagerInterface */
    protected $em;

    /** @var  Nette\DI\Container */
    protected $container;

    /** @var Nette\Security\User */
    private $user;

    /** @var Users */
    private $users;

    /** @var NamingHelper */
    private $namingHelper;

    private $client;

    public function __construct()
    {
        global $container;
        $this->container = $container;
        $this->em = PresenterTestHelper::getEntityManager($container);
        $this->user = $container->getByType(\Nette\Security\User::class);
        $this->users = $container->getByType(Users::class);
        $this->client = Mockery::mock(Client::class);

        $recodexHelperName = current($this->container->findByType(RecodexApiHelper::class));
        $this->namingHelper = $this->container->getByType(NamingHelper::class);
        $this->container->removeService($recodexHelperName);
        $this->container->addService($recodexHelperName, new RecodexApiHelper(
            [
                'extensionId' => 'sis-cuni',
                'apiBase' => 'https://recodex.example/',
            ],
            $this->namingHelper,
            $this->client
        ));
    }

    protected function setUp()
    {
        PresenterTestHelper::fillDatabase($this->container);
        $this->presenter = PresenterTestHelper::createPresenter(
            $this->container,
            GroupsPresenter::class
        );
    }

    protected function tearDown()
    {
        Mockery::close();

        if ($this->user->isLoggedIn()) {
            $this->user->logout(true);
        }
    }

    private static function group(
        string $id,
        ?string $parentId,
        string $name,
        bool $org = false,
        array $attributes = [],
        ?string $membership = null
    ): array {
        return [
            "id" => $id,
            "parentGroupId" => $parentId,
            "admins" => [
                "teacher1" => [
                    "titlesBeforeName" => "",
                    "firstName" => "First",
                    "lastName" => "Teacher",
                    "titlesAfterName" => "",
                    "email" => "teacher1@recodex"
                ]
            ],
            "localizedTexts" => [
                [
                    "id" => "text1",
                    "locale" => "en",
                    "name" => $name,
                    "description" => "",
                    "createdAt" => 1738275050
                ]
            ],
            "organizational" => $org,
            "exam" => false,
            "public" => false,
            "detaining" => false,
            "attributes" => $attributes ? ['sis-cuni' => $attributes] : [],
            "membership" => $membership
        ];
    }

    public function testListGroupsStudent()
    {
        Debugger::enable(false);

        PresenterTestHelper::login($this->container, PresenterTestHelper::STUDENT1_LOGIN);
        $this->client->shouldReceive("get")->with('group-attributes', Mockery::any())
            ->andReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'success' => true,
                'code' => 200,
                'payload' => [
                    self::group('p1', null, 'Parent', true, []),
                    self::group('p2', null, 'Parent 2', true, []),
                    self::group('g1', 'p1', 'Group 1', false, ['group' => ['sis1']]),
                    self::group('g2', 'p1', 'Group 2', false, ['group' => ['sis2']], 'student'),
                    self::group('g3', 'p1', 'Group 3', false, ['group' => ['sis3']]),
                ]
            ])));

        $payload = PresenterTestHelper::performPresenterRequest(
            $this->presenter,
            'Groups',
            'GET',
            ['action' => 'student', 'eventIds' => ['sis1']]
        );

        Assert::count(3, $payload);

        $ids = array_map(fn($group) => $group->id, $payload);
        sort($ids);
        Assert::equal(['g1', 'g2', 'p1'], $ids);
        Assert::null($payload['p1']->parentGroupId);
        Assert::equal('p1', $payload['g1']->parentGroupId);
        Assert::equal('p1', $payload['g2']->parentGroupId);
        Assert::true($payload['p1']->organizational);
        Assert::false($payload['g1']->organizational);
        Assert::false($payload['g2']->organizational);
    }

    public function testListGroupsTeacher()
    {
        PresenterTestHelper::login($this->container, PresenterTestHelper::TEACHER1_LOGIN);
        $this->client->shouldReceive("get")->with('group-attributes', Mockery::any())
            ->andReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'success' => true,
                'code' => 200,
                'payload' => [
                    self::group('r', null, 'Root', true, []),
                    self::group('p1', 'r', 'Prg 1', true, ['course' => ['prg1']]),
                    self::group('p2', 'r', 'Prg 2', true, ['course' => ['prg2']], 'teacher'),
                    self::group('p3', 'r', 'Prg 1 & 3', true, ['course' => ['prg3', 'prg1']]),
                    self::group('p4', 'r', 'Prg 2 & 4', true, ['course' => ['prg2', 'prg4']]),
                    self::group('g1', 'p1', 'Group 1'),
                    self::group('g2', 'p2', 'Group 2', false, ['group' => ['sis2']], 'teacher'),
                    self::group('g3', 'p3', 'Group 3'),
                    self::group('g4', 'p4', 'Group 4'),
                ]
            ])));

        $payload = PresenterTestHelper::performPresenterRequest(
            $this->presenter,
            'Groups',
            'GET',
            ['action' => 'teacher', 'eventIds' => ['sis2'], 'courseIds' => ['prg1']]
        );

        Assert::count(7, $payload);

        $ids = array_map(fn($group) => $group->id, $payload);
        sort($ids);
        Assert::equal(['g1', 'g2', 'g3', 'p1', 'p2', 'p3', 'r'], $ids);
    }

    public function testBindGroup()
    {
        PresenterTestHelper::login($this->container, PresenterTestHelper::TEACHER1_LOGIN);
        $event = $this->presenter->sisEvents->findOneBy(['sisId' => 'gl1p']);
        Assert::notNull($event);

        $this->client->shouldReceive("get")->with('group-attributes', Mockery::any())
            ->andReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'success' => true,
                'code' => 200,
                'payload' => [
                    self::group('r', null, 'Root', true, []),
                    self::group('c1', 'r', 'Course group', true, [RecodexGroup::ATTR_COURSE_KEY => [$event->getCourse()->getCode()]], 'admin'),
                    self::group('t1', 'c1', 'Term group', true, [RecodexGroup::ATTR_TERM_KEY => [$event->getTerm()->getYearTermKey()]]),
                    self::group('g1', 't1', 'Group 1', false, []),
                ]
            ])));

        $this->client->shouldReceive("post")->with('group-attributes/g1', Mockery::any())
            ->andReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'success' => true,
                'code' => 200,
                'payload' => "OK"
            ])));

        $payload = PresenterTestHelper::performPresenterRequest(
            $this->presenter,
            'Groups',
            'POST',
            ['action' => 'bind', 'id' => 'g1', 'eventId' => $event->getId()]
        );

        Assert::equal('OK', $payload);
    }

    public function testBindTermGroup()
    {
        PresenterTestHelper::login($this->container, PresenterTestHelper::TEACHER1_LOGIN);
        $event = $this->presenter->sisEvents->findOneBy(['sisId' => 'gl1p']);
        Assert::notNull($event);

        $this->client->shouldReceive("get")->with('group-attributes', Mockery::any())
            ->andReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'success' => true,
                'code' => 200,
                'payload' => [
                    self::group('r', null, 'Root', true, []),
                    self::group('c1', 'r', 'Course group', true, [RecodexGroup::ATTR_COURSE_KEY => [$event->getCourse()->getCode()]]),
                    self::group('t1', 'c1', 'Term group', false, [RecodexGroup::ATTR_TERM_KEY => [$event->getTerm()->getYearTermKey()]], 'supervisor'),
                ]
            ])));

        $this->client->shouldReceive("post")->with('group-attributes/t1', Mockery::any())
            ->andReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'success' => true,
                'code' => 200,
                'payload' => "OK"
            ])));

        $payload = PresenterTestHelper::performPresenterRequest(
            $this->presenter,
            'Groups',
            'POST',
            ['action' => 'bind', 'id' => 't1', 'eventId' => $event->getId()]
        );

        Assert::equal('OK', $payload);
    }

    public function testBindGroupFailWrong1()
    {
        PresenterTestHelper::login($this->container, PresenterTestHelper::STUDENT1_LOGIN);
        $event = $this->presenter->sisEvents->findOneBy(['sisId' => 'gl1p']);
        Assert::notNull($event);

        Assert::exception(function () use ($event) {
            PresenterTestHelper::performPresenterRequest(
                $this->presenter,
                'Groups',
                'POST',
                ['action' => 'bind', 'id' => 'g1', 'eventId' => $event->getId()]
            );
        }, ForbiddenRequestException::class);
    }

    public function testBindGroupFailWrong2()
    {
        PresenterTestHelper::login($this->container, PresenterTestHelper::TEACHER1_LOGIN);
        $event = $this->presenter->sisEvents->findOneBy(['sisId' => 'gl1p']);
        Assert::notNull($event);

        $this->client->shouldReceive("get")->with('group-attributes', Mockery::any())
            ->andReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'success' => true,
                'code' => 200,
                'payload' => [
                    self::group('r', null, 'Root', true, []),
                    self::group('c1', 'r', 'Course group', true, [RecodexGroup::ATTR_COURSE_KEY => [$event->getCourse()->getCode()]]),
                    self::group('t1', 'c1', 'Term group', true, []),
                    self::group('g1', 't1', 'Group 1', false, []),
                ]
            ])));

        Assert::exception(function () use ($event) {
            PresenterTestHelper::performPresenterRequest(
                $this->presenter,
                'Groups',
                'POST',
                ['action' => 'bind', 'id' => 'g1', 'eventId' => $event->getId()]
            );
        }, ForbiddenRequestException::class);
    }

    public function testBindGroupFailWrong3()
    {
        PresenterTestHelper::login($this->container, PresenterTestHelper::TEACHER1_LOGIN);
        $event = $this->presenter->sisEvents->findOneBy(['sisId' => 'gl1p']);
        Assert::notNull($event);

        $this->client->shouldReceive("get")->with('group-attributes', Mockery::any())
            ->andReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'success' => true,
                'code' => 200,
                'payload' => [
                    self::group('r', null, 'Root', true, []),
                    self::group('c1', 'r', 'Course group', true, []),
                    self::group('t1', 'c1', 'Term group', true, [RecodexGroup::ATTR_TERM_KEY => [$event->getTerm()->getYearTermKey()]]),
                    self::group('g1', 't1', 'Group 1', false, []),
                ]
            ])));

        Assert::exception(function () use ($event) {
            PresenterTestHelper::performPresenterRequest(
                $this->presenter,
                'Groups',
                'POST',
                ['action' => 'bind', 'id' => 'g1', 'eventId' => $event->getId()]
            );
        }, ForbiddenRequestException::class);
    }

    public function testBindGroupFailUnauthorized()
    {
        PresenterTestHelper::login($this->container, PresenterTestHelper::TEACHER1_LOGIN);
        $event = $this->presenter->sisEvents->findOneBy(['sisId' => 'gl1p']);
        Assert::notNull($event);

        $this->client->shouldReceive("get")->with('group-attributes', Mockery::any())
            ->andReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'success' => true,
                'code' => 200,
                'payload' => [
                    self::group('r', null, 'Root', true, []),
                    self::group('c1', 'r', 'Course group', true, [RecodexGroup::ATTR_COURSE_KEY => [$event->getCourse()->getCode()]], 'supervisor'),
                    self::group('t1', 'c1', 'Term group', true, [RecodexGroup::ATTR_TERM_KEY => [$event->getTerm()->getYearTermKey()]]),
                    self::group('g1', 't1', 'Group 1', false, []),
                ]
            ])));

        Assert::exception(function () use ($event) {
            PresenterTestHelper::performPresenterRequest(
                $this->presenter,
                'Groups',
                'POST',
                ['action' => 'bind', 'id' => 'g1', 'eventId' => $event->getId()]
            );
        }, ForbiddenRequestException::class);
    }

    public function testBindGroupFailAlreadyBound()
    {
        PresenterTestHelper::login($this->container, PresenterTestHelper::TEACHER1_LOGIN);
        $event = $this->presenter->sisEvents->findOneBy(['sisId' => 'gl1p']);
        Assert::notNull($event);

        $this->client->shouldReceive("get")->with('group-attributes', Mockery::any())
            ->andReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'success' => true,
                'code' => 200,
                'payload' => [
                    self::group('r', null, 'Root', true, []),
                    self::group('c1', 'r', 'Course group', true, [RecodexGroup::ATTR_COURSE_KEY => [$event->getCourse()->getCode()]], 'admin'),
                    self::group('t1', 'c1', 'Term group', true, [RecodexGroup::ATTR_TERM_KEY => [$event->getTerm()->getYearTermKey()]]),
                    self::group('g1', 't1', 'Group 1', false, [RecodexGroup::ATTR_GROUP_KEY => [$event->getSisId()]]),
                ]
            ])));

        Assert::exception(function () use ($event) {
            PresenterTestHelper::performPresenterRequest(
                $this->presenter,
                'Groups',
                'POST',
                ['action' => 'bind', 'id' => 'g1', 'eventId' => $event->getId()]
            );
        }, BadRequestException::class);
    }

    public function testBindGroupFailOrganizational()
    {
        PresenterTestHelper::login($this->container, PresenterTestHelper::TEACHER1_LOGIN);
        $event = $this->presenter->sisEvents->findOneBy(['sisId' => 'gl1p']);
        Assert::notNull($event);

        $this->client->shouldReceive("get")->with('group-attributes', Mockery::any())
            ->andReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'success' => true,
                'code' => 200,
                'payload' => [
                    self::group('r', null, 'Root', true, []),
                    self::group('c1', 'r', 'Course group', true, [RecodexGroup::ATTR_COURSE_KEY => [$event->getCourse()->getCode()]]),
                    self::group('t1', 'c1', 'Term group', true, [RecodexGroup::ATTR_TERM_KEY => [$event->getTerm()->getYearTermKey()]], 'supervisor'),
                ]
            ])));

        Assert::exception(function () use ($event) {
            PresenterTestHelper::performPresenterRequest(
                $this->presenter,
                'Groups',
                'POST',
                ['action' => 'bind', 'id' => 't1', 'eventId' => $event->getId()]
            );
        }, BadRequestException::class);
    }

    public function testUnbindGroupAdmin()
    {
        PresenterTestHelper::login($this->container, PresenterTestHelper::TEACHER1_LOGIN);
        $event = $this->presenter->sisEvents->findOneBy(['sisId' => 'gl1p']);
        Assert::notNull($event);

        $this->client->shouldReceive("get")->with('group-attributes', Mockery::any())
            ->andReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'success' => true,
                'code' => 200,
                'payload' => [
                    self::group('r', null, 'Root', true, []),
                    self::group('c1', 'r', 'Course group', true, [], 'admin'),
                    self::group('g1', 'c1', 'Group 1', false, [RecodexGroup::ATTR_GROUP_KEY => [$event->getSisId()]]),
                ]
            ])));

        $this->client->shouldReceive("delete")->with('group-attributes/g1', Mockery::any())
            ->andReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'success' => true,
                'code' => 200,
                'payload' => "OK"
            ])));

        $payload = PresenterTestHelper::performPresenterRequest(
            $this->presenter,
            'Groups',
            'POST',
            ['action' => 'unbind', 'id' => 'g1', 'eventId' => $event->getId()]
        );

        Assert::equal('OK', $payload);
    }

    public function testUnbindGroupSupervisor()
    {
        PresenterTestHelper::login($this->container, PresenterTestHelper::TEACHER1_LOGIN);
        $event = $this->presenter->sisEvents->findOneBy(['sisId' => 'gl1p']);
        Assert::notNull($event);

        $this->client->shouldReceive("get")->with('group-attributes', Mockery::any())
            ->andReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'success' => true,
                'code' => 200,
                'payload' => [
                    self::group('r', null, 'Root', true, []),
                    self::group('g1', 'r', 'Group 1', false, [RecodexGroup::ATTR_GROUP_KEY => [$event->getSisId()]], 'supervisor'),
                ]
            ])));

        $this->client->shouldReceive("delete")->with('group-attributes/g1', Mockery::any())
            ->andReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'success' => true,
                'code' => 200,
                'payload' => "OK"
            ])));

        $payload = PresenterTestHelper::performPresenterRequest(
            $this->presenter,
            'Groups',
            'POST',
            ['action' => 'unbind', 'id' => 'g1', 'eventId' => $event->getId()]
        );

        Assert::equal('OK', $payload);
    }

    public function testUnbindGroupFailUnauthorized()
    {
        PresenterTestHelper::login($this->container, PresenterTestHelper::STUDENT1_LOGIN);
        $event = $this->presenter->sisEvents->findOneBy(['sisId' => 'gl1p']);
        Assert::notNull($event);

        Assert::exception(function () use ($event) {
            PresenterTestHelper::performPresenterRequest(
                $this->presenter,
                'Groups',
                'POST',
                ['action' => 'unbind', 'id' => 'g1', 'eventId' => $event->getId()]
            );
        }, ForbiddenRequestException::class);
    }

    public function testUnbindGroupFailUnauthorized2()
    {
        PresenterTestHelper::login($this->container, PresenterTestHelper::TEACHER1_LOGIN);
        $event = $this->presenter->sisEvents->findOneBy(['sisId' => 'gl1p']);
        Assert::notNull($event);

        $this->client->shouldReceive("get")->with('group-attributes', Mockery::any())
            ->andReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'success' => true,
                'code' => 200,
                'payload' => [
                    self::group('r', null, 'Root', true, []),
                    self::group('g1', 'r', 'Group 1', false, [RecodexGroup::ATTR_GROUP_KEY => [$event->getSisId()]], 'student'),
                ]
            ])));

        Assert::exception(function () use ($event) {
            PresenterTestHelper::performPresenterRequest(
                $this->presenter,
                'Groups',
                'POST',
                ['action' => 'unbind', 'id' => 'g1', 'eventId' => $event->getId()]
            );
        }, ForbiddenRequestException::class);
    }

    public function testUnbindGroupFailNotBound()
    {
        PresenterTestHelper::login($this->container, PresenterTestHelper::TEACHER1_LOGIN);
        $event = $this->presenter->sisEvents->findOneBy(['sisId' => 'gl1p']);
        Assert::notNull($event);

        $this->client->shouldReceive("get")->with('group-attributes', Mockery::any())
            ->andReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'success' => true,
                'code' => 200,
                'payload' => [
                    self::group('r', null, 'Root', true, []),
                    self::group('g1', 'r', 'Group 1', false, [], 'admin'),
                ]
            ])));

        Assert::exception(function () use ($event) {
            PresenterTestHelper::performPresenterRequest(
                $this->presenter,
                'Groups',
                'POST',
                ['action' => 'unbind', 'id' => 'g1', 'eventId' => $event->getId()]
            );
        }, BadRequestException::class);
    }

    public function testJoinGroup()
    {
        PresenterTestHelper::login($this->container, PresenterTestHelper::STUDENT1_LOGIN);
        $studentId = $this->users->findOneBy(['email' => PresenterTestHelper::STUDENT1_LOGIN])?->getId();
        $event = $this->presenter->sisEvents->findOneBy(['sisId' => 'gl1p']);
        Assert::notNull($event);

        $this->client->shouldReceive("get")->with('group-attributes', Mockery::any())
            ->andReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'success' => true,
                'code' => 200,
                'payload' => [
                    self::group('r', null, 'Root', true, []),
                    self::group('g1', 'r', 'Group 1', false, [RecodexGroup::ATTR_GROUP_KEY => [$event->getSisId()]], null),
                ]
            ])));

        $this->client->shouldReceive("post")->with("groups/g1/students/$studentId", Mockery::any())
            ->andReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'success' => true,
                'code' => 200,
                'payload' => "OK"
            ])));

        $payload = PresenterTestHelper::performPresenterRequest(
            $this->presenter,
            'Groups',
            'POST',
            ['action' => 'join', 'id' => 'g1']
        );

        Assert::equal('OK', $payload);
    }

    public function testJoinGroupFailNoEvent()
    {
        PresenterTestHelper::login($this->container, PresenterTestHelper::STUDENT1_LOGIN);

        $this->client->shouldReceive("get")->with('group-attributes', Mockery::any())
            ->andReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'success' => true,
                'code' => 200,
                'payload' => [
                    self::group('r', null, 'Root', true, []),
                    self::group('g1', 'r', 'Group 1', false, [], null),
                ]
            ])));

        Assert::exception(function () {
            PresenterTestHelper::performPresenterRequest(
                $this->presenter,
                'Groups',
                'POST',
                ['action' => 'join', 'id' => 'g1']
            );
        }, ForbiddenRequestException::class);
    }

    public function testJoinGroupFailAlreadyMember()
    {
        PresenterTestHelper::login($this->container, PresenterTestHelper::STUDENT1_LOGIN);
        $event = $this->presenter->sisEvents->findOneBy(['sisId' => 'gl1p']);
        Assert::notNull($event);

        $this->client->shouldReceive("get")->with('group-attributes', Mockery::any())
            ->andReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'success' => true,
                'code' => 200,
                'payload' => [
                    self::group('r', null, 'Root', true, []),
                    self::group('g1', 'r', 'Group 1', false, [RecodexGroup::ATTR_GROUP_KEY => [$event->getSisId()]], 'student'),
                ]
            ])));

        Assert::exception(function () {
            PresenterTestHelper::performPresenterRequest(
                $this->presenter,
                'Groups',
                'POST',
                ['action' => 'join', 'id' => 'g1']
            );
        }, BadRequestException::class);
    }

    public function testCreateGroup()
    {
        PresenterTestHelper::login($this->container, PresenterTestHelper::TEACHER1_LOGIN);
        $user = $this->users->findOneBy(['email' => PresenterTestHelper::TEACHER1_LOGIN]);
        Assert::notNull($user);
        $event = $this->presenter->sisEvents->findOneBy(['sisId' => 'gl1p']);
        Assert::notNull($event);

        $this->client->shouldReceive("get")->with('group-attributes', Mockery::any())
            ->andReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'success' => true,
                'code' => 200,
                'payload' => [
                    self::group('r', null, 'Root', true, []),
                    self::group('c1', 'r', 'Course group', true, [RecodexGroup::ATTR_COURSE_KEY => [$event->getCourse()->getCode()]]),
                    self::group('t1', 'c1', 'Term group', true, [RecodexGroup::ATTR_TERM_KEY => [$event->getTerm()->getYearTermKey()]]),
                ]
            ])));

        $this->client->shouldReceive("post")->with('groups', Mockery::on(function ($arg) use ($user, $event) {
            Assert::type('array', $arg);
            Assert::type('array', $arg['json'] ?? null);
            $body = $arg['json'];
            Assert::equal($user->getInstanceId(), $body['instanceId']);
            Assert::equal('t1', $body['parentGroupId']);
            Assert::false($body['publicStats']);
            Assert::true($body['detaining']);
            Assert::false($body['isPublic']);
            Assert::false($body['isOrganizational']);
            Assert::false($body['isExam']);
            Assert::true($body['noAdmin']);
            Assert::count(2, $body['localizedTexts']);
            foreach ($body['localizedTexts'] as $localizedText) {
                Assert::type('array', $localizedText);
                Assert::count(3, $localizedText);
                $locale = $localizedText['locale'] ?? '';
                Assert::contains($locale, ['en', 'cs']);
                Assert::equal($this->namingHelper->getGroupName($event, $locale), $localizedText['name'] ?? null);
                Assert::equal($this->namingHelper->getGroupDescription($event, $locale), $localizedText['description'] ?? null);
            }
            return true;
        }))->andReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'success' => true,
            'code' => 200,
            'payload' => ['id' => 'g1']
        ])));

        $this->client->shouldReceive("post")->with('groups/g1/members/' . $user->getId(), Mockery::any())
            ->andReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'success' => true,
                'code' => 200,
                'payload' => "OK"
            ])));

        $this->client->shouldReceive("post")->with('group-attributes/g1', Mockery::any())
            ->andReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'success' => true,
                'code' => 200,
                'payload' => "OK"
            ])));

        $payload = PresenterTestHelper::performPresenterRequest(
            $this->presenter,
            'Groups',
            'POST',
            ['action' => 'create', 'parentId' => 't1', 'eventId' => $event->getId()]
        );

        Assert::equal("OK", $payload);
    }

    public function testCreateGroupFailWrongParent()
    {
        PresenterTestHelper::login($this->container, PresenterTestHelper::TEACHER1_LOGIN);
        $event = $this->presenter->sisEvents->findOneBy(['sisId' => 'gl1p']);
        Assert::notNull($event);

        $this->client->shouldReceive("get")->with('group-attributes', Mockery::any())
            ->andReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'success' => true,
                'code' => 200,
                'payload' => [
                    self::group('r', null, 'Root', true, []),
                    self::group('c1', 'r', 'Course group', true, []),
                    self::group('t1', 'c1', 'Term group', true, [RecodexGroup::ATTR_TERM_KEY => [$event->getTerm()->getYearTermKey()]]),
                ]
            ])));


        Assert::exception(function () use ($event) {
            PresenterTestHelper::performPresenterRequest(
                $this->presenter,
                'Groups',
                'POST',
                ['action' => 'create', 'parentId' => 't1', 'eventId' => $event->getId()]
            );
        }, ForbiddenRequestException::class);
    }

    public function testCreateGroupFailWrongParent2()
    {
        PresenterTestHelper::login($this->container, PresenterTestHelper::TEACHER1_LOGIN);
        $event = $this->presenter->sisEvents->findOneBy(['sisId' => 'gl1p']);
        Assert::notNull($event);

        $this->client->shouldReceive("get")->with('group-attributes', Mockery::any())
            ->andReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'success' => true,
                'code' => 200,
                'payload' => [
                    self::group('r', null, 'Root', true, []),
                    self::group('c1', 'r', 'Course group', true, [RecodexGroup::ATTR_COURSE_KEY => [$event->getCourse()->getCode()]]),
                    self::group('t1', 'c1', 'Term group', true, []),
                ]
            ])));


        Assert::exception(function () use ($event) {
            PresenterTestHelper::performPresenterRequest(
                $this->presenter,
                'Groups',
                'POST',
                ['action' => 'create', 'parentId' => 't1', 'eventId' => $event->getId()]
            );
        }, ForbiddenRequestException::class);
    }
}

Debugger::$logDirectory = __DIR__ . '/../../log';
(new TestGroupsPresenter())->run();
