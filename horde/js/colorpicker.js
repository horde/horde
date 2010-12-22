/**
 * ColorPicker object.
 *
 * Original Sphere Plugin v0.1/v0.3, Design/Programming by Ulyses, (c) 2007
 * ColorJack.com, IE fixes by Hamish.
 *
 * Rewritten to utilize Prototype for Horde by Chuck Hagenbuch,
 * chuck@horde.org.
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

var ColorPicker = Class.create({

    initialize: function(options)
    {
        if (options.color) {
            options.color = Color.normalizehex(options.color);
        }

        this.options = Object.extend({
            color: '#ffffff',
            update: [],
            draggable: false,
            resizable: false,
            offsetParent: null,
            offset: 10
        }, options || {})

        this.stop = 1;
        this.zIndex = 1000;
        this.hsv = Color.hex2hsv(this.options.color);

        var div = $('color-picker'),
            xy;

        if (!div) {
            div = new Element('DIV', { id: 'color-picker' }).hide();
            div.insert(
                new Element('DIV', { className: 'north' }).insert(
                    new Element('FORM', { id: 'color-picker-hex-form' }).insert(
                        new Element('SPAN', { id: 'color-picker-hex' })
                    ).insert(
                        new Element('INPUT', { id: 'color-picker-hex-edit', size: 6 }).hide()
                    )
                ).insert(
                    new Element('DIV', { id: 'color-picker-close' }).insert('X')
                )
            ).insert(
                new Element('DIV', { className: 'south', id: 'color-picker-sphere' }).insert(
                    new Element('DIV', { id: 'color-picker-cursor' })
                ).insert(
                    new Element('IMG', { id: 'color-picker-palette' })
                ).insert(
                    new Element('IMG', { id: 'color-picker-resize' })
                )
            );

            if (this.options.draggable) {
                div.setStyle({ cursor: 'move' });
            }

            $(document.body).insert(div);

            $('color-picker-palette', 'color-picker-resize').each(function(c) {
                var d = new Element('DIV', { className: c.readAttribute('id') + '-src' }).hide();
                $(document.body).insert(d);
                c.writeAttribute('src', d.getStyle('backgroundImage').replace(/url\("?(.*?)"?\)/, '$1'));
                c.observe('drag', Event.stop).observe('selectstart', Event.stop);
                d.remove();
            });

            if (!this.iefix && Prototype.Browser.IE) {
                this.iefix = new Element('IFRAME', { id: 'color-picker-iefix', src: 'javascript:false;', frameborder: 0, scrolling: 'no' }).hide().setStyle({ position: 'absolute', filter: 'progid:DXImageTransform.Microsoft.Alpha(opacity=0);' });
                div.insert({ after: this.iefix });
            }
        }

        xy = this.options.offsetParent
            ? this.options.offsetParent.cumulativeOffset()
            : [ 0, 0 ];

        div.setStyle({
            position: 'absolute',
            left: xy[0] + this.options.offset + 'px',
            top: xy[1] + this.options.offset + 'px'
        }).show();

        if (this.iefix) {
            this.ietimeout = setTimeout(this.fixIEOverlapping.bind(this), 50);
        }

        // Init based on passed-in color.
        $('color-picker-hex').update(this.options.color);

        // Set cursor to initial position.
        this.coords(parseInt($('color-picker-sphere').getStyle('width')));

        if (!this.options.resizable) {
            $('color-picker-resize').hide();
        }

        this.addEvents();
    },

    fixIEOverlapping: function()
    {
        this.iefix.clone('color-picker', { setTop: (!$('color-picker').getStyle('height')) }).setStyle({ zIndex: $('color-picker').getStyle('zIndex') - 1 }).show();
    },

    hide: function()
    {
        this.removeEvents();
        $('color-picker').hide();
        if (this.iefix) {
            this.iefix.hide();
            clearTimeout(this.ietimeout);
        }
    },

    addEvents: function()
    {
        if (this.listeners) {
            return;
        }

        this.listeners = [
            [ 'color-picker-close', 'click', this.hide.bindAsEventListener(this) ],
            [ 'color-picker-sphere', 'mousedown', this.coreXY.bindAsEventListener(this, 'color-picker-cursor') ],
            [ 'color-picker-sphere', 'dblclick', this.hide.bindAsEventListener(this) ],
            [ 'color-picker-hex', 'click', this.editHex.bindAsEventListener(this) ],
            [ $('color-picker-hex').up('FORM'), 'submit', this.editHexSubmit.bindAsEventListener(this) ]
        ];

        if (this.options.draggable) {
            this.listeners.push([ 'color-picker', 'mousedown', this.coreXY.bindAsEventListener(this, 'color-picker') ]);
        }

        if (this.options.resizable) {
            this.listeners.push([ 'color-picker-resize', 'mousedown', this.coreXY.bindAsEventListener(this, 'color-picker-resize') ]);
        }

        this.listeners.each(function(l) {
            $(l[0]).observe(l[1], l[2]);
        });
    },

    removeEvents: function()
    {
        if (!this.listeners) {
            return;
        }

        this.listeners.each(function(l) {
            $(l[0]).stopObserving(l[1], l[2]);
        });

        this.listeners = null;
    },

    coords: function(W)
    {
        var W2 = W / 2,
            rad = (this.hsv[0] / 360) * (Math.PI * 2),
            hyp = (this.hsv[1] + (100 - this.hsv[2])) / 100 * (W2 / 2);

        $('color-picker-cursor').setStyle({
            left: Math.round(Math.abs(Math.round(Math.sin(rad) * hyp) + W2 + 3)) + 'px',
            top: Math.round(Math.abs(Math.round(Math.cos(rad) * hyp) - W2 - 21)) + 'px'
        });
    },

    point: function(o, a, b, e, oH)
    {
        this.commit(o, [ e.pointerX() + a, e.pointerY() + b ], oH);
    },

    commit: function(o, v, oH)
    {
        if (o == 'color-picker-cursor') {
            var W = parseInt($('color-picker-sphere').getStyle('width')),
                W2 = W / 2,
                W3 = W2 / 2,
                x = v[0] - W2 - 3,
                y = W - v[1] - W2 + 21,
                SV = Math.sqrt(Math.pow(x, 2) + Math.pow(y, 2)),
                hue = Math.atan2(x, y) / (Math.PI * 2);

            this.hsv = [
                hue > 0 ? (hue * 360) : ((hue * 360) + 360),
                SV < W3 ? (SV / W3) * 100 : 100,
                SV >= W3 ? Math.max(0, 1 - ((SV - W3) / (W2 - W3))) * 100 : 100
            ];

            this.updateColor();

            this.coords(W);
        } else if (o == 'color-picker-resize') {
            var b = Math.max(Math.max(v[0], v[1]) + oH, 75);
            this.coords(b);

            $('color-picker').setStyle({ height: (b + 28) + 'px', width: (b + 20) + 'px' });
            $('color-picker-sphere').setStyle({ height: b + 'px', width: b + 'px' });
        } else {
            $(o).setStyle({ left: v[0] + 'px', top: v[1] + 'px' });
        }
    },

    coreXY: function(e, o)
    {
        if (o == 'color-picker') {
            if (e.element().up('#color-picker-hex-form') &&
                !$('color-picker-hex').visible()) {
                return;
            }
        } else {
            e.stop();
        }

        if (!this.stop) {
            return;
        }

        var ab, oX, oY, oH;

        this.stop = '';
        $(o).setStyle({ zIndex: this.zIndex++ }, true);

        if (o == 'color-picker-cursor') {
            ab = $(o).up().cumulativeOffset();
            this.point(o, -(ab[0] - 5), -(ab[1] - 28), e);
        }

        if (o == 'color-picker-resize') {
            oX = -(e.pointerX()),
            oY = -(e.pointerY()),
            oH = parseInt($('color-picker-sphere').getStyle('height'));
        } else {
            oX = parseInt($(o).getStyle('left')) - e.pointerX(),
            oY = parseInt($(o).getStyle('top')) - e.pointerY(),
            oH = null;
        }

        document.observe('mousemove', function(e, o, oX, oY, oH) {
            if (!this.stop) {
                this.point(o, oX, oY, e, oH);
            }
        }.bindAsEventListener(this, o, oX, oY, oH));

        document.observe('mouseup', function() {
            this.stop = 1;
            document.stopObserving('mousemove').stopObserving('mouseup');
        }.bind(this));
    },

    updateColor: function()
    {
        var brightness = Color.brightness(Color.hsv2rgb(this.hsv)),
            c = Color.hsv2hex(this.hsv);

        $('color-picker-hex').update('#' + c);

        this.options.update.each(function(u) {
            switch (u[1]) {
            case 'background':
                $(u[0]).setStyle({
                    backgroundColor: '#' + c,
                    color: (brightness < 125 ? '#fff' : '#000')
                });
                break;

            case 'value':
                $(u[0]).value = '#' + c;
                break;
            }
        });
    },

    editHex: function(e)
    {
        e.element().hide().next().setValue('#' + Color.hsv2hex(this.hsv)).show().focus();
    },

    editHexSubmit: function(e)
    {
        var hex = $F(e.element().down('INPUT'));
        $('color-picker-hex').update(hex);
        this.hsv = Color.hex2hsv(hex);
        this.coords(parseInt($('color-picker-sphere').getStyle('width')));
        this.updateColor();
        e.element().down().show().next().hide();
        e.stop();
    }

});

/**
 * Color utility class
 */
