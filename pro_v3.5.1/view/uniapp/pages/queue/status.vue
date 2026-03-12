<template>
  <view class="queue-status-page" :style="colorStyle">
    <view class="header-gradient">
      <view class="header-gradient__bg-circle header-gradient__bg-circle--1"></view>
      <view class="header-gradient__bg-circle header-gradient__bg-circle--2"></view>

      <view class="header-card">
        <view class="header-card__label">公排池总单数</view>
        <view class="header-card__total">{{ queueStatus.totalOrders || 0 }}</view>
        <view class="header-card__progress" v-if="queueStatus.progress">
          <HjfQueueProgress
            :current-count="queueStatus.progress.current_batch_count"
            :trigger-multiple="queueStatus.progress.trigger_multiple"
            :next-refund-no="queueStatus.progress.next_refund_queue_no"
          />
        </view>
      </view>
    </view>

    <view class="order-list-section">
      <view class="section-header">
        <view class="section-header__dot"></view>
        <view class="section-header__title">我的排队订单</view>
      </view>

      <view v-if="loading" class="loading-wrap">
        <view class="loading-dots">
          <view class="loading-dot loading-dot--1"></view>
          <view class="loading-dot loading-dot--2"></view>
          <view class="loading-dot loading-dot--3"></view>
        </view>
        <text class="loading-text">加载中...</text>
      </view>

      <view v-else-if="!queueStatus.myOrders || queueStatus.myOrders.length === 0" class="empty-wrap">
        <emptyPage title="暂无排队记录～" src="/statics/images/noOrder.gif" />
      </view>

      <view v-else class="order-list">
        <view
          v-for="(order, index) in queueStatus.myOrders"
          :key="order.id || index"
          class="order-item"
        >
          <view class="order-item__top">
            <view class="order-item__no">
              <text class="order-item__no-hash">#</text>
              <text class="order-item__no-value">{{ order.queue_no }}</text>
            </view>
            <view
              class="order-item__tag"
              :class="order.status === 0 ? 'order-item__tag--active' : 'order-item__tag--refunded'"
            >
              <view class="order-item__tag-dot" :class="order.status === 0 ? 'order-item__tag-dot--active' : 'order-item__tag-dot--refunded'"></view>
              {{ order.status === 0 ? '排队中' : '已退款' }}
            </view>
          </view>

          <view class="order-item__id">{{ order.order_id }}</view>

          <view class="order-item__bottom">
            <text class="order-item__amount">¥{{ Number(order.amount).toFixed(2) }}</text>
            <text class="order-item__wait">{{ order.estimated_wait }}</text>
          </view>
        </view>
      </view>
    </view>

    <view v-if="queueStatus.myOrders && queueStatus.myOrders.length > 0" class="load-more-bar">
      <text v-if="loadingMore" class="load-more-text">加载中...</text>
      <text v-else-if="finished" class="load-more-text">没有更多内容啦~</text>
      <text v-else class="load-more-text">上拉加载更多</text>
    </view>

    <HjfRefundNotice
      :visible="showRefund"
      :amount="refundData.amount"
      :order-id="refundData.orderId"
      @close="handleRefundClose"
    />
  </view>
</template>

<script>
/**
 * @file pages/queue/status.vue
 * @description P12 公排状态页 — 展示公排池总单数、当前批次进度及我的排队订单列表，
 *              并在退款到账时弹出 HjfRefundNotice 通知。
 * @see docs/frontend-new-pages-spec.md 第 2.2.4 节
 */

import { getQueueStatus } from '@/api/hjfQueue.js';
import HjfQueueProgress from '@/components/HjfQueueProgress.vue';
import HjfRefundNotice from '@/components/HjfRefundNotice.vue';
import emptyPage from '@/components/emptyPage.vue';
import colors from '@/mixins/color.js';

