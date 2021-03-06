<?php

/*
 * This file is part of the Storage package
 *
 * (c) Michal Wachowski <wachowski.michal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Moss\Storage\Model\Definition\Relation;

/**
 * Many to many trough mediator table (with pivot table)
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
class ManyTrough extends Relation
{
    /**
     * @param string      $entity
     * @param array       $in
     * @param array       $out
     * @param null|string $mediator
     * @param null|string $container
     */
    public function __construct($entity, array $in, array $out, $mediator, $container = null)
    {
        $this->entity = $entity ? ltrim($entity, '\\') : null;
        $this->type = 'manyTrough';
        $this->container = $this->containerName($container);

        $this->mediator = $mediator ? ltrim($mediator, '\\') : $mediator;

        $this->assertTroughKeys($in, $out);
        $this->assignKeys($in, $this->in);
        $this->assignKeys($out, $this->out);
        $this->keys = array_combine(array_keys($this->in), array_values($this->out));
    }
}
