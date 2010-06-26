/*

  OpenLayers.js -- OpenLayers Map Viewer Library

  Copyright 2005-2008 MetaCarta, Inc., released under the Clear BSD license.
  Please see http://svn.openlayers.org/trunk/openlayers/license.txt
  for the full text of the license.

  Includes compressed code under the following licenses:

  (For uncompressed versions of the code used please see the
  OpenLayers SVN repository: <http://openlayers.org/>)

*/

/* Contains portions of Prototype.js:
 *
 * Prototype JavaScript framework, version 1.4.0
 *  (c) 2005 Sam Stephenson <sam@conio.net>
 *
 *  Prototype is freely distributable under the terms of an MIT-style license.
 *  For details, see the Prototype web site: http://prototype.conio.net/
 *
 *--------------------------------------------------------------------------*/

/**  
*  
*  Contains portions of Rico <http://openrico.org/>
* 
*  Copyright 2005 Sabre Airline Solutions  
*  
*  Licensed under the Apache License, Version 2.0 (the "License"); you
*  may not use this file except in compliance with the License. You
*  may obtain a copy of the License at
*  
*         http://www.apache.org/licenses/LICENSE-2.0  
*  
*  Unless required by applicable law or agreed to in writing, software
*  distributed under the License is distributed on an "AS IS" BASIS,
*  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or
*  implied. See the License for the specific language governing
*  permissions and limitations under the License. 
*
**/

/**
 * Contains XMLHttpRequest.js <http://code.google.com/p/xmlhttprequest/>
 * Copyright 2007 Sergey Ilinsky (http://www.ilinsky.com)
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 */

/**
 * Contains portions of Gears <http://code.google.com/apis/gears/>
 *
 * Copyright 2007, Google Inc.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *  1. Redistributions of source code must retain the above copyright notice,
 *     this list of conditions and the following disclaimer.
 *  2. Redistributions in binary form must reproduce the above copyright notice,
 *     this list of conditions and the following disclaimer in the documentation
 *     and/or other materials provided with the distribution.
 *  3. Neither the name of Google Inc. nor the names of its contributors may be
 *     used to endorse or promote products derived from this software without
 *     specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR IMPLIED
 * WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO
 * EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS;
 * OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
 * WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR
 * OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF
 * ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * Sets up google.gears.*, which is *the only* supported way to access Gears.
 *
 * Circumvent this file at your own risk!
 *
 * In the future, Gears may automatically define google.gears.* without this
 * file. Gears may use these objects to transparently fix bugs and compatibility
 * issues. Applications that use the code below will continue to work seamlessly
 * when that happens.
 */
/* ======================================================================
    OpenLayers/SingleFile.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */

var OpenLayers = {
    singleFile: true
};


/* ======================================================================
    OpenLayers.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */

/* 
 * @requires OpenLayers/BaseTypes.js
 * @requires OpenLayers/Lang/en.js
 * @requires OpenLayers/Console.js
 */ 

(function() {
    /**
     * Cache for the script location returned from
     * OpenLayers._getScriptLocation
     */
    var scriptLocation;
    
    /**
     * Namespace: OpenLayers
     * The OpenLayers object provides a namespace for all things OpenLayers
     */
    window.OpenLayers = {
        
        /**
         * Property: _scriptName
         * {String} Relative path of this script.
         */
        _scriptName: "OpenLayers.js",

        /**
         * Function: _getScriptLocation
         * Return the path to this script.
         *
         * Returns:
         * {String} Path to this script
         */
        _getScriptLocation: function () {
            if (scriptLocation != undefined) {
                return scriptLocation;
            }
            scriptLocation = "";            
            var isOL = new RegExp("(^|(.*?\\/))(" + OpenLayers._scriptName + ")(\\?|$)");
         
            var scripts = document.getElementsByTagName('script');
            for (var i=0, len=scripts.length; i<len; i++) {
                var src = scripts[i].getAttribute('src');
                if (src) {
                    var match = src.match(isOL);
                    if(match) {
                        scriptLocation = match[1];
                        break;
                    }
                }
            }
            return scriptLocation;
        }
    };
 })();

/**
 * Constant: VERSION_NUMBER
 */
OpenLayers.VERSION_NUMBER="OpenLayers 2.9.1 -- $Revision: 10129 $";
/* ======================================================================
    OpenLayers/BaseTypes.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */

/**
 * @requires OpenLayers/BaseTypes/Class.js
 * @requires OpenLayers/BaseTypes/LonLat.js
 * @requires OpenLayers/BaseTypes/Size.js
 * @requires OpenLayers/BaseTypes/Pixel.js
 * @requires OpenLayers/BaseTypes/Bounds.js
 * @requires OpenLayers/BaseTypes/Element.js
 * @requires OpenLayers/Lang/en.js
 * @requires OpenLayers/Console.js
 */
 
/** 
 * Header: OpenLayers Base Types
 * OpenLayers custom string, number and function functions are described here.
 */

/**
 * Namespace: OpenLayers.String
 * Contains convenience functions for string manipulation.
 */
OpenLayers.String = {

    /**
     * APIFunction: startsWith
     * Test whether a string starts with another string. 
     * 
     * Parameters:
     * str - {String} The string to test.
     * sub - {Sring} The substring to look for.
     *  
     * Returns:
     * {Boolean} The first string starts with the second.
     */
    startsWith: function(str, sub) {
        return (str.indexOf(sub) == 0);
    },

    /**
     * APIFunction: contains
     * Test whether a string contains another string.
     * 
     * Parameters:
     * str - {String} The string to test.
     * sub - {String} The substring to look for.
     * 
     * Returns:
     * {Boolean} The first string contains the second.
     */
    contains: function(str, sub) {
        return (str.indexOf(sub) != -1);
    },
    
    /**
     * APIFunction: trim
     * Removes leading and trailing whitespace characters from a string.
     * 
     * Parameters:
     * str - {String} The (potentially) space padded string.  This string is not
     *     modified.
     * 
     * Returns:
     * {String} A trimmed version of the string with all leading and 
     *     trailing spaces removed.
     */
    trim: function(str) {
        return str.replace(/^\s\s*/, '').replace(/\s\s*$/, '');
    },
    
    /**
     * APIFunction: camelize
     * Camel-case a hyphenated string. 
     *     Ex. "chicken-head" becomes "chickenHead", and
     *     "-chicken-head" becomes "ChickenHead".
     *
     * Parameters:
     * str - {String} The string to be camelized.  The original is not modified.
     * 
     * Returns:
     * {String} The string, camelized
     */
    camelize: function(str) {
        var oStringList = str.split('-');
        var camelizedString = oStringList[0];
        for (var i=1, len=oStringList.length; i<len; i++) {
            var s = oStringList[i];
            camelizedString += s.charAt(0).toUpperCase() + s.substring(1);
        }
        return camelizedString;
    },
    
    /**
     * APIFunction: format
     * Given a string with tokens in the form ${token}, return a string
     *     with tokens replaced with properties from the given context
     *     object.  Represent a literal "${" by doubling it, e.g. "${${".
     *
     * Parameters:
     * template - {String} A string with tokens to be replaced.  A template
     *     has the form "literal ${token}" where the token will be replaced
     *     by the value of context["token"].
     * context - {Object} An optional object with properties corresponding
     *     to the tokens in the format string.  If no context is sent, the
     *     window object will be used.
     * args - {Array} Optional arguments to pass to any functions found in
     *     the context.  If a context property is a function, the token
     *     will be replaced by the return from the function called with
     *     these arguments.
     *
     * Returns:
     * {String} A string with tokens replaced from the context object.
     */
    format: function(template, context, args) {
        if(!context) {
            context = window;
        }

        // Example matching: 
        // str   = ${foo.bar}
        // match = foo.bar
        var replacer = function(str, match) {
            var replacement;

            // Loop through all subs. Example: ${a.b.c}
            // 0 -> replacement = context[a];
            // 1 -> replacement = context[a][b];
            // 2 -> replacement = context[a][b][c];
            var subs = match.split(/\.+/);
            for (var i=0; i< subs.length; i++) {
                if (i == 0) {
                    replacement = context;
                }

                replacement = replacement[subs[i]];
            }

            if(typeof replacement == "function") {
                replacement = args ?
                    replacement.apply(null, args) :
                    replacement();
            }

            // If replacement is undefined, return the string 'undefined'.
            // This is a workaround for a bugs in browsers not properly 
            // dealing with non-participating groups in regular expressions:
            // http://blog.stevenlevithan.com/archives/npcg-javascript
            if (typeof replacement == 'undefined') {
                return 'undefined';
            } else {
                return replacement; 
            }
        };

        return template.replace(OpenLayers.String.tokenRegEx, replacer);
    },

    /**
     * Property: OpenLayers.String.tokenRegEx
     * Used to find tokens in a string.
     * Examples: ${a}, ${a.b.c}, ${a-b}, ${5}
     */
    tokenRegEx:  /\$\{([\w.]+?)\}/g,
    
    /**
     * Property: OpenLayers.String.numberRegEx
     * Used to test strings as numbers.
     */
    numberRegEx: /^([+-]?)(?=\d|\.\d)\d*(\.\d*)?([Ee]([+-]?\d+))?$/,
    
    /**
     * APIFunction: OpenLayers.String.isNumeric
     * Determine whether a string contains only a numeric value.
     *
     * Examples:
     * (code)
     * OpenLayers.String.isNumeric("6.02e23") // true
     * OpenLayers.String.isNumeric("12 dozen") // false
     * OpenLayers.String.isNumeric("4") // true
     * OpenLayers.String.isNumeric(" 4 ") // false
     * (end)
     *
     * Returns:
     * {Boolean} String contains only a number.
     */
    isNumeric: function(value) {
        return OpenLayers.String.numberRegEx.test(value);
    },
    
    /**
     * APIFunction: numericIf
     * Converts a string that appears to be a numeric value into a number.
     * 
     * Returns
     * {Number|String} a Number if the passed value is a number, a String
     *     otherwise. 
     */
    numericIf: function(value) {
        return OpenLayers.String.isNumeric(value) ? parseFloat(value) : value;
    }

};

if (!String.prototype.startsWith) {
    /**
     * APIMethod: String.startsWith
     * *Deprecated*. Whether or not a string starts with another string. 
     * 
     * Parameters:
     * sStart - {Sring} The string we're testing for.
     *  
     * Returns:
     * {Boolean} Whether or not this string starts with the string passed in.
     */
    String.prototype.startsWith = function(sStart) {
        OpenLayers.Console.warn(OpenLayers.i18n("methodDeprecated",
                                {'newMethod':'OpenLayers.String.startsWith'}));
        return OpenLayers.String.startsWith(this, sStart);
    };
}

if (!String.prototype.contains) {
    /**
     * APIMethod: String.contains
     * *Deprecated*. Whether or not a string contains another string.
     * 
     * Parameters:
     * str - {String} The string that we're testing for.
     * 
     * Returns:
     * {Boolean} Whether or not this string contains with the string passed in.
     */
    String.prototype.contains = function(str) {
        OpenLayers.Console.warn(OpenLayers.i18n("methodDeprecated",
                                  {'newMethod':'OpenLayers.String.contains'}));
        return OpenLayers.String.contains(this, str);
    };
}

if (!String.prototype.trim) {
    /**
     * APIMethod: String.trim
     * *Deprecated*. Removes leading and trailing whitespace characters from a string.
     * 
     * Returns:
     * {String} A trimmed version of the string - all leading and 
     *          trailing spaces removed
     */
    String.prototype.trim = function() {
        OpenLayers.Console.warn(OpenLayers.i18n("methodDeprecated",
                                      {'newMethod':'OpenLayers.String.trim'}));
        return OpenLayers.String.trim(this);
    };
}

if (!String.prototype.camelize) {
    /**
     * APIMethod: String.camelize
     * *Deprecated*. Camel-case a hyphenated string. 
     *     Ex. "chicken-head" becomes "chickenHead", and
     *     "-chicken-head" becomes "ChickenHead".
     * 
     * Returns:
     * {String} The string, camelized
     */
    String.prototype.camelize = function() {
        OpenLayers.Console.warn(OpenLayers.i18n("methodDeprecated",
                                  {'newMethod':'OpenLayers.String.camelize'}));
        return OpenLayers.String.camelize(this);
    };
}

/**
 * Namespace: OpenLayers.Number
 * Contains convenience functions for manipulating numbers.
 */
OpenLayers.Number = {

    /**
     * Property: decimalSeparator
     * Decimal separator to use when formatting numbers.
     */
    decimalSeparator: ".",
    
    /**
     * Property: thousandsSeparator
     * Thousands separator to use when formatting numbers.
     */
    thousandsSeparator: ",",
    
    /**
     * APIFunction: limitSigDigs
     * Limit the number of significant digits on a float.
     * 
     * Parameters:
     * num - {Float}
     * sig - {Integer}
     * 
     * Returns:
     * {Float} The number, rounded to the specified number of significant
     *     digits.
     */
    limitSigDigs: function(num, sig) {
        var fig = 0;
        if (sig > 0) {
            fig = parseFloat(num.toPrecision(sig));
        }
        return fig;
    },
    
    /**
     * APIFunction: format
     * Formats a number for output.
     * 
     * Parameters:
     * num  - {Float}
     * dec  - {Integer} Number of decimal places to round to.
     *        Defaults to 0. Set to null to leave decimal places unchanged.
     * tsep - {String} Thousands separator.
     *        Default is ",".
     * dsep - {String} Decimal separator.
     *        Default is ".".
     *
     * Returns:
     * {String} A string representing the formatted number.
     */
    format: function(num, dec, tsep, dsep) {
        dec = (typeof dec != "undefined") ? dec : 0; 
        tsep = (typeof tsep != "undefined") ? tsep :
            OpenLayers.Number.thousandsSeparator; 
        dsep = (typeof dsep != "undefined") ? dsep :
            OpenLayers.Number.decimalSeparator;

        if (dec != null) {
            num = parseFloat(num.toFixed(dec));
        }

        var parts = num.toString().split(".");
        if (parts.length == 1 && dec == null) {
            // integer where we do not want to touch the decimals
            dec = 0;
        }
        
        var integer = parts[0];
        if (tsep) {
            var thousands = /(-?[0-9]+)([0-9]{3})/; 
            while(thousands.test(integer)) { 
                integer = integer.replace(thousands, "$1" + tsep + "$2"); 
            }
        }
        
        var str;
        if (dec == 0) {
            str = integer;
        } else {
            var rem = parts.length > 1 ? parts[1] : "0";
            if (dec != null) {
                rem = rem + new Array(dec - rem.length + 1).join("0");
            }
            str = integer + dsep + rem;
        }
        return str;
    }
};

if (!Number.prototype.limitSigDigs) {
    /**
     * APIMethod: Number.limitSigDigs
     * *Deprecated*. Limit the number of significant digits on an integer. Does *not*
     *     work with floats!
     * 
     * Parameters:
     * sig - {Integer}
     * 
     * Returns:
     * {Integer} The number, rounded to the specified number of significant digits.
     *           If null, 0, or negative value passed in, returns 0
     */
    Number.prototype.limitSigDigs = function(sig) {
        OpenLayers.Console.warn(OpenLayers.i18n("methodDeprecated",
                              {'newMethod':'OpenLayers.Number.limitSigDigs'}));
        return OpenLayers.Number.limitSigDigs(this, sig);
    };
}

/**
 * Namespace: OpenLayers.Function
 * Contains convenience functions for function manipulation.
 */
OpenLayers.Function = {
    /**
     * APIFunction: bind
     * Bind a function to an object.  Method to easily create closures with
     *     'this' altered.
     * 
     * Parameters:
     * func - {Function} Input function.
     * object - {Object} The object to bind to the input function (as this).
     * 
     * Returns:
     * {Function} A closure with 'this' set to the passed in object.
     */
    bind: function(func, object) {
        // create a reference to all arguments past the second one
        var args = Array.prototype.slice.apply(arguments, [2]);
        return function() {
            // Push on any additional arguments from the actual function call.
            // These will come after those sent to the bind call.
            var newArgs = args.concat(
                Array.prototype.slice.apply(arguments, [0])
            );
            return func.apply(object, newArgs);
        };
    },
    
    /**
     * APIFunction: bindAsEventListener
     * Bind a function to an object, and configure it to receive the event
     *     object as first parameter when called. 
     * 
     * Parameters:
     * func - {Function} Input function to serve as an event listener.
     * object - {Object} A reference to this.
     * 
     * Returns:
     * {Function}
     */
    bindAsEventListener: function(func, object) {
        return function(event) {
            return func.call(object, event || window.event);
        };
    },
    
    /**
     * APIFunction: False
     * A simple function to that just does "return false". We use this to 
     * avoid attaching anonymous functions to DOM event handlers, which 
     * causes "issues" on IE<8.
     * 
     * Usage:
     * document.onclick = OpenLayers.Function.False;
     * 
     * Returns:
     * {Boolean}
     */
    False : function() {
        return false;
    },

    /**
     * APIFunction: True
     * A simple function to that just does "return true". We use this to 
     * avoid attaching anonymous functions to DOM event handlers, which 
     * causes "issues" on IE<8.
     * 
     * Usage:
     * document.onclick = OpenLayers.Function.True;
     * 
     * Returns:
     * {Boolean}
     */
    True : function() {
        return true;
    }
};

if (!Function.prototype.bind) {
    /**
     * APIMethod: Function.bind
     * *Deprecated*. Bind a function to an object. 
     * Method to easily create closures with 'this' altered.
     * 
     * Parameters:
     * object - {Object} the this parameter
     * 
     * Returns:
     * {Function} A closure with 'this' altered to the first
     *            argument.
     */
    Function.prototype.bind = function() {
        OpenLayers.Console.warn(OpenLayers.i18n("methodDeprecated",
                                {'newMethod':'OpenLayers.Function.bind'}));
        // new function takes the same arguments with this function up front
        Array.prototype.unshift.apply(arguments, [this]);
        return OpenLayers.Function.bind.apply(null, arguments);
    };
}

if (!Function.prototype.bindAsEventListener) {
    /**
     * APIMethod: Function.bindAsEventListener
     * *Deprecated*. Bind a function to an object, and configure it to receive the
     *     event object as first parameter when called. 
     * 
     * Parameters:
     * object - {Object} A reference to this.
     * 
     * Returns:
     * {Function}
     */
    Function.prototype.bindAsEventListener = function(object) {
        OpenLayers.Console.warn(OpenLayers.i18n("methodDeprecated",
                        {'newMethod':'OpenLayers.Function.bindAsEventListener'}));
        return OpenLayers.Function.bindAsEventListener(this, object);
    };
}

/**
 * Namespace: OpenLayers.Array
 * Contains convenience functions for array manipulation.
 */
OpenLayers.Array = {

    /**
     * APIMethod: filter
     * Filter an array.  Provides the functionality of the
     *     Array.prototype.filter extension to the ECMA-262 standard.  Where
     *     available, Array.prototype.filter will be used.
     *
     * Based on well known example from http://developer.mozilla.org/en/docs/Core_JavaScript_1.5_Reference:Objects:Array:filter
     *
     * Parameters:
     * array - {Array} The array to be filtered.  This array is not mutated.
     *     Elements added to this array by the callback will not be visited.
     * callback - {Function} A function that is called for each element in
     *     the array.  If this function returns true, the element will be
     *     included in the return.  The function will be called with three
     *     arguments: the element in the array, the index of that element, and
     *     the array itself.  If the optional caller parameter is specified
     *     the callback will be called with this set to caller.
     * caller - {Object} Optional object to be set as this when the callback
     *     is called.
     *
     * Returns:
     * {Array} An array of elements from the passed in array for which the
     *     callback returns true.
     */
    filter: function(array, callback, caller) {
        var selected = [];
        if (Array.prototype.filter) {
            selected = array.filter(callback, caller);
        } else {
            var len = array.length;
            if (typeof callback != "function") {
                throw new TypeError();
            }
            for(var i=0; i<len; i++) {
                if (i in array) {
                    var val = array[i];
                    if (callback.call(caller, val, i, array)) {
                        selected.push(val);
                    }
                }
            }        
        }
        return selected;
    }
    
};
/* ======================================================================
    OpenLayers/BaseTypes/Class.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */

/**
 * Constructor: OpenLayers.Class
 * Base class used to construct all other classes. Includes support for 
 *     multiple inheritance. 
 *     
 * This constructor is new in OpenLayers 2.5.  At OpenLayers 3.0, the old 
 *     syntax for creating classes and dealing with inheritance 
 *     will be removed.
 * 
 * To create a new OpenLayers-style class, use the following syntax:
 * > var MyClass = OpenLayers.Class(prototype);
 *
 * To create a new OpenLayers-style class with multiple inheritance, use the
 *     following syntax:
 * > var MyClass = OpenLayers.Class(Class1, Class2, prototype);
 * Note that instanceof reflection will only reveil Class1 as superclass.
 * Class2 ff are mixins.
 *
 */
OpenLayers.Class = function() {
    var Class = function() {
        /**
         * This following condition can be removed at 3.0 - this is only for
         * backwards compatibility while the Class.inherit method is still
         * in use.  So at 3.0, the following three lines would be replaced with
         * simply:
         * this.initialize.apply(this, arguments);
         */
        if (arguments && arguments[0] != OpenLayers.Class.isPrototype) {
            this.initialize.apply(this, arguments);
        }
    };
    var extended = {};
    var parent, initialize;
    for(var i=0, len=arguments.length; i<len; ++i) {
        if(typeof arguments[i] == "function") {
            // make the class passed as the first argument the superclass
            if(i == 0 && len > 1) {
                initialize = arguments[i].prototype.initialize;
                // replace the initialize method with an empty function,
                // because we do not want to create a real instance here
                arguments[i].prototype.initialize = function() {};
                // the line below makes sure that the new class has a
                // superclass
                extended = new arguments[i];
                // restore the original initialize method
                if(initialize === undefined) {
                    delete arguments[i].prototype.initialize;
                } else {
                    arguments[i].prototype.initialize = initialize;
                }
            }
            // get the prototype of the superclass
            parent = arguments[i].prototype;
        } else {
            // in this case we're extending with the prototype
            parent = arguments[i];
        }
        OpenLayers.Util.extend(extended, parent);
    }
    Class.prototype = extended;
    return Class;
};

/**
 * Property: isPrototype
 * *Deprecated*.  This is no longer needed and will be removed at 3.0.
 */
OpenLayers.Class.isPrototype = function () {};

/**
 * APIFunction: OpenLayers.create
 * *Deprecated*.  Old method to create an OpenLayers style class.  Use the
 *     <OpenLayers.Class> constructor instead.
 *
 * Returns:
 * An OpenLayers class
 */
OpenLayers.Class.create = function() {
    return function() {
        if (arguments && arguments[0] != OpenLayers.Class.isPrototype) {
            this.initialize.apply(this, arguments);
        }
    };
};


/**
 * APIFunction: inherit
 * *Deprecated*.  Old method to inherit from one or more OpenLayers style
 *     classes.  Use the <OpenLayers.Class> constructor instead.
 *
 * Parameters:
 * class - One or more classes can be provided as arguments
 *
 * Returns:
 * An object prototype
 */
OpenLayers.Class.inherit = function () {
    var superClass = arguments[0];
    var proto = new superClass(OpenLayers.Class.isPrototype);
    for (var i=1, len=arguments.length; i<len; i++) {
        if (typeof arguments[i] == "function") {
            var mixin = arguments[i];
            arguments[i] = new mixin(OpenLayers.Class.isPrototype);
        }
        OpenLayers.Util.extend(proto, arguments[i]);
    }
    return proto;
};
/* ======================================================================
    OpenLayers/Util.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */

/**
 * @requires OpenLayers/Console.js
 */

/**
 * Namespace: Util
 */
OpenLayers.Util = {};

/** 
 * Function: getElement
 * This is the old $() from prototype
 */
OpenLayers.Util.getElement = function() {
    var elements = [];

    for (var i=0, len=arguments.length; i<len; i++) {
        var element = arguments[i];
        if (typeof element == 'string') {
            element = document.getElementById(element);
        }
        if (arguments.length == 1) {
            return element;
        }
        elements.push(element);
    }
    return elements;
};

/**
 * Function: isElement
 * A cross-browser implementation of "e instanceof Element".
 *
 * Parameters:
 * o - {Object} The object to test.
 *
 * Returns:
 * {Boolean}
 */
OpenLayers.Util.isElement = function(o) {
    return !!(o && o.nodeType === 1);
};

/** 
 * Maintain existing definition of $.
 */
if(typeof window.$  === "undefined") {
    window.$ = OpenLayers.Util.getElement;
}

/**
 * APIFunction: extend
 * Copy all properties of a source object to a destination object.  Modifies
 *     the passed in destination object.  Any properties on the source object
 *     that are set to undefined will not be (re)set on the destination object.
 *
 * Parameters:
 * destination - {Object} The object that will be modified
 * source - {Object} The object with properties to be set on the destination
 *
 * Returns:
 * {Object} The destination object.
 */
OpenLayers.Util.extend = function(destination, source) {
    destination = destination || {};
    if(source) {
        for(var property in source) {
            var value = source[property];
            if(value !== undefined) {
                destination[property] = value;
            }
        }

        /**
         * IE doesn't include the toString property when iterating over an object's
         * properties with the for(property in object) syntax.  Explicitly check if
         * the source has its own toString property.
         */

        /*
         * FF/Windows < 2.0.0.13 reports "Illegal operation on WrappedNative
         * prototype object" when calling hawOwnProperty if the source object
         * is an instance of window.Event.
         */

        var sourceIsEvt = typeof window.Event == "function"
                          && source instanceof window.Event;

        if(!sourceIsEvt
           && source.hasOwnProperty && source.hasOwnProperty('toString')) {
            destination.toString = source.toString;
        }
    }
    return destination;
};


/** 
 * Function: removeItem
 * Remove an object from an array. Iterates through the array
 *     to find the item, then removes it.
 *
 * Parameters:
 * array - {Array}
 * item - {Object}
 * 
 * Return
 * {Array} A reference to the array
 */
OpenLayers.Util.removeItem = function(array, item) {
    for(var i = array.length - 1; i >= 0; i--) {
        if(array[i] == item) {
            array.splice(i,1);
            //break;more than once??
        }
    }
    return array;
};

/**
 * Function: clearArray
 * *Deprecated*. This function will disappear in 3.0.
 * Please use "array.length = 0" instead.
 * 
 * Parameters:
 * array - {Array}
 */
OpenLayers.Util.clearArray = function(array) {
    OpenLayers.Console.warn(
        OpenLayers.i18n(
            "methodDeprecated", {'newMethod': 'array = []'}
        )
    );
    array.length = 0;
};

/** 
 * Function: indexOf
 * Seems to exist already in FF, but not in MOZ.
 * 
 * Parameters:
 * array - {Array}
 * obj - {Object}
 * 
 * Returns:
 * {Integer} The index at, which the first object was found in the array.
 *           If not found, returns -1.
 */
OpenLayers.Util.indexOf = function(array, obj) {
    // use the build-in function if available.
    if (typeof array.indexOf == "function") {
        return array.indexOf(obj);
    } else {
        for (var i = 0, len = array.length; i < len; i++) {
            if (array[i] == obj) {
                return i;
            }
        }
        return -1;   
    }
};



/**
 * Function: modifyDOMElement
 * 
 * Modifies many properties of a DOM element all at once.  Passing in 
 * null to an individual parameter will avoid setting the attribute.
 *
 * Parameters:
 * id - {String} The element id attribute to set.
 * px - {<OpenLayers.Pixel>} The left and top style position.
 * sz - {<OpenLayers.Size>}  The width and height style attributes.
 * position - {String}       The position attribute.  eg: absolute, 
 *                           relative, etc.
 * border - {String}         The style.border attribute.  eg:
 *                           solid black 2px
 * overflow - {String}       The style.overview attribute.  
 * opacity - {Float}         Fractional value (0.0 - 1.0)
 */
OpenLayers.Util.modifyDOMElement = function(element, id, px, sz, position, 
                                            border, overflow, opacity) {

    if (id) {
        element.id = id;
    }
    if (px) {
        element.style.left = px.x + "px";
        element.style.top = px.y + "px";
    }
    if (sz) {
        element.style.width = sz.w + "px";
        element.style.height = sz.h + "px";
    }
    if (position) {
        element.style.position = position;
    }
    if (border) {
        element.style.border = border;
    }
    if (overflow) {
        element.style.overflow = overflow;
    }
    if (parseFloat(opacity) >= 0.0 && parseFloat(opacity) < 1.0) {
        element.style.filter = 'alpha(opacity=' + (opacity * 100) + ')';
        element.style.opacity = opacity;
    } else if (parseFloat(opacity) == 1.0) {
        element.style.filter = '';
        element.style.opacity = '';
    }
};

/** 
 * Function: createDiv
 * Creates a new div and optionally set some standard attributes.
 * Null may be passed to each parameter if you do not wish to
 * set a particular attribute.
 * Note - zIndex is NOT set on the resulting div.
 * 
 * Parameters:
 * id - {String} An identifier for this element.  If no id is
 *               passed an identifier will be created 
 *               automatically.
 * px - {<OpenLayers.Pixel>} The element left and top position. 
 * sz - {<OpenLayers.Size>} The element width and height.
 * imgURL - {String} A url pointing to an image to use as a 
 *                   background image.
 * position - {String} The style.position value. eg: absolute,
 *                     relative etc.
 * border - {String} The the style.border value. 
 *                   eg: 2px solid black
 * overflow - {String} The style.overflow value. Eg. hidden
 * opacity - {Float} Fractional value (0.0 - 1.0)
 * 
 * Returns: 
 * {DOMElement} A DOM Div created with the specified attributes.
 */
OpenLayers.Util.createDiv = function(id, px, sz, imgURL, position, 
                                     border, overflow, opacity) {

    var dom = document.createElement('div');

    if (imgURL) {
        dom.style.backgroundImage = 'url(' + imgURL + ')';
    }

    //set generic properties
    if (!id) {
        id = OpenLayers.Util.createUniqueID("OpenLayersDiv");
    }
    if (!position) {
        position = "absolute";
    }
    OpenLayers.Util.modifyDOMElement(dom, id, px, sz, position, 
                                     border, overflow, opacity);

    return dom;
};

/**
 * Function: createImage
 * Creates an img element with specific attribute values.
 *  
 * Parameters:
 * id - {String} The id field for the img.  If none assigned one will be
 *               automatically generated.
 * px - {<OpenLayers.Pixel>} The left and top positions.
 * sz - {<OpenLayers.Size>} The style.width and style.height values.
 * imgURL - {String} The url to use as the image source.
 * position - {String} The style.position value.
 * border - {String} The border to place around the image.
 * opacity - {Float} Fractional value (0.0 - 1.0)
 * delayDisplay - {Boolean} If true waits until the image has been
 *                          loaded.
 * 
 * Returns:
 * {DOMElement} A DOM Image created with the specified attributes.
 */
OpenLayers.Util.createImage = function(id, px, sz, imgURL, position, border,
                                       opacity, delayDisplay) {

    var image = document.createElement("img");

    //set generic properties
    if (!id) {
        id = OpenLayers.Util.createUniqueID("OpenLayersDiv");
    }
    if (!position) {
        position = "relative";
    }
    OpenLayers.Util.modifyDOMElement(image, id, px, sz, position, 
                                     border, null, opacity);

    if(delayDisplay) {
        image.style.display = "none";
        OpenLayers.Event.observe(image, "load", 
            OpenLayers.Function.bind(OpenLayers.Util.onImageLoad, image));
        OpenLayers.Event.observe(image, "error", 
            OpenLayers.Function.bind(OpenLayers.Util.onImageLoadError, image));
        
    }
    
    //set special properties
    image.style.alt = id;
    image.galleryImg = "no";
    if (imgURL) {
        image.src = imgURL;
    }


        
    return image;
};

/**
 * Function: setOpacity
 * *Deprecated*.  This function has been deprecated. Instead, please use 
 *     <OpenLayers.Util.modifyDOMElement> 
 *     or 
 *     <OpenLayers.Util.modifyAlphaImageDiv>
 * 
 * Set the opacity of a DOM Element
 *     Note that for this function to work in IE, elements must "have layout"
 *     according to:
 *     http://msdn.microsoft.com/workshop/author/dhtml/reference/properties/haslayout.asp
 *
 * Parameters:
 * element - {DOMElement} Set the opacity on this DOM element
 * opacity - {Float} Opacity value (0.0 - 1.0)
 */
OpenLayers.Util.setOpacity = function(element, opacity) {
    OpenLayers.Util.modifyDOMElement(element, null, null, null,
                                     null, null, null, opacity);
};

/**
 * Function: onImageLoad
 * Bound to image load events.  For all images created with <createImage> or
 *     <createAlphaImageDiv>, this function will be bound to the load event.
 */
OpenLayers.Util.onImageLoad = function() {
    // The complex check here is to solve issues described in #480.
    // Every time a map view changes, it increments the 'viewRequestID' 
    // property. As the requests for the images for the new map view are sent
    // out, they are tagged with this unique viewRequestID. 
    // 
    // If an image has no viewRequestID property set, we display it regardless, 
    // but if it does have a viewRequestID property, we check that it matches 
    // the viewRequestID set on the map.
    // 
    // If the viewRequestID on the map has changed, that means that the user
    // has changed the map view since this specific request was sent out, and
    // therefore this tile does not need to be displayed (so we do not execute
    // this code that turns its display on).
    //
    if (!this.viewRequestID ||
        (this.map && this.viewRequestID == this.map.viewRequestID)) { 
        this.style.display = "";  
    }
    OpenLayers.Element.removeClass(this, "olImageLoadError");
};

/**
 * Property: IMAGE_RELOAD_ATTEMPTS
 * {Integer} How many times should we try to reload an image before giving up?
 *           Default is 0
 */
OpenLayers.IMAGE_RELOAD_ATTEMPTS = 0;

/**
 * Function: onImageLoadError 
 */
OpenLayers.Util.onImageLoadError = function() {
    this._attempts = (this._attempts) ? (this._attempts + 1) : 1;
    if (this._attempts <= OpenLayers.IMAGE_RELOAD_ATTEMPTS) {
        var urls = this.urls;
        if (urls && urls instanceof Array && urls.length > 1){
            var src = this.src.toString();
            var current_url, k;
            for (k = 0; current_url = urls[k]; k++){
                if(src.indexOf(current_url) != -1){
                    break;
                }
            }
            var guess = Math.floor(urls.length * Math.random());
            var new_url = urls[guess];
            k = 0;
            while(new_url == current_url && k++ < 4){
                guess = Math.floor(urls.length * Math.random());
                new_url = urls[guess];
            }
            this.src = src.replace(current_url, new_url);
        } else {
            this.src = this.src;
        }
    } else {
        OpenLayers.Element.addClass(this, "olImageLoadError");
    }
    this.style.display = "";
};

/**
 * Property: alphaHackNeeded
 * {Boolean} true if the png alpha hack is necessary and possible, false otherwise.
 */
OpenLayers.Util.alphaHackNeeded = null;

/**
 * Function: alphaHack
 * Checks whether it's necessary (and possible) to use the png alpha
 * hack which allows alpha transparency for png images under Internet
 * Explorer.
 * 
 * Returns:
 * {Boolean} true if the png alpha hack is necessary and possible, false otherwise.
 */
OpenLayers.Util.alphaHack = function() {
    if (OpenLayers.Util.alphaHackNeeded == null) {
        var arVersion = navigator.appVersion.split("MSIE");
        var version = parseFloat(arVersion[1]);
        var filter = false;
    
        // IEs4Lin dies when trying to access document.body.filters, because 
        // the property is there, but requires a DLL that can't be provided. This
        // means that we need to wrap this in a try/catch so that this can
        // continue.
    
        try { 
            filter = !!(document.body.filters);
        } catch (e) {}    
    
        OpenLayers.Util.alphaHackNeeded = (filter && 
                                           (version >= 5.5) && (version < 7));
    }
    return OpenLayers.Util.alphaHackNeeded;
};

/** 
 * Function: modifyAlphaImageDiv
 * 
 * div - {DOMElement} Div containing Alpha-adjusted Image
 * id - {String}
 * px - {<OpenLayers.Pixel>}
 * sz - {<OpenLayers.Size>}
 * imgURL - {String}
 * position - {String}
 * border - {String}
 * sizing {String} 'crop', 'scale', or 'image'. Default is "scale"
 * opacity - {Float} Fractional value (0.0 - 1.0)
 */ 
OpenLayers.Util.modifyAlphaImageDiv = function(div, id, px, sz, imgURL, 
                                               position, border, sizing, 
                                               opacity) {

    OpenLayers.Util.modifyDOMElement(div, id, px, sz, position,
                                     null, null, opacity);

    var img = div.childNodes[0];

    if (imgURL) {
        img.src = imgURL;
    }
    OpenLayers.Util.modifyDOMElement(img, div.id + "_innerImage", null, sz, 
                                     "relative", border);
    
    if (OpenLayers.Util.alphaHack()) {
        if(div.style.display != "none") {
            div.style.display = "inline-block";
        }
        if (sizing == null) {
            sizing = "scale";
        }
        
        div.style.filter = "progid:DXImageTransform.Microsoft" +
                           ".AlphaImageLoader(src='" + img.src + "', " +
                           "sizingMethod='" + sizing + "')";
        if (parseFloat(div.style.opacity) >= 0.0 && 
            parseFloat(div.style.opacity) < 1.0) {
            div.style.filter += " alpha(opacity=" + div.style.opacity * 100 + ")";
        }

        img.style.filter = "alpha(opacity=0)";
    }
};

/** 
 * Function: createAlphaImageDiv
 * 
 * id - {String}
 * px - {<OpenLayers.Pixel>}
 * sz - {<OpenLayers.Size>}
 * imgURL - {String}
 * position - {String}
 * border - {String}
 * sizing - {String} 'crop', 'scale', or 'image'. Default is "scale"
 * opacity - {Float} Fractional value (0.0 - 1.0)
 * delayDisplay - {Boolean} If true waits until the image has been
 *                          loaded.
 * 
 * Returns:
 * {DOMElement} A DOM Div created with a DOM Image inside it. If the hack is 
 *              needed for transparency in IE, it is added.
 */ 
OpenLayers.Util.createAlphaImageDiv = function(id, px, sz, imgURL, 
                                               position, border, sizing, 
                                               opacity, delayDisplay) {
    
    var div = OpenLayers.Util.createDiv();
    var img = OpenLayers.Util.createImage(null, null, null, null, null, null, 
                                          null, false);
    div.appendChild(img);

    if (delayDisplay) {
        img.style.display = "none";
        OpenLayers.Event.observe(img, "load",
            OpenLayers.Function.bind(OpenLayers.Util.onImageLoad, div));
        OpenLayers.Event.observe(img, "error",
            OpenLayers.Function.bind(OpenLayers.Util.onImageLoadError, div));
    }

    OpenLayers.Util.modifyAlphaImageDiv(div, id, px, sz, imgURL, position, 
                                        border, sizing, opacity);
    
    return div;
};


/** 
 * Function: upperCaseObject
 * Creates a new hashtable and copies over all the keys from the 
 *     passed-in object, but storing them under an uppercased
 *     version of the key at which they were stored.
 * 
 * Parameters: 
 * object - {Object}
 * 
 * Returns: 
 * {Object} A new Object with all the same keys but uppercased
 */
OpenLayers.Util.upperCaseObject = function (object) {
    var uObject = {};
    for (var key in object) {
        uObject[key.toUpperCase()] = object[key];
    }
    return uObject;
};

/** 
 * Function: applyDefaults
 * Takes an object and copies any properties that don't exist from
 *     another properties, by analogy with OpenLayers.Util.extend() from
 *     Prototype.js.
 * 
 * Parameters:
 * to - {Object} The destination object.
 * from - {Object} The source object.  Any properties of this object that
 *     are undefined in the to object will be set on the to object.
 *
 * Returns:
 * {Object} A reference to the to object.  Note that the to argument is modified
 *     in place and returned by this function.
 */
OpenLayers.Util.applyDefaults = function (to, from) {
    to = to || {};
    /*
     * FF/Windows < 2.0.0.13 reports "Illegal operation on WrappedNative
     * prototype object" when calling hawOwnProperty if the source object is an
     * instance of window.Event.
     */
    var fromIsEvt = typeof window.Event == "function"
                    && from instanceof window.Event;

    for (var key in from) {
        if (to[key] === undefined ||
            (!fromIsEvt && from.hasOwnProperty
             && from.hasOwnProperty(key) && !to.hasOwnProperty(key))) {
            to[key] = from[key];
        }
    }
    /**
     * IE doesn't include the toString property when iterating over an object's
     * properties with the for(property in object) syntax.  Explicitly check if
     * the source has its own toString property.
     */
    if(!fromIsEvt && from && from.hasOwnProperty
       && from.hasOwnProperty('toString') && !to.hasOwnProperty('toString')) {
        to.toString = from.toString;
    }
    
    return to;
};

/**
 * Function: getParameterString
 * 
 * Parameters:
 * params - {Object}
 * 
 * Returns:
 * {String} A concatenation of the properties of an object in 
 *          http parameter notation. 
 *          (ex. <i>"key1=value1&key2=value2&key3=value3"</i>)
 *          If a parameter is actually a list, that parameter will then
 *          be set to a comma-seperated list of values (foo,bar) instead
 *          of being URL escaped (foo%3Abar). 
 */
OpenLayers.Util.getParameterString = function(params) {
    var paramsArray = [];
    
    for (var key in params) {
      var value = params[key];
      if ((value != null) && (typeof value != 'function')) {
        var encodedValue;
        if (typeof value == 'object' && value.constructor == Array) {
          /* value is an array; encode items and separate with "," */
          var encodedItemArray = [];
          var item;
          for (var itemIndex=0, len=value.length; itemIndex<len; itemIndex++) {
            item = value[itemIndex];
            encodedItemArray.push(encodeURIComponent(
                (item === null || item === undefined) ? "" : item)
            );
          }
          encodedValue = encodedItemArray.join(",");
        }
        else {
          /* value is a string; simply encode */
          encodedValue = encodeURIComponent(value);
        }
        paramsArray.push(encodeURIComponent(key) + "=" + encodedValue);
      }
    }
    
    return paramsArray.join("&");
};

/**
 * Function: urlAppend
 * Appends a parameter string to a url. This function includes the logic for
 * using the appropriate character (none, & or ?) to append to the url before
 * appending the param string.
 * 
 * Parameters:
 * url - {String} The url to append to
 * paramStr - {String} The param string to append
 * 
 * Returns:
 * {String} The new url
 */
OpenLayers.Util.urlAppend = function(url, paramStr) {
    var newUrl = url;
    if(paramStr) {
        var parts = (url + " ").split(/[?&]/);
        newUrl += (parts.pop() === " " ?
            paramStr :
            parts.length ? "&" + paramStr : "?" + paramStr);
    }
    return newUrl;
};

/**
 * Property: ImgPath
 * {String} Default is ''.
 */
OpenLayers.ImgPath = '';

/** 
 * Function: getImagesLocation
 * 
 * Returns:
 * {String} The fully formatted image location string
 */
OpenLayers.Util.getImagesLocation = function() {
    return OpenLayers.ImgPath || (OpenLayers._getScriptLocation() + "img/");
};


/** 
 * Function: Try
 * Execute functions until one of them doesn't throw an error. 
 *     Capitalized because "try" is a reserved word in JavaScript.
 *     Taken directly from OpenLayers.Util.Try()
 * 
 * Parameters:
 * [*] - {Function} Any number of parameters may be passed to Try()
 *    It will attempt to execute each of them until one of them 
 *    successfully executes. 
 *    If none executes successfully, returns null.
 * 
 * Returns:
 * {*} The value returned by the first successfully executed function.
 */
OpenLayers.Util.Try = function() {
    var returnValue = null;

    for (var i=0, len=arguments.length; i<len; i++) {
      var lambda = arguments[i];
      try {
        returnValue = lambda();
        break;
      } catch (e) {}
    }

    return returnValue;
};


/** 
 * Function: getNodes
 * 
 * These could/should be made namespace aware?
 * 
 * Parameters:
 * p - {}
 * tagName - {String}
 * 
 * Returns:
 * {Array}
 */
OpenLayers.Util.getNodes=function(p, tagName) {
    var nodes = OpenLayers.Util.Try(
        function () {
            return OpenLayers.Util._getNodes(p.documentElement.childNodes,
                                            tagName);
        },
        function () {
            return OpenLayers.Util._getNodes(p.childNodes, tagName);
        }
    );
    return nodes;
};

/**
 * Function: _getNodes
 * 
 * Parameters:
 * nodes - {Array}
 * tagName - {String}
 * 
 * Returns:
 * {Array}
 */
OpenLayers.Util._getNodes=function(nodes, tagName) {
    var retArray = [];
    for (var i=0, len=nodes.length; i<len; i++) {
        if (nodes[i].nodeName==tagName) {
            retArray.push(nodes[i]);
        }
    }

    return retArray;
};



/**
 * Function: getTagText
 * 
 * Parameters:
 * parent - {}
 * item - {String}
 * index - {Integer}
 * 
 * Returns:
 * {String}
 */
OpenLayers.Util.getTagText = function (parent, item, index) {
    var result = OpenLayers.Util.getNodes(parent, item);
    if (result && (result.length > 0))
    {
        if (!index) {
            index=0;
        }
        if (result[index].childNodes.length > 1) {
            return result.childNodes[1].nodeValue; 
        }
        else if (result[index].childNodes.length == 1) {
            return result[index].firstChild.nodeValue; 
        }
    } else { 
        return ""; 
    }
};

/**
 * Function: getXmlNodeValue
 * 
 * Parameters:
 * node - {XMLNode}
 * 
 * Returns:
 * {String} The text value of the given node, without breaking in firefox or IE
 */
OpenLayers.Util.getXmlNodeValue = function(node) {
    var val = null;
    OpenLayers.Util.Try( 
        function() {
            val = node.text;
            if (!val) {
                val = node.textContent;
            }
            if (!val) {
                val = node.firstChild.nodeValue;
            }
        }, 
        function() {
            val = node.textContent;
        }); 
    return val;
};

/** 
 * Function: mouseLeft
 * 
 * Parameters:
 * evt - {Event}
 * div - {HTMLDivElement}
 * 
 * Returns:
 * {Boolean}
 */
OpenLayers.Util.mouseLeft = function (evt, div) {
    // start with the element to which the mouse has moved
    var target = (evt.relatedTarget) ? evt.relatedTarget : evt.toElement;
    // walk up the DOM tree.
    while (target != div && target != null) {
        target = target.parentNode;
    }
    // if the target we stop at isn't the div, then we've left the div.
    return (target != div);
};

/**
 * Property: precision
 * {Number} The number of significant digits to retain to avoid
 * floating point precision errors.
 *
 * We use 14 as a "safe" default because, although IEEE 754 double floats
 * (standard on most modern operating systems) support up to about 16
 * significant digits, 14 significant digits are sufficient to represent
 * sub-millimeter accuracy in any coordinate system that anyone is likely to
 * use with OpenLayers.
 *
 * If DEFAULT_PRECISION is set to 0, the original non-truncating behavior
 * of OpenLayers <2.8 is preserved. Be aware that this will cause problems
 * with certain projections, e.g. spherical Mercator.
 *
 */
OpenLayers.Util.DEFAULT_PRECISION = 14;

/**
 * Function: toFloat
 * Convenience method to cast an object to a Number, rounded to the
 * desired floating point precision.
 *
 * Parameters:
 * number    - {Number} The number to cast and round.
 * precision - {Number} An integer suitable for use with
 *      Number.toPrecision(). Defaults to OpenLayers.Util.DEFAULT_PRECISION.
 *      If set to 0, no rounding is performed.
 *
 * Returns:
 * {Number} The cast, rounded number.
 */
OpenLayers.Util.toFloat = function (number, precision) {
    if (precision == null) {
        precision = OpenLayers.Util.DEFAULT_PRECISION;
    }
    var number;
    if (precision == 0) {
        number = parseFloat(number);
    } else {
        number = parseFloat(parseFloat(number).toPrecision(precision));
    }
    return number;
};

/**
 * Function: rad
 * 
 * Parameters:
 * x - {Float}
 * 
 * Returns:
 * {Float}
 */
OpenLayers.Util.rad = function(x) {return x*Math.PI/180;};

/**
 * Function: distVincenty
 * Given two objects representing points with geographic coordinates, this
 *     calculates the distance between those points on the surface of an
 *     ellipsoid.
 * 
 * Parameters:
 * p1 - {<OpenLayers.LonLat>} (or any object with both .lat, .lon properties)
 * p2 - {<OpenLayers.LonLat>} (or any object with both .lat, .lon properties)
 * 
 * Returns:
 * {Float} The distance (in km) between the two input points as measured on an
 *     ellipsoid.  Note that the input point objects must be in geographic
 *     coordinates (decimal degrees) and the return distance is in kilometers.
 */
OpenLayers.Util.distVincenty=function(p1, p2) {
    var a = 6378137, b = 6356752.3142,  f = 1/298.257223563;
    var L = OpenLayers.Util.rad(p2.lon - p1.lon);
    var U1 = Math.atan((1-f) * Math.tan(OpenLayers.Util.rad(p1.lat)));
    var U2 = Math.atan((1-f) * Math.tan(OpenLayers.Util.rad(p2.lat)));
    var sinU1 = Math.sin(U1), cosU1 = Math.cos(U1);
    var sinU2 = Math.sin(U2), cosU2 = Math.cos(U2);
    var lambda = L, lambdaP = 2*Math.PI;
    var iterLimit = 20;
    while (Math.abs(lambda-lambdaP) > 1e-12 && --iterLimit>0) {
        var sinLambda = Math.sin(lambda), cosLambda = Math.cos(lambda);
        var sinSigma = Math.sqrt((cosU2*sinLambda) * (cosU2*sinLambda) +
        (cosU1*sinU2-sinU1*cosU2*cosLambda) * (cosU1*sinU2-sinU1*cosU2*cosLambda));
        if (sinSigma==0) {
            return 0;  // co-incident points
        }
        var cosSigma = sinU1*sinU2 + cosU1*cosU2*cosLambda;
        var sigma = Math.atan2(sinSigma, cosSigma);
        var alpha = Math.asin(cosU1 * cosU2 * sinLambda / sinSigma);
        var cosSqAlpha = Math.cos(alpha) * Math.cos(alpha);
        var cos2SigmaM = cosSigma - 2*sinU1*sinU2/cosSqAlpha;
        var C = f/16*cosSqAlpha*(4+f*(4-3*cosSqAlpha));
        lambdaP = lambda;
        lambda = L + (1-C) * f * Math.sin(alpha) *
        (sigma + C*sinSigma*(cos2SigmaM+C*cosSigma*(-1+2*cos2SigmaM*cos2SigmaM)));
    }
    if (iterLimit==0) {
        return NaN;  // formula failed to converge
    }
    var uSq = cosSqAlpha * (a*a - b*b) / (b*b);
    var A = 1 + uSq/16384*(4096+uSq*(-768+uSq*(320-175*uSq)));
    var B = uSq/1024 * (256+uSq*(-128+uSq*(74-47*uSq)));
    var deltaSigma = B*sinSigma*(cos2SigmaM+B/4*(cosSigma*(-1+2*cos2SigmaM*cos2SigmaM)-
        B/6*cos2SigmaM*(-3+4*sinSigma*sinSigma)*(-3+4*cos2SigmaM*cos2SigmaM)));
    var s = b*A*(sigma-deltaSigma);
    var d = s.toFixed(3)/1000; // round to 1mm precision
    return d;
};

/**
 * Function: getParameters
 * Parse the parameters from a URL or from the current page itself into a 
 *     JavaScript Object. Note that parameter values with commas are separated
 *     out into an Array.
 * 
 * Parameters:
 * url - {String} Optional url used to extract the query string.
 *                If null, query string is taken from page location.
 * 
 * Returns:
 * {Object} An object of key/value pairs from the query string.
 */
OpenLayers.Util.getParameters = function(url) {
    // if no url specified, take it from the location bar
    url = url || window.location.href;

    //parse out parameters portion of url string
    var paramsString = "";
    if (OpenLayers.String.contains(url, '?')) {
        var start = url.indexOf('?') + 1;
        var end = OpenLayers.String.contains(url, "#") ?
                    url.indexOf('#') : url.length;
        paramsString = url.substring(start, end);
    }

    var parameters = {};
    var pairs = paramsString.split(/[&;]/);
    for(var i=0, len=pairs.length; i<len; ++i) {
        var keyValue = pairs[i].split('=');
        if (keyValue[0]) {
            var key = decodeURIComponent(keyValue[0]);
            var value = keyValue[1] || ''; //empty string if no value

            //decode individual values (being liberal by replacing "+" with " ")
            value = decodeURIComponent(value.replace(/\+/g, " ")).split(",");

            //if there's only one value, do not return as array                    
            if (value.length == 1) {
                value = value[0];
            }                
            
            parameters[key] = value;
         }
     }
    return parameters;
};

/**
 * Function: getArgs
 * *Deprecated*.  Will be removed in 3.0.  Please use instead
 *     <OpenLayers.Util.getParameters>
 * 
 * Parameters:
 * url - {String} Optional url used to extract the query string.
 *                If null, query string is taken from page location.
 * 
 * Returns:
 * {Object} An object of key/value pairs from the query string.
 */
OpenLayers.Util.getArgs = function(url) {
    OpenLayers.Console.warn(
        OpenLayers.i18n(
            "methodDeprecated", {'newMethod': 'OpenLayers.Util.getParameters'}
        )
    );
    return OpenLayers.Util.getParameters(url);
};

/**
 * Property: lastSeqID
 * {Integer} The ever-incrementing count variable.
 *           Used for generating unique ids.
 */
OpenLayers.Util.lastSeqID = 0;

/**
 * Function: createUniqueID
 * Create a unique identifier for this session.  Each time this function
 *     is called, a counter is incremented.  The return will be the optional
 *     prefix (defaults to "id_") appended with the counter value.
 * 
 * Parameters:
 * prefix {String} Optionsal string to prefix unique id. Default is "id_".
 * 
 * Returns:
 * {String} A unique id string, built on the passed in prefix.
 */
OpenLayers.Util.createUniqueID = function(prefix) {
    if (prefix == null) {
        prefix = "id_";
    }
    OpenLayers.Util.lastSeqID += 1; 
    return prefix + OpenLayers.Util.lastSeqID;        
};

/**
 * Constant: INCHES_PER_UNIT
 * {Object} Constant inches per unit -- borrowed from MapServer mapscale.c
 * derivation of nautical miles from http://en.wikipedia.org/wiki/Nautical_mile
 * Includes the full set of units supported by CS-MAP (http://trac.osgeo.org/csmap/)
 * and PROJ.4 (http://trac.osgeo.org/proj/)
 * The hardcoded table is maintain in a CS-MAP source code module named CSdataU.c
 * The hardcoded table of PROJ.4 units are in pj_units.c.
 */
OpenLayers.INCHES_PER_UNIT = { 
    'inches': 1.0,
    'ft': 12.0,
    'mi': 63360.0,
    'm': 39.3701,
    'km': 39370.1,
    'dd': 4374754,
    'yd': 36
};
OpenLayers.INCHES_PER_UNIT["in"]= OpenLayers.INCHES_PER_UNIT.inches;
OpenLayers.INCHES_PER_UNIT["degrees"] = OpenLayers.INCHES_PER_UNIT.dd;
OpenLayers.INCHES_PER_UNIT["nmi"] = 1852 * OpenLayers.INCHES_PER_UNIT.m;

// Units from CS-Map
OpenLayers.METERS_PER_INCH = 0.02540005080010160020;
OpenLayers.Util.extend(OpenLayers.INCHES_PER_UNIT, {
    "Inch": OpenLayers.INCHES_PER_UNIT.inches,
    "Meter": 1.0 / OpenLayers.METERS_PER_INCH,   //EPSG:9001
    "Foot": 0.30480060960121920243 / OpenLayers.METERS_PER_INCH,   //EPSG:9003
    "IFoot": 0.30480000000000000000 / OpenLayers.METERS_PER_INCH,   //EPSG:9002
    "ClarkeFoot": 0.3047972651151 / OpenLayers.METERS_PER_INCH,   //EPSG:9005
    "SearsFoot": 0.30479947153867624624 / OpenLayers.METERS_PER_INCH,   //EPSG:9041
    "GoldCoastFoot": 0.30479971018150881758 / OpenLayers.METERS_PER_INCH,   //EPSG:9094
    "IInch": 0.02540000000000000000 / OpenLayers.METERS_PER_INCH,
    "MicroInch": 0.00002540000000000000 / OpenLayers.METERS_PER_INCH,
    "Mil": 0.00000002540000000000 / OpenLayers.METERS_PER_INCH,
    "Centimeter": 0.01000000000000000000 / OpenLayers.METERS_PER_INCH,
    "Kilometer": 1000.00000000000000000000 / OpenLayers.METERS_PER_INCH,   //EPSG:9036
    "Yard": 0.91440182880365760731 / OpenLayers.METERS_PER_INCH,
    "SearsYard": 0.914398414616029 / OpenLayers.METERS_PER_INCH,   //EPSG:9040
    "IndianYard": 0.91439853074444079983 / OpenLayers.METERS_PER_INCH,   //EPSG:9084
    "IndianYd37": 0.91439523 / OpenLayers.METERS_PER_INCH,   //EPSG:9085
    "IndianYd62": 0.9143988 / OpenLayers.METERS_PER_INCH,   //EPSG:9086
    "IndianYd75": 0.9143985 / OpenLayers.METERS_PER_INCH,   //EPSG:9087
    "IndianFoot": 0.30479951 / OpenLayers.METERS_PER_INCH,   //EPSG:9080
    "IndianFt37": 0.30479841 / OpenLayers.METERS_PER_INCH,   //EPSG:9081
    "IndianFt62": 0.3047996 / OpenLayers.METERS_PER_INCH,   //EPSG:9082
    "IndianFt75": 0.3047995 / OpenLayers.METERS_PER_INCH,   //EPSG:9083
    "Mile": 1609.34721869443738887477 / OpenLayers.METERS_PER_INCH,
    "IYard": 0.91440000000000000000 / OpenLayers.METERS_PER_INCH,   //EPSG:9096
    "IMile": 1609.34400000000000000000 / OpenLayers.METERS_PER_INCH,   //EPSG:9093
    "NautM": 1852.00000000000000000000 / OpenLayers.METERS_PER_INCH,   //EPSG:9030
    "Lat-66": 110943.316488932731 / OpenLayers.METERS_PER_INCH,
    "Lat-83": 110946.25736872234125 / OpenLayers.METERS_PER_INCH,
    "Decimeter": 0.10000000000000000000 / OpenLayers.METERS_PER_INCH,
    "Millimeter": 0.00100000000000000000 / OpenLayers.METERS_PER_INCH,
    "Dekameter": 10.00000000000000000000 / OpenLayers.METERS_PER_INCH,
    "Decameter": 10.00000000000000000000 / OpenLayers.METERS_PER_INCH,
    "Hectometer": 100.00000000000000000000 / OpenLayers.METERS_PER_INCH,
    "GermanMeter": 1.0000135965 / OpenLayers.METERS_PER_INCH,   //EPSG:9031
    "CaGrid": 0.999738 / OpenLayers.METERS_PER_INCH,
    "ClarkeChain": 20.1166194976 / OpenLayers.METERS_PER_INCH,   //EPSG:9038
    "GunterChain": 20.11684023368047 / OpenLayers.METERS_PER_INCH,   //EPSG:9033
    "BenoitChain": 20.116782494375872 / OpenLayers.METERS_PER_INCH,   //EPSG:9062
    "SearsChain": 20.11676512155 / OpenLayers.METERS_PER_INCH,   //EPSG:9042
    "ClarkeLink": 0.201166194976 / OpenLayers.METERS_PER_INCH,   //EPSG:9039
    "GunterLink": 0.2011684023368047 / OpenLayers.METERS_PER_INCH,   //EPSG:9034
    "BenoitLink": 0.20116782494375872 / OpenLayers.METERS_PER_INCH,   //EPSG:9063
    "SearsLink": 0.2011676512155 / OpenLayers.METERS_PER_INCH,   //EPSG:9043
    "Rod": 5.02921005842012 / OpenLayers.METERS_PER_INCH,
    "IntnlChain": 20.1168 / OpenLayers.METERS_PER_INCH,   //EPSG:9097
    "IntnlLink": 0.201168 / OpenLayers.METERS_PER_INCH,   //EPSG:9098
    "Perch": 5.02921005842012 / OpenLayers.METERS_PER_INCH,
    "Pole": 5.02921005842012 / OpenLayers.METERS_PER_INCH,
    "Furlong": 201.1684023368046 / OpenLayers.METERS_PER_INCH,
    "Rood": 3.778266898 / OpenLayers.METERS_PER_INCH,
    "CapeFoot": 0.3047972615 / OpenLayers.METERS_PER_INCH,
    "Brealey": 375.00000000000000000000 / OpenLayers.METERS_PER_INCH,
    "ModAmFt": 0.304812252984505969011938 / OpenLayers.METERS_PER_INCH,
    "Fathom": 1.8288 / OpenLayers.METERS_PER_INCH,
    "NautM-UK": 1853.184 / OpenLayers.METERS_PER_INCH,
    "50kilometers": 50000.0 / OpenLayers.METERS_PER_INCH,
    "150kilometers": 150000.0 / OpenLayers.METERS_PER_INCH
});

//unit abbreviations supported by PROJ.4
OpenLayers.Util.extend(OpenLayers.INCHES_PER_UNIT, {
    "mm": OpenLayers.INCHES_PER_UNIT["Meter"] / 1000.0,
    "cm": OpenLayers.INCHES_PER_UNIT["Meter"] / 100.0,
    "dm": OpenLayers.INCHES_PER_UNIT["Meter"] * 100.0,
    "km": OpenLayers.INCHES_PER_UNIT["Meter"] * 1000.0,
    "kmi": OpenLayers.INCHES_PER_UNIT["nmi"],    //International Nautical Mile
    "fath": OpenLayers.INCHES_PER_UNIT["Fathom"], //International Fathom
    "ch": OpenLayers.INCHES_PER_UNIT["IntnlChain"],  //International Chain
    "link": OpenLayers.INCHES_PER_UNIT["IntnlLink"], //International Link
    "us-in": OpenLayers.INCHES_PER_UNIT["inches"], //U.S. Surveyor's Inch
    "us-ft": OpenLayers.INCHES_PER_UNIT["Foot"],	//U.S. Surveyor's Foot
    "us-yd": OpenLayers.INCHES_PER_UNIT["Yard"],	//U.S. Surveyor's Yard
    "us-ch": OpenLayers.INCHES_PER_UNIT["GunterChain"], //U.S. Surveyor's Chain
    "us-mi": OpenLayers.INCHES_PER_UNIT["Mile"],   //U.S. Surveyor's Statute Mile
    "ind-yd": OpenLayers.INCHES_PER_UNIT["IndianYd37"],  //Indian Yard
    "ind-ft": OpenLayers.INCHES_PER_UNIT["IndianFt37"],  //Indian Foot
    "ind-ch": 20.11669506 / OpenLayers.METERS_PER_INCH  //Indian Chain
});

/** 
 * Constant: DOTS_PER_INCH
 * {Integer} 72 (A sensible default)
 */
OpenLayers.DOTS_PER_INCH = 72;

/**
 * Function: normalizeScale
 * 
 * Parameters:
 * scale - {float}
 * 
 * Returns:
 * {Float} A normalized scale value, in 1 / X format. 
 *         This means that if a value less than one ( already 1/x) is passed
 *         in, it just returns scale directly. Otherwise, it returns 
 *         1 / scale
 */
OpenLayers.Util.normalizeScale = function (scale) {
    var normScale = (scale > 1.0) ? (1.0 / scale) 
                                  : scale;
    return normScale;
};

/**
 * Function: getResolutionFromScale
 * 
 * Parameters:
 * scale - {Float}
 * units - {String} Index into OpenLayers.INCHES_PER_UNIT hashtable.
 *                  Default is degrees
 * 
 * Returns:
 * {Float} The corresponding resolution given passed-in scale and unit 
 *     parameters.  If the given scale is falsey, the returned resolution will
 *     be undefined.
 */
OpenLayers.Util.getResolutionFromScale = function (scale, units) {
    var resolution;
    if (scale) {
        if (units == null) {
            units = "degrees";
        }
        var normScale = OpenLayers.Util.normalizeScale(scale);
        resolution = 1 / (normScale * OpenLayers.INCHES_PER_UNIT[units]
                                        * OpenLayers.DOTS_PER_INCH);        
    }
    return resolution;
};

/**
 * Function: getScaleFromResolution
 * 
 * Parameters:
 * resolution - {Float}
 * units - {String} Index into OpenLayers.INCHES_PER_UNIT hashtable.
 *                  Default is degrees
 * 
 * Returns:
 * {Float} The corresponding scale given passed-in resolution and unit 
 *         parameters.
 */
OpenLayers.Util.getScaleFromResolution = function (resolution, units) {

    if (units == null) {
        units = "degrees";
    }

    var scale = resolution * OpenLayers.INCHES_PER_UNIT[units] *
                    OpenLayers.DOTS_PER_INCH;
    return scale;
};

/**
 * Function: safeStopPropagation
 * *Deprecated*. This function has been deprecated. Please use directly 
 *     <OpenLayers.Event.stop> passing 'true' as the 2nd 
 *     argument (preventDefault)
 * 
 * Safely stop the propagation of an event *without* preventing
 *   the default browser action from occurring.
 * 
 * Parameter:
 * evt - {Event}
 */
OpenLayers.Util.safeStopPropagation = function(evt) {
    OpenLayers.Event.stop(evt, true);
};

/**
 * Function: pagePositon
 * Calculates the position of an element on the page. 
 *
 * Parameters:
 * forElement - {DOMElement}
 * 
 * Returns:
 * {Array} two item array, L value then T value.
 */
OpenLayers.Util.pagePosition = function(forElement) {
    var valueT = 0, valueL = 0;

    var element = forElement;
    var child = forElement;
    while(element) {

        if(element == document.body) {
            if(OpenLayers.Element.getStyle(child, 'position') == 'absolute') {
                break;
            }
        }
        
        valueT += element.offsetTop  || 0;
        valueL += element.offsetLeft || 0;

        child = element;
        try {
            // wrapping this in a try/catch because IE chokes on the offsetParent
            element = element.offsetParent;
        } catch(e) {
            OpenLayers.Console.error(OpenLayers.i18n(
                                  "pagePositionFailed",{'elemId':element.id}));
            break;
        }
    }

    element = forElement;
    while(element) {
        valueT -= element.scrollTop  || 0;
        valueL -= element.scrollLeft || 0;
        element = element.parentNode;
    }
    
    return [valueL, valueT];
};


/** 
 * Function: isEquivalentUrl
 * Test two URLs for equivalence. 
 * 
 * Setting 'ignoreCase' allows for case-independent comparison.
 * 
 * Comparison is based on: 
 *  - Protocol
 *  - Host (evaluated without the port)
 *  - Port (set 'ignorePort80' to ignore "80" values)
 *  - Hash ( set 'ignoreHash' to disable)
 *  - Pathname (for relative <-> absolute comparison) 
 *  - Arguments (so they can be out of order)
 *  
 * Parameters:
 * url1 - {String}
 * url2 - {String}
 * options - {Object} Allows for customization of comparison:
 *                    'ignoreCase' - Default is True
 *                    'ignorePort80' - Default is True
 *                    'ignoreHash' - Default is True
 *
 * Returns:
 * {Boolean} Whether or not the two URLs are equivalent
 */
OpenLayers.Util.isEquivalentUrl = function(url1, url2, options) {
    options = options || {};

    OpenLayers.Util.applyDefaults(options, {
        ignoreCase: true,
        ignorePort80: true,
        ignoreHash: true
    });

    var urlObj1 = OpenLayers.Util.createUrlObject(url1, options);
    var urlObj2 = OpenLayers.Util.createUrlObject(url2, options);

    //compare all keys except for "args" (treated below)
    for(var key in urlObj1) {
        if(key !== "args") {
            if(urlObj1[key] != urlObj2[key]) {
                return false;
            }
        }
    }

    // compare search args - irrespective of order
    for(var key in urlObj1.args) {
        if(urlObj1.args[key] != urlObj2.args[key]) {
            return false;
        }
        delete urlObj2.args[key];
    }
    // urlObj2 shouldn't have any args left
    for(var key in urlObj2.args) {
        return false;
    }
    
    return true;
};

/**
 * Function: createUrlObject
 * 
 * Parameters:
 * url - {String}
 * options - {Object} A hash of options.  Can be one of:
 *            ignoreCase: lowercase url,
 *            ignorePort80: don't include explicit port if port is 80,
 *            ignoreHash: Don't include part of url after the hash (#).
 * 
 * Returns:
 * {Object} An object with separate url, a, port, host, and args parsed out 
 *          and ready for comparison
 */
OpenLayers.Util.createUrlObject = function(url, options) {
    options = options || {};

    // deal with relative urls first
    if(!(/^\w+:\/\//).test(url)) {
        var loc = window.location;
        var port = loc.port ? ":" + loc.port : "";
        var fullUrl = loc.protocol + "//" + loc.host.split(":").shift() + port;
        if(url.indexOf("/") === 0) {
            // full pathname
            url = fullUrl + url;
        } else {
            // relative to current path
            var parts = loc.pathname.split("/");
            parts.pop();
            url = fullUrl + parts.join("/") + "/" + url;
        }
    }
  
    if (options.ignoreCase) {
        url = url.toLowerCase(); 
    }

    var a = document.createElement('a');
    a.href = url;
    
    var urlObject = {};
    
    //host (without port)
    urlObject.host = a.host.split(":").shift();

    //protocol
    urlObject.protocol = a.protocol;  

    //port (get uniform browser behavior with port 80 here)
    if(options.ignorePort80) {
        urlObject.port = (a.port == "80" || a.port == "0") ? "" : a.port;
    } else {
        urlObject.port = (a.port == "" || a.port == "0") ? "80" : a.port;
    }

    //hash
    urlObject.hash = (options.ignoreHash || a.hash === "#") ? "" : a.hash;  
    
    //args
    var queryString = a.search;
    if (!queryString) {
        var qMark = url.indexOf("?");
        queryString = (qMark != -1) ? url.substr(qMark) : "";
    }
    urlObject.args = OpenLayers.Util.getParameters(queryString);

    //pathname (uniform browser behavior with leading "/")
    urlObject.pathname = (a.pathname.charAt(0) == "/") ? a.pathname : "/" + a.pathname;
    
    return urlObject; 
};
 
/**
 * Function: removeTail
 * Takes a url and removes everything after the ? and #
 * 
 * Parameters:
 * url - {String} The url to process
 * 
 * Returns:
 * {String} The string with all queryString and Hash removed
 */
OpenLayers.Util.removeTail = function(url) {
    var head = null;
    
    var qMark = url.indexOf("?");
    var hashMark = url.indexOf("#");

    if (qMark == -1) {
        head = (hashMark != -1) ? url.substr(0,hashMark) : url;
    } else {
        head = (hashMark != -1) ? url.substr(0,Math.min(qMark, hashMark)) 
                                  : url.substr(0, qMark);
    }
    return head;
};


/**
 * Function: getBrowserName
 * 
 * Returns:
 * {String} A string which specifies which is the current 
 *          browser in which we are running. 
 * 
 *          Currently-supported browser detection and codes:
 *           * 'opera' -- Opera
 *           * 'msie'  -- Internet Explorer
 *           * 'safari' -- Safari
 *           * 'firefox' -- FireFox
 *           * 'mozilla' -- Mozilla
 * 
 *          If we are unable to property identify the browser, we 
 *           return an empty string.
 */
OpenLayers.Util.getBrowserName = function() {
    var browserName = "";
    
    var ua = navigator.userAgent.toLowerCase();
    if ( ua.indexOf( "opera" ) != -1 ) {
        browserName = "opera";
    } else if ( ua.indexOf( "msie" ) != -1 ) {
        browserName = "msie";
    } else if ( ua.indexOf( "safari" ) != -1 ) {
        browserName = "safari";
    } else if ( ua.indexOf( "mozilla" ) != -1 ) {
        if ( ua.indexOf( "firefox" ) != -1 ) {
            browserName = "firefox";
        } else {
            browserName = "mozilla";
        }
    }
    
    return browserName;
};



    
/**
 * Method: getRenderedDimensions
 * Renders the contentHTML offscreen to determine actual dimensions for
 *     popup sizing. As we need layout to determine dimensions the content
 *     is rendered -9999px to the left and absolute to ensure the 
 *     scrollbars do not flicker
 *     
 * Parameters:
 * contentHTML
 * size - {<OpenLayers.Size>} If either the 'w' or 'h' properties is 
 *     specified, we fix that dimension of the div to be measured. This is 
 *     useful in the case where we have a limit in one dimension and must 
 *     therefore meaure the flow in the other dimension.
 * options - {Object}
 *     displayClass - {String} Optional parameter.  A CSS class name(s) string
 *         to provide the CSS context of the rendered content.
 *     containerElement - {DOMElement} Optional parameter. Insert the HTML to 
 *         this node instead of the body root when calculating dimensions. 
 * 
 * Returns:
 * {OpenLayers.Size}
 */
OpenLayers.Util.getRenderedDimensions = function(contentHTML, size, options) {
    
    var w, h;
    
    // create temp container div with restricted size
    var container = document.createElement("div");
    container.style.visibility = "hidden";
        
    var containerElement = (options && options.containerElement) 
    	? options.containerElement : document.body;

    //fix a dimension, if specified.
    if (size) {
        if (size.w) {
            w = size.w;
            container.style.width = w + "px";
        } else if (size.h) {
            h = size.h;
            container.style.height = h + "px";
        }
    }

    //add css classes, if specified
    if (options && options.displayClass) {
        container.className = options.displayClass;
    }
    
    // create temp content div and assign content
    var content = document.createElement("div");
    content.innerHTML = contentHTML;
    
    // we need overflow visible when calculating the size
    content.style.overflow = "visible";
    if (content.childNodes) {
        for (var i=0, l=content.childNodes.length; i<l; i++) {
            if (!content.childNodes[i].style) continue;
            content.childNodes[i].style.overflow = "visible";
        }
    }
    
    // add content to restricted container 
    container.appendChild(content);
    
    // append container to body for rendering
    containerElement.appendChild(container);
    
    // Opera and IE7 can't handle a node with position:aboslute if it inherits
    // position:absolute from a parent.
    var parentHasPositionAbsolute = false;
    var parent = container.parentNode;
    while (parent && parent.tagName.toLowerCase()!="body") {
        var parentPosition = OpenLayers.Element.getStyle(parent, "position");
        if(parentPosition == "absolute") {
            parentHasPositionAbsolute = true;
            break;
        } else if (parentPosition && parentPosition != "static") {
            break;
        }
        parent = parent.parentNode;
    }

    if(!parentHasPositionAbsolute) {
        container.style.position = "absolute";
    }
    
    // calculate scroll width of content and add corners and shadow width
    if (!w) {
        w = parseInt(content.scrollWidth);
    
        // update container width to allow height to adjust
        container.style.width = w + "px";
    }        
    // capture height and add shadow and corner image widths
    if (!h) {
        h = parseInt(content.scrollHeight);
    }

    // remove elements
    container.removeChild(content);
    containerElement.removeChild(container);
    
    return new OpenLayers.Size(w, h);
};

/**
 * APIFunction: getScrollbarWidth
 * This function has been modified by the OpenLayers from the original version,
 *     written by Matthew Eernisse and released under the Apache 2 
 *     license here:
 * 
 *     http://www.fleegix.org/articles/2006/05/30/getting-the-scrollbar-width-in-pixels
 * 
 *     It has been modified simply to cache its value, since it is physically 
 *     impossible that this code could ever run in more than one browser at 
 *     once. 
 * 
 * Returns:
 * {Integer}
 */
OpenLayers.Util.getScrollbarWidth = function() {
    
    var scrollbarWidth = OpenLayers.Util._scrollbarWidth;
    
    if (scrollbarWidth == null) {
        var scr = null;
        var inn = null;
        var wNoScroll = 0;
        var wScroll = 0;
    
        // Outer scrolling div
        scr = document.createElement('div');
        scr.style.position = 'absolute';
        scr.style.top = '-1000px';
        scr.style.left = '-1000px';
        scr.style.width = '100px';
        scr.style.height = '50px';
        // Start with no scrollbar
        scr.style.overflow = 'hidden';
    
        // Inner content div
        inn = document.createElement('div');
        inn.style.width = '100%';
        inn.style.height = '200px';
    
        // Put the inner div in the scrolling div
        scr.appendChild(inn);
        // Append the scrolling div to the doc
        document.body.appendChild(scr);
    
        // Width of the inner div sans scrollbar
        wNoScroll = inn.offsetWidth;
    
        // Add the scrollbar
        scr.style.overflow = 'scroll';
        // Width of the inner div width scrollbar
        wScroll = inn.offsetWidth;
    
        // Remove the scrolling div from the doc
        document.body.removeChild(document.body.lastChild);
    
        // Pixel width of the scroller
        OpenLayers.Util._scrollbarWidth = (wNoScroll - wScroll);
        scrollbarWidth = OpenLayers.Util._scrollbarWidth;
    }

    return scrollbarWidth;
};

/**
 * APIFunction: getFormattedLonLat
 * This function will return latitude or longitude value formatted as 
 *
 * Parameters:
 * coordinate - {Float} the coordinate value to be formatted
 * axis - {String} value of either 'lat' or 'lon' to indicate which axis is to
 *          to be formatted (default = lat)
 * dmsOption - {String} specify the precision of the output can be one of:
 *           'dms' show degrees minutes and seconds
 *           'dm' show only degrees and minutes
 *           'd' show only degrees
 * 
 * Returns:
 * {String} the coordinate value formatted as a string
 */
OpenLayers.Util.getFormattedLonLat = function(coordinate, axis, dmsOption) {
    if (!dmsOption) {
        dmsOption = 'dms';    //default to show degree, minutes, seconds
    }
    var abscoordinate = Math.abs(coordinate)
    var coordinatedegrees = Math.floor(abscoordinate);

    var coordinateminutes = (abscoordinate - coordinatedegrees)/(1/60);
    var tempcoordinateminutes = coordinateminutes;
    coordinateminutes = Math.floor(coordinateminutes);
    var coordinateseconds = (tempcoordinateminutes - coordinateminutes)/(1/60);
    coordinateseconds =  Math.round(coordinateseconds*10);
    coordinateseconds /= 10;

    if( coordinatedegrees < 10 ) {
        coordinatedegrees = "0" + coordinatedegrees;
    }
    var str = coordinatedegrees + " ";  //get degree symbol here somehow for SVG/VML labelling

    if (dmsOption.indexOf('dm') >= 0) {
        if( coordinateminutes < 10 ) {
            coordinateminutes = "0" + coordinateminutes;
        }
        str += coordinateminutes + "'";
  
        if (dmsOption.indexOf('dms') >= 0) {
            if( coordinateseconds < 10 ) {
                coordinateseconds = "0" + coordinateseconds;
            }
            str += coordinateseconds + '"';
        }
    }
    
    if (axis == "lon") {
        str += coordinate < 0 ? OpenLayers.i18n("W") : OpenLayers.i18n("E");
    } else {
        str += coordinate < 0 ? OpenLayers.i18n("S") : OpenLayers.i18n("N");
    }
    return str;
};

/* ======================================================================
    Rico/Corner.js
   ====================================================================== */

/*
 * This file has been edited substantially from the Rico-released
 * version by the OpenLayers development team.
 *  
 *  Copyright 2005 Sabre Airline Solutions  
 *  
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the
 *  License. You may obtain a copy of the License at
 *  
 *         http://www.apache.org/licenses/LICENSE-2.0  
 *  
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the * License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, * either express or
 * implied. See the License for the specific language governing
 * permissions * and limitations under the License.
 *
 */  
OpenLayers.Rico = new Object();
OpenLayers.Rico.Corner = {

    round: function(e, options) {
        e = OpenLayers.Util.getElement(e);
        this._setOptions(options);

        var color = this.options.color;
        if ( this.options.color == "fromElement" ) {
            color = this._background(e);
        }
        var bgColor = this.options.bgColor;
        if ( this.options.bgColor == "fromParent" ) {
            bgColor = this._background(e.offsetParent);
        }
        this._roundCornersImpl(e, color, bgColor);
    },

    /**   This is a helper function to change the background
    *     color of <div> that has had Rico rounded corners added.
    *
    *     It seems we cannot just set the background color for the
    *     outer <div> so each <span> element used to create the
    *     corners must have its background color set individually.
    *
    * @param {DOM} theDiv - A child of the outer <div> that was
    *                        supplied to the `round` method.
    *
    * @param {String} newColor - The new background color to use.
    */
    changeColor: function(theDiv, newColor) {
   
        theDiv.style.backgroundColor = newColor;

        var spanElements = theDiv.parentNode.getElementsByTagName("span");
        
        for (var currIdx = 0; currIdx < spanElements.length; currIdx++) {
            spanElements[currIdx].style.backgroundColor = newColor;
        }
    }, 


    /**   This is a helper function to change the background
    *     opacity of <div> that has had Rico rounded corners added.
    *
    *     See changeColor (above) for algorithm explanation
    *
    * @param {DOM} theDiv A child of the outer <div> that was
    *                        supplied to the `round` method.
    *
    * @param {int} newOpacity The new opacity to use (0-1).
    */
    changeOpacity: function(theDiv, newOpacity) {
   
        var mozillaOpacity = newOpacity;
        var ieOpacity = 'alpha(opacity=' + newOpacity * 100 + ')';
        
        theDiv.style.opacity = mozillaOpacity;
        theDiv.style.filter = ieOpacity;

        var spanElements = theDiv.parentNode.getElementsByTagName("span");
        
        for (var currIdx = 0; currIdx < spanElements.length; currIdx++) {
            spanElements[currIdx].style.opacity = mozillaOpacity;
            spanElements[currIdx].style.filter = ieOpacity;
        }

    },

    /** this function takes care of redoing the rico cornering
    *    
    *    you can't just call updateRicoCorners() again and pass it a 
    *    new options string. you have to first remove the divs that 
    *    rico puts on top and below the content div.
    *
    * @param {DOM} theDiv - A child of the outer <div> that was
    *                        supplied to the `round` method.
    *
    * @param {Object} options - list of options
    */
    reRound: function(theDiv, options) {

        var topRico = theDiv.parentNode.childNodes[0];
        //theDiv would be theDiv.parentNode.childNodes[1]
        var bottomRico = theDiv.parentNode.childNodes[2];
       
        theDiv.parentNode.removeChild(topRico);
        theDiv.parentNode.removeChild(bottomRico); 

        this.round(theDiv.parentNode, options);
    }, 

   _roundCornersImpl: function(e, color, bgColor) {
      if(this.options.border) {
         this._renderBorder(e,bgColor);
      }
      if(this._isTopRounded()) {
         this._roundTopCorners(e,color,bgColor);
      }
      if(this._isBottomRounded()) {
         this._roundBottomCorners(e,color,bgColor);
      }
   },

   _renderBorder: function(el,bgColor) {
      var borderValue = "1px solid " + this._borderColor(bgColor);
      var borderL = "border-left: "  + borderValue;
      var borderR = "border-right: " + borderValue;
      var style   = "style='" + borderL + ";" + borderR +  "'";
      el.innerHTML = "<div " + style + ">" + el.innerHTML + "</div>";
   },

   _roundTopCorners: function(el, color, bgColor) {
      var corner = this._createCorner(bgColor);
      for(var i=0 ; i < this.options.numSlices ; i++ ) {
         corner.appendChild(this._createCornerSlice(color,bgColor,i,"top"));
      }
      el.style.paddingTop = 0;
      el.insertBefore(corner,el.firstChild);
   },

   _roundBottomCorners: function(el, color, bgColor) {
      var corner = this._createCorner(bgColor);
      for(var i=(this.options.numSlices-1) ; i >= 0 ; i-- ) {
         corner.appendChild(this._createCornerSlice(color,bgColor,i,"bottom"));
      }
      el.style.paddingBottom = 0;
      el.appendChild(corner);
   },

   _createCorner: function(bgColor) {
      var corner = document.createElement("div");
      corner.style.backgroundColor = (this._isTransparent() ? "transparent" : bgColor);
      return corner;
   },

   _createCornerSlice: function(color,bgColor, n, position) {
      var slice = document.createElement("span");

      var inStyle = slice.style;
      inStyle.backgroundColor = color;
      inStyle.display  = "block";
      inStyle.height   = "1px";
      inStyle.overflow = "hidden";
      inStyle.fontSize = "1px";

      var borderColor = this._borderColor(color,bgColor);
      if ( this.options.border && n == 0 ) {
         inStyle.borderTopStyle    = "solid";
         inStyle.borderTopWidth    = "1px";
         inStyle.borderLeftWidth   = "0px";
         inStyle.borderRightWidth  = "0px";
         inStyle.borderBottomWidth = "0px";
         inStyle.height            = "0px"; // assumes css compliant box model
         inStyle.borderColor       = borderColor;
      }
      else if(borderColor) {
         inStyle.borderColor = borderColor;
         inStyle.borderStyle = "solid";
         inStyle.borderWidth = "0px 1px";
      }

      if ( !this.options.compact && (n == (this.options.numSlices-1)) ) {
         inStyle.height = "2px";
      }
      this._setMargin(slice, n, position);
      this._setBorder(slice, n, position);
      return slice;
   },

   _setOptions: function(options) {
      this.options = {
         corners : "all",
         color   : "fromElement",
         bgColor : "fromParent",
         blend   : true,
         border  : false,
         compact : false
      };
      OpenLayers.Util.extend(this.options, options || {});

      this.options.numSlices = this.options.compact ? 2 : 4;
      if ( this._isTransparent() ) {
         this.options.blend = false;
      }
   },

   _whichSideTop: function() {
      if ( this._hasString(this.options.corners, "all", "top") ) {
         return "";
      }
      if ( this.options.corners.indexOf("tl") >= 0 && this.options.corners.indexOf("tr") >= 0 ) {
         return "";
      }
      if (this.options.corners.indexOf("tl") >= 0) {
         return "left";
      } else if (this.options.corners.indexOf("tr") >= 0) {
          return "right";
      }
      return "";
   },

   _whichSideBottom: function() {
      if ( this._hasString(this.options.corners, "all", "bottom") ) {
         return "";
      }
      if ( this.options.corners.indexOf("bl")>=0 && this.options.corners.indexOf("br")>=0 ) {
         return "";
      }

      if(this.options.corners.indexOf("bl") >=0) {
         return "left";
      } else if(this.options.corners.indexOf("br")>=0) {
         return "right";
      }
      return "";
   },

   _borderColor : function(color,bgColor) {
      if ( color == "transparent" ) {
         return bgColor;
      } else if ( this.options.border ) {
         return this.options.border;
      } else if ( this.options.blend ) {
         return this._blend( bgColor, color );
      } else {
         return "";
      }
   },


   _setMargin: function(el, n, corners) {
      var marginSize = this._marginSize(n);
      var whichSide = corners == "top" ? this._whichSideTop() : this._whichSideBottom();

      if ( whichSide == "left" ) {
         el.style.marginLeft = marginSize + "px"; el.style.marginRight = "0px";
      }
      else if ( whichSide == "right" ) {
         el.style.marginRight = marginSize + "px"; el.style.marginLeft  = "0px";
      }
      else {
         el.style.marginLeft = marginSize + "px"; el.style.marginRight = marginSize + "px";
      }
   },

   _setBorder: function(el,n,corners) {
      var borderSize = this._borderSize(n);
      var whichSide = corners == "top" ? this._whichSideTop() : this._whichSideBottom();
      if ( whichSide == "left" ) {
         el.style.borderLeftWidth = borderSize + "px"; el.style.borderRightWidth = "0px";
      }
      else if ( whichSide == "right" ) {
         el.style.borderRightWidth = borderSize + "px"; el.style.borderLeftWidth  = "0px";
      }
      else {
         el.style.borderLeftWidth = borderSize + "px"; el.style.borderRightWidth = borderSize + "px";
      }
      if (this.options.border != false) {
        el.style.borderLeftWidth = borderSize + "px"; el.style.borderRightWidth = borderSize + "px";
      }
   },

   _marginSize: function(n) {
      if ( this._isTransparent() ) {
         return 0;
      }
      var marginSizes          = [ 5, 3, 2, 1 ];
      var blendedMarginSizes   = [ 3, 2, 1, 0 ];
      var compactMarginSizes   = [ 2, 1 ];
      var smBlendedMarginSizes = [ 1, 0 ];

      if ( this.options.compact && this.options.blend ) {
         return smBlendedMarginSizes[n];
      } else if ( this.options.compact ) {
         return compactMarginSizes[n];
      } else if ( this.options.blend ) {
         return blendedMarginSizes[n];
      } else {
         return marginSizes[n];
      }
   },

   _borderSize: function(n) {
      var transparentBorderSizes = [ 5, 3, 2, 1 ];
      var blendedBorderSizes     = [ 2, 1, 1, 1 ];
      var compactBorderSizes     = [ 1, 0 ];
      var actualBorderSizes      = [ 0, 2, 0, 0 ];

      if ( this.options.compact && (this.options.blend || this._isTransparent()) ) {
         return 1;
      } else if ( this.options.compact ) {
         return compactBorderSizes[n];
      } else if ( this.options.blend ) {
         return blendedBorderSizes[n];
      } else if ( this.options.border ) {
         return actualBorderSizes[n];
      } else if ( this._isTransparent() ) {
         return transparentBorderSizes[n];
      }
      return 0;
   },

   _hasString: function(str) { for(var i=1 ; i<arguments.length ; i++) if (str.indexOf(arguments[i]) >= 0) { return true; } return false; },
   _blend: function(c1, c2) { var cc1 = OpenLayers.Rico.Color.createFromHex(c1); cc1.blend(OpenLayers.Rico.Color.createFromHex(c2)); return cc1; },
   _background: function(el) { try { return OpenLayers.Rico.Color.createColorFromBackground(el).asHex(); } catch(err) { return "#ffffff"; } },
   _isTransparent: function() { return this.options.color == "transparent"; },
   _isTopRounded: function() { return this._hasString(this.options.corners, "all", "top", "tl", "tr"); },
   _isBottomRounded: function() { return this._hasString(this.options.corners, "all", "bottom", "bl", "br"); },
   _hasSingleTextChild: function(el) { return el.childNodes.length == 1 && el.childNodes[0].nodeType == 3; }
};
/* ======================================================================
    OpenLayers/BaseTypes/Element.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */

/**
 * Namespace: OpenLayers.Element
 */
OpenLayers.Element = {

    /**
     * APIFunction: visible
     * 
     * Parameters: 
     * element - {DOMElement}
     * 
     * Returns:
     * {Boolean} Is the element visible?
     */
    visible: function(element) {
        return OpenLayers.Util.getElement(element).style.display != 'none';
    },

    /**
     * APIFunction: toggle
     * Toggle the visibility of element(s) passed in
     * 
     * Parameters:
     * element - {DOMElement} Actually user can pass any number of elements
     */
    toggle: function() {
        for (var i=0, len=arguments.length; i<len; i++) {
            var element = OpenLayers.Util.getElement(arguments[i]);
            var display = OpenLayers.Element.visible(element) ? 'hide' 
                                                              : 'show';
            OpenLayers.Element[display](element);
        }
    },


    /**
     * APIFunction: hide
     * Hide element(s) passed in
     * 
     * Parameters:
     * element - {DOMElement} Actually user can pass any number of elements
     */
    hide: function() {
        for (var i=0, len=arguments.length; i<len; i++) {
            var element = OpenLayers.Util.getElement(arguments[i]);
            element.style.display = 'none';
        }
    },

    /**
     * APIFunction: show
     * Show element(s) passed in
     * 
     * Parameters:
     * element - {DOMElement} Actually user can pass any number of elements
     */
    show: function() {
        for (var i=0, len=arguments.length; i<len; i++) {
            var element = OpenLayers.Util.getElement(arguments[i]);
            element.style.display = '';
        }
    },

    /**
     * APIFunction: remove
     * Remove the specified element from the DOM.
     * 
     * Parameters:
     * element - {DOMElement}
     */
    remove: function(element) {
        element = OpenLayers.Util.getElement(element);
        element.parentNode.removeChild(element);
    },

    /**
     * APIFunction: getHeight
     *  
     * Parameters:
     * element - {DOMElement}
     * 
     * Returns:
     * {Integer} The offset height of the element passed in
     */
    getHeight: function(element) {
        element = OpenLayers.Util.getElement(element);
        return element.offsetHeight;
    },

    /**
     * APIFunction: getDimensions
     * *Deprecated*. Returns dimensions of the element passed in.
     *  
     * Parameters:
     * element - {DOMElement}
     * 
     * Returns:
     * {Object} Object with 'width' and 'height' properties which are the 
     *          dimensions of the element passed in.
     */
    getDimensions: function(element) {
        element = OpenLayers.Util.getElement(element);
        if (OpenLayers.Element.getStyle(element, 'display') != 'none') {
            return {width: element.offsetWidth, height: element.offsetHeight};
        }
    
        // All *Width and *Height properties give 0 on elements with display none,
        // so enable the element temporarily
        var els = element.style;
        var originalVisibility = els.visibility;
        var originalPosition = els.position;
        var originalDisplay = els.display;
        els.visibility = 'hidden';
        els.position = 'absolute';
        els.display = '';
        var originalWidth = element.clientWidth;
        var originalHeight = element.clientHeight;
        els.display = originalDisplay;
        els.position = originalPosition;
        els.visibility = originalVisibility;
        return {width: originalWidth, height: originalHeight};
    },

    /**
     * Function: hasClass
     * Tests if an element has the given CSS class name.
     *
     * Parameters:
     * element - {DOMElement} A DOM element node.
     * name - {String} The CSS class name to search for.
     *
     * Returns:
     * {Boolean} The element has the given class name.
     */
    hasClass: function(element, name) {
        var names = element.className;
        return (!!names && new RegExp("(^|\\s)" + name + "(\\s|$)").test(names));
    },
    
    /**
     * Function: addClass
     * Add a CSS class name to an element.  Safe where element already has
     *     the class name.
     *
     * Parameters:
     * element - {DOMElement} A DOM element node.
     * name - {String} The CSS class name to add.
     *
     * Returns:
     * {DOMElement} The element.
     */
    addClass: function(element, name) {
        if(!OpenLayers.Element.hasClass(element, name)) {
            element.className += (element.className ? " " : "") + name;
        }
        return element;
    },

    /**
     * Function: removeClass
     * Remove a CSS class name from an element.  Safe where element does not
     *     have the class name.
     *
     * Parameters:
     * element - {DOMElement} A DOM element node.
     * name - {String} The CSS class name to remove.
     *
     * Returns:
     * {DOMElement} The element.
     */
    removeClass: function(element, name) {
        var names = element.className;
        if(names) {
            element.className = OpenLayers.String.trim(
                names.replace(
                    new RegExp("(^|\\s+)" + name + "(\\s+|$)"), " "
                )
            );
        }
        return element;
    },

    /**
     * Function: toggleClass
     * Remove a CSS class name from an element if it exists.  Add the class name
     *     if it doesn't exist.
     *
     * Parameters:
     * element - {DOMElement} A DOM element node.
     * name - {String} The CSS class name to toggle.
     *
     * Returns:
     * {DOMElement} The element.
     */
    toggleClass: function(element, name) {
        if(OpenLayers.Element.hasClass(element, name)) {
            OpenLayers.Element.removeClass(element, name);
        } else {
            OpenLayers.Element.addClass(element, name);
        }
        return element;
    },

    /**
     * APIFunction: getStyle
     * 
     * Parameters:
     * element - {DOMElement}
     * style - {?}
     * 
     * Returns:
     * {?}
     */
    getStyle: function(element, style) {
        element = OpenLayers.Util.getElement(element);

        var value = null;
        if (element && element.style) {
            value = element.style[OpenLayers.String.camelize(style)];
            if (!value) {
                if (document.defaultView && 
                    document.defaultView.getComputedStyle) {
                    
                    var css = document.defaultView.getComputedStyle(element, null);
                    value = css ? css.getPropertyValue(style) : null;
                } else if (element.currentStyle) {
                    value = element.currentStyle[OpenLayers.String.camelize(style)];
                }
            }
        
            var positions = ['left', 'top', 'right', 'bottom'];
            if (window.opera &&
                (OpenLayers.Util.indexOf(positions,style) != -1) &&
                (OpenLayers.Element.getStyle(element, 'position') == 'static')) { 
                value = 'auto';
            }
        }
    
        return value == 'auto' ? null : value;
    }

};
/* ======================================================================
    OpenLayers/BaseTypes/Size.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */

/**
 * Class: OpenLayers.Size
 * Instances of this class represent a width/height pair
 */
OpenLayers.Size = OpenLayers.Class({

    /**
     * APIProperty: w
     * {Number} width
     */
    w: 0.0,
    
    /**
     * APIProperty: h
     * {Number} height
     */
    h: 0.0,


    /**
     * Constructor: OpenLayers.Size
     * Create an instance of OpenLayers.Size
     *
     * Parameters:
     * w - {Number} width
     * h - {Number} height
     */
    initialize: function(w, h) {
        this.w = parseFloat(w);
        this.h = parseFloat(h);
    },

    /**
     * Method: toString
     * Return the string representation of a size object
     *
     * Returns:
     * {String} The string representation of OpenLayers.Size object. 
     * (ex. <i>"w=55,h=66"</i>)
     */
    toString:function() {
        return ("w=" + this.w + ",h=" + this.h);
    },

    /**
     * APIMethod: clone
     * Create a clone of this size object
     *
     * Returns:
     * {<OpenLayers.Size>} A new OpenLayers.Size object with the same w and h
     * values
     */
    clone:function() {
        return new OpenLayers.Size(this.w, this.h);
    },

    /**
     *
     * APIMethod: equals
     * Determine where this size is equal to another
     *
     * Parameters:
     * sz - {<OpenLayers.Size>}
     *
     * Returns: 
     * {Boolean} The passed in size has the same h and w properties as this one.
     * Note that if sz passed in is null, returns false.
     *
     */
    equals:function(sz) {
        var equals = false;
        if (sz != null) {
            equals = ((this.w == sz.w && this.h == sz.h) ||
                      (isNaN(this.w) && isNaN(this.h) && isNaN(sz.w) && isNaN(sz.h)));
        }
        return equals;
    },

    CLASS_NAME: "OpenLayers.Size"
});
/* ======================================================================
    OpenLayers/Console.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */

/**
 * Namespace: OpenLayers.Console
 * The OpenLayers.Console namespace is used for debugging and error logging.
 * If the Firebug Lite (../Firebug/firebug.js) is included before this script,
 * calls to OpenLayers.Console methods will get redirected to window.console.
 * This makes use of the Firebug extension where available and allows for
 * cross-browser debugging Firebug style.
 *
 * Note:
 * Note that behavior will differ with the Firebug extention and Firebug Lite.
 * Most notably, the Firebug Lite console does not currently allow for
 * hyperlinks to code or for clicking on object to explore their properties.
 * 
 */
OpenLayers.Console = {
    /**
     * Create empty functions for all console methods.  The real value of these
     * properties will be set if Firebug Lite (../Firebug/firebug.js script) is
     * included.  We explicitly require the Firebug Lite script to trigger
     * functionality of the OpenLayers.Console methods.
     */
    
    /**
     * APIFunction: log
     * Log an object in the console.  The Firebug Lite console logs string
     * representation of objects.  Given multiple arguments, they will
     * be cast to strings and logged with a space delimiter.  If the first
     * argument is a string with printf-like formatting, subsequent arguments
     * will be used in string substitution.  Any additional arguments (beyond
     * the number substituted in a format string) will be appended in a space-
     * delimited line.
     * 
     * Parameters:
     * object - {Object}
     */
    log: function() {},

    /**
     * APIFunction: debug
     * Writes a message to the console, including a hyperlink to the line
     * where it was called.
     *
     * May be called with multiple arguments as with OpenLayers.Console.log().
     * 
     * Parameters:
     * object - {Object}
     */
    debug: function() {},

    /**
     * APIFunction: info
     * Writes a message to the console with the visual "info" icon and color
     * coding and a hyperlink to the line where it was called.
     *
     * May be called with multiple arguments as with OpenLayers.Console.log().
     * 
     * Parameters:
     * object - {Object}
     */
    info: function() {},

    /**
     * APIFunction: warn
     * Writes a message to the console with the visual "warning" icon and
     * color coding and a hyperlink to the line where it was called.
     *
     * May be called with multiple arguments as with OpenLayers.Console.log().
     * 
     * Parameters:
     * object - {Object}
     */
    warn: function() {},

    /**
     * APIFunction: error
     * Writes a message to the console with the visual "error" icon and color
     * coding and a hyperlink to the line where it was called.
     *
     * May be called with multiple arguments as with OpenLayers.Console.log().
     * 
     * Parameters:
     * object - {Object}
     */
    error: function() {},
    
    /**
     * APIFunction: userError
     * A single interface for showing error messages to the user. The default
     * behavior is a Javascript alert, though this can be overridden by
     * reassigning OpenLayers.Console.userError to a different function.
     *
     * Expects a single error message
     * 
     * Parameters:
     * object - {Object}
     */
    userError: function(error) {
        alert(error);
    },

    /**
     * APIFunction: assert
     * Tests that an expression is true. If not, it will write a message to
     * the console and throw an exception.
     *
     * May be called with multiple arguments as with OpenLayers.Console.log().
     * 
     * Parameters:
     * object - {Object}
     */
    assert: function() {},

    /**
     * APIFunction: dir
     * Prints an interactive listing of all properties of the object. This
     * looks identical to the view that you would see in the DOM tab.
     * 
     * Parameters:
     * object - {Object}
     */
    dir: function() {},

    /**
     * APIFunction: dirxml
     * Prints the XML source tree of an HTML or XML element. This looks
     * identical to the view that you would see in the HTML tab. You can click
     * on any node to inspect it in the HTML tab.
     * 
     * Parameters:
     * object - {Object}
     */
    dirxml: function() {},

    /**
     * APIFunction: trace
     * Prints an interactive stack trace of JavaScript execution at the point
     * where it is called.  The stack trace details the functions on the stack,
     * as well as the values that were passed as arguments to each function.
     * You can click each function to take you to its source in the Script tab,
     * and click each argument value to inspect it in the DOM or HTML tabs.
     * 
     */
    trace: function() {},

    /**
     * APIFunction: group
     * Writes a message to the console and opens a nested block to indent all
     * future messages sent to the console. Call OpenLayers.Console.groupEnd()
     * to close the block.
     *
     * May be called with multiple arguments as with OpenLayers.Console.log().
     * 
     * Parameters:
     * object - {Object}
     */
    group: function() {},

    /**
     * APIFunction: groupEnd
     * Closes the most recently opened block created by a call to
     * OpenLayers.Console.group
     */
    groupEnd: function() {},
    
    /**
     * APIFunction: time
     * Creates a new timer under the given name. Call
     * OpenLayers.Console.timeEnd(name)
     * with the same name to stop the timer and print the time elapsed.
     *
     * Parameters:
     * name - {String}
     */
    time: function() {},

    /**
     * APIFunction: timeEnd
     * Stops a timer created by a call to OpenLayers.Console.time(name) and
     * writes the time elapsed.
     *
     * Parameters:
     * name - {String}
     */
    timeEnd: function() {},

    /**
     * APIFunction: profile
     * Turns on the JavaScript profiler. The optional argument title would
     * contain the text to be printed in the header of the profile report.
     *
     * This function is not currently implemented in Firebug Lite.
     * 
     * Parameters:
     * title - {String} Optional title for the profiler
     */
    profile: function() {},

    /**
     * APIFunction: profileEnd
     * Turns off the JavaScript profiler and prints its report.
     * 
     * This function is not currently implemented in Firebug Lite.
     */
    profileEnd: function() {},

    /**
     * APIFunction: count
     * Writes the number of times that the line of code where count was called
     * was executed. The optional argument title will print a message in
     * addition to the number of the count.
     *
     * This function is not currently implemented in Firebug Lite.
     *
     * Parameters:
     * title - {String} Optional title to be printed with count
     */
    count: function() {},

    CLASS_NAME: "OpenLayers.Console"
};

/**
 * Execute an anonymous function to extend the OpenLayers.Console namespace
 * if the firebug.js script is included.  This closure is used so that the
 * "scripts" and "i" variables don't pollute the global namespace.
 */
(function() {
    /**
     * If Firebug Lite is included (before this script), re-route all
     * OpenLayers.Console calls to the console object.
     */
    var scripts = document.getElementsByTagName("script");
    for(var i=0, len=scripts.length; i<len; ++i) {
        if(scripts[i].src.indexOf("firebug.js") != -1) {
            if(console) {
                OpenLayers.Util.extend(OpenLayers.Console, console);
                break;
            }
        }
    }
})();
/* ======================================================================
    OpenLayers/Icon.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */


/**
 * Class: OpenLayers.Icon
 * 
 * The icon represents a graphical icon on the screen.  Typically used in
 * conjunction with a <OpenLayers.Marker> to represent markers on a screen.
 *
 * An icon has a url, size and position.  It also contains an offset which 
 * allows the center point to be represented correctly.  This can be
 * provided either as a fixed offset or a function provided to calculate
 * the desired offset. 
 * 
 */
OpenLayers.Icon = OpenLayers.Class({
    
    /** 
     * Property: url 
     * {String}  image url
     */
    url: null,
    
    /** 
     * Property: size 
     * {<OpenLayers.Size>} 
     */
    size: null,

    /** 
     * Property: offset 
     * {<OpenLayers.Pixel>} distance in pixels to offset the image when being rendered
     */
    offset: null,    
    
    /** 
     * Property: calculateOffset 
     * {<OpenLayers.Pixel>} Function to calculate the offset (based on the size) 
     */
    calculateOffset: null,    
    
    /** 
     * Property: imageDiv 
     * {DOMElement} 
     */
    imageDiv: null,

    /** 
     * Property: px 
     * {<OpenLayers.Pixel>} 
     */
    px: null,
    
    /** 
     * Constructor: OpenLayers.Icon
     * Creates an icon, which is an image tag in a div.  
     *
     * url - {String} 
     * size - {<OpenLayers.Size>} 
     * offset - {<OpenLayers.Pixel>}
     * calculateOffset - {Function} 
     */
    initialize: function(url, size, offset, calculateOffset) {
        this.url = url;
        this.size = (size) ? size : new OpenLayers.Size(20,20);
        this.offset = offset ? offset : new OpenLayers.Pixel(-(this.size.w/2), -(this.size.h/2));
        this.calculateOffset = calculateOffset;

        var id = OpenLayers.Util.createUniqueID("OL_Icon_");
        this.imageDiv = OpenLayers.Util.createAlphaImageDiv(id);
    },
    
    /** 
     * Method: destroy
     * Nullify references and remove event listeners to prevent circular 
     * references and memory leaks
     */
    destroy: function() {
        // erase any drawn elements
        this.erase();

        OpenLayers.Event.stopObservingElement(this.imageDiv.firstChild); 
        this.imageDiv.innerHTML = "";
        this.imageDiv = null;
    },

    /** 
     * Method: clone
     * 
     * Returns:
     * {<OpenLayers.Icon>} A fresh copy of the icon.
     */
    clone: function() {
        return new OpenLayers.Icon(this.url, 
                                   this.size, 
                                   this.offset, 
                                   this.calculateOffset);
    },
    
    /**
     * Method: setSize
     * 
     * Parameters:
     * size - {<OpenLayers.Size>} 
     */
    setSize: function(size) {
        if (size != null) {
            this.size = size;
        }
        this.draw();
    },
    
    /**
     * Method: setUrl
     * 
     * Parameters:
     * url - {String} 
     */
    setUrl: function(url) {
        if (url != null) {
            this.url = url;
        }
        this.draw();
    },

    /** 
     * Method: draw
     * Move the div to the given pixel.
     * 
     * Parameters:
     * px - {<OpenLayers.Pixel>} 
     * 
     * Returns:
     * {DOMElement} A new DOM Image of this icon set at the location passed-in
     */
    draw: function(px) {
        OpenLayers.Util.modifyAlphaImageDiv(this.imageDiv, 
                                            null, 
                                            null, 
                                            this.size, 
                                            this.url, 
                                            "absolute");
        this.moveTo(px);
        return this.imageDiv;
    }, 

    /** 
     * Method: erase
     * Erase the underlying image element.
     *
     */
    erase: function() {
        if (this.imageDiv != null && this.imageDiv.parentNode != null) {
            OpenLayers.Element.remove(this.imageDiv);
        }
    }, 
    
    /** 
     * Method: setOpacity
     * Change the icon's opacity
     *
     * Parameters:
     * opacity - {float} 
     */
    setOpacity: function(opacity) {
        OpenLayers.Util.modifyAlphaImageDiv(this.imageDiv, null, null, null, 
                                            null, null, null, null, opacity);

    },
    
    /**
     * Method: moveTo
     * move icon to passed in px.
     *
     * Parameters:
     * px - {<OpenLayers.Pixel>} 
     */
    moveTo: function (px) {
        //if no px passed in, use stored location
        if (px != null) {
            this.px = px;
        }

        if (this.imageDiv != null) {
            if (this.px == null) {
                this.display(false);
            } else {
                if (this.calculateOffset) {
                    this.offset = this.calculateOffset(this.size);  
                }
                var offsetPx = this.px.offset(this.offset);
                OpenLayers.Util.modifyAlphaImageDiv(this.imageDiv, null, offsetPx);
            }
        }
    },
    
    /** 
     * Method: display
     * Hide or show the icon
     *
     * Parameters:
     * display - {Boolean} 
     */
    display: function(display) {
        this.imageDiv.style.display = (display) ? "" : "none"; 
    },
    

    /**
     * APIMethod: isDrawn
     * 
     * Returns:
     * {Boolean} Whether or not the icon is drawn.
     */
    isDrawn: function() {
        // nodeType 11 for ie, whose nodes *always* have a parentNode
        // (of type document fragment)
        var isDrawn = (this.imageDiv && this.imageDiv.parentNode && 
                       (this.imageDiv.parentNode.nodeType != 11));    

        return isDrawn;   
    },

    CLASS_NAME: "OpenLayers.Icon"
});
/* ======================================================================
    OpenLayers/Popup.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */


/**
 * Class: OpenLayers.Popup
 * A popup is a small div that can opened and closed on the map.
 * Typically opened in response to clicking on a marker.  
 * See <OpenLayers.Marker>.  Popup's don't require their own
 * layer and are added the the map using the <OpenLayers.Map.addPopup>
 * method.
 *
 * Example:
 * (code)
 * popup = new OpenLayers.Popup("chicken", 
 *                    new OpenLayers.LonLat(5,40),
 *                    new OpenLayers.Size(200,200),
 *                    "example popup",
 *                    true);
 *       
 * map.addPopup(popup);
 * (end)
 */
OpenLayers.Popup = OpenLayers.Class({

    /** 
     * Property: events  
     * {<OpenLayers.Events>} custom event manager 
     */
    events: null,
    
    /** Property: id
     * {String} the unique identifier assigned to this popup.
     */
    id: "",

    /** 
     * Property: lonlat 
     * {<OpenLayers.LonLat>} the position of this popup on the map
     */
    lonlat: null,

    /** 
     * Property: div 
     * {DOMElement} the div that contains this popup.
     */
    div: null,

    /** 
     * Property: contentSize 
     * {<OpenLayers.Size>} the width and height of the content.
     */
    contentSize: null,    

    /** 
     * Property: size 
     * {<OpenLayers.Size>} the width and height of the popup.
     */
    size: null,    

    /** 
     * Property: contentHTML 
     * {String} An HTML string for this popup to display.
     */
    contentHTML: null,
    
    /** 
     * Property: backgroundColor 
     * {String} the background color used by the popup.
     */
    backgroundColor: "",
    
    /** 
     * Property: opacity 
     * {float} the opacity of this popup (between 0.0 and 1.0)
     */
    opacity: "",

    /** 
     * Property: border 
     * {String} the border size of the popup.  (eg 2px)
     */
    border: "",
    
    /** 
     * Property: contentDiv 
     * {DOMElement} a reference to the element that holds the content of
     *              the div.
     */
    contentDiv: null,
    
    /** 
     * Property: groupDiv 
     * {DOMElement} First and only child of 'div'. The group Div contains the
     *     'contentDiv' and the 'closeDiv'.
     */
    groupDiv: null,

    /** 
     * Property: closeDiv
     * {DOMElement} the optional closer image
     */
    closeDiv: null,

    /** 
     * APIProperty: autoSize
     * {Boolean} Resize the popup to auto-fit the contents.
     *     Default is false.
     */
    autoSize: false,

    /**
     * APIProperty: minSize
     * {<OpenLayers.Size>} Minimum size allowed for the popup's contents.
     */
    minSize: null,

    /**
     * APIProperty: maxSize
     * {<OpenLayers.Size>} Maximum size allowed for the popup's contents.
     */
    maxSize: null,

    /** 
     * Property: displayClass
     * {String} The CSS class of the popup.
     */
    displayClass: "olPopup",

    /** 
     * Property: contentDisplayClass
     * {String} The CSS class of the popup content div.
     */
    contentDisplayClass: "olPopupContent",

    /** 
     * Property: padding 
     * {int or <OpenLayers.Bounds>} An extra opportunity to specify internal 
     *     padding of the content div inside the popup. This was originally
     *     confused with the css padding as specified in style.css's 
     *     'olPopupContent' class. We would like to get rid of this altogether,
     *     except that it does come in handy for the framed and anchoredbubble
     *     popups, who need to maintain yet another barrier between their 
     *     content and the outer border of the popup itself. 
     * 
     *     Note that in order to not break API, we must continue to support 
     *     this property being set as an integer. Really, though, we'd like to 
     *     have this specified as a Bounds object so that user can specify
     *     distinct left, top, right, bottom paddings. With the 3.0 release
     *     we can make this only a bounds.
     */
    padding: 0,

    /** 
     * Property: disableFirefoxOverflowHack
     * {Boolean} The hack for overflow in Firefox causes all elements 
     *     to be re-drawn, which causes Flash elements to be 
     *     re-initialized, which is troublesome.
     *     With this property the hack can be disabled.
     */
    disableFirefoxOverflowHack: false,

    /**
     * Method: fixPadding
     * To be removed in 3.0, this function merely helps us to deal with the 
     *     case where the user may have set an integer value for padding, 
     *     instead of an <OpenLayers.Bounds> object.
     */
    fixPadding: function() {
        if (typeof this.padding == "number") {
            this.padding = new OpenLayers.Bounds(
                this.padding, this.padding, this.padding, this.padding
            );
        }
    },

    /**
     * APIProperty: panMapIfOutOfView
     * {Boolean} When drawn, pan map such that the entire popup is visible in
     *     the current viewport (if necessary).
     *     Default is false.
     */
    panMapIfOutOfView: false,
    
    /**
     * APIProperty: keepInMap 
     * {Boolean} If panMapIfOutOfView is false, and this property is true, 
     *     contrain the popup such that it always fits in the available map
     *     space. By default, this is not set on the base class. If you are
     *     creating popups that are near map edges and not allowing pannning,
     *     and especially if you have a popup which has a
     *     fixedRelativePosition, setting this to false may be a smart thing to
     *     do. Subclasses may want to override this setting.
     *   
     *     Default is false.
     */
    keepInMap: false,

    /**
     * APIProperty: closeOnMove
     * {Boolean} When map pans, close the popup.
     *     Default is false.
     */
    closeOnMove: false,
    
    /** 
     * Property: map 
     * {<OpenLayers.Map>} this gets set in Map.js when the popup is added to the map
     */
    map: null,

    /** 
    * Constructor: OpenLayers.Popup
    * Create a popup.
    * 
    * Parameters: 
    * id - {String} a unqiue identifier for this popup.  If null is passed
    *               an identifier will be automatically generated. 
    * lonlat - {<OpenLayers.LonLat>}  The position on the map the popup will
    *                                 be shown.
    * contentSize - {<OpenLayers.Size>} The size of the content.
    * contentHTML - {String}          An HTML string to display inside the   
    *                                 popup.
    * closeBox - {Boolean}            Whether to display a close box inside
    *                                 the popup.
    * closeBoxCallback - {Function}   Function to be called on closeBox click.
    */
    initialize:function(id, lonlat, contentSize, contentHTML, closeBox, closeBoxCallback) {
        if (id == null) {
            id = OpenLayers.Util.createUniqueID(this.CLASS_NAME + "_");
        }

        this.id = id;
        this.lonlat = lonlat;

        this.contentSize = (contentSize != null) ? contentSize 
                                  : new OpenLayers.Size(
                                                   OpenLayers.Popup.WIDTH,
                                                   OpenLayers.Popup.HEIGHT);
        if (contentHTML != null) { 
             this.contentHTML = contentHTML;
        }
        this.backgroundColor = OpenLayers.Popup.COLOR;
        this.opacity = OpenLayers.Popup.OPACITY;
        this.border = OpenLayers.Popup.BORDER;

        this.div = OpenLayers.Util.createDiv(this.id, null, null, 
                                             null, null, null, "hidden");
        this.div.className = this.displayClass;
        
        var groupDivId = this.id + "_GroupDiv";
        this.groupDiv = OpenLayers.Util.createDiv(groupDivId, null, null, 
                                                    null, "relative", null,
                                                    "hidden");

        var id = this.div.id + "_contentDiv";
        this.contentDiv = OpenLayers.Util.createDiv(id, null, this.contentSize.clone(), 
                                                    null, "relative");
        this.contentDiv.className = this.contentDisplayClass;
        this.groupDiv.appendChild(this.contentDiv);
        this.div.appendChild(this.groupDiv);

        if (closeBox) {
            this.addCloseBox(closeBoxCallback);
        } 

        this.registerEvents();
    },

    /** 
     * Method: destroy
     * nullify references to prevent circular references and memory leaks
     */
    destroy: function() {

        this.id = null;
        this.lonlat = null;
        this.size = null;
        this.contentHTML = null;
        
        this.backgroundColor = null;
        this.opacity = null;
        this.border = null;
        
        if (this.closeOnMove && this.map) {
            this.map.events.unregister("movestart", this, this.hide);
        }

        this.events.destroy();
        this.events = null;
        
        if (this.closeDiv) {
            OpenLayers.Event.stopObservingElement(this.closeDiv); 
            this.groupDiv.removeChild(this.closeDiv);
        }
        this.closeDiv = null;
        
        this.div.removeChild(this.groupDiv);
        this.groupDiv = null;

        if (this.map != null) {
            this.map.removePopup(this);
        }
        this.map = null;
        this.div = null;
        
        this.autoSize = null;
        this.minSize = null;
        this.maxSize = null;
        this.padding = null;
        this.panMapIfOutOfView = null;
    },

    /** 
    * Method: draw
    * Constructs the elements that make up the popup.
    *
    * Parameters:
    * px - {<OpenLayers.Pixel>} the position the popup in pixels.
    * 
    * Returns:
    * {DOMElement} Reference to a div that contains the drawn popup
    */
    draw: function(px) {
        if (px == null) {
            if ((this.lonlat != null) && (this.map != null)) {
                px = this.map.getLayerPxFromLonLat(this.lonlat);
            }
        }

        // this assumes that this.map already exists, which is okay because 
        // this.draw is only called once the popup has been added to the map.
        if (this.closeOnMove) {
            this.map.events.register("movestart", this, this.hide);
        }
        
        //listen to movestart, moveend to disable overflow (FF bug)
        if (!this.disableFirefoxOverflowHack && OpenLayers.Util.getBrowserName() == 'firefox') {
            this.map.events.register("movestart", this, function() {
                var style = document.defaultView.getComputedStyle(
                    this.contentDiv, null
                );
                var currentOverflow = style.getPropertyValue("overflow");
                if (currentOverflow != "hidden") {
                    this.contentDiv._oldOverflow = currentOverflow;
                    this.contentDiv.style.overflow = "hidden";
                }
            });
            this.map.events.register("moveend", this, function() {
                var oldOverflow = this.contentDiv._oldOverflow;
                if (oldOverflow) {
                    this.contentDiv.style.overflow = oldOverflow;
                    this.contentDiv._oldOverflow = null;
                }
            });
        }

        this.moveTo(px);
        if (!this.autoSize && !this.size) {
            this.setSize(this.contentSize);
        }
        this.setBackgroundColor();
        this.setOpacity();
        this.setBorder();
        this.setContentHTML();
        
        if (this.panMapIfOutOfView) {
            this.panIntoView();
        }    

        return this.div;
    },

    /** 
     * Method: updatePosition
     * if the popup has a lonlat and its map members set, 
     * then have it move itself to its proper position
     */
    updatePosition: function() {
        if ((this.lonlat) && (this.map)) {
            var px = this.map.getLayerPxFromLonLat(this.lonlat);
            if (px) {
                this.moveTo(px);           
            }    
        }
    },

    /**
     * Method: moveTo
     * 
     * Parameters:
     * px - {<OpenLayers.Pixel>} the top and left position of the popup div. 
     */
    moveTo: function(px) {
        if ((px != null) && (this.div != null)) {
            this.div.style.left = px.x + "px";
            this.div.style.top = px.y + "px";
        }
    },

    /**
     * Method: visible
     *
     * Returns:      
     * {Boolean} Boolean indicating whether or not the popup is visible
     */
    visible: function() {
        return OpenLayers.Element.visible(this.div);
    },

    /**
     * Method: toggle
     * Toggles visibility of the popup.
     */
    toggle: function() {
        if (this.visible()) {
            this.hide();
        } else {
            this.show();
        }
    },

    /**
     * Method: show
     * Makes the popup visible.
     */
    show: function() {
        OpenLayers.Element.show(this.div);

        if (this.panMapIfOutOfView) {
            this.panIntoView();
        }    
    },

    /**
     * Method: hide
     * Makes the popup invisible.
     */
    hide: function() {
        OpenLayers.Element.hide(this.div);
    },

    /**
     * Method: setSize
     * Used to adjust the size of the popup. 
     *
     * Parameters:
     * contentSize - {<OpenLayers.Size>} the new size for the popup's 
     *     contents div (in pixels).
     */
    setSize:function(contentSize) { 
        this.size = contentSize.clone(); 
        
        // if our contentDiv has a css 'padding' set on it by a stylesheet, we 
        //  must add that to the desired "size". 
        var contentDivPadding = this.getContentDivPadding();
        var wPadding = contentDivPadding.left + contentDivPadding.right;
        var hPadding = contentDivPadding.top + contentDivPadding.bottom;

        // take into account the popup's 'padding' property
        this.fixPadding();
        wPadding += this.padding.left + this.padding.right;
        hPadding += this.padding.top + this.padding.bottom;

        // make extra space for the close div
        if (this.closeDiv) {
            var closeDivWidth = parseInt(this.closeDiv.style.width);
            wPadding += closeDivWidth + contentDivPadding.right;
        }

        //increase size of the main popup div to take into account the 
        // users's desired padding and close div.        
        this.size.w += wPadding;
        this.size.h += hPadding;

        //now if our browser is IE, we need to actually make the contents 
        // div itself bigger to take its own padding into effect. this makes 
        // me want to shoot someone, but so it goes.
        if (OpenLayers.Util.getBrowserName() == "msie") {
            this.contentSize.w += 
                contentDivPadding.left + contentDivPadding.right;
            this.contentSize.h += 
                contentDivPadding.bottom + contentDivPadding.top;
        }

        if (this.div != null) {
            this.div.style.width = this.size.w + "px";
            this.div.style.height = this.size.h + "px";
        }
        if (this.contentDiv != null){
            this.contentDiv.style.width = contentSize.w + "px";
            this.contentDiv.style.height = contentSize.h + "px";
        }
    },  

    /**
     * APIMethod: updateSize
     * Auto size the popup so that it precisely fits its contents (as 
     *     determined by this.contentDiv.innerHTML). Popup size will, of
     *     course, be limited by the available space on the current map
     */
    updateSize: function() {
        
        // determine actual render dimensions of the contents by putting its
        // contents into a fake contentDiv (for the CSS) and then measuring it
        var preparedHTML = "<div class='" + this.contentDisplayClass+ "'>" + 
            this.contentDiv.innerHTML + 
            "</div>";
 
        var containerElement = (this.map) ? this.map.layerContainerDiv
        								  : document.body;
        var realSize = OpenLayers.Util.getRenderedDimensions(
            preparedHTML, null,	{
                displayClass: this.displayClass,
                containerElement: containerElement
            }
        );

        // is the "real" size of the div is safe to display in our map?
        var safeSize = this.getSafeContentSize(realSize);

        var newSize = null;
        if (safeSize.equals(realSize)) {
            //real size of content is small enough to fit on the map, 
            // so we use real size.
            newSize = realSize;

        } else {

            //make a new OL.Size object with the clipped dimensions 
            // set or null if not clipped.
            var fixedSize = new OpenLayers.Size();
            fixedSize.w = (safeSize.w < realSize.w) ? safeSize.w : null;
            fixedSize.h = (safeSize.h < realSize.h) ? safeSize.h : null;
        
            if (fixedSize.w && fixedSize.h) {
                //content is too big in both directions, so we will use 
                // max popup size (safeSize), knowing well that it will 
                // overflow both ways.                
                newSize = safeSize;
            } else {
                //content is clipped in only one direction, so we need to 
                // run getRenderedDimensions() again with a fixed dimension
                var clippedSize = OpenLayers.Util.getRenderedDimensions(
                    preparedHTML, fixedSize, {
                        displayClass: this.contentDisplayClass,
                        containerElement: containerElement
                    }
                );
                
                //if the clipped size is still the same as the safeSize, 
                // that means that our content must be fixed in the 
                // offending direction. If overflow is 'auto', this means 
                // we are going to have a scrollbar for sure, so we must 
                // adjust for that.
                //
                var currentOverflow = OpenLayers.Element.getStyle(
                    this.contentDiv, "overflow"
                );
                if ( (currentOverflow != "hidden") && 
                     (clippedSize.equals(safeSize)) ) {
                    var scrollBar = OpenLayers.Util.getScrollbarWidth();
                    if (fixedSize.w) {
                        clippedSize.h += scrollBar;
                    } else {
                        clippedSize.w += scrollBar;
                    }
                }
                
                newSize = this.getSafeContentSize(clippedSize);
            }
        }                        
        this.setSize(newSize);     
    },    

    /**
     * Method: setBackgroundColor
     * Sets the background color of the popup.
     *
     * Parameters:
     * color - {String} the background color.  eg "#FFBBBB"
     */
    setBackgroundColor:function(color) { 
        if (color != undefined) {
            this.backgroundColor = color; 
        }
        
        if (this.div != null) {
            this.div.style.backgroundColor = this.backgroundColor;
        }
    },  
    
    /**
     * Method: setOpacity
     * Sets the opacity of the popup.
     * 
     * Parameters:
     * opacity - {float} A value between 0.0 (transparent) and 1.0 (solid).   
     */
    setOpacity:function(opacity) { 
        if (opacity != undefined) {
            this.opacity = opacity; 
        }
        
        if (this.div != null) {
            // for Mozilla and Safari
            this.div.style.opacity = this.opacity;

            // for IE
            this.div.style.filter = 'alpha(opacity=' + this.opacity*100 + ')';
        }
    },  
    
    /**
     * Method: setBorder
     * Sets the border style of the popup.
     *
     * Parameters:
     * border - {String} The border style value. eg 2px 
     */
    setBorder:function(border) { 
        if (border != undefined) {
            this.border = border;
        }
        
        if (this.div != null) {
            this.div.style.border = this.border;
        }
    },      
    
    /**
     * Method: setContentHTML
     * Allows the user to set the HTML content of the popup.
     *
     * Parameters:
     * contentHTML - {String} HTML for the div.
     */
    setContentHTML:function(contentHTML) {

        if (contentHTML != null) {
            this.contentHTML = contentHTML;
        }
       
        if ((this.contentDiv != null) && 
            (this.contentHTML != null) &&
            (this.contentHTML != this.contentDiv.innerHTML)) {
       
            this.contentDiv.innerHTML = this.contentHTML;
       
            if (this.autoSize) {
                
                //if popup has images, listen for when they finish
                // loading and resize accordingly
                this.registerImageListeners();

                //auto size the popup to its current contents
                this.updateSize();
            }
        }    

    },
    
    /**
     * Method: registerImageListeners
     * Called when an image contained by the popup loaded. this function
     *     updates the popup size, then unregisters the image load listener.
     */   
    registerImageListeners: function() { 

        // As the images load, this function will call updateSize() to 
        // resize the popup to fit the content div (which presumably is now
        // bigger than when the image was not loaded).
        // 
        // If the 'panMapIfOutOfView' property is set, we will pan the newly
        // resized popup back into view.
        // 
        // Note that this function, when called, will have 'popup' and 
        // 'img' properties in the context.
        //
        var onImgLoad = function() {
            
            this.popup.updateSize();
     
            if ( this.popup.visible() && this.popup.panMapIfOutOfView ) {
                this.popup.panIntoView();
            }

            OpenLayers.Event.stopObserving(
                this.img, "load", this.img._onImageLoad
            );
    
        };

        //cycle through the images and if their size is 0x0, that means that 
        // they haven't been loaded yet, so we attach the listener, which 
        // will fire when the images finish loading and will resize the 
        // popup accordingly to its new size.
        var images = this.contentDiv.getElementsByTagName("img");
        for (var i = 0, len = images.length; i < len; i++) {
            var img = images[i];
            if (img.width == 0 || img.height == 0) {

                var context = {
                    'popup': this,
                    'img': img
                };

                //expando this function to the image itself before registering
                // it. This way we can easily and properly unregister it.
                img._onImgLoad = OpenLayers.Function.bind(onImgLoad, context);

                OpenLayers.Event.observe(img, 'load', img._onImgLoad);
            }    
        } 
    },

    /**
     * APIMethod: getSafeContentSize
     * 
     * Parameters:
     * size - {<OpenLayers.Size>} Desired size to make the popup.
     * 
     * Returns:
     * {<OpenLayers.Size>} A size to make the popup which is neither smaller
     *     than the specified minimum size, nor bigger than the maximum 
     *     size (which is calculated relative to the size of the viewport).
     */
    getSafeContentSize: function(size) {

        var safeContentSize = size.clone();

        // if our contentDiv has a css 'padding' set on it by a stylesheet, we 
        //  must add that to the desired "size". 
        var contentDivPadding = this.getContentDivPadding();
        var wPadding = contentDivPadding.left + contentDivPadding.right;
        var hPadding = contentDivPadding.top + contentDivPadding.bottom;

        // take into account the popup's 'padding' property
        this.fixPadding();
        wPadding += this.padding.left + this.padding.right;
        hPadding += this.padding.top + this.padding.bottom;

        if (this.closeDiv) {
            var closeDivWidth = parseInt(this.closeDiv.style.width);
            wPadding += closeDivWidth + contentDivPadding.right;
        }

        // prevent the popup from being smaller than a specified minimal size
        if (this.minSize) {
            safeContentSize.w = Math.max(safeContentSize.w, 
                (this.minSize.w - wPadding));
            safeContentSize.h = Math.max(safeContentSize.h, 
                (this.minSize.h - hPadding));
        }

        // prevent the popup from being bigger than a specified maximum size
        if (this.maxSize) {
            safeContentSize.w = Math.min(safeContentSize.w, 
                (this.maxSize.w - wPadding));
            safeContentSize.h = Math.min(safeContentSize.h, 
                (this.maxSize.h - hPadding));
        }
        
        //make sure the desired size to set doesn't result in a popup that 
        // is bigger than the map's viewport.
        //
        if (this.map && this.map.size) {
            
            var extraX = 0, extraY = 0;
            if (this.keepInMap && !this.panMapIfOutOfView) {
                var px = this.map.getPixelFromLonLat(this.lonlat);
                switch (this.relativePosition) {
                    case "tr":
                        extraX = px.x;
                        extraY = this.map.size.h - px.y;
                        break;
                    case "tl":
                        extraX = this.map.size.w - px.x;
                        extraY = this.map.size.h - px.y;
                        break;
                    case "bl":
                        extraX = this.map.size.w - px.x;
                        extraY = px.y;
                        break;
                    case "br":
                        extraX = px.x;
                        extraY = px.y;
                        break;
                    default:    
                        extraX = px.x;
                        extraY = this.map.size.h - px.y;
                        break;
                }
            }    
          
            var maxY = this.map.size.h - 
                this.map.paddingForPopups.top - 
                this.map.paddingForPopups.bottom - 
                hPadding - extraY;
            
            var maxX = this.map.size.w - 
                this.map.paddingForPopups.left - 
                this.map.paddingForPopups.right - 
                wPadding - extraX;
            
            safeContentSize.w = Math.min(safeContentSize.w, maxX);
            safeContentSize.h = Math.min(safeContentSize.h, maxY);
        }
        
        return safeContentSize;
    },
    
    /**
     * Method: getContentDivPadding
     * Glorious, oh glorious hack in order to determine the css 'padding' of 
     *     the contentDiv. IE/Opera return null here unless we actually add the 
     *     popup's main 'div' element (which contains contentDiv) to the DOM. 
     *     So we make it invisible and then add it to the document temporarily. 
     *
     *     Once we've taken the padding readings we need, we then remove it 
     *     from the DOM (it will actually get added to the DOM in 
     *     Map.js's addPopup)
     *
     * Returns:
     * {<OpenLayers.Bounds>}
     */
    getContentDivPadding: function() {

        //use cached value if we have it
        var contentDivPadding = this._contentDivPadding;
        if (!contentDivPadding) {

        	if (this.div.parentNode == null) {
	        	//make the div invisible and add it to the page        
	            this.div.style.display = "none";
	            document.body.appendChild(this.div);
	    	}
	            
            //read the padding settings from css, put them in an OL.Bounds        
            contentDivPadding = new OpenLayers.Bounds(
                OpenLayers.Element.getStyle(this.contentDiv, "padding-left"),
                OpenLayers.Element.getStyle(this.contentDiv, "padding-bottom"),
                OpenLayers.Element.getStyle(this.contentDiv, "padding-right"),
                OpenLayers.Element.getStyle(this.contentDiv, "padding-top")
            );
    
            //cache the value
            this._contentDivPadding = contentDivPadding;

            if (this.div.parentNode == document.body) {
	            //remove the div from the page and make it visible again
	            document.body.removeChild(this.div);
	            this.div.style.display = "";
            }
        }
        return contentDivPadding;
    },

    /**
     * Method: addCloseBox
     * 
     * Parameters:
     * callback - {Function} The callback to be called when the close button
     *     is clicked.
     */
    addCloseBox: function(callback) {

        this.closeDiv = OpenLayers.Util.createDiv(
            this.id + "_close", null, new OpenLayers.Size(17, 17)
        );
        this.closeDiv.className = "olPopupCloseBox"; 
        
        // use the content div's css padding to determine if we should
        //  padd the close div
        var contentDivPadding = this.getContentDivPadding();
         
        this.closeDiv.style.right = contentDivPadding.right + "px";
        this.closeDiv.style.top = contentDivPadding.top + "px";
        this.groupDiv.appendChild(this.closeDiv);

        var closePopup = callback || function(e) {
            this.hide();
            OpenLayers.Event.stop(e);
        };
        OpenLayers.Event.observe(this.closeDiv, "click", 
                OpenLayers.Function.bindAsEventListener(closePopup, this));
    },

    /**
     * Method: panIntoView
     * Pans the map such that the popup is totaly viewable (if necessary)
     */
    panIntoView: function() {
        
        var mapSize = this.map.getSize();
    
        //start with the top left corner of the popup, in px, 
        // relative to the viewport
        var origTL = this.map.getViewPortPxFromLayerPx( new OpenLayers.Pixel(
            parseInt(this.div.style.left),
            parseInt(this.div.style.top)
        ));
        var newTL = origTL.clone();
    
        //new left (compare to margins, using this.size to calculate right)
        if (origTL.x < this.map.paddingForPopups.left) {
            newTL.x = this.map.paddingForPopups.left;
        } else 
        if ( (origTL.x + this.size.w) > (mapSize.w - this.map.paddingForPopups.right)) {
            newTL.x = mapSize.w - this.map.paddingForPopups.right - this.size.w;
        }
        
        //new top (compare to margins, using this.size to calculate bottom)
        if (origTL.y < this.map.paddingForPopups.top) {
            newTL.y = this.map.paddingForPopups.top;
        } else 
        if ( (origTL.y + this.size.h) > (mapSize.h - this.map.paddingForPopups.bottom)) {
            newTL.y = mapSize.h - this.map.paddingForPopups.bottom - this.size.h;
        }
        
        var dx = origTL.x - newTL.x;
        var dy = origTL.y - newTL.y;
        
        this.map.pan(dx, dy);
    },

    /** 
     * Method: registerEvents
     * Registers events on the popup.
     *
     * Do this in a separate function so that subclasses can 
     *   choose to override it if they wish to deal differently
     *   with mouse events
     * 
     *   Note in the following handler functions that some special
     *    care is needed to deal correctly with mousing and popups. 
     *   
     *   Because the user might select the zoom-rectangle option and
     *    then drag it over a popup, we need a safe way to allow the
     *    mousemove and mouseup events to pass through the popup when
     *    they are initiated from outside.
     * 
     *   Otherwise, we want to essentially kill the event propagation
     *    for all other events, though we have to do so carefully, 
     *    without disabling basic html functionality, like clicking on 
     *    hyperlinks or drag-selecting text.
     */
     registerEvents:function() {
        this.events = new OpenLayers.Events(this, this.div, null, true);

        this.events.on({
            "mousedown": this.onmousedown,
            "mousemove": this.onmousemove,
            "mouseup": this.onmouseup,
            "click": this.onclick,
            "mouseout": this.onmouseout,
            "dblclick": this.ondblclick,
            scope: this
        });
        
     },

    /** 
     * Method: onmousedown 
     * When mouse goes down within the popup, make a note of
     *   it locally, and then do not propagate the mousedown 
     *   (but do so safely so that user can select text inside)
     * 
     * Parameters:
     * evt - {Event} 
     */
    onmousedown: function (evt) {
        this.mousedown = true;
        OpenLayers.Event.stop(evt, true);
    },

    /** 
     * Method: onmousemove
     * If the drag was started within the popup, then 
     *   do not propagate the mousemove (but do so safely
     *   so that user can select text inside)
     * 
     * Parameters:
     * evt - {Event} 
     */
    onmousemove: function (evt) {
        if (this.mousedown) {
            OpenLayers.Event.stop(evt, true);
        }
    },

    /** 
     * Method: onmouseup
     * When mouse comes up within the popup, after going down 
     *   in it, reset the flag, and then (once again) do not 
     *   propagate the event, but do so safely so that user can 
     *   select text inside
     * 
     * Parameters:
     * evt - {Event} 
     */
    onmouseup: function (evt) {
        if (this.mousedown) {
            this.mousedown = false;
            OpenLayers.Event.stop(evt, true);
        }
    },

    /**
     * Method: onclick
     * Ignore clicks, but allowing default browser handling
     * 
     * Parameters:
     * evt - {Event} 
     */
    onclick: function (evt) {
        OpenLayers.Event.stop(evt, true);
    },

    /** 
     * Method: onmouseout
     * When mouse goes out of the popup set the flag to false so that
     *   if they let go and then drag back in, we won't be confused.
     * 
     * Parameters:
     * evt - {Event} 
     */
    onmouseout: function (evt) {
        this.mousedown = false;
    },
    
    /** 
     * Method: ondblclick
     * Ignore double-clicks, but allowing default browser handling
     * 
     * Parameters:
     * evt - {Event} 
     */
    ondblclick: function (evt) {
        OpenLayers.Event.stop(evt, true);
    },

    CLASS_NAME: "OpenLayers.Popup"
});

OpenLayers.Popup.WIDTH = 200;
OpenLayers.Popup.HEIGHT = 200;
OpenLayers.Popup.COLOR = "white";
OpenLayers.Popup.OPACITY = 1;
OpenLayers.Popup.BORDER = "0px";
/* ======================================================================
    OpenLayers/Renderer.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */

/**
 * Class: OpenLayers.Renderer 
 * This is the base class for all renderers.
 *
 * This is based on a merger code written by Paul Spencer and Bertil Chapuis.
 * It is largely composed of virtual functions that are to be implemented
 * in technology-specific subclasses, but there is some generic code too.
 * 
 * The functions that *are* implemented here merely deal with the maintenance
 *  of the size and extent variables, as well as the cached 'resolution' 
 *  value. 
 * 
 * A note to the user that all subclasses should use getResolution() instead
 *  of directly accessing this.resolution in order to correctly use the 
 *  cacheing system.
 *
 */
OpenLayers.Renderer = OpenLayers.Class({

    /** 
     * Property: container
     * {DOMElement} 
     */
    container: null,
    
    /**
     * Property: root
     * {DOMElement}
     */
    root: null,

    /** 
     * Property: extent
     * {<OpenLayers.Bounds>}
     */
    extent: null,

    /**
     * Property: locked
     * {Boolean} If the renderer is currently in a state where many things
     *     are changing, the 'locked' property is set to true. This means 
     *     that renderers can expect at least one more drawFeature event to be
     *     called with the 'locked' property set to 'true': In some renderers,
     *     this might make sense to use as a 'only update local information'
     *     flag. 
     */  
    locked: false,
    
    /** 
     * Property: size
     * {<OpenLayers.Size>} 
     */
    size: null,
    
    /**
     * Property: resolution
     * {Float} cache of current map resolution
     */
    resolution: null,
    
    /**
     * Property: map  
     * {<OpenLayers.Map>} Reference to the map -- this is set in Vector's setMap()
     */
    map: null,
    
    /**
     * Constructor: OpenLayers.Renderer 
     *
     * Parameters:
     * containerID - {<String>} 
     * options - {Object} options for this renderer. See sublcasses for
     *     supported options.
     */
    initialize: function(containerID, options) {
        this.container = OpenLayers.Util.getElement(containerID);
    },
    
    /**
     * APIMethod: destroy
     */
    destroy: function() {
        this.container = null;
        this.extent = null;
        this.size =  null;
        this.resolution = null;
        this.map = null;
    },

    /**
     * APIMethod: supported
     * This should be overridden by specific subclasses
     * 
     * Returns:
     * {Boolean} Whether or not the browser supports the renderer class
     */
    supported: function() {
        return false;
    },    
    
    /**
     * Method: setExtent
     * Set the visible part of the layer.
     *
     * Resolution has probably changed, so we nullify the resolution 
     * cache (this.resolution) -- this way it will be re-computed when 
     * next it is needed.
     * We nullify the resolution cache (this.resolution) if resolutionChanged
     * is set to true - this way it will be re-computed on the next
     * getResolution() request.
     *
     * Parameters:
     * extent - {<OpenLayers.Bounds>}
     * resolutionChanged - {Boolean}
     */
    setExtent: function(extent, resolutionChanged) {
        this.extent = extent.clone();
        if (resolutionChanged) {
            this.resolution = null;
        }
    },
    
    /**
     * Method: setSize
     * Sets the size of the drawing surface.
     * 
     * Resolution has probably changed, so we nullify the resolution 
     * cache (this.resolution) -- this way it will be re-computed when 
     * next it is needed.
     *
     * Parameters:
     * size - {<OpenLayers.Size>} 
     */
    setSize: function(size) {
        this.size = size.clone();
        this.resolution = null;
    },
    
    /** 
     * Method: getResolution
     * Uses cached copy of resolution if available to minimize computing
     * 
     * Returns:
     * The current map's resolution
     */
    getResolution: function() {
        this.resolution = this.resolution || this.map.getResolution();
        return this.resolution;
    },
    
    /**
     * Method: drawFeature
     * Draw the feature.  The optional style argument can be used
     * to override the feature's own style.  This method should only
     * be called from layer.drawFeature().
     *
     * Parameters:
     * feature - {<OpenLayers.Feature.Vector>} 
     * style - {<Object>}
     * 
     * Returns:
     * {Boolean} true if the feature has been drawn completely, false if not,
     *     undefined if the feature had no geometry
     */
    drawFeature: function(feature, style) {
        if(style == null) {
            style = feature.style;
        }
        if (feature.geometry) {
            var bounds = feature.geometry.getBounds();
            if(bounds) {
                if (!bounds.intersectsBounds(this.extent)) {
                    style = {display: "none"};
                }
                var rendered = this.drawGeometry(feature.geometry, style, feature.id);
                if(style.display != "none" && style.label && rendered !== false) {
                    var location = feature.geometry.getCentroid(); 
                    if(style.labelXOffset || style.labelYOffset) {
                        xOffset = isNaN(style.labelXOffset) ? 0 : style.labelXOffset;
                        yOffset = isNaN(style.labelYOffset) ? 0 : style.labelYOffset;
                        var res = this.getResolution();
                        location.move(xOffset*res, yOffset*res);
                    }
                    this.drawText(feature.id, style, location);
                } else {
                    this.removeText(feature.id);
                }
                return rendered;
            }
        }
    },


    /** 
     * Method: drawGeometry
     * 
     * Draw a geometry.  This should only be called from the renderer itself.
     * Use layer.drawFeature() from outside the renderer.
     * virtual function
     *
     * Parameters:
     * geometry - {<OpenLayers.Geometry>} 
     * style - {Object} 
     * featureId - {<String>} 
     */
    drawGeometry: function(geometry, style, featureId) {},
        
    /**
     * Method: drawText
     * Function for drawing text labels.
     * This method is only called by the renderer itself.
     * 
     * Parameters: 
     * featureId - {String}
     * style -
     * location - {<OpenLayers.Geometry.Point>}
     */
    drawText: function(featureId, style, location) {},

    /**
     * Method: removeText
     * Function for removing text labels.
     * This method is only called by the renderer itself.
     * 
     * Parameters: 
     * featureId - {String}
     */
    removeText: function(featureId) {},
    
    /**
     * Method: clear
     * Clear all vectors from the renderer.
     * virtual function.
     */    
    clear: function() {},

    /**
     * Method: getFeatureIdFromEvent
     * Returns a feature id from an event on the renderer.  
     * How this happens is specific to the renderer.  This should be
     * called from layer.getFeatureFromEvent().
     * Virtual function.
     * 
     * Parameters:
     * evt - {<OpenLayers.Event>} 
     *
     * Returns:
     * {String} A feature id or null.
     */
    getFeatureIdFromEvent: function(evt) {},
    
    /**
     * Method: eraseFeatures 
     * This is called by the layer to erase features
     * 
     * Parameters:
     * features - {Array(<OpenLayers.Feature.Vector>)} 
     */
    eraseFeatures: function(features) {
        if(!(features instanceof Array)) {
            features = [features];
        }
        for(var i=0, len=features.length; i<len; ++i) {
            this.eraseGeometry(features[i].geometry);
            this.removeText(features[i].id);
        }
    },
    
    /**
     * Method: eraseGeometry
     * Remove a geometry from the renderer (by id).
     * virtual function.
     * 
     * Parameters:
     * geometry - {<OpenLayers.Geometry>} 
     */
    eraseGeometry: function(geometry) {},
    
    /**
     * Method: moveRoot
     * moves this renderer's root to a (different) renderer.
     * To be implemented by subclasses that require a common renderer root for
     * feature selection.
     * 
     * Parameters:
     * renderer - {<OpenLayers.Renderer>} target renderer for the moved root
     */
    moveRoot: function(renderer) {},

    /**
     * Method: getRenderLayerId
     * Gets the layer that this renderer's output appears on. If moveRoot was
     * used, this will be different from the id of the layer containing the
     * features rendered by this renderer.
     * 
     * Returns:
     * {String} the id of the output layer.
     */
    getRenderLayerId: function() {
        return this.container.id;
    },

    CLASS_NAME: "OpenLayers.Renderer"
});
/* ======================================================================
    OpenLayers/BaseTypes/Bounds.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */

/**
 * @requires OpenLayers/Console.js
 */

/**
 * Class: OpenLayers.Bounds
 * Instances of this class represent bounding boxes.  Data stored as left,
 * bottom, right, top floats. All values are initialized to null, however,
 * you should make sure you set them before using the bounds for anything.
 * 
 * Possible use case:
 * > bounds = new OpenLayers.Bounds();
 * > bounds.extend(new OpenLayers.LonLat(4,5));
 * > bounds.extend(new OpenLayers.LonLat(5,6));
 * > bounds.toBBOX(); // returns 4,5,5,6
 */
OpenLayers.Bounds = OpenLayers.Class({

    /**
     * Property: left
     * {Number} Minimum horizontal coordinate.
     */
    left: null,

    /**
     * Property: bottom
     * {Number} Minimum vertical coordinate.
     */
    bottom: null,

    /**
     * Property: right
     * {Number} Maximum horizontal coordinate.
     */
    right: null,

    /**
     * Property: top
     * {Number} Maximum vertical coordinate.
     */
    top: null,
    
    /**
     * Property: centerLonLat
     * {<OpenLayers.LonLat>} A cached center location.  This should not be
     *     accessed directly.  Use <getCenterLonLat> instead.
     */
    centerLonLat: null,

    /**
     * Constructor: OpenLayers.Bounds
     * Construct a new bounds object.
     *
     * Parameters:
     * left - {Number} The left bounds of the box.  Note that for width
     *        calculations, this is assumed to be less than the right value.
     * bottom - {Number} The bottom bounds of the box.  Note that for height
     *          calculations, this is assumed to be more than the top value.
     * right - {Number} The right bounds.
     * top - {Number} The top bounds.
     */
    initialize: function(left, bottom, right, top) {
        if (left != null) {
            this.left = OpenLayers.Util.toFloat(left);
        }
        if (bottom != null) {
            this.bottom = OpenLayers.Util.toFloat(bottom);
        }
        if (right != null) {
            this.right = OpenLayers.Util.toFloat(right);
        }
        if (top != null) {
            this.top = OpenLayers.Util.toFloat(top);
        }
    },

    /**
     * Method: clone
     * Create a cloned instance of this bounds.
     *
     * Returns:
     * {<OpenLayers.Bounds>} A fresh copy of the bounds
     */
    clone:function() {
        return new OpenLayers.Bounds(this.left, this.bottom, 
                                     this.right, this.top);
    },

    /**
     * Method: equals
     * Test a two bounds for equivalence.
     *
     * Parameters:
     * bounds - {<OpenLayers.Bounds>}
     *
     * Returns:
     * {Boolean} The passed-in bounds object has the same left,
     *           right, top, bottom components as this.  Note that if bounds 
     *           passed in is null, returns false.
     */
    equals:function(bounds) {
        var equals = false;
        if (bounds != null) {
            equals = ((this.left == bounds.left) && 
                      (this.right == bounds.right) &&
                      (this.top == bounds.top) && 
                      (this.bottom == bounds.bottom));
        }
        return equals;
    },

    /** 
     * APIMethod: toString
     * 
     * Returns:
     * {String} String representation of bounds object. 
     *          (ex.<i>"left-bottom=(5,42) right-top=(10,45)"</i>)
     */
    toString:function() {
        return ( "left-bottom=(" + this.left + "," + this.bottom + ")"
                 + " right-top=(" + this.right + "," + this.top + ")" );
    },

    /**
     * APIMethod: toArray
     *
     * Parameters:
     * reverseAxisOrder - {Boolean} Should we reverse the axis order?
     *
     * Returns:
     * {Array} array of left, bottom, right, top
     */
    toArray: function(reverseAxisOrder) {
        if (reverseAxisOrder === true) {
            return [this.bottom, this.left, this.top, this.right];
        } else {
            return [this.left, this.bottom, this.right, this.top];
        }
    },    

    /** 
     * APIMethod: toBBOX
     * 
     * Parameters:
     * decimal - {Integer} How many significant digits in the bbox coords?
     *                     Default is 6
     * reverseAxisOrder - {Boolean} Should we reverse the axis order?
     * 
     * Returns:
     * {String} Simple String representation of bounds object.
     *          (ex. <i>"5,42,10,45"</i>)
     */
    toBBOX:function(decimal, reverseAxisOrder) {
        if (decimal== null) {
            decimal = 6; 
        }
        var mult = Math.pow(10, decimal);
        var xmin = Math.round(this.left * mult) / mult;
        var ymin = Math.round(this.bottom * mult) / mult;
        var xmax = Math.round(this.right * mult) / mult;
        var ymax = Math.round(this.top * mult) / mult;
        if (reverseAxisOrder === true) {
            return ymin + "," + xmin + "," + ymax + "," + xmax;
        } else {
            return xmin + "," + ymin + "," + xmax + "," + ymax;
        }
    },
 
    /**
     * APIMethod: toGeometry
     * Create a new polygon geometry based on this bounds.
     *
     * Returns:
     * {<OpenLayers.Geometry.Polygon>} A new polygon with the coordinates
     *     of this bounds.
     */
    toGeometry: function() {
        return new OpenLayers.Geometry.Polygon([
            new OpenLayers.Geometry.LinearRing([
                new OpenLayers.Geometry.Point(this.left, this.bottom),
                new OpenLayers.Geometry.Point(this.right, this.bottom),
                new OpenLayers.Geometry.Point(this.right, this.top),
                new OpenLayers.Geometry.Point(this.left, this.top)
            ])
        ]);
    },
    
    /**
     * APIMethod: getWidth
     * 
     * Returns:
     * {Float} The width of the bounds
     */
    getWidth:function() {
        return (this.right - this.left);
    },

    /**
     * APIMethod: getHeight
     * 
     * Returns:
     * {Float} The height of the bounds (top minus bottom).
     */
    getHeight:function() {
        return (this.top - this.bottom);
    },

    /**
     * APIMethod: getSize
     * 
     * Returns:
     * {<OpenLayers.Size>} The size of the box.
     */
    getSize:function() {
        return new OpenLayers.Size(this.getWidth(), this.getHeight());
    },

    /**
     * APIMethod: getCenterPixel
     * 
     * Returns:
     * {<OpenLayers.Pixel>} The center of the bounds in pixel space.
     */
    getCenterPixel:function() {
        return new OpenLayers.Pixel( (this.left + this.right) / 2,
                                     (this.bottom + this.top) / 2);
    },

    /**
     * APIMethod: getCenterLonLat
     * 
     * Returns:
     * {<OpenLayers.LonLat>} The center of the bounds in map space.
     */
    getCenterLonLat:function() {
        if(!this.centerLonLat) {
            this.centerLonLat = new OpenLayers.LonLat(
                (this.left + this.right) / 2, (this.bottom + this.top) / 2
            );
        }
        return this.centerLonLat;
    },

    /**
     * Method: scale
     * Scales the bounds around a pixel or lonlat. Note that the new 
     *     bounds may return non-integer properties, even if a pixel
     *     is passed. 
     * 
     * Parameters:
     * ratio - {Float} 
     * origin - {<OpenLayers.Pixel> or <OpenLayers.LonLat>}
     *          Default is center.
     *
     * Returns:
     * {<OpenLayers.Bound>} A new bounds that is scaled by ratio
     *                      from origin.
     */

    scale: function(ratio, origin){
        if(origin == null){
            origin = this.getCenterLonLat();
        }
        
        var origx,origy;

        // get origin coordinates
        if(origin.CLASS_NAME == "OpenLayers.LonLat"){
            origx = origin.lon;
            origy = origin.lat;
        } else {
            origx = origin.x;
            origy = origin.y;
        }

        var left = (this.left - origx) * ratio + origx;
        var bottom = (this.bottom - origy) * ratio + origy;
        var right = (this.right - origx) * ratio + origx;
        var top = (this.top - origy) * ratio + origy;
        
        return new OpenLayers.Bounds(left, bottom, right, top);
    },

    /**
     * APIMethod: add
     * 
     * Parameters:
     * x - {Float}
     * y - {Float}
     * 
     * Returns:
     * {<OpenLayers.Bounds>} A new bounds whose coordinates are the same as
     *     this, but shifted by the passed-in x and y values.
     */
    add:function(x, y) {
        if ( (x == null) || (y == null) ) {
            var msg = OpenLayers.i18n("boundsAddError");
            OpenLayers.Console.error(msg);
            return null;
        }
        return new OpenLayers.Bounds(this.left + x, this.bottom + y,
                                     this.right + x, this.top + y);
    },
    
    /**
     * APIMethod: extend
     * Extend the bounds to include the point, lonlat, or bounds specified.
     *     Note, this function assumes that left < right and bottom < top.
     * 
     * Parameters: 
     * object - {Object} Can be LonLat, Point, or Bounds
     */
    extend:function(object) {
        var bounds = null;
        if (object) {
            // clear cached center location
            switch(object.CLASS_NAME) {
                case "OpenLayers.LonLat":    
                    bounds = new OpenLayers.Bounds(object.lon, object.lat,
                                                    object.lon, object.lat);
                    break;
                case "OpenLayers.Geometry.Point":
                    bounds = new OpenLayers.Bounds(object.x, object.y,
                                                    object.x, object.y);
                    break;
                    
                case "OpenLayers.Bounds":    
                    bounds = object;
                    break;
            }
    
            if (bounds) {
                this.centerLonLat = null;
                if ( (this.left == null) || (bounds.left < this.left)) {
                    this.left = bounds.left;
                }
                if ( (this.bottom == null) || (bounds.bottom < this.bottom) ) {
                    this.bottom = bounds.bottom;
                } 
                if ( (this.right == null) || (bounds.right > this.right) ) {
                    this.right = bounds.right;
                }
                if ( (this.top == null) || (bounds.top > this.top) ) { 
                    this.top = bounds.top;
                }
            }
        }
    },

    /**
     * APIMethod: containsLonLat
     * 
     * Parameters:
     * ll - {<OpenLayers.LonLat>}
     * inclusive - {Boolean} Whether or not to include the border.
     *     Default is true.
     *
     * Returns:
     * {Boolean} The passed-in lonlat is within this bounds.
     */
    containsLonLat:function(ll, inclusive) {
        return this.contains(ll.lon, ll.lat, inclusive);
    },

    /**
     * APIMethod: containsPixel
     * 
     * Parameters:
     * px - {<OpenLayers.Pixel>}
     * inclusive - {Boolean} Whether or not to include the border. Default is
     *     true.
     *
     * Returns:
     * {Boolean} The passed-in pixel is within this bounds.
     */
    containsPixel:function(px, inclusive) {
        return this.contains(px.x, px.y, inclusive);
    },
    
    /**
     * APIMethod: contains
     * 
     * Parameters:
     * x - {Float}
     * y - {Float}
     * inclusive - {Boolean} Whether or not to include the border. Default is
     *     true.
     *
     * Returns:
     * {Boolean} Whether or not the passed-in coordinates are within this
     *     bounds.
     */
    contains:function(x, y, inclusive) {
        //set default
        if (inclusive == null) {
            inclusive = true;
        }

        if (x == null || y == null) {
            return false;
        }

        x = OpenLayers.Util.toFloat(x);
        y = OpenLayers.Util.toFloat(y);

        var contains = false;
        if (inclusive) {
            contains = ((x >= this.left) && (x <= this.right) && 
                        (y >= this.bottom) && (y <= this.top));
        } else {
            contains = ((x > this.left) && (x < this.right) && 
                        (y > this.bottom) && (y < this.top));
        }              
        return contains;
    },

    /**
     * APIMethod: intersectsBounds
     * Determine whether the target bounds intersects this bounds.  Bounds are
     *     considered intersecting if any of their edges intersect or if one
     *     bounds contains the other.
     * 
     * Parameters:
     * bounds - {<OpenLayers.Bounds>} The target bounds.
     * inclusive - {Boolean} Treat coincident borders as intersecting.  Default
     *     is true.  If false, bounds that do not overlap but only touch at the
     *     border will not be considered as intersecting.
     *
     * Returns:
     * {Boolean} The passed-in bounds object intersects this bounds.
     */
    intersectsBounds:function(bounds, inclusive) {
        if (inclusive == null) {
            inclusive = true;
        }
        var intersects = false;
        var mightTouch = (
            this.left == bounds.right ||
            this.right == bounds.left ||
            this.top == bounds.bottom ||
            this.bottom == bounds.top
        );
        
        // if the two bounds only touch at an edge, and inclusive is false,
        // then the bounds don't *really* intersect.
        if (inclusive || !mightTouch) {
            // otherwise, if one of the boundaries even partially contains another,
            // inclusive of the edges, then they do intersect.
            var inBottom = (
                ((bounds.bottom >= this.bottom) && (bounds.bottom <= this.top)) ||
                ((this.bottom >= bounds.bottom) && (this.bottom <= bounds.top))
            );
            var inTop = (
                ((bounds.top >= this.bottom) && (bounds.top <= this.top)) ||
                ((this.top > bounds.bottom) && (this.top < bounds.top))
            );
            var inLeft = (
                ((bounds.left >= this.left) && (bounds.left <= this.right)) ||
                ((this.left >= bounds.left) && (this.left <= bounds.right))
            );
            var inRight = (
                ((bounds.right >= this.left) && (bounds.right <= this.right)) ||
                ((this.right >= bounds.left) && (this.right <= bounds.right))
            );
            intersects = ((inBottom || inTop) && (inLeft || inRight));
        }
        return intersects;
    },
    
    /**
     * APIMethod: containsBounds
     * Determine whether the target bounds is contained within this bounds.
     * 
     * bounds - {<OpenLayers.Bounds>} The target bounds.
     * partial - {Boolean} If any of the target corners is within this bounds
     *     consider the bounds contained.  Default is false.  If true, the
     *     entire target bounds must be contained within this bounds.
     * inclusive - {Boolean} Treat shared edges as contained.  Default is
     *     true.
     *
     * Returns:
     * {Boolean} The passed-in bounds object is contained within this bounds. 
     */
    containsBounds:function(bounds, partial, inclusive) {
        if (partial == null) {
            partial = false;
        }
        if (inclusive == null) {
            inclusive = true;
        }
        var bottomLeft  = this.contains(bounds.left, bounds.bottom, inclusive);
        var bottomRight = this.contains(bounds.right, bounds.bottom, inclusive);
        var topLeft  = this.contains(bounds.left, bounds.top, inclusive);
        var topRight = this.contains(bounds.right, bounds.top, inclusive);
        
        return (partial) ? (bottomLeft || bottomRight || topLeft || topRight)
                         : (bottomLeft && bottomRight && topLeft && topRight);
    },

    /** 
     * APIMethod: determineQuadrant
     * 
     * Parameters:
     * lonlat - {<OpenLayers.LonLat>}
     * 
     * Returns:
     * {String} The quadrant ("br" "tr" "tl" "bl") of the bounds in which the
     *     coordinate lies.
     */
    determineQuadrant: function(lonlat) {
    
        var quadrant = "";
        var center = this.getCenterLonLat();
        
        quadrant += (lonlat.lat < center.lat) ? "b" : "t";
        quadrant += (lonlat.lon < center.lon) ? "l" : "r";
    
        return quadrant; 
    },
    
    /**
     * APIMethod: transform
     * Transform the Bounds object from source to dest. 
     *
     * Parameters: 
     * source - {<OpenLayers.Projection>} Source projection. 
     * dest   - {<OpenLayers.Projection>} Destination projection. 
     *
     * Returns:
     * {<OpenLayers.Bounds>} Itself, for use in chaining operations.
     */
    transform: function(source, dest) {
        // clear cached center location
        this.centerLonLat = null;
        var ll = OpenLayers.Projection.transform(
            {'x': this.left, 'y': this.bottom}, source, dest);
        var lr = OpenLayers.Projection.transform(
            {'x': this.right, 'y': this.bottom}, source, dest);
        var ul = OpenLayers.Projection.transform(
            {'x': this.left, 'y': this.top}, source, dest);
        var ur = OpenLayers.Projection.transform(
            {'x': this.right, 'y': this.top}, source, dest);
        this.left   = Math.min(ll.x, ul.x);
        this.bottom = Math.min(ll.y, lr.y);
        this.right  = Math.max(lr.x, ur.x);
        this.top    = Math.max(ul.y, ur.y);
        return this;
    },

    /**
     * APIMethod: wrapDateLine
     *  
     * Parameters:
     * maxExtent - {<OpenLayers.Bounds>}
     * options - {Object} Some possible options are:
     *                    leftTolerance - {float} Allow for a margin of error 
     *                                            with the 'left' value of this 
     *                                            bound.
     *                                            Default is 0.
     *                    rightTolerance - {float} Allow for a margin of error 
     *                                             with the 'right' value of 
     *                                             this bound.
     *                                             Default is 0.
     * 
     * Returns:
     * {<OpenLayers.Bounds>} A copy of this bounds, but wrapped around the 
     *                       "dateline" (as specified by the borders of 
     *                       maxExtent). Note that this function only returns 
     *                       a different bounds value if this bounds is 
     *                       *entirely* outside of the maxExtent. If this 
     *                       bounds straddles the dateline (is part in/part 
     *                       out of maxExtent), the returned bounds will be 
     *                       merely a copy of this one.
     */
    wrapDateLine: function(maxExtent, options) {    
        options = options || {};
        
        var leftTolerance = options.leftTolerance || 0;
        var rightTolerance = options.rightTolerance || 0;

        var newBounds = this.clone();
    
        if (maxExtent) {

           //shift right?
           while ( newBounds.left < maxExtent.left && 
                   (newBounds.right - rightTolerance) <= maxExtent.left ) { 
                newBounds = newBounds.add(maxExtent.getWidth(), 0);
           }

           //shift left?
           while ( (newBounds.left + leftTolerance) >= maxExtent.right && 
                   newBounds.right > maxExtent.right ) { 
                newBounds = newBounds.add(-maxExtent.getWidth(), 0);
           }
        }
                
        return newBounds;
    },

    CLASS_NAME: "OpenLayers.Bounds"
});

/** 
 * APIFunction: fromString
 * Alternative constructor that builds a new OpenLayers.Bounds from a 
 *     parameter string
 * 
 * Parameters: 
 * str - {String}Comma-separated bounds string. (ex. <i>"5,42,10,45"</i>)
 * 
 * Returns:
 * {<OpenLayers.Bounds>} New bounds object built from the 
 *                       passed-in String.
 */
OpenLayers.Bounds.fromString = function(str) {
    var bounds = str.split(",");
    return OpenLayers.Bounds.fromArray(bounds);
};

/** 
 * APIFunction: fromArray
 * Alternative constructor that builds a new OpenLayers.Bounds
 *     from an array
 * 
 * Parameters:
 * bbox - {Array(Float)} Array of bounds values (ex. <i>[5,42,10,45]</i>)
 *
 * Returns:
 * {<OpenLayers.Bounds>} New bounds object built from the passed-in Array.
 */
OpenLayers.Bounds.fromArray = function(bbox) {
    return new OpenLayers.Bounds(parseFloat(bbox[0]),
                                 parseFloat(bbox[1]),
                                 parseFloat(bbox[2]),
                                 parseFloat(bbox[3]));
};

/** 
 * APIFunction: fromSize
 * Alternative constructor that builds a new OpenLayers.Bounds
 *     from a size
 * 
 * Parameters:
 * size - {<OpenLayers.Size>} 
 *
 * Returns:
 * {<OpenLayers.Bounds>} New bounds object built from the passed-in size.
 */
OpenLayers.Bounds.fromSize = function(size) {
    return new OpenLayers.Bounds(0,
                                 size.h,
                                 size.w,
                                 0);
};

/**
 * Function: oppositeQuadrant
 * Get the opposite quadrant for a given quadrant string.
 *
 * Parameters:
 * quadrant - {String} two character quadrant shortstring
 *
 * Returns:
 * {String} The opposing quadrant ("br" "tr" "tl" "bl"). For Example, if 
 *          you pass in "bl" it returns "tr", if you pass in "br" it 
 *          returns "tl", etc.
 */
OpenLayers.Bounds.oppositeQuadrant = function(quadrant) {
    var opp = "";
    
    opp += (quadrant.charAt(0) == 't') ? 'b' : 't';
    opp += (quadrant.charAt(1) == 'l') ? 'r' : 'l';
    
    return opp;
};
/* ======================================================================
    OpenLayers/BaseTypes/LonLat.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */

/**
 * @requires OpenLayers/Console.js
 */

/**
 * Class: OpenLayers.LonLat
 * This class represents a longitude and latitude pair
 */
OpenLayers.LonLat = OpenLayers.Class({

    /** 
     * APIProperty: lon
     * {Float} The x-axis coodinate in map units
     */
    lon: 0.0,
    
    /** 
     * APIProperty: lat
     * {Float} The y-axis coordinate in map units
     */
    lat: 0.0,

    /**
     * Constructor: OpenLayers.LonLat
     * Create a new map location.
     *
     * Parameters:
     * lon - {Number} The x-axis coordinate in map units.  If your map is in
     *     a geographic projection, this will be the Longitude.  Otherwise,
     *     it will be the x coordinate of the map location in your map units.
     * lat - {Number} The y-axis coordinate in map units.  If your map is in
     *     a geographic projection, this will be the Latitude.  Otherwise,
     *     it will be the y coordinate of the map location in your map units.
     */
    initialize: function(lon, lat) {
        this.lon = OpenLayers.Util.toFloat(lon);
        this.lat = OpenLayers.Util.toFloat(lat);
    },
    
    /**
     * Method: toString
     * Return a readable string version of the lonlat
     *
     * Returns:
     * {String} String representation of OpenLayers.LonLat object. 
     *           (ex. <i>"lon=5,lat=42"</i>)
     */
    toString:function() {
        return ("lon=" + this.lon + ",lat=" + this.lat);
    },

    /** 
     * APIMethod: toShortString
     * 
     * Returns:
     * {String} Shortened String representation of OpenLayers.LonLat object. 
     *         (ex. <i>"5, 42"</i>)
     */
    toShortString:function() {
        return (this.lon + ", " + this.lat);
    },

    /** 
     * APIMethod: clone
     * 
     * Returns:
     * {<OpenLayers.LonLat>} New OpenLayers.LonLat object with the same lon 
     *                       and lat values
     */
    clone:function() {
        return new OpenLayers.LonLat(this.lon, this.lat);
    },

    /** 
     * APIMethod: add
     * 
     * Parameters:
     * lon - {Float}
     * lat - {Float}
     * 
     * Returns:
     * {<OpenLayers.LonLat>} A new OpenLayers.LonLat object with the lon and 
     *                       lat passed-in added to this's. 
     */
    add:function(lon, lat) {
        if ( (lon == null) || (lat == null) ) {
            var msg = OpenLayers.i18n("lonlatAddError");
            OpenLayers.Console.error(msg);
            return null;
        }
        return new OpenLayers.LonLat(this.lon + lon, this.lat + lat);
    },

    /** 
     * APIMethod: equals
     * 
     * Parameters:
     * ll - {<OpenLayers.LonLat>}
     * 
     * Returns:
     * {Boolean} Boolean value indicating whether the passed-in 
     *           <OpenLayers.LonLat> object has the same lon and lat 
     *           components as this.
     *           Note: if ll passed in is null, returns false
     */
    equals:function(ll) {
        var equals = false;
        if (ll != null) {
            equals = ((this.lon == ll.lon && this.lat == ll.lat) ||
                      (isNaN(this.lon) && isNaN(this.lat) && isNaN(ll.lon) && isNaN(ll.lat)));
        }
        return equals;
    },

    /**
     * APIMethod: transform
     * Transform the LonLat object from source to dest. This transformation is
     *    *in place*: if you want a *new* lonlat, use .clone() first.
     *
     * Parameters: 
     * source - {<OpenLayers.Projection>} Source projection. 
     * dest   - {<OpenLayers.Projection>} Destination projection. 
     *
     * Returns:
     * {<OpenLayers.LonLat>} Itself, for use in chaining operations.
     */
    transform: function(source, dest) {
        var point = OpenLayers.Projection.transform(
            {'x': this.lon, 'y': this.lat}, source, dest);
        this.lon = point.x;
        this.lat = point.y;
        return this;
    },
    
    /**
     * APIMethod: wrapDateLine
     * 
     * Parameters:
     * maxExtent - {<OpenLayers.Bounds>}
     * 
     * Returns:
     * {<OpenLayers.LonLat>} A copy of this lonlat, but wrapped around the 
     *                       "dateline" (as specified by the borders of 
     *                       maxExtent)
     */
    wrapDateLine: function(maxExtent) {    

        var newLonLat = this.clone();
    
        if (maxExtent) {
            //shift right?
            while (newLonLat.lon < maxExtent.left) {
                newLonLat.lon +=  maxExtent.getWidth();
            }    
           
            //shift left?
            while (newLonLat.lon > maxExtent.right) {
                newLonLat.lon -= maxExtent.getWidth();
            }    
        }
                
        return newLonLat;
    },

    CLASS_NAME: "OpenLayers.LonLat"
});

/** 
 * Function: fromString
 * Alternative constructor that builds a new <OpenLayers.LonLat> from a 
 *     parameter string
 * 
 * Parameters:
 * str - {String} Comma-separated Lon,Lat coordinate string. 
 *                 (ex. <i>"5,40"</i>)
 * 
 * Returns:
 * {<OpenLayers.LonLat>} New <OpenLayers.LonLat> object built from the 
 *                       passed-in String.
 */
OpenLayers.LonLat.fromString = function(str) {
    var pair = str.split(",");
    return new OpenLayers.LonLat(parseFloat(pair[0]), 
                                 parseFloat(pair[1]));
};
/* ======================================================================
    OpenLayers/BaseTypes/Pixel.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */

/**
 * @requires OpenLayers/Console.js
 */

/**
 * Class: OpenLayers.Pixel
 * This class represents a screen coordinate, in x and y coordinates
 */
OpenLayers.Pixel = OpenLayers.Class({
    
    /**
     * APIProperty: x
     * {Number} The x coordinate
     */
    x: 0.0,

    /**
     * APIProperty: y
     * {Number} The y coordinate
     */
    y: 0.0,
    
    /**
     * Constructor: OpenLayers.Pixel
     * Create a new OpenLayers.Pixel instance
     *
     * Parameters:
     * x - {Number} The x coordinate
     * y - {Number} The y coordinate
     *
     * Returns:
     * An instance of OpenLayers.Pixel
     */
    initialize: function(x, y) {
        this.x = parseFloat(x);
        this.y = parseFloat(y);
    },
    
    /**
     * Method: toString
     * Cast this object into a string
     *
     * Returns:
     * {String} The string representation of Pixel. ex: "x=200.4,y=242.2"
     */
    toString:function() {
        return ("x=" + this.x + ",y=" + this.y);
    },

    /**
     * APIMethod: clone
     * Return a clone of this pixel object
     *
     * Returns:
     * {<OpenLayers.Pixel>} A clone pixel
     */
    clone:function() {
        return new OpenLayers.Pixel(this.x, this.y); 
    },
    
    /**
     * APIMethod: equals
     * Determine whether one pixel is equivalent to another
     *
     * Parameters:
     * px - {<OpenLayers.Pixel>}
     *
     * Returns:
     * {Boolean} The point passed in as parameter is equal to this. Note that
     * if px passed in is null, returns false.
     */
    equals:function(px) {
        var equals = false;
        if (px != null) {
            equals = ((this.x == px.x && this.y == px.y) ||
                      (isNaN(this.x) && isNaN(this.y) && isNaN(px.x) && isNaN(px.y)));
        }
        return equals;
    },

    /**
     * APIMethod: add
     *
     * Parameters:
     * x - {Integer}
     * y - {Integer}
     *
     * Returns:
     * {<OpenLayers.Pixel>} A new Pixel with this pixel's x&y augmented by the 
     * values passed in.
     */
    add:function(x, y) {
        if ( (x == null) || (y == null) ) {
            var msg = OpenLayers.i18n("pixelAddError");
            OpenLayers.Console.error(msg);
            return null;
        }
        return new OpenLayers.Pixel(this.x + x, this.y + y);
    },

    /**
    * APIMethod: offset
    * 
    * Parameters
    * px - {<OpenLayers.Pixel>}
    * 
    * Returns:
    * {<OpenLayers.Pixel>} A new Pixel with this pixel's x&y augmented by the 
    *                      x&y values of the pixel passed in.
    */
    offset:function(px) {
        var newPx = this.clone();
        if (px) {
            newPx = this.add(px.x, px.y);
        }
        return newPx;
    },

    CLASS_NAME: "OpenLayers.Pixel"
});
/* ======================================================================
    OpenLayers/Control.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */

/**
 * @requires OpenLayers/Console.js
 */

/**
 * Class: OpenLayers.Control
 * Controls affect the display or behavior of the map. They allow everything
 * from panning and zooming to displaying a scale indicator. Controls by 
 * default are added to the map they are contained within however it is
 * possible to add a control to an external div by passing the div in the
 * options parameter.
 * 
 * Example:
 * The following example shows how to add many of the common controls
 * to a map.
 * 
 * > var map = new OpenLayers.Map('map', { controls: [] });
 * >
 * > map.addControl(new OpenLayers.Control.PanZoomBar());
 * > map.addControl(new OpenLayers.Control.MouseToolbar());
 * > map.addControl(new OpenLayers.Control.LayerSwitcher({'ascending':false}));
 * > map.addControl(new OpenLayers.Control.Permalink());
 * > map.addControl(new OpenLayers.Control.Permalink('permalink'));
 * > map.addControl(new OpenLayers.Control.MousePosition());
 * > map.addControl(new OpenLayers.Control.OverviewMap());
 * > map.addControl(new OpenLayers.Control.KeyboardDefaults());
 *
 * The next code fragment is a quick example of how to intercept 
 * shift-mouse click to display the extent of the bounding box
 * dragged out by the user.  Usually controls are not created
 * in exactly this manner.  See the source for a more complete 
 * example:
 *
 * > var control = new OpenLayers.Control();
 * > OpenLayers.Util.extend(control, {
 * >     draw: function () {
 * >         // this Handler.Box will intercept the shift-mousedown
 * >         // before Control.MouseDefault gets to see it
 * >         this.box = new OpenLayers.Handler.Box( control, 
 * >             {"done": this.notice},
 * >             {keyMask: OpenLayers.Handler.MOD_SHIFT});
 * >         this.box.activate();
 * >     },
 * >
 * >     notice: function (bounds) {
 * >         OpenLayers.Console.userError(bounds);
 * >     }
 * > }); 
 * > map.addControl(control);
 * 
 */
OpenLayers.Control = OpenLayers.Class({

    /** 
     * Property: id 
     * {String} 
     */
    id: null,
    
    /** 
     * Property: map 
     * {<OpenLayers.Map>} this gets set in the addControl() function in
     * OpenLayers.Map 
     */
    map: null,

    /** 
     * Property: div 
     * {DOMElement} 
     */
    div: null,

    /** 
     * Property: type 
     * {OpenLayers.Control.TYPES} Controls can have a 'type'. The type
     * determines the type of interactions which are possible with them when
     * they are placed into a toolbar. 
     */
    type: null, 

    /** 
     * Property: allowSelection
     * {Boolean} By deafault, controls do not allow selection, because
     * it may interfere with map dragging. If this is true, OpenLayers
     * will not prevent selection of the control.
     * Default is false.
     */
    allowSelection: false,  

    /** 
     * Property: displayClass 
     * {string}  This property is used for CSS related to the drawing of the
     * Control. 
     */
    displayClass: "",
    
    /**
    * Property: title  
    * {string}  This property is used for showing a tooltip over the  
    * Control.  
    */ 
    title: "",

    /**
     * APIProperty: autoActivate
     * {Boolean} Activate the control when it is added to a map.  Default is
     *     false.
     */
    autoActivate: false,

    /** 
     * Property: active 
     * {Boolean} The control is active.
     */
    active: null,

    /** 
     * Property: handler 
     * {<OpenLayers.Handler>} null
     */
    handler: null,

    /**
     * APIProperty: eventListeners
     * {Object} If set as an option at construction, the eventListeners
     *     object will be registered with <OpenLayers.Events.on>.  Object
     *     structure must be a listeners object as shown in the example for
     *     the events.on method.
     */
    eventListeners: null,

    /** 
     * Property: events
     * {<OpenLayers.Events>} Events instance for triggering control specific
     *     events.
     */
    events: null,

    /**
     * Constant: EVENT_TYPES
     * {Array(String)} Supported application event types.  Register a listener
     *     for a particular event with the following syntax:
     * (code)
     * control.events.register(type, obj, listener);
     * (end)
     *
     * Listeners will be called with a reference to an event object.  The
     *     properties of this event depends on exactly what happened.
     *
     * All event objects have at least the following properties:
     * object - {Object} A reference to control.events.object (a reference
     *      to the control).
     * element - {DOMElement} A reference to control.events.element (which
     *      will be null unless documented otherwise).
     *
     * Supported map event types:
     * activate - Triggered when activated.
     * deactivate - Triggered when deactivated.
     */
    EVENT_TYPES: ["activate", "deactivate"],

    /**
     * Constructor: OpenLayers.Control
     * Create an OpenLayers Control.  The options passed as a parameter
     * directly extend the control.  For example passing the following:
     * 
     * > var control = new OpenLayers.Control({div: myDiv});
     *
     * Overrides the default div attribute value of null.
     * 
     * Parameters:
     * options - {Object} 
     */
    initialize: function (options) {
        // We do this before the extend so that instances can override
        // className in options.
        this.displayClass = 
            this.CLASS_NAME.replace("OpenLayers.", "ol").replace(/\./g, "");
        
        OpenLayers.Util.extend(this, options);
        
        this.events = new OpenLayers.Events(this, null, this.EVENT_TYPES);
        if(this.eventListeners instanceof Object) {
            this.events.on(this.eventListeners);
        }
        if (this.id == null) {
            this.id = OpenLayers.Util.createUniqueID(this.CLASS_NAME + "_");
        }
    },

    /**
     * Method: destroy
     * The destroy method is used to perform any clean up before the control
     * is dereferenced.  Typically this is where event listeners are removed
     * to prevent memory leaks.
     */
    destroy: function () {
        if(this.events) {
            if(this.eventListeners) {
                this.events.un(this.eventListeners);
            }
            this.events.destroy();
            this.events = null;
        }
        this.eventListeners = null;

        // eliminate circular references
        if (this.handler) {
            this.handler.destroy();
            this.handler = null;
        }
        if(this.handlers) {
            for(var key in this.handlers) {
                if(this.handlers.hasOwnProperty(key) &&
                   typeof this.handlers[key].destroy == "function") {
                    this.handlers[key].destroy();
                }
            }
            this.handlers = null;
        }
        if (this.map) {
            this.map.removeControl(this);
            this.map = null;
        }
    },

    /** 
     * Method: setMap
     * Set the map property for the control. This is done through an accessor
     * so that subclasses can override this and take special action once 
     * they have their map variable set. 
     *
     * Parameters:
     * map - {<OpenLayers.Map>} 
     */
    setMap: function(map) {
        this.map = map;
        if (this.handler) {
            this.handler.setMap(map);
        }
    },
  
    /**
     * Method: draw
     * The draw method is called when the control is ready to be displayed
     * on the page.  If a div has not been created one is created.  Controls
     * with a visual component will almost always want to override this method 
     * to customize the look of control. 
     *
     * Parameters:
     * px - {<OpenLayers.Pixel>} The top-left pixel position of the control
     *      or null.
     *
     * Returns:
     * {DOMElement} A reference to the DIV DOMElement containing the control
     */
    draw: function (px) {
        if (this.div == null) {
            this.div = OpenLayers.Util.createDiv(this.id);
            this.div.className = this.displayClass;
            if (!this.allowSelection) {
                this.div.className += " olControlNoSelect";
                this.div.setAttribute("unselectable", "on", 0);
                this.div.onselectstart = OpenLayers.Function.False; 
            }    
            if (this.title != "") {
                this.div.title = this.title;
            }
        }
        if (px != null) {
            this.position = px.clone();
        }
        this.moveTo(this.position);
        return this.div;
    },

    /**
     * Method: moveTo
     * Sets the left and top style attributes to the passed in pixel 
     * coordinates.
     *
     * Parameters:
     * px - {<OpenLayers.Pixel>}
     */
    moveTo: function (px) {
        if ((px != null) && (this.div != null)) {
            this.div.style.left = px.x + "px";
            this.div.style.top = px.y + "px";
        }
    },

    /**
     * Method: activate
     * Explicitly activates a control and it's associated
     * handler if one has been set.  Controls can be
     * deactivated by calling the deactivate() method.
     * 
     * Returns:
     * {Boolean}  True if the control was successfully activated or
     *            false if the control was already active.
     */
    activate: function () {
        if (this.active) {
            return false;
        }
        if (this.handler) {
            this.handler.activate();
        }
        this.active = true;
        if(this.map) {
            OpenLayers.Element.addClass(
                this.map.viewPortDiv,
                this.displayClass.replace(/ /g, "") + "Active"
            );
        }
        this.events.triggerEvent("activate");
        return true;
    },
    
    /**
     * Method: deactivate
     * Deactivates a control and it's associated handler if any.  The exact
     * effect of this depends on the control itself.
     * 
     * Returns:
     * {Boolean} True if the control was effectively deactivated or false
     *           if the control was already inactive.
     */
    deactivate: function () {
        if (this.active) {
            if (this.handler) {
                this.handler.deactivate();
            }
            this.active = false;
            if(this.map) {
                OpenLayers.Element.removeClass(
                    this.map.viewPortDiv,
                    this.displayClass.replace(/ /g, "") + "Active"
                );
            }
            this.events.triggerEvent("deactivate");
            return true;
        }
        return false;
    },

    CLASS_NAME: "OpenLayers.Control"
});

OpenLayers.Control.TYPE_BUTTON = 1;
OpenLayers.Control.TYPE_TOGGLE = 2;
OpenLayers.Control.TYPE_TOOL   = 3;
/* ======================================================================
    OpenLayers/Lang.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */

/**
 * @requires OpenLayers/Console.js
 */

/**
 * Namespace: OpenLayers.Lang
 * Internationalization namespace.  Contains dictionaries in various languages
 *     and methods to set and get the current language.
 */
OpenLayers.Lang = {
    
    /** 
     * Property: code
     * {String}  Current language code to use in OpenLayers.  Use the
     *     <setCode> method to set this value and the <getCode> method to
     *     retrieve it.
     */
    code: null,

    /** 
     * APIProperty: defaultCode
     * {String} Default language to use when a specific language can't be
     *     found.  Default is "en".
     */
    defaultCode: "en",
        
    /**
     * APIFunction: getCode
     * Get the current language code.
     *
     * Returns:
     * The current language code.
     */
    getCode: function() {
        if(!OpenLayers.Lang.code) {
            OpenLayers.Lang.setCode();
        }
        return OpenLayers.Lang.code;
    },
    
    /**
     * APIFunction: setCode
     * Set the language code for string translation.  This code is used by
     *     the <OpenLayers.Lang.translate> method.
     *
     * Parameters-
     * code - {String} These codes follow the IETF recommendations at
     *     http://www.ietf.org/rfc/rfc3066.txt.  If no value is set, the
     *     browser's language setting will be tested.  If no <OpenLayers.Lang>
     *     dictionary exists for the code, the <OpenLayers.String.defaultLang>
     *     will be used.
     */
    setCode: function(code) {
        var lang;
        if(!code) {
            code = (OpenLayers.Util.getBrowserName() == "msie") ?
                navigator.userLanguage : navigator.language;
        }
        var parts = code.split('-');
        parts[0] = parts[0].toLowerCase();
        if(typeof OpenLayers.Lang[parts[0]] == "object") {
            lang = parts[0];
        }

        // check for regional extensions
        if(parts[1]) {
            var testLang = parts[0] + '-' + parts[1].toUpperCase();
            if(typeof OpenLayers.Lang[testLang] == "object") {
                lang = testLang;
            }
        }
        if(!lang) {
            OpenLayers.Console.warn(
                'Failed to find OpenLayers.Lang.' + parts.join("-") +
                ' dictionary, falling back to default language'
            );
            lang = OpenLayers.Lang.defaultCode;
        }
        
        OpenLayers.Lang.code = lang;
    },

    /**
     * APIMethod: translate
     * Looks up a key from a dictionary based on the current language string.
     *     The value of <getCode> will be used to determine the appropriate
     *     dictionary.  Dictionaries are stored in <OpenLayers.Lang>.
     *
     * Parameters:
     * key - {String} The key for an i18n string value in the dictionary.
     * context - {Object} Optional context to be used with
     *     <OpenLayers.String.format>.
     * 
     * Returns:
     * {String} A internationalized string.
     */
    translate: function(key, context) {
        var dictionary = OpenLayers.Lang[OpenLayers.Lang.getCode()];
        var message = dictionary[key];
        if(!message) {
            // Message not found, fall back to message key
            message = key;
        }
        if(context) {
            message = OpenLayers.String.format(message, context);
        }
        return message;
    }
    
};


/**
 * APIMethod: OpenLayers.i18n
 * Alias for <OpenLayers.Lang.translate>.  Looks up a key from a dictionary
 *     based on the current language string. The value of
 *     <OpenLayers.Lang.getCode> will be used to determine the appropriate
 *     dictionary.  Dictionaries are stored in <OpenLayers.Lang>.
 *
 * Parameters:
 * key - {String} The key for an i18n string value in the dictionary.
 * context - {Object} Optional context to be used with
 *     <OpenLayers.String.format>.
 * 
 * Returns:
 * {String} A internationalized string.
 */
OpenLayers.i18n = OpenLayers.Lang.translate;
/* ======================================================================
    OpenLayers/Popup/Anchored.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */


/**
 * @requires OpenLayers/Popup.js
 */

/**
 * Class: OpenLayers.Popup.Anchored
 * 
 * Inherits from:
 *  - <OpenLayers.Popup>
 */
OpenLayers.Popup.Anchored = 
  OpenLayers.Class(OpenLayers.Popup, {

    /** 
     * Parameter: relativePosition
     * {String} Relative position of the popup ("br", "tr", "tl" or "bl").
     */
    relativePosition: null,
    
    /**
     * APIProperty: keepInMap 
     * {Boolean} If panMapIfOutOfView is false, and this property is true, 
     *     contrain the popup such that it always fits in the available map
     *     space. By default, this is set. If you are creating popups that are
     *     near map edges and not allowing pannning, and especially if you have
     *     a popup which has a fixedRelativePosition, setting this to false may
     *     be a smart thing to do.
     *   
     *     For anchored popups, default is true, since subclasses will
     *     usually want this functionality.
     */
    keepInMap: true,

    /**
     * Parameter: anchor
     * {Object} Object to which we'll anchor the popup. Must expose a 
     *     'size' (<OpenLayers.Size>) and 'offset' (<OpenLayers.Pixel>).
     */
    anchor: null,

    /** 
    * Constructor: OpenLayers.Popup.Anchored
    * 
    * Parameters:
    * id - {String}
    * lonlat - {<OpenLayers.LonLat>}
    * contentSize - {<OpenLayers.Size>}
    * contentHTML - {String}
    * anchor - {Object} Object which must expose a 'size' <OpenLayers.Size> 
    *     and 'offset' <OpenLayers.Pixel> (generally an <OpenLayers.Icon>).
    * closeBox - {Boolean}
    * closeBoxCallback - {Function} Function to be called on closeBox click.
    */
    initialize:function(id, lonlat, contentSize, contentHTML, anchor, closeBox,
                        closeBoxCallback) {
        var newArguments = [
            id, lonlat, contentSize, contentHTML, closeBox, closeBoxCallback
        ];
        OpenLayers.Popup.prototype.initialize.apply(this, newArguments);

        this.anchor = (anchor != null) ? anchor 
                                       : { size: new OpenLayers.Size(0,0),
                                           offset: new OpenLayers.Pixel(0,0)};
    },

    /**
     * APIMethod: destroy
     */
    destroy: function() {
        this.anchor = null;
        this.relativePosition = null;
        
        OpenLayers.Popup.prototype.destroy.apply(this, arguments);        
    },

    /**
     * APIMethod: show
     * Overridden from Popup since user might hide popup and then show() it 
     *     in a new location (meaning we might want to update the relative
     *     position on the show)
     */
    show: function() {
        this.updatePosition();
        OpenLayers.Popup.prototype.show.apply(this, arguments);
    },

    /**
     * Method: moveTo
     * Since the popup is moving to a new px, it might need also to be moved
     *     relative to where the marker is. We first calculate the new 
     *     relativePosition, and then we calculate the new px where we will 
     *     put the popup, based on the new relative position. 
     * 
     *     If the relativePosition has changed, we must also call 
     *     updateRelativePosition() to make any visual changes to the popup 
     *     which are associated with putting it in a new relativePosition.
     * 
     * Parameters:
     * px - {<OpenLayers.Pixel>}
     */
    moveTo: function(px) {
        var oldRelativePosition = this.relativePosition;
        this.relativePosition = this.calculateRelativePosition(px);
        
        var newPx = this.calculateNewPx(px);
        
        var newArguments = new Array(newPx);        
        OpenLayers.Popup.prototype.moveTo.apply(this, newArguments);
        
        //if this move has caused the popup to change its relative position, 
        // we need to make the appropriate cosmetic changes.
        if (this.relativePosition != oldRelativePosition) {
            this.updateRelativePosition();
        }
    },

    /**
     * APIMethod: setSize
     * 
     * Parameters:
     * contentSize - {<OpenLayers.Size>} the new size for the popup's 
     *     contents div (in pixels).
     */
    setSize:function(contentSize) { 
        OpenLayers.Popup.prototype.setSize.apply(this, arguments);

        if ((this.lonlat) && (this.map)) {
            var px = this.map.getLayerPxFromLonLat(this.lonlat);
            this.moveTo(px);
        }
    },  
    
    /** 
     * Method: calculateRelativePosition
     * 
     * Parameters:
     * px - {<OpenLayers.Pixel>}
     * 
     * Returns:
     * {String} The relative position ("br" "tr" "tl" "bl") at which the popup
     *     should be placed.
     */
    calculateRelativePosition:function(px) {
        var lonlat = this.map.getLonLatFromLayerPx(px);        
        
        var extent = this.map.getExtent();
        var quadrant = extent.determineQuadrant(lonlat);
        
        return OpenLayers.Bounds.oppositeQuadrant(quadrant);
    }, 

    /**
     * Method: updateRelativePosition
     * The popup has been moved to a new relative location, so we may want to 
     *     make some cosmetic adjustments to it. 
     * 
     *     Note that in the classic Anchored popup, there is nothing to do 
     *     here, since the popup looks exactly the same in all four positions.
     *     Subclasses such as the AnchoredBubble and Framed, however, will 
     *     want to do something special here.
     */
    updateRelativePosition: function() {
        //to be overridden by subclasses
    },

    /** 
     * Method: calculateNewPx
     * 
     * Parameters:
     * px - {<OpenLayers.Pixel>}
     * 
     * Returns:
     * {<OpenLayers.Pixel>} The the new px position of the popup on the screen
     *     relative to the passed-in px.
     */
    calculateNewPx:function(px) {
        var newPx = px.offset(this.anchor.offset);
        
        //use contentSize if size is not already set
        var size = this.size || this.contentSize;

        var top = (this.relativePosition.charAt(0) == 't');
        newPx.y += (top) ? -size.h : this.anchor.size.h;
        
        var left = (this.relativePosition.charAt(1) == 'l');
        newPx.x += (left) ? -size.w : this.anchor.size.w;

        return newPx;   
    },

    CLASS_NAME: "OpenLayers.Popup.Anchored"
});
/* ======================================================================
    OpenLayers/Renderer/Canvas.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */

/**
 * @requires OpenLayers/Renderer.js
 */

/**
 * Class: OpenLayers.Renderer.Canvas 
 * A renderer based on the 2D 'canvas' drawing element.element
 * 
 * Inherits:
 *  - <OpenLayers.Renderer>
 */
OpenLayers.Renderer.Canvas = OpenLayers.Class(OpenLayers.Renderer, {

    /**
     * Property: canvas
     * {Canvas} The canvas context object.
     */
    canvas: null, 
    
    /**
     * Property: features
     * {Object} Internal object of feature/style pairs for use in redrawing the layer.
     */
    features: null, 
   
    /**
     * Property: geometryMap
     * {Object} Geometry -> Feature lookup table. Used by eraseGeometry to
     *     lookup features to remove from our internal table (this.features)
     *     when erasing geoms.
     */
    geometryMap: null,
 
    /**
     * Constructor: OpenLayers.Renderer.Canvas
     *
     * Parameters:
     * containerID - {<String>} 
     */
    initialize: function(containerID) {
        OpenLayers.Renderer.prototype.initialize.apply(this, arguments);
        this.root = document.createElement("canvas");
        this.container.appendChild(this.root);
        this.canvas = this.root.getContext("2d");
        this.features = {};
        this.geometryMap = {};
    },
    
    /** 
     * Method: eraseGeometry
     * Erase a geometry from the renderer. Because the Canvas renderer has
     *     'memory' of the features that it has drawn, we have to remove the
     *     feature so it doesn't redraw.   
     * 
     * Parameters:
     * geometry - {<OpenLayers.Geometry>}
     */
    eraseGeometry: function(geometry) {
        this.eraseFeatures(this.features[this.geometryMap[geometry.id]][0]);
    },

    /**
     * APIMethod: supported
     * 
     * Returns:
     * {Boolean} Whether or not the browser supports the renderer class
     */
    supported: function() {
        var canvas = document.createElement("canvas");
        return !!canvas.getContext;
    },    
    
    /**
     * Method: setExtent
     * Set the visible part of the layer.
     *
     * Resolution has probably changed, so we nullify the resolution 
     * cache (this.resolution), then redraw. 
     *
     * Parameters:
     * extent - {<OpenLayers.Bounds>} 
     */
    setExtent: function(extent) {
        this.extent = extent.clone();
        this.resolution = null;
        this.redraw();
    },
    
    /**
     * Method: setSize
     * Sets the size of the drawing surface.
     *
     * Once the size is updated, redraw the canvas.
     *
     * Parameters:
     * size - {<OpenLayers.Size>} 
     */
    setSize: function(size) {
        this.size = size.clone();
        this.root.style.width = size.w + "px";
        this.root.style.height = size.h + "px";
        this.root.width = size.w;
        this.root.height = size.h;
        this.resolution = null;
    },
    
    /**
     * Method: drawFeature
     * Draw the feature. Stores the feature in the features list,
     * then redraws the layer. 
     *
     * Parameters:
     * feature - {<OpenLayers.Feature.Vector>} 
     * style - {<Object>} 
     */
    drawFeature: function(feature, style) {
        if(style == null) {
            style = feature.style;
        }
        style = OpenLayers.Util.extend({
          'fillColor': '#000000',
          'strokeColor': '#000000',
          'strokeWidth': 2,
          'fillOpacity': 1,
          'strokeOpacity': 1
        }, style);  
        this.features[feature.id] = [feature, style]; 
        if (feature.geometry) { 
            this.geometryMap[feature.geometry.id] = feature.id;
        }    
        this.redraw();
    },


    /** 
     * Method: drawGeometry
     * Used when looping (in redraw) over the features; draws
     * the canvas. 
     *
     * Parameters:
     * geometry - {<OpenLayers.Geometry>} 
     * style - {Object} 
     */
    drawGeometry: function(geometry, style) {
        var className = geometry.CLASS_NAME;
        if ((className == "OpenLayers.Geometry.Collection") ||
            (className == "OpenLayers.Geometry.MultiPoint") ||
            (className == "OpenLayers.Geometry.MultiLineString") ||
            (className == "OpenLayers.Geometry.MultiPolygon")) {
            for (var i = 0; i < geometry.components.length; i++) {
                this.drawGeometry(geometry.components[i], style);
            }
            return;
        };
        switch (geometry.CLASS_NAME) {
            case "OpenLayers.Geometry.Point":
                this.drawPoint(geometry, style);
                break;
            case "OpenLayers.Geometry.LineString":
                this.drawLineString(geometry, style);
                break;
            case "OpenLayers.Geometry.LinearRing":
                this.drawLinearRing(geometry, style);
                break;
            case "OpenLayers.Geometry.Polygon":
                this.drawPolygon(geometry, style);
                break;
            default:
                break;
        }
    },

    /**
     * Method: drawExternalGraphic
     * Called to draw External graphics. 
     * 
     * Parameters: 
     * geometry - {<OpenLayers.Geometry>}
     * style    - {Object}
     */ 
    drawExternalGraphic: function(pt, style) {
       var img = new Image();
       img.src = style.externalGraphic;
       
       if(style.graphicTitle) {
           img.title=style.graphicTitle;           
       }

       var width = style.graphicWidth || style.graphicHeight;
       var height = style.graphicHeight || style.graphicWidth;
       width = width ? width : style.pointRadius*2;
       height = height ? height : style.pointRadius*2;
       var xOffset = (style.graphicXOffset != undefined) ?
           style.graphicXOffset : -(0.5 * width);
       var yOffset = (style.graphicYOffset != undefined) ?
           style.graphicYOffset : -(0.5 * height);
       var opacity = style.graphicOpacity || style.fillOpacity;
       
       var context = { img: img, 
                       x: (pt[0]+xOffset), 
                       y: (pt[1]+yOffset), 
                       width: width, 
                       height: height, 
                       canvas: this.canvas };

       img.onload = OpenLayers.Function.bind( function() {
           this.canvas.drawImage(this.img, this.x, 
                                 this.y, this.width, this.height);
       }, context);   
    },

    /**
     * Method: setCanvasStyle
     * Prepare the canvas for drawing by setting various global settings.
     *
     * Parameters:
     * type - {String} one of 'stroke', 'fill', or 'reset'
     * style - {Object} Symbolizer hash
     */
    setCanvasStyle: function(type, style) {
        if (type == "fill") {     
            this.canvas.globalAlpha = style['fillOpacity'];
            this.canvas.fillStyle = style['fillColor'];
        } else if (type == "stroke") {  
            this.canvas.globalAlpha = style['strokeOpacity'];
            this.canvas.strokeStyle = style['strokeColor'];
            this.canvas.lineWidth = style['strokeWidth'];
        } else {
            this.canvas.globalAlpha = 0;
            this.canvas.lineWidth = 1;
        }
    },

    /**
     * Method: drawPoint
     * This method is only called by the renderer itself.
     * 
     * Parameters: 
     * geometry - {<OpenLayers.Geometry>}
     * style    - {Object}
     */ 
    drawPoint: function(geometry, style) {
        if(style.graphic !== false) {
            var pt = this.getLocalXY(geometry);
            
            if (style.externalGraphic) {
                this.drawExternalGraphic(pt, style);
            } else {
                if(style.fill !== false) {
                    this.setCanvasStyle("fill", style);
                    this.canvas.beginPath();
                    this.canvas.arc(pt[0], pt[1], style.pointRadius, 0, Math.PI*2, true);
                    this.canvas.fill();
                }
                
                if(style.stroke !== false) {
                    this.setCanvasStyle("stroke", style);
                    this.canvas.beginPath();
                    this.canvas.arc(pt[0], pt[1], style.pointRadius, 0, Math.PI*2, true);
                    this.canvas.stroke();
                    this.setCanvasStyle("reset");
                }
            }
        }
    },

    /**
     * Method: drawLineString
     * This method is only called by the renderer itself.
     * 
     * Parameters: 
     * geometry - {<OpenLayers.Geometry>}
     * style    - {Object}
     */ 
    drawLineString: function(geometry, style) {
        if(style.stroke !== false) {
            this.setCanvasStyle("stroke", style);
            this.canvas.beginPath();
            var start = this.getLocalXY(geometry.components[0]);
            this.canvas.moveTo(start[0], start[1]);
            for(var i = 1; i < geometry.components.length; i++) {
                var pt = this.getLocalXY(geometry.components[i]);
                this.canvas.lineTo(pt[0], pt[1]);
            }
            this.canvas.stroke();
        }
        this.setCanvasStyle("reset");
    },    
    
    /**
     * Method: drawLinearRing
     * This method is only called by the renderer itself.
     * 
     * Parameters: 
     * geometry - {<OpenLayers.Geometry>}
     * style    - {Object}
     */ 
    drawLinearRing: function(geometry, style) {
        if(style.fill !== false) {
            this.setCanvasStyle("fill", style);
            this.canvas.beginPath();
            var start = this.getLocalXY(geometry.components[0]);
            this.canvas.moveTo(start[0], start[1]);
            for(var i = 1; i < geometry.components.length - 1 ; i++) {
                var pt = this.getLocalXY(geometry.components[i]);
                this.canvas.lineTo(pt[0], pt[1]);
            }
            this.canvas.fill();
        }
        
        if(style.stroke !== false) {
            var oldWidth = this.canvas.lineWidth; 
            this.setCanvasStyle("stroke", style);
            this.canvas.beginPath();
            var start = this.getLocalXY(geometry.components[0]);
            this.canvas.moveTo(start[0], start[1]);
            for(var i = 1; i < geometry.components.length; i++) {
                var pt = this.getLocalXY(geometry.components[i]);
                this.canvas.lineTo(pt[0], pt[1]);
            }
            this.canvas.stroke();
        }
        this.setCanvasStyle("reset");
    },    
    
    /**
     * Method: drawPolygon
     * This method is only called by the renderer itself.
     * 
     * Parameters: 
     * geometry - {<OpenLayers.Geometry>}
     * style    - {Object}
     */ 
    drawPolygon: function(geometry, style) {
        this.drawLinearRing(geometry.components[0], style);
        for (var i = 1; i < geometry.components.length; i++) {
            this.drawLinearRing(geometry.components[i], {
                fillOpacity: 0, 
                strokeWidth: 0, 
                strokeOpacity: 0, 
                strokeColor: '#000000', 
                fillColor: '#000000'}
            ); // inner rings are 'empty'  
        }
    },
    
    /**
     * Method: drawText
     * This method is only called by the renderer itself.
     *
     * Parameters:
     * location - {<OpenLayers.Point>}
     * style    - {Object}
     */
    drawText: function(location, style) {
        style = OpenLayers.Util.extend({
            fontColor: "#000000",
            labelAlign: "cm"
        }, style);
        var pt = this.getLocalXY(location);
        
        this.setCanvasStyle("reset");
        this.canvas.fillStyle = style.fontColor;
        this.canvas.globalAlpha = style.fontOpacity || 1.0;
        var fontStyle = style.fontWeight + " " + style.fontSize + " " + style.fontFamily;
        if (this.canvas.fillText) {
            // HTML5
            var labelAlign =
                OpenLayers.Renderer.Canvas.LABEL_ALIGN[style.labelAlign[0]] ||
                "center";
            this.canvas.font = fontStyle;
            this.canvas.textAlign = labelAlign;
            this.canvas.fillText(style.label, pt[0], pt[1]);
        } else if (this.canvas.mozDrawText) {
            // Mozilla pre-Gecko1.9.1 (<FF3.1)
            this.canvas.mozTextStyle = fontStyle;
            // No built-in text alignment, so we measure and adjust the position
            var len = this.canvas.mozMeasureText(style.label);
            switch(style.labelAlign[0]) {
                case "l":
                    break;
                case "r":
                    pt[0] -= len;
                    break;
                case "c":
                default:
                    pt[0] -= len / 2;
            }
            this.canvas.translate(pt[0], pt[1]);
            
            this.canvas.mozDrawText(style.label);
            this.canvas.translate(-1*pt[0], -1*pt[1]);
        }
        this.setCanvasStyle("reset");
    },

    /**
     * Method: getLocalXY
     * transform geographic xy into pixel xy
     *
     * Parameters: 
     * point - {<OpenLayers.Geometry.Point>}
     */
    getLocalXY: function(point) {
        var resolution = this.getResolution();
        var extent = this.extent;
        var x = (point.x / resolution + (-extent.left / resolution));
        var y = ((extent.top / resolution) - point.y / resolution);
        return [x, y];
    },
        
    /**
     * Method: clear
     * Clear all vectors from the renderer.
     * virtual function.
     */    
    clear: function() {
        this.canvas.clearRect(0, 0, this.root.width, this.root.height);
    },

    /**
     * Method: getFeatureIdFromEvent
     * Returns a feature id from an event on the renderer.  
     * 
     * Parameters:
     * evt - {<OpenLayers.Event>} 
     *
     * Returns:
     * {String} A feature id or null.
     */
    getFeatureIdFromEvent: function(evt) {
        var loc = this.map.getLonLatFromPixel(evt.xy);
        var resolution = this.getResolution();
        var bounds = new OpenLayers.Bounds(loc.lon - resolution * 5, 
                                           loc.lat - resolution * 5, 
                                           loc.lon + resolution * 5, 
                                           loc.lat + resolution * 5);
        var geom = bounds.toGeometry();
        for (var feat in this.features) {
            if (!this.features.hasOwnProperty(feat)) { continue; }
            if (this.features[feat][0].geometry.intersects(geom)) {
                return feat;
            }
        }   
        return null;
    },
    
    /**
     * Method: eraseFeatures 
     * This is called by the layer to erase features; removes the feature from
     *     the list, then redraws the layer.
     * 
     * Parameters:
     * features - {Array(<OpenLayers.Feature.Vector>)} 
     */
    eraseFeatures: function(features) {
        if(!(features instanceof Array)) {
            features = [features];
        }
        for(var i=0; i<features.length; ++i) {
            delete this.features[features[i].id];
        }
        this.redraw();
    },

    /**
     * Method: redraw
     * The real 'meat' of the function: any time things have changed,
     *     redraw() can be called to loop over all the data and (you guessed
     *     it) redraw it.  Unlike Elements-based Renderers, we can't interact
     *     with things once they're drawn, to remove them, for example, so
     *     instead we have to just clear everything and draw from scratch.
     */
    redraw: function() {
        if (!this.locked) {
            this.clear();
            var labelMap = [];
            var feature, style;
            for (var id in this.features) {
                if (!this.features.hasOwnProperty(id)) { continue; }
                feature = this.features[id][0];
                style = this.features[id][1];
                if (!feature.geometry) { continue; }
                this.drawGeometry(feature.geometry, style);
                if(style.label) {
                    labelMap.push([feature, style]);
                }
            }
            var item;
            for (var i=0; len=labelMap.length, i<len; ++i) {
                item = labelMap[i];
                this.drawText(item[0].geometry.getCentroid(), item[1]);
            }
        }    
    },

    CLASS_NAME: "OpenLayers.Renderer.Canvas"
});

/**
 * Constant: OpenLayers.Renderer.Canvas.LABEL_ALIGN
 * {Object}
 */
OpenLayers.Renderer.Canvas.LABEL_ALIGN = {
    "l": "left",
    "r": "right"
};
/* ======================================================================
    OpenLayers/Renderer/Elements.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */

/**
 * @requires OpenLayers/Renderer.js
 */

/**
 * Class: OpenLayers.ElementsIndexer
 * This class takes care of figuring out which order elements should be
 *     placed in the DOM based on given indexing methods. 
 */
OpenLayers.ElementsIndexer = OpenLayers.Class({
   
    /**
     * Property: maxZIndex
     * {Integer} This is the largest-most z-index value for a node
     *     contained within the indexer.
     */
    maxZIndex: null,
    
    /**
     * Property: order
     * {Array<String>} This is an array of node id's stored in the
     *     order that they should show up on screen. Id's higher up in the
     *     array (higher array index) represent nodes with higher z-indeces.
     */
    order: null, 
    
    /**
     * Property: indices
     * {Object} This is a hash that maps node ids to their z-index value
     *     stored in the indexer. This is done to make finding a nodes z-index 
     *     value O(1).
     */
    indices: null,
    
    /**
     * Property: compare
     * {Function} This is the function used to determine placement of
     *     of a new node within the indexer. If null, this defaults to to
     *     the Z_ORDER_DRAWING_ORDER comparison method.
     */
    compare: null,
    
    /**
     * APIMethod: initialize
     * Create a new indexer with 
     * 
     * Parameters:
     * yOrdering - {Boolean} Whether to use y-ordering.
     */
    initialize: function(yOrdering) {

        this.compare = yOrdering ? 
            OpenLayers.ElementsIndexer.IndexingMethods.Z_ORDER_Y_ORDER :
            OpenLayers.ElementsIndexer.IndexingMethods.Z_ORDER_DRAWING_ORDER;
            
        this.order = [];
        this.indices = {};
        this.maxZIndex = 0;
    },
    
    /**
     * APIMethod: insert
     * Insert a new node into the indexer. In order to find the correct 
     *     positioning for the node to be inserted, this method uses a binary 
     *     search. This makes inserting O(log(n)). 
     * 
     * Parameters:
     * newNode - {DOMElement} The new node to be inserted.
     * 
     * Returns
     * {DOMElement} the node before which we should insert our newNode, or
     *     null if newNode can just be appended.
     */
    insert: function(newNode) {
        // If the node is known to the indexer, remove it so we can
        // recalculate where it should go.
        if (this.exists(newNode)) {
            this.remove(newNode);
        }
        
        var nodeId = newNode.id;
        
        this.determineZIndex(newNode);       

        var leftIndex = -1;
        var rightIndex = this.order.length;
        var middle;

        while (rightIndex - leftIndex > 1) {
            middle = parseInt((leftIndex + rightIndex) / 2);
            
            var placement = this.compare(this, newNode,
                OpenLayers.Util.getElement(this.order[middle]));
            
            if (placement > 0) {
                leftIndex = middle;
            } else {
                rightIndex = middle;
            } 
        }
        
        this.order.splice(rightIndex, 0, nodeId);
        this.indices[nodeId] = this.getZIndex(newNode);
        
        // If the new node should be before another in the index
        // order, return the node before which we have to insert the new one;
        // else, return null to indicate that the new node can be appended.
        return this.getNextElement(rightIndex);
    },
    
    /**
     * APIMethod: remove
     * 
     * Parameters:
     * node - {DOMElement} The node to be removed.
     */
    remove: function(node) {
        var nodeId = node.id;
        var arrayIndex = OpenLayers.Util.indexOf(this.order, nodeId);
        if (arrayIndex >= 0) {
            // Remove it from the order array, as well as deleting the node
            // from the indeces hash.
            this.order.splice(arrayIndex, 1);
            delete this.indices[nodeId];
            
            // Reset the maxium z-index based on the last item in the 
            // order array.
            if (this.order.length > 0) {
                var lastId = this.order[this.order.length - 1];
                this.maxZIndex = this.indices[lastId];
            } else {
                this.maxZIndex = 0;
            }
        }
    },
    
    /**
     * APIMethod: clear
     */
    clear: function() {
        this.order = [];
        this.indices = {};
        this.maxZIndex = 0;
    },
    
    /**
     * APIMethod: exists
     *
     * Parameters:
     * node- {DOMElement} The node to test for existence.
     *
     * Returns:
     * {Boolean} Whether or not the node exists in the indexer?
     */
    exists: function(node) {
        return (this.indices[node.id] != null);
    },

    /**
     * APIMethod: getZIndex
     * Get the z-index value for the current node from the node data itself.
     * 
     * Parameters:
     * node - {DOMElement} The node whose z-index to get.
     * 
     * Returns:
     * {Integer} The z-index value for the specified node (from the node 
     *     data itself).
     */
    getZIndex: function(node) {
        return node._style.graphicZIndex;  
    },
    
    /**
     * Method: determineZIndex
     * Determine the z-index for the current node if there isn't one, 
     *     and set the maximum value if we've found a new maximum.
     * 
     * Parameters:
     * node - {DOMElement} 
     */
    determineZIndex: function(node) {
        var zIndex = node._style.graphicZIndex;
        
        // Everything must have a zIndex. If none is specified,
        // this means the user *must* (hint: assumption) want this
        // node to succomb to drawing order. To enforce drawing order
        // over all indexing methods, we'll create a new z-index that's
        // greater than any currently in the indexer.
        if (zIndex == null) {
            zIndex = this.maxZIndex;
            node._style.graphicZIndex = zIndex; 
        } else if (zIndex > this.maxZIndex) {
            this.maxZIndex = zIndex;
        }
    },

    /**
     * APIMethod: getNextElement
     * Get the next element in the order stack.
     * 
     * Parameters:
     * index - {Integer} The index of the current node in this.order.
     * 
     * Returns:
     * {DOMElement} the node following the index passed in, or
     *     null.
     */
    getNextElement: function(index) {
        var nextIndex = index + 1;
        if (nextIndex < this.order.length) {
            var nextElement = OpenLayers.Util.getElement(this.order[nextIndex]);
            if (nextElement == undefined) {
                nextElement = this.getNextElement(nextIndex);
            }
            return nextElement;
        } else {
            return null;
        } 
    },
    
    CLASS_NAME: "OpenLayers.ElementsIndexer"
});

/**
 * Namespace: OpenLayers.ElementsIndexer.IndexingMethods
 * These are the compare methods for figuring out where a new node should be 
 *     placed within the indexer. These methods are very similar to general 
 *     sorting methods in that they return -1, 0, and 1 to specify the 
 *     direction in which new nodes fall in the ordering.
 */
OpenLayers.ElementsIndexer.IndexingMethods = {
    
    /**
     * Method: Z_ORDER
     * This compare method is used by other comparison methods.
     *     It can be used individually for ordering, but is not recommended,
     *     because it doesn't subscribe to drawing order.
     * 
     * Parameters:
     * indexer - {<OpenLayers.ElementsIndexer>}
     * newNode - {DOMElement}
     * nextNode - {DOMElement}
     * 
     * Returns:
     * {Integer}
     */
    Z_ORDER: function(indexer, newNode, nextNode) {
        var newZIndex = indexer.getZIndex(newNode);

        var returnVal = 0;
        if (nextNode) {
            var nextZIndex = indexer.getZIndex(nextNode);
            returnVal = newZIndex - nextZIndex; 
        }
        
        return returnVal;
    },

    /**
     * APIMethod: Z_ORDER_DRAWING_ORDER
     * This method orders nodes by their z-index, but does so in a way
     *     that, if there are other nodes with the same z-index, the newest 
     *     drawn will be the front most within that z-index. This is the 
     *     default indexing method.
     * 
     * Parameters:
     * indexer - {<OpenLayers.ElementsIndexer>}
     * newNode - {DOMElement}
     * nextNode - {DOMElement}
     * 
     * Returns:
     * {Integer}
     */
    Z_ORDER_DRAWING_ORDER: function(indexer, newNode, nextNode) {
        var returnVal = OpenLayers.ElementsIndexer.IndexingMethods.Z_ORDER(
            indexer, 
            newNode, 
            nextNode
        );
        
        // Make Z_ORDER subscribe to drawing order by pushing it above
        // all of the other nodes with the same z-index.
        if (nextNode && returnVal == 0) {
            returnVal = 1;
        }
        
        return returnVal;
    },

    /**
     * APIMethod: Z_ORDER_Y_ORDER
     * This one should really be called Z_ORDER_Y_ORDER_DRAWING_ORDER, as it
     *     best describes which ordering methods have precedence (though, the 
     *     name would be too long). This method orders nodes by their z-index, 
     *     but does so in a way that, if there are other nodes with the same 
     *     z-index, the nodes with the lower y position will be "closer" than 
     *     those with a higher y position. If two nodes have the exact same y 
     *     position, however, then this method will revert to using drawing  
     *     order to decide placement.
     * 
     * Parameters:
     * indexer - {<OpenLayers.ElementsIndexer>}
     * newNode - {DOMElement}
     * nextNode - {DOMElement}
     * 
     * Returns:
     * {Integer}
     */
    Z_ORDER_Y_ORDER: function(indexer, newNode, nextNode) {
        var returnVal = OpenLayers.ElementsIndexer.IndexingMethods.Z_ORDER(
            indexer, 
            newNode, 
            nextNode
        );
        
        if (nextNode && returnVal === 0) {            
            var result = nextNode._boundsBottom - newNode._boundsBottom;
            returnVal = (result === 0) ? 1 : result;
        }
        
        return returnVal;       
    }
};

/**
 * Class: OpenLayers.Renderer.Elements
 * This is another virtual class in that it should never be instantiated by 
 *  itself as a Renderer. It exists because there is *tons* of shared 
 *  functionality between different vector libraries which use nodes/elements
 *  as a base for rendering vectors. 
 * 
 * The highlevel bits of code that are implemented here are the adding and 
 *  removing of geometries, which is essentially the same for any 
 *  element-based renderer. The details of creating each node and drawing the
 *  paths are of course different, but the machinery is the same. 
 * 
 * Inherits:
 *  - <OpenLayers.Renderer>
 */
OpenLayers.Renderer.Elements = OpenLayers.Class(OpenLayers.Renderer, {

    /**
     * Property: rendererRoot
     * {DOMElement}
     */
    rendererRoot: null,
    
    /**
     * Property: root
     * {DOMElement}
     */
    root: null,
    
    /**
     * Property: vectorRoot
     * {DOMElement}
     */
    vectorRoot: null,

    /**
     * Property: textRoot
     * {DOMElement}
     */
    textRoot: null,

    /**
     * Property: xmlns
     * {String}
     */    
    xmlns: null,
    
    /**
     * Property: Indexer
     * {<OpenLayers.ElementIndexer>} An instance of OpenLayers.ElementsIndexer 
     *     created upon initialization if the zIndexing or yOrdering options
     *     passed to this renderer's constructor are set to true.
     */
    indexer: null, 
    
    /**
     * Constant: BACKGROUND_ID_SUFFIX
     * {String}
     */
    BACKGROUND_ID_SUFFIX: "_background",
    
    /**
     * Constant: BACKGROUND_ID_SUFFIX
     * {String}
     */
    LABEL_ID_SUFFIX: "_label",
    
    /**
     * Property: minimumSymbolizer
     * {Object}
     */
    minimumSymbolizer: {
        strokeLinecap: "round",
        strokeOpacity: 1,
        strokeDashstyle: "solid",
        fillOpacity: 1,
        pointRadius: 0
    },
    
    /**
     * Constructor: OpenLayers.Renderer.Elements
     * 
     * Parameters:
     * containerID - {String}
     * options - {Object} options for this renderer. Supported options are:
     *     * yOrdering - {Boolean} Whether to use y-ordering
     *     * zIndexing - {Boolean} Whether to use z-indexing. Will be ignored
     *         if yOrdering is set to true.
     */
    initialize: function(containerID, options) {
        OpenLayers.Renderer.prototype.initialize.apply(this, arguments);

        this.rendererRoot = this.createRenderRoot();
        this.root = this.createRoot("_root");
        this.vectorRoot = this.createRoot("_vroot");
        this.textRoot = this.createRoot("_troot");
        
        this.root.appendChild(this.vectorRoot);
        this.root.appendChild(this.textRoot);
        
        this.rendererRoot.appendChild(this.root);
        this.container.appendChild(this.rendererRoot);
        
        if(options && (options.zIndexing || options.yOrdering)) {
            this.indexer = new OpenLayers.ElementsIndexer(options.yOrdering);
        }
    },
    
    /**
     * Method: destroy
     */
    destroy: function() {

        this.clear(); 

        this.rendererRoot = null;
        this.root = null;
        this.xmlns = null;

        OpenLayers.Renderer.prototype.destroy.apply(this, arguments);
    },
    
    /**
     * Method: clear
     * Remove all the elements from the root
     */    
    clear: function() {
        if (this.vectorRoot) {
            while (this.vectorRoot.childNodes.length > 0) {
                this.vectorRoot.removeChild(this.vectorRoot.firstChild);
            }
        }
        if (this.textRoot) {
            while (this.textRoot.childNodes.length > 0) {
                this.textRoot.removeChild(this.textRoot.firstChild);
            }
        }
        if (this.indexer) {
            this.indexer.clear();
        }
    },

    /** 
     * Method: getNodeType
     * This function is in charge of asking the specific renderer which type
     *     of node to create for the given geometry and style. All geometries
     *     in an Elements-based renderer consist of one node and some
     *     attributes. We have the nodeFactory() function which creates a node
     *     for us, but it takes a 'type' as input, and that is precisely what
     *     this function tells us.  
     *  
     * Parameters:
     * geometry - {<OpenLayers.Geometry>}
     * style - {Object}
     * 
     * Returns:
     * {String} The corresponding node type for the specified geometry
     */
    getNodeType: function(geometry, style) { },

    /** 
     * Method: drawGeometry 
     * Draw the geometry, creating new nodes, setting paths, setting style,
     *     setting featureId on the node.  This method should only be called
     *     by the renderer itself.
     *
     * Parameters:
     * geometry - {<OpenLayers.Geometry>}
     * style - {Object}
     * featureId - {String}
     * 
     * Returns:
     * {Boolean} true if the geometry has been drawn completely; null if
     *     incomplete; false otherwise
     */
    drawGeometry: function(geometry, style, featureId) {
        var className = geometry.CLASS_NAME;
        var rendered = true;
        if ((className == "OpenLayers.Geometry.Collection") ||
            (className == "OpenLayers.Geometry.MultiPoint") ||
            (className == "OpenLayers.Geometry.MultiLineString") ||
            (className == "OpenLayers.Geometry.MultiPolygon")) {
            for (var i = 0, len=geometry.components.length; i<len; i++) {
                rendered = this.drawGeometry(
                    geometry.components[i], style, featureId) && rendered;
            }
            return rendered;
        };

        rendered = false;
        if (style.display != "none") {
            if (style.backgroundGraphic) {
                this.redrawBackgroundNode(geometry.id, geometry, style,
                    featureId);
            }
            rendered = this.redrawNode(geometry.id, geometry, style,
                featureId);
        }
        if (rendered == false) {
            var node = document.getElementById(geometry.id);
            if (node) {
                if (node._style.backgroundGraphic) {
                    node.parentNode.removeChild(document.getElementById(
                        geometry.id + this.BACKGROUND_ID_SUFFIX));
                }
                node.parentNode.removeChild(node);
            }
        }
        return rendered;
    },
    
    /**
     * Method: redrawNode
     * 
     * Parameters:
     * id - {String}
     * geometry - {<OpenLayers.Geometry>}
     * style - {Object}
     * featureId - {String}
     * 
     * Returns:
     * {Boolean} true if the complete geometry could be drawn, null if parts of
     *     the geometry could not be drawn, false otherwise
     */
    redrawNode: function(id, geometry, style, featureId) {
        // Get the node if it's already on the map.
        var node = this.nodeFactory(id, this.getNodeType(geometry, style));
        
        // Set the data for the node, then draw it.
        node._featureId = featureId;
        node._boundsBottom = geometry.getBounds().bottom;
        node._geometryClass = geometry.CLASS_NAME;
        node._style = style;

        var drawResult = this.drawGeometryNode(node, geometry, style);
        if(drawResult === false) {
            return false;
        }
         
        node = drawResult.node;
        
        // Insert the node into the indexer so it can show us where to
        // place it. Note that this operation is O(log(n)). If there's a
        // performance problem (when dragging, for instance) this is
        // likely where it would be.
        if (this.indexer) {
            var insert = this.indexer.insert(node);
            if (insert) {
                this.vectorRoot.insertBefore(node, insert);
            } else {
                this.vectorRoot.appendChild(node);
            }
        } else {
            // if there's no indexer, simply append the node to root,
            // but only if the node is a new one
            if (node.parentNode !== this.vectorRoot){ 
                this.vectorRoot.appendChild(node);
            }
        }
        
        this.postDraw(node);
        
        return drawResult.complete;
    },
    
    /**
     * Method: redrawBackgroundNode
     * Redraws the node using special 'background' style properties. Basically
     *     just calls redrawNode(), but instead of directly using the 
     *     'externalGraphic', 'graphicXOffset', 'graphicYOffset', and 
     *     'graphicZIndex' properties directly from the specified 'style' 
     *     parameter, we create a new style object and set those properties 
     *     from the corresponding 'background'-prefixed properties from 
     *     specified 'style' parameter.
     * 
     * Parameters:
     * id - {String}
     * geometry - {<OpenLayers.Geometry>}
     * style - {Object}
     * featureId - {String}
     * 
     * Returns:
     * {Boolean} true if the complete geometry could be drawn, null if parts of
     *     the geometry could not be drawn, false otherwise
     */
    redrawBackgroundNode: function(id, geometry, style, featureId) {
        var backgroundStyle = OpenLayers.Util.extend({}, style);
        
        // Set regular style attributes to apply to the background styles.
        backgroundStyle.externalGraphic = backgroundStyle.backgroundGraphic;
        backgroundStyle.graphicXOffset = backgroundStyle.backgroundXOffset;
        backgroundStyle.graphicYOffset = backgroundStyle.backgroundYOffset;
        backgroundStyle.graphicZIndex = backgroundStyle.backgroundGraphicZIndex;
        backgroundStyle.graphicWidth = backgroundStyle.backgroundWidth || backgroundStyle.graphicWidth;
        backgroundStyle.graphicHeight = backgroundStyle.backgroundHeight || backgroundStyle.graphicHeight;
        
        // Erase background styles.
        backgroundStyle.backgroundGraphic = null;
        backgroundStyle.backgroundXOffset = null;
        backgroundStyle.backgroundYOffset = null;
        backgroundStyle.backgroundGraphicZIndex = null;
        
        return this.redrawNode(
            id + this.BACKGROUND_ID_SUFFIX, 
            geometry, 
            backgroundStyle, 
            null
        );
    },

    /**
     * Method: drawGeometryNode
     * Given a node, draw a geometry on the specified layer.
     *     node and geometry are required arguments, style is optional.
     *     This method is only called by the render itself.
     *
     * Parameters:
     * node - {DOMElement}
     * geometry - {<OpenLayers.Geometry>}
     * style - {Object}
     * 
     * Returns:
     * {Object} a hash with properties "node" (the drawn node) and "complete"
     *     (null if parts of the geometry could not be drawn, false if nothing
     *     could be drawn)
     */
    drawGeometryNode: function(node, geometry, style) {
        style = style || node._style;
        OpenLayers.Util.applyDefaults(style, this.minimumSymbolizer);

        var options = {
            'isFilled': style.fill === undefined ?
                true :
                style.fill,
            'isStroked': style.stroke === undefined ?
                !!style.strokeWidth :
                style.stroke
        };
        var drawn;
        switch (geometry.CLASS_NAME) {
            case "OpenLayers.Geometry.Point":
                if(style.graphic === false) {
                    options.isFilled = false;
                    options.isStroked = false;
                }
                drawn = this.drawPoint(node, geometry);
                break;
            case "OpenLayers.Geometry.LineString":
                options.isFilled = false;
                drawn = this.drawLineString(node, geometry);
                break;
            case "OpenLayers.Geometry.LinearRing":
                drawn = this.drawLinearRing(node, geometry);
                break;
            case "OpenLayers.Geometry.Polygon":
                drawn = this.drawPolygon(node, geometry);
                break;
            case "OpenLayers.Geometry.Surface":
                drawn = this.drawSurface(node, geometry);
                break;
            case "OpenLayers.Geometry.Rectangle":
                drawn = this.drawRectangle(node, geometry);
                break;
            default:
                break;
        }

        node._options = options; 

        //set style
        //TBD simplify this
        if (drawn != false) {
            return {
                node: this.setStyle(node, style, options, geometry),
                complete: drawn
            };
        } else {
            return false;
        }
    },
    
    /**
     * Method: postDraw
     * Things that have do be done after the geometry node is appended
     *     to its parent node. To be overridden by subclasses.
     * 
     * Parameters:
     * node - {DOMElement}
     */
    postDraw: function(node) {},
    
    /**
     * Method: drawPoint
     * Virtual function for drawing Point Geometry. 
     *     Should be implemented by subclasses.
     *     This method is only called by the renderer itself.
     * 
     * Parameters: 
     * node - {DOMElement}
     * geometry - {<OpenLayers.Geometry>}
     * 
     * Returns:
     * {DOMElement} or false if the renderer could not draw the point
     */ 
    drawPoint: function(node, geometry) {},

    /**
     * Method: drawLineString
     * Virtual function for drawing LineString Geometry. 
     *     Should be implemented by subclasses.
     *     This method is only called by the renderer itself.
     * 
     * Parameters: 
     * node - {DOMElement}
     * geometry - {<OpenLayers.Geometry>}
     * 
     * Returns:
     * {DOMElement} or null if the renderer could not draw all components of
     *     the linestring, or false if nothing could be drawn
     */ 
    drawLineString: function(node, geometry) {},

    /**
     * Method: drawLinearRing
     * Virtual function for drawing LinearRing Geometry. 
     *     Should be implemented by subclasses.
     *     This method is only called by the renderer itself.
     * 
     * Parameters: 
     * node - {DOMElement}
     * geometry - {<OpenLayers.Geometry>}
     * 
     * Returns:
     * {DOMElement} or null if the renderer could not draw all components
     *     of the linear ring, or false if nothing could be drawn
     */ 
    drawLinearRing: function(node, geometry) {},

    /**
     * Method: drawPolygon
     * Virtual function for drawing Polygon Geometry. 
     *    Should be implemented by subclasses.
     *    This method is only called by the renderer itself.
     * 
     * Parameters: 
     * node - {DOMElement}
     * geometry - {<OpenLayers.Geometry>}
     * 
     * Returns:
     * {DOMElement} or null if the renderer could not draw all components
     *     of the polygon, or false if nothing could be drawn
     */ 
    drawPolygon: function(node, geometry) {},

    /**
     * Method: drawRectangle
     * Virtual function for drawing Rectangle Geometry. 
     *     Should be implemented by subclasses.
     *     This method is only called by the renderer itself.
     * 
     * Parameters: 
     * node - {DOMElement}
     * geometry - {<OpenLayers.Geometry>}
     * 
     * Returns:
     * {DOMElement} or false if the renderer could not draw the rectangle
     */ 
    drawRectangle: function(node, geometry) {},

    /**
     * Method: drawCircle
     * Virtual function for drawing Circle Geometry. 
     *     Should be implemented by subclasses.
     *     This method is only called by the renderer itself.
     * 
     * Parameters: 
     * node - {DOMElement}
     * geometry - {<OpenLayers.Geometry>}
     * 
     * Returns:
     * {DOMElement} or false if the renderer could not draw the circle
     */ 
    drawCircle: function(node, geometry) {},

    /**
     * Method: drawSurface
     * Virtual function for drawing Surface Geometry. 
     *     Should be implemented by subclasses.
     *     This method is only called by the renderer itself.
     * 
     * Parameters: 
     * node - {DOMElement}
     * geometry - {<OpenLayers.Geometry>}
     * 
     * Returns:
     * {DOMElement} or false if the renderer could not draw the surface
     */ 
    drawSurface: function(node, geometry) {},

    /**
     * Method: removeText
     * Removes a label
     * 
     * Parameters:
     * featureId - {String}
     */
    removeText: function(featureId) {
        var label = document.getElementById(featureId + this.LABEL_ID_SUFFIX);
        if (label) {
            this.textRoot.removeChild(label);
        }
    },

    /**
     * Method: getFeatureIdFromEvent
     * 
     * Parameters:
     * evt - {Object} An <OpenLayers.Event> object
     *
     * Returns:
     * {<OpenLayers.Geometry>} A geometry from an event that 
     *     happened on a layer.
     */
    getFeatureIdFromEvent: function(evt) {
        var target = evt.target;
        var useElement = target && target.correspondingUseElement;
        var node = useElement ? useElement : (target || evt.srcElement);
        var featureId = node._featureId;
        return featureId;
    },

    /** 
     * Method: eraseGeometry
     * Erase a geometry from the renderer. In the case of a multi-geometry, 
     *     we cycle through and recurse on ourselves. Otherwise, we look for a 
     *     node with the geometry.id, destroy its geometry, and remove it from
     *     the DOM.
     * 
     * Parameters:
     * geometry - {<OpenLayers.Geometry>}
     */
    eraseGeometry: function(geometry) {
        if ((geometry.CLASS_NAME == "OpenLayers.Geometry.MultiPoint") ||
            (geometry.CLASS_NAME == "OpenLayers.Geometry.MultiLineString") ||
            (geometry.CLASS_NAME == "OpenLayers.Geometry.MultiPolygon") ||
            (geometry.CLASS_NAME == "OpenLayers.Geometry.Collection")) {
            for (var i=0, len=geometry.components.length; i<len; i++) {
                this.eraseGeometry(geometry.components[i]);
            }
        } else {    
            var element = OpenLayers.Util.getElement(geometry.id);
            if (element && element.parentNode) {
                if (element.geometry) {
                    element.geometry.destroy();
                    element.geometry = null;
                }
                element.parentNode.removeChild(element);

                if (this.indexer) {
                    this.indexer.remove(element);
                }
                
                if (element._style.backgroundGraphic) {
                    var backgroundId = geometry.id + this.BACKGROUND_ID_SUFFIX;
                    var bElem = OpenLayers.Util.getElement(backgroundId);
                    if (bElem && bElem.parentNode) {
                        // No need to destroy the geometry since the element and the background
                        // node share the same geometry.
                        bElem.parentNode.removeChild(bElem);
                    }
                }
            }
        }
    },

    /** 
     * Method: nodeFactory
     * Create new node of the specified type, with the (optional) specified id.
     * 
     * If node already exists with same ID and a different type, we remove it
     *     and then call ourselves again to recreate it.
     * 
     * Parameters:
     * id - {String}
     * type - {String} type Kind of node to draw.
     * 
     * Returns:
     * {DOMElement} A new node of the given type and id.
     */
    nodeFactory: function(id, type) {
        var node = OpenLayers.Util.getElement(id);
        if (node) {
            if (!this.nodeTypeCompare(node, type)) {
                node.parentNode.removeChild(node);
                node = this.nodeFactory(id, type);
            }
        } else {
            node = this.createNode(type, id);
        }
        return node;
    },
    
    /** 
     * Method: nodeTypeCompare
     * 
     * Parameters:
     * node - {DOMElement}
     * type - {String} Kind of node
     * 
     * Returns:
     * {Boolean} Whether or not the specified node is of the specified type
     *     This function must be overridden by subclasses.
     */
    nodeTypeCompare: function(node, type) {},
    
    /** 
     * Method: createNode
     * 
     * Parameters:
     * type - {String} Kind of node to draw.
     * id - {String} Id for node.
     * 
     * Returns:
     * {DOMElement} A new node of the given type and id.
     *     This function must be overridden by subclasses.
     */
    createNode: function(type, id) {},

    /**
     * Method: moveRoot
     * moves this renderer's root to a different renderer.
     * 
     * Parameters:
     * renderer - {<OpenLayers.Renderer>} target renderer for the moved root
     */
    moveRoot: function(renderer) {
        var root = this.root;
        if(renderer.root.parentNode == this.rendererRoot) {
            root = renderer.root;
        }
        root.parentNode.removeChild(root);
        renderer.rendererRoot.appendChild(root);
    },
    
    /**
     * Method: getRenderLayerId
     * Gets the layer that this renderer's output appears on. If moveRoot was
     * used, this will be different from the id of the layer containing the
     * features rendered by this renderer.
     * 
     * Returns:
     * {String} the id of the output layer.
     */
    getRenderLayerId: function() {
        return this.root.parentNode.parentNode.id;
    },
    
    /**
     * Method: isComplexSymbol
     * Determines if a symbol cannot be rendered using drawCircle
     * 
     * Parameters:
     * graphicName - {String}
     * 
     * Returns
     * {Boolean} true if the symbol is complex, false if not
     */
    isComplexSymbol: function(graphicName) {
        return (graphicName != "circle") && !!graphicName;
    },

    CLASS_NAME: "OpenLayers.Renderer.Elements"
});


/**
 * Constant: OpenLayers.Renderer.symbol
 * Coordinate arrays for well known (named) symbols.
 */
OpenLayers.Renderer.symbol = {
    "star": [350,75, 379,161, 469,161, 397,215, 423,301, 350,250, 277,301,
            303,215, 231,161, 321,161, 350,75],
    "cross": [4,0, 6,0, 6,4, 10,4, 10,6, 6,6, 6,10, 4,10, 4,6, 0,6, 0,4, 4,4,
            4,0],
    "x": [0,0, 25,0, 50,35, 75,0, 100,0, 65,50, 100,100, 75,100, 50,65, 25,100, 0,100, 35,50, 0,0],
    "square": [0,0, 0,1, 1,1, 1,0, 0,0],
    "triangle": [0,10, 10,10, 5,0, 0,10]
};
/* ======================================================================
    OpenLayers/Tween.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */

/**
 * @requires OpenLayers/Console.js
 */

/**
 * Namespace: OpenLayers.Tween
 */
OpenLayers.Tween = OpenLayers.Class({
    
    /**
     * Constant: INTERVAL
     * {int} Interval in milliseconds between 2 steps
     */
    INTERVAL: 10,
    
    /**
     * APIProperty: easing
     * {<OpenLayers.Easing>(Function)} Easing equation used for the animation
     *     Defaultly set to OpenLayers.Easing.Expo.easeOut
     */
    easing: null,
    
    /**
     * APIProperty: begin
     * {Object} Values to start the animation with
     */
    begin: null,
    
    /**
     * APIProperty: finish
     * {Object} Values to finish the animation with
     */
    finish: null,
    
    /**
     * APIProperty: duration
     * {int} duration of the tween (number of steps)
     */
    duration: null,
    
    /**
     * APIProperty: callbacks
     * {Object} An object with start, eachStep and done properties whose values
     *     are functions to be call during the animation. They are passed the
     *     current computed value as argument.
     */
    callbacks: null,
    
    /**
     * Property: time
     * {int} Step counter
     */
    time: null,
    
    /**
     * Property: interval
     * {int} Interval id returned by window.setInterval
     */
    interval: null,
    
    /**
     * Property: playing
     * {Boolean} Tells if the easing is currently playing
     */
    playing: false,
    
    /** 
     * Constructor: OpenLayers.Tween
     * Creates a Tween.
     *
     * Parameters:
     * easing - {<OpenLayers.Easing>(Function)} easing function method to use
     */ 
    initialize: function(easing) {
        this.easing = (easing) ? easing : OpenLayers.Easing.Expo.easeOut;
    },
    
    /**
     * APIMethod: start
     * Plays the Tween, and calls the callback method on each step
     * 
     * Parameters:
     * begin - {Object} values to start the animation with
     * finish - {Object} values to finish the animation with
     * duration - {int} duration of the tween (number of steps)
     * options - {Object} hash of options (for example callbacks (start, eachStep, done))
     */
    start: function(begin, finish, duration, options) {
        this.playing = true;
        this.begin = begin;
        this.finish = finish;
        this.duration = duration;
        this.callbacks = options.callbacks;
        this.time = 0;
        if (this.interval) {
            window.clearInterval(this.interval);
            this.interval = null;
        }
        if (this.callbacks && this.callbacks.start) {
            this.callbacks.start.call(this, this.begin);
        }
        this.interval = window.setInterval(
            OpenLayers.Function.bind(this.play, this), this.INTERVAL);
    },
    
    /**
     * APIMethod: stop
     * Stops the Tween, and calls the done callback
     *     Doesn't do anything if animation is already finished
     */
    stop: function() {
        if (!this.playing) {
            return;
        }
        
        if (this.callbacks && this.callbacks.done) {
            this.callbacks.done.call(this, this.finish);
        }
        window.clearInterval(this.interval);
        this.interval = null;
        this.playing = false;
    },
    
    /**
     * Method: play
     * Calls the appropriate easing method
     */
    play: function() {
        var value = {};
        for (var i in this.begin) {
            var b = this.begin[i];
            var f = this.finish[i];
            if (b == null || f == null || isNaN(b) || isNaN(f)) {
                OpenLayers.Console.error('invalid value for Tween');
            }
            
            var c = f - b;
            value[i] = this.easing.apply(this, [this.time, b, c, this.duration]);
        }
        this.time++;
        
        if (this.callbacks && this.callbacks.eachStep) {
            this.callbacks.eachStep.call(this, value);
        }
        
        if (this.time > this.duration) {
            if (this.callbacks && this.callbacks.done) {
                this.callbacks.done.call(this, this.finish);
                this.playing = false;
            }
            window.clearInterval(this.interval);
            this.interval = null;
        }
    },
    
    /**
     * Create empty functions for all easing methods.
     */
    CLASS_NAME: "OpenLayers.Tween"
});

/**
 * Namespace: OpenLayers.Easing
 * 
 * Credits:
 *      Easing Equations by Robert Penner, <http://www.robertpenner.com/easing/>
 */
OpenLayers.Easing = {
    /**
     * Create empty functions for all easing methods.
     */
    CLASS_NAME: "OpenLayers.Easing"
};

/**
 * Namespace: OpenLayers.Easing.Linear
 */
OpenLayers.Easing.Linear = {
    
    /**
     * Function: easeIn
     * 
     * Parameters:
     * t - {Float} time
     * b - {Float} beginning position
     * c - {Float} total change
     * d - {Float} duration of the transition
     */
    easeIn: function(t, b, c, d) {
        return c*t/d + b;
    },
    
    /**
     * Function: easeOut
     * 
     * Parameters:
     * t - {Float} time
     * b - {Float} beginning position
     * c - {Float} total change
     * d - {Float} duration of the transition
     */
    easeOut: function(t, b, c, d) {
        return c*t/d + b;
    },
    
    /**
     * Function: easeInOut
     * 
     * Parameters:
     * t - {Float} time
     * b - {Float} beginning position
     * c - {Float} total change
     * d - {Float} duration of the transition
     */
    easeInOut: function(t, b, c, d) {
        return c*t/d + b;
    },

    CLASS_NAME: "OpenLayers.Easing.Linear"
};

/**
 * Namespace: OpenLayers.Easing.Expo
 */
OpenLayers.Easing.Expo = {
    
    /**
     * Function: easeIn
     * 
     * Parameters:
     * t - {Float} time
     * b - {Float} beginning position
     * c - {Float} total change
     * d - {Float} duration of the transition
     */
    easeIn: function(t, b, c, d) {
        return (t==0) ? b : c * Math.pow(2, 10 * (t/d - 1)) + b;
    },
    
    /**
     * Function: easeOut
     * 
     * Parameters:
     * t - {Float} time
     * b - {Float} beginning position
     * c - {Float} total change
     * d - {Float} duration of the transition
     */
    easeOut: function(t, b, c, d) {
        return (t==d) ? b+c : c * (-Math.pow(2, -10 * t/d) + 1) + b;
    },
    
    /**
     * Function: easeInOut
     * 
     * Parameters:
     * t - {Float} time
     * b - {Float} beginning position
     * c - {Float} total change
     * d - {Float} duration of the transition
     */
    easeInOut: function(t, b, c, d) {
        if (t==0) return b;
        if (t==d) return b+c;
        if ((t/=d/2) < 1) return c/2 * Math.pow(2, 10 * (t - 1)) + b;
        return c/2 * (-Math.pow(2, -10 * --t) + 2) + b;
    },

    CLASS_NAME: "OpenLayers.Easing.Expo"
};

/**
 * Namespace: OpenLayers.Easing.Quad
 */
OpenLayers.Easing.Quad = {
    
    /**
     * Function: easeIn
     * 
     * Parameters:
     * t - {Float} time
     * b - {Float} beginning position
     * c - {Float} total change
     * d - {Float} duration of the transition
     */
    easeIn: function(t, b, c, d) {
        return c*(t/=d)*t + b;
    },
    
    /**
     * Function: easeOut
     * 
     * Parameters:
     * t - {Float} time
     * b - {Float} beginning position
     * c - {Float} total change
     * d - {Float} duration of the transition
     */
    easeOut: function(t, b, c, d) {
        return -c *(t/=d)*(t-2) + b;
    },
    
    /**
     * Function: easeInOut
     * 
     * Parameters:
     * t - {Float} time
     * b - {Float} beginning position
     * c - {Float} total change
     * d - {Float} duration of the transition
     */
    easeInOut: function(t, b, c, d) {
        if ((t/=d/2) < 1) return c/2*t*t + b;
        return -c/2 * ((--t)*(t-2) - 1) + b;
    },

    CLASS_NAME: "OpenLayers.Easing.Quad"
};
/* ======================================================================
    OpenLayers/Control/Attribution.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */

/**
 * @requires OpenLayers/Control.js
 */

/**
 * Class: OpenLayers.Control.Attribution
 * The attribution control adds attribution from layers to the map display. 
 * It uses 'attribution' property of each layer.
 *
 * Inherits from:
 *  - <OpenLayers.Control>
 */
OpenLayers.Control.Attribution = 
  OpenLayers.Class(OpenLayers.Control, {
    
    /**
     * APIProperty: seperator
     * {String} String used to seperate layers.
     */
    separator: ", ",
    
    /**
     * Constructor: OpenLayers.Control.Attribution 
     * 
     * Parameters:
     * options - {Object} Options for control.
     */
    initialize: function(options) {
        OpenLayers.Control.prototype.initialize.apply(this, arguments);
    },

    /** 
     * Method: destroy
     * Destroy control.
     */
    destroy: function() {
        this.map.events.un({
            "removelayer": this.updateAttribution,
            "addlayer": this.updateAttribution,
            "changelayer": this.updateAttribution,
            "changebaselayer": this.updateAttribution,
            scope: this
        });
        
        OpenLayers.Control.prototype.destroy.apply(this, arguments);
    },    
    
    /**
     * Method: draw
     * Initialize control.
     * 
     * Returns: 
     * {DOMElement} A reference to the DIV DOMElement containing the control
     */    
    draw: function() {
        OpenLayers.Control.prototype.draw.apply(this, arguments);
        
        this.map.events.on({
            'changebaselayer': this.updateAttribution,
            'changelayer': this.updateAttribution,
            'addlayer': this.updateAttribution,
            'removelayer': this.updateAttribution,
            scope: this
        });
        this.updateAttribution();
        
        return this.div;    
    },

    /**
     * Method: updateAttribution
     * Update attribution string.
     */
    updateAttribution: function() {
        var attributions = [];
        if (this.map && this.map.layers) {
            for(var i=0, len=this.map.layers.length; i<len; i++) {
                var layer = this.map.layers[i];
                if (layer.attribution && layer.getVisibility()) {
                    // add attribution only if attribution text is unique
                    if (OpenLayers.Util.indexOf(
                                    attributions, layer.attribution) === -1) {
                        attributions.push( layer.attribution );
                    }
                }
            } 
            this.div.innerHTML = attributions.join(this.separator);
        }
    },

    CLASS_NAME: "OpenLayers.Control.Attribution"
});
/* ======================================================================
    OpenLayers/Control/LayerSwitcher.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */

/** 
 * @requires OpenLayers/Control.js
 */

/**
 * Class: OpenLayers.Control.LayerSwitcher
 * The LayerSwitcher control displays a table of contents for the map. This 
 * allows the user interface to switch between BaseLasyers and to show or hide
 * Overlays. By default the switcher is shown minimized on the right edge of 
 * the map, the user may expand it by clicking on the handle.
 *
 * To create the LayerSwitcher outside of the map, pass the Id of a html div 
 * as the first argument to the constructor.
 * 
 * Inherits from:
 *  - <OpenLayers.Control>
 */
OpenLayers.Control.LayerSwitcher = 
  OpenLayers.Class(OpenLayers.Control, {

    /**
     * APIProperty: roundedCorner
     * {Boolean} If true the Rico library is used for rounding the corners
     *     of the layer switcher div, defaults to true.
     */
    roundedCorner: true,

    /**  
     * APIProperty: roundedCornerColor
     * {String} The color of the rounded corners, only applies if roundedCorner
     *     is true, defaults to "darkblue".
     */
    roundedCornerColor: "darkblue",
    
    /**  
     * Property: layerStates 
     * {Array(Object)} Basically a copy of the "state" of the map's layers 
     *     the last time the control was drawn. We have this in order to avoid
     *     unnecessarily redrawing the control.
     */
    layerStates: null,
    

  // DOM Elements
  
    /**
     * Property: layersDiv
     * {DOMElement} 
     */
    layersDiv: null,
    
    /** 
     * Property: baseLayersDiv
     * {DOMElement}
     */
    baseLayersDiv: null,

    /** 
     * Property: baseLayers
     * {Array(<OpenLayers.Layer>)}
     */
    baseLayers: null,
    
    
    /** 
     * Property: dataLbl
     * {DOMElement} 
     */
    dataLbl: null,
    
    /** 
     * Property: dataLayersDiv
     * {DOMElement} 
     */
    dataLayersDiv: null,

    /** 
     * Property: dataLayers
     * {Array(<OpenLayers.Layer>)} 
     */
    dataLayers: null,


    /** 
     * Property: minimizeDiv
     * {DOMElement} 
     */
    minimizeDiv: null,

    /** 
     * Property: maximizeDiv
     * {DOMElement} 
     */
    maximizeDiv: null,
    
    /**
     * APIProperty: ascending
     * {Boolean} 
     */
    ascending: true,
 
    /**
     * Constructor: OpenLayers.Control.LayerSwitcher
     * 
     * Parameters:
     * options - {Object}
     */
    initialize: function(options) {
        OpenLayers.Control.prototype.initialize.apply(this, arguments);
        this.layerStates = [];
    },

    /**
     * APIMethod: destroy 
     */    
    destroy: function() {
        
        OpenLayers.Event.stopObservingElement(this.div);

        OpenLayers.Event.stopObservingElement(this.minimizeDiv);
        OpenLayers.Event.stopObservingElement(this.maximizeDiv);

        //clear out layers info and unregister their events 
        this.clearLayersArray("base");
        this.clearLayersArray("data");
        
        this.map.events.un({
            "addlayer": this.redraw,
            "changelayer": this.redraw,
            "removelayer": this.redraw,
            "changebaselayer": this.redraw,
            scope: this
        });
        
        OpenLayers.Control.prototype.destroy.apply(this, arguments);
    },

    /** 
     * Method: setMap
     *
     * Properties:
     * map - {<OpenLayers.Map>} 
     */
    setMap: function(map) {
        OpenLayers.Control.prototype.setMap.apply(this, arguments);

        this.map.events.on({
            "addlayer": this.redraw,
            "changelayer": this.redraw,
            "removelayer": this.redraw,
            "changebaselayer": this.redraw,
            scope: this
        });
    },

    /**
     * Method: draw
     *
     * Returns:
     * {DOMElement} A reference to the DIV DOMElement containing the 
     *     switcher tabs.
     */  
    draw: function() {
        OpenLayers.Control.prototype.draw.apply(this);

        // create layout divs
        this.loadContents();

        // set mode to minimize
        if(!this.outsideViewport) {
            this.minimizeControl();
        }

        // populate div with current info
        this.redraw();    

        return this.div;
    },

    /** 
     * Method: clearLayersArray
     * User specifies either "base" or "data". we then clear all the
     *     corresponding listeners, the div, and reinitialize a new array.
     * 
     * Parameters:
     * layersType - {String}  
     */
    clearLayersArray: function(layersType) {
        var layers = this[layersType + "Layers"];
        if (layers) {
            for(var i=0, len=layers.length; i<len ; i++) {
                var layer = layers[i];
                OpenLayers.Event.stopObservingElement(layer.inputElem);
                OpenLayers.Event.stopObservingElement(layer.labelSpan);
            }
        }
        this[layersType + "LayersDiv"].innerHTML = "";
        this[layersType + "Layers"] = [];
    },


    /**
     * Method: checkRedraw
     * Checks if the layer state has changed since the last redraw() call.
     * 
     * Returns:
     * {Boolean} The layer state changed since the last redraw() call. 
     */
    checkRedraw: function() {
        var redraw = false;
        if ( !this.layerStates.length ||
             (this.map.layers.length != this.layerStates.length) ) {
            redraw = true;
        } else {
            for (var i=0, len=this.layerStates.length; i<len; i++) {
                var layerState = this.layerStates[i];
                var layer = this.map.layers[i];
                if ( (layerState.name != layer.name) || 
                     (layerState.inRange != layer.inRange) || 
                     (layerState.id != layer.id) || 
                     (layerState.visibility != layer.visibility) ) {
                    redraw = true;
                    break;
                }    
            }
        }    
        return redraw;
    },
    
    /** 
     * Method: redraw
     * Goes through and takes the current state of the Map and rebuilds the
     *     control to display that state. Groups base layers into a 
     *     radio-button group and lists each data layer with a checkbox.
     *
     * Returns: 
     * {DOMElement} A reference to the DIV DOMElement containing the control
     */  
    redraw: function() {
        //if the state hasn't changed since last redraw, no need 
        // to do anything. Just return the existing div.
        if (!this.checkRedraw()) { 
            return this.div; 
        } 

        //clear out previous layers 
        this.clearLayersArray("base");
        this.clearLayersArray("data");
        
        var containsOverlays = false;
        var containsBaseLayers = false;
        
        // Save state -- for checking layer if the map state changed.
        // We save this before redrawing, because in the process of redrawing
        // we will trigger more visibility changes, and we want to not redraw
        // and enter an infinite loop.
        var len = this.map.layers.length;
        this.layerStates = new Array(len);
        for (var i=0; i <len; i++) {
            var layer = this.map.layers[i];
            this.layerStates[i] = {
                'name': layer.name, 
                'visibility': layer.visibility,
                'inRange': layer.inRange,
                'id': layer.id
            };
        }    

        var layers = this.map.layers.slice();
        if (!this.ascending) { layers.reverse(); }
        for(var i=0, len=layers.length; i<len; i++) {
            var layer = layers[i];
            var baseLayer = layer.isBaseLayer;

            if (layer.displayInLayerSwitcher) {

                if (baseLayer) {
                    containsBaseLayers = true;
                } else {
                    containsOverlays = true;
                }    

                // only check a baselayer if it is *the* baselayer, check data
                //  layers if they are visible
                var checked = (baseLayer) ? (layer == this.map.baseLayer)
                                          : layer.getVisibility();
    
                // create input element
                var inputElem = document.createElement("input");
                inputElem.id = this.id + "_input_" + layer.name;
                inputElem.name = (baseLayer) ? this.id + "_baseLayers" : layer.name;
                inputElem.type = (baseLayer) ? "radio" : "checkbox";
                inputElem.value = layer.name;
                inputElem.checked = checked;
                inputElem.defaultChecked = checked;

                if (!baseLayer && !layer.inRange) {
                    inputElem.disabled = true;
                }
                var context = {
                    'inputElem': inputElem,
                    'layer': layer,
                    'layerSwitcher': this
                };
                OpenLayers.Event.observe(inputElem, "mouseup", 
                    OpenLayers.Function.bindAsEventListener(this.onInputClick,
                                                            context)
                );
                
                // create span
                var labelSpan = document.createElement("span");
                OpenLayers.Element.addClass(labelSpan, "labelSpan")
                if (!baseLayer && !layer.inRange) {
                    labelSpan.style.color = "gray";
                }
                labelSpan.innerHTML = layer.name;
                labelSpan.style.verticalAlign = (baseLayer) ? "bottom" 
                                                            : "baseline";
                OpenLayers.Event.observe(labelSpan, "click", 
                    OpenLayers.Function.bindAsEventListener(this.onInputClick,
                                                            context)
                );
                // create line break
                var br = document.createElement("br");
    
                
                var groupArray = (baseLayer) ? this.baseLayers
                                             : this.dataLayers;
                groupArray.push({
                    'layer': layer,
                    'inputElem': inputElem,
                    'labelSpan': labelSpan
                });
                                                     
    
                var groupDiv = (baseLayer) ? this.baseLayersDiv
                                           : this.dataLayersDiv;
                groupDiv.appendChild(inputElem);
                groupDiv.appendChild(labelSpan);
                groupDiv.appendChild(br);
            }
        }

        // if no overlays, dont display the overlay label
        this.dataLbl.style.display = (containsOverlays) ? "" : "none";        
        
        // if no baselayers, dont display the baselayer label
        this.baseLbl.style.display = (containsBaseLayers) ? "" : "none";        

        return this.div;
    },

    /** 
     * Method:
     * A label has been clicked, check or uncheck its corresponding input
     * 
     * Parameters:
     * e - {Event} 
     *
     * Context:  
     *  - {DOMElement} inputElem
     *  - {<OpenLayers.Control.LayerSwitcher>} layerSwitcher
     *  - {<OpenLayers.Layer>} layer
     */

    onInputClick: function(e) {

        if (!this.inputElem.disabled) {
            if (this.inputElem.type == "radio") {
                this.inputElem.checked = true;
                this.layer.map.setBaseLayer(this.layer);
            } else {
                this.inputElem.checked = !this.inputElem.checked;
                this.layerSwitcher.updateMap();
            }
        }
        OpenLayers.Event.stop(e);
    },
    
    /**
     * Method: onLayerClick
     * Need to update the map accordingly whenever user clicks in either of
     *     the layers.
     * 
     * Parameters: 
     * e - {Event} 
     */
    onLayerClick: function(e) {
        this.updateMap();
    },


    /** 
     * Method: updateMap
     * Cycles through the loaded data and base layer input arrays and makes
     *     the necessary calls to the Map object such that that the map's 
     *     visual state corresponds to what the user has selected in 
     *     the control.
     */
    updateMap: function() {

        // set the newly selected base layer        
        for(var i=0, len=this.baseLayers.length; i<len; i++) {
            var layerEntry = this.baseLayers[i];
            if (layerEntry.inputElem.checked) {
                this.map.setBaseLayer(layerEntry.layer, false);
            }
        }

        // set the correct visibilities for the overlays
        for(var i=0, len=this.dataLayers.length; i<len; i++) {
            var layerEntry = this.dataLayers[i];   
            layerEntry.layer.setVisibility(layerEntry.inputElem.checked);
        }

    },

    /** 
     * Method: maximizeControl
     * Set up the labels and divs for the control
     * 
     * Parameters:
     * e - {Event} 
     */
    maximizeControl: function(e) {

        // set the div's width and height to empty values, so
        // the div dimensions can be controlled by CSS
        this.div.style.width = "";
        this.div.style.height = "";

        this.showControls(false);

        if (e != null) {
            OpenLayers.Event.stop(e);                                            
        }
    },
    
    /** 
     * Method: minimizeControl
     * Hide all the contents of the control, shrink the size, 
     *     add the maximize icon
     *
     * Parameters:
     * e - {Event} 
     */
    minimizeControl: function(e) {

        // to minimize the control we set its div's width
        // and height to 0px, we cannot just set "display"
        // to "none" because it would hide the maximize
        // div
        this.div.style.width = "0px";
        this.div.style.height = "0px";

        this.showControls(true);

        if (e != null) {
            OpenLayers.Event.stop(e);                                            
        }
    },

    /**
     * Method: showControls
     * Hide/Show all LayerSwitcher controls depending on whether we are
     *     minimized or not
     * 
     * Parameters:
     * minimize - {Boolean}
     */
    showControls: function(minimize) {

        this.maximizeDiv.style.display = minimize ? "" : "none";
        this.minimizeDiv.style.display = minimize ? "none" : "";

        this.layersDiv.style.display = minimize ? "none" : "";
    },
    
    /** 
     * Method: loadContents
     * Set up the labels and divs for the control
     */
    loadContents: function() {

        //configure main div

        OpenLayers.Event.observe(this.div, "mouseup", 
            OpenLayers.Function.bindAsEventListener(this.mouseUp, this));
        OpenLayers.Event.observe(this.div, "click",
                      this.ignoreEvent);
        OpenLayers.Event.observe(this.div, "mousedown",
            OpenLayers.Function.bindAsEventListener(this.mouseDown, this));
        OpenLayers.Event.observe(this.div, "dblclick", this.ignoreEvent);

        // layers list div        
        this.layersDiv = document.createElement("div");
        this.layersDiv.id = this.id + "_layersDiv";
        OpenLayers.Element.addClass(this.layersDiv, "layersDiv");

        this.baseLbl = document.createElement("div");
        this.baseLbl.innerHTML = OpenLayers.i18n("baseLayer");
        OpenLayers.Element.addClass(this.baseLbl, "baseLbl");
        
        this.baseLayersDiv = document.createElement("div");
        OpenLayers.Element.addClass(this.baseLayersDiv, "baseLayersDiv");

        this.dataLbl = document.createElement("div");
        this.dataLbl.innerHTML = OpenLayers.i18n("overlays");
        OpenLayers.Element.addClass(this.dataLbl, "dataLbl");
        
        this.dataLayersDiv = document.createElement("div");
        OpenLayers.Element.addClass(this.dataLayersDiv, "dataLayersDiv");

        if (this.ascending) {
            this.layersDiv.appendChild(this.baseLbl);
            this.layersDiv.appendChild(this.baseLayersDiv);
            this.layersDiv.appendChild(this.dataLbl);
            this.layersDiv.appendChild(this.dataLayersDiv);
        } else {
            this.layersDiv.appendChild(this.dataLbl);
            this.layersDiv.appendChild(this.dataLayersDiv);
            this.layersDiv.appendChild(this.baseLbl);
            this.layersDiv.appendChild(this.baseLayersDiv);
        }    
 
        this.div.appendChild(this.layersDiv);

        if(this.roundedCorner) {
            OpenLayers.Rico.Corner.round(this.div, {
                corners: "tl bl",
                bgColor: "transparent",
                color: this.roundedCornerColor,
                blend: false
            });
            OpenLayers.Rico.Corner.changeOpacity(this.layersDiv, 0.75);
        }

        var imgLocation = OpenLayers.Util.getImagesLocation();
        var sz = new OpenLayers.Size(18,18);        

        // maximize button div
        var img = imgLocation + 'layer-switcher-maximize.png';
        this.maximizeDiv = OpenLayers.Util.createAlphaImageDiv(
                                    "OpenLayers_Control_MaximizeDiv", 
                                    null, 
                                    sz, 
                                    img, 
                                    "absolute");
        OpenLayers.Element.addClass(this.maximizeDiv, "maximizeDiv");
        this.maximizeDiv.style.display = "none";
        OpenLayers.Event.observe(this.maximizeDiv, "click", 
            OpenLayers.Function.bindAsEventListener(this.maximizeControl, this)
        );
        
        this.div.appendChild(this.maximizeDiv);

        // minimize button div
        var img = imgLocation + 'layer-switcher-minimize.png';
        var sz = new OpenLayers.Size(18,18);        
        this.minimizeDiv = OpenLayers.Util.createAlphaImageDiv(
                                    "OpenLayers_Control_MinimizeDiv", 
                                    null, 
                                    sz, 
                                    img, 
                                    "absolute");
        OpenLayers.Element.addClass(this.minimizeDiv, "minimizeDiv");
        this.minimizeDiv.style.display = "none";
        OpenLayers.Event.observe(this.minimizeDiv, "click", 
            OpenLayers.Function.bindAsEventListener(this.minimizeControl, this)
        );

        this.div.appendChild(this.minimizeDiv);
    },
    
    /** 
     * Method: ignoreEvent
     * 
     * Parameters:
     * evt - {Event} 
     */
    ignoreEvent: function(evt) {
        OpenLayers.Event.stop(evt);
    },

    /** 
     * Method: mouseDown
     * Register a local 'mouseDown' flag so that we'll know whether or not
     *     to ignore a mouseUp event
     * 
     * Parameters:
     * evt - {Event}
     */
    mouseDown: function(evt) {
        this.isMouseDown = true;
        this.ignoreEvent(evt);
    },

    /** 
     * Method: mouseUp
     * If the 'isMouseDown' flag has been set, that means that the drag was 
     *     started from within the LayerSwitcher control, and thus we can 
     *     ignore the mouseup. Otherwise, let the Event continue.
     *  
     * Parameters:
     * evt - {Event} 
     */
    mouseUp: function(evt) {
        if (this.isMouseDown) {
            this.isMouseDown = false;
            this.ignoreEvent(evt);
        }
    },

    CLASS_NAME: "OpenLayers.Control.LayerSwitcher"
});
/* ======================================================================
    OpenLayers/Control/MousePosition.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */


/**
 * @requires OpenLayers/Control.js
 */

/**
 * Class: OpenLayers.Control.MousePosition
 * The MousePosition control displays geographic coordinates of the mouse
 * pointer, as it is moved about the map.
 *
 * Inherits from:
 *  - <OpenLayers.Control>
 */
OpenLayers.Control.MousePosition = OpenLayers.Class(OpenLayers.Control, {
    
    /** 
     * Property: element
     * {DOMElement} 
     */
    element: null,
    
    /** 
     * APIProperty: prefix
     * {String}
     */
    prefix: '',
    
    /** 
     * APIProperty: separator
     * {String}
     */
    separator: ', ',
    
    /** 
     * APIProperty: suffix
     * {String}
     */
    suffix: '',
    
    /** 
     * APIProperty: numDigits
     * {Integer}
     */
    numDigits: 5,
    
    /** 
     * APIProperty: granularity
     * {Integer} 
     */
    granularity: 10,

    /**
     * APIProperty: emptyString 
     * {String} Set this to some value to set when the mouse is outside the
     *     map.
     */
    emptyString: null,
    
    /** 
     * Property: lastXy
     * {<OpenLayers.Pixel>}
     */
    lastXy: null,

    /**
     * APIProperty: displayProjection
     * {<OpenLayers.Projection>} The projection in which the 
     * mouse position is displayed
     */
    displayProjection: null, 
    
    /**
     * Constructor: OpenLayers.Control.MousePosition
     * 
     * Parameters:
     * options - {Object} Options for control.
     */
    initialize: function(options) {
        OpenLayers.Control.prototype.initialize.apply(this, arguments);
    },

    /**
     * Method: destroy
     */
     destroy: function() {
         if (this.map) {
             this.map.events.unregister('mousemove', this, this.redraw);
         }
         OpenLayers.Control.prototype.destroy.apply(this, arguments);
     },

    /**
     * Method: draw
     * {DOMElement}
     */    
    draw: function() {
        OpenLayers.Control.prototype.draw.apply(this, arguments);

        if (!this.element) {
            this.div.left = "";
            this.div.top = "";
            this.element = this.div;
        }
        
        this.redraw();
        return this.div;
    },
   
    /**
     * Method: redraw  
     */
    redraw: function(evt) {

        var lonLat;

        if (evt == null) {
            this.reset();
            return;
        } else {
            if (this.lastXy == null ||
                Math.abs(evt.xy.x - this.lastXy.x) > this.granularity ||
                Math.abs(evt.xy.y - this.lastXy.y) > this.granularity)
            {
                this.lastXy = evt.xy;
                return;
            }

            lonLat = this.map.getLonLatFromPixel(evt.xy);
            if (!lonLat) { 
                // map has not yet been properly initialized
                return;
            }    
            if (this.displayProjection) {
                lonLat.transform(this.map.getProjectionObject(), 
                                 this.displayProjection );
            }      
            this.lastXy = evt.xy;
            
        }
        
        var newHtml = this.formatOutput(lonLat);

        if (newHtml != this.element.innerHTML) {
            this.element.innerHTML = newHtml;
        }
    },

    /**
     * Method: reset
     */
    reset: function(evt) {
        if (this.emptyString != null) {
            this.element.innerHTML = this.emptyString;
        }
    },

    /**
     * Method: formatOutput
     * Override to provide custom display output
     *
     * Parameters:
     * lonLat - {<OpenLayers.LonLat>} Location to display
     */
    formatOutput: function(lonLat) {
        var digits = parseInt(this.numDigits);
        var newHtml =
            this.prefix +
            lonLat.lon.toFixed(digits) +
            this.separator + 
            lonLat.lat.toFixed(digits) +
            this.suffix;
        return newHtml;
     },

    /** 
     * Method: setMap
     */
    setMap: function() {
        OpenLayers.Control.prototype.setMap.apply(this, arguments);
        this.map.events.register( 'mousemove', this, this.redraw);
        this.map.events.register( 'mouseout', this, this.reset);
    },     

    CLASS_NAME: "OpenLayers.Control.MousePosition"
});
/* ======================================================================
    OpenLayers/Control/Pan.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */

/**
 * @requires OpenLayers/Control.js
 */

/**
 * Class: OpenLayers.Control.Pan
 * The Pan control is a single button to pan the map in one direction. For
 * a more complete control see <OpenLayers.Control.PanPanel>.
 *
 * Inherits from:
 *  - <OpenLayers.Control>
 */
OpenLayers.Control.Pan = OpenLayers.Class(OpenLayers.Control, {

    /** 
     * APIProperty: slideFactor
     * {Integer} Number of pixels by which we'll pan the map in any direction 
     *     on clicking the arrow buttons, defaults to 50.
     */
    slideFactor: 50,

    /** 
     * Property: direction
     * {String} in {'North', 'South', 'East', 'West'}
     */
    direction: null,

    /**
     * Property: type
     * {String} The type of <OpenLayers.Control> -- When added to a 
     *     <Control.Panel>, 'type' is used by the panel to determine how to 
     *     handle our events.
     */
    type: OpenLayers.Control.TYPE_BUTTON,

    /**
     * Constructor: OpenLayers.Control.Pan 
     * Control which handles the panning (in any of the cardinal directions)
     *     of the map by a set px distance. 
     *
     * Parameters:
     * direction - {String} The direction this button should pan.
     * options - {Object} An optional object whose properties will be used
     *     to extend the control.
     */
    initialize: function(direction, options) {
    
        this.direction = direction;
        this.CLASS_NAME += this.direction;
        
        OpenLayers.Control.prototype.initialize.apply(this, [options]);
    },
    
    /**
     * Method: trigger
     */
    trigger: function(){
    
        switch (this.direction) {
            case OpenLayers.Control.Pan.NORTH: 
                this.map.pan(0, -this.slideFactor);
                break;
            case OpenLayers.Control.Pan.SOUTH: 
                this.map.pan(0, this.slideFactor);
                break;
            case OpenLayers.Control.Pan.WEST: 
                this.map.pan(-this.slideFactor, 0);
                break;
            case OpenLayers.Control.Pan.EAST: 
                this.map.pan(this.slideFactor, 0);
                break;
        }
    },

    CLASS_NAME: "OpenLayers.Control.Pan"
});

OpenLayers.Control.Pan.NORTH = "North";
OpenLayers.Control.Pan.SOUTH = "South";
OpenLayers.Control.Pan.EAST = "East";
OpenLayers.Control.Pan.WEST = "West";
/* ======================================================================
    OpenLayers/Control/PanZoom.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */


/**
 * @requires OpenLayers/Control.js
 */

/**
 * Class: OpenLayers.Control.PanZoom
 * The PanZoom is a visible control, composed of a
 * <OpenLayers.Control.PanPanel> and a <OpenLayers.Control.ZoomPanel>. By
 * default it is drawn in the upper left corner of the map.
 *
 * Inherits from:
 *  - <OpenLayers.Control>
 */
OpenLayers.Control.PanZoom = OpenLayers.Class(OpenLayers.Control, {

    /** 
     * APIProperty: slideFactor
     * {Integer} Number of pixels by which we'll pan the map in any direction 
     *     on clicking the arrow buttons.  If you want to pan by some ratio
     *     of the map dimensions, use <slideRatio> instead.
     */
    slideFactor: 50,

    /** 
     * APIProperty: slideRatio
     * {Number} The fraction of map width/height by which we'll pan the map            
     *     on clicking the arrow buttons.  Default is null.  If set, will
     *     override <slideFactor>. E.g. if slideRatio is .5, then the Pan Up
     *     button will pan up half the map height. 
     */
    slideRatio: null,

    /** 
     * Property: buttons
     * {Array(DOMElement)} Array of Button Divs 
     */
    buttons: null,

    /** 
     * Property: position
     * {<OpenLayers.Pixel>} 
     */
    position: null,

    /**
     * Constructor: OpenLayers.Control.PanZoom
     * 
     * Parameters:
     * options - {Object}
     */
    initialize: function(options) {
        this.position = new OpenLayers.Pixel(OpenLayers.Control.PanZoom.X,
                                             OpenLayers.Control.PanZoom.Y);
        OpenLayers.Control.prototype.initialize.apply(this, arguments);
    },

    /**
     * APIMethod: destroy
     */
    destroy: function() {
        OpenLayers.Control.prototype.destroy.apply(this, arguments);
        this.removeButtons();
        this.buttons = null;
        this.position = null;
    },

    /**
     * Method: draw
     *
     * Parameters:
     * px - {<OpenLayers.Pixel>} 
     * 
     * Returns:
     * {DOMElement} A reference to the container div for the PanZoom control.
     */
    draw: function(px) {
        // initialize our internal div
        OpenLayers.Control.prototype.draw.apply(this, arguments);
        px = this.position;

        // place the controls
        this.buttons = [];

        var sz = new OpenLayers.Size(18,18);
        var centered = new OpenLayers.Pixel(px.x+sz.w/2, px.y);

        this._addButton("panup", "north-mini.png", centered, sz);
        px.y = centered.y+sz.h;
        this._addButton("panleft", "west-mini.png", px, sz);
        this._addButton("panright", "east-mini.png", px.add(sz.w, 0), sz);
        this._addButton("pandown", "south-mini.png", 
                        centered.add(0, sz.h*2), sz);
        this._addButton("zoomin", "zoom-plus-mini.png", 
                        centered.add(0, sz.h*3+5), sz);
        this._addButton("zoomworld", "zoom-world-mini.png", 
                        centered.add(0, sz.h*4+5), sz);
        this._addButton("zoomout", "zoom-minus-mini.png", 
                        centered.add(0, sz.h*5+5), sz);
        return this.div;
    },
    
    /**
     * Method: _addButton
     * 
     * Parameters:
     * id - {String} 
     * img - {String} 
     * xy - {<OpenLayers.Pixel>} 
     * sz - {<OpenLayers.Size>} 
     * 
     * Returns:
     * {DOMElement} A Div (an alphaImageDiv, to be precise) that contains the
     *     image of the button, and has all the proper event handlers set.
     */
    _addButton:function(id, img, xy, sz) {
        var imgLocation = OpenLayers.Util.getImagesLocation() + img;
        var btn = OpenLayers.Util.createAlphaImageDiv(
                                    this.id + "_" + id, 
                                    xy, sz, imgLocation, "absolute");

        //we want to add the outer div
        this.div.appendChild(btn);

        OpenLayers.Event.observe(btn, "mousedown", 
            OpenLayers.Function.bindAsEventListener(this.buttonDown, btn));
        OpenLayers.Event.observe(btn, "dblclick", 
            OpenLayers.Function.bindAsEventListener(this.doubleClick, btn));
        OpenLayers.Event.observe(btn, "click", 
            OpenLayers.Function.bindAsEventListener(this.doubleClick, btn));
        btn.action = id;
        btn.map = this.map;
    
        if(!this.slideRatio){
            var slideFactorPixels = this.slideFactor;
            var getSlideFactor = function() {
                return slideFactorPixels;
            };
        } else {
            var slideRatio = this.slideRatio;
            var getSlideFactor = function(dim) {
                return this.map.getSize()[dim] * slideRatio;
            };
        }

        btn.getSlideFactor = getSlideFactor;

        //we want to remember/reference the outer div
        this.buttons.push(btn);
        return btn;
    },
    
    /**
     * Method: _removeButton
     * 
     * Parameters:
     * btn - {Object}
     */
    _removeButton: function(btn) {
        OpenLayers.Event.stopObservingElement(btn);
        btn.map = null;
        btn.getSlideFactor = null;
        this.div.removeChild(btn);
        OpenLayers.Util.removeItem(this.buttons, btn);
    },
    
    /**
     * Method: removeButtons
     */
    removeButtons: function() {
        for(var i=this.buttons.length-1; i>=0; --i) {
            this._removeButton(this.buttons[i]);
        }
    },
    
    /**
     * Method: doubleClick
     *
     * Parameters:
     * evt - {Event} 
     *
     * Returns:
     * {Boolean}
     */
    doubleClick: function (evt) {
        OpenLayers.Event.stop(evt);
        return false;
    },
    
    /**
     * Method: buttonDown
     *
     * Parameters:
     * evt - {Event} 
     */
    buttonDown: function (evt) {
        if (!OpenLayers.Event.isLeftClick(evt)) {
            return;
        }

        switch (this.action) {
            case "panup": 
                this.map.pan(0, -this.getSlideFactor("h"));
                break;
            case "pandown": 
                this.map.pan(0, this.getSlideFactor("h"));
                break;
            case "panleft": 
                this.map.pan(-this.getSlideFactor("w"), 0);
                break;
            case "panright": 
                this.map.pan(this.getSlideFactor("w"), 0);
                break;
            case "zoomin": 
                this.map.zoomIn(); 
                break;
            case "zoomout": 
                this.map.zoomOut(); 
                break;
            case "zoomworld": 
                this.map.zoomToMaxExtent(); 
                break;
        }

        OpenLayers.Event.stop(evt);
    },

    CLASS_NAME: "OpenLayers.Control.PanZoom"
});

/**
 * Constant: X
 * {Integer}
 */
OpenLayers.Control.PanZoom.X = 4;

/**
 * Constant: Y
 * {Integer}
 */
OpenLayers.Control.PanZoom.Y = 4;
/* ======================================================================
    OpenLayers/Control/Panel.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */

/**
 * @requires OpenLayers/Control.js
 */

/**
 * Class: OpenLayers.Control.Panel
 * The Panel control is a container for other controls. With it toolbars
 * may be composed.
 *
 * Inherits from:
 *  - <OpenLayers.Control>
 */
OpenLayers.Control.Panel = OpenLayers.Class(OpenLayers.Control, {
    /**
     * Property: controls
     * {Array(<OpenLayers.Control>)}
     */
    controls: null,    
    
    /**
     * APIProperty: autoActivate
     * {Boolean} Activate the control when it is added to a map.  Default is
     *     true.
     */
    autoActivate: true,

    /** 
     * APIProperty: defaultControl
     * {<OpenLayers.Control>} The control which is activated when the control is
     * activated (turned on), which also happens at instantiation.
     */
    defaultControl: null, 

    /**
     * Constructor: OpenLayers.Control.Panel
     * Create a new control panel.
     * 
     * Parameters:
     * options - {Object} An optional object whose properties will be used
     *     to extend the control.
     */
    initialize: function(options) {
        OpenLayers.Control.prototype.initialize.apply(this, [options]);
        this.controls = [];
    },

    /**
     * APIMethod: destroy
     */
    destroy: function() {
        OpenLayers.Control.prototype.destroy.apply(this, arguments);
        for(var i = this.controls.length - 1 ; i >= 0; i--) {
            if(this.controls[i].events) {
                this.controls[i].events.un({
                    "activate": this.redraw,
                    "deactivate": this.redraw,
                    scope: this
                });
            }
            OpenLayers.Event.stopObservingElement(this.controls[i].panel_div);
            this.controls[i].panel_div = null;
        }    
    },

    /**
     * APIMethod: activate
     */
    activate: function() {
        if (OpenLayers.Control.prototype.activate.apply(this, arguments)) {
            for(var i=0, len=this.controls.length; i<len; i++) {
                if (this.controls[i] == this.defaultControl) {
                    this.controls[i].activate();
                }
            }    
            this.redraw();
            return true;
        } else {
            return false;
        }
    },
    
    /**
     * APIMethod: deactivate
     */
    deactivate: function() {
        if (OpenLayers.Control.prototype.deactivate.apply(this, arguments)) {
            for(var i=0, len=this.controls.length; i<len; i++) {
                this.controls[i].deactivate();
            }    
            return true;
        } else {
            return false;
        }
    },
    
    /**
     * Method: draw
     *
     * Returns:
     * {DOMElement}
     */    
    draw: function() {
        OpenLayers.Control.prototype.draw.apply(this, arguments);
        for (var i=0, len=this.controls.length; i<len; i++) {
            this.map.addControl(this.controls[i]);
            this.controls[i].deactivate();
            this.controls[i].events.on({
                "activate": this.redraw,
                "deactivate": this.redraw,
                scope: this
            });
        }
        return this.div;
    },

    /**
     * Method: redraw
     */
    redraw: function() {
        this.div.innerHTML = "";
        if (this.active) {
            for (var i=0, len=this.controls.length; i<len; i++) {
                var element = this.controls[i].panel_div;
                if (this.controls[i].active) {
                    element.className = this.controls[i].displayClass + "ItemActive";
                } else {    
                    element.className = this.controls[i].displayClass + "ItemInactive";
                }    
                this.div.appendChild(element);
            }
        }
    },

    /**
     * APIMethod: activateControl
     * 
     * Parameters:
     * control - {<OpenLayers.Control>}
     */
    activateControl: function (control) {
        if (!this.active) { return false; }
        if (control.type == OpenLayers.Control.TYPE_BUTTON) {
            control.trigger();
            this.redraw();
            return;
        }
        if (control.type == OpenLayers.Control.TYPE_TOGGLE) {
            if (control.active) {
                control.deactivate();
            } else {
                control.activate();
            }
            this.redraw();
            return;
        }
        for (var i=0, len=this.controls.length; i<len; i++) {
            if (this.controls[i] != control) {
                if (this.controls[i].type != OpenLayers.Control.TYPE_TOGGLE) {
                    this.controls[i].deactivate();
                }
            }
        }
        control.activate();
    },

    /**
     * APIMethod: addControls
     * To build a toolbar, you add a set of controls to it. addControls
     * lets you add a single control or a list of controls to the 
     * Control Panel.
     *
     * Parameters:
     * controls - {<OpenLayers.Control>} 
     */    
    addControls: function(controls) {
        if (!(controls instanceof Array)) {
            controls = [controls];
        }
        this.controls = this.controls.concat(controls);
        
        // Give each control a panel_div which will be used later.
        // Access to this div is via the panel_div attribute of the 
        // control added to the panel.
        // Also, stop mousedowns and clicks, but don't stop mouseup,
        // since they need to pass through.
        for (var i=0, len=controls.length; i<len; i++) {
            var element = document.createElement("div");
            controls[i].panel_div = element;
            if (controls[i].title != "") {
                controls[i].panel_div.title = controls[i].title;
            }
            OpenLayers.Event.observe(controls[i].panel_div, "click", 
                OpenLayers.Function.bind(this.onClick, this, controls[i]));
            OpenLayers.Event.observe(controls[i].panel_div, "dblclick", 
                OpenLayers.Function.bind(this.onDoubleClick, this, controls[i]));
            OpenLayers.Event.observe(controls[i].panel_div, "mousedown", 
                OpenLayers.Function.bindAsEventListener(OpenLayers.Event.stop));
        }    

        if (this.map) { // map.addControl() has already been called on the panel
            for (var i=0, len=controls.length; i<len; i++) {
                this.map.addControl(controls[i]);
                controls[i].deactivate();
                controls[i].events.on({
                    "activate": this.redraw,
                    "deactivate": this.redraw,
                    scope: this
                });
            }
            this.redraw();
        }
    },
   
    /**
     * Method: onClick
     */
    onClick: function (ctrl, evt) {
        OpenLayers.Event.stop(evt ? evt : window.event);
        this.activateControl(ctrl);
    },

    /**
     * Method: onDoubleClick
     */
    onDoubleClick: function(ctrl, evt) {
        OpenLayers.Event.stop(evt ? evt : window.event);
    },

    /**
     * APIMethod: getControlsBy
     * Get a list of controls with properties matching the given criteria.
     *
     * Parameter:
     * property - {String} A control property to be matched.
     * match - {String | Object} A string to match.  Can also be a regular
     *     expression literal or object.  In addition, it can be any object
     *     with a method named test.  For reqular expressions or other, if
     *     match.test(control[property]) evaluates to true, the control will be
     *     included in the array returned.  If no controls are found, an empty
     *     array is returned.
     *
     * Returns:
     * {Array(<OpenLayers.Control>)} A list of controls matching the given criteria.
     *     An empty array is returned if no matches are found.
     */
    getControlsBy: function(property, match) {
        var test = (typeof match.test == "function");
        var found = OpenLayers.Array.filter(this.controls, function(item) {
            return item[property] == match || (test && match.test(item[property]));
        });
        return found;
    },

    /**
     * APIMethod: getControlsByName
     * Get a list of contorls with names matching the given name.
     *
     * Parameter:
     * match - {String | Object} A control name.  The name can also be a regular
     *     expression literal or object.  In addition, it can be any object
     *     with a method named test.  For reqular expressions or other, if
     *     name.test(control.name) evaluates to true, the control will be included
     *     in the list of controls returned.  If no controls are found, an empty
     *     array is returned.
     *
     * Returns:
     * {Array(<OpenLayers.Control>)} A list of controls matching the given name.
     *     An empty array is returned if no matches are found.
     */
    getControlsByName: function(match) {
        return this.getControlsBy("name", match);
    },

    /**
     * APIMethod: getControlsByClass
     * Get a list of controls of a given type (CLASS_NAME).
     *
     * Parameter:
     * match - {String | Object} A control class name.  The type can also be a
     *     regular expression literal or object.  In addition, it can be any
     *     object with a method named test.  For reqular expressions or other,
     *     if type.test(control.CLASS_NAME) evaluates to true, the control will
     *     be included in the list of controls returned.  If no controls are
     *     found, an empty array is returned.
     *
     * Returns:
     * {Array(<OpenLayers.Control>)} A list of controls matching the given type.
     *     An empty array is returned if no matches are found.
     */
    getControlsByClass: function(match) {
        return this.getControlsBy("CLASS_NAME", match);
    },

    CLASS_NAME: "OpenLayers.Control.Panel"
});

/* ======================================================================
    OpenLayers/Control/ZoomIn.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */

/**
 * @requires OpenLayers/Control.js
 */

/**
 * Class: OpenLayers.Control.ZoomIn
 * The ZoomIn control is a button to increase the zoom level of a map.
 *
 * Inherits from:
 *  - <OpenLayers.Control>
 */
OpenLayers.Control.ZoomIn = OpenLayers.Class(OpenLayers.Control, {

    /**
     * Property: type
     * {String} The type of <OpenLayers.Control> -- When added to a 
     *     <Control.Panel>, 'type' is used by the panel to determine how to 
     *     handle our events.
     */
    type: OpenLayers.Control.TYPE_BUTTON,
    
    /**
     * Method: trigger
     */
    trigger: function(){
        this.map.zoomIn();
    },

    CLASS_NAME: "OpenLayers.Control.ZoomIn"
});
/* ======================================================================
    OpenLayers/Control/ZoomOut.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */

/**
 * @requires OpenLayers/Control.js
 */

/**
 * Class: OpenLayers.Control.ZoomOut
 * The ZoomOut control is a button to decrease the zoom level of a map.
 *
 * Inherits from:
 *  - <OpenLayers.Control>
 */
OpenLayers.Control.ZoomOut = OpenLayers.Class(OpenLayers.Control, {

    /**
     * Property: type
     * {String} The type of <OpenLayers.Control> -- When added to a 
     *     <Control.Panel>, 'type' is used by the panel to determine how to 
     *     handle our events.
     */
    type: OpenLayers.Control.TYPE_BUTTON,
    
    /**
     * Method: trigger
     */
    trigger: function(){
        this.map.zoomOut();
    },

    CLASS_NAME: "OpenLayers.Control.ZoomOut"
});
/* ======================================================================
    OpenLayers/Control/ZoomToMaxExtent.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */

/**
 * @requires OpenLayers/Control.js
 */

/**
 * Class: OpenLayers.Control.ZoomToMaxExtent 
 * The ZoomToMaxExtent control is a button that zooms out to the maximum
 * extent of the map. It is designed to be used with a 
 * <OpenLayers.Control.Panel>.
 * 
 * Inherits from:
 *  - <OpenLayers.Control>
 */
OpenLayers.Control.ZoomToMaxExtent = OpenLayers.Class(OpenLayers.Control, {

    /**
     * Property: type
     * {String} The type of <OpenLayers.Control> -- When added to a 
     *     <Control.Panel>, 'type' is used by the panel to determine how to 
     *     handle our events.
     */
    type: OpenLayers.Control.TYPE_BUTTON,
    
    /*
     * Method: trigger
     * Do the zoom.
     */
    trigger: function() {
        if (this.map) {
            this.map.zoomToMaxExtent();
        }    
    },

    CLASS_NAME: "OpenLayers.Control.ZoomToMaxExtent"
});
/* ======================================================================
    OpenLayers/Events.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */


/**
 * @requires OpenLayers/Util.js
 */

/**
 * Namespace: OpenLayers.Event
 * Utility functions for event handling.
 */
OpenLayers.Event = {

    /** 
     * Property: observers 
     * {Object} A hashtable cache of the event observers. Keyed by
     * element._eventCacheID 
     */
    observers: false,
    
    /** 
     * Constant: KEY_BACKSPACE 
     * {int} 
     */
    KEY_BACKSPACE: 8,

    /** 
     * Constant: KEY_TAB 
     * {int} 
     */
    KEY_TAB: 9,

    /** 
     * Constant: KEY_RETURN 
     * {int} 
     */
    KEY_RETURN: 13,

    /** 
     * Constant: KEY_ESC 
     * {int} 
     */
    KEY_ESC: 27,

    /** 
     * Constant: KEY_LEFT 
     * {int} 
     */
    KEY_LEFT: 37,

    /** 
     * Constant: KEY_UP 
     * {int} 
     */
    KEY_UP: 38,

    /** 
     * Constant: KEY_RIGHT 
     * {int} 
     */
    KEY_RIGHT: 39,

    /** 
     * Constant: KEY_DOWN 
     * {int} 
     */
    KEY_DOWN: 40,

    /** 
     * Constant: KEY_DELETE 
     * {int} 
     */
    KEY_DELETE: 46,


    /**
     * Method: element
     * Cross browser event element detection.
     * 
     * Parameters:
     * event - {Event} 
     * 
     * Returns:
     * {DOMElement} The element that caused the event 
     */
    element: function(event) {
        return event.target || event.srcElement;
    },

    /**
     * Method: isLeftClick
     * Determine whether event was caused by a left click. 
     *
     * Parameters:
     * event - {Event} 
     * 
     * Returns:
     * {Boolean}
     */
    isLeftClick: function(event) {
        return (((event.which) && (event.which == 1)) ||
                ((event.button) && (event.button == 1)));
    },

    /**
     * Method: isRightClick
     * Determine whether event was caused by a right mouse click. 
     *
     * Parameters:
     * event - {Event} 
     * 
     * Returns:
     * {Boolean}
     */
     isRightClick: function(event) {
        return (((event.which) && (event.which == 3)) ||
                ((event.button) && (event.button == 2)));
    },
     
    /**
     * Method: stop
     * Stops an event from propagating. 
     *
     * Parameters: 
     * event - {Event} 
     * allowDefault - {Boolean} If true, we stop the event chain but 
     *                               still allow the default browser 
     *                               behaviour (text selection, radio-button 
     *                               clicking, etc)
     *                               Default false
     */
    stop: function(event, allowDefault) {
        
        if (!allowDefault) { 
            if (event.preventDefault) {
                event.preventDefault();
            } else {
                event.returnValue = false;
            }
        }
                
        if (event.stopPropagation) {
            event.stopPropagation();
        } else {
            event.cancelBubble = true;
        }
    },

    /** 
     * Method: findElement
     * 
     * Parameters:
     * event - {Event} 
     * tagName - {String} 
     * 
     * Returns:
     * {DOMElement} The first node with the given tagName, starting from the
     * node the event was triggered on and traversing the DOM upwards
     */
    findElement: function(event, tagName) {
        var element = OpenLayers.Event.element(event);
        while (element.parentNode && (!element.tagName ||
              (element.tagName.toUpperCase() != tagName.toUpperCase()))){
            element = element.parentNode;
        }
        return element;
    },

    /** 
     * Method: observe
     * 
     * Parameters:
     * elementParam - {DOMElement || String} 
     * name - {String} 
     * observer - {function} 
     * useCapture - {Boolean} 
     */
    observe: function(elementParam, name, observer, useCapture) {
        var element = OpenLayers.Util.getElement(elementParam);
        useCapture = useCapture || false;

        if (name == 'keypress' &&
           (navigator.appVersion.match(/Konqueror|Safari|KHTML/)
           || element.attachEvent)) {
            name = 'keydown';
        }

        //if observers cache has not yet been created, create it
        if (!this.observers) {
            this.observers = {};
        }

        //if not already assigned, make a new unique cache ID
        if (!element._eventCacheID) {
            var idPrefix = "eventCacheID_";
            if (element.id) {
                idPrefix = element.id + "_" + idPrefix;
            }
            element._eventCacheID = OpenLayers.Util.createUniqueID(idPrefix);
        }

        var cacheID = element._eventCacheID;

        //if there is not yet a hash entry for this element, add one
        if (!this.observers[cacheID]) {
            this.observers[cacheID] = [];
        }

        //add a new observer to this element's list
        this.observers[cacheID].push({
            'element': element,
            'name': name,
            'observer': observer,
            'useCapture': useCapture
        });

        //add the actual browser event listener
        if (element.addEventListener) {
            element.addEventListener(name, observer, useCapture);
        } else if (element.attachEvent) {
            element.attachEvent('on' + name, observer);
        }
    },

    /** 
     * Method: stopObservingElement
     * Given the id of an element to stop observing, cycle through the 
     *   element's cached observers, calling stopObserving on each one, 
     *   skipping those entries which can no longer be removed.
     * 
     * parameters:
     * elementParam - {DOMElement || String} 
     */
    stopObservingElement: function(elementParam) {
        var element = OpenLayers.Util.getElement(elementParam);
        var cacheID = element._eventCacheID;

        this._removeElementObservers(OpenLayers.Event.observers[cacheID]);
    },

    /**
     * Method: _removeElementObservers
     *
     * Parameters:
     * elementObservers - {Array(Object)} Array of (element, name, 
     *                                         observer, usecapture) objects, 
     *                                         taken directly from hashtable
     */
    _removeElementObservers: function(elementObservers) {
        if (elementObservers) {
            for(var i = elementObservers.length-1; i >= 0; i--) {
                var entry = elementObservers[i];
                var args = new Array(entry.element,
                                     entry.name,
                                     entry.observer,
                                     entry.useCapture);
                var removed = OpenLayers.Event.stopObserving.apply(this, args);
            }
        }
    },

    /**
     * Method: stopObserving
     * 
     * Parameters:
     * elementParam - {DOMElement || String} 
     * name - {String} 
     * observer - {function} 
     * useCapture - {Boolean} 
     *  
     * Returns:
     * {Boolean} Whether or not the event observer was removed
     */
    stopObserving: function(elementParam, name, observer, useCapture) {
        useCapture = useCapture || false;
    
        var element = OpenLayers.Util.getElement(elementParam);
        var cacheID = element._eventCacheID;

        if (name == 'keypress') {
            if ( navigator.appVersion.match(/Konqueror|Safari|KHTML/) || 
                 element.detachEvent) {
              name = 'keydown';
            }
        }

        // find element's entry in this.observers cache and remove it
        var foundEntry = false;
        var elementObservers = OpenLayers.Event.observers[cacheID];
        if (elementObservers) {
    
            // find the specific event type in the element's list
            var i=0;
            while(!foundEntry && i < elementObservers.length) {
                var cacheEntry = elementObservers[i];
    
                if ((cacheEntry.name == name) &&
                    (cacheEntry.observer == observer) &&
                    (cacheEntry.useCapture == useCapture)) {
    
                    elementObservers.splice(i, 1);
                    if (elementObservers.length == 0) {
                        delete OpenLayers.Event.observers[cacheID];
                    }
                    foundEntry = true;
                    break; 
                }
                i++;           
            }
        }
    
        //actually remove the event listener from browser
        if (foundEntry) {
            if (element.removeEventListener) {
                element.removeEventListener(name, observer, useCapture);
            } else if (element && element.detachEvent) {
                element.detachEvent('on' + name, observer);
            }
        }
        return foundEntry;
    },
    
    /** 
     * Method: unloadCache
     * Cycle through all the element entries in the events cache and call
     *   stopObservingElement on each. 
     */
    unloadCache: function() {
        // check for OpenLayers.Event before checking for observers, because
        // OpenLayers.Event may be undefined in IE if no map instance was
        // created
        if (OpenLayers.Event && OpenLayers.Event.observers) {
            for (var cacheID in OpenLayers.Event.observers) {
                var elementObservers = OpenLayers.Event.observers[cacheID];
                OpenLayers.Event._removeElementObservers.apply(this, 
                                                           [elementObservers]);
            }
            OpenLayers.Event.observers = false;
        }
    },

    CLASS_NAME: "OpenLayers.Event"
};

/* prevent memory leaks in IE */
OpenLayers.Event.observe(window, 'unload', OpenLayers.Event.unloadCache, false);

// FIXME: Remove this in 3.0. In 3.0, Event.stop will no longer be provided
// by OpenLayers.
if (window.Event) {
    OpenLayers.Util.applyDefaults(window.Event, OpenLayers.Event);
} else {
    var Event = OpenLayers.Event;
}

/**
 * Class: OpenLayers.Events
 */
OpenLayers.Events = OpenLayers.Class({

    /** 
     * Constant: BROWSER_EVENTS
     * {Array(String)} supported events 
     */
    BROWSER_EVENTS: [
        "mouseover", "mouseout",
        "mousedown", "mouseup", "mousemove", 
        "click", "dblclick", "rightclick", "dblrightclick",
        "resize", "focus", "blur"
    ],

    /** 
     * Property: listeners 
     * {Object} Hashtable of Array(Function): events listener functions  
     */
    listeners: null,

    /** 
     * Property: object 
     * {Object}  the code object issuing application events 
     */
    object: null,

    /** 
     * Property: element 
     * {DOMElement}  the DOM element receiving browser events 
     */
    element: null,

    /** 
     * Property: eventTypes 
     * {Array(String)}  list of support application events 
     */
    eventTypes: null,

    /** 
     * Property: eventHandler 
     * {Function}  bound event handler attached to elements 
     */
    eventHandler: null,

    /** 
     * APIProperty: fallThrough 
     * {Boolean} 
     */
    fallThrough: null,

    /** 
     * APIProperty: includeXY
     * {Boolean} Should the .xy property automatically be created for browser
     *    mouse events? In general, this should be false. If it is true, then
     *    mouse events will automatically generate a '.xy' property on the 
     *    event object that is passed. (Prior to OpenLayers 2.7, this was true
     *    by default.) Otherwise, you can call the getMousePosition on the
     *    relevant events handler on the object available via the 'evt.object'
     *    property of the evt object. So, for most events, you can call:
     *    function named(evt) { 
     *        this.xy = this.object.events.getMousePosition(evt) 
     *    } 
     *
     *    This option typically defaults to false for performance reasons:
     *    when creating an events object whose primary purpose is to manage
     *    relatively positioned mouse events within a div, it may make
     *    sense to set it to true.
     *
     *    This option is also used to control whether the events object caches
     *    offsets. If this is false, it will not: the reason for this is that
     *    it is only expected to be called many times if the includeXY property
     *    is set to true. If you set this to true, you are expected to clear 
     *    the offset cache manually (using this.clearMouseCache()) if:
     *        the border of the element changes
     *        the location of the element in the page changes
    */
    includeXY: false,      

    /**
     * Method: clearMouseListener
     * A version of <clearMouseCache> that is bound to this instance so that
     *     it can be used with <OpenLayers.Event.observe> and
     *     <OpenLayers.Event.stopObserving>.
     */
    clearMouseListener: null,

    /**
     * Constructor: OpenLayers.Events
     * Construct an OpenLayers.Events object.
     *
     * Parameters:
     * object - {Object} The js object to which this Events object  is being
     * added element - {DOMElement} A dom element to respond to browser events
     * eventTypes - {Array(String)} Array of custom application events 
     * fallThrough - {Boolean} Allow events to fall through after these have
     *                         been handled?
     * options - {Object} Options for the events object.
     */
    initialize: function (object, element, eventTypes, fallThrough, options) {
        OpenLayers.Util.extend(this, options);
        this.object     = object;
        this.fallThrough = fallThrough;
        this.listeners  = {};

        // keep a bound copy of handleBrowserEvent() so that we can
        // pass the same function to both Event.observe() and .stopObserving()
        this.eventHandler = OpenLayers.Function.bindAsEventListener(
            this.handleBrowserEvent, this
        );
        
        // to be used with observe and stopObserving
        this.clearMouseListener = OpenLayers.Function.bind(
            this.clearMouseCache, this
        );

        // if eventTypes is specified, create a listeners list for each 
        // custom application event.
        this.eventTypes = [];
        if (eventTypes != null) {
            for (var i=0, len=eventTypes.length; i<len; i++) {
                this.addEventType(eventTypes[i]);
            }
        }
        
        // if a dom element is specified, add a listeners list 
        // for browser events on the element and register them
        if (element != null) {
            this.attachToElement(element);
        }
    },

    /**
     * APIMethod: destroy
     */
    destroy: function () {
        if (this.element) {
            OpenLayers.Event.stopObservingElement(this.element);
            if(this.element.hasScrollEvent) {
                OpenLayers.Event.stopObserving(
                    window, "scroll", this.clearMouseListener
                );
            }
        }
        this.element = null;

        this.listeners = null;
        this.object = null;
        this.eventTypes = null;
        this.fallThrough = null;
        this.eventHandler = null;
    },

    /**
     * APIMethod: addEventType
     * Add a new event type to this events object.
     * If the event type has already been added, do nothing.
     * 
     * Parameters:
     * eventName - {String}
     */
    addEventType: function(eventName) {
        if (!this.listeners[eventName]) {
            this.eventTypes.push(eventName);
            this.listeners[eventName] = [];
        }
    },

    /**
     * Method: attachToElement
     *
     * Parameters:
     * element - {HTMLDOMElement} a DOM element to attach browser events to
     */
    attachToElement: function (element) {
        if(this.element) {
            OpenLayers.Event.stopObservingElement(this.element);
        }
        this.element = element;
        for (var i=0, len=this.BROWSER_EVENTS.length; i<len; i++) {
            var eventType = this.BROWSER_EVENTS[i];

            // every browser event has a corresponding application event 
            // (whether it's listened for or not).
            this.addEventType(eventType);
            
            // use Prototype to register the event cross-browser
            OpenLayers.Event.observe(element, eventType, this.eventHandler);
        }
        // disable dragstart in IE so that mousedown/move/up works normally
        OpenLayers.Event.observe(element, "dragstart", OpenLayers.Event.stop);
    },
    
    /**
     * APIMethod: on
     * Convenience method for registering listeners with a common scope.
     *     Internally, this method calls <register> as shown in the examples
     *     below.
     *
     * Example use:
     * (code)
     * // register a single listener for the "loadstart" event
     * events.on({"loadstart", loadStartListener});
     *
     * // this is equivalent to the following
     * events.register("loadstart", undefined, loadStartListener);
     *
     * // register multiple listeners to be called with the same `this` object
     * events.on({
     *     "loadstart": loadStartListener,
     *     "loadend": loadEndListener,
     *     scope: object
     * });
     *
     * // this is equivalent to the following
     * events.register("loadstart", object, loadStartListener);
     * events.register("loadstart", object, loadEndListener);
     * (end)
     */
    on: function(object) {
        for(var type in object) {
            if(type != "scope") {
                this.register(type, object.scope, object[type]);
            }
        }
    },

    /**
     * APIMethod: register
     * Register an event on the events object.
     *
     * When the event is triggered, the 'func' function will be called, in the
     * context of 'obj'. Imagine we were to register an event, specifying an 
     * OpenLayers.Bounds Object as 'obj'. When the event is triggered, the 
     * context in the callback function will be our Bounds object. This means
     * that within our callback function, we can access the properties and 
     * methods of the Bounds object through the "this" variable. So our 
     * callback could execute something like: 
     * :    leftStr = "Left: " + this.left;
     *   
     *                   or
     *  
     * :    centerStr = "Center: " + this.getCenterLonLat();
     *
     * Parameters:
     * type - {String} Name of the event to register
     * obj - {Object} The object to bind the context to for the callback#.
     *                     If no object is specified, default is the Events's 
     *                     'object' property.
     * func - {Function} The callback function. If no callback is 
     *                        specified, this function does nothing.
     * 
     * 
     */
    register: function (type, obj, func) {

        if ( (func != null) && 
             (OpenLayers.Util.indexOf(this.eventTypes, type) != -1) ) {

            if (obj == null)  {
                obj = this.object;
            }
            var listeners = this.listeners[type];
            listeners.push( {obj: obj, func: func} );
        }
    },

    /**
     * APIMethod: registerPriority
     * Same as register() but adds the new listener to the *front* of the
     *     events queue instead of to the end.
     *    
     *     TODO: get rid of this in 3.0 - Decide whether listeners should be 
     *     called in the order they were registered or in reverse order.
     *
     *
     * Parameters:
     * type - {String} Name of the event to register
     * obj - {Object} The object to bind the context to for the callback#.
     *                If no object is specified, default is the Events's 
     *                'object' property.
     * func - {Function} The callback function. If no callback is 
     *                   specified, this function does nothing.
     */
    registerPriority: function (type, obj, func) {

        if (func != null) {
            if (obj == null)  {
                obj = this.object;
            }
            var listeners = this.listeners[type];
            if (listeners != null) {
                listeners.unshift( {obj: obj, func: func} );
            }
        }
    },
    
    /**
     * APIMethod: un
     * Convenience method for unregistering listeners with a common scope.
     *     Internally, this method calls <unregister> as shown in the examples
     *     below.
     *
     * Example use:
     * (code)
     * // unregister a single listener for the "loadstart" event
     * events.un({"loadstart", loadStartListener});
     *
     * // this is equivalent to the following
     * events.unregister("loadstart", undefined, loadStartListener);
     *
     * // unregister multiple listeners with the same `this` object
     * events.un({
     *     "loadstart": loadStartListener,
     *     "loadend": loadEndListener,
     *     scope: object
     * });
     *
     * // this is equivalent to the following
     * events.unregister("loadstart", object, loadStartListener);
     * events.unregister("loadstart", object, loadEndListener);
     * (end)
     */
    un: function(object) {
        for(var type in object) {
            if(type != "scope") {
                this.unregister(type, object.scope, object[type]);
            }
        }
    },

    /**
     * APIMethod: unregister
     *
     * Parameters:
     * type - {String} 
     * obj - {Object} If none specified, defaults to this.object
     * func - {Function} 
     */
    unregister: function (type, obj, func) {
        if (obj == null)  {
            obj = this.object;
        }
        var listeners = this.listeners[type];
        if (listeners != null) {
            for (var i=0, len=listeners.length; i<len; i++) {
                if (listeners[i].obj == obj && listeners[i].func == func) {
                    listeners.splice(i, 1);
                    break;
                }
            }
        }
    },

    /** 
     * Method: remove
     * Remove all listeners for a given event type. If type is not registered,
     *     does nothing.
     *
     * Parameters:
     * type - {String} 
     */
    remove: function(type) {
        if (this.listeners[type] != null) {
            this.listeners[type] = [];
        }
    },

    /**
     * APIMethod: triggerEvent
     * Trigger a specified registered event.  
     * 
     * Parameters:
     * type - {String} 
     * evt - {Event}
     *
     * Returns:
     * {Boolean} The last listener return.  If a listener returns false, the
     *     chain of listeners will stop getting called.
     */
    triggerEvent: function (type, evt) {
        var listeners = this.listeners[type];

        // fast path
        if(!listeners || listeners.length == 0) {
            return;
        }

        // prep evt object with object & div references
        if (evt == null) {
            evt = {};
        }
        evt.object = this.object;
        evt.element = this.element;
        if(!evt.type) {
            evt.type = type;
        }
    
        // execute all callbacks registered for specified type
        // get a clone of the listeners array to
        // allow for splicing during callbacks
        var listeners = listeners.slice(), continueChain;
        for (var i=0, len=listeners.length; i<len; i++) {
            var callback = listeners[i];
            // bind the context to callback.obj
            continueChain = callback.func.apply(callback.obj, [evt]);

            if ((continueChain != undefined) && (continueChain == false)) {
                // if callback returns false, execute no more callbacks.
                break;
            }
        }
        // don't fall through to other DOM elements
        if (!this.fallThrough) {           
            OpenLayers.Event.stop(evt, true);
        }
        return continueChain;
    },

    /**
     * Method: handleBrowserEvent
     * Basically just a wrapper to the triggerEvent() function, but takes 
     *     care to set a property 'xy' on the event with the current mouse 
     *     position.
     *
     * Parameters:
     * evt - {Event} 
     */
    handleBrowserEvent: function (evt) {
        if (this.includeXY) {
            evt.xy = this.getMousePosition(evt);
        } 
        this.triggerEvent(evt.type, evt);
    },

    /**
     * APIMethod: clearMouseCache
     * Clear cached data about the mouse position. This should be called any 
     *     time the element that events are registered on changes position 
     *     within the page.
     */
    clearMouseCache: function() { 
        this.element.scrolls = null;
        this.element.lefttop = null;
        this.element.offsets = null;
    },      

    /**
     * Method: getMousePosition
     * 
     * Parameters:
     * evt - {Event} 
     * 
     * Returns:
     * {<OpenLayers.Pixel>} The current xy coordinate of the mouse, adjusted
     *                      for offsets
     */
    getMousePosition: function (evt) {
        if (!this.includeXY) {
            this.clearMouseCache();
        } else if (!this.element.hasScrollEvent) {
            OpenLayers.Event.observe(window, "scroll", this.clearMouseListener);
            this.element.hasScrollEvent = true;
        }
        
        if (!this.element.scrolls) {
            this.element.scrolls = [
                (document.documentElement.scrollLeft
                         || document.body.scrollLeft),
                (document.documentElement.scrollTop
                         || document.body.scrollTop)
            ];
        }

        if (!this.element.lefttop) {
            this.element.lefttop = [
                (document.documentElement.clientLeft || 0),
                (document.documentElement.clientTop  || 0)
            ];
        }
        
        if (!this.element.offsets) {
            this.element.offsets = OpenLayers.Util.pagePosition(this.element);
            this.element.offsets[0] += this.element.scrolls[0];
            this.element.offsets[1] += this.element.scrolls[1];
        }
        return new OpenLayers.Pixel(
            (evt.clientX + this.element.scrolls[0]) - this.element.offsets[0]
                         - this.element.lefttop[0], 
            (evt.clientY + this.element.scrolls[1]) - this.element.offsets[1]
                         - this.element.lefttop[1]
        ); 
    },

    CLASS_NAME: "OpenLayers.Events"
});
/* ======================================================================
    OpenLayers/Format.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */

/**
 * @requires OpenLayers/Util.js
 * @requires OpenLayers/Console.js
 */

/**
 * Class: OpenLayers.Format
 * Base class for format reading/writing a variety of formats.  Subclasses
 *     of OpenLayers.Format are expected to have read and write methods.
 */
OpenLayers.Format = OpenLayers.Class({
    
    /**
     * Property: options
     * {Object} A reference to options passed to the constructor.
     */
    options: null,
    
    /**
     * APIProperty: externalProjection
     * {<OpenLayers.Projection>} When passed a externalProjection and
     *     internalProjection, the format will reproject the geometries it
     *     reads or writes. The externalProjection is the projection used by
     *     the content which is passed into read or which comes out of write.
     *     In order to reproject, a projection transformation function for the
     *     specified projections must be available. This support may be 
     *     provided via proj4js or via a custom transformation function. See
     *     {<OpenLayers.Projection.addTransform>} for more information on
     *     custom transformations.
     */
    externalProjection: null,

    /**
     * APIProperty: internalProjection
     * {<OpenLayers.Projection>} When passed a externalProjection and
     *     internalProjection, the format will reproject the geometries it
     *     reads or writes. The internalProjection is the projection used by
     *     the geometries which are returned by read or which are passed into
     *     write.  In order to reproject, a projection transformation function
     *     for the specified projections must be available. This support may be
     *     provided via proj4js or via a custom transformation function. See
     *     {<OpenLayers.Projection.addTransform>} for more information on
     *     custom transformations.
     */
    internalProjection: null,

    /**
     * APIProperty: data
     * {Object} When <keepData> is true, this is the parsed string sent to
     *     <read>.
     */
    data: null,

    /**
     * APIProperty: keepData
     * {Object} Maintain a reference (<data>) to the most recently read data.
     *     Default is false.
     */
    keepData: false,

    /**
     * Constructor: OpenLayers.Format
     * Instances of this class are not useful.  See one of the subclasses.
     *
     * Parameters:
     * options - {Object} An optional object with properties to set on the
     *           format
     *
     * Valid options:
     * keepData - {Boolean} If true, upon <read>, the data property will be
     *     set to the parsed object (e.g. the json or xml object).
     *
     * Returns:
     * An instance of OpenLayers.Format
     */
    initialize: function(options) {
        OpenLayers.Util.extend(this, options);
        this.options = options;
    },
    
    /**
     * APIMethod: destroy
     * Clean up.
     */
    destroy: function() {
    },

    /**
     * Method: read
     * Read data from a string, and return an object whose type depends on the
     * subclass. 
     * 
     * Parameters:
     * data - {string} Data to read/parse.
     *
     * Returns:
     * Depends on the subclass
     */
    read: function(data) {
        OpenLayers.Console.userError(OpenLayers.i18n("readNotImplemented"));
    },
    
    /**
     * Method: write
     * Accept an object, and return a string. 
     *
     * Parameters:
     * object - {Object} Object to be serialized
     *
     * Returns:
     * {String} A string representation of the object.
     */
    write: function(object) {
        OpenLayers.Console.userError(OpenLayers.i18n("writeNotImplemented"));
    },

    CLASS_NAME: "OpenLayers.Format"
});     
/* ======================================================================
    OpenLayers/Lang/en.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */

/**
 * @requires OpenLayers/Lang.js
 */

/**
 * Namespace: OpenLayers.Lang["en"]
 * Dictionary for English.  Keys for entries are used in calls to
 *     <OpenLayers.Lang.translate>.  Entry bodies are normal strings or
 *     strings formatted for use with <OpenLayers.String.format> calls.
 */
OpenLayers.Lang.en = {

    'unhandledRequest': "Unhandled request return ${statusText}",

    'permalink': "Permalink",

    'overlays': "Overlays",

    'baseLayer': "Base Layer",

    'sameProjection':
        "The overview map only works when it is in the same projection as the main map",

    'readNotImplemented': "Read not implemented.",

    'writeNotImplemented': "Write not implemented.",

    'noFID': "Can't update a feature for which there is no FID.",

    'errorLoadingGML': "Error in loading GML file ${url}",

    'browserNotSupported':
        "Your browser does not support vector rendering. Currently supported renderers are:\n${renderers}",

    'componentShouldBe': "addFeatures : component should be an ${geomType}",

    // console message
    'getFeatureError':
        "getFeatureFromEvent called on layer with no renderer. This usually means you " +
        "destroyed a layer, but not some handler which is associated with it.",

    // console message
    'minZoomLevelError':
        "The minZoomLevel property is only intended for use " +
        "with the FixedZoomLevels-descendent layers. That this " +
        "wfs layer checks for minZoomLevel is a relic of the" +
        "past. We cannot, however, remove it without possibly " +
        "breaking OL based applications that may depend on it." +
        " Therefore we are deprecating it -- the minZoomLevel " +
        "check below will be removed at 3.0. Please instead " +
        "use min/max resolution setting as described here: " +
        "http://trac.openlayers.org/wiki/SettingZoomLevels",

    'commitSuccess': "WFS Transaction: SUCCESS ${response}",

    'commitFailed': "WFS Transaction: FAILED ${response}",

    'googleWarning':
        "The Google Layer was unable to load correctly.<br><br>" +
        "To get rid of this message, select a new BaseLayer " +
        "in the layer switcher in the upper-right corner.<br><br>" +
        "Most likely, this is because the Google Maps library " +
        "script was either not included, or does not contain the " +
        "correct API key for your site.<br><br>" +
        "Developers: For help getting this working correctly, " +
        "<a href='http://trac.openlayers.org/wiki/Google' " +
        "target='_blank'>click here</a>",

    'getLayerWarning':
        "The ${layerType} Layer was unable to load correctly.<br><br>" +
        "To get rid of this message, select a new BaseLayer " +
        "in the layer switcher in the upper-right corner.<br><br>" +
        "Most likely, this is because the ${layerLib} library " +
        "script was not correctly included.<br><br>" +
        "Developers: For help getting this working correctly, " +
        "<a href='http://trac.openlayers.org/wiki/${layerLib}' " +
        "target='_blank'>click here</a>",

    'scale': "Scale = 1 : ${scaleDenom}",
    
    //labels for the graticule control
    'W': 'W',
    'E': 'E',
    'N': 'N',
    'S': 'S',

    // console message
    'layerAlreadyAdded':
        "You tried to add the layer: ${layerName} to the map, but it has already been added",

    // console message
    'reprojectDeprecated':
        "You are using the 'reproject' option " +
        "on the ${layerName} layer. This option is deprecated: " +
        "its use was designed to support displaying data over commercial " + 
        "basemaps, but that functionality should now be achieved by using " +
        "Spherical Mercator support. More information is available from " +
        "http://trac.openlayers.org/wiki/SphericalMercator.",

    // console message
    'methodDeprecated':
        "This method has been deprecated and will be removed in 3.0. " +
        "Please use ${newMethod} instead.",

    // console message
    'boundsAddError': "You must pass both x and y values to the add function.",

    // console message
    'lonlatAddError': "You must pass both lon and lat values to the add function.",

    // console message
    'pixelAddError': "You must pass both x and y values to the add function.",

    // console message
    'unsupportedGeometryType': "Unsupported geometry type: ${geomType}",

    // console message
    'pagePositionFailed':
        "OpenLayers.Util.pagePosition failed: element with id ${elemId} may be misplaced.",
                    
    'end': '',

    // console message
    'filterEvaluateNotImplemented': "evaluate is not implemented for this filter type."
};
/* ======================================================================
    OpenLayers/Popup/AnchoredBubble.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */


/**
 * @requires OpenLayers/Popup/Anchored.js
 */

/**
 * Class: OpenLayers.Popup.AnchoredBubble
 * 
 * Inherits from: 
 *  - <OpenLayers.Popup.Anchored>
 */
OpenLayers.Popup.AnchoredBubble = 
  OpenLayers.Class(OpenLayers.Popup.Anchored, {

    /**
     * Property: rounded
     * {Boolean} Has the popup been rounded yet?
     */
    rounded: false, 
    
    /** 
     * Constructor: OpenLayers.Popup.AnchoredBubble
     * 
     * Parameters:
     * id - {String}
     * lonlat - {<OpenLayers.LonLat>}
     * contentSize - {<OpenLayers.Size>}
     * contentHTML - {String}
     * anchor - {Object} Object to which we'll anchor the popup. Must expose 
     *     a 'size' (<OpenLayers.Size>) and 'offset' (<OpenLayers.Pixel>) 
     *     (Note that this is generally an <OpenLayers.Icon>).
     * closeBox - {Boolean}
     * closeBoxCallback - {Function} Function to be called on closeBox click.
     */
    initialize:function(id, lonlat, contentSize, contentHTML, anchor, closeBox,
                        closeBoxCallback) {
        
        this.padding = new OpenLayers.Bounds(
            0, OpenLayers.Popup.AnchoredBubble.CORNER_SIZE,
            0, OpenLayers.Popup.AnchoredBubble.CORNER_SIZE
        );
        OpenLayers.Popup.Anchored.prototype.initialize.apply(this, arguments);
    },

    /** 
     * Method: draw
     * 
     * Parameters:
     * px - {<OpenLayers.Pixel>}
     * 
     * Returns:
     * {DOMElement} Reference to a div that contains the drawn popup.
     */
    draw: function(px) {
        
        OpenLayers.Popup.Anchored.prototype.draw.apply(this, arguments);

        this.setContentHTML();
        
        //set the popup color and opacity           
        this.setBackgroundColor(); 
        this.setOpacity();

        return this.div;
    },

    /**
     * Method: updateRelativePosition
     * The popup has been moved to a new relative location, in which case
     *     we will want to re-do the rico corners.
     */
    updateRelativePosition: function() {
        this.setRicoCorners();
    },

    /**
     * APIMethod: setSize
     * 
     * Parameters:
     * contentSize - {<OpenLayers.Size>} the new size for the popup's 
     *     contents div (in pixels).
     */
    setSize:function(contentSize) { 
        OpenLayers.Popup.Anchored.prototype.setSize.apply(this, arguments);

        this.setRicoCorners();
    },  

    /**
     * APIMethod: setBackgroundColor
     * 
     * Parameters:
     * color - {String}
     */
    setBackgroundColor:function(color) { 
        if (color != undefined) {
            this.backgroundColor = color; 
        }
        
        if (this.div != null) {
            if (this.contentDiv != null) {
                this.div.style.background = "transparent";
                OpenLayers.Rico.Corner.changeColor(this.groupDiv, 
                                                   this.backgroundColor);
            }
        }
    },  
    
    /**
     * APIMethod: setOpacity
     * 
     * Parameters: 
     * opacity - {float}
     */
    setOpacity:function(opacity) { 
        OpenLayers.Popup.Anchored.prototype.setOpacity.call(this, opacity);
        
        if (this.div != null) {
            if (this.groupDiv != null) {
                OpenLayers.Rico.Corner.changeOpacity(this.groupDiv, 
                                                     this.opacity);
            }
        }
    },  
 
    /** 
     * Method: setBorder
     * Always sets border to 0. Bubble Popups can not have a border.
     * 
     * Parameters:
     * border - {Integer}
     */
    setBorder:function(border) { 
        this.border = 0;
    },      
 
    /** 
     * Method: setRicoCorners
     * Update RICO corners according to the popup's current relative postion.
     */
    setRicoCorners:function() {
    
        var corners = this.getCornersToRound(this.relativePosition);
        var options = {corners: corners,
                         color: this.backgroundColor,
                       bgColor: "transparent",
                         blend: false};

        if (!this.rounded) {
            OpenLayers.Rico.Corner.round(this.div, options);
            this.rounded = true;
        } else {
            OpenLayers.Rico.Corner.reRound(this.groupDiv, options);
            //set the popup color and opacity
            this.setBackgroundColor(); 
            this.setOpacity();
        }
    },

    /** 
     * Method: getCornersToRound
     *  
     * Returns:
     * {String} The proper corners string ("tr tl bl br") for rico to round.
     */
    getCornersToRound:function() {

        var corners = ['tl', 'tr', 'bl', 'br'];

        //we want to round all the corners _except_ the opposite one. 
        var corner = OpenLayers.Bounds.oppositeQuadrant(this.relativePosition);
        OpenLayers.Util.removeItem(corners, corner);

        return corners.join(" ");
    },

    CLASS_NAME: "OpenLayers.Popup.AnchoredBubble"
});

/**
 * Constant: CORNER_SIZE
 * {Integer} 5. Border space for the RICO corners.
 */
OpenLayers.Popup.AnchoredBubble.CORNER_SIZE = 5;

/* ======================================================================
    OpenLayers/Projection.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under a modified BSD license.
 * See http://svn.openlayers.org/trunk/openlayers/repository-license.txt 
 * for the full text of the license. */

/**
 * @requires OpenLayers/Util.js
 */

/**
 * Class: OpenLayers.Projection
 * Class for coordinate transforms between coordinate systems.
 *     Depends on the proj4js library. If proj4js is not available, 
 *     then this is just an empty stub.
 */
OpenLayers.Projection = OpenLayers.Class({

    /**
     * Property: proj
     * {Object} Proj4js.Proj instance.
     */
    proj: null,
    
    /**
     * Property: projCode
     * {String}
     */
    projCode: null,

    /**
     * Constructor: OpenLayers.Projection
     * This class offers several methods for interacting with a wrapped 
     *     pro4js projection object. 
     *
     * Parameters:
     * projCode - {String} A string identifying the Well Known Identifier for
     *    the projection.
     * options - {Object} An optional object to set additional properties
     *     on the layer.
     *
     * Returns:
     * {<OpenLayers.Projection>} A projection object.
     */
    initialize: function(projCode, options) {
        OpenLayers.Util.extend(this, options);
        this.projCode = projCode;
        if (window.Proj4js) {
            this.proj = new Proj4js.Proj(projCode);
        }
    },
    
    /**
     * APIMethod: getCode
     * Get the string SRS code.
     *
     * Returns:
     * {String} The SRS code.
     */
    getCode: function() {
        return this.proj ? this.proj.srsCode : this.projCode;
    },
   
    /**
     * APIMethod: getUnits
     * Get the units string for the projection -- returns null if 
     *     proj4js is not available.
     *
     * Returns:
     * {String} The units abbreviation.
     */
    getUnits: function() {
        return this.proj ? this.proj.units : null;
    },

    /**
     * Method: toString
     * Convert projection to string (getCode wrapper).
     *
     * Returns:
     * {String} The projection code.
     */
    toString: function() {
        return this.getCode();
    },

    /**
     * Method: equals
     * Test equality of two projection instances.  Determines equality based
     *     soley on the projection code.
     *
     * Returns:
     * {Boolean} The two projections are equivalent.
     */
    equals: function(projection) {
        if (projection && projection.getCode) {
            return this.getCode() == projection.getCode();
        } else {
            return false;
        }    
    },

    /* Method: destroy
     * Destroy projection object.
     */
    destroy: function() {
        delete this.proj;
        delete this.projCode;
    },
    
    CLASS_NAME: "OpenLayers.Projection" 
});     

/**
 * Property: transforms
 * Transforms is an object, with from properties, each of which may
 * have a to property. This allows you to define projections without 
 * requiring support for proj4js to be included.
 *
 * This object has keys which correspond to a 'source' projection object.  The
 * keys should be strings, corresponding to the projection.getCode() value.
 * Each source projection object should have a set of destination projection
 * keys included in the object. 
 * 
 * Each value in the destination object should be a transformation function,
 * where the function is expected to be passed an object with a .x and a .y
 * property.  The function should return the object, with the .x and .y
 * transformed according to the transformation function.
 *
 * Note - Properties on this object should not be set directly.  To add a
 *     transform method to this object, use the <addTransform> method.  For an
 *     example of usage, see the OpenLayers.Layer.SphericalMercator file.
 */
OpenLayers.Projection.transforms = {};

/**
 * APIMethod: addTransform
 * Set a custom transform method between two projections.  Use this method in
 *     cases where the proj4js lib is not available or where custom projections
 *     need to be handled.
 *
 * Parameters:
 * from - {String} The code for the source projection
 * to - {String} the code for the destination projection
 * method - {Function} A function that takes a point as an argument and
 *     transforms that point from the source to the destination projection
 *     in place.  The original point should be modified.
 */
OpenLayers.Projection.addTransform = function(from, to, method) {
    if(!OpenLayers.Projection.transforms[from]) {
        OpenLayers.Projection.transforms[from] = {};
    }
    OpenLayers.Projection.transforms[from][to] = method;
};

/**
 * APIMethod: transform
 * Transform a point coordinate from one projection to another.  Note that
 *     the input point is transformed in place.
 * 
 * Parameters:
 * point - {{OpenLayers.Geometry.Point> | Object} An object with x and y
 *     properties representing coordinates in those dimensions.
 * sourceProj - {OpenLayers.Projection} Source map coordinate system
 * destProj - {OpenLayers.Projection} Destination map coordinate system
 *
 * Returns:
 * point - {object} A transformed coordinate.  The original point is modified.
 */
OpenLayers.Projection.transform = function(point, source, dest) {
    if (source.proj && dest.proj) {
        point = Proj4js.transform(source.proj, dest.proj, point);
    } else if (source && dest && 
               OpenLayers.Projection.transforms[source.getCode()] && 
               OpenLayers.Projection.transforms[source.getCode()][dest.getCode()]) {
        OpenLayers.Projection.transforms[source.getCode()][dest.getCode()](point); 
    }
    return point;
};
/* ======================================================================
    OpenLayers/Renderer/SVG.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */

/**
 * @requires OpenLayers/Renderer/Elements.js
 */

/**
 * Class: OpenLayers.Renderer.SVG
 * 
 * Inherits:
 *  - <OpenLayers.Renderer.Elements>
 */
OpenLayers.Renderer.SVG = OpenLayers.Class(OpenLayers.Renderer.Elements, {

    /** 
     * Property: xmlns
     * {String}
     */
    xmlns: "http://www.w3.org/2000/svg",
    
    /**
     * Property: xlinkns
     * {String}
     */
    xlinkns: "http://www.w3.org/1999/xlink",

    /**
     * Constant: MAX_PIXEL
     * {Integer} Firefox has a limitation where values larger or smaller than  
     *           about 15000 in an SVG document lock the browser up. This 
     *           works around it.
     */
    MAX_PIXEL: 15000,

    /**
     * Property: translationParameters
     * {Object} Hash with "x" and "y" properties
     */
    translationParameters: null,
    
    /**
     * Property: symbolMetrics
     * {Object} Cache for symbol metrics according to their svg coordinate
     *     space. This is an object keyed by the symbol's id, and values are
     *     an array of [width, centerX, centerY].
     */
    symbolMetrics: null,
    
    /**
     * Property: isGecko
     * {Boolean}
     */
    isGecko: null,

    /**
     * Property: supportUse
     * {Boolean} true if defs/use is supported - known to not work as expected
     * at least in some applewebkit/5* builds.
     * See https://bugs.webkit.org/show_bug.cgi?id=33322
     */
    supportUse: null,

    /**
     * Constructor: OpenLayers.Renderer.SVG
     * 
     * Parameters:
     * containerID - {String}
     */
    initialize: function(containerID) {
        if (!this.supported()) { 
            return; 
        }
        OpenLayers.Renderer.Elements.prototype.initialize.apply(this, 
                                                                arguments);
        this.translationParameters = {x: 0, y: 0};
        this.supportUse = (navigator.userAgent.toLowerCase().indexOf("applewebkit/5") == -1);
        this.isGecko = (navigator.userAgent.toLowerCase().indexOf("gecko/") != -1);
        
        this.symbolMetrics = {};
    },

    /**
     * APIMethod: destroy
     */
    destroy: function() {
        OpenLayers.Renderer.Elements.prototype.destroy.apply(this, arguments);
    },
    
    /**
     * APIMethod: supported
     * 
     * Returns:
     * {Boolean} Whether or not the browser supports the SVG renderer
     */
    supported: function() {
        var svgFeature = "http://www.w3.org/TR/SVG11/feature#";
        return (document.implementation && 
           (document.implementation.hasFeature("org.w3c.svg", "1.0") || 
            document.implementation.hasFeature(svgFeature + "SVG", "1.1") || 
            document.implementation.hasFeature(svgFeature + "BasicStructure", "1.1") ));
    },    

    /**
     * Method: inValidRange
     * See #669 for more information
     *
     * Parameters:
     * x      - {Integer}
     * y      - {Integer}
     * xyOnly - {Boolean} whether or not to just check for x and y, which means
     *     to not take the current translation parameters into account if true.
     * 
     * Returns:
     * {Boolean} Whether or not the 'x' and 'y' coordinates are in the  
     *           valid range.
     */ 
    inValidRange: function(x, y, xyOnly) {
        var left = x + (xyOnly ? 0 : this.translationParameters.x);
        var top = y + (xyOnly ? 0 : this.translationParameters.y);
        return (left >= -this.MAX_PIXEL && left <= this.MAX_PIXEL &&
                top >= -this.MAX_PIXEL && top <= this.MAX_PIXEL);
    },

    /**
     * Method: setExtent
     * 
     * Parameters:
     * extent - {<OpenLayers.Bounds>}
     * resolutionChanged - {Boolean}
     * 
     * Returns:
     * {Boolean} true to notify the layer that the new extent does not exceed
     *     the coordinate range, and the features will not need to be redrawn.
     *     False otherwise.
     */
    setExtent: function(extent, resolutionChanged) {
        OpenLayers.Renderer.Elements.prototype.setExtent.apply(this, 
                                                               arguments);
        
        var resolution = this.getResolution();
        var left = -extent.left / resolution;
        var top = extent.top / resolution;

        // If the resolution has changed, start over changing the corner, because
        // the features will redraw.
        if (resolutionChanged) {
            this.left = left;
            this.top = top;
            // Set the viewbox
            var extentString = "0 0 " + this.size.w + " " + this.size.h;

            this.rendererRoot.setAttributeNS(null, "viewBox", extentString);
            this.translate(0, 0);
            return true;
        } else {
            var inRange = this.translate(left - this.left, top - this.top);
            if (!inRange) {
                // recenter the coordinate system
                this.setExtent(extent, true);
            }
            return inRange;
        }
    },
    
    /**
     * Method: translate
     * Transforms the SVG coordinate system
     * 
     * Parameters:
     * x - {Float}
     * y - {Float}
     * 
     * Returns:
     * {Boolean} true if the translation parameters are in the valid coordinates
     *     range, false otherwise.
     */
    translate: function(x, y) {
        if (!this.inValidRange(x, y, true)) {
            return false;
        } else {
            var transformString = "";
            if (x || y) {
                transformString = "translate(" + x + "," + y + ")";
            }
            this.root.setAttributeNS(null, "transform", transformString);
            this.translationParameters = {x: x, y: y};
            return true;
        }
    },

    /**
     * Method: setSize
     * Sets the size of the drawing surface.
     * 
     * Parameters:
     * size - {<OpenLayers.Size>} The size of the drawing surface
     */
    setSize: function(size) {
        OpenLayers.Renderer.prototype.setSize.apply(this, arguments);
        
        this.rendererRoot.setAttributeNS(null, "width", this.size.w);
        this.rendererRoot.setAttributeNS(null, "height", this.size.h);
    },

    /** 
     * Method: getNodeType 
     * 
     * Parameters:
     * geometry - {<OpenLayers.Geometry>}
     * style - {Object}
     * 
     * Returns:
     * {String} The corresponding node type for the specified geometry
     */
    getNodeType: function(geometry, style) {
        var nodeType = null;
        switch (geometry.CLASS_NAME) {
            case "OpenLayers.Geometry.Point":
                if (style.externalGraphic) {
                    nodeType = "image";
                } else if (this.isComplexSymbol(style.graphicName)) {
                    nodeType = this.supportUse === false ? "svg" : "use";
                } else {
                    nodeType = "circle";
                }
                break;
            case "OpenLayers.Geometry.Rectangle":
                nodeType = "rect";
                break;
            case "OpenLayers.Geometry.LineString":
                nodeType = "polyline";
                break;
            case "OpenLayers.Geometry.LinearRing":
                nodeType = "polygon";
                break;
            case "OpenLayers.Geometry.Polygon":
            case "OpenLayers.Geometry.Curve":
            case "OpenLayers.Geometry.Surface":
                nodeType = "path";
                break;
            default:
                break;
        }
        return nodeType;
    },

    /** 
     * Method: setStyle
     * Use to set all the style attributes to a SVG node.
     * 
     * Takes care to adjust stroke width and point radius to be
     * resolution-relative
     *
     * Parameters:
     * node - {SVGDomElement} An SVG element to decorate
     * style - {Object}
     * options - {Object} Currently supported options include 
     *                              'isFilled' {Boolean} and
     *                              'isStroked' {Boolean}
     */
    setStyle: function(node, style, options) {
        style = style  || node._style;
        options = options || node._options;
        var r = parseFloat(node.getAttributeNS(null, "r"));
        var widthFactor = 1;
        var pos;
        if (node._geometryClass == "OpenLayers.Geometry.Point" && r) {
            node.style.visibility = "";
            if (style.graphic === false) {
                node.style.visibility = "hidden";
            } else if (style.externalGraphic) {
                pos = this.getPosition(node);
                
        		if (style.graphicTitle) {
                    node.setAttributeNS(null, "title", style.graphicTitle);
                }
                if (style.graphicWidth && style.graphicHeight) {
                  node.setAttributeNS(null, "preserveAspectRatio", "none");
                }
                var width = style.graphicWidth || style.graphicHeight;
                var height = style.graphicHeight || style.graphicWidth;
                width = width ? width : style.pointRadius*2;
                height = height ? height : style.pointRadius*2;
                var xOffset = (style.graphicXOffset != undefined) ?
                    style.graphicXOffset : -(0.5 * width);
                var yOffset = (style.graphicYOffset != undefined) ?
                    style.graphicYOffset : -(0.5 * height);

                var opacity = style.graphicOpacity || style.fillOpacity;
                
                node.setAttributeNS(null, "x", (pos.x + xOffset).toFixed());
                node.setAttributeNS(null, "y", (pos.y + yOffset).toFixed());
                node.setAttributeNS(null, "width", width);
                node.setAttributeNS(null, "height", height);
                node.setAttributeNS(this.xlinkns, "href", style.externalGraphic);
                node.setAttributeNS(null, "style", "opacity: "+opacity);
            } else if (this.isComplexSymbol(style.graphicName)) {
                // the symbol viewBox is three times as large as the symbol
                var offset = style.pointRadius * 3;
                var size = offset * 2;
                var id = this.importSymbol(style.graphicName);
                pos = this.getPosition(node);
                widthFactor = this.symbolMetrics[id][0] * 3 / size;
                
                // remove the node from the dom before we modify it. This
                // prevents various rendering issues in Safari and FF
                var parent = node.parentNode;
                var nextSibling = node.nextSibling;
                if(parent) {
                    parent.removeChild(node);
                }
                
                if(this.supportUse === false) {
                    // workaround for webkit versions that cannot do defs/use
                    // (see https://bugs.webkit.org/show_bug.cgi?id=33322):
                    // copy the symbol instead of referencing it
                    var src = document.getElementById(id);
                    node.firstChild && node.removeChild(node.firstChild);
                    node.appendChild(src.firstChild.cloneNode(true));
                    node.setAttributeNS(null, "viewBox", src.getAttributeNS(null, "viewBox"));
                } else {
                    node.setAttributeNS(this.xlinkns, "href", "#" + id);
                }
                node.setAttributeNS(null, "width", size);
                node.setAttributeNS(null, "height", size);
                node.setAttributeNS(null, "x", pos.x - offset);
                node.setAttributeNS(null, "y", pos.y - offset);
                
                // now that the node has all its new properties, insert it
                // back into the dom where it was
                if(nextSibling) {
                    parent.insertBefore(node, nextSibling);
                } else if(parent) {
                    parent.appendChild(node);
                }
            } else {
                node.setAttributeNS(null, "r", style.pointRadius);
            }

            var rotation = style.rotation;
            if ((rotation !== undefined || node._rotation !== undefined) && pos) {
                node._rotation = rotation;
                rotation |= 0;
                if(node.nodeName !== "svg") {
                    node.setAttributeNS(null, "transform",
                        "rotate(" + rotation + " " + pos.x + " " +
                        pos.y + ")");
                } else {
                     var metrics = this.symbolMetrics[id]
                     node.firstChild.setAttributeNS(null, "transform",
                     "rotate(" + style.rotation + " " + metrics[1] +
                         " " +  metrics[2] + ")");
                }
            }
        }
        
        if (options.isFilled) {
            node.setAttributeNS(null, "fill", style.fillColor);
            node.setAttributeNS(null, "fill-opacity", style.fillOpacity);
        } else {
            node.setAttributeNS(null, "fill", "none");
        }

        if (options.isStroked) {
            node.setAttributeNS(null, "stroke", style.strokeColor);
            node.setAttributeNS(null, "stroke-opacity", style.strokeOpacity);
            node.setAttributeNS(null, "stroke-width", style.strokeWidth * widthFactor);
            node.setAttributeNS(null, "stroke-linecap", style.strokeLinecap);
            // Hard-coded linejoin for now, to make it look the same as in VML.
            // There is no strokeLinejoin property yet for symbolizers.
            node.setAttributeNS(null, "stroke-linejoin", "round");
            node.setAttributeNS(null, "stroke-dasharray", this.dashStyle(style,
                widthFactor));
        } else {
            node.setAttributeNS(null, "stroke", "none");
        }
        
        if (style.pointerEvents) {
            node.setAttributeNS(null, "pointer-events", style.pointerEvents);
        }
		        
        if (style.cursor != null) {
            node.setAttributeNS(null, "cursor", style.cursor);
        }
        
        return node;
    },

    /** 
     * Method: dashStyle
     * 
     * Parameters:
     * style - {Object}
     * widthFactor - {Number}
     * 
     * Returns:
     * {String} A SVG compliant 'stroke-dasharray' value
     */
    dashStyle: function(style, widthFactor) {
        var w = style.strokeWidth * widthFactor;
        var str = style.strokeDashstyle;
        switch (str) {
            case 'solid':
                return 'none';
            case 'dot':
                return [1, 4 * w].join();
            case 'dash':
                return [4 * w, 4 * w].join();
            case 'dashdot':
                return [4 * w, 4 * w, 1, 4 * w].join();
            case 'longdash':
                return [8 * w, 4 * w].join();
            case 'longdashdot':
                return [8 * w, 4 * w, 1, 4 * w].join();
            default:
                return OpenLayers.String.trim(str).replace(/\s+/g, ",");
        }
    },
    
    /** 
     * Method: createNode
     * 
     * Parameters:
     * type - {String} Kind of node to draw
     * id - {String} Id for node
     * 
     * Returns:
     * {DOMElement} A new node of the given type and id
     */
    createNode: function(type, id) {
        var node = document.createElementNS(this.xmlns, type);
        if (id) {
            node.setAttributeNS(null, "id", id);
        }
        return node;    
    },
    
    /** 
     * Method: nodeTypeCompare
     * 
     * Parameters:
     * node - {SVGDomElement} An SVG element
     * type - {String} Kind of node
     * 
     * Returns:
     * {Boolean} Whether or not the specified node is of the specified type
     */
    nodeTypeCompare: function(node, type) {
        return (type == node.nodeName);
    },
   
    /**
     * Method: createRenderRoot
     * 
     * Returns:
     * {DOMElement} The specific render engine's root element
     */
    createRenderRoot: function() {
        return this.nodeFactory(this.container.id + "_svgRoot", "svg");
    },

    /**
     * Method: createRoot
     * 
     * Parameter:
     * suffix - {String} suffix to append to the id
     * 
     * Returns:
     * {DOMElement}
     */
    createRoot: function(suffix) {
        return this.nodeFactory(this.container.id + suffix, "g");
    },

    /**
     * Method: createDefs
     *
     * Returns:
     * {DOMElement} The element to which we'll add the symbol definitions
     */
    createDefs: function() {
        var defs = this.nodeFactory(this.container.id + "_defs", "defs");
        this.rendererRoot.appendChild(defs);
        return defs;
    },

    /**************************************
     *                                    *
     *     GEOMETRY DRAWING FUNCTIONS     *
     *                                    *
     **************************************/

    /**
     * Method: drawPoint
     * This method is only called by the renderer itself.
     * 
     * Parameters: 
     * node - {DOMElement}
     * geometry - {<OpenLayers.Geometry>}
     * 
     * Returns:
     * {DOMElement} or false if the renderer could not draw the point
     */ 
    drawPoint: function(node, geometry) {
        return this.drawCircle(node, geometry, 1);
    },

    /**
     * Method: drawCircle
     * This method is only called by the renderer itself.
     * 
     * Parameters: 
     * node - {DOMElement}
     * geometry - {<OpenLayers.Geometry>}
     * radius - {Float}
     * 
     * Returns:
     * {DOMElement} or false if the renderer could not draw the circle
     */
    drawCircle: function(node, geometry, radius) {
        var resolution = this.getResolution();
        var x = (geometry.x / resolution + this.left);
        var y = (this.top - geometry.y / resolution);

        if (this.inValidRange(x, y)) { 
            node.setAttributeNS(null, "cx", x);
            node.setAttributeNS(null, "cy", y);
            node.setAttributeNS(null, "r", radius);
            return node;
        } else {
            return false;
        }    
            
    },
    
    /**
     * Method: drawLineString
     * This method is only called by the renderer itself.
     * 
     * Parameters: 
     * node - {DOMElement}
     * geometry - {<OpenLayers.Geometry>}
     * 
     * Returns:
     * {DOMElement} or null if the renderer could not draw all components of
     *     the linestring, or false if nothing could be drawn
     */ 
    drawLineString: function(node, geometry) {
        var componentsResult = this.getComponentsString(geometry.components);
        if (componentsResult.path) {
            node.setAttributeNS(null, "points", componentsResult.path);
            return (componentsResult.complete ? node : null);  
        } else {
            return false;
        }
    },
    
    /**
     * Method: drawLinearRing
     * This method is only called by the renderer itself.
     * 
     * Parameters: 
     * node - {DOMElement}
     * geometry - {<OpenLayers.Geometry>}
     * 
     * Returns:
     * {DOMElement} or null if the renderer could not draw all components
     *     of the linear ring, or false if nothing could be drawn
     */ 
    drawLinearRing: function(node, geometry) {
        var componentsResult = this.getComponentsString(geometry.components);
        if (componentsResult.path) {
            node.setAttributeNS(null, "points", componentsResult.path);
            return (componentsResult.complete ? node : null);  
        } else {
            return false;
        }
    },
    
    /**
     * Method: drawPolygon
     * This method is only called by the renderer itself.
     * 
     * Parameters: 
     * node - {DOMElement}
     * geometry - {<OpenLayers.Geometry>}
     * 
     * Returns:
     * {DOMElement} or null if the renderer could not draw all components
     *     of the polygon, or false if nothing could be drawn
     */ 
    drawPolygon: function(node, geometry) {
        var d = "";
        var draw = true;
        var complete = true;
        var linearRingResult, path;
        for (var j=0, len=geometry.components.length; j<len; j++) {
            d += " M";
            linearRingResult = this.getComponentsString(
                geometry.components[j].components, " ");
            path = linearRingResult.path;
            if (path) {
                d += " " + path;
                complete = linearRingResult.complete && complete;
            } else {
                draw = false;
            }
        }
        d += " z";
        if (draw) {
            node.setAttributeNS(null, "d", d);
            node.setAttributeNS(null, "fill-rule", "evenodd");
            return complete ? node : null;
        } else {
            return false;
        }    
    },
    
    /**
     * Method: drawRectangle
     * This method is only called by the renderer itself.
     * 
     * Parameters: 
     * node - {DOMElement}
     * geometry - {<OpenLayers.Geometry>}
     * 
     * Returns:
     * {DOMElement} or false if the renderer could not draw the rectangle
     */ 
    drawRectangle: function(node, geometry) {
        var resolution = this.getResolution();
        var x = (geometry.x / resolution + this.left);
        var y = (this.top - geometry.y / resolution);

        if (this.inValidRange(x, y)) { 
            node.setAttributeNS(null, "x", x);
            node.setAttributeNS(null, "y", y);
            node.setAttributeNS(null, "width", geometry.width / resolution);
            node.setAttributeNS(null, "height", geometry.height / resolution);
            return node;
        } else {
            return false;
        }
    },
    
    /**
     * Method: drawSurface
     * This method is only called by the renderer itself.
     * 
     * Parameters: 
     * node - {DOMElement}
     * geometry - {<OpenLayers.Geometry>}
     * 
     * Returns:
     * {DOMElement} or false if the renderer could not draw the surface
     */ 
    drawSurface: function(node, geometry) {

        // create the svg path string representation
        var d = null;
        var draw = true;
        for (var i=0, len=geometry.components.length; i<len; i++) {
            if ((i%3) == 0 && (i/3) == 0) {
                var component = this.getShortString(geometry.components[i]);
                if (!component) { draw = false; }
                d = "M " + component;
            } else if ((i%3) == 1) {
                var component = this.getShortString(geometry.components[i]);
                if (!component) { draw = false; }
                d += " C " + component;
            } else {
                var component = this.getShortString(geometry.components[i]);
                if (!component) { draw = false; }
                d += " " + component;
            }
        }
        d += " Z";
        if (draw) {
            node.setAttributeNS(null, "d", d);
            return node;
        } else {
            return false;
        }    
    },
    
    /**
     * Method: drawText
     * This method is only called by the renderer itself.
     * 
     * Parameters: 
     * featureId - {String}
     * style -
     * location - {<OpenLayers.Geometry.Point>}
     */
    drawText: function(featureId, style, location) {
        var resolution = this.getResolution();
        
        var x = (location.x / resolution + this.left);
        var y = (location.y / resolution - this.top);
        
        var label = this.nodeFactory(featureId + this.LABEL_ID_SUFFIX, "text");
        var tspan = this.nodeFactory(featureId + this.LABEL_ID_SUFFIX + "_tspan", "tspan");

        label.setAttributeNS(null, "x", x);
        label.setAttributeNS(null, "y", -y);
        
        if (style.fontColor) {
            label.setAttributeNS(null, "fill", style.fontColor);
        }
        if (style.fontOpacity) {
            label.setAttributeNS(null, "opacity", style.fontOpacity);
        }
        if (style.fontFamily) {
            label.setAttributeNS(null, "font-family", style.fontFamily);
        }
        if (style.fontSize) {
            label.setAttributeNS(null, "font-size", style.fontSize);
        }
        if (style.fontWeight) {
            label.setAttributeNS(null, "font-weight", style.fontWeight);
        }
        if(style.labelSelect === true) {
            label.setAttributeNS(null, "pointer-events", "visible");
            label._featureId = featureId;
            tspan._featureId = featureId;
            tspan._geometry = location;
            tspan._geometryClass = location.CLASS_NAME;
        } else {
            label.setAttributeNS(null, "pointer-events", "none");
        }
        var align = style.labelAlign || "cm";
        label.setAttributeNS(null, "text-anchor",
            OpenLayers.Renderer.SVG.LABEL_ALIGN[align[0]] || "middle");

        if (this.isGecko) {
            label.setAttributeNS(null, "dominant-baseline",
                OpenLayers.Renderer.SVG.LABEL_ALIGN[align[1]] || "central");
        } else {
            tspan.setAttributeNS(null, "baseline-shift",
                OpenLayers.Renderer.SVG.LABEL_VSHIFT[align[1]] || "-35%");
        }

        tspan.textContent = style.label;
        
        if(!label.parentNode) {
            label.appendChild(tspan);
            this.textRoot.appendChild(label);
        }   
    },
    
    /** 
     * Method: getComponentString
     * 
     * Parameters:
     * components - {Array(<OpenLayers.Geometry.Point>)} Array of points
     * separator - {String} character between coordinate pairs. Defaults to ","
     * 
     * Returns:
     * {Object} hash with properties "path" (the string created from the
     *     components and "complete" (false if the renderer was unable to
     *     draw all components)
     */
    getComponentsString: function(components, separator) {
        var renderCmp = [];
        var complete = true;
        var len = components.length;
        var strings = [];
        var str, component, j;
        for(var i=0; i<len; i++) {
            component = components[i];
            renderCmp.push(component);
            str = this.getShortString(component);
            if (str) {
                strings.push(str);
            } else {
                // The current component is outside the valid range. Let's
                // see if the previous or next component is inside the range.
                // If so, add the coordinate of the intersection with the
                // valid range bounds.
                if (i > 0) {
                    if (this.getShortString(components[i - 1])) {
                        strings.push(this.clipLine(components[i],
                            components[i-1]));
                    }
                }
                if (i < len - 1) {
                    if (this.getShortString(components[i + 1])) {
                        strings.push(this.clipLine(components[i],
                            components[i+1]));
                    }
                }
                complete = false;
            }
        }

        return {
            path: strings.join(separator || ","),
            complete: complete
        };
    },
    
    /**
     * Method: clipLine
     * Given two points (one inside the valid range, and one outside),
     * clips the line betweeen the two points so that the new points are both
     * inside the valid range.
     * 
     * Parameters:
     * badComponent - {<OpenLayers.Geometry.Point>)} original geometry of the
     *     invalid point
     * goodComponent - {<OpenLayers.Geometry.Point>)} original geometry of the
     *     valid point
     * Returns
     * {String} the SVG coordinate pair of the clipped point (like
     *     getShortString), or an empty string if both passed componets are at
     *     the same point.
     */
    clipLine: function(badComponent, goodComponent) {
        if (goodComponent.equals(badComponent)) {
            return "";
        }
        var resolution = this.getResolution();
        var maxX = this.MAX_PIXEL - this.translationParameters.x;
        var maxY = this.MAX_PIXEL - this.translationParameters.y;
        var x1 = goodComponent.x / resolution + this.left;
        var y1 = this.top - goodComponent.y / resolution;
        var x2 = badComponent.x / resolution + this.left;
        var y2 = this.top - badComponent.y / resolution;
        var k;
        if (x2 < -maxX || x2 > maxX) {
            k = (y2 - y1) / (x2 - x1);
            x2 = x2 < 0 ? -maxX : maxX;
            y2 = y1 + (x2 - x1) * k;
        }
        if (y2 < -maxY || y2 > maxY) {
            k = (x2 - x1) / (y2 - y1);
            y2 = y2 < 0 ? -maxY : maxY;
            x2 = x1 + (y2 - y1) * k;
        }
        return x2 + "," + y2;
    },

    /** 
     * Method: getShortString
     * 
     * Parameters:
     * point - {<OpenLayers.Geometry.Point>}
     * 
     * Returns:
     * {String} or false if point is outside the valid range
     */
    getShortString: function(point) {
        var resolution = this.getResolution();
        var x = (point.x / resolution + this.left);
        var y = (this.top - point.y / resolution);

        if (this.inValidRange(x, y)) { 
            return x + "," + y;
        } else {
            return false;
        }
    },
    
    /**
     * Method: getPosition
     * Finds the position of an svg node.
     * 
     * Parameters:
     * node - {DOMElement}
     * 
     * Returns:
     * {Object} hash with x and y properties, representing the coordinates
     *     within the svg coordinate system
     */
    getPosition: function(node) {
        return({
            x: parseFloat(node.getAttributeNS(null, "cx")),
            y: parseFloat(node.getAttributeNS(null, "cy"))
        });
    },

    /**
     * Method: importSymbol
     * add a new symbol definition from the rendererer's symbol hash
     * 
     * Parameters:
     * graphicName - {String} name of the symbol to import
     * 
     * Returns:
     * {String} - id of the imported symbol
     */      
    importSymbol: function (graphicName)  {
        if (!this.defs) {
            // create svg defs tag
            this.defs = this.createDefs();
        }
        var id = this.container.id + "-" + graphicName;
        
        // check if symbol already exists in the defs
        if (document.getElementById(id) != null) {
            return id;
        }
        
        var symbol = OpenLayers.Renderer.symbol[graphicName];
        if (!symbol) {
            throw new Error(graphicName + ' is not a valid symbol name');
            return;
        }

        var symbolNode = this.nodeFactory(id, "symbol");
        var node = this.nodeFactory(null, "polygon");
        symbolNode.appendChild(node);
        var symbolExtent = new OpenLayers.Bounds(
                                    Number.MAX_VALUE, Number.MAX_VALUE, 0, 0);

        var points = "";
        var x,y;
        for (var i=0; i<symbol.length; i=i+2) {
            x = symbol[i];
            y = symbol[i+1];
            symbolExtent.left = Math.min(symbolExtent.left, x);
            symbolExtent.bottom = Math.min(symbolExtent.bottom, y);
            symbolExtent.right = Math.max(symbolExtent.right, x);
            symbolExtent.top = Math.max(symbolExtent.top, y);
            points += " " + x + "," + y;
        }
        
        node.setAttributeNS(null, "points", points);
        
        var width = symbolExtent.getWidth();
        var height = symbolExtent.getHeight();
        // create a viewBox three times as large as the symbol itself,
        // to allow for strokeWidth being displayed correctly at the corners.
        var viewBox = [symbolExtent.left - width,
                        symbolExtent.bottom - height, width * 3, height * 3];
        symbolNode.setAttributeNS(null, "viewBox", viewBox.join(" "));
        this.symbolMetrics[id] = [
            Math.max(width, height),
            symbolExtent.getCenterLonLat().lon,
            symbolExtent.getCenterLonLat().lat
        ];
        
        this.defs.appendChild(symbolNode);
        return symbolNode.id;
    },
    
    /**
     * Method: getFeatureIdFromEvent
     * 
     * Parameters:
     * evt - {Object} An <OpenLayers.Event> object
     *
     * Returns:
     * {<OpenLayers.Geometry>} A geometry from an event that 
     *     happened on a layer.
     */
    getFeatureIdFromEvent: function(evt) {
        var featureId = OpenLayers.Renderer.Elements.prototype.getFeatureIdFromEvent.apply(this, arguments);
        if(this.supportUse === false && !featureId) {
            var target = evt.target;
            featureId = target.parentNode && target != this.rendererRoot &&
                target.parentNode._featureId;
        }
        return featureId;
    },

    CLASS_NAME: "OpenLayers.Renderer.SVG"
});

/**
 * Constant: OpenLayers.Renderer.SVG.LABEL_ALIGN
 * {Object}
 */
OpenLayers.Renderer.SVG.LABEL_ALIGN = {
    "l": "start",
    "r": "end",
    "b": "bottom",
    "t": "hanging"
};

/**
 * Constant: OpenLayers.Renderer.SVG.LABEL_VSHIFT
 * {Object}
 */
OpenLayers.Renderer.SVG.LABEL_VSHIFT = {
    // according to
    // http://www.w3.org/Graphics/SVG/Test/20061213/htmlObjectHarness/full-text-align-02-b.html
    // a baseline-shift of -70% shifts the text exactly from the
    // bottom to the top of the baseline, so -35% moves the text to
    // the center of the baseline.
    "t": "-70%",
    "b": "0"    
};
/* ======================================================================
    OpenLayers/Renderer/VML.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */

/**
 * @requires OpenLayers/Renderer/Elements.js
 */

/**
 * Class: OpenLayers.Renderer.VML
 * Render vector features in browsers with VML capability.  Construct a new
 * VML renderer with the <OpenLayers.Renderer.VML> constructor.
 * 
 * Note that for all calculations in this class, we use (num | 0) to truncate a 
 * float value to an integer. This is done because it seems that VML doesn't 
 * support float values.
 *
 * Inherits from:
 *  - <OpenLayers.Renderer.Elements>
 */
OpenLayers.Renderer.VML = OpenLayers.Class(OpenLayers.Renderer.Elements, {

    /**
     * Property: xmlns
     * {String} XML Namespace URN
     */
    xmlns: "urn:schemas-microsoft-com:vml",
    
    /**
     * Property: symbolCache
     * {DOMElement} node holding symbols. This hash is keyed by symbol name,
     *     and each value is a hash with a "path" and an "extent" property.
     */
    symbolCache: {},

    /**
     * Property: offset
     * {Object} Hash with "x" and "y" properties
     */
    offset: null,
    
    /**
     * Constructor: OpenLayers.Renderer.VML
     * Create a new VML renderer.
     *
     * Parameters:
     * containerID - {String} The id for the element that contains the renderer
     */
    initialize: function(containerID) {
        if (!this.supported()) { 
            return; 
        }
        if (!document.namespaces.olv) {
            document.namespaces.add("olv", this.xmlns);
            var style = document.createStyleSheet();
            var shapes = ['shape','rect', 'oval', 'fill', 'stroke', 'imagedata', 'group','textbox']; 
            for (var i = 0, len = shapes.length; i < len; i++) {

                style.addRule('olv\\:' + shapes[i], "behavior: url(#default#VML); " +
                              "position: absolute; display: inline-block;");
            }                  
        }
        
        OpenLayers.Renderer.Elements.prototype.initialize.apply(this, 
                                                                arguments);
    },

    /**
     * APIMethod: destroy
     * Deconstruct the renderer.
     */
    destroy: function() {
        OpenLayers.Renderer.Elements.prototype.destroy.apply(this, arguments);
    },

    /**
     * APIMethod: supported
     * Determine whether a browser supports this renderer.
     *
     * Returns:
     * {Boolean} The browser supports the VML renderer
     */
    supported: function() {
        return !!(document.namespaces);
    },    

    /**
     * Method: setExtent
     * Set the renderer's extent
     *
     * Parameters:
     * extent - {<OpenLayers.Bounds>}
     * resolutionChanged - {Boolean}
     * 
     * Returns:
     * {Boolean} true to notify the layer that the new extent does not exceed
     *     the coordinate range, and the features will not need to be redrawn.
     */
    setExtent: function(extent, resolutionChanged) {
        OpenLayers.Renderer.Elements.prototype.setExtent.apply(this, 
                                                               arguments);
        var resolution = this.getResolution();
    
        var left = (extent.left/resolution) | 0;
        var top = (extent.top/resolution - this.size.h) | 0;
        if (resolutionChanged || !this.offset) {
            this.offset = {x: left, y: top};
            left = 0;
            top = 0;
        } else {
            left = left - this.offset.x;
            top = top - this.offset.y;
        }

        
        var org = left + " " + top;
        this.root.coordorigin = org;
        var roots = [this.root, this.vectorRoot, this.textRoot];
        var root;
        for(var i=0, len=roots.length; i<len; ++i) {
            root = roots[i];

            var size = this.size.w + " " + this.size.h;
            root.coordsize = size;
            
        }
        // flip the VML display Y axis upside down so it 
        // matches the display Y axis of the map
        this.root.style.flip = "y";
        
        return true;
    },


    /**
     * Method: setSize
     * Set the size of the drawing surface
     *
     * Parameters:
     * size - {<OpenLayers.Size>} the size of the drawing surface
     */
    setSize: function(size) {
        OpenLayers.Renderer.prototype.setSize.apply(this, arguments);
        
        // setting width and height on all roots to avoid flicker which we
        // would get with 100% width and height on child roots
        var roots = [
            this.rendererRoot,
            this.root,
            this.vectorRoot,
            this.textRoot
        ];
        var w = this.size.w + "px";
        var h = this.size.h + "px";
        var root;
        for(var i=0, len=roots.length; i<len; ++i) {
            root = roots[i];
            root.style.width = w;
            root.style.height = h;
        }
    },

    /**
     * Method: getNodeType
     * Get the node type for a geometry and style
     *
     * Parameters:
     * geometry - {<OpenLayers.Geometry>}
     * style - {Object}
     *
     * Returns:
     * {String} The corresponding node type for the specified geometry
     */
    getNodeType: function(geometry, style) {
        var nodeType = null;
        switch (geometry.CLASS_NAME) {
            case "OpenLayers.Geometry.Point":
                if (style.externalGraphic) {
                    nodeType = "olv:rect";
                } else if (this.isComplexSymbol(style.graphicName)) {
                    nodeType = "olv:shape";
                } else {
                    nodeType = "olv:oval";
                }
                break;
            case "OpenLayers.Geometry.Rectangle":
                nodeType = "olv:rect";
                break;
            case "OpenLayers.Geometry.LineString":
            case "OpenLayers.Geometry.LinearRing":
            case "OpenLayers.Geometry.Polygon":
            case "OpenLayers.Geometry.Curve":
            case "OpenLayers.Geometry.Surface":
                nodeType = "olv:shape";
                break;
            default:
                break;
        }
        return nodeType;
    },

    /**
     * Method: setStyle
     * Use to set all the style attributes to a VML node.
     *
     * Parameters:
     * node - {DOMElement} An VML element to decorate
     * style - {Object}
     * options - {Object} Currently supported options include 
     *                              'isFilled' {Boolean} and
     *                              'isStroked' {Boolean}
     * geometry - {<OpenLayers.Geometry>}
     */
    setStyle: function(node, style, options, geometry) {
        style = style  || node._style;
        options = options || node._options;
        var widthFactor = 1;
        var fillColor = style.fillColor;

        if (node._geometryClass === "OpenLayers.Geometry.Point") {
            if (style.externalGraphic) {
        		if (style.graphicTitle) {
                    node.title=style.graphicTitle;
                } 
                var width = style.graphicWidth || style.graphicHeight;
                var height = style.graphicHeight || style.graphicWidth;
                width = width ? width : style.pointRadius*2;
                height = height ? height : style.pointRadius*2;

                var resolution = this.getResolution();
                var xOffset = (style.graphicXOffset != undefined) ?
                    style.graphicXOffset : -(0.5 * width);
                var yOffset = (style.graphicYOffset != undefined) ?
                    style.graphicYOffset : -(0.5 * height);
                
                node.style.left = (((geometry.x/resolution - this.offset.x)+xOffset) | 0) + "px";
                node.style.top = (((geometry.y/resolution - this.offset.y)-(yOffset+height)) | 0) + "px";
                node.style.width = width + "px";
                node.style.height = height + "px";
                node.style.flip = "y";
                
                // modify fillColor and options for stroke styling below
                fillColor = "none";
                options.isStroked = false;
            } else if (this.isComplexSymbol(style.graphicName)) {
                var cache = this.importSymbol(style.graphicName);
                node.path = cache.path;
                node.coordorigin = cache.left + "," + cache.bottom;
                var size = cache.size;
                node.coordsize = size + "," + size;        
                this.drawCircle(node, geometry, style.pointRadius);
                node.style.flip = "y";
            } else {
                this.drawCircle(node, geometry, style.pointRadius);
            }
        }

        // fill 
        if (options.isFilled) { 
            node.fillcolor = fillColor; 
        } else { 
            node.filled = "false"; 
        }
        var fills = node.getElementsByTagName("fill");
        var fill = (fills.length == 0) ? null : fills[0];
        if (!options.isFilled) {
            if (fill) {
                node.removeChild(fill);
            }
        } else {
            if (!fill) {
                fill = this.createNode('olv:fill', node.id + "_fill");
            }
            fill.opacity = style.fillOpacity;

            if (node._geometryClass === "OpenLayers.Geometry.Point" &&
                    style.externalGraphic) {

                // override fillOpacity
                if (style.graphicOpacity) {
                    fill.opacity = style.graphicOpacity;
                }
                
                fill.src = style.externalGraphic;
                fill.type = "frame";
                
                if (!(style.graphicWidth && style.graphicHeight)) {
                  fill.aspect = "atmost";
                }                
            }
            if (fill.parentNode != node) {
                node.appendChild(fill);
            }
        }

        // additional rendering for rotated graphics or symbols
        var rotation = style.rotation;
        if (rotation !== node._rotation) {
            node._rotation = rotation;
            if (style.externalGraphic) {
                this.graphicRotate(node, xOffset, yOffset, style);
                // make the fill fully transparent, because we now have
                // the graphic as imagedata element. We cannot just remove
                // the fill, because this is part of the hack described
                // in graphicRotate
                fill.opacity = 0;
            } else if(node._geometryClass === "OpenLayers.Geometry.Point") {
                node.style.rotation = rotation || 0;
            }
        }

        // stroke 
        if (options.isStroked) { 
            node.strokecolor = style.strokeColor; 
            node.strokeweight = style.strokeWidth + "px"; 
        } else { 
            node.stroked = false; 
        }
        var strokes = node.getElementsByTagName("stroke");
        var stroke = (strokes.length == 0) ? null : strokes[0];
        if (!options.isStroked) {
            if (stroke) {
                node.removeChild(stroke);
            }
        } else {
            if (!stroke) {
                stroke = this.createNode('olv:stroke', node.id + "_stroke");
                node.appendChild(stroke);
            }
            stroke.opacity = style.strokeOpacity;
            stroke.endcap = !style.strokeLinecap || style.strokeLinecap == 'butt' ? 'flat' : style.strokeLinecap;
            stroke.dashstyle = this.dashStyle(style);
        }
        
        if (style.cursor != "inherit" && style.cursor != null) {
            node.style.cursor = style.cursor;
        }
        return node;
    },

    /**
     * Method: graphicRotate
     * If a point is to be styled with externalGraphic and rotation, VML fills
     * cannot be used to display the graphic, because rotation of graphic
     * fills is not supported by the VML implementation of Internet Explorer.
     * This method creates a olv:imagedata element inside the VML node,
     * DXImageTransform.Matrix and BasicImage filters for rotation and
     * opacity, and a 3-step hack to remove rendering artefacts from the
     * graphic and preserve the ability of graphics to trigger events.
     * Finally, OpenLayers methods are used to determine the correct
     * insertion point of the rotated image, because DXImageTransform.Matrix
     * does the rotation without the ability to specify a rotation center
     * point.
     * 
     * Parameters:
     * node    - {DOMElement}
     * xOffset - {Number} rotation center relative to image, x coordinate
     * yOffset - {Number} rotation center relative to image, y coordinate
     * style   - {Object}
     */
    graphicRotate: function(node, xOffset, yOffset, style) {
        var style = style || node._style;
        var options = node._options;
        var rotation = style.rotation || 0;
        
        var aspectRatio, size;
        if (!(style.graphicWidth && style.graphicHeight)) {
            // load the image to determine its size
            var img = new Image();
            img.onreadystatechange = OpenLayers.Function.bind(function() {
                if(img.readyState == "complete" ||
                        img.readyState == "interactive") {
                    aspectRatio = img.width / img.height;
                    size = Math.max(style.pointRadius * 2, 
                        style.graphicWidth || 0,
                        style.graphicHeight || 0);
                    xOffset = xOffset * aspectRatio;
                    style.graphicWidth = size * aspectRatio;
                    style.graphicHeight = size;
                    this.graphicRotate(node, xOffset, yOffset, style);
                }
            }, this);
            img.src = style.externalGraphic;
            
            // will be called again by the onreadystate handler
            return;
        } else {
            size = Math.max(style.graphicWidth, style.graphicHeight);
            aspectRatio = style.graphicWidth / style.graphicHeight;
        }
        
        var width = Math.round(style.graphicWidth || size * aspectRatio);
        var height = Math.round(style.graphicHeight || size);
        node.style.width = width + "px";
        node.style.height = height + "px";
        
        // Three steps are required to remove artefacts for images with
        // transparent backgrounds (resulting from using DXImageTransform
        // filters on svg objects), while preserving awareness for browser
        // events on images:
        // - Use the fill as usual (like for unrotated images) to handle
        //   events
        // - specify an imagedata element with the same src as the fill
        // - style the imagedata element with an AlphaImageLoader filter
        //   with empty src
        var image = document.getElementById(node.id + "_image");
        if (!image) {
            image = this.createNode("olv:imagedata", node.id + "_image");
            node.appendChild(image);
        }
        image.style.width = width + "px";
        image.style.height = height + "px";
        image.src = style.externalGraphic;
        image.style.filter =
            "progid:DXImageTransform.Microsoft.AlphaImageLoader(" + 
            "src='', sizingMethod='scale')";

        var rot = rotation * Math.PI / 180;
        var sintheta = Math.sin(rot);
        var costheta = Math.cos(rot);

        // do the rotation on the image
        var filter =
            "progid:DXImageTransform.Microsoft.Matrix(M11=" + costheta +
            ",M12=" + (-sintheta) + ",M21=" + sintheta + ",M22=" + costheta +
            ",SizingMethod='auto expand')\n";

        // set the opacity (needed for the imagedata)
        var opacity = style.graphicOpacity || style.fillOpacity;
        if (opacity && opacity != 1) {
            filter += 
                "progid:DXImageTransform.Microsoft.BasicImage(opacity=" + 
                opacity+")\n";
        }
        node.style.filter = filter;

        // do the rotation again on a box, so we know the insertion point
        var centerPoint = new OpenLayers.Geometry.Point(-xOffset, -yOffset);
        var imgBox = new OpenLayers.Bounds(0, 0, width, height).toGeometry();
        imgBox.rotate(style.rotation, centerPoint);
        var imgBounds = imgBox.getBounds();

        node.style.left = Math.round(
            parseInt(node.style.left) + imgBounds.left) + "px";
        node.style.top = Math.round(
            parseInt(node.style.top) - imgBounds.bottom) + "px";
    },

    /**
     * Method: postDraw
     * Does some node postprocessing to work around browser issues:
     * - Some versions of Internet Explorer seem to be unable to set fillcolor
     *   and strokecolor to "none" correctly before the fill node is appended
     *   to a visible vml node. This method takes care of that and sets
     *   fillcolor and strokecolor again if needed.
     * - In some cases, a node won't become visible after being drawn. Setting
     *   style.visibility to "visible" works around that.
     * 
     * Parameters:
     * node - {DOMElement}
     */
    postDraw: function(node) {
        node.style.visibility = "visible";
        var fillColor = node._style.fillColor;
        var strokeColor = node._style.strokeColor;
        if (fillColor == "none" &&
                node.fillcolor != fillColor) {
            node.fillcolor = fillColor;
        }
        if (strokeColor == "none" &&
                node.strokecolor != strokeColor) {
            node.strokecolor = strokeColor;
        }
    },


    /**
     * Method: setNodeDimension
     * Get the geometry's bounds, convert it to our vml coordinate system, 
     * then set the node's position, size, and local coordinate system.
     *   
     * Parameters:
     * node - {DOMElement}
     * geometry - {<OpenLayers.Geometry>}
     */
    setNodeDimension: function(node, geometry) {

        var bbox = geometry.getBounds();
        if(bbox) {
            var resolution = this.getResolution();
        
            var scaledBox = 
                new OpenLayers.Bounds((bbox.left/resolution - this.offset.x) | 0,
                                      (bbox.bottom/resolution - this.offset.y) | 0,
                                      (bbox.right/resolution - this.offset.x) | 0,
                                      (bbox.top/resolution - this.offset.y) | 0);
            
            // Set the internal coordinate system to draw the path
            node.style.left = scaledBox.left + "px";
            node.style.top = scaledBox.top + "px";
            node.style.width = scaledBox.getWidth() + "px";
            node.style.height = scaledBox.getHeight() + "px";
    
            node.coordorigin = scaledBox.left + " " + scaledBox.top;
            node.coordsize = scaledBox.getWidth()+ " " + scaledBox.getHeight();
        }
    },
    
    /** 
     * Method: dashStyle
     * 
     * Parameters:
     * style - {Object}
     * 
     * Returns:
     * {String} A VML compliant 'stroke-dasharray' value
     */
    dashStyle: function(style) {
        var dash = style.strokeDashstyle;
        switch (dash) {
            case 'solid':
            case 'dot':
            case 'dash':
            case 'dashdot':
            case 'longdash':
            case 'longdashdot':
                return dash;
            default:
                // very basic guessing of dash style patterns
                var parts = dash.split(/[ ,]/);
                if (parts.length == 2) {
                    if (1*parts[0] >= 2*parts[1]) {
                        return "longdash";
                    }
                    return (parts[0] == 1 || parts[1] == 1) ? "dot" : "dash";
                } else if (parts.length == 4) {
                    return (1*parts[0] >= 2*parts[1]) ? "longdashdot" :
                        "dashdot";
                }
                return "solid";
        }
    },

    /**
     * Method: createNode
     * Create a new node
     *
     * Parameters:
     * type - {String} Kind of node to draw
     * id - {String} Id for node
     *
     * Returns:
     * {DOMElement} A new node of the given type and id
     */
    createNode: function(type, id) {
        var node = document.createElement(type);
        if (id) {
            node.id = id;
        }
        
        // IE hack to make elements unselectable, to prevent 'blue flash'
        // while dragging vectors; #1410
        node.unselectable = 'on';
        node.onselectstart = OpenLayers.Function.False;
        
        return node;    
    },
    
    /**
     * Method: nodeTypeCompare
     * Determine whether a node is of a given type
     *
     * Parameters:
     * node - {DOMElement} An VML element
     * type - {String} Kind of node
     *
     * Returns:
     * {Boolean} Whether or not the specified node is of the specified type
     */
    nodeTypeCompare: function(node, type) {

        //split type
        var subType = type;
        var splitIndex = subType.indexOf(":");
        if (splitIndex != -1) {
            subType = subType.substr(splitIndex+1);
        }

        //split nodeName
        var nodeName = node.nodeName;
        splitIndex = nodeName.indexOf(":");
        if (splitIndex != -1) {
            nodeName = nodeName.substr(splitIndex+1);
        }

        return (subType == nodeName);
    },

    /**
     * Method: createRenderRoot
     * Create the renderer root
     *
     * Returns:
     * {DOMElement} The specific render engine's root element
     */
    createRenderRoot: function() {
        return this.nodeFactory(this.container.id + "_vmlRoot", "div");
    },

    /**
     * Method: createRoot
     * Create the main root element
     * 
     * Parameters:
     * suffix - {String} suffix to append to the id
     *
     * Returns:
     * {DOMElement}
     */
    createRoot: function(suffix) {
        return this.nodeFactory(this.container.id + suffix, "olv:group");
    },
    
    /**************************************
     *                                    *
     *     GEOMETRY DRAWING FUNCTIONS     *
     *                                    *
     **************************************/
    
    /**
     * Method: drawPoint
     * Render a point
     * 
     * Parameters:
     * node - {DOMElement}
     * geometry - {<OpenLayers.Geometry>}
     * 
     * Returns:
     * {DOMElement} or false if the point could not be drawn
     */
    drawPoint: function(node, geometry) {
        return this.drawCircle(node, geometry, 1);
    },

    /**
     * Method: drawCircle
     * Render a circle.
     * Size and Center a circle given geometry (x,y center) and radius
     * 
     * Parameters:
     * node - {DOMElement}
     * geometry - {<OpenLayers.Geometry>}
     * radius - {float}
     * 
     * Returns:
     * {DOMElement} or false if the circle could not ne drawn
     */
    drawCircle: function(node, geometry, radius) {
        if(!isNaN(geometry.x)&& !isNaN(geometry.y)) {
            var resolution = this.getResolution();

            node.style.left = (((geometry.x /resolution - this.offset.x) | 0) - radius) + "px";
            node.style.top = (((geometry.y /resolution - this.offset.y) | 0) - radius) + "px";
    
            var diameter = radius * 2;
            
            node.style.width = diameter + "px";
            node.style.height = diameter + "px";
            return node;
        }
        return false;
    },


    /**
     * Method: drawLineString
     * Render a linestring.
     * 
     * Parameters:
     * node - {DOMElement}
     * geometry - {<OpenLayers.Geometry>}
     * 
     * Returns:
     * {DOMElement}
     */
    drawLineString: function(node, geometry) {
        return this.drawLine(node, geometry, false);
    },

    /**
     * Method: drawLinearRing
     * Render a linearring
     * 
     * Parameters:
     * node - {DOMElement}
     * geometry - {<OpenLayers.Geometry>}
     * 
     * Returns:
     * {DOMElement}
     */
    drawLinearRing: function(node, geometry) {
        return this.drawLine(node, geometry, true);
    },

    /**
     * Method: DrawLine
     * Render a line.
     * 
     * Parameters:
     * node - {DOMElement}
     * geometry - {<OpenLayers.Geometry>}
     * closeLine - {Boolean} Close the line? (make it a ring?)
     * 
     * Returns:
     * {DOMElement}
     */
    drawLine: function(node, geometry, closeLine) {

        this.setNodeDimension(node, geometry);

        var resolution = this.getResolution();
        var numComponents = geometry.components.length;
        var parts = new Array(numComponents);

        var comp, x, y;
        for (var i = 0; i < numComponents; i++) {
            comp = geometry.components[i];
            x = (comp.x/resolution - this.offset.x) | 0;
            y = (comp.y/resolution - this.offset.y) | 0;
            parts[i] = " " + x + "," + y + " l ";
        }
        var end = (closeLine) ? " x e" : " e";
        node.path = "m" + parts.join("") + end;
        return node;
    },

    /**
     * Method: drawPolygon
     * Render a polygon
     * 
     * Parameters:
     * node - {DOMElement}
     * geometry - {<OpenLayers.Geometry>}
     * 
     * Returns:
     * {DOMElement}
     */
    drawPolygon: function(node, geometry) {
        this.setNodeDimension(node, geometry);

        var resolution = this.getResolution();
    
        var path = [];
        var linearRing, i, j, len, ilen, comp, x, y;
        for (j = 0, len=geometry.components.length; j<len; j++) {
            linearRing = geometry.components[j];

            path.push("m");
            for (i=0, ilen=linearRing.components.length; i<ilen; i++) {
                comp = linearRing.components[i];
                x = (comp.x / resolution - this.offset.x) | 0;
                y = (comp.y / resolution - this.offset.y) | 0;
                path.push(" " + x + "," + y);
                if (i==0) {
                    path.push(" l");
                }
            }
            path.push(" x ");
        }
        path.push("e");
        node.path = path.join("");
        return node;
    },

    /**
     * Method: drawRectangle
     * Render a rectangle
     * 
     * Parameters:
     * node - {DOMElement}
     * geometry - {<OpenLayers.Geometry>}
     * 
     * Returns:
     * {DOMElement}
     */
    drawRectangle: function(node, geometry) {
        var resolution = this.getResolution();
    
        node.style.left = ((geometry.x/resolution - this.offset.x) | 0) + "px";
        node.style.top = ((geometry.y/resolution - this.offset.y) | 0) + "px";
        node.style.width = ((geometry.width/resolution) | 0) + "px";
        node.style.height = ((geometry.height/resolution) | 0) + "px";
        
        return node;
    },
    
    /**
     * Method: drawText
     * This method is only called by the renderer itself.
     * 
     * Parameters: 
     * featureId - {String}
     * style -
     * location - {<OpenLayers.Geometry.Point>}
     */
    drawText: function(featureId, style, location) {
        var label = this.nodeFactory(featureId + this.LABEL_ID_SUFFIX, "olv:rect");
        var textbox = this.nodeFactory(featureId + this.LABEL_ID_SUFFIX + "_textbox", "olv:textbox");
        
        var resolution = this.getResolution();
        label.style.left = ((location.x/resolution - this.offset.x) | 0) + "px";
        label.style.top = ((location.y/resolution - this.offset.y) | 0) + "px";
        label.style.flip = "y";

        textbox.innerText = style.label;

        if (style.fontColor) {
            textbox.style.color = style.fontColor;
        }
        if (style.fontOpacity) {
            textbox.style.filter = 'alpha(opacity=' + (style.fontOpacity * 100) + ')';
        }
        if (style.fontFamily) {
            textbox.style.fontFamily = style.fontFamily;
        }
        if (style.fontSize) {
            textbox.style.fontSize = style.fontSize;
        }
        if (style.fontWeight) {
            textbox.style.fontWeight = style.fontWeight;
        }
        if(style.labelSelect === true) {
            label._featureId = featureId;
            textbox._featureId = featureId;
            textbox._geometry = location;
            textbox._geometryClass = location.CLASS_NAME;
        }
        textbox.style.whiteSpace = "nowrap";
        // fun with IE: IE7 in standards compliant mode does not display any
        // text with a left inset of 0. So we set this to 1px and subtract one
        // pixel later when we set label.style.left
        textbox.inset = "1px,0px,0px,0px";

        if(!label.parentNode) {
            label.appendChild(textbox);
            this.textRoot.appendChild(label);
        }

        var align = style.labelAlign || "cm";
        var xshift = textbox.clientWidth *
            (OpenLayers.Renderer.VML.LABEL_SHIFT[align[0] || "c"]);
        var yshift = textbox.clientHeight *
            (OpenLayers.Renderer.VML.LABEL_SHIFT[align[1] || "m"]);
        label.style.left = parseInt(label.style.left)-xshift-1+"px";
        label.style.top = parseInt(label.style.top)+yshift+"px";
        
    },

    /**
     * Method: drawSurface
     * 
     * Parameters:
     * node - {DOMElement}
     * geometry - {<OpenLayers.Geometry>}
     * 
     * Returns:
     * {DOMElement}
     */
    drawSurface: function(node, geometry) {

        this.setNodeDimension(node, geometry);

        var resolution = this.getResolution();
    
        var path = [];
        var comp, x, y;
        for (var i=0, len=geometry.components.length; i<len; i++) {
            comp = geometry.components[i];
            x = (comp.x / resolution - this.offset.x) | 0;
            y = (comp.y / resolution - this.offset.y) | 0;
            if ((i%3)==0 && (i/3)==0) {
                path.push("m");
            } else if ((i%3)==1) {
                path.push(" c");
            }
            path.push(" " + x + "," + y);
        }
        path.push(" x e");

        node.path = path.join("");
        return node;
    },
    
    /**
     * Method: moveRoot
     * moves this renderer's root to a different renderer.
     * 
     * Parameters:
     * renderer - {<OpenLayers.Renderer>} target renderer for the moved root
     * root - {DOMElement} optional root node. To be used when this renderer
     *     holds roots from multiple layers to tell this method which one to
     *     detach
     * 
     * Returns:
     * {Boolean} true if successful, false otherwise
     */
    moveRoot: function(renderer) {
        var layer = this.map.getLayer(renderer.container.id);
        if(layer instanceof OpenLayers.Layer.Vector.RootContainer) {
            layer = this.map.getLayer(this.container.id);
        }
        layer && layer.renderer.clear();
        OpenLayers.Renderer.Elements.prototype.moveRoot.apply(this, arguments);
        layer && layer.redraw();
    },
    
    /**
     * Method: importSymbol
     * add a new symbol definition from the rendererer's symbol hash
     * 
     * Parameters:
     * graphicName - {String} name of the symbol to import
     * 
     * Returns:
     * {Object} - hash of {DOMElement} "symbol" and {Number} "size"
     */      
    importSymbol: function (graphicName)  {
        var id = this.container.id + "-" + graphicName;
        
        // check if symbol already exists in the cache
        var cache = this.symbolCache[id];
        if (cache) {
            return cache;
        }
        
        var symbol = OpenLayers.Renderer.symbol[graphicName];
        if (!symbol) {
            throw new Error(graphicName + ' is not a valid symbol name');
            return;
        }

        var symbolExtent = new OpenLayers.Bounds(
                                    Number.MAX_VALUE, Number.MAX_VALUE, 0, 0);
        
        var pathitems = ["m"];
        for (var i=0; i<symbol.length; i=i+2) {
            var x = symbol[i];
            var y = symbol[i+1];
            symbolExtent.left = Math.min(symbolExtent.left, x);
            symbolExtent.bottom = Math.min(symbolExtent.bottom, y);
            symbolExtent.right = Math.max(symbolExtent.right, x);
            symbolExtent.top = Math.max(symbolExtent.top, y);

            pathitems.push(x);
            pathitems.push(y);
            if (i == 0) {
                pathitems.push("l");
            }
        }
        pathitems.push("x e");
        var path = pathitems.join(" ");

        var diff = (symbolExtent.getWidth() - symbolExtent.getHeight()) / 2;
        if(diff > 0) {
            symbolExtent.bottom = symbolExtent.bottom - diff;
            symbolExtent.top = symbolExtent.top + diff;
        } else {
            symbolExtent.left = symbolExtent.left + diff;
            symbolExtent.right = symbolExtent.right - diff;
        }
        
        cache = {
            path: path,
            size: symbolExtent.getWidth(), // equals getHeight() now
            left: symbolExtent.left,
            bottom: symbolExtent.bottom
        };
        this.symbolCache[id] = cache;
        
        return cache;
    },
    
    CLASS_NAME: "OpenLayers.Renderer.VML"
});

/**
 * Constant: OpenLayers.Renderer.VML.LABEL_SHIFT
 * {Object}
 */
OpenLayers.Renderer.VML.LABEL_SHIFT = {
    "l": 0,
    "c": .5,
    "r": 1,
    "t": 0,
    "m": .5,
    "b": 1
};
/* ======================================================================
    OpenLayers/Tile.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */


/*
 * @requires OpenLayers/Util.js
 * @requires OpenLayers/Console.js
 */

/*
 * Class: OpenLayers.Tile 
 * This is a class designed to designate a single tile, however
 *     it is explicitly designed to do relatively little. Tiles store 
 *     information about themselves -- such as the URL that they are related
 *     to, and their size - but do not add themselves to the layer div 
 *     automatically, for example. Create a new tile with the 
 *     <OpenLayers.Tile> constructor, or a subclass. 
 * 
 * TBD 3.0 - remove reference to url in above paragraph
 * 
 */
OpenLayers.Tile = OpenLayers.Class({
    
    /** 
     * Constant: EVENT_TYPES
     * {Array(String)} Supported application event types
     */
    EVENT_TYPES: [ "loadstart", "loadend", "reload", "unload"],
    
    /**
     * APIProperty: events
     * {<OpenLayers.Events>} An events object that handles all 
     *                       events on the tile.
     */
    events: null,

    /**
     * Property: id 
     * {String} null
     */
    id: null,
    
    /** 
     * Property: layer 
     * {<OpenLayers.Layer>} layer the tile is attached to 
     */
    layer: null,
    
    /**
     * Property: url
     * {String} url of the request.
     *
     * TBD 3.0 
     * Deprecated. The base tile class does not need an url. This should be 
     * handled in subclasses. Does not belong here.
     */
    url: null,

    /** 
     * APIProperty: bounds 
     * {<OpenLayers.Bounds>} null
     */
    bounds: null,
    
    /** 
     * Property: size 
     * {<OpenLayers.Size>} null
     */
    size: null,
    
    /** 
     * Property: position 
     * {<OpenLayers.Pixel>} Top Left pixel of the tile
     */    
    position: null,

    /**
     * Property: isLoading
     * {Boolean} Is the tile loading?
     */
    isLoading: false,
        
    /** TBD 3.0 -- remove 'url' from the list of parameters to the constructor.
     *             there is no need for the base tile class to have a url.
     * 
     * Constructor: OpenLayers.Tile
     * Constructor for a new <OpenLayers.Tile> instance.
     * 
     * Parameters:
     * layer - {<OpenLayers.Layer>} layer that the tile will go in.
     * position - {<OpenLayers.Pixel>}
     * bounds - {<OpenLayers.Bounds>}
     * url - {<String>}
     * size - {<OpenLayers.Size>}
     */   
    initialize: function(layer, position, bounds, url, size) {
        this.layer = layer;
        this.position = position.clone();
        this.bounds = bounds.clone();
        this.url = url;
        this.size = size.clone();

        //give the tile a unique id based on its BBOX.
        this.id = OpenLayers.Util.createUniqueID("Tile_");
        
        this.events = new OpenLayers.Events(this, null, this.EVENT_TYPES);
    },

    /**
     * Method: unload
     * Call immediately before destroying if you are listening to tile
     * events, so that counters are properly handled if tile is still
     * loading at destroy-time. Will only fire an event if the tile is
     * still loading.
     */
    unload: function() {
       if (this.isLoading) { 
           this.isLoading = false; 
           this.events.triggerEvent("unload"); 
       }
    },
    
    /** 
     * APIMethod: destroy
     * Nullify references to prevent circular references and memory leaks.
     */
    destroy:function() {
        this.layer  = null;
        this.bounds = null;
        this.size = null;
        this.position = null;
        
        this.events.destroy();
        this.events = null;
    },
    
    /**
     * Method: clone
     *
     * Parameters:
     * obj - {<OpenLayers.Tile>} The tile to be cloned
     *
     * Returns:
     * {<OpenLayers.Tile>} An exact clone of this <OpenLayers.Tile>
     */
    clone: function (obj) {
        if (obj == null) {
            obj = new OpenLayers.Tile(this.layer, 
                                      this.position, 
                                      this.bounds, 
                                      this.url, 
                                      this.size);
        } 
        
        // catch any randomly tagged-on properties
        OpenLayers.Util.applyDefaults(obj, this);
        
        return obj;
    },

    /**
     * Method: draw
     * Clear whatever is currently in the tile, then return whether or not 
     *     it should actually be re-drawn.
     * 
     * Returns:
     * {Boolean} Whether or not the tile should actually be drawn. Note that 
     *     this is not really the best way of doing things, but such is 
     *     the way the code has been developed. Subclasses call this and
     *     depend on the return to know if they should draw or not.
     */
    draw: function() {
        var maxExtent = this.layer.maxExtent;
        var withinMaxExtent = (maxExtent &&
                               this.bounds.intersectsBounds(maxExtent, false));
 
        // The only case where we *wouldn't* want to draw the tile is if the 
        // tile is outside its layer's maxExtent.
        this.shouldDraw = (withinMaxExtent || this.layer.displayOutsideMaxExtent);
                
        //clear tile's contents and mark as not drawn
        this.clear();
        
        return this.shouldDraw;
    },
    
    /** 
     * Method: moveTo
     * Reposition the tile.
     *
     * Parameters:
     * bounds - {<OpenLayers.Bounds>}
     * position - {<OpenLayers.Pixel>}
     * redraw - {Boolean} Call draw method on tile after moving.
     *     Default is true
     */
    moveTo: function (bounds, position, redraw) {
        if (redraw == null) {
            redraw = true;
        }

        this.bounds = bounds.clone();
        this.position = position.clone();
        if (redraw) {
            this.draw();
        }
    },

    /** 
     * Method: clear
     * Clear the tile of any bounds/position-related data so that it can 
     *     be reused in a new location. To be implemented by subclasses.
     */
    clear: function() {
        // to be implemented by subclasses
    },
    
    /**   
     * Method: getBoundsFromBaseLayer
     * Take the pixel locations of the corner of the tile, and pass them to 
     *     the base layer and ask for the location of those pixels, so that 
     *     displaying tiles over Google works fine.
     *
     * Parameters:
     * position - {<OpenLayers.Pixel>}
     *
     * Returns:
     * bounds - {<OpenLayers.Bounds>} 
     */
    getBoundsFromBaseLayer: function(position) {
        var msg = OpenLayers.i18n('reprojectDeprecated',
                                              {'layerName':this.layer.name});
        OpenLayers.Console.warn(msg);
        var topLeft = this.layer.map.getLonLatFromLayerPx(position); 
        var bottomRightPx = position.clone();
        bottomRightPx.x += this.size.w;
        bottomRightPx.y += this.size.h;
        var bottomRight = this.layer.map.getLonLatFromLayerPx(bottomRightPx); 
        // Handle the case where the base layer wraps around the date line.
        // Google does this, and it breaks WMS servers to request bounds in 
        // that fashion.  
        if (topLeft.lon > bottomRight.lon) {
            if (topLeft.lon < 0) {
                topLeft.lon = -180 - (topLeft.lon+180);
            } else {
                bottomRight.lon = 180+bottomRight.lon+180;
            }        
        }
        var bounds = new OpenLayers.Bounds(topLeft.lon, 
                                       bottomRight.lat, 
                                       bottomRight.lon, 
                                       topLeft.lat);  
        return bounds;
    },        
        
    /** 
     * Method: showTile
     * Show the tile only if it should be drawn.
     */
    showTile: function() { 
        if (this.shouldDraw) {
            this.show();
        }
    },
    
    /** 
     * Method: show
     * Show the tile.  To be implemented by subclasses.
     */
    show: function() { },
    
    /** 
     * Method: hide
     * Hide the tile.  To be implemented by subclasses.
     */
    hide: function() { },
    
    CLASS_NAME: "OpenLayers.Tile"
});
/* ======================================================================
    OpenLayers/Control/PanPanel.js
   ====================================================================== */

/**
 * @requires OpenLayers/Control/Panel.js
 * @requires OpenLayers/Control/Pan.js
 */

/**
 * Class: OpenLayers.Control.PanPanel
 * The PanPanel is visible control for panning the map North, South, East or
 * West in small steps. By default it is drawn in the top left corner of the
 * map.
 *
 * Note: 
 * If you wish to use this class with the default images and you want 
 *       it to look nice in ie6, you should add the following, conditionally
 *       added css stylesheet to your HTML file:
 * 
 * (code)
 * <!--[if lte IE 6]>
 *   <link rel="stylesheet" href="../theme/default/ie6-style.css" type="text/css" />
 * <![endif]-->
 * (end)
 *
 * Inherits from:
 *  - <OpenLayers.Control.Panel> 
 */
OpenLayers.Control.PanPanel = OpenLayers.Class(OpenLayers.Control.Panel, {

    /** 
     * APIProperty: slideFactor
     * {Integer} Number of pixels by which we'll pan the map in any direction 
     *     on clicking the arrow buttons, defaults to 50.
     */
    slideFactor: 50,

    /**
     * Constructor: OpenLayers.Control.PanPanel 
     * Add the four directional pan buttons.
     *
     * Parameters:
     * options - {Object} An optional object whose properties will be used
     *     to extend the control.
     */
    initialize: function(options) {
        OpenLayers.Control.Panel.prototype.initialize.apply(this, [options]);
        this.addControls([
            new OpenLayers.Control.Pan(OpenLayers.Control.Pan.NORTH,
                                       {slideFactor: this.slideFactor}),
            new OpenLayers.Control.Pan(OpenLayers.Control.Pan.SOUTH,
                                       {slideFactor: this.slideFactor}),
            new OpenLayers.Control.Pan(OpenLayers.Control.Pan.EAST,
                                       {slideFactor: this.slideFactor}),
            new OpenLayers.Control.Pan(OpenLayers.Control.Pan.WEST,
                                       {slideFactor: this.slideFactor})
        ]);
    },

    CLASS_NAME: "OpenLayers.Control.PanPanel"
});
/* ======================================================================
    OpenLayers/Control/PanZoomBar.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */


/**
 * @requires OpenLayers/Control/PanZoom.js
 */

/**
 * Class: OpenLayers.Control.PanZoomBar
 * The PanZoomBar is a visible control composed of a
 * <OpenLayers.Control.PanPanel> and a <OpenLayers.Control.ZoomBar>. 
 * By default it is displayed in the upper left corner of the map as 4
 * directional arrows above a vertical slider.
 *
 * Inherits from:
 *  - <OpenLayers.Control.PanZoom>
 */
OpenLayers.Control.PanZoomBar = OpenLayers.Class(OpenLayers.Control.PanZoom, {

    /** 
     * APIProperty: zoomStopWidth
     */
    zoomStopWidth: 18,

    /** 
     * APIProperty: zoomStopHeight
     */
    zoomStopHeight: 11,

    /** 
     * Property: slider
     */
    slider: null,

    /** 
     * Property: sliderEvents
     * {<OpenLayers.Events>}
     */
    sliderEvents: null,

    /** 
     * Property: zoombarDiv
     * {DOMElement}
     */
    zoombarDiv: null,

    /** 
     * Property: divEvents
     * {<OpenLayers.Events>}
     */
    divEvents: null,

    /** 
     * APIProperty: zoomWorldIcon
     * {Boolean}
     */
    zoomWorldIcon: false,

    /**
     * APIProperty: forceFixedZoomLevel
     * {Boolean} Force a fixed zoom level even though the map has 
     *     fractionalZoom
     */
    forceFixedZoomLevel: false,

    /**
     * Property: mouseDragStart
     * {<OpenLayers.Pixel>}
     */
    mouseDragStart: null,

    /**
     * Property: zoomStart
     * {<OpenLayers.Pixel>}
     */
    zoomStart: null,

    /**
     * Constructor: OpenLayers.Control.PanZoomBar
     */ 
    initialize: function() {
        OpenLayers.Control.PanZoom.prototype.initialize.apply(this, arguments);
    },

    /**
     * APIMethod: destroy
     */
    destroy: function() {

        this._removeZoomBar();

        this.map.events.un({
            "changebaselayer": this.redraw,
            scope: this
        });

        OpenLayers.Control.PanZoom.prototype.destroy.apply(this, arguments);

        delete this.mouseDragStart;
        delete this.zoomStart;
    },
    
    /**
     * Method: setMap
     * 
     * Parameters:
     * map - {<OpenLayers.Map>} 
     */
    setMap: function(map) {
        OpenLayers.Control.PanZoom.prototype.setMap.apply(this, arguments);
        this.map.events.register("changebaselayer", this, this.redraw);
    },

    /** 
     * Method: redraw
     * clear the div and start over.
     */
    redraw: function() {
        if (this.div != null) {
            this.removeButtons();
            this._removeZoomBar();
        }  
        this.draw();
    },
    
    /**
    * Method: draw 
    *
    * Parameters:
    * px - {<OpenLayers.Pixel>} 
    */
    draw: function(px) {
        // initialize our internal div
        OpenLayers.Control.prototype.draw.apply(this, arguments);
        px = this.position.clone();

        // place the controls
        this.buttons = [];

        var sz = new OpenLayers.Size(18,18);
        var centered = new OpenLayers.Pixel(px.x+sz.w/2, px.y);
        var wposition = sz.w;

        if (this.zoomWorldIcon) {
            centered = new OpenLayers.Pixel(px.x+sz.w, px.y);
        }

        this._addButton("panup", "north-mini.png", centered, sz);
        px.y = centered.y+sz.h;
        this._addButton("panleft", "west-mini.png", px, sz);
        if (this.zoomWorldIcon) {
            this._addButton("zoomworld", "zoom-world-mini.png", px.add(sz.w, 0), sz);
            
            wposition *= 2;
        }
        this._addButton("panright", "east-mini.png", px.add(wposition, 0), sz);
        this._addButton("pandown", "south-mini.png", centered.add(0, sz.h*2), sz);
        this._addButton("zoomin", "zoom-plus-mini.png", centered.add(0, sz.h*3+5), sz);
        centered = this._addZoomBar(centered.add(0, sz.h*4 + 5));
        this._addButton("zoomout", "zoom-minus-mini.png", centered, sz);
        return this.div;
    },

    /** 
    * Method: _addZoomBar
    * 
    * Parameters:
    * location - {<OpenLayers.Pixel>} where zoombar drawing is to start.
    */
    _addZoomBar:function(centered) {
        var imgLocation = OpenLayers.Util.getImagesLocation();
        
        var id = this.id + "_" + this.map.id;
        var zoomsToEnd = this.map.getNumZoomLevels() - 1 - this.map.getZoom();
        var slider = OpenLayers.Util.createAlphaImageDiv(id,
                       centered.add(-1, zoomsToEnd * this.zoomStopHeight), 
                       new OpenLayers.Size(20,9), 
                       imgLocation+"slider.png",
                       "absolute");
        this.slider = slider;
        
        this.sliderEvents = new OpenLayers.Events(this, slider, null, true,
                                            {includeXY: true});
        this.sliderEvents.on({
            "mousedown": this.zoomBarDown,
            "mousemove": this.zoomBarDrag,
            "mouseup": this.zoomBarUp,
            "dblclick": this.doubleClick,
            "click": this.doubleClick
        });
        
        var sz = new OpenLayers.Size();
        sz.h = this.zoomStopHeight * this.map.getNumZoomLevels();
        sz.w = this.zoomStopWidth;
        var div = null;
        
        if (OpenLayers.Util.alphaHack()) {
            var id = this.id + "_" + this.map.id;
            div = OpenLayers.Util.createAlphaImageDiv(id, centered,
                                      new OpenLayers.Size(sz.w, 
                                              this.zoomStopHeight),
                                      imgLocation + "zoombar.png", 
                                      "absolute", null, "crop");
            div.style.height = sz.h + "px";
        } else {
            div = OpenLayers.Util.createDiv(
                        'OpenLayers_Control_PanZoomBar_Zoombar' + this.map.id,
                        centered,
                        sz,
                        imgLocation+"zoombar.png");
        }
        
        this.zoombarDiv = div;
        
        this.divEvents = new OpenLayers.Events(this, div, null, true, 
                                                {includeXY: true});
        this.divEvents.on({
            "mousedown": this.divClick,
            "mousemove": this.passEventToSlider,
            "dblclick": this.doubleClick,
            "click": this.doubleClick
        });
        
        this.div.appendChild(div);

        this.startTop = parseInt(div.style.top);
        this.div.appendChild(slider);

        this.map.events.register("zoomend", this, this.moveZoomBar);

        centered = centered.add(0, 
            this.zoomStopHeight * this.map.getNumZoomLevels());
        return centered; 
    },
    
    /**
     * Method: _removeZoomBar
     */
    _removeZoomBar: function() {
        this.sliderEvents.un({
            "mousedown": this.zoomBarDown,
            "mousemove": this.zoomBarDrag,
            "mouseup": this.zoomBarUp,
            "dblclick": this.doubleClick,
            "click": this.doubleClick
        });
        this.sliderEvents.destroy();

        this.divEvents.un({
            "mousedown": this.divClick,
            "mousemove": this.passEventToSlider,
            "dblclick": this.doubleClick,
            "click": this.doubleClick
        });
        this.divEvents.destroy();
        
        this.div.removeChild(this.zoombarDiv);
        this.zoombarDiv = null;
        this.div.removeChild(this.slider);
        this.slider = null;
        
        this.map.events.unregister("zoomend", this, this.moveZoomBar);
    },
    
    /**
     * Method: passEventToSlider
     * This function is used to pass events that happen on the div, or the map,
     * through to the slider, which then does its moving thing.
     *
     * Parameters:
     * evt - {<OpenLayers.Event>} 
     */
    passEventToSlider:function(evt) {
        this.sliderEvents.handleBrowserEvent(evt);
    },
    
    /**
     * Method: divClick
     * Picks up on clicks directly on the zoombar div
     *           and sets the zoom level appropriately.
     */
    divClick: function (evt) {
        if (!OpenLayers.Event.isLeftClick(evt)) {
            return;
        }
        var y = evt.xy.y;
        var top = OpenLayers.Util.pagePosition(evt.object)[1];
        var levels = (y - top)/this.zoomStopHeight;
        if(this.forceFixedZoomLevel || !this.map.fractionalZoom) {
            levels = Math.floor(levels);
        }    
        var zoom = (this.map.getNumZoomLevels() - 1) - levels; 
        zoom = Math.min(Math.max(zoom, 0), this.map.getNumZoomLevels() - 1);
        this.map.zoomTo(zoom);
        OpenLayers.Event.stop(evt);
    },
    
    /*
     * Method: zoomBarDown
     * event listener for clicks on the slider
     *
     * Parameters:
     * evt - {<OpenLayers.Event>} 
     */
    zoomBarDown:function(evt) {
        if (!OpenLayers.Event.isLeftClick(evt)) {
            return;
        }
        this.map.events.on({
            "mousemove": this.passEventToSlider,
            "mouseup": this.passEventToSlider,
            scope: this
        });
        this.mouseDragStart = evt.xy.clone();
        this.zoomStart = evt.xy.clone();
        this.div.style.cursor = "move";
        // reset the div offsets just in case the div moved
        this.zoombarDiv.offsets = null; 
        OpenLayers.Event.stop(evt);
    },
    
    /*
     * Method: zoomBarDrag
     * This is what happens when a click has occurred, and the client is
     * dragging.  Here we must ensure that the slider doesn't go beyond the
     * bottom/top of the zoombar div, as well as moving the slider to its new
     * visual location
     *
     * Parameters:
     * evt - {<OpenLayers.Event>} 
     */
    zoomBarDrag:function(evt) {
        if (this.mouseDragStart != null) {
            var deltaY = this.mouseDragStart.y - evt.xy.y;
            var offsets = OpenLayers.Util.pagePosition(this.zoombarDiv);
            if ((evt.clientY - offsets[1]) > 0 && 
                (evt.clientY - offsets[1]) < parseInt(this.zoombarDiv.style.height) - 2) {
                var newTop = parseInt(this.slider.style.top) - deltaY;
                this.slider.style.top = newTop+"px";
                this.mouseDragStart = evt.xy.clone();
            }
            OpenLayers.Event.stop(evt);
        }
    },
    
    /*
     * Method: zoomBarUp
     * Perform cleanup when a mouseup event is received -- discover new zoom
     * level and switch to it.
     *
     * Parameters:
     * evt - {<OpenLayers.Event>} 
     */
    zoomBarUp:function(evt) {
        if (!OpenLayers.Event.isLeftClick(evt)) {
            return;
        }
        if (this.mouseDragStart) {
            this.div.style.cursor="";
            this.map.events.un({
                "mouseup": this.passEventToSlider,
                "mousemove": this.passEventToSlider,
                scope: this
            });
            var deltaY = this.zoomStart.y - evt.xy.y;
            var zoomLevel = this.map.zoom;
            if (!this.forceFixedZoomLevel && this.map.fractionalZoom) {
                zoomLevel += deltaY/this.zoomStopHeight;
                zoomLevel = Math.min(Math.max(zoomLevel, 0), 
                                     this.map.getNumZoomLevels() - 1);
            } else {
                zoomLevel += Math.round(deltaY/this.zoomStopHeight);
            }
            this.map.zoomTo(zoomLevel);
            this.mouseDragStart = null;
            this.zoomStart = null;
            OpenLayers.Event.stop(evt);
        }
    },
    
    /*
    * Method: moveZoomBar
    * Change the location of the slider to match the current zoom level.
    */
    moveZoomBar:function() {
        var newTop = 
            ((this.map.getNumZoomLevels()-1) - this.map.getZoom()) * 
            this.zoomStopHeight + this.startTop + 1;
        this.slider.style.top = newTop + "px";
    },    
    
    CLASS_NAME: "OpenLayers.Control.PanZoomBar"
});
/* ======================================================================
    OpenLayers/Control/ZoomPanel.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */

/**
 * @requires OpenLayers/Control/Panel.js
 * @requires OpenLayers/Control/ZoomIn.js
 * @requires OpenLayers/Control/ZoomOut.js
 * @requires OpenLayers/Control/ZoomToMaxExtent.js
 */

/**
 * Class: OpenLayers.Control.ZoomPanel
 * The ZoomPanel control is a compact collecton of 3 zoom controls: a 
 * <OpenLayers.Control.ZoomIn>, a <OpenLayers.Control.ZoomToMaxExtent>, and a
 * <OpenLayers.Control.ZoomOut>. By default it is drawn in the upper left 
 * corner of the map.
 *
 * Note: 
 * If you wish to use this class with the default images and you want 
 *       it to look nice in ie6, you should add the following, conditionally
 *       added css stylesheet to your HTML file:
 * 
 * (code)
 * <!--[if lte IE 6]>
 *   <link rel="stylesheet" href="../theme/default/ie6-style.css" type="text/css" />
 * <![endif]-->
 * (end)
 * 
 * Inherits from:
 *  - <OpenLayers.Control.Panel>
 */
OpenLayers.Control.ZoomPanel = OpenLayers.Class(OpenLayers.Control.Panel, {

    /**
     * Constructor: OpenLayers.Control.ZoomPanel 
     * Add the three zooming controls.
     *
     * Parameters:
     * options - {Object} An optional object whose properties will be used
     *     to extend the control.
     */
    initialize: function(options) {
        OpenLayers.Control.Panel.prototype.initialize.apply(this, [options]);
        this.addControls([
            new OpenLayers.Control.ZoomIn(),
            new OpenLayers.Control.ZoomToMaxExtent(),
            new OpenLayers.Control.ZoomOut()
        ]);
    },

    CLASS_NAME: "OpenLayers.Control.ZoomPanel"
});
/* ======================================================================
    OpenLayers/Handler.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */

/**
 * @requires OpenLayers/Events.js
 */

/**
 * Class: OpenLayers.Handler
 * Base class to construct a higher-level handler for event sequences.  All
 *     handlers have activate and deactivate methods.  In addition, they have
 *     methods named like browser events.  When a handler is activated, any
 *     additional methods named like a browser event is registered as a
 *     listener for the corresponding event.  When a handler is deactivated,
 *     those same methods are unregistered as event listeners.
 *
 * Handlers also typically have a callbacks object with keys named like
 *     the abstracted events or event sequences that they are in charge of
 *     handling.  The controls that wrap handlers define the methods that
 *     correspond to these abstract events - so instead of listening for
 *     individual browser events, they only listen for the abstract events
 *     defined by the handler.
 *     
 * Handlers are created by controls, which ultimately have the responsibility
 *     of making changes to the the state of the application.  Handlers
 *     themselves may make temporary changes, but in general are expected to
 *     return the application in the same state that they found it.
 */
OpenLayers.Handler = OpenLayers.Class({

    /**
     * Property: id
     * {String}
     */
    id: null,
        
    /**
     * APIProperty: control
     * {<OpenLayers.Control>}. The control that initialized this handler.  The
     *     control is assumed to have a valid map property - that map is used
     *     in the handler's own setMap method.
     */
    control: null,

    /**
     * Property: map
     * {<OpenLayers.Map>}
     */
    map: null,

    /**
     * APIProperty: keyMask
     * {Integer} Use bitwise operators and one or more of the OpenLayers.Handler
     *     constants to construct a keyMask.  The keyMask is used by
     *     <checkModifiers>.  If the keyMask matches the combination of keys
     *     down on an event, checkModifiers returns true.
     *
     * Example:
     * (code)
     *     // handler only responds if the Shift key is down
     *     handler.keyMask = OpenLayers.Handler.MOD_SHIFT;
     *
     *     // handler only responds if Ctrl-Shift is down
     *     handler.keyMask = OpenLayers.Handler.MOD_SHIFT |
     *                       OpenLayers.Handler.MOD_CTRL;
     * (end)
     */
    keyMask: null,

    /**
     * Property: active
     * {Boolean}
     */
    active: false,
    
    /**
     * Property: evt
     * {Event} This property references the last event handled by the handler.
     *     Note that this property is not part of the stable API.  Use of the
     *     evt property should be restricted to controls in the library
     *     or other applications that are willing to update with changes to
     *     the OpenLayers code.
     */
    evt: null,

    /**
     * Constructor: OpenLayers.Handler
     * Construct a handler.
     *
     * Parameters:
     * control - {<OpenLayers.Control>} The control that initialized this
     *     handler.  The control is assumed to have a valid map property; that
     *     map is used in the handler's own setMap method.
     * callbacks - {Object} An object whose properties correspond to abstracted
     *     events or sequences of browser events.  The values for these
     *     properties are functions defined by the control that get called by
     *     the handler.
     * options - {Object} An optional object whose properties will be set on
     *     the handler.
     */
    initialize: function(control, callbacks, options) {
        OpenLayers.Util.extend(this, options);
        this.control = control;
        this.callbacks = callbacks;
        if (control.map) {
            this.setMap(control.map); 
        }

        OpenLayers.Util.extend(this, options);
        
        this.id = OpenLayers.Util.createUniqueID(this.CLASS_NAME + "_");
    },
    
    /**
     * Method: setMap
     */
    setMap: function (map) {
        this.map = map;
    },

    /**
     * Method: checkModifiers
     * Check the keyMask on the handler.  If no <keyMask> is set, this always
     *     returns true.  If a <keyMask> is set and it matches the combination
     *     of keys down on an event, this returns true.
     *
     * Returns:
     * {Boolean} The keyMask matches the keys down on an event.
     */
    checkModifiers: function (evt) {
        if(this.keyMask == null) {
            return true;
        }
        /* calculate the keyboard modifier mask for this event */
        var keyModifiers =
            (evt.shiftKey ? OpenLayers.Handler.MOD_SHIFT : 0) |
            (evt.ctrlKey  ? OpenLayers.Handler.MOD_CTRL  : 0) |
            (evt.altKey   ? OpenLayers.Handler.MOD_ALT   : 0);
    
        /* if it differs from the handler object's key mask,
           bail out of the event handler */
        return (keyModifiers == this.keyMask);
    },

    /**
     * APIMethod: activate
     * Turn on the handler.  Returns false if the handler was already active.
     * 
     * Returns: 
     * {Boolean} The handler was activated.
     */
    activate: function() {
        if(this.active) {
            return false;
        }
        // register for event handlers defined on this class.
        var events = OpenLayers.Events.prototype.BROWSER_EVENTS;
        for (var i=0, len=events.length; i<len; i++) {
            if (this[events[i]]) {
                this.register(events[i], this[events[i]]); 
            }
        } 
        this.active = true;
        return true;
    },
    
    /**
     * APIMethod: deactivate
     * Turn off the handler.  Returns false if the handler was already inactive.
     * 
     * Returns:
     * {Boolean} The handler was deactivated.
     */
    deactivate: function() {
        if(!this.active) {
            return false;
        }
        // unregister event handlers defined on this class.
        var events = OpenLayers.Events.prototype.BROWSER_EVENTS;
        for (var i=0, len=events.length; i<len; i++) {
            if (this[events[i]]) {
                this.unregister(events[i], this[events[i]]); 
            }
        } 
        this.active = false;
        return true;
    },

    /**
    * Method: callback
    * Trigger the control's named callback with the given arguments
    *
    * Parameters:
    * name - {String} The key for the callback that is one of the properties
    *     of the handler's callbacks object.
    * args - {Array(*)} An array of arguments (any type) with which to call 
    *     the callback (defined by the control).
    */
    callback: function (name, args) {
        if (name && this.callbacks[name]) {
            this.callbacks[name].apply(this.control, args);
        }
    },

    /**
    * Method: register
    * register an event on the map
    */
    register: function (name, method) {
        // TODO: deal with registerPriority in 3.0
        this.map.events.registerPriority(name, this, method);
        this.map.events.registerPriority(name, this, this.setEvent);
    },

    /**
    * Method: unregister
    * unregister an event from the map
    */
    unregister: function (name, method) {
        this.map.events.unregister(name, this, method);   
        this.map.events.unregister(name, this, this.setEvent);
    },
    
    /**
     * Method: setEvent
     * With each registered browser event, the handler sets its own evt
     *     property.  This property can be accessed by controls if needed
     *     to get more information about the event that the handler is
     *     processing.
     *
     * This allows modifier keys on the event to be checked (alt, shift,
     *     and ctrl cannot be checked with the keyboard handler).  For a
     *     control to determine which modifier keys are associated with the
     *     event that a handler is currently processing, it should access
     *     (code)handler.evt.altKey || handler.evt.shiftKey ||
     *     handler.evt.ctrlKey(end).
     *
     * Parameters:
     * evt - {Event} The browser event.
     */
    setEvent: function(evt) {
        this.evt = evt;
        return true;
    },

    /**
     * Method: destroy
     * Deconstruct the handler.
     */
    destroy: function () {
        // unregister event listeners
        this.deactivate();
        // eliminate circular references
        this.control = this.map = null;        
    },

    CLASS_NAME: "OpenLayers.Handler"
});

/**
 * Constant: OpenLayers.Handler.MOD_NONE
 * If set as the <keyMask>, <checkModifiers> returns false if any key is down.
 */
OpenLayers.Handler.MOD_NONE  = 0;

/**
 * Constant: OpenLayers.Handler.MOD_SHIFT
 * If set as the <keyMask>, <checkModifiers> returns false if Shift is down.
 */
OpenLayers.Handler.MOD_SHIFT = 1;

/**
 * Constant: OpenLayers.Handler.MOD_CTRL
 * If set as the <keyMask>, <checkModifiers> returns false if Ctrl is down.
 */
OpenLayers.Handler.MOD_CTRL  = 2;

/**
 * Constant: OpenLayers.Handler.MOD_ALT
 * If set as the <keyMask>, <checkModifiers> returns false if Alt is down.
 */
OpenLayers.Handler.MOD_ALT   = 4;


/* ======================================================================
    OpenLayers/Map.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */

/**
 * @requires OpenLayers/Util.js
 * @requires OpenLayers/Events.js
 * @requires OpenLayers/Tween.js
 * @requires OpenLayers/Console.js
 */

/**
 * Class: OpenLayers.Map
 * Instances of OpenLayers.Map are interactive maps embedded in a web page.
 * Create a new map with the <OpenLayers.Map> constructor.
 * 
 * On their own maps do not provide much functionality.  To extend a map
 * it's necessary to add controls (<OpenLayers.Control>) and 
 * layers (<OpenLayers.Layer>) to the map. 
 */
OpenLayers.Map = OpenLayers.Class({
    
    /**
     * Constant: Z_INDEX_BASE
     * {Object} Base z-indexes for different classes of thing 
     */
    Z_INDEX_BASE: {
        BaseLayer: 100,
        Overlay: 325,
        Feature: 725,
        Popup: 750,
        Control: 1000
    },

    /**
     * Constant: EVENT_TYPES
     * {Array(String)} Supported application event types.  Register a listener
     *     for a particular event with the following syntax:
     * (code)
     * map.events.register(type, obj, listener);
     * (end)
     *
     * Listeners will be called with a reference to an event object.  The
     *     properties of this event depends on exactly what happened.
     *
     * All event objects have at least the following properties:
     *  - *object* {Object} A reference to map.events.object.
     *  - *element* {DOMElement} A reference to map.events.element.
     *
     * Browser events have the following additional properties:
     *  - *xy* {<OpenLayers.Pixel>} The pixel location of the event (relative
     *      to the the map viewport).
     *  - other properties that come with browser events
     *
     * Supported map event types:
     *  - *preaddlayer* triggered before a layer has been added.  The event
     *      object will include a *layer* property that references the layer  
     *      to be added.
     *  - *addlayer* triggered after a layer has been added.  The event object
     *      will include a *layer* property that references the added layer.
     *  - *removelayer* triggered after a layer has been removed.  The event
     *      object will include a *layer* property that references the removed
     *      layer.
     *  - *changelayer* triggered after a layer name change, order change,
     *      opacity change, params change or visibility change
     *      (due to resolution thresholds). Listeners will receive an event
     *      object with *layer* and *property* properties. The *layer*
     *      property will be a reference to the changed layer. 
     *      The *property* property will be a key to the
     *      changed property (name, order, opacity, params or visibility).
     *  - *movestart* triggered after the start of a drag, pan, or zoom
     *  - *move* triggered after each drag, pan, or zoom
     *  - *moveend* triggered after a drag, pan, or zoom completes
     *  - *zoomend* triggered after a zoom completes
     *  - *addmarker* triggered after a marker has been added
     *  - *removemarker* triggered after a marker has been removed
     *  - *clearmarkers* triggered after markers have been cleared
     *  - *mouseover* triggered after mouseover the map
     *  - *mouseout* triggered after mouseout the map
     *  - *mousemove* triggered after mousemove the map
     *  - *dragstart* Does not work.  Register for movestart instead.
     *  - *drag* Does not work.  Register for move instead.
     *  - *dragend* Does not work.  Register for moveend instead.
     *  - *changebaselayer* triggered after the base layer changes
     */
    EVENT_TYPES: [ 
        "preaddlayer", "addlayer", "removelayer", "changelayer", "movestart",
        "move", "moveend", "zoomend", "popupopen", "popupclose",
        "addmarker", "removemarker", "clearmarkers", "mouseover",
        "mouseout", "mousemove", "dragstart", "drag", "dragend",
        "changebaselayer"],

    /**
     * Property: id
     * {String} Unique identifier for the map
     */
    id: null,
    
    /**
     * Property: fractionalZoom
     * {Boolean} For a base layer that supports it, allow the map resolution
     *     to be set to a value between one of the values in the resolutions
     *     array.  Default is false.
     *
     * When fractionalZoom is set to true, it is possible to zoom to
     *     an arbitrary extent.  This requires a base layer from a source
     *     that supports requests for arbitrary extents (i.e. not cached
     *     tiles on a regular lattice).  This means that fractionalZoom
     *     will not work with commercial layers (Google, Yahoo, VE), layers
     *     using TileCache, or any other pre-cached data sources.
     *
     * If you are using fractionalZoom, then you should also use
     *     <getResolutionForZoom> instead of layer.resolutions[zoom] as the
     *     former works for non-integer zoom levels.
     */
    fractionalZoom: false,
    
    /**
     * APIProperty: events
     * {<OpenLayers.Events>} An events object that handles all 
     *                       events on the map
     */
    events: null,
    
    /**
     * APIProperty: allOverlays
     * {Boolean} Allow the map to function with "overlays" only.  Defaults to
     *     false.  If true, the lowest layer in the draw order will act as
     *     the base layer.  In addition, if set to true, all layers will
     *     have isBaseLayer set to false when they are added to the map.
     *
     * Note:
     * If you set map.allOverlays to true, then you *cannot* use
     *     map.setBaseLayer or layer.setIsBaseLayer.  With allOverlays true,
     *     the lowest layer in the draw layer is the base layer.  So, to change
     *     the base layer, use <setLayerIndex> or <raiseLayer> to set the layer
     *     index to 0.
     */
    allOverlays: false,

    /**
     * APIProperty: div
     * {DOMElement|String} The element that contains the map (or an id for
     *     that element).  If the <OpenLayers.Map> constructor is called
     *     with two arguments, this should be provided as the first argument.
     *     Alternatively, the map constructor can be called with the options
     *     object as the only argument.  In this case (one argument), a
     *     div property may or may not be provided.  If the div property
     *     is not provided, the map can be rendered to a container later
     *     using the <render> method.
     *     
     * Note:
     * If you are calling <render> after map construction, do not use
     *     <maxResolution>  auto.  Instead, divide your <maxExtent> by your
     *     maximum expected dimension.
     */
    div: null,
    
    /**
     * Property: dragging
     * {Boolean} The map is currently being dragged.
     */
    dragging: false,

    /**
     * Property: size
     * {<OpenLayers.Size>} Size of the main div (this.div)
     */
    size: null,
    
    /**
     * Property: viewPortDiv
     * {HTMLDivElement} The element that represents the map viewport
     */
    viewPortDiv: null,

    /**
     * Property: layerContainerOrigin
     * {<OpenLayers.LonLat>} The lonlat at which the later container was
     *                       re-initialized (on-zoom)
     */
    layerContainerOrigin: null,

    /**
     * Property: layerContainerDiv
     * {HTMLDivElement} The element that contains the layers.
     */
    layerContainerDiv: null,

    /**
     * APIProperty: layers
     * {Array(<OpenLayers.Layer>)} Ordered list of layers in the map
     */
    layers: null,

    /**
     * Property: controls
     * {Array(<OpenLayers.Control>)} List of controls associated with the map.
     *
     * If not provided in the map options at construction, the map will
     *     be given the following controls by default:
     *  - <OpenLayers.Control.Navigation>
     *  - <OpenLayers.Control.PanZoom>
     *  - <OpenLayers.Control.ArgParser>
     *  - <OpenLayers.Control.Attribution>
     */
    controls: null,

    /**
     * Property: popups
     * {Array(<OpenLayers.Popup>)} List of popups associated with the map
     */
    popups: null,

    /**
     * APIProperty: baseLayer
     * {<OpenLayers.Layer>} The currently selected base layer.  This determines
     * min/max zoom level, projection, etc.
     */
    baseLayer: null,
    
    /**
     * Property: center
     * {<OpenLayers.LonLat>} The current center of the map
     */
    center: null,

    /**
     * Property: resolution
     * {Float} The resolution of the map.
     */
    resolution: null,

    /**
     * Property: zoom
     * {Integer} The current zoom level of the map
     */
    zoom: 0,    

    /**
     * Property: panRatio
     * {Float} The ratio of the current extent within
     *         which panning will tween.
     */
    panRatio: 1.5,    

    /**
     * Property: viewRequestID
     * {String} Used to store a unique identifier that changes when the map 
     *          view changes. viewRequestID should be used when adding data 
     *          asynchronously to the map: viewRequestID is incremented when 
     *          you initiate your request (right now during changing of 
     *          baselayers and changing of zooms). It is stored here in the 
     *          map and also in the data that will be coming back 
     *          asynchronously. Before displaying this data on request 
     *          completion, we check that the viewRequestID of the data is 
     *          still the same as that of the map. Fix for #480
     */
    viewRequestID: 0,

  // Options

    /**
     * APIProperty: tileSize
     * {<OpenLayers.Size>} Set in the map options to override the default tile
     *                     size for this map.
     */
    tileSize: null,

    /**
     * APIProperty: projection
     * {String} Set in the map options to override the default projection 
     *          string this map - also set maxExtent, maxResolution, and 
     *          units if appropriate.  Default is "EPSG:4326".
     */
    projection: "EPSG:4326",    
        
    /**
     * APIProperty: units
     * {String} The map units.  Defaults to 'degrees'.  Possible values are
     *          'degrees' (or 'dd'), 'm', 'ft', 'km', 'mi', 'inches'.
     */
    units: 'degrees',

    /**
     * APIProperty: resolutions
     * {Array(Float)} A list of map resolutions (map units per pixel) in 
     *     descending order.  If this is not set in the layer constructor, it 
     *     will be set based on other resolution related properties 
     *     (maxExtent, maxResolution, maxScale, etc.).
     */
    resolutions: null,

    /**
     * APIProperty: maxResolution
     * {Float} Default max is 360 deg / 256 px, which corresponds to
     *          zoom level 0 on gmaps.  Specify a different value in the map 
     *          options if you are not using a geographic projection and 
     *          displaying the whole world.
     */
    maxResolution: 1.40625,

    /**
     * APIProperty: minResolution
     * {Float}
     */
    minResolution: null,

    /**
     * APIProperty: maxScale
     * {Float}
     */
    maxScale: null,

    /**
     * APIProperty: minScale
     * {Float}
     */
    minScale: null,

    /**
     * APIProperty: maxExtent
     * {<OpenLayers.Bounds>} The maximum extent for the map.  Defaults to the
     *                       whole world in decimal degrees 
     *                       (-180, -90, 180, 90).  Specify a different
     *                        extent in the map options if you are not using a 
     *                        geographic projection and displaying the whole 
     *                        world.
     */
    maxExtent: null,
    
    /**
     * APIProperty: minExtent
     * {<OpenLayers.Bounds>}
     */
    minExtent: null,
    
    /**
     * APIProperty: restrictedExtent
     * {<OpenLayers.Bounds>} Limit map navigation to this extent where possible.
     *     If a non-null restrictedExtent is set, panning will be restricted
     *     to the given bounds.  In addition, zooming to a resolution that
     *     displays more than the restricted extent will center the map
     *     on the restricted extent.  If you wish to limit the zoom level
     *     or resolution, use maxResolution.
     */
    restrictedExtent: null,

    /**
     * APIProperty: numZoomLevels
     * {Integer} Number of zoom levels for the map.  Defaults to 16.  Set a
     *           different value in the map options if needed.
     */
    numZoomLevels: 16,

    /**
     * APIProperty: theme
     * {String} Relative path to a CSS file from which to load theme styles.
     *          Specify null in the map options (e.g. {theme: null}) if you 
     *          want to get cascading style declarations - by putting links to 
     *          stylesheets or style declarations directly in your page.
     */
    theme: null,
    
    /** 
     * APIProperty: displayProjection
     * {<OpenLayers.Projection>} Requires proj4js support.Projection used by
     *     several controls to display data to user. If this property is set,
     *     it will be set on any control which has a null displayProjection
     *     property at the time the control is added to the map. 
     */
    displayProjection: null,

    /**
     * APIProperty: fallThrough
     * {Boolean} Should OpenLayers allow events on the map to fall through to
     *           other elements on the page, or should it swallow them? (#457)
     *           Default is to fall through.
     */
    fallThrough: true,
    
    /**
     * Property: panTween
     * {OpenLayers.Tween} Animated panning tween object, see panTo()
     */
    panTween: null,

    /**
     * APIProperty: eventListeners
     * {Object} If set as an option at construction, the eventListeners
     *     object will be registered with <OpenLayers.Events.on>.  Object
     *     structure must be a listeners object as shown in the example for
     *     the events.on method.
     */
    eventListeners: null,

    /**
     * APIProperty: panMethod
     * {Function} The Easing function to be used for tweening.  Default is
     * OpenLayers.Easing.Expo.easeOut. Setting this to 'null' turns off
     * animated panning.
     */
    panMethod: OpenLayers.Easing.Expo.easeOut,
    
    /**
     * Property: panDuration
     * {Integer} The number of steps to be passed to the
     * OpenLayers.Tween.start() method when the map is
     * panned.
     * Default is 50.
     */
    panDuration: 50,
    
    /**
     * Property: paddingForPopups
     * {<OpenLayers.Bounds>} Outside margin of the popup. Used to prevent 
     *     the popup from getting too close to the map border.
     */
    paddingForPopups : null,
    
    /**
     * Constructor: OpenLayers.Map
     * Constructor for a new OpenLayers.Map instance.  There are two possible
     *     ways to call the map constructor.  See the examples below.
     *
     * Parameters:
     * div - {DOMElement|String}  The element or id of an element in your page
     *     that will contain the map.  May be omitted if the <div> option is
     *     provided or if you intend to call the <render> method later.
     * options - {Object} Optional object with properties to tag onto the map.
     *
     * Examples (method one):
     * (code)
     * // create a map with default options in an element with the id "map1"
     * var map = new OpenLayers.Map("map1");
     *
     * // create a map with non-default options in an element with id "map2"
     * var options = {
     *     maxExtent: new OpenLayers.Bounds(-200000, -200000, 200000, 200000),
     *     maxResolution: 156543,
     *     units: 'm',
     *     projection: "EPSG:41001"
     * };
     * var map = new OpenLayers.Map("map2", options);
     * (end)
     *
     * Examples (method two - single argument):
     * (code)
     * // create a map with non-default options
     * var map = new OpenLayers.Map({
     *     div: "map_id",
     *     maxExtent: new OpenLayers.Bounds(-200000, -200000, 200000, 200000),
     *     maxResolution: 156543,
     *     units: 'm',
     *     projection: "EPSG:41001"
     * });
     *
     * // create a map without a reference to a container - call render later
     * var map = new OpenLayers.Map({
     *     maxExtent: new OpenLayers.Bounds(-200000, -200000, 200000, 200000),
     *     maxResolution: 156543,
     *     units: 'm',
     *     projection: "EPSG:41001"
     * });
     */    
    initialize: function (div, options) {
        
        // If only one argument is provided, check if it is an object.
        if(arguments.length === 1 && typeof div === "object") {
            options = div;
            div = options && options.div;
        }

        // Simple-type defaults are set in class definition. 
        //  Now set complex-type defaults 
        this.tileSize = new OpenLayers.Size(OpenLayers.Map.TILE_WIDTH,
                                            OpenLayers.Map.TILE_HEIGHT);
        
        this.maxExtent = new OpenLayers.Bounds(-180, -90, 180, 90);
        
        this.paddingForPopups = new OpenLayers.Bounds(15, 15, 15, 15);

        this.theme = OpenLayers._getScriptLocation() + 
                             'theme/default/style.css'; 

        // now override default options 
        OpenLayers.Util.extend(this, options);

        // initialize layers array
        this.layers = [];

        this.id = OpenLayers.Util.createUniqueID("OpenLayers.Map_");

        this.div = OpenLayers.Util.getElement(div);
        if(!this.div) {
            this.div = document.createElement("div");
            this.div.style.height = "1px";
            this.div.style.width = "1px";
        }
        
        OpenLayers.Element.addClass(this.div, 'olMap');

        // the viewPortDiv is the outermost div we modify
        var id = this.id + "_OpenLayers_ViewPort";
        this.viewPortDiv = OpenLayers.Util.createDiv(id, null, null, null,
                                                     "relative", null,
                                                     "hidden");
        this.viewPortDiv.style.width = "100%";
        this.viewPortDiv.style.height = "100%";
        this.viewPortDiv.className = "olMapViewport";
        this.div.appendChild(this.viewPortDiv);

        // the layerContainerDiv is the one that holds all the layers
        id = this.id + "_OpenLayers_Container";
        this.layerContainerDiv = OpenLayers.Util.createDiv(id);
        this.layerContainerDiv.style.zIndex=this.Z_INDEX_BASE['Popup']-1;
        
        this.viewPortDiv.appendChild(this.layerContainerDiv);

        this.events = new OpenLayers.Events(this, 
                                            this.div, 
                                            this.EVENT_TYPES, 
                                            this.fallThrough, 
                                            {includeXY: true});
        this.updateSize();
        if(this.eventListeners instanceof Object) {
            this.events.on(this.eventListeners);
        }
 
        // update the map size and location before the map moves
        this.events.register("movestart", this, this.updateSize);

        // Because Mozilla does not support the "resize" event for elements 
        // other than "window", we need to put a hack here. 
        if (OpenLayers.String.contains(navigator.appName, "Microsoft")) {
            // If IE, register the resize on the div
            this.events.register("resize", this, this.updateSize);
        } else {
            // Else updateSize on catching the window's resize
            //  Note that this is ok, as updateSize() does nothing if the 
            //  map's size has not actually changed.
            this.updateSizeDestroy = OpenLayers.Function.bind(this.updateSize, 
                this);
            OpenLayers.Event.observe(window, 'resize',
                            this.updateSizeDestroy);
        }
        
        // only append link stylesheet if the theme property is set
        if(this.theme) {
            // check existing links for equivalent url
            var addNode = true;
            var nodes = document.getElementsByTagName('link');
            for(var i=0, len=nodes.length; i<len; ++i) {
                if(OpenLayers.Util.isEquivalentUrl(nodes.item(i).href,
                                                   this.theme)) {
                    addNode = false;
                    break;
                }
            }
            // only add a new node if one with an equivalent url hasn't already
            // been added
            if(addNode) {
                var cssNode = document.createElement('link');
                cssNode.setAttribute('rel', 'stylesheet');
                cssNode.setAttribute('type', 'text/css');
                cssNode.setAttribute('href', this.theme);
                document.getElementsByTagName('head')[0].appendChild(cssNode);
            }
        }
        
        if (this.controls == null) {
            if (OpenLayers.Control != null) { // running full or lite?
                this.controls = [ new OpenLayers.Control.Navigation(),
                                  new OpenLayers.Control.PanZoom(),
                                  new OpenLayers.Control.ArgParser(),
                                  new OpenLayers.Control.Attribution()
                                ];
            } else {
                this.controls = [];
            }
        }

        for(var i=0, len=this.controls.length; i<len; i++) {
            this.addControlToMap(this.controls[i]);
        }

        this.popups = [];

        this.unloadDestroy = OpenLayers.Function.bind(this.destroy, this);
        

        // always call map.destroy()
        OpenLayers.Event.observe(window, 'unload', this.unloadDestroy);
        
        // add any initial layers
        if (options && options.layers) {
            this.addLayers(options.layers);        
            // set center (and optionally zoom)
            if (options.center) {
                // zoom can be undefined here
                this.setCenter(options.center, options.zoom);
            }
        }
    },
    
    /**
     * APIMethod: render
     * Render the map to a specified container.
     * 
     * Parameters:
     * div - {String|DOMElement} The container that the map should be rendered
     *     to. If different than the current container, the map viewport
     *     will be moved from the current to the new container.
     */
    render: function(div) {
        this.div = OpenLayers.Util.getElement(div);
        OpenLayers.Element.addClass(this.div, 'olMap');
        this.events.attachToElement(this.div);
        this.viewPortDiv.parentNode.removeChild(this.viewPortDiv);
        this.div.appendChild(this.viewPortDiv);
        this.updateSize();
    },

    /**
     * Method: unloadDestroy
     * Function that is called to destroy the map on page unload. stored here
     *     so that if map is manually destroyed, we can unregister this.
     */
    unloadDestroy: null,
    
    /**
     * Method: updateSizeDestroy
     * When the map is destroyed, we need to stop listening to updateSize
     *    events: this method stores the function we need to unregister in 
     *    non-IE browsers.
     */
    updateSizeDestroy: null,

    /**
     * APIMethod: destroy
     * Destroy this map
     */
    destroy:function() {
        // if unloadDestroy is null, we've already been destroyed
        if (!this.unloadDestroy) {
            return false;
        }
        
        // make sure panning doesn't continue after destruction
        if(this.panTween && this.panTween.playing) {
            this.panTween.stop();
        }

        // map has been destroyed. dont do it again!
        OpenLayers.Event.stopObserving(window, 'unload', this.unloadDestroy);
        this.unloadDestroy = null;

        if (this.updateSizeDestroy) {
            OpenLayers.Event.stopObserving(window, 'resize', 
                                           this.updateSizeDestroy);
        } else {
            this.events.unregister("resize", this, this.updateSize);
        }    
        
        this.paddingForPopups = null;    

        if (this.controls != null) {
            for (var i = this.controls.length - 1; i>=0; --i) {
                this.controls[i].destroy();
            } 
            this.controls = null;
        }
        if (this.layers != null) {
            for (var i = this.layers.length - 1; i>=0; --i) {
                //pass 'false' to destroy so that map wont try to set a new 
                // baselayer after each baselayer is removed
                this.layers[i].destroy(false);
            } 
            this.layers = null;
        }
        if (this.viewPortDiv) {
            this.div.removeChild(this.viewPortDiv);
        }
        this.viewPortDiv = null;

        if(this.eventListeners) {
            this.events.un(this.eventListeners);
            this.eventListeners = null;
        }
        this.events.destroy();
        this.events = null;

    },

    /**
     * APIMethod: setOptions
     * Change the map options
     *
     * Parameters:
     * options - {Object} Hashtable of options to tag to the map
     */
    setOptions: function(options) {
        OpenLayers.Util.extend(this, options);
    },

    /**
     * APIMethod: getTileSize
     * Get the tile size for the map
     *
     * Returns:
     * {<OpenLayers.Size>}
     */
     getTileSize: function() {
         return this.tileSize;
     },


    /**
     * APIMethod: getBy
     * Get a list of objects given a property and a match item.
     *
     * Parameters:
     * array - {String} A property on the map whose value is an array.
     * property - {String} A property on each item of the given array.
     * match - {String | Object} A string to match.  Can also be a regular
     *     expression literal or object.  In addition, it can be any object
     *     with a method named test.  For reqular expressions or other, if
     *     match.test(map[array][i][property]) evaluates to true, the item will
     *     be included in the array returned.  If no items are found, an empty
     *     array is returned.
     *
     * Returns:
     * {Array} An array of items where the given property matches the given
     *     criteria.
     */
    getBy: function(array, property, match) {
        var test = (typeof match.test == "function");
        var found = OpenLayers.Array.filter(this[array], function(item) {
            return item[property] == match || (test && match.test(item[property]));
        });
        return found;
    },

    /**
     * APIMethod: getLayersBy
     * Get a list of layers with properties matching the given criteria.
     *
     * Parameter:
     * property - {String} A layer property to be matched.
     * match - {String | Object} A string to match.  Can also be a regular
     *     expression literal or object.  In addition, it can be any object
     *     with a method named test.  For reqular expressions or other, if
     *     match.test(layer[property]) evaluates to true, the layer will be
     *     included in the array returned.  If no layers are found, an empty
     *     array is returned.
     *
     * Returns:
     * {Array(<OpenLayers.Layer>)} A list of layers matching the given criteria.
     *     An empty array is returned if no matches are found.
     */
    getLayersBy: function(property, match) {
        return this.getBy("layers", property, match);
    },

    /**
     * APIMethod: getLayersByName
     * Get a list of layers with names matching the given name.
     *
     * Parameter:
     * match - {String | Object} A layer name.  The name can also be a regular
     *     expression literal or object.  In addition, it can be any object
     *     with a method named test.  For reqular expressions or other, if
     *     name.test(layer.name) evaluates to true, the layer will be included
     *     in the list of layers returned.  If no layers are found, an empty
     *     array is returned.
     *
     * Returns:
     * {Array(<OpenLayers.Layer>)} A list of layers matching the given name.
     *     An empty array is returned if no matches are found.
     */
    getLayersByName: function(match) {
        return this.getLayersBy("name", match);
    },

    /**
     * APIMethod: getLayersByClass
     * Get a list of layers of a given class (CLASS_NAME).
     *
     * Parameter:
     * match - {String | Object} A layer class name.  The match can also be a
     *     regular expression literal or object.  In addition, it can be any
     *     object with a method named test.  For reqular expressions or other,
     *     if type.test(layer.CLASS_NAME) evaluates to true, the layer will
     *     be included in the list of layers returned.  If no layers are
     *     found, an empty array is returned.
     *
     * Returns:
     * {Array(<OpenLayers.Layer>)} A list of layers matching the given class.
     *     An empty array is returned if no matches are found.
     */
    getLayersByClass: function(match) {
        return this.getLayersBy("CLASS_NAME", match);
    },

    /**
     * APIMethod: getControlsBy
     * Get a list of controls with properties matching the given criteria.
     *
     * Parameter:
     * property - {String} A control property to be matched.
     * match - {String | Object} A string to match.  Can also be a regular
     *     expression literal or object.  In addition, it can be any object
     *     with a method named test.  For reqular expressions or other, if
     *     match.test(layer[property]) evaluates to true, the layer will be
     *     included in the array returned.  If no layers are found, an empty
     *     array is returned.
     *
     * Returns:
     * {Array(<OpenLayers.Control>)} A list of controls matching the given
     *     criteria.  An empty array is returned if no matches are found.
     */
    getControlsBy: function(property, match) {
        return this.getBy("controls", property, match);
    },

    /**
     * APIMethod: getControlsByClass
     * Get a list of controls of a given class (CLASS_NAME).
     *
     * Parameter:
     * match - {String | Object} A control class name.  The match can also be a
     *     regular expression literal or object.  In addition, it can be any
     *     object with a method named test.  For reqular expressions or other,
     *     if type.test(control.CLASS_NAME) evaluates to true, the control will
     *     be included in the list of controls returned.  If no controls are
     *     found, an empty array is returned.
     *
     * Returns:
     * {Array(<OpenLayers.Control>)} A list of controls matching the given class.
     *     An empty array is returned if no matches are found.
     */
    getControlsByClass: function(match) {
        return this.getControlsBy("CLASS_NAME", match);
    },

  /********************************************************/
  /*                                                      */
  /*                  Layer Functions                     */
  /*                                                      */
  /*     The following functions deal with adding and     */
  /*        removing Layers to and from the Map           */
  /*                                                      */
  /********************************************************/         

    /**
     * APIMethod: getLayer
     * Get a layer based on its id
     *
     * Parameter:
     * id - {String} A layer id
     *
     * Returns:
     * {<OpenLayers.Layer>} The Layer with the corresponding id from the map's 
     *                      layer collection, or null if not found.
     */
    getLayer: function(id) {
        var foundLayer = null;
        for (var i=0, len=this.layers.length; i<len; i++) {
            var layer = this.layers[i];
            if (layer.id == id) {
                foundLayer = layer;
                break;
            }
        }
        return foundLayer;
    },

    /**
    * Method: setLayerZIndex
    * 
    * Parameters:
    * layer - {<OpenLayers.Layer>} 
    * zIdx - {int} 
    */    
    setLayerZIndex: function (layer, zIdx) {
        layer.setZIndex(
            this.Z_INDEX_BASE[layer.isBaseLayer ? 'BaseLayer' : 'Overlay']
            + zIdx * 5 );
    },

    /**
     * Method: resetLayersZIndex
     * Reset each layer's z-index based on layer's array index
     */
    resetLayersZIndex: function() {
        for (var i=0, len=this.layers.length; i<len; i++) {
            var layer = this.layers[i];
            this.setLayerZIndex(layer, i);
        }
    },

    /**
    * APIMethod: addLayer
    *
    * Parameters:
    * layer - {<OpenLayers.Layer>} 
    */    
    addLayer: function (layer) {
        for(var i=0, len=this.layers.length; i <len; i++) {
            if (this.layers[i] == layer) {
                var msg = OpenLayers.i18n('layerAlreadyAdded', 
                                                      {'layerName':layer.name});
                OpenLayers.Console.warn(msg);
                return false;
            }
        }
        if(this.allOverlays) {
            layer.isBaseLayer = false;
        }

        if (this.events.triggerEvent("preaddlayer", {layer: layer}) === false) {
            return;
        }
        
        layer.div.className = "olLayerDiv";
        layer.div.style.overflow = "";
        this.setLayerZIndex(layer, this.layers.length);

        if (layer.isFixed) {
            this.viewPortDiv.appendChild(layer.div);
        } else {
            this.layerContainerDiv.appendChild(layer.div);
        }
        this.layers.push(layer);
        layer.setMap(this);

        if (layer.isBaseLayer || (this.allOverlays && !this.baseLayer))  {
            if (this.baseLayer == null) {
                // set the first baselaye we add as the baselayer
                this.setBaseLayer(layer);
            } else {
                layer.setVisibility(false);
            }
        } else {
            layer.redraw();
        }

        this.events.triggerEvent("addlayer", {layer: layer});
        layer.afterAdd();
    },

    /**
    * APIMethod: addLayers 
    *
    * Parameters:
    * layers - {Array(<OpenLayers.Layer>)} 
    */    
    addLayers: function (layers) {
        for (var i=0, len=layers.length; i<len; i++) {
            this.addLayer(layers[i]);
        }
    },

    /** 
     * APIMethod: removeLayer
     * Removes a layer from the map by removing its visual element (the 
     *   layer.div property), then removing it from the map's internal list 
     *   of layers, setting the layer's map property to null. 
     * 
     *   a "removelayer" event is triggered.
     * 
     *   very worthy of mention is that simply removing a layer from a map
     *   will not cause the removal of any popups which may have been created
     *   by the layer. this is due to the fact that it was decided at some
     *   point that popups would not belong to layers. thus there is no way 
     *   for us to know here to which layer the popup belongs.
     *    
     *     A simple solution to this is simply to call destroy() on the layer.
     *     the default OpenLayers.Layer class's destroy() function
     *     automatically takes care to remove itself from whatever map it has
     *     been attached to. 
     * 
     *     The correct solution is for the layer itself to register an 
     *     event-handler on "removelayer" and when it is called, if it 
     *     recognizes itself as the layer being removed, then it cycles through
     *     its own personal list of popups, removing them from the map.
     * 
     * Parameters:
     * layer - {<OpenLayers.Layer>} 
     * setNewBaseLayer - {Boolean} Default is true
     */
    removeLayer: function(layer, setNewBaseLayer) {
        if (setNewBaseLayer == null) {
            setNewBaseLayer = true;
        }

        if (layer.isFixed) {
            this.viewPortDiv.removeChild(layer.div);
        } else {
            this.layerContainerDiv.removeChild(layer.div);
        }
        OpenLayers.Util.removeItem(this.layers, layer);
        layer.removeMap(this);
        layer.map = null;

        // if we removed the base layer, need to set a new one
        if(this.baseLayer == layer) {
            this.baseLayer = null;
            if(setNewBaseLayer) {
                for(var i=0, len=this.layers.length; i<len; i++) {
                    var iLayer = this.layers[i];
                    if (iLayer.isBaseLayer || this.allOverlays) {
                        this.setBaseLayer(iLayer);
                        break;
                    }
                }
            }
        }

        this.resetLayersZIndex();

        this.events.triggerEvent("removelayer", {layer: layer});
    },

    /**
     * APIMethod: getNumLayers
     * 
     * Returns:
     * {Int} The number of layers attached to the map.
     */
    getNumLayers: function () {
        return this.layers.length;
    },

    /** 
     * APIMethod: getLayerIndex
     *
     * Parameters:
     * layer - {<OpenLayers.Layer>}
     *
     * Returns:
     * {Integer} The current (zero-based) index of the given layer in the map's
     *           layer stack. Returns -1 if the layer isn't on the map.
     */
    getLayerIndex: function (layer) {
        return OpenLayers.Util.indexOf(this.layers, layer);
    },
    
    /** 
     * APIMethod: setLayerIndex
     * Move the given layer to the specified (zero-based) index in the layer
     *     list, changing its z-index in the map display. Use
     *     map.getLayerIndex() to find out the current index of a layer. Note
     *     that this cannot (or at least should not) be effectively used to
     *     raise base layers above overlays.
     *
     * Parameters:
     * layer - {<OpenLayers.Layer>} 
     * idx - {int} 
     */
    setLayerIndex: function (layer, idx) {
        var base = this.getLayerIndex(layer);
        if (idx < 0) {
            idx = 0;
        } else if (idx > this.layers.length) {
            idx = this.layers.length;
        }
        if (base != idx) {
            this.layers.splice(base, 1);
            this.layers.splice(idx, 0, layer);
            for (var i=0, len=this.layers.length; i<len; i++) {
                this.setLayerZIndex(this.layers[i], i);
            }
            this.events.triggerEvent("changelayer", {
                layer: layer, property: "order"
            });
            if(this.allOverlays) {
                if(idx === 0) {
                    this.setBaseLayer(layer);
                } else if(this.baseLayer !== this.layers[0]) {
                    this.setBaseLayer(this.layers[0]);
                }
            }
        }
    },

    /** 
     * APIMethod: raiseLayer
     * Change the index of the given layer by delta. If delta is positive, 
     *     the layer is moved up the map's layer stack; if delta is negative,
     *     the layer is moved down.  Again, note that this cannot (or at least
     *     should not) be effectively used to raise base layers above overlays.
     *
     * Paremeters:
     * layer - {<OpenLayers.Layer>} 
     * delta - {int} 
     */
    raiseLayer: function (layer, delta) {
        var idx = this.getLayerIndex(layer) + delta;
        this.setLayerIndex(layer, idx);
    },
    
    /** 
     * APIMethod: setBaseLayer
     * Allows user to specify one of the currently-loaded layers as the Map's
     *     new base layer.
     * 
     * Parameters:
     * newBaseLayer - {<OpenLayers.Layer>}
     */
    setBaseLayer: function(newBaseLayer) {
        
        if (newBaseLayer != this.baseLayer) {
          
            // ensure newBaseLayer is already loaded
            if (OpenLayers.Util.indexOf(this.layers, newBaseLayer) != -1) {

                // preserve center and scale when changing base layers
                var center = this.getCenter();
                var newResolution = OpenLayers.Util.getResolutionFromScale(
                    this.getScale(), newBaseLayer.units
                );

                // make the old base layer invisible 
                if (this.baseLayer != null && !this.allOverlays) {
                    this.baseLayer.setVisibility(false);
                }

                // set new baselayer
                this.baseLayer = newBaseLayer;
                
                // Increment viewRequestID since the baseLayer is 
                // changing. This is used by tiles to check if they should 
                // draw themselves.
                this.viewRequestID++;
                if(!this.allOverlays || this.baseLayer.visibility) {
                    this.baseLayer.setVisibility(true);
                }

                // recenter the map
                if (center != null) {
                    // new zoom level derived from old scale
                    var newZoom = this.getZoomForResolution(
                        newResolution || this.resolution, true
                    );
                    // zoom and force zoom change
                    this.setCenter(center, newZoom, false, true);
                }

                this.events.triggerEvent("changebaselayer", {
                    layer: this.baseLayer
                });
            }        
        }
    },


  /********************************************************/
  /*                                                      */
  /*                 Control Functions                    */
  /*                                                      */
  /*     The following functions deal with adding and     */
  /*        removing Controls to and from the Map         */
  /*                                                      */
  /********************************************************/         

    /**
     * APIMethod: addControl
     * Add the passed over control to the map. Optionally 
     *     position the control at the given pixel.
     * 
     * Parameters:
     * control - {<OpenLayers.Control>}
     * px - {<OpenLayers.Pixel>}
     */    
    addControl: function (control, px) {
        this.controls.push(control);
        this.addControlToMap(control, px);
    },
    
    /**
     * APIMethod: addControls
     * Add all of the passed over controls to the map. 
     *     You can pass over an optional second array
     *     with pixel-objects to position the controls.
     *     The indices of the two arrays should match and
     *     you can add null as pixel for those controls 
     *     you want to be autopositioned.   
     *     
     * Parameters:
     * controls - {Array(<OpenLayers.Control>)}
     * pixels - {Array(<OpenLayers.Pixel>)}
     */    
    addControls: function (controls, pixels) {
        var pxs = (arguments.length === 1) ? [] : pixels;
        for (var i=0, len=controls.length; i<len; i++) {
            var ctrl = controls[i];
            var px = (pxs[i]) ? pxs[i] : null;
            this.addControl( ctrl, px );
        }
    },

    /**
     * Method: addControlToMap
     * 
     * Parameters:
     * 
     * control - {<OpenLayers.Control>}
     * px - {<OpenLayers.Pixel>}
     */    
    addControlToMap: function (control, px) {
        // If a control doesn't have a div at this point, it belongs in the
        // viewport.
        control.outsideViewport = (control.div != null);
        
        // If the map has a displayProjection, and the control doesn't, set 
        // the display projection.
        if (this.displayProjection && !control.displayProjection) {
            control.displayProjection = this.displayProjection;
        }    
        
        control.setMap(this);
        var div = control.draw(px);
        if (div) {
            if(!control.outsideViewport) {
                div.style.zIndex = this.Z_INDEX_BASE['Control'] +
                                    this.controls.length;
                this.viewPortDiv.appendChild( div );
            }
        }
        if(control.autoActivate) {
            control.activate();
        }
    },
    
    /**
     * APIMethod: getControl
     * 
     * Parameters:
     * id - {String} ID of the control to return.
     * 
     * Returns:
     * {<OpenLayers.Control>} The control from the map's list of controls 
     *                        which has a matching 'id'. If none found, 
     *                        returns null.
     */    
    getControl: function (id) {
        var returnControl = null;
        for(var i=0, len=this.controls.length; i<len; i++) {
            var control = this.controls[i];
            if (control.id == id) {
                returnControl = control;
                break;
            }
        }
        return returnControl;
    },
    
    /** 
     * APIMethod: removeControl
     * Remove a control from the map. Removes the control both from the map 
     *     object's internal array of controls, as well as from the map's 
     *     viewPort (assuming the control was not added outsideViewport)
     * 
     * Parameters:
     * control - {<OpenLayers.Control>} The control to remove.
     */    
    removeControl: function (control) {
        //make sure control is non-null and actually part of our map
        if ( (control) && (control == this.getControl(control.id)) ) {
            if (control.div && (control.div.parentNode == this.viewPortDiv)) {
                this.viewPortDiv.removeChild(control.div);
            }
            OpenLayers.Util.removeItem(this.controls, control);
        }
    },

  /********************************************************/
  /*                                                      */
  /*                  Popup Functions                     */
  /*                                                      */
  /*     The following functions deal with adding and     */
  /*        removing Popups to and from the Map           */
  /*                                                      */
  /********************************************************/         

    /** 
     * APIMethod: addPopup
     * 
     * Parameters:
     * popup - {<OpenLayers.Popup>}
     * exclusive - {Boolean} If true, closes all other popups first
     */
    addPopup: function(popup, exclusive) {

        if (exclusive) {
            //remove all other popups from screen
            for (var i = this.popups.length - 1; i >= 0; --i) {
                this.removePopup(this.popups[i]);
            }
        }

        popup.map = this;
        this.popups.push(popup);
        var popupDiv = popup.draw();
        if (popupDiv) {
            popupDiv.style.zIndex = this.Z_INDEX_BASE['Popup'] +
                                    this.popups.length;
            this.layerContainerDiv.appendChild(popupDiv);
        }
    },
    
    /** 
    * APIMethod: removePopup
    * 
    * Parameters:
    * popup - {<OpenLayers.Popup>}
    */
    removePopup: function(popup) {
        OpenLayers.Util.removeItem(this.popups, popup);
        if (popup.div) {
            try { this.layerContainerDiv.removeChild(popup.div); }
            catch (e) { } // Popups sometimes apparently get disconnected
                      // from the layerContainerDiv, and cause complaints.
        }
        popup.map = null;
    },

  /********************************************************/
  /*                                                      */
  /*              Container Div Functions                 */
  /*                                                      */
  /*   The following functions deal with the access to    */
  /*    and maintenance of the size of the container div  */
  /*                                                      */
  /********************************************************/     

    /**
     * APIMethod: getSize
     * 
     * Returns:
     * {<OpenLayers.Size>} An <OpenLayers.Size> object that represents the 
     *                     size, in pixels, of the div into which OpenLayers 
     *                     has been loaded. 
     *                     Note - A clone() of this locally cached variable is
     *                     returned, so as not to allow users to modify it.
     */
    getSize: function () {
        var size = null;
        if (this.size != null) {
            size = this.size.clone();
        }
        return size;
    },

    /**
     * APIMethod: updateSize
     * This function should be called by any external code which dynamically
     *     changes the size of the map div (because mozilla wont let us catch 
     *     the "onresize" for an element)
     */
    updateSize: function() {
        // the div might have moved on the page, also
        var newSize = this.getCurrentSize();
        if (newSize && !isNaN(newSize.h) && !isNaN(newSize.w)) {
            this.events.clearMouseCache();
            var oldSize = this.getSize();
            if (oldSize == null) {
                this.size = oldSize = newSize;
            }
            if (!newSize.equals(oldSize)) {
                
                // store the new size
                this.size = newSize;
    
                //notify layers of mapresize
                for(var i=0, len=this.layers.length; i<len; i++) {
                    this.layers[i].onMapResize();                
                }
    
                var center = this.getCenter();
    
                if (this.baseLayer != null && center != null) {
                    var zoom = this.getZoom();
                    this.zoom = null;
                    this.setCenter(center, zoom);
                }
    
            }
        }
    },
    
    /**
     * Method: getCurrentSize
     * 
     * Returns:
     * {<OpenLayers.Size>} A new <OpenLayers.Size> object with the dimensions 
     *                     of the map div
     */
    getCurrentSize: function() {

        var size = new OpenLayers.Size(this.div.clientWidth, 
                                       this.div.clientHeight);

        if (size.w == 0 && size.h == 0 || isNaN(size.w) && isNaN(size.h)) {
            size.w = this.div.offsetWidth;
            size.h = this.div.offsetHeight;
        }
        if (size.w == 0 && size.h == 0 || isNaN(size.w) && isNaN(size.h)) {
            size.w = parseInt(this.div.style.width);
            size.h = parseInt(this.div.style.height);
        }
        return size;
    },

    /** 
     * Method: calculateBounds
     * 
     * Parameters:
     * center - {<OpenLayers.LonLat>} Default is this.getCenter()
     * resolution - {float} Default is this.getResolution() 
     * 
     * Returns:
     * {<OpenLayers.Bounds>} A bounds based on resolution, center, and 
     *                       current mapsize.
     */
    calculateBounds: function(center, resolution) {

        var extent = null;
        
        if (center == null) {
            center = this.getCenter();
        }                
        if (resolution == null) {
            resolution = this.getResolution();
        }
    
        if ((center != null) && (resolution != null)) {

            var size = this.getSize();
            var w_deg = size.w * resolution;
            var h_deg = size.h * resolution;
        
            extent = new OpenLayers.Bounds(center.lon - w_deg / 2,
                                           center.lat - h_deg / 2,
                                           center.lon + w_deg / 2,
                                           center.lat + h_deg / 2);
        
        }

        return extent;
    },


  /********************************************************/
  /*                                                      */
  /*            Zoom, Center, Pan Functions               */
  /*                                                      */
  /*    The following functions handle the validation,    */
  /*   getting and setting of the Zoom Level and Center   */
  /*       as well as the panning of the Map              */
  /*                                                      */
  /********************************************************/
    /**
     * APIMethod: getCenter
     * 
     * Returns:
     * {<OpenLayers.LonLat>}
     */
    getCenter: function () {
        var center = null;
        if (this.center) {
            center = this.center.clone();
        }
        return center;
    },


    /**
     * APIMethod: getZoom
     * 
     * Returns:
     * {Integer}
     */
    getZoom: function () {
        return this.zoom;
    },
    
    /** 
     * APIMethod: pan
     * Allows user to pan by a value of screen pixels
     * 
     * Parameters:
     * dx - {Integer}
     * dy - {Integer}
     * options - {Object} Options to configure panning:
     *  - *animate* {Boolean} Use panTo instead of setCenter. Default is true.
     *  - *dragging* {Boolean} Call setCenter with dragging true.  Default is
     *    false.
     */
    pan: function(dx, dy, options) {
        options = OpenLayers.Util.applyDefaults(options, {
            animate: true,
            dragging: false
        });
        // getCenter
        var centerPx = this.getViewPortPxFromLonLat(this.getCenter());

        // adjust
        var newCenterPx = centerPx.add(dx, dy);
        
        // only call setCenter if not dragging or there has been a change
        if (!options.dragging || !newCenterPx.equals(centerPx)) {
            var newCenterLonLat = this.getLonLatFromViewPortPx(newCenterPx);
            if (options.animate) {
                this.panTo(newCenterLonLat);
            } else {
                this.setCenter(newCenterLonLat, null, options.dragging);
            }    
        }

   },
   
   /** 
     * APIMethod: panTo
     * Allows user to pan to a new lonlat
     * If the new lonlat is in the current extent the map will slide smoothly
     * 
     * Parameters:
     * lonlat - {<OpenLayers.Lonlat>}
     */
    panTo: function(lonlat) {
        if (this.panMethod && this.getExtent().scale(this.panRatio).containsLonLat(lonlat)) {
            if (!this.panTween) {
                this.panTween = new OpenLayers.Tween(this.panMethod);
            }
            var center = this.getCenter();

            // center will not change, don't do nothing
            if (lonlat.lon == center.lon &&
                lonlat.lat == center.lat) {
                return;
            }

            var from = {
                lon: center.lon,
                lat: center.lat
            };
            var to = {
                lon: lonlat.lon,
                lat: lonlat.lat
            };
            this.panTween.start(from, to, this.panDuration, {
                callbacks: {
                    start: OpenLayers.Function.bind(function(lonlat) {
                        this.events.triggerEvent("movestart");
                    }, this),
                    eachStep: OpenLayers.Function.bind(function(lonlat) {
                        lonlat = new OpenLayers.LonLat(lonlat.lon, lonlat.lat);
                        this.moveTo(lonlat, this.zoom, {
                            'dragging': true,
                            'noEvent': true
                        });
                    }, this),
                    done: OpenLayers.Function.bind(function(lonlat) {
                        lonlat = new OpenLayers.LonLat(lonlat.lon, lonlat.lat);
                        this.moveTo(lonlat, this.zoom, {
                            'noEvent': true
                        });
                        this.events.triggerEvent("moveend");
                    }, this)
                }
            });
        } else {
            this.setCenter(lonlat);
        }
    },

    /**
     * APIMethod: setCenter
     * Set the map center (and optionally, the zoom level).
     * 
     * Parameters:
     * lonlat - {<OpenLayers.LonLat>} The new center location.
     * zoom - {Integer} Optional zoom level.
     * dragging - {Boolean} Specifies whether or not to trigger 
     *                      movestart/end events
     * forceZoomChange - {Boolean} Specifies whether or not to trigger zoom 
     *                             change events (needed on baseLayer change)
     *
     * TBD: reconsider forceZoomChange in 3.0
     */
    setCenter: function(lonlat, zoom, dragging, forceZoomChange) {
        this.moveTo(lonlat, zoom, {
            'dragging': dragging,
            'forceZoomChange': forceZoomChange,
            'caller': 'setCenter'
        });
    },

    /**
     * Method: moveTo
     *
     * Parameters:
     * lonlat - {<OpenLayers.LonLat>}
     * zoom - {Integer}
     * options - {Object}
     */
    moveTo: function(lonlat, zoom, options) {
        if (!options) { 
            options = {};
        }
        if (zoom != null) {
            zoom = parseFloat(zoom);
            if (!this.fractionalZoom) {
                zoom = Math.round(zoom);
            }
        }
        // dragging is false by default
        var dragging = options.dragging;
        // forceZoomChange is false by default
        var forceZoomChange = options.forceZoomChange;
        // noEvent is false by default
        var noEvent = options.noEvent;

        if (this.panTween && options.caller == "setCenter") {
            this.panTween.stop();
        }    
             
        if (!this.center && !this.isValidLonLat(lonlat)) {
            lonlat = this.maxExtent.getCenterLonLat();
        }

        if(this.restrictedExtent != null) {
            // In 3.0, decide if we want to change interpretation of maxExtent.
            if(lonlat == null) { 
                lonlat = this.getCenter(); 
            }
            if(zoom == null) { 
                zoom = this.getZoom(); 
            }
            var resolution = this.getResolutionForZoom(zoom);
            var extent = this.calculateBounds(lonlat, resolution); 
            if(!this.restrictedExtent.containsBounds(extent)) {
                var maxCenter = this.restrictedExtent.getCenterLonLat(); 
                if(extent.getWidth() > this.restrictedExtent.getWidth()) { 
                    lonlat = new OpenLayers.LonLat(maxCenter.lon, lonlat.lat); 
                } else if(extent.left < this.restrictedExtent.left) {
                    lonlat = lonlat.add(this.restrictedExtent.left -
                                        extent.left, 0); 
                } else if(extent.right > this.restrictedExtent.right) { 
                    lonlat = lonlat.add(this.restrictedExtent.right -
                                        extent.right, 0); 
                } 
                if(extent.getHeight() > this.restrictedExtent.getHeight()) { 
                    lonlat = new OpenLayers.LonLat(lonlat.lon, maxCenter.lat); 
                } else if(extent.bottom < this.restrictedExtent.bottom) { 
                    lonlat = lonlat.add(0, this.restrictedExtent.bottom -
                                        extent.bottom); 
                } 
                else if(extent.top > this.restrictedExtent.top) { 
                    lonlat = lonlat.add(0, this.restrictedExtent.top -
                                        extent.top); 
                } 
            }
        }
        
        var zoomChanged = forceZoomChange || (
                            (this.isValidZoomLevel(zoom)) && 
                            (zoom != this.getZoom()) );

        var centerChanged = (this.isValidLonLat(lonlat)) && 
                            (!lonlat.equals(this.center));


        // if neither center nor zoom will change, no need to do anything
        if (zoomChanged || centerChanged || !dragging) {

            if (!this.dragging && !noEvent) {
                this.events.triggerEvent("movestart");
            }

            if (centerChanged) {
                if ((!zoomChanged) && (this.center)) { 
                    // if zoom hasnt changed, just slide layerContainer
                    //  (must be done before setting this.center to new value)
                    this.centerLayerContainer(lonlat);
                }
                this.center = lonlat.clone();
            }

            // (re)set the layerContainerDiv's location
            if ((zoomChanged) || (this.layerContainerOrigin == null)) {
                this.layerContainerOrigin = this.center.clone();
                this.layerContainerDiv.style.left = "0px";
                this.layerContainerDiv.style.top  = "0px";
            }

            if (zoomChanged) {
                this.zoom = zoom;
                this.resolution = this.getResolutionForZoom(zoom);
                // zoom level has changed, increment viewRequestID.
                this.viewRequestID++;
            }    
            
            var bounds = this.getExtent();
            
            //send the move call to the baselayer and all the overlays    

            if(this.baseLayer.visibility) {
                this.baseLayer.moveTo(bounds, zoomChanged, dragging);
                if(dragging) {
                    this.baseLayer.events.triggerEvent("move");
                } else {
                    this.baseLayer.events.triggerEvent("moveend",
                        {"zoomChanged": zoomChanged}
                    );
                }
            }
            
            bounds = this.baseLayer.getExtent();
            
            for (var i=0, len=this.layers.length; i<len; i++) {
                var layer = this.layers[i];
                if (layer !== this.baseLayer && !layer.isBaseLayer) {
                    var inRange = layer.calculateInRange();
                    if (layer.inRange != inRange) {
                        // the inRange property has changed. If the layer is
                        // no longer in range, we turn it off right away. If
                        // the layer is no longer out of range, the moveTo
                        // call below will turn on the layer.
                        layer.inRange = inRange;
                        if (!inRange) {
                            layer.display(false);
                        }
                        this.events.triggerEvent("changelayer", {
                            layer: layer, property: "visibility"
                        });
                    }
                    if (inRange && layer.visibility) {
                        layer.moveTo(bounds, zoomChanged, dragging);
                        if(dragging) {
                            layer.events.triggerEvent("move");
                        } else {
                            layer.events.triggerEvent("moveend",
                                {"zoomChanged": zoomChanged}
                            );
                        }
                    }
                }                
            }
            
            if (zoomChanged) {
                //redraw popups
                for (var i=0, len=this.popups.length; i<len; i++) {
                    this.popups[i].updatePosition();
                }
            }    
            
            this.events.triggerEvent("move");
    
            if (zoomChanged) { this.events.triggerEvent("zoomend"); }
        }

        // even if nothing was done, we want to notify of this
        if (!dragging && !noEvent) {
            this.events.triggerEvent("moveend");
        }
        
        // Store the map dragging state for later use
        this.dragging = !!dragging; 

    },

    /** 
     * Method: centerLayerContainer
     * This function takes care to recenter the layerContainerDiv.
     * 
     * Parameters:
     * lonlat - {<OpenLayers.LonLat>}
     */
    centerLayerContainer: function (lonlat) {

        var originPx = this.getViewPortPxFromLonLat(this.layerContainerOrigin);
        var newPx = this.getViewPortPxFromLonLat(lonlat);

        if ((originPx != null) && (newPx != null)) {
            this.layerContainerDiv.style.left = Math.round(originPx.x - newPx.x) + "px";
            this.layerContainerDiv.style.top  = Math.round(originPx.y - newPx.y) + "px";
        }
    },

    /**
     * Method: isValidZoomLevel
     * 
     * Parameters:
     * zoomLevel - {Integer}
     * 
     * Returns:
     * {Boolean} Whether or not the zoom level passed in is non-null and 
     *           within the min/max range of zoom levels.
     */
    isValidZoomLevel: function(zoomLevel) {
       return ( (zoomLevel != null) &&
                (zoomLevel >= 0) && 
                (zoomLevel < this.getNumZoomLevels()) );
    },
    
    /**
     * Method: isValidLonLat
     * 
     * Parameters:
     * lonlat - {<OpenLayers.LonLat>}
     * 
     * Returns:
     * {Boolean} Whether or not the lonlat passed in is non-null and within
     *           the maxExtent bounds
     */
    isValidLonLat: function(lonlat) {
        var valid = false;
        if (lonlat != null) {
            var maxExtent = this.getMaxExtent();
            valid = maxExtent.containsLonLat(lonlat);        
        }
        return valid;
    },

  /********************************************************/
  /*                                                      */
  /*                 Layer Options                        */
  /*                                                      */
  /*    Accessor functions to Layer Options parameters    */
  /*                                                      */
  /********************************************************/
    
    /**
     * APIMethod: getProjection
     * This method returns a string representing the projection. In 
     *     the case of projection support, this will be the srsCode which
     *     is loaded -- otherwise it will simply be the string value that
     *     was passed to the projection at startup.
     *
     * FIXME: In 3.0, we will remove getProjectionObject, and instead
     *     return a Projection object from this function. 
     * 
     * Returns:
     * {String} The Projection string from the base layer or null. 
     */
    getProjection: function() {
        var projection = this.getProjectionObject();
        return projection ? projection.getCode() : null;
    },
    
    /**
     * APIMethod: getProjectionObject
     * Returns the projection obect from the baselayer.
     *
     * Returns:
     * {<OpenLayers.Projection>} The Projection of the base layer.
     */
    getProjectionObject: function() {
        var projection = null;
        if (this.baseLayer != null) {
            projection = this.baseLayer.projection;
        }
        return projection;
    },
    
    /**
     * APIMethod: getMaxResolution
     * 
     * Returns:
     * {String} The Map's Maximum Resolution
     */
    getMaxResolution: function() {
        var maxResolution = null;
        if (this.baseLayer != null) {
            maxResolution = this.baseLayer.maxResolution;
        }
        return maxResolution;
    },
        
    /**
     * APIMethod: getMaxExtent
     *
     * Parameters:
     * options - {Object} 
     * 
     * Allowed Options:
     * restricted - {Boolean} If true, returns restricted extent (if it is 
     *     available.)
     *
     * Returns:
     * {<OpenLayers.Bounds>} The maxExtent property as set on the current 
     *     baselayer, unless the 'restricted' option is set, in which case
     *     the 'restrictedExtent' option from the map is returned (if it
     *     is set).
     */
    getMaxExtent: function (options) {
        var maxExtent = null;
        if(options && options.restricted && this.restrictedExtent){
            maxExtent = this.restrictedExtent;
        } else if (this.baseLayer != null) {
            maxExtent = this.baseLayer.maxExtent;
        }        
        return maxExtent;
    },
    
    /**
     * APIMethod: getNumZoomLevels
     * 
     * Returns:
     * {Integer} The total number of zoom levels that can be displayed by the 
     *           current baseLayer.
     */
    getNumZoomLevels: function() {
        var numZoomLevels = null;
        if (this.baseLayer != null) {
            numZoomLevels = this.baseLayer.numZoomLevels;
        }
        return numZoomLevels;
    },

  /********************************************************/
  /*                                                      */
  /*                 Baselayer Functions                  */
  /*                                                      */
  /*    The following functions, all publicly exposed     */
  /*       in the API?, are all merely wrappers to the    */
  /*       the same calls on whatever layer is set as     */
  /*                the current base layer                */
  /*                                                      */
  /********************************************************/

    /**
     * APIMethod: getExtent
     * 
     * Returns:
     * {<OpenLayers.Bounds>} A Bounds object which represents the lon/lat 
     *                       bounds of the current viewPort. 
     *                       If no baselayer is set, returns null.
     */
    getExtent: function () {
        var extent = null;
        if (this.baseLayer != null) {
            extent = this.baseLayer.getExtent();
        }
        return extent;
    },

    /**
     * APIMethod: getResolution
     * 
     * Returns:
     * {Float} The current resolution of the map. 
     *         If no baselayer is set, returns null.
     */
    getResolution: function () {
        var resolution = null;
        if (this.baseLayer != null) {
            resolution = this.baseLayer.getResolution();
        } else if(this.allOverlays === true && this.layers.length > 0) {
            // while adding the 1st layer to the map in allOverlays mode,
            // this.baseLayer is not set yet when we need the resolution
            // for calculateInRange.
            resolution = this.layers[0].getResolution();
        }
        return resolution;
    },

    /**
     * APIMethod: getUnits
     * 
     * Returns:
     * {Float} The current units of the map. 
     *         If no baselayer is set, returns null.
     */
    getUnits: function () {
        var units = null;
        if (this.baseLayer != null) {
            units = this.baseLayer.units;
        }
        return units;
    },

     /**
      * APIMethod: getScale
      * 
      * Returns:
      * {Float} The current scale denominator of the map. 
      *         If no baselayer is set, returns null.
      */
    getScale: function () {
        var scale = null;
        if (this.baseLayer != null) {
            var res = this.getResolution();
            var units = this.baseLayer.units;
            scale = OpenLayers.Util.getScaleFromResolution(res, units);
        }
        return scale;
    },


    /**
     * APIMethod: getZoomForExtent
     * 
     * Parameters: 
     * bounds - {<OpenLayers.Bounds>}
     * closest - {Boolean} Find the zoom level that most closely fits the 
     *     specified bounds. Note that this may result in a zoom that does 
     *     not exactly contain the entire extent.
     *     Default is false.
     * 
     * Returns:
     * {Integer} A suitable zoom level for the specified bounds.
     *           If no baselayer is set, returns null.
     */
    getZoomForExtent: function (bounds, closest) {
        var zoom = null;
        if (this.baseLayer != null) {
            zoom = this.baseLayer.getZoomForExtent(bounds, closest);
        }
        return zoom;
    },

    /**
     * APIMethod: getResolutionForZoom
     * 
     * Parameter:
     * zoom - {Float}
     * 
     * Returns:
     * {Float} A suitable resolution for the specified zoom.  If no baselayer
     *     is set, returns null.
     */
    getResolutionForZoom: function(zoom) {
        var resolution = null;
        if(this.baseLayer) {
            resolution = this.baseLayer.getResolutionForZoom(zoom);
        }
        return resolution;
    },

    /**
     * APIMethod: getZoomForResolution
     * 
     * Parameter:
     * resolution - {Float}
     * closest - {Boolean} Find the zoom level that corresponds to the absolute 
     *     closest resolution, which may result in a zoom whose corresponding
     *     resolution is actually smaller than we would have desired (if this
     *     is being called from a getZoomForExtent() call, then this means that
     *     the returned zoom index might not actually contain the entire 
     *     extent specified... but it'll be close).
     *     Default is false.
     * 
     * Returns:
     * {Integer} A suitable zoom level for the specified resolution.
     *           If no baselayer is set, returns null.
     */
    getZoomForResolution: function(resolution, closest) {
        var zoom = null;
        if (this.baseLayer != null) {
            zoom = this.baseLayer.getZoomForResolution(resolution, closest);
        }
        return zoom;
    },

  /********************************************************/
  /*                                                      */
  /*                  Zooming Functions                   */
  /*                                                      */
  /*    The following functions, all publicly exposed     */
  /*       in the API, are all merely wrappers to the     */
  /*               the setCenter() function               */
  /*                                                      */
  /********************************************************/
  
    /** 
     * APIMethod: zoomTo
     * Zoom to a specific zoom level
     * 
     * Parameters:
     * zoom - {Integer}
     */
    zoomTo: function(zoom) {
        if (this.isValidZoomLevel(zoom)) {
            this.setCenter(null, zoom);
        }
    },
    
    /**
     * APIMethod: zoomIn
     * 
     * Parameters:
     * zoom - {int}
     */
    zoomIn: function() {
        this.zoomTo(this.getZoom() + 1);
    },
    
    /**
     * APIMethod: zoomOut
     * 
     * Parameters:
     * zoom - {int}
     */
    zoomOut: function() {
        this.zoomTo(this.getZoom() - 1);
    },

    /**
     * APIMethod: zoomToExtent
     * Zoom to the passed in bounds, recenter
     * 
     * Parameters:
     * bounds - {<OpenLayers.Bounds>}
     * closest - {Boolean} Find the zoom level that most closely fits the 
     *     specified bounds. Note that this may result in a zoom that does 
     *     not exactly contain the entire extent.
     *     Default is false.
     * 
     */
    zoomToExtent: function(bounds, closest) {
        var center = bounds.getCenterLonLat();
        if (this.baseLayer.wrapDateLine) {
            var maxExtent = this.getMaxExtent();

            //fix straddling bounds (in the case of a bbox that straddles the 
            // dateline, it's left and right boundaries will appear backwards. 
            // we fix this by allowing a right value that is greater than the
            // max value at the dateline -- this allows us to pass a valid 
            // bounds to calculate zoom)
            //
            bounds = bounds.clone();
            while (bounds.right < bounds.left) {
                bounds.right += maxExtent.getWidth();
            }
            //if the bounds was straddling (see above), then the center point 
            // we got from it was wrong. So we take our new bounds and ask it
            // for the center. Because our new bounds is at least partially 
            // outside the bounds of maxExtent, the new calculated center 
            // might also be. We don't want to pass a bad center value to 
            // setCenter, so we have it wrap itself across the date line.
            //
            center = bounds.getCenterLonLat().wrapDateLine(maxExtent);
        }
        this.setCenter(center, this.getZoomForExtent(bounds, closest));
    },

    /** 
     * APIMethod: zoomToMaxExtent
     * Zoom to the full extent and recenter.
     *
     * Parameters:
     * options - 
     * 
     * Allowed Options:
     * restricted - {Boolean} True to zoom to restricted extent if it is 
     *     set. Defaults to true.
     */
    zoomToMaxExtent: function(options) {
        //restricted is true by default
        var restricted = (options) ? options.restricted : true;

        var maxExtent = this.getMaxExtent({
            'restricted': restricted 
        });
        this.zoomToExtent(maxExtent);
    },

    /** 
     * APIMethod: zoomToScale
     * Zoom to a specified scale 
     * 
     * Parameters:
     * scale - {float}
     * closest - {Boolean} Find the zoom level that most closely fits the 
     *     specified scale. Note that this may result in a zoom that does 
     *     not exactly contain the entire extent.
     *     Default is false.
     * 
     */
    zoomToScale: function(scale, closest) {
        var res = OpenLayers.Util.getResolutionFromScale(scale, 
                                                         this.baseLayer.units);
        var size = this.getSize();
        var w_deg = size.w * res;
        var h_deg = size.h * res;
        var center = this.getCenter();

        var extent = new OpenLayers.Bounds(center.lon - w_deg / 2,
                                           center.lat - h_deg / 2,
                                           center.lon + w_deg / 2,
                                           center.lat + h_deg / 2);
        this.zoomToExtent(extent, closest);
    },
    
  /********************************************************/
  /*                                                      */
  /*             Translation Functions                    */
  /*                                                      */
  /*      The following functions translate between       */
  /*           LonLat, LayerPx, and ViewPortPx            */
  /*                                                      */
  /********************************************************/
      
  //
  // TRANSLATION: LonLat <-> ViewPortPx
  //

    /**
     * Method: getLonLatFromViewPortPx
     * 
     * Parameters:
     * viewPortPx - {<OpenLayers.Pixel>}
     * 
     * Returns:
     * {<OpenLayers.LonLat>} An OpenLayers.LonLat which is the passed-in view 
     *                       port <OpenLayers.Pixel>, translated into lon/lat
     *                       by the current base layer.
     */
    getLonLatFromViewPortPx: function (viewPortPx) {
        var lonlat = null; 
        if (this.baseLayer != null) {
            lonlat = this.baseLayer.getLonLatFromViewPortPx(viewPortPx);
        }
        return lonlat;
    },

    /**
     * APIMethod: getViewPortPxFromLonLat
     * 
     * Parameters:
     * lonlat - {<OpenLayers.LonLat>}
     * 
     * Returns:
     * {<OpenLayers.Pixel>} An OpenLayers.Pixel which is the passed-in 
     *                      <OpenLayers.LonLat>, translated into view port 
     *                      pixels by the current base layer.
     */
    getViewPortPxFromLonLat: function (lonlat) {
        var px = null; 
        if (this.baseLayer != null) {
            px = this.baseLayer.getViewPortPxFromLonLat(lonlat);
        }
        return px;
    },

    
  //
  // CONVENIENCE TRANSLATION FUNCTIONS FOR API
  //

    /**
     * APIMethod: getLonLatFromPixel
     * 
     * Parameters:
     * px - {<OpenLayers.Pixel>}
     *
     * Returns:
     * {<OpenLayers.LonLat>} An OpenLayers.LonLat corresponding to the given
     *                       OpenLayers.Pixel, translated into lon/lat by the 
     *                       current base layer
     */
    getLonLatFromPixel: function (px) {
        return this.getLonLatFromViewPortPx(px);
    },

    /**
     * APIMethod: getPixelFromLonLat
     * Returns a pixel location given a map location.  The map location is
     *     translated to an integer pixel location (in viewport pixel
     *     coordinates) by the current base layer.
     * 
     * Parameters:
     * lonlat - {<OpenLayers.LonLat>} A map location.
     * 
     * Returns: 
     * {<OpenLayers.Pixel>} An OpenLayers.Pixel corresponding to the 
     *     <OpenLayers.LonLat> translated into view port pixels by the current
     *     base layer.
     */
    getPixelFromLonLat: function (lonlat) {
        var px = this.getViewPortPxFromLonLat(lonlat);
        px.x = Math.round(px.x);
        px.y = Math.round(px.y);
        return px;
    },



  //
  // TRANSLATION: ViewPortPx <-> LayerPx
  //

    /**
     * APIMethod: getViewPortPxFromLayerPx
     * 
     * Parameters:
     * layerPx - {<OpenLayers.Pixel>}
     * 
     * Returns:
     * {<OpenLayers.Pixel>} Layer Pixel translated into ViewPort Pixel 
     *                      coordinates
     */
    getViewPortPxFromLayerPx:function(layerPx) {
        var viewPortPx = null;
        if (layerPx != null) {
            var dX = parseInt(this.layerContainerDiv.style.left);
            var dY = parseInt(this.layerContainerDiv.style.top);
            viewPortPx = layerPx.add(dX, dY);            
        }
        return viewPortPx;
    },
    
    /**
     * APIMethod: getLayerPxFromViewPortPx
     * 
     * Parameters:
     * viewPortPx - {<OpenLayers.Pixel>}
     * 
     * Returns:
     * {<OpenLayers.Pixel>} ViewPort Pixel translated into Layer Pixel 
     *                      coordinates
     */
    getLayerPxFromViewPortPx:function(viewPortPx) {
        var layerPx = null;
        if (viewPortPx != null) {
            var dX = -parseInt(this.layerContainerDiv.style.left);
            var dY = -parseInt(this.layerContainerDiv.style.top);
            layerPx = viewPortPx.add(dX, dY);
            if (isNaN(layerPx.x) || isNaN(layerPx.y)) {
                layerPx = null;
            }
        }
        return layerPx;
    },
    
  //
  // TRANSLATION: LonLat <-> LayerPx
  //

    /**
     * Method: getLonLatFromLayerPx
     * 
     * Parameters:
     * px - {<OpenLayers.Pixel>}
     *
     * Returns:
     * {<OpenLayers.LonLat>}
     */
    getLonLatFromLayerPx: function (px) {
       //adjust for displacement of layerContainerDiv
       px = this.getViewPortPxFromLayerPx(px);
       return this.getLonLatFromViewPortPx(px);         
    },
    
    /**
     * APIMethod: getLayerPxFromLonLat
     * 
     * Parameters:
     * lonlat - {<OpenLayers.LonLat>} lonlat
     *
     * Returns:
     * {<OpenLayers.Pixel>} An OpenLayers.Pixel which is the passed-in 
     *                      <OpenLayers.LonLat>, translated into layer pixels 
     *                      by the current base layer
     */
    getLayerPxFromLonLat: function (lonlat) {
       //adjust for displacement of layerContainerDiv
       var px = this.getPixelFromLonLat(lonlat);
       return this.getLayerPxFromViewPortPx(px);         
    },

    CLASS_NAME: "OpenLayers.Map"
});

/**
 * Constant: TILE_WIDTH
 * {Integer} 256 Default tile width (unless otherwise specified)
 */
OpenLayers.Map.TILE_WIDTH = 256;
/**
 * Constant: TILE_HEIGHT
 * {Integer} 256 Default tile height (unless otherwise specified)
 */
OpenLayers.Map.TILE_HEIGHT = 256;
/* ======================================================================
    OpenLayers/Marker.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */


/**
 * @requires OpenLayers/Events.js
 * @requires OpenLayers/Icon.js
 */

/**
 * Class: OpenLayers.Marker
 * Instances of OpenLayers.Marker are a combination of a 
 * <OpenLayers.LonLat> and an <OpenLayers.Icon>.  
 *
 * Markers are generally added to a special layer called
 * <OpenLayers.Layer.Markers>.
 *
 * Example:
 * (code)
 * var markers = new OpenLayers.Layer.Markers( "Markers" );
 * map.addLayer(markers);
 *
 * var size = new OpenLayers.Size(10,17);
 * var offset = new OpenLayers.Pixel(-(size.w/2), -size.h);
 * var icon = new OpenLayers.Icon('http://boston.openguides.org/markers/AQUA.png',size,offset);
 * markers.addMarker(new OpenLayers.Marker(new OpenLayers.LonLat(0,0),icon));
 * markers.addMarker(new OpenLayers.Marker(new OpenLayers.LonLat(0,0),icon.clone()));
 *
 * (end)
 *
 * Note that if you pass an icon into the Marker constructor, it will take
 * that icon and use it. This means that you should not share icons between
 * markers -- you use them once, but you should clone() for any additional
 * markers using that same icon.
 */
OpenLayers.Marker = OpenLayers.Class({
    
    /** 
     * Property: icon 
     * {<OpenLayers.Icon>} The icon used by this marker.
     */
    icon: null,

    /** 
     * Property: lonlat 
     * {<OpenLayers.LonLat>} location of object
     */
    lonlat: null,
    
    /** 
     * Property: events 
     * {<OpenLayers.Events>} the event handler.
     */
    events: null,
    
    /** 
     * Property: map 
     * {<OpenLayers.Map>} the map this marker is attached to
     */
    map: null,
    
    /** 
     * Constructor: OpenLayers.Marker
     * Parameters:
     * lonlat - {<OpenLayers.LonLat>} the position of this marker
     * icon - {<OpenLayers.Icon>}  the icon for this marker
     */
    initialize: function(lonlat, icon) {
        this.lonlat = lonlat;
        
        var newIcon = (icon) ? icon : OpenLayers.Marker.defaultIcon();
        if (this.icon == null) {
            this.icon = newIcon;
        } else {
            this.icon.url = newIcon.url;
            this.icon.size = newIcon.size;
            this.icon.offset = newIcon.offset;
            this.icon.calculateOffset = newIcon.calculateOffset;
        }
        this.events = new OpenLayers.Events(this, this.icon.imageDiv, null);
    },
    
    /**
     * APIMethod: destroy
     * Destroy the marker. You must first remove the marker from any 
     * layer which it has been added to, or you will get buggy behavior.
     * (This can not be done within the marker since the marker does not
     * know which layer it is attached to.)
     */
    destroy: function() {
        // erase any drawn features
        this.erase();

        this.map = null;

        this.events.destroy();
        this.events = null;

        if (this.icon != null) {
            this.icon.destroy();
            this.icon = null;
        }
    },
    
    /** 
    * Method: draw
    * Calls draw on the icon, and returns that output.
    * 
    * Parameters:
    * px - {<OpenLayers.Pixel>}
    * 
    * Returns:
    * {DOMElement} A new DOM Image with this marker's icon set at the 
    * location passed-in
    */
    draw: function(px) {
        return this.icon.draw(px);
    }, 

    /** 
    * Method: erase
    * Erases any drawn elements for this marker.
    */
    erase: function() {
        if (this.icon != null) {
            this.icon.erase();
        }
    }, 

    /**
    * Method: moveTo
    * Move the marker to the new location.
    *
    * Parameters:
    * px - {<OpenLayers.Pixel>} the pixel position to move to
    */
    moveTo: function (px) {
        if ((px != null) && (this.icon != null)) {
            this.icon.moveTo(px);
        }           
        this.lonlat = this.map.getLonLatFromLayerPx(px);
    },

    /**
     * APIMethod: isDrawn
     * 
     * Returns:
     * {Boolean} Whether or not the marker is drawn.
     */
    isDrawn: function() {
        var isDrawn = (this.icon && this.icon.isDrawn());
        return isDrawn;   
    },

    /**
     * Method: onScreen
     *
     * Returns:
     * {Boolean} Whether or not the marker is currently visible on screen.
     */
    onScreen:function() {
        
        var onScreen = false;
        if (this.map) {
            var screenBounds = this.map.getExtent();
            onScreen = screenBounds.containsLonLat(this.lonlat);
        }    
        return onScreen;
    },
    
    /**
     * Method: inflate
     * Englarges the markers icon by the specified ratio.
     *
     * Parameters:
     * inflate - {float} the ratio to enlarge the marker by (passing 2
     *                   will double the size).
     */
    inflate: function(inflate) {
        if (this.icon) {
            var newSize = new OpenLayers.Size(this.icon.size.w * inflate,
                                              this.icon.size.h * inflate);
            this.icon.setSize(newSize);
        }        
    },
    
    /** 
     * Method: setOpacity
     * Change the opacity of the marker by changin the opacity of 
     *   its icon
     * 
     * Parameters:
     * opacity - {float}  Specified as fraction (0.4, etc)
     */
    setOpacity: function(opacity) {
        this.icon.setOpacity(opacity);
    },

    /**
     * Method: setUrl
     * Change URL of the Icon Image.
     * 
     * url - {String} 
     */
    setUrl: function(url) {
        this.icon.setUrl(url);
    },    

    /** 
     * Method: display
     * Hide or show the icon
     * 
     * display - {Boolean} 
     */
    display: function(display) {
        this.icon.display(display);
    },

    CLASS_NAME: "OpenLayers.Marker"
});


/**
 * Function: defaultIcon
 * Creates a default <OpenLayers.Icon>.
 * 
 * Returns:
 * {<OpenLayers.Icon>} A default OpenLayers.Icon to use for a marker
 */
OpenLayers.Marker.defaultIcon = function() {
    var url = OpenLayers.Util.getImagesLocation() + "marker.png";
    var size = new OpenLayers.Size(21, 25);
    var calculateOffset = function(size) {
                    return new OpenLayers.Pixel(-(size.w/2), -size.h);
                 };

    return new OpenLayers.Icon(url, size, null, calculateOffset);        
};
    

/* ======================================================================
    OpenLayers/Tile/Image.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */


/**
 * @requires OpenLayers/Tile.js
 */

/**
 * Class: OpenLayers.Tile.Image
 * Instances of OpenLayers.Tile.Image are used to manage the image tiles
 * used by various layers.  Create a new image tile with the
 * <OpenLayers.Tile.Image> constructor.
 *
 * Inherits from:
 *  - <OpenLayers.Tile>
 */
OpenLayers.Tile.Image = OpenLayers.Class(OpenLayers.Tile, {

    /** 
     * Property: url
     * {String} The URL of the image being requested. No default. Filled in by
     * layer.getURL() function. 
     */
    url: null,
    
    /** 
     * Property: imgDiv
     * {DOMElement} The div element which wraps the image.
     */
    imgDiv: null,

    /**
     * Property: frame
     * {DOMElement} The image element is appended to the frame.  Any gutter on
     * the image will be hidden behind the frame. 
     */ 
    frame: null, 
    
    /**
     * Property: layerAlphaHack
     * {Boolean} True if the png alpha hack needs to be applied on the layer's div.
     */
    layerAlphaHack: null,
    
    /**
     * Property: isBackBuffer
     * {Boolean} Is this tile a back buffer tile?
     */
    isBackBuffer: false,
    
    /**
     * Property: lastRatio
     * {Float} Used in transition code only.  This is the previous ratio
     *     of the back buffer tile resolution to the map resolution.  Compared
     *     with the current ratio to determine if zooming occurred.
     */
    lastRatio: 1,

    /**
     * Property: isFirstDraw
     * {Boolean} Is this the first time the tile is being drawn?
     *     This is used to force resetBackBuffer to synchronize
     *     the backBufferTile with the foreground tile the first time
     *     the foreground tile loads so that if the user zooms
     *     before the layer has fully loaded, the backBufferTile for
     *     tiles that have been loaded can be used.
     */
    isFirstDraw: true,
        
    /**
     * Property: backBufferTile
     * {<OpenLayers.Tile>} A clone of the tile used to create transition
     *     effects when the tile is moved or changes resolution.
     */
    backBufferTile: null,

    /** TBD 3.0 - reorder the parameters to the init function to remove 
     *             URL. the getUrl() function on the layer gets called on 
     *             each draw(), so no need to specify it here.
     * 
     * Constructor: OpenLayers.Tile.Image
     * Constructor for a new <OpenLayers.Tile.Image> instance.
     * 
     * Parameters:
     * layer - {<OpenLayers.Layer>} layer that the tile will go in.
     * position - {<OpenLayers.Pixel>}
     * bounds - {<OpenLayers.Bounds>}
     * url - {<String>} Deprecated. Remove me in 3.0.
     * size - {<OpenLayers.Size>}
     */   
    initialize: function(layer, position, bounds, url, size) {
        OpenLayers.Tile.prototype.initialize.apply(this, arguments);

        this.url = url; //deprecated remove me
        
        this.frame = document.createElement('div'); 
        this.frame.style.overflow = 'hidden'; 
        this.frame.style.position = 'absolute'; 

        this.layerAlphaHack = this.layer.alpha && OpenLayers.Util.alphaHack();
    },

    /** 
     * APIMethod: destroy
     * nullify references to prevent circular references and memory leaks
     */
    destroy: function() {
        if (this.imgDiv != null)  {
            if (this.layerAlphaHack) {
                // unregister the "load" handler
                OpenLayers.Event.stopObservingElement(this.imgDiv.childNodes[0]);                
            }

            // unregister the "load" and "error" handlers. Only the "error" handler if
            // this.layerAlphaHack is true.
            OpenLayers.Event.stopObservingElement(this.imgDiv);
            
            if (this.imgDiv.parentNode == this.frame) {
                this.frame.removeChild(this.imgDiv);
                this.imgDiv.map = null;
            }
            this.imgDiv.urls = null;
            // abort any currently loading image
            this.imgDiv.src = OpenLayers.Util.getImagesLocation() + "blank.gif";
        }
        this.imgDiv = null;
        if ((this.frame != null) && (this.frame.parentNode == this.layer.div)) { 
            this.layer.div.removeChild(this.frame); 
        }
        this.frame = null; 
        
        /* clean up the backBufferTile if it exists */
        if (this.backBufferTile) {
            this.backBufferTile.destroy();
            this.backBufferTile = null;
        }
        
        this.layer.events.unregister("loadend", this, this.resetBackBuffer);
        
        OpenLayers.Tile.prototype.destroy.apply(this, arguments);
    },

    /**
     * Method: clone
     *
     * Parameters:
     * obj - {<OpenLayers.Tile.Image>} The tile to be cloned
     *
     * Returns:
     * {<OpenLayers.Tile.Image>} An exact clone of this <OpenLayers.Tile.Image>
     */
    clone: function (obj) {
        if (obj == null) {
            obj = new OpenLayers.Tile.Image(this.layer, 
                                            this.position, 
                                            this.bounds, 
                                            this.url, 
                                            this.size);        
        } 
        
        //pick up properties from superclass
        obj = OpenLayers.Tile.prototype.clone.apply(this, [obj]);
        
        //dont want to directly copy the image div
        obj.imgDiv = null;
            
        
        return obj;
    },
    
    /**
     * Method: draw
     * Check that a tile should be drawn, and draw it.
     * 
     * Returns:
     * {Boolean} Always returns true.
     */
    draw: function() {
        if (this.layer != this.layer.map.baseLayer && this.layer.reproject) {
            this.bounds = this.getBoundsFromBaseLayer(this.position);
        }
        var drawTile = OpenLayers.Tile.prototype.draw.apply(this, arguments);
        
        if (OpenLayers.Util.indexOf(this.layer.SUPPORTED_TRANSITIONS, this.layer.transitionEffect) != -1) {
            if (drawTile) {
                //we use a clone of this tile to create a double buffer for visual
                //continuity.  The backBufferTile is used to create transition
                //effects while the tile in the grid is repositioned and redrawn
                if (!this.backBufferTile) {
                    this.backBufferTile = this.clone();
                    this.backBufferTile.hide();
                    // this is important.  It allows the backBuffer to place itself
                    // appropriately in the DOM.  The Image subclass needs to put
                    // the backBufferTile behind the main tile so the tiles can
                    // load over top and display as soon as they are loaded.
                    this.backBufferTile.isBackBuffer = true;
                    
                    // potentially end any transition effects when the tile loads
                    this.events.register('loadend', this, this.resetBackBuffer);
                    
                    // clear transition back buffer tile only after all tiles in
                    // this layer have loaded to avoid visual glitches
                    this.layer.events.register("loadend", this, this.resetBackBuffer);
                }
                // run any transition effects
                this.startTransition();
            } else {
                // if we aren't going to draw the tile, then the backBuffer should
                // be hidden too!
                if (this.backBufferTile) {
                    this.backBufferTile.clear();
                }
            }
        } else {
            if (drawTile && this.isFirstDraw) {
                this.events.register('loadend', this, this.showTile);
                this.isFirstDraw = false;
            }   
        }    
        
        if (!drawTile) {
            return false;
        }
        
        if (this.isLoading) {
            //if we're already loading, send 'reload' instead of 'loadstart'.
            this.events.triggerEvent("reload"); 
        } else {
            this.isLoading = true;
            this.events.triggerEvent("loadstart");
        }
        
        return this.renderTile();
    },
    
    /** 
     * Method: resetBackBuffer
     * Triggered by two different events, layer loadend, and tile loadend.
     *     In any of these cases, we check to see if we can hide the 
     *     backBufferTile yet and update its parameters to match the 
     *     foreground tile.
     *
     * Basic logic:
     *  - If the backBufferTile hasn't been drawn yet, reset it
     *  - If layer is still loading, show foreground tile but don't hide
     *    the backBufferTile yet
     *  - If layer is done loading, reset backBuffer tile and show 
     *    foreground tile
     */
    resetBackBuffer: function() {
        this.showTile();
        if (this.backBufferTile && 
            (this.isFirstDraw || !this.layer.numLoadingTiles)) {
            this.isFirstDraw = false;
            // check to see if the backBufferTile is within the max extents
            // before rendering it 
            var maxExtent = this.layer.maxExtent;
            var withinMaxExtent = (maxExtent &&
                                   this.bounds.intersectsBounds(maxExtent, false));
            if (withinMaxExtent) {
                this.backBufferTile.position = this.position;
                this.backBufferTile.bounds = this.bounds;
                this.backBufferTile.size = this.size;
                this.backBufferTile.imageSize = this.layer.getImageSize(this.bounds) || this.size;
                this.backBufferTile.imageOffset = this.layer.imageOffset;
                this.backBufferTile.resolution = this.layer.getResolution();
                this.backBufferTile.renderTile();
            }

            this.backBufferTile.hide();
        }
    },
    
    /**
     * Method: renderTile
     * Internal function to actually initialize the image tile,
     *     position it correctly, and set its url.
     */
    renderTile: function() {
        if (this.imgDiv == null) {
            this.initImgDiv();
        }

        this.imgDiv.viewRequestID = this.layer.map.viewRequestID;
        
        if (this.layer.async) {
            // Asyncronous image requests call the asynchronous getURL method
            // on the layer to fetch an image that covers 'this.bounds', in the scope of
            // 'this', setting the 'url' property of the layer itself, and running
            // the callback 'positionFrame' when the image request returns.
            this.layer.getURLasync(this.bounds, this, "url", this.positionImage);
        } else {
            // syncronous image requests get the url and position the frame immediately,
            // and don't wait for an image request to come back.
          
            // needed for changing to a different server for onload error
            if (this.layer.url instanceof Array) {
                this.imgDiv.urls = this.layer.url.slice();
            }
          
            this.url = this.layer.getURL(this.bounds);
          
            // position the frame immediately
            this.positionImage(); 
        }
        return true;
    },

    /**
     * Method: positionImage
     * Using the properties currenty set on the layer, position the tile correctly.
     * This method is used both by the async and non-async versions of the Tile.Image
     * code.
     */
     positionImage: function() {
        // if the this layer doesn't exist at the point the image is
        // returned, do not attempt to use it for size computation
        if ( this.layer == null )
            return;
        
        // position the frame 
        OpenLayers.Util.modifyDOMElement(this.frame, 
                                          null, this.position, this.size);   

        var imageSize = this.layer.getImageSize(this.bounds); 
        if (this.layerAlphaHack) {
            OpenLayers.Util.modifyAlphaImageDiv(this.imgDiv,
                    null, null, imageSize, this.url);
        } else {
            OpenLayers.Util.modifyDOMElement(this.imgDiv,
                    null, null, imageSize) ;
            this.imgDiv.src = this.url;
        }
    },

    /** 
     * Method: clear
     *  Clear the tile of any bounds/position-related data so that it can 
     *   be reused in a new location.
     */
    clear: function() {
        if(this.imgDiv) {
            this.hide();
            if (OpenLayers.Tile.Image.useBlankTile) { 
                this.imgDiv.src = OpenLayers.Util.getImagesLocation() + "blank.gif";
            }    
        }
    },

    /**
     * Method: initImgDiv
     * Creates the imgDiv property on the tile.
     */
    initImgDiv: function() {
        
        var offset = this.layer.imageOffset; 
        var size = this.layer.getImageSize(this.bounds); 
     
        if (this.layerAlphaHack) {
            this.imgDiv = OpenLayers.Util.createAlphaImageDiv(null,
                                                           offset,
                                                           size,
                                                           null,
                                                           "relative",
                                                           null,
                                                           null,
                                                           null,
                                                           true);
        } else {
            this.imgDiv = OpenLayers.Util.createImage(null,
                                                      offset,
                                                      size,
                                                      null,
                                                      "relative",
                                                      null,
                                                      null,
                                                      true);
        }
        
        this.imgDiv.className = 'olTileImage';

        /* checkImgURL used to be used to called as a work around, but it
           ended up hiding problems instead of solving them and broke things
           like relative URLs. See discussion on the dev list:
           http://openlayers.org/pipermail/dev/2007-January/000205.html

        OpenLayers.Event.observe( this.imgDiv, "load",
            OpenLayers.Function.bind(this.checkImgURL, this) );
        */
        this.frame.style.zIndex = this.isBackBuffer ? 0 : 1;
        this.frame.appendChild(this.imgDiv); 
        this.layer.div.appendChild(this.frame); 

        if(this.layer.opacity != null) {
            
            OpenLayers.Util.modifyDOMElement(this.imgDiv, null, null, null,
                                             null, null, null, 
                                             this.layer.opacity);
        }

        // we need this reference to check back the viewRequestID
        this.imgDiv.map = this.layer.map;

        //bind a listener to the onload of the image div so that we 
        // can register when a tile has finished loading.
        var onload = function() {
            
            //normally isLoading should always be true here but there are some 
            // right funky conditions where loading and then reloading a tile
            // with the same url *really*fast*. this check prevents sending 
            // a 'loadend' if the msg has already been sent
            //
            if (this.isLoading) { 
                this.isLoading = false; 
                this.events.triggerEvent("loadend"); 
            }
        };
        
        if (this.layerAlphaHack) { 
            OpenLayers.Event.observe(this.imgDiv.childNodes[0], 'load', 
                                     OpenLayers.Function.bind(onload, this));    
        } else { 
            OpenLayers.Event.observe(this.imgDiv, 'load', 
                                 OpenLayers.Function.bind(onload, this)); 
        } 
        

        // Bind a listener to the onerror of the image div so that we
        // can registere when a tile has finished loading with errors.
        var onerror = function() {

            // If we have gone through all image reload attempts, it is time
            // to realize that we are done with this image. Since
            // OpenLayers.Util.onImageLoadError already has taken care about
            // the error, we can continue as if the image was loaded
            // successfully.
            if (this.imgDiv._attempts > OpenLayers.IMAGE_RELOAD_ATTEMPTS) {
                onload.call(this);
            }
        };
        OpenLayers.Event.observe(this.imgDiv, "error",
                                 OpenLayers.Function.bind(onerror, this));
    },

    /**
     * Method: checkImgURL
     * Make sure that the image that just loaded is the one this tile is meant
     * to display, since panning/zooming might have changed the tile's URL in
     * the meantime. If the tile URL did change before the image loaded, set
     * the imgDiv display to 'none', as either (a) it will be reset to visible
     * when the new URL loads in the image, or (b) we don't want to display
     * this tile after all because its new bounds are outside our maxExtent.
     * 
     * This function should no longer  be neccesary with the improvements to
     * Grid.js in OpenLayers 2.3. The lack of a good isEquivilantURL function
     * caused problems in 2.2, but it's possible that with the improved 
     * isEquivilant URL function, this might be neccesary at some point.
     * 
     * See discussion in the thread at 
     * http://openlayers.org/pipermail/dev/2007-January/000205.html
     */
    checkImgURL: function () {
        // Sometimes our image will load after it has already been removed
        // from the map, in which case this check is not needed.  
        if (this.layer) {
            var loaded = this.layerAlphaHack ? this.imgDiv.firstChild.src : this.imgDiv.src;
            if (!OpenLayers.Util.isEquivalentUrl(loaded, this.url)) {
                this.hide();
            }
        }
    },
    
    /**
     * Method: startTransition
     * This method is invoked on tiles that are backBuffers for tiles in the
     *     grid.  The grid tile is about to be cleared and a new tile source
     *     loaded.  This is where the transition effect needs to be started
     *     to provide visual continuity.
     */
    startTransition: function() {
        // backBufferTile has to be valid and ready to use
        if (!this.backBufferTile || !this.backBufferTile.imgDiv) {
            return;
        }

        // calculate the ratio of change between the current resolution of the
        // backBufferTile and the layer.  If several animations happen in a
        // row, then the backBufferTile will scale itself appropriately for
        // each request.
        var ratio = 1;
        if (this.backBufferTile.resolution) {
            ratio = this.backBufferTile.resolution / this.layer.getResolution();
        }
        
        // if the ratio is not the same as it was last time (i.e. we are
        // zooming), then we need to adjust the backBuffer tile
        if (ratio != this.lastRatio) {
            if (this.layer.transitionEffect == 'resize') {
                // In this case, we can just immediately resize the 
                // backBufferTile.
                var upperLeft = new OpenLayers.LonLat(
                    this.backBufferTile.bounds.left, 
                    this.backBufferTile.bounds.top
                );
                var size = new OpenLayers.Size(
                    this.backBufferTile.size.w * ratio,
                    this.backBufferTile.size.h * ratio
                );

                var px = this.layer.map.getLayerPxFromLonLat(upperLeft);
                OpenLayers.Util.modifyDOMElement(this.backBufferTile.frame, 
                                                 null, px, size);
                var imageSize = this.backBufferTile.imageSize;
                imageSize = new OpenLayers.Size(imageSize.w * ratio, 
                                                imageSize.h * ratio);
                var imageOffset = this.backBufferTile.imageOffset;
                if(imageOffset) {
                    imageOffset = new OpenLayers.Pixel(
                        imageOffset.x * ratio, imageOffset.y * ratio
                    );
                }

                OpenLayers.Util.modifyDOMElement(
                    this.backBufferTile.imgDiv, null, imageOffset, imageSize
                ) ;

                this.backBufferTile.show();
            }
        } else {
            // default effect is just to leave the existing tile
            // until the new one loads if this is a singleTile and
            // there was no change in resolution.  Otherwise we
            // don't bother to show the backBufferTile at all
            if (this.layer.singleTile) {
                this.backBufferTile.show();
            } else {
                this.backBufferTile.hide();
            }
        }
        this.lastRatio = ratio;

    },
    
    /** 
     * Method: show
     * Show the tile by showing its frame.
     */
    show: function() {
        this.frame.style.display = '';
        // Force a reflow on gecko based browsers to actually show the element
        // before continuing execution.
        if (OpenLayers.Util.indexOf(this.layer.SUPPORTED_TRANSITIONS, 
                this.layer.transitionEffect) != -1) {
            if (navigator.userAgent.toLowerCase().indexOf("gecko") != -1) { 
                this.frame.scrollLeft = this.frame.scrollLeft; 
            } 
        }
    },
    
    /** 
     * Method: hide
     * Hide the tile by hiding its frame.
     */
    hide: function() {
        this.frame.style.display = 'none';
    },
    
    CLASS_NAME: "OpenLayers.Tile.Image"
  }
);

OpenLayers.Tile.Image.useBlankTile = ( 
    OpenLayers.Util.getBrowserName() == "safari" || 
    OpenLayers.Util.getBrowserName() == "opera"); 
/* ======================================================================
    OpenLayers/Control/OverviewMap.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */

/** 
 * @requires OpenLayers/Control.js
 * @requires OpenLayers/BaseTypes.js
 * @requires OpenLayers/Events.js
 */

/**
 * Class: OpenLayers.Control.OverviewMap
 * The OverMap control creates a small overview map, useful to display the 
 * extent of a zoomed map and your main map and provide additional 
 * navigation options to the User.  By default the overview map is drawn in
 * the lower right corner of the main map. Create a new overview map with the
 * <OpenLayers.Control.OverviewMap> constructor.
 *
 * Inerits from:
 *  - <OpenLayers.Control>
 */
OpenLayers.Control.OverviewMap = OpenLayers.Class(OpenLayers.Control, {

    /**
     * Property: element
     * {DOMElement} The DOM element that contains the overview map
     */
    element: null,
    
    /**
     * APIProperty: ovmap
     * {<OpenLayers.Map>} A reference to the overview map itself.
     */
    ovmap: null,

    /**
     * APIProperty: size
     * {<OpenLayers.Size>} The overvew map size in pixels.  Note that this is
     * the size of the map itself - the element that contains the map (default
     * class name olControlOverviewMapElement) may have padding or other style
     * attributes added via CSS.
     */
    size: new OpenLayers.Size(180, 90),

    /**
     * APIProperty: layers
     * {Array(<OpenLayers.Layer>)} Ordered list of layers in the overview map.
     * If none are sent at construction, the base layer for the main map is used.
     */
    layers: null,
    
    /**
     * APIProperty: minRectSize
     * {Integer} The minimum width or height (in pixels) of the extent
     *     rectangle on the overview map.  When the extent rectangle reaches
     *     this size, it will be replaced depending on the value of the
     *     <minRectDisplayClass> property.  Default is 15 pixels.
     */
    minRectSize: 15,
    
    /**
     * APIProperty: minRectDisplayClass
     * {String} Replacement style class name for the extent rectangle when
     *     <minRectSize> is reached.  This string will be suffixed on to the
     *     displayClass.  Default is "RectReplacement".
     *
     * Example CSS declaration:
     * (code)
     * .olControlOverviewMapRectReplacement {
     *     overflow: hidden;
     *     cursor: move;
     *     background-image: url("img/overview_replacement.gif");
     *     background-repeat: no-repeat;
     *     background-position: center;
     * }
     * (end)
     */
    minRectDisplayClass: "RectReplacement",

    /**
     * APIProperty: minRatio
     * {Float} The ratio of the overview map resolution to the main map
     *     resolution at which to zoom farther out on the overview map.
     */
    minRatio: 8,

    /**
     * APIProperty: maxRatio
     * {Float} The ratio of the overview map resolution to the main map
     *     resolution at which to zoom farther in on the overview map.
     */
    maxRatio: 32,
    
    /**
     * APIProperty: mapOptions
     * {Object} An object containing any non-default properties to be sent to
     *     the overview map's map constructor.  These should include any
     *     non-default options that the main map was constructed with.
     */
    mapOptions: null,

    /**
     * APIProperty: autoPan
     * {Boolean} Always pan the overview map, so the extent marker remains in
     *     the center.  Default is false.  If true, when you drag the extent
     *     marker, the overview map will update itself so the marker returns
     *     to the center.
     */
    autoPan: false,
    
    /**
     * Property: handlers
     * {Object}
     */
    handlers: null,

    /**
     * Property: resolutionFactor
     * {Object}
     */
    resolutionFactor: 1,

    /**
     * Constructor: OpenLayers.Control.OverviewMap
     * Create a new overview map
     *
     * Parameters:
     * object - {Object} Properties of this object will be set on the overview
     * map object.  Note, to set options on the map object contained in this
     * control, set <mapOptions> as one of the options properties.
     */
    initialize: function(options) {
        this.layers = [];
        this.handlers = {};
        OpenLayers.Control.prototype.initialize.apply(this, [options]);
    },
    
    /**
     * APIMethod: destroy
     * Deconstruct the control
     */
    destroy: function() {
        if (!this.mapDiv) { // we've already been destroyed
            return;
        }
        if (this.handlers.click) {
            this.handlers.click.destroy();
        }
        if (this.handlers.drag) {
            this.handlers.drag.destroy();
        }

        this.mapDiv.removeChild(this.extentRectangle);
        this.extentRectangle = null;

        if (this.rectEvents) {
            this.rectEvents.destroy();
            this.rectEvents = null;
        }

        if (this.ovmap) {
            this.ovmap.destroy();
            this.ovmap = null;
        }
        
        this.element.removeChild(this.mapDiv);
        this.mapDiv = null;

        this.div.removeChild(this.element);
        this.element = null;

        if (this.maximizeDiv) {
            OpenLayers.Event.stopObservingElement(this.maximizeDiv);
            this.div.removeChild(this.maximizeDiv);
            this.maximizeDiv = null;
        }
        
        if (this.minimizeDiv) {
            OpenLayers.Event.stopObservingElement(this.minimizeDiv);
            this.div.removeChild(this.minimizeDiv);
            this.minimizeDiv = null;
        }

        this.map.events.un({
            "moveend": this.update,
            "changebaselayer": this.baseLayerDraw,
            scope: this
        });

        OpenLayers.Control.prototype.destroy.apply(this, arguments);    
    },

    /**
     * Method: draw
     * Render the control in the browser.
     */    
    draw: function() {
        OpenLayers.Control.prototype.draw.apply(this, arguments);
        if(!(this.layers.length > 0)) {
            if (this.map.baseLayer) {
                var layer = this.map.baseLayer.clone();
                this.layers = [layer];
            } else {
                this.map.events.register("changebaselayer", this, this.baseLayerDraw);
                return this.div;
            }
        }

        // create overview map DOM elements
        this.element = document.createElement('div');
        this.element.className = this.displayClass + 'Element';
        this.element.style.display = 'none';

        this.mapDiv = document.createElement('div');
        this.mapDiv.style.width = this.size.w + 'px';
        this.mapDiv.style.height = this.size.h + 'px';
        this.mapDiv.style.position = 'relative';
        this.mapDiv.style.overflow = 'hidden';
        this.mapDiv.id = OpenLayers.Util.createUniqueID('overviewMap');
        
        this.extentRectangle = document.createElement('div');
        this.extentRectangle.style.position = 'absolute';
        this.extentRectangle.style.zIndex = 1000;  //HACK
        this.extentRectangle.className = this.displayClass+'ExtentRectangle';
        this.mapDiv.appendChild(this.extentRectangle);

        this.element.appendChild(this.mapDiv);  

        this.div.appendChild(this.element);

        // Optionally add min/max buttons if the control will go in the
        // map viewport.
        if(!this.outsideViewport) {
            this.div.className += " " + this.displayClass + 'Container';
            var imgLocation = OpenLayers.Util.getImagesLocation();
            // maximize button div
            var img = imgLocation + 'layer-switcher-maximize.png';
            this.maximizeDiv = OpenLayers.Util.createAlphaImageDiv(
                                        this.displayClass + 'MaximizeButton', 
                                        null, 
                                        new OpenLayers.Size(18,18), 
                                        img, 
                                        'absolute');
            this.maximizeDiv.style.display = 'none';
            this.maximizeDiv.className = this.displayClass + 'MaximizeButton';
            OpenLayers.Event.observe(this.maximizeDiv, 'click', 
                OpenLayers.Function.bindAsEventListener(this.maximizeControl,
                                                        this)
            );
            this.div.appendChild(this.maximizeDiv);
    
            // minimize button div
            var img = imgLocation + 'layer-switcher-minimize.png';
            this.minimizeDiv = OpenLayers.Util.createAlphaImageDiv(
                                        'OpenLayers_Control_minimizeDiv', 
                                        null, 
                                        new OpenLayers.Size(18,18), 
                                        img, 
                                        'absolute');
            this.minimizeDiv.style.display = 'none';
            this.minimizeDiv.className = this.displayClass + 'MinimizeButton';
            OpenLayers.Event.observe(this.minimizeDiv, 'click', 
                OpenLayers.Function.bindAsEventListener(this.minimizeControl,
                                                        this)
            );
            this.div.appendChild(this.minimizeDiv);
            
            var eventsToStop = ['dblclick','mousedown'];
            
            for (var i=0, len=eventsToStop.length; i<len; i++) {

                OpenLayers.Event.observe(this.maximizeDiv, 
                                         eventsToStop[i], 
                                         OpenLayers.Event.stop);

                OpenLayers.Event.observe(this.minimizeDiv,
                                         eventsToStop[i], 
                                         OpenLayers.Event.stop);
            }
            
            this.minimizeControl();
        } else {
            // show the overview map
            this.element.style.display = '';
        }
        if(this.map.getExtent()) {
            this.update();
        }
        
        this.map.events.register('moveend', this, this.update);

        return this.div;
    },
    
    /**
     * Method: baseLayerDraw
     * Draw the base layer - called if unable to complete in the initial draw
     */
    baseLayerDraw: function() {
        this.draw();
        this.map.events.unregister("changebaselayer", this, this.baseLayerDraw);
    },

    /**
     * Method: rectDrag
     * Handle extent rectangle drag
     *
     * Parameters:
     * px - {<OpenLayers.Pixel>} The pixel location of the drag.
     */
    rectDrag: function(px) {
        var deltaX = this.handlers.drag.last.x - px.x;
        var deltaY = this.handlers.drag.last.y - px.y;
        if(deltaX != 0 || deltaY != 0) {
            var rectTop = this.rectPxBounds.top;
            var rectLeft = this.rectPxBounds.left;
            var rectHeight = Math.abs(this.rectPxBounds.getHeight());
            var rectWidth = this.rectPxBounds.getWidth();
            // don't allow dragging off of parent element
            var newTop = Math.max(0, (rectTop - deltaY));
            newTop = Math.min(newTop,
                              this.ovmap.size.h - this.hComp - rectHeight);
            var newLeft = Math.max(0, (rectLeft - deltaX));
            newLeft = Math.min(newLeft,
                               this.ovmap.size.w - this.wComp - rectWidth);
            this.setRectPxBounds(new OpenLayers.Bounds(newLeft,
                                                       newTop + rectHeight,
                                                       newLeft + rectWidth,
                                                       newTop));
        }
    },
    
    /**
     * Method: mapDivClick
     * Handle browser events
     *
     * Parameters:
     * evt - {<OpenLayers.Event>} evt
     */
    mapDivClick: function(evt) {
        var pxCenter = this.rectPxBounds.getCenterPixel();
        var deltaX = evt.xy.x - pxCenter.x;
        var deltaY = evt.xy.y - pxCenter.y;
        var top = this.rectPxBounds.top;
        var left = this.rectPxBounds.left;
        var height = Math.abs(this.rectPxBounds.getHeight());
        var width = this.rectPxBounds.getWidth();
        var newTop = Math.max(0, (top + deltaY));
        newTop = Math.min(newTop, this.ovmap.size.h - height);
        var newLeft = Math.max(0, (left + deltaX));
        newLeft = Math.min(newLeft, this.ovmap.size.w - width);
        this.setRectPxBounds(new OpenLayers.Bounds(newLeft,
                                                   newTop + height,
                                                   newLeft + width,
                                                   newTop));
        this.updateMapToRect();
    },

    /**
     * Method: maximizeControl
     * Unhide the control.  Called when the control is in the map viewport.
     *
     * Parameters:
     * e - {<OpenLayers.Event>}
     */
    maximizeControl: function(e) {
        this.element.style.display = '';
        this.showToggle(false);
        if (e != null) {
            OpenLayers.Event.stop(e);                                            
        }
    },

    /**
     * Method: minimizeControl
     * Hide all the contents of the control, shrink the size, 
     * add the maximize icon
     * 
     * Parameters:
     * e - {<OpenLayers.Event>}
     */
    minimizeControl: function(e) {
        this.element.style.display = 'none';
        this.showToggle(true);
        if (e != null) {
            OpenLayers.Event.stop(e);                                            
        }
    },

    /**
     * Method: showToggle
     * Hide/Show the toggle depending on whether the control is minimized
     *
     * Parameters:
     * minimize - {Boolean} 
     */
    showToggle: function(minimize) {
        this.maximizeDiv.style.display = minimize ? '' : 'none';
        this.minimizeDiv.style.display = minimize ? 'none' : '';
    },

    /**
     * Method: update
     * Update the overview map after layers move.
     */
    update: function() {
        if(this.ovmap == null) {
            this.createMap();
        }
        
        if(this.autoPan || !this.isSuitableOverview()) {
            this.updateOverview();
        }
        
        // update extent rectangle
        this.updateRectToMap();
    },
    
    /**
     * Method: isSuitableOverview
     * Determines if the overview map is suitable given the extent and
     * resolution of the main map.
     */
    isSuitableOverview: function() {
        var mapExtent = this.map.getExtent();
        var maxExtent = this.map.maxExtent;
        var testExtent = new OpenLayers.Bounds(
                                Math.max(mapExtent.left, maxExtent.left),
                                Math.max(mapExtent.bottom, maxExtent.bottom),
                                Math.min(mapExtent.right, maxExtent.right),
                                Math.min(mapExtent.top, maxExtent.top));        

        if (this.ovmap.getProjection() != this.map.getProjection()) {
            testExtent = testExtent.transform(
                this.map.getProjectionObject(),
                this.ovmap.getProjectionObject() );
        }

        var resRatio = this.ovmap.getResolution() / this.map.getResolution();
        return ((resRatio > this.minRatio) &&
                (resRatio <= this.maxRatio) &&
                (this.ovmap.getExtent().containsBounds(testExtent)));
    },
    
    /**
     * Method updateOverview
     * Called by <update> if <isSuitableOverview> returns true
     */
    updateOverview: function() {
        var mapRes = this.map.getResolution();
        var targetRes = this.ovmap.getResolution();
        var resRatio = targetRes / mapRes;
        if(resRatio > this.maxRatio) {
            // zoom in overview map
            targetRes = this.minRatio * mapRes;            
        } else if(resRatio <= this.minRatio) {
            // zoom out overview map
            targetRes = this.maxRatio * mapRes;
        }
        var center;
        if (this.ovmap.getProjection() != this.map.getProjection()) {
            center = this.map.center.clone();
            center.transform(this.map.getProjectionObject(),
                this.ovmap.getProjectionObject() );
        } else {
            center = this.map.center;
        }
        this.ovmap.setCenter(center, this.ovmap.getZoomForResolution(
            targetRes * this.resolutionFactor));
        this.updateRectToMap();
    },
    
    /**
     * Method: createMap
     * Construct the map that this control contains
     */
    createMap: function() {
        // create the overview map
        var options = OpenLayers.Util.extend(
                        {controls: [], maxResolution: 'auto', 
                         fallThrough: false}, this.mapOptions);
        this.ovmap = new OpenLayers.Map(this.mapDiv, options);
        
        // prevent ovmap from being destroyed when the page unloads, because
        // the OverviewMap control has to do this (and does it).
        OpenLayers.Event.stopObserving(window, 'unload', this.ovmap.unloadDestroy);
        
        this.ovmap.addLayers(this.layers);
        this.ovmap.zoomToMaxExtent();
        // check extent rectangle border width
        this.wComp = parseInt(OpenLayers.Element.getStyle(this.extentRectangle,
                                               'border-left-width')) +
                     parseInt(OpenLayers.Element.getStyle(this.extentRectangle,
                                               'border-right-width'));
        this.wComp = (this.wComp) ? this.wComp : 2;
        this.hComp = parseInt(OpenLayers.Element.getStyle(this.extentRectangle,
                                               'border-top-width')) +
                     parseInt(OpenLayers.Element.getStyle(this.extentRectangle,
                                               'border-bottom-width'));
        this.hComp = (this.hComp) ? this.hComp : 2;

        this.handlers.drag = new OpenLayers.Handler.Drag(
            this, {move: this.rectDrag, done: this.updateMapToRect},
            {map: this.ovmap}
        );
        this.handlers.click = new OpenLayers.Handler.Click(
            this, {
                "click": this.mapDivClick
            },{
                "single": true, "double": false,
                "stopSingle": true, "stopDouble": true,
                "pixelTolerance": 1,
                map: this.ovmap
            }
        );
        this.handlers.click.activate();
        
        this.rectEvents = new OpenLayers.Events(this, this.extentRectangle,
                                                null, true);
        this.rectEvents.register("mouseover", this, function(e) {
            if(!this.handlers.drag.active && !this.map.dragging) {
                this.handlers.drag.activate();
            }
        });
        this.rectEvents.register("mouseout", this, function(e) {
            if(!this.handlers.drag.dragging) {
                this.handlers.drag.deactivate();
            }
        });

        if (this.ovmap.getProjection() != this.map.getProjection()) {
            var sourceUnits = this.map.getProjectionObject().getUnits() ||
                this.map.units || this.map.baseLayer.units;
            var targetUnits = this.ovmap.getProjectionObject().getUnits() ||
                this.ovmap.units || this.ovmap.baseLayer.units;
            this.resolutionFactor = sourceUnits && targetUnits ?
                OpenLayers.INCHES_PER_UNIT[sourceUnits] /
                OpenLayers.INCHES_PER_UNIT[targetUnits] : 1;
        }
    },
        
    /**
     * Method: updateRectToMap
     * Updates the extent rectangle position and size to match the map extent
     */
    updateRectToMap: function() {
        // If the projections differ we need to reproject
        var bounds;
        if (this.ovmap.getProjection() != this.map.getProjection()) {
            bounds = this.map.getExtent().transform(
                this.map.getProjectionObject(), 
                this.ovmap.getProjectionObject() );
        } else {
            bounds = this.map.getExtent();
        }
        var pxBounds = this.getRectBoundsFromMapBounds(bounds);
        if (pxBounds) {
            this.setRectPxBounds(pxBounds);
        }
    },
    
    /**
     * Method: updateMapToRect
     * Updates the map extent to match the extent rectangle position and size
     */
    updateMapToRect: function() {
        var lonLatBounds = this.getMapBoundsFromRectBounds(this.rectPxBounds);
        if (this.ovmap.getProjection() != this.map.getProjection()) {
            lonLatBounds = lonLatBounds.transform(
                this.ovmap.getProjectionObject(),
                this.map.getProjectionObject() );
        }
        this.map.panTo(lonLatBounds.getCenterLonLat());
    },

    /**
     * Method: setRectPxBounds
     * Set extent rectangle pixel bounds.
     *
     * Parameters:
     * pxBounds - {<OpenLayers.Bounds>}
     */
    setRectPxBounds: function(pxBounds) {
        var top = Math.max(pxBounds.top, 0);
        var left = Math.max(pxBounds.left, 0);
        var bottom = Math.min(pxBounds.top + Math.abs(pxBounds.getHeight()),
                              this.ovmap.size.h - this.hComp);
        var right = Math.min(pxBounds.left + pxBounds.getWidth(),
                             this.ovmap.size.w - this.wComp);
        var width = Math.max(right - left, 0);
        var height = Math.max(bottom - top, 0);
        if(width < this.minRectSize || height < this.minRectSize) {
            this.extentRectangle.className = this.displayClass +
                                             this.minRectDisplayClass;
            var rLeft = left + (width / 2) - (this.minRectSize / 2);
            var rTop = top + (height / 2) - (this.minRectSize / 2);
            this.extentRectangle.style.top = Math.round(rTop) + 'px';
            this.extentRectangle.style.left = Math.round(rLeft) + 'px';
            this.extentRectangle.style.height = this.minRectSize + 'px';
            this.extentRectangle.style.width = this.minRectSize + 'px';
        } else {
            this.extentRectangle.className = this.displayClass +
                                             'ExtentRectangle';
            this.extentRectangle.style.top = Math.round(top) + 'px';
            this.extentRectangle.style.left = Math.round(left) + 'px';
            this.extentRectangle.style.height = Math.round(height) + 'px';
            this.extentRectangle.style.width = Math.round(width) + 'px';
        }
        this.rectPxBounds = new OpenLayers.Bounds(
            Math.round(left), Math.round(bottom),
            Math.round(right), Math.round(top)
        );
    },

    /**
     * Method: getRectBoundsFromMapBounds
     * Get the rect bounds from the map bounds.
     *
     * Parameters:
     * lonLatBounds - {<OpenLayers.Bounds>}
     *
     * Returns:
     * {<OpenLayers.Bounds>}A bounds which is the passed-in map lon/lat extent
     * translated into pixel bounds for the overview map
     */
    getRectBoundsFromMapBounds: function(lonLatBounds) {
        var leftBottomLonLat = new OpenLayers.LonLat(lonLatBounds.left,
                                                     lonLatBounds.bottom);
        var rightTopLonLat = new OpenLayers.LonLat(lonLatBounds.right,
                                                   lonLatBounds.top);
        var leftBottomPx = this.getOverviewPxFromLonLat(leftBottomLonLat);
        var rightTopPx = this.getOverviewPxFromLonLat(rightTopLonLat);
        var bounds = null;
        if (leftBottomPx && rightTopPx) {
            bounds = new OpenLayers.Bounds(leftBottomPx.x, leftBottomPx.y,
                                           rightTopPx.x, rightTopPx.y);
        }
        return bounds;
    },

    /**
     * Method: getMapBoundsFromRectBounds
     * Get the map bounds from the rect bounds.
     *
     * Parameters:
     * pxBounds - {<OpenLayers.Bounds>}
     *
     * Returns:
     * {<OpenLayers.Bounds>} Bounds which is the passed-in overview rect bounds
     * translated into lon/lat bounds for the overview map
     */
    getMapBoundsFromRectBounds: function(pxBounds) {
        var leftBottomPx = new OpenLayers.Pixel(pxBounds.left,
                                                pxBounds.bottom);
        var rightTopPx = new OpenLayers.Pixel(pxBounds.right,
                                              pxBounds.top);
        var leftBottomLonLat = this.getLonLatFromOverviewPx(leftBottomPx);
        var rightTopLonLat = this.getLonLatFromOverviewPx(rightTopPx);
        return new OpenLayers.Bounds(leftBottomLonLat.lon, leftBottomLonLat.lat,
                                     rightTopLonLat.lon, rightTopLonLat.lat);
    },

    /**
     * Method: getLonLatFromOverviewPx
     * Get a map location from a pixel location
     *
     * Parameters:
     * overviewMapPx - {<OpenLayers.Pixel>}
     *
     * Returns:
     * {<OpenLayers.LonLat>} Location which is the passed-in overview map
     * OpenLayers.Pixel, translated into lon/lat by the overview map
     */
    getLonLatFromOverviewPx: function(overviewMapPx) {
        var size = this.ovmap.size;
        var res  = this.ovmap.getResolution();
        var center = this.ovmap.getExtent().getCenterLonLat();
    
        var delta_x = overviewMapPx.x - (size.w / 2);
        var delta_y = overviewMapPx.y - (size.h / 2);
        
        return new OpenLayers.LonLat(center.lon + delta_x * res ,
                                     center.lat - delta_y * res); 
    },

    /**
     * Method: getOverviewPxFromLonLat
     * Get a pixel location from a map location
     *
     * Parameters:
     * lonlat - {<OpenLayers.LonLat>}
     *
     * Returns:
     * {<OpenLayers.Pixel>} Location which is the passed-in OpenLayers.LonLat, 
     * translated into overview map pixels
     */
    getOverviewPxFromLonLat: function(lonlat) {
        var res  = this.ovmap.getResolution();
        var extent = this.ovmap.getExtent();
        var px = null;
        if (extent) {
            px = new OpenLayers.Pixel(
                        Math.round(1/res * (lonlat.lon - extent.left)),
                        Math.round(1/res * (extent.top - lonlat.lat)));
        } 
        return px;
    },

    CLASS_NAME: 'OpenLayers.Control.OverviewMap'
});
/* ======================================================================
    OpenLayers/Feature.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */


/**
 * @requires OpenLayers/Util.js
 * @requires OpenLayers/Marker.js
 * @requires OpenLayers/Popup/AnchoredBubble.js
 */

/**
 * Class: OpenLayers.Feature
 * Features are combinations of geography and attributes. The OpenLayers.Feature
 *     class specifically combines a marker and a lonlat.
 */
OpenLayers.Feature = OpenLayers.Class({

    /** 
     * Property: layer 
     * {<OpenLayers.Layer>} 
     */
    layer: null,

    /** 
     * Property: id 
     * {String} 
     */
    id: null,
    
    /** 
     * Property: lonlat 
     * {<OpenLayers.LonLat>} 
     */
    lonlat: null,

    /** 
     * Property: data 
     * {Object} 
     */
    data: null,

    /** 
     * Property: marker 
     * {<OpenLayers.Marker>} 
     */
    marker: null,

    /**
     * APIProperty: popupClass
     * {<OpenLayers.Class>} The class which will be used to instantiate
     *     a new Popup. Default is <OpenLayers.Popup.AnchoredBubble>.
     */
    popupClass: OpenLayers.Popup.AnchoredBubble,

    /** 
     * Property: popup 
     * {<OpenLayers.Popup>} 
     */
    popup: null,

    /** 
     * Constructor: OpenLayers.Feature
     * Constructor for features.
     *
     * Parameters:
     * layer - {<OpenLayers.Layer>} 
     * lonlat - {<OpenLayers.LonLat>} 
     * data - {Object} 
     * 
     * Returns:
     * {<OpenLayers.Feature>}
     */
    initialize: function(layer, lonlat, data) {
        this.layer = layer;
        this.lonlat = lonlat;
        this.data = (data != null) ? data : {};
        this.id = OpenLayers.Util.createUniqueID(this.CLASS_NAME + "_"); 
    },

    /** 
     * Method: destroy
     * nullify references to prevent circular references and memory leaks
     */
    destroy: function() {

        //remove the popup from the map
        if ((this.layer != null) && (this.layer.map != null)) {
            if (this.popup != null) {
                this.layer.map.removePopup(this.popup);
            }
        }
        // remove the marker from the layer
        if (this.layer != null && this.marker != null) {
            this.layer.removeMarker(this.marker);
        }

        this.layer = null;
        this.id = null;
        this.lonlat = null;
        this.data = null;
        if (this.marker != null) {
            this.destroyMarker(this.marker);
            this.marker = null;
        }
        if (this.popup != null) {
            this.destroyPopup(this.popup);
            this.popup = null;
        }
    },
    
    /**
     * Method: onScreen
     * 
     * Returns:
     * {Boolean} Whether or not the feature is currently visible on screen
     *           (based on its 'lonlat' property)
     */
    onScreen:function() {
        
        var onScreen = false;
        if ((this.layer != null) && (this.layer.map != null)) {
            var screenBounds = this.layer.map.getExtent();
            onScreen = screenBounds.containsLonLat(this.lonlat);
        }    
        return onScreen;
    },
    

    /**
     * Method: createMarker
     * Based on the data associated with the Feature, create and return a marker object.
     *
     * Returns: 
     * {<OpenLayers.Marker>} A Marker Object created from the 'lonlat' and 'icon' properties
     *          set in this.data. If no 'lonlat' is set, returns null. If no
     *          'icon' is set, OpenLayers.Marker() will load the default image.
     *          
     *          Note - this.marker is set to return value
     * 
     */
    createMarker: function() {

        if (this.lonlat != null) {
            this.marker = new OpenLayers.Marker(this.lonlat, this.data.icon);
        }
        return this.marker;
    },

    /**
     * Method: destroyMarker
     * Destroys marker.
     * If user overrides the createMarker() function, s/he should be able
     *   to also specify an alternative function for destroying it
     */
    destroyMarker: function() {
        this.marker.destroy();  
    },

    /**
     * Method: createPopup
     * Creates a popup object created from the 'lonlat', 'popupSize',
     *     and 'popupContentHTML' properties set in this.data. It uses
     *     this.marker.icon as default anchor. 
     *  
     *  If no 'lonlat' is set, returns null. 
     *  If no this.marker has been created, no anchor is sent.
     *
     *  Note - the returned popup object is 'owned' by the feature, so you
     *      cannot use the popup's destroy method to discard the popup.
     *      Instead, you must use the feature's destroyPopup
     * 
     *  Note - this.popup is set to return value
     * 
     * Parameters: 
     * closeBox - {Boolean} create popup with closebox or not
     * 
     * Returns:
     * {<OpenLayers.Popup>} Returns the created popup, which is also set
     *     as 'popup' property of this feature. Will be of whatever type
     *     specified by this feature's 'popupClass' property, but must be
     *     of type <OpenLayers.Popup>.
     * 
     */
    createPopup: function(closeBox) {

        if (this.lonlat != null) {
            
            var id = this.id + "_popup";
            var anchor = (this.marker) ? this.marker.icon : null;

            if (!this.popup) {
                this.popup = new this.popupClass(id, 
                                                 this.lonlat,
                                                 this.data.popupSize,
                                                 this.data.popupContentHTML,
                                                 anchor, 
                                                 closeBox); 
            }    
            if (this.data.overflow != null) {
                this.popup.contentDiv.style.overflow = this.data.overflow;
            }    
            
            this.popup.feature = this;
        }        
        return this.popup;
    },

    
    /**
     * Method: destroyPopup
     * Destroys the popup created via createPopup.
     *
     * As with the marker, if user overrides the createPopup() function, s/he 
     *   should also be able to override the destruction
     */
    destroyPopup: function() {
        if (this.popup) {
            this.popup.feature = null;
            this.popup.destroy();
            this.popup = null;
        }    
    },

    CLASS_NAME: "OpenLayers.Feature"
});
/* ======================================================================
    OpenLayers/Handler/Click.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the clear BSD license.
 * See http://svn.openlayers.org/trunk/openlayers/license.txt 
 * for the full text of the license. */

/**
 * @requires OpenLayers/Handler.js
 */

/**
 * Class: OpenLayers.Handler.Click
 * A handler for mouse clicks.  The intention of this handler is to give
 *     controls more flexibility with handling clicks.  Browsers trigger
 *     click events twice for a double-click.  In addition, the mousedown,
 *     mousemove, mouseup sequence fires a click event.  With this handler,
 *     controls can decide whether to ignore clicks associated with a double
 *     click.  By setting a <pixelTolerance>, controls can also ignore clicks
 *     that include a drag.  Create a new instance with the
 *     <OpenLayers.Handler.Click> constructor.
 * 
 * Inherits from:
 *  - <OpenLayers.Handler> 
 */
OpenLayers.Handler.Click = OpenLayers.Class(OpenLayers.Handler, {

    /**
     * APIProperty: delay
     * {Number} Number of milliseconds between clicks before the event is
     *     considered a double-click.
     */
    delay: 300,
    
    /**
     * APIProperty: single
     * {Boolean} Handle single clicks.  Default is true.  If false, clicks
     * will not be reported.  If true, single-clicks will be reported.
     */
    single: true,
    
    /**
     * APIProperty: double
     * {Boolean} Handle double-clicks.  Default is false.
     */
    'double': false,
    
    /**
     * APIProperty: pixelTolerance
     * {Number} Maximum number of pixels between mouseup and mousedown for an
     *     event to be considered a click.  Default is 0.  If set to an
     *     integer value, clicks with a drag greater than the value will be
     *     ignored.  This property can only be set when the handler is
     *     constructed.
     */
    pixelTolerance: 0,
    
    /**
     * APIProperty: stopSingle
     * {Boolean} Stop other listeners from being notified of clicks.  Default
     *     is false.  If true, any click listeners registered before this one
     *     will not be notified of *any* click event (associated with double
     *     or single clicks).
     */
    stopSingle: false,
    
    /**
     * APIProperty: stopDouble
     * {Boolean} Stop other listeners from being notified of double-clicks.
     *     Default is false.  If true, any click listeners registered before
     *     this one will not be notified of *any* double-click events.
     * 
     * The one caveat with stopDouble is that given a map with two click
     *     handlers, one with stopDouble true and the other with stopSingle
     *     true, the stopSingle handler should be activated last to get
     *     uniform cross-browser performance.  Since IE triggers one click
     *     with a dblclick and FF triggers two, if a stopSingle handler is
     *     activated first, all it gets in IE is a single click when the
     *     second handler stops propagation on the dblclick.
     */
    stopDouble: false,

    /**
     * Property: timerId
     * {Number} The id of the timeout waiting to clear the <delayedCall>.
     */
    timerId: null,
    
    /**
     * Property: down
     * {<OpenLayers.Pixel>} The pixel location of the last mousedown.
     */
    down: null,
    
    /**
     * Property: rightclickTimerId
     * {Number} The id of the right mouse timeout waiting to clear the 
     *     <delayedEvent>.
     */
    rightclickTimerId: null,
    
    /**
     * Constructor: OpenLayers.Handler.Click
     * Create a new click handler.
     * 
     * Parameters:
     * control - {<OpenLayers.Control>} The control that is making use of
     *     this handler.  If a handler is being used without a control, the
     *     handler's setMap method must be overridden to deal properly with
     *     the map.
     * callbacks - {Object} An object with keys corresponding to callbacks
     *     that will be called by the handler. The callbacks should
     *     expect to recieve a single argument, the click event.
     *     Callbacks for 'click' and 'dblclick' are supported.
     * options - {Object} Optional object whose properties will be set on the
     *     handler.
     */
    initialize: function(control, callbacks, options) {
        OpenLayers.Handler.prototype.initialize.apply(this, arguments);
        // optionally register for mouseup and mousedown
        if(this.pixelTolerance != null) {
            this.mousedown = function(evt) {
                this.down = evt.xy;
                return true;
            };
        }
    },
    
    /**
     * Method: mousedown
     * Handle mousedown.  Only registered as a listener if pixelTolerance is
     *     a non-zero value at construction.
     *
     * Returns:
     * {Boolean} Continue propagating this event.
     */
    mousedown: null,

    /**
     * Method: mouseup
     * Handle mouseup.  Installed to support collection of right mouse events.
     * 
     * Returns:
     * {Boolean} Continue propagating this event.
     */
    mouseup:  function (evt) {
        var propagate = true;

        // Collect right mouse clicks from the mouseup
        //  IE - ignores the second right click in mousedown so using
        //  mouseup instead
        if (this.checkModifiers(evt) && 
            this.control.handleRightClicks && 
            OpenLayers.Event.isRightClick(evt)) {
          propagate = this.rightclick(evt);
        }

        return propagate;
    },
    
    /**
     * Method: rightclick
     * Handle rightclick.  For a dblrightclick, we get two clicks so we need 
     *     to always register for dblrightclick to properly handle single 
     *     clicks.
     *     
     * Returns:
     * {Boolean} Continue propagating this event.
     */
    rightclick: function(evt) {
        if(this.passesTolerance(evt)) {
           if(this.rightclickTimerId != null) {
                //Second click received before timeout this must be 
                // a double click
                this.clearTimer();      
                this.callback('dblrightclick', [evt]);
                return !this.stopDouble;
            } else { 
                //Set the rightclickTimerId, send evt only if double is 
                // true else trigger single
                var clickEvent = this['double'] ?
                    OpenLayers.Util.extend({}, evt) : 
                    this.callback('rightclick', [evt]);

                var delayedRightCall = OpenLayers.Function.bind(
                    this.delayedRightCall, 
                    this, 
                    clickEvent
                );
                this.rightclickTimerId = window.setTimeout(
                    delayedRightCall, this.delay
                );
            } 
        }
        return !this.stopSingle;
    },
    
    /**
     * Method: delayedRightCall
     * Sets <rightclickTimerId> to null.  And optionally triggers the 
     *     rightclick callback if evt is set.
     */
    delayedRightCall: function(evt) {
        this.rightclickTimerId = null;
        if (evt) {
           this.callback('rightclick', [evt]);
        }
        return !this.stopSingle;
    },
    
    /**
     * Method: dblclick
     * Handle dblclick.  For a dblclick, we get two clicks in some browsers
     *     (FF) and one in others (IE).  So we need to always register for
     *     dblclick to properly handle single clicks.
     *     
     * Returns:
     * {Boolean} Continue propagating this event.
     */
    dblclick: function(evt) {
        if(this.passesTolerance(evt)) {
            if(this["double"]) {
                this.callback('dblclick', [evt]);
            }
            this.clearTimer();
        }
        return !this.stopDouble;
    },
    
    /**
     * Method: click
     * Handle click.
     *
     * Returns:
     * {Boolean} Continue propagating this event.
     */
    click: function(evt) {
        if(this.passesTolerance(evt)) {
            if(this.timerId != null) {
                // already received a click
                this.clearTimer();
            } else {
                // set the timer, send evt only if single is true
                //use a clone of the event object because it will no longer 
                //be a valid event object in IE in the timer callback
                var clickEvent = this.single ?
                    OpenLayers.Util.extend({}, evt) : null;
                this.timerId = window.setTimeout(
                    OpenLayers.Function.bind(this.delayedCall, this, clickEvent),
                    this.delay
                );
            }
        }
        return !this.stopSingle;
    },
    
    /**
     * Method: passesTolerance
     * Determine whether the event is within the optional pixel tolerance.  Note
     *     that the pixel tolerance check only works if mousedown events get to
     *     the listeners registered here.  If they are stopped by other elements,
     *     the <pixelTolerance> will have no effect here (this method will always
     *     return true).
     *
     * Returns:
     * {Boolean} The click is within the pixel tolerance (if specified).
     */
    passesTolerance: function(evt) {
        var passes = true;
        if(this.pixelTolerance != null && this.down) {
            var dpx = Math.sqrt(
                Math.pow(this.down.x - evt.xy.x, 2) +
                Math.pow(this.down.y - evt.xy.y, 2)
            );
            if(dpx > this.pixelTolerance) {
                passes = false;
            }
        }
        return passes;
    },

    /**
     * Method: clearTimer
     * Clear the timer and set <timerId> to null.
     */
    clearTimer: function() {
        if(this.timerId != null) {
            window.clearTimeout(this.timerId);
            this.timerId = null;
        }
        if(this.rightclickTimerId != null) {
            window.clearTimeout(this.rightclickTimerId);
            this.rightclickTimerId = null;
        }
    },
    
    /**
     * Method: delayedCall
     * Sets <timerId> to null.  And optionally triggers the click callback if
     *     evt is set.
     */
    delayedCall: function(evt) {
        this.timerId = null;
        if(evt) {
            this.callback('click', [evt]);
        }
    },

    /**
     * APIMethod: deactivate
     * Deactivate the handler.
     *
     * Returns:
     * {Boolean} The handler was successfully deactivated.
     */
    deactivate: function() {
        var deactivated = false;
        if(OpenLayers.Handler.prototype.deactivate.apply(this, arguments)) {
            this.clearTimer();
            this.down = null;
            deactivated = true;
        }
        return deactivated;
    },

    CLASS_NAME: "OpenLayers.Handler.Click"
});
/* ======================================================================
    OpenLayers/Handler/Drag.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */

/**
 * @requires OpenLayers/Handler.js
 */

/**
 * Class: OpenLayers.Handler.Drag
 * The drag handler is used to deal with sequences of browser events related
 *     to dragging.  The handler is used by controls that want to know when
 *     a drag sequence begins, when a drag is happening, and when it has
 *     finished.
 *
 * Controls that use the drag handler typically construct it with callbacks
 *     for 'down', 'move', and 'done'.  Callbacks for these keys are called
 *     when the drag begins, with each move, and when the drag is done.  In
 *     addition, controls can have callbacks keyed to 'up' and 'out' if they
 *     care to differentiate between the types of events that correspond with
 *     the end of a drag sequence.  If no drag actually occurs (no mouse move)
 *     the 'down' and 'up' callbacks will be called, but not the 'done'
 *     callback.
 *
 * Create a new drag handler with the <OpenLayers.Handler.Drag> constructor.
 *
 * Inherits from:
 *  - <OpenLayers.Handler>
 */
OpenLayers.Handler.Drag = OpenLayers.Class(OpenLayers.Handler, {
  
    /** 
     * Property: started
     * {Boolean} When a mousedown event is received, we want to record it, but
     *     not set 'dragging' until the mouse moves after starting. 
     */
    started: false,
    
    /**
     * Property: stopDown
     * {Boolean} Stop propagation of mousedown events from getting to listeners
     *     on the same element.  Default is true.
     */
    stopDown: true,

    /** 
     * Property: dragging 
     * {Boolean} 
     */
    dragging: false,

    /** 
     * Property: last
     * {<OpenLayers.Pixel>} The last pixel location of the drag.
     */
    last: null,

    /** 
     * Property: start
     * {<OpenLayers.Pixel>} The first pixel location of the drag.
     */
    start: null,

    /**
     * Property: oldOnselectstart
     * {Function}
     */
    oldOnselectstart: null,
    
    /**
     * Property: interval
     * {Integer} In order to increase performance, an interval (in 
     *     milliseconds) can be set to reduce the number of drag events 
     *     called. If set, a new drag event will not be set until the 
     *     interval has passed. 
     *     Defaults to 0, meaning no interval. 
     */
    interval: 0,
    
    /**
     * Property: timeoutId
     * {String} The id of the timeout used for the mousedown interval.
     *     This is "private", and should be left alone.
     */
    timeoutId: null,
    
    /**
     * APIProperty: documentDrag
     * {Boolean} If set to true, the handler will also handle mouse moves when
     *     the cursor has moved out of the map viewport. Default is false.
     */
    documentDrag: false,
    
    /**
     * Property: documentEvents
     * {<OpenLayers.Events>} Event instance for observing document events. Will
     *     be set on mouseout if documentDrag is set to true.
     */
    documentEvents: null,

    /**
     * Constructor: OpenLayers.Handler.Drag
     * Returns OpenLayers.Handler.Drag
     * 
     * Parameters:
     * control - {<OpenLayers.Control>} The control that is making use of
     *     this handler.  If a handler is being used without a control, the
     *     handlers setMap method must be overridden to deal properly with
     *     the map.
     * callbacks - {Object} An object containing a single function to be
     *     called when the drag operation is finished. The callback should
     *     expect to recieve a single argument, the pixel location of the event.
     *     Callbacks for 'move' and 'done' are supported. You can also speficy
     *     callbacks for 'down', 'up', and 'out' to respond to those events.
     * options - {Object} 
     */
    initialize: function(control, callbacks, options) {
        OpenLayers.Handler.prototype.initialize.apply(this, arguments);
    },
    
    /**
     * The four methods below (down, move, up, and out) are used by subclasses
     *     to do their own processing related to these mouse events.
     */
    
    /**
     * Method: down
     * This method is called during the handling of the mouse down event.
     *     Subclasses can do their own processing here.
     *
     * Parameters:
     * evt - {Event} The mouse down event
     */
    down: function(evt) {
    },
    
    /**
     * Method: move
     * This method is called during the handling of the mouse move event.
     *     Subclasses can do their own processing here.
     *
     * Parameters:
     * evt - {Event} The mouse move event
     *
     */
    move: function(evt) {
    },

    /**
     * Method: up
     * This method is called during the handling of the mouse up event.
     *     Subclasses can do their own processing here.
     *
     * Parameters:
     * evt - {Event} The mouse up event
     */
    up: function(evt) {
    },

    /**
     * Method: out
     * This method is called during the handling of the mouse out event.
     *     Subclasses can do their own processing here.
     *
     * Parameters:
     * evt - {Event} The mouse out event
     */
    out: function(evt) {
    },

    /**
     * The methods below are part of the magic of event handling.  Because
     *     they are named like browser events, they are registered as listeners
     *     for the events they represent.
     */

    /**
     * Method: mousedown
     * Handle mousedown events
     *
     * Parameters:
     * evt - {Event} 
     *
     * Returns:
     * {Boolean} Let the event propagate.
     */
    mousedown: function (evt) {
        var propagate = true;
        this.dragging = false;
        if (this.checkModifiers(evt) && OpenLayers.Event.isLeftClick(evt)) {
            this.started = true;
            this.start = evt.xy;
            this.last = evt.xy;
            OpenLayers.Element.addClass(
                this.map.viewPortDiv, "olDragDown"
            );
            this.down(evt);
            this.callback("down", [evt.xy]);
            OpenLayers.Event.stop(evt);
            
            if(!this.oldOnselectstart) {
                this.oldOnselectstart = (document.onselectstart) ? document.onselectstart : OpenLayers.Function.True;
                document.onselectstart = OpenLayers.Function.False;
            }
            
            propagate = !this.stopDown;
        } else {
            this.started = false;
            this.start = null;
            this.last = null;
        }
        return propagate;
    },

    /**
     * Method: mousemove
     * Handle mousemove events
     *
     * Parameters:
     * evt - {Event} 
     *
     * Returns:
     * {Boolean} Let the event propagate.
     */
    mousemove: function (evt) {
        if (this.started && !this.timeoutId && (evt.xy.x != this.last.x || evt.xy.y != this.last.y)) {
            if(this.documentDrag === true && this.documentEvents) {
                if(evt.element === document) {
                    this.adjustXY(evt);
                    // do setEvent manually because the documentEvents are not
                    // registered with the map
                    this.setEvent(evt);
                } else {
                    this.destroyDocumentEvents();
                }
            }
            if (this.interval > 0) {
                this.timeoutId = setTimeout(OpenLayers.Function.bind(this.removeTimeout, this), this.interval);
            }
            this.dragging = true;
            this.move(evt);
            this.callback("move", [evt.xy]);
            if(!this.oldOnselectstart) {
                this.oldOnselectstart = document.onselectstart;
                document.onselectstart = OpenLayers.Function.False;
            }
            this.last = this.evt.xy;
        }
        return true;
    },
    
    /**
     * Method: removeTimeout
     * Private. Called by mousemove() to remove the drag timeout.
     */
    removeTimeout: function() {
        this.timeoutId = null;
    },

    /**
     * Method: mouseup
     * Handle mouseup events
     *
     * Parameters:
     * evt - {Event} 
     *
     * Returns:
     * {Boolean} Let the event propagate.
     */
    mouseup: function (evt) {
        if (this.started) {
            if(this.documentDrag === true && this.documentEvents) {
                this.adjustXY(evt);
                this.destroyDocumentEvents();
            }
            var dragged = (this.start != this.last);
            this.started = false;
            this.dragging = false;
            OpenLayers.Element.removeClass(
                this.map.viewPortDiv, "olDragDown"
            );
            this.up(evt);
            this.callback("up", [evt.xy]);
            if(dragged) {
                this.callback("done", [evt.xy]);
            }
            document.onselectstart = this.oldOnselectstart;
        }
        return true;
    },

    /**
     * Method: mouseout
     * Handle mouseout events
     *
     * Parameters:
     * evt - {Event} 
     *
     * Returns:
     * {Boolean} Let the event propagate.
     */
    mouseout: function (evt) {
        if (this.started && OpenLayers.Util.mouseLeft(evt, this.map.div)) {
            if(this.documentDrag === true) {
                this.documentEvents = new OpenLayers.Events(this, document,
                                            null, null, {includeXY: true});
                this.documentEvents.on({
                    mousemove: this.mousemove,
                    mouseup: this.mouseup
                });
                OpenLayers.Element.addClass(
                    document.body, "olDragDown"
                );
            } else {
                var dragged = (this.start != this.last);
                this.started = false; 
                this.dragging = false;
                OpenLayers.Element.removeClass(
                    this.map.viewPortDiv, "olDragDown"
                );
                this.out(evt);
                this.callback("out", []);
                if(dragged) {
                    this.callback("done", [evt.xy]);
                }
                if(document.onselectstart) {
                    document.onselectstart = this.oldOnselectstart;
                }
            }
        }
        return true;
    },

    /**
     * Method: click
     * The drag handler captures the click event.  If something else registers
     *     for clicks on the same element, its listener will not be called 
     *     after a drag.
     * 
     * Parameters: 
     * evt - {Event} 
     * 
     * Returns:
     * {Boolean} Let the event propagate.
     */
    click: function (evt) {
        // let the click event propagate only if the mouse moved
        return (this.start == this.last);
    },

    /**
     * Method: activate
     * Activate the handler.
     * 
     * Returns:
     * {Boolean} The handler was successfully activated.
     */
    activate: function() {
        var activated = false;
        if(OpenLayers.Handler.prototype.activate.apply(this, arguments)) {
            this.dragging = false;
            activated = true;
        }
        return activated;
    },

    /**
     * Method: deactivate 
     * Deactivate the handler.
     * 
     * Returns:
     * {Boolean} The handler was successfully deactivated.
     */
    deactivate: function() {
        var deactivated = false;
        if(OpenLayers.Handler.prototype.deactivate.apply(this, arguments)) {
            this.started = false;
            this.dragging = false;
            this.start = null;
            this.last = null;
            deactivated = true;
            OpenLayers.Element.removeClass(
                this.map.viewPortDiv, "olDragDown"
            );
        }
        return deactivated;
    },
    
    /**
     * Method: adjustXY
     * Converts event coordinates that are relative to the document body to
     * ones that are relative to the map viewport. The latter is the default in
     * OpenLayers.
     * 
     * Parameters:
     * evt - {Object}
     */
    adjustXY: function(evt) {
        var pos = OpenLayers.Util.pagePosition(this.map.div);
        evt.xy.x -= pos[0];
        evt.xy.y -= pos[1];
    },
    
    /**
     * Method: destroyDocumentEvents
     * Destroys the events instance that gets added to the document body when
     * documentDrag is true and the mouse cursor leaves the map viewport while
     * dragging.
     */
    destroyDocumentEvents: function() {
        OpenLayers.Element.removeClass(
            document.body, "olDragDown"
        );
        this.documentEvents.destroy();
        this.documentEvents = null;
    },

    CLASS_NAME: "OpenLayers.Handler.Drag"
});
/* ======================================================================
    OpenLayers/Handler/Feature.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */


/**
 * @requires OpenLayers/Handler.js
 */

/**
 * Class: OpenLayers.Handler.Feature 
 * Handler to respond to mouse events related to a drawn feature.  Callbacks
 *     with the following keys will be notified of the following events
 *     associated with features: click, clickout, over, out, and dblclick.
 *
 * This handler stops event propagation for mousedown and mouseup if those
 *     browser events target features that can be selected.
 */
OpenLayers.Handler.Feature = OpenLayers.Class(OpenLayers.Handler, {

    /**
     * Property: EVENTMAP
     * {Object} A object mapping the browser events to objects with callback
     *     keys for in and out.
     */
    EVENTMAP: {
        'click': {'in': 'click', 'out': 'clickout'},
        'mousemove': {'in': 'over', 'out': 'out'},
        'dblclick': {'in': 'dblclick', 'out': null},
        'mousedown': {'in': null, 'out': null},
        'mouseup': {'in': null, 'out': null}
    },

    /**
     * Property: feature
     * {<OpenLayers.Feature.Vector>} The last feature that was hovered.
     */
    feature: null,

    /**
     * Property: lastFeature
     * {<OpenLayers.Feature.Vector>} The last feature that was handled.
     */
    lastFeature: null,

    /**
     * Property: down
     * {<OpenLayers.Pixel>} The location of the last mousedown.
     */
    down: null,

    /**
     * Property: up
     * {<OpenLayers.Pixel>} The location of the last mouseup.
     */
    up: null,
    
    /**
     * Property: clickTolerance
     * {Number} The number of pixels the mouse can move between mousedown
     *     and mouseup for the event to still be considered a click.
     *     Dragging the map should not trigger the click and clickout callbacks
     *     unless the map is moved by less than this tolerance. Defaults to 4.
     */
    clickTolerance: 4,

    /**
     * Property: geometryTypes
     * To restrict dragging to a limited set of geometry types, send a list
     * of strings corresponding to the geometry class names.
     * 
     * @type Array(String)
     */
    geometryTypes: null,

    /**
     * Property: stopClick
     * {Boolean} If stopClick is set to true, handled clicks do not
     *      propagate to other click listeners. Otherwise, handled clicks
     *      do propagate. Unhandled clicks always propagate, whatever the
     *      value of stopClick. Defaults to true.
     */
    stopClick: true,

    /**
     * Property: stopDown
     * {Boolean} If stopDown is set to true, handled mousedowns do not
     *      propagate to other mousedown listeners. Otherwise, handled
     *      mousedowns do propagate. Unhandled mousedowns always propagate,
     *      whatever the value of stopDown. Defaults to true.
     */
    stopDown: true,

    /**
     * Property: stopUp
     * {Boolean} If stopUp is set to true, handled mouseups do not
     *      propagate to other mouseup listeners. Otherwise, handled mouseups
     *      do propagate. Unhandled mouseups always propagate, whatever the
     *      value of stopUp. Defaults to false.
     */
    stopUp: false,
    
    /**
     * Constructor: OpenLayers.Handler.Feature
     *
     * Parameters:
     * control - {<OpenLayers.Control>} 
     * layer - {<OpenLayers.Layer.Vector>}
     * callbacks - {Object} An object with a 'over' property whos value is
     *     a function to be called when the mouse is over a feature. The 
     *     callback should expect to recieve a single argument, the feature.
     * options - {Object} 
     */
    initialize: function(control, layer, callbacks, options) {
        OpenLayers.Handler.prototype.initialize.apply(this, [control, callbacks, options]);
        this.layer = layer;
    },


    /**
     * Method: mousedown
     * Handle mouse down.  Stop propagation if a feature is targeted by this
     *     event (stops map dragging during feature selection).
     * 
     * Parameters:
     * evt - {Event} 
     */
    mousedown: function(evt) {
        this.down = evt.xy;
        return this.handle(evt) ? !this.stopDown : true;
    },
    
    /**
     * Method: mouseup
     * Handle mouse up.  Stop propagation if a feature is targeted by this
     *     event.
     * 
     * Parameters:
     * evt - {Event} 
     */
    mouseup: function(evt) {
        this.up = evt.xy;
        return this.handle(evt) ? !this.stopUp : true;
    },

    /**
     * Method: click
     * Handle click.  Call the "click" callback if click on a feature,
     *     or the "clickout" callback if click outside any feature.
     * 
     * Parameters:
     * evt - {Event} 
     *
     * Returns:
     * {Boolean}
     */
    click: function(evt) {
        return this.handle(evt) ? !this.stopClick : true;
    },
        
    /**
     * Method: mousemove
     * Handle mouse moves.  Call the "over" callback if moving in to a feature,
     *     or the "out" callback if moving out of a feature.
     * 
     * Parameters:
     * evt - {Event} 
     *
     * Returns:
     * {Boolean}
     */
    mousemove: function(evt) {
        if (!this.callbacks['over'] && !this.callbacks['out']) {
            return true;
        }     
        this.handle(evt);
        return true;
    },
    
    /**
     * Method: dblclick
     * Handle dblclick.  Call the "dblclick" callback if dblclick on a feature.
     *
     * Parameters:
     * evt - {Event} 
     *
     * Returns:
     * {Boolean}
     */
    dblclick: function(evt) {
        return !this.handle(evt);
    },

    /**
     * Method: geometryTypeMatches
     * Return true if the geometry type of the passed feature matches
     *     one of the geometry types in the geometryTypes array.
     *
     * Parameters:
     * feature - {<OpenLayers.Vector.Feature>}
     *
     * Returns:
     * {Boolean}
     */
    geometryTypeMatches: function(feature) {
        return this.geometryTypes == null ||
            OpenLayers.Util.indexOf(this.geometryTypes,
                                    feature.geometry.CLASS_NAME) > -1;
    },

    /**
     * Method: handle
     *
     * Parameters:
     * evt - {Event}
     *
     * Returns:
     * {Boolean} The event occurred over a relevant feature.
     */
    handle: function(evt) {
        if(this.feature && !this.feature.layer) {
            // feature has been destroyed
            this.feature = null;
        }
        var type = evt.type;
        var handled = false;
        var previouslyIn = !!(this.feature); // previously in a feature
        var click = (type == "click" || type == "dblclick");
        this.feature = this.layer.getFeatureFromEvent(evt);
        if(this.feature && !this.feature.layer) {
            // feature has been destroyed
            this.feature = null;
        }
        if(this.lastFeature && !this.lastFeature.layer) {
            // last feature has been destroyed
            this.lastFeature = null;
        }
        if(this.feature) {
            var inNew = (this.feature != this.lastFeature);
            if(this.geometryTypeMatches(this.feature)) {
                // in to a feature
                if(previouslyIn && inNew) {
                    // out of last feature and in to another
                    if(this.lastFeature) {
                        this.triggerCallback(type, 'out', [this.lastFeature]);
                    }
                    this.triggerCallback(type, 'in', [this.feature]);
                } else if(!previouslyIn || click) {
                    // in feature for the first time
                    this.triggerCallback(type, 'in', [this.feature]);
                }
                this.lastFeature = this.feature;
                handled = true;
            } else {
                // not in to a feature
                if(this.lastFeature && (previouslyIn && inNew || click)) {
                    // out of last feature for the first time
                    this.triggerCallback(type, 'out', [this.lastFeature]);
                }
                // next time the mouse goes in a feature whose geometry type
                // doesn't match we don't want to call the 'out' callback
                // again, so let's set this.feature to null so that
                // previouslyIn will evaluate to false the next time
                // we enter handle. Yes, a bit hackish...
                this.feature = null;
            }
        } else {
            if(this.lastFeature && (previouslyIn || click)) {
                this.triggerCallback(type, 'out', [this.lastFeature]);
            }
        }
        return handled;
    },
    
    /**
     * Method: triggerCallback
     * Call the callback keyed in the event map with the supplied arguments.
     *     For click and clickout, the <clickTolerance> is checked first.
     *
     * Parameters:
     * type - {String}
     */
    triggerCallback: function(type, mode, args) {
        var key = this.EVENTMAP[type][mode];
        if(key) {
            if(type == 'click' && this.up && this.down) {
                // for click/clickout, only trigger callback if tolerance is met
                var dpx = Math.sqrt(
                    Math.pow(this.up.x - this.down.x, 2) +
                    Math.pow(this.up.y - this.down.y, 2)
                );
                if(dpx <= this.clickTolerance) {
                    this.callback(key, args);
                }
            } else {
                this.callback(key, args);
            }
        }
    },

    /**
     * Method: activate 
     * Turn on the handler.  Returns false if the handler was already active.
     *
     * Returns:
     * {Boolean}
     */
    activate: function() {
        var activated = false;
        if(OpenLayers.Handler.prototype.activate.apply(this, arguments)) {
            this.moveLayerToTop();
            this.map.events.on({
                "removelayer": this.handleMapEvents,
                "changelayer": this.handleMapEvents,
                scope: this
            });
            activated = true;
        }
        return activated;
    },
    
    /**
     * Method: deactivate 
     * Turn off the handler.  Returns false if the handler was already active.
     *
     * Returns: 
     * {Boolean}
     */
    deactivate: function() {
        var deactivated = false;
        if(OpenLayers.Handler.prototype.deactivate.apply(this, arguments)) {
            this.moveLayerBack();
            this.feature = null;
            this.lastFeature = null;
            this.down = null;
            this.up = null;
            this.map.events.un({
                "removelayer": this.handleMapEvents,
                "changelayer": this.handleMapEvents,
                scope: this
            });
            deactivated = true;
        }
        return deactivated;
    },
    
    /**
     * Method handleMapEvents
     * 
     * Parameters:
     * evt - {Object}
     */
    handleMapEvents: function(evt) {
        if (!evt.property || evt.property == "order") {
            this.moveLayerToTop();
        }
    },
    
    /**
     * Method: moveLayerToTop
     * Moves the layer for this handler to the top, so mouse events can reach
     * it.
     */
    moveLayerToTop: function() {
        var index = Math.max(this.map.Z_INDEX_BASE['Feature'] - 1,
            this.layer.getZIndex()) + 1;
        this.layer.setZIndex(index);
        
    },
    
    /**
     * Method: moveLayerBack
     * Moves the layer back to the position determined by the map's layers
     * array.
     */
    moveLayerBack: function() {
        var index = this.layer.getZIndex() - 1;
        if (index >= this.map.Z_INDEX_BASE['Feature']) {
            this.layer.setZIndex(index);
        } else {
            this.map.setLayerZIndex(this.layer,
                this.map.getLayerIndex(this.layer));
        }
    },

    CLASS_NAME: "OpenLayers.Handler.Feature"
});
/* ======================================================================
    OpenLayers/Handler/Hover.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the clear BSD license.
 * See http://svn.openlayers.org/trunk/openlayers/license.txt 
 * for the full text of the license. */

/**
 * @requires OpenLayers/Handler.js
 */

/**
 * Class: OpenLayers.Handler.Hover
 * The hover handler is to be used to emulate mouseovers on objects
 *      on the map that aren't DOM elements. For example one can use
 *      this handler to send WMS/GetFeatureInfo requests as the user
 *      moves the mouve over the map.
 * 
 * Inherits from:
 *  - <OpenLayers.Handler> 
 */
OpenLayers.Handler.Hover = OpenLayers.Class(OpenLayers.Handler, {

    /**
     * APIProperty: delay
     * {Integer} - Number of milliseconds between mousemoves before
     *      the event is considered a hover. Default is 500.
     */
    delay: 500,
    
    /**
     * APIProperty: pixelTolerance
     * {Integer} - Maximum number of pixels between mousemoves for
     *      an event to be considered a hover. Default is null.
     */
    pixelTolerance: null,

    /**
     * APIProperty: stopMove
     * {Boolean} - Stop other listeners from being notified on mousemoves.
     *      Default is false.
     */
    stopMove: false,

    /**
     * Property: px
     * {<OpenLayers.Pixel>} - The location of the last mousemove, expressed
     *      in pixels.
     */
    px: null,

    /**
     * Property: timerId
     * {Number} - The id of the timer.
     */
    timerId: null,
 
    /**
     * Constructor: OpenLayers.Handler.Hover
     * Construct a hover handler.
     *
     * Parameters:
     * control - {<OpenLayers.Control>} The control that initialized this
     *     handler.  The control is assumed to have a valid map property; that
     *     map is used in the handler's own setMap method.
     * callbacks - {Object} An object with keys corresponding to callbacks
     *     that will be called by the handler. The callbacks should
     *     expect to receive a single argument, the event. Callbacks for
     *     'move', the mouse is moving, and 'pause', the mouse is pausing,
     *     are supported.
     * options - {Object} An optional object whose properties will be set on
     *     the handler.
     */
    initialize: function(control, callbacks, options) {
        OpenLayers.Handler.prototype.initialize.apply(this, arguments);
    },

    /**
     * Method: mousemove
     * Called when the mouse moves on the map.
     *
     * Parameters:
     * evt - {<OpenLayers.Event>}
     *
     * Returns:
     * {Boolean} Continue propagating this event.
     */
    mousemove: function(evt) {
        if(this.passesTolerance(evt.xy)) {
            this.clearTimer();
            this.callback('move', [evt]);
            this.px = evt.xy;
            // clone the evt so original properties can be accessed even
            // if the browser deletes them during the delay
            evt = OpenLayers.Util.extend({}, evt);
            this.timerId = window.setTimeout(
                OpenLayers.Function.bind(this.delayedCall, this, evt),
                this.delay
            );
        }
        return !this.stopMove;
    },

    /**
     * Method: mouseout
     * Called when the mouse goes out of the map.
     *
     * Parameters:
     * evt - {<OpenLayers.Event>}
     *
     * Returns:
     * {Boolean} Continue propagating this event.
     */
    mouseout: function(evt) {
        if (OpenLayers.Util.mouseLeft(evt, this.map.div)) {
            this.clearTimer();
            this.callback('move', [evt]);
        }
        return true;
    },

    /**
     * Method: passesTolerance
     * Determine whether the mouse move is within the optional pixel tolerance.
     *
     * Parameters:
     * px - {<OpenLayers.Pixel>}
     *
     * Returns:
     * {Boolean} The mouse move is within the pixel tolerance.
     */
    passesTolerance: function(px) {
        var passes = true;
        if(this.pixelTolerance && this.px) {
            var dpx = Math.sqrt(
                Math.pow(this.px.x - px.x, 2) +
                Math.pow(this.px.y - px.y, 2)
            );
            if(dpx < this.pixelTolerance) {
                passes = false;
            }
        }
        return passes;
    },

    /**
     * Method: clearTimer
     * Clear the timer and set <timerId> to null.
     */
    clearTimer: function() {
        if(this.timerId != null) {
            window.clearTimeout(this.timerId);
            this.timerId = null;
        }
    },

    /**
     * Method: delayedCall
     * Triggers pause callback.
     *
     * Parameters:
     * evt - {<OpenLayers.Event>}
     */
    delayedCall: function(evt) {
        this.callback('pause', [evt]);
    },

    /**
     * APIMethod: deactivate
     * Deactivate the handler.
     *
     * Returns:
     * {Boolean} The handler was successfully deactivated.
     */
    deactivate: function() {
        var deactivated = false;
        if(OpenLayers.Handler.prototype.deactivate.apply(this, arguments)) {
            this.clearTimer();
            deactivated = true;
        }
        return deactivated;
    },

    CLASS_NAME: "OpenLayers.Handler.Hover"
});
/* ======================================================================
    OpenLayers/Handler/Keyboard.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */

/**
 * @requires OpenLayers/Handler.js
 * @requires OpenLayers/Events.js
 */

/**
 * Class: OpenLayers.handler.Keyboard
 * A handler for keyboard events.  Create a new instance with the
 *     <OpenLayers.Handler.Keyboard> constructor.
 * 
 * Inherits from:
 *  - <OpenLayers.Handler> 
 */
OpenLayers.Handler.Keyboard = OpenLayers.Class(OpenLayers.Handler, {

    /* http://www.quirksmode.org/js/keys.html explains key x-browser
        key handling quirks in pretty nice detail */

    /** 
     * Constant: KEY_EVENTS
     * keydown, keypress, keyup
     */
    KEY_EVENTS: ["keydown", "keyup"],

    /** 
    * Property: eventListener
    * {Function}
    */
    eventListener: null,

    /**
     * Constructor: OpenLayers.Handler.Keyboard
     * Returns a new keyboard handler.
     * 
     * Parameters:
     * control - {<OpenLayers.Control>} The control that is making use of
     *     this handler.  If a handler is being used without a control, the
     *     handlers setMap method must be overridden to deal properly with
     *     the map.
     * callbacks - {Object} An object containing a single function to be
     *     called when the drag operation is finished. The callback should
     *     expect to recieve a single argument, the pixel location of the event.
     *     Callbacks for 'keydown', 'keypress', and 'keyup' are supported.
     * options - {Object} Optional object whose properties will be set on the
     *     handler.
     */
    initialize: function(control, callbacks, options) {
        OpenLayers.Handler.prototype.initialize.apply(this, arguments);
        // cache the bound event listener method so it can be unobserved later
        this.eventListener = OpenLayers.Function.bindAsEventListener(
            this.handleKeyEvent, this
        );
    },
    
    /**
     * Method: destroy
     */
    destroy: function() {
        this.deactivate();
        this.eventListener = null;
        OpenLayers.Handler.prototype.destroy.apply(this, arguments);
    },

    /**
     * Method: activate
     */
    activate: function() {
        if (OpenLayers.Handler.prototype.activate.apply(this, arguments)) {
            for (var i=0, len=this.KEY_EVENTS.length; i<len; i++) {
                OpenLayers.Event.observe(
                    document, this.KEY_EVENTS[i], this.eventListener);
            }
            return true;
        } else {
            return false;
        }
    },

    /**
     * Method: deactivate
     */
    deactivate: function() {
        var deactivated = false;
        if (OpenLayers.Handler.prototype.deactivate.apply(this, arguments)) {
            for (var i=0, len=this.KEY_EVENTS.length; i<len; i++) {
                OpenLayers.Event.stopObserving(
                    document, this.KEY_EVENTS[i], this.eventListener);
            }
            deactivated = true;
        }
        return deactivated;
    },

    /**
     * Method: handleKeyEvent 
     */
    handleKeyEvent: function (evt) {
        if (this.checkModifiers(evt)) {
            this.callback(evt.type, [evt]);
        }
    },

    CLASS_NAME: "OpenLayers.Handler.Keyboard"
});
/* ======================================================================
    OpenLayers/Handler/MouseWheel.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */

/**
 * @requires OpenLayers/Handler.js
 */

/**
 * Class: OpenLayers.Handler.MouseWheel
 * Handler for wheel up/down events.
 * 
 * Inherits from:
 *  - <OpenLayers.Handler>
 */
OpenLayers.Handler.MouseWheel = OpenLayers.Class(OpenLayers.Handler, {
    /** 
     * Property: wheelListener 
     * {function} 
     */
    wheelListener: null,

    /** 
     * Property: mousePosition
     * {<OpenLayers.Pixel>} mousePosition is necessary because
     * evt.clientX/Y is buggy in Moz on wheel events, so we cache and use the
     * value from the last mousemove.
     */
    mousePosition: null,

    /**
     * Property: interval
     * {Integer} In order to increase server performance, an interval (in 
     *     milliseconds) can be set to reduce the number of up/down events 
     *     called. If set, a new up/down event will not be set until the 
     *     interval has passed. 
     *     Defaults to 0, meaning no interval. 
     */
    interval: 0,
    
    /**
     * Property: delta
     * {Integer} When interval is set, delta collects the mousewheel z-deltas
     *     of the events that occur within the interval.
     *      See also the cumulative option
     */
    delta: 0,
    
    /**
     * Property: cumulative
     * {Boolean} When interval is set: true to collect all the mousewheel 
     *     z-deltas, false to only record the delta direction (positive or
     *     negative)
     */
    cumulative: true,

    /**
     * Constructor: OpenLayers.Handler.MouseWheel
     *
     * Parameters:
     * control - {<OpenLayers.Control>} 
     * callbacks - {Object} An object containing a single function to be
     *                          called when the drag operation is finished.
     *                          The callback should expect to recieve a single
     *                          argument, the point geometry.
     * options - {Object} 
     */
    initialize: function(control, callbacks, options) {
        OpenLayers.Handler.prototype.initialize.apply(this, arguments);
        this.wheelListener = OpenLayers.Function.bindAsEventListener(
            this.onWheelEvent, this
        );
    },

    /**
     * Method: destroy
     */    
    destroy: function() {
        OpenLayers.Handler.prototype.destroy.apply(this, arguments);
        this.wheelListener = null;
    },

    /**
     *  Mouse ScrollWheel code thanks to http://adomas.org/javascript-mouse-wheel/
     */

    /** 
     * Method: onWheelEvent
     * Catch the wheel event and handle it xbrowserly
     * 
     * Parameters:
     * e - {Event} 
     */
    onWheelEvent: function(e){
        
        // make sure we have a map and check keyboard modifiers
        if (!this.map || !this.checkModifiers(e)) {
            return;
        }
        
        // Ride up the element's DOM hierarchy to determine if it or any of 
        //  its ancestors was: 
        //   * specifically marked as scrollable
        //   * one of our layer divs
        //   * the map div
        //
        var overScrollableDiv = false;
        var overLayerDiv = false;
        var overMapDiv = false;
        
        var elem = OpenLayers.Event.element(e);
        while((elem != null) && !overMapDiv && !overScrollableDiv) {

            if (!overScrollableDiv) {
                try {
                    if (elem.currentStyle) {
                        overflow = elem.currentStyle["overflow"];
                    } else {
                        var style = 
                            document.defaultView.getComputedStyle(elem, null);
                        var overflow = style.getPropertyValue("overflow");
                    }
                    overScrollableDiv = ( overflow && 
                        (overflow == "auto") || (overflow == "scroll") );
                } catch(err) {
                    //sometimes when scrolling in a popup, this causes 
                    // obscure browser error
                }
            }

            if (!overLayerDiv) {
                for(var i=0, len=this.map.layers.length; i<len; i++) {
                    // Are we in the layer div? Note that we have two cases
                    // here: one is to catch EventPane layers, which have a 
                    // pane above the layer (layer.pane)
                    if (elem == this.map.layers[i].div 
                        || elem == this.map.layers[i].pane) { 
                        overLayerDiv = true;
                        break;
                    }
                }
            }
            overMapDiv = (elem == this.map.div);

            elem = elem.parentNode;
        }
        
        // Logic below is the following:
        //
        // If we are over a scrollable div or not over the map div:
        //  * do nothing (let the browser handle scrolling)
        //
        //    otherwise 
        // 
        //    If we are over the layer div: 
        //     * zoom/in out
        //     then
        //     * kill event (so as not to also scroll the page after zooming)
        //
        //       otherwise
        //
        //       Kill the event (dont scroll the page if we wheel over the 
        //        layerswitcher or the pan/zoom control)
        //
        if (!overScrollableDiv && overMapDiv) {
            if (overLayerDiv) {
                var delta = 0;
                if (!e) {
                    e = window.event;
                }
                if (e.wheelDelta) {
                    delta = e.wheelDelta/120; 
                    if (window.opera && window.opera.version() < 9.2) {
                        delta = -delta;
                    }
                } else if (e.detail) {
                    delta = -e.detail / 3;
                }
                this.delta = this.delta + delta;

                if(this.interval) {
                    window.clearTimeout(this._timeoutId);
                    this._timeoutId = window.setTimeout(
                        OpenLayers.Function.bind(function(){
                            this.wheelZoom(e);
                        }, this),
                        this.interval
                    );
                } else {
                    this.wheelZoom(e);
                }
            }
            OpenLayers.Event.stop(e);
        }
    },

    /**
     * Method: wheelZoom
     * Given the wheel event, we carry out the appropriate zooming in or out,
     *     based on the 'wheelDelta' or 'detail' property of the event.
     * 
     * Parameters:
     * e - {Event}
     */
    wheelZoom: function(e) {
        var delta = this.delta;
        this.delta = 0;
        
        if (delta) {
            // add the mouse position to the event because mozilla has 
            // a bug with clientX and clientY (see 
            // https://bugzilla.mozilla.org/show_bug.cgi?id=352179)
            // getLonLatFromViewPortPx(e) returns wrong values
            if (this.mousePosition) {
                e.xy = this.mousePosition;
            } 
            if (!e.xy) {
                // If the mouse hasn't moved over the map yet, then
                // we don't have a mouse position (in FF), so we just
                // act as if the mouse was at the center of the map.
                // Note that we can tell we are in the map -- and 
                // this.map is ensured to be true above.
                e.xy = this.map.getPixelFromLonLat(
                    this.map.getCenter()
                );
            }
            if (delta < 0) {
                this.callback("down", [e, this.cumulative ? delta : -1]);
            } else {
                this.callback("up", [e, this.cumulative ? delta : 1]);
            }
        }
    },
    
    /**
     * Method: mousemove
     * Update the stored mousePosition on every move.
     * 
     * Parameters:
     * evt - {Event} The browser event
     *
     * Returns: 
     * {Boolean} Allow event propagation
     */
    mousemove: function (evt) {
        this.mousePosition = evt.xy;
    },

    /**
     * Method: activate 
     */
    activate: function (evt) {
        if (OpenLayers.Handler.prototype.activate.apply(this, arguments)) {
            //register mousewheel events specifically on the window and document
            var wheelListener = this.wheelListener;
            OpenLayers.Event.observe(window, "DOMMouseScroll", wheelListener);
            OpenLayers.Event.observe(window, "mousewheel", wheelListener);
            OpenLayers.Event.observe(document, "mousewheel", wheelListener);
            return true;
        } else {
            return false;
        }
    },

    /**
     * Method: deactivate 
     */
    deactivate: function (evt) {
        if (OpenLayers.Handler.prototype.deactivate.apply(this, arguments)) {
            // unregister mousewheel events specifically on the window and document
            var wheelListener = this.wheelListener;
            OpenLayers.Event.stopObserving(window, "DOMMouseScroll", wheelListener);
            OpenLayers.Event.stopObserving(window, "mousewheel", wheelListener);
            OpenLayers.Event.stopObserving(document, "mousewheel", wheelListener);
            return true;
        } else {
            return false;
        }
    },

    CLASS_NAME: "OpenLayers.Handler.MouseWheel"
});
/* ======================================================================
    OpenLayers/Layer.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */


/**
 * @requires OpenLayers/Map.js
 * @requires OpenLayers/Projection.js
 */

/**
 * Class: OpenLayers.Layer
 */
OpenLayers.Layer = OpenLayers.Class({

    /**
     * APIProperty: id
     * {String}
     */
    id: null,

    /** 
     * APIProperty: name
     * {String}
     */
    name: null,

    /** 
     * APIProperty: div
     * {DOMElement}
     */
    div: null,

    /**
     * Property: opacity
     * {Float} The layer's opacity. Float number between 0.0 and 1.0.
     */
    opacity: null,

    /**
     * APIProperty: alwaysInRange
     * {Boolean} If a layer's display should not be scale-based, this should 
     *     be set to true. This will cause the layer, as an overlay, to always 
     *     be 'active', by always returning true from the calculateInRange() 
     *     function. 
     * 
     *     If not explicitly specified for a layer, its value will be 
     *     determined on startup in initResolutions() based on whether or not 
     *     any scale-specific properties have been set as options on the 
     *     layer. If no scale-specific options have been set on the layer, we 
     *     assume that it should always be in range.
     * 
     *     See #987 for more info.
     */
    alwaysInRange: null,   

    /**
     * Constant: EVENT_TYPES
     * {Array(String)} Supported application event types.  Register a listener
     *     for a particular event with the following syntax:
     * (code)
     * layer.events.register(type, obj, listener);
     * (end)
     *
     * Listeners will be called with a reference to an event object.  The
     *     properties of this event depends on exactly what happened.
     *
     * All event objects have at least the following properties:
     * object - {Object} A reference to layer.events.object.
     * element - {DOMElement} A reference to layer.events.element.
     *
     * Supported map event types:
     * loadstart - Triggered when layer loading starts.
     * loadend - Triggered when layer loading ends.
     * loadcancel - Triggered when layer loading is canceled.
     * visibilitychanged - Triggered when layer visibility is changed.
     * move - Triggered when layer moves (triggered with every mousemove
     *     during a drag).
     * moveend - Triggered when layer is done moving, object passed as
     *     argument has a zoomChanged boolean property which tells that the
     *     zoom has changed.
     */
    EVENT_TYPES: ["loadstart", "loadend", "loadcancel", "visibilitychanged",
                  "move", "moveend"],
        
    /**
     * APIProperty: events
     * {<OpenLayers.Events>}
     */
    events: null,

    /**
     * APIProperty: map
     * {<OpenLayers.Map>} This variable is set when the layer is added to 
     *     the map, via the accessor function setMap().
     */
    map: null,
    
    /**
     * APIProperty: isBaseLayer
     * {Boolean} Whether or not the layer is a base layer. This should be set 
     *     individually by all subclasses. Default is false
     */
    isBaseLayer: false,
 
    /**
     * Property: alpha
     * {Boolean} The layer's images have an alpha channel.  Default is false. 
     */
    alpha: false,

    /** 
     * APIProperty: displayInLayerSwitcher
     * {Boolean} Display the layer's name in the layer switcher.  Default is
     *     true.
     */
    displayInLayerSwitcher: true,

    /**
     * APIProperty: visibility
     * {Boolean} The layer should be displayed in the map.  Default is true.
     */
    visibility: true,

    /**
     * APIProperty: attribution
     * {String} Attribution string, displayed when an 
     *     <OpenLayers.Control.Attribution> has been added to the map.
     */
    attribution: null, 

    /** 
     * Property: inRange
     * {Boolean} The current map resolution is within the layer's min/max 
     *     range. This is set in <OpenLayers.Map.setCenter> whenever the zoom 
     *     changes.
     */
    inRange: false,
    
    /**
     * Propery: imageSize
     * {<OpenLayers.Size>} For layers with a gutter, the image is larger than 
     *     the tile by twice the gutter in each dimension.
     */
    imageSize: null,
    
    /**
     * Property: imageOffset
     * {<OpenLayers.Pixel>} For layers with a gutter, the image offset 
     *     represents displacement due to the gutter.
     */
    imageOffset: null,

  // OPTIONS

    /** 
     * Property: options
     * {Object} An optional object whose properties will be set on the layer.
     *     Any of the layer properties can be set as a property of the options
     *     object and sent to the constructor when the layer is created.
     */
    options: null,

    /**
     * APIProperty: eventListeners
     * {Object} If set as an option at construction, the eventListeners
     *     object will be registered with <OpenLayers.Events.on>.  Object
     *     structure must be a listeners object as shown in the example for
     *     the events.on method.
     */
    eventListeners: null,

    /**
     * APIProperty: gutter
     * {Integer} Determines the width (in pixels) of the gutter around image
     *     tiles to ignore.  By setting this property to a non-zero value,
     *     images will be requested that are wider and taller than the tile
     *     size by a value of 2 x gutter.  This allows artifacts of rendering
     *     at tile edges to be ignored.  Set a gutter value that is equal to
     *     half the size of the widest symbol that needs to be displayed.
     *     Defaults to zero.  Non-tiled layers always have zero gutter.
     */ 
    gutter: 0, 

    /**
     * APIProperty: projection
     * {<OpenLayers.Projection>} or {<String>} Set in the layer options to
     *     override the default projection string this layer - also set maxExtent,
     *     maxResolution, and units if appropriate. Can be either a string or
     *     an <OpenLayers.Projection> object when created -- will be converted
     *     to an object when setMap is called if a string is passed.  
     */
    projection: null,    
    
    /**
     * APIProperty: units
     * {String} The layer map units.  Defaults to 'degrees'.  Possible values
     *     are 'degrees' (or 'dd'), 'm', 'ft', 'km', 'mi', 'inches'.
     */
    units: null,

    /**
     * APIProperty: scales
     * {Array}  An array of map scales in descending order.  The values in the
     *     array correspond to the map scale denominator.  Note that these
     *     values only make sense if the display (monitor) resolution of the
     *     client is correctly guessed by whomever is configuring the
     *     application.  In addition, the units property must also be set.
     *     Use <resolutions> instead wherever possible.
     */
    scales: null,

    /**
     * APIProperty: resolutions
     * {Array} A list of map resolutions (map units per pixel) in descending
     *     order.  If this is not set in the layer constructor, it will be set
     *     based on other resolution related properties (maxExtent,
     *     maxResolution, maxScale, etc.).
     */
    resolutions: null,
    
    /**
     * APIProperty: maxExtent
     * {<OpenLayers.Bounds>}  The center of these bounds will not stray outside
     *     of the viewport extent during panning.  In addition, if
     *     <displayOutsideMaxExtent> is set to false, data will not be
     *     requested that falls completely outside of these bounds.
     */
    maxExtent: null,
    
    /**
     * APIProperty: minExtent
     * {<OpenLayers.Bounds>}
     */
    minExtent: null,
    
    /**
     * APIProperty: maxResolution
     * {Float} Default max is 360 deg / 256 px, which corresponds to
     *     zoom level 0 on gmaps.  Specify a different value in the layer 
     *     options if you are not using a geographic projection and 
     *     displaying the whole world.
     */
    maxResolution: null,

    /**
     * APIProperty: minResolution
     * {Float}
     */
    minResolution: null,

    /**
     * APIProperty: numZoomLevels
     * {Integer}
     */
    numZoomLevels: null,
   
    /**
     * APIProperty: minScale
     * {Float}
     */
    minScale: null,
    
    /**
     * APIProperty: maxScale
     * {Float}
     */
    maxScale: null,

    /**
     * APIProperty: displayOutsideMaxExtent
     * {Boolean} Request map tiles that are completely outside of the max 
     *     extent for this layer. Defaults to false.
     */
    displayOutsideMaxExtent: false,

    /**
     * APIProperty: wrapDateLine
     * {Boolean} #487 for more info.   
     */
    wrapDateLine: false,
    
    /**
     * APIProperty: transitionEffect
     * {String} The transition effect to use when the map is panned or
     *     zoomed.  
     *
     * There are currently two supported values:
     *  - *null* No transition effect (the default).
     *  - *resize*  Existing tiles are resized on zoom to provide a visual
     *    effect of the zoom having taken place immediately.  As the
     *    new tiles become available, they are drawn over top of the
     *    resized tiles.
     */
    transitionEffect: null,
    
    /**
     * Property: SUPPORTED_TRANSITIONS
     * {Array} An immutable (that means don't change it!) list of supported 
     *     transitionEffect values.
     */
    SUPPORTED_TRANSITIONS: ['resize'],
    
    /**
     * Constructor: OpenLayers.Layer
     *
     * Parameters:
     * name - {String} The layer name
     * options - {Object} Hashtable of extra options to tag onto the layer
     */
    initialize: function(name, options) {

        this.addOptions(options);

        this.name = name;
        
        if (this.id == null) {

            this.id = OpenLayers.Util.createUniqueID(this.CLASS_NAME + "_");

            this.div = OpenLayers.Util.createDiv(this.id);
            this.div.style.width = "100%";
            this.div.style.height = "100%";
            this.div.dir = "ltr";

            this.events = new OpenLayers.Events(this, this.div, 
                                                this.EVENT_TYPES);
            if(this.eventListeners instanceof Object) {
                this.events.on(this.eventListeners);
            }

        }

        if (this.wrapDateLine) {
            this.displayOutsideMaxExtent = true;
        }
    },
    
    /**
     * Method: destroy
     * Destroy is a destructor: this is to alleviate cyclic references which
     *     the Javascript garbage cleaner can not take care of on its own.
     *
     * Parameters:
     * setNewBaseLayer - {Boolean} Set a new base layer when this layer has
     *     been destroyed.  Default is true.
     */
    destroy: function(setNewBaseLayer) {
        if (setNewBaseLayer == null) {
            setNewBaseLayer = true;
        }
        if (this.map != null) {
            this.map.removeLayer(this, setNewBaseLayer);
        }
        this.projection = null;
        this.map = null;
        this.name = null;
        this.div = null;
        this.options = null;

        if (this.events) {
            if(this.eventListeners) {
                this.events.un(this.eventListeners);
            }
            this.events.destroy();
        }
        this.eventListeners = null;
        this.events = null;
    },
    
   /**
    * Method: clone
    *
    * Parameters:
    * obj - {<OpenLayers.Layer>} The layer to be cloned
    *
    * Returns:
    * {<OpenLayers.Layer>} An exact clone of this <OpenLayers.Layer>
    */
    clone: function (obj) {
        
        if (obj == null) {
            obj = new OpenLayers.Layer(this.name, this.getOptions());
        }
        
        // catch any randomly tagged-on properties
        OpenLayers.Util.applyDefaults(obj, this);
        
        // a cloned layer should never have its map property set
        //  because it has not been added to a map yet. 
        obj.map = null;
        
        return obj;
    },
    
    /**
     * Method: getOptions
     * Extracts an object from the layer with the properties that were set as
     *     options, but updates them with the values currently set on the
     *     instance.
     * 
     * Returns:
     * {Object} the <options> of the layer, representing the current state.
     */
    getOptions: function() {
        var options = {};
        for(var o in this.options) {
            options[o] = this[o];
        }
        return options;
    },
    
    /** 
     * APIMethod: setName
     * Sets the new layer name for this layer.  Can trigger a changelayer event
     *     on the map.
     *
     * Parameters:
     * newName - {String} The new name.
     */
    setName: function(newName) {
        if (newName != this.name) {
            this.name = newName;
            if (this.map != null) {
                this.map.events.triggerEvent("changelayer", {
                    layer: this,
                    property: "name"
                });
            }
        }
    },    
    
   /**
    * APIMethod: addOptions
    * 
    * Parameters:
    * newOptions - {Object}
    */
    addOptions: function (newOptions) {
        
        if (this.options == null) {
            this.options = {};
        }
        
        // update our copy for clone
        OpenLayers.Util.extend(this.options, newOptions);

        // add new options to this
        OpenLayers.Util.extend(this, newOptions);
    },
    
    /**
     * APIMethod: onMapResize
     * This function can be implemented by subclasses
     */
    onMapResize: function() {
        //this function can be implemented by subclasses  
    },

    /**
     * APIMethod: redraw
     * Redraws the layer.  Returns true if the layer was redrawn, false if not.
     *
     * Returns:
     * {Boolean} The layer was redrawn.
     */
    redraw: function() {
        var redrawn = false;
        if (this.map) {

            // min/max Range may have changed
            this.inRange = this.calculateInRange();

            // map's center might not yet be set
            var extent = this.getExtent();

            if (extent && this.inRange && this.visibility) {
                var zoomChanged = true;
                this.moveTo(extent, zoomChanged, false);
                this.events.triggerEvent("moveend",
                    {"zoomChanged": zoomChanged});
                redrawn = true;
            }
        }
        return redrawn;
    },

    /**
     * Method: moveTo
     * 
     * Parameters:
     * bound - {<OpenLayers.Bounds>}
     * zoomChanged - {Boolean} Tells when zoom has changed, as layers have to
     *     do some init work in that case.
     * dragging - {Boolean}
     */
    moveTo:function(bounds, zoomChanged, dragging) {
        var display = this.visibility;
        if (!this.isBaseLayer) {
            display = display && this.inRange;
        }
        this.display(display);
    },

    /**
     * Method: setMap
     * Set the map property for the layer. This is done through an accessor
     *     so that subclasses can override this and take special action once 
     *     they have their map variable set. 
     * 
     *     Here we take care to bring over any of the necessary default 
     *     properties from the map. 
     * 
     * Parameters:
     * map - {<OpenLayers.Map>}
     */
    setMap: function(map) {
        if (this.map == null) {
        
            this.map = map;
            
            // grab some essential layer data from the map if it hasn't already
            //  been set
            this.maxExtent = this.maxExtent || this.map.maxExtent;
            this.projection = this.projection || this.map.projection;
            
            if (this.projection && typeof this.projection == "string") {
                this.projection = new OpenLayers.Projection(this.projection);
            }
            
            // Check the projection to see if we can get units -- if not, refer
            // to properties.
            this.units = this.projection.getUnits() ||
                         this.units || this.map.units;
            
            this.initResolutions();
            
            if (!this.isBaseLayer) {
                this.inRange = this.calculateInRange();
                var show = ((this.visibility) && (this.inRange));
                this.div.style.display = show ? "" : "none";
            }
            
            // deal with gutters
            this.setTileSize();
        }
    },
    
    /**
     * Method: afterAdd
     * Called at the end of the map.addLayer sequence.  At this point, the map
     *     will have a base layer.  To be overridden by subclasses.
     */
    afterAdd: function() {
    },
    
    /**
     * APIMethod: removeMap
     * Just as setMap() allows each layer the possibility to take a 
     *     personalized action on being added to the map, removeMap() allows
     *     each layer to take a personalized action on being removed from it. 
     *     For now, this will be mostly unused, except for the EventPane layer,
     *     which needs this hook so that it can remove the special invisible
     *     pane. 
     * 
     * Parameters:
     * map - {<OpenLayers.Map>}
     */
    removeMap: function(map) {
        //to be overridden by subclasses
    },
    
    /**
     * APIMethod: getImageSize
     *
     * Parameters:
     * bounds - {<OpenLayers.Bounds>} optional tile bounds, can be used
     *     by subclasses that have to deal with different tile sizes at the
     *     layer extent edges (e.g. Zoomify)
     * 
     * Returns:
     * {<OpenLayers.Size>} The size that the image should be, taking into 
     *     account gutters.
     */ 
    getImageSize: function(bounds) { 
        return (this.imageSize || this.tileSize); 
    },    
  
    /**
     * APIMethod: setTileSize
     * Set the tile size based on the map size.  This also sets layer.imageSize
     *     and layer.imageOffset for use by Tile.Image.
     * 
     * Parameters:
     * size - {<OpenLayers.Size>}
     */
    setTileSize: function(size) {
        var tileSize = (size) ? size :
                                ((this.tileSize) ? this.tileSize :
                                                   this.map.getTileSize());
        this.tileSize = tileSize;
        if(this.gutter) {
          // layers with gutters need non-null tile sizes
          //if(tileSize == null) {
          //    OpenLayers.console.error("Error in layer.setMap() for " +
          //                              this.name + ": layers with " +
          //                              "gutters need non-null tile sizes");
          //}
            this.imageOffset = new OpenLayers.Pixel(-this.gutter, 
                                                    -this.gutter); 
            this.imageSize = new OpenLayers.Size(tileSize.w + (2*this.gutter), 
                                                 tileSize.h + (2*this.gutter)); 
        }
    },

    /**
     * APIMethod: getVisibility
     * 
     * Returns:
     * {Boolean} The layer should be displayed (if in range).
     */
    getVisibility: function() {
        return this.visibility;
    },

    /** 
     * APIMethod: setVisibility
     * Set the visibility flag for the layer and hide/show & redraw 
     *     accordingly. Fire event unless otherwise specified
     * 
     * Note that visibility is no longer simply whether or not the layer's
     *     style.display is set to "block". Now we store a 'visibility' state 
     *     property on the layer class, this allows us to remember whether or 
     *     not we *desire* for a layer to be visible. In the case where the 
     *     map's resolution is out of the layer's range, this desire may be 
     *     subverted.
     * 
     * Parameters:
     * visible - {Boolean} Whether or not to display the layer (if in range)
     */
    setVisibility: function(visibility) {
        if (visibility != this.visibility) {
            this.visibility = visibility;
            this.display(visibility);
            this.redraw();
            if (this.map != null) {
                this.map.events.triggerEvent("changelayer", {
                    layer: this,
                    property: "visibility"
                });
            }
            this.events.triggerEvent("visibilitychanged");
        }
    },

    /** 
     * APIMethod: display
     * Hide or show the Layer
     * 
     * Parameters:
     * display - {Boolean}
     */
    display: function(display) {
        if (display != (this.div.style.display != "none")) {
            this.div.style.display = (display && this.calculateInRange()) ? "block" : "none";
        }
    },

    /**
     * APIMethod: calculateInRange
     * 
     * Returns:
     * {Boolean} The layer is displayable at the current map's current
     *     resolution. Note that if 'alwaysInRange' is true for the layer, 
     *     this function will always return true.
     */
    calculateInRange: function() {
        var inRange = false;

        if (this.alwaysInRange) {
            inRange = true;
        } else {
            if (this.map) {
                var resolution = this.map.getResolution();
                inRange = ( (resolution >= this.minResolution) &&
                            (resolution <= this.maxResolution) );
            }
        }
        return inRange;
    },

    /** 
     * APIMethod: setIsBaseLayer
     * 
     * Parameters:
     * isBaseLayer - {Boolean}
     */
    setIsBaseLayer: function(isBaseLayer) {
        if (isBaseLayer != this.isBaseLayer) {
            this.isBaseLayer = isBaseLayer;
            if (this.map != null) {
                this.map.events.triggerEvent("changebaselayer", {
                    layer: this
                });
            }
        }
    },

  /********************************************************/
  /*                                                      */
  /*                 Baselayer Functions                  */
  /*                                                      */
  /********************************************************/
  
    /** 
     * Method: initResolutions
     * This method's responsibility is to set up the 'resolutions' array 
     *     for the layer -- this array is what the layer will use to interface
     *     between the zoom levels of the map and the resolution display 
     *     of the layer.
     * 
     * The user has several options that determine how the array is set up.
     *  
     * For a detailed explanation, see the following wiki from the 
     *     openlayers.org homepage:
     *     http://trac.openlayers.org/wiki/SettingZoomLevels
     */
    initResolutions: function() {

        // These are the relevant options which are used for calculating 
        //  resolutions information.
        //
        var props = new Array(
          'projection', 'units',
          'scales', 'resolutions',
          'maxScale', 'minScale', 
          'maxResolution', 'minResolution', 
          'minExtent', 'maxExtent',
          'numZoomLevels', 'maxZoomLevel'
        );

        //these are the properties which do *not* imply that user wishes 
        // this layer to be scale-dependant
        var notScaleProps = ['projection', 'units'];    

        //should the layer be scale-dependant? default is false -- this will 
        // only be set true if we find that the user has specified a property
        // from the 'props' array that is not in 'notScaleProps'
        var useInRange = false;

        // First we create a new object where we will store all of the 
        //  resolution-related properties that we find in either the layer's
        //  'options' array or from the map.
        //
        var confProps = {};        
        for(var i=0, len=props.length; i<len; i++) {
            var property = props[i];
            
            // If the layer had one of these properties set *and* it is 
            // a scale property (is not a non-scale property), then we assume
            // the user did intend to use scale-dependant display (useInRange).
            if (this.options[property] && 
                OpenLayers.Util.indexOf(notScaleProps, property) == -1) {
                useInRange = true;
            }
                   
            confProps[property] = this.options[property] || this.map[property];
        }

        //only automatically set 'alwaysInRange' if the user hasn't already 
        // set it (to true or false, since the default is null). If user did
        // not intend to use scale-dependant display then we set they layer
        // as alwaysInRange. This means calculateInRange() will always return 
        // true and the layer will never be turned off due to scale changes.
        //
        if (this.alwaysInRange == null) {
            this.alwaysInRange = !useInRange;
        }

        // Do not use the scales array set at the map level if 
        // either minScale or maxScale or both are set at the
        // layer level
        if ((this.options.minScale != null ||
             this.options.maxScale != null) &&
            this.options.scales == null) {

            confProps.scales = null;
        }
        // Do not use the resolutions array set at the map level if 
        // either minResolution or maxResolution or both are set at the
        // layer level
        if ((this.options.minResolution != null ||
             this.options.maxResolution != null) &&
            this.options.resolutions == null) {

            confProps.resolutions = null;
        }

        // If numZoomLevels hasn't been set and the maxZoomLevel *has*, 
        //  then use maxZoomLevel to calculate numZoomLevels
        //
        if ( (!confProps.numZoomLevels) && (confProps.maxZoomLevel) ) {
            confProps.numZoomLevels = confProps.maxZoomLevel + 1;
        }

        // First off, we take whatever hodge-podge of values we have and 
        //  calculate/distill them down into a resolutions[] array
        //
        if ((confProps.scales != null) || (confProps.resolutions != null)) {
          //preset levels
            if (confProps.scales != null) {
                confProps.resolutions = [];
                for(var i=0, len=confProps.scales.length; i<len; i++) {
                    var scale = confProps.scales[i];
                    confProps.resolutions[i] = 
                       OpenLayers.Util.getResolutionFromScale(scale, 
                                                              confProps.units);
                }
            }
            confProps.numZoomLevels = confProps.resolutions.length;

        } else {
          //maxResolution and numZoomLevels based calculation

            // determine maxResolution
            if (confProps.minScale) {
                confProps.maxResolution = 
                    OpenLayers.Util.getResolutionFromScale(confProps.minScale, 
                                                           confProps.units);
            } else if (confProps.maxResolution == "auto") {
                var viewSize = this.map.getSize();
                var wRes = confProps.maxExtent.getWidth() / viewSize.w;
                var hRes = confProps.maxExtent.getHeight()/ viewSize.h;
                confProps.maxResolution = Math.max(wRes, hRes);
            } 

            // determine minResolution
            if (confProps.maxScale != null) {           
                confProps.minResolution = 
                    OpenLayers.Util.getResolutionFromScale(confProps.maxScale, 
                                                           confProps.units);
            } else if ( (confProps.minResolution == "auto") && 
                        (confProps.minExtent != null) ) {
                var viewSize = this.map.getSize();
                var wRes = confProps.minExtent.getWidth() / viewSize.w;
                var hRes = confProps.minExtent.getHeight()/ viewSize.h;
                confProps.minResolution = Math.max(wRes, hRes);
            } 

            // determine numZoomLevels if not already set on the layer
            // this gives numZoomLevels assuming approximately base 2 scaling
            if (confProps.minResolution != null &&
                this.options.numZoomLevels == undefined) {
                var ratio = confProps.maxResolution / confProps.minResolution;
                confProps.numZoomLevels = 
                    Math.floor(Math.log(ratio) / Math.log(2)) + 1;
            }
            
            // now we have numZoomLevels and maxResolution, 
            //  we can populate the resolutions array
            confProps.resolutions = new Array(confProps.numZoomLevels);
            var base = 2;
            if(typeof confProps.minResolution == "number" &&
               confProps.numZoomLevels > 1) {
                /**
                 * If maxResolution and minResolution are set (or related
                 * scale properties), we calculate the base for exponential
                 * scaling that starts at maxResolution and ends at
                 * minResolution in numZoomLevels steps.
                 */
                base = Math.pow(
                    (confProps.maxResolution / confProps.minResolution),
                    (1 / (confProps.numZoomLevels - 1))
                );
            }
            for (var i=0; i < confProps.numZoomLevels; i++) {
                var res = confProps.maxResolution / Math.pow(base, i);
                confProps.resolutions[i] = res;
            }
        }
        
        //sort resolutions array ascendingly
        //
        confProps.resolutions.sort( function(a, b) { return(b-a); } );

        // now set our newly calculated values back to the layer 
        //  Note: We specifically do *not* set them to layer.options, which we 
        //        will preserve as it was when we added this layer to the map. 
        //        this way cloned layers reset themselves to new map div 
        //        dimensions)
        //

        this.resolutions = confProps.resolutions;
        this.maxResolution = confProps.resolutions[0];
        var lastIndex = confProps.resolutions.length - 1;
        this.minResolution = confProps.resolutions[lastIndex];
        
        this.scales = [];
        for(var i=0, len=confProps.resolutions.length; i<len; i++) {
            this.scales[i] = 
               OpenLayers.Util.getScaleFromResolution(confProps.resolutions[i], 
                                                      confProps.units);
        }
        this.minScale = this.scales[0];
        this.maxScale = this.scales[this.scales.length - 1];
        
        this.numZoomLevels = confProps.numZoomLevels;
    },

    /**
     * APIMethod: getResolution
     * 
     * Returns:
     * {Float} The currently selected resolution of the map, taken from the
     *     resolutions array, indexed by current zoom level.
     */
    getResolution: function() {
        var zoom = this.map.getZoom();
        return this.getResolutionForZoom(zoom);
    },

    /** 
     * APIMethod: getExtent
     * 
     * Returns:
     * {<OpenLayers.Bounds>} A Bounds object which represents the lon/lat 
     *     bounds of the current viewPort.
     */
    getExtent: function() {
        // just use stock map calculateBounds function -- passing no arguments
        //  means it will user map's current center & resolution
        //
        return this.map.calculateBounds();
    },

    /**
     * APIMethod: getZoomForExtent
     * 
     * Parameters:
     * bounds - {<OpenLayers.Bounds>}
     * closest - {Boolean} Find the zoom level that most closely fits the 
     *     specified bounds. Note that this may result in a zoom that does 
     *     not exactly contain the entire extent.
     *     Default is false.
     *
     * Returns:
     * {Integer} The index of the zoomLevel (entry in the resolutions array) 
     *     for the passed-in extent. We do this by calculating the ideal 
     *     resolution for the given extent (based on the map size) and then 
     *     calling getZoomForResolution(), passing along the 'closest'
     *     parameter.
     */
    getZoomForExtent: function(extent, closest) {
        var viewSize = this.map.getSize();
        var idealResolution = Math.max( extent.getWidth()  / viewSize.w,
                                        extent.getHeight() / viewSize.h );

        return this.getZoomForResolution(idealResolution, closest);
    },
    
    /** 
     * Method: getDataExtent
     * Calculates the max extent which includes all of the data for the layer.
     *     This function is to be implemented by subclasses.
     * 
     * Returns:
     * {<OpenLayers.Bounds>}
     */
    getDataExtent: function () {
        //to be implemented by subclasses
    },

    /**
     * APIMethod: getResolutionForZoom
     * 
     * Parameter:
     * zoom - {Float}
     * 
     * Returns:
     * {Float} A suitable resolution for the specified zoom.
     */
    getResolutionForZoom: function(zoom) {
        zoom = Math.max(0, Math.min(zoom, this.resolutions.length - 1));
        var resolution;
        if(this.map.fractionalZoom) {
            var low = Math.floor(zoom);
            var high = Math.ceil(zoom);
            resolution = this.resolutions[low] -
                ((zoom-low) * (this.resolutions[low]-this.resolutions[high]));
        } else {
            resolution = this.resolutions[Math.round(zoom)];
        }
        return resolution;
    },

    /**
     * APIMethod: getZoomForResolution
     * 
     * Parameters:
     * resolution - {Float}
     * closest - {Boolean} Find the zoom level that corresponds to the absolute 
     *     closest resolution, which may result in a zoom whose corresponding
     *     resolution is actually smaller than we would have desired (if this
     *     is being called from a getZoomForExtent() call, then this means that
     *     the returned zoom index might not actually contain the entire 
     *     extent specified... but it'll be close).
     *     Default is false.
     * 
     * Returns:
     * {Integer} The index of the zoomLevel (entry in the resolutions array) 
     *     that corresponds to the best fit resolution given the passed in 
     *     value and the 'closest' specification.
     */
    getZoomForResolution: function(resolution, closest) {
        var zoom;
        if(this.map.fractionalZoom) {
            var lowZoom = 0;
            var highZoom = this.resolutions.length - 1;
            var highRes = this.resolutions[lowZoom];
            var lowRes = this.resolutions[highZoom];
            var res;
            for(var i=0, len=this.resolutions.length; i<len; ++i) {
                res = this.resolutions[i];
                if(res >= resolution) {
                    highRes = res;
                    lowZoom = i;
                }
                if(res <= resolution) {
                    lowRes = res;
                    highZoom = i;
                    break;
                }
            }
            var dRes = highRes - lowRes;
            if(dRes > 0) {
                zoom = lowZoom + ((highRes - resolution) / dRes);
            } else {
                zoom = lowZoom;
            }
        } else {
            var diff;
            var minDiff = Number.POSITIVE_INFINITY;
            for(var i=0, len=this.resolutions.length; i<len; i++) {            
                if (closest) {
                    diff = Math.abs(this.resolutions[i] - resolution);
                    if (diff > minDiff) {
                        break;
                    }
                    minDiff = diff;
                } else {
                    if (this.resolutions[i] < resolution) {
                        break;
                    }
                }
            }
            zoom = Math.max(0, i-1);
        }
        return zoom;
    },
    
    /**
     * APIMethod: getLonLatFromViewPortPx
     * 
     * Parameters:
     * viewPortPx - {<OpenLayers.Pixel>}
     *
     * Returns:
     * {<OpenLayers.LonLat>} An OpenLayers.LonLat which is the passed-in 
     *     view port <OpenLayers.Pixel>, translated into lon/lat by the layer.
     */
    getLonLatFromViewPortPx: function (viewPortPx) {
        var lonlat = null;
        if (viewPortPx != null) {
            var size = this.map.getSize();
            var center = this.map.getCenter();
            if (center) {
                var res  = this.map.getResolution();
        
                var delta_x = viewPortPx.x - (size.w / 2);
                var delta_y = viewPortPx.y - (size.h / 2);
            
                lonlat = new OpenLayers.LonLat(center.lon + delta_x * res ,
                                             center.lat - delta_y * res); 

                if (this.wrapDateLine) {
                    lonlat = lonlat.wrapDateLine(this.maxExtent);
                }
            } // else { DEBUG STATEMENT }
        }
        return lonlat;
    },

    /**
     * APIMethod: getViewPortPxFromLonLat
     * Returns a pixel location given a map location.  This method will return
     *     fractional pixel values.
     * 
     * Parameters:
     * lonlat - {<OpenLayers.LonLat>}
     *
     * Returns: 
     * {<OpenLayers.Pixel>} An <OpenLayers.Pixel> which is the passed-in 
     *     <OpenLayers.LonLat>,translated into view port pixels.
     */
    getViewPortPxFromLonLat: function (lonlat) {
        var px = null; 
        if (lonlat != null) {
            var resolution = this.map.getResolution();
            var extent = this.map.getExtent();
            px = new OpenLayers.Pixel(
                (1/resolution * (lonlat.lon - extent.left)),
                (1/resolution * (extent.top - lonlat.lat))
            );    
        }
        return px;
    },
    
    /**
     * APIMethod: setOpacity
     * Sets the opacity for the entire layer (all images)
     * 
     * Parameter:
     * opacity - {Float}
     */
    setOpacity: function(opacity) {
        if (opacity != this.opacity) {
            this.opacity = opacity;
            for(var i=0, len=this.div.childNodes.length; i<len; ++i) {
                var element = this.div.childNodes[i].firstChild;
                OpenLayers.Util.modifyDOMElement(element, null, null, null, 
                                                 null, null, null, opacity);
            }
            if (this.map != null) {
                this.map.events.triggerEvent("changelayer", {
                    layer: this,
                    property: "opacity"
                });
            }
        }
    },

    /**
     * Method: getZIndex
     * 
     * Returns: 
     * {Integer} the z-index of this layer
     */    
    getZIndex: function () {
        return this.div.style.zIndex;
    },

    /**
     * Method: setZIndex
     * 
     * Parameters: 
     * zIndex - {Integer}
     */    
    setZIndex: function (zIndex) {
        this.div.style.zIndex = zIndex;
    },

    /**
     * Method: adjustBounds
     * This function will take a bounds, and if wrapDateLine option is set
     *     on the layer, it will return a bounds which is wrapped around the 
     *     world. We do not wrap for bounds which *cross* the 
     *     maxExtent.left/right, only bounds which are entirely to the left 
     *     or entirely to the right.
     * 
     * Parameters:
     * bounds - {<OpenLayers.Bounds>}
     */
    adjustBounds: function (bounds) {

        if (this.gutter) {
            // Adjust the extent of a bounds in map units by the 
            // layer's gutter in pixels.
            var mapGutter = this.gutter * this.map.getResolution();
            bounds = new OpenLayers.Bounds(bounds.left - mapGutter,
                                           bounds.bottom - mapGutter,
                                           bounds.right + mapGutter,
                                           bounds.top + mapGutter);
        }

        if (this.wrapDateLine) {
            // wrap around the date line, within the limits of rounding error
            var wrappingOptions = { 
                'rightTolerance':this.getResolution()
            };    
            bounds = bounds.wrapDateLine(this.maxExtent, wrappingOptions);
                              
        }
        return bounds;
    },

    CLASS_NAME: "OpenLayers.Layer"
});
/* ======================================================================
    OpenLayers/Control/DragFeature.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */


/**
 * @requires OpenLayers/Control.js
 * @requires OpenLayers/Handler/Drag.js
 * @requires OpenLayers/Handler/Feature.js
 */

/**
 * Class: OpenLayers.Control.DragFeature
 * The DragFeature control moves a feature with a drag of the mouse. Create a
 * new control with the <OpenLayers.Control.DragFeature> constructor.
 *
 * Inherits From:
 *  - <OpenLayers.Control>
 */
OpenLayers.Control.DragFeature = OpenLayers.Class(OpenLayers.Control, {

    /**
     * APIProperty: geometryTypes
     * {Array(String)} To restrict dragging to a limited set of geometry types,
     *     send a list of strings corresponding to the geometry class names.
     */
    geometryTypes: null,
    
    /**
     * APIProperty: onStart
     * {Function} Define this function if you want to know when a drag starts.
     *     The function should expect to receive two arguments: the feature
     *     that is about to be dragged and the pixel location of the mouse.
     *
     * Parameters:
     * feature - {<OpenLayers.Feature.Vector>} The feature that is about to be
     *     dragged.
     * pixel - {<OpenLayers.Pixel>} The pixel location of the mouse.
     */
    onStart: function(feature, pixel) {},

    /**
     * APIProperty: onDrag
     * {Function} Define this function if you want to know about each move of a
     *     feature. The function should expect to receive two arguments: the
     *     feature that is being dragged and the pixel location of the mouse.
     *
     * Parameters:
     * feature - {<OpenLayers.Feature.Vector>} The feature that was dragged.
     * pixel - {<OpenLayers.Pixel>} The pixel location of the mouse.
     */
    onDrag: function(feature, pixel) {},

    /**
     * APIProperty: onComplete
     * {Function} Define this function if you want to know when a feature is
     *     done dragging. The function should expect to receive two arguments:
     *     the feature that is being dragged and the pixel location of the
     *     mouse.
     *
     * Parameters:
     * feature - {<OpenLayers.Feature.Vector>} The feature that was dragged.
     * pixel - {<OpenLayers.Pixel>} The pixel location of the mouse.
     */
    onComplete: function(feature, pixel) {},

    /**
     * APIProperty: documentDrag
     * {Boolean} If set to true, mouse dragging will continue even if the
     *     mouse cursor leaves the map viewport. Default is false.
     */
    documentDrag: false,
    
    /**
     * Property: layer
     * {<OpenLayers.Layer.Vector>}
     */
    layer: null,
    
    /**
     * Property: feature
     * {<OpenLayers.Feature.Vector>}
     */
    feature: null,

    /**
     * Property: dragCallbacks
     * {Object} The functions that are sent to the drag handler for callback.
     */
    dragCallbacks: {},

    /**
     * Property: featureCallbacks
     * {Object} The functions that are sent to the feature handler for callback.
     */
    featureCallbacks: {},
    
    /**
     * Property: lastPixel
     * {<OpenLayers.Pixel>}
     */
    lastPixel: null,

    /**
     * Constructor: OpenLayers.Control.DragFeature
     * Create a new control to drag features.
     *
     * Parameters:
     * layer - {<OpenLayers.Layer.Vector>} The layer containing features to be
     *     dragged.
     * options - {Object} Optional object whose properties will be set on the
     *     control.
     */
    initialize: function(layer, options) {
        OpenLayers.Control.prototype.initialize.apply(this, [options]);
        this.layer = layer;
        this.handlers = {
            drag: new OpenLayers.Handler.Drag(
                this, OpenLayers.Util.extend({
                    down: this.downFeature,
                    move: this.moveFeature,
                    up: this.upFeature,
                    out: this.cancel,
                    done: this.doneDragging
                }, this.dragCallbacks), {
                    documentDrag: this.documentDrag
                }
            ),
            feature: new OpenLayers.Handler.Feature(
                this, this.layer, OpenLayers.Util.extend({
                    over: this.overFeature,
                    out: this.outFeature
                }, this.featureCallbacks),
                {geometryTypes: this.geometryTypes}
            )
        };
    },
    
    /**
     * APIMethod: destroy
     * Take care of things that are not handled in superclass
     */
    destroy: function() {
        this.layer = null;
        OpenLayers.Control.prototype.destroy.apply(this, []);
    },

    /**
     * APIMethod: activate
     * Activate the control and the feature handler.
     * 
     * Returns:
     * {Boolean} Successfully activated the control and feature handler.
     */
    activate: function() {
        return (this.handlers.feature.activate() &&
                OpenLayers.Control.prototype.activate.apply(this, arguments));
    },

    /**
     * APIMethod: deactivate
     * Deactivate the control and all handlers.
     * 
     * Returns:
     * {Boolean} Successfully deactivated the control.
     */
    deactivate: function() {
        // the return from the handlers is unimportant in this case
        this.handlers.drag.deactivate();
        this.handlers.feature.deactivate();
        this.feature = null;
        this.dragging = false;
        this.lastPixel = null;
        OpenLayers.Element.removeClass(
            this.map.viewPortDiv, this.displayClass + "Over"
        );
        return OpenLayers.Control.prototype.deactivate.apply(this, arguments);
    },

    /**
     * Method: overFeature
     * Called when the feature handler detects a mouse-over on a feature.
     *     This activates the drag handler.
     *
     * Parameters:
     * feature - {<OpenLayers.Feature.Vector>} The selected feature.
     */
    overFeature: function(feature) {
        if(!this.handlers.drag.dragging) {
            this.feature = feature;
            this.handlers.drag.activate();
            this.over = true;
            OpenLayers.Element.addClass(this.map.viewPortDiv, this.displayClass + "Over");
        } else {
            if(this.feature.id == feature.id) {
                this.over = true;
            } else {
                this.over = false;
            }
        }
    },

    /**
     * Method: downFeature
     * Called when the drag handler detects a mouse-down.
     *
     * Parameters:
     * pixel - {<OpenLayers.Pixel>} Location of the mouse event.
     */
    downFeature: function(pixel) {
        this.lastPixel = pixel;
        this.onStart(this.feature, pixel);
    },

    /**
     * Method: moveFeature
     * Called when the drag handler detects a mouse-move.  Also calls the
     *     optional onDrag method.
     * 
     * Parameters:
     * pixel - {<OpenLayers.Pixel>} Location of the mouse event.
     */
    moveFeature: function(pixel) {
        var res = this.map.getResolution();
        this.feature.geometry.move(res * (pixel.x - this.lastPixel.x),
                                   res * (this.lastPixel.y - pixel.y));
        this.layer.drawFeature(this.feature);
        this.lastPixel = pixel;
        this.onDrag(this.feature, pixel);
    },

    /**
     * Method: upFeature
     * Called when the drag handler detects a mouse-up.
     * 
     * Parameters:
     * pixel - {<OpenLayers.Pixel>} Location of the mouse event.
     */
    upFeature: function(pixel) {
        if(!this.over) {
            this.handlers.drag.deactivate();
        }
    },

    /**
     * Method: doneDragging
     * Called when the drag handler is done dragging.
     *
     * Parameters:
     * pixel - {<OpenLayers.Pixel>} The last event pixel location.  If this event
     *     came from a mouseout, this may not be in the map viewport.
     */
    doneDragging: function(pixel) {
        this.onComplete(this.feature, pixel);
    },

    /**
     * Method: outFeature
     * Called when the feature handler detects a mouse-out on a feature.
     *
     * Parameters:
     * feature - {<OpenLayers.Feature.Vector>} The feature that the mouse left.
     */
    outFeature: function(feature) {
        if(!this.handlers.drag.dragging) {
            this.over = false;
            this.handlers.drag.deactivate();
            OpenLayers.Element.removeClass(
                this.map.viewPortDiv, this.displayClass + "Over"
            );
            this.feature = null;
        } else {
            if(this.feature.id == feature.id) {
                this.over = false;
            }
        }
    },
        
    /**
     * Method: cancel
     * Called when the drag handler detects a mouse-out (from the map viewport).
     */
    cancel: function() {
        this.handlers.drag.deactivate();
        this.over = false;
    },

    /**
     * Method: setMap
     * Set the map property for the control and all handlers.
     *
     * Parameters: 
     * map - {<OpenLayers.Map>} The control's map.
     */
    setMap: function(map) {
        this.handlers.drag.setMap(map);
        this.handlers.feature.setMap(map);
        OpenLayers.Control.prototype.setMap.apply(this, arguments);
    },

    CLASS_NAME: "OpenLayers.Control.DragFeature"
});
/* ======================================================================
    OpenLayers/Control/DragPan.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */

/**
 * @requires OpenLayers/Control.js
 * @requires OpenLayers/Handler/Drag.js
 */

/**
 * Class: OpenLayers.Control.DragPan
 * The DragPan control pans the map with a drag of the mouse.
 *
 * Inherits from:
 *  - <OpenLayers.Control>
 */
OpenLayers.Control.DragPan = OpenLayers.Class(OpenLayers.Control, {

    /** 
     * Property: type
     * {OpenLayers.Control.TYPES}
     */
    type: OpenLayers.Control.TYPE_TOOL,
    
    /**
     * Property: panned
     * {Boolean} The map moved.
     */
    panned: false,
    
    /**
     * Property: interval
     * {Integer} The number of milliseconds that should ellapse before
     *     panning the map again. Set this to increase dragging performance.
     *     Defaults to 25 milliseconds.
     */
    interval: 25,
    
    /**
     * APIProperty: documentDrag
     * {Boolean} If set to true, mouse dragging will continue even if the
     *     mouse cursor leaves the map viewport. Default is false.
     */
    documentDrag: false,
    
    /**
     * Method: draw
     * Creates a Drag handler, using <panMap> and
     * <panMapDone> as callbacks.
     */    
    draw: function() {
        this.handler = new OpenLayers.Handler.Drag(this, {
                "move": this.panMap,
                "done": this.panMapDone
            }, {
                interval: this.interval,
                documentDrag: this.documentDrag
            }
        );
    },

    /**
    * Method: panMap
    *
    * Parameters:
    * xy - {<OpenLayers.Pixel>} Pixel of the mouse position
    */
    panMap: function(xy) {
        this.panned = true;
        this.map.pan(
            this.handler.last.x - xy.x,
            this.handler.last.y - xy.y,
            {dragging: this.handler.dragging, animate: false}
        );
    },
    
    /**
     * Method: panMapDone
     * Finish the panning operation.  Only call setCenter (through <panMap>)
     *     if the map has actually been moved.
     *
     * Parameters:
     * xy - {<OpenLayers.Pixel>} Pixel of the mouse position
     */
    panMapDone: function(xy) {
        if(this.panned) {
            this.panMap(xy);
            this.panned = false;
        }
    },

    CLASS_NAME: "OpenLayers.Control.DragPan"
});
/* ======================================================================
    OpenLayers/Feature/Vector.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */

// TRASH THIS
OpenLayers.State = {
    /** states */
    UNKNOWN: 'Unknown',
    INSERT: 'Insert',
    UPDATE: 'Update',
    DELETE: 'Delete'
};

/**
 * @requires OpenLayers/Feature.js
 * @requires OpenLayers/Util.js
 */

/**
 * Class: OpenLayers.Feature.Vector
 * Vector features use the OpenLayers.Geometry classes as geometry description.
 * They have an 'attributes' property, which is the data object, and a 'style'
 * property, the default values of which are defined in the 
 * <OpenLayers.Feature.Vector.style> objects.
 * 
 * Inherits from:
 *  - <OpenLayers.Feature>
 */
OpenLayers.Feature.Vector = OpenLayers.Class(OpenLayers.Feature, {

    /** 
     * Property: fid 
     * {String} 
     */
    fid: null,
    
    /** 
     * APIProperty: geometry 
     * {<OpenLayers.Geometry>} 
     */
    geometry: null,

    /** 
     * APIProperty: attributes 
     * {Object} This object holds arbitrary properties that describe the
     *     feature.
     */
    attributes: null,

    /**
     * Property: bounds
     * {<OpenLayers.Bounds>} The box bounding that feature's geometry, that
     *     property can be set by an <OpenLayers.Format> object when
     *     deserializing the feature, so in most cases it represents an
     *     information set by the server. 
     */
    bounds: null,

    /** 
     * Property: state 
     * {String} 
     */
    state: null,
    
    /** 
     * APIProperty: style 
     * {Object} 
     */
    style: null,

    /**
     * APIProperty: url
     * {String} If this property is set it will be taken into account by
     *     {<OpenLayers.HTTP>} when upadting or deleting the feature.
     */
    url: null,
    
    /**
     * Property: renderIntent
     * {String} rendering intent currently being used
     */
    renderIntent: "default",

    /** 
     * Constructor: OpenLayers.Feature.Vector
     * Create a vector feature. 
     * 
     * Parameters:
     * geometry - {<OpenLayers.Geometry>} The geometry that this feature
     *     represents.
     * attributes - {Object} An optional object that will be mapped to the
     *     <attributes> property. 
     * style - {Object} An optional style object.
     */
    initialize: function(geometry, attributes, style) {
        OpenLayers.Feature.prototype.initialize.apply(this,
                                                      [null, null, attributes]);
        this.lonlat = null;
        this.geometry = geometry ? geometry : null;
        this.state = null;
        this.attributes = {};
        if (attributes) {
            this.attributes = OpenLayers.Util.extend(this.attributes,
                                                     attributes);
        }
        this.style = style ? style : null; 
    },
    
    /** 
     * Method: destroy
     * nullify references to prevent circular references and memory leaks
     */
    destroy: function() {
        if (this.layer) {
            this.layer.removeFeatures(this);
            this.layer = null;
        }
            
        this.geometry = null;
        OpenLayers.Feature.prototype.destroy.apply(this, arguments);
    },
    
    /**
     * Method: clone
     * Create a clone of this vector feature.  Does not set any non-standard
     *     properties.
     *
     * Returns:
     * {<OpenLayers.Feature.Vector>} An exact clone of this vector feature.
     */
    clone: function () {
        return new OpenLayers.Feature.Vector(
            this.geometry ? this.geometry.clone() : null,
            this.attributes,
            this.style);
    },

    /**
     * Method: onScreen
     * Determine whether the feature is within the map viewport.  This method
     *     tests for an intersection between the geometry and the viewport
     *     bounds.  If a more effecient but less precise geometry bounds
     *     intersection is desired, call the method with the boundsOnly
     *     parameter true.
     *
     * Parameters:
     * boundsOnly - {Boolean} Only test whether a feature's bounds intersects
     *     the viewport bounds.  Default is false.  If false, the feature's
     *     geometry must intersect the viewport for onScreen to return true.
     * 
     * Returns:
     * {Boolean} The feature is currently visible on screen (optionally
     *     based on its bounds if boundsOnly is true).
     */
    onScreen:function(boundsOnly) {
        var onScreen = false;
        if(this.layer && this.layer.map) {
            var screenBounds = this.layer.map.getExtent();
            if(boundsOnly) {
                var featureBounds = this.geometry.getBounds();
                onScreen = screenBounds.intersectsBounds(featureBounds);
            } else {
                var screenPoly = screenBounds.toGeometry();
                onScreen = screenPoly.intersects(this.geometry);
            }
        }    
        return onScreen;
    },

    /**
     * Method: getVisibility
     * Determine whether the feature is displayed or not. It may not displayed
     *     because:
     *     - its style display property is set to 'none',
     *     - it doesn't belong to any layer,
     *     - the styleMap creates a symbolizer with display property set to 'none'
     *          for it,
     *     - the layer which it belongs to is not visible.
     * 
     * Returns:
     * {Boolean} The feature is currently displayed.
     */
    getVisibility: function() {
        return !(this.style && this.style.display == 'none' ||
                 !this.layer ||
                 this.layer && this.layer.styleMap &&
                 this.layer.styleMap.createSymbolizer(this, this.renderIntent).display == 'none' ||
                 this.layer && !this.layer.getVisibility());
    },
    
    /**
     * Method: createMarker
     * HACK - we need to decide if all vector features should be able to
     *     create markers
     * 
     * Returns:
     * {<OpenLayers.Marker>} For now just returns null
     */
    createMarker: function() {
        return null;
    },

    /**
     * Method: destroyMarker
     * HACK - we need to decide if all vector features should be able to
     *     delete markers
     * 
     * If user overrides the createMarker() function, s/he should be able
     *   to also specify an alternative function for destroying it
     */
    destroyMarker: function() {
        // pass
    },

    /**
     * Method: createPopup
     * HACK - we need to decide if all vector features should be able to
     *     create popups
     * 
     * Returns:
     * {<OpenLayers.Popup>} For now just returns null
     */
    createPopup: function() {
        return null;
    },

    /**
     * Method: atPoint
     * Determins whether the feature intersects with the specified location.
     * 
     * Parameters: 
     * lonlat - {<OpenLayers.LonLat>} 
     * toleranceLon - {float} Optional tolerance in Geometric Coords
     * toleranceLat - {float} Optional tolerance in Geographic Coords
     * 
     * Returns:
     * {Boolean} Whether or not the feature is at the specified location
     */
    atPoint: function(lonlat, toleranceLon, toleranceLat) {
        var atPoint = false;
        if(this.geometry) {
            atPoint = this.geometry.atPoint(lonlat, toleranceLon, 
                                                    toleranceLat);
        }
        return atPoint;
    },

    /**
     * Method: destroyPopup
     * HACK - we need to decide if all vector features should be able to
     * delete popups
     */
    destroyPopup: function() {
        // pass
    },

    /**
     * Method: move
     * Moves the feature and redraws it at its new location
     *
     * Parameters:
     * state - {OpenLayers.LonLat or OpenLayers.Pixel} the
     *         location to which to move the feature.
     */
    move: function(location) {

        if(!this.layer || !this.geometry.move){
            //do nothing if no layer or immoveable geometry
            return;
        }

        var pixel;
        if (location.CLASS_NAME == "OpenLayers.LonLat") {
            pixel = this.layer.getViewPortPxFromLonLat(location);
        } else {
            pixel = location;
        }
        
        var lastPixel = this.layer.getViewPortPxFromLonLat(this.geometry.getBounds().getCenterLonLat());
        var res = this.layer.map.getResolution();
        this.geometry.move(res * (pixel.x - lastPixel.x),
                           res * (lastPixel.y - pixel.y));
        this.layer.drawFeature(this);
        return lastPixel;
    },
    
    /**
     * Method: toState
     * Sets the new state
     *
     * Parameters:
     * state - {String} 
     */
    toState: function(state) {
        if (state == OpenLayers.State.UPDATE) {
            switch (this.state) {
                case OpenLayers.State.UNKNOWN:
                case OpenLayers.State.DELETE:
                    this.state = state;
                    break;
                case OpenLayers.State.UPDATE:
                case OpenLayers.State.INSERT:
                    break;
            }
        } else if (state == OpenLayers.State.INSERT) {
            switch (this.state) {
                case OpenLayers.State.UNKNOWN:
                    break;
                default:
                    this.state = state;
                    break;
            }
        } else if (state == OpenLayers.State.DELETE) {
            switch (this.state) {
                case OpenLayers.State.INSERT:
                    // the feature should be destroyed
                    break;
                case OpenLayers.State.DELETE:
                    break;
                case OpenLayers.State.UNKNOWN:
                case OpenLayers.State.UPDATE:
                    this.state = state;
                    break;
            }
        } else if (state == OpenLayers.State.UNKNOWN) {
            this.state = state;
        }
    },
    
    CLASS_NAME: "OpenLayers.Feature.Vector"
});


/**
 * Constant: OpenLayers.Feature.Vector.style
 * OpenLayers features can have a number of style attributes. The 'default' 
 *     style will typically be used if no other style is specified. These
 *     styles correspond for the most part, to the styling properties defined
 *     by the SVG standard. 
 *     Information on fill properties: http://www.w3.org/TR/SVG/painting.html#FillProperties
 *     Information on stroke properties: http://www.w3.org/TR/SVG/painting.html#StrokeProperties
 *
 * Symbolizer properties:
 * fill - {Boolean} Set to false if no fill is desired.
 * fillColor - {String} Hex fill color.  Default is "#ee9900".
 * fillOpacity - {Number} Fill opacity (0-1).  Default is 0.4 
 * stroke - {Boolean} Set to false if no stroke is desired.
 * strokeColor - {String} Hex stroke color.  Default is "#ee9900".
 * strokeOpacity - {Number} Stroke opacity (0-1).  Default is 1.
 * strokeWidth - {Number} Pixel stroke width.  Default is 1.
 * strokeLinecap - {String} Stroke cap type.  Default is "round".  [butt | round | square]
 * strokeDashstyle - {String} Stroke dash style.  Default is "solid". [dot | dash | dashdot | longdash | longdashdot | solid]
 * graphic - {Boolean} Set to false if no graphic is desired.
 * pointRadius - {Number} Pixel point radius.  Default is 6.
 * pointerEvents - {String}  Default is "visiblePainted".
 * cursor - {String} Default is "".
 * externalGraphic - {String} Url to an external graphic that will be used for rendering points.
 * graphicWidth - {Number} Pixel width for sizing an external graphic.
 * graphicHeight - {Number} Pixel height for sizing an external graphic.
 * graphicOpacity - {Number} Opacity (0-1) for an external graphic.
 * graphicXOffset - {Number} Pixel offset along the positive x axis for displacing an external graphic.
 * graphicYOffset - {Number} Pixel offset along the positive y axis for displacing an external graphic.
 * rotation - {Number} For point symbolizers, this is the rotation of a graphic in the clockwise direction about its center point (or any point off center as specified by graphicXOffset and graphicYOffset).
 * graphicZIndex - {Number} The integer z-index value to use in rendering.
 * graphicName - {String} Named graphic to use when rendering points.  Supported values include "circle" (default),
 *     "square", "star", "x", "cross", "triangle".
 * graphicTitle - {String} Tooltip for an external graphic. Only supported in Firefox and Internet Explorer.
 * backgroundGraphic - {String} Url to a graphic to be used as the background under an externalGraphic.
 * backgroundGraphicZIndex - {Number} The integer z-index value to use in rendering the background graphic.
 * backgroundXOffset - {Number} The x offset (in pixels) for the background graphic.
 * backgroundYOffset - {Number} The y offset (in pixels) for the background graphic.
 * backgroundHeight - {Number} The height of the background graphic.  If not provided, the graphicHeight will be used.
 * backgroundWidth - {Number} The width of the background width.  If not provided, the graphicWidth will be used.
 * label - {String} The text for an optional label. For browsers that use the canvas renderer, this requires either
 *     fillText or mozDrawText to be available.
 * labelAlign - {String} Label alignment. This specifies the insertion point relative to the text. It is a string
 *     composed of two characters. The first character is for the horizontal alignment, the second for the vertical
 *     alignment. Valid values for horizontal alignment: "l"=left, "c"=center, "r"=right. Valid values for vertical
 *     alignment: "t"=top, "m"=middle, "b"=bottom. Example values: "lt", "cm", "rb". The canvas renderer does not
 *     support vertical alignment, it will always use "b".
 * labelXOffset - {Number} Pixel offset along the positive x axis for displacing the label.
 * labelYOffset - {Number} Pixel offset along the positive y axis for displacing the label.
 * labelSelect - {Boolean} If set to true, labels will be selectable using SelectFeature or similar controls.
 *     Default is false.
 * fontColor - {String} The font color for the label, to be provided like CSS.
 * fontOpacity - {Number} Opacity (0-1) for the label
 * fontFamily - {String} The font family for the label, to be provided like in CSS.
 * fontSize - {String} The font size for the label, to be provided like in CSS.
 * fontWeight - {String} The font weight for the label, to be provided like in CSS.
 * display - {String} Symbolizers will have no effect if display is set to "none".  All other values have no effect.
 */ 
OpenLayers.Feature.Vector.style = {
    'default': {
        fillColor: "#ee9900",
        fillOpacity: 0.4, 
        hoverFillColor: "white",
        hoverFillOpacity: 0.8,
        strokeColor: "#ee9900",
        strokeOpacity: 1,
        strokeWidth: 1,
        strokeLinecap: "round",
        strokeDashstyle: "solid",
        hoverStrokeColor: "red",
        hoverStrokeOpacity: 1,
        hoverStrokeWidth: 0.2,
        pointRadius: 6,
        hoverPointRadius: 1,
        hoverPointUnit: "%",
        pointerEvents: "visiblePainted",
        cursor: "inherit"
    },
    'select': {
        fillColor: "blue",
        fillOpacity: 0.4, 
        hoverFillColor: "white",
        hoverFillOpacity: 0.8,
        strokeColor: "blue",
        strokeOpacity: 1,
        strokeWidth: 2,
        strokeLinecap: "round",
        strokeDashstyle: "solid",
        hoverStrokeColor: "red",
        hoverStrokeOpacity: 1,
        hoverStrokeWidth: 0.2,
        pointRadius: 6,
        hoverPointRadius: 1,
        hoverPointUnit: "%",
        pointerEvents: "visiblePainted",
        cursor: "pointer"
    },
    'temporary': {
        fillColor: "#66cccc",
        fillOpacity: 0.2, 
        hoverFillColor: "white",
        hoverFillOpacity: 0.8,
        strokeColor: "#66cccc",
        strokeOpacity: 1,
        strokeLinecap: "round",
        strokeWidth: 2,
        strokeDashstyle: "solid",
        hoverStrokeColor: "red",
        hoverStrokeOpacity: 1,
        hoverStrokeWidth: 0.2,
        pointRadius: 6,
        hoverPointRadius: 1,
        hoverPointUnit: "%",
        pointerEvents: "visiblePainted",
        cursor: "inherit"
    },
    'delete': {
        display: "none"
    }
};    
/* ======================================================================
    OpenLayers/Handler/Box.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */

/**
 * @requires OpenLayers/Handler.js
 * @requires OpenLayers/Handler/Drag.js
 */

/**
 * Class: OpenLayers.Handler.Box
 * Handler for dragging a rectangle across the map.  Box is displayed 
 * on mouse down, moves on mouse move, and is finished on mouse up.
 *
 * Inherits from:
 *  - <OpenLayers.Handler> 
 */
OpenLayers.Handler.Box = OpenLayers.Class(OpenLayers.Handler, {

    /** 
     * Property: dragHandler 
     * {<OpenLayers.Handler.Drag>} 
     */
    dragHandler: null,

    /**
     * APIProperty: boxDivClassName
     * {String} The CSS class to use for drawing the box. Default is
     *     olHandlerBoxZoomBox
     */
    boxDivClassName: 'olHandlerBoxZoomBox',
    
    /**
     * Property: boxCharacteristics
     * {Object} Caches some box characteristics from css. This is used
     *     by the getBoxCharacteristics method.
     */
    boxCharacteristics: null,

    /**
     * Constructor: OpenLayers.Handler.Box
     *
     * Parameters:
     * control - {<OpenLayers.Control>} 
     * callbacks - {Object} An object containing a single function to be
     *                          called when the drag operation is finished.
     *                          The callback should expect to recieve a single
     *                          argument, the point geometry.
     * options - {Object} 
     */
    initialize: function(control, callbacks, options) {
        OpenLayers.Handler.prototype.initialize.apply(this, arguments);
        var callbacks = {
            "down": this.startBox, 
            "move": this.moveBox, 
            "out":  this.removeBox,
            "up":   this.endBox
        };
        this.dragHandler = new OpenLayers.Handler.Drag(
                                this, callbacks, {keyMask: this.keyMask});
    },

    /**
     * Method: setMap
     */
    setMap: function (map) {
        OpenLayers.Handler.prototype.setMap.apply(this, arguments);
        if (this.dragHandler) {
            this.dragHandler.setMap(map);
        }
    },

    /**
    * Method: startBox
    *
    * Parameters:
    * evt - {Event} 
    */
    startBox: function (xy) {
        this.zoomBox = OpenLayers.Util.createDiv('zoomBox',
                                                 this.dragHandler.start);
        this.zoomBox.className = this.boxDivClassName;                                         
        this.zoomBox.style.zIndex = this.map.Z_INDEX_BASE["Popup"] - 1;
        this.map.viewPortDiv.appendChild(this.zoomBox);

        OpenLayers.Element.addClass(
            this.map.viewPortDiv, "olDrawBox"
        );
    },

    /**
    * Method: moveBox
    */
    moveBox: function (xy) {
        var startX = this.dragHandler.start.x;
        var startY = this.dragHandler.start.y;
        var deltaX = Math.abs(startX - xy.x);
        var deltaY = Math.abs(startY - xy.y);
        this.zoomBox.style.width = Math.max(1, deltaX) + "px";
        this.zoomBox.style.height = Math.max(1, deltaY) + "px";
        this.zoomBox.style.left = xy.x < startX ? xy.x+"px" : startX+"px";
        this.zoomBox.style.top = xy.y < startY ? xy.y+"px" : startY+"px";

        // depending on the box model, modify width and height to take borders
        // of the box into account
        var box = this.getBoxCharacteristics();
        if (box.newBoxModel) {
            if (xy.x > startX) {
                this.zoomBox.style.width =
                    Math.max(1, deltaX - box.xOffset) + "px";
            }
            if (xy.y > startY) {
                this.zoomBox.style.height =
                    Math.max(1, deltaY - box.yOffset) + "px";
            }
        }
    },

    /**
    * Method: endBox
    */
    endBox: function(end) {
        var result;
        if (Math.abs(this.dragHandler.start.x - end.x) > 5 ||    
            Math.abs(this.dragHandler.start.y - end.y) > 5) {   
            var start = this.dragHandler.start;
            var top = Math.min(start.y, end.y);
            var bottom = Math.max(start.y, end.y);
            var left = Math.min(start.x, end.x);
            var right = Math.max(start.x, end.x);
            result = new OpenLayers.Bounds(left, bottom, right, top);
        } else {
            result = this.dragHandler.start.clone(); // i.e. OL.Pixel
        } 
        this.removeBox();

        this.callback("done", [result]);
    },

    /**
     * Method: removeBox
     * Remove the zoombox from the screen and nullify our reference to it.
     */
    removeBox: function() {
        this.map.viewPortDiv.removeChild(this.zoomBox);
        this.zoomBox = null;
        this.boxCharacteristics = null;
        OpenLayers.Element.removeClass(
            this.map.viewPortDiv, "olDrawBox"
        );

    },

    /**
     * Method: activate
     */
    activate: function () {
        if (OpenLayers.Handler.prototype.activate.apply(this, arguments)) {
            this.dragHandler.activate();
            return true;
        } else {
            return false;
        }
    },

    /**
     * Method: deactivate
     */
    deactivate: function () {
        if (OpenLayers.Handler.prototype.deactivate.apply(this, arguments)) {
            this.dragHandler.deactivate();
            return true;
        } else {
            return false;
        }
    },
    
    /**
     * Method: getCharacteristics
     * Determines offset and box model for a box.
     * 
     * Returns:
     * {Object} a hash with the following properties:
     *     - xOffset - Corner offset in x-direction
     *     - yOffset - Corner offset in y-direction
     *     - newBoxModel - true for all browsers except IE in quirks mode
     */
    getBoxCharacteristics: function() {
        if (!this.boxCharacteristics) {
            var xOffset = parseInt(OpenLayers.Element.getStyle(this.zoomBox,
                "border-left-width")) + parseInt(OpenLayers.Element.getStyle(
                this.zoomBox, "border-right-width")) + 1;
            var yOffset = parseInt(OpenLayers.Element.getStyle(this.zoomBox,
                "border-top-width")) + parseInt(OpenLayers.Element.getStyle(
                this.zoomBox, "border-bottom-width")) + 1;
            // all browsers use the new box model, except IE in quirks mode
            var newBoxModel = OpenLayers.Util.getBrowserName() == "msie" ?
                document.compatMode != "BackCompat" : true;
            this.boxCharacteristics = {
                xOffset: xOffset,
                yOffset: yOffset,
                newBoxModel: newBoxModel
            };
        }
        return this.boxCharacteristics;
    },

    CLASS_NAME: "OpenLayers.Handler.Box"
});
/* ======================================================================
    OpenLayers/Layer/EventPane.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */


/**
 * @requires OpenLayers/Layer.js
 * @requires OpenLayers/Util.js
 */

/**
 * Class: OpenLayers.Layer.EventPane
 * Base class for 3rd party layers.  Create a new event pane layer with the
 * <OpenLayers.Layer.EventPane> constructor.
 * 
 * Inherits from:
 *  - <OpenLayers.Layer>
 */
OpenLayers.Layer.EventPane = OpenLayers.Class(OpenLayers.Layer, {
    
    /**
     * APIProperty: smoothDragPan
     * {Boolean} smoothDragPan determines whether non-public/internal API
     *     methods are used for better performance while dragging EventPane 
     *     layers. When not in sphericalMercator mode, the smoother dragging 
     *     doesn't actually move north/south directly with the number of 
     *     pixels moved, resulting in a slight offset when you drag your mouse 
     *     north south with this option on. If this visual disparity bothers 
     *     you, you should turn this option off, or use spherical mercator. 
     *     Default is on.
     */
    smoothDragPan: true,

    /**
     * Property: isBaseLayer
     * {Boolean} EventPaned layers are always base layers, by necessity.
     */ 
    isBaseLayer: true,

    /**
     * APIProperty: isFixed
     * {Boolean} EventPaned layers are fixed by default.
     */ 
    isFixed: true,

    /**
     * Property: pane
     * {DOMElement} A reference to the element that controls the events.
     */
    pane: null,


    /**
     * Property: mapObject
     * {Object} This is the object which will be used to load the 3rd party library
     * in the case of the google layer, this will be of type GMap, 
     * in the case of the ve layer, this will be of type VEMap
     */ 
    mapObject: null,


    /**
     * Constructor: OpenLayers.Layer.EventPane
     * Create a new event pane layer
     *
     * Parameters:
     * name - {String}
     * options - {Object} Hashtable of extra options to tag onto the layer
     */
    initialize: function(name, options) {
        OpenLayers.Layer.prototype.initialize.apply(this, arguments);
        if (this.pane == null) {
            this.pane = OpenLayers.Util.createDiv(this.div.id + "_EventPane");
        }
    },
    
    /**
     * APIMethod: destroy
     * Deconstruct this layer.
     */
    destroy: function() {
        this.mapObject = null;
        this.pane = null;
        OpenLayers.Layer.prototype.destroy.apply(this, arguments); 
    },

    
    /**
     * Method: setMap
     * Set the map property for the layer. This is done through an accessor
     * so that subclasses can override this and take special action once 
     * they have their map variable set. 
     *
     * Parameters:
     * map - {<OpenLayers.Map>}
     */
    setMap: function(map) {
        OpenLayers.Layer.prototype.setMap.apply(this, arguments);
        
        this.pane.style.zIndex = parseInt(this.div.style.zIndex) + 1;
        this.pane.style.display = this.div.style.display;
        this.pane.style.width="100%";
        this.pane.style.height="100%";
        if (OpenLayers.Util.getBrowserName() == "msie") {
            this.pane.style.background = 
                "url(" + OpenLayers.Util.getImagesLocation() + "blank.gif)";
        }

        if (this.isFixed) {
            this.map.viewPortDiv.appendChild(this.pane);
        } else {
            this.map.layerContainerDiv.appendChild(this.pane);
        }

        // once our layer has been added to the map, we can load it
        this.loadMapObject();
    
        // if map didn't load, display warning
        if (this.mapObject == null) {
            this.loadWarningMessage();
        }
    },

    /**
     * APIMethod: removeMap
     * On being removed from the map, we'll like to remove the invisible 'pane'
     *     div that we added to it on creation. 
     * 
     * Parameters:
     * map - {<OpenLayers.Map>}
     */
    removeMap: function(map) {
        if (this.pane && this.pane.parentNode) {
            this.pane.parentNode.removeChild(this.pane);
        }
        OpenLayers.Layer.prototype.removeMap.apply(this, arguments);
    },
  
    /**
     * Method: loadWarningMessage
     * If we can't load the map lib, then display an error message to the 
     *     user and tell them where to go for help.
     * 
     *     This function sets up the layout for the warning message. Each 3rd
     *     party layer must implement its own getWarningHTML() function to 
     *     provide the actual warning message.
     */
    loadWarningMessage:function() {

        this.div.style.backgroundColor = "darkblue";

        var viewSize = this.map.getSize();
        
        var msgW = Math.min(viewSize.w, 300);
        var msgH = Math.min(viewSize.h, 200);
        var size = new OpenLayers.Size(msgW, msgH);

        var centerPx = new OpenLayers.Pixel(viewSize.w/2, viewSize.h/2);

        var topLeft = centerPx.add(-size.w/2, -size.h/2);            

        var div = OpenLayers.Util.createDiv(this.name + "_warning", 
                                            topLeft, 
                                            size,
                                            null,
                                            null,
                                            null,
                                            "auto");

        div.style.padding = "7px";
        div.style.backgroundColor = "yellow";

        div.innerHTML = this.getWarningHTML();
        this.div.appendChild(div);
    },
  
    /** 
     * Method: getWarningHTML
     * To be implemented by subclasses.
     * 
     * Returns:
     * {String} String with information on why layer is broken, how to get
     *          it working.
     */
    getWarningHTML:function() {
        //should be implemented by subclasses
        return "";
    },
  
    /**
     * Method: display
     * Set the display on the pane
     *
     * Parameters:
     * display - {Boolean}
     */
    display: function(display) {
        OpenLayers.Layer.prototype.display.apply(this, arguments);
        this.pane.style.display = this.div.style.display;
    },
  
    /**
     * Method: setZIndex
     * Set the z-index order for the pane.
     * 
     * Parameters:
     * zIndex - {int}
     */
    setZIndex: function (zIndex) {
        OpenLayers.Layer.prototype.setZIndex.apply(this, arguments);
        this.pane.style.zIndex = parseInt(this.div.style.zIndex) + 1;
    },

    /**
     * Method: moveTo
     * Handle calls to move the layer.
     * 
     * Parameters:
     * bounds - {<OpenLayers.Bounds>}
     * zoomChanged - {Boolean}
     * dragging - {Boolean}
     */
    moveTo:function(bounds, zoomChanged, dragging) {
        OpenLayers.Layer.prototype.moveTo.apply(this, arguments);

        if (this.mapObject != null) {

            var newCenter = this.map.getCenter();
            var newZoom = this.map.getZoom();

            if (newCenter != null) {

                var moOldCenter = this.getMapObjectCenter();
                var oldCenter = this.getOLLonLatFromMapObjectLonLat(moOldCenter);

                var moOldZoom = this.getMapObjectZoom();
                var oldZoom= this.getOLZoomFromMapObjectZoom(moOldZoom);

                if ( !(newCenter.equals(oldCenter)) || 
                     !(newZoom == oldZoom) ) {

                    if (dragging && this.dragPanMapObject && 
                        this.smoothDragPan) {
                        var oldPx = this.map.getViewPortPxFromLonLat(oldCenter);
                        var newPx = this.map.getViewPortPxFromLonLat(newCenter);
                        this.dragPanMapObject(newPx.x-oldPx.x, oldPx.y-newPx.y);
                    } else {
                        var center = this.getMapObjectLonLatFromOLLonLat(newCenter);
                        var zoom = this.getMapObjectZoomFromOLZoom(newZoom);
                        this.setMapObjectCenter(center, zoom, dragging);
                    }
                }
            }
        }
    },


  /********************************************************/
  /*                                                      */
  /*                 Baselayer Functions                  */
  /*                                                      */
  /********************************************************/

    /**
     * Method: getLonLatFromViewPortPx
     * Get a map location from a pixel location
     * 
     * Parameters:
     * viewPortPx - {<OpenLayers.Pixel>}
     *
     * Returns:
     *  {<OpenLayers.LonLat>} An OpenLayers.LonLat which is the passed-in view
     *  port OpenLayers.Pixel, translated into lon/lat by map lib
     *  If the map lib is not loaded or not centered, returns null
     */
    getLonLatFromViewPortPx: function (viewPortPx) {
        var lonlat = null;
        if ( (this.mapObject != null) && 
             (this.getMapObjectCenter() != null) ) {
            var moPixel = this.getMapObjectPixelFromOLPixel(viewPortPx);
            var moLonLat = this.getMapObjectLonLatFromMapObjectPixel(moPixel);
            lonlat = this.getOLLonLatFromMapObjectLonLat(moLonLat);
        }
        return lonlat;
    },

 
    /**
     * Method: getViewPortPxFromLonLat
     * Get a pixel location from a map location
     *
     * Parameters:
     * lonlat - {<OpenLayers.LonLat>}
     *
     * Returns:
     * {<OpenLayers.Pixel>} An OpenLayers.Pixel which is the passed-in
     * OpenLayers.LonLat, translated into view port pixels by map lib
     * If map lib is not loaded or not centered, returns null
     */
    getViewPortPxFromLonLat: function (lonlat) {
        var viewPortPx = null;
        if ( (this.mapObject != null) && 
             (this.getMapObjectCenter() != null) ) {

            var moLonLat = this.getMapObjectLonLatFromOLLonLat(lonlat);
            var moPixel = this.getMapObjectPixelFromMapObjectLonLat(moLonLat);
        
            viewPortPx = this.getOLPixelFromMapObjectPixel(moPixel);
        }
        return viewPortPx;
    },

  /********************************************************/
  /*                                                      */
  /*               Translation Functions                  */
  /*                                                      */
  /*   The following functions translate Map Object and   */
  /*            OL formats for Pixel, LonLat              */
  /*                                                      */
  /********************************************************/

  //
  // TRANSLATION: MapObject LatLng <-> OpenLayers.LonLat
  //

    /**
     * Method: getOLLonLatFromMapObjectLonLat
     * Get an OL style map location from a 3rd party style map location
     *
     * Parameters
     * moLonLat - {Object}
     * 
     * Returns:
     * {<OpenLayers.LonLat>} An OpenLayers.LonLat, translated from the passed in 
     *          MapObject LonLat
     *          Returns null if null value is passed in
     */
    getOLLonLatFromMapObjectLonLat: function(moLonLat) {
        var olLonLat = null;
        if (moLonLat != null) {
            var lon = this.getLongitudeFromMapObjectLonLat(moLonLat);
            var lat = this.getLatitudeFromMapObjectLonLat(moLonLat);
            olLonLat = new OpenLayers.LonLat(lon, lat);
        }
        return olLonLat;
    },

    /**
     * Method: getMapObjectLonLatFromOLLonLat
     * Get a 3rd party map location from an OL map location.
     *
     * Parameters:
     * olLonLat - {<OpenLayers.LonLat>}
     * 
     * Returns:
     * {Object} A MapObject LonLat, translated from the passed in 
     *          OpenLayers.LonLat
     *          Returns null if null value is passed in
     */
    getMapObjectLonLatFromOLLonLat: function(olLonLat) {
        var moLatLng = null;
        if (olLonLat != null) {
            moLatLng = this.getMapObjectLonLatFromLonLat(olLonLat.lon,
                                                         olLonLat.lat);
        }
        return moLatLng;
    },


  //
  // TRANSLATION: MapObject Pixel <-> OpenLayers.Pixel
  //

    /**
     * Method: getOLPixelFromMapObjectPixel
     * Get an OL pixel location from a 3rd party pixel location.
     *
     * Parameters:
     * moPixel - {Object}
     * 
     * Returns:
     * {<OpenLayers.Pixel>} An OpenLayers.Pixel, translated from the passed in 
     *          MapObject Pixel
     *          Returns null if null value is passed in
     */
    getOLPixelFromMapObjectPixel: function(moPixel) {
        var olPixel = null;
        if (moPixel != null) {
            var x = this.getXFromMapObjectPixel(moPixel);
            var y = this.getYFromMapObjectPixel(moPixel);
            olPixel = new OpenLayers.Pixel(x, y);
        }
        return olPixel;
    },

    /**
     * Method: getMapObjectPixelFromOLPixel
     * Get a 3rd party pixel location from an OL pixel location
     *
     * Parameters:
     * olPixel - {<OpenLayers.Pixel>}
     * 
     * Returns:
     * {Object} A MapObject Pixel, translated from the passed in 
     *          OpenLayers.Pixel
     *          Returns null if null value is passed in
     */
    getMapObjectPixelFromOLPixel: function(olPixel) {
        var moPixel = null;
        if (olPixel != null) {
            moPixel = this.getMapObjectPixelFromXY(olPixel.x, olPixel.y);
        }
        return moPixel;
    },

    CLASS_NAME: "OpenLayers.Layer.EventPane"
});
/* ======================================================================
    OpenLayers/Layer/FixedZoomLevels.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */

/**
 * @requires OpenLayers/Layer.js
 */

/**
 * Class: OpenLayers.Layer.FixedZoomLevels
 *   Some Layers will already have established zoom levels (like google 
 *    or ve). Instead of trying to determine them and populate a resolutions[]
 *    Array with those values, we will hijack the resolution functionality
 *    here.
 * 
 *   When you subclass FixedZoomLevels: 
 * 
 *   The initResolutions() call gets nullified, meaning no resolutions[] array 
 *    is set up. Which would be a big problem getResolution() in Layer, since 
 *    it merely takes map.zoom and indexes into resolutions[]... but....
 * 
 *   The getResolution() call is also overridden. Instead of using the 
 *    resolutions[] array, we simply calculate the current resolution based
 *    on the current extent and the current map size. But how will we be able
 *    to calculate the current extent without knowing the resolution...?
 *  
 *   The getExtent() function is also overridden. Instead of calculating extent
 *    based on the center point and the current resolution, we instead 
 *    calculate the extent by getting the lonlats at the top-left and 
 *    bottom-right by using the getLonLatFromViewPortPx() translation function,
 *    taken from the pixel locations (0,0) and the size of the map. But how 
 *    will we be able to do lonlat-px translation without resolution....?
 * 
 *   The getZoomForResolution() method is overridden. Instead of indexing into
 *    the resolutions[] array, we call OpenLayers.Layer.getExent(), passing in
 *    the desired resolution. With this extent, we then call getZoomForExtent() 
 * 
 * 
 *   Whenever you implement a layer using OpenLayers.Layer.FixedZoomLevels, 
 *    it is your responsibility to provide the following three functions:
 * 
 *   - getLonLatFromViewPortPx
 *   - getViewPortPxFromLonLat
 *   - getZoomForExtent
 * 
 *  ...those three functions should generally be provided by any reasonable 
 *  API that you might be working from.
 *
 */
OpenLayers.Layer.FixedZoomLevels = OpenLayers.Class({
      
  /********************************************************/
  /*                                                      */
  /*                 Baselayer Functions                  */
  /*                                                      */
  /*    The following functions must all be implemented   */
  /*                  by all base layers                  */
  /*                                                      */
  /********************************************************/
    
    /**
     * Constructor: OpenLayers.Layer.FixedZoomLevels
     * Create a new fixed zoom levels layer.
     */
    initialize: function() {
        //this class is only just to add the following functions... 
        // nothing to actually do here... but it is probably a good
        // idea to have layers that use these functions call this 
        // inititalize() anyways, in case at some point we decide we 
        // do want to put some functionality or state in here. 
    },
    
    /**
     * Method: initResolutions
     * Populate the resolutions array
     */
    initResolutions: function() {

        var props = new Array('minZoomLevel', 'maxZoomLevel', 'numZoomLevels');
          
        for(var i=0, len=props.length; i<len; i++) {
            var property = props[i];
            this[property] = (this.options[property] != null)  
                                     ? this.options[property] 
                                     : this.map[property];
        }

        if ( (this.minZoomLevel == null) ||
             (this.minZoomLevel < this.MIN_ZOOM_LEVEL) ){
            this.minZoomLevel = this.MIN_ZOOM_LEVEL;
        }        

        //
        // At this point, we know what the minimum desired zoom level is, and
        //  we must calculate the total number of zoom levels. 
        //  
        //  Because we allow for the setting of either the 'numZoomLevels'
        //   or the 'maxZoomLevel' properties... on either the layer or the  
        //   map, we have to define some rules to see which we take into
        //   account first in this calculation. 
        //
        // The following is the precedence list for these properties:
        // 
        // (1) numZoomLevels set on layer
        // (2) maxZoomLevel set on layer
        // (3) numZoomLevels set on map
        // (4) maxZoomLevel set on map*
        // (5) none of the above*
        //
        // *Note that options (4) and (5) are only possible if the user 
        //  _explicitly_ sets the 'numZoomLevels' property on the map to 
        //  null, since it is set by default to 16. 
        //

        //
        // Note to future: In 3.0, I think we should remove the default 
        // value of 16 for map.numZoomLevels. Rather, I think that value 
        // should be set as a default on the Layer.WMS class. If someone
        // creates a 3rd party layer and does not specify any 'minZoomLevel', 
        // 'maxZoomLevel', or 'numZoomLevels', and has not explicitly 
        // specified any of those on the map object either.. then I think
        // it is fair to say that s/he wants all the zoom levels available.
        // 
        // By making map.numZoomLevels *null* by default, that will be the 
        // case. As it is, I don't feel comfortable changing that right now
        // as it would be a glaring API change and actually would probably
        // break many peoples' codes. 
        //

        //the number of zoom levels we'd like to have.
        var desiredZoomLevels;

        //this is the maximum number of zoom levels the layer will allow, 
        // given the specified starting minimum zoom level.
        var limitZoomLevels = this.MAX_ZOOM_LEVEL - this.minZoomLevel + 1;

        if ( ((this.options.numZoomLevels == null) && 
              (this.options.maxZoomLevel != null)) // (2)
              ||
             ((this.numZoomLevels == null) &&
              (this.maxZoomLevel != null)) // (4)
           ) {
            //calculate based on specified maxZoomLevel (on layer or map)
            desiredZoomLevels = this.maxZoomLevel - this.minZoomLevel + 1;
        } else {
            //calculate based on specified numZoomLevels (on layer or map)
            // this covers cases (1) and (3)
            desiredZoomLevels = this.numZoomLevels;
        }

        if (desiredZoomLevels != null) {
            //Now that we know what we would *like* the number of zoom levels
            // to be, based on layer or map options, we have to make sure that
            // it does not conflict with the actual limit, as specified by 
            // the constants on the layer itself (and calculated into the
            // 'limitZoomLevels' variable). 
            this.numZoomLevels = Math.min(desiredZoomLevels, limitZoomLevels);
        } else {
            // case (5) -- neither 'numZoomLevels' not 'maxZoomLevel' was 
            // set on either the layer or the map. So we just use the 
            // maximum limit as calculated by the layer's constants.
            this.numZoomLevels = limitZoomLevels;
        }

        //now that the 'numZoomLevels' is appropriately, safely set, 
        // we go back and re-calculate the 'maxZoomLevel'.
        this.maxZoomLevel = this.minZoomLevel + this.numZoomLevels - 1;

        if (this.RESOLUTIONS != null) {
            var resolutionsIndex = 0;
            this.resolutions = [];
            for(var i= this.minZoomLevel; i <= this.maxZoomLevel; i++) {
                this.resolutions[resolutionsIndex++] = this.RESOLUTIONS[i];            
            }
            this.maxResolution = this.resolutions[0];
            this.minResolution = this.resolutions[this.resolutions.length - 1];
        }       
    },
    
    /**
     * APIMethod: getResolution
     * Get the current map resolution
     * 
     * Returns:
     * {Float} Map units per Pixel
     */
    getResolution: function() {

        if (this.resolutions != null) {
            return OpenLayers.Layer.prototype.getResolution.apply(this, arguments);
        } else {
            var resolution = null;
            
            var viewSize = this.map.getSize();
            var extent = this.getExtent();
            
            if ((viewSize != null) && (extent != null)) {
                resolution = Math.max( extent.getWidth()  / viewSize.w,
                                       extent.getHeight() / viewSize.h );
            }
            return resolution;
        }
     },

    /**
     * APIMethod: getExtent
     * Calculates using px-> lonlat translation functions on tl and br 
     *     corners of viewport
     * 
     * Returns:
     * {<OpenLayers.Bounds>} A Bounds object which represents the lon/lat 
     *                       bounds of the current viewPort.
     */
    getExtent: function () {
        var extent = null;
        
        
        var size = this.map.getSize();
        
        var tlPx = new OpenLayers.Pixel(0,0);
        var tlLL = this.getLonLatFromViewPortPx(tlPx);

        var brPx = new OpenLayers.Pixel(size.w, size.h);
        var brLL = this.getLonLatFromViewPortPx(brPx);
        
        if ((tlLL != null) && (brLL != null)) {
            extent = new OpenLayers.Bounds(tlLL.lon, 
                                       brLL.lat, 
                                       brLL.lon, 
                                       tlLL.lat);
        }

        return extent;
    },

    /**
     * Method: getZoomForResolution
     * Get the zoom level for a given resolution
     *
     * Parameters:
     * resolution - {Float}
     *
     * Returns:
     * {Integer} A suitable zoom level for the specified resolution.
     *           If no baselayer is set, returns null.
     */
    getZoomForResolution: function(resolution) {
      
        if (this.resolutions != null) {
            return OpenLayers.Layer.prototype.getZoomForResolution.apply(this, arguments);
        } else {
            var extent = OpenLayers.Layer.prototype.getExtent.apply(this, []);
            return this.getZoomForExtent(extent);
        }
    },



    
    /********************************************************/
    /*                                                      */
    /*             Translation Functions                    */
    /*                                                      */
    /*    The following functions translate GMaps and OL    */ 
    /*     formats for Pixel, LonLat, Bounds, and Zoom      */
    /*                                                      */
    /********************************************************/
    
    
    //
    // TRANSLATION: MapObject Zoom <-> OpenLayers Zoom
    //
  
    /**
     * Method: getOLZoomFromMapObjectZoom
     * Get the OL zoom index from the map object zoom level
     *
     * Parameters:
     * moZoom - {Integer}
     * 
     * Returns:
     * {Integer} An OpenLayers Zoom level, translated from the passed in zoom
     *           Returns null if null value is passed in
     */
    getOLZoomFromMapObjectZoom: function(moZoom) {
        var zoom = null;
        if (moZoom != null) {
            zoom = moZoom - this.minZoomLevel;
        }
        return zoom;
    },
    
    /**
     * Method: getMapObjectZoomFromOLZoom
     * Get the map object zoom level from the OL zoom level
     *
     * Parameters:
     * olZoom - {Integer}
     * 
     * Returns:
     * {Integer} A MapObject level, translated from the passed in olZoom
     *           Returns null if null value is passed in
     */
    getMapObjectZoomFromOLZoom: function(olZoom) {
        var zoom = null; 
        if (olZoom != null) {
            zoom = olZoom + this.minZoomLevel;
        }
        return zoom;
    },

    CLASS_NAME: "OpenLayers.Layer.FixedZoomLevels"
});

/* ======================================================================
    OpenLayers/Layer/HTTPRequest.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */


/**
 * @requires OpenLayers/Layer.js
 */

/**
 * Class: OpenLayers.Layer.HTTPRequest
 * 
 * Inherits from: 
 *  - <OpenLayers.Layer>
 */
OpenLayers.Layer.HTTPRequest = OpenLayers.Class(OpenLayers.Layer, {

    /** 
     * Constant: URL_HASH_FACTOR
     * {Float} Used to hash URL param strings for multi-WMS server selection.
     *         Set to the Golden Ratio per Knuth's recommendation.
     */
    URL_HASH_FACTOR: (Math.sqrt(5) - 1) / 2,

    /** 
     * Property: url
     * {Array(String) or String} This is either an array of url strings or 
     *                           a single url string. 
     */
    url: null,

    /** 
     * Property: params
     * {Object} Hashtable of key/value parameters
     */
    params: null,
    
    /** 
     * APIProperty: reproject
     * *Deprecated*. See http://trac.openlayers.org/wiki/SpatialMercator
     * for information on the replacement for this functionality. 
     * {Boolean} Whether layer should reproject itself based on base layer 
     *           locations. This allows reprojection onto commercial layers. 
     *           Default is false: Most layers can't reproject, but layers 
     *           which can create non-square geographic pixels can, like WMS.
     *           
     */
    reproject: false,

    /**
     * Constructor: OpenLayers.Layer.HTTPRequest
     * 
     * Parameters:
     * name - {String}
     * url - {Array(String) or String}
     * params - {Object}
     * options - {Object} Hashtable of extra options to tag onto the layer
     */
    initialize: function(name, url, params, options) {
        var newArguments = arguments;
        newArguments = [name, options];
        OpenLayers.Layer.prototype.initialize.apply(this, newArguments);
        this.url = url;
        this.params = OpenLayers.Util.extend( {}, params);
    },

    /**
     * APIMethod: destroy
     */
    destroy: function() {
        this.url = null;
        this.params = null;
        OpenLayers.Layer.prototype.destroy.apply(this, arguments); 
    },
    
    /**
     * APIMethod: clone
     * 
     * Parameters:
     * obj - {Object}
     * 
     * Returns:
     * {<OpenLayers.Layer.HTTPRequest>} An exact clone of this 
     *                                  <OpenLayers.Layer.HTTPRequest>
     */
    clone: function (obj) {
        
        if (obj == null) {
            obj = new OpenLayers.Layer.HTTPRequest(this.name,
                                                   this.url,
                                                   this.params,
                                                   this.getOptions());
        }
        
        //get all additions from superclasses
        obj = OpenLayers.Layer.prototype.clone.apply(this, [obj]);

        // copy/set any non-init, non-simple values here
        
        return obj;
    },

    /** 
     * APIMethod: setUrl
     * 
     * Parameters:
     * newUrl - {String}
     */
    setUrl: function(newUrl) {
        this.url = newUrl;
    },

    /**
     * APIMethod: mergeNewParams
     * 
     * Parameters:
     * newParams - {Object}
     *
     * Returns:
     * redrawn: {Boolean} whether the layer was actually redrawn.
     */
    mergeNewParams:function(newParams) {
        this.params = OpenLayers.Util.extend(this.params, newParams);
        var ret = this.redraw();
        if(this.map != null) {
            this.map.events.triggerEvent("changelayer", {
                layer: this,
                property: "params"
            });
        }
        return ret;
    },

    /**
     * APIMethod: redraw
     * Redraws the layer.  Returns true if the layer was redrawn, false if not.
     *
     * Parameters:
     * force - {Boolean} Force redraw by adding random parameter.
     *
     * Returns:
     * {Boolean} The layer was redrawn.
     */
    redraw: function(force) { 
        if (force) {
            return this.mergeNewParams({"_olSalt": Math.random()});
        } else {
            return OpenLayers.Layer.prototype.redraw.apply(this, []);
        }
    },
    
    /**
     * Method: selectUrl
     * selectUrl() implements the standard floating-point multiplicative
     *     hash function described by Knuth, and hashes the contents of the 
     *     given param string into a float between 0 and 1. This float is then
     *     scaled to the size of the provided urls array, and used to select
     *     a URL.
     *
     * Parameters:
     * paramString - {String}
     * urls - {Array(String)}
     * 
     * Returns:
     * {String} An entry from the urls array, deterministically selected based
     *          on the paramString.
     */
    selectUrl: function(paramString, urls) {
        var product = 1;
        for (var i=0, len=paramString.length; i<len; i++) { 
            product *= paramString.charCodeAt(i) * this.URL_HASH_FACTOR; 
            product -= Math.floor(product); 
        }
        return urls[Math.floor(product * urls.length)];
    },

    /** 
     * Method: getFullRequestString
     * Combine url with layer's params and these newParams. 
     *   
     *    does checking on the serverPath variable, allowing for cases when it 
     *     is supplied with trailing ? or &, as well as cases where not. 
     *
     *    return in formatted string like this:
     *        "server?key1=value1&key2=value2&key3=value3"
     * 
     * WARNING: The altUrl parameter is deprecated and will be removed in 3.0.
     *
     * Parameters:
     * newParams - {Object}
     * altUrl - {String} Use this as the url instead of the layer's url
     *   
     * Returns: 
     * {String}
     */
    getFullRequestString:function(newParams, altUrl) {

        // if not altUrl passed in, use layer's url
        var url = altUrl || this.url;
        
        // create a new params hashtable with all the layer params and the 
        // new params together. then convert to string
        var allParams = OpenLayers.Util.extend({}, this.params);
        allParams = OpenLayers.Util.extend(allParams, newParams);
        var paramsString = OpenLayers.Util.getParameterString(allParams);
        
        // if url is not a string, it should be an array of strings, 
        // in which case we will deterministically select one of them in 
        // order to evenly distribute requests to different urls.
        //
        if (url instanceof Array) {
            url = this.selectUrl(paramsString, url);
        }   
 
        // ignore parameters that are already in the url search string
        var urlParams = 
            OpenLayers.Util.upperCaseObject(OpenLayers.Util.getParameters(url));
        for(var key in allParams) {
            if(key.toUpperCase() in urlParams) {
                delete allParams[key];
            }
        }
        paramsString = OpenLayers.Util.getParameterString(allParams);
        
        return OpenLayers.Util.urlAppend(url, paramsString);
    },

    CLASS_NAME: "OpenLayers.Layer.HTTPRequest"
});
/* ======================================================================
    OpenLayers/Layer/SphericalMercator.js
   ====================================================================== */

/**
 * @requires OpenLayers/Layer.js
 * @requires OpenLayers/Projection.js
 */

/**
 * Class: OpenLayers.Layer.SphericalMercator
 * A mixin for layers that wraps up the pieces neccesary to have a coordinate
 *     conversion for working with commercial APIs which use a spherical
 *     mercator projection.  Using this layer as a base layer, additional
 *     layers can be used as overlays if they are in the same projection.
 *
 * A layer is given properties of this object by setting the sphericalMercator
 *     property to true.
 *
 * More projection information:
 *  - http://spatialreference.org/ref/user/google-projection/
 *
 * Proj4 Text:
 *     +proj=merc +a=6378137 +b=6378137 +lat_ts=0.0 +lon_0=0.0 +x_0=0.0 +y_0=0
 *     +k=1.0 +units=m +nadgrids=@null +no_defs
 *
 * WKT:
 *     900913=PROJCS["WGS84 / Simple Mercator", GEOGCS["WGS 84",
 *     DATUM["WGS_1984", SPHEROID["WGS_1984", 6378137.0, 298.257223563]], 
 *     PRIMEM["Greenwich", 0.0], UNIT["degree", 0.017453292519943295], 
 *     AXIS["Longitude", EAST], AXIS["Latitude", NORTH]],
 *     PROJECTION["Mercator_1SP_Google"], 
 *     PARAMETER["latitude_of_origin", 0.0], PARAMETER["central_meridian", 0.0], 
 *     PARAMETER["scale_factor", 1.0], PARAMETER["false_easting", 0.0], 
 *     PARAMETER["false_northing", 0.0], UNIT["m", 1.0], AXIS["x", EAST],
 *     AXIS["y", NORTH], AUTHORITY["EPSG","900913"]]
 */
OpenLayers.Layer.SphericalMercator = {

    /**
     * Method: getExtent
     * Get the map's extent.
     *
     * Returns:
     * {<OpenLayers.Bounds>} The map extent.
     */
    getExtent: function() {
        var extent = null;
        if (this.sphericalMercator) {
            extent = this.map.calculateBounds();
        } else {
            extent = OpenLayers.Layer.FixedZoomLevels.prototype.getExtent.apply(this);
        }
        return extent;
    },

    /** 
     * Method: initMercatorParameters 
     * Set up the mercator parameters on the layer: resolutions,
     *     projection, units.
     */
    initMercatorParameters: function() {
        // set up properties for Mercator - assume EPSG:900913
        this.RESOLUTIONS = [];
        var maxResolution = 156543.0339;
        for(var zoom=0; zoom<=this.MAX_ZOOM_LEVEL; ++zoom) {
            this.RESOLUTIONS[zoom] = maxResolution / Math.pow(2, zoom);
        }
        this.units = "m";
        this.projection = "EPSG:900913";
    },

    /**
     * APIMethod: forwardMercator
     * Given a lon,lat in EPSG:4326, return a point in Spherical Mercator.
     *
     * Parameters:
     * lon - {float} 
     * lat - {float}
     * 
     * Returns:
     * {<OpenLayers.LonLat>} The coordinates transformed to Mercator.
     */
    forwardMercator: function(lon, lat) {
        var x = lon * 20037508.34 / 180;
        var y = Math.log(Math.tan((90 + lat) * Math.PI / 360)) / (Math.PI / 180);

        y = y * 20037508.34 / 180;
        
        return new OpenLayers.LonLat(x, y);
    },

    /**
     * APIMethod: inverseMercator
     * Given a x,y in Spherical Mercator, return a point in EPSG:4326.
     *
     * Parameters:
     * x - {float} A map x in Spherical Mercator.
     * y - {float} A map y in Spherical Mercator.
     * 
     * Returns:
     * {<OpenLayers.LonLat>} The coordinates transformed to EPSG:4326.
     */
    inverseMercator: function(x, y) {

        var lon = (x / 20037508.34) * 180;
        var lat = (y / 20037508.34) * 180;

        lat = 180/Math.PI * (2 * Math.atan(Math.exp(lat * Math.PI / 180)) - Math.PI / 2);
        
        return new OpenLayers.LonLat(lon, lat);
    },

    /**
     * Method: projectForward 
     * Given an object with x and y properties in EPSG:4326, modify the x,y
     * properties on the object to be the Spherical Mercator projected
     * coordinates.
     *
     * Parameters:
     * point - {Object} An object with x and y properties. 
     * 
     * Returns:
     * {Object} The point, with the x and y properties transformed to spherical
     * mercator.
     */
    projectForward: function(point) {
        var lonlat = OpenLayers.Layer.SphericalMercator.forwardMercator(point.x, point.y);
        point.x = lonlat.lon;
        point.y = lonlat.lat;
        return point;
    },
    
    /**
     * Method: projectInverse
     * Given an object with x and y properties in Spherical Mercator, modify
     * the x,y properties on the object to be the unprojected coordinates.
     *
     * Parameters:
     * point - {Object} An object with x and y properties. 
     * 
     * Returns:
     * {Object} The point, with the x and y properties transformed from
     * spherical mercator to unprojected coordinates..
     */
    projectInverse: function(point) {
        var lonlat = OpenLayers.Layer.SphericalMercator.inverseMercator(point.x, point.y);
        point.x = lonlat.lon;
        point.y = lonlat.lat;
        return point;
    }

};

/**
 * Note: Two transforms declared
 * Transforms from EPSG:4326 to EPSG:900913 and from EPSG:900913 to EPSG:4326
 *     are set by this class.
 */
OpenLayers.Projection.addTransform("EPSG:4326", "EPSG:900913",
    OpenLayers.Layer.SphericalMercator.projectForward);
OpenLayers.Projection.addTransform("EPSG:900913", "EPSG:4326",
    OpenLayers.Layer.SphericalMercator.projectInverse);
/* ======================================================================
    OpenLayers/Control/ZoomBox.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */

/**
 * @requires OpenLayers/Control.js
 * @requires OpenLayers/Handler/Box.js
 */

/**
 * Class: OpenLayers.Control.ZoomBox
 * The ZoomBox control enables zooming directly to a given extent, by drawing 
 * a box on the map. The box is drawn by holding down shift, whilst dragging 
 * the mouse.
 *
 * Inherits from:
 *  - <OpenLayers.Control>
 */
OpenLayers.Control.ZoomBox = OpenLayers.Class(OpenLayers.Control, {
    /**
     * Property: type
     * {OpenLayers.Control.TYPE}
     */
    type: OpenLayers.Control.TYPE_TOOL,

    /**
     * Property: out
     * {Boolean} Should the control be used for zooming out?
     */
    out: false,

    /**
     * Property: alwaysZoom
     * {Boolean} Always zoom in/out, when box drawed 
     */
    alwaysZoom: false,

    /**
     * Method: draw
     */    
    draw: function() {
        this.handler = new OpenLayers.Handler.Box( this,
                            {done: this.zoomBox}, {keyMask: this.keyMask} );
    },

    /**
     * Method: zoomBox
     *
     * Parameters:
     * position - {<OpenLayers.Bounds>} or {<OpenLayers.Pixel>}
     */
    zoomBox: function (position) {
        if (position instanceof OpenLayers.Bounds) {
            var bounds;
            if (!this.out) {
                var minXY = this.map.getLonLatFromPixel(
                            new OpenLayers.Pixel(position.left, position.bottom));
                var maxXY = this.map.getLonLatFromPixel(
                            new OpenLayers.Pixel(position.right, position.top));
                bounds = new OpenLayers.Bounds(minXY.lon, minXY.lat,
                                               maxXY.lon, maxXY.lat);
            } else {
                var pixWidth = Math.abs(position.right-position.left);
                var pixHeight = Math.abs(position.top-position.bottom);
                var zoomFactor = Math.min((this.map.size.h / pixHeight),
                    (this.map.size.w / pixWidth));
                var extent = this.map.getExtent();
                var center = this.map.getLonLatFromPixel(
                    position.getCenterPixel());
                var xmin = center.lon - (extent.getWidth()/2)*zoomFactor;
                var xmax = center.lon + (extent.getWidth()/2)*zoomFactor;
                var ymin = center.lat - (extent.getHeight()/2)*zoomFactor;
                var ymax = center.lat + (extent.getHeight()/2)*zoomFactor;
                bounds = new OpenLayers.Bounds(xmin, ymin, xmax, ymax);
            }
            // always zoom in/out 
            var lastZoom = this.map.getZoom(); 
            this.map.zoomToExtent(bounds);
            if (lastZoom == this.map.getZoom() && this.alwaysZoom == true){ 
                this.map.zoomTo(lastZoom + (this.out ? -1 : 1)); 
            }
        } else { // it's a pixel
            if (!this.out) {
                this.map.setCenter(this.map.getLonLatFromPixel(position),
                               this.map.getZoom() + 1);
            } else {
                this.map.setCenter(this.map.getLonLatFromPixel(position),
                               this.map.getZoom() - 1);
            }
        }
    },

    CLASS_NAME: "OpenLayers.Control.ZoomBox"
});
/* ======================================================================
    OpenLayers/Format/WKT.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */

/**
 * @requires OpenLayers/Format.js
 * @requires OpenLayers/Feature/Vector.js
 */

/**
 * Class: OpenLayers.Format.WKT
 * Class for reading and writing Well-Known Text.  Create a new instance
 * with the <OpenLayers.Format.WKT> constructor.
 * 
 * Inherits from:
 *  - <OpenLayers.Format>
 */
OpenLayers.Format.WKT = OpenLayers.Class(OpenLayers.Format, {
    
    /**
     * Constructor: OpenLayers.Format.WKT
     * Create a new parser for WKT
     *
     * Parameters:
     * options - {Object} An optional object whose properties will be set on
     *           this instance
     *
     * Returns:
     * {<OpenLayers.Format.WKT>} A new WKT parser.
     */
    initialize: function(options) {
        this.regExes = {
            'typeStr': /^\s*(\w+)\s*\(\s*(.*)\s*\)\s*$/,
            'spaces': /\s+/,
            'parenComma': /\)\s*,\s*\(/,
            'doubleParenComma': /\)\s*\)\s*,\s*\(\s*\(/,  // can't use {2} here
            'trimParens': /^\s*\(?(.*?)\)?\s*$/
        };
        OpenLayers.Format.prototype.initialize.apply(this, [options]);
    },

    /**
     * Method: read
     * Deserialize a WKT string and return a vector feature or an
     * array of vector features.  Supports WKT for POINT, MULTIPOINT,
     * LINESTRING, MULTILINESTRING, POLYGON, MULTIPOLYGON, and
     * GEOMETRYCOLLECTION.
     *
     * Parameters:
     * wkt - {String} A WKT string
     *
     * Returns:
     * {<OpenLayers.Feature.Vector>|Array} A feature or array of features for
     * GEOMETRYCOLLECTION WKT.
     */
    read: function(wkt) {
        var features, type, str;
        var matches = this.regExes.typeStr.exec(wkt);
        if(matches) {
            type = matches[1].toLowerCase();
            str = matches[2];
            if(this.parse[type]) {
                features = this.parse[type].apply(this, [str]);
            }
            if (this.internalProjection && this.externalProjection) {
                if (features && 
                    features.CLASS_NAME == "OpenLayers.Feature.Vector") {
                    features.geometry.transform(this.externalProjection,
                                                this.internalProjection);
                } else if (features &&
                           type != "geometrycollection" &&
                           typeof features == "object") {
                    for (var i=0, len=features.length; i<len; i++) {
                        var component = features[i];
                        component.geometry.transform(this.externalProjection,
                                                     this.internalProjection);
                    }
                }
            }
        }    
        return features;
    },

    /**
     * Method: write
     * Serialize a feature or array of features into a WKT string.
     *
     * Parameters:
     * features - {<OpenLayers.Feature.Vector>|Array} A feature or array of
     *            features
     *
     * Returns:
     * {String} The WKT string representation of the input geometries
     */
    write: function(features) {
        var collection, geometry, type, data, isCollection;
        if(features.constructor == Array) {
            collection = features;
            isCollection = true;
        } else {
            collection = [features];
            isCollection = false;
        }
        var pieces = [];
        if(isCollection) {
            pieces.push('GEOMETRYCOLLECTION(');
        }
        for(var i=0, len=collection.length; i<len; ++i) {
            if(isCollection && i>0) {
                pieces.push(',');
            }
            geometry = collection[i].geometry;
            type = geometry.CLASS_NAME.split('.')[2].toLowerCase();
            if(!this.extract[type]) {
                return null;
            }
            if (this.internalProjection && this.externalProjection) {
                geometry = geometry.clone();
                geometry.transform(this.internalProjection, 
                                   this.externalProjection);
            }                       
            data = this.extract[type].apply(this, [geometry]);
            pieces.push(type.toUpperCase() + '(' + data + ')');
        }
        if(isCollection) {
            pieces.push(')');
        }
        return pieces.join('');
    },
    
    /**
     * Object with properties corresponding to the geometry types.
     * Property values are functions that do the actual data extraction.
     */
    extract: {
        /**
         * Return a space delimited string of point coordinates.
         * @param {<OpenLayers.Geometry.Point>} point
         * @returns {String} A string of coordinates representing the point
         */
        'point': function(point) {
            return point.x + ' ' + point.y;
        },

        /**
         * Return a comma delimited string of point coordinates from a multipoint.
         * @param {<OpenLayers.Geometry.MultiPoint>} multipoint
         * @returns {String} A string of point coordinate strings representing
         *                  the multipoint
         */
        'multipoint': function(multipoint) {
            var array = [];
            for(var i=0, len=multipoint.components.length; i<len; ++i) {
                array.push(this.extract.point.apply(this, [multipoint.components[i]]));
            }
            return array.join(',');
        },
        
        /**
         * Return a comma delimited string of point coordinates from a line.
         * @param {<OpenLayers.Geometry.LineString>} linestring
         * @returns {String} A string of point coordinate strings representing
         *                  the linestring
         */
        'linestring': function(linestring) {
            var array = [];
            for(var i=0, len=linestring.components.length; i<len; ++i) {
                array.push(this.extract.point.apply(this, [linestring.components[i]]));
            }
            return array.join(',');
        },

        /**
         * Return a comma delimited string of linestring strings from a multilinestring.
         * @param {<OpenLayers.Geometry.MultiLineString>} multilinestring
         * @returns {String} A string of of linestring strings representing
         *                  the multilinestring
         */
        'multilinestring': function(multilinestring) {
            var array = [];
            for(var i=0, len=multilinestring.components.length; i<len; ++i) {
                array.push('(' +
                           this.extract.linestring.apply(this, [multilinestring.components[i]]) +
                           ')');
            }
            return array.join(',');
        },
        
        /**
         * Return a comma delimited string of linear ring arrays from a polygon.
         * @param {<OpenLayers.Geometry.Polygon>} polygon
         * @returns {String} An array of linear ring arrays representing the polygon
         */
        'polygon': function(polygon) {
            var array = [];
            for(var i=0, len=polygon.components.length; i<len; ++i) {
                array.push('(' +
                           this.extract.linestring.apply(this, [polygon.components[i]]) +
                           ')');
            }
            return array.join(',');
        },

        /**
         * Return an array of polygon arrays from a multipolygon.
         * @param {<OpenLayers.Geometry.MultiPolygon>} multipolygon
         * @returns {Array} An array of polygon arrays representing
         *                  the multipolygon
         */
        'multipolygon': function(multipolygon) {
            var array = [];
            for(var i=0, len=multipolygon.components.length; i<len; ++i) {
                array.push('(' +
                           this.extract.polygon.apply(this, [multipolygon.components[i]]) +
                           ')');
            }
            return array.join(',');
        }

    },

    /**
     * Object with properties corresponding to the geometry types.
     * Property values are functions that do the actual parsing.
     */
    parse: {
        /**
         * Return point feature given a point WKT fragment.
         * @param {String} str A WKT fragment representing the point
         * @returns {<OpenLayers.Feature.Vector>} A point feature
         * @private
         */
        'point': function(str) {
            var coords = OpenLayers.String.trim(str).split(this.regExes.spaces);
            return new OpenLayers.Feature.Vector(
                new OpenLayers.Geometry.Point(coords[0], coords[1])
            );
        },

        /**
         * Return a multipoint feature given a multipoint WKT fragment.
         * @param {String} A WKT fragment representing the multipoint
         * @returns {<OpenLayers.Feature.Vector>} A multipoint feature
         * @private
         */
        'multipoint': function(str) {
            var points = OpenLayers.String.trim(str).split(',');
            var components = [];
            for(var i=0, len=points.length; i<len; ++i) {
                components.push(this.parse.point.apply(this, [points[i]]).geometry);
            }
            return new OpenLayers.Feature.Vector(
                new OpenLayers.Geometry.MultiPoint(components)
            );
        },
        
        /**
         * Return a linestring feature given a linestring WKT fragment.
         * @param {String} A WKT fragment representing the linestring
         * @returns {<OpenLayers.Feature.Vector>} A linestring feature
         * @private
         */
        'linestring': function(str) {
            var points = OpenLayers.String.trim(str).split(',');
            var components = [];
            for(var i=0, len=points.length; i<len; ++i) {
                components.push(this.parse.point.apply(this, [points[i]]).geometry);
            }
            return new OpenLayers.Feature.Vector(
                new OpenLayers.Geometry.LineString(components)
            );
        },

        /**
         * Return a multilinestring feature given a multilinestring WKT fragment.
         * @param {String} A WKT fragment representing the multilinestring
         * @returns {<OpenLayers.Feature.Vector>} A multilinestring feature
         * @private
         */
        'multilinestring': function(str) {
            var line;
            var lines = OpenLayers.String.trim(str).split(this.regExes.parenComma);
            var components = [];
            for(var i=0, len=lines.length; i<len; ++i) {
                line = lines[i].replace(this.regExes.trimParens, '$1');
                components.push(this.parse.linestring.apply(this, [line]).geometry);
            }
            return new OpenLayers.Feature.Vector(
                new OpenLayers.Geometry.MultiLineString(components)
            );
        },
        
        /**
         * Return a polygon feature given a polygon WKT fragment.
         * @param {String} A WKT fragment representing the polygon
         * @returns {<OpenLayers.Feature.Vector>} A polygon feature
         * @private
         */
        'polygon': function(str) {
            var ring, linestring, linearring;
            var rings = OpenLayers.String.trim(str).split(this.regExes.parenComma);
            var components = [];
            for(var i=0, len=rings.length; i<len; ++i) {
                ring = rings[i].replace(this.regExes.trimParens, '$1');
                linestring = this.parse.linestring.apply(this, [ring]).geometry;
                linearring = new OpenLayers.Geometry.LinearRing(linestring.components);
                components.push(linearring);
            }
            return new OpenLayers.Feature.Vector(
                new OpenLayers.Geometry.Polygon(components)
            );
        },

        /**
         * Return a multipolygon feature given a multipolygon WKT fragment.
         * @param {String} A WKT fragment representing the multipolygon
         * @returns {<OpenLayers.Feature.Vector>} A multipolygon feature
         * @private
         */
        'multipolygon': function(str) {
            var polygon;
            var polygons = OpenLayers.String.trim(str).split(this.regExes.doubleParenComma);
            var components = [];
            for(var i=0, len=polygons.length; i<len; ++i) {
                polygon = polygons[i].replace(this.regExes.trimParens, '$1');
                components.push(this.parse.polygon.apply(this, [polygon]).geometry);
            }
            return new OpenLayers.Feature.Vector(
                new OpenLayers.Geometry.MultiPolygon(components)
            );
        },

        /**
         * Return an array of features given a geometrycollection WKT fragment.
         * @param {String} A WKT fragment representing the geometrycollection
         * @returns {Array} An array of OpenLayers.Feature.Vector
         * @private
         */
        'geometrycollection': function(str) {
            // separate components of the collection with |
            str = str.replace(/,\s*([A-Za-z])/g, '|$1');
            var wktArray = OpenLayers.String.trim(str).split('|');
            var components = [];
            for(var i=0, len=wktArray.length; i<len; ++i) {
                components.push(OpenLayers.Format.WKT.prototype.read.apply(this,[wktArray[i]]));
            }
            return components;
        }

    },

    CLASS_NAME: "OpenLayers.Format.WKT" 
});     
/* ======================================================================
    OpenLayers/Layer/Grid.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */


/**
 * @requires OpenLayers/Layer/HTTPRequest.js
 * @requires OpenLayers/Console.js
 */

/**
 * Class: OpenLayers.Layer.Grid
 * Base class for layers that use a lattice of tiles.  Create a new grid
 * layer with the <OpenLayers.Layer.Grid> constructor.
 *
 * Inherits from:
 *  - <OpenLayers.Layer.HTTPRequest>
 */
OpenLayers.Layer.Grid = OpenLayers.Class(OpenLayers.Layer.HTTPRequest, {
    
    /**
     * APIProperty: tileSize
     * {<OpenLayers.Size>}
     */
    tileSize: null,
    
    /**
     * Property: grid
     * {Array(Array(<OpenLayers.Tile>))} This is an array of rows, each row is 
     *     an array of tiles.
     */
    grid: null,

    /**
     * APIProperty: singleTile
     * {Boolean} Moves the layer into single-tile mode, meaning that one tile 
     *     will be loaded. The tile's size will be determined by the 'ratio'
     *     property. When the tile is dragged such that it does not cover the 
     *     entire viewport, it is reloaded.
     */
    singleTile: false,

    /** APIProperty: ratio
     *  {Float} Used only when in single-tile mode, this specifies the 
     *          ratio of the size of the single tile to the size of the map.
     */
    ratio: 1.5,

    /**
     * APIProperty: buffer
     * {Integer} Used only when in gridded mode, this specifies the number of 
     *           extra rows and colums of tiles on each side which will
     *           surround the minimum grid tiles to cover the map.
     */
    buffer: 2,

    /**
     * APIProperty: numLoadingTiles
     * {Integer} How many tiles are still loading?
     */
    numLoadingTiles: 0,

    /**
     * Constructor: OpenLayers.Layer.Grid
     * Create a new grid layer
     *
     * Parameters:
     * name - {String}
     * url - {String}
     * params - {Object}
     * options - {Object} Hashtable of extra options to tag onto the layer
     */
    initialize: function(name, url, params, options) {
        OpenLayers.Layer.HTTPRequest.prototype.initialize.apply(this, 
                                                                arguments);
        
        //grid layers will trigger 'tileloaded' when each new tile is 
        // loaded, as a means of progress update to listeners.
        // listeners can access 'numLoadingTiles' if they wish to keep track
        // of the loading progress
        //
        this.events.addEventType("tileloaded");

        this.grid = [];
    },

    /**
     * APIMethod: destroy
     * Deconstruct the layer and clear the grid.
     */
    destroy: function() {
        this.clearGrid();
        this.grid = null;
        this.tileSize = null;
        OpenLayers.Layer.HTTPRequest.prototype.destroy.apply(this, arguments); 
    },

    /**
     * Method: clearGrid
     * Go through and remove all tiles from the grid, calling
     *    destroy() on each of them to kill circular references
     */
    clearGrid:function() {
        if (this.grid) {
            for(var iRow=0, len=this.grid.length; iRow<len; iRow++) {
                var row = this.grid[iRow];
                for(var iCol=0, clen=row.length; iCol<clen; iCol++) {
                    var tile = row[iCol];
                    this.removeTileMonitoringHooks(tile);
                    tile.destroy();
                }
            }
            this.grid = [];
        }
    },

    /**
     * APIMethod: clone
     * Create a clone of this layer
     *
     * Parameters:
     * obj - {Object} Is this ever used?
     * 
     * Returns:
     * {<OpenLayers.Layer.Grid>} An exact clone of this OpenLayers.Layer.Grid
     */
    clone: function (obj) {
        
        if (obj == null) {
            obj = new OpenLayers.Layer.Grid(this.name,
                                            this.url,
                                            this.params,
                                            this.getOptions());
        }

        //get all additions from superclasses
        obj = OpenLayers.Layer.HTTPRequest.prototype.clone.apply(this, [obj]);

        // copy/set any non-init, non-simple values here
        if (this.tileSize != null) {
            obj.tileSize = this.tileSize.clone();
        }
        
        // we do not want to copy reference to grid, so we make a new array
        obj.grid = [];

        return obj;
    },    

    /**
     * Method: moveTo
     * This function is called whenever the map is moved. All the moving
     * of actual 'tiles' is done by the map, but moveTo's role is to accept
     * a bounds and make sure the data that that bounds requires is pre-loaded.
     *
     * Parameters:
     * bounds - {<OpenLayers.Bounds>}
     * zoomChanged - {Boolean}
     * dragging - {Boolean}
     */
    moveTo:function(bounds, zoomChanged, dragging) {
        OpenLayers.Layer.HTTPRequest.prototype.moveTo.apply(this, arguments);
        
        bounds = bounds || this.map.getExtent();

        if (bounds != null) {
             
            // if grid is empty or zoom has changed, we *must* re-tile
            var forceReTile = !this.grid.length || zoomChanged;

            // total bounds of the tiles
            var tilesBounds = this.getTilesBounds();            
      
            if (this.singleTile) {
                
                // We want to redraw whenever even the slightest part of the 
                //  current bounds is not contained by our tile.
                //  (thus, we do not specify partial -- its default is false)
                if ( forceReTile || 
                     (!dragging && !tilesBounds.containsBounds(bounds))) {
                    this.initSingleTile(bounds);
                }
            } else {
             
                // if the bounds have changed such that they are not even 
                //  *partially* contained by our tiles (IE user has 
                //  programmatically panned to the other side of the earth) 
                //  then we want to reTile (thus, partial true).  
                //
                if (forceReTile || !tilesBounds.containsBounds(bounds, true)) {
                    this.initGriddedTiles(bounds);
                } else {
                    //we might have to shift our buffer tiles
                    this.moveGriddedTiles(bounds);
                }
            }
        }
    },
    
    /**
     * APIMethod: setTileSize
     * Check if we are in singleTile mode and if so, set the size as a ratio
     *     of the map size (as specified by the layer's 'ratio' property).
     * 
     * Parameters:
     * size - {<OpenLayers.Size>}
     */
    setTileSize: function(size) { 
        if (this.singleTile) {
            size = this.map.getSize().clone();
            size.h = parseInt(size.h * this.ratio);
            size.w = parseInt(size.w * this.ratio);
        } 
        OpenLayers.Layer.HTTPRequest.prototype.setTileSize.apply(this, [size]);
    },
        
    /**
     * Method: getGridBounds
     * Deprecated. This function will be removed in 3.0. Please use 
     *     getTilesBounds() instead.
     * 
     * Returns:
     * {<OpenLayers.Bounds>} A Bounds object representing the bounds of all the
     * currently loaded tiles (including those partially or not at all seen 
     * onscreen)
     */
    getGridBounds: function() {
        var msg = "The getGridBounds() function is deprecated. It will be " +
                  "removed in 3.0. Please use getTilesBounds() instead.";
        OpenLayers.Console.warn(msg);
        return this.getTilesBounds();
    },

    /**
     * APIMethod: getTilesBounds
     * Return the bounds of the tile grid.
     *
     * Returns:
     * {<OpenLayers.Bounds>} A Bounds object representing the bounds of all the
     *     currently loaded tiles (including those partially or not at all seen 
     *     onscreen).
     */
    getTilesBounds: function() {    
        var bounds = null; 
        
        if (this.grid.length) {
            var bottom = this.grid.length - 1;
            var bottomLeftTile = this.grid[bottom][0];
    
            var right = this.grid[0].length - 1; 
            var topRightTile = this.grid[0][right];
    
            bounds = new OpenLayers.Bounds(bottomLeftTile.bounds.left, 
                                           bottomLeftTile.bounds.bottom,
                                           topRightTile.bounds.right, 
                                           topRightTile.bounds.top);
            
        }   
        return bounds;
    },

    /**
     * Method: initSingleTile
     * 
     * Parameters: 
     * bounds - {<OpenLayers.Bounds>}
     */
    initSingleTile: function(bounds) {

        //determine new tile bounds
        var center = bounds.getCenterLonLat();
        var tileWidth = bounds.getWidth() * this.ratio;
        var tileHeight = bounds.getHeight() * this.ratio;
                                       
        var tileBounds = 
            new OpenLayers.Bounds(center.lon - (tileWidth/2),
                                  center.lat - (tileHeight/2),
                                  center.lon + (tileWidth/2),
                                  center.lat + (tileHeight/2));
  
        var ul = new OpenLayers.LonLat(tileBounds.left, tileBounds.top);
        var px = this.map.getLayerPxFromLonLat(ul);

        if (!this.grid.length) {
            this.grid[0] = [];
        }

        var tile = this.grid[0][0];
        if (!tile) {
            tile = this.addTile(tileBounds, px);
            
            this.addTileMonitoringHooks(tile);
            tile.draw();
            this.grid[0][0] = tile;
        } else {
            tile.moveTo(tileBounds, px);
        }           
        
        //remove all but our single tile
        this.removeExcessTiles(1,1);
    },

    /** 
     * Method: calculateGridLayout
     * Generate parameters for the grid layout. This  
     *
     * Parameters:
     * bounds - {<OpenLayers.Bound>}
     * extent - {<OpenLayers.Bounds>}
     * resolution - {Number}
     *
     * Returns:
     * Object containing properties tilelon, tilelat, tileoffsetlat,
     * tileoffsetlat, tileoffsetx, tileoffsety
     */
    calculateGridLayout: function(bounds, extent, resolution) {
        var tilelon = resolution * this.tileSize.w;
        var tilelat = resolution * this.tileSize.h;
        
        var offsetlon = bounds.left - extent.left;
        var tilecol = Math.floor(offsetlon/tilelon) - this.buffer;
        var tilecolremain = offsetlon/tilelon - tilecol;
        var tileoffsetx = -tilecolremain * this.tileSize.w;
        var tileoffsetlon = extent.left + tilecol * tilelon;
        
        var offsetlat = bounds.top - (extent.bottom + tilelat);  
        var tilerow = Math.ceil(offsetlat/tilelat) + this.buffer;
        var tilerowremain = tilerow - offsetlat/tilelat;
        var tileoffsety = -tilerowremain * this.tileSize.h;
        var tileoffsetlat = extent.bottom + tilerow * tilelat;
        
        return { 
          tilelon: tilelon, tilelat: tilelat,
          tileoffsetlon: tileoffsetlon, tileoffsetlat: tileoffsetlat,
          tileoffsetx: tileoffsetx, tileoffsety: tileoffsety
        };

    },

    /**
     * Method: initGriddedTiles
     * 
     * Parameters:
     * bounds - {<OpenLayers.Bounds>}
     */
    initGriddedTiles:function(bounds) {
        
        // work out mininum number of rows and columns; this is the number of
        // tiles required to cover the viewport plus at least one for panning

        var viewSize = this.map.getSize();
        var minRows = Math.ceil(viewSize.h/this.tileSize.h) + 
                      Math.max(1, 2 * this.buffer);
        var minCols = Math.ceil(viewSize.w/this.tileSize.w) +
                      Math.max(1, 2 * this.buffer);
        
        var extent = this.maxExtent;
        var resolution = this.map.getResolution();
        
        var tileLayout = this.calculateGridLayout(bounds, extent, resolution);

        var tileoffsetx = Math.round(tileLayout.tileoffsetx); // heaven help us
        var tileoffsety = Math.round(tileLayout.tileoffsety);

        var tileoffsetlon = tileLayout.tileoffsetlon;
        var tileoffsetlat = tileLayout.tileoffsetlat;
        
        var tilelon = tileLayout.tilelon;
        var tilelat = tileLayout.tilelat;

        this.origin = new OpenLayers.Pixel(tileoffsetx, tileoffsety);

        var startX = tileoffsetx; 
        var startLon = tileoffsetlon;

        var rowidx = 0;
        
        var layerContainerDivLeft = parseInt(this.map.layerContainerDiv.style.left);
        var layerContainerDivTop = parseInt(this.map.layerContainerDiv.style.top);
        
    
        do {
            var row = this.grid[rowidx++];
            if (!row) {
                row = [];
                this.grid.push(row);
            }

            tileoffsetlon = startLon;
            tileoffsetx = startX;
            var colidx = 0;
 
            do {
                var tileBounds = 
                    new OpenLayers.Bounds(tileoffsetlon, 
                                          tileoffsetlat, 
                                          tileoffsetlon + tilelon,
                                          tileoffsetlat + tilelat);

                var x = tileoffsetx;
                x -= layerContainerDivLeft;

                var y = tileoffsety;
                y -= layerContainerDivTop;

                var px = new OpenLayers.Pixel(x, y);
                var tile = row[colidx++];
                if (!tile) {
                    tile = this.addTile(tileBounds, px);
                    this.addTileMonitoringHooks(tile);
                    row.push(tile);
                } else {
                    tile.moveTo(tileBounds, px, false);
                }
     
                tileoffsetlon += tilelon;       
                tileoffsetx += this.tileSize.w;
            } while ((tileoffsetlon <= bounds.right + tilelon * this.buffer)
                     || colidx < minCols);
             
            tileoffsetlat -= tilelat;
            tileoffsety += this.tileSize.h;
        } while((tileoffsetlat >= bounds.bottom - tilelat * this.buffer)
                || rowidx < minRows);
        
        //shave off exceess rows and colums
        this.removeExcessTiles(rowidx, colidx);

        //now actually draw the tiles
        this.spiralTileLoad();
    },
    
    /**
     * Method: spiralTileLoad
     *   Starts at the top right corner of the grid and proceeds in a spiral 
     *    towards the center, adding tiles one at a time to the beginning of a 
     *    queue. 
     * 
     *   Once all the grid's tiles have been added to the queue, we go back 
     *    and iterate through the queue (thus reversing the spiral order from 
     *    outside-in to inside-out), calling draw() on each tile. 
     */
    spiralTileLoad: function() {
        var tileQueue = [];
 
        var directions = ["right", "down", "left", "up"];

        var iRow = 0;
        var iCell = -1;
        var direction = OpenLayers.Util.indexOf(directions, "right");
        var directionsTried = 0;
        
        while( directionsTried < directions.length) {

            var testRow = iRow;
            var testCell = iCell;

            switch (directions[direction]) {
                case "right":
                    testCell++;
                    break;
                case "down":
                    testRow++;
                    break;
                case "left":
                    testCell--;
                    break;
                case "up":
                    testRow--;
                    break;
            } 
    
            // if the test grid coordinates are within the bounds of the 
            //  grid, get a reference to the tile.
            var tile = null;
            if ((testRow < this.grid.length) && (testRow >= 0) &&
                (testCell < this.grid[0].length) && (testCell >= 0)) {
                tile = this.grid[testRow][testCell];
            }
            
            if ((tile != null) && (!tile.queued)) {
                //add tile to beginning of queue, mark it as queued.
                tileQueue.unshift(tile);
                tile.queued = true;
                
                //restart the directions counter and take on the new coords
                directionsTried = 0;
                iRow = testRow;
                iCell = testCell;
            } else {
                //need to try to load a tile in a different direction
                direction = (direction + 1) % 4;
                directionsTried++;
            }
        } 
        
        // now we go through and draw the tiles in forward order
        for(var i=0, len=tileQueue.length; i<len; i++) {
            var tile = tileQueue[i];
            tile.draw();
            //mark tile as unqueued for the next time (since tiles are reused)
            tile.queued = false;       
        }
    },

    /**
     * APIMethod: addTile
     * Gives subclasses of Grid the opportunity to create an 
     * OpenLayer.Tile of their choosing. The implementer should initialize 
     * the new tile and take whatever steps necessary to display it.
     *
     * Parameters
     * bounds - {<OpenLayers.Bounds>}
     * position - {<OpenLayers.Pixel>}
     *
     * Returns:
     * {<OpenLayers.Tile>} The added OpenLayers.Tile
     */
    addTile:function(bounds, position) {
        // Should be implemented by subclasses
    },
    
    /** 
     * Method: addTileMonitoringHooks
     * This function takes a tile as input and adds the appropriate hooks to 
     *     the tile so that the layer can keep track of the loading tiles.
     * 
     * Parameters: 
     * tile - {<OpenLayers.Tile>}
     */
    addTileMonitoringHooks: function(tile) {
        
        tile.onLoadStart = function() {
            //if that was first tile then trigger a 'loadstart' on the layer
            if (this.numLoadingTiles == 0) {
                this.events.triggerEvent("loadstart");
            }
            this.numLoadingTiles++;
        };
        tile.events.register("loadstart", this, tile.onLoadStart);
      
        tile.onLoadEnd = function() {
            this.numLoadingTiles--;
            this.events.triggerEvent("tileloaded");
            //if that was the last tile, then trigger a 'loadend' on the layer
            if (this.numLoadingTiles == 0) {
                this.events.triggerEvent("loadend");
            }
        };
        tile.events.register("loadend", this, tile.onLoadEnd);
        tile.events.register("unload", this, tile.onLoadEnd);
    },

    /** 
     * Method: removeTileMonitoringHooks
     * This function takes a tile as input and removes the tile hooks 
     *     that were added in addTileMonitoringHooks()
     * 
     * Parameters: 
     * tile - {<OpenLayers.Tile>}
     */
    removeTileMonitoringHooks: function(tile) {
        tile.unload();
        tile.events.un({
            "loadstart": tile.onLoadStart,
            "loadend": tile.onLoadEnd,
            "unload": tile.onLoadEnd,
            scope: this
        });
    },
    
    /**
     * Method: moveGriddedTiles
     * 
     * Parameters:
     * bounds - {<OpenLayers.Bounds>}
     */
    moveGriddedTiles: function(bounds) {
        var buffer = this.buffer || 1;
        while (true) {
            var tlLayer = this.grid[0][0].position;
            var tlViewPort = 
                this.map.getViewPortPxFromLayerPx(tlLayer);
            if (tlViewPort.x > -this.tileSize.w * (buffer - 1)) {
                this.shiftColumn(true);
            } else if (tlViewPort.x < -this.tileSize.w * buffer) {
                this.shiftColumn(false);
            } else if (tlViewPort.y > -this.tileSize.h * (buffer - 1)) {
                this.shiftRow(true);
            } else if (tlViewPort.y < -this.tileSize.h * buffer) {
                this.shiftRow(false);
            } else {
                break;
            }
        };
    },

    /**
     * Method: shiftRow
     * Shifty grid work
     *
     * Parameters:
     * prepend - {Boolean} if true, prepend to beginning.
     *                          if false, then append to end
     */
    shiftRow:function(prepend) {
        var modelRowIndex = (prepend) ? 0 : (this.grid.length - 1);
        var grid = this.grid;
        var modelRow = grid[modelRowIndex];

        var resolution = this.map.getResolution();
        var deltaY = (prepend) ? -this.tileSize.h : this.tileSize.h;
        var deltaLat = resolution * -deltaY;

        var row = (prepend) ? grid.pop() : grid.shift();

        for (var i=0, len=modelRow.length; i<len; i++) {
            var modelTile = modelRow[i];
            var bounds = modelTile.bounds.clone();
            var position = modelTile.position.clone();
            bounds.bottom = bounds.bottom + deltaLat;
            bounds.top = bounds.top + deltaLat;
            position.y = position.y + deltaY;
            row[i].moveTo(bounds, position);
        }

        if (prepend) {
            grid.unshift(row);
        } else {
            grid.push(row);
        }
    },

    /**
     * Method: shiftColumn
     * Shift grid work in the other dimension
     *
     * Parameters:
     * prepend - {Boolean} if true, prepend to beginning.
     *                          if false, then append to end
     */
    shiftColumn: function(prepend) {
        var deltaX = (prepend) ? -this.tileSize.w : this.tileSize.w;
        var resolution = this.map.getResolution();
        var deltaLon = resolution * deltaX;

        for (var i=0, len=this.grid.length; i<len; i++) {
            var row = this.grid[i];
            var modelTileIndex = (prepend) ? 0 : (row.length - 1);
            var modelTile = row[modelTileIndex];
            
            var bounds = modelTile.bounds.clone();
            var position = modelTile.position.clone();
            bounds.left = bounds.left + deltaLon;
            bounds.right = bounds.right + deltaLon;
            position.x = position.x + deltaX;

            var tile = prepend ? this.grid[i].pop() : this.grid[i].shift();
            tile.moveTo(bounds, position);
            if (prepend) {
                row.unshift(tile);
            } else {
                row.push(tile);
            }
        }
    },
    
    /**
     * Method: removeExcessTiles
     * When the size of the map or the buffer changes, we may need to
     *     remove some excess rows and columns.
     * 
     * Parameters:
     * rows - {Integer} Maximum number of rows we want our grid to have.
     * colums - {Integer} Maximum number of columns we want our grid to have.
     */
    removeExcessTiles: function(rows, columns) {
        
        // remove extra rows
        while (this.grid.length > rows) {
            var row = this.grid.pop();
            for (var i=0, l=row.length; i<l; i++) {
                var tile = row[i];
                this.removeTileMonitoringHooks(tile);
                tile.destroy();
            }
        }
        
        // remove extra columns
        while (this.grid[0].length > columns) {
            for (var i=0, l=this.grid.length; i<l; i++) {
                var row = this.grid[i];
                var tile = row.pop();
                this.removeTileMonitoringHooks(tile);
                tile.destroy();
            }
        }
    },

    /**
     * Method: onMapResize
     * For singleTile layers, this will set a new tile size according to the
     * dimensions of the map pane.
     */
    onMapResize: function() {
        if (this.singleTile) {
            this.clearGrid();
            this.setTileSize();
        }
    },
    
    /**
     * APIMethod: getTileBounds
     * Returns The tile bounds for a layer given a pixel location.
     *
     * Parameters:
     * viewPortPx - {<OpenLayers.Pixel>} The location in the viewport.
     *
     * Returns:
     * {<OpenLayers.Bounds>} Bounds of the tile at the given pixel location.
     */
    getTileBounds: function(viewPortPx) {
        var maxExtent = this.maxExtent;
        var resolution = this.getResolution();
        var tileMapWidth = resolution * this.tileSize.w;
        var tileMapHeight = resolution * this.tileSize.h;
        var mapPoint = this.getLonLatFromViewPortPx(viewPortPx);
        var tileLeft = maxExtent.left + (tileMapWidth *
                                         Math.floor((mapPoint.lon -
                                                     maxExtent.left) /
                                                    tileMapWidth));
        var tileBottom = maxExtent.bottom + (tileMapHeight *
                                             Math.floor((mapPoint.lat -
                                                         maxExtent.bottom) /
                                                        tileMapHeight));
        return new OpenLayers.Bounds(tileLeft, tileBottom,
                                     tileLeft + tileMapWidth,
                                     tileBottom + tileMapHeight);
    },
    
    CLASS_NAME: "OpenLayers.Layer.Grid"
});
/* ======================================================================
    OpenLayers/Style.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
  * full text of the license. */


/**
 * @requires OpenLayers/Util.js
 * @requires OpenLayers/Feature/Vector.js
 */

/**
 * Class: OpenLayers.Style
 * This class represents a UserStyle obtained
 *     from a SLD, containing styling rules.
 */
OpenLayers.Style = OpenLayers.Class({

    /**
     * APIProperty: name
     * {String}
     */
    name: null,
    
    /**
     * Property: title
     * {String} Title of this style (set if included in SLD)
     */
    title: null,
    
    /**
     * Property: description
     * {String} Description of this style (set if abstract is included in SLD)
     */
    description: null,

    /**
     * APIProperty: layerName
     * {<String>} name of the layer that this style belongs to, usually
     * according to the NamedLayer attribute of an SLD document.
     */
    layerName: null,
    
    /**
     * APIProperty: isDefault
     * {Boolean}
     */
    isDefault: false,
     
    /** 
     * Property: rules 
     * {Array(<OpenLayers.Rule>)}
     */
    rules: null,
    
    /**
     * Property: context
     * {Object} An optional object with properties that symbolizers' property
     * values should be evaluated against. If no context is specified,
     * feature.attributes will be used
     */
    context: null,

    /**
     * Property: defaultStyle
     * {Object} hash of style properties to use as default for merging
     * rule-based style symbolizers onto. If no rules are defined,
     * createSymbolizer will return this style. If <defaultsPerSymbolizer> is set to
     * true, the defaultStyle will only be taken into account if there are
     * rules defined.
     */
    defaultStyle: null,
    
    /**
     * Property: defaultsPerSymbolizer
     * {Boolean} If set to true, the <defaultStyle> will extend the symbolizer
     * of every rule. Properties of the <defaultStyle> will also be used to set
     * missing symbolizer properties if the symbolizer has stroke, fill or
     * graphic set to true. Default is false.
     */
    defaultsPerSymbolizer: false,
    
    /**
     * Property: propertyStyles
     * {Hash of Boolean} cache of style properties that need to be parsed for
     * propertyNames. Property names are keys, values won't be used.
     */
    propertyStyles: null,
    

    /** 
     * Constructor: OpenLayers.Style
     * Creates a UserStyle.
     *
     * Parameters:
     * style        - {Object} Optional hash of style properties that will be
     *                used as default style for this style object. This style
     *                applies if no rules are specified. Symbolizers defined in
     *                rules will extend this default style.
     * options - {Object} An optional object with properties to set on the
     *     style.
     *
     * Valid options:
     * rules - {Array(<OpenLayers.Rule>)} List of rules to be added to the
     *     style.
     * 
     * Return:
     * {<OpenLayers.Style>}
     */
    initialize: function(style, options) {

        OpenLayers.Util.extend(this, options);
        this.rules = [];
        if(options && options.rules) {
            this.addRules(options.rules);
        }

        // use the default style from OpenLayers.Feature.Vector if no style
        // was given in the constructor
        this.setDefaultStyle(style ||
                             OpenLayers.Feature.Vector.style["default"]);

    },

    /** 
     * APIMethod: destroy
     * nullify references to prevent circular references and memory leaks
     */
    destroy: function() {
        for (var i=0, len=this.rules.length; i<len; i++) {
            this.rules[i].destroy();
            this.rules[i] = null;
        }
        this.rules = null;
        this.defaultStyle = null;
    },
    
    /**
     * Method: createSymbolizer
     * creates a style by applying all feature-dependent rules to the base
     * style.
     * 
     * Parameters:
     * feature - {<OpenLayers.Feature>} feature to evaluate rules for
     * 
     * Returns:
     * {Object} symbolizer hash
     */
    createSymbolizer: function(feature) {
        var style = this.defaultsPerSymbolizer ? {} : this.createLiterals(
            OpenLayers.Util.extend({}, this.defaultStyle), feature);
        
        var rules = this.rules;

        var rule, context;
        var elseRules = [];
        var appliedRules = false;
        for(var i=0, len=rules.length; i<len; i++) {
            rule = rules[i];
            // does the rule apply?
            var applies = rule.evaluate(feature);
            
            if(applies) {
                if(rule instanceof OpenLayers.Rule && rule.elseFilter) {
                    elseRules.push(rule);
                } else {
                    appliedRules = true;
                    this.applySymbolizer(rule, style, feature);
                }
            }
        }
        
        // if no other rules apply, apply the rules with else filters
        if(appliedRules == false && elseRules.length > 0) {
            appliedRules = true;
            for(var i=0, len=elseRules.length; i<len; i++) {
                this.applySymbolizer(elseRules[i], style, feature);
            }
        }

        // don't display if there were rules but none applied
        if(rules.length > 0 && appliedRules == false) {
            style.display = "none";
        }
        
        return style;
    },
    
    /**
     * Method: applySymbolizer
     *
     * Parameters:
     * rule - {OpenLayers.Rule}
     * style - {Object}
     * feature - {<OpenLayer.Feature.Vector>}
     *
     * Returns:
     * {Object} A style with new symbolizer applied.
     */
    applySymbolizer: function(rule, style, feature) {
        var symbolizerPrefix = feature.geometry ?
                this.getSymbolizerPrefix(feature.geometry) :
                OpenLayers.Style.SYMBOLIZER_PREFIXES[0];

        var symbolizer = rule.symbolizer[symbolizerPrefix] || rule.symbolizer;
        
        if(this.defaultsPerSymbolizer === true) {
            var defaults = this.defaultStyle;
            OpenLayers.Util.applyDefaults(symbolizer, {
                pointRadius: defaults.pointRadius
            });
            if(symbolizer.stroke === true || symbolizer.graphic === true) {
                OpenLayers.Util.applyDefaults(symbolizer, {
                    strokeWidth: defaults.strokeWidth,
                    strokeColor: defaults.strokeColor,
                    strokeOpacity: defaults.strokeOpacity,
                    strokeDashstyle: defaults.strokeDashstyle,
                    strokeLinecap: defaults.strokeLinecap
                });
            }
            if(symbolizer.fill === true || symbolizer.graphic === true) {
                OpenLayers.Util.applyDefaults(symbolizer, {
                    fillColor: defaults.fillColor,
                    fillOpacity: defaults.fillOpacity
                });
            }
            if(symbolizer.graphic === true) {
                OpenLayers.Util.applyDefaults(symbolizer, {
                    pointRadius: this.defaultStyle.pointRadius,
                    externalGraphic: this.defaultStyle.externalGraphic,
                    graphicName: this.defaultStyle.graphicName,
                    graphicOpacity: this.defaultStyle.graphicOpacity,
                    graphicWidth: this.defaultStyle.graphicWidth,
                    graphicHeight: this.defaultStyle.graphicHeight,
                    graphicXOffset: this.defaultStyle.graphicXOffset,
                    graphicYOffset: this.defaultStyle.graphicYOffset
                });
            }
        }

        // merge the style with the current style
        return this.createLiterals(
                OpenLayers.Util.extend(style, symbolizer), feature);
    },
    
    /**
     * Method: createLiterals
     * creates literals for all style properties that have an entry in
     * <this.propertyStyles>.
     * 
     * Parameters:
     * style   - {Object} style to create literals for. Will be modified
     *           inline.
     * feature - {Object}
     * 
     * Returns:
     * {Object} the modified style
     */
    createLiterals: function(style, feature) {
        var context = OpenLayers.Util.extend({}, feature.attributes || feature.data);
        OpenLayers.Util.extend(context, this.context);
        
        for (var i in this.propertyStyles) {
            style[i] = OpenLayers.Style.createLiteral(style[i], context, feature, i);
        }
        return style;
    },
    
    /**
     * Method: findPropertyStyles
     * Looks into all rules for this style and the defaultStyle to collect
     * all the style hash property names containing ${...} strings that have
     * to be replaced using the createLiteral method before returning them.
     * 
     * Returns:
     * {Object} hash of property names that need createLiteral parsing. The
     * name of the property is the key, and the value is true;
     */
    findPropertyStyles: function() {
        var propertyStyles = {};

        // check the default style
        var style = this.defaultStyle;
        this.addPropertyStyles(propertyStyles, style);

        // walk through all rules to check for properties in their symbolizer
        var rules = this.rules;
        var symbolizer, value;
        for (var i=0, len=rules.length; i<len; i++) {
            symbolizer = rules[i].symbolizer;
            for (var key in symbolizer) {
                value = symbolizer[key];
                if (typeof value == "object") {
                    // symbolizer key is "Point", "Line" or "Polygon"
                    this.addPropertyStyles(propertyStyles, value);
                } else {
                    // symbolizer is a hash of style properties
                    this.addPropertyStyles(propertyStyles, symbolizer);
                    break;
                }
            }
        }
        return propertyStyles;
    },
    
    /**
     * Method: addPropertyStyles
     * 
     * Parameters:
     * propertyStyles - {Object} hash to add new property styles to. Will be
     *                  modified inline
     * symbolizer     - {Object} search this symbolizer for property styles
     * 
     * Returns:
     * {Object} propertyStyles hash
     */
    addPropertyStyles: function(propertyStyles, symbolizer) {
        var property;
        for (var key in symbolizer) {
            property = symbolizer[key];
            if (typeof property == "string" &&
                    property.match(/\$\{\w+\}/)) {
                propertyStyles[key] = true;
            }
        }
        return propertyStyles;
    },
    
    /**
     * APIMethod: addRules
     * Adds rules to this style.
     * 
     * Parameters:
     * rules - {Array(<OpenLayers.Rule>)}
     */
    addRules: function(rules) {
        this.rules = this.rules.concat(rules);
        this.propertyStyles = this.findPropertyStyles();
    },
    
    /**
     * APIMethod: setDefaultStyle
     * Sets the default style for this style object.
     * 
     * Parameters:
     * style - {Object} Hash of style properties
     */
    setDefaultStyle: function(style) {
        this.defaultStyle = style; 
        this.propertyStyles = this.findPropertyStyles();
    },
        
    /**
     * Method: getSymbolizerPrefix
     * Returns the correct symbolizer prefix according to the
     * geometry type of the passed geometry
     * 
     * Parameters:
     * geometry {<OpenLayers.Geometry>}
     * 
     * Returns:
     * {String} key of the according symbolizer
     */
    getSymbolizerPrefix: function(geometry) {
        var prefixes = OpenLayers.Style.SYMBOLIZER_PREFIXES;
        for (var i=0, len=prefixes.length; i<len; i++) {
            if (geometry.CLASS_NAME.indexOf(prefixes[i]) != -1) {
                return prefixes[i];
            }
        }
    },
    
    CLASS_NAME: "OpenLayers.Style"
});


/**
 * Function: createLiteral
 * converts a style value holding a combination of PropertyName and Literal
 * into a Literal, taking the property values from the passed features.
 * 
 * Parameters:
 * value - {String} value to parse. If this string contains a construct like
 *         "foo ${bar}", then "foo " will be taken as literal, and "${bar}"
 *         will be replaced by the value of the "bar" attribute of the passed
 *         feature.
 * context - {Object} context to take attribute values from
 * feature - {<OpenLayers.Feature.Vector>} optional feature to pass to
 *           <OpenLayers.String.format> for evaluating functions in the
 *           context.
 * property - {String} optional, name of the property for which the literal is
 *            being created for evaluating functions in the context.
 * 
 * Returns:
 * {String} the parsed value. In the example of the value parameter above, the
 * result would be "foo valueOfBar", assuming that the passed feature has an
 * attribute named "bar" with the value "valueOfBar".
 */
OpenLayers.Style.createLiteral = function(value, context, feature, property) {
    if (typeof value == "string" && value.indexOf("${") != -1) {
        value = OpenLayers.String.format(value, context, [feature, property]);
        value = (isNaN(value) || !value) ? value : parseFloat(value);
    }
    return value;
};
    
/**
 * Constant: OpenLayers.Style.SYMBOLIZER_PREFIXES
 * {Array} prefixes of the sld symbolizers. These are the
 * same as the main geometry types
 */
OpenLayers.Style.SYMBOLIZER_PREFIXES = ['Point', 'Line', 'Polygon', 'Text'];
/* ======================================================================
    OpenLayers/Control/Navigation.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */

/**
 * @requires OpenLayers/Control/ZoomBox.js
 * @requires OpenLayers/Control/DragPan.js
 * @requires OpenLayers/Handler/MouseWheel.js
 * @requires OpenLayers/Handler/Click.js
 */

/**
 * Class: OpenLayers.Control.Navigation
 * The navigation control handles map browsing with mouse events (dragging,
 *     double-clicking, and scrolling the wheel).  Create a new navigation 
 *     control with the <OpenLayers.Control.Navigation> control.  
 * 
 *     Note that this control is added to the map by default (if no controls 
 *     array is sent in the options object to the <OpenLayers.Map> 
 *     constructor).
 * 
 * Inherits:
 *  - <OpenLayers.Control>
 */
OpenLayers.Control.Navigation = OpenLayers.Class(OpenLayers.Control, {

    /** 
     * Property: dragPan
     * {<OpenLayers.Control.DragPan>} 
     */
    dragPan: null,

    /**
     * APIProprety: dragPanOptions
     * {Object} Options passed to the DragPan control.
     */
    dragPanOptions: null,

    /**
     * APIProperty: documentDrag
     * {Boolean} Allow panning of the map by dragging outside map viewport.
     *     Default is false.
     */
    documentDrag: false,

    /** 
     * Property: zoomBox
     * {<OpenLayers.Control.ZoomBox>}
     */
    zoomBox: null,

    /**
     * APIProperty: zoomBoxEnabled
     * {Boolean} Whether the user can draw a box to zoom
     */
    zoomBoxEnabled: true, 

    /**
     * APIProperty: zoomWheelEnabled
     * {Boolean} Whether the mousewheel should zoom the map
     */
    zoomWheelEnabled: true,
    
    /**
     * Property: mouseWheelOptions
     * {Object} Options passed to the MouseWheel control (only useful if
     *     <zoomWheelEnabled> is set to true)
     */
    mouseWheelOptions: null,

    /**
     * APIProperty: handleRightClicks
     * {Boolean} Whether or not to handle right clicks. Default is false.
     */
    handleRightClicks: false,

    /**
     * APIProperty: zoomBoxKeyMask
     * {Integer} <OpenLayers.Handler> key code of the key, which has to be
     *    pressed, while drawing the zoom box with the mouse on the screen. 
     *    You should probably set handleRightClicks to true if you use this
     *    with MOD_CTRL, to disable the context menu for machines which use
     *    CTRL-Click as a right click.
     * Default: <OpenLayers.Handler.MOD_SHIFT
     */
    zoomBoxKeyMask: OpenLayers.Handler.MOD_SHIFT,
    
    /**
     * APIProperty: autoActivate
     * {Boolean} Activate the control when it is added to a map.  Default is
     *     true.
     */
    autoActivate: true,

    /**
     * Constructor: OpenLayers.Control.Navigation
     * Create a new navigation control
     * 
     * Parameters:
     * options - {Object} An optional object whose properties will be set on
     *                    the control
     */
    initialize: function(options) {
        this.handlers = {};
        OpenLayers.Control.prototype.initialize.apply(this, arguments);
    },

    /**
     * Method: destroy
     * The destroy method is used to perform any clean up before the control
     * is dereferenced.  Typically this is where event listeners are removed
     * to prevent memory leaks.
     */
    destroy: function() {
        this.deactivate();

        if (this.dragPan) {
            this.dragPan.destroy();
        }
        this.dragPan = null;

        if (this.zoomBox) {
            this.zoomBox.destroy();
        }
        this.zoomBox = null;
        OpenLayers.Control.prototype.destroy.apply(this,arguments);
    },
    
    /**
     * Method: activate
     */
    activate: function() {
        this.dragPan.activate();
        if (this.zoomWheelEnabled) {
            this.handlers.wheel.activate();
        }    
        this.handlers.click.activate();
        if (this.zoomBoxEnabled) {
            this.zoomBox.activate();
        }
        return OpenLayers.Control.prototype.activate.apply(this,arguments);
    },

    /**
     * Method: deactivate
     */
    deactivate: function() {
        this.zoomBox.deactivate();
        this.dragPan.deactivate();
        this.handlers.click.deactivate();
        this.handlers.wheel.deactivate();
        return OpenLayers.Control.prototype.deactivate.apply(this,arguments);
    },
    
    /**
     * Method: draw
     */
    draw: function() {
        // disable right mouse context menu for support of right click events
        if (this.handleRightClicks) {
            this.map.viewPortDiv.oncontextmenu = OpenLayers.Function.False;
        }

        var clickCallbacks = { 
            'dblclick': this.defaultDblClick, 
            'dblrightclick': this.defaultDblRightClick 
        };
        var clickOptions = {
            'double': true, 
            'stopDouble': true
        };
        this.handlers.click = new OpenLayers.Handler.Click(
            this, clickCallbacks, clickOptions
        );
        this.dragPan = new OpenLayers.Control.DragPan(
            OpenLayers.Util.extend({
                map: this.map,
                documentDrag: this.documentDrag
            }, this.dragPanOptions)
        );
        this.zoomBox = new OpenLayers.Control.ZoomBox(
                    {map: this.map, keyMask: this.zoomBoxKeyMask});
        this.dragPan.draw();
        this.zoomBox.draw();
        this.handlers.wheel = new OpenLayers.Handler.MouseWheel(
                                    this, {"up"  : this.wheelUp,
                                           "down": this.wheelDown},
                                    this.mouseWheelOptions );
    },

    /**
     * Method: defaultDblClick 
     * 
     * Parameters:
     * evt - {Event} 
     */
    defaultDblClick: function (evt) {
        var newCenter = this.map.getLonLatFromViewPortPx( evt.xy ); 
        this.map.setCenter(newCenter, this.map.zoom + 1);
    },

    /**
     * Method: defaultDblRightClick 
     * 
     * Parameters:
     * evt - {Event} 
     */
    defaultDblRightClick: function (evt) {
        var newCenter = this.map.getLonLatFromViewPortPx( evt.xy ); 
        this.map.setCenter(newCenter, this.map.zoom - 1);
    },
    
    /**
     * Method: wheelChange  
     *
     * Parameters:
     * evt - {Event}
     * deltaZ - {Integer}
     */
    wheelChange: function(evt, deltaZ) {
        var currentZoom = this.map.getZoom();
        var newZoom = this.map.getZoom() + Math.round(deltaZ);
        newZoom = Math.max(newZoom, 0);
        newZoom = Math.min(newZoom, this.map.getNumZoomLevels());
        if (newZoom === currentZoom) {
            return;
        }
        var size    = this.map.getSize();
        var deltaX  = size.w/2 - evt.xy.x;
        var deltaY  = evt.xy.y - size.h/2;
        var newRes  = this.map.baseLayer.getResolutionForZoom(newZoom);
        var zoomPoint = this.map.getLonLatFromPixel(evt.xy);
        var newCenter = new OpenLayers.LonLat(
                            zoomPoint.lon + deltaX * newRes,
                            zoomPoint.lat + deltaY * newRes );
        this.map.setCenter( newCenter, newZoom );
    },

    /** 
     * Method: wheelUp
     * User spun scroll wheel up
     * 
     * Parameters:
     * evt - {Event}
     * delta - {Integer}
     */
    wheelUp: function(evt, delta) {
        this.wheelChange(evt, delta || 1);
    },

    /** 
     * Method: wheelDown
     * User spun scroll wheel down
     * 
     * Parameters:
     * evt - {Event}
     * delta - {Integer}
     */
    wheelDown: function(evt, delta) {
        this.wheelChange(evt, delta || -1);
    },
    
    /**
     * Method: disableZoomBox
     */
    disableZoomBox : function() {
        this.zoomBoxEnabled = false;
        this.zoomBox.deactivate();       
    },
    
    /**
     * Method: enableZoomBox
     */
    enableZoomBox : function() {
        this.zoomBoxEnabled = true;
        if (this.active) {
            this.zoomBox.activate();
        }    
    },
    
    /**
     * Method: disableZoomWheel
     */
    
    disableZoomWheel : function() {
        this.zoomWheelEnabled = false;
        this.handlers.wheel.deactivate();       
    },
    
    /**
     * Method: enableZoomWheel
     */
    
    enableZoomWheel : function() {
        this.zoomWheelEnabled = true;
        if (this.active) {
            this.handlers.wheel.activate();
        }    
    },

    CLASS_NAME: "OpenLayers.Control.Navigation"
});
/* ======================================================================
    OpenLayers/Geometry.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */
 
/**
 * @requires OpenLayers/Format/WKT.js
 * @requires OpenLayers/Feature/Vector.js
 */

/**
 * Class: OpenLayers.Geometry
 * A Geometry is a description of a geographic object.  Create an instance of
 * this class with the <OpenLayers.Geometry> constructor.  This is a base class,
 * typical geometry types are described by subclasses of this class.
 */
OpenLayers.Geometry = OpenLayers.Class({

    /**
     * Property: id
     * {String} A unique identifier for this geometry.
     */
    id: null,

    /**
     * Property: parent
     * {<OpenLayers.Geometry>}This is set when a Geometry is added as component
     * of another geometry
     */
    parent: null,

    /**
     * Property: bounds 
     * {<OpenLayers.Bounds>} The bounds of this geometry
     */
    bounds: null,

    /**
     * Constructor: OpenLayers.Geometry
     * Creates a geometry object.  
     */
    initialize: function() {
        this.id = OpenLayers.Util.createUniqueID(this.CLASS_NAME+ "_");
    },
    
    /**
     * Method: destroy
     * Destroy this geometry.
     */
    destroy: function() {
        this.id = null;
        this.bounds = null;
    },
    
    /**
     * APIMethod: clone
     * Create a clone of this geometry.  Does not set any non-standard
     *     properties of the cloned geometry.
     * 
     * Returns:
     * {<OpenLayers.Geometry>} An exact clone of this geometry.
     */
    clone: function() {
        return new OpenLayers.Geometry();
    },
    
    /**
     * Set the bounds for this Geometry.
     * 
     * Parameters:
     * object - {<OpenLayers.Bounds>} 
     */
    setBounds: function(bounds) {
        if (bounds) {
            this.bounds = bounds.clone();
        }
    },
    
    /**
     * Method: clearBounds
     * Nullify this components bounds and that of its parent as well.
     */
    clearBounds: function() {
        this.bounds = null;
        if (this.parent) {
            this.parent.clearBounds();
        }    
    },
    
    /**
     * Method: extendBounds
     * Extend the existing bounds to include the new bounds. 
     * If geometry's bounds is not yet set, then set a new Bounds.
     * 
     * Parameters:
     * newBounds - {<OpenLayers.Bounds>} 
     */
    extendBounds: function(newBounds){
        var bounds = this.getBounds();
        if (!bounds) {
            this.setBounds(newBounds);
        } else {
            this.bounds.extend(newBounds);
        }
    },
    
    /**
     * APIMethod: getBounds
     * Get the bounds for this Geometry. If bounds is not set, it 
     * is calculated again, this makes queries faster.
     * 
     * Returns:
     * {<OpenLayers.Bounds>}
     */
    getBounds: function() {
        if (this.bounds == null) {
            this.calculateBounds();
        }
        return this.bounds;
    },
    
    /** 
     * APIMethod: calculateBounds
     * Recalculate the bounds for the geometry. 
     */
    calculateBounds: function() {
        //
        // This should be overridden by subclasses.
        //
    },
    
    /**
     * APIMethod: distanceTo
     * Calculate the closest distance between two geometries (on the x-y plane).
     *
     * Parameters:
     * geometry - {<OpenLayers.Geometry>} The target geometry.
     * options - {Object} Optional properties for configuring the distance
     *     calculation.
     *
     * Valid options depend on the specific geometry type.
     * 
     * Returns:
     * {Number | Object} The distance between this geometry and the target.
     *     If details is true, the return will be an object with distance,
     *     x0, y0, x1, and x2 properties.  The x0 and y0 properties represent
     *     the coordinates of the closest point on this geometry. The x1 and y1
     *     properties represent the coordinates of the closest point on the
     *     target geometry.
     */
    distanceTo: function(geometry, options) {
    },
    
    /**
     * APIMethod: getVertices
     * Return a list of all points in this geometry.
     *
     * Parameters:
     * nodes - {Boolean} For lines, only return vertices that are
     *     endpoints.  If false, for lines, only vertices that are not
     *     endpoints will be returned.  If not provided, all vertices will
     *     be returned.
     *
     * Returns:
     * {Array} A list of all vertices in the geometry.
     */
    getVertices: function(nodes) {
    },

    /**
     * Method: atPoint
     * Note - This is only an approximation based on the bounds of the 
     * geometry.
     * 
     * Parameters:
     * lonlat - {<OpenLayers.LonLat>} 
     * toleranceLon - {float} Optional tolerance in Geometric Coords
     * toleranceLat - {float} Optional tolerance in Geographic Coords
     * 
     * Returns:
     * {Boolean} Whether or not the geometry is at the specified location
     */
    atPoint: function(lonlat, toleranceLon, toleranceLat) {
        var atPoint = false;
        var bounds = this.getBounds();
        if ((bounds != null) && (lonlat != null)) {

            var dX = (toleranceLon != null) ? toleranceLon : 0;
            var dY = (toleranceLat != null) ? toleranceLat : 0;
    
            var toleranceBounds = 
                new OpenLayers.Bounds(this.bounds.left - dX,
                                      this.bounds.bottom - dY,
                                      this.bounds.right + dX,
                                      this.bounds.top + dY);

            atPoint = toleranceBounds.containsLonLat(lonlat);
        }
        return atPoint;
    },
    
    /**
     * Method: getLength
     * Calculate the length of this geometry. This method is defined in
     * subclasses.
     * 
     * Returns:
     * {Float} The length of the collection by summing its parts
     */
    getLength: function() {
        //to be overridden by geometries that actually have a length
        //
        return 0.0;
    },

    /**
     * Method: getArea
     * Calculate the area of this geometry. This method is defined in subclasses.
     * 
     * Returns:
     * {Float} The area of the collection by summing its parts
     */
    getArea: function() {
        //to be overridden by geometries that actually have an area
        //
        return 0.0;
    },
    
    /**
     * APIMethod: getCentroid
     * Calculate the centroid of this geometry. This method is defined in subclasses.
     *
     * Returns:
     * {<OpenLayers.Geometry.Point>} The centroid of the collection
     */
    getCentroid: function() {
        return null;
    },

    /**
     * Method: toString
     * Returns the Well-Known Text representation of a geometry
     *
     * Returns:
     * {String} Well-Known Text
     */
    toString: function() {
        return OpenLayers.Format.WKT.prototype.write(
            new OpenLayers.Feature.Vector(this)
        );
    },

    CLASS_NAME: "OpenLayers.Geometry"
});

/**
 * Function: OpenLayers.Geometry.fromWKT
 * Generate a geometry given a Well-Known Text string.
 *
 * Parameters:
 * wkt - {String} A string representing the geometry in Well-Known Text.
 *
 * Returns:
 * {<OpenLayers.Geometry>} A geometry of the appropriate class.
 */
OpenLayers.Geometry.fromWKT = function(wkt) {
    var format = arguments.callee.format;
    if(!format) {
        format = new OpenLayers.Format.WKT();
        arguments.callee.format = format;
    }
    var geom;
    var result = format.read(wkt);
    if(result instanceof OpenLayers.Feature.Vector) {
        geom = result.geometry;
    } else if(result instanceof Array) {
        var len = result.length;
        var components = new Array(len);
        for(var i=0; i<len; ++i) {
            components[i] = result[i].geometry;
        }
        geom = new OpenLayers.Geometry.Collection(components);
    }
    return geom;
};
    
/**
 * Method: OpenLayers.Geometry.segmentsIntersect
 * Determine whether two line segments intersect.  Optionally calculates
 *     and returns the intersection point.  This function is optimized for
 *     cases where seg1.x2 >= seg2.x1 || seg2.x2 >= seg1.x1.  In those
 *     obvious cases where there is no intersection, the function should
 *     not be called.
 *
 * Parameters:
 * seg1 - {Object} Object representing a segment with properties x1, y1, x2,
 *     and y2.  The start point is represented by x1 and y1.  The end point
 *     is represented by x2 and y2.  Start and end are ordered so that x1 < x2.
 * seg2 - {Object} Object representing a segment with properties x1, y1, x2,
 *     and y2.  The start point is represented by x1 and y1.  The end point
 *     is represented by x2 and y2.  Start and end are ordered so that x1 < x2.
 * options - {Object} Optional properties for calculating the intersection.
 *
 * Valid options:
 * point - {Boolean} Return the intersection point.  If false, the actual
 *     intersection point will not be calculated.  If true and the segments
 *     intersect, the intersection point will be returned.  If true and
 *     the segments do not intersect, false will be returned.  If true and
 *     the segments are coincident, true will be returned.
 * tolerance - {Number} If a non-null value is provided, if the segments are
 *     within the tolerance distance, this will be considered an intersection.
 *     In addition, if the point option is true and the calculated intersection
 *     is within the tolerance distance of an end point, the endpoint will be
 *     returned instead of the calculated intersection.  Further, if the
 *     intersection is within the tolerance of endpoints on both segments, or
 *     if two segment endpoints are within the tolerance distance of eachother
 *     (but no intersection is otherwise calculated), an endpoint on the
 *     first segment provided will be returned.
 *
 * Returns:
 * {Boolean | <OpenLayers.Geometry.Point>}  The two segments intersect.
 *     If the point argument is true, the return will be the intersection
 *     point or false if none exists.  If point is true and the segments
 *     are coincident, return will be true (and the instersection is equal
 *     to the shorter segment).
 */
OpenLayers.Geometry.segmentsIntersect = function(seg1, seg2, options) {
    var point = options && options.point;
    var tolerance = options && options.tolerance;
    var intersection = false;
    var x11_21 = seg1.x1 - seg2.x1;
    var y11_21 = seg1.y1 - seg2.y1;
    var x12_11 = seg1.x2 - seg1.x1;
    var y12_11 = seg1.y2 - seg1.y1;
    var y22_21 = seg2.y2 - seg2.y1;
    var x22_21 = seg2.x2 - seg2.x1;
    var d = (y22_21 * x12_11) - (x22_21 * y12_11);
    var n1 = (x22_21 * y11_21) - (y22_21 * x11_21);
    var n2 = (x12_11 * y11_21) - (y12_11 * x11_21);
    if(d == 0) {
        // parallel
        if(n1 == 0 && n2 == 0) {
            // coincident
            intersection = true;
        }
    } else {
        var along1 = n1 / d;
        var along2 = n2 / d;
        if(along1 >= 0 && along1 <= 1 && along2 >=0 && along2 <= 1) {
            // intersect
            if(!point) {
                intersection = true;
            } else {
                // calculate the intersection point
                var x = seg1.x1 + (along1 * x12_11);
                var y = seg1.y1 + (along1 * y12_11);
                intersection = new OpenLayers.Geometry.Point(x, y);
            }
        }
    }
    if(tolerance) {
        var dist;
        if(intersection) {
            if(point) {
                var segs = [seg1, seg2];
                var seg, x, y;
                // check segment endpoints for proximity to intersection
                // set intersection to first endpoint within the tolerance
                outer: for(var i=0; i<2; ++i) {
                    seg = segs[i];
                    for(var j=1; j<3; ++j) {
                        x = seg["x" + j];
                        y = seg["y" + j];
                        dist = Math.sqrt(
                            Math.pow(x - intersection.x, 2) +
                            Math.pow(y - intersection.y, 2)
                        );
                        if(dist < tolerance) {
                            intersection.x = x;
                            intersection.y = y;
                            break outer;
                        }
                    }
                }
                
            }
        } else {
            // no calculated intersection, but segments could be within
            // the tolerance of one another
            var segs = [seg1, seg2];
            var source, target, x, y, p, result;
            // check segment endpoints for proximity to intersection
            // set intersection to first endpoint within the tolerance
            outer: for(var i=0; i<2; ++i) {
                source = segs[i];
                target = segs[(i+1)%2];
                for(var j=1; j<3; ++j) {
                    p = {x: source["x"+j], y: source["y"+j]};
                    result = OpenLayers.Geometry.distanceToSegment(p, target);
                    if(result.distance < tolerance) {
                        if(point) {
                            intersection = new OpenLayers.Geometry.Point(p.x, p.y);
                        } else {
                            intersection = true;
                        }
                        break outer;
                    }
                }
            }
        }
    }
    return intersection;
};

/**
 * Function: OpenLayers.Geometry.distanceToSegment
 *
 * Parameters:
 * point - {Object} An object with x and y properties representing the
 *     point coordinates.
 * segment - {Object} An object with x1, y1, x2, and y2 properties
 *     representing endpoint coordinates.
 *
 * Returns:
 * {Object} An object with distance, x, and y properties.  The distance
 *     will be the shortest distance between the input point and segment.
 *     The x and y properties represent the coordinates along the segment
 *     where the shortest distance meets the segment.
 */
OpenLayers.Geometry.distanceToSegment = function(point, segment) {
    var x0 = point.x;
    var y0 = point.y;
    var x1 = segment.x1;
    var y1 = segment.y1;
    var x2 = segment.x2;
    var y2 = segment.y2;
    var dx = x2 - x1;
    var dy = y2 - y1;
    var along = ((dx * (x0 - x1)) + (dy * (y0 - y1))) /
                (Math.pow(dx, 2) + Math.pow(dy, 2));
    var x, y;
    if(along <= 0.0) {
        x = x1;
        y = y1;
    } else if(along >= 1.0) {
        x = x2;
        y = y2;
    } else {
        x = x1 + along * dx;
        y = y1 + along * dy;
    }
    return {
        distance: Math.sqrt(Math.pow(x - x0, 2) + Math.pow(y - y0, 2)),
        x: x, y: y
    };
};
/* ======================================================================
    OpenLayers/Layer/WMS.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */


/**
 * @requires OpenLayers/Layer/Grid.js
 * @requires OpenLayers/Tile/Image.js
 */

/**
 * Class: OpenLayers.Layer.WMS
 * Instances of OpenLayers.Layer.WMS are used to display data from OGC Web
 *     Mapping Services. Create a new WMS layer with the <OpenLayers.Layer.WMS>
 *     constructor.
 * 
 * Inherits from:
 *  - <OpenLayers.Layer.Grid>
 */
OpenLayers.Layer.WMS = OpenLayers.Class(OpenLayers.Layer.Grid, {

    /**
     * Constant: DEFAULT_PARAMS
     * {Object} Hashtable of default parameter key/value pairs 
     */
    DEFAULT_PARAMS: { service: "WMS",
                      version: "1.1.1",
                      request: "GetMap",
                      styles: "",
                      exceptions: "application/vnd.ogc.se_inimage",
                      format: "image/jpeg"
                     },
    
    /**
     * Property: reproject
     * *Deprecated*. See http://trac.openlayers.org/wiki/SphericalMercator
     * for information on the replacement for this functionality. 
     * {Boolean} Try to reproject this layer if its coordinate reference system
     *           is different than that of the base layer.  Default is true.  
     *           Set this in the layer options.  Should be set to false in 
     *           most cases.
     */
    reproject: false,
 
    /**
     * APIProperty: isBaseLayer
     * {Boolean} Default is true for WMS layer
     */
    isBaseLayer: true,
    
    /**
     * APIProperty: encodeBBOX
     * {Boolean} Should the BBOX commas be encoded? The WMS spec says 'no', 
     * but some services want it that way. Default false.
     */
    encodeBBOX: false,
    
    /** 
     * APIProperty: noMagic 
     * {Boolean} If true, the image format will not be automagicaly switched 
     *     from image/jpeg to image/png or image/gif when using 
     *     TRANSPARENT=TRUE. Also isBaseLayer will not changed by the  
     *     constructor. Default false. 
     */ 
    noMagic: false,
    
    /**
     * Property: yx
     * {Array} Array of strings with the EPSG codes for which the axis order
     *     is to be reversed (yx instead of xy, LatLon instead of LonLat). This
     *     is only relevant for WMS versions >= 1.3.0.
     */
    yx: ['EPSG:4326'],
    
    /**
     * Constructor: OpenLayers.Layer.WMS
     * Create a new WMS layer object
     *
     * Example:
     * (code)
     * var wms = new OpenLayers.Layer.WMS("NASA Global Mosaic",
     *                                    "http://wms.jpl.nasa.gov/wms.cgi", 
     *                                    {layers: "modis,global_mosaic"});
     * (end)
     *
     * Parameters:
     * name - {String} A name for the layer
     * url - {String} Base url for the WMS
     *                (e.g. http://wms.jpl.nasa.gov/wms.cgi)
     * params - {Object} An object with key/value pairs representing the
     *                   GetMap query string parameters and parameter values.
     * options - {Ojbect} Hashtable of extra options to tag onto the layer
     */
    initialize: function(name, url, params, options) {
        var newArguments = [];
        //uppercase params
        params = OpenLayers.Util.upperCaseObject(params);
        newArguments.push(name, url, params, options);
        OpenLayers.Layer.Grid.prototype.initialize.apply(this, newArguments);
        OpenLayers.Util.applyDefaults(
                       this.params, 
                       OpenLayers.Util.upperCaseObject(this.DEFAULT_PARAMS)
                       );


        //layer is transparent        
        if (!this.noMagic && this.params.TRANSPARENT && 
            this.params.TRANSPARENT.toString().toLowerCase() == "true") {
            
            // unless explicitly set in options, make layer an overlay
            if ( (options == null) || (!options.isBaseLayer) ) {
                this.isBaseLayer = false;
            } 
            
            // jpegs can never be transparent, so intelligently switch the 
            //  format, depending on teh browser's capabilities
            if (this.params.FORMAT == "image/jpeg") {
                this.params.FORMAT = OpenLayers.Util.alphaHack() ? "image/gif"
                                                                 : "image/png";
            }
        }

    },    

    /**
     * Method: destroy
     * Destroy this layer
     */
    destroy: function() {
        // for now, nothing special to do here. 
        OpenLayers.Layer.Grid.prototype.destroy.apply(this, arguments);  
    },

    
    /**
     * Method: clone
     * Create a clone of this layer
     *
     * Returns:
     * {<OpenLayers.Layer.WMS>} An exact clone of this layer
     */
    clone: function (obj) {
        
        if (obj == null) {
            obj = new OpenLayers.Layer.WMS(this.name,
                                           this.url,
                                           this.params,
                                           this.getOptions());
        }

        //get all additions from superclasses
        obj = OpenLayers.Layer.Grid.prototype.clone.apply(this, [obj]);

        // copy/set any non-init, non-simple values here

        return obj;
    },    
    
    /**
     * APIMethod: reverseAxisOrder
     * Returns true if the axis order is reversed for the WMS version and
     * projection of the layer.
     * 
     * Returns:
     * {Boolean} true if the axis order is reversed, false otherwise.
     */
    reverseAxisOrder: function() {
        return (parseFloat(this.params.VERSION) >= 1.3 && 
            OpenLayers.Util.indexOf(this.yx, 
            this.map.getProjectionObject().getCode()) !== -1)
    },
    
    /**
     * Method: getURL
     * Return a GetMap query string for this layer
     *
     * Parameters:
     * bounds - {<OpenLayers.Bounds>} A bounds representing the bbox for the
     *                                request.
     *
     * Returns:
     * {String} A string with the layer's url and parameters and also the
     *          passed-in bounds and appropriate tile size specified as 
     *          parameters.
     */
    getURL: function (bounds) {
        bounds = this.adjustBounds(bounds);
        
        var imageSize = this.getImageSize();
        var newParams = {};
        // WMS 1.3 introduced axis order
        var reverseAxisOrder = this.reverseAxisOrder();
        newParams.BBOX = this.encodeBBOX ?
            bounds.toBBOX(null, reverseAxisOrder) :
            bounds.toArray(reverseAxisOrder);
        newParams.WIDTH = imageSize.w;
        newParams.HEIGHT = imageSize.h;
        var requestString = this.getFullRequestString(newParams);
        return requestString;
    },

    /**
     * Method: addTile
     * addTile creates a tile, initializes it, and adds it to the layer div. 
     *
     * Parameters:
     * bounds - {<OpenLayers.Bounds>}
     * position - {<OpenLayers.Pixel>}
     * 
     * Returns:
     * {<OpenLayers.Tile.Image>} The added OpenLayers.Tile.Image
     */
    addTile:function(bounds,position) {
        return new OpenLayers.Tile.Image(this, position, bounds, 
                                         null, this.tileSize);
    },

    /**
     * APIMethod: mergeNewParams
     * Catch changeParams and uppercase the new params to be merged in
     *     before calling changeParams on the super class.
     * 
     *     Once params have been changed, the tiles will be reloaded with
     *     the new parameters.
     * 
     * Parameters:
     * newParams - {Object} Hashtable of new params to use
     */
    mergeNewParams:function(newParams) {
        var upperParams = OpenLayers.Util.upperCaseObject(newParams);
        var newArguments = [upperParams];
        return OpenLayers.Layer.Grid.prototype.mergeNewParams.apply(this, 
                                                             newArguments);
    },

    /** 
     * APIMethod: getFullRequestString
     * Combine the layer's url with its params and these newParams. 
     *   
     *     Add the SRS parameter from projection -- this is probably
     *     more eloquently done via a setProjection() method, but this 
     *     works for now and always.
     *
     * Parameters:
     * newParams - {Object}
     * altUrl - {String} Use this as the url instead of the layer's url
     * 
     * Returns:
     * {String} 
     */
    getFullRequestString:function(newParams, altUrl) {
        var projectionCode = this.map.getProjection();
        var value = (projectionCode == "none") ? null : projectionCode
        if (parseFloat(this.params.VERSION) >= 1.3) {
            this.params.CRS = value;
        } else {
            this.params.SRS = value;
        }

        return OpenLayers.Layer.Grid.prototype.getFullRequestString.apply(
                                                    this, arguments);
    },

    CLASS_NAME: "OpenLayers.Layer.WMS"
});
/* ======================================================================
    OpenLayers/Layer/XYZ.js
   ====================================================================== */

/* Copyright (c) 2006-2009 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */

/**
 * @requires OpenLayers/Layer/Grid.js
 * @requires OpenLayers/Tile/Image.js
 */

/** 
 * Class: OpenLayers.Layer.XYZ
 * The XYZ class is designed to make it easier for people who have tiles
 * arranged by a standard XYZ grid. 
 */
OpenLayers.Layer.XYZ = OpenLayers.Class(OpenLayers.Layer.Grid, {
    
    /**
     * APIProperty: isBaseLayer
     * Default is true, as this is designed to be a base tile source. 
     */
    isBaseLayer: true,
    
    /**
     * APIProperty: sphericalMecator
     * Whether the tile extents should be set to the defaults for 
     *    spherical mercator. Useful for things like OpenStreetMap.
     *    Default is false, except for the OSM subclass.
     */
    sphericalMercator: false,

    /**
     * Constructor: OpenLayers.Layer.XYZ
     *
     * Parameters:
     * name - {String}
     * url - {String}
     * options - {Object} Hashtable of extra options to tag onto the layer
     */
    initialize: function(name, url, options) {
        if (options && options.sphericalMercator || this.sphericalMercator) {
            options = OpenLayers.Util.extend({
                maxExtent: new OpenLayers.Bounds(
                    -128 * 156543.0339,
                    -128 * 156543.0339,
                    128 * 156543.0339,
                    128 * 156543.0339
                ),
                maxResolution: 156543.0339,
                numZoomLevels: 19,
                units: "m",
                projection: "EPSG:900913"
            }, options);
        }
        url = url || this.url;
        name = name || this.name;
        var newArguments = [name, url, {}, options];
        OpenLayers.Layer.Grid.prototype.initialize.apply(this, newArguments);
    },
    
    /**
     * APIMethod: clone
     * Create a clone of this layer
     *
     * Parameters:
     * obj - {Object} Is this ever used?
     * 
     * Returns:
     * {<OpenLayers.Layer.Grid>} An exact clone of this OpenLayers.Layer.Grid
     */
    clone: function (obj) {
        
        if (obj == null) {
            obj = new OpenLayers.Layer.XYZ(this.name,
                                            this.url,
                                            this.getOptions());
        }

        //get all additions from superclasses
        obj = OpenLayers.Layer.HTTPRequest.prototype.clone.apply(this, [obj]);

        // copy/set any non-init, non-simple values here
        if (this.tileSize != null) {
            obj.tileSize = this.tileSize.clone();
        }
        
        // we do not want to copy reference to grid, so we make a new array
        obj.grid = [];

        return obj;
    },    

    /**
     * Method: getUrl
     *
     * Parameters:
     * bounds - {<OpenLayers.Bounds>}
     *
     * Returns:
     * {String} A string with the layer's url and parameters and also the
     *          passed-in bounds and appropriate tile size specified as
     *          parameters
     */
    getURL: function (bounds) {
        var res = this.map.getResolution();
        var x = Math.round((bounds.left - this.maxExtent.left) 
            / (res * this.tileSize.w));
        var y = Math.round((this.maxExtent.top - bounds.top) 
            / (res * this.tileSize.h));
        var z = this.map.getZoom();

        var url = this.url;
        var s = '' + x + y + z;
        if (url instanceof Array)
        {
            url = this.selectUrl(s, url);
        }
        
        var path = OpenLayers.String.format(url, {'x': x, 'y': y, 'z': z});

        return path;
    },
    
    /**
     * Method: addTile
     * addTile creates a tile, initializes it, and adds it to the layer div. 
     * 
     * Parameters:
     * bounds - {<OpenLayers.Bounds>}
     * position - {<OpenLayers.Pixel>}
     * 
     * Returns:
     * {<OpenLayers.Tile.Image>} The added OpenLayers.Tile.Image
     */
    addTile:function(bounds,position) {
        return new OpenLayers.Tile.Image(this, position, bounds, 
                                         null, this.tileSize);
    },
     
    /* APIMethod: setMap
     * When the layer is added to a map, then we can fetch our origin 
     *    (if we don't have one.) 
     * 
     * Parameters:
     * map - {<OpenLayers.Map>}
     */
    setMap: function(map) {
        OpenLayers.Layer.Grid.prototype.setMap.apply(this, arguments);
        if (!this.tileOrigin) { 
            this.tileOrigin = new OpenLayers.LonLat(this.maxExtent.left,
                                                this.maxExtent.bottom);
        }                                       
    },

    CLASS_NAME: "OpenLayers.Layer.XYZ"
});


/**
 * Class: OpenLayers.Layer.OSM
 * A class to access OpenStreetMap tiles. By default, uses the OpenStreetMap
 *    hosted tile.openstreetmap.org 'Mapnik' tileset. If you wish to use
 *    tiles@home / osmarender layer instead, you can pass a layer like:
 * 
 * (code)
 *     new OpenLayers.Layer.OSM("t@h", 
 *       "http://tah.openstreetmap.org/Tiles/tile/${z}/${x}/${y}.png"); 
 * (end)
 *
 * This layer defaults to Spherical Mercator.
 */

OpenLayers.Layer.OSM = OpenLayers.Class(OpenLayers.Layer.XYZ, {
     name: "OpenStreetMap",
     attribution: "Data CC-By-SA by <a href='http://openstreetmap.org/'>OpenStreetMap</a>",
     sphericalMercator: true,
     url: 'http://tile.openstreetmap.org/${z}/${x}/${y}.png',
     CLASS_NAME: "OpenLayers.Layer.OSM"
});
/* ======================================================================
    OpenLayers/StyleMap.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */

/**
 * @requires OpenLayers/Style.js
 * @requires OpenLayers/Feature/Vector.js
 */
 
/**
 * Class: OpenLayers.StyleMap
 */
OpenLayers.StyleMap = OpenLayers.Class({
    
    /**
     * Property: styles
     * Hash of {<OpenLayers.Style>}, keyed by names of well known
     * rendering intents (e.g. "default", "temporary", "select", "delete").
     */
    styles: null,
    
    /**
     * Property: extendDefault
     * {Boolean} if true, every render intent will extend the symbolizers
     * specified for the "default" intent at rendering time. Otherwise, every
     * rendering intent will be treated as a completely independent style.
     */
    extendDefault: true,
    
    /**
     * Constructor: OpenLayers.StyleMap
     * 
     * Parameters:
     * style   - {Object} Optional. Either a style hash, or a style object, or
     *           a hash of style objects (style hashes) keyed by rendering
     *           intent. If just one style hash or style object is passed,
     *           this will be used for all known render intents (default,
     *           select, temporary)
     * options - {Object} optional hash of additional options for this
     *           instance
     */
    initialize: function (style, options) {
        this.styles = {
            "default": new OpenLayers.Style(
                OpenLayers.Feature.Vector.style["default"]),
            "select": new OpenLayers.Style(
                OpenLayers.Feature.Vector.style["select"]),
            "temporary": new OpenLayers.Style(
                OpenLayers.Feature.Vector.style["temporary"]),
            "delete": new OpenLayers.Style(
                OpenLayers.Feature.Vector.style["delete"])
        };
        
        // take whatever the user passed as style parameter and convert it
        // into parts of stylemap.
        if(style instanceof OpenLayers.Style) {
            // user passed a style object
            this.styles["default"] = style;
            this.styles["select"] = style;
            this.styles["temporary"] = style;
            this.styles["delete"] = style;
        } else if(typeof style == "object") {
            for(var key in style) {
                if(style[key] instanceof OpenLayers.Style) {
                    // user passed a hash of style objects
                    this.styles[key] = style[key];
                } else if(typeof style[key] == "object") {
                    // user passsed a hash of style hashes
                    this.styles[key] = new OpenLayers.Style(style[key]);
                } else {
                    // user passed a style hash (i.e. symbolizer)
                    this.styles["default"] = new OpenLayers.Style(style);
                    this.styles["select"] = new OpenLayers.Style(style);
                    this.styles["temporary"] = new OpenLayers.Style(style);
                    this.styles["delete"] = new OpenLayers.Style(style);
                    break;
                }
            }
        }
        OpenLayers.Util.extend(this, options);
    },

    /**
     * Method: destroy
     */
    destroy: function() {
        for(var key in this.styles) {
            this.styles[key].destroy();
        }
        this.styles = null;
    },
    
    /**
     * Method: createSymbolizer
     * Creates the symbolizer for a feature for a render intent.
     * 
     * Parameters:
     * feature - {<OpenLayers.Feature>} The feature to evaluate the rules
     *           of the intended style against.
     * intent  - {String} The intent determines the symbolizer that will be
     *           used to draw the feature. Well known intents are "default"
     *           (for just drawing the features), "select" (for selected
     *           features) and "temporary" (for drawing features).
     * 
     * Returns:
     * {Object} symbolizer hash
     */
    createSymbolizer: function(feature, intent) {
        if(!feature) {
            feature = new OpenLayers.Feature.Vector();
        }
        if(!this.styles[intent]) {
            intent = "default";
        }
        feature.renderIntent = intent;
        var defaultSymbolizer = {};
        if(this.extendDefault && intent != "default") {
            defaultSymbolizer = this.styles["default"].createSymbolizer(feature);
        }
        return OpenLayers.Util.extend(defaultSymbolizer,
            this.styles[intent].createSymbolizer(feature));
    },
    
    /**
     * Method: addUniqueValueRules
     * Convenience method to create comparison rules for unique values of a
     * property. The rules will be added to the style object for a specified
     * rendering intent. This method is a shortcut for creating something like
     * the "unique value legends" familiar from well known desktop GIS systems
     * 
     * Parameters:
     * renderIntent - {String} rendering intent to add the rules to
     * property     - {String} values of feature attributes to create the
     *                rules for
     * symbolizers  - {Object} Hash of symbolizers, keyed by the desired
     *                property values 
     * context      - {Object} An optional object with properties that
     *                symbolizers' property values should be evaluated
     *                against. If no context is specified, feature.attributes
     *                will be used
     */
    addUniqueValueRules: function(renderIntent, property, symbolizers, context) {
        var rules = [];
        for (var value in symbolizers) {
            rules.push(new OpenLayers.Rule({
                symbolizer: symbolizers[value],
                context: context,
                filter: new OpenLayers.Filter.Comparison({
                    type: OpenLayers.Filter.Comparison.EQUAL_TO,
                    property: property,
                    value: value
                })
            }));
        }
        this.styles[renderIntent].addRules(rules);
    },

    CLASS_NAME: "OpenLayers.StyleMap"
});
/* ======================================================================
    OpenLayers/Geometry/Point.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */

/**
 * @requires OpenLayers/Geometry.js
 */

/**
 * Class: OpenLayers.Geometry.Point
 * Point geometry class. 
 * 
 * Inherits from:
 *  - <OpenLayers.Geometry> 
 */
OpenLayers.Geometry.Point = OpenLayers.Class(OpenLayers.Geometry, {

    /** 
     * APIProperty: x 
     * {float} 
     */
    x: null,

    /** 
     * APIProperty: y 
     * {float} 
     */
    y: null,

    /**
     * Constructor: OpenLayers.Geometry.Point
     * Construct a point geometry.
     *
     * Parameters:
     * x - {float} 
     * y - {float}
     * 
     */
    initialize: function(x, y) {
        OpenLayers.Geometry.prototype.initialize.apply(this, arguments);
        
        this.x = parseFloat(x);
        this.y = parseFloat(y);
    },

    /**
     * APIMethod: clone
     * 
     * Returns:
     * {<OpenLayers.Geometry.Point>} An exact clone of this OpenLayers.Geometry.Point
     */
    clone: function(obj) {
        if (obj == null) {
            obj = new OpenLayers.Geometry.Point(this.x, this.y);
        }

        // catch any randomly tagged-on properties
        OpenLayers.Util.applyDefaults(obj, this);

        return obj;
    },

    /** 
     * Method: calculateBounds
     * Create a new Bounds based on the lon/lat
     */
    calculateBounds: function () {
        this.bounds = new OpenLayers.Bounds(this.x, this.y,
                                            this.x, this.y);
    },

    /**
     * APIMethod: distanceTo
     * Calculate the closest distance between two geometries (on the x-y plane).
     *
     * Parameters:
     * geometry - {<OpenLayers.Geometry>} The target geometry.
     * options - {Object} Optional properties for configuring the distance
     *     calculation.
     *
     * Valid options:
     * details - {Boolean} Return details from the distance calculation.
     *     Default is false.
     * edge - {Boolean} Calculate the distance from this geometry to the
     *     nearest edge of the target geometry.  Default is true.  If true,
     *     calling distanceTo from a geometry that is wholly contained within
     *     the target will result in a non-zero distance.  If false, whenever
     *     geometries intersect, calling distanceTo will return 0.  If false,
     *     details cannot be returned.
     *
     * Returns:
     * {Number | Object} The distance between this geometry and the target.
     *     If details is true, the return will be an object with distance,
     *     x0, y0, x1, and x2 properties.  The x0 and y0 properties represent
     *     the coordinates of the closest point on this geometry. The x1 and y1
     *     properties represent the coordinates of the closest point on the
     *     target geometry.
     */
    distanceTo: function(geometry, options) {
        var edge = !(options && options.edge === false);
        var details = edge && options && options.details;
        var distance, x0, y0, x1, y1, result;
        if(geometry instanceof OpenLayers.Geometry.Point) {
            x0 = this.x;
            y0 = this.y;
            x1 = geometry.x;
            y1 = geometry.y;
            distance = Math.sqrt(Math.pow(x0 - x1, 2) + Math.pow(y0 - y1, 2));
            result = !details ?
                distance : {x0: x0, y0: y0, x1: x1, y1: y1, distance: distance};
        } else {
            result = geometry.distanceTo(this, options);
            if(details) {
                // switch coord order since this geom is target
                result = {
                    x0: result.x1, y0: result.y1,
                    x1: result.x0, y1: result.y0,
                    distance: result.distance
                };
            }
        }
        return result;
    },
    
    /** 
     * APIMethod: equals
     * Determine whether another geometry is equivalent to this one.  Geometries
     *     are considered equivalent if all components have the same coordinates.
     * 
     * Parameters:
     * geom - {<OpenLayers.Geometry.Point>} The geometry to test. 
     *
     * Returns:
     * {Boolean} The supplied geometry is equivalent to this geometry.
     */
    equals: function(geom) {
        var equals = false;
        if (geom != null) {
            equals = ((this.x == geom.x && this.y == geom.y) ||
                      (isNaN(this.x) && isNaN(this.y) && isNaN(geom.x) && isNaN(geom.y)));
        }
        return equals;
    },
    
    /**
     * Method: toShortString
     *
     * Returns:
     * {String} Shortened String representation of Point object. 
     *         (ex. <i>"5, 42"</i>)
     */
    toShortString: function() {
        return (this.x + ", " + this.y);
    },
    
    /**
     * APIMethod: move
     * Moves a geometry by the given displacement along positive x and y axes.
     *     This modifies the position of the geometry and clears the cached
     *     bounds.
     *
     * Parameters:
     * x - {Float} Distance to move geometry in positive x direction. 
     * y - {Float} Distance to move geometry in positive y direction.
     */
    move: function(x, y) {
        this.x = this.x + x;
        this.y = this.y + y;
        this.clearBounds();
    },

    /**
     * APIMethod: rotate
     * Rotate a point around another.
     *
     * Parameters:
     * angle - {Float} Rotation angle in degrees (measured counterclockwise
     *                 from the positive x-axis)
     * origin - {<OpenLayers.Geometry.Point>} Center point for the rotation
     */
    rotate: function(angle, origin) {
        angle *= Math.PI / 180;
        var radius = this.distanceTo(origin);
        var theta = angle + Math.atan2(this.y - origin.y, this.x - origin.x);
        this.x = origin.x + (radius * Math.cos(theta));
        this.y = origin.y + (radius * Math.sin(theta));
        this.clearBounds();
    },
    
    /**
     * APIMethod: getCentroid
     *
     * Returns:
     * {<OpenLayers.Geometry.Point>} The centroid of the collection
     */
    getCentroid: function() {
        return new OpenLayers.Geometry.Point(this.x, this.y);
    },

    /**
     * APIMethod: resize
     * Resize a point relative to some origin.  For points, this has the effect
     *     of scaling a vector (from the origin to the point).  This method is
     *     more useful on geometry collection subclasses.
     *
     * Parameters:
     * scale - {Float} Ratio of the new distance from the origin to the old
     *                 distance from the origin.  A scale of 2 doubles the
     *                 distance between the point and origin.
     * origin - {<OpenLayers.Geometry.Point>} Point of origin for resizing
     * ratio - {Float} Optional x:y ratio for resizing.  Default ratio is 1.
     * 
     * Returns:
     * {OpenLayers.Geometry} - The current geometry. 
     */
    resize: function(scale, origin, ratio) {
        ratio = (ratio == undefined) ? 1 : ratio;
        this.x = origin.x + (scale * ratio * (this.x - origin.x));
        this.y = origin.y + (scale * (this.y - origin.y));
        this.clearBounds();
        return this;
    },
    
    /**
     * APIMethod: intersects
     * Determine if the input geometry intersects this one.
     *
     * Parameters:
     * geometry - {<OpenLayers.Geometry>} Any type of geometry.
     *
     * Returns:
     * {Boolean} The input geometry intersects this one.
     */
    intersects: function(geometry) {
        var intersect = false;
        if(geometry.CLASS_NAME == "OpenLayers.Geometry.Point") {
            intersect = this.equals(geometry);
        } else {
            intersect = geometry.intersects(this);
        }
        return intersect;
    },
    
    /**
     * APIMethod: transform
     * Translate the x,y properties of the point from source to dest.
     * 
     * Parameters:
     * source - {<OpenLayers.Projection>} 
     * dest - {<OpenLayers.Projection>}
     * 
     * Returns:
     * {<OpenLayers.Geometry>} 
     */
    transform: function(source, dest) {
        if ((source && dest)) {
            OpenLayers.Projection.transform(
                this, source, dest); 
            this.bounds = null;
        }       
        return this;
    },

    /**
     * APIMethod: getVertices
     * Return a list of all points in this geometry.
     *
     * Parameters:
     * nodes - {Boolean} For lines, only return vertices that are
     *     endpoints.  If false, for lines, only vertices that are not
     *     endpoints will be returned.  If not provided, all vertices will
     *     be returned.
     *
     * Returns:
     * {Array} A list of all vertices in the geometry.
     */
    getVertices: function(nodes) {
        return [this];
    },

    CLASS_NAME: "OpenLayers.Geometry.Point"
});
/* ======================================================================
    OpenLayers/Layer/Vector.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */

/**
 * @requires OpenLayers/Layer.js
 * @requires OpenLayers/Renderer.js
 * @requires OpenLayers/StyleMap.js
 * @requires OpenLayers/Feature/Vector.js
 * @requires OpenLayers/Console.js
 */

/**
 * Class: OpenLayers.Layer.Vector
 * Instances of OpenLayers.Layer.Vector are used to render vector data from
 *     a variety of sources. Create a new vector layer with the
 *     <OpenLayers.Layer.Vector> constructor.
 *
 * Inherits from:
 *  - <OpenLayers.Layer>
 */
OpenLayers.Layer.Vector = OpenLayers.Class(OpenLayers.Layer, {

    /**
     * Constant: EVENT_TYPES
     * {Array(String)} Supported application event types.  Register a listener
     *     for a particular event with the following syntax:
     * (code)
     * layer.events.register(type, obj, listener);
     * (end)
     *
     * Listeners will be called with a reference to an event object.  The
     *     properties of this event depends on exactly what happened.
     *
     * All event objects have at least the following properties:
     * object - {Object} A reference to layer.events.object.
     * element - {DOMElement} A reference to layer.events.element.
     *
     * Supported map event types (in addition to those from <OpenLayers.Layer>):
     * beforefeatureadded - Triggered before a feature is added.  Listeners
     *      will receive an object with a *feature* property referencing the
     *      feature to be added.  To stop the feature from being added, a
     *      listener should return false.
     * beforefeaturesadded - Triggered before an array of features is added.
     *      Listeners will receive an object with a *features* property
     *      referencing the feature to be added. To stop the features from
     *      being added, a listener should return false.
     * featureadded - Triggered after a feature is added.  The event
     *      object passed to listeners will have a *feature* property with a
     *      reference to the added feature.
     * featuresadded - Triggered after features are added.  The event
     *      object passed to listeners will have a *features* property with a
     *      reference to an array of added features.
     * beforefeatureremoved - Triggered before a feature is removed. Listeners
     *      will receive an object with a *feature* property referencing the
     *      feature to be removed.
     * featureremoved - Triggerd after a feature is removed. The event
     *      object passed to listeners will have a *feature* property with a
     *      reference to the removed feature.
     * featuresremoved - Triggered after features are removed. The event
     *      object passed to listeners will have a *features* property with a
     *      reference to an array of removed features.
     * featureselected - Triggered after a feature is selected.  Listeners
     *      will receive an object with a *feature* property referencing the
     *      selected feature.
     * featureunselected - Triggered after a feature is unselected.
     *      Listeners will receive an object with a *feature* property
     *      referencing the unselected feature.
     * beforefeaturemodified - Triggered when a feature is selected to 
     *      be modified.  Listeners will receive an object with a *feature* 
     *      property referencing the selected feature.
     * featuremodified - Triggered when a feature has been modified.
     *      Listeners will receive an object with a *feature* property referencing 
     *      the modified feature.
     * afterfeaturemodified - Triggered when a feature is finished being modified.
     *      Listeners will receive an object with a *feature* property referencing 
     *      the modified feature.
     * vertexmodified - Triggered when a vertex within any feature geometry
     *      has been modified.  Listeners will receive an object with a
     *      *feature* property referencing the modified feature, a *vertex*
     *      property referencing the vertex modified (always a point geometry),
     *      and a *pixel* property referencing the pixel location of the
     *      modification.
     * sketchstarted - Triggered when a feature sketch bound for this layer
     *      is started.  Listeners will receive an object with a *feature*
     *      property referencing the new sketch feature and a *vertex* property
     *      referencing the creation point.
     * sketchmodified - Triggered when a feature sketch bound for this layer
     *      is modified.  Listeners will receive an object with a *vertex*
     *      property referencing the modified vertex and a *feature* property
     *      referencing the sketch feature.
     * sketchcomplete - Triggered when a feature sketch bound for this layer
     *      is complete.  Listeners will receive an object with a *feature*
     *      property referencing the sketch feature.  By returning false, a
     *      listener can stop the sketch feature from being added to the layer.
     * refresh - Triggered when something wants a strategy to ask the protocol
     *      for a new set of features.
     */
    EVENT_TYPES: ["beforefeatureadded", "beforefeaturesadded",
                  "featureadded", "featuresadded",
                  "beforefeatureremoved", "featureremoved", "featuresremoved",
                  "beforefeatureselected", "featureselected", "featureunselected", 
                  "beforefeaturemodified", "featuremodified", "afterfeaturemodified",
                  "vertexmodified", "sketchstarted", "sketchmodified",
                  "sketchcomplete", "refresh"],

    /**
     * APIProperty: isBaseLayer
     * {Boolean} The layer is a base layer.  Default is false.  Set this property
     * in the layer options.
     */
    isBaseLayer: false,

    /** 
     * APIProperty: isFixed
     * {Boolean} Whether the layer remains in one place while dragging the
     * map.
     */
    isFixed: false,

    /** 
     * APIProperty: isVector
     * {Boolean} Whether the layer is a vector layer.
     */
    isVector: true,
    
    /** 
     * APIProperty: features
     * {Array(<OpenLayers.Feature.Vector>)} 
     */
    features: null,
    
    /** 
     * Property: filter
     * {<OpenLayers.Filter>} The filter set in this layer,
     *     a strategy launching read requests can combined
     *     this filter with its own filter.
     */
    filter: null,
    
    /** 
     * Property: selectedFeatures
     * {Array(<OpenLayers.Feature.Vector>)} 
     */
    selectedFeatures: null,
    
    /**
     * Property: unrenderedFeatures
     * {Object} hash of features, keyed by feature.id, that the renderer
     *     failed to draw
     */
    unrenderedFeatures: null,

    /**
     * APIProperty: reportError
     * {Boolean} report friendly error message when loading of renderer
     * fails.
     */
    reportError: true, 

    /** 
     * APIProperty: style
     * {Object} Default style for the layer
     */
    style: null,
    
    /**
     * Property: styleMap
     * {<OpenLayers.StyleMap>}
     */
    styleMap: null,
    
    /**
     * Property: strategies
     * {Array(<OpenLayers.Strategy>})} Optional list of strategies for the layer.
     */
    strategies: null,
    
    /**
     * Property: protocol
     * {<OpenLayers.Protocol>} Optional protocol for the layer.
     */
    protocol: null,
    
    /**
     * Property: renderers
     * {Array(String)} List of supported Renderer classes. Add to this list to
     * add support for additional renderers. This list is ordered:
     * the first renderer which returns true for the  'supported()'
     * method will be used, if not defined in the 'renderer' option.
     */
    renderers: ['SVG', 'VML', 'Canvas'],
    
    /** 
     * Property: renderer
     * {<OpenLayers.Renderer>}
     */
    renderer: null,
    
    /**
     * APIProperty: rendererOptions
     * {Object} Options for the renderer. See {<OpenLayers.Renderer>} for
     *     supported options.
     */
    rendererOptions: null,
    
    /** 
     * APIProperty: geometryType
     * {String} geometryType allows you to limit the types of geometries this
     * layer supports. This should be set to something like
     * "OpenLayers.Geometry.Point" to limit types.
     */
    geometryType: null,

    /** 
     * Property: drawn
     * {Boolean} Whether the Vector Layer features have been drawn yet.
     */
    drawn: false,

    /**
     * Constructor: OpenLayers.Layer.Vector
     * Create a new vector layer
     *
     * Parameters:
     * name - {String} A name for the layer
     * options - {Object} Optional object with non-default properties to set on
     *           the layer.
     *
     * Returns:
     * {<OpenLayers.Layer.Vector>} A new vector layer
     */
    initialize: function(name, options) {
        
        // concatenate events specific to vector with those from the base
        this.EVENT_TYPES =
            OpenLayers.Layer.Vector.prototype.EVENT_TYPES.concat(
            OpenLayers.Layer.prototype.EVENT_TYPES
        );

        OpenLayers.Layer.prototype.initialize.apply(this, arguments);

        // allow user-set renderer, otherwise assign one
        if (!this.renderer || !this.renderer.supported()) {  
            this.assignRenderer();
        }

        // if no valid renderer found, display error
        if (!this.renderer || !this.renderer.supported()) {
            this.renderer = null;
            this.displayError();
        } 

        if (!this.styleMap) {
            this.styleMap = new OpenLayers.StyleMap();
        }

        this.features = [];
        this.selectedFeatures = [];
        this.unrenderedFeatures = {};
        
        // Allow for custom layer behavior
        if(this.strategies){
            for(var i=0, len=this.strategies.length; i<len; i++) {
                this.strategies[i].setLayer(this);
            }
        }

    },

    /**
     * APIMethod: destroy
     * Destroy this layer
     */
    destroy: function() {
        if (this.strategies) {
            var strategy, i, len;
            for(i=0, len=this.strategies.length; i<len; i++) {
                strategy = this.strategies[i];
                if(strategy.autoDestroy) {
                    strategy.destroy();
                }
            }
            this.strategies = null;
        }
        if (this.protocol) {
            if(this.protocol.autoDestroy) {
                this.protocol.destroy();
            }
            this.protocol = null;
        }
        this.destroyFeatures();
        this.features = null;
        this.selectedFeatures = null;
        this.unrenderedFeatures = null;
        if (this.renderer) {
            this.renderer.destroy();
        }
        this.renderer = null;
        this.geometryType = null;
        this.drawn = null;
        OpenLayers.Layer.prototype.destroy.apply(this, arguments);  
    },

    /**
     * Method: clone
     * Create a clone of this layer.
     * 
     * Note: Features of the layer are also cloned.
     *
     * Returns:
     * {<OpenLayers.Layer.Vector>} An exact clone of this layer
     */
    clone: function (obj) {
        
        if (obj == null) {
            obj = new OpenLayers.Layer.Vector(this.name, this.getOptions());
        }

        //get all additions from superclasses
        obj = OpenLayers.Layer.prototype.clone.apply(this, [obj]);

        // copy/set any non-init, non-simple values here
        var features = this.features;
        var len = features.length;
        var clonedFeatures = new Array(len);
        for(var i=0; i<len; ++i) {
            clonedFeatures[i] = features[i].clone();
        }
        obj.features = clonedFeatures;

        return obj;
    },    
    
    /**
     * Method: refresh
     * Ask the layer to request features again and redraw them.  Triggers
     *     the refresh event if the layer is in range and visible.
     *
     * Parameters:
     * obj - {Object} Optional object with properties for any listener of
     *     the refresh event.
     */
    refresh: function(obj) {
        if(this.calculateInRange() && this.visibility) {
            this.events.triggerEvent("refresh", obj);
        }
    },

    /** 
     * Method: assignRenderer
     * Iterates through the available renderer implementations and selects 
     * and assigns the first one whose "supported()" function returns true.
     */    
    assignRenderer: function()  {
        for (var i=0, len=this.renderers.length; i<len; i++) {
            var rendererClass = OpenLayers.Renderer[this.renderers[i]];
            if (rendererClass && rendererClass.prototype.supported()) {
                this.renderer = new rendererClass(this.div,
                    this.rendererOptions);
                break;
            }  
        }  
    },

    /** 
     * Method: displayError 
     * Let the user know their browser isn't supported.
     */
    displayError: function() {
        if (this.reportError) {
            OpenLayers.Console.userError(OpenLayers.i18n("browserNotSupported", 
                                     {'renderers':this.renderers.join("\n")}));
        }    
    },

    /** 
     * Method: setMap
     * The layer has been added to the map. 
     * 
     * If there is no renderer set, the layer can't be used. Remove it.
     * Otherwise, give the renderer a reference to the map and set its size.
     * 
     * Parameters:
     * map - {<OpenLayers.Map>} 
     */
    setMap: function(map) {        
        OpenLayers.Layer.prototype.setMap.apply(this, arguments);

        if (!this.renderer) {
            this.map.removeLayer(this);
        } else {
            this.renderer.map = this.map;
            this.renderer.setSize(this.map.getSize());
        }
    },

    /**
     * Method: afterAdd
     * Called at the end of the map.addLayer sequence.  At this point, the map
     *     will have a base layer.  Any autoActivate strategies will be
     *     activated here.
     */
    afterAdd: function() {
        if(this.strategies) {
            var strategy, i, len;
            for(i=0, len=this.strategies.length; i<len; i++) {
                strategy = this.strategies[i];
                if(strategy.autoActivate) {
                    strategy.activate();
                }
            }
        }
    },

    /**
     * Method: removeMap
     * The layer has been removed from the map.
     *
     * Parameters:
     * map - {<OpenLayers.Map>}
     */
    removeMap: function(map) {
        if(this.strategies) {
            var strategy, i, len;
            for(i=0, len=this.strategies.length; i<len; i++) {
                strategy = this.strategies[i];
                if(strategy.autoActivate) {
                    strategy.deactivate();
                }
            }
        }
    },
    
    /**
     * Method: onMapResize
     * Notify the renderer of the change in size. 
     * 
     */
    onMapResize: function() {
        OpenLayers.Layer.prototype.onMapResize.apply(this, arguments);
        this.renderer.setSize(this.map.getSize());
    },

    /**
     * Method: moveTo
     *  Reset the vector layer's div so that it once again is lined up with 
     *   the map. Notify the renderer of the change of extent, and in the
     *   case of a change of zoom level (resolution), have the 
     *   renderer redraw features.
     * 
     *  If the layer has not yet been drawn, cycle through the layer's 
     *   features and draw each one.
     * 
     * Parameters:
     * bounds - {<OpenLayers.Bounds>} 
     * zoomChanged - {Boolean} 
     * dragging - {Boolean} 
     */
    moveTo: function(bounds, zoomChanged, dragging) {
        OpenLayers.Layer.prototype.moveTo.apply(this, arguments);
        
        var coordSysUnchanged = true;

        if (!dragging) {
            this.renderer.root.style.visibility = "hidden";
            
            this.div.style.left = -parseInt(this.map.layerContainerDiv.style.left) + "px";
            this.div.style.top = -parseInt(this.map.layerContainerDiv.style.top) + "px";
            var extent = this.map.getExtent();
            coordSysUnchanged = this.renderer.setExtent(extent, zoomChanged);
            
            this.renderer.root.style.visibility = "visible";

            // Force a reflow on gecko based browsers to prevent jump/flicker.
            // This seems to happen on only certain configurations; it was originally
            // noticed in FF 2.0 and Linux.
            if (navigator.userAgent.toLowerCase().indexOf("gecko") != -1) {
                this.div.scrollLeft = this.div.scrollLeft;
            }
            
            if(!zoomChanged && coordSysUnchanged) {
                for(var i in this.unrenderedFeatures) {
                    var feature = this.unrenderedFeatures[i];
                    this.drawFeature(feature);
                }
            }
        }
        
        if (!this.drawn || zoomChanged || !coordSysUnchanged) {
            this.drawn = true;
            var feature;
            for(var i=0, len=this.features.length; i<len; i++) {
                this.renderer.locked = (i !== (len - 1));
                feature = this.features[i];
                this.drawFeature(feature);
            }
        }    
    },
    
    /** 
     * APIMethod: display
     * Hide or show the Layer
     * 
     * Parameters:
     * display - {Boolean}
     */
    display: function(display) {
        OpenLayers.Layer.prototype.display.apply(this, arguments);
        // we need to set the display style of the root in case it is attached
        // to a foreign layer
        var currentDisplay = this.div.style.display;
        if(currentDisplay != this.renderer.root.style.display) {
            this.renderer.root.style.display = currentDisplay;
        }
    },

    /**
     * APIMethod: addFeatures
     * Add Features to the layer.
     *
     * Parameters:
     * features - {Array(<OpenLayers.Feature.Vector>)} 
     * options - {Object}
     */
    addFeatures: function(features, options) {
        if (!(features instanceof Array)) {
            features = [features];
        }
        
        var notify = !options || !options.silent;
        if(notify) {
            var event = {features: features};
            var ret = this.events.triggerEvent("beforefeaturesadded", event);
            if(ret === false) {
                return;
            }
            features = event.features;
        }
        

        for (var i=0, len=features.length; i<len; i++) {
            if (i != (features.length - 1)) {
                this.renderer.locked = true;
            } else {
                this.renderer.locked = false;
            }    
            var feature = features[i];
            
            if (this.geometryType &&
              !(feature.geometry instanceof this.geometryType)) {
                var throwStr = OpenLayers.i18n('componentShouldBe',
                          {'geomType':this.geometryType.prototype.CLASS_NAME});
                throw throwStr;
              }

            this.features.push(feature);
            
            //give feature reference to its layer
            feature.layer = this;

            if (!feature.style && this.style) {
                feature.style = OpenLayers.Util.extend({}, this.style);
            }

            if (notify) {
                if(this.events.triggerEvent("beforefeatureadded",
                                            {feature: feature}) === false) {
                    continue;
                };
                this.preFeatureInsert(feature);
            }

            this.drawFeature(feature);
            
            if (notify) {
                this.events.triggerEvent("featureadded", {
                    feature: feature
                });
                this.onFeatureInsert(feature);
            }
        }
        
        if(notify) {
            this.events.triggerEvent("featuresadded", {features: features});
        }
    },


    /**
     * APIMethod: removeFeatures
     * Remove features from the layer.  This erases any drawn features and
     *     removes them from the layer's control.  The beforefeatureremoved
     *     and featureremoved events will be triggered for each feature.  The
     *     featuresremoved event will be triggered after all features have
     *     been removed.  To supress event triggering, use the silent option.
     * 
     * Parameters:
     * features - {Array(<OpenLayers.Feature.Vector>)} List of features to be
     *     removed.
     * options - {Object} Optional properties for changing behavior of the
     *     removal.
     *
     * Valid options:
     * silent - {Boolean} Supress event triggering.  Default is false.
     */
    removeFeatures: function(features, options) {
        if(!features || features.length === 0) {
            return;
        }
        if (!(features instanceof Array)) {
            features = [features];
        }
        if (features === this.features || features === this.selectedFeatures) {
            features = features.slice();
        }

        var notify = !options || !options.silent;

        for (var i = features.length - 1; i >= 0; i--) {
            // We remain locked so long as we're not at 0
            // and the 'next' feature has a geometry. We do the geometry check
            // because if all the features after the current one are 'null', we
            // won't call eraseGeometry, so we break the 'renderer functions
            // will always be called with locked=false *last*' rule. The end result
            // is a possible gratiutious unlocking to save a loop through the rest 
            // of the list checking the remaining features every time. So long as
            // null geoms are rare, this is probably okay.    
            if (i != 0 && features[i-1].geometry) {
                this.renderer.locked = true;
            } else {
                this.renderer.locked = false;
            }
    
            var feature = features[i];
            delete this.unrenderedFeatures[feature.id];

            if (notify) {
                this.events.triggerEvent("beforefeatureremoved", {
                    feature: feature
                });
            }

            this.features = OpenLayers.Util.removeItem(this.features, feature);
            // feature has no layer at this point
            feature.layer = null;

            if (feature.geometry) {
                this.renderer.eraseFeatures(feature);
            }
                    
            //in the case that this feature is one of the selected features, 
            // remove it from that array as well.
            if (OpenLayers.Util.indexOf(this.selectedFeatures, feature) != -1){
                OpenLayers.Util.removeItem(this.selectedFeatures, feature);
            }

            if (notify) {
                this.events.triggerEvent("featureremoved", {
                    feature: feature
                });
            }
        }

        if (notify) {
            this.events.triggerEvent("featuresremoved", {features: features});
        }
    },

    /**
     * APIMethod: destroyFeatures
     * Erase and destroy features on the layer.
     *
     * Parameters:
     * features - {Array(<OpenLayers.Feature.Vector>)} An optional array of
     *     features to destroy.  If not supplied, all features on the layer
     *     will be destroyed.
     * options - {Object}
     */
    destroyFeatures: function(features, options) {
        var all = (features == undefined); // evaluates to true if
                                           // features is null
        if(all) {
            features = this.features;
        }
        if(features) {
            this.removeFeatures(features, options);
            for(var i=features.length-1; i>=0; i--) {
                features[i].destroy();
            }
        }
    },

    /**
     * APIMethod: drawFeature
     * Draw (or redraw) a feature on the layer.  If the optional style argument
     * is included, this style will be used.  If no style is included, the
     * feature's style will be used.  If the feature doesn't have a style,
     * the layer's style will be used.
     * 
     * This function is not designed to be used when adding features to 
     * the layer (use addFeatures instead). It is meant to be used when
     * the style of a feature has changed, or in some other way needs to 
     * visually updated *after* it has already been added to a layer. You
     * must add the feature to the layer for most layer-related events to 
     * happen.
     *
     * Parameters: 
     * feature - {<OpenLayers.Feature.Vector>} 
     * style - {Object} Symbolizer hash or {String} renderIntent
     */
    drawFeature: function(feature, style) {
        // don't try to draw the feature with the renderer if the layer is not 
        // drawn itself
        if (!this.drawn) {
            return
        }
        if (typeof style != "object") {
            if(!style && feature.state === OpenLayers.State.DELETE) {
                style = "delete";
            }
            var renderIntent = style || feature.renderIntent;
            style = feature.style || this.style;
            if (!style) {
                style = this.styleMap.createSymbolizer(feature, renderIntent);
            }
        }
        
        if (!this.renderer.drawFeature(feature, style)) {
            this.unrenderedFeatures[feature.id] = feature;
        } else {
            delete this.unrenderedFeatures[feature.id];
        };
    },
    
    /**
     * Method: eraseFeatures
     * Erase features from the layer.
     *
     * Parameters:
     * features - {Array(<OpenLayers.Feature.Vector>)} 
     */
    eraseFeatures: function(features) {
        this.renderer.eraseFeatures(features);
    },

    /**
     * Method: getFeatureFromEvent
     * Given an event, return a feature if the event occurred over one.
     * Otherwise, return null.
     *
     * Parameters:
     * evt - {Event} 
     *
     * Returns:
     * {<OpenLayers.Feature.Vector>} A feature if one was under the event.
     */
    getFeatureFromEvent: function(evt) {
        if (!this.renderer) {
            OpenLayers.Console.error(OpenLayers.i18n("getFeatureError")); 
            return null;
        }    
        var featureId = this.renderer.getFeatureIdFromEvent(evt);
        return this.getFeatureById(featureId);
    },
    
    /**
     * APIMethod: getFeatureById
     * Given a feature id, return the feature if it exists in the features array
     *
     * Parameters:
     * featureId - {String} 
     *
     * Returns:
     * {<OpenLayers.Feature.Vector>} A feature corresponding to the given
     * featureId
     */
    getFeatureById: function(featureId) {
        //TBD - would it be more efficient to use a hash for this.features?
        var feature = null;
        for(var i=0, len=this.features.length; i<len; ++i) {
            if(this.features[i].id == featureId) {
                feature = this.features[i];
                break;
            }
        }
        return feature;
    },
    
    /**
     * Unselect the selected features
     * i.e. clears the featureSelection array
     * change the style back
    clearSelection: function() {

       var vectorLayer = this.map.vectorLayer;
        for (var i = 0; i < this.map.featureSelection.length; i++) {
            var featureSelection = this.map.featureSelection[i];
            vectorLayer.drawFeature(featureSelection, vectorLayer.style);
        }
        this.map.featureSelection = [];
    },
     */


    /**
     * APIMethod: onFeatureInsert
     * method called after a feature is inserted.
     * Does nothing by default. Override this if you
     * need to do something on feature updates.
     *
     * Paarameters: 
     * feature - {<OpenLayers.Feature.Vector>} 
     */
    onFeatureInsert: function(feature) {
    },
    
    /**
     * APIMethod: preFeatureInsert
     * method called before a feature is inserted.
     * Does nothing by default. Override this if you
     * need to do something when features are first added to the
     * layer, but before they are drawn, such as adjust the style.
     *
     * Parameters:
     * feature - {<OpenLayers.Feature.Vector>} 
     */
    preFeatureInsert: function(feature) {
    },

    /** 
     * APIMethod: getDataExtent
     * Calculates the max extent which includes all of the features.
     * 
     * Returns:
     * {<OpenLayers.Bounds>}
     */
    getDataExtent: function () {
        var maxExtent = null;
        var features = this.features;
        if(features && (features.length > 0)) {
            maxExtent = new OpenLayers.Bounds();
            var geometry = null;
            for(var i=0, len=features.length; i<len; i++) {
                geometry = features[i].geometry;
                if (geometry) {
                    maxExtent.extend(geometry.getBounds());
                }
            }
        }
        return maxExtent;
    },

    CLASS_NAME: "OpenLayers.Layer.Vector"
});
/* ======================================================================
    OpenLayers/Layer/Vector/RootContainer.js
   ====================================================================== */

/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */

/**
 * @requires OpenLayers/Layer/Vector.js
 */

/**
 * Class: OpenLayers.Layer.Vector.RootContainer
 * A special layer type to combine multiple vector layers inside a single
 *     renderer root container. This class is not supposed to be instantiated
 *     from user space, it is a helper class for controls that require event
 *     processing for multiple vector layers.
 *
 * Inherits from:
 *  - <OpenLayers.Layer.Vector>
 */
OpenLayers.Layer.Vector.RootContainer = OpenLayers.Class(OpenLayers.Layer.Vector, {
    
    /**
     * Property: displayInLayerSwitcher
     * Set to false for this layer type
     */
    displayInLayerSwitcher: false,
    
    /**
     * APIProperty: layers
     * Layers that are attached to this container. Required config option.
     */
    layers: null,
    
    /**
     * Constructor: OpenLayers.Layer.Vector.RootContainer
     * Create a new root container for multiple vector layer. This constructor
     * is not supposed to be used from user space, it is only to be used by
     * controls that need feature selection across multiple vector layers.
     *
     * Parameters:
     * name - {String} A name for the layer
     * options - {Object} Optional object with non-default properties to set on
     *           the layer.
     * 
     * Required options properties:
     * layers - {Array(<OpenLayers.Layer.Vector>)} The layers managed by this
     *     container
     *
     * Returns:
     * {<OpenLayers.Layer.Vector.RootContainer>} A new vector layer root
     *     container
     */
    initialize: function(name, options) {
        OpenLayers.Layer.Vector.prototype.initialize.apply(this, arguments);
    },
    
    /**
     * Method: display
     */
    display: function() {},
    
    /**
     * Method: getFeatureFromEvent
     * walk through the layers to find the feature returned by the event
     * 
     * Parameters:
     * evt - {Object} event object with a feature property
     * 
     * Returns:
     * {<OpenLayers.Feature.Vector>}
     */
    getFeatureFromEvent: function(evt) {
        var layers = this.layers;
        var feature;
        for(var i=0; i<layers.length; i++) {
            feature = layers[i].getFeatureFromEvent(evt);
            if(feature) {
                return feature;
            }
        }
    },
    
    /**
     * Method: setMap
     * 
     * Parameters:
     * map - {<OpenLayers.Map>}
     */
    setMap: function(map) {
        OpenLayers.Layer.Vector.prototype.setMap.apply(this, arguments);
        this.collectRoots();
        map.events.register("changelayer", this, this.handleChangeLayer);
    },
    
    /**
     * Method: removeMap
     * 
     * Parameters:
     * map - {<OpenLayers.Map>}
     */
    removeMap: function(map) {
        map.events.unregister("changelayer", this, this.handleChangeLayer);
        this.resetRoots();
        OpenLayers.Layer.Vector.prototype.removeMap.apply(this, arguments);
    },
    
    /**
     * Method: collectRoots
     * Collects the root nodes of all layers this control is configured with
     * and moveswien the nodes to this control's layer
     */
    collectRoots: function() {
        var layer;
        // walk through all map layers, because we want to keep the order
        for(var i=0; i<this.map.layers.length; ++i) {
            layer = this.map.layers[i];
            if(OpenLayers.Util.indexOf(this.layers, layer) != -1) {
                layer.renderer.moveRoot(this.renderer);
            }
        }
    },
    
    /**
     * Method: resetRoots
     * Resets the root nodes back into the layers they belong to.
     */
    resetRoots: function() {
        var layer;
        for(var i=0; i<this.layers.length; ++i) {
            layer = this.layers[i];
            if(this.renderer && layer.renderer.getRenderLayerId() == this.id) {
                this.renderer.moveRoot(layer.renderer);
            }
        }
    },
    
    /**
     * Method: handleChangeLayer
     * Event handler for the map's changelayer event. We need to rebuild
     * this container's layer dom if order of one of its layers changes.
     * This handler is added with the setMap method, and removed with the
     * removeMap method.
     * 
     * Parameters:
     * evt - {Object}
     */
    handleChangeLayer: function(evt) {
        var layer = evt.layer;
        if(evt.property == "order" &&
                        OpenLayers.Util.indexOf(this.layers, layer) != -1) {
            this.resetRoots();
            this.collectRoots();
        }
    },

    CLASS_NAME: "OpenLayers.Layer.Vector.RootContainer"
});
