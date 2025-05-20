<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Request;

class Menu
{
    public static function setClassByRoute($routes, $class = 'active', $addLanguaje = false)
    {
        foreach ($routes as $route) {
            if (Request::is(($addLanguaje ? app()->getLocale().'/' : '').$route.'*')) {
                return $class;
            }
        }

        return '';
    }

    public static function subMenu($name, $children)
    {
        return '<li><a class="'.self::setClassByRoute([$name], 'active', true).'" href="'.route($children['route'], app()->getLocale()).'">'.$children['title'].'</a></li>';
    }
}
