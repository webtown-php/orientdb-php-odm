<?php

namespace Doctrine\ODM\OrientDB\Tests\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\ODM\OrientDB\Events;
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

    /**
     * @test
     * @return ClassMetadata
     */
    public function can_load_mapping_for_class() {
        $entityClassName = User::class;

        return $this->createClassMetadata($entityClassName);
    }

    /**
     * @depends can_load_mapping_for_class
     * @test
     *
     * @param ClassMetadata $class
     *
     * @return ClassMetadata
     */
    public function orientClass_is_set($class) {
        $this->assertEquals('OUser', $class->orientClass);

        return $class;
    }

    /**
     * @depends can_load_mapping_for_class
     * @test
     *
     * @param ClassMetadata $class
     *
     * @return ClassMetadata
     */
    public function is_document_mapping(ClassMetadata $class) {
        $this->assertTrue($class->isDocument());

        return $class;
    }

    /**
     * @depends can_load_mapping_for_class
     * @test
     *
     * @param ClassMetadata $class
     *
     * @return ClassMetadata
     */
    public function isChangeTrackingPolicy_set_to_NOTIFY($class) {
        $this->assertEquals(ClassMetadata::CHANGETRACKING_NOTIFY, $class->changeTrackingPolicy);
        $this->assertTrue($class->isChangeTrackingNotify());

        return $class;
    }

    #region document listeners

    /**
     * @depends can_load_mapping_for_class
     * @test
     *
     * @param ClassMetadata $md
     */
    public function document_has_listeners(ClassMetadata $md) {
        $this->assertArrayHasKey(Events::prePersist, $md->documentListeners);
        $this->assertArrayHasKey(Events::postPersist, $md->documentListeners);
        $this->assertArrayHasKey(Events::preUpdate, $md->documentListeners);
        $this->assertArrayHasKey(Events::postUpdate, $md->documentListeners);
        $this->assertArrayHasKey(Events::preRemove, $md->documentListeners);
        $this->assertArrayHasKey(Events::postRemove, $md->documentListeners);
        $this->assertArrayHasKey(Events::postLoad, $md->documentListeners);
        $this->assertArrayHasKey(Events::preFlush, $md->documentListeners);

        $this->assertCount(2, $md->documentListeners[Events::prePersist]);
        $this->assertCount(2, $md->documentListeners[Events::postPersist]);
        $this->assertCount(2, $md->documentListeners[Events::preUpdate]);
        $this->assertCount(2, $md->documentListeners[Events::postUpdate]);
        $this->assertCount(2, $md->documentListeners[Events::preRemove]);
        $this->assertCount(2, $md->documentListeners[Events::postRemove]);
        $this->assertCount(2, $md->documentListeners[Events::postLoad]);
        $this->assertCount(2, $md->documentListeners[Events::preFlush]);
    }

    #endregion

    /**
     * @depends can_load_mapping_for_class
     * @test
     *
     * @param ClassMetadata $class
     *
     * @return ClassMetadata
     */
    public function all_fields_are_mapped($class) {
        $this->assertEquals(11, count($class->fieldMappings));
        $this->assertTrue(isset($class->fieldMappings['id']));
        $this->assertTrue(isset($class->fieldMappings['version']));
        $this->assertTrue(isset($class->fieldMappings['name']));
        $this->assertTrue(isset($class->fieldMappings['email']));

        return $class;
    }

    /**
     * @depends can_load_mapping_for_class
     * @test
     *
     * @param ClassMetadata $class
     *
     * @return ClassMetadata
     */
    public function all_associations_are_mapped($class) {
        $this->assertEquals(5, count($class->associationMappings));
        $this->assertTrue(isset($class->associationMappings['address']));
        $this->assertTrue(isset($class->associationMappings['phonenumbers']));
        $this->assertTrue(isset($class->associationMappings['groups']));
        $this->assertTrue(isset($class->associationMappings['embeddedPhonenumber']));
        $this->assertTrue(isset($class->associationMappings['otherPhonenumbers']));

        return $class;
    }

    /**
     * @depends all_associations_are_mapped
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
     * @depends can_load_mapping_for_class
     * @test
     *
     * @param ClassMetadata $class
     */
    public function getAssociationTargetClass_returns_expected_value($class) {
        $this->assertEquals('Doctrine\ODM\OrientDB\Tests\Mapping\Driver\Address', $class->getAssociationTargetClass('address'));
        $this->assertEquals('Doctrine\ODM\OrientDB\Tests\Mapping\Driver\Group', $class->getAssociationTargetClass('groups'));
        $this->assertEquals('Doctrine\ODM\OrientDB\Tests\Mapping\Driver\Phonenumber', $class->getAssociationTargetClass('phonenumbers'));
        $this->assertEquals('Doctrine\ODM\OrientDB\Tests\Mapping\Driver\Phonenumber', $class->getAssociationTargetClass('embeddedPhonenumber'));
        $this->assertEquals('Doctrine\ODM\OrientDB\Tests\Mapping\Driver\Phonenumber', $class->getAssociationTargetClass('otherPhonenumbers'));
    }

    #region vertex mapping

    /**
     * @test
     *
     * @return ClassMetadata
     */
    public function load_vertex_document() {
        $entityClassName = ContactV::class;

        return $this->createClassMetadata($entityClassName);
    }

    /**
     * @depends load_vertex_document
     * @test
     *
     * @param $md
     *
     * @return ClassMetadata
     */
    public function vertex_document_has_correct_oclass(ClassMetadata $md) {
        $this->assertEquals('ContactV', $md->orientClass);

        return $md;
    }

    /**
     * @depends load_vertex_document
     * @test
     *
     * @param ClassMetadata $md
     *
     * @return ClassMetadata
     */
    public function is_vertex_document(ClassMetadata $md) {
        $this->assertTrue($md->isVertex());

        return $md;
    }

    /**
     * @depends is_vertex_document
     * @test
     *
     * @param ClassMetadata $md
     *
     * @return ClassMetadata
     */
    public function vertex_has_correct_field_mappings(ClassMetadata $md) {
        $this->assertCount(6, $md->fieldMappings);
        $this->assertArrayHasKey('rid', $md->fieldMappings);
        $this->assertArrayHasKey('name', $md->fieldMappings);

        return $md;
    }

    /**
     * @depends is_vertex_document
     * @test
     *
     * @param ClassMetadata $md
     *
     * @return ClassMetadata
     */
    public function vertex_has_correct_association_mappings(ClassMetadata $md) {
        $this->assertCount(4, $md->associationMappings);
        $this->assertArrayHasKey('liked', $md->associationMappings);
        $this->assertArrayHasKey('likes', $md->associationMappings);
        $this->assertArrayHasKey('follows', $md->associationMappings);
        $this->assertArrayHasKey('followers', $md->associationMappings);

        return $md;
    }

    /**
     * @depends vertex_has_correct_association_mappings
     * @test
     *
     * @param ClassMetadata $md
     *
     * @return ClassMetadata
     */
    public function vertex_has_correct_RelatedToVia_mappings(ClassMetadata $md) {
        foreach (['liked', 'likes'] as $p) {
            $m = $md->associationMappings[$p];
            $this->assertEquals(LikedE::class, $m['targetDoc']);
            $this->assertEquals('liked', $m['oclass']);
            $this->assertEquals(ClassMetadata::LINK_BAG_EDGE, $m['association']);
            $this->assertFalse($m['indirect']);
            $this->assertTrue($m['isOwningSide']);
            $this->assertTrue($m['orphanRemoval']);
        }

        $this->assertEquals('out', $md->associationMappings['liked']['direction']);
        $this->assertEquals('in', $md->associationMappings['likes']['direction']);

        return $md;
    }

    /**
     * @depends vertex_has_correct_association_mappings
     * @test
     *
     * @param ClassMetadata $md
     *
     * @return ClassMetadata
     */
    public function vertex_has_correct_RelatedTo_mappings(ClassMetadata $md) {
        foreach (['follows', 'followers'] as $p) {
            $m = $md->associationMappings[$p];
            $this->assertArrayNotHasKey('targetDoc', $m);
            $this->assertEquals('follows', $m['oclass']);
            $this->assertEquals(ClassMetadata::LINK_BAG_EDGE, $m['association']);
            $this->assertTrue($m['indirect']);
        }

        $this->assertEquals('out', $md->associationMappings['follows']['direction']);
        $this->assertEquals('in', $md->associationMappings['followers']['direction']);

        return $md;
    }

    #endregion

    #region edge mapping

    /**
     * @test
     *
     * @return ClassMetadata
     */
    public function load_edge_document() {
        $entityClassName = LikedE::class;

        return $this->createClassMetadata($entityClassName);
    }

    /**
     * @depends load_edge_document
     * @test
     *
     * @param $md
     *
     * @return ClassMetadata
     */
    public function edge_document_has_correct_oclass(ClassMetadata $md) {
        $this->assertEquals('LikedE', $md->orientClass);

        return $md;
    }

    /**
     * @depends load_edge_document
     * @test
     *
     * @param ClassMetadata $md
     *
     * @return ClassMetadata
     */
    public function is_edge_document(ClassMetadata $md) {
        $this->assertTrue($md->isEdge());

        return $md;
    }

    /**
     * @depends is_edge_document
     * @test
     *
     * @param ClassMetadata $md
     *
     * @return ClassMetadata
     */
    public function edge_has_correct_field_mappings(ClassMetadata $md) {
        $this->assertCount(4, $md->fieldMappings);
        $this->assertArrayHasKey('rid', $md->fieldMappings);
        $this->assertArrayHasKey('description', $md->fieldMappings);

        return $md;
    }

    /**
     * @depends is_edge_document
     * @test
     *
     * @param ClassMetadata $md
     *
     * @return ClassMetadata
     */
    public function edge_has_correct_association_mappings(ClassMetadata $md) {
        $this->assertCount(2, $md->associationMappings);
        $this->assertArrayHasKey('in', $md->associationMappings);
        $this->assertArrayHasKey('out', $md->associationMappings);

        return $md;
    }

    /**
     * @depends edge_has_correct_association_mappings
     * @test
     *
     * @param ClassMetadata $md
     *
     * @return ClassMetadata
     */
    public function edge_has_correct_In_mapping(ClassMetadata $md) {
        $m = $md->associationMappings['in'];
        $this->assertEquals('in', $m['name']);
        $this->assertEquals(ClassMetadata::LINK, $m['association']);

        return $md;
    }

    /**
     * @depends edge_has_correct_association_mappings
     * @test
     *
     * @param ClassMetadata $md
     *
     * @return ClassMetadata
     */
    public function edge_has_correct_Out_mapping(ClassMetadata $md) {
        $m = $md->associationMappings['out'];
        $this->assertEquals('out', $m['name']);
        $this->assertEquals(ClassMetadata::LINK, $m['association']);

        return $md;
    }

    #endregion
}

