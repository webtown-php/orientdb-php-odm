<?php

namespace Doctrine\ODM\OrientDB\Types;

class ByteType extends Type
{
    const MIN = -128;
    const MAX = 127;

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