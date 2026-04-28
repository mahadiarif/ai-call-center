<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerMemory extends Model
{
    protected $guarded = [];

    protected $casts = [
        'known_data'   => 'array',
        'last_call_at' => 'datetime',
    ];

    // ফোন নম্বর দিয়ে কাস্টমার খোঁজো বা নতুন তৈরি করো
    public static function findOrCreateByPhone(string $phone): self
    {
        return self::firstOrCreate(
            ['phone_number' => $phone],
            ['total_calls' => 0, 'known_data' => []]
        );
    }

    // কল শেষে memory আপডেট করো
    public function updateAfterCall(array $extractedData, string $ivrServiceName = null): void
    {
        $known = $this->known_data ?? [];

        // নতুন data merge করো — পুরনো data overwrite করবে না
        foreach ($extractedData as $key => $value) {
            if (!empty($value) && $value !== 'N/A') {
                $known[$key] = $value;
            }
        }

        // নাম আলাদা করে রাখো — সহজে দেখার জন্য
        $name = $extractedData['customer_name'] ?? $this->name;

        $this->update([
            'name'             => $name,
            'total_calls'      => $this->total_calls + 1,
            'last_call_at'     => now(),
            'last_ivr_service' => $ivrServiceName,
            'known_data'       => $known,
        ]);
    }

    // AI prompt এর জন্য memory text তৈরি করো
    public function toPromptText(): string
    {
        if ($this->total_calls === 0) {
            return ""; // নতুন কাস্টমার
        }

        $text = "\n[কাস্টমার পরিচিতি — আগের কল থেকে শেখা]\n";
        $text .= "এই নম্বরটি আগে {$this->total_calls} বার কল করেছে।\n";

        if ($this->name) {
            $text .= "কাস্টমারের নাম: {$this->name}\n";
        }
        if ($this->last_ivr_service) {
            $text .= "শেষবার সার্ভিস নিয়েছিল: {$this->last_ivr_service}\n";
        }
        if ($this->last_call_at) {
            $text .= "শেষ কলের সময়: " . $this->last_call_at->diffForHumans() . "\n";
        }
        if (!empty($this->known_data)) {
            $text .= "আগে দেওয়া তথ্য (প্রয়োজনে পুনরায় জিজ্ঞেস করো না):\n";
            foreach ($this->known_data as $key => $value) {
                $text .= "  - {$key}: {$value}\n";
            }
        }
        $text .= "নির্দেশনা: কাস্টমারকে নাম ধরে সম্বোধন করো এবং আগে দেওয়া তথ্য আর জিজ্ঞেস করো না।\n";

        return $text;
    }
}
