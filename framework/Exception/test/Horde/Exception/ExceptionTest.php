<?php
/**
 * Test for the Horde_Exception:: class.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Exception
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Exception
 */

/**
 * Require the tested classes.
 */
require_once 'Autoload.php';

/**
 * Test for the Horde_Exception:: class.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Exception
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Exception
 */
class Horde_Exception_ExceptionTest extends  PHPUnit_Framework_TestCase
{

    // Basic Exception Testing

    public function testEmptyConstructionYieldsEmptyMessage()
    {
        $e = new Horde_Exception();
        $this->assertSame('', $e->getMessage());
    }

    public function testEmptyConstructionYieldsCodeZero()
    {
        $e = new Horde_Exception();
        $this->assertSame(0, $e->getCode());
    }

    public function testMethodGetpreviousYieldsPreviousException()
    {
        $e = new Horde_Exception(null, null, new Exception('previous'));
        $this->assertEquals('previous', $e->getPrevious()->getMessage());
    }

    public function testMethodTostringYieldsExceptionDescription()
    {
        $e = new Horde_Exception();
        $this->assertContains('exception \'Horde_Exception\'', (string)$e);
    }

    public function testMethodTostringContainsDescriptionOfPreviousException()
    {
        $e = new Horde_Exception(null, null, new Exception('previous'));
        $this->assertContains('Next exception \'Horde_Exception\'', (string)$e);
    }

    // NotFound Exception Testing

    public function testEmptyConstructionYieldsNotFoundMessage()
    {
        setlocale(LC_MESSAGES, 'C');
        $e = new Horde_Exception_NotFound();
        $this->assertSame('Not Found', $e->getMessage());
    }

    // Basic Exception Testing

    public function testEmptyConstructionYieldsPermissionDeniedMessage()
    {
        setlocale(LC_MESSAGES, 'C');
        $e = new Horde_Exception_PermissionDenied();
        $this->assertSame('Permission Denied', $e->getMessage());
    }

    // Prior Exception Testing

    public function testConstructionWithPearErrorYieldsMessageFromPearError()
    {
        require_once __DIR__ . '/Stub/PearError.php';
        $p = new Horde_Exception_Stub_PearError('pear');
        $e = new Horde_Exception_Wrapped($p);
        $this->assertSame('pear', $e->getMessage());
    }

    public function testConstructionWithPearErrorYieldsCodeFromPearError()
    {
        require_once __DIR__ . '/Stub/PearError.php';
        $p = new Horde_Exception_Stub_PearError('pear', 666);
        $e = new Horde_Exception_Wrapped($p);
        $this->assertSame(666, $e->getCode());
    }

    // LastError Exception Testing

    public function testConstructionOfLastErrorYieldsStandardException()
    {
        $e = new Horde_Exception_LastError();
        $this->assertSame('', $e->getMessage());
    }

    public function testConstructionWithGetlasterrorarrayYieldsMessageFromArray()
    {
        $e = new Horde_Exception_LastError(null, $this->_getLastError());
        $this->assertSame('get_last_error', $e->getMessage());
    }

    public function testConstructionWithGetlasterrorarrayYieldsCodeFromArray()
    {
        $e = new Horde_Exception_LastError(null, $this->_getLastError());
        $this->assertSame(666, $e->getCode());
    }

    public function testConstructionWithGetlasterrorarrayYieldsFileFromArray()
    {
        $e = new Horde_Exception_LastError(null, $this->_getLastError());
        $this->assertSame('/some/file.php', $e->getFile());
    }

    public function testConstructionWithGetlasterrorarrayYieldsLineFromArray()
    {
        $e = new Horde_Exception_LastError(null, $this->_getLastError());
        $this->assertSame(99, $e->getLine());
    }

    public function testConstructionWithGetlasterrorarrayConcatenatesMessagesFromConstructorAndErrorarray()
    {
        $e = new Horde_Exception_LastError('An error occurred: ', $this->_getLastError());
        $this->assertSame('An error occurred: get_last_error', $e->getMessage());
    }

    public function testCatchingAndConvertingPearErrors()
    {
        $this->_loadPear();
        try {
            Horde_Exception_Pear::catchError(new PEAR_Error('An error occurred.'));
        } catch (Horde_Exception_Pear $e) {
            $this->assertContains(
                'Horde_Exception_ExceptionTest->testCatchingAndConvertingPearErrors unkown:unkown',
                $e->details
            );
        }
    }

    public function testStringUserinfo()
    {
        $this->_loadPear();
        try {
            Horde_Exception_Pear::catchError(
                new PEAR_Error('An error occurred.', null, null, null, 'userinfo')
            );
        } catch (Horde_Exception_Pear $e) {
            $this->assertContains('userinfo', $e->details);
        }
    }

    public function testArrayUserinfo()
    {
        $this->_loadPear();
        try {
            Horde_Exception_Pear::catchError(
                new PEAR_Error('An error occurred.', null, null, null, array('userinfo'))
            );
        } catch (Horde_Exception_Pear $e) {
            $this->assertContains('[0] => userinfo', $e->details);
        }
    }

    private function _getLastError()
    {
        return array(
            'message' => 'get_last_error',
            'type'    => 666,
            'file'    => '/some/file.php',
            'line'    => 99
        );
    }

    private function _loadPear()
    {
        @require_once 'PEAR.php';
        if (!class_exists('PEAR_Error')) {
            $this->markTestSkipped('PEAR_Error is missing!');
        }
    }
}