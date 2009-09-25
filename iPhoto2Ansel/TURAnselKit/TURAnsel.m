/**
 * TURAnsel.m
 *
 * Main class for interacting with a remote Ansel server.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org)
 * 
 * @license http://opensource.org/licenses/bsd-license.php
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 */
#import <Foundation/Foundation.h>
#import "TURAnselKit.h"

@interface TURAnsel (PrivateAPI)
- (void)doLogin;
@end

@implementation TURAnsel

@synthesize rpcEndPoint;
@synthesize username;
@synthesize password;

#pragma mark -
#pragma mark init/dealloc
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

#pragma mark -
#pragma mark Actions
- (void)connect 
{
    [self doLogin];
}
- (void) cancel
{
    state = TURAnselStateCancelled;
}

/**
 * Create a new gallery on the Ansel server.
 *
 * @param NSDictionary params  A dictionary of parameters for the new gallery.
 *
 * @return NSDictionary  A dictionary describing the new gallery
 */
- (NSDictionary *)createNewGallery: (NSDictionary *)params
{
    NSArray *apiparams = [NSArray arrayWithObjects: @"ansel", params, nil]; 
    NSArray *order = [NSArray arrayWithObjects: kTURAnselAPIParamScope, kTURAnselAPIParamGaleryParams, nil];
    
    NSDictionary *response = [self callRPCMethod: @"images.createGallery"
                                      withParams: apiparams
                                       withOrder: order];
    
    if (response) {
        NSNumber *gallery_id = [response objectForKey: (NSString *)kWSMethodInvocationResult];    
        NSDictionary *results = [NSDictionary dictionaryWithObjectsAndKeys:
                                 gallery_id, kTURAnselGalleryKeyId,
                                 [params valueForKey: @"name"], kTURAnselGalleryKeyName,
                                 @"", kTURAnselGalleryKeyDescription,
                                 [NSNumber numberWithInt: 0], kTURAnselGalleryKeyImages,
                                 [NSNumber numberWithInt: 0], kTURAnselGalleryKeyDefaultImage, nil];
        
        TURAnselGallery *newGallery = [[TURAnselGallery alloc] initWithObject: results
                                                                   controller: self];
        [galleryList addObject: newGallery];
        [newGallery release];
        
        return results;
    }
    
    return nil;
}


/**
 * Entry point for calling RPC methods on the Horde server. 
 * 
 * @param NSString methodName  The method to call (e.g. images.listGalleries)
 * @param NSArray  params      All the method's parameters
 * @param NSArray  order       Keys for the params array, needed because of how
 *                             WSMethodInvocationSetParameters is used. (The keys are
 *                             disregarded by Horde, but needed to ensure they get
 *                             sent in the correct order by WS.
 *
 * The invocationCallback function is called on completion, which in turn will
 * call the methodCompletionCallback with the results
 */
