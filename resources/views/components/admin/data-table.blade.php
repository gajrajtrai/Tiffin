@props([
    'headers' => [],       // ['Column 1', 'Column 2', ...]
    'empty'   => 'No records found.',
])

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm']) }}>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="border-b border-slate-200 bg-slate-50">
                <tr>
                    @foreach ($headers as $header)
                        <th scope="col"
                            class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                            {{ $header }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @if (trim($slot ?? '') === '')
                    <tr>
                        <td colspan="{{ count($headers) }}" class="px-4 py-12 text-center text-sm text-slate-500">
                            {{ $empty }}
                        </td>
                    </tr>
                @else
                    {{ $slot }}
                @endif
            </tbody>
        </table>
    </div>
</div>