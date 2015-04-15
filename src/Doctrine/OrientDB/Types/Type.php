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

    const EMBEDDED = 'embedded';
    const EMBEDDED_SET = 'embeddedset';
    const EMBEDDED_LIST = 'embeddedlist';
    const EMBEDDED_MAP = 'embeddedmap';

    const LINK = 'link';
    const LINK_SET = 'linkset';
    const LINK_LIST = 'linklist';
    const LINK_MAP = 'linkmap';
    const LINK_BAG = 'linkbag';

    /** Map of already instantiated type objects. One instance per type (flyweight). */
    private static $typeObjects = [];

    /** The map of supported doctrine mapping types. */
    private static $typesMap = [
        self::RID           => 'Doctrine\OrientDB\Types\RidType',
        self::BOOLEAN       => 'Doctrine\OrientDB\Types\BooleanType',

        self::BYTE          => 'Doctrine\OrientDB\Types\ByteType',
        self::SHORT         => 'Doctrine\OrientDB\Types\ShortType',
        self::INTEGER       => 'Doctrine\OrientDB\Types\IntegerType',
        self::LONG          => 'Doctrine\OrientDB\Types\LongType',

        self::FLOAT         => 'Doctrine\OrientDB\Types\FloatType',
        self::DOUBLE        => 'Doctrine\OrientDB\Types\DoubleType',

        self::STRING        => 'Doctrine\OrientDB\Types\StringType',

        self::DATE          => 'Doctrine\OrientDB\Types\DateType',
        self::DATETIME      => 'Doctrine\OrientDB\Types\DateTimeType',

        self::DECIMAL       => 'Doctrine\OrientDB\Types\DecimalType',

        self::EMBEDDED      => 'Doctrine\OrientDB\Types\EmbeddedType',
        self::EMBEDDED_SET  => 'Doctrine\OrientDB\Types\EmbeddedSetType',
        self::EMBEDDED_LIST => 'Doctrine\OrientDB\Types\EmbeddedListType',
        self::EMBEDDED_MAP  => 'Doctrine\OrientDB\Types\EmbeddedMapType',

        self::LINK          => 'Doctrine\OrientDB\Types\LinkType',
        self::LINK_SET      => 'Doctrine\OrientDB\Types\LinkSetType',
        self::LINK_LIST     => 'Doctrine\OrientDB\Types\LinkListType',
        self::LINK_MAP      => 'Doctrine\OrientDB\Types\LinkMapType',
        self::LINK_BAG      => 'Doctrine\OrientDB\Types\LinkBagType',
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

    /**
     * @return string
     */
    public function getName() {
        return $this->__toString();
    }

    public function __toString() {
        $e = explode('\\', get_class($this));

        return str_replace('Type', '', end($e));
    }
}
