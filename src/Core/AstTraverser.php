<?php

declare(strict_types=1);

namespace PhpScript\Core;

use PhpScript\Ast\ArrayAccess;
use PhpScript\Ast\Assignment;
use PhpScript\Ast\BinaryOperation;
use PhpScript\Ast\BreakStatement;
use PhpScript\Ast\ContinueStatement;
use PhpScript\Ast\EchoStatement;
use PhpScript\Ast\ForeachStatement;
use PhpScript\Ast\ForStatement;
use PhpScript\Ast\FunctionCall;
use PhpScript\Ast\Identifier;
use PhpScript\Ast\IfStatement;
use PhpScript\Ast\ListLiteral;
use PhpScript\Ast\Literal;
use PhpScript\Ast\MapLiteral;
use PhpScript\Ast\MemberAccess;
use PhpScript\Ast\NoOp;
use PhpScript\Ast\PostfixOperation;
use PhpScript\Ast\Program;
use PhpScript\Ast\ReturnStatement;
use PhpScript\Ast\UnaryOperation;
use PhpScript\Ast\Variable;
use PhpScript\Contracts\AstTraverserInterface;
use PhpScript\Contracts\Node;
use PhpScript\Exceptions\AstTraverserException;
use PhpScript\Exceptions\EngineException;
use PhpScript\Runtime\Runtime;
use PhpScript\Runtime\Signature;
use ReflectionFunction;

class AstTraverser implements AstTraverserInterface
{
    /**
     * Variables that would give a script access to the PHP environment.
     */
    public const array RESERVED_VARIABLES = [
        'this', 'GLOBALS', '_SERVER', '_GET', '_POST', '_FILES', '_COOKIE',
        '_SESSION', '_REQUEST', '_ENV', 'http_response_header', 'argc', 'argv',
    ];

    /**
     * Prefix of the variables the engine uses internally in the generated code.
     */
    public const string INTERNAL_PREFIX = '__ps_';

    private const string RUNTIME = '\\' . Runtime::class;

    private const string BUDGET = '$' . self::INTERNAL_PREFIX . 'budget';

    private const string GUARD = '$' . self::INTERNAL_PREFIX . 'guard';

    private string $generatedCode = '';

    private bool $budgetTracking = false;

    /**
     * @var \PhpScript\Core\Token[]
     */
    private array $sourceMap = [];

    private int $currentLine = 1;

    private int $loopDepth = 0;

    /**
     * @var string[]
     */
    private array $allowedFunctions = [];

    /**
     * @param  string[]  $allowedFunctions
     */
    public function setAllowedFunctions(array $allowedFunctions): void
    {
        $this->allowedFunctions = $allowedFunctions;
    }

    public function setBudgetTracking(bool $enabled): void
    {
        $this->budgetTracking = $enabled;
    }

    /**
     * @throws AstTraverserException
     */
    public function traverse(Node $node): string
    {
        $this->generatedCode = '';
        $this->sourceMap = [];
        $this->currentLine = 1;
        $this->loopDepth = 0;
        $this->doTraverse($node);

        return $this->generatedCode;
    }

    private function enterLoop(): void
    {
        $this->loopDepth++;
    }

    private function exitLoop(): void
    {
        $this->loopDepth--;
    }

    /**
     * @return \PhpScript\Core\Token[]
     */
    public function getSourceMap(): array
    {
        return $this->sourceMap;
    }

    public function visitBreakStatement(BreakStatement $node): string
    {
        $this->traverseBreakStatement($node);

        return $this->generatedCode;
    }

    public function visitContinueStatement(ContinueStatement $node): string
    {
        $this->traverseContinueStatement($node);

        return $this->generatedCode;
    }

