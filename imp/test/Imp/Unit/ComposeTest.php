<?php
/**
 * Copyright 2011-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category   Horde
 * @copyright  2011-2015 Horde LLC
 * @license    http://www.horde.org/licenses/gpl GPL
 * @package    IMP
 * @subpackage UnitTests
 */

/**
 * Test compose related code.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2011-2015 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/gpl GPL
 * @package    IMP
 * @subpackage UnitTests
 */
class Imp_Unit_ComposeTest extends PHPUnit_Framework_TestCase
{
    public function testBug10431()
    {
        $text = 'Das könnte zum Beispiel so aussehen, dass wir bei entsprechenden Anfragen diese an eine Kontaktperson bei Euch weiterleiten. Oder Ihr schnürt ein entsprechendes Paket, dass wir in unseren Angeboten mit anführen. Bei erfolgreicher Vermittlung bekämen wir eine Vermittlungsgebühr.
Wir ständen dann weiterhin für 3rd-Level-Support zur Verfügung, d.h. für alle Anfragen des Kunden bzgl. Horde, die nicht zum Tagesgeschäft gehören.';
        $text = Horde_String::convertCharset($text, 'UTF-8', 'ISO-8859-1');

        $textBody = new Horde_Mime_Part();
        $textBody->setType('text/plain');
        $textBody->setCharset('ISO-8859-1');

        $flowed = new Horde_Text_Flowed($text, 'ISO-8859-1');
        $flowed->setDelSp(true);
        $textBody->setContents($flowed->toFlowed());

        $flowed_txt = $textBody->toString(array('headers' => false));

        $textBody2 = new Horde_Mime_Part();
        $textBody2->setType('text/plain');
        $textBody2->setCharset('ISO-8859-1');
        $textBody2->setContents($flowed_txt, array(
            'encoding' => 'quoted-printable'
        ));

        $flowed2 = new Horde_Text_Flowed($textBody2->getContents(), 'ISO-8859-1');
        $flowed2->setMaxLength(0);
        $flowed2->setDelSp(true);

        $this->assertEquals(
            $text,
            trim($flowed2->toFixed())
        );
    }

}
