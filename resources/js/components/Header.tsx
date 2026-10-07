import React from 'react';
import { Project, User } from '../types';
import { Bell, RefreshCw, LogOut, Shield, User as UserIcon, CheckCircle2 } from 'lucide-react';

interface HeaderProps {
    user: User | null;
    projects: Project[];
    selectedProjectId: number | 'all';
    setSelectedProjectId: (id: number | 'all') => void;
    onLogout: () => void;
    onRefresh: () => void;
    isLoading: boolean;
}

export const Header: React.FC<HeaderProps> = ({
    user,
    projects,
    selectedProjectId,
    setSelectedProjectId,
    onLogout,
    onRefresh,
    isLoading,
}) => {
    return (
        <header className="h-16 bg-slate-900/60 border-b border-slate-800/80 px-6 flex items-center justify-between backdrop-blur-md shrink-0">
            {/* Project Filter Selector */}
            <div className="flex items-center gap-3">
                <span className="text-xs font-medium text-slate-400">Scope:</span>
                <select
                    value={selectedProjectId}
                    onChange={(e) => setSelectedProjectId(e.target.value === 'all' ? 'all' : Number(e.target.value))}
                    className="bg-slate-800/90 border border-slate-700 text-xs rounded-lg px-3 py-1.5 text-slate-100 font-medium focus:outline-none focus:ring-1 focus:ring-indigo-500"
                >
                    <option value="all">All Projects & Clients ({projects.length})</option>
                    {projects.map((p) => (
                        <option key={p.id} value={p.id}>
                            {p.name} ({p.target_domain})
                        </option>
                    ))}
                </select>

                <div className="hidden sm:flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-[11px] text-emerald-400 font-medium">
                    <CheckCircle2 className="w-3.5 h-3.5" />
                    <span>Real-time Scheduler Active</span>
                </div>
            </div>

            {/* Right Tools & User Info */}
            <div className="flex items-center gap-4">
                <button
                    onClick={onRefresh}
                    disabled={isLoading}
                    title="Refresh Live Data"
                    className="p-2 rounded-lg bg-slate-800/80 text-slate-300 hover:text-white hover:bg-slate-700/60 transition text-xs flex items-center gap-1.5"
                >
                    <RefreshCw className={`w-3.5 h-3.5 ${isLoading ? 'animate-spin text-indigo-400' : ''}`} />
                    <span className="hidden md:inline text-xs">Sync</span>
                </button>

                {/* User Card */}
                {user ? (
                    <div className="flex items-center gap-3 pl-2 border-l border-slate-800">
                        <div className="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-xs font-bold text-white shadow-inner">
                            {user.name.charAt(0)}
                        </div>
                        <div className="hidden lg:block text-left">
                            <div className="text-xs font-semibold text-slate-200 leading-tight">{user.name}</div>
                            <div className="text-[10px] text-slate-400 capitalize flex items-center gap-1">
                                <Shield className="w-2.5 h-2.5 text-indigo-400" />
                                {user.role.replace('_', ' ')}
                            </div>
                        </div>
                        <button
                            onClick={onLogout}
                            title="Sign out"
                            className="p-1.5 text-slate-400 hover:text-rose-400 transition rounded-lg hover:bg-slate-800"
                        >
                            <LogOut className="w-4 h-4" />
                        </button>
                    </div>
                ) : (
                    <div className="flex items-center gap-2">
                        <UserIcon className="w-4 h-4 text-slate-400" />
                        <span className="text-xs text-slate-300">Guest Mode</span>
                    </div>
                )}
            </div>
        </header>
    );
};
