--TEST--
Line folding tests.
--FILE--
<?php

require_once dirname(__FILE__) . '/common.php';

$ical = new Horde_Icalendar();
$event = Horde_Icalendar::newComponent('vevent', $ical);
$event->setAttribute('UID', 'XXX');
$event->setAttribute('DTSTART', array('year' => 2008, 'month' => 1, 'mday' => 1), array('VALUE' => 'DATE'));
$event->setAttribute('DTSTAMP', array('year' => 2008, 'month' => 1, 'mday' => 1), array('VALUE' => 'DATE'));
$event->setAttribute('DESCRIPTION', 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aliquam sollicitudin faucibus mauris amet.');
$ical->addComponent($event);
echo $ical->exportVCalendar();
echo "\n";

$ical = new Horde_Icalendar('1.0');
$event = Horde_Icalendar::newComponent('vevent', $ical);
$event->setAttribute('UID', 'XXX');
$event->setAttribute('DTSTART', array('year' => 2008, 'month' => 1, 'mday' => 1), array('VALUE' => 'DATE'));
$event->setAttribute('DTSTAMP', array('year' => 2008, 'month' => 1, 'mday' => 1), array('VALUE' => 'DATE'));
$event->setAttribute('DESCRIPTION', 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aliquam sollicitudin faucibus mauris amet.');
$ical->addComponent($event);
echo $ical->exportVCalendar();
echo "\n";

$ical = new Horde_Icalendar();
$event = Horde_Icalendar::newComponent('vevent', $ical);
$event->setAttribute('UID', 'XXX');
$event->setAttribute('DTSTART', array('year' => 2008, 'month' => 1, 'mday' => 1), array('VALUE' => 'DATE'));
$event->setAttribute('DTSTAMP', array('year' => 2008, 'month' => 1, 'mday' => 1), array('VALUE' => 'DATE'));
$event->setAttribute('DESCRIPTION', 'Lörem ipsüm dölör sit ämet, cönsectetüer ädipiscing elit. Aliqüäm söllicitüdin fäücibüs mäüris ämet.');
$ical->addComponent($event);
echo $ical->exportVCalendar();
echo "\n";

$ical = new Horde_Icalendar('1.0');
$event = Horde_Icalendar::newComponent('vevent', $ical);
$event->setAttribute('UID', 'XXX');
$event->setAttribute('DTSTART', array('year' => 2008, 'month' => 1, 'mday' => 1), array('VALUE' => 'DATE'));
$event->setAttribute('DTSTAMP', array('year' => 2008, 'month' => 1, 'mday' => 1), array('VALUE' => 'DATE'));
$event->setAttribute('DESCRIPTION', 'Lörem ipsüm dölör sit ämet, cönsectetüer ädipiscing elit. Aliqüäm söllicitüdin fäücibüs mäüris ämet.', array('CHARSET' => 'UTF-8'));
$ical->addComponent($event);
echo $ical->exportVCalendar();
echo "\n";

$ical = new Horde_Icalendar('1.0');
$event = Horde_Icalendar::newComponent('vevent', $ical);
$event->setAttribute('UID', 'XXX');
$event->setAttribute('DTSTART', array('year' => 2008, 'month' => 1, 'mday' => 1), array('VALUE' => 'DATE'));
$event->setAttribute('DTSTAMP', array('year' => 2008, 'month' => 1, 'mday' => 1), array('VALUE' => 'DATE'));
$event->setAttribute('DESCRIPTION', 'Löremipsümdölörsitämet,cönsectetüerädipiscingelit.Aliqüämsöllicitüdinfäücibüsmäürisämet. Löremipsümdölörsitämet,cönsectetüerädipiscingelit.Aliqüämsöllicitüdinfäücibüsmäürisämet.', array('CHARSET' => 'UTF-8'));
$ical->addComponent($event);
echo $ical->exportVCalendar();
echo "\n";

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
echo $ical->exportVCalendar();

?>
--EXPECT--
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//The Horde Project//Horde iCalendar Library//EN
METHOD:PUBLISH
BEGIN:VEVENT
UID:XXX
DTSTART;VALUE=DATE:20080101
DTSTAMP;VALUE=DATE:20080101
DESCRIPTION:Lorem ipsum dolor sit amet\, consectetuer adipiscing elit.
  Aliquam sollicitudin faucibus mauris amet.
SUMMARY:
END:VEVENT
END:VCALENDAR

BEGIN:VCALENDAR
VERSION:1.0
PRODID:-//The Horde Project//Horde iCalendar Library//EN
METHOD:PUBLISH
BEGIN:VEVENT
UID:XXX
DTSTART:20080101T000000
DTSTAMP:20080101T000000
DESCRIPTION:Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aliquam sollicitudin faucibus mauris amet.
SUMMARY:
END:VEVENT
END:VCALENDAR

BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//The Horde Project//Horde iCalendar Library//EN
METHOD:PUBLISH
BEGIN:VEVENT
UID:XXX
DTSTART;VALUE=DATE:20080101
DTSTAMP;VALUE=DATE:20080101
DESCRIPTION:Lörem ipsüm dölör sit ämet\, cönsectetüer ädipiscing elit.
  Aliqüäm söllicitüdin fäücibüs mäüris ämet.
SUMMARY:
END:VEVENT
END:VCALENDAR

BEGIN:VCALENDAR
VERSION:1.0
PRODID:-//The Horde Project//Horde iCalendar Library//EN
METHOD:PUBLISH
BEGIN:VEVENT
UID:XXX
DTSTART:20080101T000000
DTSTAMP:20080101T000000
DESCRIPTION;CHARSET=UTF-8;ENCODING=QUOTED-PRINTABLE:L=C3=B6rem ips=C3=BCm d=C3=B6l=C3=B6r sit =C3=A4met, c=C3=B6nsectet=C3=BCer =
=C3=A4dipiscing elit. Aliq=C3=BC=C3=A4m s=C3=B6llicit=C3=BCdin f=C3=A4=C3=BC=
cib=C3=BCs m=C3=A4=C3=BCris =C3=A4met.
SUMMARY:
END:VEVENT
END:VCALENDAR

BEGIN:VCALENDAR
VERSION:1.0
PRODID:-//The Horde Project//Horde iCalendar Library//EN
METHOD:PUBLISH
BEGIN:VEVENT
UID:XXX
DTSTART:20080101T000000
DTSTAMP:20080101T000000
DESCRIPTION;CHARSET=UTF-8;ENCODING=QUOTED-PRINTABLE:L=C3=B6remips=C3=BCmd=C3=B6l=C3=B6rsit=C3=A4met,c=C3=B6nsectet=C3=BCer=C3=A4=
dipiscingelit.Aliq=C3=BC=C3=A4ms=C3=B6llicit=C3=BCdinf=C3=A4=C3=BCcib=C3=BCs=
m=C3=A4=C3=BCris=C3=A4met. L=C3=B6remips=C3=BCmd=C3=B6l=C3=B6rsit=C3=A4met,c=
=C3=B6nsectet=C3=BCer=C3=A4dipiscingelit.Aliq=C3=BC=C3=A4ms=C3=B6llicit=C3=
=BCdinf=C3=A4=C3=BCcib=C3=BCsm=C3=A4=C3=BCris=C3=A4met.
SUMMARY:
END:VEVENT
END:VCALENDAR

BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//The Horde Project//Horde iCalendar Library//EN
METHOD:PUBLISH
BEGIN:VEVENT
UID:XXX
DTSTART;VALUE=DATE:20080101
DTSTAMP;VALUE=DATE:20080101
DESCRIPTION:SYLVIE DAGORNE a écrit :\n\nBonjour\,\n\nsuite à mon appel
  téléphonique auprès de Jacques Benzerara\, il m'a renvoyé vers vous. En
  effet\, je souhaiterais vous rencontrer car:\n1°) au niveau de
  l'observatoire local nous devons lancer une enquête sur un suivi de
  cohorte à la rentrée prochaine qui concernera tous les étudiants de L1.
  Nous souhaiterons faire un questionnaire en ligne ce questionnaire devra
  être hébergé sur un serveur.\n\n2°) dans le cadre de l'observatoire
  régional\, nos partenaires nous demande également de faire des
  questionnaires en ligne. Nous disposons du logiciel Modalisa qui permet
  de le réaliser mais du point de vu technique\, nous avons besoin de voir
  avec vous\,  les difficultés et les limites d'un tel dispositif afin de
  voir les démarches à suivre et pouvoir évoquer tous ces problèmes
  techniques\, je souhaiterais vous rencontrer. Merci de me précisez vos
  disponibilités?\n...\nJe serai accompagné d'un collègue pour
  l'observatoire local (David Le foll) et de la chargée d'études de
  l'observatoire régional (Amélie Gicquel) pour la partie régionale.
SUMMARY:
END:VEVENT
END:VCALENDAR
