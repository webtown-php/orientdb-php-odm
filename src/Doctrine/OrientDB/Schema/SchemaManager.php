<?php

namespace Doctrine\OrientDB\Schema;

use Doctrine\OrientDB\Binding\BindingInterface;

class SchemaManager
{
    /**
     * @var BindingInterface
     */
    private $_cn;

    public function __construct(BindingInterface $cn) {
        $this->_cn = $cn;
    }

    /**
     * Creates a new database.
     *
     * @param string $database The name of the database to create.
     * @param string $storage
     * @param string $type
     */
    public function createDatabase($database, $storage = 'memory', $type = 'document') {
        $this->_cn->createDatabase($database, $storage, $type);
    }

    /**
     * listDatabases returns a list of the available databases for this connection
     *
     * @return string[]
     */
    public function listDatabases() {
        return $this->_cn->listDatabases();
    }

    /**
     * listClassNames returns a list of class names for the current database
     *
     * @return string[]
     */
    public function listClassNames() {
        $res = $this->_cn->getDatabaseInfo();

        return array_map(function ($class) {
            return $class['name'];
        }, $res['classes']);
    }

    /**
     * @param string $name
     *
     * @return OClass|null
     */
    public function getClass($name) {
        $classes = $this->listClasses();

        return isset($classes[$name]) ? $classes[$name] : null;
    }

    /**
     * listClasses returns a list of available OrientDB classes for the current database
     *
     * @return OClass[]
     */
    public function listClasses() {
        $res = $this->_cn->getDatabaseInfo();

        /** @var OClass[] $hasSuper */
        $hasSuper = [];
        /** @var OClass[] $classes */
        $classes = [];
        foreach ($res['classes'] as $meta) {
            $classes[$meta['name']] = $c = new OClass($meta);
            if (!empty($meta->superClass)) {
                $hasSuper[] = $c;
            }
        }

        foreach ($hasSuper as $c) {
            $c->setSuperClass($classes[$c->getSuperClassName()]);
        }

        return $classes;
    }
}