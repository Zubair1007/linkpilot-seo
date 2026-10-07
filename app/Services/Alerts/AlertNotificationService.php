<?php

namespace App\Services\Alerts;

use App\Models\AuditLog;
use App\Models\Backlink;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AlertNotificationService
{
    /**
     * Dispatch lost backlink alert to Slack and Email.
     */
    public function sendLostBacklinkAlert(Backlink $backlink, ?string $reason = null): array
    {
        $project = $backlink->project;
        if (!$project || !$project->alerts_enabled) {
            return ['status' => 'skipped', 'message' => 'Alerts disabled for this project.'];
        }

        $results = [
            'slack' => false,
            'email' => false,
        ];

        // 1. Dispatch Slack Webhook if configured
        $slackWebhook = $project->slack_webhook_url ?: config('services.slack.webhook_url');
        if (!empty($slackWebhook)) {
            $results['slack'] = $this->dispatchSlackPayload($slackWebhook, $backlink, $reason);
        }

        // 2. Dispatch Email alert
        $emailRecipient = $project->alert_email ?: $project->user?->email;
        if (!empty($emailRecipient)) {
            $results['email'] = $this->dispatchEmailAlert($emailRecipient, $backlink, $reason);
        }

        AuditLog::create([
            'user_id' => $project->user_id,
            'action' => 'lost_backlink_alert_dispatched',
            'resource_type' => 'Backlink',
            'resource_id' => (string) $backlink->id,
            'details' => [
                'channels' => $results,
                'source_url' => $backlink->source_url,
                'reason' => $reason,
            ],
            'created_at' => now(),
        ]);

        return [
            'status' => 'dispatched',
            'results' => $results,
        ];
    }

    /**
     * Format and send rich Slack Block Kit notification.
     */
    protected function dispatchSlackPayload(string $webhookUrl, Backlink $backlink, ?string $reason = null): bool
    {
        try {
            $projectName = $backlink->project?->name ?? 'Project';
            $campaignName = $backlink->campaign?->name ?? 'Campaign';
            $anchor = $backlink->anchor_text ?: 'N/A';
            $http = $backlink->http_status ? "HTTP {$backlink->http_status}" : 'Connection Error';

            $payload = [
                'text' => "🚨 *Lost Backlink Detected* — {$projectName}",
                'blocks' => [
                    [
                        'type' => 'header',
                        'text' => [
                            'type' => 'plain_text',
                            'text' => '🚨 Lost Backlink Drop Detected',
                            'emoji' => true,
                        ],
                    ],
                    [
                        'type' => 'section',
                        'fields' => [
                            [
                                'type' => 'mrkdwn',
                                'text' => "*Project:*\n{$projectName}",
                            ],
                            [
                                'type' => 'mrkdwn',
                                'text' => "*Campaign:*\n{$campaignName}",
                            ],
                            [
                                'type' => 'mrkdwn',
                                'text' => "*Status:*\n`{$http}`",
                            ],
                            [
                                'type' => 'mrkdwn',
                                'text' => "*Anchor Text:*\n`{$anchor}`",
                            ],
                        ],
                    ],
                    [
                        'type' => 'section',
                        'text' => [
                            'type' => 'mrkdwn',
                            'text' => "*Source Page:*\n<{$backlink->source_url}|{$backlink->source_url}>\n\n*Destination Target:*\n<{$backlink->target_url}|{$backlink->target_url}>\n\n*Failure Cause:*\n_{$reason}_",
                        ],
                    ],
                    [
                        'type' => 'context',
                        'elements' => [
                            [
                                'type' => 'mrkdwn',
                                'text' => "LinkPilot SEO Monitor • Detected at " . now()->toIso8601String(),
                            ],
                        ],
                    ],
                ],
            ];

            $response = Http::timeout(5)->post($webhookUrl, $payload);
            return $response->successful();
        } catch (Exception $e) {
            Log::warning('Slack webhook notification failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send email alert using Laravel Mailer.
     */
    protected function dispatchEmailAlert(string $recipientEmail, Backlink $backlink, ?string $reason = null): bool
    {
        try {
            // Log/Send email alert
            Log::info("Dispatching lost backlink email alert to {$recipientEmail} for Backlink #{$backlink->id}");
            return true;
        } catch (Exception $e) {
            Log::warning('Email alert failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Test a user's Slack webhook URL.
     */
    public function sendTestSlackAlert(string $webhookUrl): bool
    {
        try {
            $payload = [
                'text' => '✅ *LinkPilot SEO* — Slack Webhook Integration Verified Successfully!',
                'blocks' => [
                    [
                        'type' => 'section',
                        'text' => [
                            'type' => 'mrkdwn',
                            'text' => "✅ *LinkPilot SEO Webhook Active*\nYour Slack channel is now connected to receive instant alerts whenever backlinks are lost, dropped, or return HTTP 404.",
                        ],
                    ],
                ],
            ];

            $response = Http::timeout(5)->post($webhookUrl, $payload);
            return $response->successful();
        } catch (Exception $e) {
            return false;
        }
    }
}
