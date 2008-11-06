<?php
if ($registry->hasMethod('contacts/sources')) {
    require_once IMP_BASE . '/lib/Compose.php';
    $search = IMP_Compose::getAddressSearchParams();
    $readable = $registry->call('contacts/sources');
    $writeable = $registry->call('contacts/sources', true);

    $nbReadSources = count(array_keys($readable));
    $nbWriteSources = count(array_keys($writeable));

    if ($nbReadSources == 1) {
        // Only one source, no need to display the selection widget
        $search['sources'] = array_keys($readable);
        $readSelect = '';
    }

    $prefSelect = '';
    foreach ($search['sources'] as $source) {
        if (!empty($readable[$source])) {
            $prefSelect .= '<option value="' . $source . '">' . $readable[$source] . "</option>\n";
        }
    }

    $readSelect = '';
    if (!is_a($readable, 'PEAR_Error') && is_array($readable)) {
        foreach (array_diff(array_keys($readable), $search['sources']) as $source) {
            $readSelect .= '<option value="' . $source . '">' . $readable[$source] . "</option>\n";
        }
    }

    if (!is_a($writeable, 'PEAR_Error') && is_array($writeable)) {
        $writeSelect = '<option value="">' . _("None") . '</option>' . "\n";
        $writeSource = '';
        foreach ($writeable as $source => $name) {
            $sel = $prefs->getValue('add_source') == $source ? ' selected="selected"' : '';
            $writeSelect .= '<option value="' . $source . '"' . "$sel>" . $name . "</option>\n";
            $writeSource = $source;
        }
    }

    $search_fields = array();
    if (!is_a($readable, 'PEAR_Error') && is_array($readable)) {
        foreach (array_keys($readable) as $source) {
            $search_fields[$source] = $registry->call('contacts/fields', $source);
        }
    }

    $js = "var searchFields = [];\n";
    $source_count = 0;
    foreach ($search_fields as $source => $fields) {
        $js .= "searchFields[$source_count] = [];\n";
        $js .= "searchFields[$source_count][0] = '$source';\n";

        $field_count = 1;
        foreach ($fields as $field) {
            if ($field['search']) {
                $marked = isset($search['fields'][$source]) && in_array($field['name'], $search['fields'][$source]) ? 'true' : 'false';
                $js .= "searchFields[$source_count][$field_count] = ['" . $field['name'] . "', '" . $field['label'] . "', $marked];\n";
                $field_count++;
            }
        }

        $source_count++;
    }
}
?>

<?php if (!$prefs->isLocked('search_sources') && (!empty($readSelect) || !empty($prefSelect))): ?>
<script type="text/javascript">

function deselectHeaders()
{
    var p = document.prefs;
    p.unselected_search_sources[0].selected = p.selected_search_sources[0].selected = false;
}

function resetHidden()
{
    var i, l,
        p = document.prefs,
        tmp = '';
    for (i = 1, l = p.selected_search_sources.length; i < l; ++i) {
        tmp += p.selected_search_sources[i].value;
        if (i < l - 1) {
            tmp += "\t";
        }
    }

    document.prefs.search_sources.value = tmp;
}

function addSource()
{
    var i, l,
        p = document.prefs,
        uss = p.unselected_search_sources,
        sss = p.selected_search_sources;
    for (i = 1, l = uss.length; i < l; ++i) {
        if (uss[i].selected) {
            sss[sss.length] = new Option(uss[i].text, uss[i].value);
            uss[i] = null;
            --i;
        }
    }

    resetHidden();
}

function removeSource()
{
    var i, l,
        p = document.prefs,
        sss = p.selected_search_sources,
        uss = p.unselected_search_sources;
    for (i = 1, l = sss.length; i < l; ++i) {
        if (sss[i].selected) {
            uss[uss.length] = new Option(sss[i].text, sss[i].value)
            sss[i] = null;
            --i;
        }
    }

    resetHidden();
}

