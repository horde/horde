<?php
/**
 * @category   Horde
 * @package    Icalendar
 * @subpackage UnitTests
 */

/**
 * @category   Horde
 * @package    Icalendar
 * @subpackage UnitTests
 */
class Horde_Icalendar_ExportTest extends Horde_Test_Case
{
    public function testLineFolding()
    {
        $ical = new Horde_Icalendar();
        $event = Horde_Icalendar::newComponent('vevent', $ical);
        $event->setAttribute('UID', 'XXX');
        $event->setAttribute('DTSTART', array('year' => 2008, 'month' => 1, 'mday' => 1), array('VALUE' => 'DATE'));
        $event->setAttribute('DTSTAMP', array('year' => 2008, 'month' => 1, 'mday' => 1), array('VALUE' => 'DATE'));
        $event->setAttribute('DESCRIPTION', 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aliquam sollicitudin faucibus mauris amet.');
        $event->setAttribute('BINARY', base64_encode('Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aliquam sollicitudin faucibus mauris amet.'), array('ENCODING' => 'b'));
        $ical->addComponent($event);
        $this->assertStringEqualsFile(
            __DIR__ . '/fixtures/line-folding-1.ics',
            $ical->exportVCalendar()
        );

        $ical = new Horde_Icalendar('1.0');
        $event = Horde_Icalendar::newComponent('vevent', $ical);
        $event->setAttribute('UID', 'XXX');
        $event->setAttribute('DTSTART', array('year' => 2008, 'month' => 1, 'mday' => 1), array('VALUE' => 'DATE'));
        $event->setAttribute('DTSTAMP', array('year' => 2008, 'month' => 1, 'mday' => 1), array('VALUE' => 'DATE'));
        $event->setAttribute('DESCRIPTION', 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aliquam sollicitudin faucibus mauris amet.');
        $ical->addComponent($event);
        $this->assertStringEqualsFile(
            __DIR__ . '/fixtures/line-folding-2.ics',
            $ical->exportVCalendar()
        );

        $ical = new Horde_Icalendar();
        $event = Horde_Icalendar::newComponent('vevent', $ical);
        $event->setAttribute('UID', 'XXX');
        $event->setAttribute('DTSTART', array('year' => 2008, 'month' => 1, 'mday' => 1), array('VALUE' => 'DATE'));
        $event->setAttribute('DTSTAMP', array('year' => 2008, 'month' => 1, 'mday' => 1), array('VALUE' => 'DATE'));
        $event->setAttribute('DESCRIPTION', 'Lörem ipsüm dölör sit ämet, cönsectetüer ädipiscing elit. Aliqüäm söllicitüdin fäücibüs mäüris ämet.');
        $ical->addComponent($event);
        $this->assertStringEqualsFile(
            __DIR__ . '/fixtures/line-folding-3.ics',
            $ical->exportVCalendar()
        );

        $ical = new Horde_Icalendar('1.0');
        $event = Horde_Icalendar::newComponent('vevent', $ical);
        $event->setAttribute('UID', 'XXX');
        $event->setAttribute('DTSTART', array('year' => 2008, 'month' => 1, 'mday' => 1), array('VALUE' => 'DATE'));
        $event->setAttribute('DTSTAMP', array('year' => 2008, 'month' => 1, 'mday' => 1), array('VALUE' => 'DATE'));
        $event->setAttribute('DESCRIPTION', 'Lörem ipsüm dölör sit ämet, cönsectetüer ädipiscing elit. Aliqüäm söllicitüdin fäücibüs mäüris ämet.', array('CHARSET' => 'UTF-8'));
        $ical->addComponent($event);
        $this->assertStringEqualsFile(
            __DIR__ . '/fixtures/line-folding-4.ics',
            $ical->exportVCalendar()
        );

        $ical = new Horde_Icalendar('1.0');
        $event = Horde_Icalendar::newComponent('vevent', $ical);
        $event->setAttribute('UID', 'XXX');
        $event->setAttribute('DTSTART', array('year' => 2008, 'month' => 1, 'mday' => 1), array('VALUE' => 'DATE'));
        $event->setAttribute('DTSTAMP', array('year' => 2008, 'month' => 1, 'mday' => 1), array('VALUE' => 'DATE'));
        $event->setAttribute('DESCRIPTION', 'Löremipsümdölörsitämet,cönsectetüerädipiscingelit.Aliqüämsöllicitüdinfäücibüsmäürisämet. Löremipsümdölörsitämet,cönsectetüerädipiscingelit.Aliqüämsöllicitüdinfäücibüsmäürisämet.', array('CHARSET' => 'UTF-8'));
        $ical->addComponent($event);
        $this->assertStringEqualsFile(
            __DIR__ . '/fixtures/line-folding-5.ics',
            $ical->exportVCalendar()
        );

        $ical = new Horde_Icalendar();
        $event = Horde_Icalendar::newComponent('vevent', $ical);
        $event->setAttribute('UID', 'XXX');
        $event->setAttribute('DTSTART', array('year' => 2008, 'month' => 1, 'mday' => 1), array('VALUE' => 'DATE'));
        $event->setAttribute('DTSTAMP', array('year' => 2008, 'month' => 1, 'mday' => 1), array('VALUE' => 'DATE'));
        $description = <<<EOT
SYLVIE DAGORNE a écrit :

Bonjour,

suite à mon appel téléphonique auprès de Jacques Benzerara, il m'a renvoyé vers vous. En effet, je souhaiterais vous rencontrer car:
1°) au niveau de l'observatoire local nous devons lancer une enquête sur un suivi de cohorte à la rentrée prochaine qui concernera tous les étudiants de L1. Nous souhaiterons faire un questionnaire en ligne ce questionnaire devra être hébergé sur un serveur.

2°) dans le cadre de l'observatoire régional, nos partenaires nous demande également de faire des questionnaires en ligne. Nous disposons du logiciel Modalisa qui permet de le réaliser mais du point de vu technique, nous avons besoin de voir avec vous,  les difficultés et les limites d'un tel dispositif afin de voir les démarches à suivre et pouvoir évoquer tous ces problèmes techniques, je souhaiterais vous rencontrer. Merci de me précisez vos disponibilités?
...
Je serai accompagné d'un collègue pour l'observatoire local (David Le foll) et de la chargée d'études de l'observatoire régional (Amélie Gicquel) pour la partie régionale.
EOT;
        $event->setAttribute('DESCRIPTION', $description);
        $ical->addComponent($event);
        $this->assertStringEqualsFile(
            __DIR__ . '/fixtures/line-folding-6.ics',
            $ical->exportVCalendar()
        );
    }

