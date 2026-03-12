<template>
  <view class="hjf-asset-card">
    <view class="card-row">
      <view class="card-item">
        <view class="label">现金余额(¥)</view>
        <view class="value">{{ formattedNowMoney }}</view>
      </view>
      <view class="card-item">
        <view class="label">待释放积分</view>
        <view class="value">{{ formattedFrozenPoints }}</view>
      </view>
      <view class="card-item">
        <view class="label">已释放积分</view>
        <view class="value">{{ formattedAvailablePoints }}</view>
      </view>
    </view>
    <view v-if="todayRelease != null" class="card-footer">
      <view class="footer-text">今日预计释放 {{ formattedTodayRelease }} 积分</view>
    </view>
  </view>
</template>

<script>
/**
 * @file HjfAssetCard.vue
 * @description 三栏资产展示卡片（现金余额 / 待释放积分 / 已释放积分），渐变背景。用于资产总览等页面。
 * @see docs/frontend-new-pages-spec.md 第 3.2.3 节
 * @see pages/users/user_money/index.vue 渐变卡片区域
 */
export default {
  name: 'HjfAssetCard',

  props: {
    /**
     * 现金余额（元），字符串便于对接接口返回
     * @type {string}
     * @default '0.00'
     */
    nowMoney: {
      type: String,
      default: '0.00'
    },
    /**
     * 待释放积分
     * @type {number}
     * @default 0
     */
    frozenPoints: {
      type: Number,
      default: 0
    },
    /**
     * 已释放积分
     * @type {number}
     * @default 0
     */
    availablePoints: {
      type: Number,
      default: 0
    },
    /**
     * 今日预计释放积分（不传则不显示底部提示）
     * @type {number}
     * @default null
     */
    todayRelease: {
      type: Number,
      default: null
    }
  },

  computed: {
    /** 格式化后的现金余额，保留两位小数 */
    formattedNowMoney() {
      const num = parseFloat(this.nowMoney);
      return isNaN(num) ? '0.00' : num.toFixed(2);
    },
    /** 格式化后的待释放积分 */
    formattedFrozenPoints() {
      return Number(this.frozenPoints).toLocaleString();
    },
    /** 格式化后的已释放积分 */
    formattedAvailablePoints() {
      return Number(this.availablePoints).toLocaleString();
    },
    /** 格式化后的今日预计释放 */
    formattedTodayRelease() {
      if (this.todayRelease == null) return '';
      return Number(this.todayRelease).toLocaleString();
    }
  }
};
</script>

<style scoped lang="scss">
.hjf-asset-card {
  width: 710rpx;
  margin: 0 auto;
  background: linear-gradient(90deg, var(--view-theme, #e93323) 0%, var(--view-gradient, #f76b1c) 100%);
  border-radius: 32rpx;
  box-sizing: border-box;
  color: rgba(255, 255, 255, 0.95);
  font-size: 24rpx;
  position: relative;
  overflow: hidden;

  .card-row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    padding: 36rpx 32rpx 32rpx;
  }

  .card-item {
    flex: 1;
    min-width: 0;
    text-align: center;
    padding: 0 8rpx;

    .label {
      display: block;
      font-size: 24rpx;
      color: rgba(255, 255, 255, 0.95);
      margin-bottom: 12rpx;
    }

    .value {
      font-size: 40rpx;
      font-weight: 600;
      color: #ffffff;
      font-family: 'SemiBold', sans-serif;
    }
  }

  .card-footer {
    width: 100%;
    padding: 20rpx 32rpx 24rpx;
    background: rgba(255, 255, 255, 0.1);
    text-align: center;

    .footer-text {
      font-size: 24rpx;
      color: rgba(255, 255, 255, 0.95);
    }
  }
}
</style>
