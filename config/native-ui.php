<?php

/**
 * Native UI — Theme Tokens
 *
 * Colors come from the house. Blue leads: the shake-gable blue is the brand
 * color, lifted slightly for primary so it reads as blue on a phone screen
 * rather than charcoal. Slate siding, white trim, the black front door, the
 * lawn and the sky fill in the rest.
 *
 * Keep each `on-*` token at 4.5:1 contrast against its background token.
 */

return [

    'theme' => [

        'light' => [
            // Gable blue, lifted from #2F3C49 — buttons, FAB, active tab, links.
            'primary' => '#3B5573',
            'on-primary' => '#FFFFFF',

            // The gable blue as painted — deep fills: video tiles, avatars, banners.
            'secondary' => '#2F3C49',
            'on-secondary' => '#FFFFFF',

            // White trim on a blue-tinted page.
            'surface' => '#FFFFFF',
            'on-surface' => '#0B0F14',
            'background' => '#EEF2F6',
            'on-background' => '#0B0F14',

            'surface-variant' => '#E1E8EF',
            'on-surface-variant' => '#55606C',

            'outline' => '#C9D3DD',
            'outline-variant' => '#E1E8EF',

            'destructive' => '#B91C1C',
            'on-destructive' => '#FFFFFF',

            // Sky — highlights and "new" badges.
            'accent' => '#8CBFFB',
            'on-accent' => '#0B0F14',

            // Lawn — paid, fed, done.
            'success' => '#55870A',
            'on-success' => '#FFFFFF',

            'input-fill' => '#FFFFFF',
            'on-input' => '#0B0F14',
        ],

        'dark' => [
            'primary' => '#9DB4CC',
            'on-primary' => '#0B0F14',

            'secondary' => '#2F3C49',
            'on-secondary' => '#F4F6F8',

            // Front-door black page, gable-blue cards.
            'surface' => '#16202A',
            'on-surface' => '#F4F6F8',
            'background' => '#0B0F14',
            'on-background' => '#F4F6F8',

            'surface-variant' => '#2F3C49',
            'on-surface-variant' => '#B9C1CA',

            'outline' => '#34414E',
            'outline-variant' => '#202B36',

            'destructive' => '#F87171',
            'on-destructive' => '#0B0F14',

            'accent' => '#8CBFFB',
            'on-accent' => '#0B0F14',

            'success' => '#7DB33A',
            'on-success' => '#0B0F14',

            'input-fill' => '#16202A',
            'on-input' => '#F4F6F8',
        ],

        'radius-sm' => 4,
        'radius-md' => 8,
        'radius-lg' => 16,
        'radius-full' => 9999,

        'font-sm' => 14,
        'font-md' => 16,
        'font-lg' => 20,
        'font-xl' => 24,
    ],

    'fonts' => [
        'default' => 'System',
    ],

];
