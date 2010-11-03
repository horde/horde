--TEST--
Timezone handling
--FILE--
<?php

require_once dirname(__FILE__) . '/common.php';
date_default_timezone_set('UTC');

$test_files = glob(dirname(__FILE__) . '/fixtures/vTimezone/*.???');
sort($test_files);
foreach ($test_files as $file) {
    echo basename($file) . "\n";
    $ical = new Horde_Icalendar();
    $ical->parsevCalendar(file_get_contents($file));
    foreach ($ical->getComponents() as $component) {
        if ($component->getType() != 'vEvent') {
            continue;
        }
        $date = $component->getAttribute('DTSTART');
        if (is_array($date)) {
            continue;
        }
        echo $component->getAttribute('SUMMARY') . "\n";
        $d = new Horde_Date($date);
        echo $d->format('H:i') . "\n";
    }
    echo "\n";
}

?>
--EXPECT--
AuthorChats.ics
FAR Sexy Sunday Chat
14:00
Lady Aibell Chat
15:00
Lady Aibell Chat
15:00
FAR Sexy Sunday Chat
14:00
Sapphire Phelan's Birthday and She Wants to Party Chat
01:00
Bianca's Chat
15:00
Lady Aibell Chat @ Coffeetime Devin Group
15:00
Live Editor Chat
00:00
FAR Sexy Sunday Chat
14:00
Tammy Lee Author Live Chat
18:00
CPLLC Romance Authors @ Coffeetime
15:00
CPLLC Romance Authors @ Coffeetime
15:00
Ella Scopilo's Hump Day chat
01:00

MMMPseminar.ics
Jack Murphy
Trojan Horse or Proton Force: Finding the Right Partners for Toxin Translocation
18:00
Kai Matuschewski
Hitting the Plasmodium Life Cycle Early On: Attenuated Liver Stages
17:00
Felix Rey
Insights into the Mechanism of Membrane Fusion Derived from Structural Studies of Viral Envelope Proteins
18:00
Maurizio Del Poeta
Sphingolipid-Mediated Fungal Pathogenesis
18:00
Jorge Galan
Structure, Assembly, and Function of the Type III Secretion Injectisome
18:00
Don Ganem
RNAi, MicroRNAs and Viral Infection
18:00
Barak Cohen
Genomic Analysis of Natural Variation in Saccharomyces
18:00
Michael Ferguson
The Structure and Biosynthesis of Trypanosome Surface Molecules:  Basic Science and Therapeutic Possiblities
18:00
James M. Musser
Molecular Pathogenomics of Group A Streptococcus, the Flesh-Eater
17:00
Wayne Yokoyama
Innate Responses to Viral Infections
17:00
Herbert \"Skip\" Virgin
Host-Herpesvirus Standoff:  Good News From the Front in an Ancient Battle
18:00
New Event
17:00
Matthew Welch
Exploitation of the Host Actin Cytoskeleton by Bacterial and Viral Pathogens
17:00
Theresa Koeher
Virulence Gene Expression by Bacillus anthracis and Implications for the Host
17:00
Eduardo Groisman
Regulatory Networks Controlling Bacterial Physiology and Virulence
18:00
Brendan Cormack
Transcriptional Silencing and Adherence in the Yeast Pathogen Candida glabrata
17:00
Andrew Pekosz
Intracellular Transport of Viral Proteins and Particles
18:00

Moon_Days.ics
Standard Time resumes
07:00
New Moon
16:05
Full Moon
03:13
Full Moon
12:58
Full Moon
00:25
Autumnal Equinox
04:03
New Moon
22:18
Full Moon
03:02
New Moon
11:45
New Moon
20:10
Full Moon
22:54
Full Moon
18:42
New Moon
14:01
Full Moon
13:57
Summer Solstice
12:26
Full Moon
05:45
New Moon
04:01
New Moon
04:31
New Moon
05:14

ProjectCalendar.ics
Code slush begins
03:00
String freeze
03:00
Calendar Test Day
12:00
Calendar QA Chat
16:30
Status Meeting
16:00

SpanishHolidays.ics

