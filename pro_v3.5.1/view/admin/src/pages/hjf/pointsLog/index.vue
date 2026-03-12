<template>
  <!-- 积分释放日志 -->
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
          <FormItem label="类型：">
            <Select
              v-model="formValidate.type"
              placeholder="全部类型"
              class="input-add"
              clearable
            >
              <Option value="release">释放</Option>
              <Option value="reward">奖励</Option>
              <Option value="consume">消费</Option>
            </Select>
          </FormItem>
          <FormItem label="时间：">
            <DatePicker
              :editable="false"
              :value="timeVal"
              format="yyyy/MM/dd"
              type="daterange"
              placement="bottom-start"
              placeholder="自定义日期范围"
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

    <!-- 今日统计 -->
    <Card :bordered="false" dis-hover class="ivu-mt">
      <div class="statistics-bar">
        <div class="stat-item">
          <span class="stat-label">今日释放总量</span>
          <span class="stat-value stat-value--primary">{{ statistics.total_released_today | numFormat }}</span>
          <span class="stat-unit">积分</span>
        </div>
        <div class="stat-divider" />
        <div class="stat-item">
          <span class="stat-label">今日释放用户数</span>
          <span class="stat-value">{{ statistics.total_users_released | numFormat }}</span>
          <span class="stat-unit">人</span>
        </div>
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

        <!-- 积分数量列 -->
        <template slot-scope="{ row }" slot="points">
          <span :class="pointsClass(row.type)">{{ pointsPrefix(row.type) }}{{ row.points | numFormat }}</span>
        </template>

        <!-- 类型列 -->
        <template slot-scope="{ row }" slot="type">
          <Tag :color="typeColor(row.type)">{{ row.type_text }}</Tag>
        </template>

        <!-- 状态列 -->
        <template slot-scope="{ row }" slot="status">
          <Badge
            :status="row.status === 1 ? 'success' : 'processing'"
            :text="row.status_text"
          />
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
import { mapState } from 'vuex';
import { pointsReleaseLogApi } from '@/api/hjfPoints.js';

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
  name: 'HjfPointsLog',

  filters: {
    numFormat(val) {
      const n = Number(val);
      if (isNaN(n)) return '0';
      return n.toLocaleString('zh-CN');
    }
  },

  data() {
    return {
      timeVal: [],
      total: 0,
      loading: false,
      tabList: [],
      statistics: {
        total_released_today: 0,
        total_users_released: 0
      },
      formValidate: {
        keyword: '',
        type: '',
        date_range: '',
        page: 1,
        limit: 20
      },
      dateOptions: { shortcuts: DATE_SHORTCUTS },
      columns: [
        {
          title: '用户信息',
          slot: 'user',
          minWidth: 200
        },
        {
          title: '积分数量',
          slot: 'points',
          minWidth: 120
        },
        {
          title: '类型',
          slot: 'type',
          minWidth: 100
        },
        {
          title: '状态',
          slot: 'status',
          minWidth: 100
        },
        {
          title: '时间',
          key: 'add_time',
          minWidth: 170
        }
      ]
    };
  },

  computed: {
    ...mapState('admin/layout', ['isMobile']),

    labelWidth() {
      return this.isMobile ? undefined : 80;
    },

    labelPosition() {
      return this.isMobile ? 'top' : 'right';
    }
  },

  mounted() {
    this.getList();
  },

  methods: {
    getList() {
      this.loading = true;
      pointsReleaseLogApi(this.formValidate)
        .then(res => {
          const data = res.data;
          this.tabList = data.list || [];
          this.total = data.count || 0;
          if (data.statistics) {
            this.statistics = data.statistics;
          }
        })
        .catch(err => {
          this.$Message.error((err && err.msg) || '加载失败，请重试');
        })
        .finally(() => {
          this.loading = false;
        });
    },

    handleSearch() {
      this.formValidate.page = 1;
      this.getList();
    },

    handleReset() {
      this.timeVal = [];
      this.formValidate.keyword = '';
      this.formValidate.type = '';
      this.formValidate.date_range = '';
      this.formValidate.page = 1;
      this.getList();
    },

    onChangeTime(e) {
      this.timeVal = e;
      this.formValidate.date_range = e[0] ? e.join('-') : '';
      this.formValidate.page = 1;
      this.getList();
    },

    pageChange(index) {
      this.formValidate.page = index;
      this.getList();
    },

    typeColor(type) {
      const map = { release: 'green', reward: 'blue', consume: 'orange' };
      return map[type] || 'default';
    },

    pointsClass(type) {
      return type === 'consume' ? 'points-consume' : 'points-income';
    },

    pointsPrefix(type) {
      return type === 'consume' ? '-' : '+';
    }
  }
};
</script>

<style scoped lang="stylus">
.statistics-bar
  display flex
  align-items center
  padding 8px 0

.stat-item
  display flex
  align-items baseline
  gap 6px

.stat-label
  font-size 13px
  color #808695

.stat-value
  font-size 22px
  font-weight 600
  color #2d2d2d

.stat-value--primary
  color #19be6b

.stat-unit
  font-size 12px
  color #aaa

.stat-divider
  width 1px
  height 32px
  background #e8eaec
  margin 0 32px

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

.points-income
  font-weight 600
  color #19be6b

.points-consume
  font-weight 600
  color #ed4014

.tabform .ivu-form-item
  margin-bottom 10px

.page
  margin-top 16px
</style>
