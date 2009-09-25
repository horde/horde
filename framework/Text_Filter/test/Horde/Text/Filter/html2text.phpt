--TEST--
Horde_Text_Filter_Html2text tests
--FILE--
<?php

require_once 'Horde/String.php';
require_once 'Horde/Util.php';
require dirname(__FILE__) . '/../../../../lib/Horde/Text/Filter.php';
require dirname(__FILE__) . '/../../../../lib/Horde/Text/Filter/Html2text.php';
$html = file_get_contents(dirname(__FILE__) . '/fixtures/html2text.html');
echo Horde_Text_Filter::filter($html, 'html2text', array('charset' => 'UTF-8'));

?>
--EXPECT--
INLINE FORMATTING

Some text with leading and trailing whitespace

	emphasis text		/emphasis text/
	strong text		STRONG TEXT
	italic text		/italic text/
	bold text		BOLD TEXT
	emphasis and strong		/EMPHASIS AND STRONG/
	underline text		_underline text_

-------------------------

LINKS

Horde Homepage[1]
Test User[2]
Some inline link[3].
http://www.example.com

-------------------------

HEADINGS

You can make various levels of heading by putting equals-signs before
and after the text (all on its own line):

LEVEL 3 HEADING

Level 4 Heading

Level 5 Heading

Level 6 Heading

-------------------------

BULLET LISTS

You can create bullet lists by starting a paragraph with one or more
asterisks.

  * Bullet one

  * Sub-bullet

NUMBERED LISTS

Similarly, you can create numbered lists by starting a paragraph with
one or more hashes.

  * Numero uno
  * Number two

  * Sub-item

MIXING BULLET AND NUMBER LIST ITEMS

You can mix and match bullet and number lists:

  * Number one

  * Bullet
  * Bullet

  * Number two

  * Bullet
  * Bullet

  * Sub-bullet

  * Sub-sub-number
  * Sub-sub-number

  * Number three

  * Bullet
  * Bullet

BLOCK QUOTING

> Horde Homepage[4]
> Some inline link[5].

Line inbetween.

> HEADING INSIDE QUOTING
>
> This is a paragraph inside a block quoting. The result should be
> several lines prefixed with the > character.

SPECIAL CHARACTERS

ä é © (tm)

Zitat von John Doe <john.doe@example.com>: 

> Hallo lieber John, 
>
> Blah, blah.'

-- 
Some signature
http://www.example.com

Zitat von Jane Doe <jane.doe@example.com>:

> Jan Schneider a écrit :
>
> > Zitat von Jane Doe <jane.doe@example.com>: 
> >
> > > Hi, 
> > >
> > > I prepare the last "horde-webmail-1.2" for production
> > > level but I have few questions: 
> > > - is there a way to disable "external_display_cal" in
> > > kronolith, I don't want seeing birthdays calendars (turba) and
> > task
> > > list (nag)
> >
> >
> > They aren't displayed by default, or do you mean you don't want
> them
> > to appear in the top right calendar panel?
>
>  Yes I don't want them to appear in the top right calendar panel but
> I want user can create their external_cal

Jan.

-- 
Do you need professional PHP or Horde consulting?
http://horde.org/consulting/

Links:
------
[1] http://www.horde.org
[2] mailto:test@example.com
[3] http://www.horde.org
[4] http://www.horde.org
[5] http://www.horde.org