allcategories.vcs
NSS ISDC 2006
04:00
VSE Trailer Ohio State Tour
12:00
AIAA International Energy Conversion Conference
04:00
42nd AIAA/ASME/SAE/ASEE Joint Propulsion Conference
04:00
2006 Beam Power Challenge
04:00
2006 Tether Challenge
04:00
X PRIZE Cup Spaceflight Exposition
04:00
2007 Astronaut Glove Challenge: APRIL DATE TBD
05:00
2007 Regolith Excavation Challenge
04:00
2007 Personal Air Vehicle (PAV) Challenge: DATE TBD
04:00
2008 MoonROx (Moon Regolith Oxygen) Challenge
04:00

arsenal32FC.ics
Sheffield Utd (2)0-0(4) Arsenal
20:05
Arsenal 5-3 Middlesbrough
15:05
West Ham Utd 0-0 Arsenal
14:00
Arsenal 2-1 Tottenham
15:00
Birmingham City 2-1 Arsenal
14:00
Carling Cup Final
15:00
Fulham 0-3 Arsenal
14:00
Leeds United 1-4 Arsenal
16:05
Barcelona 2-1 Arsenal
18:45
Manchester City 1-3 Arsenal
18:45
Arsenal 1-1 Aston Villa
14:00
River Plate 0-0 Arsenal
17:00
Weiz 0-5 Arsenal
17:00
Boreham Wood 0-4 Arsenal XI
18:30
Carling Cup Semi-Final (2)
20:00
Arsenal - Liverpool
16:00
Arsenal - Newcastle Utd
15:00
Arsenal 2-1 Bolton Wanderers
15:00
Charlton Athletic 1-3 Arsenal
15:00
F.A. Cup 4rth round
15:00
Southampton 1-1 Arsenal
12:45
Ritzing 2-5 Arsenal
14:00
Arsenal 3-0 Portsmouth
15:00
Beveren 0-0 Arsenal XI
17:30
Wolverhampton 1-3 Arsenal
15:00
Fulham - Arsenal
19:45
Hamburg - Arsenal
18:45
Champions League knockout round 2
19:45
Arsenal 3-2 Newcastle United
19:00
Arsenal 1-1 Manchester United
15:05
Arsenal 2-1 Manchester City
16:05
Arsenal 0-0 AFC Ajax
19:45
Arsenal 2-0 Lokomotiv Moscow
19:45
Arsenal (5)0-0(4) Manchester Utd
14:00
Lokomotiv Moscow 0-0 Arsenal
16:30
Arsenal 2-1 Chelsea
14:00
Manchester Utd 0-0 Arsenal
15:05
St Albans City 1-3 Arsenal XI
18:30
Chelsea 1-0 Arsenal
15:00
Manchester City 1-0 Arsenal
16:15
Arsenal 0-0 Manchester Utd
20:00
Blackburn Rovers 1-0 Arsenal
15:00
FC Porto - Arsenal
19:45
Bolton W 1-1 Arsenal
15:00
Aston Villa - Arsenal
15:00
Real Madrid 0-1 Arsenal
19:45
Arsenal 1-1 West Bromwich Albion
15:00
Bolton Wanderers 2-0 Arsenal
15:00
Manchester Utd - Arsenal
15:00
Arsenal - Reading
15:00
Arsenal 1-0 Manchester City
14:00
Arsenal - Chelsea
14:00
Champions League knockout round 2
19:45
AZ Alkmaar 0-3 Arsenal
17:00
Everton 1-4 Arsenal
13:00
Readling - Arsenal
15:00
Middlesbrough 2-1 Arsenal
16:15
Barnet 1-4 Arsenal
14:00
Champions League semi-final 1
18:45
CSKA Moscow - Arsenal
16:30
Arsenal 2-1 Ajax
16:00
Arsenal 1-0 Birmingham City
12:30
Arsenal 2-2 Southampton
14:00
Everton - Arsenal
15:00
Schwadorf 1-8 Arsenal
15:00
Celta de Vigo 2-3 Arsenal
19:45
Sheffield Utd - Arsenal
15:00
Arsenal 3-1 Everton
19:45
Arsenal 2-0 Newcastle
12:30
Arsenal 3-0 Charlton Athletic
15:00
Arsenal - Charlton Athletic
15:00
Arsenal - West Ham
14:00
Arsenal 2-1 Chelsea
11:30
Portsmouth 0-1 Arsenal
16:05
Arsenal 0-0 Real Madrid
19:45
F.A. Cup Semi-Final
14:00
Champions League semi-final 1
18:45
Arsenal 0-0 Fulham
14:00
Arsenal 2-1 Leicester City
14:00
Arsenal 3-1 Sunderland
15:00
Arsenal 1-0 Dynamo Kyiv
19:45
FC Utrecht 0-3 Arsenal
16:00
Chelsea 1-1 Arsenal
19:45
Manchester City 1-2 Arsenal
15:05
Man Utd 1-0 Arsenal
19:45
Arsenal 1-0 Blackburn Rovers
14:00
Wigan Athletic - Arsenal
19:45
Arsenal 3-0 Sparta Prague
19:45
Arsenal 1-1 Portsmouth
14:00
Arsenal 2-1 Dinamo Zagreb
19:05
Birmingham City 0-3 Arsenal
15:00
Champions League quarter-final 1
18:45
Barnet 0-0 Arsenal
14:00
Arsenal 2-2 Bolton Wanderers
11:45
Arsenal - Middlesbrough
14:00
Dynamo Kyiv 2-1 Arsenal
18:45
Arsenal 3-1 Aston Villa
14:00
Arsenal - Watford
14:00
Portsmouth 1-1 Arsenal
19:00
Sparta Prague 0-2 Arsenal
18:45
Bolton 0-1 Arsenal
12:15
Arsenal 1-1 Bolton Wanderers
15:00
Arsenal 3-0 Blackburn Rovers
15:00
Arsenal 3-1 ManUtd
14:00
Champions League semi-final 2
18:45
Arsenal - Wigan Athletic
15:00
Sunderland 0-3 Arsenal
16:15
Arsenal - Tottenham
12:45
Villarreal CF 0-0 Arsenal
18:45
Arsenal - Fulham
14:00
Arsenal 1-2 Chelsea
18:45
F.A. Cup 5th round
15:00
Arsenal 3-0 Reading
19:45
Arsenal 3-0 Blackburn Rovers
11:15
Liverpool 1-0 Arsenal
20:00
Ajax 0-0 Arsenal
18:45
Bolton Wanderers 1-0 Arsenal
17:15
Arsenal 2-1 Wigan Athletic
19:45
Aston Villa 0-0 Arsenal
12:45
Dinamo Zagreb 0-3 Arsenal
19:05
Newcastle Utd - Arsenal
14:00
Grazer AK 1-2 Arsenal
16:00
Carling Cup Semi-Final (1)
20:00
Everton 1-0 Arsenal
12:45
Porto 1-2 Arsenal
16:30
Bayern München 3-1 Arsenal
19:45
Arsenal 4-2 Liverpool
11:30
Arsenal 2-1 Liverpool
16:00
Barnet 1-10 Arsenal
14:00
Arsenal - Portsmouth
15:00
Watford - Arsenal
17:30
Arsenal 1(9)-1(8) Rotherham United
19:45
Manchester Utd 2-0 Arsenal
15:05
Aston Villa 0-2 Arsenal
14:00
Boreham Wood 2-6 Arsenal XI
18:30
Champions League quarter-final 2
18:45
Bolton 1-0 Arsenal
17:40
Champions League semi-final 2
18:45
West Bromwich Albion 0-2 Arsenal
19:00
Bolton Wanderers - Arsenal
17:15
Arsenal 1-1 Sheffield Utd
12:30
Arsenal - Blackburn Rovers
15:00
Carling Cup 3rd round
19:00
Arsenal 5-0 Aston Villa
14:00
Sunderland 0-3 Arsenal
18:45
Arsenal 2-1 Charlton Athletic
15:00
Arsenal 4-1 Middlesbrough
15:00
Arsenal 1-0 Newcastle
16:05
Arsenal 2-0 Fulham
13:00
KSK Beveren 2-2 Arsenal XI
16:00
Juventus 0-0 Arsenal
18:45
Arsenal 4-2 Wigan Athletic
14:00
Newcastle Utd 0-1 Arsenal
20:00
Manchester City 0-1 Arsenal
14:00
Middlesbrough 0-4 Arsenal
14:00
Champions League knockout round 1
19:45
Middlesbrough 0-4 Arsenal
15:05
Everton 1-1 Arsenal
20:00
Portsmouth - Arsenal
14:00
Tottenham Hotspur 1-1 Arsenal
12:00
Champions League quarter-final 2
18:45
Arsenal 3-0 Birmingham City
15:00
Arsenal 3-1 West Bromwich Albion
14:00
Leeds United 1-4 Arsenal
15:00
Arsenal 2-2 Chelsea
16:05
Carling Cup 4th round
20:00
NK Maribor 2-3 Arsenal
17:30
Barnet 0-0 Arsenal
14:00
Wigan Athletic 1-0 Arsenal
19:45
Norwich City 1-4 Arsenal
16:15
Portsmouth 1-5 Arsenal
18:00
Blackburn Rovers 0-2 Arsenal
15:00
Tottenham - Arsenal
14:00
Liverpool - Arsenal
14:00
Arsenal 2-1 FC Thun
18:45
Blackburn Rovers - Arsenal
15:00
Arsenal 3-0 Blackburn Rovers
18:45
Arsenal 2-0 Aston Villa
18:45
Middlesbrough - Arsenal
15:00
Arsenal 2-1 Cardiff City
13:00
Arsenal 3-1 Liverpool
15:05
Champions League quarter-final 1
18:45
Arsenal 0-1 Manchester Utd
11:00
Middlesbrough 0-1 Arsenal
14:00
FC Thun 0-1 Arsenal
19:45
Panathinaikos 2-2 Arsenal
18:45
West Ham Utd - Arsenal
13:30
Charlton Athletic 1-1 Arsenal
14:00
Arsenal - Everton
14:00
Arsenal 7-0 Middlesbrough
15:00
Newcastle Utd 0-0 Arsenal
15:05
PSV Eindhoven 1-1 Arsenal
19:45
Charlton Athletic - Arsenal
14:00
Arsenal 2-0 Juventus
18:45
F.A. Cup 6th round
15:00
Arsenal - Hamburg
19:45
Portsmouth 1-1 Arsenal
19:00
West Bromwich Albion 0-2 Arsenal
20:00
Champions League knockout round 1
19:45
Arsenal 1-1 Tottenham Hotspur
11:45
Arsenal 2-1 Stoke City
14:00
Birmingham City 0-2 Arsenal
15:00
Arsenal 2-0 Southampton
19:45
Arsenal 2-3 West Ham Utd
20:00
Arsenal 0-2 Chelsea
16:00
Southampton 0-1 Arsenal
20:00
Arsenal 1-1 Manchester City
19:45
Tottenham 2-2 Arsenal
15:05
Leicester City 1-1 Arsenal
15:00
Arsenal 4-1 Middlesbrough
15:00
Tottenham Hotspur 4-5 Arsenal
12:00
Arsenal 1-0 Tottenham Hotspur
19:00
Newcastle Utd 1-0 Arsenal
17:15
Arsenal 1(3)-1(4) ManUtd
13:00
Inter Milan 1-5 Arsenal
19:45
West Bromwich Albion 2-1 Arsenal
14:00
Blackburn Rovers 0-1 Arsenal
12:45
Manchester Utd 2-0 Arsenal
15:00
Austria Vienna 0-2 Arsenal
18:00
Arsenal 2-1 Everton
14:00
Arsenal 3-0 Wolverhampton W
12:00
AFC Ajax 1-2  Arsenal
18:45
Arsenal - Sheffield Utd
14:00
Manchester City 1-2 Arsenal
18:45
Arsenal 7-0 Everton
19:00
Crystal Palace 1-1 Arsenal
17:15
Arsenal 1-0 Besiktas
17:00
Arsenal - FC Porto
18:45
Rangers 0-3 Arsenal
18:45
Liverpool 2-1 Arsenal
16:05
Arsenal 1-0 PSV Eindhoven
18:45
Liverpool 1-2 Arsenal
11:30
Arsenal - Manchester Utd
15:00
Charlton Athletic 0-1 Arsenal
12:45
Arsenal 1-0 Bayern München
19:45
Arsenal 5-1 Rosenborg
19:45
Chelsea 2-1 Arsenal
14:00
Champions League Final
18:45
Arsenal 0-3 Inter Milan
18:45
Fulham 0-1 Arsenal
15:05
Arsenal 0-1 Middlesbrough
19:45
Arsenal - CSKA Moscow
19:45
Arsenal 1-1 Panathinaikos
19:45
Chelsea - Arsenal
16:00
Aston Villa 1-3 Arsenal
17:15
Arsenal 2-0 Celta de Vigo
19:45
Arsenal 5-0 Leeds
19:00
Arsenal 5-1 Crystal Palace
20:00
Fulham 0-4 Arsenal
15:00
Arsenal 4-0 Portsmouth
19:45
Arsenal - Manchester City
19:45
Ritzing 2-2 Arsenal
18:00
Arsenal 2-4 Manchester Utd
20:00
Arsenal - Bolton Wanderers
14:00
Arsenal 1-0 Villarreal CF
18:45
Arsenal 5-1 Wolverhampton W.
19:45
Celtic 1-1 Arsenal
14:00
F.A. Cup Final
14:00
Arsenal 2-0 Wolverhampton Wanderers
15:00
Peterborough 1-0 Arsenal XI
18:30
Arsenal 2-0 Everton
19:00
Arsenal 4-0 Charlton Athletic
14:00
Arsenal 4-1 Fulham
18:45
Chelsea 0-0 Arsenal
19:00
Doncaster Rovers 2-2P Arsenal
19:45
Sturm Graz 0-2 Arsenal
16:30
Rosenborg 1-1 Arsenal
18:45
Arsenal 6-0 England XI
18:45
Arsenal 4-1 Norwich City
14:00
Arsenal 0-0 Birmingham City
11:30
Middlesbrough 2-1 Arsenal
20:00
Ajax 0-1 Arsenal
19:15
Chelsea 1-2 Arsenal
12:30
Boreham Wood 1-1 Arsenal XI
18:45
Carling Cup 5th round
20:00
KSK Beveren 3-3 Arsenal XI
18:30
Wigan Athletic 2-3 Arsenal
12:45
F.A. Cup 3rd round
15:00
SV Mattersburg 1-2 Arsenal
17:00

