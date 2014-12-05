<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2011-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @package   Horde_ActiveSync
 * @subpackage UnitTests
 */

/**
 * Factory to provide various test servers.
 *
 * @author     Michael J Rubinsky <mrubinsk@horde.org>
 * @category   Horde
 * @copyright  2014 Horde LLC
 * @ignore
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @package    Horde_ActiveSync
 * @subpackage UnitTests
 */
class Horde_ActiveSync_Factory_TestServer extends Horde_Test_Case
{
    public $server;
    public $driver;
    public $input;
    public $output;
    public $request;

    public function __construct($params = array())
    {
        $this->driver = $this->getMockSkipConstructor('Horde_ActiveSync_Driver_Base');
        $this->input = fopen('php://memory', 'wb+');
        $decoder = new Horde_ActiveSync_Wbxml_Decoder($this->input);
        $this->output = fopen('php://memory', 'wb+');
        $encoder = new Horde_ActiveSync_Wbxml_Encoder($this->output);
        $state = $this->getMockSkipConstructor('Horde_ActiveSync_State_Base');
        $this->request = $this->getMockSkipConstructor('Horde_Controller_Request_Http');
        $this->request->expects($this->any())
            ->method('getHeader')
            ->will($this->returnValue('14.1'));
        $this->server = new Horde_ActiveSync($this->driver, $decoder, $encoder, $state, $this->request);
    }

}