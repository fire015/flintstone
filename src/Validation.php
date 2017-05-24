<?php

namespace Flintstone;

class Validation
{
    /**
     * Validate the key.
     *
     * @param string $key
     *
     * @throws Exception
     */
    public static function validateKey($key)
    {
        if (empty($key) || !preg_match('/^[\w-]+$/', $key)) {
            throw new Exception('Invalid characters in key');
        }
    }

    /**
     * Check the data type is valid.
     *
     * @param mixed $data
     *
     * @throws Exception
     */
    public static function validateData($data)
    {
        if (!is_null($data) && !is_string($data) && !is_int($data) && !is_float($data) && !is_array($data)) {
            throw new Exception('Invalid data type');
        }
    }

    /**
     * Check the database name is valid.
     *
     * @param string $name
     *
     * @throws Exception
     */
    public static function validateDatabaseName($name)
    {
        if (empty($name) || !preg_match('/^[\w-]+$/', $name)) {
            throw new Exception('Invalid characters in database name');
        }
    }
}
