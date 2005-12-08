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
 * $Id$
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
    this.curExten = 0;
    this.curPrio = 0;
    this.prioActive = false;
}

Dialplan.prototype.highlightExten = function(exten)
{
    if (this.curExten && this.curExten != exten) {
        this.dehighlightPrio();
        this.dehighlightExten();
    }

    this.curExten = exten;
    document.getElementById('eBox-' + exten).className = 'extensionBoxHighlight';
    document.getElementById('pList-' + exten).className = 'pListHighlight';
}

Dialplan.prototype.dehighlightExten = function(exten)
{
    document.getElementById('eBox-' + this.curExten).className = 'extensionBox';
    document.getElementById('pList-' + this.curExten).className = 'pList';
    this.curExten = 0;
}

Dialplan.prototype.highlightPrio = function(exten, prio)
{
    if (exten != this.curExten) {
        this.highlightExten(exten);
    }
    if (this.curPrio && prio != this.curPrio) {
        this.dehighlightPrio();
    }

    this.curPrio = prio;
    priority = document.getElementById('priority-'+exten+'-'+prio);
    priority.className = 'priorityHighlight';
    priority.onclick = priority.activate;
    document.getElementById('pButtons-'+exten+'-'+prio).style['visibility'] = 'visible';
}

Dialplan.prototype.dehighlightPrio = function()
{
    this.deactivatePrio();
    if (!this.curPrio) {
        return true;
    }

    priority = document.getElementById('priority-'+this.curExten+'-'+this.curPrio);
    priority.className = 'priority';
    priority.onclick = priority.highlight;
    document.getElementById('pButtons-'+this.curExten+'-'+this.curPrio).style['visibility'] = 'hidden';
    this.curPrio = 0;
    return true;
}


Dialplan.prototype.activatePrio = function(exten, prio)
{
    document.getElementById('pBox-'+exten+'-'+prio).style['display'] = 'none';
    document.getElementById('pBoxInput-'+exten+'-'+prio).style['display'] = 'inline';
    document.getElementById('pApp-'+exten+'-'+prio).style['display'] = 'none';
    document.getElementById('pAppInput-'+exten+'-'+prio).style['display'] = 'inline';
    document.getElementById('pArgs-'+exten+'-'+prio).style['display'] = 'none';
    document.getElementById('pArgsInput-'+exten+'-'+prio).style['display'] = 'inline';
    this.prioActive = true;
}

