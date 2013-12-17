/**
 * JS Implementation of MurmurHash3
 *
 * Original version:
 * https://github.com/kazuyukitanimura/murmurhash-js
 *
 * Additions by Michael Slusarz <slusarz@horde.org>
 *
 * -----
 *
 * Copyright (c) 2011 Gary Court
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 *
 * @param {string} key ASCII only
 * @param {number} seed Positive integer only
 * @return {number} 32-bit positive integer hash
 */

function murmurhash3(key, seed)
{
    var remainder = key.length & 3, // key.length % 4
        bytes = key.length - remainder,
        h1 = seed,
        c1 = 0xcc9e2d51,
        c2 = 0x1b873593,
        i = 0,
        c1_l = c1 & 0xffff,
        c1_h = c1 & 0xffff0000,
        c2_l  = c2 & 0xffff,
        c2_h  = c2 & 0xffff0000,
        k1;

    while (i < bytes) {
        k1 = ((key.charCodeAt(i++) & 0xff)) |
             ((key.charCodeAt(i++) & 0xff) << 8) |
             ((key.charCodeAt(i++) & 0xff) << 16) |
             ((key.charCodeAt(i++) & 0xff) << 24);

        // note that javascript precision is 2^53
        k1 = (k1 * c1_l + (k1 & 0xffff) * c1_h) & 0xffffffff;
        k1 = (k1 << 15) | (k1 >>> 17);
        k1 = (k1 * c2_l + (k1 & 0xffff) * c2_h) & 0xffffffff;

        h1 ^= k1;
        h1  = (h1 << 13) | (h1 >>> 19);
        h1  = (h1 * 5 + 0xe6546b64) & 0xffffffff;
    }

    k1 = 0;

    switch (remainder) {
    case 3:
        k1 ^= (key.charCodeAt(i + 2) & 0xff) << 16;

    case 2:
        k1 ^= (key.charCodeAt(i + 1) & 0xff) << 8;

    case 1:
        k1 ^= (key.charCodeAt(i) & 0xff);

        k1  = (k1 * c1_l + (k1 & 0xffff) * c1_h) & 0xffffffff;
        k1  = (k1 << 16) | (k1 >>> 16);
        k1  = (k1 * c2_l + (k1 & 0xffff) * c2_h) & 0xffffffff;
        h1 ^= k1;
    }

    h1 ^= key.length;

    h1 ^= h1 >>> 16;
    h1  = (h1 * 0xca6b + (h1 & 0xffff) * 0x85eb0000) & 0xffffffff;
    h1 ^= h1 >>> 13;
    h1  = (h1 * 0xae35 + (h1 & 0xffff) * 0xc2b20000) & 0xffffffff;
    h1 ^= h1 >>> 16;

    return h1 >>> 0;
}
