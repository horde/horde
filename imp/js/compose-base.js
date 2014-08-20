/**
 * Provides basic compose code shared between standard and dynamic displays.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @copyright  2014 Horde LLC
 * @license    GPL-2 (http://www.horde.org/licenses/gpl)
 */

var ImpComposeBase = {

    // Vars defaulting to null: editor_on, identities, rte_sig

    getSpellChecker: function()
    {
        return (HordeImple.SpellChecker && HordeImple.SpellChecker.spellcheck)
            ? HordeImple.SpellChecker.spellcheck
            : null;
    },

    setCursorPosition: function(input, type)
    {
        var pos, range;

        if (!(input = $(input))) {
            return;
        }

        switch (type) {
        case 'top':
            pos = 0;
            input.setValue('\n' + $F(input));
            break;

        case 'bottom':
            pos = $F(input).length;
            break;

        default:
            return;
        }

        if (input.setSelectionRange) {
            /* This works in Mozilla. */
            Field.focus(input);
            input.setSelectionRange(pos, pos);
            if (pos) {
                (function() { input.scrollTop = input.scrollHeight - input.offsetHeight; }).delay(0.1);
            }
        } else if (input.createTextRange) {
            /* This works in IE */
            range = input.createTextRange();
            range.collapse(true);
            range.moveStart('character', pos);
            range.moveEnd('character', 0);
            Field.select(range);
            range.scrollIntoView(true);
        }
    },

    setSignature: function(rte, identity)
    {
        var config, s = $('signature');

        if (!s) {
            return;
        }

        if (rte) {
            s.setValue(Object.isString(identity) ? identity : identity.hsig);

            if (this.rte_sig) {
                this.rte_sig.setData($F('signature'));
            } else {
                config = Object.clone(IMP.ckeditor_config);
                config.removePlugins = 'toolbar,elementspath';
                config.contentsCss = [ CKEDITOR.basePath + 'contents.css', CKEDITOR.basePath + 'nomargin.css' ];
                config.height = ($('signatureBorder') ? $('signatureBorder') : $('signature')).getLayout().get('height');
                this.rte_sig = new IMP_Editor('signature', config);
            }
        } else {
            if (this.rte_sig) {
                this.rte_sig.destroy();
                delete this.rte_sig;
            }
            s.setValue(Object.isString(identity) ? identity : identity.sig);
        }
    },

    updateAddressField: function(e)
    {
        var elt = $(e.memo.field),
            v = $F(elt).strip(),
            pos = v.lastIndexOf(',');

        if (v.empty()) {
            v = '';
        } else if (pos != (v.length - 1)) {
            v += ', ';
        } else {
            v += ' ';
        }

        elt.setValue(v + e.memo.value + ', ');
        document.fire('AutoComplete:reset');
    },

    focus: function(elt)
    {
        elt = $(elt);
        try {
            // IE 8 requires try/catch to silence a warning.
            elt.focus();
        } catch (e) {}
        $(document).fire('AutoComplete:focus', elt);
    }

};
