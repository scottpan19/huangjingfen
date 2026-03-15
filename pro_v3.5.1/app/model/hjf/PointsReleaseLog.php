<?php
declare(strict_types=1);

namespace app\model\hjf;

use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;

/**
 * 积分释放日志模型
 * Class PointsReleaseLog
 * @package app\model\hjf
 */
class PointsReleaseLog extends BaseModel
{
    use ModelTrait;

    protected $pk = 'id';

    protected $name = 'points_release_log';

    protected $autoWriteTimestamp = 'int';

    protected $createTime = 'add_time';

    public function setAddTimeAttr(): int
    {
        return time();
    }
}
