<?php
namespace WebbuildersGroup\NextGenImages\Tests\PHPUnit;

use SilverStripe\Assets\Dev\TestAssetStore;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Folder;
use SilverStripe\Assets\Image;
use SilverStripe\Assets\InterventionBackend;
use SilverStripe\Assets\Storage\DBFile;
use SilverStripe\Dev\SapphireTest;

class GenerateWebP extends SapphireTest
{
    protected static $fixture_file = 'GenerateWebP.yml';

    /**
     * Configure the test asset store among some other setup stuff
     */
    protected function setUp(): void
    {
        parent::setUp();

        TestAssetStore::activate('GenerateWebPTest');

        // Copy test images for each of the fixture references
        /** @var File $image */
        $files = File::get()->exclude('ClassName', Folder::class);
        foreach ($files as $image) {
            $sourcePath = __DIR__ . '/GenerateWebPTest/' . $image->Name;
            $image->setFromLocalFile($sourcePath, $image->Filename);
            $image->publishSingle();
        }

        // Set default config
        InterventionBackend::config()->set('error_cache_ttl', [
            InterventionBackend::FAILED_INVALID => 0,
            InterventionBackend::FAILED_MISSING => '5,10',
            InterventionBackend::FAILED_UNKNOWN => 300,
        ]);
    }

    /**
     * Resets the test asset store
     */
    protected function tearDown(): void
    {
        TestAssetStore::reset();

        parent::tearDown();
    }

    /**
     * Tests WebP file detection
     */
    public function testWebPDetection()
    {
        /** @var Image|\WebbuildersGroup\NextGenImages\Extensions\ImageExtension $png **/
        $png = $this->objFromFixture(Image::class, 'testpng');

        //Sanity Check
        $this->assertNotEmpty($png);
        $this->assertNotFalse($png);
        $this->assertTrue($png->exists());

        //Make srue the image was not detected as a WebP
        $this->assertFalse($png->getIsWebP(), 'Image should not have been a WebP but it was detected as one');


        /** @var Image|\WebbuildersGroup\NextGenImages\Extensions\ImageExtension $webp **/
        $webp = $this->objFromFixture(Image::class, 'testwebp');

        //Sanity Check
        $this->assertNotEmpty($webp);
        $this->assertNotFalse($webp);
        $this->assertTrue($webp->exists());

        //Make sure the image was detected as a WebP
        $this->assertTrue($webp->getIsWebP(), 'Image should have been a WebP but it wasn\'t detected as one');
    }

    /**
     * Test getting a WebP file from the Image class
     */
    public function testImageGetWebP()
    {
        /** @var Image|\WebbuildersGroup\NextGenImages\Extensions\ImageExtension $img **/
        $img = $this->objFromFixture(Image::class, 'testpng');

        //Sanity Check
        $this->assertNotEmpty($img);
        $this->assertNotFalse($img);
        $this->assertTrue($img->exists());


        //Attempt to generate the WebP
        $generatedWebP = $img->getWebP();
        $this->assertInstanceOf(DBFile::class, $generatedWebP);


        //Check to see if the file was generated how we expect
        $this->assertFileExists(TestAssetStore::base_path() . '/folder/wbg-logo-png.png.webp', 'WebP Variant was not generated as expected');


        //Verify the DBFile returned has the expected file name
        $this->assertEquals('folder/wbg-logo-png.png.webp', $generatedWebP->Filename);
    }

    /**
     * Test getting a WebP file from the DBFile class
     */
    public function testDBFileGetWebP()
    {
        /** @var Image|\WebbuildersGroup\NextGenImages\Extensions\ImageExtension $img **/
        $img = $this->objFromFixture(Image::class, 'testpng');

        //Sanity Check
        $this->assertNotEmpty($img);
        $this->assertNotFalse($img);
        $this->assertTrue($img->exists());


        //Create a DBFile of the image
        /** @var DBFile|\WebbuildersGroup\NextGenImages\Extensions\ImageExtension $dbFile **/
        $dbFile = $img->File;
        $this->assertInstanceOf(DBFile::class, $dbFile);
        $this->assertEquals('folder/wbg-logo-png.png', $dbFile->Filename);


        //Attempt to generate the WebP
        $generatedWebP = $dbFile->getWebP();
        $this->assertInstanceOf(DBFile::class, $generatedWebP);


        //Check to see if the file was generated how we expect
        $this->assertFileExists(TestAssetStore::base_path() . '/folder/wbg-logo-png.png.webp', 'WebP Variant was not generated as expected');


        //Verify the DBFile returned has the expected file name
        $this->assertEquals('folder/wbg-logo-png.png.webp', $generatedWebP->Filename);
    }

    /**
     * Tests resampling a generated WebP file from the Image class
     */
    public function testImageResampleGeneratedWebP()
    {
        /** @var Image|\WebbuildersGroup\NextGenImages\Extensions\ImageExtension $img **/
        $img = $this->objFromFixture(Image::class, 'testpng');

        //Sanity Check
        $this->assertNotEmpty($img);
        $this->assertNotFalse($img);
        $this->assertTrue($img->exists());

        //Generate the WebP
        $generatedWebP = $img->getWebP();


        //Resample the WebP
        /** @var DBFile|\WebbuildersGroup\NextGenImages\Extensions\ImageExtension $resampled **/
        $resampled = $generatedWebP->ScaleWidth(100);
        $this->assertInstanceOf(DBFile::class, $resampled);


        //Make sure we got a resample back
        $this->assertEquals('ScaleWidthWzEwMF0', $resampled->getVariant());


        //Make sure the resample has the name we expect
        $this->assertEquals('/assets/GenerateWebPTest/folder/wbg-logo-png__ScaleWidthWzEwMF0.png.webp', $resampled->getURL(false));
    }

