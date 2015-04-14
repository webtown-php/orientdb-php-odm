<?php

namespace Doctrine\OrientDB\Types;

class DecimalType extends Type
{
    public function convertToDatabaseValue($value) {
        return $value;
    }

    public function convertToPHPValue($value) {
        return $value;
    }

}