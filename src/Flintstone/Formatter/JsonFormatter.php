<?php

/**
 * Flintstone - A key/value database store using flat files for PHP
 * Copyright (c) 2014 Jason M
 */

namespace Flintstone\Formatter;

use Flintstone\FlintstoneException;

/**
 * Encodes/decodes data into JSON
 */
class JsonFormatter implements FormatterInterface {

    /**
     * {@inheritdoc}
     */
    public function encode($data) {
        if (isset($data) && is_array($data) && $this->isAssocativeArray($data)) {
            throw new FlintstoneException('Associate arrays cannot be stored as JSON Values');
        }

        return json_encode($data);
    }

    /**
     * {@inheritdoc}
     */
    public function decode($data) {
        return json_decode($data);
    }

    /**
     * Determines if passed in array is an associative array.
     * 
     * @param array $arr Array you'd like to check
     * @return boolean If $arr is an associative array
     */
    private function isAssocativeArray($arr) {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

}
