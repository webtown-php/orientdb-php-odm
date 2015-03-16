<?php

namespace Doctrine\ODM\OrientDB\Persister\SQLBatch;

class DocReference implements Value
{
    /**
     * @var string
     */
    private $ref;

    /**
     * @param $ref
     *
     * @return DocReference
     */
    public static function create($ref) {
        $r = new self();
        $r->ref = $ref;
        return $r;
    }

    public function toValue() {
        return $this->ref;
    }

    function __toString() {
        return $this->ref;
    }
}