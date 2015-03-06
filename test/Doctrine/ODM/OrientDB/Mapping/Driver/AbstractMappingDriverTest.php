<?php

namespace test\Doctrine\ODM\OrientDB\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\Driver\AnnotationDriver as AbstractAnnotationDriver;
use Doctrine\ODM\OrientDB\Mapping\ClassMetadata;
use test\PHPUnit\TestCase;

abstract class AbstractMappingDriverTest extends TestCase
{

    /**
     * @return AbstractAnnotationDriver
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
     */
    public function testAssociationMappings($class) {
        $this->assertEquals(5, count($class->associationMappings));
        $this->assertTrue(isset($class->associationMappings['address']));
        $this->assertTrue(isset($class->associationMappings['phonenumbers']));
        $this->assertTrue(isset($class->associationMappings['groups']));
        $this->assertTrue(isset($class->associationMappings['embeddedPhonenumber']));
        $this->assertTrue(isset($class->associationMappings['otherPhonenumbers']));
    }

    /**
     * @depends testLoadMapping
     *
     * @param ClassMetadata $class
     */
    public function testGetAssociationTargetClass($class) {
        $this->assertEquals('test\Doctrine\ODM\OrientDB\Mapping\Driver\Address', $class->getAssociationTargetClass('address'));
        $this->assertEquals('test\Doctrine\ODM\OrientDB\Mapping\Driver\Group', $class->getAssociationTargetClass('groups'));
        $this->assertEquals('test\Doctrine\ODM\OrientDB\Mapping\Driver\Phonenumber', $class->getAssociationTargetClass('phonenumbers'));
        $this->assertEquals('test\Doctrine\ODM\OrientDB\Mapping\Driver\Phonenumber', $class->getAssociationTargetClass('embeddedPhonenumber'));
        $this->assertEquals('test\Doctrine\ODM\OrientDB\Mapping\Driver\Phonenumber', $class->getAssociationTargetClass('otherPhonenumbers'));
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
     * @Property(type="string")
     */
    public $email;

    /**
     * @Property(type="int")
     */
    public $mysqlProfileId;

    /**
     * @Link(targetClass="Address", cascade="remove")
     */
    public $address;

    /**
     * @LinkSet(targetClass="Phonenumber", cascade={"persist"})
     */
    public $phonenumbers;

    /**
     * @LinkList(targetClass="Group", cascade={"all"})
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
     * @Property(type="date")
     */
    public $createdAt;

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