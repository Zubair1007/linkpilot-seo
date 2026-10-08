import React from 'react';
import { Project, User } from '../types';
import { Bell, RefreshCw, LogOut, Shield, User as UserIcon, CheckCircle2, LogIn, UserPlus } from 'lucide-react';

interface HeaderProps {
    user: User | null;
    projects: Project[];
    selectedProjectId: number | 'all';
    setSelectedProjectId: (id: number | 'all') => void;
    onLogout: () => void;
    onOpenLogin?: () => void;
    onOpenRegister?: () => void;
    onRefresh: () => void;
    isLoading: boolean;
    activeTabLabel: string;
    activeTabPath: string;
}

export const Header: React.FC<HeaderProps> = ({
    user,
    projects,
    selectedProjectId,
    setSelectedProjectId,
    onLogout,
    onOpenLogin,
    onOpenRegister,
    onRefresh,
    isLoading,
    activeTabLabel,
    activeTabPath,
}) => {
    return (
        <header className="h-16 bg-slate-900/60 border-b border-slate-800/80 px-4 md:px-6 flex items-center justify-between backdrop-blur-md shrink-0">
            {/* Active Location Breadcrumb & Scope Filter */}
            <div className="flex items-center gap-3 md:gap-4 overflow-hidden">
                {/* Active View Pill */}
                <div className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-800/90 border border-slate-700/80 shadow-sm text-xs shrink-0">
                    <span className="text-slate-400 font-medium hidden sm:inline">LinkPilot</span>
                    <span className="text-slate-600 hidden sm:inline">/</span>
                    <span className="text-indigo-300 font-semibold flex items-center gap-1.5">
                        <span className="w-2 h-2 rounded-full bg-indigo-400 animate-pulse"></span>
                        {activeTabLabel}
                    </span>
                    <span className="text-[10px] text-slate-400 font-mono hidden md:inline ml-1 bg-slate-900/80 px-1.5 py-0.5 rounded border border-slate-700/50">
                        {activeTabPath}
                    </span>
                </div>

                {/* Project Filter Selector */}
                <div className="hidden sm:flex items-center gap-2 border-l border-slate-800 pl-3">
                    <span className="text-xs font-medium text-slate-400 hidden xl:inline">Scope:</span>
                    <select
                        value={selectedProjectId}
                        onChange={(e) => setSelectedProjectId(e.target.value === 'all' ? 'all' : Number(e.target.value))}
                        className="bg-slate-800/90 border border-slate-700 text-xs rounded-lg px-2.5 py-1.5 text-slate-100 font-medium focus:outline-none focus:ring-1 focus:ring-indigo-500 max-w-[200px] truncate"
                    >
                        <option value="all">All Projects ({projects.length})</option>
                        {projects.map((p) => (
                            <option key={p.id} value={p.id}>
                                {p.name} ({p.target_domain})
                            </option>
                        ))}
                    </select>

                    <div className="hidden xl:flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-[11px] text-emerald-400 font-medium">
                        <CheckCircle2 className="w-3.5 h-3.5" />
                        <span>Live Sync Active</span>
                    </div>
                </div>
            </div>

            {/* Right Tools & User Info */}
            <div className="flex items-center gap-3 md:gap-4 shrink-0">
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
                    <div className="flex items-center gap-2 sm:gap-2.5">
                        <a
                            href="/login"
                            onClick={(e) => {
                                e.preventDefault();
                                if (onOpenLogin) onOpenLogin();
                            }}
                            className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700/80 text-slate-200 hover:text-white border border-slate-700 text-xs font-semibold transition cursor-pointer"
                        >
                            <LogIn className="w-3.5 h-3.5 text-indigo-400" />
                            <span>Sign In</span>
                        </a>
                        <a
                            href="/register"
                            onClick={(e) => {
                                e.preventDefault();
                                if (onOpenRegister) onOpenRegister();
                            }}
                            className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-md shadow-indigo-600/30 transition cursor-pointer"
                        >
                            <UserPlus className="w-3.5 h-3.5" />
                            <span>Create Account</span>
                        </a>
                    </div>
                )}
            </div>
        </header>
    );
};
