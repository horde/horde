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

    } else {
        var form = '';
        form += '    <span class="priorityBox">';
        form += '        <input id="p" type="text" size="3" maxlength="3" name="newprio"';
        form +=              ' value="'+this.curPrio+'" />';
        form += '    </span>';
        document.getElementById('pBox-'+this.curExten+'-'+this.curPrio).innerHTML = form;
        document.getElementById('pBox-'+this.curExten+'-'+this.curPrio).p.focus;
        document.getElementById('pBox-'+this.curExten+'-'+this.curPrio).p.select;
    }
}

Dialplan.prototype.deactivatePriority = function()
{
    if (this.curPrio && document.getElementById('pButtons-'+this.curExten+'-'+this.curPrio)) {
        document.getElementById('priority-'+this.curExten+'-'+this.curPrio).className = 'priority';
        document.getElementById('pButtons-'+this.curExten+'-'+this.curPrio).style['visibility'] = 'hidden';
    }
    this.curPrio = 0;
}

Dialplan.prototype.drawPrioTable = function (exten)
{
    var table = '';
    if (!exten) {
        alert('Must first choose an extension to draw');
        return false;
    }
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
        table += '                <select name="app['+exten+']['+p+']"';
        table +=                    'onclick="javascript:'+this.object+'.activatePriority(\''+exten+'\', ';
        table +=                    '\''+p+'\')">\n';
        table += this.genAppList(this.dp[exten]['priorities'][p]['application']);
        table += '                </select>\n';
        table += '            </span>\n';
        table += '        </span>\n';
        table += '        <span class="pElement" id="pArgs-'+exten+'-'+p+'"\n';
        table += '            name="pArgs-'+exten+'-'+p+'">\n';
        table += '            <span class="argBox" id="args-'+exten+'-'+p+'"\n';
        table += '                name="args-'+exten+'-'+p+'">';
        table +=                      this.dp[exten]['priorities'][p]['args']+'</span>\n';
        table += '        </span>\n';
        table += '</div>\n';
    }
    document.getElementById('pList-'+exten).innerHTML = table;
}

Dialplan.prototype.genAppList = function (app)
{
    var appstring = '';
    for (var a in this.applist) {
        appstring += '<option value="'+this.applist[a]+'"';
        if (this.applist[a] == app) {
            appstring += ' selected';
        }
        appstring += '>'+this.applist[a]+'</option>\n';
    }
    return appstring;
}

Dialplan.prototype.addExten = function (exten, extenName)
{
    this.dp[exten] = new Array();
}

Dialplan.prototype.addPrio = function(exten, prio)
{
    prio = Number(prio);
    if (this.dp[exten]['priorities'][prio] != 'undefined') {
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
            // Make a notch for the new priority by incrementing all priorities greater
            // than the requested one.  Try to exclude error handling priorities
            // which are unrelated to the changed extension.  See README for
            // more information.
            // TODO: Make a decision about whether this is the best way to handle
            // error handling priorities.
            if (p > prio && (p < prio + 90 || p > prio + 100)) {
                tmp[p + 1] = this.dp[exten]['priorities'][p];
                plist[i++] = p + 1;
            } else {
                tmp[p] = this.dp[exten]['priorities'][p];
                plist[i++] = p;
            }
        }

        // Seed the new priority
        p = prio + 1;
        tmp[p] = new Array();
        tmp[p]['application'] = '';
        tmp[p]['args'] = '';
        plist[i] = p;


        // Empty the original array
        this.dp[exten]['priorities'] = new Array();

        // Sort the priorities and put them back into the original array
        plist.sort(this._numCompare);
        for (i = 0; i < plist.length; i++) {
            p = Number(plist[i]);
            this.dp[exten]['priorities'][p] = tmp[p];
        }
    }

    this.curPrio = 0;
    this.drawPrioTable(exten);
    this.activatePriority(exten, prio);
    return true;
}

Dialplan.prototype.delPrio = function(exten, prio)
{
    prio = Number(prio);
    if (this.dp[exten]['priorities'][prio] != 'undefined') {
        // The .length method on this array always reports number of priorities + 1;
        // Haven't yet solved this mystery but the below test does work correctly.
        if (this.dp[exten]['priorities'].length <= 2) {
            alert('Extensions must have at least one priority');
            return false;
        }
        // Due to javascript's inability to remove an array element while maintaining
        // associations, we copy the elements into a tmp array and ultimately replace
        // the object's copy.  We will also have to sort the resulting array manually
        // so it renders correctly.
        var tmp = new Array();
        var plist = new Array();
        var i = 0;
        var p;

        for (p in this.dp[exten]['priorities']) {
            // Notch out the old priority by decrementing all priorities greater
            // than the requested one.  Try to exclude error handling priorities
            // which are unrelated to the changed extension.  See README for
            // more information.
            // TODO: Make a decision about whether this is the best way to handle
            // error handling priorities.
            p = Number(p);
            if (p > prio && (p < prio + 90 || p > prio + 100)) {
                tmp[p - 1] = this.dp[exten]['priorities'][p];
                plist[i++] = p - 1;
            } else if (p != prio) {
                tmp[p] = this.dp[exten]['priorities'][p];
                plist[i++] = p;
            }
        }

        // Empty the original array
        this.dp[exten]['priorities'] = new Array();

        // Sort the priorities and put them back into the original array
        plist.sort(this._numCompare);
        for (i = 0; i < plist.length; i++) {
            p = Number(plist[i]);
            this.dp[exten]['priorities'][p] = tmp[p];
        }
    }

    this.curPrio = 0;
    this.drawPrioTable(exten);
    return true;
}

Dialplan.prototype._numCompare = function(a, b)
{
    return (a - b);
}

Dialplan.prototype._incrPrio = function (exten, prio)
{

    return true;
}