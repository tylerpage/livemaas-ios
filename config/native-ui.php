<?php

/**
 * Native UI — Theme Tokens
 *
 * Colors are sampled from the house: slate lap siding, harbor-blue shake
 * gable, charcoal shingles, white trim, black front door, the lawn and sky.
 * Keep each `on-*` token at 4.5:1 contrast against its background token.
 */

return [

    'theme' => [

        'light' => [
            // Slate lap siding.
            'primary' => '#515660',
            'on-primary' => '#FFFFFF',

            // Harbor-blue shake gable.
            'secondary' => '#2F3C49',
            'on-secondary' => '#FFFFFF',

            // White trim and garage doors.
            'surface' => '#FFFFFF',
            'on-surface' => '#0B0F14',
            'background' => '#F4F6F8',
            'on-background' => '#0B0F14',

            'surface-variant' => '#E8ECF0',
            // Shingle charcoal, darkened from #70757E to clear 4.5:1 on the variant surface.
            'on-surface-variant' => '#5C616A',

            'outline' => '#D3D9DF',
            'outline-variant' => '#E8ECF0',

            'destructive' => '#B91C1C',
            'on-destructive' => '#FFFFFF',

            // Sky — links, info, "new" highlights.
            'accent' => '#8CBFFB',
            'on-accent' => '#0B0F14',

            // Lawn — paid, fed, done.
            'success' => '#55870A',
            'on-success' => '#0B0F14',

            'input-fill' => '#FFFFFF',
            'on-input' => '#0B0F14',
        ],

        'dark' => [
            'primary' => '#A3ABB8',
            'on-primary' => '#0B0F14',

            'secondary' => '#B2C1CC',
            'on-secondary' => '#0B0F14',

            // Front-door black and the harbor gable as dark surfaces.
            'surface' => '#1A222B',
            'on-surface' => '#F4F6F8',
            'background' => '#0B0F14',
            'on-background' => '#F4F6F8',

            'surface-variant' => '#2F3C49',
            'on-surface-variant' => '#B9C1CA',

            'outline' => '#3A4552',
            'outline-variant' => '#252E38',

            'destructive' => '#F87171',
            'on-destructive' => '#0B0F14',

            'accent' => '#8CBFFB',
            'on-accent' => '#0B0F14',

            'success' => '#7DB33A',
            'on-success' => '#0B0F14',

            'input-fill' => '#1A222B',
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
