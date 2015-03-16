<?php

namespace test\Doctrine\ODM\OrientDB\Persister;

use Doctrine\ODM\OrientDB\Mapping\ClassMetadata;
use Doctrine\ODM\OrientDB\Persister\CommitOrderCalculator;
use test\Doctrine\ODM\OrientDB\Document\Stub\Linked\Contact;
use test\Doctrine\ODM\OrientDB\Document\Stub\Linked\EmailAddress;
use test\Doctrine\ODM\OrientDB\Document\Stub\Linked\Phone;
use test\PHPUnit\TestCase;

/**
 * @group functional
 */
class CommitOrderCalculatorTest extends TestCase
{

    /**
     * @test
     */
    public function verify_commit_ordering_from_metadata() {
        $dm  = $this->createDocumentManager([], ['test/Doctrine/ODM/OrientDB/Document/Stub/Linked']);
        $mdf = $dm->getMetadataFactory();

        $sorted = CommitOrderCalculator::getCommitOrderFromMetadata($mdf);

        $expected = [Contact::class, Phone::class, EmailAddress::class];
        $this->assertSame($expected, $sorted);
    }

    /**
     * @test
     */
    public function verify_commit_ordering() {
        $class1 = new ClassMetadata(__NAMESPACE__ . '\NodeClass1');
        $class2 = new ClassMetadata(__NAMESPACE__ . '\NodeClass2');
        $class3 = new ClassMetadata(__NAMESPACE__ . '\NodeClass3');
        $class4 = new ClassMetadata(__NAMESPACE__ . '\NodeClass4');
        $class5 = new ClassMetadata(__NAMESPACE__ . '\NodeClass5');

        $calc = new CommitOrderCalculator();
        $calc->addClass($class1);
        $calc->addClass($class2);
        $calc->addClass($class3);
        $calc->addClass($class4);
        $calc->addClass($class5);

        $calc->addDependency($class1, $class2);
        $calc->addDependency($class2, $class3);
        $calc->addDependency($class3, $class4);
        $calc->addDependency($class5, $class1);

        $sorted = $calc->getCommitOrder();

        // There is only 1 valid ordering for this constellation
        $expected = array_map(function (ClassMetadata $md) {
            return $md->name;
        }, [$class5, $class1, $class2, $class3, $class4]);
        $this->assertSame($expected, $sorted);
    }
}

class NodeClass1
{
}

class NodeClass2
{
}

class NodeClass3
{
}

class NodeClass4
{
}

class NodeClass5
{
}
