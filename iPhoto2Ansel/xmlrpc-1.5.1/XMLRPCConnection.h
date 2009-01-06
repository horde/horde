// 
// Copyright 2008 Eric Czarny <eczarny@gmail.com>
// 
// Permission is hereby granted, free of charge, to any person obtaining a copy
// of  this  software  and  associated documentation files (the "Software"), to
// deal  in  the Software without restriction, including without limitation the
// rights  to  use,  copy,  modify,  merge,  publish,  distribute,  sublicense,
// and/or sell copies  of  the  Software,  and  to  permit  persons to whom the
// Software is furnished to do so, subject to the following conditions:
// 
// The  above  copyright notice and this permission notice shall be included in
// all copies or substantial portions of the Software.
// 
// THE  SOFTWARE  IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
// IMPLIED,  INCLUDING  BUT  NOT  LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS  FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
// AUTHORS  OR  COPYRIGHT  HOLDERS  BE  LIABLE  FOR ANY CLAIM, DAMAGES OR OTHER
// LIABILITY,  WHETHER  IN  AN  ACTION  OF CONTRACT, TORT OR OTHERWISE, ARISING
// FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
// IN THE SOFTWARE.
// 

// 
// Cocoa XML-RPC Framework
// XMLRPCConnection.h
// 
// Created by Eric Czarny on Thursday, January 15, 2004.
// Copyright 2008 Divisible by Zero.
// 
 
#import <Foundation/Foundation.h>

@class XMLRPCRequest, XMLRPCResponse;

/* XML-RPC Connection Notifications */
extern NSString *XMLRPCSentRequestNotification;
extern NSString *XMLRPCRequestFailedNotification;
extern NSString *XMLRPCReceivedAuthenticationChallengeNotification;
extern NSString *XMLRPCCancelledAuthenticationChallengeNotification;
extern NSString *XMLRPCReceivedResponseNotification;

@interface XMLRPCConnection : NSObject {
	NSURLConnection *currentConnection;
	NSString *currentXMLRPCMethod;
	NSMutableData *incomingXMLData;
	id applicationDelegate;
}

- (id)initWithXMLRPCRequest: (XMLRPCRequest *)request delegate: (id)delegate;

#pragma mark -

+ (XMLRPCResponse *)sendSynchronousXMLRPCRequest: (XMLRPCRequest *)request;

#pragma mark -

- (void)cancel;

@end

#pragma mark -

@interface NSObject (XMLRPCConnectionDelegate)

- (void)connection: (XMLRPCConnection *)connection didReceiveResponse: (XMLRPCResponse *)response forMethod: (NSString *)method;

- (void)connection: (XMLRPCConnection *)connection didFailWithError: (NSError *)error forMethod: (NSString *)method;

- (void)connection: (XMLRPCConnection *)connection didReceiveAuthenticationChallenge: (NSURLAuthenticationChallenge *)challenge forMethod: (NSString *)method;

- (void)connection: (XMLRPCConnection *)connection didCancelAuthenticationChallenge: (NSURLAuthenticationChallenge *)challenge forMethod: (NSString *)method;

@end
