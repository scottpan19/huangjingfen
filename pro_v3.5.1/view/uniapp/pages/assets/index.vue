<template>
  <view class="hjf-assets-page" :style="colorStyle">

    <view class="assets-wrapper">
      <view class="assets-header">
        <view class="assets-header__top">
          <view class="assets-header__title">我的资产</view>
        </view>

        <view v-if="loading" class="skeleton-card"></view>

        <view v-else class="hero-card">
          <view class="hero-card__bg-circle hero-card__bg-circle--1"></view>
          <view class="hero-card__bg-circle hero-card__bg-circle--2"></view>
          <view class="hero-card__main">
            <view class="hero-card__label">现金余额(元)</view>
            <view class="hero-card__money">
              <text class="hero-card__yen">¥</text>{{ assetsInfo ? Number(assetsInfo.now_money).toFixed(2) : '0.00' }}
            </view>
          </view>
          <view class="hero-card__row">
            <view class="hero-card__col">
              <view class="hero-card__col-val">{{ formattedFrozenPoints }}</view>
              <view class="hero-card__col-key">待释放积分</view>
            </view>
            <view class="hero-card__sep"></view>
            <view class="hero-card__col">
              <view class="hero-card__col-val">{{ formattedAvailablePoints }}</view>
              <view class="hero-card__col-key">已释放积分</view>
            </view>
            <view class="hero-card__sep"></view>
            <view class="hero-card__col" v-if="assetsInfo && assetsInfo.today_release != null">
              <view class="hero-card__col-val hero-card__col-val--accent">{{ assetsInfo.today_release }}</view>
              <view class="hero-card__col-key">今日释放</view>
            </view>
          </view>
        </view>
      </view>

      <view class="quick-nav">
        <view class="quick-nav__item" hover-class="quick-nav__item--hover" @tap="goPointsDetail">
          <view class="quick-nav__icon quick-nav__icon--points">
            <text class="iconfont icon-jifen"></text>
          </view>
          <view class="quick-nav__label">积分明细</view>
          <view class="quick-nav__desc">待释放 / 已释放</view>
        </view>
        <view class="quick-nav__item" hover-class="quick-nav__item--hover" @tap="goCashDetail">
          <view class="quick-nav__icon quick-nav__icon--cash">
            <text class="iconfont icon-qianbao"></text>
          </view>
          <view class="quick-nav__label">现金明细</view>
          <view class="quick-nav__desc">收支流水记录</view>
        </view>
        <view class="quick-nav__item" hover-class="quick-nav__item--hover" @tap="goWithdraw">
          <view class="quick-nav__icon quick-nav__icon--withdraw">
            <text class="iconfont icon-tixian"></text>
          </view>
          <view class="quick-nav__label">申请提现</view>
          <view class="quick-nav__desc">可用余额提现</view>
        </view>
      </view>
    </view>

    <view class="release-card" v-if="!loading && assetsInfo">
      <view class="release-card__header">
        <view class="release-card__dot"></view>
        <view class="release-card__title">今日释放预告</view>
        <view class="release-card__date">{{ todayDateStr }}</view>
      </view>
      <view class="release-card__body">
        <view class="release-card__item">
          <view class="release-card__value release-card__value--highlight">
            {{ assetsInfo.today_release != null ? assetsInfo.today_release : 0 }}
          </view>
          <view class="release-card__key">今日预计释放(积分)</view>
        </view>
        <view class="release-card__divider"></view>
        <view class="release-card__item">
          <view class="release-card__value">{{ formattedFrozenPoints }}</view>
          <view class="release-card__key">待释放总积分</view>
        </view>
        <view class="release-card__divider"></view>
        <view class="release-card__item">
          <view class="release-card__value">{{ formattedAvailablePoints }}</view>
          <view class="release-card__key">已释放积分</view>
        </view>
      </view>
      <view class="release-card__tips">
        <text class="iconfont icon-tishi"></text>
        积分每日自动释放，释放后可用于抵扣消费
      </view>
    </view>

    <view class="stats-row" v-if="!loading && assetsInfo">
      <view class="stats-item">
        <view class="stats-icon stats-icon--refund">
          <text class="iconfont icon-qianbao"></text>
        </view>
        <view class="stats-info">
          <view class="stats-value">¥{{ assetsInfo.total_queue_refund }}</view>
          <view class="stats-label">公排累计退款</view>
        </view>
      </view>
      <view class="stats-divider"></view>
      <view class="stats-item">
        <view class="stats-icon stats-icon--points">
          <text class="iconfont icon-jifen"></text>
        </view>
        <view class="stats-info">
          <view class="stats-value">{{ formattedTotalPoints }}</view>
          <view class="stats-label">累计获得积分</view>
        </view>
      </view>
    </view>

  </view>
</template>

<script>
import { getAssetsOverview } from '@/api/hjfAssets.js';
import colors from '@/mixins/color.js';

