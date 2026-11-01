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
use PhpScript\Contracts\Node;
use PhpScript\Contracts\ParserInterface;
use PhpScript\Exceptions\ParseException;

final class Parser implements ParserInterface
{
    /**
     * Deepest nesting of blocks and expressions. Deeper scripts would exhaust the
     * PHP stack while parsing or compiling.
     */
    public const int MAX_DEPTH = 256;

    private int $depth = 0;

    /**
     * @var Token[]
     */
    private array $tokens;

    private int $position = 0;

    /**
     * In expression mode only side-effect free expressions are accepted:
     * no statements, no assignments, no increments or decrements.
     */
    private bool $expressionMode = false;

    /**
     * @param  Token[]  $tokens
     *
     * @throws ParseException
     */
    public function parse(array $tokens): Node
    {
        $this->reset($tokens, expressionMode: false);

        $statements = [];
        while (! $this->isAtEnd()) {
            $statements[] = $this->parseStatement();
        }

        return new Program($statements);
    }

    /**
     * @param  Token[]  $tokens
     *
     * @throws ParseException
     */
    public function parseExpression(array $tokens): Node
    {
        $this->reset($tokens, expressionMode: true);

        if ($this->isAtEnd()) {
            throw new ParseException('Expected an expression.', null);
        }

        $node = $this->parseExpressionNode();

        if ($this->position < count($this->tokens)) {
            throw new ParseException('Unexpected token: ' . $this->peek()->type->value . ', expected end of expression.', $this->peek());
        }

        return $node;
    }

    /**
     * @param  Token[]  $tokens
     */
    private function reset(array $tokens, bool $expressionMode): void
    {
        $this->tokens = array_values(
            array_filter(
                $tokens,
                static fn (Token $token): bool => ! in_array($token->type, [TokenType::T_WHITESPACE, TokenType::T_COMMENT], true)
            )
        );
        $this->position = 0;
        $this->depth = 0;
        $this->expressionMode = $expressionMode;
    }

    /**
     * Goes one level deeper and returns the previous depth for leave().
     *
     * @throws ParseException
     */
    private function enter(): int
    {
        if (++$this->depth > self::MAX_DEPTH) {
            throw new ParseException('Script is nested too deeply.', $this->isAtEnd() ? $this->previous() : $this->peek());
        }

        return $this->depth - 1;
    }

    /**
     * @throws ParseException
     */
    private function parseStatement(): Node
    {
        $depth = $this->enter();
        $node = $this->parseStatementNode();
        $this->depth = $depth;

        return $node;
    }

    /**
     * @throws ParseException
     */
    private function parseStatementNode(): Node
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

        if ($this->match(TokenType::T_CONTINUE)) {
            $node = $this->parseContinueStatement($token);
            $this->match(TokenType::T_SEMICOLON);

            return $node;
        }

        if ($this->match(TokenType::T_RETURN)) {
            $node = $this->parseReturnStatement($token);
            $this->match(TokenType::T_SEMICOLON);

            return $node;
        }

        if ($this->match(TokenType::T_SEMICOLON)) {
            return new NoOp($token);
        }

        $node = $this->parseExpressionNode();

        $this->match(TokenType::T_SEMICOLON);

