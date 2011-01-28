/**
 * KeychainPlugIn.m
 *
 * Dashboard Widget plug-in that stores and retrieves passwords using Keychain.
 *
 * Copyright 2003-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://opensource.org/licenses/lgpl-license.php
 */

#import "KeychainPlugIn.h"

@implementation KeychainPlugIn

#pragma mark *** WidgetPlugin methods ***

// initWithWebView is called as the Dashboard widget and its WebView
// are initialized, which is when the widget plug-in is loaded
// This is just an object initializer; DO NOT use the passed WebView
// to manipulate WebScriptObjects or anything in the WebView's hierarchy
- (id) initWithWebView:(WebView*)webView {
    self = [super init];
    return self;
}

#pragma mark *** WebScripting methods ***

// windowScriptObjectAvailable passes the JavaScript window object referring
// to the plug-in's parent window (in this case, the Dashboard widget)
// We use that to register our plug-in as a var of the window object;
// This allows the plug-in to be referenced from JavaScript via
// window.<plugInName>, or just <plugInName>
- (void) windowScriptObjectAvailable:(WebScriptObject*)webScriptObject {
    [webScriptObject setValue:self forKey:@"KeychainPlugIn"];
}

// Prevent direct key access from JavaScript
// Write accessor methods and expose those if necessary
+ (BOOL) isKeyExcludedFromWebScript:(const char*)key {
	return YES;
}

// Used for convenience of WebScripting names below
NSString * const kWebSelectorPrefix = @"web_";

// This is where prefixing our JavaScript methods with web_ pays off:
// instead of a huge if/else trail to decide which methods to exclude,
// just check the selector names for kWebSelectorPrefix
+ (BOOL) isSelectorExcludedFromWebScript:(SEL)aSelector {
    return !([NSStringFromSelector(aSelector) hasPrefix:kWebSelectorPrefix]);
}

// Another simple implementation: take the first token of the Obj-C method signature
// and remove the web_ prefix. So web_getPassword is called from JavaScript as
// KeychainPlugIn.getPassword
+ (NSString *) webScriptNameForSelector:(SEL)aSelector {
    NSString* selName = NSStringFromSelector(aSelector);

    if ([selName hasPrefix:kWebSelectorPrefix] && ([selName length] > [kWebSelectorPrefix length])) {
        return [[[selName substringFromIndex:[kWebSelectorPrefix length]] componentsSeparatedByString: @":"] objectAtIndex: 0];
    }
    return nil;
}

- (BOOL) changePassword: (SecKeychainItemRef) itemRef to: (NSString *) password {
    if (!password || !itemRef) {
        return NO;
    }

    const char *pass = [password cStringUsingEncoding: [NSString defaultCStringEncoding]];

    OSErr status = SecKeychainItemModifyContent(itemRef, nil, strlen(pass), pass);
    if (status == noErr) {
        return YES;
    }

    return NO;
}

#pragma mark *** Web-exposed methods ***

- (NSString *) web_getPassword: (NSString *) username serverName: (NSString *) serverName serverPath: (NSString *) serverPath itemReference: (SecKeychainItemRef *) itemRef {
    const char *host = [serverName UTF8String];
    const char *path = [serverPath UTF8String];
    const char *user = [username UTF8String];
    void *password = NULL;
    UInt32 passwordLength = 0;

    OSStatus findResult = SecKeychainFindInternetPassword(
        NULL, // default keychain
        strlen(host), // server name length
        host, // server name
        0, // security domain length
        NULL, // security domain
        strlen(user), // account name length
        user, // account name
        strlen(path), // path length
        path, // path
        0, // port
        kSecProtocolTypeHTTP, // protocol
        kSecAuthenticationTypeDefault, // authentication type
        &passwordLength, // password length
        &password, // password
        itemRef // item ref
    );

    if (findResult == noErr) {
        NSString *returnString = [NSString stringWithCString: password length: passwordLength];
        SecKeychainItemFreeContent(NULL, password);
        return returnString;
    }

    return nil;
}

- (BOOL) web_addPassword: (NSString *) password forUser: (NSString *) username serverName: (NSString *) serverName serverPath: (NSString *) serverPath {
    const char *host = [serverName UTF8String];
    const char *path = [serverPath UTF8String];
    const char *user = [username UTF8String];
    const char *pass = [password UTF8String];
    SecKeychainItemRef itemRef;

	NSLog(@"%s", host);

    NSString *currentPassword = [self web_getPassword: username serverName: serverName serverPath: serverPath itemReference: &itemRef];
    if (currentPassword) {
        if ([currentPassword isEqualToString: password]) {
            return YES;
        }

        return [self changePassword: itemRef to: password];
    }

    OSStatus addResult = SecKeychainAddInternetPassword(
        NULL, // default keychain
        strlen(host), // server name length
        host, // server name
        0, // security domain length
        NULL, // security domain
        strlen(user), // account name length
        user, // account name
        strlen(path), // path length
        path, // path
        0, // port
        kSecProtocolTypeHTTP, // protocol
        kSecAuthenticationTypeDefault, // authentication type
        strlen(pass), // password length
        pass, // password
        NULL // item ref
    );
	NSLog(@"%d", addResult);

    if (addResult == noErr) {
        return YES;
    }

    return NO;
}

@end
