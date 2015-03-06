<?php

namespace test\Doctrine\ODM\OrientDB\Document\Stub\Contact;

/**
* @Document(class="ContactAddress")
*/
class Address
{
    /**
     * @RID
     */
    protected $rid;
    /**
     * @Property(name="nojson", type="nojson")
     */
    protected $annotatedNotInJSON;
    /**
     * @Property(name="date", type="date")
     */
    protected $date;
    /**
     * @Property(name="datetime", type="datetime")
     */
    protected $date_time;
    /**
     * @Property(name="type", type="string")
     */
    protected $type;
    /**
     * @Property(name="is_true", type="boolean")
     */
    protected $is__true;
    /**
     * @Property(name="is_false", type="boolean")
     */
    protected $is__false;

    /**
     * @Property(name="sample")
     */
    protected $example_property;

    /**
     * @Property(name="capital", type="double")
     */
    protected $capital;

    /**
     * @Property(name="negative_short", type="short")
     */
    protected $negative_short;

    /**
     * @Property(name="positive_short", type="short")
     */
    protected $positive_short;

    /**
     * @Property(name="invalid_short", type="short")
     */
    protected $invalid_short;

    /**
     * @Property(name="negative_long", type="long")
     */
    protected $negative_long;

    /**
     * @Property(name="positive_long", type="long")
     */
    protected $positive_long;

    /**
     * @Property(name="invalid_long", type="long")
     */
    protected $invalid_long;

    /**
     * @Property(name="negative_byte", type="byte")
     */
    protected $negative_byte;

    /**
     * @Property(name="positive_byte", type="byte")
     */
    protected $positive_byte;

    /**
     * @Property(name="invalid_byte", type="byte")
     */
    protected $invalid_byte;

    /**
     * @Property(type="float")
     */
    protected $floating;

    /**
     * @Property(type="binary")
     */
    protected $image;

    /**
     * @Link(targetClass="test")
     */
    protected $link;

    /**
     * @Property(type="embedded")
     */
    protected $embedded;


    /**
     * @Property(type="embedded_set", cast="link")
     */
    protected $embeddedset;

    /**
     * @Property(type="embedded_list", cast="link")
     */
    protected $embeddedlist;

    /**
     * @Property(type="embedded_list", cast="boolean")
     */
    protected $embeddedbooleans;

    /**
     * @Property(type="embedded_list", cast="string")
     */
    protected $embeddedstrings;

    /**
     * @Property(type="embedded_list", cast="integer")
     */
    protected $embeddedintegers;

    /**
     * @Property(type="embedded_set", cast="boolean")
     */
    protected $embeddedsetbooleans;

    /**
     * @Property(type="embedded_set", cast="string")
     */
    protected $embeddedsetstrings;

    /**
     * @Property(type="embedded_set", cast="integer")
     */
    protected $embeddedsetintegers;

    /**
     * @Property(type="link")
     */
    protected $lazy_link;

    /**
     * @Property(type="linklist")
     */
    protected $lazy_linklist;

    /**
     * @Property(type="linkset")
     */
    public $lazy_linkset;

    /**
     * @Property(type="linkmap")
     */
    public $lazy_linkmap;

    /**
     * @Property(type="linkset")
     */
    protected $linkset;

    /**
     * @Property(type="linklist")
     */
    protected $linklist;

    /**
     * @Property(type="linkmap")
     */
    protected $linkmap;

    /**
     * @Property(type="embedded_map", cast="link")
     */
    protected $embedded_map;

    /**
     * @Property(type="integer")
     */
    protected $number;

    protected $street;

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getAnnotatedButNotInJSON()
    {
        return $this->annotatedNotInJSON;
    }

