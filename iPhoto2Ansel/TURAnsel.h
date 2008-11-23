//
//  TURAnsel.h
//  myXMLTest
//
//  Created by Michael Rubinsky on 10/31/08.
//  Copyright 2008 __MyCompanyName__. All rights reserved.
//
#import <Cocoa/Cocoa.h>
@class TURAnselGallery, XMLRPCResponse;

typedef enum {
    PERMS_SHOW = 2,
    PERMS_READ = 4,
    PERMS_EDIT = 8,
    PERMS_DELETE = 16
} HORDE_PERMS;

typedef enum {
    TURAnselStateDisconnected = 0,
    TURAnselStateConnected,
    TURAnselStateError
} TURAnselState;


@interface NSObject (TURAnselDelegate)
- (void)TURAnselDidInitialize;
- (void)TURAnselHadError: (NSError *)error;
@end

@interface TURAnsel : NSObject {
    NSString *userAgent;
    NSString *rpcEndPoint;
    NSString *username;
    NSString *password;
    NSMutableArray *galleryList;
    TURAnselState state;
    id delegate;
    NSLock *lock;
}

@property (readwrite, retain) NSString *rpcEndPoint;
@property (readwrite, retain) NSString *username;
@property (readwrite, retain) NSString *password;

- (id)initWithConnectionParameters: (NSDictionary *)params;
- (void)connect;
- (TURAnselGallery *)getGalleryById: (NSString *)galleryId;
- (TURAnselGallery *)getGalleryByIndex: (NSInteger)index;
- (XMLRPCResponse *)callRPCMethod: (NSString *)methodName withParams: (NSArray *)params;
- (NSDictionary *)createNewGallery: (NSDictionary *)params;

// Getters/setters
- (void) setState: (TURAnselState)state;
- (TURAnselState)state;
- (id)delegate;
- (void)setDelegate:(id)newDelegate;
@end
