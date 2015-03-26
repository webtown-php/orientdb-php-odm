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
use Doctrine\OrientDB\Query\QueryBuilder;
use Integration\Document\Address;
use Integration\Document\Profile;
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

        $this->assertInstanceOf('Integration\Document\Comment', $comments[0]);
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
    public function testExecutionOfASelect() {
        $manager = $this->createDocumentManager();

        $query     = QueryBuilder::select(array('Address'));
        $addresses = $manager->execute($query);

        $this->assertEquals(40, count($addresses));
        $this->assertInstanceOf('Integration\Document\Address', $addresses[0]);
    }

    /**
     * @group integration
     */
    public function testFindingARecordWithAnExecuteReturnsAnArrayHowever() {
        $manager = $this->createDocumentManager();

        $query     = QueryBuilder::select([$this->addressId . ':0']);
        $addresses = $manager->execute($query);

        $this->assertEquals(1, count($addresses));
        $this->assertInstanceOf('Integration\Document\Address', $addresses[0]);
    }

    /**
     * @group integration
     */
    public function testExecutionOfAnUpdate() {
        $manager = $this->createDocumentManager();

        $query = QueryBuilder::update('Address');
        $query->set(['my' => 'yours'])->where('@rid = ?', $this->addressId . ':30');
        $result = $manager->execute($query);

        $this->assertInternalType('boolean', $result);
        $this->assertTrue($result);
    }

    /**
     * @group integration
     * @expectedException \Doctrine\OrientDB\Binding\InvalidQueryException
     */
    public function testAnExceptionGetsRaisedWhenExecutingAWrongQuery() {
        $manager = $this->createDocumentManager();

        $query = QueryBuilder::update('Address');
        $query->set([])->where('@rid = ?', '1:10000');

        $manager->execute($query);
    }

    /**
     * @group integration
     */
    public function testFindingARecord() {
        $manager = $this->createDocumentManager();
        $address = $manager->findByRid($this->addressId . ':0');

        $this->assertInstanceOf('Integration\Document\Address', $address);
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
        /** @var Address $address */
        $address = $manager->findByRid($this->addressId . ':0');

        $city = $address->getCity();
        $this->assertInstanceOf('Integration\Document\Country', $city);
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

        $this->assertInstanceOf('Integration\Document\Comment', $comments[0]);
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

    /**
     * @group integration
     */
    public function testExecutingASelectOfASingleRecordReturnsAnArrayWithOneRecord() {
        $manager = $this->createDocumentManager();

        $query = QueryBuilder::select(['Address']);
        $query->where('@rid = ?', $this->addressId . ':0');

        $results = $manager->execute($query);

        $this->assertInstanceOf(static::COLLECTION_CLASS, $results);
        $this->assertSame(1, count($results));
    }

    /**
     * @group integration
     */
    public function testExecutionWithNoOutput() {
        $manager = $this->createDocumentManager();

        $query = QueryBuilder::update('Address');
        $query->set(['type' => 'Residence']);

        $results = $manager->execute($query);

        $this->assertInternalType('bool', $results);
        $this->assertTrue($results);
    }

}
