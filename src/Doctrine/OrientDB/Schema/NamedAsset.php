<?php

namespace Doctrine\OrientDB\Schema;

/**
 * Base class for all named schema assets
 */
abstract class NamedAsset extends SchemaAsset
{
    /**
     * @var string
     */
    protected $_name;

    protected function __construct($name) {
        $this->_name = $name;
    }

    /**
     * Returns the name of this schema asset
     *
     * @return string
     */
    public function getName() {
        return $this->_name;
    }
}