<?php

if (! function_exists('array_extract_options')) {
    function array_extract_options(&$array)
    {
        $options = [];
        if (is_array($array)) {
            $clonedArray = $array;
            uksort($clonedArray, 'strnatcmp');
            foreach ( $clonedArray as $key => $value ) {
                if ( is_string($key) ) {
                    $options[$key] = $value;
                    unset($array[$key]);
                }
            }
        }
        return $options;
    }
}

if (! function_exists('compact_property')) {
    function compact_property($instance, $properties)
    {
        $properties = array_slice(func_get_args(), 1);
        $compactArray = [];
        foreach ($properties as $property) {
            if ( property_exists($instance, $property) ) {
                $reflection = new \ReflectionProperty($instance, $property);
                $reflection->setAccessible(true);
                $$property = $reflection->getValue($instance);

                $compactArray = array_merge($compactArray, compact($property));
            }
        }
        return $compactArray;
    }
}

if (! function_exists('ac_trans')) {
    function ac_trans($id, $parameters = [], $domain = 'messages', $locale = null)
    {
        $namespace = null;
        // TODO: DRY conditions
        if (! Lang::has($id)) {
            $namespace = 'authority-controller::';
            $id = $namespace.$id;

            if (! Lang::has($id)) {
                $defaultId = 'messages.unauthorized.default';
                $id = $namespace.$defaultId;

                if (! Lang::has($id)) {
                    $id = $defaultId;
                    if (Lang::has($id, 'en')) {
                        return trans($id, $parameters, $domain, 'en');
                    } else {
                        return trans($namespace.$id, $parameters, $domain, 'en');
                    }
                }
            }
        }

        return trans($id, $parameters, $domain, $locale);
    }
}


if (! function_exists('ac_trans_choice')) {
    function ac_trans_choice($id, $number, array $parameters = [], $domain = 'messages', $locale = null)
    {
        $namespace = null;
        // TODO: DRY conditions
        if (! Lang::has($id)) {
            $namespace = 'authority-controller::';
            $id = $namespace.$id;

            if (! Lang::has($id)) {
                $defaultId = 'messages.unauthorized.default';
                $id = $namespace.$defaultId;

                if (! Lang::has($id)) {
                    $id = $defaultId;
                    if (Lang::has($id, 'en')) {
                        return trans_choice($id, $number, $parameters, $domain, 'en');

                    } else {
                        return trans_choice($namespace.$id, $number, $parameters, $domain, 'en');
                    }
                }
            }
        }

        return trans_choice($id, $number, $parameters, $domain, $locale);
    }
}
