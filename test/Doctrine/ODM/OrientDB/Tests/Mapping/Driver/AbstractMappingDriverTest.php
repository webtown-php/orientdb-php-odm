<?php

namespace Doctrine\ODM\OrientDB\Tests\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\ODM\OrientDB\Mapping\ClassMetadata;
use PHPUnit\TestCase;

abstract class AbstractMappingDriverTest extends TestCase
{
    /**
     * @return MappingDriver
     */
    abstract protected function _loadDriver();

    public function createClassMetadata($entityClassName) {
        $mappingDriver = $this->_loadDriver();

        $class = new ClassMetadata($entityClassName);
        $class->initializeReflection(new \Doctrine\Common\Persistence\Mapping\RuntimeReflectionService);
        $mappingDriver->loadMetadataForClass($entityClassName, $class);

        return $class;
    }

    public function testLoadMapping() {
        $entityClassName = User::class;

        return $this->createClassMetadata($entityClassName);
    }

    /**
     * @depends testLoadMapping
     *
     * @param ClassMetadata $class
     *
     * @return ClassMetadata
     */
    public function testOClassName($class) {
        $this->assertEquals('OUser', $class->orientClass);

        return $class;
    }

    /**
     * @depends testLoadMapping
     *
     * @param ClassMetadata $class
     *
     * @return ClassMetadata
     */
    public function testChangeTrackingPolicy($class) {
        $this->assertEquals(ClassMetadata::CHANGETRACKING_NOTIFY, $class->changeTrackingPolicy);
        $this->assertTrue($class->isChangeTrackingNotify());

        return $class;
    }


    /**
     * @depends testLoadMapping
     *
     * @param ClassMetadata $class
     *
     * @return ClassMetadata
     */
    public function testFieldMappings($class) {
        $this->assertEquals(10, count($class->fieldMappings));
        $this->assertTrue(isset($class->fieldMappings['id']));
        $this->assertTrue(isset($class->fieldMappings['name']));
        $this->assertTrue(isset($class->fieldMappings['email']));

        return $class;
    }

    /**
     * @depends testLoadMapping
     *
     * @param ClassMetadata $class
     *
     * @return ClassMetadata
     */
    public function testAssociationMappings($class) {
        $this->assertEquals(5, count($class->associationMappings));
        $this->assertTrue(isset($class->associationMappings['address']));
        $this->assertTrue(isset($class->associationMappings['phonenumbers']));
        $this->assertTrue(isset($class->associationMappings['groups']));
        $this->assertTrue(isset($class->associationMappings['embeddedPhonenumber']));
        $this->assertTrue(isset($class->associationMappings['otherPhonenumbers']));

        return $class;
    }

    /**
     * @depends testAssociationMappings
     *
     * @param ClassMetadata $class
     *
     * @return ClassMetadata
     */
    public function testOwningOneToOneAssociation($class)
    {
        $this->assertTrue(isset($class->associationMappings['address']));
        $this->assertTrue($class->associationMappings['address']['isOwningSide']);
        $this->assertEquals('user', $class->associationMappings['address']['parentProperty']);
        // Check cascading
        $this->assertTrue($class->associationMappings['address']['isCascadeRemove']);
        $this->assertFalse($class->associationMappings['address']['isCascadePersist']);
        $this->assertFalse($class->associationMappings['address']['isCascadeRefresh']);
        $this->assertFalse($class->associationMappings['address']['isCascadeDetach']);
        $this->assertFalse($class->associationMappings['address']['isCascadeMerge']);

        return $class;
    }

    /**
     * @depends testOwningOneToOneAssociation
     *
     * @param ClassMetadata $class
     *
     * @return ClassMetadata
     */
    public function testOwningOneToManyAssociation($class)
    {
        $this->assertTrue(isset($class->associationMappings['phonenumbers']));
        $this->assertTrue($class->associationMappings['phonenumbers']['isOwningSide'], 'isOwningSide');
        $this->assertTrue($class->associationMappings['phonenumbers']['isCascadePersist']);
        $this->assertTrue($class->associationMappings['phonenumbers']['isCascadeRemove']);
        $this->assertFalse($class->associationMappings['phonenumbers']['isCascadeRefresh']);
        $this->assertFalse($class->associationMappings['phonenumbers']['isCascadeDetach']);
        $this->assertFalse($class->associationMappings['phonenumbers']['isCascadeMerge']);
        $this->assertTrue($class->associationMappings['phonenumbers']['orphanRemoval']);

        return $class;
    }

    /**
     * @depends testLoadMapping
     *
     * @param ClassMetadata $class
     */
    public function testGetAssociationTargetClass($class) {
        $this->assertEquals('Doctrine\ODM\OrientDB\Tests\Mapping\Driver\Address', $class->getAssociationTargetClass('address'));
        $this->assertEquals('Doctrine\ODM\OrientDB\Tests\Mapping\Driver\Group', $class->getAssociationTargetClass('groups'));
        $this->assertEquals('Doctrine\ODM\OrientDB\Tests\Mapping\Driver\Phonenumber', $class->getAssociationTargetClass('phonenumbers'));
        $this->assertEquals('Doctrine\ODM\OrientDB\Tests\Mapping\Driver\Phonenumber', $class->getAssociationTargetClass('embeddedPhonenumber'));
        $this->assertEquals('Doctrine\ODM\OrientDB\Tests\Mapping\Driver\Phonenumber', $class->getAssociationTargetClass('otherPhonenumbers'));
    }
}

/**
 * @ChangeTrackingPolicy("NOTIFY")
 * @Document(class="OUser")
 */
class User
{
    /**
     * @RID
     **/
    public $id;

    /**
     * @Property(name="username", type="string")
     */
    public $name;

    /**
     * @Property(type="string", nullable=true)
     */
    public $email;

    /**
     * @Property(type="integer")
     */
    public $mysqlProfileId;

    /**
     * @Property(type="date")
     */
    public $createdAt;

    /**
     * @Link(targetClass="Address", cascade={"remove"}, parentProperty="user")
     */
    public $address;

    /**
     * @LinkSet(targetClass="Phonenumber", parentProperty="user", cascade={"persist"}, orphanRemoval=true)
     */
    public $phonenumbers;

    /**
     * @LinkList(targetClass="Group", cascade={"all"}, parentProperty="user")
     */
    public $groups;

    /**
     * @Embedded(targetClass="Phonenumber", name="embedded_phone_number")
     */
    public $embeddedPhonenumber;

    /**
     * @EmbeddedList(targetClass="Phonenumber")
     */
    public $otherPhonenumbers;

    /**
     * @PrePersist
     */
    public function doStuffOnPrePersist() {
    }

    /**
     * @PrePersist
     */
    public function doOtherStuffOnPrePersistToo() {
    }

    /**
     * @PostPersist
     */
    public function doStuffOnPostPersist() {

    }

    public static function loadMetadata(ClassMetadata $metadata) {

    }
}