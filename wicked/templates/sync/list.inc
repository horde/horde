<?php
/**
 * Formats time according to user prefs
 *
 * @param int $timestamp message timestamp
 *
 * @return string $date formatted date
 */
function _format_date($timestamp)
{
    return strftime($GLOBALS['prefs']->getValue('date_format'), $timestamp)
        . ' ' . date($GLOBALS['prefs']->getValue('twentyFour') ? 'G:i' : 'g:ia', $timestamp);
}

/* Find new pages to download */
echo  '<h2>' . _("New pages on remote server") . ': ' . $GLOBALS['session']->get('wicked', 'sync_url') . ' (' . $GLOBALS['session']->get('wicked', 'sync_user') . ')</h2>';
if (empty($new_remote)) {
    echo _("No pages found");
} else {
    echo '<table id="new-remote" class="sortable striped"><thead><tr>'
        . ' <th>' . _("Page") . '</th>'
        . ' <th>' . _("Version") . '</th>'
        . ' <th>' . _("Created") . '</th>'
        . ' <th>' . _("Author") . '</th>'
        . '<th>' . _("Actions") . '</th>'
        . '</tr></thead><tbody>';
    $download = Horde_Util::addParameter(Wicked::url('SyncPages'), array('actionID' => 'sync_download', 'sync_page' => '')) ;
    foreach ($new_remote as $pageName => $info) {
        echo  '<tr>'
            . '<td>' . $pageName . '</td>'
            . '<td>' . $info['page_majorversion'] . '.' . $info['page_minorversion'] . '</td>'
            . '<td>' . _format_date($info['version_created']) . '</td>'
            . '<td>' . $info['change_author'] . '</td>'
            .  '<td>' . $this->_viewLink($pageName, false)
            .  ' | <a href="' .  $download . $pageName . '">' . _("Download") . '</a></td>'
            . '</tr>';
    }
    echo '</tbody></table>';
}

/* Find new pages to upload */
echo '<h2>' . _("New pages on local server") . '</h2>';
if (empty($new_local)) {
    echo _("No pages found");
} else {
    echo '<table id="new-remote" class="sortable striped"><thead><tr>'
            . ' <th>' . _("Page") . '</th>'
            . ' <th>' . _("Version") . '</th>'
            . ' <th>' . _("Created") . '</th>'
            . ' <th>' . _("Author") . '</th>'
            . '<th>' . _("Actions") . '</th>'
            . '</tr></thead><tbody>';
    $upload = Horde_Util::addParameter(Wicked::url('SyncPages'), array('actionID' => 'sync_upload', 'sync_page' => '')) ;
    foreach ($new_local as $pageName => $info) {
        echo  '<tr>'
            . '<td>' . $pageName . '</td>'
            . '<td>' . $info['page_majorversion'] . '.' . $info['page_minorversion'] . '</td>'
            . '<td>' . _format_date($info['version_created']) . '</td>'
            . '<td>' . $info['change_author'] . '</td>'
            .  '<td>' . $this->_viewLink($pageName)
            .  ' | <a href="' .  $upload . $pageName . '">' .  _("Upload") . '</a></td>'
            . '</tr>';
    }

    echo '</tbody></table>';
}

echo '<h2>' . _("Pages to sync") . '</h2>';
if (empty($sync_pages)) {
    echo _("No pages to sync");
} else {
    echo '<table id="new-remote" class="sortable striped"><thead><tr>'
            . ' <th>' . _("Page") . '</th><th>' . _("Actions") . '</th></tr></thead><tbody>';
    $showdiff = Horde_Util::addParameter(Wicked::url('SyncDiff'), array('actionID' => 'sync_diff', 'sync_page' => ''), null, false) ;
    foreach ($sync_pages as $pageName) {
        echo  '<tr><td>' . $pageName . '</td>'
            . '<td>' . $this->_viewLink($pageName)
            . ' | ' .  $this->_viewLink($pageName, false)
            . ' |  <a href="' . $showdiff . $pageName . '" target="_blank">' . _("Show changes") . '</a></td>'
            . '</tr>';
    }
    echo '</tbody></table>';
}

