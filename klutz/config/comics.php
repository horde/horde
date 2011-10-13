<?php
/**
 * Comics configuration file.
 */

$comics = array(
    /*
    'w00t' => array(
        'name'      => '/usr/bin/w00t',
        'author'    => 'Chaobell',
        'method'    => 'direct',
        'days'      => 'random',
        'url'       => 'http://www.w00t-comic.net/images/strip/{%Y%m%d}.png',
        'homepage'  => 'http://www.w00t-comic.net/',
        'enabled'   => true
    ),
    */

    'ntf' => array(
        'name'      => '9 to 5',
        'author'    => 'Harley Schwadron',
        'method'    => 'direct',
        'url'       => 'http://images.ucomics.com/comics/tmntf/{%Y}/tmntf{%y%m%d}.gif',
        'enabled'   => true
    ),

    'unionave' => array(
        'name'      => '44 Union Ave',
        'author'    => 'Mike Witmer',
        'method'    => 'direct',
        'url'       => 'http://images.ucomics.com/comics/fua/{%Y}/fua{%y%m%d}.gif',
        'homepage'  => 'http://www.44unionavenue.com',
        'enabled'   => true,
    ),

    'adamathome' => array(
        'name'      => 'Adam@Home',
        'author'    => 'Brian Basset',
        'method'    => 'direct',
        'url'       => 'http://images.ucomics.com/comics/ad/{%Y}/ad{%y%m%d}.gif',
        'homepage'  => 'http://www.ucomics.com/adamathome/',
        'enabled'   => true
    ),

    'ace' => array(
        'name'      => 'The Adventures of ACE, DBA',
        'author'    => 'Steve Karam',
        'homepage'  => 'http://www.orcldba.com/ace',
        'method'    => 'search',
        'url'       => 'http://www.orcldba.com/ace/{%Y}/{%m}/{%d}/',
        'search'    => 'src\s*=\s*\S*(/ace/comics/[0-9a-zA-Z\-.]+.\w{3})',
        'enabled'   => true,
        'days'      => 'random',
    ),

    'agnes' => array(
        'name'      => 'Agnes',
        'author'    => 'Tony Cochran',
        'method'    => 'search',
        'url'       => 'http://comics.com/agnes/{%Y-%m-%d}/',
        'search'    => 'src\s*=\s*[\'"](http://.*/dyn/str_strip/\d+.full.gif)',
        'homepage'  => 'http://comics.com/agnes/',
        'enabled'   => true
    ),

    'andycapp' => array(
        'name'      => 'Andy Capp',
        'author'    => 'Reg Smythe',
        'method'    => 'search',
        'url'       => 'http://comics.com/andy_capp/{%Y-%m-%d}/',
        'search'    => 'src\s*=\s*[\'"](http://.*/dyn/str_strip/\d+.full.gif)',
        'homepage'  => 'http://comics.com/andy_capp/',
        'enabled'   => true
    ),

    'angst' => array(
        'name'      => 'Angst Technology',
        'author'    => 'Barry T. Smith',
        'method'    => 'direct',
        'days'      => 'random',
        'url'       => 'http://www.angsttechnology.com/images/AT/cartoons/{%m-%d-%y}.gif',
        'homepage'  => 'http://www.angsttechnology.com/',
        'enabled'   => false
    ),

    'argyle' => array(
        'name'      => 'The Argyle Sweater',
        'author'    => 'Scott Hilburn',
        'method'    => 'direct',
        'url'       => 'http://images.ucomics.com/comics/tas/{%Y}/tas{%y%m%d}.gif',
        'enabled'   => true
    ),

    'arlonjanis' => array(
        'name'      => 'Arlo & Janis',
        'author'    => 'Jimmy Johnson',
        'method'    => 'search',
        'url'       => 'http://comics.com/arlo&janis/{%Y-%m-%d}/',
        'search'    => 'src\s*=\s*[\'"](http://.*/dyn/str_strip/\d+.full.gif)',
        'homepage'  => 'http://comics.com/arlo&janis/',
        'enabled'   => true
    ),

    'babyblues' => array(
        'name'      => 'Baby Blues',
        'author'    => 'Jerry Scott and Rick Kirkman',
        'method'    => 'direct',
        'url'       => 'http://est.rbma.com/content/Baby_Blues?date={%Y%m%d}',
        'referer'   => 'http://www.kingfeatures.com/features/comics/babyblue/aboutMaina.php',
        'homepage'  => 'http://www.kingfeatures.com/features/comics/babyblue/about.htm',
        'enabled'   => true
    ),

    'ballard' => array(
        'name'      => 'Ballard Street',
        'author'    => 'Jerry Van Amerongen',
        'method'    => 'search',
        'url'       => 'http://comics.com/ballard_street/{%Y-%m-%d}/',
        'search'    => 'src\s*=\s*[\'"](http://.*/dyn/str_strip/\d+.full.gif)',
        'homepage'  => 'http://comics.com/ballard_street/',
        'enabled'   => true
    ),

    'bc' => array(
        'name'      => 'B.C.',
        'author'    => 'Johnny Hart',
        'method'    => 'search',
        'url'       => 'http://comics.com/bc/{%Y-%m-%d}/',
        'search'    => 'src\s*=\s*[\'"](http://.*/dyn/str_strip/\d+.full.gif)',
        'homepage'  => 'http://comics.com/bc/',
        'enabled'   => true
    ),

    'beetlebailey' => array(
        'name'      => 'Beetle Bailey',
        'author'    => 'Mort Walker',
        'method'    => 'direct',
        'url'       => 'http://est.rbma.com/content/Beetle_Bailey?date={%Y%m%d}',
        'referer'   => 'http://www.kingfeatures.com/features/comics/bbailey/aboutMaina.php',
        'homepage'  => 'http://www.kingfeatures.com/features/comics/bbailey/about.htm',
        'enabled'   => true
    ),

    'day' => array(
        'name'      => 'Bill Day',
        'author'    => 'Bill Day',
        'method'    => 'search',
        'days'      => 'random',
        'url'       => 'http://www.comics.com/editoons/day/archive/day-{%Y%m%d}.html',
        'search'    => 'src\s*=\s*\S*(/editoons/[a-zA-Z0-9\/.]+/day\d+.\w{3})',
        'homepage'  => 'http://www.comics.com/editoons/day/',
        'enabled'   => true
    ),

    'schorr' => array(
        'name'      => 'Bill Schorr',
        'author'    => 'Bill Schorr',
        'method'    => 'search',
        'days'      => 'random',
        'url'       => 'http://www.comics.com/editoons/schorr/archive/schorr-{%Y%m%d}.html',
        'search'    => 'src\s*=\s*\S*(/editoons/[a-zA-Z0-9\/.]+/schorr\d+.\w{3})',
        'homepage'  => 'http://www.comics.com/editoons/schorr/',
        'enabled'   => true
    ),

    'bizarro' => array(
        'name'      => 'Bizarro',
        'author'    => 'Dan Piraro',
        'method'    => 'direct',
        'url'       => 'http://est.rbma.com/content/Bizarro?date={%Y%m%d}',
        'referer'   => 'http://www.kingfeatures.com/features/comics/bizarro/aboutMaina.php',
        'homepage'  => 'http://www.kingfeatures.com/features/comics/bizarro/about.htm',
        'enabled'   => true
    ),

    'boondocks' => array(
        'name'      => 'The Boondocks',
        'author'    => 'Aaron McGruder',
        'method'    => 'direct',
        'url'       => 'http://images.ucomics.com/comics/bo/{%Y}/bo{%y%m%d}.gif',
        'homepage'  => 'http://www.ucomics.com/boondocks/',
        'enabled'   => true
    ),

    'bornloser' => array(
        'name'      => 'The Born Loser',
        'author'    => 'Chip Sansom',
        'method'    => 'search',
        'url'       => 'http://comics.com/the_born_loser/{%Y-%m-%d}/',
        'search'    => 'src\s*=\s*[\'"](http://.*/dyn/str_strip/\d+.full.gif)',
        'homepage'  => 'http://comics.com/the_born_loser/',
        'enabled'   => true
    ),

    'beattie' => array(
        'name'      => 'Bruce Beattie',
        'author'    => 'Bruce Beattie',
        'method'    => 'direct',
        'days'      => 'random',
        'url'       => 'http://www.news-journalonline.com/downloads/{%m%d%y}beat.gif',
        'homepage'  => 'http://www.news-journalonline.com/column/beattie',
        'enabled'   => true
    ),

    'brainwaves' => array(
        'name'      => 'Brain Waves',
        'author'    => 'Betsy Streeter',
        'method'    => 'direct',
        'url'       => 'http://images.ucomics.com/comics/bwv/{%Y}/bwv{%y%m%d}.gif',
        'enabled'   => true
    ),

    'calvin' => array(
        'name'      => 'Calvin and Hobbes',
        'author'    => 'Watterson',
        'method'    => 'direct',
        'url'       => 'http://images.ucomics.com/comics/ch/{%Y}/ch{%y%m%d}.gif',
        'homepage'  => 'http://www.calvinandhobbes.com/',
        'enabled'   => true
    ),

    'cathy' => array(
        'name'      => 'Cathy',
        'author'    => 'Cathy Guisewite',
        'method'    => 'direct',
        'url'       => 'http://images.ucomics.com/comics/ca/{%Y}/ca{%y%m%d}.gif',
        'homepage'  => 'http://www.ucomics.com/cathy/',
        'enabled'   => true
    ),

    'catswithhands' => array(
        'name'      => 'Cats With Hands',
        'author'    => 'Joe Martin',
        'method'    => 'direct',
        'url'       => 'http://www.mrboffo.com/comicsweb/catswithhandsweb/strips/{%Y%m%d}cwh_s_web.jpg',
        'homepage'  => 'http://www.mrbuffo.com',
        'enabled'   => true,
    ),

    'bok' => array(
        'name'      => 'Chip Bok',
        'author'    => 'Chip Bok',
        'method'    => 'search',
        'days'      => 'random',
        'url'       => 'http://www.comics.com/editoons/bok/archive/bok-{%Y%m%d}.html',
        'search'    => 'src\s*=\s*\S*(/editoons/[a-zA-Z0-9\/.]+/bok\d+.\w{3})',
        'homepage'  => 'http://www.comics.com/editoons/bok/',
        'enabled'   => true
    ),

    'closetohome' => array(
        'name'      => 'Close to Home',
        'author'    => 'Glen McPherson',
        'method'    => 'direct',
        'url'       => 'http://images.ucomics.com/comics/cl/{%Y}/cl{%y%m%d}.gif',
        'homepage'  => 'http://www.ucomics.com/closetohome/viewcl.htm',
        'enabled'   => true
    ),

    'ctrl-alt-delete' => array(
        'name'      => 'Ctrl-Alt-Delete',
        'author'    => 'Tim Buckley',
        'method'    => 'direct',
        'url'       => 'http://www.ctrlaltdel-online.com/comics/{%Y%m%d}.jpg',
        'homepage'  => 'http://www.ctrlaltdel-online.com/index.php',
        'days'      => 'random',
        'enabled'   => true
    ),

    'cadsillies' => array(
        'name'      => 'Ctrl-Alt-Delete Sillies',
        'author'    => 'Tim Buckley',
        'method'    => 'direct',
        'url'       => 'http://www.ctrlaltdel-online.com/comics/Lite{%Y%m%d}.jpg',
        'homepage'  => 'http://www.ctrlaltdel-online.com/index.php',
        'days'      => 'random',
        'enabled'   => true
    ),

    'cornered' => array(
        'name'      => 'Cornered',
        'author'    => 'Mike Baldwin',
        'method'    => 'direct',
        'url'       => 'http://images.ucomics.com/comics/co/{%Y}/co{%y%m%d}.gif',
        'homepage'  => 'http://www.cornered.com/',
        'enabled'   => true
    ),

    // Last strip 6/28/05
    'catrow' => array(
        'name'      => 'David Catrow',
        'author'    => 'David Catrow',
        'method'    => 'direct',
        'days'      => 'random',
        'url'       => 'http://cagle.slate.msn.com/working/{%y%m%d}/catrow.gif',
        'homepage'  => 'http://dailynews.yahoo.com/h/cx/ipipe/ipipecatrow/',
        'comment'   => 'This comic is not produced on a regular schedule.  It may or may not be available on any given day.',
        'enabled'   => false
    ),

    'dennismenace' => array(
        'name'      => 'Dennis the Menace',
        'author'    => 'Marcus Hamilton and Ron Ferdinand, Created by Hank Ketcham',
        'method'    => 'direct',
        'url'       => 'http://est.rbma.com/content/Dennis_The_Menace?date={%Y%m%d}',
        'referer'   => 'http://www.kingfeatures.com/features/comics/dennis/aboutMaina.php',
        'homepage'  => 'http://www.kingfeatures.com/features/comics/dennis/about.htm',
        'enabled'   => true
    ),

    'dilbert' => array(
        'name'      => 'Dilbert',
        'author'    => 'Scott Adams',
        'method'    => 'search',
        'url'       => 'http://www.dilbert.com/strips/comic/{%Y-%m-%d}/',
        'search'    => '["\']([^"\']*/dyn/str_strip/[0-9/]+\.strip\.print\.gif)',
        'homepage'  => 'http://www.dilbert.com/',
        'enabled'   => true
    ),

    'domesticabuse' => array(
        'name'      => 'Domestic Abuse',
        'author'    => 'Jeremy Lambros',
        'url'       => 'http://images.ucomics.com/comics/dom/{%Y}/dom{%y%m%d}.jpg',
        'method'    => 'direct',
        'enabled'   => true
    ),

    'doonesbury' => array(
        'name'      => 'Doonesbury',
        'author'    => 'Gary Trudeau',
        'method'    => 'direct',
        'url'       => 'http://images.ucomics.com/comics/db/{%Y}/db{%y%m%d}.gif',
        'homepage'  => 'http://www.doonesbury.com/',
        'enabled'   => true
    ),

    'dorktower' => array(
        'name'      => 'Dork Tower',
        'author'    => 'John Kovalic',
        'method'    => 'search',
        'days'      => 'random',
        'url'       => 'http://www.dorktower.com/{%Y/%m/%d}/?catid=6',
        'search'    => 'img\s+src=[\'"]?([^"\'>]+?images/comics[^"\'>]+?)[\'"> ]',
        'subs'      => array('url'),
        'homepage'  => 'http://www.dorktower.com/',
        'enabled'   => true
    ),

    'duplex' => array(
        'name'      => 'The Duplex',
        'author'    => 'Glenn McCoy',
        'method'    => 'direct',
        'url'       => 'http://images.ucomics.com/comics/dp/{%Y}/dp{%y%m%d}.gif',
        'homepage'  => 'http://www.ucomics.com/duplex/viewdp.htm',
        'enabled'   => true
    ),

    // This needs to use    => 'ref' stuff, but only appears once about every
    // four weeks or so.  Still need to figure this one out.
    /*'dykes' => array(
        'name'      => 'Dykes To Watch Out For',
        'author'    => 'Alison Bechdel',
        'method'    => 'search',
        'days'      => 'random',
        'url'       => 'http://www.planetout.com/entertainment/comics/',
        'search'    => array('(/entertainment/comics/dtwof/archive/\d+.html)',
                             'SRC=.*?(/images/newsplanet/comics/new_size/dtwof/\d+.gif)'),
        'nohistory' => true,
        'homapage'  => 'http://www.dykestowatchoutfor.com/',
        'enabled'   => true
    ),*/

    'edgecity' => array(
        'name'      => 'Edge City',
        'author'    => 'Terry and Patty Laban',
        'method'    => 'direct',
        'url'       =>  'http://pst.rbma.com/content/Edge_City?date={%Y%m%d}&size=hires',
        'referer'   =>  'http://seattlepi.nwsource.com/fun/edgecity.asp',
        'enabled'   =>   true
    ),

    'familycircus' => array(
        'name'      => 'Family Circus',
        'author'    => 'Bil Keane',
        'method'    => 'direct',
        'days'      => array('mon', 'tue', 'wed', 'thu', 'fri', 'sat'),
        'url'       => 'http://est.rbma.com/content/Family_Circus?date={%Y%m%d}',
        'referer'   => 'http://www.kingfeatures.com/features/comics/familyc/aboutMaina.php',
        'homepage'  => 'http://www.kingfeatures.com/features/comics/familyc/about.htm',
        'enabled'   => true
    ),

    'fifthwave' => array(
        'name'      => 'The 5th Wave',
        'author'    => 'Rich Tennant',
        'method'    => 'direct',
        'days'      => array('Sun'),
        'url'       => 'http://images.ucomics.com/comics/fw/{%Y}/fw{%y%m%d}.gif',
        'homepage'  => 'http://www.ucomics.com/thefifthwave/',
        'enabled'   => true
    ),

    'flightdeck' => array(
        'name'      => 'Flight Deck',
        'author'    => 'Peter Waldner',
        'method'    => 'direct',
        'url'       => 'http://images.ucomics.com/comics/crfd/{%Y}/crfd{%y%m%d}.gif',
        'enabled'   => true
    ),

    'fminus' => array(
        'name'      => 'F Minus',
        'author'    => 'Tony Carrillo',
        'method'    => 'search',
        'url'       => 'http://comics.com/f_minus/{%Y-%m-%d}/',
        'search'    => 'src\s*=\s*[\'"](http://.*/dyn/str_strip/\d+.full.gif)',
        'homepage'  => 'http://comics.com/f_minus/',
        'enabled'   => true
    ),

    'forbetter' => array(
        'name'      => 'For Better or for Worse',
        'author'    => 'Lynn Johnston',
        'method'    => 'direct',
        'url'       => 'http://www.uclick.com/feature/{%y}/{%m}/{%d}/fb{%y%m%d}.gif',
        'homepage'  => 'http://www.fborfw.com/',
        'enabled'   => true
    ),

    'foxtrot' => array(
        'name'      => 'Fox Trot',
        'days'      => array('Sun'),
        'author'    => 'Bill Amend',
        'method'    => 'direct',
        'url'       => 'http://images.ucomics.com/comics/ft/{%Y}/ft{%y%m%d}.gif',
        'homepage'  => 'http://www.ucomics.com/foxtrot/',
        'enabled'   => true
    ),

    'fne' => array(
        'name'      => 'Frank and Ernest',
        'author'    => 'Bob Thaves',
        'method'    => 'search',
        'url'       => 'http://www.unitedmedia.com/comics/franknernest/archive/franknernest-{%Y%m%d}.html',
        'search'    => 'SRC\s*=\s*\S*(/comics/franknernest/archive/images/franknernest\d+.\w{3})',
        'homepage'  => 'http://aolsvc.toonville.aol.com/main.asp?fnum=126',
        'enabled'   => true
    ),

    'garfield' => array(
        'name'      => 'Garfield',
        'author'    => 'Jim Davis',
        'method'    => 'direct',
        'url'       => 'http://images.ucomics.com/comics/ga/{%Y}/ga{%y%m%d}.gif',
        'homepage'  => 'http://www.ucomics.com/garfield/',
        'enabled'   => true
    ),

    'garfieldminus' => array(
        'name'      => 'Garfield Minus Garfield',
        'author'    => 'Dan Walsh (and Jim Davis)',
        'homepage'  => 'http://garfieldminusgarfield.net/',
        'url'       => 'http://garfieldminusgarfield.net/day/{%Y/%m/%d}',
        'method'    => 'search',
        'search'    => '<img\s+src=\S?(http://.*?media.tumblr.com/[^\.]+?\.\w{3})',
        'days'      => array('mon', 'wed', 'fri'),
        'enabled'   => true
    ),

    'markstein' => array(
        'name'      => 'Gary Markstein',
        'author'    => 'Gary Markstein',
        'method'    => 'direct',
        'days'      => 'random',
        'url'       => 'http://www.jsonline.com/news/editorials/cartoons{%y}/marksteinbig{%m%d%y}.jpg',
        'homepage'  => 'http://www.jsonline.com/news/editorials/markedits.asp',
        'comment'   => 'This comic is not produced on a regular schedule.  It may or may not be available on any given day.',
        'enabled'   => true
    ),

    'gasolinealley' => array(
        'name'      => 'Gasoline Alley',
        'author'    => 'Jim Scancarelli',
        'method'    => 'direct',
        'url'       => 'http://images.ucomics.com/comics/tmgas/{%Y}/tmgas{%y%m%d}.gif',
        'homepage'  => 'http://www.ucomics.com/gasolinealley/',
        'enabled'   => true
    ),

    'geech' => array(
        'name'      => 'Geech',
        'author'    => 'Jerry Bittle',
        'method'    => 'search',
        'url'       => 'http://comics.com/geech_classics/{%Y-%m-%d}/',
        'search'    => 'src\s*=\s*[\'"](http://.*/dyn/str_strip/\d+.full.gif)',
        'homepage'  => 'http://comics.com/geech_classics/',
        'enabled'   => true
    ),

    'getfuzzy' => array(
        'name'      => 'Get Fuzzy',
        'author'    => 'Darby Conley',
        'method'    => 'search',
        'url'       => 'http://comics.com/get_fuzzy/{%Y-%m-%d}/',
        'search'    => 'src\s*=\s*[\'"](http://.*/dyn/str_strip/\d+.full.gif)',
        'homepage'  => 'http://comics.com/get_fuzzy/',
        'enabled'   => true
    ),

    'mccoy' => array(
        'name'      => 'Glenn McCoy',
        'author'    => 'Glenn McCoy',
        'method'    => 'direct',
        'days'      => 'random',
        'url'       => 'http://images.ucomics.com/comics/gm/{%Y}/gm{%y%m%d}.gif',
        'homepage'  => 'http://www.ucomics.com/glennmccoy',
        'enabled'   => true
    ),

    'heart'    => array(
        'name'      => 'Heart of the City',
        'author'    => 'Mark Tatulli',
        'method'    => 'direct',
        'url'       => 'http://images.ucomics.com/comics/hc/{%Y}/hc{%y%m%d}.gif',
        'enabled'   => true
    ),

    'helpdesk' => array(
        'name'      => 'Help Desk',
        'method'    => 'direct',
        'author'    => 'Christopher B. Wright',
        'url'       => 'http://ubersoft.net/files/comics/hd/hd{%Y%m%d}.png',
        'comment'   => 'Help Desk is a comic strip that I draw and write that focuses on Alex, a top-rated employee in the technical support division of Ubersoft, a very very evil computer company.',
        'homepage'  => 'http://ubersoft.net/comic/hd',
        'enabled'   => true,
        'days'      => array('mon', 'tue', 'wed', 'thu', 'fri'),
    ),

    'hilois' => array(
        'name'      => 'Hi and Lois',
        'author'    => 'Greg and Brian Walker, Drawn by Chance Browne',
        'method'    => 'direct',
        'url'       => 'http://est.rbma.com/content/Hi_and_Lois?date={%Y%m%d}',
        'referer'   => 'http://www.kingfeatures.com/features/comics/hi_lois/aboutMaina.php',
        'homepage'  => 'http://www.kingfeatures.com/features/comics/hi_lois/about.htm',
        'enabled'   => true
    ),

    'jaketherake' => array(
        'name'      => 'Jake The Rake',
        'author'    => 'Marcus Morgan',
        'method'    => 'bysize',
        'days'      => 'random',
        'url'       => 'http://www.adjectivenoun.org.uk/jake/archive.htm',
        'search'    => '<li>{%d/%b/%y}: <a HREF="(.*?)">',
        'subs'      => array('search'),
        'homepage'  => 'http://www.adjectivenoun.org.uk/jake/',
        'enabled'   => false
    ),

    'holbert' => array(
        'name'      => 'Jerry Holbert',
        'author'    => 'Jerry Holbert',
        'method'    => 'search',
        'days'      => 'random',
        'url'       => 'http://www.comics.com/editoons/holbert/archive/holbert-{%Y%m%d}.html',
        'search'    => 'src\s*=\s*\S*(/editoons/[a-zA-Z0-9\/.]+/holbert\d+.\w{3})',
        'homepage'  => 'http://www.comics.com/editoons/holbert/',
        'enabled'   => true
    ),

    'jumpstart' => array(
        'name'      => 'Jump Start',
        'author'    => 'Robb Armstrong',
        'method'    => 'search',
        'url'       => 'http://comics.com/jump_start/{%Y-%m-%d}/',
        'search'    => 'src\s*=\s*[\'"](http://.*/dyn/str_strip/\d+.full.gif)',
        'homepage'  => 'http://comics.com/jump_start/',
        'enabled'   => true
    ),

    'kernelpanic' => array(
        'name'      => 'Kernel Panic',
        'method'    => 'direct',
        'author'    => 'Christopher B. Wright',
        'url'       => 'http://ubersoft.net/files/comics/kp/kp{%Y%m%d}.png',
        'homepage'  => 'http://ubersoft.net/comic/kp',
        'enabled'   => true,
        'days'      => 'random'
    ),

    'libertymeadows' => array(
        'name'      => 'Liberty Meadows',
        'author'    => 'Frank Cho',
        'method'    => 'search',
        'url'       => 'http://comics.com/liberty_meadows/{%Y-%m-%d}/',
        'search'    => 'src\s*=\s*[\'"](http://.*/dyn/str_strip/\d+.full.gif)',
        'homepage'  => 'http://comics.com/liberty_meadows/',
        'enabled'   => true
    ),

    'lfg' => array(
        'name'      => 'Looking for Group',
        'author'    => 'Ryan Sohmer and Lar deSouza',
        'method'    => 'direct',
        'url'       => 'http://archive.lfgcomic.com/lfg{i}.gif',
        'icount'    => 263,
        'idate'     => 'June 22, 2009',
        'iformat'   => '%04d',
        'itype'     => 'ref',   
        'days'      => array('mon', 'thu'),
        'homepage'  => 'http://lfgcomic.com/',
        'enabled'   => true
    ),
 
    'luann' => array(
        'name'      => 'Luann',
        'author'    => 'Greg Evans',
        'method'    => 'search',
        'url'       => 'http://comics.com/luann/{%Y-%m-%d}/',
        'search'    => 'src\s*=\s*[\'"](http://.*/dyn/str_strip/\d+.full.gif)',
        'homepage'  => 'http://comics.com/luann/',
        'enabled'   => true
    ),

    'ramsey' => array(
        'name'      => 'Marshall Ramsey',
        'method'    => 'search',
        'author'    => 'Marshall Ramsey',
        'url'       => 'http://www.clarionledger.com/apps/pbcs.dll/section?Date={%Y%m%d}&Category=OPINION04',
        'search'    => 'src="(.*?cmsimg.clarionledger.com.*?)"',
        'homepage'  => 'http://www.clarionledger.com/news/editorial/ramsey/archive/cartoons/marshallbio.html',
        'enabled'   => true,
        'days'      => 'random'
    ),

    'marvin' => array(
        'name'      => 'Marvin',
        'author'    => 'Tom Armstrong',
        'method'    => 'direct',
        'url'       => 'http://est.rbma.com/content/Marvin?date={%Y%m%d}',
        'referer'   => 'http://www.kingfeatures.com/features/comics/marvin/aboutMaina.php',
        'homepage'  => 'http://www.kingfeatures.com/features/comics/marvin/about.htm',
        'enabled'   => true
    ),

    'meg' => array(
        'name'      => 'Meg',
        'author'    => 'Greg Curfman',
        'method'    => 'search',
        'url'       => 'http://comics.com/meg_classics/{%Y-%m-%d}/',
        'search'    => 'src\s*=\s*[\'"](http://.*/dyn/str_strip/\d+.full.gif)',
        'homepage'  => 'http://comics.com/meg_classics/',
        'enabled'   => true
    ),

    'luckovich' => array(
        'name'      => 'Mike Luckovich',
        'author'    => 'Mike Luckovich',
        'method'    => 'search',
        'days'      => 'random',
        'url'       => 'http://www.comics.com/editoons/luckovich/archive/luckovich-{%Y%m%d}.html',
        'search'    => 'src\s*=\s*\S*(/editoons/[a-zA-Z0-9\/.]+/luckovich\d+.\w{3})',
        'homepage'  => 'http://www.comics.com/editoons/luckovich/',
        'enabled'   => true
    ),

    'thompson' => array(
        'name'      => 'Mike Thompson',
        'author'    => 'Mike Thompson',
        'method'    => 'direct',
        'days'      => 'random',
        'url'       => 'http://cagle.msnbc.com/working/{%y%m%d}/thompson.jpg',
        'homepage'  => 'http://www.freep.com/index/thompson.htm',
        'comment'   => 'This comic is not produced on a regular schedule.  It may or may not be available on any given day.',
        'enabled'   => true
    ),

    'misterboffo' => array(
        'name'      => 'Mister Boffo',
        'author'    => 'Joe Martin',
        'method'    => 'direct',
        'url'       => 'http://www.mrboffo.com/comicsweb/mrboffoweb/strips/{%Y%m%d}bfo_s_web.jpg',
        'homepage'  => 'http://www.mrboffo.com',
        'enabled'   => true
    ),

    'monty' => array(
        'name'      => 'Monty',
        'author'    => 'Jim Meddick',
        'method'    => 'search',
        'url'       => 'http://comics.com/monty/{%Y-%m-%d}/',
        'search'    => 'src\s*=\s*[\'"](http://.*/dyn/str_strip/\d+.full.gif)',
        'homepage'  => 'http://comics.com/monty/',
        'enabled'   => true
    ),

    'grimm' => array(
        'name'      => 'Mother Goose and Grimm',
        'method'    => 'direct',
        'author'    => 'Mike Peters',
        'url'       => 'http://www.grimmy.com/images/MGG_Archive/MGG_{%Y}/MGG{%m%d}.gif',
        'homepage'  => 'http://www.grimmy.com/',
        'enabled'   => true
    ),

    'mutts' => array(
        'name'      => 'Mutts',
        'author'    => 'Patrick McDonnell',
        'method'    => 'direct',
        'url'       => 'http://est.rbma.com/content/Mutts?date={%Y%m%d}',
        'referer'   => 'http://www.kingfeatures.com/features/comics/mutts/aboutMaina.php',
        'homepage'  => 'http://www.muttscomics.com/',
        'enabled'   => true
    ),

    'nodwick' => array (
        'name'      => 'Nodwick',
        'author'    => 'Aaron Williams',
        'method'    => 'direct',
        'url'       => 'http://nodwick.humor.gamespy.com/gamespyarchive/strips/{%Y}-{%m}-{%d}.jpg',
        'homepage'  => 'http://nodwick.humor.gamespy.com/index.htm',
        'days'      => array('mon', 'wed', 'fri'),
        'enabled'   => true
    ),

    'nonseq' => array(
        'name'      => 'Non Sequitur',
        'author'    => 'Wiley',
        'method'    => 'direct',
        'url'       => 'http://images.ucomics.com/comics/nq/{%Y}/nq{%y%m%d}.gif',
        'homepage'  => 'http://www.non-sequitur.com/',
        'enabled'   => true
    ),

    'notinventedhere' => array(
        'name'      => 'Not Invented Here',
        'author'    => 'Bill Barnes and Paul Southworth',
        'method'    => 'direct',
        'url'       => 'http://thiswas.notinventedhe.re/on/{%Y-%m-%d}',
        'homepage'  => 'http://notinventedhe.re/',
        'days'      => array('mon', 'tue', 'wed', 'thu'),
        'enabled'   => true
    ),
        
    'offthemark' => array(
        'name'      => 'Off the Mark',
        'author'    => 'Mark Parisi',
        'method'    => 'direct',
        'url'       => 'http://www.offthemarkcartoons.com/cartoons/{%Y-%m-%d}.gif',
        'homepage'  => 'http://www.offthemark.com/',
        'enabled'   => true
    ),

    'ohmygods' => array(
        'name'      => 'Oh My Gods!',
        'author'    => 'Shivian',
        'method'    => 'direct',
        'url'       => 'http://ohmygods.timerift.net/strips/{%Y}/{%m}/{%d}.jpg',
        'homepage'  => 'http://ohmygods.timerift.net/',
        'enabled'   => true
    ),

    'fastrack' => array(
        'name'      => 'On the Fastrack',
        'author'    => 'Bill Holbrook',
        'method'    => 'direct',
        'url'       => 'http://est.rbma.com/content/Fast_Track?date={%Y%m%d}',
        'referer'   => 'http://www.kingfeatures.com/features/comics/fastrack/aboutMaina.php',
        'homepage'  => 'http://www.kingfeatures.com/features/comics/fastrack/about.htm',
        'enabled'   => true
    ),

    'oostick' => array(
        'name'      => 'Order of the Stick',
        'author'    => 'Rich Burlew',
        'method'    => 'bysize',
        'homepage'  => 'http://giantitp.com/',
        'enabled'   => true,

        /*
         * OOTS is on an irregular schedule, but to get history we need
         * to assume certain days of the week.  You can either try
         * to fiddle with the days or choose the alternative configuration.
         */

        'days'      => array('mon', 'wed', 'fri'),
        'url'       => 'http://www.giantitp.com/comics/oots{i}.html',
        'itype'     => 'ref',
        'idate'     => 'February 26, 2007',
        'iformat'   => '%04d',
        'icount'    => 418,
        'subs'      => array('url'),
        'nohistory' => false

        /*
         * Alternative configuration: Try to grab the most recent comic
         * each day.  Does not allow historical comics to be fetched.
         */
//        'days'      => 'random',
//        'url'       => 'http://www.giantitp.com/index.html',
//        'search'    => '<B>Order of the Stick </B><A href="(/comics/.*?.html)" class="SideBar">',
//        'nohistory' => true
    ),

    'overboard' => array(
        'name'      => 'Overboard',
        'author'    => 'Chip Dunham',
        'method'    => 'direct',
        'url'       => 'http://images.ucomics.com/comics/ob/{%Y}/ob{%y%m%d}.gif',
        'homepage'  => 'http://www.ucomics.com/overboard/',
        'enabled'   => true
    ),

    'peanuts' => array(
        'name'      => 'Peanuts Classics',
        'author'    => 'Charles Schultz',
        'method'    => 'search',
        'url'       => 'http://comics.com/peanuts/{%Y-%m-%d}/',
        'search'    => 'src\s*=\s*[\'"](http://.*/dyn/str_strip/\d+.full.gif)',
        'homepage'  => 'http://comics.com/peanuts/',
        'enabled'   => true
    ),

    'pearls' => array(
        'name'      => 'Pearls Before Swine',
        'author'    => 'Stephan Pastis',
        'method'    => 'search',
        'url'       => 'http://comics.com/pearls_before_swine/{%Y-%m-%d}/',
        'search'    => 'src\s*=\s*[\'"](http://.*/dyn/str_strip/\d+.full.gif)',
        'homepage'  => 'http://comics.com/pearls_before_swine/',
        'enabled'   => true
    ),

    'penny_arcade' => array(
        'name'      => 'Penny Arcade',
        'author'    => 'Gabe and Tycho',
        'method'    => 'direct',
        'days'      => 'random',
        'url'       => 'http://www.penny-arcade.com/images/{%Y}/{%Y%m%d}.jpg',
        'homepage'  => 'http://www.penny-arcade.com',
        'enabled'   => true
    ),

    'phdcomics' => array(
        'name'      => 'Piled Higher and Deeper',
        'author'    => 'Jorge Cham',
        'method'    => 'direct',
        'days'      => 'random',
        'url'       => 'http://www.phdcomics.com/comics/archive/phd{%m%d%y}s.gif',
        'homepage'  => 'http://www.phdcomics.com/',
        'enabled'   => true
    ),

    'popeye' => array(
        'name'      => 'Popeye',
        'author'    => 'Hy Eisman',
        'method'    => 'direct',
        'url'       => 'http://est.rbma.com/content/Popeye?date={%Y%m%d}',
        'referer'   => 'http://www.kingfeatures.com/features/comics/popeye/aboutMaina.php',
        'homepage'  => 'http://www.kingfeatures.com/features/comics/popeye/about.htm',
        'enabled'   => true
    ),

    'pvp' => array(
        'name'      => 'PVP',
        'author'    => 'Scott Kurtz',
        'method'    => 'search',
        'homepage'  => 'http://www.pvponline.com/',
        'url'       => 'http://www.pvponline.com/{%Y/%m/%d/}',
        'search'    => 'img\s+src=[\'"]?(.*?/comics/pvp\d+.*?\.\w{3})',
        'days'      => array('mon', 'tue', 'wed', 'thu', 'fri'),
        'enabled'   => true,
    ),

    'realitycheck' => array(
        'name'      => 'Reality Check',
        'author'    => 'Dave Whamond',
        'method'    => 'search',
        'url'       => 'http://comics.com/reality_check/{%Y-%m-%d}/',
        'search'    => 'src\s*=\s*[\'"](http://.*/dyn/str_strip/\d+.full.gif)',
        'homepage'  => 'http://comics.com/reality_check/',
        'enabled'   => true
    ),

    'RedMeat' => array(
        'name'      => 'Red Meat',
        'author'    => 'Max Cannon',
        'method'    => 'direct',
        'days'      => array('Tue'),
        'url'       => 'http://www.redmeat.com/redmeat/{%Y}-{%m}-{%d}/index-1.gif',
        'homepage'  => 'http://www.redmeat.com/',
        'enabled'   => true
    ),

    'rwo' => array(
        'name'      => 'Rhymes with Orange',
        'author'    => 'Hilary B. Price',
        'method'    => 'direct',
        'url'       => 'http://est.rbma.com/content/Rhymes_with_Orange?date={%Y%m%d}',
        'referer'   => 'http://rhymeswithorange.com',
        'homepage'  => 'http://rhymeswithorange.com',
        'enabled'   => true
    ),

    'rogers' => array(
        'name'      => 'Rob Rogers',
        'author'    => 'Rob Rogers',
        'method'    => 'search',
        'days'      => 'random',
        'url'       => 'http://www.comics.com/editoons/rogers/archive/rogers-{%Y%m%d}.html',
        'search'    => 'src\s*=\s*\S*(/editoons/[a-zA-Z0-9\/.]+/rogers\d+.\w{3})',
        'homepage'  => 'http://www.comics.com/editoons/rogers/',
        'enabled'   => true
    ),

    'ariail' => array(
        'name'      => 'Robert Ariail',
        'author'    => 'Robert Ariail',
        'method'    => 'search',
        'days'      => 'random',
        'url'       => 'http://www.comics.com/editoons/ariail/archive/ariail-{%Y%m%d}.html',
        'search'    => 'src\s*=\s*\S*(/editoons/[a-zA-Z0-9\/.]+/ariail\d+.\w{3})',
        'homepage'  => 'http://www.comics.com/editoons/ariail/',
        'enabled'   => true
    ),

    'roseisrose' => array(
        'name'      => 'Rose Is Rose',
        'author'    => 'Pat Brady',
        'method'    => 'search',
        'url'       => 'http://comics.com/rose_is_rose/{%Y-%m-%d}/',
        'search'    => 'src\s*=\s*[\'"](http://.*/dyn/str_strip/\d+.full.gif)',
        'homepage'  => 'http://comics.com/rose_is_rose/',
        'enabled'   => true
    ),

    'smbc' => array(
        'name'      => 'Saturday Morning Breakfast Cereal',
        'author'    => 'Zach Weiner',
        'method'    => 'direct',
        'url'       => 'http://www.smbc-comics.com/comics/{%Y%m%d}.gif',
        'homepage'  => 'http://www.smbc-comics.com',
        'enabled'   => true
    ),

    'shithappens' => array(
        'name'      => 'Shit Happens!',
        'author'    => 'Ralph Ruthe',
        'method'    => 'search',
        'days'      => 'random',
        'url'       => 'http://www.ruthe.de/',
        'search'    => 'src="(cartoons/strip_\d+.jpg)',
        'homepage'  => 'http://www.ruthe.de/',
        'nohistory' => true,
        'enabled'   => true
    ),

    'shoe' => array(
        'name'      => 'Shoe',
        'author'    => 'Jeff MacNelly',
        'method'    => 'direct',
        'url'       => 'http://images.ucomics.com/comics/tmsho/{%Y}/tmsho{%y%m%d}.gif',
        'homepage'  => 'http://www.macnelly.com/',
        'enabled'   => true
    ),

    'sluggy' => array(
        'name'      => 'Sluggy Freelance',
        'author'    => 'Pete Abrams',
        'method'    => 'search',
        'url'       => 'http://sluggy.com/daily.php?date={%y%m%d}',
        'search'    => 'src="(http://sluggy.com/images/comics/\d+\w.\w{3})',
        'referer'   => 'http://www.sluggy.com/daily.php?date={%y%m%d}',
        'offset'    => 1,
        'homepage'  => 'http://www.sluggy.com/',
        'enabled'   => true
    ),

    'smithsworld'  => array(
        'name'      => "Smith's World",
        'author'    => 'Mike Smith',
        'method'    => 'search',
        'url'       => 'http://www.lasvegassun.com/news/opinion/smiths-world/',
        'homepage'  => 'http://www.lasvegassun.com/news/opinion/smiths-world/',
        'search'    => '<a.*?title="Smith\'s World for {%B %d, %Y}"><img.*?src="(.*?)"',
        'subs'      => array('search'),
        'days'      => 'random',
        'nohistory' => true,
        'enabled'   => true
    ),

    'somethingpositive' => array(
        'name'      => 'Something Positive',
        'author'    => 'R*K*Milholland',
        'method'    => 'search',
        'days'      => 'random',
        'url'       => 'http://www.somethingpositive.net/',
        'search'    => 'src="(arch\/\w+)',
        'homepage'  => 'http://www.somethingpositive.net/',
        'enabled'   => true
    ),

    'stantis' => array(
        'name'      => 'Scott Stantis',
        'author'    => 'Scott Stantis',
        'method'    => 'direct',
        'days'      => 'random',
        'url'       => 'http://cagle.com/working/{%y%m%d}/stantis.jpg',
        'homepage'  => 'http://cagle.com/politicalcartoons/PCcartoons/stantis.asp',
        'enabled'   => true
    ),

    'shermanslagoon' => array(
        'name'      => 'Sherman\'s Lagoon',
        'author'    => 'Jim Toomey',
        'method'    => 'direct',
        'url'       => 'http://www.slagoon.com/dailies/SL{%y}{%m}{%d}.gif',
        'homepage'  => 'http://www.slagoon.com/',
        'enabled'   => true
      ),

    'stockcartoon'  => array(
        'name'      => 'Stock Car Toons',
        'author'    => 'Mike Smith',
        'method'    => 'search',
        'url'       => 'http://www.lasvegassun.com/news/opinion/smiths-world/',
        'homepage'  => 'http://www.lasvegassun.com/news/opinion/smiths-world/',
        'search'    => '<a.*?title="Stockcartoon for {%B %d, %Y}"><img.*?src="(.*?)"',
        'subs'      => array('search'),
        'days'      => array('thu'),
        'nohistory' => true,
        'enabled'   => true
    ),

    'stonesoup' => array(
        'name'      => 'Stone Soup',
        'author'    => 'Jan Eliot',
        'method'    => 'direct',
        'url'       => 'http://images.ucomics.com/comics/ss/{%Y}/ss{%y%m%d}.gif',
        'homepage'  => 'http://www.ucomics.com/stonesoup/',
        'enabled'   => true
    ),

    'tankmcnamara' => array(
        'name'      => 'Tank McNamara',
        'author'    => 'Jeff Millar and Bill Hinds',
        'method'    => 'direct',
        'url'       => 'http://images.ucomics.com/comics/tm/{%Y}/tm{%y%m%d}.gif',
        'homepage'  => 'http://www.ucomics.com/tankmcnamara/',
        'enabled'   => true
    ),

    'rall' => array(
        'name'      => 'Ted Rall',
        'author'    => 'Ted Rall',
        'method'    => 'direct',
        'days'      => array('mon', 'thu', 'sat'),
        'url'       => 'http://images.ucomics.com/comics/tr/{%Y}/tr{%y%m%d}.gif',
        'homepage'  => 'http://www.ucomics.com/duplex/viewtr.htm',
        'enabled'   => true
    ),

    'tombug' => array(
        'name'      => 'Tom the Dancing Bug',
        'author'    => 'Ruben Bolling',
        'method'    => 'direct',
        'days'      => 'random',
        'url'       => 'http://images.ucomics.com/comics/td/{%Y}/td{%y%m%d}.gif',
        'homepage'  => 'http://www.gocomics.com/tomthedancingbug',
        'enabled'   => true
    ),


    'tonyauth' => array(
        'name'      => 'Tony Auth',
        'author'    => 'Tony Auth',
        'method'    => 'direct',
        'days'      => 'random',
        'url'       => 'http://images.ucomics.com/comics/ta/{%Y}/ta{%y%m%d}.gif',
        'homepage'  => 'http://www.ucomics.com/tonyauth/',
        'enabled'   => true
    ),

    'unshelved' => array(
        'name'      => 'Unshelved',
        'author'    => 'Bill Barnes, Gene Ambaum,  and Paul Southworth',
        'method'    => 'direct',
        'url'       => 'http://www.unshelved.com/strips/({%Y%m%d.gif}',
        'homepage'  => 'http://www.unshelved.com/',
        'enabled'   => true
    ),
        
    'userfriendly' => array(
        'name'      => 'User Friendly',
        'author'    => 'Iliad',
        'method'    => 'search',
        'url'       => 'http://ars.userfriendly.org/cartoons/?id={%Y%m%d}&mode=classic',
        'search'    => '(http://www.userfriendly.org/cartoons/archives/\d+\w+/uf[n]*[g]*\d+.gif)',
        'homepage'  => 'http://www.userfriendly.org/',
        'enabled'   => true
    ),

    'woid' => array(
        'name'      => 'The Wizard of Id',
        'author'    => 'Brant Parker',
        'method'    => 'search',
        'url'       => 'http://comics.com/wizard_of_id/{%Y-%m-%d}/',
        'search'    => 'src\s*=\s*[\'"](http://.*/dyn/str_strip/\d+.full.gif)',
        'homepage'  => 'http://comics.com/wizard_of_id/',
        'enabled'   => true
    ),

    'xkcd' => array(
        'name'      => 'xkcd',
        'author'    => 'Randall Munroe',
        'homepage'  => 'http://xkcd.com/',
        'method'    => 'search',
        'days'      => array('mon', 'wed', 'fri'),
        'icount'    => 360,
        'idate'     => 'December 21, 2007',
        'iformat'   => '%03d',
        'itype'     => 'ref',
        'url'       => 'http://xkcd.com/{i}/',
        'search'    => '(http://imgs.xkcd.com/comics/\w+.\w{3})',
        'enabled'   => true
    ),

    'zippy' => array(
        'name'      => 'Zippy the Pinhead',
        'author'    => 'Bill Griffith',
        'method'    => 'direct',
        'url'       => 'http://est.rbma.com/content/Zippy_the_Pinhead?date={%Y%m%d}',
        'referer'   => 'http://www.kingfeatures.com/features/comics/zippy/aboutMaina.php',
        'homepage'  => 'http://www.kingfeatures.com/features/comics/zippy/about.htm',
        'enabled'   => true
    ),

    'zits' => array(
        'name'      => 'Zits',
        'author'    => 'Jerry Scott and Jim Borgman',
        'method'    => 'direct',
        'url'       => 'http://est.rbma.com/content/Zits?date={%Y%m%d}',
        'referer'   => 'http://www.kingfeatures.com/features/comics/zits/aboutMaina.php',
        'homepage'  => 'http://www.kingfeatures.com/features/comics/zits/about.htm',
        'enabled'   => true
    ),

);
