<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ServiceRequest;
use App\Models\CallLog;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // ১. AI Performance Logs (Latencies)
        $logs = [];
        for ($i = 0; $i < 50; $i++) {
            $logs[] = [
                'session_id' => \Illuminate\Support\Str::uuid(),
                'latency_ms' => rand(800, 2500),
                'provider'   => 'Gemini Live 2.5',
                'status'     => 'ready',
                'created_at' => Carbon::now()->subMinutes(rand(0, 1440)),
                'updated_at' => Carbon::now(),
            ];
        }
        DB::table('ai_performance_logs')->insert($logs);

        // ২. Service Requests & Call Logs
        $statuses = ['Pending', 'Resolved', 'Drop Call', 'Incoming'];
        $mobiles  = ['01711000000', '01822000000', '01933000000', '01544000000', '01655000000'];
        $names    = ['Karim Ahmed', 'Rahima Begum', 'Sumon Ali', 'Fatema Zohra', 'Tanvir Hasan'];

        for ($i = 0; $i < 20; $i++) {
            $status = $statuses[array_rand($statuses)];
            $createdAt = Carbon::now()->subMinutes(rand(10, 1440));
            
            $sr = ServiceRequest::create([
                'mobile_number' => $mobiles[array_rand($mobiles)],
                'customer_name' => $names[array_rand($names)],
                'status'        => $status,
                'gender'        => rand(0, 1) ? 'male' : 'female',
                'extracted_data' => [
                    'product' => ['AC', 'TV', 'Fridge', 'Washing Machine'][rand(0, 3)],
                    'problem' => 'Not working properly',
                    'district' => 'Dhaka'
                ],
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            CallLog::create([
                'caller_number' => $sr->mobile_number,
                'duration'      => rand(30, 300),
                'status'        => $status === 'Drop Call' ? 'dropped' : 'completed',
                'session_id'    => \Illuminate\Support\Str::uuid(),
                'created_at'    => $createdAt,
                'updated_at'    => $createdAt,
            ]);
        }
    }
}
