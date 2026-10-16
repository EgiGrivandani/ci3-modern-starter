<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Canonical representation keeps equivalent IPv6 addresses in the same bucket.
 */
function normalize_client_ip($value)
{
    if (!is_string($value) || !filter_var($value, FILTER_VALIDATE_IP))
        return NULL;
    $packed = inet_pton($value);
    if (strlen($packed) === 16 && substr($packed, 0, 12) === str_repeat("\0", 10) . "\xff\xff") {
        $packed = substr($packed, 12);
    }
    return inet_ntop($packed);
}

function client_ip_in_cidr($ip, $cidr)
{
    if (!is_string($cidr))
        return FALSE;
    $parts = explode('/', $cidr);
    if (count($parts) !== 2 || !ctype_digit($parts[1]))
        return FALSE;
    $ip = normalize_client_ip($ip);
    $network = normalize_client_ip($parts[0]);
    if ($ip === NULL || $network === NULL)
        return FALSE;
    $address = inet_pton($ip);
    $network = inet_pton($network);
    $bits = (int) $parts[1];
    if (strlen($address) !== strlen($network) || $bits > strlen($address) * 8)
        return FALSE;
    $bytes = intdiv($bits, 8);
    $remainder = $bits % 8;
    if (substr($address, 0, $bytes) !== substr($network, 0, $bytes))
        return FALSE;
    if ($remainder === 0)
        return TRUE;
    $mask = (0xff << (8 - $remainder)) & 0xff;
    return (ord($address[$bytes]) & $mask) === (ord($network[$bytes]) & $mask);
}

/**
 * Only a verified immediate Cloudflare peer may supply the visitor header.
 * X-Forwarded-For and X-Real-IP are deliberately not used here.
 */
function resolve_client_ip(array $server, $trust_cloudflare, array $ranges)
{
    $peer = normalize_client_ip($server['REMOTE_ADDR'] ?? NULL);
    if ($peer === NULL)
        return '0.0.0.0';
    if ($trust_cloudflare !== TRUE)
        return $peer;
    foreach ($ranges as $range) {
        if (client_ip_in_cidr($peer, $range)) {
            return normalize_client_ip($server['HTTP_CF_CONNECTING_IP'] ?? NULL) ?? $peer;
        }
    }
    return $peer;
}

/** Visitor IP for rate limits and application audit records. */
function client_ip()
{
    $ci =& get_instance();
    $ci->config->load('client_ip', TRUE);
    return resolve_client_ip(
        $_SERVER,
        $ci->config->item('trust_cloudflare', 'client_ip'),
        $ci->config->item('cloudflare_ranges', 'client_ip')
    );
}
