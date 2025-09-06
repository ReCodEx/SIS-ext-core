<?php

$container = require_once __DIR__ . "/../bootstrap.php";

use App\Helpers\RecodexApiHelper;
use App\Presenters\GroupsPresenter;
use App\Exceptions\BadRequestException;
use App\Exceptions\ForbiddenRequestException;
use Doctrine\ORM\EntityManagerInterface;
use Tester\Assert;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;

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

    private $client;

    public function __construct()
    {
        global $container;
        $this->container = $container;
        $this->em = PresenterTestHelper::getEntityManager($container);
        $this->user = $container->getByType(\Nette\Security\User::class);
        $this->client = Mockery::mock(Client::class);

        $recodexHelperName = current($this->container->findByType(RecodexApiHelper::class));
        $this->container->removeService($recodexHelperName);
        $this->container->addService($recodexHelperName, new RecodexApiHelper(
            [
                'extensionId' => 'sis-cuni',
                'apiBase' => 'https://recodex.example/',
            ],
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
        PresenterTestHelper::login($this->container, PresenterTestHelper::STUDENT1_LOGIN);
        $this->client->shouldReceive("get")->andReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode([
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
            'Terms',
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
        $this->client->shouldReceive("get")->andReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode([
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
            'Terms',
            'GET',
            ['action' => 'teacher', 'eventIds' => ['sis2'], 'courseIds' => ['prg1']]
        );

        Assert::count(7, $payload);

        $ids = array_map(fn($group) => $group->id, $payload);
        sort($ids);
        Assert::equal(['g1', 'g2', 'g3', 'p1', 'p2', 'p3', 'r'], $ids);
    }
}

(new TestGroupsPresenter())->run();