events.ics
test cet 2
13:00

exchange.ics
internal final review of mmc site changes
19:00

exdate.ics

iscw.ics
Ontoweb Day http://nextwebgeneration.com/meetings/ontoweb5/
13:30
Ontoweb SIG day http://nextwebgeneration.com/meetings/ontoweb5/
13:30
Beach Barbeque
00:00

meeting.ics
Updated: Webex Training - Encryption Push
17:00

privacy_events.ics
Australian Smart Card Summit
04:00
SOCAP Australia 2006 Symposium
03:30

rfc2445.ics
Bastille Day Party
17:00
Annual Employee Review
16:30
Laurel is in sensitivity awareness class.
16:30
Our Blissful Anniversary
00:00
XYZ Project Review
13:30
Calendaring Interoperability Planning Meeting
12:30

test.vcs
2007 Astronaut Glove Challenge: APRIL DATE TBD
05:00

test4.vcs
Chiefs vs. Buffalo @ Arrowhead Stadium
19:00

test_recurring.vcs
XXXStriked out XXX
09:00

wicca.ics
Wolf Moon
10:32
Snow Moon
04:54
Worm Moon
20:58
Pink Moon
10:06
Flower Moon
20:18
Strawberry Moon
04:14
Buck Moon
11:00
Sturgeon Moon
17:53
Harvest Moon
02:01
Hunter's Moon
12:14
Beaver Moon
00:57
Cold Moon
16:15
