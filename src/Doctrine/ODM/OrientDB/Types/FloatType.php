<?php

namespace Doctrine\ODM\OrientDB\Types;

class FloatType extends Type
{
    public function convertToDatabaseValue($value) {
        return $value !== null
            ? floatval($value)
            : null;
    }

    public function convertToPHPValue($value) {
        return $value !== null
            ? floatval($value)
            : null;
    }

}