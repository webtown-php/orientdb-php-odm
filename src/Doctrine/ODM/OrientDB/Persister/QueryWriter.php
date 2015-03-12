<?php

namespace Doctrine\ODM\OrientDB\Persister;

/**
 * Class QueryWriter
 *
 * @package    Doctrine\ODM
 * @subpackage OrientDB
 * @author     Tamás Millián <tamas.millian@gmail.com>
 */
class QueryWriter
{
    private $queries = [];

    public function getQueries() {
        return $this->queries;
    }

    public function addInsertQuery($identifier, $class, \stdClass $fields, $cluster = null) {
        $query           = "let %s = INSERT INTO %s%s SET %s";
        $cluster         = $cluster ? ' cluster ' . $cluster : '';
        $this->queries[] = sprintf($query, $identifier, $class, $cluster, $this->flattenFields($fields));

        // returned so we can map the rid to the document
        return count($this->queries) - 1;
    }

    public function addUpdateQuery($identifier, \stdClass $fields, $lock = 'DEFAULT') {
        $query           = "UPDATE %s SET %s LOCK %s";
        $this->queries[] = sprintf($query, $identifier, $this->flattenFields($fields), $lock);
    }

    /**
     * @TODO cover vertex/edge deletion
     *
     * @param string $identifier
     * @param string $lock
     */
    public function addDeleteQuery($identifier, $lock = 'DEFAULT') {
        $query           = "DELETE FROM %s LOCK %s";
        $this->queries[] = sprintf($query, $identifier, $lock);
    }

    protected function flattenFields(\stdClass $fields) {
        $parts = '';
        foreach ($fields as $name => $value) {
            $parts [] = sprintf('%s=%s', $name, self::escape($value));
        }

        return implode(',', $parts);
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public static function escape($value) {
        switch (true) {
            case is_string($value):
                return "'$value'";

            case is_null($value):
                return 'null';

            case is_object($value):
            case is_array($value):
                // embedded document, list, map or set
                return json_encode($value);

            default:
                return $value;
        }
    }
}