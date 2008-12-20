</tbody>
</table>
<script type="text/javascript">
//<![CDATA[
function setRevisionLogTitles()
{
    var revisionLogMap = [];
    <?php
    foreach ($revList as $lg) {
        // The code below attempts to account for the following:
        //   1) log text with line breaks.
        //   2) log text with inline HTML.
        $logmsg = Text_Filter::filter($lg->log, 'text2html', array('parselevel' => TEXT_HTML_NOHTML, 'charset' => NLS::getCharset(), 'class' => ''));

        // The above function converts newlines to HTML, and we don't
        // want to escape those. Since the entire text is enclosed in
        // single-quotes in JavaScipt, it should suffice to simply
        // escape those. Everything else is already escaped.
        $logmsg = str_replace('</', '<\/',
            '<strong>' . $lg->rev . '</strong> ' .
            '&lt;' . Chora::showAuthorName($lg->queryAuthor(), true) . '&gt; ' .
            '<strong>' . sprintf(_("%s ago"), Chora::readableTime($lg->date, true)) . '</strong>' .
            '<br />' .
            str_replace(array("'", "\n"), array('&#039;', '\n'), $logmsg));
        echo "revisionLogMap['" . $lg->rev . "'] = '$logmsg';\n";
    }
    ?>

    var links = document.getElementsByTagName('A');
    for (var i = 0; i < links.length; ++i) {
        var rx = RegExp('^p?rev_([0-9.]+)_\\d+$').exec(links[i].id);
        if (!rx) {
            continue;
        }
        links[i].setAttribute('title', revisionLogMap[rx[1]]);
    }

    ToolTips.attachBehavior();
}
addEvent(window, 'load', setRevisionLogTitles);
//]]>
</script>
