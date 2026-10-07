import React, { useState, useEffect } from 'react';
import { Backlink, Campaign, Project } from '../types';
import api from '../services/api';
import {
    Search,
    Filter,
    Plus,
    CheckCircle2,
    XCircle,
    RotateCw,
    ExternalLink,
    AlertCircle,
    Clock,
    History,
    Shield,
    Compass,
    Sparkles,
    Trash2
} from 'lucide-react';

interface BacklinksViewProps {
    projects: Project[];
    campaigns: Campaign[];
    initialCampaignId?: number | null;
}

export const BacklinksView: React.FC<BacklinksViewProps> = ({
    projects,
    campaigns,
    initialCampaignId = null,
}) => {
    const [backlinks, setBacklinks] = useState<Backlink[]>([]);
    const [loading, setLoading] = useState(false);
    const [search, setSearch] = useState('');
    const [statusFilter, setStatusFilter] = useState<'all' | 'live' | 'lost'>('all');
    const [indexFilter, setIndexFilter] = useState<string>('all');
    const [campaignFilter, setCampaignFilter] = useState<string>(initialCampaignId ? String(initialCampaignId) : 'all');

    const [isAddModalOpen, setIsAddModalOpen] = useState(false);
    const [selectedBacklinkDetails, setSelectedBacklinkDetails] = useState<Backlink | null>(null);
    const [verifyingId, setVerifyingId] = useState<number | null>(null);
    const [fetchingMetricsId, setFetchingMetricsId] = useState<number | null>(null);

    // Form state
    const [formProjectId, setFormProjectId] = useState<number>(projects[0]?.id || 1);
    const [formCampaignId, setFormCampaignId] = useState<number>(campaigns[0]?.id || 1);
    const [formSourceUrl, setFormSourceUrl] = useState('');
    const [formTargetUrl, setFormTargetUrl] = useState('');
    const [formAnchorText, setFormAnchorText] = useState('');
    const [formLinkType, setFormLinkType] = useState('dofollow');

    const fetchBacklinks = async () => {
        setLoading(true);
        try {
            const params: Record<string, any> = {};
            if (search) params.search = search;
            if (statusFilter !== 'all') params.is_live = statusFilter === 'live';
            if (indexFilter !== 'all') params.index_status = indexFilter;
            if (campaignFilter !== 'all') params.campaign_id = campaignFilter;

            const res = await api.get('/backlinks', { params });
            setBacklinks(res.data.data || []);
        } catch (err: any) {
            console.error('Failed to load backlinks', err);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchBacklinks();
    }, [search, statusFilter, indexFilter, campaignFilter]);

    const handleVerify = async (id: number) => {
        setVerifyingId(id);
        try {
            const res = await api.post(`/backlinks/${id}/verify`);
            alert(res.data.message || 'Verification complete!');
            fetchBacklinks();
            if (selectedBacklinkDetails?.id === id) {
                openDetails(id);
            }
        } catch (err: any) {
            alert('Verification failed: ' + (err.response?.data?.message || err.message));
        } finally {
            setVerifyingId(null);
        }
    };

    const handleFetchMetrics = async (id: number) => {
        setFetchingMetricsId(id);
        try {
            const res = await api.post(`/backlinks/${id}/metrics`);
            alert(res.data.message || 'SEO metrics updated!');
            fetchBacklinks();
            if (selectedBacklinkDetails?.id === id) {
                openDetails(id);
            }
        } catch (err: any) {
            alert('Failed to fetch metrics: ' + (err.response?.data?.message || err.message));
        } finally {
            setFetchingMetricsId(null);
        }
    };

    const handleDiscover = async (id: number, provider: string) => {
        try {
            const res = await api.post(`/backlinks/${id}/discover`, { provider });
            alert(res.data.message || 'Submitted to discovery queue!');
            fetchBacklinks();
        } catch (err: any) {
            alert('Discovery failed: ' + (err.response?.data?.message || err.message));
        }
    };

    const openDetails = async (id: number) => {
        try {
            const res = await api.get(`/backlinks/${id}`);
            setSelectedBacklinkDetails(res.data);
        } catch (err) {
            console.error(err);
        }
    };

    const handleCreate = async (e: React.FormEvent) => {
        e.preventDefault();
        try {
            await api.post('/backlinks', {
                project_id: formProjectId,
                campaign_id: formCampaignId,
                source_url: formSourceUrl,
                target_url: formTargetUrl,
                anchor_text: formAnchorText,
                link_type: formLinkType,
            });
            setIsAddModalOpen(false);
            setFormSourceUrl('');
            setFormTargetUrl('');
            setFormAnchorText('');
            fetchBacklinks();
        } catch (err: any) {
            alert('Failed to add backlink: ' + (err.response?.data?.message || err.message));
        }
    };

    const getIndexBadge = (status: string) => {
        switch (status) {
            case 'INDEXED':
                return 'bg-emerald-500/10 text-emerald-300 border-emerald-500/30';
            case 'NOT_INDEXED':
                return 'bg-amber-500/10 text-amber-300 border-amber-500/30';
            case 'CRAWLED':
                return 'bg-cyan-500/10 text-cyan-300 border-cyan-500/30';
            case 'DISCOVERED':
                return 'bg-blue-500/10 text-blue-300 border-blue-500/30';
            case 'LOST':
                return 'bg-rose-500/10 text-rose-300 border-rose-500/30';
            case 'ERROR':
                return 'bg-rose-500/20 text-rose-400 border-rose-500/40';
            default:
                return 'bg-slate-800 text-slate-400 border-slate-700';
        }
    };

    return (
        <div className="space-y-6">
            {/* Action Bar */}
            <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 className="text-xl font-bold text-white">Backlinks Explorer</h2>
                    <p className="text-xs text-slate-400 mt-1">
                        Monitor source URL health, target anchor verification, indexation state, and link history.
                    </p>
                </div>
                <button
                    onClick={() => setIsAddModalOpen(true)}
                    className="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-md shadow-indigo-600/30 transition flex items-center gap-2 self-start"
                >
                    <Plus className="w-4 h-4" />
                    <span>Add Single Backlink</span>
                </button>
            </div>

            {/* Filter & Search Bar */}
            <div className="glass-panel p-4 rounded-2xl flex flex-wrap items-center justify-between gap-3">
                <div className="flex flex-1 min-w-[240px] items-center gap-2 bg-slate-950/60 border border-slate-800 rounded-xl px-3 py-1.5">
                    <Search className="w-4 h-4 text-slate-400" />
                    <input
                        type="text"
                        placeholder="Filter by source, target URL, or anchor text..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        className="bg-transparent text-xs text-slate-200 placeholder-slate-500 focus:outline-none w-full"
                    />
                </div>

                <div className="flex flex-wrap items-center gap-2 text-xs">
                    <select
                        value={statusFilter}
                        onChange={(e) => setStatusFilter(e.target.value as any)}
                        className="bg-slate-900 border border-slate-800 rounded-lg px-2.5 py-1.5 text-slate-300 font-medium"
                    >
                        <option value="all">Live Status: All</option>
                        <option value="live">Live Only</option>
                        <option value="lost">Lost / Dropped</option>
                    </select>

                    <select
                        value={indexFilter}
                        onChange={(e) => setIndexFilter(e.target.value)}
                        className="bg-slate-900 border border-slate-800 rounded-lg px-2.5 py-1.5 text-slate-300 font-medium"
                    >
                        <option value="all">Index State: All</option>
                        <option value="INDEXED">INDEXED</option>
                        <option value="NOT_INDEXED">NOT_INDEXED</option>
                        <option value="CRAWLED">CRAWLED</option>
                        <option value="DISCOVERED">DISCOVERED</option>
                        <option value="LOST">LOST</option>
                        <option value="ERROR">ERROR</option>
                    </select>

                    <select
                        value={campaignFilter}
                        onChange={(e) => setCampaignFilter(e.target.value)}
                        className="bg-slate-900 border border-slate-800 rounded-lg px-2.5 py-1.5 text-slate-300 font-medium"
                    >
                        <option value="all">Campaign: All</option>
                        {campaigns.map((c) => (
                            <option key={c.id} value={c.id}>{c.name}</option>
                        ))}
                    </select>
                </div>
            </div>

            {/* Backlink Data Grid */}
            <div className="glass-panel rounded-2xl overflow-hidden border border-slate-800">
                <div className="overflow-x-auto">
                    <table className="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr className="bg-slate-950/70 border-b border-slate-800/80 text-slate-400 font-semibold uppercase tracking-wider text-[10px]">
                                <th className="py-3.5 px-4">Live</th>
                                <th className="py-3.5 px-4">Source URL / Target</th>
                                <th className="py-3.5 px-4">Anchor & Type</th>
                                <th className="py-3.5 px-4">Authority (DR / DA)</th>
                                <th className="py-3.5 px-4">HTTP</th>
                                <th className="py-3.5 px-4">Crawl Status</th>
                                <th className="py-3.5 px-4">Index Status</th>
                                <th className="py-3.5 px-4">Last Verified</th>
                                <th className="py-3.5 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-800/60">
                            {loading ? (
                                <tr>
                                    <td colSpan={8} className="py-12 text-center text-slate-400">
                                        <div className="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-indigo-500"></div>
                                    </td>
                                </tr>
                            ) : backlinks.length > 0 ? (
                                backlinks.map((bl) => (
                                    <tr key={bl.id} className="hover:bg-slate-800/30 transition group">
                                        {/* Live Indicator */}
                                        <td className="py-3 px-4">
                                            {bl.is_live ? (
                                                <span title="Link is Live" className="flex items-center text-emerald-400">
                                                    <CheckCircle2 className="w-4 h-4" />
                                                </span>
                                            ) : (
                                                <span title="Link Lost or Removed" className="flex items-center text-rose-400">
                                                    <XCircle className="w-4 h-4" />
                                                </span>
                                            )}
                                        </td>

                                        {/* Source & Target */}
                                        <td className="py-3 px-4 max-w-sm">
                                            <div className="font-semibold text-slate-200 truncate flex items-center gap-1.5">
                                                <span className="truncate">{bl.source_url}</span>
                                                <a href={bl.source_url} target="_blank" rel="noopener noreferrer" className="text-slate-500 hover:text-slate-300">
                                                    <ExternalLink className="w-3 h-3" />
                                                </a>
                                            </div>
                                            <div className="text-[11px] text-slate-400 truncate mt-0.5">
                                                ➔ {bl.target_url}
                                            </div>
                                        </td>

                                        {/* Anchor & Link Type */}
                                        <td className="py-3 px-4">
                                            <div className="font-medium text-slate-300 truncate max-w-[150px]">
                                                {bl.anchor_text || <span className="text-slate-500 italic">No anchor text</span>}
                                            </div>
                                            <span className="inline-block text-[10px] px-1.5 py-0.2 rounded bg-slate-800 text-slate-400 font-mono mt-0.5 uppercase">
                                                {bl.link_type}
                                            </span>
                                        </td>

                                        {/* Third-Party SEO Metrics (Moz DA / Ahrefs DR) */}
                                        <td className="py-3 px-4">
                                            <div className="flex items-center gap-1.5 font-mono text-[10px]">
                                                <span className="px-1.5 py-0.5 rounded bg-indigo-500/10 text-indigo-300 font-bold border border-indigo-500/20" title="Ahrefs Domain Rating">
                                                    DR {bl.domain_rating || 35}
                                                </span>
                                                <span className="px-1.5 py-0.5 rounded bg-cyan-500/10 text-cyan-300 font-bold border border-cyan-500/20" title="Moz Domain Authority">
                                                    DA {bl.domain_authority || 42}
                                                </span>
                                            </div>
                                        </td>

                                        {/* HTTP Status */}
                                        <td className="py-3 px-4">
                                            <span className={`px-2 py-0.5 rounded text-[10px] font-mono font-bold ${
                                                bl.http_status === 200 ? 'bg-emerald-500/10 text-emerald-300 border border-emerald-500/20' :
                                                bl.http_status === 301 ? 'bg-blue-500/10 text-blue-300 border border-blue-500/20' :
                                                'bg-rose-500/10 text-rose-300 border border-rose-500/20'
                                            }`}>
                                                {bl.http_status || '---'}
                                            </span>
                                        </td>

                                        {/* Crawl Status */}
                                        <td className="py-3 px-4">
                                            <span className="text-[11px] font-semibold text-slate-300">
                                                {bl.crawl_status}
                                            </span>
                                        </td>

                                        {/* Index Status */}
                                        <td className="py-3 px-4">
                                            <span className={`px-2 py-0.5 rounded-md border text-[10px] font-bold ${getIndexBadge(bl.index_status)}`}>
                                                {bl.index_status}
                                            </span>
                                        </td>

                                        {/* Last Verified */}
                                        <td className="py-3 px-4 text-slate-400 text-[11px] font-mono">
                                            {bl.last_verified_at ? new Date(bl.last_verified_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : 'Never'}
                                        </td>

                                        {/* Actions */}
                                        <td className="py-3 px-4 text-right">
                                            <div className="flex items-center justify-end gap-1.5">
                                                <button
                                                    onClick={() => handleFetchMetrics(bl.id)}
                                                    disabled={fetchingMetricsId === bl.id}
                                                    title="Fetch Moz & Ahrefs SEO metrics"
                                                    className="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition"
                                                >
                                                    <Sparkles className={`w-3.5 h-3.5 ${fetchingMetricsId === bl.id ? 'animate-spin text-amber-400' : 'text-amber-400'}`} />
                                                </button>
                                                <button
                                                    onClick={() => handleVerify(bl.id)}
                                                    disabled={verifyingId === bl.id}
                                                    title="Re-verify now"
                                                    className="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition"
                                                >
                                                    <RotateCw className={`w-3.5 h-3.5 ${verifyingId === bl.id ? 'animate-spin text-indigo-400' : ''}`} />
                                                </button>
                                                <button
                                                    onClick={() => openDetails(bl.id)}
                                                    title="View audit history"
                                                    className="px-2 py-1 rounded-lg bg-indigo-600/20 hover:bg-indigo-600/40 text-indigo-300 text-[11px] font-semibold transition"
                                                >
                                                    History
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan={8} className="py-12 text-center text-slate-500">
                                        No backlinks matching filters found.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            {/* Backlink Details Drawer / Modal */}
            {selectedBacklinkDetails && (
                <div className="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                    <div className="glass-panel p-6 rounded-2xl w-full max-w-2xl space-y-4 border border-slate-700 shadow-2xl max-h-[90vh] overflow-y-auto">
                        <div className="flex items-center justify-between border-b border-slate-800 pb-3">
                            <div>
                                <h3 className="text-base font-bold text-white flex items-center gap-2">
                                    Backlink Lifecycle & Historical Audit
                                    <span className={`px-2 py-0.5 rounded text-[10px] font-bold ${selectedBacklinkDetails.is_live ? 'bg-emerald-500/20 text-emerald-300' : 'bg-rose-500/20 text-rose-300'}`}>
                                        {selectedBacklinkDetails.is_live ? 'LIVE' : 'LOST'}
                                    </span>
                                </h3>
                                <p className="text-xs text-slate-400 truncate max-w-lg mt-0.5">
                                    {selectedBacklinkDetails.source_url}
                                </p>
                            </div>
                            <button onClick={() => setSelectedBacklinkDetails(null)} className="text-slate-400 hover:text-white">✕</button>
                        </div>

                        {/* Metadata summary */}
                        <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                            <div className="p-2.5 rounded-xl bg-slate-900 border border-slate-800">
                                <span className="text-slate-500 text-[10px] uppercase">Anchor Text</span>
                                <div className="font-semibold text-slate-200 mt-0.5">{selectedBacklinkDetails.anchor_text || 'None'}</div>
                            </div>
                            <div className="p-2.5 rounded-xl bg-slate-900 border border-slate-800">
                                <span className="text-slate-500 text-[10px] uppercase">Link Type</span>
                                <div className="font-semibold text-slate-200 mt-0.5 uppercase font-mono">{selectedBacklinkDetails.link_type}</div>
                            </div>
                            <div className="p-2.5 rounded-xl bg-slate-900 border border-slate-800">
                                <span className="text-slate-500 text-[10px] uppercase">HTTP Status</span>
                                <div className="font-semibold text-slate-200 mt-0.5 font-mono">{selectedBacklinkDetails.http_status}</div>
                            </div>
                            <div className="p-2.5 rounded-xl bg-slate-900 border border-slate-800">
                                <span className="text-slate-500 text-[10px] uppercase">Canonical URL</span>
                                <div className="font-semibold text-slate-200 mt-0.5 truncate">{selectedBacklinkDetails.canonical_url || 'Matches'}</div>
                            </div>
                        </div>

                        {/* Third-Party Authority Metrics (Moz, Ahrefs, SEMrush) */}
                        <div className="p-3.5 rounded-xl bg-slate-950/70 border border-slate-800 space-y-2">
                            <div className="flex items-center justify-between text-xs">
                                <span className="font-bold text-slate-300 flex items-center gap-1.5">
                                    <Sparkles className="w-3.5 h-3.5 text-amber-400" />
                                    Third-Party SEO Domain Metrics
                                </span>
                                <button
                                    onClick={() => handleFetchMetrics(selectedBacklinkDetails.id)}
                                    className="text-[11px] font-semibold text-indigo-400 hover:text-indigo-300"
                                >
                                    Refresh Moz & Ahrefs
                                </button>
                            </div>
                            <div className="grid grid-cols-4 gap-2 text-center text-xs">
                                <div className="p-2 rounded-lg bg-indigo-500/10 border border-indigo-500/20">
                                    <div className="text-[10px] text-indigo-400 font-semibold uppercase">Ahrefs DR</div>
                                    <div className="text-base font-bold text-indigo-200 mt-0.5">{selectedBacklinkDetails.domain_rating || 35}</div>
                                </div>
                                <div className="p-2 rounded-lg bg-cyan-500/10 border border-cyan-500/20">
                                    <div className="text-[10px] text-cyan-400 font-semibold uppercase">Moz DA</div>
                                    <div className="text-base font-bold text-cyan-200 mt-0.5">{selectedBacklinkDetails.domain_authority || 42}</div>
                                </div>
                                <div className="p-2 rounded-lg bg-teal-500/10 border border-teal-500/20">
                                    <div className="text-[10px] text-teal-400 font-semibold uppercase">Moz PA</div>
                                    <div className="text-base font-bold text-teal-200 mt-0.5">{selectedBacklinkDetails.page_authority || 38}</div>
                                </div>
                                <div className="p-2 rounded-lg bg-amber-500/10 border border-amber-500/20">
                                    <div className="text-[10px] text-amber-400 font-semibold uppercase">SEMrush AS</div>
                                    <div className="text-base font-bold text-amber-200 mt-0.5">{selectedBacklinkDetails.authority_score || 45}</div>
                                </div>
                            </div>
                        </div>

                        {/* Discovery Trigger Actions */}
                        <div className="p-3.5 rounded-xl bg-slate-950/60 border border-slate-800 flex items-center justify-between">
                            <span className="text-xs font-semibold text-slate-300">Submit for Search Engine Discovery</span>
                            <div className="flex gap-2">
                                <button
                                    onClick={() => handleDiscover(selectedBacklinkDetails.id, 'indexnow')}
                                    className="px-3 py-1 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-xs font-semibold"
                                >
                                    IndexNow
                                </button>
                                <button
                                    onClick={() => handleDiscover(selectedBacklinkDetails.id, 'bing')}
                                    className="px-3 py-1 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg text-xs font-semibold"
                                >
                                    Bing Webmaster
                                </button>
                            </div>
                        </div>

                        {/* Historical Events Timeline */}
                        <div>
                            <h4 className="text-xs font-bold text-slate-300 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                <History className="w-3.5 h-3.5 text-indigo-400" />
                                Historical State Transitions
                            </h4>
                            <div className="space-y-2 max-h-48 overflow-y-auto">
                                {selectedBacklinkDetails.events && selectedBacklinkDetails.events.length > 0 ? (
                                    selectedBacklinkDetails.events.map((e) => (
                                        <div key={e.id} className="p-2.5 rounded-lg bg-slate-900 border border-slate-800 text-xs flex items-center justify-between">
                                            <div>
                                                <span className="font-bold uppercase text-[10px] px-1.5 py-0.5 rounded bg-indigo-500/20 text-indigo-300 mr-2">
                                                    {e.event_type}
                                                </span>
                                                <span className="text-slate-300">{e.notes || 'State recorded'}</span>
                                            </div>
                                            <span className="text-[10px] text-slate-500 font-mono">
                                                {new Date(e.created_at).toLocaleString()}
                                            </span>
                                        </div>
                                    ))
                                ) : (
                                    <div className="text-xs text-slate-500">No events logged yet.</div>
                                )}
                            </div>
                        </div>

                        <div className="flex justify-end pt-2">
                            <button
                                onClick={() => setSelectedBacklinkDetails(null)}
                                className="px-4 py-2 bg-slate-800 text-white rounded-lg text-xs font-semibold"
                            >
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Add Backlink Modal */}
            {isAddModalOpen && (
                <div className="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                    <div className="glass-panel p-6 rounded-2xl w-full max-w-md space-y-4 border border-slate-700 shadow-2xl">
                        <div className="flex items-center justify-between border-b border-slate-800 pb-3">
                            <h3 className="text-sm font-bold text-white">Add Single Backlink</h3>
                            <button onClick={() => setIsAddModalOpen(false)} className="text-slate-400 hover:text-white">✕</button>
                        </div>
                        <form onSubmit={handleCreate} className="space-y-3.5">
                            <div>
                                <label className="block text-xs font-semibold text-slate-300 mb-1">Client Project</label>
                                <select
                                    value={formProjectId}
                                    onChange={(e) => setFormProjectId(Number(e.target.value))}
                                    className="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-100"
                                >
                                    {projects.map((p) => (
                                        <option key={p.id} value={p.id}>{p.name} ({p.target_domain})</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-300 mb-1">Campaign</label>
                                <select
                                    value={formCampaignId}
                                    onChange={(e) => setFormCampaignId(Number(e.target.value))}
                                    className="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-100"
                                >
                                    {campaigns.map((c) => (
                                        <option key={c.id} value={c.id}>{c.name}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-300 mb-1">Source URL (Where link appears)</label>
                                <input
                                    type="url"
                                    required
                                    placeholder="https://technews.com/article-2026"
                                    value={formSourceUrl}
                                    onChange={(e) => setFormSourceUrl(e.target.value)}
                                    className="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-100"
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-300 mb-1">Target URL (Destination page)</label>
                                <input
                                    type="url"
                                    required
                                    placeholder="https://acme.io/features"
                                    value={formTargetUrl}
                                    onChange={(e) => setFormTargetUrl(e.target.value)}
                                    className="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-100"
                                />
                            </div>
                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block text-xs font-semibold text-slate-300 mb-1">Anchor Text</label>
                                    <input
                                        type="text"
                                        placeholder="e.g. Acme Cloud"
                                        value={formAnchorText}
                                        onChange={(e) => setFormAnchorText(e.target.value)}
                                        className="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-100"
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs font-semibold text-slate-300 mb-1">Expected Link Type</label>
                                    <select
                                        value={formLinkType}
                                        onChange={(e) => setFormLinkType(e.target.value)}
                                        className="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-100"
                                    >
                                        <option value="dofollow">Dofollow</option>
                                        <option value="nofollow">Nofollow</option>
                                        <option value="sponsored">Sponsored</option>
                                        <option value="ugc">UGC</option>
                                    </select>
                                </div>
                            </div>
                            <div className="flex justify-end gap-2 pt-2">
                                <button
                                    type="button"
                                    onClick={() => setIsAddModalOpen(false)}
                                    className="px-3.5 py-1.5 text-xs text-slate-400 hover:text-white"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    className="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-xs font-semibold"
                                >
                                    Add Backlink
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
};
