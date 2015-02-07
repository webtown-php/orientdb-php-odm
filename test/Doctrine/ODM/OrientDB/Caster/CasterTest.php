<?php

/**
 * CasterTest
 *
 * @package    Doctrine\ODM\OrientDB
 * @subpackage Test
 * @author     Alessandro Nadalin <alessandro.nadalin@gmail.com>
 * @author     David Funaro <ing.davidino@gmail.com>
 * @version
 */

namespace test\Doctrine\ODM\OrientDB\Caster;

use Doctrine\ODM\OrientDB\Caster\Caster;
use Doctrine\ODM\OrientDB\Collections\ArrayCollection;
use Doctrine\ODM\OrientDB\Mapping;
use Doctrine\ODM\OrientDB\Mapping\Annotations\Property;
use test\PHPUnit\TestCase;

class CasterTest extends TestCase
{
    /**
     * @var \Doctrine\ODM\OrientDB\Hydration\Hydrator
     */
    private $hydrator;

    /**
     * @var Caster
     */
    private $caster;

    public function setup() {
        $manager        = $this->createManager();
        $this->hydrator = $manager->getUnitOfWork()->getHydrator();
        $this->caster   = new Caster($this->hydrator);
    }

    /**
     * @dataProvider getBooleans
     */
    public function testBooleanCasting($expected, $input) {
        $this->assertEquals($expected, $this->caster->setValue($input)->castBoolean());
    }

    public function getBooleans() {
        return [
            [true, true],
            [true, 1],
            [true, 'true'],
            [true, '1'],
            [false, '0'],
            [false, 'false'],
            [false, false],
            [false, 0],
        ];
    }

    /**
     * @dataProvider getForcedBooleans
     */
    public function testForcedBooleanCasting($expected, $input) {
        $this->hydrator->enableMismatchesTolerance(true);
        $this->assertEquals($expected, $this->caster->setValue($input)->castBoolean());
    }

    /**
     * @dataProvider getForcedBooleans
     * @expectedException \Doctrine\ODM\OrientDB\Caster\CastingMismatchException
     */
    public function testForcedBooleanCastingRaisesAnException($expected, $input) {
        $this->caster->setValue($input)->castBoolean();
    }

    public function getForcedBooleans() {
        return [
            [true, 'ciao'],
            [true, 1111],
            [false, ''],
            [false, null],
            [true, ' '],
            [true, 'off'],
            [true, 12.12],
            [true, (float)12.12],
            [true, '12,12'],
            [true, -50],
        ];
    }

    /**
     * @dataProvider getBytes
     */
    public function testBytesCasting($byte) {
        $this->assertEquals($byte, $this->caster->setValue($byte)->castByte());
    }

    public function getBytes() {
        return [
            [0],
            [1],
            [100],
            [127],
            [-127],
            [-128],
            [-1],
        ];
    }

    /**
     * @dataProvider getForcedBytes
     */
    public function testForcedBytesCasting($expected, $byte) {
        $this->hydrator->enableMismatchesTolerance(true);
        $this->assertEquals($expected, $this->caster->setValue($byte)->castByte());
    }

    /**
     * @dataProvider getForcedBytes
     * @expectedException \Doctrine\ODM\OrientDB\Caster\CastingMismatchException
     */
    public function testForcedBytesCastingRaisesAnException($expected, $byte) {
        $this->assertEquals($expected, $this->caster->setValue($byte)->castByte());
    }

    public function getForcedBytes() {
        return [
            [Caster::BYTE_MAX_VALUE, '129'],
            [Caster::BYTE_MIN_VALUE, '-129'],
            [Caster::BYTE_MAX_VALUE, '2000'],
            [Caster::BYTE_MIN_VALUE, '-2000'],
            [Caster::BYTE_MIN_VALUE, (float)-500.12],
            [Caster::BYTE_MAX_VALUE, (float)500.12],
            [Caster::BYTE_MIN_VALUE, '-1500/3'],
            [Caster::BYTE_MAX_VALUE, '1500/3'],
            [127, 'ciao'],
        ];
    }

    /**
     * @dataProvider getLongs
     */
    public function testLongsCasting($long) {
        $this->assertEquals($long, $this->caster->setValue($long)->castLong());
    }

    public function getLongs() {
        return [
            [0],
            [1],
            [100],
            [127],
            [1273825789],
            [-127],
            [-12735355],
            [-128],
            [-1],
        ];
    }

