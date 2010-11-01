/*
 * LiquidMetal, version: 0.1 (2009-02-05)
 *
 * A mimetic poly-alloy of Quicksilver's scoring algorithm, essentially
 * LiquidMetal.
 *
 * For usage and examples, visit:
 * http://github.com/rmm5t/liquidmetal
 *
 * Licensed under the MIT:
 * http://www.opensource.org/licenses/mit-license.php
 *
 * Copyright (c) 2009, Ryan McGeary (ryanonjavascript -[at]- mcgeary [*dot*] org)
 * Modified by the Horde Project to use compressibility.
 *
 * @category Horde
 * @package  Core
 */
var LiquidMetal = {

    SCORE_NO_MATCH: 0.0,
    SCORE_MATCH: 1.0,
    SCORE_TRAILING: 0.8,
    SCORE_TRAILING_BUT_STARTED: 0.9,
    SCORE_BUFFER: 0.85,

    score: function(str, abbr)
    {
        // Short circuits
        if (abbr.length == 0) {
           return this.SCORE_TRAILING;
        }
        if (abbr.length > str.length) {
            return this.SCORE_NO_MATCH;
        }

        var scores = this.buildScoreArray(str, abbr),
            sum = 0.0;

        scores.each(function(i) {
            sum += i;
        });

        return (sum / scores.size());
    },

    buildScoreArray: function(str, abbr)
    {
        var lastIndex = -1,
            lower = str.toLowerCase(),
            scores = new Array(str.length),
            started = false;

        abbr.toLowerCase().split("").each(function(c) {
            var index = to = lower.indexOf(c, lastIndex + 1),
                val = this.SCORE_BUFFER;

            if (index < 0) {
                return this.fillArray(scores, this.SCORE_NO_MATCH);
            }

            if (index == 0) {
                started = true;
            }

            if (this.isNewWord(str, index)) {
                scores[index - 1] = 1;
                to = index - 1;
            } else if (!this.isUpperCase(str, index)) {
                val = this.SCORE_NO_MATCH;
            }

            this.fillArray(scores, val, lastIndex + 1, val);

            scores[index] = this.SCORE_MATCH;
            lastIndex = index;
        }.bind(this));

        this.fillArray(scores, started ? this.SCORE_TRAILING_BUT_STARTED : this.SCORE_TRAILING, lastIndex + 1);

        return scores;
    },

    isUpperCase: function(str, index)
    {
        var c = str.charAt(index);
        return ("A" <= c && c <= "Z");
    },

    isNewWord: function(str, index)
    {
        var c = str.charAt(index - 1);
        return (c == " " || c == "\t");
    },

    fillArray: function(arr, value, from, to)
    {
        from = Math.max(from || 0, 0);
        to = Math.min(to || arr.length, arr.length);
        for (var i = from; i < to; ++i) {
            arr[i] = value;
        }
        return arr;
    }
};
