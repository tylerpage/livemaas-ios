<?php

declare(strict_types=1);

arch()->preset()->php();

$supportNamespaces = ['App\NativeComponents\Layouts', 'App\NativeComponents\Concerns'];

arch('native components extend NativeComponent')
    ->expect('App\NativeComponents')
    ->toExtend('Native\Mobile\Edge\NativeComponent')
    ->ignoring($supportNamespaces);

arch('native components render a view')
    ->expect('App\NativeComponents')
    ->toHaveMethod('render')
    ->ignoring($supportNamespaces);
