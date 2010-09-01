<tr valign="top">
<td colspan="7">

<table style="width: 100%;">
<tr valign="top">
<td style="text-align: center">
<?php if ($row['picture']): ?>
<a href="<?php echo News::getImageUrl($id, 'big'); ?>" target="_blank" title="<?php echo _("Click for full picture") ?>">
    <img src="<?php echo News::getImageUrl($id, 'small'); ?>" style="height: 50px; width: 50px;" />
</a><br />
<a href="<?php echo Horde_Util::addParameter($browse_url, array('id' => $id, 'actionID' => 'deletepicture')) ?>" onclick="confirm('<?php echo _("Do you really want to delete this picture?") ?>');"><?php echo _("Delete picture") ?></a>
<?php endif; ?>
</td>
<td>
<?php

if (isset($allowed_cats[$row['category1']])) {
    echo _("Primary category") . ': ' . $allowed_cats[$row['category1']] . "<br />\n";
} else {
    echo _("Primary category") . ': ' . $row['category1'] . '!!!<br />' . "\n";
}

if ($row['category2']>0) {
    echo _("Secondary category") . ': ' . $allowed_cats[$row['category2']] . "<br />\n";
}

if (substr($row['unpublish'], 0, 1) != 0) {
    echo _("Unpublish date") . ': ' . $row['unpublish'] . "<br />\n";
}

if ($conf['comments']['allow']) {
    echo _("Allow comments") . ': ' . ($row['comments']>-1  ? _("Yes") : _("No")) . "<br />\n";
}

if ($row['editor']) {
    echo _("Editor") . ': ' . $row['editor'] . "<br />\n";
}

if ($row['sortorder']) {
    echo _("Sort order") . ': ' . $row['sortorder'] . "<br />\n";
}

if (isset($row['parents'])) {
    echo _("Parents") . ': ' . sizeof($row['parents']) . "<br />\n";
}

if (!empty($row['source']) && isset($GLOBALS['cfgSources'][$row['source']])) {
    echo _("Source") . ': ' . $GLOBALS['cfgSources'][$row['source']]['title'] . '<br />';
}

if (isset($row['sourcelink'])) {
    echo _("Source link") . ': <a href="' . $row['sourcelink'] . '" target="_blank">' . $row['sourcelink'] . '</a><br />';
}

// schedul
if (!empty($row['selling'])) {
    $item = explode('|', $row['selling']);
    echo _("Selling item") . ': ' . $registry->get('name', $registry->hasInterface($item[0]));
    $articles = $registry->call($item[0]  . '/listCostObjects');
    foreach ($articles[0]['objects'] as $item_data) {
        if ($item_data['id'] == $item[1]) {
            echo ' - ' . $item_data['name'];
            break;
        }
    }
}

// Form
if (!empty($row['form'])) {
    //
}

if ($row['attachments']) {
    echo News::format_attached($id);
}

?>
</td>
<td>
<?php

$versions = $news->getVerisons($id);
if ($versions instanceof PEAR_Error) {
    echo $versions->getMessage();
    echo $versions->getDebugInfo();
}

unset($versions[0]); // current version

if (sizeof($versions)>0) {
    echo _("Edit history: ") . '<br />';

    foreach ($versions as $version) {

        switch ($version['action']) {
        case 'update':
            echo _("Update");
            break;
        default:
            echo _("Insert");
        break;
        }
        echo _(" by ") . $version['user_uid'] . _(" at ") . $version['created'] . "\n(";

        $url = Horde_Util::addParameter(Horde::url('news.php'), array('id' => $id, 'version' => $version['version']));
        echo Horde::link($url, _("View"), '', '_blank', '', _("View")) . _("View") . '</a> | ';

        $url = Horde_Util::addParameter(Horde::url('edit.php'), array('id' => $id, 'actionID' => 'renew'));
        echo Horde::link(Horde_Util::addParameter($url,'version', $version['version']),_("Renew")) . _("Renew") . '</a> | ';

        $url = Horde_Util::addParameter(Horde::url('diff.php'), array('id' => $id, 'version' => $version['version']));
        echo Horde::link('#', _("Diff"), '', '', Horde::popupJs($url, array('urlencode' => true)) . 'return false;') . _("Diff") . '</a> ';

        echo ')<br />' . "\n";
    }
}

?>
</td>
</tr>
</table>

</td>

