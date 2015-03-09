<?php

namespace Doctrine\ODM\OrientDB\Persister;

use Doctrine\ODM\OrientDB\UnitOfWork;

interface PersisterInterface
{
    /**
     * Process the unit of work and maps the RIDs back to new documents
     * so it can be used in user land.
     *
     * @param UnitOfWork $uow
     *
     */
    public function process(UnitOfWork $uow);
} 