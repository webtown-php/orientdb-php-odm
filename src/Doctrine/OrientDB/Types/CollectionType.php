<?php

namespace Doctrine\OrientDB\Types;

class CollectionType extends Type
{
    /**
     * @var string
     */
    protected $type;

    protected function __construct($type) {
        $this->type = $type;
    }

    public function convertToDatabaseValue($value) {
        return $value !== null ? array_values($value) : null;
    }

    public function convertToPHPValue($value) {
        return $value !== null ? array_values($value) : null;
    }


}