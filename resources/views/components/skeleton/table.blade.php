@props(['rows' => 5, 'cols' => 4])

<div class="w-full">
    <div class="hidden md:block">
        <table class="w-full table-fixed">
            <thead class="bg-surface-th border-b border-border">
                <tr>
                    @for($i = 0; $i < $cols; $i++)
                        <th class="px-4 py-3 text-left">
                            <x-skeleton class="h-3 w-20 rounded" />
                        </th>
                    @endfor
                </tr>
            </thead>
            <tbody class="divide-y divide-border-light/50">
                @for($i = 0; $i < $rows; $i++)
                    <tr>
                        @for($j = 0; $j < $cols; $j++)
                            <td class="px-4 py-3">
                                <x-skeleton class="h-4 w-full max-w-[180px] rounded" style="width: {{ rand(40, 90) }}%;" />
                            </td>
                        @endfor
                    </tr>
                @endfor
            </tbody>
        </table>
    </div>
    <div class="md:hidden flex flex-col gap-3">
        @for($i = 0; $i < $rows; $i++)
            <div class="card p-4">
                <div class="flex justify-between items-start mb-3">
                    <x-skeleton class="h-4 w-32 rounded" />
                    <x-skeleton class="h-5 w-16 rounded-full" />
                </div>
                <div class="space-y-2">
                    <x-skeleton class="h-3 w-48 rounded" />
                    <x-skeleton class="h-3 w-24 rounded" />
                </div>
            </div>
        @endfor
    </div>
</div>
