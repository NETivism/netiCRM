<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Utils;

use PhpMyAdmin\SqlParser\Statements\CreateStatement;

use function is_array;

/**
 * Table utilities.
 */
class Table
{
    /**
     * Gets fields of the table.
     *
     * @param CreateStatement $statement the statement to be processed
     *
     * @return array<int|string, array<string, bool|string|mixed>>
     * @psalm-return array<string, array{
     *  type: string,
     *  timestamp_not_null: bool,
     *  default_value?: mixed,
     *  default_current_timestamp?: true,
     *  on_update_current_timestamp?: true,
     *  expr?: mixed
     * }>
     */
    public static function getFields(CreateStatement $statement): array
    {
        if (empty($statement->fields) || ! is_array($statement->fields) || ! $statement->options->has('TABLE')) {
            return [];
        }

        $ret = [];

        foreach ($statement->fields as $field) {
            // Skipping keys.
            if (empty($field->type)) {
                continue;
            }

            $ret[$field->name] = [
                'type' => $field->type->name,
                'timestamp_not_null' => false,
            ];

            if (! $field->options) {
                continue;
            }

            if ($field->type->name === 'TIMESTAMP') {
                if ($field->options->has('NOT NULL')) {
                    $ret[$field->name]['timestamp_not_null'] = true;
                }
            }

            $option = $field->options->get('DEFAULT');

            if ($option !== '') {
                $ret[$field->name]['default_value'] = $option;
                if ($option === 'CURRENT_TIMESTAMP') {
                    $ret[$field->name]['default_current_timestamp'] = true;
                }
            }

            if ($field->options->get('ON UPDATE') === 'CURRENT_TIMESTAMP') {
                $ret[$field->name]['on_update_current_timestamp'] = true;
            }

            $option = $field->options->get('AS');

            if ($option === '') {
                continue;
            }

            $ret[$field->name]['expr'] = $option;
        }

        return $ret;
    }
}