function moveSourceUp()
{
    var i, l, tmp,
        sss = document.prefs.selected_search_sources,
        sel = sss.selectedIndex;

    if (sel == -1 || sss.length <= 2) {
        return;
    }

    // deselect everything but the first selected item
    sss.selectedIndex = sel;

    if (sel == 1) {
        tmp = sss[sel];
        sss[sel] = null;
        sss[sss.length] = tmp;
        sss.selectedIndex = sss.length - 1;
    } else {
        tmp = [];
        for (i = 1, l = sss.length; i < l; ++i) {
            tmp[i - 1] = new Option(sss[i].text, sss[i].value);
        }

        for (i = 0, l = tmp.length; i < l; ++i) {
            if (i + 1 == sel - 1) {
                sss[i + 1] = tmp[i + 1];
            } else if (i + 1 == sel) {
                sss[i + 1] = tmp[i - 1];
            } else {
                sss[i + 1] = tmp[i];
            }
        }

        sss.selectedIndex = sel - 1;
    }

    resetHidden();
}

function moveSourceDown()
{
    var i,
        sss = document.prefs.selected_search_sources,
        sel = sss.selectedIndex,
        l = sss.length,
        tmp = [];

    if (sel == -1 || l <= 2) {
        return;
    }

    // deselect everything but the first selected item
    sss.selectedIndex = sel;

    if (sel == l - 1) {
        for (i = 1; i < l; ++i) {
            tmp[i - 1] = new Option(sss[i].text, sss[i].value);
        }

        sss[1] = tmp[tmp.length - 1];
        for (i = 0, l = tmp.length - 1; i < l; ++i) {
            sss[i + 2] = tmp[i];
        }

        sss.selectedIndex = 1;
    } else {
        for (i = 1; i < l; ++i) {
            tmp[i - 1] = new Option(sss[i].text, sss[i].value)
        }

        for (i = 0, l = tmp.length; i < l; ++i) {
            if (i + 1 == sel) {
                sss[i + 1] = tmp[i + 1];
            } else if (i + 1 == sel + 1) {
                sss[i + 1] = tmp[i - 1];
            } else {
                sss[i + 1] = tmp[i];
            }
        }

        sss.selectedIndex = sel + 1;
    }

    resetHidden();
}

<?php echo $js ?>

var selectedIndex = false,
    selectedValue = false,
    nbSources = <?php echo $nbReadSources ?>;
<?php if ($nbReadSources == 1): ?>
selectedIndex = 1;
selectedValue = "<?php echo $search['sources'][0] ?>";
<?php endif; ?>

function updateSearchFields()
{
    var i, j, l, l2,
        f = document.prefs,
        sf = f.search_fields,
        fieldString = '';

    <?php if ($nbReadSources > 1): ?>
    var sss = f.selected_search_sources;
    selectedIndex = sss.selectedIndex;
    <?php endif; ?>

    while (sf.length > 0) {
        sf.options[sf.length - 1] = null;
    }

    if (selectedIndex < 1) {
        return;
    }

    <?php if ($nbReadSources > 1): ?>
    selectedValue = sss.options[selectedIndex].value;
    <?php endif; ?>

    for (i = 0, l = searchFields.length; i < l; ++i) {
        if (i > 0) {
            fieldString += "\n";
        }
        fieldString += searchFields[i][0];
        for (j = 1, l2 = searchFields[i].length; j < l2; ++j) {
            if (searchFields[i][j][2]) {
                fieldString += "\t" + searchFields[i][j][0];
            }

            if (searchFields[i][0] == selectedValue) {
                sf.options[sf.length] = new Option(searchFields[i][j][1], searchFields[i][j][0]);
                if (searchFields[i][j][2]) {
                    sf.options[sf.length - 1].selected = true;
                }
            }
        }
    }

    f.search_fields_string.value = fieldString;
}

