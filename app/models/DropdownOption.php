<?php

class DropdownOption
{
    public const CATEGORIES = [
        'employment_status'  => 'Employment Status',
        'payment_method'     => 'Payment Method',
        'expense_type'       => 'Expense Type',
        'reason_for_payment' => 'Reason for Payment',
    ];

    /** Active values for a category, in display order. */
    public static function values(string $category): array
    {
        return array_column(
            Database::all(
                'SELECT value FROM dropdown_options
                 WHERE category = :c AND is_active = TRUE
                 ORDER BY display_order, value',
                ['c' => $category]
            ),
            'value'
        );
    }

    /** All options (active + inactive) grouped by category, for the settings page. */
    public static function grouped(): array
    {
        $rows = Database::all('SELECT * FROM dropdown_options ORDER BY category, display_order, value');
        $out = [];
        foreach ($rows as $r) {
            $out[$r['category']][] = $r;
        }
        return $out;
    }

    public static function add(string $category, string $value): void
    {
        $max = (int) Database::scalar(
            'SELECT COALESCE(MAX(display_order), 0) FROM dropdown_options WHERE category = :c',
            ['c' => $category]
        );
        Database::q(
            'INSERT INTO dropdown_options (category, value, display_order)
             VALUES (:c, :v, :o) ON CONFLICT (category, value) DO UPDATE SET is_active = TRUE',
            ['c' => $category, 'v' => $value, 'o' => $max + 1]
        );
    }

    public static function setOrder(int $id, int $order): void
    {
        Database::q('UPDATE dropdown_options SET display_order = :o WHERE id = :id', ['o' => $order, 'id' => $id]);
    }

    public static function toggle(int $id): void
    {
        Database::q('UPDATE dropdown_options SET is_active = NOT is_active WHERE id = :id', ['id' => $id]);
    }
}
