/**
 * Provides the javascript for managing the source selection widget.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

var HordeSourceSelectPrefs = {

    resetHidden: function()
    {
        $('sources').setValue($F('selected_sources').toJSON());
    },

    moveAction: function(from, to)
    {
        $(from).childElements().each(function(c) {
            if (c.selected) {
                c.remove();
                $(to).insert(c);
            }
        });

        this.resetHidden();
    },

    moveSource: function(e, mode)
    {
        var sa = $('selected_sources'), sel, tmp;

        if (sa.selectedIndex < 1 || sa.length < 3) {
            return;
        }

        // Deselect everything but the first selected item
        sa.childElements().each(function(s) {
            if (sel) {
                s.selected = false;
            } else if (s.selected) {
                sel = s;
            }
        });

        switch (mode) {
        case 'down':
            tmp = sel.next();
            if (tmp) {
                sel.remove();
                tmp.insert({ after: sel });
            }
            break;

        case 'up':
            tmp = sel.previous();
            if (tmp && tmp.value) {
                sel.remove();
                tmp.insert({ before: sel });
            }
            break;
        }

        this.resetHidden();
        e.stop();
    },

    onDomLoad: function()
    {
        this.resetHidden();

        if ($('unselected_sources')) {
            $('addsource').observe('click', this.moveAction.bind(this, 'unselected_sources', 'selected_sources'));
            $('removesource').observe('click', this.moveAction.bind(this, 'selected_sources', 'unselected_sources'));
            $('moveup').observe('click', this.moveSource.bindAsEventListener(this, 'up'));
            $('movedown').observe('click', this.moveSource.bindAsEventListener(this, 'down'));
        }
    }

};

document.observe('dom:loaded', HordeSourceSelectPrefs.onDomLoad.bind(HordeSourceSelectPrefs));
