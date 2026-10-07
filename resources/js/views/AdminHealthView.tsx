import React, { useState, useEffect } from 'react';
import api from '../services/api';
import { ShieldAlert, Server, Activity, Users, Database, Cpu, CheckCircle2, Lock } from 'lucide-react';

export const AdminHealthView: React.FC = () => {
    const [health, setHealth] = useState<any | null>(null);
    const [users, setUsers] = useState<any[]>([]);
    const [loading, setLoading] = useState(false);

    const fetchData = async () => {
        setLoading(true);
        try {
            const [healthRes, usersRes] = await Promise.all([
                api.get('/admin/health'),
                api.get('/admin/users'),
            ]);
            setHealth(healthRes.data);
            setUsers(usersRes.data.data || []);
        } catch (err) {
            console.error('Failed to load admin health info', err);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchData();
    }, []);

    const handleRoleUpdate = async (userId: number, newRole: string) => {
        try {
            await api.put(`/admin/users/${userId}`, { role: newRole });
            fetchData();
        } catch (err: any) {
            alert('Failed to update role: ' + (err.response?.data?.message || err.message));
        }
    };

    return (
        <div className="space-y-6">
            <div>
                <h2 className="text-xl font-bold text-white flex items-center gap-2">
                    System Health & Administration
                    <span className="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                        Diagnostics Online
                    </span>
                </h2>
                <p className="text-xs text-slate-400 mt-1">
                    Database telemetry, queue health, background worker status, API rate limit throttles, and team role management.
                </p>
            </div>

            {/* Health Vitals Grid */}
            {health && (
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div className="glass-panel p-5 rounded-2xl space-y-2">
                        <div className="flex items-center justify-between text-xs font-semibold text-slate-400">
                            <span>Framework & Runtime</span>
                            <Server className="w-4 h-4 text-indigo-400" />
                        </div>
                        <div className="text-lg font-bold text-white">PHP {health.system?.php_version}</div>
                        <div className="text-[11px] text-slate-500 font-mono">Laravel {health.system?.laravel_version}</div>
                    </div>

                    <div className="glass-panel p-5 rounded-2xl space-y-2">
                        <div className="flex items-center justify-between text-xs font-semibold text-slate-400">
                            <span>Database State</span>
                            <Database className="w-4 h-4 text-emerald-400" />
                        </div>
                        <div className="text-lg font-bold text-emerald-400 flex items-center gap-1.5">
                            <CheckCircle2 className="w-4 h-4" />
                            {health.system?.database}
                        </div>
                        <div className="text-[11px] text-slate-500 font-mono">SQLite / MySQL Driver</div>
                    </div>

                    <div className="glass-panel p-5 rounded-2xl space-y-2">
                        <div className="flex items-center justify-between text-xs font-semibold text-slate-400">
                            <span>Queue Engine</span>
                            <Cpu className="w-4 h-4 text-cyan-400" />
                        </div>
                        <div className="text-lg font-bold text-white font-mono">
                            {health.queues?.pending_jobs} Pending
                        </div>
                        <div className="text-[11px] text-slate-500 font-mono">
                            Failed: {health.queues?.failed_jobs} jobs
                        </div>
                    </div>

                    <div className="glass-panel p-5 rounded-2xl space-y-2">
                        <div className="flex items-center justify-between text-xs font-semibold text-slate-400">
                            <span>Security Audits (24h)</span>
                            <Lock className="w-4 h-4 text-amber-400" />
                        </div>
                        <div className="text-lg font-bold text-amber-300 font-mono">
                            {health.security_audits_24h} Events
                        </div>
                        <div className="text-[11px] text-slate-500">SSRF & API audit trail</div>
                    </div>
                </div>
            )}

            {/* User & Role Management Table */}
            <div className="glass-panel p-6 rounded-2xl space-y-4">
                <h3 className="text-sm font-bold text-white flex items-center gap-2">
                    <Users className="w-4 h-4 text-indigo-400" />
                    Team Accounts & Role-Based Access Control
                </h3>

                <div className="overflow-x-auto">
                    <table className="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr className="bg-slate-950/60 border-b border-slate-800 text-slate-400 uppercase text-[10px]">
                                <th className="py-2.5 px-3">Name</th>
                                <th className="py-2.5 px-3">Email</th>
                                <th className="py-2.5 px-3">Current Role</th>
                                <th className="py-2.5 px-3">Status</th>
                                <th className="py-2.5 px-3">API Limit</th>
                                <th className="py-2.5 px-3 text-right">Modify Role</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-800/60">
                            {users.map((u) => (
                                <tr key={u.id} className="hover:bg-slate-800/30 transition">
                                    <td className="py-3 px-3 font-semibold text-slate-200">{u.name}</td>
                                    <td className="py-3 px-3 font-mono text-slate-400">{u.email}</td>
                                    <td className="py-3 px-3">
                                        <span className="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-indigo-500/10 text-indigo-300 border border-indigo-500/20">
                                            {u.role.replace('_', ' ')}
                                        </span>
                                    </td>
                                    <td className="py-3 px-3">
                                        <span className="text-emerald-400 font-semibold">{u.status}</span>
                                    </td>
                                    <td className="py-3 px-3 font-mono text-slate-300">{u.api_rate_limit} req/min</td>
                                    <td className="py-3 px-3 text-right">
                                        <select
                                            value={u.role}
                                            onChange={(e) => handleRoleUpdate(u.id, e.target.value)}
                                            className="bg-slate-900 border border-slate-800 text-xs rounded-lg px-2 py-1 text-slate-300"
                                        >
                                            <option value="admin">Admin</option>
                                            <option value="seo_specialist">SEO Specialist</option>
                                            <option value="viewer">Viewer</option>
                                        </select>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
};
