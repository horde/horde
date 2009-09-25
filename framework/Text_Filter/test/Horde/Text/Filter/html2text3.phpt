--TEST--
Horde_Text_Filter_Html2text quoting test
--FILE--
<?php

require_once 'Horde/String.php';
require_once 'Horde/Util.php';
require dirname(__FILE__) . '/../../../../lib/Horde/Text/Filter.php';
require dirname(__FILE__) . '/../../../../lib/Horde/Text/Filter/Html2text.php';
$html = <<<EOT
<p>Zitat von Roberto Maurizzi &lt;roberto.maurizzi@gmail.com&gt;:</p>
  <blockquote type="cite">
    <div class="gmail_quote">
      <blockquote style="border-left: 1px solid #cccccc; margin: 0pt 0pt 0pt 0.8ex; padding-left: 1ex;" class="gmail_quote">
        <blockquote style="border-left: 1px solid #cccccc; margin: 0pt 0pt 0pt 0.8ex; padding-left: 1ex;" class="gmail_quote"> 
          <div class="Ih2E3d">
            <blockquote style="border-left: 1px solid #cccccc; margin: 0pt 0pt 0pt 0.8ex; padding-left: 1ex;" class="gmail_quote">4) In Turba, I can select a VFS driver to use. Currently it is set to<br />

None and turba seems to be working fine. What does Turba use the VFS<br />
for?<br /> </blockquote> 
          </div>
        </blockquote><br />
You can attach files to contacts with that.<br /> <br />
Jan.<br /><font color="#888888"> </font>
      </blockquote>
      <div><br /></div>
    </div>Anything similar for Kronolith, maybe in the new version?<br />I've googled a little and only found a discussion in 2004 about having attachment (or links) from VFS in Kronolith.<br />
I'd really like to be able to attach all my taxes forms to the day I have to pay them ;-) and more in general all the extra documentation regarding an appointment.<br /><br />Ciao,<br />&nbsp; Roberto<br /><br /> 
  </blockquote> 
  <p>Some unquoted line with single ' quotes.</p>
  <p class="imp-signature"><!--begin_signature-->Jan.<br /> <br />
-- <br />
Do you need professional PHP or Horde consulting?<br /> <a target="_blank" href="http://horde.org/consulting/">http://horde.org/consulting/</a><!--end_signature--></p>
EOT;
echo Horde_Text_Filter::filter($html, 'html2text');

?>
--EXPECT--
Zitat von Roberto Maurizzi <roberto.maurizzi@gmail.com>: 

> > > > 4) In Turba, I can select a VFS driver to use. Currently it is
> > set
> > > > to
> > > > None and turba seems to be working fine. What does Turba use
> the
> > > VFS
> > > > for?
> >
> > You can attach files to contacts with that.
> >
> > Jan.
>
> Anything similar for Kronolith, maybe in the new version?
> I've googled a little and only found a discussion in 2004 about
> having attachment (or links) from VFS in Kronolith.
> I'd really like to be able to attach all my taxes forms to the day I
> have to pay them ;-) and more in general all the extra documentation
> regarding an appointment.
>
> Ciao,
>   Roberto

Some unquoted line with single ' quotes. 

Jan.

-- 
Do you need professional PHP or Horde consulting?
http://horde.org/consulting/
