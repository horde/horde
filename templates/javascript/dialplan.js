/**
 * Shout Dialplan Javascript Class
 *
 * Provides the javascript class to create dynamic dialplans
 *
 * Copyright 2005 Ben Klang <ben@alkaloid.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you did not
 * receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * $Horde: shout/templates/javascript/dialplan.js,v 1.0.0.1 2005/11/10 06:23:22 ben Exp $
 *
 * @author  Ben Klang <ben@alkaloid.net>
 * @package Shout
 * @since   Shout 0.1
 */
function Dialplan(instanceName)
{
    this._instanceName = instanceName;
    this.dp = new Array();
    this.dp = eval('shout_dialplan_entry_'+instanceName);
    this.applist = eval('shout_dialplan_applist_'+instanceName);
    this.object = 'shout_dialplan_object_'+instanceName;
    this.curExten = '';
    this.curPrio = '';
}

Dialplan.prototype.highlightExten = function(exten)
{
    if (this.curExten && this.curExten != exten) {
        this.deactivatePriority();

        document.getElementById('eBox-' + this.curExten).className = 'extensionBox';
        document.getElementById('pList-' + this.curExten).className = 'pList';
    }

    this.curExten = exten;
    document.getElementById("eBox-" + exten).className = 'extensionBoxHighlight';
    document.getElementById("pList-" + exten).className = 'pListHighlight';
}


Dialplan.prototype.activatePriority = function(exten, prio)
{
    prio = Number(prio);
    this.curPrio = Number(this.curPrio);
    if (prio != this.curPrio || exten != this.curExten) {
        if (this.curExten) {
            this.deactivatePriority();
        }

        if (exten != this.curExten) {
            this.highlightExten(exten);
        }

        this.curPrio = prio;
        document.getElementById('priority-'+exten+'-'+prio).className = 'priorityHighlight';
        document.getElementById('pButtons-'+exten+'-'+prio).style['visibility'] = 'visible';
    //     document.getElementById('pButtons-'+exten+'-'+prio).className = 'pButtonsHighlight';
    //     document.getElementById('pNumber-'+exten+'-'+prio).className = 'pElementHighlight';
    //     document.getElementById('pApp-'+exten+'-'+prio).className = 'pElementHighlight';
    //     document.getElementById('pArgs-'+exten+'-'+prio).className = 'pElementHighlight';
    } else {
        var form = '';
        //form += '<form id="renumber">';
        form += '    <span class="priorityBox">';
        form += '        <input type="text" size="3" maxlength="3" name="newprio" value="'+this.curPrio+'" />';
        form += '    </span>';
        //form += '</form>';
        document.getElementById('pBox-'+this.curExten+'-'+this.curPrio).innerHTML = form;
    }
}

Dialplan.prototype.deactivatePriority = function()
{
    if (this.curPrio && document.getElementById('pButtons-'+this.curExten+'-'+this.curPrio)) {
        document.getElementById('priority-'+this.curExten+'-'+this.curPrio).className = 'priority';
        document.getElementById('pButtons-'+this.curExten+'-'+this.curPrio).style['visibility'] = 'hidden';
//         document.getElementById('pButtons-'+this.curExten+'-'+this.curPrio).className = 'pButtons';
//         document.getElementById('pNumber-'+this.curExten+'-'+this.curPrio).className = 'pElement';
//         document.getElementById('pApp-'+this.curExten+'-'+this.curPrio).className = 'pElement';
//         document.getElementById('pArgs-'+this.curExten+'-'+this.curPrio).className = 'pElement';
    }
}

