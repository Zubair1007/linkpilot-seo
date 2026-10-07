import React, { useState, useEffect } from 'react';
import api from '../services/api';
import { Cpu, RefreshCw, CheckCircle2, Clock, AlertOctagon, RotateCcw } from 'lucide-react';

export const QueuesView: React.FC = () => {
    const [jobs, setJobs] = useState<any[]>([]);
    const [apiLogs, setApiLogs] = useState<any[]>([]);
    const [loading, setLoading] = useState(false);
    const [filterStatus, setFilterStatus] = useState<string>('all');

    const fetchData = async () => {
        setLoading(true);
        try {
            const params: Record<string, any> = {};
            if (filterStatus !== 'all') params.status = filterStatus;

            const [jobsRes, logsRes] = await Promise.all([
                api.get('/checks/discovery-jobs', { params }),
                api.get('/checks/api-logs'),
            ]);

            setJobs(jobsRes.data.data || []);
            setApiLogs(logsRes.data.data || []);
        } catch (err) {
            console.error('Failed to load queue status', err);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchData();
    }, [filterStatus]);

    return (
        <div className="space-y-6">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 className="text-xl font-bold text-white flex items-center gap-2">
                        Discovery Queue & Retry Engine
                        <span className="text-[10px] px-2 py-0.5 rounded-full bg-cyan-500/20 text-cyan-300 border border-cyan-500/30 font-semibold">
                            Exponential Backoff
                        </span>
                    </h2>
                    <p className="text-xs text-slate-400 mt-1">
                        Throttled queue workers, rate limiters, non-repeating permanent error filters, and real-time API call auditing.
                    </p>
                </div>
                <button
                    onClick={fetchData}
                    disabled={loading}
                    className="p-2 rounded-xl bg-slate-800 text-slate-200 hover:text-white flex items-center gap-1.5 text-xs font-semibold self-start"
                >
                    <RefreshCw className={`w-3.5 h-3.5 ${loading ? 'animate-spin text-indigo-400' : ''}`} />
                    <span>Refresh Queue</span>
                </button>
            </div>

            {/* Discovery Jobs Table */}
            <div className="glass-panel p-6 rounded-2xl space-y-4">
                <div className="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 className="text-sm font-bold text-white flex items-center gap-2">
                        <Cpu className="w-4 h-4 text-indigo-400" />
                        Discovery Worker Jobs (Principle #8)
                    </h3>
                    <select
                        value={filterStatus}
                        onChange={(e) => setFilterStatus(e.target.value)}
                        className="bg-slate-900 border border-slate-800 text-xs rounded-lg px-2.5 py-1 text-slate-300 font-medium"
                    >
                        <option value="all">Status: All</option>
                        <option value="pending">Pending</option>
                        <option value="running">Running</option>
                        <option value="completed">Completed</option>
                        <option value="failed">Failed</option>
                    </select>
                </div>

                <div className="overflow-x-auto">
                    <table className="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr className="bg-slate-950/60 border-b border-slate-800/80 text-slate-400 font-semibold uppercase text-[10px]">
                                <th className="py-2.5 px-3">Job ID</th>
                                <th className="py-2.5 px-3">Target URL</th>
                                <th className="py-2.5 px-3">Provider</th>
                                <th className="py-2.5 px-3">Method</th>
                                <th className="py-2.5 px-3">Status</th>
                                <th className="py-2.5 px-3">Attempt</th>
                                <th className="py-2.5 px-3">Response</th>
                                <th className="py-2.5 px-3 text-right">Timestamp</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-800/60">
                            {jobs.map((job) => (
                                <tr key={job.id} className="hover:bg-slate-800/30 transition">
                                    <td className="py-2.5 px-3 font-mono text-slate-400">#{job.id}</td>
                                    <td className="py-2.5 px-3 font-semibold text-slate-200 max-w-xs truncate">
                                        {job.backlink?.target_url || `Backlink #${job.backlink_id}`}
                                    </td>
                                    <td className="py-2.5 px-3 uppercase text-[10px] font-bold text-indigo-300">
                                        {job.provider}
                                    </td>
                                    <td className="py-2.5 px-3 text-slate-400 text-[11px] font-mono">
                                        {job.method}
                                    </td>
                                    <td className="py-2.5 px-3">
                                        <span className={`px-2 py-0.5 rounded text-[10px] font-bold uppercase ${
                                            job.status === 'completed' ? 'bg-emerald-500/10 text-emerald-300 border border-emerald-500/20' :
                                            job.status === 'failed' ? 'bg-rose-500/10 text-rose-300 border border-rose-500/20' :
                                            'bg-amber-500/10 text-amber-300 border border-amber-500/20'
                                        }`}>
                                            {job.status}
                                        </span>
                                    </td>
                                    <td className="py-2.5 px-3 font-mono text-slate-300">{job.attempt}</td>
                                    <td className="py-2.5 px-3 max-w-xs truncate text-[11px] text-slate-400">
                                        <span className="font-mono text-slate-300 mr-1">[{job.response_code || 200}]</span>
                                        {job.response_message}
                                    </td>
                                    <td className="py-2.5 px-3 text-right text-[11px] text-slate-500 font-mono">
                                        {new Date(job.created_at).toLocaleTimeString()}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>

            {/* Sanitized External API Call Audit (Principle #9) */}
            <div className="glass-panel p-6 rounded-2xl space-y-4">
                <div className="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div>
                        <h3 className="text-sm font-bold text-white flex items-center gap-2">
                            <CheckCircle2 className="w-4 h-4 text-emerald-400" />
                            Sanitized External API Audit Logs (Principle #9)
                        </h3>
                        <p className="text-[11px] text-slate-400">
                            Logged with provider, endpoint, latency, and redacted credential parameters.
                        </p>
                    </div>
                </div>

                <div className="space-y-2">
                    {apiLogs.map((log) => (
                        <div key={log.id} className="p-3 rounded-xl bg-slate-950/60 border border-slate-800 flex items-center justify-between text-xs">
                            <div className="space-y-0.5">
                                <div className="flex items-center gap-2">
                                    <span className="font-bold text-indigo-400 uppercase text-[10px]">{log.provider}</span>
                                    <span className="px-1.5 py-0.2 bg-slate-800 text-[10px] font-mono text-slate-300 rounded uppercase">
                                        {log.http_method}
                                    </span>
                                    <span className="font-mono text-slate-300">{log.endpoint_category}</span>
                                </div>
                                <div className="text-[11px] text-slate-400 font-mono truncate max-w-xl">
                                    {log.url}
                                </div>
                            </div>
                            <div className="text-right">
                                <div className="font-mono font-bold text-slate-200">
                                    Status: <span className={log.response_code === 200 ? 'text-emerald-400' : 'text-amber-400'}>{log.response_code || 200}</span>
                                </div>
                                <div className="text-[10px] text-slate-500 font-mono mt-0.5">
                                    Latency: {log.latency_ms}ms
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
};
