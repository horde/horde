--TEST--
Horde_Text_Filter_Xss tests
--FILE--
<?php

/* Test cases from http://ha.ckers.org/xss.html */

require dirname(__FILE__) . '/../../../../lib/Horde/Text/Filter.php';
require dirname(__FILE__) . '/../../../../lib/Horde/Text/Filter/Xss.php';

foreach (glob(dirname(__FILE__) . '/fixtures/xss*.html') as $file) {
    $data = file_get_contents($file);
    echo basename($file) . "\n";
    echo Horde_Text_Filter::filter($data, 'xss', array('body_only' => false));
}

foreach (glob(dirname(__FILE__) . '/fixtures/style_xss*.html') as $file) {
    $data = file_get_contents($file);
    echo basename($file) . "\n";
    echo Horde_Text_Filter::filter($data, 'xss', array('body_only' => false, 'strip_styles' => false));
}

?>
--EXPECT--
xss01.html
<XSSCleaned_script />
xss02.html
<IMG SRC="XSSCleanedalert('XSS');">
xss03.html
<IMG SRC=XSSCleanedalert('XSS')>
xss04.html
<IMG SRC=XSSCleanedalert('XSS')>
xss05.html
<IMG SRC=XSSCleanedalert(&quot;XSS&quot;)>
xss06.html
<IMG SRC=XSSCleanedalert("RSnake says, 'XSS'")`>
xss07.html
<IMG """><XSSCleaned_script />">
xss08.html
<IMG SRC=XSSCleanedalert(String.fromCharCode(88,83,83))>
xss09.html
<IMG SRC=XSSCleaned&#97;&#108;&#101;&#114;&#116;&#40;&#39;&#88;&#83;&#83;&#39;&#41>
xss10.html
<IMG SRC=&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;>
xss100.html
<img src='blank.jpg' XSSCleaned='width:expression(alert("xssed"))'>
xss11.html
<IMG SRC=XSSCleaned&#x61&#x6C&#x65&#x72&#x74&#x28&#x27&#x58&#x53&#x53&#x27&#x29>
xss12.html
<IMG SRC="XSSCleanedalert('XSS');">
xss13.html
<IMG SRC="XSSCleanedalert('XSS');">
xss14.html
<IMG SRC="XSSCleanedalert('XSS');">
xss15.html
<IMG SRC="XSSCleanedalert('XSS');">
xss16.html
<IMG
SRC
=XSSCleaned
a
l
e
r
t
(
'
X
S
S
'
)
"
>
xss17.html
<IMG SRC=XSSCleanedalert("XSS")>
xss18.html
<XSSCleaned_script />
xss19.html
<IMG SRC="XSSCleanedalert('XSS');">
xss20.html
<XSSCleaned_script />
xss21.html
<BODY onloadXSSCleaned=alert("XSS")>
xss22.html
<XSSCleaned_script />
xss23.html
<<XSSCleaned_script />
xss24.html
<XSSCleaned_tag SRC=http://ha.ckers.org/xss.js?<B>
xss25.html
<XSSCleaned_tag SRC=//ha.ckers.org/.j>
xss26.html
<IMG SRC="XSSCleanedalert('XSS')"
xss27.html
<XSSCleaned_tag src=http://ha.ckers.org/scriptlet.html <
xss28.html
<XSSCleaned_script />
xss29.html
</TITLE><XSSCleaned_script />
xss30.html
<INPUT TYPE="XSSCleanedalert('XSS');">
xss31.html
<BODY BACKGROUND="XSSCleanedalert('XSS')">
xss32.html
<BODY ONLOADXSSCleaned=alert('XSS')>
xss33.html
<IMG DYNSRC="XSSCleanedalert('XSS')">
xss34.html
<IMG LOWSRC="XSSCleanedalert('XSS')">
xss35.html
<BGSOUND SRC="XSSCleanedalert('XSS');">
xss36.html
<BR SIZE="&{;}">
xss37.html
<XSSCleaned_tag SRC="http://ha.ckers.org/scriptlet.html"></XSSCleaned_tag>
xss38.html
<!-- <LINK REL="XSSCleanedalert('XSS');"> -->
xss39.html
<!-- <LINK REL="stylesheet" HREF="http://ha.ckers.org/xss.css"> -->
xss40.html
<!--@import'http://ha.ckers.org/xss.css';-->
xss41.html
<XSSCleaned_tag HTTP-EQUIV="Link" Content="<http://ha.ckers.org/xss.css>; REL=stylesheet">
xss42.html
<!--BODY{-moz-XSSCleaned:url("http://ha.ckers.org/xssmoz.xml#xss")}-->
xss43.html
<XSS XSSCleaned="behavior: url(xss.htc);">
xss44.html
<!--li {list-style-image: url("XSSCleanedalert('XSS')");}--><UL><LI>XSS
xss45.html
<IMG SRC='XSSCleanedmsgbox("XSS")'>
xss46.html
<IMG SRC="XSSCleaned:[code]">
xss47.html
<IMG SRC="XSSCleaned[code]">
xss48.html
<XSSCleaned_tag HTTP-EQUIV="XSSCleanedalert('XSS');">
xss49.html
<XSSCleaned_tag HTTP-EQUIV="refresh" CONTENT="0;url=data:text/html;base64,PHNjcmlwdD5hbGVydCgnWFNTJyk8L3NjcmlwdD4K">
xss50.html
<XSSCleaned_tag HTTP-EQUIV="XSSCleanedalert('XSS');">
xss51.html
<XSSCleaned_tag SRC=XSSCleanedalert('XSS')></XSSCleaned_tag>
xss52.html
<XSSCleaned_tagSET><XSSCleaned_tag SRC=XSSCleanedalert('XSS')></XSSCleaned_tag></XSSCleaned_tagSET>
xss53.html
<TABLE BACKGROUND="XSSCleanedalert('XSS')">
xss54.html
<TABLE><TD BACKGROUND="XSSCleanedalert('XSS')">
xss55.html
<DIV XSSCleaned="XSSCleanedalert('XSS'))">
xss56.html
<DIV XSSCleaned="XSSCleaned\0061\006c\0065\0072\0074\0028.1027\0058.1053\0053\0027\0029'\0029">
xss57.html
<DIV XSSCleaned="XSSCleanedalert('XSS'))">
xss58.html
<DIV XSSCleaned="width: expression(alert('XSS'));">
xss59.html
<!--@im\port'\ja\vasc\ript:alert("XSS")';-->
xss60.html
<IMG XSSCleaned="xss:expr/*XSS*/ession(alert('XSS'))">
xss61.html
<XSS XSSCleaned="xss:expression(alert('XSS'))">
xss62.html
exp/*<A XSSCleaned='no\xss:noxss("*//*");
xss:&#101;x&#x2F;*XSS*//*/*/pression(alert("XSS"))'>
xss63.html
<!--alert('XSS');-->
xss64.html
<!--.XSS{background-image:url("XSSCleanedalert('XSS')");}--><A CLASS=XSS></A>
xss65.html
<!--BODY{background:url("XSSCleanedalert('XSS')")}-->
xss66.html

xss67.html
<!-- <BASE HREF="XSSCleanedalert('XSS');//"> -->
xss68.html
<XSSCleaned_tag TYPE="text/x-scriptlet" DATA="http://ha.ckers.org/scriptlet.html"></XSSCleaned_tag>
xss69.html
<XSSCleaned_tag classid=clsid:ae24fdae-03c6-11d1-8b76-0080c744f389><param name=XSSCleanedalert('XSS')></XSSCleaned_tag>
xss70.html
<XSSCleaned_tag SRC="http://ha.ckers.org/xss.swf" AllowScriptAccess="always"></XSSCleaned_tag>
xss71.html
<XSSCleaned_tag SRC="data:image/svg+xml;base64,PHN2ZyB4bWxuczpzdmc9Imh0dH A6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcv MjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hs aW5rIiB2ZXJzaW9uPSIxLjAiIHg9IjAiIHk9IjAiIHdpZHRoPSIxOTQiIGhlaWdodD0iMjAw IiBpZD0ieHNzIj48c2NyaXB0IHR5cGU9InRleHQvZWNtYXNjcmlwdCI+YWxlcnQoIlh TUyIpOzwvc2NyaXB0Pjwvc3ZnPg==" type="image/svg+xml" AllowScriptAccess="always"></XSSCleaned_tag>
xss72.html
<HTML xmlns:xss>
  <XSSCleaned_tagnamespace="xss" implementation="http://ha.ckers.org/xss.htc">
  <xss:xss>XSS</xss:xss>
</HTML>
xss73.html
<XSSCleaned_tag ID=I><X><C><![CDATA[<IMG SRC="javas]]><![CDATA[cript:alert('XSS');">]]>
</C></X></XSSCleaned_tag><SPAN DATASRC=#I DATAFLD=C DATAFORMATAS=HTML></SPAN>
xss74.html
<XSSCleaned_tag ID="xss"><I><B>&lt;IMG SRC="XSSCleanedalert('XSS')"&gt;</B></I></XSSCleaned_tag>
<SPAN DATASRC="#xss" DATAFLD="B" DATAFORMATAS="HTML"></SPAN>
xss75.html
<XSSCleaned_tag SRC="xsstest.xml" ID=I></XSSCleaned_tag>
<SPAN DATASRC=#I DATAFLD=C DATAFORMATAS=HTML></SPAN>
xss76.html
<HTML><BODY>
<?XSSCleaned_tag:namespace prefix="t" ns="urn:schemas-microsoft-com:time">
<XSSCleaned_tagnamespace="t" implementation="#default#time2">
<t:set attributeName="innerHTML" to="XSS&lt;SCRIPT DEFER&gt;alert(&quot;XSS&quot;)&lt;/SCRIPT&gt;">
</BODY></HTML>
xss77.html
<XSSCleaned_tag SRC="http://ha.ckers.org/xss.jpg"><XSSCleaned_tag>
xss78.html
<IMG SRC="XSSCleanedalert('XSS')"
xss79.html
<XSSCleaned_script />
xss80.html
<XSSCleaned_script />
xss81.html
<XSSCleaned_script />
xss82.html
<XSSCleaned_script />
xss83.html
<XSSCleaned_script />
xss84.html
<XSSCleaned_script />
xss85.html
<XSSCleaned_script />PT SRC="http://ha.ckers.org/a.js"></XSSCleaned_tag>
xss97.html
<body/onloadXSSCleaned=alert(/xss/)>
xss98.html
<XSSCleaned_tagset rows="15,15,15,15,15,15,15,15,15,*">
<XSSCleaned_tag src="mailbox.php?page=1&amp;actionID=delete_messages&amp;targetMbox=&amp;newMbox=0&amp;flag=&amp;indices%5B%5D=199&amp;indices%5B%5D=200&amp;indices%5B%5D=201&amp;indices%5B%5D=202&amp;indices%5B%5D=203&amp;indices%5B%5D=204&amp;indices%5B%5D=205&amp;indices%5B%5D=206&amp;indices%5B%5D=207&amp;indices%5B%5D=208&amp;indices%5B%5D=209&amp;indices%5B%5D=210&amp;indices%5B%5D=211&amp;indices%5B%5D=212&amp;indices%5B%5D=213&amp;indices%5B%5D=214&amp;indices%5B%5D=215&amp;indices%5B%5D=216&amp;indices%5B%5D=217&amp;indices%5B%5D=218&amp;indices%5B%5D=219&amp;indices%5B%5D=220&amp;indices%5B%5D=221&amp;indices%5B%5D=222&amp;indices%5B%5D=223&amp;indices%5B%5D=224&amp;indices%5B%5D=225&amp;indices%5B%5D=226&amp;indices%5B%5D=227&amp;indices%5B%5D=228&amp;indices%5B%5D=229&amp;indices%5B%5D=230&amp;indices%5B%5D=231&amp;indices%5B%5D=232&amp;indices%5B%5D=233&amp;indices%5B%5D=234&amp;indices%5B%5D=235&amp;indices%5B%5D=236&amp;indices%5B%5D=237&amp;indices%5B%5D=238&amp;indices%5B%5D=239&amp;indices%5B%5D=240&amp;indices%5B%5D=241&amp;indices%5B%5D=242&amp;indices%5B%5D=243&amp;indices%5B%5D=244&amp;indices%5B%5D=245&amp;indices%5B%5D=246&amp;indices%5B%5D=247&amp;indices%5B%5D=248&amp;indices%5B%5D=249&amp;indices%5B%5D=250&amp;indices%5B%5D=251&amp;indices%5B%5D=252&amp;indices%5B%5D=253&amp;indices%5B%5D=254&amp;indices%5B%5D=255&amp;indices%5B%5D=256&amp;indices%5B%5D=257&amp;indices%5B%5D=258&amp;indices%5B%5D=259&amp;indices%5B%5D=260&amp;indices%5B%5D=261&amp;indices%5B%5D=262&amp;indices%5B%5D=263&amp;indices%5B%5D=264&amp;indices%5B%5D=265&amp;indices%5B%5D=266&amp;indices%5B%5D=267&amp;indices%5B%5D=268&amp;indices%5B%5D=269&amp;indices%5B%5D=270&amp;indices%5B%5D=271&amp;indices%5B%5D=272&amp;indices%5B%5D=273&amp;indices%5B%5D=274&amp;indices%5B%5D=275&amp;indices%5B%5D=276&amp;indices%5B%5D=277&amp;indices%5B%5D=278&amp;indices%5B%5D=279&amp;indices%5B%5D=280&amp;indices%5B%5D=281&amp;indices%5B%5D=282&amp;indices%5B%5D=283&amp;indices%5B%5D=284&amp;indices%5B%5D=285&amp;indices%5B%5D=286&amp;indices%5B%5D=287&amp;indices%5B%5D=288&amp;indices%5B%5D=289&amp;indices%5B%5D=290&amp;indices%5B%5D=291&amp;indices%5B%5D=292&amp;indices%5B%5D=293&amp;indices%5B%5D=294&amp;indices%5B%5D=295&amp;indices%5B%5D=296&amp;indices%5B%5D=297&amp;indices%5B%5D=298">
<XSSCleaned_tag src="mailbox.php?page=1&amp;actionID=delete_messages&amp;targetMbox=&amp;newMbox=0&amp;flag=&amp;indices%5B%5D=299&amp;indices%5B%5D=300&amp;indices%5B%5D=301&amp;indices%5B%5D=302&amp;indices%5B%5D=303&amp;indices%5B%5D=304&amp;indices%5B%5D=305&amp;indices%5B%5D=306&amp;indices%5B%5D=307&amp;indices%5B%5D=308&amp;indices%5B%5D=309&amp;indices%5B%5D=310&amp;indices%5B%5D=311&amp;indices%5B%5D=312&amp;indices%5B%5D=313&amp;indices%5B%5D=314&amp;indices%5B%5D=315&amp;indices%5B%5D=316&amp;indices%5B%5D=317&amp;indices%5B%5D=318&amp;indices%5B%5D=319&amp;indices%5B%5D=320&amp;indices%5B%5D=321&amp;indices%5B%5D=322&amp;indices%5B%5D=323&amp;indices%5B%5D=324&amp;indices%5B%5D=325&amp;indices%5B%5D=326&amp;indices%5B%5D=327&amp;indices%5B%5D=328&amp;indices%5B%5D=329&amp;indices%5B%5D=330&amp;indices%5B%5D=331&amp;indices%5B%5D=332&amp;indices%5B%5D=333&amp;indices%5B%5D=334&amp;indices%5B%5D=335&amp;indices%5B%5D=336&amp;indices%5B%5D=337&amp;indices%5B%5D=338&amp;indices%5B%5D=339&amp;indices%5B%5D=340&amp;indices%5B%5D=341&amp;indices%5B%5D=342&amp;indices%5B%5D=343&amp;indices%5B%5D=344&amp;indices%5B%5D=345&amp;indices%5B%5D=346&amp;indices%5B%5D=347&amp;indices%5B%5D=348&amp;indices%5B%5D=349&amp;indices%5B%5D=350&amp;indices%5B%5D=351&amp;indices%5B%5D=352&amp;indices%5B%5D=353&amp;indices%5B%5D=354&amp;indices%5B%5D=355&amp;indices%5B%5D=356&amp;indices%5B%5D=357&amp;indices%5B%5D=358&amp;indices%5B%5D=359&amp;indices%5B%5D=360&amp;indices%5B%5D=361&amp;indices%5B%5D=362&amp;indices%5B%5D=363&amp;indices%5B%5D=364&amp;indices%5B%5D=365&amp;indices%5B%5D=366&amp;indices%5B%5D=367&amp;indices%5B%5D=368&amp;indices%5B%5D=369&amp;indices%5B%5D=370&amp;indices%5B%5D=371&amp;indices%5B%5D=372&amp;indices%5B%5D=373&amp;indices%5B%5D=374&amp;indices%5B%5D=375&amp;indices%5B%5D=376&amp;indices%5B%5D=377&amp;indices%5B%5D=378&amp;indices%5B%5D=379&amp;indices%5B%5D=380&amp;indices%5B%5D=381&amp;indices%5B%5D=382&amp;indices%5B%5D=383&amp;indices%5B%5D=384&amp;indices%5B%5D=385&amp;indices%5B%5D=386&amp;indices%5B%5D=387&amp;indices%5B%5D=388&amp;indices%5B%5D=389&amp;indices%5B%5D=390&amp;indices%5B%5D=391&amp;indices%5B%5D=392&amp;indices%5B%5D=393&amp;indices%5B%5D=394&amp;indices%5B%5D=395&amp;indices%5B%5D=396&amp;indices%5B%5D=397&amp;indices%5B%5D=398">
<XSSCleaned_tag src="mailbox.php?page=1&amp;actionID=delete_messages&amp;targetMbox=&amp;newMbox=0&amp;flag=&amp;indices%5B%5D=399&amp;indices%5B%5D=400&amp;indices%5B%5D=401&amp;indices%5B%5D=402&amp;indices%5B%5D=403&amp;indices%5B%5D=404&amp;indices%5B%5D=405&amp;indices%5B%5D=406&amp;indices%5B%5D=407&amp;indices%5B%5D=408&amp;indices%5B%5D=409&amp;indices%5B%5D=410&amp;indices%5B%5D=411&amp;indices%5B%5D=412&amp;indices%5B%5D=413&amp;indices%5B%5D=414&amp;indices%5B%5D=415&amp;indices%5B%5D=416&amp;indices%5B%5D=417&amp;indices%5B%5D=418&amp;indices%5B%5D=419&amp;indices%5B%5D=420&amp;indices%5B%5D=421&amp;indices%5B%5D=422&amp;indices%5B%5D=423&amp;indices%5B%5D=424&amp;indices%5B%5D=425&amp;indices%5B%5D=426&amp;indices%5B%5D=427&amp;indices%5B%5D=428&amp;indices%5B%5D=429&amp;indices%5B%5D=430&amp;indices%5B%5D=431&amp;indices%5B%5D=432&amp;indices%5B%5D=433&amp;indices%5B%5D=434&amp;indices%5B%5D=435&amp;indices%5B%5D=436&amp;indices%5B%5D=437&amp;indices%5B%5D=438&amp;indices%5B%5D=439&amp;indices%5B%5D=440&amp;indices%5B%5D=441&amp;indices%5B%5D=442&amp;indices%5B%5D=443&amp;indices%5B%5D=444&amp;indices%5B%5D=445&amp;indices%5B%5D=446&amp;indices%5B%5D=447&amp;indices%5B%5D=448&amp;indices%5B%5D=449&amp;indices%5B%5D=450&amp;indices%5B%5D=451&amp;indices%5B%5D=452&amp;indices%5B%5D=453&amp;indices%5B%5D=454&amp;indices%5B%5D=455&amp;indices%5B%5D=456&amp;indices%5B%5D=457&amp;indices%5B%5D=458&amp;indices%5B%5D=459&amp;indices%5B%5D=460&amp;indices%5B%5D=461&amp;indices%5B%5D=462&amp;indices%5B%5D=463&amp;indices%5B%5D=464&amp;indices%5B%5D=465&amp;indices%5B%5D=466&amp;indices%5B%5D=467&amp;indices%5B%5D=468&amp;indices%5B%5D=469&amp;indices%5B%5D=470&amp;indices%5B%5D=471&amp;indices%5B%5D=472&amp;indices%5B%5D=473&amp;indices%5B%5D=474&amp;indices%5B%5D=475&amp;indices%5B%5D=476&amp;indices%5B%5D=477&amp;indices%5B%5D=478&amp;indices%5B%5D=479&amp;indices%5B%5D=480&amp;indices%5B%5D=481&amp;indices%5B%5D=482&amp;indices%5B%5D=483&amp;indices%5B%5D=484&amp;indices%5B%5D=485&amp;indices%5B%5D=486&amp;indices%5B%5D=487&amp;indices%5B%5D=488&amp;indices%5B%5D=489&amp;indices%5B%5D=490&amp;indices%5B%5D=491&amp;indices%5B%5D=492&amp;indices%5B%5D=493&amp;indices%5B%5D=494&amp;indices%5B%5D=495&amp;indices%5B%5D=496&amp;indices%5B%5D=497&amp;indices%5B%5D=498">
<XSSCleaned_tag src="mailbox.php?page=1&amp;actionID=delete_messages&amp;targetMbox=&amp;newMbox=0&amp;flag=&amp;indices%5B%5D=499&amp;indices%5B%5D=500&amp;indices%5B%5D=501&amp;indices%5B%5D=502&amp;indices%5B%5D=503&amp;indices%5B%5D=504&amp;indices%5B%5D=505&amp;indices%5B%5D=506&amp;indices%5B%5D=507&amp;indices%5B%5D=508&amp;indices%5B%5D=509&amp;indices%5B%5D=510&amp;indices%5B%5D=511&amp;indices%5B%5D=512&amp;indices%5B%5D=513&amp;indices%5B%5D=514&amp;indices%5B%5D=515&amp;indices%5B%5D=516&amp;indices%5B%5D=517&amp;indices%5B%5D=518&amp;indices%5B%5D=519&amp;indices%5B%5D=520&amp;indices%5B%5D=521&amp;indices%5B%5D=522&amp;indices%5B%5D=523&amp;indices%5B%5D=524&amp;indices%5B%5D=525&amp;indices%5B%5D=526&amp;indices%5B%5D=527&amp;indices%5B%5D=528&amp;indices%5B%5D=529&amp;indices%5B%5D=530&amp;indices%5B%5D=531&amp;indices%5B%5D=532&amp;indices%5B%5D=533&amp;indices%5B%5D=534&amp;indices%5B%5D=535&amp;indices%5B%5D=536&amp;indices%5B%5D=537&amp;indices%5B%5D=538&amp;indices%5B%5D=539&amp;indices%5B%5D=540&amp;indices%5B%5D=541&amp;indices%5B%5D=542&amp;indices%5B%5D=543&amp;indices%5B%5D=544&amp;indices%5B%5D=545&amp;indices%5B%5D=546&amp;indices%5B%5D=547&amp;indices%5B%5D=548&amp;indices%5B%5D=549&amp;indices%5B%5D=550&amp;indices%5B%5D=551&amp;indices%5B%5D=552&amp;indices%5B%5D=553&amp;indices%5B%5D=554&amp;indices%5B%5D=555&amp;indices%5B%5D=556&amp;indices%5B%5D=557&amp;indices%5B%5D=558&amp;indices%5B%5D=559&amp;indices%5B%5D=560&amp;indices%5B%5D=561&amp;indices%5B%5D=562&amp;indices%5B%5D=563&amp;indices%5B%5D=564&amp;indices%5B%5D=565&amp;indices%5B%5D=566&amp;indices%5B%5D=567&amp;indices%5B%5D=568&amp;indices%5B%5D=569&amp;indices%5B%5D=570&amp;indices%5B%5D=571&amp;indices%5B%5D=572&amp;indices%5B%5D=573&amp;indices%5B%5D=574&amp;indices%5B%5D=575&amp;indices%5B%5D=576&amp;indices%5B%5D=577&amp;indices%5B%5D=578&amp;indices%5B%5D=579&amp;indices%5B%5D=580&amp;indices%5B%5D=581&amp;indices%5B%5D=582&amp;indices%5B%5D=583&amp;indices%5B%5D=584&amp;indices%5B%5D=585&amp;indices%5B%5D=586&amp;indices%5B%5D=587&amp;indices%5B%5D=588&amp;indices%5B%5D=589&amp;indices%5B%5D=590&amp;indices%5B%5D=591&amp;indices%5B%5D=592&amp;indices%5B%5D=593&amp;indices%5B%5D=594&amp;indices%5B%5D=595&amp;indices%5B%5D=596&amp;indices%5B%5D=597&amp;indices%5B%5D=598">
<XSSCleaned_tag src="mailbox.php?page=1&amp;actionID=delete_messages&amp;targetMbox=&amp;newMbox=0&amp;flag=&amp;indices%5B%5D=599&amp;indices%5B%5D=600&amp;indices%5B%5D=601&amp;indices%5B%5D=602&amp;indices%5B%5D=603&amp;indices%5B%5D=604&amp;indices%5B%5D=605&amp;indices%5B%5D=606&amp;indices%5B%5D=607&amp;indices%5B%5D=608&amp;indices%5B%5D=609&amp;indices%5B%5D=610&amp;indices%5B%5D=611&amp;indices%5B%5D=612&amp;indices%5B%5D=613&amp;indices%5B%5D=614&amp;indices%5B%5D=615&amp;indices%5B%5D=616&amp;indices%5B%5D=617&amp;indices%5B%5D=618&amp;indices%5B%5D=619&amp;indices%5B%5D=620&amp;indices%5B%5D=621&amp;indices%5B%5D=622&amp;indices%5B%5D=623&amp;indices%5B%5D=624&amp;indices%5B%5D=625&amp;indices%5B%5D=626&amp;indices%5B%5D=627&amp;indices%5B%5D=628&amp;indices%5B%5D=629&amp;indices%5B%5D=630&amp;indices%5B%5D=631&amp;indices%5B%5D=632&amp;indices%5B%5D=633&amp;indices%5B%5D=634&amp;indices%5B%5D=635&amp;indices%5B%5D=636&amp;indices%5B%5D=637&amp;indices%5B%5D=638&amp;indices%5B%5D=639&amp;indices%5B%5D=640&amp;indices%5B%5D=641&amp;indices%5B%5D=642&amp;indices%5B%5D=643&amp;indices%5B%5D=644&amp;indices%5B%5D=645&amp;indices%5B%5D=646&amp;indices%5B%5D=647&amp;indices%5B%5D=648&amp;indices%5B%5D=649&amp;indices%5B%5D=650&amp;indices%5B%5D=651&amp;indices%5B%5D=652&amp;indices%5B%5D=653&amp;indices%5B%5D=654&amp;indices%5B%5D=655&amp;indices%5B%5D=656&amp;indices%5B%5D=657&amp;indices%5B%5D=658&amp;indices%5B%5D=659&amp;indices%5B%5D=660&amp;indices%5B%5D=661&amp;indices%5B%5D=662&amp;indices%5B%5D=663&amp;indices%5B%5D=664&amp;indices%5B%5D=665&amp;indices%5B%5D=666&amp;indices%5B%5D=667&amp;indices%5B%5D=668&amp;indices%5B%5D=669&amp;indices%5B%5D=670&amp;indices%5B%5D=671&amp;indices%5B%5D=672&amp;indices%5B%5D=673&amp;indices%5B%5D=674&amp;indices%5B%5D=675&amp;indices%5B%5D=676&amp;indices%5B%5D=677&amp;indices%5B%5D=678&amp;indices%5B%5D=679&amp;indices%5B%5D=680&amp;indices%5B%5D=681&amp;indices%5B%5D=682&amp;indices%5B%5D=683&amp;indices%5B%5D=684&amp;indices%5B%5D=685&amp;indices%5B%5D=686&amp;indices%5B%5D=687&amp;indices%5B%5D=688&amp;indices%5B%5D=689&amp;indices%5B%5D=690&amp;indices%5B%5D=691&amp;indices%5B%5D=692&amp;indices%5B%5D=693&amp;indices%5B%5D=694&amp;indices%5B%5D=695&amp;indices%5B%5D=696&amp;indices%5B%5D=697&amp;indices%5B%5D=698">
<XSSCleaned_tag src="mailbox.php?page=1&amp;actionID=expunge_mailbox">
<XSSCleaned_tag src="mailbox.php?page=1&amp;actionID=expunge_mailbox">
<XSSCleaned_tag src="mailbox.php?page=1&amp;actionID=expunge_mailbox">
<XSSCleaned_tag src="mailbox.php?page=1&amp;actionID=expunge_mailbox">
<XSSCleaned_tag src="http://secunia.com/">
</XSSCleaned_tagset>
xss99.html
<img src=""> <BODY ONLOADXSSCleaned="a();"><XSSCleaned_script /><"" />
style_xss01.html
<BASE HREF="XSSCleanedalert('XSS');//">
