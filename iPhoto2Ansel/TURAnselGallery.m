//
//  TURAnselGallery.m
// Class wraps an Ansel Gallery
//
//  Created by Michael Rubinsky on 10/21/08.
//  Copyright 2008 __MyCompanyName__. All rights reserved.
//
#import <Foundation/Foundation.h>
#import "XMLRPC/XMLRPC.h"
#import "TURXMLConnection.h"
#import "TURAnsel.h"
#import "TURAnselGallery.h"

@implementation TURAnselGallery

@synthesize galleryDescription;
@synthesize galleryName;
@synthesize galleryImageCount;
@synthesize galleryDefaultImage;
@synthesize galleryDefaultImageURL;

#pragma mark Instance Methods --------------------------------------------------

/**
 * Init a gallery object
 */
- (id)initWithObject: (id)galleryData
{
    [super init];
    // KVC automatically type coerces
    [self setValue: [galleryData valueForKey:@"share_id"]
            forKey: @"galleryId"];
    [self setValue:[galleryData valueForKey:@"attribute_desc"]
            forKey:@"galleryDescription"];
    [self setValue:[galleryData valueForKey:@"attribute_name"]
            forKey:@"galleryName"];
    [self setValue: [galleryData valueForKey:@"attribute_images"]
            forKey:@"galleryImageCount"];    
    [self setValue: [galleryData valueForKey:@"attribute_default"]
            forKey:@"galleryDefaultImage"];

    return self;
}

/**
 * Requests the gallery's default image url to be fetched from the server
 * (This information is not present in the gallery definition array returned
 *  from the images.listGalleries call). 
 *  
 * This tells the anselController to send the request and sets this object up
 * as the delegate to receive the results. 
 */
- (void)requestDefaultImageURL
{

//    NSArray *params = [[NSArray alloc] initWithObjects:
//                       @"ansel",                                         // Scope
//                       [NSNumber numberWithInt: galleryDefaultImage],    // Image Id
//                       @"thumb",                                         // Thumbnail type
//                       [NSNumber numberWithBool:YES],                    // Full path
//                       nil];
//    [self setState:TURAnselGalleryStateBusy];
//    [anselController callRPCMethod:@"images.getImageUrl"
//                        withParams: params
//                      withDelegate: self];
    
}

/**
 * Forks off a new thread for the upload
 */
- (void)uploadImageObject: (NSDictionary *)imageParameters
{
    //[NSThread prepareForInterThreadMessages];
    NSDictionary *threadDispatchInfo = [NSDictionary dictionaryWithObjectsAndKeys:
                                        [NSThread currentThread], @"CallingThread",
                                        nil];
    NSDictionary *arguments = [NSDictionary dictionaryWithObjectsAndKeys:
                               imageParameters, @"imageParameters",
                               threadDispatchInfo, @"threadDispatchInfo",
                               nil];
    
    
    [self uploadThread: arguments];
}

- (bool) isBusy
{
    if (state == TURAnselGalleryStateReady) {
        return NO;
    } else {
        return YES;
    }
}

#pragma mark Response parsers called from the delegate method ------------------
/**
 * Called by the XMLRPCConnection delegate to parse the resposne
 */
- (void)parseImageUrlRequest: (XMLRPCResponse *)response
{
    [self setState:TURAnselGalleryStateReady];
    NSLog(@"Image URL For Gallery Preview: %@",[response responseObject]);
    NSString *url = [NSString stringWithFormat:@"%@", [response responseObject]];
    NSURL *imageURL = [NSURL URLWithString:url];
    galleryDefaultImageURL = [imageURL retain]; 
    
    if ([delegate respondsToSelector:@selector(TURAnselGalleryDidReceiveDefaultURL:)]) {
        [delegate TURAnselGalleryDidReceiveDefaultURL: self];
    }
    
}

#pragma mark Getter/Setter------------------------------------------------------
- (int)galleryId
{
    return galleryId;
}
- (void)setGalleryId:(int)id
{
    galleryId = id;
}

- (id)delegate
{
    return delegate;
}
- (void)setDelegate: (id)newDelegate
{
    delegate = newDelegate;
}

- (TURAnselGalleryState)state
{
    return state;
}
- (void)setState: (TURAnselGalleryState)theState 
{
    state = theState;
}

- (void)setAnselController: (TURAnsel *)newController
{
    [anselController autorelease];
    anselController = [newController retain];
}

- (TURAnsel *)anselController
{
    return anselController;
}

#pragma mark Overrides----------------------------------------------------------
- (void)dealloc
{
    NSLog(@"TURAnselGallery dealloc called");
    [galleryDescription release];
    [anselController release];
    [galleryDefaultImageURL release];
    [galleryDescription release];
    [super dealloc];
}

- (id)init
{
    [super init];
    [self setState:TURAnselGalleryStateReady];
    return self;
}

- (id)description 
{
    NSString *text = [NSString stringWithFormat:@"Description: %@ Id: %d has: %d images", galleryDescription, galleryId, galleryImageCount];
    return text;
}

#pragma mark PrivateAPI
- (void)uploadThread:(NSDictionary *)parameters
{
        //For now, assume success stil need to do error checking/handling
        [self doUplod:[[parameters objectForKey:@"imageParameters"] retain]];
        if ([delegate respondsToSelector:@selector(TURAnselGalleryDidUploadImage:)]) {
            [delegate performSelectorOnMainThread:@selector(TURAnselGalleryDidUploadImage:)
                                       withObject:self 
                                    waitUntilDone:NO];
        }
}
- (void)doUplod:(NSDictionary *)imageParameters
{
        // Need to build the XMLRPC params array now.
        NSArray *params = [[NSArray alloc] initWithObjects:
                           @"ansel",                                 // app
                           [NSNumber numberWithInt: galleryId],      // gallery_id
                           [imageParameters valueForKey: @"data"],   // image data array   
                           [imageParameters valueForKey:@"default"], // set as default?
                           @"",                                      // Additional gallery data to set?      
                           @"base64",                                // Image data encoding      
                           nil];
        
        // Send the request up to the controller
         [anselController callRPCMethod:@"images.saveImage"
                           withParams: params];
        [params release];
}
@end