export default {
  name: 'QueueStatus',

  mixins: [colors],

  components: {
    HjfQueueProgress,
    HjfRefundNotice,
    emptyPage
  },

  data() {
    return {
      /**
       * 公排状态数据，包含 totalOrders、myOrders、progress
       * @type {{ totalOrders: number, myOrders: Array, progress: Object }}
       */
      queueStatus: {},

      /**
       * 是否正在加载数据
       * @type {boolean}
       */
      loading: false,

      /**
       * 是否显示退款通知弹窗
       * @type {boolean}
       */
      showRefund: false,

      /**
       * 退款弹窗所需数据
       * @type {{ amount: number, orderId: string }}
       */
      refundData: {
        amount: 0,
        orderId: ''
      },

      /** @type {boolean} 是否正在上拉加载更多 */
      loadingMore: false,

      /** @type {boolean} 是否已加载完全部数据 */
      finished: false
    };
  },

  onLoad() {
    this.loadQueueStatus();
  },

  onShow() {
    this.checkPendingRefundNotice();
  },

  onReachBottom() {
    this.loadMoreOrders();
  },

  methods: {
    /**
     * 加载公排状态数据
     * 调用 getQueueStatus()，将返回值赋给 queueStatus，
     * 并在检测到已退款订单时触发退款弹窗。
     * @returns {Promise<void>}
     */
    loadQueueStatus() {
      this.loading = true;
      getQueueStatus()
        .then(res => {
          if (res && res.data) {
            this.$set(this, 'queueStatus', res.data);
            this.detectNewRefund(res.data.myOrders || []);
          }
        })
        .catch(err => {
          console.error('[QueueStatus] loadQueueStatus error:', err);
          uni.showToast({ title: '加载失败，请稍后重试', icon: 'none' });
        })
        .finally(() => {
          this.loading = false;
        });
    },

    /**
     * 检测是否存在刚完成退款的订单，有则弹出退款通知。
     * 策略：取 status=1 且 refund_time 最大的一条（最近退款），
     * 结合页面跳转参数 show_refund=1 触发弹窗。
     * @param {Array} orders - 我的排队订单列表
     */
    detectNewRefund(orders) {
      const refunded = orders.filter(o => o.status === 1 && o.refund_time > 0);
      if (!refunded.length) return;
      refunded.sort((a, b) => b.refund_time - a.refund_time);
      const latest = refunded[0];
      const showParam = this._pageParams && this._pageParams.show_refund;
      if (showParam === '1') {
        this.refundData = {
          amount: latest.amount,
          orderId: latest.order_id
        };
        this.showRefund = true;
      }
    },

    /**
     * 页面显示时检查是否需要弹出退款通知（从外部跳转携带参数时使用）
     */
    checkPendingRefundNotice() {
      const pages = getCurrentPages();
      const current = pages[pages.length - 1];
      const options = (current && current.options) || {};
      if (options.show_refund === '1' && this.queueStatus.myOrders) {
        this.detectNewRefund(this.queueStatus.myOrders);
      }
    },

    /**
     * 上拉加载更多（当前数据由单次接口全量返回，模拟已到底状态）
     * Phase 4 接入分页接口后替换为真实分页逻辑。
     */
    loadMoreOrders() {
      if (this.loadingMore || this.finished) return;
      this.loadingMore = true;
      setTimeout(() => {
        this.finished = true;
        this.loadingMore = false;
      }, 500);
    },

    /**
     * 关闭退款通知弹窗
     */
    handleRefundClose() {
      this.showRefund = false;
    }
  }
};
</script>

<style scoped lang="scss">
.queue-status-page {
  min-height: 100vh;
  background: #f4f5f7;
  padding-bottom: 40rpx;
}

