<?php

namespace App\Contracts;

use App\Models\SearchEngineProperty;

interface SearchEngineProviderInterface
{
    /**
     * Get provider name.
     */
    public function getProviderName(): string;

    /**
     * Validate and verify authorized property.
     */
    public function validateProperty(SearchEngineProperty $property): array;

    /**
     * Get quota limits and usage.
     */
    public function getQuotaInfo(SearchEngineProperty $property): array;
}