- (NSDictionary *)callRPCMethod: (NSString *)methodName
                     withParams: (NSArray *) params
                      withOrder: (NSArray *) order
{
    NSLog(@"Initiating connection for %@", methodName);
    
    // Get a URL object
    NSURL *url = [NSURL URLWithString: [self valueForKey: @"rpcEndPoint"]];
    NSDictionary *values = [NSDictionary dictionaryWithObjects: params forKeys:order];
    
    // Credentials
    NSString *user = [self valueForKey:@"username"];
    NSString *pass = [self valueForKey:@"password"];
    
    if (user != nil && [user length] && pass != nil && [pass length]) {
        // Create a custom http request with authorization
        CFHTTPMessageRef request = CFHTTPMessageCreateRequest(kCFAllocatorDefault,
                                                              (CFStringRef)@"POST",
                                                              (CFURLRef)url,
                                                              kCFHTTPVersion1_1);
        // Add auth creds to request.
        Boolean success = CFHTTPMessageAddAuthentication(request,
                                                         NULL,
                                                         (CFStringRef)user,
                                                         (CFStringRef)pass,
                                                         kCFHTTPAuthenticationSchemeBasic,
                                                         false);
        
        NSLog(@"Results adding credentials to request: %d", success);
        if (!success) {
            NSLog(@"Unable to authenticate");
            
            if ([[self delegate] respondsToSelector: @selector(TURAnselHadError:)]) {
                NSError *error = [NSError errorWithDomain:@"TURAnsel"
                                                     code: 1
                                                 userInfo:[NSDictionary dictionaryWithObjectsAndKeys: @"Authentication failure.", @"message", nil]];
                
                [[self delegate] TURAnselHadError: error];
            }
            
            return nil;
            
        } else {
            // Build a new invocation
            [self setState:TURAnselStateWaiting];
            WSMethodInvocationRef rpcCall;
            rpcCall = WSMethodInvocationCreate((CFURLRef)url, (CFStringRef)methodName, kWSXMLRPCProtocol); 
            WSMethodInvocationSetParameters(rpcCall, (CFDictionaryRef)values, (CFArrayRef)order);
            WSMethodInvocationSetProperty(rpcCall, kWSHTTPMessage, request);
            NSDictionary *result = (NSDictionary *)WSMethodInvocationInvoke(rpcCall);
            CFRelease(request);
            // Check out the results
            if (WSMethodResultIsFault((CFDictionaryRef) result)) {
                NSError *error;
                CFHTTPMessageRef response = (CFHTTPMessageRef)[result objectForKey:(id)kWSHTTPResponseMessage];
                // We might not have a response at all (server might be unreachable)
                if (response) {
                    int resStatusCode = CFHTTPMessageGetResponseStatusCode(response);
                    NSString *resStatusLine = (NSString *)CFHTTPMessageCopyResponseStatusLine(response);
                    if (resStatusCode == 401) {
                        error = [NSError errorWithDomain: @"TURAnsel"
                                                    code: resStatusCode
                                                userInfo: [NSDictionary dictionaryWithObjectsAndKeys: resStatusLine, @"NSLocalizedDescriptionKey", nil]];
                    } else {
                        NSNumber *faultCode = [result objectForKey: (NSString *)kWSFaultCode];
                        NSString *faultString = [result objectForKey: (NSString *)kWSFaultString];
                        NSLog(@"faultCode: %@ faultString: %@", faultCode, faultString);
                        error = [NSError errorWithDomain: @"TURAnsel"
                                                    code: [faultCode intValue]
                                                userInfo: [NSDictionary dictionaryWithObjectsAndKeys: [NSString stringWithFormat: @"There was an error contacting the Ansel server: %@, %@", resStatusLine, faultString], @"NSLocalizedDescriptionKey", nil]];
                        
                        
                    } 
                    [resStatusLine release];
                } else {
                    // No response
                    NSNumber *faultCode = [result objectForKey: (NSString *)kWSFaultCode];
                    NSString *faultString = [result objectForKey: (NSString *)kWSFaultString];
                    NSLog(@"faultCode: %@ faultString: %@", faultCode, faultString);
                    error = [NSError errorWithDomain: @"TURAnsel"
                                                code: [faultCode intValue]
                                            userInfo: [NSDictionary dictionaryWithObjectsAndKeys: [NSString stringWithFormat: @"There was an error contacting the Ansel server: %@", faultString], @"NSLocalizedDescriptionKey", nil]];

                }
                
                if ([[self delegate] respondsToSelector: @selector(TURAnselHadError:)]) {
                    [[self delegate] TURAnselHadError: error];
                }
                [result autorelease];
                return nil;
                
            }
            CFHTTPMessageRef response = (CFHTTPMessageRef)[result objectForKey:(id)kWSHTTPResponseMessage];
            int resStatusCode = CFHTTPMessageGetResponseStatusCode(response);
            NSLog(@"ResponseCode: %d", resStatusCode);
            [self setState:TURAnselStateConnected];
            return [result autorelease];
        }       
    }
    
    NSLog(@"No authentication information present.");
    return nil;
    
}

#pragma mark -
#pragma mark Setters/Getters
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

/**
 * Return the gallery at the specified position in the internal storage array.
 * Needed for when we are using this class as a datasource for a UI element.
 */
- (TURAnselGallery *)getGalleryByIndex: (NSInteger)index
{
    TURAnselGallery *g = [galleryList objectAtIndex:index];
    return g;
}

#pragma mark --
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

#pragma mark PrivateAPI
/**
 * Perform initial contact with Ansel server. Retrieves the list of galleries
 * available for the selected server.
 *
 * @return void
 */
- (void)doLogin
{
    NSArray *params = [[NSArray alloc] initWithObjects:
                       @"ansel",                                 // Scope 
                       [NSNumber numberWithInt: PERMS_EDIT],     // Perms
                       @"",                                      // No parent
                       [NSNumber numberWithBool:YES],            // allLevels
                       [NSNumber numberWithInt: 0],              // Offset
                       [NSNumber numberWithInt: 0],              // Count
                       [self valueForKey:@"username"], nil];     // Restrict to user (This should be an option eventually).
    
    NSArray *order = [NSArray arrayWithObjects: kTURAnselAPIParamScope, kTURAnselAPIParamPerms,
                                                kTURAnselAPIParamParent, kTURAnselAPIParamAllLevels,
                                                kTURAnselAPIParamOffset, kTURAnselAPIParamCount,
                                                kTURAnselAPIParamUserOnly, nil];
    
    NSDictionary *results = [self callRPCMethod: @"images.listGalleries"
                                     withParams: params
                                      withOrder: order];
    
    if (results) {
        NSDictionary *galleries = [results objectForKey: (id)kWSMethodInvocationResult];
        for (NSString *gal in galleries) {
            TURAnselGallery *theGallery = [[TURAnselGallery alloc] initWithObject: gal
                                                                       controller: self];
            [theGallery setAnselController: self];
            [galleryList addObject: theGallery];
            [theGallery release];
            theGallery = nil;
        }
        
        if ([delegate respondsToSelector:@selector(TURAnselDidInitialize)]) {
            [delegate performSelectorOnMainThread:@selector(TURAnselDidInitialize)
                                       withObject:self
                                    waitUntilDone: NO];
        }
        
    }
    
    [params release];
}
@end