import React from 'react';
import {
    LayoutDashboard,
    FolderKanban,
    Target,
    Link2,
    UploadCloud,
    ActivitySquare,
    SearchCheck,
    Cpu,
    FileSpreadsheet,
    ShieldAlert,
    Settings,
    Radio
} from 'lucide-react';

interface SidebarProps {
    currentTab: string;
    setCurrentTab: (tab: string) => void;
    liveAlertCount: number;
}

export const Sidebar: React.FC<SidebarProps> = ({ currentTab, setCurrentTab, liveAlertCount }) => {
    const navItems = [
        { id: 'dashboard', label: 'Executive Dashboard', icon: LayoutDashboard },
        { id: 'projects', label: 'Projects & Domains', icon: FolderKanban },
        { id: 'campaigns', label: 'Campaigns', icon: Target },
        { id: 'backlinks', label: 'Backlinks Explorer', icon: Link2 },
        { id: 'bulk-import', label: 'Bulk Import', icon: UploadCloud },
        { id: 'health-analyzer', label: 'URL Health & SSRF', icon: ActivitySquare },
        { id: 'integrations', label: 'Search Engine APIs', icon: SearchCheck },
        { id: 'queues', label: 'Discovery & Retries', icon: Cpu },
        { id: 'reports', label: 'Reports & Export', icon: FileSpreadsheet },
        { id: 'admin', label: 'System Health & Admin', icon: ShieldAlert },
    ];

    return (
        <aside className="w-64 bg-slate-900/90 border-r border-slate-800 flex flex-col shrink-0 select-none backdrop-blur-md">
            {/* Brand Logo & Title */}
            <div className="h-16 flex items-center px-6 gap-3 border-b border-slate-800/80 bg-slate-950/40">
                <div className="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-cyan-400 flex items-center justify-center text-white shadow-lg shadow-indigo-500/20">
                    <Radio className="w-5 h-5 animate-pulse" />
                </div>
                <div>
                    <h1 className="text-base font-bold tracking-tight text-white flex items-center gap-1.5">
                        LinkPilot <span className="text-[10px] uppercase font-semibold px-1.5 py-0.5 rounded bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">SEO</span>
                    </h1>
                    <p className="text-[11px] text-slate-400 font-medium">Index & Crawl Monitor</p>
                </div>
            </div>

            {/* Navigation Links */}
            <nav className="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                <div className="px-3 pb-2 text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                    Platform Core
                </div>
                {navItems.map((item) => {
                    const Icon = item.icon;
                    const isActive = currentTab === item.id;
                    return (
                        <button
                            key={item.id}
                            onClick={() => setCurrentTab(item.id)}
                            className={`w-full flex items-center justify-between px-3.5 py-2.5 rounded-lg text-xs font-medium transition-all duration-150 ${
                                isActive
                                    ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30 font-semibold'
                                    : 'text-slate-300 hover:text-white hover:bg-slate-800/60'
                            }`}
                        >
                            <div className="flex items-center gap-3">
                                <Icon className={`w-4 h-4 ${isActive ? 'text-white' : 'text-slate-400'}`} />
                                <span>{item.label}</span>
                            </div>
                            {item.id === 'backlinks' && liveAlertCount > 0 && (
                                <span className="px-1.5 py-0.2 text-[10px] font-bold rounded-full bg-rose-500/20 text-rose-300 border border-rose-500/40">
                                    {liveAlertCount} lost
                                </span>
                            )}
                        </button>
                    );
                })}
            </nav>

            {/* Principles & Compliance Badge */}
            <div className="p-3.5 m-3 rounded-xl bg-slate-950/60 border border-slate-800/80 text-[11px] text-slate-400 space-y-1.5">
                <div className="flex items-center gap-1.5 font-semibold text-emerald-400 text-xs">
                    <span className="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                    SSRF & Policy Safe
                </div>
                <p className="leading-relaxed text-[11px] text-slate-400">
                    Strict authorization enforcement, private IP blocks, sanitized API audit logging.
                </p>
            </div>
        </aside>
    );
};
