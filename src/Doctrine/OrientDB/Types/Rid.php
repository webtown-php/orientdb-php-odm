<?php

namespace Doctrine\OrientDB\Types;

class Rid
{
    protected $rid;

    /**
     * Instantiates a new object, injecting the $rid;
     *
     * @param string $rid
     */
    public function __construct($rid) {
        $this->rid = $rid;
    }

    /**
     * Returns the rid associated with the current object.
     *
     * @return string
     */
    public function getValue() {
        return $this->rid;
    }
}
