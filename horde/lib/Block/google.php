<?php

if (isset($GLOBALS['conf']['api']['googlesearch'])) {
    $block_name = _("Google Search");
}

/**
 * @package Horde_Block
 */
class Horde_Block_Horde_google extends Horde_Block
{
    protected $_app = 'horde';

    /**
     * The title to go in this block.
     *
     * @return string   The title text.
     */
    protected function _title()
    {
        return _("Google Search");
    }

    /**
     * The content to go in this block.
     *
     * @return string   The content
     */
    protected function _content()
    {
        if (empty($GLOBALS['conf']['api']['googlesearch'])) {
            return '<p><em>' . _("Google search is not enabled.") . '</em></p>';
        }

        Horde::startBuffer();
?>
<link href="http://www.google.com/uds/css/gsearch.css" type="text/css" rel="stylesheet"/>
<div id="googlesearch">...</div>
<script type="text/javascript" src="http://www.google.com/uds/api?file=uds.js&amp;v=1.0&amp;key=<?php echo htmlspecialchars($GLOBALS['conf']['api']['googlesearch']) ?>"></script>
<script type="text/javascript">
//<![CDATA[
function GoogleSearchSetup()
{
    // Create a search control
    var searchControl = new GSearchControl();

    // Add in a full set of searchers
    searchControl.addSearcher(new GwebSearch());
    searchControl.addSearcher(new GvideoSearch());
    searchControl.addSearcher(new GblogSearch());
    searchControl.addSearcher(new GnewsSearch());
    searchControl.addSearcher(new GbookSearch());

    // create a drawOptions object
    var drawOptions = new GdrawOptions();

    // tell the searcher to draw itself in tabbed mode
    drawOptions.setDrawMode(GSearchControl.DRAW_MODE_TABBED);
    searchControl.draw(document.getElementById('googlesearch'), drawOptions);
}
GSearch.setOnLoadCallback(GoogleSearchSetup);
//]]>
</script>
<?php
        return Horde::endBuffer();
    }

}
