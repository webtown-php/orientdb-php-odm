<?php

/**
 * ManagerTest class
 *
 * @package    Doctrine\ODM\OrientDB
 * @subpackage Test
 * @author     Alessandro Nadalin <alessandro.nadalin@gmail.com>
 */

namespace Doctrine\ODM\OrientDB\Tests\Integration;

use Doctrine\ODM\OrientDB\PersistentCollection;
use Integration\Document\Post;
use PHPUnit\TestCase;

/**
 * @group integration
 */
class RepositoryTest extends TestCase
{
    public $postId;
    public $addressId;

    public function setup() {
        $this->postId    = $this->getClassId('Post');
        $this->addressId = $this->getClassId('Address');
    }

    /**
     * @param $class
     *
     * @return \Doctrine\ODM\OrientDB\DocumentRepository
     */
    protected function createRepository($class) {
        $manager = $this->createDocumentManager(array(
            'mismatches_tolerance' => true,
        ));

        $repository = $manager->getRepository($class);

        return $repository;
    }

    public function testFindingADocumentOfTheRepo() {
        $class      = 'Integration\Document\Post';
        $repository = $this->createRepository($class);

        $this->assertInstanceOf($class, $repository->find($this->postId . ':0'));
    }

    public function testYouCanSpecifyFetchplansWithTheRepo() {
        $class      = 'Integration\Document\Post';
        $repository = $this->createRepository($class);

        /** @var Post $post */
        $post = $repository->findWithPlan($this->postId . ':0', '*:0');
        $this->assertInstanceOf(PersistentCollection::class, $post->getComments());

        $post     = $repository->findWithPlan($this->postId . ':0', '*:-1');
        $comments = $post->getComments()->toArray();
    }

    /**
     * @expectedException \Doctrine\OrientDB\Exception
     */
    public function testFindingADocumentOfAnotherRepoRaisesAnException() {
        $repository = $this->createRepository('Integration\Document\Post');
        $repository->find($this->addressId . ':0');
    }

    public function testFindingANonExistingDocument() {
        $repository = $this->createRepository('Integration\Document\Post');

        $this->assertNull($repository->find('18:985023989'));
    }

    public function testRetrievingAllTheRepo() {
        $repository = $this->createRepository('Integration\Document\Post');
        $posts      = $repository->findAll();

        $this->assertEquals(7, count($posts));
    }

    public function testRetrievingByCriteria() {
        $repository = $this->createRepository('Integration\Document\Post');

        $posts = $repository->findBy(array('title' => 'aaaa'), array('@rid' => 'DESC'));
        $this->assertCount(0, $posts);

        $posts = $repository->findBy(array(), array('@rid' => 'DESC'));
        $this->assertCount(7, $posts);
        $this->assertTrue($posts[0]->getRid() > $posts[1]->getRid());

        $posts = $repository->findBy(array(), array('@rid' => 'ASC'));
        $this->assertCount(7, $posts);
        $this->assertTrue($posts[0]->getRid() < $posts[1]->getRid());

        $posts = $repository->findBy(array(), array('@rid' => 'ASC'), 1);
        $this->assertCount(1, $posts);
    }

    public function testRetrievingARecordByCriteria() {
        $repository = $this->createRepository('Integration\Document\Post');

        $post = $repository->findOneBy(array('title' => 0), array('@rid' => 'DESC'));
        $this->assertInstanceOf('Integration\Document\Post', $post);
    }
}
