<?php

declare(strict_types=1);

namespace OCA\DigitalSignage\Service;

use OCP\ICache;
use OCP\ICacheFactory;

class InstantMessageService {
    private const CACHE_NAMESPACE = 'digitalsignage-instant-messages';
    private const CACHE_SAFETY_WINDOW_SECONDS = 60;
    private const DEFAULT_DURATION_SECONDS = 15;
    private const MIN_DURATION_SECONDS = 1;
    private const MAX_DURATION_SECONDS = 300;
    private const MAX_MESSAGE_LENGTH = 500;

    private ICache $cache;

    public function __construct(ICacheFactory $cacheFactory) {
        $this->cache = $cacheFactory->create(self::CACHE_NAMESPACE);
    }

    public function storeMessage(int $displayId, string $message, int $duration): array {
        $normalizedMessage = trim($message);
        if ($normalizedMessage === '') {
            throw new \InvalidArgumentException('Message must not be empty');
        }

        $messageLength = function_exists('mb_strlen') ? mb_strlen($normalizedMessage) : strlen($normalizedMessage);
        if ($messageLength > self::MAX_MESSAGE_LENGTH) {
            throw new \InvalidArgumentException('Message is too long');
        }

        if (strip_tags($normalizedMessage) !== $normalizedMessage) {
            throw new \InvalidArgumentException('Message must not contain HTML');
        }

        if ($duration < self::MIN_DURATION_SECONDS || $duration > self::MAX_DURATION_SECONDS) {
            throw new \InvalidArgumentException('Duration is out of allowed range');
        }

        $messageId = bin2hex(random_bytes(16));
        $expiresAt = time() + $duration;
        $payload = [
            'id' => $messageId,
            'message' => $normalizedMessage,
            'duration' => $duration,
            'expiresAt' => $expiresAt,
        ];

        $ttl = $duration + self::CACHE_SAFETY_WINDOW_SECONDS;
        $this->cache->set($this->getDisplayCacheKey($displayId), $payload, $ttl);

        return $payload;
    }

    public function pollMessages(int $displayId, ?string $since): array {
        $payload = $this->cache->get($this->getDisplayCacheKey($displayId));
        if (!is_array($payload)) {
            return ['messages' => [], 'nextSince' => $since];
        }

        $id = isset($payload['id']) && is_string($payload['id']) ? $payload['id'] : null;
        $message = isset($payload['message']) && is_string($payload['message']) ? $payload['message'] : null;
        $duration = isset($payload['duration']) ? (int)$payload['duration'] : self::DEFAULT_DURATION_SECONDS;
        $expiresAt = isset($payload['expiresAt']) ? (int)$payload['expiresAt'] : 0;

        if ($id === null || $message === null || $expiresAt <= time()) {
            $this->cache->remove($this->getDisplayCacheKey($displayId));
            return ['messages' => [], 'nextSince' => $since];
        }

        if ($since !== null && $since !== '' && hash_equals($since, $id)) {
            return ['messages' => [], 'nextSince' => $id];
        }

        return [
            'messages' => [[
                'id' => $id,
                'message' => $message,
                'duration' => max(self::MIN_DURATION_SECONDS, min(self::MAX_DURATION_SECONDS, $duration)),
                'expiresAt' => $expiresAt,
            ]],
            'nextSince' => $id,
        ];
    }

    private function getDisplayCacheKey(int $displayId): string {
        return 'display-' . $displayId;
    }
}
