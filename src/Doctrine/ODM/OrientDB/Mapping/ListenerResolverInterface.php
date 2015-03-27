<?php

namespace Doctrine\ODM\OrientDB\Mapping;

interface ListenerResolverInterface
{
    /**
     * Returns a document listener instance for the given class name.
     *
     * @param   string $className The fully-qualified class name
     *
     * @return  object An document listener
     */
    public function resolve($className);
}