    /**
     * @throws \PhpScript\Exceptions\AstTraverserException
     * @throws \PhpScript\Exceptions\EngineException
     */
    private function doTraverse(Node $node): void
    {
        $token = $node->getToken();
        if ($token && ! isset($this->sourceMap[$this->currentLine])) {
            $this->sourceMap[$this->currentLine] = $token;
        }

        match ($node::class) {
            Program::class => $this->traverseProgram($node),
            EchoStatement::class => $this->traverseEchoStatement($node),
            IfStatement::class => $this->traverseIfStatement($node),
            ForStatement::class => $this->traverseForStatement($node),
            ForeachStatement::class => $this->traverseForeachStatement($node),
            ReturnStatement::class => $this->traverseReturnStatement($node),
            BreakStatement::class => $this->traverseBreakStatement($node),
            ContinueStatement::class => $this->traverseContinueStatement($node),
            Assignment::class => $this->traverseAssignment($node),
            BinaryOperation::class => $this->traverseBinaryOperation($node),
            UnaryOperation::class => $this->traverseUnaryOperation($node),
            PostfixOperation::class => $this->traversePostfixOperation($node),
            ArrayAccess::class => $this->traverseArrayAccess($node),
            MemberAccess::class => $this->traverseMemberAccess($node),
            FunctionCall::class => $this->traverseFunctionCall($node),
            Variable::class => $this->traverseVariable($node),
            Identifier::class => $this->traverseIdentifier($node),
            Literal::class => $this->traverseLiteral($node),
            ListLiteral::class => $this->traverseListLiteral($node),
            MapLiteral::class => $this->traverseMapLiteral($node),
            NoOp::class => $this->traverseNoOp(),
            default => throw AstTraverserException::unknownNodeType($node::class),
        };
    }

    /**
     * Every statement gets its own line in the generated code, so the source map
     * can point runtime errors at the right statement.
     */
    private function traverseProgram(Program $node): void
    {
        foreach ($node->statements as $statement) {
            $this->emitTick();
            $this->doTraverse($statement);
            $this->generatedCode .= ';';
            $this->newline();
        }
    }

    private function newline(): void
    {
        $this->generatedCode .= "\n";
        $this->currentLine++;
    }

    /**
     * Counts one operation. Loop bodies tick too, so even `for (;;) {}` is bounded.
     */
    private function emitTick(): void
    {
        if ($this->budgetTracking) {
            $this->generatedCode .= self::BUDGET . '->tick(); ';
        }
    }

    /**
     * Opens a block body on a new line. Loops tick once per iteration on a line
     * of their own that maps to the loop.
     */
    private function openBlock(?Node $loop = null): void
    {
        $this->generatedCode .= ' {';
        $this->newline();

        if ($loop instanceof Node && $this->budgetTracking) {
            $token = $loop->getToken();
            if ($token instanceof Token) {
                $this->sourceMap[$this->currentLine] = $token;
            }
            $this->emitTick();
            $this->newline();
        }
    }

    private function traverseReturnStatement(ReturnStatement $node): void
    {
        $this->generatedCode .= 'return ';
        if ($node->expression instanceof Node) {
            $this->doTraverse($node->expression);

            return;
        }

        $this->generatedCode .= 'null';
    }

    private function traverseEchoStatement(EchoStatement $node): void
    {
        $this->generatedCode .= 'echo ';
        $this->doTraverse($node->expression);
    }

    private function traverseIfStatement(IfStatement $node): void
    {
        $this->generatedCode .= 'if (';
        $this->doTraverse($node->condition);
        $this->generatedCode .= ')';
        $this->openBlock();
        $this->doTraverse($node->then);
        $this->generatedCode .= '}';

        if ($node->else instanceof Node) {
            $this->generatedCode .= ' else';
            $this->openBlock();
            $this->doTraverse($node->else);
            $this->generatedCode .= '}';
        }
    }

    private function traverseForStatement(ForStatement $node): void
    {
        $this->generatedCode .= 'for (';
        if ($node->initializer instanceof Node) {
            $this->doTraverse($node->initializer);
        }
        $this->generatedCode .= '; ';
        if ($node->condition instanceof Node) {
            $this->doTraverse($node->condition);
        }
        $this->generatedCode .= '; ';
        if ($node->increment instanceof Node) {
            $this->doTraverse($node->increment);
        }
        $this->generatedCode .= ')';
        $this->openBlock($node);
        $this->enterLoop();
        $this->doTraverse($node->body);
        $this->exitLoop();
        $this->generatedCode .= '}';
    }

