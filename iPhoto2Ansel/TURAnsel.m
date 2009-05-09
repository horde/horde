//
//  TURAnsel.m
//  AnselCocoaToolkit
//
//  Created by Michael Rubinsky on 10/31/08.
//  Copyright 2008 Michael Rubinsky <mrubinsk@horde.org>
//

#import <Foundation/Foundation.h>
#import <XMLRPC/XMLRPC.h>
#import "TURXMLConnection.h"
#import "TURAnsel.h"
#import "TURAnselGallery.h"

@interface TURAnsel (PrivateAPI)
- (void)doLogin;
@end

@implementation TURAnsel

@synthesize rpcEndPoint;
@synthesize username;
@synthesize password;

#pragma mark init
- (id)initWithConnectionParameters: (NSDictionary *)params
{
    [super init];
    galleryList = [[NSMutableArray alloc] init];
    
    // Initialize the connection properties, KVC style
    [self setValue:[params objectForKey:@"endpoint"] 
            forKey: @"rpcEndPoint"];
    [self setValue: [params objectForKey:@"username"]
            forKey: @"username"];
    [self setValue: [params objectForKey:@"password"]
            forKey: @"password"];
    [self setValue: @"The Ansel Cocoa XML-RPC Client"
            forKey: @"userAgent"];
    return self;
}

#pragma mark Instance Methods
/**
 * Initial connection to the Ansel server
 * Authenticate and fill our cache with the available galleries for uploading to
 */
- (void)connect 
{
    [self doLogin];
    if (state == TURAnselStateConnected) {
        if ([delegate respondsToSelector:@selector(TURAnselDidInitialize)]) {
            [delegate performSelectorOnMainThread:@selector(TURAnselDidInitialize)
                                       withObject:self
                                    waitUntilDone: NO];
        }
    }
}

- (void) cancel
{
    state = TURAnselStateCancelled;
}

// Fetch a gallery by id
- (TURAnselGallery *)getGalleryById: (NSString *)galleryId
{
    for (TURAnselGallery *g in galleryList) {
        if ([galleryId isEqualTo: [NSNumber numberWithInt: [g galleryId]]]) {
            return g;
        }
    }
    
    return nil;
}

// Return the gallery at the specified position in the internal storage array.
// Needed for when we are using this class as a datasource for a UI element.
- (TURAnselGallery *)getGalleryByIndex: (NSInteger)index
{
    TURAnselGallery *g = [galleryList objectAtIndex:index];
    return g;
}

// Creates a new gallery
// For now, only takes a gallery name, but no reason it can't take descriiption
// and default perms etc...
- (NSDictionary *)createNewGallery: (NSDictionary *)params
{
    NSArray *apiparams = [NSArray arrayWithObjects:
                             @"ansel", params, nil]; 
    XMLRPCResponse *response = [self callRPCMethod: @"images.createGallery"
                                        withParams: apiparams];
    if (state != TURAnselStateError) {
        NSDictionary *results = [NSDictionary dictionaryWithObjectsAndKeys:
                                    response, @"share_id",
                                    [params valueForKey: @"name"], @"attribute_name",
                                    @"", @"attribute_desc",
                                    [NSNumber numberWithInt: 0], @"attribute_images",
                                    [NSNumber numberWithInt: 0], @"attribute_default", nil];
        TURAnselGallery *newGallery = [[TURAnselGallery alloc] initWithObject: results
                                                                   controller: self];
        [galleryList addObject: newGallery];
        //[attributes release];
        return results;
    }
    
    // If we have an error, tell the world
    // *really* need to give these errors real numbers.....
    if ([delegate respondsToSelector:@selector(TURAnselHadError:)]) {
        NSError *error = [NSError errorWithDomain:@"TURAnsel"
                                             code:3
                                         userInfo:[NSDictionary dictionaryWithObjectsAndKeys: @"Could not create gallery.", @"message", nil]];
        [delegate TURAnselHadError:error];
    }

    return nil;
}


