<?php

namespace FCNPressespiegel\Controller;

use FCNPressespiegel\Cronjobs\PressreviewAutoImportCronjob;

class PluginController
{
    use Controller;

    private function __construct()
    {
        PressreviewBookmarkletController::init();
        PressreviewController::init();
        SettingsController::init();
        PressreviewAutoImportCronjob::init();
    }
}
