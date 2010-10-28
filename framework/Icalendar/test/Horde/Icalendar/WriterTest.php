<?php
/**
 * @category   Horde
 * @package    Horde_Icalendar
 * @subpackage UnitTests
 * @copyright  2009 The Horde Project (http://www.horde.org/)
 * @license    http://www.fsf.org/copyleft/lgpl.html
 */

/**
 * @category   Horde
 * @package    Horde_Icalendar
 * @subpackage UnitTests
 */
class Horde_Icalendar_WriterTest extends Horde_Test_Case
{
    public function testEscapes()
    {
        $ical = new Horde_Icalendar_Vcalendar(array('version' => '2.0'));
        $ical->method = 'PUBLISH';
        $event1 = new Horde_Icalendar_Vevent();
        $event2 = new Horde_Icalendar_Vevent();

        $event1->uid = '20041120-8550-innerjoin-org';
        $event1->startDate = new Horde_Date(array('year' => 2005, 'month' => 5, 'mday' => 3));
        $event1->stamp = new Horde_Date(array('year' => 2004, 'month' => 11, 'mday' => 20), 'UTC');
        $event1->summary = 'Escaped Comma in Description Field';
        $event1->description = 'There is a comma (escaped with a baskslash) in this sentence and some important words after it, see anything here?';

        $event2->uid = '20041120-8549-innerjoin-org';
        $event2->startDate = new Horde_Date(array('year' => 2005, 'month' => 5, 'mday' => 4));
        $event2->stamp = new Horde_Date(array('year' => 2004, 'month' => 11, 'mday' => 20), 'UTC');
        $event2->summary = 'Dash (rather than Comma) in the Description Field';
        $event2->description = 'There are important words after this dash - see anything here or have the words gone?';

        $ical->components[] = $event1;
        $ical->components[] = $event2;

        $this->assertEquals('BEGIN:VCALENDAR
METHOD:PUBLISH
PRODID:-//The Horde Project//Horde_Icalendar Library//EN
VERSION:2.0
BEGIN:VEVENT
UID:20041120-8550-innerjoin-org
DTSTART;VALUE=DATE:20050503
DTSTAMP:20041120T000000Z
SUMMARY:Escaped Comma in Description Field
DESCRIPTION:There is a comma (escaped with a baskslash) in this sentence an
 d some important words after it\, see anything here?
END:VEVENT
BEGIN:VEVENT
UID:20041120-8549-innerjoin-org
DTSTART;VALUE=DATE:20050504
DTSTAMP:20041120T000000Z
SUMMARY:Dash (rather than Comma) in the Description Field
DESCRIPTION:There are important words after this dash - see anything here o
 r have the words gone?
END:VEVENT
END:VCALENDAR
',
                            $ical->export());
    }

