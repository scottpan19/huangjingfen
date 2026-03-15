<template>
  <view :style="colorStyle" class="points-detail">
    <!-- Tab 筛选 -->
    <view class="nav acea-row">
      <view
        class="item"
        v-for="(tab, idx) in tabs"
        :key="idx"
        :class="activeTab === idx ? 'on' : ''"
        @click="changeTab(idx)"
      >{{ tab.label }}</view>
    </view>

    <!-- 按日期分组的记录列表 -->
    <view class="record-list">
      <view
        class="date-group"
        v-for="(group, gIndex) in groupedList"
        :key="gIndex"
      >
        <view class="date-label">{{ group.date }}</view>
        <view class="group-card">
          <view
            class="record-item acea-row row-between-wrapper"
            v-for="(item, iIndex) in group.children"
            :key="iIndex"
          >
            <!-- 左侧：标题 + 时间 + 状态标签 -->
            <view class="record-left">
              <view class="record-title line1">{{ item.title }}</view>
              <view class="record-meta acea-row">
                <text class="record-time">{{ item.add_time }}</text>
                <text
                  class="status-tag"
                  :class="item.status === 'frozen' ? 'frozen' : 'released'"
                >{{ item.status === 'frozen' ? '待释放' : '已释放' }}</text>
              </view>
            </view>

            <!-- 右侧：积分增减 -->
            <view
              class="points-amount"
              :class="item.pm === 1 ? 'add' : 'sub'"
            >{{ item.pm === 1 ? '+' : '-' }}{{ item.points }}</view>
          </view>
        </view>
      </view>

      <!-- 加载状态 -->
      <view class="loading-bar acea-row row-center-wrapper" v-if="flatList.length > 0">
        <text class="loading iconfont icon-jiazai" :hidden="!loading"></text>
        {{ loadTitle }}
      </view>

      <!-- 空状态 -->
      <view class="px-20 mt-20" v-if="flatList.length === 0 && !loading">
        <emptyPage title="暂无积分记录～" src="/statics/images/noOrder.gif"></emptyPage>
      </view>
    </view>
  </view>
</template>

<script>
import { getPointsDetail } from '@/api/hjfAssets.js';
import emptyPage from '@/components/emptyPage.vue';
import colors from '@/mixins/color';

/**
 * P17 积分明细页
 *
 * 展示当前用户的积分流水，支持5个 Tab 按类型筛选，
 * 记录按 add_time 日期字段分组展示，支持上拉翻页加载。
 *
 * @see docs/frontend-new-pages-spec.md
 */
export default {
  name: 'PointsDetail',

  components: { emptyPage },

  mixins: [colors],

  data() {
    return {
      /**
       * Tab 配置：5个筛选类型
       * @type {Array<{ label: string, type: string }>}
       */
      tabs: [
        { label: '全部',     type: '' },
        { label: '直推奖励', type: 'reward_direct' },
        { label: '伞下奖励', type: 'reward_umbrella' },
        { label: '每日释放', type: 'release' },
        { label: '消费',     type: 'consume' },
      ],

      /** 当前激活 Tab 索引 @type {number} */
      activeTab: 0,

      /** @type {Array<Object>} 原始记录列表（所有已加载页的合并） */
      flatList: [],

      /** @type {number} 当前页码（从 1 开始） */
      page: 1,

      /** @type {number} 每页条数 */
      limit: 15,

      /** @type {boolean} 是否正在加载 */
      loading: false,

      /** @type {boolean} 是否已加载完全部数据 */
      finished: false,

      /** @type {string} 底部加载提示文字 */
      loadTitle: '加载更多',
    };
  },

  computed: {
    /**
     * 按 add_time 日期前10位分组
     * @returns {Array<{ date: string, children: Object[] }>}
     */
    groupedList() {
      const map = {};
      const order = [];
      this.flatList.forEach(item => {
        const key = (item.add_time || '').substring(0, 10) || '未知日期';
        if (!map[key]) {
          map[key] = [];
          order.push(key);
        }
        map[key].push(item);
      });
      return order.map(date => ({ date, children: map[date] }));
    },
  },

  onLoad() {
    this.loadList();
  },

  onReachBottom() {
    this.loadList();
  },

  methods: {
    /**
     * 加载积分明细数据（分页追加）
     */
    loadList() {
      if (this.loading || this.finished) return;

      this.loading = true;
      this.loadTitle = '';

      const params = { page: this.page, limit: this.limit };
      const currentType = this.tabs[this.activeTab].type;
      if (currentType) params.type = currentType;

      getPointsDetail(params)
        .then(res => {
          const newItems = (res.data && res.data.list) ? res.data.list : [];
          newItems.forEach(item => this.flatList.push(item));

          const isEnd = newItems.length < this.limit;
          this.finished = isEnd;
          this.loadTitle = isEnd ? '没有更多内容啦~' : '加载更多';
          this.page += 1;
        })
        .catch(() => {
          this.loadTitle = '加载更多';
        })
        .finally(() => {
          this.loading = false;
        });
    },

    /**
     * 切换 Tab 筛选，重置列表并重新加载
     * @param {number} idx - 目标 Tab 索引
     */
    changeTab(idx) {
      if (idx === this.activeTab) return;
      this.activeTab = idx;
      this.flatList = [];
      this.page = 1;
      this.finished = false;
      this.loadList();
    },
  },
};
</script>