    /**
     * @dataProvider getForcedLongs
     */
    public function testForcedLongsCasting($expected, $long) {
        $this->hydrator->enableMismatchesTolerance(true);
        $this->assertEquals($expected, $this->caster->setValue($long)->castLong());
    }

    /**
     * @dataProvider getForcedLongs
     * @expectedException \Doctrine\ODM\OrientDB\Caster\CastingMismatchException
     */
    public function testForcedLongsCastingRaisesAnException($expected, $long) {
        $this->assertEquals($expected, $this->caster->setValue($long)->castLong());
    }

    public function getForcedLongs() {
        return [
            [Caster::LONG_LIMIT, Caster::LONG_LIMIT + '129'],
            [Caster::LONG_LIMIT, -Caster::LONG_LIMIT - '129'],
            [Caster::LONG_LIMIT, Caster::LONG_LIMIT + '2000'],
            [Caster::LONG_LIMIT, -Caster::LONG_LIMIT - '2000'],
            [Caster::LONG_LIMIT, -Caster::LONG_LIMIT - (float)500.12],
            [Caster::LONG_LIMIT, Caster::LONG_LIMIT + (float)500.12],
            [Caster::LONG_LIMIT, -Caster::LONG_LIMIT - '1500/3'],
            [Caster::LONG_LIMIT, Caster::LONG_LIMIT + '1500/3'],
        ];
    }

    /**
     * @dataProvider getIntegers
     */
    public function testIntegersCasting($expected, $integer) {
        $this->assertEquals($expected, $this->caster->setValue($integer)->castInteger());
    }

    public function getIntegers() {
        return [
            [0, '0'],
            [1, 1],
            [100, '100'],
            [-4, '-4'],
        ];
    }

    /**
     * @dataProvider getForcedIntegers
     */
    public function testForcedIntegerCasting($expected, $integer) {
        $this->hydrator->enableMismatchesTolerance(true);
        $this->assertEquals($expected, $this->caster->setValue($integer)->castInteger());
    }

    /**
     * @dataProvider getForcedIntegers
     * @expectedException \Doctrine\ODM\OrientDB\Caster\CastingMismatchException
     */
    public function testForcedIntegersCastingRaisesAnException($expected, $integer) {
        $this->assertEquals($expected, $this->caster->setValue($integer)->castInteger());
    }

    public function getForcedIntegers() {
        return [
            [0, 'ciao'],
            [0, null],
            [1, new \stdClass()],
        ];
    }

    /**
     * @dataProvider getDecimals
     */
    public function testDecimalCasting($expected, $double) {
        $this->assertEquals($expected, $this->caster->setValue($double)->castDecimal());
    }

    /**
     * @dataProvider getForcedDecimals
     */
    public function testForcedDecimalCasting($expected, $decimal) {
        $this->hydrator->enableMismatchesTolerance(true);
        $this->assertEquals($expected, $this->caster->setValue($decimal)->castDecimal());
    }

    /**
     * @dataProvider getForcedDecimals
     * @expectedException \Doctrine\ODM\OrientDB\Caster\CastingMismatchException
     */
    public function testForcedDecimalCastingRaisesAnException($expected, $decimal) {
        $this->assertEquals($expected, $this->caster->setValue($decimal)->castDecimal());
    }

    public function getDecimals() {
        return [
            [1E-8, "1E-8"], //0.00000001
            [4.9E-324, 4.8E-324],
            [4.9E100, 4.9E100],
            [1.7976931348623157E+308, 1.7976931348623157E+308],

        ];
    }

    public function getForcedDecimals() {
        return [
            [4.9E-324, 'ciao'],
            [4.9E-324, null],
        ];
    }

    /**
     * @dataProvider getDoubles
     */
    public function testDoublesCasting($expected, $double) {
        $this->assertEquals($expected, $this->caster->setValue($double)->castDouble());
    }

    public function getDoubles() {
        return [
            [0.2, '0.2'],
            [11, 11],
            [0, '00.00000000000000'],
            [-4, -4],
            [-4, '-4'],
        ];
    }

    /**
     * @dataProvider getForcedDoubles
     */
    public function testForcedDoublesCasting($expected, $double) {
        $this->hydrator->enableMismatchesTolerance(true);
        $this->assertEquals($expected, $this->caster->setValue($double)->castDouble());
    }

    /**
     * @dataProvider getForcedDoubles
     * @expectedException \Doctrine\ODM\OrientDB\Caster\CastingMismatchException
     */
    public function testForcedDoublesCastingRaisesAnException($expected, $ddouble) {
        $this->assertEquals($expected, $this->caster->setValue($ddouble)->castDouble());
    }

