<?php

namespace FCNPressespiegel\Enum;

enum Option: string
{
    case CRONJOB_ENABLED = '_fcnp_cronjob_enabled';

    case HIDE_OLDER_THEN_DAYS = '_fcnp_hide_older_then_days';

    case AI_TAGGING_ENABLED = '_fcnp_ai_tagging_enabled';

    case EXCERPT_ENABLED = '_fcnp_excerpt_enabled';

    case IMPORT_ERORRS = '_fcnp_import_errors';
}
