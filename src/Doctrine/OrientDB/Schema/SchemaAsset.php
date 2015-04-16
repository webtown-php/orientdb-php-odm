<?php

namespace Doctrine\OrientDB\Schema;

/**
 * Base class for all schema assets
 */
abstract class SchemaAsset
{

    /**
     * @param OSchemaVisitorInterface $visitor
     */
    public abstract function accept(OSchemaVisitorInterface $visitor);
}