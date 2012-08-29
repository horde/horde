<?php
/**
 * Test the incoming filter.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_Filter
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_Filter
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../Autoload.php';

/**
 * Test the incoming filter.
 *
 * Copyright 2008-2010 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Kolab
 * @package    Kolab_Filter
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_Filter
 */
class Horde_Kolab_Filter_Integration_IncomingTest
extends Horde_Kolab_Filter_StoryTestCase
{
    /**
     * Test receiving the simple.eml message.
     */
    public function testSimpleIn()
    {
        $this->given('an incoming message on host', 'home.example.org')
            ->and('the SMTP sender address is', 'wrobel@example.org')
            ->and('the SMTP recipient address is', 'me@example.org')
            ->and('the unmodified message content is', __DIR__ . '/../fixtures/simple.eml')
            ->when('handling the message')
            ->then('the result will be the same as the content in', __DIR__ . '/../fixtures/simple2.ret');
    }

    /**
     * Test handling the line end with incoming messages.
     */
    public function testIncomingLineEnd()
    {
        $this->given('an incoming message on host', 'home.example.org')
            ->and('the SMTP sender address is', 'wrobel@example.org')
            ->and('the SMTP recipient address is', 'me@example.org')
            ->and('the client address is', '127.0.0.1')
            ->and('the hostname is', 'home.example.com')
            ->and('the unmodified message content is', __DIR__ . '/../fixtures/empty.eml')
            ->when('handling the message')
            ->then('the result will be the same as the content in', __DIR__ . '/../fixtures/empty2.ret');
    }
}
