<?php

namespace Doctrine\ODM\OrientDB\Persister;

use Doctrine\ODM\OrientDB\Mapping\ClassMetadata;
use Doctrine\ODM\OrientDB\Mapping\ClassMetadataFactory;

class CommitOrderCalculator
{
    const CACHE_KEY = '$$class-dependencies';

    const NOT_VISITED = 1;
    const IN_PROGRESS = 2;
    const VISITED = 3;

    private $nodeStates = [];

    /**
     * @var ClassMetadata[]
     */
    private $classes = []; // The nodes to sort

    /**
     * @var ClassMetadata[]
     */
    private $parentClasses = [];

    /**
     * @var ClassMetadata[]
     */
    private $sorted = [];

    /**
     * Gets the commit order for all the classes available via the provided {@see ClassMetadataFactory}
     *
     * @param ClassMetadataFactory $mdf
     *
     * @return \string[]
     */
    public static function getCommitOrderFromMetadata(ClassMetadataFactory $mdf) {
        $cd  = $mdf->getCacheDriver();
        if ($cd->contains(self::CACHE_KEY)) {
            return $cd->fetch(self::CACHE_KEY);
        }

        $co = new self();

        /** @var ClassMetadata $class */
        foreach ($mdf->getAllMetadata() as $class) {
            $co->classes[$class->name] = $class;

            foreach ($class->associationMappings as $assoc) {
                if ($assoc['isOwningSide']) {
                    continue;
                }

                /** @var ClassMetadata $parentClass */
                $parentClass = $mdf->getMetadataFor($assoc['targetClass']);

                $co->addParent($parentClass, $class);

                // If the target class has mapped subclasses, these share the same dependency.
//                if ( ! $targetClass->subClasses) {
//                    continue;
//                }
//
//                foreach ($targetClass->subClasses as $subClassName) {
//                    $targetSubClass = $this->dm->getClassMetadata($subClassName);
//
//                    if ( ! $calc->hasClass($subClassName)) {
//                        $calc->addClass($targetSubClass);
//
//                        $newNodes[] = $targetSubClass;
//                    }
//
//                    $calc->addDependency($targetSubClass, $class);
//                }
            }
        }

        $ordered = $co->getCommitOrder();
        $cd->save(self::CACHE_KEY, $ordered);

        return $ordered;
    }

    /**
     * Gets a valid commit order for all current nodes.
     *
     * Uses a depth-first search (DFS) to traverse the graph.
     * The desired topological sorting is the reverse post order of these searches.
     *
     * @return string[]
     */
    public function getCommitOrder() {
        // Check whether we need to do anything. 0 or 1 node is easy.
        $nodeCount = count($this->classes);
        if ($nodeCount <= 1) {
            return $nodeCount === 0 ? [] : array_values($this->classes);
        }

        // Init
        foreach ($this->classes as $node) {
            $this->nodeStates[$node->name] = self::NOT_VISITED;
        }

        // Go
        foreach ($this->classes as $node) {
            if ($this->nodeStates[$node->name] == self::NOT_VISITED) {
                $this->visitNode($node);
            }
        }

        $sorted = array_map(function(ClassMetadata $md) {
            return $md->name;
        }, array_reverse($this->sorted));

        $this->sorted = $this->nodeStates = [];

        return $sorted;
    }

    private function visitNode($node) {
        $this->nodeStates[$node->name] = self::IN_PROGRESS;

        if (isset($this->parentClasses[$node->name])) {
            foreach ($this->parentClasses[$node->name] as $relatedNode) {
                if ($this->nodeStates[$relatedNode->name] == self::NOT_VISITED) {
                    $this->visitNode($relatedNode);
                }
            }
        }

        $this->nodeStates[$node->name] = self::VISITED;
        $this->sorted[]                = $node;
    }

    /**
     * @param ClassMetadata $class
     */
    public function addClass(ClassMetadata $class) {
        $this->classes[$class->name] = $class;
    }

    /**
     * Indicates $parent is a dependency for $child
     *
     * @param ClassMetadata $parent
     * @param ClassMetadata $child
     */
    public function addParent(ClassMetadata $parent, ClassMetadata $child) {
        $this->parentClasses[$parent->name][] = $child;
    }
}