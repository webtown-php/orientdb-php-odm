<?php


namespace Doctrine\ODM\OrientDB\Tests\Integration;

use Doctrine\ODM\OrientDB\Collections\PersistentCollection;
use Doctrine\ODM\OrientDB\Tests\Models\Standard\Comment;
use Doctrine\ODM\OrientDB\Tests\Models\Standard\Profile;

/**
 * @group integration
 */
class DocumentManagerTest extends AbstractIntegrationTest
{
    public $postId;
    public $addressId;
    public $profileId;

    public function setup() {
        $this->useModelSet('standard');
        parent::setUp();
        $this->postId    = $this->getClassId('Post');
        $this->addressId = $this->getClassId('Address');
        $this->profileId = $this->getClassId('Profile');

        // load some data
        $sql = [
            'let r1 = INSERT INTO Comment SET body="hi"',
            'let r2 = INSERT INTO Comment SET body="hello"',
            'let r3 = INSERT INTO Post SET id=10',
            'UPDATE $r3 ADD comments = $r1, comments = $r2',

            'let r4 = INSERT INTO City SET name="Rome"',
            'let r5 = INSERT INTO Address SET street="Street", city=$r4',

            'return { "n1": $r1.@rid, "n2": $r2 }'
        ];

        $this->dm->getBinding()->sqlBatch($sql, false);
    }

    /**
     * @group integration
     */
    public function testGettingARelatedCollectionViaLazyLoading() {
        $manager = $this->createDocumentManager();

        $post     = $manager->findByRid($this->postId . ':0', '*:0');
        $comments = $post->getComments();

        $this->assertInstanceOf(Comment::class, $comments[0]);
    }

    public function testFind() {
        $dm = $this->createDocumentManager();
        /** @var Profile $profile */
        $profile = $dm->find(Profile::class, $this->profileId . ':0');
    }

    /**
     * @group integration
     */
    public function testFindingARecord() {
        $manager = $this->createDocumentManager();
        $address = $manager->findByRid($this->addressId . ':0');

        $this->assertInstanceOf('Doctrine\ODM\OrientDB\Tests\Models\Standard\Address', $address);
    }

    /**
     * @group integration
     */
    public function testFindingARecordWithAFetchPlan() {
        $manager = $this->createDocumentManager();

        $post = $manager->findByRid($this->postId . ':0', '*:-1');

        $this->assertInstanceOf(PersistentCollection::class, $post->comments);
    }

    /**
     * @group integration
     */
    public function testGettingARelatedRecord() {
        $manager = $this->createDocumentManager();
        /** @var \Doctrine\ODM\OrientDB\Tests\Models\Standard\Address $address */
        $address = $manager->findByRid($this->addressId . ':0');

        $city = $address->getCity();
        $this->assertInstanceOf('Doctrine\ODM\OrientDB\Tests\Models\Standard\Country', $city);
        $this->assertEquals('Rome', $city->name);
    }

    /**
     * @group integration
     */
    public function testGettingARelatedCollection() {
        $manager = $this->createDocumentManager();

        $post     = $manager->findByRid($this->postId . ':0');
        $comments = $post->getComments();

        $this->assertInstanceOf(Comment::class, $comments[0]);
    }

    /**
     * @group integration
     * @expectedException \Doctrine\ODM\OrientDB\OClassNotFoundException
     */
    public function testLookingForANonMappedTypeRaisesAnException() {
        $manager = $this->createDocumentManager([], ['./docs']);

        $manager->findByRid($this->postId . ':0');
    }

    /**
     * @group integration
     */
    public function testFindingANonExistingRecord() {
        $manager = $this->createDocumentManager();

        $address = $manager->findByRid($this->postId . ':2000');

        $this->assertInternalType("null", $address);
    }
}