<style scoped lang="scss">
.points-detail {
  min-height: 100vh;
  background: #f5f5f5;
}

/* ===== Tab 导航 ===== */
.nav {
  background: #fff;
  height: 88rpx;
  width: 100%;
  position: sticky;
  top: 0;
  z-index: 10;
  overflow-x: auto;

  .item {
    flex: 1;
    min-width: 140rpx;
    text-align: center;
    font-size: 26rpx;
    color: #333;
    line-height: 88rpx;
    position: relative;
    white-space: nowrap;

    &.on {
      color: var(--view-theme);
      font-size: 28rpx;
      font-weight: 500;

      &::after {
        position: absolute;
        content: ' ';
        width: 48rpx;
        height: 6rpx;
        border-radius: 20rpx;
        background: var(--view-theme);
        bottom: 0;
        left: 50%;
        margin-left: -24rpx;
      }
    }
  }
}

/* ===== 记录列表 ===== */
.record-list {
  padding: 24rpx 24rpx 40rpx;
}

.date-group {
  margin-bottom: 24rpx;
}

.date-label {
  font-size: 24rpx;
  color: #999;
  margin-bottom: 12rpx;
  padding-left: 4rpx;
}

.group-card {
  background: #fff;
  border-radius: 16rpx;
  overflow: hidden;
}

/* ===== 单条记录 ===== */
.record-item {
  padding: 28rpx 30rpx;
  align-items: center;

  &:not(:last-child) {
    border-bottom: 1rpx solid #f2f2f2;
  }
}

.record-left {
  flex: 1;
  margin-right: 24rpx;
  overflow: hidden;

  .record-title {
    font-size: 28rpx;
    color: #333;
    font-weight: 500;
    margin-bottom: 10rpx;
  }

  .record-meta {
    align-items: center;
    flex-wrap: nowrap;
  }

  .record-time {
    font-size: 22rpx;
    color: #bbb;
    margin-right: 12rpx;
  }
}

/* ===== 状态标签 ===== */
.status-tag {
  font-size: 20rpx;
  padding: 2rpx 10rpx;
  border-radius: 20rpx;

  &.frozen {
    color: #999;
    background: #f0f0f0;
  }

  &.released {
    color: #52c41a;
    background: rgba(82, 196, 26, 0.1);
  }
}

/* ===== 积分数值 ===== */
.points-amount {
  font-size: 32rpx;
  font-weight: 600;
  flex-shrink: 0;

  &.add {
    color: #19be6b;
  }

  &.sub {
    color: #ed4014;
  }
}

/* ===== 底部加载提示 ===== */
.loading-bar {
  font-size: 26rpx;
  color: #aaa;
  padding: 24rpx 0;

  .loading {
    margin-right: 8rpx;
    animation: rotate 1s linear infinite;
  }
}

@keyframes rotate {
  from { transform: rotate(0deg); }
  to   { transform: rotate(360deg); }
}
</style>
