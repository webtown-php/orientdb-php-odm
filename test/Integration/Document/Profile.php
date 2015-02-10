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
 * Class Profile
 *
 * @package
 * @subpackage
 * @author      Alessandro Nadalin <alessandro.nadalin@gmail.com>
 * @author      David Funaro <ing.davidino@gmail.com>
 */

namespace test\Integration\Document;
use Doctrine\ODM\OrientDB\PersistentCollection;

/**
* @Document(class="Profile")
*/
class Profile
{
    /**
     * @RID
     */
    public $rid;

    /**
     * @Property(type="long")
     */
    public $hash;

    /**
     * @LinkMap(targetClass="Profile")
     * @var Profile[]
     */
    public $followers;

    /**
     * @EmbeddedList(targetClass="Phone")
     * @var Phone[]
     */
    public $phones;

    /**
     * @return Phone[]|PersistentCollection
     */
    public function getPhones() {
        return $this->phones;
    }

    /**
     * @return Profile[]|PersistentCollection
     */
    public function getFollowers() {
        return $this->followers;
    }


}
