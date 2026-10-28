@use('App\Icons\Ios')
@use('App\Icons\Android')

<native:column fill class="bg-theme-background">
    <native:refreshable @refresh="refresh" class="w-full">
        <native:column class="w-full gap-4 p-4">
            {{-- Month switcher --}}
            <native:row class="w-full items-center">
                <native:button ref="previous-month" variant="ghost" :ios-icon="Ios::ChevronLeft" :android-icon="Android::ChevronLeft"
                               a11y-label="Previous month" @tap="previousMonth" />
                <native:text ref="month-label" class="flex-1 text-center text-lg font-bold text-theme-primary">{{ $this->monthLabel() }}</native:text>
                <native:button ref="next-month" variant="ghost" :ios-icon="Ios::ChevronRight" :android-icon="Android::ChevronRight"
                               a11y-label="Next month" :disabled="$this->isCurrentMonth()" @tap="nextMonth" />
            </native:row>

            {{-- Month summary, on the house blue --}}
            @if ($summary)
                <native:column ref="summary" class="w-full rounded-lg bg-theme-secondary p-5 gap-4">
                    <native:column class="gap-1">
                        <native:text class="text-sm text-theme-on-secondary opacity-80">Paid this month</native:text>
                        <native:text ref="month-total" class="text-3xl font-bold text-theme-on-secondary">{{ $this->money($summary['total_cents']) }}</native:text>
                        <native:text class="text-sm text-theme-on-secondary opacity-80">{{ $summary['count'] }} {{ Str::plural('bill', $summary['count']) }}</native:text>
                    </native:column>

                    @if ($summary['by_member'] !== [])
                        <native:column class="w-full gap-2">
                            <native:text class="text-xs font-bold text-theme-on-secondary opacity-80">WHO PAID</native:text>
                            @foreach ($summary['by_member'] as $row)
                                <native:row class="w-full">
                                    <native:text class="flex-1 text-theme-on-secondary">{{ $row['name'] }}</native:text>
                                    <native:text class="font-bold text-theme-on-secondary">{{ $this->money($row['total_cents']) }}</native:text>
                                </native:row>
                            @endforeach
                        </native:column>

                        <native:column class="w-full gap-2">
                            <native:text class="text-xs font-bold text-theme-on-secondary opacity-80">PAID WITH</native:text>
                            @foreach ($summary['by_payment_method'] as $row)
                                <native:row class="w-full">
                                    <native:text class="flex-1 text-theme-on-secondary">{{ $row['name'] }}</native:text>
                                    <native:text class="font-bold text-theme-on-secondary">{{ $this->money($row['total_cents']) }}</native:text>
                                </native:row>
                            @endforeach
                        </native:column>
                    @endif
                </native:column>
            @endif

            @if ($error)
                <native:text ref="bills-error" class="text-sm text-theme-destructive">{{ $error }}</native:text>
            @endif

            {{-- The running list --}}
            @if ($loaded && $bills === [] && ! $error)
                <native:text class="w-full text-center py-8 text-theme-on-surface-variant">No bills recorded for {{ $this->monthLabel() }}.</native:text>
            @endif

            @foreach ($bills as $bill)
                <native:row ref="bill-{{ $bill['id'] }}" a11y-label="{{ $bill['payee'] }}, {{ $this->money($bill['amount_cents']) }}"
                            class="w-full items-center gap-3 rounded-lg bg-theme-surface border border-theme-outline-variant p-4"
                            @tap="openBill({{ $bill['id'] }})">
                    <native:column class="flex-1 gap-1">
                        <native:text class="font-bold text-theme-on-surface">{{ $bill['payee'] }}</native:text>
                        <native:text class="text-sm text-theme-on-surface-variant">
                            {{ $this->paidOnLabel($bill['paid_on']) }} · {{ $bill['paid_by']['name'] ?? '' }}@if ($bill['payment_method']) · {{ $bill['payment_method']['display_name'] }}@endif
                        </native:text>
                    </native:column>
                    @if (($bill['attachment_count'] ?? 0) > 0)
                        <native:icon :ios="Ios::Paperclip" :android="Android::AttachFile" :size="16" a11y-label="Has photos" class="text-theme-on-surface-variant" />
                    @endif
                    <native:text class="font-bold text-theme-primary">{{ $this->money($bill['amount_cents']) }}</native:text>
                </native:row>
            @endforeach
        </native:column>
    </native:refreshable>

    <native:fab ref="add-bill" @tap="addBill" a11y-label="Add a bill" :ios="Ios::Plus" :android="Android::Add" />
</native:column>
