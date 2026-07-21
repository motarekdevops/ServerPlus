<?php

namespace App\Services;

use App\Models\Server;
use phpseclib3\Net\SSH2;
use phpseclib3\Crypt\PublicKeyLoader;

class SshService
{
    /**
     * @throws \Exception
     */
    public function connect(Server $server): SSH2
    {
        $ssh = new SSH2($server->host, $server->port);
        $ssh->setTimeout(10);

        try {
            $key = PublicKeyLoader::load($server->private_key);
        } catch (\Throwable $e) {
            throw new \Exception("Invalid private key format: {$e->getMessage()}");
        }

        if (! $ssh->login($server->username, $key)) {
            $lastError = $ssh->getLastError();
            throw new \Exception(
                $lastError ? "Authentication failed: {$lastError}" : "Authentication failed: invalid credentials or unreachable host"
            );
        }

        return $ssh;
    }

    public function getCpuUsage(SSH2 $ssh): float
    {
        $output = $ssh->exec("top -bn1 | grep 'Cpu(s)' | awk '{print $2+$4}'");
        return (float) trim($output);
    }

    public function getRamUsage(SSH2 $ssh): float
    {
        $output = $ssh->exec("free | grep Mem | awk '{print (\$3/\$2) * 100.0}'");
        return (float) trim($output);
    }

    public function getDiskUsage(SSH2 $ssh): float
    {
        $output = $ssh->exec("df -h / | tail -1 | awk '{print \$5}' | tr -d '%'");
        return (float) trim($output);
    }

    public function getUptime(SSH2 $ssh): float
    {
        $output = $ssh->exec("cat /proc/uptime | awk '{print \$1}'");
        return (float) trim($output);
    }

    public function getAvailableUpdates(SSH2 $ssh): int
    {
        $output = $ssh->exec("apt list --upgradable 2>/dev/null | grep -c upgradable");
        return (int) trim($output);
    }

    /**
     * Returns full SSL certificate details: days remaining, issued date, expiry date.
     */
    public function getSslCertificateDetails(SSH2 $ssh, string $domain): array
    {
        $command = "echo | openssl s_client -servername {$domain} -connect {$domain}:443 2>/dev/null | openssl x509 -noout -dates 2>/dev/null";

        $output = trim($ssh->exec($command));

        if (empty($output)) {
            throw new \Exception("Could not retrieve SSL certificate for {$domain}");
        }

        // Output looks like:
        // notBefore=Jan 1 00:00:00 2026 GMT
        // notAfter=Apr 1 00:00:00 2026 GMT
        preg_match('/notBefore=(.+)/', $output, $issuedMatch);
        preg_match('/notAfter=(.+)/', $output, $expiryMatch);

        if (empty($issuedMatch[1]) || empty($expiryMatch[1])) {
            throw new \Exception("Could not parse SSL certificate dates for {$domain}");
        }

        $issuedAt = strtotime(trim($issuedMatch[1]));
        $expiresAt = strtotime(trim($expiryMatch[1]));

        if ($issuedAt === false || $expiresAt === false) {
            throw new \Exception("Invalid SSL certificate date format for {$domain}");
        }

        $daysRemaining = (int) floor(($expiresAt - time()) / 86400);

        return [
            'days_remaining' => $daysRemaining,
            'issued_at' => date('Y-m-d H:i:s', $issuedAt),
            'expires_at' => date('Y-m-d H:i:s', $expiresAt),
        ];
    }

    /**
     * Detects whether a domain is served by Nginx or Apache by checking
     * common config directories on the server. Returns null if undetermined.
     */
    public function detectWebServer(SSH2 $ssh, string $domain): ?string
    {
        $nginxCheck = trim($ssh->exec(
            "grep -rl '{$domain}' /etc/nginx/sites-enabled/ /etc/nginx/conf.d/ 2>/dev/null | head -1"
        ));

        if (! empty($nginxCheck)) {
            return 'nginx';
        }

        $apacheCheck = trim($ssh->exec(
            "grep -rl '{$domain}' /etc/apache2/sites-enabled/ 2>/dev/null | head -1"
        ));

        if (! empty($apacheCheck)) {
            return 'apache';
        }

        return null;
    }

    /**
     * Renews the SSL certificate via certbot and reloads the detected web server.
     */
    public function renewCertificate(SSH2 $ssh, string $domain, ?string $webServer): string
    {
        $output = $ssh->exec("sudo certbot renew --cert-name {$domain} --non-interactive 2>&1");

        if ($webServer === 'nginx') {
            $ssh->exec('sudo systemctl reload nginx 2>&1');
        } elseif ($webServer === 'apache') {
            $ssh->exec('sudo systemctl reload apache2 2>&1');
        }

        return $output;
    }
}
