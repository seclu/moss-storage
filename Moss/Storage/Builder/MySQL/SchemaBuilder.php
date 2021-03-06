<?php

/*
 * This file is part of the Storage package
 *
 * (c) Michal Wachowski <wachowski.michal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Moss\Storage\Builder\MySQL;

use Moss\Storage\Builder\AbstractSchemaBuilder;
use Moss\Storage\Builder\BuilderException;
use Moss\Storage\Builder\SchemaBuilderInterface;

/**
 * MySQL schema builder - builds queries managing tables (create, alter, drop)
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
class SchemaBuilder extends AbstractSchemaBuilder implements SchemaBuilderInterface
{
    protected $fieldTypes = array(
        'boolean' => array('tinyint'),
        'integer' => array('smallint', 'mediumint', 'int', 'integer', 'bigint'),
        'decimal' => array('decimal'),
        'string' => array('char', 'varchar', 'tinytext', 'mediumtext', 'text', 'longtext'),
        'datetime' => array('time', 'date', 'datetime', 'timestamp', 'year'),
        'serial' => array('blob')
    );

    /**
     * Builds column definitions and return them as array
     *
     * @return array
     */
    protected function buildColumns()
    {
        $nodes = array();

        foreach ($this->columns as $node) {
            $nodes[] = $this->buildColumn($node[0], $node[1], $node[2]);
        }

        return $nodes;
    }

    /**
     * Builds column definitions for add alteration
     *
     * @return array
     */
    protected function buildAddColumns()
    {
        $nodes = array();

        foreach ($this->columns as $node) {
            $str = 'ADD ' . $this->buildColumn($node[0], $node[1], $node[2]);

            if ($node[3] !== null) {
                $str .= ' AFTER ' . $node[3];
            }

            $nodes[] = $str;
        }

        return $nodes;
    }

    /**
     * Builds column definitions for change
     *
     * @return array
     */
    protected function buildChangeColumns()
    {
        $nodes = array();

        foreach ($this->columns as $node) {
            $nodes[] = 'CHANGE ' . ($node[3] ? $node[3] : $node[0]) . ' ' . $this->buildColumn($node[0], $node[1], $node[2]);
        }

        return $nodes;
    }

    /**
     * Builds columns list to drop
     *
     * @return array
     */
    protected function buildDropColumns()
    {
        $nodes = array();

        foreach ($this->columns as $node) {
            $nodes[] = 'DROP ' . $node[0];
        }

        return $nodes;
    }

    /**
     * Builds column
     *
     * @param string $name
     * @param string $type
     * @param array  $attributes
     *
     * @return string
     */
    private function buildColumn($name, $type, array $attributes)
    {
        return $name . ' ' . $this->buildColumnType($name, $type, $attributes) . ' ' . $this->buildColumnAttributes($type, $attributes);
    }

    /**
     * Builds column type part
     *
     * @param string $name
     * @param string $type
     * @param array  $attributes
     *
     * @return string
     * @throws BuilderException
     */
    private function buildColumnType($name, $type, array $attributes)
    {
        switch ($type) {
            case 'boolean':
                return 'TINYINT(1)';
            case 'integer':
                $len = isset($attributes['length']) ? $attributes['length'] : 11;

                return sprintf('INT(%u)', $len);
            case 'decimal':
                $len = isset($attributes['length']) ? $attributes['length'] : 11;
                $prc = isset($attributes['precision']) ? $attributes['precision'] : 4;

                return sprintf('DECIMAL(%u,%u)', $len, $prc);
            case 'datetime':
                return 'DATETIME';
            case 'serial':
                return 'BLOB';
            case 'string':
                $len = isset($attributes['length']) ? $attributes['length'] : null;
                if ($len == 0 || $len > 1023) {
                    return 'TEXT';
                } elseif ($len > 255) {
                    return sprintf('VARCHAR(%u)', $len);
                } else {
                    return sprintf('CHAR(%u)', $len);
                }
        }

        throw new BuilderException(sprintf('Invalid type "%s" for field "%s"', $type, $name));
    }

    /**
     * Builds columns attributes part
     *
     * @param string $type
     * @param array  $attributes
     *
     * @return string
     */
    private function buildColumnAttributes($type, array $attributes)
    {
        $node = array();

        if (isset($attributes['default'])) {
            if (!in_array($type, array('boolean', 'integer', 'decimal'))) {
                $node[] = 'DEFAULT \'' . $attributes['default'] . '\'';
            } else {
                $node[] = 'DEFAULT ' . $attributes['default'];
            }
        } elseif (isset($attributes['null'])) {
            $node[] = 'DEFAULT NULL';
        } else {
            $node[] = 'NOT NULL';
        }

        if ($type == 'integer' && isset($attributes['auto_increment'])) {
            $node[] = 'AUTO_INCREMENT';
        }

        return implode(' ', $node);
    }

    /**
     * Builds key/index definitions and returns them as array
     *
     * @return array
     */
    protected function buildIndexes()
    {
        $nodes = array();

        foreach ($this->indexes as $node) {
            $nodes[] = $this->buildIndex($node[0], $node[1], $node[2], $node[3]);
        }

        return $nodes;
    }

    /**
     * Builds key/index definitions to add
     *
     * @return array
     */
    protected function buildAddIndex()
    {
        $nodes = array();

        foreach ($this->indexes as $node) {
            $nodes[] = 'ADD ' . $this->buildIndex($node[0], $node[1], $node[2], $node[3]);
        }

        return $nodes;
    }

    /**
     * Builds key/index definitions to add
     *
     * @return array
     */
    protected function buildDropIndex()
    {
        $nodes = array();

        foreach ($this->indexes as $node) {
            switch ($node[2]) {
                case 'primary':
                    $nodes[] = 'DROP PRIMARY KEY';
                    break;
                case 'foreign':
                    $nodes[] = 'DROP FOREIGN KEY ' . $this->table . '_' . $node[0];
                    break;
                default:
                    $nodes[] = 'DROP KEY ' . $this->table . '_' . $node[0];
            }
        }

        return $nodes;
    }

    /**
     * Builds index
     *
     * @param string      $name
     * @param array       $fields
     * @param string      $type
     * @param null|string $table
     *
     * @return string
     * @throws BuilderException
     */
    private function buildIndex($name, array $fields, $type = 'index', $table = null)
    {
        switch ($type) {
            case 'primary':
                return 'PRIMARY KEY (' . implode(', ', $fields) . ')';
            case 'foreign':
                return 'CONSTRAINT ' . $this->table . '_' . $name . ' FOREIGN KEY (' . implode(', ', array_keys($fields)) . ') REFERENCES ' . $table . ' (' . implode(', ', array_values($fields)) . ') ON UPDATE CASCADE ON DELETE RESTRICT';
            case 'unique':
                return 'UNIQUE KEY ' . $this->table . '_' . $name . ' (' . implode(', ', $fields) . ')';
            case 'index':
                return 'KEY ' . $this->table . '_' . $name . ' (' . implode(', ', $fields) . ')';
            default:
                throw new BuilderException(sprintf('Invalid type "%s" for index "%s"', $type, $name));
        }
    }

    /**
     * Builds query string
     *
     * @return string
     * @throws BuilderException
     */
    public function build()
    {
        if (empty($this->table)) {
            throw new BuilderException('Missing table name');
        }

        $stmt = array();
        switch ($this->operation) {
            case 'check':
                $stmt[] = <<<"SQL"
SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_NAME = '{$this->table}'
SQL;
                break;
            case 'info':
                $stmt[] = <<<"SQL"
SELECT
	c.ORDINAL_POSITION AS pos,
	c.TABLE_SCHEMA AS table_schema,
	c.TABLE_NAME AS table_name,
	c.COLUMN_NAME AS column_name,
	c.DATA_TYPE AS column_type,
	CASE WHEN LOCATE('(', c.NUMERIC_PRECISION) > 0 IS NOT NULL THEN c.NUMERIC_PRECISION ELSE c.CHARACTER_MAXIMUM_LENGTH END AS column_length,
	c.NUMERIC_SCALE AS column_precision,
	c.IS_NULLABLE AS column_nullable,
	CASE WHEN INSTR(LOWER(c.EXTRA), 'auto_increment') > 0 THEN 'YES' ELSE 'NO' END AS column_auto_increment,
	c.COLUMN_DEFAULT AS column_default,
	s.INDEX_NAME AS index_name,
	CASE WHEN (s.INDEX_NAME IS NOT NULL AND i.CONSTRAINT_TYPE IS NULL) THEN 'INDEX' ELSE i.CONSTRAINT_TYPE END AS index_type,
	k.ORDINAL_POSITION AS index_pos,
	k.REFERENCED_TABLE_SCHEMA AS ref_schema,
	k.REFERENCED_TABLE_NAME AS ref_table,
	k.REFERENCED_COLUMN_NAME AS ref_column
FROM
	information_schema.COLUMNS AS c
	LEFT JOIN information_schema.KEY_COLUMN_USAGE AS k ON c.TABLE_SCHEMA = k.TABLE_SCHEMA AND c.TABLE_NAME = k.TABLE_NAME AND c.COLUMN_NAME = k.COLUMN_NAME
	LEFT JOIN information_schema.STATISTICS AS s ON c.TABLE_SCHEMA = s.TABLE_SCHEMA AND c.TABLE_NAME = s.TABLE_NAME AND c.COLUMN_NAME = s.COLUMN_NAME
	LEFT JOIN information_schema.TABLE_CONSTRAINTS AS i ON k.CONSTRAINT_SCHEMA = i.CONSTRAINT_SCHEMA AND k.CONSTRAINT_NAME = i.CONSTRAINT_NAME
WHERE c.TABLE_NAME = '{$this->table}'
ORDER BY pos;
SQL;
                break;
            case 'create':
                $stmt[] = 'CREATE TABLE';
                $stmt[] = $this->table;
                $stmt[] = '(';
                $stmt[] = implode(', ', array_merge($this->buildColumns(), $this->buildIndexes()));
                $stmt[] = ')';
                $stmt[] = 'ENGINE=InnoDB DEFAULT CHARSET=utf8';
                break;
            case 'add':
                $stmt[] = 'ALTER TABLE';
                $stmt[] = $this->table;
                $stmt[] = implode(', ', array_merge($this->buildAddColumns(), $this->buildAddIndex()));
                break;
            case 'change':
                $stmt[] = 'ALTER TABLE';
                $stmt[] = $this->table;
                $stmt[] = implode(', ', array_merge($this->buildChangeColumns()));
                break;
            case 'remove':
                $stmt[] = 'ALTER TABLE';
                $stmt[] = $this->table;
                $stmt[] = implode(', ', array_merge($this->buildDropColumns(), $this->buildDropIndex()));
                break;
            case 'drop':
                $stmt[] = 'DROP TABLE IF EXISTS';
                $stmt[] = $this->table;
                break;
        }

        $stmt = array_filter($stmt);

        return implode(' ', $stmt);
    }

    /**
     * Build model like column description from passed row
     *
     * @param array $node
     *
     * @return array
     * @throws BuilderException
     */
    protected function parseColumn($node)
    {
        $type = strtolower(preg_replace('/^([^ (]+).*/i', '$1', $node['column_type']));

        $result = array(
            'name' => $node['column_name'],
            'type' => $node['column_type'],
            'attributes' => array(
                'length' => (int) $node['column_length'],
                'precision' => (int) $node['column_precision'],
                'null' => $node['column_nullable'] == 'YES',
                'auto_increment' => $node['column_auto_increment'] === 'YES',
                'default' => empty($node['column_default']) ? null : $node['column_default']
            )
        );

        switch ($type) {
            case in_array($type, $this->fieldTypes['boolean']):
                $result['type'] = 'boolean';
                break;
            case in_array($type, $this->fieldTypes['serial']):
                $result['type'] = 'serial';
                break;
            case in_array($type, $this->fieldTypes['integer']):
                $result['type'] = 'integer';
                break;
            case in_array($type, $this->fieldTypes['decimal']):
                $result['type'] = 'decimal';
                break;
            case in_array($type, $this->fieldTypes['string']):
                $result['type'] = 'string';
                break;
            case in_array($type, $this->fieldTypes['datetime']):
                $result['type'] = 'datetime';
                break;
            default:
                throw new BuilderException(sprintf('Invalid or unsupported field type "%s" in table "%s"', $type, $this->table));
        }

        return $result;
    }

    /**
     * Build model like index description from passed row
     *
     * @param array $node
     *
     * @return array
     * @throws BuilderException
     */
    protected function parseIndex($node)
    {
        $type = strtolower(preg_replace('/^([^ (]+).*/i', '$1', $node['index_type']));

        $result = array(
            'name' => $node['index_name'],
            'type' => $node['index_type'],
            'fields' => array($node['column_name']),
            'table' => $node['ref_table'],
            'foreign' => empty($node['ref_column']) ? array() : array($node['ref_column'])
        );

        switch ($type) {
            case 'primary':
                $result['type'] = 'primary';
                break;
            case 'unique':
                $result['type'] = 'unique';
                break;
            case 'index':
                $result['type'] = 'index';
                break;
            case 'foreign':
                $result['type'] = 'foreign';
                break;
            default:
                throw new BuilderException(sprintf('Invalid or unsupported index type "%s" in table "%s"', $type, $this->table));
        }

        if ($result['type'] == 'primary') {
            $result['name'] = 'primary';
        } else {
            $result['name'] = substr($result['name'], strlen($node['table_name']) + 1);
        }

        return $result;
    }
}
