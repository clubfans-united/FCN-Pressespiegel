<?php

namespace FCNPressespiegel\Cronjobs;

use FCNPressespiegel\Manager\PressreviewManager;
use Rockschtar\WordPress\Cronjob\CronJob;
use Rockschtar\WordPress\Cronjob\Models\CronjobConfig;

class PressreviewAutoImportCronjob extends CronJob
{
    public function execute(): void
    {
        $cronjobEnabled = get_option('_fcnp_cronjob_enabled') === '1';

        if (!$cronjobEnabled) {
            return;
        }

        PressreviewManager::doPressreviewAutoImport();
    }

    public function config(): CronjobConfig
    {
        $config = new CronjobConfig();

        $config->setFirstRun(new \DateTime());
        $config->setRecurrence('hourly');
        $config->setHook('cu_pressreview_auto_import');
        $config->setPluginFile(FCNP_PLUGIN_FILE);
        return $config;
    }
}
