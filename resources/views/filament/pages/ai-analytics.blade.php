<x-filament-panels::page>
<div style="padding:0.5rem 0;">

    <!-- Summary Cards -->
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.5rem;">
        <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;padding:1.2rem;text-align:center;">
            <div style="font-size:0.75rem;color:#6b7280;font-weight:600;text-transform:uppercase;">🎫 Total Tickets</div>
            <div style="font-size:2.2rem;font-weight:800;color:#1d4ed8;margin:0.4rem 0;">{{ $ticketStats['total'] }}</div>
        </div>
        <div style="background:#fffbeb;border-radius:12px;border:1px solid #fde68a;padding:1.2rem;text-align:center;">
            <div style="font-size:0.75rem;color:#92400e;font-weight:600;text-transform:uppercase;">⏳ Pending</div>
            <div style="font-size:2.2rem;font-weight:800;color:#d97706;margin:0.4rem 0;">{{ $ticketStats['pending'] }}</div>
        </div>
        <div style="background:#f0fdf4;border-radius:12px;border:1px solid #bbf7d0;padding:1.2rem;text-align:center;">
            <div style="font-size:0.75rem;color:#166534;font-weight:600;text-transform:uppercase;">✅ Resolved</div>
            <div style="font-size:2.2rem;font-weight:800;color:#16a34a;margin:0.4rem 0;">{{ $ticketStats['resolved'] }}</div>
        </div>
        <div style="background:#f0f9ff;border-radius:12px;border:1px solid #bae6fd;padding:1.2rem;text-align:center;">
            <div style="font-size:0.75rem;color:#0369a1;font-weight:600;text-transform:uppercase;">📅 Today</div>
            <div style="font-size:2.2rem;font-weight:800;color:#0369a1;margin:0.4rem 0;">{{ $ticketStats['today'] }}</div>
        </div>
    </div>

    <!-- Chart Card -->
    <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;padding:1.5rem;margin-bottom:1.5rem;">
        <h3 style="font-size:1rem;font-weight:700;color:#111827;margin:0 0 1rem 0;">📊 Last 7 Days Ticket Trend</h3>
        <canvas id="ticketChart" style="max-height:300px;"></canvas>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('ticketChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: @json($chartData['labels']),
            datasets: [{
                label: 'AI Tickets',
                data: @json($chartData['data']),
                backgroundColor: 'rgba(251,191,36,0.15)',
                borderColor: 'rgb(251,191,36)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: 'rgb(251,191,36)',
                pointRadius: 5,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });
});
</script>
</x-filament-panels::page>
