<?php

use Flintstone\Line;

class LineTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Line
     */
    private $line;

    protected function setUp()
    {
        $this->line = new Line('foo=bar');
    }

    public function testGetLine()
    {
        $this->assertEquals('foo=bar', $this->line->getLine());
    }

    public function testGetKey()
    {
        $this->assertEquals('foo', $this->line->getKey());
    }

    public function testGetData()
    {
        $this->assertEquals('bar', $this->line->getData());
    }

    public function testMultipleEquals()
    {
        $line = new Line('foo=bar=baz');
        $this->assertEquals('foo', $line->getKey());
        $this->assertEquals('bar=baz', $line->getData());
    }
}
