/**
 * Horde Form Sorter Field Javascript Class
 *
 * Provides the javascript class to accompany the Horde_Form sorter
 * field.
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
*
* @author Marko Djukic <marko@oblo.com>
 */

function Horde_Form_Sorter(instanceName, varName, header)
{
    /* Set up this class instance for function calls from the page. */
    this._instanceName = instanceName;

    this._varName = varName;

    /* Sorter variables. */
    this._header = '';
    this.minLength = 0;
    if (header != '') {
        this._header = header;
        this.minLength = 1;
    }
    this.sorterList = document.getElementById(this._varName + '_list');
    this.sorterArray = document.getElementById(this._varName + '_array');

    this.deselectHeader = function()
    {
        if (this._header != '') {
            this.sorterList[0].selected = false;
        }
    }

    this.setHidden = function()
    {
        var tmpArray = [];

        for (var i = this.minLength; i < this.sorterList.length; i++) {
            if (this.sorterList[i].value) {
                tmpArray[i - this.minLength] = this.sorterList[i].value;
            }
        }

        this.sorterArray.value = tmpArray.join("\t");
    }

    this.moveColumnUp = function()
    {
        var sel = this.sorterList.selectedIndex;

        if (sel <= this.minLength || this.sorterList.length <= this.minLength + 1) return;

        /* Deselect everything but the first selected item. */
        this.sorterList.selectedIndex = sel;
        var up = this.sorterList[sel].value;

        tmp = [];
        for (i = this.minLength; i < this.sorterList.length; i++) {
            tmp[i - this.minLength] = new Option(this.sorterList[i].text, this.sorterList[i].value)
        }

        for (i = 0; i < tmp.length; i++) {
            if (i + this.minLength == sel - 1) {
                this.sorterList[i + this.minLength] = tmp[i + 1];
            } else if (i + this.minLength == sel) {
                this.sorterList[i + this.minLength] = tmp[i - 1];
            } else {
                this.sorterList[i + this.minLength] = tmp[i];
            }
        }

        this.sorterList.selectedIndex = sel - 1;

        this.setHidden();
    }

    this.moveColumnDown = function()
    {
        var sel = this.sorterList.selectedIndex;

        if (sel == -1 || sel == this.sorterList.length - 1) return;

        /* Deselect everything but the first selected item. */
        this.sorterList.selectedIndex = sel;
        var down = this.sorterList[sel].value;

        tmp = [];
        for (i = this.minLength; i < this.sorterList.length; i++) {
            tmp[i - this.minLength] = new Option(this.sorterList[i].text, this.sorterList[i].value)
        }

        for (i = 0; i < tmp.length; i++) {
            if (i + this.minLength == sel) {
                this.sorterList[i + this.minLength] = tmp[i + 1];
            } else if (i + this.minLength == sel + 1) {
                this.sorterList[i + this.minLength] = tmp[i - 1];
            } else {
                this.sorterList[i + this.minLength] = tmp[i];
            }
        }

        this.sorterList.selectedIndex = sel + 1;

        this.setHidden();
    }
}
