<template>
  <view class="guide-page" :style="{ paddingTop: statusBarHeight + 'px' }">

    <!-- 右上角"跳过"按钮（固定定位） -->
    <view
      class="skip-btn"
      :style="{ top: (statusBarHeight + 12) + 'px' }"
      @tap="handleSkip"
    >
      <text class="skip-text">跳过</text>
    </view>

    <!-- 轮播主体 -->
    <swiper
      class="guide-swiper"
      :current="currentIndex"
      :indicator-dots="false"
      @change="onSwiperChange"
    >
      <swiper-item
        v-for="(slide, index) in slides"
        :key="index"
        class="swiper-item"
      >
        <view class="slide-content">
          <!-- 配图占位：渐变色块 -->
          <view class="slide-image-placeholder" :style="gradientStyles[index % gradientStyles.length]"></view>

          <!-- 文字区域 -->
          <view class="slide-text-wrap">
            <text class="slide-title">{{ slide.title }}</text>
            <text class="slide-desc">{{ slide.desc }}</text>
          </view>

          <!-- 第3屏底部"立即开始"按钮 -->
          <view v-if="index === slides.length - 1" class="start-wrap">
            <button class="btn-start" @tap="handleStart">立即开始</button>
          </view>
        </view>
      </swiper-item>
    </swiper>

    <!-- 底部圆点指示器 -->
    <view class="dot-indicators">
      <view
        v-for="(slide, index) in slides"
        :key="index"
        class="dot"
        :class="{ active: index === currentIndex }"
      ></view>
    </view>

  </view>
</template>

<script>
import { MOCK_GUIDE_DATA } from '@/utils/hjfMockData.js';

/**
 * P23 新用户引导页
 *
 * 3屏轮播引导，数据来自 MOCK_GUIDE_DATA.slides。
 * navigationStyle: custom，使用 getSystemInfoSync().statusBarHeight 适配刘海屏。
 *
 * @module pages/guide/hjf_intro
 * @see docs/frontend-new-pages-spec.md
 */
export default {
  name: 'HjfIntro',

  data() {
    const sysInfo = uni.getSystemInfoSync();
    return {
      /** 状态栏高度（px） @type {number} */
      statusBarHeight: sysInfo.statusBarHeight || 0,

      /**
       * 引导轮播幻灯片列表，来自 MOCK_GUIDE_DATA
       * @type {Array<{title: string, desc: string, image: string}>}
       */
      slides: [],

      /** 当前激活的幻灯片索引（0-based） @type {number} */
      currentIndex: 0,

      /** 各屏渐变色块样式 */
      gradientStyles: [
        { background: 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)' },
        { background: 'linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%)' },
        { background: 'linear-gradient(135deg, #a1c4fd 0%, #c2e9fb 100%)' },
      ],
    };
  },

  onLoad() {
    this.slides = MOCK_GUIDE_DATA.slides || [];
  },

  methods: {
    /**
     * swiper change 事件，同步当前索引
     * @param {Object} e - swiper change 事件
     */
    onSwiperChange(e) {
      this.currentIndex = e.detail.current;
    },

    /**
     * 点击"跳过"按钮，直接跳转首页 Tab
     */
    handleSkip() {
      uni.switchTab({ url: '/pages/index/index' });
    },

    /**
     * 点击"立即开始"按钮（第3屏），跳转首页 Tab
     */
    handleStart() {
      uni.switchTab({ url: '/pages/index/index' });
    },
  },
};
</script>

<style scoped lang="scss">
.guide-page {
  position: relative;
  width: 100%;
  height: 100vh;
  background: #fff;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-sizing: border-box;
}

/* ===== 跳过按钮 ===== */
.skip-btn {
  position: fixed;
  right: 40rpx;
  z-index: 99;
  padding: 12rpx 32rpx;
  background: rgba(0, 0, 0, 0.12);
  border-radius: 40rpx;

  .skip-text {
    font-size: 26rpx;
    color: #fff;
  }
}

/* ===== 轮播主体 ===== */
.guide-swiper {
  flex: 1;
  width: 100%;
}

.swiper-item {
  width: 100%;
  height: 100%;
}

.slide-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
  padding: 0 60rpx 120rpx;
  box-sizing: border-box;
}

/* ===== 渐变色块占位图 ===== */
.slide-image-placeholder {
  width: 480rpx;
  height: 360rpx;
  border-radius: 24rpx;
  margin-bottom: 80rpx;
}

/* ===== 文字区域 ===== */
.slide-text-wrap {
  text-align: center;
  margin-bottom: 60rpx;
}

.slide-title {
  display: block;
  font-size: 44rpx;
  font-weight: bold;
  color: #222;
  margin-bottom: 28rpx;
  line-height: 1.3;
}

.slide-desc {
  display: block;
  font-size: 30rpx;
  color: #888;
  line-height: 1.8;
}

/* ===== 第3屏"立即开始"按钮 ===== */
.start-wrap {
  width: 100%;
  display: flex;
  justify-content: center;
}

.btn-start {
  width: 560rpx;
  height: 96rpx;
  border-radius: 48rpx;
  font-size: 34rpx;
  font-weight: bold;
  border: none;
  background: linear-gradient(135deg, var(--view-theme) 0%, #ff7043 100%);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;

  &::after {
    border: none;
  }
}

/* ===== 底部圆点指示器 ===== */
.dot-indicators {
  display: flex;
  align-items: center;
  justify-content: center;
  padding-bottom: 60rpx;
}

.dot {
  width: 16rpx;
  height: 16rpx;
  border-radius: 50%;
  background: #ddd;
  margin: 0 10rpx;
  transition: all 0.3s;

  &.active {
    width: 40rpx;
    border-radius: 8rpx;
    background: var(--view-theme);
  }
}
</style>
