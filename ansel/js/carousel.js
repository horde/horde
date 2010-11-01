/*  Prototype-UI, version trunk
 *
 *  Prototype-UI is freely distributable under the terms of an MIT-style license.
 *  For details, see the PrototypeUI web site: http://www.prototype-ui.com/
 *
 *--------------------------------------------------------------------------*/

if(typeof Prototype == 'undefined' )
  throw("Prototype-UI library require Prototype library >= 1.6.0");

if (Prototype.Browser.WebKit) {
  Prototype.Browser.WebKitVersion = parseFloat(navigator.userAgent.match(/AppleWebKit\/([\d\.\+]*)/)[1]);
  Prototype.Browser.Safari2 = (Prototype.Browser.WebKitVersion < 420);
}

if (Prototype.Browser.IE) {
  Prototype.Browser.IEVersion = parseFloat(navigator.appVersion.split(';')[1].strip().split(' ')[1]);
  Prototype.Browser.IE6 =  Prototype.Browser.IEVersion == 6;
  Prototype.Browser.IE7 =  Prototype.Browser.IEVersion == 7;
}

Prototype.falseFunction = function() { return false };
Prototype.trueFunction  = function() { return true  };

/*
Namespace: UI

  Introduction:
    Prototype-UI is a library of user interface components based on the Prototype framework.
    Its aim is to easilly improve user experience in web applications.

    It also provides utilities to help developers.

  Guideline:
    - Prototype conventions are followed
    - Everything should be unobstrusive
    - All components are themable with CSS stylesheets, various themes are provided

  Warning:
    Prototype-UI is still under deep development, this release is targeted to developers only.
    All interfaces are subjects to changes, suggestions are welcome.

    DO NOT use it in production for now.

  Authors:
    - Sébastien Gruhier, <http://www.xilinus.com>
    - Samuel Lebeau, <http://gotfresh.info>
*/

var UI = {
  Abstract: { },
  Ajax: { }
};
Object.extend(Class.Methods, {
  extend: Object.extend.methodize(),

  addMethods: Class.Methods.addMethods.wrap(function(proceed, source) {
    // ensure we are not trying to add null or undefined
    if (!source) return this;

    // no callback, vanilla way
    if (!source.hasOwnProperty('methodsAdded'))
      return proceed(source);

    var callback = source.methodsAdded;
    delete source.methodsAdded;
    proceed(source);
    callback.call(source, this);
    source.methodsAdded = callback;

    return this;
  }),

  addMethod: function(name, lambda) {
    var methods = {};
    methods[name] = lambda;
    return this.addMethods(methods);
  },

  method: function(name) {
    return this.prototype[name].valueOf();
  },

  classMethod: function() {
    $A(arguments).flatten().each(function(method) {
      this[method] = (function() {
        return this[method].apply(this, arguments);
      }).bind(this.prototype);
    }, this);
    return this;
  },

  // prevent any call to this method
  undefMethod: function(name) {
    this.prototype[name] = undefined;
    return this;
  },

  // remove the class' own implementation of this method
  removeMethod: function(name) {
    delete this.prototype[name];
    return this;
  },

  aliasMethod: function(newName, name) {
    this.prototype[newName] = this.prototype[name];
    return this;
  },

  aliasMethodChain: function(target, feature) {
    feature = feature.camelcase();

    this.aliasMethod(target+"Without"+feature, target);
    this.aliasMethod(target, target+"With"+feature);

    return this;
  }
});
Object.extend(Number.prototype, {
  // Snap a number to a grid
  snap: function(round) {
    return parseInt(round == 1 ? this : (this / round).floor() * round);
  }
});
/*
Interface: String

*/

