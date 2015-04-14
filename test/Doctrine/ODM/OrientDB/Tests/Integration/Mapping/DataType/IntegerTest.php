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

namespace Doctrine\ODM\OrientDB\Tests\Integration\Mapping\DataType;

use PHPUnit\TestCase;

/**
 * @group integration
 */
class IntegerTest extends TestCase
{
    public $postId;

    public function setup() {
        $this->postId = $this->getClassId('Post');
    }

    public function testHydrationOfAnIntegerProperty() {
        $manager = $this->createDocumentManager();

        $post = $manager->findByRid("#" . $this->postId . ":0");
        $this->assertInternalType('integer', $post->id);
    }

    public function testMismatchedAttributesAreConvertedIfTheMapperToleratesMismatches() {
        $manager = $this->createDocumentManager();

        $post = $manager->findByRid("#" . $this->postId . ":0");
        $this->assertInternalType('integer', $post->title);
    }
}
