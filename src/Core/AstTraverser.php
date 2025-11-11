<?php

declare(strict_types=1);

namespace PhpScript\Core;

use PhpScript\Ast\ArrayAccess;
use PhpScript\Ast\Assignment;
use PhpScript\Ast\BinaryOperation;
use PhpScript\Ast\EchoStatement;
use PhpScript\Ast\ForeachStatement;
use PhpScript\Ast\ForStatement;
use PhpScript\Ast\FunctionCall;
use PhpScript\Ast\Identifier;
use PhpScript\Ast\IfStatement;
use PhpScript\Ast\Literal;
use PhpScript\Ast\MemberAccess;
use PhpScript\Ast\NoOp;
use PhpScript\Ast\PostfixOperation;
use PhpScript\Ast\Program;
use PhpScript\Ast\UnaryOperation;
use PhpScript\Ast\Variable;
use PhpScript\Contracts\AstTraverserInterface;
use PhpScript\Contracts\Node;
use PhpScript\Exceptions\AstTraverserException;
use PhpScript\Exceptions\EngineException;

final class AstTraverser implements AstTraverserInterface
{
    private string $generatedCode = '';

    /**
     * @var \PhpScript\Core\Token[]
     */
    private array $sourceMap = [];

    private int $currentLine = 1;

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

    /**
     * @throws AstTraverserException
     */
    public function traverse(Node $node): string
    {
        $this->generatedCode = '';
        $this->sourceMap = [];
        $this->currentLine = 1;
        $this->doTraverse($node);

        return $this->generatedCode;
    }

    /**
     * @return \PhpScript\Core\Token[]
     */
    public function getSourceMap(): array
    {
        return $this->sourceMap;
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
            NoOp::class => $this->traverseNoOp(),
            default => throw AstTraverserException::unknownNodeType($node::class),
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

    private function traverseIfStatement(IfStatement $node): void
    {
        $this->generatedCode .= 'if (';
        $this->doTraverse($node->condition);
        $this->generatedCode .= ') {';
        $this->doTraverse($node->then);
        $this->generatedCode .= '}';

        if ($node->else instanceof Node) {
            $this->generatedCode .= ' else {';
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
        $this->generatedCode .= ') {';
        $this->doTraverse($node->body);
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
        $this->generatedCode .= ') {';
        $this->doTraverse($node->body);
        $this->generatedCode .= '}';
    }

    private function traverseAssignment(Assignment $node): void
    {
        $this->doTraverse($node->variable);
        $this->generatedCode .= ' = ';
        $this->doTraverse($node->expression);
    }

    /**
     * @throws \PhpScript\Exceptions\AstTraverserException
     */
    private function traverseBinaryOperation(BinaryOperation $node): void
    {
        if ($node->operator === TokenType::T_PLUS) {
            $this->generatedCode .= '((is_numeric(';
            $this->doTraverse($node->left);
            $this->generatedCode .= ') && is_numeric(';
            $this->doTraverse($node->right);
            $this->generatedCode .= ')) ? (';
            $this->doTraverse($node->left);
            $this->generatedCode .= ' + ';
            $this->doTraverse($node->right);
            $this->generatedCode .= ') : (';
            $this->doTraverse($node->left);
            $this->generatedCode .= ' . ';
            $this->doTraverse($node->right);
            $this->generatedCode .= '))';

            return;
        }

        $this->doTraverse($node->left);
        $operator = match ($node->operator) {
            TokenType::T_MINUS => '-',
            TokenType::T_MULTIPLY => '*',
            TokenType::T_DIVIDE => '/',
            TokenType::T_COMPARE_EQUALS => '===',
            TokenType::T_COMPARE_UNEQUALS => '!==',
            TokenType::T_GREATER_THAN => '>',
            TokenType::T_LESS_THAN => '<',
            default => throw AstTraverserException::unknownOperator($node->operator->value),
        };

        $this->generatedCode .= ' ' . $operator . ' ';
        $this->doTraverse($node->right);
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
        $this->generatedCode .= $operator;
        $this->doTraverse($node->right);
    }

    /**
     * @throws \PhpScript\Exceptions\AstTraverserException
     */
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
        $this->generatedCode .= '->';
        $this->doTraverse($node->property);
    }

    /**
     * @throws \PhpScript\Exceptions\EngineException
     */
    private function traverseFunctionCall(FunctionCall $node): void
    {
        if ($node->callee instanceof Identifier && ! in_array($node->callee->name, $this->allowedFunctions, true)) {
            $token = $node->callee->getToken();
            $length = $token instanceof Token ? strlen($token->value) : strlen($node->callee->name);
            throw EngineException::invalidFunctionCall($node->callee->name, (int) $token?->line, (int) $token?->column, (int) $token?->offset, $length);
        }

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
        if (is_numeric($value)) {
            $this->generatedCode .= (string) $value;

            return;
        }

        if (is_string($value)) {
            $this->generatedCode .= "'" . addslashes($value) . "'";

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
        throw AstTraverserException::unknownLiteralType(gettype($value));
        // @codeCoverageIgnoreEnd
    }

    private function traverseNoOp(): void
    {
        // empty
    }
}
