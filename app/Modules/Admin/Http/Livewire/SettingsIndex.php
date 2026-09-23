<?php

namespace App\Modules\Admin\Http\Livewire;

use App\Modules\Core\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.admin')]
class SettingsIndex extends Component
{
    use WithFileUploads;

    public string $activeTab = 'general';

    // General
    public string $restaurant_name = '';
    public string $restaurant_mobile = '';
    public string $restaurant_address = '';

    // Ordering
    public string $order_cutoff_time = '11:00';
    public string $delivery_slot_label = '';
    public int $delivery_capacity = 100;
    public int $max_mains_per_day = 2;

    // Wallet
    public string $wallet_min_topup = '500';
    public string $wallet_max_topup = '5000';
    public string $low_balance_threshold = '500';

    // Landing
    public string $landing_hero_title = '';
    public string $landing_hero_subtitle = '';

    // Banks
    /** @var array<int, array{bank_name:string, short_name:string, account_name:string, qr_image:string}> */
    public array $banks = [];

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null> */
    public array $qrUploads = [];

    public ?string $statusMessage = null;
    public ?string $statusType = null;

    public function mount(): void
    {
        if (! auth()->user()->can('settings.view')) {
            abort(403);
        }

        $this->restaurant_name = (string) Setting::get('restaurant_name', 'Tamkulay Tiffins');
        $this->restaurant_mobile = (string) Setting::get('restaurant_mobile', '');
        $this->restaurant_address = (string) Setting::get('restaurant_address', '');

        $this->order_cutoff_time = (string) Setting::get('order_cutoff_time', '11:00');
        $this->delivery_slot_label = (string) Setting::get('delivery_slot_label', '11:00 AM – 2:00 PM');
        $this->delivery_capacity = (int) Setting::get('delivery_capacity', 100);
        $this->max_mains_per_day = (int) Setting::get('max_mains_per_day', 2);

        $this->wallet_min_topup = (string) Setting::get('wallet_min_topup', '500');
        $this->wallet_max_topup = (string) Setting::get('wallet_max_topup', '5000');
        $this->low_balance_threshold = (string) Setting::get('low_balance_threshold', '500');

        $this->landing_hero_title = (string) Setting::get('landing_hero_title', 'Fresh lunch from Tamkulay Tiffins');
        $this->landing_hero_subtitle = (string) Setting::get('landing_hero_subtitle', 'Home-style meals delivered to your college gate or ready for pickup at our counter. Prepaid wallet, no queues, no fuss.');

        // Load banks from JSON setting. account_number is intentionally dropped.
        $raw = Setting::get('bank_accounts', []);
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $this->banks = is_array($decoded) ? $decoded : [];
        } elseif (is_array($raw)) {
            $this->banks = $raw;
        }

