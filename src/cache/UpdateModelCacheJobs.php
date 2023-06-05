<?php

namespace Yuyue8\TpProjectCores\cache;

use \Yuyue8\TpProjectCores\basic\BaseCache;
use \Yuyue8\TpQueue\basic\BaseJobs;
use \Yuyue8\TpQueue\traits\QueueTrait;

/**
 * Class UpdateModelCacheJobs
 * @package data\jobs\cache
 */
class UpdateModelCacheJobs extends BaseJobs
{
    use QueueTrait;

    protected $queueName = 'UpdateModelCacheJobs';

    public function doJob($cache_class, $list)
    {
        
        try {

            if(!$list->isEmpty()){
                /** @var BaseCache $cache */
                $cache = app($cache_class);
                $cache->deleteCache($list);
            }

        } catch (\Throwable $th) {
            //throw $th;
            event('jobs.JobsFailListener', "UpdateModelCacheJobs:where=>{" . json_encode($list) . "}, error=>" . $th->getMessage());
            return false;
        }

        return true;
    }
}
