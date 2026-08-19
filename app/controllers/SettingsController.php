<?php

class SettingsController
{
    public function dropdowns(): void
    {
        Auth::requireLogin();
        render('settings/dropdowns', [
            'title'      => 'Settings — Dropdown Options',
            'grouped'    => DropdownOption::grouped(),
            'categories' => DropdownOption::CATEGORIES,
        ]);
    }

    public function storeOption(): void
    {
        Auth::requireLogin();
        Csrf::verify();
        $category = post('category');
        $value = post('value');
        if (!$category || !isset(DropdownOption::CATEGORIES[$category])) {
            flash('error', 'Unknown dropdown category.');
        } elseif (!$value) {
            flash('error', 'Enter a value to add.');
        } else {
            DropdownOption::add($category, $value);
            flash('success', "Added \"{$value}\" to " . DropdownOption::CATEGORIES[$category] . '.');
        }
        redirect('/settings/dropdowns');
    }

    public function updateOption(string $id): void
    {
        Auth::requireLogin();
        Csrf::verify();
        $order = post_num('display_order');
        if ($order !== null) {
            DropdownOption::setOrder((int) $id, (int) $order);
            flash('success', 'Order updated.');
        }
        redirect('/settings/dropdowns');
    }

    public function toggleOption(string $id): void
    {
        Auth::requireLogin();
        Csrf::verify();
        DropdownOption::toggle((int) $id);
        flash('success', 'Option updated.');
        redirect('/settings/dropdowns');
    }
}
