/**
 * AnselGalleryViewItem
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org)
 * 
 * @implements IKImageBrowserItem
 * @license http://opensource.org/licenses/bsd-license.php
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 */
#import <Cocoa/Cocoa.h>
#import <Quartz/Quartz.h>

@interface AnselGalleryViewItem: NSObject 
{
	NSURL *image;
	NSString *imageID;
    NSString *imageTitle;
    NSCalendarDate *imageDate;
}

@property(readwrite,copy) NSURL * image;
@property(readwrite,copy) NSString * imageID;

- (id)initWithURL: (NSURL *)theUrl withTitle: (NSString *)theTitle withDate: (NSDate *)theDate;

#pragma mark -
#pragma mark Required Methods IKImageBrowserItem Informal Protocol
- (NSString *)imageUID;
- (NSString *)imageRepresentationType;
- (id)imageRepresentation;

- (NSString *)imageTitle;
- (NSString *)imageSubtitle;

@end
