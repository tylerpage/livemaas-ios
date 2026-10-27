<?php

declare(strict_types=1);

namespace App\NativeComponents\Layouts;

use App\Icons\Android;
use App\Icons\Ios;
use Native\Mobile\Edge\Layouts\Builders\NavBar;
use Native\Mobile\Edge\Layouts\Builders\Tab;
use Native\Mobile\Edge\Layouts\Builders\TabBar;
use Native\Mobile\Edge\Layouts\NativeLayout;
use Native\Mobile\Edge\NativeComponent;

/**
 * The five-tab shell every signed-in screen sits in. Uses the system bars
 * (Liquid Glass on iOS 26) rather than custom-drawn ones, tinted with the
 * house's slate.
 */
class HouseTabs extends NativeLayout
{
    public function usesNativeChrome(): bool
    {
        return true;
    }

    public function navBar(NativeComponent $screen): ?NavBar
    {
        return NavBar::make()
            ->title($screen->navTitle())
            ->displayMode('large');
    }

    public function tabBar(NativeComponent $screen): ?TabBar
    {
        return TabBar::make()
            ->activeColor(theme('primary'))
            ->add(Tab::link('Timeline', '/', ios: Ios::HouseFill, android: Android::Home))
            ->add(Tab::link('Bills', '/bills', ios: Ios::CreditcardFill, android: Android::CreditCard))
            ->add(Tab::link('Dogs', '/dogs', ios: Ios::PawprintFill, android: Android::Pets))
            ->add(Tab::link('Files', '/files', ios: Ios::FolderFill, android: Android::Folder))
            ->add(Tab::link('More', '/more', ios: Ios::EllipsisCircle, android: Android::MoreHoriz));
    }
}
