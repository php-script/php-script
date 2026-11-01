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

final class PhpScriptRenderer implements AstTraverserInterface
{
    private string $generatedCode = '';

    private int $indentLevel = 0;

    /**
     * @param  string[]  $allowedFunctions
     *
     * @codeCoverageIgnore
     */
    public function setAllowedFunctions(array $allowedFunctions): void
    {
        // do nothing
    }

    /**
     * @codeCoverageIgnore
     */
    public function setBudgetTracking(bool $enabled): void
    {
        // do nothing
    }

    /**
     * @return Token[]
     *
     * @codeCoverageIgnore
     */
    public function getSourceMap(): array
    {
        return [];
    }

    public function traverse(Node $node): string
    {
        $this->generatedCode = '';
        $this->doTraverse($node);

        return $this->generatedCode;
    }

    private function doTraverse(Node $node): void
    {
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
            // @codeCoverageIgnoreStart
            default => throw AstTraverserException::unknownNodeType($node::class),
            // @codeCoverageIgnoreEnd
        };
    }

    private function traverseProgram(Program $node): void
    {
        foreach ($node->statements as $statement) {
            $this->doTraverse($statement);
            $this->generatedCode .= "\n" . $this->lineIndent();
        }
    }

    private function traverseReturnStatement(ReturnStatement $node): void
    {
        $this->generatedCode .= 'return';
        if ($node->expression instanceof Node) {
            $this->generatedCode .= ' ';
            $this->doTraverse($node->expression);
        }
        $this->generatedCode .= ';';
    }

    private function traverseEchoStatement(EchoStatement $node): void
    {
        $this->generatedCode .= 'echo ';
        $this->doTraverse($node->expression);
        $this->generatedCode .= ';';
    }

    private function traverseIfStatement(IfStatement $node): void
    {
        $this->generatedCode .= 'if (';
        $this->doTraverse($node->condition);
        $this->generatedCode .= ') {' . "\n" . $this->lineIndent(1);
        $this->doTraverse($node->then);
        $this->generatedCode .= "\n" . $this->lineIndent(-1) . '}';

        if ($node->else instanceof Node) {
            $this->generatedCode .= ' else {' . "\n" . $this->lineIndent(1);
            $this->doTraverse($node->else);
            $this->generatedCode .= "\n" . $this->lineIndent(-1) . '}' . "\n" . $this->lineIndent();
        } else {
            $this->generatedCode .= "\n" . $this->lineIndent();
        }
    }

    private function traverseForStatement(ForStatement $node): void
    {
        $this->generatedCode .= 'for (';
        if ($node->initializer instanceof Node) {
            $this->doTraverse($node->initializer);
            $this->generatedCode .= ' ';
        } else {
            $this->generatedCode .= '; ';
        }

        if ($node->condition instanceof Node) {
            $this->doTraverse($node->condition);
        }
        $this->generatedCode .= '; ';
        if ($node->increment instanceof Node) {
            $this->doTraverse($node->increment);
        }
        $this->generatedCode .= ') {' . "\n" . $this->lineIndent(1);
        $this->doTraverse($node->body);
        $this->generatedCode .= "\n" . $this->lineIndent(-1) . '}' . "\n" . $this->lineIndent();
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
        $this->generatedCode .= ') {' . "\n" . $this->lineIndent(1);
        $this->doTraverse($node->body);
        $this->generatedCode .= "\n" . $this->lineIndent(-1) . '}' . "\n" . $this->lineIndent();
    }

    public function visitBreakStatement(BreakStatement $node): string
    {
        $this->traverseBreakStatement($node);

        return $this->generatedCode;
    }

    private function traverseBreakStatement(BreakStatement $node): void
    {
        $this->generatedCode .= $node->level === 1 ? 'break;' : "break {$node->level};";
    }

    public function visitContinueStatement(ContinueStatement $node): string
    {
        $this->traverseContinueStatement($node);

        return $this->generatedCode;
    }

    private function traverseContinueStatement(ContinueStatement $node): void
    {
        $this->generatedCode .= $node->level === 1 ? 'continue;' : "continue {$node->level};";
    }

    private function traverseAssignment(Assignment $node): void
    {
        $this->doTraverse($node->variable);
        $this->generatedCode .= ' = ';
        $this->doTraverse($node->expression);
        $this->generatedCode .= ';';
    }

    private function traverseBinaryOperation(BinaryOperation $node): void
    {
        $operator = match ($node->operator) {
            TokenType::T_PLUS => '+',
            TokenType::T_MINUS => '-',
            TokenType::T_MULTIPLY => '*',
            TokenType::T_DIVIDE => '/',
            TokenType::T_MODULO => '%',
            TokenType::T_COMPARE_EQUALS => '==',
            TokenType::T_COMPARE_UNEQUALS => '!=',
            TokenType::T_GREATER_THAN => '>',
            TokenType::T_LESS_THAN => '<',
            TokenType::T_GREATER_EQUAL => '>=',
            TokenType::T_LESS_EQUAL => '<=',
            TokenType::T_AND => '&&',
            TokenType::T_OR => '||',
            default => throw AstTraverserException::unknownOperator($node->operator->value),
        };

        $precedence = $this->precedence($node);
        // Operators are left-associative: the right operand needs parentheses already on equal precedence.
        $this->traverseOperand($node->left, $this->precedence($node->left) < $precedence);
        $this->generatedCode .= ' ' . $operator . ' ';
        $this->traverseOperand($node->right, $this->precedence($node->right) <= $precedence);
    }

    private function traverseOperand(Node $node, bool $parenthesize): void
    {
        $this->generatedCode .= $parenthesize ? '(' : '';
        $this->doTraverse($node);
        $this->generatedCode .= $parenthesize ? ')' : '';
    }

    /**
     * Binding strength as used by the parser; higher binds tighter.
     */
    private function precedence(Node $node): int
    {
        if ($node instanceof UnaryOperation) {
            return 7;
        }

        if (! $node instanceof BinaryOperation) {
            return 8;
        }

        return match ($node->operator) {
            TokenType::T_OR => 1,
            TokenType::T_AND => 2,
            TokenType::T_COMPARE_EQUALS, TokenType::T_COMPARE_UNEQUALS => 3,
            TokenType::T_PLUS, TokenType::T_MINUS => 5,
            TokenType::T_MULTIPLY, TokenType::T_DIVIDE, TokenType::T_MODULO => 6,
            default => 4,
        };
    }

    private function traverseUnaryOperation(UnaryOperation $node): void
    {
        $operator = match ($node->operator) {
            TokenType::T_BANG => '!',
            TokenType::T_MINUS => '-',
            default => throw AstTraverserException::unknownOperator($node->operator->value),
        };
        $this->generatedCode .= $operator;
        // `- -a` must not become `--a`, which is a decrement.
        $doubleMinus = $node->operator === TokenType::T_MINUS
            && $node->right instanceof UnaryOperation
            && $node->right->operator === TokenType::T_MINUS;
        $this->traverseOperand($node->right, $doubleMinus || $this->precedence($node->right) < 7);
    }

    private function traversePostfixOperation(PostfixOperation $node): void
    {
        $this->doTraverse($node->left);
        $operator = match ($node->operator) {
            TokenType::T_INCREMENT => '++',
            TokenType::T_DECREMENT => '--',
            default => throw AstTraverserException::unknownOperator($node->operator->value),
        };
        $this->generatedCode .= $operator;
    }

    private function traverseArrayAccess(ArrayAccess $node): void
    {
        $this->doTraverse($node->array);
        $this->generatedCode .= '[';
        $this->doTraverse($node->key);
        $this->generatedCode .= ']';
    }

    private function traverseMemberAccess(MemberAccess $node): void
    {
        $this->doTraverse($node->object);
        $this->generatedCode .= '.';
        $this->doTraverse($node->property);
    }

    private function traverseFunctionCall(FunctionCall $node): void
    {
        $this->doTraverse($node->callee);
        $this->generatedCode .= '(';
        foreach ($node->arguments as $i => $argument) {
            $this->doTraverse($argument);
            if ($i < count($node->arguments) - 1) {
                $this->generatedCode .= ', ';
            }
        }
        $this->generatedCode .= ')';
    }

    private function traverseVariable(Variable $node): void
    {
        $this->generatedCode .= $node->name;
    }

    private function traverseIdentifier(Identifier $node): void
    {
        $this->generatedCode .= $node->name;
    }

    private function traverseLiteral(Literal $node): void
    {
        $value = $node->value;
        if (is_int($value)) {
            $this->generatedCode .= (string) $value;

            return;
        }

        if (is_float($value)) {
            $this->generatedCode .= $this->renderFloat($value);

            return;
        }

        if (is_string($value)) {
            if ($value === PHP_EOL) {
                $this->generatedCode .= 'LINEBREAK';

                return;
            }
            $this->generatedCode .= $this->quote($value);

            return;
        }

        if (is_bool($value)) {
            $this->generatedCode .= $value ? 'true' : 'false';

            return;
        }

        if ($value === null) {
            $this->generatedCode .= 'null';

            return;
        }

        throw AstTraverserException::unknownLiteralType(gettype($value));
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
        $this->generatedCode .= '{';
        foreach ($node->entries as $i => [$key, $value]) {
            $this->generatedCode .= $i > 0 ? ', ' : '';
            $this->generatedCode .= $this->isBareKey($key) ? $key : $this->quote($key);
            $this->generatedCode .= ': ';
            $this->doTraverse($value);
        }
        $this->generatedCode .= '}';
    }

    private function isBareKey(string $key): bool
    {
        return preg_match('/^[a-zA-Z_]\w*$/', $key) === 1
            && ! in_array($key, ['if', 'else', 'for', 'foreach', 'as', 'echo', 'return', 'true', 'false', 'null', 'break', 'continue', 'LINEBREAK'], true);
    }

    private function traverseNoOp(): void {}

    private function lineIndent(int $increment = 0): string
    {
        $this->indentLevel += $increment;

        return str_repeat(' ', $this->indentLevel * 4);
    }

    /**
     * Quotes a string so that the lexer reads back the same value.
     */
    private function quote(string $value): string
    {
        return "'" . addcslashes($value, "\0..\37'\\\177") . "'";
    }

    /**
     * Floats without exponent, because the language has no `1.0E+25` syntax.
     */
    private function renderFloat(float $value): string
    {
        $exported = var_export($value, true);
        if (! str_contains($exported, 'E')) {
            return $exported;
        }

        // Expand the shortest round-trip form (e.g. "1.5E-8") without adding digits.
        [$mantissa, $exponent] = explode('E', $exported);
        $sign = str_starts_with($mantissa, '-') ? '-' : '';
        $digits = str_replace(['-', '.'], '', $mantissa);
        $point = strlen(explode('.', ltrim($mantissa, '-'))[0]) + (int) $exponent;

        if ($point <= 0) {
            return $sign . '0.' . str_repeat('0', -$point) . rtrim($digits, '0');
        }

        $digits = str_pad($digits, $point, '0');
        $fraction = rtrim(substr($digits, $point), '0');

        return $sign . substr($digits, 0, $point) . '.' . ($fraction === '' ? '0' : $fraction);
    }
}
