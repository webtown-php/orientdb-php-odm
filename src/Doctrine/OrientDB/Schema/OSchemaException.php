<?php

namespace Doctrine\OrientDB\Schema;

use Doctrine\OrientDB\OrientDBException;

class OSchemaException extends OrientDBException
{
    const CLASS_DOES_NOT_EXIST = 10;
    const CLASS_ALREADY_EXISTS = 20;

    const PROPERTY_ALREADY_EXISTS = 30;
    const PROPERTY_DOES_NOT_EXIST = 40;

    const INDEX_ALREADY_EXISTS = 50;
    const INDEX_DOES_NOT_EXIST = 60;


    /**
     * @param string $className
     *
     * @return $this
     */
    static public function classDoesNotExist($className) {
        return new self(sprintf("there is no class with the name '%s' in the schema", $className), self::CLASS_DOES_NOT_EXIST);
    }

    /**
     * @param string $className
     *
     * @return $this
     */
    public static function classAlreadyExists($className) {
        return new self(sprintf("a class with name '%s' already exists", $className), self::CLASS_ALREADY_EXISTS);
    }

    /**
     * @param string $propertyName
     * @param string $className
     *
     * @return $this
     */
    public static function propertyAlreadyExists($propertyName, $className) {
        return new self(sprintf("a property with name '%s' already exists on class '%s'", $propertyName, $className), self::PROPERTY_ALREADY_EXISTS);
    }

    /**
     * @param string $propertyName
     * @param string $className
     *
     * @return $this
     */
    public static function propertyDoesNotExist($propertyName, $className) {
        return new self(sprintf("a property with name '%s' does not ext on class '%s'", $propertyName, $className), self::PROPERTY_DOES_NOT_EXIST);
    }

    /**
     * @param string $indexName
     * @param string $className
     *
     * @return $this
     */
    public static function indexAlreadyExists($indexName, $className) {
        return new self(sprintf("an index with name '%s' already exists on class '%s'", $indexName, $className), self::INDEX_ALREADY_EXISTS);
    }
}