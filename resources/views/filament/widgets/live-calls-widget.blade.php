<x-filament-widgets::widget>
    <x-filament::section>
        <div>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <span style="display:inline-block;width:12px;height:12px;border-radius:50%;
                        background:{{ $this->activeCalls > 0 ? '#22c55e' : '#9ca3af' }};
                        box-shadow:0 0 0 4px {{ $this->activeCalls > 0 ? 'rgba(34,197,94,0.25)' : 'rgba(156,163,175,0.2)' }};"></span>
                    <strong style="font-size:1.1rem;">📞 Live Call Monitor</strong>
                </div>
                <span style="font-size:0.75rem;color:#9ca3af;">🕐 {{ $this->currentTime }} (BD Time)</span>
            </div>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:16px;">
                <div style="text-align:center;padding:16px;border-radius:12px;
                    border:2px solid {{ $this->activeCalls > 0 ? '#86efac' : '#e5e7eb' }};
                    background:{{ $this->activeCalls > 0 ? '#f0fdf4' : '#f9fafb' }};">
                    <div style="font-size:2.5rem;font-weight:900;color:{{ $this->activeCalls > 0 ? '#16a34a' : '#9ca3af' }};">{{ $this->activeCalls }}</div>
                    <div style="font-size:0.75rem;margin-top:4px;color:{{ $this->activeCalls > 0 ? '#15803d' : '#6b7280' }};">🔴 এখন Live কল</div>
                </div>
                <div style="text-align:center;padding:16px;border-radius:12px;border:2px solid #93c5fd;background:#eff6ff;">
                    <div style="font-size:2.5rem;font-weight:900;color:#1d4ed8;">{{ $this->lastHour }}</div>
                    <div style="font-size:0.75rem;color:#1e40af;margin-top:4px;">⏱ শেষ ১ ঘণ্টা</div>
                </div>
                <div style="text-align:center;padding:16px;border-radius:12px;border:2px solid #c4b5fd;background:#faf5ff;">
                    <div style="font-size:2.5rem;font-weight:900;color:#7c3aed;">{{ $this->todayTotal }}</div>
                    <div style="font-size:0.75rem;color:#6d28d9;margin-top:4px;">📅 আজকের মোট কল</div>
                </div>
            </div>
            @if($this->recentCalls && $this->recentCalls->count() > 0)
            <p style="font-size:0.85rem;font-weight:600;color:#374151;margin-bottom:8px;">🕑 সাম্প্রতিক কলসমূহ</p>
            <div style="overflow-x:auto;border-radius:8px;border:1px solid #e5e7eb;">
                <table style="width:100%;border-collapse:collapse;font-size:0.85rem;">
                    <thead>
                        <tr style="background:#f9fafb;">
                            <th style="padding:8px 12px;text-align:left;font-size:0.75rem;color:#6b7280;">কাস্টমার</th>
                            <th style="padding:8px 12px;text-align:left;font-size:0.75rem;color:#6b7280;">মোবাইল</th>
                            <th style="padding:8px 12px;text-align:left;font-size:0.75rem;color:#6b7280;">IVR সার্ভিস</th>
                            <th style="padding:8px 12px;text-align:left;font-size:0.75rem;color:#6b7280;">স্ট্যাটাস</th>
                            <th style="padding:8px 12px;text-align:left;font-size:0.75rem;color:#6b7280;">সময়</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($this->recentCalls as $i => $call)
                        @php
                            $isRecent = $call->created_at->diffInMinutes(now()) < 5;
                            $rowBg = $isRecent ? '#f0fdf4' : ($i % 2 === 0 ? '#ffffff' : '#f9fafb');
                            $statusStyle = match($call->status) {
                                'Pending'  => 'background:#fef9c3;color:#854d0e;',
                                'Resolved' => 'background:#dcfce7;color:#166534;',
                                'Rejected' => 'background:#fee2e2;color:#991b1b;',
                                default    => 'background:#f3f4f6;color:#374151;',
                            };
                        @endphp
                        <tr style="background:{{ $rowBg }};border-top:1px solid #f3f4f6;">
                            <td style="padding:8px 12px;">
                                @if($isRecent)<span style="display:inline-block;width:8px;height:8px;background:#22c55e;border-radius:50%;margin-right:6px;"></span>@endif
                                {{ $call->customer_name ?? 'অজানা' }}
                            </td>
                            <td style="padding:8px 12px;color:#6b7280;">{{ $call->mobile_number ?? 'N/A' }}</td>
                            <td style="padding:8px 12px;color:#6b7280;">{{ $call->ivrService?->service_name ?? 'সাধারণ' }}</td>
                            <td style="padding:8px 12px;">
                                <span style="padding:2px 8px;border-radius:9999px;font-size:0.75rem;font-weight:600;{{ $statusStyle }}">{{ $call->status }}</span>
                            </td>
                            <td style="padding:8px 12px;font-size:0.75rem;color:#9ca3af;">{{ $call->created_at->diffForHumans() }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div style="text-align:center;padding:24px;color:#9ca3af;">
                <div style="font-size:2.5rem;margin-bottom:8px;">📵</div>
                <p>এখন কোনো কল নেই</p>
            </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>