.header-gradient {
  background: linear-gradient(135deg, var(--view-theme, #e93323) 0%, var(--view-gradient, #f76b1c) 100%);
  padding: 36rpx 30rpx 48rpx;
  position: relative;
  overflow: hidden;
}

.header-gradient__bg-circle {
  position: absolute;
  border-radius: 50%;
  background: #fff;
  opacity: 0.06;
}

.header-gradient__bg-circle--1 {
  width: 400rpx;
  height: 400rpx;
  top: -160rpx;
  right: -100rpx;
}

.header-gradient__bg-circle--2 {
  width: 240rpx;
  height: 240rpx;
  bottom: -60rpx;
  left: -50rpx;
}

.header-card {
  position: relative;
  z-index: 1;
  background: rgba(255, 255, 255, 0.14);
  border-radius: 28rpx;
  padding: 36rpx 32rpx;
  backdrop-filter: blur(10px);
}

.header-card__label {
  font-size: 26rpx;
  color: rgba(255, 255, 255, 0.8);
  margin-bottom: 10rpx;
}

.header-card__total {
  font-size: 64rpx;
  font-weight: 700;
  color: #fff;
  font-family: 'SemiBold', sans-serif;
  line-height: 1.1;
  margin-bottom: 28rpx;
}

.header-card__progress {
  background: rgba(255, 255, 255, 0.95);
  border-radius: 18rpx;
  overflow: hidden;
}

.order-list-section {
  margin: -16rpx 20rpx 0;
  position: relative;
  z-index: 2;
}

.section-header {
  display: flex;
  align-items: center;
  padding: 24rpx 4rpx 20rpx;
}

.section-header__dot {
  width: 8rpx;
  height: 30rpx;
  border-radius: 4rpx;
  background: var(--view-theme, #e93323);
  margin-right: 14rpx;
  flex-shrink: 0;
}

.section-header__title {
  font-size: 30rpx;
  font-weight: 600;
  color: #333;
}

.loading-wrap {
  padding: 60rpx 0;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 20rpx;
}

.loading-dots {
  display: flex;
  gap: 12rpx;
}

.loading-dot {
  width: 14rpx;
  height: 14rpx;
  border-radius: 50%;
  background: var(--view-theme, #e93323);
  animation: dot-bounce 1.2s infinite ease-in-out;
}

.loading-dot--2 { animation-delay: 0.2s; }
.loading-dot--3 { animation-delay: 0.4s; }

@keyframes dot-bounce {
  0%, 80%, 100% { opacity: 0.3; transform: scale(0.8); }
  40% { opacity: 1; transform: scale(1.2); }
}

.loading-text {
  font-size: 26rpx;
  color: #999;
}

.empty-wrap {
  padding: 40rpx 0;
  text-align: center;
}

.order-item {
  background: #fff;
  border-radius: 24rpx;
  padding: 30rpx 32rpx;
  margin-bottom: 20rpx;
  box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.04);
  position: relative;
  overflow: hidden;
}

.order-item__top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 18rpx;
}

.order-item__no {
  display: flex;
  align-items: baseline;
}

.order-item__no-hash {
  font-size: 26rpx;
  font-weight: 500;
  color: var(--view-theme, #e93323);
  margin-right: 4rpx;
}

.order-item__no-value {
  font-size: 36rpx;
  font-weight: 700;
  color: #333;
  font-family: 'SemiBold', sans-serif;
}

.order-item__tag {
  font-size: 22rpx;
  font-weight: 500;
  padding: 8rpx 20rpx;
  border-radius: 24rpx;
  display: flex;
  align-items: center;
  gap: 8rpx;
}

.order-item__tag-dot {
  width: 10rpx;
  height: 10rpx;
  border-radius: 50%;
}

.order-item__tag--active {
  background: #e6f7ee;
  color: #389e0d;
}

.order-item__tag-dot--active {
  background: #52c41a;
}

.order-item__tag--refunded {
  background: #f5f5f5;
  color: #999;
}

.order-item__tag-dot--refunded {
  background: #bbb;
}

.order-item__id {
  font-size: 24rpx;
  color: #999;
  padding-bottom: 18rpx;
  word-break: break-all;
}

.order-item__bottom {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-top: 18rpx;
  border-top: 1rpx solid #f0f0f0;
}

.order-item__amount {
  font-size: 34rpx;
  font-weight: 600;
  color: #333;
  font-family: 'SemiBold', sans-serif;
}

.order-item__wait {
  font-size: 24rpx;
  color: #999;
  background: #fafafa;
  padding: 6rpx 16rpx;
  border-radius: 8rpx;
}

.load-more-bar {
  padding: 32rpx 0 48rpx;
  text-align: center;
}

.load-more-text {
  font-size: 26rpx;
  color: #aaa;
}
</style>
