import React, { useState, useEffect } from 'react';
import { Project, SearchEngineProperty } from '../types';
import api from '../services/api';
import { SearchCheck, Key, Shield, CheckCircle2, XCircle, Lock, Plus, ExternalLink, Zap } from 'lucide-react';

interface IntegrationsViewProps {
    projects: Project[];
}

export const IntegrationsView: React.FC<IntegrationsViewProps> = ({ projects }) => {
    const [properties, setProperties] = useState<SearchEngineProperty[]>([]);
    const [isConnecting, setIsConnecting] = useState(false);
    const [provider, setProvider] = useState<'bing_webmaster' | 'google_search_console' | 'indexnow'>('indexnow');
    const [propertyUrl, setPropertyUrl] = useState('https://acme.io/');
    const [apiKey, setApiKey] = useState('');
    const [keyLocation, setKeyLocation] = useState('');
    const [selectedProjectId, setSelectedProjectId] = useState<number>(projects[0]?.id || 1);
    const [isSubmitting, setIsSubmitting] = useState(false);

    const fetchProperties = async () => {
        try {
            const res = await api.get('/search-engines/properties');
            setProperties(res.data || []);
        } catch (err) {
            console.error('Failed to load search engine properties', err);
        }
    };

    useEffect(() => {
        fetchProperties();
    }, []);

    const handleConnect = async (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);
        try {
            const res = await api.post('/search-engines/connect', {
                project_id: selectedProjectId,
                provider,
                property_url: propertyUrl,
                api_key: apiKey,
                key_location: keyLocation,
            });

            alert(res.data.validation?.message || 'Property configured successfully.');
            setIsConnecting(false);
            setApiKey('');
            fetchProperties();
        } catch (err: any) {
            alert('Connection failed: ' + (err.response?.data?.message || err.message));
        } finally {
            setIsSubmitting(false);
        }
    };

    const handleDisconnect = async (id: number) => {
        if (!confirm('Disconnect this search engine property?')) return;
        try {
            await api.delete(`/search-engines/${id}`);
            fetchProperties();
        } catch (err: any) {
            alert('Failed to disconnect: ' + (err.response?.data?.message || err.message));
        }
    };

    return (
        <div className="space-y-6">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 className="text-xl font-bold text-white flex items-center gap-2">
                        Search Engine Properties & API Integrations
                        <span className="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                            Encrypted Vault
                        </span>
                    </h2>
                    <p className="text-xs text-slate-400 mt-1">
                        Connect authorized properties for Bing Webmaster, IndexNow, and Google Search Console. Principles #3, #4, #5 & #10 enforced.
                    </p>
                </div>
                <button
                    onClick={() => setIsConnecting(true)}
                    className="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-md shadow-indigo-600/30 transition flex items-center gap-2 self-start"
                >
                    <Plus className="w-4 h-4" />
                    <span>Connect Authorized Property</span>
                </button>
            </div>

            {/* Provider Cards */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-5">
                {/* Google Search Console */}
                <div className="glass-panel p-5 rounded-2xl space-y-3 border-indigo-500/20">
                    <div className="flex items-center justify-between">
                        <span className="text-xs font-bold text-slate-200">Google Search Console</span>
                        <span className="text-[10px] px-2 py-0.5 rounded bg-indigo-500/10 text-indigo-400 font-semibold">URL Inspection</span>
                    </div>
                    <p className="text-xs text-slate-400 leading-relaxed">
                        Authenticates via OAuth/Service Account. Reads genuine crawl timestamps, canonical confirmation, and indexing state.
                    </p>
                    <div className="text-[11px] text-slate-500 font-mono">Quota: 2,000 inspections/day</div>
                </div>

                {/* Bing Webmaster */}
                <div className="glass-panel p-5 rounded-2xl space-y-3 border-cyan-500/20">
                    <div className="flex items-center justify-between">
                        <span className="text-xs font-bold text-slate-200">Bing Webmaster API</span>
                        <span className="text-[10px] px-2 py-0.5 rounded bg-cyan-500/10 text-cyan-400 font-semibold">URL Submission</span>
                    </div>
                    <p className="text-xs text-slate-400 leading-relaxed">
                        Submits discovered backlink URLs directly to Bing indexers for authorized site properties.
                    </p>
                    <div className="text-[11px] text-slate-500 font-mono">Quota: 10,000 URLs/day</div>
                </div>

                {/* IndexNow Protocol */}
                <div className="glass-panel p-5 rounded-2xl space-y-3 border-emerald-500/20">
                    <div className="flex items-center justify-between">
                        <span className="text-xs font-bold text-slate-200">IndexNow Protocol</span>
                        <span className="text-[10px] px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-400 font-semibold">Instant Ping</span>
                    </div>
                    <p className="text-xs text-slate-400 leading-relaxed">
                        Multi-search engine discovery protocol supported by Bing, Yandex, Seznam, and Naver with verified host keys.
                    </p>
                    <div className="text-[11px] text-slate-500 font-mono">Quota: 10,000 URLs/batch</div>
                </div>
            </div>

            {/* Connected Properties List */}
            <div className="glass-panel p-6 rounded-2xl space-y-4">
                <h3 className="text-sm font-bold text-white flex items-center gap-2">
                    <Lock className="w-4 h-4 text-emerald-400" />
                    Authorized Properties Registry
                </h3>

                <div className="space-y-3">
                    {properties.map((prop) => (
                        <div
                            key={prop.id}
                            className="p-4 rounded-xl bg-slate-950/60 border border-slate-800/80 flex flex-col md:flex-row md:items-center justify-between gap-4"
                        >
                            <div className="space-y-1">
                                <div className="flex items-center gap-2">
                                    <span className="text-xs font-bold text-slate-100 uppercase tracking-wide">
                                        {prop.provider.replace('_', ' ')}
                                    </span>
                                    {prop.is_authorized ? (
                                        <span className="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 flex items-center gap-1 font-semibold">
                                            <CheckCircle2 className="w-3 h-3" />
                                            Authorized
                                        </span>
                                    ) : (
                                        <span className="text-[10px] px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/20 flex items-center gap-1 font-semibold">
                                            <XCircle className="w-3 h-3" />
                                            Unverified
                                        </span>
                                    )}
                                </div>
                                <div className="text-xs font-mono text-slate-300">{prop.property_url}</div>
                            </div>

                            {/* Quota Progress */}
                            <div className="flex items-center gap-4">
                                <div className="text-right">
                                    <div className="text-[11px] text-slate-400 font-medium">Daily Quota Usage</div>
                                    <div className="text-xs font-bold text-slate-200 mt-0.5 font-mono">
                                        {prop.quota_used_today} / {prop.quota_daily} calls
                                    </div>
                                </div>
                                <button
                                    onClick={() => handleDisconnect(prop.id)}
                                    className="px-3 py-1.5 rounded-lg bg-rose-950/40 hover:bg-rose-900/60 text-rose-300 border border-rose-800/40 text-xs font-semibold transition"
                                >
                                    Disconnect
                                </button>
                            </div>
                        </div>
                    ))}

                    {properties.length === 0 && (
                        <div className="text-xs text-slate-500 text-center py-6">
                            No search engine properties connected yet.
                        </div>
                    )}
                </div>
            </div>

            {/* Connect Property Modal */}
            {isConnecting && (
                <div className="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                    <div className="glass-panel p-6 rounded-2xl w-full max-w-md space-y-4 border border-slate-700 shadow-2xl">
                        <div className="flex items-center justify-between border-b border-slate-800 pb-3">
                            <h3 className="text-sm font-bold text-white">Connect Search Engine Property</h3>
                            <button onClick={() => setIsConnecting(false)} className="text-slate-400 hover:text-white">✕</button>
                        </div>
                        <form onSubmit={handleConnect} className="space-y-3.5">
                            <div>
                                <label className="block text-xs font-semibold text-slate-300 mb-1">Provider</label>
                                <select
                                    value={provider}
                                    onChange={(e) => setProvider(e.target.value as any)}
                                    className="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-100"
                                >
                                    <option value="indexnow">IndexNow Protocol</option>
                                    <option value="bing_webmaster">Bing Webmaster API</option>
                                    <option value="google_search_console">Google Search Console</option>
                                </select>
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-300 mb-1">Associated Client Project</label>
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
                                <label className="block text-xs font-semibold text-slate-300 mb-1">Property URL (or sc-domain:)</label>
                                <input
                                    type="text"
                                    required
                                    placeholder="https://acme.io/"
                                    value={propertyUrl}
                                    onChange={(e) => setPropertyUrl(e.target.value)}
                                    className="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-100 font-mono"
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-300 mb-1">API Key / Token</label>
                                <input
                                    type="password"
                                    required
                                    placeholder="Encrypted automatically at rest"
                                    value={apiKey}
                                    onChange={(e) => setApiKey(e.target.value)}
                                    className="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-100 font-mono"
                                />
                            </div>
                            {provider === 'indexnow' && (
                                <div>
                                    <label className="block text-xs font-semibold text-slate-300 mb-1">Key Location URL (Optional)</label>
                                    <input
                                        type="url"
                                        placeholder="https://acme.io/yourkey.txt"
                                        value={keyLocation}
                                        onChange={(e) => setKeyLocation(e.target.value)}
                                        className="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-100 font-mono"
                                    />
                                </div>
                            )}

                            <div className="p-2.5 rounded-lg bg-indigo-950/40 border border-indigo-800/40 text-[11px] text-indigo-300">
                                <strong>Principle #10:</strong> API secrets are encrypted using AES-256 before database persistence and never exposed in frontend bundles.
                            </div>

                            <div className="flex justify-end gap-2 pt-2">
                                <button
                                    type="button"
                                    onClick={() => setIsConnecting(false)}
                                    className="px-3.5 py-1.5 text-xs text-slate-400 hover:text-white"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    disabled={isSubmitting}
                                    className="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-xs font-semibold"
                                >
                                    {isSubmitting ? 'Validating...' : 'Authorize Property'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
};
