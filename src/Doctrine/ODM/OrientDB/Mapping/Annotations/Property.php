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
 */
class Property extends PropertyBase
{
    /**
     * @var string
     */
    public $type;

    /**
     * @var bool
     */
    public $nullable = false;

    /**
     * @var int
     */
    public $min;

    /**
     * @var int
     */
    public $max;

    /**
     * @var bool
     */
    public $mandatory = false;

    /**
     * @var bool
     */
    public $readonly = false;
}
