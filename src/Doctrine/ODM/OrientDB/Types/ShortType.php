<?php

namespace Doctrine\ODM\OrientDB\Types;

class ShortType extends Type
{
    const MIN = -32768;
    const MAX = 32767;

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