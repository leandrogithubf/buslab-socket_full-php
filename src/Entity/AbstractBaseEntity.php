<?php

namespace App\Entity;

/**
 * Enables for all classes default getters and setters with magic method for all
 * attributes, but id (as doctrines advises to not have a setter for this one).
 */
abstract class AbstractBaseEntity
{
    /**
     * Overloading methods to check if the user is trying to get or set an
     * attribute. If a method is implemented, the __call won't be called.
     *
     * @param string $name      The name of the method beign called
     * @param array  $arguments The array of arguments being passed
     *
     * @return mixed Might be the attribute value or the instance
     */
    public function __call(string $name, array $arguments)
    {
        if (property_exists(get_called_class(), $name)) {
            return $this->{$name};
        }

        if (0 === strpos($name, 'get')) {
            $property = lcfirst(strtr($name, ['get' => '']));
            if (property_exists(get_called_class(), $property)) {
                return $this->{$property};
            }

            trigger_error('Call to undefined method ' . get_called_class() . '::' . $name . '()', E_USER_ERROR);
        }

        if (0 === strpos($name, 'set')) {
            $property = lcfirst(strtr($name, ['set' => '']));
            if (property_exists(get_called_class(), $property) && 'id' !== $property) {
                $this->{$property} = $arguments[0];

                return $this;
            }

            trigger_error('Call to undefined method ' . get_called_class() . '::' . $name . '()', E_USER_ERROR);
        }

        trigger_error('Call to undefined method ' . get_called_class() . '::' . $name . '()', E_USER_ERROR);
    }

    /**
     * Overloading methods to check if the user is trying to get a valid
     * attribute.
     *
     * @param string $name The name of the property beign called
     *
     * @return mixed Depends on the attribute value
     */
    public function __get($name)
    {
        $method = 'get' . ucwords($name);

        return $this->{$method}();
    }

    /**
     * Overloading methods to check if the user is trying to set a valid
     * attribute.
     *
     * @param string $name  The name of the property beign called
     * @param mixed  $value
     *
     * @return mixed Depends on the attribute value
     */
    public function __set($name, $value)
    {
        $method = 'set' . ucwords($name);

        return $this->{$method}($value);
    }
}
