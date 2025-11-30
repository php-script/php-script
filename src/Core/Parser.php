<?php

declare(strict_types=1);

namespace PhpScript\Core;

use PhpScript\Ast\ArrayAccess;
use PhpScript\Ast\Assignment;
use PhpScript\Ast\BinaryOperation;
use PhpScript\Ast\BreakStatement;
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
use PhpScript\Contracts\Node;
use PhpScript\Contracts\ParserInterface;
use PhpScript\Exceptions\ParseException;

final class Parser implements ParserInterface
{
    /**
     * @var \PhpScript\Core\Token[]
     */
    private array $tokens;

    private int $position = 0;

    /**
     * @param  \PhpScript\Core\Token[]  $tokens
     *
     * @throws \PhpScript\Exceptions\ParseException
     */
    public function parse(array $tokens): Node
    {
        $this->tokens = array_values(
            array_filter(
                $tokens,
                static fn (Token $token): bool => ! in_array($token->type, [TokenType::T_WHITESPACE, TokenType::T_COMMENT], true)
            )
        );
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
            $node = $this->parseEchoStatement($token);
            $this->match(TokenType::T_SEMICOLON);

            return $node;
        }

        if ($this->match(TokenType::T_IF)) {
            return $this->parseIfStatement($token);
        }

        if ($this->match(TokenType::T_FOR)) {
            return $this->parseForStatement($token);
        }

        if ($this->match(TokenType::T_FOREACH)) {
            return $this->parseForeachStatement($token);
        }

