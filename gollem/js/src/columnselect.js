/**
 * Provides the javascript for the gollem column select preferences page.
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

var columns = [];

function selectSource()
{
    var f = document.prefs;
    var fieldString = '';

    while (f.unselected_columns.length > 1) {
        f.unselected_columns.options[f.unselected_columns.length - 1] = null;
    }
    while (f.selected_columns.length > 1) {
        f.selected_columns.options[f.selected_columns.length - 1] = null;
    }

    if (f.source.selectedIndex < 1) {
        return;
    }
    var source = f.source.selectedIndex - 1;

    var selected = [];
    var unselected = [];
    for (var i = 1; i < columns[source].length; i++) {
        if (columns[source][i][2]) {
            selected[columns[source][i][3]] = [columns[source][i][1], columns[source][i][0]];
        } else {
            unselected[unselected.length] = [columns[source][i][1], columns[source][i][0]];
        }
    }
    for (i = 0; i < selected.length; i++) {
        f.selected_columns.options[i + 1] = new Option(selected[i][0], selected[i][1]);
    }
    for (i = 0; i < unselected.length; i++) {
        f.unselected_columns.options[i + 1] = new Option(unselected[i][0], unselected[i][1]);
    }
}

function deselectHeaders()
{
    document.prefs.unselected_columns[0].selected = false;
    document.prefs.selected_columns[0].selected = false;
}

function resetHidden()
{
    var tmp = '';
    for (var i = 0; i < columns.length; i++) {
        if (i > 0) {
            tmp += '\n';
        }
        tmp += columns[i][0];
        for (var j = 1; j < columns[i].length; j++) {
            if (columns[i][j][2]) {
                tmp += '\t' + columns[i][j][0];
            }
        }
    }
    document.prefs.columns.value = tmp;
}

function addColumn()
{
    var f = document.prefs;
    var source = f.source.selectedIndex - 1;

    for (i = 1; i < f.unselected_columns.length; i++) {
        if (f.unselected_columns[i].selected) {
            for (var j = 1; j < columns[source].length; j++) {
                if (columns[source][j][0] == f.unselected_columns[i].value) {
                    columns[source][j][2] = true;
                }
            }
            f.selected_columns[f.selected_columns.length] = new Option(f.unselected_columns[i].text, f.unselected_columns[i].value);
            f.unselected_columns[i] = null;
            i--;
        }
    }

    resetHidden();
}

function removeColumn()
{
    var f = document.prefs;
    var source = f.source.selectedIndex - 1;

    for (i = 1; i < f.selected_columns.length; i++) {
        if (f.selected_columns[i].selected) {
            for (var j = 1; j < columns[source].length; j++) {
                if (columns[source][j][0] == f.selected_columns[i].value) {
                    columns[source][j][2] = false;
                }
            }
            f.unselected_columns[f.unselected_columns.length] = new Option(f.selected_columns[i].text, f.selected_columns[i].value)
            f.selected_columns[i] = null;
            i--;
        }
    }

    resetHidden();
}

function moveColumnUp()
{
    var f = document.prefs;
    var sel = f.selected_columns.selectedIndex;
    var source = f.source.selectedIndex - 1;

    if (sel <= 1 || f.selected_columns.length <= 2) return;

    // deselect everything but the first selected item
    f.selected_columns.selectedIndex = sel;
    var up = f.selected_columns[sel].value;

    tmp = [];
    for (i = 1; i < f.selected_columns.length; i++) {
        tmp[i - 1] = new Option(f.selected_columns[i].text, f.selected_columns[i].value)
    }

    for (i = 0; i < tmp.length; i++) {
        if (i + 1 == sel - 1) {
            f.selected_columns[i + 1] = tmp[i + 1];
        } else if (i + 1 == sel) {
            f.selected_columns[i + 1] = tmp[i - 1];
        } else {
            f.selected_columns[i + 1] = tmp[i];
        }
    }

    f.selected_columns.selectedIndex = sel - 1;

    for (i = 2; i < columns[source].length - 1; i++) {
        if (columns[source][i][0] == up) {
            column = columns[source][i];
            columns[source][i] = columns[source][i - 1];
            columns[source][i - 1] = column;
        }
    }

    resetHidden();
}

function moveColumnDown()
{
    var f = document.prefs;
    var sel = f.selected_columns.selectedIndex;
    var source = f.source.selectedIndex - 1;

    if (sel == -1 || f.selected_columns.length <= 2 || sel == f.selected_columns.length - 1) return;

    // deselect everything but the first selected item
    f.selected_columns.selectedIndex = sel;
    var down = f.selected_columns[sel].value;

    tmp = [];
    for (i = 1; i < f.selected_columns.length; i++) {
        tmp[i - 1] = new Option(f.selected_columns[i].text, f.selected_columns[i].value)
    }

    for (i = 0; i < tmp.length; i++) {
        if (i + 1 == sel) {
            f.selected_columns[i + 1] = tmp[i + 1];
        } else if (i + 1 == sel + 1) {
            f.selected_columns[i + 1] = tmp[i - 1];
        } else {
            f.selected_columns[i + 1] = tmp[i];
        }
    }

    f.selected_columns.selectedIndex = sel + 1;

    for (i = columns[source].length - 2; i > 0; i--) {
        if (columns[source][i][0] == down || columns[source][i + 1][0] == down) {
            column = columns[source][i];
            columns[source][i] = columns[source][i + 1];
            columns[source][i + 1] = column;
        }
    }

    resetHidden();
}
