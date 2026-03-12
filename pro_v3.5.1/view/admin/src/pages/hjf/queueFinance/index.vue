<template>
  <!-- 公排财务流水 -->
  <div>
    <!-- 统计卡片 -->
    <Card :bordered="false" dis-hover class="ivu-mt">
      <div class="stat-bar">
        <div class="stat-item">
          <span class="stat-label">累计退款总额</span>
          <span class="stat-value">¥{{ totalRefund }}</span>
        </div>
        <div class="stat-item">
          <span class="stat-label">退款笔数</span>
          <span class="stat-count">{{ total }} 笔</span>
        </div>
      </div>
    </Card>

    <!-- 搜索区 -->
    <Card :bordered="false" dis-hover class="ivu-mt" :padding="0">
      <div class="new_card_pd">
        <Form
          ref="formValidate"
          :label-width="labelWidth"
          :label-position="labelPosition"
          inline
          class="tabform"
          @submit.native.prevent
        >
          <FormItem label="订单号：">
            <Input
              v-model="formValidate.order_id"
              placeholder="请输入订单号"
              class="input-add"
              clearable
            />
          </FormItem>
          <FormItem label="昵称/ID：">
            <Input
              v-model="formValidate.keyword"
              placeholder="请输入用户昵称或ID"
              class="input-add"
              clearable
            />
          </FormItem>
          <FormItem label="退款时间：">
            <DatePicker
              :editable="false"
              :value="timeVal"
              format="yyyy/MM/dd HH:mm"
              type="datetimerange"
              placement="bottom-start"
              placeholder="自定义时间范围"
              class="input-add"
              :options="dateOptions"
              @on-change="onChangeTime"
            />
          </FormItem>
          <FormItem>
            <Button type="primary" class="mr14" @click="handleSearch">查询</Button>
            <Button @click="handleReset">重置</Button>
          </FormItem>
        </Form>
      </div>
    </Card>

    <!-- 数据表格 -->
    <Card :bordered="false" dis-hover class="ivu-mt">
      <Table
        ref="table"
        :columns="columns"
        :data="tabList"
        :loading="loading"
        no-data-text="暂无数据"
        no-filtered-data-text="暂无筛选结果"
      >
        <!-- 订单号列 -->
        <template slot-scope="{ row }" slot="order_id">
          <span class="order-id">{{ row.order_id }}</span>
        </template>

        <!-- 用户信息列 -->
        <template slot-scope="{ row }" slot="user">
          <div class="user-info">
            <div class="user-name">{{ row.nickname }}</div>
            <div class="user-meta">
              <span class="user-phone">{{ row.phone }}</span>
              <span class="user-id">UID: {{ row.uid }}</span>
            </div>
          </div>
        </template>

        <!-- 退款金额列 -->
        <template slot-scope="{ row }" slot="amount">
          <span class="amount-text">¥{{ Number(row.amount).toFixed(2) }}</span>
        </template>

        <!-- 退款时间列 -->
        <template slot-scope="{ row }" slot="refund_time">
          <span v-if="row.refund_time">{{ row.refund_time }}</span>
          <span v-else class="text-muted">—</span>
        </template>

        <!-- 操作人列 -->
        <template slot-scope="{ row }" slot="operator">
          <span v-if="row.operator">{{ row.operator }}</span>
          <span v-else class="text-muted">—</span>
        </template>
      </Table>

      <!-- 分页 -->
      <div class="acea-row row-right page">
        <Page
          :total="total"
          :current="formValidate.page"
          :page-size="formValidate.limit"
          show-elevator
          show-total
          @on-change="pageChange"
        />
      </div>
    </Card>
  </div>
</template>

<script>
/**
 * @module pages/hjf/queueFinance/index
 * @description 公排财务流水页面
 *
 * 功能：
 * - 顶部展示累计退款总额与退款笔数统计
 * - 展示公排退款流水列表（分页 + 筛选）
 * - 支持按订单号、用户昵称/ID、退款时间范围搜索
 * - 列表字段：订单号、用户信息、金额、退款时间、操作人
 *
 * 数据来源：`@/api/hjfQueue.js` → `queueFinanceListApi()`
 * Phase 1 使用 Mock 数据（MOCK_QUEUE_FINANCE）；Phase 4 集成时将 `USE_MOCK` 改为 false
 *
 * 路由：`/admin/hjf/queue/finance`
 * 权限标识：`hjf-queue-finance`
 */
import { mapState } from 'vuex';
import { queueFinanceListApi } from '@/api/hjfQueue.js';