    public function getForcedDoubles() {
        return [
            [0, ''],
            [0, null],
            [0, 'one'],
            ['15', '15/3'],
            [15.2, '15.2.2'],
        ];
    }

    /**
     * @dataProvider getDoubles
     */
    public function testFloatsCasting($expected, $float) {
        $this->assertEquals($expected, $this->caster->setValue($float)->castFloat());
    }

    /**
     * @dataProvider getForcedDoubles
     */
    public function testForcedFloatsCasting($expected, $float) {
        $this->hydrator->enableMismatchesTolerance(true);
        $this->assertEquals($expected, $this->caster->setValue($float)->castFloat());
    }

    /**
     * @dataProvider getForcedDoubles
     * @expectedException \Doctrine\ODM\OrientDB\Caster\CastingMismatchException
     */
    public function testForcedFloatsCastingRaisesAnException($expected, $float) {
        $this->assertEquals($expected, $this->caster->setValue($float)->castFloat());
    }

    /**
     * @dataProvider getStrings
     */
    public function testStringCasting($expected, $string) {
        $this->assertEquals($expected, $this->caster->setValue($string)->castString());
    }

    public function getStrings() {
        return [
            ['0', '0'],
            ['hello', 'hello'],
            ['', ''],
        ];
    }

    /**
     * @dataProvider getForcedStrings
     */
    public function testForcedStringsCasting($expected, $string) {
        $this->hydrator->enableMismatchesTolerance(true);
        $this->assertEquals($expected, $this->caster->setValue($string)->castString());
    }

    /**
     * @dataProvider getForcedStrings
     * @expectedException \Doctrine\ODM\OrientDB\Caster\CastingMismatchException
     */
    public function testForcedStringsCastingRaisesAnException($expected, $string) {
        $this->assertEquals($expected, $this->caster->setValue($string)->castString());
    }

    public function getForcedStrings() {
        return [
            ['12', 12],
            ['-12', -12],
            ['', null],
            ['Array', [1, 2, 3]],
        ];
    }

    public function testInjectingTheValueInTheConstructor() {
        $this->caster = new Caster($this->hydrator, 'v');
        $this->assertEquals('v', $this->caster->castString());
    }

    /**
     * @dataProvider getShorts
     */
    public function testShortsCasting($short) {
        $this->assertEquals($short, $this->caster->setValue($short)->castShort());
    }

    public function getShorts() {
        return [
            [0],
            [1],
            [100],
            [127],
            [32766],
            [-127],
            [-32766],
            [-128],
            [-1],
        ];
    }

    /**
     * @dataProvider getForcedShorts
     */
    public function testForcedShortsCasting($expected, $short) {
        $this->hydrator->enableMismatchesTolerance(true);
        $this->assertEquals($expected, $this->caster->setValue($short)->castShort());
    }

    /**
     * @dataProvider getForcedShorts
     * @expectedException \Doctrine\ODM\OrientDB\Caster\CastingMismatchException
     */
    public function testForcedShortsCastingRaisesAnException($expected, $short) {
        $this->assertEquals($expected, $this->caster->setValue($short)->castShort());
    }

    public function getForcedShorts() {
        return [
            [32767, 32767],
            [32767, -32767],
            ['bella', 'bella'],
            [true, true],
            [[], []],
        ];
    }

    /**
     * @dataProvider getDateTimes
     */
    public function testDateTimesCasting($expected, $datetimes) {
        $this->assertEquals($expected, $this->caster->setValue($datetimes)->castDateTime());
    }

    public function getDateTimes() {
        return [
            [new \DateTime('2011-01-01 11:11:11'), '2011-01-01 11:11:11'],
        ];
    }

    /**
     * @dataProvider getDates
     */
    public function testDatesCasting($expected, $date) {
        $this->hydrator->enableMismatchesTolerance(true);
        $this->assertEquals($expected, $this->caster->setValue($date)->castDate());
    }

