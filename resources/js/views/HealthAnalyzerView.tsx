import React, { useState } from 'react';
import api from '../services/api';
import {
    Activity,
    ShieldCheck,
    ShieldAlert,
    Clock,
    Globe,
    Search,
    AlertTriangle,
    CheckCircle2,
    XCircle,
    Zap
} from 'lucide-react';

export const HealthAnalyzerView: React.FC = () => {
    const [sourceUrl, setSourceUrl] = useState('https://technews-weekly.com/best-cloud-saas-2026');
    const [targetUrl, setTargetUrl] = useState('https://acme.io/features');
    const [isAnalyzing, setIsAnalyzing] = useState(false);
    const [analysisResult, setAnalysisResult] = useState<any | null>(null);
    const [errorMessage, setErrorMessage] = useState<string | null>(null);

    const runAnalysis = async (testSource?: string, testTarget?: string) => {
        const src = testSource || sourceUrl;
        const tgt = testTarget || targetUrl;
        setIsAnalyzing(true);
        setAnalysisResult(null);
        setErrorMessage(null);

        try {
            const res = await api.post('/checks/analyze-url', {
                source_url: src,
                target_url: tgt,
            });
            setAnalysisResult(res.data);
        } catch (err: any) {
            setErrorMessage(err.response?.data?.error || err.message || 'Inspection failed.');
        } finally {
            setIsAnalyzing(false);
        }
    };

    const runSsrfDemo = (maliciousUrl: string) => {
        setSourceUrl(maliciousUrl);
        runAnalysis(maliciousUrl, 'https://acme.io');
    };

    return (
        <div className="space-y-6 max-w-4xl">
            <div>
                <h2 className="text-xl font-bold text-white flex items-center gap-2">
                    URL Health & SSRF Protection Engine
                    <span className="text-[10px] px-2 py-0.5 rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                        Live Inspector
                    </span>
                </h2>
                <p className="text-xs text-slate-400 mt-1">
                    Directly fetch and evaluate arbitrary URLs with strict server-side request forgery (SSRF) filters, timeout guards, and HTML element extraction.
                </p>
            </div>

            {/* URL Input Form */}
            <div className="glass-panel p-6 rounded-2xl space-y-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label className="block text-xs font-semibold text-slate-300 mb-1">
                            Source Page URL to Inspect
                        </label>
                        <input
                            type="url"
                            value={sourceUrl}
                            onChange={(e) => setSourceUrl(e.target.value)}
                            className="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-100 font-mono focus:outline-none focus:border-indigo-500"
                        />
                    </div>
                    <div>
                        <label className="block text-xs font-semibold text-slate-300 mb-1">
                            Expected Target Destination URL
                        </label>
                        <input
                            type="url"
                            value={targetUrl}
                            onChange={(e) => setTargetUrl(e.target.value)}
                            className="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-100 font-mono focus:outline-none focus:border-indigo-500"
                        />
                    </div>
                </div>

                <div className="flex flex-wrap items-center justify-between gap-3 pt-2">
                    {/* SSRF Test Trigger Buttons */}
                    <div className="flex items-center gap-2 text-xs text-slate-400">
                        <span className="text-[11px] font-semibold">Test SSRF Traps:</span>
                        <button
                            type="button"
                            onClick={() => runSsrfDemo('http://localhost:8000/env')}
                            className="px-2 py-1 rounded-lg bg-rose-950/40 border border-rose-800/40 text-[10px] text-rose-300 hover:bg-rose-900/40"
                        >
                            localhost:8000
                        </button>
                        <button
                            type="button"
                            onClick={() => runSsrfDemo('http://169.254.169.254/metadata')}
                            className="px-2 py-1 rounded-lg bg-rose-950/40 border border-rose-800/40 text-[10px] text-rose-300 hover:bg-rose-900/40"
                        >
                            169.254.169.254
                        </button>
                        <button
                            type="button"
                            onClick={() => runSsrfDemo('http://192.168.1.1/router')}
                            className="px-2 py-1 rounded-lg bg-rose-950/40 border border-rose-800/40 text-[10px] text-rose-300 hover:bg-rose-900/40"
                        >
                            192.168.1.1 (LAN)
                        </button>
                    </div>

                    <button
                        onClick={() => runAnalysis()}
                        disabled={isAnalyzing}
                        className="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs shadow-md shadow-indigo-600/30 transition flex items-center gap-2"
                    >
                        <Activity className={`w-4 h-4 ${isAnalyzing ? 'animate-spin' : ''}`} />
                        <span>{isAnalyzing ? 'Analyzing Remote Page...' : 'Inspect URL Health'}</span>
                    </button>
                </div>
            </div>

            {/* Error / SSRF Block Notice */}
            {errorMessage && (
                <div className="p-4 rounded-xl bg-rose-950/40 border border-rose-800/50 flex items-start gap-3 text-rose-200">
                    <ShieldAlert className="w-5 h-5 text-rose-400 shrink-0 mt-0.5" />
                    <div>
                        <h4 className="text-xs font-bold uppercase tracking-wider text-rose-300">
                            SSRF Filter Blocked Request
                        </h4>
                        <p className="text-xs mt-1 leading-relaxed text-rose-300 font-mono">
                            {errorMessage}
                        </p>
                    </div>
                </div>
            )}

            {/* Analysis Results Display */}
            {analysisResult && (
                <div className="glass-panel p-6 rounded-2xl space-y-6">
                    <div className="flex items-center justify-between border-b border-slate-800 pb-3">
                        <span className="text-xs font-bold text-white flex items-center gap-2">
                            <ShieldCheck className="w-4 h-4 text-emerald-400" />
                            Inspection Diagnostics & DOM Audit
                        </span>
                        <span className="px-2.5 py-0.5 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-[10px] text-emerald-400 font-bold">
                            SSRF Verified Safe
                        </span>
                    </div>

                    {/* Vitals Grid */}
                    <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
                        <div className="p-3 rounded-xl bg-slate-950 border border-slate-800">
                            <div className="text-[10px] text-slate-500 uppercase font-semibold">HTTP Status</div>
                            <div className="text-xl font-bold text-white mt-1 font-mono">
                                {analysisResult.fetch?.http_status || 'ERR'}
                            </div>
                        </div>
                        <div className="p-3 rounded-xl bg-slate-950 border border-slate-800">
                            <div className="text-[10px] text-slate-500 uppercase font-semibold">Response Latency</div>
                            <div className="text-xl font-bold text-indigo-400 mt-1 font-mono">
                                {analysisResult.fetch?.latency_ms}ms
                            </div>
                        </div>
                        <div className="p-3 rounded-xl bg-slate-950 border border-slate-800">
                            <div className="text-[10px] text-slate-500 uppercase font-semibold">Redirect Hops</div>
                            <div className="text-xl font-bold text-white mt-1 font-mono">
                                {analysisResult.fetch?.redirect_count}
                            </div>
                        </div>
                        <div className="p-3 rounded-xl bg-slate-950 border border-slate-800">
                            <div className="text-[10px] text-slate-500 uppercase font-semibold">Target Link Found</div>
                            <div className={`text-xl font-bold mt-1 ${analysisResult.page_analysis?.backlink_found ? 'text-emerald-400' : 'text-rose-400'}`}>
                                {analysisResult.page_analysis?.backlink_found ? 'YES' : 'NO'}
                            </div>
                        </div>
                    </div>

                    {/* Extracted SEO Metadata */}
                    {analysisResult.page_analysis && (
                        <div className="space-y-3 pt-2">
                            <h4 className="text-xs font-bold text-slate-300 uppercase tracking-wider">
                                Extracted On-Page Attributes
                            </h4>

                            <div className="p-4 rounded-xl bg-slate-950/60 border border-slate-800 space-y-2.5 text-xs">
                                <div className="flex justify-between border-b border-slate-800/80 pb-2">
                                    <span className="text-slate-400">Page Title:</span>
                                    <span className="font-semibold text-slate-200 text-right truncate max-w-md">
                                        {analysisResult.page_analysis.page_title || 'None'}
                                    </span>
                                </div>
                                <div className="flex justify-between border-b border-slate-800/80 pb-2">
                                    <span className="text-slate-400">Meta Robots:</span>
                                    <span className="font-mono text-slate-200">
                                        {analysisResult.page_analysis.meta_robots || 'None specified'}
                                    </span>
                                </div>
                                <div className="flex justify-between border-b border-slate-800/80 pb-2">
                                    <span className="text-slate-400">Canonical Tag:</span>
                                    <span className="font-mono text-slate-200 truncate max-w-md">
                                        {analysisResult.page_analysis.canonical_url || 'Matches self'}
                                    </span>
                                </div>
                                <div className="flex justify-between border-b border-slate-800/80 pb-2">
                                    <span className="text-slate-400">Extracted Anchor Text:</span>
                                    <span className="font-bold text-indigo-300">
                                        "{analysisResult.page_analysis.anchor_text_found || 'N/A'}"
                                    </span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-slate-400">Link Type & Rel Attributes:</span>
                                    <span className="font-mono text-xs uppercase text-slate-300">
                                        {analysisResult.page_analysis.link_type} ({analysisResult.page_analysis.rel_attributes?.join(', ') || 'none'})
                                    </span>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            )}
        </div>
    );
};
