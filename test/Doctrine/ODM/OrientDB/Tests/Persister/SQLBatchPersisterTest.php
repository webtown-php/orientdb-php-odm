<?php

namespace Doctrine\ODM\OrientDB\Tests\Persister;

use Doctrine\ODM\OrientDB\Persister\SQLBatch\SQLBatchPersister;
use PHPUnit\TestCase;

/**
 * @group functional
 */
class SQLBatchPersisterTest extends TestCase
{
    private static $order = [
        Doc1::class,
        Doc2::class,
        Doc3::class,
        Doc4::class,
    ];

    /**
     * @test
     */
    public function orderByType_returns_single_class() {
        $set = SQLBatchPersister::prepareCommitOrderArray(self::$order);

        $d = new Doc1();
        $docs[spl_object_hash($d)] = $d;
        $actual = SQLBatchPersister::orderByType($docs, $set);
        $this->assertCount(1, $actual);
        $this->assertArrayHasKey(Doc1::class, $actual);

        $i = $actual[Doc1::class];
        $this->assertArrayHasKey(spl_object_hash($d), $i);
    }

    /**
     * @test
     */
    public function orderByType_returns_array_in_correct_order() {
        $set = SQLBatchPersister::prepareCommitOrderArray(self::$order);

        $docs = [
            new Doc1(), new Doc3(), new Doc4(), new Doc2(),
            new Doc2(), new Doc1(), new Doc3(), new Doc4(),
        ];
        foreach ($docs as $k => $d) {
            $docs[spl_object_hash($d)] = $d;
            unset($docs[$k]);
        }

        $actual = SQLBatchPersister::orderByType($docs, $set);
        $this->assertCount(4, $actual);
        $this->assertArrayHasKey(Doc1::class, $actual);
        $this->assertArrayHasKey(Doc2::class, $actual);
        $this->assertArrayHasKey(Doc3::class, $actual);
        $this->assertArrayHasKey(Doc4::class, $actual);

        $keys = array_keys($actual);
        $this->assertEquals(self::$order, $keys);
    }
}

class Doc1 {

}

class Doc2 {

}

class Doc3 {

}

class Doc4 {

}
