<?php
namespace Moss\Storage\Model\Definition\Field;

class IntegerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @dataProvider tableProvider
     */
    public function testTable($table, $expected)
    {
        $field = new Integer('foo');
        $this->assertEquals($expected, $field->table($table));
    }

    public function tableProvider()
    {
        return array(
            array(null, null),
            array('foo', 'foo'),
            array('bar', 'bar'),
            array('yada', 'yada'),
        );
    }

    public function testName()
    {
        $field = new Integer('foo');
        $this->assertEquals('foo', $field->name());
    }

    public function testType()
    {
        $field = new Integer('foo');
        $this->assertEquals('integer', $field->type());
    }

    /**
     * @dataProvider mappingProvider
     */
    public function testMapping($mapping, $expected)
    {
        $field = new Integer('foo', array('length' => 10), $mapping);
        $this->assertEquals($expected, $field->mapping());
    }

    public function mappingProvider()
    {
        return array(
            array(null, 'foo'),
            array('', 'foo'),
            array('foo', 'foo'),
            array('bar', 'bar'),
            array('yada', 'yada'),
        );
    }

    public function testNonExistentAttribute()
    {
        $field = new Integer('foo', array('length' => 128), 'bar');
        $this->assertNull($field->attribute('NonExistentAttribute'));
    }

    /**
     * @dataProvider attributeValueProvider
     */
    public function testAttribute($attribute, $key, $value = true)
    {
        $field = new Integer('foo', $attribute, 'bar');
        $this->assertEquals($value, $field->attribute($key));
    }

    public function attributeValueProvider()
    {
        return array(
            array(array('length' => 10), 'length', 10),
            array(array('null'), 'null'),
            array(array('auto_increment'), 'auto_increment', true),
            array(array('default' => 123), 'default', 123)
        );
    }

    /**
     * @dataProvider attributeArrayProvider
     */
    public function testAttributes($attribute, $expected)
    {
        $field = new Integer('foo', $attribute, 'bar');
        $this->assertEquals($expected, $field->attributes());
    }

    public function attributeArrayProvider()
    {
        return array(
            array(array('length' => 10), array('length' => 10)),
            array(array('null'), array('length' => 11, 'null' => true)),
            array(array('auto_increment'), array('length' => 11, 'auto_increment' => true)),
            array(array('default' => 123), array('length' => 11, 'null' => true, 'default' => 123))
        );
    }

    /**
     * @expectedException \Moss\Storage\Model\Definition\DefinitionException
     * @expectedExceptionMessage Forbidden attribute
     * @dataProvider             forbiddenAttributeProvider
     */
    public function testForbiddenAttributes($attribute)
    {
        new Integer('foo', array($attribute), 'bar');
    }

    public function forbiddenAttributeProvider()
    {
        return array(
            array('precision'),
        );
    }
}
