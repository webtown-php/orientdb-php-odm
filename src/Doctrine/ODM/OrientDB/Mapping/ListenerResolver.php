<?php

namespace Doctrine\ODM\OrientDB\Mapping;

class ListenerResolver implements ListenerResolverInterface
{
    /**
     * @var object[]
     */
    private $instances = [];

    /**
     * @inheritdoc
     */
    public function resolve($className) {
        $className = trim($className, '\\');
        if (isset($this->instances[$className])) {
            return $this->instances[$className];
        }

        return $this->instances[$className] = new $className();
    }
}