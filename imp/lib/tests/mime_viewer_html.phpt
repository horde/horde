--TEST--
IMP HTML MIME Viewer tests.
--FILE--
<?php

require_once dirname(__FILE__) . '/../Application.php';
Horde_Registry::appInit('imp', array(
    'authentication' => 'none',
    'cli' => true
));

require_once dirname(__FILE__) . '/../Mime/Viewer/Html.php';
class IMP_Html_Viewer_Test extends IMP_Horde_Mime_Viewer_Html
{
    public function runTest($html)
    {
        $this->_imptmp = array(
            'blockimg' => 'imgblock.png',
            'img' => true,
            'imgblock' => false,
            'inline' => true,
            'target' => '_blank'
        );

        $dom = new Horde_Domhtml($html);
        $this->_node($dom->dom, $dom->dom);

        return $dom->dom->saveXML($dom->dom->getElementsByTagName('body')->item(0)->firstChild) . "\n";
    }

    protected function _node($doc, $node)
    {
        if ($node->hasChildNodes()) {
            foreach ($node->childNodes as $child) {
                $this->_nodeCallback($doc, $child);
                $this->_node($doc, $child);
            }
        }
    }

}

$v = new IMP_Html_Viewer_Test(new Horde_Mime_Part());

// Test regex for converting links to open in a new window.
$links = array(
    'foo',
    'example@example.com',
    'foo <a href="#bar">Anchor</a>',
    'foo <a href="http://www.example.com/">example</a>',
    'foo <a target="foo" href="http://www.example.com/">example</a>',
    'foo <a href="http://www.example.com/" target="foo">example</a>',
    'foo <a mailto="example@example.com">Example Email</a>',
    '<map name="Map"><area shape="rect" coords="32,-2,293,29" href="http://www.example.com/"></map>',
    '<map name="Map"><area shape="rect" coords="32,-2,293,29" href="http://www.example.com/" target="foo"></map>'
);

foreach ($links as $val) {
    echo $v->runTest($val);
}

echo "\n";

// Test regex for hiding images.
$images = array(
    '<img src="http://example.com/image.png">',
    '<img src="http://example.com/image.png" />',
    '<td  background=http://example.com/image.png>',
    "<img src= http://example.com/image.png alt='Best flight deals'  border=0>",
    '<foo style="background:url(http://example.com/image.png)">',
    '<foo style="background: transparent url(http://example.com/image.png) repeat">',
    '<foo style="background-image:url(http://example.com/image.png)">',
    '<foo style="background: transparent url(http://example.com/image.png) repeat">'
);

foreach ($images as $val) {
    echo $v->runTest($val);
}

?>
--EXPECT--
<p>foo</p>
<p>example@example.com</p>
<p>foo <a href="#bar">Anchor</a></p>
<p>foo <a href="http://www.example.com/" target="_blank">example</a></p>
<p>foo <a target="foo" href="http://www.example.com/">example</a></p>
<p>foo <a href="http://www.example.com/" target="foo">example</a></p>
<p>foo <a mailto="example@example.com">Example Email</a></p>
<map name="Map"><area shape="rect" coords="32,-2,293,29" href="http://www.example.com/" target="_blank"/></map>
<map name="Map"><area shape="rect" coords="32,-2,293,29" href="http://www.example.com/" target="foo"/></map>

<img src="imgblock.png" htmlimgblocked="http://example.com/image.png"/>
<img src="imgblock.png" htmlimgblocked="http://example.com/image.png"/>
<td background="imgblock.png" htmlimgblocked="http://example.com/image.png"/>
<img src="imgblock.png" alt="Best flight deals" border="0" htmlimgblocked="http://example.com/image.png"/>
<foo style="background:url(imgblock.png)" htmlimgblocked="http://example.com/image.png"/>
<foo style="background: transparent url(imgblock.png) repeat" htmlimgblocked="http://example.com/image.png"/>
<foo style="background-image:url(imgblock.png)" htmlimgblocked="http://example.com/image.png"/>
<foo style="background: transparent url(imgblock.png) repeat" htmlimgblocked="http://example.com/image.png"/>
