<?php
declare(strict_types=1);

namespace app\model\hjf;

use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;

/**
 * 公排池模型
 * Class QueuePool
 * @package app\model\hjf
 */
class QueuePool extends BaseModel
{
    use ModelTrait;

    protected $pk = 'id';

    protected $name = 'queue_pool';

    protected $autoWriteTimestamp = 'int';

    protected $createTime = 'add_time';

    public function setAddTimeAttr(): int
    {
        return time();
    }

    /**
     * 状态文本
     * @param int $value
     * @return string
     */
    public function getStatusTextAttr(mixed $value, array $data): string
    {
        return ($data['status'] ?? 0) === 1 ? '已退款' : '排队中';
    }
}
