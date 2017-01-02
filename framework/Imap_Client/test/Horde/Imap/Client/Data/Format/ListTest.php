<?php
/**
 * Copyright 2011-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @copyright  2011-2016 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */

/**
 * Tests for the List data format object.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2011-2016 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */
class Horde_Imap_Client_Data_Format_ListTest
extends PHPUnit_Framework_TestCase
{
    public function testBasicListFunctions()
    {
        $ob = new Horde_Imap_Client_Data_Format_List();

        $this->assertEquals(
            0,
            count($ob)
        );

        $ob->add(new Horde_Imap_Client_Data_Format_Atom('Foo'));
        $ob->add(new Horde_Imap_Client_Data_Format_Atom('Bar'));
        $ob->add(new Horde_Imap_Client_Data_Format_String('Baz'));

        $this->assertEquals(
            3,
            count($ob)
        );

        $this->assertEquals(
            'Foo Bar "Baz"',
            strval($ob)
        );

        $this->assertEquals(
            'Foo Bar "Baz"',
            $ob->escape()
        );

        foreach ($ob as $key => $val) {
            switch ($key) {
            case 0:
            case 1:
                $this->assertEquals(
                    'Horde_Imap_Client_Data_Format_Atom',
                    get_class($val)
                );
                break;

            case 2:
                $this->assertEquals(
                    'Horde_Imap_Client_Data_Format_String',
                    get_class($val)
                );
                break;
            }
        }

    }

    public function testAdvancedListFunctions()
    {
        $ob = new Horde_Imap_Client_Data_Format_List('Foo');

        $this->assertEquals(
            1,
            count($ob)
        );

        $ob_array = iterator_to_array($ob);
        $this->assertEquals(
            'Horde_Imap_Client_Data_Format_Atom',
            get_class(reset($ob_array))
        );

        $ob->add(array(
            'Foo',
            new Horde_Imap_Client_Data_Format_List(array('Bar'))
        ));

        $this->assertEquals(
            3,
            count($ob)
        );

        $this->assertEquals(
            'Foo Foo (Bar)',
            $ob->escape()
        );

        $ob = new Horde_Imap_Client_Data_Format_List(array(
            'Foo',
            new Horde_Imap_Client_Data_Format_List(array(
                'Foo1'
            )),
            'Bar',
            new Horde_Imap_Client_Data_Format_List(array(
                new Horde_Imap_Client_Data_Format_String('Bar1'),
                new Horde_Imap_Client_Data_Format_List(array(
                    'Baz'
                ))
            ))
        ));

        $this->assertEquals(
            4,
            count($ob)
        );

        $this->assertEquals(
            'Foo (Foo1) Bar ("Bar1" (Baz))',
            $ob->escape()
        );
    }

}