/** 快捷日期选项（今天 / 近7天 / 近1个月） */
const DATE_SHORTCUTS = [
  {
    text: '今天',
    value() {
      const d = new Date();
      d.setHours(0, 0, 0, 0);
      return [d, new Date()];
    }
  },
  {
    text: '近7天',
    value() {
      const end = new Date();
      const start = new Date();
      start.setDate(start.getDate() - 6);
      start.setHours(0, 0, 0, 0);
      return [start, end];
    }
  },
  {
    text: '近1个月',
    value() {
      const end = new Date();
      const start = new Date();
      start.setMonth(start.getMonth() - 1);
      start.setHours(0, 0, 0, 0);
      return [start, end];
    }
  }
];

export default {
  name: 'HjfQueueFinance',

  data() {
    return {
      /** @type {string[]} 日期选择器当前值 [startTime, endTime] */
      timeVal: [],

      /** @type {number} 列表总条数，用于分页组件 */
      total: 0,

      /** @type {string} 累计退款总额，来自接口 total_refund 字段 */
      totalRefund: '0.00',

      /** @type {boolean} 表格加载状态 */
      loading: false,

      /** @type {Array<Object>} 表格数据行 */
      tabList: [],

      /**
       * 搜索表单及分页参数
       * @property {string} order_id    - 订单号
       * @property {string} keyword     - 用户昵称或 UID 关键词
       * @property {string} date_range  - 退款时间范围，格式 "startTime-endTime"
       * @property {number} page        - 当前页码
       * @property {number} limit       - 每页条数
       */
      formValidate: {
        order_id: '',
        keyword: '',
        date_range: '',
        page: 1,
        limit: 20
      },

      /** @type {Object} DatePicker 快捷选项配置 */
      dateOptions: { shortcuts: DATE_SHORTCUTS },

      /** @type {Array<Object>} 表格列定义 */
      columns: [
        {
          title: '订单号',
          slot: 'order_id',
          minWidth: 190
        },
        {
          title: '用户信息',
          slot: 'user',
          minWidth: 200
        },
        {
          title: '金额',
          slot: 'amount',
          minWidth: 130
        },
        {
          title: '退款时间',
          slot: 'refund_time',
          minWidth: 170
        },
        {
          title: '操作人',
          slot: 'operator',
          minWidth: 120
        }
      ]
    };
  },

  computed: {
    ...mapState('admin/layout', ['isMobile']),

    /** @returns {number|undefined} 表单标签宽度，移动端不设固定宽 */
    labelWidth() {
      return this.isMobile ? undefined : 80;
    },

    /** @returns {string} 表单标签对齐方式 */
    labelPosition() {
      return this.isMobile ? 'top' : 'right';
    }
  },

  mounted() {
    this.getList();
  },

  methods: {
    /**
     * 获取公排财务流水列表
     * @returns {void}
     */
    getList() {
      this.loading = true;
      queueFinanceListApi(this.formValidate)
        .then(res => {
          const data = res.data;
          this.tabList = data.list || [];
          this.total = data.count || 0;
          this.totalRefund = data.total_refund
            ? Number(data.total_refund).toFixed(2)
            : '0.00';
        })
        .catch(err => {
          this.$Message.error((err && err.msg) || '加载失败，请重试');
        })
        .finally(() => {
          this.loading = false;
        });
    },

    /**
     * 点击「查询」按钮：重置到第1页并刷新列表
     * @returns {void}
     */
    handleSearch() {
      this.formValidate.page = 1;
      this.getList();
    },

    /**
     * 点击「重置」按钮：清空所有搜索条件并刷新
     * @returns {void}
     */
    handleReset() {
      this.timeVal = [];
      this.formValidate.order_id = '';
      this.formValidate.keyword = '';
      this.formValidate.date_range = '';
      this.formValidate.page = 1;
      this.getList();
    },

    /**
     * DatePicker 变更回调
     * @param {string[]} e - [startTimeStr, endTimeStr]
     * @returns {void}
     */
    onChangeTime(e) {
      this.timeVal = e;
      this.formValidate.date_range = e[0] ? e.join('-') : '';
      this.formValidate.page = 1;
      this.getList();
    },

    /**
     * 分页器页码变更回调
     * @param {number} index - 跳转的目标页码
     * @returns {void}
     */
    pageChange(index) {
      this.formValidate.page = index;
      this.getList();
    }
  }
};
</script>

<style scoped lang="stylus">
.stat-bar
  display flex
  align-items center
  gap 48px
  padding 8px 4px

.stat-item
  display flex
  flex-direction column
  gap 4px

.stat-label
  font-size 13px
  color #999

.stat-value
  font-size 24px
  font-weight 600
  color #ed4014

.stat-count
  font-size 20px
  font-weight 500
  color #2d2d2d

.order-id
  font-size 12px
  color #515a6e

.user-info
  line-height 1.4

.user-name
  font-weight 500
  color #2d2d2d

.user-meta
  font-size 12px
  color #999

  .user-phone
    margin-right 8px

.amount-text
  font-weight 500
  color #ed4014

.text-muted
  color #bbb

.tabform .ivu-form-item
  margin-bottom 10px

.page
  margin-top 16px
</style>
