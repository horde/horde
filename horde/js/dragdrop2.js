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
 * ------
 * new Drag(element, {
 *     caption: '',               // Either string or function to set caption
 *                                // on mouse move.
 *     classname: '',             // Class name of the drag element.
 *                                // DEFAULT: 'drag'
 *     constraint: '',            // Constrain movement to 'horizontal' or
 *                                // 'vertical'.
 *     ghosting: false,           // Show ghost outline when dragging.
 *     nodrop: false,             // Don't do drop checking. Optimizes
 *                                // movement speed.
 *     offset: { x:0, y:0 },      // An offset to apply to ghosted elements.
 *     parentElement: function(), // Function returns the parent element.
 *     scroll: element,           // Scroll this element when above/below (
 *                                // only for vertical elements).
 *     snap: null,                // If ghosting, snap allows to specify
 *                                // coords at which the ghosted image will
 *                                // "snap" into place.
 *     snapToParent: false        // Keep image snapped inside the parent
 *                                // element.
 *     threshold: 0               // Move threshold.
 * });
 *
 * Events fired for Drags:
 * -----------------------
 * Custom events are triggered on the drag element. The 'memo' property of
 * the Event object contains the original event object.
 *
 * 'DragDrop2:drag'
 *   Fired on mousemove.
 *
 * 'DragDrop2:end'
 *   Fired when dragging ends.
 *
 * 'DragDrop2:mousedown'
 *   Fired on mousedown.
 *
 * 'DragDrop2:mouseup'
 *   Fored on mouseup *if* the element was not dragged.
 *
 * 'DragDrop2:start'
 *   Fired when first moved more than 'threshold'.
 *
 *
 * new Drop(element, {
 *     accept: [],      // Accept filter by tag name(s) or leave empty to
 *                      // accept all tags.
 *     caption: '',     // Either string or function to set caption on
 *                      // mouseover.
 *     hoverclass: '',  // Change the drag element to this class when hovering
 *                      // over an element.
 *                      // DEFAULT: 'dragdrop'
 *     keypress: false  // If true, will re-render caption if a keypress is
 *                      // detected while a drop is active (useful for
 *                      // CTRL/SHIFT actions).
 * });
 *
 * Events fired for Drops:
 * -----------------------
 * Custom events are triggered on the drop element. The 'memo' property of
 * the Event object contains the Drag object. The dragged element is available
 * in 'memo.element'. The browser event that triggered the custom event is
 * available in 'memo.dragevent'.
 *
 * 'DragDrop2:drop'
 *   Fired when mouse button released (a/k/a a drop event).
 *
 * 'DragDrop2:out'
 *   Fired when mouse leaves the drop zone.
 *
 * 'DragDrop2:over'
 *   Fired when mouse over drop zone.
 *
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
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde
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
            this.keypressE = drag._keyPress.bindAsEventListener(drag);
            document.observe('mousemove', this.mousemoveE);
            document.observe('mouseup', this.mouseupE);
            document.observe('keydown', this.keypressE);
            document.observe('keyup', this.keypressE);
        },

        deactivate: function()
        {
            if (this.drag) {
                this.drag = DragDrop.Drops.drop = null;
                document.stopObserving('mousemove', this.mousemoveE);
                document.stopObserving('mouseup', this.mouseupE);
                document.stopObserving('keydown', this.keypressE);
                document.stopObserving('keyup', this.keypressE);
            }
        },

        _mouseHandler: function(e)
        {
            var elt = e.findElement('.DragElt');
            if (this.drags.size() && elt) {
                this.getDrag(elt).mouseDown(e);
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

};

Drag = Class.create({

    initialize: function(el)
    {
        this.dragevent = null;
        this.element = $(el);
        this.ghostOffset = [ 0, 0 ];
        this.options = Object.extend({
            caption: '',
            classname: 'drag',
            constraint: null,
            ghosting: false,
            nodrop: false,
            parentElement: null,
            scroll: null,
            snap: null,
            snapToParent: false,
            threshold: 0
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
        DragDrop.Drags.activate(this);
        this.move = 0;
        this.wasDragged = false;
        this.wasMoved = false;
        this.lastcaption = null;
        this.clickEvent = e;

        this.element.fire('DragDrop2:mousedown', Object.clone(e));

        if (this.options.ghosting || this.options.caption) {
            if (!DragDrop.Drags.cover) {
                DragDrop.Drags.cover = new Element('DIV', { id: 'dragdrop2Cover' });
                $(document.body).insert(DragDrop.Drags.cover);
                DragDrop.Drags.cover.insert(new Element('DIV').setStyle({ position: 'absolute' }).hide());
            }

            $$('IFRAME').each(function(i) {
                var z;
                if (i.visible()) {
                    z = parseInt(i.getStyle('zIndex'), 10);
                    if (isNaN(z)) {
                        z = 2;
                    }
                    DragDrop.Drags.cover.insert(DragDrop.Drags.cover.down().clone(false).setStyle({ zIndex: z }).clonePosition(i).show());
                }
            }, this);
        }

        if (this.options.snapToParent) {
            this.snap = this.options.parentElement
                ? this.options.parentElement().getDimensions()
                : this.element.parentNode.getDimensions();
        }

        // Stop event to prevent text selection. IE and Gecko are handled in
        // initialize().
        if (!Prototype.Browser.IE && !Prototype.Browser.Gecko) {
            e.stop();
        }
    },

    _mouseMove: function(e)
    {
        var go, eo, po, xy, p, delta, int;

        if (++this.move <= this.options.threshold) {
            return;
        } else if (!this.wasMoved) {
            this.element.fire('DragDrop2:start', Object.clone(this.clickEvent));
            this.wasMoved = true;
        }

        this.lastCoord = xy = [ e.pointerX(), e.pointerY() ];

        if (!this.options.caption) {
            if (!this.ghost) {
                // Use the position of the original click event as the start
                // coordinate.
                xy = [ this.clickEvent.pointerX(), this.clickEvent.pointerY() ];

                // Create the "ghost", i.e. the moving element, a clone of the
                // original element, if it doesn't exist yet.
                var layout = this.element.getLayout();
                this.ghost = $(this.element.clone(true))
                    .writeAttribute('id', null)
                    .addClassName(this.options.classname)
                    .setStyle({ position: 'absolute', height: layout.get('height') + 'px', width: layout.get('width') + 'px' });

                var p = this.element.viewportOffset();
                var delta = document.body.viewportOffset();
                delta[0] -= document.body.offsetLeft;
                delta[1] -= document.body.offsetTop;
                this.ghost.style.left = (p[0] - delta[0]) + 'px';
                this.ghost.style.top  = (p[1] - delta[1]) + 'px';

                // eo is the offset of the original element to the body.
                eo = this.element.cumulativeOffset();

                // Save external dimensions, i.e. height and width including
                // padding and margins, for later usage.
                this.dim = {
                    width: layout.get('margin-box-width'),
                    height: layout.get('margin-box-height')
                }

                if (this.options.ghosting) {
                    var z = parseInt(this.element.getStyle('zIndex'), 10);
                    if (isNaN(z)) {
                        z = 1;
                    }
                    this.ghost.setOpacity(0.7).setStyle({ zIndex: z + 1 });
                } else {
                    this.element.setStyle({ visibility: 'hidden' });
                }

                // Insert ghost into the parent, either specified by a
                // function result, or using the original element's parent.
                if (this.options.parentElement) {
                    this.options.parentElement().insert(this.ghost);
                } else {
                    this.element.insert({ before: this.ghost });
                }

                // go is the offset of the ghost to the body. This might be
                // different from the original element's offset because we
                // used that element's position when cloning the ghost, but
                // they might have different parents now.
                go = this.ghost.cumulativeOffset();

                // Calculate the difference between the ghost's offset and the
                // orginal element's offset.
                this.ghostOffset = [ go[0] - eo[0], go[1] - eo[1] ];

                // Add the event coordinates to the offset, because we use the
                // coordinates during later mousemove events as a basis for
                // the new ghost position. But we don't want to position the
                // ghost relative to the mouse pointer, but relative to where
                // the mouse pointer clicked when the ghost was created.
                // @todo: why do we subtract eo?
                if (this.options.offset) {
                    this.mouseOffset = this.ghostOffset;
                } else {
                    this.mouseOffset = [ this.ghostOffset[0] + xy[0] - eo[0],
                                         this.ghostOffset[1] + xy[1] - eo[1] ];
                }

                if (!this.options.caption && this.options.constraint) {
                    // Because we later only set the left or top coordinates
                    // when using constraints, we have to set the correct
                    // "opposite" coordinates here.
                    po = this.ghost.getOffsetParent().cumulativeOffset();
                    switch (this.options.constraint) {
                    case 'horizontal':
                        this.ghost.setStyle({ top: (eo[1] - po[1]) + 'px' });
                        break;

                    case 'vertical':
                        this.ghost.setStyle({ left: (eo[0] - po[0]) + 'px' });
                        break;
                    }
                }
            }

            // Subtract the ghost's offset to the original mouse position and
            // add any scrolling.
            xy[0] -= this.mouseOffset[0];
            xy[1] -= this.mouseOffset[1];

            this._setContents(this.ghost, xy[0], xy[1]);
        }

        if (!this.options.nodrop) {
            this._onMoveDrag(xy, e);
        }

        this.wasDragged = true;

        this.element.fire('DragDrop2:drag', Object.clone(e));

        if (this.options.scroll) {
            this._onMoveScroll();
        }
    },

    _mouseUp: function(e)
    {
        var d = DragDrop.Drops.drop, tmp;

        this._stopScrolling();

        if (this.ghost) {
            if (!this.options.ghosting) {
                this.element.setStyle({ visibility: 'visible' });
            }
            try {
                this.ghost.remove();
            } catch (e) {}
            this.ghost = null;
        }

        DragDrop.Drags.div.hide();

        if (DragDrop.validDrop(this.element)) {
            this.dragevent = e;
            d.element.fire('DragDrop2:drop', this);
        }

        DragDrop.Drags.deactivate();

        if ((this.options.ghosting || this.options.caption) &&
            DragDrop.Drags.cover) {
            DragDrop.Drags.cover.down().siblings().invoke('remove');
        }

        if (!this.element.parentNode) {
            tmp = new Element('DIV').insert(this.element);
        }

        this.element.fire(this.wasMoved ? 'DragDrop2:end' : 'DragDrop2:mouseup', Object.clone(e));

        tmp = null;
    },

    _onMoveDrag: function(xy, e)
    {
        var d = DragDrop.Drops.drop,
            div = DragDrop.Drags.div,
            d_update = true,
            elt = this._findElement(e);

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
                /* Ignore if mouse is over an offset ghosted element. */
                if (elt == this.ghost) {
                    return;
                }

                elt = DragDrop.Drops.getDrop(elt);
                if (elt == d) {
                    d_update = false;
                } else {
                    elt.mouseOver(e);
                    d = elt;
                }
            } else if (d) {
                d.mouseOut(e);
                d = null;
            }
        }

        if (d_update) {
            this._updateCaption(d, div, e);
        }

        this._setCaption(div, xy);
    },

    _updateCaption: function(d, div, e)
    {
        var caption, cname, c_opt;

        if (d && DragDrop.validDrop(this.element)) {
            d_cap = d.options.caption;
            if (!d_cap) {
                return;
            }
            caption = Object.isFunction(d_cap) ? d_cap(d.element, this.element, e) : d_cap;
            if (caption && d.options.hoverclass) {
                cname = d.options.hoverclass;
            }
        }

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
    },

    _findElement: function(e)
    {
        var drop, x, y;

        if (this.options.caption ||
            (this.options.offset &&
             (this.options.offset.x > 0 || this.options.offset.y > 0))) {
            return e.element();
        }

        if (!DragDrop.Drops.drops.size()) {
            return;
        }

        Position.prepare();

        x = e.pointerX();
        y = e.pointerY();

        drop = DragDrop.Drops.drops.find(function(drop) {
            return Position.within(drop.value.element, x, y);
        });

        if (drop) {
            return drop.value.element;
        }
    },

    _keyPress: function(e)
    {
        if (DragDrop.Drops.drop &&
            DragDrop.Drops.drop.options.keypress) {
            this._updateCaption(DragDrop.Drops.drop, DragDrop.Drags.div, e);
        }
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

        var delta, p, speed, vp,
            s = this.options.scroll,
            dim = s.getDimensions();

        // No need to scroll if element is not current scrolling.
        if (s.scrollHeight == dim.height) {
            return;
        }

        delta = document.viewport.getScrollOffsets();
        p = s.viewportOffset(),
        speed = [ 0, 0 ];
        vp = document.viewport.getDimensions();

        p[0] += s.scrollLeft + delta.left;
        p[2] = p[0] + dim.width;

        // Only scroll if directly above/below element
        if (this.lastCoord[0] > p[2] ||
            this.lastCoord[0] < p[0]) {
            return;
        }

        p[1] = vp.height - dim.height;
        p[3] = vp.height - 10;

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
        var e_pos, vp, so, xy, style;

        if (this.options.offset) {
            x += this.options.offset.x;
            y += this.options.offset.y;
        }

        if (this.options.snapToParent) {
            if (x < 0) {
                x = 0;
            }
            if (y < 0) {
                y = 0;
            }
            if (x + this.dim.width > this.snap.width) {
                x = this.snap.width - this.dim.width;
            }
            if (y + this.dim.height > this.snap.height) {
                y = this.snap.height - this.dim.height;
            }
        } else if (this.options.snap) {
            xy = this.options.snap(x, y, this.element);
            x = xy[0];
            y = xy[1];
        } else {
            e_pos = elt.getDimensions();
            vp = document.viewport.getDimensions();
            so = document.viewport.getScrollOffsets();
            vp.width += so[0];
            vp.height += so[1];
            if (x + this.ghostOffset[0] < 0) {
                x = -this.ghostOffset[0];
            } else if (x + e_pos.width + this.ghostOffset[0] > vp.width) {
                x = vp.width - e_pos.width - this.ghostOffset[0];
            }
            if (y + this.ghostOffset[1] < 0) {
                y = -this.ghostOffset[1];
            } else if (y + e_pos.height + this.ghostOffset[1] > vp.height) {
                y = vp.height - e_pos.height - this.ghostOffset[1];
            }
        }

        if (!this.options.caption) {
            switch (this.options.constraint) {
            case 'horizontal':
                style = { left: x + 'px' };
                break;

            case 'vertical':
                style = { top: y + 'px' };
                break;

            default:
                style = { left: x + 'px', top: y + 'px' };
                break;
            }
        } else {
            style = { left: x + 'px', top: y + 'px' };
        }

        elt.setStyle(style).show();
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
            keypress: false
        }, arguments[1] || {});
        DragDrop.Drops.register(this);
    },

    destroy: function()
    {
        DragDrop.Drops.unregister(this);
    },

    mouseOver: function(e)
    {
        DragDrop.Drops.drop = this;
        DragDrop.Drags.drag.dragevent = e;
        this.element.fire('DragDrop2:over', DragDrop.Drags.drag);
    },

    mouseOut: function(e)
    {
        this.element.fire('DragDrop2:out', DragDrop.Drags.drag);
        DragDrop.Drags.drag.dragevent = e;
        DragDrop.Drops.drop = null;
    }

});
