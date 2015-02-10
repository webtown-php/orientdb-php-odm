<?php

/**
 * IntegerTest
 *
 * @package    Doctrine\ODM\OrientDB
 * @subpackage Test
 * @author     Alessandro Nadalin <alessandro.nadalin@gmail.com>
 * @author     David Funaro <ing.davidino@gmail.com>
 * @version
 */

namespace test\Doctrine\ODM\OrientDB\Integration\Mapping\DataType;

use test\PHPUnit\TestCase;

/**
 * @group integration
 */
class IntegerTest extends TestCase
{
    public $postId;

    public function setup()
    {
        $this->postId = $this->getClassId('Post');
    }

    public function testHydrationOfAnIntegerProperty()
    {
        $manager = $this->createDocumentManager(array(
            'mismatches_tolerance' => true,
        ));

        $post = $manager->findByRid("#".$this->postId.":0");
        $this->assertInternalType('integer', $post->id);
    }

    public function testMismatchedAttributesAreConvertedIfTheMapperToleratesMismatches()
    {
        $manager = $this->createDocumentManager(array(
            'mismatches_tolerance' => true,
        ));

        $post = $manager->findByRid("#".$this->postId.":0");

        $this->assertInternalType('integer', $post->title);
    }
}
