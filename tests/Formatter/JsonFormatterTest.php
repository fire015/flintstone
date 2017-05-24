<?php

use Flintstone\Formatter\JsonFormatter;

class JsonFormatterTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var JsonFormatter
     */
    private $formatter;

    protected function setUp()
    {
        $this->formatter = new JsonFormatter();
    }

    public function testEncode()
    {
        $data = $this->formatter->encode(["test", "new\nline"]);
        $this->assertEquals('["test","new\nline"]', $data);
    }

    public function testDecode()
    {
        $data = $this->formatter->decode('["test","new\nline"]');
        $this->assertTrue(is_array($data));
        $this->assertEquals(["test", "new\nline"], $data);
    }
}
