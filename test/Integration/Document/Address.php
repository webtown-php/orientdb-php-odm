<?php

/*
 * This file is part of the Orient package.
 *
 * (c) Alessandro Nadalin <alessandro.nadalin@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Class Address
 *
 * @package
 * @subpackage
 * @author      Alessandro Nadalin <alessandro.nadalin@gmail.com>
 * @author      David Funaro <ing.davidino@gmail.com>
 */

namespace Integration\Document;

/**
 * @Document(class="Address")
 */
class Address
{
    /**
     * @RID
     */
    public $rid;

    /**
     * @Version
     */
    public $version;

    /**
     * @Link(targetClass="City")
     */
    protected $city;

    protected $about;

    /**
     * @return City
     */
    public function getCity() {
        return $this->city;
    }

    public function setCity($city) {
        $this->city = $city;
    }
}
