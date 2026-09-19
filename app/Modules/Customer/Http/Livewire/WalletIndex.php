<?php

namespace App\Modules\Customer\Http\Livewire;

use App\Modules\Core\Models\Setting;
use App\Modules\Payment\Models\PaymentProof;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.public')]
class WalletIndex extends Component
{
    use WithFileUploads;

    public string $claimedAmount = '';
    public string $bankReference = '';
    public string $note = '';
    public $screenshot = null;

    public ?string $statusMessage = null;
    public ?string $statusType = null;

    public function mount(): void
    {
        if (! auth()->check()) {
            $this->redirect(route('login'));
        }

        $this->claimedAmount = (string) Setting::get('wallet_min_topup', 500);
    }

    protected function rules(): array
    {
        $min = (float) Setting::get('wallet_min_topup', 500);
        $max = (float) Setting::get('wallet_max_topup', 5000);

        return [
            'claimedAmount' => "required|numeric|min:{$min}|max:{$max}",
            'bankReference' => 'nullable|string|max:100',
            'note'          => 'nullable|string|max:500',
            'screenshot'    => 'required|image|max:5120',
        ];
    }

    protected function messages(): array
    {
        $min = (float) Setting::get('wallet_min_topup', 500);
        $max = (float) Setting::get('wallet_max_topup', 5000);

        return [
            'claimedAmount.required' => 'Enter the amount you transferred.',
            'claimedAmount.min'      => 'Minimum top-up is Nu. '.number_format($min, 0).'.',
            'claimedAmount.max'      => 'Maximum top-up is Nu. '.number_format($max, 0).'.',
            'screenshot.required'    => 'Please attach the payment screenshot.',
            'screenshot.image'       => 'The file must be an image (JPEG, PNG, WebP).',
            'screenshot.max'         => 'Image must be smaller than 5 MB.',
        ];
    }

    public function submit(): void
    {
        $this->validate();

        $proof = PaymentProof::create([
            'user_id'        => auth()->id(),
            'claimed_amount' => $this->claimedAmount,
            'bank_reference' => $this->bankReference !== '' ? $this->bankReference : null,
            'note'           => $this->note !== '' ? $this->note : null,
            'status'         => PaymentProof::STATUS_PENDING,
        ]);

        $proof->addMedia($this->screenshot)
              ->usingFileName('topup_'.$proof->id.'_'.time().'.'.$this->screenshot->getClientOriginalExtension())
              ->toMediaCollection(PaymentProof::MEDIA_SCREENSHOT);

        $this->reset(['claimedAmount', 'bankReference', 'note', 'screenshot']);

        $this->statusType = 'success';
        $this->statusMessage = 'Payment proof submitted. We will review it and credit your wallet shortly.';
    }

    /**
     * Read the list of merchant bank accounts from settings.
     * Falls back to the legacy single-QR setting if the JSON isn't configured.
     */
    protected function bankAccounts(): array
    {
        $raw = Setting::get('bank_accounts');

        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded) && count($decoded) > 0) {
                return $decoded;
            }
        }

        if (is_array($raw) && count($raw) > 0) {
            return $raw;
        }

        // Legacy fallback: single QR from the older setting
        $legacyQr = Setting::get('wallet_qr_image_path');
        if ($legacyQr) {
            return [[
                'bank_name'      => Setting::get('bank_name', 'Bank'),
                'short_name'     => Setting::get('bank_name', 'Bank'),
                'account_name'   => Setting::get('bank_account_name', ''),
                'account_number' => Setting::get('bank_account_number', ''),
                'qr_image'       => $legacyQr,
            ]];
        }

        return [];
    }

    public function render(): View
    {
        $user = auth()->user();

        $proofs = $user->paymentProofs()
            ->with('media')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $settings = [
            'banks'                 => $this->bankAccounts(),
            'min_topup'             => (float) Setting::get('wallet_min_topup', 500),
            'max_topup'             => (float) Setting::get('wallet_max_topup', 5000),
            'low_balance_threshold' => (float) $user->low_balance_threshold,
        ];

        return view('public.wallet', compact('user', 'proofs', 'settings'));
    }
}