    public function getDates() {
        return [
            [new \DateTime('2012-12-30'), '2012-12-30'],
        ];
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testThrowsInvalidArgumentExceptionForBadDateClass() {
        $i = new Caster($this->hydrator, null, '\Exception');
    }

    /**
     * @dataProvider getBinaries
     */
    public function testBinaryCasting($binary) {
        $this->assertEquals('data:;base64,' . $binary, $this->caster->setValue($binary)->castBinary());
    }

    public function getBinaries() {
        return [
            ['2011-01-01 11:11:11'],
            [12],
            [-12],
        ];
    }

    /**
     * @dataProvider getForcedBinaries
     */
    public function testForcedBinaryCasting($binary) {
        $this->assertEquals('data:;base64,' . $binary, $this->caster->setValue($binary)->castBinary());
    }

    public function getForcedBinaries() {
        return array(
            array(new \Doctrine\OrientDB\Binding\Client\Http\CurlClientResponse("1\r\n\r\n2")),
        );
    }

    /**
     * @dataProvider getLinks
     */
    public function testLinksCasting($expected, $link) {
        $this->assertEquals($expected, $this->caster->setValue($link)->castLink());
    }

    public function getLinks() {
        $addressId                  = '#' . $this->getClassId('Address') . ':0';
        $orientDocument             = new \stdClass();
        $orientDocument->{"@class"} = 'Address';
        $orientDocument->{"@rid"}   = $addressId;

        $address = $this->ensureProxy($orientDocument);

        return [
            [$address, $orientDocument],
            [$address, $addressId],
            [null, 'pete']
        ];
    }

    /**
     * @dataProvider getLinkCollections
     */
    public function testLinkListCasting($expected, $linkCollection) {
        $this->assertEquals($expected, $this->caster->setValue($linkCollection)->castLinkList());
    }

    /**
     * @dataProvider getLinkCollections
     */
    public function testLinkSetCasting($expected, $linkCollection) {
        $this->assertEquals($expected, $this->caster->setValue($linkCollection)->castLinkSet());
    }

    /**
     * @dataProvider getLinkCollections
     */
    public function testLinkMapCasting($expected, $linkCollection) {

        $this->assertEquals($expected, $this->caster->setValue($linkCollection)->castLinkMap());
    }

    public function getLinkCollections() {
        $orientDocument             = new \stdClass();
        $orientDocument->{"@class"} = 'Address';
        $orientDocument->{"@rid"}   = '#' . $this->getClassId('Address') . ':0';;


        $address = $this->ensureProxy($orientDocument);

        $countryRid = '#' . $this->getClassId('Country') . ':0';;

        return [
            [new ArrayCollection(['hello' => $this->createManager()
                                                            ->getReference($countryRid)]), ['hello' => $countryRid]],
            [new ArrayCollection(['hello' => $address]), ['hello' => $orientDocument]],
        ];
    }

    /**
     * @dataProvider getEmbedded
     */
    public function testEmbeddedCasting($expected, $embedded) {
        $this->assertEquals($expected, $this->caster->setValue($embedded)->castEmbedded());
    }

    public function getEmbedded() {
        $orientDocument             = new \stdClass();
        $orientDocument->{"@class"} = 'Address';
        $orientDocument->{"@rid"}   = '#' . $this->getClassId('Address') . ':0';

        $address = $this->ensureProxy($orientDocument);

        return array(
            array($address, $orientDocument),
        );
    }

    /**
     * @dataProvider getEmbeddedSet
     */
    public function testEmbeddedSetCasting($expected, $embeddedSet) {
        $property = $this->getMock(Property::class, null, [['cast' => 'embedded']]);

        $this->caster->setProperty('annotation', $property);

        $this->assertEquals($expected, $this->caster->setValue($embeddedSet)->castEmbeddedSet());
    }

    /**
     * @dataProvider getEmbeddedSet
     */
    public function testEmbeddedMapCasting($expected, $embeddedSet) {
        $property = $this->getMock(Property::class, null, [['cast' => 'embedded']]);

        $this->caster->setProperty('annotation', $property);

        $this->assertEquals($expected, $this->caster->setValue($embeddedSet)->castEmbeddedMap());
    }

    /**
     * @dataProvider getEmbeddedSet
     */
    public function testEmbeddedListCasting($expected, $embeddedSet) {
        $property = $this->getMock(Property::class, null, [['cast' => 'embedded']]);

        $this->caster->setProperty('annotation', $property);

        $this->assertEquals($expected, $this->caster->setValue($embeddedSet)->castEmbeddedList());
    }

    public function getEmbeddedSet() {
        $orientDocument             = new \stdClass();
        $orientDocument->{"@class"} = 'Address';
        $orientDocument->{"@rid"}   = '#' . $this->getClassId('Address') . ':0';

        $address = $this->ensureProxy($orientDocument);

        return [
            [['hello' => $address], ['hello' => $orientDocument]],
        ];
    }
}
