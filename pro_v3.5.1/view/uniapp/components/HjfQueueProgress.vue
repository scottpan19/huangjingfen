<template>
  <view class="hjf-queue-progress">
    <!-- 环形进度 -->
    <view class="progress-ring-wrap">
      <view class="progress-ring" :style="ringStyle">
        <view class="progress-ring-inner">
          <text class="progress-text">{{ currentCount }}/{{ triggerMultiple }}</text>
        </view>
      </view>
    </view>
    <!-- 条形进度 -->
    <view class="progress-bar-wrap">
      <view class="progress-bar-track">
        <view class="progress-bar-fill" :style="barStyle" />
      </view>
      <view class="progress-label">
        <text>当前批次进度</text>
        <text class="progress-value">{{ currentCount }}/{{ triggerMultiple }}</text>
      </view>
      <view v-if="nextRefundNo != null" class="next-refund">
        下一退款序号: {{ nextRefundNo }}
      </view>
    </view>
  </view>
</template>

<script>
/**
 * @file HjfQueueProgress.vue
 * @description 公排批次进度组件：环形/条形进度条展示当前批次进度（如 2/4），并显示下一个退款序号。
 * @see docs/frontend-new-pages-spec.md 第 2.2.2 节
 */

export default {
  name: 'HjfQueueProgress',
  props: {
    /**
     * 当前批次已入队数
     * @type {number}
     */
    currentCount: {
      type: Number,
      default: 0
    },
    /**
     * 触发倍数（每多少人触发一批退款，默认 4）
     * @type {number}
     */
    triggerMultiple: {
      type: Number,
      default: 4
    },
    /**
     * 下一个退款的 queue_no（可选，有值时显示「下一退款序号」）
     * @type {number|null}
     */
    nextRefundNo: {
      type: Number,
      default: null
    }
  },
  computed: {
    /**
     * 进度百分比（0–100），用于条形/环形展示
     * @returns {number}
     */
    progressPercent() {
      const total = this.triggerMultiple;
      if (!total || total <= 0) return 0;
      const p = Math.min(100, (this.currentCount / total) * 100);
      return Math.round(p * 10) / 10;
    },
    /**
     * 环形进度样式（conic-gradient 用 progressPercent）
     * @returns {Object}
     */
    ringStyle() {
      return {
        '--progress-percent': this.progressPercent,
        '--progress-color': 'var(--view-theme)'
      };
    },
    /**
     * 条形进度填充宽度与主题色
     * @returns {Object}
     */
    barStyle() {
      return {
        width: this.progressPercent + '%',
        backgroundColor: 'var(--view-theme)'
      };
    }
  }
};
</script>

<style scoped lang="scss">
.hjf-queue-progress {
  padding: 24rpx;
}

.progress-ring-wrap {
  display: flex;
  justify-content: center;
  margin-bottom: 32rpx;
}

.progress-ring {
  --progress-percent: 0;
  --progress-color: var(--view-theme);
  width: 160rpx;
  height: 160rpx;
  border-radius: 50%;
  background: conic-gradient(
    var(--progress-color) 0deg,
    var(--progress-color) calc(var(--progress-percent) * 3.6deg),
    #eee calc(var(--progress-percent) * 3.6deg),
    #eee 360deg
  );
  display: flex;
  align-items: center;
  justify-content: center;
}

.progress-ring-inner {
  width: 120rpx;
  height: 120rpx;
  border-radius: 50%;
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
}

.progress-text {
  font-size: 28rpx;
  font-weight: 600;
  color: #333;
}

.progress-bar-wrap {
  padding: 0 8rpx;
}

.progress-bar-track {
  height: 16rpx;
  border-radius: 8rpx;
  background: #eee;
  overflow: hidden;
}

.progress-bar-fill {
  height: 100%;
  border-radius: 8rpx;
  transition: width 0.25s ease;
}

.progress-label {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 16rpx;
  font-size: 24rpx;
  color: #666;
}

.progress-value {
  font-weight: 600;
  color: var(--view-theme);
}

.next-refund {
  margin-top: 12rpx;
  font-size: 22rpx;
  color: #999;
}
</style>