Object.extend(String.prototype, {
  camelcase: function() {
    var string = this.dasherize().camelize();
    return string.charAt(0).toUpperCase() + string.slice(1);
  },

  /*
    Method: makeElement
      toElement is unfortunately already taken :/

      Transforms html string into an extended element or null (when failed)

      > '<li><a href="#">some text</a></li>'.makeElement(); // => LI href#
      > '<img src="foo" id="bar" /><img src="bar" id="bar" />'.makeElement(); // => IMG#foo (first one)

    Returns:
      Extended element

  */
  makeElement: function() {
    var wrapper = new Element('div'); wrapper.innerHTML = this;
    return wrapper.down();
  }
});
Object.extend(Array.prototype, {
  empty: function() {
    return !this.length;
  },

  extractOptions: function() {
    return this.last().constructor === Object ? this.pop() : { };
  },

  removeAt: function(index) {
    var object = this[index];
    this.splice(index, 1);
    return object;
  },

  remove: function(object) {
    var index;
    while ((index = this.indexOf(object)) != -1)
      this.removeAt(index);
    return object;
  },

  insert: function(index) {
    var args = $A(arguments);
    args.shift();
    this.splice.apply(this, [ index, 0 ].concat(args));
    return this;
  }
});
Element.addMethods({
  getScrollDimensions: function(element) {
    return {
      width:  element.scrollWidth,
      height: element.scrollHeight
    }
  },

  getScrollOffset: function(element) {
    return Element._returnOffset(element.scrollLeft, element.scrollTop);
  },

  setScrollOffset: function(element, offset) {
    element = $(element);
    if (arguments.length == 3)
      offset = { left: offset, top: arguments[2] };
    element.scrollLeft = offset.left;
    element.scrollTop  = offset.top;
    return element;
  },

  // returns "clean" numerical style (without "px") or null if style can not be resolved
  // or is not numeric
  getNumStyle: function(element, style) {
    var value = parseFloat($(element).getStyle(style));
    return isNaN(value) ? null : value;
  },

  // by Tobie Langel (http://tobielangel.com/2007/5/22/prototype-quick-tip)
  appendText: function(element, text) {
    element = $(element);
    text = String.interpret(text);
    element.appendChild(document.createTextNode(text));
    return element;
  }
});

document.whenReady = function(callback) {
  if (document.loaded)
    callback.call(document);
  else
    document.observe('dom:loaded', callback);
};

Object.extend(document.viewport, {
  // Alias this method for consistency
  getScrollOffset: document.viewport.getScrollOffsets,

  setScrollOffset: function(offset) {
    Element.setScrollOffset(Prototype.Browser.WebKit ? document.body : document.documentElement, offset);
  },

  getScrollDimensions: function() {
    return Element.getScrollDimensions(Prototype.Browser.WebKit ? document.body : document.documentElement);
  }
});
/*
Interface: UI.Options
  Mixin to handle *options* argument in initializer pattern.

  TODO: find a better example than Circle that use an imaginary Point function,
        this example should be used in tests too.

  It assumes class defines a property called *options*, containing
  default options values.

  Instances hold their own *options* property after a first call to <setOptions>.

  Example:
    > var Circle = Class.create(UI.Options, {
    >
    >   // default options
    >   options: {
    >     radius: 1,
    >     origin: Point(0, 0)
    >   },
    >
    >   // common usage is to call setOptions in initializer
    >   initialize: function(options) {
    >     this.setOptions(options);
    >   }
    > });
    >
    > var circle = new Circle({ origin: Point(1, 4) });
    >
    > circle.options
    > // => { radius: 1, origin: Point(1,4) }

  Accessors:
    There are builtin methods to automatically write options accessors. All those
    methods can take either an array of option names nor option names as arguments.
    Notice that those methods won't override an accessor method if already present.

     * <optionsGetter> creates getters
     * <optionsSetter> creates setters
     * <optionsAccessor> creates both getters and setters

    Common usage is to invoke them on a class to create accessors for all instances
    of this class.
    Invoking those methods on a class has the same effect as invoking them on the class prototype.
    See <classMethod> for more details.

    Example:
    > // Creates getter and setter for the "radius" options of circles
    > Circle.optionsAccessor('radius');
    >
    > circle.setRadius(4);
    > // 4
    >
    > circle.getRadius();
    > // => 4 (circle.options.radius)

  Inheritance support:
    Subclasses can refine default *options* values, after a first instance call on setOptions,
    *options* attribute will hold all default options values coming from the inheritance hierarchy.
*/