    public function testEscapes()
    {
        $ical = new Horde_Icalendar();

        $event1 = Horde_Icalendar::newComponent('vevent', $ical);
        $event2 = Horde_Icalendar::newComponent('vevent', $ical);

        $event1->setAttribute('UID', '20041120-8550-innerjoin-org');
        $event1->setAttribute('DTSTART', array('year' => 2005, 'month' => 5, 'mday' => 3), array('VALUE' => 'DATE'));
        $event1->setAttribute('DTSTAMP', array('year' => 2004, 'month' => 11, 'mday' => 20), array('VALUE' => 'DATE'));
        $event1->setAttribute('SUMMARY', 'Escaped Comma in Description Field');
        $event1->setAttribute('DESCRIPTION', 'There is a comma (escaped with a baskslash) in this sentence and some important words after it, see anything here?');

        $event2->setAttribute('UID', '20041120-8549-innerjoin-org');
        $event2->setAttribute('DTSTART', array('year' => 2005, 'month' => 5, 'mday' => 4), array('VALUE' => 'DATE'));
        $event2->setAttribute('DTSTAMP', array('year' => 2004, 'month' => 11, 'mday' => 20), array('VALUE' => 'DATE'));
        $event2->setAttribute('SUMMARY', 'Dash (rather than Comma) in the Description Field');
        $event2->setAttribute('DESCRIPTION', 'There are important words after this dash - see anything here or have the words gone?');

        $ical->addComponent($event1);
        $ical->addComponent($event2);

        $this->assertStringEqualsFile(
            __DIR__ . '/fixtures/escapes2.ics',
            $ical->exportVCalendar()
        );

        $readIcal = new Horde_Icalendar();
        $readIcal->parseVCalendar($ical->exportVCalendar());
        $this->assertEquals(
            array('There is a comma (escaped with a baskslash) in this sentence and some important words after it, see anything here?'),
            $readIcal->getComponent(0)->getAttributeValues('DESCRIPTION')
        );
        $this->assertEquals(
            array('There are important words after this dash - see anything here or have the words gone?'),
            $readIcal->getComponent(1)->getAttributeValues('DESCRIPTION')
        );
    }

    public function testQuotedParameters()
    {
        $ical = new Horde_Icalendar();
        $event1 = Horde_Icalendar::newComponent('vevent', $ical);
        $event1->setAttribute('UID', '20041120-8550-innerjoin-org');
        $event1->setAttribute('DTSTART', array('year' => 2005, 'month' => 5, 'mday' => 3), array('VALUE' => 'DATE'));
        $event1->setAttribute('DTSTAMP', array('year' => 2004, 'month' => 11, 'mday' => 20), array('VALUE' => 'DATE'));
        $event1->setAttribute('SUMMARY', 'Escaped Comma in Description Field');
        $event1->setAttribute('DESCRIPTION', 'There is a comma (escaped with a baskslash) in this sentence and some important words after it, see anything here?');
        $event1->setAttribute('ORGANIZER', 'mailto:mueller@example.org', array('CN' => "Klä,rc\"hen;\n Mül:ler"));
        $ical->addComponent($event1);
        $this->assertStringEqualsFile(
            __DIR__ . '/fixtures/quoted-params.ics',
            $ical->exportVCalendar()
        );
    }
}
