import React, { useState } from 'react';
import { Campaign } from '../types';
import api from '../services/api';
import { UploadCloud, FileText, CheckCircle2, AlertTriangle, XCircle, ArrowRight } from 'lucide-react';

interface BulkImportViewProps {
    campaigns: Campaign[];
    onImportSuccess: () => void;
}

export const BulkImportView: React.FC<BulkImportViewProps> = ({ campaigns, onImportSuccess }) => {
    const [selectedCampaignId, setSelectedCampaignId] = useState<number>(campaigns[0]?.id || 1);
    const [rawText, setRawText] = useState('');
    const [file, setFile] = useState<File | null>(null);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [importResult, setImportResult] = useState<any | null>(null);

    const handleImport = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!rawText.trim() && !file) {
            alert('Please enter URLs or upload a file.');
            return;
        }

        setIsSubmitting(true);
        try {
            const formData = new FormData();
            if (file) {
                formData.append('file', file);
            } else {
                formData.append('raw_text', rawText);
            }

            const res = await api.post(`/campaigns/${selectedCampaignId}/import`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });

            setImportResult(res.data);
            setRawText('');
            setFile(null);
            onImportSuccess();
        } catch (err: any) {
            alert('Import failed: ' + (err.response?.data?.message || err.message));
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <div className="space-y-6 max-w-4xl">
            <div>
                <h2 className="text-xl font-bold text-white">Bulk Backlink Ingestion Engine</h2>
                <p className="text-xs text-slate-400 mt-1">
                    Import lists of source & target backlink pairs. Automated validation detects malformed URLs, invalid schemes, and duplicate entries.
                </p>
            </div>

            <div className="glass-panel p-6 rounded-2xl space-y-5">
                <form onSubmit={handleImport} className="space-y-4">
                    {/* Select Destination Campaign */}
                    <div>
                        <label className="block text-xs font-semibold text-slate-300 mb-1.5">
                            Target Campaign for Ingestion
                        </label>
                        <select
                            value={selectedCampaignId}
                            onChange={(e) => setSelectedCampaignId(Number(e.target.value))}
                            className="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-slate-100 font-medium focus:ring-1 focus:ring-indigo-500"
                        >
                            {campaigns.map((c) => (
                                <option key={c.id} value={c.id}>
                                    {c.name} ({c.project?.name || 'Project'})
                                </option>
                            ))}
                        </select>
                    </div>

                    {/* Manual Raw Paste Textarea */}
                    <div>
                        <div className="flex items-center justify-between mb-1.5">
                            <label className="block text-xs font-semibold text-slate-300">
                                Manual Paste (Format: SourceURL TargetURL [AnchorText] or CSV)
                            </label>
                            <span className="text-[11px] text-slate-500">Supports comma, tab, or whitespace delimited</span>
                        </div>
                        <textarea
                            rows={8}
                            placeholder={`https://techreview.com/post-101   https://acme.io/features   Acme Cloud
https://industrymag.net/top-tools  https://acme.io/pricing    Transparent Pricing
https://partner-digest.org/links   https://acme.io/docs       API Docs`}
                            value={rawText}
                            onChange={(e) => {
                                setRawText(e.target.value);
                                if (file) setFile(null);
                            }}
                            className="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-xs text-slate-200 font-mono placeholder-slate-600 focus:outline-none focus:border-indigo-500"
                        />
                    </div>

                    {/* Or File Upload */}
                    <div>
                        <label className="block text-xs font-semibold text-slate-300 mb-1.5">
                            Or Upload CSV / TXT File
                        </label>
                        <input
                            type="file"
                            accept=".csv,.txt"
                            onChange={(e) => {
                                if (e.target.files && e.target.files[0]) {
                                    setFile(e.target.files[0]);
                                    setRawText('');
                                }
                            }}
                            className="w-full text-xs text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-800 file:text-slate-200 hover:file:bg-slate-700"
                        />
                    </div>

                    <button
                        type="submit"
                        disabled={isSubmitting}
                        className="w-full py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs shadow-md shadow-indigo-600/30 transition flex items-center justify-center gap-2"
                    >
                        <UploadCloud className="w-4 h-4" />
                        <span>{isSubmitting ? 'Validating & Importing...' : 'Validate & Import Backlinks'}</span>
                    </button>
                </form>

                {/* Import Result Feedback */}
                {importResult && (
                    <div className="p-4 rounded-xl bg-slate-950/80 border border-slate-800 space-y-3">
                        <div className="flex items-center justify-between border-b border-slate-800 pb-2">
                            <span className="text-xs font-bold text-white flex items-center gap-1.5">
                                <CheckCircle2 className="w-4 h-4 text-emerald-400" />
                                Batch Ingestion Report
                            </span>
                            <span className="text-[11px] text-slate-400">Total Parsed: {importResult.total_parsed}</span>
                        </div>

                        <div className="grid grid-cols-3 gap-3 text-center text-xs">
                            <div className="p-2.5 rounded-lg bg-emerald-500/10 border border-emerald-500/20">
                                <div className="text-emerald-400 font-bold text-lg">{importResult.total_imported}</div>
                                <div className="text-[10px] text-emerald-300">Successfully Imported</div>
                            </div>
                            <div className="p-2.5 rounded-lg bg-amber-500/10 border border-amber-500/20">
                                <div className="text-amber-400 font-bold text-lg">{importResult.total_duplicates}</div>
                                <div className="text-[10px] text-amber-300">Duplicates Filtered</div>
                            </div>
                            <div className="p-2.5 rounded-lg bg-rose-500/10 border border-rose-500/20">
                                <div className="text-rose-400 font-bold text-lg">{importResult.total_errors}</div>
                                <div className="text-[10px] text-rose-300">Errors / Malformed</div>
                            </div>
                        </div>

                        {importResult.errors && importResult.errors.length > 0 && (
                            <div className="p-3 rounded-lg bg-rose-950/30 border border-rose-800/40 text-xs text-rose-300 space-y-1">
                                <div className="font-semibold text-rose-200">Validation Failures:</div>
                                {importResult.errors.map((err: any, i: number) => (
                                    <div key={i} className="text-[11px]">
                                        Row {err.row}: {err.error} {err.url ? `(${err.url})` : ''}
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                )}
            </div>
        </div>
    );
};
