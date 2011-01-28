/**
 * KeychainPlugIn.h
 *
 * Dashboard Widget plug-in that stores and retrieves passwords using Keychain.
 *
 * Copyright 2003-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://opensource.org/licenses/lgpl-license.php
 */

#import <Cocoa/Cocoa.h>
#import <WebKit/WebKit.h>
#include <Security/Security.h>

@interface KeychainPlugIn : NSObject {
}

- (BOOL) changePassword: (SecKeychainItemRef) itemRef to: (NSString *) password;

// JavaScript-ready methods
- (NSString *) web_getPassword: (NSString *) username serverName: (NSString *) serverName serverPath: (NSString *) serverPath itemReference: (SecKeychainItemRef *) itemRef;
- (BOOL) web_addPassword: (NSString *) password forUser: (NSString *) username serverName: (NSString *) serverName serverPath: (NSString *) serverPath;

@end
