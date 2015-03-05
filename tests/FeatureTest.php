<?php

/**
 * Flintstone Unit Tests
 */

namespace Flinstone\tests;

class FeatureTest extends TestFixture
{
    public function testSetOperationsAcceptValidValues()
    {
        $this->assertTrue($this->db->set('a', '1'));
        $this->assertTrue($this->db->set('b', 2));
        $this->assertTrue($this->db->set('c', array(3, 4, 5)));
        $this->assertTrue($this->db->set('d', "some data == some new lines\r\n"));
        $this->assertSame($this->db->get('d'), "some data == some new lines\r\n");
        $this->assertSame($this->db->get('c'), array(3, 4, 5));
    }

    /**
     * @expectedException \Flintstone\FlintstoneException
     * @expectedExceptionMessage Invalid data type
     */
    public function testThrowsExceptionWhenStoringABooleanValue()
    {
        $this->db->set('d', false);
    }

    /**
     * @expectedException \Flintstone\FlintstoneException
     * @expectedExceptionMessage Key must be a string
     */
    public function testThrowsExceptionWhenKeyIsAnInteger()
    {
        $this->db->get(1);
    }

    /**
     * @expectedException \Flintstone\FlintstoneException
     * @expectedExceptionMessage Key may not contain the equals character
     */
    public function testThrowsExceptionWhenKeyContainsEqualsCharacter()
    {
        $this->db->get('a=b');
    }

    public function testEmptyStringIsAValidKey()
    {
        $this->assertFalse($this->db->get(''));
    }

    public function testAcceptsComplexKey()
    {
        $this->assertFalse($this->db->get('users:1:name'));
    }

    /**
     * @expectedException \Flintstone\FlintstoneException
     * @expectedExceptionMessage Maximum key length is 1024 characters
     */
    public function testThrowsExceptionIfKeyExceedsMaxLength()
    {
        $this->db->get(str_repeat('a', 2048));
    }

    public function testReplaceOperationReplacesOriginalValue()
    {
        $this->assertTrue($this->db->set('a', '1'));
        $this->assertTrue($this->db->replace('a', '2'));
        $this->assertSame($this->db->get('a'), '2');
        $this->assertTrue($this->db->set('a', '1'));
        $this->assertSame($this->db->get('a'), '1');
    }

    public function testGetOperationReturnsCorrectValues()
    {
        $this->assertFalse($this->db->get('a'));
        $this->assertTrue($this->db->set('a', '1'));
        $this->assertSame($this->db->get('a'), '1');
    }

    public function testDeleteOperationRemovesAValue()
    {
        $this->assertFalse($this->db->delete('a'));
        $this->assertTrue($this->db->set('a', '1'));
        $this->assertTrue($this->db->delete('a'));
        $this->assertFalse($this->db->get('a'));
    }

    public function testFlushEmptiesTheDatabase()
    {
        $this->assertTrue($this->db->set('a', '1'));
        $this->assertTrue($this->db->set('b', 2));
        $this->assertTrue($this->db->flush());
        $keys = $this->db->getKeys();
        $this->assertCount(0, $keys);
    }

    public function testGetKeysReturnsAllKeys()
    {
        $this->assertTrue($this->db->set('a', '1'));
        $this->assertTrue($this->db->set('b', 2));
        $this->assertTrue($this->db->set('c', array(3, 4, 5)));
        $keys = $this->db->getKeys();
        $this->assertCount(3, $keys);
        $this->assertContains('a', $keys);
    }

    public function testGetAllReturnsAllData()
    {
        $this->assertTrue($this->db->set('a', '1'));
        $this->assertTrue($this->db->set('b', 2));
        $this->assertTrue($this->db->set('c', array(3, 4, 5)));
        $data = $this->db->getAll();
        $expected = array(
            'a' => '1',
            'b' => 2,
            'c' => array(3, 4, 5),
        );
        $this->assertEquals($expected, $data);
    }

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
