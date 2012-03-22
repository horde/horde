<?php
/**
 * Test the Blogger recipient.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Push
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://www.horde.org/libraries/Horde_Push
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../../Autoload.php';

/**
 * Test the Blogger recipient.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @package    Push
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://www.horde.org/libraries/Horde_Push
 */
class Horde_Push_Unit_Push_Recipient_BloggerTest
extends Horde_Push_TestCase
{
    public function testBloggerRecipient()
    {
        $push = new Horde_Push();
        $request = new Horde_Http_Request_Mock();
        $request->addResponse('Auth=X');
        $request->addResponse('<entry xmlns=\'http://www.w3.org/2005/Atom\'>OK</entry>', 201);
        $client = new Horde_Http_Client(array('request' => $request));
        $result = $push->addRecipient(
            new Horde_Push_Recipient_Blogger(
                $client,
                array(
                    'url' => 'http://blogger.com',
                    'username' => 'test',
                    'password' => 'pass',
                )
            )
        )->setSummary('BLOG')
            ->push();
        $this->assertEquals(array('Pushed blog entry to http://blogger.com.'), $result);
    }

    public function testPretend()
    {
        $push = new Horde_Push();
        $request = new Horde_Http_Request_Mock();
        $request->addResponse('Auth=X');
        $request->addResponse('<entry xmlns=\'http://www.w3.org/2005/Atom\'>OK</entry>', 201);
        $client = new Horde_Http_Client(array('request' => $request));
        $result = $push->addRecipient(
            new Horde_Push_Recipient_Blogger(
                $client,
                array(
                    'url' => 'http://blogger.com',
                    'username' => 'test',
                    'password' => 'pass',
                )
            )
        )->setSummary('BLOG')
            ->push(array('pretend' => true));
        $this->assertEquals(
            array(
                'Would push 

BLOG

 to http://blogger.com.'
            ),
            $result
        );
    }

    /**
     * @expectedException Horde_Push_Exception
     */
    public function testAuthFailure()
    {
        $push = new Horde_Push();
        $request = new Horde_Http_Request_Mock();
        $request->addResponse('Auth=X', 404);
        $client = new Horde_Http_Client(array('request' => $request));
        $result = $push->addRecipient(
            new Horde_Push_Recipient_Blogger(
                $client,
                array(
                    'url' => 'http://blogger.com',
                    'username' => 'test',
                    'password' => 'pass',
                )
            )
        )->setSummary('BLOG')
            ->push();
    }

    /**
     * @expectedException Horde_Push_Exception
     */
    public function testMissingAuth()
    {
        $push = new Horde_Push();
        $request = new Horde_Http_Request_Mock();
        $request->addResponse('NO AUTH');
        $client = new Horde_Http_Client(array('request' => $request));
        $result = $push->addRecipient(
            new Horde_Push_Recipient_Blogger(
                $client,
                array(
                    'url' => 'http://blogger.com',
                    'username' => 'test',
                    'password' => 'pass',
                )
            )
        )->setSummary('BLOG')
            ->push();
    }

    /**
     * @expectedException Horde_Push_Exception
     */
    public function testBlogFailure()
    {
        $push = new Horde_Push();
        $request = new Horde_Http_Request_Mock();
        $request->addResponse('Auth=X');
        $request->addResponse('', 404);
        $client = new Horde_Http_Client(array('request' => $request));
        $result = $push->addRecipient(
            new Horde_Push_Recipient_Blogger(
                $client,
                array(
                    'url' => 'http://blogger.com',
                    'username' => 'test',
                    'password' => 'pass',
                )
            )
        )->setSummary('BLOG')
            ->push();
    }

    /**
     * @expectedException Horde_Push_Exception
     */
    public function testBadResponse()
    {
        $push = new Horde_Push();
        $request = new Horde_Http_Request_Mock();
        $request->addResponse('Auth=X');
        $request->addResponse('', 201);
        $client = new Horde_Http_Client(array('request' => $request));
        $result = $push->addRecipient(
            new Horde_Push_Recipient_Blogger(
                $client,
                array(
                    'url' => 'http://blogger.com',
                    'username' => 'test',
                    'password' => 'pass',
                )
            )
        )->setSummary('BLOG')
            ->push();
    }
}
