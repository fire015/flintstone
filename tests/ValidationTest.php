<?php

use Flintstone\Validation;

class ValidationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     * @expectedException Flintstone\Exception
     * @expectedExceptionMessage Invalid characters in key
     */
    public function validateKey()
    {
        Validation::validateKey('test!123');
    }

    /**
     * @test
     * @expectedException Flintstone\Exception
     * @expectedExceptionMessage Invalid characters in database name
     */
    public function validateDatabaseName()
    {
        Validation::validateDatabaseName('test!123');
    }
}
