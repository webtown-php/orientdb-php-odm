<?php

namespace Benchmarks\Doctrine\ODM\OrientDB\Mapping;

use Athletic\AthleticEvent;
use Doctrine\ODM\OrientDB\Mapping\ClassMetadata;

class ClassMetadataEvent extends AthleticEvent
{
    /**
     * @var ClassMetadata
     */
    private $md;

    /**
     * @var DummyDocument
     */
    private $doc;


    protected function setUp() {
        $this->md = new ClassMetadata(DummyDocument::class);
        $this->doc = new DummyDocument('val');
    }

    /**
     * @iterations 10000
     */
    public function setDocumentValue() {
        $this->md->setFieldValue($this->doc, 'name', 'Cam');
    }

    /**
     * @iterations 10000
     */
    public function getDocumentValue() {
        $res = $this->md->getFieldValue($this->doc, 'name');
    }
}

class DummyDocument
{
    private $name;

    public function __construct($name) {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getName() {
        return $this->name;
    }
}