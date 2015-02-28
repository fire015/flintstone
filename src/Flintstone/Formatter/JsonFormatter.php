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
     * {@inheritdoc}
     */
    public function encode($data)
    {
        return json_encode($data);
    }

    /**
     * {@inheritdoc}
     */
    public function decode($data)
    {
        return json_decode($data, true);
    }
}
