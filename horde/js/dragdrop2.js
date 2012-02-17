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
 *     // Either string or function to set caption on mouse move.
 *     caption: '',
 *
 *     // Class name of the drag element.
 *     classname: 'drag',
 *
 *     // Constrain movement to 'horizontal' or 'vertical'.
 *     constraint: '',
 *
 *     // Show ghost outline when dragging.
 *     ghosting: false,
 *
 *     // Don't do drop checking. Optimizes movement speed.
 *     nodrop: false,
 *
 *     // An offset to apply to ghosted elements. Coordinates are the position
 *     // to display the element as measured from the upper-left corner of
 *     // the ghosted element. By default, the ghosted element is cloned under
 *     // the cursor.
 *     offset: { x:0, y:0 },
 *
 *     // Scroll this element when above/below (only for vertical elements).
 *     scroll: element,
 *
 *     // If ghosting, specifies the coords at which the ghosted image will
 *     // "snap" into place.
 *     snap: null,
 *
 *     // Keep image snapped inside the parent element. If true, uses
 *     // the parent element. If a function, uses return from function as the
 *     // parent element.
 *     snapToParent: false
 *
 *     // Move threshold.
 *     threshold: 0
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
 *     // Accept filter by tag name(s) or leave empty to accept all tags.
 *     accept: [],
 *
 *     // Either string or function to set caption on mouseover.
 *     caption: '',
 *
 *     // Change the drag element to this class when hovering over an element.
 *     hoverclass: 'dragdrop',
 *
 *     // If true, will re-render caption if a keypress is detected while a
 *     // drop is active (useful for CTRL/SHIFT combo actions).
 *     keypress: false
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
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde
 */

