<?php

namespace FCNPressespiegel\Enum;

use MyCLabs\Enum\Enum;

class Action extends Enum
{
    public const NOTHING = 'nothing';
    public const PRESSREVIEW_THIS_SHOW = 'fcnp_pressreview_this_show';

    public static function getCurrentAction(bool $is_query_var = false): Action
    {
        if ($is_query_var) {
            $action_request = get_query_var('fcnp-action');
        } else {
            $action_request =
                filter_input(INPUT_POST, 'fcnp-action', FILTER_UNSAFE_RAW) ??
                filter_input(INPUT_GET, 'fcp-action', FILTER_UNSAFE_RAW);
        }

        if (Action::isValid($action_request)) {
            return new Action($action_request);
        }

        return new Action(Action::NOTHING);
    }
}
