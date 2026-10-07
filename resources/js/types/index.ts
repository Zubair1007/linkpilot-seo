export interface User {
    id: number;
    name: string;
    email: string;
    role: 'admin' | 'seo_specialist' | 'viewer';
    status: 'active' | 'suspended';
    api_rate_limit: number;
}

export interface Project {
    id: number;
    user_id: number;
    name: string;
    slug: string;
    description?: string;
    target_domain: string;
    slack_webhook_url?: string;
    alert_email?: string;
    alerts_enabled?: boolean;
    is_active: boolean;
    domains?: Domain[];
    campaigns?: Campaign[];
    backlinks_count?: number;
    created_at: string;
}

export interface Domain {
    id: number;
    project_id: number;
    domain: string;
    is_verified: boolean;
    verification_method?: string;
    verification_token?: string;
    verified_at?: string;
}

export interface Campaign {
    id: number;
    project_id: number;
    name: string;
    description?: string;
    check_frequency: '24h' | '72h' | '7d' | '14d' | '30d';
    is_active: boolean;
    next_run_at?: string;
    last_run_at?: string;
    backlinks_count?: number;
    project?: Project;
}

export interface Backlink {
    id: number;
    project_id: number;
    campaign_id: number;
    domain_id?: number;
    source_url: string;
    target_url: string;
    anchor_text?: string;
    link_type: 'dofollow' | 'nofollow' | 'ugc' | 'sponsored' | 'unknown';
    rel_attributes?: string[];
    domain_rating?: number;
    domain_authority?: number;
    page_authority?: number;
    authority_score?: number;
    metrics_updated_at?: string;
    http_status?: number;
    final_url?: string;
    canonical_url?: string;
    robots_status?: string;
    indexability: string;
    is_live: boolean;
    is_indexed: boolean;
    crawl_status: 'PENDING' | 'DISCOVERED' | 'CRAWLED' | 'ERROR';
    index_status: 'UNKNOWN' | 'PENDING' | 'DISCOVERED' | 'CRAWLED' | 'INDEXED' | 'NOT_INDEXED' | 'ERROR' | 'LOST';
    discovery_status: 'UNKNOWN' | 'PENDING' | 'SUBMITTED' | 'DISCOVERED';
    first_seen_at?: string;
    last_seen_at?: string;
    last_verified_at?: string;
    last_crawled_at?: string;
    last_indexed_check_at?: string;
    retry_count: number;
    max_retries: number;
    next_retry_at?: string;
    last_error?: string;
    project?: { id: number; name: string };
    campaign?: { id: number; name: string };
    events?: BacklinkEvent[];
    health_checks?: HealthCheck[];
}

export interface BacklinkEvent {
    id: number;
    backlink_id: number;
    event_type: 'added' | 'verified' | 'changed' | 'lost' | 'restored';
    old_data?: Record<string, any>;
    new_data?: Record<string, any>;
    notes?: string;
    created_at: string;
}

export interface HealthCheck {
    id: number;
    backlink_id: number;
    source_url: string;
    http_status?: number;
    response_time_ms?: number;
    redirect_count: number;
    final_url?: string;
    canonical_url?: string;
    meta_robots?: string;
    x_robots_tag?: string;
    robots_txt_status?: string;
    content_type?: string;
    is_https: boolean;
    page_title?: string;
    backlink_found: boolean;
    passed: boolean;
    error_message?: string;
    created_at: string;
}

export interface SearchEngineProperty {
    id: number;
    user_id: number;
    project_id?: number;
    provider: 'bing_webmaster' | 'google_search_console' | 'indexnow';
    property_url: string;
    is_authorized: boolean;
    authorization_status: string;
    authorized_at?: string;
    quota_daily: number;
    quota_used_today: number;
}

export interface DashboardMetrics {
    total_projects: number;
    total_campaigns: number;
    total_backlinks: number;
    live_backlinks: number;
    lost_backlinks: number;
    urls_checked: number;
    crawled_urls: number;
    indexed_urls: number;
    not_indexed_urls: number;
    index_rate: number;
    failed_checks: number;
    pending_jobs: number;
}
