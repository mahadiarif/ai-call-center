<x-filament-panels::page>
<div style="padding:0.5rem 0;">

    <!-- Summary Cards -->
    <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:1rem;margin-bottom:1.5rem;">
        <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;padding:1rem;text-align:center;">
            <div style="font-size:0.7rem;color:#6b7280;font-weight:600;text-transform:uppercase;">📅 Today</div>
            <div style="font-size:1.8rem;font-weight:800;color:#1d4ed8;margin:0.3rem 0;">{{ $summary['today_minutes'] }}</div>
            <div style="font-size:0.75rem;color:#6b7280;">Minutes</div>
        </div>
        <div style="background:#f0f9ff;border-radius:12px;border:1px solid #bae6fd;padding:1rem;text-align:center;">
            <div style="font-size:0.7rem;color:#0369a1;font-weight:600;text-transform:uppercase;">📆 This Week</div>
            <div style="font-size:1.8rem;font-weight:800;color:#0369a1;margin:0.3rem 0;">{{ $summary['week_minutes'] }}</div>
            <div style="font-size:0.75rem;color:#6b7280;">Minutes</div>
        </div>
        <div style="background:#ecfdf5;border-radius:12px;border:1px solid #a7f3d0;padding:1rem;text-align:center;">
            <div style="font-size:0.7rem;color:#047857;font-weight:600;text-transform:uppercase;">📊 This Month</div>
            <div style="font-size:1.8rem;font-weight:800;color:#059669;margin:0.3rem 0;">{{ $summary['month_minutes'] }}</div>
            <div style="font-size:0.75rem;color:#6b7280;">Minutes</div>
        </div>
        <div style="background:#fef3c7;border-radius:12px;border:1px solid #fde047;padding:1rem;text-align:center;">
            <div style="font-size:0.7rem;color:#92400e;font-weight:600;text-transform:uppercase;">📈 This Year</div>
            <div style="font-size:1.8rem;font-weight:800;color:#d97706;margin:0.3rem 0;">{{ $summary['year_minutes'] }}</div>
            <div style="font-size:0.75rem;color:#6b7280;">Minutes</div>
        </div>
        <div style="background:#faf5ff;border-radius:12px;border:1px solid #e9d5ff;padding:1rem;text-align:center;">
            <div style="font-size:0.7rem;color:#6d28d9;font-weight:600;text-transform:uppercase;">🗂️ Lifetime</div>
            <div style="font-size:1.8rem;font-weight:800;color:#7c3aed;margin:0.3rem 0;">{{ $summary['lifetime_minutes'] }}</div>
            <div style="font-size:0.75rem;color:#6b7280;">Total ({{ $summary['completed_calls'] }} calls)</div>
        </div>
    </div>

    <!-- Filter Buttons -->
    <div style="display:flex;gap:0.5rem;margin-bottom:1rem;flex-wrap:wrap;">
        <button wire:click="setFilter('daily')" 
                style="padding:0.6rem 1.2rem;border-radius:8px;font-weight:600;font-size:0.875rem;cursor:pointer;border:2px solid;transition:all 0.2s;
                {{ $filter === 'daily' ? 'background:#2563eb;color:#fff;border-color:#2563eb;' : 'background:#fff;color:#374151;border-color:#e5e7eb;' }}">
            📅 Daily
        </button>
        <button wire:click="setFilter('weekly')" 
                style="padding:0.6rem 1.2rem;border-radius:8px;font-weight:600;font-size:0.875rem;cursor:pointer;border:2px solid;transition:all 0.2s;
                {{ $filter === 'weekly' ? 'background:#2563eb;color:#fff;border-color:#2563eb;' : 'background:#fff;color:#374151;border-color:#e5e7eb;' }}">
            📆 Weekly
        </button>
        <button wire:click="setFilter('monthly')" 
                style="padding:0.6rem 1.2rem;border-radius:8px;font-weight:600;font-size:0.875rem;cursor:pointer;border:2px solid;transition:all 0.2s;
                {{ $filter === 'monthly' ? 'background:#2563eb;color:#fff;border-color:#2563eb;' : 'background:#fff;color:#374151;border-color:#e5e7eb;' }}">
            📊 Monthly
        </button>
        <button wire:click="setFilter('yearly')" 
                style="padding:0.6rem 1.2rem;border-radius:8px;font-weight:600;font-size:0.875rem;cursor:pointer;border:2px solid;transition:all 0.2s;
                {{ $filter === 'yearly' ? 'background:#2563eb;color:#fff;border-color:#2563eb;' : 'background:#fff;color:#374151;border-color:#e5e7eb;' }}">
            📈 Yearly
        </button>
        <button wire:click="setFilter('lifetime')" 
                style="padding:0.6rem 1.2rem;border-radius:8px;font-weight:600;font-size:0.875rem;cursor:pointer;border:2px solid;transition:all 0.2s;
                {{ $filter === 'lifetime' ? 'background:#2563eb;color:#fff;border-color:#2563eb;' : 'background:#fff;color:#374151;border-color:#e5e7eb;' }}">
            🗂️ Lifetime
        </button>
    </div>

    <!-- Data Table -->
    <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;overflow:hidden;">
        <div style="background:linear-gradient(135deg,#1e3a5f,#2563eb);padding:1rem 1.5rem;">
            <h3 style="color:#fff;font-size:1rem;font-weight:700;margin:0;">
                📈 Call Minutes Report - 
                @if($filter === 'daily') Last 30 Days
                @elseif($filter === 'weekly') Last 12 Weeks
                @elseif($filter === 'monthly') Last 12 Months
                @elseif($filter === 'yearly') All Years
                @else All Time
                @endif
            </h3>
            <p style="color:#93c5fd;font-size:0.75rem;margin:0.2rem 0 0;">Average Call Duration: {{ $summary['avg_minutes'] }} min | Drop/Missed calls not counted in minutes</p>
        </div>

        @if(count($data) > 0)
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:0.875rem;">
                <thead>
                    <tr style="background:#f9fafb;border-bottom:2px solid #e5e7eb;">
                        <th style="padding:0.8rem 1rem;text-align:left;color:#374151;font-weight:600;">Period</th>
                        <th style="padding:0.8rem 1rem;text-align:center;color:#374151;font-weight:600;">Total Calls</th>
                        <th style="padding:0.8rem 1rem;text-align:center;color:#16a34a;font-weight:600;">✅ Completed</th>
                        <th style="padding:0.8rem 1rem;text-align:center;color:#f59e0b;font-weight:600;">⚠️ Dropped</th>
                        <th style="padding:0.8rem 1rem;text-align:center;color:#ef4444;font-weight:600;">❌ Missed</th>
                        <th style="padding:0.8rem 1rem;text-align:center;color:#2563eb;font-weight:600;">⏱️ Total Minutes</th>
                        <th style="padding:0.8rem 1rem;text-align:center;color:#7c3aed;font-weight:600;">📊 Avg Min/Call</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data as $i => $row)
                    <tr style="border-bottom:1px solid #f3f4f6;{{ $i === 0 ? 'background:#fffbeb;' : ($i % 2 === 0 ? 'background:#fff;' : 'background:#f9fafb;') }}">
                        <td style="padding:0.8rem 1rem;font-weight:{{ $i === 0 ? '700' : '500' }};color:#111827;">
                            {{ $row['period'] }}
                            @if($i === 0 && $filter !== 'lifetime')<span style="background:#fef3c7;color:#92400e;font-size:0.65rem;padding:0.15rem 0.5rem;border-radius:10px;margin-left:6px;">Current</span>@endif
                        </td>
                        <td style="padding:0.8rem 1rem;text-align:center;font-weight:600;color:#374151;">{{ $row['total'] }}</td>
                        <td style="padding:0.8rem 1rem;text-align:center;font-weight:600;color:#16a34a;">{{ $row['completed'] }}</td>
                        <td style="padding:0.8rem 1rem;text-align:center;font-weight:600;color:#f59e0b;">{{ $row['dropped'] }}</td>
                        <td style="padding:0.8rem 1rem;text-align:center;font-weight:600;color:#ef4444;">{{ $row['missed'] }}</td>
                        <td style="padding:0.8rem 1rem;text-align:center;font-weight:700;font-size:1rem;color:#2563eb;">{{ $row['minutes'] }} min</td>
                        <td style="padding:0.8rem 1rem;text-align:center;font-weight:600;color:#7c3aed;">{{ $row['avg_min'] }} min</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div style="padding:3rem;text-align:center;color:#9ca3af;">
            <div style="font-size:3rem;">📞</div>
            <div style="margin-top:0.5rem;">No call logs yet</div>
        </div>
        @endif
    </div>

</div>
</x-filament-panels::page>
