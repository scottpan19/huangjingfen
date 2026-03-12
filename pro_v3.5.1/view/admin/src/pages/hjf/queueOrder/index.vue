<template>
  <!-- 公排订单管理 -->
  <div>
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
          <FormItem label="昵称/ID：">
            <Input
              v-model="formValidate.keyword"
              placeholder="请输入用户昵称或ID"
              class="input-add"
              clearable
            />
          </FormItem>
          <FormItem label="状态：">
            <Select
              v-model="formValidate.status"
              placeholder="全部状态"
              class="input-add"
              clearable
            >
              <Option :value="0">排队中</Option>
              <Option :value="1">已退款</Option>
            </Select>
          </FormItem>
          <FormItem label="加入时间：">
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

        <!-- 金额列 -->
        <template slot-scope="{ row }" slot="amount">
          <span class="amount-text">¥{{ Number(row.amount).toFixed(2) }}</span>
        </template>

        <!-- 状态列 -->
        <template slot-scope="{ row }" slot="status">
          <Tag :color="row.status === 0 ? 'green' : 'default'">
            {{ row.status_text }}
          </Tag>
        </template>

        <!-- 退款时间列 -->
        <template slot-scope="{ row }" slot="refund_time">
          <span v-if="row.refund_time">{{ row.refund_time }}</span>
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
 * @module pages/hjf/queueOrder/index
 * @description 公排订单管理页面
 *
 * 功能：
 * - 展示公排订单列表（分页 + 筛选）
 * - 支持按用户昵称/ID、状态、加入时间范围搜索
 * - 列表字段：订单ID、用户信息、金额、排队序号、状态、退款时间、加入时间
 *
 * 数据来源：`@/api/hjfQueue.js` → `queueOrderList()`
 * Phase 1 使用 Mock 数据；Phase 4 集成时将 `USE_MOCK` 改为 false
 *
 * 路由：`/admin/hjf/queue/order`
 * 权限标识：`hjf-queue-order`
 *
 * @see docs/frontend-new-pages-spec.md §5.2.4
 * @see pro_v3.5.1/view/admin/src/pages/finance/commission/index.vue 参考模式
 */
import { mapState } from 'vuex';
import { queueOrderListApi } from '@/api/hjfQueue.js';

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
  name: 'HjfQueueOrder',

  data() {
    return {
      /** @type {string[]} 日期选择器当前值 [startTime, endTime] */
      timeVal: [],

      /** @type {number} 列表总条数，用于分页组件 */
      total: 0,

      /** @type {boolean} 表格加载状态 */
      loading: false,

      /** @type {Array<Object>} 表格数据行 */
      tabList: [],

      /**
       * 搜索表单及分页参数
       * @property {string}  keyword   - 用户昵称或 UID 关键词
       * @property {number|string} status - 排队状态：0 排队中 / 1 已退款 / '' 全部
       * @property {string}  date_range - 加入时间范围，格式 "startTime-endTime"
       * @property {number}  page      - 当前页码
       * @property {number}  limit     - 每页条数
       */
      formValidate: {
        keyword: '',
        status: '',
        date_range: '',
        page: 1,
        limit: 20
      },

      /** @type {Object} DatePicker 快捷选项配置 */
      dateOptions: { shortcuts: DATE_SHORTCUTS },

      /**
       * 表格列定义
       * @type {Array<Object>}
       */
      columns: [
        {
          title: '用户信息',
          slot: 'user',
          minWidth: 200
        },
        {
          title: '订单号',
          key: 'order_id',
          minWidth: 190
        },
        {
          title: '金额',
          slot: 'amount',
          minWidth: 110
        },
        {
          title: '排队序号',
          key: 'queue_no',
          minWidth: 110
        },
        {
          title: '状态',
          slot: 'status',
          minWidth: 100
        },
        {
          title: '退款时间',
          slot: 'refund_time',
          minWidth: 170
        },
        {
          title: '加入时间',
          key: 'add_time',
          minWidth: 170
        }
      ]
    };
  },

  computed: {
    ...mapState('admin/layout', ['isMobile']),

    /** @returns {number|undefined} 表单标签宽度，移动端不设固定宽 */
    labelWidth() {
      return this.isMobile ? undefined : 90;
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
     * 获取公排订单列表
     * 调用 `queueOrderList` API，将结果填充到 `tabList`，更新 `total`
     * @returns {void}
     */
    getList() {
      this.loading = true;
      queueOrderListApi(this.formValidate)
        .then(res => {
          const data = res.data;
          this.tabList = data.list || [];
          this.total = data.count || 0;
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
      this.formValidate.keyword = '';
      this.formValidate.status = '';
      this.formValidate.date_range = '';
      this.formValidate.page = 1;
      this.getList();
    },

    /**
     * DatePicker 变更回调：更新 date_range 参数并触发查询
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
