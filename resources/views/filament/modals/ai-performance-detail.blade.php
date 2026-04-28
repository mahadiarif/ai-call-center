<div class="space-y-4 p-2">

    {{-- Score Card --}}
    <div class="flex items-center gap-4 p-4 rounded-lg
        {{ $record->score >= 80 ? 'bg-green-50 border border-green-300' : ($record->score >= 50 ? 'bg-yellow-50 border border-yellow-300' : 'bg-red-50 border border-red-300') }}">
        <div class="text-4xl font-bold
            {{ $record->score >= 80 ? 'text-green-600' : ($record->score >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
            {{ $record->score }}%
        </div>
        <div>
            <div class="font-semibold text-gray-700">AI Score</div>
            <div class="text-sm text-gray-500">
                {{ $record->filled_fields }}/{{ $record->total_fields }} ফিল্ড সংগ্রহ করা হয়েছে
            </div>
            <div class="text-sm font-medium mt-1">
                @if($record->score >= 80) ✅ চমৎকার — AI ভালো কাজ করেছে
                @elseif($record->score >= 50) ⚠️ মোটামুটি — কিছু উন্নতি দরকার
                @else ❌ দুর্বল — AI কে আরও train করতে হবে
                @endif
            </div>
        </div>
    </div>

    {{-- Customer Info --}}
    <div class="grid grid-cols-2 gap-3">
        <div class="bg-gray-50 rounded p-3">
            <div class="text-xs text-gray-500">কাস্টমার</div>
            <div class="font-medium">{{ $record->serviceRequest?->customer_name ?? 'অজানা' }}</div>
        </div>
        <div class="bg-gray-50 rounded p-3">
            <div class="text-xs text-gray-500">মোবাইল</div>
            <div class="font-medium">{{ $record->serviceRequest?->mobile_number ?? '—' }}</div>
        </div>
        <div class="bg-gray-50 rounded p-3">
            <div class="text-xs text-gray-500">সার্ভিস</div>
            <div class="font-medium">{{ $record->ivrService?->service_name ?? '—' }}</div>
        </div>
        <div class="bg-gray-50 rounded p-3">
            <div class="text-xs text-gray-500">সময়</div>
            <div class="font-medium">{{ $record->created_at?->diffForHumans() }}</div>
        </div>
    </div>

    {{-- Collected Fields --}}
    @if(!empty($record->collected_fields))
    <div>
        <div class="text-sm font-semibold text-green-700 mb-2">✅ AI যা সংগ্রহ করেছে:</div>
        <div class="flex flex-wrap gap-2">
            @foreach($record->collected_fields as $field)
                <span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full">{{ $field }}</span>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Missing Fields --}}
    @if(!empty($record->missing_fields))
    <div>
        <div class="text-sm font-semibold text-red-700 mb-2">❌ AI যা মিস করেছে (Train করুন):</div>
        <div class="flex flex-wrap gap-2 mb-3">
            @foreach($record->missing_fields as $field)
                <span class="px-2 py-1 bg-red-100 text-red-700 text-xs rounded-full">{{ $field }}</span>
            @endforeach
        </div>
        <div class="bg-yellow-50 border border-yellow-200 rounded p-3 text-sm text-yellow-800">
            💡 <strong>AI Train করতে:</strong> Knowledge Base এ যান এবং এই missing fields এর জন্য
            sample question ও answer যোগ করুন। তাহলে AI পরের বার এই তথ্য সংগ্রহ করতে পারবে।
            <div class="mt-2">
                <a href="/admin/knowledge-bases/create"
                   target="_blank"
                   class="inline-block px-3 py-1 bg-yellow-500 text-white text-xs rounded hover:bg-yellow-600">
                    🧠 Knowledge Base এ যান →
                </a>
            </div>
        </div>
    </div>
    @else
    <div class="bg-green-50 border border-green-200 rounded p-3 text-sm text-green-700">
        ✅ এই কলে AI সব তথ্য সংগ্রহ করতে পেরেছে! কোনো training দরকার নেই।
    </div>
    @endif

</div>