Dialplan.prototype.drawPrioTable = function (exten)
{
    var table = '';
    if (!exten) {
        alert('Must first choose an extension to draw');
        return false;
    }
    //alert(document.getElementById('pList-'+exten).innerHTML);
    for (var p in this.dp[exten]['priorities']) {
        table += '<div class="priority" id="priority-'+exten+'-'+p+'">\n';
        table += '        <span class="pButtons" id="pButtons-'+exten+'-'+p+'"\n';
        table += '            name="pButtons-'+exten+'-'+p+'">\n';
        table += '            <span class="add" onclick="javascript:'+this.object+'.addPrio(\''+exten+'\', \''+p+'\');">+</span>\n';
        table += '            <span class="remove" onclick="javascript:'+this.object+'.delPrio(\''+exten+'\', \''+p+'\');">-</span>\n';
        table += '        </span>\n';
        table += '        <span class="pElement" id="pBox-'+exten+'-'+p+'"\n';
        table += '            name="pBox-'+exten+'-'+p+'"\n';
        table += '            onclick="javascript:'+this.object+'.activatePriority(\''+exten+'\', \''+p+'\')">\n';
        table += '            <span class="priorityBox">'+p+'</span>\n';
        table += '        </span>\n';
        table += '        <span class="pElement" id="pApp-'+exten+'-'+p+'"\n';
        table += '            name="pApp-'+exten+'-'+p+'">\n';
        table += '            <span class="applicationBox"></span>\n';
        table += '                <select name="app['+exten+']['+p+']">\n';;
        table += this.genAppList(this.dp[exten]['priorities'][p]['application']);
        table += '                </select>\n';
        table += '            </span>\n';
        table += '        </span>\n';
        table += '        <span class="pElement" id="pArgs-'+exten+'-'+p+'"\n';
        table += '            name="pArgs-'+exten+'-'+p+'">\n';
        table += '            <span class="argBox" id="args-'+exten+'-'+p+'"\n';
        table += '                name="args-'+exten+'-'+p+'">ARGS</span>\n';
        table += '        </span>\n';
        table += '</div>\n';
    }
    //alert(table);
    document.getElementById('pList-'+exten).innerHTML = table;
}

Dialplan.prototype.genAppList = function (app)
{
    applist = '<option value="APPLICATION">APPLICATION</option>\n';
    return applist;
}

Dialplan.prototype.addExten = function (exten, extenName)
{
    this.dp[exten] = new Array();
}

Dialplan.prototype.addPrio = function(exten, prio)
{
    prio = Number(prio);
    if (this.dp[exten]['priorities'][prio] != 'undefined') {
        this._incrPrio(exten, prio);
    }
    this.dp[exten]['priorities'][prio] = new Array();
    this.curPrio = 0;
    this.drawPrioTable(exten);
}

Dialplan.prototype._numCompare = function(a, b)
{
    return (a - b);
}

Dialplan.prototype._incrPrio = function (exten, prio)
{
    // Due to javascript's inability to remove an array element while maintaining
    // associations, we copy the elements into a tmp array and ultimately replace
    // the object's copy.  We will also have to sort the resulting array manually
    // so it renders correctly.
    var tmp = new Array();
    var plist = new Array();
    var i = 0;
    var p;

    for (p in this.dp[exten]['priorities']) {
        p = Number(p);
        prio = Number(prio);
        // Make a notch for the new priority by incrementing all priorities greater
        // than the requested one.  Try to exclude error handling priorities
        // which are unrelated to the changed extension.  See README for
        // more information.
        if (p > prio && (p < 101 || p > prio + 100)) {
            tmp[p + 1] = this.dp[exten]['priorities'][p];
            plist[i] = p + 1;
        } else {
            tmp[p] = this.dp[exten]['priorities'][p];
            plist[i] = p;
        }
        i++;
    }
    // Seed the new priority
    prio = Number(prio) + 1;
    tmp[prio] = new Array();
    tmp[prio]['application'] = '';
    tmp[prio]['args'] = '';
    plist[i] = prio;

    // Empty the original array
    this.dp[exten]['priorities'] = new Array();

    // Sort the priorities and put them back into the original array
    plist.sort(this._numCompare);
    for (i = 0; i < plist.length; i++) {
        p = Number(plist[i]);
        this.dp[exten]['priorities'][p] = tmp[p];
    }
    return true;
}