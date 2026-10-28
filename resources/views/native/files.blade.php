@use('App\Icons\Ios')
@use('App\Icons\Android')

<native:column fill class="bg-theme-background">
    <native:column class="w-full gap-2 px-4 pt-2 pb-2">
        <native:outlined-text-input ref="search" placeholder="Search files" :ios-leading-icon="Ios::Magnifyingglass" :android-leading-icon="Android::Search"
                                    native:model.debounce.400ms="search" />
        <native:select ref="category" label="Category" :options="$this->categoryOptions()" native:model="category" />
    </native:column>

    @if ($loaded && $documents === [] && ! $error)
        @if ($this->isFiltered())
            <native:text class="w-full text-center py-8 text-theme-on-surface-variant">Nothing matches that search.</native:text>
        @else
            <native:empty-state title="No files yet" message="Tap + to add insurance policies, warranties, manuals and other paperwork." />
        @endif
    @else
        <native:refreshable @refresh="refresh" class="w-full">
            <native:column class="w-full gap-3 px-4 pb-4">
                @foreach ($documents as $document)
                    <native:row ref="document-{{ $document['id'] }}" a11y-label="{{ $document['title'] }}"
                                class="w-full items-center gap-3 rounded-lg bg-theme-surface border border-theme-outline-variant p-4"
                                @tap="open({{ $document['id'] }})">
                        <native:column center class="w-10 h-10 rounded-md bg-theme-secondary">
                            <native:icon :ios="Ios::DocFill" :android="Android::Description" :size="20" class="text-theme-on-secondary" />
                        </native:column>
                        <native:column class="flex-1 gap-1">
                            <native:text class="font-bold text-theme-on-surface">{{ $document['title'] }}</native:text>
                            <native:text class="text-sm text-theme-on-surface-variant">
                                {{ $document['category_label'] }} · {{ $document['attachment_count'] }} {{ Str::plural('file', $document['attachment_count']) }} · {{ $this->addedOn($document['created_at']) }}
                            </native:text>
                        </native:column>
                        <native:icon :ios="Ios::ChevronRight" :android="Android::ChevronRight" :size="14" class="text-theme-on-surface-variant" />
                    </native:row>
                @endforeach
            </native:column>
        </native:refreshable>
    @endif

    @if ($error)
        <native:text ref="files-error" class="w-full px-4 text-sm text-theme-destructive">{{ $error }}</native:text>
    @endif

    <native:fab ref="add-document" @tap="add" a11y-label="Add a file" :ios="Ios::Plus" :android="Android::Add" />
</native:column>
