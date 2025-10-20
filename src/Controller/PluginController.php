<?php

namespace FCNPressespiegel\Controller;

class PluginController
{
    use Controller;

    private function __construct()
    {
        PressreviewBookmarkletController::init();
        PressreviewController::init();
        SettingsController::init();
        AutoImportController::init();
    }
}
