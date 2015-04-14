<?php

namespace Doctrine\OrientDB\Types;

class BooleanType extends Type
{
    static private $TRUE_VALUES = [1, '1', 'true'];

    public function convertToDatabaseValue($value) {
        if (is_bool($value)) {
            return $value;
        }

        if (in_array($value, self::$TRUE_VALUES)) {
            return true;
        }

        return false;
    }

    public function convertToPHPValue($value) {
        if (is_bool($value)) {
            return $value;
        }

        if (in_array($value, self::$TRUE_VALUES)) {
            return true;
        }

        return false;
    }

}