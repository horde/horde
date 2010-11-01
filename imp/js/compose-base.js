/**
 * compose-base.js - Provides basic compose javascript functions shared
 * between standarad and dynamic displays.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

var IMP_Compose_Base = {

    // Vars defaulting to null: editor_on, identities

    getIdentity: function(id, editor_on)
    {
        return {
            id: this.identities[id],
            sig: this.identities[id][((editor_on || this.editor_on) ? 'sig_html' : 'sig')].replace(/^\n/, '')
        };
    },

    setCursorPosition: function(input, type, sig)
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

        case 'sig':
            pos = $F(input).replace(/\r\n/g, '\n').lastIndexOf(sig) - 1;
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

    replaceSignature: function(id)
    {
        var lastsig, msg, nextsig, pos, tmp, tmp2,
            next = this.getIdentity(id);

        // If the rich text editor is on, we'll use a regexp to find the
        // signature comment and replace its contents.
        if (this.editor_on) {
            // Create a temporary element, import the data from the editor,
            // search/replace the current imp signature data, and reinsert
            // into the editor.
            tmp = new Element('DIV').hide();
            $(document.body).insert(tmp);
            tmp.update(CKEDITOR.instances['composeMessage'].getData());
            tmp2 = tmp.select('DIV.impComposeSignature');
            if (tmp2.size()) {
                msg = tmp2.last().update(next.sig);
            } else {
                msg = next.id.sig_loc
                    ? tmp.insert({ top: next.sig })
                    : tmp.insert({ bottom: next.sig });
            }
            CKEDITOR.instances['composeMessage'].setData(msg.innerHTML);
            tmp.remove();
        } else {
            msg = $F('composeMessage').replace(/\r\n/g, '\n');
            last = this.getIdentity($F('last_identity'));
            lastsig = last.sig.replace(/^\n/, '');
            nextsig = next.sig.replace(/^\n/, '');

            pos = last.id.sig_loc
                ? msg.indexOf(lastsig)
                : msg.lastIndexOf(lastsig);

            if (pos != -1) {
                if (next.id.sig_loc == last.id.sig_loc) {
                    msg = msg.substring(0, pos) + nextsig + msg.substring(pos + lastsig.length, msg.length);
                } else if (next.id.sig_loc) {
                    msg = nextsig + msg.substring(0, pos) + msg.substring(pos + lastsig.length, msg.length);
                } else {
                    msg = msg.substring(0, pos) + msg.substring(pos + lastsig.length, msg.length) + nextsig;
                }

                $('composeMessage').setValue(msg.replace(/\r\n/g, '\n').replace(/\n/g, '\r\n'));
            }
        }

        $('last_identity').setValue(id);
    }

};
