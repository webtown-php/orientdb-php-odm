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
 * Class used to identify a document's annotations.
 *
 * @package    Doctrine\ODM
 * @subpackage OrientDB
 * @author     Alessandro Nadalin <alessandro.nadalin@gmail.com>
 */

namespace Doctrine\ODM\OrientDB\Mapping\Annotations;

/**
 * @Annotation
 * @Target("CLASS")
 */
class Document extends AbstractPersistentDocument
{
    /**
     * @Required
     * @var string
     */
    public $oclass;
}
