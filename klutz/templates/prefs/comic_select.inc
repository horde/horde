<?php

global $klutz;

$selected = explode("\t", $prefs->getValue('viewcomics'));
$comics = $klutz->getProperty($klutz->listEnabled(), 'name');

if (count($selected) == 1 && empty($selected[0])) {
    $selected = array();
}

$comicSelect = '';
foreach ($comics as $index => $name) {
    $comicSelect .= '<option value="' . $index . '"';
    if (in_array($index, $selected)) {
        $comicSelect .= ' selected="selected"';
    }
    $comicSelect .= '>' . $name . '</option>';
}
$comicSelect = trim($comicSelect);

?>

<?php if (!$prefs->isLocked('viewcomics') && (!empty($comicSelect))): ?>
<script type="text/javascript">
<!--
function resetHidden()
{
    var tmp = '';
    for (i = 0; i < document.prefs.comics_list.length; i++) {
        if (document.prefs.comics_list[i].selected == true) {
            if (tmp != '') tmp += "\t";
            tmp += document.prefs.comics_list[i].value;
        }
    }

    document.prefs.viewcomics.value = tmp;
}

function selectAll(value)
{
    for (i = 0; i < document.prefs.comics_list.length; i++) {
        document.prefs.comics_list[i].selected = value;
    }
    resetHidden();
}

// -->
</script>

<?php echo _("Choose the comics you wish to view.") ?><br />
<?php echo _("To select multiple comics, hold down the Control (PC) or Command (Mac) while clicking.") ?><br />
<input type="hidden" name="viewcomics" value="<?php echo implode("\t", $selected) ?>" />
<table>
    <tr>
        <td>
            <select name="comics_list" multiple="multiple" size="10" width="20" onchange="resetHidden();">
                <?php echo $comicSelect ?>
            </select>
        </td>
    </tr>
    <tr>
        <td>
            <input type="button" class="button" name="clear" value="<?php echo _("Select None")?>" onclick="selectAll(false);" />
            <input type="button" class="button" name="selectall" value="<?php echo _("Select All")?>" onclick="selectAll(true);" />
        </td>
    </tr>
</table>
<?php endif; ?>
