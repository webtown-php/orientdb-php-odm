<?php

namespace Doctrine\ODM\OrientDB\Persister\SQLBatch;

class RidMap extends \ArrayObject implements Value
{
    function __toString() {
        return $this->toValue();
    }

    public function toValue() {
        $parts = [];
        foreach ($this as $k => $v) {
            $parts [] = sprintf('"%s" : %s', $k, $v);
        }

        return sprintf('{ %s }', implode(', ', $parts));
    }
}