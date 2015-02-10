<?php

namespace Doctrine\ODM\OrientDB\Types;

class StringType extends Type
{
    public function convertToDatabaseValue($value) {
        return $value !== null ? strval($value) : null;
    }

    public function convertToPHPValue($value) {
        return $value !== null ? strval($value) : null;
    }

}