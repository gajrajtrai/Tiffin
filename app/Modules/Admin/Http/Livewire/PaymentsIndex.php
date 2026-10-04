<?php

namespace App\Modules\Admin\Http\Livewire;

use App\Modules\Payment\Models\PaymentProof;
use App\Modules\Payment\Services\PaymentProofService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class PaymentsIndex extends Component
{
    use WithPagination;

    #[Url(as: 'status', except: 'pending')]
    public string $statusFilter = 'pending';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public ?string $statusMessage = null;
    public ?string $statusType = null;

    public ?int $reviewingId = null;
    public string $approvalAmount = '';
    public string $rejectionReason = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', PaymentProof::class);
    }

    public function updating(string $name): void
    {
        if (in_array($name, ['statusFilter', 'search'], true)) {
            $this->resetPage();
        }
    }

    public function setStatus(string $status): void
    {
        $this->statusFilter = $status;
        $this->resetPage();
    }

    protected function flash(string $type, string $message): void
    {
        $this->statusType = $type;
        $this->statusMessage = $message;
    }

    public function openReview(int $id): void
    {
        $proof = PaymentProof::findOrFail($id);

        $this->reviewingId = $id;
        $this->approvalAmount = (string) $proof->claimed_amount;
        $this->rejectionReason = '';
        $this->resetErrorBag();

        $this->dispatch('open-modal-review-payment');
    }

    public function approve(): void
    {
        $this->validate([
            'approvalAmount' => 'required|numeric|min:1|max:50000',
        ], [
            'approvalAmount.required' => 'Enter the amount to credit.',
            'approvalAmount.min'      => 'Minimum credit is 1.',
            'approvalAmount.max'      => 'Maximum single credit is 50,000.',
        ]);

        $proof = PaymentProof::findOrFail($this->reviewingId);
        Gate::authorize('verify', $proof);

        $amount = (float) $this->approvalAmount;

        try {
            app(PaymentProofService::class)->approve(
                proof:          $proof,
                admin:          auth()->user(),
                overrideAmount: $amount,
            );

            $this->reviewingId = null;
            $this->approvalAmount = '';
            $this->rejectionReason = '';
            $this->dispatch('close-modal-review-payment');

            $this->flash('success', 'Approved. Credited Nu. '.number_format($amount, 2).'.');
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
    }

    public function reject(): void
    {
        $this->validate([
            'rejectionReason' => 'required|string|min:3|max:255',
        ], [
            'rejectionReason.required' => 'A reason is required.',
            'rejectionReason.min'      => 'Please write a clearer reason.',
        ]);

        $proof = PaymentProof::findOrFail($this->reviewingId);
        Gate::authorize('reject', $proof);

        try {
            app(PaymentProofService::class)->reject(
                proof:  $proof,
                admin:  auth()->user(),
                reason: $this->rejectionReason,
            );

            $this->reviewingId = null;
            $this->approvalAmount = '';
            $this->rejectionReason = '';
            $this->dispatch('close-modal-review-payment');

            $this->flash('success', 'Payment proof rejected.');
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
    }

    public function layoutData(): array
    {
        return [
            'title'   => 'Payments',
            'heading' => 'Payment Verification',
        ];
    }

    public function render(): View
    {
        $query = PaymentProof::query()->with(['user', 'walletTransaction']);

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->search !== '') {
            $term = '%'.$this->search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('bank_reference', 'like', $term)
                  ->orWhere('note', 'like', $term)
                  ->orWhereHas('user', function ($uq) use ($term) {
                      $uq->where('name', 'like', $term)
                         ->orWhere('mobile', 'like', $term);
                  });
            });
        }

        $proofs = $query->orderByDesc('created_at')->paginate(20);

        $counts = [
            'pending'       => PaymentProof::pending()->count(),
            'approved'      => PaymentProof::approved()->count(),
            'rejected'      => PaymentProof::rejected()->count(),
            'pendingAmount' => (float) PaymentProof::pending()->sum('claimed_amount'),
        ];

        $reviewing = $this->reviewingId
            ? PaymentProof::with(['user', 'walletTransaction'])->find($this->reviewingId)
            : null;

        return view('admin.payments.index', compact('proofs', 'counts', 'reviewing'));
    }
}