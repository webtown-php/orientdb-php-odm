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
 * This class manages the generation of SQL statements able to assign or revoke
 * permissions inside OrientDB.
 *
 * @package    Orient
 * @subpackage Query
 * @author     Alessandro Nadalin <alessandro.nadalin@gmail.com>
 */

namespace Orient\Query\Command;

use Orient\Contract\Query\Command\Credential as CredentialInterface;
use Orient\Query\Command;

abstract class Credential extends Command implements CredentialInterface
{
    /**
     * Creates a new statement, setting the $permission.
     *
     * @param string $permission
     */
    public function __construct($permission)
    {
        parent::__construct();

        $this->permission($permission);
    }

    /**
     * Sets a permission for the query.
     *
     * @param   string $permission
     * @return  Credential
     */
    public function permission($permission)
    {
        $this->setToken('Permission', $permission);

        return $this;
    }

    /**
     * Sets the $resource on which the credential is given.
     *
     * @param   string $resource
     * @return  Credential
     */
    public function on($resource)
    {
        $this->setToken('Resource', $resource);

        return $this;
    }

    /**
     * Sets the $role having the credential on a resource.
     *
     * @param   string $role
     * @return  Credential
     */
    public function to($role)
    {
        $this->setToken('Role', $role);

        return $this;
    }
}
