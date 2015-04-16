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
 * This interface is the foundation of the library as it enables to implement
 * classes that can peform requests to OrienDB using different protocols or
 * backends.
 *
 * @package    Doctrine\OrientDB
 * @subpackage Binding
 * @author     Daniele Alessandri <suppakilla@gmail.com>
 */

namespace Doctrine\OrientDB\Binding;

use Doctrine\OrientDB\Binding\Exception\InvalidDatabaseException;
use Doctrine\OrientDB\Query\CommandInterface;

interface BindingInterface
{
    const LANGUAGE_SQLPLUS = 'sql';
    const LANGUAGE_GREMLIN = 'gremlin';

    /**
     * Gets the current server.
     *
     * @api
     * @return BindingResultInterface
     */
    public function getServer();


    /**
     * Executes a raw command on the given database.
     *
     * @api
     *
     * @param string $query
     * @param string $language
     * @param string $database
     *
     * @return BindingResultInterface
     */
    public function command($query, $language = BindingInterface::LANGUAGE_SQLPLUS, $database = null);

    /**
     * Executes an SQL query on the server.
     *
     * The second argument specifies when to use COMMAND or QUERY as the
     * underlying command.
     *
     * @param CommandInterface $cmd       .
     * @param string           $fetchPlan Optional fetch plan for the query.
     *
     * @return BindingResultInterface
     */
    public function execute(CommandInterface $cmd, $fetchPlan = null);

    /**
     * Returns the name of the database the binding is
     * currently using.
     *
     * @return string
     */
    public function getDatabaseName();

    /**
     * Retrieves details regarding the specified database.
     *
     * @api
     *
     * @param string $database
     *
     * @return \stdClass
     * @throws InvalidDatabaseException
     */
    public function getDatabase($database = null);

    /**
     * Indicates whether the specified database exists
     *
     * @param string $database
     *
     * @return bool
     */
    public function databaseExists($database = null);

    /**
     * Deletes an existing database.
     *
     * @api
     *
     * @param string $database
     *
     * @throws InvalidDatabaseException
     */
    public function deleteDatabase($database);

    /**
     * Lists all the existing databases.
     *
     * @api
     * @return string[]
     */
    public function listDatabases();

    /**
     * Creates a new database.
     *
     * @api
     *
     * @param string $database
     * @param string $storage
     * @param string $type
     *
     * @return \stdClass
     */
    public function createDatabase($database, $storage = 'memory', $type = 'document');
}
