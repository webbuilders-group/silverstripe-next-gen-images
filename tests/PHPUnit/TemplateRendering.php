<?php
namespace WebbuildersGroup\NextGenImages\Tests\PHPUnit;

use SilverStripe\Assets\Dev\TestAssetStore;
use SilverStripe\Assets\File;
use SilverStripe\Assets\FilenameParsing\HashFileIDHelper;
use SilverStripe\Assets\Folder;
use SilverStripe\Assets\Image;
use SilverStripe\Assets\InterventionBackend;
use SilverStripe\Assets\Storage\DBFile;
use SilverStripe\Control\Director;
use SilverStripe\Core\Convert;
use SilverStripe\Dev\FunctionalTest;

class TemplateRendering extends FunctionalTest
{
    protected static $fixture_file = 'TemplateRendering.yml';


    /**
     * Configure the test asset store among some other setup stuff
     */
    protected function setUp(): void
    {
        parent::setUp();

        TestAssetStore::activate('TemplateRendering');

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
     * Tests to see if a published png image is rendered properly with the source and picture tag as well both the webp and the original are accessible
     */
    public function testPublishedPNGRender()
    {
        /** @var Image|\WebbuildersGroup\NextGenImages\Extensions\ImageExtension $img **/
        $img = $this->objFromFixture(Image::class, 'testpng');

        // Sanity Check
        $this->assertNotEmpty($img);
        $this->assertNotFalse($img);
        $this->assertTrue($img->exists());


        // Attempt to generate the WebP
        $generatedWebP = $img->getWebP();
        $this->assertInstanceOf(DBFile::class, $generatedWebP);
        $this->assertFileExists(TestAssetStore::base_path() . '/.protected/folder/' . substr($img->FileHash, 0, HashFileIDHelper::HASH_TRUNCATE_LENGTH) . '/wbg-logo-png__ExtRewriteWyJwbmciLCJ3ZWJwIl0.webp', 'WebP Variant was not generated as expected');


        // Publish the file
        $img->publishSingle();
        $this->assertFileExists(TestAssetStore::base_path() . '/folder/wbg-logo-png.png', 'Orginal was not moved as expected');
        $this->assertFileExists(TestAssetStore::base_path() . '/folder/wbg-logo-png__ExtRewriteWyJwbmciLCJ3ZWJwIl0.webp', 'WebP Variant was not moved as expected');
        $this->assertFileDoesNotExist(TestAssetStore::base_path() . '/.protected/folder/' . substr($img->FileHash, 0, HashFileIDHelper::HASH_TRUNCATE_LENGTH) . '/wbg-logo-png__ExtRewriteWyJwbmciLCJ3ZWJwIl0.webp', 'WebP Variant was not removed from the protected path as expected');


        // Make sure the url ends how we'd expect
        $webpURL = $generatedWebP->getURL();
        $this->assertStringEndsWith('folder/wbg-logo-png__ExtRewriteWyJwbmciLCJ3ZWJwIl0.webp', $webpURL);


        // Render the file as if it was being used in the template
        $renderedTemplate = $img->forTemplate();


        // Make sure the picture tag exits
        $this->assertStringContainsString('<picture>', $renderedTemplate);


        // Make sure the source tag exists
        $this->assertStringContainsString('<source srcset="' . Convert::raw2att($webpURL) . '" type="image/webp" />', $renderedTemplate, 'Could not find the expected <source> tag in the rendered template');


        // Make sure the image tag exists and links to the correct file
        $originalURL = $img->getURL();
        $this->assertMatchesRegularExpression('/<img([^>]+) src="' . preg_quote(Convert::raw2att($originalURL), '/') . '"([^>]+)>/', $renderedTemplate, 'Could not fine the <img> tag pointing to the original image');


        // Make sure we can hit the WebP file
        $response = $this->get(Director::makeRelative(str_replace('/TemplateRendering/', '/', $webpURL)));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertFalse($response->isError());

        // Make sure we can hit the original file
        $response = $this->get(Director::makeRelative(str_replace('/TemplateRendering/', '/', $originalURL)));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertFalse($response->isError());
    }

    /**
     * Tests to see if a draft png image is rendered properly with the source and picture tag as well both the webp and the original are accessible
     */
    public function testDraftPNGRender()
    {
        /** @var Image|\WebbuildersGroup\NextGenImages\Extensions\ImageExtension $img **/
        $img = $this->objFromFixture(Image::class, 'testpng');

        // Sanity Check
        $this->assertNotEmpty($img);
        $this->assertNotFalse($img);
        $this->assertTrue($img->exists());


        // Attempt to generate the WebP
        $generatedWebP = $img->getWebP();
        $this->assertInstanceOf(DBFile::class, $generatedWebP);
        $this->assertFileExists(TestAssetStore::base_path() . '/.protected/folder/' . substr($img->FileHash, 0, HashFileIDHelper::HASH_TRUNCATE_LENGTH) . '/wbg-logo-png__ExtRewriteWyJwbmciLCJ3ZWJwIl0.webp', 'WebP Variant was not generated as expected');


        // Make sure the url ends how we'd expect
        $webpURL = $generatedWebP->getURL();
        $this->assertStringEndsWith('/wbg-logo-png__ExtRewriteWyJwbmciLCJ3ZWJwIl0.webp', $webpURL);


        // Render the file as if it was being used in the template
        $renderedTemplate = $img->forTemplate();


        // Make sure the picture tag exits
        $this->assertStringContainsString('<picture>', $renderedTemplate);


        // Make sure the source tag exists
        $this->assertStringContainsString('<source srcset="' . Convert::raw2att($webpURL) . '" type="image/webp" />', $renderedTemplate, 'Could not find the expected <source> tag in the rendered template');


        // Make sure the image tag exists and links to the correct file
        $originalURL = $img->getURL();
        $this->assertMatchesRegularExpression('/<img([^>]+) src="' . preg_quote(Convert::raw2att($originalURL), '/') . '"([^>]+)>/', $renderedTemplate, 'Could not fine the <img> tag pointing to the original image');


        // Make sure we can hit the WebP file
        $response = $this->get(Director::makeRelative(str_replace('/TemplateRendering/', '/', $webpURL)));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertFalse($response->isError());


        // Make sure we can hit the original file
        $response = $this->get(Director::makeRelative(str_replace('/TemplateRendering/', '/', $originalURL)));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertFalse($response->isError());
    }

    /**
     * Tests to see if a published webp image is rendered properly without the source and picture tag as well both the file is accessible
     */
    public function testPublishedWebPRender()
    {
        /** @var Image|\WebbuildersGroup\NextGenImages\Extensions\ImageExtension $img **/
        $img = $this->objFromFixture(Image::class, 'testwebp');

        // Sanity Check
        $this->assertNotEmpty($img);
        $this->assertNotFalse($img);
        $this->assertTrue($img->exists());


        // Attempt to generate the WebP
        $generatedWebP = $img->getWebP();
        $this->assertInstanceOf(Image::class, $generatedWebP);
        $this->assertFileDoesNotExist(TestAssetStore::base_path() . '/.protected/folder/' . substr($img->FileHash, 0, HashFileIDHelper::HASH_TRUNCATE_LENGTH) . '/wbg-logo-webp.webp.webp', 'WebP Variant was not generated as expected');


        // Publish the file
        $img->publishSingle();
        $this->assertFileExists(TestAssetStore::base_path() . '/folder/wbg-logo-webp.webp', 'Orginal was not moved as expected');


        // Render the file as if it was being used in the template
        $renderedTemplate = $img->forTemplate();


        // Make sure the picture tag does not exit
        $this->assertStringNotContainsString('<picture>', $renderedTemplate);
        $this->assertStringNotContainsString('<source srcset="', $renderedTemplate);


        // Make sure the image tag exists and links to the correct file
        $originalURL = $img->getURL();
        $this->assertMatchesRegularExpression('/<img([^>]+) src="' . preg_quote(Convert::raw2att($originalURL), '/') . '"([^>]+)>/', $renderedTemplate, 'Could not fine the <img> tag pointing to the original image');


        // Make sure we can hit the original file
        $response = $this->get(Director::makeRelative(str_replace('/TemplateRendering/', '/', $originalURL)));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertFalse($response->isError());
    }

    /**
     * Tests to see if a draft image is rendered properly with the source and picture tag as well both the webp and the original are accessible
     */
    public function testDraftWebPRender()
    {
        /** @var Image|\WebbuildersGroup\NextGenImages\Extensions\ImageExtension $img **/
        $img = $this->objFromFixture(Image::class, 'testwebp');

        // Sanity Check
        $this->assertNotEmpty($img);
        $this->assertNotFalse($img);
        $this->assertTrue($img->exists());


        // Attempt to generate the WebP
        $generatedWebP = $img->getWebP();
        $this->assertInstanceOf(Image::class, $generatedWebP);
        $this->assertFileDoesNotExist(TestAssetStore::base_path() . '/.protected/folder/' . substr($img->FileHash, 0, HashFileIDHelper::HASH_TRUNCATE_LENGTH) . '/wbg-logo-webp.webp.webp', 'WebP Variant was not generated as expected');


        // Render the file as if it was being used in the template
        $renderedTemplate = $img->forTemplate();


        // Make sure the picture tag does not exit
        $this->assertStringNotContainsString('<picture>', $renderedTemplate);
        $this->assertStringNotContainsString('<source srcset="', $renderedTemplate);


        // Make sure the image tag exists and links to the correct file
        $originalURL = $img->getURL();
        $this->assertMatchesRegularExpression('/<img([^>]+) src="' . preg_quote(Convert::raw2att($originalURL), '/') . '"([^>]+)>/', $renderedTemplate, 'Could not fine the <img> tag pointing to the original image');


        // Make sure we can hit the original file
        $response = $this->get(Director::makeRelative(str_replace('/TemplateRendering/', '/', $originalURL)));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertFalse($response->isError());
    }
}
