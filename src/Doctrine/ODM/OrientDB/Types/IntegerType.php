<?php

namespace Doctrine\ODM\OrientDB\Types;

class IntegerType extends Type
{
    const MIN = -2147483648;
    const MAX = 2147483647;

    public function convertToDatabaseValue($value) {
        return $value !== null
            ? max(min(intval($value), self::MAX), self::MIN)
            : $value;
    }

    public function convertToPHPValue($value) {
        return $value !== null
            ? max(min(intval($value), self::MAX), self::MIN)
            : $value;
    }
}