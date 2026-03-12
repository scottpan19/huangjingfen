<template>
  <!-- 会员等级管理 -->
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
          <FormItem label="昵称/手机号：">
            <Input
              v-model="formValidate.keyword"
              placeholder="请输入昵称或手机号"
              class="input-add"
              clearable
            />
          </FormItem>
          <FormItem label="会员等级：">
            <Select
              v-model="formValidate.member_level"
              placeholder="全部等级"
              class="input-add"
              clearable
            >
              <Option :value="0">普通会员</Option>
              <Option :value="1">创客</Option>
              <Option :value="2">云店</Option>
              <Option :value="3">服务商</Option>
              <Option :value="4">分公司</Option>
            </Select>
          </FormItem>
          <FormItem label="不考核：">
            <Select
              v-model="formValidate.no_assess"
              placeholder="全部"
              class="input-add"
              clearable
            >
              <Option :value="0">正常考核</Option>
              <Option :value="1">不考核</Option>
            </Select>
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
            <div v-if="row.spread_nickname" class="user-spread">
              推荐人：{{ row.spread_nickname }}
            </div>
          </div>
        </template>

        <!-- 会员等级列 -->
        <template slot-scope="{ row }" slot="member_level">
          <Tag :color="LEVEL_COLORS[row.member_level] || 'default'">
            {{ row.member_level_name }}
          </Tag>
        </template>

        <!-- 不考核状态列 -->
        <template slot-scope="{ row }" slot="no_assess">
          <Tag :color="row.no_assess === 1 ? 'orange' : 'green'">
            {{ row.no_assess === 1 ? '不考核' : '正常' }}
          </Tag>
        </template>

        <!-- 积分余额列 -->
        <template slot-scope="{ row }" slot="points">
          <div class="points-info">
            <div>待释放：<span class="points-frozen">{{ row.frozen_points }}</span></div>
            <div>已释放：<span class="points-avail">{{ row.available_points }}</span></div>
          </div>
        </template>

        <!-- 现金余额列 -->
        <template slot-scope="{ row }" slot="now_money">
          <span class="money-text">¥{{ Number(row.now_money).toFixed(2) }}</span>
        </template>

        <!-- 操作列 -->
        <template slot-scope="{ row }" slot="action">
          <a class="mr10" @click="handleViewDetail(row)">查看详情</a>
          <a class="mr10" @click="openLevelModal(row)">调整等级</a>
          <a @click="openNoAssessModal(row)">设置不考核</a>
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

    <!-- 查看详情弹窗 -->
    <Modal
      v-model="detailModal.visible"
      title="会员详情"
      :footer-hide="true"
      width="480"
    >
      <div v-if="detailModal.row" class="detail-body">
        <div class="detail-row">
          <span class="detail-label">昵称</span>
          <span>{{ detailModal.row.nickname }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">手机号</span>
          <span>{{ detailModal.row.phone }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">UID</span>
          <span>{{ detailModal.row.uid }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">会员等级</span>
          <Tag :color="LEVEL_COLORS[detailModal.row.member_level] || 'default'">
            {{ detailModal.row.member_level_name }}
          </Tag>
        </div>
        <div class="detail-row">
          <span class="detail-label">考核状态</span>
          <Tag :color="detailModal.row.no_assess === 1 ? 'orange' : 'green'">
            {{ detailModal.row.no_assess === 1 ? '不考核' : '正常考核' }}
          </Tag>
        </div>
        <div class="detail-row">
          <span class="detail-label">推荐人</span>
          <span>{{ detailModal.row.spread_nickname || '—' }}（UID: {{ detailModal.row.spread_uid || '—' }}）</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">直推人数</span>
          <span>{{ detailModal.row.direct_count }} 人</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">伞下订单数</span>
          <span>{{ detailModal.row.umbrella_orders }} 单</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">待释放积分</span>
          <span class="points-frozen">{{ detailModal.row.frozen_points }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">已释放积分</span>
          <span class="points-avail">{{ detailModal.row.available_points }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">现金余额</span>
          <span class="money-text">¥{{ Number(detailModal.row.now_money).toFixed(2) }}</span>
        </div>
      </div>
    </Modal>

    <!-- 调整等级弹窗 -->
    <Modal
      v-model="levelModal.visible"
      title="调整会员等级"
      :loading="levelModal.loading"
      @on-ok="confirmLevelChange"
    >
      <Form :label-width="90">
        <FormItem label="当前等级：">
          <Tag v-if="levelModal.row" :color="LEVEL_COLORS[levelModal.row.member_level] || 'default'">
            {{ levelModal.row && levelModal.row.member_level_name }}
          </Tag>
        </FormItem>
        <FormItem label="调整为：">
          <Select v-model="levelModal.newLevel" placeholder="请选择新等级">
            <Option :value="0">普通会员</Option>
            <Option :value="1">创客</Option>
            <Option :value="2">云店</Option>
            <Option :value="3">服务商</Option>
            <Option :value="4">分公司</Option>
          </Select>
        </FormItem>
      </Form>
    </Modal>

    <!-- 设置不考核弹窗 -->
    <Modal
      v-model="noAssessModal.visible"
      title="设置不考核"
      :loading="noAssessModal.loading"
      @on-ok="confirmNoAssess"
    >
      <Form :label-width="90">
        <FormItem label="用户：">
          <span>{{ noAssessModal.row && noAssessModal.row.nickname }}</span>
        </FormItem>
        <FormItem label="当前状态：">
          <Tag v-if="noAssessModal.row" :color="noAssessModal.row.no_assess === 1 ? 'orange' : 'green'">
            {{ noAssessModal.row && (noAssessModal.row.no_assess === 1 ? '不考核' : '正常考核') }}
          </Tag>
        </FormItem>
        <FormItem label="设置为：">
          <RadioGroup v-model="noAssessModal.newStatus">
            <Radio :label="0">正常考核</Radio>
            <Radio :label="1">不考核</Radio>
          </RadioGroup>
        </FormItem>
      </Form>
    </Modal>
  </div>
</template>

<script>
/**
 * @module pages/hjf/memberLevel/index
 * @description 会员等级管理页面
 *
 * 功能：
 * - 展示会员列表（分页 + 多条件筛选）
 * - 列表字段：用户信息、会员等级、直推人数、伞下订单数、积分余额、现金余额、不考核状态、操作
 * - 支持按昵称/手机号、会员等级、不考核状态搜索
 * - 操作：查看详情弹窗、调整会员等级、设置/取消不考核
 *
 * 数据来源：`@/api/hjfMember.js` → `memberList()`、`memberSetLevel()`、`memberSetNoAssess()`
 * Phase 1 使用 Mock 数据；Phase 4 集成时将 `USE_MOCK` 改为 false
 *
 * 路由：`/admin/hjf/member/level`
 * 权限标识：`hjf-member-level`
 *
 * @see docs/frontend-new-pages-spec.md §5.2.9
 * @see pro_v3.5.1/view/admin/src/pages/finance/commission/index.vue 参考模式
 */
import { mapState } from 'vuex';
import { memberList, memberSetLevel, memberSetNoAssess } from '@/api/hjfMember.js';

/**
 * 会员等级 → iView Tag 颜色映射
 * @type {Object.<number, string>}
 */
const LEVEL_COLORS = {
  0: 'default',
  1: 'blue',
  2: 'green',
  3: 'gold',
  4: 'red'
};

/**
 * 会员等级名称列表，下标即等级值
 * @type {string[]}
 */
const LEVEL_NAMES = ['普通会员', '创客', '云店', '服务商', '分公司'];

export default {
  name: 'HjfMemberLevel',

  data() {
    return {
      /**
       * 等级颜色映射（供模板访问）
       * @type {Object.<number, string>}
       */
      LEVEL_COLORS,

      /** @type {number} 列表总条数，用于分页组件 */
      total: 0,

      /** @type {boolean} 表格加载状态 */
      loading: false,

      /** @type {Array<Object>} 表格数据行 */
      tabList: [],

      /**
       * 搜索表单及分页参数
       * @property {string}         keyword      - 昵称或手机号关键词
       * @property {number|string}  member_level - 等级筛选：0-4 / '' 全部
       * @property {number|string}  no_assess    - 不考核筛选：0 正常 / 1 不考核 / '' 全部
       * @property {number}         page         - 当前页码
       * @property {number}         limit        - 每页条数
       */
      formValidate: {
        keyword: '',
        member_level: '',
        no_assess: '',
        page: 1,
        limit: 20
      },

      /**
       * 查看详情弹窗状态
       * @property {boolean} visible - 是否显示
       * @property {Object|null} row - 当前行数据
       */
      detailModal: {
        visible: false,
        row: null
      },

      /**
       * 调整等级弹窗状态
       * @property {boolean}    visible  - 是否显示
       * @property {boolean}    loading  - 确认按钮加载中
       * @property {Object|null} row     - 当前操作行
       * @property {number|string} newLevel - 选择的新等级
       */
      levelModal: {
        visible: false,
        loading: false,
        row: null,
        newLevel: ''
      },

      /**
       * 设置不考核弹窗状态
       * @property {boolean}    visible    - 是否显示
       * @property {boolean}    loading    - 确认按钮加载中
       * @property {Object|null} row       - 当前操作行
       * @property {number}     newStatus  - 选择的新状态：0 正常 / 1 不考核
       */
      noAssessModal: {
        visible: false,
        loading: false,
        row: null,
        newStatus: 0
      },

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
          title: '会员等级',
          slot: 'member_level',
          minWidth: 110
        },
        {
          title: '直推人数',
          key: 'direct_count',
          minWidth: 100
        },
        {
          title: '伞下订单数',
          key: 'umbrella_orders',
          minWidth: 110
        },
        {
          title: '积分余额',
          slot: 'points',
          minWidth: 160
        },
        {
          title: '现金余额',
          slot: 'now_money',
          minWidth: 110
        },
        {
          title: '考核状态',
          slot: 'no_assess',
          minWidth: 100
        },
        {
          title: '操作',
          slot: 'action',
          minWidth: 200,
          fixed: 'right'
        }
      ]
    };
  },

  computed: {
    ...mapState('admin/layout', ['isMobile']),

    /** @returns {number|undefined} 表单标签宽度，移动端不设固定宽 */
    labelWidth() {
      return this.isMobile ? undefined : 96;
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
     * 获取会员列表
     * 调用 `memberList` API，将结果填充到 `tabList`，更新 `total`
     * @returns {void}
     */
    getList() {
      this.loading = true;
      memberList(this.formValidate)
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
      this.formValidate.keyword = '';
      this.formValidate.member_level = '';
      this.formValidate.no_assess = '';
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
    },

    /**
     * 打开查看详情弹窗
     * @param {Object} row - 当前行数据
     * @returns {void}
     */
    handleViewDetail(row) {
      this.detailModal.row = row;
      this.detailModal.visible = true;
    },

    /**
     * 打开调整等级弹窗，预填当前等级
     * @param {Object} row - 当前行数据
     * @returns {void}
     */
    openLevelModal(row) {
      this.levelModal.row = row;
      this.levelModal.newLevel = row.member_level;
      this.levelModal.loading = false;
      this.levelModal.visible = true;
    },

    /**
     * 确认调整会员等级
     * 调用 `memberSetLevel`，成功后更新本地行数据并关闭弹窗
     * @returns {void}
     */
    confirmLevelChange() {
      const { row, newLevel } = this.levelModal;
      if (newLevel === '' || newLevel === null) {
        this.$Message.warning('请选择要调整的等级');
        this.levelModal.loading = false;
        return;
      }
      if (newLevel === row.member_level) {
        this.levelModal.visible = false;
        return;
      }
      this.levelModal.loading = true;
      memberSetLevel(row.uid, newLevel)
        .then(() => {
          this.$Message.success('等级调整成功');
          row.member_level = newLevel;
          row.member_level_name = LEVEL_NAMES[newLevel];
          this.levelModal.visible = false;
        })
        .catch(err => {
          this.$Message.error((err && err.msg) || '操作失败，请重试');
          this.levelModal.loading = false;
        });
    },

    /**
     * 打开设置不考核弹窗，预填当前状态
     * @param {Object} row - 当前行数据
     * @returns {void}
     */
    openNoAssessModal(row) {
      this.noAssessModal.row = row;
      this.noAssessModal.newStatus = row.no_assess;
      this.noAssessModal.loading = false;
      this.noAssessModal.visible = true;
    },

    /**
     * 确认设置不考核
     * 调用 `memberSetNoAssess`，成功后更新本地行数据并关闭弹窗
     * @returns {void}
     */
    confirmNoAssess() {
      const { row, newStatus } = this.noAssessModal;
      if (newStatus === row.no_assess) {
        this.noAssessModal.visible = false;
        return;
      }
      this.noAssessModal.loading = true;
      memberSetNoAssess(row.uid, newStatus)
        .then(() => {
          this.$Message.success('设置成功');
          row.no_assess = newStatus;
          this.noAssessModal.visible = false;
        })
        .catch(err => {
          this.$Message.error((err && err.msg) || '操作失败，请重试');
          this.noAssessModal.loading = false;
        });
    }
  }
};
</script>

<style scoped lang="stylus">
.user-info
  line-height 1.5

.user-name
  font-weight 500
  color #2d2d2d

.user-meta
  font-size 12px
  color #999

  .user-phone
    margin-right 8px

.user-spread
  font-size 12px
  color #aaa

.points-info
  font-size 12px
  line-height 1.8

.points-frozen
  color #fa8c16
  font-weight 500

.points-avail
  color #52c41a
  font-weight 500

.money-text
  font-weight 500
  color #ed4014

.detail-body
  padding 0 8px

.detail-row
  display flex
  align-items center
  padding 8px 0
  border-bottom 1px solid #f5f5f5

  &:last-child
    border-bottom none

.detail-label
  width 90px
  flex-shrink 0
  color #888
  font-size 13px

.tabform .ivu-form-item
  margin-bottom 10px

.mr10
  margin-right 10px

.page
  margin-top 16px
</style>
