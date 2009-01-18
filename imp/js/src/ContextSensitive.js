/**
 * ContextSensitive: a library for generating context-sensitive content on
 * HTML elements. It will take over the click/oncontextmenu functions for the
 * document, and works only where these are possible to override.  It allows
 * contextmenus to be created via both a left and right mouse click.
 *
 * Requires prototypejs 1.6+ and scriptaculous 1.8+ (effects.js only).
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
        this.current = this.lasttarget = this.target = null;
        this.elements = $H();

        document.observe('contextmenu', this.rightClickHandler.bindAsEventListener(this));
        document.observe('click', this.leftClickHandler.bindAsEventListener(this));
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
     * Hide the current element.
     */
    close: function(immediate)
    {
        if (this.current) {
            if (immediate) {
                this.current.hide();
            } else {
                Effect.Fade(this.current, { duration: 0.2 });
            }
            this.current = this.target = null;
        }
    },

    /**
     * Get the element that triggered the current context menu (if any).
     */
    element: function(current)
    {
        return current ? this.target : this.lasttarget;
    },

    /**
     * Returns the current displayed menu element, if any.
     */
    currentmenu: function()
    {
        if (this.current && this.current.visible()) {
            return this.current;
        }
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
    leftClickHandler: function(e)
    {
        // Check for a right click. FF on Linux triggers an onclick event even
        // w/a right click, so disregard.
        if (e.isRightClick()) {
            return;
        }

        // Check if the mouseclick is registered to an element now.
        this.rightClickHandler(e, true);
    },

    /**
     * Called when a right click event occurs.
     */
    rightClickHandler: function(e, left)
    {
        if (this.trigger(e.element(), left, e.pointerX(), e.pointerY())) {
            e.stop();
        };
    },

    /**
     * Display context menu if valid element has been activated.
     */
    trigger: function(target, leftclick, x, y)
    {
        var ctx, el, offset, offsets, size, v, voffsets;

        [ target ].concat(target.ancestors()).find(function(n) {
            ctx = this.validElement(n.id, leftclick);
            return ctx;
        }, this);

        // Return if event not found or event is disabled.
        if (!ctx || ctx.disable) {
            this.close();
            return false;
        }

        // Try to retrieve the context-sensitive element we want to
        // display. If we can't find it we just return.
        el = $(ctx.ctx);
        if (!el) {
            this.close();
            return false;
        } else if (leftclick && el == this.current) {
            return false;
        }

        // Register the current element that will be shown and the
        // element that was clicked on.
        this.current = el;
        this.lasttarget = this.target = $(ctx.id);

        // Get the base element positions.
        offset = ctx.opts.offset;
        if (!offset && (Object.isUndefined(x) || Object.isUndefined(y))) {
            offset = target.id;
        }
        offset = $(offset);

        if (offset) {
            offsets = offset.viewportOffset();
            voffsets = document.viewport.getScrollOffsets();
            x = offsets[0] + voffsets.left;
            y = offsets[1] + offset.getHeight() + voffsets.top;
        }

        // Get window/element dimensions
        v = document.viewport.getDimensions();
        size = el.getDimensions();

        // Make sure context window is entirely on screen
        if ((y + size.height) > v.height) {
            y = v.height - size.height - 10;
        }
        if ((x + size.width) > v.width) {
            x = v.width - size.width - 10;
        }

        if (ctx.opts.onShow) {
            ctx.opts.onShow(ctx);
        }

        Effect.Appear(el.setStyle({ left: x + 'px', top: y + 'px' }), { duration: 0.2 });

        return true;
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
        this.disable = false;
    }

});