/**
 * @DocumentListeners({"ListenerOne", "ListenerTwo"})
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

class ListenerOne
{
    public function postLoad() {
    }

    public function postPersist() {
    }

    public function postRemove() {
    }

    public function postUpdate() {
    }

    public function preFlush() {
    }

    public function prePersist() {
    }

    public function preRemove() {
    }

    public function preUpdate() {
    }
}

class ListenerTwo
{
    /**
     * @PostLoad
     */
    public function afterLoad() {
    }

    /**
     * @PostPersist
     */
    public function afterPersist() {
    }

    /**
     * @PostRemove
     */
    public function afterRemove() {
    }

    /**
     * @PostUpdate
     */
    public function afterUpdate() {
    }

    /**
     * @PreFlush
     */
    public function beforeFlush() {
    }

    /**
     * @PrePersist
     */
    public function beforePersist() {
    }

    /**
     * @PreRemove
     */
    public function beforeRemove() {
    }

    /**
     * @PreUpdate
     */
    public function beforeUpdate() {
    }
}

/**
 * @Vertex(oclass="ContactV")
 */
class ContactV
{
    /**
     * @RID
     */
    public $rid;

    /**
     * @Property(type="string")
     * @var string
     */
    public $name;

    /**
     * @RelatedToVia(targetDoc="LikedE", oclass="liked", direction="out")
     * @var LikedE[]
     */
    public $liked;

    /**
     * @RelatedToVia(targetDoc="LikedE", oclass="liked", direction="in")
     * @var LikedE[]
     */
    public $likes;

    /**
     * @RelatedTo(oclass="follows", direction="out")
     * @var ContactV[]
     */
    public $follows;

    /**
     * @RelatedTo(oclass="follows", direction="in")
     * @var ContactV[]
     */
    public $followers;
}

/**
 * @Relationship(oclass="LikedE")
 */
class LikedE
{
    /**
     * @RID
     * @var string
     */
    public $rid;

    /**
     * @Property
     * @var string
     */
    public $description;

    /**
     * @Out
     * @var
     */
    public $out;

    /**
     * @In
     * @var object
     */
    public $in;
}