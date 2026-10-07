<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $reportTitle ?? 'LinkPilot SEO Executive Backlink Report' }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            font-size: 11px;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
        }
        .header {
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .brand {
            font-size: 20px;
            font-weight: bold;
            color: #4f46e5;
            letter-spacing: -0.5px;
        }
        .subtitle {
            font-size: 11px;
            color: #64748b;
            margin-top: 3px;
        }
        .meta-box {
            float: right;
            text-align: right;
            font-size: 10px;
            color: #475569;
        }
        .clear {
            clear: both;
        }
        .kpi-container {
            margin-bottom: 25px;
        }
        .kpi-card {
            float: left;
            width: 22%;
            margin-right: 3%;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px;
            text-align: center;
        }
        .kpi-card.last {
            margin-right: 0;
        }
        .kpi-val {
            font-size: 22px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 4px;
        }
        .kpi-lbl {
            font-size: 9px;
            text-transform: uppercase;
            font-weight: bold;
            color: #64748b;
        }
        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 6px;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th {
            background-color: #f1f5f9;
            color: #475569;
            font-size: 9px;
            text-transform: uppercase;
            font-weight: bold;
            text-align: left;
            padding: 8px 6px;
            border-bottom: 1px solid #cbd5e1;
        }
        td {
            padding: 7px 6px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 10px;
            vertical-align: top;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-live { background-color: #dcfce7; color: #15803d; }
        .badge-lost { background-color: #fee2e2; color: #b91c1c; }
        .badge-indexed { background-color: #e0e7ff; color: #4338ca; }
        .badge-notindexed { background-color: #fef3c7; color: #b45309; }
        .footer {
            margin-top: 40px;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
            text-align: center;
            font-size: 9px;
            color: #94a3b8;
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="meta-box">
            <div><strong>Generated:</strong> {{ now()->format('M d, Y - H:i T') }}</div>
            <div><strong>Target Domain:</strong> {{ $project->target_domain ?? 'Global Portfolio' }}</div>
            <div><strong>Project:</strong> {{ $project->name ?? 'All Campaigns' }}</div>
        </div>
        <div class="brand">LinkPilot SEO</div>
        <div class="subtitle">Executive Backlink Discovery, Health & Index Monitoring Audit</div>
        <div class="clear"></div>
    </div>

    <!-- KPI Summary Row -->
    <div class="kpi-container">
        <div class="kpi-card">
            <div class="kpi-lbl">Total Backlinks</div>
            <div class="kpi-val">{{ $metrics['total_backlinks'] ?? count($backlinks) }}</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-lbl">Live & Verified</div>
            <div class="kpi-val" style="color: #16a34a;">{{ $metrics['live_backlinks'] ?? 0 }}</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-lbl">Lost / Dropped</div>
            <div class="kpi-val" style="color: #dc2626;">{{ $metrics['lost_backlinks'] ?? 0 }}</div>
        </div>
        <div class="kpi-card last">
            <div class="kpi-lbl">Indexation Ratio</div>
            <div class="kpi-val" style="color: #4f46e5;">{{ $metrics['index_rate'] ?? 0 }}%</div>
        </div>
        <div class="clear"></div>
    </div>

    <!-- Critical Attention: Lost Backlinks -->
    @if(count($lostBacklinks) > 0)
    <div class="section-title" style="color: #b91c1c;">⚠️ Action Required: Lost or Dropped Backlinks ({{ count($lostBacklinks) }})</div>
    <table>
        <thead>
            <tr>
                <th style="width: 35%;">Source Page</th>
                <th style="width: 25%;">Target URL</th>
                <th style="width: 15%;">Anchor Text</th>
                <th style="width: 10%;">HTTP Status</th>
                <th style="width: 15%;">Issue Diagnostics</th>
            </tr>
        </thead>
        <tbody>
            @foreach($lostBacklinks as $lb)
            <tr>
                <td><strong>{{ $lb->source_url }}</strong></td>
                <td>{{ $lb->target_url }}</td>
                <td>{{ $lb->anchor_text ?? 'N/A' }}</td>
                <td>{{ $lb->http_status ?? '404' }}</td>
                <td style="color: #b91c1c;">{{ $lb->last_error ?? 'Anchor removed from HTML' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <!-- Comprehensive Backlinks Inventory -->
    <div class="section-title">Backlink Health & Indexation Inventory</div>
    <table>
        <thead>
            <tr>
                <th style="width: 32%;">Source URL</th>
                <th style="width: 20%;">Target URL</th>
                <th style="width: 14%;">Anchor Text</th>
                <th style="width: 8%;">Type</th>
                <th style="width: 6%;">HTTP</th>
                <th style="width: 10%;">Crawl</th>
                <th style="width: 10%;">Index State</th>
            </tr>
        </thead>
        <tbody>
            @foreach($backlinks as $b)
            <tr>
                <td>{{ $b->source_url }}</td>
                <td>{{ $b->target_url }}</td>
                <td>{{ $b->anchor_text ?? 'None' }}</td>
                <td><span class="badge">{{ $b->link_type }}</span></td>
                <td>{{ $b->http_status ?? '-' }}</td>
                <td>{{ $b->crawl_status }}</td>
                <td>
                    <span class="badge {{ $b->index_status == 'INDEXED' ? 'badge-indexed' : ($b->index_status == 'LOST' ? 'badge-lost' : 'badge-notindexed') }}">
                        {{ $b->index_status }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        LinkPilot SEO Platform • Search Engine Compliance & Discovery Engine • All rights reserved.
    </div>

</body>
</html>
