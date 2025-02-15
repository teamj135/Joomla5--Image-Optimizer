<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.ImageOptimizer
 * @author      Sanjeev Kumar Raghuwanshi
 * @copyright   (C) 2025 Sanjeev Kumar Raghuwanshi
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        URL
 * @since       1.0.0
 */

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Uri\Uri;

class PlgSystemImageOptimizer extends CMSPlugin
{
    protected $autoloadLanguage = true;

    /**
     * Trigger when content is saved (e.g., Media Manager image upload)
     */
    public function onContentAfterSave($context, $table, $isNew)
    {
        $app = Factory::getApplication();

        // Ensure this runs only for actual media uploads, not API requests
        if ($app->input->get('format') === 'json' || !$isNew) {
            return;
        }

        // Check if this is an image upload in com_media
        if (strpos($context, 'com_media') !== false) {
            if (empty($table->path)) {
                Factory::getApplication()->enqueueMessage(
                    JText::_('PLG_SYSTEM_IMAGEOPTIMIZER_ERROR_NO_PATH'), 'error'
                );
                return;
            }

            // Use the correct image path
            $filePath = JPATH_ROOT . '/' . $table->path;

            if (file_exists($filePath)) {
                $this->optimizeImage($filePath);
            } else {
                Factory::getApplication()->enqueueMessage(
                    JText::sprintf('PLG_SYSTEM_IMAGEOPTIMIZER_ERROR_FILE_NOT_FOUND', $filePath), 'error'
                );
            }
        }
    }

    /**
     * Trigger when a page is rendered - Converts images dynamically
     */
    public function onAfterRender()
    {
        $app = Factory::getApplication();
        if ($app->isClient('administrator')) {
            return; // Avoid affecting Joomla backend
        }

        $body = $app->getBody();
        preg_match_all('/<img.*?src=["\'](.*?)["\']/', $body, $matches);

        foreach ($matches[1] as $imageUrl) {
            // Ignore external images
            if (strpos($imageUrl, 'http') === 0 && strpos($imageUrl, Uri::root()) === false) {
                continue;
            }

            // Get the correct local image path
            $relativePath = str_replace(Uri::root(), '', $imageUrl);
            $localPath = JPATH_ROOT . '/' . ltrim($relativePath, '/');

            // Correct WebP Path (Prevents ".jpg.webp" issue)
            $pathInfo = pathinfo($localPath);
            $webpPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.webp';

            // Convert image if needed
            if (file_exists($localPath) && !file_exists($webpPath)) {
                $this->optimizeImage($localPath);
            }

            // Replace image in HTML if WebP exists
            if (file_exists($webpPath)) {
                $webpUrl = Uri::root() . str_replace(JPATH_ROOT . '/', '', $webpPath);
                $body = str_replace($imageUrl, $webpUrl, $body);
            }
        }

        $app->setBody($body);
    }

    /**
     * Optimize image: Convert to WebP and upload to CDN if enabled
     */
    private function optimizeImage($filePath)
    {
        if (!file_exists($filePath)) {
            Factory::getApplication()->enqueueMessage(
                JText::sprintf('PLG_SYSTEM_IMAGEOPTIMIZER_ERROR_FILE_NOT_FOUND', $filePath), 'error'
            );
            return;
        }

        $imageInfo = getimagesize($filePath);
        if (!$imageInfo) {
            Factory::getApplication()->enqueueMessage(
                JText::sprintf('PLG_SYSTEM_IMAGEOPTIMIZER_ERROR_INVALID_IMAGE', $filePath), 'error'
            );
            return;
        }

        $mime = $imageInfo['mime'];
        $image = null;

        // Correct WebP Output Path
        $pathInfo = pathinfo($filePath);
        $outputFile = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.webp';

        // Create image resource
        switch ($mime) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($filePath);
                break;
            case 'image/png':
                $image = imagecreatefrompng($filePath);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($filePath);
                break;
        }

        if ($image && imagewebp($image, $outputFile, 80)) {
            imagedestroy($image);
            Factory::getApplication()->enqueueMessage(
                JText::sprintf('PLG_SYSTEM_IMAGEOPTIMIZER_SUCCESS_WEBP_CREATED', $outputFile), 'message'
            );

            if ($this->params->get('enable_cdn', 0)) {
                $this->uploadToCDN($outputFile);
            }
        } else {
            Factory::getApplication()->enqueueMessage(
                JText::sprintf('PLG_SYSTEM_IMAGEOPTIMIZER_ERROR_WEBP_FAILED', $filePath), 'error'
            );
        }
    }

    /**
     * Upload optimized WebP image to a CDN
     */
    private function uploadToCDN($filePath)
    {
        $cdnUrl = $this->params->get('cdn_url', '');
        if (!$cdnUrl) {
            Factory::getApplication()->enqueueMessage(
                JText::_('PLG_SYSTEM_IMAGEOPTIMIZER_ERROR_CDN_NOT_SET'), 'error'
            );
            return;
        }

        $fileData = curl_file_create($filePath);
        $ch = curl_init($cdnUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ['file' => $fileData]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        Factory::getApplication()->enqueueMessage(
            JText::sprintf('PLG_SYSTEM_IMAGEOPTIMIZER_SUCCESS_CDN_UPLOAD', $response), 'message'
        );
    }
}
