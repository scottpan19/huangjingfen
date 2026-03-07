<template>
    <div class="tdinfo">
        <Row class="expand-row">
            <Col span="6">
                <span class="expand-key">用户昵称：</span>
                <span class="expand-value"> {{row.nickname}}</span>
            </Col>
            <Col span="6">
                <span class="expand-key">手机号码：</span>
                <span class="expand-value">{{row.phone}}</span>
            </Col>
            <Col span="6">
                <span class="expand-key">真实姓名：</span>
                <span class="expand-value">{{row.real_name}}</span>
            </Col>
            <Col span="6">
                <span class="expand-key">生日：</span>
                <span class="expand-value">{{row.birthday}}</span>
            </Col>
        </Row>
        <Row class="expand-row">
            <Col span="6">
                <span class="expand-key">用户分组：</span>
                <span class="expand-value"> {{row.group_id}}</span>
            </Col>
            <Col span="6">
                <span class="expand-key">用户标签：</span>
                <span class="expand-value">{{ row.labels }}</span>
            </Col>
            <Col span="6">
                <span class="expand-key">用户性别：</span>
                <span class="expand-value">{{row.sex}}</span>
            </Col>
            <Col span="6">
                <span class="expand-key">会员等级：</span>
                <span class="expand-value">{{row.level}}</span>
            </Col>
        </Row>
        <Row class="expand-row">
            <Col span="6">
                <span class="expand-key">付费会员：</span>
                <span class="expand-value">{{row.isMember ? "是" : "否"}}</span>
            </Col>
            <Col span="6">
                <span class="expand-key">推广员：</span>
                <span class="expand-value">{{row.is_promoter ? "是" : "否"}}</span>
            </Col>
            <Col span="6">
                <span class="expand-key">首次访问：</span>
                <span class="expand-value"> {{row.add_time | formatDate}}</span>
            </Col>
            <Col span="6">
                <span class="expand-key">近次访问：</span>
                <span class="expand-value">{{row.last_time  | formatDate}}</span>
            </Col>
        </Row>
        <Row class="expand-row">
            <!--  -->
            <Col span="6">
                <span class="expand-key">推荐人：</span>
                <span class="expand-value">{{row.spread_uid_nickname}}</span>
            </Col>
            <Col span="6">
                <span class="expand-key">下单次数：</span>
                <span class="expand-value">{{row.pay_count}}</span>
            </Col>
            <Col span="6">
                <span class="expand-key">国家：</span>
                <span class="expand-value">{{row.country}}</span>
            </Col>
            <Col span="6">
                <span class="expand-key">采购商：</span>
                <span class="expand-value">{{row.is_channel ? "是" : "否"}}</span>
            </Col>
        </Row>
        <Row class="expand-row">
            <Col span="6">
                <span class="expand-key">备注：</span>
                <span class="expand-value">{{row.mark}}</span>
            </Col>
        </Row>
    </div>
</template>

<script>
    import { formatDate } from '@/utils/validate';
    export default {
        name: 'table-expand',
        filters: {
            formatDate (time) {
                if (time !== 0) {
                    let date = new Date(time * 1000);
                    return formatDate(date, 'yyyy-MM-dd hh:mm');
                }
            }
        },
        props: {
            row: Object
        }
    }
</script>

<style scoped>
    .expand-row{
        margin-bottom: 16px;
    }
    .tdinfo {
        margin-left: 88px;
    }
</style>
