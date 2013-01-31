function chooseObject(oid)
{
    if (!window.opener || !window.opener.obrowserCallback) {
        return false;
    }

    var result = window.opener.obrowserCallback(window.name, oid);
    if (!result) {
        window.close();
        return;
    }

    alert(result);
    return false;
}
