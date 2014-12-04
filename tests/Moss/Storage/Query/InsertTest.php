<?php

namespace Moss\Storage\Query;

use Moss\Storage\Builder\MySQL\QueryBuilder as Builder;
use Moss\Storage\Model\Model;
use Moss\Storage\Model\ModelBag;
use Moss\Storage\Model\Definition\Field\Integer;
use Moss\Storage\Model\Definition\Field\String;
use Moss\Storage\Model\Definition\Index\Primary;
use Moss\Storage\Model\Definition\Relation\One;

class InsertTest extends \PHPUnit_Framework_TestCase
{
    private $testEntity;
    private $insertObject;

    protected function setUp()
    {
        $this->entity = new \stdClass();
        $this->entity->id = 1;
        $this->entity->text = 'foobar';

        $this->insertObject = new Insert($this->mockDriver(), $this->mockBuilder(), $this->mockModelBag());
    }

    public function testWith()
    {

    }

    public function testRelation()
    {

    }

    public function testValue()
    {

    }

    public function testValues()
    {

    }

    public function testQueryString()
    {

    }

    protected function mockDriver($affectedRows = 0)
    {
        $driver = $this->getMock('\Moss\Storage\Driver\DriverInterface');

        $driver->expects($this->any())
            ->method('prepare')
            ->will($this->returnSelf());

        $driver->expects($this->any())
            ->method('execute')
            ->will($this->returnSelf());

        $driver->expects($this->any())
            ->method('store')
            ->will($this->returnArgument(0));

        $driver->expects($this->any())
            ->method('cast')
            ->will($this->returnArgument(0));

        $driver->expects($this->any())
            ->method('affectedRows')
            ->will($this->returnValue($affectedRows));

        return $driver;
    }

    protected function mockBuilder()
    {
        $builder = new Builder();

        return $builder;
    }

    protected function mockModelBag($relType = 'one', $localValues = array(), $foreignValues= array())
    {
        $table = new Model(
            '\stdClass',
            'test_table',
            array(
                new Integer('id', array('auto_increment')),
                new String('text', array('length' => '128', 'null')),
            ),
            array(
                new Primary(array('id')),
            ),
            array(
                $this->mockRelation($relType, $localValues, $foreignValues)
            )
        );

        $other = new Model(
            '\altClass',
            'test_other',
            array(
                new Integer('id', array('auto_increment')),
                new String('text', array('length' => '128', 'null')),
            ),
            array(
                new Primary(array('id')),
            )
        );

        $mediator = new Model(
            null,
            'test_mediator',
            array(
                new Integer('in'),
                new Integer('out'),
            ),
            array(
                new Primary(array('in', 'out')),
            )
        );

        $bag = new ModelBag();
        $bag->set($table, 'table');
        $bag->set($other, 'other');
        $bag->set($mediator, 'mediator');

        return $bag;
    }

    protected function mockRelation($relType, $localValues = array(), $foreignValues= array())
    {
        switch ($relType) {
            case 'one':
            default:
                $relation= new One('\altClass', array('id' => 'id'), 'other');
                break;
            case 'many':
                $relation = new Many('\altClass', array('id' => 'id'), 'other');
                break;
            case 'oneTrough':
                $relation = new OneTrough('\altClass', array('id' => 'in_id'), array('out_id' => 'id'), 'mediator', 'other');
                break;
            case 'manyTrough':
                $relation = new ManyTrough('\altClass', array('id' => 'in_id'), array('out_id' => 'id'), 'mediator', 'other');
                break;
        }

        if($localValues) {
            $relation->localValues($localValues);
        }

        if($foreignValues) {
            $relation->foreignValues($foreignValues);
        }

        return $relation;
    }
}
 