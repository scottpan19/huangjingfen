<?php

namespace app\controller\admin\v1\system;

use app\controller\admin\AuthController;
use app\services\system\SystemRecommendServices;
use think\annotation\Inject;

class SystemRecommend extends AuthController
{
    /**
     * @var SystemRecommendServices
     */
    #[Inject]
    protected SystemRecommendServices $services;

    /**
     * 获取推荐列表
     * @return mixed
     */
    public function getRecommendList()
    {
        return $this->success($this->services->getRecommendList());
    }

    /**
     * 获取推荐详情
     * @param int $id 推荐ID
     * @return mixed
     */
    public function getRecommendInfo($id)
    {
        return $this->success($this->services->getRecommendInfo($id));
    }

    /**
     * 编辑推荐标题
     * @return mixed
     */
    public function editRecommendTitle()
    {
        [$id, $title] = $this->request->postMore([
            ['id', 0],
            ['title', ''],
        ], true);
        $this->services->editRecommendTitle($id, $title);
        return $this->success('保存成功');
    }

    /**
     * 保存推荐配置
     * @return mixed
     */
    public function saveRecommend()
    {
        $data = $this->request->postMore([
            ['id', 0], // id 0新增，非0编辑
            ['name', ''], //名称
            ['title', ''], //推荐标题
            ['pic', ''], //图标
            ['type', 0], //推荐类型
            ['specify_recommend_status', 0], //指定推荐状态
            ['specify_recommend_content', ''], //指定推荐内容
            ['personality_recommend_status', 0], //个性推荐状态
            ['personality_recommend_content', ''], //个性推荐内容
            ['sort_recommend_status', 0], //排序推荐状态
            ['sort_recommend_content', ''], //排序推荐内容
            ['status', 1] //状态
        ]);
        $id = $data['id'];
        unset($data['id']);
        $result = $this->services->saveRecommend($id, $data);
        return $this->success('保存成功');
    }

    /**
     * 设置推荐状态
     * @param int $id 推荐ID
     * @param int $status 状态
     * @return mixed
     */
    public function setRecommendStatus($id, $status)
    {
        $this->services->setRecommendStatus($id, $status);
        return $this->success('修改成功');
    }
}
