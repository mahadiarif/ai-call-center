<div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;overflow:hidden;margin-bottom:1rem;">
    <!-- Header -->
    <div style="background:linear-gradient(135deg,#1e3a5f,#2563eb);padding:1rem 1.5rem;display:flex;justify-content:space-between;align-items:center;">
        <div>
            <h3 style="color:#fff;font-size:1.1rem;font-weight:700;margin:0;">⏱️ কল মিনিট রিপোর্ট</h3>
            <p style="color:#93c5fd;font-size:0.8rem;margin:0;">শুধুমাত্র সম্পন্ন (completed) কলের মিনিট হিসাব</p>
        </div>
        <span style="background:rgba(255,255,255,0.15);color:#fff;padding:0.3rem 0.8rem;border-radius:20px;font-size:0.75rem;">⏰ প্রতি ৬০ সেকেন্ডে আপডেট</span>
    </div>

    <!-- Summary Cards -->
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:0;border-bottom:1px solid #e5e7eb;">

        <div style="padding:1.2rem;border-right:1px solid #e5e7eb;text-align:center;">
            <div style="font-size:0.75rem;color:#6b7280;font-weight:600;text-transform:uppercase;">📅 আজকে</div>
            <div style="font-size:2rem;font-weight:800;color:#1d4ed8;margin:0.4rem 0;">{{ $this->summary['today_minutes'] ?? 0 }}</div>
            <div style="font-size:0.85rem;color:#6b7280;">মিনিট কথা হয়েছে</div>
        </div>

        <div style="padding:1.2rem;border-right:1px solid #e5e7eb;text-align:center;background:#f0f9ff;">
            <div style="font-size:0.75rem;color:#6b7280;font-weight:600;text-transform:uppercase;">📆 এই মাস</div>
            <div style="font-size:2rem;font-weight:800;color:#0369a1;margin:0.4rem 0;">{{ $this->summary['month_minutes'] ?? 0 }}</div>
            <div style="font-size:0.85rem;color:#6b7280;">মিনিট কথা হয়েছে</div>
        </div>

        <div style="padding:1.2rem;text-align:center;">
            <div style="font-size:0.75rem;color:#6b7280;font-weight:600;text-transform:uppercase;">🗂️ সর্বকাল</div>
            <div style="font-size:2rem;font-weight:800;color:#7c3aed;margin:0.4rem 0;">{{ $this->summary['lifetime_minutes'] ?? 0 }}</div>
            <div style="font-size:0.85rem;color:#6b7280;">মোট মিনিট ({{ $this->summary['completed_calls'] ?? 0 }} কলে)</div>
        </div>
    </div>

    <!-- Monthly Table -->
    @if(count($this->monthly) > 0)
    <div style="padding:1rem 1.5rem;">
        <h4 style="font-size:0.9rem;font-weight:700;color:#374151;margin:0 0 0.8rem 0;">📈 মাসিক রিপোর্ট (গত ৬ মাস)</h4>
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:0.85rem;">
                <thead>
                    <tr style="background:#f9fafb;">
                        <th style="padding:0.6rem 1rem;text-align:left;color:#6b7280;font-weight:600;border-bottom:2px solid #e5e7eb;">মাস</th>
                        <th style="padding:0.6rem 1rem;text-align:center;color:#6b7280;font-weight:600;border-bottom:2px solid #e5e7eb;">মোট কল</th>
                        <th style="padding:0.6rem 1rem;text-align:center;color:#16a34a;font-weight:600;border-bottom:2px solid #e5e7eb;">✅ সম্পন্ন</th>
                        <th style="padding:0.6rem 1rem;text-align:center;color:#f59e0b;font-weight:600;border-bottom:2px solid #e5e7eb;">⚠️ ড্রপ</th>
                        <th style="padding:0.6rem 1rem;text-align:center;color:#ef4444;font-weight:600;border-bottom:2px solid #e5e7eb;">❌ মিসড</th>
                        <th style="padding:0.6rem 1rem;text-align:center;color:#2563eb;font-weight:600;border-bottom:2px solid #e5e7eb;">⏱️ মোট মিনিট</th>
                        <th style="padding:0.6rem 1rem;text-align:center;color:#7c3aed;font-weight:600;border-bottom:2px solid #e5e7eb;">📊 গড় মিনিট/কল</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($this->monthly as $i => $row)
                    <tr style="border-bottom:1px solid #f3f4f6;{{ $i === 0 ? 'background:#fffbeb;' : '' }}">
                        <td style="padding:0.7rem 1rem;font-weight:{{ $i === 0 ? '700' : '400' }};color:#111827;">
                            {{ $row['month'] }}
                            @if($i === 0)<span style="background:#fef3c7;color:#92400e;font-size:0.65rem;padding:0.1rem 0.4rem;border-radius:10px;margin-left:6px;">চলতি</span>@endif
                        </td>
                        <td style="padding:0.7rem 1rem;text-align:center;color:#374151;font-weight:600;">{{ $row['total'] }}</td>
                        <td style="padding:0.7rem 1rem;text-align:center;color:#16a34a;font-weight:600;">{{ $row['completed'] }}</td>
                        <td style="padding:0.7rem 1rem;text-align:center;color:#f59e0b;font-weight:600;">{{ $row['dropped'] }}</td>
                        <td style="padding:0.7rem 1rem;text-align:center;color:#ef4444;font-weight:600;">{{ $row['missed'] }}</td>
                        <td style="padding:0.7rem 1rem;text-align:center;color:#2563eb;font-weight:700;font-size:1rem;">{{ $row['minutes'] }} মি.</td>
                        <td style="padding:0.7rem 1rem;text-align:center;color:#7c3aed;font-weight:600;">{{ $row['avg_min'] }} মি.</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @else
    <div style="padding:2rem;text-align:center;color:#9ca3af;">
        <div style="font-size:2rem;">📞</div>
        <div>এখনো কোনো কল লগ নেই</div>
    </div>
    @endif

    <div style="background:#f0f9ff;border-top:1px solid #bae6fd;padding:0.8rem 1.5rem;">
        <span style="color:#0369a1;font-size:0.8rem;">💡 গড় কল সময়: <strong>{{ $this->summary['avg_minutes'] ?? 0 }} মিনিট</strong> | Drop ও Missed কলের মিনিট গণনায় আসে না</span>
    </div>
</div>

