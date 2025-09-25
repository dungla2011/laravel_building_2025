<?php

na    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        \App\Models\Media::create([
            'name' => 'profile-avatar.jpg',
            'file_name' => 'avatars/1234_profile-avatar.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'size' => 102400,
            'disk' => 'public',
            'path' => 'avatars/1234_profile-avatar.jpg',
            'url' => '/storage/avatars/1234_profile-avatar.jpg',
            'alt_text' => 'User profile avatar',
            'metadata' => ['width' => 400, 'height' => 400],
            'user_id' => 1,
            'collection_name' => 'avatars',
            'is_public' => true
        ]);

        \App\Models\Media::create([
            'name' => 'document.pdf',
            'file_name' => 'docs/important_doc.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size' => 2048000,
            'disk' => 'private',
            'path' => 'docs/important_doc.pdf',
            'url' => null,
            'alt_text' => null,
            'metadata' => ['pages' => 15],
            'user_id' => 1,
            'collection_name' => 'documents',
            'is_public' => false
        ]);
    } Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MediaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
    }
}
