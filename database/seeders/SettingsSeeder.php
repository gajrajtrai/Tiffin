<?php

namespace Database\Seeders;

use App\Modules\Core\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // ─── General ───────────────────────────────────────────────
            ['restaurant_name',   'Tamkulay Tiffins',       'string',  'general', 'Restaurant Name'],
            ['restaurant_mobile', '+975 17 123 456',        'string',  'general', 'Contact Mobile'],
            ['restaurant_address','Near College Gate, Thimphu', 'string', 'general', 'Address'],
            ['currency',          'Nu.',                    'string',  'general', 'Currency Symbol'],

            // ─── Ordering ──────────────────────────────────────────────
            ['order_cutoff_time',        '11:00',           'string',  'order', 'Delivery Order Cut-off Time'],
            ['delivery_slot_label',      '11:00 AM – 2:00 PM', 'string', 'order', 'Delivery Slot Label'],
            ['delivery_capacity',        100,               'integer', 'order', 'Max Delivery Orders per Day'],
            ['max_mains_per_day',        2,                 'integer', 'order', 'Max Main Courses per Day'],

            // ─── Wallet & Payment ──────────────────────────────────────
            ['low_balance_threshold', 500,  'decimal', 'payment', 'Low Balance Warning Threshold'],
            ['wallet_min_topup',      500,  'decimal', 'payment', 'Minimum Wallet Top-up'],
            ['wallet_max_topup',      5000, 'decimal', 'payment', 'Maximum Wallet Top-up'],

            // ─── Tax ───────────────────────────────────────────────────
            ['tax_label', 'GST', 'string',  'payment', 'Tax Label'],
            ['tax_rate',  5,     'decimal', 'payment', 'Tax Rate (%)'],
        ];

        foreach ($settings as [$key, $value, $type, $group, $label]) {
            Setting::updateOrCreate(
                ['key' => $key],
                [
                    'value'       => (string) $value,
                    'type'        => $type,
                    'group'       => $group,
                    'label'       => $label,
                ]
            );
        }

        $this->command->info('✓ Seeded '.count($settings).' settings.');
    }
}