(function() {
  UI.Options = {
    methodsAdded: function(klass) {
      klass.classMethod($w(' setOptions allOptions optionsGetter optionsSetter optionsAccessor '));
    },

    // Group: Methods

    /*
      Method: setOptions
        Extends object's *options* property with the given object
    */
    setOptions: function(options) {
      if (!this.hasOwnProperty('options'))
        this.options = this.allOptions();

      this.options = Object.extend(this.options, options || {});
    },

    /*
      Method: allOptions
        Computes the complete default options hash made by reverse extending all superclasses
        default options.

        > Widget.prototype.allOptions();
    */
    allOptions: function() {
      var superclass = this.constructor.superclass, ancestor = superclass && superclass.prototype;
      return (ancestor && ancestor.allOptions) ?
          Object.extend(ancestor.allOptions(), this.options) :
          Object.clone(this.options);
    },

    /*
      Method: optionsGetter
        Creates default getters for option names given as arguments.
        With no argument, creates getters for all option names.
    */
    optionsGetter: function() {
      addOptionsAccessors(this, arguments, false);
    },

    /*
      Method: optionsSetter
        Creates default setters for option names given as arguments.
        With no argument, creates setters for all option names.
    */
    optionsSetter: function() {
      addOptionsAccessors(this, arguments, true);
    },

    /*
      Method: optionsAccessor
        Creates default getters/setters for option names given as arguments.
        With no argument, creates accessors for all option names.
    */
    optionsAccessor: function() {
      this.optionsGetter.apply(this, arguments);
      this.optionsSetter.apply(this, arguments);
    }
  };

  // Internal
  function addOptionsAccessors(receiver, names, areSetters) {
    names = $A(names).flatten();

    if (names.empty())
      names = Object.keys(receiver.allOptions());

    names.each(function(name) {
      var accessorName = (areSetters ? 'set' : 'get') + name.camelcase();

      receiver[accessorName] = receiver[accessorName] || (areSetters ?
        // Setter
        function(value) { return this.options[name] = value } :
        // Getter
        function()      { return this.options[name]         });
    });
  }
})();
/*
  Class: UI.Carousel

  Main class to handle a carousel of elements in a page. A carousel :
    * could be vertical or horizontal
    * works with liquid layout
    * is designed by CSS

  Assumptions:
    * Elements should be from the same size

  Example:
    > ...
    > <div id="horizontal_carousel">
    >   <div class="previous_button"></div>
    >   <div class="container">
    >     <ul>
    >       <li> What ever you like</li>
    >     </ul>
    >   </div>
    >   <div class="next_button"></div>
    > </div>
    > <script>
    > new UI.Carousel("horizontal_carousel");
    > </script>
    > ...
*/
UI.Carousel = Class.create(UI.Options, {
  // Group: Options
  options: {
    // Property: direction
    //   Can be horizontal or vertical, horizontal by default
    direction               : "horizontal",

    // Property: previousButton
    //   Selector of previous button inside carousel element, ".previous_button" by default,
    //   set it to false to ignore previous button
    previousButton          : ".previous_button",

    // Property: nextButton
    //   Selector of next button inside carousel element, ".next_button" by default,
    //   set it to false to ignore next button
    nextButton              : ".next_button",

    // Property: container
    //   Selector of carousel container inside carousel element, ".container" by default,
    container               : ".container",

    // Property: scrollInc
    //   Define the maximum number of elements that gonna scroll each time, auto by default
    scrollInc               : "auto",

    // Property: disabledButtonSuffix
    //   Define the suffix classanme used when a button get disabled, to '_disabled' by default
    //   Previous button classname will be previous_button_disabled
    disabledButtonSuffix : '_disabled',

    // Property: overButtonSuffix
    //   Define the suffix classanme used when a button has a rollover status, '_over' by default
    //   Previous button classname will be previous_button_over
    overButtonSuffix : '_over'
  },

  /*
    Group: Attributes

      Property: element
        DOM element containing the carousel

      Property: id
        DOM id of the carousel's element

      Property: container
        DOM element containing the carousel's elements

      Property: elements
        Array containing the carousel's elements as DOM elements

      Property: previousButton
        DOM id of the previous button

      Property: nextButton
        DOM id of the next button

      Property: posAttribute
        Define if the positions are from left or top

      Property: dimAttribute
        Define if the dimensions are horizontal or vertical

      Property: elementSize
        Size of each element, it's an integer

      Property: nbVisible
        Number of visible elements, it's a float

      Property: animating
        Define whether the carousel is in animation or not
  */

  /*
    Group: Events
      List of events fired by a carousel

      Notice: Carousel custom events are automatically namespaced in "carousel:" (see Prototype custom events).

      Examples:
        This example will observe all carousels
        > document.observe('carousel:scroll:ended', function(event) {
        >   alert("Carousel with id " + event.memo.carousel.id + " has just been scrolled");
        > });

        This example will observe only this carousel
        > new UI.Carousel('horizontal_carousel').observe('scroll:ended', function(event) {
        >   alert("Carousel with id " + event.memo.carousel.id + " has just been scrolled");
        > });

      Property: previousButton:enabled
        Fired when the previous button has just been enabled

      Property: previousButton:disabled
        Fired when the previous button has just been disabled

      Property: nextButton:enabled
        Fired when the next button has just been enabled

      Property: nextButton:disabled
        Fired when the next button has just been disabled

      Property: scroll:started
        Fired when a scroll has just started

      Property: scroll:ended
        Fired when a scroll has been done,
        memo.shift = number of elements scrolled, it's a float

      Property: sizeUpdated
        Fired when the carousel size has just been updated.
        Tips: memo.carousel.currentSize() = the new carousel size
  */

  // Group: Constructor

  /*
    Method: initialize
      Constructor function, should not be called directly

    Parameters:
      element - DOM element
      options - (Hash) list of optional parameters

    Returns:
      this
  */
  initialize: function(element, options) {
    this.setOptions(options);
    this.element = $(element);
    this.id = this.element.id;
    this.container   = this.element.down(this.options.container).firstDescendant();
    this.elements    = this.container.childElements();
    this.previousButton = this.options.previousButton == false ? null : this.element.down(this.options.previousButton);
    this.nextButton = this.options.nextButton == false ? null : this.element.down(this.options.nextButton);

    this.posAttribute = (this.options.direction == "horizontal" ? "left" : "top");
    this.dimAttribute = (this.options.direction == "horizontal" ? "width" : "height");

    this.elementSize = this.computeElementSize();
    this.nbVisible = this.currentSize() / this.elementSize;

    var scrollInc = this.options.scrollInc;
    if (scrollInc == "auto")
      scrollInc = Math.floor(this.nbVisible);
    [ this.previousButton, this.nextButton ].each(function(button) {
      if (!button) return;
      var className = (button == this.nextButton ? "next_button" : "previous_button") + this.options.overButtonSuffix;
      button.clickHandler = this.scroll.bind(this, (button == this.nextButton ? -1 : 1) * scrollInc * this.elementSize);
      button.observe("click", button.clickHandler)
            .observe("mouseover", function() {button.addClassName(className)}.bind(this))
            .observe("mouseout",  function() {button.removeClassName(className)}.bind(this));
    }, this);
    this.updateButtons();
  },

  // Group: Destructor

  /*
    Method: destroy
      Cleans up DOM and memory
  */
  destroy: function($super) {
    [ this.previousButton, this.nextButton ].each(function(button) {
      if (!button) return;
        button.stopObserving("click", button.clickHandler);
    }, this);
      this.element.remove();
      this.fire('destroyed');
  },

  // Group: Event handling

  /*
    Method: fire
      Fires a carousel custom event automatically namespaced in "carousel:" (see Prototype custom events).
      The memo object contains a "carousel" property referring to the carousel.

    Example:
      > document.observe('carousel:scroll:ended', function(event) {
      >   alert("Carousel with id " + event.memo.carousel.id + " has just been scrolled");
      > });

    Parameters:
      eventName - an event name
      memo      - a memo object

    Returns:
      fired event
  */
  fire: function(eventName, memo) {
    memo = memo || { };
    memo.carousel = this;
    return this.element.fire('carousel:' + eventName, memo);
  },

  /*
    Method: observe
      Observe a carousel event with a handler function automatically bound to the carousel

    Parameters:
      eventName - an event name
      handler   - a handler function

    Returns:
      this
  */
  observe: function(eventName, handler) {
    this.element.observe('carousel:' + eventName, handler.bind(this));
    return this;
  },

  /*
    Method: stopObserving
      Unregisters a carousel event, it must take the same parameters as this.observe (see Prototype stopObserving).

    Parameters:
      eventName - an event name
      handler   - a handler function

    Returns:
      this
  */
  stopObserving: function(eventName, handler) {
      this.element.stopObserving('carousel:' + eventName, handler);
      return this;
  },

  // Group: Actions

  /*
    Method: checkScroll
      Check scroll position to avoid unused space at right or bottom

    Parameters:
      position       - position to check
      updatePosition - should the container position be updated ? true/false

    Returns:
      position
  */
  checkScroll: function(position, updatePosition) {
    if (position > 0)
      position = 0;
    else {
      var limit = this.elements.last().positionedOffset()[this.posAttribute] + this.elementSize;
      var carouselSize = this.currentSize();

      if (position + limit < carouselSize)
        position += carouselSize - (position + limit);
      position = Math.min(position, 0);
    }
    if (updatePosition)
      this.container.style[this.posAttribute] = position + "px";

    return position;
  },

  /*
    Method: scroll
      Scrolls carousel from maximum deltaPixel

    Parameters:
      deltaPixel - a float

    Returns:
      this
  */
  scroll: function(deltaPixel) {
    if (this.animating)
      return this;

    // Compute new position
    var position =  this.currentPosition() + deltaPixel;

    // Check bounds
    position = this.checkScroll(position, false);

    // Compute shift to apply
    deltaPixel = position - this.currentPosition();
    if (deltaPixel != 0) {
      this.animating = true;
      this.fire("scroll:started");

      var that = this;
      // Move effects
      this.container.morph("opacity:0.5", {duration: 0.2, afterFinish: function() {
        that.container.morph(that.posAttribute + ": " + position + "px", {
          duration: 0.4,
          delay: 0.2,
          afterFinish: function() {
            that.container.morph("opacity:1", {
              duration: 0.2,
              afterFinish: function() {
                that.animating = false;
                that.updateButtons()
                  .fire("scroll:ended", { shift: deltaPixel / that.currentSize() });
              }
            });
          }
        });
      }});
    }
    return this;
  },

  /*
    Method: scrollTo
      Scrolls carousel, so that element with specified index is the left-most.
      This method is convenient when using carousel in a tabbed navigation.
      Clicking on first tab should scroll first container into view, clicking on a fifth - fifth one, etc.
      Indexing starts with 0.

    Parameters:
      Index of an element which will be a left-most visible in the carousel

    Returns:
      this
  */
  scrollTo: function(index) {
    if (this.animating || index < 0 || index > this.elements.length || index == this.currentIndex() || isNaN(parseInt(index)))
      return this;
    return this.scroll((this.currentIndex() - index) * this.elementSize);
  },

  /*
    Method: updateButtons
      Update buttons status to enabled or disabled
      Them status is defined by classNames and fired as carousel's custom events

    Returns:
      this
  */
  updateButtons: function() {
      this.updatePreviousButton();
    this.updateNextButton();
    return this;
  },

  updatePreviousButton: function() {
    var position = this.currentPosition();
    var previousClassName = "previous_button" + this.options.disabledButtonSuffix;

    if (this.previousButton.hasClassName(previousClassName) && position != 0) {
      this.previousButton.removeClassName(previousClassName);
      this.fire('previousButton:enabled');
    }
    if (!this.previousButton.hasClassName(previousClassName) && position == 0) {
        this.previousButton.addClassName(previousClassName);
      this.fire('previousButton:disabled');
    }
  },

  updateNextButton: function() {
    var lastPosition = this.currentLastPosition();
    var size = this.currentSize();
    var nextClassName = "next_button" + this.options.disabledButtonSuffix;

    if (this.nextButton.hasClassName(nextClassName) && lastPosition != size) {
      this.nextButton.removeClassName(nextClassName);
      this.fire('nextButton:enabled');
    }
    if (!this.nextButton.hasClassName(nextClassName) && lastPosition == size) {
        this.nextButton.addClassName(nextClassName);
      this.fire('nextButton:disabled');
    }
  },

  // Group: Size and Position

  /*
    Method: computeElementSize
      Return elements size in pixel, height or width depends on carousel orientation.

    Returns:
      an integer value
  */
  computeElementSize: function() {
    return this.elements.first().getDimensions()[this.dimAttribute];
  },

  /*
    Method: currentIndex
      Returns current visible index of a carousel.
      For example, a horizontal carousel with image #3 on left will return 3 and with half of image #3 will return 3.5
      Don't forget that the first image have an index 0

    Returns:
      a float value
  */
  currentIndex: function() {
    return - this.currentPosition() / this.elementSize;
  },

  /*
    Method: currentLastPosition
      Returns the current position from the end of the last element. This value is in pixel.

    Returns:
      an integer value, if no images a present it will return 0
  */
  currentLastPosition: function() {
    if (this.container.childElements().empty())
      return 0;
    return this.currentPosition() +
           this.elements.last().positionedOffset()[this.posAttribute] +
           this.elementSize;
  },

  /*
    Method: currentPosition
      Returns the current position in pixel.
      Tips: To get the position in elements use currentIndex()

    Returns:
      an integer value
  */
  currentPosition: function() {
    return this.container.getNumStyle(this.posAttribute);
  },

  /*
    Method: currentSize
      Returns the current size of the carousel in pixel

    Returns:
      Carousel's size in pixel
  */
  currentSize: function() {
    return this.container.parentNode.getDimensions()[this.dimAttribute];
  },

  /*
    Method: updateSize
      Should be called if carousel size has been changed (usually called with a liquid layout)

    Returns:
      this
  */
  updateSize: function() {
    this.nbVisible = this.currentSize() / this.elementSize;
    var scrollInc = this.options.scrollInc;
    if (scrollInc == "auto")
      scrollInc = Math.floor(this.nbVisible);

    [ this.previousButton, this.nextButton ].each(function(button) {
      if (!button) return;
      button.stopObserving("click", button.clickHandler);
      button.clickHandler = this.scroll.bind(this, (button == this.nextButton ? -1 : 1) * scrollInc * this.elementSize);
      button.observe("click", button.clickHandler);
    }, this);

    this.checkScroll(this.currentPosition(), true);
    this.updateButtons().fire('sizeUpdated');
    return this;
  }
});
/*
  Class: UI.Ajax.Carousel

  Gives the AJAX power to carousels. An AJAX carousel :
    * Use AJAX to add new elements on the fly

  Example:
    > new UI.Ajax.Carousel("horizontal_carousel",
    >   {url: "get-more-elements", elementSize: 250});
*/
UI.Ajax.Carousel = Class.create(UI.Carousel, {
  // Group: Options
  //
  //   Notice:
  //     It also include of all carousel's options
  options: {
    // Property: elementSize
    //   Required, it define the size of all elements
    elementSize : -1,

    // Property: url
    //   Required, it define the URL used by AJAX carousel to request new elements details
    url         : null
  },

  /*
    Group: Attributes

      Notice:
        It also include of all carousel's attributes

      Property: elementSize
        Size of each elements, it's an integer

      Property: endIndex
        Index of the last loaded element

      Property: hasMore
        Flag to define if there's still more elements to load

      Property: requestRunning
        Define whether a request is processing or not

      Property: updateHandler
        Callback to update carousel, usually used after request success

      Property: url
        URL used to request additional elements
  */

  /*
    Group: Events
      List of events fired by an AJAX carousel, it also include of all carousel's custom events

      Property: request:started
        Fired when the request has just started

      Property: request:ended
        Fired when the request has succeed
  */

  // Group: Constructor

  /*
    Method: initialize
      Constructor function, should not be called directly

    Parameters:
      element - DOM element
      options - (Hash) list of optional parameters

    Returns:
      this
  */
  initialize: function($super, element, options) {
    if (!options.url)
      throw("url option is required for UI.Ajax.Carousel");
    if (!options.elementSize)
      throw("elementSize option is required for UI.Ajax.Carousel");

    $super(element, options);

    this.endIndex = 0;
    this.hasMore  = true;

    // Cache handlers
    this.updateHandler = this.update.bind(this);
    this.updateAndScrollHandler = function(nbElements, transport, json) {
        this.update(transport, json);
        this.scroll(nbElements);
      }.bind(this);

    // Run first ajax request to fill the carousel
    this.runRequest.bind(this).defer({parameters: {from: 0, to: Math.floor(this.nbVisible)}, onSuccess: this.updateHandler});
  },

  // Group: Actions

  /*
    Method: runRequest
      Request the new elements details

    Parameters:
      options - (Hash) list of optional parameters

    Returns:
      this
  */
  runRequest: function(options) {
    this.requestRunning = true;
    //alert("Asking for: " + options.parameters.from + " - " + options.parameters.to);
    new Ajax.Request(this.options.url, Object.extend({method: "GET"}, options));
    this.fire("request:started");
    return this;
  },

  /*
    Method: scroll
      Scrolls carousel from maximum deltaPixel

    Parameters:
      deltaPixel - a float

    Returns:
      this
  */
  scroll: function($super, deltaPixel) {
    if (this.animating || this.requestRunning)
      return this;

    var nbElements = (-deltaPixel) / this.elementSize;
    // Check if there is not enough
    if (this.hasMore && nbElements > 0 && this.currentIndex() + this.nbVisible + nbElements - 1 > this.endIndex) {
      var from = this.endIndex + 1;
      var to   = Math.floor(from + this.nbVisible - 1);
      this.runRequest({parameters: {from: from, to: to}, onSuccess: this.updateAndScrollHandler.curry(deltaPixel).bind(this)});
      return this;
    }
    else
      $super(deltaPixel);
  },

  /*
    Method: update
      Update the carousel

    Parameters:
      transport - XMLHttpRequest object
      json      - JSON object

    Returns:
      this
  */
  update: function(transport, json) {
    this.requestRunning = false;
    this.fire("request:ended");
    if (!json)
      json = transport.responseJSON;
    this.hasMore = json.more;

    this.endIndex = Math.max(this.endIndex, json.to);
    this.elements = this.container.insert({bottom: json.html}).childElements();
    return this.updateButtons();
  },

  // Group: Size and Position

  /*
    Method: computeElementSize
      Return elements size in pixel

    Returns:
      an integer value
  */
  computeElementSize: function() {
    return this.options.elementSize;
  },

  /*
    Method: updateSize
      Should be called if carousel size has been changed (usually called with a liquid layout)

    Returns:
      this
  */
  updateSize: function($super) {
    var nbVisible = this.nbVisible;
    $super();
    // If we have enough space for at least a new element
    if (Math.floor(this.nbVisible) - Math.floor(nbVisible) >= 1 && this.hasMore) {
      if (this.currentIndex() + Math.floor(this.nbVisible) >= this.endIndex) {
        var nbNew = Math.floor(this.currentIndex() + Math.floor(this.nbVisible) - this.endIndex);
        this.runRequest({parameters: {from: this.endIndex + 1, to: this.endIndex + nbNew}, onSuccess: this.updateHandler});
      }
    }
    return this;
  },

  updateNextButton: function($super) {
    var lastPosition = this.currentLastPosition();
    var size = this.currentSize();
    var nextClassName = "next_button" + this.options.disabledButtonSuffix;

    if (this.nextButton.hasClassName(nextClassName) && lastPosition != size) {
      this.nextButton.removeClassName(nextClassName);
      this.fire('nextButton:enabled');
    }
    if (!this.nextButton.hasClassName(nextClassName) && lastPosition == size && !this.hasMore) {
        this.nextButton.addClassName(nextClassName);
      this.fire('nextButton:disabled');
    }
  }
});
