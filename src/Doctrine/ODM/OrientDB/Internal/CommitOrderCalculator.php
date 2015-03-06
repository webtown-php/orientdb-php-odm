<?php

namespace Doctrine\ODM\OrientDB\Internal;

/**
 * The CommitOrderCalculator is used by the UnitOfWork to sort out the
 * correct order in which changes to documents need to be persisted.
 *
 * @since       1.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @author      Roman Borschel <roman@code-factory.org>
 */
class CommitOrderCalculator
{
    const NOT_VISITED = 1;
    const IN_PROGRESS = 2;
    const VISITED = 3;
    
    private $nodeStates = [];
    private $classes = []; // The nodes to sort
    private $relatedClasses = [];
    private $sorted = [];
    
    /**
     * Clears the current graph.
     *
     * @return void
     */
    public function clear()
    {
        $this->classes =
        $this->relatedClasses = array();
    }
    
    /**
     * Gets a valid commit order for all current nodes.
     * 
     * Uses a depth-first search (DFS) to traverse the graph.
     * The desired topological sorting is the reverse postorder of these searches.
     *
     * @return array The list of ordered classes.
     */
    public function getCommitOrder()
    {
        // Check whether we need to do anything. 0 or 1 node is easy.
        $nodeCount = count($this->classes);
        if ($nodeCount === 0) {
            return array();
        }

        if ($nodeCount === 1) {
            return array_values($this->classes);
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

        $sorted = array_reverse($this->sorted);

        $this->sorted = $this->nodeStates = array();

        return $sorted;
    }

    private function visitNode($node)
    {
        $this->nodeStates[$node->name] = self::IN_PROGRESS;

        if (isset($this->relatedClasses[$node->name])) {
            foreach ($this->relatedClasses[$node->name] as $relatedNode) {
                if ($this->nodeStates[$relatedNode->name] == self::NOT_VISITED) {
                    $this->visitNode($relatedNode);
                }
            }
        }

        $this->nodeStates[$node->name] = self::VISITED;
        $this->sorted[] = $node;
    }
    
    public function addDependency($fromClass, $toClass)
    {
        $this->relatedClasses[$fromClass->name][] = $toClass;
    }
    
    public function hasDependency($fromClass, $toClass)
    {
        if ( ! isset($this->relatedClasses[$fromClass->name])) {
            return false;
        }

        return in_array($toClass, $this->relatedClasses[$fromClass->name]);
    }

    public function hasClass($className)
    {
        return isset($this->classes[$className]);
    }
    
    public function addClass($class)
    {
        $this->classes[$class->name] = $class;
    }
}
