<?php

namespace Doctrine\ODM\OrientDB\Tests\Integration\Mapping\DataType;
use Doctrine\ODM\OrientDB\Tests\Models\Standard\Post;

/**
 * @group integration
 */
class IntegerTest extends AbstractDataTypeTest
{
    public $postId;

    private $rid;

    /**
     * @before
     */
    public function loadBefore() {
        $this->postId = $this->getClassId('Post');
        $b            = $this->dm->getBinding();
        $this->rid    = $b->command('INSERT INTO Post set id=10, title=20')->getData()->result[0]->{'@rid'};
    }

    public function testHydrationOfAnIntegerProperty() {
        /** @var Post $post */
        $post = $this->dm->findByRid($this->rid);
        $this->assertInternalType('integer', $post->id);
    }

    public function testMismatchedAttributesAreConvertedIfTheMapperToleratesMismatches() {
        /** @var Post $post */
        $post = $this->dm->findByRid($this->rid);
        $this->assertInternalType('integer', $post->title);
    }
}
