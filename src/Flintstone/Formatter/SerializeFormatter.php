<?php

/**
 * Flintstone - A key/value database store using flat files for PHP
 * Copyright (c) 2014 Jason M
 */

namespace Flintstone\Formatter;

/**
 * Encodes/decodes data into a native PHP stored representation
 */
class SerializeFormatter implements FormatterInterface
{
    /**
     * {@inheritdoc}
     */
    public function encode($data)
    {
        return serialize($this->preserveLines($data, false));
    }

    /**
     * {@inheritdoc}
     */
    public function decode($data)
    {
        return $this->preserveLines(unserialize($data), true);
    }

    /**
     * Preserve new lines, recursive function
     *
     * @param mixed   $data    the data
     * @param boolean $reverse to reverse the replacement order
     *
     * @return mixed the data
     */
    private function preserveLines($data, $reverse)
    {
        $search  = array("\n", "\r");
        $replace = array("\\n", "\\r");
        if ($reverse) {
            $search  = array("\\n", "\\r");
            $replace = array("\n", "\r");
        }

        if (is_string($data)) {
            $data = str_replace($search, $replace, $data);
        } elseif (is_array($data)) {
            foreach ($data as &$value) {
                $value = $this->preserveLines($value, $reverse);
            }
            unset($value);
        }

        return $data;
    }
}
