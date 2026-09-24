<?php

namespace App\Modules\Admin\Http\Livewire;

use App\Models\AccountClosureRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class ClosureRequestsIndex extends Component
{
    use WithPagination;

    #[Url(as: 'status', except: 'pending')]
    public string $statusFilter = 'pending';

    public ?string $statusMessage = null;
    public ?string $statusType = null;

    // Approve/reject modal
    public ?int $reviewingId = null;
    public string $adminNotes = '';

    public function mount(): void
    {
        if (! auth()->user()->can('user.edit')) {
            abort(403);
        }
    }

    public function layoutData(): array
    {
        return [
            'title'   => 'Closure Requests',
            'heading' => 'Account Closure Requests',
        ];
    }

    protected function flash(string $type, string $message): void
    {
        $this->statusType = $type;
        $this->statusMessage = $message;
    }

    public function setStatus(string $status): void
    {
        $this->statusFilter = $status;
        $this->resetPage();
    }

    /*
    |--------------------------------------------------------------------------
    | Review actions
    |--------------------------------------------------------------------------
    */

    public function openReview(int $id): void
    {
        $req = AccountClosureRequest::findOrFail($id);

        if (! $req->isPending()) {
            $this->flash('error', 'This request has already been reviewed.');
            return;
        }

        $this->reviewingId = $id;
        $this->adminNotes = '';
        $this->resetErrorBag();
        $this->dispatch('open-modal-review-closure');
    }

    public function closeReview(): void
    {
        $this->reviewingId = null;
        $this->adminNotes = '';
        $this->resetErrorBag();
        $this->dispatch('close-modal-review-closure');
    }

    public function approve(): void
    {
        if (! auth()->user()->can('user.edit')) {
            abort(403);
        }

        $this->validate([
            'adminNotes' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () {
            $req = AccountClosureRequest::query()
                ->lockForUpdate()
                ->findOrFail($this->reviewingId);

            if (! $req->isPending()) {
                throw new \RuntimeException('This request has already been reviewed.');
            }

            $req->status = AccountClosureRequest::STATUS_APPROVED;
            $req->admin_notes = $this->adminNotes !== '' ? $this->adminNotes : null;
            $req->reviewed_by = auth()->id();
            $req->reviewed_at = now();
            $req->save();

            // Suspend the user — data remains intact
            $user = User::find($req->user_id);
            if ($user) {
                $user->status = 'suspended';
                $user->save();
            }

            // Auto-reject any other pending requests from the same user
            AccountClosureRequest::query()
                ->where('user_id', $req->user_id)
                ->where('id', '!=', $req->id)
                ->pending()
                ->update([
                    'status' => AccountClosureRequest::STATUS_REJECTED,
                    'admin_notes' => 'Superseded by an approved closure request.',
                    'reviewed_by' => auth()->id(),
                    'reviewed_at' => now(),
                ]);
        });

        $this->closeReview();
        $this->flash('success', 'Closure approved. Customer account suspended.');
    }

    public function reject(): void
    {
        if (! auth()->user()->can('user.edit')) {
            abort(403);
        }

        $this->validate([
            'adminNotes' => ['required', 'string', 'min:3', 'max:500'],
        ], [
            'adminNotes.required' => 'A reason is required — the customer will see it.',
            'adminNotes.min'      => 'Please give a clearer reason.',
        ]);

        $req = AccountClosureRequest::findOrFail($this->reviewingId);

        if (! $req->isPending()) {
            $this->flash('error', 'This request has already been reviewed.');
            return;
        }

        $req->status = AccountClosureRequest::STATUS_REJECTED;
        $req->admin_notes = $this->adminNotes;
        $req->reviewed_by = auth()->id();
        $req->reviewed_at = now();
        $req->save();

        $this->closeReview();
        $this->flash('success', 'Request rejected. Customer will see your note.');
    }

    /*
    |--------------------------------------------------------------------------
    | Render
    |--------------------------------------------------------------------------
    */

    public function render(): View
    {
        $query = AccountClosureRequest::query()
            ->with(['user', 'reviewedBy']);

        if (in_array($this->statusFilter, ['pending', 'approved', 'rejected'], true)) {
            $query->where('status', $this->statusFilter);
        }

        $requests = $query->orderByDesc('created_at')->paginate(25);

        $counts = [
            'pending'  => AccountClosureRequest::pending()->count(),
            'approved' => AccountClosureRequest::where('status', 'approved')->count(),
            'rejected' => AccountClosureRequest::where('status', 'rejected')->count(),
        ];

        $reviewing = $this->reviewingId
            ? AccountClosureRequest::with('user')->find($this->reviewingId)
            : null;

        return view('admin.users.closure-requests', compact('requests', 'counts', 'reviewing'));
    }
}