<?php
/**
 * Created by PhpStorm.
 * User: stuartcarnie
 * Date: 2/9/15
 * Time: 8:48 PM
 */

namespace Doctrine\ODM\OrientDB\Types;


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


    /** Map of already instantiated type objects. One instance per type (flyweight). */
    private static $typeObjects = array();

    /** The map of supported doctrine mapping types. */
    private static $typesMap = array(
        self::RID      => 'Doctrine\ODM\OrientDB\Types\RidType',
        self::BOOLEAN  => 'Doctrine\ODM\OrientDB\Types\BooleanType',

        self::BYTE     => 'Doctrine\ODM\OrientDB\Types\ByteType',
        self::SHORT    => 'Doctrine\ODM\OrientDB\Types\ShortType',
        self::INTEGER  => 'Doctrine\ODM\OrientDB\Types\IntegerType',
        self::LONG     => 'Doctrine\ODM\OrientDB\Types\LongType',

        self::FLOAT    => 'Doctrine\ODM\OrientDB\Types\FloatType',
        self::DOUBLE   => 'Doctrine\ODM\OrientDB\Types\DoubleType',

        self::STRING   => 'Doctrine\ODM\OrientDB\Types\StringType',

        self::DATE     => 'Doctrine\ODM\OrientDB\Types\DateTimeType',
        self::DATETIME => 'Doctrine\ODM\OrientDB\Types\DateTimeType',

        self::DECIMAL  => 'Doctrine\ODM\OrientDB\Types\DecimalType',

    );

    /* Prevent instantiation and force use of the factory method. */
    final private function __construct() {
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
     * @return \Doctrine\ODM\OrientDB\Types\Type $type
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

    public function __toString() {
        $e = explode('\\', get_class($this));

        return str_replace('Type', '', end($e));
    }
}
