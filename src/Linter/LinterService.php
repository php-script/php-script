<?php

declare(strict_types=1);

namespace PhpScript\Linter;

use InvalidArgumentException;
use PhpScript\Ast\BreakStatement;
use PhpScript\Ast\ForeachStatement;
use PhpScript\Ast\ForStatement;
use PhpScript\Ast\IfStatement;
use PhpScript\Ast\Literal;
use PhpScript\Ast\Program;
use PhpScript\Ast\ReturnStatement;
use PhpScript\Contracts\AstTraverserInterface;
use PhpScript\Contracts\LexerInterface;
use PhpScript\Contracts\Node;
use PhpScript\Contracts\ParserInterface;
use PhpScript\Core\AstTraverser;
use PhpScript\Core\Lexer;
use PhpScript\Core\Parser;
use PhpScript\Core\PhpScriptRenderer;
use PhpScript\Core\Token;
use PhpScript\Exceptions\EngineException;
use PhpScript\Exceptions\LexerException;
use PhpScript\Exceptions\ParseException;

class LinterService
{
    public const string RULE_SYNTAX = 'syntax';

    public const string RULE_COMPILE = 'compile';

    public const string RULE_INFINITE_LOOP = 'infinite-loop';

    public const array RULES = [self::RULE_SYNTAX, self::RULE_COMPILE, self::RULE_INFINITE_LOOP];

    private string $script = '';

    private bool $expression = false;

    /** @var string[]|null */
    private ?array $allowedFunctions = null;

    /** @var array<string, true> */
    private array $disabledRules = [];

    /** @var array{string, list<LintDiagnostic>}|null */
    private ?array $result = null;

    public function __construct(
        private readonly LexerInterface $lexer = new Lexer,
        private readonly ParserInterface $parser = new Parser,
        private readonly AstTraverserInterface $renderer = new PhpScriptRenderer,
        private readonly AstTraverserInterface $compiler = new AstTraverser,
    ) {}

    public function withScript(string $script): self
    {
        $this->script = $script;

        return $this->invalidate();
    }

    /**
     * Lints the script as a single expression (see Engine::evaluate()).
     */
    public function asExpression(bool $expression = true): self
    {
        $this->expression = $expression;

        return $this->invalidate();
    }

    /**
     * Enables the compile check: function calls must be in this list.
     *
     * @param  string[]  $allowedFunctions
     */
    public function withAllowedFunctions(array $allowedFunctions): self
    {
        $this->allowedFunctions = $allowedFunctions;

        return $this->invalidate();
    }

    /**
     * @throws InvalidArgumentException for an unknown rule
     */
    public function withoutRule(string $rule): self
    {
        $this->assertRule($rule);
        $this->disabledRules[$rule] = true;

        return $this->invalidate();
    }

    /**
     * @throws InvalidArgumentException for an unknown rule
     */
    public function withRule(string $rule): self
    {
        $this->assertRule($rule);
        unset($this->disabledRules[$rule]);

        return $this->invalidate();
    }

    private function assertRule(string $rule): void
    {
        if (! in_array($rule, self::RULES, true)) {
            throw new InvalidArgumentException(sprintf("Unknown lint rule '%s'. Known rules: %s.", $rule, implode(', ', self::RULES)));
        }
    }

    /**
     * The formatted script, or the original script if it cannot be parsed.
     */
    public function linted(): string
    {
        return $this->lint()[0];
    }

    /**
     * @return list<LintDiagnostic>
     */
    public function diagnostics(): array
    {
        return $this->lint()[1];
    }

    /**
     * True when there are no errors. Warnings (e.g. a possibly infinite loop) do not
     * make a script invalid.
     */
    public function isValid(): bool
    {
        return array_all($this->diagnostics(), static fn (LintDiagnostic $d): bool => $d->severity !== LintDiagnostic::SEVERITY_ERROR);
    }

    private function invalidate(): self
    {
        $this->result = null;

        return $this;
    }

