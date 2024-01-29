<?php

namespace FCNPressespiegel\Controller;

use FCNPressespiegel\Cronjobs\PressreviewAutoImportCronjob;
use Rockschtar\WordPress\Controller\HookController;

class PluginController
{
    use Controller;

    private function __construct()
    {
        PressreviewBookmarkletController::init();
        PressreviewController::init();
        PressreviewAutoImportCronjob::init();
    }
}
