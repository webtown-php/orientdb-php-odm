<?php

namespace Doctrine\ODM\OrientDB\Event;

use Doctrine\Common\EventArgs;
use Doctrine\Common\EventManager;
use Doctrine\ODM\OrientDB\Mapping\ClassMetadata;
use Doctrine\ODM\OrientDB\Mapping\ListenerResolverInterface;

/**
 * A method invoker based on entity lifecycle.
 *
 * @author  Fabio B. Silva <fabio.bat.silva@gmail.com>
 * @since   2.4
 */
class ListenersInvoker
{
    const INVOKE_NONE = 0x00;
    const INVOKE_LISTENERS = 0x01;
    const INVOKE_CALLBACKS = 0x02;
    const INVOKE_MANAGER = 0x04;

    const BIT_LISTENERS = 0;
    const BIT_CALLBACKS = 1;
    const BIT_MANAGER = 2;

    /**
     * bitmas for all invocations
     */
    const INVOKE_ALL = 0x07;

    /**
     * @var ListenerResolverInterface
     */
    private $resolver;

    /**
     * The EventManager used for dispatching events.
     *
     * @var \Doctrine\Common\EventManager
     */
    private $eventManager;

    /**
     * Initializes a new ListenersInvoker instance.
     *
     * @param EventManager              $em
     * @param ListenerResolverInterface $lr
     */
    public function __construct(EventManager $em, ListenerResolverInterface $lr) {
        $this->eventManager = $em;
        $this->resolver     = $lr;
    }

    /**
     * Get the subscribed event systems
     *
     * @param ClassMetadata $metadata  The entity metadata.
     * @param string        $eventName The entity lifecycle event.
     *
     * @return integer Bitmask of subscribed event systems.
     */
    public function getSubscribedSystems(ClassMetadata $metadata, $eventName) {
        $invoke = self::INVOKE_NONE;

        if (isset($metadata->lifecycleCallbacks[$eventName])) {
            $invoke |= self::INVOKE_CALLBACKS;
        }

        if (isset($metadata->documentListeners[$eventName])) {
            $invoke |= self::INVOKE_LISTENERS;
        }

        if ($this->eventManager->hasListeners($eventName)) {
            $invoke |= self::INVOKE_MANAGER;
        }

        return $invoke;
    }

    public function getSubscribedSystems2(ClassMetadata $metadata, $eventName) {
        return
            (isset($metadata->documentListeners[$eventName]) << self::BIT_LISTENERS)
            | (isset($metadata->lifecycleCallbacks[$eventName]) << self::BIT_CALLBACKS)
            | ($this->eventManager->hasListeners($eventName) << self::BIT_MANAGER);
    }

    /**
     * Dispatches the lifecycle event of the given entity.
     *
     * @param ClassMetadata              $metadata  The entity metadata.
     * @param string                     $eventName The entity lifecycle event.
     * @param object                     $entity    The Entity on which the event occurred.
     * @param \Doctrine\Common\EventArgs $event     The Event args.
     * @param integer                    $invoke    Bitmask to invoke listeners.
     */
    public function invoke(ClassMetadata $metadata, $eventName, $entity, EventArgs $event, $invoke) {
        if ($invoke & self::INVOKE_CALLBACKS) {
            foreach ($metadata->lifecycleCallbacks[$eventName] as $callback) {
                $entity->$callback($event);
            }
        }

        if ($invoke & self::INVOKE_LISTENERS) {
            foreach ($metadata->documentListeners[$eventName] as $listener) {
                $class    = $listener['class'];
                $method   = $listener['method'];
                $instance = $this->resolver->resolve($class);

                $instance->$method($entity, $event);
            }
        }

        if ($invoke & self::INVOKE_MANAGER) {
            $this->eventManager->dispatchEvent($eventName, $event);
        }
    }
}