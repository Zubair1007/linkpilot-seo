import React, { useState } from 'react';
import { Campaign, Project } from '../types';
import api from '../services/api';
import { Target, Play, Clock, BarChart3, Plus, Trash2, CheckCircle2, AlertTriangle, Download } from 'lucide-react';

interface CampaignsViewProps {
    campaigns: Campaign[];
    projects: Project[];
    onRefresh: () => void;
    onSelectCampaign: (campaignId: number) => void;
}

export const CampaignsView: React.FC<CampaignsViewProps> = ({
    campaigns,
    projects,
    onRefresh,
    onSelectCampaign,
}) => {
    const [isCreatingCampaign, setIsCreatingCampaign] = useState(false);
    const [selectedProjectId, setSelectedProjectId] = useState<number>(projects[0]?.id || 1);
    const [name, setName] = useState('');
    const [description, setDescription] = useState('');
    const [checkFrequency, setCheckFrequency] = useState<'24h' | '72h' | '7d' | '14d' | '30d'>('24h');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [activeStatusModal, setActiveStatusModal] = useState<any | null>(null);

    const handleCreateCampaign = async (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);
        try {
            await api.post('/campaigns', {
                project_id: selectedProjectId,
                name,
                description,
                check_frequency: checkFrequency,
            });
            setIsCreatingCampaign(false);
            setName('');
            setDescription('');
            onRefresh();
        } catch (err: any) {
            alert('Failed to create campaign: ' + (err.response?.data?.message || err.message));
        } finally {
            setIsSubmitting(false);
        }
    };

    const handleTriggerRun = async (campaignId: number) => {
        try {
            const res = await api.post(`/campaigns/${campaignId}/run`);
            alert(res.data.message || 'Verification queue triggered.');
            onRefresh();
        } catch (err: any) {
            alert('Failed to trigger queue: ' + (err.response?.data?.message || err.message));
        }
    };

    const handleOpenStatus = async (campaignId: number) => {
        try {
            const res = await api.get(`/campaigns/${campaignId}/status`);
            setActiveStatusModal(res.data);
        } catch (err: any) {
            alert('Failed to load campaign status: ' + (err.response?.data?.message || err.message));
        }
    };

    return (
        <div className="space-y-6">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 className="text-xl font-bold text-white">Monitoring Campaigns</h2>
                    <p className="text-xs text-slate-400 mt-1">
                        Automated crawl schedules (24h, 72h, 7d, 14d, 30d) with queue throttling and index tracking.
                    </p>
                </div>
                <button
                    onClick={() => setIsCreatingCampaign(true)}
                    className="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-md shadow-indigo-600/30 transition flex items-center gap-2 self-start"
                >
                    <Plus className="w-4 h-4" />
                    <span>Create Campaign</span>
                </button>
            </div>

            {/* Campaign Cards Grid */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {campaigns.map((camp) => (
                    <div key={camp.id} className="glass-panel p-6 rounded-2xl space-y-4 hover:border-slate-700 transition">
                        <div className="flex items-start justify-between">
                            <div>
                                <span className="text-[10px] uppercase font-bold text-indigo-400 tracking-wider">
                                    {camp.project?.name || 'Project'}
                                </span>
                                <h3 className="text-base font-bold text-white mt-0.5">{camp.name}</h3>
                                {camp.description && (
                                    <p className="text-xs text-slate-400 mt-1">{camp.description}</p>
                                )}
                            </div>
                            <span className="px-2.5 py-1 rounded-lg bg-indigo-500/10 text-indigo-300 border border-indigo-500/20 text-xs font-semibold flex items-center gap-1.5">
                                <Clock className="w-3.5 h-3.5" />
                                {camp.check_frequency} cycle
                            </span>
                        </div>

                        {/* Metric Bar */}
                        <div className="p-3.5 rounded-xl bg-slate-950/60 border border-slate-800/80 grid grid-cols-3 gap-2 text-center">
                            <div>
                                <div className="text-[10px] text-slate-400 uppercase font-semibold">Total URLs</div>
                                <div className="text-lg font-bold text-white mt-0.5">{camp.backlinks_count || 0}</div>
                            </div>
                            <div>
                                <div className="text-[10px] text-slate-400 uppercase font-semibold">Next Scheduled</div>
                                <div className="text-xs font-semibold text-slate-300 mt-1">
                                    {camp.next_run_at ? new Date(camp.next_run_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : 'Pending'}
                                </div>
                            </div>
                            <div>
                                <div className="text-[10px] text-slate-400 uppercase font-semibold">Engine State</div>
                                <div className="text-xs font-semibold text-emerald-400 mt-1 flex items-center justify-center gap-1">
                                    <CheckCircle2 className="w-3 h-3" />
                                    Active
                                </div>
                            </div>
                        </div>

                        {/* Actions */}
                        <div className="flex items-center justify-between pt-2">
                            <button
                                onClick={() => handleOpenStatus(camp.id)}
                                className="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold flex items-center gap-1.5 transition"
                            >
                                <BarChart3 className="w-3.5 h-3.5 text-indigo-400" />
                                <span>Analytics & Status</span>
                            </button>

                            <div className="flex items-center gap-2">
                                <button
                                    onClick={() => onSelectCampaign(camp.id)}
                                    className="px-3 py-1.5 rounded-lg bg-slate-800/80 hover:bg-slate-700 text-slate-300 text-xs font-semibold transition"
                                >
                                    Browse Links
                                </button>
                                <button
                                    onClick={() => handleTriggerRun(camp.id)}
                                    className="px-3.5 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-sm transition flex items-center gap-1.5"
                                >
                                    <Play className="w-3 h-3 fill-current" />
                                    <span>Run Queue Now</span>
                                </button>
                            </div>
                        </div>
                    </div>
                ))}
            </div>

            {/* Campaign Detailed Status Modal */}
            {activeStatusModal && (
                <div className="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                    <div className="glass-panel p-6 rounded-2xl w-full max-w-2xl space-y-4 border border-slate-700 shadow-2xl max-h-[90vh] overflow-y-auto">
                        <div className="flex items-center justify-between border-b border-slate-800 pb-3">
                            <div>
                                <h3 className="text-base font-bold text-white">{activeStatusModal.campaign?.name}</h3>
                                <p className="text-xs text-slate-400">Deep Lifecycle Status Report</p>
                            </div>
                            <button onClick={() => setActiveStatusModal(null)} className="text-slate-400 hover:text-white">✕</button>
                        </div>

                        {/* Stat row */}
                        <div className="grid grid-cols-4 gap-3">
                            <div className="p-3 rounded-xl bg-slate-900 border border-slate-800 text-center">
                                <div className="text-[10px] text-slate-400 uppercase">Total URLs</div>
                                <div className="text-xl font-bold text-white mt-1">{activeStatusModal.metrics?.total_urls}</div>
                            </div>
                            <div className="p-3 rounded-xl bg-slate-900 border border-slate-800 text-center">
                                <div className="text-[10px] text-emerald-400 uppercase">Live URLs</div>
                                <div className="text-xl font-bold text-emerald-300 mt-1">{activeStatusModal.metrics?.live_urls}</div>
                            </div>
                            <div className="p-3 rounded-xl bg-slate-900 border border-slate-800 text-center">
                                <div className="text-[10px] text-rose-400 uppercase">Lost URLs</div>
                                <div className="text-xl font-bold text-rose-300 mt-1">{activeStatusModal.metrics?.lost_urls}</div>
                            </div>
                            <div className="p-3 rounded-xl bg-slate-900 border border-slate-800 text-center">
                                <div className="text-[10px] text-cyan-400 uppercase">Index Rate</div>
                                <div className="text-xl font-bold text-cyan-300 mt-1">{activeStatusModal.metrics?.index_rate}%</div>
                            </div>
                        </div>

                        {/* Status Breakdown Lists */}
                        <div className="grid grid-cols-2 gap-4">
                            <div className="p-4 rounded-xl bg-slate-950/60 border border-slate-800">
                                <h4 className="text-xs font-semibold text-slate-300 mb-2">Crawl Statuses</h4>
                                <pre className="text-[11px] text-slate-400 font-mono">
                                    {JSON.stringify(activeStatusModal.crawl_status, null, 2)}
                                </pre>
                            </div>
                            <div className="p-4 rounded-xl bg-slate-950/60 border border-slate-800">
                                <h4 className="text-xs font-semibold text-slate-300 mb-2">Index Statuses</h4>
                                <pre className="text-[11px] text-slate-400 font-mono">
                                    {JSON.stringify(activeStatusModal.index_status, null, 2)}
                                </pre>
                            </div>
                        </div>

                        <div className="flex justify-end pt-2">
                            <button
                                onClick={() => setActiveStatusModal(null)}
                                className="px-4 py-2 bg-slate-800 text-white rounded-lg text-xs font-semibold"
                            >
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Create Campaign Modal */}
            {isCreatingCampaign && (
                <div className="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                    <div className="glass-panel p-6 rounded-2xl w-full max-w-md space-y-4 border border-slate-700 shadow-2xl">
                        <div className="flex items-center justify-between border-b border-slate-800 pb-3">
                            <h3 className="text-sm font-bold text-white">Create Monitoring Campaign</h3>
                            <button onClick={() => setIsCreatingCampaign(false)} className="text-slate-400 hover:text-white">✕</button>
                        </div>
                        <form onSubmit={handleCreateCampaign} className="space-y-3.5">
                            <div>
                                <label className="block text-xs font-semibold text-slate-300 mb-1">Select Client Project</label>
                                <select
                                    value={selectedProjectId}
                                    onChange={(e) => setSelectedProjectId(Number(e.target.value))}
                                    className="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-100"
                                >
                                    {projects.map((p) => (
                                        <option key={p.id} value={p.id}>{p.name} ({p.target_domain})</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-300 mb-1">Campaign Name</label>
                                <input
                                    type="text"
                                    required
                                    placeholder="e.g. Q2 High Authority Guest Posts"
                                    value={name}
                                    onChange={(e) => setName(e.target.value)}
                                    className="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-100"
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-300 mb-1">Scheduled Frequency</label>
                                <select
                                    value={checkFrequency}
                                    onChange={(e) => setCheckFrequency(e.target.value as any)}
                                    className="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-100"
                                >
                                    <option value="24h">24 Hours (Daily Monitoring)</option>
                                    <option value="72h">72 Hours (Bi-Weekly Check)</option>
                                    <option value="7d">7 Days (Weekly Audit)</option>
                                    <option value="14d">14 Days (Fortnightly Review)</option>
                                    <option value="30d">30 Days (Monthly Verification)</option>
                                </select>
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-300 mb-1">Description (Optional)</label>
                                <textarea
                                    rows={2}
                                    value={description}
                                    onChange={(e) => setDescription(e.target.value)}
                                    className="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-100"
                                />
                            </div>
                            <div className="flex justify-end gap-2 pt-2">
                                <button
                                    type="button"
                                    onClick={() => setIsCreatingCampaign(false)}
                                    className="px-3.5 py-1.5 text-xs text-slate-400 hover:text-white"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    disabled={isSubmitting}
                                    className="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-xs font-semibold"
                                >
                                    {isSubmitting ? 'Creating...' : 'Create Campaign'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
};
