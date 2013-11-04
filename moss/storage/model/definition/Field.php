<?php
namespace moss\storage\model\definition;

use moss\storage\model\ModelInterface;

class Field implements FieldInterface
{
    private $name;
    private $type;
    private $mapping;
    private $attributes;

    public function __construct($field, $type = ModelInterface::FIELD_STRING, $attributes = array(), $mapping = null)
    {
        if (!in_array($type, array(ModelInterface::FIELD_BOOLEAN, ModelInterface::FIELD_INTEGER, ModelInterface::FIELD_DECIMAL, ModelInterface::FIELD_STRING, ModelInterface::FIELD_DATETIME, ModelInterface::FIELD_SERIAL))) {
            throw new DefinitionException(sprintf('Invalid type "%s" for field "%s"', $type, $this->name));
        }

        $this->name = $field;
        $this->type = $type;
        $this->mapping = $mapping;

        foreach ($attributes as $key => $value) {
            if (!is_numeric($key)) {
                continue;
            }

            unset($attributes[$key]);
            $attributes[$value] = true;
        }

        $this->attributes = $attributes;
    }

    /**
     * Returns relation name in entity
     *
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * Returns relation type
     *
     * @return string
     */
    public function type()
    {
        return $this->type;
    }

    /**
     * Returns field container mapping or null when no mapping
     *
     * @return null|string
     */
    public function mapping()
    {
        return $this->mapping ? $this->mapping : $this->name;
    }

    /**
     * Returns attribute value or null if not set
     *
     * @param string $attribute
     *
     * @return mixed
     */
    public function attribute($attribute)
    {
        if (!array_key_exists($attribute, $this->attributes)) {
            return null;
        }

        return $this->attributes[$attribute];
    }

    /**
     * Returns array containing field attributes
     *
     * @return array
     */
    public function attributes()
    {
        return $this->attributes;
    }
}
