<?php

namespace test\Doctrine\ODM\OrientDB\Hydrator\Dynamic;

use Doctrine\ODM\OrientDB\DocumentManager;
use Doctrine\ODM\OrientDB\Hydrator\Dynamic\DynamicHydrator;
use Doctrine\ODM\OrientDB\Mapping\ClassMetadataFactory;
use Doctrine\ODM\OrientDB\UnitOfWork;
use Doctrine\OrientDB\Binding\BindingResultInterface;
use Doctrine\OrientDB\Binding\HttpBindingInterface;
use Doctrine\OrientDB\Query\Query;
use Prophecy\Argument as Arg;
use test\Doctrine\ODM\OrientDB\Document\Stub;
use test\PHPUnit\TestCase;

/**
 * @group functional
 */
class DynamicHydratorTest extends TestCase
{
    /**
     * @var DocumentManager
     */
    private $manager;
    /**
     * @var ClassMetadataFactory
     */
    private $metadataFactory;

    /**
     * @var UnitOfWork
     */
    private $uow;

    /**
     * @before
     */
    public function before() {
        $binding = $this->prophesize(HttpBindingInterface::class);
        $binding->getDatabaseName()
                ->willReturn("ODM");

        $data = <<<JSON
{
    "classes": [
        {"name":"LinkedContact", "clusters":[1]},
        {"name":"LinkedEmailAddress", "clusters":[2]},
        {"name":"LinkedPhone", "clusters":[3]}
    ]
}
JSON;
        $data = json_decode($data);

        $stub = $this->prophesize(BindingResultInterface::class);
        $stub->getData()
             ->willReturn($data);

        $binding->getDatabase()
                ->willReturn($stub->reveal());

        $rawResult = json_decode('[{
            "@type": "d", "@rid": "#2:1", "@version": 1, "@class": "LinkedEmailAddress",
            "type": "work",
            "email": "syd@gmail.com"
        }]');

        $result = $this->prophesize(BindingResultInterface::class);
        $result->getResult()
               ->willReturn($rawResult);

//        $binding->execute(Arg::that(function (Query $q) {
//            /** @var Select $cmd */
//            $cmd = $q->getCommand();
//            $val = $cmd->getTokenValue('Target');
//
//            return $val[0] === '#2:1';
//        }), Arg::any())
//                ->willReturn($result->reveal());

        $self = $this;
        $binding->execute(Arg::any(), Arg::any())
                ->will(function ($args) use ($self) {
                    /** @var Query $qry */
                    list ($qry) = $args;
                    $cmd = $qry->getCommand();
                    $val = $cmd->getTokenValue('Target');

                    $result = $self->prophesize(BindingResultInterface::class);

                    switch ($val) {
                        case ["#2:1"]:
                            $rawResult = json_decode('[{
                                "@type": "d", "@rid": "#2:1", "@version": 1, "@class": "LinkedEmailAddress",
                                "type": "work",
                                "email": "syd@gmail.com",
                                "contact": "#1:1"
                            }]');
                            break;
                        case ["#3:1", "#3:2"]:
                            $rawResult = json_decode('[{
                                "@type": "d", "@rid": "#3:1", "@version": 1, "@class": "LinkedPhone",
                                "type": "work",
                                "phoneNumber": "4805551920",
                                "primary": true
                            },{
                                "@type": "d", "@rid": "#3:2", "@version": 1, "@class": "LinkedPhone",
                                "type": "home",
                                "phoneNumber": "5552094878",
                                "primary": false
                            }]');
                            break;
                    }

                    $result->getResult()
                           ->willReturn($rawResult);


