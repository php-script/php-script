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
use PhpScript\Contracts\Node;
use PhpScript\Contracts\ParserInterface;
use RuntimeException;

final class Parser implements ParserInterface
{
    /**
     * @var list<Token>
     */
    private array $tokens;

    private int $position = 0;

    /**
     * @param  list<Token>  $tokens
     */
    public function parse(array $tokens): Node
    {
        $this->tokens = array_values(array_filter($tokens, static fn (Token $token): bool => ! in_array($token->type, [TokenType::T_WHITESPACE, TokenType::T_COMMENT], true)));
        $this->position = 0;

        $statements = [];
        while (! $this->isAtEnd()) {
            $statements[] = $this->parseStatement();
        }

        return new Program($statements);
    }

    private function parseStatement(): Node
    {
        if ($this->match(TokenType::T_ECHO)) {
            return $this->parseEchoStatement();
        }

        if ($this->match(TokenType::T_SEMICOLON)) {
            return new NoOp;
        }

        $node = $this->parseExpression();

        $this->match(TokenType::T_SEMICOLON);

        return $node;
    }

    private function parseEchoStatement(): EchoStatement
    {
        $expression = $this->parseExpression();

        return new EchoStatement($expression);
    }

    private function parseExpression(): Node
    {
        return $this->parseAssignment();
    }

    private function parseAssignment(): Node
    {
        $left = $this->parseConcat();

        if ($this->match(TokenType::T_EQUALS)) {
            if (! ($left instanceof Variable)) {
                throw new RuntimeException('Invalid assignment target.');
            }
            $right = $this->parseAssignment();

            return new Assignment($left, $right);
        }

        return $left;
    }

    private function parseConcat(): Node
    {
        $node = $this->parseAdditive();

        while ($this->match(TokenType::T_CONCAT)) {
            $operator = $this->previous()->type;
            $right = $this->parseAdditive();
            $node = new BinaryOperation($node, $operator, $right);
        }

        return $node;
    }

    private function parseAdditive(): Node
    {
        $node = $this->parseMultiplicative();

        while ($this->match(TokenType::T_PLUS, TokenType::T_MINUS)) {
            $operator = $this->previous()->type;
            $right = $this->parseMultiplicative();
            $node = new BinaryOperation($node, $operator, $right);
        }

        return $node;
    }

    private function parseMultiplicative(): Node
    {
        $node = $this->parsePrimary();

        while ($this->match(TokenType::T_MULTIPLY, TokenType::T_DIVIDE)) {
            $operator = $this->previous()->type;
            $right = $this->parsePrimary();
            $node = new BinaryOperation($node, $operator, $right);
        }

        return $node;
    }

    private function parsePrimary(): Node
    {
        if ($this->match(TokenType::T_NUMBER, TokenType::T_STRING)) {
            return new Literal($this->previous()->value);
        }

        if ($this->match(TokenType::T_IDENTIFIER)) {
            $node = new Variable($this->previous()->value);

            while ($this->match(TokenType::T_DOT)) {
                $property = $this->consume(TokenType::T_IDENTIFIER, 'Expected identifier after .');
                $node = new MemberAccess($node, new Identifier($property->value));
            }

            return $node;
        }

        if ($this->match(TokenType::T_LPAREN)) {
            $node = $this->parseExpression();
            $this->consume(TokenType::T_RPAREN, "Expect ')' after expression.");

            return $node;
        }

        throw new RuntimeException('Unexpected token: '.$this->peek()->type->value);
    }

    private function match(TokenType ...$types): bool
    {
        foreach ($types as $type) {
            if ($this->check($type)) {
                $this->advance();

                return true;
            }
        }

        return false;
    }

    private function consume(TokenType $type, string $message): Token
    {
        if ($this->check($type)) {
            return $this->advance();
        }

        throw new RuntimeException($message);
    }

    private function check(TokenType $type): bool
    {
        if ($this->isAtEnd()) {
            return false;
        }

        return $this->peek()->type === $type;
    }

    private function advance(): Token
    {
        if (! $this->isAtEnd()) {
            $this->position++;
        }

        return $this->previous();
    }

    private function isAtEnd(): bool
    {
        return $this->position >= count($this->tokens);
    }

    private function peek(): Token
    {
        return $this->tokens[$this->position];
    }

    private function previous(): Token
    {
        return $this->tokens[$this->position - 1];
    }
}
