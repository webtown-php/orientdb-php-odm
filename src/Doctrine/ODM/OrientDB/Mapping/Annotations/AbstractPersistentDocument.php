<?php

namespace Doctrine\ODM\OrientDB\Mapping\Annotations;

abstract class AbstractPersistentDocument extends AbstractDocument
{
    /**
     * @var string
     */
    public $repositoryClass;
}