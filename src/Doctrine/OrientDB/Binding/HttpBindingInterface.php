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
 * This interface is implemented in order to be compliant with the interface
 * Doctrine\OrientDB exposes through its HTTP interface.
 * See: http://code.google.com/p/orient/wiki/Doctrine\OrientDB_REST
 *
 * @package    Doctrine\OrientDB
 * @subpackage Binding
 * @author     Alessandro Nadalin <alessandro.nadalin@gmail.com>
 * @author     Daniele Alessandri <suppakilla@gmail.com>
 */

namespace Doctrine\OrientDB\Binding;

use Doctrine\OrientDB\Binding\Adapter\HttpClientAdapterInterface;

interface HttpBindingInterface extends BindingInterface
{
    /**
     * Deletes a class.
     *
     * @api
     *
     * @param string $class
     * @param string $database
     *
     * @return HttpBindingResultInterface
     */
    public function deleteClass($class, $database = null);

    /**
     * Retrieves a class and its records.
     *
     * @api
     *
     * @param string $class
     * @param string $database
     *
     * @return HttpBindingResultInterface
     */
    public function getClass($class, $database = null);

    /**
     * Creates a new class.
     *
     * @api
     *
     * @param string $class
     * @param string $body
     * @param string $database
     *
     * @return HttpBindingResultInterface
     */
    public function postClass($class, $body = null, $database = null);

    /**
     * Retrieves records from the given cluster in the database.
     *
     * @api
     *
     * @param   string  $cluster
     * @param   string  $database
     * @param   integer $limit
     *
     * @return HttpBindingResultInterface
     */
    public function cluster($cluster, $limit = null, $database = null);

    /**
     * Connects to the specified database.
     *
     * @api
     *
     * @param string $database
     *
     * @return HttpBindingResultInterface
     */
    public function connect($database);

    /**
     * Disconnect this instance from the server.
     *
     * @api
     * @return HttpBindingResultInterface
     */
    public function disconnect();

    /**
     * Stores a new document in the database.
     *
     * @api
     *
     * @param string $document
     * @param string $database
     *
     * @return HttpBindingResultInterface
     */
    public function postDocument($document, $database = null);

    /**
     * Updates an existing document in the database.
     *
     * @api
     *
     * @param string $rid
     * @param string $document
     * @param string $database
     *
     * @return HttpBindingResultInterface
     */
    public function putDocument($rid, $document, $database = null);

    /**
     * Deletes a document from the database.
     *
     * @api
     *
     * @param string $rid
     * @param string $version
     * @param string $database
     *
     * @return HttpBindingResultInterface
     */
    public function deleteDocument($rid, $version = null, $database = null);

    /**
     * Sets the username and password used to authenticate to the server.
     *
     * @param string $username
     * @param string $password
     */
    public function setAuthentication($username = null, $password = null);

    /**
     * Sets the underlying HTTP client adapter.
     *
     * @param HttpClientAdapterInterface $adapter
     */
    public function setAdapter(HttpClientAdapterInterface $adapter);

    /**
     * Sets the underlying HTTP client adapter.
     *
     * @return HttpClientAdapterInterface
     */
    public function getAdapter();
}
