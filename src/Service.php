<?php

namespace Yuyue8\TpProjectCores;

class Service extends \think\Service
{

    public function boot()
    {
        $this->commands(
            \Yuyue8\TpProjectCores\commands\MakeCores::class
        );
    }

}
