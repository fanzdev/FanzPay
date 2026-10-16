<?php
declare(strict_types=1);

namespace App\Mcp;

use App\Core\Logger;
use App\Core\RateLimiter;
use App\Services\Settings;

/**
 * Dispatcher protokol MCP (JSON-RPC 2.0 via Streamable HTTP).
 * Protokol versi: 2024-11-05. Server: fanzpay-mcp.
 */
final class McpServer {
    public const VERSION = '2024-11-05';
    public const SERVER = 'fanzpay-mcp';
    public const SERVER_VERSION = '1.0.0';

    /** @param array{user:array, source:string, scopes:array<int,string>}|null $auth */
    public static function dispatch(array $req, ?array $auth): array {
        $id = $req['id'] ?? null;
        $method = (string)($req['method'] ?? '');
        $params = is_array($req['params'] ?? null) ? $req['params'] : [];

        if (($req['jsonrpc'] ?? '') !== '2.0' || $method === '') {
            return self::err($id, -32600, 'Request harus JSON-RPC 2.0 dengan method.');
        }
        if (Settings::get('mcp_enabled', '1') !== '1') {
            return self::err($id, -32603, 'MCP Server sedang nonaktif (maintenance).');
        }

        try {
            $result = match ($method) {
                'initialize' => self::initialize($params),
                'notifications/initialized' => null,
                'ping' => ['pong' => true],
                'tools/list' => self::toolsList(),
                'tools/call' => self::toolsCall($params, $auth),
                default => throw new McpException(-32601, 'Method tidak dikenal: ' . $method),
            };
            if ($method === 'notifications/initialized') return ['jsonrpc' => '2.0', 'id' => $id, 'result' => new \stdClass()];
            return ['jsonrpc' => '2.0', 'id' => $id, 'result' => $result];
        } catch (McpException $e) {
            try { Logger::audit($auth ? (int)$auth['user']['id'] : null, null, 'mcp.error', 'mcp', $method, 'fail', ['code' => $e->getCode()]); } catch (\Throwable $t) {}
            return self::err($id, $e->getCode(), $e->getMessage());
        } catch (\Throwable $e) {
            Logger::log('mcp.exception', ['method' => $method]);
            return self::err($id, -32603, 'Transaksi sedang diproses.');
        }
    }

    private static function initialize(array $p): array {
        return [
            'protocolVersion' => self::VERSION,
            'capabilities' => ['tools' => ['listChanged' => false]],
            'serverInfo' => ['name' => self::SERVER, 'version' => self::SERVER_VERSION],
            'instructions' => 'FanzPay MCP: 18 tools PPOB. Auth via Bearer token (halaman Koneksi AI). PIN tidak pernah via AI.',
        ];
    }

    private static function toolsList(): array {
        $out = [];
        foreach (McpTools::definitions() as $name => $def) {
            if (Settings::get('mcp_tool_' . $name, '1') !== '1') continue;
            $out[] = ['name' => $name, 'description' => $def['description'],
                'inputSchema' => McpTools::inputSchema($name)];
        }
        return ['tools' => $out];
    }

    /** @param array{user:array, source:string, scopes:array<int,string>}|null $auth */
    private static function toolsCall(array $p, ?array $auth): array {
        $tool = (string)($p['name'] ?? '');
        $args = is_array($p['arguments'] ?? null) ? $p['arguments'] : [];
        $defs = McpTools::definitions();
        if (!isset($defs[$tool])) throw new McpException(-32601, 'Tool tidak dikenal.');
        if (Settings::get('mcp_tool_' . $tool, '1') !== '1') {
            throw new McpException(-32603, 'Tool dinonaktifkan administrator.');
        }
        if (!$auth) throw new McpException(-32603, 'Auth required: sertakan Bearer token MCP (buat di halaman Koneksi AI setelah login).');
        $need = $defs[$tool]['scope'];
        if ($need === 'purchase' && !in_array('purchase', $auth['scopes'], true)) {
            throw new McpException(-32603, 'Token hanya scope read. Buat token dengan izin pembelian di halaman Koneksi AI.');
        }
        $uid = (int)$auth['user']['id'];
        $rl = RateLimiter::hit('mcp:' . $uid, max(10, Settings::int('mcp_rate_per_min', 60)), 60);
        if (!$rl['allowed']) throw new McpException(-32603, 'Rate limit terlampaui. Coba lagi nanti.');
        if ($tool === 'execute_ppob_purchase') {
            $rl2 = RateLimiter::hit('mcp-exec:' . $uid, 10, 600);
            if (!$rl2['allowed']) throw new McpException(-32603, 'Terlalu banyak eksekusi. Tunggu beberapa menit.');
        }
        $args = McpTools::validate($tool, $args);
        $data = McpRunner::call($tool, $args, $auth['user']);
        try {
            Logger::audit($uid, 'user', 'mcp.tool.' . $tool, 'mcp', null, 'ok', ['source' => $auth['source']]);
        } catch (\Throwable $e) {}
        return ['content' => [['type' => 'text', 'text' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]], 'isError' => false];
    }

    private static function err(mixed $id, int $code, string $msg): array {
        return ['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => $code, 'message' => $msg]];
    }
}
