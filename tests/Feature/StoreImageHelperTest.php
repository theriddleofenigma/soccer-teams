<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StoreImageHelperTest extends TestCase
{
    /**
     * Store Image should add the uploaded file/image to the given path.
     */
    public function test_store_image_helper_should_save_the_file_in_storage(): void
    {
        $savedPath = storeImage(UploadedFile::fake()->image('sample.jpg'), fake()->filePath());
        Storage::assertExists($savedPath);
    }

    /**
     * Store Image helper should save the uploaded file in given path.
     */
    public function test_store_image_helper_should_save_in_given_path(): void
    {
        $path = fake()->filePath();
        $savedPath = storeImage(UploadedFile::fake()->image('sample.jpg'), $path);
        $this->assertTrue(Str($savedPath)->startsWith(ltrim($path, '/')));
    }

    /**
     * Image saved in storage should match the uploaded file content.
     */
    public function test_image_saved_in_storage_should_match_the_uploaded_file(): void
    {
        $file = UploadedFile::fake()->image('sample.jpg');
        $savedPath = storeImage($file, fake()->filePath());
        $this->assertEquals($file->get(), Storage::get($savedPath));
    }
}
