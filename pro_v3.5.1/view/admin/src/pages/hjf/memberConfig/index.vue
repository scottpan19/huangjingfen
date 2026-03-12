<template>
  <div>
    <Card :bordered="false" dis-hover class="ivu-mt">
      <p slot="title">会员等级配置</p>
      <Spin v-if="loading" fix />
      <Form
        v-else
        ref="configForm"
        :model="formData"
        :label-width="0"
        class="hjf-member-config-form"
        @submit.native.prevent
      >
        <!-- 表头 -->
        <Row class="level-table-head" type="flex" align="middle">
          <Col :span="3" class="col-center">等级</Col>
          <Col :span="4" class="col-center">等级名称</Col>
          <Col :span="4" class="col-center">升级条件<br><span class="col-sub">（直推报单人数）</span></Col>
          <Col :span="4" class="col-center">直推奖励<br><span class="col-sub">（元/单）</span></Col>
          <Col :span="4" class="col-center">伞下奖励比例<br><span class="col-sub">（%）</span></Col>
          <Col :span="3" class="col-center">是否启用</Col>
        </Row>

        <!-- 各等级行 -->
        <Row
          v-for="(item, idx) in formData.levels"
          :key="item.level"
          class="level-table-row"
          type="flex"
          align="middle"
        >
          <!-- 等级 Tag -->
          <Col :span="3" class="col-center">
            <Tag :color="levelColor(item.level)">Lv.{{ item.level }}</Tag>
          </Col>

          <!-- 等级名称（只读） -->
          <Col :span="4" class="col-center level-name">{{ item.name }}</Col>

          <!-- 升级条件 -->
          <Col :span="4" class="col-center">
            <FormItem
              :prop="`levels.${idx}.require_orders`"
              :rules="item.level === 0 ? [] : requireOrdersRules"
              class="inline-form-item"
            >
              <template v-if="item.level === 0">
                <span class="text-muted">默认等级</span>
              </template>
              <template v-else>
                <InputNumber
                  v-model="item.require_orders"
                  :min="1"
                  :max="99999"
                  :step="10"
                  :precision="0"
                  style="width: 100px"
                />
                <span class="unit-label">人</span>
              </template>
            </FormItem>
          </Col>

          <!-- 直推奖励 -->
          <Col :span="4" class="col-center">
            <FormItem
              :prop="`levels.${idx}.direct_reward`"
              :rules="rewardRules"
              class="inline-form-item"
            >
              <InputNumber
                v-model="item.direct_reward"
                :min="0"
                :max="999999"
                :step="100"
                :precision="0"
                style="width: 110px"
              />
            </FormItem>
          </Col>

          <!-- 伞下奖励比例 -->
          <Col :span="4" class="col-center">
            <FormItem
              :prop="`levels.${idx}.umbrella_reward_rate`"
              :rules="item.level === 0 ? [] : rateRules"
              class="inline-form-item"
            >
              <template v-if="item.level === 0">
                <span class="text-muted">—</span>
              </template>
              <template v-else>
                <InputNumber
                  v-model="item.umbrella_reward_rate"
                  :min="0"
                  :max="100"
                  :step="1"
                  :precision="1"
                  style="width: 90px"
                />
                <span class="unit-label">%</span>
              </template>
            </FormItem>
          </Col>

          <!-- 是否启用 -->
          <Col :span="3" class="col-center">
            <i-switch v-model="item.enabled" size="large">
              <span slot="open">启用</span>
              <span slot="close">停用</span>
            </i-switch>
          </Col>
        </Row>

        <!-- 操作按钮 -->
        <Row class="ivu-mt-16">
          <Col :span="24">
            <Button type="primary" :loading="saving" @click="handleSave">保存配置</Button>
            <Button class="ivu-ml-8" @click="handleReset">重置</Button>
          </Col>
        </Row>
      </Form>
    </Card>
  </div>
</template>

<script>
import { memberConfigGetApi, memberConfigSaveApi } from '@/api/hjfMember.js';

const LEVEL_COLORS = { 0: 'default', 1: 'blue', 2: 'green', 3: 'orange', 4: 'red' };

export default {
  name: 'HjfMemberConfig',

  data() {
    return {
      loading: false,
      saving: false,

      formData: {
        levels: []
      },

      originalLevels: [],

      requireOrdersRules: [
        { required: true, type: 'number', message: '请填写升级所需人数', trigger: 'change' },
        { type: 'number', min: 1, message: '升级人数至少为 1', trigger: 'change' }
      ],

      rewardRules: [
        { required: true, type: 'number', message: '请填写直推奖励金额', trigger: 'change' },
        { type: 'number', min: 0, message: '奖励金额不能为负', trigger: 'change' }
      ],

      rateRules: [
        { required: true, type: 'number', message: '请填写伞下奖励比例', trigger: 'change' },
        { type: 'number', min: 0, max: 100, message: '比例须在 0～100 之间', trigger: 'change' }
      ]
    };
  },

  mounted() {
    this.getConfig();
  },

  methods: {
    levelColor(level) {
      return LEVEL_COLORS[level] || 'default';
    },

    getConfig() {
      this.loading = true;
      memberConfigGetApi()
        .then(res => {
          if (res && res.data && Array.isArray(res.data.levels)) {
            const levels = res.data.levels.map(l => ({ ...l }));
            this.formData.levels = levels;
            this.originalLevels = levels.map(l => ({ ...l }));
          }
        })
        .catch(() => {
          this.$Message.error('获取会员配置失败，请刷新重试');
        })
        .finally(() => {
          this.loading = false;
        });
    },

    handleSave() {
      this.$refs.configForm.validate(valid => {
        if (!valid) return;
        this.saving = true;
        memberConfigSaveApi({ levels: this.formData.levels.map(l => ({ ...l })) })
          .then(() => {
            this.$Message.success('会员配置保存成功');
            this.originalLevels = this.formData.levels.map(l => ({ ...l }));
          })
          .catch(() => {
            this.$Message.error('保存失败，请重试');
          })
          .finally(() => {
            this.saving = false;
          });
      });
    },

    handleReset() {
      this.formData.levels = this.originalLevels.map(l => ({ ...l }));
      this.$refs.configForm.resetFields();
    }
  }
};
</script>

<style scoped>
.hjf-member-config-form {
  padding: 4px 0;
}

.level-table-head {
  background: #f8f8f9;
  border: 1px solid #e8eaec;
  border-bottom: none;
  padding: 10px 0;
  font-weight: 600;
  font-size: 13px;
  color: #515a6e;
}

.level-table-row {
  border: 1px solid #e8eaec;
  border-bottom: none;
  padding: 10px 0;
  transition: background 0.15s;
}

.level-table-row:last-of-type {
  border-bottom: 1px solid #e8eaec;
}

.level-table-row:hover {
  background: #f0faff;
}

.col-center {
  text-align: center;
  line-height: 1.5;
}

.col-sub {
  font-size: 11px;
  color: #999;
  font-weight: 400;
}

.level-name {
  font-weight: 500;
  color: #17233d;
}

.inline-form-item {
  margin-bottom: 0 !important;
}

.unit-label {
  margin-left: 4px;
  color: #515a6e;
  font-size: 13px;
}

.text-muted {
  color: #bbb;
}
</style>
