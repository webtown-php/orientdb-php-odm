<?php

/*
 * This file is part of the Doctrine\OrientDB package.
 *
 * (c) Alessandro Nadalin <alessandro.nadalin@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Class used to manipulate and identity properties in an annotation.
 *
 * @package    Doctrine\ODM
 * @subpackage OrientDB
 * @author     Alessandro Nadalin <alessandro.nadalin@gmail.com>
 */

namespace Doctrine\ODM\OrientDB\Mapping\Annotations;
use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 * @Attributes({
 *    @Attribute("type",     required = false, type = "string"),
 *    @Attribute("cast",     required = false, type = "string"),
 *    @Attribute("nullable", required = false, type = "bool"  ),
 * })
 */
class Property extends AbstractProperty
{
    public $type;
    public $cast;
    public $nullable = false;

    public function getCast()
    {
        return $this->cast;
    }
}
