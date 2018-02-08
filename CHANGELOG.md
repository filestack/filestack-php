# Filestack-php Changelog

## 1.1.12 (Feb 8, 2018)
- Updated license and fixed composer.json issues

## 1.1.11 (Nov 15, 2017)
- Fixed a bug with security that always use default expiration of one hour
no matter what was passed in

## 1.1.10 (Sep 8, 2017)
- Updated client->upload() function to return filelink instead of array of status and json

## 1.1.9 (Aug 8, 2017)
- Added option param in upload() call for Intelligent Ingestion

## 1.1.8 (Aug 8, 2017)
- Intelligent Ingestion Upload

## 1.1.7 (July 28, 2017)
- Small file upload bugfix

## 1.1.6 (June 14, 2017)
- Added Image Tagging and Safe For Work functionalities

## 1.1.5 (May 31, 2017)
- Updated source header to Filestack-Source

## 1.1.4 (May 26, 2017)
- FS-980 modified upload() function to use multiparts upload api
- FS-1039 added getConvertTaskInfo() to client and filelink

## 1.1.3 (May 18, 2017)
- Added rulesets for PHPMD
- Cleaned up code

## 1.1.2 (May 18, 2017)
- Switched to using PHPMD engine for codeclimate

## 1.1.1 (May 18, 2017)
- Integrated CodeClimate

## 1.0.6 (May 18, 2017)
- Linked Travisci and Coveralls to project
- Soft release

## 1.0.5 (May 17, 2017)
- Updated README, prepared to publish to packagist.org

## 1.0.4 (May 16, 2017)
- FS-398 added zip() and compress() functionalities
- FS-406 added screenshot() functionality
- FS-408 added collage() funcionality
- FS-94 added debug() call
- FS-409 added convertFile() functionality
- FS-410 added Audio and Video conversions

## 1.0.3 (May 8, 2017)
- FS-89 Integrated tests with TravisCI
- FS-90 Implemented Security
- FS-91 Added download(), getContent(), getMetadata() functions
- FS-92 Added delete() and overwrite() funciton
- FS-93 Implemeted Transformation functions

## 1.0.2 (May 2, 2017)

- FS-95 Added store function to CommonMixin
- FS-88 Added basic tests

## 1.0.1 (April 27, 2017)

- Initial project file structure