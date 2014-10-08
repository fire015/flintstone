<?php

/**
 * Flintstone Unit Tests
 */
namespace Flinstone\tests;

class FeatureTest extends TestFixture
{
    /**
     * Test 'set' operations
     */
    public function testSet()
    {
        $this->assertTrue($this->db->set('a', '1'));
        $this->assertTrue($this->db->set('b', 2));
        $this->assertTrue($this->db->set('c', array(3, 4, 5)));
        $this->assertTrue($this->db->set('d', "some data == some new lines\r\n"));
        $this->assertSame($this->db->get('d'), "some data == some new lines\r\n");
    }

    /**
     * Test 'set' operations
     * @expectedException Flintstone\FlintstoneException
     */
    public function testSetException()
    {
        $this->db->set('d', false);
    }

    /**
     * Test invalid key
     * @expectedException Flintstone\FlintstoneException
     */
    public function testInvalidKey()
    {
        $this->db->get(1);
    }

    /**
     * Test invalid character key
     * @expectedException Flintstone\FlintstoneException
     */
    public function testInvalidChrKey()
    {
        $this->db->get('a=b');
    }

    /**
     * Test blank key
     */
    public function testBlankKey()
    {
        $this->assertFalse($this->db->get(''));
    }

    /**
     * Test complex key
    */
    public function testComplexKey()
    {
        $this->assertFalse($this->db->get('users:1:name'));
    }

    /**
     * Test huge key
     * @expectedException Flintstone\FlintstoneException
     */
    public function testHugeKey()
    {
        $this->db->get(str_repeat('a', 2048));
    }

    /**
     * Test 'replace' operations
     */
    public function testReplace()
    {
        $this->assertTrue($this->db->set('a', '1'));
        $this->assertTrue($this->db->replace('a', '2'));
        $this->assertSame($this->db->get('a'), '2');
        $this->assertTrue($this->db->set('a', '1'));
        $this->assertSame($this->db->get('a'), '1');
    }

    /**
     * Test 'get' operations
     */
    public function testGet()
    {
        $this->assertFalse($this->db->get('a'));
        $this->assertTrue($this->db->set('a', '1'));
        $this->assertSame($this->db->get('a'), '1');
    }

    /**
     * Test 'delete' operations
     */
    public function testDelete()
    {
        $this->assertFalse($this->db->delete('a'));
        $this->assertTrue($this->db->set('a', '1'));
        $this->assertTrue($this->db->delete('a'));
        $this->assertFalse($this->db->get('a'));
    }

    /**
     * Test 'flush' operations
     */
    public function testFlush()
    {
        $this->assertTrue($this->db->set('a', '1'));
        $this->assertTrue($this->db->set('b', 2));
        $this->assertTrue($this->db->flush());
        $keys = $this->db->getKeys();
        $this->assertEquals(0, count($keys));
    }

    /**
     * Test 'getKeys' operations
     */
    public function testGetKeys()
    {
        $this->assertTrue($this->db->set('a', '1'));
        $this->assertTrue($this->db->set('b', 2));
        $this->assertTrue($this->db->set('c', array(3, 4, 5)));
        $keys = $this->db->getKeys();
        $this->assertEquals(3, count($keys));
        $this->assertContains('a', $keys);
    }

    /**
     * Test preserves keys between modification methods
     */
    public function testPreserveKeys()
    {
        $expected = array('name' => 'foo', 'age' => 'bar');
        foreach (range(1, 5) as $i) {
            $this->db->set('user'.$i, $expected);
        }
        $this->assertSame($expected, $this->db->get('user3'));
        $this->db->replace('user3', 'toto');

        $this->assertSame('toto', $this->db->get('user3'));
        $this->assertSame($expected, $this->db->get('user2'));
    }
}
