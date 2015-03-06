<?php

namespace Doctrine\ODM\OrientDB\Hydrator\Dynamic;

use Doctrine\Common\EventManager;
use Doctrine\ODM\OrientDB\DocumentManager;
use Doctrine\ODM\OrientDB\Hydrator\HydratorFactoryInterface;
use Doctrine\ODM\OrientDB\Hydrator\HydratorInterface;
use Doctrine\ODM\OrientDB\Proxy\Proxy;

class DynamicHydratorFactory implements HydratorFactoryInterface
{
    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var EventManager
     */
    private $evm;

    /**
     * @var HydratorInterface[]
     */
    private $hydrators;

    /**
     * @inheritdoc
     */
    public function setDocumentManager(DocumentManager $dm) {
        $this->dm  = $dm;
        $this->evm = $dm->getEventManager();
    }

    /**
     * @inheritdoc
     */
    public function getHydratorFor($className) {
        if (!isset($this->hydrators[$className])) {
            $metadata                    = $this->dm->getClassMetadata($className);
            $this->hydrators[$className] = new DynamicHydrator($this->dm, $this->dm->getUnitOfWork(), $metadata);
        }

        return $this->hydrators[$className];
    }

    /**
     * @inheritdoc
     */
    public function hydrate($document, $data, array $hints = []) {
        $metadata = $this->dm->getClassMetadata(get_class($document));

        // Invoke preLoad lifecycle events and listeners
//        if ( ! empty($metadata->lifecycleCallbacks[Events::preLoad])) {
//            $args = array(&$data);
//            $metadata->invokeLifecycleCallbacks(Events::preLoad, $document, $args);
//        }
//        if ($this->evm->hasListeners(Events::preLoad)) {
//            $this->evm->dispatchEvent(Events::preLoad, new PreLoadEventArgs($document, $this->dm, $data));
//        }

        $data = $this->getHydratorFor($metadata->name)->hydrate($document, $data, $hints);
        if ($document instanceof Proxy) {
            $document->__isInitialized__ = true;
        }

        // Invoke the postLoad lifecycle callbacks and listeners
//        if ( ! empty($metadata->lifecycleCallbacks[Events::postLoad])) {
//            $metadata->invokeLifecycleCallbacks(Events::postLoad, $document);
//        }
//        if ($this->evm->hasListeners(Events::postLoad)) {
//            $this->evm->dispatchEvent(Events::postLoad, new LifecycleEventArgs($document, $this->dm));
//        }

        return $data;

    }
}