                    return $result->reveal();

                });

        $this->manager         = $this->createDocumentManagerWithBinding($binding->reveal(), [], ['test/Doctrine/ODM/OrientDB/Document/Stub']);
        $this->uow             = $this->manager->getUnitOfWork();
        $this->metadataFactory = $this->manager->getMetadataFactory();
    }

    /**
     * @test
     */
    public function hydrate_contact_with_no_embedded_data() {
        $md = $this->manager->getClassMetadata(Stub\Simple\Contact::class);
        $dh = new DynamicHydrator($this->manager, $this->manager->getUnitOfWork(), $md);
        $c  = new Stub\Simple\Contact();
        $d  = json_decode(<<<JSON
{
    "@rid": "#1:1",
    "name": "Sydney",
    "height": 122,
    "birthday": "2004-04-09T02:33:00Z",
    "active": true
}
JSON
        );

        $hd = $dh->hydrate($c, $d);

        $expected = [
            '@rid'     => '#1:1',
            'name'     => 'Sydney',
            'height'   => 122,
            'birthday' => new \DateTime("2004-04-09T02:33:00Z"),
            'active'   => true,
        ];

        $this->assertEquals($expected, $hd);
        $this->assertEquals("#1:1", $c->rid);
        $this->assertEquals("Sydney", $c->name);
        $this->assertEquals(122, $c->height);
        $this->assertEquals(new \DateTime("2004-04-09T02:33:00Z"), $c->birthday);
        $this->assertEquals(true, $c->active);
    }

    /**
     * @test
     */
    public function hydrate_contact_with_embedded() {
        $md = $this->manager->getClassMetadata(Stub\Embedded\Contact::class);
        $dh = new DynamicHydrator($this->manager, $this->manager->getUnitOfWork(), $md);
        $c  = new Stub\Embedded\Contact();
        $d  = json_decode(<<<JSON
{
    "@rid": "#1:1",
    "name": "Sydney",
    "email": {
        "@type": "d",
        "@class": "EmbeddedEmailAddress",
        "type": "work",
        "email": "syd@gmail.com"
    }
}
JSON
        );

        $hd = $dh->hydrate($c, $d);
        $this->assertEquals(['@rid', 'name', 'email', 'phones'], array_keys($hd));

        $this->assertEquals('#1:1', $hd['@rid']);
        $this->assertEquals('Sydney', $hd['name']);

        $this->assertNotNull($hd['email']);
        $this->assertNotNull($c->email);
        $this->assertEquals('work', $c->email->type);
        $this->assertEquals('syd@gmail.com', $c->email->email);

        $this->assertEquals(UnitOfWork::STATE_MANAGED, $this->uow->getDocumentState($c->email));
        $pa = $this->uow->getParentAssociation($c->email);

        $this->assertCount(3, $pa);
        // mapping
        $this->assertTrue(is_array($pa[0]));

        // parent contact
        $this->assertEquals($c, $pa[1]);

        // property path
        $this->assertEquals('email', $pa[2]);
    }

    /**
     * @test
     */
    public function hydrate_contact_with_embedded_list() {
        $md = $this->manager->getClassMetadata(Stub\Embedded\Contact::class);
        $dh = new DynamicHydrator($this->manager, $this->manager->getUnitOfWork(), $md);
        $c  = new Stub\Embedded\Contact();
        $d  = json_decode(<<<JSON
{
    "@rid": "#1:1",
    "name": "Sydney",
    "phones": [
        {
            "@type": "d",
            "@class": "EmbeddedPhone",
            "type": "work",
            "phoneNumber": "4805551920",
            "primary": true
        },
        {
            "@type": "d",
            "@class": "EmbeddedPhone",
            "type": "home",
            "phoneNumber": "5552094878",
            "primary": false
        }
    ]
}
JSON
        );

        $hd = $dh->hydrate($c, $d);
        $this->assertEquals(['@rid', 'name', 'phones'], array_keys($hd));

        /** @var Stub\Embedded\Phone[] $phones */
        $phones = $c->phones->toArray();
        $this->assertCount(2, $phones);

        $phone = $phones[0];
        $this->assertEquals('work', $phone->type);
        $this->assertEquals('4805551920', $phone->phoneNumber);
        $this->assertEquals(true, $phone->primary);

        $phone = $phones[1];
        $this->assertEquals('home', $phone->type);
        $this->assertEquals('5552094878', $phone->phoneNumber);
        $this->assertEquals(false, $phone->primary);
    }

    /**
     * @test
     */
    public function hydrate_contact_with_link() {
        $c  = new Stub\Linked\Contact();
        $md = $this->manager->getClassMetadata(Stub\Linked\Contact::class);
        $dh = new DynamicHydrator($this->manager, $this->manager->getUnitOfWork(), $md);

        $d = json_decode(<<<JSON
{
    "@rid": "#1:1",
    "name": "Sydney",
    "email": "#2:1"
}
JSON
        );

        $hd = $dh->hydrate($c, $d);
        $this->uow->registerManaged($c, "#1:1", $hd);
        $this->assertEquals(['@rid', 'name', 'email', 'phones'], array_keys($hd));

        $this->assertEquals('#1:1', $hd['@rid']);
        $this->assertEquals('Sydney', $hd['name']);

        $this->assertNotNull($hd['email']);

        $email = $c->getEmail();
        $this->assertNotNull($email);
        $this->assertEquals('work', $email->type);
        $this->assertEquals('syd@gmail.com', $email->email);

        $this->assertEquals(UnitOfWork::STATE_MANAGED, $this->uow->getDocumentState($email));
        $this->assertTrue($this->uow->isInIdentityMap($email));

        return [$c, $email];
    }

    /**
     * @test
     * @param $args
     * @depends hydrate_contact_with_link
     */
    public function hydrated_contact_on_email_is_same($args) {
        list ($c, $email) = $args;
        $this->assertSame($c, $email->contact);
    }

    /**
     * @test
     */
    public function hydrate_contact_with_link_list() {
        $md = $this->manager->getClassMetadata(Stub\Linked\Contact::class);
        $dh = new DynamicHydrator($this->manager, $this->manager->getUnitOfWork(), $md);
        $c  = new Stub\Linked\Contact();
        $d  = json_decode(<<<JSON
{
    "@rid": "#1:1",
    "name": "Sydney",
    "phones": [ "#3:1", "#3:2" ]
}
JSON
        );

        $hd = $dh->hydrate($c, $d);
        $this->assertEquals(['@rid', 'name', 'phones'], array_keys($hd));

        $this->assertEquals('#1:1', $hd['@rid']);
        $this->assertEquals('Sydney', $hd['name']);

        /** @var Stub\Linked\Phone[] $phones */
        $phones = $c->getPhones()->toArray();
        $this->assertCount(2, $phones);

        $phone = $phones[0];
        $this->assertEquals('work', $phone->type);
        $this->assertEquals(UnitOfWork::STATE_MANAGED, $this->uow->getDocumentState($phone));
        $this->assertTrue($this->uow->isInIdentityMap($phone));

        $phone = $phones[1];
        $this->assertEquals('home', $phone->type);
        $this->assertEquals(UnitOfWork::STATE_MANAGED, $this->uow->getDocumentState($phone));
        $this->assertTrue($this->uow->isInIdentityMap($phone));
    }

}
