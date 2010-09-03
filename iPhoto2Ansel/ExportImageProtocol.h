/*
     File:       ExportImageProtocol.h

     Contains:   iPhoto Plug-ins interfaces: Protocol for image exporting

     Version:    Technology: iPhoto
                 Release:    1.0

     Copyright:  © 2002-2007 by Apple Inc. All rights reserved.

     Bugs?:      For bug reports, consult the following page on
                 the World Wide Web:

                     http://developer.apple.com/bugreporter/
*/

typedef enum
{
	EQualityLow,
	EQualityMed,
	EQualityHigh,
	EQualityMax
} ExportQuality;

typedef enum
{
	EMNone, // 0000
	EMEXIF, // 0001
	EMIPTC, // 0010
	EMBoth  // 0011
} ExportMetadata;

typedef struct
{
	OSType			format;
	ExportQuality	quality;
	float			rotation;
	unsigned		width;
	unsigned		height;
	ExportMetadata	metadata;
} ImageExportOptions;

//exif metadata access keys
#define kIPExifDateDigitized @"DateDigitized"
#define kIPExifCameraModel @"CameraModel"
#define kIPExifShutter @"Shutter"
#define kIPExifAperture @"Aperture"
#define kIPExifMaxAperture @"MaxAperture"
#define kIPExifExposureBias @"ExposureBias"
#define kIPExifExposure @"Exposure"
#define kIPExifExposureIndex @"ExposureIndex"
#define kIPExifFocalLength @"FocalLength"
#define kIPExifDistance @"Distance"
#define kIPExifSensing @"Sensing"
#define kIPExifLightSource @"LightSource"
#define kIPExifFlash @"Flash"
#define kIPExifMetering @"Metering"
#define kIPExifBrightness @"Brightness"
#define kIPExifISOSpeed @"ISOSpeed"

//tiff metadata access keys
#define kIPTiffImageWidth @"ImageWidth"
#define kIPTiffImageHeight @"ImageHeight"
#define kIPTiffOriginalDate @"OriginalDate"
#define kIPTiffDigitizedDate @"DigitizedDate"
#define kIPTiffFileName @"FileName"
#define kIPTiffFileSize @"FileSize"
#define kIPTiffModifiedDate @"ModifiedDate"
#define kIPTiffImportedDate @"ImportedDate"
#define kIPTiffCameraMaker @"CameraMaker"
#define kIPTiffCameraModel @"CameraModel"
#define kIPTiffSoftware @"Software"

@protocol ExportImageProtocol

//------------------------------------------------------------------------------
// Access to images
//------------------------------------------------------------------------------
- (unsigned)imageCount;
- (NSSize)imageSizeAtIndex:(unsigned)index;
- (OSType)imageFormatAtIndex:(unsigned)index;
- (OSType)originalImageFormatAtIndex:(unsigned)index;
- (BOOL)originalIsRawAtIndex:(unsigned)index;
- (BOOL)originalIsMovieAtIndex:(unsigned)index;
- (NSString *)imageTitleAtIndex:(unsigned)index;
- (NSString *)imageCommentsAtIndex:(unsigned)index;
- (float)imageRotationAtIndex:(unsigned)index;
- (NSString *)imagePathAtIndex:(unsigned)index;
- (NSString *)sourcePathAtIndex:(unsigned)index;
- (NSString *)thumbnailPathAtIndex:(unsigned)index;
- (NSString *)imageFileNameAtIndex:(unsigned)index;
- (BOOL)imageIsEditedAtIndex:(unsigned)index;
- (BOOL)imageIsPortraitAtIndex:(unsigned)index;
- (float)imageAspectRatioAtIndex:(unsigned)index;
- (unsigned long long)imageFileSizeAtIndex:(unsigned)index;
- (NSDate *)imageDateAtIndex:(unsigned)index;
- (int)imageRatingAtIndex:(unsigned)index;
- (NSDictionary *)imageTiffPropertiesAtIndex:(unsigned)index;
- (NSDictionary *)imageExifPropertiesAtIndex:(unsigned)index;
- (NSArray *)imageKeywordsAtIndex:(unsigned)index;
- (NSArray *)albumsOfImageAtIndex:(unsigned)index;

- (NSString *)getExtensionForImageFormat:(OSType)format;
- (OSType)getImageFormatForExtension:(NSString *)extension;

	//------------------------------------------------------------------------------
	// Access to albums
	//------------------------------------------------------------------------------
- (unsigned)albumCount; //total number of albums
- (NSString *)albumNameAtIndex:(unsigned)index; //name of album at index
- (NSString *)albumMusicPathAtIndex:(unsigned)index;
- (NSString *)albumCommentsAtIndex:(unsigned)index;
- (unsigned)positionOfImageAtIndex:(unsigned)index inAlbum:(unsigned)album;

	//------------------------------------------------------------------------------
	// Access to export controller's GUI
	//------------------------------------------------------------------------------
- (id)window;
- (void)enableControls;
- (void)disableControls;

- (void)clickExport;
- (void)startExport;
- (void)cancelExportBeforeBeginning;

- (NSString *)directoryPath;
- (unsigned)sessionID;

- (BOOL)exportImageAtIndex:(unsigned)index dest:(NSString *)dest options:(ImageExportOptions *)options;
- (NSSize)lastExportedImageSize;

	//------------------------------------------------------------------------------
@end
