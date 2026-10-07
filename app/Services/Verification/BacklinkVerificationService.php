<?php

namespace App\Services\Verification;

use App\Models\Backlink;
use App\Models\BacklinkEvent;
use App\Services\Analysis\UrlHealthAnalyzerService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BacklinkVerificationService
{
    public function __construct(
        protected UrlHealthAnalyzerService $analyzerService
    ) {}

    /**
     * Verify a backlink, update status, and record historical events.
     */
    public function verifyBacklink(Backlink $backlink): array
    {
        $oldState = [
            'is_live' => $backlink->is_live,
            'http_status' => $backlink->http_status,
            'anchor_text' => $backlink->anchor_text,
            'link_type' => $backlink->link_type,
            'final_url' => $backlink->final_url,
            'canonical_url' => $backlink->canonical_url,
            'indexability' => $backlink->indexability,
            'crawl_status' => $backlink->crawl_status,
            'index_status' => $backlink->index_status,
        ];

        $result = $this->analyzerService->analyze($backlink);

        DB::beginTransaction();
        try {
            $isLive = $result['is_live'];
            $httpStatus = $result['http_status'];
            $newAnchor = $result['anchor_text'] ?? $backlink->anchor_text;
            $newLinkType = ($result['link_type'] !== 'unknown') ? $result['link_type'] : $backlink->link_type;
            $newFinalUrl = $result['final_url'];
            $newCanonicalUrl = $result['canonical_url'];
            $newIndexability = $result['indexability'];
            $robotsStatus = $result['robots_status'];

            // Determine updated crawl_status
            $newCrawlStatus = ($httpStatus && $httpStatus >= 200 && $httpStatus < 400) ? 'CRAWLED' : ($result['error'] ? 'ERROR' : 'DISCOVERED');

            // Handle Lost / Restored / Changed / Verified events
            $eventsLogged = [];

            if (!$isLive && $oldState['is_live']) {
                // Backlink was live, now lost!
                $lossReason = $result['error'] ?? 'Backlink anchor not found in page HTML';
                $backlink->recordEvent('lost', $oldState, [
                    'is_live' => false,
                    'http_status' => $httpStatus,
                    'error' => $lossReason,
                ], 'Backlink target URL was not found in source HTML during verification.');
                $eventsLogged[] = 'lost';
                $backlink->index_status = 'LOST';

                // Dispatch Email & Slack alert
                app(\App\Services\Alerts\AlertNotificationService::class)->sendLostBacklinkAlert($backlink, $lossReason);
            } elseif ($isLive && !$oldState['is_live']) {
                // Backlink was lost, now restored!
                $backlink->recordEvent('restored', $oldState, [
                    'is_live' => true,
                    'http_status' => $httpStatus,
                    'anchor_text' => $newAnchor,
                ], 'Backlink was successfully rediscovered on the target page.');
                $eventsLogged[] = 'restored';
                if ($backlink->index_status === 'LOST') {
                    $backlink->index_status = 'CRAWLED';
                }
            } else {
                // Check if attributes changed
                $changes = [];
                if ($oldState['anchor_text'] !== null && $newAnchor !== null && $oldState['anchor_text'] !== $newAnchor) {
                    $changes['anchor_text'] = ['from' => $oldState['anchor_text'], 'to' => $newAnchor];
                }
                if ($oldState['link_type'] !== 'unknown' && $newLinkType !== 'unknown' && $oldState['link_type'] !== $newLinkType) {
                    $changes['link_type'] = ['from' => $oldState['link_type'], 'to' => $newLinkType];
                }
                if ($oldState['http_status'] !== null && $httpStatus !== null && $oldState['http_status'] !== $httpStatus) {
                    $changes['http_status'] = ['from' => $oldState['http_status'], 'to' => $httpStatus];
                }

                if (!empty($changes)) {
                    $backlink->recordEvent('changed', $oldState, $changes, 'Backlink properties changed during verification.');
                    $eventsLogged[] = 'changed';
                } else {
                    $backlink->recordEvent('verified', null, ['http_status' => $httpStatus, 'is_live' => $isLive], 'Routine verification check passed.');
                    $eventsLogged[] = 'verified';
                }
            }

            // Update Backlink attributes
            $backlink->is_live = $isLive;
            $backlink->http_status = $httpStatus;
            $backlink->anchor_text = $newAnchor;
            $backlink->link_type = $newLinkType;
            $backlink->final_url = $newFinalUrl;
            $backlink->canonical_url = $newCanonicalUrl;
            $backlink->robots_status = $robotsStatus;
            $backlink->indexability = $newIndexability;
            $backlink->crawl_status = $newCrawlStatus;
            $backlink->last_verified_at = now();
            $backlink->last_crawled_at = now();
            if ($isLive) {
                $backlink->last_seen_at = now();
            }

            if ($result['error']) {
                $backlink->last_error = $result['error'];
            } else {
                $backlink->last_error = null;
                $backlink->retry_count = 0;
            }

            $backlink->save();

            DB::commit();

            return [
                'success' => true,
                'backlink' => $backlink->fresh(),
                'events' => $eventsLogged,
                'analysis' => $result,
            ];
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Failed to verify backlink #{$backlink->id}: " . $e->getMessage());

            $backlink->increment('retry_count');
            $backlink->last_error = $e->getMessage();
            $backlink->save();

            throw $e;
        }
    }
}
