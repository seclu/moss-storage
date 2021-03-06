<?php
namespace Moss\Storage\Model;

class ModelBagTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider nameProvider
     */
    public function testGetSet($name, $expected)
    {
        $bag = new ModelBag();
        $bag->set($this->mockModel('\stdClass', 'table_std'), 'std');
        $bag->set($this->mockModel('\splFileObject', 'table_spl'), 'spl');
        $bag->set($this->mockModel('\foo\bar\Yada', 'table_yada'), 'yada');

        $model = $bag->get($name);
        $this->assertEquals($model->entity(), $expected);
    }

    /**
     * @expectedException \Moss\Storage\Model\ModelException
     * @expectedExceptionMessage does not exists
     * @dataProvider             nameProvider
     */
    public function testGetUndefined($name)
    {
        $bag = new ModelBag();
        $bag->get($name);
    }

    /**
     * @dataProvider nameProvider
     */
    public function testHas($name)
    {
        $bag = new ModelBag();
        $bag->set($this->mockModel('\stdClass', 'table_std'), 'std');
        $bag->set($this->mockModel('\splFileObject', 'table_spl'), 'spl');
        $bag->set($this->mockModel('\foo\bar\Yada', 'table_yada'), 'yada');

        $this->assertTrue($bag->has($name));
    }

    public function nameProvider()
    {
        return array(
            array('\stdClass', 'stdClass'),
            array('\splFileObject', 'splFileObject'),
            array('\foo\bar\Yada', 'foo\bar\Yada'),

            array('std', 'stdClass'),
            array('spl', 'splFileObject'),
            array('yada', 'foo\bar\Yada'),

            array('table_std', 'stdClass'),
            array('table_spl', 'splFileObject'),
            array('table_yada', 'foo\bar\Yada')
        );
    }

    protected function mockModel($entity, $table, $alias = null)
    {
        $mock = $this->getMock('Moss\Storage\Model\ModelInterface');

        $mock->expects($this->any())
            ->method('entity')
            ->will($this->returnValue(ltrim($entity, '\\')));

        $mock->expects($this->any())
            ->method('alias')
            ->will($this->returnCallback(function ($arg = null) use (&$alias) { return $alias = $arg; }));

        $mock->expects($this->any())
            ->method('table')
            ->will($this->returnValue($table));

        return $mock;
    }

}
 