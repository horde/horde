/**
 * ContextSensitive: a library for generating context-sensitive content on
 * HTML elements. It will take over the click/oncontextmenu functions for the
 * document, and works only where these are possible to override.  It allows
 * contextmenus to be created via both a left and right mouse click.
 *
 * On Opera, the context menu is triggered by a left click + SHIFT + CTRL
 * combination.
 *
 * Requires prototypejs 1.6+ and scriptaculous 1.8+ (effects.js only).
 *
 *
 * Usage:
 * ------
 * cs = new ContextSensitive();
 *
 * Custom Events:
 * --------------
 * Custom events are triggered on the base element. The parameters given
 * below are available through the 'memo' property of the Event object.
 *
 * ContextSensitive:click
 *   Fired when a contextmenu element is clicked on.
 *   params: (object) elt - (Element) The menu element clicked on.
 *                    trigger - (string) The parent menu.
 *
 * ContextSensitive:show
 *   Fired before a contextmenu is displayed.
 *   params: (string) The DOM ID of the context menu.
 *
 *
 * Original code by Havard Eide (http://eide.org/) released under the MIT
 * license.
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
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Michael Slusarz <slusarz@horde.org>
 */

var ContextSensitive = Class.create({

    initialize: function()
    {
        this.baseelt = null;
        this.current = [];
        this.elements = $H();
        this.submenus = $H();
        this.triggers = [];

        if (!Prototype.Browser.Opera) {
            document.observe('contextmenu', this._rightClickHandler.bindAsEventListener(this));
        }
        document.observe('click', this._leftClickHandler.bindAsEventListener(this));
        document.observe(Prototype.Browser.Gecko ? 'DOMMouseScroll' : 'mousescroll', this.close.bind(this));
    },

    /**
     * Elements are of type ContextSensitive.Element.
     */
    addElement: function(id, target, opts)
    {
        var left = Boolean(opts.left);
        if (id && !this.validElement(id, left)) {
            this.elements.set(id + Number(left), new ContextSensitive.Element(id, target, opts));
        }
    },

    /**
     * Remove a registered element.
     */
    removeElement: function(id)
    {
        this.elements.unset(id + '0');
        this.elements.unset(id + '1');
    },

    /**
     * Hide the currently displayed element(s).
     */
    close: function()
    {
        this._closeMenu(0, true);
    },

    /**
     * Close all menus below a specified level.
     */
    _closeMenu: function(idx, immediate)
    {
        if (this.current.size()) {
            this.current.splice(idx, this.current.size() - idx).each(function(s) {
                // Fade-out on final display.
                if (!immediate && idx == 0) {
                    s.fade({ duration: 0.15 });
                } else {
                    $(s).hide();
                }
            });

            this.triggers.splice(idx, this.triggers.size() - idx).each(function(s) {
                $(s).removeClassName('contextHover');
            });

            if (idx == 0) {
                this.baseelt = null;
            }
        }
    },

    /**
     * Returns the current displayed menu element ID, if any. If more than one
     * submenu is open, returns the last ID opened.
     */
    currentmenu: function()
    {
        return this.current.last();
    },

    /**
     * Get a valid element (the ones that can be right-clicked) based
     * on a element ID.
     */
    validElement: function(id, left)
    {
        return this.elements.get(id + Number(Boolean(left)));
    },

    /**
     * Set the disabled flag of an event.
     */
    disable: function(id, left, disable)
    {
        var e = this.validElement(id, left);
        if (e) {
            e.disable = disable;
        }
    },

    /**
     * Called when a left click event occurs. Will return before the
     * element is closed if we click on an element inside of it.
     */
    _leftClickHandler: function(e)
    {
        var base, elt, elt_up, trigger;

        if (this.operaCheck(e)) {
            this._rightClickHandler(e, false);
            e.stop();
            return;
        }

        // Check for a right click. FF on Linux triggers an onclick event even
        // w/a right click, so disregard.
        if (e.isRightClick()) {
            return;
        }

        // Check for click in open contextmenu.
        if (this.current.size()) {
            elt = e.element();
            if (!elt.match('A')) {
                elt = elt.up('A');
                if (!elt) {
                    this._rightClickHandler(e, true);
                    return;
                }
            }
            elt_up = elt.up('.contextMenu');

            if (elt_up) {
                e.stop();

                if (elt.hasClassName('contextSubmenu') &&
                    elt_up.identify() != this.currentmenu()) {
                    this._closeMenu(this.current.indexOf(elt.identify()));
                }

                base = this.baseelt;
                trigger = this.triggers.last();
                this.close();
                base.fire('ContextSensitive:click', { elt: elt, trigger: trigger });
                return;
            }
        }

        // Check if the mouseclick is registered to an element now.
        this._rightClickHandler(e, true);
    },

    /**
     * Checks if the Opera right-click emulation is present.
     */
    operaCheck: function(e)
    {
        return Prototype.Browser.Opera && e.shiftKey && e.ctrlKey;
    },

    /**
     * Called when a right click event occurs.
     */
    _rightClickHandler: function(e, left)
    {
        if (this.trigger(e.element(), left, e.pointerX(), e.pointerY())) {
            e.stop();
        }
    },

    /**
     * Display context menu if valid element has been activated.
     */
    trigger: function(target, leftclick, x, y)
    {
        var ctx, el, offset, offsets, voffsets;

        [ target ].concat(target.ancestors()).find(function(n) {
            ctx = this.validElement(n.id, leftclick);
            return ctx;
        }, this);

        // Try to retrieve the context-sensitive element we want to
        // display. If we can't find it we just return.
        if (!ctx ||
            ctx.disable ||
            !(el = $(ctx.ctx)) ||
            (leftclick && target == this.baseelt) ||
            this.currentmenu() == ctx.ctx) {
            this.close();
            return false;
        }

        this.close();

        // Register the element that was clicked on.
        this.baseelt = target;

        offset = ctx.opts.offset;
        if (!offset && (Object.isUndefined(x) || Object.isUndefined(y))) {
            offset = target.identify();
        }
        offset = $(offset);

        if (offset) {
            offsets = offset.viewportOffset();
            voffsets = document.viewport.getScrollOffsets();
            x = offsets[0] + voffsets.left;
            y = offsets[1] + offset.getHeight() + voffsets.top;
        }

        this._displayMenu(el, x, y);
        this.triggers.push(el.identify());

        return true;
    },

    /**
     * Display the [sub]menu on the screen.
     */
    _displayMenu: function(elt, x, y)
    {
        // Get window/element dimensions
        var eltL, h, w,
            id = elt.identify(),
            v = document.viewport.getDimensions();

        elt.setStyle({ visibility: 'hidden' }).show();
        eltL = elt.getLayout(),
        h = eltL.get('border-box-height');
        w = eltL.get('border-box-width');
        elt.hide().setStyle({ visibility: 'visible' });

        // Make sure context window is entirely on screen
        if ((y + h) > v.height) {
            y = v.height - h - 2;
        }

        if ((x + w) > v.width) {
            x = this.current.size()
                ? ($(this.current.last()).viewportOffset()[0] - w)
                : (v.width - w - 2);
        }

        this.baseelt.fire('ContextSensitive:show', id);

        elt.setStyle({ left: x + 'px', top: y + 'px' })

        if (this.current.size()) {
            elt.show();
        } else {
            // Fade-in on initial display.
            elt.appear({ duration: 0.15 });
        }

        this.current.push(id);
    },

    /**
     * Add a submenu to an existing menu.
     */
    addSubMenu: function(id, submenu)
    {
        if (!this.submenus.get(id)) {
            if (!this.submenus.size()) {
                document.observe('mouseover', this._mouseoverHandler.bindAsEventListener(this));
            }
            this.submenus.set(id, submenu);
            $(submenu).addClassName('contextMenu');
            $(id).addClassName('contextSubmenu');
        }
    },

    /**
     * Mouseover DOM Event handler.
     */
    _mouseoverHandler: function(e)
    {
        if (!this.current.size()) {
            return;
        }

        var cm = this.currentmenu(),
            elt = e.element(),
            elt_up = elt.up('.contextMenu'),
            id = elt.identify(),
            id_div, offsets, sub, voffsets, x, y;

        if (!elt_up) {
            return;
        }

        id_div = elt_up.identify();

        if (elt.hasClassName('contextSubmenu')) {
            sub = this.submenus.get(id);
            if (sub != cm || this.currentmenu() != id) {
                if (id_div != cm) {
                    this._closeMenu(this.current.indexOf(id_div) + 1);
                }

                offsets = elt.viewportOffset();
                voffsets = document.viewport.getScrollOffsets();
                x = offsets[0] + voffsets.left + elt.getWidth();
                y = offsets[1] + voffsets.top;
                this._displayMenu($(sub), x, y, id);
                this.triggers.push(id);
                elt.addClassName('contextHover');
            }
        } else if ((this.current.size() > 1) &&
                   id_div != cm) {
            this._closeMenu(this.current.indexOf(id));
        }
    }

});

ContextSensitive.Element = Class.create({

    // opts: 'left' -> monitor left click; 'offset' -> id of element used to
    //       determine offset placement
    initialize: function(id, target, opts)
    {
        this.id = id;
        this.ctx = target;
        this.opts = opts;
        this.opts.left = Boolean(opts.left);
        this.disable = opts.disable;

        target = $(target);
        if (target) {
            target.addClassName('contextMenu');
        }
    }

});
