<?php

namespace Doctrine\OrientDB\Schema;

abstract class OClassAsset extends NamedAsset
{
    /**
     * @var OClass
     */
    protected $_class;

    /**
     * @param string $name
     * @param OClass $class
     */
    protected function __construct($name, OClass $class) {
        parent::__construct($name);
        $this->_class = $class;
    }

    /**
     * @return OClass
     */
    public function getClass() {
        return $this->_class;
    }
}