var Color = {

    normalizehex: function(h)
    {
        if (h.substring(0, 1) == '#') {
            h = h.substring(1);
        }

        if (h.length == 3) {
            h = h.charAt(0).times(2) +
                h.charAt(1).times(2) +
                h.charAt(2).times(2);
        }

        return '#' + h;
    },

    hsv2hex: function(h)
    {
        return Color.rgb2hex(Color.hsv2rgb(h));
    },

    hex2hsv: function(h)
    {
        return Color.rgb2hsv(Color.hex2rgb(h));
    },

    hex2rgb: function(hex)
    {
        if (hex.substring(0, 1) == '#') {
            hex = hex.substring(1);
        }

        return [
            parseInt(hex.substring(0, 2), 16),
            parseInt(hex.substring(2, 4), 16),
            parseInt(hex.substring(4, 6), 16)
        ];
    },

    rgb2hex: function(rgb)
    {
        var r = rgb[0].toString(16),
            g = rgb[1].toString(16),
            b = rgb[2].toString(16);

        return (r.length == 2 ? r : '0' + r) +
               (g.length == 2 ? g : '0' + g) +
               (b.length == 2 ? b : '0' + b);
    },

    /**
     * http://easyrgb.com/math.php?MATH=M21#text21
     */
    hsv2rgb: function(r)
    {
        var F, A, C, R, B, G,
            S = r[1] / 100,
            V = r[2] / 100,
            H = r[0] / 360;

        if (S > 0) {
            if (H >= 1) {
                H = 0;
            }

            H = 6 * H;
            F = H - Math.floor(H);
            A = Math.round(255 * V * (1.0 - S));
            B = Math.round(255 * V * (1.0 - (S * F)));
            C = Math.round(255 * V * (1.0 - (S * (1.0 - F))));
            V = Math.round(255 * V);

            switch (Math.floor(H)) {
            case 0:
                R = V;
                G = C;
                B = A;
                break;

            case 1:
                R = B;
                G = V;
                B = A;
                break;

            case 2:
                R = A;
                G = V;
                B = C;
                break;

            case 3:
                R = A;
                G = B;
                B = V;
                break;

            case 4:
                R = C;
                G = A;
                B = V;
                break;

            case 5:
                R = V;
                G = A;
                //B = B;
                break;
            }

            return [ R ? R : 0, G ? G : 0, B ? B : 0 ];
        }

        return [ (V = Math.round(V * 255)), V, V ];
    },

    /**
     * http://easyrgb.com/math.php?MATH=M21#text21
     */
    rgb2hsv: function(r)
    {
        var max = Math.max(r[0], r[1], r[2]),
            delta = max - Math.min(r[0], r[1], r[2]),
            H, S;

        if (max != 0) {
            S = Math.round(delta / max * 100);

            if (r[0] == max) {
                H = (r[1] - r[2]) / delta;
            } else if (r[1] == max) {
                H = 2 + (r[2] - r[0]) / delta;
            } else if (r[2] == max) {
                H = 4 + (r[0] - r[1]) / delta;
            }

            H = Math.min(Math.round(H * 60), 360);
            if (H < 0) {
                H += 360;
            }
        }

        return [ H ? H : 0, S ? S : 0, (max / 2.55) ];

    },

    brightness: function(rgb)
    {
        return Math.round((rgb[0] * 299 + rgb[1] * 587 + rgb[2] * 114) / 1000);
    }

}
