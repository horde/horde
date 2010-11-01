/**
 * browse.js
 */

var TurbaBrowse = {
    // Defined externally: contact1, contact2, contact3, copy1, copy2,
    //                     submit1, submit2, delete1
};

function AnySelected()
{
    for (i = 0; i < document.contacts.elements.length; i++) {
        if (document.contacts.elements[i].checked) {
            return true;
        }
    }
    return false;
}

function Add(select)
{
    if (!AnySelected()) {
        window.alert(TurbaBrowse.contact1);
        return false;
    }

    key = select[select.selectedIndex].value;
    if (key == '') {
        window.alert(TurbaBrowse.contact2);
        return false;
    }

    if (key.indexOf(':') == -1 || key.lastIndexOf(':') == key.length - 1) {
        var newList = window.prompt(TurbaBrowse.contact3, '');
        if (newList != null && newList != '') {
            if (key.lastIndexOf(':') == key.length - 1) {
                key = key.substr(0, key.length - 1);
            }
            document.contacts.targetAddressbook.value = key;
            document.contacts.targetNew.value = 1;
            document.contacts.targetList.value = newList;
        } else {
            return false;
        }
    } else {
        document.contacts.targetList.value = key;
    }

    Submit('add');
}

function CopyMove(action, select)
{
    if (!AnySelected()) {
        window.alert(TurbaBrowse.contact1);
        return false;
    }

    key = select[select.selectedIndex].value;
    if (key == '') {
        window.alert(TurbaBrowse.copymove);
        return false;
    }

    document.contacts.targetAddressbook.value = key;
    Submit(action);
}

function Submit(action)
{
    if (AnySelected()) {
        if (action != 'delete' || window.confirm(TurbaBrowse.submit)) {
            document.contacts.actionID.value = action;
            document.contacts.submit();
        }
    } else {
        window.alert(TurbaBrowse.contact1);
        return false;
    }
}

function SelectAll()
{
    for (var i = 0; i < document.contacts.elements.length; i++) {
        document.contacts.elements[i].checked = document.contacts.checkAll.checked;
    }
}

function confirmDelete(name)
{
    return window.confirm(TurbaBrowse.confirmdelete.replace('%s', name));
}