var DragDrop = {

    Drags: {

        drags: $H(),

        register: function(obj)
        {
            var func;

            if (!this.div) {
                /* Once-only initialization. */
                this.div = new Element('DIV', { className: obj.options.classname }).setStyle({ position: 'absolute' }).hide();
                $(document.body).insert(this.div);

                func = this._mouseHandler.bindAsEventListener(this);
                document.observe('mousedown', func);
                document.observe('mousemove', func);
                document.observe('mouseup', func);
                document.observe('keydown', func);
                document.observe('keyup', func);

                if (Prototype.Browser.IE) {
                    document.observe('selectstart', func);
                }
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
        },

        deactivate: function()
        {
            this.drag = DragDrop.Drops.drop = null;
        },

        _mouseHandler: function(e)
        {
            var elt;

            switch (e.type) {
            case 'keydown':
            case 'keyup':
                if (this.drag) {
                    this.drag._keyPress(e);
                }
                break;

            case 'mousedown':
                if (this.drags.size() &&
                    (elt = e.findElement('.DragElt'))) {
                    this.getDrag(elt).mouseDown(e);
                }
                break;

            case 'mousemove':
                if (this.drag) {
                    this.drag._mouseMove(e);
                }
                break;

            case 'mouseup':
                if (this.drag) {
                    this.drag._mouseUp(e);
                }
                break;

            case 'selectstart':
                if (this.drag) {
                    e.stop();
                }
                break;
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
        this.options = Object.extend({
            caption: '',
            classname: 'drag',
            constraint: null,
            ghosting: false,
            nodrop: false,
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
        if (Prototype.Browser.Gecko) {
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
        this.lastcaption = this.lastelt = null;
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

        // Stop event to prevent text selection. Gecko is handled in
        // initialize(); IE is handled by DragDrop selectstart event handler.
        if (!Prototype.Browser.IE && !Prototype.Browser.Gecko) {
            e.stop();
        }
    },

    _mouseMove: function(e)
    {
        var elt, layout, xy, z;

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
                layout = this.element.getLayout();
                elt = $(this.element.clone(true))
                    .writeAttribute('id', null)
                    .addClassName(this.options.classname)
                    .setStyle({ position: 'absolute', height: layout.get('height') + 'px', width: layout.get('width') + 'px' });

                if (this.options.ghosting) {
                    z = parseInt(this.element.getStyle('zIndex'), 10);
                    if (isNaN(z)) {
                        z = 1;
                    }
                    elt.setOpacity(0.7).setStyle({ zIndex: z + 1 });
                } else {
                    this.element.setStyle({ visibility: 'hidden' });
                }

                $(document.body).insert(elt);

                elt.clonePosition(this.element);

                this.ghost = this._prepareHover(elt, xy[0], xy[1], 'ghost');
            }

            this._position(this.ghost, xy[0], xy[1]);
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

    _prepareHover: function(elt, x, y, type)
    {
        var boundary, dim, noupdate, vo;

        if (this.options.snapToParent) {
            boundary = Object.isFunction(this.options.snapToParent)
                ? this.options.snapToParent()
                : this.element.parentNode;
            vo = boundary.viewportOffset();
        } else {
            boundary = document.viewport;
            vo = [ 0, 0 ];
        }

        if (this.options.offset) {
            pos = [
                x + this.options.offset.x,
                y + this.options.offset.y
            ];
        } else {
            switch (type) {
            case 'caption':
                pos = [ x + 15, y ];
                break;

            case 'ghost':
                pos = elt.viewportOffset();
                noupdate = true;
                break;
            }
        }

        if (this.ghost && type == 'caption') {
            pos[1] += this.ghost.height + 5;
        }

        if (!noupdate) {
            elt.setStyle({
                left: pos[0] + 'px',
                top: pos[1] + 'px'
            });
        }

        dim = boundary.getDimensions();
        layout = elt.getLayout();

        return {
            elt: elt,

            x_left: vo[0],
            x_right: vo[0] + dim.width,

            y_top: vo[1],
            y_bottom: vo[1] + dim.height,

            xy_left: x - pos[0],
            xy_top: y - pos[1],

            width: layout.get('margin-box-width'),
            height: layout.get('margin-box-height')
        };
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
                this.ghost.elt.remove();
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

        this.wasMoved = false;
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

        if (this.lastelt != elt) {
            this.lastelt = elt;

            /* Do mouseover/mouseout-like detection here. Saves on observe
             * calls and handles case where mouse moves over scrollbars. */
            if (DragDrop.Drops.drops.size()) {
                if (!elt.hasClassName('DropElt')) {
                    elt = elt.up('.DropElt');
                }

                if (elt) {
                    if (this.ghost && (elt == this.ghost.elt)) {
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
                this._updateCaption(d, div, e, e.pointerX(), e.pointerY());
            }
        }

        if (this.lastcaption) {
            this._position(this.caption, xy[0], xy[1]);
        }
    },

    _updateCaption: function(d, div, e, x, y)
    {
        var caption, cname, c_opt, vo;

        if (d && DragDrop.validDrop(this.element)) {
            d_cap = d.options.caption;
            if (!d_cap) {
                return;
            }
            caption = Object.isFunction(d_cap)
                ? d_cap(d.element, this.element, e)
                : d_cap;
            if (caption && d.options.hoverclass) {
                cname = d.options.hoverclass;
            }
        }

        if (!caption) {
            c_opt = this.options.caption;
            caption = Object.isFunction(c_opt)
                ? c_opt(this.element)
                : c_opt;
        }

        if (caption != this.lastcaption) {
            this.lastcaption = caption;
            if (caption.empty()) {
                div.hide();
            } else {
                div.update(caption).writeAttribute({
                    className: cname || this.options.classname
                });

                this.caption = this._prepareHover(div, x, y, 'caption');
            }
        }
    },

    _findElement: function(e)
    {
        var drop, x, y;

        if (this.options.caption ||
            (this.options.offset &&
             (this.options.offset.x > 0 ||
              this.options.offset.y > 0))) {
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
            this._updateCaption(DragDrop.Drops.drop, DragDrop.Drags.div, e, this.lastCoord[0], this.lastCoord[1]);
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

    _position: function(ob, x, y)
    {
        var xy, style;

        if (this.options.snap) {
            xy = this.options.snap(x, y, this.element);
            x = xy[0];
            y = xy[1];
        } else {
            x -= ob.xy_left;
            y -= ob.xy_top;

            if (x < ob.x_left) {
                x = ob.x_left;
            }
            if (y < ob.y_top) {
                y = ob.y_top;
            }

            if (x + ob.width > ob.x_right) {
                x = ob.x_right - ob.width;
            }
            if (y + ob.height > ob.y_bottom) {
                y = ob.y_bottom - ob.height;
            }
        }

        style = { left: x + 'px', top: y + 'px' };

        if (!this.options.caption) {
            switch (this.options.constraint) {
            case 'horizontal':
                delete style.top;
                break;

            case 'vertical':
                delete style.left;
                break;
            }
        }

        ob.elt.setStyle(style).show();
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
