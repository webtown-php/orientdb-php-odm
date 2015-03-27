<?php

namespace Benchmarks\Doctrine\ODM\OrientDB\Event;

use Athletic\AthleticEvent;
use Doctrine\Common\EventManager;
use Doctrine\ODM\OrientDB\Event\ListenersInvoker;
use Doctrine\ODM\OrientDB\Events;
use Doctrine\ODM\OrientDB\Mapping\ClassMetadata;
use Doctrine\ODM\OrientDB\Mapping\ListenerResolver;

class ListenersInvokerEvent extends AthleticEvent
{
    /**
     * @var ListenersInvoker
     */
    private $li;

    private $md;

    protected function setUp() {
        $ev = new EventManager();
        $ev->addEventListener(Events::prePersist, new \stdClass());

        $lr = new ListenerResolver();
        $this->md = new ClassMetadata('Test');
        $this->md->documentListeners[Events::prePersist] = [];
        $this->md->lifecycleCallbacks[Events::prePersist] = [];


        $this->li = new ListenersInvoker($ev, $lr);
    }

    /**
     * @iterations 10000
     */
    public function getSubscribedSystems_short() {
        $this->li->getSubscribedSystems($this->md, Events::prePersist);
    }

    /**
     * @iterations 10000
     */
    public function getSubscribedSystems2_short() {
        $this->li->getSubscribedSystems2($this->md, Events::prePersist);
    }
}