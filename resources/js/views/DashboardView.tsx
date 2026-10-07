import React from 'react';
import { DashboardMetrics } from '../types';
import { MetricCard } from '../components/MetricCard';
import {
    Link2,
    ShieldCheck,
    AlertOctagon,
    CheckCircle,
    Compass,
    Radio,
    Clock,
    Flame,
    ExternalLink,
    Zap
} from 'lucide-react';

interface DashboardViewProps {
    metrics: DashboardMetrics | null;
    recentEvents: any[];
    apiUsage: any[];
    onNavigate: (tab: string) => void;
}

export const DashboardView: React.FC<DashboardViewProps> = ({
    metrics,
    recentEvents,
    apiUsage,
    onNavigate,
}) => {
    if (!metrics) {
        return (
            <div className="p-8 flex items-center justify-center min-h-[400px]">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-500"></div>
            </div>
        );
    }

    return (
        <div className="space-y-6">
            {/* Header & Principle Compliance Notice */}
            <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 p-5 rounded-2xl bg-gradient-to-r from-indigo-950/40 via-slate-900/60 to-slate-900 border border-indigo-500/20">
                <div>
                    <h2 className="text-xl font-bold text-white flex items-center gap-2">
                        Organic Search & Backlink Discovery Center
                        <span className="text-xs px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                            Enterprise Engine
                        </span>
                    </h2>
                    <p className="text-xs text-slate-400 mt-1">
                        Continuous health inspection, crawl discovery queue, search-engine property validation, and lost-link prevention.
                    </p>
                </div>
                <div className="flex items-center gap-2 shrink-0">
                    <button
                        onClick={() => onNavigate('bulk-import')}
                        className="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-md shadow-indigo-600/30 transition flex items-center gap-2"
                    >
                        <Zap className="w-3.5 h-3.5" />
                        <span>Quick Import</span>
                    </button>
                    <button
                        onClick={() => onNavigate('health-analyzer')}
                        className="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold border border-slate-700 transition flex items-center gap-2"
                    >
                        <Compass className="w-3.5 h-3.5" />
                        <span>SSRF Analyzer</span>
                    </button>
                </div>
            </div>

            {/* Primary Metrics Row */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <MetricCard
                    title="Total Backlinks"
                    value={metrics.total_backlinks}
                    subtitle="Tracked across active campaigns"
                    icon={Link2}
                    colorScheme="indigo"
                />
                <MetricCard
                    title="Live & Verified"
                    value={metrics.live_backlinks}
                    subtitle={`${Math.round((metrics.live_backlinks / (metrics.total_backlinks || 1)) * 100)}% active confirmation`}
                    icon={ShieldCheck}
                    colorScheme="emerald"
                />
                <MetricCard
                    title="Lost / Dropped"
                    value={metrics.lost_backlinks}
                    subtitle="Anchor missing or 404 page"
                    icon={AlertOctagon}
                    colorScheme="rose"
                />
                <MetricCard
                    title="Indexation Ratio"
                    value={`${metrics.index_rate}%`}
                    subtitle={`${metrics.indexed_urls} of ${metrics.total_backlinks} confirmed indexed`}
                    icon={CheckCircle}
                    colorScheme="cyan"
                />
            </div>

            {/* Crawl & Index Status Breakdown Grid */}
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {/* Status Pillars */}
                <div className="lg:col-span-2 glass-panel p-6 rounded-2xl space-y-4">
                    <div className="flex items-center justify-between border-b border-slate-800/80 pb-3">
                        <div className="flex items-center gap-2">
                            <Radio className="w-4 h-4 text-indigo-400" />
                            <h3 className="text-sm font-semibold text-white">Status Classification (Principle #2)</h3>
                        </div>
                        <span className="text-[11px] text-slate-400 font-medium">Distinct lifecycle states</span>
                    </div>

                    <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div className="p-3.5 rounded-xl bg-slate-950/60 border border-slate-800/80">
                            <div className="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Discovered</div>
                            <div className="text-2xl font-bold text-white mt-1">{metrics.crawled_urls}</div>
                            <div className="text-[10px] text-slate-500 mt-1">Found by crawler</div>
                        </div>
                        <div className="p-3.5 rounded-xl bg-slate-950/60 border border-slate-800/80">
                            <div className="text-[11px] font-semibold text-cyan-400 uppercase tracking-wider">Crawled</div>
                            <div className="text-2xl font-bold text-cyan-200 mt-1">{metrics.crawled_urls}</div>
                            <div className="text-[10px] text-slate-500 mt-1">HTTP 200 parsed</div>
                        </div>
                        <div className="p-3.5 rounded-xl bg-slate-950/60 border border-emerald-500/20 bg-emerald-500/5">
                            <div className="text-[11px] font-semibold text-emerald-400 uppercase tracking-wider">Indexed</div>
                            <div className="text-2xl font-bold text-emerald-300 mt-1">{metrics.indexed_urls}</div>
                            <div className="text-[10px] text-emerald-400/80 mt-1">Confirmed in search</div>
                        </div>
                        <div className="p-3.5 rounded-xl bg-slate-950/60 border border-amber-500/20 bg-amber-500/5">
                            <div className="text-[11px] font-semibold text-amber-400 uppercase tracking-wider">Not Indexed</div>
                            <div className="text-2xl font-bold text-amber-300 mt-1">{metrics.not_indexed_urls}</div>
                            <div className="text-[10px] text-amber-400/80 mt-1">Noindex or excluded</div>
                        </div>
                    </div>

                    {/* Operational Principle Disclaimer */}
                    <div className="p-3 rounded-xl bg-slate-900/90 border border-slate-800 text-[11px] text-slate-400 flex items-start gap-2.5">
                        <Flame className="w-4 h-4 text-amber-400 shrink-0 mt-0.5" />
                        <div>
                            <strong className="text-slate-200 font-semibold">Product Compliance Principle #1:</strong> LinkPilot SEO reports honest search engine statuses. Submission to Bing Webmaster, IndexNow, or GSC does not guarantee indexing. Search engines retain complete discretion over indexing algorithms.
                        </div>
                    </div>
                </div>

                {/* API Provider Usage & Latency */}
                <div className="glass-panel p-6 rounded-2xl space-y-4">
                    <div className="flex items-center justify-between border-b border-slate-800/80 pb-3">
                        <div className="flex items-center gap-2">
                            <Zap className="w-4 h-4 text-cyan-400" />
                            <h3 className="text-sm font-semibold text-white">Search Engine APIs</h3>
                        </div>
                        <span className="text-[11px] text-slate-400">Sanitized (Principle #9)</span>
                    </div>

                    <div className="space-y-3">
                        {apiUsage && apiUsage.length > 0 ? (
                            apiUsage.map((u, i) => (
                                <div key={i} className="p-3 rounded-xl bg-slate-950/60 border border-slate-800/80 flex items-center justify-between">
                                    <div>
                                        <div className="text-xs font-semibold text-slate-200 capitalize">
                                            {u.provider.replace('_', ' ')}
                                        </div>
                                        <div className="text-[10px] text-slate-500 mt-0.5">
                                            Avg Latency: <span className="text-slate-400 font-mono">{Math.round(u.avg_latency || 0)}ms</span>
                                        </div>
                                    </div>
                                    <span className="text-xs font-bold text-indigo-400 bg-indigo-500/10 px-2.5 py-1 rounded-lg border border-indigo-500/20">
                                        {u.count} calls
                                    </span>
                                </div>
                            ))
                        ) : (
                            <div className="text-xs text-slate-500 text-center py-6">
                                No external API requests logged yet.
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* Historical Backlink Events Timeline */}
            <div className="glass-panel p-6 rounded-2xl space-y-4">
                <div className="flex items-center justify-between border-b border-slate-800/80 pb-3">
                    <div className="flex items-center gap-2">
                        <Clock className="w-4 h-4 text-indigo-400" />
                        <h3 className="text-sm font-semibold text-white">Recent Backlink Events Timeline</h3>
                    </div>
                    <button
                        onClick={() => onNavigate('backlinks')}
                        className="text-xs font-semibold text-indigo-400 hover:text-indigo-300 flex items-center gap-1"
                    >
                        <span>View All Links</span>
                        <ExternalLink className="w-3.5 h-3.5" />
                    </button>
                </div>

                <div className="space-y-2.5">
                    {recentEvents && recentEvents.length > 0 ? (
                        recentEvents.map((evt) => {
                            const badgeConfig: Record<string, { bg: string; text: string; border: string }> = {
                                added: { bg: 'bg-indigo-500/10', text: 'text-indigo-300', border: 'border-indigo-500/30' },
                                verified: { bg: 'bg-emerald-500/10', text: 'text-emerald-300', border: 'border-emerald-500/30' },
                                lost: { bg: 'bg-rose-500/10', text: 'text-rose-300', border: 'border-rose-500/30' },
                                restored: { bg: 'bg-teal-500/10', text: 'text-teal-300', border: 'border-teal-500/30' },
                                changed: { bg: 'bg-amber-500/10', text: 'text-amber-300', border: 'border-amber-500/30' },
                            };
                            const badge = badgeConfig[evt.event_type] || { bg: 'bg-slate-700/40', text: 'text-slate-300', border: 'border-slate-600' };

                            return (
                                <div key={evt.id} className="p-3.5 rounded-xl bg-slate-950/40 border border-slate-800/70 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:border-slate-700 transition">
                                    <div className="flex items-start sm:items-center gap-3">
                                        <span className={`px-2.5 py-0.5 text-[10px] font-bold uppercase rounded-md border ${badge.bg} ${badge.text} ${badge.border} shrink-0`}>
                                            {evt.event_type}
                                        </span>
                                        <div>
                                            <div className="text-xs font-semibold text-slate-200 truncate max-w-lg">
                                                {evt.backlink?.source_url || 'Source URL'}
                                            </div>
                                            <div className="text-[11px] text-slate-400 mt-0.5">
                                                Target: <span className="text-slate-300">{evt.backlink?.target_url}</span>
                                                {evt.notes && <span className="ml-2 text-slate-500">• {evt.notes}</span>}
                                            </div>
                                        </div>
                                    </div>
                                    <div className="text-[10px] text-slate-500 shrink-0 font-mono">
                                        {new Date(evt.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' })}
                                    </div>
                                </div>
                            );
                        })
                    ) : (
                        <div className="text-xs text-slate-500 text-center py-6">
                            No backlink events recorded yet.
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
};
