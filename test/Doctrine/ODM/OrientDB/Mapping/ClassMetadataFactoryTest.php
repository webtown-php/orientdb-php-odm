<?php

namespace test\Doctrine\ODM\OrientDB\Mapping;

use Doctrine\ODM\OrientDB\Event\LoadClassMetadataEventArgs;
use Doctrine\ODM\OrientDB\Events;
use Doctrine\ODM\OrientDB\Mapping\ClassMetadata;
use test\Integration\Document\Address;
use test\PHPUnit\TestCase;

class ClassMetadataFactoryTest extends TestCase
{
    public function testGetMetadataForOClass() {
        $mdf = $this->createDocumentManager()->getMetadataFactory();
        $md  = $mdf->getMetadataForOClass('Address');
        $this->assertInstanceOf(ClassMetadata::class, $md);
    }

    /**
     * @expectedException \Doctrine\ODM\OrientDB\OClassNotFoundException
     */
    public function testGetMetadataForMissingOClassThrowsException() {
        $mdf = $this->createDocumentManager()->getMetadataFactory();
        $mdf->getMetadataForOClass('FOO');
    }

    public function testLoadClassMetadataEvent() {
        $dm = $this->createDocumentManager();
        $dm->getEventManager()->addEventListener(Events::loadClassMetadata, $this);
        $mdf = $dm->getMetadataFactory();
        $md  = $mdf->getMetadataFor(Address::class);
        $this->assertTrue($md->hasField('about'));
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs) {
        /** @var ClassMetadata $classMetadata */
        $classMetadata = $eventArgs->getClassMetadata();
        $field         = array(
            'fieldName' => 'about',
            'type'      => 'string'
        );
        $classMetadata->mapField($field);
    }
}
