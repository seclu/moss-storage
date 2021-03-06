<?php

/*
 * This file is part of the Storage package
 *
 * (c) Michal Wachowski <wachowski.michal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Moss\Storage\Model;

/**
 * Registry containing models
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
class ModelBag
{
    /**
     * @var array|ModelInterface
     */
    protected $collection = array();

    /**
     * @var array|ModelInterface
     */
    protected $byAlias = array();

    /**
     * @var array|ModelInterface
     */
    protected $byEntity = array();

    /**
     * @var array|ModelInterface
     */
    protected $byTable = array();

    /**
     * Construct
     *
     * @param array $collection
     */
    public function __construct($collection = array())
    {
        $this->all($collection);
    }

    /**
     * Retrieves offset value
     *
     * @param string $alias
     *
     * @return ModelInterface
     * @throws ModelException
     */
    public function get($alias)
    {
        $alias = ltrim($alias, '\\');

        if (isset($this->byAlias[$alias])) {
            return $this->byAlias[$alias];
        }

        if (isset($this->byEntity[$alias])) {
            return $this->byEntity[$alias];
        }

        if (isset($this->byTable[$alias])) {
            return $this->byTable[$alias];
        }

        throw new ModelException(sprintf('Model for entity "%s" does not exists', $alias));
    }

    /**
     * Sets value to offset
     *
     * @param ModelInterface $model
     * @param string         $alias
     *
     * @return $this
     */
    public function set(ModelInterface $model, $alias = null)
    {
        $hash = spl_object_hash($model);

        $this->collection[$hash] = & $model;

        $key = preg_replace('/_?[^\w\d]+/i', '_', $model->table());

        $alias = $model->alias($alias ? $alias : $key);
        $this->byAlias[$alias] = & $this->collection[$hash];

        $entity = $model->entity() ? ltrim($model->entity(), '\\') : $key;
        $this->byEntity[$entity] = & $this->collection[$hash];

        $entity = $model->table();
        $this->byTable[$entity] = & $this->collection[$hash];

        return $this;
    }

    /**
     * Returns true if offset exists in bag
     *
     * @param string $alias
     *
     * @return bool
     */
    public function has($alias)
    {
        $alias = ltrim($alias, '\\');

        if (isset($this->byAlias[$alias]) || isset($this->byEntity[$alias]) || isset($this->byTable[$alias])) {
            return true;
        }

        return false;
    }

    /**
     * Returns all options
     * If array passed, becomes bag content
     *
     * @param array $array overwrites values
     *
     * @return array|ModelInterface[]
     */
    public function all($array = array())
    {
        if (!empty($array)) {
            foreach ($array as $key => $model) {
                $this->set($model, is_numeric($key) ? null : $key);
            }
        }

        return $this->byAlias;
    }
}
