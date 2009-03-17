/**
 * dragdrop.js - A minimalist library to handle drag/drop actions.
 * Requires prototype.js 1.6.0.2+
 *
 * Adapted from SkyByte.js/SkyByteDD.js v1.0-beta, May 17 2007
 *   (c) 2007 Aleksandras Ilarionovas (Alex)
 *   http://www.skybyte.net/scripts/
 *
 * Scrolling and ghosting code adapted from script.aculo.us dragdrop.js v1.8.0
 *   (c) 2005-2007 Thomas Fuchs (http://script.aculo.us, http://mir.aculo.us)
 *   (c) 2005-2007 Sammi Williams (http://www.oriontransfer.co.nz, sammi@oriontransfer.co.nz)
 *
 * The original scripts were freely distributable under the terms of an
 * MIT-style license.
 *
 * Usage:
 *   new Drag(element, {
 *       classname: '',           // Class name of the drag element
 *                                // DEFAULT: 'drag'
 *       caption: '',             // Either string or function to set caption
 *                                // on mouse move
 *       parentElement: element,  // Define a parent element
 *       ghosting: false,         // Show ghost outline when dragging.
 *       offset: { x:0, y:0 },    // An offset to apply to ghosted elements.
 *       scroll: element,         // Scroll this element when above/below.
 *                                // Only working for vertical elements
 *       snap: null,              // If ghosting, snap allows to specify
 *                                // coords at which the ghosted image will
 *                                // "snap" into place.
 *       snapToParent: false      // Keep image snapped inside the parent
 *                                // element.
 *       threshold: 0,            // Move threshold
 *       // For the following functions, d = drop element, e = event object
 *       onStart: function(d,e),  // A function to run on mousedown
 *       onDrag: function(d,e),   // A function to run on mousemove
 *       onEnd: function(d,e)     // A function to run on mouseup
 *   });
 *
 *   new Drop(element, {
 *       accept: [],           // Accept filter by tag name(s) or leave empty
 *                             // to accept all tags
 *       caption: '',          // Either string or function to set caption on
 *                             // mouse over
 *       hoverclass: '',       // Change the drag element to this class when
 *                             // hovering over an element.
 *                             // DEFAULT: 'dragdrop'
 *       onDrop: function(drop,drag)  // Function fired when mouse button
 *                                    // released (a/k/a a drop event)
 *       onOver: function(drop,drag)  // Function fired when mouse over zone
 *       onOut: function(drop,drag)   // Function fired when mouse leaves the
 *                                    // zone
 *  });
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
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * @author  Michael Slusarz <slusarz@curecanti.org>
 * @package IMP
 */

var DragDrop = {
    Drags: {
        drags: $H(),

        register: function(obj)
        {
            if (!this.div) {
                this.div = new Element('DIV', { className: obj.options.classname }).setStyle({ position: 'absolute' }).hide();
                $(document.body).insert(this.div);
                document.observe('mousedown', this._mouseHandler.bindAsEventListener(this));
            }

            this.drags.set(obj.element.identify(), obj);
            obj.element.addClassName('DragElt');
        },

        unregister: function(obj)
        {
            if (this.drag == obj.element) {
                this.drag.deactivate();
            }

            this.drags.unset(obj.element.identify());
            obj.element.removeClassName('DragElt');
        },

        getDrag: function(el)
        {
            return this.drags.get(Object.isElement(el) ? $(el).identify() : el);
        },

        activate: function(drag)
        {
            if (this.drag) {
                this.deactivate();
            }
            this.drag = drag;
            this.mousemoveE = drag._mouseMove.bindAsEventListener(drag);
            this.mouseupE = drag._mouseUp.bindAsEventListener(drag);
            document.observe('mousemove', this.mousemoveE);
            document.observe('mouseup', this.mouseupE);
        },

        deactivate: function()
        {
            if (this.drag) {
                this.drag = DragDrop.Drops.drop = null;
                document.stopObserving('mousemove', this.mousemoveE);
                document.stopObserving('mouseup', this.mouseupE);
            }
        },

        _mouseHandler: function(e)
        {
            var elt = e.element();

            if (this.drags.size()) {
                if (!elt.hasClassName('DragElt')) {
                    elt = elt.up('.DragElt');
                }
                if (elt) {
                    this.getDrag(elt).mouseDown(e);
                }
            }
        }
    },

    Drops: {
        drops: $H(),

        register: function(obj)
        {
            this.drops.set(obj.element.identify(), obj);
            obj.element.addClassName('DropElt');
        },

        unregister: function(obj)
        {
            if (this.drop == obj.element) {
                this.drop = null;
            }

            this.drops.unset(obj.element.identify());
            obj.element.removeClassName('DropElt');
        },

        getDrop: function(el)
        {
            return this.drops.get(Object.isElement(el) ? $(el).identify() : el);
        }
    },

    validDrop: function(el)
    {
        var d = DragDrop.Drops.drop;
        return (d &&
                el &&
                el != d.element &&
                (!d.options.accept.size() ||
                 d.options.accept.include(el.tagName)));
    }
},

