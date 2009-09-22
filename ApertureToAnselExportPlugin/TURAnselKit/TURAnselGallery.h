/**
 * TURAnselGallery
 *
 * Copyright 2009 The Horde Project (http://www.horde.org)
 * 
 * @license http://opensource.org/licenses/bsd-license.php
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 */
#import <Cocoa/Cocoa.h>
@class TURAnsel, NSURL;

@interface NSObject (TURAnselGalleryDelegate)
- (void)TURAnselGalleryDidUploadImage: (id *)gallery;
@end

@interface TURAnselGallery : NSObject {
    int _galleryId;
    int galleryImageCount;
    int galleryKeyImage;
    NSURL *galleryKeyImageURL;
    NSMutableArray *imageList;
    NSString *galleryName;
    NSString *galleryDescription;
    TURAnsel *anselController;
    id delegate;
}
@property (readonly) NSString *galleryName;
@property (readonly) NSString *galleryDescription;
@property (readonly) int galleryImageCount;
@property (readwrite) int galleryKeyImage;

- (id)initWithObject:(id)galleryData controller:(TURAnsel * )controller;
- (void)uploadImageObject: (NSDictionary *)imageParameters;

// Getter / Setter
- (void)setDelegate: (id)newDelegate;
- (id)delegate;
- (NSURL *)galleryKeyImageURL;
- (id)listImages;
- (int)galleryId;
- (void)setAnselController:(TURAnsel *)newController;
@end