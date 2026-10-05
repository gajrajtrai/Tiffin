<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Carbon;

class ExportReportRequest extends FormRequest
{
    /**
     * Only users with report.export can download exports.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('report.export') ?? false;
    }

    /**
     * Validation rules for the export query string.
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:orders,items,customers,expenses'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to'   => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ];
    }

    /**
     * Custom messages so failures give helpful errors.
     */
    public function messages(): array
    {
        return [
            'type.in'           => 'Unknown export type. Choose one of: orders, items, customers, expenses.',
            'from.date_format'  => 'From date must be in YYYY-MM-DD format.',
            'to.date_format'    => 'To date must be in YYYY-MM-DD format.',
            'to.after_or_equal' => 'The "to" date must be on or after the "from" date.',
        ];
    }

    /**
     * Return 422 for validation failures instead of the default 302 redirect.
     * This is a file-download endpoint, not a form.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Invalid export parameters.',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }

    /**
     * Ensure the from/to range is sane — no more than 1 year back, no future.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $from = $this->input('from');
            $to   = $this->input('to');

            // Only run range checks when the date format itself passed.
            // Otherwise Carbon would throw on garbage input.
            $fromIsValidDate = $from && $this->looksLikeIsoDate($from);
            $toIsValidDate   = $to   && $this->looksLikeIsoDate($to);

            if ($fromIsValidDate) {
                $fromDate = Carbon::createFromFormat('Y-m-d', $from)->startOfDay();

                if ($fromDate->lt(now()->subYear())) {
                    $validator->errors()->add(
                        'from',
                        'Export range cannot start more than one year in the past.'
                    );
                }

                if ($fromDate->isFuture()) {
                    $validator->errors()->add(
                        'from',
                        'The "from" date cannot be in the future.'
                    );
                }
            }

            if ($toIsValidDate) {
                $toDate = Carbon::createFromFormat('Y-m-d', $to)->startOfDay();

                if ($toDate->isFuture()) {
                    $validator->errors()->add(
                        'to',
                        'The "to" date cannot be in the future.'
                    );
                }
            }
        });
    }

    /**
     * Quick structural check so we never call Carbon::parse() on garbage.
     */
    protected function looksLikeIsoDate(string $value): bool
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return false;
        }

        // Verify it's a real calendar date (e.g., not 2026-02-30)
        [$y, $m, $d] = array_map('intval', explode('-', $value));

        return checkdate($m, $d, $y);
    }

    /*
    |--------------------------------------------------------------------------
    | Typed accessors — cleaner than reading $request->input() in the controller
    |--------------------------------------------------------------------------
    */

    public function exportType(): string
    {
        return $this->validated('type');
    }

    public function fromDate(): Carbon
    {
        $from = $this->validated('from') ?? now()->startOfMonth()->toDateString();
        return Carbon::parse($from)->startOfDay();
    }

    public function toDate(): Carbon
    {
        $to = $this->validated('to') ?? today()->toDateString();
        return Carbon::parse($to)->endOfDay();
    }
}