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
    this.dom = new Array(); // Store rendered elements for IE
    for (var e in this.dp) {
        this.dom[e] = new Array();
    }
}

Dialplan.prototype.highlightExten = function(exten)
{
    this.drawPrioTable(exten);
    if (this.curExten && this.curExten != exten) {
        this.deactivatePriority();

        document.getElementById('eBox-' + this.curExten).className = 'extensionBox';
        document.getElementById('pList-' + this.curExten).className = 'pList';
    }

    this.curExten = exten;
    document.getElementById('eBox-' + exten).className = 'extensionBoxHighlight';
    document.getElementById('pList-' + exten).className = 'pListHighlight';
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
        this.dom[exten][prio]['priority'].className = 'priorityHighlight';
        this.dom[exten][prio]['pButtons'].style['visibility'] = 'visible';

    } else {
        var form = '';
        form += '    <span class="priorityBox">';
        form += '        <input id="p" type="text" size="3" maxlength="3" name="newprio"';
        form +=              ' value="'+this.curPrio+'" />';
        form += '    </span>';
        this.dom[exten][prio]['pBox'].innerHTML = form;
        this.dom[exten][prio]['pBox'].p.focus;
        this.dom[exten][prio]['pBox'].p.select;
    }
}

Dialplan.prototype.deactivatePriority = function()
{
    if (this.curPrio && document.getElementById('pButtons-'+this.curExten+'-'+this.curPrio)) {
        this.dom[this.curExten][this.curPrio]['priority'].className = 'priority';
        this.dom[this.curExten][this.curPrio]['pButtons'].style['visibility'] = 'hidden';
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

    var pList = document.getElementById('pList-'+exten);

    // Prune all children so we render a clean table
    while(pList.childNodes[0]) {
        pList.removeChild(pList.childNodes[0]);
    }

    for (var p in this.dp[exten]['priorities']) {
//         table += '<div class="priority" id="priority-'+exten+'-'+p+'">\n';
        var priority = document.createElement('div');
        priority.className = 'priority';
        priority.id = 'priority-' + exten + '-' + p;
        pList.appendChild(priority);
        this.dom[exten][p] = new Array();
        this.dom[exten][p]['priority'] = priority;

//         table += '        <span class="pButtons" id="pButtons-'+exten+'-'+p+'"\n';
//         table += '            name="pButtons-'+exten+'-'+p+'">\n';
        var pButtons = document.createElement('span');
        pButtons.className = 'pButtons';
        pButtons.id = 'pButtons-' + exten + '-' + p;
        priority.appendChild(pButtons);
        this.dom[exten][p]['pButtons'] = pButtons;

//         table += '            <span class="add" onclick="javascript:'+this.object+'.addPrio(\''+exten+'\',';
//         table +=                  ' \''+p+'\');">+</span>\n';
        var button = document.createElement('span');
        button.className = 'add';
        button.id = 'pButton-add-'+exten+'-'+p;
        button.dp = this;
        button.exten = exten;
        button.prio = p;
        button.addPrio = function () { this.dp.addPrio(this.exten, this.prio); }
        button.onclick = button.addPrio;
        button.innerHTML='+';
        pButtons.appendChild(button);
        this.dom[exten][p]['pButton-add'] = button;

//         table += '            <span class="remove" onclick="javascript:'+this.object+'.delPrio(\''+exten+'\',';
//         table +=                  ' \''+p+'\');">-</span>\n';
        var button = document.createElement('span');
        button.className = 'remove';
        button.id = 'pButton-del-'+exten+'-'+p;
        button.dp = this;
        button.exten = exten;
        button.prio = p;
        button.delPrio = function () { this.dp.delPrio(this.exten, this.prio); }
        button.onclick = button.delPrio;
        button.innerHTML='-';
        pButtons.appendChild(button);
        this.dom[exten][p]['pButton-del'] = button;

//         table += '        </span>\n';

//         table += '        <span class="pElement" id="pBox-'+exten+'-'+p+'"\n';
//         table += '            name="pBox-'+exten+'-'+p+'"\n';
//         table += '            onclick="javascript:'+this.object+'.activatePriority(\''+exten+'\',';
//         table +=                  ' \''+p+'\')">\n';
        var pElement = document.createElement('span');
        pElement.className = 'pElement';
        pElement.id = 'pBox-'+exten+'-'+p;
        // The next 5 lines are hijinks required to make the highlighting work properly.  We
        // have to save a reference to this object in the pElement object so we can call back
        // into the activate/deactivate routines.  We also have to save the prio and exten because
        // the onclick assignment has to be a function reference which takes no arguments.
        // See above and below comments disparaging javascript.
        pElement.dp = this;
        pElement.exten = exten;
        pElement.prio = p;
        pElement.activate = function () { this.dp.activatePriority(this.exten, this.prio); }
        pElement.onclick = pElement.activate;
        priority.appendChild(pElement);
        this.dom[exten][p]['pBox'] = pElement;

//         table += '            <span class="priorityBox">'+p+'</span>\n';
        var priorityBox = document.createElement('span');
        priorityBox.className = 'priorityBox';
        priorityBox.innerHTML = p;
        pElement.appendChild(priorityBox);

//         table += '        </span>\n';

//         table += '        <span class="pElement" id="pApp-'+exten+'-'+p+'"\n';
//         table += '            name="pApp-'+exten+'-'+p+'">\n';
        var pElement = document.createElement('span');
        pElement.className = 'pElement';
        pElement.id = 'pApp-' + exten + '-' + p;
        priority.appendChild(pElement);
        this.dom[exten][p]['pApp'] = pElement;

//         table += '            <span class="applicationBox">\n';
        var applicationBox = document.createElement('span');
        applicationBox.className = 'applicationBox';
        applicationBox.id = 'applicationBox-' + exten + '-' + p;
        pElement.appendChild(applicationBox);
        this.dom[exten][p]['applicationBox'] = applicationBox;

        var selectHTML = '';
        selectHTML += '                <select name="app['+exten+']['+p+']"';
        selectHTML +=                    'onclick="javascript:'+this.object+'.activatePriority(\''+exten+'\', ';
        selectHTML +=                    '\''+p+'\')">\n';
        selectHTML += this.genAppList(this.dp[exten]['priorities'][p]['application']);
        selectHTML += '                </select>\n';
        applicationBox.innerHTML = selectHTML;

//         table += '            </span>\n';
//         table += '        </span>\n';

//         table += '        <span class="pElement" id="pArgs-'+exten+'-'+p+'"\n';
//         table += '            name="pArgs-'+exten+'-'+p+'">\n';
        var pElement = document.createElement('span');
        pElement.className = 'pElement';
        pElement.id = 'pArgs-' + exten + '-' + p;
        priority.appendChild(pElement);
        this.dom[exten][p]['pArgs'] = pElement;

//         table += '            <span class="argBox" id="args-'+exten+'-'+p+'"\n';
//         table += '                name="args-'+exten+'-'+p+'">';
//         table +=                      this.dp[exten]['priorities'][p]['args']+'</span>\n';
        var argsBox = document.createElement('span');
        argsBox.className = 'argBox';
        argsBox.id = 'argsBox-' + exten + '-' + p;
        argsBox.innerHTML = this.dp[exten]['priorities'][p]['args'];
        pElement.appendChild(argsBox);
        this.dom[exten][p]['argsBox'] = argsBox;

//         table += '        </span>\n';
//         table += '</div>\n';
    }
//     document.getElementById('pList-'+exten).innerHTML = table;
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