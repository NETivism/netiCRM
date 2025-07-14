<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Statements;

use PhpMyAdmin\SqlParser\Exceptions\ParserException;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Parsers\OptionsArrays;
use PhpMyAdmin\SqlParser\Statement;
use PhpMyAdmin\SqlParser\TokensList;
use PhpMyAdmin\SqlParser\TokenType;

use function array_slice;
use function is_int;

/** KILL [HARD|SOFT]
 * {
 * {CONNECTION|QUERY} id |
 * QUERY ID query_id | USER user_name
 * }
 */
class KillStatement extends Statement
{
    /**
     * Options of this statement.
     *
     * @var array<string, int|array<int, int|string>>
     * @psalm-var array<string, (positive-int|array{positive-int, ('var'|'var='|'expr'|'expr=')})>
     */
    public static array $statementOptions = [
        'HARD' => 1,
        'SOFT' => 1,
        'CONNECTION' => 2,
        'QUERY' => 2,
        'USER' => 2,
    ];

    /**
     * Holds the identifier if explicitly set
     */
    public Statement|int|null $identifier = null;

    /**
     * Whether MariaDB ID keyword is used or not.
     */
    public bool $idKeywordUsed = false;

    /**
     * Whether parenthesis used around the identifier or not
     */
    public bool $parenthesisUsed = false;

    /** @throws ParserException */
    public function parse(Parser $parser, TokensList $list): void
    {
        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 --------------------- [ OPTIONS PARSED ] --------------------------> 0
         *
         *      0 -------------------- [ number ] -----------------------------------> 2
         *
         *      0 -------------------- [ ( ] ----------------------------------------> 3
         *
         *      0 -------------------- [ QUERY ID ] ---------------------------------> 0
         *
         *      3 -------------------- [ number ] -----------------------------------> 3
         *
         *      3 -------------------- [ SELECT STATEMENT ] -------------------------> 2
         *
         *      3 -------------------- [ ) ] ----------------------------------------> 2
         *
         *      2 ----------------------------------------------------------> Final state
         */
        $state = 0;

        ++$list->idx; // Skipping `KILL`.
        $this->options = OptionsArrays::parse($parser, $list, static::$statementOptions);
        ++$list->idx;
        for (; $list->idx < $list->count; ++$list->idx) {
            $token = $list->tokens[$list->idx];

            if ($token->type === TokenType::Whitespace || $token->type === TokenType::Comment) {
                continue;
            }

            switch ($state) {
                case 0:
                    $currIdx = $list->idx;
                    $prev = $list->getPreviousOfType(TokenType::Keyword);
                    $list->idx = $currIdx;
                    if ($token->type === TokenType::Number && is_int($token->value)) {
                        $this->identifier = $token->value;
                        $state = 2;
                    } elseif ($token->type === TokenType::Operator && $token->value === '(') {
                        $this->parenthesisUsed = true;
                        $state = 3;
                    } elseif ($prev && $token->value === 'ID' && $prev->value === 'QUERY') {
                        $this->idKeywordUsed = true;
                        $state = 0;
                    } else {
                        $parser->error('Unexpected token.', $token);
                        break 2;
                    }

                    break;

                case 3:
                    if ($token->type === TokenType::Keyword && $token->value === 'SELECT') {
                        $subList = new TokensList(array_slice($list->tokens, $list->idx - 1));
                        $subParser = new Parser($subList);
                        if ($subParser->errors !== []) {
                            foreach ($subParser->errors as $error) {
                                $parser->errors[] = $error;
                            }

                            break;
                        }

                        $this->identifier = $subParser->statements[0];
                        $state = 2;
                    } elseif ($token->type === TokenType::Operator && $token->value === ')') {
                        $state = 2;
                    } elseif ($token->type === TokenType::Number && is_int($token->value)) {
                        $this->identifier = $token->value;
                        $state = 3;
                    } else {
                        $parser->error('Unexpected token.', $token);
                        break 2;
                    }

                    break;
            }
        }

        if ($state !== 2) {
            $token = $list->tokens[$list->idx];
            $parser->error('Unexpected end of the KILL statement.', $token);
        }

        --$list->idx;
    }

    public function build(): string
    {
        $ret = 'KILL';

        if ($this->options !== null && $this->options->options !== []) {
            $ret .= ' ' . $this->options->build();
        }

        if ($this->idKeywordUsed) {
            $ret .= ' ID';
        }

        $identifier = (string) $this->identifier;
        if ($this->parenthesisUsed) {
            $ret .= ' (' . $identifier . ')';
        } else {
            $ret .= ' ' . $identifier;
        }

        return $ret;
    }
}