    public function testLineFolding()
    {
        $ical = new Horde_Icalendar_Vcalendar(array('version' => '2.0'));
        $event = new Horde_Icalendar_Vevent();
        $event->uid = 'XXX';
        $event->startDate = new Horde_Date(array('year' => 2008, 'month' => 1, 'mday' => 1));
        $event->stamp = new Horde_Date(array('year' => 2008, 'month' => 1, 'mday' => 1), 'UTC');
        $event->description = 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aliquam sollicitudin faucibus mauris amet.';
        $ical->components[] = $event;
        $this->assertEquals('BEGIN:VCALENDAR
PRODID:-//The Horde Project//Horde_Icalendar Library//EN
VERSION:2.0
BEGIN:VEVENT
UID:XXX
DTSTART;VALUE=DATE:20080101
DTSTAMP:20080101T000000Z
DESCRIPTION:Lorem ipsum dolor sit amet\, consectetuer adipiscing elit. Aliq
 uam sollicitudin faucibus mauris amet.
END:VEVENT
END:VCALENDAR
',
                            $ical->export());

        $ical = new Horde_Icalendar_Vcalendar(array('version' => '1.0'));
        $event = new Horde_Icalendar_Vevent();
        $event->uid = 'XXX';
        $event->startDate = new Horde_Date(array('year' => 2008, 'month' => 1, 'mday' => 1));
        $event->stamp = new Horde_Date(array('year' => 2008, 'month' => 1, 'mday' => 1), 'UTC');
        $event->description = 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aliquam sollicitudin faucibus mauris amet.';
        $ical->components[] = $event;
        $this->assertEquals('BEGIN:VCALENDAR
PRODID:-//The Horde Project//Horde_Icalendar Library//EN
VERSION:1.0
BEGIN:VEVENT
UID:XXX
DTSTART:20080101T000000
DTSTAMP:20080101T000000Z
DESCRIPTION:Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aliquam sollicitudin faucibus mauris amet.
END:VEVENT
END:VCALENDAR
',
                            $ical->export());

        $ical = new Horde_Icalendar_Vcalendar(array('version' => '2.0'));
        $event = new Horde_Icalendar_Vevent();
        $event->uid = 'XXX';
        $event->startDate = new Horde_Date(array('year' => 2008, 'month' => 1, 'mday' => 1));
        $event->stamp = new Horde_Date(array('year' => 2008, 'month' => 1, 'mday' => 1), 'UTC');
        $event->description = 'Lörem ipsüm dölör sit ämet, cönsectetüer ädipiscing elit. Aliqüäm söllicitüdin fäücibüs mäüris ämet.';
        $ical->components[] = $event;
        $this->assertEquals('BEGIN:VCALENDAR
PRODID:-//The Horde Project//Horde_Icalendar Library//EN
VERSION:2.0
BEGIN:VEVENT
UID:XXX
DTSTART;VALUE=DATE:20080101
DTSTAMP:20080101T000000Z
DESCRIPTION:Lörem ipsüm dölör sit ämet\, cönsectetüer ädipiscing elit.
  Aliqüäm söllicitüdin fäücibüs mäüris ämet.
END:VEVENT
END:VCALENDAR
',
                            $ical->export());

        $ical = new Horde_Icalendar_Vcalendar(array('version' => '1.0'));
        $event = new Horde_Icalendar_Vevent();
        $event->uid = 'XXX';
        $event->startDate = new Horde_Date(array('year' => 2008, 'month' => 1, 'mday' => 1));
        $event->stamp = new Horde_Date(array('year' => 2008, 'month' => 1, 'mday' => 1), 'UTC');
        $event->description = 'Lörem ipsüm dölör sit ämet, cönsectetüer ädipiscing elit. Aliqüäm söllicitüdin fäücibüs mäüris ämet.';
        $ical->components[] = $event;
        $this->assertEquals('BEGIN:VCALENDAR
PRODID:-//The Horde Project//Horde_Icalendar Library//EN
VERSION:1.0
BEGIN:VEVENT
UID:XXX
DTSTART:20080101T000000
DTSTAMP:20080101T000000Z
DESCRIPTION;CHARSET=UTF-8;ENCODING=QUOTED-PRINTABLE:L=C3=B6rem ips=C3=BCm d=C3=B6l=C3=B6r sit =C3=A4met,=
 c=C3=B6nsectet=C3=BCer =C3=A4dipiscing elit. Aliq=C3=BC=C3=A4m=
 s=C3=B6llicit=C3=BCdin f=C3=A4=C3=BCcib=C3=BCs m=C3=A4=C3=BCris =C3=A4met.
END:VEVENT
END:VCALENDAR
',
                            $ical->export());

        $ical = new Horde_Icalendar_Vcalendar(array('version' => '1.0'));
        $event = new Horde_Icalendar_Vevent();
        $event->uid = 'XXX';
        $event->startDate = new Horde_Date(array('year' => 2008, 'month' => 1, 'mday' => 1));
        $event->stamp = new Horde_Date(array('year' => 2008, 'month' => 1, 'mday' => 1), 'UTC');
        $event->description = 'Löremipsümdölörsitämet,cönsectetüerädipiscingelit.Aliqüämsöllicitüdinfäücibüsmäürisämet. Löremipsümdölörsitämet,cönsectetüerädipiscingelit.Aliqüämsöllicitüdinfäücibüsmäürisämet.';
        $ical->components[] = $event;
        $this->assertEquals('BEGIN:VCALENDAR
PRODID:-//The Horde Project//Horde_Icalendar Library//EN
VERSION:1.0
BEGIN:VEVENT
UID:XXX
DTSTART:20080101T000000
DTSTAMP:20080101T000000Z
DESCRIPTION;CHARSET=UTF-8;ENCODING=QUOTED-PRINTABLE:L=C3=B6remips=C3=BCmd=C3=B6l=C3=B6rsit=C3=A4met,c=C3=B6nsectet=C3=BCer=C3=A=
4dipiscingelit.Aliq=C3=BC=C3=A4ms=C3=B6llicit=C3=BCdinf=C3=A4=C3=BCcib=C3=B=
Csm=C3=A4=C3=BCris=C3=A4met.=
 L=C3=B6remips=C3=BCmd=C3=B6l=C3=B6rsit=C3=A4met,c=C3=B6nsectet=C3=BCer=C3==
A4dipiscingelit.Aliq=C3=BC=C3=A4ms=C3=B6llicit=C3=BCdinf=C3=A4=C3=BCcib=C3==
BCsm=C3=A4=C3=BCris=C3=A4met.
END:VEVENT
END:VCALENDAR
',
                            $ical->export());

        $ical = new Horde_Icalendar_Vcalendar(array('version' => '2.0'));
        $event = new Horde_Icalendar_Vevent();
        $event->uid = 'XXX';
        $event->startDate = new Horde_Date(array('year' => 2008, 'month' => 1, 'mday' => 1));
        $event->stamp = new Horde_Date(array('year' => 2008, 'month' => 1, 'mday' => 1), 'UTC');
$event->description = <<<EOT
SYLVIE DAGORNE a écrit :

Bonjour,

suite à mon appel téléphonique auprès de Jacques Benzerara, il m'a renvoyé vers vous. En effet, je souhaiterais vous rencontrer car:
1°) au niveau de l'observatoire local nous devons lancer une enquête sur un suivi de cohorte à la rentrée prochaine qui concernera tous les étudiants de L1. Nous souhaiterons faire un questionnaire en ligne ce questionnaire devra être hébergé sur un serveur.

2°) dans le cadre de l'observatoire régional, nos partenaires nous demande également de faire des questionnaires en ligne. Nous disposons du logiciel Modalisa qui permet de le réaliser mais du point de vu technique, nous avons besoin de voir avec vous,  les difficultés et les limites d'un tel dispositif afin de voir les démarches à suivre et pouvoir évoquer tous ces problèmes techniques, je souhaiterais vous rencontrer. Merci de me précisez vos disponibilités?
...
Je serai accompagné d'un collègue pour l'observatoire local (David Le foll) et de la chargée d'études de l'observatoire régional (Amélie Gicquel) pour la partie régionale.
EOT;
        $ical->components[] = $event;
        $this->assertEquals('BEGIN:VCALENDAR
PRODID:-//The Horde Project//Horde_Icalendar Library//EN
VERSION:2.0
BEGIN:VEVENT
UID:XXX
DTSTART;VALUE=DATE:20080101
DTSTAMP:20080101T000000Z
DESCRIPTION:SYLVIE DAGORNE a écrit :\n\nBonjour\,\n\nsuite à mon appel
  téléphonique auprès de Jacques Benzerara\, il m\'a renvoyé vers vous. En
  effet\, je souhaiterais vous rencontrer car:\n1°) au niveau de
  l\'observatoire local nous devons lancer une enquête sur un suivi de
  cohorte à la rentrée prochaine qui concernera tous les étudiants de L1.
  Nous souhaiterons faire un questionnaire en ligne ce questionnaire devra
  être hébergé sur un serveur.\n\n2°) dans le cadre de l\'observatoire
  régional\, nos partenaires nous demande également de faire des
  questionnaires en ligne. Nous disposons du logiciel Modalisa qui permet
  de le réaliser mais du point de vu technique\, nous avons besoin de voir
  avec vous\,  les difficultés et les limites d\'un tel dispositif afin de
  voir les démarches à suivre et pouvoir évoquer tous ces problèmes
  techniques\, je souhaiterais vous rencontrer. Merci de me précisez vos
  disponibilités?\n...\nJe serai accompagné d\'un collègue pour
  l\'observatoire local (David Le foll) et de la chargée d\'études de
  l\'observatoire régional (Amélie Gicquel) pour la partie régionale.
