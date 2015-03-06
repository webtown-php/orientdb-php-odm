<?php

namespace Doctrine\ODM\OrientDB\Event;

use Doctrine\Common\Persistence\Event\ManagerEventArgs as BaseManagerEventArgs;

/**
 * Provides event arguments for the flush events.
 *
 * @since 1.0
 */
class ManagerEventArgs extends BaseManagerEventArgs
{
    /**
     * Retrieves the associated DocumentManager.
     *
     * @return \Doctrine\ODM\OrientDB\DocumentManager
     */
    public function getDocumentManager()
    {
        return $this->getObjectManager();
    }
}
