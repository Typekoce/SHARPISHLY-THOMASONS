<?php
declare(strict_types=1);

namespace App\Models;

use App\Db;

class VideoUploadModel
{
    private Db $db;

    public function __construct()
    {
        $this->db = new Db();
    }

    public function createUpload(string $path, string $filename): int
    {
        return $this->db->save([
            'tbl'               => 'video_uploads',
            'original_path'     => $path,
            'original_filename' => $filename,
            'status'            => 'pending',
        ]);
    }

    public function addPlatformPost(int $uploadId, string $platform, array $custom): int
    {
        return $this->db->save([
            'tbl'                => 'social_posts',
            'video_upload_id'    => $uploadId,
            'platform'           => $platform,
            'custom_title'       => $custom['title'] ?? '',
            'custom_description' => $custom['description'] ?? '',
            'custom_hashtags'    => $custom['hashtags'] ?? '',
            'status'             => 'pending',
        ]);
    }

    public function updatePostStatus(int $postId, string $status, ?string $url = null): void
    {
        $this->db->save([
            'tbl'      => 'social_posts',
            'id'       => $postId,
            'status'   => $status,
            'post_url' => $url,
            'posted_at'=> date('Y-m-d H:i:s'),
        ]);
    }
}