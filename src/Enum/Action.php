<?php

namespace FCNPressespiegel\Enum;

enum Action : string
{
    case PRESSREVIEW_THIS_SHOW = 'fcnp_pressreview_this_show';

    case IMPORT = 'fcnp_import';
    public static function getCurrentAction(bool $is_query_var = false): ?Action
    {
        if ($is_query_var) {
            $actionRequest = get_query_var('fcnp-action');
        } else {
            $actionRequest =
                filter_input(INPUT_POST, 'fcnp-action', FILTER_UNSAFE_RAW) ??
                filter_input(INPUT_GET, 'fcp-action', FILTER_UNSAFE_RAW);
        }

        if (Action::tryFrom($actionRequest)) {
            return Action::from($actionRequest);
        }

        return null;
    }
}
