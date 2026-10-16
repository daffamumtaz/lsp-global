<?php

namespace App\Filesystem;

use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\Visibility;

class SupabaseStorageAdapter extends AwsS3V3Adapter
{
    public function write(string $path, string $contents, Config $config): void
    {
        $this->setVisibility($path, $config->get('visibility', Visibility::PUBLIC));
        parent::write($path, $contents, $config);
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $this->setVisibility($path, $config->get('visibility', Visibility::PUBLIC));
        parent::writeStream($path, $contents, $config);
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        $this->setVisibility($destination, $config->get('visibility', Visibility::PUBLIC));
        parent::copy($source, $destination, $config);
    }

    public function setVisibility(string $path, string $visibility): void
    {
        if ($visibility !== Visibility::PUBLIC) {
            throw UnableToSetVisibility::atLocation($path, 'This disk uses a public Supabase bucket; private files require a separate disk.');
        }
    }

    public function visibility(string $path): FileAttributes
    {
        return new FileAttributes($path, visibility: Visibility::PUBLIC);
    }
}
