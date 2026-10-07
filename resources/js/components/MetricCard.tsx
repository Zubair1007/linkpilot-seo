import React from 'react';
import { LucideIcon } from 'lucide-react';

interface MetricCardProps {
    title: string;
    value: string | number;
    subtitle?: string;
    icon: LucideIcon;
    trend?: string;
    colorScheme?: 'indigo' | 'emerald' | 'rose' | 'amber' | 'cyan' | 'slate';
}

export const MetricCard: React.FC<MetricCardProps> = ({
    title,
    value,
    subtitle,
    icon: Icon,
    trend,
    colorScheme = 'indigo',
}) => {
    const colorClasses = {
        indigo: 'border-indigo-500/20 bg-indigo-500/5 text-indigo-400',
        emerald: 'border-emerald-500/20 bg-emerald-500/5 text-emerald-400',
        rose: 'border-rose-500/20 bg-rose-500/5 text-rose-400',
        amber: 'border-amber-500/20 bg-amber-500/5 text-amber-400',
        cyan: 'border-cyan-500/20 bg-cyan-500/5 text-cyan-400',
        slate: 'border-slate-700/60 bg-slate-800/40 text-slate-400',
    };

    return (
        <div className="glass-panel p-5 rounded-2xl relative overflow-hidden transition-all duration-200 hover:border-slate-700 hover:shadow-lg">
            <div className="flex items-center justify-between">
                <span className="text-xs font-semibold text-slate-400 uppercase tracking-wider">{title}</span>
                <div className={`p-2.5 rounded-xl border ${colorClasses[colorScheme]}`}>
                    <Icon className="w-5 h-5" />
                </div>
            </div>

            <div className="mt-4 flex items-baseline gap-2">
                <div className="text-3xl font-extrabold text-white tracking-tight">{value}</div>
                {trend && (
                    <span className="text-xs font-medium text-emerald-400 bg-emerald-500/10 px-1.5 py-0.5 rounded">
                        {trend}
                    </span>
                )}
            </div>

            {subtitle && (
                <p className="mt-1 text-xs text-slate-400 font-medium">
                    {subtitle}
                </p>
            )}
        </div>
    );
};
