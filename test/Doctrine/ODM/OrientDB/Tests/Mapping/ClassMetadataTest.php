<?php

/**
 * ClassMetadataTest
 *
 * @package    Doctrine\ODM\OrientDB
 * @subpackage Test
 * @author     Alessandro Nadalin <alessandro.nadalin@gmail.com>
 * @author     David Funaro <ing.davidino@gmail.com>
 * @version
 */

namespace Doctrine\ODM\OrientDB\Tests\Mapping;

use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\ODM\OrientDB\Mapping;
use Doctrine\ODM\OrientDB\Mapping\Annotations as ODM;
use Doctrine\ODM\OrientDB\Mapping\ClassMetadata;
use PHPUnit\TestCase;

/**
 * @Document(oclass="Mapped")
 */
class Mapped
{
    /**
     * @RID
     */
    protected $rid;

    /**
     * @Property(name="field",type="string")
     */
    protected $field;

    /**
     * @Link(targetDoc="test")
     */
    protected $assoc;

    /**
     * @LinkSet(targetDoc="test")
     */
    protected $multiassoc;

    function __construct($rid = null) {
        $this->rid = $rid;
    }
}

class ClassMetadataTest extends TestCase
{
    /**
     * @var ClassMetadata
     */
    private $metadata;

    public function setup() {
        $this->metadata = new ClassMetadata(Mapped::class);
        $this->metadata->initializeReflection(new RuntimeReflectionService());

        $this->metadata->setIdentifier('rid');
        $this->metadata->mapField(['fieldName' => 'field', 'name' => 'field', 'type' => 'string']);
        $this->metadata->mapLink(['fieldName' => 'assoc', 'name' => 'assoc']);
        $this->metadata->mapLinkSet(['fieldName' => 'multiassoc', 'name' => 'multiassoc']);
    }

    function testGetName() {
        $this->assertEquals(Mapped::class, $this->metadata->getName());
    }

    function testGetIdentifier() {
        $this->assertEquals(array('rid'), $this->metadata->getIdentifier());
    }

    function testGetIdentifierValues() {
        $i = new Mapped('#1:1');
        $this->assertEquals('#1:1', $this->metadata->getIdentifierValue($i));
    }

    function testGetReflectionClass() {
        $this->assertInstanceOf('\ReflectionClass', $this->metadata->getReflectionClass());
    }

    function testIsIdentifier() {
        $this->assertEquals(true, $this->metadata->isIdentifier('rid'));
        $this->assertEquals(false, $this->metadata->isIdentifier('id'));
    }

    function testHasField() {
        $this->assertEquals(true, $this->metadata->hasField('field'));
        $this->assertEquals(false, $this->metadata->hasField('OMNOMNOMNOMN'));
    }

    function testHasAssociation() {
        $this->assertEquals(true, $this->metadata->hasAssociation('assoc'));
        $this->assertEquals(false, $this->metadata->hasAssociation('OMNOMNOMNOMN'));
    }

    function testIsSingleValuedAssociation() {
        $this->assertEquals(true, $this->metadata->isSingleValuedAssociation('assoc'));
        $this->assertEquals(false, $this->metadata->isSingleValuedAssociation('multiassoc'));
    }

    function testIsCollectionValuedAssociation() {
        $this->assertEquals(false, $this->metadata->isCollectionValuedAssociation('assoc'));
        $this->assertEquals(true, $this->metadata->isCollectionValuedAssociation('multiassoc'));
    }

    function testGetFieldNames() {
        $this->assertEquals(array('field', 'assoc', 'multiassoc'), $this->metadata->getFieldNames());
    }

    function testGetAssociationNames() {
        $this->assertEquals(array('assoc', 'multiassoc'), $this->metadata->getAssociationNames());
    }

    function testGetTypeOfField() {
        $this->assertEquals('string', $this->metadata->getTypeOfField('field'));
    }

    function testGetAssociationTargetClass() {
        $this->setExpectedException(\InvalidArgumentException::class);
        $this->assertEquals(null, $this->metadata->getAssociationTargetClass('OMNOMNOMNOMN'));
    }
}
