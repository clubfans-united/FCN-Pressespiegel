<?php

namespace FCNPressespiegel\Controller;

class PluginController
{
    use Controller;

    private function __construct()
    {
        PressreviewController::init();
        CronController::init();
        BookmarkletController::init();
        SettingsController::init();
    }
}