END:VEVENT
END:VCALENDAR
',
                            $ical->export());
    }

    public function testQuotedParams()
    {
        $ical = new Horde_Icalendar_Vcalendar(array('version' => '1.0'));
        $event = new Horde_Icalendar_Vevent();
        $event->uid = '20041120-8550-innerjoin-org';
        $event->startDate = new Horde_Date(array('year' => 2005, 'month' => 5, 'mday' => 3));
        $event->stamp = new Horde_Date(array('year' => 2004, 'month' => 11, 'mday' => 20), 'UTC');
        $event->summary = 'Escaped Comma in Description Field';
        $event->description = 'There is a comma (escaped with a baskslash) in this sentence and some important words after it, see anything here?';
        $event->setProperty('organizer', 'mailto:mueller@example.org', array('cn' => "Klä,rc\"hen;\n Mül:ler"));
        $ical->components[] = $event;
        $this->assertEquals('BEGIN:VCALENDAR
PRODID:-//The Horde Project//Horde_Icalendar Library//EN
VERSION:2.0
BEGIN:VEVENT
UID:20041120-8550-innerjoin-org
DTSTART;VALUE=DATE:20050503
DTSTAMP:20041120T000000Z
SUMMARY:Escaped Comma in Description Field
DESCRIPTION:There is a comma (escaped with a baskslash) in this sentence
  and some important words after it\, see anything here?
ORGANIZER;CN="Klä,rchen; Mül:ler":mailto:mueller@example.org
END:VEVENT
END:VCALENDAR
',
                            $ical->export());
        /*
         $readIcal->parseVCalendar($ical->exportVCalendar());
         $event = $readIcal->getComponent(0);
         $attr = $event->getAttribute('ORGANIZER', true);
         echo $attr[0]['CN'];
         Klä,rchen; Mül:ler
        */
    }

}