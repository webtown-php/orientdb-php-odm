<?php

namespace Doctrine\ODM\OrientDB\Persister\SQLBatch;

class RidList extends \ArrayObject implements Value
{
    function __toString() {
        return $this->toValue();
    }

    /**
     * @return mixed
     */
    public function toValue() {
        return sprintf('[ %s ]', implode(' , ', (array)$this));
    }
}