Drag = Class.create({

    initialize: function(el) {
        this.element = $(el);
        this.options = Object.extend({
            caption: '',
            classname: 'drag',
            parentElement: null,
            constraint: null,
            ghosting: false,
            scroll: null,
            snap: null,
            snapToParent: false,
            threshold: 0,
            onDrag: null,
            onEnd: null,
            onStart: null
        }, arguments[1] || {});
        if (this.options.scroll) {
            this.options.scroll = $(this.options.scroll);
        }
        DragDrop.Drags.register(this);

        // Disable text selection.
        // See: http://ajaxcookbook.org/disable-text-selection/
        // Stopping the event on mousedown works on all browsers, but avoid
        // that if possible because it will prevent any event handlers further
        // up the DOM tree from firing.
        if (Prototype.Browser.IE) {
            this.element.observe('selectstart', Event.stop);
        } else if (Prototype.Browser.Gecko) {
            this.element.setStyle({ MozUserSelect: 'none' });
        }
    },

    destroy: function()
    {
        DragDrop.Drags.unregister(this);
    },

    mouseDown: function(e)
    {
        $(document.body).setStyle({ cursor: 'default' });
        DragDrop.Drags.activate(this);
        this.move = 0;
        this.wasDragged = false;
        this.lastcaption = null;

        if (Object.isFunction(this.options.onStart)) {
            this.options.onStart(this, e);
        }

        if (this.options.snapToParent) {
            if (this.options.parentElement) {
                this.snap = this.options.parentElement().getDimensions();
            } else {
                this.snap = this.element.parentNode.getDimensions();
            }
        }

        if (!Prototype.Browser.IE && !Prototype.Browser.Gecko) {
            e.stop();
        }
    },

    _mouseMove: function(e)
    {
        var oleft, otop, go, eo, xy;

        if (++this.move <= this.options.threshold) {
            return;
        }

        this.lastCoord = xy = [ e.pointerX(), e.pointerY() ];

        if (!this.options.caption) {
            if (!this.ghost) {
                this.ghost = $(this.element.cloneNode(true)).writeAttribute('id', null).clonePosition(this.element).setStyle({ position: 'absolute' });

                if (this.options.ghosting) {
                    this.ghost.setOpacity(0.7).setStyle({ zIndex: parseInt(this.element.getStyle('zIndex')) + 1 });
                    eo = this.element.cumulativeOffset();
                } else {
                    this.elthold = new Element('SPAN').setStyle({ display: 'block' }).clonePosition(this.element);
                    this.element.hide().insert({ before: this.elthold });
                    eo = this.elthold.cumulativeOffset();
                }

                if (this.options.parentElement) {
                    this.options.parentElement().insert(this.ghost);
                } else {
                    this.element.insert({ before: this.ghost });
                }

                go = this.ghost.cumulativeOffset();
                this.ghostOffset = [ go[0] + xy[0] - 2 * eo[0],
                                     go[1] + xy[1] - 2 * eo[1] ];

                if (this.options.snapToParent) {
                    this.dim = this.element.getDimensions();
                    this.dim.width += parseInt(this.element.getStyle('paddingLeft'))
                        + parseInt(this.element.getStyle('paddingRight'))
                        + parseInt(this.element.getStyle('marginLeft'))
                        + parseInt(this.element.getStyle('marginRight'));
                    this.dim.height += parseInt(this.element.getStyle('paddingTop'))
                        + parseInt(this.element.getStyle('paddingBottom'))
                        + parseInt(this.element.getStyle('marginTop'))
                        + parseInt(this.element.getStyle('marginBottom'));
                }
            }

            xy[0] -= this.ghostOffset[0];
            xy[1] -= this.ghostOffset[1];

            if (!this.options.caption) {
                switch (this.options.constraint) {
                case 'horizontal':
                    xy[1] = this.ghost.offsetTop;
                    break;

                case 'vertical':
                    xy[0] = this.ghost.offsetLeft;
                    break;
                }
            }

            if (this.options.snap) {
                xy = this.options.snap(xy[0], xy[1], this.element);
            }
            if (this.options.snapToParent) {
                if (xy[0] < 0) {
                    xy[0] = 0;
                }
                if (xy[0] + this.dim.width > this.snap.width) {
                    xy[0] = this.snap.width - this.dim.width;
                }
                if (xy[1] < 0) {
                    xy[1] = 0;
                }
                if (xy[1] + this.dim.height > this.snap.height) {
                    xy[1] = this.snap.height - this.dim.height;
                }
            }

            if (this.options.offset) {
                xy[0] += this.options.offset.x;
                xy[1] += this.options.offset.y;
            }

            this._setContents(this.ghost, xy[0], xy[1]);
        }

        this._onMoveDrag(xy, e);

        if (Object.isFunction(this.options.onDrag)) {
            this.options.onDrag(this, e);
        }

        this.wasDragged = true;

        if (this.options.scroll) {
            this._onMoveScroll();
        }
    },

    _mouseUp: function(e)
    {
        var d = DragDrop.Drops.drop;

        this._stopScrolling();

        if (this.ghost) {
            if (!this.options.ghosting) {
                this.elthold.remove();
                this.element.show();
            }
            this.ghost.remove();
            this.ghost = null;
        }

        DragDrop.Drags.div.hide();

        if (DragDrop.validDrop(this.element) &&
            Object.isFunction(d.options.onDrop)) {
            d.options.onDrop(d.element, this.element, e);
        }

        DragDrop.Drags.deactivate();

        if (Object.isFunction(this.options.onEnd)) {
            this.options.onEnd(this, e);
        }
    },

    _onMoveDrag: function(xy, e)
    {
        var c_opt, caption, cname, d_cap,
            d = DragDrop.Drops.drop,
            div = DragDrop.Drags.div,
            d_update = true,
            elt = e.element();

        /* elt will be null if we drag off the browser window. */
        if (!Object.isElement(elt)) {
            return;
        }

        if (this.lastelt == elt) {
            this._setCaption(div, xy);
            return;
        }

        this.lastelt = elt;

        /* Do mouseover/mouseout-like detection here. Saves on observe calls
         * and handles case where mouse moves over scrollbars. */
        if (DragDrop.Drops.drops.size()) {
            if (!elt.hasClassName('DropElt')) {
                elt = elt.up('.DropElt');
            }

            if (elt) {
                elt = DragDrop.Drops.getDrop(elt);
                if (elt == d) {
                    d_update = false;
                } else {
                    elt.mouseOver();
                    d = elt;
                }
            } else if (d) {
                d.mouseOut();
                d = null;
            }
        }

        if (d_update && d && DragDrop.validDrop(this.element)) {
            d_cap = d.options.caption;
            if (d_cap) {
                caption = Object.isFunction(d_cap) ? d_cap(d.element, this.element, e) : d_cap;
                if (caption && d.options.hoverclass) {
                    cname = d.options.hoverclass;
                }
            } else {
                d_update = false;
            }
        }

        if (d_update) {
            if (!caption) {
                c_opt = this.options.caption;
                caption = Object.isFunction(c_opt) ? c_opt(this.element) : c_opt;
            }
            if (caption != this.lastcaption) {
                this.lastcaption = caption;
                div.update(caption).writeAttribute({ className: cname || this.options.classname });
                if (caption.empty()) {
                    div.hide();
                }
            }
        }

        this._setCaption(div, xy);
    },

    _setCaption: function(div, xy)
    {
        if (this.lastcaption) {
            this._setContents(div, xy[0] + 15, xy[1] + (this.ghost ? (this.ghost.getHeight() + 5) : 5));
        }
    },

    _onMoveScroll: function()
    {
        this._stopScrolling();

        var delta, p, speed,
            s = this.options.scroll,
            dim = s.getDimensions();

        // No need to scroll if element is not current scrolling.
        if (s.scrollHeight == dim.height) {
            return;
        }

        delta = document.viewport.getScrollOffsets();
        p = s.viewportOffset(),
        speed = [ 0, 0 ];

        p[0] += s.scrollLeft + delta.left;
        p[2] = p[0] + dim.width;

        // Only scroll if directly above/below element
        if (this.lastCoord[0] > p[2] ||
            this.lastCoord[0] < p[0]) {
            return;
        }

        p[1] += s.scrollTop + delta.top;
        p[3] = p[1] + dim.height;

        // Left scroll
        //if (this.lastCoord[0] < p[0]) {
        //    speed[0] = this.lastCoord[0] - p[0];
        //}
        // Top scroll
        if (this.lastCoord[1] < p[1]) {
            speed[1] = this.lastCoord[1] - p[1];
        }
        // Scroll right
        //if (this.lastCoord[0] > p[2]) {
        //    speed[0] = this.lastCoord[0] - p[2];
        //}
        // Scroll left
        if (this.lastCoord[1] > p[3]) {
            speed[1] = this.lastCoord[1] - p[3];
        }

        if (speed[0] || speed[1]) {
            this.lastScrolled = new Date();
            this.scrollInterval = setInterval(this._scroll.bind(this, speed[0] * 15, speed[1] * 15), 10);
        }
    },

    _stopScrolling: function()
    {
        if (this.scrollInterval) {
            clearInterval(this.scrollInterval);
            this.scrollInterval = null;
        }
    },

    _scroll: function(x, y)
    {
        var current = new Date(),
            delta = current - this.lastScrolled,
            s = this.options.scroll;
        this.lastScrolled = current;

        //s.scrollLeft += x * delta / 1000;
        s.scrollTop += y * delta / 1000;
    },

    _setContents: function(elt, x, y)
    {
        var e_pos = elt.getDimensions(),
            l = x,
            t = y;

        if (x < 0) {
            l = 0;
        } else if (x + e_pos.width > window.innerWidth) {
            l = window.innerWidth - e_pos.width;
        }

        if (y < 0) {
            t = 0;
        } else if (y + e_pos.height > window.innerHeight) {
            t = window.innerHeight - e_pos.height;
        }

        elt.setStyle({ left: l + 'px', top: t + 'px' }).show();
    }

}),

Drop = Class.create({

    initialize: function(el)
    {
        this.element = $(el);
        this.options = Object.extend({
            accept: [],
            caption: '',
            hoverclass: 'dragdrop',
            onDrop: null,
            onOut: null,
            onOver: null
        }, arguments[1] || {});
        DragDrop.Drops.register(this);
    },

    destroy: function()
    {
        DragDrop.Drops.unregister(this);
    },

    mouseOver: function()
    {
        DragDrop.Drops.drop = this;
        if (Object.isFunction(this.options.onOver)) {
            this.options.onOver(this.element, DragDrop.Drags.drag);
        }
    },

    mouseOut: function()
    {
        if (Object.isFunction(this.options.onOut)) {
            this.options.onOut(this.element, DragDrop.Drags.drag);
        }
        DragDrop.Drops.drop = null;
    }
});
