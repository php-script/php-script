<?php

declare(strict_types=1);

namespace PhpScript\Core;

use PhpScript\Ast\Assignment;
use PhpScript\Ast\BinaryOperation;
use PhpScript\Ast\EchoStatement;
use PhpScript\Ast\Identifier;
use PhpScript\Ast\Literal;
use PhpScript\Ast\MemberAccess;
use PhpScript\Ast\NoOp;
use PhpScript\Ast\Program;
use PhpScript\Ast\Variable;
use PhpScript\Contracts\AstTraverserInterface;
use PhpScript\Contracts\Node;
use RuntimeException;

final class AstTraverser implements AstTraverserInterface
{
    private string $generatedCode = '';

    private array $sourceMap = [];

    private int $currentLine = 1;

    public function traverse(Node $node): string
    {
        $this->generatedCode = '';
        $this->sourceMap = [];
        $this->currentLine = 1;
        $this->doTraverse($node);

        return $this->generatedCode;
    }

    public function getSourceMap(): array
    {
        return $this->sourceMap;
    }

    private function doTraverse(Node $node): void
    {
        $token = $node->getToken();
        if ($token instanceof \PhpScript\Core\Token) {
            $this->sourceMap[$this->currentLine] = $token;
        }

        match ($node::class) {
            Program::class => $this->traverseProgram($node),
            EchoStatement::class => $this->traverseEchoStatement($node),
            Assignment::class => $this->traverseAssignment($node),
            BinaryOperation::class => $this->traverseBinaryOperation($node),
            MemberAccess::class => $this->traverseMemberAccess($node),
            Variable::class => $this->traverseVariable($node),
            Identifier::class => $this->traverseIdentifier($node),
            Literal::class => $this->traverseLiteral($node),
            NoOp::class => $this->traverseNoOp(),
            default => throw new RuntimeException('Unknown node type: '.$node::class),
        };
    }

    private function traverseProgram(Program $node): void
    {
        foreach ($node->statements as $statement) {
            $this->doTraverse($statement);
            $this->generatedCode .= ";\n";
            $this->currentLine++;
        }
    }

    private function traverseEchoStatement(EchoStatement $node): void
    {
        $this->generatedCode .= 'echo ';
        $this->doTraverse($node->expression);
    }

    private function traverseAssignment(Assignment $node): void
    {
        $this->doTraverse($node->variable);
        $this->generatedCode .= ' = ';
        $this->doTraverse($node->expression);
    }

    private function traverseBinaryOperation(BinaryOperation $node): void
    {
        $this->doTraverse($node->left);
        $operator = match ($node->operator) {
            TokenType::T_PLUS => '+',
            TokenType::T_MINUS => '-',
            TokenType::T_MULTIPLY => '*',
            TokenType::T_DIVIDE => '/',
            TokenType::T_CONCAT => '.',
            TokenType::T_EQUALS_EQUALS => '==',
            TokenType::T_GT => '>',
            TokenType::T_LT => '<',
            default => throw new RuntimeException('Unknown operator: '.$node->operator->value),
        };
        $this->generatedCode .= ' '.$operator.' ';
        $this->doTraverse($node->right);
    }

    private function traverseMemberAccess(MemberAccess $node): void
    {
        $this->doTraverse($node->object);
        $this->generatedCode .= '->';
        $this->doTraverse($node->property);
    }

    private function traverseVariable(Variable $node): void
    {
        $this->generatedCode .= '$'.$node->name;
    }

    private function traverseIdentifier(Identifier $node): void
    {
        $this->generatedCode .= $node->name;
    }

    private function traverseLiteral(Literal $node): void
    {
        $value = $node->value;
        if (is_numeric($value)) {
            $this->generatedCode .= (string) $value;

            return;
        }

        if (is_string($value)) {
            $this->generatedCode .= "'".addslashes($value)."'";

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

        // @codeCoverageIgnoreStart
        throw new RuntimeException('Unknown literal type: '.gettype($value));
        // @codeCoverageIgnoreEnd
    }

    private function traverseNoOp(): void
    {
        // empty
    }
}
