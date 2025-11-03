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
    public function traverse(Node $node): string
    {
        return match ($node::class) {
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

    private function traverseProgram(Program $node): string
    {
        $result = '';
        foreach ($node->statements as $statement) {
            $result .= $this->traverse($statement).";\n";
        }

        return $result;
    }

    private function traverseEchoStatement(EchoStatement $node): string
    {
        return 'echo '.$this->traverse($node->expression);
    }

    private function traverseAssignment(Assignment $node): string
    {
        return $this->traverse($node->variable).' = '.$this->traverse($node->expression);
    }

    private function traverseBinaryOperation(BinaryOperation $node): string
    {
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

        return $this->traverse($node->left).' '.$operator.' '.$this->traverse($node->right);
    }

    private function traverseMemberAccess(MemberAccess $node): string
    {
        return $this->traverse($node->object).'->'.$this->traverse($node->property);
    }

    private function traverseVariable(Variable $node): string
    {
        return '$'.$node->name;
    }

    private function traverseIdentifier(Identifier $node): string
    {
        return $node->name;
    }

    private function traverseLiteral(Literal $node): string
    {
        $value = $node->value;
        if (is_numeric($value)) {
            return (string) $value;
        }

        if (is_string($value)) {
            return "'".addslashes($value)."'";
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value === null) {
            return 'null';
        }

        // @codeCoverageIgnoreStart
        throw new RuntimeException('Unknown literal type: '.gettype($value));
        // @codeCoverageIgnoreEnd
    }

    private function traverseNoOp(): string
    {
        return '';
    }
}