    private function traverseForeachStatement(ForeachStatement $node): void
    {
        $this->generatedCode .= 'foreach (';
        $this->doTraverse($node->iterable);
        $this->generatedCode .= ' as ';
        if ($node->key instanceof Variable) {
            $this->doTraverse($node->key);
            $this->generatedCode .= ' => ';
        }
        $this->doTraverse($node->value);
        $this->generatedCode .= ')';
        $this->openBlock($node);
        $this->enterLoop();
        $this->doTraverse($node->body);
        $this->exitLoop();
        $this->generatedCode .= '}';
    }

    /**
     * @throws \PhpScript\Exceptions\EngineException
     */
    private function traverseBreakStatement(BreakStatement $node): void
    {
        if ($this->loopDepth === 0) {
            $token = $node->getToken();
            $length = $token instanceof Token ? strlen($token->value) : 5;
            throw EngineException::runtimeError(
                "'break' can only be used inside a loop",
                (int) $token?->line,
                (int) $token?->column,
                (int) $token?->offset,
                $length
            );
        }

        if ($node->level > $this->loopDepth) {
            $token = $node->getToken();
            $length = $token instanceof Token ? strlen($token->value) : 5;
            throw EngineException::runtimeError(
                "Cannot break {$node->level} level(s) (only {$this->loopDepth} loop(s) available)",
                (int) $token?->line,
                (int) $token?->column,
                (int) $token?->offset,
                $length
            );
        }

        $this->generatedCode .= $node->level === 1 ? 'break' : "break {$node->level}";
    }

    /**
     * @throws \PhpScript\Exceptions\EngineException
     */
    private function traverseContinueStatement(ContinueStatement $node): void
    {
        if ($this->loopDepth === 0) {
            $token = $node->getToken();
            $length = $token instanceof Token ? strlen($token->value) : 8;
            throw EngineException::runtimeError(
                "'continue' can only be used inside a loop",
                (int) $token?->line,
                (int) $token?->column,
                (int) $token?->offset,
                $length
            );
        }

        if ($node->level > $this->loopDepth) {
            $token = $node->getToken();
            $length = $token instanceof Token ? strlen($token->value) : 8;
            throw EngineException::runtimeError(
                "Cannot continue {$node->level} level(s) (only {$this->loopDepth} loop(s) available)",
                (int) $token?->line,
                (int) $token?->column,
                (int) $token?->offset,
                $length
            );
        }

        $this->generatedCode .= $node->level === 1 ? 'continue' : "continue {$node->level}";
    }

    /**
     * `x = …` stays a plain PHP assignment. Writes into lists, maps and objects go
     * through Runtime::assign(), which checks every key on the way.
     *
     * @throws \PhpScript\Exceptions\EngineException
     */
    private function traverseAssignment(Assignment $node): void
    {
        if ($node->variable instanceof Variable) {
            $this->doTraverse($node->variable);
            $this->generatedCode .= ' = ';
            $this->doTraverse($node->expression);

            return;
        }

        $this->generatedCode .= self::RUNTIME . '::assign(';
        $this->traversePath($node->variable);
        $this->generatedCode .= ', ';
        $this->doTraverse($node->expression);
        $this->generatedCode .= ')';
    }

    /**
     * Emits `$root, [[kind, key], …]` for a write target like `a.b[c]`.
     *
     * @throws \PhpScript\Exceptions\EngineException
     */
    private function traversePath(Node $target): void
    {
        $segments = [];
        while ($target instanceof MemberAccess || $target instanceof ArrayAccess) {
            $segments[] = $target;
            $target = $target instanceof MemberAccess ? $target->object : $target->array;
        }

        if (! $target instanceof Variable) {
            throw $this->forbidden('Invalid assignment target', $target);
        }

        $this->doTraverse($target);
        $this->generatedCode .= ', [';
        foreach (array_reverse($segments) as $i => $segment) {
            $this->generatedCode .= $i > 0 ? ', ' : '';
            if ($segment instanceof MemberAccess) {
                $this->generatedCode .= '[' . self::RUNTIME . '::MEMBER, ' . var_export($segment->property->name, true) . ']';

                continue;
            }

            $this->generatedCode .= '[' . self::RUNTIME . '::INDEX, ';
            $this->doTraverse($segment->key);
            $this->generatedCode .= ']';
        }
        $this->generatedCode .= ']';
    }

