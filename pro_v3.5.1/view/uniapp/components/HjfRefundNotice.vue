<template>
  <view v-if="visible" class="hjf-refund-notice" @tap.self="handleMaskTap">
    <view class="hjf-refund-notice__mask" />
    <view class="hjf-refund-notice__box">
      <view class="hjf-refund-notice__icon-wrap">
        <text class="hjf-refund-notice__icon">✓</text>
      </view>
      <view class="hjf-refund-notice__title">恭喜！您的公排订单已退款</view>
      <view class="hjf-refund-notice__desc">已入账到现金余额</view>
      <view class="hjf-refund-notice__amount">
        <text class="hjf-refund-notice__amount-label">退款金额</text>
        <text class="hjf-refund-notice__amount-value">{{ formattedAmount }}</text>
      </view>
      <view v-if="orderId" class="hjf-refund-notice__order">
        <text class="hjf-refund-notice__order-label">订单号</text>
        <text class="hjf-refund-notice__order-value">{{ orderId }}</text>
      </view>
      <view class="hjf-refund-notice__btn" @tap="handleConfirm">确认</view>
    </view>
  </view>
</template>

<script>
/**
 * @file HjfRefundNotice.vue
 * @description 公排退款成功后的弹窗通知，展示退款金额、已入账到现金余额、订单号，确认按钮关闭弹窗。
 * @see docs/frontend-new-pages-spec.md 第 2.2.3 节
 */
export default {
  name: 'HjfRefundNotice',

  props: {
    /**
     * 是否显示弹窗
     * @type {boolean}
     * @default false
     */
    visible: {
      type: Boolean,
      default: false
    },
    /**
     * 退款金额（元）
     * @type {number}
     * @default 0
     */
    amount: {
      type: Number,
      default: 0
    },
    /**
     * 订单号
     * @type {string}
     * @default ''
     */
    orderId: {
      type: String,
      default: ''
    }
  },

  computed: {
    /**
     * 格式化后的退款金额，格式：¥3,600.00（千分位 + 两位小数）
     * @returns {string}
     */
    formattedAmount() {
      const num = Number(this.amount);
      if (isNaN(num)) return '¥0.00';
      const fixed = num.toFixed(2);
      const [intPart, decPart] = fixed.split('.');
      const formatted = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
      return `¥${formatted}.${decPart}`;
    }
  },

  methods: {
    /**
     * 点击确认按钮，关闭弹窗并触发 close 事件
     */
    handleConfirm() {
      this.$emit('close');
    },

    /**
     * 点击遮罩层，关闭弹窗并触发 close 事件
     */
    handleMaskTap() {
      this.$emit('close');
    }
  }
};
</script>

<style scoped lang="scss">
.hjf-refund-notice {
  position: fixed;
  left: 0;
  top: 0;
  right: 0;
  bottom: 0;
  z-index: 999;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40rpx;
  box-sizing: border-box;
}

.hjf-refund-notice__mask {
  position: absolute;
  left: 0;
  top: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
}

.hjf-refund-notice__box {
  position: relative;
  width: 100%;
  max-width: 560rpx;
  background: #fff;
  border-radius: 24rpx;
  padding: 48rpx 40rpx 40rpx;
  box-sizing: border-box;
}

.hjf-refund-notice__icon-wrap {
  width: 88rpx;
  height: 88rpx;
  margin: 0 auto 24rpx;
  background: var(--view-gradient, linear-gradient(135deg, #52c41a 0%, #73d13d 100%));
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.hjf-refund-notice__icon {
  font-size: 48rpx;
  color: #fff;
  font-weight: bold;
  line-height: 1;
}

.hjf-refund-notice__title {
  font-size: 36rpx;
  font-weight: 600;
  color: #333;
  text-align: center;
  margin-bottom: 12rpx;
}

.hjf-refund-notice__desc {
  font-size: 28rpx;
  color: #666;
  text-align: center;
  margin-bottom: 32rpx;
}

.hjf-refund-notice__amount {
  background: #f5f5f5;
  border-radius: 12rpx;
  padding: 24rpx;
  margin-bottom: 20rpx;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.hjf-refund-notice__amount-label {
  font-size: 28rpx;
  color: #666;
}

.hjf-refund-notice__amount-value {
  font-size: 36rpx;
  font-weight: 600;
  color: #ff4d4f;
}

.hjf-refund-notice__order {
  background: #fafafa;
  border-radius: 12rpx;
  padding: 20rpx 24rpx;
  margin-bottom: 40rpx;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.hjf-refund-notice__order-label {
  font-size: 26rpx;
  color: #999;
}

.hjf-refund-notice__order-value {
  font-size: 26rpx;
  color: #666;
  max-width: 360rpx;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.hjf-refund-notice__btn {
  height: 88rpx;
  line-height: 88rpx;
  text-align: center;
  font-size: 32rpx;
  font-weight: 500;
  color: #fff;
  background: var(--view-gradient, linear-gradient(135deg, #1890ff 0%, #40a9ff 100%));
  border-radius: 44rpx;
}

.hjf-refund-notice__btn:active {
  opacity: 0.9;
}
</style>
