<?php

namespace Doctrine\OrientDB\Query;

use Doctrine\OrientDB\Query\Command\Select;
use Doctrine\OrientDB\Query\Command\Update;

final class QueryBuilder
{
    /**
     * @param array $target
     *
     * @return Select
     */
    public static function select(array $target = []) {
        return new Select($target);
    }

    /**
     * @param string $class
     *
     * @return Update
     */
    public static function update($class) {
        return new Update($class);
    }
}