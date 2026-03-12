<template>
  <view :style="colorStyle" class="queue-history">
    <!-- Tab 筛选 -->
    <view class="nav acea-row">
      <view
        class="item"
        :class="activeTab === 0 ? 'on' : ''"
        @click="changeTab(0)"
      >全部</view>
      <view
        class="item"
        :class="activeTab === 1 ? 'on' : ''"
        @click="changeTab(1)"
      >排队中</view>
      <view
        class="item"
        :class="activeTab === 2 ? 'on' : ''"
        @click="changeTab(2)"
      >已退款</view>
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
            <!-- 左侧：订单号 + 批次号 -->
            <view class="record-left">
              <view class="order-id line1">订单号：{{ item.order_id }}</view>
              <view class="record-meta">
                <text v-if="item.trigger_batch > 0">批次号：#{{ item.trigger_batch }}</text>
                <text v-else>入队序号：#{{ item.queue_no }}</text>
              </view>
              <view class="record-time" v-if="item.status === 1 && item.refund_time > 0">
                退款时间：{{ formatTime(item.refund_time) }}
              </view>
              <view class="record-time" v-else>
                入队时间：{{ formatTime(item.add_time) }}
              </view>
            </view>

            <!-- 右侧：金额 + 状态标签 -->
            <view class="record-right">
              <view class="amount">¥{{ Number(item.amount).toFixed(2) }}</view>
              <view class="status-tag" :class="item.status === 1 ? 'refunded' : 'queuing'">
                {{ item.status === 1 ? '已退款' : '排队中' }}
              </view>
            </view>
          </view>
        </view>
      </view>

      <!-- 加载状态 -->
      <view class="loading-bar acea-row row-center-wrapper" v-if="list.length > 0">
        <text class="loading iconfont icon-jiazai" :hidden="!loading"></text>
        {{ loadTitle }}
      </view>

      <!-- 空状态 -->
      <view class="px-20 mt-20" v-if="list.length === 0 && !loading">
        <emptyPage title="暂无公排记录～" src="/statics/images/noOrder.gif"></emptyPage>
      </view>
    </view>
  </view>
</template>

<script>
import { getQueueHistory } from '@/api/hjfQueue.js';
import emptyPage from '@/components/emptyPage.vue';
import colors from '@/mixins/color';

/**
 * P13 公排历史记录页
 *
 * 展示当前用户的所有公排历史，支持 Tab 筛选（全部/排队中/已退款），
 * 记录按日期分组显示，支持上拉翻页加载。
 *
 * @see docs/frontend-new-pages-spec.md 2.2.5
 */
export default {
  name: 'QueueHistory',

  components: { emptyPage },

  mixins: [colors],

  data() {
    return {
      /** @type {Array<Object>} 原始记录列表（所有已加载页的合并） */
      list: [],

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

      /**
       * 当前激活的 Tab
       * 0 = 全部，1 = 排队中，2 = 已退款
       * @type {number}
       */
      activeTab: 0,

    };
  },

  computed: {
    /**
     * 按 time_key（日期字符串）将 list 分组
     * 过滤规则：activeTab=0 不过滤；1 只显示 status=0；2 只显示 status=1
     * @returns {Array<{ date: string, children: Object[] }>}
     */
    groupedList() {
      const filtered = this.list.filter(item => {
        if (this.activeTab === 1) return item.status === 0;
        if (this.activeTab === 2) return item.status === 1;
        return true;
      });

      const map = {};
      const order = [];
      filtered.forEach(item => {
        const key = item.time_key;
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
    this.loadHistory();
  },

  onReachBottom() {
    this.loadHistory();
  },

  methods: {
    /**
     * 加载公排历史记录（分页追加）
     * 若正在加载或已全部加载则直接返回。
     * @returns {void}
     */
    loadHistory() {
      if (this.loading || this.finished) return;

      this.loading = true;
      this.loadTitle = '';

      getQueueHistory({
        page: this.page,
        limit: this.limit,
        status: this.activeTab === 0 ? undefined : this.activeTab === 1 ? 0 : 1,
      })
        .then(res => {
          const newItems = (res.data && res.data.list) ? res.data.list : [];

          newItems.forEach(item => {
            this.list.push(item);
          });

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
     * @param {number} tab - 目标 Tab 索引（0/1/2）
     * @returns {void}
     */
    changeTab(tab) {
      if (tab === this.activeTab) return;
      this.activeTab = tab;
      this.list = [];
      this.page = 1;
      this.finished = false;
      this.loadHistory();
    },

    /**
     * 将 Unix 时间戳格式化为 YYYY-MM-DD HH:mm
     * @param {number} timestamp - Unix 秒级时间戳
     * @returns {string} 格式化后的时间字符串
     */
    formatTime(timestamp) {
      if (!timestamp) return '--';
      const d = new Date(timestamp * 1000);
      const pad = n => String(n).padStart(2, '0');
      return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
    },
  },
};
</script>

<style scoped lang="scss">
.queue-history {
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

  .item {
    flex: 1;
    text-align: center;
    font-size: 28rpx;
    color: #333;
    line-height: 88rpx;
    position: relative;

    &.on {
      color: var(--view-theme);
      font-size: 30rpx;
      font-weight: 500;

      &::after {
        position: absolute;
        content: ' ';
        width: 64rpx;
        height: 6rpx;
        border-radius: 20rpx;
        background: var(--view-theme);
        bottom: 0;
        left: 50%;
        margin-left: -32rpx;
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

  .order-id {
    font-size: 28rpx;
    color: #333;
    font-weight: 500;
    margin-bottom: 8rpx;
  }

  .record-meta {
    font-size: 24rpx;
    color: #999;
    margin-bottom: 6rpx;
  }

  .record-time {
    font-size: 22rpx;
    color: #bbb;
  }
}

.record-right {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  flex-shrink: 0;

  .amount {
    font-size: 32rpx;
    color: #333;
    font-weight: 600;
    margin-bottom: 10rpx;
  }
}

/* ===== 状态标签 ===== */
.status-tag {
  font-size: 22rpx;
  padding: 4rpx 14rpx;
  border-radius: 20rpx;

  &.queuing {
    color: #19be6b;
    background: rgba(25, 190, 107, 0.1);
  }

  &.refunded {
    color: #999;
    background: #f5f5f5;
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
