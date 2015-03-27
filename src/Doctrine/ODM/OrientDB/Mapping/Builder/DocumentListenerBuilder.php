<?php

namespace Doctrine\ODM\OrientDB\Mapping\Builder;

use Doctrine\ODM\OrientDB\Events;
use Doctrine\ODM\OrientDB\Mapping\ClassMetadata;
use Doctrine\ODM\OrientDB\Mapping\MappingException;

class DocumentListenerBuilder
{
    /**
     * @var array
     */
    static private $events = array(
        Events::preRemove   => true,
        Events::postRemove  => true,
        Events::prePersist  => true,
        Events::postPersist => true,
        Events::preUpdate   => true,
        Events::postUpdate  => true,
        Events::postLoad    => true,
        Events::preFlush    => true
    );

    /**
     * Lookup the document class to find methods that match to event lifecycle names
     *
     * @param ClassMetadata $metadata  The document metadata.
     * @param string        $className The listener class name.
     *
     * @throws MappingException           When the listener class not found.
     */
    static public function bindDocumentListener(ClassMetadata $metadata, $className) {
        $class = $metadata->fullyQualifiedClassName($className);

        if (!class_exists($class)) {
            throw MappingException::documentListenerClassNotFound($class, $className);
        }

        foreach (get_class_methods($class) as $method) {
            if (!isset(self::$events[$method])) {
                continue;
            }

            $metadata->addDocumentListener($method, $class, $method);
        }
    }
}