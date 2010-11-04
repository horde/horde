<?php
/**
 * Fomates time accoring to user prefs
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

$local = $this->getLocalPageInfo($this->_pageName);
$remote = $this->getRemotePageInfo($this->_pageName);

?>
<table style="width: 100%" class="striped">
<thead>
<tr>
<th><?php echo _("Location") ?></th>
<th><?php echo _("Version") ?></th>
<th><?php echo _("Created") ?></th>
<th><?php echo _("Author") ?></th>
<th><?php echo _("Change log") ?></th>
<th><?php echo _("Checksum") ?></th>
</tr>
</thead>
<tbody>
<tr>
<td><?php echo _("Local") ?></td>
<td><?php echo $local['page_majorversion'] . '.' . $local['page_minorversion'] ?></td>
<td><?php echo _format_date($local['version_created']) ?></td>
<td><?php echo $local['change_author'] ?></td>
<td><?php echo $local['change_log'] ?></td>
<td><?php echo $local['page_checksum'] ?></td>
</tr>
<tr>
<td><?php echo _("Remote") ?></td>
<td><?php echo $remote['page_majorversion'] . '.' . $remote['page_minorversion'] ?></td>
<td><?php echo _format_date($remote['version_created']) ?></td>
<td><?php echo $remote['change_author'] ?></td>
<td><?php echo $remote['change_log'] ?></td>
<td><?php echo $remote['page_checksum'] ?></td>
</tr>
</tbody>
</table>

<?php
$match = $this->_getSameVersion();
if ($match) {
?>
<h1 class="header"><?php echo _("Found same content in history") ?></h1>
<table style="width: 100%" class="striped">
<thead>
<tr>
<th><?php echo _("Location") ?></th>
<th><?php echo _("Version") ?></th>
<th><?php echo _("Created") ?></th>
<th><?php echo _("Author") ?></th>
<th><?php echo _("Change log") ?></th>
<th><?php echo _("Checksum") ?></th>
</tr>
</thead>
<tbody>
<tr>
<td><?php echo _("Local") ?></td>
<td><?php echo $match['local']['page_majorversion'] . '.' . $match['local']['page_minorversion'] ?></td>
<td><?php echo _format_date($match['local']['version_created']) ?></td>
<td><?php echo $match['local']['change_author'] ?></td>
<td><?php echo $match['local']['change_log'] ?></td>
<td><?php echo $match['local']['page_checksum'] ?></td>
</tr>
<tr>
<td><?php echo _("Remote") ?></td>
<td><?php echo $match['remote']['page_majorversion'] . '.' . $match['remote']['page_minorversion'] ?></td>
<td><?php echo _format_date($match['remote']['version_created']) ?></td>
<td><?php echo $match['remote']['change_author'] ?></td>
<td><?php echo $match['remote']['change_log'] ?></td>
<td><?php echo $match['remote']['page_checksum'] ?></td>
</tr>
</tbody>
</table>
<?php
}

// nofiy what form returned
$GLOBALS['notification']->notify(array('listeners' => 'status'));

?>

<h1 class="header">
<span style="float: right">
<a href="<?php  echo Horde_Util::addParameter(Wicked::url('SyncDiff'), array('sync_page' => $this->_pageName, 'inverse' => !$inverse)) ?>">Inverse</a> |
<a href="" target="_blank"><?php echo _("Download") ?></a> |
<a href="" target="_blank"><?php echo _("Upload") ?></a> |
<a href="<?php  echo Horde_Util::addParameter(Wicked::url('EditPage'), 'referrer', $this->_pageName) ?>" target="_blank"><?php echo _("Edit local") ?></a> |
<a href="<?php  echo $GLOBALS['session']->get('wicked', 'sync_edit') . $this->_pageName ?>" target="_blank"><?php echo _("Edit remote") ?></a>
</span>
<?php echo sprintf(_("Diff for %s between %s and %s"), $page->pageTitle(), $name1, $name2) ?>
</h1>
<div class="text headerbox" style="padding:5px">
<pre><?php  echo $renderer->render($diff) ?></pre>
</div>
