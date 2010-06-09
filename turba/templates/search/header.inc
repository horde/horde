<?php
/* Build the directory sources select widget. */
$source_options = '';
$criteria_options = '';
$js_criteria = "var criteriaOptions = [];\n"
    . "var shareSources = [];\n";

$source_count = 0;
$criteria = $shareSources = array();
foreach (Turba::getAddressBooks() as $key => $entry) {
    $criteria[$key] = array();

    /* Build the criteria select widget. */
    $field_count = 1;
    foreach ($entry['search'] as $field) {
        if ($key == $source) {
            $selected = ($criteria == $field) ? ' selected="selected"' : '';
            $criteria_options .= "<option value=\"$field\"$selected>" .
                htmlspecialchars($GLOBALS['attributes'][$field]['label']) . "</option>\n";
        }
        $field_count++;
        $criteria[$key][$field] = $GLOBALS['attributes'][$field]['label'];
    }

    $selected = ($key == $source) ? ' selected="selected"' : '';
    $source_options .= "<option value=\"$key\"$selected>"
        . htmlspecialchars($entry['title']) . "</option>\n";

    $unique_source = $key;
    $source_count++;

    /* Remember vbooks and sources that are using shares. */
    $shareSources[$key] = $entry['type'] != 'vbook';
}

/* Build search mode tabs. */
$sUrl = Horde::applicationUrl('search.php');
$vars = Horde_Variables::getDefaultVariables();
$tabs = new Horde_Ui_Tabs('search_mode', $vars);
$tabs->addTab(_("Basic Search"), $sUrl, 'basic');
$tabs->addTab(_("Advanced Search"), $sUrl, 'advanced');
echo $tabs->render($_SESSION['turba']['search_mode']);

?>
<div class="text" style="padding:1em">
<form name="directory_search" action="search.php" method="get" onsubmit="RedBox.loading(); return true;">
<?php echo Horde_Util::formInput() ?>
<?php if ($source_count == 1): ?>
 <input type="hidden" id="turbaSearchSource" name="source" value="<?php echo $unique_source ?>" />
<?php endif; ?>
