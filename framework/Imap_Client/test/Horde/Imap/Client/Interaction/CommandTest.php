<?php
/**
 * Copyright 2014-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @copyright  2014-2016 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */

/**
 * Tests for the Interaction Command object
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2014-2016 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */
class Horde_Imap_Client_Interaction_CommandTest
    extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider continuationCheckProvider
     */
    public function testContinuationCheck($command, $result)
    {
        $this->assertEquals(
            $result,
            $command->continuation
        );
    }

    public function continuationCheckProvider()
    {
        $out = array();

        $cmd = new Horde_Imap_Client_Interaction_Command('FOO', '1');
        $cmd->add(array(
            'FOO',
            'BAR'
        ));

        $out[] = array($cmd, false);

        $cmd = clone $cmd;
        $cmd->add(
            new Horde_Imap_Client_Interaction_Command_Continuation(function() {})
        );

        $out[] = array($cmd, true);

        $cmd = new Horde_Imap_Client_Interaction_Command('FOO', '1');
        $cmd->add(array(
            'FOO',
            array(
                'BAR'
            ),
            new Horde_Imap_Client_Data_Format_List(array(
                'BAR'
            ))
        ));

        $out[] = array($cmd, false);

        $cmd = new Horde_Imap_Client_Interaction_Command('FOO', '1');
        $cmd->add(array(
            'FOO',
            array(
                'BAR',
                array(
                    'BAZ',
                    array(
                        new Horde_Imap_Client_Data_Format_String_Nonascii('Envoyé')
                    )
                )
            )
        ));

        $out[] = array($cmd, true);

        return $out;
    }

}
