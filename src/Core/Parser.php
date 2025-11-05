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
use PhpScript\Exceptions\ParseException;

final class Parser implements ParserInterface
{
    /**
     * @var list<Token>
     */
    private array $tokens;

    private int $position = 0;

    /**
     * @param  list<Token>  $tokens
     *
     * @throws \PhpScript\Exceptions\ParseException
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

    /**
     * @throws \PhpScript\Exceptions\ParseException
     */
    private function parseStatement(): Node
    {
        $token = $this->peek();
        if ($this->match(TokenType::T_ECHO)) {
            return $this->parseEchoStatement($token);
        }

        if ($this->match(TokenType::T_SEMICOLON)) {
            return new NoOp($token);
        }

        $node = $this->parseExpression();

        $this->match(TokenType::T_SEMICOLON);

        return $node;
    }

    /**
     * @throws \PhpScript\Exceptions\ParseException
     */
    private function parseEchoStatement(Token $token): EchoStatement
    {
        $expression = $this->parseExpression();

        return new EchoStatement($expression, $token);
    }

    /**
     * @throws \PhpScript\Exceptions\ParseException
     */
    private function parseExpression(): Node
    {
        return $this->parseAssignment();
    }

    /**
     * @throws \PhpScript\Exceptions\ParseException
     */
    private function parseAssignment(): Node
    {
        $left = $this->parseConcat();

        if ($this->match(TokenType::T_EQUALS)) {
            $token = $this->previous();
            if (! ($left instanceof Variable)) {
                throw new ParseException('Invalid assignment target.', $token);
            }
            $right = $this->parseAssignment();

            return new Assignment($left, $right, $token);
        }

        return $left;
    }

    /**
     * @throws \PhpScript\Exceptions\ParseException
     */
    private function parseConcat(): Node
    {
        $node = $this->parseAdditive();

        while ($this->match(TokenType::T_CONCAT)) {
            $token = $this->previous();
            $operator = $this->previous()->type;
            $right = $this->parseAdditive();
            $node = new BinaryOperation($node, $operator, $right, $token);
        }

        return $node;
    }

    /**
     * @throws \PhpScript\Exceptions\ParseException
     */
    private function parseAdditive(): Node
    {
        $node = $this->parseMultiplicative();

        while ($this->match(TokenType::T_PLUS, TokenType::T_MINUS)) {
            $token = $this->previous();
            $operator = $this->previous()->type;
            $right = $this->parseMultiplicative();
            $node = new BinaryOperation($node, $operator, $right, $token);
        }

        return $node;
    }

    /**
     * @throws \PhpScript\Exceptions\ParseException
     */
    private function parseMultiplicative(): Node
    {
        $node = $this->parsePrimary();

        while ($this->match(TokenType::T_MULTIPLY, TokenType::T_DIVIDE)) {
            $token = $this->previous();
            $operator = $this->previous()->type;
            $right = $this->parsePrimary();
            $node = new BinaryOperation($node, $operator, $right, $token);
        }

        return $node;
    }

    /**
     * @throws \PhpScript\Exceptions\ParseException
     */
    private function parsePrimary(): Node
    {
        $token = $this->peek();
        if ($this->match(TokenType::T_NUMBER, TokenType::T_STRING)) {
            return new Literal($this->previous()->value, $token);
        }

        if ($this->match(TokenType::T_IDENTIFIER)) {
            $node = new Variable($this->previous()->value, $token);

            while ($this->match(TokenType::T_DOT)) {
                $propertyToken = $this->consume(TokenType::T_IDENTIFIER, 'Expected identifier after .');
                $property = new Identifier($propertyToken->value, $propertyToken);
                $node = new MemberAccess($node, $property, $propertyToken);
            }

            return $node;
        }

        if ($this->match(TokenType::T_LPAREN)) {
            $node = $this->parseExpression();
            $this->consume(TokenType::T_RPAREN, "Expect ')' after expression.");

            return $node;
        }

        throw new ParseException('Unexpected token: '.$this->peek()->type->value, $this->peek());
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

    /**
     * @throws \PhpScript\Exceptions\ParseException
     */
    private function consume(TokenType $type, string $message): Token
    {
        if ($this->check($type)) {
            return $this->advance();
        }

        if ($this->isAtEnd()) {
            throw new ParseException($message, $this->previous());
        }

        // @codeCoverageIgnoreStart
        throw new ParseException($message, $this->peek());
        // @codeCoverageIgnoreEnd
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
