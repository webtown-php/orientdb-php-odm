<?php

namespace Doctrine\ODM\OrientDB\Event;

use Doctrine\Common\Persistence\Event\LifecycleEventArgs as BaseLifecycleEventArgs;

/**
 * Lifecycle Events are triggered by the UnitOfWork during lifecycle transitions
 * of documents.
 *
 * @since 1.0
 */
class LifecycleEventArgs extends BaseLifecycleEventArgs
{
    /**
     * Retrieves the associated document.
     *
     * @return object
     */
    public function getDocument() {
        return $this->getObject();
    }

    /**
     * Retrieves the associated DocumentManager.
     *
     * @return \Doctrine\ODM\OrientDB\DocumentManager
     */
    public function getDocumentManager() {
        return $this->getObjectManager();
    }
}
