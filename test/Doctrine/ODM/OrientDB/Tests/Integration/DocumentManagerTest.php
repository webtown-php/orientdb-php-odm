<?php

/**
 * ManagerTest class
 *
 * @package    Doctrine\ODM\OrientDB
 * @subpackage Test
 * @author     Alessandro Nadalin <alessandro.nadalin@gmail.com>
 */

namespace Doctrine\ODM\OrientDB\Tests\Integration;

use Doctrine\ODM\OrientDB\Collections\PersistentCollection;
use Doctrine\ODM\OrientDB\Tests\Models\Standard\Address;
use Doctrine\ODM\OrientDB\Tests\Models\Standard\Comment;
use Doctrine\ODM\OrientDB\Tests\Models\Standard\Profile;
use PHPUnit\TestCase;

/**
 * @group integration
 */
class DocumentManagerTest extends TestCase
{
    public $postId;
    public $addressId;
    public $profileId;

    public function setup() {
        $this->postId    = $this->getClassId('Post');
        $this->addressId = $this->getClassId('Address');
        $this->profileId = $this->getClassId('Profile');
    }

    /**
     * @group integration
     */
    public function testGettingARelatedCollectionViaLazyLoading() {
        $manager = $this->createDocumentManager([
            'mismatches_tolerance' => true,
        ]);

        $post     = $manager->findByRid($this->postId . ':0', '*:0');
        $comments = $post->getComments();

        $this->assertInstanceOf(Comment::class, $comments[0]);
    }

    public function testFind() {
        $dm = $this->createDocumentManager();
        /** @var Profile $profile */
        $profile = $dm->find(Profile::class, $this->profileId . ':0');
        $phones  = $profile->getPhones()->toArray();
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
        $manager = $this->createDocumentManager([
            'mismatches_tolerance' => true,
        ]);

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
        $manager = $this->createDocumentManager([
            'mismatches_tolerance' => true,
        ]);

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
