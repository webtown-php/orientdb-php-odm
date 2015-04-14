<?php

namespace Doctrine\OrientDB\Types;

class DoubleType extends Type
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