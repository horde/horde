<?php
/**
 * Basic test case.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Pear
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Pear
Cli
 */

/**
 * Basic test case.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license instorageion (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @package    Pear
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Pear
 */
class Horde_Pear_TestCase
extends Horde_Test_Case
{
    protected function _getRelease()
    {
        return '<?xml version="1.0" encoding="UTF-8" ?>
<r xmlns="http://pear.php.net/dtd/rest.release" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xlink="http://www.w3.org/1999/xlink" xsi:schemaLocation="http://pear.php.net/dtd/rest.release http://pear.php.net/dtd/rest.release.xsd">
    <p xlink:href="/rest/p/A">A</p>
    <c>pear.horde.org</c>
    <v>1.0.0</v>
    <st>stable</st>
    <l>LGPL-2.1</l>
    <m>wrobel</m>
    <s>Component A</s>
    <d>Fancy thing.</d>
    <da>2011-04-06 01:07:26</da>
    <n>
* First stable release for A.
 </n>
    <f>439824</f>
    <g>http://pear.horde.org/get/A-1.0.0</g>
    <x xlink:href="package.1.0.0.xml"/>
</r>';
    }

    protected function _getBRelease()
    {
        return '<?xml version="1.0" encoding="UTF-8" ?>
<r xmlns="http://pear.php.net/dtd/rest.release" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xlink="http://www.w3.org/1999/xlink" xsi:schemaLocation="http://pear.php.net/dtd/rest.release http://pear.php.net/dtd/rest.release.xsd">
    <p xlink:href="/rest/p/B">B</p>
    <c>pear.horde.org</c>
    <v>1.0.0</v>
    <st>stable</st>
    <l>LGPL-2.1</l>
    <m>wrobel</m>
    <s>Component A</s>
    <d>Fancy thing.</d>
    <da>2011-04-06 01:07:26</da>
    <n>
* First stable release for B.
 </n>
    <f>439824</f>
    <g>http://pear.horde.org/get/B-1.0.0</g>
    <x xlink:href="package.1.0.0.xml"/>
</r>';
    }

    protected function getRemoteList($list = null)
    {
        if (!class_exists('Horde_Http_Client')) {
            $this->markTestSkipped('Horde_Http is missing!');
        }
        if ($list === null) {
            $list = '<?xml version="1.0" encoding="UTF-8" ?>
<l><p xlink:href="/rest/p/a">A</p><p xlink:href="/rest/p/b">B</p></l>';
        }
        $body = new Horde_Support_StringStream($list);
        $response = new Horde_Http_Response_Mock('', $body->fopen());
        $response->code = 200;
        $request = new Horde_Http_Request_Mock();
        $request->setResponse($response);
        return $this->createRemote($request);
    }

    protected function createRemote($request)
    {
        return new Horde_Pear_Remote(
            'test', 
            new Horde_Pear_Rest(
                new Horde_Http_Client(array('request' => $request)),
                'http://test'
            )
        );
    }
}
