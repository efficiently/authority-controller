<?php

if (! function_exists('array_extract_options')) {
    function array_extract_options(&$array)
    {
        $options = [];
        if (is_array($array)) {
            $clonedArray = $array;
            uksort($clonedArray, 'strnatcmp');
            foreach ($clonedArray as $key => $value) {
                if (is_string($key)) {
                    $options[$key] = $value;
                    unset($array[$key]);
                }
            }
        }

        return $options;
    }
}

if (! function_exists('get_classname')) {

    /**
     * Like get_class() function but compatible with Mockery and $object parameter is required
     *
     * @param  object|\Mockery\MockInterface $object
     * @return string
     */
    function get_classname($object)
    {
        $classname = get_class($object);
        if ($object instanceof \Mockery\MockInterface) {
            $classname = preg_replace('/_/', '\\', preg_replace('/^Mockery_[0-9]+_+(.+)$/', '$1', $classname));
        }

        return $classname;
    }
}

if (! function_exists('respond_to')) {

    /**
     * Like method_exists function but compatible with Mockery
     *
     * @param  mixed   $object
     * @param  string  $methodName
     * @return boolean
     */
    function respond_to($object, $methodName)
    {
        if (method_exists($object, $methodName)) {
            return true;
        } elseif (is_a($object, '\Mockery\MockInterface') && ($expectationDirector = array_get($object->mockery_getExpectations(), $methodName))) {
            foreach ((array) $expectationDirector->getExpectations() as $expectation) {
                if ($expectation->isEligible()) {
                    return true;
                }
            }
        } elseif (is_string($object) && class_exists($object) && is_a(($instance=\App::make($object)), '\Mockery\MockInterface')) {
            // Check if a mocked static method exists or not. You need to do:
            //
            //   $category = Mockery::mock('alias:Category', ['getProducts'=>'products']);
            //   App::instance('Category', $category);
            //   respond_to('Category', 'getProducts');//-> true
            return respond_to($instance, $methodName);
        }

        return false;
    }
}

if (! function_exists('compact_property')) {
    function compact_property($instance, $properties)
    {
        $properties = array_slice(func_get_args(), 1);
        $compactArray = [];
        foreach ($properties as $property) {
            if (property_exists($instance, $property)) {
                $$property = get_property($instance, $property);

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

if (! function_exists('set_property')) {

    function set_property($object, $propertyName, $value)
    {
        if (property_exists($object, $propertyName)) {
            $reflection = new \ReflectionProperty($object, $propertyName);
            $reflection->setAccessible(true);
            $reflection->setValue($object, $value);
        } else {
            $object->$propertyName = $value;
        }
    }
}

if (! function_exists('get_property')) {

    function get_property($object, $propertyName)
    {
        if (property_exists($object, $propertyName)) {
            $reflection = new \ReflectionProperty($object, $propertyName);
            $reflection->setAccessible(true);
            return $reflection->getValue($object);
        } else {
            return null;
        }
    }
}

if (! function_exists('invoke_method')) {

    function invoke_method($object, $methodName, $values = [])
    {
        $values = (array) $values;
        if (method_exists($object, $methodName)) {
            $reflection = new \ReflectionMethod($object, $methodName);
            $reflection->setAccessible(true);
            return $reflection->invokeArgs($object, $values);
        } else {
            return call_user_func_array([$object, $methodName], $values);
        }
    }
}
