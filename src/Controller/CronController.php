<?php

namespace FCNPressespiegel\Controller;

use FCNPressespiegel\Enum\Option;
use FCNPressespiegel\Manager\PressreviewManager;

class CronController
{
    use Controller;

    private const HOOK = 'fcnp_cron_import';

    private function __construct()
    {

        register_activation_hook(FCNP_PLUGIN_FILE, $this->schedule(...));
        register_deactivation_hook(FCNP_PLUGIN_FILE, $this->unschedule(...));

        add_action(self::HOOK, $this->import(...));
    }

    private function schedule(): void
    {
        if (wp_next_scheduled(self::HOOK)) {
            return;
        }

        wp_schedule_event(time(), 'hourly', self::HOOK);
    }

    private function unschedule(): void
    {
        wp_clear_scheduled_hook(self::HOOK);
    }

    private function import(): void
    {
        $pressreviewManager = new PressreviewManager();
        $pressreviewManager->import();
    }
}
