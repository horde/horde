/**
 * base64url (RFC 4648 [5]) encoding methods.
 *
 * Loosely based on code from:
 *   http://www.webtoolkit.info/javascript-base64.html
 *
 * Requires prototypejs v1.7+.
 */

Object.extend(String.prototype, (function() {
    // Base64 character library
    var chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_";

    function encode()
    {
        var c1, c2, c3,
            i = 0,
            output = "";

        while (i < this.length) {
            c1 = this.charCodeAt(i++);
            c2 = this.charCodeAt(i++);

            output += chars.charAt(c1 >> 2);
            if (isNaN(c2)) {
                output += chars.charAt((c1 & 3) << 4);
                ++i;
            } else {
                output += chars.charAt(((c1 & 3) << 4) | (c2 >> 4));
                c3 = this.charCodeAt(i++);
                if (isNaN(c3)) {
                    output += chars.charAt((c2 & 15) << 2);
                } else {
                    output += chars.charAt(((c2 & 15) << 2) | (c3 >> 6)) +
                              chars.charAt(c3 & 63);
                }
            }
        }

        return output;
    }

    function decode()
    {
        var e1, e2, e3, e4,
            i = 0,
            output = "";

        while (i < this.length) {
            e1 = chars.indexOf(this.charAt(i++));
            e2 = chars.indexOf(this.charAt(i++));
            e3 = this.charAt(i++);

            output += String.fromCharCode((e1 << 2) | (e2 >> 4));
            if (e3.empty()) {
                ++i;
            } else {
                e3 = chars.indexOf(e3);
                e4 = this.charAt(i++);
                output += String.fromCharCode(((e2 & 15) << 4) | (e3 >> 2));
                if (!e4.empty()) {
                    output += String.fromCharCode(((e3 & 3) << 6) | chars.indexOf(e4));
                }
            }
        }

        return output;
    }

    return {
        base64urlEncode: encode,
        base64urlDecode: decode
    };
})());
