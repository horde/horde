//
//  TURXMLConnection.m
//  iPhoto2Ansel
//
//  Created by Michael Rubinsky on 11/5/08.
//  Copyright 2008 __MyCompanyName__. All rights reserved.
//
#import <Foundation/Foundation.h>
#import <XMLRPC/XMLRPC.h>
#import "TURXMLConnection.h"

@implementation TURXMLConnection

static NSString *ERR_DOMAIN = @"com.theupstairsroom.XMLConnection";

- (TURXMLConnection *)initWithXMLRPCRequest: (XMLRPCRequest *)request 
                            withCredentials: (NSDictionary *)credentials
{
    username = [[credentials objectForKey:@"username"] retain];
    password = [[credentials objectForKey:@"password"] retain];
    running = YES;
    connection = [[XMLRPCConnection alloc] initWithXMLRPCRequest: request
                                                        delegate: self];
    return self;
}

- (BOOL)isRunning
{
    return running;
}

- (id)response
{
    return response;
}

- (void)dealloc
{
    NSLog(@"TURXMLConnection dealloc called");
    [username release];
    [password release];
    [super dealloc];
}

- (BOOL)hasError
{
    return hasError;
}

// Return the error object, but get rid of it.
- (NSError *)error
{
    return [error autorelease];
}

#pragma mark XMLRPCConnection Delegate
- (void)connection: (XMLRPCConnection *)xconnection 
didReceiveResponse: (XMLRPCResponse *)theResponse 
         forMethod: (NSString *)method
{    
    NSLog(@"Received response for %@", method);
    if (theResponse != nil) {
        if ([theResponse isFault]) {
            NSLog(@"Fault code: %@", [theResponse faultCode]);
            NSDictionary *userInfo = [NSDictionary dictionaryWithObjectsAndKeys:
                                      [theResponse faultString], @"NSLocalizedDescriptionKey", nil];
            error = [[NSError alloc] initWithDomain: ERR_DOMAIN
                                               code: [[theResponse faultCode] intValue]
                                           userInfo: userInfo];
            hasError = YES;
            [theResponse release];
            [xconnection cancel];
        }
    } else {
        NSDictionary *userInfo = [NSDictionary dictionaryWithObjectsAndKeys:
                                  @"Unable to parse XML in XMLRPCDelegate method", @"NSLocalizedDescriptionKey", nil];
        error = [[NSError alloc] initWithDomain: ERR_DOMAIN
                                           code: TURXML_ERR_PARSE
                                       userInfo: userInfo];
        hasError = YES;
        [theResponse release];
        [xconnection cancel];
    }
    
    response = [[theResponse responseObject] retain];
    [theResponse release];
    [xconnection release];
    running = NO;
}

- (void)connection: (XMLRPCConnection *)xconnection 
didReceiveAuthenticationChallenge: (NSURLAuthenticationChallenge *)challenge
                        forMethod: (NSString *)method
{
    NSLog(@"Credentials requested in method: %@", method);
    if ([challenge previousFailureCount] == 0) {
        NSURLCredential *newCredential;
        newCredential = [NSURLCredential credentialWithUser: [self valueForKey: @"username"]
                                                   password: [self valueForKey: @"password"]
                                                persistence: NSURLCredentialPersistenceForSession];
        
        [[challenge sender] useCredential: newCredential
               forAuthenticationChallenge: challenge];
        NSLog(@"Credentials sent");
    } else {
        [[challenge sender] cancelAuthenticationChallenge: challenge];
        NSDictionary *userInfo = [NSDictionary dictionaryWithObjectsAndKeys:
                                  @"Authentication Failed", @"NSLocalizedDescriptionKey",
                                  @"Check your username and password and try again.", @"NSLocalizedRecoverySuggestionErrorKey", nil];

        error = [[NSError alloc] initWithDomain: ERR_DOMAIN
                                           code: TURXML_ERR_BADAUTH
                                       userInfo: userInfo];
        running = NO;
        hasError = YES;
    }
}

- (void)connection: (XMLRPCConnection *)xconnection
  didFailWithError: (NSError *)xerror
         forMethod: (NSString *)method
{
    error = [xerror retain];
    hasError = YES;
    running = NO;
}


@end
