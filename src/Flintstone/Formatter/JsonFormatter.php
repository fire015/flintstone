<?php

/**
 * Flintstone - A key/value database store using flat files for PHP
 * Copyright (c) 2014 Jason M
 */

namespace Flintstone\Formatter;

/**
 * Encodes/decodes data into JSON
 */
class JsonFormatter implements FormatterInterface
{
    /**
     * Encode data into a string
     *
     * @param mixed $data the data to encode
     *
     * @return string the encoded string
     */
    public function encode($data)
    {
        return json_encode($data);
    }

    /**
     * Decode a string into data
     *
     * @param string $data the encoded string
     *
     * @return mixed the decoded data
     */
    public function decode($data)
    {
        return json_decode($data, true);
    }
}
