<?php
/**
 * Created by PhpStorm.
 * User: joshgulledge
 * Date: 7/26/18
 * Time: 8:58 PM
 */

namespace FS\Documents\Helpers;


use Aws\S3\S3Client;
use League\Flysystem\AdapterInterface;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;

trait S3
{
    /** @var  */
    protected $adapter;

    /** @var S3Client $s3Client */
    protected $s3Client;

    /** @var string */
    protected $s3_bucket;

    /** @var string */
    protected $s3_prefix;

    /** @var  Filesystem */
    protected $filesystem;


    /**
     * @param string $path
     * @param int $minutes
     *
     * @return string
     */
    public function getS3Url($path, $minutes=30)
    {
        if (!is_object($this->s3Client) || !$this->s3Client instanceof S3Client) {
            $this->loadAwsS3Adapter();
        }
        $cmd = $this->s3Client->getCommand('GetObject', [
            'Bucket' => $this->s3_bucket,
            'Key' => (!empty($this->s3_prefix) ? $this->s3_prefix.'/'.$path : $path)
        ]);

        $request = $this->s3Client->createPresignedRequest($cmd, '+'.$minutes.' minutes');

        // Get the actual presigned-url
        return (string)$request->getUri();
    }

    /**
     * @return $this
     */
    protected function loadAwsS3Adapter()
    {
        /** @var S3Client $client */
        $this->s3Client = new S3Client([
            'credentials' => [
                'key'    => $_ENV['S3_ACCESS_KEY_ID'],
                'secret' => $_ENV['S3_SECRET_KEY'],
            ],
            'region' => $_ENV['S3_REGION'],
            'version' => $_ENV['S3_VERSION'],
        ]);

        $this->s3_bucket = $_ENV['S3_BUCKET'];

        $this->s3_prefix = $_ENV['S3_PREFIX'];

        $this->adapter = new AwsS3Adapter($this->s3Client, $this->s3_bucket, $this->s3_prefix);

        $this->filesystem = new Filesystem($this->adapter);

        return $this;
    }

    /**
     * @param string $s3_file_name
     * @param string $local_file_path
     * @return bool
     */
    protected function transferFileToS3($s3_file_name, $local_file_path)
    {
        if (!is_object($this->s3Client) || !$this->s3Client instanceof S3Client) {
            $this->loadAwsS3Adapter();
        }

        $success = $this->filesystem->put($s3_file_name, file_get_contents($local_file_path));
        try {
            if ($success) {
                $this->filesystem->setVisibility($s3_file_name, AdapterInterface::VISIBILITY_PRIVATE);
            }
        } catch (FileNotFoundException $exception) {
            // @TODO log error
        }

        return $success;
    }

}