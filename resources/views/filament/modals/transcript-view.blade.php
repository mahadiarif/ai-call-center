<div style="padding: 1rem;">
    <div style="background: #f9fafb; border-radius: 8px; padding: 1rem; margin-bottom: 1rem;">
        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
            <strong style="color: #374151;">📊 AI Performance Score:</strong>
            <span style="color: {{ $score >= 80 ? '#16a34a' : ($score >= 50 ? '#f59e0b' : '#ef4444') }}; font-weight: bold;">
                {{ $score }}%
            </span>
        </div>
        <div style="font-size: 0.875rem; color: #6b7280; margin-top: 0.5rem;">
            💡 <strong>AI বলছে:</strong> {{ $suggestion }}
        </div>
    </div>

    <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1rem; max-height: 400px; overflow-y: auto;">
        <h3 style="margin: 0 0 1rem; color: #111827; font-size: 1rem;">📜 Full Conversation Transcript:</h3>
        
        @if($transcript)
            <pre style="white-space: pre-wrap; font-family: monospace; font-size: 0.875rem; line-height: 1.6; color: #374151;">{{ $transcript }}</pre>
        @else
            <p style="color: #9ca3af; text-align: center; padding: 2rem;">No transcript available</p>
        @endif
    </div>
</div>
