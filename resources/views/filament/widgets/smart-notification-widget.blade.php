<div style="padding:0.5rem 0;">
    @foreach($this->alerts as $alert)
        @php
            $styles = [
                'danger'  => 'background:#fef2f2;border-left:4px solid #ef4444;color:#991b1b;',
                'warning' => 'background:#fffbeb;border-left:4px solid #f59e0b;color:#92400e;',
                'success' => 'background:#f0fdf4;border-left:4px solid #22c55e;color:#166534;',
            ];
            $s = $styles[$alert['type']] ?? $styles['success'];
        @endphp
        <div style="{{ $s }} display:flex;align-items:center;justify-content:space-between;border-radius:6px;padding:0.6rem 1rem;margin-bottom:0.4rem;">
            <span style="font-size:0.875rem;font-weight:500;">
                {{ $alert['icon'] }} {{ $alert['message'] }}
            </span>
            @if($alert['link'])
                <a href="{{ $alert['link'] }}" style="font-size:0.75rem;font-weight:600;text-decoration:underline;white-space:nowrap;margin-left:1rem;color:inherit;">
                    দেখুন →
                </a>
            @endif
        </div>
    @endforeach
</div>
