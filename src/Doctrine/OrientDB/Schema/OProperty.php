<?php

namespace Doctrine\OrientDB\Schema;

use Doctrine\OrientDB\Types\Type;

class OProperty extends OClassAsset
{
    /**
     * @var array
     */
    private $_meta;

    /**
     * @var Type
     */
    private $_type;

    /**
     * OProperty constructor.
     *
     * @param OClass $class
     * @param array  $meta
     *
     */
    public function __construct(OClass $class, array $meta) {
        parent::__construct($meta['name'], $class);
        $this->_meta = $meta;
        $this->_type = Type::getType(strtolower($meta['type']));
    }

    /**
     * @return Type
     */
    public function getType() {
        return $this->_type;
    }

    /**
     * @return string
     */
    public function getLinkedClass() {
        return isset($this->_meta['linkedClass']) ? $this->_meta['linkedClass'] : null;
    }

    /**
     * @return string
     */
    public function getLinkedType() {
        return isset($this->_meta['linkedType']) ? $this->_meta['linkedType'] : null;
    }

    /**
     * @return bool
     */
    public function isMandatory() {
        return $this->_meta['mandatory'];
    }

    /**
     * @return bool
     */
    public function isReadOnly() {
        return $this->_meta['readonly'];
    }

    /**
     * @return bool
     */
    public function isNotNull() {
        return $this->_meta['notNull'];
    }

    /**
     * @return int
     */
    public function getMin() {
        return $this->_meta['min'];
    }

    /**
     * @return int
     */
    public function getMax() {
        return $this->_meta['max'];
    }

    /**
     * @return string
     */
    public function getRegExp() {
        return isset($this->_meta['regexp']) ? $this->_meta['regexp'] : null;
    }

    /**
     * @return int
     */
    public function getCollate() {
        return $this->_meta['collate'];
    }

    /**
     * @inheritdoc
     */
    public function accept(OSchemaVisitorInterface $visitor) {
        $visitor->onVisitedOProperty($this);
    }
}