    /**
     * Every operation is wrapped in parentheses, so the PHP code keeps the
     * precedence of the parsed tree.
     *
     * @throws \PhpScript\Exceptions\AstTraverserException
     */
    private function traverseBinaryOperation(BinaryOperation $node): void
    {
        $runtimeCall = match ($node->operator) {
            TokenType::T_PLUS => 'add',
            TokenType::T_COMPARE_EQUALS, TokenType::T_COMPARE_UNEQUALS => 'equals',
            default => null,
        };

        if ($runtimeCall !== null) {
            $negate = $node->operator === TokenType::T_COMPARE_UNEQUALS ? '!' : '';
            $this->generatedCode .= $negate . self::RUNTIME . '::' . $runtimeCall . '(';
            $this->doTraverse($node->left);
            $this->generatedCode .= ', ';
            $this->doTraverse($node->right);
            $this->generatedCode .= $runtimeCall === 'add' && $this->budgetTracking ? ', ' . self::BUDGET . ')' : ')';

            return;
        }

        $comparison = match ($node->operator) {
            TokenType::T_GREATER_THAN => '>',
            TokenType::T_LESS_THAN => '<',
            TokenType::T_GREATER_EQUAL => '>=',
            TokenType::T_LESS_EQUAL => '<=',
            default => null,
        };

        if ($comparison !== null) {
            $this->generatedCode .= self::RUNTIME . '::compare(';
            $this->doTraverse($node->left);
            $this->generatedCode .= ", '" . $comparison . "', ";
            $this->doTraverse($node->right);
            $this->generatedCode .= ')';

            return;
        }

        $operator = match ($node->operator) {
            TokenType::T_MINUS => '-',
            TokenType::T_MULTIPLY => '*',
            TokenType::T_DIVIDE => '/',
            TokenType::T_MODULO => '%',
            TokenType::T_AND => '&&',
            TokenType::T_OR => '||',
            default => throw AstTraverserException::unknownOperator($node->operator->value),
        };

        $this->generatedCode .= '(';
        $this->doTraverse($node->left);
        $this->generatedCode .= ' ' . $operator . ' ';
        $this->doTraverse($node->right);
        $this->generatedCode .= ')';
    }

    /**
     * @throws \PhpScript\Exceptions\AstTraverserException
     */
    private function traverseUnaryOperation(UnaryOperation $node): void
    {
        $operator = match ($node->operator) {
            TokenType::T_BANG => '!',
            TokenType::T_MINUS => '-',
            default => throw AstTraverserException::unknownOperator($node->operator->value),
        };
        $this->generatedCode .= '(' . $operator;
        $this->doTraverse($node->right);
        $this->generatedCode .= ')';
    }

    /**
     * @throws \PhpScript\Exceptions\AstTraverserException
     */
    private function traversePostfixOperation(PostfixOperation $node): void
    {
        $delta = match ($node->operator) {
            TokenType::T_INCREMENT => '1',
            TokenType::T_DECREMENT => '-1',
            default => throw AstTraverserException::unknownOperator($node->operator->value),
        };
        $this->generatedCode .= self::RUNTIME . '::increment(';
        $this->traversePath($node->left);
        $this->generatedCode .= ', ' . $delta . ')';
    }

    private function traverseArrayAccess(ArrayAccess $node): void
    {
        $this->generatedCode .= self::RUNTIME . '::index(';
        $this->doTraverse($node->array);
        $this->generatedCode .= ', ';
        $this->doTraverse($node->key);
        $this->generatedCode .= ')';
    }

    private function traverseMemberAccess(MemberAccess $node): void
    {
        $this->generatedCode .= self::RUNTIME . '::member(';
        $this->doTraverse($node->object);
        $this->generatedCode .= ', ' . var_export($node->property->name, true) . ')';
    }

