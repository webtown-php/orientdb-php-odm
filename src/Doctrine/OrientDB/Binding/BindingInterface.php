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
    public function getServerInfo();

    /**
     * Executes a raw command on the given database.
     *
     * @api
     *
     * @param string $query
     * @param string $language
     *
     * @return BindingResultInterface
     *
     */
    public function command($query, $language = BindingInterface::LANGUAGE_SQLPLUS);

    /**
     * Executes a raw query on the given database.
     *
     * Results can be limited with the $limit parameter and a fetch plan can be used to
     * specify how to retrieve the graph and limit its depth.
     *
     * It differs from the command because OrientDB defines a query as a SELECT only.
     *
     * @api
     *
     * @param string $query SQL or Gremlin query.
     * @param int    $limit Maximum number of records (default is 20).
     * @param string $fetchPlan
     * @param string $language
     *
     * @return BindingResultInterface
     *
     */
    public function query($query, $limit = null, $fetchPlan = null, $language = BindingInterface::LANGUAGE_SQLPLUS);

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

    /**
     * Retrieves a record from the database. An optional fetch plan can be used to
     * specify how to retrieve the graph and limit its depth.
     *
     * @api
     *
     * @param string $rid
     * @param string $fetchPlan
     *
     * @return mixed|null
     *
     */
    public function getDocument($rid, $fetchPlan = null);

    /**
     * Determines if a document exists for the specified $rid
     *
     * @param string $rid
     *
     * @return bool
     */
    public function documentExists($rid);

    /**
     * @param string $cmd
     * @param bool   $transaction
     *
     * @return mixed
     */
    public function sqlBatch($cmd, $transaction = true);
}
