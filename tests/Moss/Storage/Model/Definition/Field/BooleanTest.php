<?php
namespace Moss\Storage\Model\Definition\Field;

class BooleanTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @dataProvider tableProvider
     */
    public function testTable($table, $expected)
    {
        $field = new Boolean('foo');
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
        $field = new Boolean('foo');
        $this->assertEquals('foo', $field->name());
    }

    public function testType()
    {
        $field = new Boolean('foo');
        $this->assertEquals('boolean', $field->type());
    }

    /**
     * @dataProvider mappingProvider
     */
    public function testMapping($mapping, $expected)
    {
        $field = new Boolean('foo', array(), $mapping);
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
        $field = new Boolean('foo', array(), 'bar');
        $this->assertNull($field->attribute('NonExistentAttribute'));
    }

    /**
     * @dataProvider attributeValueProvider
     */
    public function testAttribute($attribute, $key, $value = true)
    {
        $field = new Boolean('foo', $attribute, 'bar');
        $this->assertEquals($value, $field->attribute($key));
    }

    public function attributeValueProvider()
    {
        return array(
            array(array('null'), 'null'),
            array(array('default' => 0), 'default', 0),
        );
    }

    /**
     * @dataProvider attributeArrayProvider
     */
    public function testAttributes($attribute, $expected = array())
    {
        $field = new Boolean('foo', $attribute, 'bar');
        $this->assertEquals($expected, $field->attributes());
    }

    public function attributeArrayProvider()
    {
        return array(
            array(array('null'), array('null' => true)),
            array(array('default' => 0), array('null' => true, 'default' => 0)),
        );
    }

    /**
     * @expectedException \Moss\Storage\Model\Definition\DefinitionException
     * @expectedExceptionMessage Forbidden attribute
     * @dataProvider forbiddenAttributeProvider
     */
    public function testForbiddenAttributes($attribute)
    {
        new Boolean('foo', array($attribute), 'bar');
    }

    public function forbiddenAttributeProvider()
    {
        return array(
            array('length'),
            array('precision'),
            array('auto_increment'),
        );
    }
}
