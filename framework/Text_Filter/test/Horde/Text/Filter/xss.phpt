--TEST--
Horde_Text_Filter_Xss tests
--FILE--
<?php

/* Test cases from http://ha.ckers.org/xss.html */

require dirname(__FILE__) . '/../../../../lib/Horde/Text/Filter.php';
require dirname(__FILE__) . '/../../../../lib/Horde/Text/Filter/Base.php';
require dirname(__FILE__) . '/../../../../lib/Horde/Text/Filter/Xss.php';
require dirname(__FILE__) . '/../../../../../Util/lib/Horde/String.php';
require dirname(__FILE__) . '/../../../../../Util/lib/Horde/Util.php';

foreach (glob(dirname(__FILE__) . '/fixtures/xss*.html') as $file) {
    echo basename($file) . "\n" .
        Horde_Text_Filter::filter(file_get_contents($file), 'xss') .
        "\n";
}

foreach (glob(dirname(__FILE__) . '/fixtures/style_xss*.html') as $file) {
    echo basename($file) . "\n" .
        Horde_Text_Filter::filter(file_get_contents($file), 'xss', array(
            'strip_styles' => false
        )) .
        "\n";
}

?>
--EXPECT--
xss01.html

xss02.html
<img/>
xss03.html
<img/>
xss04.html
<img/>
xss05.html
<img/>
xss06.html
<img says=""/>
xss07.html
<img/>"&gt;

xss08.html
<img/>
xss09.html
<img/>
xss10.html
<img src="&#xA0;&#xA0;&#xA0;&#xA0;&#xA0;&#xA0;&#xA0;&#xA0;&#xA0;&#xA0;&#xA0;&#xA0;&#xA0;&#xA0;&#xA0;&#xA0;&#xA0;&#xA0;&#xA0;&#xA0;&#xA0;&#xA0;&#xA0;"/>
xss100.html
<img src="blank.jpg"/>
xss11.html
<img/>
xss12.html
<img/>
xss13.html
<img/>
xss14.html
<img/>
xss15.html
<img/>
xss16.html
<img src="j" a="" v="" s="" c="" r="" i="" p="" t="" :="" l="" e="" x=""/>
xss17.html
<img/>
xss18.html

xss19.html
<img src=" "/>
xss20.html

xss21.html

xss22.html

xss23.html
<p>alert("XSS");//</p>
xss24.html

xss25.html

xss26.html
<img/>
xss27.html

xss28.html

xss29.html

xss30.html
<input type="IMAGE"/>
xss31.html

xss32.html

xss33.html
<img/>
xss34.html
<img/>
xss35.html
<bgsound/>
xss36.html
<br/>
xss37.html

xss38.html

xss39.html

xss40.html

xss41.html

xss42.html

xss43.html
<xss/>
xss44.html
<ul><li>XSS
</li></ul>
xss45.html
<img/>
xss46.html
<img/>
xss47.html
<img/>
xss48.html

xss49.html

xss50.html

xss51.html

xss52.html

xss53.html
<table/>
xss54.html
<table><td/></table>
xss55.html
<div/>
xss56.html
<div/>
xss57.html
<div/>
xss58.html
<div/>
xss59.html

xss60.html
<img/>
xss61.html
<xss/>
xss62.html
<p>exp/*<a/></p>
xss63.html

xss64.html

xss65.html

xss66.html

xss67.html

xss68.html

xss69.html

xss70.html

xss71.html

xss72.html
<xss>XSS</xss>
xss73.html
<span datasrc="#I" datafld="C" dataformatas="HTML"/>
xss74.html
<span datasrc="#xss" datafld="B" dataformatas="HTML"/>
xss75.html
<span datasrc="#I" datafld="C" dataformatas="HTML"/>
xss76.html


xss77.html

xss78.html
<img/>
xss79.html

xss80.html

xss81.html

xss82.html

xss83.html

xss84.html

xss85.html
<p>PT SRC="http://ha.ckers.org/a.js"&gt;</p>
xss95.html
<a>Click me</a>
xss96.html
<a>Click me</a>
xss97.html

xss98.html

xss99.html
<img src=""/>
style_xss01.html
