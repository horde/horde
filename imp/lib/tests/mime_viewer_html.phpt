--TEST--
IMP HTML MIME Viewer tests.
--FILE--
<?php

require_once dirname(__FILE__) . '/../Application.php';
Horde_Registry::appInit('imp', array(
    'authentication' => 'none',
    'cli' => true
));

$mock_part = new Horde_Mime_Part();
$mock_part->setType('text/html');

$v = Horde_Mime_Viewer::factory($mock_part);
$v->blockimg = 'imgblock.png';
$v->newwinTarget = '_blank';

// Test regex for converting links to open in a new window.
echo $v->openLinksInNewWindow('foo') . "\n";
echo $v->openLinksInNewWindow('example@example.com') . "\n";
echo $v->openLinksInNewWindow('foo <a href="#bar">Anchor</a>') . "\n";
echo $v->openLinksInNewWindow('foo <a href="http://www.example.com/">example</a>') . "\n";
echo $v->openLinksInNewWindow('foo <a target="foo" href="http://www.example.com/">example</a>') . "\n";
echo $v->openLinksInNewWindow('foo <a href="http://www.example.com/" target="foo">example</a>') . "\n";
echo $v->openLinksInNewWindow('foo <a mailto="example@example.com">Example Email</a>') . "\n";
echo $v->openLinksInNewWindow('<map name="Map"><area shape="rect" coords="32,-2,293,29" href="http://www.example.com/"></map>') . "\n";
echo $v->openLinksInNewWindow('<map name="Map"><area shape="rect" coords="32,-2,293,29" href="http://www.example.com/" target="foo"></map>') . "\n";
echo "\n";

// Test regex for hiding images.
echo $v->blockImages('<img src="http://example.com/image.png">') . "\n";
echo $v->blockImages('<img src="http://example.com/image.png" />') . "\n";
echo $v->blockImages('<td  background=http://example.com/image.png>') . "\n";
echo $v->blockImages("<img src= http://example.com/image.png alt='Best flight deals'  border=0>") . "\n";
echo $v->blockImages('<foo style="background:url(http://example.com/image.png)">') . "\n";
echo $v->blockImages('<foo style="background: transparent url(http://example.com/image.png) repeat">') . "\n";
echo $v->blockImages('<foo style="background-image:url(http://example.com/image.png)">') . "\n";
echo $v->blockImages('<foo style="background: transparent url(http://example.com/image.png) repeat">
<foo style="background-image:url(http://example.com/image.png)">') . "\n";

?>
--EXPECT--
foo
example@example.com
foo <a href="#bar">Anchor</a>
foo <a target="_blank" href="http://www.example.com/">example</a>
foo <a target="_blank" href="http://www.example.com/">example</a>
foo <a href="http://www.example.com/" target="_blank">example</a>
foo <a target="_blank" mailto="example@example.com">Example Email</a>
<map name="Map"><area target="_blank" shape="rect" coords="32,-2,293,29" href="http://www.example.com/"></map>
<map name="Map"><area shape="rect" coords="32,-2,293,29" href="http://www.example.com/" target="_blank"></map>

<img src="imgblock.png" htmlimgblocked="http%3A%2F%2Fexample.com%2Fimage.png">
<img src="imgblock.png" htmlimgblocked="http%3A%2F%2Fexample.com%2Fimage.png" />
<td  background="imgblock.png" htmlimgblocked="http%3A%2F%2Fexample.com%2Fimage.png">
<img src="imgblock.png" htmlimgblocked="http%3A%2F%2Fexample.com%2Fimage.png" alt='Best flight deals'  border=0>
<foo style="background:url('imgblock.png')" htmlimgblocked="http%3A%2F%2Fexample.com%2Fimage.png">
<foo style="background: transparent url('imgblock.png') repeat" htmlimgblocked="http%3A%2F%2Fexample.com%2Fimage.png">
<foo style="background-image:url('imgblock.png')" htmlimgblocked="http%3A%2F%2Fexample.com%2Fimage.png">
<foo style="background: transparent url('imgblock.png') repeat" htmlimgblocked="http%3A%2F%2Fexample.com%2Fimage.png">
<foo style="background-image:url('imgblock.png')" htmlimgblocked="http%3A%2F%2Fexample.com%2Fimage.png">