    /**
     * Tests resampling a generated WebP file from the DBFile class
     */
    public function testDBFileResampleGeneratedWebP()
    {
        /** @var Image|\WebbuildersGroup\NextGenImages\Extensions\ImageExtension $img **/
        $img = $this->objFromFixture(Image::class, 'testpng');

        //Sanity Check
        $this->assertNotEmpty($img);
        $this->assertNotFalse($img);
        $this->assertTrue($img->exists());

        $dbFile = $img->File;

        //Generate the WebP
        $generatedWebP = $dbFile->getWebP();


        //Resample the WebP
        /** @var DBFile|\WebbuildersGroup\NextGenImages\Extensions\ImageExtension $resampled **/
        $resampled = $generatedWebP->ScaleWidth(100);
        $this->assertInstanceOf(DBFile::class, $resampled);


        //Make sure we got a resample back
        $this->assertEquals('ScaleWidthWzEwMF0', $resampled->getVariant());


        //Make sure the resample has the name we expect
        $this->assertEquals('/assets/GenerateWebPTest/folder/wbg-logo-png__ScaleWidthWzEwMF0.png.webp', $resampled->getURL(false));
    }

    /**
     * Tests getting a WebP variant after resampling the original using the Image class
     */
    public function testImageResampleResampleGetWebP()
    {
        /** @var Image|\WebbuildersGroup\NextGenImages\Extensions\ImageExtension $img **/
        $img = $this->objFromFixture(Image::class, 'testpng');

        //Sanity Check
        $this->assertNotEmpty($img);
        $this->assertNotFalse($img);
        $this->assertTrue($img->exists());


        //Resample the original
        /** @var DBFile|\WebbuildersGroup\NextGenImages\Extensions\ImageExtension $resampled **/
        $resampled = $img->ScaleWidth(100);
        $this->assertInstanceOf(DBFile::class, $resampled);


        //Make sure we got a resample back
        $this->assertEquals('ScaleWidthWzEwMF0', $resampled->getVariant());

        //Make sure the resample has the name we expect
        $this->assertEquals('/assets/GenerateWebPTest/folder/wbg-logo-png__ScaleWidthWzEwMF0.png', $resampled->getURL(false));


        //Generate the WebP
        $generatedWebP = $resampled->getWebP();


        //Make sure we got a resample back
        $this->assertEquals('ScaleWidthWzEwMF0', $generatedWebP->getVariant());


        //Make sure the resample has the name we expect
        $this->assertEquals('/assets/GenerateWebPTest/folder/wbg-logo-png__ScaleWidthWzEwMF0.png.webp', $generatedWebP->getURL(false));
    }

    /**
     * Tests getting a WebP variant after resampling the original using the DBFile class
     */
    public function testDBFileResampleGetWebP()
    {
        /** @var Image|\WebbuildersGroup\NextGenImages\Extensions\ImageExtension $img **/
        $img = $this->objFromFixture(Image::class, 'testpng');

        //Sanity Check
        $this->assertNotEmpty($img);
        $this->assertNotFalse($img);
        $this->assertTrue($img->exists());

        $dbFile = $img->File;


        //Resample the original
        /** @var DBFile|\WebbuildersGroup\NextGenImages\Extensions\ImageExtension $resampled **/
        $resampled = $dbFile->ScaleWidth(100);
        $this->assertInstanceOf(DBFile::class, $resampled);


        //Make sure we got a resample back
        $this->assertEquals('ScaleWidthWzEwMF0', $resampled->getVariant());

        //Make sure the resample has the name we expect
        $this->assertEquals('/assets/GenerateWebPTest/folder/wbg-logo-png__ScaleWidthWzEwMF0.png', $resampled->getURL(false));


        //Generate the WebP
        $generatedWebP = $resampled->getWebP();


        //Make sure we got a resample back
        $this->assertEquals('ScaleWidthWzEwMF0', $generatedWebP->getVariant());


        //Make sure the resample has the name we expect
        $this->assertEquals('/assets/GenerateWebPTest/folder/wbg-logo-png__ScaleWidthWzEwMF0.png.webp', $generatedWebP->getURL(false));
    }

    /**
     * Test to ensure that an image that is already a WebP does not generate another WebP using the Image class
     */
    public function testImageGenerateWebPWithWebP()
    {
        /** @var Image|\WebbuildersGroup\NextGenImages\Extensions\ImageExtension $img **/
        $img = $this->objFromFixture(Image::class, 'testwebp');

        //Sanity Check
        $this->assertNotEmpty($img);
        $this->assertNotFalse($img);
        $this->assertTrue($img->exists());

        //Generate the WebP
        $generatedWebP = $img->getWebP();

        //Make sure we got an image back again and that it's the same image
        $this->assertInstanceOf(Image::class, $generatedWebP);
        $this->assertEquals($img->ID, $generatedWebP->ID);
        $this->assertEquals($img->getURL(), $generatedWebP->getURL());
    }

    /**
     * Test to ensure that an image that is already a WebP does not generate another WebP using the Image class
     */
    public function testDBFileGenerateWebPWithWebP()
    {
        /** @var Image|\WebbuildersGroup\NextGenImages\Extensions\ImageExtension $img **/
        $img = $this->objFromFixture(Image::class, 'testwebp');

        //Sanity Check
        $this->assertNotEmpty($img);
        $this->assertNotFalse($img);
        $this->assertTrue($img->exists());

        $img = $img->File;

        //Generate the WebP
        $generatedWebP = $img->getWebP();

        //Make sure we got an image back again and that it's the same image
        $this->assertInstanceOf(DBFile::class, $generatedWebP);
        $this->assertEquals($img->getURL(), $generatedWebP->getURL());
    }
}
