<?php
namespace WebbuildersGroup\NextGenImages\Tests\PHPUnit;

use SilverStripe\Assets\Dev\TestAssetStore;
use SilverStripe\Assets\File;
use SilverStripe\Assets\FilenameParsing\HashFileIDHelper;
use SilverStripe\Assets\Folder;
use SilverStripe\Assets\Image;
use SilverStripe\Assets\InterventionBackend;
use SilverStripe\Assets\Storage\DBFile;
use SilverStripe\Dev\SapphireTest;

class WebPFIleMoving extends SapphireTest
{
    protected static $fixture_file = 'WebPFIleMoving.yml';

    /**
     * Configure the test asset store among some other setup stuff
     */
    protected function setUp(): void
    {
        parent::setUp();

        TestAssetStore::activate('GenerateWebPTest');

        // Copy test images for each of the fixture references
        /** @var \SilverStripe\DataList|File[] $files */
        $files = File::get()->exclude('ClassName', Folder::class);
        foreach ($files as $image) {
            $sourcePath = __DIR__ . '/assets/' . $image->Name;
            $image->setFromLocalFile($sourcePath, $image->Filename);
            $image->write();
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
     * Tests to see if the WebP variant is moved along with the original to the public assets folder after publishing
     */
    public function testPublishMoveWebP()
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


        //Check to see if the file was generated in the protected folder
        $this->assertFileExists(TestAssetStore::base_path() . '/.protected/folder/' . substr($img->FileHash, 0, HashFileIDHelper::HASH_TRUNCATE_LENGTH) . '/wbg-logo-png.png.webp', 'WebP Variant was not generated as expected');


        //Generate a resampled WebP
        $generatedWebP->ScaleWidth(100);
        $this->assertFileExists(TestAssetStore::base_path() . '/.protected/folder/' . substr($img->FileHash, 0, HashFileIDHelper::HASH_TRUNCATE_LENGTH) . '/wbg-logo-png__ScaleWidthWzEwMF0.png.webp', 'Resampled WebP Variant was not generated as expected');



        //Publish the file
        $img->publishSingle();


        //Check to see that the file was moved to the public asset path with the original
        $this->assertFileExists(TestAssetStore::base_path() . '/folder/wbg-logo-png.png', 'Orginal was not moved as expected');
        $this->assertFileExists(TestAssetStore::base_path() . '/folder/wbg-logo-png.png.webp', 'WebP Variant was not moved as expected');
        $this->assertFileExists(TestAssetStore::base_path() . '/folder/wbg-logo-png__ScaleWidthWzEwMF0.png.webp', 'Resampled WebP Variant was not moved as expected');
    }

    /**
     * Tests to see if the WebP variant is moved along with the original to the .protected folder after unpublishing
     */
    public function testUnpublishMoveWebP()
    {
        /** @var Image|\WebbuildersGroup\NextGenImages\Extensions\ImageExtension $img **/
        $img = $this->objFromFixture(Image::class, 'testpng');

        //Sanity Check
        $this->assertNotEmpty($img);
        $this->assertNotFalse($img);
        $this->assertTrue($img->exists());


        //Publish the file
        $img->publishSingle();


        //Attempt to generate the WebP
        $generatedWebP = $img->getWebP();
        $this->assertInstanceOf(DBFile::class, $generatedWebP);


        //Check to see if the file was generated properly
        $this->assertFileExists(TestAssetStore::base_path() . '/folder/wbg-logo-png.png.webp', 'WebP Variant was not generated as expected');


        //Generate a resampled WebP
        $generatedWebP->ScaleWidth(100);
        $this->assertFileExists(TestAssetStore::base_path() . '/folder/wbg-logo-png__ScaleWidthWzEwMF0.png.webp', 'Resampled WebP Variant was not generated as expected');


        //Unpublish the file
        $img->doUnpublish();


        //Check to see that the file was moved to the protected asset path with the original
        $this->assertFileExists(TestAssetStore::base_path() . '/.protected/folder/' . substr($img->FileHash, 0, HashFileIDHelper::HASH_TRUNCATE_LENGTH) . '/wbg-logo-png.png', 'Orginal was not moved as expected');
        $this->assertFileExists(TestAssetStore::base_path() . '/.protected/folder/' . substr($img->FileHash, 0, HashFileIDHelper::HASH_TRUNCATE_LENGTH) . '/wbg-logo-png.png.webp', 'WebP Variant was not moved as expected');
        $this->assertFileExists(TestAssetStore::base_path() . '/.protected/folder/' . substr($img->FileHash, 0, HashFileIDHelper::HASH_TRUNCATE_LENGTH) . '/wbg-logo-png__ScaleWidthWzEwMF0.png.webp', 'Resampled WebP Variant was not moved as expected');
    }

    /**
     * Tests to see if the WebP variant is deleted along with the original when archiving a published image
     */
    public function testArchiveLiveWebP()
    {
        /** @var Image|\WebbuildersGroup\NextGenImages\Extensions\ImageExtension $img **/
        $img = $this->objFromFixture(Image::class, 'testpng');

        //Sanity Check
        $this->assertNotEmpty($img);
        $this->assertNotFalse($img);
        $this->assertTrue($img->exists());


        //Publish the file
        $img->publishSingle();


        //Attempt to generate the WebP
        $generatedWebP = $img->getWebP();
        $this->assertInstanceOf(DBFile::class, $generatedWebP);


        //Check to see if the file was generated properly
        $this->assertFileExists(TestAssetStore::base_path() . '/folder/wbg-logo-png.png.webp', 'WebP Variant was not generated as expected');


        //Generate a resampled WebP
        $generatedWebP->ScaleWidth(100);
        $this->assertFileExists(TestAssetStore::base_path() . '/folder/wbg-logo-png__ScaleWidthWzEwMF0.png.webp', 'Resampled WebP Variant was not generated as expected');


        //Delete the file
        $img->doArchive();


        //Check to see that the file was deleted along with the original
        $this->assertFileDoesNotExist(TestAssetStore::base_path() . '/folder/wbg-logo-png.png', 'Orginal was not deleted as expected');
        $this->assertFileDoesNotExist(TestAssetStore::base_path() . '/folder/wbg-logo-png.png.webp', 'WebP Variant was not deleted as expected');
        $this->assertFileDoesNotExist(TestAssetStore::base_path() . '/folder/wbg-logo-png__ScaleWidthWzEwMF0.png.webp', 'Resampled WebP Variant was not deleted as expected');
        $this->assertFileDoesNotExist(TestAssetStore::base_path() . '/.protected/folder/' . substr($img->FileHash, 0, HashFileIDHelper::HASH_TRUNCATE_LENGTH) . '/wbg-logo-png.png', 'Orginal was not deleted as expected');
        $this->assertFileDoesNotExist(TestAssetStore::base_path() . '/.protected/folder/' . substr($img->FileHash, 0, HashFileIDHelper::HASH_TRUNCATE_LENGTH) . '/wbg-logo-png.png.webp', 'WebP Variant was not deleted as expected');
        $this->assertFileDoesNotExist(TestAssetStore::base_path() . '/.protected/folder/' . substr($img->FileHash, 0, HashFileIDHelper::HASH_TRUNCATE_LENGTH) . '/wbg-logo-png__ScaleWidthWzEwMF0.png.webp', 'Resampled WebP Variant was not deleted as expected');
    }

    /**
     * Tests to see if the WebP variant is deleted along with the original when archiving a draft image
     */
    public function testArchiveDraftWebP()
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


        //Check to see if the file was generated properly
        $this->assertFileExists(TestAssetStore::base_path() . '/.protected/folder/' . substr($img->FileHash, 0, HashFileIDHelper::HASH_TRUNCATE_LENGTH) . '/wbg-logo-png.png.webp', 'WebP Variant was not generated as expected');


        //Generate a resampled WebP
        $generatedWebP->ScaleWidth(100);
        $this->assertFileExists(TestAssetStore::base_path() . '/.protected/folder/' . substr($img->FileHash, 0, HashFileIDHelper::HASH_TRUNCATE_LENGTH) . '/wbg-logo-png__ScaleWidthWzEwMF0.png.webp', 'Resampled WebP Variant was not generated as expected');


        //Delete the file
        $img->doArchive();


        //Check to see that the file was deleted along with the original
        $this->assertFileDoesNotExist(TestAssetStore::base_path() . '/folder/wbg-logo-png.png', 'Orginal was not deleted as expected');
        $this->assertFileDoesNotExist(TestAssetStore::base_path() . '/folder/wbg-logo-png.png.webp', 'WebP Variant was not deleted as expected');
        $this->assertFileDoesNotExist(TestAssetStore::base_path() . '/folder/wbg-logo-png__ScaleWidthWzEwMF0.png.webp', 'Resampled WebP Variant was not deleted as expected');
        $this->assertFileDoesNotExist(TestAssetStore::base_path() . '/.protected/folder/' . substr($img->FileHash, 0, HashFileIDHelper::HASH_TRUNCATE_LENGTH) . '/wbg-logo-png.png', 'Orginal was not deleted as expected');
        $this->assertFileDoesNotExist(TestAssetStore::base_path() . '/.protected/folder/' . substr($img->FileHash, 0, HashFileIDHelper::HASH_TRUNCATE_LENGTH) . '/wbg-logo-png.png.webp', 'WebP Variant was not deleted as expected');
        $this->assertFileDoesNotExist(TestAssetStore::base_path() . '/.protected/folder/' . substr($img->FileHash, 0, HashFileIDHelper::HASH_TRUNCATE_LENGTH) . '/wbg-logo-png__ScaleWidthWzEwMF0.png.webp', 'Resampled WebP Variant was not deleted as expected');
    }
}
