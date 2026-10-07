<?php

namespace App\Services\Import;

use App\Models\Backlink;
use App\Models\Campaign;
use App\Models\Project;
use Exception;
use Illuminate\Support\Facades\DB;

class BacklinkImportService
{
    /**
     * Import backlinks from raw text (one per line, format: source_url [target_url] [anchor_text])
     * or comma/tab separated.
     */
    public function importFromText(string $rawText, Campaign $campaign, string $defaultTargetUrl = ''): array
    {
        $lines = preg_split("/\r\n|\n|\r/", $rawText);
        $rows = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            // Check delimiter: tab, comma, semicolon, or space
            if (str_contains($line, "\t")) {
                $cols = explode("\t", $line);
            } elseif (str_contains($line, ',')) {
                $cols = str_getcsv($line);
            } else {
                $cols = preg_split('/\s+/', $line, 3);
            }

            $sourceUrl = trim($cols[0] ?? '');
            $targetUrl = trim($cols[1] ?? $defaultTargetUrl);
            $anchorText = trim($cols[2] ?? '');

            $rows[] = [
                'source_url' => $sourceUrl,
                'target_url' => $targetUrl,
                'anchor_text' => $anchorText ?: null,
            ];
        }

        return $this->processRows($rows, $campaign);
    }

    /**
     * Import from CSV file content.
     */
    public function importFromCsv(string $csvContent, Campaign $campaign, string $defaultTargetUrl = ''): array
    {
        $lines = preg_split("/\r\n|\n|\r/", $csvContent);
        $rows = [];
        $header = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $cols = str_getcsv($line);
            if (!$header) {
                // Determine if this line is a header
                $firstCol = strtolower(trim($cols[0] ?? ''));
                if (in_array($firstCol, ['source', 'source_url', 'url', 'page', 'source url'], true)) {
                    $header = array_map(fn($c) => strtolower(trim($c)), $cols);
                    continue;
                }
            }

            if ($header) {
                $rowAssoc = [];
                foreach ($header as $i => $key) {
                    $rowAssoc[$key] = trim($cols[$i] ?? '');
                }

                $source = $rowAssoc['source_url'] ?? $rowAssoc['source'] ?? $rowAssoc['url'] ?? '';
                $target = $rowAssoc['target_url'] ?? $rowAssoc['target'] ?? $defaultTargetUrl;
                $anchor = $rowAssoc['anchor_text'] ?? $rowAssoc['anchor'] ?? null;

                $rows[] = [
                    'source_url' => $source,
                    'target_url' => $target,
                    'anchor_text' => $anchor ?: null,
                ];
            } else {
                // No header, treat as columns 0: source, 1: target, 2: anchor
                $rows[] = [
                    'source_url' => trim($cols[0] ?? ''),
                    'target_url' => trim($cols[1] ?? $defaultTargetUrl),
                    'anchor_text' => trim($cols[2] ?? '') ?: null,
                ];
            }
        }

        return $this->processRows($rows, $campaign);
    }

    /**
     * Process, validate, deduplicate, and persist rows.
     */
    public function processRows(array $rows, Campaign $campaign): array
    {
        $imported = [];
        $duplicates = [];
        $errors = [];
        $seenInBatch = [];

        $project = $campaign->project;

        $maxImport = (int) config('linkpilot.limits.max_urls_per_import', 50);
        if (count($rows) > $maxImport) {
            throw new Exception("Import batch exceeds configured limit of {$maxImport} URLs (Free-tier guardrail: MAX_URLS_PER_IMPORT). Please upload a smaller batch.");
        }

        DB::beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 1;
                $source = trim($row['source_url'] ?? '');
                $target = trim($row['target_url'] ?? '');
                $anchor = trim($row['anchor_text'] ?? '') ?: null;

                // 1. Validation: Missing source or target
                if (empty($source)) {
                    $errors[] = ['row' => $rowNumber, 'error' => 'Missing source URL.'];
                    continue;
                }
                if (empty($target)) {
                    $errors[] = ['row' => $rowNumber, 'error' => 'Missing target URL.'];
                    continue;
                }

                // 2. Validate URL scheme
                $sourceParsed = parse_url($source);
                $targetParsed = parse_url($target);

                if (!$sourceParsed || !isset($sourceParsed['scheme']) || !in_array(strtolower($sourceParsed['scheme']), ['http', 'https'], true)) {
                    $errors[] = ['row' => $rowNumber, 'url' => $source, 'error' => 'Invalid source URL format or scheme (HTTP/HTTPS required).'];
                    continue;
                }
                if (!$targetParsed || !isset($targetParsed['scheme']) || !in_array(strtolower($targetParsed['scheme']), ['http', 'https'], true)) {
                    $errors[] = ['row' => $rowNumber, 'url' => $target, 'error' => 'Invalid target URL format or scheme (HTTP/HTTPS required).'];
                    continue;
                }

                // 3. Batch deduplication key
                $dedupKey = strtolower($source . '|' . $target);
                if (isset($seenInBatch[$dedupKey])) {
                    $duplicates[] = ['row' => $rowNumber, 'source_url' => $source, 'target_url' => $target, 'reason' => 'Duplicate URL pair within import batch.'];
                    continue;
                }
                $seenInBatch[$dedupKey] = true;

                // 4. Database deduplication check in this project & campaign
                $existing = Backlink::where('project_id', $project->id)
                    ->where('campaign_id', $campaign->id)
                    ->where('source_url', $source)
                    ->where('target_url', $target)
                    ->first();

                if ($existing) {
                    $duplicates[] = ['row' => $rowNumber, 'source_url' => $source, 'target_url' => $target, 'reason' => 'Backlink already exists in this campaign.'];
                    continue;
                }

                // 5. Create Backlink record
                $backlink = Backlink::create([
                    'project_id' => $project->id,
                    'campaign_id' => $campaign->id,
                    'source_url' => $source,
                    'target_url' => $target,
                    'anchor_text' => $anchor,
                    'link_type' => 'unknown',
                    'is_live' => true,
                    'is_indexed' => false,
                    'crawl_status' => 'PENDING',
                    'index_status' => 'UNKNOWN',
                    'discovery_status' => 'PENDING',
                    'first_seen_at' => now(),
                    'last_seen_at' => now(),
                    'retry_count' => 0,
                    'max_retries' => 5,
                ]);

                // Record 'added' event in history
                $backlink->recordEvent('added', null, [
                    'source_url' => $source,
                    'target_url' => $target,
                    'anchor_text' => $anchor,
                ], 'Backlink imported into campaign.');

                $imported[] = $backlink;
            }

            DB::commit();

            return [
                'success' => true,
                'total_parsed' => count($rows),
                'total_imported' => count($imported),
                'total_duplicates' => count($duplicates),
                'total_errors' => count($errors),
                'imported' => $imported,
                'duplicates' => $duplicates,
                'errors' => $errors,
            ];
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
