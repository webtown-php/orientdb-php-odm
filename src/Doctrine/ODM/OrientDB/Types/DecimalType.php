<?php

namespace Doctrine\ODM\OrientDB\Types;

class DecimalType extends Type
{
    public function convertToDatabaseValue($value) {
        return $value;
    }

    public function convertToPHPValue($value) {
        return $value;
    }

}