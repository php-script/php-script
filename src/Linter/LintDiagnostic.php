<?php

declare(strict_types=1);

namespace PhpScript\Linter;

/**
 * A single problem the linter found, with its position in the script.
 */
final readonly class LintDiagnostic
{
    public const string SEVERITY_ERROR = 'error';

    public const string SEVERITY_WARNING = 'warning';

    public function __construct(
        public string $rule,
        public string $message,
        public int $line,
        public int $column,
        public int $offset,
        public int $length,
        public string $severity = self::SEVERITY_ERROR,
    ) {}

    /**
     * @return array{rule: string, message: string, line: int, column: int, offset: int, length: int, severity: string}
     */
    public function toArray(): array
    {
        return [
            'rule' => $this->rule,
            'message' => $this->message,
            'line' => $this->line,
            'column' => $this->column,
            'offset' => $this->offset,
            'length' => $this->length,
            'severity' => $this->severity,
        ];
    }
}
