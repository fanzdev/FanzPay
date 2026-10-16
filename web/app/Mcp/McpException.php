<?php
declare(strict_types=1);

namespace App\Mcp;

/** Error JSON-RPC bertipe untuk MCP. */
final class McpException extends \RuntimeException {
    public function __construct(int $code, string $message) {
        parent::__construct($message, $code);
    }

    public function jsonCode(): int {
        return (int)$this->getCode();
    }
}