        foreach ($this->banks as $i => $bank) {
            $this->banks[$i] = [
                'bank_name'    => $bank['bank_name'] ?? '',
                'short_name'   => $bank['short_name'] ?? '',
                'account_name' => $bank['account_name'] ?? '',
                'qr_image'     => $bank['qr_image'] ?? '',
            ];
        }
    }

    public function layoutData(): array
    {
        return [
            'title'   => 'Settings',
            'heading' => 'Restaurant Settings',
        ];
    }

    public function switchTab(string $tab): void
    {
        if (in_array($tab, ['general', 'ordering', 'wallet', 'landing', 'banks'], true)) {
            $this->activeTab = $tab;
            $this->statusMessage = null;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Bank list management
    |--------------------------------------------------------------------------
    */

    public function addBank(): void
    {
        $this->banks[] = [
            'bank_name'    => '',
            'short_name'   => '',
            'account_name' => '',
            'qr_image'     => '',
        ];
    }

    public function removeBank(int $index): void
    {
        if (! isset($this->banks[$index])) {
            return;
        }

        $qr = $this->banks[$index]['qr_image'] ?? '';
        if ($qr !== '' && file_exists(public_path('qr/'.$qr))) {
            @unlink(public_path('qr/'.$qr));
        }

        unset($this->banks[$index]);
        $this->banks = array_values($this->banks);

        unset($this->qrUploads[$index]);
        $this->qrUploads = array_values($this->qrUploads);
    }

    public function removeQr(int $index): void
    {
        if (! isset($this->banks[$index])) {
            return;
        }

        $qr = $this->banks[$index]['qr_image'] ?? '';
        if ($qr !== '' && file_exists(public_path('qr/'.$qr))) {
            @unlink(public_path('qr/'.$qr));
        }

        $this->banks[$index]['qr_image'] = '';
    }

    /*
    |--------------------------------------------------------------------------
    | Save
    |--------------------------------------------------------------------------
    */

    protected function rules(): array
    {
        return [
            'restaurant_name'        => 'required|string|max:255',
            'restaurant_mobile'      => 'required|string|regex:/^\d{8}$/',
            'restaurant_address'     => 'required|string|max:255',

            'order_cutoff_time'      => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'delivery_slot_label'    => 'required|string|max:100',
            'delivery_capacity'      => 'required|integer|min:1|max:10000',
            'max_mains_per_day'      => 'required|integer|min:1|max:10',

            'wallet_min_topup'       => 'required|numeric|min:1|max:100000',
            'wallet_max_topup'       => 'required|numeric|min:1|max:1000000|gte:wallet_min_topup',
            'low_balance_threshold'  => 'required|numeric|min:0|max:1000000',

            'landing_hero_title'     => 'required|string|max:255',
            'landing_hero_subtitle'  => 'required|string|max:500',

            'banks'                  => 'array',
            'banks.*.bank_name'      => 'required|string|max:100',
            'banks.*.short_name'     => 'required|string|max:20',
            'banks.*.account_name'   => 'required|string|max:100',

            'qrUploads'              => 'array',
            'qrUploads.*'            => 'nullable|image|max:2048|mimes:jpeg,png,webp',
        ];
    }

    protected function messages(): array
    {
        return [
            'restaurant_mobile.regex'      => 'Mobile must be 8 digits (e.g. 17123456).',
            'order_cutoff_time.regex'      => 'Cut-off must be in 24-hour HH:MM format (e.g. 11:00).',
            'wallet_max_topup.gte'         => 'Maximum top-up must be at least as large as the minimum.',
            'banks.*.bank_name.required'   => 'Each bank needs a name.',
            'banks.*.short_name.required'  => 'Each bank needs a short label (shown on the customer tab).',
            'banks.*.account_name.required'=> 'Each bank needs an account holder name.',
            'qrUploads.*.image'            => 'QR must be an image (JPEG, PNG, or WebP).',
            'qrUploads.*.max'              => 'QR image must be smaller than 2 MB.',
        ];
    }

    public function save(): void
    {
        if (! auth()->user()->can('settings.edit')) {
            abort(403);
        }

        $this->validate();

        // ─── Phase 1: Persist all scalar settings in one transaction ──
        DB::transaction(function () {
            Setting::setWithType('restaurant_name', $this->restaurant_name, 'string');
            Setting::setWithType('restaurant_mobile', $this->restaurant_mobile, 'string');
            Setting::setWithType('restaurant_address', $this->restaurant_address, 'string');

            Setting::setWithType('order_cutoff_time', $this->order_cutoff_time, 'string');
            Setting::setWithType('delivery_slot_label', $this->delivery_slot_label, 'string');
            Setting::setWithType('delivery_capacity', $this->delivery_capacity, 'integer');
            Setting::setWithType('max_mains_per_day', $this->max_mains_per_day, 'integer');

            Setting::setWithType('wallet_min_topup', $this->wallet_min_topup, 'decimal');
            Setting::setWithType('wallet_max_topup', $this->wallet_max_topup, 'decimal');
            Setting::setWithType('low_balance_threshold', $this->low_balance_threshold, 'decimal');

            Setting::setWithType('landing_hero_title', $this->landing_hero_title, 'string');
            Setting::setWithType('landing_hero_subtitle', $this->landing_hero_subtitle, 'string');
        });

        // ─── Phase 2: Process QR uploads (isolated, non-fatal) ────────
        $qrFailures = [];

        try {
            $qrDir = public_path('qr');
            if (! is_dir($qrDir)) {
                if (! @mkdir($qrDir, 0755, true) && ! is_dir($qrDir)) {
                    throw new \RuntimeException("Could not create QR directory: {$qrDir}");
                }
            }

            if (! is_writable($qrDir)) {
                throw new \RuntimeException("QR directory is not writable: {$qrDir}");
            }

            foreach ($this->banks as $index => $bank) {
                if (! isset($this->qrUploads[$index]) || $this->qrUploads[$index] === null) {
                    continue;
                }

                $file = $this->qrUploads[$index];
                $bankLabel = $bank['bank_name'] ?: 'Bank #'.($index + 1);

                try {
                    $source = $file->getRealPath();

                    if ($source === false || ! is_file($source)) {
                        throw new \RuntimeException("Temporary upload file missing: {$source}");
                    }

                    $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
                    if (! in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true)) {
                        throw new \RuntimeException("Unsupported image type: {$ext}");
                    }

                    $base = Str::slug($bank['short_name'] ?: $bank['bank_name'] ?: 'bank');
                    if ($base === '') {
                        $base = 'bank';
                    }

                    $filename = $base.'-'.time().'-'.$index.'.'.$ext;
                    $destination = $qrDir.DIRECTORY_SEPARATOR.$filename;

                    $old = $bank['qr_image'] ?? '';
                    if ($old !== '' && $old !== $filename && file_exists($qrDir.DIRECTORY_SEPARATOR.$old)) {
                        @unlink($qrDir.DIRECTORY_SEPARATOR.$old);
                    }

                    $contents = @file_get_contents($source);
                    if ($contents === false) {
                        $err = error_get_last();
                        throw new \RuntimeException('Could not read temp file: '.($err['message'] ?? 'unknown error'));
                    }

                    $written = @file_put_contents($destination, $contents);
                    if ($written === false) {
                        $err = error_get_last();
                        throw new \RuntimeException('Could not write to '.$destination.': '.($err['message'] ?? 'unknown error'));
                    }

                    $this->banks[$index]['qr_image'] = $filename;

                } catch (\Throwable $e) {
                    $qrFailures[] = $bankLabel.': '.$e->getMessage();
                }
            }
        } catch (\Throwable $e) {
            $qrFailures[] = 'QR setup failed: '.$e->getMessage();
        }

        // ─── Phase 3: Persist bank JSON ──────────────────────────────
        try {
            DB::transaction(function () {
                Setting::setJson('bank_accounts', array_map(fn ($b) => [
                    'bank_name'    => $b['bank_name'],
                    'short_name'   => $b['short_name'],
                    'account_name' => $b['account_name'],
                    'qr_image'     => $b['qr_image'] ?? '',
                ], $this->banks));
            });
        } catch (\Throwable $e) {
            $qrFailures[] = 'Bank list save failed: '.$e->getMessage();
        }

        // ─── Clear uploads and re-read from DB ──────────────────────
        $this->qrUploads = [];
        $this->mount();

        if (! empty($qrFailures)) {
            $this->statusType = 'warning';
            $this->statusMessage = 'Settings saved, but some QR uploads failed: '.implode(' · ', $qrFailures);
        } else {
            $this->statusType = 'success';
            $this->statusMessage = 'Settings saved.';
        }
    }

    public function render(): View
    {
        return view('admin.settings.index');
    }
}