    /**
     * @return array{string, list<LintDiagnostic>}
     */
    private function lint(): array
    {
        return $this->result ??= $this->process();
    }

    /**
     * @return array{string, list<LintDiagnostic>}
     */
    private function process(): array
    {
        try {
            $tokens = $this->lexer->tokenize($this->script);
            $node = $this->expression
                ? $this->parser->parseExpression($tokens)
                : $this->parser->parse($tokens);
        } catch (LexerException $e) {
            return [rtrim($this->script), [new LintDiagnostic(self::RULE_SYNTAX, $e->getMessage(), $e->line, $e->column, $e->offset, 1)]];
        } catch (ParseException $e) {
            return [rtrim($this->script), [$this->tokenDiagnostic(self::RULE_SYNTAX, $e->getMessage(), $e->getToken())]];
        }

        $diagnostics = [];

        if ($this->allowedFunctions !== null && $this->enabled(self::RULE_COMPILE)) {
            try {
                $this->compiler->setAllowedFunctions($this->allowedFunctions);
                $this->compiler->traverse($this->expression ? new Program([new ReturnStatement($node)]) : $node);
            } catch (EngineException $e) {
                $diagnostics[] = new LintDiagnostic(self::RULE_COMPILE, $e->getMessage(), $e->line, $e->column, $e->offset, $e->length);
            }
        }

        if ($this->enabled(self::RULE_INFINITE_LOOP)) {
            array_push($diagnostics, ...$this->findInfiniteLoops($node));
        }

        return [rtrim($this->renderer->traverse($node)), $diagnostics];
    }

    private function enabled(string $rule): bool
    {
        return ! isset($this->disabledRules[$rule]);
    }

    private function tokenDiagnostic(string $rule, string $message, ?Token $token, string $severity = LintDiagnostic::SEVERITY_ERROR): LintDiagnostic
    {
        return new LintDiagnostic(
            $rule,
            $message,
            $token->line ?? 1,
            (int) $token?->column,
            (int) $token?->offset,
            $token instanceof Token ? max(1, strlen($token->value)) : 1,
            $severity,
        );
    }

    /**
     * Reports `for` loops that never stop on their own: no condition (or a constant
     * truthy one) and no reachable `break` or `return`. Only obvious cases are found;
     * the operation limit is the real safety net.
     *
     * @return list<LintDiagnostic>
     */
    private function findInfiniteLoops(Node $node): array
    {
        $diagnostics = [];

        if ($node instanceof ForStatement && $this->isEndless($node->condition) && ! $this->canExit($node->body, 1)) {
            $diagnostics[] = $this->tokenDiagnostic(
                self::RULE_INFINITE_LOOP,
                'Loop has no exit condition and no reachable break or return.',
                $node->getToken(),
                LintDiagnostic::SEVERITY_WARNING,
            );
        }

        foreach ($this->children($node) as $child) {
            array_push($diagnostics, ...$this->findInfiniteLoops($child));
        }

        return $diagnostics;
    }

    private function isEndless(?Node $condition): bool
    {
        return ! $condition instanceof Node || ($condition instanceof Literal && (bool) $condition->value);
    }

    /**
     * Whether the body contains a statement that leaves the loop `$depth` levels up.
     */
    private function canExit(Node $node, int $depth): bool
    {
        if ($node instanceof ReturnStatement) {
            return true;
        }

        if ($node instanceof BreakStatement) {
            return $node->level >= $depth;
        }

        $nested = $node instanceof ForStatement || $node instanceof ForeachStatement;

        return array_any($this->children($node), fn (Node $child): bool => $this->canExit($child, $nested ? $depth + 1 : $depth));
    }

    /**
     * Child statements of a node. Expressions never contain statements.
     *
     * @return list<Node>
     */
    private function children(Node $node): array
    {
        return match (true) {
            $node instanceof Program => array_values($node->statements),
            $node instanceof IfStatement => array_filter([$node->then, $node->else]),
            $node instanceof ForStatement, $node instanceof ForeachStatement => [$node->body],
            default => [],
        };
    }
}
