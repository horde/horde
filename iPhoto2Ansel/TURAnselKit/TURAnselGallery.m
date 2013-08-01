/**
 * TURAnselGallery.m
 *
 * Copyright 2009-2013 Horde LLC (http://www.horde.org/)
 *
 * @license http://www.horde.org/licenses/bsd
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 */
#import <Foundation/Foundation.h>
#import "TURAnselKit.h"

@interface TURAnselGallery (PrivateAPI)
- (void)doUpload: (NSDictionary *)imageParams;
@end

@implementation TURAnselGallery

@synthesize galleryDescription;
@synthesize galleryName;
@synthesize galleryImageCount;
@synthesize galleryKeyImage;

#pragma mark -
#pragma mark init/dealloc
/**
 * Initialize a gallery object
 */
- (id)initWithObject:(id)galleryData controller:(TURAnsel *)controller
{
    [super init];
    [self setValue: [galleryData valueForKey: kTURAnselGalleryKeyId]
            forKey: @"galleryId"];
    [self setValue:[galleryData valueForKey: kTURAnselGalleryKeyDescription]
            forKey:@"galleryDescription"];
    [self setValue:[galleryData valueForKey: kTURAnselGalleryKeyName]
            forKey:@"galleryName"];
    [self setValue: [galleryData valueForKey: kTURAnselGalleryKeyImages]
            forKey:@"galleryImageCount"];
    [self setValue: [galleryData valueForKey: kTURAnselGalleryKeyDefaultImage]
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
 *
 * @return NSURL  The url object
 */
- (NSURL *)galleryKeyImageURL
{
    if (galleryKeyImageURL) {
        return galleryKeyImageURL;
    } else {
        NSArray *params;
        NSArray *order;
        if ([[anselController valueForKey:@"version"] intValue] == 2) {
            // Version 2 API
            params = [NSArray arrayWithObjects:
                       [NSNumber numberWithInt: galleryKeyImage],
                       [NSDictionary dictionaryWithObjectsAndKeys: @"thumb", kTURAnselAPIParamView, [NSNumber numberWithBool:YES], kTURAnselAPIParamFull, nil],
                       nil];
            order = [NSArray arrayWithObjects: kTURAnselAPIParamImageId,
                                               kTURAnselAPIParamSingleParameter,
                                               nil];
        } else {
            params = [NSArray arrayWithObjects:
                               @"ansel",                                         // Scope
                               [NSNumber numberWithInt: galleryKeyImage],        // Image Id
                               @"thumb",                                         // Thumbnail type
                               [NSNumber numberWithBool:YES],                    // Full path
                               nil];

            order = [NSArray arrayWithObjects: kTURAnselAPIParamScope,
                                               kTURAnselAPIParamImageId,
                                               kTURAnselAPIParamThumbnailStyle,
                                               kTURAnselAPIParamFullPath, nil];
        }
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
- (NSMutableArray *)listImages
{
    if (![imageList count]) {
        NSArray *params;
        NSArray *order;

        if ([[anselController valueForKey:@"version"] intValue] == 2) {
            params = [NSArray arrayWithObjects: 
                      [NSNumber numberWithInt: _galleryId],
                      [NSDictionary dictionaryWithObjectsAndKeys:
                       [NSNumber numberWithBool: YES], @"full",
                       @"ansel_default", @"style", nil],
                      nil];
            order = [NSArray arrayWithObjects: kTURAnselAPIParamGalleryId,
                     kTURAnselAPIParamSingleParameter,
                     nil];
        } else {
            params = [NSArray arrayWithObjects:
                               @"ansel",                                //Scope
                               [NSNumber numberWithInt: _galleryId],    //Gallery Id
                               [NSNumber numberWithInt: 2],             //PERMS_SHOW
                               @"thumb",                                // Thumbnail
                               [NSNumber numberWithBool:YES],           // Full path
                               nil];
            order = [NSArray arrayWithObjects: kTURAnselAPIParamScope,
                                               kTURAnselAPIParamGalleryId,
                                               kTURAnselAPIParamPerms,
                                               kTURAnselAPIParamThumbnailStyle,
                                               kTURAnselAPIParamFullPath, nil];
        }
        NSDictionary *response = [anselController callRPCMethod: @"images.listImages"
                                                     withParams: params
                                                      withOrder: order];
        if (response) {
            if ([[anselController valueForKey:@"version"] intValue] == 2) {
                // images.listImages returns a hash in version 2, not an array
                // Not sure where the bug is here, PHP/Horde/Cocoa WS, but if
                // the array returned by listImages only contains a single image,
                // the image id key is ignored and it's taken as a NSArray object.
                // This still works since all the methods called on the results
                // object are shared by both NSDictionary and NSArray.
                NSArray *results = [response objectForKey: (id)kWSMethodInvocationResult];
                if (![results count]) {
                    return imageList;
                }
                imageList = [[NSMutableArray arrayWithArray: results] retain];
            } else {
                imageList = [[response objectForKey: (id)kWSMethodInvocationResult] retain];
            }
        }
    }

    return imageList;;
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

    NSArray *params;
    NSArray *order;

    if ([[anselController valueForKey:@"version"] intValue] == 2) {
        params = [NSArray arrayWithObjects: [NSNumber numberWithInt: _galleryId],
                                            [imageParameters valueForKey: @"data"],
                                            [NSDictionary dictionaryWithObjectsAndKeys:@"base64", kTURAnselAPIParamEncoding, nil],
                                            nil];
        order = [NSArray arrayWithObjects: kTURAnselAPIParamGalleryId,
                                           kTURAnselAPIParamImageData,
                                           kTURAnselAPIParamSingleParameter,
                                           nil];
    } else {
        params = [NSArray arrayWithObjects:
                           @"ansel",                                  // app
                           [NSNumber numberWithInt: _galleryId],      // gallery_id
                           [imageParameters valueForKey: @"data"],    // image data array
                           [imageParameters valueForKey: @"default"], // set as default?
                           @"",                                       // Additional gallery data to set?
                           @"base64",                                 // Image data encoding
                           nil];
        order = [NSArray arrayWithObjects: kTURAnselAPIParamScope,
                                           kTURAnselAPIParamGalleryId,
                                           kTURAnselAPIParamImageData,
                                           kTURAnselAPIParamSetAsDefault,
                                           kTURAnselAPIParamAdditionalData,
                                           kTURAnselAPIParamEncoding, nil];
    }

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
}
@end