Dialplan.prototype.deactivatePrio = function()
{
    if (!this.prioActive) {
        // Speed hack: If thie priority isn't active, don't go through the
        // motions to deactivate it.
        return true;
    }
    var dirty = false;
    var pAppInput = document.getElementById('pAppInput-'+this.curExten+'-'+this.curPrio);
    var pArgsInput = document.getElementById('pArgsInput-'+this.curExten+'-'+this.curPrio);

    if (pAppInput.value != this.dp[this.curExten]['priorities'][this.curPrio]['application']) {
        this.dp[this.curExten]['priorities'][this.curPrio]['application'] = pAppInput.value;
        dirty = true;
    }

    if (pArgsInput != this.dp[this.curExten]['priorities'][this.curPrio]['args']) {
        this.dp[this.curExten]['priorities'][this.curPrio]['args'] = pArgsInput.value;
        dirty = true;
    }

    // Check to see if the priority number was updated
    prio = Number(document.getElementById('pBoxInput-'+this.curExten+'-'+this.curPrio).value);
    if (this.curPrio != prio) {
        this.renumberPrio(prio);
        this.curPrio = prio;
        // Since we've just redrawn the prio table no sense in drawing it again
        dirty = false;
    }

    // This test is purely a speed hack.  Redrawing the prio table is slower than simply resetting
    // the status of the elements.  However if data has changed we are forced to redraw the prio table.
    if (dirty) {
        this.drawPrioTable(this.curExten);
    } else {
        document.getElementById('pBox-'+this.curExten+'-'+this.curPrio).style['display'] = 'inline';
        document.getElementById('pBoxInput-'+this.curExten+'-'+this.curPrio).style['display'] = 'none';
        document.getElementById('pApp-'+this.curExten+'-'+this.curPrio).style['display'] = 'inline';
        document.getElementById('pAppInput-'+this.curExten+'-'+this.curPrio).style['display'] = 'none';
        document.getElementById('pArgs-'+this.curExten+'-'+this.curPrio).style['display'] = 'inline';
        document.getElementById('pArgsInput-'+this.curExten+'-'+this.curPrio).style['display'] = 'none';
    }
    this.prioActive = false;

    return true;
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
        // The next 5 lines are hijinks required to make the highlighting work properly.  We
        // have to save a reference to this object in the pElement object so we can call back
        // into the activate/deactivate routines.  We also have to save the prio and exten because
        // the onclick assignment has to be a function reference which takes no arguments.
        // See above and below comments disparaging javascript.
        priority.dp = this;
        priority.exten = exten;
        priority.prio = p;
        priority.highlight = function () { this.dp.highlightPrio(this.exten, this.prio); }
        priority.activate = function () { this.dp.activatePrio(this.exten, this.prio); }
        priority.onclick = priority.highlight;
        pList.appendChild(priority);

        var pButtons = document.createElement('span');
        pButtons.className = 'pButtons';
        pButtons.id = 'pButtons-' + exten + '-' + p;
        priority.appendChild(pButtons);

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

        var pElement = document.createElement('span');
        pElement.className = 'pElement';
        priority.appendChild(pElement);

        var pBox = document.createElement('span');
        pBox.className = 'pBox';
        pBox.id = 'pBox-'+exten+'-'+p;
        pBox.style['display'] = 'inline';
        pBox.innerHTML = p;
        pElement.appendChild(pBox);

        var pBoxInput = document.createElement('input');
        pBoxInput.type = 'text';
        pBoxInput.size = 3;
        pBoxInput.id = 'pBoxInput-'+exten+'-'+p;
        pBoxInput.name = 'pBoxInput-'+exten+'-'+p;
        pBoxInput.value = p;
        pBoxInput.maxlength = 3;
        pBoxInput.style['display'] = 'none';
        pBoxInput.dp = this;
        pBoxInput.exten = exten;
        pBoxInput.prio = p;
        pBoxInput.onblur = pBoxInput.deactivate;
        pBoxInput.deactivate = function () { this.dp.deactivatePriority(); }
        pElement.appendChild(pBoxInput);

        var pElement = document.createElement('span');
        pElement.className = 'pElement';
        priority.appendChild(pElement);

        var pApp = document.createElement('span');
        pApp.className = 'pApp';
        pApp.id = 'pApp-' + exten + '-' + p;
        pApp.style['display'] = 'inline';
        pElement.appendChild(pApp);
        pApp.innerHTML = this.dp[exten]['priorities'][p]['application'];

        var pAppInput = document.createElement('select');
        pAppInput.className = 'pAppInput';
        pAppInput.id = 'pAppInput-' + exten + '-' + p;
        pAppInput.name = 'pAppInput-' + exten + '-' + p;
        pAppInput.style['display'] ='none';
        pElement.appendChild(pAppInput);
        this.genAppList(pAppInput, this.dp[exten]['priorities'][p]['application']);

        var pElement = document.createElement('span');
        pElement.className = 'pElement';
        priority.appendChild(pElement);

        var pArgs = document.createElement('span');
        pArgs.className = 'pArgs';
        pArgs.id = 'pArgs-' + exten + '-' + p;
        pArgs.innerHTML = this.dp[exten]['priorities'][p]['args'];
        pElement.appendChild(pArgs);

        var pArgsInput = document.createElement('input');
        pArgsInput.className = 'pArgsInput';
        pArgsInput.id = 'pArgsInput-' + exten + '-' + p;
        pArgsInput.name = 'pArgsInput-' + exten + '-' + p;
        pArgsInput.value = this.dp[exten]['priorities'][p]['args'];
        pArgsInput.style['display'] = 'none';
        pElement.appendChild(pArgsInput);
    }
    return true;
}

Dialplan.prototype.genAppList = function (selectObj, app)
{
    for (var a in this.applist) {
        var o = document.createElement('option');
        o.value = this.applist[a];
        if (this.applist[a] == app) {
            o.selected = true;
        }
        o.innerHTML = this.applist[a];
        selectObj.appendChild(o);
    }
    return true;
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
        var firstEmpty = prio + 1;

        // Locate an empty priority.  We should not increment any priorities past this as
        // the lower priorities will move to fill this hole.
        while (this.dp[exten]['priorities'][firstEmpty]) {
            firstEmpty++;
        }

        for (p in this.dp[exten]['priorities']) {
            p = Number(p);
            // Make a notch for the new priority by incrementing all priorities greater
            // than the requested one.  Try to exclude error handling priorities
            // which are unrelated to the changed extension.  See README for
            // more information.
            // TODO: Make a decision about whether this is the best way to handle
            // error handling priorities.
            if (p > prio && (p < prio + 90 || p > prio + 100) && p < firstEmpty) {
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

    this.drawPrioTable(exten);
    this.highlightPrio(exten, prio);
    return true;
}

Dialplan.prototype.insertPrio = function(exten, prio)
{
    // Simple wrapper for addPrio()
    // Create an empty slot.  Subtract one from the new prio because the
    // behavior of addPrio is to append to the specified location.
    return this.addPrio(exten, prio - 1);
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

Dialplan.prototype.renumberPrio = function(newPrio)
{
    // Copy the old prio to a temporary location for future use
    var tmp = new Array();
    var oldPrio = Number(this.curPrio);
    tmp = this.dp[this.curExten]['priorities'][oldPrio];
    newPrio = Number(newPrio);

    // Empty out the old priority
    this.delPrio(this.curExten, oldPrio);

    this.insertPrio(this.curExten, newPrio);
    // Copy the old priority into its new home
    this.dp[this.curExten]['priorities'][newPrio] = tmp;
    this.drawPrioTable(this.curExten);

    // Highlight the renumbered priority
    this.curPrio = newPrio;
    this.highlightPrio(this.curExten, this.curPrio);

    return true;
}

Dialplan.prototype._numCompare = function(a, b)
{
    return (a - b);
}