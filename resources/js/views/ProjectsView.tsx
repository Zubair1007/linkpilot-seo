import React, { useState } from 'react';
import { Project } from '../types';
import api from '../services/api';
import { FolderPlus, Globe, CheckCircle2, XCircle, Plus, ShieldCheck, Trash2 } from 'lucide-react';

interface ProjectsViewProps {
    projects: Project[];
    onRefresh: () => void;
}

export const ProjectsView: React.FC<ProjectsViewProps> = ({ projects, onRefresh }) => {
    const [isCreatingProject, setIsCreatingProject] = useState(false);
    const [newProjectName, setNewProjectName] = useState('');
    const [newTargetDomain, setNewTargetDomain] = useState('');
    const [newDescription, setNewDescription] = useState('');
    const [newSlackWebhook, setNewSlackWebhook] = useState('');
    const [newAlertEmail, setNewAlertEmail] = useState('');

    const [domainModalProjectId, setDomainModalProjectId] = useState<number | null>(null);
    const [newDomainInput, setNewDomainInput] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleCreateProject = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!newProjectName || !newTargetDomain) return;

        setIsSubmitting(true);
        try {
            await api.post('/projects', {
                name: newProjectName,
                target_domain: newTargetDomain,
                description: newDescription,
                slack_webhook_url: newSlackWebhook || null,
                alert_email: newAlertEmail || null,
            });
            setIsCreatingProject(false);
            setNewProjectName('');
            setNewTargetDomain('');
            setNewDescription('');
            setNewSlackWebhook('');
            setNewAlertEmail('');
            onRefresh();
        } catch (err: any) {
            alert('Failed to create project: ' + (err.response?.data?.message || err.message));
        } finally {
            setIsSubmitting(false);
        }
    };

    const handleTestSlack = async (webhookUrl?: string) => {
        const url = webhookUrl || prompt('Enter Slack Webhook URL to test:');
        if (!url) return;
        try {
            const res = await api.post('/projects/test-slack', { webhook_url: url });
            alert(res.data.message);
        } catch (err: any) {
            alert('Test failed: ' + (err.response?.data?.message || err.message));
        }
    };

    const handleAddDomain = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!domainModalProjectId || !newDomainInput) return;

        setIsSubmitting(true);
        try {
            await api.post(`/projects/${domainModalProjectId}/domains`, {
                domain: newDomainInput,
                verification_method: 'dns',
            });
            setDomainModalProjectId(null);
            setNewDomainInput('');
            onRefresh();
        } catch (err: any) {
            alert('Failed to add domain: ' + (err.response?.data?.message || err.message));
        } finally {
            setIsSubmitting(false);
        }
    };

    const handleVerifyDomain = async (domainId: number) => {
        try {
            await api.post(`/domains/${domainId}/verify`);
            onRefresh();
        } catch (err: any) {
            alert('Verification failed: ' + (err.response?.data?.message || err.message));
        }
    };

    const handleDeleteProject = async (projectId: number) => {
        if (!confirm('Are you sure you want to delete this project and its campaigns?')) return;
        try {
            await api.delete(`/projects/${projectId}`);
            onRefresh();
        } catch (err: any) {
            alert('Delete failed: ' + (err.response?.data?.message || err.message));
        }
    };

    return (
        <div className="space-y-6">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 className="text-xl font-bold text-white">Client Projects & Domain Registry</h2>
                    <p className="text-xs text-slate-400 mt-1">
                        Group campaigns by client property and verify domain ownership for search engine API access.
                    </p>
                </div>
                <button
                    onClick={() => setIsCreatingProject(true)}
                    className="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-md shadow-indigo-600/30 transition flex items-center gap-2 self-start"
                >
                    <FolderPlus className="w-4 h-4" />
                    <span>Create New Project</span>
                </button>
            </div>

            {/* Projects Grid */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {projects.map((project) => (
                    <div key={project.id} className="glass-panel p-6 rounded-2xl space-y-4 hover:border-slate-700 transition">
                        <div className="flex items-start justify-between">
                            <div>
                                <h3 className="text-base font-bold text-white flex items-center gap-2">
                                    {project.name}
                                    <span className="text-[10px] px-2 py-0.5 rounded-full bg-slate-800 border border-slate-700 text-slate-300 font-mono">
                                        {project.target_domain}
                                    </span>
                                </h3>
                                {project.description && (
                                    <p className="text-xs text-slate-400 mt-1 leading-relaxed">{project.description}</p>
                                )}
                            </div>
                            <button
                                onClick={() => handleDeleteProject(project.id)}
                                title="Delete Project"
                                className="text-slate-500 hover:text-rose-400 p-1.5 rounded-lg hover:bg-slate-800 transition"
                            >
                                <Trash2 className="w-4 h-4" />
                            </button>
                        </div>

                        {/* Associated Verified Domains */}
                        <div className="pt-2 border-t border-slate-800/80">
                            <div className="flex items-center justify-between mb-2">
                                <span className="text-xs font-semibold text-slate-400 flex items-center gap-1.5">
                                    <Globe className="w-3.5 h-3.5 text-indigo-400" />
                                    Authorized Domains ({project.domains?.length || 0})
                                </span>
                                <button
                                    onClick={() => setDomainModalProjectId(project.id)}
                                    className="text-[11px] text-indigo-400 hover:text-indigo-300 font-semibold flex items-center gap-1"
                                >
                                    <Plus className="w-3 h-3" />
                                    <span>Add Domain</span>
                                </button>
                            </div>

                            <div className="space-y-2">
                                {project.domains && project.domains.length > 0 ? (
                                    project.domains.map((dom) => (
                                        <div
                                            key={dom.id}
                                            className="p-2.5 rounded-xl bg-slate-950/60 border border-slate-800/70 flex items-center justify-between"
                                        >
                                            <div className="flex items-center gap-2">
                                                <span className="text-xs font-medium text-slate-200">{dom.domain}</span>
                                                {dom.is_verified ? (
                                                    <span className="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 flex items-center gap-1">
                                                        <CheckCircle2 className="w-3 h-3" />
                                                        Verified
                                                    </span>
                                                ) : (
                                                    <span className="text-[10px] px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/20 flex items-center gap-1">
                                                        <XCircle className="w-3 h-3" />
                                                        Unverified
                                                    </span>
                                                )}
                                            </div>

                                            {!dom.is_verified && (
                                                <button
                                                    onClick={() => handleVerifyDomain(dom.id)}
                                                    className="px-2.5 py-1 rounded-lg bg-indigo-600/20 hover:bg-indigo-600/40 text-indigo-300 text-[11px] font-semibold border border-indigo-500/30 transition"
                                                >
                                                    Verify Now
                                                </button>
                                            )}
                                        </div>
                                    ))
                                ) : (
                                    <div className="text-xs text-slate-500 py-2 italic">No secondary domains added.</div>
                                )}
                            </div>
                        </div>

                        {/* Alerts Configuration Badge */}
                        <div className="pt-2 border-t border-slate-800/80 flex items-center justify-between text-[11px]">
                            <div className="flex items-center gap-2">
                                <span className="font-semibold text-slate-400">Alert Channel:</span>
                                {project.slack_webhook_url ? (
                                    <span className="px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-semibold">
                                        Slack Active
                                    </span>
                                ) : (
                                    <span className="text-slate-500 italic">No Slack configured</span>
                                )}
                            </div>
                            <button
                                onClick={() => handleTestSlack(project.slack_webhook_url)}
                                className="text-indigo-400 hover:text-indigo-300 font-semibold text-[11px]"
                            >
                                Test Slack Ping
                            </button>
                        </div>

                        {/* Project Footer Status */}
                        <div className="flex items-center justify-between pt-2 text-[11px] text-slate-500">
                            <div>Campaigns: <span className="text-slate-300 font-semibold">{project.campaigns?.length || 0}</span></div>
                            <div>Backlinks: <span className="text-slate-300 font-semibold">{project.backlinks_count || 0}</span></div>
                        </div>
                    </div>
                ))}
            </div>

            {/* Create Project Modal */}
            {isCreatingProject && (
                <div className="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                    <div className="glass-panel p-6 rounded-2xl w-full max-w-md space-y-4 border border-slate-700 shadow-2xl">
                        <div className="flex items-center justify-between border-b border-slate-800 pb-3">
                            <h3 className="text-sm font-bold text-white">Create Client Project</h3>
                            <button onClick={() => setIsCreatingProject(false)} className="text-slate-400 hover:text-white">✕</button>
                        </div>
                        <form onSubmit={handleCreateProject} className="space-y-3.5">
                            <div>
                                <label className="block text-xs font-semibold text-slate-300 mb-1">Project Name</label>
                                <input
                                    type="text"
                                    required
                                    placeholder="e.g. Acme SaaS Growth"
                                    value={newProjectName}
                                    onChange={(e) => setNewProjectName(e.target.value)}
                                    className="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-100 focus:outline-none focus:border-indigo-500"
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-300 mb-1">Target Domain</label>
                                <input
                                    type="text"
                                    required
                                    placeholder="e.g. acme.io"
                                    value={newTargetDomain}
                                    onChange={(e) => setNewTargetDomain(e.target.value)}
                                    className="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-100 focus:outline-none focus:border-indigo-500"
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-300 mb-1">Slack Webhook URL (For Lost Link Alerts)</label>
                                <input
                                    type="url"
                                    placeholder="https://hooks.slack.com/services/..."
                                    value={newSlackWebhook}
                                    onChange={(e) => setNewSlackWebhook(e.target.value)}
                                    className="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-100 font-mono focus:outline-none focus:border-indigo-500"
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-300 mb-1">Alert Email Recipient</label>
                                <input
                                    type="email"
                                    placeholder="alerts@clientdomain.com"
                                    value={newAlertEmail}
                                    onChange={(e) => setNewAlertEmail(e.target.value)}
                                    className="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-100 focus:outline-none focus:border-indigo-500"
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-300 mb-1">Description (Optional)</label>
                                <textarea
                                    rows={2}
                                    value={newDescription}
                                    onChange={(e) => setNewDescription(e.target.value)}
                                    className="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-100 focus:outline-none focus:border-indigo-500"
                                />
                            </div>
                            <div className="flex justify-end gap-2 pt-2">
                                <button
                                    type="button"
                                    onClick={() => setIsCreatingProject(false)}
                                    className="px-3.5 py-1.5 text-xs text-slate-400 hover:text-white"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    disabled={isSubmitting}
                                    className="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-xs font-semibold"
                                >
                                    {isSubmitting ? 'Creating...' : 'Create Project'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Add Domain Modal */}
            {domainModalProjectId && (
                <div className="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                    <div className="glass-panel p-6 rounded-2xl w-full max-w-md space-y-4 border border-slate-700 shadow-2xl">
                        <div className="flex items-center justify-between border-b border-slate-800 pb-3">
                            <h3 className="text-sm font-bold text-white">Add Domain to Project</h3>
                            <button onClick={() => setDomainModalProjectId(null)} className="text-slate-400 hover:text-white">✕</button>
                        </div>
                        <form onSubmit={handleAddDomain} className="space-y-3.5">
                            <div>
                                <label className="block text-xs font-semibold text-slate-300 mb-1">Domain Hostname</label>
                                <input
                                    type="text"
                                    required
                                    placeholder="e.g. blog.acme.io"
                                    value={newDomainInput}
                                    onChange={(e) => setNewDomainInput(e.target.value)}
                                    className="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-100 focus:outline-none focus:border-indigo-500"
                                />
                            </div>
                            <div className="flex justify-end gap-2 pt-2">
                                <button
                                    type="button"
                                    onClick={() => setDomainModalProjectId(null)}
                                    className="px-3.5 py-1.5 text-xs text-slate-400 hover:text-white"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    disabled={isSubmitting}
                                    className="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-xs font-semibold"
                                >
                                    {isSubmitting ? 'Adding...' : 'Add Domain'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
};
