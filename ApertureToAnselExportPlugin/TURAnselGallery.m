//
//  TURAnselGallery.m
// Class wraps an Ansel Gallery
//
//  Created by Michael Rubinsky on 10/21/08.
//  Copyright 2008 __MyCompanyName__. All rights reserved.
//
#import <Foundation/Foundation.h>
#import "TURAnsel.h"
#import "TURAnselGallery.h"

@interface TURAnselGallery (PrivateAPI)
- (void)doUpload: (NSDictionary *)imageParams;
@end

@implementation TURAnselGallery

@synthesize galleryDescription;
@synthesize galleryName;
@synthesize galleryImageCount;
@synthesize galleryKeyImage;

#pragma mark -
#pragma mark init

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
            forKey:@"galleryKeyImage"];
    [self setAnselController: controller];
    return self;
}

- (void)dealloc
{
    NSLog(@"TURAnselGallery dealloc called on Gallery %@", self);
    [anselController release];
    anselController = nil;
    
    [galleryKeyImageURL release];
    galleryKeyImageURL = nil;
    
    [imageList release];
    imageList = nil;
    
    [super dealloc];
}
- (id)description 
{
    NSString *text = [NSString stringWithFormat:@"Description: %@ Id: %d has: %d images", galleryName, _galleryId, galleryImageCount];
    return text;
}

#pragma mark -
#pragma mark Actions
/**
 * Requests the gallery's key image url to be fetched from the server
 * (This information is not present in the gallery definition array returned
 *  from the images.listGalleries call). 
 */
- (NSURL *)galleryKeyImageURL
{
    if (galleryKeyImageURL) {
        return galleryKeyImageURL;
    } else {
        NSArray *params = [[NSArray alloc] initWithObjects:
                           @"ansel",                                         // Scope
                           [NSNumber numberWithInt: galleryKeyImage],        // Image Id
                           @"thumb",                                         // Thumbnail type
                           [NSNumber numberWithBool:YES],                    // Full path
                           nil];
        NSArray *order = [NSArray arrayWithObjects: @"scope", @"image_id", @"thumbnail", @"path", nil];
        NSDictionary *response = [anselController callRPCMethod: @"images.getImageUrl"
                                                       withParams: params
                                                        withOrder: order];
        
        if (response) {
            NSDictionary *url = [response objectForKey:(id)kWSMethodInvocationResult];
            [galleryKeyImageURL autorelease];
            galleryKeyImageURL = [[NSURL URLWithString: [NSString stringWithFormat: @"%@", url]] retain];
            NSLog(@"galleryKeyImageURL: %@", galleryKeyImageURL);
            return galleryKeyImageURL;
        } 
        
        return nil;
    }

}

/**
 * Get the complete list of image ids and URLs
 */
- (id)listImages
{
    if (!imageList) {
        
        NSArray *params = [[NSArray alloc] initWithObjects:
                           @"ansel",                                //Scope
                           [NSNumber numberWithInt: _galleryId],    //Gallery Id
                           [NSNumber numberWithInt: 2],             //PERMS_SHOW
                           @"thumb",                                // Thumbnail
                           [NSNumber numberWithBool:YES],           // Full path
                           nil];
        NSArray *order = [NSArray arrayWithObjects: @"scope", @"gallery_id", @"perms", @"thumnail", @"path", nil];
        NSDictionary *response = [anselController callRPCMethod: @"images.listImages"
                                                     withParams: params
                                                      withOrder: order];
        if (response) {
            [imageList autorelease];
            imageList = [[response objectForKey: (id)kWSMethodInvocationResult] retain];
            
            NSLog(@"listImages: %@", imageList);
            
            return imageList;
        }
    }
    
    return nil;
}
    
/**
 * Upload the provided image to this gallery.
 */
- (void)uploadImageObject: (NSDictionary *)imageParameters
{
    [self doUpload: imageParameters];
}

#pragma mark -
#pragma mark Getter/Setter
- (int)galleryId
{
    return _galleryId;
}
- (void)setGalleryId:(int)id
{
    _galleryId = id;
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

#pragma mark -
#pragma mark PrivateAPI
- (void)doUpload:(NSDictionary *)imageParameters
{
        // Need to build the params array now.
        NSArray *params = [[NSArray alloc] initWithObjects:
                           @"ansel",                                  // app
                           [NSNumber numberWithInt: _galleryId],      // gallery_id
                           [imageParameters valueForKey: @"data"],    // image data array   
                           [imageParameters valueForKey: @"default"], // set as default?
                           @"",                                       // Additional gallery data to set?      
                           @"base64",                                 // Image data encoding      
                           nil];
        NSArray *order = [NSArray arrayWithObjects: @"scope", @"gallery_id", @"data", @"default", @"additional_data", @"encoding", nil];
        
        // Send the request up to the controller
        NSDictionary *result = [anselController callRPCMethod: @"images.saveImage"
                                                   withParams: params
                                                    withOrder: order];
    
        if (result) {
            if ([delegate respondsToSelector:@selector(TURAnselGalleryDidUploadImage:)]) {
                [delegate performSelectorOnMainThread: @selector(TURAnselGalleryDidUploadImage:)
                                           withObject: self 
                                        waitUntilDone: NO];
            }
        }
    
        [params release];
}
@end
