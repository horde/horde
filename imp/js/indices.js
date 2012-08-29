/**
 * indices.js - Code to generate/parse UID strings.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

var ImpIndices = {

    INBOX: 'SU5CT1g', // 'INBOX' base64url encoded

    /**
     * Convert object to an IMP UID range string.
     *
     * This method requires prototype.js.
     *
     * @param object ob    Mailbox name as keys, values are array of uids.
     * @param object opts  Additional options:
     *   - pop3: (boolean) Output POP3 string?
     *   - raw: (boolean) Force into parsing in raw mode
     *
     * @return string  UID range string.
     */
    toUIDString: function(ob, opts)
    {
        var str = '';
        opts = opts || {};

        $H(ob).each(function(o) {
            if (!o.value.size()) {
                return;
            }

            if (opts.pop3) {
                o.value.each(function(u) {
                    str += '{P' + u.length + '}' + u;
                });
            } else {
                var u = (opts.raw ? o.value.clone() : o.value.numericSort()),
                    first = u.shift(),
                    last = first,
                    out = [];

                u.each(function(k) {
                    if (!opts.raw && (last + 1 == k)) {
                        last = k;
                    } else {
                        out.push(first + (last == first ? '' : (':' + last)));
                        first = last = k;
                    }
                });
                out.push(first + (last == first ? '' : (':' + last)));
                str += '{' + o.key.length + '}' + o.key + out.join(',');
            }
        });

        return str;
    },

    /**
     * Parses an IMP UID range string.
     *
     * This method requires either prototypejs or jQuery.
     *
     * @param string str   An IMP UID range string.
     * @param object opts  Additional options:
     *   - pop3: (boolean) Output POP3 string?
     *
     * @return object  Properties are mailbox names, values are lists of UIDs.
     */
    parseUIDString: function(str, opts)
    {
        var arr, end, i, initial, j, len, mbox, size, uidstr,
            mlist = {},
            uids = [];

        opts = opts || {};
        str = str.replace(/^\s+/, '').replace(/\s+$/, '');

        if (opts.pop3) {
            initial = '{P';
            mlist[this.INBOX] = [];
        } else {
            initial = '{';
        }

        while (str.length) {
            if (str.lastIndexOf(initial, 0) !== 0) {
                break;
            }
            i = str.indexOf('}');
            size = Number(str.substring(initial.length, i));
            mbox = str.substr(i + 1, size);

            if (opts.pop3) {
                mlist[this.INBOX].push(mbox);
                str = str.substr(i + 1 + size);
            } else {
                i += size + 1;
                end = str.indexOf('{', i);
                if (end == -1) {
                    uidstr = str.substr(i);
                    str = '';
                } else {
                    uidstr = str.substr(i, end - i);
                    str = str.substr(end);
                }

                arr = uidstr.split(',');
                for (j = 0, len = arr.length; j < len; ++j) {
                    var k, r = arr[j].split(':');
                    if (r.length == 1) {
                        uids.push(Number(arr[j]));
                    } else {
                        // POP3 will never exist in range here.
                        for (k = Number(r[0]); k <= Number(r[1]); ++k) {
                            uids.push(k);
                        }
                    }
                }

                mlist[mbox] = uids;
            }
        }

        return mlist;
    }

};
