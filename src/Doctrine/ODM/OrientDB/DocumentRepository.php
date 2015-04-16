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
 * Repository class
 *
 * @package    Doctrine\ODM
 * @subpackage OrientDB
 * @author     Alessandro Nadalin <alessandro.nadalin@gmail.com>
 * @author     David Funaro <ing.davidino@gmail.com>
 */

namespace Doctrine\ODM\OrientDB;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\Common\Util\Inflector;
use Doctrine\ODM\OrientDB\Mapping\ClassMetadata;
use Doctrine\OrientDB\OrientDBException;
use Doctrine\OrientDB\Query\QueryBuilder;
use RuntimeException;

class DocumentRepository implements ObjectRepository
{
    /**
     * @var DocumentManager
     */
    protected $dm;
    /**
     * @var UnitOfWork
     */
    protected $uow;
    /**
     * @var ClassMetadata
     */
    protected $metadata;

    /**
     * Instantiates a new repository.
     *
     * @param DocumentManager $dm
     * @param UnitOfWork      $uow
     * @param ClassMetadata   $metadata
     */
    public function __construct(DocumentManager $dm, UnitOfWork $uow, ClassMetadata $metadata) {
        $this->dm       = $dm;
        $this->uow      = $uow;
        $this->metadata = $metadata;
    }

    /**
     * Convenient method that intercepts the find*By*() calls.
     *
     * @param string $method
     * @param array  $arguments
     *
     * @return mixed
     * @throws RuntimeException
     */
    public function __call($method, $arguments) {
        if (strpos($method, 'findOneBy') === 0) {
            $property = substr($method, 9);
            $method   = 'findOneBy';
        } elseif (strpos($method, 'findBy') === 0) {
            $property = (substr($method, 6));
            $method   = 'findBy';
        } else {
            throw new RuntimeException(sprintf("The %s repository class does not have a method %s", get_called_class(), $method));
        }

        $property = Inflector::tableize($property);

        foreach ($arguments as $position => $argument) {
            if (is_object($argument)) {
                if (!method_exists($argument, 'getRid')) {
                    throw new RuntimeException("When calling \$repository->find*By*(), you can only pass, as arguments, objects that have the getRid() method (shortly, entitites)");
                }
                $arguments[$position] = $argument->getRid();
            }
        }

        return $this->$method([$property => $arguments[0]]);
    }

    /**
     * Finds an object by its primary key / identifier.
     *
     * @param string $rid The identifier.
     *
     * @return object The object.
     * @throws OrientDBException
     * @throws OClassNotFoundException
     * @throws \Exception
     */
    public function find($rid) {
        return $this->findWithPlan($rid);
    }

    /**
     * @param        $rid
     * @param string $fetchPlan
     *
     * @return mixed|null
     * @throws OrientDBException
     */
    public function findWithPlan($rid, $fetchPlan = '*:0') {
        if (empty($rid)) {
            return null;
        }

        // try identity map first
        if (!$document = $this->uow->tryGetById($rid, $this->metadata)) {
            $document = $this->getDocumentPersister()->load($rid, $fetchPlan);
        }

        if ($document && !$this->contains($document)) {
            throw new OrientDBException(
                "You are asking to find record $rid through the repository {$this->getClassName()} " .
                "but the document belongs to another repository (" . get_class($document) . ")"
            );
        }

        return $document;
    }

    /**
     * Finds all objects in the repository.
     *
     * @return mixed The objects.
     */
    public function findAll() {
        return $this->findBy([]);
    }

    /**
     * Finds objects by a set of criteria.
     *
     * Optionally sorting and limiting details can be passed. An implementation may throw
     * an UnexpectedValueException if certain values of the sorting or limiting details are
     * not supported.
     *
     * @param array      $criteria
     * @param array|null $orderBy
     * @param int|null   $limit
     * @param int|null   $offset
     * @param string     $fetchPlan
     *
     * @return ArrayCollection The objects.
     * @throws OrientDBException
     */
    public function findBy(array $criteria, array $orderBy = [], $limit = null, $offset = null, $fetchPlan = '*:0') {
        $parts[] = sprintf('SELECT FROM %s', $this->metadata->getOrientClass());

        if ($criteria) {
            $where = [];
            foreach ($criteria as $key => $value) {
                $value   = json_encode($value);
                $where[] = "$key = $value";
            }
            $parts[] = sprintf('WHERE %s', implode(' AND ', $where));
        }

        if ($orderBy) {
            $orders = [];
            foreach ($orderBy as $key => $order) {
                $orders[] = "$key $order";
            }

            $parts[] = sprintf('ORDER BY %s', implode(', ', $orders));
        }

        if ($limit) {
            $parts[] = "LIMIT " . $limit;
        }

        $select = implode(' ', $parts);

        $collection = $this->dm->query($select, $fetchPlan);

        if (!$collection instanceof ArrayCollection) {
            throw new OrientDBException(
                "Problems executing the query \"{$parts->getRaw()}\". " .
                "The server returned $collection instead of ArrayCollection."
            );
        }

        return $collection;
    }

    /**
     * Finds a single object by a set of criteria.
     *
     * @param array $criteria
     *
     * @return object The object.
     */
    public function findOneBy(array $criteria) {
        $documents = $this->findBy($criteria, [], 1);

        if ($documents instanceof ArrayCollection && count($documents)) {
            return $documents->first();
        }

        return null;
    }

    /**
     * Returns the POPO class associated with this repository.
     *
     * @return string
     */
    public function getClassName() {
        return $this->metadata->getName();
    }

    /**
     * Verifies if the $document should belog to this repository.
     *
     * @param  object $document
     *
     * @return boolean
     */
    protected function contains($document) {
        return $this->metadata->name === ClassUtils::getClass($document);
    }

    /**
     * @return Persister\DocumentPersister
     */
    protected function getDocumentPersister() {
        return $this->uow->getDocumentPersister($this->metadata->name);
    }
}
