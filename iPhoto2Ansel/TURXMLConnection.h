//
//  TURXMLConnection.h
//
// This is a thin wrapper around XMLRPCRequests to allow them to more easily
// be run in a seperate thread then the reciever that needs to know about it's
// progress.  Done to deal with modal threads blocking the NSURLConnection
// responses in iPhoto plugins.
//
//  Created by Michael Rubinsky on 11/5/08.
//  Copyright 2008 __MyCompanyName__. All rights reserved.
//

#import <Cocoa/Cocoa.h>
#import "XMLRPC/XMLRPCConnection.h"
@class XMLRPCRequest;

// Local error codes
#define TURXML_ERR_BADAUTH  1  // Login failed
#define TURXML_ERR_PARSE    2  // Could not parse XML
#define TURXML_ERR_CANCEL   3  // Action cancelled

@interface TURXMLConnection : XMLRPCConnection {
    NSString *username;
    NSString *password;
    XMLRPCResponse *response;
    XMLRPCConnection *connection;
    BOOL hasError;
    NSError *error;
    BOOL running;
}

- (TURXMLConnection *)initWithXMLRPCRequest: (XMLRPCRequest *)request 
                            withCredentials:(NSDictionary *)credentials;

- (id)response;
- (BOOL)hasError;
- (BOOL)isRunning;
- (NSError *)error;
@end
