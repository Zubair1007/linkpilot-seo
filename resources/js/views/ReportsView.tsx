import React, { useState, useEffect } from 'react';
import { Campaign, Project } from '../types';
import api from '../services/api';
import { FileSpreadsheet, Download, Plus, FileText, CheckCircle2 } from 'lucide-react';

interface ReportsViewProps {
    projects: Project[];
    campaigns: Campaign[];
}

export const ReportsView: React.FC<ReportsViewProps> = ({ projects, campaigns }) => {
    const [reports, setReports] = useState<any[]>([]);
    const [isGenerating, setIsGenerating] = useState(false);
    const [title, setTitle] = useState('Executive Backlink Index Audit');
    const [reportType, setReportType] = useState('campaign_summary');
    const [selectedProjectId, setSelectedProjectId] = useState<number>(projects[0]?.id || 1);
    const [selectedCampaignId, setSelectedCampaignId] = useState<number>(campaigns[0]?.id || 1);

    const fetchReports = async () => {
        try {
            const res = await api.get('/reports');
            setReports(res.data.data || []);
        } catch (err) {
            console.error(err);
        }
    };

    useEffect(() => {
        fetchReports();
    }, []);

    const handleGenerate = async (e: React.FormEvent) => {
        e.preventDefault();
        setIsGenerating(true);
        try {
            await api.post('/reports/generate', {
                project_id: selectedProjectId,
                campaign_id: selectedCampaignId,
                title,
                type: reportType,
                format: 'json',
            });
            fetchReports();
            alert('Report compiled and ready for review.');
        } catch (err: any) {
            alert('Generation failed: ' + (err.response?.data?.message || err.message));
        } finally {
            setIsGenerating(false);
        }
    };

    const handleDownloadCsv = () => {
        window.open('/api/v1/reports/export-csv', '_blank');
    };

    const handleDownloadPdf = () => {
        window.open('/api/v1/reports/export-pdf', '_blank');
    };

    return (
        <div className="space-y-6">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 className="text-xl font-bold text-white">SEO Reports & Analytics Exports</h2>
                    <p className="text-xs text-slate-400 mt-1">
                        Executive client summaries, lost link diagnostics, crawl logs, and live CSV / PDF exports.
                    </p>
                </div>
                <div className="flex items-center gap-2 self-start">
                    <button
                        onClick={handleDownloadPdf}
                        className="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-md shadow-indigo-600/30 transition flex items-center gap-2"
                    >
                        <FileText className="w-4 h-4" />
                        <span>Download Executive PDF</span>
                    </button>
                    <button
                        onClick={handleDownloadCsv}
                        className="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold border border-slate-700 transition flex items-center gap-2"
                    >
                        <Download className="w-4 h-4 text-emerald-400" />
                        <span>Direct CSV Export</span>
                    </button>
                </div>
            </div>

            {/* Generate Report Form */}
            <div className="glass-panel p-6 rounded-2xl space-y-4">
                <h3 className="text-sm font-bold text-white flex items-center gap-2">
                    <Plus className="w-4 h-4 text-indigo-400" />
                    Generate New Report
                </h3>
                <form onSubmit={handleGenerate} className="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
                    <div>
                        <label className="block text-slate-400 mb-1">Report Title</label>
                        <input
                            type="text"
                            required
                            value={title}
                            onChange={(e) => setTitle(e.target.value)}
                            className="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-slate-100"
                        />
                    </div>
                    <div>
                        <label className="block text-slate-400 mb-1">Report Category</label>
                        <select
                            value={reportType}
                            onChange={(e) => setReportType(e.target.value)}
                            className="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-slate-100"
                        >
                            <option value="campaign_summary">Campaign Summary</option>
                            <option value="backlink_audit">Backlink Audit</option>
                            <option value="lost_backlinks">Lost Backlinks Alert</option>
                            <option value="index_status">Index Status Breakdown</option>
                            <option value="api_activity">API Activity & Latency</option>
                        </select>
                    </div>
                    <div>
                        <label className="block text-slate-400 mb-1">Client Project</label>
                        <select
                            value={selectedProjectId}
                            onChange={(e) => setSelectedProjectId(Number(e.target.value))}
                            className="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-slate-100"
                        >
                            {projects.map((p) => (
                                <option key={p.id} value={p.id}>{p.name}</option>
                            ))}
                        </select>
                    </div>
                    <div className="flex items-end">
                        <button
                            type="submit"
                            disabled={isGenerating}
                            className="w-full py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg font-semibold"
                        >
                            {isGenerating ? 'Compiling...' : 'Generate Report'}
                        </button>
                    </div>
                </form>
            </div>

            {/* Generated Reports List */}
            <div className="glass-panel p-6 rounded-2xl space-y-4">
                <h3 className="text-sm font-bold text-white flex items-center gap-2">
                    <FileSpreadsheet className="w-4 h-4 text-emerald-400" />
                    Historical Generated Reports
                </h3>

                <div className="space-y-3">
                    {reports.map((rep) => (
                        <div key={rep.id} className="p-4 rounded-xl bg-slate-950/60 border border-slate-800 flex items-center justify-between">
                            <div className="space-y-1">
                                <div className="text-sm font-bold text-white flex items-center gap-2">
                                    {rep.title}
                                    <span className="text-[10px] uppercase font-mono px-2 py-0.5 rounded bg-indigo-500/10 text-indigo-300 border border-indigo-500/20">
                                        {rep.type.replace('_', ' ')}
                                    </span>
                                </div>
                                <div className="text-xs text-slate-400">
                                    Metrics Summary: <span className="font-mono text-slate-300">{JSON.stringify(rep.summary_metrics)}</span>
                                </div>
                            </div>
                            <div className="text-right">
                                <span className="text-[11px] text-slate-500 font-mono">
                                    {new Date(rep.created_at).toLocaleDateString()}
                                </span>
                            </div>
                        </div>
                    ))}

                    {reports.length === 0 && (
                        <div className="text-xs text-slate-500 text-center py-6">
                            No reports generated yet.
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
};
