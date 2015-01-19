/**
 * Contacts page.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @copyright  2014-2015 Horde LLC
 * @license    GPL-2 (http://www.horde.org/licenses/gpl)
 */

var ImpContacts = {

    _passAddresses: function()
    {
        var sa = '';

        $('selected_addresses').childElements().each(function(s) {
            if (s.value) {
                sa += s.value + '|';
            }
        });

        $('sa').setValue(sa);
    },

    sameOption: function(f, item, itemj)
    {
        var t = f + ": " + item.value,
            tj = itemj.value;

        return Try.these(
            function() {
                return (t == tj) || (decodeURIComponent(t) == decodeURIComponent(tj));
            },
            // Catch exception with NS 7.1
            function() {
                return (t == tj);
            }
        );
    },

    addAddress: function(where)
    {
        var selected, length, results = $('search_results');

        if (!$F(results).size()) {
            alert(this.text.select);
        } else {
            selected = $('selected_addresses');
            length = $A(selected).size();
            results.childElements().each(function(i) {
                if (i.value && i.selected) {
                    if (!$A(selected).any(function(j) {
                        return this.sameOption(where, i, j);
                    }, this)) {
                        selected[length++] = new Option(
                            this.text.rcpt[where] + ': ' + i.value,
                            where + ': ' + i.value
                        );
                    }
                }
            }, this);
        }
    },

    updateMessage: function()
    {
        if (!parent.opener) {
            alert(this.text.closed);
            window.close();
            return;
        }

        $('selected_addresses').childElements().each(function(s) {
            var pos,
                address = s.value;

            if (!address.empty()) {
                pos = address.indexOf(':');

                $(parent.opener.document).fire('ImpContacts:update', {
                    field: address.substring(0, pos),
                    value: address.substring(pos + 2, address.length)
                });
            }
        });

        window.close();
    },

    removeAddress: function()
    {
        $('selected_addresses').childElements().each(function(o) {
            if (o.selected) {
                o.remove();
            }
        });
    },

    resize: function()
    {
        window.resizeBy(0, Math.max(0, document.body.clientHeight - document.viewport.getHeight()));
    },

    onDomLoad: function()
    {
        if ($('search').present()) {
            $('btn_clear').show();
        }

        HordeCore.initHandler('click');
        HordeCore.initHandler('dblclick');
        $('contacts').observe('submit', this._passAddresses.bind(this));

        this.resize.bind(this).delay(0.25);
    },

    clickHandler: function(e)
    {
        switch (e.element().readAttribute('id')) {
        case 'btn_add_bcc':
            this.addAddress('bcc');
            break;

        case 'btn_add_cc':
            this.addAddress('cc');
            break;

        case 'btn_add_to':
            this.addAddress('to');
            break;

        case 'btn_cancel':
            window.close();
            e.memo.hordecore_stop = true;
            break;

        case 'btn_clear':
            $('search').clear();
            break;

        case 'btn_delete':
            this.removeAddress();
            break;

        case 'btn_update':
            this.updateMessage();
            break;
        }
    },

    dblclickHandler: function(e)
    {
        switch (e.element().readAttribute('id')) {
        case 'search_results':
            this.addAddress('to');
            break;

        case 'selected_addresses':
            this.removeAddress();
            break;
        }
    }

};

document.observe('dom:loaded', ImpContacts.onDomLoad.bind(ImpContacts));
document.observe('HordeCore:click', ImpContacts.clickHandler.bindAsEventListener(ImpContacts));
document.observe('HordeCore:dblclick', ImpContacts.dblclickHandler.bindAsEventListener(ImpContacts));
