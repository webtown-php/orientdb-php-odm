<?php

namespace Doctrine\ODM\OrientDB\Persister\SQLBatch;

interface Value
{
    /**
     * @return mixed
     */
    public function toValue();
}