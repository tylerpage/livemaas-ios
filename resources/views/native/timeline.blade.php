@use('App\Icons\Ios')
@use('App\Icons\Android')

<native:column fill class="bg-theme-background">
    @if ($error)
        <native:text ref="timeline-error" class="w-full px-4 py-2 text-sm text-theme-destructive">{{ $error }}</native:text>
    @endif

    @if ($loaded && $posts === [] && ! $error)
        <native:empty-state title="No house logs yet" message="Tap + to post the first update, photo or video." />
    @else
        <native:refreshable @refresh="refresh" class="w-full">
            <native:column class="w-full gap-3 p-4">
                @foreach ($posts as $post)
                    <native:post-card :post="$post" key="post-{{ $post['id'] }}" @deleted="postDeleted" />
                @endforeach

                @if ($nextCursor)
                    <native:button ref="load-more" variant="secondary" class="w-full" @tap="loadMore">Load older logs</native:button>
                @endif
            </native:column>
        </native:refreshable>
    @endif

    <native:fab ref="compose" @tap="compose" a11y-label="New house log" :ios="Ios::Plus" :android="Android::Add" />
</native:column>
