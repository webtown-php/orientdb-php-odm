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
        $this->assertEquals(15, count($class->fieldMappings));
        $this->assertTrue(isset($class->fieldMappings['id']));
        $this->assertTrue(isset($class->fieldMappings['version']));
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
        $this->assertEquals(9, count($class->associationMappings));
        $this->assertTrue(isset($class->associationMappings['address']));
        $this->assertTrue(isset($class->associationMappings['phonenumbers']));
        $this->assertTrue(isset($class->associationMappings['groups']));
        $this->assertTrue(isset($class->associationMappings['embeddedPhonenumber']));
        $this->assertTrue(isset($class->associationMappings['otherPhonenumbers']));

        return $class;
    }

    /**
     * @depends testAssociationMappings
     * @test
     *
     * @param ClassMetadata $class
     *
     * @return ClassMetadata
     */
    public function relates_to_mappings($class) {
        $this->assertTrue(isset($class->associationMappings['follows']));
        $this->assertTrue(isset($class->associationMappings['followers']));
        $this->assertTrue(isset($class->associationMappings['managers']));
        $this->assertTrue(isset($class->associationMappings['employees']));

        return $class;
    }

    /**
     * @depends relates_to_mappings
     * @test
     *
     * @param ClassMetadata $class
     *
     * @return ClassMetadata
     */
    public function relates_to_direct_mappings($class) {
        $m = $class->associationMappings['follows'];
        $this->assertTrue($m['indirect']);
        $this->assertEquals(ClassMetadata::LINK_BAG, $m['association']);
        $m = $class->associationMappings['followers'];
        $this->assertTrue($m['indirect']);
        $this->assertEquals(ClassMetadata::LINK_BAG, $m['association']);

        return $class;
    }

    /**
     * @depends relates_to_mappings
     * @test
     *
     * @param ClassMetadata $class
     *
     * @return ClassMetadata
     */
    public function relates_to_via_mappings($class) {
        $m = $class->associationMappings['managers'];
        $this->assertFalse($m['indirect']);
        $this->assertEquals(ClassMetadata::LINK_BAG, $m['association']);
        $m = $class->associationMappings['employees'];
        $this->assertFalse($m['indirect']);
        $this->assertEquals(ClassMetadata::LINK_BAG, $m['association']);

        return $class;
    }

    /**
     * @depends testAssociationMappings
     *
     * @param ClassMetadata $class
     *
     * @return ClassMetadata
     */
    public function testOwningOneToOneAssociation($class) {
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
    public function testOwningOneToManyAssociation($class) {
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
 * @Document(oclass="OUser")
 */
class User
{
    /**
     * @RID
     * @var string
     **/
    public $id;

    /**
     * @Version
     * @var int
     */
    public $version;

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
     * @Link(targetDoc="Address", cascade={"remove"}, parentProperty="user")
     */
    public $address;

    /**
     * @LinkSet(targetDoc="Phonenumber", parentProperty="user", cascade={"persist"}, orphanRemoval=true)
     */
    public $phonenumbers;

    /**
     * @LinkList(targetDoc="Group", cascade={"all"}, parentProperty="user")
     */
    public $groups;

    /**
     * @Embedded(targetDoc="Phonenumber", name="embedded_phone_number")
     */
    public $embeddedPhonenumber;

    /**
     * @EmbeddedList(targetDoc="Phonenumber")
     */
    public $otherPhonenumbers;

    /**
     * @RelatedTo(oclass="followed", direction="out")
     * @var User[]
     */
    public $follows;

    /**
     * @RelatedTo(oclass="followed", direction="in")
     * @var User[]
     */
    public $followers;

    /**
     * @RelatedToVia(targetDoc="ReportsTo", oclass="reports_to", direction="in")
     * @var array
     */
    public $managers;

    /**
     * @RelatedToVia(targetDoc="ReportsTo", oclass="reports_to", direction="out")
     * @var array
     */
    public $employees;

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