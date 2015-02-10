<?php

/**
 * ManagerTest class
 *
 * @package    Doctrine\ODM\OrientDB
 * @subpackage Test
 * @author     Alessandro Nadalin <alessandro.nadalin@gmail.com>
 */

namespace test\Doctrine\ODM\OrientDB\Integration;

use Doctrine\ODM\OrientDB\PersistentCollection;
use test\Integration\Document\Address;
use test\Integration\Document\Post;
use test\Integration\Document\Profile;
use test\PHPUnit\TestCase;
use Doctrine\OrientDB\Query\Query;

/**
 * @group integration
 */
class DocumentManagerTest extends TestCase
{
    public $postId;
    public $addressId;
    public $profileId;

    public function setup()
    {
        $this->postId    = $this->getClassId('Post');
        $this->addressId = $this->getClassId('Address');
        $this->profileId = $this->getClassId('Profile');
    }

    /**
     * @group integration
     */
    public function testGettingARelatedCollectionViaLazyLoading()
    {
        $manager = $this->createDocumentManager([
            'mismatches_tolerance' => true,
        ]);

        $post       = $manager->findByRid($this->postId.':0', '*:0');
        $comments   = $post->getComments();

        $this->assertInstanceOf('test\Integration\Document\Comment', $comments[0]);
    }

    public function testFind() {
        $dm = $this->createDocumentManager();
        /** @var Profile $profile */
        $profile = $dm->find(Profile::class, $this->profileId.':0');
        $phones = $profile->getPhones()->toArray();
    }

    /**
     * @group integration
     */
    public function testExecutionOfASelect()
    {
        $manager = $this->createDocumentManager();

        $query = new Query(array('Address'));
        $addresses = $manager->execute($query);

        $this->assertEquals(40, count($addresses));
        $this->assertInstanceOf('test\Integration\Document\Address', $addresses[0]);
    }

    /**
     * @group integration
     */
    public function testFindingARecordWithAnExecuteReturnsAnArrayHowever()
    {
        $manager = $this->createDocumentManager();

        $query = new Query(array($this->addressId.':0'));
        $addresses = $manager->execute($query);

        $this->assertEquals(1, count($addresses));
        $this->assertInstanceOf('test\Integration\Document\Address', $addresses[0]);
    }

    /**
     * @group integration
     */
    public function testExecutionOfAnUpdate()
    {
        $manager = $this->createDocumentManager();

        $query = new Query(array('Address'));
        $query->update('Address')->set(array('my' => 'yours'))->where('@rid = ?', $this->addressId.':30');
        $result = $manager->execute($query);

        $this->assertInternalType('boolean', $result);
        $this->assertTrue($result);
    }

    /**
     * @group integration
     * @expectedException \Doctrine\OrientDB\Binding\InvalidQueryException
     */
    public function testAnExceptionGetsRaisedWhenExecutingAWrongQuery()
    {
        $manager = $this->createDocumentManager();

        $query = new Query(array('Address'));
        $query->update('Address')->set(array())->where('@rid = ?', '1:10000');

        $manager->execute($query);
    }

    /**
     * @group integration
     */
    public function testFindingARecord()
    {
        $manager = $this->createDocumentManager();
        $address = $manager->findByRid($this->addressId.':0');

        $this->assertInstanceOf('test\Integration\Document\Address', $address);
    }

    /**
     * @group integration
     */
    public function testFindingARecordWithAFetchPlan()
    {
        $manager = $this->createDocumentManager(array(
            'mismatches_tolerance' => true,
        ));

        $post = $manager->findByRid($this->postId.':0', '*:-1');

        $this->assertInstanceOf(PersistentCollection::class, $post->comments);
    }

    /**
     * @group integration
     */
    public function testGettingARelatedRecord()
    {
        $manager = $this->createDocumentManager();
        /** @var Address $address */
        $address = $manager->findByRid($this->addressId.':0');

        $city = $address->getCity();
        $this->assertInstanceOf('test\Integration\Document\Country', $city);
        $this->assertEquals('Rome', $city->name);
    }

    /**
     * @group integration
     */
    public function testGettingARelatedCollection()
    {
        $manager = $this->createDocumentManager(array(
            'mismatches_tolerance' => true,
        ));

        $post       = $manager->findByRid($this->postId.':0');
        $comments   = $post->getComments();

        $this->assertInstanceOf('test\Integration\Document\Comment', $comments[0]);
    }

    /**
     * @group integration
     * @expectedException \Doctrine\ODM\OrientDB\OClassNotFoundException
     */
    public function testLookingForANonMappedTypeRaisesAnException()
    {
        $manager = $this->createDocumentManager([], ['./docs']);

        $manager->findByRid($this->postId.':0');
    }

    /**
     * @group integration
     */
    public function testFindingANonExistingRecord()
    {
        $manager = $this->createDocumentManager();

        $address = $manager->findByRid($this->postId.':2000');

        $this->assertInternalType("null", $address);
    }

    /**
     * @group integration
     */
    public function testExecutingASelectOfASingleRecordReturnsAnArrayWithOneRecord()
    {
        $manager = $this->createDocumentManager();

        $query = new Query(array('Address'));
        $query->where('@rid = ?', $this->addressId.':0');

        $results = $manager->execute($query);

        $this->assertInstanceOf(static::COLLECTION_CLASS, $results);
        $this->assertSame(1, count($results));
    }

    /**
     * @group integration
     */
    public function testExecutionWithNoOutput()
    {
        $manager = $this->createDocumentManager();

        $query = new Query();
        $query->update('Address')->set(array('type' => 'Residence'));

        $results = $manager->execute($query);

        $this->assertInternalType('bool', $results);
        $this->assertTrue($results);
    }

}
