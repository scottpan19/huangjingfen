<template>
  <view class="guide-page">
    <!-- 跳过按钮 -->
    <view v-if="currentIndex < slides.length - 1" class="skip-btn" @tap="handleSkip">
      <text class="skip-text">跳过</text>
    </view>

    <!-- 轮播主体 -->
    <swiper
      class="guide-swiper"
      :current="currentIndex"
      :indicator-dots="false"
      @change="onSwiperChange"
    >
      <swiper-item v-for="(slide, index) in slides" :key="index" class="swiper-item">
        <view class="slide-content">
          <image
            class="slide-image"
            :src="slide.image"
            mode="aspectFit"
          />
          <view class="slide-text-wrap">
            <text class="slide-title">{{ slide.title }}</text>
            <text class="slide-desc">{{ slide.desc }}</text>
          </view>
        </view>
      </swiper-item>
    </swiper>

    <!-- 底部区域：指示器 + 按钮 -->
    <view class="bottom-area">
      <!-- 圆点指示器 -->
      <view class="dot-indicators">
        <view
          v-for="(slide, index) in slides"
          :key="index"
          class="dot"
          :class="{ active: index === currentIndex }"
        />
      </view>

      <!-- 操作按钮 -->
      <view class="action-wrap">
        <button
          v-if="currentIndex < slides.length - 1"
          class="btn-next"
          @tap="handleNext"
        >
          下一步
        </button>
        <button
          v-else
          class="btn-start"
          @tap="handleStart"
        >
          立即体验
        </button>
      </view>
    </view>
  </view>
</template>

<script>
/**
 * @file hjf_intro.vue
 * @description P23 新用户引导页（轮播引导）
 *
 * 功能：
 * - 3 屏轮播引导（平台介绍 / 公排规则 / 会员积分体系）
 * - 底部圆点指示器实时同步当前页
 * - 右上角"跳过"按钮（最后一屏隐藏）
 * - 最后一屏显示"立即体验"按钮，点击跳转首页
 * - 跳过或完成后写入本地存储，不再重复展示
 *
 * Mock 数据：MOCK_GUIDE_DATA（来自 @/utils/hjfMockData.js）
 * 路由注册：pages/guide/hjf_intro（navigationStyle: custom）
 *
 * @module pages/guide/hjf_intro
 * @author OpenClaw Agent
 * @version 1.0.0
 * @see docs/frontend-new-pages-spec.md 第 4.2.1 节
 */

import { MOCK_GUIDE_DATA } from '@/utils/hjfMockData.js';

/** 本地存储 key，用于记录引导已读状态 */
const GUIDE_READ_KEY = 'hjf_guide_read';

export default {
  name: 'HjfIntro',

  data() {
    return {
      /**
       * 引导轮播幻灯片列表
       * @type {Array<{title: string, desc: string, image: string}>}
       */
      slides: [],

      /**
       * 当前激活的幻灯片索引（0-based）
       * @type {number}
       */
      currentIndex: 0
    };
  },

  onLoad() {
    this.initSlides();
  },

  methods: {
    /**
     * 初始化轮播数据，从 Mock 数据加载幻灯片列表
     * Phase 4 集成后可替换为真实 API 调用
     */
    initSlides() {
      this.slides = MOCK_GUIDE_DATA.slides || [];
    },

    /**
     * swiper change 事件回调，同步当前索引到圆点指示器
     * @param {UniApp.SwiperChangeEvent} e - swiper change 事件对象
     */
    onSwiperChange(e) {
      this.currentIndex = e.detail.current;
    },

    /**
     * 点击"下一步"按钮，切换到下一张幻灯片
     */
    handleNext() {
      if (this.currentIndex < this.slides.length - 1) {
        this.currentIndex += 1;
      }
    },

    /**
     * 点击"跳过"按钮，记录已读状态并直接跳转首页
     */
    handleSkip() {
      this.markGuideRead();
      this.goHome();
    },

    /**
     * 点击"立即体验"按钮（最后一屏），记录已读状态并跳转首页
     */
    handleStart() {
      this.markGuideRead();
      this.goHome();
    },

    /**
     * 将引导已读状态写入本地存储
     * 后续在 App.vue 或首页 onShow 中读取此标记，决定是否跳转引导页
     */
    markGuideRead() {
      uni.setStorageSync(GUIDE_READ_KEY, '1');
    },

    /**
     * 跳转至应用首页，使用 reLaunch 清空页面栈
     */
    goHome() {
      uni.reLaunch({ url: '/pages/index/index' });
    }
  }
};
</script>

<style scoped lang="scss">
$theme-gradient: linear-gradient(135deg, var(--view-theme, #e93323) 0%, #ff7043 100%);
$slide-height: calc(100vh - 280rpx);

.guide-page {
  position: relative;
  width: 100%;
  height: 100vh;
  background: #fff;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

// 跳过按钮
.skip-btn {
  position: fixed;
  top: calc(var(--status-bar-height) + 20rpx);
  right: 40rpx;
  z-index: 10;
  padding: 12rpx 30rpx;
  background: rgba(0, 0, 0, 0.12);
  border-radius: 40rpx;

  .skip-text {
    font-size: 26rpx;
    color: #fff;
  }
}

// 轮播主体
.guide-swiper {
  flex: 1;
  width: 100%;
  height: $slide-height;
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
  padding: 0 60rpx;
  box-sizing: border-box;
}

.slide-image {
  width: 560rpx;
  height: 420rpx;
  margin-bottom: 80rpx;
}

.slide-text-wrap {
  text-align: center;
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

// 底部区域
.bottom-area {
  padding: 0 60rpx 80rpx;
  display: flex;
  flex-direction: column;
  align-items: center;
}

// 圆点指示器
.dot-indicators {
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 60rpx;
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
    background: var(--view-theme, #e93323);
  }
}

// 按钮公共
.btn-next,
.btn-start {
  width: 560rpx;
  height: 96rpx;
  border-radius: 48rpx;
  font-size: 34rpx;
  font-weight: bold;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;

  &::after {
    border: none;
  }
}

.btn-next {
  background: #f5f5f5;
  color: #555;
}

.btn-start {
  background: $theme-gradient;
  color: #fff;
  box-shadow: 0 8rpx 30rpx rgba(233, 51, 35, 0.35);
}
</style>
