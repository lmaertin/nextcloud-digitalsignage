<?php

declare(strict_types=1);

namespace OCA\DigitalSignage\Util;

/**
 * Short URL slugs for displays: lowercase letters, digits and single hyphens.
 */
final class Slug {
    public const MAX_LENGTH = 64;

    /**
     * Returns the normalized slug, null for "no slug", or false when the input is invalid.
     *
     * @return string|null|false
     */
    public static function normalize(?string $slug) {
        $slug = strtolower(trim((string)$slug));
        if ($slug === '') {
            return null;
        }

        if (strlen($slug) > self::MAX_LENGTH || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            return false;
        }

        return $slug;
    }
}
