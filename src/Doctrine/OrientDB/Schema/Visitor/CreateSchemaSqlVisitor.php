<?php

namespace Doctrine\OrientDB\Schema\Visitor;

use Doctrine\OrientDB\Schema\OClass;
use Doctrine\OrientDB\Schema\OIndex;
use Doctrine\OrientDB\Schema\OProperty;
use Doctrine\OrientDB\Schema\OSchema;
use Doctrine\OrientDB\Schema\OSchemaVisitorInterface;
use Doctrine\OrientDB\Types\ComplexType;
use Doctrine\OrientDB\Types\Type;

class CreateSchemaSqlVisitor implements OSchemaVisitorInterface
{
    use OSchemaVisitorTrait;

    /**
     * @var string[]
     */
    private $_classSql = [];

    /**
     * @var string[]
     */
    private $_propertySql = [];

    /**
     * @var string[]
     */
    private $_indexSql = [];

    /**
     * @return string[]
     */
    public function getSql() {
        return array_merge($this->_classSql, $this->_propertySql, $this->_indexSql);
    }

    public function onVisitingOClass(OClass $node) {
        if (OSchema::isSystemClass($node->getName())) {
            return false;
        }

        $this->_classSql[] = $this->getCreateClassSql($node);

        return true;
    }

    private function getCreateClassSql(OClass $node) {
        $sql = sprintf('CREATE CLASS %s', $node->getName());
        if ($node->getSuperClass()) {
            $sql .= sprintf(' EXTENDS %s', $node->getSuperClass()->getName());
        }

        if ($node->isAbstract()) {
            $sql .= ' ABSTRACT';
        }

        return $sql;
    }


    public function onVisitedOProperty(OProperty $node) {
        if (OSchema::isSystemProperty($node->getName())) {
            return;
        }

        $this->_propertySql[] = $this->getCreatePropertySql($node);

        if ($node->isMandatory()) {
            $this->_propertySql[] = $this->getAlterPropertySqlForAttribute($node, 'mandatory', true);
        }

        if ($node->isReadOnly()) {
            $this->_propertySql[] = $this->getAlterPropertySqlForAttribute($node, 'readonly', true);
        }

        if ($node->isNotNull()) {
            $this->_propertySql[] = $this->getAlterPropertySqlForAttribute($node, 'notnull', true);
        }

        if ($node->getRegExp()) {
            $this->_propertySql[] = $this->getAlterPropertySqlForAttribute($node, 'regexp', $node->getRegExp());
        }

        $collate = $node->getCollate();
        if ($collate && strcasecmp($collate, 'default') !== 0) {
            $this->_propertySql[] = $this->getAlterPropertySqlForAttribute($node, 'collate', $collate);
        }
    }

    private function getCreatePropertySql(OProperty $node) {
        return sprintf('CREATE PROPERTY %s.%s %s',
            $node->getClass()->getName(),
            $node->getName(),
            $this->getTypeDeclarationSql($node)
        );
    }

    private function getAlterPropertySqlForAttribute(OProperty $node, $attr, $val) {
        if (is_bool($val)) {
            $val = $val === true ? 'true' : 'false';
        }

        return sprintf('ALTER PROPERTY %s.%s %s %s',
            $node->getClass()->getName(),
            $node->getName(),
            strtoupper($attr),
            $val
        );
    }

    private function getTypeDeclarationSql(OProperty $node) {
        $type = $node->getType();
        $name = strtoupper($type->getName());

        if ($type instanceof ComplexType) {
            $refType = $node->getLinkedClass() ?: $node->getLinkedType();
            if ($refType) {
                $name .= " $refType";
            }
        }

        return $name;
    }

    public function onVisitedOIndex(OIndex $node) {
        $this->_indexSql[] = $this->getCreateIndexSql($node);
    }

    private function getCreateIndexSql(OIndex $node) {
        if ($node->isAutomatic()) {
            return sprintf('CREATE INDEX %s %s',
                $node->getName(),
                $node->getType()
            );
        }

        return sprintf('CREATE INDEX %s ON %s (%s) %s',
            $node->getName(),
            $node->getClass()->getName(),
            implode(', ', $node->getFields()),
            $node->getType()
        );
    }
}