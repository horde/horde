/**
 * Slider2.js - A minimalist library to create a slider that acts like a
 * browser's native scrollbar.
 *
 * Requires prototype.js 1.6.0.2+
 *
 *
 * Usage:
 * ------
 * slider = new Slider2(track, { options });
 *
 *   track - (element|string) TODO
 *   options - (object) TODO
 *
 * Custom Events:
 * --------------
 * Custom events are triggered on the track element.
 *
 * Slider2:change
 *   Fired when slidebar is released and has moved from the original value.
 *
 * Slider2:end
 *   Fired when slidebar is released.
 *
 * Slider2:slide
 *   Fired when slidebar is moved.
 *
 * Slider2:start
 *   Fired when slidebar is clicked on.
 *
 *
 * Adapted from script.aculo.us slider.js v1.8.0
 *   (c) 2005-2007 Marty Haught, Thomas Fuchs
 *   http://script.aculo.us/
 *
 * The original script was freely distributable under the terms of an
 * MIT-style license.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 *
 * Copyright 2007-2012 Horde LLC (http://www.horde.org/)
 *
 * @author Michael Slusarz <slusarz@horde.org>
 */

var Slider2 = Class.create({

    value: 0,

    initialize: function(track, options)
    {
        this.track = $(track);
        this.options = Object.extend({
            buttonclass: null,
            cursorclass: null,
            pagesize: 0,
            totalsize: 0
        }, options || {});

        this.handle = new Element('DIV', { className: this.options.cursorclass }).makePositioned();
        this.track.insert(this.handle);

        if (this.options.buttonclass) {
            this.sbup = new Element('DIV', { className: this.options.buttonclass.up });
            this.sbdown = new Element('DIV', { className: this.options.buttonclass.down }).makePositioned();
            this.handle.insert({ before: this.sbup, after: this.sbdown });
            [ this.sbup, this.sbdown ].invoke('observe', 'mousedown', this._arrowClick.bindAsEventListener(this));
        }

        if (Prototype.Browser.IE) {
            [ this.track, this.sbup ].invoke('makePositioned');
        }

        this.value = 0;
        this.active = this.dragging = false;

        if (this.needScroll()) {
            this._initScroll();
        }

        [ this.handle, this.track ].invoke('observe', 'mousedown', this._startDrag.bindAsEventListener(this));

        document.observe('mouseup', this._endDrag.bindAsEventListener(this));
        document.observe('mousemove', this._update.bindAsEventListener(this));
    },

    _initScroll: function()
    {
        if (this.init) {
            return;
        }
        this.init = true;
        this.track.show();
        this._updateHandleLength();
    },

    _startDrag: function(e)
    {
        var elt = e.element();

        if (!e.isLeftClick() || elt == this.sbup || elt == this.sbdown) {
            return;
        }

        var dir,
            hoffsets = this.handle.cumulativeOffset();

        if (elt == this.track) {
            dir = (e.pointerY() < hoffsets[1]) ? -1 : 1;
            this.setScrollPosition(this.getValue() - dir + (this.options.pagesize * dir));
        } else {
            this.curroffsets = this.track.cumulativeOffset();
            this.offsetY = e.pointerY() - hoffsets[1] + this.sbup.offsetHeight;
            this.active = true;
            this.track.fire('Slider2:start');
        }

        e.stop();
    },

    _update: function(e)
    {
        if (this.active) {
            this.dragging = true;
            this._setScrollPosition('px', Math.min(Math.max(0, e.pointerY() - this.offsetY - this.curroffsets[1]), this.handletop));
            this.track.fire('Slider2:slide');
            if (Prototype.Browser.WebKit) {
                window.scrollBy(0,0);
            }
            e.stop();
        }
    },

    _endDrag: function(e)
    {
        if (this.active) {
            if (this.dragging) {
                this._updateFinished();
                e.stop();
            }
            this.track.fire('Slider2:end');
        }
        this.active = this.dragging = false;
    },

    _arrowClick: function(e)
    {
        this.setScrollPosition(this.getValue() + ((e.element() == this.sbup) ? -1 : 1));
    },

    _updateFinished: function()
    {
        this.track.fire('Slider2:change');
    },

    setHandleLength: function(pagesize, totalsize)
    {
        this.options.pagesize = pagesize;
        this.options.totalsize = totalsize;
    },

    updateHandleLength: function()
    {
        if (!this.needScroll()) {
            this.value = 0;
            this.track.hide();
        } else {
            this.track.show();
            this._updateHandleLength();
        }
    },

    _updateHandleLength: function()
    {
        var t = this.track.offsetHeight - this.sbup.offsetHeight - this.sbdown.offsetHeight;

        // Minimum handle size = 10px
        this.handle.setStyle({ height: Math.max(10, Math.round((this.options.pagesize / this.options.totalsize) * t)) + 'px' });
        this.handletop = t - this.handle.offsetHeight;
        if (this.sbdown) {
            this.sbdown.setStyle({ top: this.handletop + 'px' });
        }
        this._setScrollPosition('val', this.getValue());
    },

    getValue: function()
    {
        return this.value;
    },

    setScrollPosition: function(val)
    {
        var oldval = this.getValue();
        this._setScrollPosition('val', val);
        if (oldval != this.getValue()) {
            this._updateFinished();
        }
    },

    _setScrollPosition: function(type, data)
    {
        this.value = (type == 'val')
            ? Math.min(Math.max(0, data), Math.max(0, this.options.totalsize - this.options.pagesize))
            : Math.max(0, Math.round(Math.min(data, this.handletop) / this.handletop * (this.options.totalsize - this.options.pagesize)));

        if (type == 'px') {
            this.handlevalue = data;
        } else {
            this.handlevalue = Math.round(this.value / (this.options.totalsize - this.options.pagesize) * this.handletop);

            /* Always make sure there is at least 1 pixel if we are not at the
             * absolute bottom or top. */
            if (isNaN(this.handlevalue)) {
                this.handlevalue = 0;
            } else if (this.handlevalue == 0 && this.value != 0) {
                this.handlevalue += 1;
            } else if (this.handlevalue == this.handletop &&
                       ((this.options.totalsize - this.options.pagesize) != this.value)) {
                this.handlevalue -= 1;
            }
        }

        this.handle.setStyle({ top: this.handlevalue + 'px' });
    },

    needScroll: function()
    {
        return (this.options.pagesize < this.options.totalsize);
    }
});