export default {
  name: 'AssetsIndex',

  mixins: [colors],

  data() {
    return {
      assetsInfo: null,
      loading: false
    };
  },

  computed: {
    todayDateStr() {
      const d = new Date();
      const mm = String(d.getMonth() + 1).padStart(2, '0');
      const dd = String(d.getDate()).padStart(2, '0');
      return `${d.getFullYear()}-${mm}-${dd}`;
    },
    formattedFrozenPoints() {
      if (!this.assetsInfo) return '0';
      return Number(this.assetsInfo.frozen_points).toLocaleString();
    },
    formattedAvailablePoints() {
      if (!this.assetsInfo) return '0';
      return Number(this.assetsInfo.available_points).toLocaleString();
    },
    formattedTotalPoints() {
      if (!this.assetsInfo) return '0';
      return Number(this.assetsInfo.total_points_earned).toLocaleString();
    }
  },

  onLoad() {
    this.fetchAssetsOverview();
  },

  onShow() {
    if (this.assetsInfo) {
      this.fetchAssetsOverview();
    }
  },

  methods: {
    fetchAssetsOverview() {
      this.loading = true;
      getAssetsOverview()
        .then(res => {
          if (res && res.status === 200) {
            this.assetsInfo = res.data;
          }
        })
        .catch(() => {
          uni.showToast({ title: '加载失败，请稍后重试', icon: 'none' });
        })
        .finally(() => {
          this.loading = false;
        });
    },

    goPointsDetail() {
      uni.navigateTo({ url: '/pages/assets/points_detail' });
    },

    goCashDetail() {
      uni.navigateTo({ url: '/pages/users/user_bill/index?type=2' });
    },

    goWithdraw() {
      uni.navigateTo({ url: '/pages/users/user_cash/index' });
    }
  }
};
</script>

<style scoped lang="scss">
.hjf-assets-page {
  min-height: 100vh;
  background-color: #f4f5f7;
  padding-bottom: 60rpx;
}

