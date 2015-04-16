<?php

namespace Doctrine\OrientDB\Schema\Visitor;

use Doctrine\OrientDB\Schema\OClass;
use Doctrine\OrientDB\Schema\OIndex;
use Doctrine\OrientDB\Schema\OProperty;
use Doctrine\OrientDB\Schema\OSchema;

trait OSchemaVisitorTrait
{
    /**
     * @param OSchema $node
     *
     * @return bool
     */
    public function onVisitingOSchema(OSchema $node) {
        return true;
    }

    /**
     * @param OSchema $node
     *
     * @return void
     */
    public function onVisitedOSchema(OSchema $node) {
    }

    /**
     * @param OClass $node
     *
     * @return bool
     */
    public function onVisitingOClass(OClass $node) {
        return true;
    }

    /**
     * @param OClass $node
     *
     * @return void
     */
    public function onVisitedOClass(OClass $node) {
    }

    /**
     * @param OProperty $node
     *
     * @return void
     */
    public function onVisitedOProperty(OProperty $node) {
    }

    /**
     * @param OIndex $node
     *
     * @return void
     */
    public function onVisitedOIndex(OIndex $node) {
    }
}