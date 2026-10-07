<?php

namespace App\Contracts;

use App\Models\Backlink;

interface IndexStatusProviderInterface
{
    /**
     * Inspect and retrieve genuine search engine index status for an authorized URL.
     * Note: Does not claim indexing without genuine search engine API confirmation.
     */
    public function inspectIndexStatus(Backlink $backlink): array;
}
