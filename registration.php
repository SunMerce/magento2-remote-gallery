<?php
/**
 * Sunmerce RemoteGallery
 *
 * Display remote CDN image URLs instead of the native product media gallery.
 */

\Magento\Framework\Component\ComponentRegistrar::register(
    \Magento\Framework\Component\ComponentRegistrar::MODULE,
    'Sunmerce_RemoteGallery',
    __DIR__
);
