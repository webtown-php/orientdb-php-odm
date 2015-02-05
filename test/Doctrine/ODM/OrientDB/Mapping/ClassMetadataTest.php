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

namespace test\Doctrine\ODM\OrientDB\Mapping;

use Doctrine\ODM\OrientDB\Mapping\Annotations\Property;
use test\PHPUnit\TestCase;
use Doctrine\ODM\OrientDB\Mapping;
use Doctrine\ODM\OrientDB\Mapping\ClassMetadata;
use Doctrine\ODM\OrientDB\Mapping\Annotations as ODM;

/**
* @ODM\Document(class="Mapped")
*/
class Mapped
{
    /**
     * @ODM\Property(name="@rid",type="string")
     */
    protected $rid;

    /**
     * @ODM\Property(name="field",type="string")
     */
    protected $field;

    /**
     * @ODM\Property(name="assoc",type="link")
     */
    protected $assoc;

    /**
     * @ODM\Property(name="multiassoc",type="linkset")
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

    public function setup()
    {
        $this->metadata = new ClassMetadata(Mapped::class);

        $this->metadata->setIdentifier('rid');
        $this->metadata->setFields(array(
            'field' => new Property(array('name' => 'field', 'type' => 'string'))
        ));
        $this->metadata->setAssociations(array(
            'assoc'      => new Property(array('name' => 'assoc', 'type' => 'link')),
            'multiassoc' => new Property(array('name' => 'multiassoc', 'type' => 'linkset'))
        ));
    }

    function testGetName()
    {
        $this->assertEquals(Mapped::class, $this->metadata->getName());
    }

    function testGetIdentifier()
    {
        $this->assertEquals(array('rid'), $this->metadata->getIdentifier());
    }

    function testGetIdentifierValues() {
        $i = new Mapped('#1:1');
        $this->assertEquals('#1:1', $this->metadata->getIdentifierValues($i));
    }

    function testGetReflectionClass()
    {
        $this->assertInstanceOf('\ReflectionClass', $this->metadata->getReflectionClass());
    }

    function testIsIdentifier()
    {
        $this->assertEquals(true, $this->metadata->isIdentifier('@rid'));
        $this->assertEquals(false, $this->metadata->isIdentifier('id'));
    }

    function testHasField()
    {
        $this->assertEquals(true, $this->metadata->hasField('field'));
        $this->assertEquals(false, $this->metadata->hasField('OMNOMNOMNOMN'));
    }

    function testHasAssociation()
    {
        $this->assertEquals(true, $this->metadata->hasAssociation('assoc'));
        $this->assertEquals(false, $this->metadata->hasAssociation('OMNOMNOMNOMN'));
    }

    function testIsSingleValuedAssociation()
    {
        $this->assertEquals(true, $this->metadata->isSingleValuedAssociation('assoc'));
        $this->assertEquals(false, $this->metadata->isSingleValuedAssociation('multiassoc'));
    }

    function testIsCollectionValuedAssociation()
    {
        $this->assertEquals(false, $this->metadata->isCollectionValuedAssociation('assoc'));
        $this->assertEquals(true, $this->metadata->isCollectionValuedAssociation('multiassoc'));
    }

    function testGetFieldNames()
    {
        $this->assertEquals(array('field'), $this->metadata->getFieldNames());
    }

    function testGetAssociationNames()
    {
        $this->assertEquals(array('assoc', 'multiassoc'), $this->metadata->getAssociationNames());
    }

    function testGetTypeOfField()
    {
        $this->assertEquals('string', $this->metadata->getTypeOfField('field'));
    }

    function testGetAssociationTargetClass()
    {
        $this->assertEquals(null, $this->metadata->getAssociationTargetClass('OMNOMNOMNOMN'));
    }
}
