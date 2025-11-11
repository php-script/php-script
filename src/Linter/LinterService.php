<?php

declare(strict_types=1);

namespace PhpScript\Linter;

use PhpScript\Core\Lexer;
use PhpScript\Core\Parser;
use PhpScript\Core\PhpScriptRenderer;
use Throwable;

class LinterService {

    private string $linted = '';

    public function __construct(
        private readonly Lexer $lexer = new Lexer,
        private readonly Parser $parser = new Parser,
        private readonly PhpScriptRenderer $renderer = new PhpScriptRenderer,
    ) {}

    public function withScript(string $script): self
    {
        try {
            $tokens = $this->lexer->tokenize($script);
            $ast = $this->parser->parse($tokens);

            $this->linted = rtrim($this->renderer->traverse($ast));
        } catch (Throwable) {
            $this->linted = rtrim($script);
        }

        return $this;
    }

    public function linted(): string
    {
        return $this->linted;
    }
}
