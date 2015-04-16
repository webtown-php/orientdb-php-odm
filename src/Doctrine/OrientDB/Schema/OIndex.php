<?php

namespace Doctrine\OrientDB\Schema;

class OIndex extends OClassAsset
{
    /**
     * @var \stdClass
     */
    private $_meta;

    /**
     * @var bool
     */
    private $_isAutomatic;

    /**
     * OProperty constructor.
     *
     * @param OClass    $class
     * @param \stdClass $meta
     */
    public function __construct(OClass $class, \stdClass $meta) {
        parent::__construct($meta->name, $class);
        $this->_meta = $meta;
        if (strpos($meta->name, '.') !== false) {
            $parts = explode('.', $meta->name);
            if ($parts[0] === $class->getName()) {
                $this->_isAutomatic = true;
            }
        }
    }

    /**
     * @return string
     */
    public function getType() {
        return $this->_meta->type;
    }

    /**
     * @return string[]
     */
    public function getFields() {
        return $this->_meta->fields;
    }

    /**
     * Returns true if this is an explicit property index
     *
     * @return bool
     */
    public function isAutomatic() {
        return $this->_isAutomatic;
    }

    /**
     * @inheritdoc
     */
    public function accept(OSchemaVisitorInterface $visitor) {
        $visitor->onVisitedOIndex($this);
    }
}