<?php

namespace Doctrine\OrientDB\Schema;

interface OSchemaVisitorInterface
{
    /**
     * @param OSchema $node
     *
     * @return bool
     */
    public function onVisitingOSchema(OSchema $node);

    /**
     * @param OSchema $node
     *
     * @return void
     */
    public function onVisitedOSchema(OSchema $node);

    /**
     * @param OClass $node
     *
     * @return bool
     */
    public function onVisitingOClass(OClass $node);

    /**
     * @param OClass $node
     *
     * @return void
     */
    public function onVisitedOClass(OClass $node);

    /**
     * @param OProperty $node
     *
     * @return void
     */
    public function onVisitedOProperty(OProperty $node);

    /**
     * @param OIndex $node
     *
     * @return void
     */
    public function onVisitedOIndex(OIndex $node);
}