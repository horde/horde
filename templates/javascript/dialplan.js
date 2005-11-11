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
    this.dp = eval('shout_dialplan_'+instanceName);
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
    if (this.curExten) {
        this.deactivatePriority();
    }

    if (exten != this.curExten) {
        this.highlightExten(exten);
    }

    this.curPrio = prio;
    document.getElementById('pButtons-'+exten+'-'+prio).className = 'pButtonsHighlight';
    document.getElementById('pNumber-'+exten+'-'+prio).className = 'pElementHighlight';
    document.getElementById('pApp-'+exten+'-'+prio).className = 'pElementHighlight';
    document.getElementById('pArgs-'+exten+'-'+prio).className = 'pElementHighlight';
}

Dialplan.prototype.deactivatePriority = function()
{
    if (this.curPrio && document.getElementById('pButtons-'+this.curExten+'-'+this.curPrio)) {
        document.getElementById('pButtons-'+this.curExten+'-'+this.curPrio).className = 'pButtons';
        document.getElementById('pNumber-'+this.curExten+'-'+this.curPrio).className = 'pElement';
        document.getElementById('pApp-'+this.curExten+'-'+this.curPrio).className = 'pElement';
        document.getElementById('pArgs-'+this.curExten+'-'+this.curPrio).className = 'pElement';
    }
}

Dialplan.prototype.drawPrioTable = function (exten)
{
    if (!exten) {
        alert('Must first choose an extension to draw');
        return false;
    }
    alert(document.getElementById('pList-'+exten).innerHTML);
    table  = '<table class="pList" cellspacing="0">';
    table += '  <tbody>\n';
    table += '    <tr class="priority">\n';
    for (var p in this.dp[exten]['priorities']) {
        table += '        <td class="pButtons" id="pButtons-'+exten+'-'+p+'"\n';
        table += '            name="pButtons-'+exten+'-'+p+'">\n';
        table += '            <span class="add" onclick="javascript:dp.addPrio(\''+exten+'\', \''+p+'\');">+</span>\n';
        table += '            <span class="remove" onclick="javascript:dp.delPrio(\''+exten+'\', \''+p+'\');">-</span>\n';
        table += '        </td>\n';
        table += '        <td class="pElement" id="pNumber-'+exten+'-'+p+'"\n';
        table += '            name="pNumber-'+exten+'-'+p+'"\n';
        table += '            onclick="javascript:dp.activatePriority(\''+exten+'\', \''+p+'\')">\n';
        table += '            <span class="priorityBox">'+p+'</span>\n';
        table += '        </td>\n';
        table += '        <td class="pElement" id="pApp-'+exten+'-'+p+'"\n';
        table += '            name="pApp-'+exten+'-'+p+'">\n';
        table += '            <span class="applicationBox"></span>\n';
        table += '                <select name="app['+exten+']['+p+']">\n';
        table += '                    <option value="APPLICATION">APPLICATION</option>\n';
        table += '                </select>\n';
        table += '            </span>\n';
        table += '        </td>\n';
        table += '        <td class="pElement" id="pArgs-'+exten+'-'+p+'"\n';
        table += '            name="pArgs-'+exten+'-'+p+'">\n';
        table += '            <span class="argBox" id="args-'+exten+'-'+p+'"\n';
        table += '                name="args-'+exten+'-'+p+'">ARGS</span>\n';
        table += '        </td>\n';
    }
    table += '    </tr>\n';
    table += '  </tbody>\n';
    table += '</table>\n';
    alert(table);
    document.getElementById('pList-'+exten).innerHTML = table;
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
    this.drawPrioTable(exten);
}

Dialplan.prototype._incrPrio = function (exten, prio)
{
    p = Number(prio) + 1;
    h = Number(prio) + 101;

    // Check for error handlers
    if (this.dp[exten]['priorities'][h] != 'undefined') {
        alert(this.dp[exten][h]);
        //this._incrPrio(exten, h);
    }

    // Make sure the next slot is empty.  If not move it first.
    if (this.dp[exten]['priorities'][p] != 'undefined') {
        alert(p);
        //this._incrPrio(exten, p);
    }

    // Copy the existing prio to its new home
    this.dp[exten]['priorities'][p] = this.dp[exten]['priorities'][prio];
}