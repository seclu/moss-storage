<?php
namespace moss\storage\query;

use moss\storage\driver\DriverInterface;
use moss\storage\builder\QueryInterface as BuilderInterface;
use moss\storage\model\ModelInterface;

/**
 * Query representation
 *
 * @package moss Storage
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 */
interface QueryInterface
{
    // Entity operation types
    const OPERATION_COUNT = 'count';
    const OPERATION_READ_ONE = 'readOne';
    const OPERATION_READ = 'read';
    const OPERATION_WRITE = 'write';
    const OPERATION_INSERT = 'insert';
    const OPERATION_UPDATE = 'update';
    const OPERATION_DELETE = 'delete';
    const OPERATION_CLEAR = 'clear';

    /**
     * Returns driver instance
     *
     * @return DriverInterface
     */
    public function driver();

    /**
     * Returns builder instance
     *
     * @return BuilderInterface
     */
    public function builder();

    /**
     * Returns model instance
     *
     * @return ModelInterface
     */
    public function model();

    /**
     * Sets query operation
     *
     * @param string        $operation
     * @param string|object $entity
     *
     * @return $this
     */
    public function operation($operation, $entity);

    /**
     * Sets field names which will be read
     *
     * @param array $fields
     *
     * @return $this
     */
    public function fields($fields = array());

    /**
     * Adds aggregate method to query
     *
     * @param string $method
     * @param string $field
     * @param string $alias
     *
     * @return $this
     */
    public function aggregate($method, $field, $alias = null);

    /**
     * Adds grouping to query
     *
     * @param string $field
     *
     * @return $this
     */
    public function group($field);

    /**
     * Sets field names which values will be written
     *
     * @param array $values
     *
     * @return $this
     */
    public function values($values = array());

    /**
     * Adds join to query
     *
     * @param string $type
     * @param string $entity
     *
     * @return $this
     */
    public function join($type, $entity);

    /**
     * Adds where condition to builder
     *
     * @param mixed  $field
     * @param mixed  $value
     * @param string $comparison
     * @param string $logical
     *
     * @return $this
     */
    public function where($field, $value, $comparison = BuilderInterface::COMPARISON_EQUAL, $logical = BuilderInterface::LOGICAL_AND);

    /**
     * Adds having condition to builder
     *
     * @param mixed  $field
     * @param mixed  $value
     * @param string $comparison
     * @param string $logical
     *
     * @return $this
     */
    public function having($field, $value, $comparison = BuilderInterface::COMPARISON_EQUAL, $logical = BuilderInterface::LOGICAL_AND);

    /**
     * Adds sorting to query
     *
     * @param string       $field
     * @param string|array $order
     *
     * @return $this
     */
    public function order($field, $order = BuilderInterface::ORDER_DESC);

    /**
     * Sets limits to query
     *
     * @param int      $limit
     * @param null|int $offset
     *
     * @return $this
     */
    public function limit($limit, $offset = null);

    /**
     * Adds relation to query
     *
     * @param string $relation
     * @param bool   $transparent
     *
     * @return $this
     */
    public function relation($relation, $transparent = false);

    /**
     * Executes query
     * After execution query is reset
     *
     * @return mixed|null|void
     */
    public function execute();

    /**
     * Previews query
     * Write operation is treated as update
     *
     * @return string
     */
    public function queryString();

    /**
     * Resets adapter
     *
     * @return $this
     */
    public function reset();
}