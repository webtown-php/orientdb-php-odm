<?php

namespace Doctrine\OrientDB\Query;

use Doctrine\OrientDB\Query\Command\Select;

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
}