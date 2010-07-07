/**
 * RedBox - display a non-modal dialog box.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

var RedBox = {

    overlay: true,
    duration: 0.4,
    onDisplay: null,

    showInline: function(id)
    {
        this.appearWindow();
        this.cloneWindowContents(id);
    },

    showHtml: function(html)
    {
        this.appearWindow();
        this.htmlWindowContents(html);
    },

    appearWindow: function()
    {
        var loading = $('RB_loading'),
            opts = { queue: 'end', duration: this.duration },
            effects = [], effect;

        if (loading && loading.visible()) {
            loading.hide();
        } else {
            effect = this.showOverlay(true);
            if (effect) {
                effects.push(effect);
            }
        }

        if (this.onDisplay) {
            opts.afterFinish = this.onDisplay;
        }

        effects.push(new Effect.Appear($('RB_window'), { sync: true, duration: this.duration }));
        new Effect.Parallel(effects, opts);
        $('RB_window').scrollTo();
    },

    loading: function()
    {
        var rl = $('RB_loading');

        this.showOverlay();
        if (rl) {
            rl.show();
        }
        this.setWindowPosition();
    },

    close: function()
    {
        $('RB_window').fade({ duration: this.duration });
        if (this.overlay && this.overlayVisible()) {
            $('RB_overlay').fade({ duration: this.duration });
        }
    },

    showOverlay: function(sync)
    {
        var rb = $('RB_redbox'), ov;

        if (!rb) {
            rb = new Element('DIV', { id: 'RB_redbox', align: 'center' });
            $(document.body).insert(rb);

            ov = new Element('DIV', { id: 'RB_overlay' }).hide();
            rb.insert({ top: new Element('DIV', { id: 'RB_window' }).hide() }).insert({ top: ov });

            if (this.overlay) {
                ov.insert({ top: new Element('DIV', { id: 'RB_loading' }).hide() });
            }
        }

        if (this.overlay) {
            this.setOverlaySize();
            return new Effect.Appear($('RB_overlay'), { sync: sync, duration: this.duration, to: 0.6, queue: sync ? 'parallel' : 'end' });
        }
    },

    setOverlaySize: function()
    {
        var yScroll;

        if (window.innerHeight && window.scrollMaxY) {
            yScroll = window.innerHeight + window.scrollMaxY;
        } else if (document.body.scrollHeight > document.body.offsetHeight) {
            // all but Explorer Mac
            yScroll = document.body.scrollHeight;
        } else {
            // Explorer Mac...would also work in Explorer 6 Strict, Mozilla
            // and Safari
            yScroll = document.body.offsetHeight;
        }

        $('RB_overlay').setStyle({ height: yScroll + 'px' });
    },

    setWindowPosition: function()
    {
        var win = $('RB_window'),
            d = win.getDimensions(),
            v = document.viewport.getDimensions();
        win.setStyle({ width: 'auto', height: 'auto', left: ((v.width - d.width) / 2) + 'px', top: ((v.height - d.height) / 2) + 'px' });
    },

    cloneWindowContents: function(id)
    {
        $('RB_window').appendChild($($(id).clone(true)).setStyle({ display: 'block' }));
        this.setWindowPosition();
    },

    htmlWindowContents: function(html)
    {
        $('RB_window').update(html);
        this.setWindowPosition();
    },

    getWindow: function()
    {
        return $('RB_window');
    },

    getWindowContents: function()
    {
        var w = $('RB_window');
        return w.visible() ? w.down() : null;
    },

    overlayVisible: function()
    {
        var ov = $('RB_overlay');
        return ov && ov.visible();
    }

}
