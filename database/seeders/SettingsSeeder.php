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
            ['restaurant_mobile', '+975 17123456',          'string',  'general', 'Contact Mobile'],
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
			// ─── Bank details for customer top-up ─────────────────────
            ['bank_name',           'Bank of Bhutan',    'string', 'payment', 'Bank Name'],
            ['bank_account_name',   'Tamkulay Tiffins',  'string', 'payment', 'Account Holder Name'],
            ['bank_account_number', '1234567890',        'string', 'payment', 'Account Number'],
            ['wallet_qr_image_path','',                  'string', 'payment', 'Merchant QR Image (filename in /public/qr/)'],

            // ─── Tax ───────────────────────────────────────────────────
            ['tax_label', 'GST', 'string',  'payment', 'Tax Label'],
            ['tax_rate',  5,     'decimal', 'payment', 'Tax Rate (%)'],
			
            // ─── Landing Page ──────────────────────────────────────────
            [
                'landing_hero_title',
                'Fresh lunch from Tamkulay Tiffins',
                'string',
                'landing',
                'Hero headline',
            ],
            [
                'landing_hero_subtitle',
                'Home-style meals delivered to your college gate or ready for pickup at our counter. Prepaid wallet, no queues, no fuss.',
                'string',
                'landing',
                'Hero subtitle',
            ],
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