.assets-wrapper {
  background: linear-gradient(180deg, var(--view-theme, #e93323) 0%, var(--view-gradient, #f76b1c) 50%, #f4f5f7 100%);
  padding-bottom: 4rpx;
}

.assets-header {
  padding-top: 32rpx;
}

.assets-header__top {
  padding: 0 30rpx 24rpx;
}

.assets-header__title {
  font-size: 36rpx;
  font-weight: 700;
  color: #fff;
  letter-spacing: 2rpx;
}

.skeleton-card {
  width: 710rpx;
  height: 280rpx;
  background: linear-gradient(90deg, rgba(255,255,255,0.15) 25%, rgba(255,255,255,0.25) 50%, rgba(255,255,255,0.15) 75%);
  background-size: 400% 100%;
  border-radius: 32rpx;
  margin: 0 auto 20rpx;
  animation: skeleton-shimmer 1.5s infinite;
}

@keyframes skeleton-shimmer {
  0%   { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

.hero-card {
  width: 710rpx;
  margin: 0 auto;
  border-radius: 32rpx;
  background: linear-gradient(135deg, var(--view-theme, #e93323) 0%, var(--view-gradient, #f76b1c) 100%);
  box-sizing: border-box;
  position: relative;
  overflow: hidden;
  box-shadow: 0 8rpx 32rpx rgba(0, 0, 0, 0.12);
}

.hero-card__bg-circle {
  position: absolute;
  border-radius: 50%;
  opacity: 0.08;
  background: #fff;
}

.hero-card__bg-circle--1 {
  width: 320rpx;
  height: 320rpx;
  top: -80rpx;
  right: -60rpx;
}

.hero-card__bg-circle--2 {
  width: 200rpx;
  height: 200rpx;
  bottom: -40rpx;
  left: -30rpx;
}

.hero-card__main {
  padding: 40rpx 36rpx 28rpx;
  position: relative;
  z-index: 1;
}

.hero-card__label {
  font-size: 24rpx;
  color: rgba(255, 255, 255, 0.8);
  margin-bottom: 12rpx;
}

.hero-card__money {
  font-size: 64rpx;
  font-weight: 700;
  color: #fff;
  font-family: 'SemiBold', sans-serif;
  line-height: 1.1;
}

.hero-card__yen {
  font-size: 36rpx;
  font-weight: 500;
  margin-right: 4rpx;
}

.hero-card__row {
  display: flex;
  align-items: center;
  background: rgba(255, 255, 255, 0.12);
  padding: 24rpx 0;
  position: relative;
  z-index: 1;
}

.hero-card__col {
  flex: 1;
  text-align: center;
}

.hero-card__col-val {
  font-size: 36rpx;
  font-weight: 600;
  color: #fff;
  font-family: 'SemiBold', sans-serif;
  margin-bottom: 6rpx;
}

.hero-card__col-val--accent {
  color: #ffe58f;
}

.hero-card__col-key {
  font-size: 22rpx;
  color: rgba(255, 255, 255, 0.75);
}

.hero-card__sep {
  width: 1rpx;
  height: 48rpx;
  background: rgba(255, 255, 255, 0.2);
  flex-shrink: 0;
}

.quick-nav {
  display: flex;
  margin: 20rpx 20rpx 0;
}

.quick-nav__item {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  background: #fff;
  border-radius: 20rpx;
  padding: 30rpx 12rpx 26rpx;
  box-sizing: border-box;
  margin: 0 8rpx;
  box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.04);
  transition: all 0.2s;
}

.quick-nav__item:first-child { margin-left: 0; }
.quick-nav__item:last-child  { margin-right: 0; }

.quick-nav__item--hover {
  opacity: 0.75;
  transform: scale(0.97);
}

.quick-nav__icon {
  width: 80rpx;
  height: 80rpx;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 16rpx;
}

.quick-nav__icon .iconfont {
  font-size: 38rpx;
  color: #fff;
}

.quick-nav__icon--points {
  background: linear-gradient(135deg, var(--view-theme, #e93323) 0%, var(--view-gradient, #f76b1c) 100%);
  box-shadow: 0 6rpx 16rpx rgba(233, 51, 35, 0.25);
}

.quick-nav__icon--cash {
  background: linear-gradient(135deg, #52c41a 0%, #73d13d 100%);
  box-shadow: 0 6rpx 16rpx rgba(82, 196, 26, 0.25);
}

.quick-nav__icon--withdraw {
  background: linear-gradient(135deg, #fa8c16 0%, #ffc53d 100%);
  box-shadow: 0 6rpx 16rpx rgba(250, 140, 22, 0.25);
}

.quick-nav__label {
  font-size: 26rpx;
  font-weight: 500;
  color: #333;
  margin-bottom: 6rpx;
}

.quick-nav__desc {
  font-size: 20rpx;
  color: #999;
  text-align: center;
}

.release-card {
  width: 710rpx;
  margin: 24rpx auto;
  background: #fff;
  border-radius: 24rpx;
  box-sizing: border-box;
  overflow: hidden;
  box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.04);
}

.release-card__header {
  display: flex;
  align-items: center;
  padding: 28rpx 32rpx 0;
}

.release-card__dot {
  width: 8rpx;
  height: 30rpx;
  border-radius: 4rpx;
  background: var(--view-theme, #e93323);
  margin-right: 14rpx;
  flex-shrink: 0;
}

.release-card__title {
  font-size: 30rpx;
  font-weight: 600;
  color: #333;
  flex: 1;
}

.release-card__date {
  font-size: 22rpx;
  color: #bbb;
}

.release-card__body {
  display: flex;
  align-items: center;
  padding: 28rpx 20rpx 24rpx;
}

.release-card__item {
  flex: 1;
  text-align: center;
}

.release-card__value {
  font-size: 38rpx;
  font-weight: 600;
  color: #333;
  margin-bottom: 8rpx;
  font-family: 'SemiBold', sans-serif;
}

.release-card__value--highlight {
  color: var(--view-theme, #e93323);
}

.release-card__key {
  font-size: 22rpx;
  color: #999;
}

.release-card__divider {
  width: 1rpx;
  height: 60rpx;
  background-color: #eee;
  align-self: center;
  flex-shrink: 0;
}

.release-card__tips {
  font-size: 22rpx;
  color: #bbb;
  padding: 0 32rpx 24rpx;
  display: flex;
  align-items: center;
  gap: 6rpx;
  background: #fafafa;
  padding-top: 18rpx;
}

.release-card__tips .iconfont {
  font-size: 24rpx;
  color: #ccc;
}

.stats-row {
  width: 710rpx;
  margin: 0 auto;
  background: #fff;
  border-radius: 24rpx;
  display: flex;
  align-items: center;
  padding: 32rpx 24rpx;
  box-sizing: border-box;
  box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.04);
}

.stats-item {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16rpx;
}

.stats-icon {
  width: 64rpx;
  height: 64rpx;
  border-radius: 16rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.stats-icon .iconfont {
  font-size: 32rpx;
  color: #fff;
}

.stats-icon--refund {
  background: linear-gradient(135deg, #ff7875 0%, #ff4d4f 100%);
}

.stats-icon--points {
  background: linear-gradient(135deg, #ffa940 0%, #fa8c16 100%);
}

.stats-info {
  display: flex;
  flex-direction: column;
}

.stats-value {
  font-size: 32rpx;
  font-weight: 600;
  color: #333;
  font-family: 'SemiBold', sans-serif;
  line-height: 1.3;
}

.stats-label {
  font-size: 22rpx;
  color: #999;
  margin-top: 4rpx;
}

.stats-divider {
  width: 1rpx;
  height: 60rpx;
  background-color: #eee;
  flex-shrink: 0;
  margin: 0 10rpx;
}
</style>