        return $node;
    }

    /**
     * @throws ParseException
     */
    private function parseForStatement(Token $token): ForStatement
    {
        $this->consume(TokenType::T_LEFT_PARENTHESIS, "Expect '(' after 'for'.");

        $initializer = null;
        if (! $this->check(TokenType::T_SEMICOLON)) {
            $initializer = $this->parseExpressionNode();
        }
        $this->consume(TokenType::T_SEMICOLON, "Expect ';' after loop initializer.");

        $condition = null;
        if (! $this->check(TokenType::T_SEMICOLON)) {
            $condition = $this->parseExpressionNode();
        }
        $this->consume(TokenType::T_SEMICOLON, "Expect ';' after loop condition.");

        $increment = null;
        if (! $this->check(TokenType::T_RIGHT_PARENTHESIS)) {
            $increment = $this->parseExpressionNode();
        }
        $this->consume(TokenType::T_RIGHT_PARENTHESIS, "Expect ')' after for clauses.");

        $body = $this->parseBlock();

        return new ForStatement($initializer, $condition, $increment, $body, $token);
    }

    /**
     * @throws ParseException
     */
    private function parseForeachStatement(Token $token): ForeachStatement
    {
        $this->consume(TokenType::T_LEFT_PARENTHESIS, "Expect '(' after 'foreach'.");
        $iterable = $this->parseExpressionNode();
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
     * @throws ParseException
     */
    private function parseIfStatement(Token $token): IfStatement
    {
        $this->consume(TokenType::T_LEFT_PARENTHESIS, "Expect '(' after 'if'.");
        $condition = $this->parseExpressionNode();
        $this->consume(TokenType::T_RIGHT_PARENTHESIS, "Expect ')' after if condition.");

        $thenBranch = $this->parseBlock();
        $elseBranch = null;

        if ($this->match(TokenType::T_ELSE)) {
            $elseBranch = $this->parseBlock();
        }

        return new IfStatement($condition, $thenBranch, $elseBranch, $token);
    }

    /**
     * @throws ParseException
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
     * @throws ParseException
     */
    private function parseEchoStatement(Token $token): EchoStatement
    {
        $expression = $this->parseExpressionNode();

        return new EchoStatement($expression, $token);
    }

    /**
     * @throws ParseException
     */
    private function parseReturnStatement(Token $token): ReturnStatement
    {
        if ($this->endsStatement($token)) {
            return new ReturnStatement(null, $token);
        }

        return new ReturnStatement($this->parseExpressionNode(), $token);
    }

    /**
     * @throws ParseException
     */
    private function parseBreakStatement(Token $token): BreakStatement
    {
        $level = 1;

        if ($this->check(TokenType::T_NUMBER) && $this->peek()->line === $token->line) {
            $levelToken = $this->advance();
            $levelValue = $levelToken->value;

            if (! is_numeric($levelValue) || str_contains($levelValue, '.')) {
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
        } elseif (! $this->endsStatement($token)) {
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
     * @throws ParseException
     */
    private function parseContinueStatement(Token $token): ContinueStatement
    {
        $level = 1;

        if ($this->check(TokenType::T_NUMBER) && $this->peek()->line === $token->line) {
            $levelToken = $this->advance();
            $levelValue = $levelToken->value;

            if (! is_numeric($levelValue) || str_contains($levelValue, '.')) {
                throw new ParseException(
                    'Continue level must be a positive integer (got ' . $levelValue . ')',
                    $levelToken
                );
            }

            $level = (int) $levelValue;

            if ($level < 1) {
                throw new ParseException(
                    'Continue level must be a positive integer (got ' . $level . ')',
                    $levelToken
                );
            }
        } elseif (! $this->endsStatement($token)) {
            // If next token is not a number and not a semicolon, it's an error
            $unexpectedToken = $this->peek();
            throw new ParseException(
                'Unexpected token: ' . $unexpectedToken->type->value,
                $unexpectedToken
            );
        }

        return new ContinueStatement($level, $token);
    }

    /**
     * A statement ends at `;`, `}`, the end of the script or a line break.
     */
    private function endsStatement(Token $keyword): bool
    {
        if ($this->isAtEnd()) {
            return true;
        }
        if ($this->check(TokenType::T_SEMICOLON)) {
            return true;
        }
        if ($this->check(TokenType::T_RIGHT_BRACE)) {
            return true;
        }

        return $this->peek()->line !== $keyword->line;
    }

    /**
     * @throws ParseException
     */
    private function parseExpressionNode(): Node
    {
        $depth = $this->enter();
        $node = $this->parseAssignment();
        $this->depth = $depth;

        return $node;
    }

    /**
     * @throws ParseException
     */
    private function parseAssignment(): Node
    {
        $left = $this->parseLogicalOr();

        if ($this->match(TokenType::T_EQUALS)) {
            $token = $this->previous();
            if ($this->expressionMode) {
                throw new ParseException('Assignments are not allowed in expressions.', $token);
            }
            if (! $this->isAssignable($left)) {
                throw new ParseException('Invalid assignment target.', $token);
            }

            return new Assignment($left, $this->parseExpressionNode(), $token);
        }

        return $left;
    }

    /**
     * Only a variable or a path below it (`a.b[c]`) can be written to.
     *
     * @phpstan-assert-if-true ArrayAccess|MemberAccess|Variable $node
     */
    private function isAssignable(Node $node): bool
    {
        while ($node instanceof MemberAccess || $node instanceof ArrayAccess) {
            $node = $node instanceof MemberAccess ? $node->object : $node->array;
        }

        return $node instanceof Variable;
    }

    /**
     * Parses a left-associative chain like `a + b - c`. Every operator counts as
     * one level of nesting, because the tree grows one level per operator.
     *
     * @param  callable(): Node  $operand
     *
     * @throws ParseException
     */
    private function parseBinary(callable $operand, TokenType ...$operators): Node
    {
        $depth = $this->depth;
        $node = $operand();

        while ($this->match(...$operators)) {
            $token = $this->previous();
            $this->enter();
            $node = new BinaryOperation($node, $token->type, $operand(), $token);
        }

        $this->depth = $depth;

        return $node;
    }

    /**
     * @throws ParseException
     */
    private function parseLogicalOr(): Node
    {
        return $this->parseBinary($this->parseLogicalAnd(...), TokenType::T_OR);
    }

    /**
     * @throws ParseException
     */
    private function parseLogicalAnd(): Node
    {
        return $this->parseBinary($this->parseEquality(...), TokenType::T_AND);
    }

    /**
     * @throws ParseException
     */
    private function parseEquality(): Node
    {
        return $this->parseBinary($this->parseRelational(...), TokenType::T_COMPARE_EQUALS, TokenType::T_COMPARE_UNEQUALS);
    }

    /**
     * `<` binds tighter than `==`, so `a < b == c < d` compares two booleans.
     *
     * @throws ParseException
     */
    private function parseRelational(): Node
    {
        return $this->parseBinary(
            $this->parseAdditive(...),
            TokenType::T_GREATER_THAN,
            TokenType::T_LESS_THAN,
            TokenType::T_GREATER_EQUAL,
            TokenType::T_LESS_EQUAL,
        );
    }

    /**
     * @throws ParseException
     */
    private function parseAdditive(): Node
    {
        return $this->parseBinary($this->parseMultiplicative(...), TokenType::T_PLUS, TokenType::T_MINUS);
    }

    /**
     * @throws ParseException
     */
    private function parseMultiplicative(): Node
    {
        return $this->parseBinary($this->parseUnary(...), TokenType::T_MULTIPLY, TokenType::T_DIVIDE, TokenType::T_MODULO);
    }

    /**
     * @throws ParseException
     */
    private function parseUnary(): Node
    {
        if ($this->match(TokenType::T_BANG, TokenType::T_MINUS)) {
            $token = $this->previous();
            $depth = $this->enter();
            $right = $this->parseUnary();
            $this->depth = $depth;

            return new UnaryOperation($token->type, $right, $token);
        }

        return $this->parsePostfix();
    }

    /**
     * @throws ParseException
     */
    private function parsePostfix(): Node
    {
        $node = $this->parseCall();

        if ($this->match(TokenType::T_INCREMENT, TokenType::T_DECREMENT)) {
            $token = $this->previous();
            if ($this->expressionMode) {
                throw new ParseException('Increment and decrement are not allowed in expressions.', $token);
            }
            if (! $this->isAssignable($node)) {
                throw new ParseException('Invalid increment or decrement target.', $token);
            }

            return new PostfixOperation($node, $token->type, $token);
        }

        return $node;
    }

    /**
     * @throws ParseException
     */
    private function parseCall(): Node
    {
        $depth = $this->depth;
        $expr = $this->parsePrimary();

        while (true) {
            $this->enter();
            if ($this->match(TokenType::T_LEFT_PARENTHESIS)) {
                $expr = $this->finishCall($expr);
            } elseif ($this->match(TokenType::T_DOT)) {
                $propertyToken = $this->consume(TokenType::T_IDENTIFIER, 'Expected identifier after .');
                $property = new Identifier($propertyToken->value, $propertyToken);
                $expr = new MemberAccess($expr, $property, $propertyToken);
            } elseif ($this->match(TokenType::T_LEFT_BRACKET)) {
                $key = $this->parseExpressionNode();
                $this->consume(TokenType::T_RIGHT_BRACKET, "Expect ']' after array key.");
                $expr = new ArrayAccess($expr, $key);
            } else {
                break;
            }
        }

        $this->depth = $depth;

        return $expr;
    }

    /**
     * @throws ParseException
     */
    private function finishCall(Node $callee): FunctionCall
    {
        if ($callee instanceof Variable) {
            $callee = new Identifier($callee->name, $callee->getToken());
        }

        $arguments = [];
        if (! $this->check(TokenType::T_RIGHT_PARENTHESIS)) {
            do {
                $arguments[] = $this->parseExpressionNode();
            } while ($this->match(TokenType::T_COMMA));
        }

        $token = $this->consume(TokenType::T_RIGHT_PARENTHESIS, "Expect ')' after arguments.");

        return new FunctionCall($callee, $arguments, $token);
    }

    /**
     * @throws ParseException
     */
    private function parsePrimary(): Node
    {
        if ($this->isAtEnd()) {
            throw new ParseException('Unexpected end of input.', $this->tokens === [] ? null : $this->previous());
        }

        $token = $this->peek();
        if ($this->match(TokenType::T_NUMBER)) {
            $value = $this->previous()->value;

            if (str_contains($value, '.')) {
                if (! is_finite((float) $value)) {
                    throw new ParseException(sprintf('Number %s is too large.', $value), $token);
                }

                return new Literal((float) $value, $token);
            }

            $digits = ltrim($value, '0');
            $integer = $digits === '' ? 0 : filter_var($digits, FILTER_VALIDATE_INT);
            if ($integer === false) {
                throw new ParseException(sprintf('Number %s is too large.', $value), $token);
            }

            return new Literal($integer, $token);
        }

        if ($this->match(TokenType::T_STRING)) {
            return new Literal($this->previous()->value, $token);
        }

        if ($this->match(TokenType::T_NULL)) {
            return new Literal(null, $token);
        }

        if ($this->match(TokenType::T_LEFT_BRACKET)) {
            return $this->parseListLiteral($token);
        }

        if ($this->match(TokenType::T_LEFT_BRACE)) {
            return $this->parseMapLiteral($token);
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
            $node = $this->parseExpressionNode();
            $this->consume(TokenType::T_RIGHT_PARENTHESIS, "Expect ')' after expression.");

            return $node;
        }

        throw new ParseException('Unexpected token: ' . $this->peek()->type->value, $this->peek());
    }

    /**
     * @throws ParseException
     */
    private function parseListLiteral(Token $token): ListLiteral
    {
        $elements = [];
        while (! $this->check(TokenType::T_RIGHT_BRACKET)) {
            $elements[] = $this->parseExpressionNode();
            if (! $this->match(TokenType::T_COMMA)) {
                break;
            }
        }

        $this->consume(TokenType::T_RIGHT_BRACKET, "Expect ']' after list elements.");

        return new ListLiteral($elements, $token);
    }

    /**
     * @throws ParseException
     */
    private function parseMapLiteral(Token $token): MapLiteral
    {
        $entries = [];
        while (! $this->check(TokenType::T_RIGHT_BRACE)) {
            if ($this->isAtEnd() || ! $this->match(TokenType::T_IDENTIFIER, TokenType::T_STRING)) {
                throw new ParseException('Map keys must be identifiers or strings.', $this->isAtEnd() ? $this->previous() : $this->peek());
            }

            $key = $this->previous()->value;
            $this->consume(TokenType::T_COLON, "Expect ':' after map key.");
            $entries[] = [$key, $this->parseExpressionNode()];

            if (! $this->match(TokenType::T_COMMA)) {
                break;
            }
        }

        $this->consume(TokenType::T_RIGHT_BRACE, "Expect '}' after map entries.");

        return new MapLiteral($entries, $token);
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
     * @throws ParseException
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
