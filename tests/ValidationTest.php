<?php

use Flintstone\Validation;

class ValidationTest extends PHPUnit_Framework_TestCase
{
    /**
     * @expectedException Flintstone\Exception
     * @expectedExceptionMessage Invalid characters in key
     */
    public function testValidateKey()
    {
        Validation::validateKey('test!123');
    }

    /**
     * @expectedException Flintstone\Exception
     * @expectedExceptionMessage Invalid data type
     */
    public function testValidateData()
    {
        Validation::validateData(new self());
    }

    /**
     * @expectedException Flintstone\Exception
     * @expectedExceptionMessage Invalid characters in database name
     */
    public function testValidateDatabaseName()
    {
        Validation::validateDatabaseName('test!123');
    }
}
