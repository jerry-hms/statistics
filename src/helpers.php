<?php

if (! function_exists('depth_picker')) {
    /**
     * 根据数组生成key
     * @param $arr
     * @param $attributes
     * @return array
     */
    function depth_picker($arr, $attributes) {

        $collect = [];
        $arr = array_values(array_intersect($arr, array_keys($attributes)));

        if (! $arr) {
            return [];
        }
        for ($i = 0; $i < sizeof($arr); $i++) {
            $field = isset($attributes[$arr[$i]]) ? $arr[$i] . ':' . $attributes[$arr[$i]] : '';
            $collect[] = $field;
            for ($j = $i+1; $j < sizeof($arr); $j++) {
                $field .= ':' . $arr[$j] . ':' . $attributes[$arr[$j]];
                $collect[] = $field;
            }

        }
        return $collect;
    }

}

if (! function_exists('splice_arr')) {

    /**
     * 将字符串拼接成数组
     * @example field1:a:field2:b:field3:c => [field1 => a, field2 => 2, ...]
     * @param string $string
     * @return array|bool
     */
    function splice_arr(string $string) {
        if (strpos($string, ':') === false) {
            return false;
        }
        $arr = explode(':', $string);
        $newArray = [];
        for ($i = 0; $i < sizeof($arr); $i++) {
            if (($i % 2) == 0 || $i == 0) {
                $newArray[$arr[$i]] = isset($arr[$i+1]) ? $arr[$i+1] : '';
            }
        }
        return $newArray;
    }
}