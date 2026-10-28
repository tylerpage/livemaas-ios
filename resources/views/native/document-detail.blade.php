@use('App\Icons\Ios')
@use('App\Icons\Android')

<native:scroll-view fill class="bg-theme-background">
    <native:column class="w-full gap-4 p-4">
        @if ($document)
            <native:column class="w-full rounded-lg bg-theme-secondary p-5 gap-1">
                <native:text ref="document-title" class="text-2xl font-bold text-theme-on-secondary">{{ $document['title'] }}</native:text>
                <native:text class="text-theme-on-secondary opacity-80">
                    {{ $document['category_label'] }} · added {{ $this->addedOn() }} by {{ $document['created_by']['name'] ?? 'someone' }}
                </native:text>
            </native:column>

            @if ($document['notes'])
                <native:column class="w-full rounded-lg bg-theme-surface p-4">
                    <native:text class="text-theme-on-surface leading-relaxed">{{ $document['notes'] }}</native:text>
                </native:column>
            @endif

            @foreach ($document['attachments'] as $attachment)
                <native:row ref="attachment-{{ $loop->index }}" a11y-label="Open {{ $attachment['original_name'] }}"
                            class="w-full items-center gap-3 rounded-lg bg-theme-surface border border-theme-outline-variant p-3"
                            @tap="openAttachment({{ $loop->index }})">
                    @if (str_starts_with($attachment['mime_type'], 'image/') && $attachment['url'])
                        <native:image src="{{ $attachment['url'] }}" alt="" class="w-14 h-14 rounded-sm object-cover" />
                    @else
                        <native:column center class="w-14 h-14 rounded-sm bg-theme-secondary">
                            <native:icon :ios="Ios::DocFill" :android="Android::Description" :size="24" class="text-theme-on-secondary" />
                        </native:column>
                    @endif
                    <native:column class="flex-1 gap-1">
                        <native:text class="text-theme-on-surface" max-lines="2">{{ $attachment['original_name'] }}</native:text>
                        <native:text class="text-sm text-theme-on-surface-variant">{{ $this->fileSize($attachment['size_bytes']) }}</native:text>
                    </native:column>
                    <native:icon :ios="Ios::ArrowUpRight" :android="Android::OpenInNew" :size="14" class="text-theme-primary" />
                </native:row>
            @endforeach

            @if ($confirmingDelete)
                <native:row class="w-full gap-2">
                    <native:button ref="cancel-delete" variant="secondary" class="flex-1" @tap="cancelDelete">Keep</native:button>
                    <native:button ref="confirm-delete" variant="destructive" class="flex-1" @tap="delete">Delete file</native:button>
                </native:row>
            @else
                <native:button ref="delete" variant="ghost" class="w-full" @tap="askToDelete">Delete this file</native:button>
            @endif
        @endif

        @if ($error)
            <native:text ref="document-error" class="text-sm text-theme-destructive">{{ $error }}</native:text>
        @endif
    </native:column>
</native:scroll-view>