function changeSearchFields()
{
    var i, j, l, l2,
        f = document.prefs;
    <?php if ($nbReadSources > 1): ?>
    var sss = f.selected_search_sources;
    selectedIndex = sss.selectedIndex;
    selectedValue = sss.options[selectedIndex].value;
    <?php endif; ?>

    for (i = 0, l = searchFields.length; i < l; ++i) {
        if (searchFields[i][0] == selectedValue) {
            for (j = 1, l2 = searchFields[i].length; j < l2; ++j) {
                searchFields[i][j][2] = f.search_fields.options[j - 1].selected;
            }
            updateSearchFields();
            return;
        }
    }
}

</script>

<br />
<input type="hidden" name="search_sources" value="<?php echo implode("\t", $search['sources']) ?>" />
<?php if ($nbReadSources > 1): ?>
<?php echo _("Choose the order of address books to search when expanding addresses.") ?><br />
<table>
 <tr>
  <td>
   <label for="unselected_search_sources" class="hidden"><?php echo _("Available Address books:") ?></label>
   <select id="unselected_search_sources" name="unselected_search_sources" multiple="multiple" size="5" style="width:20em" onchange="deselectHeaders()">
    <option value=""><?php echo _("Available Address books:") ?></option>
    <?php echo $readSelect ?>
   </select>
  </td>
  <td>
   <a href="#" onclick="addSource(); return false;"><?php echo Horde::img(isset($GLOBALS['nls']['rtl'][$GLOBALS['language']]) ? 'lhand.png' : 'rhand.png', _("Add source"), null, $registry->getImageDir('horde')) ?></a>
   <br />
   <a href="#" onclick="removeSource(); return false;"><?php echo Horde::img(isset($GLOBALS['nls']['rtl'][$GLOBALS['language']]) ? 'rhand.png' : 'lhand.png', _("Remove source"), null, $registry->getImageDir('horde')) ?></a>
  </td>
  <td>
   <label for="selected_search_sources" class="hidden"><?php echo _("Selected Address books:") ?></label>
   <select name="selected_search_sources" multiple="multiple" size="5" style="width:20em" onchange="deselectHeaders();updateSearchFields();">
    <option value=""><?php echo _("Selected Address books:") ?></option>
    <?php echo $prefSelect ?>
   </select>
  </td>
  <td>
   <a href="#" onclick="moveSourceUp(); return false;"><?php echo Horde::img('nav/up.png', _("Move up"), null, $registry->getImageDir('horde')) ?></a>
   <br />
   <a href="#" onclick="moveSourceDown(); return false;"><?php echo Horde::img('nav/down.png', _("Move down"), null, $registry->getImageDir('horde')) ?></a>
  </td>
 </tr>
</table>

<?php echo _("Click on one of your selected address books and then select all fields to search.") ?><br />
<?php else: ?>
<?php echo _("Select all fields to search when expanding addresses.") ?><br />
<?php endif; ?>

<?php echo _("To select multiple fields, hold down the Control (PC) or Command (Mac) while clicking.") ?><br />
<input type="hidden" name="search_fields_string" />
<table>
 <tr>
  <td>
   <label for="search_fields" class="hidden"><?php echo _("Fields to search") ?></label>
   <select id="search_fields" name="search_fields" multiple="multiple" size="5" style="width:20em" onchange="changeSearchFields()">
    <option><?php echo str_repeat('&nbsp;', 50) ?></option>
   </select>
  </td>
 </tr>
</table>

<script type="text/javascript">
updateSearchFields();
</script>
<?php endif; ?>

<?php if (!$prefs->isLocked('add_source') && !empty($writeSelect)): ?>
 <?php echo _("Choose the address book to use when adding addresses.") ?><br />
 <br />
 <label for="add_source" class="hidden"><?php echo _("Address book to add addresses to") ?></label>
 <select id="add_source" name="add_source"><?php echo $writeSelect ?></select>
<?php endif; ?>
