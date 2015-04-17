<?php

namespace Doctrine\OrientDB\Schema;

/**
 * Object representation of an OrientDB schema
 */
class OSchema extends SchemaAsset
{
    private static $_systemClasses = [
        'E',
        'OFunction',
        'OIdentity',
        'ORIDs',
        'ORestricted',
        'ORole',
        'OSchedule',
        'OTriggered',
        'OUser',
        'V',
    ];

    private static $_systemProperties = [
        '@type',
        '@rid',
        '@class',
        '@version',
    ];
    private static $_classDefaults = [
        'abstract' => false,
    ];
    /**
     * @var OClass[]
     */
    private $_classes = [];

    public function __construct(array $classes = []) {
        foreach ($classes as $class) {
            $this->_addClass($class);
        }
    }

    protected function _addClass(OClass $class) {
        if (isset($this->_classes[$class->getName()])) {
            throw OSchemaException::classAlreadyExists($class->getName());
        }

        $this->_classes[$class->getName()] = $class;
    }

    /**
     * returns true if the specified class name is a system class
     *
     * @param string $name
     *
     * @return bool
     */
    public static function isSystemClass($name) {
        return in_array($name, self::$_systemClasses);
    }

    /**
     * @return string[]
     */
    public static function getSystemClasses() {
        return self::$_systemClasses;
    }

    /**
     * returns true if the specified name is a system property
     *
     * @param string $name
     *
     * @return bool
     */
    public static function isSystemProperty($name) {
        return in_array($name, self::$_systemProperties);
    }

    /**
     * @return string[]
     */
    public static function getSystemProperties() {
        return self::$_systemProperties;
    }

    /**
     * @return OClass[]
     */
    public function getClasses() {
        return $this->_classes;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasClass($name) {
        return isset($this->_classes[$name]);
    }

    /**
     * @param string $name
     * @param OClass $superClass
     *
     * @return OClass
     * @throws OSchemaException
     */
    public function createClass($name, OClass $superClass = null) {
        $meta         = self::$_classDefaults;
        $meta['name'] = $name;

        $class = new OClass($meta, $superClass);
        $this->_addClass($class);

        return $class;
    }

    public function createEdgeClass($name) {
        $meta         = self::$_classDefaults;
        $meta['name'] = $name;

        $class = new OClass($meta, $this->getClass('E'));
        $this->_addClass($class);

        return $class;
    }

    /**
     * @param string $name
     *
     * @return OClass
     * @throws OSchemaException
     */
    public function getClass($name) {
        if (!isset($this->_classes[$name])) {
            throw OSchemaException::classDoesNotExist($name);
        }

        return $this->_classes[$name];
    }

    /**
     * @inheritdoc
     */
    public function accept(OSchemaVisitorInterface $visitor) {
        if ($visitor->onVisitingOSchema($this)) {
            foreach ($this->_classes as $class) {
                $class->accept($visitor);
            }
        }
        $visitor->onVisitedOSchema($this);
    }
}