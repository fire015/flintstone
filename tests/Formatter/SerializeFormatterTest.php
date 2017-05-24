<?php

use Flintstone\Formatter\SerializeFormatter;

class SerializeFormatterTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var SerializeFormatter
     */
    private $formatter;

    protected function setUp()
    {
        $this->formatter = new SerializeFormatter();
    }

    public function testEncode()
    {
        $data = $this->formatter->encode(["test", "new\nline"]);
        $this->assertEquals('a:2:{i:0;s:4:"test";i:1;s:9:"new\nline";}', $data);
    }

    public function testDecode()
    {
        $data = $this->formatter->decode('a:2:{i:0;s:4:"test";i:1;s:9:"new\nline";}');
        $this->assertTrue(is_array($data));
        $this->assertEquals(["test", "new\nline"], $data);
    }
}
