<?php

namespace Doctrine\ODM\OrientDB\Tests\Event;

use Doctrine\Common\EventManager;
use Doctrine\ODM\OrientDB\Event\ListenersInvoker;
use Doctrine\ODM\OrientDB\Mapping\ClassMetadata;
use Doctrine\ODM\OrientDB\Mapping\ListenerResolverInterface;
use PHPUnit\TestCase;
use Prophecy\Argument as Arg;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @group functional
 */
class ListenersInvokerTest extends TestCase
{
    /**
     * @test
     * @dataProvider data_getSubscribedSystems
     */
    public function test_getSubscribedSystems($event, $hasListeners, array $doc, array $lc, $expected) {
        $md = new ClassMetadata('Test');
        $md->documentListeners = $doc;
        $md->lifecycleCallbacks = $lc;

        /** @var EventManager|ObjectProphecy $em */
        $em = $this->prophesize(EventManager::class);
        $em->hasListeners(Arg::any())
            ->willReturn($hasListeners);

        $lr = $this->prophesize(ListenerResolverInterface::class);

        $li = new ListenersInvoker($em->reveal(), $lr->reveal());

        $actual = $li->getSubscribedSystems($md, $event);
        $this->assertEquals($expected, $actual);

        $actual = $li->getSubscribedSystems2($md, $event);
        $this->assertEquals($expected, $actual);
    }

    const EVT1 = 'a';
    const EVT2 = 'b';

    public function data_getSubscribedSystems() {
        return [
            [
                self::EVT1,
                false,
                [],
                [],
                ListenersInvoker::INVOKE_NONE
            ],
            [
                self::EVT1,
                true,
                [],
                [],
                ListenersInvoker::INVOKE_MANAGER
            ],
            [
                self::EVT1,
                false,
                [
                    self::EVT1 => [],
                ],
                [],
                ListenersInvoker::INVOKE_LISTENERS
            ],
            [
                self::EVT1,
                false,
                [],
                [
                    self::EVT1 => [],
                ],
                ListenersInvoker::INVOKE_CALLBACKS
            ],

            [
                self::EVT1,
                true,
                [
                    self::EVT1 => [],
                ],
                [],
                ListenersInvoker::INVOKE_MANAGER + ListenersInvoker::INVOKE_LISTENERS
            ],
            [
                self::EVT1,
                true,
                [],
                [
                    self::EVT1 => [],
                ],
                ListenersInvoker::INVOKE_MANAGER + ListenersInvoker::INVOKE_CALLBACKS
            ],
            [
                self::EVT1,
                true,
                [
                    self::EVT1 => [],
                ],
                [
                    self::EVT1 => [],
                ],
                ListenersInvoker::INVOKE_ALL
            ],
            [
                self::EVT2,
                true,
                [
                    self::EVT1 => [],
                ],
                [
                    self::EVT1 => [],
                ],
                ListenersInvoker::INVOKE_MANAGER
            ],
        ];
    }
}
