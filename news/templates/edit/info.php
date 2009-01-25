<tr valign="top">
<td colspan="7">

<table style="width: 100%;">
<tr valign="top">
<td>
<img src="<?php echo News::getImageUrl($id, 'small'); ?>" style="height: 50px; width: 50px;" />
</td>
<td>
<?php

echo _("Primary category") . ': ' . $allowed_cats[$row['category1']] . "<br />\n";

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
    echo $news->format_attached($id);
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

        $url = Util::addParameter(Horde::applicationUrl('news.php'), array('id' => $id, 'version' => $version['version']));
        echo Horde::link($url, _("View"), '', '_blank', '', _("View")) . _("View") . '</a> | ';

        $url = Util::addParameter(Horde::applicationUrl('edit.php'), array('id' => $id, 'actionID' => 'renew'));
        echo Horde::link(Util::addParameter($url,'version', $version['version']),_("Renew")) . _("Renew") . '</a> | ';

        $url = Util::addParameter(Horde::applicationUrl('diff.php'), array('id' => $id, 'version' => $version['version']));
        echo Horde::link('#', _("Diff"), '', '', "popup('$url')") . _("Diff") . '</a> ';

        echo ')<br />' . "\n";
    }
}

?>
</td>
</tr>
</table>

</td>