    /**
     * Only two kinds of calls exist: an allowed function by name and a method of an object.
     * Anything else (`'system'('id')`, `f()()`, `list[0]()`) would call an arbitrary
     * PHP function by its string name and is rejected.
     *
     * Arguments that PHP may call (callable parameters) are checked at runtime by
     * the CallGuard, methods are called through it.
     *
     * @throws \PhpScript\Exceptions\EngineException
     */
    private function traverseFunctionCall(FunctionCall $node): void
    {
        $callee = $node->callee;

        if ($callee instanceof MemberAccess) {
            $method = $callee->property->name;
            if (str_starts_with($method, '__')) {
                throw $this->forbidden(sprintf("Calling magic method '%s' is not allowed", $method), $callee->property);
            }

            $this->generatedCode .= self::GUARD . '->call(';
            $this->doTraverse($callee->object);
            $this->generatedCode .= ', ' . var_export($method, true);
            foreach ($node->arguments as $argument) {
                $this->generatedCode .= ', ';
                $this->doTraverse($argument);
            }
            $this->generatedCode .= ')';

            return;
        }

        if (! $callee instanceof Identifier) {
            throw $this->forbidden('Dynamic function calls are not allowed', $node);
        }

        if (! in_array($callee->name, $this->allowedFunctions, true)) {
            $token = $callee->getToken();
            $length = $token instanceof Token ? strlen($token->value) : strlen($callee->name);
            throw EngineException::invalidFunctionCall($callee->name, (int) $token?->line, (int) $token?->column, (int) $token?->offset, $length);
        }

        $signature = function_exists($callee->name) ? Signature::of(new ReflectionFunction($callee->name)) : [[], false];

        $this->generatedCode .= $callee->name . '(';
        foreach (array_values($node->arguments) as $i => $argument) {
            $this->generatedCode .= $i > 0 ? ', ' : '';
            $check = Signature::at($signature, $i) === Signature::CALLBACK;
            $this->generatedCode .= $check ? self::GUARD . '->callback(' : '';
            $this->doTraverse($argument);
            $this->generatedCode .= $check ? ')' : '';
        }
        $this->generatedCode .= ')';
    }

    private function forbidden(string $message, Node $node): EngineException
    {
        $token = $node->getToken();

        return EngineException::compileError(
            $message,
            (int) $token?->line,
            (int) $token?->column,
            (int) $token?->offset,
            $token instanceof Token ? max(1, strlen($token->value)) : 1,
        );
    }

    /**
     * @throws \PhpScript\Exceptions\EngineException
     */
    private function traverseVariable(Variable $node): void
    {
        if (in_array($node->name, self::RESERVED_VARIABLES, true) || str_starts_with($node->name, self::INTERNAL_PREFIX)) {
            throw $this->forbidden(sprintf("Variable name '%s' is reserved", $node->name), $node);
        }

        $this->generatedCode .= '$' . $node->name;
    }

    private function traverseIdentifier(Identifier $node): void
    {
        $this->generatedCode .= $node->name;
    }

    /**
     * @throws \PhpScript\Exceptions\AstTraverserException
     */
    private function traverseLiteral(Literal $node): void
    {
        $value = $node->value;
        if (! is_scalar($value) && $value !== null) {
            throw AstTraverserException::unknownLiteralType(gettype($value));
        }

        if (is_string($value) && preg_match('/[\x00-\x1f\x7f]/', $value) === 1) {
            // A literal line break would shift the lines of the source map.
            // Octal escapes for all control characters: addcslashes() would write `\a`
            // and `\b`, which PHP does not understand in double-quoted strings.
            $escaped = (string) preg_replace_callback(
                '/[\x00-\x1f\x7f]/',
                static fn (array $match): string => sprintf('\\%03o', ord($match[0])),
                addcslashes($value, '"\\$'),
            );
            $this->generatedCode .= '"' . $escaped . '"';

            return;
        }

        $this->generatedCode .= $value === null ? 'null' : var_export($value, true);
    }

    private function traverseListLiteral(ListLiteral $node): void
    {
        $this->generatedCode .= '[';
        foreach ($node->elements as $i => $element) {
            $this->generatedCode .= $i > 0 ? ', ' : '';
            $this->doTraverse($element);
        }
        $this->generatedCode .= ']';
    }

    private function traverseMapLiteral(MapLiteral $node): void
    {
        $this->generatedCode .= '(object) [';
        foreach ($node->entries as $i => [$key, $value]) {
            $this->generatedCode .= ($i > 0 ? ', ' : '') . var_export($key, true) . ' => ';
            $this->doTraverse($value);
        }
        $this->generatedCode .= ']';
    }

    private function traverseNoOp(): void
    {
        // empty
    }
}