// Call an arbitrary RPC method on the Horde server.
- (XMLRPCResponse *)callRPCMethod: (NSString *)methodName
                       withParams: (NSArray *)params
{
    NSLog(@"Initiating connection for %@", methodName);
    
    // Get a URL object
    NSURL *url = [NSURL URLWithString: [self valueForKey: @"rpcEndPoint"]];
    XMLRPCRequest *request = [[XMLRPCRequest alloc]initWithHost: url];
    [request setUserAgent: [self valueForKey:@"userAgent"]];
    [request setMethod: methodName withParameters: params];
    
    NSDictionary *credentials = [[NSDictionary alloc] initWithObjectsAndKeys: 
                                 [self valueForKey:@"username"], @"username",
                                 [self valueForKey:@"password"], @"password", nil];
    
    TURXMLConnection *connection = [[TURXMLConnection alloc]
                                    initWithXMLRPCRequest: request 
                                    withCredentials:credentials];

    // Don't move on until we have a response - this blocks the thread until
    // that happens.  We should have some kind of timeout here...
    while ([connection isRunning]) {        
        if (state == TURAnselStateCancelled) {
            [connection cancel];
        }
        NSDate *loopDate = [[NSDate alloc] initWithTimeIntervalSinceNow:0.1];
        [[NSRunLoop currentRunLoop] runMode:NSDefaultRunLoopMode
                                 beforeDate:loopDate];
        [loopDate release];
    }
    
    if ([connection hasError] == NO) {
        XMLRPCResponse *response = [[connection response] autorelease];
        [credentials release];
        [connection release];
        return response;
    } else {
        state = TURAnselStateError;
        NSError *error = [[connection error] retain];
        if ([delegate respondsToSelector:@selector(TURAnselHadError:)]) {
            [delegate TURAnselHadError:error];
        }
        [error autorelease];
        [connection release];
        return nil;
    }

}

#pragma mark TableView datasource
- (int)numberOfRowsInTableView: (NSTableView *)tv
{
    return [galleryList count];
}

- (id)tableView: (NSTableView *)tv
objectValueForTableColumn:(NSTableColumn *)tc
            row: (int)rowIndex
{
    NSString *identifier = [tc identifier]; 
    TURAnselGallery *g = [galleryList objectAtIndex:rowIndex];
    NSString *stringValue = [g valueForKey: identifier];
    return stringValue;
}

#pragma mark ComboBox Datasource

- (NSInteger)numberOfItemsInComboBox:(NSComboBox *)aComboBox
{
    return [galleryList count];
}

- (id)comboBox:(NSComboBox *)aComboBox
  objectValueForItemAtIndex:(NSInteger)index
{
    TURAnselGallery *g = [galleryList objectAtIndex:index];
    NSString *stringValue = [g valueForKey:@"galleryName"];
    return stringValue;
}

#pragma mark Getter/Setters
- (TURAnselState) state
{
    return state;
}
-(void) setState: (TURAnselState)newstate
{
    state = newstate;
}

- (id)delegate {
    return delegate;    
}

- (void)setDelegate:(id)newDelegate {
    delegate = newDelegate;
}

-(void) dealloc
{
    NSLog(@"TURAnsel dealloc");   
    [galleryList removeAllObjects];
    [galleryList release];
    [rpcEndPoint release];
    [username release];
    [password release];
    [userAgent release];
    [super dealloc];
}

#pragma mark PrivateAPI
- (void)doLogin
{
    // Start out by building an array of parameters to pass to the api call.
    // We start by asking for a list of available galleries with PERMS_EDIT.
    // This has the side effect of authenticating for the session.
    NSArray *params = [[NSArray alloc] initWithObjects:
                       @"ansel",                                 // Scope 
                       [NSNumber numberWithInt: PERMS_EDIT],     // Perms
                       @"",                                      // No parent
                       [NSNumber numberWithBool:YES],            // allLevels
                       [NSNumber numberWithInt: 0],              // Offset
                       [NSNumber numberWithInt: 0],              // Count
                       [self valueForKey:@"username"], nil];     // Restrict to user (This should be an option eventually).do
    

    id galleries = [self callRPCMethod:@"images.listGalleries"
                            withParams:params];
    NSLog(@"%@", galleries);
    if (state != TURAnselStateError) {
        state = TURAnselStateConnected;
        for (NSString *gal in galleries) {
            TURAnselGallery *theGallery = [[TURAnselGallery alloc] initWithObject: gal
                                                                       controller: self];
            [theGallery setAnselController: self];
            [galleryList addObject: theGallery];
            [theGallery release];
            theGallery = nil;
        }
    }
    [params release];
}

@end
