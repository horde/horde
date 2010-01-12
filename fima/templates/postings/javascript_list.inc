<script type="text/javascript">
<!--

function AnySelected()
{
    for (i = 0; i < document.postings.elements.length; i++) {
        if (document.postings.elements[i].checked) return true;
    }
    return false;
}

var rowColors = new Array();
function selectRow(rowId)
{
    rowOb = document.getElementById('row' + rowId);
    if (!rowOb) {
        return;
    }

    if (rowOb.className.indexOf('selectedRow') != -1) {
        rowOb.className = rowColors[rowId];
    } else {
        rowColors[rowId] = rowOb.className;
        rowOb.className = 'selectedRow';
    }
}

function getIndexByElement(elem)
{
    for (var i = 0; i < elem.form.elements.length; i++) {
        if (elem == elem.form.elements[i]) return i;
    }
    return null;
}

function Submit(action)
{
    if (!AnySelected() && action != 'transfer_postings') {
        window.alert('<?php echo addslashes(_("You must select at least one posting first.")) ?>');
        return;
    }

<?php if ($prefs->getValue('delete_opt') == 1): ?>
    if (action == 'delete_postings') {
        if (!window.confirm('<?php echo addslashes(_("Are you sure you wish to PERMANENTLY delete these postings?")) ?>')) {
            return;
        }
    }
<?php endif; ?>
    document.postings.actionID.value = action;
    document.postings.submit();
}

function makeSelection(whichForm)
{
    switch (parseInt(whichForm)) {
    case -1:
        if (document.postings.checkAll.checked) {
            flag = "!0";
        } else {
            flag = "0";
        }
        break;
    case 1:
        flag = document.select1.filter.options[document.select1.filter.selectedIndex].value;
        break;
    default:
        flag = document.select2.filter.options[document.select2.filter.selectedIndex].value;
    }

    if (flag.substring(0, 1) == "!") {
        selectFlaged(flag.substring(1), false);
    } else if (flag.substring(0, 1) == "~") {
        selectFlaged(flag.substring(0,1), null);
    } else {
        selectFlaged(flag, true);
    }

    // Reset the form.
    switch (parseInt(whichForm)) {
    case -1:
        break;

    case 1:
        document.select1.reset();
        break;

    default:
        document.select2.reset();
    }
}

var startrange = -1;

function selectRange(e)
{
    var checkBox = (e.srcElement || e.target);
    var endrange = getIndexByElement(checkBox);

    if (startrange >= 0 && e.shiftKey) {
        if (document.postings.elements[startrange].checked == document.postings.elements[endrange].checked) {
            if (startrange > endrange) {
                var tmp = endrange;
                endrange = startrange;
                startrange = tmp;
            }
            for (var i = startrange + 1; i < endrange; i++) {
                document.postings.elements[i].checked = document.postings.elements[startrange].checked;
                selectRow(document.postings.elements[i].id.replace(/check/, ''));
            }
        }
        startrange = -1;
    } else {
        startrange = getIndexByElement(checkBox);
    }
}

function onClickHandler(e)
{
    var e = e || window.event;
    var elem = (e.srcElement || e.target);

    // Range selection/deselection.
    if (elem.name == "indices[]") {
        selectRange(e);
    }
}

document.onclick = onClickHandler;

var Flags;

function selectFlaged(flag, val)
{
    shift = 0;
    for (var i = 0; i < document.postings.elements.length; i++) {
        while (document.postings.elements[i].name != "indices[]") {
            i++;
            shift++;
            if (!document.postings.elements[i]) {
                return;
            }
        }
        
        if (document.postings.elements[i].disabled) {
            continue;
        }

        if (flag == '~') {
            check = !document.postings.elements[i].checked;
        } else if (flag & Flags[i - shift]) {
            check = val;
        } else {
            check = !val;
        }

        if (document.postings.elements[i].checked != check) {
            document.postings.elements[i].checked = check;
            selectRow(document.postings.elements[i].id.replace(/check/, ''));
        }
    }
}

//-->
</script>