        if ($this->match(TokenType::T_BREAK)) {
            $node = $this->parseBreakStatement($token);
            $this->match(TokenType::T_SEMICOLON);

            return $node;
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
    private function parseForStatement(Token $token): ForStatement
    {
        $this->consume(TokenType::T_LEFT_PARENTHESIS, "Expect '(' after 'for'.");

        $initializer = null;
        if (! $this->check(TokenType::T_SEMICOLON)) {
            $initializer = $this->parseExpression();
        }
        $this->consume(TokenType::T_SEMICOLON, "Expect ';' after loop initializer.");

        $condition = null;
        if (! $this->check(TokenType::T_SEMICOLON)) {
            $condition = $this->parseExpression();
        }
        $this->consume(TokenType::T_SEMICOLON, "Expect ';' after loop condition.");

        $increment = null;
        if (! $this->check(TokenType::T_RIGHT_PARENTHESIS)) {
            $increment = $this->parseExpression();
        }
        $this->consume(TokenType::T_RIGHT_PARENTHESIS, "Expect ')' after for clauses.");

        $body = $this->parseBlock();

        return new ForStatement($initializer, $condition, $increment, $body, $token);
    }

    /**
     * @throws \PhpScript\Exceptions\ParseException
     */
    private function parseForeachStatement(Token $token): ForeachStatement
    {
        $this->consume(TokenType::T_LEFT_PARENTHESIS, "Expect '(' after 'foreach'.");
        $iterable = $this->parseExpression();
        $this->consume(TokenType::T_AS, "Expect 'as' after iterable.");
        $valueToken = $this->consume(TokenType::T_IDENTIFIER, 'Expect identifier for value.');
        $value = new Variable($valueToken->value, $valueToken);
        $key = null;
        if ($this->match(TokenType::T_COMMA)) {
            $key = $value;
            $valueToken = $this->consume(TokenType::T_IDENTIFIER, 'Expect identifier for value.');
            $value = new Variable($valueToken->value, $valueToken);
        }
        $this->consume(TokenType::T_RIGHT_PARENTHESIS, "Expect ')' after foreach details.");
        $body = $this->parseBlock();

        return new ForeachStatement($iterable, $value, $key, $body, $token);
    }

    /**
     * @throws \PhpScript\Exceptions\ParseException
     */
    private function parseIfStatement(Token $token): IfStatement
    {
        $this->consume(TokenType::T_LEFT_PARENTHESIS, "Expect '(' after 'if'.");
        $condition = $this->parseExpression();
        $this->consume(TokenType::T_RIGHT_PARENTHESIS, "Expect ')' after if condition.");

        $thenBranch = $this->parseBlock();
        $elseBranch = null;

        if ($this->match(TokenType::T_ELSE)) {
            $elseBranch = $this->parseBlock();
        }

        return new IfStatement($condition, $thenBranch, $elseBranch, $token);
    }

    /**
     * @throws \PhpScript\Exceptions\ParseException
     */
    private function parseBlock(): Node
    {
        $this->consume(TokenType::T_LEFT_BRACE, "Expect '{' before block.");

        $statements = [];
        while (! $this->check(TokenType::T_RIGHT_BRACE) && ! $this->isAtEnd()) {
            $statements[] = $this->parseStatement();
        }

        $this->consume(TokenType::T_RIGHT_BRACE, "Expect '}' after block.");

        return new Program($statements);
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
    private function parseBreakStatement(Token $token): BreakStatement
    {
        $level = 1;

        if ($this->check(TokenType::T_NUMBER)) {
            $levelToken = $this->advance();
            $levelValue = $levelToken->value;

            if (! is_numeric($levelValue) || str_contains((string) $levelValue, '.')) {
                throw new ParseException(
                    'Break level must be a positive integer (got ' . $levelValue . ')',
                    $levelToken
                );
            }

            $level = (int) $levelValue;

            if ($level < 1) {
                throw new ParseException(
                    'Break level must be a positive integer (got ' . $level . ')',
                    $levelToken
                );
            }
        } elseif (! $this->check(TokenType::T_SEMICOLON)) {
            // If next token is not a number and not a semicolon, it's an error
            $unexpectedToken = $this->peek();
            throw new ParseException(
                'Unexpected token: ' . $unexpectedToken->type->value,
                $unexpectedToken
            );
        }

        return new BreakStatement($level, $token);
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
        $left = $this->parseComparison();

        if ($this->match(TokenType::T_EQUALS)) {
            $token = $this->previous();
            if (! $left instanceof Variable && ! $left instanceof MemberAccess && ! $left instanceof ArrayAccess) {
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
    private function parseComparison(): Node
    {
        $node = $this->parseAdditive();

        while ($this->match(TokenType::T_COMPARE_EQUALS, TokenType::T_COMPARE_UNEQUALS, TokenType::T_GREATER_THAN, TokenType::T_LESS_THAN)) {
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
        $node = $this->parseUnary();

        while ($this->match(TokenType::T_MULTIPLY, TokenType::T_DIVIDE)) {
            $token = $this->previous();
            $operator = $this->previous()->type;
            $right = $this->parseUnary();
            $node = new BinaryOperation($node, $operator, $right, $token);
        }

        return $node;
    }

    /**
     * @throws \PhpScript\Exceptions\ParseException
     */
    private function parseUnary(): Node
    {
        if ($this->match(TokenType::T_BANG, TokenType::T_MINUS)) {
            $token = $this->previous();
            $operator = $this->previous()->type;
            $right = $this->parseUnary();

            return new UnaryOperation($operator, $right, $token);
        }

        return $this->parsePostfix();
    }

    /**
     * @throws \PhpScript\Exceptions\ParseException
     */
    private function parsePostfix(): Node
    {
        $node = $this->parseCall();

        if ($this->match(TokenType::T_INCREMENT, TokenType::T_DECREMENT)) {
            $token = $this->previous();
            $operator = $this->previous()->type;

            return new PostfixOperation($node, $operator, $token);
        }

        return $node;
    }

    /**
     * @throws \PhpScript\Exceptions\ParseException
     */
    private function parseCall(): Node
    {
        $expr = $this->parsePrimary();

        while (true) {
            if ($this->match(TokenType::T_LEFT_PARENTHESIS)) {
                $expr = $this->finishCall($expr);
            } elseif ($this->match(TokenType::T_DOT)) {
                $propertyToken = $this->consume(TokenType::T_IDENTIFIER, 'Expected identifier after .');
                $property = new Identifier($propertyToken->value, $propertyToken);
                $expr = new MemberAccess($expr, $property, $propertyToken);
            } elseif ($this->match(TokenType::T_LEFT_BRACKET)) {
                $key = $this->parseExpression();
                $this->consume(TokenType::T_RIGHT_BRACKET, "Expect ']' after array key.");
                $expr = new ArrayAccess($expr, $key);
            } else {
                break;
            }
        }

        return $expr;
    }

    /**
     * @throws \PhpScript\Exceptions\ParseException
     */
    private function finishCall(Node $callee): FunctionCall
    {
        if ($callee instanceof Variable) {
            $callee = new Identifier($callee->name, $callee->getToken());
        }

        $arguments = [];
        if (! $this->check(TokenType::T_RIGHT_PARENTHESIS)) {
            do {
                $arguments[] = $this->parseExpression();
            } while ($this->match(TokenType::T_COMMA));
        }

        $token = $this->consume(TokenType::T_RIGHT_PARENTHESIS, "Expect ')' after arguments.");

        return new FunctionCall($callee, $arguments, $token);
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

        if ($this->match(TokenType::T_TRUE)) {
            return new Literal(true, $token);
        }

        if ($this->match(TokenType::T_FALSE)) {
            return new Literal(false, $token);
        }

        if ($this->match(TokenType::T_LINEBREAK)) {
            return new Literal(PHP_EOL, $token);
        }

        if ($this->match(TokenType::T_IDENTIFIER)) {
            return new Variable($this->previous()->value, $token);
        }

        if ($this->match(TokenType::T_LEFT_PARENTHESIS)) {
            $node = $this->parseExpression();
            $this->consume(TokenType::T_RIGHT_PARENTHESIS, "Expect ')' after expression.");

            return $node;
        }

        throw new ParseException('Unexpected token: ' . $this->peek()->type->value, $this->peek());
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
