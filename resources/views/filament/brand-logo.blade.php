@php
    $logoUrl = null;
    try {
        if (\Illuminate\Support\Facades\Schema::hasTable('company_profiles')) {
            $profile = \App\Models\CompanyProfile::where('is_active', true)->first();
            if ($profile && $profile->company_logo) {
                $logoUrl = url('storage/' . $profile->company_logo);
            }
        }
    } catch (\Exception $e) {}
@endphp

@if($logoUrl)
    <img src="{{ $logoUrl }}" alt="Logo" style="height:2rem; object-fit:contain; display:block;" />
@else
    <span style="font-weight:700; font-size:1.1rem;">
        {{ \App\Models\CompanyProfile::where('is_active', true)->value('company_name') ?? config('app.name') }}
    </span>
@endif
