<?php

namespace Doctrine\OrientDB\Types;

class DateType extends Type
{
    public function convertToDatabaseValue($value) {
        return $value instanceof \DateTimeInterface
            ? $value->getTimestamp() * 1000
            : null;
    }

    public function convertToPHPValue($value) {
        if ($value === null) {
            return null;
        }

        $value = preg_replace('/(\s\d{2}:\d{2}:\d{2}):(\d{1,6})/', '$1.$2', $value);

        if (is_numeric($value)) {
            $datetime = new \DateTime();
            $datetime->setTimestamp($value);
        } else {
            $datetime = new \DateTime($value);
        }

        return $datetime;
    }

}