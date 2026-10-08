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
    userRole?: string;
}

export const Sidebar: React.FC<SidebarProps> = ({ currentTab, setCurrentTab, liveAlertCount, userRole }) => {
    const allNavItems = [
        { id: 'dashboard', label: 'Executive Dashboard', path: '/dashboard', icon: LayoutDashboard, adminOnly: false },
        { id: 'projects', label: 'Projects & Domains', path: '/projects', icon: FolderKanban, adminOnly: false },
        { id: 'campaigns', label: 'Campaigns', path: '/campaigns', icon: Target, adminOnly: false },
        { id: 'backlinks', label: 'Backlinks Explorer', path: '/backlinks', icon: Link2, adminOnly: false },
        { id: 'bulk-import', label: 'Bulk Import', path: '/bulk-import', icon: UploadCloud, adminOnly: false },
        { id: 'health-analyzer', label: 'URL Health & SSRF', path: '/health-analyzer', icon: ActivitySquare, adminOnly: false },
        { id: 'integrations', label: 'Search Engine APIs', path: '/integrations', icon: SearchCheck, adminOnly: true },
        { id: 'queues', label: 'Discovery & Retries', path: '/queues', icon: Cpu, adminOnly: false },
        { id: 'reports', label: 'Reports & Export', path: '/reports', icon: FileSpreadsheet, adminOnly: false },
        { id: 'admin', label: 'System Health & Admin', path: '/admin', icon: ShieldAlert, adminOnly: true },
    ];

    const navItems = allNavItems.filter(item => !item.adminOnly || userRole === 'admin');

    return (
        <aside className="w-64 bg-slate-900/90 border-r border-slate-800 flex flex-col shrink-0 select-none backdrop-blur-md">
            {/* Brand Logo & Title */}
            <a
                href="/dashboard"
                onClick={(e) => {
                    e.preventDefault();
                    setCurrentTab('dashboard');
                }}
                className="h-16 flex items-center px-6 gap-3 border-b border-slate-800/80 bg-slate-950/40 hover:bg-slate-900/50 transition cursor-pointer"
            >
                <div className="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-cyan-400 flex items-center justify-center text-white shadow-lg shadow-indigo-500/20">
                    <Radio className="w-5 h-5 animate-pulse" />
                </div>
                <div>
                    <h1 className="text-base font-bold tracking-tight text-white flex items-center gap-1.5">
                        LinkPilot <span className="text-[10px] uppercase font-semibold px-1.5 py-0.5 rounded bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">SEO</span>
                    </h1>
                    <p className="text-[11px] text-slate-400 font-medium">Index & Crawl Monitor</p>
                </div>
            </a>

            {/* Navigation Links */}
            <nav className="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                <div className="px-3 pb-2 text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                    Platform Core
                </div>
                {navItems.map((item) => {
                    const Icon = item.icon;
                    const isActive = currentTab === item.id;
                    return (
                        <a
                            key={item.id}
                            href={item.path}
                            onClick={(e) => {
                                e.preventDefault();
                                setCurrentTab(item.id);
                            }}
                            className={`w-full flex items-center justify-between px-3.5 py-2.5 rounded-lg text-xs font-medium transition-all duration-150 cursor-pointer ${
                                isActive
                                    ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30 font-semibold ring-1 ring-indigo-400/40'
                                    : 'text-slate-300 hover:text-white hover:bg-slate-800/60'
                            }`}
                        >
                            <div className="flex items-center gap-3">
                                <Icon className={`w-4 h-4 ${isActive ? 'text-white' : 'text-slate-400'}`} />
                                <span>{item.label}</span>
                            </div>
                            <div className="flex items-center gap-1.5">
                                {item.id === 'backlinks' && liveAlertCount > 0 && !isActive && (
                                    <span className="px-1.5 py-0.2 text-[10px] font-bold rounded-full bg-rose-500/20 text-rose-300 border border-rose-500/40">
                                        {liveAlertCount} lost
                                    </span>
                                )}
                                {isActive && (
                                    <span className="w-1.5 h-1.5 rounded-full bg-cyan-300 shadow-sm animate-pulse"></span>
                                )}
                            </div>
                        </a>
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
