<?php

namespace Doctrine\OrientDB\Types;

class LongType extends Type
{
    public function convertToDatabaseValue($value) {
        return $value !== null
            ? max(min(intval($value), PHP_INT_MAX), -PHP_INT_MAX - 1)
            : $value;
    }

    public function convertToPHPValue($value) {
        return $value !== null
            ? max(min(intval($value), PHP_INT_MAX), -PHP_INT_MAX - 1)
            : $value;
    }
}