    public function setDate($date)
    {
        $this->date = $date;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function setEmbeddedList($list)
    {
        $this->embeddedlist = $list;
    }

    public function getEmbeddedList()
    {
        return $this->embeddedlist;
    }

    public function setEmbeddedSet($set)
    {
        $this->embeddedset = $set;
    }

    public function getEmbeddedSet()
    {
        return $this->embeddedset;
    }

    public function setEmbeddedIntegers($list)
    {
        $this->embeddedintegers = $list;
    }

    public function getEmbeddedIntegers()
    {
        return $this->embeddedintegers;
    }

    public function setEmbeddedBooleans($list)
    {
        $this->embeddedbooleans = $list;
    }

    public function getEmbeddedBooleans()
    {
        return $this->embeddedbooleans;
    }

    public function setEmbeddedStrings($list)
    {
        $this->embeddedstrings = $list;
    }

    public function getEmbeddedStrings()
    {
        return $this->embeddedstrings;
    }

    public function setEmbeddedSetIntegers($list)
    {
        $this->embeddedsetintegers = $list;
    }

    public function getEmbeddedSetIntegers()
    {
        return $this->embeddedsetintegers;
    }

    public function setEmbeddedSetBooleans($list)
    {
        $this->embeddedsetbooleans = $list;
    }

    public function getEmbeddedSetBooleans()
    {
        return $this->embeddedsetbooleans;
    }

    public function setEmbeddedSetStrings($list)
    {
        $this->embeddedsetstrings = $list;
    }

    public function getEmbeddedSetStrings()
    {
        return $this->embeddedsetstrings;
    }


    public function setDateTime($date)
    {
        $this->date_time = $date;
    }

    public function getDateTime()
    {
        return $this->date_time;
    }

    public function getCapital()
    {
        return $this->capital;
    }

    public function setCapital($capital)
    {
        $this->capital = $capital;
    }

    public function getNegativeShort()
    {
        return $this->negative_short;
    }

    public function setNegativeShort($short)
    {
        $this->negative_short = $short;
    }

    public function getPositiveShort()
    {
        return $this->positive_short;
    }

    public function setPositiveShort($short)
    {
        $this->positive_short = $short;
    }

    public function getLink()
    {
        return $this->link;
    }

    public function setLink($link)
    {
        $this->link = $link;
    }

    public function getEmbedded()
    {
        return $this->embedded;
    }

    public function setEmbedded($embedded)
    {
        $this->embedded = $embedded;
    }

    public function getLinkset()
    {
        return $this->linkset;
    }

    public function setLinkset($linkset)
    {
        $this->linkset = $linkset;
    }

    public function getLinklist()
    {
        return $this->linklist;
    }

    public function setLinklist($linklist)
    {
        $this->linklist = $linklist;
    }

    public function getLinkmap()
    {
        return $this->linkmap;
    }

    public function setLinkmap($linkmap)
    {
        $this->linkmap = $linkmap;
    }

    public function getEmbeddedMap()
    {
        return $this->embedded_map;
    }

    public function setEmbeddedMap($embedded_map)
    {
        $this->embedded_map = $embedded_map;
    }

    public function getLazyLink()
    {
        return $this->lazy_link;
    }

    public function setLazyLink($lazy_link)
    {
        $this->lazy_link = $lazy_link;
    }

    public function getLazyLinkList()
    {
        return $this->lazy_linklist;
    }

    public function setLazyLinkList($lazy_linklist)
    {
        $this->lazy_linklist = $lazy_linklist;
    }

    public function getInvalidShort()
    {
        return $this->invalid_short;
    }

    public function setInvalidShort($short)
    {
        $this->invalid_short = $short;
    }

    public function getImage()
    {
        return $this->image;
    }

    public function setImage($image)
    {
        $this->image = $image;
    }

    public function getNegativeLong()
    {
        return $this->negative_long;
    }

    public function setNegativeLong($long)
    {
        $this->negative_long = $long;
    }

    public function getPositiveLong()
    {
        return $this->positive_long;
    }

    public function setPositiveLong($long)
    {
        $this->positive_long = $long;
    }

    public function getInvalidLong()
    {
        return $this->invalid_long;
    }

    public function setInvalidLong($long)
    {
        $this->invalid_long = $long;
    }

    public function getNegativeByte()
    {
        return $this->negative_byte;
    }

    public function setNegativeByte($Byte)
    {
        $this->negative_byte = $Byte;
    }

    public function getPositiveByte()
    {
        return $this->positive_byte;
    }

    public function setPositiveByte($Byte)
    {
        $this->positive_byte = $Byte;
    }

    public function getInvalidByte()
    {
        return $this->invalid_byte;
    }

    public function setInvalidByte($Byte)
    {
        $this->invalid_byte = $Byte;
    }

    public function getNumber()
    {
        return $this->number;
    }

    public function setNumber($number)
    {
        $this->number = $number;
    }

    public function getFloating()
    {
        return $this->floating;
    }

    public function setFloating($floating)
    {
        $this->floating = $floating;
    }

    public function setStreet($street)
    {
        $this->street = $street;
    }

    public function getStreet()
    {
        return $this->street;
    }

    public function getIsTrue()
    {
        return $this->is__true;
    }

    public function getisFalse()
    {
        return $this->is__false;
    }

    public function setIsTrue($val)
    {
        $this->is__true = $val;
    }

    public function setisFalse($val)
    {
        $this->is__false = $val;
    }

    public function getExampleProperty()
    {
        return $this->example_property;
    }

    public function setExampleProperty($value)
    {
        $this->example_property = $value;
    }

    public function testCustomMethod($k1, $k2)
    {
        return $k1 + $k2;
    }

    public static function testStaticMethod($k1, $k2)
    {
        return $k1 + $k2;
    }
}
