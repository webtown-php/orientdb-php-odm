<?php

namespace Doctrine\OrientDB\Schema;

class OClass extends NamedAsset
{
    private static $_propertyDefaults = [
        'readonly'  => false,
        'mandatory' => false,
        'notNull'   => false,
        'min'       => null,
        'max'       => null,
        'collate'   => 'default',
    ];
    /**
     * @var array
     */
    private $_meta;
    /**
     * @var OProperty[]
     */
    private $_properties = [];
    /**
     * @var OIndex[]
     */
    private $_indexes = [];
    /**
     * @var OClass
     */
    private $_superClass;

    public function __construct(array $meta, OClass $superClass = null) {
        parent::__construct($meta['name']);

        if (isset($meta['properties'])) {
            foreach ($meta['properties'] as $p) {
                $prop = new OProperty($this, $p);
                $this->_addProperty($prop);
            }
        }
        if (isset($meta['indexes'])) {
            foreach ($meta['indexes'] as $p) {
                $index = new OIndex($this, $p);
                $this->_addIndex($index);
            }
        }
        $this->_meta       = $meta;
        $this->_superClass = $superClass;
    }

    protected function _addProperty(OProperty $property) {
        $name = $property->getName();
        if (isset($this->_properties[$name])) {
            throw OSchemaException::propertyAlreadyExists($name, $this->getName());
        }

        $this->_properties[$name] = $property;
    }

    protected function _addIndex(OIndex $index) {
        $name = $index->getName();
        if (isset($this->_properties[$name])) {
            throw OSchemaException::indexAlreadyExists($name, $this->getName());
        }

        $this->_indexes[$name] = $index;
    }

    /**
     * @return OClass
     */
    public function getSuperClass() {
        return $this->_superClass;
    }

    public function setSuperClass(OClass $value = null) {
        $this->_superClass = $value;
    }

    /**
     * @return string
     */
    public function getSuperClassName() {
        return $this->_meta['superClass'];
    }

    /**
     * @return bool
     */
    public function isAbstract() {
        return $this->_meta['abstract'];
    }

    /**
     * @param string $name
     * @param string $type
     * @param array  $options
     *
     * @return OProperty
     * @throws OSchemaException
     */
    public function addProperty($name, $type, array $options = []) {
        $meta    = [
            'name' => $name,
            'type' => $type,
        ];
        $options = array_merge(self::$_propertyDefaults, $options, $meta);
        $prop    = new OProperty($this, $options);
        $this->_addProperty($prop);

        return $prop;
    }

    /**
     * @param string $name
     * @param bool   $includeParent
     *
     * @return bool
     */
    public function hasIndex($name, $includeParent = true) {
        $all = $this->getIndexes($includeParent);

        return isset($all[$name]);
    }

    /**
     * @param bool $includeParent
     *
     * @return OIndex[]
     */
    public function getIndexes($includeParent = true) {
        return isset($this->_superClass) && $includeParent
            ? array_merge($this->_superClass->getIndexes(), $this->_indexes)
            : $this->_indexes;
    }

    /**
     * Adds an automatic property index
     *
     * @param string $property
     * @param string $type
     *
     * @return OIndex
     * @throws OSchemaException
     */
    public function addPropertyIndex($property, $type) {
        $name = sprintf("%s.%s", $this->getName(), $property);

        return $this->addIndex($name, [$property], $type);
    }

    /**
     * Add new index
     *
     * @param string   $name
     * @param string[] $properties
     * @param string   $type
     *
     * @return OIndex
     * @throws OSchemaException
     */
    public function addIndex($name, array $properties, $type) {
        $i = $this->_createIndex($name, $properties, $type);
        $this->_addIndex($i);

        return $i;
    }

    /**
     * @param string   $name
     * @param string[] $properties
     * @param string   $type
     *
     * @return OIndex
     * @throws OSchemaException
     */
    private function _createIndex($name, array $properties, $type) {
        $meta = [
            'name'   => $name,
            'type'   => $type,
            'fields' => $properties,
        ];

        foreach ($properties as $p) {
            if (!$this->hasProperty($p)) {
                throw OSchemaException::propertyDoesNotExist($p, $this->getName());
            }
        }

        return new OIndex($this, $meta);
    }

    /**
     * @param string $name
     * @param bool   $includeParent
     *
     * @return bool
     */
    public function hasProperty($name, $includeParent = true) {
        $all = $this->getProperties($includeParent);

        return isset($all[$name]);
    }

    /**
     * @param bool $includeParent true to include parent properties
     *
     * @return OProperty[]
     */
    public function getProperties($includeParent = true) {
        return isset($this->_superClass) && $includeParent
            ? array_merge($this->_superClass->getProperties(), $this->_properties)
            : $this->_properties;
    }

    /**
     * @param OSchemaVisitorInterface $visitor
     */
    public function accept(OSchemaVisitorInterface $visitor) {
        if ($visitor->onVisitingOClass($this)) {
            foreach ($this->getProperties(false) as $p) {
                $p->accept($visitor);
            }

            foreach ($this->getIndexes(false) as $p) {
                $p->accept($visitor);
            }
        }
    }


}