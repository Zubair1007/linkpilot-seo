<?php

namespace App\Contracts;

use App\Models\Backlink;
use App\Models\DiscoveryJob;

interface DiscoveryProviderInterface
{
    /**
     * Unique identifier for the provider (e.g. 'bing', 'google_sc', 'indexnow').
     */
    public function getProviderName(): string;

    /**
     * Submit or inspect URL for discovery.
     */
    public function submitUrl(Backlink $backlink, ?DiscoveryJob $job = null): array;

    /**
     * Batch submit URLs where authorized.
     */
    public function batchSubmit(array $backlinks): array;

    /**
     * Check if the authenticated property is authorized to submit for this URL.
     */
    public function isAuthorizedForUrl(string $url): bool;
}
