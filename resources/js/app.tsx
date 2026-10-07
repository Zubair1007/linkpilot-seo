import React, { useState, useEffect } from 'react';
import { createRoot } from 'react-dom/client';
import api from './services/api';
import { User, Project, Campaign, DashboardMetrics } from './types';
import { Sidebar } from './components/Sidebar';
import { Header } from './components/Header';
import { DashboardView } from './views/DashboardView';
import { ProjectsView } from './views/ProjectsView';
import { CampaignsView } from './views/CampaignsView';
import { BacklinksView } from './views/BacklinksView';
import { BulkImportView } from './views/BulkImportView';
import { HealthAnalyzerView } from './views/HealthAnalyzerView';
import { IntegrationsView } from './views/IntegrationsView';
import { QueuesView } from './views/QueuesView';
import { ReportsView } from './views/ReportsView';
import { AdminHealthView } from './views/AdminHealthView';
import { AuthModal } from './views/AuthModal';

export const App: React.FC = () => {
    const [user, setUser] = useState<User | null>(null);
    const [showAuthModal, setShowAuthModal] = useState(false);
    const [currentTab, setCurrentTab] = useState<string>('dashboard');
    const [selectedProjectId, setSelectedProjectId] = useState<number | 'all'>('all');
    const [filterCampaignId, setFilterCampaignId] = useState<number | null>(null);

    const [projects, setProjects] = useState<Project[]>([]);
    const [campaigns, setCampaigns] = useState<Campaign[]>([]);
    const [metrics, setMetrics] = useState<DashboardMetrics | null>(null);
    const [recentEvents, setRecentEvents] = useState<any[]>([]);
    const [apiUsage, setApiUsage] = useState<any[]>([]);
    const [isLoading, setIsLoading] = useState(false);

    // Initial auth check
    useEffect(() => {
        const checkAuth = async () => {
            const token = localStorage.getItem('lp_token');
            if (!token) {
                setShowAuthModal(true);
                return;
            }

            try {
                const res = await api.get('/auth/me');
                setUser(res.data.user);
            } catch (err) {
                localStorage.removeItem('lp_token');
                setShowAuthModal(true);
            }
        };

        checkAuth();
    }, []);

    // Load projects and dashboard metrics
    const fetchCoreData = async () => {
        setIsLoading(true);
        try {
            const [dashRes, projRes, campRes] = await Promise.all([
                api.get('/dashboard'),
                api.get('/projects'),
                api.get('/campaigns'),
            ]);

            setMetrics(dashRes.data.metrics);
            setRecentEvents(dashRes.data.recent_events || []);
            setApiUsage(dashRes.data.api_usage || []);
            setProjects(projRes.data.data || []);
            setCampaigns(campRes.data.data || []);
        } catch (err) {
            console.error('Failed to load platform data', err);
        } finally {
            setIsLoading(false);
        }
    };

    useEffect(() => {
        if (user) {
            fetchCoreData();
        }
    }, [user, selectedProjectId]);

    const handleLoginSuccess = (loggedInUser: User, token: string) => {
        setUser(loggedInUser);
        setShowAuthModal(false);
        fetchCoreData();
    };

    const handleLogout = async () => {
        try {
            await api.post('/auth/logout');
        } catch (e) {
            // ignore
        }
        localStorage.removeItem('lp_token');
        setUser(null);
        setShowAuthModal(true);
    };

    const navigateToBacklinksWithCampaign = (campaignId: number) => {
        setFilterCampaignId(campaignId);
        setCurrentTab('backlinks');
    };

    return (
        <div className="flex h-screen bg-slate-950 text-slate-100 overflow-hidden font-sans">
            {/* Sidebar Navigation */}
            <Sidebar
                currentTab={currentTab}
                setCurrentTab={(tab) => {
                    if (tab !== 'backlinks') setFilterCampaignId(null);
                    setCurrentTab(tab);
                }}
                liveAlertCount={metrics?.lost_backlinks || 0}
            />

            {/* Main Application Area */}
            <div className="flex-1 flex flex-col min-w-0 overflow-hidden">
                {/* Header */}
                <Header
                    user={user}
                    projects={projects}
                    selectedProjectId={selectedProjectId}
                    setSelectedProjectId={setSelectedProjectId}
                    onLogout={handleLogout}
                    onRefresh={fetchCoreData}
                    isLoading={isLoading}
                />

                {/* Content View Container */}
                <main className="flex-1 overflow-y-auto p-6 md:p-8 space-y-6">
                    {currentTab === 'dashboard' && (
                        <DashboardView
                            metrics={metrics}
                            recentEvents={recentEvents}
                            apiUsage={apiUsage}
                            onNavigate={setCurrentTab}
                        />
                    )}

                    {currentTab === 'projects' && (
                        <ProjectsView
                            projects={projects}
                            onRefresh={fetchCoreData}
                        />
                    )}

                    {currentTab === 'campaigns' && (
                        <CampaignsView
                            campaigns={campaigns}
                            projects={projects}
                            onRefresh={fetchCoreData}
                            onSelectCampaign={navigateToBacklinksWithCampaign}
                        />
                    )}

                    {currentTab === 'backlinks' && (
                        <BacklinksView
                            projects={projects}
                            campaigns={campaigns}
                            initialCampaignId={filterCampaignId}
                        />
                    )}

                    {currentTab === 'bulk-import' && (
                        <BulkImportView
                            campaigns={campaigns}
                            onImportSuccess={() => {
                                fetchCoreData();
                                setCurrentTab('backlinks');
                            }}
                        />
                    )}

                    {currentTab === 'health-analyzer' && (
                        <HealthAnalyzerView />
                    )}

                    {currentTab === 'integrations' && (
                        <IntegrationsView
                            projects={projects}
                        />
                    )}

                    {currentTab === 'queues' && (
                        <QueuesView />
                    )}

                    {currentTab === 'reports' && (
                        <ReportsView
                            projects={projects}
                            campaigns={campaigns}
                        />
                    )}

                    {currentTab === 'admin' && (
                        <AdminHealthView />
                    )}
                </main>
            </div>

            {/* Authentication Modal */}
            {showAuthModal && (
                <AuthModal onLoginSuccess={handleLoginSuccess} />
            )}
        </div>
    );
};

const rootElement = document.getElementById('root');
if (rootElement) {
    createRoot(rootElement).render(<App />);
}
