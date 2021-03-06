<?php
namespace Moss\Storage\Builder\PgSQL;

class SchemaTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \Moss\Storage\Builder\BuilderException
     * @expectedExceptionMessage Missing table name
     */
    public function testMissingTable()
    {
        $schema = new SchemaBuilder(null);
        $schema->build();
    }

    public function testTable()
    {
        $schema = new SchemaBuilder('table', 'add');
        $schema->column('foo');
        $this->assertEquals('ALTER TABLE table ADD foo TEXT NOT NULL', $schema->build());
    }

    /**
     * @expectedException \Moss\Storage\Builder\BuilderException
     * @expectedExceptionMessage Missing table name
     */
    public function testTableWithEmptyString()
    {
        $query = new SchemaBuilder('table', 'check');
        $query->reset()
            ->table('');
    }

    /**
     * @expectedException \Moss\Storage\Builder\BuilderException
     * @expectedExceptionMessage Unknown operation
     */
    public function testInvalidOperation()
    {
        new SchemaBuilder('table', 'foo');
    }

    /**
     * @dataProvider shortOperationProvider
     */
    public function testOperation($operation, $expected)
    {
        $schema = new SchemaBuilder('table', 'check');
        $schema
            ->operation($operation)
            ->column('foo')
            ->index('idx', array('foo'), 'index');
        $this->assertEquals($expected, $schema->build());
    }

    /**
     * @dataProvider shortOperationProvider
     */
    public function testOperationAliases($operation, $expected)
    {
        $schema = new SchemaBuilder('foo', 'check');
        $schema->{$operation}('table')
            ->column('foo')
            ->index('idx', array('foo'), 'index');
        $this->assertEquals($expected, $schema->build());
    }

    public function shortOperationProvider()
    {
        return array(
            array(
                'check',
                'SELECT table_name FROM information_schema.tables WHERE table_name = \'table\''
            ),
            array(
                'info',
                'SELECT
	c.ordinal_position AS pos,
	c.table_schema AS table_schema,
	c.table_name AS table_name,
	c.column_name AS column_name,
	c.data_type AS column_type,
	CASE WHEN c.character_maximum_length IS NOT NULL THEN c.character_maximum_length ELSE c.numeric_precision END AS column_length,
	c.numeric_scale AS column_precision,
	c.is_nullable AS column_nullable,
	CASE WHEN POSITION(\'nextval\' IN c.column_default) > 0 THEN \'YES\' ELSE \'NO\' END AS column_auto_increment,
	CASE WHEN POSITION(\'nextval\' IN c.column_default) > 0 THEN NULL ELSE c.column_default END AS column_default,
	CASE WHEN u.constraint_name IS NULL AND ic.relname IS NOT NULL THEN ic.relname ELSE u.constraint_name END AS index_name,
	CASE WHEN t.constraint_type IS NULL AND ic.relname IS NOT NULL THEN \'INDEX\' ELSE t.constraint_type END AS index_type,
	u.ordinal_position AS index_pos,
	y.table_schema AS ref_schema,
	y.table_name AS ref_table,
	y.column_name AS ref_column
FROM information_schema.columns AS c
	LEFT JOIN information_schema.key_column_usage AS u ON u.table_schema = c.table_schema AND u.table_name = c.table_name AND u.column_name = c.column_name
	LEFT JOIN information_schema.table_constraints AS t ON u.constraint_schema = t.constraint_schema AND u.constraint_name = t.constraint_name AND constraint_type != \'CHECK\'

	LEFT JOIN pg_catalog.pg_class AS it ON it.relname = c.table_name
	LEFT JOIN pg_catalog.pg_attribute AS ia ON ia.attrelid = it.oid AND ia.attname = c.column_name
	LEFT JOIN pg_catalog.pg_index AS ii ON ii.indrelid = it.oid AND ia.attnum = ANY (ii.indkey::INT[])
	LEFT JOIN pg_catalog.pg_class AS ic ON ic.oid = ii.indexrelid

	LEFT JOIN information_schema.referential_constraints AS f ON f.constraint_schema = t.constraint_schema AND f.constraint_name = t.constraint_name
	LEFT JOIN information_schema.key_column_usage AS x ON x.constraint_name = f.constraint_name
	LEFT JOIN information_schema.key_column_usage AS y ON y.ordinal_position = x.position_in_unique_constraint AND y.constraint_name = f.unique_constraint_name
WHERE c.table_name = \'table\'
ORDER BY pos'
            ),
            array(
                'create',
                'CREATE TABLE table ( foo TEXT NOT NULL ) ; CREATE INDEX table_idx ON table ( foo )'
            ),
            array(
                'add',
                'ALTER TABLE table ADD foo TEXT NOT NULL; CREATE INDEX table_idx ON table ( foo )'
            ),
            array(
                'change',
                'ALTER TABLE table ALTER foo TYPE TEXT; ALTER TABLE table ALTER foo SET NOT NULL'
            ),
            array(
                'remove',
                'ALTER TABLE table DROP COLUMN foo; DROP INDEX table_idx'
            ),
            array(
                'drop',
                'DROP TABLE IF EXISTS table'
            )
        );
    }

    /**
     * @dataProvider columnProvider
     */
    public function testCreateColumn($actual, $expected)
    {
        $schema = new SchemaBuilder('table', 'create');
        $schema->column('foo', $actual);
        $this->assertEquals('CREATE TABLE table ( foo ' . $expected . ' NOT NULL )', $schema->build());
    }

    /**
     * @dataProvider columnProvider
     */
    public function testAddColumn($actual, $expected)
    {
        $schema = new SchemaBuilder('table', 'add');
        $schema->column('foo', $actual);
        $this->assertEquals('ALTER TABLE table ADD foo ' . $expected . ' NOT NULL', $schema->build());
    }

    /**
     * @dataProvider columnProvider
     */
    public function testChangeColumn($actual, $expected)
    {
        $schema = new SchemaBuilder('table', 'change');
        $schema->column('foo', $actual);
        $this->assertEquals('ALTER TABLE table ALTER foo TYPE ' . $expected . '; ALTER TABLE table ALTER foo SET NOT NULL', $schema->build());
    }

    /**
     * @dataProvider columnProvider
     */
    public function testRemoveColumn($actual, $expected)
    {
        $schema = new SchemaBuilder('table', 'remove');
        $schema->column('foo', $actual);
        $this->assertEquals('ALTER TABLE table DROP COLUMN foo', $schema->build());
    }

    public function columnProvider()
    {
        return array(
            array(
                'boolean',
                'BOOLEAN'
            ),
            array(
                'integer',
                'INTEGER',
            ),
            array(
                'decimal',
                'NUMERIC(11,4)',
            ),
            array(
                'string',
                'TEXT'
            ),
            array(
                'datetime',
                'TIMESTAMP WITHOUT TIME ZONE'
            ),
            array(
                'serial',
                'BYTEA'
            ),

        );
    }

    /**
     * @dataProvider attributeProvider
     */
    public function testColumnAttributes($type, $attributes, $expected)
    {
        $schema = new SchemaBuilder('table', 'add');
        $schema->column('foo', $type, $attributes);
        $this->assertEquals('ALTER TABLE table ADD foo ' . $expected . '', $schema->build());
    }

    public function attributeProvider()
    {
        return array(
            array(
                'integer',
                array(),
                'INTEGER NOT NULL'
            ),
            array(
                'integer',
                array('default' => 1),
                'INTEGER DEFAULT 1'
            ),
            array(
                'integer',
                array('auto_increment'),
                'SERIAL NOT NULL'
            ),
            array(
                'integer',
                array('null'),
                'INTEGER DEFAULT NULL'
            ),
            array(
                'integer',
                array('length' => 6),
                'INTEGER NOT NULL'
            ),
            array(
                'string',
                array('length' => null),
                'CHARACTER VARYING NOT NULL'
            ),
            array(
                'string',
                array('length' => 2048),
                'TEXT NOT NULL'
            ),
            array(
                'string',
                array('length' => 512),
                'CHARACTER VARYING NOT NULL'
            ),
            array(
                'string',
                array('length' => 10),
                'CHARACTER VARYING NOT NULL'
            ),
            array(
                'decimal',
                array('precision' => 2),
                'NUMERIC(11,2) NOT NULL'
            ),
            array(
                'decimal',
                array('length' => 6, 'precision' => 2),
                'NUMERIC(6,2) NOT NULL'
            ),
        );
    }

    /**
     * @dataProvider createIndexProvider
     */
    public function testCreateIndex($type, $fields, $table, $expected)
    {
        $schema = new SchemaBuilder('table', 'create');
        $schema->column('foo', 'integer')
            ->index('foo', $fields, $type, $table);
        $this->assertEquals($expected, $schema->build());
    }

    /**
     * @dataProvider createIndexProvider
     */
    public function testCreateIndexAliases($type, $fields, $table, $expected)
    {
        $schema = new SchemaBuilder('table', 'create');
        $schema->column('foo', 'integer');
        switch ($type) {
            case 'primary':
                $schema->primary($fields);
                break;
            case 'unique':
                $schema->unique('foo', $fields);
                break;
            case 'index':
                $schema->index('foo', $fields);
                break;
            case 'foreign':
                $schema->foreign('foo', $fields, $table);
                break;
        }
        $this->assertEquals($expected, $schema->build());
    }

    public function createIndexProvider()
    {
        return array(
            array(
                'primary',
                array('foo'),
                null,
                'CREATE TABLE table ( foo INTEGER NOT NULL, CONSTRAINT table_pk PRIMARY KEY (foo) )'
            ),
            array(
                'unique',
                array('foo'),
                null,
                'CREATE TABLE table ( foo INTEGER NOT NULL, CONSTRAINT table_foo UNIQUE (foo) )'
            ),
            array(
                'index',
                array('foo'),
                null,
                'CREATE TABLE table ( foo INTEGER NOT NULL ) ; CREATE INDEX table_foo ON table ( foo )'
            ),
            array(
                'foreign',
                array('foo' => 'bar'),
                'yada',
                'CREATE TABLE table ( foo INTEGER NOT NULL, CONSTRAINT table_foo FOREIGN KEY (foo) REFERENCES yada (bar) MATCH SIMPLE ON UPDATE CASCADE ON DELETE RESTRICT )'
            ),
        );
    }

    /**
     * @dataProvider indexAlterProvider
     */
    public function testAddIndex($type, $fields, $table, $expected)
    {
        $schema = new SchemaBuilder('table', 'add');
        $schema->index('foo', $fields, $type, $table);
        $this->assertEquals($expected, $schema->build());
    }

    /**
     * @dataProvider indexAlterProvider
     */
    public function testAddIndexAlias($type, $fields, $table, $expected)
    {
        $schema = new SchemaBuilder('table', 'add');
        switch ($type) {
            case 'primary':
                $schema->primary($fields);
                break;
            case 'unique':
                $schema->unique('foo', $fields);
                break;
            case 'index':
                $schema->index('foo', $fields);
                break;
            case 'foreign':
                $schema->foreign('foo', $fields, $table);
                break;
        }
        $this->assertEquals($expected, $schema->build());
    }


    public function indexAlterProvider()
    {
        return array(
            array(
                'primary',
                array('foo'),
                null,
                'ALTER TABLE table ADD CONSTRAINT table_pk PRIMARY KEY (foo)'
            ),
            array(
                'unique',
                array('foo'),
                null,
                'ALTER TABLE table ADD CONSTRAINT table_foo UNIQUE (foo)'
            ),
            array(
                'index',
                array('foo'),
                null,
                'CREATE INDEX table_foo ON table ( foo )'
            ),
            array(
                'foreign',
                array('foo' => 'bar'),
                'yada',
                'ALTER TABLE table ADD CONSTRAINT table_foo FOREIGN KEY (foo) REFERENCES yada (bar) MATCH SIMPLE ON UPDATE CASCADE ON DELETE RESTRICT'
            ),
        );
    }

    /**
     * @dataProvider dropIndexProvider
     */
    public function testRemoveIndex($type, $fields, $table, $expected)
    {
        $schema = new SchemaBuilder('table', 'remove');
        $schema->index('foo', $fields, $type, $table);
        $this->assertEquals($expected, $schema->build());
    }

    /**
     * @dataProvider dropIndexProvider
     */
    public function testRemoveIndexAlias($type, $fields, $table, $expected)
    {
        $schema = new SchemaBuilder('table', 'remove');
        switch ($type) {
            case 'primary':
                $schema->primary($fields);
                break;
            case 'unique':
                $schema->unique('foo', $fields);
                break;
            case 'index':
                $schema->index('foo', $fields);
                break;
            case 'foreign':
                $schema->foreign('foo', $fields, $table);
                break;
        }
        $this->assertEquals($expected, $schema->build());
    }

    public function dropIndexProvider()
    {
        return array(
            array(
                'primary',
                array('foo'),
                null,
                'ALTER TABLE table DROP PRIMARY KEY'
            ),
            array(
                'unique',
                array('foo'),
                null,
                'ALTER TABLE table DROP CONSTRAINT table_foo',
            ),
            array(
                'index',
                array('foo'),
                null,
                'DROP INDEX table_foo',
            ),
            array(
                'foreign',
                array('bar'),
                'yada',
                'ALTER TABLE table DROP CONSTRAINT table_foo',
            ),
        );
    }

    /**
     * @dataProvider parseProvider
     */
    public function testParse($array, $fields, $indexes = array())
    {
        $expected = array(
            'table' => 'table',
            'fields' => $fields,
            'indexes' => $indexes,
        );

        $schema = new SchemaBuilder();
        $result = $schema->parse($array);
        $this->assertEquals($expected, $result);
    }

    public function parseProvider()
    {
        return array(
            array(
                array($this->createInputColumn('column', 'boolean')),
                array($this->createOutputColumn('column', 'boolean')),
            ),
            array(
                array($this->createInputColumn('column', 'boolean', array('default' => 0))),
                array($this->createOutputColumn('column', 'boolean', array('default' => 0))),
            ),
            array(
                array($this->createInputColumn('column', 'integer')),
                array($this->createOutputColumn('column', 'integer')),
            ),
            array(
                array($this->createInputColumn('column', 'integer', array('length' => 5))),
                array($this->createOutputColumn('column', 'integer', array('length' => 5))),
            ),
            array(
                array($this->createInputColumn('column', 'integer', array('auto_increment' => 'YES'))),
                array($this->createOutputColumn('column', 'integer', array('auto_increment' => true))),
            ),
            array(
                array($this->createInputColumn('column', 'integer', array('default' => 10))),
                array($this->createOutputColumn('column', 'integer', array('default' => 10))),
            ),
            array(
                array($this->createInputColumn('column', 'integer', array('null' => 'YES'))),
                array($this->createOutputColumn('column', 'integer', array('null' => true))),
            ),
            array(
                array($this->createInputColumn('column', 'numeric')),
                array($this->createOutputColumn('column', 'decimal')),
            ),
            array(
                array($this->createInputColumn('column', 'numeric', array('length' => 4, 'precision' => 2))),
                array($this->createOutputColumn('column', 'decimal', array('length' => 4, 'precision' => 2))),
            ),
            array(
                array($this->createInputColumn('column', 'numeric', array('default' => 10.2))),
                array($this->createOutputColumn('column', 'decimal', array('default' => 10.2))),
            ),
            array(
                array($this->createInputColumn('column', 'numeric', array('null' => 'YES'))),
                array($this->createOutputColumn('column', 'decimal', array('null' => true))),
            ),
            array(
                array($this->createInputColumn('column', 'text')),
                array($this->createOutputColumn('column', 'string')),
            ),
            array(
                array($this->createInputColumn('column', 'char', array('length' => 100))),
                array($this->createOutputColumn('column', 'string', array('length' => 100))),
            ),
            array(
                array($this->createInputColumn('column', 'varchar', array('length' => 300))),
                array($this->createOutputColumn('column', 'string', array('length' => 300))),
            ),
            array(
                array($this->createInputColumn('column', 'text', array('length' => 2000))),
                array($this->createOutputColumn('column', 'string', array('length' => 2000))),
            ),
            array(
                array($this->createInputColumn('column', 'text', array('null' => 'YES'))),
                array($this->createOutputColumn('column', 'string', array('null' => true))),
            ),
            array(
                array($this->createInputColumn('column', 'timestamp')),
                array($this->createOutputColumn('column', 'datetime')),
            ),
            array(
                array($this->createInputColumn('column', 'timestamp', array('null' => 'YES'))),
                array($this->createOutputColumn('column', 'datetime', array('null' => true))),
            ),
            array(
                array($this->createInputColumn('column', 'bytea')),
                array($this->createOutputColumn('column', 'serial')),
            ),
            array(
                array($this->createInputColumn('column', 'bytea', array('null' => 'YES'))),
                array($this->createOutputColumn('column', 'serial', array('null' => true))),
            ),
            array(
                array($this->createInputColumn('column', 'integer', array(), array('name' => 'primary', 'type' => 'primary', 'pos' => 1))),
                array($this->createOutputColumn('column', 'integer', array('length' => 0))),
                array($this->createOutputIndex('primary', 'primary', array('column')))
            ),
            array(
                array($this->createInputColumn('column', 'integer', array(), array('name' => 'idx', 'type' => 'unique', 'pos' => 1))),
                array($this->createOutputColumn('column', 'integer', array('length' => 0))),
                array($this->createOutputIndex('idx', 'unique', array('column')))
            ),
            array(
                array($this->createInputColumn('column', 'integer', array(), array('name' => 'idx', 'type' => 'index', 'pos' => 1))),
                array($this->createOutputColumn('column', 'integer', array('length' => 0))),
                array($this->createOutputIndex('idx', 'index', array('column')))
            ),
            array(
                array($this->createInputColumn('column', 'integer', array(), array('name' => 'idx', 'type' => 'foreign', 'pos' => 1), array('table' => 'other', 'column' => 'ref'))),
                array($this->createOutputColumn('column', 'integer', array('length' => 0))),
                array($this->createOutputIndex('idx', 'foreign', array('column'), array('table' => 'other', 'fields' => array('ref'))))
            ),
            array(
                array(
                    $this->createInputColumn('columnA', 'integer', array(), array('name' => 'idx', 'type' => 'foreign', 'pos' => 1), array('table' => 'other', 'column' => 'refA')),
                    $this->createInputColumn('columnB', 'integer', array(), array('name' => 'idx', 'type' => 'foreign', 'pos' => 2), array('table' => 'other', 'column' => 'refB'))
                ),
                array(
                    $this->createOutputColumn('columnA', 'integer', array('length' => 0)),
                    $this->createOutputColumn('columnB', 'integer', array('length' => 0))
                ),
                array(
                    $this->createOutputIndex('idx', 'foreign', array('columnA', 'columnB'), array('table' => 'other', 'fields' => array('refA', 'refB')))
                )
            ),
        );
    }

    protected function createInputColumn($name, $type, $attributes = array(), $index = array(), $ref = array())
    {
        return array(
            'pos' => 1,
            'table_schema' => 'test',
            'table_name' => 'table',
            'column_name' => $name,
            'column_type' => $type,
            'column_length' => $this->get($attributes, 'length'),
            'column_precision' => $this->get($attributes, 'precision', 0),
            'column_nullable' => $this->get($attributes, 'null', 'NO'),
            'column_auto_increment' => $this->get($attributes, 'auto_increment', 'NO'),
            'column_default' => $this->get($attributes, 'default', null),
            'index_name' => array_key_exists('name', $index) ? (array_key_exists('type', $index) && $index['type'] !== 'primary' ? 'table_' : null) . $index['name'] : null,
            'index_type' => $this->get($index, 'type', null),
            'index_pos' => $this->get($index, 'pos', null),
            'ref_schema' => $this->get($ref, 'schema', null),
            'ref_table' => $this->get($ref, 'table', null),
            'ref_column' => $this->get($ref, 'column', null),
        );
    }

    protected function createOutputColumn($name, $type, $attributes = array())
    {
        return array(
            'name' => $name,
            'type' => $type,
            'attributes' => array(
                'length' => $this->get($attributes, 'length'),
                'precision' => $this->get($attributes, 'precision', 0),
                'null' => $this->get($attributes, 'null', false),
                'auto_increment' => $this->get($attributes, 'auto_increment', false),
                'default' => $this->get($attributes, 'default', null),
            )
        );
    }

    protected function createOutputIndex($name, $type, array $fields, $ref = array())
    {
        return array(
            'name' => $name,
            'type' => $type,
            'fields' => $ref ? array_combine($fields, $this->get($ref, 'fields', array())) : $fields,
            'table' => $this->get($ref, 'table')
        );
    }

    protected function get($array, $offset, $default = null)
    {
        return array_key_exists($offset, $array) ? $array[$offset] : $default;
    }
}