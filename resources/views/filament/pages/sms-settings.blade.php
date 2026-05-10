<x-filament-panels::page>
<div style="padding:0.5rem 0; max-width:900px;">

    {{-- ── Gateway Config ── --}}
    <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;margin-bottom:1.5rem;overflow:hidden;">
        <div style="background:linear-gradient(135deg,#1e3a5f,#2563eb);padding:1rem 1.5rem;">
            <h3 style="color:#fff;font-size:1rem;font-weight:700;margin:0;">⚙️ Gateway কনফিগারেশন</h3>
        </div>
        <div style="padding:1.5rem;">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:1.2rem;padding:0.8rem 1rem;background:#f0f9ff;border-radius:8px;border:1px solid #bae6fd;">
                <input type="checkbox" wire:model.live="is_active" id="is_active"
                    style="width:18px;height:18px;cursor:pointer;accent-color:#2563eb;">
                <label for="is_active" style="font-weight:600;font-size:0.95rem;cursor:pointer;margin:0;">
                    SMS Gateway চালু আছে?
                </label>
                <span style="font-size:0.8rem;color:#0369a1;">
                    @if($is_active) ✅ চালু @else ❌ বন্ধ — কোনো SMS যাবে না @endif
                </span>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div>
                    <label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:4px;color:#374151;">Gateway নাম</label>
                    <input type="text" wire:model="gateway_name" placeholder="Gennet"
                        style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:0.9rem;box-sizing:border-box;">
                </div>
                <div>
                    <label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:4px;color:#374151;">SMS Type</label>
                    <select wire:model="sms_type"
                        style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:0.9rem;background:#fff;">
                        <option value="non_masking">Non-Masking (Number দিয়ে যাবে)</option>
                        <option value="masking">Masking (Brand name দিয়ে যাবে)</option>
                    </select>
                </div>
                <div style="grid-column:1/3;">
                    <label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:4px;color:#374151;">API URL</label>
                    <input type="url" wire:model="gateway_url"
                        style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:0.9rem;box-sizing:border-box;">
                </div>
                <div>
                    <label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:4px;color:#374151;">API Token</label>
                    <input type="password" wire:model="api_token" placeholder="আপনার api_token দিন"
                        style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:0.9rem;box-sizing:border-box;">
                </div>
                <div>
                    <label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:4px;color:#374151;">SID (Sender ID)</label>
                    <input type="text" wire:model="sid" placeholder="8809612352224"
                        style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:0.9rem;box-sizing:border-box;">
                    <p style="font-size:0.75rem;color:#6b7280;margin:4px 0 0;">Masking → approved brand name &nbsp;|&nbsp; Non-masking → number</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Auto SMS ── --}}
    <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;margin-bottom:1.5rem;overflow:hidden;">
        <div style="background:linear-gradient(135deg,#065f46,#059669);padding:1rem 1.5rem;">
            <h3 style="color:#fff;font-size:1rem;font-weight:700;margin:0;">🤖 Auto SMS কনফিগারেশন</h3>
        </div>
        <div style="padding:1.5rem;">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.2rem;">
                <div style="padding:0.8rem 1rem;background:#f0fdf4;border-radius:8px;border:1px solid #bbf7d0;">
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                        <input type="checkbox" wire:model="auto_send_on_sr"
                            style="width:18px;height:18px;accent-color:#059669;">
                        <div>
                            <div style="font-weight:600;font-size:0.9rem;">SR তৈরিতে Auto SMS</div>
                            <div style="font-size:0.75rem;color:#6b7280;">Walton SR পাওয়ার পর কাস্টমারকে SMS</div>
                        </div>
                    </label>
                </div>
                <div style="padding:0.8rem 1rem;background:#fffbeb;border-radius:8px;border:1px solid #fde68a;">
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                        <input type="checkbox" wire:model="auto_send_on_qm"
                            style="width:18px;height:18px;accent-color:#d97706;">
                        <div>
                            <div style="font-weight:600;font-size:0.9rem;">QM Ticket তৈরিতে Auto SMS</div>
                            <div style="font-size:0.75rem;color:#6b7280;">QM submit হলে কাস্টমারকে SMS</div>
                        </div>
                    </label>
                </div>
            </div>

            <div style="margin-bottom:1rem;">
                <label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:4px;color:#374151;">
                    SR SMS Template
                    <span style="font-weight:400;color:#6b7280;font-size:0.78rem;margin-left:8px;">
                        Variables: {name} {sr_number} {product}
                    </span>
                </label>
                <textarea wire:model="sr_template" rows="3"
                    style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:0.9rem;box-sizing:border-box;resize:vertical;"></textarea>
            </div>

            <div>
                <label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:4px;color:#374151;">
                    QM SMS Template
                    <span style="font-weight:400;color:#6b7280;font-size:0.78rem;margin-left:8px;">
                        Variables: {name} {qm_number}
                    </span>
                </label>
                <textarea wire:model="qm_template" rows="3"
                    style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:0.9rem;box-sizing:border-box;resize:vertical;"></textarea>
            </div>
        </div>
    </div>

    {{-- ── Test SMS ── --}}
    <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;overflow:hidden;">
        <div style="background:linear-gradient(135deg,#4c1d95,#7c3aed);padding:1rem 1.5rem;">
            <h3 style="color:#fff;font-size:1rem;font-weight:700;margin:0;">🧪 Test SMS পাঠান</h3>
        </div>
        <div style="padding:1.5rem;display:flex;gap:1rem;align-items:flex-end;">
            <div style="flex:1;">
                <label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:4px;color:#374151;">Test Mobile Number</label>
                <input type="text" wire:model="test_mobile" placeholder="01XXXXXXXXX"
                    style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:0.9rem;box-sizing:border-box;">
            </div>
            <button wire:click="sendTestSms"
                style="padding:9px 20px;background:#7c3aed;color:#fff;border:none;border-radius:8px;font-weight:600;font-size:0.9rem;cursor:pointer;white-space:nowrap;">
                📨 Test পাঠান
            </button>
        </div>
    </div>

</div>
</x-filament-panels::page>
