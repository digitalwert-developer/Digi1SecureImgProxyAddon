<?php

namespace Digi1SecureImgProxyAddon\Services;

use FroshThumbnailProcessor\Services\ThumbnailUrlTemplateInterface;
use Shopware\Bundle\MediaBundle\MediaServiceInterface;

class ThumbnailUrlTemplate implements ThumbnailUrlTemplateInterface
{
    /** @var string */
    private $domain;

    /** @var string */
    private $key;

    /** @var string */
    private $salt;

    /** @var string */
    private $resizingType;

    /** @var string */
    private $gravity;

    /** @var int */
    private $enlarge;

    /** @var int */
    private $signatureSize;

    /**
     * @var ThumbnailUrlTemplateInterface
     */
    private $parent;

    /* @var MediaServiceInterface */
    private $mediaService;

    public function __construct(MediaServiceInterface $mediaService,  ThumbnailUrlTemplateInterface $parent)
    {
        $this->mediaService = $mediaService;
        $this->domain = $mediaService->get('FroshPlatformThumbnailProcessorImgProxy.config.Domain');
        $this->key = $mediaService->get('FroshPlatformThumbnailProcessorImgProxy.config.imgproxykey');
        $this->salt = $mediaService->get('FroshPlatformThumbnailProcessorImgProxy.config.imgproxysalt');
        $this->resizingType = $mediaService->get('FroshPlatformThumbnailProcessorImgProxy.config.resizingType') ?: 'fit';
        $this->gravity = $mediaService->get('FroshPlatformThumbnailProcessorImgProxy.config.gravity') ?: 'sm';
        $this->enlarge = $mediaService->get('FroshPlatformThumbnailProcessorImgProxy.config.enlarge') ?: 0;
        $this->signatureSize = $mediaService->get('FroshPlatformThumbnailProcessorImgProxy.config.signatureSize') ?: 32;
        $this->parent = $parent;
    }

    /**
     * @param $mediaPath
     * @param $width
     * @param $height
     * @param bool|string $mediaUrl
     *
     * @return string
     */
    public function getUrl($mediaPath, $width, $height)
    {
        $keyBin = pack('H*', $this->key);
        $saltBin = pack('H*', $this->salt);
        $mediaUrl = substr($this->mediaService->getUrl('/'), 0, -1);

        if (empty($keyBin) || empty($saltBin)) {
            return $this->parent->getUrl($mediaPath, $width, $height);
        }

        $extension = pathinfo($mediaPath, PATHINFO_EXTENSION);
        $encodedUrl = rtrim(strtr(base64_encode($mediaUrl . '/' . $mediaPath), '+/', '-_'), '=');

        $path = "/{$this->resizingType}/{$width}/{$height}/{$this->gravity}/{$this->enlarge}/{$encodedUrl}.{$extension}";
        $signature = hash_hmac('sha256', $saltBin . $path, $keyBin, true);

        if ($this->signatureSize !== 32) {
            $signature = pack('A' . $this->signatureSize, $signature);
        }

        $signature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        return $this->domain . '/' . $signature . $path;
    }
}
