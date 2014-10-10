<?php

/**
 * Flintstone - A key/value database store using flat files for PHP
 * Copyright (c) 2014 Jason M
 */

namespace Flintstone\Formatter;

/**
 * Interface for formatters
 */
interface FormatterInterface
{
    /**
     * Encode data into a string
     *
     * @param mixed $data the data to encode
     *
     * @return string the encoded string
     */
    public function encode($data);

    /**
     * Decode a string into data
     *
     * @param string $data the encoded string
     *
     * @return mixed the decoded data
     */
    public function decode($data);
}
