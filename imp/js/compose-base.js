/**
 * compose-base.js - Provides basic compose javascript functions shared
 * between standarad and dynamic displays.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

var ImpComposeBase = {

    // Vars defaulting to null: editor_on, identities, rte, rte_loade

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
                (function() { input.scrollTop = input.scrollHeight - input.offsetHeight; }).defer();
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

    setSignature: function(identity)
    {
        var config, s = $('signature');

        if (!s) {
            return;
        }

        if (this.editor_on) {
            s.removeClassName('fixed')
                .update(identity.hsig);

            if (Object.isUndefined(this.rte_loaded)) {
                CKEDITOR.on('instanceReady', function(evt) {
                    this.rte_loaded = true;
                }.bind(this));
                CKEDITOR.on('instanceDestroyed', function(evt) {
                    this.rte_loaded = false;
                }.bind(this));
            }

            config = Object.clone(IMP.ckeditor_config);
            config.removePlugins = 'toolbar,elementspath';
            config.contentsCss = [ CKEDITOR.basePath + 'contents.css', CKEDITOR.basePath + 'nomargin.css' ];
            config.height = $('signatureBorder').getLayout().get('height');
            this.rte = CKEDITOR.replace('signature', config);
        } else {
            if (this.rte) {
                this.rte.destroy(true);
                delete this.rte;
            }
            s.addClassName('fixed')
                .update(identity.sig);
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
    },

    focus: function(elt)
    {
        elt = $(elt);
        elt.focus();
        $(document).fire('AutoComplete:focus', elt);
    },

    autocompleteValue: function(ob, val)
    {
        var pos = 0,
            chr, in_group, in_quote, tmp;

        chr = val.charAt(pos);
        while (chr !== "") {
            var orig_pos = pos;
            ++pos;

            if (!orig_pos || (val.charAt(orig_pos - 1) != '\\')) {
                switch (chr) {
                case ',':
                    if (!orig_pos) {
                        val = val.substr(1);
                    } else if (!in_group && !in_quote) {
                        ob.addNewItem(val.substr(0, orig_pos));
                        val = val.substr(orig_pos + 2);
                        pos = 0;
                    }
                    break;

                case '"':
                    in_quote = !in_quote;
                    break;

                case ':':
                    if (!in_quote) {
                        in_group = true;
                    }
                    break;

                case ';':
                    if (!in_quote) {
                        in_group = false;
                    }
                    break;
                }
            }

            chr = val.charAt(pos);
        }

        return val;
    },

    autocompleteHandlers: function()
    {
        var handlers = {};
        $(document).fire('AutoComplete:handlers', handlers);
        return $H(handlers);
    },

    autocompleteProcess: function(r)
    {
        this.autocompleteHandlers().each(function(pair) {
            var ob = $H(pair.value.toObject(true));
            ob.values().each(function(v) {
                v.className = pair.value.p.listClassItem;
            });

            $H(r[pair.key] || {}).each(function(pair2) {
                $w(pair2.value).each(function(c) {
                    ob.get(pair2.key).addClassName(c);
                });
            });
        });
    },

    sendParams: function(params, ac)
    {
        var out = [];
        params = $H(params);

        if (ac) {
            this.autocompleteHandlers().each(function(pair) {
                $H(pair.value.toObject()).each(function(pair2) {
                    out.push({
                        addr: pair2.value,
                        id: pair.key,
                        itemid: pair2.key
                    });
                });
            });
            params.set('addr_ac', Object.toJSON(out));
        }

        return params;
    },

    tasksHandler: function(e)
    {
        var t = e.tasks || {};

        if (t['imp:compose-addr']) {
            this.autocompleteProcess(t['imp:compose-addr']);
        }
    }

};

/* Catch tasks. */
document.observe('HordeCore:runTasks', function(e) {
    ImpComposeBase.tasksHandler(e.memo);
});
