// $Horde: beatnik/js/src/beatnik.js,v 1.3 2008/08/20 08:56:53 duck Exp $

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

