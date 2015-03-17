<?php

namespace Doctrine\ODM\OrientDB\Persister\SQLBatch;

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

    /**
     * @var int
     */
    private $inserts = 0;

    public function getQueries() {
        return $this->queries;
    }

    public function addBegin() {
        $this->queries [] = 'begin';
    }

    public function addCommit($retries = 10) {
        $this->queries [] = 'commit retry ' . $retries;
    }

    public function addInsertQuery($var, $class, \stdClass $fields, $cluster = null) {
        $query           = "let %s = INSERT INTO %s%s SET %s RETURN @rid";
        $cluster         = $cluster ? ' cluster ' . $cluster : '';
        $this->queries[] = sprintf($query, $var, $class, $cluster, $this->flattenFields($fields));

        // returned so we can map the rid to the document
        return $this->inserts++;
    }

    /**
     * @param string    $rid
     * @param \stdClass $fields
     * @param int|null  $version
     * @param string    $lock
     */
    public function addUpdateQuery($rid, \stdClass $fields, $var = null, $version = null, $lock = 'DEFAULT') {
        $query = "%sUPDATE %s SET %s %s %s LOCK %s";
        if ($version !== null) {
            $let    = "let $var = ";
            $where  = "WHERE @version = $version";
            $return = "RETURN AFTER @version";
        } else {
            $let = $where = $return = "";
        }
        $this->queries[] = sprintf($query, $let, $rid, $this->flattenFields($fields), $return, $where, $lock);
    }

    public function addCollectionAddQuery($identifier, $fieldName, $value, $lock = 'DEFAULT') {
        $query           = "UPDATE %s ADD %s = [%s] LOCK %s";
        $this->queries[] = sprintf($query, $identifier, $fieldName, $value, $lock);
    }

    public function addCollectionDelQuery($identifier, $fieldName, $value, $lock = 'DEFAULT') {
        $query           = "UPDATE %s REMOVE %s = [%s] LOCK %s";
        $this->queries[] = sprintf($query, $identifier, $fieldName, $value, $lock);
    }

    public function addCollectionMapPutQuery($identifier, $fieldName, $key, $value, $lock = 'DEFAULT') {
        $query           = "UPDATE %s PUT %s = '%s', %s LOCK %s";
        $this->queries[] = sprintf($query, $identifier, $fieldName, $key, $value, $lock);
    }

    public function addCollectionMapDelQuery($identifier, $fieldName, $key, $lock = 'DEFAULT') {
        $query           = "UPDATE %s REMOVE %s = '%s' LOCK %s";
        $this->queries[] = sprintf($query, $identifier, $fieldName, $key, $lock);
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
            case $value instanceof Value:
                return $value->toValue();

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