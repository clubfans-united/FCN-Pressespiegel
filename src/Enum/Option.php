<?php

namespace FCNPressespiegel\Enum;

enum Option : string
{
    case CRONJOB_ENABLED = '_fcnp_cronjob_enabled';

    case HIDE_OLDER_THEN_DAYS = '_fcnp_hide_older_then_days';
}
