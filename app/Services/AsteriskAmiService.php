<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class AsteriskAmiService
{
    private string $host;
    private int    $port;
    private string $username;
    private string $password;
    private        $socket = null;

    public function __construct()
    {
        $this->host     = env('ASTERISK_AMI_HOST', '127.0.0.1');
        $this->port     = (int) env('ASTERISK_AMI_PORT', 5038);
        $this->username = env('ASTERISK_AMI_USERNAME', 'admin');
        $this->password = env('ASTERISK_AMI_PASSWORD', 'admin123');
    }

    // AMI তে connect করো
    private function connect(): bool
    {
        $this->socket = @fsockopen($this->host, $this->port, $errno, $errstr, 5);
        if (!$this->socket) {
            Log::error("Asterisk AMI connect failed: {$errstr} ({$errno})");
            return false;
        }
        // Welcome message পড়ো
        fgets($this->socket, 1024);

        // Login
        $this->send("Action: Login\r\nUsername: {$this->username}\r\nSecret: {$this->password}\r\n\r\n");
        $response = $this->read();

        if (str_contains($response, 'Success')) {
            $this->drainEvents(); // FullyBooted ইত্যাদি events flush করো
            return true;
        }

        Log::error("Asterisk AMI login failed: {$response}");
        return false;
    }

    // Disconnect
    private function disconnect(): void
    {
        if ($this->socket) {
            $this->send("Action: Logoff\r\n\r\n");
            fclose($this->socket);
            $this->socket = null;
        }
    }

    // Command পাঠাও
    private function send(string $data): void
    {
        if ($this->socket) {
            fwrite($this->socket, $data);
        }
    }

    // Response পড়ো — Event block (FullyBooted etc.) skip করে শুধু Response: block return করো
    private function read(): string
    {
        if (!$this->socket) return '';

        stream_set_timeout($this->socket, 5);
        $buffer = '';
        while ($line = fgets($this->socket, 4096)) {
            $buffer .= $line;
            if (trim($line) === '') {
                // blank line = end of one block
                // Response: block হলে return করো, Event: block হলে skip করো
                if (str_contains($buffer, 'Response:')) {
                    return $buffer;
                }
                $buffer = ''; // Event block — discard, পরেরটা পড়ো
            }
            // timeout হলে যা আছে তা return করো
            $info = stream_get_meta_data($this->socket);
            if (!empty($info['timed_out'])) break;
        }
        return $buffer; // fallback
    }

    // Login এর পরে queued events (FullyBooted ইত্যাদি) drain করো
    private function drainEvents(): void
    {
        stream_set_timeout($this->socket, 1);
        while ($line = fgets($this->socket, 4096)) {
            // just drain — nothing to do
        }
        stream_set_timeout($this->socket, 5);
    }

    /**
     * Caller number দিয়ে active Asterisk channel name খোঁজো
     * e.g. "01617020303" → "PJSIP/trunk-provider-00000001"
     */
    public function findChannelByCallerNumber(string $callerNumber): ?string
    {
        try {
            if (!$this->connect()) return null;

            // CoreShowChannels — all active channels list
            $this->send("Action: CoreShowChannels\r\nActionID: lookup1\r\n\r\n");

            // Read until CoreShowChannelsComplete
            $allData = '';
            stream_set_timeout($this->socket, 5);
            $deadline = time() + 5;
            while (time() < $deadline) {
                $line = fgets($this->socket, 4096);
                if ($line === false) break;
                $allData .= $line;
                if (str_contains($line, 'CoreShowChannelsComplete')) break;
            }
            $this->disconnect();

            // Parse channel blocks — find one where CallerIDNum matches
            $clean = preg_replace('/\D/', '', $callerNumber);
            $blocks = explode("\r\n\r\n", $allData);
            foreach ($blocks as $block) {
                if (!str_contains($block, 'Event: CoreShowChannel')) continue;
                if (preg_match('/CallerIDNum:\s*(\S+)/i', $block, $cidm)) {
                    $blockNum = preg_replace('/\D/', '', $cidm[1]);
                    if ($blockNum === $clean || str_ends_with($blockNum, $clean) || str_ends_with($clean, $blockNum)) {
                        if (preg_match('/Channel:\s*(\S+)/i', $block, $chanm)) {
                            return trim($chanm[1]);
                        }
                    }
                }
            }
            return null;
        } catch (\Throwable $e) {
            Log::error("[findChannel] " . $e->getMessage());
            return null;
        }
    }

    /**
     * রাগী কাস্টমারের কল Human Agent এ transfer করো
     *
     * @param string $customerChannel  কাস্টমারের active channel (যেমন: SIP/1001-00000001)
     * @param string $agentNumber      Agent এর extension বা নম্বর (যেমন: 1002 বা 01712345678)
     * @param string $context          Dialplan context
     * @return array ['success' => bool, 'message' => string]
     */
    public function transferToAgent(string $customerChannel, string $agentNumber, string $context = null): array
    {
        $context = $context ?? env('ASTERISK_CONTEXT', 'from-internal');

        try {
            if (!$this->connect()) {
                return ['success' => false, 'message' => 'Asterisk AMI connect করা যায়নি'];
            }

            // Redirect action — কাস্টমারের কল agent এ পাঠাও
            $action = "Action: Redirect\r\n"
                    . "Channel: {$customerChannel}\r\n"
                    . "Exten: {$agentNumber}\r\n"
                    . "Context: {$context}\r\n"
                    . "Priority: 1\r\n"
                    . "\r\n";

            $this->send($action);
            $response = $this->read();

            $this->disconnect();

            if (str_contains($response, 'Success')) {
                Log::info("Asterisk transfer success: {$customerChannel} → {$agentNumber}");
                return ['success' => true, 'message' => "কাস্টমার agent {$agentNumber} এ transfer হয়েছে"];
            }

            Log::warning("Asterisk transfer failed: {$response}");
            return ['success' => false, 'message' => 'Transfer হয়নি: ' . trim($response)];

        } catch (\Throwable $e) {
            Log::error("Asterisk AMI error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * IVR escalation — AMI দিয়ে agent কে call করো (customer channel জানা না থাকলেও কাজ করে)
     * Strategy: Originate → agent extension → agentটি যখন pick up করবে তখন customer এর sr/number দেখবে
     */
    public function originateToAgent(string $agentNumber, string $customerChannel = null): array
    {
        $context  = env('ASTERISK_CONTEXT', 'from-internal');
        $callerId = env('ASTERISK_CALLER_ID', 'Walton Helpline <16267>');

        try {
            if (!$this->connect()) {
                return ['success' => false, 'message' => 'Asterisk AMI connect হয়নি'];
            }

            if ($customerChannel) {
                // Channel আছে → Redirect (bridge করো)
                $action = "Action: Redirect\r\n"
                        . "Channel: {$customerChannel}\r\n"
                        . "Context: escalation-forward\r\n"
                        . "Exten: {$agentNumber}\r\n"
                        . "Priority: 1\r\n"
                        . "\r\n";
            } else {
                // Channel নেই → agent কে Originate করো
                // Mobile number (01x / 09x) হলে PJSIP via trunk, extension হলে direct PJSIP
                $outboundTrunk = env('ASTERISK_OUTBOUND_TRUNK', 'PJSIP/trunk-provider');
                if (preg_match('/^0[19]\d{9}$/', $agentNumber) && stripos($outboundTrunk, 'PJSIP/') === 0) {
                    $endpointName  = substr($outboundTrunk, 6); // strip "PJSIP/"
                    $agentChannel  = "PJSIP/{$agentNumber}@{$endpointName}";
                } else {
                    $agentChannel  = "PJSIP/{$agentNumber}"; // local extension
                }
                $action = "Action: Originate\r\n"
                        . "Channel: {$agentChannel}\r\n"
                        . "Context: {$context}\r\n"
                        . "Exten: {$agentNumber}\r\n"
                        . "Priority: 1\r\n"
                        . "CallerID: {$callerId}\r\n"
                        . "Timeout: 30000\r\n"
                        . "Async: true\r\n"
                        . "\r\n";
            }

            $this->send($action);
            $response = $this->read();
            $this->disconnect();

            if (str_contains($response, 'Success')) {
                return ['success' => true, 'message' => "Agent {$agentNumber} কে call দেওয়া হচ্ছে"];
            }

            Log::warning("[originateToAgent] failed: {$response}");
            return ['success' => false, 'message' => trim($response)];

        } catch (\Throwable $e) {
            Log::error("[originateToAgent] error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Outbound Survey Call — কাস্টমারকে সরাসরি call করো
     * Campaign এর pending survey record থেকে একটি নম্বরে call dial করবে।
     *
     * @param string $mobileNumber  কাস্টমারের বাংলাদেশ মোবাইল নম্বর (01XXXXXXXXX)
     * @param int    $surveyId      FeedbackSurvey.id — AI prompt এ customer info পাবে
     * @param string $trunkName     SIP Trunk নাম (যেমন: "SIP/trunk-gp" বা "DAHDI/g1")
     */
    public function originateSurveyCall(string $mobileNumber, int $surveyId, string $trunkName = null): array
    {
        $trunk    = $trunkName ?? env('ASTERISK_OUTBOUND_TRUNK', 'PJSIP/trunk-provider');
        $context  = env('ASTERISK_OUTBOUND_CONTEXT', 'outbound-survey');
        $callerId = env('ASTERISK_CALLER_ID', 'Walton Helpline <16267>');
        $appUrl   = rtrim(env('APP_URL', 'http://localhost'), '/');

        // Channel format:
        //   PJSIP → "PJSIP/NUMBER@endpoint"  (e.g. PJSIP/01617020303@trunk-provider)
        //   SIP   → "SIP/trunk/NUMBER"        (e.g. SIP/trunk-gp/01617020303)
        if (stripos($trunk, 'PJSIP/') === 0) {
            // "PJSIP/trunk-provider" → "PJSIP/01617020303@trunk-provider"
            $endpointName = substr($trunk, 6); // strip "PJSIP/"
            $channel = "PJSIP/{$mobileNumber}@{$endpointName}";
        } else {
            // Legacy SIP/DAHDI: keep slash format
            $channel = "{$trunk}/{$mobileNumber}";
        }
        try {
            if (!$this->connect()) {
                return ['success' => false, 'message' => 'Asterisk AMI connect হয়নি'];
            }

            // Originate: Asterisk → কাস্টমারকে dial করবে → connect হলে outbound-survey context এ যাবে
            $action = "Action: Originate\r\n"
                    . "Channel: {$channel}\r\n"
                    . "Context: {$context}\r\n"
                    . "Exten: s\r\n"
                    . "Priority: 1\r\n"
                    . "CallerID: {$callerId}\r\n"
                    . "Timeout: 30000\r\n"
                    . "Variable: SURVEY_ID={$surveyId}\r\n"
                    . "Variable: CUSTOMER_NUMBER={$mobileNumber}\r\n"
                    . "Variable: APP_URL={$appUrl}\r\n"
                    . "Async: true\r\n"
                    . "\r\n";

            $this->send($action);
            $response = $this->read();
            $this->disconnect();

            if (str_contains($response, 'Success')) {
                Log::info("[OutboundSurvey] Call initiated → {$mobileNumber} (survey #{$surveyId})");
                return ['success' => true, 'message' => "{$mobileNumber} নম্বরে call দেওয়া হচ্ছে..."];
            }

            Log::warning("[OutboundSurvey] Originate failed → {$mobileNumber}: {$response}");
            return ['success' => false, 'message' => 'Call দেওয়া যায়নি: ' . trim($response)];

        } catch (\Throwable $e) {
            Log::error("[OutboundSurvey] AMI error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
