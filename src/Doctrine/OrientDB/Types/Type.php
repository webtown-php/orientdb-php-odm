<?php

namespace Doctrine\OrientDB\Types;

use Doctrine\ODM\OrientDB\Mapping\MappingException;

abstract class Type
{
    const RID = 'rid';
    const BOOLEAN = 'boolean';

    const BYTE = 'byte';
    const SHORT = 'short';
    const INTEGER = 'integer';
    const LONG = 'long';

    const FLOAT = 'float';
    const DOUBLE = 'double';

    const STRING = 'string';
    const DATE = 'date';
    const DATETIME = 'datetime';
    const DECIMAL = 'decimal';

    const EMBEDDED_SET = 'embedded_set';
    const LINK_SET = 'link_set';
    const LINK_LIST = 'link_list';
    const LINK_MAP = 'link_map';
    const LINK_BAG = 'link_bag';

    /** Map of already instantiated type objects. One instance per type (flyweight). */
    private static $typeObjects = [];

    /** The map of supported doctrine mapping types. */
    private static $typesMap = [
        self::RID      => 'Doctrine\OrientDB\Types\RidType',
        self::BOOLEAN  => 'Doctrine\OrientDB\Types\BooleanType',

        self::BYTE     => 'Doctrine\OrientDB\Types\ByteType',
        self::SHORT    => 'Doctrine\OrientDB\Types\ShortType',
        self::INTEGER  => 'Doctrine\OrientDB\Types\IntegerType',
        self::LONG     => 'Doctrine\OrientDB\Types\LongType',

        self::FLOAT    => 'Doctrine\OrientDB\Types\FloatType',
        self::DOUBLE   => 'Doctrine\OrientDB\Types\DoubleType',

        self::STRING   => 'Doctrine\OrientDB\Types\StringType',

        self::DATE     => 'Doctrine\OrientDB\Types\DateTimeType',
        self::DATETIME => 'Doctrine\OrientDB\Types\DateTimeType',

        self::DECIMAL  => 'Doctrine\OrientDB\Types\DecimalType',
    ];

    /**
     * For parsing fieldTypes property per
     * {@link https://github.com/orientechnologies/orientdb-docs/wiki/OrientDB-REST#json-data-type-handling-and-schema-less-mode docs}
     *
     * @var array
     */
    private static $shortTypesMap = [
        'f' => self::FLOAT,         // float
        'c' => self::DECIMAL,       // decimal
        'l' => self::LONG,          // long
        'd' => self::DOUBLE,        // double
        'b' => self::BYTE,          // byte and binary
        'a' => self::DATE,          // date
        't' => self::DATETIME,      // datetime
        's' => self::SHORT,         // short

        'e' => self::EMBEDDED_SET,  // Set, because arrays and List are serialized as arrays like [3,4,5]
        'x' => self::RID,           // links
        'n' => self::LINK_SET,      // linksets
        'z' => self::LINK_LIST,     // linklist
        'm' => self::LINK_MAP,      // linkmap
        'g' => self::LINK_BAG,      // linkbag
    ];

    final private function __construct() {
    }

    /**
     * Register a new type in the type map.
     *
     * @param string $name  The name of the type.
     * @param string $class The class name.
     */
    final public static function registerType($name, $class) {
        self::$typesMap[$name] = $class;
    }

    /**
     * Get a Type instance.
     *
     * @param string $type The type name.
     *
     * @return \Doctrine\OrientDB\Types\Type $type
     * @throws \InvalidArgumentException
     */
    final public static function getType($type) {
        if (!isset(self::$typesMap[$type])) {
            throw new \InvalidArgumentException(sprintf('Invalid type specified "%s".', $type));
        }
        if (!isset(self::$typeObjects[$type])) {
            $className                = self::$typesMap[$type];
            self::$typeObjects[$type] = new $className;
        }

        return self::$typeObjects[$type];
    }

    /**
     * Adds a custom type to the type map.
     *
     * @static
     *
     * @param string $name      Name of the type. This should correspond to what getName() returns.
     * @param string $className The class name of the custom type.
     *
     * @throws MappingException
     */
    final public static function addType($name, $className) {
        if (isset(self::$typesMap[$name])) {
            throw MappingException::typeExists($name);
        }

        self::$typesMap[$name] = $className;
    }

    /**
     * Checks if exists support for a type.
     *
     * @static
     *
     * @param string $name Name of the type
     *
     * @return boolean TRUE if type is supported; FALSE otherwise
     */
    final public static function hasType($name) {
        return isset(self::$typesMap[$name]);
    }

    /**
     * Overrides an already defined type to use a different implementation.
     *
     * @static
     *
     * @param string $name
     * @param string $className
     *
     * @throws MappingException
     */
    final public static function overrideType($name, $className) {
        if (!isset(self::$typesMap[$name])) {
            throw MappingException::typeNotFound($name);
        }

        self::$typesMap[$name] = $className;
    }

    /**
     * Get the types array map which holds all registered types and the corresponding
     * type class
     *
     * @return array $typesMap
     */
    final public static function getTypesMap() {
        return self::$typesMap;
    }

    final public static function getShortTypesMap() {
        return self::$shortTypesMap;
    }

    /**
     * Converts a value from its PHP representation to its database representation
     * of this type.
     *
     * @param mixed $value The value to convert.
     *
     * @return mixed The database representation of the value.
     */
    public function convertToDatabaseValue($value) {
        return $value;
    }

    /**
     * Converts a value from its database representation to its PHP representation
     * of this type.
     *
     * @param mixed $value The value to convert.
     *
     * @return mixed The PHP representation of the value.
     */
    public function convertToPHPValue($value) {
        return $value;
    }

    public function codeToDatabaseValue() {
        return '$return = $value;';
    }

    public function codeToPHPValue() {
        return '$return = $value;';
    }

    /**
     *
     * @param mixed $left
     * @param mixed $right
     *
     * @return bool
     */
    public function equalsPHP($left, $right) {
        return $left == $right;
    }

    public function __toString() {
        $e = explode('\\', get_class($this));

        return str_replace('Type', '', end($e));
    }
}
