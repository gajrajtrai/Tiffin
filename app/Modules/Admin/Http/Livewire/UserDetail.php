<?php

namespace App\Modules\Admin\Http\Livewire;

use App\Models\User;
use App\Modules\Payment\Services\WalletService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class UserDetail extends Component
{
    use WithPagination;

    public User $user;

    // Inline status message (not session — Livewire actions don't reload the page)
    public ?string $statusMessage = null;
    public ?string $statusType = null;

    // Credit wallet form
    public string $creditAmount = '';
    public string $creditReason = '';

    // Debit wallet form
    public string $debitAmount = '';
    public string $debitReason = '';

    public function mount(User $user): void
    {
        if (! auth()->user()->can('user.view')) {
            abort(403);
        }

        $this->user = $user->load('roles');
    }

    public function layoutData(): array
    {
        return [
            'title'   => $this->user->name,
            'heading' => 'User Details',
        ];
    }

    protected function flash(string $type, string $message): void
    {
        $this->statusType = $type;
        $this->statusMessage = $message;
    }

    /*
    |--------------------------------------------------------------------------
    | Actions
    |--------------------------------------------------------------------------
    */

    public function toggleStatus(): void
    {
        if (! auth()->user()->can('user.edit')) {
            abort(403);
        }

        if ($this->user->id === auth()->id()) {
            $this->flash('error', 'You cannot suspend your own account.');
            return;
        }

        $this->user->status = $this->user->status === 'active' ? 'suspended' : 'active';
        $this->user->save();

        $this->flash('success', 'User status updated to '.$this->user->status.'.');
    }

    public function creditWallet(): void
    {
        if (! auth()->user()->can('wallet.credit')) {
            abort(403);
        }

        $this->validate([
            'creditAmount' => 'required|numeric|min:1|max:50000',
            'creditReason' => 'required|string|max:255',
        ], [
            'creditAmount.required' => 'Enter an amount.',
            'creditAmount.min'      => 'Minimum credit is 1.',
            'creditAmount.max'      => 'Maximum single credit is 50,000.',
            'creditReason.required' => 'A reason is required for the audit trail.',
        ]);

        try {
            app(WalletService::class)->credit(
                user:        $this->user,
                amount:      (float) $this->creditAmount,
                description: $this->creditReason,
                performedBy: auth()->user(),
            );

            $this->user->refresh();
            $this->reset(['creditAmount', 'creditReason']);
            $this->dispatch('close-modal-credit-wallet');
            $this->flash('success', 'Wallet credited by Nu. '.number_format((float) $this->user->wallet_balance, 2).' balance now shown above.');
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
    }

    public function debitWallet(): void
    {
        if (! auth()->user()->can('wallet.debit')) {
            abort(403);
        }

        $this->validate([
            'debitAmount' => 'required|numeric|min:1|max:50000',
            'debitReason' => 'required|string|max:255',
        ], [
            'debitAmount.required' => 'Enter an amount.',
            'debitAmount.min'      => 'Minimum debit is 1.',
            'debitAmount.max'      => 'Maximum single debit is 50,000.',
            'debitReason.required' => 'A reason is required for the audit trail.',
        ]);

        try {
            app(WalletService::class)->debit(
                user:        $this->user,
                amount:      (float) $this->debitAmount,
                description: $this->debitReason,
                performedBy: auth()->user(),
            );

            $this->user->refresh();
            $this->reset(['debitAmount', 'debitReason']);
            $this->dispatch('close-modal-debit-wallet');
            $this->flash('success', 'Wallet debited. New balance: Nu. '.number_format((float) $this->user->wallet_balance, 2));
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Render
    |--------------------------------------------------------------------------
    */

    public function render(): View
    {
        $orders = $this->user->orders()
            ->with('items')
            ->orderByDesc('service_date')
            ->orderByDesc('id')
            ->paginate(8, ['*'], 'ordersPage');

        $transactions = $this->user->walletTransactions()
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        $paymentProofs = $this->user->paymentProofs()
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $stats = [
            'orderCount'   => $this->user->orders()->count(),
            'lifetimeValue'=> (float) $this->user->orders()
                                    ->where('status', '!=', 'cancelled')
                                    ->sum('total'),
            'pendingProofs'=> $paymentProofs->where('status', 'pending')->count(),
        ];

        return view('admin.users.show', compact('orders', 'transactions', 'paymentProofs', 'stats'));
    }
}