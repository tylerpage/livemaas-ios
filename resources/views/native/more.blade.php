<native:column fill class="bg-theme-background p-4 gap-6">
    <native:column class="w-full rounded-lg bg-theme-surface p-4 gap-1">
        <native:text class="text-sm text-theme-on-surface-variant">Signed in as</native:text>
        <native:text ref="user-name" class="text-lg font-bold text-theme-on-surface">{{ $userName }}</native:text>
    </native:column>

    <native:button ref="sign-out" variant="destructive" class="w-full" @tap="signOut">
        Sign out
    </native:button>
</native:column>
