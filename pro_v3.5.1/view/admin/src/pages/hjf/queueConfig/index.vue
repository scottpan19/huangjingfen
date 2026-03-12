<template>
  <div>
    <Card :bordered="false" dis-hover class="ivu-mt">
      <p slot="title">公排参数配置</p>
      <Spin v-if="loading" fix />
      <Form
        v-else
        ref="configForm"
        :model="formData"
        :rules="formRules"
        :label-width="200"
        label-position="right"
        class="hjf-config-form"
        @submit.native.prevent
      >
        <FormItem label="触发倍数（进N退1）：" prop="trigger_multiple">
          <InputNumber
            v-model="formData.trigger_multiple"
            :min="2"
            :max="100"
            :step="1"
            :precision="0"
            style="width: 160px"
          />
          <span class="form-tip">新进 N 单后退还最早 1 单，默认 4</span>
        </FormItem>

        <FormItem label="退款周期（天）：" prop="refund_cycle">
          <InputNumber
            v-model="formData.refund_cycle"
            :min="1"
            :max="365"
            :step="1"
            :precision="0"
            style="width: 160px"
          />
          <span class="form-tip">触发退款后资金到账的等待天数</span>
        </FormItem>

        <FormItem label="是否启用公排：">
          <i-switch
            v-model="formData.enabled"
            size="large"
          >
            <span slot="open">启用</span>
            <span slot="close">停用</span>
          </i-switch>
          <span class="form-tip">关闭后新订单不再加入公排队列</span>
        </FormItem>

        <FormItem>
          <Button
            type="primary"
            :loading="saving"
            @click="handleSave"
          >保存配置</Button>
          <Button class="ivu-ml-8" @click="handleReset">重置</Button>
        </FormItem>
      </Form>
    </Card>
  </div>
</template>

<script>
import { queueConfigGetApi, queueConfigSaveApi } from '@/api/hjfQueue.js';

export default {
  name: 'HjfQueueConfig',

  data() {
    return {
      loading: false,
      saving: false,
      formData: {
        trigger_multiple: 4,
        refund_cycle: 30,
        enabled: true
      },
      originalData: {},
      formRules: {
        trigger_multiple: [
          { required: true, type: 'number', message: '请输入触发倍数', trigger: 'change' },
          { type: 'number', min: 2, message: '触发倍数不能小于 2', trigger: 'change' }
        ],
        refund_cycle: [
          { required: true, type: 'number', message: '请输入退款周期', trigger: 'change' },
          { type: 'number', min: 1, message: '退款周期不能小于 1 天', trigger: 'change' }
        ]
      }
    };
  },

  mounted() {
    this.getConfig();
  },

  methods: {
    getConfig() {
      this.loading = true;
      queueConfigGetApi()
        .then(res => {
          if (res && res.data) {
            this.formData = {
              trigger_multiple: res.data.trigger_multiple,
              refund_cycle: res.data.refund_cycle,
              enabled: !!res.data.enabled
            };
            this.originalData = { ...this.formData };
          }
        })
        .catch(() => {
          this.$Message.error('获取配置失败，请刷新重试');
        })
        .finally(() => {
          this.loading = false;
        });
    },

    handleSave() {
      this.$refs.configForm.validate(valid => {
        if (!valid) return;
        this.saving = true;
        queueConfigSaveApi({ ...this.formData })
          .then(() => {
            this.$Message.success('配置保存成功');
            this.originalData = { ...this.formData };
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
      this.formData = { ...this.originalData };
      this.$refs.configForm.resetFields();
    }
  }
};
</script>

<style scoped>
.hjf-config-form {
  max-width: 720px;
  padding: 8px 0;
}

.form-tip {
  margin-left: 10px;
  color: #999;
  font-size: 12px;
}
</style>
