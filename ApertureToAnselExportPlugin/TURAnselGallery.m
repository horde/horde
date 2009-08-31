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

@interface TURAnselGallery (PrivateAPI)
- (void)doUpload: (NSDictionary *)imageParams;
@end

@implementation TURAnselGallery

@synthesize galleryDescription;
@synthesize galleryName;
@synthesize galleryImageCount;
@synthesize galleryDefaultImage;

#pragma mark Instance Methods --------------------------------------------------

/**
 * Init a gallery object
 */
- (id)initWithObject:(id)galleryData controller:(TURAnsel *)controller
{
    [super init];
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
    [self setAnselController: controller];
    return self;
}

/**
 * Requests the gallery's default image url to be fetched from the server
 * (This information is not present in the gallery definition array returned
 *  from the images.listGalleries call). 
 */
- (NSURL *)galleryDefaultImageURL
{
    if (!galleryDefaultImageURL) {

        NSArray *params = [[NSArray alloc] initWithObjects:
                           @"ansel",                                         // Scope
                           [NSNumber numberWithInt: galleryDefaultImage],    // Image Id
                           @"thumb",                                         // Thumbnail type
                           [NSNumber numberWithBool:YES],                    // Full path
                           nil];
        
        [self setState:TURAnselGalleryStateBusy];
        XMLRPCResponse *response = [anselController callRPCMethod: @"images.getImageUrl"
                                                       withParams: params];
        
        if (response) {
            galleryDefaultImageURL = [[NSURL URLWithString: [NSString stringWithFormat:@"%@", response]] retain];
        }
    }
    
    return galleryDefaultImageURL;
}

/**
 * Get the complete list of image ids and URLs
 */
- (id)listImages
{
    if (!imageList) {
        
        NSArray *params = [[NSArray alloc] initWithObjects:
                           @"ansel",                                //Scope
                           [NSNumber numberWithInt: galleryId],     //Gallery Id
                           [NSNumber numberWithInt: 2],             //PERMS_SHOW
                           @"thumb",                                // Thumbnail
                           [NSNumber numberWithBool:YES],           // Full path
                           nil];
        
        [self setState:TURAnselGalleryStateBusy];
        XMLRPCResponse *response = [anselController callRPCMethod: @"images.listImages"
                                                       withParams: params];
    
        if (response) {
            imageList = [response retain];
        }
    }
    
    return imageList;
}
    
/**
 * Upload the provided image to this gallery.
 */
- (void)uploadImageObject: (NSDictionary *)imageParameters
{    
    [self doUpload: imageParameters];
}

- (bool) isBusy
{
    if (state == TURAnselGalleryStateReady) {
        return NO;
    } else {
        return YES;
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
    NSLog(@"TURAnselGallery dealloc called on Gallery %@", self);
    [anselController release];
    [galleryDefaultImageURL release];
    [imageList release];
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
    NSString *text = [NSString stringWithFormat:@"Description: %@ Id: %d has: %d images", galleryName, galleryId, galleryImageCount];
    return text;
}

#pragma mark PrivateAPI
- (void)doUpload:(NSDictionary *)imageParameters
{
        // Need to build the XMLRPC params array now.
        NSArray *params = [[NSArray alloc] initWithObjects:
                           @"ansel",                                 // app
                           [NSNumber numberWithInt: galleryId],      // gallery_id
                           [imageParameters valueForKey: @"data"],   // image data array   
                           [imageParameters valueForKey: @"default"], // set as default?
                           @"",                                      // Additional gallery data to set?      
                           @"base64",                                // Image data encoding      
                           nil];
        
        // Send the request up to the controller
         [anselController callRPCMethod: @"images.saveImage"
                           withParams: params];
    
        if ([delegate respondsToSelector:@selector(TURAnselGalleryDidUploadImage:)]) {
            [delegate performSelectorOnMainThread: @selector(TURAnselGalleryDidUploadImage:)
                                       withObject: self 
                                    waitUntilDone: NO];
        }
    
        [params release];
}
@end
