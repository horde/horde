/**
 * Some example code.
 *
 */
var Foo = {
    doit: function(foo)
    {
        var test, xyz = 1;

        this.callme();
        xyz++;
        test = 'Bar';
        alert(test + foo);
    }
};

Foo.doit("Boo");
