/*
 * A JavaScript implementation of the RSA Data Security, Inc. MD5 Message
 * Digest Algorithm, as defined in RFC 1321.
 * Version 2.2 Copyright (C) Paul Johnston 1999 - 2009
 * Other contributors: Greg Holt, Andrew Kepert, Ydnar, Lostinet
 * Distributed under the BSD License
 * See http://pajhome.org.uk/crypt/md5 for more info.
 *
 * Additions by Michael Slusarz <slusarz@horde.org>
 * Removed all functions except those needed to do string->hex hashing &
 * enclosed in MD5 namespace.
 *
 * Requires prototype.js (v1.6.1+)
 */

window.MD5 = {

    hash: function(s)
    {
        return this.rstr2hex(this.rstr_md5(this.str2rstr_utf8(s)));
    },

    /* Calculate the MD5 of a raw string */
    rstr_md5: function(s)
    {
        return this.binl2rstr(this.binl_md5(this.rstr2binl(s), s.length * 8));
    },

    /* Convert a raw string to a hex string. */
    rstr2hex: function(input)
    {
        var i, x,
            hex_tab = "0123456789abcdef",
            output = "";

        for (i = 0; i < input.length; ++i) {
            x = input.charCodeAt(i);
            output += hex_tab.charAt((x >>> 4) & 0x0F) + hex_tab.charAt(x & 0x0F);
        }

        return output;
    },

    /* Encode a string as utf-8. For efficiency, this assumes the input is
     * valid utf-16. */
    str2rstr_utf8: function(input)
    {
        var x, y,
            i = -1,
            output = "";

        while (++i < input.length) {
            /* Decode utf-16 surrogate pairs */
            x = input.charCodeAt(i);
            y = i + 1 < input.length ? input.charCodeAt(i + 1) : 0;
            if (0xD800 <= x && x <= 0xDBFF && 0xDC00 <= y && y <= 0xDFFF) {
                x = 0x10000 + ((x & 0x03FF) << 10) + (y & 0x03FF);
                ++i;
            }

            /* Encode output as utf-8 */
            if (x <= 0x7F) {
                output += String.fromCharCode(x);
            } else if (x <= 0x7FF) {
                output += String.fromCharCode(0xC0 | ((x >>> 6) & 0x1F), 0x80 | (x & 0x3F));
            } else if (x <= 0xFFFF) {
                output += String.fromCharCode(0xE0 | ((x >>> 12) & 0x0F), 0x80 | ((x >>> 6) & 0x3F), 0x80 | (x & 0x3F));
            } else if (x <= 0x1FFFFF) {
                output += String.fromCharCode(0xF0 | ((x >>> 18) & 0x07), 0x80 | ((x >>> 12) & 0x3F), 0x80 | ((x >>> 6) & 0x3F), 0x80 | (x & 0x3F));
            }
        }

        return output;
    },

    /* Convert a raw string to an array of little-endian words.
     * Characters >255 have their high-byte silently ignored. */
    rstr2binl: function(input)
    {
        var i,
            output = Array(input.length >> 2);

        for(i = 0; i < output.length; ++i) {
            output[i] = 0;
        }

        for (i = 0; i < input.length * 8; i += 8) {
            output[i>>5] |= (input.charCodeAt(i / 8) & 0xFF) << (i%32);
        }

        return output;
    },

    /* Convert an array of little-endian words to a string. */
    binl2rstr: function(input)
    {
        var i, output = "";

        for (i = 0; i < input.length * 32; i += 8) {
            output += String.fromCharCode((input[i>>5] >>> (i % 32)) & 0xFF);
        }

        return output;
    },

    /* Calculate the MD5 of an array of little-endian words, and a bit
     * length. */
    binl_md5: function(x, len)
    {
        /* append padding */
        x[len >> 5] |= 0x80 << ((len) % 32);
        x[(((len + 64) >>> 9) << 4) + 14] = len;

        var i, olda, oldb, oldc, oldd,
            a = 1732584193,
            b = -271733879,
            c = -1732584194,
            d = 271733878;

        for (i = 0; i < x.length; i += 16) {
            olda = a;
            oldb = b;
            oldc = c;
            oldd = d;

            a = this.md5_ff(a, b, c, d, x[i+ 0], 7 , -680876936);
            d = this.md5_ff(d, a, b, c, x[i+ 1], 12, -389564586);
            c = this.md5_ff(c, d, a, b, x[i+ 2], 17,  606105819);
            b = this.md5_ff(b, c, d, a, x[i+ 3], 22, -1044525330);
            a = this.md5_ff(a, b, c, d, x[i+ 4], 7 , -176418897);
            d = this.md5_ff(d, a, b, c, x[i+ 5], 12,  1200080426);
            c = this.md5_ff(c, d, a, b, x[i+ 6], 17, -1473231341);
            b = this.md5_ff(b, c, d, a, x[i+ 7], 22, -45705983);
            a = this.md5_ff(a, b, c, d, x[i+ 8], 7 ,  1770035416);
            d = this.md5_ff(d, a, b, c, x[i+ 9], 12, -1958414417);
            c = this.md5_ff(c, d, a, b, x[i+10], 17, -42063);
            b = this.md5_ff(b, c, d, a, x[i+11], 22, -1990404162);
            a = this.md5_ff(a, b, c, d, x[i+12], 7 ,  1804603682);
            d = this.md5_ff(d, a, b, c, x[i+13], 12, -40341101);
            c = this.md5_ff(c, d, a, b, x[i+14], 17, -1502002290);
            b = this.md5_ff(b, c, d, a, x[i+15], 22,  1236535329);

            a = this.md5_gg(a, b, c, d, x[i+ 1], 5 , -165796510);
            d = this.md5_gg(d, a, b, c, x[i+ 6], 9 , -1069501632);
            c = this.md5_gg(c, d, a, b, x[i+11], 14,  643717713);
            b = this.md5_gg(b, c, d, a, x[i+ 0], 20, -373897302);
            a = this.md5_gg(a, b, c, d, x[i+ 5], 5 , -701558691);
            d = this.md5_gg(d, a, b, c, x[i+10], 9 ,  38016083);
            c = this.md5_gg(c, d, a, b, x[i+15], 14, -660478335);
            b = this.md5_gg(b, c, d, a, x[i+ 4], 20, -405537848);
            a = this.md5_gg(a, b, c, d, x[i+ 9], 5 ,  568446438);
            d = this.md5_gg(d, a, b, c, x[i+14], 9 , -1019803690);
            c = this.md5_gg(c, d, a, b, x[i+ 3], 14, -187363961);
            b = this.md5_gg(b, c, d, a, x[i+ 8], 20,  1163531501);
            a = this.md5_gg(a, b, c, d, x[i+13], 5 , -1444681467);
            d = this.md5_gg(d, a, b, c, x[i+ 2], 9 , -51403784);
            c = this.md5_gg(c, d, a, b, x[i+ 7], 14,  1735328473);
            b = this.md5_gg(b, c, d, a, x[i+12], 20, -1926607734);

            a = this.md5_hh(a, b, c, d, x[i+ 5], 4 , -378558);
            d = this.md5_hh(d, a, b, c, x[i+ 8], 11, -2022574463);
            c = this.md5_hh(c, d, a, b, x[i+11], 16,  1839030562);
            b = this.md5_hh(b, c, d, a, x[i+14], 23, -35309556);
            a = this.md5_hh(a, b, c, d, x[i+ 1], 4 , -1530992060);
            d = this.md5_hh(d, a, b, c, x[i+ 4], 11,  1272893353);
            c = this.md5_hh(c, d, a, b, x[i+ 7], 16, -155497632);
            b = this.md5_hh(b, c, d, a, x[i+10], 23, -1094730640);
            a = this.md5_hh(a, b, c, d, x[i+13], 4 ,  681279174);
            d = this.md5_hh(d, a, b, c, x[i+ 0], 11, -358537222);
            c = this.md5_hh(c, d, a, b, x[i+ 3], 16, -722521979);
            b = this.md5_hh(b, c, d, a, x[i+ 6], 23,  76029189);
            a = this.md5_hh(a, b, c, d, x[i+ 9], 4 , -640364487);
            d = this.md5_hh(d, a, b, c, x[i+12], 11, -421815835);
            c = this.md5_hh(c, d, a, b, x[i+15], 16,  530742520);
            b = this.md5_hh(b, c, d, a, x[i+ 2], 23, -995338651);

            a = this.md5_ii(a, b, c, d, x[i+ 0], 6 , -198630844);
            d = this.md5_ii(d, a, b, c, x[i+ 7], 10,  1126891415);
            c = this.md5_ii(c, d, a, b, x[i+14], 15, -1416354905);
            b = this.md5_ii(b, c, d, a, x[i+ 5], 21, -57434055);
            a = this.md5_ii(a, b, c, d, x[i+12], 6 ,  1700485571);
            d = this.md5_ii(d, a, b, c, x[i+ 3], 10, -1894986606);
            c = this.md5_ii(c, d, a, b, x[i+10], 15, -1051523);
            b = this.md5_ii(b, c, d, a, x[i+ 1], 21, -2054922799);
            a = this.md5_ii(a, b, c, d, x[i+ 8], 6 ,  1873313359);
            d = this.md5_ii(d, a, b, c, x[i+15], 10, -30611744);
            c = this.md5_ii(c, d, a, b, x[i+ 6], 15, -1560198380);
            b = this.md5_ii(b, c, d, a, x[i+13], 21,  1309151649);
            a = this.md5_ii(a, b, c, d, x[i+ 4], 6 , -145523070);
            d = this.md5_ii(d, a, b, c, x[i+11], 10, -1120210379);
            c = this.md5_ii(c, d, a, b, x[i+ 2], 15,  718787259);
            b = this.md5_ii(b, c, d, a, x[i+ 9], 21, -343485551);

            a = this.safe_add(a, olda);
            b = this.safe_add(b, oldb);
            c = this.safe_add(c, oldc);
            d = this.safe_add(d, oldd);
        }

        return Array(a, b, c, d);
    },

    /* These functions implement the four basic operations the algorithm
     * uses. */
    md5_cmn: function(q, a, b, x, s, t)
    {
        return this.safe_add(this.bit_rol(this.safe_add(this.safe_add(a, q), this.safe_add(x, t)), s),b);
    },

    md5_ff: function(a, b, c, d, x, s, t)
    {
        return this.md5_cmn((b & c) | ((~b) & d), a, b, x, s, t);
    },

    md5_gg: function(a, b, c, d, x, s, t)
    {
        return this.md5_cmn((b & d) | (c & (~d)), a, b, x, s, t);
    },

    md5_hh: function(a, b, c, d, x, s, t)
    {
        return this.md5_cmn(b ^ c ^ d, a, b, x, s, t);
    },

    md5_ii: function(a, b, c, d, x, s, t)
    {
        return this.md5_cmn(c ^ (b | (~d)), a, b, x, s, t);
    },

    /* Add integers, wrapping at 2^32. This uses 16-bit operations internally
     * to work around bugs in some JS interpreters. */
    safe_add: function(x, y)
    {
        var lsw = (x & 0xFFFF) + (y & 0xFFFF),
            msw = (x >> 16) + (y >> 16) + (lsw >> 16);
        return (msw << 16) | (lsw & 0xFFFF);
    },

    /* Bitwise rotate a 32-bit number to the left. */
    bit_rol: function(num, cnt)
    {
        return (num << cnt) | (num >>> (32 - cnt));
    }

};
