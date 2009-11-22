// Menu Domain submit
var loading;
function domainSubmit(clear)
{
    if (document.menu.domainSelector[document.menu.domainSelector.selectedIndex].value != '') {
         if ((loading == null) || (clear != null)) {
            loading = true;
            document.menu.submit();
         }
    }
}

