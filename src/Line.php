<?php

namespace Flintstone;

class Line
{
    /**
     * @var string
     */
    protected $line;

    /**
     * @var array
     */
    protected $pieces = [];

    /**
     * @param string $line
     */
    public function __construct($line)
    {
        $this->line = $line;
        $this->pieces = explode('=', $line, 2);
    }

    /**
     * @return string
     */
    public function getLine()
    {
        return $this->line;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->pieces[0];
    }

    /**
     * @return string
     */
    public function getData()
    {
        return $this->pieces[1];
    }
}
