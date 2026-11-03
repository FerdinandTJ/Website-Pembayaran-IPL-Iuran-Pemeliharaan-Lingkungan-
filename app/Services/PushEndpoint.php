<?php

namespace App\Services;

class PushEndpoint
{
    public function allowed(string $endpoint): bool
    {
        $url = parse_url($endpoint);
        if (! $url || ($url['scheme'] ?? '') !== 'https' || empty($url['host']) || isset($url['user']) || isset($url['pass']) || isset($url['fragment']) || (isset($url['port']) && $url['port'] !== 443)) {
            return false;
        }
        $host = strtolower($url['host']);

        return in_array($host, config('webpush.hosts'), true)
            || preg_match('/^[a-z0-9-]+\.notify\.windows\.com$/D', $host) === 1;
    }
}
